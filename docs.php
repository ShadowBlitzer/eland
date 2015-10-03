<?php
ob_start();
$rootpath = '';
$role = 'guest';
require_once $rootpath . 'includes/inc_default.php';

$elas_mongo->connect();

$q = ($_GET['q']) ?: '';
$del = $_GET['del'];

$submit = ($_POST['zend']) ? true : false;
$confirm_del = ($_POST['confirm_del']) ? true : false;

$bucket = getenv('S3_BUCKET_DOC') ?: die('No "S3_BUCKET_DOC" env config var in found!');

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$s3 = Aws\S3\S3Client::factory(array(
		'signature'	=> 'v4',
		'region'	=> 'eu-central-1',
		'version'	=> '2006-03-01',
	));
}

if ($confirm_del && $del)
{
	$doc_id = new MongoId($del);

	if ($doc = $elas_mongo->docs->findOne(array('_id' => $doc_id)))
	{
		$s3->deleteObject(array(
			'Bucket'	=> $bucket,
			'Key'		=> $doc['filename'],
		));
		
		$elas_mongo->docs->remove(
			array('_id' => $doc_id),
			array('justOne'	=> true)
		);

		$alert->success('Het document werd verwijderd.');
		header('Location: ' . $rootpath . 'docs.php');
		exit;
	}
	$alert->error('Document niet gevonden.');
}

if (isset($del))
{
	$doc_id = new MongoId($del);

	$doc = $elas_mongo->docs->findOne(array('_id' => $doc_id));

	if ($doc)
	{
		$h1 = 'Document verwijderen?';
		$fa = 'files-o';

		require_once $rootpath . 'includes/inc_header.php';
		
		echo '<div class="panel panel-info">';
		echo '<div class="panel-heading">';
		echo '<form method="post">';

		echo '<p>';
		echo '<a href="https://s3.eu-central-1.amazonaws.com/' . $bucket . '/' . $doc['filename'] . '" target="_self">';
		echo ($doc['name']) ?: $doc['org_filename'];
		echo '</a>';
		echo '</p>';

		echo '<a href="' . $rootpath . 'docs.php" class="btn btn-default">Annuleren</a>&nbsp;';
		echo '<input type="submit" value="Verwijderen" name="confirm_del" class="btn btn-danger">';
		echo '</form>';

		echo '</div>';
		echo '</div>';

		require_once $rootpath . 'includes/inc_footer.php';
		exit;
	}

	$alert->error('Document niet gevonden.');
}


if ($submit)
{
	$tmpfile = $_FILES['file']['tmp_name'];
	$file = $_FILES['file']['name'];
	$file_size = $_FILES['file']['size'];
	$type = $_FILES['file']['type'];
	$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

	$allowed_types = array(
		'application/pdf'			=> 1,
		'image/jpeg'				=> 1,
		'image/png'					=> 1,
		'image/gif'					=> 1,
		'image/bmp'					=> 1,
		'image/tiff'				=> 1,
		'text/plain'				=> 1,
		'text/rtf'					=> 1,
		'application/msword'		=> 1,
		'application/zip'			=> 1,
		'audio/mpeg'				=> 1,
		'application/x-gzip'		=> 1,
		'application/x-compressed'	=> 1,
		'application/zip'			=> 1,
	);

	if ($file_size > 1024 * 1024 * 10)
	{
		$alert->error('Het bestand is te groot. De maximum grootte is 10MB.');
	}
	else if (!$file)
	{
		$alert->error('Geen bestand geselecteerd.');
	}
	else if (!($token = $_POST['token']))
	{
		$alert->error('Een token ontbreekt.');
	}
	else if (!$redis->get($schema . '_d_' . $token))
	{
		$alert->error('Geen geldig token');
	}
	else
	{
		$redis->del($schema . '_d_' . $token);

		$access = $_POST['access'];

		$mid = new MongoId();

		$filename = $schema . '_d_' . $mid . '_' . sha1(time() . mt_rand(0, 1000000)) . '.' . $ext;

		$doc = array(
			'_id' 			=> $mid,
			'filename'		=> $filename,
			'org_filename'	=> $file,
			'access'		=> (int) $access,
			'ts'			=> gmdate('Y-m-d H:i:s'),
			'user_id'		=> $s_id,
		);

		if ($name = $_POST['name'])
		{
			$doc['name'] = $name;
		}

		$elas_mongo->docs->insert($doc);

		$params = array(
			'CacheControl'	=> 'public, max-age=31536000',
		);

		if ($allowed_types[$type])
		{
			$params['ContentType'] = $type;
		}

		$upload = $s3->upload($bucket, $filename, fopen($tmpfile, 'rb'), 'public-read', array(
			'params'	=> $params
		));

		$alert->success('Het bestand is opgeladen.');
		header('Location: ' . $rootpath . 'docs.php');
		exit;
	}

}

$token = sha1(time() . mt_rand(0, 1000000));
$redis->set($schema . '_d_' . $token, '1');
$redis->expire($schema . '_d_' . $token, 3600);

$find = array(
	'access'	=> array('$gte'	=> $access_ary[$s_accountrole])
);

$docs = $elas_mongo->docs->find($find);

$h1 = 'Documenten';
$fa = 'files-o';

include $rootpath . 'includes/inc_header.php';

echo '<div class="panel panel-info">';
echo '<div class="panel-heading">';

echo '<form method="get">';
echo '<div class="row">';
echo '<div class="col-xs-12">';
echo '<div class="input-group">';
echo '<span class="input-group-addon">';
echo '<i class="fa fa-search"></i>';
echo '</span>';
echo '<input type="text" class="form-control" id="q" name="q" value="' . $q . '">';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</form>';

echo '</div>';
echo '</div>';

echo '<div class="table-responsive">';
echo '<table class="table table-bordered table-striped table-hover footable"';
echo ' data-filter="#q" data-filter-minimum="1">';
echo '<thead>';

echo '<tr>';
echo '<th data-sort-initial="true">Naam</th>';
echo '<th data-hide="phone, tablet">Tijdstip</th>';
echo ($s_guest) ? '' : '<th data-hide="phone, tablet">Zichtbaarheid</th>';
echo ($s_admin) ? '<th data-hide="phone, tablet" data-sort-ignore="true">Verwijderen</th>' : '';
echo '</tr>';

echo '</thead>';
echo '<tbody>';

foreach($docs as $val)
{
	$access = $acc_ary[$val['access']];
	echo '<tr>';

	echo '<td>';
	echo '<a href="https://s3.eu-central-1.amazonaws.com/' . $bucket . '/' . $val['filename'] . '" target="_self">';
	echo ($val['name']) ?: $val['org_filename'];
	echo '</a>';
	echo '</td>';
	echo '<td>' . $val['ts'] . '</td>';

	if (!$s_guest)
	{
		echo '<td>';
		echo '<span class="label label-' . $access[1] . '">' . $access[0] . '</span>';
		echo '</td>';
	}

	if ($s_admin)
	{
		echo '<td><a href="'. $rootpath . 'docs.php?del=' . $val['_id'] . '" class="btn btn-danger btn-xs">';
		echo '<i class="fa fa-times"></i> Verwijderen</a></td>';
	}
	echo '</tr>';

}
echo '</tbody>';
echo '</table>';

if ($s_admin)
{
	echo '<form method="post" class="form-horizontal" enctype="multipart/form-data">';

	echo '<div class="panel panel-info">';
	echo '<div class="panel-heading">';

	echo '<h3><span class="label label-default">Admin</span> Nieuw document opladen</h3>';

	echo '<div class="form-group">';
	echo '<label for="file" class="col-sm-2 control-label">Bestand</label>';
	echo '<div class="col-sm-10">';
	echo '<input type="file" class="form-control" id="file" name="file" ';
	echo 'required>';
	echo '</div>';
	echo '</div>';

	echo '<div class="form-group">';
	echo '<label for="name" class="col-sm-2 control-label">Naam (optioneel)</label>';
	echo '<div class="col-sm-10">';
	echo '<input type="text" class="form-control" id="name" name="name">';
	echo '</div>';
	echo '</div>';

	echo '<div class="form-group">';
	echo '<label for="access" class="col-sm-2 control-label">Zichtbaar</label>';
	echo '<div class="col-sm-10">';
	echo '<select type="file" class="form-control" id="access" name="access" ';
	echo 'required>';
	render_select_options($access_options, 0);
	echo '</select>';
	echo '</div>';
	echo '</div>';

	echo '<input type="submit" name="zend" value="Opladen" class="btn btn-default">';
	echo '<input type="hidden" value="' . $token . '" name="token">';

	echo '</div>';
	echo '</div>';
	echo '</form>';

	echo '</div>';
	echo '</div>';
	echo '</div>';
}

include $rootpath . 'includes/inc_footer.php';

