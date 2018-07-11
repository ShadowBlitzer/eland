<?php declare(strict_types=1);

namespace App\Controller\System;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

use App\Form\Post\ContactType;
use App\Mail\MailQueueConfirmLink;
use App\Mail\MailValidatedConfirmLink;
use App\Mail\MailAdmin;
use App\Mail\MailQueue;

class ContactController extends AbstractController
{
	/**
	 * @Route("/contact",
	 * name="contact_form",
	 * methods={"GET", "POST"})
	 */
	public function form(MailQueueConfirmLink $mailQueueConfirmLink,
		TranslatorInterface $translator, Request $request, string $schema):Response
	{
		$form = $this->createForm(ContactType::class)
			->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			$mailQueueConfirmLink
				->setTo([$data['email']])
				->setData($data)
				->setTemplate('confirm_contact')
				->setRoute('contact_confirm')
				->put();

			$this->addFlash('info', $translator->trans('contact.confirm_email_info', ['%email%' => $data['email']]));

			return $this->redirectToRoute('login', ['schema' => $schema]);
		}

		return $this->render('contact/form.html.twig', [
			'form' => $form->createView(),
		]);
	}

	/**
	 * @Route("/contact/{token}",
	 * name="contact_confirm",
	 * methods="GET")
	 */
	public function confirm(MailValidatedConfirmLink $mailValidatedConfirmLink, MailAdmin $mailAdmin,
		MailQueue $mailQueue,
		TranslatorInterface $translator,
		Request $request, string $schema, string $token):Response
	{
		$data = $mailValidatedConfirmLink->get();

		error_log(json_encode($data));

		if (!count($data))
		{
			$this->addFlash('error', $translator->trans('contact.confirm_not_found'));
			return $this->redirectToRoute('login', ['schema' => $schema]);
		}

		$mailQueue->setTemplate('contact_admin')
			->setVars($data)
			->setSchema($schema)
			->setTo($mailAdmin->get($schema))
			->setReplyTo([$data['email']])
			->setPriority(900000)
			->put();

		$this->addFlash('success', $translator->trans('contact.success'));

		return $this->redirectToRoute('login', ['schema' => $schema]);
	}
}
