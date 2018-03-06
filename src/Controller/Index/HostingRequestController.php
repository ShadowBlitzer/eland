<?php

namespace App\Controller\Index;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Translation\TranslatorInterface;

use App\Form\Post\HostingRequestType;
use App\Mail\MailQueueConfirmLink;
use App\Mail\MailValidatedConfirmLink;
use App\Mail\MailQueue;
use App\Mail\MailEnv;

class HostingRequestController extends AbstractController
{
	/**
	 * @Route("/hosting-request", name="hosting_request")
	 * @Method({"GET", "POST"})
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
	 * @Route("/hosting-request/{token}", name="hosting_request_confirm")
	 * @Method({"GET"})
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


		$this->addFlash('success', $translator->trans('hosting_request.success'));

		return $this->redirectToRoute('main_index');
	}
}
