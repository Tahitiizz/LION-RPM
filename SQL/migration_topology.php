<?
// maj 15:58 10/07/2009 MPR : Correction du bug 10573 : Tous les messages affichés lors de l'exécution du script sont en anglais
// 12/08/09 CCT1 : traduction de tous les textes français en anglais.
// 11:19 30/10/2009 SCT : mise en commentaire des commandes "file()" sur les fichiers temporaires de migration pour éviter les erreurs lors de migration de tables trop importantes

include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");


exec("rm -f ".$repertoire_physique_niveau0."SQL/demon_migration_topology.html");
// Fonction qui active ou désactive le mode debug
function set_mode_debug($mode){
	global $database_connection;
	
	$query = "UPDATE sys_debug SET value = $mode  WHERE parameters = 'debug_global'";
	pg_query($database_connection,$query);
	
	if($mode == 1)
		demon("Enabling Debug Mode\n<br/>");
	else
		demon("Disabling Debug Mode\n<br/>");
}

// Fonction qui écrit dans un fichier démon
function demon($text,$titre=""){

	global $repertoire_physique_niveau0;
	
	$file_demon = $repertoire_physique_niveau0."SQL/demon_migration_topology.html";
	
	$title = str_replace('"','\"',$titre);
	exec('echo "'.$title.'" >> '.$file_demon);
	
	if(is_array($text)){
		foreach($text as $k=>$v){
			$val = str_replace('"','\"',$v);
			exec('echo "'.$k.'=>'.$val.'<br/>\n" >> '.$file_demon);
		}
	}else{
		$msg = str_replace('"','\"',$text);
		exec('echo "'.$msg.'" >> '.$file_demon);
	}
	
}

function sql($query,$titre=""){

	if(!is_array($query)){
		$msg = $titre.'<pre style="color:#3399ff">'.$query.'</pre><br/>';
	}else{
		$msg = $titre;
		foreach($query as $q){
			$msg.= '<pre style="color:#3399ff">'.$q.'</pre><br/>';
		}
	}
	demon($msg);
}


function drop_tables_object_n_ref(){

	global $database_connection;

	$query = "SELECT object_ref_table
			FROM sys_definition_categorie";
	
	$res = pg_query($database_connection,$query);
	
	while($row = pg_fetch_array($res) ){
	
		$queries[] = "DROP TABLE ".$row['object_ref_table'];
		$queries[] = "DROP TABLE ".substr($row['object_ref_table'],0,-4);
	}
	
	$drop = implode(";",$queries);
	pg_query($database_connection, $drop);
}

// Fonction qui créée les tables de référence
function create_tables_object_ref(){

	global $database_connection;
	
	$queries[] = "CREATE TABLE edw_object_ref
		(
		  eor_date text,
		  eor_blacklisted smallint DEFAULT 0,
		  eor_on_off integer DEFAULT 1,
		  eor_obj_type text,
		  eor_id text,
		  eor_label text
		)
		WITH (OIDS=TRUE);
		ALTER TABLE edw_object_ref OWNER TO postgres";

	// Index
	$queries[] = "CREATE INDEX index_eor_id
				  ON edw_object_ref
				  USING btree
				  (eor_id);";
	$queries[] = "CREATE INDEX index_eor_obj_type
				  ON edw_object_ref
				  USING btree
				  (eor_obj_type);";
	$queries[] = "CREATE INDEX index_eor_on_off
				  ON edw_object_ref
				  USING btree
				  (eor_on_off);";
	// 04/06/2009 BBX : ajout d'un index sur les labels
	$queries[] = "CREATE INDEX index_eor_label
				  ON edw_object_ref
				  USING btree
				  (eor_label);";
	
	//////////
	$queries[] = "CREATE TABLE edw_object
		(
		  eo_date text,
		  eo_obj_type text,
		  eo_id text
		)
		WITH (OIDS=TRUE);
		ALTER TABLE edw_object OWNER TO postgres";
	
	// Index sur l'id de l'élément réseau
	$queries[] = "CREATE INDEX index_eo_id
				  ON edw_object
				  USING btree
				  (eo_id);";
	
	// Index sur le niveau d'agrégation réseau
	$queries[] = "CREATE INDEX index_eo_obj_type
				  ON edw_object
				  USING btree
				  (eo_obj_type);";

	/////////////
	$queries[] = "CREATE TABLE edw_object_ref_parameters
		(
			eorp_id text,
			eorp_delete_counter integer DEFAULT 0,
			eorp_x double precision,
			eorp_y double precision,
			eorp_azimuth integer, 
			eorp_longitude double precision,
			eorp_latitude double precision
		)
		WITH (OIDS=TRUE);
		ALTER TABLE edw_object_ref_parameters OWNER TO postgres";
	
	// Index sur l'id de l'élément réseau
	$queries[] = "CREATE INDEX index_eorp_id
				  ON edw_object_ref_parameters
				  USING btree
				  (eorp_id);";
	
	///////////////
	$queries[] = "CREATE TABLE edw_object_arc_ref
		(
		  eoar_id text,
		  eoar_id_parent text,
		  eoar_arc_type text
		
		)
		WITH (OIDS=TRUE);
		ALTER TABLE edw_object_arc_ref OWNER TO postgres";
	
	// Index sur l'id de l'élément réseau
	$queries[] = "CREATE INDEX index_eoar_id
				  ON edw_object_arc_ref
				  USING btree
				  (eoar_id)";
				  
	// Index sur l'id de l'élément réseau parent
	$queries[] = "CREATE INDEX index_eoar_id_parent
				  ON edw_object_arc_ref
				  USING btree
				  (eoar_id_parent)";
				  
	// Index sur le type d'arc
	$queries[] = "CREATE INDEX index_eoar_arc_type
				  ON edw_object_arc_ref
				  USING btree
				  (eoar_arc_type)";
	
	/////////////
	// Création de la table temporaire edw_object_arc 
	$queries[] = "CREATE TABLE edw_object_arc
		(
		  eoa_id text,
		  eoa_id_parent text,
		  eoa_arc_type text
		
		)
		WITH (OIDS=TRUE);
		ALTER TABLE edw_object_arc OWNER TO postgres";
	
	// Index sur l'id de l'élément réseau
	$queries[] = "CREATE INDEX index_eoa_id
				  ON edw_object_arc
				  USING btree
				  (eoa_id)";

	// Index sur l'id de l'élément réseau parent
	$queries[] = "CREATE INDEX index_eoa_id_parent
				  ON edw_object_arc
				  USING btree
				  (eoa_id_parent)";

	// Index sur le type d'arc
	$queries[] = "CREATE INDEX index_eoa_arc_type
				  ON edw_object_arc
				  USING btree
				  (eoa_arc_type)";
	

	// Création de la table qui insère la correspondance entre le header d'un fichier et les niveaux d'agrégation en base
	$queries[] = "
	CREATE TABLE edw_object_ref_header (
		eorh_id_column_db TEXT,
		eorh_id_column_file TEXT,
		eorh_id_produit TEXT
	)
	";
	
	$queries[] = "TRUNCATE edw_object_ref_header";
	$queries[] = "
	COPY edw_object_ref_header (eorh_id_produit, eorh_id_column_db, eorh_id_column_file)
	FROM '".dirname(__FILE__)."/topo_ref.csv' 
	WITH DELIMITER ';' NULL ''";
	
	// sql($queries[0],"<b>Création de la table edw_object_ref : </b>");
	// sql($queries[3],"<b>Création de la table edw_object_ref_parameters : </b>");
	// sql($queries[6],"<b>Création de la table edw_object_arc_ref : </b>");
	
	demon($queries,"queries");
	
	$res = @pg_query( $database_connection, implode(";",$queries) );
	if($res){
		demon("<hr/>Creating tables ok<hr/>");
	}else{
		demon("<h3 style='color:#fff;background-color:#f00'>Creating table, SQL error - Tables already exist or query error </h3>");
		demon(pg_last_error($database_connection));
		echo "SQL Error - Tables already exist or problem of query\n";
		
	}
	
}


// Fonction qui nettoie les tables object_ref
function clean_object_ref(){
	global $database_connection;
	
	$query = "TRUNCATE edw_object_ref;TRUNCATE edw_object_ref_parameters;TRUNCATE edw_object_arc_ref;";
	$res = pg_query( $database_connection, $query );
	
}


// Fonction qui qui insère les données de la famille principale ( La famille principale contient "en principe" toutes les données topologiques des familles secondaires) 
function insert_data_main_family(){

	global $database_connection;
	
	$main_family = get_main_family();
	$net_min = get_network_aggregation_min_from_family( $main_family );
	$object_ref = get_object_ref_from_family($main_family);
	$lst_na = getNaLabelList('all',$main_family);
	
	foreach($lst_na as $family => $tab_na){
	
		foreach($tab_na as $na=>$na_label){
		
			// Insertion des données dans edw_object_ref
			$select = "eor_date, eor_blacklisted, eor_obj_type , eor_id , eor_label";
			$insert = "date, blacklisted, '$na', $na, ".$na."_label";
			if($na == net_min){
				$select.= ", eor_on_off";
				$insert.= ",on_off";
			}
			
			$queries[] = "INSERT INTO edw_object_ref ( $select) 
							SELECT DISTINCT on ($na) $insert FROM $object_ref WHERE $na IS NOT NULL AND $na != ''";
			if( $na == $net_min ){
				$queries[] = "INSERT INTO edw_object_ref_parameters ( eorp_id, eorp_delete_counter, eorp_x , eorp_y , eorp_azimuth, eorp_longitude, eorp_latitude ) 
									SELECT DISTINCT ON ($na) $na, delete_counter, x , y ,azimuth, longitude, latitude FROM $object_ref WHERE $na IS NOT NULL AND $na != ''";
			}
		}
	}
	
	$query = "SELECT DISTINCT level_source, agregation FROM sys_definition_network_agregation WHERE agregation <> level_source and family = '$main_family' ORDER BY level_source, agregation";

	$res = pg_query ($database_connection, $query);
	
	while( $row = pg_fetch_array($res) ){
		
		$id_na = $row['level_source'];
		$id_na_parent = $row['agregation'];
		
		$queries[] = "INSERT INTO edw_object_arc_ref (eoar_id, eoar_id_parent,eoar_arc_type) 
		SELECT DISTINCT on ($id_na) $id_na, $id_na_parent, '$id_na' || '|s|' || '$id_na_parent' 
		FROM $object_ref 
		WHERE $id_na_parent IS NOT NULL and $id_na IS NOT NULL
			 AND $id_na_parent != '' and $id_na != ''
		";
		
	}
	
	demon("<pre style='color:#3399ff'>".implode("<br/>",$queries)."</pre>");
	// demon((implode("<br/>",$queries),"queries insert main family");
	
	$res = pg_query($database_connection, implode(";",$queries));
	sql(implode("\n",$queries));
	
	// maj 07/07/2009 - MPR : Correction du bug 10382 - Conversion des coordonnées x et y en longitude latitude si cela n'a pas été effectué dans la migration 3.0 vers 4.0
	// Récupération du srid de la région géographique
	$query = "SELECT srid FROM sys_gis_config_global LIMIT 1";
	$res = pg_query($database_connection, $query);
	
	while($row = pg_fetch_array($res)){
	
		// On migre les coordonnées uniquement si le srid est différent de null
		if($row['srid'] !== '' and $row['srid'] !== null){
			
			demon("Conversion of x/y coordinates in longitude/latitude.");
			// Conversion des coordonnées x et y en longitude et latitude
			$query = "UPDATE edw_object_ref_parameters 
						SET eorp_longitude = x(AsEWKT(Transform(GeomFromEWKT(geomfromtext('POINT('||eorp_x||' '||eorp_y||')', {$row['srid']})), 4326))),
						eorp_latitude = y(AsEWKT(Transform(GeomFromEWKT(geomfromtext('POINT('||eorp_x||' '||eorp_y||')', {$row['srid']})), 4326)))
						WHERE eorp_x IS NOT NULL AND eorp_y IS NOT NULL AND eorp_longitude IS NULL AND eorp_latitude IS NULL;";
			
			$res = pg_query($database_connection, $query);
			sql( pg_affected_rows($res)." lignes = $query\n");			
		}
	}
	
	if($res){
	
		demon("Topology migration : main family Ok <br/>\n");
		
	}else{
		demon("<h3 style='color:#fff;background-color:#f00'>Topology migration : main family, SQL error - No Topology Data of the family has been migrated.</h3><br/>\n");
		echo "SQL Error - No Topology Data of the family has been migrated\n";

	}
}


// Fonction qui intègre les autres données topologiques 
function insert_data_other_families(){

	global $database_connection;
	
	$main_family = get_main_family();
	$table_ref = "edw_object_ref";
	$table_ref_arc = "edw_object_arc_ref";
	
	$query = "SELECT DISTINCT agregation, family, level_source 
			  FROM sys_definition_network_agregation 
			  WHERE family <> '$main_family'";
	sql($query,"query other families");

	$res = pg_query($database_connection,$query);
	
	while ($row = pg_fetch_array($res) ){
	
		$object_ref = get_object_ref_from_family($row['family']);
		$na 		= $row['agregation'];
		$na_child 	= $row['level_source'];
		
		// Insertion des données dans la table edw_object_ref
		$queries[] = "INSERT INTO edw_object_ref (eor_date, eor_blacklisted, eor_obj_type , eor_id , eor_label ) 
							SELECT DISTINCT on ($na ) obj_ref.date, obj_ref.blacklisted, '$na', obj_ref.$na, obj_ref.".$na."_label 
							FROM $object_ref obj_ref LEFT JOIN $table_ref ON $na = eor_id and eor_obj_type = '$na'
							WHERE eor_id IS NULL and $na IS NOT NULL and $na != ''";	
			
		//Insertion des données dans la table edw_object_arc_ref
		if( $na !== $na_child ){
			$queries[] = "INSERT INTO edw_object_arc_ref (eoar_id, eoar_id_parent, eoar_arc_type) 
			SELECT DISTINCT on ($na_child) $na_child, $na , '$na_child' || '|s|' || '$na'
			FROM $object_ref obj_ref LEFT JOIN $table_ref_arc ON eoar_id = obj_ref.$na_child 
			WHERE eoar_id IS NULL AND $na IS NOT NULL AND $na_child IS NOT NULL
			AND $na != '' AND $na_child != ''";
			
		}	
	}
		
	demon("<pre style='color:#3399ff'>".implode("<br/>",$queries)."</pre>");
	
	$res = pg_query($database_connection,implode(";",$queries) );
	if($res){
		demon("Topology migration : secondary families Ok<br/>");
	}else{
		demon("<h3 style='color:#fff;background-color:#f00'>Topology migration : secondary families, SQL error - No Topology Data of the family has been migrated.</h3><br/>");
		echo "SQL Error - No Topology Data has been migrated";
	}
	
	
}


// Fonction qui récupère le nom des colonnes d'une table 
function set_columns($table, $_na, $lst_na){

	global $database_connection;

	$query = "SELECT a.attname as champs, 

		      CASE WHEN (SELECT substring(pg_catalog.pg_get_expr(d.adbin, d.adrelid) for 128) 
						FROM pg_catalog.pg_attrdef d WHERE d.adrelid = a.attrelid AND d.adnum = a.attnum AND a.atthasdef
						) ~ '^nextval' 
				  THEN 'serial' 
				  ELSE pg_catalog.format_type(a.atttypid, a.atttypmod) END as type

			  FROM pg_catalog.pg_attribute a 

			  WHERE a.attrelid IN (
					SELECT c.oid FROM pg_catalog.pg_class c 
					LEFT JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace 
					WHERE c.relname ~ '^$table$' 
						AND pg_catalog.pg_table_is_visible(c.oid) ORDER BY 1
				) 

				AND a.attnum > 0 

				AND NOT a.attisdropped 

				AND a.attname <> 'the_geom' 

			 ";
	
			 
	$res = pg_query($database_connection,$query);
	
	$columns = array();
	$tab_columns = array();
	$id_column = 1;
	
	while( $row = pg_fetch_array($res) ){

		if( $row['champs'] == $_na or in_array( $row['champs'], $lst_na ) )
		{
			$tab = explode("_", $row['champs']);
			
			if(!in_array( $tab[0], $tab_columns)){
				$tab_columns[] = $tab[0];
				$columns[$id_column] = $tab[0];
			}
			
			$id_column++;
			
			if(!in_array($tab[1],$tab_columns)){
				$tab_columns[] = $tab[1];
				$columns[$id_column] = $tab[1];
			}
			
			$id_column++;
		} 
		else 
		{
			 $columns[$id_column] = $row['champs'];
			 $id_column++;
		}
	}

	return $columns;
}

function maj_sdna(){
	
	global $repertoire_physique_niveau0,$database_connection;
	
	$query = "ALTER TABLE sys_definition_network_agregation ADD COLUMN na_max_unique smallint;";
	pg_query($database_connection,$query);

	$query = "ALTER TABLE sys_definition_network_agregation ADD COLUMN na_parent_unique smallint DEFAULT 1;";
	pg_query($database_connection,$query);
	
}

// Fonction qui récupère les tables de données à migrer
function get_tables(){

	global $repertoire_physique_niveau0,$database_connection;
	
	
	demon("<h3>".date('d/m/Y h:m:s')." - Beginning migration of data tables</h3>");
	
	$_module = strtolower(get_sys_global_parameters("module") );
	
	$query = "SELECT relname FROM pg_class WHERE relname like 'edw_$_module%' order by relname ";
	// On récupère toutes les tables de données à migrer
	// Migration des tables de données faisant partie d'une famille 3ème axe
	$query = "	SELECT DISTINCT g.id_ligne, edw_group_table, time_agregation, network_agregation,g.family, t.data_type

				FROM sys_definition_group_table g, sys_definition_group_table_time t, sys_definition_network_agregation n1,  sys_definition_group_table_network n2

				WHERE g.id_ligne = n2.id_group_table 
					AND g.family = n1.family 
					AND g.id_ligne = t.id_group_table 
					AND t.on_off = 1
					AND axe = 3
				ORDER BY edw_group_table, network_agregation, time_agregation asc, t.data_type
			 ";
	
	
	sql($query);
	
	$res = pg_query($database_connection, $query);
	if($res){
		if( pg_num_rows($res) > 0 )
		{
			$tables = pg_fetch_all($res);			
		} else {	
			$tables = array();
		}
	}
	else
		$tables = array();
		
	return $tables;
}


// Fonction qui récupère les données dans un fichier temp.mig
function copy_data_into_temp_file($table,$file_source,$header,$file_cible,$network_agregation,$table_min,$tab_na,$tab_na_3,$family)
{
	global $database_connection;

	$_na = explode("_",$network_agregation);
	
	/*
	demon($table_min,"table_min");
	$query = "ALTER TABLE $table DROP COLUMN ".$_na[0];
	@pg_query($database_connection,$query);
	$query = "ALTER TABLE $table DROP COLUMN ".$_na[1];
	@pg_query($database_connection,$query);
	*/
	
	$query = "COPY $table
			TO '$file_source'
			WITH DELIMITER ';'
			 NULL AS ''
			";
	sql($query);
	pg_query($database_connection, $query);

	// Ajout le colonne 1 axe et son index
	$queries[] = "ALTER TABLE $table ADD COLUMN ".$_na[0]." text";
	//$queries[] = "CREATE INDEX ix_".uniqid('')." ON $table USING btree(".$_na[0].")";
	// Ajout le colonne 3 axe et son index
	$queries[] = "ALTER TABLE $table ADD COLUMN ".$_na[1]." text";
	//$queries[] = "CREATE INDEX ix_".uniqid('')." ON $table USING btree(".$_na[1].")";
	
	
	$lst_na[] = $_na[0];
	$lst_na[] = $_na[1];
	
	if( $table_min )
	{
		foreach( $tab_na[$family] as $na => $na_label )
		{
			if( !in_array( $na, $lst_na) )
			{
				$query = "ALTER TABLE $table DROP COLUMN ".$na;
				@pg_query($database_connection,$query);
				$queries[] = "ALTER TABLE $table ADD COLUMN ".$na." text";
				//$queries[] = "CREATE INDEX ix_".uniqid('')." ON $table USING btree(".$na.")";
				sql("ALTER TABLE $table ADD COLUMN ".$na." text");
				$lst_na[] = $na;
			}
		}
		
		foreach($tab_na_3[$family] as $na_axe3 => $label_axe3)
		{
			if( !in_array($na_axe3, $lst_na) )
			{
				$query = "ALTER TABLE $table DROP COLUMN ".$na_axe3;
				@pg_query($database_connection,$query);
				$queries[] = "ALTER TABLE $table ADD COLUMN ".$na_axe3." text";
				//$queries[] = "CREATE INDEX ix_".uniqid('')." ON $table USING btree(".$na_axe3.")";
				$lst_na[] = $na_axe3;
				sql("ALTER TABLE $table ADD COLUMN ".$na_axe3." text");
			}
		}
	}
	
	demon( implode("<br />",$queries) );
	pg_query($database_connection, implode(";",$queries));
	
	return $lst_na;
	
	
}


// Fonction qui supprime les colonnes inutiles
function maj_structure_data_table($table,$network_agregation)
{
	global $database_connection;
	
	$query = "ALTER TABLE $table DROP COLUMN $network_agregation";
	@pg_query($database_connection,$query);
}


// Fonction qui copie les données en base
function copy_data_into_table($table,$header,$file_cible, $lst_na, $lst_na_2)
{
	global $database_connection;

	demon($header."<br/>");

	$indexes = get_indexes($table);
	// Supprime les index
	if ( count($indexes) > 0 )
	{
		foreach(array_keys($indexes) as $index )
		{
			pg_query($database_connection, "DROP INDEX $index");
		}
	}
	
	// $query = "TRUNCATE $table";
	// pg_query($database_connection,$query);
	
	$query = "BEGIN;TRUNCATE $table;";
	$query.= "COPY $table ($header)
			FROM '$file_cible'
			WITH DELIMITER ';'
			 NULL AS '';COMMIT;
			";
		
	demon("<br/>$query");
	sql($query);
	$res = pg_query($database_connection,$query);
	
	// Recrée  les index
	if ( count($indexes) > 0 )
	{
		foreach($indexes as $index )
		{
			foreach ( $lst_na as $na )
			{
				if ( ereg("\(".$na, $index) )
				{
					$index = str_replace("(".$na,"(".str_replace('_', ',', $na), $index);
				}
			}
			pg_query($database_connection, $index);
		}
	}
	
	foreach ( $lst_na_2 as $na )
	{
		pg_query($database_connection, "CREATE INDEX ix_".uniqid('')." ON $table USING btree(".$na.")");
	}
	
	
	if($res){
	  return true;
	}else{
	  demon("table $table not migrated.\n\n");
	  return false;
	}
}

// Fonction qui vérifie si le séparateur 3ème axe est bien présent
function check_associations($file_source)
{
	$awk = 'BEGIN { FS=";" ; OFS=";" }{ if( index($1,"|s|")==null ) print $1 }';
	exec("cat $file_source |awk '$awk'", $tab);
	
	$check = true;
	if( count($tab) > 0 ) {
		
		$check = false;
	}
	
	return $check;
	
}

// Fonction qui éclate la colonne na |s| na_axe3 en deux colonnes na et na_axe3
function split_na_axe3($file_source,$file_cible,$string,$replace,$nbCols = null)
{
	// Si on a qu'une seule colonne on est sur le niveau d'aggrégation possède une valeur
	// (cas où on n'est pas sur une table de niveau minimum)
	if ( $nbCols === null || $nbCols == 1)
	{
		$cmd = "sed < $file_source 's/$string/$replace/g' > $file_cible";
	}
	else // cas où on est sur table de niveau minimum soit les tables raw/hour
	{
		$cmd = "awk 'BEGIN{FS=\";\"; OFS=\";\"} {";
		for ( $i = 1; $i <= $nbCols; $i++ )
		{
			$cmd .= "if(index(\$$i, \"|s|\")){sub(/\\|s\\|/,\";\", \$$i)}else{\$$i=\";\"};";
		}
		$cmd .= "print \$0}' $file_source > $file_cible";
	}
	
	demon($cmd,"spli_na_axe3");
	demon("file $file_cible generated...\n<br/>");
	exec($cmd);
	// 11:16 30/10/2009 SCT : mise en commentaire car risque d'erreur dans le cas de l
	//$file1 = file($file_source);
	//$file2 = file($file_cible);
}

 function get_min_time_level($group_id, $data_type)
{
	global $database_connection;
	
	$query = "select time_agregation from sys_definition_group_table_time
				where id_group_table='$group_id' and data_type='$data_type'
				and id_source in
				(select min(id_source) from sys_definition_group_table_time
				 where id_group_table='$group_id'
				 and data_type='$data_type')";
	$res = pg_query($database_connection, $query);
	while ($row = pg_fetch_array($res))
	$level = $row[0];
	return $level;
}

/**
* retourne le plus bas niveau d'agrégation de network souhaité pour le group_table $group_table de type $data_type (raw, kpi, ...), pris dans sys_definition_group_table_network
 *
 * @param int $group_id
 * @param string $data_type
 * @return string
 */
function get_min_network_level($group_id, $data_type)
{
	global $database_connection;
		
	$query = "select network_agregation
				from sys_definition_group_table_network
				where id_group_table='$group_id'
				and data_type='$data_type'";
	$query .= "order by rank limit 1";

	$res = pg_query($database_connection, $query);
	while ($row = pg_fetch_array($res)){
		$level = $row['network_agregation'];
	}
	return $level;
}


function get_lst_na($id_group_table){
	global $database_connection;
	
	$query = "SELECT DISTINCT network_agregation FROM sys_definition_group_table_network WHERE id_group_table = $id_group_table";
	
	$res = pg_query($database_connection,$query);
	
	while($row = pg_fetch_array($res) ){
		$lst_na[] = $row['network_agregation'];
	}

	return $lst_na;
}


// Fonction qui migre des tables de données
function migration_data_tables()
{
	global $repertoire_physique_niveau0,$database_connection;
	
	demon("<hr><ul>Migration of data tables");
	print("  migration of data tables.\n");
	
	// On récupère toutes les tables à migrer
	$tables = get_tables();

	// exec("rm -f ".$repertoire_physique_niveau0."SQL/*.mig");
	if( count($tables) > 0 ) {
		foreach( $tables as $row )
		{
			$family = $row['family'];
			
			$table = $row['edw_group_table']."_".$row['data_type']."_".$row['network_agregation']."_".$row['time_agregation'];
		
			$id_group_table = $row['id_ligne'];
			
			$tab_na = getNaLabelList("na",$family);
			$tab_na_3 = getNaLabelList("na_axe3",$family);
			$lst_na = get_lst_na($id_group_table);
			
			$_na = explode("_",$row['network_agregation']);

			echo "	- $table\n";
			demon("<br/><li>$table</li><br/>");

			$columns = set_columns($table,$row['network_agregation'],$lst_na);

			$header = implode(",",$columns);
			$header2 = str_replace(",", ";", $header);
			
			// Préparation des fichiers .mig
			$file_source = $repertoire_physique_niveau0."SQL/migration_data_".$table."_temp.mig";
			$file_temp = $repertoire_physique_niveau0."SQL/migration_data_".$table."_temp_2.mig";
			$file_cible  = $repertoire_physique_niveau0."SQL/migration_data_".$table.".mig";
			prepare_files($file_source,$file_cible);
			
			// On vérifie que l'on est bien sur la table minimum 
			$table_min = false;
			
			if( $row['network_agregation'] == get_min_network_level($id_group_table,$row['data_type']) and $row['time_agregation'] == get_min_time_level($id_group_table, $row['data_type']) and $row['data_type'] == 'raw' ){
				$table_min = true;
			}
			
			// Récupération des données dans un fichier temp.mig
			$lst_na_2 = copy_data_into_temp_file($table,$file_source,$header2,$file_cible,$row['network_agregation'],$table_min,$tab_na,$tab_na_3,$family);
			
			// On vérifie que l'on doit traiter la table 
			$file_tmp = str_replace("/","\/",$file_source);
			$cmd = "wc -l $file_source | sed 's/ $file_tmp//'";
			
			unset($nb_lignes_tmp);
			
			exec($cmd,$nb_lignes_tmp);
			$nb_lignes = intval($nb_lignes_tmp[0]);

			demon( "<hr>" );
			
			// On vérifie que le fichier n'est pas vide
			if($nb_lignes > 0 )
			{
				// On vérifie qu'il y a bien un séparateur 3ème axe ( '|s|' ) sur le niveau d'agrégation de la famille 
				if( check_associations($file_source) == false )
				{
					demon("Error in the table $table...\n\n<br/> The field ".$row['network_agregation']." does not contain third axis separator ( '|s|' )...\n<br/>");
			
					correct_table_without_separator_3rd_axis($table,$file_source);
				}

				// Compte le nombre de colonne contenant des niveaux d'agrégation
				$numberColNa = 1;
				if ( $table_min )
					$numberColNa = count($lst_na);
				
				// on éclate la colonne na |s| na_3ème axe en deux colonnes
				split_na_axe3($file_source,$file_temp,"|s|",";", $numberColNa);
				
				if( $table_min )
				{
					$id_columns = array();
					
					foreach($columns as $id=>$column)
					{
						if(!in_array($id,$id_columns))
						{
							if(is_array($id))
								$id_columns[] = $id[0];
							else
								$id_columns[] = $id;
						}
					}
					
					cut_columns($file_temp,$file_cible,$file_tmp, $id_columns);
				} 
				else 
				{
					$file_cible = $file_temp;
				}

				// Copie des données en base			
				$res = copy_data_into_table($table,$header,$file_cible, $lst_na, $lst_na_2);
				
			}
			
			// Suppression de la colonne na_naAxe3
			if( $table_min )
			{
				foreach($lst_na as $_na)
				{
					maj_structure_data_table($table,$_na);
				}
			}
			else
			{
				maj_structure_data_table($table,$row['network_agregation']);
			}
		
			// modif 14:37 19/11/2008 GHX
			// Suppression des fichiers temporaires 
			exec("rm -f ".$repertoire_physique_niveau0."SQL/*.mig*");
		}
		
		demon("</ul>");
		echo "Migration of Data Table[Ok]\n";
	}else{
		
		echo "  no Family with 3rd axis.\n";
	}
}

//
function cut_columns($file_source, $file_cible, $file_cible_tmp, $id_columns){

	$columns = implode(",",$id_columns);
	demon($columns,"get columns");
	
	$cmd = "cut -d';' -f$columns $file_source > $file_cible_tmp";
	demon($cmd,"cmd");
	exec($cmd);

	
	exec("mv $file_cible_tmp $file_cible");
	
	// 16:06 06/11/2009 : MPR  - Correction du bug 12246 - Mise en commentaire des fonctions file() - Erreur php sur fichiers contenant une volumétrie de données trop importante 
	// $file = file($file_cible);
	// $file_result = str_replace("\/","/",$file_cible);
	// $file = file($file_result);
	// demon(implode("<br/>",$file), "<hr>FILE RESULT<br/>");
	
}
// Fonction qui ajoute le séparateur 3ème axe lorsqu'il est absent dans une table de données
function correct_table_without_separator_3rd_axis($table,$file_source)
{

	demon("Correcting Error on $table - Add 3rd axis separator on the Network Agregation Level\n<br/>");
	echo("-> Correcting Error in the table $table - Add 3rd axis separator on the Network Agregation Level\n");
	
	exec("cp $file_source ".$file_source."_tmp");
	exec("cat ".$file_source."_tmp |awk 'BEGIN { FS=\";\" ; OFS=\";\" }{ if( index($1,\"|s|\")==null ) $1=$1\"|s|\"; print $0 }' > $file_source");
	
	exec("rm -f ".$file_source."_tmp");
	demon("update Ok...\n\n<br/>");

}


// Fonction qui Migre les tables edw_object_n_ref
function migration_edw_object_ref()
{
	global $database_connection;
	
	demon("<h3>".date('d/m/Y h:m:s')." - Beginning migration of topology tables (edw_object_ref*)</h3>");
	print("  migration of topology tables (edw_object_ref tables).");

	$deb_1 = getmicrotime();

	demon("<h4>Creating new topology tables : edw_object_ref, edw_object_ref_parameters</h4>");
	
	// Création des tables 
	create_tables_object_ref();
	
	// Nettoyage des tables 
	clean_object_ref(); // ???? A quoi ca sert, les tables sont vide à la création ?????
	
	$fin_1 = getmicrotime();
	demon("<h5>Exécution time : ".round ($fin_1 - $deb_1,3)." sec</h5>");

	demon("<h4>Topology migration : main family.</h4>");
	
	// Migration des données de la famille principale 
	$deb_2 = getmicrotime();
	insert_data_main_family();
	$fin_2 = getmicrotime();

	demon("<h5>Execution time : ".round ($fin_2 - $deb_2,3)." sec</h5>");

	demon("<h4>Topology migration : secondary families.</h4>");
	
	// Migration des autres données topologiques
	$deb_3 = getmicrotime();
	insert_data_other_families();
	$fin_3 = getmicrotime();
	
	/*
		17/11/2008 GHX (cf BAC)
		Afin de préparer l’utilisation du mapping de la topologie, j’ai modifié sur la base « cb41000_iu40014_dev3 » du serveur 3 la table edw_object_ref 
		en ajoutant le champ eor_id_codeq qui définit l’identifiant de l’élément de référence sur la base « master ». 
		Par souci de performances, ce champ est indexé
	*/
	$query = "
			ALTER TABLE edw_object_ref ADD COLUMN eor_id_codeq text;
			CREATE INDEX index_eor_id_codeq ON edw_object_ref USING btree(eor_id_codeq);
	";
	pg_query($database_connection, $query);
	
	demon("<h5>Execution time : ".round ($fin_3 - $deb_3,3)." sec</h5>");
	echo "[Ok]\n";
}


// Fonction qui migre les résultats d'alarmes statiques, dynamiques et top-worst
function migration_alarm_results()
{

	global $database_connection, $repertoire_physique_niveau0;
	
	demon("<h3>".date('d/m/Y h:m:s')." - Starting alarm resultuts migration</h3>");
	print("  migration of alarm results tables.");
	
	$deb_1 = getmicrotime();
	
	$alarm_tables = array("sys_definition_alarm_static","sys_definition_alarm_dynamic","sys_definition_alarm_top_worst");
	$alarm_types = array("static","dynamic","top-worst");
	
	$tables_results = array("edw_alarm","edw_alarm_log_error");
	
	$column_type = array("alarm_type","type");

	$sub_query = "SELECT DISTINCT family FROM sys_definition_network_agregation WHERE axe = 3";
	
	$header = array(
	"id_alarm, id_result, ta, ta_value, na, a3, na_value, a3_value, alarm_type, rank_alarm, rank, critical_level, calculation_time, visible",
	"id_alarm, ta, ta_value, na, a3, nb_result, type,a3_value,critical_level,calculation_time");
	
	$select = array(
	"id_alarm, id_result, ta, ta_value, na, na_value, alarm_type, rank_alarm, rank, critical_level, calculation_time, visible",
	"id_alarm, ta, ta_value, na, nb_result, type,a3_value,critical_level,calculation_time"
	);
	

	
	foreach( $alarm_tables as $k=>$table ){
		
		foreach($tables_results as $key=>$table_res){
			// migration de la table edw_alarm
			$type = $alarm_types[$k];
			
			demon("<h3>".date('d/m/Y h:m:s')." Migration of $type alarm in $table_res</h3>");
			// On réordonne les colonnes afin de faciliter la migration des données na => na_axe1 et a3 => na_axe3 / na_value => na_value_axe1 et a3_value => na_value_axe3  
			
			
			// Sélection des résultats d'alarmes 3ème axe
			$query = "SELECT $select[$key] FROM $table_res WHERE id_alarm IN (
							SELECT DISTINCT alarm_id FROM $table 
							WHERE family IN ( $sub_query)
							) AND $column_type[$key] = '$type'
					 ";
					 		 
			// Préparation des fichiers
			$file_tmp  = $repertoire_physique_niveau0."SQL/migration_".$table_res."_".$type."_temp.mig";
			$file_tmp2 = $repertoire_physique_niveau0."SQL/migration_".$table_res."_".$type."_temp2.mig";
			$file_cible = $repertoire_physique_niveau0."SQL/migration_".$table_res."_".$type.".mig";
			prepare_files($file_tmp,$file_cible,$file_tmp2);
			
			// On copie les résultats dans un fichier
			$copy = "COPY ( $query ) TO '$file_tmp' WITH DELIMITER AS ';' NULL AS '' ";
			
			demon($copy."<hr>");
			
			$res = pg_query($database_connection,$copy);
			
			if(pg_cmdtuples($res)>0){
				
				demon("Traitement des alarmes $type\n<br>\n<br>");
				// 13/08/09 GHX correction bug bZ 11005
				if($table_res == "edw_alarm"){
					// On éclate na en deux colonnes
					split_na_axe3($file_tmp,$file_tmp2,"\\(;[^;]*\\)_\\([^;]*;\\)","\\1;\\2");

					// On éclate na_value en deux colonnes
					split_na_axe3($file_tmp2,$file_cible,"|s|",";");
				}else{
					split_na_axe3($file_tmp,$file_cible,"\\(;[^;]*\\)_\\([^;]*;\\)","\\1;\\2");
				}
				
				// Utilisation du header pour intégrer dans la sélection du COPY les champs a3 et a3_value
				$query = "DELETE FROM $table_res WHERE id_alarm IN (
							SELECT DISTINCT alarm_id FROM $table 
							WHERE family IN ( $sub_query)
							) AND $column_type[$key] = '$type'";
				pg_query($database_connection, $query);
				
				$query = "COPY $table_res($header[$key]) FROM '$file_cible' WITH delimiter as ';' NULL as '' ";
				sql($query);
				pg_query($database_connection, $query);
				demon("Migrated data\n<br>");
				
			}
		}
	}
	
	$fin_1 = getmicrotime();

	demon("<h5>Execution time : ".round ($fin_1 - $deb_1,3)." sec</h5>");
	echo "[Ok]\n";
}

// Fonction qui attribue tous les droits sur le fichier pour l'utilisateur astellia
function prepare_files($file_source,$file_cible,$file_tmp2="") 
{
	global $repertoire_physique_niveau0;
	
	exec("touch $file_source");
	exec("touch $file_cible");
	if($file_tmp2 !== ""){
		exec("touch $file_tmp2");
	}
	exec("chmod 777 ".$repertoire_physique_niveau0."SQL/*.mig");
	exec("chown astellia:astellia ".$repertoire_physique_niveau0."SQL/*.mig");
	
}

// Retourne les index d'une table
function get_indexes($table)
{
	global $database_connection;
	
	$sql = "SELECT indexname, indexdef from pg_indexes where tablename='$table'";
	$result = pg_query($database_connection, $sql);
	
	$indexes = array();
	if ( pg_num_rows($result) > 0)
	{
		while(list($indexname, $indexdef) = pg_fetch_row($result) )
		{
			$indexes[$indexname] = $indexdef;
		}
	}
	
	return $indexes;
}

// ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- //

set_mode_debug(1);

echo "  start of the Migration - ".date('d/m/Y H:i:s')."\n";
$deb = getmicrotime();

demon("<h2>".date('d/m/Y H:i:s')." - Topology Migration start</h2>");

// Migration des tables edw_object_n_ref
migration_edw_object_ref();

// Migration des tables de données
migration_data_tables();

maj_sdna();

migration_alarm_results();

$fin = getmicrotime();

drop_tables_object_n_ref();

// Suppression des fichiers temporaires 
exec("rm -f ".$repertoire_physique_niveau0."SQL/*.mig*");

demon("<h2>".date('d/m/Y H:i:s')." - End of topology migration</h2>");
demon("<h5>Total execution time : ".round ($fin - $deb,3)." sec</h5>");

set_mode_debug(0);

echo "  end of the Migration - ".date('d/m/Y H:i:s')."\n";
echo "Demon File generated >> ".$repertoire_physique_niveau0."SQL/demon_migration_topology.html\n";

?>
