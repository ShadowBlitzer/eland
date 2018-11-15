<?php declare(strict_types=1);

namespace App\Service;

use App\Service\Xdb;
use Doctrine\DBAL\Connection as Db;
use Predis\Client as Predis;

class UserCache
{
	private $db;
	private $xdb;
	private $predis;
	private $ttl = 2592000;
	private $local;
	private $predis_prefix = 'user_cache_';
	private $is_cli;

	public function __construct(db $db, Xdb $xdb, Predis $predis)
	{
		$this->db = $db;
		$this->xdb = $xdb;
		$this->predis = $predis;
		$this->is_cli = php_sapi_name() === 'cli' ? true : false;
	}

	public function clear(int $id, string $schema)
	{
		$predis_key = $this->predis_prefix . $schema . '_' . $id;

		$this->predis->del($predis_key);
		unset($this->local[$schema][$id]);

		return;
	}

	public function get(int $id, string $schema)
	{
		$predis_key = $this->predis_prefix . $schema . '_' . $id;

		if (isset($this->local[$schema][$id]))
		{
			return $this->local[$schema][$id];
		}

		if ($this->predis->exists($predis_key))
		{
			$user = unserialize($this->predis->get($predis_key));

			if (!$this->is_cli)
			{
				$this->local[$schema][$id] = $user;
			}

			return $user;
		}

		$user = $this->read_from_db($id, $schema);

		if (isset($user))
		{
			$this->predis->set($predis_key, serialize($user));
			$this->predis->expire($predis_key, $this->ttl);

			if (!$this->is_cli)
			{
				$this->local[$schema][$id] = $user;
			}
		}

		return $user;
	}

	private function read_from_db(int $id, string $schema)
	{
		$user = $this->db->fetchAssoc('select * 
			from ' . $schema . '.users 
			where id = ?', [$id]);

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
			$this->xdb->set('user_fullname_access', $id, $schema, ['fullname_access' => 'admin']);
		}

		if ($user['accountrole'] === 'interlets'
			&& $user['letscode'] !== '' 
			&& $user['letscode'] !== null)
		{
			$interlets_group = $app['db']->fetchAssoc('select *
				from ' . $schema . '.letsgroups
				where letcode = ?', [$user['letscode']]);	
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

		$predis_key = $this->predis_prefix . $schema . '_' . $id;

		if ($this->predis->exists($predis_key))
		{
			if ($this->predis->get($predis_key) === $user)
			{
				return;
			}
		}

		$this->predis->set($predis_key, $user);
		$this->predis->expire($predis_key, $this->ttl);
		unset($this->local[$schema][$id]);
	}
}
