<?
/** 
 *      @package TA_CB
 *      @version 5.1.5
 *      @subpackage Parser def
 * 
 *      maj 13/10/2011 - MPR : Nettoyage du script (suppression de toutes les méthodes obselètes
 *      maj 14/10/2011 - MPR : DE Time Aggregation Bypass
 * 24/10/2011 NSE bz 24350 : on vérifie que le code sonde n'est pas vide avant de préfixer l'élément réseau.
 */
?>
<?php
/**
 * @package Parser_Generique
 * @author Cyrille Gourves
 */

// 07/12/09 CGS
// Correction BUGZ 13264 [REC][CB 5.0.1] parser DEF KO sur les familles 3è axe

// 13/11/09
// Correction BUGZ 12698 [CORPORATE] : si intéation fichier horaire sur Corpo Day, Warning à'éan
// Correction BUGZ 12700 [CORPORATE] : Mode Day KO
//22/10/09 CGS
// Correction BUGZ 12202 [Parser DEF][Load Data] : la colonne prise en compre pour récupérer les compteurs d'une famille est incorrecte. 
// Correction BUGZ 12180 [Parser DEF][Load Data] : compteurs sensibles à la casse. 
//
// 16/10/09 CGS
// Correction bug 12095 [REC][CB 5.0.1] pb concatenation code_connection dans le parser DEF
//
// 15/10/09 CGS
// Correction bug 12048 [REC][CB 5.0.1] pb integration de donnees avec le parser DEF 
// Correction bug 12049 [REC][CB 5.0.1] pb suppression de tables _temp avec le parser DEF
//
// 01/03/2011 NSE bz 21007 :
//  - modification de checkConcatNa : ajout du paramètre $na pour choisir le NA sur lequel on veut vérifier usePrefix
//	- dans create_file_query : on gère l'utilisation du préfixe sur le 3° axe
//	
// 26/10/2011 MMT Bz 24409 il ne peut y avoir qu'une requete par granularité, on boucle dessus

class load_data_def extends load_data_generic
{
	/**
	 * Fonction qui execute les differentes étapes pour l'integration des données
	 * 
	 * @param int $hour heure traitée
	 * @global text nom du systeme installé. Information présente dans sys_global_parameters
	 */
	function __construct( $hour )
	{
		global $system_name;
		// 23-01-2008 SCT : Bug # 5734 => ajout du "set_time_limit" en cas de retrieve sur un grand nombre de fichier
		set_time_limit(10000);
          
                $this->database = Database::getConnection();
		$this->dateObj=new Date();
                
                parent::__construct();
                
                // Utilisation de la méthode getInfosFiles() pour récupérer les informations des fichiers collectés
                $files = $this->getInfosFiles( $hour );
                
      foreach( $files as $row )
		{
			$this->init( $row );
			$this->display_header();
			$this->generic_create_table_w( "" );
			if ($this->header)
			{
				//				$this->generic_create_table_w($this->tableau_entite_properties);
				$net=$this->get_net_field_from_uploaded_file($row["flat_file_location"]);
				if ($this->axe3 != "")
					$net .= "_".$this->axe3 ;
				$this->create_copy_header( $row["flat_file_location"],$net);
				$this->create_file_query( $row["flat_file_location"], $net );
				$this->exec_copy_query2( $this->copy_file, $net);
				$treated_nets[] = $net;
				$treated[ $this->id_gt ] = $net;
			}
		}
		if ( isset( $this->query_insert_in_w_table ) )
      {
			// 26/10/2011 MMT Bz 24409 il ne peut y avoir qu'une requete par granularité, on boucle dessus
			foreach ( $this->query_insert_in_w_table as $id_gt => $queries_insert_per_granularity)
			{
				foreach( $queries_insert_per_granularity as $tmp_granularity => $query_insert )
				{
					displayInDemon( trim($query_insert."<br />") );
					$this->database->execute($query_insert);
				}
			}
		}
		// BUGZ 12049 : tables_temp pas supprimées
		$this->clean_copy_tables();

		//pour tous les group_table qui n'ont pas été traités (parce que pas de fichiers source liés à ces group table dans le retrieve courant) 
		$all_infos=$this->get_infos_categorie();
		foreach ($all_infos["rank"] as $id_gt => $min_net)
		{
                        // véf si l'id group_table est dans le tableau des id_gt traité  
			if(!isset($treated[$id_gt]))
			{       
                                $this->granularity = ( $all_infos["ta_bypass"][$id_gt] != "" ) ? $all_infos["ta_bypass"][$id_gt] : $this->ta_min;  
				$this->type=$this->get_family_from_id_gt($id_gt);
				//récupération de l'axe3 pour la famille courante
				$this->axe3=get_network_aggregation_min_axe3_from_family($this->type);
				$this->network_level[0]=$min_net;
				$this->id_gt=$id_gt;
				
				$this->tableau_entite_properties[""]["field"] = 'hour int8, day int8, week int4, month int4, '.$this->network_level[0].' text';
                                $this->tableau_entite_properties[""]["specific_field"] = 'hour,day,week,month,'.$this->network_level[0];
					
				if ($this->axe3 != "")
				{	
					$this->tableau_entite_properties[""]["field"] .= ','.$this->axe3.' text' ;
					$this->tableau_entite_properties[""]["specific_field"] .= ','.$this->axe3 ;
					$this->network_level[0] .= "_".$this->axe3 ;
				}
				$this->tableau_entite_properties[""]["group_table"] = $this->id_gt;
				$this->tableau_entite_properties[""]["network"] = $this->network_level[0];
				$this->generic_create_table_w("");
			}
		}
		$this->clean_copy_tables();
	}

        // maj 14/10/2011 - MPR : DE Time Aggregation Bypass 
        // Création de la méthode getInfosFiles pour savoir si le fichier a traité contient des données bypassées ou non
	/**
	 * Fonction qui récupère les fichiers à traiter
         * On vérifie également si la ta concernée est bypassée ou non
	 * 
	 * @param text $hour Heure pour laquelle il faut récupérer les fichiers à traiter
	 * @return array Tableau contenant toutes les informations sur les fichiers à traiter
	 */
	function getInfosFiles( $hour )
	{
		$query = "  SELECT sfful.*, CASE WHEN SPLIT_PART( flat_file_naming_template, '_', 5 ) = ta_bypass THEN ta_bypass ELSE NULL END as ta_bypass
                            FROM sys_definition_flat_file_lib, sys_definition_categorie, sys_flat_file_uploaded_list sfful
                            WHERE hour = {$hour}
                                AND flat_file_naming_template = flat_file_template 
                                AND family = SPLIT_PART( flat_file_template, '_', 3 ) ";
                
                $result = $this->database->execute( $query );
                
                return $this->database->getQueryResults( $result );
 	}
        
        // maj 14/10/2011 - MPR : DE Time Aggregation Bypass - Ajout de la colonne ta_bypass  
        /**
         * Fonction qui récupère les infos de la famille
         * @return array
         */
	function get_infos_categorie()
	{
        // 18/01/2011 BBX
        // BZ 25428 : ajout d'une condition sur le champ on_off
		$query="select rank,network_aggregation_min from sys_definition_categorie WHERE on_off = 1";
		$result = $this->database->execute($query);
		while($row = $this->database->getQueryResults($result,1))
			$infos["rank"][$row["rank"]]=$row["network_aggregation_min"];
			$infos["ta_bypass"][$row["rank"]]=$row["network_aggregation_min"];
		return $infos;
	}

	// retourne l'id group_table du fichier uploadé
	function get_id_group_table_from_uploaded_file()
	{
		$query="select id_group_table 
			from sys_link_filetype_grouptable 
			where 
			flat_file_id=
			(select id_flat_file 
			 from sys_definition_flat_file_lib 
			 where flat_file_naming_template='".$this->uploaded_file["flat_file_template"]."');";
                
		return $this->database->getone($query);
	}

	// retourne le code connection du fichier uploadé
	function get_code_connection_from_uploaded_file()
	{
		$query="select connection_code_sonde
			from sys_definition_connection 
			where 
			id_connection=".$this->uploaded_file["id_connection"];
		return $this->database->getone($query);
	}

	// retourne le nom de la famille pour le group table donné
	function get_family_from_id_gt($id_gt)
	{
		$query="select family from sys_definition_group_table where id_ligne=".$id_gt;
		$resultm = $this->database->execute($query);
		$results= $this->database->getQueryResults($resultm);
		return $results[0]["family"];
	}

	// retourne les network levels pour l'id group table donné
	function get_nas_from_id_gt($family)
	{
		//BUGZ 13264 : ajout de la condition "axe isnull"
                // La correction est ajout de "axe IS NULL" et non "axe isnull"
		$query = "SELECT agregation
			FROM sys_definition_network_agregation
			WHERE family='$family' and axe IS NULL ORDER BY agregation_rank;";
		$resultm = $this->database->execute($query);
		while ($row=$this->database->getQueryResults($resultm,1))
			$results[]=$row["agregation"];
		return $results;
	}

	// vérifie si on doit concaténer le code de la connection au NA
        // 01/03/2011 NSE bz 21007 : ajout du paramètre $na
	function checkConcatNa($family,$na='')
	{
		if(empty($na))
			$na=$this->netfield;
		//CGS 16/10/09
		//BUGZ 12095 : pb concatenation code_connection dans le parser DEF
		$query="select use_prefix from sys_definition_network_agregation where agregation='".$na."' and family='$family'";
		return $this->database->getone($query);
	}

	//vérifie que le header contient bien : 
	// le TA_min au 1er champ, 
	//le NA_min (ou tout NA supérieur) au 2ème champ
	//éventuellement l'axe3 au 3ème champ. 
	function checkHeader()
	{
		exec("head -1 ".$this->uploaded_file["flat_file_location"],$source_header);
		$fields=explode(";",$source_header[0]);
                // récupération des NA pour le group table courant
		//$this->network_levels = $this->get_nas_from_id_gt($this->type);

		// récupération du NA présent dans le fichier
		//$this->get_net_field_from_uploaded_file($this->uploaded_file["flat_file_location"]);

		$this->header=true;

		//si le NA du fichier est différent du NA min paramétré dans l'appli
		if ($this->netfield != $this->network_levels[0])
		{
			sys_log_ast("Critical","Trending&Aggregation","Data Collect",
					"Wrongs header for export file ".$this->uploaded_file["uploaded_flat_file_name"].", bad network level : ".$this->netfield.",".$this->network_levels[0]." expected","support_1");
			displayInDemon("Wrongs header for export file ".$this->uploaded_file["uploaded_flat_file_name"].", bad network level : ".$this->netfield.",".$this->network_levels[0]." expected");
			unset($this->network_level);
			$this->header=false;
		}
		//else $this->network_level[0]=$this->netfield;
		$this->network_level[0]=$this->netfield;

		// si il y a un axe3, vérifier que le 3ème champ dans le fichier est bien l'axe3
		if ( $this->axe3 != "" )
		{
                        // maj 14/10/2011 - MPR : DE Time Aggregation Bypass
                        // On utilise $this->granularity
			if( $fields[0] != $this->granularity or $fields[2] != $this->axe3 )
			{
				sys_log_ast("Critical","Trending&Aggregation","Data Collect",
						"Wrong header for export file ".$this->uploaded_file["uploaded_flat_file_name"].": $fields[0];$fields[1];$fields[2]");
				displayInDemon("Wrong header for export file ".$this->uploaded_file["uploaded_flat_file_name"].": $fields[0];$fields[1];$fields[2]");
				$this->header=false;
			}
		}
		else if ($fields[0]!= $this->granularity )
                {
				sys_log_ast("Critical","Trending&Aggregation","Data Collect",
						"Wrongs header for export file ".$this->uploaded_file["uploaded_flat_file_name"].": $fields[0];$fields[1]","support_1");
				displayInDemon("Wrongs header for export file ".$this->uploaded_file["uploaded_flat_file_name"].": $fields[0];$fields[1]");
				$this->header=false;
                }
	}

	// retourne le NA (2ème champ) présent dans le fichier traité
	function get_net_field_from_uploaded_file($flat_file_location)
	{
		$cmd="head -1 ".$flat_file_location ."| awk -F\";\" '{print $2}' ";
		exec($cmd,$netfield);

		return $netfield[0];
	}

	/**
	 * Fonction qui initialise toutes les données du fichier parser
	 *
	 * @param object $info_uploaded_file fichier à traiter
	 */
	function init( $info_uploaded_file )
	{
		$this->hour = $info_uploaded_file["hour"];
		$this->day = substr($this->hour, 0, 8);
		$this->uploaded_file=$info_uploaded_file;
		$this->ta_min=get_ta_min();
		
                $this->granularity = ( $info_uploaded_file["ta_bypass"] != "" ) ? $info_uploaded_file["ta_bypass"] : $this->ta_min;
                
                
		// 02/04/2010 BBX
		// Suis-je un Mixed KPI ?
		$this->isMixedKpi = MixedKpiModel::isMixedKpi();

		$this->id_gt=$this->get_id_group_table_from_uploaded_file();
		$family=$this->get_family_from_id_gt($this->id_gt);
		$this->type=$family;
		// récupération des NA pour le group table courant
		$this->network_levels = $this->get_nas_from_id_gt($this->type);
		// récupération du NA présent dans le fichier
		$this->netfield=$this->get_net_field_from_uploaded_file($this->uploaded_file["flat_file_location"]);

		//récupération axe3
		$this->axe3=get_network_aggregation_min_axe3_from_family($family);

		$this->checkHeader();
		//	{
		unset($this->todo);
		$this->todo[] = $family;
                
		// propriétés associées à chaque entité
                // Suppression du switch qui sert à rien
                $this->tableau_entite_properties[""]["field"] = 'hour int8, day int8, week int4, month int4, '.$this->network_level[0].' text';
                $this->tableau_entite_properties[""]["specific_field"] = 'hour,day,week,month,'.$this->network_level[0];
		$this->tableau_entite_properties[""]["group_table"] = $this->id_gt;
		//si il y a un axe3, on ajoute le champ dans tableau_entite_properties
		if ( $this->axe3 != "" )
		{
			$this->tableau_entite_properties[""]["network"] = $this->network_level[0]."_".$this->axe3;
			$this->tableau_entite_properties[""]["field"] .= ','.$this->axe3.' text' ;
			$this->tableau_entite_properties[""]["specific_field"] .= ','.$this->axe3 ;
			$this->network_level[0] .= "_".$this->axe3;
		}
		else
			$this->tableau_entite_properties[""]["network"] = $this->network_level[0];
                
                       
                $this->get_cpts_from_sfr("");
        }

	/**
	 * Fonction de remplissage des tables W_temp
	 * 
	 * @param array $tableau_entite_properties tableau contenant les proprietes de chaque entite
	 */
	function create_copy_header($file_upload,$level)
	{
		$new_fields=array();
		$fields_in_source=array();
		$fields_sfr=array();
		$used_fields=array();

		$copy_header = "COPY " . $this->table_name_temp[""][$level]." (".$this->tableau_entite_properties[""]["specific_field"];

		//récupération de la 1ère ligne du fichier source
		exec("head -1 $file_upload",$source_header);
		$fields=explode(";",$source_header[0]);

		if ($this->axe3 != "")
			$start_cpt=3;
		else
			$start_cpt=2;

		//BUG 12180 : compteurs sensibles à la casse
		for($i=$start_cpt;$i<count($fields);$i++)
			$used_fields[strtolower($fields[$i])]=$i;

		// $this->get_cpts_from_sfr();
		
		// BZ 14861
		// 25/03/2010 BBX
		// Modification de la récupération des compteurs.
		// 04/02/2010 BBX
		// Si on est sur un Mixed KPI, il faut utiliser "nms_field_name".
		// Dans les autres cas, il faut utiliser "edw_field_name".
		if($this->isMixedKpi)
		{
			// Les champs du fichier Data Export seront des "nms_field_name"
			foreach ($this->nms_cpts_in_sfr[''] as $edw_field=>$nms_field) 
			{
				$fields_sfr[] = strtolower($nms_field);
				$fields_nms_to_edw[strtolower($nms_field)] = $edw_field;
			}
		}
		else
		{
			// Les champs du fichier Data Export seront des "edw_field_name"
			foreach ($this->nms_cpts_in_sfr[''] as $edw_field=>$nms_field) 
			{
				$fields_sfr[] = strtolower($edw_field);
				$fields_nms_to_edw[strtolower($edw_field)] = $edw_field;
			}		
		}
		// FIN BZ 14861

		// Récupération de tous les compteurs présents dans le fichier source et qui sont dans sys_field_reference
		foreach ($used_fields as $field1 => $cpt)
		{
			if (in_array($field1,$fields_sfr))
			{
				$cpt2=$cpt+1;
				//utile pour savoir quels compteurs prendre dans la commande awk pour construire le corps du copy.
				$copy_body.="\";\"$".$cpt2;
				$fields_in_source[]=$field1; 
				
				// BZ 14861
				// 25/03/2010 BBX
				// Utilisation du tableau $fields_nms_to_edw pour récupérer la valeur de edw_field_name
				$copy_header.=",".$fields_nms_to_edw[$field1];
				// Si la valeur est null (\N) on remplace par la valeur par défaut
				$default_value = $this->cpts_default_value_in_sfr[""][$fields_nms_to_edw[$field1]];
				// FIN BZ 14861
				
				if( strtoupper($default_value) == 'NULL' )	$default_value = '';
				$copy_ctrl.= 'if($'.$cpt2.'=="\\\\N"){$'.$cpt2.'="'.$default_value.'"};';
			}
			else
				$new_fields[]=$field1;
		}

        // 27/01/2011 OJT : correction bz20324, gestion du group table
		$this->update_dynamic_counter_list( $new_fields, null , $this->id_gt );

		//récupération des compteurs présents dans sys_field_reference et absents du fichier source
		$fields_in_sfr_and_not_in_source = array();
		foreach ($fields_sfr as $field2)
		{
			if (!in_array($field2,$fields_in_source))
			{
				// BZ 14861
				// 25/03/2010 BBX
				// Utilisation du tableau $fields_nms_to_edw pour récupérer la valeur de edw_field_name
				$default_value = $this->cpts_default_value_in_sfr[""][$fields_nms_to_edw[$field2]];				
				if( strtoupper($default_value) == 'NULL' )	$default_value = '';
				$copy_body.="\";\"".$default_value;
				$copy_header.=",".$fields_nms_to_edw[$field2];
				// FIN BZ 14861
			}
		}
		$copy_header.=") FROM "; 
		$this->copy_header=$copy_header;
		$this->copy_body=$copy_body;
		$this->copy_ctrl=$copy_ctrl;
	}

	/**
	 * Fonction qui parse le fichier et qui va integrer dans un fichier au format csv les données issues du fichier source
	 * 
	 * @param int $id_fichier numero du fichier à traiter
	 * @global text repertoire physique d'installation de l'application
	 */
	function create_file_query($file_upload,$level)
	{
		global $repertoire_physique_niveau0;

		$week = $this->dateObj->getWeek($this->day);
		$month = substr($this->hour, 0, 6);

		$capture_duration = 3600;
		if(file_exists($file_upload))
		{
			foreach($this->todo as $family)
			{
				$cmd = 'awk -F ";" \'{if(NR>1){ '.$this->copy_ctrl.' print $1';
				switch( $this->granularity )
                                {
					case "hour":
						$cmd.='";"'.$this->day.'";"'.$week.'";"'.$month.'";';
					break;
					case "day":
						// BUGZ 12700 : ajout de la colonne hour 
						// 08/08/2014 GFS - Bug 43352 - [SUP][T&A HPG][#46945][SFR] incorrect data integration on T&A HPG Corpo partitionned with T&A HPG affiliate non-partitionned
						$cmd.='"23;"'.$this->day.'";"'.$week.'";"'.$month.'";';
					break;
				}
                                // 24/10/2011 NSE bz 24350 : on vérifie que le code sonde n'est pas vide avant de préfixer l'élément réseau.
                                $code_connect=$this->get_code_connection_from_uploaded_file();
				// Vérification pour le NA 1° axe
				if(!empty($code_connect)&&$this->checkConcatNa($family))
				{
					$cmd.=$code_connect.'_"$2';
				}
				else
					$cmd.='"$2';
				// 01/03/2011 NSE bz 21007 : on gère l'utilisation du préfixe sur le 3° axe
				if ($this->axe3 != ""){
					// si le NA 3° axe de la famille a 'useprefix' de coché
					if(!empty($code_connect)&&$this->checkConcatNa($family,$this->axe3))
					{   // on ajoute le préfixe
						$cmd.='";'.$code_connect.'_"$3';
					}
					else
					$cmd.='";"$3';
				}
				// fin 01/03/2011 NSE bz 21007
				$query_file=REP_PHYSIQUE_NIVEAU_0.'/upload/copy_' . $this->type . '_' . $level . '_' . $family . uniqid("") .'.sql'; 
				$cmd.=$this->copy_body.'}}\' '.$file_upload.' > '.$query_file;
				exec($cmd,$result);
				$this->copy_file=$query_file; 
			}
		}
		else
			print "Fichier non présent<br>";		
	}

	function exec_copy_query2($copy_file,$level)
	{
		// modification lié au RI
		$time_expected_ri = get_sys_global_parameters("capture_duration"); //Recupere la duree de capture
		$aggreg_net_ri = 1; //precise que les calculs qui sont fait sont des aggregation réau (la source et la cible ayant le meme time aggregation)
		// 10:02 06/03/2009 SCT : bug 8906 => ajout d'un paramèe suppléntaire pour les conditions dans les formules de compteurs
		$integration_level = 1;
		$nb_sondes_ri = get_sys_global_parameters("nb_sources_expected"); //répè le nombre de sondes attendues
		$table_object_ri = "''"; //variable inutile car non utilisee lorsque le network_minimum est egal au niveau network cible. Lors de l'integration des fichiers sources, on est forcént au niveau minimum
		$time_coeff = 1; //au niveau hour, le coeff time vaut 1
		$min_net_ri = "''"; //l'important est que cette variable soit éle ànetwork_ri
		$network_ri = "''";
		// 16/12/2008 MPR - On ajoute le type d'arc pour la fonction ri_calculation
		$arc_type = "''";

		$network_ri_dynamic = "''"; //variable inutile car non utilisee lorsque le network_minimum est egal au niveau network cible. Lors de l'integration des fichiers sources, on est forcént au niveau minimum
		$hour_ri = "hour"; //nom de la colonne qui sert dans la fonction CASE WHEN $aggreg_net_ri=1 THEN ri_calculation_sonde($hour_ri)::float4 ELSE SUM(capture_duration) END prént dans sys_field_reference
		// 16/12/2008 MPR : Ajout de la variable query_ri pour le calcul du ri
		$query_ri = 1;

                // 08/09/2010 BBX
                // DE Bypass : ajout de la variable $network
                $network        = $level;
                $network_source = "'" . $network . "'";

		if( file_exists($copy_file) )
		{
			$query_copy = $this->copy_header . "'" . $copy_file . "' with delimiter ';' NULL AS ''";
			$this->database->execute($query_copy);

			if($this->database->getLastError() != '')
				echo $this->database->getLastError() . " " . $query_copy . ";\n";
			else
			{
				exec("wc -l " . $copy_file . " | awk '{print $1}'", $nb_lignes);
				echo $nb_lignes[0] . " inserted lines for the level $level and granularity ".$this->granularity."<br />";
				$query = "INSERT INTO " . $this->table_name[""][$level] . " (" . $this->tableau_entite_properties[""]["specific_field"] . "," . implode(",", array_keys($this->cpts_aggregated_in_sfr[""])) . ") SELECT " . $this->tableau_entite_properties[""]["specific_field"] . "," . implode(",", $this->cpts_aggregated_in_sfr[""]) . " FROM " . $this->table_name_temp[""][$level] . " GROUP BY " . $this->tableau_entite_properties[""]["specific_field"];
            eval("\$query = \"$query\";");
				// 26/10/2011 MMT Bz 24409 il ne peut y avoir qu'une requete par granularité
				$this->query_insert_in_w_table[ $this->id_gt ][$this->granularity] = $query;
				unlink($copy_file);
			}
		}
	}

}

?>
