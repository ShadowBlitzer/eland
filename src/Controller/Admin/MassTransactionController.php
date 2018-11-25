<?php declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MassTransactionController extends AbstractController
{
    /**
     * Route("/mass-transaction", name="mass_transaction")
     */
    public function index(Request $request, string $system, string $access):Response
    {
        return $this->render('mass_transaction/index.html.twig', [
            'controller_name' => 'MassTransactionController',
        ]);
    }
}
