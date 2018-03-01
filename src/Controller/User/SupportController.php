<?php

namespace App\Controller\User;

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
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints as Assert;

class SupportController extends AbstractController
{
	/**
	 * @Route("/support", name="support")
	 * @Method({"GET", "POST"})
	 */
	public function form(Request $request, string $schema, string $access):Response
	{
		$form = $this->createFormBuilder()
			->add('message', TextareaType::class, [
				'constraints' => [
					new Assert\NotBlank(),
					new Assert\Length(['min' => 20, 'max' => 2000]),
				],
			])
			->add('submit', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			$app['mail_queue']->setTemplate('contact_admin')
				->setVars($data)
				->setSchema($schema)
				->setTo($app['mail_admin']->get($schema))
				->setReplyTo([$data['email']])  // to do: get user email
				->setPriority(900000)
				->put();

			$this->addFlash('success', $app->trans('support.success'));

			return $app->redirect($app->path('support', ['schema' => $schema]));
		}

		return $this->render('support/' . $access . '_form.html.twig', [
			'form' => $form->createView(),
		]);
	}
}

