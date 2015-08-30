<?php
ob_start();
$rootpath = '../';
$role = 'guest';
require_once $rootpath . 'includes/inc_default.php';
require_once $rootpath . 'includes/inc_mailfunctions.php';

$msgid = $_GET['id'];

if(!isset($msgid))
{
	header('Location: ' . $rootpath . 'messages/overview.php');
	exit;
}

$message = $db->GetRow('SELECT m.*,
		c.id as cid,
		c.fullname as catname
	FROM messages m, categories c
	WHERE m.id = ' . $msgid . '
		AND c.id = m.id_category');

$user = readuser($message['id_user']);

$to = $db->GetOne('select c.value
	from contact c, type_contact tc
	where c.id_type_contact = tc.id
		and c.id_user = ' . $user['id'] . '
		and tc.abbrev = \'mail\'');

$balance = $user['saldo'];

if ($_POST['zend'])
{
	$content = $_POST['content'];
	$cc = $_POST['cc'];

	$systemtag = readconfigfromdb('systemtag');

	$me = readuser($s_id);

	$from = $db->GetOne('select c.value
		from contact c, type_contact tc
		where c.id_type_contact = tc.id
			and c.id_user = ' . $s_id . '
			and tc.abbrev = \'mail\'');

	$my_contacts = $db->GetArray('select c.value, tc.abbrev
		from contact c, type_contact tc
		where c.flag_public = 1
			and c.id_user = ' . $s_id . '
			and c.id_type_contact = tc.id');

	$va = ($message['msg_type']) ? 'aanbod' : 'vraag';

    $subject = '[eLAS-' . $systemtag . '] - Reactie op je ' . $va . ' ' . $message['content'];

	if($cc)
	{
		$to =  $to . ', ' . $from;
	}

	$mailcontent = 'Beste ' . $user['fullname'] . "\r\n\n";
	$mailcontent .= '-- ' . $me['fullname'] . ' heeft een reactie op je ' . $va . " verstuurd via eLAS --\r\n\n";
	$mailcontent .= $content . "\n\n";
	$mailcontent .= "Om te antwoorden kan je gewoon reply kiezen of de contactgegevens hieronder gebruiken\n";
	$mailcontent .= 'Contactgegevens van ' . $me['fullname'] . ":\n";

	foreach($my_contacts as $value)
	{
		$mailcontent .= '* ' . $value['abbrev'] . "\t" . $value['value'] ."\n";
	}

	if ($content)
	{
		$status = sendemail($from, $to, $subject, $mailcontent, 1);

		if ($status)
		{
			$alert->error($status);
		}
		else
		{
			$alert->success('Mail verzonden.');
			$content = '';
		}
	}
	else
	{
		$alert->error('Fout: leeg bericht. Mail niet verzonden.');
	}
}

$msgpictures = $db->GetArray('select * from msgpictures where msgid = ' . $msgid);
$currency = readconfigfromdb('currency');

$title = $message['content'];

$contacts = $db->GetArray('select c.*, tc.abbrev
	from contact c, type_contact tc
	where c.id_type_contact = tc.id
		and c.id_user = ' . $user['id'] . '
		and c.flag_public = 1');

$includejs = '<script src="' . $cdn_jssor_slider_mini_js . '"></script>
	<script src="' . $cdn_jquery_ui_widget . '"></script>
	<script src="' . $cdn_load_image . '"></script>
	<script src="' . $cdn_canvas_to_blob . '"></script>
	<script src="' . $cdn_jquery_iframe_transport . '"></script>
	<script src="' . $cdn_jquery_fileupload . '"></script>
	<script src="' . $cdn_jquery_fileupload_process . '"></script>
	<script src="' . $cdn_jquery_fileupload_image . '"></script>
	<script src="' . $cdn_jquery_fileupload_validate . '"></script>
	<script src="' . $rootpath . 'js/msg_view.js"></script>';

$top_buttons = '';

if ($s_accountrole == 'user' || $s_accountrole == 'admin')
{
	$top_buttons .= '<a href="' . $rootpath . 'messages/edit.php?mode=new" class="btn btn-success"';
	$top_buttons .= ' title="Vraag of aanbod toevoegen"><i class="fa fa-plus"></i>';
	$top_buttons .= '<span class="hidden-xs hidden-sm"> Toevoegen</span></a>';

	if ($s_accountrole == 'admin' || $s_id == $message['id_user'])
	{
		$top_buttons .= '<a href="' . $rootpath . 'messages/edit.php?mode=edit&id=' . $msgid . '" ';
		$top_buttons .= 'class="btn btn-primary"';
		$top_buttons .= ' title="Vraag of aanbod aanpassen"><i class="fa fa-pencil"></i>';
		$top_buttons .= '<span class="hidden-xs hidden-sm"> Aanpassen</span></a>';

		$top_buttons .= '<a href="' . $rootpath . 'messages/delete.php?id=' . $msgid . '" ';
		$top_buttons .= 'class="btn btn-danger"';
		$top_buttons .= ' title="Vraag of aanbod verwijderen"><i class="fa fa-times"></i>';
		$top_buttons .= '<span class="hidden-xs hidden-sm"> Verwijderen</span></a>';
	}

	if ($message['msg_type'] == 1 && $s_id != $message['id_user'])
	{
		$top_buttons .= '<a href="' . $rootpath . 'transactions/add.php?mid=' . $msgid . '" class="btn btn-warning"';
		$top_buttons .= ' title="Transactie voor dit aanbod toevoegen"><i class="fa fa-exchange"></i>';
		$top_buttons .= '<span class="hidden-xs hidden-sm"> Transactie</span></a>';
	}

	$top_buttons .= '<a href="' . $rootpath . 'messages/overview.php" class="btn btn-default"';
	$top_buttons .= ' title="Alle Vraag en aanbod"><i class="fa fa-newspaper-o"></i>';
	$top_buttons .= '<span class="hidden-xs hidden-sm"> Lijst</span></a>';

	$top_buttons .= '<a href="' . $rootpath . 'userdetails/mymsg_overview.php" class="btn btn-default"';
	$top_buttons .= ' title="Mijn vraag en aanbod"><i class="fa fa-newspaper-o"></i>';
	$top_buttons .= '<span class="hidden-xs hidden-sm"> Mijn vraag en aanbod</span></a>';
}

$h1 = ($message['msg_type']) ? 'Aanbod' : 'Vraag';
$h1 .= ': ' . htmlspecialchars($message['content'], ENT_QUOTES);
$fa = 'newspaper-o';

include $rootpath.'includes/inc_header.php';

echo '<div class="row">';

if($s_accountrole == "admin" || $s_id == $user['id'])
{
	$add_img = '<div class="upload-wrapper">
<div id="error_output"></div>
    <div id="files" class="files"></div>
</div>';
	// $btn_add_img = "<script type='text/javascript'>function AddPic () { OpenTBox('" . $myurl ."'); } </script>";
	$add_img .= '<input type="file" name="files[]" class="btn btn-success" ';
	$add_img .= 'title="Afbeelding toevoegen" multiple id="fileupload">';
//	$add_img .= '<i class="fa fa-plus"></i>';
//	$add_img .= '<span class="hidden-xs hidden-sm"> Afbeelding toevoegen</span>';
}

$add_img = ($add_img) ? '<p>' . $add_img . '</p>' : '';

if ($msgpictures)
{
	echo '<div class="col-md-6">';
	echo '<div class="col-lg-8 col-lg-offset-2 text-center">';
	echo '<div id="slider1_container" style="position: relative; 
					top: 0px; left: 0px; width: 600px; height: 300px;">';
	echo '<div u="slides" style="cursor: move; position: absolute;
						overflow: hidden; left: 0px; top: 0px; width: 600px; height: 300px;">';

	foreach ($msgpictures as $key => $value)
	{
		$file = $value['PictureFile'];
		$url = 'https://s3.eu-central-1.amazonaws.com/' . getenv('S3_BUCKET') . '/' . $file;
		echo '<div><img u="image" src="' . $url . '" /></div>';
	}

	echo '</div>';

	echo '<div u="navigator" class="jssorb01" style="bottom: 16px; right: 10px;">';
	echo '<div u="prototype"></div>';
	echo '</div>';

	echo '<span u="arrowleft" class="jssora02l" style="top: 123px; left: 8px;"></span>';
	echo '<span u="arrowright" class="jssora02r" style="top: 123px; right: 8px;"></span>';

	echo '</div></div>';
	echo $add_img;
	echo '</div>';

	echo '<div class="col-md-6">';
}
else
{
	echo '<div class="col-md-12">';
	echo '<div id="slider1_container"></div>';
	$str = ($message['msg_type']) ? ' dit aanbod' : ' deze vraag';
	echo '<p>Er zijn geen afbeeldingen voor ' . $str . '.</p>';
	echo $add_img;
}	

echo '<div class="panel panel-default">';
echo '<div class="panel-body">';

if (!empty($message['Description']))
{
	echo nl2br(htmlspecialchars($message['Description'],ENT_QUOTES));
}
else
{
	echo '<i>Er werd geen omschrijving ingegeven.</i>';
}

echo '</div>';
echo '</div>';

echo '<dl class="dl-horizontal">';
echo '<dt>';
echo '(Richt)prijs';
echo '</dt>';
echo '<dd>';
$units = ($message['units']) ? ' per ' . $message['units'] : '';
echo (empty($message['amount'])) ? 'niet opgegeven.' : $message['amount'] . ' ' . $currency . $units;
echo '</dd>';

echo '<dt>Van gebruiker: ';
echo '</dt>';
echo '<dd>';
echo '<a href="' . $rootpath . 'memberlist_view.php?id=' . $user['id'] . '">';
echo trim($user['letscode']) . ' ' . htmlspecialchars($user['fullname'],ENT_QUOTES);
echo '</a>';
echo ' (saldo: <span class="label label-default">' . $balance . '</span> ' .$currency . ')';
echo '</dd>';

echo '<dt>Plaats</dt>';
echo '<dd>' . $user['postcode'] . '</dd>';

echo '<dt>Aangemaakt op</dt>';
echo '<dd>' . $message['cdate'] . '</dd>';

echo '<dt>Geldig tot</dt>';
echo '<dd>' . $message['validity'] . '</dd>';

echo '</dl>';

echo '</div>'; //col-md-6
echo '</div>'; //row

echo '<div class="row">';
echo '<div class="col-md-12">';

echo '<h3><i class="fa fa-map-marker"></i> Contactinfo';
echo '</h3>';

echo '<div class="table-responsive">';
echo '<table class="table table-hover table-striped table-bordered footable">';

echo '<thead>';
echo '<tr>';
echo '<th>Type</th>';
echo '<th>Waarde</th>';
echo '</tr>';
echo '</thead>';

echo '<tbody>';

foreach ($contacts as $c)
{
	echo '<tr>';
	echo '<td>' . $c['abbrev'] . '</td>';
	echo '<td>' . htmlspecialchars($c['value'],ENT_QUOTES) . '</td>';
	echo '</tr>';
}

echo '</tbody>';

echo '</table>';
echo '</div>';

echo '</div>';
echo '</div>';

// response form
echo '<div class="panel panel-info">';
echo '<div class="panel-heading">';

echo '<form method="post" class="form-horizontal">';

echo '<div class="form-group">';
echo '<div class="col-sm-12">';
echo '<textarea name="content" rows="6" placeholder="Je reactie naar ' . $user['fullname'] . '" ';
echo 'class="form-control" required';
if(empty($to) || $s_accountrole == 'guest')
{
	echo ' disabled';
}
echo '>' . $content . '</textarea>';
echo '</div>';
echo '</div>';

echo '<div class="form-group">';
echo '<div class="col-sm-12">';
echo '<input type="checkbox" name="cc"';
echo (isset($cc)) ? ' checked="checked"' : '';
echo ' value="1" >Stuur een kopie naar mijzelf';
echo '</div>';
echo '</div>';

echo '<input type="submit" name="zend" value="Versturen" class="btn btn-default"';
if(empty($to) || $s_accountrole == 'guest')
{
	echo ' disabled';
}
echo '>';
echo '</form>';

echo '</div>';
echo '</div>';
echo '</div>';

include $rootpath . 'includes/inc_footer.php';
