<?php

ob_start();
$rootpath = "../";
$role = 'admin'; 
require_once($rootpath."includes/inc_default.php");

$msgid = $_GET['id'];
$validity = $_GET['validity'];

if(isset($msgid))
{
	$msg = $db->fetchAssoc('select * from messages where id = ?', array($msgid));

	if ($msg)
	{
		$m = array(
			'validity'		=> strtotime($msg['validity']) + (86400 * 30 * $validity),
			'mdate'			=> gmdate('Y-m-d H:i:s'),
			'exp_user_warn'	=> 'f',
		);

		if ($db->update('messages', $m, 'UPDATE', array('id' => $msgid))
		{
			$alert->success('Vraag of aanbod is verlengd.');
		}
		else
		{
			$alert->error('Vraag of aanbod is niet verlengd.');
		}
	}
}

header('Location: ' . $rootpath . 'messages/overview.php');
exit;
