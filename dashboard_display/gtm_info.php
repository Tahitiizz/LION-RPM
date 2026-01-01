<?php
/**
 * Page affichant les informations d'un GTM
 * 
 * 17/08/2009 GHX : (Evo) Prise en compte de l'affichage des KPI/RAW identiques (code+legende)
 * 12/01/2011 OJT : Correction bz19809, affichage du label du KPI/RAW (et non de la légende)
 * 05/05/2011 OJT : Ajout des informations sur la famille, BH et Order By
 * 24/10/2011 NSE bz 22761 : on ne remplace finalement pas * par /  :)
*/

session_start();
include_once dirname(__FILE__)."/../php/environnement_liens.php";

$data_info = array();

if (!isset($_GET['id_gtm']))	// Cas du dashboard générique (à définir)
{
	$kpi_raw_type = $_GET['kpi_raw_type'];
    $kpi_raw_id = $_GET['kpi_raw_id'];
	$name = ($kpi_raw_type=='kpi')? 'kpi_name' : 'edw_field_name';
	$label = ($kpi_raw_type=='kpi')? 'kpi_label' : 'edw_field_name_label';
	$table_name = ($kpi_raw_type=='kpi')? 'sys_definition_kpi' : 'sys_field_reference';
	$formula = ($kpi_raw_type=='kpi')?
	"CASE WHEN substring(kpi_formula from 1 for 4) = 'CASE' THEN substring(kpi_formula from '%ELSE #\"%#\" END' for '#') ELSE kpi_formula END AS formula"
	: " '' AS formula ";
	$query = "
		SELECT
		$name AS legend,
		'".strtoupper($_GET['kpi_raw_type'])."' AS type,
		$name AS name,
		$label AS label,
		$formula,
		'' AS commentaire,
		CASE WHEN '$name' IN (SELECT saafk_idkpi FROM sys_aa_filter_kpi) THEN true ELSE false END AS link_to_aa
		FROM $table_name
		WHERE id_ligne = $kpi_raw_id
	";
}
else // GTM "normal"
{
	// Création d'une nouvelle instance de la classe 'GTMModel' afin de récupérer toutes les informations du GTM
	$gtm_model = new GTMModel($_GET['id_gtm']);

    // 05/05/2011 OJT  
    $gtmSortBy = $gtm_model->getGTMSortBy();
  
    // Doit-on afficher les infos BH ?
    $isBHDisplay = ( isset( $_GET['ta'] ) && ( substr( $_GET['ta'], -3, 3  ) == '_bh' ) );

	// On va chercher tous les produits disponibles dans le GTM ainsi que leurs labels
	$gtm_products = $gtm_model->getGTMProducts();

	for ($i=0; $i < count($gtm_products); $i++) {
		$product_infos = getProductInformations($gtm_products[$i]);
		$gtm_product_labels[$gtm_products[$i]] = $product_infos[$gtm_products[$i]]['sdp_label'];
	}

	// On récupère toutes les propriétés du GTM que l'on stocke au format HTML dans un tableau
	$gtm_properties = $gtm_model->getGTMProperties();

    // 17/08/2009 GHX : On prend le deuxieme format des datas soit data2 au lieu de data (les index ne sont pas les mêmes
	foreach ($gtm_properties['data2'] as $id_elem => $elt_values) 
	{	
        if ($elt_values['class_object'] == "raw")
        {
			$elt_info = $gtm_model->getRawInformations($id_elem, $elt_values['id_product']);
		}
		else 
		{
			$elt_info = $gtm_model->getKpiInformations($id_elem, $elt_values['id_product']);

            // 04/05/2011 OJT : Ajout d'espace avant et après les opérateurs
            // afin de permettre le retour à la ligne automatique
            // 10/10/2011 BBX
            // BZ 22986 : correction du remplacement des opérateurs
            $pattern = array( '/\*/', '/\+/', '/\//', '/-/');
            $replace = array( ' * ', ' + ', ' / ', ' - ');
            $elt_info['formula'] = preg_replace($pattern, $replace, $elt_info['formula']);
		}

        // Info de calcul de la BH
        $strInfoBH = '';
        if( $isBHDisplay )
        {
            $familyModel = new FamilyModel( $elt_info['family'], $elt_values['id_product'] );
            $bhInfo = $familyModel->getBHInfos();

            if( count( $bhInfo ) > 0 )
            {
                if( strtolower( $bhInfo['bh_indicator_type'] ) == 'kpi' )
                {
                    $rawKpiModel = new KpiModel();
                    $field       = 'kpi_name';
                }
                else
                {
                    $rawKpiModel = new RawModel();
                    $field       = 'edw_field_name';
                }
                $bhRawKpiId    = $rawKpiModel->getIdFromSpecificField( $field, $bhInfo['bh_indicator_name'], Database::getConnection( $elt_values['id_product'] ) );
                $bhRawKpiLabel = $rawKpiModel->getLabelFromId( $bhRawKpiId, Database::getConnection( $elt_values['id_product'] ) );
                $strInfoBH = "<td><b><i>Calculated on {$bhInfo['bh_indicator_type']}:</i></b><br />{$bhRawKpiLabel}</td>";
            }
            else
            {
                $strInfoBH = "<td><b><i>Calculated on :</i></b><br />No BH defined</td>";
            }
        }

        // 12/01/2011 OJT : Correction bz19809, affichage du label du KPI/RAW (et non de la légende)
        $infos_HTML = '<tr>'
						.'	<td class="title">'.$elt_values['data_legend'].'&nbsp</td>'
						.'	<td><b><i>'.strtoupper($elt_values['class_object']).'</b></i> : '.$elt_info['name'].'&nbsp</td>'
						.'	<td>'.$elt_info['src_label'].'&nbsp;</td>'
						.'	<td>'.$gtm_product_labels[$elt_values['id_product']].'</td>'
                        .'  <td>'.FamilyModel::getLabel($elt_info['family'], $elt_values['id_product']).'</td>'
                        .$strInfoBH
						.'	<td>'.((isset($elt_info['formula'])) ? $elt_info['formula'] : "").'&nbsp</td>'
						.'	<td>'.$elt_info['comment'].'&nbsp</td>'
						.'	<td class="center">'.($elt_info['link_to_aa'] == "t" ? 'Yes' : 'No') .'&nbsp</td>'
						.'</tr>';
		$data_infos[] = $infos_HTML;
	}
}

?>
<html>
<head>
<title>GTM Information</title>
<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css"/>
</head>
<body bgcolor="#fefefe">
<div class="center">
	<img src="<?=$niveau0?>images/titres/gtm_indicator_information_titre.gif"/>
</div>
<br />
<div id="DataInformation" class="tabPrincipal">
	<div>
		<fieldset>
			<legend class="texteGrisBold">&nbsp;<?= __T('U_DATA_INFORMATION_FIEDSET_DATA_INFORMATION'); ?>&nbsp;</legend>
				<br />
                <table class="graphDataInfo" border="0">
					<tr>
						<th><?= __T('U_DATA_INFORMATION_LEGEND_LABEL'); ?></th>
						<th><?= __T('U_DATA_INFORMATION_KPI_RAW_NAME'); ?></th>
						<th><?= __T('U_DATA_INFORMATION_KPI_RAW_LABEL'); ?></th>
						<th><?= __T('U_DATA_INFORMATION_PRODUCT'); ?></th>
                        <th>Family</th>
                        <?php if( $isBHDisplay ) echo '<th>BH Info</th>'; ?>
						<th><?= __T('U_DATA_INFORMATION_FORMULA'); ?></th>
						<th><?= __T('U_DATA_INFORMATION_COMMENT'); ?></th>
						<th><?= __T('U_DATA_INFORMATION_GO_TO_AA'); ?></th>
					</tr>
				<?= implode('', $data_infos)?>
				</table>
		</fieldset>
	</div>
	<?php
	
	// Les div "Information" et "Troubleshooting" ne sont pas inclus dans les infomartions du dashboard générique
	if ( isset($_GET['id_gtm']) ){
	?>
		<div class="texteGris">
			<fieldset>
				<legend class="texteGrisBold">&nbsp;<?= __T('U_DATA_INFORMATION_FIEDSET_GRAPH_DEFINITION'); ?>&nbsp;</legend>
				<br />
				<?= (strlen($gtm_properties['definition']) >= 1) ? nl2br($gtm_properties['definition']) : __T('U_DATA_INFORMATION_NO_GRAPH_DEFINITION') ?>
			</fieldset>
		</div>
		<div class="texteGris">
			<fieldset>
				<legend class="texteGrisBold">&nbsp;<?= __T('U_DATA_INFORMATION_FIEDSET_TROUBLESHOOTING'); ?>&nbsp;</legend>
				<br />
				<?= (strlen($gtm_properties['troubleshooting']) >= 1) ? nl2br($gtm_properties['troubleshooting']) : __T('U_DATA_INFORMATION_NO_TROUBLESHOOTING') ?>
			</fieldset>
		</div>
                <div class="texteGris">
                    <fieldset>
                        <legend class="texteGrisBold">&nbsp;Other information&nbsp;</legend>
	<?php
                            if( isset( $gtmSortBy ) )
                            {
                                echo "<b>Graph default order by ".strtoupper( $gtmSortBy['type'] )."</b> : {$gtmSortBy['name']} ({$gtmSortBy['label']})";
	}
	?>
                    </fieldset>
</div>
            <?php
            }
            ?>
        </div>
</body>
</html>