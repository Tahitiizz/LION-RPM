<html>

<head>
	<script type="text/javascript" src="../js/prototype/prototype.js"> </script>
	<script type="text/javascript" src="../js/prototype/window.js"> </script>
	<script type="text/javascript" src="../js/prototype/scriptaculous.js"> </script>
	
	<link href="../css/prototype_window/default.css" rel="stylesheet" type="text/css"/>
	<link href="../css/prototype_window/alphacube.css" rel="stylesheet" type="text/css"/>

	<script type="text/javascript" src="../js/alarm_functions.js"> </script>
	<script type="text/javascript" src="../js/tab-view.js"> </script>
	<script type="text/javascript" src="../js/tab-view-ajax.js"> </script>		
	<script type="text/javascript" src="../js/networkElementSelection.js"></script>
	
	<link rel="stylesheet" href="../css/alarm_style.css" type="text/css">
	<link rel="stylesheet" href="../css/tab-view.css" type="text/css">
	<link href="../css/networkElementSelection.css" rel="stylesheet" type="text/css">
</head>

<body>
	<table class="tabPrincipal" width="600px" cellpadding="5" cellspacing="5" align="center">
		<tr>
			<td>
			<?php

				include_once '../class/AlarmSetup.class.php';

				$_alarm_id = ((isset($_GET['alarm_id'])) ? $_GET['alarm_id'] : '');

				$alarm_setup = new AlarmSetup($_GET['family'], $_GET['alarm_type'], $_alarm_id);

				$alarm_setup->generateIHM();

				// On appelle ici la selection des na puisque celle-ci doit être défini une fois le code HTML généré

				$alarm_setup->setNetworkElementSelection('img_select_na');

			?>
			</td>
		</tr>
	</table>
</body>
</html>