<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

use App\Form\Post\SupportType;
use App\Mail\MailQueue;
use App\Mail\MailAdmin;

class SupportController extends AbstractController
{
	/**
	 * @Route("/support", name="support", methods={"GET", "POST"})
	 */
	public function form(MailQueue $mailQueue, MailAdmin $mailAdmin,
		TranslatorInterface $translator,
		Request $request, string $schema, string $access):Response
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

			$this->addFlash('success', $translator->trans('support.success'));

			return $this->redirectToRoute('support', ['schema' => $schema, 'access' => $access]);
		}

		return $this->render('support/' . $access . '_form.html.twig', [
			'form' => $form->createView(),
		]);
	}
}
