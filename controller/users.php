<?php

namespace controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;

class users
{
	public function index(Request $request, Application $app)
	{

		return $app['twig']->render('users/list.html.twig', [
			'users'	=> $users,
		]);
	}


	public function show(Request $request, Application $app, int $id)
	{


		return $app['twig']->render('news/extended.html.twig', [
			'user'	=> $user,
		]);
	}


	public function add(Request $request, Application $app)
	{

		$data = [
			'name' => 'Your name',
			'email' => 'Your email',
		];

		$form = $app['form.factory']->createBuilder(FormType::class, $data)
			->add('first_name')
			->add('last_name')
			->add('email', EmailType::class)
			->add('postcode')
			->add('gsm', TextType::class, ['required'	=> false])
			->add('tel', TextType::class, ['required'	=> false])
			->add('send', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			// do something with the data

			// redirect somewhere
			return $app->redirect('...');
		}

		return $app['twig']->render('anon/register.html.twig', [
			'form' => $form->createView(),
		]);
	}

	public function edit(Request $request, Application $app)
	{

	}

}
