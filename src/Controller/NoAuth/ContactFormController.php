<?php

namespace App\Controller\NoAuth;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ContactFormController extends AbstractController
{
    /**
     * @Route("/contact-form", name="contact_form")
     */
    public function index()
    {
        return $this->render('contact_form/index.html.twig', [
            'controller_name' => 'ContactFormController',
        ]);
    }
}
