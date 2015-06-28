<?php
ob_start();
$rootpath = '../';
$role = 'admin';
require_once $rootpath . 'includes/inc_default.php';
require_once $rootpath . 'includes/inc_adoconnection.php';

$messages = $db->GetArray('select m.*,
	u.name, u.letscode
	from messages m, users u
	where m.id_user = u.id
		and validity > \'' . gmdate('Y-m-d H:i:s') . '\'');

header("Content-disposition: attachment; filename=elas-messages-".date("Y-m-d").".csv");
header("Content-Type: application/force-download");
header("Content-Transfer-Encoding: binary");
header("Pragma: no-cache");
header("Expires: 0");
        
echo '"letscode", "username", "cdate","validity","id_category","content","msg_type"';
	echo "\r\n";
foreach($messages as $value)
{
	echo '"';
	echo $value['letscode'];
	echo '","';
	echo $value['name'];
	echo '","';
	echo $value['cdate'];
	echo '","';
	echo $value['validity'];
	echo '","';
	echo $value['id_category'];
	echo '","';
	echo $value['content'];
	echo '","';
	echo $value['msg_type'];
	echo '"';
	echo "\r\n";
}
