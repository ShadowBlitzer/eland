<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StatusController extends AbstractController
{
    /**
     * @Route("/status", name="status")
     */
    public function index(Request $request, string $system, string $access):Response
    {
        return $this->render('status/index.html.twig', [
            'controller_name' => 'StatusController',
        ]);
    }
}
