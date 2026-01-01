<?
/*
*	@cb50000@
*
*	18/06/2009 BBX :
*		=> Constantes CB 5.0
*		=> Header CB 5.0
*
 * 01/03/2011 MMT bz 19128 utilisation de la nouvelle classe alarmMailPdf
*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?
	/*
		dernière modif 03 01 2006 christophe
		- maj 07 03 2006 christophe : inhibition de l'envoie des mails en fonction de la variable automatic_email_activation de sys_global_parameters
	 *
	*/
	/*
		Permet de lancer l'envoie des mails avec les rapports PDF d'alarmes.
	*/

	// A virer pr la livraison
	
	include_once(dirname(__FILE__)."/../php/environnement_liens.php");
	include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
	include_once(REP_PHYSIQUE_NIVEAU_0 . "class/alarmDisplayCreate.class.php");	// Génère le tableau des alarmes et l'enregistre dans sys_contenu_buffer
	include_once(REP_PHYSIQUE_NIVEAU_0 . "class/alarmPDFFileCreate.class.php");	// énère les fichiers PDF à envoyer.
	include_once(REP_PHYSIQUE_NIVEAU_0 . "class/libMail.class.php");				// Gestion de l'envoie des mails
	include_once(REP_PHYSIQUE_NIVEAU_0 . "class/alarmMailPdf.class.php");	// envoie les mails par groupe / user  avec les PDF

	// Header
	$arborescence = 'Alarm Mail PDF';
	include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');
?>
	<div id="container" style="width:100%;text-align:center">
	<div class=texteGris>
<?

	$automatic_email_activation = get_sys_global_parameters("automatic_email_activation");
	// L'envoie des mails avec les fichiers PDF ne se fait que si la variable de sys_global_parameters automatic_email_activation = 1
	if ($automatic_email_activation == 1) {
	
		// on se connecte à la db
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
		$db = Database::getConnection();

		$offset_day = $db->getone("select value from sys_global_parameters where parameters='offset_day' ");
		
		//01/03/2011 MMT bz 19128 utilisation de la nouvelle classe alarmMailPdf
		$alarmMail = new alarmMailPdf($offset_day);
		$alarmMail->sendMails($alarmMail->getAlarms());
	} else {
		echo "<font style='color:#FF0000; font-style:bold;'>La variable automatic_email_activation est égale à 0  l'envoi des mails des alarmes avec PDF est inhibé.</font>";
	}
?>
	</div>
	</div>
	</body>
	</html>
