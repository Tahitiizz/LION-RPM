<?
/*
*	@cb50000@
*
*	23/06/2009 - Copyright Astellia
*
*	Composant de base version 5.0.0.00
*
*	=> BBX : Adaptation du script pour le CB 5.0
*/
?>
<?
/*
	Affiche les alarmes ayant trop de résultats.
	Le fichier est appelé depuis la page de visualisation des alarmes : managemnt ou history.
*/
session_start();

// INCLUDES.
include_once(dirname(__FILE__)	. "/../../../../php/environnement_liens.php");

// Permet de rediriger l'utilisateur sur la page d'accueil de l'apllication si la session est perdue.
if ($repertoire_physique_niveau0 == ""){
	echo ("Session time expired.");
	exit;
}	

// Connexion à la base de données locale
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
$db = Database::getConnection();

// Récupération des alarmes du user
$query = "SELECT object_source FROM sys_contenu_buffer
WHERE id_user = '".$_SESSION['id_user']."'
AND object_type = 'alarm_too_much_result'";

// Affichage des résultats
$html = "No result.";
$result = $db->getRow($query);
if(count($result) > 0) {
	$html = $result['object_source'];
}

// DEBUT PAGE
$arborescence = 'Alarm error';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');
?>
<link rel="stylesheet" href="<?=$niveau0?>css/alarm_display.css" type="text/css">
<div id="container" style="width:100%;text-align:center">
	<?=$html?>
	<div style="margin-top:10px;">
		<input type="button" value="Close" name="close" onclick="window.close()" class="bouton" />
	</div>
</div>
</body>
</html>
