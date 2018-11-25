<?php declare(strict_types=1);

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TypeaheadActiveAccountController extends AbstractController
{
    /**
     * Route("/typeahead-active-accounts", name="typeahead_active_account")
     */
    public function index(Request $request, string $system, string $access):Response
    {
        return $this->render('typeahead_active_accounts/index.html.twig', [
            'controller_name' => 'TypeaheadActiveAccountsController',
        ]);
    }
}
