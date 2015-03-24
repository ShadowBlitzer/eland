<?php
ob_start();
$rootpath = "../";
$role = 'user';
require_once($rootpath."includes/inc_default.php");
require_once($rootpath."includes/inc_adoconnection.php");
require_once($rootpath."includes/inc_transactions.php");
require_once($rootpath."includes/inc_userinfo.php");
require_once($rootpath."includes/inc_mailfunctions.php");
require_once($rootpath."includes/inc_form.php");

$transaction = array();

if ($_POST['zend'])
{
	$transaction["description"] = pg_escape_string($_POST["description"]);
	list($letscode_from) = explode(' ', pg_escape_string($_POST['letscode_from']));
	list($letscode_to) = explode(' ', pg_escape_string($_POST['letscode_to']));
	$transaction['amount'] = pg_escape_string($_POST['amount']);
	$transaction['date'] = ($_POST['date']) ? pg_escape_string($_POST['date']) : $transaction["date"] = date("Y-m-d H:i:s");
	$letsgroupid = $_POST['letsgroup'];

	$timestamp = make_timestamp($transaction["date"]);

	$letsgroup = $db->GetRow('SELECT * FROM letsgroups WHERE id = ' . $letsgroupid);

	if (!isset($letsgroup))
	{
		$alert->error('Letsgroep niet gevonden.');
	}

	$where = ($s_accountrole == 'user') ? 'id = ' . $s_id : 'letscode = \'' . $letscode_from . '\'';
	$fromuser = $db->GetRow('SELECT * FROM users WHERE ' . $where);

	$letscode_touser = ($letsgroup['apimethod'] == 'internal') ? $letscode_to : $letsgroup['localletscode'];
	$touser = $db->GetRow('SELECT * FROM users WHERE letscode = \'' . $letscode_touser . '\'');

	$transaction['id_from'] = $fromuser['id'];
	$transaction['id_to'] = $touser['id'];

	$transaction['transid'] = generate_transid();

	$errors = validate_input($transaction, $fromuser, $touser);

	if(!empty($errors))
	{
		$alert->error(implode("\n", $errors));
	}
	else
	{
		switch($letsgroup['apimethod'])
		{
			case 'internal':
			case 'mail':
			
				if (insert_transaction($transaction))
				{
					if ($letsgroup['apimethod'] == 'internal')
					{
						mail_transaction($transaction);
					}
					else
					{
						mail_interlets_transaction($transaction);
					}
					$alert->success("Transactie opgeslagen");
				}
				else
				{
					$alert->error("Gefaalde transactie");
				}
				header('Location: ' . $rootpath . 'transactions/alltrans.php');
				exit;

				break;

			case "elassoap":

				$transaction["letscode_to"] = $letscode_to;
				$transaction["letsgroup_id"] = $letsgroupid;
				$currencyratio = readconfigfromdb("currencyratio");
				$transaction["amount"] = $transaction["amount"] / $currencyratio;
				$transaction["amount"] = (float) $transaction["amount"];
				$transaction["amount"] = round($transaction["amount"], 5);
				$transaction["signature"] = sign_transaction($transaction, $letsgroup["presharedkey"]);
				$transaction["retry_until"] = time() + (60*60*24*4);
				// Queue the transaction for later handling
				$mytransid = queuetransaction($transaction, $fromuser, $touser);
				if($mytransid == $transid)
				{
					$alert->success("Interlets transactie in verwerking");
					if (!$redis->get($session_name . '_interletsq'))
					{
						$redis->set($session_name . '_interletsq', time());
					}
				}
				else
				{
					$alert->error("Gefaalde transactie", 1);
				}
				header('Location: ' . $rootpath . 'transactions/alltrans.php');
				exit;
				
				break;

			case 'interletsdirect':

				break;
		}
	}
}
else
{
	$mid = $_GET['mid'];
	$uid = $_GET['uid'];

	$transaction = array(
		'date'			=> date('Y-m-d'),
		'letscode_from'	=> $s_letscode . ' ' . $s_name,
		'letscode_to'	=> '',
		'amount'		=> '1',
		'description'	=> '',
	);

	if ($mid)
	{
		$transaction = $db->GetRow('SELECT
				m.content, m.amount, u.letscode as letscode_to, u.fullname
			FROM messages m, users u
			WHERE u.id = m.id_user
				AND m.id = ' . $mid);
		$transaction['letscode_to'] .= ' ' . $transaction['fullname'];
		$transaction['description'] =  '#m' . $mid . ' ' . $transaction['content'];
	}
	else if ($uid)
	{
		$transaction = $db->GetRow('SELECT letscode as letscode_to, fullname FROM users WHERE id = ' . $uid);
		$transaction['letscode_to'] .= ' ' . $transaction['fullname'];
	}
}

$includejs = '
	<script src="' . $cdn_jquery . '"></script>
	<script src="' . $cdn_jqueryui . '"></script>
	<script src="' . $cdn_jqueryui_i18n . '"></script>
	<script src="' . $cdn_typeahead . '"></script>
	<script src="' . $rootpath . 'js/transactions_add.js"></script>';

$includecss = '<link rel="stylesheet" type="text/css" href="' . $cdn_jqueryui_css . '" />';

include $rootpath . 'includes/inc_header.php';

$user = get_user($s_id);
$balance = $user["saldo"];

//$list_users = get_users($s_id);

$currency = readconfigfromdb('currency');

echo "<h1>{$currency} uitschrijven</h1>";

$minlimit = $user["minlimit"];

echo "<div id='baldiv'>";
echo '<p><strong>' . $user["name"].' '.$user["letscode"] . ' huidige ' . $currency . ' stand: '.$balance.'</strong> || ';
echo "<strong>Limiet minstand: " . $minlimit . "</strong></p>";
echo "</div>";

$date = date("Y-m-d");

//echo "<script type='text/javascript' src='/js/posttransaction.js'></script>";
// echo "<script type='text/javascript' src='/js/userinfo.js'></script>";
echo "<div id='transformdiv'>";
echo "<form  method='post'>";
echo "<table>";

echo "<tr>";
echo "<td align='right'>Van LETScode</td>";
echo "<td>";

/*
echo "<select name='letscode_from' accesskey='2' id='letscode_from' \n";
if($s_accountrole != "admin") {
	echo " DISABLED";
}
echo " onchange=\"javascript:document.getElementById('baldiv').innerHTML = ''\">";

$list_users = $db->GetAssoc('SELECT letscode, fullname
	FROM users
	WHERE status IN (1, 2)
		AND accountrole NOT IN (\'guest\', \'interlets\')
	ORDER BY letscode');
render_select_options($list_users, $transaction['letscode_from']);
echo "</select>\n";
*/

echo '<input type="text" name="letscode_from" size="40" value="' . $transaction['letscode_from'] . '" ';
echo ($s_accountrole == 'admin') ? '' : ' disabled="disabled" ';
echo 'required>';


echo "</td><td width='150'><div id='fromoutputdiv'></div>";
echo "</td></tr>";

echo "<tr><td valign='top' align='right'>Datum</td><td>";
echo "<input type='text' name='date' id='date' size='18' value='" .$date ."'";
echo ($s_accountrole == "admin") ? '' : ' disabled="disabled"';
echo ">";
echo "</td><td>";

echo "</td></tr><tr><td></td><td>";
echo "</td></tr>";

echo "<tr><td align='right'>";
echo "Aan LETS groep";
echo "</td><td>";
echo "<select name='letsgroup' id='letsgroup' onchange=\"document.getElementById('letscode_to').value='';\">\n";

$letsgroups = $db->getAssoc('SELECT id, groupname FROM letsgroups');
render_select_options($letsgroups, $transaction['letsgroup']);

echo "</select>";
echo "</td><td>";
echo "</td></tr><tr><td></td><td>";
echo "<tr><td align='right'>";
echo "Aan LETScode";
echo "</td><td>";
echo '<input type="text" name="letscode_to" value="' . $transaction['letscode_to'] . '" size="40" required>';
echo "</td><td><div id='tooutputdiv'></div>";
echo "</td></tr><tr><td></td><td>";
echo "</td></tr>";

echo '<tr><td valign="top" align="right">Aantal ' . $currency . '</td><td>';
echo "<input type='number' min='1' name='amount' size='10' ";
echo 'value="' . $transaction['amount'] . '" required>';
echo "</td><td>";
echo "</td></tr>";
echo "<tr><td></td><td>";
echo "</td></tr>";

echo "<tr><td valign='top' align='right'>Dienst</td><td>";
echo '<input type="text" name="description" id="description" size="40" maxlength="60" ';
echo 'value="' . $transaction['description'] . '" required>';
echo "</td><td>";
echo "</td></tr><tr><td></td><td>";
echo "</td></tr>";
echo "<tr><tr><td colspan='3'>&nbsp;</td></tr><td></td><td colspan='2'>";
echo "<input type='submit' name='zend' id='zend' value='Overschrijven'>";
echo "</td></tr></table>";
echo "</form>";
echo "</div>";


////////// output div
// echo "<div id='serveroutput' class='serveroutput'>";
// echo "</div>";

/*
echo "<table border=0 width='100%'><tr><td align='left'>";
$myurl="userlookup.php";
echo "<form id='lookupform'><input type='button' id='lookup' value='LETSCode opzoeken' onclick=\"javascript:newwindow=window.open('$myurl','Lookup','width=600,height=500,scrollbars=yes,toolbar=no,location=no,menubar=no');\"></form>";

echo "</td><td align='right'>";
echo "</td></tr></table>";
*/

include($rootpath."includes/inc_footer.php");

///////////////////////////////////////////////////////


// Make timestamps for SQL statements
function make_timestamp($timestring)
{
/*	$month = substr($timestring, 3, 2);
	$day = substr($timestring, 0, 2);
	$year = substr($timestring, 6, 4); */
	list($day, $month, $year) = explode('-', $timestring);
	return mktime(0, 0, 0, trim($month), trim($day), trim($year));
}

function validate_input($transaction, $fromuser, $touser)
{
	global $s_accountrole;

	$errors = array();

	if (!isset($transaction["description"]) || (trim($transaction["description"] )==""))
	{
		$errors["description"]="Dienst is niet ingevuld";
	}

	if (!isset($transaction["amount"])|| (trim($transaction["amount"] )==""))
	{
		$errors["amount"]="Bedrag is niet ingevuld";
	}
	else if (eregi('^[0-9]+$', $var) == FALSE)
	{
		$errors["amount"]="Bedrag is geen geldig getal";
	}

	$user = get_user($transaction["id_from"]);
	if(($user["saldo"] - $transaction["amount"]) < $fromuser["minlimit"] && $s_accountrole != "admin")
	{
		$errors["amount"]="Je beschikbaar saldo laat deze transactie niet toe";
	}

	if(empty($fromuser))
	{
		$errors["id_from"] = "Gebruiker bestaat niet";
	}

	if(empty($touser) )
	{
		$errors["id_to"] = "Bestemmeling bestaat niet";
	}

	if($fromuser["letscode"] == $touser["letscode"])
	{
		$errors["id"] = "Van en Aan zijn hetzelfde";
	}

	if(($touser["maxlimit"] != NULL && $touser["maxlimit"] != 0)
		&& $touser["saldo"] > $touser["maxlimit"] && $s_accountrole != "admin")
	{
		$errors["id_to"] = "De bestemmeling heeft zijn maximum limiet bereikt";
	}

	if(!($touser["status"] == 1 || $touser["status"] == 2))
	{
		$errors["id_to"]="De bestemmeling is niet actief";
	}

	//date may not be empty
	if (!isset($transaction["date"])|| (trim($transaction["date"] )==""))
	{
		$errors["date"]="Datum is niet ingevuld";
	}
	else if (strtotime($transaction["date"]) == -1)
	{
		$errors["date"]="Fout in datumformaat (jjjj-mm-dd)";
	}

	return $errors;
}





