<?php
ob_start();
$rootpath = "../";
$role = 'user';
require_once($rootpath."includes/inc_default.php");
require_once($rootpath."includes/inc_adoconnection.php");

if (!isset($s_id)){
	header("Location: ".$rootpath."login.php");
	exit;
}

if(!isset($_GET["id"]))
{
	header("Location:  mydetails.php");
	exit;
}

$id = $_GET["id"];

if(validate_request($id) == 1)
{
	delete_contact($id);
	$alert->success('Contact verwijderd.');
	header("Location:  mydetails.php");
	exit;
}

$alert->error('Contact niet verwijderd.');
include($rootpath."includes/inc_header.php");
show_error();
include($rootpath."includes/inc_footer.php");


////////////////////////////////////////////////////////////////////////////


function validate_request($id){
	$contact = get_contact($id);
	$contact_type = get_contact_type($contact["id_type_contact"]);
	$type_count = get_type_count($contact["id_user"],$contact["id_type_contact"]);

	if($contact_type["protect"] == 1 && $type_count < 2) {
		return 0;
	} else {
		return 1;
	}
}

function get_type_count($userid,$typeid) {
	global $db;
        $query = "SELECT COUNT(id) AS count FROM contact WHERE id_user = $userid AND id_type_contact = $typeid";
	$count =  $db->GetRow($query);
	return $count["count"];
}

function get_contact($id) {
	global $db;
	$query = "SELECT * FROM contact WHERE id = " .$id;
	$contact = $db->GetRow($query);
	return $contact;
}

function get_contact_type($typeid){
        global $db;
        $query = "SELECT * FROM type_contact WHERE id = " .$typeid;
        $contact_type = $db->GetRow($query);
	return $contact_type;
}

function show_error() {
	echo "<P><font color='red'><strong>De instellingen van eLAS laten je niet toe deze informatie te verwijderen.</strong></font></P>";
	echo "<P>Als je niet wil dat andere leden deze gegevens zien kan je de optie 'publiek' uitschakelen</P>";
	echo "<P><a href='mydetails.php'>Terug naar het overzicht</P>";
}

function delete_contact($id){
	global $db;
	$query = "DELETE FROM contact WHERE id =".$id ;
	$result = $db->Execute($query);
}
