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
	/**
	 * @Route("/contact", name="contact_form")
	 * @Method({"GET", "POST"})
	 */
	public function form(Request $request, string $schema)
	{
		$form = $this->createForm(contact_type::class)
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

			return $this->redirectToRoute('login', ['schema' => $schema]);
		}

		return $this->render('contact/form.html.twig', [
			'form' => $form->createView(),
		]);
	}

	/**
	 * @Route("/contact/{token}", name="contact_confirm")
	 * @Method("GET")
	 */
	public function confirm(Request $request, string $schema, string $token)
	{
		$data = $app['mail_validated_confirm_link']->get();

		error_log(json_encode($data));
		
		if (!count($data))
		{
			$this->addFlash('error', 'contact.confirm_not_found');
			return $this->redirectToRoute('confirm', ['schema' => $schema]);
		}

		$app['mail_queue']->setTemplate('contact_admin')
			->setVars($data)
			->setSchema($schema)
			->setTo($app['mail_admin']->get($schema))
			->setReplyTo([$data['email']])
			->setPriority(900000)
			->put();

		$this->addFlash('success', 'contact.success');

		return $this->redirectToRoute('login', ['schema' => $schema]);
	}
}

