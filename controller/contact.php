<?php

namespace controller;

use util\app;
use util\user;
use Symfony\Component\HttpFoundation\Request;
use form\contact_type;

class contact
{
	public function form(Request $request, app $app, string $schema)
	{
		$form = $app->build_form(contact_type::class)
			->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			$data['ip'] = $_SERVER['REMOTE_ADDR'];
			$data['agent'] = $_SERVER['HTTP_USER_AGENT'];

			$token = $app['token_cache']->set($data);

			$app['mail_confirm']->set_subject(' ')
				->set_data($data)
				->set_template('')
				->set_confirm_route('')
				->queue();



			$data['vars'] = [
				'subject'	=> 'contact.confirm_email.subject',
				'top'		=> 'contact.confirm_email.top',
				'bottom'	=> 'contact.confirm_email.bottom',
				'url'		=> $app->url('contact_confirm',	[
					'token' => $token,
					'schema' => $schema,
				]),
			];

			$data['template'] = 'link';
			$data['to'] = $data['email'];

			$data['token'] = $token;
			$data['schema'] = $schema;

			$redis_key = $schema . '_contact_confirm_' . $token;
			$app['predis']->set($redis_key, json_encode($data));
			$app['predis']->expire($redis_key, 86400);

			$data['priority'] = 10000;

			$app['mail']->queue($data);

			$app->info($app->trans('contact.confirm_email.info'));

			return $app->redirect($app->path('login', ['schema' => $schema]));
		}

		return $app['twig']->render('contact/form.html.twig', [
			'form' => $form->createView(),
		]);
	}

	/**
	 *
	 */

	public function confirm(Request $request, app $app, string $schema, string $token)
	{
		$redis_key = 'contact_confirm_' . $token;
		$data = $app['predis']->get($redis_key);

		if (!$data)
		{
			$app['session']->getFlashBag()->add('error', $app->trans('contact.confirm_not_found'));

			return $app->redirect($app->path('index'));
		}

		$data = json_decode($data, true);

		$email = strtolower($data['email']);

		$app['xdb']->set('email_validated', $email, []);

		$app['mail']->queue([
			'to'		=> getenv('MAIL_ADDRESS_CONTACT'),
			'template'	=> 'contact',
			'subject'	=> $app->trans('contact.mail_subject'),
			'message'	=> $data['message'],
			'browser'	=> $_SERVER['HTTP_USER_AGENT'],
			'ip'		=> $_SERVER['REMOTE_ADDR'],
			'reply_to'	=> $email,
		]);

		$app['predis']->del($redis_key);

		$app->success($app->trans('contact.success'));

		return $app->redirect($app->path('index'));
	}
}

