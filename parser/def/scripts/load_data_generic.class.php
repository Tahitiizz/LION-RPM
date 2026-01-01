<?
/** 
 *      @package TA_CB
 *      @version 5.1.5
 *      @subpackage Parser def
 * 
 *      maj 13/10/2011 - MPR : Nettoyage du script (suppression de toutes les méthodes obselètes
 *      maj 14/10/2011 - MPR : DE Time Aggregation Bypass
 *             -> Création de la méthode getInfosFile() qui récupère toutes les informations sur les fichiers à traiter (En fonction de la ta du fichier source, on vérifie qu'elle est bypassée ou non)                             
 * 20/10/2011 NSE bz 24295 : pb lorsqu'une famille est manquante : compteurs de la famille traitée avant sont utilisés pour créer la table temporaire.
 */
?>
<?
/*
 *	@gsm3.0.0.00@
 *
 *	14:06 15/06/2009 SCT => mise à niveau sur CB 5.0
 *	11:23 09/07/2009 SCT : amélioration de l'affichage du démon
 */
?>
<?php
/**
 * Fichier qui contient des fonctions génériques utilisées dans les classes propres à chaque type de fichier à traiter
 * 
 * 12-12-2006 GH : fonction d'insertion des compteurs dynamiques issus des fichiers sources.Non activé car le CB1.3 n'a pas la fonction de mapping des compteurs dynamiques<br>
 * 				  Suppression de la fonction liée aux pseudo-Kpis<br>
 * 				  Modification de la fonction 'get_cpts_from_sfr' pour que dans le cas du compteur capture_duration, la fonction d'agregation soit AVG ce qui est important pour les HAndover sur lesquels ont ne prend pas la target et donc c'est une sorte de macro-diversité<br>
 * 				  Modification de la fonction 'get_nets' pour que la clé des tableaux intégrer le elt_type<br>
 * 				  Modification de la fonction 'get_oth_element' pour que la clé des tableaux intégrer le elt_type<br>
 * 				  Ajout de la fonction 'get_counters_element' qui récupère les informations sur les compteurs (entité 3 du fichier principal). Jusqu'à présent cette fonction était présente dans les fichiers php qui traitent le type 60 et 66 mais de manière moins complète
 * 				  Ajout de la fonction 'get_nb_active_cpts' qui permet d'avoir le nombre de compteur associé à un type. Le but est de pouvoir géré plusieurs moteurs Cigale différents dont lenombre de colonnes dans les fichiers texte peuvent varier suivant la version du moteur
 * 				  Ajout de la fonction 'clean_copy_tables' qui nettoie les tables temporaires _temp
 * 
 * 05-04-2007 GH : ajout du type 63,64,69 dans la fonction get_files
 * 				  ajout de l'entité 8 dans le fichier R04 qui correspond à des données de distribution des débits
 * 				  ajout de la fonction get_distibution qui renvoie 2 tableaux contenant les distributions
 * 
 *
 * 16/12/2008 MPR : Ajout de la variable query_ri pour le calcul du ri et de $arc_type
 * 16/12/2008 - MPR : Modification de la requête qui récupère les éléments réseau en base
 * 16-08-2007 SC : ajout de deux fonctions "get_source" et "set_aa"
 *                      ajout de la fonction "get_source" : permet une analyse de l'entête du fichier R04 afin de récupérer les informations nécessaires à la connexion aux serveurs pour l'appli AA
 *                      ajout de la fonction "set_aa" : permet l'enregistrement des informations connexion serveur "sys_aa_server" et "sys_aa_base" pour exploitation logiciel AA
 * 
 * 21-08-2007 SC :
 *            - ajout de la fonction "generic_create_table_w" pour la creation des tables W et W_temp de chaque famille
 *                paramètres:
 *                * $tableau_entite[$label_entite] = array('field' => 'xxxx, xxxx, xxx, xxx, xxxx', 'specific_field' => 'yyyy, yyyy, yyy, yyy, yyyy', 'group_table' => 'x', 'network' => 'xxx', 'W_table_name' => 'xxxx');
 *                    ce tableau contient les informations de champs à construire pour chaque entité du fichier
 *            - ajout de la fonction "generic_create_copy_header" pour le remplissage des bases de données depuis des fichiers SQL
 *                paramètres:
 *                * $tableau_todo = array($label_entite => 'xxxx, xxxx, xxx, xxx, xxxx')
 *            - ajout de la fonction "generic_explode_base_file" pour le remplissage des bases de données depuis les fichiers d'information
 *                paramètres:
 *                * $i int numéro du fichier à traiter
 *                * $tableau_todo array tableau contenant la référence entité et le numéro entité à recherche
 *                * $numero_fichier int numéro du fichier en cours d'analyse
 *            - ajout de la fonction "generic_check_data" pour la recherche du nombre d'entités dans un fichier d'information
 *                paramètres:
 *                * $i int numéro du fichier à traiter
 *                * $tableau_entite array tableau contenant la référence entité, le numéro entité à rechercher et le commentaire en cas de résultat nul
 *                * $active_log bool permet l'activation de la fonction "sys_log_ast"
 * 
 * 23-08-2007 SC :
 *            - modification de la fonction "get_files" : on donne l'extension du fichier à rechercher
 *                paramètres:
 *                * $type_extension text l'extension du fichier recherche
 *                return:
 *                * $j int nombre de fichiers trouvés
 *            - création d'une fonction "get_files_R04" à partir de l'ancienne fonction "get_files" pour la mise en tableau des fichiers R04
 *                return:
 *                * $j int nombre de fichiers trouvés
 * 
 * 10-09-2007 GHY : suppression de toute reference aux fichiers R04 afin que les fonctions puissent être réutilisées par la suite pour d'autres parser
 * 					=>Modifications du code de SC (modifs du 23-08 et 21-08
 * 
 * 
 * 12/09/2007 Gwen : 
 * 	- ajout de la fonction create_file_topo
 * 	- modification de la fonction get_source
 * 	- prise en compte du tag dans la fonction set_aa
 *
 *  27/09/2007 Gwen : 
 * 	- on prend les dates de traitements au lieu des dates de captures pour remplir sys_aa_base
 *
 *  13/11/2007 SCT : 
 *      - fonction "explode_file_reference" => ajout de l'entité art_elts dans l'analyse du fichier de référence
 *      - fonction "get_file_reference" => ajout de la recherche du fichier contenant les art_elts
 *      - function "get_attrib" => ajout de la fonction pour la récupération des éléments du fichier
 *
 * 16-11-20007 SCT : fonction "get_capture_duration" => modification de la fonction pour obtenir le capture duration en fonction du type de fichier de référence utilisé : standard, resellers, ...
 *
 * 07-11-2007 Gwen : fonction "create_file_topo" => [bug 5291] si l'un élément réseau n'est pas présent dans le tableau $this->type_na celui-ci ne sera pas mit dans le fichier de topo
 *
 * 26-11-2007 SCT : fonction "update_dynamic_counter_list" => ajout de l'analyse des compteurs pour les TIMERS
 *
 * 28-11-2007 SCT : fonction "get_element_from_topology" => ajout d'une fonction pour la recherche des éléments de niveau minimum de la topologie d'une ou plusieurs familles
 * 
 * 17-12-2007 SCT : modification fonction "get_files" => recherche des fichiers de référence pour la correspondance avec le fichier analysé (problème de décalage lors de fichiers manquants)
 * 21-12-2007 SCT : ajout d'une fonction "chargement_topo_restreinte" => récupération des éléments de la topo à partir des différents paramètres passés en arguments 
 *
 *	- 09/01/2008 Gwénaël : correction du bug 5685
 *
	- maj 05/02/2008, benoit : corrections pour compatibilité php5
 *
 *	- modif 26/03/2009 SCT : ajout d'une condition sur l'exécution du FOREACH afin d'éviter les erreurs en cas de tableau vide
 *	- modif 16:41 22/07/2009 SCT : BZ 10763 => Problème d'intégration des données pour la famille SMS-center
 * @package Parser_GSM
 * @author Guillaume Houssay 
 * @version 2.1.0.10
 */

class load_data_generic
{
    	/**
	 * Fonction constructeur qui initialise des variables
	 * 
	 * @global text nom du module de l'application (ici gsm)
	 */
	function __construct()
	{
		global $module;

		$this->module = $module;
		$global_parameters = edw_LoadParams();
		$this->capture_duration_expected = $global_parameters["capture_duration"];
		$this->net_element_count = 1; //correspond à une unite de compatge qui en s'aggregeant compte le nombre d'éléments
		$this->database = Database::getConnection();
		// 11:47 13/07/2009 SCT : gestion de l'affichage du debug
		$this->debug = get_sys_debug('retrieve_load_data');
	} 
	/**
	 * Fonction qui supprime de la table sys_flat_file_uploaded_list l'heure traitée
	 * une fois le traitement des fichiers pour cette heure terminé.
	 */
	function clean_flat_file_uploaded_list()
	{
		$query = "
			SELECT 
				flat_file_location 
			FROM 
				sys_flat_file_uploaded_list 
			WHERE 
				hour = " . $this->hour;
		$res = $this->database->execute($query);
		while($values = $this->database->getQueryResults($res, 1))
			$files[] = $values['flat_file_location'];
		foreach($files as $file)
		{
			if(file_exists($file))
				unlink($file);
			$query_clean = "
				DELETE FROM 
					sys_flat_file_uploaded_list 
				WHERE 
					flat_file_location = '" . $file . "'";
			$result_clean = $this->database->execute($query_clean);
		} 
	} 

	/**
	 * fonction qui nettoie les fichiers csv contenant les données insérées dans les tables
	 */
	function clean_copy_files()
	{
		// maj 03/07/2008 Benjamin : ajout d'un tests sur l'existance de la liste des fichiers
		if(isset($this->copy_files))
		{
			foreach($this->copy_files AS $todo => $array_level)
			{
				foreach ($array_level AS $level => $inutile)
					unlink($this->copy_files[$todo][$level]);

			}
		}
	} 

	/**
	 * Nettoyage des tables temporaires _temp
	 */
	function clean_copy_tables()
	{
		// BUGZ 12049 : tables_temp pas supprimées
		foreach ($this->table_names_temp as $todo => $temp_table)
		{
			postgres_drop_table(strtolower($temp_table));
		} 
	} 
	/**
	 * Fonction qui éxécute les requetes de chargement des fichiers csv dans les tables.
	 */
	function exec_copy_query()
	{ 
		// modification liée au RI
		$time_expected_ri = get_sys_global_parameters("capture_duration"); //Recupere la duree de capture
		$aggreg_net_ri = 1; //precise que les calculs qui sont fait sont des aggregation réseau (la source et la cible ayant le meme time aggregation)
		// 10:02 06/03/2009 SCT : bug 8906 => ajout d'un paramètre supplémentaire pour les conditions dans les formules de compteurs
		$integration_level = 1;
		$nb_sondes_ri = get_sys_global_parameters("nb_sources_expected"); //récupère le nombre de sondes attendues
		$table_object_ri = "''"; //variable inutile car non utilisee lorsque le network_minimum est egal au niveau network cible. Lors de l'integration des fichiers sources, on est forcément au niveau minimum
		$time_coeff = 1; //au niveau hour, le coeff time vaut 1
		$min_net_ri = "''"; //l'important est que cette variable soit égale à $network_ri
		$network_ri = "''";
		// 16/12/2008 MPR - On ajoute le type d'arc pour la fonction ri_calculation
		$arc_type = "''";
		
		$network_ri_dynamic = "''"; //variable inutile car non utilisee lorsque le network_minimum est egal au niveau network cible. Lors de l'integration des fichiers sources, on est forcément au niveau minimum
		$hour_ri = "hour"; //nom de la colonne qui sert dans la fonction CASE WHEN $aggreg_net_ri=1 THEN ri_calculation_sonde($hour_ri)::float4 ELSE SUM(capture_duration) END présent dans sys_field_reference
		// 16/12/2008 MPR : Ajout de la variable query_ri pour le calcul du ri
		$query_ri = 1;


		// maj 05/06/2008 Benjamin : On vérifie que $this->copy_files existe avant de le traiter.
		if(isset($this->copy_files))
		{
			foreach($this->copy_files AS $todo => $array_level)
			{
				foreach($array_level AS $level => $inutile)
				{
					$network = $level; //cette variable est la même que celle utilisée dans le compute. C'est l'agregation network cible. Elle est utilisée dans certains cas pour le bypass de compteurs.
					$network_source = "'" . $network . "'"; //cette variable est la même que celle utilisée dans le compute. C'est l'agregation network source. Elle est utilisée dans certains cas pour le bypass de compteurs.
					unset($nb_lignes);
					if(file_exists($this->copy_files[$todo][$level]))
					{
						$query_copy = $this->copy_header[$todo][$level] . "'" . $this->copy_files[$todo][$level] . "' with delimiter ';' NULL AS ''";
						$result = $this->database->execute($query_copy);

						$lErreur = '';
						if(($lErreur = $this->database->getLastError()) != '')
							// 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
							echo displayInDemon($lErreur.' '.$query_copy.';<br>'."\n", 'alert');
						else
						{
							exec("wc -l " . $this->copy_files[$todo][$level] . " | awk '{print $1}'", $nb_lignes);
							// 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
							echo displayInDemon($nb_lignes[0].' lignes insérées pour l\'entité '.$todo.' et le niveau '.$level.'<br>'."\n");

							$query = "
								INSERT INTO 
									" . $this->table_name[$todo][$level] . " 
										(" . $this->tableau_entite_properties[$todo][$level] . ", " . implode(", ", array_keys($this->cpts_aggregated_in_sfr[$todo])) . ") 
										SELECT 
											" . $this->tableau_entite_properties[$todo][$level] . ", " . implode(", ", $this->cpts_aggregated_in_sfr[$todo]) . " 
										FROM 
											" . $this->table_name_temp[$todo][$level] . " 
										GROUP BY 
											" . $this->tableau_entite_properties[$todo][$level];
							eval("\$query = \"$query\";");
							$res = $this->database->execute($query);


							$lErreur = $this->database->getLastError();
							if($lErreur != '')
								// 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
								echo displayInDemon($lErreur.' '.$query.';<br>'."\n", 'alert');
							else

							{
								// 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
								if($this->debug)
									echo displayInDemon($this->database->getAffectedRows($res).' = '.$query.'<br>'."\n");
							}
						} 
					}
					else
						// 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
						echo displayInDemon('Le fichier pour le COPY n\'est pas présent<br>'."\n", 'alert');
				} 
			}

		} 
	} 

	/**
	 * Récupère la liste de compteurs dans la table sys_field_reference.
	 * Recupère d'une part la liste des compteurs tels que présent dans la source ainsi que le nom des compteurs donnés dans l'application.
	 */
	function get_cpts_from_sfr( $entite = "" )
	{
		unset($this->cpts_in_sfr);
		unset($this->cpts_default_value_in_sfr);
		unset($this->nms_cpts_in_sfr);
		unset($this->cpts_aggregated_in_sfr);

		if ($entite != "")
			$nms_table_field = $this->type . "_" . $entite;
		else 
			$nms_table_field = $this->type;
		$query = "
			SELECT 
				edw_field_name, 
				edw_field_type,
				nms_field_name, 
				edw_agregation_function,
				default_value,
				edw_agregation_formula
			FROM 
				sys_field_reference
			WHERE 
				id_group_table = ".$this->id_gt." and on_off = 1
			ORDER BY 
				flat_file_position ASC";
		$res = $this->database->execute($query);
		$i = 0;
		while($row = $this->database->getQueryResults($res, 1))
		{
			$this->cpts_in_sfr[$entite][$row['edw_field_name']] = $row['edw_field_type'];
			$this->cpts_default_value_in_sfr[$entite][$row['edw_field_name']] = $row['default_value'];
			$this->nms_cpts_in_sfr[$entite][$row['edw_field_name']] = $row['nms_field_name'];
                        // 13/10/2011 BBX
                        // BZ 23930 : on forme le champ en minuscules
			if($row['default_value'] == "" || strtolower($row['edw_agregation_function']) == 'log')
				// on est dans le cas où la fonction d'aggregation est standard (MIN,MAX,AVG ou SUM). On teste si le compteur vaut 'capture_duration' afin de forcer sa fonction d'aggregation
				$this->cpts_aggregated_in_sfr[$entite][$row['edw_field_name']] = $row['edw_agregation_formula']; 
			elseif($row['edw_field_name'] == 'capture_duration')
				// Force la fonction d'aggregation de capture_duration à AVG car notamment pour les données de type Handover on ne peut pas utiliser la fonction stadard SUM car sinon cela effetuerait pour une même cellule la SUM des capture_duration de la cellule pour chacune de ses TARGET.
				$this->cpts_aggregated_in_sfr[$entite][$row['edw_field_name']] = 'AVG(capture_duration)';
			else
				$this->cpts_aggregated_in_sfr[$entite][$row['edw_field_name']] = $row['edw_agregation_function'] . "(CASE WHEN " . $row['edw_field_name'] . " IS NULL THEN " . $row['default_value'] . " ELSE " . $row['edw_field_name'] . " END )";
			$i++;
		} 
	} 

	/**
	 * Fonction qui insere dans la table sys_field_reference_all les compteurs dynamiques non déjà présent
	 * Cela utilise les résultat de la fonction get_counters_element qui récupère la liste des compteurs dynamiques
	 *
	 * 26-11-2007 SCT : ajout de l'analyse des compteurs pour les TIMERS
	 * 09:09 05/12/2008 SCT : ajout de l'id_group_table en tant que paramètre => on doit pouvoir ajouter un compteur pour chaque famille de l'application
	 *
	 * @param array $liste_counters : tableau contenant les clés et noms des compteurs à ajouter
	 * @param string $entite nom de l'entite sur lequel le compteur sera ajouté
	 * @param int $id_group_table id_group_table de la famille pour le compteur à ajouter
	 * @param int $default_value valeur par défaut du compteur
	 * @param sting $prefix_counter le préfixe pour le compteur
	 */

	function update_dynamic_counter_list($liste_counters, $entite, $id_group_table = 1, $default_value = 0, $prefix_counter = null)
	{
		// Creation d'une table temporaire sur le modele de sys_field_reference_all
		$table_uniqid = 'w_temp_' . uniqid("");
		$query = '
			CREATE TEMP TABLE 
				' . $table_uniqid . ' 
			(LIKE sys_field_reference_all)';
		$this->database->execute($query);
		// Ecriture des compteurs dynamqieus dans un fichier
		$ligne = "";
		$fichier_uniqid = REP_PHYSIQUE_NIVEAU_0 . "upload/temp_" . uniqid("") . ".txt";
		$fp = fopen($fichier_uniqid, "w+");
		// 15:21 26/03/2009 SCT : ajout d'une condition sur l'exécution du FOREACH afin d'éviter les erreurs en cas de tableau vide
		if(isset($liste_counters) && count($liste_counters) > 0)
		{
			foreach($liste_counters AS $key => $counter_name)
			{
                	// 27/01/2011 OJT : correction bz20324, gestion du group table et suppression du test de l'entitié GSM
				$ligne .= $key.";".$entite.";".$counter_name.";".$id_group_table.";SUM;SUM;".($this->hasAxe3 ? "SUM" : "NA").";0;$default_value;".$prefix_counter."\n";
			}
		}

		fwrite($fp, $ligne);
		fclose($fp); 
		// maj 09/06/2008 Benjamin : affichage des compteurs ignorés pour cause de doublon
		$query = "
			SELECT 
				nms_field_name 
			FROM 
				sys_field_reference_all 
			WHERE 
				nms_field_name IN ('".implode("', '", $liste_counters)."')";
		$result = $this->database->execute($query);
		// insertion du fichier dans la table temporaire
		$query = "
			COPY 
				" . $table_uniqid . " 
					(
					id_ligne,
					nms_table,
					nms_field_name,
					id_group_table,
					edw_agregation_function_axe1,
					edw_agregation_function_axe2,
					edw_agregation_function_axe3,
					blacklisted,
					default_value,
					prefix_counter
					) 
			FROM 
				'" . $fichier_uniqid . "' 
			WITH DELIMITER ';'";
		$this->database->execute($query); 
		// insertion dans la table sys_field_reference_all des nouveaux compteurs
		$query = '
			INSERT INTO 
				sys_field_reference_all 
					(
					nms_table, 
					nms_field_name, 
					id_group_table, 
					edw_agregation_function_axe1,
					edw_agregation_function_axe2,
					edw_agregation_function_axe3,
					blacklisted,
					default_value,
					prefix_counter
					) 
					SELECT 
						nms_table, 
						nms_field_name, 
						id_group_table, 
						edw_agregation_function_axe1,
						edw_agregation_function_axe2,
						edw_agregation_function_axe3,
						blacklisted,
						default_value,
						prefix_counter 
					FROM 
						' . $table_uniqid . ' 
					WHERE 
						NOT EXISTS 
						(
							SELECT 
								nms_field_name 
							FROM 
								sys_field_reference_all 
							WHERE 
							   sys_field_reference_all.nms_field_name = ' . $table_uniqid . '.nms_field_name 
							LIMIT 1
						)';
		$res = $this->database->execute($query);


		$lErreur = $this->database->getLastError();
		if($lErreur != '')
			// 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
			echo displayInDemon($lErreur.' on '.$query.';<br>'."\n", 'alert');
		else
			// 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
			echo displayInDemon($this->database->getAffectedRows($res).' nouveaux compteurs dynamiques insérés<br>'."\n");

		exec("rm $fichier_uniqid");
	} 

	/**
	 * Fonction d'insertion des tables de données qui ont été génrées dans la table sys_w_tables_list
	 * 
	 */
	function insert_in_w_tables_list($cle_entite, $level)
	{
		$query_check="select count(*) from sys_w_tables_list 
						where table_name='{$this->table_name[$cle_entite][$level]}'";
		if ( $this->database->getone($query_check)==0 )
		{
			$query = "
				INSERT INTO 
					sys_w_tables_list
						(hour, day, table_name, group_table, network, ta) 
				VALUES
					({$this->hour}, {$this->day}, '{$this->table_name[$cle_entite][$level]}', '{$this->tableau_entite_properties[$cle_entite]['group_table']}', '{$this->tableau_entite_properties[$cle_entite]['network']}', '{$this->granularity}')";
			$this->database->execute( $query );
		}
	} 

	/**
	 * fonction qui affcihe dans le démon l'heure et le type de fichier traité.Cela permet une meilleure lecture du démon
	 */
	function display_header()
	{
		// 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
		displayInDemon('<pre>****************** Treatment of '.$this->hour.', '.$this->type.'******************<br />'."\n");
	} 

	/**
	 * Fonction de création des tables W et W_temp
	 * 
	 */
	function generic_create_table_w( $cle_entite )
	{
                if( !isset( $this->table_names_temp) )
                        $this->table_names_temp = array();
                
		foreach( $this->network_level AS $level )
		{
			foreach( $this->tableau_entite_properties AS $cle_entite => $inutile )
			{
                            // 20/10/2011 NSE bz 24295 : $this->cpts_in_sfr[$cle_entite] n'était pas remis à jour
                            $this->get_cpts_from_sfr($cle_entite);
                                 // maj 14/10/2011 - MPR : On créé une table w_astellia_%_day si la ta est bypassée 
                                $this->table_name[$cle_entite][$level] = "w_astellia_" . $this->type . "_" . $level . "_" . ( ( $this->granularity != $this->ta_min ) ? $this->day : $this->hour );
                                $this->table_name_temp[$cle_entite][$level] = $this->table_name[$cle_entite][$level] . "_temp";
                                // echo $this->table_name[$cle_entite][$level].'<br>';
                                if( !$this->database->doesTableExist( $this->table_name[$cle_entite][$level] ) )
                                {
                                    // BUGZ 12049 : tables_temp pas supprimées
                                    if( !in_array($this->table_name[$cle_entite][$level] . "_temp", $this->table_names_temp ) )
                                        $this->table_names_temp[]=$this->table_name[$cle_entite][$level] . "_temp";

                                    $tables = array( $this->table_name[$cle_entite][$level], $this->table_name_temp[$cle_entite][$level]);
        
                                    foreach( $this->cpts_in_sfr[$cle_entite] AS $name => $type )
                                            $fields[] = $name . " " . $type;
                                    
                                    //echo "clef : $cle_entite - ".'$fields : ';print_r($fields);
                                    //echo $cle_entite.' $this->tableau_entite_properties['.$cle_entite.'][field] : '.$this->tableau_entite_properties[$cle_entite]['field'].'<br>';

                                    foreach( $tables as $table )
                                    {
                                        $query_create = "CREATE TABLE {$table} (" . $this->tableau_entite_properties[$cle_entite]['field'] . ", ".implode(", ", $fields) . ")";
                                        $this->database->execute( $query_create );
                                        if( $this->debug )
                                            displayInDemon("Table copy".( ( strpos($table, "_temp") !== false ) ? " temp ": "" )." :".$query_create."<br />\n");
                                    }
                                    unset($fields);
                                    
                                     $this->insert_in_w_tables_list($cle_entite, $level);
                                }
			} 
		} 
	} 

} 
?>
