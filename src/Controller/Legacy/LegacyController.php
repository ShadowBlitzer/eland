<?php declare(strict_types=1);

namespace App\Controller\Legacy;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacyController extends AbstractController
{
    /**
     * @Route("/{_locale}/{system}/{route}", name="legacy_no_auth")
     */
    public function noAuthIndex():Response
    {
        $rsp = '';




        return new Response($rsp);
    }

    /**
     * @Route("/{_locale}/{system}/{access}/{route}", name="legacy_guest")
     */
    public function guestIndex():Response
    {
        $rsp = '';




        return new Response($rsp);
    }

    /**
     * @Route("/{_locale}/{system}/{access}/{route}", name="legacy_user")
     */
    public function userIndex():Response
    {
        $rsp = '';




        return new Response($rsp);
    }
}
