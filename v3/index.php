<?php
include('php/snippets/authentication.php');
include('php/classes/db_classes.php');
include('php/snippets/constants.php');
$db = new DB(SERVER,USER,PW,DB);
$info = $db->query("SELECT client FROM clients");
$clients = array();
foreach ($info['rows'] as $row){
	array_push($clients,$row['client']);
}
$db->close();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
		<meta name="robots" content="noindex,nofollow" />
		<title>Form Test</title>
		<script type='text/javascript' src='js/all.php'></script>
		<script type='text/javascript'>
		
		// This line needs to be configured for your local machine. Probably should add a separate config file to facilitate collaboration
			var root = "<?php echo ($_SERVER['DOCUMENT_ROOT'] == "C:/xampp/htdocs" ? "http://localhost/seotracker" : "http://oozlemedia.net/seo"); ?>";
			$(document).ready(function(){
				var spreadsheet = new Spreadsheet();
			});
		</script>
		<link rel='stylesheet' href='css/seo.css' type='text/css'/>
    </head>
    <body>
		<div class='logo'><img src='http://www.oozlemedia.com/wp-content/themes/oozle/images/logo.png'/></div>
		<div id='outputInfo'>
			<p class='num_articles'></p>
			<p class='total_cost'></p>
		</div>
		<p id='selectors'>Month: <select id='month'><?php
			echo date("F",time());
			for ($i=2;$i<14;$i++){
				$month = date("F",mktime(0,0,0,$i,0,0));
				echo "<option".($month == date("F",time()) ? " selected='selected'" : "").">".$month."</option>";
			}
			?></select>
		Year: <select id='year'><?php for ($i=2010;$i<=date("Y",time() + 63113852);$i++) echo "<option".($i==date("Y",time()) ? " selected='selected'" : "").">".$i."</option>"; ?></select>
		Client: <select id='clientSelect'><option></option><?php foreach ($clients as $client){echo "<option>".$client."</option>";} ?></select></p>
		<div id='ajaxLoading'><img src='css/ajax-loader.gif'/></div>
		<button id='export'>Export to CSV</button>
		<button id='importKeywords'>Import Keywords</button>
		<input type='text' id='showVals'/>
		<table id='outputtable'></table>
		<div id='controls'>
			<p style='display:inline-block;float:left;'><button class='addRowsButton'>Add</button> <input class='addRowsUpDown' type='number' min='1' max='20' value='1' step='1'/> rows</p>
			<p id='saveStuff' style='display:inline-block;float:right;'><span id='saveOutput'></span><input type='button' value='Save Now' id='saveButton'/><input type='button' value='Turn Off Autosave' class='turnOffAutosaveButton'/></p>
		</div>
		<ol id='output' size='5'></ol>
    </body>
</html>