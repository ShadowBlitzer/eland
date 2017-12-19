<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
	public function form(Request $request, string $schema, string $access)
	{
		$form = $app->form()
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

			$app['mail_queue']->set_template('contact_admin')
				->set_vars($data)
				->set_schema($schema)
				->set_to($app['mail_admin']->get($schema))
				->set_reply_to([$data['email']])  // to do: get user email
				->set_priority(900000)
				->put();

			$this->addFlash('success', $app->trans('support.success'));

			return $app->redirect($app->path('support', ['schema' => $schema]));
		}

		return $this->render('support/' . $access . '_form.html.twig', [
			'form' => $form->createView(),
		]);
	}
}

