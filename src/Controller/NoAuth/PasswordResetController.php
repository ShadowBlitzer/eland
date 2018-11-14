<?php

namespace App\Controller\NoAuth;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class PasswordResetController extends AbstractController
{
    /**
     * @Route("/password/reset", name="password_reset")
     */
    public function index()
    {
        return $this->render('password_reset/index.html.twig', [
            'controller_name' => 'PasswordResetController',
        ]);
    }
}
