<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DocController extends AbstractController
{
    /**
     * Route("/doc", name="doc")
     */
    public function index(Request $request, string $system, string $access):Response
    {
        return $this->render('doc/index.html.twig', [
            'controller_name' => 'DocController',
        ]);
    }
}
