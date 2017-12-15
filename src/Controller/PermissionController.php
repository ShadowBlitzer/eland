<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class PermissionController extends AbstractController
{
	public function index(Request $request, string $schema, string $access)
	{
		return $this->render('permission/a_index.html.twig', []);
	}
}

