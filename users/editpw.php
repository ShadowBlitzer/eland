<?php
ob_start();
$rootpath = "../";
$role = 'admin';
require_once($rootpath."includes/inc_default.php");
require_once($rootpath."includes/inc_adoconnection.php");
require_once($rootpath."includes/inc_passwords.php");

if (!isset($_GET["id"])){
	header("Location: overview.php");
	exit;
}

$id = $_GET["id"];

if(isset($_POST["zend"])){
	$pw = array();
	$pw["pw1"] = trim($_POST["pw1"]);
	$pw["pw2"] = trim($_POST["pw2"]);
	$errorlist = validate_input($pw);
	if (empty($errorlist))
	{
		$update["password"]=hash('sha512', $pw["pw1"]);
		$update["mdate"] = date("Y-m-d H:i:s");
		if ($db->AutoExecute("users", $update, 'UPDATE', "id=$id"))
		{
			readuser($id, true);
			$alert->success('Paswoord opgeslagen.');
			header('Location: view.php?id=' . $id);
			exit;
		}
	}
	$alert->error('Paswoord niet opgeslagen.');
}

$user = readuser($id);

$h1 = 'Paswoord veranderen';
$fa = 'key';

include $rootpath . 'includes/inc_header.php';


echo '<p>Gebruiker: ' . $user['name'] . ' ( ' . $user['letscode'] . ' )</p>';
echo "<div class='border_b'>";

echo '<div class="panel panel-info">';
echo '<div class="panel-heading">';


echo "<form method='POST'>";
echo "<table class='data' cellspacing='0' cellpadding='0' border='0'>";
echo "<tr><td valign='top' align='right'>Paswoord</td>";
echo "<td valign='top'>";
echo "<input  type='password' name='pw1' size='30' value='" . $pw['pw1'] . "' required>";
echo "</td>";
echo "<td>";
	if (isset($errorlist["pw1"])){
		echo $errorlist["pw1"];
	}
echo "</td>";
echo "</tr>";
echo "<tr><td valign='top' align='right'>Herhaal paswoord</td>";
echo "<td valign='top'>";
echo "<input  type='password' name='pw2' size='30' value='".$pw['pw2'] . "' required>";
echo "</td>";
echo "<td>";
	if (isset($errorlist["pw2"])){
		echo $errorlist["pw2"];
	}
echo "</td>";
echo "</tr>";
echo "<tr><td colspan='3'>";
	if (isset($errorlist["pw3"])){
		echo $errorlist["pw3"];
	}
echo "</td></tr>";
echo "<tr><td></td><td>";
echo "<input type='submit' value='paswoord wijzigen' name='zend'>";
echo "</td><td>&nbsp;</td></tr>";
echo "</table>";
echo "</form>";

echo '</div>';
echo '</div>';

echo "</div>";

include $rootpath . 'includes/inc_footer.php';

function validate_input($pw){
	$errorlist = array();
	if (empty($pw["pw1"]) || (trim($pw["pw1"]) == "")){
		$errorlist["pw1"] = "<font color='#F56DB5'>Vul <strong>paswoord</strong> in!</font>";
	}

	if (empty($pw["pw2"]) || (trim($pw["pw2"]) == "")){
		$errorlist["pw2"] = "<font color='#F56DB5'>Vul <strong>paswoord</strong> in!</font>";
	}
	if ($pw["pw1"] !== $pw["pw2"]){
	$errorlist["pw3"] = "<font color='#F56DB5'><strong>Paswoorden zijn niet identiek</strong>!</font>";
	}
	return $errorlist;
}
