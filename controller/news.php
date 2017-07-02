<?php

namespace controller;

use util\app;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;

class news
{
	public function index(Request $request, app $app, string $schema, string $access)
	{

/*
		$query = 'select * from news  where approved = \'t\' order by itemdate desc';

		$news = $app['db']->fetchAll($query);

		$news_access_ary = [];

		$rows = $app['xdb']->get_many(['agg_schema' => $app['eland.this_group']->get_schema(), 'agg_type' => 'news_access']);

		foreach ($rows as $row)
		{
			$access = $row['data']['access'];
			$news_access_ary[$row['eland_id']] = $access;
		}

		foreach ($news as $k => $n)
		{
			$news_id = $n['id'];

			if (!isset($news_access_ary[$news_id]))
			{
				$app['eland.xdb']->set('news_access', $news_id, ['access' => 'interlets']);
				$news[$k]['access'] = 'interlets';
				continue;
			}

			$news[$k]['access'] = $news_access_ary[$news_id];

			if (!$app['eland.access_control']->is_visible($news[$k]['access']))
			{
				unset($news[$k]);
			}
		}
*/

		return $app['twig']->render('news/index.html.twig', [
			'news'	=> $news,
		]);
	}

	public function show(Request $request, app $app, string $schema, string $access, string $eid)
	{
		$news = $app['db']->fetchAssoc('SELECT n.*
			FROM ' . $schema . '.news n
			WHERE n.id = ?', [$id]);

		if (!$s_admin && !$news['approved'])
		{
			$app['alert']->error('Je hebt geen toegang tot dit nieuwsbericht.');
//			$app->redirect('news');
			cancel();
		}

		$news_access = $app['xdb']->get('news_access', $id)['data']['access'];

		if (!$app['access_control']->is_visible($news_access))
		{
			$app['alert']->error('Je hebt geen toegang tot dit nieuwsbericht.');
			cancel();
		}

		$and_approved_sql = ($s_admin) ? '' : ' and approved = \'t\' ';

		$rows = $app['eland.xdb']->get_many(['agg_schema' => $app['eland.this_group']->get_schema(),
			'agg_type' => 'news_access',
			'eland_id' => ['<' => $news['id']],
			'access' => $app['eland.access_control']->get_visible_ary()], 'order by eland_id desc limit 1');

		$prev = (count($rows)) ? reset($rows)['eland_id'] : false;

		$rows = $app['eland.xdb']->get_many(['agg_schema' => $app['eland.this_group']->get_schema(),
			'agg_type' => 'news_access',
			'eland_id' => ['>' => $news['id']],
			'access' => $app['eland.access_control']->get_visible_ary()], 'order by eland_id asc limit 1');

		$next = (count($rows)) ? reset($rows)['eland_id'] : false;


		return $app['twig']->render('news/extended.html.twig', [
			'news'	=> $news,
		]);
	}

	public function add(Request $request, app $app, string $schema, string $access)
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

		return $app['twig']->render('news/add.html.twig', [
			'form' => $form->createView(),
		]);
	}

	public function edit(Request $request, app $app, string $schema, string $access)
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

		return $app['twig']->render('news/edit.html.twig', [
			'form' => $form->createView(),
		]);
	}



}
