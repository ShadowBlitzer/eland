<?php declare(strict_types=1);

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ElasSystemLoginController extends AbstractController
{
    /**
     * Route("/elas-system-login", name="elas_system_login")
     */
    public function index(Request $request, string $system, string $access):Response
    {
        return $this->render('elas_system_login/index.html.twig', [
            'controller_name' => 'ElasSystemLoginController',
        ]);
    }
}
