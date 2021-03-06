<?php

namespace service;

use Doctrine\DBAL\Connection as db;
use service\cache;

class distance
{
	protected $db;
	protected $cache;

	protected $lat;
	protected $lng;
	protected $geo = false;
	protected $to_lat;
	protected $to_lng;
	protected $to_geo = false;
	protected $to = [];
	protected $dist;

	public function __construct(db $db, cache $cache)
	{
		$this->db = $db;
		$this->cache = $cache;
	}

	public function set_from_geo(
		string $adr = '',
		string $s_id = '',
		string $s_schema = ''):self
	{
		if ($this->geo)
		{
			return $this;
		}

		if ($s_id && $s_schema)
		{
			$adr = $this->db->fetchColumn('select c.value
					from ' . $s_schema . '.contact c, ' . $s_schema . '.type_contact tc
					where c.id_user = ?
						and c.id_type_contact = tc.id
						and tc.abbrev = \'adr\'', [$s_id]);
		}

		if (!$adr)
		{
			$this->geo = false;
			return $this;
		}

		$geo = $this->cache->get('geo_' . $adr);

		if (count($geo))
		{
			$this->lat = deg2rad($geo['lat']);
			$this->lng = deg2rad($geo['lng']);

			$this->geo = true;
		}
		else
		{
			$this->geo = false;
		}

		return $this;
	}

	public function set_to_geo(string $adr = ''):self
	{
		$adr = trim($adr);

		if (!$adr)
		{
			return $this;
		}

		$geo = $this->cache->get('geo_' . $adr);

		if (count($geo))
		{
			$this->to[] = [
				'lat'	=> $geo['lat'],
				'lng'	=> $geo['lng'],
			];

			$this->to_lat = deg2rad($geo['lat']);
			$this->to_lng = deg2rad($geo['lng']);
		}

		return $this;
	}

	public function has_to_data():bool
	{
		return count($this->to) > 0;
	}

	public function get_to_data():string
	{
		return htmlspecialchars(json_encode($this->to));
	}

	public function calc():self
	{
		if (!$this->geo || !$this->to_geo)
		{
			return $this;
		}

		$lat_d = $this->to_lat - $this->lat;
		$lng_d = $this->to_lng - $this->lng;

		$angle = 2 * asin(sqrt(pow(sin($lat_d / 2), 2) + cos($this->lat) * cos($this->to_lat) * pow(sin($lng_d / 2), 2)));

		$this->dist = 6371 * $angle;

		return $this;
	}

	public function format():string
	{
		if (!$this->geo || !$this->to_geo)
		{
			return '';
		}

		if ($this->dist < 1)
		{
			return (round($this->dist * 10) * 100) . ' m';
		}

		if ($this->dist < 10)
		{
			return (round($this->dist * 10) / 10) . ' km';
		}

		return round($this->dist) . ' km';
	}

	public function format_parenthesis():string
	{
		$p = $this->format();

		return $p ? ' (' . $p . ')' : '';
	}
}
