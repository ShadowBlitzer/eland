<?php

namespace migrate_elas;

use Doctrine\DBAL\Connection as db;
use service\xdb;
use migrate_elas\base;
use migrate_elas\base_interface;

class currency extends base implements base_interface
{
	$map = [
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
		'date_format'						=> '%e %b %Y, %H:%M:%S',
		'weekly_mail_show_interlets'		=> 'recent',
		'weekly_mail_show_news'				=> 'recent',
		'weekly_mail_show_docs'				=> 'recent',
		'weekly_mail_show_forum'			=> 'recent',
		'weekly_mail_show_transactions'		=> 'recent',
		'weekly_mail_show_leaving_users'	=> 'recent',
		'weekly_mail_show_new_users'		=> 'recent',
		'weekly_mail_template'				=> 'messages_top',
		'default_landing_page'				=> 'messages',
		'homepage_url'						=> '',
		'template_lets'						=> '1',
		'interlets_en'						=> '1',
	];

	public function execute()
	{
		parent::execute();








	}

	public function get_step()
	{
		return 100;
	}
}
