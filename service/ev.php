<?php

namespace service;

use Doctrine\DBAL\Connection as db;
use Predis\Client as Redis;
use service\uuid;

/*
Table "xdb.ev"
Column  |            Type             |              Modifiers
--------+-----------------------------+--------------------------------------
ts      | timestamp without time zone | default timezone('utc'::text, now())
id      | uuid                        | not null
version | integer                     | not null
data    | jsonb                       |
meta    | jsonb                       |
Indexes:
"ev_pkey" PRIMARY KEY, btree (id, version)

Table "xdb.ag"
Column  |            Type             |              Modifiers
--------+-----------------------------+--------------------------------------
ts      | timestamp without time zone | default timezone('utc'::text, now())
id      | uuid                        | not null
type    | character varying(32)       |
segment | uuid                        |
version | integer                     | not null
data    | jsonb                       |
meta    | jsonb                       |
Indexes:
"ag_pkey" PRIMARY KEY, btree (id)
"ag_type_segment_idx" btree (type, segment)

*/

class ev
{
	private $db;
	private $redis;
	private $uuid;

	public function __construct(db $db, Redis $redis, uuid $uuid)
	{
		$this->db = $db;
		$this->redis = $redis;
		$this->uuid = $uuid;
	}

	public function get_new_id()
	{
		while(true)
		{
			$id = $this->uuid->gen();

			if (!$this->db->fetchColumn('select id from xdb.ag where id = ?', [$id]))
			{
				return $id;
			}
		}		
	}

	public function set(string $id, array $data = [], array $meta = [])
	{
		if (!strlen($id))
		{
			// trow exception
			return 'ev: No id set';
		}

		$row = $this->db->fetchAssoc('select data, version, segment, type
			from xdb.ag
			where id = ?', [$id]);

		if ($row)
		{
			if (isset($data['segment'])
				&& $row['segment'] !== $data['segment'])
			{
				// throw exception segment mismatch
			}

			if (isset($data['type'])
				&& $row['type'] !== $data['type'])
			{
				// throw exception type mismatch
			}

			$type = $row['type'];
			$segment = $row['segment'];
			$prev_data = json_decode($row['data'], true);
			$data = array_diff_assoc($data, $prev_data);
			$version = $row['version'] + 1;
			$ev = 'updated';
		}
		else
		{
			$prev_data = [];
			$version = 1;
			$ev = 'created';
			$type = $data['type'] ?? '';
			$segment = $data['segment'] ?? '';
			$this->redis->del('ag_count_by_type_and_segment_' . $type . '_' . $segment);
		}

		if (!count($data))
		{
			return;
			// exception?
			// return 'xdb: no (new) data';
		}

		$redis_id = 'ag_' . $id;

		$meta['version'] = $version;
		$meta['ev'] = $ev;

		$data_json = json_encode($data);
		$meta_json = json_encode($meta);

		$full_data = array_merge($prev_data, $data);

		$full = [
			'id'			=> $id,
			'version'		=> $version,
			'data'			=> $full_data,
			'meta'			=> $meta,
			'type'			=> $type,
			'segment'		=> $segment,
		];

		$insert = [
			'id'			=> $id,
			'version'		=> $version,
			'data'			=> $data_json,
			'meta'			=> $meta_json,
		];

		try
		{
			$this->db->beginTransaction();

			$this->db->insert('xdb.ev', $insert);

			if ($version === 1)
			{
				$insert['type'] = $type;
				$insert['segment'] = $segment;
				$this->db->insert('xdb.ag', $insert);

			}
			else
			{
				unset($insert['data']);
				$update = $insert;
				$update['data'] = json_encode($full_data);
				$this->db->update('xdb.ag', $update, ['id' => $id]);
			}

			$this->db->commit();

			$this->redis->set($redis_id, json_encode($full));
		}
		catch(Exception $e)
		{
			$this->db->rollback();
			throw $e;
		}
	}

	/*
	 *
	 */

	public function del(string $id, array $meta = [])
	{
		if (!strlen($id))
		{
			// exception
			return 'ev.: No id set';
		}

		$version = $this->db->fetchColumn('select version
			from xdb.ag
			where id = ?', [$id]);

		if (!$version)
		{
			// not found exception
			return 'ev.: Not found: ' . $id . ', could not delete';
		}

		$meta['version'] = $version + 1;
		$meta['ev'] = 'deleted';

		$insert = [
			'id'			=> $id,
			'version'	    => $meta['version'],
			'data'			=> '{}',
			'meta'			=> json_encode($meta),
		];

		try
		{
			$this->redis->del('ag_' . $id);
			$this->db->beginTransaction();
			$this->db->insert('xdb.ev', $insert);
			$this->db->delete('xdb.ag', ['id' => $id]);
			$this->db->commit();
		}
		catch(Exception $e)
		{
			$this->db->rollback();
			throw $e;
		}
	}

	/*
	 *
	 */

	public function get(string $id): array
	{
		if (!strlen($id))
		{
			// id not set exception
			return [];
		}

		$redis_id = 'ag_' . $id;

		$row = $this->redis->get($redis_id);

		if ($row)
		{
			return json_decode($row, true);
		}

		$row = $this->db->fetchAssoc('select *
			from xdb.ag
			where id = ?', [$id]);

		if (!$row)
		{
			// 'object not found exception';
			return false;
		}

		$row['data'] = json_decode($row['data'], true);
		$row['meta'] = json_decode($row['meta'], true);

		$this->redis->set($redis_id, json_encode($row));

		return $row;
	}

	/**
	 *
	 */

	public function get_many(array $filters = [], string $query_extra = '')
	{
		$sql_where = [];
		$sql_params = [];
		$sql_types = [];

		if (isset($filters['id_ary']))
		{
			$sql_where[] = 'id in (?)';
			$sql_params[] = $filters['id_ary'];
			$sql_types[] = \Doctrine\DBAL\Connection::PARAM_STR_ARRAY;
		}

		unset($filters['id_ary']);

		if (isset($filters['access']))
		{
			$sql_where[] = 'meta->>\'access\' in (?)';
			$sql_params[] = $filters['access'];
			$sql_types[] = \Doctrine\DBAL\Connection::PARAM_STR_ARRAY;
		}

		unset($filters['access']);

		foreach ($filters as $key => $value)
		{
			if (is_array($value))
			{
				$v = reset($value);
				$k = key($value);

				if ($k === 0)
				{
					$sql_where[] = $key . ' ' . $v;
				}
				else
				{
					$sql_where[] = $key . ' ' . $k . ' ?';
					$sql_params[] = $v;
					$sql_types[] = \PDO::PARAM_STR;
				}
			}
			else
			{
				$sql_where[] = $key . ' = ?';
				$sql_params[] = $value;
				$sql_types[] = \PDO::PARAM_STR;
			}
		}

		$query = 'select * from xdb.ag';

		if (count($sql_where))
		{
			$query .= ' where ' . implode(' and ', $sql_where);
		}

		$query .= ($query_extra) ? ' ' . $query_extra : '';

		$stmt = $this->db->executeQuery($query, $sql_params, $sql_types);

		$ary = [];

		while ($row = $stmt->fetch())
		{
			$row['data'] = json_decode($row['data'], true);

			$ary[$row['id']] = $row;
		}

		error_log(' - xdb get_many - ');

		return $ary;
	}

	/**
	 *
	 */

	public function count_by_type_and_segment(string $type, string $segment = ''): int
	{
		$redis_id = 'ag_count_by_type_and_segment_' . $type . '_' . $segment;

		$count = $this->redis->get($redis_id);

		if (isset($count))
		{
			return (int) $count;
		}

		$sql_where = $sql_params = [];

		$sql_where[] = 'type = ?';
		$sql_params[] = $type;

		$sql_where[] = 'segment = ?';
		$sql_params[] = $segment;

		$where = ' where ' . implode(' and ', $sql_where);

		$count = $this->db->fetchColumn('select count(*)
			from xdb.ag' . $where, $sql_params);

		$this->redis->set($redis_id, $count);

		return (int) $count;
	}

	/**
	 *
	 */

	public function id_in_use(string $id): bool
	{
		return $this->db->fetchColumn('select id from xdb.ag where id = ?', [$id]) ? true : false;
	}

	public function count_by_data(array $data): int
	{
		$sql_where = $sql_params = [];

		foreach($data as $key => $val)
		{
			$sql_where[] = 'data->>\'' . $key . '\' = ?';
			$sql_params[] = $val;
		}

		$where = ' where ' . implode(' and ', $sql_where);

		$count = $this->db->fetchColumn('select count(*)
			from xdb.ag' . $where, $sql_params);

		return (int) $count;
	}

	public function get_by_data(array $data)
	{
		$sql_where = $sql_params = [];

		foreach($data as $key => $val)
		{
			$sql_where[] = 'data->>\'' . $key . '\' = ?';
			$sql_params[] = $val;
		}

		$where = ' where ' . implode(' and ', $sql_where);

		$row = $this->db->fetchAssoc('select *
			from xdb.ag' . $where, $sql_params);

		if (!$row)
		{
			// not found exception
			return false;
		}

		$row['data'] = json_decode($row['data'], true);
		$row['meta'] = json_decode($row['meta'], true);

		return $row;
	}
}
