<?php

namespace App\Controller\System;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use util\user;
use form\contact_type;

class ContactController extends AbstractController
{
	public function form(Request $request, string $schema)
	{
		$form = $app->build_form(contact_type::class)
			->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			$app['mail_queue_confirm_link']
				->set_to([$data['email']])
				->set_data($data)
				->set_template('confirm_contact')
				->set_route('contact_confirm')
				->put();

			$this->addFlash('info', 'contact.confirm_email_info', ['%email%' => $data['email']]);

			return $app->reroute('login', ['schema' => $schema]);
		}

		return $this->render('contact/form.html.twig', [
			'form' => $form->createView(),
		]);
	}

	public function confirm(Request $request, string $schema, string $token)
	{
		$data = $app['mail_validated_confirm_link']->get();

		error_log(json_encode($data));
		
		if (!count($data))
		{
			$this->addFlash('error', 'contact.confirm_not_found');
			return $app->reroute('confirm', ['schema' => $schema]);
		}

		$app['mail_queue']->set_template('contact_admin')
			->set_vars($data)
			->set_schema($schema)
			->set_to($app['mail_admin']->get($schema))
			->set_reply_to([$data['email']])
			->set_priority(900000)
			->put();

		$this->addFlash('success', 'contact.success');

		return $app->reroute('login', ['schema' => $schema]);
	}
}

