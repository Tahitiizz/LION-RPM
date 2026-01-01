<?
/*
*	@cb51000@
*
*	28-06-2010 - Copyright Astellia
* 
*	Composant de base version cb_5.1.0.00
*
*	28/06/2010 NSE : Division par zéro - remplacement de l'opérateur / par //
*	15/07/2010 NSE : Suppression de l'opérateur //
*       09:30 18/10/2010 SCT : BZ 18518 => Vérification de la formule KPI avant activation depuis KPI_Builder
*           + appel de la méthode statique "prepareErlangbPregFormula" pour la transformation de la formule
*       12:05 19/10/2010 SCT : BZ 18589 => Blocage du computeKpi
*           + Modification de la variable en entrée ($kpiFormula devient $counter['formula'])
*           + Include du fichier KpiFormula.class.php
 *
* 12/10/2011 MMT DE BYpass temporel, ajout du parametre Bypass dans getTimeWhere, pas de gestion du bypass pour KPI
*				suppression de la table w_edw Bypass si existe
* 26/10/2011 MMT bz 24418 pas de drop de la table bypass si compute booster
 * 13/06/2012 NSE bz 27382 : suppression de la date du nom de la table source si elle est présente
 * 24/07/2012 ACS BZ 28210 Compute booster error at midnight on partitioning T&A
*
*/
?>
<?
/*
*	@cb41000@
*
*	14-11-2008 - Copyright Astellia
*
*	14/11/2008 BBX : Modification du traitement pour gérer le nouveau modèle de topo. Split Axe1 / Axe3
*	19/11/2008 : $this->sql retourne désormais un tableau de données (généré par la méthode getAll de la classe DatabaseConnection)
*	16/12/2009 BBX : Il faut mettre la construction de $where_clause avant l'eval car la variable est nécessaire pour le calcul Erlang. BZ 13473
*	10:09 22/12/2009 SCT : on ajoute 2 variables pour récupérer le 1er axe et le 3ème axe dans la fonction "prepareRequest"
*	03/03/2010 - MPR : Correction du BZ 14263 - Warning présent si aucun kpi pour une famille
*	09/04/2010 NSE  bz 14256 problème quand la formule contient null pour tch_counter : on caste null en real
*	22/04/2010 NSE bz 14713 : regexp trop restrictive modifiée
*/
?>
<?
/*
*	@cb40000@
*
*	28-01-2008 - Copyright Astellia
* 
*	Composant de base version cb_4.0.0.00
*   	- 28-01-2008 SCT : modification du script pour passage en Compute Booster
*/
?>
<?php
/**
 *
 * ******* PRINCIPE DE CONSTRUCTION DES REQUETES DE LA BH *******
 *
 * Exemples de requetes :
 *
 * Exemple de requete BH pour calculer raw_sgsn_day_bh a partir de raw_sgsn_hour (mode standard) :
 *
 * INSERT INTO edw_iu_pagrac_axe1_raw_sgsn_day_bh (sgsn, day_bh,week_bh,month_bh,bh, capture_duration, capture_duration_expected, pip_maxduration, pip_nbpageff1, pip_nbpageff2)
 * SELECT sgsn, day,week,month,hour, sum(capture_duration), ri_calculation('rac','edw_object_3_ref','sgsn',sgsn)::float4*24*3600, max(PIP_MaxDuration), sum(PIP_NbPAGeff1),sum(pip_nbpageff2)
 * FROM edw_iu_pagrac_axe1_raw_sgsn_hour w
 * WHERE day = '20051130'  and  w.oid in (select t.oid from edw_iu_pagrac_axe1_raw_sgsn_hour t  where day = '20051130'  and w.sgsn=t.sgsn ORDER BY pip_nbpageff2 DESC LIMIT 1)
 * GROUP BY sgsn, day,week,month,hour
 *
 * Exemple de requete BH pour calculer raw_rnc_day_bh a partir de raw_sai_day_bh (mode agrege) :
 *
 * INSERT INTO edw_iu_ho_axe1_raw_rnc_day_bh (rnc, day_bh,week_bh,month_bh,bh, capture_duration, capture_duration_expected, mor_nb_cpt_fail_ho_2g)
 * SELECT rnc, day_bh,week_bh,month_bh,null, sum(capture_duration), ri_calculation('sai','edw_object_5_ref','rnc',rnc)::float4*24*3600, sum(MOR_Nb_Cpt_Fail_HO_2G)
 * FROM edw_iu_ho_axe1_raw_sai_day_bh w ,(select distinct sai,rnc from edw_object_5_ref) o
 * WHERE day_bh='20051130'  and w.sai=o.sai
 * GROUP BY rnc, day_bh,week_bh,month_bh
 *
 * Exemple de requete BH pour calculer raw_cluster1_month_bh a partir de raw_cluster1_day_bh (avec bh_parameter > 1) :
 *
 * INSERT INTO edw_iu_roam_axe1_raw_cluster1_month_bh (cluster1, month_bh,bh, capture_duration, capture_duration_expected, riu_att_oth, riu_att_rej)
 * SELECT cluster1, month_bh,null, CASE WHEN 0=1 THEN ri_calculation_sonde('inutile')::float4 ELSE avg(capture_duration) END, 3*5208*3600, avg(RIU_ATT_OTH), avg(RIU_ATT_REJ),
 * FROM edw_iu_roam_axe1_raw_cluster1_day_bh w
 * WHERE month_bh = '200512'  and  w.oid in (select t.oid from edw_iu_roam_axe1_raw_cluster1_day_bh t  where month_bh = '200512'  and w.cluster1=t.cluster1 ORDER BY RIU_ATT_OTH+RIU_ATT_REJ DESC LIMIT 3)
 * GROUP BY cluster1, month_bh
 *
 * Exemple de requete BH pour calculer raw_network_week_bh a partir de raw_network_day_bh (avec bh_parameter = 1) :
 *
 * INSERT INTO edw_iu_pagrac_axe1_raw_network_week_bh (network, week_bh,bh, capture_duration, capture_duration_expected, pip_maxduration, pip_nbpageff1)
 * SELECT network, week_bh,bh, sum(capture_duration), ri_calculation('rac','edw_object_3_ref','network',network)::float4*168*3600, max(PIP_MaxDuration), sum(PIP_NbPAGeff1)
 * FROM edw_iu_pagrac_axe1_raw_network_day_bh w
 * WHERE week_bh='200548'  and  w.oid in (select t.oid from edw_iu_pagrac_axe1_raw_network_day_bh t  WHERE week_bh='200548'  and w.network=t.network ORDER BY PIP_NbPAGeff1 DESC LIMIT 1)
 * GROUP BY network, week_bh,bh
 *
 * ******* FIN DU PRINCIPE DE CONSTRUCTION DES REQUETES DE LA BH *******
 *
 */
include_once REP_PHYSIQUE_NIVEAU_0."class/KpiFormula.class.php";

 class computeKpi extends compute
{
	/**
	* Constructeur de l'objet
	*
	* @since cb4.0.0.00
	* @version cb4.0.0.00	 
	* @param Ressource $database_connection
	* @param int $simulation simulation de l'enregistrement des donnees en bdd
	*/
	public function __construct ()
	{
		parent::__construct('kpi');
	} // End __construct

	/**
	* Récupération des compteurs pour la famille en cours de traitement
	*
	* @since cb4.0.0.00
	* @version cb4.1.0.00
	*	19/11/2008 : $this->sql retourne désormais un tableau de données (généré par la méthode getAll de la classe DatabaseConnection)
	*/	
	public function getCounters ()
	{
		$query = "
				SELECT DISTINCT
					kpi_name AS name, 
					kpi_formula AS formula 
				FROM	
					sys_definition_kpi
				WHERE
					edw_group_table = '".$this->family_info['edw_group_table']."' 
					AND on_off = '1'
                	AND numerator_denominator = 'total' 
                	AND new_field != '1'";
		// maj 03/03/2010 - MPR : Correction du BZ 14263 - Warning présent si aucun kpi pour une famille
		$tab =  $this->sql($query);
		$this->counters[$this->family_info['edw_group_table']] = array();
		if( count($tab) > 0 ){
			foreach( $tab as $row)
			{
				$this->counters[$this->family_info['edw_group_table']][] = $row;					
			}
		}
	} // End function getCounter
	
	/**
	* Création des tables source pour la famille traitée
	*
	* @since cb4.0.0.00
	* @version cb4.0.0.00
	* @param string $period : période à traiter hour ou day (ex: 2007012415 ou 20071224)
	*/
	public function createSourceTables ( $period )
	{
		foreach ( $this->tables[$this->id_group_table] as $index => $table )
		{
                    // Récupère la NA et TA source de la table
                    $na_ta_source = $this->getSourceNA_TA($table);	

                    $table_source = $this->family_info['edw_group_table'].'_'.$this->compute_info['categorie'].'_'.$na_ta_source[0].'_'.$na_ta_source[1];

                    // maj 26/12/2012 - Correction du bz 25284 - Compute Booster KO sur le jour courant sur T&A partitioné
                    // On réécrit la table target qui ne contient plus la ta_value lors de son initialisation
	                // 24/07/2012 ACS BZ 28210 Compute booster error at midnight on partitioning T&A
	                // on mets à jour la 'target' dès que la 'base' est définie. C'est à dire lors d'un compute booster sur le jour courant pour une table partitionnée
	                if (!empty($table['base'])) {
                        $this->tables[$this->id_group_table][$index]['target'] = $table['target'] = $table['base']."_".$period;
                    }
                    
                    // Si la table est base sur le niveau minimum, on va cherche les données dans la temporaire w_*
                    if ( preg_match('/'.$this->family_info['na_min_deployed'].'_'.$this->family_info['ta_min_deployed'].'/', $this->tables[$this->id_group_table][$index]['target'])
                            && !preg_match('/_bh/',$this->tables[$this->id_group_table][$index]['target']) )
                    {
                            // on redefinit la variable $table_source calculee juste au dessus car une table source w_edw_...kpi...ddmmyy n'existe pas, seules les tables w_edw_...raw...ddmmyy existent
                            $table_source = $this->family_info['edw_group_table'].'_raw_'.$na_ta_source[0].'_'.$na_ta_source[1];
                            $table_source = "w_" . $table_source . "_" . $period;
                    }
                    else
                    {
                            // Pour les KPIs la table source est = à la table cible mais on remplace kpi par raw pour les NA qui ne s'agrègenet pas sur le niveau minimum.
                            $table_source = preg_replace("/_kpi_/", "_raw_", $this->tables[$this->id_group_table][$index]['target']);
                            // 13/06/2012 NSE bz 27382 : on récupère la TA
                            if(preg_match('/_[0-9]{8,10}$/',$table_source))
                                $period = substr ($table_source, strrpos($table_source,'_')+1);
                    }
			
                    // 25/05/2011 BBX : -PARTITIONING-
                    if( $this->_isPartitioned )
                    { 
                        // Compute switch
                        $newPeriod = $period;
                        
                        // BZ 30587 : Lecture de l'offset day depuis la base de donnée
                        // Afin d'obtenir la valeur insérée lors du compute launcher
                        $this->searchOffsetDay(); 
                        if( $this->compute_info['offset'] != 0 )
                        {
                            $tableParts = explode('_',$table['target']);
                            $newPeriod = $tableParts[count($tableParts)-1];
                        }  
                        // BZ 30587 : Mise à jour de l'offset day dans l'objet 
                        // afin de correspondre a la réalité
                        $this->updateComputeOffset();
                        
                        // Si on est en compute switch, on ajoute les tables horaires
		                // 24/07/2012 ACS BZ 28210 Compute booster error at midnight on partitioning T&A
    		            // on mets à jour la période source dès lors qu'elle concerne une table "hour"
                        if($table['ta'] == 'hour' )
                        {
                            // 19/10/2012 BBX
                            // BZ 29806 : Sur un produit Daily, si la table source est une table temporaire
                            // Alors il faut prendre la table temporaire contenant toutes les heures
                            if(!$this->_productModel->isHourly() && (strncmp($table_source,'w_edw', 5) == 0)) {
                                $newPeriod = substr($newPeriod,0,8);
                            }
                            $table_source = preg_replace('/[0-9]+$/',$newPeriod,$table_source);
                        }
                        else
                        {
                            // 25/05/2011 BBX : -PARTITIONING-
                            // La table source sera une partition lorsque c'est possible
                            // maj 28/12/2011 - MPR la variable $ta_source n'existe pas, utilisation de $na_ta_source[1]
                            if( ( $na_ta_source[1] == $table['ta']) && (strncmp($table_source,'w_edw', 5) != 0) && $period != $newPeriod )
                            {  
                                // 13/06/2012 NSE bz 27382 : suppression de la date du nom de la table source si elle est présente
                                if(preg_match('/_[0-9]{8,10}$/',$table_source))
                                    $table_source = substr ($table_source, 0, strrpos($table_source,'_'));
                                $partition = new Partition($table_source, $newPeriod, $this->database);
                                $table_source = $partition->getName();
                            }
                        }
                    }
			
                    $this->tables[$this->id_group_table][$index]['source'] = $table_source;
                    $this->tables[$this->id_group_table][$index]['na_source'] = $na_ta_source[0];
                    $this->tables[$this->id_group_table][$index]['ta_source'] = $na_ta_source[1];
                    
		}
		
		if ( $this->debug & 2 )
		{
			echo '<b>Tables cibles<->sources</b><pre>'.print_r($this->tables[$this->id_group_table], 1).'</pre>';
		}
	} // End function createSourceTables
		
	/**
	* Prépare les requêtes à exécuter
	*
	*
	* Cas des valeur des variables "aggreg_net_ri" et "network" (ces variables servent pour les bybass) 
	*
	* Si on est sur une aggrégation réseau 2ieme axe:
	*	aggreg_net_ri = 1
	*	network = prend la valeur de l'aggrégation réseau du 2ieme axe
	* 
	* Si on est sur une aggrégation réseau 3ieme axe:
	* 	aggreg_net_ri = 2
	* 	network = prend la valeur de l'aggrégation réseau du 3ieme axe
	* 
	* Si on est sur une aggrégation temporelle, on garde les valeurs par défaut soit:
	* 	aggreg_net_ri = 0
	* 	network = aggrégation réseau "général" (i.e.: si on est sur une famille troisième axe, on a l'aggrégation entière axe2_axe3)
	* 
	* ATTENTION : ces variables doivent toujours être initialisé et ne doivent pas changer de nom
	*
	*
	* @since cb4.0.0.00
	* @version cb4.0.0.00
	* @param string $period : période à traiter hour ou day (ex: 2007012415 ou 20071224)
	*/
	public function prepareRequest ( $period )
	{
		$queries = array();
                
                // BZ 30587 : mise à jour de l'offset day systématique avant d'aller le lire
                // afin de prendre en compte un éventuel changement de jour à ce moment précis.
                // De plus, on utilise la classe Date.
                $this->updateComputeOffset();                
                $untilweek  = Date::getWeekFromDay($this->compute_info['day']);
                $untilmonth = Date::getMonthFromDatabaseParameters($this->compute_info['offset']);
                $untilday   = $this->compute_info['day'];
                $untilhour  = $period;

		foreach ( $this->tables[$this->id_group_table] as $index => $table )
		{
			$table_source = $table['source'];
			$table_target = $table['target'];
			$na = $table['na'];
			$ta = $table['ta'];
			$na_source = $table['na_source'];
			$ta_source = $table['ta_source'];   
			
			// Pour le module capacity planning, il faut la variable $network et $time
			$time = $ta;
			$network = $na;
			// 10:09 22/12/2009 SCT : on ajoute 2 variables pour récupérer le 1er axe et le 3ème axe
			$tempNa = explode('_', $network);
			$network1stAxis = $tempNa[0];
			$network3rdAxis = $tempNa[1];
			unset($tempNa);
        
			$listFields = array();
			$listFormules = array();	
			
                        // maj 28/12/2011 - MPR : Correction du bz 25309
                        // T&A Partitioné / Compute Booster 
                        // La condition sur la TA(hour) n'est pas bonne
                        // En effet, en compute booster la period est le day et non l'heure
                        // Il faut donc récupéré l'heure depuis la table cible
                        if( $this->_isPartitioned && $table['ta'] == 'hour' )
                        {
                            // Réinitialisation de l'heure
                            $tableParts = explode('_',$table['target']);
                            $untilhour = $tableParts[count($tableParts)-1];
                        }
                        
			// construction de la condition temporelle
			// 16/12/2009 BBX
			// Il faut mettre la construction de $where_clause avant l'eval car la variable est nécessaire
			// pour le calcul Erlang. BZ 13473
			// 12/10/2011 MMT DE BYpass temporel, ajout du parametre Bypass, pas de gestion du bypass pour KPI
			$where_clause = $this->getTimeWhere($na, $ta,false, $untilhour, $untilday, $untilweek, $untilmonth);
			$where_clause = (trim($where_clause) == '') ? '' : $where_clause;
			
                        // 17/03/2011 MPR/BBX -PARTITIONING-
                        // On insère directement les données dans la partition et non dans la table mère
                        if($this->_isPartitioned)
                        {
                            $this->tables[$this->id_group_table][$index]['query_begin']     = "BEGIN";
                            $this->tables[$this->id_group_table][$index]['query_commit']    = "COMMIT";
                            $this->tables[$this->id_group_table][$index]['query_truncate']  = "TRUNCATE $table_target";
                            $this->tables[$this->id_group_table][$index]['query_analyze']   = "ANALYZE {$table_target}";
                            $this->tables[$this->id_group_table][$index]['target']          = $table_target;
                        }

			//on evalue les fonctions d'aggregation au cas où les fonctions sont tapées en dur et contiennnent des variables (utilisé par le module capacity planning notamment)
			foreach ( $this->counters[$this->family_info['edw_group_table']] as $counter )
			{
				// 09/04/2010 NSE bz 14256 problème quand la formule contient null pour tch_counter
				// on caste null en real
				// 22/04/2010 NSE bz 14713 : regexp trop restrictive modifiée
                                // 09:30 18/10/2010 SCT : BZ 18518 => Vérification de la formule KPI avant activation depuis KPI_Builder
                                //  + appel de la méthode statique "prepareErlangbPregFormula" pour la transformation de la formule
                                // 12:05 19/10/2010 SCT : BZ 18589 => Modification de la variable en entrée ($kpiFormula devient $counter['formula'])
				$counter['formula'] = KpiFormula::prepareErlangbPregFormula($counter['formula']);
				eval('$formula = "'.$counter['formula'].'";');
				$listFields[] = $counter['name'];
				$listFormules[] = $formula;
			}
			
			if ( count($listFields) > 0 )
				$fields = implode(", ", $listFields);		
                        
                        // 23/03/2012 BBX
                        // BZ 26374 : Ajout de parenthèses autour des formules 
                        // afin d'isoler les écritures contenant par exemple SELECT
			if ( count($listFormules) > 0 )
				$values = "(" . implode("), (", $listFormules) . ")";
				
			// 14/11/2008 BBX : On split la NA pour séparer 1er axe et 3ème axe
			list($first_axis_part,$third_axis_part) = explode('_',$na);
			$na_list = $first_axis_part;
			if($third_axis_part != '') $na_list .= ','.$third_axis_part;

			// construction des champs temporels
           	$time_fields = $this->getTimeFields($ta);
			
                        // 01/08/2012 BBX
                        // BZ 28325 : Jointure avec la topologie
                        $whereTopo = "";
                        if(strncmp($table_source,'w_edw', 5) == 0)
                        {
                            list($naSource) = explode('_',$table['na_source']);
                            $operator = (trim($where_clause) == "") ? " WHERE" : " AND";
                            $whereTopo = "{$operator} {$naSource} IN (SELECT eor_id FROM edw_object_ref WHERE eor_obj_type = '$naSource' AND eor_on_off = 1)";
                        }

			// 14/11/2008 BBX : Utilisation de le variable $na_list au lieu de $na.
			$query =             
            	"INSERT INTO ".$table_target." (" . $na_list . ", " . $time_fields . ", " . $fields . ")
                SELECT " . $na_list . ", " . $time_fields . ", " . $values . "
                FROM ".$table_source." 
                        ".$where_clause.$whereTopo;

			if ( count($fields) == 0 )
				$query = "";
			
			$this->tables[$this->id_group_table][$index]['query_insert'] = $query;
		} // End foreach
		
		
		if ( $this->debug )
		{
			echo '<b>Liste des requêtes à exécuter : </b><pre>'.print_r($this->tables[$this->id_group_table], 1).'</pre>';
		}
	} // End function prepareRequest
	
	/**
	* Post-traitement : nettoyage des compteurs dans les tables et suppression des tables w_edw
	*
	* @since cb4.0.0.00
	* @version cb4.0.0.00
	* @param array $listeFamilleInfo : tableau contenant les id_group_table des familles et les informations de chaque famille venant de la méthode searchFamilyInfo
	* @param array $listComputePeriod : tableau contenant les dates ou les heures qui viennent d'être traitées donc qui peuvent être supprimées
	* @param array $listeSelfAgregationLevel : tableau ayant pour index id_group_table et contenant des array listant les NA s'agrégeant sur elle-mêmes
	*/
	public function afterProcess ($listeFamilleInfo, $listComputePeriod, $listeSelfAgregationLevel)
	{
		// quand le débug est = 3 on n'exécute pas les requêtes du compute.
		if ( $this->debug == 3 )
		{
			displayInDemon("Pas d'exécution des requêtes de drop des tables edw_... dans ce mode de debug.");
		}
		else
		{
			/*
				Si on a changé de compute_mode hourly > daily, on doit supprimer les tables w_edw...hour... concernées par le compute.
				On vat chercher la liste des heures dans la table sys_global_parameters.
			*/
			// si il y a eu un switch hourly > daily
			if ( $this->_computeSwitch == 'hourly' )
			{
				$liste_hours = 	get_sys_global_parameters('hour_to_compute');
				$separateur = 	get_sys_global_parameters('sep_axe3');
				$tab_liste_hours = explode($separateur,$liste_hours);
				foreach ($tab_liste_hours as $h)
					$listComputePeriod[] = $h;
			}
			
			// boucle sur chaque famille pour le nettoyage des tables
			foreach ( $listeFamilleInfo as $id_group_table => $tableau_info_famille )
			{
				// suppression des tables temporaires (w_edw_...)
				$this->drop_table_temp($id_group_table, $tableau_info_famille, $listComputePeriod, $listeSelfAgregationLevel[$id_group_table]);
				
				// note christophe 26/02/08 : à voir si on intègre ici le nettoyage de l'historique ou sinon on le laisse dans clean_history.
				// nettoyage de l'historique
				/*
				$deploy = new deploy($this->database_connection, $id_group_table);
				if ( $this->debug & 2 )
				{
					echo '<b>$deploy->clean_tables</b><br>';
				}        
				$deploy->clean_tables();
				*/
			}
		}
	} // End function afterProcess

	/**
	* suppression des tables temporaires "w_edw_..."
	*
	* @since cb4.0.0.00
	* @version cb4.0.0.00
	* @param int $id_group_table : identifiant du group_table de la famille en cours
	* @param array $tableau_info_famille['family'] = le nom de la famille (paas vraiment utile dans cette fonction);
	* 									 ['edw_group_table'] = le nom des tables;
	* @param array $listComputePeriod : tableau contenant les dates ou les heures qui viennent d'être traitées donc qui peuvent être supprimées
	* @param array $listeSelfAgregationLevel : tableau  contenant des array listant les NA s'agrégeant sur elle-mêmes
	*/
	function drop_table_temp ($id_group_table, $tableau_info_famille, $listComputePeriod, $listeSelfAgregationLevel)
    {
        $group = $tableau_info_famille['edw_group_table'];
        if ( $this->debug & 2 )
		{
			displayInDemon('<b>Nettoyage des tables w_edw...</b><br>');
		}
        foreach ($listeSelfAgregationLevel AS $level)
        {
            foreach ( $listComputePeriod as $period )
			{

				// 17/10/2011 MMT DE Bypass temporel, suppression de la table Bypass si existe
				// 26/10/2011 MMT bz 24418 pas de drop de la table bypass si compute booster
				$table_temp_daily_bypass = "w_" . $group . "_raw_" . $level . "_bypass_day_" . $period;
				if(isset($this->existing_tables[$table_temp_daily_bypass])){
					displayInDemon("<li>Drop de la table bypass " . $table_temp_daily_bypass."</li>");
					postgres_drop_table($table_temp_daily_bypass);
				}
				
            $table_temp = "w_" . $group . "_raw_" . $level . "_" . $tableau_info_famille['ta_min_deployed'] . "_" . $period;

				displayInDemon("<li>Drop de la table " . $table_temp."</li>");
	            postgres_drop_table($table_temp);

			}
        }
    }
} // End class computeRaw
?>