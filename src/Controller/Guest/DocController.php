<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DocController extends AbstractController
{
    /**
     * @Route("/doc", name="doc")
     */
    public function index()
    {
        return $this->render('doc/index.html.twig', [
            'controller_name' => 'DocController',
        ]);
    }
}
