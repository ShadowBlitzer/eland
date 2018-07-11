<?php declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Connection as Db;
use Predis\Client as Predis;

/*
                          Table "xdb.cache"
 Column  |            Type             |              Modifiers
---------+-----------------------------+--------------------------------------
 id      | character varying(255)      | not null
 data    | jsonb                       |
 ts      | timestamp without time zone | default timezone('utc'::text, now())
 expires | timestamp without time zone |
Indexes:
    "cache_pkey" PRIMARY KEY, btree (id)
*/

class Cache
{
	private $db;
	private $redis;
	private $prefix = 'cache_';

	public function __construct(Db $db, Predis $redis)
	{
		$this->db = $db;
		$this->redis = $redis;
	}

	public function set(string $id, array $data, int $expires = 0)
	{
		$data = json_encode($data);

		$key = $this->prefix . $id;

		$this->redis->set($key, $data);

		if ($expires)
		{
			$this->redis->expire($key, $expires);
		}

		$insert = [
			'id'			=> $id,
			'data'			=> $data,
		];

		if ($expires !== 0)
		{
			$insert['expires'] = gmdate('Y-m-d H:i:s', time() + $expires);
		}

		try
		{
			$this->db->beginTransaction();

			if ($this->db->fetchColumn('select id from xdb.cache where id = ?', [$id]))
			{
				$this->db->update('xdb.cache', ['data' => $data], ['id' => $id]);
			}
			else
			{
				$this->db->insert('xdb.cache', $insert);
			}

			$this->db->commit();
		}
		catch(Exception $e)
		{
			$this->db->rollback();
			$this->redis->del($key);
			throw $e;
			exit;
		}
	}

	public function get(string $id):array
	{
		return json_decode($this->getString($id) ?? '{}', true);
	}

	public function getString(string $id) // returns null when key not available // TODO conditional return ?string in php7.1 
	{
		$key = $this->prefix . $id;

		$data = $this->redis->get($key);

		if (isset($data))
		{
			return $data;
		}

		$row = $this->db->fetchAssoc('select data, expires
			from xdb.cache
			where id = ?
				and (expires < timezone(\'utc\'::text, now())
					or expires is null)', [$id]);

		if ($row)
		{
			$this->redis->set($key, $row['data']);

			if (isset($data['expires']))
			{
				$this->redis->expireat($key, $data['expires']);
			}

			return $row['data'];
		}

		return null;
	}

	public function exists(string $id):bool 
	{
		$key = $this->prefix . $id;

		if ($this->redis->exists($key))
		{
			return true;
		}

		return $this->db->fetchColumn('select id
			from xdb.cache
			where id = ?
				and (expires < timezone(\'utc\'::text, now())
					or expires is null)', [$id]) ? true : false;
	}

	public function expire(string $id, int $time):Cache
	{
		$id = trim($id);

		if (!$id)
		{
			return $this;
		}

		$this->redis->expire('cache_' . $id, $time);

		$time = gmdate('Y-m-d H:i:s', $time);

		$this->db->update('xdb.cache', ['expires' => $time], ['id' => $id]);

		return $this;
	}

	public function del(string $id):Cache
	{
		$id = trim($id);

		if (!$id)
		{
			return $this;
		}

		$this->redis->del('cache_' . $id);

		$this->db->delete('xdb.cache', ['id' => $id]);

		return $this;
	}

	public function cleanup():Cache
	{
		$this->db->executeQuery('delete from xdb.cache
			where expires < timezone(\'utc\'::text, now()) and expires is not null');

		return $this;
	}
}

