<?php

namespace controller;

use util\app;
use util\user;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints as Assert;
use form\email_addon_type;
use form\addon_type;
use form\register_type;

class register
{
	public function form(Request $request, app $app, string $schema)
	{
		$form = $app->build_form(register_type::class)
			->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			$app['mail_queue_confirm_link']
				->set_to([$data['email']])
				->set_data($data)
				->set_template('confirm_register')
				->set_route('register_confirm')
				->put();

			$app->info($app->trans('register.confirm_email_info', ['%email%' => $data['email']]));

			return $app->redirect($app->path('login', ['schema' => $schema]));
		}

		return $app['twig']->render('register/form.html.twig', ['form' => $form->createView()]);
	}

	/**
	 *
	 */

	public function confirm(Request $request, app $app, string $schema, string $token)
	{
		$data = $app['mail_validated_confirm_link']->get();
	
		error_log(json_encode($data));
		
		if (!count($data))
		{
			$app->err($app->trans('register.confirm_not_found'));
			return $app->redirect($app->path('register', ['schema' => $schema]));
		}

		// TO DO: process data

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
}

