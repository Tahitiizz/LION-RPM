<?
/*
*	@cb41000@
*
*	06/11/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.0
*
*	- maj 06/11/2008 maxime => Modification de la fonction get_main_family (ajout du paramètre database afin de préciser sur quel produit on se connecte (utilisation de la classe DatabaseConnection()

	- maj 14/11/2008, benoit : ajout d'une note indiquant qu'il ne faut plus utiliser la fonction 'get_gt_info_from_family()' mais 'GetGTInfoFromFamily()' du fichier "php/edw_function.php" pour retourner les informations group_table d'une famille donnée
	- MaJ 19/11/2008 - SLC - fonction get_axe3_information_from_family() obsolète, utiliser GetAxe3Information() de edw_function.php
	- MaJ 20/11/2008 - SLC - ajout sur toutes les fonctions de $product='' et suppression de $database_connection
	- MaJ 20/11/2008 - Gwen - ajout de la fonction getPathNetworkAggregation() à la fin du fichier
	- MaJ 21/11/2008 - BBX - ajout de la fonction  getLevelsAgregOnLevel() à la fin du fichier
	
	21/07/2009 GHX
		- Modification de la fonction  getPathNetworkAggregation()  pour prendre en compte que si l'on passe pas un ID produit on recherche sur tous les produits
	 * 10:03 08/10/2009 SCT : ajout de la fonction getAllowColorNA() pour la récupération des NA dont le allow_color est activé
	 * 15:41 16/10/2009 SCT : ajout de la fonction getNaAxe3MinfromFamily pour correction du BZ 12071


*
*/
/*
*	@cb30004@
*
*	27/09/2007 - Copyright Astellia
*
*	Composant de base version cb_3.0.0.04
*
*	- maj 27/09/2007 maxime => Ajout de la fonction get_limit_3rd_axis qui vérifie si l'on doit limiter le nombre d'éléments 3ème axe d'un famille 3ème axe
*
*/
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- maj 17/08/07 Gwénaël : modif de la fonction get_network_aggregation_max_from_family
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
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*/
?>
<?
/*
*	@cb21001_gsm20010@
*
*	Composant de base version cb_2.1.0.01
*
*1	Parser version gsm_20010
*
*	maj 28/12/2006, maxime : création de la fonction get_family_from_object_ref_table() -> on récupère la famille qui correspond à la table de référence des na
*	@cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
*
*	- maj 22 11 2006 christophe : on affiche les NA dans l'ordre correspondant à la requête se trouvant dans sys_selecteur_properties :
	update de la fonction get_network_aggregation_from_family
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
<?php

/*
* 04/11/2005 : SLS : ajout de la fonction get_mandatory_network_aggregation_from_family()
* 20/11/2005 : GH : ajout de la colonne external_reference dans la table sys_definition_gt_axe
* 25 / 11 / 2005 : CC : ajout de la fonction get_axe3_information_from_axe_gt_id()
* 17/08/2006 : MD : ajout de la fonction getFamilyFromIdGroup() : permet de trouver la famille a partir d'un id de groupe de tables
* 05 10 2006 : MD ajout de la fonction get_network_aggregation_max_from_family()
*/


/*
	retourne le label d'un axe.
*/
// MaJ 20/11/2008 - SLC - ajout $product, supression $database_connection
function get_axe3_label($axe_gt_id,$product = ''){
	$db = Database::getConnection( $product );
	$query = "SELECT axe_type_label FROM sys_definition_gt_axe WHERE axe_gt_id='$axe_gt_id' ";
	$row = $db->getrow($query);
	if ($row) {
		$label = $row['axe_type_label'];
	} else {
		// valeur à afficher par défaut.
		$query = " select distinct axe_type_label FROM sys_definition_gt_axe ";
		$row = $db->getrow($query);
		if ($row) {
			$label = $row['axe_type_label'];
		} else {
			$label = "axe 3";
		}
	}
	return $label;
}

/*
	Permet de récupèrer l'dentifiant d'un group table lorsqu'il y a un 3 ème axe.
	@param : $gt_name : nom du group table ; $family : famille.
	@global : $database_connection.
	@return : $id_group_table : identifiant du group table.
*/
// MaJ 20/11/2008 - SLC - ajout $product, supression $database_connection
function get_group_table_id($gt_name, $family,$product = '') {
	$db = Database::getConnection( $product );
	
	$query = "SELECT id_ligne FROM sys_definition_group_table WHERE family='$family' AND edw_group_table='$gt_name' ";
	$row = $db->getrow($query);
	if ($row) {
		$id_group_table = $row['id_ligne'];
	} else {
		echo "<b><u>Error :</u> no group table for this family ($family) and this group table ($gt_name).<br>Please contact your application administrator.</b>";
		exit;
	}
	return $id_group_table;
}

/*
	Permet de récupèrer le nom d'un group table
	@param : $id : identifiant du group table.
	@global : $database_connection.
	@return : $name : nom du group table.
*/
// MaJ 20/11/2008 - SLC - ajout $product, supression $database_connection
function get_gt_name($id,$product = ''){
	$db = Database::getConnection( $product );
	
	$query = "SELECT axe_gt_id FROM sys_definition_gt_axe WHERE id_group_table=$id ";
	$row = $db->getrow($query);
	if ($row) {
		$name = $row['axe_gt_id'];
	} else {
		echo "<b><u>Error :</u> no group table name. <br>Please contact your application administrator.</b>";
		exit;
	}
	return $name;
}


/*
* fonction qui à partir d'un identifiant de group table retourne les informations sur l'axe du group table
* @param :$id_gt_value : identifiant du group table
* @global : $database_connection
* @return : $axe_information qui contient toutes les données ou FALSE si aucun axe n'est trouve
*
*/
// MaJ 20/11/2008 - SLC - ajout $product, supression $database_connection
function get_axe3_information_from_gt($id_gt_value,$product = '')
{
	$db = Database::getConnection( $product );
	$query = "
		SELECT axe_gt_id,axe_index,axe_index_label,axe_label,axe_type,family,axe_order,id_group_table,external_reference, axe_type_label
		FROM sys_definition_gt_axe
		WHERE id_group_table='$id_gt_value'";
	return $db->getrow($query);
}

/**
* Fonction qui indique si l'on doit limiter le nombre d'éléments axe3 en fonction de la famille
* @param : $family text
* @global : $database_connection
* @return true si l'on doit limiter le nbre d'éléments 3ème axe ou false si ce n'est pas le cas
*
*/
// MaJ 20/11/2008 - SLC - ajout $product, supression $database_connection
function get_limit_axe3($family,$product = '') {
	$db = Database::getConnection( $product );
	__debug($family);
	$na_min_axe3 = get_network_aggregation_min_axe3_from_family( $family,$product );
	__debug($na_min_axe3);
	if( $na_min_axe3 != false ) {
		$query = "SELECT limit_3rd_axis FROM sys_definition_network_agregation WHERE family = '$family' and agregation = '$na_min_axe3'";
		__debug($query,"get_limit_axe3");
		$row = $db->getrow($query);
		__debug($row['limit_3rd_axis'],"row['limit_3rd_axis']");
		if ($row['limit_3rd_axis'] == 1)
			return true;
		else
			return false;
	} else
		return false;
}

/*
* fonction qui à partir d'un identifiant d'axe de group table retourne les informations sur l'axe du group table
* @param :$axe_gt_id_value :identifiant de l'axe
* @global : $database_connection
* @return : $axe_information qui contient toutes les données ou FALSE si aucun axe n'est trouve
*
*/
// MaJ 20/11/2008 - SLC - ajout $product, supression $database_connection
function get_axe3_information_from_axe_id($axe_gt_id_value,$product = '')
{
	$db = Database::getConnection( $product );
	$query = "SELECT axe_gt_id,axe_index,axe_index_label,axe_label,axe_type,family,axe_order,id_group_table,external_reference
			FROM sys_definition_gt_axe
			WHERE axe_gt_id = '$axe_gt_id_value'";
	return $db->getrow($query);
}

/*
* fonction qui à partir d'un nom de family retourne les informations sur l'axe de la famille
* @param :$family : nom de la famille
* @global : $database_connection
* @return : $axe_information qui contient toutes les données ou FALSE si aucun axe n'est trouve
*
*/
// MaJ 19/11/2008 - SLC - ajout $product, supression $database_connection
function get_axe3_information_from_family($family,$product = '')
{
	$db = Database::getConnection( $product );

	$query = "
		SELECT axe_gt_id,axe_index,axe_index_label,axe_label,axe_type,family,axe_order,id_group_table, axe_type_label
		FROM sys_definition_gt_axe
		WHERE family='$family'";
	$result = $db->getall($query);
	$result_nb = count($result);

	if ($result) {
		for ($i = 0;$i < $result_nb;$i++) {
			$row = $result[$i];
			$axe_information["axe_gt_id"][$i]		= $row["axe_gt_id"];
			$axe_information["axe_index"][$i]		= $row["axe_index"];
			$axe_information["axe_index_label"][$i]	= $row["axe_index_label"];
			$axe_information["axe_label"][$i]		= $row["axe_label"];
			$axe_information["axe_type"][$i]		= $row["axe_type"];
			$axe_information["family"][$i]			= $row["family"];
			$axe_information["axe_order"][$i]		= $row["axe_order"];
			$axe_information["id_group_table"][$i]	= $row["id_group_table"];
			$axe_information["axe_type_label"][$i]	= $row["axe_type_label"];
		}
		return $axe_information;
	} else {
		return false;
	}
}

/* Retourne la famille a laquelle appartient un groupe de tables
  * @param id_group l'identifiant de groupe de tables dont on recherche la famille
  * @return le nom de la famille a laquelle appartient id_group et une chaine vide si aucune famille n'a ete trouvee
  */
// MaJ 20/11/2008 - SLC - ajout $product, supression $database_connection
function getFamilyFromIdGroup($id_group, $product = '') {
    	$db = Database::getConnection( $product );
	$family="";
	$query="SELECT family FROM sys_definition_group_table WHERE id_ligne=$id_group";
	$check_family = $db->getone($query);

	if ($check_family)
		$family=$check_family;

	return $family;
}

// MaJ 19/11/2008 - SLC - ajout $product, supression $database_connection
function get_object_ref_from_family($family,$product = '')
{
    	$db = Database::getConnection( $product );
	$query = "SELECT object_ref_table FROM sys_definition_categorie WHERE family='$family'";
	return $db->getone($query);
}


// MaJ 20/11/2008 - SLC - ajout $product, supression $database_connection
function get_family_from_object_ref_table($object_ref_table,$product = '')
{
	$db = Database::getConnection( $product );
	$query = "SELECT family  FROM sys_definition_categorie WHERE object_ref_table='$object_ref_table'";
	return $db->getone($query);
}


// MaJ 19/11/2008 - SLC - ajout de la variable $product, supression de global $database_connection
function get_network_aggregation_min_from_family($family,$product = '')
{
	$db = Database::getConnection( $product );
	$query = "SELECT network_aggregation_min FROM sys_definition_categorie WHERE family='$family'";
	return $db->getone($query);
}

// MaJ 20/11/2008 - SLC - ajout $product, supression $database_connection
function get_network_aggregation_min_axe3_from_family($family,$product = '')
{
	$db = Database::getConnection( $product );
	
	$query = "
		SELECT agregation,agregation_level
		FROM sys_definition_network_agregation
		WHERE family = '$family'
			AND axe = 3
		ORDER BY agregation_level ASC
		LIMIT 1";
	$row = $db->getrow($query);
	if ($row) {
		return $row["agregation"];
	} else {
		return false;
	}
}

//Retourne le niveau d'agregation le plus haut d'une famille donnee et null si non trouve
// MaJ 20/11/2008 - SLC - ajout $product, supression $database_connection
function get_network_aggregation_max_from_family($family,$axe=0,$product = '')
{
	$db = Database::getConnection( $product );
	$net_max=null;
	$where = "where family='$family'";
	// modif 17/08/2007 Gwénaël
	// Ajout d'une condition pour préciser que l'axe est null dans le cas de axe1
	// => sinon problème dans le cas où le level du niveau max d'une famille 3° axe est le même que celui du niveau max du troisième axe
	if ($axe==0)	$where.= "and axe IS NULL";
	else			$where.= "and axe=$axe";
	$query = "
		select agregation
		from sys_definition_network_agregation
		$where
		order by agregation_level desc
		limit 1";
	$get_max = $db->getone($query);
	if ($get_max)
		$net_max = $get_max;

	return $net_max;
}

// MaJ 20/11/2008 - SLC - ajout $product, supression $database_connection
function get_family_information_from_family($family,$product = '')
{
	$db = Database::getConnection( $product );
	$query = "
		SELECT family,family_label,object_ref_table,rank,on_off,visible,network_aggregation_min
		FROM sys_definition_categorie
		WHERE family='$family'";
	return $db->getrow($query);
}

// MaJ 20/11/2008 - SLC - ajout $product, supression $database_connection
function get_network_aggregation_from_family($family,$product='')
{
	$db = Database::getConnection( $product );
	
	$query = "
		SELECT DISTINCT agregation,agregation_label, agregation_rank
		FROM sys_definition_network_agregation
		WHERE family='$family'
		ORDER BY agregation_rank ASC";
	$res = $db->getall($query);
	
	if ($res)
		foreach ($res as $row)
			$na_list[$row["agregation"]] = $row["agregation_label"];
	
	// On compare par rapport à la requête de sys_selecteur_properties afin que les NA retournées soient dans le même ordre que cette requête.
	$q = "
		SELECT ssp.selection_sql as query
		FROM sys_selecteur_properties ssp, sys_object_selecteur sos
		WHERE ssp.id_selecteur = sos.object_id
			AND sos.family = '$family'
			AND ssp.properties = 'network_agregation'
			AND ssp.selection_sql <> ''
	";
	$stored_query = $db->getone($q);
	if ($result) {
		$query = str_replace('$this->family', '$family', $stored_query);
		eval( "\$query = \"$query\";" );
		$resultat = $db->getall($query);
		if ($resultat) {
			$na_list2 = Array();
			foreach ($resultat as $row)
				foreach ($na_list as $na=>$na_label)
					if($na == $row['agregation'])
						$na_list2[$na] = $na_label;
			$na_list = $na_list2;
		}
		return $na_list;
	} else {
		return false;
	}
}

// MaJ 20/11/2008 - SLC - ajout $product, supression $database_connection
function get_mandatory_network_aggregation_from_family($family,$product = '')
{
	$db = Database::getConnection( $product );
	
	$query = "
		SELECT DISTINCT agregation,agregation_label, agregation_rank
		FROM sys_definition_network_agregation
		WHERE family='$family'
			AND mandatory=1
		ORDER BY agregation_rank ASC";
	$res = $db->getall($query);
	
	if ($res) {
		foreach ($res as $row)
			$agregation[$row["agregation"]] = $row["agregation_label"];
		return $agregation;
	} else {
		return false;
	}
}

/*fonction qui determine la famille principale
* @global $database_connection
*/
// MaJ 20/11/2008 - SLC - ajout $product, supression $database_connection
function get_main_family( $product='' )
{
	$db = Database::getConnection( $product );
	$query = "SELECT family from sys_definition_categorie WHERE main_family=1";
	return $db->getone($query);
}


// 14/11/2008 - Modif. benoit : la fonction ci-dessous est dépréciée, utiliser 'GetGTInfoFromFamily($family, $product = "")' du fichier "php/edw_function.php"

/* Retourne l'ensemble des informations d'un group_table pour une famille donnée
 * @param family nom de la famille dont on recherche les informations
 * @return tableau de données du group_table
 */

// MaJ 20/11/2008 - SLC - ajout $product, supression $database_connection
function get_gt_info_from_family($family,$product = '')
{
	$db = Database::getConnection( $product );

	$sql = "SELECT * FROM sys_definition_group_table WHERE family='$family'";
	$req = $db->getall($sql);

	$tab_gt_info = array();

	if ($req)
		foreach ($req as $row)
			foreach ($row as $key=>$value)
				$tab_gt_info[$key] = $value;

	return $tab_gt_info;
}

/***
* Cette fonction retourne un tableau contenant tous les niveaux agrégés depuis le niveau
* précisé en paramètre.
* 23/06/2010 BBX : réécriture de la fonction en récursif
* @param : string Niveau de départ
* @param : array Tableau contenant les chemin d'agrégation (généré par "getPathNetworkAggregation")
* @return : array Tableau contenant tous les parents (grand parents, arrières grands parents, etc) du niveau
****/
function getLevelsAgregOnLevel($level, array $pathNa)
{
    // Moi-même
    $naLevels = array($level);

    // Mes parents directs
    if(array_key_exists($level,$pathNa))
    {
        foreach($pathNa[$level] as $parent)
            $naLevels = array_merge($naLevels,getLevelsAgregOnLevel($parent, $pathNa));
    }

    // Retour du résultat
    return array_unique($naLevels);
}

/*
 * Retourne les niveaux d'agregation dont le champ "allow_color" est activé dans "sys_definition_network_agregation"
 * @param string $product : nom du produit (vide par défaut)
 * @return $tableau[]=NA le tableau contenant les NA activés
 * 10:03 08/10/2009 SCT : ajout de la fonction
 */
function getAllowColorNA($product='')
{
	$db = Database::getConnection( $product );
	// recherche des NA possédant allow_color = 1 (sans notion de famille)
	$query = 'SELECT agregation, agregation_label FROM sys_definition_network_agregation WHERE allow_color = 1';
	$req = $db->getall($query);

	$tableauNaColorAllowed = array();

	if($req)
	{
		foreach($req AS $row)
			$tableauNaColorAllowed[$row['agregation_label']] = $row['agregation'];
	}

	return $tableauNaColorAllowed;
} // End getAllowColorNA()

/*
 * Récupération du na min du 3ème axe
 * @param string $product : nom du produit (vide par défaut)
 * @param string $family : nom de la famille
 * @return $tableau[]=NA le tableau contenant les NA
 * 15:41 16/10/2009 SCT : ajout de la fonction pour correction du BZ 12071
 */
function getNAAxe3MinFromFamily($product='', $family='')
{
	$db = Database::getConnection( $product );
	// recherche des NA possédant allow_color = 1 (sans notion de famille)
	$query = '
		SELECT 
			agregation 
		FROM 
			sys_definition_network_agregation 
		WHERE 
			third_axis_default_level = 1
			AND family = \''.$family.'\'';
	$req = $db->getall($query);

	$tableauNa = array();

	if($req)
	{
		foreach($req AS $row)
			$tableauNa[] = $row['agregation'];
		return $tableauNa;
	}
	else
		return false;

	
} // End getAllowColorNA()

?>
