<?php
/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Cette page enregistre les properties d'un element
*
*	30/01/2009 GHX
*		- modification des requetes SQL pour mettre des valeurs entre cote [REFONTE CONTEXTE]
* 	02/06/2009 SPS
*		- on regarde en base, si pour le graph en cours, on a une position en ordonnee a gauche, sinon on affiche un message d'erreur (correction bug 9773)
*	14/08/2009 GHX
*		- (Evo) Modification pour prendre en compte le faite que dans un graphe on peut avoir plusieurs fois le meme KPI [code+label] identique et qu'il est considere comme un seul
*	 30/11/2009 MPR 
*		- Correction du bug 13105 : On remplace les ' par des espaces
*/

$intranet_top_no_echo = true;
include_once('common.inc.php');


// on recupère les données envoyées au script
// 10:20 30/01/2009 GHX
// Suppression du formatage en INT
$id = $_POST['id'];

// on va chercher le graph pour vérifier qu'on a les droits d'écriture dessus
$query = " --- on va chercher le graph
	SELECT * FROM sys_pauto_page_name WHERE id_page IN 
	 ( SELECT id_page FROM sys_pauto_config WHERE id='$id' )
";
$graph = $db->getrow($query);
if (!allow_write($graph)) {
	echo __T('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_CHANGE_THAT_GTM');
	exit;
}


/* 02/06/2009 - SPS : correction bug 9773
* on regarde en base, si pour le graph en cours, on a une position en ordonnee a gauche, sinon on affiche un message d'erreur
*/
//on selectionne la position en ordonnee de tous les raws/kpis du graph
$requete_ordonnee = "
	SELECT position_ordonnee 
	FROM graph_data 
	WHERE id_data IN (
		SELECT id 
		FROM sys_pauto_config 
		WHERE id_page IN (
				SELECT id_page FROM sys_pauto_config WHERE id ='$id'
		)
		AND id <> '$id' 
	)";	
$t_values = $db->getAll($requete_ordonnee);

//on rajoute la position du raw/kpi que l'on veut sauvegarder
$t_values[]['position_ordonnee'] = $_POST['position_ordonnee'];

$nb_data = count($t_values);
$nb_right = 0;

//on parcourt la liste des positions et on compte celle qui sont a droite
for($i=0;$i<$nb_data;$i++) {
	if ($t_values[$i]['position_ordonnee'] == "right") {
		$nb_right++;
	}
}

//si on a autant de positions a droite que de donnees, cela signifie qu'on en a aucune a gauche
if ($nb_right == $nb_data) {
	echo __T('G_GTM_BUILDER_NO_LEFT_Y_AXIS_POSITION');
	exit;
}
else {
	// maj 30/11/2009 MPR - Correction du bug 13105 : On remplace les ' par des espaces
	$_POST['data_legend'] = str_replace("'", " ", trim($_POST['data_legend']) );
	// on compose la requête d'update
	$query = " --- MaJ du graph_data
		update graph_data
		set 	data_legend='{$_POST['data_legend']}',
			position_ordonnee='{$_POST['position_ordonnee']}',
			display_type='{$_POST['display_type']}',
			line_design='{$_POST['line_design']}',
			color='{$_POST['color']}',
			filled_color='{$_POST['fill_color']}@{$_POST['fill_transparency']}'
		where id_data='$id'
		";
	$db->execute($query);

	$setApplySameStyle = true;
	include dirname(__FILE__).'/graph_ajax_elemts_with_same_name.php';
	
	echo 'ok';
	exit;
}

// debug
$txt = '';
foreach ($_POST as $key => $val) {
	$txt .= "\n$key = $val";
}
echo $txt;
exit;

?>
