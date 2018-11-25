<?php declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TypeaheadLogTypeController extends AbstractController
{
    /**
     * Route("/typeahead-log-types", name="typeahead_log_type")
     */
    public function index(Request $request, string $system, string $access):Response
    {
        return $this->render('typeahead_log_types/index.html.twig', [
            'controller_name' => 'TypeaheadLogTypesController',
        ]);
    }
}
