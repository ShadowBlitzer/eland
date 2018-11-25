<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TransactionSumController extends AbstractController
{
    /**
     * Route("/transaction-sum", name="transaction_sum")
     */
    public function index(Request $request, string $system, string $access):Response
    {
        return $this->render('transactions_sum/index.html.twig', [
            'controller_name' => 'TransactionsSumController',
        ]);
    }
}
