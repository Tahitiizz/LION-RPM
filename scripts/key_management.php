<?
/*
* 	Fichier qui nettoie les tables de topologie lorsque le nombre d'élément réseau de niveau d'agrégation identique à celui de la clé si celui dépasse la limite fixée dans la clé
*/
?>
<?
/**
*	@cb51102@
*
*	17/01/2011 - Copyright Astellia
*
*	Composant de base version cb_5.1.1.02
*/
?>
<?
/*
*	@cb4100@
*
*	10/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.0
*
*	  maj 10/12/2008 - MPR :  Initialisation du produit (par défault id_prod = '' pour pointer vers la base local
*	  maj 10/12/2008 - MPR : Connexion à la base de donnée local
*	  maj 10/12/2008 : MPR : Tables de référence de la topologie
*	  maj 10/12/2008 : MPR : Récupération des niveaux d'agrégation de la famille principale
*	  maj 10/12/2008 - MPR : Récupération des informations de la clé
*	  maj 10/12/2008 - MPR : Récupération du nombre d'éléments présents en base du même niveau d'agrégation que celui de la clé 
*	  maj 10/12/2008 - MPR : Création de la fonction getNbElements qui récupère le nombre d'éléments réseau de niveau d'agrégation de la clé présents en base
*	  maj 10/12/2008 - MPR : Construction de la sous-requête qui va supprimer les éléments réseau en trop
*	  maj 10/12/2008 - MPR : Modification de la requête qui récupère les éléments réseau à supprimer (nouvelle structure de la topologie)
*	  maj 10/12/2008 - MPR : Suppresion des éléments dans la table edw_object_ref
*	  maj 10/12/2008 - MPR : Ajout du returning pour retourner les éléments supprimés de la requête
*	  maj 10/12/2008 - MPR : Création des conditions à ajouter en fonction du na
*	  maj 10/12/2008 - MPR : Suppression des éléments réseau dans les tables edw_object_arc_ref et edw_object_ref_parameters
*	  maj 10/12/2008 - MPR : Suppression des éléments réseau de niveau minimum dans la table edw_object_ref_parameters
*	  maj 10/12/2008 - MPR :  On supprime tous les arcs où les éléments réseau supprimés par la clé
*	  maj 11/12/2008 - MPR : Appel à la classe Key pour y extraire ses données
*	  maj 27/07/2009 GHX : On récupère le label du module en base pour le Tracelog pour les éléments réseaux supprimés
*	  maj 23/09/2009 - MPR : Correction du bug 11672 : Remplacement du = par un ILIKE à cause du %
*	  maj 23/10/2009 - MPR : Correction du bug 12241 : Erreur SQL sur ON (e.eor_id = z.{$network_aggregation_in_key})
*     21/09/2010 NSE bz 16789 : les familles concernées par le NA à supprimer sont répétées plusieurs fois
*	
*/
?><?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	- maj 21/03/2008 - Maxime : On récupère le label du module en base pour le Tracelog
*	- maj 21/03/2008 - Maxime : Correction du bug 5698 : On ne prend pas en compte les éléments réseaux VIRTUAL
*	- maj 01/04/2008 - Maxime : On boucle sur toutes les familles afin de supprimer entièrement les éléments réseau de la base de données
	
	- maj 25/04/2008, benoit : correction du bug 5698
	
	- maj 25/07/2008 BBX : prise en charge du 3ème axe et optimisation des requêtes. BZ 7198

*/
?>
<?php
/*
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*
*	- modif 11:42 22/08/2007 Gwénaël :
*			- renomage du fichier update_object_ref.php => key_management.php
*			- Suppression des fonctions qui mettent à jour des les tables object_ref (labels + chemins) voir le nouveau fichier scripts/update_object_ref.php (utilisation des classes Topology)
*/
?>
<?php
/*
*	@cb21000@
*
*	08/12/2006 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.00
*/
?>
<?php
/*
 *  - maj 01/03/2007 Gwénaël : Modification de la requête qui récupère les éléments réseaux qui correspond au niveau d'agrégation de la clé
 *                                                          - Rajout d'un paramètre pour la fonction "object_ref_limitation_with_key" pour avoir le nom de la famille principale qui permet ensuite de récupé "edw_group_table" dans la table  sys_definition_group_table
 *                                                          - Requête : récupère les éléments réseaux contenu dans la table 'edw_object_X_ref' avec la date minimum leur correspondant dans la table '$edw_group_table . "_raw_" . $network_aggregation_in_key' et trié par date DESC et msc ASC. Si aucune date (= aucune données) il apparaît au début de la liste et sera supprimé avant les autres s'il est nécessaire.
 *
 *  - 18-04-2007 GH : Modification des requetes de mise à jour des tables de référence de la famile principale et des familles secondaires pour améliorer les performances
 */
?>
<?php
include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once( REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyManagement.class.php");
include_once( REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");

$deb = getmicrotime();
displayInDemon("Check sur le nombre d'éléments présent en base par rapport au nombre autorisé par la clé","title");
// -------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
// ETAPE 1 - Initilisation des paramètres de la famille 	en fonction du produit                                                                   //
// -------------------------------------------------------------------------------------------------------------------------------------------------------------------------//

// Initialisation du produit (par défault id_prod = '' pour pointer vers la base local
$id_prod = '';

// On récupère la famille principale du produit
$main_family = get_main_family($id_prod);

// maj 10/12/2008 : MPR - Récupération des niveaux d'agrégation de la famille principale
$network_agregation              = getNaLabelList("na",$main_family, $id_prod);
$lst_network_agregation          = $network_agregation[$main_family];

// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
//                      ETAPE 2 - Récupération des Informations de la clé et du nombre d'éléments présents en base du même niveau d'agrégation que celui de la clé	          //
// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------//

// Récupération des informations de la clé
$topoManagement = TopologyManagement::getInstance( $id_prod );

$topoManagement->setModeLog("demon");

$tab_key = $topoManagement->getInfoKey();

if ( in_array( $tab_key['na'], array_keys( $lst_network_agregation ) ) )
{
        // maj 10/12/2008 - MPR : Récupération du nombre d'éléments présents en base du même niveau d'agrégation que celui de la clé
        $nb_elements_in_db = $topoManagement->getNbElements( $tab_key['na'] );

        // On compte le nombre d'éléments en trop
        $nb_elements_a_eliminer = $nb_elements_in_db - $tab_key['nb_elements'];

// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
//                      ETAPE 3 - Affichage de messages d'information dans le démon et dans le tracelog							//
// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
        displayInDemon("Nombre de {$tab_key['na']} trouves : {$nb_elements_in_db}<br/>");
        displayInDemon("Nombre de {$tab_key['na']} maximum: {$tab_key['nb_elements']}<br/><br/>");

        // si le nombre maximum d'elements est dépassé, alors on va effacer les elements les plus anciens
        if( $nb_elements_a_eliminer > 0 )
        {
                // On récupère le label et object_ref_table des familles concernées ( famille principale + familles secondaires possédant le niveau d'aggrégation présent dans la clé
                $families = getFamiliesWithNaOfKey( $tab_key['na'] );
                displayInDemon("Familles concernées : ".implode(" - ",$families['family'])."<br/><br/>");

// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
//                      ETAPE 4 - Nettoyage de la topologie         								 	//
// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
            $topoManagement->cleanObjectRef( $nb_elements_a_eliminer, $main_family, __T('A_TRACELOG_MODULE_LABEL_LICENCE_KEY') );
        }
        else
        {
                displayInDemon("<h4>Le nombre de {$tab_key['na']} ne dépasse pas le nombre autorisé par la clé</h4>");
        }

} else {

        displayInDemon("Le niveau d'aggregation {$tab_key['na']} present dans la cle n'existe pas dans la table reference de la topology ou n'appartient pas à la famille principale<br>");

}
$fin = getmicrotime();
displayInDemon("<br />Temps exécution total : ".round(($fin - $deb),3)." sec<br />");


/**
* Fonction qui récupère les familles secondaires possédant le niveau d'aggrégation à supprimer
* @param string $network_aggregation_in_key : niveau d'aggrégation dans la clé
* @param object $database instance de la classe DataBaseConnection
* @return array(string,string)
*/
function getFamiliesWithNaOfKey( $network_aggregation_in_key )
{
        $database = DataBase::getConnection();
        // 21/09/2010 NSE bz 16789 : ajout du distinct
	$query = "
                    SELECT distinct family_label, object_ref_table ,agregation_name, agregation
                    FROM sys_definition_network_agregation n, sys_definition_categorie c
                    WHERE agregation ilike '{$network_aggregation_in_key}%'
                        AND agregation_label = agregation_name
                        AND n.family = c.family
                    ORDER BY family_label
                   ";
	
	$res = $database->getAll($query);
	
	$tab = array();
	foreach( $res as $row ){
		$tab['family'][] = $row['family_label'];
		$tab['object_ref_table'][] = $row['object_ref_table'];
	}
	
	return $tab;
} // End function get_family_secondaries_with_na_min_of_main_family

?>