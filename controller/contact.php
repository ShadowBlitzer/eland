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

			$app['mail_queue_confirm_link']
				->set_to([$data['email']])
				->set_data($data)
				->set_template('confirm')
				->set_route('contact_confirm')
				->put();

			$app->info($app->trans('contact.confirm_email.info'));

			return $app->redirect($app->path('login', ['schema' => $schema]));
		}

		return $app['twig']->render('contact/form.html.twig', [
			'form' => $form->createView(),
		]);
	}

	public function confirm(Request $request, app $app, string $schema, string $token)
	{
		$data = $app['mail_validated_confirm_link']->get();

		error_log(json_encode($data));
		
		if (!count($data))
		{
			$app->err($app->trans('contact.confirm_not_found'));
			return $app->redirect($app->path('confirm', ['schema' => $schema]));
		}

		$app['mail_queue']->set_template('contact_admin')
			->set_vars($data)
			->set_schema($schema)
			->set_to($app['mail_admin']->get($schema))
			->set_reply_to([$data['email']])
			->set_priority(900000)
			->put();

		$app->success($app->trans('contact.success'));

		return $app->redirect($app->path('login', ['schema' => $schema]));
	}
}

