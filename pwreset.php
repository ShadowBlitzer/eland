<?php

$page_access = 'anonymous';

require_once __DIR__ . '/includes/inc_default.php';
require_once __DIR__ . '/includes/inc_passwords.php';

$token = $_GET['token'] ?? false;

if ($token)
{
	if ($_POST['zend'])
	{
		$password = $_POST['password'];

		if (!(password_strength($password) < 50)) // ignored readconfigfromdb('pwscore')
		{
			if ($user_id = $app['redis']->get($app['eland.this_group']->get_schema() . '_token_' . $token))
			{
				$app['db']->update('users', ['password' => hash('sha512', $password)], ['id' => $user_id]);
				$user = readuser($user_id, true);
				$app['eland.alert']->success('Paswoord opgeslagen.');

				$url = $app['eland.base_url'] . '/login.php?login=' . $user['letscode'];

				$subj = 'nieuw paswoord.';
				$text = 'Beste ' . $user['name'] . ",\n\n";
				$text .= 'Er werd een nieuw paswoord voor je account ingesteld.';
				$text .= "\n\npaswoord: " . $password . "\n";
				$text .= 'login (letscode): ' . $user['letscode'] . "\n\n";
				$text .= 'Inloggen: ' . $url;

				$app['eland.task.mail']->queue(['to' => $user_id, 'subject' => $subj, 'text' => $text]);

				header('Location: ' . $rootpath . 'login.php');
				exit;
			}

			$app['eland.alert']->error('Het reset-token is niet meer geldig.');
			header('Location: pwreset.php');
			exit;
		}
		else
		{
			$app['eland.alert']->error('Te zwak paswoord.');
		}
	}

	$h1 = 'Nieuw paswoord ingeven.';
	$fa = 'key';

	$app['eland.assets']->add('generate_password.js');

	require_once __DIR__ . '/includes/inc_header.php';

	echo '<div class="panel panel-info">';
	echo '<div class="panel-heading">';

	echo '<form method="post" class="form-horizontal" role="form">';

	echo '<div class="form-group">';
	echo '<label for="password" class="col-sm-2 control-label">Nieuw paswoord</label>';
	echo '<div class="col-sm-10 controls">';
	echo '<div class="input-group">';
	echo '<input type="text" class="form-control" id="password" name="password" ';
	echo 'value="' . $password . '" required>';
	echo '<span class="input-group-btn">';
    echo '<button class="btn btn-default" type="button" id="generate">Genereer</button>';
    echo '</span>';
    echo '</div>';
	echo '</div>';
	echo '</div>';

	echo '<input type="submit" class="btn btn-default" value="Bewaar paswoord" name="zend">';
	echo '</form>';

	echo '</div>';
	echo '</div>';

	require_once __DIR__ . '/includes/inc_footer.php';
	exit;
}

if (isset($_POST['zend']))
{
	$email = $_POST['email'];

	if($email)
	{
		$mail_ary = $app['db']->fetchAll('SELECT c.id_user, u.letscode
			FROM contact c, type_contact tc, users u
			WHERE c. value = ?
				AND tc.id = c.id_type_contact
				AND tc.abbrev = \'mail\'
				AND c.id_user = u.id', [$email]);

		if (count($mail_ary) < 2)
		{
			$user_id = $mail_ary[0]['id_user'];
			$letscode = $mail_ary[0]['letscode'];

			if ($user_id)
			{
				$token = substr(hash('sha512', $user_id . $app['eland.this_group']->get_schema() . time() . $email), 0, 12);
				$key = $app['eland.this_group']->get_schema() . '_token_' . $token;
				$app['redis']->set($key, $user_id);
				$app['redis']->expire($key, 3600);

				$url = $app['eland.base_url'] . '/pwreset.php?token=' . $token;

				$subject = 'Paswoord reset link.';

				$text = "Link om je paswoord te resetten :\n\n" . $url . "\n\n";
				$text .= "Let op: deze link blijft slechts 1 uur geldig.\n\n";
				$text .= 'Je letscode is: ' . $letscode . "\n\n";
				$text .= 'Gelieve deze mail te negeren indien je niet zelf deze paswoord ';
				$text .= 'reset aangevraagd hebt op de website, ';

				$app['eland.task.mail']->queue(['to' => $email, 'text' => $text, 'subject' => $subject], 1000);

				$app['eland.alert']->success('Een link om je paswoord te resetten werd naar je mailbox verzonden. Opgelet, deze link blijft slechts één uur geldig.');

				header('Location: login.php');
				exit;
			}
			else
			{
				$app['eland.alert']->error('Mailadres niet bekend');
			}
		}
		else
		{
			$app['eland.alert']->error('Mailadres niet uniek.');
		}
	}
	else
	{
		$app['eland.alert']->error('Geef een mailadres op');
	}
}

$h1 = 'Paswoord vergeten';

require_once __DIR__ . '/includes/inc_header.php';

echo '<p>Met onderstaand formulier stuur je een link om je paswoord te resetten naar je mailbox. </p>';

echo '<div class="panel panel-info">';
echo '<div class="panel-heading">';

echo '<form method="post" class="form-horizontal">';

echo '<div class="form-group">';
echo '<label for="email" class="col-sm-2 control-label">Email</label>';
echo '<div class="col-sm-10">';
echo '<input type="email" class="form-control" id="email" name="email" ';
echo 'value="' . $email . '" required>';
echo '</div>';
echo '</div>';

echo '<input type="submit" class="btn btn-default" value="Reset paswoord" name="zend">';
echo '</form>';

echo '</div>';
echo '</div>';

require_once __DIR__ . '/includes/inc_footer.php';
