<?php

namespace task;

use Doctrine\DBAL\Connection as db;
use service\cache;
use service\systems;

class get_elas_intersystem_domains
{
	protected $cache;
	protected $db;
	protected $systems;

	public function __construct(
		db $db,
		cache $cache,
		systems $systems
	)
	{
		$this->db = $db;
		$this->cache = $cache;
		$this->systems = $systems;
	}

	function process():void
	{
		$elas_intersystem_domains = $this->cache->get('elas_interlets_domains');

		$domains = [];

		foreach ($this->systems->get_schemas() as $sch)
		{
			$groups = $this->db->fetchAll('select url, remoteapikey, id
				from ' . $sch . '.letsgroups
				where apimethod = \'elassoap\'
					and remoteapikey is not null
					and url <> \'\'');

			foreach ($groups as $group)
			{
				$domain = strtolower(parse_url($group['url'], PHP_URL_HOST));

				if ($this->systems->get_schema($domain))
				{
					continue;
				}

				if (!$group['remoteapikey'])
				{
					continue;
				}

				$domains[$domain][$sch] = [
					'remoteapikey'	=> trim($group['remoteapikey']),
					'group_id'		=> $group['id'],
				];
			}
		}

		if ($elas_intersystem_domains == $domains)
		{
			return;
		}

		$this->cache->set('elas_interlets_domains', $domains);
		$this->cache->set('elas_intersystem_domains', $domains);

		return;
	}
}
