<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ExportController extends AbstractController
{
    /**
     * @Route("/export", name="export")
     */
    public function index()
    {
        return $this->render('export/index.html.twig', [
            'controller_name' => 'ExportController',
        ]);
    }
}
