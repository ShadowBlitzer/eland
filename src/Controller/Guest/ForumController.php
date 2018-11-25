<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ForumController extends AbstractController
{
    /**
     * Route("/forum", name="forum")
     */
    public function index(Request $request, string $system, string $access):Response
    {
        return $this->render('forum/index.html.twig', [
            'controller_name' => 'ForumController',
        ]);
    }
}
