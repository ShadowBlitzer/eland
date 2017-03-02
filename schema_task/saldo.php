<?php

namespace eland\schema_task;

use eland\model\schema_task;
use Doctrine\DBAL\Connection as db;
use eland\xdb;
use eland\cache;
use Monolog\Logger;
use eland\queue\mail;
use eland\date_format;
use eland\distance;

use eland\schedule;
use eland\groups;
use eland\this_group;
use eland\interlets_groups;

class saldo extends schema_task
{
	private $db;
	private $xdb;
	private $cache;
	private $monolog;
	private $mail;
	private $s3_img_url;
	private $s3_doc_url;
	private $protocol;
	private $date_format;
	private $distance;

	public function __construct(db $db, xdb $xdb, cache $cache, Logger $monolog, mail $mail,
		string $s3_img_url, string $s3_doc_url, string $protocol,
		date_format $date_format, distance $distance, schedule $schedule,
		groups $groups, this_group $this_group,
		interlets_groups $interlets_groups)
	{
		parent::__construct($schedule, $groups, $this_group);
		$this->db = $db;
		$this->xdb = $xdb;
		$this->cache = $cache;
		$this->monolog = $monolog;
		$this->mail = $mail;
		$this->s3_img_url = $s3_img_url;
		$this->s3_doc_url = $s3_doc_url;
		$this->protocol = $protocol;
		$this->date_format = $date_format;
		$this->interlets_groups = $interlets_groups;
	}

	function process()
	{
		// vars

		$host = $this->groups->get_host($this->schema);

		if (!$host)
		{
			return;
		}

		$base_url = $this->protocol . $host;

		$treshold_time = gmdate('Y-m-d H:i:s', time() - readconfigfromdb('saldofreqdays', $this->schema) * 86400);
 	
		$msg_url = $base_url . '/messages.php?id=';
		$msgs_url = $base_url . '/messages.php';
		$news_url = $base_url . '/news.php?id=';
		$user_url = $base_url . '/users.php?id=';
		$login_url = $base_url . '/login.php?login=';
		$new_message_url = $base_url . '/messages.php?add=1';
		$new_transaction_url = $base_url . '/transactions.php?add=1';
		$account_edit_url = $base_url . '/users.php?edit=';

		$users = $news = $new_users = $leaving_users = $transactions = $messages = [];

		$forum = $interlets = $docs = [];

		$mailaddr = $mailaddr_public = $saldo_mail = [];

	// fetch active users

		$rs = $this->db->prepare('SELECT u.id,
				u.name, u.saldo, u.status, u.minlimit, u.maxlimit,
				u.letscode, u.postcode, u.cron_saldo
			FROM ' . $this->schema . '.users u
			WHERE u.status in (1, 2)');

		$rs->execute();

		while ($row = $rs->fetch())
		{
			$users[$row['id']] = $row;
		}

	// fetch mail addresses & cron_saldo

		$st = $this->db->prepare('select u.id, c.value, c.flag_public
			from ' . $this->schema . '.users u, ' . $this->schema . '.contact c, ' . $this->schema . '.type_contact tc
			where u.status in (1, 2)
				and u.id = c.id_user
				and c.id_type_contact = tc.id
				and tc.abbrev = \'mail\'');

		$st->execute();

		while ($row = $st->fetch())
		{
			$user_id = $row['id'];
			$mail = $row['value'];
			$mailaddr[$user_id][] = $mail;
			$mailaddr_public[$user_id][] = $row['flag_public'];

			if (!$users[$user_id] || !$users[$user_id]['cron_saldo'])
			{
				continue;
			}

			$saldo_mail[$user_id] = true;
		}

	// fetch images

		$image_ary = [];

		$rs = $this->db->prepare('select m.id, p."PictureFile"
			from ' . $this->schema . '.msgpictures p, ' . $this->schema . '.messages m
			where p.msgid = m.id
				and m.cdate >= ?', [$treshold_time]);

		$rs->bindValue(1, $treshold_time);
		$rs->execute();

		while ($row = $rs->fetch())
		{
			$image_ary[$row['id']][] = $row['PictureFile'];
		}

	// fetch addresses

		$addr = $addr_public = $addr_p = [];

		$rs = $this->db->prepare('select u.id, c.value, c.flag_public
			from ' . $this->schema . '.users u, ' . $this->schema . '.contact c, ' . $this->schema . '.type_contact tc
			where u.status in (1, 2)
				and u.id = c.id_user
				and c.id_type_contact = tc.id
				and tc.abbrev = \'adr\'');

		$rs->execute();

		while ($row = $rs->fetch())
		{
			$addr[$row['id']] = $row['value']; 
			$addr_public[$row['id']] = $row['flag_public'];
			$users[$row['id']]['adr'] = $row['value'];

			$geo = $this->cache->get('geo_' . $row['value']);

			if (count($geo))
			{
				if (isset($geo['lat']) && isset($geo['lng']))
				{
					$users_geo[$row['id']] = $geo;
				}
			}
		}

	// fetch messages

		$rs = $this->db->prepare('select m.id, m.content,
			m."Description" as description, m.msg_type, m.id_user,
			m.amount, m.units,
			u.name, u.letscode, u.postcode
			from ' . $this->schema . '.messages m, ' . $this->schema . '.users u
			where m.id_user = u.id
				and u.status IN (1, 2)
				and m.cdate >= ?
			order BY m.cdate DESC');

		$rs->bindValue(1, $treshold_time);
		$rs->execute();

		while ($row = $rs->fetch())
		{
			$uid = $row['id_user'];
			$adr = isset($addr_public[$uid]) && $addr_public[$uid] ? $addr[$uid] : '';

			$row['type'] = $row['msg_type'] ? 'offer' : 'want';
			$row['offer'] = $row['type'] == 'offer' ? true : false;
			$row['want'] = $row['type'] == 'want' ? true : false;
			$row['images'] = $image_ary[$row['id']];
			$row['url'] = $base_url . '/messages.php?id=' . $row['id'];
			$row['mail'] = $mailaddr[$uid] ?? '';
			$row['user'] = $row['letscode'] . ' ' . $row['name'];
			$row['user_url'] = $base_url . '/users.php?id=' . $uid;
			$row['addr'] = str_replace(' ', '+', $adr);
			$row['adr'] = $adr;

			if (isset($users_geo[$uid]))
			{
				$row['geo'] = $users_geo[$uid];
			}

			$messages[] = $row;
		}

	// interlets messages

		if (readconfigfromdb('weekly_mail_show_interlets', $this->schema) == 'recent')
		{
			$eland_ary = $this->interlets_groups->get_eland($this->schema);

			foreach ($eland_ary as $sch => $d)
			{
				$interlets_msgs = [];

				$rs = $this->db->prepare('select m.id, m.content,
					m."Description" as description, m.msg_type, m.id_user,
					m.amount, m.units,
					u.name, u.letscode, u.postcode
					from ' . $sch . '.messages m, ' . $sch . '.users u
					where m.id_user = u.id
						and m.local = \'f\'
						and u.status IN (1, 2)
						and m.cdate >= ?
					order BY m.cdate DESC');

				$rs->bindValue(1, $treshold_time);
				$rs->execute();

				while ($row = $rs->fetch())
				{
					$row['type'] = $row['msg_type'] ? 'offer' : 'want';
					$row['offer'] = $row['type'] == 'offer' ? true : false;
					$row['want'] = $row['type'] == 'want' ? true : false;
					$row['user'] = $row['letscode'] . ' ' . $row['name'];

					$interlets_msgs[] = $row;
				}

				if (count($interlets_msgs))
				{
					$interlets[] = [
						'group'		=> readconfigfromdb('systemname', $sch),
						'messages'	=> $interlets_msgs,
					];
				}
			}

			$elas_ary = $this->interlets_groups->get_elas($this->schema);

			foreach ($elas_ary as $group_id => $ary)
			{
				$interlets_msgs = [];

				$domain = strtolower(parse_url($ary['url'], PHP_URL_HOST)); // TODO: switch to $ary['domain']

				$elas_msgs = $this->cache->get($domain . '_elas_interlets_msgs');

				foreach ($elas_msgs as $m)
				{
					if ($m['fetched_at'] < $treshold_time)
					{
						continue;
					}

					$m['type'] = $m['ow'] == 'o' ? 'offer' : 'want';
					$m['offer'] = $m['type'] == 'offer' ? true : false;
					$m['want'] = $m['type'] == 'want' ? true : false;

					$interlets_msgs[] = $m;
				}

				if (count($interlets_msgs))
				{
					$interlets[] = [
						'group'		=> $ary['groupname'],
						'messages'	=> $interlets_msgs,
					];
				}
			}
		}

	// news

		$show_news = readconfigfromdb('weekly_mail_show_news', $this->schema);

		if ($show_news != 'none')
		{
			$rows = $this->xdb->get_many(['agg_schema' => $this->schema, 'agg_type' => 'news_access']);

			foreach ($rows as $row)
			{
				$news_access_ary[$row['eland_id']] = $row['data']['access'];
			}

			$query = 'select n.*, u.name, u.letscode
				from ' . $this->schema . '.news n, ' . $this->schema . '.users u
				where n.approved = \'t\'
					and n.published = \'t\'
					and n.id_user = u.id ';

			$query .= ($show_news == 'recent') ? 'and n.cdate > ? ' : '';

			$query .= 'order by n.cdate desc';

			$rs = $this->db->prepare($query);

			if ($show_news == 'recent')
			{
				$rs->bindValue(1, $treshold_time);
			}

			$rs->execute();

			while ($row = $rs->fetch())
			{
				if (isset($news_access_ary[$row['id']]))
				{
					$row['access'] = $news_access_ary[$row['id']];
				}
				else
				{
					$this->xdb->set('news_access', $news_id, ['access' => 'interlets'], $this->schema);
					$row['access'] = 'interlets';
				}

				if (!in_array($row['access'], ['users', 'interlets']))
				{
					continue;
				}

				$row['url'] = $base_url . '/news.php?id=' . $row['id'];
				$row['user'] = $row['letscode'] . ' ' . $row['name'];
				$row['user_url'] = $base_url . '/users.php?id=' . $row['id_user'];
				$row['itemdate_formatted'] = $this->date_format->get($row['itemdate'], 'day');

				$news[] = $row;
			}
		}

	// new users

		$show_new_users = readconfigfromdb('weekly_mail_show_new_users', $this->schema);

		if ($show_new_users != 'none')
		{

			$rs = $this->db->prepare('select u.id, u.name, u.letscode, u.postcode
				from ' . $this->schema . '.users u
				where u.status = 1
					and u.adate > ?');

			$time = gmdate('Y-m-d H:i:s', time() - readconfigfromdb('newuserdays', $this->schema) * 86400);
			$time = ($show_new_users == 'recent') ? $treshold_time: $time;

			$rs->bindValue(1, $time);
			$rs->execute();

			while ($row = $rs->fetch())
			{
				$row['url'] = $base_url . '/users.php?id=' . $row['id'];
				$row['text'] = $row['letscode'] . ' ' . $row['name'];

				$new_users[] = $row;
			}
		}

	// leaving users

		$show_leaving_users = readconfigfromdb('weekly_mail_show_leaving_users', $this->schema);

		if ($show_leaving_users != 'none')
		{

			$query = 'select u.id, u.name, u.letscode, u.postcode
				from ' . $this->schema . '.users u
				where u.status = 2';

			$query .= ($show_leaving_users == 'recent') ? ' and mdate > ?' : '';

			$rs = $this->db->prepare($query);

			if ($show_leaving_users == 'recent')
			{
				$rs->bindValue(1, $treshold_time);
			}

			$rs->execute();

			while ($row = $rs->fetch())
			{
				$row['url'] = $base_url . '/users.php?id=' . $row['id'];
				$row['text'] = $row['letscode'] . ' ' . $row['name'];

				$leaving_users[] = $row;
			}
		}

	// transactions

		$show_transactions = readconfigfromdb('weekly_mail_show_transactions', $this->schema);

		if ($show_transactions != 'none')
		{

			$rs = $this->db->prepare('select t.id_from, t.id_to, t.real_from, t.real_to,
					t.amount, t.cdate, t.description,
					uf.name as from_name, uf.letscode as from_letscode,
					ut.name as to_name, ut.letscode as to_letscode
				from ' . $this->schema . '.transactions t, ' . $this->schema . '.users uf, ' . $this->schema . '.users ut
				where t.id_from = uf.id
					and t.id_to = ut.id
					and t.cdate > ?');

			$rs->bindValue(1, $treshold_time);
			$rs->execute();

			while ($row = $rs->fetch())
			{
				$transactions[] = [
					'amount'	=> $row['amount'],
					'description'	=> $row['description'],
					'to_user'		=> $row['to_letscode'] . ' ' . $row['to_name'],
					'to_user_url'	=> $base_url . '/users.php?id=' . $row['id_to'],
					'from_user'		=> $row['from_letscode'] . ' ' . $row['from_name'],
					'from_user_url'	=> $base_url . '/users.php?id=' . $row['id_from'],
					'real_to'		=> $row['real_to'],
					'real_from'		=> $row['real_from'],
					'to_name'		=> $row['to_name'],
					'from_name'		=> $row['from_name'],
				];
			}
		}

	// forum

		$forum_topics = $forum_topics_replied = [];

		$forum_en = readconfigfromdb('forum_en', $this->schema);

		$show_forum = $forum_en ? readconfigfromdb('weekly_mail_show_forum', $this->schema) : 'none';

		if ($show_forum != 'none')
		{

			// new topics

			$rows = $this->xdb->get_many(['agg_schema' => $this->schema,
				'agg_type' => 'forum',
				'data->>\'subject\'' => ['is not null'],
				'ts' => ['>' => $treshold_time],
				'access' => ['users', 'interlets']], 'order by event_time desc');

			if (count($rows))
			{
				foreach ($rows as $row)
				{
					$data = $row['data'];

					$forum[] = [
						'subject'	=> $data['subject'],
						'content'	=> $data['content'],
						'url'		=> $base_url . '/forum.php?t=' . $row['eland_id'],
						'ts'		=> $row['ts'],
					];

					$forum_topics[$row['eland_id']] = true;
				}
			}

			// new replies

			$rows = $this->xdb->get_many(['agg_schema' => $this->schema,
				'agg_type' => 'forum',
				'data->>\'parent_id\'' => ['is not null'],
				'ts' => ['>' => $treshold_time]], 'order by event_time desc');

			foreach ($rows as $row)
			{
				$data = $row['data'];

				if (!isset($forum_topics[$data['parent_id']]))
				{
					$forum_topics_replied[] = $this->schema . '_forum_' . $data['parent_id'];
				}
			}

			if (count($forum_topics_replied))
			{
				$rows = $this->xdb->get_many(['agg_id_ary' => $forum_topics_replied,
					'access' => ['users', 'interlets']]);

				if (count($rows))
				{
					foreach ($rows as $row)
					{
						$data = $row['data'];

						$forum[] = [
							'subject'	=> $data['subject'],
							'content'	=> $data['content'],
							'url'		=> $base_url . '/forum.php?t=' . $row['eland_id'],
							'ts'		=> $row['ts'],
						];
					}
				}
			}
		}

	// docs

		$show_docs = readconfigfromdb('weekly_mail_show_docs', $this->schema);

		if ($show_docs != 'none')
		{
			$rows = $this->xdb->get_many(['agg_schema' => $this->schema,
				'agg_type' => 'doc',
				'ts' => ['>' => $treshold_time],
				'access' => ['users', 'interlets']], 'order by event_time desc');

			if (count($rows))
			{
				foreach ($rows as $row)
				{
					$data = $row['data'];

					$docs[] = [
						'name'			=> $data['name'] ?? $data['org_filename'],
						'url'			=> $this->s3_doc_url . $data['filename'],
						'ts'			=> $row['ts'],
					];

					$forum_topics[$row['eland_id']] = true;
				}
			}
		}

	//

		$vars = [
			'group'		=> [
				'name'				=> readconfigfromdb('systemname', $this->schema),
				'tag'				=> readconfigfromdb('systemtag', $this->schema),
				'currency'			=> readconfigfromdb('currency', $this->schema),
				'support'			=> explode(',', readconfigfromdb('support', $this->schema)),
				'saldofreqdays'		=> readconfigfromdb('saldofreqdays', $this->schema),
			],
			's3_img'				=> $this->s3_img_url,
			'new_users'				=> $new_users,
			'show_new_users'		=> $show_new_users,
			'leaving_users'			=> $leaving_users,
			'show_leaving_users'	=> $show_leaving_users,
			'news'					=> $news,
			'news_url'				=> $base_url . '/news.php',
			'show_news'				=> $show_news,
			'transactions'			=> $transactions,
			'transactions_url'		=> $base_url . '/transactions.php',
			'show_transactions'		=> $show_transactions,
			'new_transaction_url'	=> $base_url . '/transactions.php?add=1',
			'forum'					=> $forum,
			'forum_url'				=> $base_url . '/forum.php',
			'show_forum'			=> $show_forum,
			'forum_en'				=> $forum_en,
			'docs'					=> $docs,
			'docs_url'				=> $base_url . '/docs.php',
			'show_docs'				=> $show_docs,
			'messages'				=> $messages,
			'messages_url'			=> $base_url . '/messages.php',
			'new_message_url'		=> $base_url . '/messages.php?add=1',
			'interlets'				=> $interlets,
		];

	// queue mail

		$log_to = [];

		foreach ($saldo_mail as $id => $b)
		{
			if (isset($users_geo[$id]))
			{
				$users[$id]['geo'] = $users_geo[$id];
			}

			$this->mail->queue([
				'schema'	=> $this->schema,
				'to'		=> $id,
				'template'	=> 'periodic_overview',
				'vars'		=> array_merge($vars, [
					'user'	=> $users[$id],
					'url_login'	=> $base_url . '/login.php?login=' . $users[$id]['letscode'],
					'account_edit_url'	=> $base_url . '/users.php?edit=' . $id,
				]),
			], random_int(50, 500));

			$log_to[] = $users[$id]['letscode'] . ' ' . $users[$id]['name'] . ' (' . $id . ')';
		}

		if (count($log_to))
		{
			$this->monolog->info('Saldomail queued, to: ' . implode(', ', $log_to), ['schema' => $this->schema]);
		}
		else
		{
			$this->monolog->info('mail: no saldomail queued', ['schema' => $this->schema]);
		}

		return true;
	}

	/**
	 *
	 */
	public function get_interval()
	{

		if (isset($this->schema))
		{
			$days = readconfigfromdb('saldofreqdays', $this->schema);
			$days = $days < 1 ? 7 : $days;

			return 86400 * $days;
		}

		return 86400;
	}
}
