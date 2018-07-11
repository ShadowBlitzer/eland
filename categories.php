<?php declare(strict_types=1);

$page_access = 'admin';
require_once __DIR__ . '/include/web.php';

$edit = $_GET['edit'] ?? false;
$del = $_GET['del'] ?? false;
$add = $_GET['add'] ?? false;

if ($add)
{
	$cat = [];

	if (isset($_POST['zend']))
	{
		$cat['name'] = $_POST['name'];
		$cat['id_parent'] = $_POST['id_parent'];
		$cat['leafnote'] = ($_POST['id_parent'] == 0) ? 0 : 1;

		if (!isset($cat['name'])|| (trim($cat['name']) == ''))
		{
			$errors[] = 'Vul naam in!';
		}

		if (strlen($cat['name']) > 40)
		{
			$errors[] = 'De naam mag maximaal 40 tekens lang zijn.';
		}

		if (!isset($cat['id_parent'])|| (trim($cat['id_parent']) == ''))
		{
			$errors[] = 'Vul hoofdrubriek in!';
		}

		if ($token_error = $app['form_token']->get_error())
		{
			$errors[] = $token_error;
		}

		if (!count($errors))
		{
			$cat['cdate'] = date('Y-m-d H:i:s');
			$cat['id_creator'] = ($s_master) ? 0 : $s_id;
			$cat['fullname'] = ($cat['leafnote']) ? $app['db']->fetchColumn('SELECT name FROM categories WHERE id = ?', [(int) $cat['id_parent']]) . ' - ' : '';
			$cat['fullname'] .= $cat['name'];

			if ($app['db']->insert('categories', $cat))
			{
				$app['alert']->success('Categorie toegevoegd.');
				cancel();
			}

			$app['alert']->error('Categorie niet toegevoegd.');
		}
		else
		{
			$app['alert']->error($errors);
		}
	}

	$parent_cats = [0 => '-- Hoofdcategorie --'];

	$rs = $app['db']->prepare('SELECT id, name FROM categories WHERE leafnote = 0 ORDER BY name');

	$rs->execute();

	while ($row = $rs->fetch())
	{
		$parent_cats[$row['id']] = $row['name'];
	}

	$id_parent = ($cat['id_parent']) ? $cat['id_parent'] : 0;

	$h1 = 'Categorie toevoegen';
	$fa = 'clone';

	include __DIR__ . '/include/header.php';

	echo '<div class="panel panel-info">';
	echo '<div class="panel-heading">';

	echo '<form  method="post" class="form-horizontal">';

	echo '<div class="form-group">';
	echo '<label for="name" class="col-sm-2 control-label">Naam</label>';
	echo '<div class="col-sm-10">';
	echo '<input type="text" class="form-control" id="name" name="name" ';
	echo 'value="' . $cat['name'] . '" required maxlength="40">';
	echo '</div>';
	echo '</div>';

	echo '<div class="form-group">';
	echo '<label for="id_parent" class="col-sm-2 control-label">Hoofdcategorie of deelcategorie van</label>';
	echo '<div class="col-sm-10">';
	echo '<select name="id_parent" id="id_parent" class="form-control">';
	render_select_options($parent_cats, $id_parent);
	echo '</select>';
	echo '</div>';
	echo '</div>';

	echo aphp('categories', [], 'Annuleren', 'btn btn-default') . '&nbsp;';
	echo '<input type="submit" name="zend" value="Toevoegen" class="btn btn-success">';
	$app['form_token']->generate();

	echo '</form>';

	echo '</div>';
	echo '</div>';

	include __DIR__ . '/include/footer.php';
	exit;
}

if ($edit)
{
	$cats = [];

	$rs = $app['db']->prepare('SELECT id, * FROM categories ORDER BY fullname');

	$rs->execute();

	while ($row = $rs->fetch())
	{
		$cats[$row['id']] = $row;
	}

	$child_count_ary = [];

	foreach ($cats as $cat)
	{
		$child_count_ary[$cat['id_parent']]++;
	}

	$cat = $cats[$edit];

	if(isset($_POST['zend'])){

		$cat['name'] = $_POST['name'];
		$cat['id_parent'] = $_POST['id_parent'];
		$cat['leafnote'] = ($_POST['id_parent'] == 0) ? 0 : 1;

		if (!$cat['name'])
		{
			$app['alert']->error('Vul naam in!');
		}
		else if (($cat['stat_msgs_wanted'] + $cat['stat_msgs_offers']) && !$cat['leafnote'])
		{
			$app['alert']->error('Hoofdcategoriën kunnen geen berichten bevatten.');
		}
		else if ($cat['leafnote'] && $child_count_ary[$edit])
		{
			$app['alert']->error('Subcategoriën kunnen geen categoriën bevatten.');
		}
		else if ($token_error = $app['form_token']->get_error())
		{
			$app['alert']->error($token_error);
		}
		else
		{
			$prefix = ($cat['id_parent']) ? $app['db']->fetchColumn('SELECT name FROM categories WHERE id = ?', [$cat['id_parent']]) . ' - ' : '';
			$cat['fullname'] = $prefix . $cat['name'];
			unset($cat['id']);

			if ($app['db']->update('categories', $cat, ['id' => $edit]))
			{
				$app['alert']->success('Categorie aangepast.');
				$app['db']->executeUpdate('UPDATE categories SET fullname = ? || \' - \' || name WHERE id_parent = ?', [$cat['name'], $edit]);
				cancel();
			}

			$app['alert']->error('Categorie niet aangepast.');
		}
	}

	$parent_cats = [0 => '-- Hoofdcategorie --'];

	$rs = $app['db']->prepare('SELECT id, name FROM categories WHERE leafnote = 0 ORDER BY name');

	$rs->execute();

	while ($row = $rs->fetch())
	{
		$parent_cats[$row['id']] = $row['name'];
	}

	$id_parent = ($cat['id_parent']) ? $cat['id_parent'] : 0;


	$h1 = 'Categorie aanpassen : ' . $cat['name'];
	$fa = 'clone';

	include __DIR__ . '/include/header.php';

	echo '<div class="panel panel-info">';
	echo '<div class="panel-heading">';

	echo '<form method="post" class="form-horizontal">';

	echo '<div class="form-group">';
	echo '<label for="name" class="col-sm-2 control-label">Naam</label>';
	echo '<div class="col-sm-10">';
	echo '<input type="text" class="form-control" id="name" name="name" ';
	echo 'value="'. $cat["name"] . '" required>';
	echo '</div>';
	echo '</div>';

	echo '<div class="form-group">';
	echo '<label for="id_parent" class="col-sm-2 control-label">Hoofdcategorie of deelcategorie van</label>';
	echo '<div class="col-sm-10">';
	echo '<select class="form-control" id="id_parent" name="id_parent">';
	render_select_options($parent_cats, $id_parent);
	echo '</select>';
	echo '</div>';
	echo '</div>';

	echo aphp('categories', [], 'Annuleren', 'btn btn-default') . '&nbsp;';
	echo '<input type="submit" value="Opslaan" name="zend" class="btn btn-primary">';
	$app['form_token']->generate();

	echo '</form>';

	echo '</div>';
	echo '</div>';

	include __DIR__ . '/include/footer.php';
	exit;
}

if ($del)
{
	if(isset($_POST['zend']))
	{
		if ($error_token = $app['form_token']->get_error())
		{
			$app['alert']->error($error_token);
			cancel();
		}

		if ($app['db']->delete('categories', ['id' => $del]))
		{
			$app['alert']->success('Categorie verwijderd.');
			cancel();
		}

		$app['alert']->error('Categorie niet verwijderd.');
	}

	$fullname = $app['db']->fetchColumn('SELECT fullname FROM categories WHERE id = ?', [$del]);

	$h1 = 'Categorie verwijderen : ' . $fullname;
	$fa = 'clone';

	include __DIR__ . '/include/header.php';

	echo '<div class="panel panel-info">';
	echo '<div class="panel-heading">';

	echo "<p><font color='#F56DB5'><strong>Ben je zeker dat deze categorie";
	echo " moet verwijderd worden?</strong></font></p>";
	echo '<form method="post">';

	echo aphp('categories', [], 'Annuleren', 'btn btn-default') . '&nbsp;';
	echo '<input type="submit" value="Verwijderen" name="zend" class="btn btn-danger">';
	$app['form_token']->generate();
	echo '</form>';

	echo '</div>';
	echo '</div>';

	include __DIR__ . '/include/footer.php';
	exit;
}

$cats = $app['db']->fetchAll('select * from categories order by fullname');

$child_count_ary = [];

foreach ($cats as $cat)
{
	if (!isset($child_count_ary[$cat['id_parent']]))
	{
		$child_count_ary[$cat['id_parent']] = 0;
	}

	$child_count_ary[$cat['id_parent']]++;
}

$top_buttons .= aphp('categories', ['add' => 1], 'Toevoegen', 'btn btn-success', 'Categorie toevoegen', 'plus', true);

$h1 = 'Categorieën';
$fa = 'clone';

include __DIR__ . '/include/header.php';

echo '<div class="panel panel-default printview">';

echo '<div class="table-responsive">';
echo '<table class="table table-striped table-hover table-bordered footable" data-sort="false">';
echo '<tr>';
echo '<thead>';
echo '<th>Categorie</th>';
echo '<th data-hide="phone">Vraag</th>';
echo '<th data-hide="phone">Aanbod</th>';
echo '<th data-hide="phone">Verwijderen</th>';
echo '</tr>';
echo '</thead>';

echo '<tbody>';

foreach($cats as $cat)
{
	$count_wanted = $cat['stat_msgs_wanted'];
	$count_offers = $cat['stat_msgs_offers'];
	$count = $count_wanted + $count_offers;

	if (isset($child_count_ary[$cat['id']]))
	{
		$count += $child_count_ary[$cat['id']];
	}

	if (!$cat['id_parent'])
	{
		echo '<tr class="info">';
		echo '<td><strong>';
		echo aphp('categories', ['edit' => $cat['id']], $cat['name']);
		echo '</strong></td>';
	}
	else
	{
		echo '<tr>';
		echo '<td>';
		echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		echo aphp('categories', ['edit' => $cat['id']], $cat['name']);
		echo '</td>';
	}

	echo '<td>' . (($count_wanted) ?: '') . '</td>';
	echo '<td>' . (($count_offers) ?: '') . '</td>';

	echo '<td>';
	if (!$count)
	{
		echo aphp('categories', ['del' => $cat['id']], 'Verwijderen', 'btn btn-danger btn-xs', false, 'times');
	}
	echo '</td>';
	echo '</tr>';
}

echo '</tbody>';
echo '</table>';
echo '</div></div>';

echo '<p><ul><li>Categorieën met berichten of hoofdcategorieën met subcategorieën kan je niet verwijderen.';
echo '<li>Enkel subcategorieën kunnen berichten bevatten.</li></li></ul></p>';

include __DIR__ . '/include/footer.php';

function cancel($id = '')
{
	$params = [];

	if ($id)
	{
		$params['id'] = $id;
	}

	header('Location: ' . generate_url('categories', $params));
	exit;
}
