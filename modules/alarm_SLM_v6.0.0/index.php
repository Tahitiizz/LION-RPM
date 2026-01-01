<?php
	include_once 'class/AlarmList.class.php';
	include_once 'class/AlarmModel.class.php';
?>
<html>

<head>
	<script type="text/javascript" src="js/prototype/prototype.js"> </script>
	<script type="text/javascript" src="js/prototype/window.js"> </script>
	<script type="text/javascript" src="js/prototype/scriptaculous.js"> </script>
	<link href="css/prototype_window/default.css" rel="stylesheet" type="text/css"/>
	<link href="css/prototype_window/alphacube.css" rel="stylesheet" type="text/css"/>

	<script type="text/javascript" src="js/alarm_functions.js"> </script>
	<link rel="stylesheet" href="css/alarm_style.css" type="text/css">
</head>

<body>
	<div style="width:550px">
	<?php

		// Definition d'une nouvelle instance de connexion au modèle de données

		$alarm_model = new AlarmModel('../../libconf/app_conf.php');

		// Définition d'une nouvelle instance de la liste des alarmes et affichage

		$alarm_list = new AlarmList();
		$alarm_list->generateIHM();

	?>
	</div>
</body>