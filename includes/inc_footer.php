<?php
echo '</div>';
//echo '<div class="clearer"></div>';
echo '</div>';
echo '</div>';

/*
if ($s_accountrole == 'admin')
{
	echo '<div class="container-fluid">';
	echo '<div class="row">';
	echo '<div class="col-xs-12 bg-info">';
//	echo '<p><b>Support mailinglijst</b>';
//	echo '<ul><li>Inschrijven: support-elas-heroku-subscribe@lists.riseup.net</li>';
//	echo '<li>Berichten posten:  support-elas-heroku@lists.riseup.net</li></ul></p>';
	echo '<p><b>Rapporteer bugs in de <a href="https://github.com/eeemarv/elas-heroku/issues">Github issue tracker</a>.</b> (Maak eerst een <a href="https://github.com">Github</a> account aan.)</p>';
	echo '</div></div></div>';
}


 <div id="footer">
	<div id="footerleft">
	<?php
	if(isset($s_id)){
		echo $s_name." (".trim($s_letscode)."), ";
		echo " <a href='".$rootpath."logout.php'>Uitloggen</a>";
	}
	?>
	</div>
	<div id="footerright">
	<a href="https://github.com/eeemarv/elas-heroku ">eLAS-Heroku</a>
	</div>
</div>**/



echo '<div class="clearfix"></div>';
echo '<div class="container-fluid">';
echo '<footer class="footer">';
echo '<p><a href="https://github.com/eeemarv/elas-heroku">eLAS-Heroku ';			
echo '</a></p></footer>';
echo '</div>'; 

echo '<script src="' . $cdn_jquery . '"></script>';
echo '<script src="' . $cdn_bootstrap_js . '"></script>';
echo '<script src="' . $rootpath . 'js/base.js"></script>';

if (isset($includejs))
{
	echo $includejs;
}

echo '</body>';
echo '</html>';
