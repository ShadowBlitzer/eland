<?php

namespace App\Repository;

use App\Service\Xdb;
use Doctrine\DBAL\Connection as Db;
use Predis\Client as Redis;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserRepository
{
	private $db;
	private $xdb;
	private $redis;
	private $ttl = 2592000;
	private $redisPrefix = 'user_cache_';
	private $isCli;

	public function __construct(Db $db, Xdb $xdb, Redis $redis)
	{
		$this->db = $db;
		$this->xdb = $xdb;
		$this->redis = $redis;
		$this->isCli = php_sapi_name() === 'cli' ? true : false;
	}

	public function clear(int $id, string $schema)
	{
		$redisKey = $this->redisPrefix . $schema . '_' . $id;

		$this->redis->del($redisKey);
		unset($this->local[$schema][$id]);

		return;
	}

	public function get(int $id, string $schema):array
	{
		$redisKey = $this->redisPrefix . $schema . '_' . $id;

		if (isset($this->local[$schema][$id]))
		{
			return $this->local[$schema][$id];
		}

		if ($this->redis->exists($redisKey))
		{
			$user = unserialize($this->redis->get($redisKey));

			if (!$this->isCli)
			{
				$this->local[$schema][$id] = $user;
			}

			return $user;
		}

		$user = $this->readFromDb($id, $schema);

		if (isset($user))
		{
			$this->redis->set($redisKey, serialize($user));
			$this->redis->expire($redisKey, $this->ttl);
		
			if (!$this->isCli)
			{
				$this->local[$schema][$id] = $user;
			}
		}

		return $user;
	}

	private function readFromDb(int $id, string $schema)
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

		$redisKey = $this->redisPrefix . $schema . '_' . $id;

		if ($this->redis->exists($redisKey))
		{
			if ($this->redis->get($redisKey) === $user)
			{
				return;
			}
		}

		$this->redis->set($redisKey, $user);
		$this->redis->expire($redisKey, $this->ttl);
		unset($this->local[$schema][$id]);
	}
}
