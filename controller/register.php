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

class register
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


	public function match(Request $request, Application $app)
	{

		$data = [
			'name' => 'Your name',
			'email' => 'Your email',
		];

		$form = $app['form.factory']->createBuilder(FormType::class, $data)
			->add('first_name')
			->add('last_name')
			->add('email', EmailType::class)
			->add('postcode')
			->add('gsm', TextType::class, ['required'	=> false])
			->add('tel', TextType::class, ['required'	=> false])
			->add('zend', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			// do something with the data

			// redirect somewhere
			return $app->redirect('...');
		}

		return $app['twig']->render('anon/register.html.twig', [
			'form' => $form->createView(),
		]);
	}

}
