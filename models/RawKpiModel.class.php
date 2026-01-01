<?php
/**
 * Cette classe permet de manipuler les Raw/Counters et KPI
 *
 * @author NSE
 * 
 * SPD le 20/06/11: Ajout des méthodes getById et getByIdFam
 */
abstract class RawKpiModel 
{
	/*
	*	Constantes
	*/
	
	// Table sys_definition_kpi
	const KPI_TABLE = 'sys_definition_kpi';
	
	// Table sys_field_reference
	const RAW_TABLE = 'sys_field_reference';

	// Table sys_definition_alarm_static
	const STATIC_ALARM_TABLE = 'sys_definition_alarm_static';

	// Table sys_definition_alarm_dynamic
	const DYNAMIC_ALARM_TABLE = 'sys_definition_alarm_dynamic';

	// Table sys_definition_alarm_top_worst
	const TOP_WORST_ALARM_TABLE = 'sys_definition_alarm_top_worst';

	// Table sys_pauto_page_name
	const GRAPH_TABLE = 'sys_pauto_page_name';

	// Table sys_export_raw_kpi_data
	const DATA_EXPORT_TABLE = 'sys_export_raw_kpi_data';
	
	protected $_rawkpi_table; // à définir par le constructeur : KPI_TABLE ou RAW_TABLE
	protected $_type1; // à définir par le constructeur : raw ou kpi
	protected $_type2; // à définir par le constructeur : counter ou kpi
	protected $_fieldName; // à définir par le constructeur : edw_field_name ou kpi_name
	
        // 04/01/2012 BBX
        // BZ 24174 : déclaration d'un tableau contenant les variables PHP utilisées
        // dans les formules des compteurs / kpi
        // 23/03/2012 BBX
        // BZ 24174 : corection de la valeur par défaut de aggreg_net_ri
        protected $_phpVarsUsedInFormulas = array('aggreg_net_ri' => 0,
            'network' => '',
            'network_ri' => "''",
            'network_ri_dynamic' => '',
            'query_ri' => 0,
            'time_coeff' => 0,
            'time_expected_ri' => 0,
            'hour_ri' => "'inutile'",
            'integration_level' => '',
            'nbre_jour' => 0,
            'nb_sondes_ri' => 0,
            'min_net_ri' => "''",
            'axe3_ri' => '',
            'family_ri' => '',
            'table_object_ri' => "''",
            'network1stAxis' => '',
            'network3rdAxis' => '');

    // Force la classe étendue à définir ces méthodes
    abstract protected function __construct();  // initialiser $_type1 et $_type2
	
	/**
	 * Retourne le nom du RAW/KPI à partir de son Id
	 *
	 * @param string $id : l'identifiant du raw
	 * @param DatabaseConnection $database : intance de DatabaseConnection
	 * @return string
	 */
	abstract protected function getNameFromId ( $id, $database );
	
	/**
	 * Retourne les données du Raw ou Kpi à partir de son Id
	 *
	 * @param string $id : l'identifiant du raw
	 * @param DatabaseConnection $database : intance de DatabaseConnection
	 * @return array
	*/
	public function getById ( $id, $database )
	{
		$query = "SELECT * FROM ".$this->_rawkpi_table." WHERE id_ligne='{$id}'";
		return $database->getRow($query);
	}
	
	/**
	 * Retourne les données du Raw ou Kpi à partir de son nom
	 *
	 * @param string $name : le nom de l'element
	 * @param DatabaseConnection $database : intance de DatabaseConnection
	 * @return array
	*/
	public function getByName($name, $database) {
            // 14/10/2011 BBX
            // BZ 24174 : remplacement de "=" par "ILIKE"
            $query = "SELECT * FROM ".$this->_rawkpi_table." WHERE ".$this->_fieldName." ILIKE '{$name}'";	
            return $database->getRow($query);
	}
		
	/**
	 * Retourne les données d'un RAW ou d'un KPI et la famille associée
	 *
	 * @param string $id : l'identifiant de l'élément (raw ou kpi)
	 * @param DatabaseConnection $database : intance de DatabaseConnection
	 * @return array
	*/	
	public function getByIdFam ($id, $database) {
		$query = "
			SELECT
				el.*,
				sdgt.family,
				sdc.family_label
			FROM 
				".$this->_rawkpi_table." AS el,
				sys_definition_group_table AS sdgt,
				sys_definition_categorie AS sdc
			WHERE
				el.id_ligne = '$id'
				AND el.edw_group_table = sdgt.edw_group_table
				AND sdgt.id_ligne = sdc.rank				 
			";		
			
		return $database->getRow($query);
	}
		
	/**
	 * Retourne le label du RAW/KPI à partir de son Id
	 *
	 * @param string $id : l'identifiant du raw
	 * @param DatabaseConnection $database : intance de DatabaseConnection
	 * @return string
	 */
	abstract protected function getLabelFromId ( $id, $database );
	
    // méthodes communes
	
	/**
     * Retourne l'identifiant d'un RAW/KPI en fonction d'un champ (label, name, ...)
     *
     * @since 5.0.5.06
     * @param string $field Nom du champ (correspond à un nom de colonne)
     * @param string $fieldValue Valeur du champ
     * @param DataBaseConnection $database Instance de DataBaseConnection
     * @return string
     */
    public function getIdFromSpecificField( $field, $fieldValue, $database )
    {
        $query = "SELECT id_ligne FROM {$this->_rawkpi_table} WHERE {$field}='{$fieldValue}' LIMIT 1";
        return $database->getOne($query);
    }

	/**
	 * Retourne la liste des graphes dans lesquels le raw/kpi est utilisé
	 *
	 * @param string $id : l'identifiant d'un raw/kpi
	 * @param string $idProduct : identifiant du produit sur lequel se trouve le raw/kpi
	 * @return array
	 */
	public function getGraphListWith ( $id, $idProduct )
	{
		// Il faut se connecter au master
                // 07/11/2011 BBX BZ 24533 : remplacement new DataBaseConnection() par Database::getConnection()
                // 23/03/2012 BBX
                // BZ 26044 : correction de la requête
		$db = Database::getConnection();
		$query="SELECT id_page FROM ".self::GRAPH_TABLE." WHERE id_page IN 
		(SELECT id_page FROM sys_pauto_config 
		WHERE class_object IN ('{$this->_type1}','{$this->_type2}') AND id_product = '{$idProduct}' AND id_elem = '{$id}')";
		return $db->getAll($query);
	}
	
	/**
	 * Retourne la liste des alarmes dans lesquelles le raw/kpi est utilisé
	 *
	 * @param string $id : l'identifiant d'un raw/kpi
	 * @param DatabaseConnection $database : intance de DatabaseConnection
	 * @return array
	 */
	public function getAlarmListWith ( $id, $database )
	{
		/*la requete suivante retourne la liste des adv_kpi utilises dans des alarmes*/
		$query="
			SELECT alarm_id
			FROM ".self::STATIC_ALARM_TABLE.", ".$this->_rawkpi_table."
			WHERE id_ligne = '".$id."' 
				AND (
					( alarm_trigger_data_field = {$this->_fieldName} AND alarm_trigger_type = '{$this->_type1}' )
					OR
					( additional_field = {$this->_fieldName} AND additional_field_type = '{$this->_type1}' )
				)
			UNION
			SELECT alarm_id
			FROM ".self::DYNAMIC_ALARM_TABLE.", ".$this->_rawkpi_table."
			WHERE id_ligne = '".$id."'
				AND (
					( alarm_field = {$this->_fieldName} AND alarm_field_type = '{$this->_type1}' )
					OR
					( alarm_trigger_data_field = {$this->_fieldName} AND alarm_trigger_type = '{$this->_type1}' )
					OR
					( additional_field = {$this->_fieldName} AND additional_field_type = '{$this->_type1}' )
				)
			UNION
			SELECT alarm_id
			FROM ".self::TOP_WORST_ALARM_TABLE.", ".$this->_rawkpi_table."
			WHERE id_ligne = '".$id."'
				AND (
					( list_sort_field = {$this->_fieldName} AND list_sort_field_type = '{$this->_type1}' )
					OR 
					( additional_field = {$this->_fieldName} AND additional_field_type = '{$this->_type1}' )
				)";
		return $database->getAll($query);
	}

	/**
	 * Retourne la liste des Data Export dans lesquels est utilisé le raw/kpi
	 *
	 * @param string $id : l'identifiant d'un raw/kpi
	 * @param DatabaseConnection $database : intance de DatabaseConnection
	 * @param boolean $visible : TRUE si on doit regardé uniquement dans le Data Export visible si FALSE regarde dans tous les Data Export (default TRUE)
	 * @return array
	 */
	public function getDataExportListWith ( $id, $database, $visible = true )
	{
		$query_de = "
			SELECT DISTINCT
				export_id
			FROM 
				".self::DATA_EXPORT_TABLE."
			WHERE 
				raw_kpi_id = '{$id}'
				AND raw_kpi_type='{$this->_type1}'
			";
		
		if ( $visible == true )
		{
			$query_de .= " AND export_id IN (SELECT export_id FROM sys_export_raw_kpi_config WHERE visible = 1)";
		}
		
		return $database->getAll($query_de);
	}
	
	/**
	 * Retourne la liste de tous les éléments raw ou kpi
	 *
	 * @author GHX
	 * @param DatabaseConnection $database : intance de DatabaseConnection
	 * @param boolean $byFamilies : TRUE si on veut que les compteurs soient regroupés par familles (default FALSE)
	 * @param string $index nom de la colonne que l'on veut en index du tableau, si la colonne n'existe pas on prend id_ligne (default id_ligne)
	 * @return array
	 */
	public function getAll ( $database, $byFamilies = false, $index = 'id_ligne' )
	{
		$result = array();
		$query = "
			SELECT
				el.*,
				sdgt.family,
				sdc.family_label
			FROM 
				".$this->_rawkpi_table." AS el,
				sys_definition_group_table AS sdgt,
				sys_definition_categorie AS sdc
			WHERE
				el.edw_group_table = sdgt.edw_group_table
				AND sdgt.id_ligne = sdc.rank
			";
		$resultQuery = $database->execute($query);
		
		// On vérifie que la colonne existe sinon on prendre par défaut la colonne id_ligne
		$cols = $database->getColumns($this->_rawkpi_table);
		if ( !in_array($index, $cols) )
			$index = 'id_ligne';
		
		if ( $database->getNumRows() )
		{
			if ( $byFamilies == false )
			{
				while ( $row = $database->getQueryResults($resultQuery, 1) )
				{
					$result[$row[$index]] = $row;
				}
			}
			else
			{
				while ( $row = $database->getQueryResults($resultQuery, 1) )
				{
					$result[$row['family']][$row[$index]] = $row;
				}
			}
		}
		
		return $result;
	} // End functiong getAll

        /**
         * Sets the element on status 'to delete' updating 'new_filed' to 2
         * @param string $elemId
         * @param object $database
         */
        public function setToDrop($elemId, $database)
        {
            $query = "UPDATE $this->_rawkpi_table
                    SET new_field = 2
                    WHERE id_ligne = '$elemId'";
            $database->execute($query);
}

        /**
         * Retourne le nombre de RAWs ou KPIs actif (on_off=1) pour une famille
         *
         * @since  5.0.5.00
         * @param  DataBaseConnection $db
         * @param  string $familyId
         * @return integer
         */
        public function getNbEnabledRawKpi( DataBaseConnection $db, $familyId )
        {
            return intval( $db->getOne( "SELECT COUNT(id_ligne)
                            FROM {$this->_rawkpi_table} WHERE on_off=1 AND new_field!=2
                            AND edw_group_table=(SELECT edw_group_table
                            FROM sys_definition_group_table WHERE family='{$familyId}');" ) );
        }

        /**
         * Retourne le nombre de RAWs ou KPIs qui seront déployés au prochain CTS
         *
         * @since 5.0.5.01
         * @param  DataBaseConnection $db
         * @param  string $familyId
         * @return integer
         */
        public function getNbRawKpiToDeployed( DataBaseConnection $db, $familyId )
        {
            return intval( $db->getOne( "SELECT COUNT(id_ligne)
                            FROM {$this->_rawkpi_table} WHERE on_off=1 AND new_field=1
                            AND edw_group_table=(SELECT edw_group_table
                            FROM sys_definition_group_table WHERE family='{$familyId}');" ) );
        }
        
        /**
         * Test la formule d'un Raw ou d'un Kpi
         * 14/10/2011 BBX dans le cadre du BZ 24174
         * @param type $elemName
         * @param type $database
         * @param type $family
         * @param type $product
         * @return type boolean (false en cas de formule incorrecte, true formule correcte, 2 NULL)
         */
        public function testFormula($elemName, $database, $family, $product = '')
        {
            // Gathering information
            $familyModel    = new FamilyModel($family, $product);
            $group          = $familyModel->getEdwGroupTable();
            $netMin         = $familyModel->getValue('network_aggregation_min');
            $na_axe3        = NaModel::getNaFromFamily($family, $product, 3);
            if(!empty($na_axe3)) $netMin.= "_".$na_axe3[0];
            $timeAgg        = TaModel::getTaRawMinFromFamily($familyModel->getValue('rank'), $product);
            $dateFunction   = 'get'.ucfirst($timeAgg);
            $dataTable      = $group.'_raw_'.$netMin.'_'.$timeAgg;
            $date           = Date::$dateFunction(1);

            // Getting formula
            $elemField       = ($this->_type1 == 'raw') ? 'edw_agregation_formula' : 'kpi_formula';
            $elemInformation = $this->getByName($elemName, $database);
            $elemFormula     = $elemInformation[$elemField];

            // Testing formula
            if(empty($elemFormula)) return false;
            if(strtolower(trim($elemFormula)) == 'null') return 2;

            // 04/01/2012 BBX
            // BZ 24174 : déclaration des variables php nécessaires
            foreach($this->_phpVarsUsedInFormulas as $variable => $value) {
                $$variable = $value;
            }

            // Executing formula
            eval('$elemFormula = "'.$elemFormula.'";');
            $queryTest = "SELECT ($elemFormula)::real
                FROM $dataTable
                WHERE $timeAgg = $date";
 
            // Returning test result
            return (!$database->execute($queryTest) ? false : true);
        }
}
?>