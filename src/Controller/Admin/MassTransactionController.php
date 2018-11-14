<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class MassTransactionController extends AbstractController
{
    /**
     * @Route("/mass-transaction", name="mass_transaction")
     */
    public function index()
    {
        return $this->render('mass_transaction/index.html.twig', [
            'controller_name' => 'MassTransactionController',
        ]);
    }
}
