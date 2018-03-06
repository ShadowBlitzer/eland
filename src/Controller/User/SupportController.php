<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints as Assert;

use App\Form\Post\SupportType;
use App\Mail\MailQueue;
use App\Mail\MailAdmin;

class SupportController extends AbstractController
{
	/**
	 * @Route("/support", name="support")
	 * @Method({"GET", "POST"})
	 */
	public function form(MailQueue $mailQueue, MailAdmin $mailAdmin, Request $request, string $schema, string $access):Response
	{
		$form = $this->createForm(SupportType::class);
		$form->handleRequest($request);		

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			$mailQueue->setTemplate('contact_admin')
				->setVars($data)
				->setSchema($schema)
				->setTo($mailAdmin->get($schema))
				->setReplyTo([$data['email']])  // to do: get user email
				->setPriority(900000)
				->put();

			$this->addFlash('success', $app->trans('support.success'));

			return $this->redirectToRoute('support', ['schema' => $schema, 'access' => $access]);
		}

		return $this->render('support/' . $access . '_form.html.twig', [
			'form' => $form->createView(),
		]);
	}
}

