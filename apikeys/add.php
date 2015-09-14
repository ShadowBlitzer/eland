<?php
ob_start();
$rootpath = "../";
$role = 'admin';
require_once $rootpath . 'includes/inc_default.php';

$posted_list = array();
$posted_list['apikey'] = $_POST['apikey'];
$posted_list['comment'] = $_POST["comment"];
$posted_list['type'] = 'interlets';

if ($_POST['zend'])
{
	if($db->insert('apikeys', $posted_list))
	{
		$alert->success('Apikey opgeslagen.');
		header('Location: '.$rootpath.'apikeys/overview.php');
		exit;
	}
	$alert->error('Apikey niet opgeslagen.');
}

$apikey = sha1(readconfigfromdb('systemname') . microtime());

$h1 = 'Apikey toevoegen';
$fa = 'key';

include $rootpath . 'includes/inc_header.php';

echo '<div class="panel panel-info">';
echo '<div class="panel-heading">';

echo '<form method="post" class="form-horizontal">';

echo '<div class="form-group">';
echo '<label for="apikey" class="col-sm-2 control-label">Apikey</label>';
echo '<div class="col-sm-10">';
echo '<input type="text" class="form-control" id="apikey" name="apikey" ';
echo 'value="' . $apikey . '" required readonly>';
echo '</div>';
echo '</div>';

echo '<div class="form-group">';
echo '<label for="comment" class="col-sm-2 control-label">Comment</label>';
echo '<div class="col-sm-10">';
echo '<input type="text" class="form-control" id="comment" name="comment" ';
echo 'value="' . $posted_list['comment'] . '">';
echo '</div>';
echo '</div>';

echo '<a href="' . $rootpath . 'apikeys/overview.php" class="btn btn-default">Annuleren</a>&nbsp;';
echo '<input type="submit" name="zend" value="Opslaan" class="btn btn-success">';
echo '</form>';

echo '</div>';
echo '</div>';

include $rootpath . 'includes/inc_footer.php';


