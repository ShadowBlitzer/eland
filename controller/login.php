<?php

namespace controller;

use util\app;
use util\user;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints as Assert;
use form\login_type;

class login
{

	public function form(Request $request, app $app, string $schema)
	{
		$form = $app->build_form(login_type::class)
			->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			return $app->redirect('edit');
		}

		return $app['twig']->render('login/form.html.twig', ['form' => $form->createView()]);
	}
}

