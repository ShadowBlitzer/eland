<?php

namespace queue;

use queue\queue_interface;
use Doctrine\DBAL\Connection as db;
use service\cache;
use service\queue;
use service\user_cache;
use Monolog\Logger;
use service\geocode as geocode_service;

class geocode implements queue_interface
{
	protected $queue;
	protected $monolog;
	protected $cache;
	protected $db;
	protected $user_cache;

	protected $geocode_service;

	public function __construct(db $db, cache $cache, queue $queue,
		Logger $monolog, user_cache $user_cache, geocode_service $geocode_service)
	{
		$this->queue = $queue;
		$this->monolog = $monolog;
		$this->cache = $cache;
		$this->db = $db;
		$this->user_cache = $user_cache;
		$this->geocode_service = $geocode_service;
	}

	public function process(array $data):void
	{
		$adr = trim($data['adr']);
		$uid = $data['uid'];
		$sch = $data['schema'];

		if (!$adr || !$uid || !$sch)
		{
			error_log('geocode 1');
			return;
		}

		if ($this->cache->exists('geo_sleep'))
		{
			$this->monolog->debug('geocoding task is at sleep.', ['schema' => $sch]);
			return;
		}

		$user = $this->user_cache->get($uid, $sch);

		$log_user = 'user: ' . $sch . '.' .
			$user['letscode'] . ' ' .
			$user['name'] . ' (' . $uid . ')';

		$geo_status_key = 'geo_status_' . $adr;

		$key = 'geo_' . $adr;

		if (!$this->cache->exists($geo_status_key))
		{
			return;
		}

		$this->cache->set($geo_status_key, ['value' => 'error'], 31536000); // 1 year

		if (getenv('GEO_BLOCK') === '1')
		{
			error_log('geo coding is blocked. not processing: ' . json_encode($data));
			return;
		}

		$coords = $this->geocode_service->getCoordinates($adr);

		if (count($coords))
		{
			$this->cache->set($key, $coords);
			$this->cache->del($geo_status_key);
			$this->cache->del('geo_sleep');

			$log = 'Geocoded: ' . $adr . ' : ' . implode('|', $coords);

			$this->monolog->info('(cron) ' .
				$log . ' ' . $log_user,
				['schema' => $sch]);

			return;
		}

		$log = 'Geocode return NULL for: ' . $adr;
		$this->monolog->info('cron geocode: ' . $log .
			' ' . $log_user, ['schema' => $sch]);

		return;
	}

	public function queue(array $data):void
	{
		if (!isset($data['schema']))
		{
			$this->monolog->debug('no schema set for geocode task');
			return;
		}
		if (!isset($data['uid']))
		{
			$this->monolog->debug('no uid set for geocode task', ['schema' => $data['schema']]);
			return;
		}
		if (!isset($data['adr']))
		{
			$this->monolog->debug('no adr set for geocode task', ['schema' => $data['schema']]);
			return;
		}
		$data['adr'] = trim($data['adr']);
		$this->queue->set('geocode', $data);
	}

	public function run($schema):void
	{
		$log_ary = [];

		$st = $this->db->prepare('select c.value, c.id_user
			from ' . $schema . '.contact c, ' .
				$schema . '.type_contact tc, ' .
				$schema . '.users u
			where c.id_type_contact = tc.id
				and tc.abbrev = \'adr\'
				and c.id_user = u.id
				and u.status in (1, 2)');

		$st->execute();

		while (($row = $st->fetch()) && count($log_ary) < 20)
		{
			$data = [
				'adr'		=> trim($row['value']),
				'uid'		=> $row['id_user'],
				'schema'	=> $schema,
			];

			$this->queue($data);
			$log_ary[] = link_user($row['id_user'], $schema, false, true) . ': ' . $data['adr'];
		}

		if (count($log_ary))
		{
			$this->monolog->info('Addresses queued for geocoding: ' . implode(', ', $log_ary), ['schema' => $schema]);
		}
	}

	public function get_interval():int
	{
		return 120;
	}
}
