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
*       29/07/2010 BBX : Ajout d'une condition NOT NULL sur les axes d'investigation 1er axe et 3ème axe. BZ 14266
*       06/01/2010 MPR : Correction du bz19972 - le calcul du RI peut se baser sur le 3ème axe
 * 10/10/2011 NSE DE Bypass temporel
 * 24/07/2012 ACS BZ 28210 Compute booster error at midnight on partitioning T&A
*/
?>
<?
/*
*	@cb41000@
*
*	14-11-2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	- 14/11/2008 BBX : Gestion du nouveau modèle de topologie + Nouveau calcul du RI
*		2 nouvelles méthodes : getAgregPath et generateRiQuery
*	- 19/11/2008 : $this->sql retourne désormais un tableau de données (généré par la méthode getAll de la classe DatabaseConnection)
*
* 06/03/2009 SCT : bug 8906 => capture_duration et capture_duration_real supérieurs à 3600 pour le niveau minimum quand plusieurs sondes
*       - ajout d'un paramètre supplémentaire pour les conditions dans les formules de compteurs => $integration_level = 3;
*
*	14/12/2009 GHX
*		- Correction du BZ 13340 [REC][MIXED-KPI][TC#51659] : Bug sur la reprise de données
*       20/01/2011 MPR : Correction du bz20246 : Se réferer aux commentaires du bz19972 (merge 5.1.1 vers 5.0.4)
 *
 * 03/02/2011 MMT
 *		- Correction Bz 20443 utilise edw_object_arc_ref  à la place de edw_object_arc pour le BH aggregated
 * 25/11/2013 GFS : BZ#31570 - [SUP][TA GSM][AVP 32337][Emtel Ile Maurice] : BH doesn't work in agregated mode
*/
?>
<?
/*
*	@cb40000@
*
*	28-01-2008 - Copyright Astellia
*
*	Composant de base version cb_4.0.0.00
*   - 28-01-2008 SCT : modification du script pour passage en Compute Booster
*
*	- maj 17/07/2008 BBX : passage de la na au lieu de la TA dans getClauseWhereBH. BZ 7148
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
class computeRaw extends compute
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
		parent::__construct('raw');
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
						edw_target_field_name AS name,
						edw_agregation_formula AS formula,
						edw_agregation_function AS agreg
				FROM
					sys_field_reference
				WHERE
					edw_group_table = '".$this->family_info['edw_group_table']."'
					AND on_off='1'
					AND new_field != '1'";
		foreach($this->sql($query) as $row)
		{
			$this->counters[$this->family_info['edw_group_table']][] = $row;
		}

	} // End function getCounter

	/**
	 * Création des tables source pour la famille traitée
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 * @param string $period : période à traiter hour ou day (ex: 2007012415 ou 20071224)
         * 
         * 10/10/2011 NSE DE Bypass temporel : maj
	 */
	public function createSourceTables ( $period )
	{	
            foreach ( $this->tables[$this->id_group_table] as $index => $table )
            {
                
                // Récupère la NA et TA source de la table
                $na_ta_source = $this->getSourceNA_TA($table);
                $na_source = $na_ta_source[0];
                $ta_source = $na_ta_source[1];
                
                // maj 26/12/2012 - Correction du bz 25284 - Compute Booster KO sur le jour courant
                // On réécrit la table target qui ne contient plus la ta_value lors de son initialisation
                // 24/07/2012 ACS BZ 28210 Compute booster error at midnight on partitioning T&A
                // on mets à jour la 'target' dès que la 'base' est définie. C'est à dire lors d'un compute booster sur le jour courant pour une table partitionnée
                if (!empty($table['base'])) {
                    $this->tables[$this->id_group_table][$index]['target'] = $table['base']."_".$period;
                }
                
                // 12/10/2011 MMT DE BYpass temporel enregistre si la TA cible est bypassé
                $ta_bypassed = TaModel::IsTABypassedForFamily($table['ta'], $this->family_info['family']);

                // Table source, cas standard
                $table_source = $this->family_info['edw_group_table'].'_'.$this->compute_info['categorie'].'_'.$na_source.'_'.$ta_source;

                // Si la table est base sur le niveau minimum, on va cherche les données dans la temporaire w_*

                // 12/10/2011 MMT DE BYpass temporel si Bypass, on utilsie la table temporaire w_edw_%bypass_day
                // uniquement si NA&TA table cible = NA&TA table source
                 if($ta_bypassed){
                         $useTempTable = ($table['ta'] == $ta_source && $table['na'] == $na_source);
                 } else {
                         //sinon la table temporaire est utilisée si NA et TA sont les niveaux min absolus
                         $useTempTable = $this->family_info['ta_min_deployed'] == $ta_source && $this->family_info['na_min_deployed'] == $na_source;
                 }

                if($useTempTable){
                     // 12/10/2011 MMT DE BYpass temporel la table bypass à un nom spécifique
                     if($ta_bypassed){
                        $table_source = $this->family_info['edw_group_table']
                                        .'_'.$this->compute_info['categorie']
                                        .'_'.$na_source
                                        .($ta_bypassed?'_bypass':'')
                                        .'_'.$ta_source;
                    }
                    $table_source = "w_" . $table_source . "_" . $period;
                }

                // 25/05/2011 BBX : -PARTITIONING-
                if($this->_isPartitioned)
                {
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

                    // 25/05/2011 BBX : -PARTITIONING-
                    // La table source sera une partition lorsque c'est possible
                    if( ($ta_source == $table['ta']) && (strncmp($table_source,'w_edw', 5) != 0) )
                    {
                        $partition =  new Partition($table_source, $newPeriod, $this->database);
                        $table_source = $partition->getName();
                    } 

	            // 24/07/2012 ACS BZ 28210 Compute booster error at midnight on partitioning T&A
   		    // on mets à jour la période source dès lors qu'elle concerne une table "hour"
                    elseif ($table['ta'] == 'hour' ) 
                    {
                        // 19/10/2012 BBX
                        // BZ 29806 : Sur un produit Daily, si la table source est une table temporaire
                        // Alors il faut prendre la table temporaire contenant toutes les heures
                        if(!$this->_productModel->isHourly()) {
                            $newPeriod = substr($newPeriod,0,8);
                        }
			$table_source = preg_replace('/[0-9]+$/', $newPeriod, $table_source);
                    }
                    //}
                }

                $this->tables[$this->id_group_table][$index]['source'] = $table_source;
                $this->tables[$this->id_group_table][$index]['na_source'] = $na_source;
                $this->tables[$this->id_group_table][$index]['ta_source'] = $ta_source;

            }

            if ( $this->debug & 2 )
            {
                echo '<b>Tables cibles<->sources</b><pre>'.print_r($this->tables[$this->id_group_table], 1).'</pre>';
            }
	} // End function createSourceTables

	/**
	 * Retourne le chemin d'agrégation d'un niveau
	*
	* BBX 13/11/2008
	* @since cb4.1.0.00
	* @version cb4.1.0.00
	* @param string $family : famille
	* @param string $level : niveau dont on veut connaître le chemin jusqu'au
	* @return array : tableau contenant les niveaux d'agrégations depuis $level jusqu'au niveau minimum
	*/
	public function getAgregPath($family,$level)
	{
		// Si c'est la première fois qu'on demande le chemin pour cet élément
		$family_min_net = $this->family_info['na_min'];
		if(!isset($this->agregPathArray[$family][$level])) {
			// Récupération du chemin
			$query = "SELECT * FROM get_path('{$level}','{$family_min_net}','{$family}');";
			$array_result = Array();
			foreach($this->sql($query) as $array) {
				// 29/07/2009 BBX : récupération de la valeur de manière associative
				$array_result[] = $array['get_path'];
			}
			// Sauvegarde du résultat dans l'objet pour éviter de rééxécuter les requêtes si on cherche de nouveau les mêmes informations
			$this->agregPathArray[$family][$level] = array_reverse($array_result);
		}
		return $this->agregPathArray[$family][$level];
	}

	/**
	 * Génération de la requête de calcul du RI
	*
	* BBX 13/11/2008
	* @since cb4.1.0.00
	* @version cb4.1.0.00
	* @param string $family : famille
	* @param string $agreg_cible: niveau sur lequel on se trouve
	* @param string $link_table_object : alias de la table à laquelle l'élément appartient. Nécessaire pour certains cas afin d'éviter les ambiguités
	* @param string $table_source : table source (alias de la table à laquelle l'élément appartient. Nécessaire pour certains cas afin d'éviter les ambiguités)
        * @return string : requête de calcul du ri
    *
    * 14/12/2011 BBX
    * BZ 25140 : correction des paramètres et du préfixe de colonne
	* ACS BZ 27383 No data inserted after Compute
	*/
	public function generateRiQuery($agreg_cible, $link_table_object, $link_table_object_third_axis)
	{
		// 1) Récupération de la famille
		$family = $this->family_info['family'];

                // maj 06/01/2010 : Correction du bz19972 - le calcul du RI peut se baser sur le 3ème axe
                // 2) Récupération de l'axe sur lequel on compte le nombre d'éléments réseau
                $networkAxeRi = $this->getNetworkAxeRi($family);

                // 3) L'alias est différent en fonction de l'axe sur lequel sont basés les éléments réseau
                $linkTable = ($networkAxeRi == 3 ) ? $link_table_object_third_axis : $link_table_object;

		// 4) Explode de $agreg_cible pour être certain d'avoir un élément premier axe ou 3ème axe
		list($first_axis_part,$third_axis_part) = explode('_',$agreg_cible);
		$agreg_cible = ( $networkAxeRi == 3 ) ? $third_axis_part : $first_axis_part;

		// 5) Explode du niveau minimum pour ne conserver que la partie 1er axe ou 3ème axe
		$family_min_net = $this->family_info['na_min'];
		list($first_axis_part,$third_axis_part) = explode('_',$family_min_net);

                $family_min_net =  ( $networkAxeRi == 3 ) ? $third_axis_part : $first_axis_part;
		// 6) Est-on sur le niveau minimum ?
		if($agreg_cible == $family_min_net)
                {
			return 1;
		}
		else
                {
			// 7) Récupération des chemins d'agrégation
			$array_levels = $this->getAgregPath($family,$agreg_cible);
			// 8) Construction de la requête
			// select
			$select_ri = "SELECT count(e0.eoar_id) AS number";
			// from
			$j = 0;
			for($i = 0; $i < (count($array_levels)-1); $i++) {
				$from_ri .= (($from_ri == "") ? " FROM " : ",")."edw_object_arc_ref e{$i} ";
				$j++;
			}
			// jointures
			for($i = 0; $i < ($j-1); $i++) {
				$id_parent = $i+1;
				$where_ri .= (($where_ri == "" ? " WHERE " : " AND "))."e{$i}.eoar_id_parent = e{$id_parent}.eoar_id";
			}
			// arcs
			for($i = 0; $i < ($j); $i++) {
				$id_parent = $i+1;
				$where_ri .= (($where_ri == "" ? " WHERE " : " AND "))."e{$i}.eoar_arc_type = '".$array_levels[$i]."|s|".$array_levels[$id_parent]."'";
			}
			// condition sur l'element
			$where_ri .= " AND e".($j-1).".eoar_id_parent = {$linkTable}{$agreg_cible}";
			$query_ri = $select_ri.$from_ri.$where_ri;
		}
		// 9) Retour de la requête
		return $query_ri;
	}


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

		// >>>>>>>>>>
		// Récupère les infos lié au RI
		// ATTENTION : ne pas changer le nom des variables car elles sont présentes directement dans la formule des raw su RI !
		$time_expected_ri 	= get_sys_global_parameters("capture_duration"); //Recupere la duree de capture
		$nb_sondes_ri 		= get_sys_global_parameters("nb_sources_expected"); //récupère le nombre de sondes attendues
		$min_net_ri 		= "'" . $this->family_info['na_min_deployed'] . "'"; //niveau minimum deployé
		$axe3_ri 		= get_axe3_information_from_gt($this->id_group_table);
		$family_ri 		= $axe3_ri["family"];
		$table_object_ri 	= "'" . get_object_ref_from_family($family_ri) . "'"; //nom de la table object_ref
		// <<<<<<<<<<

		foreach ( $this->tables[$this->id_group_table] as $index => $table )
		{
			$table_source = $table['source'];
			$table_target = $table['target'];
			$na = $table['na'];
			$ta = $table['ta'];
			$na_source = $table['na_source'];
			$ta_source = $table['ta_source'];

			// 12/10/2011 MMT DE BYpass temporel enregistre si la ta est bypassé
			$ta_bypassed = TaModel::IsTABypassedForFamily($ta, $this->family_info['family']);

			// le test sur la ta min est soit le TA min soit le TA bypassé si existe
			$ta_min_tu_use = $this->family_info['ta_min_deployed'];
			if($ta_bypassed){
				$ta_min_tu_use = $ta;
			}

			// Ne pas changer, c'est utilisé dans le calcul du RI.
			$network = $na;

			// >>>>>>>>>>
			// Récupère les infos lié au RI
			// ATTENTION : ne pas changer le nom des variables
			$network_ri = "'" . $na . "'";
                        $network_ri_dynamic = $na;

			switch ( $ta )
			{
				case 'hour':
					$time_coeff = 1;
					$aggreg_net_ri = 1; //precise que les calculs qui sont fait sont des aggregation réseau (la source et la cible ayant le meme time aggregation)
					$hour_ri = "hour"; //nom de la colonne qui sert dans la fonction CASE WHEN $aggreg_net_ri=1 THEN ri_calculation_sonde($hour_ri)::float4 ELSE SUM(capture_duration) END présent dans sys_field_reference
					$integration_level = 3; //06/03/2009 SCT : bug 8906 => capture_duration et capture_duration_real supérieurs à 3600 pour le niveau minimum quand plusieurs sondes
					break;

				case 'day':
				case 'day_bh':
					$time_coeff = 24;
					$aggreg_net_ri = 0;
					//17/10/2011 MMT De Bypass $aggreg_net_ri passe à 1 en Bypass Day
					if($ta_bypassed){
						$aggreg_net_ri = 1;
					}
					$hour_ri = "'inutile'"; //nom de la colonne qui sert dans la fonction CASE WHEN $aggreg_net_ri=1 THEN ri_calculation_sonde($hour_ri)::float4 ELSE SUM(capture_duration) END présent dans sys_field_reference
					// cette variable ne sert que lorsque $aggreg_net_ri=1. Toutefois, il faut l'initiliser à une valeur car même si le CASE WHEN passe via le ELSE ce qui est présent dans le THEN est vérifié lors de la préparartion de la requete par Postgresql
					$integration_level = 3; //06/03/2009 SCT : bug 8906 => capture_duration et capture_duration_real supérieurs à 3600 pour le niveau minimum quand plusieurs sondes
					break;

				case 'week':
				case 'week_bh':
					$time_coeff = 24 * 7;
					$aggreg_net_ri = 0;
					$hour_ri = "'inutile'"; //nom de la colonne qui sert dans la fonction CASE WHEN $aggreg_net_ri=1 THEN ri_calculation_sonde($hour_ri)::float4 ELSE SUM(capture_duration) END présent dans sys_field_reference
					// cette variable ne sert que lorsque $aggreg_net_ri=1. Toutefois, il faut l'initiliser à une valeur car même si le CASE WHEN passe via le ELSE ce qui est présent dans le THEN est vérifié lors de la préparartion de la requete par Postgresql
					$integration_level = 3; //06/03/2009 SCT : bug 8906 => capture_duration et capture_duration_real supérieurs à 3600 pour le niveau minimum quand plusieurs sondes
					break;

				case 'month':
				case 'month_bh':
					// 17/11/2008 BBX : correction de la récupération du nombre de jours dans le mois
					//$nbre_jour = date("t", mktime(0, 0, 0, substr($period, 4, 2), substr($period, -2), substr($period, 0, 4)));
					//$time_coeff = 24 * 7 * $nbre_jour;
					$nbre_jour = date("t", strtotime(substr($period,0,8)));
					$time_coeff = 24 * $nbre_jour;
					$aggreg_net_ri = 0;
					$hour_ri = "'inutile'"; //nom de la colonne qui sert dans la fonction CASE WHEN $aggreg_net_ri=1 THEN ri_calculation_sonde($hour_ri)::float4 ELSE SUM(capture_duration) END présent dans sys_field_reference
					// cette variable ne sert que lorsque $aggreg_net_ri=1. Toutefois, il faut l'initiliser à une valeur car même si le CASE WHEN passe via le ELSE ce qui est présent dans le THEN est vérifié lors de la préparartion de la requete par Postgresql
					$integration_level = 3; //06/03/2009 SCT : bug 8906 => capture_duration et capture_duration_real supérieurs à 3600 pour le niveau minimum quand plusieurs sondes
					break;
			} // switch
			// <<<<<<<<<<

			// >>>>>>>>>>
			// Permet avoir une distinction entre au niveau des aggrégation réseaux d'axe 2 et d'axe 3
			$axes_sources = explode('_', $na_source);
			if ( count($axes_sources) > 1 ) {
				$axes_cibles = explode('_', $na);
				if ( $axes_sources[0] != $axes_cibles[0] ) {
					$aggreg_net_ri = 1;
					$network = $axes_cibles[0];
					$integration_level = 3; //06/03/2009 SCT : bug 8906 => capture_duration et capture_duration_real supérieurs à 3600 pour le niveau minimum quand plusieurs sondes
				}
				elseif ( $axes_sources[1] !=  $axes_cibles[1] ) { // agrégation sur le 3ieme axe
					$aggreg_net_ri = 2;
					$network = $axes_cibles[1];
					$integration_level = 3; //06/03/2009 SCT : bug 8906 => capture_duration et capture_duration_real supérieurs à 3600 pour le niveau minimum quand plusieurs sondes
				}
			}
			// <<<<<<<<<<

			//displayInDemon("MMT ta $ta   ta_bypassed $ta_bypassed  ta_source    $ta_source    aggreg_net_ri $aggreg_net_ri   network  $network   na_source $na_source  ta_min_tu_use  $ta_min_tu_use  axes_sources $axes_sources");

			// 0, 1 et 2 servent à pouvoir selectionner differentes valeurs suivant si on est dans l'insert, le select ou le group by
			$ta_fields = $this->getTimeFields($ta, 0);
			$ta_fields_sel = $this->getTimeFields($ta, 1);
			$ta_fields_group_by = $this->getTimeFields($ta, 2);
			$agreg_cible = $na;

			// 12/10/2011 MMT DE BYpass temporel utilise le $ta_min_tu_use
			if ( $ta == $ta_min_tu_use )
				$agreg_source = $na_source;
			else
				$agreg_source = $na;

			// Déclaration des variables de construction de la requête
			$where = '';
			$link_table_object = '';
			$link_table_object_third_axis = '';

			// ici je rajoute a $source la jointure si la cible n'est pas une table _temp
			// 12/10/2011 MMT DE BYpass temporel en bypass on utilise les jointures sur la topologie sur tous les niveaux > min
			if ($ta == $ta_min_tu_use && $agreg_cible != $agreg_source && ($agreg_source != $this->family_info['na_min_deployed'] ||$ta_bypassed ))
			{
				// On récupère la partie 1er axe et 3ème axe (si dispo) des éléments
				list($subquery_source,$subquery_source_third_axis) = explode('_',$agreg_source);
				list($subquery_cible,$subquery_cible_third_axis) = explode('_',$agreg_cible);
				// 13/11/2008 BBX : modification de la sous-requête pour s'intégrer à la nouvelle structure de topologie
				/*
				$axe3_info = get_axe3_information_from_gt($this->id_group_table);
				$family = $axe3_info['family'];
				$table_object = get_object_ref_from_family($family);
				// On split également sur l'underscore pour ne récupérer que la partie 3ème axe
				$table_source .= " ,(SELECT DISTINCT $agreg_source,$agreg_cible FROM $table_object) b ";
				*/
				// Si le premier axe cible est égal au premier axe source, on joint sur le 3ème axe
				if($subquery_cible == $subquery_source)
				{
					// 13/11/2008 BBX : modification de la clause where
					$link_table_object_third_axis = "b.";
					$where = " AND b.$subquery_source_third_axis = $table_source.$subquery_source_third_axis ";
					$table_source .= " ,(SELECT eoar_id AS {$subquery_source_third_axis}, eoar_id_parent AS {$subquery_cible_third_axis} FROM edw_object_arc_ref WHERE eoar_arc_type = '{$subquery_source_third_axis}|s|{$subquery_cible_third_axis}') b ";
				}
				else
				{
					// 13/11/2008 BBX : modification de la clause where
					$link_table_object = "b.";
					$where = " AND b.$subquery_source = $table_source.$subquery_source ";
					$table_source .= " ,(SELECT eoar_id AS {$subquery_source}, eoar_id_parent AS {$subquery_cible} FROM edw_object_arc_ref WHERE eoar_arc_type = '{$subquery_source}|s|{$subquery_cible}') b ";
				}
				// 13/11/2008 BBX : ajout de l'alias de la sous requete
				$network_ri_dynamic = 'b.'.$network_ri_dynamic;
			}
                        
                        // maj 27/12/2011 - MPR : Correction du bug 25309
                        // Dans le cas du Compute booster sur T&A Partitioné, la condition sur l'heure n'est pas bonne lorsqu'on calcule les données basées sur un NA > NA_min
                        // Exemple : sur T&A GSM : Calcul de edw_gsm_efferl_axe1_raw_msc_hour_2011122201 depuis edw_gsm_efferl_axe1_raw_bsc_hour
                        // La condition est WHERE hour = 20111222 au lieu de WHERE hour = 2011122201
                        // Dans le cas du Compute booster sur T&A Partitioné, la period correspond au jour et non à l'heure
                        // On récupère donc l'heure calculée depuis la table cible
                        if( $this->_isPartitioned && $table['ta'] == 'hour' )
                        {
                             // Compute switch
                            $tableParts = explode('_',$table['target']);
                            $untilhour = $tableParts[count($tableParts)-1];
                        }
                        
			// 12/10/2011 MMT DE BYpass temporel ajout paramètre bypass pour fonction getTimeWhere
			$where_clause = $this->getTimeWhere($agreg_source, $ta, $ta_bypassed, $untilhour, $untilday, $untilweek, $untilmonth);
			
                        // 17/03/2011 MPR/BBX -PARTITIONING-
                        // On insère directement les données dans la partition et non dans la table mère
                        // Et on effectue l'insertion dans une transaction avec un truncate
                        // Afin d'insérer plus rapidement (astuce Dalibo)
                        if($this->_isPartitioned)
                        {
                            $this->tables[$this->id_group_table][$index]['query_begin']     = "BEGIN";
                            $this->tables[$this->id_group_table][$index]['query_commit']    = "COMMIT";
                            $this->tables[$this->id_group_table][$index]['query_truncate']  = "TRUNCATE $table_target";
                            $this->tables[$this->id_group_table][$index]['query_analyze']   = "ANALYZE {$table_target}";
                            $this->tables[$this->id_group_table][$index]['target']          = $table_target;
                        }

			// 13/11/2008 BBX : Récupération de la requête de calcul du RI. Ne pas modifier cette variable qui est utilisée dans le calcul du RI.
			// ACS BZ 27383 No data inserted after Compute
			$query_ri = $this->generateRiQuery($agreg_cible, $link_table_object, $link_table_object_third_axis);

			$listFields = array();
			$listFormules = array();
			//on evalue les fonctions d'aggregation au cas où les fonctions seraient tapées en dures et contiennnent des variables
			foreach ( $this->counters[$this->family_info['edw_group_table']] as $counter )
			{
				eval('$form = "'.$counter['formula'].'";');
				$listFields[] = $counter['name'];
				$listFormules[] = $form;
				if ( $counter['agreg'] == '' )
					$non_agreg_fields[] = $counter['name'];
			}

			if ( count($listFields) > 0 )
				$fields = implode(", ", $listFields);
			if ( count($listFormules) > 0 )
				$values = implode(", ", $listFormules);
			if ( count($non_agreg_fields) > 0 )
				$non_agreg_fields = implode(", ", $non_agreg_fields);


			/* ****************** DEBUT CONSTRUCTION REQUETE BH ************************* */
			$where_clause_bh = ''; //pas de clause where_bh si $ta n'est pas de type bh
			if ( isATimeBH($ta) && $this->family_info['bh_formula'] != null ) // si $ta est de type BH
			{
				$ta_min_bh = getMinTimeBHLevel('bh'); //day_bh normalement
				$bh_param = getBHParam($this->id_group_table, 'bh_parameter'); //mode d'agregation temporelle (1=normal, 3=3DBH)
				$bh_na_mode = getBHParam($this->id_group_table, 'bh_network_aggregation'); //mode d'agregation reseau (standard ou aggregated)
				if ($ta_min_bh != $ta) // Agregation Temporelle : calcul de week_bh et month_bh
				{
					// week_bh et month_bh sont toujours calcules a partir de day_bh du meme niveau d'agregation reseau
					// maj 17/07/2008 BBX : passage de la na au lieu de la TA dans getClauseWhereBH.BZ 7148
					//$where_clause_bh = $this->getClauseWhereBH($ta, $table_source, $where_clause . $where, 1); //BH normal
					$where_clause_bh = $this->getClauseWhereBH($na, $table_source, $where_clause . $where, 1); //BH normal
					if ($bh_param > 1) // 3DBH valable seulement pour week_bh et month_bh (>day_bh)
					{
						// 3DBH est la moyenne des 3 jours les plus charges de la semaine ou du mois
						// maj 17/07/2008 BBX : passage de la na au lieu de la TA dans getClauseWhereBH.BZ 7148
						//$where_clause_bh = $this->getClauseWhereBH($ta, $table_source, $where_clause . $where, $bh_param);
						$where_clause_bh = $this->getClauseWhereBH($na, $table_source, $where_clause . $where, $bh_param);
						$ta_fields_sel = "$ta,null";
						$ta_fields_group_by = $ta;
						$values = str_replace("sum(", "avg(", $values);
						$values = str_replace("SUM(", "avg(", $values);
					}
					$table_source .= " w ";
				}
				elseif ($ta_min_bh == $ta)  // Agregation Reseau : concerne le calcul de day_bh
				{
					/*
					 * Regles de calculs en fonction du type de BH (agrege ou non):
					 * 1 - la table day_bh est calculee a partir de la table hour du meme niveau d'agregation reseau (mode standard)
					 * 2 - la table day_bh est calculee a partir de la table day_bh du niveau d'agregation inferieur (mode agrege)
					 */
					if ($agreg_cible != $this->family_info['na_min_deployed'] && $bh_na_mode == 'aggregated')  // cas 2 - agrege
					{
						// 25/11/2013 GFS : BZ#31570 - [SUP][TA GSM][AVP 32337][Emtel Ile Maurice] : BH doesn't work in agregated mode
						$link_table_object = "o.";
						// bsc_day_bh calcule a partir de cell_day_bh (cell_day_bh est toujours calcule a partir de cell_hour)
						$object_table = get_object_ref_from_family(getFamilyFromIdGroup($this->id_group_table)); //edw_object_1_ref par exemple
						$agreg_source_bh = $this->family_info['na'][$agreg_cible][0]; //bsc
						$ta_fields_bh = implode(",", getTimesBH()); //contient day_bh,week_bh,month_bh
                                                
                                                // 16/04/2012 BBX
                                                // BZ 26812 : On passe en mode agrégation réseau
                                                $aggreg_net_ri = 1;                                                
                                                $listFormules = array();                                                
                                                foreach ( $this->counters[$this->family_info['edw_group_table']] as $counter ) {
                                                        eval('$form = "'.$counter['formula'].'";');
                                                        $listFormules[] = $form;
                                                }
                                                if ( count($listFormules) > 0 ) $values = implode(", ", $listFormules);
                                                // FIN BZ 26812

						/*
						* 27/07/2009 BBX : modification de la requête topo pour fonctionner en CB 5.0
						*/
						// Cas famille 3ème axe
						if(GetAxe3($this->family_info['family']))
						{
							// il faut découper les combinaisons
							list($firstAxisLvlSource,$ThirdAxisLvlSource) = explode('_',$agreg_source_bh);
							list($firstAxisLvlCible,$ThirdAxisLvlCible) = explode('_',$agreg_cible);
							// Si les niveaux 3ème cible et source sont identiques, il faut joindre sur le premier axe (agrégation 1er axe)
							if($ThirdAxisLvlSource == $ThirdAxisLvlCible)
							{
								// 3/02/2011 MMT Bz 20443 utilise edw_object_arc_ref  à la place de edw_object_arc
								$table_source .= " w ,(SELECT DISTINCT eoar_id AS {$firstAxisLvlSource}, eoar_id_parent AS {$firstAxisLvlCible} FROM edw_object_arc_ref WHERE eoar_arc_type = '{$firstAxisLvlSource}|s|{$firstAxisLvlCible}') o";
								$where_clause_bh = " AND w.$firstAxisLvlSource=o.$firstAxisLvlSource";
							}
							// Sinon, la jointure se fait sur le 3ème axe (agrégation 3ème axe)
							else
							{
								// 3/02/2011 MMT Bz 20443 utilise edw_object_arc_ref  à la place de edw_object_arc
								$table_source .= " w ,(SELECT DISTINCT eoar_id AS {$ThirdAxisLvlSource}, eoar_id_parent AS {$ThirdAxisLvlCible} FROM edw_object_arc_ref WHERE eoar_arc_type = '{$ThirdAxisLvlSource}|s|{$ThirdAxisLvlCible}') o";
								$where_clause_bh = " AND w.$ThirdAxisLvlSource=o.$ThirdAxisLvlSource";
							}
						}
						// Cas standard : famille sans 3ème axe
						else
						{
							// La jointure se fait ici sur le 1er axe
							// 3/02/2011 MMT Bz 20443 utilise edw_object_arc_ref  à la place de edw_object_arc
							$table_source .= " w ,(SELECT DISTINCT eoar_id AS {$agreg_source_bh}, eoar_id_parent AS {$agreg_cible} FROM edw_object_arc_ref WHERE eoar_arc_type = '{$agreg_source_bh}|s|{$agreg_cible}') o";
							$where_clause_bh = " AND w.$agreg_source_bh=o.$agreg_source_bh";
						}
						/**/

						$where_clause = " WHERE $ta_min_bh='$untilday' ";
						$ta_fields_sel = "$ta_fields_bh,null"; //moyenne des bh du niveau network inferieur
						$ta_fields_group_by = $ta_fields_bh;
					}
					else  // cas 1 - standard
					{
						// mode agrege : pour le NA le plus petit on calcule day_bh a partir de Hour(ex : cell_day_bh a partir de cell_hour)
						// mode standard : la table day_bh est toujours calculee a partir de la table hour du meme NA (bsc_day_bh calcule a partir de bsc_hour)
						$where_clause_bh = $this->getClauseWhereBH($na, $table_source, $where_clause . $where, $bh_param);
						$table_source .= " w ";
						if ($bh_param > 1) // cas d'une XDBH moyenne des heures les plus chargees
						{
							$ta_fields_sel .= ",null"; //aucune heure remontee
							$values = str_replace("sum(", "avg(", $values);
							$values = str_replace("SUM(", "avg(", $values);
						}
						else // BH normal
						{
							$ta_fields_sel .= ",hour"; //on remonte l'heure la plus chargee
							$ta_fields_group_by .= ",hour";
						}
					}
				}
			}

			/*
				maj 27/02/2008 christophe : si on est sur le niveau minimum NA/TA, on sélectionne toutes les NA de la famille
				On fait cela pour pouvoir réintégrer les données dans la table w_edw... : voir explication dans la méthode reloadHourHistory
			*/
			if ( $agreg_cible == $this->family_info['na_min_deployed'] && $ta == $this->family_info['ta_min_deployed'])
			{
				$agreg_cible_list = select_net_fields($this->id_group_table, "raw", "-1");

				// 13/11/2008 BBX : on découpe nos éléments afin de séparer la partie 1er axe et 3ème axe
				$list_field_splitted = Array();
				foreach($agreg_cible_list as $one_field) {
					$first_axis_part = $third_axis_part= '';
					list($first_axis_part,$third_axis_part) = explode('_',$one_field);
					if($first_axis_part != '') $list_field_splitted[] = $first_axis_part;
					if($third_axis_part != '') $list_field_splitted[] = $third_axis_part;
				}

				$agreg_cible = implode(", ", array_unique($list_field_splitted));
			}
			else {
				// 13/11/2008 BBX : on découpe nos éléments afin de séparer la partie 1er axe et 3ème axe
				$first_axis_part = $third_axis_part= '';
				list($first_axis_part,$third_axis_part) = explode('_',$agreg_cible);
				$agreg_cible = $first_axis_part;
				if($third_axis_part != '') $agreg_cible .= ','.$third_axis_part;
			}

			/* ****************** FIN CONSTRUCTION REQUETE BH ************************* */

                        // 29/07/2010 BBX
                        // Ajout d'une condition NOT NULL
                        // sur les axes d'investigation 1er axe et 3ème axe
                        // BZ 14266
                        // 09/09/2010 BBX
                        // Correction de la récupération des NA en cours de calcul.
                        // BZ 17785
                        list($first_axis_part,$third_axis_part) = explode('_',$table['na']);
                        $operator = substr_count(strtolower($where_clause.$where.$where_clause_bh),"where") > 0 ? " AND" : " WHERE";
                        $notNullCondition = "{$operator} {$link_table_object}{$first_axis_part} IS NOT NULL";
                        if(!empty($third_axis_part))
                            $notNullCondition .= " AND $third_axis_part IS NOT NULL";

                        // 01/08/2012 BBX
                        // BZ 28325 : Jointure avec la topologie
                        list($naSource) = explode('_',$table['na_source']);
                        $operator = (trim($where_clause.$where.$where_clause_bh.$notNullCondition) == "") ? " WHERE" : " AND";
                        $whereTopo = "{$operator} {$link_table_object}{$naSource} IN (SELECT eor_id FROM edw_object_ref WHERE eor_obj_type = '$naSource' AND eor_on_off = 1)";

			$query = "
                                    INSERT INTO ".$table_target." (" . $agreg_cible . ", " . $ta_fields . ", " . $fields . ")
                                    SELECT ".$link_table_object . $agreg_cible . ", " . $ta_fields_sel . ", " . $values . "
                                    FROM ".$table_source."
                                    ".$where_clause . $where . $where_clause_bh."
                                    $notNullCondition
                                    $whereTopo
                                    GROUP BY ".$link_table_object . $agreg_cible . ", ";

			if (count($non_agreg_fields) > 0)
				$query .= $non_agreg_fields . ", ";

			$query .= $ta_fields_group_by;

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
	 * Post-traitement
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 */
	public function afterProcess ()
	{

	} // End function afterProcess

	/**
	* insertHistoryHour : cette méthode  permet d'insérer dans la table w_edw....hour_20071209 par exemple toutes les heures qui ont déjà été insérées en BDD.
	* Quand on passe de compute_mode hourly vers daily la table w_edw... qui doit contenir la liste des toutes les heures de la journée ne contient que les heures arrivées
	* en retard, on doit donc remplir cette table avec les heures déjà insérée.
	* @since cb4.0.0.00
	* @version cb4.0.0.00
	*/
	public function reloadHourHistory()
	{
		$t_insert = 'w_'.$this->family_info['edw_group_table'].'_'.
						$this->compute_info['categorie'].'_'.
						$this->family_info['na_min_deployed'].'_'.
						$this->family_info['ta_min_deployed'].'_'.
						$this->compute_info[day];

		$t_source = $this->family_info['edw_group_table'].'_'.
						$this->compute_info['categorie'].'_'.
						$this->family_info['na_min_deployed'].'_'.
						$this->family_info['ta_min_deployed'];

		$hoursList = 	get_sys_global_parameters('hour_to_compute');
		$sep = 			get_sys_global_parameters('sep_axe3');
		$hoursList = 	str_replace($sep,'\',\'',$hoursList);
		$ta_fields = 	$this->getTimeFields('hour', 0);

		$agreg_cible_list = select_net_fields($this->id_group_table, "raw", "-1");

		// 13/11/2008 BBX : on découpe nos éléments afin de séparer la partie 1er axe et 3ème axe
		$list_field_splitted = Array();
		foreach($agreg_cible_list as $one_field) {
			$first_axis_part = $third_axis_part= '';
			list($first_axis_part,$third_axis_part) = explode('_',$one_field);
			if($first_axis_part != '') $list_field_splitted[] = $first_axis_part;
			if($third_axis_part != '') $list_field_splitted[] = $third_axis_part;
		}

		$agreg_cible = 		implode(", ", array_unique($list_field_splitted));

		$listFields = array();
		foreach ( $this->counters[$this->family_info['edw_group_table']] as $counter )
			$listFields[] = $counter['name'];

		$fields = implode(',',$listFields);

		displayInDemon('<h3>Réintégration des heures du '.$this->compute_info[day].'</h3> (sauf des heures arrivées en retard : '.$hoursList.')');

		/*
			Note : on ne fait pas de jointure sur la table object_ref car la requête dure plus longtemps. On prend directement les  NA de toute la famille dans la table
			edw.... de na/ta minimum de la famille (l'insertion de toutes le NA dans la table edw... sur les tables na_min/ta_min se fait dans la méthode prepareRequest
			avec un if() sur les na_min/ta_min)
		*/
		// 15:19 14/12/2009 GHX
		// Correction du BZ 13340
		// Ajout du deuxieme NOT IN sur la table de buffer (edw...hour)
		$query = "
			INSERT INTO ".$t_insert." (".$agreg_cible.", ".$ta_fields.", ".$fields.")
				SELECT ".$agreg_cible.", ".$ta_fields.", ".$fields."
					FROM ".$t_source."
					WHERE hour NOT IN('".$hoursList."')
					AND  hour NOT IN ( SELECT DISTINCT hour FROM ".$t_insert.")
					AND day='".$this->compute_info['day']."'";
		//__debug($query);
		$this->sql($query);

	} // End function insertHistoryHour

} // End class computeRaw
?>
