<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;

class news
{
	public function index(Request $request, app $app, string $schema, string $access)
	{
		return $app['twig']->render('news/' . $access . '_index.html.twig', [
			'news'	=> $news,
		]);
	}

	public function show(Request $request, app $app, string $schema, string $access, array $news)
	{

		return $app['twig']->render('news/' . $access . '_show.html.twig', [
			'news'	=> $news,
		]);
	}

	public function add(Request $request, app $app, string $schema, string $access)
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
			->add('zend', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			// do something with the data

			// redirect somewhere
			return $app->redirect('...');
		}

		return $app['twig']->render('news/' . $access . '_add.html.twig', [
			'form' => $form->createView(),
		]);
	}

	public function edit(Request $request, app $app, string $schema, string $access, array $news)
	{
		return $app['twig']->render('news/' . $access . '_edit.html.twig', [
			'form' => $form->createView(),
		]);
	}

	public function approve(Request $request, app $app, string $schema, string $access, array $news)
	{
		return $app['twig']->render('news/' . $access . '_approve.html.twig', [
			'form' => $form->createView(),
		]);
	}



}
