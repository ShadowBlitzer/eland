<?php

namespace App\Controller\Index;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

use App\Form\Post\HostingRequestType;
use App\Mail\MailQueueConfirmLink;
use App\Mail\MailValidatedConfirmLink;
use App\Mail\MailQueue;
use App\Mail\MailEnv;

class HostingRequestController extends AbstractController
{
	/**
	 * @Route("/hosting-request",
	 * name="hosting_request",
	 * methods={"GET", "POST"})
	 */
	public function form(MailQueueConfirmLink $mailQueueConfirmLink,
		TranslatorInterface $translator,
		Request $request):Response
	{
		$form = $this->createForm(HostingRequestType::class)
			->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			$mailQueueConfirmLink
				->setTo([$data['email']])
				->setData($data)
				->setTemplate('confirm_hosting_request')
				->setRoute('hosting_request_confirm')
				->put();

			$this->addFlash('info', $translator->trans('hosting_request.confirm_email_info', ['%email%' => $data['email']]));

			return $this->redirectToRoute('main_index');
		}

		return $this->render('hosting_request/form.html.twig', [
			'form' => $form->createView(),
		]);
	}

	/**
	 * @Route("/hosting-request/{token}",
	 * name="hosting_request_confirm",
	 * methods="GET")
	 */
	public function confirm(
		TranslatorInterface $translator,
		MailQueue $mailQueue, MailEnv $mailEnv, MailValidatedConfirmLink $mailValidatedConfirmLink,
		Request $request, string $token):Response
	{
		$data = $mailValidatedConfirmLink->get();

		error_log(json_encode($data));

		if (!count($data))
		{
			$this->addFlash('error', $translator->trans('hosting_request.confirm_not_found'));
			return $this->redirectToRoute('hosting_request');
		}

		$mailQueue->setTemplate('hosting_request')
			->setVars($data)
			->setTo([$mailEnv->getHoster()])
			->setReplyTo([$data['email'] => $data['group_name']])
			->setPriority(900000)
			->put();

		$this->addFlash('success', $translator->trans('hosting_request.success'));

		return $this->redirectToRoute('main_index');
	}
}
