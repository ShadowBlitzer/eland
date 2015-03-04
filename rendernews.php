<?php
ob_start();
$rootpath = "";
require_once($rootpath."includes/inc_default.php");
require_once($rootpath."includes/inc_adoconnection.php");
session_start();
$s_id = $_SESSION["id"];
$s_name = $_SESSION["name"];
$s_letscode = $_SESSION["letscode"];
$s_accountrole = $_SESSION["accountrole"];

if(isset($s_id)){
	$newsitems = get_all_newsitems();
 	show_all_newsitems($newsitems, $s_accountrole);
}else{
	redirect_login($rootpath);
}

////////////////////////////////////////////////////////////////////////////
//////////////////////////////F U N C T I E S //////////////////////////////
////////////////////////////////////////////////////////////////////////////

function show_ptitle(){
	echo "<h1>Nieuws</h1>";
}

function redirect_login($rootpath){
	header("Location: ".$rootpath."login.php");
}

function show_all_newsitems($newsitems, $s_accountrole){
	echo "<div class='border_b'>";
	echo "<table class='data' cellpadding='0' cellspacing='0' border='1' width='99%'>";
	echo "<tr class='header'>";
	//echo "<td nowrap><strong>Datum</strong></td>";
	echo "<td nowrap width='20%'><strong>Agendadatum</strong></td>";
	echo "<td nowrap><strong>Titel</strong></td>";
	echo "</tr>";
	$rownumb=0;
	foreach($newsitems as $value){
	$rownumb=$rownumb+1;
		if($rownumb % 2 == 1){
			echo "<tr class='uneven_row'>";
		}else{
	        	echo "<tr class='even_row'>";
		}
		//echo "<td valign='top' nowrap>";
		//echo $value["date"];
		//echo "</td>";
	echo "<td nowrap valign='top'>";
			if(trim($value["idate"]) != "00/00/00"){
					echo $value["idate"];
			}
		echo "</td>";
		echo "<td valign='top'>";
		echo "<a href='news/view.php?id=".$value["nid"]."'>";
		if($value["approved"]==False){
			echo "<del>";
			echo htmlspecialchars($value["headline"],ENT_QUOTES);
			echo "</del>";
		} else {
			echo htmlspecialchars($value["headline"],ENT_QUOTES);
		}
		echo "</a>";
		echo "</td>";
		echo "</tr>";
	}
	echo "</table></div>";
}

function get_all_newsitems(){
	global $db;
	global $s_accountrole;
	$query = "SELECT *, ";
	$query .= "news.id AS nid, ";
	$query .= " news.cdate AS date, ";
	$query .= " news.itemdate AS idate ";
	$query .= " FROM news, users ";
	$query .= " WHERE news.id_user = users.id ";
	if($s_accountrole != "admin"){
		$query .= "AND news.approved = True ";
	}
	$query .= "ORDER BY news.cdate DESC";
	$newsitems = $db->GetArray($query);
	return $newsitems;
}

?>
