<?php

$rootpath = './';
$role = 'guest';
require_once $rootpath . 'includes/inc_default.php';
require_once $rootpath . 'includes/inc_pagination.php';
require_once $rootpath . 'includes/inc_transactions.php';

$orderby = (isset($_GET['orderby'])) ? $_GET['orderby'] : 'cdate';
$asc = (isset($_GET['asc'])) ? $_GET['asc'] : 0;
$limit = (isset($_GET['limit'])) ? $_GET['limit'] : 25;
$start = (isset($_GET['start'])) ? $_GET['start'] : 0;
$id = (isset($_GET['id'])) ? $_GET['id'] : false;
$add = (isset($_GET['add'])) ? true : false;
$submit = (isset($_POST['zend'])) ? true : false;

$mid = ($_GET['mid']) ?: false;
$tuid = ($_GET['tuid']) ?: false;
$fuid = ($_GET['fuid']) ?: false;
$uid = ($_GET['uid']) ?: false;
$inline = ($_GET['inline']) ? true : false;

$q = (isset($_GET['q'])) ? $_GET['q'] : '';
$fcode = (isset($_GET['fcode'])) ? $_GET['fcode'] : '';
$tcode = (isset($_GET['tcode'])) ? $_GET['tcode'] : '';
$andor = (isset($_GET['andor'])) ? $_GET['andor'] : 'and';
$fdate = (isset($_GET['fdate'])) ? $_GET['fdate'] : '';
$tdate = (isset($_GET['tdate'])) ? $_GET['tdate'] : '';

/**
 * add
 */
if ($add)
{
	if ($s_guest)
	{
		$alert->error('Je hebt geen rechten om een transactie toe te voegen.');
		cancel();
	}

	$transaction = array();

	$redis_transid_key = $schema . '_transid_u_' . $s_id;

	if ($submit)
	{
		$errors = array();

		$stored_transid = $redis->get($redis_transid_key);

		if (!$stored_transid)
		{
			$errors[] = 'Formulier verlopen.';
		}

		$transaction['transid'] = $_POST['transid'];
		$transaction['description'] = $_POST['description'];
		list($letscode_from) = explode(' ', $_POST['letscode_from']);
		list($letscode_to) = explode(' ', $_POST['letscode_to']);
		$transaction['amount'] = $amount = ltrim($_POST['amount'], '0 ');;
		$transaction['date'] = date('Y-m-d H:i:s');
		$letsgroup_id = $_POST['letsgroup_id'];

		if ($stored_transid != $transaction['transid'])
		{
			$errors[] = 'Fout transactie id.';
		}

		if ($db->fetchColumn('select transid from transactions where transid = ?', array($stored_transid)))
		{
			$errors[] = 'Een herinvoer van de transactie werd voorkomen.';
		}

		if (strlen($transaction['description']) > 60)
		{
			$errors[] = 'De omschrijving mag maximaal 60 tekens lang zijn.';
		}

		if ($letsgroup_id != 'self')
		{
			$letsgroup = $db->fetchAssoc('SELECT * FROM letsgroups WHERE id = ?', array($letsgroup_id));

			if (!isset($letsgroup))
			{
				$errors[] = 'Letsgroep niet gevonden.';
			}
		}

		if ($s_user)
		{
			$fromuser = $db->fetchAssoc('SELECT * FROM users WHERE id = ?', array($s_id));
		}
		else
		{
			$fromuser = $db->fetchAssoc('SELECT * FROM users WHERE letscode = ?', array($letscode_from));
		}

		$letscode_touser = ($letsgroup_id == 'self') ? $letscode_to : $letsgroup['localletscode'];

		$touser = $db->fetchAssoc('select * from users where letscode = ?', array($letscode_touser));

		if ($letsgroup_id == 'self' && $touser['status'] == 7)
		{
			$errors[] = 'Je kan niet rechtstreeks naar een interletsrekening overschrijven.';
		}

		if ($fromuser['status'] == 7)
		{
			$errors[] = 'Je kan niet rechtstreeks van een interletsrekening overschrijven.';
		}

		$transaction['id_from'] = $fromuser['id'];
		$transaction['id_to'] = $touser['id'];

		list($schemas, $domains) = get_schemas_domains(true);

		if (!$transaction['description'])
		{
			$errors[]= 'De omschrijving is niet ingevuld';
		}

		if (!$transaction['amount'])
		{
			$errors[] = 'Bedrag is niet ingevuld';
		}

		else if (!(ctype_digit($transaction['amount'])))
		{
			$errors[] = 'Het bedrag is geen geldig getal';
		}

		if(($fromuser['saldo'] - $amount) < $fromuser['minlimit'] && !$s_admin)
		{
			$errors[] = 'Je beschikbaar saldo laat deze transactie niet toe';
		}

		if(empty($fromuser))
		{
			$errors[] = 'Gebruiker bestaat niet';
		}

		if(empty($touser))
		{
			if ($letsgroup_id == 'self')
			{
				$errors[] = 'Bestemmeling bestaat niet';
			}
			else
			{
				$errors[] = 'De interletsrekening bestaat niet';
			}
		}

		if($fromuser['letscode'] == $touser['letscode'])
		{
			$errors[] = 'Van en Aan letscode zijn hetzelfde';
		}

		if(($touser['saldo'] + $transaction['amount']) > $touser['maxlimit'] && !$s_admin)
		{
			$t_account = ($letsgroup_id == 'self') ? 'bestemmeling' : 'interletsrekening';
			$errors[] = 'De ' . $t_account . ' heeft zijn maximum limiet bereikt.';
		}

		if($letsgroup_id == 'self'
			&& !$s_admin
			&& !($touser['status'] == '1' || $touser['status'] == '2'))
		{
			$errors[] = 'De bestemmeling is niet actief';
		}

		if ($s_user)
		{
			$balance_eq = readconfigfromdb('balance_equilibrium');

			if (($fromuser['status'] == 2) && (($fromuser['saldo'] - $amount) < $balance_eq))
			{
				$errors[] = 'Als uitstapper kan je geen ' . $amount . ' ' . $currency . ' uitgeven.';
			}

			if (($touser['status'] == 2) && (($touser['saldo'] + $amount) > $balance_eq))
			{
				$dest = ($letsgroup_id == 'self') ? 'De bestemmeling' : 'De letsgroep';
				$errors[] = $dest . ' is uitstapper en kan geen ' . $amount . ' ' . $currency . ' ontvangen.';
			}
		}

		if (!$transaction['date'])
		{
			$errors[] = 'Datum is niet ingevuld';
		}
		else if (strtotime($transaction['date']) == -1)
		{
			$errors[] = 'Fout in datumformaat (jjjj-mm-dd)';
		}

		$contact_admin = ($s_admin) ? '' : ' Contacteer een admin.';

		if(!empty($errors))
		{
			log_event($s_id, 'transaction', 'form error(s): ' . implode(' | ', $errors));
			$alert->error(implode('<br>', $errors));
		}
		else if ($letsgroup_id == 'self')
		{
			if ($id = insert_transaction($transaction))
			{
				$transaction['id'] = $id;
				mail_transaction($transaction);
				$alert->success('Transactie opgeslagen');
			}
			else
			{
				$alert->error('Gefaalde transactie');
			}
			cancel();
		}
		else if ($letsgroup['apimethod'] == 'mail')
		{
			if ($id = insert_transaction($transaction))
			{
				$transaction['id'] = $id;
				$transaction['letscode_to'] = $letscode_to;

				mail_mail_interlets_transaction($transaction);

				$alert->success('Interlets transactie opgeslagen (verwerking per mail).');
			}
			else
			{
				$alert->error('Gefaalde interlets transactie');
			}
			cancel();
		}
		else if ($letsgroup['apimethod'] != 'elassoap')
		{
			$alert->error('Deze interlets groep heeft geen geldige api methode.' . $contact_admin);
			cancel();
		}
		else if (!$letsgroup['url'])
		{
			$alert->error('Geen url voor deze interlets groep.' . $contact_admin);
			cancel();
		}
		else if (!($remote_schema = $schemas[$letsgroup['url']]))
		{
			// The interlets letsgroup is on another server, use elassoap; queue the transaction.

			if (!$letsgroup['remoteapikey'])
			{
				$alert->error('Geen apikey voor deze interlets groep ingesteld.' . $contact_admin);
				cancel();
			}

			if (!$letsgroup['presharedkey'])
			{
				$alert->error('Geen preshared key voor deze interlets groep ingesteld.' . $contact_admin);
				cancel();
			}

			if (!$letsgroup['myremoteletscode'])
			{
				$alert->error('Geen remote letscode ingesteld voor deze interlets groep.' . $contact_admin);
				cancel();
			}

			$transaction['letscode_to'] = $letscode_to;
			$transaction['letsgroup_id'] = $letsgroup_id;
			$currencyratio = readconfigfromdb('currencyratio');
			$transaction['amount'] = $transaction['amount'] / $currencyratio;
			$transaction['amount'] = (float) $transaction['amount'];
			$transaction['amount'] = round($transaction['amount'], 5);
			$transaction['signature'] = sign_transaction($transaction, $letsgroup['presharedkey']);
			$transaction['retry_until'] = gmdate('Y-m-d H:i:s', time() + 86400);

			unset($transaction['date'], $transaction['id_to']);

			$transaction['retry_count'] = 0;
			$transaction['last_status'] = 'NEW';

			if ($db->insert('interletsq', $transaction))
			{
				if (!$redis->get($schema . '_interletsq'))
				{
					$redis->set($schema . '_interletsq', time());
				}
				$alert->success('Interlets transactie in verwerking');
				cancel();
			}

			$alert->error('Gefaalde queue interlets transactie');
			cancel();
		}
		else
		{
			// the interlets letsgroup is on the same server

			$to_remote_user = $db->fetchAssoc('select *
				from ' . $remote_schema . '.users
				where letscode = ?', array($letscode_to));

			if (!$to_remote_user)
			{
				$alert->error('De interlets gebruiker bestaat niet.');
				cancel();
			}

			if (!in_array($to_remote_user['status'], array('1', '2')))
			{
				$alert->error('De interlets gebruiker is niet actief.');
				cancel();
			}

			$remote_letsgroup = $db->fetchAssoc('select *
				from ' . $remote_schema . '.letsgroups
				where url = ?', array($base_url));

			if (!$remote_letsgroup)
			{
				$alert->error('De remote interlets groep heeft deze letsgroep ('. $systemname . ') niet geconfigureerd.');
				cancel();
			}

			if (!$remote_letsgroup['localletscode'])
			{
				$alert->error('Er is geen interlets account gedefiniëerd in de remote interlets groep.');
				cancel();
			}

			$remote_interlets_account = $db->fetchAssoc('select *
				from ' . $remote_schema . '.users
				where letscode = ?', array($remote_letsgroup['localletscode']));

			if (!$remote_interlets_account)
			{
				$alert->error('Er is geen interlets account in de remote interlets group.');
				cancel();
			}

			if ($remote_interlets_account['accountrole'] != 'interlets')
			{
				$alert->error('Het interlets account in de remote interlets groep heeft geen juiste rol. Deze moet van het type interlets zijn.');
				cancel();
			}

			if (!in_array($remote_interlets_account['status'], array(1, 2, 7)))
			{
				$alert->error('Het interlets account in de remote interlets groep heeft geen juiste status. Deze moet van het type extern, actief of uitstapper zijn.');
				cancel();
			}

			$remote_currency = readconfigfromdb('currency', $remote_schema);
			$remote_currencyratio = readconfigfromdb('currencyratio', $remote_schema);
			$remote_balance_eq = readconfigfromdb('balance_equilibrium', $remote_schema);
			$currencyratio = readconfigfromdb('currencyratio');

			$remote_amount = round(($transaction['amount'] * $remote_currencyratio) / $currencyratio);

			if(($remote_interlets_account['saldo'] - $remote_amount) < $remote_interlets_account['minlimit'])
			{
				$alert->error('De interlets account van de remote interlets groep heeft onvoldoende saldo beschikbaar.');
				cancel();
			}

			if(($to_remote_user['saldo'] + $remote_amount) > $to_remote_user['maxlimit'])
			{
				$alert->error('De interlets gebruiker heeft zijn maximum limiet bereikt.');
				cancel();
			}

			if (($remote_interlets_account['status'] == 2) && (($remote_interlets_account['saldo'] - $remote_amount) < $remote_balance_eq))
			{
				$alert->error('Het remote interlets account heeft de status uitstapper en kan geen ' . $remote_amount . ' ' . $remote_currency . ' uitgeven (' . $amount . ' ' . $currency . ').');
				cancel();
			}

			if (($to_remote_user['status'] == 2) && (($to_remote_user['saldo'] + $remote_amount) > $remote_balance_eq))
			{
				$alert->error('De remote bestemmeling is uitstapper en kan geen ' . $remote_amount . ' ' . $remote_currency . ' ontvangen (' . $amount . ' ' . $currency . ').');
				cancel();
			}

			//
			$transaction['creator'] = (empty($s_id)) ? 0 : $s_id;
			$transaction['cdate'] = date('Y-m-d H:i:s');
			$transaction['real_to'] = $to_remote_user['letscode'] . ' ' . $to_remote_user['name'];

			$db->beginTransaction();
			try
			{
				$db->insert('transactions', $transaction);
				$id = $db->lastInsertId('transactions_id_seq');
				$db->executeUpdate('update users
					set saldo = saldo + ? where id = ?',
					array($transaction['amount'], $transaction['id_to']));
				$db->executeUpdate('update users
					set saldo = saldo - ? where id = ?',
					array($transaction['amount'], $transaction['id_from']));

				$trans_org = $transaction;
				$trans_org['id'] = $id;

				$transaction['creator'] = 0;
				$transaction['amount'] = $remote_amount;
				$transaction['id_from'] = $remote_interlets_account['id'];
				$transaction['id_to'] = $to_remote_user['id'];
				$transaction['real_from'] = link_user($fromuser['id'], null, false);
				unset($transaction['real_to']);

				$db->insert($remote_schema . '.transactions', $transaction);
				$id = $db->lastInsertId($remote_schema . '.transactions_id_seq');
				$db->executeUpdate('update ' . $remote_schema . '.users
					set saldo = saldo + ? where id = ?',
					array($remote_amount, $transaction['id_to']));
				$db->executeUpdate('update ' . $remote_schema . '.users
					set saldo = saldo - ? where id = ?',
					array($transaction['amount'], $transaction['id_from']));
				$transaction['id'] = $id;

				$db->commit();

			}
			catch(Exception $e)
			{
				$db->rollback();
				$alert->error('Transactie niet gelukt.');
				throw $e;
				exit;
			}

			readuser($fromuser['id'], true);
			readuser($touser['id'], true);

			readuser($remote_interlets_account['id'], true, $remote_schema);
			readuser($to_remote_user['id'], true, $remote_schema);

			mail_transaction($trans_org);
			mail_transaction($transaction, $remote_schema);

			log_event($s_id, 'trans', 'direct interlets transaction ' . $transaction['transid'] . ' amount: ' .
				$amount . ' from user: ' .  link_user($fromuser['id'], null, false) .
				' to user: ' . link_user($touser['id'], null, false));

			log_event('', 'trans', 'direct interlets transaction (receiving) ' . $transaction['transid'] .
				' amount: ' . $remote_amount . ' from user: ' . $remote_interlets_account['letscode'] . ' ' .
				$remote_interlets_account['name'] . ' to user: ' . $to_remote_user['letscode'] . ' ' .
				$to_remote_user['name'], $remote_schema);

			autominlimit_queue($transaction['id_from'], $transaction['id_to'], $remote_amount, $remote_schema);

			$alert->success('Interlets transactie uitgevoerd.');
			cancel();
		}

		$transaction['letscode_to'] = $_POST['letscode_to'];
		$transaction['letscode_from'] = ($s_admin) ? $_POST['letscode_from'] : $s_letscode . ' ' . $s_name;
	}
	else
	{
		//GET form

		$transid = generate_transid();

		$redis->set($redis_transid_key, $transid);
		$redis->expire($redis_transid_key, 3600);

		$transaction = array(
			'date'			=> date('Y-m-d'),
			'letscode_from'	=> $s_letscode . ' ' . $s_name,
			'letscode_to'	=> '',
			'amount'		=> '',
			'description'	=> '',
			'transid'		=> $transid,
		);

		if ($mid)
		{
			$row = $db->fetchAssoc('SELECT
					m.content, m.amount, m.id_user, u.letscode, u.name
				FROM messages m, users u
				WHERE u.id = m.id_user
					AND m.id = ?', array($mid));
			$transaction['letscode_to'] = $row['letscode'] . ' ' . $row['name'];
			$transaction['description'] =  '#m' . $mid . ' ' . $row['content'];
			$transaction['amount'] = $row['amount'];
			$tuid = $row['tuid'];
		}
		else if ($tuid)
		{
			$row = readuser($tuid);
			$transaction['letscode_to'] = $row['letscode'] . ' ' . $row['name'];
		}

		if ($fuid && $s_admin && ($fuid != $tuid))
		{
			$row = readuser($fuid);
			$transaction['letscode_from'] = $row['letscode'] . ' ' . $row['name'];
		}

		if ($tuid == $s_id && !$fuid)
		{
			$transaction['letscode_from'] = '';
		}
	}

	if (!isset($_POST['zend']))
	{
		$letsgroup['id'] = 'self';
	}

	$includejs = '<script src="' . $cdn_typeahead . '"></script>
		<script src="' . $rootpath . 'js/typeahead.js"></script>';

	$user = readuser($s_id);
	$balance = $user['saldo'];

	$letsgroups = $db->fetchAll('SELECT id, groupname, url FROM letsgroups where apimethod <> \'internal\'');
	$letsgroups = array_merge(array(array(
			'groupname' => $systemname,
			'id'		=> 'self',
		)), $letsgroups);

	$top_buttons .= aphp('transactions', '', 'Lijst', 'btn btn-default', 'Transactielijst', 'exchange', true);

	$top_buttons .= aphp('transactions', 'uid=' . $s_id, 'Mijn transacties', 'btn btn-default', 'Mijn transacties', 'user', true);

	$h1 = 'Nieuwe transactie';
	$fa = 'exchange';

	include $rootpath . 'includes/inc_header.php';

	$minlimit = $user['minlimit'];

	echo '<div>';
	echo '<p><strong>' . link_user($user) . ' huidige ' . $currency . ' stand: ';
	echo '<span class="label label-info">' . $balance . '</span></strong> ';
	echo '<strong>Minimum limiet: <span class="label label-danger">' . $minlimit . '</span></strong></p>';
	echo '</div>';

	echo '<div class="panel panel-info">';
	echo '<div class="panel-heading">';

	echo '<form  method="post" class="form-horizontal">';

	echo ($s_admin) ? '' : '<div style="display:none;">';

	echo '<div class="form-group"';
	echo ($s_admin) ? '' : ' disabled="disabled" ';
	echo '>';
	echo '<label for="letscode_from" class="col-sm-2 control-label">';
	echo '<span class="label label-info">Admin</span> ';
	echo 'Van letscode</label>';
	echo '<div class="col-sm-10">';
	echo '<input type="text" class="form-control" id="letscode_from" name="letscode_from" ';
	echo 'data-typeahead-source="letsgroup_self" ';
	echo 'value="' . $transaction['letscode_from'] . '" required>';
	echo '</div>';
	echo '</div>';

	echo ($s_admin) ? '' : '</div>';

	echo '<div class="form-group">';
	echo '<label for="letsgroup_id" class="col-sm-2 control-label">Aan letsgroep</label>';
	echo '<div class="col-sm-10">';
	echo '<select type="text" class="form-control" id="letsgroup_id" name="letsgroup_id">';

	foreach ($letsgroups as $l)
	{
		echo '<option value="' . $l['id'] . '" ';

		if ($l['id'] == 'self')
		{
			echo 'id="letsgroup_self" ';

			if ($s_admin)
			{
				$typeahead = array('users_active', 'users_inactive', 'users_ip', 'users_im');
			}
			else
			{
				$typeahead = 'users_active';
			}

			$typeahead = get_typeahead($typeahead);
		}
		else
		{
			$typeahead = get_typeahead('users_active', $l['url'], $l['id']);
		}

		echo 'data-typeahead="' . $typeahead . '"';
		echo ($l['id'] == $letsgroup['id']) ? ' selected="selected" ' : '';
		echo '>' . htmlspecialchars($l['groupname'], ENT_QUOTES) . '</option>';
	}
	echo '</select>';
	echo '</div>';
	echo '</div>';

	echo '<div class="form-group">';
	echo '<label for="letscode_to" class="col-sm-2 control-label">Aan letscode</label>';
	echo '<div class="col-sm-10">';
	echo '<input type="text" class="form-control" id="letscode_to" name="letscode_to" ';
	echo 'data-typeahead-source="letsgroup_id" ';
	echo 'value="' . $transaction['letscode_to'] . '" required>';
	echo '</div>';
	echo '</div>';

	echo '<div class="form-group">';
	echo '<label for="amount" class="col-sm-2 control-label">Aantal ' . $currency . '</label>';
	echo '<div class="col-sm-10">';
	echo '<input type="number" class="form-control" id="amount" name="amount" ';
	echo 'value="' . $transaction['amount'] . '" required>';
	echo '</div>';
	echo '</div>';

	echo '<div class="form-group">';
	echo '<label for="description" class="col-sm-2 control-label">Omschrijving</label>';
	echo '<div class="col-sm-10">';
	echo '<input type="text" class="form-control" id="description" name="description" ';
	echo 'value="' . $transaction['description'] . '" required maxlength="60">';
	echo '</div>';
	echo '</div>';

	echo aphp('transactions', '', 'Annuleren', 'btn btn-default') . '&nbsp;';
	echo '<input type="submit" name="zend" value="Overschrijven" class="btn btn-success">';

	echo '<input type="hidden" name="transid" value="' . $transaction['transid'] . '">';

	echo '</form>';
	echo '</div>';
	echo '</div>';

	echo '<small><p>Tip: Het veld Aan LETSCode geeft autosuggesties door naam of letscode in te typen. ';
	echo 'Kies eerst de juiste letsgroep om de juiste suggesties te krijgen.</p></small>';

	include $rootpath . 'includes/inc_footer.php';
	exit;
}

if ($id)
{
	$transaction = $db->fetchAssoc('select t.*
		from transactions t
		where t.id = ?', array($id));

	if ($s_user || $s_admin)
	{
		$top_buttons .= aphp('transactions', 'add=1', 'Toevoegen', 'btn btn-success', 'Transactie toevoegen', 'plus', true);
	}

	$top_buttons .= aphp('transactions', '', 'Lijst', 'btn btn-default', 'Transactielijst', 'exchange', true);

	$h1 = 'Transactie';
	$fa = 'exchange';

	include $rootpath . 'includes/inc_header.php';

	echo '<div class="panel panel-default printview">';
	echo '<div class="panel-heading">';

	echo '<dl class="dl-horizontal">';
	echo '<dt>Tijdstip</dt>';
	echo '<dd>';
	echo $transaction['date'];
	echo '</dd>';

	echo '<dt>Creatietijdstip</dt>';
	echo '<dd>';
	echo $transaction['cdate'];
	echo '</dd>';

	echo '<dt>Transactie ID</dt>';
	echo '<dd>';
	echo $transaction['transid'];
	echo '</dd>';

	echo '<dt>Van account</dt>';
	echo '<dd>';
	echo link_user($transaction['id_from']);
	echo '</dd>';

	if ($transaction['real_from'])
	{
		echo '<dt>Van remote gebruiker</dt>';
		echo '<dd>';
		echo $transaction['real_from'];
		echo '</dd>';
	}

	echo '<dt>Naar account</dt>';
	echo '<dd>';
	echo link_user($transaction['id_to']);
	echo '</dd>';

	if ($transaction['real_to'])
	{
		echo '<dt>Naar remote gebruiker</dt>';
		echo '<dd>';
		echo $transaction['real_to'];
		echo '</dd>';
	}

	echo '<dt>Waarde</dt>';
	echo '<dd>';
	echo $transaction['amount'] . ' ' . $currency;
	echo '</dd>';

	echo '<dt>Omschrijving</dt>';
	echo '<dd>';
	echo $transaction['description'];
	echo '</dd>';

	echo '</dl>';

	echo '</div></div>';

	include $rootpath . 'includes/inc_footer.php';
	exit;
}

/**
 * list
 */
$s_owner = ($s_id == $uid && $s_id && $uid) ? true : false;

$params_sql = $where_sql = $where_code_sql = array();

$params = array(
	'orderby'	=> $orderby,
	'asc'		=> $asc,
	'limit'		=> $limit,
	'start'		=> $start,	
);

if ($uid)
{
	$where_sql[] = 't.id_from = ? or t.id_to = ?';
	$params_sql[] = $uid;
	$params_sql[] = $uid;
	$params['uid'] = $uid;
	$user = readuser($uid);
}

if ($q)
{
	$where_sql[] = 't.description ilike ?';
	$params_sql[] = '%' . $q . '%';
	$params['q'] = $q;
}

if ($fcode)
{
	list($fcode) = explode(' ', trim($fcode));

	$fuid = $db->fetchColumn('select id from users where letscode = \'' . $fcode . '\'');

	if ($fuid)
	{
		$where_code_sql[] = 't.id_from = ?';
		$params_sql[] = $fuid;

		$fcode = link_user($fuid, null, false);
	}
	else
	{
		$where_code_sql[] = '1 = 2';
	}

	$params['fcode'] = $fcode;
}

if ($tcode)
{
	list($tcode) = explode(' ', trim($tcode));

	$tuid = $db->fetchColumn('select id from users where letscode = \'' . $tcode . '\'');

	if ($tuid)
	{
		$where_code_sql[] = 't.id_to = ?';
		$params_sql[] = $tuid;

		$tcode = link_user($tuid, null, false);
	}
	else
	{
		$where_code_sql[] = '1 = 2';
	}

	$params['tcode'] = $tcode;
}

if (count($where_code_sql) > 1)
{
	if ($andor == 'or')
	{
		$where_code_sql = ['(' . implode(' or ', $where_code_sql) . ')'];
	}

	$params['andor'] = $andor;
}

if ($fdate)
{
	$where_sql[] = 't.date >= ?';
	$params_sql[] = $fdate;
	$params['fdate'] = $fdate;
}

if ($tdate)
{
	$where_sql[] = 't.date <= ?';
	$params_sql[] = $tdate;
	$params['tdate'] = $tdate;
}

$where_sql = array_merge($where_sql, $where_code_sql);

if (count($where_sql))
{
	$where_sql = ' where ' . implode(' and ', $where_sql) . ' ';
}
else
{
	$where_sql = '';
}

$query = 'select t.*
	from transactions t ' .
	$where_sql . '
	order by t.' . $orderby . ' ';
$query .= ($asc) ? 'asc ' : 'desc ';
$query .= ' limit ' . $limit . ' offset ' . $start;

$transactions = $db->fetchAll($query, $params_sql);

$row_count = $db->fetchColumn('select count(t.*)
	from transactions t ' . $where_sql, $params_sql);

$pagination = new pagination('transactions', $row_count, $params, $inline);

$asc_preset_ary = array(
	'asc'	=> 0,
	'indicator' => '',
);

$tableheader_ary = array(
	'description' => array_merge($asc_preset_ary, array(
		'lbl' => 'Omschrijving')),
	'amount' => array_merge($asc_preset_ary, array(
		'lbl' => 'Bedrag')),
	'cdate'	=> array_merge($asc_preset_ary, array(
		'lbl' 		=> 'Tijdstip',
		'data_hide' => 'phone'))
);

if ($uid)
{
	$tableheader_ary['user'] = array_merge($asc_preset_ary, array(
		'lbl'			=> 'Tegenpartij',
		'data_hide'		=> 'phone, tablet',
		'no_sort'		=> true,
	));
}
else
{
	$tableheader_ary += array(
		'from_user' => array_merge($asc_preset_ary, array(
			'lbl' 		=> 'Van',
			'data_hide'	=> 'phone, tablet',
			'no_sort'	=> true,
		)),
		'to_user' => array_merge($asc_preset_ary, array(
			'lbl' 		=> 'Aan',
			'data_hide'	=> 'phone, tablet',
			'no_sort'	=> true,
		)),
	);
}

$tableheader_ary[$orderby]['asc'] = ($asc) ? 0 : 1;
$tableheader_ary[$orderby]['indicator'] = ($asc) ? '-asc' : '-desc';

if ($s_admin || $s_user)
{
	if ($uid)
	{
		$user_str = link_user($user, null, false);

		if ($user['status'] != 7)
		{
			if ($s_owner)
			{
				$top_buttons .= aphp('transactions', 'add=1', 'Transactie toevoegen', 'btn btn-success', 'Transactie toevoegen', 'plus', true);
			}
			else if ($s_admin)
			{
				$top_buttons .= aphp('transactions', 'add=1&fuid=' . $uid, 'Transactie van ' . $user_str, 'btn btn-success', 'Transactie van ' . $user_str, 'plus', true);
			}

			if ($s_admin || ($s_user && !$s_owner))
			{
				$top_buttons .= aphp('transactions', 'add=1&tuid=' . $uid, 'Transactie naar ' . $user_str, 'btn btn-warning', 'Transactie naar ' . $user_str, 'exchange', true);
			}
		}

		if (!$inline)
		{
			$top_buttons .= aphp('transactions', '', 'Lijst', 'btn btn-default', 'Transactielijst', 'exchange', true);
		}
	}
	else
	{
		$top_buttons .= aphp('transactions', 'add=1', 'Toevoegen', 'btn btn-success', 'Transactie toevoegen', 'plus', true);

		$top_buttons .= aphp('transactions', 'uid=' . $s_id, 'Mijn transacties', 'btn btn-default', 'Mijn transacties', 'user', true);
	}
}

if ($s_admin)
{
	$top_right .= '<a href="#" class="csv">';
	$top_right .= '<i class="fa fa-file"></i>';
	$top_right .= '&nbsp;csv</a>';
}

$h1 = ($uid && $inline) ? aphp('transactions', 'uid=' . $uid, 'Transacties') : 'Transacties';
$h1 .= ($uid) ? ' van ' . link_user($uid) : '';
$h1 = (!$s_admin && $s_owner) ? 'Mijn transacties' : $h1;

$fa = 'exchange';

if (!$inline)
{
	$h1 .= '<div class="pull-right">';
	$h1 .= '&nbsp;<button class="btn btn-default hidden-xs" title="Filters" ';
	$h1 .= 'data-toggle="collapse" data-target="#filter"';
	$h1 .= '><i class="fa fa-caret-down"></i><span class="hidden-xs hidden-sm"> Filters</span></button>';
	$h1 .= '</div>';

	$includejs = '
		<script src="' . $cdn_datepicker . '"></script>
		<script src="' . $cdn_datepicker_nl . '"></script>
		<script src="' . $rootpath . 'js/csv.js"></script>
		<script src="' . $cdn_typeahead . '"></script>
		<script src="' . $rootpath . 'js/typeahead.js"></script>
	';

	$includecss = '<link rel="stylesheet" type="text/css" href="' . $cdn_datepicker_css . '" />';

	include $rootpath . 'includes/inc_header.php';

	echo '<div class="panel panel-info collapse" id="filter">';
	echo '<div class="panel-heading">';

	echo '<form method="get" class="form-horizontal">';

	echo '<div class="row">';
	echo '<div class="col-xs-12">';

	echo '<div class="input-group">';
	echo '<span class="input-group-addon">';
	echo '<i class="fa fa-search"></i>';
	echo '</span>';
	echo '<input type="text" class="form-control" id="q" value="' . $q . '" name="q" placeholder="Zoekterm">';
	echo '</div>';

	echo '</div>';
	echo '</div><br>';

	echo '<div class="row">';

	echo '<div class="col-md-5">';
	echo '<div class="input-group">';
	echo '<span class="input-group-addon" id="fcode_addon">Van ';
	echo '<span class="fa fa-user"></span></span>';

	$typeahead_name_ary = array('users_active', 'users_extern');

	if ($s_admin)
	{
		$typeahead_name_ary = array_merge($typeahead_name_ary, array(
			'users_inactive', 'users_im', 'users_ip',
		));
	}

	echo '<input type="text" class="form-control" ';
	echo 'aria-describedby="fcode_addon" ';
	echo 'data-typeahead="' . get_typeahead($typeahead_name_ary) . '" '; 
	echo 'name="fcode" id="fcode" placeholder="letscode" ';
	echo 'value="' . $fcode . '">';

	echo '</div>';
	echo '</div>';

	$andor_options = array(
		'and'	=> 'EN',
		'or'	=> 'OF',
	);

	echo '<div class="col-md-2">';
	echo '<div class="input-group col-md-12">';
	echo '<select class="form-control" name="andor">';
	render_select_options($andor_options, $andor);
	echo '</select>';
	echo '</div>';
	echo '</div>';

	echo '<div class="col-md-5">';
	echo '<div class="input-group">';
	echo '<span class="input-group-addon" id="tcode_addon">Naar ';
	echo '<span class="fa fa-user"></span></span>';
	echo '<input type="text" class="form-control" ';
	echo 'data-typeahead-source="fcode" ';
	echo 'placeholder="letscode" ';
	echo 'aria-describedby="tcode_addon" ';
	echo 'name="tcode" value="' . $tcode . '">';
	echo '</div>';
	echo '</div>';

	echo '</div><br>';

	echo '<div class="row">';

	echo '<div class="col-md-6">';
	echo '<div class="input-group">';
	echo '<span class="input-group-addon" id="fdate_addon">Vanaf ';
	echo '<span class="fa fa-calendar"></span></span>';
	echo '<input type="text" class="form-control" placeholder="datum: jjjj-mm-dd" ';
	echo 'aria-describedby="fdate_addon" ';

	echo 'id="fdate" name="fdate" ';
	echo 'value="' . $fdate . '" ';
	echo 'data-provide="datepicker" ';
	echo 'data-date-format="yyyy-mm-dd" ';
	echo 'data-date-default-view-date="-1y" ';
	echo 'data-date-end-date="' . date('Y-m-d') . '" ';
	echo 'data-date-language="nl" ';
	echo 'data-date-today-highlight="true" ';
	echo 'data-date-autoclose="true" ';
	echo 'data-date-immediate-updates="true" ';
	echo '>';

	echo '</div>';
	echo '</div>';

	echo '<div class="col-md-6">';
	echo '<div class="input-group">';
	echo '<span class="input-group-addon" id="tdate_addon">Tot en met ';
	echo '<span class="fa fa-calendar"></span></span>';
	echo '<input type="text" class="form-control" placeholder="datum: jjjj-mm-dd" ';
	echo 'aria-describedby="tdate_addon" ';

	echo 'id="tdate" name="tdate" ';
	echo 'value="' . $tdate . '" ';
	echo 'data-provide="datepicker" ';
	echo 'data-date-format="yyyy-mm-dd" ';
	echo 'data-date-end-date="' . date('Y-m-d') . '" ';
	echo 'data-date-language="nl" ';
	echo 'data-date-today-highlight="true" ';
	echo 'data-date-autoclose="true" ';
	echo 'data-date-immediate-updates="true" ';
	echo '>';

	echo '</div>';
	echo '</div>';

	echo '</div>';

	$params_form = $params;
	unset($params_form['q'], $params_form['fcode'], $params_form['andor'], $params_form['tcode']);
	unset($params_form['fdate'], $params_form['tdate']);

	foreach ($params_form as $name => $value)
	{
		if (isset($value))
		{
			echo '<input name="' . $name . '" value="' . $value . '" type="hidden">';
		}
	}

	echo '<input type="submit" value="zend" style="display: none;" >';

	echo '</form>';

	echo '</div>';
	echo '</div>';	
}
else
{
	echo '<div class="row">';
	echo '<div class="col-md-12">';

	echo '<h3><i class="fa fa-exchange"></i> ' . $h1;
	echo '<span class="inline-buttons">' . $top_buttons . '</span>';
	echo '</h3>';
}

$pagination->render();

echo '<div class="panel panel-primary printview">';
echo '<div class="table-responsive">';
echo '<table class="table table-bordered table-striped table-hover footable csv transactions" ';
echo 'data-sort="false">';
echo '<thead>';
echo '<tr>';

foreach ($tableheader_ary as $key_orderby => $data)
{
	echo '<th';
	echo ($data['data_hide']) ? ' data-hide="' . $data['data_hide'] . '"' : '';
	echo '>';
	if ($data['no_sort'])
	{
		echo $data['lbl'];
	}
	else
	{
		$h_params = $params;

		$h_params['orderby'] = $key_orderby;
		$h_params['asc'] = $data['asc'];

		/*
		$params = array(
			'orderby'		=> $key_orderby,
			'asc'			=> $data['asc'],
		);
		if ($uid)
		{
			$params['uid'] = $uid;
		}
		*/

		echo aphp('transactions', $h_params, array($data['lbl'] . '&nbsp;<i class="fa fa-sort' . $data['indicator'] . '"></i>'));
	}
	echo '</th>';
}

echo '</tr>';
echo '</thead>';
echo '<tbody>';

if ($uid)
{
	foreach($transactions as $t)
	{
		echo '<tr>';
		echo '<td>';
		echo aphp('transactions', 'id=' . $t['id'], $t['description']);
		echo '</td>';

		echo '<td>';
		echo '<span class="text-';
		echo ($t['id_from'] == $uid) ? 'danger">-' : 'success">+';
		echo $t['amount'];
		echo '</span></td>';

		echo '<td>';
		echo $t['cdate'];
		echo '</td>';

		echo '<td>';

		if ($t['id_from'] == $uid)
		{
			if ($t['real_to'])
			{
				echo htmlspecialchars($t['real_to'], ENT_QUOTES);
			}
			else
			{
				echo link_user($t['id_to']);
			}
		}
		else
		{
			if ($t['real_from'])
			{
				echo htmlspecialchars($t['real_from'], ENT_QUOTES);
			}
			else
			{
				echo link_user($t['id_from']);
			}
		}

		echo '</td>';
		echo '</tr>';
	}
}
else
{
	foreach($transactions as $t)
	{
		echo '<tr>';
		echo '<td>';
		echo aphp('transactions', 'id=' . $t['id'], $t['description']);
		echo '</td>';

		echo '<td>';
		echo $t['amount'];
		echo '</td>';

		echo '<td>';
		echo $t['cdate'];
		echo '</td>';

		echo '<td';
		echo ($t['id_from'] == $s_id) ? ' class="me"' : '';
		echo '>';
		if(!empty($t['real_from']))
		{
			echo htmlspecialchars($t['real_from'],ENT_QUOTES);
		}
		else
		{
			echo link_user($t['id_from']);
		}
		echo '</td>';

		echo '<td';
		echo ($t['id_to'] == $s_id) ? ' class="me"' : '';
		echo '>';
		if(!empty($t["real_to"]))
		{
			echo htmlspecialchars($t["real_to"],ENT_QUOTES);
		}
		else
		{ 
			echo link_user($t['id_to']);
		}
		echo '</td>';

		echo '</tr>';
	}
}
echo '</table></div></div>';

$pagination->render();

if ($inline)
{
	echo '</div></div>';
}

if ($uid)
{
	$interletsq = $db->fetchAll('select q.*, l.groupname
		from interletsq q, letsgroups l
		where q.id_from = ?
			and q.letsgroup_id = l.id', array($uid));
	$from = ' van ' . link_user($uid);
}
else
{
	$interletsq = $db->fetchAll('select q.*, l.groupname
		from interletsq q, letsgroups l
		where q.letsgroup_id = l.id');
	$from = '';
}

if (count($interletsq))
{
	echo '<h3><span class="fa fa-exchange"></span> InterLETS transacties' . $from . ' in verwerking';
	echo '<span class="inline-buttons"> ' . $q_buttons . '</span>';
	echo '</h3>';

	echo '<div class="panel panel-warning printview">';
	echo '<div class="table-responsive">';
	echo '<table class="table table-hover table-striped table-bordered footable">';

	echo '<thead>';
	echo '<tr class="warning">';
	echo '<th>Omschrijving</th>';
	echo '<th>Bedrag</th>';
	echo '<th data-hide="phone" data-sort-initial="descending">Tijdstip</th>';
	echo '<th data-hide="phone, tablet">Van</th>';
	echo '<th data-hide="phone, tablet">Aan letscode</th>';
	echo '<th data-hide="phone, tablet">Groep</th>';
	echo '<th data-hide="phone, tablet">Pogingen</th>';
	echo '<th data-hide="phone, tablet">Status</th>';
	echo '<th data-hide="phone, tablet">trans id</th>';
	echo '</tr>';
	echo '</thead>';

	echo '<tbody>';

	foreach($interletsq as $q)
	{
		echo '<tr class="warning">';

		echo '<td>';
		echo htmlspecialchars($q['description'], ENT_QUOTES);
		echo '</td>';

		echo '<td>';
		echo $q['amount'] * readconfigfromdb('currencyratio');
		echo '</td>';

		echo '<td>';
		echo $q['date_created'];
		echo '</td>';

		echo '<td>';
		echo link_user($q['id_from']);
		echo '</td>';

		echo '<td>';
		echo $q['letscode_to'];
		echo '</td>';

		echo '<td>';
		echo $q['groupname'];
		echo '</td>';

		echo '<td>';
		echo $q['retry_count'];
		echo '</td>';

		echo '<td>';
		echo $q['last_status'];
		echo '</td>';

		echo '<td>';
		echo $q['transid'];
		echo '</td>';

		echo '</tr>';
	}
	echo '</table></div></div>';
}

if (!$inline)
{
	include $rootpath . 'includes/inc_footer.php';
}

function cancel($id = null)
{
	header('Location: ' . generate_url('transactions', (($id) ? 'id=' . $id : '')));
	exit;
}
