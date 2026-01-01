<?php
/**
 * fenetre d'info qui se lance sur le bouton 'Data Information' du graph
 * 
 * @author SPS
 * @date 28/05/2009
 * @version CB 5.0.0.0
 * @since CB 5.0.0.0
 * */

session_start();
include_once dirname(__FILE__)."/../../php/environnement_liens.php";

//on recupere l'id du produit
// 30/11/2011 BBX
// BZ 24889 : Correction de la récupération de l'id produit
$product_id = $_GET['id_product']; 
//on recupere les donnees du selecteur des raw/kpi en session
$data = $_SESSION['TA']['selecteur']['investigation_counters_selecteur'];

//on separe les compteurs dans la chaine recuperee
$counters = explode("|s|",$data);
foreach($counters as $counter) {
	//pour chacun des compteurs, on recupere ce qui nous interesse : type et id
	$c = explode("@",$counter);
	$kpi_raw_type = $c[0];
	$kpi_raw_id = $c[1];
	
	//on genere ensuite la requete pour recuperer les infos sur les compteurs
	$name = ($kpi_raw_type=='kpi')? 'kpi_name' : 'edw_field_name';
	$label = ($kpi_raw_type=='kpi')? 'kpi_label' : 'edw_field_name_label';
	$table_name = ($kpi_raw_type=='kpi')? 'sys_definition_kpi' : 'sys_field_reference';
	$formula = ($kpi_raw_type=='kpi')?
	"CASE WHEN substring(kpi_formula from 1 for 4) = 'CASE' THEN substring(kpi_formula from '%ELSE #\"%#\" END' for '#') ELSE kpi_formula END AS formula"
	: " '' AS formula ";
	$query = "
		SELECT
		$name AS legend,
		'".strtoupper($kpi_raw_type)."' AS type,
		$name AS name,
		$label AS label,
		$formula,
		comment AS commentaire,
		CASE WHEN '$name' IN (SELECT saafk_idkpi FROM sys_aa_filter_kpi) THEN true ELSE false END AS link_to_aa
		FROM $table_name
		WHERE id_ligne = '$kpi_raw_id'
	";
	
	//on se connecte sur le produit
        // 31/01/2011 BBX
        // On remplace new DatabaseConnection() par Database::getConnection()
        // BZ 20450
	$db = Database::getConnection($product_id);
	
	//on execute la requete cree plus haut
	$res = $db->getRow($query);
	
	//on cree l'affichage
	$infos_HTML =	'<tr>'
				.'	<td class="title">'.$res['legend'].'&nbsp</td>'
				.'	<td><b><i>'.strtoupper($res['type']).'</b></i> : '.$res['name'].'&nbsp</td>'
				.'	<td>'.$res['label'].'&nbsp;</td>'
				.'	<td>'.((isset($res['formula'])) ? $res['formula'] : "").'&nbsp</td>'
				.'	<td>'.$res['commentaire'].'&nbsp</td>'
				.'	<td class="center">'.($res['link_to_aa'] == "t" ? 'Yes' : 'No') .'&nbsp</td>'
				.'</tr>';

	$data_infos[] = $infos_HTML;
}

?>
<html>
<head>
<title>Investigation Dashboard Information</title>
<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css"/>
</head>
<body bgcolor="#fefefe">
<div class="center">
	<h1>Investigation Dashboard Information</h1>
</div>
<br />
<div id="DataInformation" class="tabPrincipal">
	<div>
		<fieldset>
			<legend class="texteGrisBold">&nbsp;<?= __T('U_DATA_INFORMATION_FIEDSET_DATA_INFORMATION'); ?>&nbsp;</legend>
				<br />
				<table class="graphDataInfo">
					<tr>
						<th><?= __T('U_DATA_INFORMATION_LEGEND_LABEL'); ?></th>
						<th><?= __T('U_DATA_INFORMATION_KPI_RAW_NAME'); ?></th>
						<th><?= __T('U_DATA_INFORMATION_KPI_RAW_LABEL'); ?></th>
						<th><?= __T('U_DATA_INFORMATION_FORMULA'); ?></th>
						<th><?= __T('U_DATA_INFORMATION_COMMENT'); ?></th>
						<th><?= __T('U_DATA_INFORMATION_GO_TO_AA'); ?></th>
					</tr>
				<?= implode('', $data_infos)?>
				</table>
		</fieldset>
	</div>
</div>
</body>
</html>
