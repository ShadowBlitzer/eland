<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ElasSoapStatusController extends AbstractController
{
    /**
     * Route("/elas-soap-status", name="elas_soap_status")
     */
    public function index(Request $request, string $system, string $access):Response
    {
        return $this->render('elas_soap_status/index.html.twig', [
            'controller_name' => 'ElasSoapStatusController',
        ]);
    }
}
