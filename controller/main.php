<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;

class main
{
	public function hosting_request(Request $request, app $app)
	{

		$data = [
			'token' => $app['token']->gen(),
		];

		$form = $app->form($data)
			->add('group_name')
			->add('email', EmailType::class)
			->add('message', TextareaType::class)
			->add('token', HiddenType::class)
			->add('send', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			$to = getenv('MAIL_HOSTER_ADDRESS');
			$from = getenv('MAIL_FROM_ADDRESS');

			if (!$to || !$from)
			{
				$app->success('error');
				return $app->redirect($app->path('main_index'));
			}

			$subject = 'Aanvraag hosting: ' . $data['group_name'];
			$text = $data['message'] . "\r\n\r\n\r\n" . 'User Agent: ';
			$text .= $request->headers->get('User-Agent');
			$text .= "\n" . 'token: ' . $data['token'];

			$enc = getenv('SMTP_ENC') ?: 'tls';
			$transport = \Swift_SmtpTransport::newInstance(getenv('SMTP_HOST'), getenv('SMTP_PORT'), $enc)
				->setUsername(getenv('SMTP_USERNAME'))
				->setPassword(getenv('SMTP_PASSWORD'));

			$mailer = \Swift_Mailer::newInstance($transport);

			$mailer->registerPlugin(new \Swift_Plugins_AntiFloodPlugin(100, 30));

			$msg = \Swift_Message::newInstance()
				->setSubject($subject)
				->setBody($text)
				->setTo($to)
				->setFrom($from)
				->setReplyTo($data['email']);

			$mailer->send($msg);

			$app->success('hosting.success');

			return $app->redirect($app->path('main_index'));
		}

		return $app->render('main/hosting_request.html.twig', [
			'form' => $form->createView(),
		]);
	}

	/**
	 *
	 */

	public function index(Request $request, app $app)
	{
		return $app->render('main/index.html.twig');
	}
}
