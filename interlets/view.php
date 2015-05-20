<?php
ob_start();
$rootpath = "../";
$role = 'admin';
require_once($rootpath."includes/inc_default.php");
require_once($rootpath."includes/inc_adoconnection.php");
require_once($rootpath."includes/inc_userinfo.php");

include($rootpath."includes/inc_header.php");

if (!isset($_GET["id"]))
{
	header('Location: overview.php');
}

$id = $_GET["id"];
$group = $db->GetRow('SELECT * FROM letsgroups WHERE id = ' . $id);
echo '<h1>' . $group['groupname'] . '</h1>';


echo "<div >";

echo '<dl class="dl-horizontal">';
echo "<dt>eLAS Soap status</dt>";

echo "<dd><i><div id='statusdiv'>";
//echo "<script type='text/javascript'>showsmallloader('statusdiv')</script>";
$soapurl = $group["elassoapurl"] ."/wsdlelas.php?wsdl";
$apikey = $group["remoteapikey"];
$client = new nusoap_client($soapurl, true);
$err = $client->getError();
if (!$err) {
	$result = $client->call('getstatus', array('apikey' => $apikey));
	$err = $client->getError();
    	if (!$err) {
		echo $result;
	}
}
echo "</div></i>";
echo "</dd>";
echo '</dl>';

echo '<dl class="dl-horizontal">';
echo "<dt>Groepnaam</dt>";
echo "<dd>" .$group["groupname"] ."</dd>";
echo "</dl>";

echo '<dl class="dl-horizontal">';
echo "<dt>Korte naam</dt>";
echo "<dd>" .$group["shortname"] ."</dd>";
echo "</dl>";

echo '<dl class="dl-horizontal">';
echo "<dt>Prefix</dt>";
echo "<dd>" .$group["prefix"] ."</dd>";
echo "</dl>";

echo '<dl class="dl-horizontal">';
echo "<dt>API methode</dt>";
echo "<dd>" .$group["apimethod"] ."</dd>";
echo "</dl>";

echo '<dl class="dl-horizontal">';
echo "<dt>API key</dt>";
echo "<dd>" .$group["remoteapikey"] ."</dd>";
echo "</dl>";

echo '<dl class="dl-horizontal">';
echo "<dt>Lokale LETS code</dt>";
echo "<dd>" .$group["localletscode"] ."</dd>";
echo "</dl>";

echo '<dl class="dl-horizontal">';
echo "<dt>Remote LETS code</dt>";
echo "<dd>" .$group["myremoteletscode"] ."</dd>";
echo "</dl>";

echo '<dl class="dl-horizontal">';
echo "<dt>URL</dt>";
echo "<dd>" .$group["url"] ."</dd>";
echo "</dl>";

echo '<dl class="dl-horizontal">';
echo "<dt>SOAP URL</dt>";
echo "<dd>" .$group["elassoapurl"] ."</dd>";
echo "</dl>";

echo '<dl class="dl-horizontal">';
echo "<dt>Preshared Key</dt>";
echo "<dd>" .$group["presharedkey"]."</dd>";
echo "</dl>";

echo "</div>";


//echo "<script type='text/javascript' src='/js/soapstatus.js'></script>";

echo "<p><small><i>";
echo "* API methode bepaalt de connectie naar de andere groep, geldige waarden zijn internal, elassoap en mail";
echo "<br>* De API key moet je aanvragen bij de beheerder van de andere installatie, het is een sleutel die je eigen eLAS toelaat om met de andere eLAS te praten";
echo "<br>* Lokale LETS Code is de letscode waarmee de andere groep op deze installatie bekend is, deze gebruiker moet al bestaan";
echo "<br>* Remote LETS code is de letscode waarmee deze installatie bij de andere groep bekend is, deze moet aan de andere kant aangemaakt zijn";
echo "<br>* URL is de weblocatie van de andere installatie";
echo "<br>* SOAP URL is de locatie voor de communicatie tussen eLAS en het andere systeem, voor een andere eLAS is dat de URL met /soap erachter";
echo "<br>* Preshared Key is een gedeelde sleutel waarmee interlets transacties ondertekend worden.  Deze moet identiek zijn aan de preshared key voor de lets-rekening van deze installatie aan de andere kant";
echo "</i></small></p>";


echo "<div id='navcontainer'>";
echo "<ul class='hormenu'>";
echo '<li><a href="edit.php?mode=edit&id=' . $id . '">Aanpassen</a></li>';
echo '<li><a href="delete.php?id=' . $id . '">Verwijderen</a></li>';
echo "</ul>";
echo "</div>";

include($rootpath."includes/inc_footer.php");
