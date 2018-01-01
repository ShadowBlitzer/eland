<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class PermissionController extends AbstractController
{
	/**
	 * @Route("/permissions", name="permission_index")
	 * @Method({"GET", "POST"})
	 */
	public function index(Request $request, string $schema, string $access)
	{
		return $this->render('permission/a_index.html.twig', []);
	}
}

