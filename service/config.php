<?php

namespace service;

use service\xdb;
use Doctrine\DBAL\Connection as db;
use Predis\Client as predis;

class config
{
	private $monolog;
	private $db;
	private $xdb;
	private $predis;
	private $this_group;

	private $default = [
		'preset_minlimit'					=> '',
		'preset_maxlimit'					=> '',
		'users_can_edit_username'			=> '0',
		'users_can_edit_fullname'			=> '0',
		'registration_en'					=> '0',
		'registration_top_text'				=> '',
		'registration_bottom_text'			=> '',
		'registration_success_text'			=> '',
		'registration_success_url'			=> '',
		'forum_en'							=> '0',
		'css'								=> '',
		'msgs_days_default'					=> '365',
		'balance_equilibrium'				=> '0',
		'date_format'						=> 'month_abbrev',
		'periodic_mail_block_ary' 			=> 
			'+messages.recent,interlets.recent,forum.recent,news.recent,docs.recent,transactions.recent',
		'default_landing_page'				=> 'messages',
		'homepage_url'						=> '',
		'template_lets'						=> '1',
		'interlets_en'						=> '1',
	];

	public function __construct(db $db, xdb $xdb, predis $predis)
	{
		$this->predis = $predis;
		$this->db = $db;
		$this->xdb = $xdb;
	}

	public function set(string $name, string $value, string $schema)
	{
		$this->xdb->set('setting', $name, ['value' => $value], $schema);

		$this->predis->del($schema . '_config_' . $name);

		// prevent string too long error for eLAS database
		$value = substr($value, 0, 60);

		$this->db->update($schema . '.config', [
			'value' => $value, 
			'"default"' => 'f',
			], ['setting' => $name]);
	}

	public function get(string $key, string $schema)
	{
		if (isset($this->local_cache[$schema][$key]))
		{
			return $this->local_cache[$schema][$key];
		}

		$redis_key = $schema . '_config_' . $key;

		if ($this->predis->exists($redis_key))
		{
			return $this->local_cache[$schema][$key] = $this->predis->get($redis_key);
		}

		$row = $this->xdb->get('setting', $key, $schema);

		if ($row)
		{
			$value = $row['data']['value'];
		}
		else if (isset($this->default[$key]))
		{
			$value = $this->default[$key];
		}
		else
		{
			$value = $this->db->fetchColumn('select value 
				from ' . $schema . '.config 
				where setting = ?', [$key]);
			$this->xdb->set('setting', $key, ['value' => $value], $schema);
		}

		if (isset($value))
		{
			$this->predis->set($redis_key, $value);
			$this->predis->expire($redis_key, 2592000);
			$this->local_cache[$schema][$key] = $value;
		}

		return $value;
	}
}
