<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PlotAccountTransactionController extends AbstractController
{
    /**
     * Route("/plot-account-transactions", name="plot_account_transaction")
     */
    public function index(Request $request, string $system, string $access):Response
    {
        return $this->render('plot_account_transactions/index.html.twig', [
            'controller_name' => 'PlotAccountTransactionsController',
        ]);
    }
}
