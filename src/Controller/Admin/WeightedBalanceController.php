<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WeightedBalanceController extends AbstractController
{
    /**
     * @Route("/weighted-balances", name="weighted_balance")
     */
    public function index(Request $request, string $system, string $access):Response
    {
        return $this->render('weighted_balances/index.html.twig', [
            'controller_name' => 'WeightedBalancesController',
        ]);
    }
}
