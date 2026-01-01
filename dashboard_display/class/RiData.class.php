<?php

/* 16/03/2009 - modif SPS : changement du lien pour l'export Excel
 * 13/01/2011 NSE bz 19924 : RI KO sur le 3° axe
 * 31/01/2011 NSE bz 20160 : No RI value for mapped Network Element
 * 24/10/2011 MMT Bz 24373 utilisation de CDATA dans le titre du graph pour prévenir les caractères spéciaux
 */

class RiData
{
	private $debug = false;
	
	private $masterTopo;
	
	// Variables TA
	
	private $ta;
	private $taValue;
	private $period;
	private $isTaBH;
	private $bh;
	private $bhLabel;
	private $bhData;
	
	// Variables NA

	private $naAxe1;
	private $neAxe1;
	private $naAxeN;
	private $neAxeN;
	private $naLabel;
        private $neAxe1AxeN;
	
	private $mode;
	
	private $gtmId;
	private $gtmTitle;
	private $xAxis;	
	private $riValues;
	
	private $dashName;
	
	const SEPARATOR = '|s|';
	
	public function __construct(){}
	
	/**
	 * Activation / Désactivation du débuggage
	 *
	 * @param $debug boolean activer / désactiver le débuggage
	 * @return void
	 */

	public function setDebug($debug)
	{
		$this->debug = $debug;
	}	
	
	/**
	 * Définition de l'application "maitre" de topologie
	 *
	 * @param int $master_topo_id identifiant de l'application "maitre" topologie
	 */

	public function setMasterTopo($master_topo_id = '')
	{
		if ($master_topo_id == '') 
		{
			// Appel à la fonction 'getMasterProduct()' de "php/edw_function.php" pour déterminer les infos du produit "master topology"

			$master_infos = getTopoMasterProduct();
			$master_topo_id = $master_infos['sdp_id'];
		}
		
		$this->masterTopo = $master_topo_id;
	}	
	
	/**
	 * Définition des valeurs de l'agrégation temporelle
	 *
	 * @param string $ta nom de la ta
	 * @param int $ta_value valeur de la ta
	 * @param int $period valeur de la période
	 */
	
	public function setTA($ta, $ta_value, $period)
	{		
		$this->ta = $ta;
		
		// On valide ici si la ta est une ta bh (busy hour) ou non
		
		$this->isTaBH = (!(strpos($ta, "bh") === false));
		
		$this->taValue = $ta_value;
		$this->period = $period;
	}
	
	/**
	 * Définition des bh
	 *
	 * @param array $bh liste des bh à inclure dans les requetes
	 */
	
	public function setBH($bh)
	{
		$this->bh = $bh;
	}

	/**
	 * Définition des labels des bh
	 *
	 * @param array $bh_label liste des labels des bh
	 */

	public function setBHLabel($bh_label)
	{
		$this->bhLabel = $bh_label;
	}

	/**
	 * Définition de la na d'axe 1 (na du sélecteur)
	 *
	 * @param string $na_axe1 nom de la na d'axe 1
	 */

	public function setNaAxe1($na_axe1)
	{
		$this->naAxe1 = $na_axe1;
	}	
	
	/**
	 * Définition des ne de la na d'axe 1 
	 *
	 * @param array $ne_axe1 liste des ne d'axe 1. Ce tableau est de la forme : $this->neAxe1['na'][0, 1, 2, 3, ...]. Exemple : $this->neAxe1['rnc'][501, 502, 505, ...]
	 */

	public function setNeAxe1($ne_axe1)
	{
		$this->neAxe1 = $ne_axe1;
	}	
	
	/**
	 * Définition des na d'axe n réparties par produit
	 *
	 * @param array $na_axe_n liste des na d'axe n. Ce tableau est de la forme : tab[id_produit][famille] = na axe n
	 */

	public function setNaAxeN($na_axe_n)
	{
		$this->naAxeN = $na_axe_n;
	}
	
	/**
	 * Définition des ne d'axe n
	 *
	 * @param array $ne_axe_n liste des ne d'axe n. Ce tableau est de la forme : tab[id_produit][famille][na axe n] = ne axe n
	 */

	public function setNeAxeN($ne_axe_n)
	{	
		$this->neAxeN = $ne_axe_n;
	}	
	
	/**
	 * Définition du label des na d'axe 1
	 *
	 * @param array $na_label liste des labels des na d'axe 1. Ce tableau est de la forme : ...
	 */

	public function setNALabel($na_label)
	{
		$this->naLabel = $na_label;
	}	
	
	/**
	 * Définition des ne de la na d'axe N
	 *
         * 13/01/2011 NSE bz 19924 : RI KO sur le 3° axe
	 * @param array $ne_axe1_axeN liste des ne d'axe 3. Ce tableau est de la forme : $this->neAxe1AxeN['na'][0, 1, 2, 3, ...]. Exemple : $this->neAxe1AxeN['rnc'][501|s|wap, 502|s|wap, 505|s|wap, ...]
	 */

	public function setNeAxe1AxeN($ne_axe1_axeN)
	{
		$this->neAxe1AxeN = $ne_axe1_axeN;
	}

	/**
	 * Définition du mode du dashboard
	 *
	 * @param string $mode nom du mode (overnetwork ou overtime)
	 */

	public function setMode($mode)
	{
		$this->mode = $mode;
	}
		
	/**
	 * Défintion de l'identifiant du GTM permettant de construire le graphe du RI
	 *
	 * @param string $gtm_id identifiant du GTM 
	 */
	
	public function setGTMId($gtm_id)
	{
		$this->gtmId = $gtm_id;
	}
	
	/**
	 * Permet de définir le nom d'une table de données à partir d'un group_table, d'un type de données, d'un identifiant produit
	 * et de la présence / absence d'un axe N
	 *
	 * @param string $group_table nom du groupe
	 * @param string $data_type nom du type (raw / kpi)
	 * @param int $product identifiant du produit
	 * @param boolean $axeN présence / abscence de l'axe n
	 * @return string nom de la table de données
	 */

	private function setDataTableName($group_table, $data_type, $product, $family, $axeN = false)
	{
		$na_for_table = $this->naAxe1;

		if (($axeN == true) && (count($this->naAxeN[$product][$family]) > 0))
		{
			$na_for_table .= "_".(implode("_", $this->naAxeN[$product][$family]));
		}

		return $group_table."_".$data_type."_".$na_for_table."_".$this->ta;		
	}
	
	/**
	 * Définition de la condition SQL sur la TA
	 *
	 * @return string condition SQL sur la TA
	 */

	private function setTACondition()
	{
		$ta_condition = "";
		
		if ($this->mode == "overtime"/* && (strpos($this->gtmTypes[$gtm_id], "pie") === false)*/) {
			$ta_condition = $this->ta." >= ".getTAMinusPeriod($this->ta, $this->taValue, $this->period)." AND ".$this->ta." <= ".$this->taValue;
		}
		else 
		{
			$ta_condition = $this->ta." = ".$this->taValue;
		}

		return $ta_condition;
	}	

	/**
	 * Définition de la requete à effectuer sur la table de référence pour chaque produit
	 *
	 * @param int $product identifiant du produit
	 * @param boolean $axeN présence (true) / abscence (false) de l'axe n (optionnel)
	 * @return string la requete à executer sur la table de référence
	 */

	private function setProductRefTable($product, $axeN = false)
	{		
		// Si un axe N est présent sur tous les produits, on définit une sous-requete listant les ne d'axe 1 et les ne d'axe N

		// La condition n'est valable que si tous les produits définissent un axe N et qu'il n'existe pas de valeur particulière d'axe N définie (= "ALL")
		
		//if (($axeN) && (count($this->naAxeN[$product]) > 0) && (count($this->neAxeN[$product]) == 0)) {

		$na_axeN = $this->checkNeAxeNALL($product);

		if (($axeN) && ($na_axeN != false)) {

			// Définition des na d'axe N
			
			//$na_axeN = $this->naAxeN[$product];

			// On récupère les valeurs de ne définies précedemment via la méthode 'getNE()'. Les ne doivent être de la forme "ne_axe1|s|ne_axeN" (pour l'instant, N vaut toujours 3 => seulement 2 axes : axe 1 et 3)

			//$ne_list = $this->neAxe1[$this->naAbcisseAxe1];
			// 31/01/2011 NSE bz 20160 : on est en 3° axe, on gère le mapping en utilisant la bonne convertion
                        //$ne_list = $this->ConvertEquivalentNeAxe1($this->neAxe1, $this->naAxe1, $product);
                        // 13/01/2011 NSE bz 19924 : RI KO sur le 3° axe
                        // en configuration 3° axe, on utilise les éléments avec leur info de 3° axe au lieu des éléments 1° axe uniquement.
                        $ne_list = $this->ConvertEquivalentNeAxe3($this->neAxe1AxeN, $this->naAxe1, $product);
			// On définit un tableau de ne où la na est la clé et la ne correspondante la valeur

			$ne = array();

			for ($j=0; $j < count($ne_list); $j++) {
				
				$ne_tab = explode(self::SEPARATOR, $ne_list[$j]);

				// La valeur de la na d'axe 1 est toujours le 1er élément du tableau

				if (@!in_array($ne_tab[0], $ne[$this->naAxe1])) {
					$ne[$this->naAxe1][] = $ne_tab[0];
				}

				// On boucle sur toutes les na d'axe N défini en allant chercher les ne correspondante à leur index+1 dans le tableau de ne

				//$na_axeN = $this->naAxeN[$product];

				for ($k=0; $k < count($na_axeN); $k++) {
					if (@!in_array($ne_tab[$k+1], $ne[$na_axeN[$k]]))
					{
						$ne[$na_axeN[$k]][] = $ne_tab[$k+1];
					}
				}
			}

			// Définiton de la table de référence ou de la sous-requete suivant les types de na présentes

			$index = 0;

			$columns	= array();
			$ref_tables	= array();
			$elements_list	= array();

			foreach ($ne as $key=>$value) {
				
				$index_sub = "A".$index;

				$columns[]      = $index_sub.".eor_id";
				$ref_tables[]	= "edw_object_ref AS ".$index_sub;

				$ne_list_str = implode(",", array_map(array($this, 'labelizeValue'), $value));

				// 23/02/2009 - Modif. benoit : mise en commentaires spécifique au RI -> on ne fait pas de condition sur les valeurs des différentes na
				
				/*if ($ne_list_str != "''") {
					$elements_list[] = $index_sub.".eor_id IN (".$ne_list_str.") AND ".$index_sub.".eor_obj_type = '".$key."'";
				}
				else 
				{*/
					$elements_list[] = $index_sub.".eor_obj_type = '".$key."'";
				//}			

				$index += 1;
			}
			
			// 23/02/2009 - Modif. benoit : ajout spécifique au RI -> la condition sur les valeurs de na se fait sur les jointures de tables de référence
			
			$elements_list[] = implode("||'".self::SEPARATOR."'||", $columns)." IN (".implode(",", array_map(array($this, 'labelizeValue'), $ne_list)).")";

			$sql .=	 " (SELECT DISTINCT ".implode("||'".self::SEPARATOR."'||", $columns)."  AS eor_id FROM ".implode(", ", $ref_tables)
					." WHERE ".implode(" AND ", $elements_list).") A";

		}
		else // Sinon, on inclut seulement la table de référence des éléments d'axe 1
		{
			$sql .= " edw_object_ref A";
		}
		
		return $sql;
	}	
	
	/**
	 * Définition de la requete propre à chaque famille des éléments du GTM
	 *
	 * @param array $elements éléments du GTM propre à la famille
	 * @param string $type type des éléments(raw / kpi)
	 * @param string $family nom de la famille
	 * @param int $product identifiant du produit
	 * @param int $gtm_id identifiant du GTM
	 * @param int $offset valeur de l'offset (cas du zoom plus)
	 * @return string requete propre à la famille
	 */
	
	/**
	 * Enter description here...
	 *
	 * @param int $product
	 * @param string $family
	 * @param unknown_type $group_table
	 * @param unknown_type $axeN
	 * @return string
	 */

	private function setRiFamilyQuery($product, $family, $group_table, $axeN = false)
	{		
		// Définition de la liste d'éléments

		$elts_list = "ri_capture_duration AS ".$family."_ri_capture_duration";

		// En mode "overtime", on inclut la ta dans la liste des éléments sélectionnés

		if ($this->mode == "overtime") $elts_list .= ", ".$this->ta;

		// Ajout de la / des colonne(s) bh

		if (count($this->bh) > 0) 
		{
			$elts_list .= ", ".implode(", ", $this->bh);
		}

		// Définition du nom de la table de données
		
		$data_table = $this->setDataTableName($group_table, 'kpi', $product, $family, $axeN);

		// Définition des champs d'axe N à récupérer via la requete
		
		$axe_n_columns = $this->setAxeNColumns($product, $family, $axeN, false);
				
		// Définition de la condition sur les ta

		$ta_cond = $this->setTACondition();

		// Définition de la liste des ne

		// 30/11/2010 BBX
                // Traitement du mapping
                // BZ 17929
                //$ne = $this->neAxe1;
                $ne = $this->ConvertEquivalentNeAxe1($this->neAxe1, $this->naAxe1, $product);

		$ne_list = implode(",", array_map(array($this, 'labelizeValue'), $ne));

		// Définition de la condition sur les Nèmes axes

		$axeN_cond = $this->setAxeNCondition($product, $family, $axeN);

		// Définition de la requete

		$sql = "SELECT ".$this->naAxe1.", ".$axe_n_columns." ".$elts_list." FROM ".$data_table." WHERE ".$ta_cond;	

		// Cas de la sélection de plusieurs éléments d'axe N

		if ($axeN && ($this->neAxeN == '')) {
			
			// Plusieurs éléments d'axe N différents existent. On base la condition sur les valeurs

			// Note : factoriser cette condition

			$ne_cond = array();

			for ($i=0; $i < count($ne); $i++) {
				
				$ne_all_axe = explode(self::SEPARATOR, $ne[$i]);

				$cond = "(".$this->naAxe1." = '".$ne_all_axe[0]."'";

				$na_axeN = $this->naAxeN[$product][$family];

				for ($j=0; $j < count($na_axeN); $j++) {
					if ($ne_all_axe[$j+1] != "") $cond .= " AND ".$na_axeN[$j]." = '".$ne_all_axe[$j+1]."'";
				}

				$cond .= ")";

				$ne_cond[] = $cond;
			}

			$sql .= " AND (".implode(' OR ', $ne_cond).")";
		}

		// Sélection uniquement d'éléments d'axe 1 ou d'un seul élément d'axe N

		else
		{
			$sql .= " AND ".$this->naAxe1." IN (".$ne_list.")".$axeN_cond;	
		}

		return $sql;		
	}	
	
	/**
	 * Définition des colonnes d'axe n à inclure dans les requetes SQL
	 *
	 * @param int $product identifiant du produit
	 * @param boolean $axeN présence / abscence de l'axe n
	 * @return string liste des colonnes d'axe n à inclure dans les requetes
	 */

	private function setAxeNColumns($product, $family, $axeN = false, $displayAxeNLabel = true)
	{
		$axe_n_columns = "";
		
		if (($axeN == true) && (count($this->naAxeN[$product][$family]) > 0))
		{
			$colums_n = array();

			for ($i=0; $i < count($this->naAxeN[$product][$family]); $i++)
			{
				$column = $this->naAxeN[$product][$family][$i];
				
				if ($displayAxeNLabel)
				{
					// 04/02/2009 - Modif. benoit : correction de la requete ci-dessous en traitant le cas où le 'eor_id_codeq' est nul (sinon, jamais de labels pour les ne 3ème axe)
					
					/*$column .= ", (SELECT eor_label||'".self::SEPARATOR."'||eor_id_codeq FROM edw_object_ref WHERE eor_obj_type = '".$this->naAxeN[$product][$family][$i]."' AND eor_id = ".$this->naAxeN[$product][$family][$i].") AS ".$this->naAxeN[$product][$family][$i]."_codeq_label";*/

					$column .= ", (SELECT (CASE WHEN eor_id_codeq IS NULL THEN '' ELSE eor_id_codeq END)||'".self::SEPARATOR."'||eor_label FROM edw_object_ref WHERE eor_obj_type = '".$this->naAxeN[$product][$family][$i]."' AND eor_id = ".$this->naAxeN[$product][$family][$i].") AS ".$this->naAxeN[$product][$family][$i]."_codeq_label";
				}

				$colums_n[] = $column;
			}
			
			$axe_n_columns = implode(", ", $colums_n).", ";
		}
		
		return $axe_n_columns;
	}	
	
	/**
	 * Définition de la condition SQL sur le / les axe(s) n
	 *
	 * @param int $product identifiant du produit
	 * @param string $family nom de la famille
	 * @param boolean $axeN présence / abscence de l'axe n
	 * @return string condition SQL
	 */

	private function setAxeNCondition($product, $family, $axeN = false)
	{
		$axeN_cond = "";

		if (($axeN == true) && (count($this->naAxeN[$product][$family]) > 0))
		{	
			$na_axeN = $this->naAxeN[$product][$family];

			// On boucle sur l'ensemble des na des axes N

			for ($i=0; $i < count($na_axeN); $i++) {

				// On établit une condition sur les valeurs de l'axe uniquement si une ou plusieurs ne sont sélectionnées sinon, le comportement défaut est de sélectionner toutes les valeurs. Cette condition est donc restrictive

				if (count($this->neAxeN[$product][$family][$na_axeN[$i]]) > 0) {
					
					// Pour l'instant, tel qu'est conçu le sélecteur on ne peut sélectionner qu'une seule valeur des na d'axe N. La condition porte donc sur l'égalité et sur le premier et unique élément du tableau. Lorsque la sélection des éléments d'axe N aura évolué il faudra remplacer cette condition par un "IN" et une énumération des valeurs du tableau d'éléments.
					
					$axeN_cond .= " AND ".$na_axeN[$i]." = '".$this->neAxeN[$product][$family][$na_axeN[$i]][0]."'";
				}
			}
		}
		return $axeN_cond;
	}
	
	/**
	 * 
	 * 
	 *
	 */
	
	/**
	 * Permet de définir le nom du dashboard appelant le RI
	 *
	 * @param string $dash_name nom du dashboard appelant
	 */
	
	public function setDashName($dash_name)
	{			
		$this->dashName = $dash_name;
	}	
	
	/**
	 * Permet de lire le xml du GTM appelant et d'en extraire les informations utiles au GTM du RI
	 *
	 * @param string $xml_src url du XML du GTM appelant
	 */
	
	public function readXmlGTMSrc($xml_src = '')
	{
		$xml = new DomDocument();
				
		if (@$xml->load($xml_src)) {
			
			// Tabtitle
			
			$this->gtmTitle = "";
			
			$tabtitle_xml = $xml->getElementsByTagName('tabtitle')->item(0);
			
			foreach ($tabtitle_xml->getElementsByTagName('text') as $text) 
			{
				$text_attr = (($text->hasAttribute('color')) ? 'color = "'.$text->getAttribute('color').'"' : "");
				//24/10/2011 MMT Bz 24373 utilisation de CDATA dans le titre pour prévenir les caractères spéciaux
				$tabtitle .= "<text ".$text_attr."><![CDATA[".$text->firstChild->nodeValue."]]></text>";
			}
			
			$this->gtmTitle = $tabtitle;
			
			// Xaxis
			
			$xaxis = array();
			
			$xaxis_labels = $xml->getElementsByTagName('xaxis_labels')->item(0);
			
			$xaxis['title'] = $xaxis_labels->getAttribute('title');

			foreach ($xaxis_labels->getElementsByTagName('label') as $labels) 
			{
				$xaxis['values'][] = $labels->firstChild->nodeValue;
			}
			
			$this->xAxis = $xaxis;
		}
	}
	
	/**
	 * Permet de définir si la valeur ALL est défini pour les éléments d'axeN
	 *
	 * @param int $product identifiant du produit
	 *
	 * @return mixed false si une valeur est spécifiée, le nom de la na sinon
	 */

	private function checkNeAxeNALL($product)
	{
		$na_axeN_all = false;

		if (isset($this->naAxeN[$product])) 
		{
			foreach ($this->naAxeN[$product] as $family => $na_axeN)
			{
				// Dans le cas où une valeur d'une des na d'axe N est non précisé, cela signifie que l'on se trouve dans le cas du "ALL"
				
				if (count($this->neAxeN[$product][$family]) == 0)
				{					
					// Note : on suppose ici qu'il y a un seul axe N (=3). Modifier ce cas pour inclure plusieurs axes

					// 06/01/2009 - Modif. benoit : on défini la valeur de l'axe N dans un tableau et non en tant que chaine

					$na_axeN_all = array($na_axeN[0]);
				}
			}
		}
		
		return $na_axeN_all;
	}
	
	/**
	 * Permet, dans le cadre du mapping de la topologie, de gérer les convertions entre les valeurs de ne préselectionnées et leurs valeurs effectives dans les tables de données du produit concerné
	 *
	 * @param array $ne liste de ne à éventuellement convertir
	 * @param string $na nom de la na
	 * @param integer $product identifiant du produit
	 *
	 * @return array liste des ne mise à jour
	 */

	private function ConvertEquivalentNeAxe1($ne, $na, $product)
	{
		$ne_to_convert = array();

		if (isset($this->equivalentNeAxe1[$product][$na])) {
			$ne_to_convert = $this->equivalentNeAxe1[$product][$na];
		}

		if (count($ne_to_convert) > 0) {
			foreach ($ne_to_convert as $ne_id => $ne_codeq) {
				if (in_array($ne_id, $ne)) {
					$ne[array_search($ne_id, $ne)] = $ne_codeq;
				}
			}
		}

		return $ne;
	}	
	
        /*
         * 31/01/2011 NSE bz 20160 : correction de la fonction qui faisait n'importe quoi
         */
	private function ConvertEquivalentNeAxe3($ne3, $na1, $product)
	{
            $ne_to_convert = array();

            if (isset($this->equivalentNeAxe1[$product][$na1])) {
                $ne_to_convert = $this->equivalentNeAxe1[$product][$na1];

                // si on a des éléments à convertir
                // ex. : 4008_16031 => 102_20762
                if (count($ne_to_convert) > 0) {
                    // pour tous les éléments 3° axe, on va regarder si la composante 1° axe fait partie de la liste des éléments à convertir
                    // ex. : Array ( [0] => 4008_16031|s|65501 [1] => 102_20762|s|64710 [2] => 101_20392|s|65501 )
                    foreach($ne3 as $lene){
                        $lene = explode(get_sys_global_parameters("sep_axe3"), $lene);
                        $ne_sans_axe3[] = $lene[0];//echo '<br>ne a chercher : '.$lene[0];
                    }

                    foreach ($ne_to_convert as $ne_id => $ne_codeq) {
                        if (in_array($ne_id, $ne_sans_axe3)) {
                            // si c'est le cas, on transforme
                            $indice = array_search($ne_id, $ne_sans_axe3);
                            $ne3[$indice] = $ne_codeq.get_sys_global_parameters("sep_axe3").array_pop(explode(get_sys_global_parameters("sep_axe3"), $ne3[$indice]));
                        }
                    }
                }
            }

            // ex. : Array ( [0] => 107_22472|s|65501 [1] => 102_20762|s|64710 [2] => 101_20392|s|65501 )
            return $ne3;
	}

	/**
	 * Permet, dans le cadre du mapping de la topologie, de gérer les convertions entre les valeurs de ne préselectionnées et leurs valeurs effectives
	 * dans les tables de données du produit concerné
	 *
	 * @param array $ne liste de ne à éventuellement convertir
	 * @param string $na nom de la na
	 * @param integer $product identifiant du produit
	 * @return array liste des ne mise à jour
	 */
	private function ReconvertEquivalentNeAxe1($ne, $na, $product)
	{
		if (isset($this->equivalentNeAxe1[$product][$na]))
		{
			$ne_to_convert = array();
			$ne_to_convert = $this->equivalentNeAxe1[$product][$na];
			if (count($ne_to_convert) > 0)
			{
				// 15:46 26/10/2009 GHX
				// Convertion de tous les éléments du tableau en string
				foreach ( $ne as $k => $v )
				{
					$ne[$k] = (string) $v;
				}
				foreach (array_flip($ne_to_convert) as $ne_codeq => $ne_id)
				{
					// On force la valeur de recherche en string
					if (in_array((string)$ne_codeq, $ne))
					{
						$ne[array_search($ne_codeq, $ne)] = $ne_id;
					}
				}
			}
		}
		return $ne;
	}

        /**
	 * Permet de définir le tableau d'equivalence entre les ne préselectionnées de différents produits.
	 * Ainsi, pour un produit donné (ex. 3) où une ne vaut X1 et sa référence S1 on notera : $this->equivalentNeAxe1[3]['X1'] = 'S1'
	 *
	 * @param array $equivalent_ne_axe1 tableau d'équivalence
	 */

	public function setEquivalentNeAxe1($equivalent_ne_axe1)
	{
		$this->equivalentNeAxe1 = $equivalent_ne_axe1;
        }

	public function getValues()
	{
		$this->riValues = array();
		
		// On récupère les produits et les familles des elements du GTM servant à définir le graphe du RI
		
		$gtm_model = new GTMModel($this->gtmId);
		
		$products_families = $gtm_model->getGTMProductsAndFamilies();
		
		// Définition des requetes permettant de récupérer les valeurs du RI
				
		foreach ($products_families as $product => $families)
		{			
			$queries = array();
			$axeN_queries = array();
			$ri_columns = array();
			
			// Définition des requetes pour les familles appartenant à chaque produit
			
			for ($i=0;$i < count($families); $i++) {
				
				$family		= $families[$i]['family'];
				$group_table	= $families[$i]['edw_group_table'];
				
				$queries[] = $this->setRiFamilyQuery($product, $family, $group_table, GetAxe3($family, $product));
				
				$ri_columns[] = $family."_ri_capture_duration";
				// 13/01/2011 NSE
                                // utilisation d'une mauvaise variable ($gtm_products[$i])
				$axeN_queries[] = GetAxe3($family, $product);
			}
			
			// ** Définition de la requete produit à partir des requetes et des données établies précedemment
			
			// 4.1 - Définition des sous-requetes
	
			$idx_tables		= array();
			$sub_queries	= array();
			$idx_bh			= array(); 
			
			for ($j=0; $j < count($queries); $j++) 
			{
				$idx = "B".$j;
	
				$query = " LEFT OUTER JOIN (".$queries[$j].") ".$idx." ON A.eor_id = ".$idx.".".$this->naAxe1;
	
				// Si un axe N sans valeur particulière est présent, on précise les noms des axe N dans la liason
				
				$na_axeN = $this->checkNeAxeNALL($product);
	
				if (($na_axeN != false) && ($axeN_queries[$j] == true)) 
				{
					$all_na_axeN = array();
	
					for ($k=0; $k < count($na_axeN); $k++) {
						$all_na_axeN[] = $idx.".".$na_axeN[$k];
					}
					
					$query .= "||'".self::SEPARATOR."'||".implode("||'".self::SEPARATOR."'||", $all_na_axeN);
				}
	
				$sub_queries[]	= $query;
				$idx_tables[]	= $idx.".".$this->ta;
	
				// Ajout des index bh
	
				if (count($this->bh) > 0) {
					for ($k=0; $k < count($this->bh); $k++) {
						$idx_bh[] = $idx.".".$this->bh[$k]." AS ".$idx."_".$this->bh[$k];
					}
				}
			}
			
			// 4.2 - Définition des champs à sélectionner
			
			$sql = " SELECT DISTINCT eor_id , ".implode(", ", $ri_columns)."".(($this->mode == "overtime") ? ", ".implode(", ", $idx_tables) : "")."".((count($this->bh) > 0) ? ", ".implode(", ", $idx_bh) : "");
		
			// 4.3 - Définition du nom de la table de référence liant les éléments			
	
			$sql .= " FROM ".$this->setProductRefTable($product, (in_array(true, $axeN_queries)));
	
			// 4.4 - Inclusion des sous-requetes précedemment définies
	
			$sql .= implode(" ", $sub_queries);
	
			// 4.5 - Définition des conditions appliquées à la table de référence (conditions sur les valeurs de ne). Cette condition s'applique seulement lorsque le produit ne contient que des éléments d'axe1 et un seul élément d'axe3 (optionnel)
			
			if ($this->checkNeAxeNALL($product) === false)
			{
				/*$ne = (!is_int($offset)) ? $this->neAxe1[$this->naAbcisseAxe1] : (array_slice($this->neAxe1[$this->naAbcisseAxe1], $offset, $this->getTop(), true));
				$ne_axe1 = $this->ConvertEquivalentNeAxe1($ne, $this->naAbcisseAxe1, $gtm_products[$i]);
				$ne_list = implode(",", array_map(array($this, 'labelizeValue'), $ne_axe1));*/
	
				$ne_axe1 = $this->ConvertEquivalentNeAxe1($this->neAxe1, $this->naAxe1, $product);
								
				$ne_list = implode(",", array_map(array($this, 'labelizeValue'), $ne_axe1));
	
				$sql .= " WHERE eor_id IN (".$ne_list.") AND eor_obj_type = '".$this->naAxe1."'";
			}
							
			// 4.6 - Execution de la requete produit et stockage des résultats

                        // 30/11/2010 BBX
                        // Traitement du mapping
                        // BZ 17929
                        $mappingEquivalents = $this->equivalentNeAxe1[$product][$this->naAxe1];

                        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
			$db = Database::getConnection($product);
			$db->setDebug($this->debug);
						
			$row = $db->execute($sql);
			
			while($elem = $db->getQueryResults($row,1)) {
				
				$ta_value = (($this->mode == "overnetwork") ? $this->taValue : $elem[$this->ta]);
				
				if ($ta_value != "")
				{
					for ($i=0;$i < count($families); $i++) {
						
						$family	= $families[$i]['family'];
						
						// Stockage dans le tableau des éléments du GTM des valeurs du RI
						
						$value = (($elem[$family.'_ri_capture_duration'] != "") ? $elem[$family.'_ri_capture_duration'] : 0);

                                                // 13/01/2011 NSE bz 19924 RI KO en ONE
                                                // si l'élément contient une information sur le 3° axe, on récupère uniquement la partie 1° axe
                                                if(substr_count($elem['eor_id'], get_sys_global_parameters("sep_axe3")) > 0)
                                                    $currentElement = array_shift(explode(get_sys_global_parameters("sep_axe3"),$elem['eor_id']));
                                                else
                                                    $currentElement = $elem['eor_id'];
	
                                                // 30/11/2010 BBX
                                                // Traitement du mapping
                                                // BZ 17929
                                                // 13/01/2011 NSE bz 19924 : RI KO en ONE
                                                // on remplace $currentElement au lieu de $elem['eor_id'] pour utiliser le NE sans sa composante 3° axe
                                                if(is_array($mappingEquivalents) && in_array($currentElement,$mappingEquivalents)){
                                                    $currentElement = array_search($currentElement,$mappingEquivalents);}
						$this->riValues[$product][$family][$currentElement][$ta_value] = $value;

						// Si une / plusieurs bh sont définis, stockage de leurs valeurs
	
						/*if (count($this->bh) > 0) 
						{							
							for ($k=0; $k < count($idx_bh); $k++) 
							{
								$bh_alias	= explode(" AS ", $idx_bh[$k]);
								$bh_row		= explode(".", $bh_alias[0]);
	
								if ($elem[strtolower($bh_alias[1])] != "") 
								{
									$this->bhData[$elem['eor_id']][$ta_value]['kpi'][$kpi_key_pdt][$kpi_key_id][$this->bhLabel[$bh_row[1]]] = getTaValueToDisplay("hour", $elem[strtolower($bh_alias[1])], "/");
								}	
							}
						}*/		
					}
				}
			}		
		}
	}
	
	public function DisplayResults()
	{				
		//print_r($this->riValues);
		
		$results = array();
		
		// Définition d'une variable locale indiquant si des résultats existent ou non pour le GTM
		
		$values_exist = true;
		
		// Traitement des GTMs vides (pas de valeurs définies)
				
		if (count($this->riValues) == 0 || $this->riValues == "") 
		{
			$values_exist = false;
			
			// Définition du nom du GTM (sert pour la définition du XML)
			
			$gtm_name = md5(uniqid(rand(), true));
			
			$results['name'] = $gtm_name;
			
			// Ajout du titre au tableau de résultats du GTM vide
										
			$results['title'] = $this->gtmTitle;
			
			// Définition de la barre de propriétés du GTM
							
			$results['properties']['title']		= array('dash' => $this->dashName, 'gtm' => "Reliability Indicator");
			
			/* 16/03/2009 - modif SPS : changement du lien pour l'export Excel */
			$results['properties']['buttons']	= array('excel' => array(	'link' => NIVEAU_0."dashboard_display/export/export_excel_from_graph.php?id_graph=".$gtm_name, 'img' => NIVEAU_0."/images/graph/btn_excel.gif", 'msg' => __T('U_TOOLTIP_CADDY_EXCEL_EXPORT')));
			
			// Ajout de la propriété "msg" indiquant le message à afficher dans le GTM
			
			$gtm_title = str_replace('color = "'.get_sys_global_parameters('tabgraph_color', "deeppink").'"', '', $this->gtmTitle);
											
			$results['msg'] = __T('U_RI_NO_DATA_FOUND');
			
			// Les autres infos du GTM sont définies comme vides
			
			$results['xaxis']	= array();
			$results['data']	= array();
			$results['link']	= array();
		}
						
		// Traitement du GTM avec des résultats
				
		if ($values_exist == true) 
		{
			// Définition du nom du GTM (sert pour définir le xml et le fichier image)
			
			$gtm_name = md5(uniqid(rand(), true));
			
			$results['name'] = $gtm_name;
			
			// Définition du titre du GTM RI
						
			$results['title'] = $this->gtmTitle;
			
			// Définition des propriétés du GTM RI
			
			$results['properties']['title']		= array('dash' => $this->dashName, 'gtm' => "Reliability Indicator");
		
			/* 16/03/2009 - modif SPS : changement du lien pour l'export Excel */
			$results['properties']['buttons']	= array('excel' => array(	'link' => NIVEAU_0."dashboard_display/export/export_excel_from_graph.php?id_graph=".$gtm_name, 'img' => NIVEAU_0."/images/graph/btn_excel.gif", 'msg' => __T('U_TOOLTIP_CADDY_EXCEL_EXPORT')));
		
			// Définition des informations xaxis
			
			$results['xaxis'] = $this->xAxis;
			
			// Définition des données à afficher dans le GTM RI
			
			$data = array();
					
			// On parcourt les produits des valeurs du RI
			
			foreach ($this->riValues as $product => $ri_values)
			{
				// On parcourt les familles des produits				
			
				foreach ($ri_values as $family => $ri_val_family)
				{
					if ($this->mode == "overnetwork")
					{
						for ($i=0;$i < count($this->neAxe1);$i++){
							
							$data[$i]['kpi'][$product][$family] = $ri_val_family[$this->neAxe1[$i]][$this->taValue]; 						
						}
					}
					else // mode overtime 
					{						
						$ta_values = getTAInterval($this->ta, $this->taValue, $this->period);
						
						for ($i=0;$i < count($ta_values);$i++){
							
							$data[$i]['kpi'][$product][$family] = $ri_val_family[$this->neAxe1[0]][$ta_values[$i]];	
						}
					}
				}		
			}
			
			$results['data'] = $data;
		}
		
		return $results;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return unknown
	 */
	
	public function getGTMProperties()
	{
		$gtm_properties = array();
		
		// Définition des propriétés "générales" du GTM RI
		
	    $gtm_properties['page_name']			= "Reliability Indicator";
	    $gtm_properties['graph_width']			= 900;
	    $gtm_properties['graph_height']			= 450;
	    $gtm_properties['position_legende']		= "top";
	    $gtm_properties['ordonnee_left_name']	= "%";
	    $gtm_properties['ordonnee_right_name']	= "";
	    $gtm_properties['object_type']			= "graph";
	    $gtm_properties['scale']				= "textlin";
	    $gtm_properties['definition']			= "";
	    $gtm_properties['troubleshooting']		= "";
    
    	// Définition des propriétés des données. Les valeurs représentées dans le GTM sont celles correspondant aux produits / familles
    	
    	$ri_elts_pptes = array();
    	
		// Parcours des produits des valeurs du RI
    	
    	foreach ($this->riValues as $product => $ri_values)
		{
			// Définition du label du produit
			
			$product_infos = getProductInformations($product);
			$product_label = $product_infos[$product]['sdp_label'];		
			
			// Parcours des familles
			
			// On récupère les labels des familles du produit
			
			$families_label = getFamilyList($product);
		
			foreach ($ri_values as $family => $ri_val_family)
			{
				$ri_elts_pptes[$product_label." ".$families_label[$family]] = array('class_object'		=> "kpi",
																					'display_type'		=> "line",
																					'line_design'		=> "square",
																					'color'				=> "#".dechex(rand(0,10000000))."@0",
																					'filled_color'		=> "#FFFFFF@1",
																					'position_ordonnee'	=> "left",
																					'id_product'		=> $product,
																					'id_elem'			=> $family);				
			}
		}	
		
		$gtm_properties['data'] = $ri_elts_pptes;
		
		return $gtm_properties;
	}
		
	/**
	 * Retourne la liste des na d'axe n d'un GTM donné
	 * 
	 * @param int $id_gtm identifiant du GTM
	 * @return array liste des na d'axe n du GTM
	 */

	private function getNaAxeN($id_gtm)
	{			
		$na_axeN = array();

		if (count($this->naAxeN) > 0) 
		{
			$gtm_model = new GTMModel($id_gtm);
			$gtm_products = $gtm_model->getGTMProducts();

			for ($i=0; $i < count($gtm_products); $i++)
			{
				$axeN_families = $this->naAxeN[$gtm_products[$i]];
				
				if (count($axeN_families) > 0) {
					
					foreach ($axeN_families as $family => $na) {			
						
						// Note : on suppose qu'il n'y a qu'un axe N par famille (à modifier)
						
						if (!in_array($na[0], $na_axeN)) $na_axeN[] = $na[0];
					}					
				}		
			}
		}		
		return $na_axeN;
	}	
	
	/**
	 * Permet de "labeliser" une valeur cad de l'entourer de quotes
	 *
	 * @param numeric $value la valeur à labeliser
	 * @return string la valeur labelisée
	 */

	private function labelizeValue($value)
	{
		return "'".$value."'";
	}
}