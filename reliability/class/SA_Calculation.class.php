<?php
/*
 * Classe de calcul du Source Availability
 */
?>
<?
/**
 * CB 5.0.3
 *
 * maj MPR :
 *      - Correction du bz 16158 - On supprime uniquement les calcul_sa collectées + les calcul sa à 0 pour les mêmes heures/jours
 *      - Correction du bz 16180 - Le SA day retourné est toujours 0. Problème de calcul SUM(a)/ SUM(b) retourne toujours 0
 *	- Correction des bz 16268/16158 - Le calcul de SA est faux avec un produit daily et plusieurs connexions // Ajout du LEFT JOIN pour compter le nombre de fichiers attendus
 *      - Correction du bz 16602 - Ajout d'un CASE WHEN pour fixer le calcul à 1 si celui-ci est > 1
 *      - Correction des bz 16168 - On complète les données avec celles manquantes via defineSAConnectionOnTaMin() uniquement lorsqu'on a des fichiers hour
 *      - Correction du bz 16242 - On ne prend pas en compte les connexions actives mais qui n'ont pas de données SA
 *      - Correction du bz 16173 - Erreur SQL si aucun fichier n'est intégré, on remplace la liste des jours intégrés par une sous-requête sur la table sys_flat_file_uploaded_list
 *      - Correction du bz 16242 - On ne prend pas en compte les connexions actives mais qui n'ont pas de données SA
 *      - 01/07/2010 : Ajout de la condition data_collection_frequency = 1 (il faut prendre en compte uniquement les connections sur des fichiers hour
 *      - 08/07/2010 - Correction du bz 16610 : Si aucune donnée day lorsque day est le TA min, alors on définit à 0 le calcul_sa
 *      - 08/07/2010 - Correction du bz 16153 : On boucle sur les jours déjà intégrés pour combler les heures manquantes
 *      - 15/10/2010 NSE bz 18735 : SA non calculé pour une certaine configuration
 *      - 06/01/2011 NSE bz 19581 : suppression des doublons créés après retraitement de données
 *      - 21/09/2010 NSE bz 18010 : erreur si aucune connexion cochée
 *      - 15/10/2010 NSE bz 18735 : SA non calculé pour une certaine configuration
 *      - 20/01/2011 MPR bz 20247 : Se réferer aux commentaires du bz20014 (merge 5.1.1 vers 5.0.4)
 *      - 07/02/2011 NSE bz 20589 : suppression d'une sortie dans le démon
 *      - 22/03/2011 BBX BZ 16153 : ajout des retours chariots manquants sur les copy + utilisation des constandes de classe pour le nom des tables
 *      - 08/06/2011 MMT bz 22404 : Netoyage de doublon: doublon definit seulement avec les champs sdsv_ta,sdsv_id_connection,sdsv_ta_value
 *									Les lignes avec le sa_value le plus elevé sont conservés lors du netoyage
 */
?>
<?
class SA_Calculation
{
	const TABLE_FILE_TYPE = "sys_definition_flat_file_lib";
	const TABLE_FILE_DATAS = "sys_definition_sa_view";
	const TABLE_CONNECTIONS = "sys_definition_connection";
	const TABLE_FILE_TYPE_PER_CONNECTION = "sys_definition_sa_file_type_per_connection";
	const TABLE_FILE_ARCHIVE = "sys_flat_file_uploaded_list_archive";
	const TABLE_FILE_ARCHIVE_TEMP = "sys_flat_file_uploaded_list";
        // maj 20/01/2011 - MPR : bz20014 - Ajout d'une table temporaire pour calculer le SA par type de fichiers
	const TABLE_CALCULATION_TEMP = "sa_calculation_tmp";

	/**
	 * Tableau contenant les types de fichier pour chaque connexion
	 * @var array
	 */
	protected $file_types_per_connection = array();

	/**
	 * Tableau contenant les heures et/ou jours collectés
	 * @var array
	 */
	protected $ta_values = array();

        // 21/10/2010 NSE bz 18735 : initialisation du tableau
	protected $days_still_integrated = array();

	/**
	 * Agrégation temporelle
	 * @var string
	 */
	protected $ta;

	/**
	 * Connexion ? la base de données
	 * @var object
	 */
	protected $_db;

	/**
	 * Mode debug
	 * @var integer
	 */
	protected $debug = 0;

	/**
	 * Constructeur
	 * @param integer $product : Produit concerné
         * @access public
	 */
	public function __construct($product="")
	{
		$this->_db = Database::getConnection($product);
		$this->_product = $product;
		$debug = get_sys_debug("src_availability", $product);

		$this->setDebug( $debug );

	} // End function __construct

	/**
	 * Fonction qui récupère la TA minimum déployée
         * @access protected
	 */
        // select agregation from sys_definition_time_agregation  where visible=1  order by agregation_rank asc limit 1
	protected function getTaMinimum()
	{
                // 20/07/2010 - MPR : utilisation de la fonction get_ta_min() de edw_function.php
		$this->ta = get_ta_min($this->_product, " AND on_off = 1 ");
		if( $this->debug )
		{
			displayInDemon("TA minimum : ".$this->ta);
		}
	} // End function getTaMinimum()

	/**
	 * Fonction qui récupère les heures et/ou jours collectés
         * @access public
	 */
	public function getTaValuesCollected()
	{
		$this->getTaMinimum();

		$query = "SELECT DISTINCT
                                CASE WHEN f.data_collection_frequency <= 1 THEN 'hour' ELSE 'day' END as ta, hour, day
                        FROM
                                ".self::TABLE_FILE_TYPE." f,
                                ".self::TABLE_FILE_TYPE_PER_CONNECTION." s,
                                sys_flat_file_uploaded_list
                        WHERE
                                id_flat_file= sdsftpc_id_flat_file
                                AND flat_file_naming_template = flat_file_template
                                AND id_connection = sdsftpc_id_connection
                                AND on_off = 1
                        ORDER BY ta, day, hour
		";
                $deb = getmicrotime();
		$result = $this->_db->getAll($query);
                $fin = getmicrotime();
                $time = round($fin - $deb, 3);

                $this->day_collected = array();
		if( count($result) > 0 )
		{
			foreach( $result as $row )
			{
				// Val correspond aux fichiers qu'on veut récupérer
				if ( $this->ta == $row['ta'] )
				{
						// On récupère la TA (généralement hour mais parfois day pour roaming et hpg par exemple)
						$this->ta_values[$row['ta']][] = $row[$row['ta']];
				}

                                if( !in_array($row["day"],  $this->day_collected ) )
                                    $this->day_collected[] = $row["day"];
			}

                        $this->displayQuery($this->_db->getNumRows(), $query, $time);
		}

	} // End function getTaValuesCollected()

        /**
         * Fonction qui affiche les requêtes SQL
         *
         * @param integer $nb_results
         * @param string $query
         * @param integer $time
         * @param string $error
         */
        protected function displayQuery($nb_results, $query, $time, $error=false)
        {
            if( !$error )
            {
                $display_query = "<pre style='color:#3399ff'>{$nb_results} : {$query}</p>";
                displayInDemon($display_query);
                displayInDemon("> Temps d\'exécution : ".$time." secondes");
            }
            else
            {
                displayInDemon("ERREUR SQL<br />{$query}","alert");
            }
        } // End function displayQuery()

	/**
	 * Fonction qui r?cup?re les types de fichiers ? prendre en compte dans le calcul du SA
	 */
	public function getConfigConnections()
	{
		$this->file_types_per_connection = ConnectionModel::getFileTypesPerConnection($this->_product);
	} // End function getConfigConnections()

	/**
	 * Fonction d'activation du debug
	 * @param integer $debug
	 */
	public function setDebug( $debug )
	{
		$this->debug = $debug;
	}

	/**
	 * Fonction qui initialise le tableau de donn?es
	 * @param array $datas
	 */
	public function setDatas($datas)
	{
		$this->datas = $datas;
	} // End function setDatas()

	/**
	 * Calcul du SA Connection sur le TA minimum (hour ou day).
	 */
	public function calculateSaConnectionsOnTaMin()
	{
		displayInDemon("Recuperation des heures ou jours calcules","title");
		$this->getTaValuesCollected();
		displayInDemon("Calcul de SA pour chacune des connexions","title");

		foreach( $this->ta_values as $ta => $ta_values )
		{
                        // Correction du bz 16158 - On supprime uniquement les calcul_sa collectées + les calcul sa à 0 pour les mêmes heures/jours
			// Suppression des anciens calculs pour les heures et jours déjà collectées
                        // 09/06/2011 BBX -PARTITIONING-
                        // Correction des casts
                        $this->createTempTableSaCalculation($ta);
                        $delete = "
                                    DELETE FROM
                                            ".self::TABLE_FILE_DATAS."
                                    USING
                                            ".self::TABLE_FILE_ARCHIVE_TEMP."
                                    WHERE
                                            -- On supprime les données des connexions ayant collectées des fichiers \n
                                            (sdsv_id_connection = id_connection AND sdsv_ta_value = {$ta}::text )
                                            -- On supprime les données déjà enregistrées mais nulles (évite les doublons) \n
                                            OR (sdsv_calcul_sa = 0 AND sdsv_ta_value = {$ta}::text )

                                            AND sdsv_ta = '{$ta}'
                                    ";

			// Insertion des données SA calculées
                        // Correction du BZ16180 - Le SA day retourné est toujours 0. Problème de calcul SUM(a)/ SUM(b) retourne toujours 0
			// Correction des BZ 16268/16158 - Le calcul de SA est faux avec un produit daily et plusieurs connexions // Ajout du LEFT JOIN pour compter le nombre de fichiers attendus
                        // Correction du BZ16602 - Ajout d'un CASE WHEN pour fixer le calcul à 1 si celui-ci est > 1
                        $returning = "";
                        $_condition_granularity = "granularity = 'hour' AND ";
                        if($this->ta !== 'hour')
                        {
                              $_condition_granularity = "";
                              $returning = "RETURNING sdsv_id_connection, sdsv_ta_value, sdsv_ta";
                        }

                        // Correction du bz
						// 24/06/2014 GFS - Bug 42329 - [SUP][T&A GSM][#42699][ZainIQ]Wrong SA calculation, Hourly SA not filled
                        $insert = "
                    INSERT INTO ".self::TABLE_FILE_DATAS." (sdsv_ta, sdsv_ta_value, sdsv_id_connection,sdsv_calcul_sa)

                    -- On calcule les données SA pour les connexions ayant eu des fichiers intégrés
                            SELECT
                            '{$ta}' as ta,
                            ta_value,
                            t0.id_connection,
                            least(1, ROUND( SUM( t0.calcul_per_type_file ) / (nb_files_expected_total) ,2 )) as calcul_sa
                    FROM
                    (
                            -- On calcul le SA pour chacune des connexions actives et pour les types de fichiers actifs
                            ".$this->generateQueryCalculationSaPerTypeFile($ta)."
                     ) t0 LEFT JOIN
                    (
                            -- On compte le nombre total de fichiers attendus
                            ".$this->generateQueryGetNbFilesExpected($_condition_granularity)."
                    ) t1

                    ON ( t0.id_connection = t1.sdsftpc_id_connection )
                    GROUP BY t0.id_connection, ta_value,nb_files_expected_total
                    HAVING  least(1, ROUND( SUM( t0.calcul_per_type_file ) / (nb_files_expected_total) ,2 )) IS NOT NULL
                    ORDER BY t0.id_connection, ta_value
                    {$returning}
			";

                        $deb_delete = getmicrotime();
                        $this->_db->execute($delete);
                        $fin_delete = getmicrotime();
                        $time_delete = round($fin_delete - $deb_delete,3);
                        if( $this->debug )
                        {
                                $this->displayQuery($this->_db->getAffectedRows(), $delete, $time_delete);
                        }

                        $deb_insert = getmicrotime();
                        if($this->ta == 'hour')
                            $this->_db->execute($insert);
                        else
                            $result = $this->_db->getAll($insert);
                        $fin_insert = getmicrotime();
                        $time_insert = round($fin_insert - $deb_insert , 3);

                        if( $this->_db->getLastError() == "" )
                        {
                                if( $this->debug ){
                                     if($this->ta == 'hour')
                                        $nb_results = $this->_db->getAffectedRows();
                                     else
                                        $nb_results = count($result);

                                     $this->displayQuery($nb_results, $insert, $time_insert);
                                }
                                // On complète les données avec celles manquantes
                                // Uniquement lorsqu'on a des fichiers hour
                                // Correction des bz 16168
                                if( $this->ta == 'hour' )
                                    $this->defineSaConnectionsOnTaMinAsNull();
                                else
                                    $this->defineSaConnectionsOnDayAsNull($result);
                        }
                        elseif( $this->debug )
                        {
                                displayInDemon("Error occured during the calculation of Source Availability","alert");
                                $this->displayQuery($nb_results, $insert, $time_insert,true);
                        }
                        if( !$this->debug )
                            $this->dropTempTableSaCalculation();
		}

	} // End function calculateSaConnections()

        /**
         * Fonction qui calcul SA par type de fichier et par connexion
         * @param integer $ta : ta calculée
         * @return string : sous-requête SQL
         *
         * maj 20/01/2011 - MPR : bz20014 - Ajout de la méthode
         *
         */
        protected function generateQueryCalculationSaPerTypeFile($ta)
        {
            /**
             *  maj 06/01/2011 : MPR - Correction du bz20014
             *  Deux cas possibles :
             *      1 - ta min = day, on se base sur le nombre de chunks total pour la connexion
             *      2 - ta_min = hour, on se base sur le nombre de chunks attendu par fichier
             */
            $nbChunksInFile = ( $ta == 'hour' ) ? "nb_chunks_in_file" : "sdsftpc_data_chunks";

            return "
                    SELECT {$ta} as ta_value, sffula.flat_file_template, nb_chunks_in_files/{$nbChunksInFile} AS calcul_per_type_file, sffula.id_connection

                    FROM
                        -- Preparation du calcul SA par type de fichier
                        ".$this->generateCalculSaPerTypeFile($ta)." calculate_tmp,
                        -- Récupération de la configuration des connexions
                        ".self::TABLE_FILE_ARCHIVE." sffula,
                        (
                                SELECT
                                        flat_file_naming_template,
                                        data_collection_frequency,
                                        sdsftpc_id_connection,
                                        sdsftpc_data_chunks
                                FROM
                                        ".self::TABLE_FILE_TYPE." f, ".self::TABLE_FILE_TYPE_PER_CONNECTION.", ".self::TABLE_CONNECTIONS." c
                                WHERE
                                    id_flat_file = sdsftpc_id_flat_file
                                    AND f.on_off = 1 AND c.on_off = 1 AND sdsftpc_id_connection = c.id_connection
                        ) s

                         WHERE {$ta} IN ( SELECT DISTINCT {$ta} FROM ".self::TABLE_FILE_ARCHIVE_TEMP." )
                                AND flat_file_naming_template = sffula.flat_file_template
                                AND calculate_tmp.flat_file_template = sffula.flat_file_template
                                AND calculate_tmp.date = sffula.{$ta}::text
                                AND calculate_tmp.id_connection = sffula.id_connection
                                AND sdsftpc_id_connection = sffula.id_connection
                         GROUP BY
                                  sffula.flat_file_template, {$ta}, sffula.id_connection,
                                  {$nbChunksInFile}, calculate_tmp.flat_file_template,
                                  calculate_tmp.id_connection, calculate_tmp.date, calculate_tmp.nb_chunks_in_files
                    ";
        } // End function generateQueryCalculationSaPerTypeFile()

        /**
         * Fonction qui génère la sous-requête qui récupère le nombre total de chunks présent par type de fichier
         * et par connexion
         * @return string : sous-requête SQL
         *
         * maj 20/01/2011 - MPR : bz20014 - Ajout de la méthode
         *
         */
        protected function generateCalculSaPerTypeFile($ta)
        {

            /**
             * maj 06/01/2011 : MPR - Correction du bz20014
             *
             * Gestion du cas particulier d'HPG
             *
             *  Schéma explicatif :
             *      00 01 02 03 04 05 06 07 08 09 10 11 12 13 14 15 16 17 18 19 20 21 22 23
             *      |--|--|--|--|--|--|--|--|--|--|--|--|--|--|--|--|--|--|--|--|--|--|--|--|
             *
             *   f1 |--|--|--|--|--|
             *   f2                      |--|--|--|--|--|--|--|--|
             *   f3                                  |--|--|--|--|--|--|--|
             * ` f4                                                          |--|--|--|
             *   f5                                                                |--|--|--|
             *
             *   Fichier |    Heure début   | Heure de fin  | Nb heures (chunks)
             * -------------------------------------------------------------
             *      f1   |      00          |       04      | `     5
             *      f2   |      07          |       14      |       8
             *      f3   |      11          |       17      |       7
             *      f4   |      19          |       21      |       3
             *      f5   |      21          |       23      |       3
             *
             *  Nombre d'heures total = 21 (les heures 05h, 06h et 18h sont manquantes)
             *
             *  /!\ Nous ne pouvons pas faire la somme du nombre d'heures des fichiers
             *      puisque les fichier f2, f3, f4 et f5 se chevauchent
             *
             * Méthode de calcul :
             * A partir de l'heure de début, de fin et le nombre de chunks,
             * nous pouvons formater les infos sous forme de tableau SQL
             *
             *  file    |                           coverage
             * --------------------------------------------------------------------
             *    f1    |       {1,1,1,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0}
             *    f2    |       {0,0,0,0,0,0,0,1,1,1,1,1,1,1,1,0,0,0,0,0,0,0,0,0}
             *    f3    |       {0,0,0,0,0,0,0,0,0,0,0,1,1,1,1,1,1,1,0,0,0,0,0,0}
             *    f4    |       {0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,1,1,0,0}
             *    f5    |       {0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,1,1}
             *
             * Et voilà, il ne reste plus qu'à faire un calcul booléen de type "OU"
             * (1 OU 0 = 1 / 0 OU 0 = 0).
             *
             *
             * Pour réaliser cela, on fait la somme de chaque colonne
             * du tableau. En utilisant LEAST, nous limitons la valeur à 1.
             * On obtient alors la liste des heures couvertes.
             *
             *  total   |       {1,1,1,1,1,0,0,1,1,1,1,1,1,1,1,1,1,1,0,1,1,1,1,1}
             *
             * Enfin, nous faisons la somme de toutes nos heures couvertes. Nous retrouvons
             * bien ici nos 3 heures manquantes et donc nb heures = 21.
             *
             */
            return "
                    (
                    -- On dédoublonne les heures dans le cas où des fichiers se chevauchent
                    SELECT flat_file_template, date, id_connection,
                             LEAST(SUM(coverage[1]::int),1)+
                             LEAST(SUM(coverage[2]::int),1)+
                             LEAST(SUM(coverage[3]::int),1)+
                             LEAST(SUM(coverage[4]::int),1)+
                             LEAST(SUM(coverage[5]::int),1)+
                             LEAST(SUM(coverage[6]::int),1)+
                             LEAST(SUM(coverage[7]::int),1)+
                             LEAST(SUM(coverage[8]::int),1)+
                             LEAST(SUM(coverage[9]::int),1)+
                             LEAST(SUM(coverage[10]::int),1)+
                             LEAST(SUM(coverage[11]::int),1)+
                             LEAST(SUM(coverage[12]::int),1)+
                             LEAST(SUM(coverage[13]::int),1)+
                             LEAST(SUM(coverage[14]::int),1)+
                             LEAST(SUM(coverage[15]::int),1)+
                             LEAST(SUM(coverage[16]::int),1)+
                             LEAST(SUM(coverage[17]::int),1)+
                             LEAST(SUM(coverage[18]::int),1)+
                             LEAST(SUM(coverage[19]::int),1)+
                             LEAST(SUM(coverage[20]::int),1)+
                             LEAST(SUM(coverage[21]::int),1)+
                             LEAST(SUM(coverage[22]::int),1)+
                             LEAST(SUM(coverage[23]::int),1)+
                             LEAST(SUM(coverage[24]::int),1)::numeric as nb_chunks_in_files
                        FROM (
                                SELECT flat_file_template, {$ta} as date, id_connection, file, hd, hf, d, string_to_array(substr(repeat('0,',hd)||repeat('1,',d)||repeat('0,',24-hf),0,24*2),',') as coverage
                                FROM ".self::TABLE_CALCULATION_TEMP."

                        ) calcul_tmp
                        GROUP BY flat_file_template, id_connection, date
                    )
                        ";
        } // End function generateCalculSaPerTypeFile()

        /**
         * Fonction qui créer la table temporaire sa_calculation_tmp
         * @param string $ta Niveau d'agrégation temporel
         *
         * maj 20/01/2011 - MPR : bz20014 - Ajout de la méthode
         *
         */
        protected function createTempTableSaCalculation($ta)
        {
            // 09/06/2011 BBX -PARTITIONING-
            // Correction des casts
            $query = "
                        DROP TABLE IF EXISTS ".self::TABLE_CALCULATION_TEMP.";
                        SELECT id_connection, {$ta}::text, flat_file_template, substring(hour::text from '..$')::int as hf, GREATEST(substring(hour::text from '..$')::int + 1 - nb_chunks_in_file ,0) as hd, nb_chunks_in_file as d, flat_file_uniqid as file
                        INTO ".self::TABLE_CALCULATION_TEMP." FROM ".self::TABLE_FILE_ARCHIVE."
                        WHERE {$ta} IN ( SELECT DISTINCT {$ta} FROM ".self::TABLE_FILE_ARCHIVE_TEMP." )
                            AND substring(flat_file_template::text from '....$') <> '.zip';
                        ";

            $deb = getmicrotime();
            $this->_db->execute($query);
            $fin = getmicrotime();
            $time = round($fin - $deb,3);

            $this->displayQuery($this->_db->getAffectedRows(), $query, $time);

        } // End function createTempTableSaCalculation()

        /**
         * Fonction qui supprime la table temporaire sa_calculation_tmp
         *
         * maj 20/01/2011 - MPR : bz20014 - Ajout de la méthode
         *
         */
        protected function dropTempTableSaCalculation()
        {
            $query = "DROP TABLE IF EXISTS ".self::TABLE_CALCULATION_TEMP;
            $this->_db->execute($query);
        } // End function dropTempTableSaCalculation()

        /**
         * Fonction qui génère la sous-requête qui récupère le nombre total de fichiers attendus
         * @return string
         *
         * maj 20/01/2011 - MPR : bz20014 - Ajout de la méthode
         *
         */
        protected function generateQueryGetNbFilesExpected($_condition_granularity)
        {
            $query = "
                        SELECT count(*) as nb_files_expected_total, sdsftpc_id_connection
                        FROM ".self::TABLE_FILE_TYPE_PER_CONNECTION." t, ".self::TABLE_FILE_TYPE." f
                        WHERE {$_condition_granularity} id_flat_file = sdsftpc_id_flat_file
                        AND on_off = 1
                        GROUP BY sdsftpc_id_connection";
             return $query;
        } // End function generateQueryGetNbFilesExpected()

        // maj 08/07/10 - MPR : Correction du bz16610 - Si aucune donnée day lorsque day est le TA min, alors on définit à 0 le calcul_sa
        /**
         * Définition du calcul SA Day à 0 pour les connexions qui n'ont pas collecté de fichiers (ta min = day)
         * @param <type> $cnx_with_data
         *
         * 06/01/2011 NSE bz 19581 : suppression des doublons
         */
        protected function defineSaConnectionsOnDayAsNull($cnx_with_data)
        {
             // maj 20/07/2010 - MPR : Correction du bz 16798 - Warning apparant dans le démon
            // Initialisation de la variable $tab
            $tab = array();
            if(count($cnx_with_data) > 0)
            {
                foreach($cnx_with_data as $cnx)
                {
                    if( !isset( $tab[ $cnx['sdsv_ta_value']] ))
                           $tab[ $cnx['sdsv_ta_value'] ] = array();

                    if(!in_array($cnx['sdsv_id_connection'], $tab[ $cnx['sdsv_ta_value']] ))
                        $tab[ $cnx['sdsv_ta_value'] ][] = $cnx['sdsv_id_connection'];
                }
            }

            $query = "
                    SELECT  sdsftpc_id_connection as id_cnx
                    FROM sys_definition_flat_file_lib f, sys_definition_sa_file_type_per_connection, sys_definition_connection c
                    WHERE id_flat_file = sdsftpc_id_flat_file
                    AND f.on_off = 1 AND c.on_off = 1
                    AND sdsftpc_id_connection = c.id_connection";

            $result = $this->_db->getAll($query);

            $lst_cnx = array();
            foreach( $result as $row )
            {
                $lst_cnx[] = $row['id_cnx'];
            }

            $copy = array();
            if(count($result) > 0)
            {
               foreach( $tab as $day=>$tab_cnx_inserted )
               {
                   $cnx_not_inserted = array_diff($lst_cnx, $tab_cnx_inserted );

                   foreach($cnx_not_inserted as $cnx)
                   {
                       // 22/03/2011 BBX
                       // Ajout du retour chariot manquant
                       // BZ 16153
                       $copy[] = "day\t{$cnx}\t{$day}\t0\n";
                   }
               }
            }
            // Copie des données en base
            $this->_db->setTable(self::TABLE_FILE_DATAS,$copy);

            //08/06/2011 MMT bz 22404 factorisation du netoyage de sa_view
            $this->deleteSaViewDoubles();
        } // End function defineSaConnectionsOnDayAsNull

        /**
         * Définition du calcul SA hour à 0 pour les connexions qui n'ont pas collecté de fichiers
         *
         * 06/01/2011 NSE bz 19581 : suppression des doublons
         */
        protected function defineSaConnectionsOnTaMinAsNull()
        {
            // On récupère l'heure max intégré pour chacune des connexions
            //
            $query = "
                        SELECT DISTINCT
                                substring(max(sdsv_ta_value)::text,9,2)::integer as hour_max,
                                day, sdsv_id_connection as id_cnx
                        FROM
                                ".self::TABLE_FILE_ARCHIVE_TEMP." l , ".self::TABLE_FILE_DATAS."
                        WHERE
                                sdsv_ta = 'hour'
                            AND l.id_connection = sdsv_id_connection
                            AND substring(sdsv_ta_value::text,1,8) = day::text
                        GROUP BY
                                day, sdsv_id_connection
                      ";
            $deb = getmicrotime();
            $result = $this->_db->getAll($query);
            $fin = getmicrotime();
            $time = round($fin - $deb,3);

            if( $this->_db->getLastError() == "" && $this->debug )
            {
                    displayInDemon("Definition des SA Connections avec des valeurs nulles","title");
                    $this->displayQuery($this->_db->getNumRows(),$query,$time);
            }
            elseif( $this->debug )
            {
                    displayInDemon("Erreur rencontrée lorsqu'on complète les données SA avec celles manquantes","alert");
                    $this->displayQuery(0, $query, $time, true);
            }

            if( count($result) > 0 )
            {
                $tab = array();
                $lst_days = array();
                $copy = array();

                // Correction du bz 16242 - On ne prend pas en compte les connexions actives mais qui n'ont pas de données SA
                //  sys_definition_sa_file_type_per_connection, sys_definition_flat_file_lib f
                // 01/07/2010 - MPR : Ajout de la condition data_collection_frequency = 1 (il faut prendre en compte uniquement les connections sur des fichiers hour
                $_condition = " c,".self::TABLE_FILE_TYPE_PER_CONNECTION.", ".self::TABLE_FILE_TYPE." f
                                WHERE c.on_off = 1 AND f.on_off = 1
                                    AND sdsftpc_id_connection = id_connection
                                    AND sdsftpc_id_flat_file = id_flat_file
                                    AND data_collection_frequency = 1
                                GROUP BY id_connection";
                $lst_connections = ConnectionModel::getAllConnections($this->_product, $_condition);

                // Etape 1 : On récupère les heures les plus grandes intégrées pour chacune des connexions
                foreach($result as $row )
                {
                    // On enregistre l'heure max pour chaque journée intégrée
                    if( !isset($hour_max[$row['day']] ) )
                        $hour_max[$row['day']] = $row['hour_max'];

                    // On enregistre les jours intégrés
                    if(!in_array($row['day'] , $lst_days))
                        $lst_days[] = $row['day'];

                    // On enregistre l'heure max pour chaque journée intégrée
                    if( $hour_max[$row['day']] < $row['hour_max'] )
                    {
                        $hour_max[$row['day']] = $row['hour_max'];
                    }
                }

                $days = array();
                // On vérifie les données également sur le nombre de jours sur lequel on va combler les données
                // 22/11/2012 BBX
                // BZ 29704 : correction du calcul des jours
                foreach($lst_days as $day)
                {
                    $refTs = strtotime($day);
                    $day_max = date('Ymd',  strtotime("-1 day",$refTs));
                    $day_min = date('Ymd',  strtotime("-2 day",$refTs));

                    if( !in_array( $day_max, $days ) )
                    {
                        $days[] = $day_max;
                    }

                    if( !in_array( $day_min, $days ) )
                    {
                        $days[] = $day_min;
                    }
                }

               $query = "
                        SELECT substring(max(sdsv_ta_value)::text,9,2)::integer as hour_max, substring(sdsv_ta_value,1,8)::integer as day,sdsv_id_connection
                        FROM ".self::TABLE_FILE_DATAS."
                            WHERE substring(sdsv_ta_value::text,1,8)::integer IN (".implode(",",$days).")
                                AND sdsv_ta = 'hour'
                        GROUP BY substring(sdsv_ta_value::text,1,8), sdsv_id_connection
                        HAVING substring(max(sdsv_ta_value)::text,9,2)::integer < 23
                        ";
               $result_still_integrated = $this->_db->getAll($query);

               $this->days_still_integrated = array();
               $hour_max_still_integrated = array();
               foreach($result_still_integrated as $row )
               {
                    // On enregistre l'heure max pour chaque journée intégrée
                    if( !isset($hour_max_still_integrated[$row['day']] ) )
                        $hour_max_still_integrated[$row['day']] = $row['hour_max'];

                    // On enregistre l'heure max pour chaque journée intégrée
                    if( $hour_max_still_integrated[$row['day']] < $row['hour_max'] )
                    {
                        $hour_max_still_integrated[$row['day']] = $row['hour_max'];
                    }

                    // On enregistre les jours intégrés
                    if(!in_array($row['day'] , $this->days_still_integrated))
                        $this->days_still_integrated[] = $row['day'];
               }
               
               echo '<pre>';
               print_r($lst_days);
               echo '</pre>';

               $tab = array_merge($days,$lst_days);
                // Récupération des données intégrées
               $datas = $this->getDatasIntegrated( $tab );

               if( $this->debug )
               {
                       __debug($datas,"datas");
                       __debug($hour_max,"HOUR MAX");
                       __debug($hour_max_still_integrated,"HOUR MAX DES JOURS DEJA INTEGRES");
               }

               foreach($lst_connections as $cnx)
               {
                    // On boucle sur les jours
                    foreach($lst_days as $day)
                    {
                        for($i=0; $i <= $hour_max[$day]; $i++)
                        {
                            if( !isset($datas[$day][$cnx][$i]) )
                            {
                                // Construction d'un tableau php pour copier les données en base
                                $hour = ($i < 10 ) ? $day."0".$i : $day.$i;
                                // 22/03/2011 BBX
                                // Ajout du retour chariot manquant
                                // BZ 16153
                                $copy[] = "hour\t{$cnx}\t{$hour}\t0\n";
                            }
                        }
                    }

                    // maj 08/07/2010 -Correction du bz16153
                    // On boucle sur les jours déjà intégrés pour combler les heures manquantes
                    foreach($this->days_still_integrated as $day)
                    {

                        for($i=$hour_max_still_integrated[$day]+1;$i <= 23;$i++)
                        {
                            if( !isset($datas[$day][$cnx][$i]) )
                            {
                                 // Construction d'un tableau php pour copier les données en base
                                $hour = ($i < 10 ) ? $day."0".$i : $day.$i;
                                // 22/03/2011 BBX
                                // Ajout du retour chariot manquant
                                // BZ 16153
                                $copy[] = "hour\t{$cnx}\t{$hour}\t0\n";
                            }
                        }
                    }
                }
                if( $this->debug )
                {
                    __debug($copy,"COPY");
                }
                // Copie des données en base
                $this->_db->setTable(self::TABLE_FILE_DATAS, $copy);

                //08/06/2011 MMT bz 22404 factorisation du netoyage de sa_view
                $this->deleteSaViewDoubles();
            }
        } // End function defineSaConnectionsOnTaMinAsNull()


        /**
         * 08/06/2011 MMT bz 22404 factorisation du netoyage de sa_view avec cette fonction
         */
        private function deleteSaViewDoubles(){

            // 06/01/2011 NSE bz 19581 On supprime les doublons après coup
            $doublons = "
                           SELECT oid,* FROM ".self::TABLE_FILE_DATAS." sdsa
                           WHERE ROW(sdsv_id_connection,sdsv_ta_value,sdsv_ta) IN
                               (
                                   -- on sélectionne ceux qui en ont plus d'1
                                   SELECT sdsv_id_connection,sdsv_ta_value,sdsv_ta from
                                      (
                                           -- on compte les occurences
                                           SELECT sdsv_id_connection,sdsv_ta_value,sdsv_ta,count(*) as nb
                                           FROM ".self::TABLE_FILE_DATAS."
                                           group by sdsv_id_connection,sdsv_ta_value,sdsv_ta
                                       ) as nombre
                                   WHERE nombre.nb>1
                               )
						-- 08/06/2011 MMT bz 22404 on range les doublons avec la valeur SA la plus haute en premier (celle qui sera gardée)
                        ORDER BY sdsv_ta,sdsv_id_connection,sdsv_ta_value, sdsv_calcul_sa desc
                        ";
            // 07/02/2011 NSE bz 20589 : suppression d'une sortie dans le démon.
            $tab_doublons = $this->_db->getAll($doublons);
            $prec = '';
            $tab_dedoublonnage = array();
            // on parcourt le tableau des doublons
            // on conserve 1 enregistrement pour chaque doublon et on met les autres dans le tableau des enregistrements à supprimer
            foreach($tab_doublons as $ligne){
                // si on l'a rencontré avant), on le supprime
                // 08/06/2011 MMT bv 22404 un doublon est definit sur 3 champs: sdsv_id_connection,sdsv_ta_value,sdsv_ta
                // on supprime les doublons avec sa_value inferieur ou egal
                $currentLineCode = implode('-', array_slice($ligne,1,3));
                if($prec == $currentLineCode){
                   $tab_dedoublonnage[] = $ligne['oid'];
                }
                $prec = $currentLineCode;
            }
            if(!empty($tab_dedoublonnage)){
                $dedoublonnage = "DELETE FROM ".self::TABLE_FILE_DATAS." WHERE oid IN (".implode(',', $tab_dedoublonnage).")";
                $this->_db->executeQuery($dedoublonnage);
            }
        }

        protected function getDatasIntegrated( $days )
        {
             // Etape 2 : On récupère toutes les heures intégrées pour chacune des connexions
                // 09/06/2011 BBX -PARTITIONING-
                // Correction des casts
                $query = "
                        SELECT DISTINCT
                                substring(sdsv_ta_value::text,9,2)::integer as hour,
                                id_connection, day
                        FROM
                                ".self::TABLE_FILE_DATAS.", ".self::TABLE_FILE_ARCHIVE_TEMP." l
                        WHERE
                                substring(sdsv_ta_value::text,1,8) = day::text
                                AND sdsv_ta = 'hour'
                                AND l.id_connection = sdsv_id_connection
                        ORDER BY
                                id_connection, hour, day
                        ";

                $deb = getmicrotime();
                $datas = $this->_db->getAll($query);
                $fin = getmicrotime();
                $time = round($fin - $deb,3);

                if($this->debug)
                {
                    $this->displayQuery($this->_db->getNumRows(),$query, $time);
                }

                if( count($datas) > 0 )
                {
                    foreach($datas as $row)
                    {
                        $t[ $row['day'] ][ $row['id_connection'] ][ $row['hour'] ] = true;
                    }
                }

                // On récupère également les heures intégrés des jours précédents les jours intégrés
                $query_2 = "
                            SELECT DISTINCT substring(sdsv_ta_value::text,9,2)::integer as hour,sdsv_id_connection, substring(sdsv_ta_value::text,1,8)::integer as day, sdsv_id_connection as id_connection
                            FROM ".self::TABLE_FILE_DATAS.", sys_definition_connection
                            WHERE substring(sdsv_ta_value::text,1,8)::integer IN (".implode(", ",$days).") AND sdsv_ta = 'hour'
                                AND id_connection = sdsv_id_connection
                            ORDER BY sdsv_id_connection, hour, day";
                $datas_2 = $this->_db->getAll($query);
                if( count($datas_2) > 0 )
                {
                    foreach($datas_2 as $row)
                    {
                         $t[ $row['day'] ][ $row['id_connection'] ][ $row['hour'] ] = true;
                    }
                }
                return $t;
        }

	/**
	 * Fonction qui récupère l'ensemble des types de fichier dont la fréquence de collecte est day (un seul fichier attendu par jour)
	 * @param integer $id_connection : ID de la connexion
	 * @return array $tab : tableau contenant l'ensemble des types de fichier dont la fr?quence de collecte est day (un seul fichier attendu par jour)
	 */
	protected function getFileTypesDay( $id_connection )
	{
		$query = "SELECT flat_file_naming_template
                            FROM ".self::TABLE_FILE_TYPE." f, ".self::TABLE_FILE_TYPE_PER_CONNECTION.", ".self::TABLE_CONNECTIONS." c
                            WHERE data_collection_frequency = 24
                                        AND sdsftpc_id_connection = {$id_connection}
                                        AND sdsftpc_id_connection = id_connection
                                        AND sdsftpc_id_flat_file = id_flat_file
                                        AND f.on_off = 1
                                        AND c.on_off = 1";
		$result = $this->_db->getAll($query);

		$tab = array();
		if(count($result) > 0 )
		{
			foreach($result as $row)
			{
				$tab[] = $row['flat_file_naming_template'];
			}
		}
		if($this->debug)
		{
			__debug($tab,"$query");
		}

		return $tab;
	} // End function getFileTypesDay()

	/**
		* Fonction qui supprime les données SA Daily pour toutes les connexions qui ont été intégrées
		* @param array $lst_days : liste de jours
		*/
	protected function removeDataOnDay()
	{
            $_condition = ( count($this->days_still_integrated) > 0 ) ? " OR sdsv_ta_value::integer IN ( ".implode(", ",$this->days_still_integrated)." )": "";
            // Correction du bz 16173 - Erreur SQL si aucun fichier n'est intégré, on remplace la liste des jours intégrés par une sous-requête sur la table sys_flat_file_uploaded_list
            // 09/06/2011 BBX -PARTITIONING-
            // Correction des casts
            $delete ="DELETE FROM ".self::TABLE_FILE_DATAS."
                        WHERE sdsv_ta = 'day'
                                AND sdsv_id_connection IN (
                                        SELECT DISTINCT id_connection
                                        FROM ".self::TABLE_CONNECTIONS."
                                )
                                AND (sdsv_ta_value IN ( SELECT DISTINCT day::text FROM ".self::TABLE_FILE_ARCHIVE_TEMP.") {$_condition})";
            $deb = getmicrotime();
            $this->_db->execute($delete);
            $fin = getmicrotime();
            $time = round($fin - $deb,3);

            if( $this->debug )
            {
                $this->displayQuery($this->_db->getAffectedRows(),$delete, $time);
            }
	}

         /**
        * Fonction qui calcul toutes les données jours pour toutes les connexions collectées
        */
	public function calculateSaConnectionsOnDay()
	{
            if( $this->ta !== "day" )
            {
		displayInDemon("Calcul de SA day pour chacune des connexions","title");
		$list_DatasOnDay = array();
		$list_ta_values = array();
		$query  = "SELECT COUNT(*) FROM ".self::TABLE_FILE_ARCHIVE_TEMP."";
                $nb_files_integrated = $this->_db->getOne($query);

                if($nb_files_integrated > 0 )
                {
                    // Suppression des données précédentes
                    $this->removeDataOnDay();

                    // Récupération de la liste des connexions où des fichiers ont été collectés pour calculer le nouveau SA Day
                    // Correction du bz 16242 - On ne prend pas en compte les connexions actives mais qui n'ont pas de données SA
                    $select = "SELECT id_connection FROM ".self::TABLE_CONNECTIONS.", ".self::TABLE_FILE_TYPE_PER_CONNECTION." WHERE on_off = 1 AND sdsftpc_id_connection= id_connection GROUP BY id_connection";
                    $deb_select = getmicrotime();
                    $result_select = $this->_db->getAll($select);
                    $fin_select = getmicrotime();
                    $time_select = round($fin_select - $deb_select, 3);

                    $this->displayQuery( count($result_select), $select, $time_select);

                    // 21/09/2010 NSE bz 18010 : test si des connexions ont été trouvées
                    if(!empty($result_select)) {
                        foreach( $result_select  as $result_connection )
                        {
                            // Récupération des types de fichier dont la fréquence de dépôt est day
                            $files_type_day = $this->getFileTypesDay( $result_connection['id_connection'] );
                            $this->day_collected = array_merge($this->days_still_integrated, $this->day_collected);
                            
                            // 22/11/2012 BBX
                            // BZ 29704 : Calcul du taux de couverture horaire attendu sur cette connection
                            $queryExp = "SELECT SUM(sdsftpc_data_chunks)::real / SUM(l.data_chunks)::real
                            FROM 
                                    sys_definition_connection c, 
                                    sys_definition_sa_file_type_per_connection p, 
                                    sys_definition_flat_file_lib l
                            WHERE p.sdsftpc_id_connection = c.id_connection
                            AND l.id_flat_file = p.sdsftpc_id_flat_file
                            AND c.id_connection = {$result_connection['id_connection']}
                            AND l.granularity = 'hour'";
                            $expectedRateCoverage = $this->_db->getOne($queryExp);

                            foreach( $this->day_collected as $date)
                            {
                                    // Récupération des stats HOUR
                                    $query_hour = "
                                            SELECT SUM(sdsv_calcul_sa)::real as sum, count(sdsv_calcul_sa) as nb FROM ".self::TABLE_FILE_DATAS."
                                            WHERE sdsv_ta = 'hour' AND substr(sdsv_ta_value,1,8) = '{$date}' AND sdsv_id_connection = {$result_connection['id_connection']}";

                                    if (count( $files_type_day ) > 0 )
                                    {
                                            // On définit calcul_sa pour la date courante
                                            $query = "
                                                    SELECT
                                                            SUM(nb_chunks_in_file) as nb_in_file,
                                                            SUM(nb_chunks_expected_in_file) as nb_expected
                                                    FROM ".self::TABLE_FILE_ARCHIVE."
                                                    WHERE flat_file_template IN ('".implode("','", $files_type_day)."')
                                                        AND day = {$date} AND id_connection = {$result_connection['id_connection']}";
                                            $deb_query = getmicrotime();
                                            $result =  $this->_db->getAll($query);
                                            $fin_query = getmicrotime();
                                            $time_query = round($fin_query - $deb_query,3);
                                            if( $this->debug )
                                                __debug($result,"ReSULT");

                                            // On vérifie si tous les fichiers sont présents
                                             $get_nb_files_total = "
                                                    SELECT count(*) * data_chunks as nb_files_expected_total
                                                    FROM ".self::TABLE_FILE_TYPE_PER_CONNECTION." t, ".self::TABLE_FILE_TYPE." f
                                                    WHERE granularity = 'day'
                                                        AND id_flat_file = sdsftpc_id_flat_file
                                                        AND on_off = 1
                                                        AND sdsftpc_id_connection = {$result_connection['id_connection']}
                                                    GROUP BY data_chunks
                                                    ";
                                             $nb_total = $this->_db->getOne($get_nb_files_total);

                                            // Si pas de résultat on initialise le Calcul sa day à 0 pour ne pas faire planter le calcul final
                                            if( $result[0]['nb_in_file'] == "" )
                                            {
                                                $calcul_sa_day = 0;
                                                $result[0]['nb_in_file'] = 0;
                                            }
                                            else
                                            {
                                                // On fixe une valeur par défaut (évite divison par 0)
                                                $nb_total =  ( $nb_total == "0" || (int)$nb_total == $result[0]['nb_expected'] ) ? 1 : (int)$nb_total;

                                                 if( $this->debug )
                                                 {
                                                    __debug("mode CALCUL PHP - ".$result[0]['nb_in_file']." / (".$result[0]['nb_expected']." * ".$nb_total." ) )");
                                                 }
                                                 // Le calcul day se base sur le nombre de chunks * le nombre de fichiers attendus
                                                $calcul_sa_day = $result[0]['nb_in_file'] / ( $nb_total );
                                            }

                                            $this->displayQuery($this->_db->getNumRows(), $query, $time_query);

                                            // Calcul du SA HOUR
                                            $deb_query_hour = getmicrotime();
                                            $datas_sa_hour = $this->_db->getAll($query_hour);
                                            $fin_query_hour = getmicrotime();
                                            $time_query_hour = round($fin_query_hour - $deb_query_hour,3);
                                            foreach( $datas_sa_hour as $row )
                                            {
                                                if($row['nb'] == 0)
                                                    $calcul_sa_hour = "";
                                                else
                                                    $calcul_sa_hour = (real)$row['sum'] / (real)$row['nb'];
                                            }

                                            if($calcul_sa_hour != "")
                                            {
                                                // 22/11/2012 BBX
                                                // BZ 29704 : Calcul en prenant en compte le taux de couverture horaire attendu sur cette connection
                                               $calcul_sa_hour = round($calcul_sa_hour / $expectedRateCoverage, 2);
                                               $this->displayQuery($this->_db->getNumRows(), $query_hour, $time_query_hour);
                                               $calcul_sa = round(($result[0]['nb_in_file']+$row['sum']) / ( $nb_total+ $row['nb']),2);
                                            }
                                            else
                                            {
                                                $calcul_sa = $calcul_sa_day;
                                            }

                                    }
                                    else
                                    {
                                        $deb_query_hour = getmicrotime();
                                        $datas_sa_hour = $this->_db->getAll($query_hour);
                                        $fin_query_hour = getmicrotime();
                                        $time_query_hour = round($fin_query_hour - $deb_query_hour,3);

                                        $calcul_sa = "";

                                        foreach($datas_sa_hour as $row )
                                        {
                                            if($row['nb'] != 0){
                                            	// 05/03/2014 GFS - Bug 39931 - [SUP][T&A IU][#36626][AlfaLiban[Source Availability]:In SA daily view mode, the % value is calculated incorrect when the values of 'Data Chunks' are set less than 24
                                                $calcul_sa =  (real)$row['sum'] / $row['nb'];
                                            }
                                        }
                                        
                                        // 22/11/2012 BBX
                                        // BZ 29704 : Calcul en prenant en compte le taux de couverture horaire attendu sur cette connection
                                        $calcul_sa = round($calcul_sa / $expectedRateCoverage, 2);

                                        $this->displayQuery($this->_db->getNumRows(), $query_hour, $time_query_hour);


                                    }

                                    if($calcul_sa !== "")
                                    {
                                        // Sauvegarde de la valeur dans une table temporaire
                                        // Correction du bz
                                        $calcul_sa = ($calcul_sa > 1) ? 1 : $calcul_sa;
                                        $list_DatasOnDay[] = "('day', {$result_connection['id_connection']}, '{$date}', {$calcul_sa})";
                                    }
                            }
                        }
                        if($this->debug)
                        {
                                echo "<pre>";
                                print_r($list_DatasOnDay);
                                echo "</pre>";
                        }

                        $list_DatasOnDay = array_unique($list_DatasOnDay);

                        // Insertion en base de toutes les données SA day
                        $insert = "INSERT INTO ".self::TABLE_FILE_DATAS."
                                                (sdsv_ta, sdsv_id_connection, sdsv_ta_value, sdsv_calcul_sa)
                                                VALUES ".implode(",", $list_DatasOnDay);
                        $deb_insert = getmicrotime();
                        $this->_db->execute($insert);
                        $fin_insert = getmicrotime();
                        $time_insert = round($fin_insert - $deb_insert,3);
                        //$this->_db->setTable( self::TABLE_FILE_DATAS, $list_DatasOnDay);

                        if( $this->_db->getLastError() != "" && $this->debug )
                        {
                            displayInDemon("Error occured during the insertion of SA Days","alert");
                            $this->displayQuery(0, $this->_db->getLastError()." - ".$insert, $time_insert, true);
                        }
                        else
                        {
                            $this->displayQuery($this->_db->getAffectedRows(), $insert, $time_insert);
                        }
                    }
                }
            }
	} // Fin de calculateSaConnectionsOnDay()

	/**
	*
	* @param <type> $sa_connections
	* @return real
	*/
	public function calculateSaOverview( $tab )
	{

		foreach($tab as $ta_value => $val)
		{
                    // Correction du bz 16242 - On ne prend pas en compte les connexions actives mais qui n'ont pas de données SA
                    $empty_tab = true;

                   $_id_to_delete = array_search('',$val);
                   if( $_id_to_delete != "" )
                        unset( $val[$_id_to_delete] );

                    if( count($val) > 0 ) {
                        // Somme des SA Connections
                        $num = array_sum($val);

                        // Nombre de connexions
                        $den =  count($val);

                        // Moyenne des valeurs
                        $calculate[$ta_value] =  round(($num / $den)*100);

                        // Vérification que le tableau ne contient pas que des valeurs nulles pour la journée en cours

                        foreach($val as $data)
                        {
                            if(isset($data) && $data!="" )
                            {
                                    $empty_tab = false;
                                    break;
                            }
                        }
                    }
                     // si les données sont toutes nulles, alors on retourne -1 pour cette ta_value
                    if($empty_tab == true)
                    $calculate[$ta_value] = -1;
		}

		return $calculate;
	}
} // End Class
?>
