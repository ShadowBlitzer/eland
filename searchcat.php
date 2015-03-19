<?php
ob_start();
$rootpath = "";
$role = 'guest';
require_once($rootpath."includes/inc_default.php");
require_once($rootpath."includes/inc_adoconnection.php");

if(!isset($s_id)){
	header("Location: ".$rootpath."login.php");
	exit;
}

$q = $_GET['q'];

include($rootpath."includes/inc_header.php");
echo "<h1>Vraag & Aanbod</h1>";

echo "<form method='get' action='$rootpath/messages/search.php'>";
echo "<input type='text' name='q' size='40' ";
echo " value='". $q ."'";
echo ">";
echo "<input type='submit' name='zend' value='Zoeken'>";
echo "<br><small><i>Een leeg zoekveld geeft ALLE V/A als resultaat terug</i></small>";
echo "</form>";

$cats = $db->GetArray('SELECT * FROM categories ORDER BY fullname');

echo "<div class='border_b'>";
echo "<table class='data' cellpadding='0' cellspacing='0' border='1' width='99%'>";
echo "<tr class='header'>";
echo "<td><strong>Categorie</strong></td>";
echo '<td>Vraag</td>';
echo '<td>Aanbod</td>';
echo "</tr>";

foreach($cats as $value){

	$row_class =  ($value["id_parent"]) ? 'uneven_row' : 'even_row';

	echo "<tr class='" . $row_class . "'>";
	echo "<td valign='top'>";
	echo ($value['id_parent']) ? '' : '<strong>';
	echo "<a href='searchcat_viewcat.php?id=".$value["id"]."'>";
	echo htmlspecialchars($value["fullname"],ENT_QUOTES);
	echo "</a>";
	echo ($value['id_parent']) ? '' : '</strong>';
	echo "</td>";

	echo '<td>' . (($v = $value['stat_msgs_wanted']) ? $v : '') . '</td>';
	echo '<td>' . (($v = $value['stat_msgs_offers']) ? $v : '') . '</td>';
	echo "</tr>";
}
echo "</table></div>";

if($s_accountrole != 'guest'){
	echo "<h1>Andere (interlets) groepen raadplegen</h1>";
	echo "<table class='data' cellpadding='0' cellspacing='0' border='1'>";
	$letsgroups = $db->Execute("SELECT * FROM letsgroups WHERE apimethod <> 'internal'");
	foreach($letsgroups as $key => $value){
		echo "<tr><td nowrap>";
		echo "<a href='#' onclick=window.open('interlets/redirect.php?letsgroup=" .$value["id"] ."','interlets','location=no,menubar=no,scrollbars=yes')>" .$value["groupname"] ."</a>";
		echo "</td></tr>";
	}
	echo "</table>";
}
include($rootpath."includes/inc_footer.php");

///////////////////


function show_outputdiv(){
        echo "<div id='output'><img src='/gfx/ajax-loader.gif' ALT='loading'>";
        echo "<script type=\"text/javascript\">loadurl('rendersearchcat.php')</script>";
        echo "</div>";
}


