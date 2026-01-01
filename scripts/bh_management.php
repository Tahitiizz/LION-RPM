<?
/*
*	@cb50000@
*
*	27/07/2009 - Copyright Astellia
*
*	27/07/2009 BBX : adaptation du script pour le CB 5.0
*
*
*	04/09/2009 GHX
*		- Correction du BZ [REC][T&A OMC Motorola][BH]Lors du déploiement de la BH le compute ne fonctionne plus
*			-> Mise à jour de la séquence avant déploiement
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
  * 28/08/2006 - creation du fichier (MD)
  *   - Ce script permet de deployer et de supprimer en base les tables de type Busy Hour
  *   - Il permet egalement d'activer ou non le calcul lie a la Busy Hour
  *
  * Liste des actions possibles
  * - deploy : deploie les tables de type BH pour une famille donnee
  * - delete : supprime les tables de type BH pour une famille donnee
  * - on : active le calcul de la BH
  * - off : desactive le calcul de la BH
  *
  * Avertissement : le script autorise l'action 'deploy'  meme si les tables sont deja deployees en base
  * Dans ce cas de figure des "index" supplémentaires sont generes pour les tables existantes
  * Lors de l'appel du script avec l'action deploy, il est conseille de verifier que la BH n'est pas deja deployee
  * L'appel successif a l'action delete, on ou off ne pose aucun probleme.
  *
  * Exemple d'utilisation :
  * 	>> navigateur : .../bh_management.php?action=deploy?family=ept
  *	>> ligne de commande : php -q bh_management.php delete ept
  *
  * Remarque : l'argument family peut prendre la valeur 'all', dans ce cas l'action est appliquee a toutes les familles
  *
  * 03 10 2006 : MD - deploiement de la BH pour le type adv_kpi (desactivation du calcul)
  * 05 10 2006 : MD - suppression deploiement BH pour adv_kpi car adv_kpi non livre pour cb2000
  */
  
// Scripts et classes nécessaires
include_once dirname(__FILE__).'/../php/environnement_liens.php';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/deploy.class.php');

// Paramètres
$family = '';
$action = '';
$product = '';

// Récupération des paramètres passés par URL
if(isset($_GET['action'])) $action=$_GET['action'];
if(isset($_GET['family'])) $family=$_GET['family'];
if(isset($_GET['product'])) $product=$_GET['product'];

// Récupération des paramètres passés par argument de commande
if($argc==4) {
	$action=$argv[1];
	$family=$argv[2];
	$product=$argv[3];
}

// Instance de connexion à la base de données du produit concerné
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
$database = Database::getConnection($product);

//active ou desactive le mode debug
//a recuperer en base
$debug_bh_management=true;

/*************************************** FUNCTION LIST **************************************/

/* Retourne les groupes de table appartenant a la famille donnee
  * @param family : un nom de famille
  * @return un tableau contenant les id de groupes de table de la famille passee en parametre
  */
function getIdGroupTable($family)
{
	global $database;
	$groups = Array();
	$where = ($family=="all")? "" : "WHERE family='$family'";
	$query = "SELECT id_ligne FROM sys_definition_group_table $where ORDER BY id_ligne";
	$result = $database->execute($query);
	while($row = $database->getQueryResults($result,1))
		$groups[] = $row['id_ligne'];
	return $groups;
}

/* Retourne les TA de la table sys_definition_time_agregation
  * Les TA retournes sont de type 'bh' et sont accompagnes de leur TA source
  * @return tableau associatif de la forme TA=>TA_source
  */
function getTimesSrcBH()
{
	global $database;
	$times = Array();
	$query = "SELECT agregation,source_default FROM sys_definition_time_agregation 
	WHERE bh_type='bh' ORDER BY agregation_rank";
	$result = $database->execute($query);
	while($row = $database->getQueryResults($result,1))
		$times[$row['agregation']]=$row['source_default'];
	return $times;
}

/* Affiche les elements du tableau passe en parametre
  * @param $values - un tableau de valeurs a afficher
  */
function display($values)
{
	foreach ($values as $v)
		print $v.'<br/>';
}

/* Deploie les tables de type Busy Hour pour le groupe de table $group_id
  * @param $group_id l'id du groupe de table que l'on cherche a deployer
  * @param $data_type un tableau contenant les noms des types de donnees a deployer pour la bh
  * @param $status 1 = deploiement des tables, 2 = suppression des tables
  * @param $display 1 pour afficher le detail du deploiement et 0 pour ne rien afficher
  * @return vrai si le deploiement c'est bien passe et faux sinon
  */
function deploy_bh($group_id,$data_type,$status,$display)
{
	global $database,$product;
	$res = false;
	$queries = Array();

	// Parametrage du deploiement pour chaque type de donnees
	foreach($data_type as $dt) 
	{
		$queries[] = "UPDATE sys_definition_group_table
		SET ".$dt."_deploy_status=3
		WHERE id_ligne=$group_id";

		$queries[] = "UPDATE sys_definition_group_table_time
		SET deploy_status=$status
		WHERE data_type='$dt' AND
		id_group_table=$group_id AND
		time_agregation IN (SELECT agregation FROM sys_definition_time_agregation WHERE bh_type='bh')";
	}

	if(count($queries)>0)
	{
		// Affichage des requêtes
		if($display) {
			foreach($queries as $q)
				print $q.'<br/>';
		}
		// Exécution des requêtes
		if($database->execute(implode(';',$queries)))
		{
                        // 19/05/2011 BBX - PARTITIONING -
                        // On peut désormais passer une instance de connexion
			$deploy = new deploy($database,$group_id,$product);
			if(count($deploy->types)>0)
			{
				// Genere les requetes liees au deploiement
				$deploy->operate();
				$res = true;
			}
			// Execute et affiche les requetes
			$deploy->display($display);
		}
	}
	return $res;
}

/***************************************DEBUT DU SCRIPT**************************************/

$queries = Array();//contient l'ensemble des requetes a executer pour effectuer l'action
$bh_data_type = Array("raw","kpi");//les types de donnees a initialiser avec la bh

print 'Action : '.$action.'<br/>';
print 'Famille : '.$family.'<br/>';
print 'Produit : '.$product.'<br/>';

// 11:00 04/09/2009 GHX
// Correction du BZ 11393
// On met à jour la séquence car risque d'id_ligne en doublons
$queryUpdateSerial = "SELECT setval('sys_definition_group_table_time_id_ligne_seq', (SELECT max(id_ligne) FROM sys_definition_group_table_time));";
$database->execute($queryUpdateSerial);

// Produit vide = master
if(($action != "") && ($family != ""))
{
	switch($action)
	{
		// Deploie et insere les etapes de calcul liees a la famille courante
		case "deploy":
		
			//contient les TA de type 'bh' definis dans sys_definition_time_agregation avec leur niveau source
			$times_bh=getTimesSrcBH();
			$where=($family=="all")?"":"AND id_group_table IN (SELECT id_ligne FROM sys_definition_group_table WHERE family='$family')";
			$queries[count($queries)]="
				DELETE FROM sys_definition_group_table_time
					WHERE  data_type IN ('".implode("','",$bh_data_type)."') AND
						   time_agregation IN ('".implode("','",array_keys($times_bh))."')
						   $where";

			$groups = getIdGroupTable($family);//tous les groupes de table de la famille courante
			print 'Groupes : ';print_r($groups);;print '<br/><br/>';

			for($i=0;$i<count($groups);$i++){//1 seul groupe si il n'y a pas de 3eme axe
				$group_id=$groups[$i];

				foreach($times_bh as $t=>$src){

					foreach($bh_data_type as $dt){//pour chaque type de donnees

						//par defaut mode bh normal (heure la plus chargee sans agregation reseau)
						$id_source="(select id_ligne from sys_definition_group_table_time where time_agregation='$src' and id_group_table=$group_id and data_type='$dt')";

						$queries[]="INSERT INTO sys_definition_group_table_time
							(id_group_table, time_agregation, time_agregation_label, id_source, data_type, on_off, \"comment\", deploy_status)
							VALUES
							($group_id, '$t', '$t',$id_source, '$dt', 0, NULL, 1)";
						//TA desactive par defaut pour empecher le calcul tant qu'une formule n'est pas definie
					}
				}
			}

			$queries[]="UPDATE sys_definition_time_agregation SET on_off=1,visible=1 WHERE bh_type='bh'";

			//activation du menu pour les profile de type admin
			$queries[]="UPDATE menu_deroulant_intranet SET is_profile_ref_admin=1 WHERE libelle_menu='Setup Busy Hour'";
			$queries[]="UPDATE profile SET profile_to_menu=btrim(replace('-'||profile_to_menu||'-','-'||t0.id_menu||'-','-'),'-')
									   FROM (SELECT id_menu FROM menu_deroulant_intranet where libelle_menu='Setup Busy Hour' limit 1) t0
									   WHERE profile_type = 'admin' AND (client_type<>'protected' OR client_type IS NULL)";
			$queries[]="UPDATE profile SET profile_to_menu = profile_to_menu || '-' || (select id_menu from menu_deroulant_intranet where libelle_menu='Setup Busy Hour' LIMIT 1) WHERE profile_type = 'admin' AND (client_type<>'protected' OR client_type IS NULL)";
			$queries[]="DELETE FROM profile_menu_position
										WHERE id_profile IN (select id_profile from profile where profile_type = 'admin' and (client_type<>'protected' OR client_type IS NULL)) AND
											  id_menu IN (select id_menu from menu_deroulant_intranet where libelle_menu='Setup Busy Hour')";
			$queries[]="INSERT INTO profile_menu_position
										SELECT t0.id_menu,
											   t1.id_profile,
											   (select max(position)+1 from profile_menu_position where id_menu_parent=t0.id_menu_parent),
											   t0.id_menu_parent
										FROM menu_deroulant_intranet t0,profile t1
										WHERE profile_type = 'admin' AND (client_type<>'protected' OR client_type IS NULL) AND libelle_menu='Setup Busy Hour'";


			display($queries);

			if($database->execute(implode(";",$queries)))
			{
				//on deploie chaque groupe de table de la famille courante
				foreach ($groups as $group_id)
						deploy_bh($group_id,$bh_data_type,1,$debug_bh_management);
			}

		break;

	case "on"://active le calcul de la BH sauf pour adv_kpi

		$where=($family=="all")?"":"AND id_group_table IN (SELECT id_ligne FROM sys_definition_group_table WHERE family='$family')";
		$query="
			UPDATE sys_definition_group_table_time
			SET on_off=1
			WHERE data_type<>'adv_kpi' AND time_agregation IN (SELECT agregation FROM sys_definition_time_agregation WHERE bh_type='bh') $where";
		print $query.'<br/>';
		$database->execute($query);
		break;

	case "off"://desactive le calcul de la BH

		$where=($family=="all")?"":"AND id_group_table IN (SELECT id_ligne FROM sys_definition_group_table WHERE family='$family')";
		$query="
			UPDATE sys_definition_group_table_time
			SET on_off=0
			WHERE time_agregation IN (SELECT agregation FROM sys_definition_time_agregation WHERE bh_type='bh') $where";
		print $query.'<br/>';
		$database->execute($query);
		break;

	case "delete"://supprime les tables deployees

		$query="";
		if($family=="all")
			$query="UPDATE sys_definition_time_agregation SET on_off=0,visible=0 WHERE bh_type='bh';";

		//on supprime la formule dans la table sys_defintion_time_bh_formula
		$where=($family=="all")?"":"WHERE family='$family'";
		$query.="DELETE FROM sys_definition_time_bh_formula $where ;";

		//desactivation du menu pour les profiles de type admin
		$query.="UPDATE profile SET profile_to_menu=btrim(replace('-'||profile_to_menu||'-','-'||t0.id_menu||'-','-'),'-')
					FROM (SELECT id_menu FROM menu_deroulant_intranet where libelle_menu='Setup Busy Hour' limit 1) t0
					WHERE profile_type = 'admin' AND (client_type<>'protected' OR client_type IS NULL);";
		$query.="DELETE FROM profile_menu_position
					 WHERE id_profile IN (select id_profile from profile where profile_type = 'admin' and (client_type<>'protected' OR client_type IS NULL)) AND
						   id_menu IN (select id_menu from menu_deroulant_intranet where libelle_menu='Setup Busy Hour');";

		$query.="UPDATE menu_deroulant_intranet SET is_profile_ref_admin=null WHERE libelle_menu='Setup Busy Hour';";


		print $query.'<br/>';
		$database->execute($query);

		$groups=getIdGroupTable($family);//tous les groupes de table de la famille courante

		foreach ($groups as $group_id)
			deploy_bh($group_id,$bh_data_type,2,$debug_bh_management);
		break;

	default :
		print "!!Echec : action non reconnue";
		break;
	}
} else {
	print "!!Echec : action ou famille vide";
}
?>
