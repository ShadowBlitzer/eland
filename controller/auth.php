<?php

namespace controller;

use util\app;
use util\user;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints as Assert;

class auth
{

	public function login(Request $request, app $app, string $schema)
	{
		$data = [
			'login'		=> '',
			'password'	=> '',
		];

		$form = $app->form($data)

			->add('login')
			->add('password', PasswordType::class, [
				'constraints' => [new Assert\Length(['min' => 4])],
			])

			->add('submit', SubmitType::class)

			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			return $app->redirect('edit');
		}

		return $app['twig']->render('auth/login.html.twig', ['form' => $form->createView()]);
	}

	/**
	 *
	 */

	public function register(Request $request, app $app, string $schema)
	{
		$data = [
			'first_name'	=> '',
			'last_name'		=> '',
			'email'			=> '',
			'postcode'		=> '',
			'mobile'		=> '',
			'telephone'		=> '',
			'accept'		=> false,
		];

		$form = $app->form($data)

			->add('first_name')
			->add('last_name')
			->add('email', EmailType::class, [
				'constraints' => new Assert\Email(),
			])

			->add('postcode')
			->add('mobile', TextType::class, [
				'required'	=> false,
			])
			->add('telephone', TextType::class, [
				'required'	=> false,
			])
			->add('accept', CheckboxType::class, [
				'constraints' => new Assert\IsTrue(),
			])
			->add('submit', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();


			$user_email = $app['xdb']->get('user_email_' . $data['email']);

			if ($user_username !== '{}')
			{
				$app->err($app->trans('register.username_already_registered'));
			}
			else if ($user_email !== '{}')
			{
				$app->err($app->trans('register.email_already_registered'));
			}
			else
			{
				$data['subject'] = 'register_confirm.subject';
				$data['top'] = 'register_confirm.top';
				$data['bottom'] = 'mail_register_confirm.bottom';
				$data['template'] = 'link';
				$data['to'] = $data['email'] = strtolower($data['email']);

				$token = $app['token']->set_length(20)->gen();

				$data['url'] = $app->url('register_confirm', ['token' => $token]);
				$data['token'] = $token;

				$redis_key = 'register_confirm_' . $token;
				$app['predis']->set($redis_key, json_encode($data));
				$app['predis']->expire($redis_key, 14400);

				$app['mail']->queue_priority($data);

				return $app->redirect($app->path('register_sent'));
			}
		}

		return $app['twig']->render('auth/register.html.twig', ['form' => $form->createView()]);
	}

	/**
	 *
	 */

	public function register_sent(Request $request, app $app)
	{
		return $app['twig']->render('page/panel_info.html.twig', [
			'subject'	=> 'register_sent.subject',
			'text'		=> 'register_sent.text',
		]);
	}

	/**
	 *
	 */

	public function register_confirm(Request $request, app $app, string $schema, string $token)
	{
		$redis_key = 'register_confirm_' . $token;
		$data = $app['predis']->get($redis_key);

		if (!$data)
		{
			return $app['twig']->render('page/panel_danger.html.twig', [
				'subject'	=> 'register_confirm.not_found.subject',
				'text'		=> 'register_confirm.not_found.text',
			]);
		}

		$data = json_decode($data, true);

		$email = strtolower($data['email']);

		$user_email = $app['xdb']->get('user_email_' . $email);

		if ($user_email !== '{}')
		{
			return $app['twig']->render('page/panel_danger.html.twig', [
				'subject'	=> 'register_confirm.email_already_exists.subject',
				'text'		=> 'register_confirm.email_already_exists.text',
			]);
		}

		$username = strtolower($data['username']);

		$user_username = $app['xdb']->get('username_' . $username);

		if ($user_username !== '{}')
		{
			return $app['twig']->render('page/panel_danger.html.twig', [
				'subject'	=> 'register_confirm.username_already_exists.subject',
				'text'		=> 'register_confirm.username_already_exists.text',
			]);
		}

		do
		{
			$uuid = $app['uuid']->gen();
			$exists = $app['xdb']->exists($uuid);
		}
		while ($exists);

		$password = $data['password'];

		$user = new user('', '', '', []);
		$password = $app->encodePassword($user, $password);

		$app['xdb']->set('user_' . $uuid, [
			'email' 	=> $email,
			'username'	=> $username,
			'roles'		=> ['ROLE_USER'],
			'password'	=> $password,
		]);

		$app['xdb']->set('user_email_' . $email, [
			'uuid'		=> $uuid,
		]);

		$app['xdb']->set('username_' . $username, [
			'uuid' 	=> $uuid,
		]);

		$app['predis']->del($redis_key);

		return $app['twig']->render('page/panel_success.html.twig', [
			'subject'	=> 'register_confirm.success.subject',
			'text'		=> 'register_confirm.success.text',
		]);
	}

	/**
	 *
	 */

	public function password_reset(Request $request, app $app, string $schema)
	{
		$data = [
			'email'	=> '',
		];

		$form = $app->form($data)
			->add('email', EmailType::class, [
				'constraints' => new Assert\Email(),
			])

			->add('submit', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			$email = strtolower($data['email']);

			$user = $app['db']->fetchAll('select u.*
				from ' . $schema . '.contact c,
					' . $schema . '.type_contact tc,
					' . $schema . '.users u
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
					$token = $app['token']->gen();
					$key = $schema . '_token_' . $token;

					$app['predis']->set($key, json_encode(['uid' => $user['id'], 'email' => $email]));
					$app['predis']->expire($key, 3600);

					$data['subject'] = 'password_reset.mail.subject';
					$data['top'] = 'password_reset.mail.top';
					$data['bottom'] = 'password_reset.mail.bottom';
					$data['template'] = 'link';
					$data['to'] = $data['email'];
					$data['url'] = $app->url('password_reset_confirm', [
						'token' 	=> $token,
						'schema' 	=> $schema,
					]);

					$data['priority'] = 1000;

					$app['mail']->queue($data);

					return $app->redirect($app->path('login', ['schema' => $schema]));
				}
			}

			$app['session']->getFlashBag()->add('error', $app->trans('password_reset.unknown_email_address'));
		}

		return $app['twig']->render('auth/password_reset.html.twig', ['form' => $form->createView()]);
	}

	/**
	 *
	 */

	public function password_reset_sent(Request $request, app $app)
	{
		return $app['twig']->render('page/panel_info.html.twig', [
			'subject'	=> 'password_reset_sent.subject',
			'text'		=> 'password_reset_sent.text',
		]);
	}

	/**
	 *
	 */

	public function _password_reset(Request $request, app $app, $token)
	{
		$redis_key = 'password_reset_' . $token;
		$data = $app['predis']->get($redis_key);

		if (!$data)
		{
			return $app['twig']->render('page/panel_danger.html.twig', [
				'subject'	=> 'new_password.not_found.subject',
				'text'		=> 'new_password.not_found.text',
			]);
		}

		$data = json_decode($data, true);

		$email = strtolower($data['email']);

		$user_email = $app['xdb']->get('user_email_' . $email);

		if ($user_email === '{}')
		{
			return $app['twig']->render('page/panel_danger.html.twig', [
				'subject'	=> 'new_password.unknown_email.subject',
				'text'		=> 'new_password.unknown_email.text',
			]);
		}

		$uuid = json_decode($user_email, true)['uuid'];

		$user = $app['xdb']->get('user_' . $uuid);

		if ($user === '{}')
		{
			return $app['twig']->render('page/panel_danger.html.twig', [
				'subject'	=> 'new_password.unknown_user.subject',
				'text'		=> 'new_password.unknown_user.text',
			]);
		}

		$form_data = [
			'password'		=> '',
		];

		$form = $app->form($form_data)
			->add('password', PasswordType::class, [
				'constraints' => [new Assert\NotBlank(), new Assert\Length(['min' => 6])],
			])
			->add('submit', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$user = json_decode($user, true);

			$form_data = $form->getData();

			$user_pwd = new user('', '', '', []);
			$user['password'] = $app->encodePassword($user_pwd, $form_data['password']);

			$app['xdb']->set('user_' . $uuid, $user);
			$app['predis']->del($redis_key);

			$app['session']->getFlashBag()->add('success', $app->trans('password_reset.success'));

			return $app->redirect($app->path('login'));
		}

		return $app['twig']->render('auth/new_password.html.twig', ['form' => $form->createView()]);
	}

	/**
	 *
	 */

	public function _new_password(Request $request, app $app, $token)
	{
		$redis_key = 'password_reset_' . $token;
		$data = $app['predis']->get($redis_key);

		if (!$data)
		{
			return $app['twig']->render('page/panel_danger.html.twig', [
				'subject'	=> 'new_password.not_found.subject',
				'text'		=> 'new_password.not_found.text',
			]);
		}

		$data = json_decode($data, true);

		$email = strtolower($data['email']);

		$user = $app['xdb']->get('user_auth_' . $email);

		if ($user === '{}')
		{
			return $app['twig']->render('page/panel_danger.html.twig', [
				'subject'	=> 'register_confirm.not_found.subject',
				'text'		=> 'register_confirm.not_found.text',
			]);

		}

		$data = [
			'password'	=> '',
		];

		$form = $app->form($data)
			->add('password', PasswordType::class)
			->add('submit', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			return $app->redirect('/edit');
		}

		return $app['twig']->render('auth/new_password.html.twig', ['form' => $form->createView()]);
	}

	/**
	 *
	 */

	public function post(Request $request, app $app)
	{
		$email = $request->get('email');

		$errors = $app['validator']->validate($email, new Assert\Email());

		if ($errors > 0)
		{
			$app['monolog']->info('unvalid email: ' . $email . ' - ' . (string) $errors);

			return $app->json(['notice' => $app->trans('notice.unvalid_email')]);
		}

		$editors = $app['xdb']->get('project_editors');

		if (!isset($editors[$email]))
		{
			$app['monolog']->info('no access for email: ' . $email);

			return $app->json(['notice' => $app->trans('notice.no_access_email')]);
		}

		$token = $app['token']->set_length(12)->gen();

		$key = 'login_token_' . $token;

		$app['predis']->set($key, $email);
		$app['predis']->expire($key, 14400); // 4 hours;

		$host = $request->getHost();

		$app['mail']->queue([
			'template'	=> 'login_token',
			'to'		=> $email,
			'url'		=> $host . '/' . $token,
		]);

		return $app->json(['notice' => $app->trans('notice.token_send_email')]);
	}

	/**
	 *
	 */

	public function token(Request $request, app $app, $token)
	{
		$edit_login = $app['xdb']->get('edit_login_' . $token);

		$app['session']->set('edit_login', $edit_login);

		return $app->redirect('edit');
	}
}

