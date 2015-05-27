<?php
ob_start();
$rootpath = '../';
$role = 'user';
require_once $rootpath . 'includes/inc_default.php';
require_once $rootpath . 'includes/inc_adoconnection.php';

/*
$msg_orderby =  (isset($_GET["msg_orderby"])) ? $_GET["msg_orderby"] : "messages.id";

$user_filterby = $_GET["user_filterby"];

$date = date('Y-m-d');

$query = "SELECT *, ";
$query .= " messages.id AS msgid, ";
$query .= " users.id AS userid, ";
$query .= " categories.id AS catid, ";
$query .= " categories.fullname AS catname, ";
$query .= " users.name AS username, ";
$query .= " users.letscode AS letscode, ";
$query .= " messages.validity AS valdate, ";
$query .= " messages.cdate AS date ";
$query .= " FROM messages, users, categories ";
$query .= "  WHERE messages.id_user = users.id ";

$query .= " AND messages.id_category = categories.id";

if (isset($user_filterby)){
	switch ($user_filterby) {
		case "expired":
			$query .= " AND messages.validity < " ."'" .$date ."'";
			break;
		case "valid":
			$query .= " AND messages.validity >= " ."'" .$date ."'";
			break;
	}
}

if (isset($msg_orderby))
{
		$query .= " ORDER BY ".$msg_orderby. " ";
}
else
{
		$query .= " ORDER BY messages.msg_type,letscode ";
}
*/
$msgs = $db->GetArray('select m.*,
		c.id as cid, c.fullname as cat,
		u.letscode, u.fullname, u.id as uid
	from messages m, categories c, users u
	where m.id_category = c.id
		and m.id_user = u.id
	order by id desc');

$top_buttons = '<a href="' . $rootpath . 'messages/edit.php?mode=new" class="btn btn-success"';
$top_buttons .= ' title="Vraag of aanbod toevoegen"><i class="fa fa-plus"></i>';
$top_buttons .= '<span class="hidden-xs hidden-sm"> Toevoegen</span></a>';

include $rootpath . 'includes/inc_header.php';

if ($s_accountrole == 'admin')
{
	echo '<div class="pull-right hidden-xs">';
	echo '<a href="' . $rootpath . 'export_messages.php';
	echo '" target="new">';
	echo '<i class="fa fa-file"></i>';
	echo '&nbsp;csv</a>';
	echo '</div>';
}

echo '<h1><i class="fa fa-leanpub"></i> Vraag & Aanbod</h1>';

echo '<ul class="nav nav-tabs">';
echo '<li class="active"><a href="#" class="bg-white">Alle</a></li>';
echo '<li class="active"><input type="text" class="search"></li>';
echo '<li><a href="#" class="bg-white">Geldig</a></li>';
echo '<li><a href="#" class="bg-danger">Vervallen</a></li>';
echo '</ul>';

/*
echo "<br>Filter: ";
echo "<a href='overview.php?user_filterby=all'>Alle</a>";
echo " - ";
echo "<a href='overview.php?user_filterby=expired'>Vervallen</a>";
echo " - ";
echo "<a href='overview.php?user_filterby=valid'>Geldig</a>";
*/

echo '<div class="table-responsive">';
echo '<table class="table table-hover table-striped table-bordered footable">';
echo '<thead>';
echo '<tr>';
echo "<th>V/A</th>";
echo "<th>Wat</th>";
echo '<th data-hide="phone, tablet">Geldig tot</th>';
echo '<th data-hide="phone, tablet">Wie</th>';
echo '<th data-hide="phone, tablet">Categorie</th>';
if ($s_accountrole == 'admin')
{
	echo '<th data-hide="phone, tablet" data-sort-ignore="true">Verlengen</th>';
}
echo '</tr>';
echo '</thead>';

echo '<tbody>';

foreach($msgs as $msg)
{
	$del = (strtotime($msg['validity']) < time()) ? true : false;

	echo '<tr';
	echo ($del) ? ' class="danger"' : '';
	echo '>';
	echo '<td>';

	echo ($msg["msg_type"]) ? 'A' : 'V';
	echo '</td>';

	echo '<td>';
	echo '<a href="' .$rootpath . 'messages/view.php?id=' . $msg['id']. '">';
	echo htmlspecialchars($msg['content'],ENT_QUOTES);
	echo '</a>';
	echo '</td>';

	echo '<td>';
	echo $msg['validity'];
	echo '</td>';

	echo '<td>';
	echo '<a href="' . $rootpath . 'memberlist_view.php?id=' . $msg['uid'] . '">';
	echo htmlspecialchars($msg['letscode'] . ' ' . $msg['fullname'], ENT_QUOTES);
	echo '</a>';
	echo '</td>';

	echo '<td>';
	echo '<a href="' . $rootpath . 'searchcat_viewcat.php?id=' . $msg['cid'] . '">';
	echo htmlspecialchars($msg['cat'],ENT_QUOTES);
	echo '</a>';
	echo '</td>';

	if ($s_accountrole == 'admin')
	{
		echo '<td>';
		echo '<a href="' . $rootpath . 'messages/extend.php?id=' . $msg['id'] . '&validity=12" class="btn btn-default btn-xs">';
		echo '1 jaar</a>&nbsp;';
		echo '<a href="' . $rootpath . 'messages/extend.php?id=' . $msg['id'] . '&validity=60" class="btn btn-default btn-xs">';
		echo '5 jaar</a>';
		echo '</td>';
	}

	echo '</tr>';
}

echo '</tbody>';
echo '</table>';
echo '</div>';

include $rootpath . 'includes/inc_footer.php';

