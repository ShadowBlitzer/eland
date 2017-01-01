<?php

$page_access = 'anonymous';

require_once __DIR__ . '/includes/web.php';

$token = $_GET['token'] ?? false;

if ($token)
{
	$data = $app['redis']->get($app['eland.this_group']->get_schema() . '_token_' . $token);
	$data = json_decode($data, true);

	$user_id = $data['user_id'];
	$email = $data['email'];

	if ($_POST['zend'])
	{
		$password = $_POST['password'];

		if (!($app['eland.password_strength']->get($password) < 50))
		{
			if ($user_id)
			{
				$app['db']->update('users', ['password' => hash('sha512', $password)], ['id' => $user_id]);
				$user = readuser($user_id, true);
				$app['eland.alert']->success('Paswoord opgeslagen.');

				$vars = [
					'group'		=> [
						'name'		=> readconfigfromdb('systemname'),
						'tag'		=> readconfigfromdb('systemtag'),
						'currency'	=> readconfigfromdb('currency'),
						'support'	=> readconfigfromdb('support'),
					],
					'password'	=> $password,
					'user'		=> $user,
					'url_login'	=> $app['eland.base_url'] . '/login.php?login=' . $user['letscode'],
				];

				$app['eland.queue.mail']->queue([
					'to' 		=> $user_id,
					'template'	=> 'password_reset',
					'vars'		=> $vars,
				]);

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

	if ($email)
	{
		$ev_data = [
			'token'			=> $token,
			'user_id'		=> $user_id,
			'script_name'	=> 'pwreset',
			'email'			=> strtolower($email),
		];

		$app['eland.xdb']->set('email_validated', $email, $ev_data);
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
	$email = trim($_POST['email']);

	if($email)
	{
		$user = $app['db']->fetchAll('select u.*
			from contact c, type_contact tc, users u
			where c. value = ?
				and tc.id = c.id_type_contact
				and tc.abbrev = \'mail\'
				and c.id_user = u.id
				and u.status in (1, 2)', [$email]);

		if (count($user) < 2)
		{
			$user = $user[0];

			if ($user['id'])
			{
				$token = substr(hash('sha512', $user['id'] . $app['eland.this_group']->get_schema() . time() . $email), 0, 12);
				$key = $app['eland.this_group']->get_schema() . '_token_' . $token;

				$app['redis']->set($key, json_encode(['user_id' => $user['id'], 'email' => $email]));
				$app['redis']->expire($key, 3600);

				$vars = [
					'group'		=> [
						'name'		=> readconfigfromdb('systemname'),
						'tag'		=> readconfigfromdb('systemtag'),
						'currency'	=> readconfigfromdb('currency'),
						'support'	=> readconfigfromdb('support'),
					],
					'token_url'	=> $app['eland.base_url'] . '/pwreset.php?token=' . $token,
					'user'		=> $user,
					'url_login'	=> $app['eland.base_url'] . '/login.php?login=' . $user['letscode'],
				];

				$app['eland.queue.mail']->queue([
					'to' 		=> $email,
					'template'	=> 'password_reset_confirm',
					'vars'		=> $vars,
				], 1000);

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
