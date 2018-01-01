<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;

class NotificationController extends AbstractController
{
	public function index(Request $request, string $schema, string $access)
	{
		return $this->render('notification/' . $access . '_index.html.twig', []);
	}


	public function form_self(Request $request, string $schema, string $access)
	{

		return $this->render('notification/' . $access . '_show.html.twig', []);
	}

	public function show_self(Request $request, string $schema, string $access)
	{

		return $this->render('notification/' . $access . '_show_self.html.twig', []);
	}

	public function add(Request $request, string $schema, string $access)
	{
		return $this->render('notification/' . $access . '_register.html.twig', []);
	}

}
