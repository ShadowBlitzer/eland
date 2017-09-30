<?php

namespace converter;

use service\xdb;
use Doctrine\DBAL\Connection as db;
use Predis\Client as redis;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class user_converter
{
	private $db;
	private $xdb;
	private $redis;
	private $ttl = 2592000;
	private $redis_prefix = 'user_cache_';
	private $schema;
	private $is_cli;

	public function __construct(db $db, xdb $xdb, redis $redis, string $schema)
	{
		$this->db = $db;
		$this->xdb = $xdb;
		$this->redis = $redis;
		$this->schema = $schema;
		$this->is_cli = php_sapi_name() === 'cli' ? true : false;
	}

	public function clear(int $id)
	{
		$redis_key = $this->redis_prefix . $schema . '_' . $id;

		$this->redis->del($redis_key);
		unset($this->local[$schema][$id]);

		return;
	}

	public function get(int $id)
	{
		$redis_key = $this->redis_prefix . $schema . '_' . $id;

		if (isset($this->local[$schema][$id]))
		{
			return $this->local[$schema][$id];
		}

		if ($this->redis->exists($redis_key))
		{
			$user = unserialize($this->redis->get($redis_key));

			if (!$this->is_cli)
			{
				$this->local[$schema][$id] = $user;
			}

			return $user;
		}

		$user = $this->read_from_db($id, $schema);

		if (isset($user))
		{
			$this->redis->set($redis_key, serialize($user));
			$this->redis->expire($redis_key, $this->ttl);
			if (!$this->is_cli)
			{
				$this->local[$schema][$id] = $user;
			}
		}

		return $user;
	}

	private function read_from_db(int $id, string $schema)
	{
		$user = $this->db->fetchAssoc('select * from ' . $schema . '.users where id = ?', [$id]);

		if (!is_array($user))
		{
			return [];
		}

		// hack eLAS compatibility (in eLAND limits can be null)
		$user['minlimit'] = $user['minlimit'] === -999999999 ? null : $user['minlimit'];
		$user['maxlimit'] = $user['maxlimit'] === 999999999 ? null : $user['maxlimit'];

		$row = $this->xdb->get('user_fullname_access', $id, $schema);

		if ($row)
		{
			$user += ['fullname_access' => $row['data']['fullname_access']];
		}
		else
		{
			$user += ['fullname_access' => 'admin'];
			$this->xdb->set('user_fullname_access', $id, ['fullname_access' => 'admin'], $schema);
		}

		if ($user['accountrole'] === 'interlets')
		{

		}

		return $user;
	}

	/**
	 * for periodic process for when cache gets out sync
	 */
	public function sync(int $id, string $schema)
	{
		$user = $this->read_from_db($id, $schema);

		if (!count($user))
		{
			return;
		}

		$user = serialize($user);

		$redis_key = $this->redis_prefix . $schema . '_' . $id;

		if ($this->redis->exists($redis_key))
		{
			if ($this->redis->get($redis_key) === $user)
			{
				return;
			}
		}

		$this->redis->set($redis_key, $user);
		$this->redis->expire($redis_key, $this->ttl);
		unset($this->local[$schema][$id]);
	}
}
