<?php
/*
 * Ce fichier est exécuté quand l'utilisateur clique sur le bouton d'ajout de
 * l'alarme dans l'envoi d'e-mail via le bouton en forme d'enveloppe sur les
 * interface qui listes les alarmes.
 *
 * $Author: o.jousset $
 * $Date: 2012-02-06 18:07:49 +0100 (lun., 06 fÃ©vr. 2012) $
 * $Revision: 63556 $
 *
 * - 08/02/2007 CCT1 : Si on ajoute des groupes, on supprime tous les enregistrement de l'alarme dans la table sys_alarm_snmp_sender
 * - 24/08/2007 JL   : Modification du script de rechargement de la page des alarmes (liste)
 * - 29/01/2009 GHX  : Modification des requetes SQL pour mettre l'id_alarm & id_groupe entre cote [REFONTE CONTEXTE]
 * - 18/07/2011 OJT  : DE SMS, les enregistrements dans sys_alarm_snmp_sender sont conservés.
 */

session_start();
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");

// reception des données
$product		= $_POST['product'];
$alarm_id		= $_POST['alarm_id'];
$alarm_type	= $_POST['alarm_type'];
$groups		= explode('||',$_POST['to_groups']);

// On se connecte à la db (18/07/2011, via le singleton)
$db = Database::getConnection( $product );

// on efface les données deja existantes dans la table
$db->execute("DELETE FROM sys_alarm_email_sender WHERE id_alarm='$alarm_id' AND alarm_type='$alarm_type'");

// on ajoute une ligne pour chaque groupe abonné
foreach ($groups as $g)
{
	// 29/01/2009 GHX
	// on n'ajoute pas les groupes avec un id vide
	if ( empty($g) ) continue;

	$query = "
		INSERT INTO sys_alarm_email_sender
			(id_alarm,id_group,time_aggregation,alarm_type)
			VALUES
			('$alarm_id','$g',(SELECT DISTINCT time FROM sys_definition_$alarm_type WHERE alarm_id='$alarm_id'),'$alarm_type')";
	$db->execute($query);
}

$debug = false;
if ( $debug )
{
    echo "<link rel='stylesheet' href='../../../../css/global_interface.css' type='text/css'/>";
    echo $db->displayQueries();
}
else
{
    // on ferme la fenetre et on recharge la page principale.
    //24/08/2007 - JL : Modification du script de rechargement de la page des alarmes (liste)
    echo '<script language="JavaScript">window.opener.location.reload(); window.close();</script>';
}
