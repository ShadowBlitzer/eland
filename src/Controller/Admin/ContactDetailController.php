<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ContactDetailController extends AbstractController
{
    /**
     * @Route("/contact/detail", name="contact_detail")
     */
    public function index()
    {
        return $this->render('contact_detail/index.html.twig', [
            'controller_name' => 'ContactDetailController',
        ]);
    }
}
