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

			$app['mail_confirm_link']
				->set_data($data)
				->set_template('confirm')
				->set_route('contact_confirm')
				->queue();

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
		$data = $app['mail_confirm_link']->get();

		error_log(json_encode($data));
		
		if (!count($data))
		{
			$app->err($app->trans('contact.confirm_not_found'));
			return $app->redirect($app->path('confirm', ['schema' => $schema]));
		}

		$email = strtolower($data['email']);

		$app['xdb']->set('email_validated', $email, [], $schema);

/*		
		$app['mail']->queue([
			'to'		=> getenv('MAIL_ADDRESS_CONTACT'),
			'template'	=> 'contact',
			'subject'	=> $app->trans('contact.mail_subject'),
			'message'	=> $data['message'],
			'browser'	=> $_SERVER['HTTP_USER_AGENT'],
			'ip'		=> $_SERVER['REMOTE_ADDR'],
			'reply_to'	=> $email,
		]);
*/

		$app->success($app->trans('contact.success'));

		return $app->redirect($app->path('login', ['schema' => $schema]));
	}
}

