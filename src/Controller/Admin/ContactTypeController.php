<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContactTypeController extends AbstractController
{
    /**
     * Route("/contact-type", name="contact_type")
     */
    public function index(Request $request, string $system, string $access):Response
    {
        return $this->render('contact_type/index.html.twig', [
            'controller_name' => 'ContactTypeController',
        ]);
    }
}
