<?
/**
 * 
 *  CB 5.3.1
 * 
 * 22/05/2013 : T&A Optimizations
 */
/*
 *      @cb5.1.1.02
 *
 *      06/01/2010 : Correction du bz19972 - le calcul du RI peut se baser sur le 3ème axe
 * 10/10/2011 NSE DE Bypass temporel
 * 12/10/2011 MMT DE BYpass temporel, ajout du parametre Bypass dans getTimeWhere
 * 21/10/2011 MMT Bz 24263 pas de recalcul des niveau day si bypass day sans de fichier day intégré
 * 27/10/2011 MMT Bz 24440 les TA day,week et month parfois désactivés lors du compute booster
 * 12/06/2012 NSE bz 27382 : les fichiers day sont intégrés dans les tables hour. Il faut indiquer l'heure (23).
 */
?>
<?
/*
 *	@cb51007@
 *
 * 11:45 15/10/2010 SCT : BZ 18427 => Désactivation de compteur utilisé pour la BH possible
 *  + modification du code pour la gestion du retour de la méthode "getBHFormula" sous forme de table (gestion des messages d'erreur)
 *  + gestion des messages d'erreur retournés par la méthode "getBHFormula"
 */
?>
<?
/*
*	@cb41000@
*
*	19-11-2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	- 19/11/2008 BBX : utilisation de l'objet databaseConnection au lieu de la variable globale database_connection
*		=> modification de la méthode sql
*		=> modification de toutes les instructions utilisant $this->sql
*
*	- 30/07/2009 BBX : modification de la fonction detectChangePeriod. BZ 9244
*		=> modification des conditions de calcul de changement de période
*	- 08/03/2010 MPR : Correction du BZ 14165 - Message d'erreur pas assez explicite
*
*/
?>
<?
/*
*	@cb40000@
*
*	28-01-2008 - Copyright Astellia
*
*	Composant de base version cb_4.0.0.00
*
*  	 - 28-01-2008 SCT : création  du script pour passage en Compute Booster
*
*	- 16/07/2008 BBX : ajout de la méthode updateComputeOffset pour prévenir les changement de jour pendant un compute. BZ 7078
*	- 22/07/2008 BBX : mise à jour de l'offset en fonction du mode de compute. BZ 7157
*/
?>
<?php
/**
 * @package compute
 *
 * 28-01-2008 SCT : Modification du script pour passage en mode Compute Booster
 */
class compute
{
	/**
	 * Active ou non le mode débug
	 *
	 * cf. get_sys_debug('compute')
	 * 	0 : désactivé
	 *	1 : activé
	 *	2 : activé mode verbose
	 *	3: activé mais les requêtes ne sont pas exécutées,seulement affichées.
	 *
	 * @since cb4.0.0.00
	 * @var int
	 */
	protected $debug;

	/**
	 * identifiant de la connexion pgsql
	 *
	 * @since cb4.0.0.00
	 * @modif cb4.1.0.00 : inutile désormais
	 * @var ressource
	 */
	//protected $database_connection;

	/**
	 * catégorie de compute à exécuter ("raw" ou "kpi")
	 *
	 * 	array['categorie'] = raw ou kpi
	 * 	array['mode'] = mode du compute hourly ou daily
	 * 	array['type'] = mode du compute hour ou day
	 * 	array['offset'] = offset_day
	 * 	array['hour'] = liste des hours à traiter
	 * 	array['day'] = jour à traiter
	 *
	 * @since cb4.0.0.00
	 * @var array
	 */
	protected $compute_info;

	/**
	 * variable contenant l'id_group_table traité
	 *
	 * @since cb4.0.0.00
	 * @var int
	 */
	protected $id_group_table;

	/**
	 * variable contenant la liste des tables de donées (edw* et w_edw* qui existent en base)
	 * 	array[nom d'une table] = nom d'une table
	 * @since cb4.0.0.00
	 * @var array
	 */
	protected $existing_tables;

	/**
	 * tableau contenant des chaînes de caractères correspondant aux champs des compteurs
	 *
	 * 	array[index]['name'] = nom du compteur
	 * 	array[index]['formula'] = formule du compteur
	 * 	array[index]['agreg'] = corresponde au cas où un champ n'a pas de fonction d'agrégation (ex TRXID ou TARGET_CELLID) pour le mettre dans le group by
	 *
	 * @since cb4.0.0.00
	 * @var array
	 */
	protected $counters;

	/**
	 * tableau contenant des chaînes de caractères correspondant aux éléments réseaux s'agrégeant sur eux-mêmes
	 *
	 *	array[index] = élément réseau
	 *
	 * @since cb4.0.0.00
	 * @var array
	 */
	protected $selfAgregElement;

	/**
	 * Tableau contenant les informations de la famille en cours de traitement
	 *
	 *	array['family'] = family
	 *	array['edw_group_name'] = edw_group_name
	 *	array['ta_min'][time_aggregation] = array( 0 => id_ligne, 1 => time_aggregation_source)
	 * 	array['ta_deployed'][time_aggregation][index] = array( 0 => id_ligne, 1 => time_aggregation, 2 => agregation_level)
	 *	array['ta_min_deployed'] = time_agregation'
	 *	array['edw_group_name'] = edw_group_name
	 *	array['na_min'] = network_agregation ACTIVE
	 *	array['na_min_deployed'] = network_agregation deployé
	 *	array['na'][network_agregation] = array(0 => network_agregation source, 1 => network_agregation level)
	 *	array['bh_formula'] = formule de la BH
	 *
	 * @since cb4.0.0.00
	 * @var array
	 */
	protected $family_info;

	/**
	 * Tableau contenant les informations de toutes les familles activées (pour bouclage sur l'ensemble des familles)
	 *
	 *	array[id_group_table]['family'] = nom de la famille
	 *	array[id_group_table]['edw_group_name'] = edw_group_name de la famille
	 *
	 * @since cb4.0.0.00
	 * @var array
	 */
	protected $all_families;

	/**
	 * Tableau contenant les tables cibles / sources
	 *
	 * 	array[id_group_table][index]['target'] = nom de la table cible
	 * 	array[id_group_table][index]['source'] = nom de la table source
	 * 	array[id_group_table][index]['ta'] = time agrégation
	 * 	array[id_group_table][index]['na'] = network agrégation
	 * 	array[id_group_table][index]['ta_source'] = time agrégation
	 * 	array[id_group_table][index]['na_source'] = network agrégation
	 * 	array[id_group_table][index]['query_delete'] = requete SQL pour la suppression des données le cas de la reprise de données
	 * 	array[id_group_table][index]['query_insert'] = requete SQL pour l'insertion des données
	 *  array[id_group_table][index]['level'] = ordre d'exécution de la requête dans le cas de la parallélisation
	 *
	 * @since cb4.0.0.00
	 * @var array
	 */
	protected $tables;

        /**
         * 20/05/2011 BBX -PARTITIONING-
         * Permettra de savoir si la base de donnée est partitionnée ou non
         * Ce résultat sera stocké dans cette variable afin de ne pas refaire
         * La requête à chaque fois.
         * @var boolean
         */
        protected $_isPartitioned = false;

        /**
         * 25/05/2011 BBX -PARTITIONING-
         * Comptabilise les tables traitées
         */
        protected $_tablesDone = array();

        /**
         * 25/05/2011 BBX -PARTITIONING-
         * Stocke les jobs pour les éxécutions en parallèle
         */
        public $currentJobs = array();

        /**
         * 25/05/2011 BBX -PARTITIONING-
         * Contiendra le fichier temporaire d'échange des résultats
         */
        public $_tempFileComputeResuls = '';

        /**
         * 25/05/2011 BBX -PARTITIONING-
         * Stocke les heures pour le compute booster
         */
        protected $_hourToCompute = array();

        /**
         * 25/05/2011 BBX -PARTITIONING-
         * Stocke le statut du compute switch
         */
        protected $_computeSwitch = '';

        /**
         * 25/05/2011 BBX -PARTITIONING-
         * Permet de savoir si les oids sont activés
         * Utile pour le calcul de la BH
         */
        protected $_oidsEnabled = false;

	/**
         * 19/10/2012 BBX
         * @var object : productModel du produit courant
         * va notemment servir à déterminer si le produit courant est horaire ou journalier 
         */
        protected $_productModel = null;

	/**
         * 23/11/2012 BBX BZ 30587
         * Separator used fo splitting dates
         * @var string 
         */
        protected $_separator = '|s|';

	/**
	 * Constructeur de l'objet
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
 	 * @param ressource $database_connection : identifiant de la connexion pgsql
 	 * @param string $compute_categorie : chaine de caractères pour le type de compute à exécuter (raw ou kpi)
         * @param int $simulation simulation de l'enregistrement des donnees en bdd
         */
	protected function __construct ($compute_categorie)
	{
		// 19/11/2008 BBX : instanciation de l'objet databaseConnection
		$this->database = DataBase::getConnection();

                // 20/05/2011 BBX -PARTITIONING-
                $this->_isPartitioned   = $this->database->isPartitioned();
                $this->_hourToCompute   = $this->getHoursFromTable();
                $this->_computeSwitch   = get_sys_global_parameters('compute_switch');
                $this->_oidsEnabled     = ($this->database->getOne("SHOW default_with_oids") == 'on');
                $this->_separator       = get_sys_global_parameters('sep_axe3');

                // 19/10/2012 BBX
                // BZ 29806 : Instanciation du productModel
                $this->_productModel    = new ProductModel(0);

		$this->compute_info['categorie'] = $compute_categorie;
		$this->compute_info['type'] = get_sys_global_parameters("compute_processing");
		$this->compute_info['mode'] = get_sys_global_parameters("compute_mode");
		$this->debug = get_sys_debug('compute');

		echo "Compute Type : " . $this->compute_info['type'] . "<br>"; // affichage de l'info
		echo "Compute Mode : " . $this->compute_info['mode'] . "<br>"; // affichage de l'info
	} // End __construct

	/**
         * 02/12/2010 BBX
         * Test la liste des compteurs existants
         * BZ 17068
         *
         * @return boolean : vrai s'il y a des compteurs, sinon faux
         */
        public function isThereAnyCounter()
        {
            // Compte les compteurs existants pour la famille courante
            $query = "SELECT COUNT(id_ligne)
            FROM sys_field_reference
            WHERE id_group_table = ".$this->id_group_table."
            AND on_off = 1
            AND new_field = 0";
            // Retour booléen
            return (int)$this->database->getOne($query);
        }

	/**
	 * searchAllFamily : recherche de l'ensemble des familles activées
	 *
	 * @since cb4.0.0.00
	 * @version cb4.1.0.00
	 *	modif 19/11/2008 BBX : $this->sql retourné désormais un tableau de données (méthode getAll de la classe databaseConnection)
	 */
	public function searchAllFamilies()
	{
		$query = "
			SELECT
				t1.edw_group_table,
				t2.family,
				t2.rank
			FROM
				sys_definition_group_table t1,
				sys_definition_categorie t2
			WHERE
				t1.id_ligne = t2.rank
				AND t2.on_off = '1'";
		foreach($this->sql($query) as $row)
		{
			$this->all_families[$row['rank']]['family'] = $row['family'];
			$this->all_families[$row['rank']]['edw_group_table'] = $row['edw_group_table'];
		}
	} // End function searchAllFamilies

	/**
	 * searchExistingTableSource : recherche l'ensemble des tables de données edw_* et w_edw_* qui existent en base.
	 *
	 * @since cb4.0.0.00
	 * @version cb4.1.0.00
	 *	modif 19/11/2008 BBX : $this->sql retourné désormais un tableau de données (méthode getAll de la classe databaseConnection)
	 */
	function searchExistingTableSource()
	{
		$query = "
			SELECT tablename
                        FROM pg_tables
                        WHERE tablename LIKE 'w_edw%'
                        OR tablename LIKE 'edw_%_raw_%'
                        OR tablename LIKE 'edw_%_kpi_%'
                        ORDER BY tablename";
		foreach($this->sql($query) as $row)
		{
			$this->existing_tables[$row['tablename']] = $row['tablename'];
		}
	} // End function searchExistingTableSource

	/**
	 * getAllFamilies : récupération de l'ensemble des familles activées
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
 	 * @return array $this->all_families
	 */
	public function getAllFamilies()
	{
		return $this->all_families;
	} // End function getAllFamilies

	/**
	 * getSelfAgregationLevel : récupération des NA devant s'agréger sur elles-mêmes pour la famille traitée
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
 	 * @return array $this->all_families
	 */
	public function getSelfAgregationLevel()
	{
		return $this->selfAgregElement;
	} // End function getSelfAgregationLevel

	/**
	 * setGroupTable : initialisation de l'ensemble des group table à traiter
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
 	 * @param int $id_group_table : variable contenant l'identifiant du group table
	 */
	public function setIdGroupTable($id_group_table)
	{
		$this->id_group_table = $id_group_table;
	} // End function setIdGroupTable

	/**
	 * getComputeMode : récupération du mode de compute
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
 	 * @return string $compute_mode
	 */
	public function getComputeMode()
	{
		return $this->compute_info['mode'];
	} // End function getComputeType

	/**
	 * getComputeType : récupération du type de compute
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
 	 * @return string $compute_type
	 */
	public function getComputeType()
	{
		return $this->compute_info['type'];
	} // End function getComputeType

        /**
         * Retourne le nombre de periodes gérées lors du compute (mode Booster)
         * @return Unsigned Integer
         */
        public function getComputePeriods()
        {
            $nbPeriod = 0;
            $explodeHours = explode( get_sys_global_parameters( 'sep_axe3' ), get_sys_global_parameters( 'hour_to_compute' ) );
            if( $explodeHours != FALSE )
            {
                $nbPeriod = max( 1, count( $explodeHours ) );
            }
            else
            {
                // Erreur, on retourne la valeur par défaut
            }
            return $nbPeriod;
        }

	/**
	 * Retourne les périodes temporelles à traiter
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
 	 * @return array
	 */
	public function getComputePeriod()
	{
            if($this->compute_info['mode'] == 'hourly' && $this->compute_info['type'] == 'hour')
            {
                return $this->compute_info['hour'];
            }
            else
            {
                return array($this->compute_info['day']);
            }
	} // End function getComputeType

	/**
	 * getFamilyInfo : récupération des informations de la famille traitée
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
 	 * @return array $this->family_info
	 */
	public function getFamilyInfo()
	{
		return $this->family_info;
	} // End function getFamilyInfo

	/**
	 * mets à jour les time aggregation differents de hour en fonction de la catégorie de compute effectué,
	 * du compute Mode et du bypass
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @param  int $on_off : activation / désactivation des niveaux minimum
	 */
        public function updateTimeAgregationToCompute($on_off)
        {
			  //17/10/2011 MMT DE Bypass temporel deplacement du test sur compute mode dans la fonction updateTimeAgregationToCompute
			   if($this->getComputeMode() == 'hourly'){
					if($this->compute_info['type'] == 'hour')
						 $query = "UPDATE sys_definition_group_table_time SET on_off = '$on_off' WHERE time_agregation <> 'hour' AND data_type='".$this->compute_info['categorie']."'";
					else
						 $query = "UPDATE sys_definition_group_table_time SET on_off = '$on_off' WHERE time_agregation = 'hour' AND data_type = '".$this->compute_info['categorie']."'";
				//17/10/2011 MMT DE Bypass temporel si on est en compute mode daily, il faut sauter tous les operations
			   // sur les heures pour les familles en Bypass Day
				}else if ($this->getComputeMode() == 'daily'){
					if($this->getComputeType() == "day"){ // en compute booster on est en mode daily type hour
						$query = "UPDATE sys_definition_group_table_time SET on_off = '$on_off'
								WHERE data_type='".$this->compute_info['categorie']."'
								AND id_ligne in
							 (SELECT gtt.id_ligne FROM sys_definition_group_table gt, sys_definition_group_table_time gtt, sys_definition_categorie dc
								WHERE gt.id_ligne = gtt.id_group_table
								AND gt.family = dc.family
								AND dc.ta_bypass = 'day'
								AND gtt.time_agregation = 'hour' )
							 ";
					}
				}
				if(!empty($query)){
					$resultat = $this->sql($query);
				}
        } // End function updateTimeAgregationToCompute


		  /**
		   * 21/10/2011 MMT Bz 24263 pas de recalcul des niveau day si bypass day sans de fichier day intégré
		   *
		   * 27/10/2011 MMT Bz 24440 les TA day,week et month parfois désactivés lors du compute booster
		   * Update the sys_definition_group_table_time table to enable/disable TA level to be calculated
		   * depending on the TA bypass source table
		   * if a TA bypass level do not have a bypass table, there is no need to do any calculation
		   *
		   * $on_off enable/disable TA levels
		   * NOTE: if the $on_off is 0, TA levels will still be set to ON if the Source Table is present
		   */
		  public function updateTimeAgregationToBypassSourceTable($on_off)
		  {

			  //27/10/2011 MMT Bz 24440 on ne rentre pas que dans le cas hourly hour -> aucun interet sur les niveaux >
				if ($this->getComputeMode() != "hourly" || $this->getComputeType() != "hour") {

					//check for each family if the table exist
					foreach ($this->all_families as $family_info) {
						// only for bypassed families
						if(TaModel::IsTABypassedForFamily("day", $family_info['family'])==1){
							//27/10/2011 MMT Bz 24440  prise en compte du parametre $on_off, si off, il faut verifier si la table est présente
							if($on_off == 0){
								$tableExist = $this->doesTABypassTempSourceTableExist($family_info['edw_group_table'], "day");
								if($tableExist){
									$on_off_query = 1;
									displayInDemon("<b>Day Bypass Table found for Family '".$family_info['family']."' </b>");
								} else {
									displayInDemon("<b>Could not find Day Bypass Table for Family '".$family_info['family']."' </b> Disableling Compute Day for this family to TA levels day, week and month");
									$on_off_query = 0;
								}
							} else {
								//27/10/2011 MMT Bz 24440
								// si on, toujours on
								$on_off_query = $on_off;
							}
							// if bypass Day, disable/enable day week and month
							//27/10/2011 MMT Bz 24440 ajout de la condition sur le data_type
							$query = "UPDATE sys_definition_group_table_time SET on_off = '$on_off_query'
								WHERE data_type='".$this->compute_info['categorie']."'
							    AND id_ligne in (
									SELECT gtt.id_ligne FROM sys_definition_group_table gt, sys_definition_group_table_time gtt
									WHERE gt.id_ligne = gtt.id_group_table
									AND gt.family = '".$family_info['family']."'
									AND gtt.time_agregation in ('day','week','month')
									  )";
							$resultat = $this->sql($query);
						}
					}
				}
		  }


		  /**
		   * 21/10/2011 MMT Bz 24263 pas de recalcul des niveau day si bypass day sans de fichier day intégré
		   *
		   * Method that checks if the temporary table created in the retrieve exists for a bypassed
		   * Family.
		   * @param string $familyGroupTable GroupTable name of the family
		   * @param string $ta Bypassed TA (day/hour)
		   * @return boolean true if the table exist false if id does not
		   */
		  private function doesTABypassTempSourceTableExist($familyGroupTable,$ta)
		  {
			  $ret = false;
				foreach ($this->existing_tables as $tableName)
				{
					//displayInDemon("-----table test : $tableName $familyGroupTable  $ta  ".$this->compute_info[$ta]);
					if(strstr($tableName, "w_".$familyGroupTable) && strstr($tableName, "bypass_".$ta."_".$this->compute_info[$ta]))
					{
						$ret = true;
						break;
					}
				}
				return $ret;
		  }

        /**
         * 06/01/2010 : Correction du bz19972 - le calcul du RI peut se baser sur le 3ème axe
         * Fonction qui récupère l'axe sur lequel on compte le nombre d'éléments réseau
         * @param string $family : Famille concernée
         * @return integer : axe sur lequel on compte le nombre d'éléments réseau
         */
        public function getNetworkAxeRi($family)
        {
            if( !isset($this->_networkAxeRi[$family]) )
            {
                $query = "SELECT network_axe_ri FROM sys_definition_categorie WHERE family = '{$family}' LIMIT 1";
                $resultat = $this->sql($query);
                $this->_networkAxeRi[$family] = $resultat[0]['network_axe_ri'];
            }
            return $this->_networkAxeRi[$family];
        }

        /**
	 * recherche des informations sur la famille en cours
	 *
	 * @since cb4.0.0.00
	 * @version cb4.1.0.00
	 *	modif 19/11/2008 BBX : $this->sql retourné désormais un tableau de données (méthode getAll de la classe databaseConnection)
	 */
	public function searchFamilyInfo()
	{
		unset($this->family_info);

		$this->family_info = $this->all_families[$this->id_group_table];

		// récupération des niveaux temps minimum pour la famille
		$query = "
			SELECT
				id_ligne,
				time_agregation,
				(select time_agregation from sys_definition_group_table_time where id_ligne = t1.id_source) AS time_agregation_source
			FROM
				sys_definition_group_table_time t1
			WHERE
				id_group_table = " . $this->id_group_table . "
				AND data_type = '" . $this->compute_info['categorie'] . "'
				AND on_off = '1'
				AND id_source IN
					(
						SELECT
							MIN(id_source)
						FROM
							sys_definition_group_table_time
						WHERE
							id_group_table = ".$this->id_group_table."
							AND data_type = '" . $this->compute_info['categorie'] . "'
							AND on_off = '1'
					)";
		foreach($this->sql($query) as $row)
			$this->family_info['ta_min'][$row['time_agregation']] = array($row['id_ligne'], $row['time_agregation_source']);

		// récupération des times agrégations
		$query = "
			SELECT
				time_agregation,
				id_ligne,
				id_source,
				on_off,
				(select time_agregation from sys_definition_group_table_time where id_ligne = t1.id_source) AS time_agregation_source,
				(SELECT agregation_level FROM sys_definition_time_agregation WHERE agregation = t1.time_agregation) AS time_agregation_level
			FROM
				sys_definition_group_table_time t1
			WHERE
				data_type = '".$this->compute_info['categorie']."'
				AND id_group_table = '".$this->id_group_table."'
			ORDER BY id_source";
		/*
		$resultat = $this->sql($query);
		$row = pg_fetch_array($resultat);
		$this->family_info['ta_min_deployed'] = $row['time_agregation'];
		do
		{
			$this->family_info['ta'][] = $row['time_agregation'];
			if( $row['on_off'] == 1 )
				$this->family_info['ta_deployed'][$row['time_agregation']] = array($row['id_ligne'], $row['time_agregation_source'], $row['time_agregation_level']);
		} while ($row = pg_fetch_array($resultat));
		*/
		$this->family_info['ta_min_deployed'] = "";
		foreach($this->sql($query) as $row)
		{
			if($this->family_info['ta_min_deployed'] == "")
				$this->family_info['ta_min_deployed'] = $row['time_agregation'];
			$this->family_info['ta'][] = $row['time_agregation'];
			if( $row['on_off'] == 1 )
				$this->family_info['ta_deployed'][$row['time_agregation']] = array($row['id_ligne'], $row['time_agregation_source'], $row['time_agregation_level']);
		}

		// récupération du niveau minimum ACTIVE de la famille
		$query = "
			SELECT
				network_agregation
			FROM
				sys_definition_group_table_network
			WHERE
				id_group_table = ".$this->id_group_table."
				AND data_type = '" .$this->compute_info['categorie'] . "'
				AND on_off = 1
			ORDER BY rank
			LIMIT 1
		";
		$resultat = $this->sql($query);
		$row = $resultat[0];
		$this->family_info['na_min_deployed'] = $row['network_agregation'];

		// récupération du niveau minimum ACTIVE OU DESACTIVE de la famille
		$query = "
			SELECT
				network_agregation
			FROM
				sys_definition_group_table_network
			WHERE
				id_group_table = ".$this->id_group_table."
				AND data_type = '" .$this->compute_info['categorie'] . "'
			ORDER BY rank
			LIMIT 1
		";
		$resultat = $this->sql($query);
		$row = $resultat[0];
		$this->family_info['na_min'] = $row['network_agregation'];

		// récupération des niveaux d'agragation de la famille
		$query = "
			SELECT
				t1.network_agregation AS agregation,
				(SELECT network_agregation FROM sys_definition_group_table_network WHERE id_ligne = t1.id_source) AS agregation_source,
				(SELECT agregation_level FROM sys_definition_network_agregation WHERE agregation = t1.network_agregation AND family = t2.family) AS agregation_level
			FROM
				sys_definition_group_table_network t1, sys_definition_categorie t2
			WHERE
				t1.id_group_table = t2.rank
				AND id_group_table = ".$this->id_group_table."
				AND data_type = '".$this->compute_info['categorie']."'
				AND t1.on_off='1'
				AND t1.rank >=
					(
						SELECT
							rank
						FROM
							sys_definition_group_table_network
						WHERE
							network_agregation = '".$this->family_info['na_min']."'
							AND id_group_table = ".$this->id_group_table."
							AND data_type = '".$this->compute_info['categorie']."'
					)
			ORDER BY t1.rank
		";
		foreach($this->sql($query) as $row)
			$this->family_info['na'][$row['agregation']] = array(0 => $row['agregation_source'], 1 => $row['agregation_level']);

		// 11:45 15/10/2010 SCT : BZ 18427 => Désactivation de compteur utilisé pour la BH possible
                //  + retour de la méthode "getBHFormula" sous forme de tableau pour la gestion des messages d'erreur
                // DEBUT BUG 18427
		// recherche de la formule de calcul de la BH
		$retourMethodeBh = getBHFormula($this->family_info['family']);
                $this->family_info['bh_formula'] = $retourMethodeBh['formula'];

		// maj 16:56 05/03/2010 - MPR : Correction du BZ 14165 - Message d'erreur pas assez explicite
		if( $this->family_info['bh_formula'] == "0" )
		{
				$this->_error_bh_formula = __T("A_COMPUTE_MSG_ERROR_BH_FORMULA_IS_ZERO");
		}
		else
		{
			$this->_error_bh_formula = '';
		}
                // 11:45 15/10/2010 SCT : BZ 18427 => ajout du message d'erreur retourné par la méthode
                if(!empty($retourMethodeBh['error']))
                    $this->_error_bh_formula = $retourMethodeBh['error'];
                // en cas d'erreur, on l'indique dans le tracelog
                if(!empty($this->_error_bh_formula))
                    sys_log_ast('Warning', get_sys_global_parameters( 'system_name' ), __T( 'A_TRACELOG_MODULE_LABEL_COMPUTE' ), $this->_error_bh_formula, 'support_1', '' );
		// FIN BUG 18427
		if ( $this->debug & 2 )
		{
			echo '<br />Formule de la BH : <code>'.$this->family_info['bh_formula'] .'</code><br /><br />';
		}
	} //End function searchFamilyInfo

        /**
         * recherche des NA devant s'agréger sur eux-mêmes pour la famille traitée
         *
         * @since cb4.0.0.00
         * @version cb4.1.0.00
         *	modif 19/11/2008 BBX : $this->sql retourné désormais un tableau de données (méthode getAll de la classe databaseConnection)
         */
        public function searchSelfAgregationLevel()
        {
                    unset($this->selfAgregElement);
            $query = "
                                    SELECT
                                            network_agregation
                                    FROM
                                            sys_definition_group_table_network
                                    WHERE
                                            id_source = id_ligne
                                            AND data_type = 'raw'
                                            AND id_group_table = '".$this->id_group_table."'";
            foreach($this->sql($query) as $row)
                    {
                    $this->selfAgregElement[] = $row['network_agregation'];
                    }
        } // End function searchSelfAgregationLevel

        /**
	 * Recherche de les périodes temporelles à traiter
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 */
	public function searchComputePeriod()
	{
            // Condition pour récupérer les données horaires
            if($this->compute_info['mode'] == 'hourly' && $this->compute_info['type'] == 'hour')
            {
                // recherche des heures à traiter
                $this->searchComputeHour();
                $this->searchComputeDay();
                // on calcule jour à partir de l'heure puis le offset day à partir du jour pour ne pas avoir les problèmes au passage d'un changement de jour (Minuit)
                // on travaille sur le premier élément du tableau. Normalement, les heures contenues dans le tableau ne dépassent pas la journée
                $day_from_hour = substr($this->compute_info['hour'][0], 0, -2);
                // initialisation de la variable offset pour l'objet
                $this->compute_info['offset'] = Date::getOffsetDayFromDay($day_from_hour);
                echo "<p>Offset : ".$this->compute_info['offset']."</p>";
                echo 'Heures à traiter : ' . implode(', ', $this->compute_info['hour']) . '<br>';
                echo 'Jour Correspondant à l\'heure : ' . $day_from_hour . '<br>';
            }
            else // ($this_compute_mode == 'hourly' AND $this->compute_type == 'day') || $this_compute_mode == 'day
            {
                // 23/11/2012 BBX
                // BZ 30587 : modification de la méthode de récupération de la journée à traiter
                $this->searchComputeDay();
                $this->compute_info['offset'] = Date::getOffsetDayFromDay($this->compute_info['day']);
                echo 'Jour à traiter : ' . $this->compute_info['day'] . '<br>';
            }
	} // End function searchComputePeriod

        /**
	 * Mise à jour de $this->compute_info['offset'] pour prévenir le changement de jour pendant un compute
	 *
	 * @since cb4.0.0.05
	 * @version cb4.0.0.05
	 * Ajouté le 16/07/2008 par BBX; Bug 7078
	 */
	public function updateComputeOffset()
	{
		// maj 22/07/2008 BBX : mise à jour de l'offset en fonction du mode de compute. BZ 7157
		// Mise à jour sur compute Hour
		if($this->compute_info['mode'] == 'hourly' && $this->compute_info['type'] == 'hour')
		{
			$day_from_hour = substr($this->compute_info['hour'][0], 0, -2);
                $this->compute_info['offset'] = Date::getOffsetDayFromDay($day_from_hour);
		}
		// Mise à jour sur compute day
		else
		{
                $this->compute_info['offset'] = Date::getOffsetDayFromDay($this->compute_info['day']);
                // 23/11/2012 BBX
                // BZ 30587 : la valeur day sera désormais toujours la bonne                
                // $this->compute_info['day'] = Date::getDayFromDatabaseParameters($this->compute_info['offset']);
		}
		// 29/07/2009 BBX : Mise à jour de l'offset en base. BZ 8354
            // 23/11/2012 BBX
            // BZ 30587 : on ne touche plus à l'offset day : ceci est géré par une cron
            //$query = "UPDATE sys_global_parameters SET value = '{$this->compute_info['offset']}'
            //WHERE parameters = 'offset_day'";
            //$this->sql($query);
	}

        /**
	 * recherche des heures à traiter depuis la base de données
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 */
        private function searchComputeHour()
	{
            // 23/11/2012 BBX
            // BZ 30587 : légère modification des heures à computer
            if ( empty($this->compute_info['hour']) ) {
                $this->compute_info['hour'] = explode($this->_separator, get_sys_global_parameters('hour_to_compute'));
            }           
	} // End function searchComputeHour

	/**
	 * recherche de la journée à traiter depuis la base de données 
         * sans passer par l'offset day : trop instable
	 * 23/11/2012 BBX
         * BZ 30587 : ajout de la méthode
	 */
        private function searchComputeDay()
	{
            // 23/11/2012 BBX
            // BZ 30587 : légère modification des heures à computer
            if ( empty($this->compute_info['day']) ) {
                $this->compute_info['day'] = get_sys_global_parameters('day_to_compute');
            }           
	} // End function searchComputeHour

	/**
	 * retourne l'offset_day pris dans sys_global_parameters
         * 27/11/2012 BBX BZ 30587 : passage en protected pour être hérité
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 */
        protected function searchOffsetDay()
        {
            $this->compute_info['offset'] = get_sys_global_parameters('offset_day');
        } // End function searchOffsetDay

        /**
         * 23/11/2012 BBX
         * BZ 30587 : returns the first computable day
         * @return day
         */
        public static function getFirstComputableDay( $dayLimit = "" )
        {
            $database = Database::getConnection();
            
            $condition = "";
            if( !empty($dayLimit) ) $condition = " AND t0.day < $dayLimit ";
            $query = "SELECT t0.day FROM sys_to_compute t0
            WHERE t0.time_type = 'day'
            AND NOT EXISTS (SELECT hour FROM sys_to_compute WHERE time_type = 'hour' AND day = t0.day)
            $condition
            ORDER BY t0.day DESC LIMIT 1";
            return $database->getOne($query);
        }
        
        /**
         * Returns hours to compute
         * @param type $day
         * @return type
         */
        public static function getHoursToCompute( $day = "" )
        {
            $database = Database::getConnection();
            
            $condition = "";
            if( !empty($day) ) $condition = " AND day = $day ";
            $hourList = array();
            $query = "SELECT DISTINCT hour
            FROM sys_to_compute 
            WHERE time_type = 'hour'
            $condition
            ORDER BY hour DESC";
            $result = $database->execute($query);
            while ( $row = $database->getQueryResults($result,1) ) {
                $hourList[] = $row['hour'];
            }
            return $hourList;
        }

        /**
	 * Création des tables cibles pour la famille traitée
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 */
	public function createTargetTables()
	{
            if ( $this->debug & 2 )
            {
                echo '<b>TA déployés</b><pre>'.print_r($this->family_info['ta_deployed'], 1).'</pre>';
                echo '<b>NA</b><pre>'.print_r($this->family_info['na'], 1).'</pre>';
            }

            $times = array();
				//21/10/2011 MMT Bz 24263 si pas de TA warning affiché
				if(isset ($this->family_info['ta_deployed'])){
					foreach ( $this->family_info['ta_deployed'] AS $ta => $tableau_info_ta)
					{
						 // on élimine les aggregation temporelles liées à la BH lorsque celle ci n'est pas définie
						 if( (isATimeBH($ta) && $this->family_info['bh_formula'] != null) || !isATimeBH($ta) )
									$times[] = array(0 => $ta, 1 => $tableau_info_ta[2]);
					}
				}

            // Création des tables cibles
            $targetTables = array();
            $cpt = 0;

            // Base partitionnée
            // maj 26/12/2012 - Correction du bz 25284 - Compute Booster KO sur le jour courant sur T&A partitioné
            // On ne boucle plus sur toutes les heures de $this->compute_info['hour'],
            // une première boucle dans compute_[raw|kpi]_proc.php existe déjà
            // BZ 30587 : Lecture de l'offset day depuis la base de donnée
            // Afin d'obtenir la valeur insérée lors du compute launcher
            $this->searchOffsetDay();
            if( $this->_isPartitioned && $this->compute_info['offset'] != 0 )
            {
                // BZ 30587 : Mise à jour de l'offset day dans l'objet 
                // afin de correspondre a la réalité
                $this->updateComputeOffset();
                foreach ( $this->family_info['na'] as $na => $tableau_info_na )
                {
                    foreach ( $times AS $tableau_info_ta )
                    {
                        foreach($this->getComputePeriod() as $period)
                        {
                            // Si on est en compute switch, on ajoute les tables horaires
                            if( $this->_computeSwitch == 'hourly' && $tableau_info_ta[0] == 'hour' )
                            {
                                foreach($this->_hourToCompute as $hour)
                                {
                                    $tableTarget = $this->family_info['edw_group_table'].'_'.$this->compute_info['categorie'].'_'.$na.'_hour';
                                    $partition = new Partition($tableTarget, $hour, $this->database);
                                    $targetTables[$cpt]['target'] = $partition->getName();
                                    $targetTables[$cpt]['na'] = $na;
                                    $targetTables[$cpt]['ta'] = 'hour';
                                    $targetTables[$cpt]['level'] = sprintf("%d%02d", $tableau_info_ta[1], $tableau_info_na[1]);
                                    $cpt++;
                                }
                            }
                            // Autres cas
                            else
                            {
                                // Date correspondant à la période
                                if($tableau_info_ta[0] == 'hour') {
                                    $timePeriod = $period;
                                }
                                else {
                                    // 23/11/2012 BBX
                                    $dateFunction = "get".ucfirst(str_replace('_bh','',$tableau_info_ta[0]))."FromDatabaseParameters";
                                    $timePeriod = Date::$dateFunction($this->compute_info['offset']);
                                }

                                // Récupération de la partition + autres infos
                                $tableTarget = $this->family_info['edw_group_table'].'_'.$this->compute_info['categorie'].'_'.$na.'_'.$tableau_info_ta[0];
                                // 12/06/2012 NSE bz 27382 : modification de l'appel (ajout du param $this->existing_tables)
                                $partition = new Partition($tableTarget, $timePeriod, $this->database, $this->existing_tables);
                                $targetTables[$cpt]['target'] = $partition->getName();
                                $targetTables[$cpt]['na'] = $na;
                                $targetTables[$cpt]['ta'] = $tableau_info_ta[0];
                                $targetTables[$cpt]['level'] = sprintf("%d%02d", $tableau_info_ta[1], $tableau_info_na[1]);
                                $cpt++;
                            }
                        }
                    }
                }
            }
            // Base non partitionnée ou partitionnée sur le jour courant
            else
            {
                foreach ( $this->family_info['na'] as $na => $tableau_info_na )
                {
                    foreach ( $times AS $tableau_info_ta )
                    {
                        $targetTables[$cpt]['target'] = $this->family_info['edw_group_table'].'_'.$this->compute_info['categorie'].'_'.$na.'_'.$tableau_info_ta[0];
                        $targetTables[$cpt]['na'] = $na;
                        $targetTables[$cpt]['ta'] = $tableau_info_ta[0];
                        $targetTables[$cpt]['level'] = sprintf("%d%02d", $tableau_info_ta[1], $tableau_info_na[1]);
                        // maj 26/12/2012 - Correction du bz 25284 - Compute Booster KO sur le jour courant sur T&A partitioné
                        // On initialise les tables cibles sans la ta_value sinon on boucle N fois sur chaque table (N représent le nombre d'heure à traiter)
                        // La variable $targetTables[$cpt]['base'] nous permettra d'ajouter pour tous les calculs la ta_value à la table cible associée
                        if( $this->compute_info['offset'] == 0 && $this->_isPartitioned )
                        {
                            $targetTables[$cpt]['base'] = $this->family_info['edw_group_table'].'_'.$this->compute_info['categorie'].'_'.$na.'_'.$tableau_info_ta[0];
                        }
                        
                        $cpt++;
                    }
                }
            }

            // Debug
            if ( $this->debug & 2 )
            {
                echo '<b>Ordre d\'exécution des requ&ecirc;tes</b>';
                foreach($targetTables AS $temp_compteur => $temp_tableau)
                        $temp_tableau_affichage[$temp_tableau['level']][] = $temp_tableau['target'];
                ksort($temp_tableau_affichage);
                echo '<pre>';
                print_r($temp_tableau_affichage);
                echo '<pre>';
                echo '<b>Tables cibles avant filtrage</b><pre>'.print_r($targetTables, 1).'</pre>';
            }

            // On filtre les tables cibles
            $this->tables[$this->id_group_table] = $this->filterTargetTables($targetTables);

            if ( $this->debug & 2 )
            {
                echo '<b>Tables cibles apres filtrage</b><pre>'.print_r($this->tables[$this->id_group_table], 1).'</pre>';
            }
        } // End function createTargetTables

	/**
	 * Filtre les tables cibles à calculer
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @param array $tables
	 * @return array
	 */
	private function filterTargetTables ( $tables )
	{
		// Lors du compute RAW on ne calcule pas les tables sur les NA qui s'agrège eux-même avec la ta_min
                /* modification pour calcul du niveau minimum lors du compute et non du retrieve
                if ( count($this->selfAgregElement) > 0 )
                {
                    $ta_min = $this->family_info['ta_min'];
                    foreach ( $tables as $index => $table )
                    {
                            if ( in_array($table['na'], $this->selfAgregElement) && $table['ta'] = $ta_min )
                                    unset($tables[$index]);
                    }
                }
                */
		$result = array();
		if ( $this->detectChangePeriod('day') )
		{
			echo ' - Changement de jour<br>';
			$result = array_merge($result, $this->filter($tables, 'hour'));
			$result = array_merge($result, $this->filter($tables, 'day')); // comprend aussi day_bh
		}
		if ( $this->detectChangePeriod('week') )
		{
			echo ' - Changement de semaine<br>';
			$result = array_merge($result, $this->filter($tables, 'week')); // comprend aussi week_bh
		}
		if ( $this->detectChangePeriod('month') )
		{
			echo ' - Changement de mois<br>';
			$result = array_merge($result, $this->filter($tables, 'month')); // comprend aussi month_bh
		}

		return $result;
	} // End function filterTargetTables

	/**
	 * Création des requêtes SQL pour nettoyer les tables cibles (cas de la reprise de données)
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @param string $period : période à traiter hour ou day (ex: 2007012415 ou 20071224)
	 */
	public function createRequestDeleteTables ( $period )
	{
            // BZ 30587 : mise à jour de l'offset day systématique avant d'aller le lire
            // afin de prendre en compte un éventuel changement de jour à ce moment précis
            $this->updateComputeOffset();
            
		$untilmonth = Date::getMonthFromDatabaseParameters($this->compute_info['offset']);
                // BZ 30587 : utilisation de la valeur day directement
                $untilweek = Date::getWeekFromDay($this->compute_info['day']);
		$requetes_del = array();
		foreach ( $this->tables[$this->id_group_table] as $index => $table )
		{
			$requete = "DELETE FROM ".$table['target']." WHERE ";
			switch ( $table['ta'] )
			{
                            case 'hour':
                                // si on est dans un mode de computehourly toutes les heures, on traite heure par heure d'ou la clause where du delete
                                // sinon on gère un mode hourly à la journée.
                                if ( $this->compute_info['mode'] == 'hourly' )
                                    $requete .= "hour = '".$period."'";
                                                    else
                                    $requete .= "day = '".$period."'";
                                break;

                            case 'day':
                                $requete .= "day = '".$period."'";
                                break;

                            case 'week':
                                $requete .= "week = '".$untilweek."'";
                                break;

                            case 'month':
                                $requete .= "month = '".$untilmonth."'";
                                break;

                            case 'day_bh':
                                $requete .= "day_bh = '".$period."'";
                                break;

                            case 'week_bh':
                                $requete .= "week_bh = '".$untilweek."'";
                                break;

                            case 'month_bh':
                                $requete .= "month_bh = '".$untilmonth."'";
                                break;
                        }

			$this->tables[$this->id_group_table][$index]['query_delete'] = $requete;
		}
	} // End function createRequestDeleteTables

	/**
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @param string $period
	 * @return boolean
	 */
	private function detectChangePeriod ($period)
	{
		// Vairable de changement
		$periodChange = false;
		// Journée à intégrer
                // BZ 30587 : utilisation de la valeur day directement
		$dayFromOffsetDay = $this->compute_info['day'];

		// 30/07/2009 BBX : modification des conditions de calcul de changement de période
		// 21/09/2009 BBX : modification des conditions de calcul de changement de période. BZ 11531
		// Condition 1 : la période de l'offset day est différente de la période courante
		//	=> intégration de données de la période précédente (reprise de données, réintégration de données, intégration du jour précédent étant le dernier jour de la période précédente)
		// Condition 2 : le jour des données à intégrer correspond au dernier jour de la période de données à intégrer
		switch ( $period )
		{
			case "day":
                        case "day_bh":
				// On calcul toujours le compute DAY
				$periodChange = true;
			break;

			case "week":
                        case "week_bh":
                                // BZ 30587 : récupération du week par rapport au day
				$weekFromOffsetDay = Date::getWeekFromDay($dayFromOffsetDay);
				$currentWeek = Date::getWeekFromDatabaseParameters('0');
				$lastDayOfWeek = Date::getLastDayFromWeek($weekFromOffsetDay,get_sys_global_parameters('week_starts_on_monday'));
				$periodChange = (($weekFromOffsetDay != $currentWeek) || ($lastDayOfWeek == $dayFromOffsetDay));
			break;

			case "month":
                        case "month_bh":
				$monthFromOffsetDay = Date::getMonthFromDatabaseParameters();
				$currentMonth = Date::getMonthFromDatabaseParameters('0');
				$lastDayOfMonth = Date::getLastDayFromMonth($monthFromOffsetDay);
				$periodChange = (($monthFromOffsetDay != $currentMonth) || ($lastDayOfMonth == $dayFromOffsetDay));
			break;
		}

		// Retour du statut de changement de période
		return $periodChange;
	} // End function detectChangePeriod

	/**
	 * Retourne untableau avec les nom de tables en fonction de la période
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @param array $tables
	 * @param string $period
	 * @return array
	 */
        private function filter ( $tables, $period )
        {
            $result = array();
            foreach ( $tables as $index => $table )
            {
                if(substr_count($table['ta'], $period) > 0)
                    $result[$index] = $table;
            }
            return $result;
        } // End function filtre


	/**
	 * Permet d'afficher les requêtes exécutées.
	 * 20/05/2011 BBX -PARTITIONING-
         * Modification de la fonction pour afficher les résultats d'éxécution
         * lorsque la base est partitionnée (ou non)
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @param array $computeArray liste des requêtes exécutées avec leurs résultats
	 * @return html
	 */
	function displayQueries(array $computeArray)
	{
            // Préparation de l'affichage
            $html = '';
            $html .= "<table border=1 width=10000  style='text-align:left;font : normal 7pt Verdana, Arial, sans-serif;color : #585858;'>";
            $html .= "<tr> <th>Cible</th> <th>Source</th> <th>Requête</th> </tr>";

            // Parcours des données d'entrée
            foreach($computeArray as $level => $computeInstructions)
            {
                // Parcours des données d'entrée
                foreach($computeInstructions as $index => $currentInstructions)
                {
                    // Affichage des informations préliminaires
                    $html .=
                    "<tr style='vertical-align:top;'>".
                            "<td width=100>".$currentInstructions['target']."</td>".
                            "<td width=100>".$currentInstructions['source']."</td>".
                            "<td width=9800>".
                                    "&#149;&nbsp;Calcul de ".$currentInstructions['target']."<br/>".
                                    "&#149;&nbsp;".date('r') . "<br/>";

                    // Parcours des resquêtes de calcul et de leur résultat
                    foreach($currentInstructions['queries'] as $name => $query) {
                        if(!preg_match('/_result$/', $name) && ($name != 'execution_time')) {
                            $html .= "&#149;&nbsp;".$currentInstructions['queries'][$name.'_result'];
                            $html .= '='.$query."<br/>";
                        }
                    }

                    // Affichage du temps d'éxécution
                    $html .= "&#149;&nbsp;Temps d'exécution : ".$currentInstructions['execution_time']." secondes, soit " . date("i:s", $currentInstructions['execution_time']);
                            "</td>
                    </tr>";
                }
            }

            // Fin de l'affichage
            $html .= "</table>";
            echo $html;
	}

        /**
         * Returns the number of threads on server
         * @return int
         */
        protected function getNbThreads()
        {
            $nbT = 0;
            $cpuInfo = file('/proc/cpuinfo');
            foreach($cpuInfo as $line) {
                if(preg_match('/^processor/',$line)) $nbT++;
            }
            return $nbT;
        }

        /**
         * Determines the amount of threads to use for compute booster
         * @return integer
         */
        protected function getMaxThreadsForComputeBooster()
        {
            $nbFamilies = count(FamilyModel::getAllFamilies());
            $nbThreads  = $this->getNbThreads();
            $nbJobs     = 1;
            if(($nbThreads - $nbFamilies) >= 1) {
                $nbJobs = floor($nbThreads / $nbFamilies);
            }
            return $nbJobs;
        }

        /**
         * Fonction qui retourne les Hours à calculer
         * @return array : Paramètre global hour_to_compute
         */
        protected function getHoursFromTable()
        {
            return explode("|s|",  get_sys_global_parameters("hour_to_compute") );
        }

	/**
	 * Exécute les requêtes.
	 * Retourne un tableau contenant la liste des requêtes exécutée avec le temps de traitement.
         * 20/05/2011 BBX -PARTITIONING-
         * Evolution de la fonction pour gérer les requêtes avec partitioning
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 *@return array
	 */
	public function executeRequest ()
	{
            // Tableau qui va contenir les instruction de compute dans l'ordre
            $computeArray = array();

            // Tableau qui va contenir les tables calculées
            $tablesDone = array();

            // Initialisation du fichier d'échange des résultats
            $this->_tempFileComputeResults = '/tmp/compute_results_'.$this->family_info['family'];
            // Suppression du fichier temporaire
            if(file_exists($this->_tempFileComputeResults))
                unlink($this->_tempFileComputeResults);

            // Récupération des instrcutions de compute
            foreach($this->tables[$this->id_group_table] AS $index => $table)
            {
                $queryList = array();
                // Requêtes pour une base partitionnée
                if($this->_isPartitioned)
                {
                    $queryList['query_begin']       = $table['query_begin'];
                    $queryList['query_truncate']    = $table['query_truncate'];
                    $queryList['query_insert']      = $table['query_insert'];
                    $queryList['query_commit']      = $table['query_commit'];
                    $queryList['query_analyze']     = $table['query_analyze'];
                }
                // Requêtes pour une base non partitionnée
                else
                {
                    $queryList['query_delete'] = $table['query_delete'];
                    $queryList['query_insert'] = $table['query_insert'];
                }
                // Inscription des traitements
                $computeArray[$table['level']][] = array('target' => $table['target'],
                                                        'source' => $table['source'],
                                                        'queries' => $queryList);
            }

            // Tri des calculs
            ksort($computeArray);

            // Pour le moment, on ne // que si :
            // - on est sur une base partitionnée
            // - on est sur un compute switch
            $maxProcesses = 1;
            $pcntl_started = false;
            if($this->_isPartitioned && $this->_computeSwitch == 'hourly') {
                $maxProcesses = $this->getMaxThreadsForComputeBooster();
            }

            // Exécution des calculs
            foreach($computeArray AS $level => $computeInstructions)
            {
                foreach($computeInstructions as $index => $currentInstructions)
                {
                    // On exécute les requêtes seulement si la table source existe.
                    if(isset($this->existing_tables[$currentInstructions['source']]))
                    {
                        // Avant de lancer un calcul, on doit s'assurer que la table source existe.
                        // Cependant, on ne vérifie pas si:
                        // - on est sur un compute raw et la table source est une table w_edw
                        // - on est sur un compute kpi et la table source est une table raw
                        $conditionTableSource = (strncmp($currentInstructions['source'],'w_edw', 5) != 0);
                        if($this->compute_info['categorie'] == 'kpi')
                            $conditionTableSource = (!substr_count($currentInstructions['source'], '_raw_'));
                        // Si la condition sur la table source est validée
                        if($conditionTableSource) {
                            // On attend que la table source soit calculée
                            // sauf si aucune table n'a encore été calculée
                            if(!empty($this->_tablesDone)) {
                                // Tant que la table source n'est pas calculée
                                while((!in_array($currentInstructions['source'],$this->_tablesDone)))
                                {
                                    // Si tous les slots sont libérés, on continue
                                    // quand même pour éviter de bloquer le compute.
                                    if(empty($this->currentJobs)) break;
                                    // Sinon on attend que les processus en cours se terminent
                                    $this->waitForFreeCalculationSlot();
                                }
                            }
                        }
                        
                        if ( $maxProcesses == 1 ) 
                        {
                            $this->launchSeqProcess($currentInstructions['queries'], $index);
                        }
                        else 
                        {
                            // Vérification du nombre de processus lancés
                            if(count($this->currentJobs) >= $maxProcesses) {
                                // On attend qu'un processus se termine
                                $this->waitForFreeCalculationSlot();
                            }

                            // Lancement d'un processus de calcul
                            $this->currentJobs[$currentInstructions['target']] =
                                $this->launchProcess($currentInstructions['queries'], $index);
                        }
                    }
                    // Si la table n'existe pas, on mémorise les erreurs
                    else
                    {
                        $queryResults['query_insert'] = "<span style='color:#FF0000'>La table source ".$currentInstructions['source']." n'existe pas.</span>";
                        if($this->_isPartitioned) {
                            $queryResults['query_begin']    = $queryResults['query_insert'];
                            $queryResults['query_truncate'] = $queryResults['query_insert'];
                            $queryResults['query_commit']   = $queryResults['query_insert'];
                            $queryResults['query_analyze']  = $queryResults['query_insert'];
                        }
                        else {
                            $queryResults['query_delete'] = $queryResults['query_insert'];
                        }
                        $queryResults['execution_time'] = 0;
                        // Et on mémorise les résultats
                        foreach($queryResults as $name => $result) {
                            $computeArray[$level][$index]['queries'][$name.'_result'] = $result;
                        }
                        // La table coorespondante est considérée comme traitée
                        $this->_tablesDone[] = $currentInstructions['target'];
                    }
                }

                // Attente des derniers processus en cours
                $this->waitCalculationProcessesToEnd();

                // Récupération des résultats
                foreach($this->getQueryResults() as $name => $results) {
                    foreach($results as $index => $result) {
                        // On ajoute les résultats de calcul
                        if($name == 'execution_time') {
                            $computeArray[$level][$index]['execution_time'] = $result;
                        }
                        else {
                            $computeArray[$level][$index]['queries'][$name.'_result'] = $result;
                        }
                    }
                }

                // Suppression du fichier temporaire
                //if(file_exists($this->_tempFileComputeResults))
                    unlink($this->_tempFileComputeResults);
            }

            // affichage pour contrôle de l'ensemble des requêtes qui vont être exécutées.
            if($this->debug & 2)
            {
                echo "<b>Ordre d'execution des requ&ecirc;tes</b>";
                echo '<pre>';
                print_r($computeArray);
                echo '</pre>';
            }

            return $computeArray;
	} // End function executeRequest

        /**
         * Fige le script tant en attendant qu'un processus se termine
         */
        public function waitForFreeCalculationSlot()
        {
            $listPidExecuted = array();
            while( count( $listPidExecuted ) == 0 )
            {
                foreach( $this->currentJobs as $table => $oneHandle )
                {
                    $a = proc_get_status( $oneHandle ); // Lecture de l'état du process
                    //
                    // Si le process vient de se terminer
                    if( $a['running'] == false && !in_array( $a['pid'], $listPidExecuted ) )
                    {
                        $listPidExecuted[] = intval( $a['pid'] );
                        unset($this->currentJobs[$table]);
                        $this->_tablesDone[] = $table;
                    }
                }
                usleep( 1000 ); // On attend 1ms avant le prochain test
            }
        }

        /**
         * Fige le script en attendant que els enfants soient terminés
         */
        public function waitCalculationProcessesToEnd()
        {
            $listPidExecuted = array();
            $nbProcesses     = count( $this->currentJobs );
            while( count( $listPidExecuted ) !== $nbProcesses )
            {
                foreach( $this->currentJobs as $table => $oneHandle )
                {
                    $a = proc_get_status( $oneHandle ); // Lecture de l'état du process
                    //
                    // Si le process vient de se terminer
                    if( $a['running'] == false && !in_array( $a['pid'], $listPidExecuted ) )
                    {
                        $listPidExecuted[] = intval( $a['pid'] );
                        unset($this->currentJobs[$table]);
                        $this->_tablesDone[] = $table;
                    }
                }
                usleep( 1000 ); // On attend 1ms avant le prochain test
            }
        }

        /**
         * Indicates if database is partitioned
         * @return boolean
         */
        public function isPartitioned()
        {
            return $this->_isPartitioned;
        }

        /**
         * Calcul d'une requête de compute dans un processus fils
         * @param array $queries
         * @param string index des requêtes courantes
         */
       public function launchProcess(array $queries, $index)
        {
            // 2012/02/08 OJT : bz25900, modification de l'utilisation de la
            // variable d'environnement env_queries. Elle contient le nom d'un
            // fichier plutôt que le tableau des requêtes.
            $serQueriesFile = REP_PHYSIQUE_NIVEAU_0.'upload/envQueriesFile_'.uniqid();
            $serQueries     = serialize( $queries );
            file_put_contents( $serQueriesFile, $serQueries );

            // Lancement du calcul
            $descriptorspec = array();
            $pipes          = array();
            $env            = array("env_queries" => $serQueriesFile,
                                    "env_debug" => $this->debug,
                                    "env_index" => $index,
                                    "env_temp_file" => $this->_tempFileComputeResults);

            $procFile = REP_PHYSIQUE_NIVEAU_0."scripts/compute_calculation.php";
            $h = proc_open("php $procFile", $descriptorspec, $pipes, NULL, $env  );
            return $h;
        }
        
        /**
         * Calcul d'une requête dans le processus courant
         * @param array $queries
         * @param string index des requêtes courantes
         */
        public function launchSeqProcess(array $queries, $index)
        {
            $serQueriesFile = REP_PHYSIQUE_NIVEAU_0.'upload/envQueriesFile_'.uniqid();
            $serQueries     = serialize( $queries );
            file_put_contents( $serQueriesFile, $serQueries );

            $env_queries = $serQueriesFile;
            $env_debug = $this->debug;
            $env_index = $index;
            $env_temp_file = $this->_tempFileComputeResults;

            include REP_PHYSIQUE_NIVEAU_0."scripts/compute_calculation.php";
        }

        /**
         * Retourne les résultats d'éxécution des calculs
         * @return array $queryResults
         */
        function getQueryResults()
        {
            if(!file_exists($this->_tempFileComputeResults))
                return array();

            $file = file($this->_tempFileComputeResults);
            $queryResults = array();
            foreach( $file as $line ) {
                if(empty($line)) continue;
                $t = explode(";",$line );
                $queryResults[$t[1]][$t[0]] = $t[2];
            }
            return $queryResults;
        }

	/**
	 * Retourne le niveau NA et TA source de la table cible
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @param array $table
	 * @return array( 0 => na_source, 1 => ta_source)
         * 
         * 10/10/2011 NSE DE Bypass temporel : maj
	 */
	protected function getSourceNA_TA ( $table )
	{
            $ta_source = $this->family_info['ta_deployed'][$table['ta']][1];
            if ( empty($ta_source) )
                    $ta_source = $table['ta'];
            $na_source = $this->family_info['na'][$table['na']][0];

            // niveau rac_day_bh calcule a partir de sai_day_bh par exemple
            // le chemin est modifie seulement pour le niveau de BH minimum, week_bh et month_bh sont calcules a partir de day_bh (inchange)
            if (getMinTimeBHLevel("bh") == $table['ta'] && $this->family_info['na_min_deployed'] != $table['na']
                                    && getBHParam($this->id_group_table, "bh_network_aggregation") == 'aggregated' )
            {
                $src_na = $na_source; //$table['na'];
                $src_ta = $table['ta'];
                            //__debug(__LINE__);
            }
            else
            {
                // 10/10/2011 NSE DE Bypass temporel 
                // Si la Ta est bypassée pour la famille
                if(TaModel::IsTABypassedForFamily($table['ta'], $this->family_info['family'])){
                    // La Ta source est la Ta de la table courante ($table['ta'])
						  $src_na = $na_source;
                    // La Na source est la Na de niveau inférieur 
                    $src_ta = $table['ta'];
                }                
                // ex : si *_raw_area_hour avec cell comme niveau de net_agreg de base,
                // on revient sur *_raw_cell_hour
                elseif ($ta_source == $table['ta'] && $this->family_info['ta_min_deployed'] == $table['ta']
                                            && $this->family_info['na_min_deployed'] == $table['na'])
                {
                    $src_na = $na_source;
                    $src_ta = $ta_source;
                    //__debug(__LINE__);
                }
                else // sinon on revient au niveau de time_agreg inférieur, sur le même réseau
                {
                    // if ( !empty($this->family_info['ta_min'][$table['ta']][1]) )
                            // $src_ta = $this->family_info['ta_min'][$table['ta']][1];
                    // else
                            // $src_ta = $table['ta'];
                    $src_ta = $ta_source;

                    if ( $this->family_info['ta_min_deployed'] == $table['ta'] )
                        $src_na = $na_source;
                    else
                        $src_na = $table['na'];

                }
            }
            return array($src_na, $src_ta);
	} // End function getSourceNA_TA

	/**
	 * Utilisé pour la génération d'une requête, retourne les champs concernant le "temps" de la requête, en fonction de $times et $period
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @param string $period
	 * @param int $sel
	 * @return string
 	 */
	protected function getTimeFields($period, $sel = 0)
	{
            // BZ 30587 : mise à jour de l'offset day systématique avant d'aller le lire
            // afin de prendre en compte un éventuel changement de jour à ce moment précis
            $this->updateComputeOffset();
            
		switch ($period)
		{
			case 'hour':
				$time_fields = 'hour ';
				switch ($sel)
				{
					case 0:
						if (in_array('day', $this->family_info['ta'])) $time_fields .= ', day ';
						if (in_array('week', $this->family_info['ta'])) $time_fields .= ', week ';
						if (in_array('month', $this->family_info['ta'])) $time_fields .= ', month ';
						break;
					case 1:
                                                // BZ 30587 : utilisation de la valeur day directement
						if (in_array('day', $this->family_info['ta'])) $time_fields .= ', ' . $this->compute_info['day'];
                                                // BZ 30587 : utilisation du day pour calculer le week
						if (in_array('week', $this->family_info['ta'])) $time_fields .= ', ' . Date::getWeekFromDay($this->compute_info['day']);
						if (in_array('month', $this->family_info['ta'])) $time_fields .= ', ' . Date::getMonthFromDatabaseParameters($this->compute_info['offset']);
						break;
					case 2:
						break;
				}
				break;

			case 'day':
				$time_fields = 'day ';
				switch ($sel)
				{
					case 0:
						if (in_array('week', $this->family_info['ta'])) $time_fields .= ', week ';
						if (in_array('month', $this->family_info['ta'])) $time_fields .= ', month ';
						break;
					case 1:
                                                // BZ 30587 : utilisation du day pour calculer le week
						if (in_array('week', $this->family_info['ta'])) $time_fields .= ', ' . Date::getWeekFromDay($this->compute_info['day']);
						if (in_array('month', $this->family_info['ta'])) $time_fields .= ', ' . Date::getMonthFromDatabaseParameters($this->compute_info['offset']);
						break;
					case 2:
						break;
				}
				break;
			case 'week':
				$time_fields = 'week ';
				break;

			case 'month':
				$time_fields = 'month ';
				break;

			case 'day_bh':
				switch ($sel)
				{
					case 0:
						$time_fields = 'day_bh,week_bh,month_bh,bh';
						break;
					case 1:
						$time_fields = 'day,week,month';
						break;
					case 2:
						$time_fields = 'day,week,month';
						break;
				}
				break;

			case 'week_bh':
				$time_fields = 'week_bh,bh';
				break;

			case 'month_bh':
				$time_fields = 'month_bh,bh';
				break;
		}
		return $time_fields;
	} // End getTimeFields

	/**
	* Retourne les champs concernant le temps de la requête, en fonction de $period
	* 12/10/2011 MMT DE BYpass temporel, ajout du parametre Bypass dans getTimeWhere
	* @param string $na
	* @param string $ta
	* @param booelan $ta_bypassed define if the given T&A is bypassed or not
	* @param int $day
	* @param int $week
	* @param int $month
	* @param string $period
	* @param string $type : raw ou kpi
	* @return string
	*/
	protected function getTimeWhere($na, $ta,$ta_bypassed, $hour, $day, $week, $month, $sel = 0, $type = 'raw')
	{
            // 24/05/2011 BBX -PARTITIONING-
            // Mémorisation de la date
		// maj 14/12/2009 - Correction du bug 13325 - Suppression des quotes dans la condition (Erreur SQL)
		switch ($ta)
		{
			case 'hour':
				if ($this->family_info['na_min_deployed'] == $na)
				{
                                        $this->date = $hour;
					$where_clause = " "; //la clause where est vide car on tape dans un table qui ne contient que l'heure à traiter ou les heures du jour à traiter
				}
				else
				{
					if ($this->compute_info['mode'] == 'hourly')
                                        {
						$where_clause = " WHERE hour = ".$hour." ";
                                                $this->date = $hour;

                                        }
                                        else
                                        {
                                                $this->date = $day;
						$where_clause = " WHERE day = ".$day." ";

                                                // 26/05/2011 -PARTITIONING-
                                                // Gestion compute booster
                                                if($this->_isPartitioned && $this->_computeSwitch == 'hourly')
                                                {
                                                    $this->date = $hour;
                                                    $where_clause = " WHERE hour = ".$hour." ";
                                                }
                                        }

                                }
				break;

			case 'day':
            $this->date = $day;
				//12/10/2011 MMT DE BYpass temporel, si on est en Bypass il ne faut pas que la clause soit vide
				if ($this->family_info['na_min_deployed'] == $na && $this->compute_info['categorie'] == 'raw' && !$ta_bypassed)
					$where_clause = " "; //la clause where est vide car on tape dans un table qui ne contient que l'heure à traiter ou les heures du jour à traiter
				else
					$where_clause = " where day = ".$day." ";
				break;

			case 'week':
                                $this->date = $week;
				$where_clause = " where week = ".$week." ";
				break;

			case 'month':
                                $this->date = $month;
				$where_clause = " where month = ".$month." ";
				break;

			case 'day_bh':
                                $this->date = $day;
				if ($this->compute_info['categorie'] == 'raw')
					$where_clause = " where day = ".$day." ";
				else
					$where_clause = " where day_bh = ".$day." ";
				break;

			case 'week_bh':
                                $this->date = $week;
				$where_clause = " where week_bh = ".$week." ";
				break;

			case 'month_bh':
                                $this->date = $month;
				$where_clause = " where month_bh = ".$month." ";
				break;
		}
		return $where_clause;
	} // End function getTimeWhere

	/**
	 * Retourne la clause where spécifique à la BH
	 *
	 * @param string $na
	 * @param string $table_source
	 * @param string $clause_where_initial
	 * @param string $limit
	 * @return string
	 */
	protected function getClauseWhereBH ($na, $table_source, $clause_where_initial, $limit)
	{
            // 30/05/2011 BBX -PARTITIONING-
            // Détermination de la colonne à utiliser pour le calcul de la BH
            $lineId = 'oid';
            if(!$this->_oidsEnabled)
                $lineId = 'ctid';

		$bh_formula = $this->family_info['bh_formula'];
		$clause_where_na = " AND ";

		// maj 08/06/2009 MPR - Pour les familles 3ème axe, on explode les combinaisons na_naAxe3
		if( get_axe3($this->family_info['family']) ){

			$_na = explode('_',$na);

			// Condition sur le 1er axe
			$clause_where_na.= "w.{$_na[0]} = t.{$_na[0]}";

			// Condition sur le 3ème axe
			$clause_where_na.= " AND w.{$_na[1]} = t.{$_na[1]}";

		} else {

			$clause_where_na.= "w.$na=t.$na";

		}


		$clause_where_bh = " and  w.".$lineId." in
							(select t.".$lineId." from $table_source t
							$clause_where_initial $clause_where_na AND $bh_formula IS NOT NULL
							ORDER BY $bh_formula DESC
							LIMIT $limit) ";

		return $clause_where_bh;
	} // End function getClauseWhereBH

	/**
	 * Exécute une requête et retourne le résultat
	 *
	 * @since cb4.0.0.00
	 * @version cb4.1.0.00
	 *
	 * modif 19/11/2008 BBX : utilisation des méthodes fournies par DatabaseConnection
	 *
	 * @param string $query : requête à exécuter
	 * @return ressource
	 */
	protected function sql ( $query )
	{
		// 19/11/2008 BBX : récupération du résultat de la requête depuis l'objet DatabaseConnection avec la méthode get_all. On récupère désormais un tableau de résultat
		$result_array = @$this->database->getAll($query);

		if ( $this->debug & 2 )
		{
			$_ = debug_backtrace();
			$f = null;
			while ( $d = array_pop($_) )
			{
				if ( (strtolower($d['function']) == 'sql') ) break;
				$f = $d;
			}
			// 19/11/2008 BBX : utilisation des méthodes fournies par DatabaseConnection
			echo '<br /><u>$query :</u> <span style="font: 9pt Verdana;color:#585858d">[function : <code>'.$f['class']. '::'.$f['function'].'()</code> - line <code>'.$d['line'].'</code>]</span><br /><pre style="color:#3399ff">'.str_replace(array("<", ">"), array('&lt;','&gt;'), $query).'</pre>';
			if ( $this->database->getLastError() != '' ) echo '<span style="color:red">'.$this->database->getLastError().'</span><br />';
			else echo '<u>num_rows :</u> <code>'.(count($result_array)+$this->database->getAffectedRows()).'</code><br />';
		}

		__debug($result_array,"ARRAY RESULT");
		return $result_array;
	} // End function sql
} // End class
?>
