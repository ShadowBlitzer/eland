<?php

namespace controller;

use util\app;
use util\user;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints as Assert;
use form\email_addon_type;
use form\password_reset_type;

class password_reset
{
	public function form(Request $request, app $app, string $schema)
	{
		$form = $app->form()
			->add('email', email_addon_type::class, [
				'constraints' => new Assert\Email(),
			])
			->add('submit', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();

			$email = strtolower($data['email']);

			$user = $app['db']->fetchAll('select u.*
				from ' . $schema . '.contact c,
					' . $schema . '.type_contact tc,
					' . $schema . '.users u
				where c. value = ?
					and tc.id = c.id_type_contact
					and tc.abbrev = \'mail\'
					and c.id_user = u.id
					and u.status in (1, 2)', [$email]);

			if (count($user) === 1)
			{
				$user = $user[0];

				$app['mail_queue_confirm_link']
					->set_to([$email])
					->set_data($user)
					->set_template('confirm_password_reset')
					->set_route('password_reset_new_password')
					->put();

				$app->success($app->trans('password_reset.link_send_success', ['%email%' => $email]));

				return $app->redirect($app->path('login', ['schema' => $schema]));
			}

			$app->err($app->trans('password_reset.unknown_email_address'));
		}

		return $app['twig']->render('password_reset/form.html.twig', ['form' => $form->createView()]);
	}

	public function new_password(Request $request, app $app, string $schema, string $token)
	{
		if ($request->getMethod() === 'GET')
		{
			$data = $app['mail_validated_confirm_link']->get();
			
			error_log(json_encode($data));
			
			if (!count($data))
			{
				$app->err($app->trans('password_reset.confirm_not_found'));
				return $app->redirect($app->path('password_reset', ['schema' => $schema]));
			}
		}

		// note: unwanted access is protected by _etoken 

		$form = $app->form()
			->add('password', password_reset_type::class)
			->add('submit', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isValid())
		{
			$data = $form->getData();


			$app->success($app->trans('password_reset.new_password_success'));
			return $app->redirect($app->path('login', ['schema' => $schema]));
		}

		return $app['twig']->render('password_reset/new_password.html.twig', ['form' => $form->createView()]);
	}
}

