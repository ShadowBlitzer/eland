<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AutoMinController extends AbstractController
{
    /**
     * @Route("/auto-min", name="auto_min")
     */
    public function index()
    {
        return $this->render('auto_min/index.html.twig', [
            'controller_name' => 'AutoMinController',
        ]);
    }
}
