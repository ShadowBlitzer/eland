<?php

namespace controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;

class page
{
	public function token(Request $request, Application $app, $token)
	{

		if($apikey = $app['redis']->get($app['eland.this_group']->get_schema() . '_token_' . $token))
		{
			$logins = $app['session']->get('logins');
			$logins[$app['eland.this_group']->get_schema()] = 'elas';
			$app['session']->set('logins', $logins);

			$param = 'welcome=1&r=guest&u=elas';

			$referrer = $_SERVER['HTTP_REFERER'] ?? 'unknown';

			if ($referrer != 'unknown')
			{
				// record logins to link the apikeys to domains and groups
				$domain_referrer = strtolower(parse_url($referrer, PHP_URL_HOST));
				$app['eland.xdb']->set('apikey_login', $apikey, ['domain' => $domain_referrer]);
			}

			$app['monolog']->info('eLAS guest login using token ' . $token . ' succeeded. referrer: ' . $referrer);

			$glue = (strpos($location, '?') === false) ? '?' : '&';
			header('Location: ' . $location . $glue . $param);
			exit;
		}

		$app['eland.alert']->error('interlets_login');

		return $app->redirect('register');
	}


	public function get(Request $request, Application $app)
	{


		return $app['twig']->render('anon/page.html.twig', [

		]);
	}

}
