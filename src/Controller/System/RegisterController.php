<?php

namespace App\Controller\System;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints as Assert;
use form\email_addon_type;
use form\addon_type;
use form\register_type;

class RegisterController extends AbstractController
{
	/**
	 * @Route("/register", name="register")
	 * @Method({"GET", "POST"})
	 */
	public function form(Request $request, string $schema)
	{
		$form = $this->createForm(register_type::class)
			->handleRequest($request);

		if ($form->isSubmitted && $form->isValid())
		{
			$data = $form->getData();

			$app['mail_queue_confirm_link']
				->setTo([$data['email']])
				->setData($data)
				->setTemplate('confirm_register')
				->setRoute('register_confirm')
				->put();

			$this->addFlash('info', 'register.confirm_email_info', ['%email%' => $data['email']]);

			return $this->redirectToRoute('login', ['schema' => $schema]);
		}

		return $this->render('register/form.html.twig', ['form' => $form->createView()]);
	}

	/**
	 * @Route("/register/{token}", name="register_confirm")
	 * @Method("GET")
	 */
	public function confirm(Request $request, string $schema, string $token)
	{
		$data = $app['mail_validated_confirm_link']->get();
	
		error_log(json_encode($data));
		
		if (!count($data))
		{
			$this->addFlash('error', 'register.confirm_not_found');
			return $this->redirectToRoute('register', ['schema' => $schema]);
		}

		// TO DO: process data

		$email = strtolower($data['email']);

		$user_email = $app['xdb']->get('user_email_' . $email);

		if ($user_email !== '{}')
		{
			return $this->render('page/panel_danger.html.twig', [
				'subject'	=> 'register_confirm.email_already_exists.subject',
				'text'		=> 'register_confirm.email_already_exists.text',
			]);
		}

		$username = strtolower($data['username']);

		$user_username = $app['xdb']->get('username_' . $username);

		if ($user_username !== '{}')
		{
			return $this->render('page/panel_danger.html.twig', [
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

		return $this->render('page/panel_success.html.twig', [
			'subject'	=> 'register_confirm.success.subject',
			'text'		=> 'register_confirm.success.text',
		]);
	}
}

