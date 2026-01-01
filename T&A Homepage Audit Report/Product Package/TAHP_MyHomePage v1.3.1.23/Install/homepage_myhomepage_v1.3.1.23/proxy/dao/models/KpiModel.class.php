<?php
/**
 * Cette classe permet de manipuler les KPI
 *
 * 10:29 18/10/2010 SCT : BZ 18518 => Vérification de la formule KPI avant activation depuis KPI_Builder
 *  + ajout de la méhode "getFormulaKpi"
 *  + ajout de la méthode "isKpiLockedBh"
 *
 * @author NSE
 */
class KpiModel extends RawKpiModel
{
	
	/**
	 * Message d'erreur
	 * @var string
	 */
	private $_msgErrors = '';
	
	/**
	 * Constructeur
	 *
	 * @author NSE
	 */
	public function __construct ()
	{
		$this->_type1 = 'kpi';
		$this->_type2 = 'kpi';
		$this->_rawkpi_table = self::KPI_TABLE;
		$this->_fieldName = 'kpi_name';
	} // End function __construct
	
	/**
	 * Retourne le nom du KPI à partir de son Id
	 *
	 * @param string $id : l'identifiant du kpi
	 * @param DatabaseConnection $database : intance de DatabaseConnection
	 * @return string
	 */
	public function getNameFromId ( $id, $database )
	{
		$query = "SELECT kpi_name FROM ".self::KPI_TABLE." WHERE id_ligne='{$id}'";
		return $database->getOne($query);
	}
	
	/** 
	 * Retourne le label du KPI à partir de son Id
	 *
	 * @param string $id : l'identifiant du raw
	 * @param DatabaseConnection $database : intance de DatabaseConnection
	 * @return string
	 */
	public function getLabelFromId ( $id, $database )
	{
		$query = "SELECT kpi_label FROM ".self::KPI_TABLE." WHERE id_ligne='{$id}'";
		return $database->getOne($query);
	}
	
	/**
	* Fonction qui retourne le group table du kpi en fonction de son id
	*
	 * string $id : l'identifiant du raw
	 * @param DatabaseConnection $database : intance de DatabaseConnection
	 * @return string
	*/
	public function getGroupTableFromId( $id, $database ){
		$query = "SELECT edw_group_table FROM ".self::KPI_TABLE." WHERE id_ligne='{$id}'";
		return $database->getOne($query);
		
	}

    /**
     * Retourne le nombrede Kpi actifs en base de données
     * @param DataBaseConnection $database
     * @param String $familyLabel
     */
    public static function getNbActiveKpi( $database, $familyLabel )
    {
        return intval( $database->getOne(
                'SELECT COUNT(*)
                    FROM sys_definition_kpi
                    WHERE on_off=1
                    AND new_field<2
                    AND edw_group_table=
                    (
                        SELECT edw_group_table
                        FROM sys_definition_group_table
                        WHERE family=\''.$familyLabel.'\'
                    );'
                ) );
    }

    /**
     * Retourne la formule d'un Kpi
     * 10:29 18/10/2010 SCT : BZ 18518 => Vérification de la formule KPI avant activation depuis KPI_Builder
     *  + ajout de la méhode
     * @param DataBaseConnection $database
     * @param String $idLigne
     * @return String $kpiFormula
     */
    public static function getFormulaKpi($database, $idLigne)
    {
        return $database->getOne('SELECT kpi_formula FROM sys_definition_kpi WHERE id_ligne = \''.$idLigne.'\'');
    }

    /**
     * Retourne si le compteur est défini pour la BH
     * 11:34 18/10/2010 SCT : BZ 18518 => Vérification de la formule KPI avant activation depuis KPI_Builder
     *  + vérification de la présence du Kpi en tant que BH
     * @param integer $pId Id du produit
     * @param string $kpiName Nom du Kpi
     * @param string $family Famille traitée
     * @return bool $resultatRecherche
     */
    public static function isKpiLockedBh($pId, $kpiName, $family)
    {
        $database = Database::getConnection($pId);
        $query_bh = "SELECT * FROM sys_definition_time_bh_formula WHERE family = '".$family."' AND lower(bh_indicator_name) = '".strtolower($kpiName)."' AND bh_indicator_type = 'KPI'";
        $res_bh = $database->execute($query_bh);
        if($database->getNumRows() == 0)
            return false;
        else
            return true;
    }

}
?>