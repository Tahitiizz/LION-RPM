<?php
/**
 * 
 * @cb5100@
 *
 	- 21/05/2010 : nouveau chemin pour java (Red Hat 5.5)
 *	03/10/12 ACS BZ 29086 error in voronoi script with negative coordinates
 * 
*/
?>
<?php
/**
 * 
 * @cb4100@
 * 
 * 	14/11/2007 - Copyright Astellia
 * 
 * 	Composant de base version cb_4.1.0.0
 *

	- maj 01/12/2008, MRP : Ajout de la classe DataBaseConnection pour exécuter les requêtes
	- maj 01/12/2008 MPR : La table de référence est maintenant edw_object_ref_parameters
	- maj 01/12/2008 MPR : Ajout du paramètre $database dans la fonction getVirtualPoint()
	- maj 01/12/2008 MPR : Modification de la requête (on remplace x => eorp_x et y par eorp_y)
	- maj 01/12/2008 MPR : Utilisation de la classe DatabaseConnection dans la fonction getVirtualPoint
	- maj 01/12/2008 MPR : Ajout des paramètre $na_min, $table_arc et $database dans la fonction constructInputFile
	- maj 01/12/2008 MPR : Ajout de deux cas : 
					CAS 1 => On se base sur la table des arcs pour récupérer les relations entre les éléments réseau
					CAS 2 => On se base quand même sur le niveau minimum afin de sélectionner uniquement les élément réseau qui possèdent des coordonnées géogrpahiques
	- maj 01/12/2008 MPR : La table de référence est maintenant edw_object_ref_parameters
	- maj 01/12/2008 - MPR : Ajout du paramètre $database pour exécuter les requêtes SQL dans la fonction getGISParam
	- maj 01/12/2008 MPR : On récupère les éléments réseau de niveau minimum dans les tables $table_params (edw_object_ref_parameters) et $table_ref ( edw_object_ref )
	- maj 02/12/2008 MPR : On affiche unqiuement les chaines de caractères dans les lignes renvoyées par le script java BuilGeomUnion.class
	- maj 02/12/2008 MPR : Suppression des fonctions getNAGlobalPolygon et getQueryComputePolygonNASup n'étant plus utilisées
        - maj 07/06/2011 MPR : - Ajout de la fonction checkPolygones() qui corrige les MULTIPOLYGON (suppression des polygones invalides)
 *
 */
?>
<?php
/**
 * 
 * @cb40000@
 * 
 * 	14/11/2007 - Copyright Acurio
 * 
 * 	Composant de base version cb_4.0.0.00
 *

	- maj 21/12/2007, benoit : ajout des '@' devant les fonctions 'pg_XXX' pour éviter les "Warning"
	- maj 21/12/2007, benoit : ajout des valeurs 0, 2 et 3 relatives aux contraintes sur le srid dans le tableau de requetes

	- maj 26/12/2007, benoit : ajout du parametre srid à la fonction 'insertFromOutputFile()'
	- maj 26/12/2007, benoit : prise en compte du nouveau parametre srid lors de l'appel de la fonction 'insertFromOutputFile()'
	- maj 26/12/2007, benoit : on précise le srid lors de la conversion puis l'insertion des polygones dans la table de résultats
	- maj 26/12/2007, benoit : suppression de la restriction sur les x, y, azimuth positifs dans la requete de selection des na dans la table 	  principale. On selectionne désormais tous ceux qui sont différents de NULL

 *
 */
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
	- 13/09/07 christophe : à la fin du calcul, on met la variable update_coord_geo de sys_global_parameters à 0

	- maj 05/07/2007, benoit : mise en commentaires de la transaction. Désactivation nécessaire car sous             RedHat, ils existent des erreurs non critiques pour le calcul qui bloquent la transaction et stoppent          tous le calcul.

	- maj 05/07/2007, benoit : on ne fait plus la maj du SRID à la fin du calcul (retourne toujours -1 et            écrase l'ancien SRID)

	- maj 06/07/2007, benoit : modification de la requete sur le ratio nombre de $na_source / nombre de $na          pour eviter la division par 0

	- maj 03/08/2007, benoit : suppression de tous les appels à des scripts externes et à leurs fonctions de         manière à préserver l'indépendance du module

	- maj 21/08/2007, benoit : ajout d'une condition sur les x,y de la table dans la requete d'union

	- maj 24/08/2007, benoit : suppression de la condition "AND isvalid(t1.p_voronoi)" qui provoque des              erreurs Postgresql dans certains cas

	- maj 24/08/2007, benoit : deplacement de la maj des coordonnées de la viewbox afin que celle-ci s'execute       avant le calcul des polygones de niveaux supérieurs au niveau minimum qui provoque parfois des erreurs

	- maj 24/08/2007, benoit : la maj des coordonnées de la viewbox se fait en 2 étapes puisque Postgresql 8.2   ne   peut pas utiliser de fonctions agrégées dans un update ("ERROR : cannot use aggregate function in UPDATE")

	- maj 03/09/2007, benoit : suppression de la verification du ratio

	- maj 03/09/2007, benoit : remplacement du calcul des polygones des na de niveaux supérieurs en SQL par un       programme Java

	- maj 04/09/2007, benoit : le calcul de Voronoi n'est lancé que si le parametre global "update_coord_geo" est    différent de 0 ce qui signifie que des cellules ont été ajoutées ou que les coordonnées de celles existantes   ont été modifiées

	- maj 05/09/2007, benoit : extension du temps maximum d'execution du script (on passe de 3600 à 7200 secondes)

*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01

	- maj 23/03/2007, benoit : mise en commentaire de la generation de la miniature. Celle-ci est désormais       définie en partie User lors du 1er appel du GIS

	- maj 20/03/2007, benoit : ajout de la generation de la miniature à la fin du calcul et de la fonction        'getNAGlobalPolygon()' permettant de définir le polygone englobant de la na de plus haut niveau

	- maj 14/03/2007, benoit : reformulation de la requete de calcul des polygones d'une na à partir de la na de   niveau inférieur

	- maj 18/05/2007, gwénaël.
		Les DELETE + INSERT font planter postgres sur RedHat, donc au lieu de faire plusieurs DELETE on vide tout simplement la table via TRUNCATE
		Et afin que tout ce passe bien on utilise une transaction, elle commence juste avant le TRUNCATE et si toutes les requêtes SQL se sont bien passé, on valide la transaction, sinon on l'annule

	- maj 01/06/2007, benoit : verification que le ratio nombre de $na_source / nombre de $na est inférieur ou 	  égal au ratio configuré dans 'sys_global_parameters'

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
*	@cb21000@
*
*	08/12/2006 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.00
*/
?>
<?
/*
*	@cb2001_iu2030_111006@
*
*	11/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.1
*
*	Parser version iu_2.0.3.0
*/
?>
<?
/*
*       @cb2000b_iu2000b@
*
*       20/07/2006 - Copyright Acurio
*
*       Composant de base version cb_2.0.0.0
*
*       Parser version iu_2.0.0.0
*/
?>
<?
/*
* 10/08/2006 : MD - creation du fichier
* 	>> ce script permet de lancer le calcul des polygones de voronoi pour tous les niveaux d'agregation de la table sys_definition_network_aggregation
* 	    avec la colonne 'voronoi_polygon_calculation' a 1
*	>> principe :
*	   - Pour chaque point de la table d'objet de la famille principale pour le niveau minimum on positionne le point en fonction de son azimuth et d'une distance d'ecartement fixee (5).
* 	   - On recherche egalement pour chaque point un potentiel point virtuel pour limiter les polygones trop grand.
*	   - Les id des points positionnes ainsi que ceux des points virtuels sont ecrits dans un fichier avec leurs coordonnees.
*	   - A partir de ce fichier, la classe de calcul java BuildVoronoi va rechercher les polygones pour chaque point present.
*	   - En retour, les polygones trouves sont ecrits dans le fichier de coordonnees avec l'id des points associes.
*	   - La table sys_gis_topology_voronoi est mise a jour grace a la commande 'COPY' et du fichier precedemment obtenu
*	   - Les niveaux d'agregation superieur au niveau minimum sont calcules a partir du niveau source definit dans sys_definition_network_aggregation
*	   - Les polygones des niveaux superieurs sont determines en effectuant une union de tous les polygones des elements reseaux appartenant au na a calculer
*
* 24/08/2006 : MD - correction d'erreurs
* 	>> concernant le nom de la table de configuration globale du gis (sys_gis_config_global au lieu de sys_giview_config_global)
*	>> désactivation du mode de suivi de progression temps réel concernant le calcul des points
*	>> modification du mode de suivi de progression temps réel concernant le calcul des points (on actualise tous les 500 points traites)
*
* 09/10/2006 : MD - evolution
*	>> on limite la superficie des polygones calcules
* 	>> on limite le temps de calcul à 1 heure
*
*/
?>

<style>
ul,ol{margin-top:0px;margin-bottom:0px}
li{margin:5px}
.v_sub_step_title{color:black;font-weight:bold;font-style:normal;display:block}
.v_sub_step_content{color:dimgray;font-weight:normal;font-style:normal;margin-bottom:0px;display:block}
.v_query{font-style:italic;font-weight:normal;display:block}
.v_command_result{background-color:black;padding-left:10px;color:white;font-style:normal;font-weight:normal;border:solid 1px dimgray;}
.v_error_msg{color:red;font-weight:normal;font-style:normal;display:block}
.v_pt{color:gray;font-size:14pt}
.v_vpt{color:red;font-size:14pt}
.v_date{color:blue;font-weight:normal;font-style:normal;display:block}
</style>

<?

// 05/09/2007 - Modif. benoit : extension du temps maximum d'execution du script (on passe de 3600 à 7200 secondes)

set_time_limit(7200);//on arrete le script si probleme pour permettre aux etapes suivantes d'etre executees

include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/DataBaseConnection.class.php");

// maj 01/12/2008, MRP : Ajout de la classe DataBaseConnection pour exécuter les requêtes
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection();
// $database->setDebug(1);

// maj 01/12/2008 MPR : La table de référence est maintenant edw_object_ref_parameters
$table_params = 'edw_object_ref_parameters';
$table_ref = 'edw_object_ref';
$table_arc = 'edw_object_arc_ref';

// 03/08/2007 - Modif. benoit : mise en commentaires de l'inclusion de scripts externes au module GIS

//include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function.php");
//include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");

/* Retourne la valeur du parametre $param de la table de config $config_table
  * @param config_table le nom de la table de configuration dans laquelle on souhaite lire la valeur de param
  * @param param le nom du parametre de la table de configuration config_table
  * @param database connexion à la base de données
  * Le parametre correspond a un nom de colonne de la table config_table.
  * @return la valeur du parametre $param
  */
function getGISParam($config_table,$param,$database){

	$value=null;
	$query="SELECT $param FROM $config_table";
	//print $query;
	// $res = $database->getOne($query);
	if ($database->getOne($query) !== ""){

		$value = $database->getOne($query);
	}
	return $value;
}

/* Retourne l'ensemble des na a calculer
  * @param family le nom de la famlle dont on cherche les na a calculer
  * @param na_min le nom de la na minimum de la famille passee en parametre
  * @return array le nom des na a calculer accompagne de la na source permettant de calculer la na courante
  *    le tableau est trie de la na de plus bas niveau jusqu'a la na de plus haut niveau (exemple : cell, bsc, msc, network)
  */
function getNAToCompute($family,$na_min,$database){

	$query="SELECT DISTINCT agregation_rank,agregation,source_default
			FROM sys_definition_network_agregation
			WHERE family='$family' AND voronoi_polygon_calculation=1 
			AND agregation<>'$na_min' AND on_off=1 
			AND agregation IS NOT NULL AND agregation<>''
			ORDER BY agregation_rank";
			
	$res = $database->getAll($query);
	
	$na=array();
	foreach($res as $row)
		$na[$row["agregation"]]=$row["source_default"];
		
	return $na;
}

/* Positionne un point en fonction de son azimuth et de l'ecartement souhaite par rapport au pylone.
  * L'azimuth est determine a partir du cercle de rayon r en prenant comme point de départ l'axe du nord
  * puis en se deplacant suivant le sens des aiguilles d'une montre.
  * Remarque : il est conseille de na pas prendre un rayon inferieur a 1 afin de permettre le calcul des polygones
  * @param x l'abscisse du point que l'on veut positionner
  * @param y l'ordonnee du point
  * @param azimuth l'azimuth du point
  * @param rayon l'ecartement souhaite par rapport au pylone
  * @return les nouvelles coordonnees du point obtenues une fois le positionnement effectue
  */
function getCoordinates($x,$y,$az,$rayon){
	$coord=array($x,$y);//les nouvelles coordonnees du point
	if($az>=0 && $az<=90){
		$coord[0]=$x+cos(deg2rad(90-$az))*$rayon;
		$coord[1]=$y+sin(deg2rad(90-$az))*$rayon;
	}elseif($az>90 && $az<=180){
		$coord[0]=$x+sin(deg2rad(180-$az))*$rayon;
		$coord[1]=$y-cos(deg2rad(180-$az))*$rayon;
	}elseif($az>180 && $az<=270){
		$coord[0]=$x-cos(deg2rad(270-$az))*$rayon;
		$coord[1]=$y-sin(deg2rad(270-$az))*$rayon;
	}elseif($az>270 && $az<=360){
		$coord[0]=$x-sin(deg2rad(360-$az))*$rayon;
		$coord[1]=$y+cos(deg2rad(360-$az))*$rayon;
	}
	$coord[0]=ceil($coord[0]);//arrondi au nombre superieur
	$coord[1]=ceil($coord[1]);
	return $coord;
}

/* Transforme un temps en seconde en minutes
  * Le format est le suivant : 1 mn 15 s
  * @param elapsed_time temps en seconde
  * @return le temps en minutes
  */
function getTimeInMN($elapsed_time){
	return floor(($elapsed_time/60)).' mn. '.($elapsed_time%60).' s.';
}

// maj 01/12/2008 MPR : Ajout du paramètre $database
/* Retourne les coordonnees du point virtuel correspondant au point (x,y) si ce dernier est valide.
  * Un point virtuel est valide si le le polygone forme par le point (x,y) et les points du cercle de rayon 'distance_max_polygon' legerement inferieur et superieur au point d'azimuth 'az'
  * ne contient aucun point definis dans la table'object_table'.
  * Le parametre angle de precision permet de connaitre les points inferieur et superieur au point d'azimuth 'az'.
  * @param object_table nom de la table objet contenant la topologie du reseau (table objet de la famille principale)
  * @param srid identifiant de projection de la table resultat
  * @param x l'abscisse du point dont on recherche un point virtuel potentiel
  * @param y l'ordonnee du point dont on recherche un point virtuel potentiel
  * @param az l'azimuth du point dont on recherche un point virtuel potentiel
  * @param angle_precision l'angle a utiliser pour determiner le polygone temporaire permettant de valider ou non le point virtuel
  * @param  distance_max_polygon l'ecart entre le point virtuel potentiel et le point de coordonnees (x,y)
  * @param database connexion à la base de données
  * @return les coordonnees du point virtuel correspondant au point (x,y) si le point a ete valide et un tableau vide sinon
  */
function getVirtualPoint($object_table,$srid,$x,$y,$az,$angle_precision,$distance_max_polygon, $database){
	//print '<font style="color:red">Traitement de l\'azimuth '.$az.' angle '.$angle_precision.' coord ('.$x.','.$y.')</font><br/>';

	$coord=array();//les coordonnees du point virtuel a retourner

	$angle_sup=($az+$angle_precision)%360;
	$angle_inf = 360 - abs($az - $angle_precision);
	if ($angle_precision <= $az)
		$angle_inf = $az - $angle_precision;

	//print '<font style="color:red">angle sup '.$angle_sup.' , angle inf '.$angle_inf.'</font></br>';
	$V=getCoordinates($x,$y,$az,$distance_max_polygon);//coordonnees du point virtuel
	$A=getCoordinates($x,$y,$angle_sup,$distance_max_polygon);//coordonnees du point superieur au point virtuel
	$B=getCoordinates($x,$y,$angle_inf,$distance_max_polygon);//coordonnees du point inferieur au point virtuel

	/*on cherche a savoir si il y a des points dans le polygone forme par les points A B et le point de coordonnees (x,y)*/
	// 03/10/12 ACS BZ 29086 error in voronoi script with negative coordinates
	$signe["x"] = checkSigne($x);
	$signe["y"] = checkSigne($y);
	$signe["A0"] = checkSigne($A[0]);
	$signe["A1"] = checkSigne($A[1]);
	$signe["B0"] = checkSigne($B[0]);
	$signe["B1"] = checkSigne($B[1]);
	// maj 11:01 01/12/2008 MPR : Modification de la requête (on remplace x => eorp_x et y par eorp_y)
    $query="SELECT oid FROM $object_table
			WHERE WithIn(
				GeometryFromtext('POINT('||eorp_x::text||' '||eorp_y::text||')',$srid),
				GeometryFromtext('POLYGON((".$signe["x"]."'||".abs($x)."::text||' ".$signe["y"]."'||".abs($y)."::text||',".$signe["A0"]."'||".abs($A[0])."::text||' ".$signe["A1"]."'||".abs($A[1])."::text||',".$signe["B0"]."'||".abs($B[0])."::text||' ".$signe["B1"]."'||".abs($B[1])."::text||',".$signe["x"]."'||".abs($x)."::text||' ".$signe["y"]."'||".abs($y)."::text||'))', $srid))
			LIMIT 1";//on limite a 1 car il suffit qu'il y ait un seul point dans le polygone pour que le point virtuel soit refuse
	// print '<br/>'.$query.'<br/>';
	
	// 21/12/2007 - Modif. benoit : ajout des '@' devant les fonctions 'pg_XXX' pour éviter les "Warning"
	
	// maj 10:54 01/12/2008 MPR : Utilisation de la classe DatabaseConnection
	$res = $database->getAll($query);
	if(count($res)==0)//si aucun point n'est dans le polygone alors on retourne le point virtuel
		$coord=$V;
		//print 'POINT a creer : '.$coord[0].' '.$coord[1].'<br/>';
	//} else
		//print 'POINT trouve<br/>';
	return $coord;
}

function checkSigne($var) {
	if ($var < 0) {
		return "-";
	}
	return "";
}

// maj 01/12/2008 MPR : Ajout des paramètre $na_min, $table_arc et $database 
/* Construit le fichier texte d'entree liant les na et les chemins des polygones à fusionner
 * @params na_min niveau d'agrégation réseau minimum
 * @params pathfile chemin absolu du fichier à écrire
 * @params result_table nom de la table contenant les chemins des polygones à fusionner
 * @params object_table nom de la table indiquant les éléments réseau
 * @params table_arc nom de la table indiquant les liaisons entre $na_from et $na_to
 * @params na_from nom de la na contenant les paths permettant le calcul des nouveaux polygones
 * @params na_to nom de la na pour laquelle on va calculer les nouveaux polygones
 * @params database connexion à la base de données
 * @return "true" si le fichier texte existe, "false" sinon
 */

function constructInputFile($na_min, $pathfile, $result_table, $object_table, $table_arc, $na_from, $na_to, $database)
{

	// maj 01/12/2008 MPR : On se base sur la table des arcs pour récupérer les relations entre les éléments réseau
	// Requete de selection des na dont on souhaite définir le polygone et des paths relatifs à ces na
	// Cas 1 : le niveau de base est le niveau d'agrégation minimum
	if( $na_from == $na_min ){

		$sql =	 "	SELECT t2.$na_to AS na, GeometryType(t2.p_voronoi) AS type, AsText(t2.p_voronoi) AS path
					FROM
					(
						SELECT DISTINCT eoar_id_parent as $na_to, t1.na_value, t1.p_voronoi
						FROM $object_table, $table_arc, $result_table t1
						WHERE t1.na_value = eoar_id
						AND t1.na = '$na_from'
						AND eoar_id = eorp_id
						AND eoar_id_parent != '' AND eoar_id_parent IS NOT NULL
						AND SPLIT_PART(eoar_arc_type,'|s|',1) = '$na_from'
						AND SPLIT_PART(eoar_arc_type,'|s|',2) = '$na_to'
						AND eorp_x IS NOT NULL AND eorp_y IS NOT NULL
					) t2 WHERE $na_to IS NOT NULL";
	
	
	// Cas 2 : Le niveau de base est > au niveau d'agrégation minimum
	// maj 01/12/2008 MPR : On se base quand même sur le niveau minimum afin de sélectionner uniquement les élément réseau qui possèdent des coordonnées géogrpahiques
	/*
	//--------------------------------------------------------------------------------------------------------------------------------------------//
	// 		Exemple de requête exécutée => Calcul de p_voronoi de network à partir de rnc
	//--------------------------------------------------------------------------------------------------------------------------------------------//
			SELECT DISTINCT ON(rnc)  network, GeometryType(p_voronoi) AS type, AsText(p_voronoi) AS path FROM (

					SELECT DISTINCT e0.eoar_id as sai, e0.eoar_id_parent as rnc, e1.eoar_id_parent as network, p_voronoi 
					FROM sys_gis_topology_voronoi, edw_object_arc_ref e0, edw_object_arc_ref e1 
					WHERE e0.eoar_arc_type = 'sai|s|rnc' 
					AND e1.eoar_arc_type = 'rnc|s|network' 
					AND e1.eoar_id = e0.eoar_id_parent 
					AND sys_gis_topology_voronoi.na_value = e0.eoar_id_parent AND na = 'rnc'

			) t1 

			RIGHT JOIN (SELECT eorp_id FROM edw_object_ref_parameters WHERE eorp_x IS NOT NULL AND eorp_y IS NOT NULL) t2 ON eorp_id = sai
	//--------------------------------------------------------------------------------------------------------------------------------------------//
	*/ 
	} else {
	
		$sql = "SELECT DISTINCT ON ($na_from) $na_to as na, GeometryType(p_voronoi) AS type, AsText(p_voronoi) AS path FROM (";
		
		// On récupère le chemin complet afin de sélectionner uniquement les éléments réseau possédant des coordonnées géographiques
		$family = get_main_family();
		$_na = getAgregPath($na_min, $na_to, $family, $database);
		
		if( count($_na) > 3 ){
		
			$id_na = array_keys($_na, $na_from);
			
			foreach( $_na as $key=>$val ){
				
				$id = $key-1;
				
				if( $val !== $_na[ count($_na)-1 ] )
					$_select[] = " e{$key}.eoar_id as {$val} ";
				else
					$_select[] = "  e{$id}.eoar_id_parent as {$val} ";
				
				if( $val !== $_na[count($_na)-1] ){
					$_from[] = "  edw_object_arc_ref e{$key}";
				}
				
				if( $val !== $_na[count($_na)-1] ){
					$_where[]  = " e{$key}.eoar_arc_type = '{$val}|s|{$_na[$key+1]}'";
				}
				
				
				if($val !== $na_min and $val !== $_na[count($_na) -1]){
					$_where[] = " e{$id}.eoar_id_parent  = e{$key}.eoar_id";
				}
			}
			
			// Construction de la sous-requête
			$sql.= "SELECT DISTINCT p_voronoi, ".implode(", ",$_select)." ";
			$sql.= " FROM sys_gis_topology_voronoi, ".implode(", ", $_from);
			$sql.= " WHERE ".implode(" AND ",$_where);
			$sql.= " AND sys_gis_topology_voronoi.na_value = e{$id_na[0]}.eoar_id AND na = '$na_from'";
			
		}else{
			
			$sql.= "SELECT DISTINCT e0.eoar_id as $na_min, e0.eoar_id_parent as $na_from, e1.eoar_id_parent as $na_to, p_voronoi 
						FROM sys_gis_topology_voronoi, edw_object_arc_ref e0, edw_object_arc_ref e1 
						WHERE e0.eoar_arc_type = '$na_min|s|$na_from' 
						AND e1.eoar_arc_type = '$na_from|s|$na_to' 
						AND e1.eoar_id = e0.eoar_id_parent 
						AND sys_gis_topology_voronoi.na_value = e0.eoar_id_parent AND na = '$na_from'
					";
		}
		
		$sql.= ") t1 

			RIGHT JOIN (
				SELECT eorp_id FROM edw_object_ref_parameters 
				WHERE eorp_x IS NOT NULL AND eorp_y IS NOT NULL
			) t2 ON eorp_id = $na_min
			WHERE $na_to IS NOT NULL
			";
	}

	$result = $database->execute($sql);
	
	$na_paths = array();

	// Pour chaque valeur de na dont on souhaite calculer le polygone, on défini les chemins des polygones à fusionner 

	while($row = $database->getQueryResults($result,1)) {

		// On supprime des chemins les chaines de caracteres "POLYGON" et "MULTIPOLYGON" ainsi que les parenthèses (simplification imposée par le calcul java ultérieur)
		
		//if (!(strpos($row['path'], "MULTIPOLYGON") === false)) {

		if ($row['type'] == "MULTIPOLYGON") {
						
			$path	= str_replace(array('MULTIPOLYGON(((', ')))'), '', $row['path']);
			$paths	= explode(')),((', $path);

			for ($i=0; $i < count($paths); $i++) {
				$na_paths[$row['na']][] = $paths[$i];
			}
		}
		else if ($row['type'] == "POLYGON")
		{
			$na_paths[$row['na']][] = str_replace(array('POLYGON((', '))'), '', $row['path']);
		}	
	}

	// On stocke les valeurs de na et les polygones associés à fusionner dans le fichier '$pathfile'

	if (count($na_paths) > 0) {
		$handle = fopen($pathfile, 'w+');

		foreach ($na_paths as $key=>$value) {

			fwrite($handle, $key.":".implode(';', $value)."\n");
		}

		fclose($handle);
	}

	return file_exists($pathfile);
}

/* Réalise l'insertion des nouveaux polygones de Voronoi stockée dans le fichier '$pathfile'
 * @params pathfile chemin absolu du fichier contenant les valeurs de na et leurs polygones à insérer
 * @params result_table nom de la table d'insertion des nouveaux polygones
 * @params na nom du niveau d'aggregation contenu dans le fichier
 * @return "true" si l'insertion est effective, "false" sinon
 */

 // 26/12/2007 - Modif. benoit : ajout du parametre srid à la fonction 'insertFromOutputFile()'

// maj 07/06/2011 - MPR : Ajout de la fonction checkPolygones()
/**
 * Fonction qui vérifie
 * @param string $multipoly
 * @return string / false : Retourne le MULTIPOLYGON corrigé ou retourn false si on a moins de 3 points
 */
function checkPolygones( $multipoly )
{
    $t= str_replace( array("MULTIPOLYGON(((",")))"),"",$multipoly );

    $polyValid = array();
    $polygons = explode(")), ((", $t);

    if( count( $polygons ) > 0 )
    {
        // On boucle sur chacun des polygones
        foreach( $polygons as $poly )
        {
            // On supprime les points en double
            $pts = array_unique( array_map('trim',explode(",",$poly) ) );

            // Un polygone valide doit contenir au moins 3 points
            if( count( $pts ) > 3 )
            {
                    $polyValid[] = $poly;
            }
        }
    }
    
    // Si on a au moins un polygone, on construit le MULTIPOLYGON
    if( count($polyValid) > 0 )
    {
		// 21/01/2014 GFS - Bug 39267 - [SUP][T&A CB][#[#40153][MCI] : Wrong GIS Calculation
		$poly = implode(")),((",$polyValid);
		if (strpos($poly, "POLYGON") === FALSE) {
        	return "MULTIPOLYGON(((".$poly.")))";
		}
		else {
			return $poly;
		}
    }
    return false;
} // End function checkPolygones()

function insertFromOutputFile($pathfile, $result_table, $na, $srid, $database)
{

	$no_errors = false;

	$elts = array();

	$fc = file($pathfile);	// on stocke le contenu du fichier dans un tableau

	// On insere chaque ligne du fichier dans la table contenant les polygones

	for ($i=0; $i < count($fc); $i++) {
		
		if ($fc[$i] != "") {
			
			$elts = explode(':', $fc[$i]);

                        // maj 07/06/2011 - MPR : Ajout d'un filtre qui supprime les polygones qui ne sont pas valide
                        $multipoly = checkPolygones($elts[1]);

                        // Si aucun polygone valide identifié, affichage d'un message d'erreur
                        if( !$multipoly )
                        {
                            displayInDemon("No Valid Polygon for {$na} = {$elts[0]}","alert");
                            $no_errors = false;
                        }
                        else
                        {          
                            // 26/12/2007 - Modif. benoit : on précise le srid lors de la conversion puis l'insertion des polygones dans la table de résultats
                            $sql = " INSERT INTO $result_table (na, na_value, p_voronoi)"
                                            ." VALUES('$na', '".$elts[0]."', GeometryFromText('".$multipoly."', ".$srid."))";


                            $result = $database->execute($sql);
                            if ( $result ) {
                                    $no_errors = true;
                            }
                            else
                            {
                                    $no_errors = false;
                            }
                        }
		}
	}
	return $no_errors;
}


/*************************************initialisation***************************************************/

// 04/09/2007 - Modif. benoit : le calcul de Voronoi n'est lancé que si le parametre global "update_coord_geo" est différent de 0 ce qui signifie que des cellules ont été ajoutées ou que les coordonnées de celles existantes ont été modifiées

$sql = "SELECT value FROM sys_global_parameters WHERE parameters = 'update_coord_geo'";
$req = pg_query($database_connection, $sql);

$update = 0;

if (pg_num_rows($req) > 0){
	$row = pg_fetch_array($req, 0);
	$update = $row['value'];
	// TEMP A VIRER 
	$update = 1;
}

if ($update == 0)
{
	print '<span class="v_sub_step_title">Aucun ajout de cellules ou de modification des coordonnées de celles existantes détectée : pas de recalcul de Voronoi</span>';	
}
else 
{
	printdate();
	$start=time();

	// 03/08/2007 - Modif. benoit : on n'utilise plus les fonctions 'get_main_family()', 'get_object_ref_from_family()', 'get_network_aggregation_min_from_family()' de 'php/edw_function_family.php' (pas d'appel à des fichiers hors module)

	//$main_family=get_main_family();//la famille principale

	$sql = "SELECT family from sys_definition_categorie WHERE main_family=1";
	
	$main_family =  $database->getOne( $sql); //la famille principale

	//$object_table=get_object_ref_from_family($main_family);//la table objet correspondate a la famille principale
	//$na_min=get_network_aggregation_min_from_family($main_family);//le na minim de la famille principale

	// maj 01/12/2008 MPR : La table de référence est maintenant edw_object_ref_parameters
	$sql = "SELECT network_aggregation_min FROM sys_definition_categorie WHERE family='$main_family'";
	
	
	$object_table	= $table_params; //la table objet correspondate a la famille principale
	$na_min			= $database->getOne( $sql ); //le na minim de la famille principale

	$result_table="sys_gis_topology_voronoi";//le nom de la table ou sont enregistres les na avec les polygones

	$srid = getGISParam("sys_gis_config_global","srid",$database);//le srid de la table de resultat
	if($srid==null)
		$srid=-1;
	$padding=5.0;//pour l'ecartement des points

	/*fichier*/
	$coordinateFileName=uniqid("voronoi_").".csv";//le nom du fichier permettant de communiquer avec la classe de calcul java
	$coordinateFilePath=REP_PHYSIQUE_NIVEAU_0."png_file/";//le repertoire ou se situe le fichier
	$separator_char=";";//caractere separateur dans le fichier coordinateFileName (Attention a echapper le caractere si c'est un caractere special)

	/*pour exec commande java*/
	// 21/05/2010 : nouveau chemin pour java
	$commandpath="/usr/java/jdk1.5.0_02/bin/";//chemin jusqu a la commande java
	$classpath=REP_PHYSIQUE_NIVEAU_0."gis/gis_class_extern/voronoi/";//repertoire ou sont stockees les fichiers .class


	/*Points virtuels*/
	$progress=false;//pour suivre l'avancement du calcul (Attention : cette option genere un fichier tres volumineux si il y a beaucoup de points)
	$prefix_vp="vp_";//prefixe utilise pour ecrire l'id des points virtuels dans le fichier de coordonnees
	$vp_on=true;//permet d'activer(true) ou de desactiver(false) le calcul des points virtuels
	
	// maj 01/12/2008 - MPR : Ajout du paramètre $database pour exécuter les requêtes SQL
	$distance_max_polygon = getGISParam("sys_gis_config_global","distance_max_voronoi",$database);//pour le calcul des points virtuels
	$angle_de_precision = getGISParam("sys_gis_config_global","angle_de_precision_voronoi",$database);//pour le calcul des points virtuels
	if($distance_max_polygon==null)
		$distance_max_polygon=20000;//distance par defaut si aucune donnee n'est trouvee en base
	if($angle_de_precision==null)
		$angle_de_precision=15;//angle par defaut si aucune donnee n'est trouvee en base

	/***********************************Fin init************************************************************/

	print '<span class="v_sub_step_title">Initialisation : </span>';
	print '<span class="v_sub_step_content">
				Famille principale : '.$main_family.'<br/>
				Table objet : '.$object_table.'<br/>
				NA min : '.$na_min.'<br/>
				SRID : '.$srid.'<br/>
				Ecartement des points : '.$padding.'<br/>
				Distance max polygone : '.$distance_max_polygon.'<br/>
				Angle de précision : '.$angle_de_precision.'<br/>
		   </span>';

	/* Recuperation des points de la table d'objet principal
	  * Principe :
	  *	pour chaque point de la table d'objet principal
	  *		1 - on positionne le point en fonction de son azimuth et d'une distance minimale
	  * 		2 - on recherche le point virtuel potentiel qui lui est associe
	  *		3 - on concatene dans une chaine l'id du point ainsi que ses coordonnees associees
	  * Remarque : les points virtuels ont pour but de limiter les polygones trop grand
	  */
	 print '<span class="v_sub_step_title">Positionnement des points en fonction de leur azimuth et calcul des points virtuels...</span>';
	 print '<span class="v_sub_step_content">';
	
	// 26/12/2007 - Modif. benoit : suppression de la restriction sur les x, y, azimuth positifs dans la requete de selection des na dans la table principale. On selectionne désormais tous ceux qui sont différents de NULL 

	//$query="SELECT $na_min as id,x,y,azimuth FROM $object_table WHERE x>0 AND y>0 AND azimuth>=0 AND on_off<>0 ORDER BY x DESC,y ASC,azimuth DESC";

	// maj 01/12/2008 MPR : On récupère les éléments réseau de niveau minimum dans les tables $table_params (edw_object_ref_parameters) et $table_ref ( edw_object_ref )
	$query = "SELECT eorp_id as id, eorp_x as x, eorp_y as y, eorp_azimuth as azimuth 
			  FROM $table_params, $table_ref 
			  WHERE eorp_x IS NOT NULL AND eorp_y IS NOT NULL AND eorp_azimuth IS NOT NULL 
					AND eor_on_off<>0 AND eor_id = eorp_id AND eor_obj_type = '$na_min' 
			  ORDER BY x DESC,y ASC,azimuth DESC";

	// maj 11:02 01/12/2008 : MPR : Utilisation de la classe DataBaseConnection
	$res = $database->execute($query);
	
	while($row = $database->getQueryResults($res,1)) {
		$result[] = $row;
	}
	
	$nb_pts=count($result);
	print '<span class="v_query">'.$query.'</span>';
	print '<span class="v_sub_step_content">Nombre de points trouvés dans '.$table_params.' : '.$nb_pts.'</span>';
	print '<span class="v_sub_step_content">Avancement : <span id="progress">0</span>/'.$nb_pts.'</span>';
	print '<span class="v_sub_step_content">Nombre de points virtuels calculés : <span id="nb_vp">0</span></span>';
	print '<span class="v_date">Durée : <span id="elapsed">0 mn. 0 s.</span></span>';
	flush();
	$coord=array();
	$list_pts=array();//la liste des points une fois positionne en fonction de leur azimuth
	$coord_vp=array();
	$nb_vp=0;
	$file_content="";
	$begin=time();

	if(count($result) > 0){
		foreach($result as $i=>$row) {
		
			/*positionnement du point en fonction de son azimuth*/
			$coord=getCoordinates($row['x'],$row['y'],$row['azimuth'],$padding);
			$file_content.=$row['id'].$separator_char.$coord[0].$separator_char.$coord[1]."\n";
			$list_pts[$row['id']]=$coord;//on ajoute le point a la liste des points existants

			/*recherche du point virtuel associe*/
			if($vp_on){//si le calcul a ete active
				$coord_vp=getVirtualPoint($table_params,$srid,$row['x'],$row['y'],$row['azimuth'],$angle_de_precision,$distance_max_polygon, $database);
				if(count($coord_vp)>0) { //on regarde si un point virtuel a ete trouve
					$file_content.=$prefix_vp.$nb_vp.$separator_char.$coord_vp[0].$separator_char.$coord_vp[1]."\n";//
					$nb_vp++;
				}
			}
			/*Sert a rendre compte de l'etat d'avancement du calcul des points*/
			//si progress=true alors a chaque point traiter on actualise le nombre de points traites dans le demon
			//si progress=false alors on affiche seulement le nombre tous les 500 points
			if($progress || (($i%499)==0 && $i!=0) || $i==($nb_pts-1)){
				?><script>
					document.getElementById('progress').innerHTML='<?=$i+1?>';
					document.getElementById('nb_vp').innerHTML='<?=$nb_vp?>';
					document.getElementById('elapsed').innerHTML='<?=getTimeInMN(time()-$begin)?>';
				</script><?
			}
			flush();
		}
	}

	/*creation du fichier contenant la liste des points dont on cherche les polygones*/
	print '<span class="v_sub_step_title">Preparation du fichier '.$coordinateFileName.'...</span>';
	flush();
	$coordinateFile=fopen($coordinateFilePath.$coordinateFileName,"w");
	if(!fwrite($coordinateFile,$file_content))//on ecrit tous les points en une seule fois dans le fichier
		print '<span class="v_error_msg">!!!Echec lors de l\'écriture des points dans le fichier : aucun point à enregistrer.</span>';

	else {//ecriture reussie
		fclose($coordinateFile);//fermeture du fichier
		print '<span class="v_sub_step_content">'.($i+$nb_vp).' points écrits.</span>';
		flush();

		/*Lancement du calcul des polygones sur le niveau minimum*/
		print '<span class="v_sub_step_title">Calcul des polygones de Voronoi pour '.$na_min.' : execution de BuildVoronoi.class...</span>';
		$command=$commandpath."java -classpath $classpath BuildVoronoi s=\\$separator_char f=$coordinateFilePath$coordinateFileName";
		print '<span class="v_query">'.$command.'</span>';
		flush();
		$begin=time();
		exec($command,$res,$status);

		print '<div class="v_command_result">';
			foreach($res as $l){
				print $l.'<br/>';
			}
		print '</div>';
		//print $status;
		if($status!=0)
			print '<span class="v_error_msg">!!!Echec du calcul des polygones. Abandon : '.$result_table.' n\'a pas été modifiée.</span>';
		else{
			print '<span class="v_date">Durée du calcul : '.getTimeInMN(time()-$begin).'</span>';
			
			// On vide toute la table au lieu de faire plusieurs deletes
			
			print '<span class="v_sub_step_title">On vide la table '.$result_table.'.</span>';
			pg_query($database_connection,"TRUNCATE $result_table;");

			/*Insertion du niveau minimum*/
			print '<span class="v_sub_step_title">Mise à jour du niveau minimum...</span>';
			$queries=array();//contient l'ensemble des requetes a executer
			// modif 18/05/2007 Gwénaël
				// suppression du DELETE
			//$queries[0]="DELETE FROM $result_table WHERE na='$na_min'";
						
			// 21/12/2007 - Modif. benoit : ajout des valeurs 0, 2 et 3 relatives aux contraintes sur le srid dans le tableau de requetes
			
			$queries[0]="ALTER TABLE sys_gis_topology_voronoi DROP CONSTRAINT \"enforce_srid_p_voronoi\"";
			$queries[1]="COPY $result_table (na_value,p_voronoi) FROM '$coordinateFilePath$coordinateFileName' WITH DELIMITER '$separator_char'";
			$queries[2]="UPDATE $result_table SET p_voronoi = setsrid(p_voronoi, $srid)";
			$queries[3]="ALTER TABLE sys_gis_topology_voronoi ADD CONSTRAINT \"enforce_srid_p_voronoi\" CHECK (srid(p_voronoi)=$srid)";
			$queries[4]="UPDATE $result_table SET na='$na_min' WHERE na IS NULL";
			$queries[5]="DELETE FROM $result_table WHERE na_value LIKE '%$prefix_vp%'";
			
			foreach($queries as $q)
				print '<span class="v_query">'.$q.'</span>';
			flush();
			$begin=time();
			// maj 10:54 01/12/2008 MPR : Utilisation de la classe DatabaseConnection
			if(!pg_query($database_connection,implode(';',$queries))){
				print '<span class="v_error_msg">!!!Erreur SQL : '.implode(';',$queries).'</span>';
			}
			print '<span class="v_date">OK Durée : '.getTimeInMN(time()-$begin).'</span>';

			//modif MD 09 10 2006 => on limite la superficie des polygones
			//recherche des polygones dont la superficie est trop grande
			print '<span class="v_sub_step_title">Decoupage des polygones de superficie trop grande ( >'.$distance_max_polygon.'^2 )...</span>';
			$query="SELECT na_value as id,p_voronoi FROM $result_table WHERE area2d(p_voronoi)>($distance_max_polygon^2) AND na='$na_min'";
			print '<span class="v_query">'.$query.'</span>';
			flush();
			$begin=time();
			// maj 10:54 01/12/2008 MPR : Utilisation de la classe DatabaseConnection
			$res=$database->getAll( $query );

			$nb_result=count($res);
			$poly_intersect=array();//la liste des polygones a decouper
			if($nb_result>0){
				print '<span class="v_sub_step_content">'.$nb_result.' polygone(s) a decouper</span>';
				
				// maj 11:04 01/12/2008 MPR : Utilisation de la classe DataBaseConnection
				
				foreach($res as $row){
					list($x,$y)=$list_pts[$row['id']];
					$xpt=$x-$distance_max_polygon;
					$ypt=$y+$distance_max_polygon;
					$width=2*$distance_max_polygon;
					$square="POLYGON(($xpt $ypt,".($xpt+$width)." $ypt,".($xpt+$width)." ".($ypt-$width).",$xpt ".($ypt-$width).",$xpt $ypt))";
					$query="UPDATE $result_table SET p_voronoi=intersection(p_voronoi,GeometryFromtext('$square',$srid)) WHERE na_value='".$row['id']."'";
					print_r($query);
					if(!pg_query($database_connection,$query)){
						print '<span class="v_error_msg">!!!Echec lors de la mise a jour</span>';
					}
				}
				print '<span class="v_date">OK Durée : '.getTimeInMN(time()-$begin).'</span>';
			} else {
				print '<span class="v_sub_step_content">Aucun polygone a decouper</span>';
			}

			// 24/08/2007 - Modif. benoit : deplacement de la maj des coordonnées de la viewbox afin que celle-ci s'execute avant le calcul des polygones de niveaux supérieurs au niveau minimum qui provoque parfois des erreurs

			// 18/01/2007 - Modif. benoit : maj des parametres du GIS dans 'sys_gis_config_global' des coordonnées de la viewbox maximale et du srid

			// 05/07/2007 - Modif. benoit : pas de maj du SRID (retourne toujours -1 et écrase l'ancien SRID)

			print '<span class="v_sub_step_title">Mise à jour des coordonnées de la viewbox</span>';

			// 24/08/2007 - Modif. benoit : la maj des coordonnées de la viewbox se fait en 2 étapes puisque Postgresql 8.2 ne peut pas utiliser de fonctions agrégées dans un update ("ERROR : cannot use aggregate function in UPDATE")

			/*$sql =	 " UPDATE sys_gis_config_global"
					." SET mapxmin = xmin(extent(p_voronoi)), mapxmax = xmax(extent(p_voronoi)),"
					." mapymin = ymin(extent(p_voronoi)), mapymax = ymax(extent(p_voronoi))"
					." FROM sys_gis_topology_voronoi";*/

			$begin = time();

			$sql1 = "SELECT xmin(extent(p_voronoi)) AS xmin, xmax(extent(p_voronoi)) AS xmax, ymin(extent(p_voronoi)) AS ymin, ymax(extent(p_voronoi)) AS ymax FROM sys_gis_topology_voronoi";

			
			$req1 = $database->getAll( $sql1 );
						
			// maj 01/12/2008 : MPR : Utilisation de la classe DataBaseConnection (modification de la requête en conséquence)
			if ( count($req1) > 0) {
			
				$row1 = $req1;
				$sql2 = "UPDATE sys_gis_config_global SET mapxmin = ".$row1[0]['xmin'].", mapxmax = ".$row1[0]['xmax'].", mapymin = ".$row1[0]['ymin'].", mapymax = ".$row1[0]['ymax'];

		
				print '<span class="v_query">'.$sql2.'</span>';
				flush();

				$req1 = $database->executeQuery($sql2);
				if (!$req1) {
					print '<span class="v_error_msg">!!!Echec lors de la mise a jour</span>';
				}
				else
				{
					print '<span class="v_date">OK Durée : '.getTimeInMN(time()-$begin).'</span>';
				} 
			}
			else // Pas de xmin, xmax, ymin, ymax dans 'sys_gis_topology_voronoi' (cad aucune données)
			{
				print '<span class="v_query">'.$sql1.'</span>';
				flush();
				
				print '<span class="v_error_msg">!!!Echec lors de la mise a jour</span>';
			}

			/* Mise a jour des niveaux d'agregation de niveaux superieurs au niveau minimum */
			
			print '<span class="v_sub_step_title">Calcul des NA > '.$na_min.'...</span>';
			
			$input_file		= REP_PHYSIQUE_NIVEAU_0."gis/gis_temp/".uniqid('in_').".csv";
			$output_file	= REP_PHYSIQUE_NIVEAU_0."gis/gis_temp/".uniqid('out_').".csv";

			$classpath = REP_PHYSIQUE_NIVEAU_0."gis/gis_class_extern/geomunion/";	//dossier des classes

			$begin_all = time();

			$na_to_compute=getNAToCompute($main_family,$na_min,$database);
			
			if(count($na_to_compute)==0)
			{
				print '<span class="v_sub_step_content">Aucun niveau à calculer.</span>';
			}
			else
			{
				foreach($na_to_compute as $na=>$na_source){
					
					print '<span class="v_sub_step_title">Calcul de l\'union des polygones pour '.$na.' à partir de '.$na_source.' :</span>';
									
					// modif 18/05/2007 Gwénaël : suppression du DELETE
					//$query="DELETE FROM $result_table WHERE na='$na';";

					// 01/06/2007 - Modif. benoit : verification que le ratio nombre de $na_source / nombre de $na est inférieur ou égal au ratio configuré dans 'sys_global_parameters'

					// 06/07/2007 - Modif. benoit : modification de la requete pour eviter la division par 0

					// 03/09/2007 - Modif. benoit : suppression de la verification du ratio

					//$sql = "SELECT CASE WHEN (COUNT(DISTINCT $na) > 0) THEN COUNT(DISTINCT $na_source) / COUNT(DISTINCT $na) ELSE 0 END AS ratio FROM edw_object_1_ref WHERE x IS NOT NULL";
				
					// 03/09/2007 - Modif. benoit : remplacement du calcul des polygones des na de niveaux supérieurs en SQL par un programme Java

					// 01/12/2008 MPR : Ajout de la table $table_arc (edw_object_arc_ref pour les récupérer les chemins d'agrégation et de la connexion à la base de données
					if (constructInputFile($na_min, $input_file, $result_table, $object_table, $table_arc,  $na_source, $na, $database )) {
				
						print '<span class="v_sub_step_content">Execution de BuildGeomUnion.class...</span>';
						$command = $commandpath."java -classpath $classpath BuildGeomUnion in=$input_file out=$output_file";
						print '<span class="v_query">'.$command.'</span>';
						flush();
						$begin=time();
						exec($command,$res,$status);

						print '<div class="v_command_result">';
						foreach($res as $l){

							// maj 02/12/2008 MPR : On affiche unqiuement les chaines de caractères 
							if(!is_array($l)){
								print_r($l); print '<br/>';
							}
						}
						print '</div>';
						$res = "";

						// 26/12/2007 - Modif. benoit : prise en compte du nouveau parametre srid lors de l'appel de la fonction 'insertFromOutputFile()'
									
						if($status != 0 || insertFromOutputFile($output_file, $result_table, $na, $srid, $database) == false){
							print '<span class="v_error_msg">!!!Echec de l\'union des polygones pour '.$na.'. Abandon : '.$result_table.' n\'a pas été modifiée.</span>';
						}
						else
						{
							print '<span class="v_date">OK Durée : '.getTimeInMN(time()-$begin).'</span>';	
						}
						if (file_exists($output_file)) unlink($output_file);
					}
					else 
					{
						echo '<span class="v_error_msg">!!!Union des polygones impossible pour le niveau '.$na.'</span>';
					}
					if (file_exists($input_file)) unlink($input_file);
				}
			}

			print '<span class="v_date"><b>Durée de calcul des niveaux supérieurs : '.getTimeInMN(time()-$begin_all).'</b></span>';
		}
		
		// 13/09/07 christophe : à la fin du calcul, on met la variable update_coord_geo de sys_global_parameters à 0
		$sql = " UPDATE sys_global_parameters SET value='0' WHERE parameters = 'update_coord_geo' ";
		$req = pg_query($database_connection, $sql);
	}
	
	print '<span class="v_date" style="font-weight:bold">Durée totale : '.getTimeInMN(time()-$start).'</span>';
	printdate();
}
?>