<?
/*
*	@gsm3.0.0.00@
*
//22/10/09 CGS
// Correction BUGZ 12176 [Parser DEF][Collect files] : mauvaises gestions du uniqid. 
*	10:08 04/08/2009 SCT => mise à  niveau sur CB 5.0
*	10:08 04/08/2009 SCT : amélioration de l'affichage du démon
*	10:08 04/08/2009 SCT : requ?te à  n'exécuter que pour les parsers DATATRENDS
*		DANS LE CAS IU, GSM, GPRS, la reprise d'un jeu de fichier incomplet (R03 + 44.txt) supprime les autres fichiers du m?me jeu déjà  intégré
*	03/11/2009 GHX
*		- Correction du BZ 12485 [Parser DEF][Collect files] : si mode day tous les fichiers uploadés sont rejetés
*	29/07/2010 NSE bz 16357 : suppression de la méthode treatmentFileInvalid()
*/
?>
<?php
/**
 * Classe propre au parser GSM d'Astellia qui g?re des méthodes permettant de collecter des informations sur le fichier collecté (date de la source, idenitfiant de la source, etc..)
 * 
 * 16-11-2007 SCT : modification de la fonction pour prendre en compte les fichiers Resellers
 * 01-02-2008 SCT : Bug # 5786 => reprise de données défaillante
 *
 * @package Retrieve_Parser_GSM
 * @author Guillaume Houssay 
 * @version 2.0.1.01
 * @todo Supprimer la notion de group table ($id_group_table_current) car la collecte des fichiers est indépendante des familles de données. Lien avec le CB qui g?re la collecte
 */

class parser_upload 
{
	/**
	 * Constructeur qui initialise l'execution de la requ?te
	 * 
	 * @param int $id_group_table identifiant du group table
	 */
	function __construct()
	{

	} 

	/**
	 * Fonction qui retourne un timestamp
	 * 
	 * @param text $date format de date issu du fichier source (au format 24/09/2005 23:00:03)
	 * @return timestamp $timestamp timestamp représentant la date en entrée
	 */
	function convert_date_to_timestamp($date)
	{
		$array_data = explode(" ", $date);

		$date = $array_data[0]; //contient que les données de type date (annee,mois,jour)
		$array_date = explode("/", $date);

		$time = $array_data[1]; //contient que les données de type date (heure,min,sec)
		$array_time = explode(":", $time);

		$year = $array_date[2];
		$month = $array_date[1] + "0"; //l'addition permet d'avoir le mois à  9 au lieu de 09 par exemple sinon la conversion en timestamp est mauvaise		
		$day = $array_date[0] + "0";
		$heure = $array_time[0] + "0";
		$min = $array_time[1] + "0";
		$sec = $array_time[2] + "0";
		$timestamp = mktime($heure, $min, $sec, $month, $day, $year);
		return $timestamp;
	} 

	/**
	 * Fonction qui collecte les arguments issus d'un flat file GSM
	 * Si aucun argument n'est trouvé, le tableau retourné contient des valeurs NULL
	 * 
	 * 16-11-2007 SCT : modification de la fonction pour prendre en compte les fichiers Resellers
	 *
 	 * 17:16 09/06/2009 SCT : ajout de la fonctionnalité d'analyse des fichiers ZIP
	 *	- Si le $template_name est '*R02.txt.zip', on effectue un traitement spécial
	 *		+ copy du fichier ZIP dans le répertoire 'flat_file_zip/extraction'
	 *		+ extraction du fichier ZIP
	 *		+ on renomme les fichiers extraits sur le m?me schéma que celui du fichier zip (cette opération permettra d'éviter les doublons dans la suite des traitements
	 *			- fichier zip : home_astellia_omc_flat_file_080611_0200_iups4_aub21_0100_R02.txt.zip
	 *			- fichier extrait original : 080611_0200_iups4_aub21_0100_R02.txt | 080611_0200_iups4_aub21_0100_24.txt
	 *			- fichier extrait final : home_astellia_omc_flat_file_080611_0200_iups4_aub21_0100_R02.txt | home_astellia_omc_flat_file_080611_0200_iups4_aub21_0100_24.txt
	 *		+ on recherche les informations du fichier R03.txt (appel récursif de la fonction en modifiant les param?tres)
	 *		+ on retourne les informations du fichier R03.txt pour le fichier ZIP
	 * 17:17 09/06/2009 SCT : transformation du param?tre "*R02.txt" en "*R02.txt$" pour faire une différenciation avec les fichiers "*R02.txt.zip"
	 *
	 * @param text $file chemin et nom du fichier à  traiter
	 * @param text $template_name identifiant du template du fichier
	 * @param array $source_info tableau contenant les informations du fichier ('source_name' => le nom du fichier d'origine, 'heure_upload' => l'heure de téléchargement du fichier)
	 * @param string $location chemin d'acc?s au fichier (chemin distant)
	 * @return array $flat_file_arguments arguments du fichier (hour,day,month,year,duree de capture)
	 */
	function get_flat_file_arguments($file, $template_name, $source_info, $location)
	{
		$duration = 3600;
                
                // 01/08/2012 BBX
                // Remplacement des awk par du PHP SPL (perf++++)
                $datafile = new SplFileObject($file, 'r');
                $datafile->setFlags(SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

                // Time Aggregation
                $currentLine = $datafile->fgetcsv(';');
                $fileta = trim($currentLine[0]);
                
                // Date
                $currentLine = $datafile->fgetcsv(';');
                $filedate = trim($currentLine[0]);
                
                unset($datafile);

                // Recalcul et enregistrement des valeurs par rapport à  ce timestamp
		// 11:51 03/11/2009 GHX
		// BZ 12485
                $flat_file_arguments = array();
		if ( strlen($filedate) == 8 && strtolower($fileta) == 'day')
			$flat_file_arguments[0] = $filedate.'00';
		else
			$flat_file_arguments[0] = $filedate;
			
                $flat_file_arguments[1] = substr($filedate,0,8);
                $flat_file_arguments[2] = substr($filedate,0,6);
                $flat_file_arguments[3] = substr($filedate,0,4);
                $flat_file_arguments[4] = $duration;
                
                return $flat_file_arguments;
	} 

	/**
	 * Fonction qui gén?re un identifiant unique pour la source à  partir des données contenues dans celle-ci
	 * Cet identifiant est utilisé pour notamment la reprise de fichiers
	 *
	 * 17:21 09/06/2009 SCT : ajout de la construction du GUID pour le fichier ZIP
	 * 17:21 09/06/2009 SCT : transformation du param?tre "*R02.txt" en "*R02.txt$" pour faire une différenciation avec les fichiers "*R02.txt.zip"
	 * 
	 * @param text $file chemin et nom du fichier à  traiter
	 * @param text $template_name identifiant du template du fichier
	 * @param text $source_name nom d'origine du fichier collecté
	 * @return text $uniq_id argument identifiant de mani?re unique la source de données
	 */
	function get_unique_identifier($file, $template_name, $source_name)
	{
		//22/10/09 CGS --- BUGZ 12176 [Parser DEF][Collect files] : mauvaises gestions du uniqid. 
		$uniq_id=md5($source_name);
		return $uniq_id;
	} 

	/**
	 * Fonction qui va mettre à  jour des informations (hour,day,flat_file_uniqid)
	 * pour les fichiers secondaires issus d'un fichier principal
	 * 
	 * Le fichier principal contient les information de date alors que les fichiers secondaires n'en contiennent pas
	 * 
	 * Le lien entre le fichier principal et les fichiers secondaires se fait par les radicaux des fichiers qui sont les m?mes
	 * utiliser uniquement pour les fichiers d'Astellia
	 * 
	 * @global ressource identifiant de connection à  la BDD
	 */
	function update_time_data( $repertoire_archive )
        {
            $database = Database::getConnection();

            $query = "
                    SELECT 
                            uploaded_flat_file_name, 
                            hour, 
                            day 
                    FROM 
                            sys_flat_file_uploaded_list 
                    WHERE 
                            hour IS NULL 
                            AND day IS NULL 
                    ORDER BY 
                            oid DESC";
            $result = $database->execute($query);
            while($values = $database->getQueryResults($result,1))
            {
                    //Recherche du fichier de référence sans lequel il ne peut pas y avoir de sauvegarde des fichiers dans la table d'archives
                    if(!ereg("R02.txt", $values["uploaded_flat_file_name"]))
                    {
                            $infos = explode("_", $values["uploaded_flat_file_name"]);
                            $cpt = count($infos)-1;
                            $infos2 = explode($infos[$cpt], $values["uploaded_flat_file_name"]);
                            $query_get_infos = "
                                    SELECT 
                                            hour,
                                            day,
                                            flat_file_uniqid 
                                    FROM 
                                            sys_flat_file_uploaded_list 
                                    WHERE 
                                            uploaded_flat_file_name = '" . $infos2[0] . "R02.txt' 
                                    ORDER BY
                                            oid DESC 
                                    LIMIT 1";
                            $res_infos = $database->execute($query_get_infos);
                            while($row_infos = $database->getQueryResults($res_infos,1))
                            {
                                    $query_update = "
                                            UPDATE 
                                                    sys_flat_file_uploaded_list 
                                            SET 
                                                    hour = " . $row_infos["hour"] . ", 
                                                    day = " . $row_infos["day"] . ",
                                                    flat_file_uniqid = '" . $row_infos["flat_file_uniqid"] . "' 
                                            WHERE 
                                                    uploaded_flat_file_name = '" . $values["uploaded_flat_file_name"] . "'"; 
                                    $database->execute($query_update);
                            } 
                    } 
            }

            // Stockage dans la table archive des fichiers collectés qui n'existe pas déjà 
                    // 06/07/2007 : Jérémy ->	Modification de la variable (repertoire_archive)
            //$repertoire_archive = $this->retrieve_parameters["repertoire_upload_archive"];
            $query = "
                    INSERT INTO 
                            sys_flat_file_uploaded_list_archive 
                                    (id_connection, hour, day, flat_file_template, flat_file_location, uploaded_flat_file_name, uploaded_flat_file_time, flat_file_uniqid, capture_duration, modification_date) 
                                            SELECT 
                                                    id_connection, 
                                                    hour,
                                                    day,
                                                    flat_file_template,
                                                    '$repertoire_archive' || uploaded_flat_file_name || '.bz2',
                                                    uploaded_flat_file_name,
                                                    uploaded_flat_file_time,
                                                    flat_file_uniqid,
                                                    capture_duration,
                                                    modification_date
                                            FROM 
                                                    sys_flat_file_uploaded_list t0 
                                            WHERE 
                                                    hour IS NOT NULL 
                                                    AND flat_file_uniqid NOT IN 
                                                            (
                                                                    SELECT 
                                                                            distinct flat_file_uniqid 
                                                                    FROM 
                                                                            sys_flat_file_uploaded_list_archive t1 
                                                                    WHERE
                                                                            t0.flat_file_template=t1.flat_file_template
                                                            )";
                $res_infos = $database->execute($query);
                //__debug($query,"QUERY");
                $nombre_insertion = $database->getAffectedRows();
                //11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                print displayInDemon($nombre_insertion.' fichiers archives (dont '.$nombre_fichiers_zip_traites.' fichiers ZIP)<br>'."\n");
	}

	/**
	* Fonction qui va analyser le nom du fichier pour retourner le type de transfert FTP qui sera utilisé
	*
	* 23/01/2009 SCT : modification de la condition de passage en mode binaire. BZ 8700
	* 
	* @param string $lib_element_naming_template template du groupe de fichier en cours de collecte
	* @return string le type de connexion FTP à  utiliser
	*/
	function get_file_type($fichier_source)
	{
		// en cas de présence de ZIP, on passe en mode binaire
		// 23/01/2009 SCT : modification de la condition
		if(strpos($fichier_source, 'R02.txt.zip'))
			$type_fichier = 'FTP_BINARY';
		else
			$type_fichier = 'FTP_ASCII';
		return $type_fichier;
	} 

	/**
	* Fonction qui va analyser le type de fichier en cours de collecte et qui va appliquer un dos2unix pour les fichiers ASCII
	*
	* 09:09 27/10/2008 SCT : dans le cas d'un fichier ZIP, on va déplacer les fichiers extraits vers le répertoire 'flat_file_zip'
	* 
	* @param string $file nom du fichier à traiter
	* @param string $lib_element_naming_template template du groupe de fichier en cours de collecte
	* @return string le type de connexion FTP à  utiliser
	*/
	function fileTreatmentDos2Unix($file, $lib_element_naming_template)
	{
		// en cas de présence de ZIP, on déplace les fichiers extraits dans le répertoire "flat_file_zip"
		if($lib_element_naming_template == '*R02.txt.zip')
		{
			$cheminRepertoireConnexionZip = $this->retrieve_parameters["repertoire_upload_zip"].'connexion_'.$this->idConnectionEnCours.'/';
			// 00:00 30/10/2008 SCT : vérification de l'existance du répertoire
			if(!is_dir($cheminRepertoireConnexionZip))
				mkdir($cheminRepertoireConnexionZip, 0777);
			// on déplace les fichiers extraits de l'archive ZIP vers le répertoire dans lequel ils seront collectés par la connection spéciale ZIP
			if($handle_zip = opendir($this->retrieve_parameters["repertoire_upload_zip_extract"]))
			{
				while(false !== ($file_extrait = readdir($handle_zip)))
				{
					if($file_extrait != "." && $file_extrait != "..")
						rename($this->retrieve_parameters["repertoire_upload_zip_extract"].$file_extrait, $cheminRepertoireConnexionZip.$file_extrait);
				}
			}
			closedir($handle_zip);
			// 23:55 29/10/2008 SCT : on incrémente la variable de comptage de fichiers ZIP
			$this->extractionFichierZip ++;
		}
		else
		{
			// 30/07/2007 - JL - Conversion du fichier avec dos2unix
			$command = "dos2unix " . $file;
			exec($command);
		}
	} // End function fileTreatmentDos2Unix
} 
?>
