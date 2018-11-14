<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SupportController extends AbstractController
{
    /**
     * @Route("/support", name="support")
     */
    public function index(Request $request, string $system, string $access):Response
    {
        return $this->render('support/index.html.twig', [
            'controller_name' => 'SupportController',
        ]);
    }
}
