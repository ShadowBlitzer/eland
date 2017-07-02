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

class register
{
	public function form(Request $request, app $app, string $schema)
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

				$app['mail']->queue($data);

				return $app->redirect($app->path('register_sent'));
			}
		}

		return $app['twig']->render('auth/register.html.twig', ['form' => $form->createView()]);
	}

	/**
	 *
	 */

	public function confirm(Request $request, app $app, string $schema, string $token)
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
}

