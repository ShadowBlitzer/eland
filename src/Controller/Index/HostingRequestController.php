<?php

namespace App\Controller\Index;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use App\Form\Post\HostingRequestType;

class HostingRequestController extends AbstractController
{
	/**
	 * @Route("/hosting-request", name="hosting_request")
	 * @Method({"GET", "POST"})
	 */
	public function form(Request $request)
	{
		$form = $this->createForm(HostingRequestType::class)
			->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();
			
			$app['mail_queue_confirm_link']
				->setTo([$data['email']])
				->setData($data)
				->setTemplate('confirm_hosting_request')
				->setRoute('hosting_request_confirm')
				->put();

			$this->addFlash('info', 'hosting_request.confirm_email_info', ['%email%' => $data['email']]);

			return $this->redirectToRoute('main_index');
		}

		return $this->render('hosting_request/form.html.twig', [
			'form' => $form->createView(),
		]);
	}

	public function confirm(Request $request, string $token)
	{
		$data = $app['mail_validated_confirm_link']->get();

		error_log(json_encode($data));
		
		if (!count($data))
		{
			$this->addFlash('error', 'hosting_request.confirm_not_found');
			return $this->redirectToRoute('hosting_request');
		}

		$app['mail_queue']->setTemplate('hosting_request')
			->setVars($data)
			->setTo([$app['mail_env']->getHoster()])
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


		$this->addFlash('success', 'hosting_request.success');

		return $this->redirectToRoute('main_index');
	}
}
