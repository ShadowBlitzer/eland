<?php declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TypeaheadDocMapNameController extends AbstractController
{
    /**
     * Route("/typeahead-doc-map-names", name="typeahead_doc_map_name")
     */
    public function index(Request $request, string $system, string $access):Response
    {
        return $this->render('typeahead_doc_map_names/index.html.twig', [
            'controller_name' => 'TypeaheadDocMapNamesController',
        ]);
    }
}
