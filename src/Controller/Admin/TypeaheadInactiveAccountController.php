<?php declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TypeaheadInactiveAccountController extends AbstractController
{
    /**
     * Route("/typeahead-inactive-accounts", name="typeahead_inactive_account")
     */
    public function index(Request $request, string $system, string $access):Response
    {
        return $this->render('typeahead_in_active_accounts/index.html.twig', [
            'controller_name' => 'TypeaheadInActiveAccountsController',
        ]);
    }
}
