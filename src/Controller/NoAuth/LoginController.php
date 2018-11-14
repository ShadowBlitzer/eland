<?php

namespace App\Controller\NoAuth;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpFoundation\Session\SessionInterface;


class LoginController extends AbstractController
{
    /**
     * @Route("/login", name="login")
     */
    public function index(Request $request, string $system,
        SessionInterface $session):Response
    {








        return $this->render('login/index.html.twig', [
            'controller_name' => 'LoginController',
        ]);
    }
}
