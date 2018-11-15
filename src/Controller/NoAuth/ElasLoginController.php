<?php

namespace App\Controller\NoAuth;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Predis\Client as Predis;
use Psr\Log\LoggerInterface;
use App\Legacy\Service\Xdb;

class ElasLoginController extends AbstractController
{
    /**
     * @Route("/elas-login/{token}", name="elas_login")
     */
    public function index(
        Request $request,
        string $system,
        string $token,
        Predis $predis,
        SessionInterface $session,
        LoggerInterface $logger,
        Xdb $xdb
    )
    {
        if($apikey = $predis->get($system . '_token_' . $token))
        {
            $logins = $predis->get('logins');
            $logins[$system] = 'elas';
            $predis->set('logins', $logins);

            $param = 'welcome=1&r=guest&u=elas';

            $referrer = $_SERVER['HTTP_REFERER'] ?? 'unknown';

            if ($referrer !== 'unknown')
            {
                // record logins to link the apikeys to domains and groups
                $domain_referrer = strtolower(parse_url($referrer, PHP_URL_HOST));
                $xdb->set('apikey_login', $apikey, ['domain' => $domain_referrer]);
            }

            $logger->info('eLAS guest login using token ' . $token . ' succeeded. referrer: ' . $referrer);

            $glue = (strpos($location, '?') === false) ? '?' : '&';
            header('Location: ' . $location . $glue . $param);
            exit;
        }
        else
        {
            $this->addFlash('error', 'De interlets login is mislukt.');
        }
    }
}
