<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SupportController extends AbstractController
{
    /**
     * @Route("/support", name="support")
     */
    public function index()
    {
        return $this->render('support/index.html.twig', [
            'controller_name' => 'SupportController',
        ]);
    }
}
