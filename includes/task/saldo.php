<?php

namespace eland\task;

use Doctrine\DBAL\Connection as db;
use eland\xdb;
use Monolog\Logger;
use eland\groups;
use eland\task\mail;

class saldo
{
	protected $db;
	protected $xdb;
	protected $monolog;
	protected $mail;
	protected $groups;
	protected $s3_img_url;
	protected $protocol;

	public function __construct(db $db, xdb $xdb, Logger $monolog, mail $mail,
		groups $groups, string $s3_img_url, string $protocol)
	{
		$this->db = $db;
		$this->xdb = $xdb;
		$this->monolog = $monolog;
		$this->mail = $mail;
		$this->groups = $groups;
		$this->s3_img_url = $s3_img_url;
		$this->protocol = $protocol;
	}

	function run($schema)
	{
		// vars

		$host = $this->groups->get_host($schema);

		if (!$host)
		{
			return;
		}

		$base_url = $this->protocol . $host;

		$treshold_time = gmdate('Y-m-d H:i:s', time() - readconfigfromdb('saldofreqdays', $schema) * 86400);
 	
		$msg_url = $base_url . '/messages.php?id=';
		$msgs_url = $base_url . '/messages.php';
		$news_url = $base_url . '/news.php?id=';
		$user_url = $base_url . '/users.php?id=';
		$login_url = $base_url . '/login.php?login=';
		$new_message_url = $base_url . '/messages.php?add=1';
		$new_transaction_url = $base_url . '/transactions.php?add=1';
		$account_edit_url = $base_url . '/users.php?edit=';

		$users = $news = $new_users = $leaving_users = $transactions = $messages = [];

		$forum = $inter_messages = $docs = [];

		$mailaddr = $mailaddr_public = $saldo_mail = [];

	// fetch active users

		$rs = $this->db->prepare('SELECT u.id,
				u.name, u.saldo, u.status, u.minlimit, u.maxlimit,
				u.letscode, u.postcode, u.cron_saldo
			FROM ' . $schema . '.users u
			WHERE u.status in (1, 2)');

		$rs->execute();

		while ($row = $rs->fetch())
		{
			$users[$row['id']] = $row;
		}

	// fetch mail addresses & cron_saldo

		$st = $this->db->prepare('select u.id, c.value, c.flag_public
			from ' . $schema . '.users u, ' . $schema . '.contact c, ' . $schema . '.type_contact tc
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
			from ' . $schema . '.msgpictures p, ' . $schema . '.messages m
			where p.msgid = m.id
				and m.cdate >= ?', [$treshold_time]);

		$rs->bindValue(1, $treshold_time);
		$rs->execute();

		while ($row = $rs->fetch())
		{
			$image_ary[$row['id']][] = $row['PictureFile'];
		}

	// fetch addresses

		$addr = $addr_public = [];

		$rs = $this->db->prepare('select u.id, c.value, flag_public
			from ' . $schema . '.users u, ' . $schema . '.contact c, ' . $schema . '.type_contact tc
			where u.status in (1, 2)
				and u.id = c.id_user
				and c.id_type_contact = tc.id
				and tc.abbrev = \'adr\'');

		$rs->execute();

		while ($row = $rs->fetch())
		{
			$addr[$row['id']] = $row['value'];
			$addr_public[$row['id']] = $row['flag_public'];
		}

	// fetch messages

		$rs = $this->db->prepare('SELECT m.id, m.content,
			m."Description" as description, m.msg_type, m.id_user,
			u.name, u.letscode, u.postcode
			FROM ' . $schema . '.messages m, ' . $schema . '.users u
			WHERE m.id_user = u.id
				AND u.status IN (1, 2)
				AND m.cdate >= ?
			ORDER BY m.cdate DESC');

		$rs->bindValue(1, $treshold_time);
		$rs->execute();

		while ($row = $rs->fetch())
		{
			$row['type'] = $row['msg_type'] ? 'offer' : 'want';
			$row['offer'] = $row['type'] == 'offer' ? true : false;
			$row['want'] = $row['type'] == 'want' ? true : false;
			$row['images'] = $image_ary[$row['id']];
			$row['url'] = $base_url . '/messages.php?id=' . $row['id'];
			$row['mail'] = $mailaddr[$msg['id_user']];
			$row['user'] = $row['letscode'] . ' ' . $row['name'];
			$row['user_url'] = $base_url . '/users.php?id=' . $row['id_user'];
			$row['addr'] = str_replace(' ', '+', $addr[$msg['id_user']]);

			$messages[] = $row;
		}

	// news

		$show_news = readconfigfromdb('weekly_mail_show_news', $schema);

		if ($show_news != 'none')
		{
			$rows = $this->xdb->get_many(['agg_schema' => $schema, 'agg_type' => 'news_access']);

			foreach ($rows as $row)
			{
				$news_access_ary[$row['eland_id']] = $row['data']['access'];
			}

			$query = 'select n.*, u.name, u.letscode
				from ' . $schema . '.news n, ' . $schema . '.users u
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
					$this->xdb->set('news_access', $news_id, ['access' => 'interlets'], $schema);
					$row['access'] = 'interlets';
				}

				if (!in_array($row['access'], ['users', 'interlets']))
				{
					continue;
				}

				$row['url'] = $base_url . '/news.php?id=' . $row['id'];
				$row['user'] = $row['letscode'] . ' ' . $row['name'];
				$row['user_url'] = $base_url . '/users.php?id=' . $row['id_user'];

				$news[] = $row;
			}
		}

	// new users

		$show_new_users = readconfigfromdb('weekly_mail_show_new_users', $schema);

		if ($show_new_users != 'none')
		{

			$rs = $this->db->prepare('select u.id, u.name, u.letscode, u.postcode
				from ' . $schema . '.users u
				where u.status = 1
					and u.adate > ?');

			$time = gmdate('Y-m-d H:i:s', time() - readconfigfromdb('newuserdays', $schema) * 86400);
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

		$show_leaving_users = readconfigfromdb('weekly_mail_show_leaving_users', $schema);

		if ($show_leaving_users != 'none')
		{

			$query = 'select u.id, u.name, u.letscode, u.postcode
				from ' . $schema . '.users u
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

		$show_transactions = readconfigfromdb('weekly_mail_show_transactions', $schema);

		if ($show_transactions != 'none')
		{

			$rs = $this->db->prepare('select t.id_from, t.id_to, t.real_from, t.real_to,
					t.amount, t.cdate, t.description,
					uf.name as from_name, uf.letscode as from_letscode,
					ut.name as to_name, ut.letscode as to_letscode
				from ' . $schema . '.transactions t, ' . $schema . '.users uf, ' . $schema . '.users ut
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

		$forum_en = readconfigfromdb('forum_en', $schema);

		$show_forum = $forum_en ? readconfigfromdb('weekly_mail_show_forum', $schema) : 'none';

		if ($show_forum != 'none')
		{

			// new topics

			$rows = $this->xdb->get_many(['agg_schema' => $schema,
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

			$rows = $this->xdb->get_many(['agg_schema' => $schema,
				'agg_type' => 'forum',
				'data->>\'parent_id\'' => ['is not null'],
				'ts' => ['>' => $treshold_time]], 'order by event_time desc');

			foreach ($rows as $row)
			{
				$data = $row['data'];

				if (!isset($forum_topics[$data['parent_id']]))
				{
					$forum_topics_replied[] = $schema . '_forum_' . $data['parent_id'];
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

		$show_docs = readconfigfromdb('weekly_mail_show_docs', $schema);

	//

		$vars = [
			'group'		=> [
				'name'				=> readconfigfromdb('systemname', $schema),
				'tag'				=> readconfigfromdb('systemtag', $schema),
				'currency'			=> readconfigfromdb('currency', $schema),
				'support'			=> readconfigfromdb('support', $schema),
				'saldofreqdays'		=> readconfigfromdb('saldofreqdays', $schema),
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
			'inter_messages'		=> $inter_messages,
		];

	// queue mail

		$log_to = [];

		foreach ($saldo_mail as $id => $b)
		{
			$this->mail->queue([
				'schema'	=> $schema,
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
			$this->monolog->info('Saldomail queued, subject: ' . $subject . ', to: ' . implode(', ', $log_to), ['schema' => $schema]);
		}
		else
		{
			$this->monolog->info('mail: no saldomail queued', ['schema' => $schema]);
		}

		return true;
	}
}
