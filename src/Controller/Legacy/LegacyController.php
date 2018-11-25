<?php declare(strict_types=1);

namespace App\Controller\Legacy;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyController extends AbstractController
{

    /**
     * @Route("/{_locale<nl>}/{system<[a-z][a-z0-9]*>}/{leg_route<[a-z_]{4,}>}",
     *  name="legacy_no_auth")
     */
    public function noAuthIndex(string $system, string $leg_route):Response
    {
        ob_start();
        $rsp = '<html><head></head><body>login</body>';
        include __DIR__ . '/../../Legacy/login.php';
        $rsp = ob_get_clean();
        return new Response($rsp);
    }

    /**
     * @Route("/{_locale<nl>}/{system<[a-z][a-z0-9]*>}/{access<[gua]>}/{leg_route<[a-z_]{4,}>}",
     *  name="legacy")
     */
    public function index(string $system, string $access, string $leg_route):Response
    {

        $rsp = '<html><head></head><body>oufti</body>';

        return new Response($rsp);
    }
}
