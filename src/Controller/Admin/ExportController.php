<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends AbstractController
{
    /**
     * Route("/export", name="export")
     */
    public function index(Request $request, string $system, string $access):Response
    {
        return $this->render('export/index.html.twig', [
            'controller_name' => 'ExportController',
        ]);
    }
}
