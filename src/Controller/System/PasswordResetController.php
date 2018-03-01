<?php

namespace App\Controller\System;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints as Assert;
use util\user;

use App\Form\EmailAddonType;
use App\Form\PasswordResetType;

class PasswordResetController extends AbstractController
{
	/**
	 * @Route("/password-reset", name="password_reset")
	 * @Method({"GET", "POST"})
	 */
	public function form(Request $request, string $schema):Response
	{
		$form = $this->createFormBuilder()
			->add('email', EmailAddonType::class, [
				'constraints' => new Assert\Email(),
			])
			->add('submit', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			$email = strtolower($data['email']);

			$user = $app['db']->fetchAll('select u.*
				from ' . $schema . '.contact c,
					' . $schema . '.type_contact tc,
					' . $schema . '.users u
				where c. value = ?
					and tc.id = c.id_type_contact
					and tc.abbrev = \'mail\'
					and c.id_user = u.id
					and u.status in (1, 2)', [$email]);

			if (count($user) === 1)
			{
				$user = $user[0];

				$app['mail_queue_confirm_link']
					->setTo([$email])
					->setData($user)
					->setTemplate('confirm_password_reset')
					->setRoute('password_reset_new_password')
					->put();

				$this->addFlash('success', 'password_reset.link_send_success', ['%email%' => $email]);

				return $this->redirectToRoute('login', ['schema' => $schema]);
			}

			$this->addFlash('error', 'password_reset.unknown_email_address');
		}

		return $this->render('password_reset/form.html.twig', [
			'form' => $form->createView(),
		]);
	}

	/**
	 * @Route("/password-reset/{token}", name="password_reset_token")
	 * @Method({"GET", "POST"})
	 */
	public function newPassword(Request $request, string $schema, string $token):Response
	{
		if ($request->getMethod() === 'GET')
		{
			$data = $app['mail_validated_confirm_link']->get();
			
			error_log(json_encode($data));
			
			if (!count($data))
			{
				$this->addFlash('error', 'password_reset.confirm_not_found');
				return $this->redirectToRoute('password_reset', ['schema' => $schema]);
			}
		}

		// note: unwanted access is protected by _etoken 

		$form = $this->createFormBuilder()
			->add('password', PasswordResetType::class)
			->add('submit', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();


			$this->addFlash('success', 'password_reset.new_password_success');
			return $this->redirectToRoute('login', ['schema' => $schema]);
		}

		return $this->render('password_reset/new_password.html.twig', ['form' => $form->createView()]);
	}
}

