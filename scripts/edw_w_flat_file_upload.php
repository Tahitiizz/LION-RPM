<?php

/*
  30/11/2009 GHX
  - Reprise des modifs de RBL sur la // des process
  -> Modification de la mise en buffer avec ob_*() car plus simple, plus rapide et moins de risque d'erreur
 */
?>
<?php

/*
 * 	@cb40000@
 *
 * 	14/11/2007 - Copyright Acurio
 *
 * 	Composant de base version cb_4.0.0.00
 *
 * 	- maj 27/02/2009 CCT1 : les fichiers environnement_datawarehouse et environnement_donnees n'existe plus
 * 	- maj 21/03/2008 - Maxime : On récupère le label du module en base pour le Tracelog
 */
?>
<?php

/*
 * 	@cb22014@
 *
 * 	06/07/2007 - Copyright Acurio - JL
 *
 * 	Suppression de la notion de grouptable
 * 	Suppression de la table "sys_definition_parser" et "..._parser_ref" : informations devenue inutiles
 * 	Création de l'objet "parser" (instance de parser_upload) qui permet une généricité au niveau des différents parser
 * 	Déplacement de ce fichier source du rép.      /home/cbXXXXX/parser/iu/scripts/      vers le rép.       /home/cbXXXXX/scripts
 *
 */
?>
<?php

/**
 * Fichier qui lance et execute la collecte des données.
 * Il gère également la reprise des fichiers texte lorsque les heures arrivent décalées.
 *
 * @package Retrieve_Parser_IU
 * @author Guillaume Houssay
 * @version 3.0.0.01
 * @todo Supprimer la notion de group table ($id_group_table_current) car la collecte des fichiers est indépendante des familles de données. Lien avec le CB qui gère la collecte
 */
// 08/12/2009 BBX : on passe le script en illimité (attention à la facture). BZ 13320
set_time_limit(0);

include_once(dirname(__FILE__) . "/../php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
// maj 27/05/2010 MPR : Ajout de la classe SA Calculation Pour le calcul de Source Availability
include_once($repertoire_physique_niveau0 . "reliability/class/SA_Calculation.class.php");
// maj 27/02/2009 CCT1 : les fichiers environnement_datawarehouse et environnement_donnees n'existe plus
//include_once($repertoire_physique_niveau0 . "php/environnement_datawarehouse.php");
//include_once($repertoire_physique_niveau0 . "php/environnement_donnees.php");
//Inclusion du fichier propre au parser avant celle de la classe générique pour reconnaitre l'instance de "parser"
$module = get_sys_global_parameters("module");
include_once($repertoire_physique_niveau0 . "parser/$module/scripts/flat_file_upload_$module.class.php");

// fichier classe d'upload spécifique à IU notamment mise à jour des dates à partir du R0
include_once($repertoire_physique_niveau0 . "class/flat_file_upload.class.php");
include_once($repertoire_physique_niveau0 . "class/libMail.class.php");
include_once($repertoire_physique_niveau0 . "class/SSHConnection.class.php" );
include_once($repertoire_physique_niveau0 . "class/log/LogFlatFile.class.php" );

// ********** DEBUT DU SCRIPT *****************
printdate();
$depart = time();
list($usec, $sec) = explode(" ", microtime());
$start = ((float) $usec + (float) $sec);

//$id_group_table_current = $group_table_param; //ce parametre contient le group table et vient de la crontab qui definit le group table de la famille et donc de la step qui est lancée.
//$id_group_table_current = 1;
$system_name = get_sys_global_parameters("system_name");

// 14:13 30/11/2009 GHX
// Reprise des modifs de RBL sur la // des process
// > Suppression d'un echo
// local = on est sur le même server
// remote = on est sur on serveur distant et on utilise FTP
// remote_ssh = on est sur on serveur distant et on utilise SSH
// 14:13 30/11/2009 GHX
// Reprise des modifs de RBL sur la // des process
// > Suppression d'un appel de __T

$query = "UPDATE sys_global_parameters set value='0' WHERE parameters='nb_flat_file_uploaded'";
pg_query($database_connection, $query);

// maj 21/03/2008 - Maxime : On récupère le label du module en base pour le Tracelog
$_module = __T('A_TRACELOG_MODULE_LABEL_COLLECT');

sys_log_ast("Info", $system_name, $_module, __T('A_PROCESS_COLLECT_FILES_BEGIN'), "support_1", "");
// 14:13 30/11/2009 GHX
// Reprise des modifs de RBL sur la // des process
// > Suppression d'une requete SQL
// 16:12 30/11/2009 GHX
// Modification de la mis en buffer
// Utilisation du buffer PHP plus simple plus rapide et moins de risque d'erreur
ob_start();
//INITIALISATION des variables et instances
$upload = new retrieve_flat_file();
//$upload->get_parser_properties();
$upload->get_connection_properties();

$upload->get_lib_element_properties();

////////////////////////////////////////////////////////////
//TRAITEMENT
//Suppression des doublons dans la table sys_flat_file_uploaded_list_archive.
$upload->remove_duplicated_rows();
$upload->get_lib_element();
$upload->flat_file_recovery();

// 06/07/2007 : Jérémy ->	Usage de l'instance du parser créée dans "flat_file_upload.class.php"
// 06/07/2007 : Jérémy ->	Ajout du répertoire en paramètre à cause des classes, celui ci n'était plus reconnu
$upload->parser->update_time_data($upload->retrieve_parameters["repertoire_upload_archive"]);

//Recherche des fichiers manquants (non rappatrié : dont la sonde n'a pas encore envoyé les données)
$upload->alarm_result_absence();

$upload->calculateSourceAvailability();

// 14:13 30/11/2009 GHX
// Reprise des modifs de RBL sur la // des process
// > Suppression d'un echo
///////////////////////////////////////////////////////////////////////////////////////////
$arrive = time();
$diff = $arrive - $depart;
__debug("Durée d'éxécution du script : $diff secondes");
//////////////////////////////////////////////////////////////////////////////////////////////

$query = "UPDATE sys_global_parameters set value='$upload->flat_file_treated' WHERE parameters='nb_flat_file_uploaded'";
pg_query($database_connection, $query);
// 14:13 30/11/2009 GHX
// Reprise des modifs de RBL sur la // des process
// > Modification du message
$message = __T('A_PROCESS_COLLECT_FILES_END', $upload->flat_file_treated);
sys_log_ast("Info", $system_name, $_module, $message, "support_1", "");

$upload->flat_file_info->setNbFlatFileTreated($upload->flat_file_treated);
$upload->flat_file_info->calculateNbFilesExpected();
$upload->flat_file_info->log_treatement();
__debug($upload->flat_file_info->getFlatFileInfo());

ob_end_flush();
?>
