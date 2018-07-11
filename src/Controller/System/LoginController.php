<?php declare(strict_types=1);

namespace App\Controller\System;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

use App\Form\Post\LoginType;

class LoginController extends AbstractController
{
	/**
	 * @Route("/login",
	 * name="login",
	 * methods={"GET", "POST"})
	 */
	public function form(TranslatorInterface $translator, Request $request, string $schema):Response
	{
		$form = $this->createForm(LoginType::class)
			->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();



			return $this->redirectToRoute('login', ['schema' => $schema]);
		}

		return $this->render('login/form.html.twig', ['form' => $form->createView()]);
	}
}
