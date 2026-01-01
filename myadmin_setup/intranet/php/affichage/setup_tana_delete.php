<?
/*
*	@cb41000@
*
*	08/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	08/12/2008 BBX : modifications pour le CB 4.1
*	=> Utilisation de la classe DatabaseConnection
*	=> Utilisation des nouvelles constantes
*	=> Gestion du produit
*
*	31/03/2009 BBX : ajout d'un contôle sur un process
*/
?>
<?
/*
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?
/*
delete gt comprend

1-mise a jour du flag 2=delete dans la table sys_definition_group_table
2-applique la fonction deploiement sur le group table
4-delete dans la table sys_definition_group_table la ligne du group table

20 07 2006 MD - Modif.  pour adv kpi ligne 51
25 08 2006 XC - Modif.  ajout de la condition where family='$family' pour la récupération de l'id_group_table ligne 38

//*/

session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Librairies et classes nécessaires
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/ProfileModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/UserModel.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'/php/deploy_and_compute_functions.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/class/deploy.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'/php/traitement_chaines_de_caracteres.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'/php/edw_function_family.php');

$tana=$_GET['tana'];
$table=$_GET['table'];
$family=$_GET["family"];
$product=$_GET['product'];

// Connexion à la base de données du produit
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection($product);

// Ajout 31/03/2009 BBX
// On regarde si un process est en cours
$queryProcess = "SELECT * FROM sys_process_encours WHERE encours = 1";
if(count($database->getAll($queryProcess)) > 0) {
	echo 'PROCESS';
	exit;
}

//pour chaque groupe table existant
$query = "SELECT DISTINCT id_ligne FROM sys_definition_group_table WHERE family='$family'";
foreach($database->getAll($query) as $row) {
	$gt[]=$row["id_ligne"];
}

for ($k=0;$k<count($gt);$k++)
{
	if(!get_axe3($family, $product)){
		$query = "UPDATE sys_definition_group_table_network SET deploy_status=2 
		WHERE network_agregation='$tana' AND id_group_table=$gt[$k]";
		$database->execute($query);
	}
	else{
		$lst_na_axe3 = getNaLabelList("na_axe3",$family, $product);
		foreach($lst_na_axe3[$family] as $net=>$val){
			$na = $tana."_".$net;
			$query = "UPDATE sys_definition_group_table_network SET deploy_status=2 
			WHERE network_agregation='$na' AND id_group_table=$gt[$k]";
			$database->execute($query);
		}
	}
	$query = "UPDATE sys_definition_group_table SET raw_deploy_status=3,kpi_deploy_status=3,adv_kpi_deploy_status=3 
	WHERE id_ligne=$gt[$k]";
	$database->execute($query);

        // 19/05/2011 BBX - PARTITIONING -
        // On peut désormais passer une instance de connexion
	$deploy = new deploy($database,$gt[$k]);
	if(count($deploy->types) > 0) $deploy->operate();
	$deploy->display(0);
}
$query = "DELETE FROM sys_definition_network_agregation WHERE agregation='$tana' AND family='$family'";
$database->execute($query);

// 31/03/2009 BBX : echo OK au lieu de redirection car traitement ajax maintenant
echo 'OK';
// Redirection
//header("Location: setup_tana_index.php?family={$family}&product={$product}");
?>
