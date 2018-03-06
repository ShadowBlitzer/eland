<?php

namespace App\Controller\System;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Translation\TranslatorInterface;

use App\Repository\UserRepository;
use App\Form\Post\PasswordResetType;
use App\Form\Post\RequestPasswordResetType;
use App\Form\Post\PasswordResetFormType;
use App\Mail\MailQueueConfirmLink;
use App\Mail\MailValidatedConfirmLink;

class PasswordResetController extends AbstractController
{
	/**
	 * @Route("/password-reset", name="password_reset")
	 * @Method({"GET", "POST"})
	 */
	public function form(
		MailQueueConfirmLink $mailQueueConfirmLink, TranslatorInterface $translator,
		UserRepository $userRepository,
		Request $request, string $schema):Response
	{
		$form = $this->createForm(RequestPasswordResetType::class)
			->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			$email = strtolower($data['email']);

			$user = $userRepository->getByEmail($email, $schema);

			if (count($user) !== 0 && in_array($user['status'], [1, 2]))
			{
				$mailQueueConfirmLink
					->setTo([$email])
					->setData($user)
					->setTemplate('confirm_password_reset')
					->setRoute('password_reset_new_password')
					->put();

				$this->addFlash('success', $translator->trans('password_reset.link_send_success', ['%email%' => $email]));

				return $this->redirectToRoute('login', ['schema' => $schema]);
			}

			$this->addFlash('error', $translator->trans('password_reset.unknown_email_address', ['%email%' => $email]));
		}

		return $this->render('password_reset/form.html.twig', [
			'form' => $form->createView(),
		]);
	}

	/**
	 * @Route("/password-reset/{token}", name="password_reset_new_password")
	 * @Method({"GET", "POST"})
	 */
	public function newPassword(
		TranslatorInterface $translator, 
		MailValidatedConfirmLink $mailValidatedConfirmLink,
		Request $request, string $schema, string $token):Response
	{
		if ($request->getMethod() === 'GET')
		{
			$data = $mailValidatedConfirmLink->get();
			
			error_log(json_encode($data));
			
			if (!count($data))
			{
				$this->addFlash('error', $translator->trans('password_reset.confirm_not_found'));
				return $this->redirectToRoute('password_reset', ['schema' => $schema]);
			}
		}

		// note: unwanted access is protected by _etoken 

		$form = $this->createForm(PasswordResetFormType::class)
			->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();


			$this->addFlash('success', $translator->trans('password_reset.new_password_success'));
			return $this->redirectToRoute('login', ['schema' => $schema]);
		}

		return $this->render('password_reset/new_password.html.twig', ['form' => $form->createView()]);
	}
}

