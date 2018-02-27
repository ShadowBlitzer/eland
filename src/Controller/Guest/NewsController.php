<?php

namespace App\Controller\Guest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Translation\TranslatorInterface;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\Form\FormFactoryInterface;

use App\Repository\NewsRepository;

use App\Service\SessionView;
use App\Util\Sort;
use App\Util\Pagination;

use Doctrine\DBAL\Connection as Db;
use App\Service\Xdb; 

use App\Form\Post\NewsType;

class NewsController extends AbstractController 
{
	/**
	 * @Route("/news", name="news_no_view")
	 * @Method("GET")
	 */
	public function noView(SessionView $sessionView, Request $request, string $schema, string $access)
	{
		return $this->redirectToRoute('news_index', [
			'schema'	=> $schema,
			'access'	=> $access,
			'view'		=> $sessionView->get('news', $schema, $access),
		]);
	}

	/**
	 * @Route("/news/{view}", name="news_index", requirements={"view" = "list|extended"})
	 * @Method("GET|POST")
	 */
	public function index(FormFactoryInterface $formFactory, NewsRepository $newsRepository,
		TranslatorInterface $translator,
		Db $db, Xdb $xdb,
		SessionView $sessionView, Request $request, 
		string $schema, string $access, string $view)
	{
		$s_admin = $access === 'a';

		$sessionView->set('news', $schema, $access, $view);
		
		$where = $params = [];

		$filtered = count($where) ? true : false;
		$where = $filtered ? ' where ' . implode(' and ', $where) . ' ' : '';

		$query = ' from ' . $schema . '.news n ' . $where;
		$row_count = $db->fetchColumn('select count(n.*)' . $query, $params);
		$query = 'select n.*' . $query;

		$sort = new Sort($request);

		$sort->addColumns([
			'headline'		=> 'asc',
			'itemdate'		=> 'desc',
			'cdate'			=> 'desc',
		])
		->setDefault('itemdate');

		$pagination = new Pagination($request, $row_count);

		$query .= $sort->query();
		$query .= $pagination->query();

		$news = $db->fetchAll($query, $params);
		
		$newsAccessAry = $toApproveAry = $approve_headline_ary = [];
		
		$rows = $xdb->getMany(['agg_schema' => $schema, 'agg_type' => 'news_access']);
		
		foreach ($rows as $row)
		{
			$acc = $row['data']['access'];
			$newsAccessAry[$row['eland_id']] = $acc;
		}
		
		foreach ($news as $k => $n)
		{
			$newsId = $n['id'];
		
			if (!isset($newsAccessAry[$newsId]))
			{
				$xdb->set('news_access', $newsId, ['access' => 'interlets'], $schema);
				$news[$k]['access'] = 'interlets';
				continue;
			}
		
			$news[$k]['access'] = $newsAccessAry[$newsId];

			if (!$n['approved'] && $s_admin)
			{
				$news[$k]['class'] = 'inactive';
				$approveButton = 'approve_' . $n['id'];
				$news[$k]['approve_button'] = $approveButton;
				$toApproveAry[] = $approveButton;
				$approve_headline_ary[$n['id']] = $n['headline'];
			}

/*		
			if (!$app['access_control']->is_visible($news[$k]['access']))
			{
				unset($news[$k]);
			}
*/
		}

		$vars = [
			'news'			=> $news,
//			'filter'		=> $filter->createView(),
			'filtered'		=> $filtered,
			'pagination'	=> $pagination->get($row_count),		
			'sort'			=> $sort->get(),
		];

		if ($s_admin && count($toApproveAry))
		{
			$approveForm = $this->createFormBuilder();

			foreach($toApproveAry as $key)
			{
				$approveForm->add($key, SubmitType::class);
			}

			$approveForm = $approveForm->getForm();
			$approveForm->handleRequest($request);

			if ($approveForm->isSubmitted() && $approveForm->isValid())
			{
				foreach ($toApproveAry as $key)
				{
					if ($approveForm->get($key)->isClicked())
					{
						list($approve_str, $id) = explode('_', $key);
						$name = $approve_headline_ary[$id];				

						$newsRepository->approve($id, $schema);

						$this->addFlash('success', $translator->trans('news.approve.success', ['%name%' => $name]));
						break;
					}
				}

				if (!isset($approve_str))
				{
					$this->addFlash('error', 'news.approve.error');					
				}

				$params = $request->attributes->all();
				unset($params['_route_params'], $params['_route']);
				return $this->redirectToRoute('news_index', $params);
			}

			$vars['approve_form'] = $approveForm->createView();
		}

		return $this->render('news/' . $access . '_' . $view . '.html.twig', $vars);
	}

	/**
	 * @Route("/news/{id}", name="news_show", requirements={"id"="\d+"})
	 * @Method("GET")
	 */
	public function show(NewsRepository $newsRepository, TranslatorInterface $translator, 
		Request $request, string $schema, string $access, int $id)
	{
		$news = $newsRepository->get($id, $schema);

		$vars = ['news'	=> $news];

		if ($access === 'a' && !$news['approved'])
		{
			$approveForm = $this->createFormBuilder()
				->add('approve', SubmitType::class)
				->getForm();
			
			$approveForm->handleRequest($request);

			if ($approveForm->isSubmitted() 
				&& $approveForm->isValid() 
				&& $approveForm->get('approve')->isClicked())
			{
				$newsRepository->approve($news['id'], $schema);
				$this->addFlash('success', $translator->trans('news.approve.success', ['%name%' => $news['headline']]));

				return $this->redirectoRoute('news_show', [
					'schema'	=> $schema,
					'access'	=> $access,
					'news'		=> $id,
				]);
			}

			$vars['approve_form'] = $approveForm->createView();
		}

		$access_ary = ['public', 'interlets', 'users', 'admin'];

		$vars['prev'] = $newsRepository->getPrev($news['id'], $schema, $access_ary);
		$vars['next'] = $newsRepository->getNext($news['id'], $schema, $access_ary);

		return $this->render('news/' . $access . '_show.html.twig', $vars);
	}

	/**
	 * @Route("/news/add", name="news_add")
	 * @Method({"GET", "POST"})
	 */
	public function add(TranslatorInterface $translator, NewsRepository $newsRepository, 
		SessionView $seesionView,
		Request $request, string $schema, string $access)
	{
		$form = $this->createForm(NewsType::class)
			->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			$data['approved'] = $access === 'a';

			$data['id'] = $newsRepository->insert($schema, $data);

			if (!$data['approved'])
			{
				$app['mail_queue']->setTemplate('news_review_admin')
					->setVars(['news' => $data])
					->setSchema($schema)
					->setTo($app['mail_newsadmin']->get($schema))
					->setPriority(900000)
					->put();

				$this->addFlash('info', $translator->trans('news_add.approve_info', ['%name%' => $data['headline']]));

				return $this->redirectToRoute('news_index', [
					'schema' 	=> $schema,
					'access'	=> $access,
					'view'		=> $sessionView->get('news', $schema, $access),
				]);					
			}

			$this->addFlash('success', $translator->trans('news_add.success', ['%name%'  => $data['headline']]));

			return $this->redirectToRoute('news_show', [
				'schema' 	=> $schema,
				'access'	=> $access,
				'news'		=> $data['id'],
			]);				
		}

		return $this->render('news/' . $access . '_add.html.twig', [
			'form' => $form->createView(),
		]);
	}

	/**
	 * @Route("/news/{id}/edit", name="news_edit")
	 * @Method({"GET", "POST"})
	 */
	public function edit(NewsRepository $newsRepository, TranslatorInterface $translator, 
		Request $request, string $schema, string $access, int $id)
	{
		$news = $newsRepository->get($id, $schema);

		$form = $this->createForm(NewsType::class, $news)
			->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$data = $form->getData();

			$newsRepository->update($id ,$schema, $data);

			$this->addFlash('success', $translator->trans('news_edit.success', ['%name%'  => $data['headline']]));

			return $this->redirectToRoute('news_show', [
				'schema' 	=> $schema,
				'access'	=> $access,
				'id'		=> $id,
			]);				
		}
	
		return $this->render('news/' . $access . '_edit.html.twig', [
			'form' => $form->createView(),
		]);
	}

	/**
	 * @Route("/news/{id}/del", name="news_del")
	 * @Method({"GET", "POST"})
	 */
	public function del(NewsRepository $newsRepository, SessionView $sessionView, 
		TranslatorInterface $translator,
		Request $request, string $schema, string $access, int $id)
	{
		$news = $newsRepository->get($id, $schema);
	
		$form = $this->createFormBuilder()
			->add('submit', SubmitType::class)
			->getForm()
			->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$newsRepository->delete($id, $schema);

			$this->addFlash('success', $translator->trans('news_del.success', ['%name%' => $news['headline']]));

			return $this->redirectToRoute('news_index', [
				'schema' 	=> $schema,
				'access'	=> $access,
				'view'		=> $sessionView->get('news', $schema, $access),
			]);				
		}

		return $this->render('news/' . $access . '_del.html.twig', [
			'form' => $form->createView(),
			'news'	=> $news,
		]);
	}	
}

/*

<?php

$approve = $_GET['approve'] ?? false;
$edit = $_GET['edit'] ?? false;
$add = $_GET['add'] ?? false;
$del = $_GET['del'] ?? false;
$id = $_GET['id'] ?? false;
$submit = isset($_POST['zend']) ? true : false;

/**
 * approve a newsitem
 */

/*

if ($approve)
{
	$page_access = 'admin';

	require_once __DIR__ . '/include/web.php';

	if ($app['db']->update('news', ['approved' => 't', 'published' => 't'], ['id' => $approve]))
	{
		$app['alert']->success('Nieuwsbericht goedgekeurd');
	}
	else
	{
		$app['alert']->error('Goedkeuren nieuwsbericht mislukt.');
	}
	cancel($approve);
}

/**
 * add or edit a newsitem
 */

 /*
if ($add || $edit)
{
	$page_access = 'user';
	require_once __DIR__ . '/include/web.php';

	$news = [];

	if ($submit)
	{
		$news = [
			'itemdate'		=> trim($_POST['itemdate'] ?? ''),
			'location'		=> trim($_POST['location'] ?? ''),
			'sticky'		=> isset($_POST['sticky']) ? 't' : 'f',
			'newsitem'		=> trim($_POST['newsitem'] ?? ''),
			'headline'		=> trim($_POST['headline'] ?? ''),
		];

		$access_error = $app['access_control']->get_post_error();

		if ($access_error)
		{
			$errors[] = $access_error;
		}

		if ($news['itemdate'])
		{
			$news['itemdate'] = $app['date_format']->reverse($news['itemdate']);

			if ($news['itemdate'] === false)
			{
				$errors[] = 'Fout formaat in agendadatum.';

				$news['itemdate'] = '';
			}
		}

		if (!isset($news['headline']) || (trim($news['headline']) == ''))
		{
			$errors[] = 'Titel is niet ingevuld';
		}

		if (strlen($news['headline']) > 200)
		{
			$errors[] = 'De titel mag maximaal 200 tekens lang zijn.';
		}

		if (strlen($news['location']) > 128)
		{
			$errors[] = 'De locatie mag maximaal 128 tekens lang zijn.';
		}

		if ($token_error = $app['form_token']->get_error())
		{
			$errors[] = $token_error;
		}
	}

	if (count($errors))
	{
		$app['alert']->error($errors);
	}
}

if ($add && $submit && !count($errors))
{
	$news['approved'] = ($s_admin) ? 't' : 'f';
	$news['published'] = ($s_admin) ? 't' : 'f';
	$news['id_user'] = ($s_master) ? 0 : $s_id;
	$news['cdate'] = gmdate('Y-m-d H:i:s');

	if ($app['db']->insert('news', $news))
	{
		$id = $app['db']->lastInsertId('news_id_seq');

		$app['xdb']->set('news_access', $id, ['access' => $_POST['access']]);

		$app['alert']->success('Nieuwsbericht opgeslagen.');

		if(!$s_admin)
		{
			$vars = [
				'group'		=> [
					'name'	=> $app['config']->get('systemname'),
					'tag'	=> $app['config']->get('systemtag'),
				],
				'news'	=> $news,
				'news_url'	=> $app['base_url'] . '/news.php?id=' . $id,
			];

			$app['mail']->queue([
				'to' 		=> 'newsadmin',
				'template'	=> 'admin_news_approve',
				'vars'		=> $vars,
			]);

			$app['alert']->success('Nieuwsbericht wacht op goedkeuring van een beheerder');
			cancel();
		}
		cancel($id);
	}
	else
	{
		$app['alert']->error('Nieuwsbericht niet opgeslagen.');
	}
}

if ($edit && $submit && !count($errors))
{
	if($app['db']->update('news', $news, ['id' => $edit]))
	{
		$app['xdb']->set('news_access', $edit, ['access' => $_POST['access']]);

		$app['alert']->success('Nieuwsbericht aangepast.');
		cancel($edit);
	}
	else
	{
		$app['alert']->error('Nieuwsbericht niet aangepast.');
	}
}

if ($edit)
{
	$news = $app['db']->fetchAssoc('SELECT * FROM news WHERE id = ?', [$edit]);
	list($news['itemdate']) = explode(' ', $news['itemdate']);

	$news_access = $app['xdb']->get('news_access', $edit)['data']['access'];
}

if ($add)
{
	$news['itemdate'] = gmdate('Y-m-d');
}

if ($add || $edit)
{
	$app['assets']->add('datepicker');

	$h1 = 'Nieuwsbericht ';
	$h1 .= ($add) ? 'toevoegen' : 'aanpassen';
	$fa = 'calendar';

	include __DIR__ . '/include/header.php';

	echo '<div class="panel panel-info">';
	echo '<div class="panel-heading">';

	echo '<form method="post" class="form-horizontal">';

	echo '<div class="form-group">';
	echo '<label for="itemdate" class="col-sm-2 control-label">Agendadatum</label>';
	echo '<div class="col-sm-10">';
	echo '<input type="text" class="form-control" id="itemdate" name="itemdate" ';
	echo 'data-provide="datepicker" ';
	echo 'data-date-format="' . $app['date_format']->datepicker_format() . '" ';
	echo 'data-date-language="nl" ';
	echo 'data-date-today-highlight="true" ';
	echo 'data-date-autoclose="true" ';
	echo 'data-date-orientation="bottom" ';
	echo 'value="' . $app['date_format']->get($news['itemdate'], 'day') . '" ';
	echo 'placeholder="' . $app['date_format']->datepicker_placeholder() . '">';
	echo '<p><small>Wanneer gaat dit door?</small></p>';
	echo '</div>';
	echo '</div>';

	echo '<div class="form-group">';
	echo '<label for="location" class="col-sm-2 control-label">Locatie</label>';
	echo '<div class="col-sm-10">';
	echo '<input type="text" class="form-control" id="location" name="location" ';
	echo 'value="' . $news['location'] . '" maxlength="128">';
	echo '</div>';
	echo '</div>';

	echo '<div class="form-group">';
	echo '<label for="headline" class="col-sm-2 control-label">Titel</label>';
	echo '<div class="col-sm-10">';
	echo '<input type="text" class="form-control" id="headline" name="headline" ';
	echo 'value="' . $news['headline'] . '" required maxlength="200">';
	echo '</div>';
	echo '</div>';

	echo '<div class="form-group">';
	echo '<label for="newsitem" class="col-sm-2 control-label">Bericht</label>';
	echo '<div class="col-sm-10">';
	echo '<textarea name="newsitem" id="newsitem" class="form-control" rows="10" required>';
	echo $news['newsitem'];
	echo '</textarea>';
	echo '</div>';
	echo '</div>';

	echo '<div class="form-group">';
	echo '<label for="sticky" class="col-sm-2 control-label">Behoud na datum</label>';
	echo '<div class="col-sm-10">';
	echo '<input type="checkbox" id="sticky" name="sticky" ';
	echo 'value="1"';
	echo  ($news['sticky'] == 't') ? ' checked="checked"' : '';
	echo '>';
	echo '</div>';
	echo '</div>';

	if ($s_user)
	{
		$omit_access = 'admin';
	}
	else
	{
		$omit_access = false;
	}

	echo $app['access_control']->get_radio_buttons('news', $news_access, $omit_access);

	$btn = ($add) ? 'success' : 'primary';
	echo aphp('news', ($edit) ? ['id' => $edit] : [], 'Annuleren', 'btn btn-default') . '&nbsp;';
	echo '<input type="submit" name="zend" value="Opslaan" class="btn btn-' . $btn . '">';
	$app['form_token']->generate();

	echo '</form>';

	echo '</div>';
	echo '</div>';

	include __DIR__ . '/include/footer.php';
	exit;
}

/**
 * delete a newsitem
 */

/*

if ($del)
{
	$page_access = 'admin';
	require_once __DIR__ . '/include/web.php';

	if ($submit)
	{
		if ($error_token = $app['form_token']->get_error())
		{
			$app['alert']->error($error_token);
			cancel();
		}

		if($app['db']->delete('news', ['id' => $del]))
		{
			$app['xdb']->del('news_access', $del);

			$app['alert']->success('Nieuwsbericht verwijderd.');
			cancel();
		}

		$app['alert']->error('Nieuwsbericht niet verwijderd.');
	}

	$news = $app['db']->fetchAssoc('SELECT n.*
		FROM news n
		WHERE n.id = ?', [$del]);

	$news_access = $app['xdb']->get('news_access', $del)['data']['access'];

	$h1 = 'Nieuwsbericht ' . $news['headline'] . ' verwijderen?';
	$fa = 'calendar';

	include __DIR__ . '/include/header.php';

	$background = ($news['approved']) ? '' : ' bg-warning';


	echo '<div class="panel panel-default printview">';
	echo '<div class="panel-heading">';

	echo '<dl>';

	echo '<dt>Bericht</dt>';
	echo '<dd>';
	echo nl2br(htmlspecialchars($news['newsitem'],ENT_QUOTES));
	echo '</dd>';

	echo '<dt>Agendadatum</dt>';

	echo '<dd>';
	echo ($itemdate) ? $app['date_format']->get($itemdate, 'day') : '<i class="fa fa-times"></i>';
	echo '</dd>';

	echo '<dt>Locatie</dt>';
	echo '<dd>';
	echo ($news['location']) ? htmlspecialchars($news['location'], ENT_QUOTES) : '<i class="fa fa-times"></i>';
	echo '</dd>';

	echo '<dt>Ingegeven door</dt>';
	echo '<dd>';
	echo link_user($news['id_user']);
	echo '</dd>';

	echo '<dt>Goedgekeurd</dt>';
	echo '<dd>';
	echo ($news['approved']) ? 'Ja' : 'Nee';
	echo '</dd>';

	echo '<dt>Behoud na datum?</dt>';
	echo '<dd>';
	echo ($news['sticky']) ? 'Ja' : 'Nee';
	echo '</dd>';

	echo '<dt>Zichtbaarheid</dt>';
	echo '<dd>';
	echo $app['access_control']->get_label($news_access);
	echo '</dd>';

	echo '</dl>';

	echo '</div></div>';

	echo '<div class="panel panel-info">';
	echo '<div class="panel-heading">';

	echo '<p class="text-danger"><strong>Ben je zeker dat dit nieuwsbericht ';
	echo 'moet verwijderd worden?</strong></p>';

	echo '<form method="post">';
	echo aphp('news', ['id' => $del], 'Annuleren', 'btn btn-default') . '&nbsp;';
	echo '<input type="submit" value="Verwijderen" name="zend" class="btn btn-danger">';
	$app['form_token']->generate();
	echo '</form>';

	echo '</div>';
	echo '</div>';

	include __DIR__ . '/include/footer.php';
	exit;
}

/**
 * show a newsitem
 */

/* 

if ($id)
{
	$page_access = 'guest';

	require_once __DIR__ . '/include/web.php';

	$show_visibility = ($s_user && $app['config']->get('template_lets')
		&& $app['config']->get('interlets_en')) || $s_admin ? true : false;

	$news = $app['db']->fetchAssoc('SELECT n.*
		FROM news n
		WHERE n.id = ?', [$id]);

	if (!$s_admin && !$news['approved'])
	{
		$app['alert']->error('Je hebt geen toegang tot dit nieuwsbericht.');
		cancel();
	}

	$news_access = $app['xdb']->get('news_access', $id)['data']['access'];

	if (!$app['access_control']->is_visible($news_access))
	{
		$app['alert']->error('Je hebt geen toegang tot dit nieuwsbericht.');
		cancel();
	}

	$and_approved_sql = ($s_admin) ? '' : ' and approved = \'t\' ';

	$rows = $app['xdb']->get_many(['agg_schema' => $app['this_group']->get_schema(),
		'agg_type' => 'news_access',
		'eland_id' => ['<' => $news['id']],
		'access' => $app['access_control']->get_visible_ary()], 'order by eland_id desc limit 1');

	$prev = (count($rows)) ? reset($rows)['eland_id'] : false;

	$rows = $app['xdb']->get_many(['agg_schema' => $app['this_group']->get_schema(),
		'agg_type' => 'news_access',
		'eland_id' => ['>' => $news['id']],
		'access' => $app['access_control']->get_visible_ary()], 'order by eland_id asc limit 1');

	$next = (count($rows)) ? reset($rows)['eland_id'] : false;

	$top_buttons = '';

	if($s_user || $s_admin)
	{
		$top_buttons .= aphp('news', ['add' => 1], 'Toevoegen', 'btn btn-success', 'Nieuws toevoegen', 'plus', true);

		if($s_admin)
		{
			$top_buttons .= aphp('news', ['edit' => $id], 'Aanpassen', 'btn btn-primary', 'Nieuwsbericht aanpassen', 'pencil', true);
			$top_buttons .= aphp('news', ['del' => $id], 'Verwijderen', 'btn btn-danger', 'Nieuwsbericht verwijderen', 'times', true);

			if (!$news['approved'])
			{
				$top_buttons .= aphp('news', ['approve' => $id], 'Goedkeuren', 'btn btn-warning', 'Nieuwsbericht goedkeuren en publiceren', 'check', true);
			}
		}
	}

	if ($prev)
	{
		$top_buttons .= aphp('news', ['id' => $prev], 'Vorige', 'btn btn-default', 'Vorige', 'chevron-down', true);
	}

	if ($next)
	{
		$top_buttons .= aphp('news', ['id' => $next], 'Volgende', 'btn btn-default', 'Volgende', 'chevron-up', true);
	}

	$top_buttons .= aphp('news', ['view' => $view_news], 'Lijst', 'btn btn-default', 'Lijst', 'calendar', true);

	$h1 = 'Nieuwsbericht: ' . htmlspecialchars($news['headline'], ENT_QUOTES);
	$fa = 'calendar';

	include __DIR__ . '/include/header.php';

	if ($show_visibility)
	{
		echo '<p>Zichtbaarheid: ';
		echo $app['access_control']->get_label($news_access);
		echo '</p>';
	}

	$background = ($news['approved']) ? '' : ' bg-warning';

	echo '<div class="panel panel-default printview">';
	echo '<div class="panel-heading">';

	echo '<p>Bericht</p>';
	echo '</div>';
	echo '<div class="panel-body' . $background . '">';
	echo nl2br(htmlspecialchars($news['newsitem'],ENT_QUOTES));
	echo '</div></div>';

	echo '<div class="panel panel-default printview">';
	echo '<div class="panel-heading">';

	echo '<dl>';

	echo '<dt>Agendadatum</dt>';

	echo '<dd>';
	echo ($news['itemdate']) ? $app['date_format']->get($news['itemdate'], 'day') : '<i class="fa fa-times"></i>';
	echo '</dd>';

	echo '<dt>Locatie</dt>';
	echo '<dd>';
	echo ($news['location']) ? htmlspecialchars($news['location'], ENT_QUOTES) : '<i class="fa fa-times"></i>';
	echo '</dd>';

	echo '<dt>Ingegeven door</dt>';
	echo '<dd>';
	echo link_user($news['id_user']);
	echo '</dd>';

	if ($s_admin)
	{
		echo '<dt>Goedgekeurd</dt>';
		echo '<dd>';
		echo ($news['approved']) ? 'Ja' : 'Nee';
		echo '</dd>';

		echo '<dt>Behoud na datum?</dt>';
		echo '<dd>';
		echo ($news['sticky']) ? 'Ja' : 'Nee';
		echo '</dd>';
	}
	echo '</dl>';

	echo '</div>';
	echo '</div>';

	include __DIR__ . '/include/footer.php';
	exit;
}

/**
 * show all newsitems
 */
/*

$page_access = 'guest';
require_once __DIR__ . '/include/web.php';

$show_visibility = ($s_user && $app['config']->get('template_lets')
	&& $app['config']->get('interlets_en')) || $s_admin ? true : false;

if (!($view || $inline))
{
	cancel();
}

$v_list = ($view == 'list' || $inline) ? true : false;
$v_extended = ($view == 'extended' && !$inline) ? true : false;

$params = [
	'view'	=> $view,
];

$query = 'select * from news';

if(!$s_admin)
{
	$query .= ' where approved = \'t\'';
}

$query .= ' order by itemdate desc';

$news = $app['db']->fetchAll($query);

$newsAccessAry = [];

$rows = $app['xdb']->get_many(['agg_schema' => $app['this_group']->get_schema(), 'agg_type' => 'news_access']);

foreach ($rows as $row)
{
	$access = $row['data']['access'];
	$newsAccessAry[$row['eland_id']] = $access;
}

foreach ($news as $k => $n)
{
	$news_id = $n['id'];

	if (!isset($newsAccessAry[$news_id]))
	{
		$app['xdb']->set('news_access', $news_id, ['access' => 'interlets']);
		$news[$k]['access'] = 'interlets';
		continue;
	}

	$news[$k]['access'] = $newsAccessAry[$news_id];

	if (!$app['access_control']->is_visible($news[$k]['access']))
	{
		unset($news[$k]);
	}
}

if(($s_user || $s_admin) && !$inline)
{
	$top_buttons .= aphp('news', ['add' => 1], 'Toevoegen', 'btn btn-success', 'Nieuws toevoegen', 'plus', true);
}

if ($inline)
{
	echo '<h3>';
	echo aphp('news', ['view' => $view_news], 'Nieuws', false, false, 'calendar');
	echo '</h3>';
}
else
{
	$h1 = 'Nieuws';

	$v_params = $params;
	$h1 .= '<span class="pull-right hidden-xs">';
	$h1 .= '<span class="btn-group" role="group">';

	$active = ($v_list) ? ' active' : '';
	$v_params['view'] = 'list';
	$h1 .= aphp('news', $v_params, '', 'btn btn-default' . $active, 'lijst', 'align-justify');

	$active = ($v_extended) ? ' active' : '';
	$v_params['view'] = 'extended';
	$h1 .= aphp('news', $v_params, '', 'btn btn-default' . $active, 'Lijst met omschrijvingen', 'th-list');

	$h1 .= '</span></span>';

	$fa = 'calendar';

	include __DIR__ . '/include/header.php';
}

if (!count($news))
{
	echo '<div class="panel panel-warning">';
	echo '<div class="panel-heading">';
	echo '<p>Er zijn momenteel geen nieuwsberichten.</p>';
	echo '</div></div>';

	if (!$inline)
	{
		include __DIR__ . '/include/footer.php';
	}
	exit;
}

if ($v_list)
{
	echo '<div class="panel panel-warning printview">';
	echo '<div class="table-responsive">';
	echo '<table class="table table-striped table-hover table-bordered footable">';

	if (!$inline)
	{
		echo '<thead>';
		echo '<tr>';
		echo '<th>Titel</th>';
		echo '<th data-hide="phone" data-sort-initial="descending">Agendadatum</th>';
		echo $s_admin ? '<th data-hide="phone">Goedgekeurd</th>' : '';
		echo $show_visibility ? '<th data-hide="phone, tablet">Zichtbaar</th>' : '';
		echo '</tr>';
		echo '</thead>';
	}

	echo '<tbody>';

	foreach ($news as $n)
	{
		echo '<tr';
		echo $n['approved'] ? '' : ' class="warning"';
		echo '>';

		echo '<td>';
		echo aphp('news', ['id' => $n['id']], $n['headline']);
		echo '</td>';

		echo $app['date_format']->get_td($n['itemdate'], 'day');

		if ($s_admin && !$inline)
		{
			echo '<td>';
			echo $n['approved'] ? 'Ja' : 'Nee';
			echo '</td>';
		}

		if ($show_visibility)
		{
			echo '<td>';
			echo $app['access_control']->get_label($n['access']);
			echo '</td>';
		}

		echo '</tr>';
	}
	echo '</tbody>';
	echo '</table></div></div>';
}
else if ($v_extended)
{
	foreach ($news as $n)
	{
		$background = $n['approved'] ? '' : ' bg-warning';

		echo '<div class="panel panel-info printview">';
		echo '<div class="panel-body' . $background . '">';

		echo '<div class="media">';
		echo '<div class="media-body">';
		echo '<h3 class="media-heading">';
		echo aphp('news', ['id' => $n['id']], $n['headline']);
		echo '</h3>';
		echo nl2br(htmlspecialchars($n['newsitem'],ENT_QUOTES));

		echo '<dl>';

		if ($n['location'])
		{
			echo '<dt>';
			echo 'Locatie';
			echo '</dt>';
			echo '<dd>';
			echo htmlspecialchars($n['location'], ENT_QUOTES);
			echo '</dd>';
		}

		if ($n['itemdate'])
		{
			echo '<dt>';
			echo 'Agendadatum';
			echo '</dt>';
			echo '<dd>';
			echo $app['date_format']->get($n['itemdate'], 'day');

			if ($n['sticky'])
			{
				echo ' <i>(Nieuwsbericht blijft behouden na datum)</i>';
			}
			echo '</dd>';
		}

		if ($show_visibility)
		{
			echo '<dt>';
			echo 'Zichtbaarheid';
			echo '</dt>';
			echo '<dd>';
			echo $app['access_control']->get_label($n['access']);
			echo '</dd>';
		}

		echo '</dl>';

		echo '</div>';
		echo '</div>';
		echo '</div>';

		echo '<div class="panel-footer">';
		echo '<p><i class="fa fa-user"></i> ' . link_user($n['id_user']);

		if ($s_admin)
		{
			echo '<span class="inline-buttons pull-right hidden-xs">';
			if (!$n['approved'])
			{
				echo aphp('news', ['approve' => $n['id']], 'Goedkeuren en publiceren', 'btn btn-warning btn-xs', false, 'check');
			}
			echo aphp('news', ['edit' => $n['id']], 'Aanpassen', 'btn btn-primary btn-xs', false, 'pencil');
			echo aphp('news', ['del' => $n['id']], 'Verwijderen', 'btn btn-danger btn-xs', false, 'times');
			echo '</span>';
		}
		echo '</p>';
		echo '</div>';

		echo '</div>';
	}
}

if (!$inline)
{
	include __DIR__ . '/include/footer.php';
}

function cancel($id = '')
{
	global $view_news;

	$params = ['view' => $view_news];

	if ($id)
	{
		$params['id'] = $id;
	}

	header('Location: ' . generate_url('news', $params));
	exit;
}

*/
