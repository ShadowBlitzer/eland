<?php declare(strict_types=1);

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Predis\Client as Predis;
use Doctrine\DBAL\Driver\Connection as Db;
use Psr\Log\LoggerInterface;
use App\Legacy\service\xdb;

class NewsController extends AbstractController
{
    /**
     * Route("/news", name="news")
     */
    public function index(Request $request, string $system, string $access):Response
    {
        return $this->render('news/index.html.twig', [
            'controller_name' => 'NewsController',
        ]);
    }
}
