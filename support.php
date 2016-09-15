<?php

$page_access = 'user';

require_once __DIR__ . '/includes/inc_default.php';

if (isset($_POST['zend']))
{
	$description = $_POST['description'] ?? '';
	$desctiption = trim($description);

	if(empty($description) || strip_tags($description) == '' || $description === false)
	{
		$errors[] = 'Het bericht is leeg.';
	}

	if (!trim(readconfigfromdb('support')))
	{
		$errors[] = 'Het support email adres is niet ingesteld op deze installatie';
	}

	if ($s_master)
	{
		$errors[] = 'Het master account kan geen berichten versturen.';
	}

	if ($token_error = $app['eland.form_token']->get_error())
	{
		$errors[] = $token_error;
	}

	if(!count($errors))
	{
		$contacts = $app['db']->fetchAll('select c.value, tc.abbrev
			from contact c, type_contact tc
			where c.id_user = ?
				and c.id_type_contact = tc.id', [$s_id]);

		$mailaddr = $app['eland.mailaddr']->get($s_id);

		$config_htmlpurifier = \HTMLPurifier_Config::createDefault();
		$config_htmlpurifier->set('Cache.DefinitionImpl', null);
		$htmlpurifier = new \HTMLPurifier($config_htmlpurifier);
		$msg_html = $htmlpurifier->purify($description);

		$converter = new \League\HTMLToMarkdown\HtmlConverter();
		$converter_config = $converter->getConfig();
		$converter_config->setOption('strip_tags', true);
		$converter_config->setOption('remove_nodes', 'img');

		$msg_text = $converter->convert($msg_html);

		$vars = [
			'group'	=> [
				'name'		=> readconfigfromdb('systemname'),
				'tag'		=> readconfigfromdb('systemtag'),
			],
			'user'	=> [
				'text'			=> link_user($s_id, false, false),
				'url'			=> $app['eland.base_url'] . '/users.php?id=' . $s_id,
				'mail'			=> $mailaddr,
			],
			'contacts'		=> $contacts,
			'msg_html'		=> $msg_html,
			'msg_text'		=> $msg_text,
			'config_url'	=> $app['eland.base_url'] . '/config.php?active_tab=mailaddresses',
		];

		$mail_ary = [
			'to'		=> 'support',
			'template'	=> 'support',
			'vars'		=> $vars,
		];

		if ($mailaddr)
		{
			$app['eland.task.mail']->queue([
				'template'	=> 'support_copy',
				'vars'		=> $vars,
				'to'		=> $s_id,
			]);

			$mail_ary['reply_to'] = $s_id;
		}

		$return_message =  $app['eland.task.mail']->queue($mail_ary);

		if (!$return_message)
		{
			$app['eland.alert']->success('De support mail is verzonden.');
			header('Location: ' . generate_url('messages'));
			exit;
		}

		$app['eland.alert']->error('Mail niet verstuurd. ' . $return_message);
	}
	else
	{
		$app['eland.alert']->error($errors);
	}
}
else
{
	$description = '';

	if ($s_master)
	{
		$app['eland.alert']->warning('Het master account kan geen berichten versturen.');
	}
	else
	{
		$mail = $app['eland.mailaddr']->get($s_id);

		if (!count($mail))
		{
			$app['eland.alert']->warning('Je hebt geen email adres ingesteld voor je account. ');
		}
	}
}

if (!readconfigfromdb('mailenabled'))
{
	$app['eland.alert']->warning('E-mail functies zijn uitgeschakeld door de beheerder. Je kan dit formulier niet gebruiken');
}
else if (!readconfigfromdb('support'))
{
	$app['eland.alert']->warning('Er is geen support mailadres ingesteld door de beheerder. Je kan dit formulier niet gebruiken.');
}

$h1 = 'Help / Probleem melden';
$fa = 'ambulance';

$app['eland.assets']->add(['summernote', 'rich_edit.js']);

require_once __DIR__ . '/includes/inc_header.php';

echo '<div class="panel panel-info">';
echo '<div class="panel-heading">';

echo '<form method="post" class="form-horizontal">';

echo '<div class="form-group">';
echo '<div class="col-sm-12">';
echo '<textarea name="description" class="form-control rich-edit" id="description" rows="4">';
echo $description;
echo '</textarea>';
echo '</div>';
echo '</div>';

echo '<input type="submit" name="zend" value="Verzenden" class="btn btn-default">';
$app['eland.form_token']->generate();

echo '</form>';

echo '</div>';
echo '</div>';

include __DIR__ . '/includes/inc_footer.php';
