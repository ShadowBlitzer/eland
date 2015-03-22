<?php
ob_start();
$rootpath = "./";
$role = 'anonymous';
$allow_anonymous_post = true;
require_once($rootpath."includes/inc_default.php");
require_once($rootpath."includes/inc_adoconnection.php");
//require_once($rootpath."includes/inc_userinfo.php");
require_once($rootpath."includes/inc_passwords.php");
require_once($rootpath."includes/inc_mailfunctions.php");

if ($s_id)
{
	header('Location: index.php');
	exit;
}

$user_id = $_GET['u'];
$token = $_GET['token'];

if ($token & $user_id)
{
	if ($_POST['zend'])
	{
		$password = $_POST['password'];

		if (!(Password_Strength($password) < readconfigfromdb('pwscore')))
		{
			$key = $session_name . '_pwreset_token_' . $user_id;
			if ($redis->get($key) == $token)
			{
				$db->Execute('UPDATE users SET password = \'' . hash('sha512', $password) . '\' WHERE id = ' . $user_id);
				readuser($user_id, true);
				$alert->success('Paswoord opgeslagen.');
				log_event($s_id, 'System', 'password reset success user ' . $user_id);
				header('Location: login.php');
				exit;
			}
			$alert->error('Het reset-token is niet meer geldig.');
			header('Location: pwreset.php');
			exit;
		}
		else
		{
			$alert->error('Te zwak paswoord.');
		}
	}

	require_once($rootpath."includes/inc_header.php");
	
	echo "<form method='post'>";
	echo "<table class='selectbox' border='0'><tr>";
	echo "<td>Nieuw paswoord</td>";
	echo "<td><input type='text' name='password' size='30' value='" . $password . "' required></td>";
	echo "</tr>";
	echo "<tr><td></td><td>";
	echo "<input type='submit' name='zend' value='Reset paswoord'>";
	echo "</td></tr>";
	echo "</table>";
	echo "</form>";

	require_once($rootpath."includes/inc_footer.php");
	exit;
}

if ($_POST['zend'])
{
	$email = $_POST["email"];
	if($email)
	{
		log_event($s_id,"System","Activation request for " .$email);
		$mail_ary = $db->GetArray('SELECT c.id_user, u.login
			FROM contact c, type_contact tc, users u
			WHERE c. value = \'' . $email . '\'
				AND tc.id = c.id_type_contact
				AND tc.abbrev = \'mail\'
				AND c.id_user = u.id');

		if (count($mail_ary) < 2)
		{
			$user_id = $mail_ary[0]['id_user'];
			$login = $mail_ary[0]['login'];

			if ($user_id)
			{
				$token = substr(hash('sha512', $user_id . $session_name . time() . $email), 0, 10);
				$key = $session_name . '_pwreset_token_' . $user_id;
				$redis->set($key, $token);
				$redis->expire($key, 3600);
				$subject = '[' . readconfigfromdb('systemtag') . '] Paswoord reset link.';
				$http = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'? "https://" : "http://";
				$port = ($_SERVER['SERVER_PORT'] == '80') ? '' : ':' . $_SERVER['SERVER_PORT'];
				$url = $http . $_SERVER["SERVER_NAME"] . $port . '/pwreset.php?token=' . $token . '&u=' . $user_id;
				$message = "Link om je paswoord te resetten :  \n
					" . $url . "\n
					Let op: deze link is slechts 1 uur geldig.\n
					Je login is: ". $login;
				sendemail(readconfigfromdb('from_address'), $email, $subject, $message);
				$alert->success('Een link om je paswoord te resetten werd naar je mailbox verzonden. Opgelet deze link blijft slechts één uur geldig.');
				log_event($s_id,"System","Paswoord reset link verstuurd naar " . $email);
				header('Location: login.php');
				exit;
			}
			else
			{
				$alert->error('Mailadres niet bekend');
			}
		}
		else
		{
			$alert->error('Mailadres niet uniek.');
		}
	}
	else
	{
		$alert->error("Geef een mailadres op");
		log_event($s_id,"System","Empty activation request");
	}
}

require_once($rootpath."includes/inc_header.php");

echo "<h1>Login of paswoord vergeten</h1>";
echo '<p>Met onderstaand formulier stuur je je login en een link om je paswoord te resetten naar je mailbox.';
echo ' Als je alleen je login vergeten bent, hoef je de link niet te gebruiken.</p>';
echo "<form method='post'>";
echo "<table class='selectbox' border='0'><tr>";
echo "<td>E-mail adres</td>";
echo "<td><input type='email' name='email' size='30' value='" . $email . "' required></td>";
echo "</tr>";
echo "<tr><td></td><td>";
echo "<input type='submit' name='zend' value='Reset paswoord'>";
echo "</td></tr>";
echo "</table>";
echo "</form>";

require_once($rootpath."includes/inc_footer.php");
