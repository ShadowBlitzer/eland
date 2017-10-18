<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;
use form\hosting_request_type;

class hosting_request
{
	public function form(Request $request, app $app)
	{
		$form = $app->build_form(hosting_request_type::class)
			->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();
			
			$app['mail_queue_confirm_link']
				->set_to([$data['email']])
				->set_data($data)
				->set_template('confirm')
				->set_route('hosting_request_confirm')
				->put();

			$app->success('hosting_request.success');

			return $app->redirect($app->path('main_index'));
		}

		return $app->render('hosting_request/form.html.twig', [
			'form' => $form->createView(),
		]);
	}

	public function confirm(Request $request, app $app, string $token)
	{
		$data = $app['mail_validated_confirm_link']->get();

		error_log(json_encode($data));
		
		if (!count($data))
		{
			$app->err($app->trans('hosting_request.confirm_not_found'));
			return $app->redirect($app->path('hosting_request'));
		}

		$app['mail_queue']->set_template('hosting_request')
			->set_vars($data)
			->set_to([$app['mail_env']->get_hoster()])
			->set_reply_to([$data['email'] => $data['group_name']])
			->set_priority(900000)
			->put();

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
/*
		$app[]->set_fail_message()
			->set_fail_route()
			->set_success_message()
			->set_success_route()
			->set_success_mail_template()
			->set_success_mail_template();
*/


		$app->success($app->trans('contact.success'));

		return $app->redirect($app->path('main_index'));
	}






}
