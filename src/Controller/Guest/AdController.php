<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdController extends AbstractController
{
    /**
     * @Route("/ad", name="ad")
     */
    public function index(Request $request, string $system, string $access):Response
    {
        return $this->render('ad/index.html.twig', [
            'controller_name' => 'AdController',
        ]);
    }
}
