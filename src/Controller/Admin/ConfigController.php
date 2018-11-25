<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfigController extends AbstractController
{
    /**
     * Route("/config", name="config")
     */
    public function index(Request $request, string $system, string $access):Response
    {
        return $this->render('config/index.html.twig', [
            'controller_name' => 'ConfigController',
        ]);
    }
}
