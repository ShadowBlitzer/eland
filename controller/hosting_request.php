<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;
use form\hosting_request_type;

class hosting_request
{
	public function form(Request $request, app $app)
	{

		if ($request->getMethod() === 'GET')
		{
			$token = $app['token']->gen();

			$redis_key = 'hosting_request_' . $token;

			$app['predis']->set($redis_key, '1');
			$app['predis']->expire($redis_key, 14400);

			$data = [
				'token' => $token,
			];
		}
		else
		{
			$data = [];
		}

		$form = $app->build_form(hosting_request_type::class, $data)
		->handleRequest($request);

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			$errors = [];

			if (!$app['predis']->get('hosting_request_' . $data['token']))
			{
				$errors[] = 'error.form_token_expired';
			}

			$to = getenv('MAIL_HOSTER_ADDRESS');
			$from = getenv('MAIL_FROM_ADDRESS');

			if (!$to || !$from)
			{
				$errors[] = 'error.internal_configuration';
			}

			if (!count($errors))
			{
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
					->setSubject($app->trans('hosting_request.mail.subject', ['%group_name%' => $data['group_name']]))
					->setBody($text)
					->setTo($to)
					->setFrom($from)
					->setReplyTo($data['email']);

				$mailer->send($msg);

				$app->success('hosting_request.success');

				return $app->redirect($app->path('main_index'));
			}

			$app->err($errors);
		}

		return $app->render('hosting_request/form.html.twig', [
			'form' => $form->createView(),
		]);
	}
}
