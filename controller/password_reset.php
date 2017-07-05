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

class password_reset
{
	public function form(Request $request, app $app, string $schema)
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
					$data['vars'] = [
						'top'		=> 'password_reset.mail.top',
						'bottom'	=> 'password_reset.mail.bottom',
						'url'		=> $app->url('password_reset_new_password', [
							'token' 	=> $token,
							'schema' 	=> $schema,
						]),
					];
					$data['template'] = 'link';
					$data['schema'] = $schema;
					$data['to'] = $data['email'];
					$data['url'] =

					$data['priority'] = 10000;

					$app['mail']->queue($data);

					return $app->redirect($app->path('login', ['schema' => $schema]));
				}
			}

			$app->err($app->trans('password_reset.unknown_email_address'));
		}

		return $app['twig']->render('password_reset/form.html.twig', ['form' => $form->createView()]);
	}

	/**
	 *
	 */

	public function new_password(Request $request, app $app, string $schema, string $token)
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

		return $app['twig']->render('password_reset/new_password.html.twig', ['form' => $form->createView()]);
	}
}

