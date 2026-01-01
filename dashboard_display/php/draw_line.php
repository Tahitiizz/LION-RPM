<?php
/*
*	fonctions pour ajouter une ligne sur un ou tous les graphes
*
*	@version CB 4.1.0.00
*	@author SPS
*	@date 07/05/2009
*
*/
session_start();
include_once dirname(__FILE__)."/../../php/environnement_liens.php";

/*classes pour la generation des graphes*/
include_once(MOD_CHARTFROMXML."class/graph.php");
include_once(MOD_CHARTFROMXML."class/SimpleXMLElement_Extended.php");
include_once(MOD_CHARTFROMXML."class/chartFromXML.php");

/*classe pour l'ajout d'une ligne sur un graph*/
include_once dirname(__FILE__)."/../class/DrawLineToGraph.class.php";

//repertoire de stockage des xml et png sur le serveur
$tmp_dir = REP_PHYSIQUE_NIVEAU_0."png_file/";
//acces a ce repertoire depuis l'ihm
$http_dir = NIVEAU_0."/png_file/";

$gtm_name = $_GET['gtm_name'];

/*on ajoute une ligne sur un seul graph*/
if(isset($_GET['gtm_name']) && $_GET['action'] == 'add') {

	//on supprime la ligne du graph
	$rltg = new DrawLineToGraph( $gtm_name, $tmp_dir );
	$rltg->removeLine();
	
	$altg = new DrawLineToGraph( $gtm_name, $tmp_dir );
	//ajout de la ligne
	$altg->addLine($_GET['line_value'],$_GET['legend'],$_GET['color'],$_GET['align']);

	//creation du graph
	$response = $altg->createGraph($http_dir);
	
	$html = $response['text'];
	$png = $response['filename'];
	
	//on affiche le texte (pour requete ajax)
	echo $html;
	
	//mise a jour des variables de session
	changeGTMInSession($gtm_name, $png);
	
}

/* Mise à jour du lien sur le caddie
 * 20/09/2010 BBX : créé dans le cadre du BZ 11945
 */
if(isset($_GET['gtm_name']) && $_GET['action'] == 'updateCart') {

    preg_match('/caddy_update\((.*)\)/', $_GET['currentAction'], $matches);

    $newParameters = array();
    foreach(explode(',',$matches[1]) as $parameter) {
        $newParam = (substr_count($parameter,'.png') > 0) ? '"'.$_GET['newGtm'].'.png"' : $parameter;
        $newParameters[] = stripcslashes($newParam);
    }

    echo implode(',', $newParameters);
}

/* Restauration du lien sur le caddie
 * 20/09/2010 BBX : créé dans le cadre du BZ 11945
 */
if(isset($_GET['gtm_name']) && $_GET['action'] == 'restoreCart') {

    preg_match('/caddy_update\((.*)\)/', $_GET['currentAction'], $matches);

    $newParameters = array();
    foreach(explode(',',$matches[1]) as $parameter) {
        $newParameters[] = stripcslashes($parameter);
    }

    echo implode(',', $newParameters);
}



/*on ajoute une ligne sur tous les graphs*/
if(isset($_GET['multi'])) {
	
	//on recupere les gtm en session
	$tgtm = getGTMFromSession();
	
	// on recupere le nom de chacun des gtm, que l'on concatene, pour envoyer la reponse a la requete ajax
	foreach($tgtm as $gtm) {
		$l_png_file .= $gtm."-";
	}
	echo $l_png_file;	
}



/*on supprime la lignes ajoutee sur un graph*/
if($_GET['action'] == 'remove' ) {
		
		$rltg = new DrawLineToGraph( $gtm_name, $tmp_dir );
		//ajout de la ligne
		$rltg->removeLine();

		$response = $rltg->createGraph($http_dir);
		
		$html = $response['text'];
		$png = $response['filename'];
	
		//on affiche le texte (pour requete ajax)
		echo $html;
		
		changeGTMInSession($gtm_name, $png);
}


/*on regarde dans le xml si l'axe y de droite est utilise*/
if(isset($_GET['gtm_name']) && $_GET['action'] == 'hasRightAxis') {

	$gtm_name = $_GET['gtm_name'];
	
	//on charge le fichier xml
	$xml = simplexml_load_file($tmp_dir.$gtm_name.".xml");		

	foreach($xml->xpath("//datas/data") as $x) {
		$side = $x->attributes()->yaxis;
		if ($side == "right") {
			$right_axis = 1;
			break;
		}
	}
	
	echo $right_axis;
}


/**
 * recupere les gtm qui sont dans la session
 * @return mixed tableau de gtm
 **/ 
function getGTMFromSession() {
	$export_buffer = $_SESSION['dashboard_export_buffer'];
	
	//on recupere le nom des xml en session
	foreach($export_buffer['data'] as $buf) {
		$gtm_name = ereg_replace(".xml","",$buf['xml']);
		$tgtm[] = basename($gtm_name);
	}
	return $tgtm;
}

/**
 * fonction qui va mettre a jour les donnees en session pour changer le nom de l'image modifiee
 * @param string $gtm_name nom du gtm
 * @param string $png_file nom de l'image modifiee
 **/
function changeGTMInSession($gtm_name,$png_file) {
	//on recupere les donnees en session relatives aux graphs
	$export_buffer = $_SESSION['dashboard_export_buffer']['data'];
	
	foreach($export_buffer as $buf) {
		//on teste pour savoir si on est sur le bon xml
		if ( eregi($gtm_name.".xml", $buf['xml'])) {
			//si oui, on prend le nom de l'ancienne image
			$old_img = basename($buf['image']);
			//on remplace le nom de l'image pour le gtm donne
			$buf['image'] = ereg_replace($old_img,$png_file,$buf['image']);
		}
		$new_buf[] = $buf;
	}
	//on remplace les donnees avec les nouvelles dans la session
	$_SESSION['dashboard_export_buffer']['data'] = $new_buf;
	
}

?>
