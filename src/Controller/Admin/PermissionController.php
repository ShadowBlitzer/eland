<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Translation\TranslatorInterface;

class PermissionController extends AbstractController
{
	/**
	 * @Route("/permissions", name="permission_index")
	 * @Method({"GET", "POST"})
	 */
	public function index(Request $request, string $schema, string $access):Response
	{
		return $this->render('permission/a_index.html.twig', []);
	}
}

