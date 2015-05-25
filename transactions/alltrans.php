<?php
ob_start();
$rootpath = "../";
$role = 'guest';
require_once($rootpath."includes/inc_default.php");
require_once($rootpath."includes/inc_adoconnection.php");

$trans_orderby = $_GET["trans_orderby"];
$asc = $_GET["asc"];

$trans_orderby = (isset($trans_orderby) && ($trans_orderby != '')) ? $trans_orderby : 'cdate';
$asc = (isset($asc) && ($asc != '')) ? $asc : 0;

$query_orderby = ($trans_orderby == 'fromusername' || $trans_orderby == 'tousername') ? $trans_orderby : 't.'.$trans_orderby;
$query = 'SELECT t.*, 
		t.id AS transid, 
		fu.id AS fromuserid,
		tu.id AS touserid,
		fu.name AS fromusername,
		tu.name AS tousername,
		fu.letscode AS fromletscode, tu.letscode AS toletscode, 
		t.date AS datum,
		t.cdate AS cdatum 
	FROM transactions t, users fu, users tu
	WHERE t.id_to = tu.id
	AND t.id_from = fu.id
	ORDER BY '.$query_orderby. ' ';
$query .= ($asc) ? 'ASC ' : 'DESC ';
$query .= 'LIMIT 1000';
$transactions = $db->GetArray($query);

$asc_preset_ary = array(
	'asc'	=> 0,
	'indicator' => '',
);

$tableheader_ary = array(
	'description' => array_merge($asc_preset_ary, array(
		'lang' => 'Omschrijving')),
	'amount' => array_merge($asc_preset_ary, array(
		'lang' => 'Bedrag')),
	'fromusername' => array_merge($asc_preset_ary, array(
		'lang' => 'Van',
		'data_hide'	=> 'phone, tablet')),
	'tousername' => array_merge($asc_preset_ary, array(
		'lang' => 'Aan',
		'data_hide'	=> 'phone, tablet')),
	'cdate'	=> array_merge($asc_preset_ary, array(
		'lang' => 'Tijdstip',
		'data_hide' => 'phone')),
);

$tableheader_ary[$trans_orderby]['asc'] = ($asc) ? 0 : 1;
$tableheader_ary[$trans_orderby]['indicator'] = ($asc) ? '-asc' : '-desc';

if (in_array($s_accountrole, array('admin', 'user')))
{
	$top_buttons = '<a href="' . $rootpath . 'transactions/add.php?" class="btn btn-success"';
	$top_buttons .= ' title="gebruiker toevoegen"><i class="fa fa-plus"></i>';
	$top_buttons .= '<span class="hidden-xs hidden-sm"> Toevoegen</span></a>';
}

include($rootpath."includes/inc_header.php");

echo '<h1>Transacties</h1>';

echo '<div class="table-responsive">';
echo '<table class="table table-bordered table-striped table-hover footable" data-sort="false">';
echo '<thead>';
echo '<tr>';

foreach ($tableheader_ary as $key_orderby => $data)
{
	echo '<th';
	echo ($data['data_hide']) ? ' data-hide="' . $data['data_hide'] . '"' : '';
	echo '>';
	echo '<a href="alltrans.php?trans_orderby='.$key_orderby.'&asc='.$data['asc'].'">';
	echo $data['lang'];
	echo '&nbsp;<i class="fa fa-sort' . $data['indicator'] . '"></i>';
	echo '</a></td>';
}

echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach($transactions as $key => $value)
{
	echo '<tr>';
	echo '<td><a href="' . $rootpath . 'view.php?id=' . $value['transid'] . '">';
	echo htmlspecialchars($value['description'],ENT_QUOTES);
	echo '</a>';
	echo '</td>';

	echo '<td>';
	echo $value['amount'];
	echo '</td>';

	echo '<td';
	echo ($value['fromuserid'] == $s_id) ? ' class="me"' : '';
	echo '>';
	if(!empty($value["real_from"]))
	{
		echo htmlspecialchars($value['real_from'],ENT_QUOTES);
	}
	else
	{
		echo '<a href="' . $rootpath . 'memberlist_view.php?id=' . $value['fromuserid'] . '">';
		echo htmlspecialchars($value["fromusername"],ENT_QUOTES). " (" .trim($value["fromletscode"]).")";
		echo '</a>';
	}
	echo '</td>';

	echo '<td';
	echo ($value['touserid'] == $s_id) ? ' class="me"' : '';
	echo '>';
	if(!empty($value["real_to"]))
	{
		echo htmlspecialchars($value["real_to"],ENT_QUOTES);
	}
	else
	{ 
		echo '<a href="' . $rootpath . 'memberlist_view.php?id=' . $value['touserid'] . '">';
		echo htmlspecialchars($value["tousername"],ENT_QUOTES). " (" .trim($value["toletscode"]).")";
		echo '</a>';
	}
	echo '</td>';

	echo '<td>';
	echo $value['cdatum'];
	echo '</td>';

	echo '</tr>';
}
echo '</table></div>';

include($rootpath."includes/inc_footer.php");
