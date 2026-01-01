<?
/*
*	@cb30000@
*
*	24/07/2007 - Copyright Acurio
*
*	Composant de base version cb_3.0.0.0
*	24/07/2007 - Jérémy - Modification du header suite à la suppression de l'iframe dans  " setup_group_index.php " 
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
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

ce script supprime un group d'utilisateurs

	- maj 23/05/2006 sls : cascade la suppression du groupe sur les alarmes

*/


session_start();
include_once($repertoire_physique_niveau0."php/environnement_liens.php");
include_once($repertoire_physique_niveau0."php/database_connection.php");
//require_once($repertoire_physique_niveau0."scripts/deploy.class.php");

$id_group=intval($_GET['id_group']);

// On supprime le groupe
$query="delete from sys_user_group where id_group=$id_group";
pg_exec($database_connection,$query);

// On cascade sur les groupes abonnés aux rapports -- c'est pgsql qui devrait faire ça :-\
$query="delete from sys_report_sendmail where mailto=$id_group and mailto_type='group'";
pg_exec($database_connection,$query);

// On cascade sur les alarmes -- idem, ça devrait être une cascade de pgsql
$query = "delete from sys_alarm_email_sender where id_group=$id_group";
pg_exec($database_connection,$query);


header("location:setup_group_index.php?nocache=".date('U'));

?>
