  </div>
  <div class="clearer"></div>
 </div>
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
	<?php
	if($configuration["hosting"]["enabled"] == 1) {
		echo "[Hosting by <a href='" . $provider->providerurl . "'>" .$provider->providername ."</a>] ";
	}	
	?>

	<script type='text/javascript'>
	function OpenAboutBox() {
		TINY.box.show({url:'about.php', fixed:false,width:0})
	}
	</script>

	<?php
	echo "<a href='javascript: OpenAboutBox();'>eLAS v" .$elasversion ."</a>";
	?>
	</div>
</div>

<!-- Include Piwik tracking code -->
<?php
if($configuration["hosting"]["enabled"] == 1) {
	require_once($rootpath."includes/inc_piwik.php");
}
?>


<?php
echo "<!-- Generated on " .gethostname() . " -->";
?>
</body>
</html>
