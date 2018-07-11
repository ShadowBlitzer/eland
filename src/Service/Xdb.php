<?php declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Connection as db;
use Doctrine\DBAL\Driver\PDOStatement;

/*
                                Table "xdb.events"
   Column    |            Type             |              Modifiers
-------------+-----------------------------+--------------------------------------
 ts          | timestamp without time zone | default timezone('utc'::text, now())
 user_id     | integer                     | default 0
 user_schema | character varying(60)       |
 agg_id      | character varying(255)      | not null
 agg_type    | character varying(60)       |
 agg_version | integer                     | not null
 data        | jsonb                       |
 event_time  | timestamp without time zone | default timezone('utc'::text, now())
 ip          | character varying(60)       |
 event       | character varying(128)      |
 agg_schema  | character varying(60)       |
 eland_id    | character varying(40)       |
 uid         | character varying(8)        |
 eid         | character varying(30)       |
Indexes:
    "events_pkey" PRIMARY KEY, btree (agg_id, agg_version)
    "events_agg_id_idx" btree (agg_id)
    "events_eid_agg_version_idx" btree (eid, agg_version)


                                 Table "xdb.aggs"
   Column    |            Type             |              Modifiers
-------------+-----------------------------+--------------------------------------
 agg_id      | character varying(255)      | not null
 agg_version | integer                     | not null
 data        | jsonb                       |
 user_id     | integer                     | default 0
 user_schema | character varying(60)       | default ''::character varying
 ts          | timestamp without time zone | default timezone('utc'::text, now())
 agg_type    | character varying(60)       | not null
 agg_schema  | character varying(60)       | not null
 ip          | character varying(60)       |
 event       | character varying(128)      |
 eland_id    | character varying(40)       |
 event_time  | timestamp without time zone | default timezone('utc'::text, now())
 eid         | character varying(30)       |
 uid         | character varying(8)        |
Indexes:
    "aggs_pkey" PRIMARY KEY, btree (agg_id)
    "aggs_agg_schema_idx" btree (agg_schema)
    "aggs_agg_type_agg_schema_idx" btree (agg_type, agg_schema)
    "aggs_agg_type_idx" btree (agg_type)
    "aggs_eid_agg_schema_idx" btree (eid, agg_schema)
    "aggs_eid_idx" btree (eid)
*/

class Xdb
{
	private $ip;
	private $userSchema = '';
	private $userId = 0;
	private $db;

	public function __construct(db $db)
	{
		$this->db = $db;

		if (php_sapi_name() == 'cli')
		{
			$this->ip = '';
		}
		else
		{
			$this->ip = $_SERVER['REMOTE_ADDR'];
		}
	}

	/*
	 */

	public function setUser(string $userSchema, $userId):Xdb
	{
		$this->userSchema = $userSchema;
		$this->userId = ctype_digit((string) $userId) ? $userId : 0;

		return $this;
	}

	/*
	 *
	 */

	public function set(string $aggType, string $elandId, string $aggSchema, array $data, string $eventTime = ''):Xdb
	{
		$aggId = $aggSchema . '_' . $aggType . '_' . $elandId;

		$row = $this->db->fetchAssoc('select data, agg_version
			from xdb.aggs
			where agg_id = ?', [$aggId]);

		if ($row)
		{
			$prevData = json_decode($row['data'], true);

			$data = array_diff_assoc($data, $prevData);
			$aggVersion = $row['agg_version'] + 1;
			$ev = 'updated';
		}
		else
		{
			$aggVersion = 1;
			$ev = 'created';
		}

		if (!count($data))
		{
			return $this;
		}

		$event = $aggType . '_' . $ev;

		$insert = [
			'user_id'		=> $this->userId,
			'user_schema'	=> $this->userSchema,
			'agg_id'		=> $aggId,
			'agg_type'		=> $aggType,
			'agg_schema'	=> $aggSchema,
			'eland_id'		=> $elandId,
			'agg_version'	=> $aggVersion,
			'event'			=> $aggType . '_' . $ev,
			'data'			=> json_encode($data),
			'ip'			=> $this->ip,
		];

		if ($eventTime)
		{
			$insert['event_time'] = $eventTime;
		}

		try
		{
			$this->db->beginTransaction();

			$this->db->insert('xdb.events', $insert);

			if ($aggVersion === 1)
			{
				$this->db->insert('xdb.aggs', $insert);
			}
			else
			{
				unset($insert['data']);
				$update = $insert;
				$update['data'] = json_encode(array_merge($prevData, $data));

				$this->db->update('xdb.aggs', $update, ['agg_id' => $aggId]);
			}

			$this->db->commit();
		}
		catch(Exception $e)
		{
			$this->db->rollback();
			throw $e;
		}

		return $this;
	}

	/*
	 *
	 */

	public function del(string $aggType, string $elandId , string $aggSchema):Xdb
	{
		$aggId = $aggSchema . '_' . $aggType . '_' . $elandId;

		$aggVersion = $this->db->fetchColumn('select agg_version
			from xdb.aggs
			where agg_id = ?', [$aggId]);

		if (!$aggVersion)
		{
			return $this; // 'Not found: ' . $agg_id . ', could not delete' exception;
		}

		$insert = [
			'user_id'		=> $this->userId,
			'user_schema'	=> $this->userSchema,
			'agg_id'		=> $aggId,
			'agg_type'		=> $aggType,
			'agg_schema'	=> $aggSchema,
			'eland_id'		=> $elandId,
			'agg_version'	=> $aggVersion + 1,
			'event'			=> $aggType . '_deleted',
			'data'			=> '{}',
			'ip'			=> $this->ip,
		];

		try
		{
			$this->db->beginTransaction();

			$this->db->insert('xdb.events', $insert);

			$this->db->delete('xdb.aggs', ['agg_id' => $aggId]);

			$this->db->commit();
		}
		catch(Exception $e)
		{
			$this->db->rollback();
			throw $e;
		}

		return $this;
	}

	/*
	 *
	 */

	public function get(string $aggType, string $elandId, string $aggSchema):array
	{
		$aggId = $aggSchema . '_' . $aggType . '_' . $elandId;

		$row = $this->db->fetchAssoc('select * from xdb.aggs where agg_id = ?', [$aggId]);

		if (!$row)
		{
			return [];
		}

		$row['data'] = json_decode($row['data'], true);

		error_log(' - xdb get ' . $aggId . ' - ');

		return $row;
	}

	/**
	 * method getMany renamed to getFiltered
	 */

	public function getFiltered(array $filters = [], string $queryExtra = ''):array
	{
		$stmt = $this->getFilterStatement($filters, $queryExtra, false);

		$ary = [];

		while ($row = $stmt->fetch())
		{
			$row['data'] = json_decode($row['data'], true);

			$ary[$row['agg_id']] = $row;
		}

		error_log(' - Xdb::getFiltered - ' . json_encode($filters));

		return $ary;
	}

	public function getFilteredData(array $filters = [], string $queryExtra = ''):array
	{
		$stmt = $this->getFilterStatement($filters, $queryExtra, false);

		$ary = [];

		while ($row = $stmt->fetch())
		{			
			$ary[$row['agg_id']] = json_decode($row['data'], true);
			$ary[$row['agg_id']]['id'] = $row['eland_id'];	
			$ary[$row['agg_id']]['ts'] = $row['ts'];						
		}

		error_log(' - Xdb::getFilteredData - ' . json_encode($filters));

		return $ary;
	}	

	public function countFiltered(array $filters = [], string $queryExtra = ''):int
	{
		$stmt = $this->getFilterStatement($filters, $queryExtra, true);

		$row = $stmt->fetch();

		return $row['count'];
	}

	private function getFilterStatement(array $filters, string $queryExtra = '', bool $isCount):PDOStatement
	{
		$sqlWhere = $sqlParams = $sqlTypes = [];

		if (isset($filters['agg_id_ary']))
		{
			$sqlWhere[] = 'agg_id in (?)';
			$sqlParams[] = $filters['agg_id_ary'];
			$sqlTypes[] = \Doctrine\DBAL\Connection::PARAM_STR_ARRAY;
		}

		unset($filters['agg_id_ary']);

		if (isset($filters['access']))
		{
			$sqlWhere[] = 'data->>\'access\' in (?)';
			$sqlParams[] = $filters['access'];
			$sqlTypes[] = \Doctrine\DBAL\Connection::PARAM_STR_ARRAY;
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
					$sqlWhere[] = $key . ' ' . $v;
				}
				else
				{
					$sqlWhere[] = $key . ' ' . $k . ' ?';
					$sqlParams[] = $v;
					$sqlTypes[] = \PDO::PARAM_STR;
				}
			}
			else
			{
				$sqlWhere[] = $key . ' = ?';
				$sqlParams[] = $value;
				$sqlTypes[] = \PDO::PARAM_STR;
			}
		}

		$query = 'select ';
		$query .= $isCount ? 'count(*)' : '*';
		$query .= ' from xdb.aggs';

		if (count($sqlWhere))
		{
			$query .= ' where ' . implode(' and ', $sqlWhere);
		}

		$query .= $queryExtra ? ' ' . $queryExtra : '';

		return $this->db->executeQuery($query, $sqlParams, $sqlTypes);
	}

	/**
	 *
	 */
/// TODO count what? type? events?
	public function count(string $aggType, string $elandId, string $aggSchema):int
	{
		$sqlWhere = $sqlParams = [];

		$sqlWhere[] = 'agg_type = ?';
		$sqlParams[] = $aggType;

		$sqlWhere[] = 'eland_id = ?';
		$sqlParams[] = $elandId;

		$sqlWhere[] = 'agg_schema = ?';
		$sqlParams[] = $aggSchema;

		$where = count($sqlWhere) ? ' where ' . implode(' and ', $sqlWhere) : '';

		return $this->db->fetchColumn('select count(*) from xdb.aggs' . $where, $sqlParams);
	}
}
