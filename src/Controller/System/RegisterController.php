<?php declare(strict_types=1);

namespace App\Controller\System;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

use App\Form\Post\RegisterType;
use App\Mail\MailQueueConfirmLink;
use App\Mail\MailValidatedConfirmLink;
use App\Mail\MailAdmin;

class RegisterController extends AbstractController
{
	/**
	 * @Route("/register",
	 * name="register",
	 * methods={"GET", "POST"})
	 */
	public function form(MailQueueConfirmLink $mailQueueConfirmLink,
		TranslatorInterface $translator,
		Request $request, string $schema):Response
	{
		$form = $this->createForm(RegisterType::class)
			->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			$mailQueueConfirmLink
				->setTo([$data['email']])
				->setData($data)
				->setTemplate('confirm_register')
				->setRoute('register_confirm')
				->put();

			$this->addFlash('info', $translator->trans('register.confirm_email_info', ['%email%' => $data['email']]));

			return $this->redirectToRoute('login', ['schema' => $schema]);
		}

		return $this->render('register/form.html.twig', ['form' => $form->createView()]);
	}

	/**
	 * @Route("/register/{token}",
	 * name="register_confirm",
	 * methods="GET")
	 */
	public function confirm(MailValidatedConfirmLink $mailValidatedConfirmLink,
		TranslatorInterface $translator,
		Request $request, string $schema, string $token):Response
	{
		$data = $mailValidatedConfirmLink->get();

		error_log(json_encode($data));

		if (!count($data))
		{
			$this->addFlash('error', $translator->trans('register.confirm_not_found'));
			return $this->redirectToRoute('register', ['schema' => $schema]);
		}

		// TO DO: process data

		$email = strtolower($data['email']);

		$userEmail = $app['xdb']->get('user_email_' . $email);

		if ($userEmail !== '{}')
		{
			return $this->render('page/panel_danger.html.twig', [
				'subject'	=> 'register_confirm.email_already_exists.subject',
				'text'		=> 'register_confirm.email_already_exists.text',
			]);
		}

		$username = strtolower($data['username']);

		$userUsername = $app['xdb']->get('username_' . $username);

		if ($userUsername !== '{}')
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
