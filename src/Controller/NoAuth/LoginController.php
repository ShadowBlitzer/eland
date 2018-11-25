<?php declare(strict_types=1);

namespace App\Controller\NoAuth;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class LoginController extends AbstractController
{
    /**
     * Route("/login", name="login")
     */
    public function index(
        Request $request,
        string $system,
        SessionInterface $session):Response
    {






        return new Response($rsp);
    }
}
