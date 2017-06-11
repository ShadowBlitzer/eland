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

		return $app->render('main/hosting_request.html.twig', [
			'form' => $form->createView(),
		]);
	}

	/**
	 *
	 */

	public function monitor(Request $request, app $app)
	{
		try
		{
			$app['db']->fetchColumn('select max(id) from xdb.events');
		}
		catch(Exception $e)
		{
			http_response_code(503);
			echo 'db fail';
			error_log('db_fail: ' . $e->getMessage());
			throw $e;
			exit;
		}

		try
		{
			$app['predis']->incr('eland_monitor');
			$app['predis']->expire('eland_monitor', 400);

			$monitor_count = $app['predis']->get('eland_monitor');

			if ($monitor_count > 2)
			{
				$monitor_service_worker = $app['predis']->get('monitor_service_worker');

				if (!$monitor_service_worker)
				{
					http_response_code(503);
					echo 'web service is up but service worker is down';
					exit;
				}
			}
		}
		catch(Exception $e)
		{
			echo 'redis fail';
			error_log('redis_fail: ' . $e->getMessage());
			throw $e;
			exit;
		}

		exit;

	}

	/**
	 *
	 */

	public function index(Request $request, app $app)
	{
		return $app->render('main/index.html.twig');
	}
}
