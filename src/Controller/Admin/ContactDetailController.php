<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContactDetailController extends AbstractController
{
    /**
     * @Route("/contact-detail", name="contact_detail")
     */
    public function index(Request $request, string $system, string $access):Response
    {
        return $this->render('contact_detail/index.html.twig', [
            'controller_name' => 'ContactDetailController',
        ]);
    }
}
