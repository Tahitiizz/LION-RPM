<?php
/*
*	@cb51000@
*
*	28-06-2010 - Copyright Astellia
* 
*	Composant de base version cb_5.1.0.00
* 
*/
?>
<?php
include_once REP_PHYSIQUE_NIVEAU_0.'class/Formula.class.php';

/**
	Classe définissant les opérations possibles sur une formule de Kpi.
	Elle hérite de la classe Formula (qui implémente l'interface IFormula).
*/
class KpiFormula extends Formula
{
    /**
    * constructeur : initialise la formule
    * @since CB 5.1.0.00
    * @param string $formula
    * @return $this
    */
    public function __construct($formula)
    {
        $this->formula = $formula;
        return $this;
    }

    /**
     * Transforme la formule de l'ErlangB
     * 09:30 18/10/2010 SCT : BZ 18518 => Vérification de la formule KPI avant activation depuis KPI_Builder
     *  + ajout de la méthode
     * @param string $formula
     * @since CB 5.1.0.07
     * @return string formule transformée
     */
    public static function prepareErlangbPregFormula($formula)
    {
        return preg_replace('/(erlangb\([^,]+,[^,]+,[^,]+,.+,\s*null)([^:])/i','$1::real$2',$formula);
    }

    /**
     * Vérifie la formule d'un Kpi
     * 16:38 14/10/2010 SCT : BZ 18518 => Vérification de la formule KPI avant activation depuis KPI_Builder
     *  + ajout de la méthode
     * @param DataBaseConnection $dbConnection
     * @param string $kpiFormula
     * @param string $family
     * @param int $idProduct
     * @since CB 5.1.0.00
     * @return boolean true si la formule est correcte
     */
    public static function checkFormula(DataBaseConnection $dbConnection, $formula, $family, $idProduct)
    {
        $kpiFormula = $formula;
        // récupération du group_table de la famille
        $familyModel = new FamilyModel($family, $idProduct);
        $edwGroupTable = $familyModel->getEdwGroupTable();
        $tableMinRawFamilyName = DataTableModel::getMinRawDataTableFromFamily($family, $idProduct);
       
        // construction de la requête de vérification
        // 16/03/2010 NSE bz 14254 : on vérifie la fomrule même dans le cas erlang, il faut juste préparer la test_query
        // utilisation de la méthode d'OJT à la place de "get_real_formula" de ROYA
        $naMinAxe1 = get_network_aggregation_min_from_family($family, $idProduct);
        $kpiFormula = str_replace("\$network1stAxis", $naMinAxe1, $kpiFormula);
        $naMinAxe3 = get_network_aggregation_min_axe3_from_family($family, $idProduct);
        $kpiFormula = str_replace("\$network3rdAxis", $naMinAxe3, $kpiFormula);
        if(self::isFormulaUsingErlangB($kpiFormula))
        {
            // oui, double stripslashes, sinon il en reste autour du mode
            $kpiFormula=stripslashes($kpiFormula);
            $kpiFormula=stripslashes($kpiFormula);
            // 09/04/2010 NSE bz 14256 problème quand la formule contient null pour tch_counter
            // on caste null en real
            // 22/04/2010 NSE bz 14713 : regexp trop restrictive modifiée
            $kpiFormula = self::prepareErlangbPregFormula($kpiFormula);
        }
        // 29/01/2013 GFS BZ#31079 - [SUP][T&A OMC Huawei BSS][Aircel India]: Can create KPI with wrong formula
        // 29/01/2013 GFS BZ#26374 - [SUP][T&A OMC NSN BSS 5.0][Aircel India]: SELECT can be added at the begining of a KPI formula in the KPI builder
        // 22/08/2017 IN3305 - double stripslashes, sinon il en reste autour du mode
        $kpiFormula = stripslashes($kpiFormula);
        $kpiFormula = stripslashes($kpiFormula);
        $testQueryCheckKpi = 'SELECT *, '.$kpiFormula.' AS "kpi_check_formula" FROM '.$tableMinRawFamilyName.' LIMIT 1 OFFSET 0';
        $testResult = $dbConnection->execute($testQueryCheckKpi);
         
        if($testResult != false): // pg_query returns a resource if success, false otherwise. So we test if the result is "NOT FALSE"           
            return true; // kpi formula can be applied to the database
        else:             
            return false; // kpi formula failed. Therefore it must not be saved
        endif;
    }

    /**
     * Création de la table de niveau minimum de données pour une famille
     * 16:38 14/10/2010 SCT : BZ 18518 => Vérification de la formule KPI avant activation depuis KPI_Builder
     *  + ajout de la méthode
     * @param DataBaseConnection $dbConnection
     * @param string $family
     * @param int $idProduct
     * @since CB 5.1.0.00
     * @return string le nom de la table
     */
    public static function createTableMinFamily(DataBaseConnection $dbConnection, $family, $idProduct)
    {
        // récupération du group_table de la famille
        $familyModel = new FamilyModel($family, $idProduct);
        $edwGroupTable = $familyModel->getEdwGroupTable();
        return DataTableModel::getMinRawDataTableFromFamily($family, $idProduct);
    }


    /**
     * Fonction static retournant le nombre de KPI actifs utilisant une formule ErlangB
     * Créé pour le correction du Bz14796
     * @param DataBaseConnection $dbConnection
     * @return Integer
     */
    public static function getNbKpiUsingErlangB( DataBaseConnection $dbConnection )
    {
        return intval( $dbConnection->getOne( "SELECT COUNT(kpi_formula) FROM sys_definition_kpi
                                        WHERE kpi_formula
                                        LIKE '%erlangb(%'
                                        AND new_field < 2
                                        AND on_off=1"
                                    ) );
    }

    /**
     * Determine si la formule fournie en paramètre utilise la fonction ErlangB
     * @param String $formula
     * @return Boolean
     */
    public static function isFormulaUsingErlangB( $formula )
    {
        return ( strripos( $formula, 'erlangb(' ) !== FALSE );
    }
}
?>