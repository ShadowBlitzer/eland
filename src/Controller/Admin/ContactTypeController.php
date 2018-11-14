<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ContactTypeController extends AbstractController
{
    /**
     * @Route("/contact/type", name="contact_type")
     */
    public function index()
    {
        return $this->render('contact_type/index.html.twig', [
            'controller_name' => 'ContactTypeController',
        ]);
    }
}
