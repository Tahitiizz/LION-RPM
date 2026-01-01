<?php
/**
*	Classe permettant de manipuler les tables de données de l'application
*
*	@author	SCT - 15/10/2010
*	@version	CB 5.1.0.07
*	@since	CB 5.1.0.07
*
*/

include_once REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php';

class DataTableModel
{
    /**
     * Retourne la table de niveau minimum Raw
     * @param string $family nom de la famille
     * @param integer $pId Id du produit
     * @return string $tableDataName
     */
    public static function getMinRawDataTableFromFamily($family, $pId = 0)
    {
        $database = Database::getConnection($pId);
        // recherche edes informations de la famille
        $familyModel = new FamilyModel($family, $pId);
		$edwGroupTable = $familyModel->getEdwGroupTable();
        // type table : RAW | KPI
        $typeTable = 'raw';
        // recherche du NA min de la famille
        $familyNaMinAxe1 = get_network_aggregation_min_from_family($family, $pId);
        $familyNaMinAxe3 = get_network_aggregation_min_axe3_from_family($family, $pId);
        $familyNaMin = $familyNaMinAxe1.($familyNaMinAxe3 ? '_'.$familyNaMinAxe3 : '');
        // recherche du TA min de la famille
        $idFamily = $familyModel->getValues();
        // 27/05/2013 GFS - Bug 33988 - [SUP][T&A OMC NSN NSS 5.1][AVP 35580][ZAIN Iraq]: kpi creation failed only for SGSN family 
        $familyTaMin = TaModel::getTaRawMinFromFamily($idFamily['rank'], $pId);

        return $edwGroupTable.'_'.$typeTable.'_'.$familyNaMin.'_'.$familyTaMin;
    }
}
?>