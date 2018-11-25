<?php declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AutoMinLimitController extends AbstractController
{
    /**
     * Route("/auto-min-limit", name="auto_min_limit")
     */
    public function index(
        Request $request,
        string $system,
        string $access
    ):Response
    {


        return $this->render('auto_min/index.html.twig', [
            'controller_name' => 'AutoMinController',
        ]);
    }
}
