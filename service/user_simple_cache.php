<?php declare(strict_types=1);

namespace service;

use service\xdb;
use Doctrine\DBAL\Connection as db;
use Predis\Client as predis;
use exception\logical_exception;

class user_simple_cache
{
	private $db;
	private $predis;
	private $ttl = 120; //2592000;
	private $prefix = 'user_simple_cache_';

	public function __construct(db $db, predis $predis)
	{
		$this->db = $db;
		$this->predis = $predis;
	}

	public function clear(string $schema)
	{
		$key = $this->prefix . $schema;
		$this->predis->del($key);
	}

	public function get(string $schema)
	{
		$key = $this->prefix . $schema;

		if ($users = $this->predis->get($key))
		{
			return unserialize($users);
		}

		$users = [];

		$rs = $this->db->prepare('select id, letscode, name, status, accountrole, adate from ' . 
			$schema . '.users');

		$rs->execute();

		while ($row = $rs->fetch())
		{
			$status = $row['status'];
			$role = $row['accountrole'];
			$adate = $row['adate'];
			unset($user_type);

			if (in_array($status, [1, 2, 7]) && $role === 'interlets')
			{
				$user_type = 'interlets';
			}
			else if ($status === 1 || $status === 2)
			{
				$user_type = 'active';
			}
			else if (in_array($status, [5, 6]) || ($status === 0 && !isset($adate)))
			{
				$user_type = 'pre-active';
			}
			else if ($status === 0 && isset($adate))
			{
				$user_type = 'post-active';
			}

			if (!isset($user_type))
			{
				throw new logical_exception(sprintf(
					'no user_type defined for status %s, accountrole %s and 
					adate %s in %s', $status, $role, $adate, __CLASS__));
			}

			$users[$row['id']] = [$user_type, $row['letscode'] . ' ' . $row['name']];
		}

		$this->predis->set($key, serialize($users));

		return $users;
	}
}
