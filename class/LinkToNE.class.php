<?php
/**
 * Class to create the link to Nova Explorer
 * CB 5.3.1 : Link to Nova Explorer
 * 
 * 06/05/2015 JLG : mantis 6254 : [DE R&D] Amélioration des perfs des liens vers NEx sur bases CAA
 */

require_once( dirname( __FILE__ ).'/CbCompatibility.class.php' );

class LinkToNE {
    
    //Separator to extract Javascript values
    static $JS_SEPARATOR = "|j|";
    
    /**
     * Url of Nova explorer
     * @var string
     */
    var $url = null;
    
    /**
     * List of xdrTypes
     * - 1 for "Signaling"
     * - 2 for "DataSession"
     * - 3 for "Application / Service"
     * - 4 for "PSM"
     * @var integer
     */
    var $xdrType = 0;
    
    /**
     * Start date
     * @var string
     */
    var $dateStart = null;
    
    /**
     * End date
     * @var string
     */
    var $dateEnd = null;
    
    /**
     * List of selected interfaces
     * @var string
     */
    var $interface = null;
    
    /**
     * Filter user to build expression
     * @var string
     */
    var $filter = null;
    
    /**
     * Family name
     * @var string
     */
    var $family = null;
    
    /**
     * Time Agregation
     * @var string
     */
    var $ta = null;
    var $ta_value = null;
    
    /**
     * Network Agregation
     * @var string
     */
    var $na = null;
    var $na_value = null;
    
    /**
     * Network Agregation (third axis)
     * @var string
     */
    var $na_axe3 = null;
    var $na_axe3_value = null;
    
    /**
     * Array for RAW/KPI
     *	array ['type']   => raw or kpi
     *	array ['id']     => id
     * @var array
     */
    var $data = array();
    
    /**
     * Product id
     * @var int
     */
    var $id_product = 0;
    
    /**
     * T&A Database connection
     * @var Ressource
     */
    var $db = null;
    
    /**
     * Debug mode (0:deactivated / 1:activated)
     * @var integer
     */
    var $debug = 0;
    
    
    /**
     * Get url
     */
    public function getUrl(){
        return $this->url;
    }
    
    
    /**
     * Get xdrtype
     */
    public function getXdrType(){
        return $this->xdrType;
    }
    
    
    /**
     * Get dateStart
     */
    public function getDateStart(){
        return $this->dateStart;
    }
    
    
    /**
     * Get dateEnd
     */
    public function getDateEnd(){
        return $this->dateEnd;
    }
    
    
    /**
     * Get interface
     */
    public function getInterface(){
        return $this->interface;
    }
    
    
    /**
     * Get filter
     */
    public function getFilter(){   
        
        $stringFilter = array();
        $stringFilterName = null;
        $stringFilterValue = null;
        $cpt = 0;
        
        foreach($this->filter as $filt){

            if($cpt == 0)
                $stringFilterName = "&filter=";
            else
                $stringFilterValue = $stringFilterValue . " AND ";

            //No value for operator IS_NULL and IS_NOT_NULL
            if(strcasecmp($filt['operator'],"IS_NULL") == 0 || strcasecmp($filt['operator'],"IS_NOT_NULL") == 0)
                $stringFilterValue = $stringFilterValue . "\"" . $filt['column'] . "\" " . $filt['operator'];
            //Bug 34460 - [INT][2.1.0.2] Links not valid from T&A product
            else if(strcasecmp($filt['operator'],"IN") == 0 || strcasecmp($filt['operator'],"NOT IN") == 0)
                $stringFilterValue = $stringFilterValue . "\"" . $filt['column'] . "\" " . $filt['operator'] . " " . $this->convertContextValueToNeValue($filt['value']);
            else
                $stringFilterValue = $stringFilterValue . "\"" . $filt['column'] . "\" " . $filt['operator'] . " \"" . $filt['value'] . "\"";

            $cpt++;
        }
        
        $stringFilter['noencoded'] = $stringFilterName . $stringFilterValue;
        $stringFilter['encoded'] = $stringFilterName . urlencode($stringFilterValue);
        
        if($this->debug){
            echo '<br><b>********************************************************************</b><br><br>'
                    . '<b>' . basename(__FILE__) . '</b> : function <b>' . __FUNCTION__ .'</b><br>'
                    . '<b>Filter name : </b>'. $stringFilterName .'<br><br>'
                    . '<b>Filter value : </b>'. $stringFilterValue .'<br><br>'
                    . '<b>Filter noencoded : </b>'. $stringFilter['noencoded'] .'<br><br>'
                    . '<b>Filter encoded : </b>'. $stringFilter['encoded'] .'<br>';
        }
       
        return $stringFilter;
    }

    /**
	 * Get server and db name (for optimizing nex performance)
	 * Example : 10.35.4.201:8601/dbname_01,10.35.4.202:8601/dbname_02
     * Mantis 6254
	 */
	public function getServerAndDb() {
		if ( $this->error !== null ) // S'il y a eu une erreur avant pas la peine d'aller plus loin
			return;

		// modif 13:30 23/10/2007 Gwen
			// Ajout d'une condition dans le WHERE : saab_tag = 'CSPS'
			// et ajout de la condition $this->tag != 'CSPS' : si on a le tag CSPS cele revient à choisir toutes les bases donc pas bession de présicer le tag
		// modif 17:01 25/10/2007 Gwen
			// Ajout des fonctions MIN et MAX [bug: 5224 : Plusieurs liens vers AA pointant vers meme base]


		// 14/12/2009 BBX
		// DEBUT BZ 13300
		// Récupération du séparateur de la famille
		$query_separator = 'SELECT separator FROM sys_definition_categorie WHERE family = \''.$this->family.'\'';
		$separator = $this->db->getOne($query_separator);
		// Préparation des éléments de la condition sur le NA (sys_aa_base)
		$saabNaOperator = " = ";
		$saabNaCondition  = "'".$this->getNaValue()."'";
		// Si on doit splitter l'élément réseau, alors on fait un IN sur les éléments découpés
		if(!empty($separator) && $this->splitNa)
		{
			$saabNaOperator = " IN ";
			$saabNaCondition = "('".implode("','",explode($separator,$this->getNaValue()))."')";
		}
		// Pour saab_na, l'opérateur et la valeur sont désormais calculés ci-dessus
		$query_server = "
			SELECT saas_host, saas_port, saas_login, saas_password, saab_database, MIN(saab_hourstart) AS hourstart, MAX(saab_hourend) AS hourend, saab_tag
			FROM sys_aa_server, sys_aa_base
			WHERE saas_idserver = saab_idserver
				AND saab_ta = '". substr($this->ta_value, 0, 8) ."'
				AND saab_na ".$saabNaOperator." ".$saabNaCondition;
                
                $saabTagsCondition = "";
                // MPR : 26/10/2011 - Correction du bz 24403
                // Suppression de la condition écrite en dur avec CSPS et ajout d'un traitement générique sur les tags AA    
                if( !empty( $this->tag ) )
                {
                    $saabTags = array();
                
                    $saabTagsCondition = " AND ( ";
                    
                    foreach ( $this->generateAllTagsPossible( $this->tag ) as $tag )
                    {
                        $saabTags[] = "'{$tag}'";
                    }
                    $saabTagsCondition.= " saab_tag IN (". implode( ",", $saabTags ).") OR saab_tag IS NULL OR saab_tag = '' )";
                }
                // ( empty($this->tag )&& $this->tag != 'CSPS' ? '' : "AND (saab_tag = '". $this->tag ."' OR saab_tag = 'CSPS' OR saab_tag IS NULL OR saab_tag = '')" ) ."
                $query_server .= $saabTagsCondition;
                
		// FIN BZ 13300

		if ( $this->ta == 'hour' ) {
			$_hour = substr($this->ta_value, -2);
			// modif 16:00 22/08/2007 Gwénaël
				//modif de la condition WHERE
			// modif 09:49 03/09/2007 Gwénaël
				// ajout de la condition BETWEEN dans le WHERE
			$query_server .= "
				AND (
					'". $_hour ."00' BETWEEN saab_hourstart AND saab_hourend
					OR (
						saab_hourstart LIKE '". $_hour ."%'
						OR saab_hourend LIKE '". $_hour ."%'
						AND saab_hourend NOT LIKE '%00'
					)
				)
				";
		}

		// 17:00 25/10/2007 Gwen
			// Ajout du GROUP BY suite à l'utilisation des fonctions MIN et MAX dans le SELECT
		$query_server .= " GROUP BY saas_host, saas_port, saas_login, saas_password, saab_database,saab_tag";

		if ( $this->debug ) {
			echo '<b>$query_server = </b><pre>'. $query_server .'</pre>';
		}

		if ( $result_server = $this->db->execute($query_server) ) {
			if ( $this->db->getNumRows() > 0 ) {

				// >>>>>>>>>>
				// modif 11:15 11/03/2008 Gwen
					// Si aucun résultat on regarde s'il n'y a pas des bases qui n'ont pas de NA spécifiés (NA == null)
				if ( $this->db->getNumRows() == 0 ) {
					$query_server = str_replace("saab_na = '". $this->getNaValue() ."'", "(saab_na IS NULL OR saab_na = '')", $query_server);

					if ( $this->debug ) {
						echo '<b>Recherche des bases sans NA spécifiés</b><br />$query_server BIS =<pre>'.$query_server.'</pre>';
					}

					if ( !($result_server == $this->db->execute($query_server)) ) {
						$this->error = __T('U_E_LINK_AA_SQL_INVALID_SERVER');
						return;
					}
				}
				// <<<<<<<<<<
				while ( $row = $this->db->getQueryResults($result_server, 1) ) {
					$this->servers[] = array(
											'host'      => $row['saas_host'],
											'port'      => $row['saas_port'],
											'login'     => $row['saas_login'],
											'pwd'       => $row['saas_password'],
											'db'        => $row['saab_database'],
											'tag'       => $row['saab_tag'],
											'hour_start'=> ( isset($row['hourstart']) ? $row['hourstart'] : null ),
											'hour_end'  => ( isset($row['hourend']) ? $row['hourend'] : null )
										);
				}
			}
			else // Si aucun résultat, il sera impossible de se connecter à une base avec AA
				$this->error = __T('U_E_LINK_AA_NO_RESULT_SERVER');
		}
		else // Erreur SQL
			$this->error = __T('U_E_LINK_AA_SQL_INVALID_SERVER');

		if ( $this->debug ) {
			echo '<b>$this->servers = </b><pre>';
			print_r($this->servers);
			echo '</pre>';
		}
		if (count($this->servers) > 0) {
			$serversA = array();
			foreach($this->servers as $server) {
				$serversA[] = $server['host'] . ":" . $server['port'] . "/" . $server['db'];
			}
			$link = "&databases=" . urlencode(implode(',', $serversA));
			return $link;
		} else {
			return "";
		}
	}
	
		/**
	 * Retourne un booléen indiquant si l'on doit utiliser le 3ème axe pour AA
	 * Créé le 17/09/2008 par BBX. BZ 7427
	 *
	 *	16:15 28/05/2009 GHX
	 *		- Modif pour prendre en compte la classe DatabaseConnection()
	 *
	 * @version CB4.1.0.00
	 * @return bool
	 */
	private function useThirdAxis()
	{
		$link_to_aa_axe3 = false;
		$query_aa_axis = "SELECT link_to_aa_3d_axis FROM sys_definition_categorie WHERE family = '{$this->family}'";
		$array_aa_axis = $this->db->getRow($query_aa_axis);
		// 16:04 03/08/2009 GHX
		// Correction du BZ 7427
		if($array_aa_axis['link_to_aa_3d_axis']=='t') $link_to_aa_axe3 = true;
		return $link_to_aa_axe3;
	}

	/**
	 * Retourne la valeur du niveau parent si on est sur le niveau minimum sinon la valeur contenu dans $this->na_value
	 * Exemple :
	 *	- si la na est SAI, la valeur retourné est la valeur du RNC
	 *	- si la na est RNC, la valeur $this->na_value est retourné
	 *
	 *	16:17 28/05/2009 GHX
	 *		- Modif pour prendre en compte la classe DatabaseConnection()
	 *
	 * @version CB4.1.0.00
	 * @return string
	 */
	private function getNaValue () 
	{
		// modif 17/09/2008 BBX : on regarde si on doit prendre en compte le 1er axe ou le 3eme axe
		if($this->useThirdAxis()) return $this->na_axe3_value;

		// 16/01/2012 BBX
		// BZ 25296 : requête qui récupère les parents avec AA activé
		// Si plusieurs parents, le parent de level et rank minimum est retourné
		$query = "SELECT agregation
		FROM sys_definition_network_agregation
		WHERE family = '{$this->family}'
		AND link_to_aa = 1
		AND level_source = '{$this->na}'
		AND agregation != '{$this->na}'
		ORDER BY agregation_level ASC, agregation_rank ASC
		LIMIT 1";
		$naParent = $this->db->getOne($query);

		// Debug
		if ( $this->debug ) {
				echo '<b>Query parents = </b><pre>';
				print_r($query);
				echo '</pre>';
				echo '<b>$naParent = </b>'. (empty($naParent) ? 'No parent with AA links enabled' : $naParent) .'<br />';
		}

		// 16/01/2012 BBX
		// BZ 25296 : Si pas de parent, on retourne l'élément courant
		if(empty($naParent))
		{
			return $this->na_value;
		}
		// Sinon, on retourne le parent
		else
		{
			// 16:59 28/05/2009 GHX
			// Modification pour récupérer le parent
			$queryParent = "
			SELECT eoar_id_parent
			FROM edw_object_arc_ref
			WHERE
			eoar_arc_type = '{$this->na}|s|{$naParent}'
			AND eoar_id = '{$this->na_value}'";

			// Débug
			if ( $this->debug ) {
				echo '<b>$queryParent = </b><pre>'. $queryParent .'</pre>';
			}

			// maj 27/01/2010 : Correction du BZ13934 : Modification de la condition lorsque le NA max ne possède pas de liens vers AA
			if ( !($parentValue = $this->db->getOne($queryParent))) {
				$this->error = __T('U_E_LINK_AA_SQL_INVALID_SERVER');
			}

			if ( $this->debug ) {
				echo '<b>$parentValue = </b>'. $parentValue .'<br />';
			}

			return $parentValue;
		}
	} // End getNaValue
	
    /**
     * Constructor
     */
    public function LinkToNE(){
        $this->debug = get_sys_debug('launch_NE');

        if($this->debug){
            echo '<br><b>********************************************************************</b><br><br>'
                    . '<b>' . basename(__FILE__) . '</b> : function <b>' . __FUNCTION__ .'</b><br>'
                    . '<b>this->debug = </b>'. $this->debug .'<br>';
        }
    }
    
    
    /**
     * 
     */
    public function setParameters($values){
        
        $separateur = get_sys_global_parameters('sep_axe3');
        $_JSvalues = explode(self::$JS_SEPARATOR, $values);
        $_values = explode($separateur, $_JSvalues[0]);
        
        $this->family        = $_values[0];
        $this->ta            = $_values[1];
        $this->ta_value      = $_values[2];
        $this->na            = $_values[3];
        $this->na_value      = $_values[4];
        $this->na_axe3       = ( !empty($_values[5]) ? $_values[5] : null );
        $this->na_axe3_value = ( !empty($_values[6]) ? $_values[6] : null );

        $values_data = explode('@', $_values[7]);
        $this->data = array (
                'type'  => $values_data[0],
                'id'    => $values_data[1],
        );

        $this->id_product = $_values[8];
        $this->db = Database::getConnection($this->id_product);
        
        if($this->debug){
            echo '<br><b>********************************************************************</b><br><br>'
                    . '<b>' . basename(__FILE__) . '</b> : function <b>' . __FUNCTION__ .'</b><br>'
                    . '<b>values = </b>'. $values .'<br>'
                    . '<b>separateur = </b>'. $separateur .'<br>'
                    . '<b>this->family = </b>'. $this->family .'<br>'
                    . '<b>this->ta = </b>'. $this->ta .'<br>'
                    . '<b>this->ta_value = </b>'. $this->ta_value .'<br>'                  
                    . '<b>this->na = </b>'. $this->na .'<br>'
                    . '<b>this->na_value = </b>'. $this->na_value .'<br>'
                    . '<b>this->na_axe3 = </b>'. $this->na_axe3 .'<br>'
                    . '<b>this->na_axe3_value = </b>'. $this->na_axe3_value .'<br>'
                    . '<b>this->id_product = </b>'. $this->id_product .'<br>'
                    . '<b>this->data = </b>'. print_r($this->data,true) .'<br>';
        }
                
    }
    
    
    /**
     * Set url
     */
    public function setUrl(){
        $this->url = get_sys_global_parameters('url_NE');
        
        if($this->debug){
            echo '<br><b>********************************************************************</b><br><br>'
                    . '<b>' . basename(__FILE__) . '</b> : function <b>' . __FUNCTION__ .'</b><br>'
                    . '<b>this->url = </b>'. $this->url .'<br>';
        }
    }
    
    
    /**
     * Set xdrType
     */
    public function setXdrType(){
        $result = $this->db->execute("SELECT snefk_xdrtype FROM sys_ne_filter_kpi
                                        WHERE snefk_idkpi = '". $this->data['id'] ."'
                                        AND snefk_type = '". $this->data['type'] ."'
                                        AND snefk_family = '". $this->family ."'");
        $row = $this->db->getQueryResults($result, 1);
        $this->xdrType = $row['snefk_xdrtype'];
        
        if($this->debug){
            echo '<br><b>********************************************************************</b><br><br>'
                    . '<b>' . basename(__FILE__) . '</b> : function <b>' . __FUNCTION__ .'</b><br>'
                    . '<b>this->xdrType = </b>'. $this->xdrType .'<br>';
        }
    }
    
    
    /**
     * Set dateStart
     */
    public function setDateStart(){
        if($this->ta == "hour")
            $this->dateStart = "L" . $this->ta_value . "0000";
        //day
        else
            $this->dateStart = "L" . $this->ta_value . "000000";
        
        if($this->debug){
            echo '<br><b>********************************************************************</b><br><br>'
                    . '<b>' . basename(__FILE__) . '</b> : function <b>' . __FUNCTION__ .'</b><br>'
                    . '<b>this->ta = </b>'. $this->ta .'<br>'
                    . '<b>this->ta_value = </b>'. $this->ta_value .'<br>';
        }
    }
    
    
    /**
     * Set dateEnd
     */
    public function setDateEnd(){
        if($this->ta == "hour") {
			$hour = substr($this->ta_value, 8, 2);
			// on détermine l'heure suivante
			$hour = $hour + 1;
			// on formate l'heure sur 2 caractères
			$hour = (strlen($hour) == 1) ? "0".$hour : $hour;
	       	$this->dateEnd = "L" . substr($this->ta_value, 0, 8) . $hour . "0000";
        }
        //day
        else {
        	$day = substr($this->ta_value, 6, 2);
        	// on détermine le jour suivant
        	$day = $day + 1;
        	// on formate le jour sur 2 caractères
        	$day = (strlen($day) == 1) ? "0".$day : $day;
        	$this->dateEnd = "L" . substr($this->ta_value, 0, 6) . $day . "000000";
        }
        
        if($this->debug){
            echo '<br><b>********************************************************************</b><br><br>'
                    . '<b>' . basename(__FILE__) . '</b> : function <b>' . __FUNCTION__ .'</b><br>'
                    . '<b>this->ta = </b>'. $this->ta .'<br>'
                    . '<b>this->ta_value = </b>'. $this->ta_value .'<br>';
        }
    }
    
    
    /**
     * Set interface
     */
    public function setInterface(){
        $result = $this->db->execute("SELECT snefk_interface FROM sys_ne_filter_kpi
                                        WHERE snefk_idkpi = '". $this->data['id'] ."'
                                        AND snefk_type = '". $this->data['type'] ."'
                                        AND snefk_family = '". $this->family ."'");
        $row = $this->db->getQueryResults($result, 1);
        $this->interface = $row['snefk_interface'];
        
        if($this->debug){
            echo '<br><b>********************************************************************</b><br><br>'
                    . '<b>' . basename(__FILE__) . '</b> : function <b>' . __FUNCTION__ .'</b><br>'
                    . '<b>this->interface = </b>'. $this->interface .'<br>';
        }
    }
    
    
    /**
     * Set filter
     */
    public function setFilter(){
        $this->getTelecomFilterFromContext();
        $this->getContextuelFilterFromContext();
        
        if($this->debug){
            echo '<br><b>********************************************************************</b><br><br>'
                    . '<b>' . basename(__FILE__) . '</b> : function <b>' . __FUNCTION__ .'</b><br>'
                    . '<b><pre>this->filter = </b>'. print_r($this->filter,true) .'</pre><br>';
        }
    }
    
    
    /**
     * Get telecom filter from context
     */
    public function getTelecomFilterFromContext(){   
        
        $query_filter = "
                SELECT snec_name, sneo_name, snelf_value
                FROM sys_ne_filter_kpi, sys_ne_list_filter, sys_ne_operator, sys_ne_column
                WHERE snefk_idfilter = snelf_idfilter
                        AND snelf_idoperator = sneo_idoperator
                        AND snelf_idcolumn = snec_idcolumn
                        AND snefk_idkpi = '". $this->data['id'] ."'
                        AND snefk_type = '". $this->data['type'] ."'
                        AND snefk_family = '". $this->family ."'
                ORDER BY snelf_order";
        
        if($this->debug){
            echo '<br><b>********************************************************************</b><br><br>'
                    . '<b>' . basename(__FILE__) . '</b> : function <b>' . __FUNCTION__ .'</b><br><br>'
                    . '<b>query_filter = </b>'. $query_filter .'<br><br>';
        }
        
        $result_filter = $this->db->execute($query_filter);
        if ($result_filter) {
            if ( $this->db->getNumRows() > 0 ) {
                $_filter = array();
                while ( $row =  $this->db->getQueryResults($result_filter, 1) ) {
                    $_filter[] = array(
                            'column'   => $row['snec_name'], // nom colonne
                            'operator' => $row['sneo_name'], // opérateur
                            'value'    => $row['snelf_value'] // valeur
                    );
                }
                unset($result_filter);
                $this->filter = $_filter;
            }
        }

       if($this->debug){
            echo '<b>Filter : </b><pre>'. print_r($this->filter,true) .'</pre><br>';
       }
    }
    
    /*public function getTelecomFilterFromContext(){   
        
        $query_filter = "
                SELECT snec_name, sneo_name, snelf_value, CASE WHEN snec_withcode THEN 'code' ELSE 'nocode' END AS index, snec_idcolumn
                FROM sys_ne_filter_kpi, sys_ne_list_filter, sys_ne_operator, sys_ne_column
                WHERE snefk_idfilter = snelf_idfilter
                        AND snelf_idoperator = sneo_idoperator
                        AND snelf_idcolumn = snec_idcolumn
                        AND snefk_idkpi = '". $this->data['id'] ."'
                        AND snefk_type = '". $this->data['type'] ."'
                        AND snefk_family = '". $this->family ."'
                ORDER BY snelf_order";
        
        if($this->debug){
            echo '<br><b>********************************************************************</b><br><br>'
                    . '<b>' . basename(__FILE__) . '</b> : function <b>' . __FUNCTION__ .'</b><br><br>'
                    . '<b>query_filter = </b>'. $query_filter .'<br><br>';
        }
        
        $result_filter = $this->db->execute($query_filter);
        if ( $result_filter ) {
            if ( $this->db->getNumRows() > 0 ) {
                $_filter = array();
                while ( $row =  $this->db->getQueryResults($result_filter, 1) ) {
                    $_filter[$row['index']][] = array(
                            'column'   => $row['snec_name'], // nom colonne
                            'operator' => $row['sneo_name'], // opérateur
                            'value'    => $row['snelf_value'], // valeur
                            'idcolumn' => $row['snec_idcolumn'] // identifiant de la column
                    );
                }
                unset($result_filter);

                // si des conditions du filtre ont des codes colonnes
                if ( count($_filter['code']) > 0 ) {
                    // Récupère le type du parser
                    // 15:48 25/08/2009 GHX
                    // Correction du BZ 11056
                    // Il faut passé l'id du produit
                    $module = get_sys_global_parameters('module', null, $this->id_product);
                    // Pour chaque condition on va récupére les codes colonnes en fonction de value
                    foreach ( $_filter['code'] as $index => $condition ) {
                        $tmp_result = array();

                        // modif 12:08 03/10/2007 Gwénaël
                                //modification de la requete qui récupère les codes colonnes
                        $replace_old = array(
                                                0 => '*',
                                                1 => ','
                                        );
                        $replace_new = array(
                                                0 => '%',
                                                1 => '|'
                                        );

                        $query_condition = "
                                SELECT snecc_necode
                                FROM sys_ne_column_code
                                WHERE snecc_idcolumn = '". $condition['idcolumn'] ."'
                                        AND snecc_fc_label SIMILAR TO '". str_replace($replace_old, $replace_new, $condition['value']) ."'
                                ";
                        
                        if($this->debug){
                            echo '<b>query_condition = </b>'. $query_condition .'<br><br>';
                        }
                        
                        $result_condition = $this->db->getAll($query_condition);
                        foreach ( $result_condition as $row )
                        {
                                $tmp_result[] = $row['snecc_necode'];
                        }
                        unset($result_condition);
                        // Remplace la valeur par le contenu du tableau $tmp_result
                        $_filter['code'][$index]['value'] = '('. implode(',', $tmp_result) .')';
                    }

                    if ( count($_filter['nocode']) > 0 ) { // Union des 2 tableaux
                        $this->filter = array_merge($_filter['nocode'],$_filter['code']);
                    }
                    else
                        $this->filter = $_filter['code'];
                }
                else // Les conditions n'ont pas de filtre spécifiques par rapport à des codes colonnes
                    $this->filter = $_filter['nocode'];
            }
        }

       if($this->debug){
            echo '<b>Filter : </b><pre>'. print_r($this->filter,true) .'</pre><br>';
       }
    }*/
    
    
    /**
     * Get contextuel filter from context
     */
    public function getContextuelFilterFromContext(){   

        // 14:44 18/12/2008 SCT : recherche de l'élément séparateur de la famille
        // NSE 22/02/2010 ajout de la condition sur la famille (comme en 4.0)
        $query_separator = 'SELECT separator FROM sys_definition_categorie WHERE family = \''.$this->family.'\'';       
        $separator = $this->db->getOne($query_separator);

        // 04/07/2014 GFS - Bug 42424 - [SUP][T&A CB][#46416][MeditelMaroc]Link To NovaExplorer : filtering on network element is not functional
        // 07/01/2015 FGD - Bug 46081 - [SUP][T&A CB][#48966][MeditelMaroc]: Link to NE, incorrect filtering on ServiceKey, based on label instead of ID
        if ($this->db->columnExists("sys_ne_contextuel_filter", "snecf_use_code")) {
			$selectColumns = ", snecf_use_code, snecf_use_case";
		}
		else {
			$selectColumns = ", 0 as snecf_use_code, 0 as snecf_use_case";
		}
        // 10/12/2009 NSE : ajout de la condition IS NULL sur les groupes de filtres pour que la requête retourne un résultat même si aucun groupe n'est défini. BZ 13342
        // 15/07/2011 MMT Bz 22810 snecf_before_value et snecf_after_value ne sont pas pris en compte
        // 09/12/2011 NSE DE new parameters in NE links contextual filters : ajout des colonnes si le slave les connait
        // 22/12/2011 NSE bz 25255 : snecf_after_value not available
        // 21/03/2014 GFS Bug 40080 - [REC][Core CS 5.3.1.01][TC #TA-62630][Link to NEx]: There is a warning message in the console panel when link to NEx from "IN" dashboard
        $query_contextuel_filter = "
            SELECT DISTINCT snec_name, snecf_type, snecf_before_value, snecf_after_value".$selectColumns." 
            FROM sys_ne_contextuel_filter, sys_ne_column, sys_ne_filter_kpi
            WHERE snecf_idcolumn = snec_idcolumn
                    AND (
                            snecf_type = '". $this->na ."' ".( $this->na_axe3 != null ? "OR snecf_type = '".$this->na_axe3."'" : "" )."
                    )
                    AND (
                            snecf_group_filter = snefk_group_filter
                            OR (  snecf_group_filter IS NULL AND snefk_group_filter IS NULL )
                    )
                    AND snefk_idkpi = '".$this->data['id']."'
            ";
        
        if($this->debug){
            echo '<br><b>********************************************************************</b><br><br>'
                    . '<b>' . basename(__FILE__) . '</b> : function <b>' . __FUNCTION__ .'</b><br><br>'
                    . '<b>query_contextuel_filter = </b>'. $query_contextuel_filter .'<br><br>'
                    ;
        }

        $filter_contextuel = array();
        $result_contextuel_filter = $this->db->execute($query_contextuel_filter);
        if ( $result_contextuel_filter ) {
            if ( $this->db->getNumRows() > 0 ) {
                while ( $row = $this->db->getQueryResults($result_contextuel_filter, 1) ) {
                    switch ( $row['snecf_type'] ) {
                        // modif 17/09/2008 BBX :Spécificité HPG, si on se trouve sur tacsv, on split tac / sv. BZ 7427
                        case 'tacsv' :
                            $_operateur = '=';
                            // 21/03/2014 GFS Bug 40080 - [REC][Core CS 5.3.1.01][TC #TA-62630][Link to NEx]: There is a warning message in the console panel when link to NEx from "IN" dashboard
							if(isset($row['snecf_use_code']) && $row['snecf_use_code']==1){
                            	// on utilise obligatoirement le code
                                $_array_value = explode('_',$this->na_value);
							}
                            else{
                            	$_array_value = explode('_',$this->getNaLabel($this->na, $this->na_value));
							}
                            if(isset($row['snecf_use_case']))
                            	$_array_value = StringModel::updateCase($row['snecf_use_case'],$_array_value);
                            
                            $_value = $row['snecf_before_value'].(($row['snec_name'] == 'sv') ? $_array_value[1] : $_array_value[0]).$row['snecf_after_value'];
                        break;

                        // modif SCT 14:39 18/12/2008 : prise en compte des combinaisons pour le parser CORE
                        case 'nod1nod2' :
                            $_operateur = '=';
                            // 21/03/2014 GFS Bug 40080 - [REC][Core CS 5.3.1.01][TC #TA-62630][Link to NEx]: There is a warning message in the console panel when link to NEx from "IN" dashboard
                            if(isset($row['snecf_use_code']) && $row['snecf_use_code']==1){
                            	// on utilise obligatoirement le code
                            	$_array_value = explode($separator,$this->na_value);
                            }
                            else
                            	$_array_value = explode($separator,$this->getNaLabel($this->na, $this->na_value));
                            if(isset($row['snecf_use_case']))
                            	$_array_value = StringModel::updateCase($row['snecf_use_case'],$_array_value);
                            
                            $_value = $row['snecf_before_value'].($row['snec_name'] == 'Node 1' ? $_array_value[0] : $_array_value[1]).$row['snecf_after_value'];
                        break;

                        // NSE 16/12/2011 : Gestion pour Core PS
                        // Pour sgsnggsn, on décompose "SGSNRNC-GGSN" en "Src Node" et "Dest Node"
                        case 'sgsnggsn' :
                        	// 21/03/2014 GFS Bug 40080 - [REC][Core CS 5.3.1.01][TC #TA-62630][Link to NEx]: There is a warning message in the console panel when link to NEx from "IN" dashboard
                        	$sgsnParent = "
	                        	SELECT eoar_id_parent
	                        	FROM edw_object_arc_ref
	                        	WHERE
	                        	eoar_arc_type = 'sgsnggsn|s|sgsn'
	                        	AND eoar_id = '{$this->na_value}'";
                        	$ggsnParent = "
	                        	SELECT eoar_id_parent
	                        	FROM edw_object_arc_ref
	                        	WHERE
	                        	eoar_arc_type = 'sgsnggsn|s|ggsn'
	                        	AND eoar_id = '{$this->na_value}'";
                        	
                        	// 10/07/2014 GFS - Bug 42639 - [SUP][T&A CorePS][#46742][MeditelMaroc] On SGSNRNC-GGSN NE level, Link To NEx is not generated
                        	if ( !($sgsnValue = $this->db->getOne($sgsnParent)) or !($ggsnValue = $this->db->getOne($ggsnParent))) {
                        		$this->error = __T('U_E_LINK_AA_SQL_INVALID_SERVER');
                        	}
                        	if ( $this->debug ) {
	                        	echo '<b>$sgsnValue = </b>'. $sgsnValue .'<br />';
	                        	echo '<b>$ggsnValue = </b>'. $ggsnValue .'<br />';
                        	}
                            $_operateur = '=';
                            // new parameters in AA links contextual filters
                            if(isset($row['snecf_use_code']) && $row['snecf_use_code']==1){
                            	// on utilise obligatoirement les codes
								$_array_value[] = $sgsnValue;
								$_array_value[] = $ggsnValue;
							}
                            else{
								// on recupere les alias des elements parents
								$_array_value[] = $this->getNaLabel('sgsn',$sgsnValue);
								$_array_value[] = $this->getNaLabel('ggsn',$ggsnValue);
							}
                            if(isset($row['saacf_use_case']))
                            	$_array_value = StringModel::updateCase($row['snecf_use_case'],$_array_value);
                                
                            $_value = $row['snecf_before_value'].($row['snec_name'] == 'Src Node' ? $_array_value[0] : $_array_value[1]).$row['snecf_after_value'];
                        break;

                        case $this->na : // correspond à la NA
                            $_operateur = '=';
                            // 21/03/2014 GFS Bug 40080 - [REC][Core CS 5.3.1.01][TC #TA-62630][Link to NEx]: There is a warning message in the console panel when link to NEx from "IN" dashboard
                            if(isset($row['snecf_use_code']) && $row['snecf_use_code']==1){
                            	// on utilise obligatoirement le code
                                $_value = $this->na_value;
							}
                            else
                            	$_value = $this->getNaLabel($this->na, $this->na_value);
                            if(isset($row['snecf_use_case']))
                            	list($_value) = StringModel::updateCase($row['snecf_use_case'],array($_value));
                            $_value = $row['snecf_before_value'].$_value.$row['snecf_after_value'];
                            break;

                        case $this->na_axe3 : // correspond à la NA 3rd axis
                            $_operateur = '=';
                            // 21/03/2014 GFS Bug 40080 - [REC][Core CS 5.3.1.01][TC #TA-62630][Link to NEx]: There is a warning message in the console panel when link to NEx from "IN" dashboard
                            if(isset($row['snecf_use_code']) && $row['snecf_use_code']==1){
                            	// on utilise obligatoirement le code
                                $_value = $this->na_axe3_value;
							}
                            else
                            	$_value = $this->getNaLabel($this->na_axe3, $this->na_axe3_value);
							if(isset($row['snecf_use_case']))
                            	list($_value) = StringModel::updateCase($row['snecf_use_case'],array($_value));
							
                            $_value = $row['snecf_before_value'].$_value.$row['snecf_after_value'];
                            break;

                    }

                    $filter_contextuel[] = array(
                            'column'   => $row['snec_name'], // nom colonne
                            'operator' => $_operateur, // opérateur
                            'value' => $_value, // opérateur
                    );
                }

                if ( count($this->filter) > 0 )
                        $this->filter = array_merge($filter_contextuel, $this->filter);
                else
                        $this->filter = $filter_contextuel;
            }
        }

        if($this->debug){
            echo '<b>Contextuel filter : </b><pre>'. print_r($filter_contextuel,true) .'</pre><br>';
        }
    }
    
    
    /**
     * Get label of network element
     */
    private function getNaLabel ($na, $na_value) {

        $query_na_label = "SELECT DISTINCT eor_label FROM edw_object_ref 
                                WHERE eor_obj_type = '". $na ."'
                                AND eor_id = '". $na_value ."'";

        $label = $this->db->getOne($query_na_label);
        $na_label = ( $label != "" and $label != null) ? $label : $na_value;

        return $na_label;
    }
    
    
    /**
     * Convert context value to Nova Explorer value
     * Bug 34460 - [INT][2.1.0.2] Links not valid from T&A product
     */
    private function convertContextValueToNeValue ($contextValue) {
        
        if($contextValue == "")
            return $contextValue;
        else
        {
            $neValue = "{";
            $elt = explode(",", $contextValue);

            for($i=0 ; $i<count($elt) ; $i++)
            {
                $tmp = null;
                if($i == 0 && substr($elt[$i],0,1) == '(' && $i == count($elt)-1 && substr($elt[$i], -1) == ')')
                    $tmp = substr($elt[$i],1,strlen($elt[$i])-2);
                else if($i == 0 && substr($elt[$i],0,1) == '(')
                    $tmp = substr($elt[$i],1);
                else if($i == count($elt)-1 && substr($elt[$i], -1) == ')')
                    $tmp = substr($elt[$i],0,strlen($elt[$i])-1);
                else
                    $tmp = $elt[$i];

                if($i > 0)
                    $neValue .= ",";

                $neValue = $neValue . "\"" . $tmp . "\"";
            }
            $neValue = $neValue . "}";
            return $neValue;
        }
    }
}
?>
