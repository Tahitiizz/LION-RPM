<?
/*
*	@cb41000@
*
*	11/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	11/12/2008 BBX : modifications pour le CB 4.1 :
*	=> Utilisation des nouvelles méthodes et constantes
*	=> Utilisation de la classe de connexion àa la base de données
*/
?>
<?
/*
*	@cb30000@
*
*	20/07/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- maj 25/07/2007 jérémy : 	Modification du lien de redirection après la suppression par requête
*
*	29/01/2009 GHX
*		- modification des requêtes SQL pour mettre schedule_id entre cote au niveau des inserts  [REFONTE CONTEXTE]
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
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Connexion à la base de données locale
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection();

$schedule_id=$_GET['schedule_id'];

// on supprime le rapport
$query = "DELETE FROM sys_report_schedule WHERE schedule_id = '$schedule_id'";
$database->execute($query);

// cascade sur les abonnés  -- c'est pgsql qui devrait faire ça :-\
$query = "DELETE FROM sys_report_sendmail WHERE schedule_id = '$schedule_id'";
$database->execute($query);

header("location:setup_schedule_index.php?nocache=".date('U'));
?>
