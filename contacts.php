<?php

$uid = $_GET['uid'] ?? false;
$abbrev = $_GET['abbrev'] ?? '';
$q = $_GET['q'] ?? '';
$letscode = $_GET['letscode'] ?? '';
$access = $_GET['access'] ?? 'all';
$ustatus = $_GET['ustatus'] ?? 'all';

$orderby = $_GET['orderby'] ?? 'c.id';
$asc = $_GET['asc'] ?? 0;
$limit = $_GET['limit'] ?? 25;
$start = $_GET['start'] ?? 0;

$del = $_GET['del'] ?? false;
$edit = $_GET['edit'] ?? false;
$add = $_GET['add'] ?? false;
$submit = isset($_POST['zend']) ? true : false;

$page_access = ($del || $add || $edit) ? 'user' : 'guest';
$page_access = ($abbrev || !$uid) ? 'admin' : $page_access;

require_once __DIR__ . '/include/web.php';

if ($del)
{
	if (!($user_id = $app['db']->fetchColumn('select c.id_user
		from ' . $app['tschema'] . '.contact c
		where c.id = ?', array($del))))
	{
		$app['alert']->error('Het contact bestaat niet.');
		cancel();
	}

	if ($uid && $uid != $user_id)
	{
		$app['alert']->error('uid in url is niet de eigenaar van contact.');
		cancel();
	}

	$user_id = ($uid) ? $uid : $user_id;

	$s_owner = !$app['s_guest']
		&& $app['s_group_self']
		&& $user_id == $app['s_id']
		&& $user_id;

	if (!($app['s_admin'] || $s_owner))
	{
		$app['alert']->error('Je hebt geen rechten om het contact te verwijderen.');
		cancel($uid);
	}

	$contact = $app['db']->fetchAssoc('select c.*, tc.abbrev
		from ' . $app['tschema'] . '.contact c, ' .
			$app['tschema'] . '.type_contact tc
		where c.id = ?
			and tc.id = c.id_type_contact', array($del));

	$owner = $app['user_cache']->get($contact['id_user'], $app['tschema']);

	if ($contact['abbrev'] == 'mail' && ($owner['status'] == 1 || $owner['status'] == 2))
	{
		if ($app['db']->fetchColumn('select count(c.*)
			from ' . $app['tschema'] . '.contact c, ' .
				$app['tschema'] . '.type_contact tc
			where c.id_type_contact = tc.id
				and c.id_user = ?
				and tc.abbrev = \'mail\'', array($user_id)) == 1)
		{
			$err = $s_owner ? 'je enige E-mail adres' : 'het enige E-mail adres van een actieve gebruiker';
			$app['alert']->warning('Waarschuwing: dit is ' . $err);
			//cancel($uid);
		}
	}

	if ($submit)
	{
		if ($error_token = $app['form_token']->get_error())
		{
			$app['alert']->error($error_token);
			cancel($uid);
		}

		if ($app['db']->delete($app['tschema'] . '.contact', array('id' => $del)))
		{
			$app['alert']->success('Contact verwijderd.');
		}
		else
		{
			$app['alert']->error('Fout bij verwijderen van het contact.');
		}
		cancel($uid);
	}

	$contact = $app['db']->fetchAssoc('select tc.abbrev, c.value, c.comments, c.flag_public
		from ' . $app['tschema'] . '.type_contact tc, ' .
			$app['tschema'] . '.contact c
		where c.id_type_contact = tc.id
			and c.id = ?', array($del));

	$h1 = 'Contact verwijderen?';

	include __DIR__ . '/include/header.php';

	echo '<br>';

	echo '<div class="panel panel-info">';
	echo '<div class="panel-heading">';

	echo '<dl>';
	if (!$s_owner)
	{
		echo '<dt>Gebruiker</dt>';
		echo '<dd>';
		echo link_user($user_id, $app['tschema']);
		echo '</dd>';
	}
	echo '<dt>Type</dt>';
	echo '<dd>';
	echo $contact['abbrev'];
	echo '</dd>';
	echo '<dt>Waarde</dt>';
	echo '<dd>';
	echo $contact['value'];
	echo '</dd>';
	echo '<dt>Commentaar</dt>';
	echo '<dd>';
	echo $contact['comments'] ?: '<i class="fa fa-times"></i>';
	echo '</dd>';
	echo '<dt>Zichtbaarheid</dt>';
	echo '<dd>';
	echo $app['access_control']->get_label($contact['flag_public']);
	echo '</dd>';
	echo '</dl>';

	echo '<form method="post" class="form-horizontal">';

	if ($uid)
	{
		echo '<input type="hidden" name="uid" value="' . $uid . '">';
		echo aphp('users', ['id' => $uid], 'Annuleren', 'btn btn-default');
	}
	else
	{
		echo aphp('contacts', [], 'Annuleren', 'btn btn-default');
	}

	echo '&nbsp;';
	echo '<input type="submit" value="Verwijderen" name="zend" class="btn btn-danger">';
	echo $app['form_token']->get_hidden_input();

	echo '</form>';

	echo '</div>';
	echo '</div>';

	include __DIR__ . '/include/footer.php';
	exit;
}

if ($edit || $add)
{
	if ($edit)
	{
		if (!($user_id = $app['db']->fetchColumn('select id_user
			from ' . $app['tschema'] . '.contact
			where id = ?', [$edit])))
		{
			$app['alert']->error('Dit contact heeft geen eigenaar
				of bestaat niet.');
			cancel();
		}

		if ($uid && $uid != $user_id)
		{
			$app['alert']->error('uid in url is niet
				de eigenaar van contact.');
			cancel();
		}
	}
	else
	{
		$user_id = false;
	}

	$user_id = $uid ? $uid : $user_id;

	$s_owner = !$app['s_guest']
		&& $app['s_group_self']
		&& $user_id == $app['s_id']
		&& $user_id;

	if (!($app['s_admin'] || $s_owner))
	{
		$err = $edit ? 'dit contact aan te passen.' : 'een contact toe te voegen voor deze gebruiker.';
		$app['alert']->error('Je hebt geen rechten om ' . $err);
		cancel($uid);
	}

	if($submit)
	{
		if ($error_token = $app['form_token']->get_error())
		{
			$errors[] = $error_token;
		}

		if ($app['s_admin'] && $add && !$uid)
		{
			$letscode = $_POST['letscode'];
			[$letscode] = explode(' ', trim($letscode));

			$user_id = $app['db']->fetchColumn('select id
				from ' . $app['tschema'] . '.users
				where letscode = ?', [$letscode]);

			if ($user_id)
			{
				$letscode = link_user($user_id, $app['tschema'], false);
			}
			else
			{
				$errors[] = 'Ongeldige letscode.';
			}
		}

		$contact = array(
			'id_type_contact'		=> $_POST['id_type_contact'],
			'value'					=> trim($_POST['value']),
			'comments' 				=> trim($_POST['comments']),
			'flag_public'			=> $app['access_control']->get_post_value(),
			'id_user'				=> $user_id,
		);

		$abbrev_type = $app['db']->fetchColumn('select abbrev
			from ' . $app['tschema'] . '.type_contact
			where id = ?', array($contact['id_type_contact']));

		if ($abbrev_type === 'mail' && !filter_var($contact['value'], FILTER_VALIDATE_EMAIL))
		{
			$errors[] = 'Geen geldig E-mail adres';
		}

		if (!$contact['value'])
		{
			$errors[] = 'Vul waarde in!';
		}

		if (strlen($contact['value']) > 130)
		{
			$errors[] = 'De waarde mag maximaal 130 tekens lang zijn.';
		}

		if (strlen($contact['comments']) > 50)
		{
			$errors[] = 'Commentaar mag maximaal 50 tekens lang zijn.';
		}

		if(!$abbrev_type)
		{
			$errors[] = 'Contacttype bestaat niet!';
		}

		$access_error = $app['access_control']->get_post_error();

		if ($access_error)
		{
			$errors[] = $access_error;
		}

		$mail_type_id = $app['db']->fetchColumn('select id
			from ' . $app['tschema'] . '.type_contact
			where abbrev = \'mail\'');

		if ($edit)
		{
			$count_mail = $app['db']->fetchColumn('select count(*)
				from ' . $app['tschema'] . '.contact
				where id_user = ?
					and id_type_contact = ?',
				array($user_id, $mail_type_id));

			$mail_id = $app['db']->fetchColumn('select id
				from ' . $app['tschema'] . '.contact
				where id_user = ?
					and id_type_contact = ?',
				array($user_id, $mail_type_id));

			if ($edit == $mail_id && $count_mail == 1 && $contact['id_type_contact'] != $mail_type_id)
			{
				$app['alert']->warning('Waarschuwing: de gebruiker heeft
					geen E-mail adres.');
			}
		}

		if ($contact['id_type_contact'] == $mail_type_id)
		{
			$mail_count = $app['db']->fetchColumn('select count(c.*)
				from ' . $app['tschema'] . '.contact c, ' .
					$app['tschema'] . '.type_contact tc, ' .
					$app['tschema'] . '.users u
				where c.id_type_contact = tc.id
					and tc.abbrev = \'mail\'
					and c.id_user = u.id
					and u.status in (1, 2)
					and u.id <> ?
					and c.value = ?', array($user_id, $contact['value']));

			if ($mail_count && $app['s_admin'])
			{
				$warning = 'Omdat deze gebruikers niet meer ';
				$warning .= 'een uniek E-mail adres hebben zullen zij ';
				$warning .= 'niet meer zelf hun paswoord kunnnen resetten ';
				$warning .= 'of kunnen inloggen met ';
				$warning .= 'E-mail adres. Zie ' . aphp('status', [], 'Status');

				if ($mail_count == 1)
				{
					$warning_2 = 'Waarschuwing: E-mail adres ' . $mailadr;
					$warning_2 .= ' bestaat al onder de actieve gebruikers.';
				}
				else if ($mail_count > 1)
				{
					$warning_2 = 'Waarschuwing: E-mail adres ' . $mailadr;
					$warning_2 .= ' bestaat al ' . $mail_count;
					$warning_2 .= ' maal onder de actieve gebruikers.';
				}

				$app['alert']->warning($warning_2 . ' ' . $warning);
			}
			else if ($mail_count)
			{
				$errors[] = 'Dit E-mail adres komt reeds voor onder
					de actieve gebruikers.';
			}
		}

		if(!count($errors))
		{
			if ($abbrev_type === 'adr')
			{
				$app['queue.geocode']->cond_queue([
					'adr'		=> $contact['value'],
					'uid'		=> $contact['id_user'],
					'schema'	=> $app['tschema'],
				]);
			}

			if ($edit)
			{
				if ($app['db']->update($app['tschema'] . '.contact',
					$contact, array('id' => $edit)))
				{
					$app['alert']->success('Contact aangepast.');
					cancel($uid);
				}
				else
				{
					$app['alert']->error('Fout bij het opslaan');
				}
			}
			else
			{
				if ($app['db']->insert($app['tschema'] . '.contact', $contact))
				{
					$app['alert']->success('Contact opgeslagen.');
					cancel($uid);
				}
				else
				{
					$app['alert']->error('Fout bij het opslaan');
				}
			}
		}
		else
		{
			$app['alert']->error($errors);
		}
	}
	else if ($edit)
	{
		$contact = $app['db']->fetchAssoc('select *
			from ' . $app['tschema'] . '.contact
			where id = ?', array($edit));
	}
	else if ($add)
	{
		$contact = array(
			'value'				=> '',
			'comments'			=> '',
			'flag_public'		=> false,
		);
	}

	$tc = [];

	$rs = $app['db']->prepare('select id, name, abbrev
		from ' . $app['tschema'] . '.type_contact');

	$rs->execute();

	while ($row = $rs->fetch())
	{
		$tc[$row['id']] = $row;

		if (isset($contact['id_type_contact']))
		{
			continue;
		}

		$contact['id_type_contact'] = $row['id'];
	}

	if ($app['s_admin'] && $add && !$uid)
	{
		$app['assets']->add(['typeahead', 'typeahead.js']);
	}

	$app['assets']->add(['contacts_edit.js']);

	$contacts_format = [
		'adr'	=> [
			'fa'		=> 'map-marker',
			'lbl'		=> 'Adres',
			'explain'	=> 'Voorbeeldstraat 23, 4520 Voorbeeldgemeente',
		],
		'gsm'	=> [
			'fa'		=> 'mobile',
			'lbl'		=> 'GSM',
		],
		'tel'	=> [
			'fa'		=> 'phone',
			'lbl'		=> 'Telefoon',
		],
		'mail'	=> [
			'fa'		=> 'envelope-o',
			'lbl'		=> 'E-mail',
			'type'		=> 'email',
		],
		'web'	=> [
			'fa'		=> 'link',
			'lbl'		=> 'Website',
			'type'		=> 'url',
		],
	];

	$abbrev = $tc[$contact['id_type_contact']]['abbrev'];

	$h1 = $edit ? 'Contact aanpassen' : 'Contact toevoegen';
	if (!(($s_owner && !$app['s_admin']) || ($app['s_admin'] && $add && !$uid)))
	{
		$h1 .=  ' voor ' . link_user($user_id, $app['tschema']);
	}

	include __DIR__ . '/include/header.php';

	echo '<div class="panel panel-info">';
	echo '<div class="panel-heading">';

	echo '<form method="post">';

	if ($app['s_admin'] && $add && !$uid)
	{
		$typeahead_ary = [];

		foreach (['active', 'inactive', 'ip', 'im', 'extern'] as $t_stat)
		{
			$typeahead_ary[] = [
				'accounts', [
					'status'	=> $t_stat,
					'schema'	=> $app['tschema'],
				],
			];
		}

		echo '<div class="form-group">';
		echo '<label for="letscode" class="control-label">Voor</label>';
		echo '<div class="input-group">';
		echo '<span class="input-group-addon" id="fcode_addon">';
		echo '<span class="fa fa-user"></span></span>';
		echo '<input type="text" class="form-control" id="letscode" name="letscode" ';
		echo 'data-typeahead="';
		echo $app['typeahead']->get($typeahead_ary);
		echo '" ';
		echo 'data-newuserdays="';
		echo $app['config']->get('newuserdays', $app['tschema']);
		echo '" ';
		echo 'placeholder="Account Code" ';
		echo 'value="';
		echo $letscode;
		echo '" required>';
		echo '</div>';
		echo '</div>';
	}

	echo '<div class="form-group">';
	echo '<label for="id_type_contact" class="control-label">Type</label>';
	echo '<select name="id_type_contact" id="id_type_contact" ';
	echo 'class="form-control" required>';

	foreach ($tc as $id => $type)
	{
		echo '<option value="';
		echo $id;
		echo '" ';
		echo 'data-abbrev="';
		echo $type['abbrev'];
		echo '" ';
		echo $id == $contact['id_type_contact'] ? ' selected="selected"' : '';
		echo '>';
		echo $type['name'];
		echo '</option>';
	}

	echo "</select>";
	echo '</div>';

	echo '<div class="form-group">';
	echo '<label for="value" class="control-label">';
	echo 'Waarde</label>';
	echo '<div class="input-group">';
	echo '<span class="input-group-addon" id="value_addon">';
	echo '<i class="fa fa-';
	echo $contacts_format[$abbrev]['fa'] ?? 'circle-o';
	echo '"></i>';
	echo '</span>';
	echo '<input type="text" class="form-control" id="value" name="value" ';
	echo 'value="';
	echo $contact['value'];
	echo '" required disabled maxlength="130" ';
	echo 'data-contacts-format="';
	echo htmlspecialchars(json_encode($contacts_format));
	echo '">';
	echo '</div>';
	echo '<p id="contact-explain">';

	echo '</p>';
	echo '</div>';

	echo '<div class="form-group">';
	echo '<label for="comments" class="control-label">';
	echo 'Commentaar</label>';
	echo '<div class="input-group">';
	echo '<span class="input-group-addon">';
	echo '<i class="fa fa-comment-o"></i>';
	echo '</span>';
	echo '<input type="text" class="form-control" id="comments" name="comments" ';
	echo 'value="';
	echo $contact['comments'];
	echo '" maxlength="50">';
	echo '</div>';
	echo '</div>';

	echo $app['access_control']->get_radio_buttons(false, $contact['flag_public']);

	if ($uid)
	{
		echo '<input type="hidden" name="uid" value="' . $uid . '">';
		echo aphp('users', ['id' => $uid], 'Annuleren', 'btn btn-default');
	}
	else
	{
		echo aphp('contacts', [], 'Annuleren', 'btn btn-default');
	}

	echo '&nbsp;';

	if ($add)
	{
		echo '<input type="submit" value="Opslaan" ';
		echo 'name="zend" class="btn btn-success">';
	}
	else
	{
		echo '<input type="submit" value="Aanpassen" ';
		echo 'name="zend" class="btn btn-primary">';
	}

	echo $app['form_token']->get_hidden_input();

	echo '</form>';

	echo '</div>';
	echo '</div>';

	include __DIR__ . '/include/footer.php';
	exit;
}

/**
 * show contacts of a user
 */

if ($uid)
{
	$s_owner = !$app['s_guest']
		&& $app['s_group_self']
		&& $uid == $app['s_id']
		&& $uid;

	$contacts = $app['db']->fetchAll('select c.*, tc.abbrev
		from ' . $app['tschema'] . '.contact c, ' .
			$app['tschema'] . '.type_contact tc
		where c.id_type_contact = tc.id
			and c.id_user = ?', array($uid));

	$user = $app['user_cache']->get($uid, $app['tschema']);

	if ($app['s_admin'] || $s_owner)
	{
		$top_buttons .= aphp('contacts', ['add' => 1, 'uid' => $uid], 'Toevoegen', 'btn btn-success', 'Contact toevoegen', 'plus', true);
	}

	if (!$app['p_inline'])
	{
		$h1 = $s_owner ? 'Mijn contacten' : 'Contacten Gebruiker ' . link_user($user, $app['tschema']);
		$fa = 'map-marker';

		include __DIR__ . '/include/header.php';
		echo '<br>';
	}
	else
	{
		echo '<div class="row">';
		echo '<div class="col-md-12">';

		echo '<h3>';
		echo '<i class="fa fa-map-marker"></i>';
		echo ' Contactinfo van ';
		echo link_user($user, $app['tschema']);
		echo ' ';
		echo $top_buttons;
		echo '</h3>';
	}

	if (!count($contacts))
	{
		echo '<br>';
		echo '<div class="panel panel-danger">';
		echo '<div class="panel-body">';
		echo '<p>Er is geen contactinfo voor deze gebruiker.</p>';
		echo '</div></div>';

		if (!$app['p_inline'])
		{
			include __DIR__ . '/include/footer.php';
		}
		exit;
	}

	echo '<div class="panel panel-danger">';
	echo '<div class="table-responsive">';
	echo '<table class="table table-hover ';
	echo 'table-striped table-bordered footable" ';
	echo 'data-sort="false">';

	echo '<thead>';
	echo '<tr>';

	echo '<th>Type</th>';
	echo '<th>Waarde</th>';
	echo '<th data-hide="phone, tablet">Commentaar</th>';

	if ($app['s_admin'] || $s_owner)
	{
		echo '<th data-hide="phone, tablet">Zichtbaarheid</th>';
		echo '<th data-sort-ignore="true" ';
		echo 'data-hide="phone, tablet">Verwijderen</th>';
	}

	echo '</tr>';
	echo '</thead>';

	echo '<tbody>';

	foreach ($contacts as $c)
	{
		echo '<tr>';
		echo '<td>';
		echo $c['abbrev'];
		echo '</td>';

		if (($c['flag_public'] < $app['s_access_level']) && !$s_owner)
		{
			echo '<td><span class="btn btn-default btn-xs">';
			echo 'verborgen</span></td>';
			echo '<td><span class="btn btn-default btn-xs">';
			echo 'verborgen</span></td>';
		}
		else if ($s_owner || $app['s_admin'])
		{
			echo '<td>';
			echo  aphp('contacts', ['edit' => $c['id'], 'uid' => $uid], $c['value']);
			if ($c['abbrev'] == 'adr'
				&& !$app['s_elas_guest']
				&& !$app['s_master'])
			{
				echo $app['distance']->set_from_geo('', $app['s_id'], $app['s_schema'])
					->set_to_geo(trim($c['value']))
					->calc()
					->format_parenthesis();
			}
			echo '</td>';
			echo '<td>';
			echo isset($c['comments']) ? aphp('contacts', ['edit' => $c['id'], 'uid' => $uid], $c['comments']) : '';
			echo '</td>';
		}
		else if ($c['abbrev'] === 'mail')
		{
			echo '<td>';
			echo '<a href="mailto:';
			echo $c['value'] . '">';
			echo $c['value'];
			echo '</a>';
			echo '</td>';
			echo '<td>';
			echo htmlspecialchars($c['comments'], ENT_QUOTES);
			echo '</td>';
		}
		else if ($c['abbrev'] === 'web')
		{
			echo '<td>';
			echo '<a href="';
			echo $c['value'] . '">';
			echo $c['value'];
			echo '</a>';
			echo '</td>';
			echo '<td>';
			echo htmlspecialchars($c['comments'], ENT_QUOTES);
			echo '</td>';
		}
		else
		{
			echo '<td>';
			echo htmlspecialchars($c['value'], ENT_QUOTES);
			if ($c['abbrev'] == 'adr'
				&& !$app['s_elas_guest']
				&& !$app['s_master'])
			{
				echo $app['distance']->set_from_geo('', $app['s_id'], $app['s_schema'])
					->set_to_geo(trim($c['value']))
					->calc()
					->format_parenthesis();
			}
			echo '</td>';
			echo '<td>' . htmlspecialchars($c['comments'], ENT_QUOTES) . '</td>';
		}

		if ($app['s_admin'] || $s_owner)
		{
			echo '<td>' . $app['access_control']->get_label($c['flag_public']) . '</td>';

			echo '<td>';
			echo aphp('contacts', ['del' => $c['id'], 'uid' => $uid], 'Verwijderen', 'btn btn-danger btn-xs', false, 'times');
			echo '</td>';
		}
		echo '</tr>';
	}

	echo '</tbody>';

	echo '</table>';

	if ($app['distance']->get_to_geo() && $app['p_inline'])
	{
		echo '<div class="panel-footer">';
		echo '<div class="user_map" id="map" data-markers="';
		echo $app['distance']->get_to_data();
		echo '" ';
		echo 'data-token="';
		echo $app['mapbox_token'];
		echo '"></div>';
		echo '</div>';
	}

	echo '</div></div>';

	echo '</div>';

	if ($app['p_inline'])
	{
		exit;
	}

	include __DIR__ . '/include/footer.php';
	exit;
}

/**
 *
 */

if (!$app['s_admin'])
{
	$app['alert']->error('Je hebt geen toegang tot deze pagina.');
	redirect_default_page();
}

$s_owner = !$app['s_guest']
	&& $app['s_group_self']
	&& $app['s_id'] == $uid
	&& $app['s_id'] && $uid;

$params = array(
	'orderby'	=> $orderby,
	'asc'		=> $asc,
	'limit'		=> $limit,
	'start'		=> $start,
);

$params_sql = $where_sql = array();

if ($uid)
{
	$user = $app['user_cache']->get($uid, $app['tschema']);

	$where_sql[] = 'c.id_user = ?';
	$params_sql[] = $uid;
	$params['uid'] = $uid;

	$letscode = link_user($user, $app['tschema'], false);
}

if (!$uid)
{
	if ($letscode)
	{
		[$letscode] = explode(' ', trim($letscode));

		$fuid = $app['db']->fetchColumn('select id
			from ' . $app['tschema'] . '.users
			where letscode = ?', [$letscode]);

		if ($fuid)
		{
			$where_sql[] = 'c.id_user = ?';
			$params_sql[] = $fuid;

			$letscode = link_user($fuid, $app['tschema'], false);
		}
		else
		{
			$where_sql[] = '1 = 2';
		}

		$params['letscode'] = $letscode;
	}
}

if ($q)
{
	$where_sql[] = '(c.value ilike ? or c.comments ilike ?)';
	$params_sql[] = '%' . $q . '%';
	$params_sql[] = '%' . $q . '%';
	$params['q'] = $q;
}

if ($abbrev)
{
	$where_sql[] = 'tc.abbrev = ?';
	$params_sql[] = $abbrev;
	$params['abbrev'] = $abbrev;
}

if ($access != 'all')
{
	switch ($access)
	{
		case 'admin':
			$acc = 0;
			break;
		case 'users':
			$acc = 1;
			break;
		case 'interlets':
			$acc = 2;
			break;
		default:
			$access = 'all';
			break;
	}

	if ($access != 'all')
	{
		$where_sql[] = 'c.flag_public = ?';
		$params_sql[] = $acc;
		$params['access'] = $acc;
	}
}

switch ($ustatus)
{
	case 'new':
		$where_sql[] = 'u.adate > ? and u.status = 1';
		$params_sql[] = gmdate('Y-m-d H:i:s', $app['new_user_treshold']);
		$params['ustatus'] = 'new';
		break;
	case 'leaving':
		$where_sql[] = 'u.status = 2';
		$params['ustatus'] = 'leaving';
		break;
	case 'active':
		$where_sql[] = 'u.status in (1, 2)';
		$params['ustatus'] = 'active';
		break;
	case 'inactive':
		$where_sql[] = 'u.status = 0';
		$params['ustatus'] = 'inactive';
		break;
	case 'ip':
		$where_sql[] = 'u.status = 5';
		$params['ustatus'] = 'ip';
		break;
	case 'im':
		$where_sql[] = 'u.status = 6';
		$params['ustatus'] = 'im';
		break;
	case 'extern':
		$where_sql[] = 'u.status = 7';
		$params['ustatus'] = 'extern';
		break;
	default:
		break;
}

$user_table_sql = '';

if ($ustatus != 'all' || $orderby == 'u.letscode')
{
	$user_table_sql = ', ' . $app['tschema'] . '.users u ';
	$where_sql[] = 'u.id = c.id_user';
}

if (count($where_sql))
{
	$where_sql = ' and ' . implode(' and ', $where_sql) . ' ';
}
else
{
	$where_sql = '';
}

$query = 'select c.*, tc.abbrev
	from ' . $app['tschema'] . '.contact c, ' .
		$app['tschema'] . '.type_contact tc' . $user_table_sql . '
	where c.id_type_contact = tc.id' . $where_sql;

$row_count = $app['db']->fetchColumn('select count(c.*)
	from ' . $app['tschema'] . '.contact c, ' .
		$app['tschema'] . '.type_contact tc' . $user_table_sql . '
	where c.id_type_contact = tc.id' . $where_sql, $params_sql);

$query .= ' order by ' . $orderby . ' ';
$query .= $asc ? 'asc ' : 'desc ';
$query .= ' limit ' . $limit . ' offset ' . $start;

$contacts = $app['db']->fetchAll($query, $params_sql);

$app['pagination']->init('contacts', $row_count, $params, $app['p_inline']);

$asc_preset_ary = array(
	'asc'	=> 0,
	'indicator' => '',
);

$tableheader_ary = array(
	'tc.abbrev' => array_merge($asc_preset_ary, array(
		'lbl' => 'Type')),
	'c.value' => array_merge($asc_preset_ary, array(
		'lbl' => 'Waarde')),
	'u.letscode'	=> array_merge($asc_preset_ary, array(
		'lbl' 		=> 'Gebruiker')),
	'c.comments'	=> array_merge($asc_preset_ary, array(
		'lbl' 		=> 'Commentaar',
		'data_hide'	=> 'phone,tablet')),
	'c.flag_public' => array_merge($asc_preset_ary, array(
		'lbl' 		=> 'Zichtbaar',
		'data_hide'	=> 'phone, tablet')),
	'del' => array_merge($asc_preset_ary, array(
		'lbl' 		=> 'Verwijderen',
		'data_hide'	=> 'phone, tablet',
		'no_sort'	=> true)),
);

$tableheader_ary[$orderby]['asc'] = ($asc) ? 0 : 1;
$tableheader_ary[$orderby]['indicator'] = ($asc) ? '-asc' : '-desc';

unset($tableheader_ary['c.id']);

$abbrev_ary = array();

$rs = $app['db']->prepare('select abbrev
	from ' . $app['tschema'] . '.type_contact');

$rs->execute();

while($row = $rs->fetch())
{
	$abbrev_ary[$row['abbrev']] = $row['abbrev'];
}

$csv_en = true;

$top_buttons .= aphp('contacts', ['add' => 1], 'Toevoegen', 'btn btn-success', 'Contact toevoegen', 'plus', true);

$panel_collapse = ($q || $abbrev || $access != 'all' || $letscode || $ustatus != 'all') ? false : true;
$filtered = $panel_collapse ? false : true;

$app['assets']->add(['typeahead', 'typeahead.js']);

$h1 = 'Contacten';
$h1 .= $filtered ? ' <small>Gefilterd</small>' : '';
$h1 .= btn_filter();

$fa = 'map-marker';

include __DIR__ . '/include/header.php';

echo '<div id="filter" class="panel panel-info';
echo $panel_collapse ? ' collapse' : '';
echo '">';

echo '<div class="panel-heading">';

echo '<form method="get" class="form-horizontal">';

echo '<div class="row">';

echo '<div class="col-sm-4">';
echo '<div class="input-group margin-bottom">';
echo '<span class="input-group-addon">';
echo '<i class="fa fa-search"></i>';
echo '</span>';
echo '<input type="text" class="form-control" id="q" value="' . $q . '" name="q" placeholder="Zoeken">';
echo '</div>';
echo '</div>';

echo '<div class="col-sm-4">';
echo '<div class="input-group margin-bottom">';
echo '<span class="input-group-addon">';
echo 'Type';
echo '</span>';
echo '<select class="form-control" id="abbrev" name="abbrev">';
echo get_select_options(array_merge(['' => ''], $abbrev_ary), $abbrev);
echo '</select>';
echo '</div>';
echo '</div>';

$access_options = [
	'all'		=> '',
	'admin'		=> 'admin',
	'users'		=> 'leden',
	'interlets'	=> 'interSysteem',
];

if (!$app['config']->get('template_lets', $app['tschema'])
	|| !$app['config']->get('interlets_en', $app['tschema']))
{
	unset($access_options['interlets']);
}

echo '<div class="col-sm-4">';
echo '<div class="input-group margin-bottom">';
echo '<span class="input-group-addon">';
echo 'Zichtbaar';
echo '</span>';
echo '<select class="form-control" id="access" name="access">';
echo get_select_options($access_options, $access);
echo '</select>';
echo '</div>';
echo '</div>';

echo '</div>';

echo '<div class="row">';

$user_status_options = array(
	'all'		=> 'Alle',
	'active'	=> 'Actief',
	'new'		=> 'Enkel instappers',
	'leaving'	=> 'Enkel uitstappers',
	'inactive'	=> 'Inactief',
	'ip'		=> 'Info-pakket',
	'im'		=> 'Info-moment',
	'extern'	=> 'Extern',
);

echo '<div class="col-sm-5">';
echo '<div class="input-group margin-bottom">';
echo '<span class="input-group-addon">';
echo 'Status ';
echo '<i class="fa fa-user"></i>';
echo '</span>';
echo '<select class="form-control" id="ustatus" name="ustatus">';
echo get_select_options($user_status_options, $ustatus);
echo '</select>';
echo '</div>';
echo '</div>';

echo '<div class="col-sm-5">';
echo '<div class="input-group margin-bottom">';
echo '<span class="input-group-addon" id="fcode_addon">Van ';
echo '<span class="fa fa-user"></span></span>';

$typeahead_ary = [];

foreach (['active', 'inactive', 'ip', 'im', 'extern'] as $t_stat)
{
	$typeahead_ary[] = [
		'accounts', [
			'status'	=> $t_stat,
			'schema'	=> $app['tschema'],
		],
	];
}

echo '<input type="text" class="form-control" ';
echo 'aria-describedby="letscode_addon" ';
echo 'data-typeahead="';
echo $app['typeahead']->get($typeahead_ary);
echo '" ';
echo 'data-newuserdays="';
echo $app['config']->get('newuserdays', $app['tschema']);
echo '" ';
echo 'name="letscode" id="letscode" placeholder="Account Code" ';
echo 'value="';
echo $letscode;
echo '">';
echo '</div>';
echo '</div>';

echo '<div class="col-sm-2">';
echo '<input type="submit" value="Toon" ';
echo 'class="btn btn-default btn-block">';
echo '</div>';

echo '</div>';

$params_form = $params;
unset($params_form['access'], $params_form['q'], $params_form['abbrev']);
unset($params_form['letscode'], $params_form['ustatus']);
unset($params_form['start']);

$params_form['r'] = 'admin';
$params_form['u'] = $app['s_id'];

foreach ($params_form as $name => $value)
{
	if (isset($value))
	{
		echo '<input name="' . $name;
		echo '" value="' . $value;
		echo '" type="hidden">';
	}
}

echo '</form>';

echo '</div>';
echo '</div>';

echo $app['pagination']->get();

if (!count($contacts))
{
	echo '<br>';
	echo '<div class="panel panel-danger">';
	echo '<div class="panel-body">';
	echo '<p>Er zijn geen resultaten.</p>';
	echo '</div></div>';

	echo $app['pagination']->get();

	include __DIR__ . '/include/footer.php';
	exit;
}

echo '<div class="panel panel-danger">';
echo '<div class="table-responsive">';
echo '<table class="table table-hover table-striped table-bordered footable csv" ';
echo 'data-sort="false">';

echo '<thead>';
echo '<tr>';

$th_params = $params;

foreach ($tableheader_ary as $key_orderby => $data)
{
	echo '<th';
	echo (isset($data['data_hide'])) ? ' data-hide="' . $data['data_hide'] . '"' : '';
	echo '>';
	if (isset($data['no_sort']))
	{
		echo $data['lbl'];
	}
	else
	{
		$th_params['orderby'] = $key_orderby;
		$th_params['asc'] = $data['asc'];

		echo '<a href="';
		echo generate_url('contacts', $th_params);
		echo '">';
		echo $data['lbl'];
		echo '&nbsp;<i class="fa fa-sort';
		echo $data['indicator'];
		echo '"></i>';
		echo '</a>';
	}
	echo '</th>';
}

echo '</tr>';
echo '</thead>';

echo '<tbody>';

foreach ($contacts as $c)
{
	echo '<tr>';
	echo '<td>';
	echo $c['abbrev'];
	echo '</td>';

	echo '<td>';
	echo isset($c['value']) ? aphp('contacts', ['edit' => $c['id']], $c['value']) : '';
	echo '</td>';
	echo '<td>';
	echo link_user($c['id_user'], $app['tschema']);
	echo '</td>';
	echo '<td>';

	if (isset($c['comments']))
	{
		echo aphp('contacts',
			['edit' => $c['id']],
			$c['comments']);
	}

	echo '</td>';
	echo '<td>';
	echo $app['access_control']->get_label($c['flag_public']);
	echo '</td>';

	echo '<td>';
	echo aphp('contacts',
		['del' => $c['id']],
		'Verwijderen',
		'btn btn-danger btn-xs',
		false,
		'times'
	);
	echo '</td>';

	echo '</tr>';
}

echo '</tbody>';

echo '</table>';

echo '</div></div>';

echo $app['pagination']->get();

include __DIR__ . '/include/footer.php';

function cancel(int $uid = 0):void
{
	if ($uid)
	{
		header('Location: ' . generate_url('users', ['id' => $uid]));
	}
	else
	{
		header('Location: ' . generate_url('contacts'));
	}
	exit;
}
