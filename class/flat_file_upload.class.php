<?php

/**
 * 
 *  CB 5.2
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 */
?><?php

/*
 * 	@cb50000@
 *
 * 	12/06/2009 - Copyright Acurio
 *
 * 	Composant de base version cb_5.0.0.00
 *
 * 	15:57 12/06/2009 SCT : utilisation de la nouvelle classe d'accès aux données
 * 	11:18 09/07/2009 SCT : amélioration du système d'affichage du debug
 * 	17:09 16/10/2009 SCT : BZ 12102 => problème d'analyse des fichiers ZIP => insensibilité à la casse
 *
 * 	30/11/2009 : On décommente car sinon, le GUID n'est pas testé. Et non, la reprise de données n'est pas bloquée. BZ 9376
 * 	30/11/2009 BBX : suppression du fichier de référence de la liste des fichiers de références uploadés. BZ 13136
 * 	15:08 10/12/2009 SCT : BZ 11513 => fichier avec seulement header intégré
 * 		+ ajout du paramètre "$file" à la fonction "treatmentFileInvalid"
 * 	15:31 11/12/2009 SCT : BZ 11505 => fichiers avec src_guid identiques intégrés
 * 	11/12/2009 BBX : on ne prend pas les fichiers cachés. BZ 12469
 * 	14:49 14/12/2009 SCT : BZ 13431 => la reprise de données n'est pas opérationnelle pour le produit GSM
 * 	14/12/2009 NSE : Optimisation de la collecte (issue du cas de fichiers très nombreux).
 * 					 Abandon du tableau archived_files_info au profit d'une requête directe sur la table sys_flat_file_uploaded_list_archive indexée.
 * 					 Plus le nb de fic dans l'historique est important, plus le gain l'est (ex. Ericsson NSS).
 * 	14:13 15/12/2009 SCT : BZ 13466, 13467 => template sensible à la casse
 * 	16:54 29/01/2010 NSE : bz 13994 suppression de la fonction search_archived_files
 * 	16:19 01/02/2010 MPR : BZ 14004 Ajout du mode passif ou actif d'une connexion FTP
 * 					- Création et utilisation des fonctions chooseBestMode(), SaveMode et reinitializeFtpMode()
 * 	14:37 24/02/2010 MPR : BZ 14477 : Problème avec certains serveurs FTP
 *
 * 	08/03/2010 BBX : si le fichier est incorrect, on doit le supprimer. BZ 14341
 * 	26/03/2010 NSE 14838 : on supprime les fichiers de données dont le fichier de référence a été rejeté (header incorect, date invalide...).
 *       28/04/2010 MPR : Correction du BZ 15247 - Reprise de données défaillante qd un GUID contient des \
 *      15:24 30/07/2010 SCT : BZ 17136 => amélioration de la gestion des messages en cas de rejet d'un fichier de données lors de la collecte
 *      15:15 30/07/2010 SCT : suppression des "print" et "echo" précédant les fonctions "displayInDemon"
 *      29/12/2010 BBX :
 *          - BZ 19807 : En cas d'échec de téléchargement FTP on retourne "false" afin de ne pas insérer le fichier en base
 *          - BZ 19807 : On télécharge le fichier pour lire le GUID. Inutile de le retélécharger lors du traitement. Si le fichier local existe déjà on le réutilise.
 *      02/03/2011 OJT : DE SFTP
 *  26/05/2011 NSE bz 22130 : Collect Process blocked
 *  15/07/2011 MMT bz 22455 : remplacement de pg_errormessage()
 *  21/10/2011 NSE : nettoyage démon
 *  21/09/2012 ACS BZ 29071 Collect performances decrease after upgrading from 5.0 to 5.1
 *  13/12/2012 GFS BZ#30526 [SUP][AVP NA]: No system alert in trace log when pm files is missing
 */
?>
<?php

/*
 * 	@cb40000@
 *
 * 	14/11/2007 - Copyright Acurio
 *
 * 	Composant de base version cb_4.0.0.00
 *
  - maj 27/05/2008 Benjamin : modification de la condition, == devient <= afin d'éviter de boucler à l'infini pour le cas rare où last_period > current_period. BZ6678
  - maj 14/04/08, christophe : correction bug BZ6327, mauvais format de la trap SNMP > on affiche 'system alerts' à la place de static alarm
  - maj 14/04/08, christophe : correction bug BZ6327, mauvais format de la trap SNMP > on affiche le contenu du champ 'publisher' comme 	     premier param.
  - maj 07/12/2007, gwénaël : répercution d'un dev du patch cb_v3.0.1.01 : [23/11/2007] modification de la gestion des alarmes systèmes. 	     Maintenant chaque type de fichier peut avoir sa propre tempo.
  - maj 15/02/2008, benoit : dans la méthode 'alarm_result_absence()', ajout de la colonne 'exclusion' dans la requete
  - maj 15/02/2008, benoit : dans la méthode 'alarm_result_absence()', ajout de '$exclusion' dans la liste issue des lignes de resultats
  - maj 15/02/2008, benoit : dans la méthode 'alarm_result_absence()', on verifie ici que l'heure de tempo ne fait pas partie des exclusions
  - maj 19/02/2008, benoit : ajout des méthodes 'decomposeExclusionValues()' et 'getIntervalValues()' permettant de retourner une chaine de    la forme "0;10-22;5;7" en une suite de valeurs uniques

  - maj 26/02/2008, SCT :
  - ajout d'un paramètre : "retrieve_delete_file" pour gérer la suppression des fichiers du serveur de données
  - ajout d'un paramètre : "retrieve_search_directory" pour activer la recherche des fichiers de données dans les sous-répertoires du      serveur de données
  - ajout d'une colonne dans les tables "sys_flat_file_uploaded_list" et "sys_flat_file_uploaded_list_archive" pour le stockage de la      date de création (ou modification) du fichier de données => gestion de la reprise dans le cas de la non-suppression des fichiers de    données.
  - modification du GUID : stockage du chemin du fichier (avec le nom du fichier) comme GUID
  - modification des méthodes pour permettre la recherche des fichiers de données dans les sous-répertoires du serveur de données pour     les modes FTP et LOCAL

  - maj 16/04/2008, benoit : correction du bug 6289
  -maj 12/06/2008 - maxime : Correction bug BZ6663 - L'alarme système a comme severité Warning et non Critical
  La MIB d'astellia est modifié, on ajoute dans arm Level la valeur 4 en lui attribuant le label Warning...
  - maj 11:34 10/10/2008 SCT : correction bug 7630 (import depuis cb 3.0.4.01)
  - Stockage de l'arborescence d'une connexion en mémoire pour utilisation avec les autres types de fichiers.
  - On diminue les temps de traitement en ne parcourant plus qu'une seule fois l'arborescence pour chaque connexion
  - maj 16:03 20/10/2008 SCT : correction bug 7696 par ajout de l'appel à la fonction "get_file_type".
  - On vérifie le type de fichier à télécharger par l'appel à cette fonction.
  + en cas de fichier texte, le mode de connexion FTP sera ASCII
  + en cas de fichier binaire, le mode de connexion FTP sera BINARY
  - Si la fonction "get_file_type" n'est pas définie dans le fichier parser/xxx/scripts/flat_file_upload_xxx.class.php, on passe en mode ASCII automatiquement
  - maj 15:18 22/10/2008 SCT : DE Collecte par groupe de fichiers
  - prise en compte de l'élément 'reference' et 'ordre' de la table sys_definition_flat_file_lib lors de la récupération des types de fichiers à collecter
  - initialisation d'un tableau ($this->tableauFichierRefCollecte) contenant l'ensemble des fichiers de référence rencontrés sur le serveur de données
  - lors de la collecte d'un fichier non référence mais appartenant à un groupe de référence, on utilise le tableau ($this->tableauFichierRefCollecte) pour identifier s'il appartient à un groupe à collecter pour autoriser la collecte
  - maj 10:44 24/10/2008 SCT : DE Collecte des fichiers ZIP
  - ajout d'un type de fichier supplémentaire dans sys_definition_flat_file_lib
  - ajout de la reference 'ZIP' sur le type de fichier "ZIP" dans sys_definition_flat_file_lib
  - sortie de la transformation 'dos2unix' de la fonction 'store_uploaded_file' vers le fichier 'parser/xxx/scripts/flat_file_upload_xxx.class.php' [fileTreatmentDos2Unix] : cette fonction peut corrompre les traitements sur les fichiers ZIP
  - ajout de l'appel à la fonction externe 'treatmentDateFileInvalid' [fichier 'parser/xxx/scripts/flat_file_upload_xxx.class.php']
  + la date de fichier de référence de l'archive ZIP n'est pas valide
  + suppression des fichiers extraits.
  + si cette méthode n'existe pas, aucun traitement (aucune erreur)
  - ajout d'une colonne dans la table sys_flat_file_uploaded_list et sys_flat_file_uploaded_list_archive pour la gestion du blocage du chargement des données des fichiers ZIP et la reprise de données sur les fichiers ZIP (déjà extraits)
  - 11:45 24/10/2008
  - modification pour prendre en compte la DE sur les alarmes systemes (paramétrage par connexion)
  - maj 07:39 30/10/2008 SCT :
 * déplacement du système d'analyse des fichiers ZIP
 * ajout d'un système de suppression des fichiers ZIP après analyse
  @version cb4.0.2.01
  - maj 09:48 04/11/2008 SCT :
  + amélioration de la collecte et de l'extraction des fichiers ZIP :
 * ils sont décompressés lors de la collecte et les fichiers extraits sont collectés en fin de connexion pendant laquelle on a trouvé les ZIP
 * la connexion 'flat_file_zip' a été supprimée de la table 'sys_definition_connection'

  - 14:32 07/11/2008 GHX :  BZ 8042 : [REC][T&A CB 4.0][DE][ZIP]: des espaces dans le label de la connection font planter la reprise de données ds le cas des zip
  @version cb 4.0.6.00
  - 16:22 20/02/2009 SCT #8881 : le groupe de référence de ce fichier a été rejeté : le header du fichier de référence est invalide
  21/09/2009 GHX
  - Correction du BZ 11579 [REC][T&A Gb 5.0][TC#4628][Alarme système] : boucle infinie

  16:32 23/09/2009 SCT => BZ 6828 : suppression des fichiers TA non utilisés par l'application

  30/09/2009 GHX
  - Re-Correction du BZ 11579 [REC][T&A Gb 5.0][TC#4628][Alarme système] : boucle infinie
  -> Modification d'une condition
  19/03/2010 MPR
  - Correction du BZ14346 : [Alarme système] Optimisation du calcul de la temporisation
  03/03/2015 JLG => BZ 41743 : vérifie que le mois est valide dans l'entête du fichier r02 (1 <= mois <= 12)
 *
 */
?>
<?php

/*
 * 	@cb22014@
 *
 * 	18/06/2007 - Copyright Acurio
 *
 * 	Composant de base version cb_2.2.0.14
 *
 * 	- 15:28 23/11/2007 Gwenaël : modification de la gestion des alarmes systèmes maintenant chaque type de fichier peut avoirt ca propre tempo et
 * 
 * 	- 03/09/2007 christophe : les mails sont envoyés au aadmin dont le compte est actif et valide.
 * 	06/07/2007 -  Jérémy : Adaptation des fonctions pour fonctionner avec la nouvelle vision des connexions : " UNE SONDE associé à UNE CONNEXION "
 * 	11/07/2007 -  Jérémy : Ajout de fonctions pour générer des alarmes système en l'absence de résultats de la part des sondes
 * 	30/07/2007 - Jérémy : Création de la fonction de génération de message dans le tracelog
 * 	07/08/2007 - Jérémy : Ajout de fonction pour la tempo : check_missing_tempo() & check_tempo_period() pour vérifier
 * 							- que la tempo n'a pas déjà été éxécutée au cours d'un retrieve dans l'heure courante ou la journée courante
 * 							- s'il n'y a pas des heures (ou des jours) qui n'auraient pas été soumise à un retrieve, et pour lesquelles il faudrai vérifier la tempo
 *
 * LISTE des FONCTIONS AJOUTEES
 * 	// Fonction PRINCIPALE
 * 		alarm_result_absence()
 * 	// Fonction de VERIF
 * 		//HEURE ou JOUR précédent
 * 			check_last_period($previous_periode,$period_type)
 * 		//HEURES COLLECTEES
 * 			check_last_files_collected($previous_periode,$period_tempo_value,$period_type)
 * 		// TEMPO
 * 			verify_last_retrieve_date($period_type, $today)
 * 			check_missing_tempo($period_type, $tempo, $period_tempo_value)
 * 			check_tempo_period($tab_tempo,$period_type)
 * 	//GENERATION des messages (tracelog et alarmes)
 * 		generate_tracelog_messages($period_type)
 * 		generate_SNMP_trap($period_tempo_value)
 * 		generate_mail($period_type,$period_tempo_value)
 * 	//RECUPERATION des FICHIERS MANQUANTS pour la ou les périodes données
 * 		get_missing_files_for_one_period($this_tempo, $period_type)
 * 		get_missing_files_for_some_periods($tab_periode, $type_periode)
 * 	//Fonction diverses
 * 		getMaxChaine($chaine,$max)
 * 		transform_date($old_date)
 * 		show_table_contain()
 *
 * 	19/02/2008 SCT : modification du système de collecte des fichiers de données pour le système datatrends.
 * 		- modification 
 *
 * 	07/04/2008 GHX correction du bug 6309[REC][T&A Cigale Roaming 3.0] : Fichier déjà intégré non supprimer du répertoire de collecte.
 */
?>
<?php

/*
 * 	@cb21201@
 *
 * 	14/03/2007 - Copyright Acurio
 *
 * 	Composant de base version cb_2.1.2.01
 */
?>
<?php

/*
 * 	@cb21002@
 *
 * 	23/02/2007 - Copyright Acurio
 *
 * 	Composant de base version cb_2.1.0.02
 */
?>
<?php

/*
 * 	@cb20100_iu2030@
 *
 * 	24/10/2006 - Copyright Acurio
 *
 * 	Composant de base version cb_2.0.1.00
 *
 * 	Parser version iu_2.0.3.0
 */
?>
<?php

/*
 * 	@cb1300_iu2000b_pour_cb2000b@
 *
 * 	19/07/2006 - Copyright Acurio
 *
 * 	Composant de base version cb_1.3.0.0
 *
 * 	Parser version iu_2.0.0.0
 */
?>
<?php

/*
 * 	16/06/2006 - Copyright Acurio
 *
 * 	Composant de base version cb_1.2.0.2p
 *
 * 	Parser version iub_1.1.0b
 */
?>
<?php

/*
 * 2006-02-14 : prise en compte d'un identifiant unique par fichier et récupération éventuelle des fichiers déjà joués.
 * 03-07-2006 : ajout dans la table de stockage des fichiers collectés de la durée de capture
 * 21-09-2006 : les fichiers de taille 0 ne sont pas chargés
 * 22-11-2006 : GH : mise à niveau par rapport au CB1301 car régression sur la gestion de la taille des fichiers à zero
 * 28-02-2007 GH : ajout du check dans la fonction 'check_file_date_information' pour vérifier si l'année de la source est <= ou égale à l'année courante. En effet dans les fichier source si JJ/MM/AAAA devient AAAA/MM/JJ le système renvoie une date mais une date dont à priori l'année est dans le futur
 * 18-12-2007 SCT : bug # 5593 => modification de la fonction pour boucler sur l'ensemble des fichiers connus du répertoire et les supprimer (pas seulement sur les fichiers dont "on_off = 1")
 *
 * 19/02/2008 SCT : ajout du paramètre pour la suppression ou le déplacement des fichiers de données depuis la configuration "sys_global_parameters" et l'exploration des sous-répertoires
 * 	19/02/2008 SCT : modification du système de collecte des fichiers de données pour le système datatrends.
 * 		- modification 
 * 		
 * 14-03-2008 SCT : modification de la gestion de la collecte des fichiers avec analyse de l'arborescence et possibilité de bloquer la suppression des fichiers de données. Stockage de la date de modification(ou création) du fichier de données pour effectuer la reprise de données.
 * 17-03-2008 SCT : bug #6035 => ajout d'un message dans le tracelog pour indiquer les problèmes de connexion lors de la collecte des fichiers
 * 
 * 27/03/2008 SCT : fonction 'get_ftp_list' => Ajout du nom du fichier sur la recherche de la date de modification pour la reprise de données
 * 27/03/2008 SCT : Bug 6139 (fonction 'get_lib_element') => vérification que le premier caractère du $location est bien "/"
 * 27-03-2008 SCT : bug 6131 => modification du message en cas de fichier non récupéré
 *
 * maj 04/07/2008 Benjamin : correction du nom de la fonction "__T" au lieu de "__". BZ7060
 * AJOUT LE 11:44 10/10/2008 SCT depuis cb 3.0.4.01 (08/07/2008 GHX  : modification du principe de scan, on ne parcours plus qu'une seule fois les répertoires.)
 * AJOUT LE 11:44 10/10/2008 SCT depuis cb 3.0.4.01 (09/07/2008 GHX  :  correction sur le nom des fichiers dans la table sys_flat_file_uploaded_list_archive suit aux modification du 08/07/2008)
 *
 */

/**
 *
 * @package retrieve flat file
 * Gère l'upload de fichier à partir des éléments présents dans les tables de configuration
 * (group table, parser,connection,lib_element)
 * Classe chapeau qui contient toutes les méthodes nécessaires à tout upload de fichier.
 * La façon de récupérer la date et l'heure de chaque fichier étant particulière,
 * il faut créer une sous-classe de cette classe et qui a une méthode "store_uploaded_file"
 */
class retrieve_flat_file {

    /**
     * initialise un objet
     *
     * @param int $id_group_table : le group table de l'objet
     * @param string $ (local ou remote) $mode qui définit si on doit chercher les information en local ou à distance via FTP
     */
    function retrieve_flat_file() {
        // 06/07/2007 : Jérémy : Suppression de l'id_group_table
        //			Création d'une instance de la classe "PARSER_UPLOAD" du fichier "FLAT_FILE_UPLOAD_IU.CLASS.PHP"
        //				Le constructeur de cette classe étant vide, aucune opération n'est lancé, mais on pourra y faire appel plus tard
        $this->parser = new parser_upload();
        // 06/07/2007 : Jérémy : Suppression du "retrieve_mode" dans le constructeur, il est instancié plus tard à partir des informations récupérées dans la BdD
        $this->flat_file_treated = 0;
        $this->flat_file_info = new LogFlatFile(); // RQ:6486 Permet de logger des infos sur les fichiers traités
        $this->parser->setFlatFileInfo($this->flat_file_info); // RQ:6486
        $this->variable_init();
        // 19/02/2008 SCT : initialisation du paramètre pour la suppression ou le déplacement des fichiers de données depuis la configuration "sys_global_parameters" et exploration des sous-répertoires
        $this->retrieve_delete_file = get_sys_global_parameters("retrieve_delete_file");
        $this->retrieve_search_directory = get_sys_global_parameters("retrieve_search_directory");
        // 15:10 04/11/2008 SCT : on ajoute le paramètre de recherche récursive des fichiers de données pour l'object parser_upload
        $this->parser->retrieve_search_directory = $this->retrieve_search_directory;
        // 11:19 09/07/2009 SCT : gestion de l'affichage du debug
        $this->debug = get_sys_debug('retrieve_collect_data');
        // Connexion à la base de données locale
        $this->database = Database::getConnection(0);
    }

// end function retrieve_flat_file()

    /**
     * fonction qui initialise des variables notamment la valeur de remplacement du * présent dans le template des flat file
     *
     * @global $database_connection, $edw_day
     */
    function variable_init() {
        $this->retrieve_parameters["start"] = getmicrotime();
        $this->system_name = get_sys_global_parameters("system_name");
        $this->compute_mode = get_sys_global_parameters("compute_mode");
        // 26/03/2012 BBX
        // BZ 26384 : prise en compte du compute booster :
        // Si le compute switch vaut hourly, alors compute_mode vaut temporairement daily
        // Dans ce cas il faut utiliser la valeur compute_switch afin de ne pas faire croire
        // A tord à la collecte qu'il s'agit d'un produit journalier.
        if (strtolower(get_sys_global_parameters("compute_switch")) == 'hourly')
            $this->compute_mode = 'hourly';
        // sauvegarde le répertoire dans lequel est le script
        $this->retrieve_parameters["repertoire_upload"] = REP_PHYSIQUE_NIVEAU_0 . 'upload/';
        $this->retrieve_parameters["repertoire_upload_archive"] = REP_PHYSIQUE_NIVEAU_0 . 'flat_file_upload_archive/';
        // 11:54 24/10/2008 SCT : ajout de 2 paramètres pour la gestion du chemin d'accès au répertoire de stockage des fichiers ZIP
        $this->retrieve_parameters["repertoire_upload_zip"] = REP_PHYSIQUE_NIVEAU_0 . 'flat_file_zip/';
        $this->retrieve_parameters["repertoire_upload_zip_extract"] = REP_PHYSIQUE_NIVEAU_0 . 'flat_file_zip/extraction/';
        // 09:45 27/10/2008 SCT : dans le cas de l'extraction des fichiers ZIP, il nous faut les répertoires de gestion du chemin d'accès au répertoire de stockage des fichiers ZIP
        $this->parser->retrieve_parameters["repertoire_upload_zip"] = $this->retrieve_parameters["repertoire_upload_zip"];
        $this->parser->retrieve_parameters["repertoire_upload_zip_extract"] = $this->retrieve_parameters["repertoire_upload_zip_extract"];
        $valeur_remplacement[0] = "\w+";
        $this->retrieve_parameters["valeur_remplacement_template"] = $valeur_remplacement;
        //variables propres au traps SNMP
        $this->snmp_activation = get_sys_global_parameters("snmp_activation");
        $this->entreprise = "enterprises.4318";
        // 09:23 11/12/2009 SCT : BZ 11505 => fichiers avec src_guid identiques intégrés
        $this->stockageGuidFile = array();
        // /BZ 11505
    }

// end function variable_init()

    /**
     * fonction qui insère dans la log les informations relatives au fichier : nom du fichier collecte et heure de collecte
     * @param $source_info : source_info["source_name"] contient le nom du fichier source telecharge, source_info["heure_upload"] contient l'heure de telechargement du fichier
     *
     */
    function log_retrieve($source_info) {
        $message = __T('A_FLAT_FILE_UPLOAD_ALARM_COLLECTED_FILE', $source_info["source_name"]);
        sys_log_ast("Info", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
    }

// end function log_retrieve()
    // 06/07/2007 : Jérémy ->	Suppression de la fonction "get_parser_properties"
    //						>> Du à la nouvelle vision : 1 CONNEXION est associé à 1 SONDE

    /**
     * Fonction qui collecte les proprietes de chaque connection liée à un parser
     * 28/02/2011 OJT : DE SFTP Ajout du numéro de port
     *
     * @global $database_connection
     */
    function get_connection_properties() {
        // 06/07/2007 : Jérémy : Suppression de la boucle foreach, on récupère désormais la liste de toutes les CONNEXIONS disponibles (ON)
        // Ajout de paramètre dans la requête et suppression de la condition " id_connection='$id_connection' "
        // qui permettait de récupérer chaque connexion une par une avec l'ancienne boucle foreach
        $query = "
			SELECT 
				id_connection,
				connection_name, 
				connection_ip_address, 
				connection_login,
				connection_password, 
				connection_type, 
				connection_mode, 
				connection_directory,
				connection_code_sonde, 
                            connection_port,
				id_region
			FROM 
				sys_definition_connection
			WHERE 
				on_off=1
			ORDER BY 
				protected ASC";

        $result = $this->database->execute($query);
        while ($values = $this->database->getQueryResults($result, 1)) {
            $id = $values['id_connection'];
            $this->flat_file_info->setIdConnection($id); // RQ:6486 init de la connexion
            $this->connexion_properties[$id]['id_connection'] = $values['id_connection'];
            $this->connexion_properties[$id]['connection_name'] = $values['connection_name'];
            $this->connexion_properties[$id]['connection_mode'] = $values['connection_mode'];
            $this->connexion_properties[$id]['connection_ip_address'] = $values['connection_ip_address'];
            $this->connexion_properties[$id]['connection_login'] = $values['connection_login'];
            $this->connexion_properties[$id]['connection_password'] = $values['connection_password'];
            $this->connexion_properties[$id]['connection_type'] = $values['connection_type'];
            $this->connexion_properties[$id]['connection_directory'] = $values['connection_directory'];
            $this->connexion_properties[$id]['connection_code_sonde'] = $values['connection_code_sonde'];
            $this->connexion_properties[$id]['connection_port'] = $values['connection_port'];
            $this->connexion_properties[$id]['id_region'] = $values['id_region'];
        }
    }

// end function get_connection_properties

    /**
     * fonction qui collecte les propriete de chaque lib_element liée à un parser
     *
     * 18-12-2007 SCT : bug # 5593 => modification de la fonction pour boucler sur l'ensemble des fichiers connus du répertoire et les supprimer (pas seulement sur les fichiers dont "on_off = 1")
     *
     * @global $database_connection
     */
    function get_lib_element_properties() {
        // 06/07/2007 : Jérémy ->	Suppression de la boucle foreach, on récupère désormais la liste de tous les "FLAT_FILE"
        //					Suppression de la condition " id_flat_file='$id_lib_element' "
        //						qui permettait de récupérer chaque flat_file un par un avec l'ancienne boucle foreach
        // 14:56 22/10/2008 SCT : ajout de la récupération du champ 'reference' depuis la table sys_definition_flat_file_lib'
        //Récupération de la liste de tous les lib_elements avec pour chacun leurs propriétés
        $query = "
			SELECT 
				id_flat_file, 
				flat_file_name, 
				flat_file_naming_template, 
				on_off, 
				reference 
			FROM 
				sys_definition_flat_file_lib
			ORDER BY 
				ordre DESC";
        $result = $this->database->execute($query);
        while ($values = $this->database->getQueryResults($result, 1)) {
            $id_lib_element = $values['id_flat_file'];

            // 19-12-2007 SCT : ajout d'une condition pour séparer les éléments actifs des inactifs
            if (trim($values['on_off']) == 1) {
                $this->lib_element_properties[$id_lib_element]['id_flat_file'] = $values['id_flat_file'];
                $this->lib_element_properties[$id_lib_element]['lib_element_name'] = $values['flat_file_name'];
                $this->lib_element_properties[$id_lib_element]['lib_element_naming_template'] = $lib_element_naming_template = $values['flat_file_naming_template'];
                $this->lib_element_properties[$id_lib_element]['reference'] = $values['reference'];

                $pattern = '';

                foreach ($this->retrieve_parameters['valeur_remplacement_template'] AS $valeur_remplacement) {
                    $temp = str_replace('*', $valeur_remplacement, $lib_element_naming_template);
                    $temp = str_replace('.', '[\.]', $temp);
                    $pattern .= $temp;
                }
                // 14:13 15/12/2009 SCT : BZ 13466, 13467 => template sensible à la casse
                //	+ ajout de "i"
                $template = '/' . $pattern . '/i';
                $this->lib_element_properties[$id_lib_element]['lib_element_template'] = $template;
            } else {
                $pattern = '';
                $lib_element_naming_template = $values['flat_file_naming_template'];

                foreach ($this->retrieve_parameters['valeur_remplacement_template'] AS $valeur_remplacement) {
                    $temp = str_replace('*', $valeur_remplacement, $lib_element_naming_template);
                    $temp = str_replace('.', '[\.]', $temp);
                    $pattern .= $temp;
                }
                // 14:13 15/12/2009 SCT : BZ 13466, 13467 => template sensible à la casse
                //	+ ajout de "i"
                $template = '/' . $pattern . '/i';
                $this->lib_element_properties_off[$id_lib_element]['lib_element_template'] = $template;
            }
        }
    }

    // end function get_lib_element_properties()

    /**
     * fonction de connection en ftp
     *
     * @param identifiant $ de connection $id_connection
     * @global $systyp
     */
    function ftp_connection($id_connection) {
        global $systyp;

        $ip = $id_connection['connection_ip_address'];
        $conn_id = @ftp_connect($ip);
        if (!$conn_id) {
            $message = __T("A_E_PARSER_CONNECTION_FTP_FAILED", $ip);
            // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
            displayInDemon('<b>' . $message . '</b><br>' . "\n", 'alert');
            sys_log_ast("Critical", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, 'support_1', '');
            $conn_id = false;
        } else {
            $login_result = @ftp_login($conn_id, $id_connection['connection_login'], $id_connection['connection_password']);
            $systyp = ftp_systype($conn_id);
            // succès de la connexion
            if ($login_result) {
                // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                displayInDemon('FTP Connection to ' . $ip . ' SUCCESSFULL<br>' . "\n");
                $this->chooseBestMode($conn_id, ftp_pwd($conn_id));
            }
            // échec de la connexion
            else {
                $message = __T("A_E_PARSER_CONNECTION_FTP_USER_FAILED", $ip, $id_connection['connection_login']);
                // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                displayInDemon('<b>' . $message . '</b><br>' . "\n", 'alert');
                sys_log_ast("Critical", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
                $conn_id = false;
            }
        }
        return $conn_id;
    }

    // end function ftp_connection

    /**
     * fonction qui analyse chaque element  (fichier en général) d'un répertoire
     * 26/02/2008 SCT : modification de la fonction pour récupérer la date de création (ou modification) du fichier
     *
     * @param  $dirline element à analyser
     * @global $systyp
     */
    function analysedir($dirline) {
        global $systyp, $conn_id;
        // type de SI
        if (ereg("([-dl])[rwxst-]{9}", substr($dirline, 0, 10)))
            $systyp = "UNIX";
        else
            $systyp = "Windows_NT";

        if (substr($dirline, 0, 5) == "total")
            $dirinfo[0] = -1;
        elseif ($systyp == "Windows_NT") {
            if (ereg("([0-9]{2})-([0-9]{2})-([0-9]{2}) *([0-9]{2}):([0-9]{2})([PA]?M?) +<DIR> {10}(.*)", $dirline, $regs)) {
                $dirinfo[0] = 1;
                $dirinfo[1] = 0;
                $dirinfo[2] = $regs[7];
                $dirinfo[3] = $regs[3] . '_' . $regs[1] . '_' . $regs[2] . '_' . $regs[4] . '_' . $regs[5];
            } elseif (ereg("([0-9]{2})-([0-9]{2})-([0-9]{2}) *([0-9]{2}):([0-9]{2})([PA]?M?) +([0-9]+) (.*)", $dirline, $regs)) {
                $dirinfo[0] = 0;
                $dirinfo[1] = $regs[7];
                $dirinfo[2] = $regs[8];
                $dirinfo[3] = $regs[3] . '_' . $regs[1] . '_' . $regs[2] . '_' . $regs[4] . '_' . $regs[5];
            }
        } elseif ($systyp == "UNIX") {
            if (ereg("([-d])[rwxst-]{9}.* ([0-9]*) ([a-zA-Z]+) ([0-9: ]*[0-9]) (.+)", $dirline, $regs)) {
                // 11/12/2009 BBX : on ne prend pas les fichiers cachés. BZ 12469
                if (substr($regs[5], 0, 1) != '.') {
                    // 24/05/2011 NSE bz 22130 : il ne faut pas que le nom du répertoire soit vide.
                    if (trim($regs[5]) != '') {
                        if ($regs[1] == "d")
                            $dirinfo[0] = 1;
                        $dirinfo[1] = $regs[2];
                        $dirinfo[2] = trim($regs[5]);
                        $dirinfo[3] = str_replace(array(' ', ':'), array('_', '_'), $regs[3] . '_' . $regs[4]);
                    }
                }
            }
        }
        if (($dirinfo[2] == ".") || ($dirinfo[2] == "..") || ($dirinfo[2] == ""))
            $dirinfo[0] = 0;

        return $dirinfo;
    }

// end function analysedir()
    // 16:54 29/01/2010 NSE bz 13994 : suppression de la fonction search_archived_files

    /**
     * AJOUT LE 11:44 10/10/2008 SCT depuis cb 3.0.4.01 (08/07/2008 GHX  : modification du principe de scan, on ne parcours plus qu'une seule fois les répertoires.)
     * fonction qui collecte les lib element en parcourant chaque connection puis chaque lib element
     */
    function get_lib_element() {
        $conn_id = null;
        $res_sftp = null;

        // on part du principe qu'un group table est composé d'un seul parser
        // parcoure de chaque connection puis pour chaque connection, on parcoure chaque lib_element
        // 06/07/2007 : Jérémy ->	On utilise désormais les tableaux "connexion_properties" et "lib_element_properties"
        if (count($this->connexion_properties) > 0) {
            // 15/02/2008 SCT : recherche des informations sur l'ensemble des fichiers archivés
            // 16:54 29/01/2010 NSE bz 13994 suppression de l'appel à la fonction $this->search_archived_files();

            foreach ($this->connexion_properties AS $id_connection) {
                $this->retrieve_mode = $id_connection['connection_type'];
                $this->id_connection = $id_connection['id_connection'];
                $this->connexion_name = $id_connection['connection_name'];
                $this->connexion_mode = $id_connection['connection_mode'];

                $ip = $id_connection['connection_ip_address'];
                $login = $id_connection['connection_login'];
                $port = $id_connection['connection_port'];
                $password = $id_connection['connection_password'];

                // JL : La variable "location" a changé, on utilise le répertoire donné par l'utilisateur lors de la création des connexions (cf. setup_connection.php)
                $location = $id_connection['connection_directory'];
                // 27/03/2008 SCT : Bug 6139 => vérification que le premier caractère du $location est bien "/"
                if (substr($location, 0, 1) != "/" && substr($location, 0, 1) != "\\") {
                    // 07/11/2011 BBX
                    // BZ 21897 : utilisation du répertoire local comme racine si un chemin relatif nous est donné
                    switch ($this->retrieve_mode) {
                        case 'remote' :
                            $conn_id = $this->ftp_connection($id_connection);
                            $location = trim(ftp_pwd($conn_id)) . "/" . $location;
                            break;
                        case 'remote_ssh' :
                            try {
                                $res_sftp = new SSHConnection($ip, $login, $password, $port);
                                $localDir = $res_sftp->exec('pwd');
                                $location = trim($localDir[0]) . "/" . $location;
                            } catch (Exception $ex) {
                                displayInDemon("Unable to determine local directory", "alert");
                            }
                            break;
                        case 'local' :
                        default :
                            $location = "/" . $location;
                            break;
                    }
                    // Fin BZ 21897
                }

                // 07:34 30/10/2008 SCT : variable pour la suppression des fichiers extraits du ZIP après traitement sinon accumulation
                $this->deleteFichierExtraitZip = false;

                // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                displayInDemon('<br />' . "\n" . '> Traitement Connection : ' . $id_connection["connection_name"] . '<br />' . "\n");
                displayInDemon('MODE : ' . $this->retrieve_mode . '<br>' . "\n");

                // 28/02/2011 OJT : DE SFTP. Gestion du remote_ssh
                switch ($this->retrieve_mode) {
                    case 'remote' :
                        $conn_id = $this->ftp_connection($id_connection);
                        break;

                    case 'remote_ssh' :
                        try {
                            $res_sftp = new SSHConnection($ip, $login, $password, $port);
                        } catch (Exception $ex) {
                            // Connexion SSH/SFTP impossible
                        }
                        break;

                    case 'local' :
                    default :
                        // Pas de connexion spécifique à créer dans ces cas.
                        break;
                }

                // 14:29 23/10/2008 SCT : suppression du tableau des références pour ne pas interférer avec collecte des fichiers d'une autre connexion
                if (isset($this->tableauFichierRefCollecte))
                    unset($this->tableauFichierRefCollecte);

                // 02/08/2007 - JL : Si la sonde en cours de traitement est en local, alors on test si le répertoire existe. Le test en mode FTP sera fait avec la fonction chdir si elle retourne FAUX
                // 28/02/2011 OJT : DE SFTP. Gestion du remote_ssh
                if (( $this->retrieve_mode == 'local' && is_dir($location) ) ||
                        ( $this->retrieve_mode == 'remote' && $conn_id ) ||
                        ( $this->retrieve_mode == 'remote_ssh' && $res_sftp )
                ) {

                    __debug($this->lib_element_properties, "LIB ELEMENT");
                    //Parcoure du tableau de lib element s'il n'est pas vide
                    if (count($this->lib_element_properties) > 0) {
                        //AJOUT LE 11:44 10/10/2008 SCT depuis cb 3.0.4.01
                        // 11:11 08/07/2008 GHX 
                        // Si on est sur le premier scan la valeur est fausse dans les autres scan, on aura un tableau 
                        // contenant la liste des fichiers déjà trouvé. Ce qui évite de tous rescanner sur chaque boucle.
                        $listingFiles = false;
                        // 23:36 29/10/2008 SCT : variable de vérification de la présence de fichiers ZIP
                        $this->parser->extractionFichierZip = 0;
                        // 23:56 29/10/2008 SCT : variable pour passer le paramètre  à la fonction  'fileTreatmentDos2Unix'
                        $this->parser->idConnectionEnCours = $this->id_connection;
                        foreach ($this->lib_element_properties AS $id_lib_element) {
                            // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                            displayInDemon('<br />' . "\n" . '> > Traitement Lib Element : ' . $id_lib_element["lib_element_name"] . '<br />' . "\n\n");
                            //récupération de l'id de l'élément courant
                            $id_flat_file = $id_lib_element["id_flat_file"];
                            switch ($this->retrieve_mode) {
                                //AJOUT LE 11:44 10/10/2008 SCT depuis cb 3.0.4.01
                                // modif 11:10 08/07/2008 GHX
                                // On récupère la liste des fichiers qu'on a trouvé lors du premier scan
                                case "remote":
                                    $listingFiles = $this->get_flat_file_remote($id_flat_file, $conn_id, $location, $listingFiles);
                                    break;

                                case "local":
                                    $listingFiles = $this->get_flat_file_local($id_flat_file, $location, $listingFiles, $this->id_connection);
                                    __debug($listingFiles, "LISTING FILES");
                                    break;

                                case "remote_ssh":
                                    $listingFiles = $this->get_flat_file_remote_ssh($id_flat_file, $res_sftp, $location, $listingFiles, $this->id_connection);
                                    break;

                                default:
                                    break;
                            } // fin switch
                        }
                        // 16:33 23/09/2009SCT BZ 6828 => suppression des fichiers non utilisés par l'application
                        // 11:12 22/09/2009 SCT : on place ici le nettoyage des fichiers étrangers
                        // 06/07/2007 : Jérémy ->
                        //Suppression des fichiers inutiles. Cette fonction est placée ici pour n'être appellée qu'une seule fois pour chaque connexion
                        //Certains arguments ne sont parfois pas utilisés (ex : $conn_if en local et en ssh)
                        //La liste des fichier est dans une variable de classe plus pratique pour l'utiliser n'importe où
                        if ($listingFiles)
                            $this->delete_stranger_file($listingFiles, $conn_id, $res_sftp, $location);
                        // 09:32 30/10/2008 SCT : on remet la variable du stockage de l'arborescence à zéro
                        $listingFiles = false;
                        // 18:21 29/10/2008 SCT : déplacement de l'analyse des fichiers ZIP
                        //	* analyse du contenu du répertoire de stockage des fichiers extraits ZIP
                        //	* s'il y a des fichiers, on effectue une intégration de ces fichiers
                        $cheminRepertoireConnexionZip = $this->retrieve_parameters["repertoire_upload_zip"] . 'connexion_' . $this->id_connection . '/';
                        // on remet une couche sur le répertoire (dès fois qu'il n'existerait pas)
                        // 26/05/2011 NSE bz 22130 : ajout de true pour créer le répertoire et les répertoire partents si besoin
                        if (!is_dir($cheminRepertoireConnexionZip))
                            mkdir($cheminRepertoireConnexionZip, 0777, true);
                        if ($this->parser->extractionFichierZip > 0) {
                            // 07:34 30/10/2008 SCT : activation de la suppression des fichiers extraits du ZIP après traitement sinon accumulation
                            $this->deleteFichierExtraitZip = true;
                            // 09:32 30/10/2008 SCT : on remet la variable du stockage de l'arborescence à zéro
                            // 16:33 23/09/2009SCT BZ 6828 => suppression des fichiers non utilisés par l'application
                            //$listingFiles = false;
                            // 14:29 23/10/2008 SCT : suppression du tableau des références pour ne pas interférer avec collecte des fichiers d'une autre connexion
                            if (isset($this->tableauFichierRefCollecte))
                                unset($this->tableauFichierRefCollecte);
                            // il y a des fichiers, on analyse intègre le contenu du répertoire
                            foreach ($this->lib_element_properties AS $id_lib_element) {
                                // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                                displayInDemon('<br />' . "\n" . '> > Traitement ZIP Lib Element : ' . $id_lib_element['lib_element_name'] . '<br />' . "\n\n");
                                //récupération de l'id de l'élément courant
                                $id_flat_file = $id_lib_element["id_flat_file"];
                                // on indique que le type de connexion est local
                                $this->retrieve_mode = 'local';
                                // on force le traitement de l'analyse du répertoire local
                                $listingFiles = $this->get_flat_file_local($id_flat_file, $cheminRepertoireConnexionZip, $listingFiles, $this->id_connection);
                            } // fin foreach
                        }

                        // 06/07/2007 : Jérémy ->
                        //Suppression des fichiers inutiles. Cette fonction est placée ici pour n'être appellée qu'une seule fois pour chaque connexion
                        //Certains arguments ne sont parfois pas utilisés (ex : $conn_if en local et en ssh)
                        //La liste des fichier est dans une variable de classe plus pratique pour l'utiliser n'importe où
                        // 16:33 23/09/2009SCT BZ 6828 => suppression des fichiers non utilisés par l'application
                        //12:08 22/09/2009 SCT : modification
                        if ($listingFiles)
                            $this->delete_stranger_file($listingFiles, $conn_id, $res_sftp, $location);
                    }
                }
                else {
                    //On affiche le message d'erreur que si la sonde courante est en local
                    if ($this->retrieve_mode == "local")
                    // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                        displayInDemon('<br>Warning the directory "' . $location . '" does not exist.<br>' . "\n", 'alert');
                }
            }
        } else
        // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
            displayInDemon('No parser are activated for the group table', 'alert');
    }

// end function get_lib_element()

    /**
     * Function SaveMode
     * Fonction qui enregistre le mode FTP à utiliser pour la connexion en cours
     * @param integer : $mode (0=> passif - 1=actif) 
     */
    public function SaveMode($mode) {

        $query = "UPDATE sys_definition_connection SET connection_mode = {$mode} 
				   WHERE id_connection = {$this->id_connection}";
        displayInDemon($query);
        $this->database->execute($query);
    }

    /**
     * Function chooseBestMode
     * Fonction qui permet de choisir le meilleur mode FTP (passif ou actif) à utiliser
     * Si le mode est déjà enregistrer en base, on ne fait pas le contrôle
     * @param string $conn_id : Connexion FTP
     * @param string $currentDir : Répertoire source
     */
    public function chooseBestMode($conn_id, $currentDir) {

        if ($this->connexion_mode == '1' or $this->connexion_mode == '0') {

            if ($this->connexion_mode == 0) {
                @ftp_pasv($conn_id, true);
                displayInDemon("FTP Mode  : Passive\n");
            } else {
                @ftp_pasv($conn_id, false);
                displayInDemon("FTP Mode  : Active\n");
            }
        } else {

            // Test mode actif 
            if (!@ftp_pasv($conn_id, false)) {
                @ftp_pasv($conn_id, true);
                $this->saveMode(0);
                displayInDemon("Active mode not available<br />\nSwitch to Passive mode<br />\n");
                return true;
            }

            // Test mode passif 
            if (!@ftp_pasv($conn_id, true)) {
                @ftp_pasv($conn_id, false);
                $this->saveMode(1);
                displayInDemon("Passive mode not available<br />\nSwitch to Active mode<br />\n");
                return true;
            }

            // Test mode actif
            @ftp_pasv($conn_id, false);
            $debut = microtime(true);
            for ($t = 0; $t <= 10; $t++) {
                @ftp_rawlist($conn_id, $currentDir, true);
            }
            $fin = microtime(true);
            $timeActive = $fin - $debut;

            // Test mode passif
            @ftp_pasv($conn_id, true);
            $debut = microtime(true);
            for ($t = 0; $t <= 10; $t++) {
                @ftp_rawlist($conn_id, $currentDir, true);
            }
            $fin = microtime(true);
            $timePassive = $fin - $debut;


            // On se met dans le mode le plus rapide
            if ($timePassive < $timeActive) {
                // Mode passif choisi
                @ftp_pasv($conn_id, true);
                displayInDemon("FTP Mode chosen : Passive \n");
                // Sauvegarde du mode passif
                $this->saveMode(0);
            } else {
                // Mode actif choisi
                @ftp_pasv($conn_id, false);
                displayInDemon("FTP Mode chosen : Active \n");
                // Sauvegarde du mode actif
                $this->saveMode(1);
            }
        }
    }

    /**
     * Collecte en SSH la liste des fichiers d'un répertoire
     *
     * @param  SSHConnection $res Ressource SFTP
     * @param  string        $location Path du repertoire
     * @return array
     */
    function get_ssh_file_list(SSHConnection $res, $location) {
        $dirList = $res->listDir($location);
        $dirInfo = array();
        $nbEntries = count($dirList);
        for ($i = 0; $i < $nbEntries; $i++) {
            $dirInfo[$i] = $this->analysedir($dirList[$i]);
        }
        return $dirInfo;
    }

    /**
     * fonction qui collecte en FTP la liste des fichier d'un répertoire
     *
     * 26/02/2008 SCT : ajout de la fonction "ftp_mdtm" pour la récupération de la date de modification du fichier
     * 27/03/2008 SCT : Ajout du nom du fichier sur la recherche de la date de modification pour la reprise de données
     *      
     * @param identifiant $ de connection (via ftp_connect) $conn_id
     * @param repertoire $ à scanner $location
     */
    function get_ftp_file_list($conn_id, $location) {
        // NSE bz 14023
        // 2010/08/05 - MGD - BZ 14145 : Suppression du warning php (ajout de @), vu qu'on le traite dans le else
        if (@ftp_chdir($conn_id, $location)) {
            // $dirlist = ftp_rawlist($conn_id, ftp_pwd($conn_id));
            // SLE2 fix BZ 14023/14050, on est deja dans le repertoire $location, donc:
            // maj 24/02/2010 MPR : Correction du BZ 14477 : Problème avec certains serveurs FTP
            $dirlist = ftp_rawlist($conn_id, "");
            for ($i = 0; $i < count($dirlist); $i++) {
                $dirinfo[$i] = $this->analysedir($dirlist[$i]);
                /* SLE2 fix : $dirlist est une string, pas un tableau !! ce qui suit envoyait la commande "MTDM ../w", ce qui n'avait jamais de sens evidemment
                  et ce, deux fois par fichier ou repertoire trouve dans le repertoire courant
                  on remplace donc par $dirinfo, qui est bien un tableau (et celui qui nous interesse), et on envoie une seule commande par fichier : ca coute cher en temps le reseau ! :)
                  if( $this->retrieve_search_directory == 1 && ftp_mdtm($conn_id, $location.'/'.$dirlist[$i][2]) != -1)
                  $dirinfo[$i][3] = date('Y_M_d_H_i', ftp_mdtm($conn_id, $location.'/'.$dirlist[$i][2]));
                 */
                if (( $this->retrieve_search_directory == 1 ) && (($mdtm = ftp_mdtm($conn_id, $location . '/' . $dirinfo[$i][2])) != -1))
                    $dirinfo[$i][3] = date('Y_M_d_H_i', $mdtm);
            }
        }
        else {
            $dirinfo = array();
            // 2010/08/05 - MGD - BZ 14145 : on a pas pu aller dans le repertoire, donc on affiche une erreur
            $message = __T("A_E_CONTEXT_DIRECTORY_NOT_EXISTS", $location);
            sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
            displayInDemon("The directory '$location' doesn't exists on the FTP server");
        }
        return $dirinfo;
    }

// function get_ftp_file_list

    /**
     * fonction qui collecte la liste des fichier d'un répertoire en local (sur le même serveur)
     *
     * 26/02/2008 SCT : construction d'une variable "date_modification" depuis la fonction "filemtime"
     * 
     * @param repertoire $ à scanner $location
     */
    function get_local_file_list($location) {
        chdir($location);
        $handle = opendir('.');
        $i = 0;
        while ($file = readdir($handle)) {
            // 11/12/2009 BBX : on ne prend pas les fichiers cachés. BZ 12469			
            //if($file != "." && $file != "..")
            if (substr($file, 0, 1) != '.') {
                if (is_dir($file))
                    $dirinfo[$i][0] = 1;
                $dirinfo[$i][1] = filesize($file);
                $dirinfo[$i][2] = $file;
                $dirinfo[$i][3] = date("Y_M_d_H_i", filemtime($file));
                $i++;
            }
        }
        //__debug($dirinfo,"Dir Info");
        closedir($handle);
        return $dirinfo;
    }

// end function get_local_file_list()

    /* 06/07/2007 : Jérémy
     * *Fonction qui récupère la liste des fichiers qui est retournée par les fonctions "get_MODE_file_list"
     * Elle consiste à boucler sur les fichiers retournés et vérifier qu'ils sont connus du parser, si ce n'est pas le cas, alors ils sont supprimer
     *
     * @param   $dirinfo		:	liste de fichiers contenus dans le répertoire de récupérétion des données :
     * @param   $conn_id		:	identifiant de connection en mode FTP
     * @param   $ip			:	adresse ip à laquelle est accessible le répertoire
     * @param   $login		:	login pour se connecter en mode FTP et SSH
     * @param   $location		:	chemin du répertoire dans lequel sont stockés les fichiers à traiter
     */

    function delete_stranger_file($dirinfo, $conn_id, $sftpRes, $location) {
        // 16:33 23/09/2009SCT BZ 6828 => suppression des fichiers non utilisés par l'application
        // 16:05 23/09/2009 GHX
        // Prise en compte du paramètre "retrieve_delete_file" de sys_global_parameters
        if ($this->retrieve_delete_file == 0)
            return;

        //INITIALISATION
        // 16:33 23/09/2009SCT BZ 6828 => suppression des fichiers non utilisés par l'application
        $delete = false;
        $nb_fichier = count($dirinfo);
        $nb_element = count($this->lib_element_properties);  //Nombre d'éléments connus (R04, 60...)
        // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
        //displayInDemon('<br><br>'."\n");
        //Boucle sur tous les fichiers du répertoire
        // 16:33 23/09/2009SCT BZ 6828 => suppression des fichiers non utilisés par l'application
        // remplacemet du for par foreach et ajout d'une condition du l'existance de la variable "$dirinfo"
        // for($i=0 ; $i < $nb_fichier ; $i++)
        if (isset($dirinfo) && ($dirinfo != false || count($dirinfo) > 0)) {
            // 16:33 23/09/2009SCT BZ 6828 => suppression des fichiers non utilisés par l'application
            $location = str_replace('//', '/', $location);
            if (substr($location, -1) != '/')
                $location .= '/';

            foreach ($dirinfo AS $dirinfo_index => $dirinfo_array) {
                // 16:33 23/09/2009SCT BZ 6828 => suppression des fichiers non utilisés par l'application
                // remplacement $i par $dirinfo_index
                $source = $dirinfo[$dirinfo_index][2];
                //Boucle sur les différents templates connus du parser (et dont le champ 'on_off' est à 1=on)
                if (isset($this->lib_element_properties_off) && count($this->lib_element_properties_off) > 0) {
                    foreach ($this->lib_element_properties_off AS $element) {
                        //Execution seulement si le drapeaux "DELETE" est à false
                        if (!$delete) {
                            $template = $element["lib_element_template"];
                            if (preg_match($template, $source))
                                $delete = true;
                        }
                    }

                    // Si le template correspondant au fichier courant n'a pas été trouvé alors on supprime le fichier
                    if ($delete) {
                        ////__debug($source,"Chemin");
                        //EFFACEMENT DU FICHIER
                        switch ($this->retrieve_mode) {
                            case "remote":
                                // 16:33 23/09/2009SCT BZ 6828 => suppression des fichiers non utilisés par l'application
                                // remplacement par $dirinfo_index
                                ftp_delete($conn_id, $dirinfo_index);
                                ////__debug("tentative de suppression du fichier $source");
                                break;

                            case "local":
                                // 16:33 23/09/2009SCT BZ 6828 => suppression des fichiers non utilisés par l'application
                                // remplacement par $dirinfo_index
                                unlink($dirinfo_index);
                                break;

                            default :
                            case "remote_ssh":
                                try {
                                    $sftpRes->unlink($dirinfo_index);
                                } catch (Exception $e) {
                                    // Nothing
                                }
                                break;
                        } // fin switch 
                        // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                        displayInDemon('<br>Le fichier "' . $source . '" non reconnu a été supprimé', 'alert');
                    }
                }

                //réinitialisation de la variable delete
                // 16:33 23/09/2009SCT BZ 6828 => suppression des fichiers non utilisés par l'application
                // remplacement true par false
                $delete = false;
            }
            // 16:33 23/09/2009SCT BZ 6828 => suppression des fichiers non utilisés par l'application
            // 11:46 22/09/2009 SCT : ajout de la fin de la condition
        }
        // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
        displayInDemon('<br><br>' . "\n");
    }

// end function delete_stranger_file()

    /**
     * *Fonction qui récupère un fichier soit en FTP soit en direct si on est en local
     * la fonction compare la taille initiale du fichier lorsqu'on a scanné le répertoire et la taille au moment du traitement
     * si les tailles sont différentes, cela signifie que le fichier est en cours de téléchargement.
     * 
     * 26/02/2008 SCT : 
     * 	+ ajout d'un paramètre sur la fonction ($file_information : les informations du fichier)
     * 	+ modification de la fonction : option de recherche dans un sous-répertoire et option de suppression des fichiers nuls
     *
     * @param identifiant $ de connection (via ftp_connect) $conn_id
     * @param nom $ du fichier source $source
     * @param nom $ et chemin de destination $destintation
     * @param taille $ initial du fichier source determinée lorsque le repertoire a été scanné
     * @param array $file_information les informations du fichier
     */
    function upload_flat_file($id_lib_element, $sftpRes, $conn_id, $source, $destination, $location, $taille_initiale, $file_information) {
        //AJOUT LE 11:44 10/10/2008 SCT depuis cb 3.0.4.01
        //  modif 11:13 10/07/2008 GHX
        // petite correction suite à la modification du scan
        $location = str_replace('//', '/', $location);
        if (substr($location, -1) == '/')
            $location = substr($location, 0, strlen($location) - 1);
        // telecharge le fichier à l'emplacement voulu
        $upload = false;
        $number_try = 0;
        unset($this->source_transformee);
        while (!$upload AND $number_try <= 2) {
            // création d'une variable temporaire contenant le nom du fichier avec son chemin pour un stockage en base de données
            $lExtensionReference = str_replace('*', '', $this->lib_element_properties[$id_lib_element]['lib_element_naming_template']);
            $lExtensionReference = str_replace('$', '', $lExtensionReference);
            // 14:13 15/12/2009 SCT : BZ 13466, 13467 => template sensible à la casse
            //	+ passage en mode stripos
            $temp_fichier_dispo_court = $location . '/' . substr($source, 0, stripos($source, $lExtensionReference));
            // 15:59 22/10/2008 SCT : dans le cas où le fichier traité est considéré comme un fichier de référence =>
            //	- on crée un tableau pour stocker son nom
            //	- le fichier peut avoir été collecté : si jamais c'est le cas, on le charge tout de même dans le tableau afin de pouvoir récupérer un fichier de données arrivé en retard ou modifié
            if ($this->lib_element_properties[$id_lib_element]['lib_element_naming_template'] == $this->lib_element_properties[$id_lib_element]['reference'])
            // on stocke le fichier de référence dans le tableau prévu à cet effet
                $this->tableauFichierRefCollecte[$this->lib_element_properties[$id_lib_element]['reference']][] = $temp_fichier_dispo_court;

            // 14:17 23/10/2008 SCT : dans le cas d'un fichier appartenant à un groupe de référence (sans qu'il soit le fichier de référence), on vérifie qu'il appartient bien à un groupe de référence
            if ($this->lib_element_properties[$id_lib_element]['lib_element_naming_template'] != $this->lib_element_properties[$id_lib_element]['reference']) {
                // 16:22 20/02/2009 SCT #8881 : le groupe de référence de ce fichier a été rejeté : le header du fichier de référence est invalide
                if (isset($this->tableauFichierRefIgnore[$this->lib_element_properties[$id_lib_element]['reference']]) && in_array($temp_fichier_dispo_court, $this->tableauFichierRefIgnore[$this->lib_element_properties[$id_lib_element]['reference']])) {
                    //  NSE 14838 on supprime les fichiers dont le fichier de référence a été rejeté.
                    // efface le fichier 
                    switch ($this->retrieve_mode) {
                        case "remote":
                            // 10/10/2008 SCT depuis cb 3.0.4.01
                            // modif 13:53 08/07/2008 GHX
                            ftp_delete($conn_id, $location . '/' . $source);
                            break;

                        case "local":
                            //MODIF LE 12:07 10/10/2008 SCT depuis cb 3.0.4.01
                            // modif 13:53 08/07/2008 GHX
                            unlink($location . '/' . $source);
                            break;

                        default :
                        case "remote_ssh" :
                            try {
                                $sftpRes->unlink($location . '/' . $source);
                            } catch (Exception $e) {
                                // Nothing
                            }
                            break;
                    } // switch
                    // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                    if ($this->debug)
                        displayInDemon('Fichier ' . $file_information[2] . ' : La référence pour ce groupe a été rejetée, le fichier l\'est aussi [' . $source . '] => suppression du fichier<br>' . "\n");
                    break;
                }
                // le groupe de référence existe
                if (isset($this->tableauFichierRefCollecte[$this->lib_element_properties[$id_lib_element]['reference']])) {
                    // ICI =>
                    // 	- le fichier appartient à un groupe de référence
                    //	- le fichier n'est pas un fichier de référence
                    //	- le tableau de référence existe
                    if (!in_array($temp_fichier_dispo_court, $this->tableauFichierRefCollecte[$this->lib_element_properties[$id_lib_element]['reference']])) {
                        // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                        if ($this->debug)
                            displayInDemon('Fichier ' . $file_information[2] . ' : Aucune référence n\'est disponible pour ce fichier<br>' . "\n");
                        break;
                    }
                }
                // le fichier appartient à un groupe de référence mais le fichier de référence n'a pas été trouvé (cas d'un seul groupe dans la collecte, sans référence) et le fichier n'est pas un fichier ZIP (dans ce cas, la référence dans la table est bien renseignée mais n'est pas utilisée comme référence)
                if ($this->lib_element_properties[$id_lib_element]['reference'] != '' && !isset($this->tableauFichierRefCollecte[$this->lib_element_properties[$id_lib_element]['reference']]) && $this->lib_element_properties[$id_lib_element]['reference'] != 'ZIP') {
                    // ICI =>
                    //	- le fichier de données appartient à un groupe de collecte
                    //	- le fichier de référence n'existe pas
                    //	- cas de la collecte d'un seul groupe de fichier
                    // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                    if ($this->debug)
                        displayInDemon('Fichier ' . $file_information[2] . ' : Aucune référence n\'est disponible pour ce fichier<br>' . "\n");
                    break;
                }
            }

            // modif 14:01 03/04/2008 GHX
            // correction de BUG 6222 & BUG 6225			
            if ($this->retrieve_search_directory == 1) { // mode récursif
                // 15/02/2008 SCT : transformation du nom source afin de voir s'il existe dans les fichiers archivés
                $source_transformee = ereg_replace('/', '_', $location . '_' . $source);
                // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                // 08/04/2011 BBX
                // Ce message ne sera désormais affiché qu'en mode débug
                // BZ 20643
                if ($this->debug) {
                    displayInDemon('source_transformée : ' . $source_transformee . '<br>' . "\n");
                }

                // 14/12/2009 NSE : optimisation de la collecte
                $uniqid = $this->connexion_name . $source_transformee;

                $query = "SELECT flat_file_uniqid, modification_date FROM sys_flat_file_uploaded_list_archive WHERE flat_file_uniqid = '" . $uniqid . "';";
                $result = $this->database->execute($query);
                // 14/12/2009 NSE : optimisation de la collecte modification des conditions
                if ($values = $this->database->getQueryResults($result, 1)) {
                    if ($values["modification_date"] == $file_information[3]) {
                        // fin des modifications des conditions  14/12/2009 NSE : optimisation de la collecte

                        /* 	14/12/2009 NSE : optimisation de la collecte : version conditions avec le tableau abandonnée
                          // 15/02/2008 SCT : vérification que le fichier n'a pas déjà été joué OU cas d'une reprise de données
                          if(isset($this->archived_files_info) && in_array($this->connexion_name.$source_transformee, $this->archived_files_info['flat_file_uniqid'])) // la présence du fichier
                          {
                          $temp_cle_tableau = array_search($this->connexion_name.$source_transformee, $this->archived_files_info['flat_file_uniqid']);
                          if($this->archived_files_info['modification_date'][$temp_cle_tableau] == $file_information[3]) // on vérifie maintenant que la date de modification du fichier est bien celle enregistrée
                          { 14/12/2009 NSE : optimisation de la collecte : fin version conditions avec le tableau abandonnée */
                        // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                        // 08/04/2011 BBX
                        // Ce message ne sera désormais affiché qu'en mode débug
                        // BZ 20643
                        if ($this->debug) {
                            displayInDemon('Fichier ' . $file_information[2] . ' : le fichier est déjà connu de la bdd (il a déjà été intégré et aucune modification depuis)<br>' . "\n");
                        }
                        // 30/11/2009 BBX : suppression du fichier de référence de la liste des fichiers de références uploadés. BZ 13136
                        if (isset($this->tableauFichierRefCollecte[$this->lib_element_properties[$id_lib_element]['reference']])) {
                            $offsetToDelete = array_search($temp_fichier_dispo_court, $this->tableauFichierRefCollecte[$this->lib_element_properties[$id_lib_element]['reference']]);
                            unset($temp_fichier_dispo_court, $this->tableauFichierRefCollecte[$this->lib_element_properties[$id_lib_element]['reference']][$offsetToDelete]);
                        }
                        // FIN BZ 13136


                        break;
                    }
                    // 09:23 11/12/2009 SCT : BZ 11505 => fichiers avec src_guid identiques intégrés
                    // Dans le mode récursif, le GUID est construit sur le chemin du fichier et non de fichier. Sachant qu'on ne peut pas avoir 2 fichiers identiques de même nom dans un unique chemin répertoire, la vérification ici n'est pas nécessaire
                } // le fichier est inconnu [on intègre] OU le fichier est connu mais la date de modification est modifiée [on intègre pour la reprise de donnée]
                else {
                    // 08/04/2011 BBX
                    // Ce message ne sera désormais affiché qu'en mode débug
                    // BZ 20643
                    if ($this->debug) {
                        // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                        displayInDemon('Fichier ' . $file_information[2] . ' : le fichier est récent, il faut l analyser<br>' . "\n");
                    }
                }
            } else { // mode non récursif
                $source_transformee = $source;

                // 11/12/2009 BBX
                // Gestion de la lecture du GUID en local ou FTP. BZ 13322
                switch ($this->retrieve_mode) {
                    default :
                    case "local" :
                        $unique_identifier = $this->parser->get_unique_identifier($location . '/' . $source, $this->lib_element_properties[$id_lib_element]["lib_element_naming_template"], $source_transformee);
                        break;

                    case "remote":
                        // 17/03/2011 OJT/SCT : Reopen bz19807, on télécharge directement au bon format (ASCII ou BINARY)
                        displayInDemon('Downloading file to get GUID...', 'normal');
                        if (method_exists($this->parser, 'get_file_type'))
                            $type_fichier = $this->parser->get_file_type($location . '/' . $source);
                        else
                            $type_fichier = 'FTP_ASCII';
                        // on lance la commande de récupération du fichier en fonction du type de fichier
                        if ($type_fichier == 'FTP_ASCII')
                            $ftpGet = ftp_get($conn_id, $destination, $location . '/' . $source, FTP_ASCII);
                        else
                            $ftpGet = ftp_get($conn_id, $destination, $location . '/' . $source, FTP_BINARY);

                        // Lecture du UniqId
                        $unique_identifier = '';
                        if ($ftpGet) {
                            $unique_identifier = $this->parser->get_unique_identifier($destination, $this->lib_element_properties[$id_lib_element]["lib_element_naming_template"], $source_transformee);
                            displayInDemon('File stored in ' . $destination, 'normal');
                        }

                        break;

                    case "remote_ssh" :
                        displayInDemon('Downloading file to get GUID...', 'normal');
                        try {
                            $sftpRes->getFile($location . '/' . $source, $destination);
                            $unique_identifier = $this->parser->get_unique_identifier($destination, $this->lib_element_properties[$id_lib_element]["lib_element_naming_template"], $source_transformee);
                            displayInDemon('File stored in ' . $destination, 'normal');
                        } catch (Exception $ex) {
                            displayInDemon($ex->getMessage(), 'alert');
                        }
                        break;
                } // switch
                // 10:20 24/04/2008 SCT : la modification de GHX bloque la reprise de données
                // 30/11/2009 : On décommente car sinon, le GUID n'est pas testé. Et non, la reprise de données n'est pas bloquée. BZ 9376
                // 14/12/2009 NSE : optimisation de la collecte conditions sans tableau
                $uniqid = $unique_identifier; //$this->connexion_name.$source_transformee;

                $query = "SELECT flat_file_uniqid, modification_date FROM sys_flat_file_uploaded_list_archive WHERE flat_file_uniqid = '" . $uniqid . "';";
                $result = $this->database->execute($query);

                if ($values = $this->database->getQueryResults($result, 1)) {
                    if ($values["modification_date"] == $file_information[3]) {

                        /* 	14/12/2009 NSE : optimisation de la collecte : version conditions avec le tableau abandonnée

                          if(isset($this->archived_files_info) && in_array($unique_identifier, $this->archived_files_info['flat_file_uniqid'])) // la présence du fichier
                          {
                          // 14:49 14/12/2009 SCT : BZ 13431 => la reprise de données n'est pas opérationnelle pour le produit GSM
                          $temp_cle_tableau = array_search($unique_identifier, $this->archived_files_info['flat_file_uniqid']);
                          if($this->archived_files_info['modification_date'][$temp_cle_tableau] == $file_information[3]) // on vérifie maintenant que la date de modification du fichier est bien celle enregistrée
                          { 14/12/2009 NSE : optimisation de la collecte fin version conditions avec le tableau abandonnée */
                        // 08/04/2011 BBX
                        // Ce message ne sera désormais affiché qu'en mode débug
                        // BZ 20643
                        if ($this->debug) {
                            displayInDemon('Fichier ' . $file_information[2] . ' : il a déjà été intégré<br>' . "\n");
                        }
                        // 16:45 11/12/2009 SCT : on ajoute la suppression du fichier de référence (déjà intégré) du tableau "tableauFichierRefCollecte" => on évite de collecter ainsi les fichiers de données
                        if (isset($this->tableauFichierRefCollecte[$this->lib_element_properties[$id_lib_element]['reference']])) {
                            $offsetToDelete = array_search($temp_fichier_dispo_court, $this->tableauFichierRefCollecte[$this->lib_element_properties[$id_lib_element]['reference']]);
                            unset($temp_fichier_dispo_court, $this->tableauFichierRefCollecte[$this->lib_element_properties[$id_lib_element]['reference']][$offsetToDelete]);
                        }

                        // modif 11:44 07/04/2008 GHX
                        //  correction du bug 6309[REC][T&A Cigale Roaming 3.0] : Fichier déjà intégré non supprimer du répertoire de collecte.
                        if ($this->retrieve_delete_file == 1) {
                            // efface le fichier uploade
                            switch ($this->retrieve_mode) {
                                case "remote":
                                    ftp_delete($conn_id, $source);
                                    break;

                                case "local":
                                    unlink($source);
                                    break;

                                default :
                                case "remote_ssh" :
                                    try {
                                        $sftpRes->unlink($source);
                                    } catch (Exception $e) {
                                        
                                    }
                                    break;
                            } // switch
                        }
                        break;
                    } // fin BZ 13431
                }
                // 09:23 11/12/2009 SCT : BZ 11505 => fichiers avec src_guid identiques intégrés
                // dans le cas où il n'a pas encore été intégré, on teste 
                //	+ si le GUID a déjà été intégré lors de cette collecte
                // 	+ on vérifie que le GUID n'est pas vide : cas du fichier de données 2X.txt
                if (isset($this->stockageGuidFile) && $unique_identifier != '' && in_array($unique_identifier, $this->stockageGuidFile)) { // if au lieu de elseif car la première passe peut être u nouveau groupe de fichier et la deuxième passe un groupe de fichier déjà intégré dont le GUID est identique à celui du groupe de la première passe
                    // un fichier contenant le même GUID a déjà été intégré lors de cette collecte
                    //	* message d'erreur
                    displayInDemon('Fichier ' . $file_information[2] . ' : le GUID a déjà été intégré lors de cette collecte. Le fichier est supprimé sinon la reprise de données ira remplacer le GUID intégré lors de cette collecte<br>' . "\n");
                    //	* message dans le tracelog
                    $message = "File " . $file_information[2] . " for hour : " . $this->hour . " can''t be loaded : GUID already collect during this process. File deleted";
                    sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
                    // on dépile l'élément de référence du tableau des références à intégrer => on traite en premier les références des groupes => on est pas encore arrivé au traitement des fichiers de données
                    if (isset($this->tableauFichierRefCollecte[$this->lib_element_properties[$id_lib_element]['reference']])) {
                        $offsetToDelete = array_search($temp_fichier_dispo_court, $this->tableauFichierRefCollecte[$this->lib_element_properties[$id_lib_element]['reference']]);
                        unset($temp_fichier_dispo_court, $this->tableauFichierRefCollecte[$this->lib_element_properties[$id_lib_element]['reference']][$offsetToDelete]);
                    }

                    // efface le fichier uploade => obligatoire sinon on effectuera une reprise de données lors de la prochaine collecte en remplaçant le fichier ayant le même GUID présent dans cette collecte
                    switch ($this->retrieve_mode) {
                        case "remote":
                            ftp_delete($conn_id, $source);
                            break;

                        case "local":
                            unlink($source);
                            break;

                        default :
                        case "remote_ssh" :
                            try {
                                $sftpRes->unlink($source);
                            } catch (Exception $ex) {
                                
                            }
                            break;
                    } // switch
                    break;
                }
                // on stocke le GUID
                else {
                    // seulement dans le cas où le GUID est différent de vide
                    if ($unique_identifier != '') {
                        $this->stockageGuidFile[] = $unique_identifier;
                    }
                }
                // FIN BZ 11505
                // FIN BZ 9376
            }

            // teste si le telechargement a marche ou pas
            // et si le nombre de retry est <2 (on fait au maximum 5 retry
            // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
            // 08/04/2011 BBX
            // Ce message ne sera désormais affiché qu'en mode débug
            // BZ 20643
            if ($this->debug) {
                displayInDemon("\n" . '<br>> > > Source : ' . $location . '/' . $source . '<br>' . "\n");
                displayInDemon('> > > Destination : ' . $destination . '<br>' . "\n");
            }
// On récupère la taille du fichier afin de voir :
            //	- s'il n'est pas vide
            //	- si la taille n'a pas bougée (dans ce cas, le fichier est en cours de création sur le serveur)
            switch ($this->retrieve_mode) {
                case "remote":
                    //MODIF LE 11:44 10/10/2008 SCT depuis cb 3.0.4.01
                    // modif 13:53 08/07/2008 GHX
                    $nouvelle_taille = ftp_size($conn_id, $location . '/' . $source);
                    break;

                case "local":
                    //MODIF LE 11:44 10/10/2008 SCT depuis cb 3.0.4.01
                    // modif 13:53 08/07/2008 GHX
                    $nouvelle_taille = filesize($location . '/' . $source);
                    break;

                default :
                case "remote_ssh":
                    try {
                        $nouvelle_taille = $sftpRes->fileSize($location . '/' . $source);
                    } catch (Exception $ex) {
                        displayInDemon($ex->getMessage(), 'alert');
                    }
                    break;
            }
            if ($nouvelle_taille != 0) {
                if ($nouvelle_taille == $taille_initiale || !$upload) {
                    switch ($this->retrieve_mode) {
                        case "remote":
                            // 29/12/2010 BBX
                            // On regarde si le fichier a déjà été téléchargé
                            // BZ 19807
                            displayInDemon('Getting file for treatment', 'normal');
                            if (file_exists($destination) && (filesize($destination) > 0)) {
                                displayInDemon('File already downloaded. Will use ' . $destination, 'normal');
                                $upload = true;
                                break;
                            }

                            //MODIF LE 11:44 10/10/2008 SCT depuis cb 3.0.4.01
                            // modif 13:53 08/07/2008 GHX
                            // 15:52 20/10/2008 SCT : Bug 7696 (problème de récupération des fichiers binaires)
                            // dans le cas où la fonction de reconnaissance du type de fichier existe, on l'utilise
                            // dans les autres cas, on force en mode FTP_ASCII
                            if (method_exists($this->parser, 'get_file_type'))
                                $type_fichier = $this->parser->get_file_type($location . '/' . $source);
                            else
                                $type_fichier = 'FTP_ASCII';
                            // on lance la commande de récupération du fichier en fonction du type de fichier
                            if ($type_fichier == 'FTP_ASCII')
                                $upload = ftp_get($conn_id, $destination, $location . '/' . $source, FTP_ASCII);
                            else
                                $upload = ftp_get($conn_id, $destination, $location . '/' . $source, FTP_BINARY);
                            // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                            displayInDemon('FTP connection type : ' . $type_fichier . '<br>' . "\n");
                            break;

                        case "local":
                            //MODIF LE 11:44 10/10/2008 SCT depuis cb 3.0.4.01
                            // modif 13:53 08/07/2008 GHX
                            $upload = copy($location . '/' . $source, $destination);
                            displayInDemon("local -copy" . $location . '/' . $source, $destination);
                            break;

                        default :
                        case "remote_ssh":
                            $upload = true;
                            displayInDemon('Getting file for treatment', 'normal');
                            if (file_exists($destination) && (filesize($destination) > 0)) {
                                displayInDemon('File already downloaded. Will use ' . $destination, 'normal');
                                break;
                            }
                            try {
                                $sftpRes->getFile($location . '/' . $source, $destination);
                            } catch (Exception $ex) {
                                displayInDemon($ex->getMessage(), 'alert');
                                $upload = false;
                            }
                            break;
                    } // switch
                    if ($upload) {
                        // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                        if ($this->debug)
                            displayInDemon('> > > *(' . filesize($destination) . ' ko)<br>' . "\n");
                        $this->source_transformee = $source_transformee;

                        // 19/02/2008 SCT : en fonction du paramétrage de sys_global_parameters, on déplace ou on copie les fichiers
                        // 07:37 30/10/2008 SCT : ajout du paramètre $this->deleteFichierExtraitZip qui est activé si le parser a extrait des fichiers d'archive ZIP
                        if ($this->retrieve_delete_file == 1 || $this->deleteFichierExtraitZip) {
                            // efface le fichier uploade
                            switch ($this->retrieve_mode) {
                                case "remote":
                                    //MODIF LE 12:07 10/10/2008 SCT depuis cb 3.0.4.01
                                    // modif 13:53 08/07/2008 GHX
                                    ftp_delete($conn_id, $location . '/' . $source);
                                    // print "j'efface<br>";
                                    break;

                                case "local":
                                    //MODIF LE 12:07 10/10/2008 SCT depuis cb 3.0.4.01
                                    // modif 13:53 08/07/2008 GHX
                                    unlink($location . '/' . $source);
                                    break;

                                default :
                                case "remote_ssh":
                                    try {
                                        $sftpRes->unlink($location . '/' . $source);
                                    } catch (Exception $ex) {
                                        
                                    }
                                    break;
                            } // switch
                        }
                        // stocke puis retourne le nom de la source collectée ainsi que l'heure
                        $source_info["source_name"] = $source;
                        $source_info["heure_upload"] = date("d F Y - H:i:s");
                        return $source_info;
                    } else {
                        // 27-03-2008 SCT : bug 6131 => modification du message en cas de fichier non récupéré
                        // envoie un message d'erreur si le ftp n'a pas marche
                        // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                        displayInDemon('UNABLE TO GET File ' . $location . '/' . $source . ' on try #' . $number_try . '<br>' . "\n", 'alert');

                        // 30/08/2011 BBX
                        // BZ 23559 : On ne renvoie false qu'après avoir effectuer les 3 tentatives
                        if ($number_try == 2) {
                            // 27-03-2008 SCT : bug 6131 => modification du message en cas de fichier non récupéré
                            $message = "UNABLE TO GET File $location/$source for hour : " . $this->hour;
                            sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");

                            // 29/12/2010 BBX
                            // En cas d'échec sur une connexion FTP
                            // On retourne faux pour ne pas traiter le fichier par la suite
                            // Afin qu'il ne soit pas inséré en base
                            // BZ 19807
                            if ($this->retrieve_mode == 'remote' || $this->retrieve_mode == 'remote_ssh')
                                return false;
                        }

                        sleep(1); //attend 3s avant la nouvelle tentative
                        $number_try++;
                    }
                }
                else {
                    // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                    displayInDemon('file ' . $source . ' is being uploaded<br>' . "\n");
                    return false;
                }
            } else {
                // je detruit les fichiers de taille 0
                // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                displayInDemon('> > > - ' . $source . ' : fichier de taille 0<br>' . "\n", 'alert');
                if ($this->retrieve_delete_file == 1) {
                    // efface le fichier uploade
                    switch ($this->retrieve_mode) {
                        case "remote":
                            //MODIF LE 12:07 10/10/2008 SCT depuis cb 3.0.4.01
                            // modif 13:53 08/07/2008 GHX
                            ftp_delete($conn_id, $location . '/' . $source);
                            // print "j'efface<br>";
                            break;

                        case "local":
                            //MODIF LE 12:07 10/10/2008 SCT depuis cb 3.0.4.01
                            // modif 13:53 08/07/2008 GHX
                            unlink($location . '/' . $source);
                            break;

                        default:
                        case "remote_ssh" :
                            try {
                                $sftpRes->unlink($location . '/' . $source);
                            } catch (Exception $ex) {
                                
                            }
                            break;
                    }
                    //MODIF LE 12:07 10/10/2008 SCT depuis cb 3.0.4.01
                    // modif 13:53 08/07/2008 GHX
                    // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                    displayInDemon('> > > - ' . $location . '/' . $source . ' detruit</font><br>' . "\n", 'alert');
                } else
                //MODIF LE 12:07 10/10/2008 SCT depuis cb 3.0.4.01
                // modif 13:53 08/07/2008 GHX
                // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                    displayInDemon('> > > - ' . $location . '/' . $source . ' non detruit : option désactivée</font><br>' . "\n", 'alert');

                $message = __T('A_FLAT_FILE_UPLOAD_ALARM_FILE_NOT_UPLOADED', $source);
                // maj 04/07/2008 Benjamin : correction du nom de la fonction "__T" au lieu de "__". BZ7060
                sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
                $upload = true;
            }
        }
    }

// end function upload_flat_file()

    /**
     * *Fonction qui va collecter un lib_element en mode FTP
     *
     * AJOUT LE 12:07 10/10/2008 SCT depuis cb 3.0.4.01
     * 	08/07/2008 GHX  : modification du principe de scan, on ne parcours plus qu'une seule fois les répertoires.
     *
     * 26/02/2008 SCT : option pour la recherche récursive dans les sous-répertoires
     * 
     * @param identifiant $ du lib_element $id_lib_element
     * @param identifiant $ de connection (via ftp_connect) $conn_id
     * @param repertoire $ source $location
     * @param tableau $files => tableau contenant les fichiers déjà scannés pour la connexion en cours
     */
    function get_flat_file_remote($id_lib_element, $conn_id, $location, $files) {
        // AJOUT LE 12:15 10/10/2008 SCT depuis cb 3.0.4.01
        // modif 11:13 08/07/2008 GHX
        // Dans le cas ou $files est faux on est sur le premier scan donc on parcout tous les répertoires.
        // 15:52 22/10/2008 SCT : si le template
        if ($files == false) {
            // 06/07/2007 : Jérémy - File_list est devenue une variable de classe plus pratique à manipuler surtout pour l'utilisation de la méthode : "delete_stranger_file"
            $file_list = $this->get_ftp_file_list($conn_id, $location);
            //__debug($file_list,'$file_list');
            // ce sleep permet d'attendre pour voir si entre temps, les fichiers contenus dans la liste on changé de taille
            // s'ils ont changé de taille, cela signifie donc qu'ils sont en cours de téléchargement et donc qu'il ne faut pas les prendre
            $nb_file = count($file_list);
            $files = array();
            if ($nb_file % 1 == 0 AND $nb_file > 0) {
                // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                displayInDemon($nb_file . ' élément(s) trouvé(s)<br>' . "\n");
                sleep(1);
                $naming_template = $this->lib_element_properties[$id_lib_element]["lib_element_naming_template"];
                $template = $this->lib_element_properties[$id_lib_element]["lib_element_template"];
                // 16:33 23/09/2009SCT BZ 6828 => suppression des fichiers non utilisés par l'application
                // 10:51 22/09/2009 SCT : destruction de la référence
                foreach ($file_list AS $file_index => $file_information) {
                    // 15/02/2008 SCT : si l'élément analysé en cours est un répertoire, on recharge la fonction get_flat_file_remote pour analyser le contenu
                    // 11/05/2011 OJT : On ne gère pas les nom de dossiers ne contenant que des espaces.
                    if ($file_information[0] == 1 && $this->retrieve_search_directory == 1 && strlen(trim($file_information[2])) > 0) {
                        // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                        if ($this->debug)
                            displayInDemon('on se trouve dans le cas d un répertoire (' . $location . '/' . $file_information[2] . '), on explore le niveau<br>' . "\n");
                        $result_files = $this->get_flat_file_remote($id_lib_element, $conn_id, $location . '/' . $file_information[2], false);

                        $files = array_merge($files, $result_files);
                        continue;
                    }
                    $files[$location . '/' . $file_information[2]] = $file_information;

                    // dans le cas où le fichier analysé possède l'extension recherchée, on prépare la collecte du fichier
                    if (preg_match($template, $file_information[2])) {
                        // 16:33 23/09/2009SCT BZ 6828 => suppression des fichiers non utilisés par l'application
                        // 10:42 22/09/2009 SCT : destruction de la référence du fichier qui vient d'être uploadé
                        unset($file_list[$file_index]);
                        unset($files[$location . '/' . $file_information[2]]);

                        $source = $file_information[2];
                        $taille_initiale = $file_information[1];
                        $destination = $this->retrieve_parameters["repertoire_upload"] . uniqid("") . ".txt";
                        $source_info = $this->upload_flat_file($id_lib_element, null, $conn_id, $source, $destination, $location, $taille_initiale, $file_information);
                        if ($source_info) {
                            // 12:11 24/10/2008 SCT  : ajout du paramètre $location dans le cas du traitement des fichiers ZIP
                            $this->store_uploaded_file($destination, $naming_template, $source_info, $file_information, $location, $id_connection);
                            // 14-03-2008 SCT : Bug#6029 => Affichage d'une ligne dans le tracelog pour chaque fichier scanné [déplacement du fichier dans la boucle]
                            // mets dans la log les informations relatives à la collecte des fichiers
                            $this->log_retrieve($source_info);
                        }
                    }
                }
            } else
            // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                displayInDemon('Aucun fichier trouvé<br>' . "\n", 'alert');
        }
        elseif (count($files) > 0) {
            $naming_template = $this->lib_element_properties[$id_lib_element]["lib_element_naming_template"];
            $template = $this->lib_element_properties[$id_lib_element]["lib_element_template"];

            //__debug($files,"FILES");

            foreach ($files AS $filename => $file_information) {
                if (preg_match($template, $file_information[2])) {
                    $location = substr($filename, 0, strrpos($filename, '/'));
                    //$location = substr($filename, 0, strrpos(str_replace('//', '/',$filename), '/'));
                    $source = $file_information[2];
                    $taille_initiale = $file_information[1];
                    $destination = $this->retrieve_parameters["repertoire_upload"] . uniqid("") . ".txt";
                    $source_info = $this->upload_flat_file($id_lib_element, null, $conn_id, $source, $destination, $location, $taille_initiale, $file_information);
                    if ($source_info) {
                        // 16:33 23/09/2009SCT BZ 6828 => suppression des fichiers non utilisés par l'application
                        // 10:42 22/09/2009 SCT : destruction de la référence du fichier qui vient d'être uploadé
                        unset($files[$filename]);
                        // 12:11 24/10/2008 SCT  : ajout du paramètre $location dans le cas du traitement des fichiers ZIP
                        $this->store_uploaded_file($destination, $naming_template, $source_info, $file_information, $location);
                        // 14-03-2008 SCT : Bug#6029 => Affichage d'une ligne dans le tracelog pour chaque fichier scanné [déplacement du fichier dans la boucle]
                        // mets dans la log les informations relatives à la collecte des fichiers
                        $this->log_retrieve($source_info);
                    }
                }
            }
        } else
        // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
            displayInDemon('Aucun fichier trouvé<br>' . "\n", 'alert');

        return $files;
    }

// end function get_flat_file_remote()

    /**
     * Fonction qui va collecter un lib_element en mode SFTP
     *
     * @param integer       $id_flat_file Identifiant du lib_element
     * @param SSHConnection $res_sftp Ressource SFTP valide
     * @param string        $location Path du repertoire
     * @param array         $listingFiles
     *
     */
    function get_flat_file_remote_ssh($id_lib_element, SSHConnection $res_sftp, $location, $files, $id_connection) {
        if ($files == false) {
            $file_list = $this->get_ssh_file_list($res_sftp, $location);
            $nb_file = count($file_list);
            $files = array();
            if ($nb_file % 1 == 0 AND $nb_file > 0) {
                displayInDemon($nb_file . ' élément(s) trouvé(s)<br>' . "\n");
                sleep(1); // Utile pour déterminer si le fichier est en cours de depôt sur le serveur distant

                $naming_template = $this->lib_element_properties[$id_lib_element]["lib_element_naming_template"];
                $template = $this->lib_element_properties[$id_lib_element]["lib_element_template"];

                foreach ($file_list as $file_index => $file_information) {
                    // 26/05/2011 NSE bz 22130 : On ne gère pas les noms de dossiers ne contenant que des espaces.
                    if ($file_information[0] == 1 && $this->retrieve_search_directory == 1 && strlen(trim($file_information[2])) > 0) {
                        if ($this->debug)
                            displayInDemon('on se trouve dans le cas d un répertoire<br>' . "\n");
                        $ret_files = $this->get_flat_file_remote_ssh($id_lib_element, $res_sftp, $location . '/' . $file_information[2], false, $id_connection);
                        $files = array_merge($files, $ret_files);
                        continue; // Dans le cas d'un appel récursif pour un dossier, on passe directement à l'itération suivante
                    }
                    $files[$location . '/' . $file_information[2]] = $file_information;

                    //Si le nom du fichier correspond au template
                    if (preg_match($template, $file_information[2])) {
                        unset($file_list[$file_index]);
                        unset($files[$location . '/' . $file_information[2]]);

                        $source = $file_information[2];
                        $taille_initiale = $file_information[1];
                        $destination = $this->retrieve_parameters["repertoire_upload"] . uniqid("") . ".txt";
                        $source_info = $this->upload_flat_file($id_lib_element, $res_sftp, $conn_id, $source, $destination, $location, $taille_initiale, $file_information);
                        if ($source_info) {
                            $this->store_uploaded_file($destination, $naming_template, $source_info, $file_information, $location, $id_connection);
                            $this->log_retrieve($source_info); // Log les informations relatives à la collecte des fichiers
                        }
                    }
                }
            } else {
                displayInDemon('Aucun fichier trouvé<br>' . "\n", 'alert');
            }
        } else if (count($files) > 0) {
            $naming_template = $this->lib_element_properties[$id_lib_element]["lib_element_naming_template"];
            $template = $this->lib_element_properties[$id_lib_element]["lib_element_template"];
            foreach ($files as $filename => $file_information) {
                if (preg_match($template, $file_information[2])) {
                    $location = substr($filename, 0, strrpos($filename, '/'));
                    $source = $file_information[2];
                    $taille_initiale = $file_information[1];
                    $destination = $this->retrieve_parameters["repertoire_upload"] . uniqid("") . ".txt";
                    $source_info = $this->upload_flat_file($id_lib_element, $res_sftp, $conn_id, $source, $destination, $location, $taille_initiale, $file_information);
                    if ($source_info) {
                        unset($files[$filename]);
                        $this->store_uploaded_file($destination, $naming_template, $source_info, $file_information, $location, $id_connection);
                        $this->log_retrieve($source_info);
                    }
                }
            }
        } else {
            displayInDemon('Aucun fichier trouvé<br>' . "\n", 'alert');
        }
        return $files;
    }

    /**
     * *Fonction qui va collecter un lib_element local
     *
     * AJOUT LE 12:07 10/10/2008 SCT depuis cb 3.0.4.01
     * 	08/07/2008 GHX  : modification du principe de scan, on ne parcours plus qu'une seule fois les répertoires.
     *
     * 26/02/2008 SCT : option pour la recherche récursive dans les sous-répertoires
     * 
     * @param identifiant $ du lib_element $id_lib_element
     * @param repertoire $ source $location
     * @param tableau $files => tableau contenant les fichiers déjà scannés pour la connexion en cours
     */
    function get_flat_file_local($id_lib_element, $location, $files, $id_connection) {
        if ($files == false) {
            $files = array();
            $file_list = $this->get_local_file_list($location);

            // ce sleep permet d'attendre pour voir si entre temps, les fichiers contenus dans la liste on changé de taille
            // s'ils ont changé de taille, cela signifie donc qu'ils sont en cours de téléchargement et donc qu'il ne faut pas les prendre
            $nb_file = count($file_list);
            if ($nb_file % 1 == 0 AND $nb_file > 0) {
                // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                displayInDemon($nb_file . ' élément(s) trouvé(s)<br>' . "\n", 'alert');
                sleep(1);
                // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                if ($this->debug)
                // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                    displayInDemon('j\'attends 1s<br>' . "\n");
                $naming_template = $this->lib_element_properties[$id_lib_element]["lib_element_naming_template"];
                $template = $this->lib_element_properties[$id_lib_element]["lib_element_template"];
                // 16:33 23/09/2009SCT BZ 6828 => suppression des fichiers non utilisés par l'application
                // 10:51 22/09/2009 SCT : destruction de la référence
                foreach ($file_list AS $file_index => $file_information) {
                    // 15/02/2008 SCT : si l'élément analysé en cours est un répertoire, on recharge la fonction get_flat_file_remote pour analyser le contenu
                    if ($file_information[0] == 1 && $this->retrieve_search_directory == 1) {
                        // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                        if ($this->debug)
                            displayInDemon('on se trouve dans le cas d un répertoire (' . $location . '/' . $file_information[2] . '), on explore le niveau<br>' . "\n");

                        __debug($file_information, "FILE INFOS");

                        $result_files = $this->get_flat_file_local($id_lib_element, $location . '/' . $file_information[2], false, $id_connection);
                        $files = array_merge($files, $result_files);
                        continue;
                    }

                    $files[$location . '/' . $file_information[2]] = $file_information;

                    if (preg_match($template, $file_information[2])) {
                        $source = $file_information[2];
                        $taille_initiale = $file_information[1];
                        $destination = $this->retrieve_parameters["repertoire_upload"] . uniqid("") . ".txt";
                        $source_info = $this->upload_flat_file($id_lib_element, null, $conn_id, $source, $destination, $location, $taille_initiale, $file_information);
                        if ($source_info) {
                            // 16:33 23/09/2009SCT BZ 6828 => suppression des fichiers non utilisés par l'application
                            // 10:42 22/09/2009 SCT : destruction de la référence du fichier qui vient d'être uploadé
                            unset($file_list[$file_index]);
                            unset($files[$location . '/' . $file_information[2]]);
                            // 12:11 24/10/2008 SCT  : ajout du paramètre $location dans le cas du traitement des fichiers ZIP
                            $this->store_uploaded_file($destination, $naming_template, $source_info, $file_information, $location, $id_connection);
                            // mets dans la log les informations relatives à la collecte des fichiers
                            $this->log_retrieve($source_info);
                        }
                    }
                }
            } else
            // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                displayInDemon('Aucun fichier trouvé<br>' . "\n", 'alert');
        }
        elseif (count($files) > 0) {
            $naming_template = $this->lib_element_properties[$id_lib_element]["lib_element_naming_template"];
            $template = $this->lib_element_properties[$id_lib_element]["lib_element_template"];
            foreach ($files AS $filename => $file_information) {
                if (preg_match($template, $file_information[2])) {
                    $location = substr($filename, 0, strrpos($filename, '/'));
                    $source = $file_information[2];
                    $taille_initiale = $file_information[1];
                    $destination = $this->retrieve_parameters["repertoire_upload"] . uniqid("") . ".txt";
                    $source_info = $this->upload_flat_file($id_lib_element, null, $conn_id, $source, $destination, $location, $taille_initiale, $file_information);
                    if ($source_info) {
                        // 16:33 23/09/2009SCT BZ 6828 => suppression des fichiers non utilisés par l'application
                        // 10:42 22/09/2009 SCT : destruction de la référence du fichier qui vient d'être uploadé
                        unset($files[$filename]);
                        // 12:11 24/10/2008 SCT  : ajout du paramètre $location dans le cas du traitement des fichiers ZIP
                        $this->store_uploaded_file($destination, $naming_template, $source_info, $file_information, $location, $id_connection);
                        //mets dans la log les informations relatives à la collecte des fichiers
                        $this->log_retrieve($source_info);
                    }
                }
            }
        } else
        // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
            displayInDemon('Aucun fichier trouvé<br>' . "\n", 'alert');

        return $files;
    }

// end function get_flat_file_local()

    /**
     * *fonction qui stocke la liste des flat file uploade et les informations associée (hour,day,template) dans une table de la BDD
     * 	CONVERSION dos2unix pour supprimer les caractères spéciaux comme  " ^M "
     *
     * 26/02/2008 SCT : modification du GUID
     * AJOUT LE 12:07 10/10/2008 SCT depuis cb 3.0.4.01
     * 	03/04/2008 GHX : modification du GUID [ BUG 6222 & BUG 6225]
     * 
     * 10-10-2008 SCT : ajout d'un paramètre dans l appel de la fonction pour la gestion des problemes de dates invalides presentes dans le contenu des fichiers binaires
     * 12:15 24/10/2008 SCT : modification pour le traitement des fichiers ZIP
     * 	- ajout du paramètre $location dans l'appel de la fonction 'store_uploaded_file'
     * 	- ajout du paramètre $location dans l'appel de la fonction 'parser->get_flat_file_arguments'
     * 17:26 24/10/2008 SCT	: modification de l'appel de la méthode get_flat_file_arguments => ajout du paramètre $this->retrieve_parameters["repertoire_upload_zip_extract"] car le paramètre n'est pas accessible depuis cette méthode
     *
     * @param  $file chemin et nom du fichier
     * @param  $template_name template  associé au fichier
     * @param array $file_information les informations du fichier téléchargé (1 => ?, 2 => nom_fichier, 3 => date_fichier serveur)
     * @param string $location chemin d'accès au fichier (chemin distant)
     * @global $database_connection
     */
    function store_uploaded_file($file, $template_name, $source_info, $file_information, $location, $id_connection) {
        global $database_connection;

        // parcoure la liste des fichiers uploade
        if (file_exists($file)) {
            // 10-10-2008 SCT : ajout d'un paramètre dans l appel de la fonction pour la gestion des problemes de dates invalides presentes dans le contenu des fichiers binaires
            $date_information = $this->parser->get_flat_file_arguments($file, $template_name, $source_info, $location);
            //__debug($date_information,"date information");
            // verifie les elements retournés par le fichier pour voir s'ils sont cohérents sinon le fichier n'est pas exploité
            $status = $this->check_file_date_information($date_information);

            if ($status) {
                // 12:26 24/10/2008 SCT : ajout d'un traitement externe (fonction fileTreatmentDos2Unix depuis le fichier 'parser/xxx/scripts/flat_file_upload_xxx.class.php'). On peut ainsi spécifier le traitement à effectuer sur le fichier en fonction du type de fichier. Cela évite de faire un dos2unix sur un fichier ZIP qui corrompt le fichier
                //	+ on recherche si la méthode existe
                //		- si oui, on l'exécute
                //		- si non, on passe sur un dos2unix standard
                if (method_exists($this->parser, 'fileTreatmentDos2Unix')) {
                    // on crée un tableau contenant la destination et la source des fichiers pour le déplacement
                    $this->parser->fileTreatmentDos2Unix($file, $template_name);
                } else {
                    // 30/07/2007 - JL - Conversion du fichier avec dos2unix
                    // modif 10:32 07/11/2008 GHX
                    //  BZ 8042 : [REC][T&A CB 4.0][DE][ZIP]: des espaces dans le label de la connection font planter la reprise de données ds le cas des zip
                    $command = 'dos2unix "' . $file . '"';
                    exec($command);
                }
                // Modif 11:50 03/04/2008 GHX
                // correction du BUG 6222 + BUG 6225
                if ($this->retrieve_search_directory == 0) {
                    $unique_identifier = $this->parser->get_unique_identifier($file, $template_name, $source_info["source_name"]);
                    $source_info_name = $source_info["source_name"];
                } else {
                    $unique_identifier = $this->connexion_name . $this->source_transformee;
                    //$source_info_name = $source_info["source_name"];
                    $source_info_name = $this->connexion_name . $this->source_transformee;
                }
                $source_info_heure_upload = $source_info["heure_upload"];

                // 30/11/2009 BBX : on échappe les "\" dans le GUID. BZ 9376
                $unique_identifier = str_replace('\\', '\\\\', $unique_identifier);



                // correction suite au merge 5.0.5.03 -> 5.1.1.03
                // 06/07/2010 BBX
                // On commence par supprimer une collecte précédente sur les mêmes fichiers
                // BZ 15887
                $query = "DELETE FROM sys_flat_file_uploaded_list
                    WHERE flat_file_uniqid = '$unique_identifier'
                    AND id_connection = $this->id_connection
                    AND hour = $date_information[0]
                    AND day = $date_information[1]";
                $this->database->execute($query);
                __debug($query, "NETTOYAGE DE LA TABLE sys_flat_file_uploaded_list AVANT INSERTION DU FICHIER $unique_identifier");

                // 15:42 24/10/2008 SCT ajout de la colonne de blocage de l'analyse du fichier ZIP
                // 01/08/2011 BBX
                // On remplace les quotes par des doubles $ afin que Postrgesql n'interprète pas les caractères échapés
                // BZ 23130
                //On teste ici si l'heure à traiter correspond bien au nom du fichier traité.
                $this->log_collect_treatment($date_information[0], $template_name, $source_info_name, $unique_identifier, $source_info_heure_upload, $id_connection);

                $query = "
					INSERT INTO
						sys_flat_file_uploaded_list
							(id_connection, hour, day, flat_file_template, flat_file_location, uploaded_flat_file_name, uploaded_flat_file_time, flat_file_uniqid, capture_duration, modification_date) 
					VALUES 
							($this->id_connection, $date_information[0], $date_information[1], '$template_name', \$\${$file}\$\$, \$\${$source_info_name}\$\$, '$source_info_heure_upload', '$unique_identifier', $date_information[4], '$file_information[3]')";
                __debug($query, "INSERTION DU FICHIER $unique_identifier");

                $result = $this->database->execute($query);
                $dev = getmicrotime();
                //__debug($query,"QUERY");
                $fin = getmicrotime();
                //__debug("temps de traitement : ".round($fin-$deb,3)." sec");
                // 15/07/2011 MMT bz 22455 remplacement de pg_errormessage()
                if ($this->database->getLastError() != '')
                // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                    displayInDemon($this->database->getLastError() . ' ' . $query . '<br>' . "\n", 'alert');
                else
                    $this->flat_file_treated++;

                // 15/11/2011 BBX
                // BZ 24215 : on n'archive pas les fichiers zip
                $extension = pathinfo($this->retrieve_parameters["repertoire_upload_archive"] . $source_info_name, PATHINFO_EXTENSION);
                if (strtolower($extension) == 'zip') {
                    if ($this->debug)
                        displayInDemon($this->retrieve_parameters["repertoire_upload_archive"] . $source_info_name . " est un fichier ZIP, on ne l'archive pas");
                }

                // copie le fichier dans le repertoire d'archive et bzip2 le fichier
                elseif (copy($file, $this->retrieve_parameters["repertoire_upload_archive"] . $source_info_name)) {
                    $file_bz2 = $this->retrieve_parameters["repertoire_upload_archive"] . $source_info_name . ".bz2";
                    // si on rejoue les même fichiers, il faut au préalable supprimer le .bz2 pour qu'il soit recréé.
                    if (file_exists($file_bz2))
                        unlink($file_bz2);

                    // modif 10:32 07/11/2008 GHX
                    //  BZ 8042 : [REC][T&A CB 4.0][DE][ZIP]: des espaces dans le label de la connection font planter la reprise de données ds le cas des zip
                    $command = '/usr/bin/bzip2 "' . $this->retrieve_parameters["repertoire_upload_archive"] . $source_info_name . '"';
                    exec($command);
                    // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                    if ($this->debug)
                        displayInDemon('Bzip et Archivage du fichier ' . $source_info_name . '<br>' . "\n");
                }
            }
            else {
                // ICI, le fichier de référence est invalide
                if (method_exists($this->parser, 'treatmentFileInvalid')) {
                    // 15:08 10/12/2009 SCT : BZ 11513 => fichier avec seulement header intégré
                    //	* ajout du paramètre "$file"
                    $message = $this->parser->treatmentFileInvalid($template_name, $source_info, $file);
                    // 14:58 30/07/2010 SCT : BZ 17136 => évolution de la gestion des messages sur rejet des fichiers de données
                    //      + pour les parsers n'utilisant qu'un simple message => le message est directement copié dans le log
                    //      + pour les parsers utilisant un tableau => le message est construit à partir du tableau
                    $messageLevel = "Warning";
                    $messageInfo = $message;
                    $messageDemon = '';
                    if (is_array($message) && isset($message['messageLevel']))
                        $messageLevel = $message['messageLevel'];
                    if (is_array($message) && isset($message['messageInfo']))
                        $messageInfo = $message['messageInfo'];
                    if (is_array($message) && isset($message['messageDemon']))
                        $messageDemon = $message['messageDemon'];
                    // possibilité de by-passé les informations de la méthode "treatmentFileInvalid" par celles de la fonction "get_flat_file_arguments"
                    if (isset($date_information['messageLevelByPassTreatmentFileInvalid']))
                        $messageLevel = $date_information['messageLevelByPassTreatmentFileInvalid'];
                    if (isset($date_information['messageInfoByPassTreatmentFileInvalid']))
                        $messageInfo = $date_information['messageInfoByPassTreatmentFileInvalid'];
                    if (isset($date_information['messageDemonByPassTreatmentFileInvalid']))
                        $messageDemon = $date_information['messageDemonByPassTreatmentFileInvalid'];
                    sys_log_ast($messageLevel, $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $messageInfo, "support_1", "");
                    if (trim($messageDemon) != '')
                        displayInDemon($messageDemon . '<br>' . "\n", 'alert');
                }
                else {
                    // ICI, la date de référence du fichier collecté n'est pas valide
                    // 14:58 30/07/2010 SCT : BZ 17136 => évolution de la gestion des messages sur rejet des fichiers de données
                    $messageLevel = 'Warning';
                    $messageInfo = __T('A_FLAT_FILE_UPLOAD_ALARM_BAD_FILE_DATE_FORMAT', $source_info["source_name"]);
                    $messageDemon = 'The information located in the file is not correct';
                    if (isset($date_information['messageLevel']))
                        $messageLevel = $date_information['messageLevel'];
                    if (isset($date_information['messageInfo']))
                        $messageInfo = $date_information['messageInfo'];
                    if (isset($date_information['messageDemon']))
                        $messageDemon = $date_information['messageDemon'];
                    sys_log_ast($messageLevel, $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $messageInfo, "support_1", "");
                    // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                    displayInDemon($messageDemon . '<br>' . "\n", 'alert');
                    // 08/03/2010 BBX : si le fichier est incorrect, on doit le supprimer. BZ 14341
                    @unlink($file);
                }
                // 16:22 20/02/2009 SCT #8881 : dans le cas où l'on traite un fichier de référence, il faut le supprimer de la liste des fichiers de référence afin de ne pas collecter son groupe
                $lExtensionReference = str_replace('*', '', $template_name);
                $lExtensionReference = str_replace('$', '', $lExtensionReference);
                // 14:13 15/12/2009 SCT : BZ 13466, 13467 => template sensible à la casse
                //	+ passage en mode stripos
                $temp_fichier_dispo_court = $location . '/' . substr($file_information[2], 0, stripos($file_information[2], $lExtensionReference));
                if (isset($this->tableauFichierRefCollecte[$template_name]) && in_array($temp_fichier_dispo_court, $this->tableauFichierRefCollecte[$template_name])) {
                    // on le dépile du tableau
                    foreach ($this->tableauFichierRefCollecte[$template_name] AS $cle => $valeur) {
                        if ($temp_fichier_dispo_court == $valeur)
                            unset($this->tableauFichierRefCollecte[$template_name][$cle]);
                    }
                    // on l'ajoute dans le tableau des fichiers de référence à ignorer
                    $this->tableauFichierRefIgnore[$template_name][] = $valeur;
                }
            }
        } else
        // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
            displayInDemon('The file ' . $file . ' does not exist<br>' . "_n", 'alert');
    }

// end function store_uploaded_file()

    /**
     * 
     * @param type $date_information
     * @param type $template_name
     * @param type $file_name
     * @param type $unique_identifier
     * @param type $source_info_heure_upload
     * @param type $id_connection
     */
    function log_collect_treatment($date_information, $template_name, $file_name, $unique_identifier, $source_info_heure_upload, $id_connection) {
        $this->flat_file_info->checkTemplateName($id_connection, $template_name, $file_name);
        $template_name = str_replace("*", "", $template_name);
        $template_name = str_replace("$", "", $template_name);

        switch ($template_name) {
            case "R04.txt": // Traitement des fichiers IU
                // patch for IN3629
                // $this->flat_file_info->log_collect_treatment_for_IU($date_information, $template_name, $file_name, $unique_identifier, $source_info_heure_upload, $id_connection);
                break;
            case ".COR": // Traitement des fichiers CORE CS
                $this->flat_file_info->log_collect_treatment_for_CoreCS($date_information, $template_name, $file_name, $unique_identifier, $source_info_heure_upload, $id_connection);
                break;
            case "99_stat_TAGN_PSM_REF.txt": // Traitement des fichiers CorePS
            case ".nps":
            case ".cps":
                $this->flat_file_info->log_collect_treatment_for_CorePS($date_information, $template_name, $file_name, $unique_identifier, $source_info_heure_upload, $id_connection);
                break;
            case "R03.txt": // Traitement des fichiers GPRS
                $this->flat_file_info->log_collect_treatment_for_GPRS($date_information, $template_name, $file_name, $unique_identifier, $source_info_heure_upload, $id_connection);
                break;
            case "R02.txt": // Traitement des fichiers GSM
                $this->flat_file_info->log_collect_treatment_for_GSM($date_information, $template_name, $file_name, $unique_identifier, $source_info_heure_upload, $id_connection);
                break;
            case "_ALL.ran": // Traitement des fichiers RRAN
                $this->flat_file_info->log_collect_treatment_for_RRAN($date_information, $template_name, $file_name, $unique_identifier, $source_info_heure_upload, $id_connection);
                break;
        }
    }

    
    /**
     * 
     */
    function flat_file_recovery() {
        // 11/10/2011 ACS Mantis 615: DE Data reprocessing GUI
        // 17:09 16/10/2009 SCT : BZ 12102 => problème d'analyse des fichiers ZIP => insensibilité à la casse
        if ($this->compute_mode == 'hourly') {
            $query = "
				SELECT 
					id_connection,
					hour,
					day,
					flat_file_template,
					flat_file_location,
					uploaded_flat_file_name,
					uploaded_flat_file_time,
					flat_file_uniqid,
					capture_duration, 
					modification_date,
					reprocess
				FROM 
					sys_flat_file_uploaded_list_archive 
				WHERE 
					(
						(hour IN 
							(
							SELECT DISTINCT 
								hour 
							FROM 
								sys_flat_file_uploaded_list
							)
						)
						OR
						reprocess = 1
					) 
					AND flat_file_template IN 
						(
							SELECT DISTINCT 
								flat_file_naming_template 
							FROM 
								sys_definition_flat_file_lib 
							WHERE 
								on_off=1
						) 
					AND flat_file_uniqid NOT IN 
						(
							SELECT 
								flat_file_uniqid 
							FROM 
								sys_flat_file_uploaded_list
						)
					AND flat_file_template NOT ILIKE '%zip'";
        } else {
            $query = "
				SELECT 
					id_connection,
					hour,
					day,
					flat_file_template,
					flat_file_location,
					uploaded_flat_file_name,
					uploaded_flat_file_time,
					flat_file_uniqid,
					capture_duration, 
					modification_date,
					reprocess
				FROM 
					sys_flat_file_uploaded_list_archive 
				WHERE 
					(
						(day IN 
							(
								SELECT DISTINCT 
									day 
								FROM 
									sys_flat_file_uploaded_list
							)
						)
						OR
						reprocess = 1
					) 
					AND flat_file_template IN 
						(
							SELECT DISTINCT 
								flat_file_naming_template 
							FROM 
								sys_definition_flat_file_lib 
							WHERE 
								on_off = 1
						) 
					AND flat_file_uniqid NOT IN 
						(
							SELECT 
								flat_file_uniqid 
							FROM 
								sys_flat_file_uploaded_list
						)
					AND flat_file_template NOT ILIKE '%zip'";
        }
        __debug($query, "QUERY");
        $result = $this->database->execute($query);
        displayInDemon("flat_file_recovery - query :" . $query);

        $nombre_fichiers = $this->database->getNumRows();

        displayInDemon("flat_file_recovery - nombre_fichiers :" . $nombre_fichiers);

        // reset the flag of reprocess for every archive files
        // 21/09/2012 ACS BZ 29071 Collect performances decrease after upgrading from 5.0 to 5.1
        $query = "UPDATE sys_flat_file_uploaded_list_archive SET reprocess = 0 WHERE reprocess = 1";
        $this->database->execute($query);

        $nombre_fichiers_reprocessed = 0;

        while ($values = $this->database->getQueryResults($result, 1)) {
            $id_connection = $values["id_connection"];
            $hour = $values["hour"];
            $day = $values["day"];
            $flat_file_template = $values["flat_file_template"];
            $file_location = $values["flat_file_location"];
            $flat_file_uniqid = $values["flat_file_uniqid"];
            $file_destination = $this->retrieve_parameters["repertoire_upload"] . uniqid("") . ".txt";
            $flat_file_name = $values["uploaded_flat_file_name"];
            $flat_file_capture_duration = $values["capture_duration"];
            $modification_date = $values['modification_date'];

            if ($values['reprocess'] == 1) {
                $nombre_fichiers_reprocessed ++;
            }
            // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
            if ($this->debug)
                displayInDemon($flat_file_name . '<br>' . "\n");
            $heure_upload = $values["uploaded_flat_file_time"];
            // On vérifie si le fichier existe
            if (file_exists($file_location)) {
                // copie le fichier bz2 dans le repertoire upload
                copy($file_location, $file_destination . ".bz2");

                // dezippe le fichier
                // modif 10:32 07/11/2008 GHX
                //  BZ 8042 : [REC][T&A CB 4.0][DE][ZIP]: des espaces dans le label de la connection font planter la reprise de données ds le cas des zip
                $command = '/usr/bin/bunzip2 "' . $file_destination . '.bz2"';
                exec($command);
                // insère le fichier dans la liste des fichier telecharges
                // maj 28/04/2010 - Correction du BZ 15247 : Reprise de données défaillante qd un GUID contient des \
                //                On remplace les \ par \\ afin que la requête SQL s'exécute convenablement
                // 01/08/2011 BBX
                // On remplace les quotes par des doubles $ afin que Postrgesql n'interprète pas les caractères échapés
                // BZ 23130
                $query = "
				INSERT INTO 
					sys_flat_file_uploaded_list 
						(id_connection, hour, day, flat_file_template, flat_file_location, uploaded_flat_file_name, uploaded_flat_file_time, flat_file_uniqid, capture_duration, modification_date) 
				VALUES 
						($id_connection, $hour, $day, '$flat_file_template', \$\${$file_destination}\$\$, \$\${$flat_file_name}\$\$, '$heure_upload', E'" . str_replace("\\", "\\\\", $flat_file_uniqid) . "', '$flat_file_capture_duration', '$modification_date')";
                $query = ereg_replace("''", "NULL", $query);
                $result2 = $this->database->execute($query);

                displayInDemon("<b>flat_file_recovery request:</b>" . $query);
                $lErreur = $this->database->getLastError();
                if ($lErreur != '')
                // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                    displayInDemon($lErreur . ' = ' . $query . '<br>' . "\n", 'alert');
            }
            else {

                $message = "Fichier : " . $file_location . "not found";

                // End message
                sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
            }
        }

        if ($nombre_fichiers > 0) {
            // 11/10/2011 ACS Mantis 615: DE Data reprocessing GUI
            // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
            displayInDemon('Re-intégration de ' . $nombre_fichiers . ' fichiers sources pour compléter les données dont ' . $nombre_fichiers_reprocessed . ' fichiers pour le retraitement de données<br />' . "\n");
        } else {
            // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
            displayInDemon('Aucun fichier source à réintégrer<br>' . "\n");
        }
    }

// end function flat_file_recovery()

    /*
     * function qui vérifie la cohérence des informations de date sur le fichier
     * Il se peut que le fichier ne renvoie aucune date (cas Astellia) auquel cas la vérification renvoie true
     * @param : $date_information : array qui contient les informations de date sur le fichier
     * @return : true si la verification est OK, false sinon
     */

    function check_file_date_information($date_information) {
        // Dans le cas où le fichier est un fichier associé à un autre (cas des fichiers d'Astellia), on a positionne les informations à NULL et donc on considere le check comme OK
        if ($date_information[4] == 'NULL' && $date_information[3] == 'NULL' && $date_information[2] == 'NULL' && $date_information[1] == 'NULL' && $date_information[0] == 'NULL')
            return true;

        /* Vérifie que 
         * - la duree ($date_information[4]) est > 0
         * - l'heure ($date_information[0], exemple 2014151300) est bien codee sur 8 caracteres et
         *   JLG - bz 41743 - que le mois est valide (1 <= mois <= 12)
         * - l'année ($date_information[3]) est > à l'année 2000 (valeur abitraire pour éliminer les heures qui correspondent à une heure mais à des valuers incohérente) et que l'année est inférieure ou égale à l'année courante
         */
        if ($date_information[4] > 0 &&
                strlen($date_information[0]) == 10 && substr($date_information[0], 4, 2) >= 1 && substr($date_information[0], 4, 2) <= 12 &&
                $date_information[3] > 2000 && $date_information[3] <= date("Y"))
            return true;
        else
            return false;
    }

// end function check_file_date_information()
    // 11/07/2007 : Jérémy
    // Fonction qui va vérifier si les fichiers de toutes les sondes (connexions) ont bien été récupérés
    //	1/ Affichage dans le tracelog :
    //		- du jeu de fichiers de la période précédente pour chaque sonde s'il n'a pas été envoyé par celles ci
    //		- ou si des jeux de fichiers pour des heures antérieurs sont récupérés, on affiche si certains fichiers sont manquants
    //	2/ Génération d'alarmes pour la date T (T = date&heure actuelle  -  date de temporisation) si les fichiers n'ont pas été livrés
    //
	//	modif 14:31 23/11/2007 Gwen : modification de la gestion des alarmes systèmes
    //	modif 17:52 06/12/2007 Gwen : ajout d'une condition pour savoir si on doit lancer la gestion des alarmes systèmes
    //  19/03/2010 MPR
    // 	- Correction du BZ14346 : [Alarme système] Optimisation du calcul de la temporisation
    function alarm_result_absence() {
        __debug(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>DEBUT DE LA TEMPO<<<<<<<<<<<<<<<<<<<<<<<");
        // modif 17:53 06/12/2007
        // ajout d'une condition pour savoir si on doit lancer la gestion des alarmes systèmes
        if (get_sys_global_parameters('alarm_systems_activation') == 0) {
            // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
            displayInDemon("\n" . '<br />La variable alarm_systems_activation est égale à 0. La gestion des alarmes systèmes est inhibé.<br>' . "\n");
            return;
        }

        $depart = time();

        ////Heure courante
        $today_mkdate = mktime(date("H"), 0, 0, date("m"), date("d"), date("Y"));


        // modif 14:04 22/11/2007 Gwen
        // Modification de la gestion des alarmes systèmes chaque 
        // maintenant chaque type de fichier peut avoir ça propre temporisation
        // 15/02/2008 - Modif. benoit : ajout de la colonne 'exclusion' dans la requete
        // modif 08:39 21/10/2008 GHX(DE)
        // Evolution des alarmes systemes : définition des alarmes systemes par connexion.
        // maj 17/03/2010 - MPR : Correction du BZ14348 - Problème de performance des Alarmes Système
        // 		- On ne boucle plus sur tous les fichiers manquants 
        $query = "
				SELECT DISTINCT period_type 
				FROM sys_definition_flat_file_lib 
				WHERE on_off = 1
				";
        $result = $this->database->execute($query);


        if ($this->database->getNumRows() > 0) {
            while ($row = $this->database->getQueryResults($result, 1)) {
                $period_type = strtolower($row['period_type']);
                if ($period_type == 'hour') {
                    $today = date("YmdH", $today_mkdate);
                } else {
                    $today = date("Ymd", $today_mkdate);
                }

                $go_tempo[$period_type] = $this->verify_last_collect_date($period_type, $today);
            }


            if ($go_tempo['hour'] || $go_tempo['day']) {
                $query = "
					SELECT 
						flat_file_name, 
						flat_file_naming_template, 
						alarm_missing_file_temporization, 
						period_type, 
						exclusion,
						id_connection,
						connection_name
					FROM 
						sys_definition_flat_file_lib sdffl,  
						sys_definition_flat_file_per_connection sdffpc, 
						sys_definition_connection sdc
					WHERE
						sdffl.id_flat_file = sdffpc_id_flat_file 
						AND sdc.id_connection = sdffpc_id_connection
						AND sdffl.on_off = 1 
						AND sdc.on_off = 1   
					ORDER BY 
						id_connection ASC,
						flat_file_name ASC
					";
                //__debug($query,"QUERY");
                $fin = getmicrotime();
                $result = $this->database->execute($query);
                $fin = getmicrotime();

                //__debug("temps de traitement : ".round($fin-$deb,3)." sec");
                if ($this->database->getNumRows() > 0) {
                    // 15/02/2008 - Modif. benoit : ajout de '$exclusion' dans la liste issue des lignes de resultats
                    while ($row = $this->database->getQueryResults($result, 1)) {
                        $ff_name = $row['flat_file_name'];
                        $ff_template = $row['flat_file_naming_template'];
                        $temporisation = $row['alarm_missing_file_temporization'];
                        // 07/04/2010 BBX : application d'un strtolower sur $row['period_type']. BZ 14348
                        $period_type = strtolower($row['period_type']);
                        $exclusion = $row['exclusion'];
                        $id_connection = $row['id_connection'];
                        $connection_name = $row['connection_name'];
                        if ($go_tempo[$period_type]) {
                            if (strtolower($period_type) == 'hour') {
                                //On incrémente la temporisation de 1heure puisque l'on prend l'heure précédente comme point de départ.
                                $temporisation += 1;
                                //Création du timestamp ($tempo_mkdate) qui sera utilisé pour réaliser les calculs
                                $tempo_mkdate = mktime(date("H") - $temporisation, 0, 0, date("m"), date("d"), date("Y"));
                                $period_tempo_value = date("YmdH", $tempo_mkdate); //Récupération de la date de Tempo en fonction du Compute_mode (houly ou daily)
                                ////Période précédente (PERIODE PRECEDENT LA PERIODE COURANTE (heure ou jour précédent) )
                                $previous_periode = date("YmdH", mktime(date("H") - 1, 0, 0, date("m"), date("d"), date("Y")));
                                // 15/02/2008 - Modif. benoit : on verifie ici que l'heure de tempo ne fait pas partie des exclusions
                                $tempo_hour = date("H", $tempo_mkdate);

                                if ($exclusion != "") {
                                    // On recupere la liste des heures exclues en décomposant la chaine de valeurs
                                    $exclusions_values = $this->decomposeExclusionValues(explode(";", $exclusion));
                                    // Si l'heure de la tempo fait partie des valeurs exclues, on passe au fichier suivant
                                    if (in_array($tempo_hour, $exclusions_values))
                                        continue;
                                }
                            }
                            else {
                                //On incrémente la temporisation de 24heures puisque l'on prend le jour précédent comme point de départ pour le calcul
                                $temporisation += 24;
                                //Création du timestamp ($tempo_mkdate) qui sera utilisé pour réaliser les calculs
                                $tempo_mkdate = mktime(date("H") - $temporisation, 0, 0, date("m"), date("d"), date("Y"));
                                $period_tempo_value = date("Ymd", $tempo_mkdate);
                                ////Période précédente (PERIODE PRECEDENT LA PERIODE COURANTE (heure ou jour précédent) )
                                $previous_periode = date("Ymd", mktime(date("H"), 0, 0, date("m"), date("d") - 1, date("Y")));
                            }

                            /*
                              >>>>>>>>>>>>>>>>>>>>>>>>>>>>>> DEBUT Suppression des appels aux fonctions suivantes : >>>>>>>>>
                              - check_last_period()
                              - check_last_files_collected()
                              Celles-ci sont obselètes (elles initialisait les variables $this->absent_files['tracelog']) qui n'est plus utilisé nulle part !!!

                              I - Récupération des données (fichiers absents)
                              1) TRACELOG
                              Vérification de la présence de TOUS les fichiers pour la période (heure ou jour) passée pour chaque sonde

                              $this->check_last_period($previous_periode, $period_type, $ff_template, $id_connection, $connection_name);

                              Vérification de la présence de TOUS les fichiers pour chaque jeux de fichiers
                              qui ont été collectés lors du dernier retrieve effectué
                              On fournit les heures et jours précédents et ceux de la tempo afin de ne pas les traiter,
                              sinon on pourrait se retrouver avec des doublons

                              $this->check_last_files_collected($previous_periode,$period_tempo_value,$period_type, $ff_template, $id_connection, $connection_name);
                              <<<<<<<<<<<<<<<<<<<<<<<<<<<<< Suppression des fonctions suivantes <<<<
                             */

                            /*
                              2) TEMPORISATION (pour les alarmes)
                              On cherche si le dernier retrieve n'a pas eu lieu dans l'heure en cour ou la journée en cours en fonction du compute mode
                              S'il y en a eu un, on ne lancera pas les test sur la tempo

                              On va aller vérifier si les retrieves on bien été lancés pour les dernières heures
                              Si ce n'est pas le cas, on va chercher les heures et/ou les jours qui n'ont pas été soumis a un retrieve
                              TRES IMPORTANT : On place la tempo pour l'heure courante dans le tableau, donc même s'il n'y a pas de RETRIEVE manquant
                              (non effectué dans le passé) on aura un tout de même un tableau (avec une seul date de tempo) à traiter.
                             */
                            $tab_tempo = $this->check_missing_tempo($period_type, $temporisation, $period_tempo_value);
                            //Vérification de la présence de TOUS les fichiers pour chaque sonde à l'heure de la temporisation
                            $this->check_tempo_period($tab_tempo, $period_type, $ff_template, $id_connection);
                        }
                    } // while
                } // if
            }
        }

        // II- Préparation, génération et envoi des informations selon les différentes méthodes
        // 1)  Affichage du contenu du tableau
        // $this->show_table_contain();

        if ($go_tempo["hour"] || $go_tempo["day"]) {
            // 2)  Génération des messages dans le Tracelog
            $deb = getmicrotime();
            $this->generate_tracelog_messages($period_type);
            $fin = getmicrotime();
            //__debug("temps de traitement generate_tracelog_messages : ".round($fin-$deb,3)." sec");
            // 3) Génération et envoi de trap SNMP (si service activé) et de mail, SI ET SEULEMENT SI   "verify_last_retrieve_date()"  retourne TRUE
            // a)  Génération et envoi d'une trap SNMP d'alarm (si le mode SNMP est activé)
            if ($this->snmp_activation == 1)
                $this->generate_SNMP_trap();

            // b)  Génération et envoi d'un mail d'alarme
            $this->generate_mail();
        }

        $arrive = time();
        $diff = $arrive - $depart;
        __debug("Durée d'éxécution du script d'alarme : $diff secondes");
    }

// end function alarm_result_absence()
    //Fonction qui vérifie s'il est suceptible de manquer des date qui n'ont pas été vérifier pour la tempo
    //On va débord chercher dans la table sys_process_encours les heures manquante depuis le dernier retrieve
    //On a juste a récupérer les deux dernières lignes créée dans la table :
    //		- la dernière étant celle créer pour le retrieve en cours de lancement,
    //		- l'avant dernière étant la précédente qui a été lancée. (s'il n'y a pas de précédente, c'est que la table a été réinitialisée,
    //	dans ce cas on peut afficher un message d'erreur, mais on ne pourra pas récupérer d'heure manquante puisqu'il nous manque une limite dans le temps )
    function check_missing_tempo($period_type, $tempo, $period_tempo_value) {
        //On intégre dans le tableau la période de tempo pour l'heure en cours de traitement
        $tab_missing_tempo[] = $period_tempo_value;

        //Récupération des dates des deux dernière retrieve qui ont été effectuées
        // 11:26 21/09/2009 GHX
        // Correction du BZ 11579 [REC][T&A Gb 5.0][TC#4628][Alarme système] : boucle infinie
        // Ajout d'une condition pour éviter d'avoir la boucle infini
        // 15:34 30/09/2009 GHX
        // Modification de la condition sur la date "<=" au lieu de "="
        // 01/07/2011 BBX
        // Correction du cast
        // BZ 22872
        $query = "
			SELECT 
				DISTINCT date 
			FROM 
				sys_process_encours
			WHERE 
				process = '10'
				AND date <= " . date('YmdHi') . "
			ORDER BY 
				date DESC 
			LIMIT 2";

        $deb = getmicrotime();
        $result = $this->database->execute($query);
        $fin = getmicrotime();
        //__debug("temps de traitement : ".round($fin-$deb,3)." sec");
        //S'il y a 2 résultats, alors on éxécute la boucle sinon, renvoi le tableau avec seulement la tempo correspondant à la période actuelle
        if ($this->database->getNumRows() == 2) {
            $row_current = $this->database->getQueryResults($result, 1);
            $row_last = $this->database->getQueryResults($result, 1);

            //Paramétrage des variables pour les deux compute mode : DAILY et HOURLY
            if ($period_type == "hour") {
                $current_period = substr($row_current['date'], 0, 10);
                $last_period = substr($row_last['date'], 0, 10);
                $date_format = "YmdH";
            } else {
                $current_period = substr($row_current['date'], 0, 8);
                $last_period = substr($row_last['date'], 0, 8);
                $date_format = "Ymd"; //Utile pour la fonction mkdate
            }

            //Si les deux dates correspondent à la même heure (en mode HOURLY) et au même jour (en mode DAILY) alors on renvoi le tableau avec l'unique valeur nécessaire
            // maj 27/05/2008 Benjamin : modification de la condition, == devient <= afin d'éviter de boucler à l'infini pour le cas rare où last_period > current_period. BZ6678
            if ($current_period <= $last_period)
            //Mème heure OU même jour, donc pas d'autre date à traiter pour la tempo, il ne manquent pas d'heure, ou elles ont déjà été traitées
                return $tab_missing_tempo;
            else {
                //Si les dates sont différentes, c'est qu'il y a des heures qui n'ont pas été traiter pour la tempo, alors on va les lister
                //On va décrémenter la date courante, et ajouter les date de tempo correspondante dans un tableau
                $missing_period = null;
                // le compteur $i permet de remonter le temps, c'est lui qui mémorise l'écartement entre la date courante et la date à traiter
                $i = 1;
                while ($last_period != date($date_format, mktime(date('H') - $i, 0, 0, date('m'), date('d'), date('Y')))) {
                    $missing_period = date($date_format, mktime(date('H') - $i, 0, 0, date('m'), date('d'), date('Y')));
                    $this_tempo = $i + $tempo;
                    $missing_tempo_period = date($date_format, mktime(date('H') - $this_tempo, 0, 0, date('m'), date('d'), date('Y')));
                    // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                    displayInDemon("\n" . '<br/>Periode manquante : ' . $missing_period);
                    displayInDemon('&nbsp; &nbsp; &nbsp; &nbsp;> TEMPO manquante : ' . $missing_tempo_period);
                    $tab_missing_tempo[] = $missing_tempo_period;
                    $i++;
                }
                //On dédoublonne le tableau, au cas ou on serait en compute mode 'daily' et que l'on aurait récupéré plusieurs heures d'une même journée
                $tab_missing_tempo = array_unique($tab_missing_tempo);

                return $tab_missing_tempo;
            }
        } else
            return $tab_missing_tempo;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// EXECUTION SOUS CONDITIONS POUR LA TEMPO :
    // Si la date et l'heure du dernier RETRIEVE correspondent à la date et l'heure courante, la tempo ne sera pas vérifiée car cela signifie que le retrieve a déjà été lancé dans l'heure en cours
    // L'intérêt est de ne pas envoyer 3 ou 4 fois des traps SNMP et des mails identiques dans une même heure
    // De toute manière la première alarme contient le max de fichiers qui peuvent être manquants pour une heure car il ne peut que arriver de nouveaux fichiers, ils ne peuvent pas disparaitre...
    /**
     * @deprecated N'est plus utilisé dans le CB
     * @param unknown $period_type
     * @param unknown $today
     * @return boolean
     */
    function verify_last_retrieve_date($period_type, $today) {
        // Recherche d'un RETRIEVE qui aurait pu être lancé dans la période courante (heure ou journée)
        // On exclu le RETRIEVE en cours d'exécution
        // Correction du BZ
        // 09/06/2011 BBX -PARTITIONING-
        // Correction des casts
        $query = "
			SELECT
				date 
			FROM 
				sys_process_encours
			WHERE 
				process = '10' 
				AND date::text LIKE ('" . $today . "%')
				AND done = 1
			ORDER BY 
				date DESC 
			LIMIT 1
			";
        $result5 = $this->database->execute($query);
        //$go_tempo est un flag qui permet de savoir si l'on peut exécuter ou pas les opération concernant la TEMPO
        if ($this->database->getNumRows() > 0) {
            $go_tempo = false;
            $row = $this->database->getQueryResults($result5, 1);
            $last_retrieve = $row['date'];
            if ($period_type == 'hour')
                $last_retrieve_date = ' à ' . substr($last_retrieve, 8, 2) . 'h' . substr($last_retrieve, 10, 2);
            else
                $last_retrieve_date = 'le ' . substr($last_retrieve, 6, 2) . '-' . substr($last_retrieve, 4, 2) . '-' . substr($last_retrieve, 0, 4) . ' à ' . substr($last_retrieve, 8, 2) . 'h' . substr($last_retrieve, 10, 2);
            // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
            displayInDemon("\n" . '<br>La TEMPO ne sera pas testée au cours de ce RETRIEVE. Un Retrieve à déjà été lancé ' . $last_retrieve_date . '<br>' . "\n");
        } else
            $go_tempo = true;

        return $go_tempo;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// EXECUTION SOUS CONDITIONS POUR LA TEMPO :
    // Si la date et l'heure du dernier COLLECT correspondent à la date et l'heure courante, la tempo ne sera pas vérifiée car cela signifie que le retrieve a déjà été lancé dans l'heure en cours
    // L'intérêt est de ne pas envoyer 3 ou 4 fois des traps SNMP et des mails identiques dans une même heure
    // De toute manière la première alarme contient le max de fichiers qui peuvent être manquants pour une heure car il ne peut que arriver de nouveaux fichiers, ils ne peuvent pas disparaitre...
    function verify_last_collect_date($period_type, $today) {
        // 13/12/2012 GFS BZ#30526
        // [SUP][AVP NA]: No system alert in trace log when pm files is missing
        $query = "
			SELECT
				date
			FROM
				sys_process_encours
			WHERE
				process = '14'
				AND date::text LIKE ('" . $today . "%')
				AND done = 1
			ORDER BY
				date DESC
			LIMIT 1
			";
        $result5 = $this->database->execute($query);
        //$go_tempo est un flag qui permet de savoir si l'on peut exécuter ou pas les opération concernant la TEMPO
        if ($this->database->getNumRows() > 0) {
            $go_tempo = false;
            $row = $this->database->getQueryResults($result5, 1);
            $last_retrieve = $row['date'];
            if ($period_type == 'hour')
                $last_retrieve_date = ' à ' . substr($last_retrieve, 8, 2) . 'h' . substr($last_retrieve, 10, 2);
            else
                $last_retrieve_date = 'le ' . substr($last_retrieve, 6, 2) . '-' . substr($last_retrieve, 4, 2) . '-' . substr($last_retrieve, 0, 4) . ' à ' . substr($last_retrieve, 8, 2) . 'h' . substr($last_retrieve, 10, 2);
            // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
            displayInDemon("\n" . '<br>La TEMPO ne sera pas testée au cours de ce COLLECT. Un Collect à déjà été lancé ' . $last_retrieve_date . '<br>' . "\n");
        } else
            $go_tempo = true;

        return $go_tempo;
    }

    // 11/07/2007 : Jérémy
    // Fonction qui vérifie si les fichiers de la dernière période passée ont bien tous été récupérés
    //	exemple : S'il est 14H50, on va alors vérifier que les fichiers de chaque sonde activées (ON) ont TOUS bien été récupérés pour 13H
    //
	//	modif 14:35 23/11/2007 Gwen ajout d'un paramètre : $flat_file_template
    //	09:05 21/10/2008 GHX : modification pour la DE sur les alarmes systemes
    //
	function check_last_period($previous_periode, $period_type, $flat_file_template, $id_connection) {
        //Appel de la fonction php "get_missing_files_for_one_period" qui va retourner les fichier manquant pour la periode donnée en paramètre
        $result1 = $this->get_missing_files_for_one_period($previous_periode, $period_type, $flat_file_template, $id_connection);

        //Traitement des données récupérées pour les placer dans une variable de classe
        while ($row = $this->database->getQueryResults($result1, 1)) {
            $sonde = $row['connection_name'];
            $this->absent_files['tracelog'][$previous_periode][$sonde][] = $row['flat_file_name'];
        }

        return $result1;
    }

    // 11/07/2007 : Jérémy
    // Fonction qui vérifie si les jeux de fichiers récupéré dans la dernière collecte contiennent bien TOUS les fichiers
    //	exemple : On vient de faire un retrieve avec 2 jeux de fichiers : celui de l'heure (ou du jour) passée et un autre d'il y a 2h
    //			On va donc écarter le jeu de l'heure passée et vérifier s'il ne manque pas de fichier pour le jeu de fichiers d'il y a 2h
    //
	//	modif 14:35 23/11/2007 Gwen ajout d'un paramètre : $flat_file_template
    //
	function check_last_files_collected($previous_periode, $period_tempo_value, $period_type, $flat_file_template, $id_connection) {
        //Récupération des HEURES et JOURS qui viennent d'être collectées (présents dans la table " sys_flat_file_uploaded_list " )
        $query = '
			SELECT DISTINCT 
				' . $period_type . ' AS period
			FROM 
				sys_flat_file_uploaded_list
			WHERE 
				flat_file_template = \'' . $flat_file_template . '\'
				AND id_connection = \'' . $id_connection . '\'
			ORDER BY 
				' . $period_type;

        //__debug($query,"check_last_files_collected $flat_file_template / $id_connection");
        $deb = getmicrotime();
        $result2 = $this->database->execute($query);
        $fin = getmicrotime();

        //__debug("temps de traitement : ".round($fin-$deb,3)." sec");
        while ($row = $this->database->getQueryResults($result2, 1)) {
            //On écarte "l'heure précédente" qui est traitée différement
            if ($row['period'] != $previous_periode)
                $period_tab[] = $row['period'];
            // else {echo "</br>l'heure ".$row['hour']." a été écartée";}
        }
        if (isset($period_tab) AND is_array($period_tab))
            $period_tab = array_unique($period_tab);

        $result3 = $this->get_missing_files_for_some_periods($period_tab, $period_type, $flat_file_template, $id_connection);

        //Traitement des données récupérées pour les placer dans une variable de classe
        //Si FALSE est retourné à la place du resultSet, c'est qu'aucun fichier n'a été récupéré
        if ($result3) {
            while ($row = $this->database->getQueryResults($result3, 1)) {
                $sonde = $row['connection_name'];
                $period = $row['period'];
                $this->absent_files['tracelog'][$period][$sonde][] = $row['flat_file_name'];
            }
        }
    }

    // 11/07/2007 : Jérémy
    // Fonction qui vérifie si les jeux de fichiers récupéré pour la période qui correspond à la temporisation ont bien TOUS été récupérés
    //	exemple : La temporisation est de 3H, il est 16H30, donc d'après le calcul, 16H30 moins 3H donne 13H30, on ne prend ici que l'heure sans les minutes
    //		Le travail va consister à vérifier que TOUS les fichiers de 13H ( générés pour la période P telle que 13H00 <= P < 13H59) ont été récupérés pour TOUTES les sondes actives
    //
	//	modif 14:35 23/11/2007 Gwen ajout d'un paramètre : $flat_file_template
    //
	function check_tempo_period($tab_tempo, $period_type, $flat_file_template, $id_connection) {
        //Etant donné que les index du tableau $tab_tempo ne se suivent pas (à cause du ARRAY_UNIQUE appliqué dessuc auparavant), on utilise la boucle FOREACH plutot que FOR
        foreach ($tab_tempo AS $this_tempo) {
            $result = $this->get_missing_files_for_one_period($this_tempo, $period_type, $flat_file_template, $id_connection);
            //Traitement des données récupérées pour les placer dans une variable de classe
            while ($row = $this->database->getQueryResults($result, 1)) {
                $sonde = $row['connection_name'];
                $this->absent_files['tempo'][$this_tempo][$sonde][] = $row['flat_file_name'];
            }
        }
    }

    // 11/07/2007 : Jérémy
    //Fonction générique qui récupère les types de fichiers absent pour UNE LISTE D'HEURES données en paramêtre dans un tableau unidimensionnel
    //Cette fonction est utilisée par la fonction " check_last_files_collected() "qui a besoin de fournir en argument plusieurs périodes.
    //Il est aussi nécessaire de donner en paramêtre le type de période (day OU hour)
    //
	//	modif 14:35 23/11/2007 Gwen ajout d'un paramètre : $flat_file_template
    //
	function get_missing_files_for_some_periods($tab_periode, $type_periode, $flat_file_template, $id_connection) {
        //Reconstitution du format adéquate pour intégrer les heures (ou jour) dans la requête : ('2007041400','2007071100','2007041401',...)
        $cpt = count($tab_periode);

        if ($cpt > 0) {

            $periode = "('" . implode("','", $tab_periode) . "')";

            //Récupération des informations
            $query = '
				SELECT DISTINCT 
					t1.connection_name, 
					t3.' . $type_periode . ' AS period, 
					t2.flat_file_name
				FROM 
					sys_definition_connection t1, 
					sys_definition_flat_file_lib t2, 
					sys_flat_file_uploaded_list_archive t3
				WHERE 
					t1.on_off = 1
					AND t2.on_off = 1
					AND t3.' . $type_periode . ' IN ' . $periode . '
					AND t2.flat_file_naming_template NOT IN 
						(
							SELECT 
								flat_file_template
							FROM 
								sys_flat_file_uploaded_list_archive
							WHERE 
								' . $type_periode . ' = t3.' . $type_periode . '
								AND flat_file_template = \'' . $flat_file_template . '\'
								AND id_connection = t1.id_connection
						)
					AND t1.id_connection = t3.id_connection
					AND t1.id_connection = \'' . $id_connection . '\'
					AND t2.flat_file_naming_template = \'' . $flat_file_template . '\'
				ORDER BY 
					' . $type_periode . ', flat_file_name';
            //__debug($query,"Requete qui vérifie les fichiers de la collecte (plusieurs heures)");
            $deb = getmicrotime();
            $result4 = $this->database->execute($query);
            $fin = getmicrotime();
            //__debug("temps de traitement : ".round($fin-$deb,3)." sec");
        } else
            return false;

        return $result4;
    }

    // 11/07/2007 : Jérémy
    //Fonction générique qui récupère les types de fichiers absent pour UNE HEURE donnée en paramêtre dans une variable simple
    //Fonction utilisée par " check_tempo_period() " et " check_last_period() " qui ne necessite qu'une seule période en entrée
    //    De plus l'utilisation de NOT IN permet de sortir les types de fichiers même si la période n'éxiste pas, alors qu'avec la fonction ci-avant,
    //si la période est inconnue, aucune ligne n'est retournée, ce qui laisse croire qu'il n'y a pas de fichier manquant pour l'heure correspondante alors qu'il le sont TOUS
    //Il est aussi nécessaire de donner en paramêtre le type de période (day OU hour)
    //
	//	modif 14:35 23/11/2007 Gwen ajout d'un paramètre : $flat_file_template
    //
	function get_missing_files_for_one_period($periode, $type_periode, $flat_file_template, $id_connection) {
        $query = '
			SELECT 
				t1.connection_name, 
				t2.flat_file_name
			FROM 
				sys_definition_connection t1, 
				sys_definition_flat_file_lib t2
			WHERE 
				t1.on_off = 1
				AND t2.on_off = 1
				AND t2.flat_file_naming_template NOT IN 
					(
						SELECT 
							flat_file_template
						FROM 
							sys_flat_file_uploaded_list_archive
						WHERE 
							' . $type_periode . ' = \'' . $periode . '\'
							AND flat_file_template = \'' . $flat_file_template . '\'
							AND id_connection = t1.id_connection
					)
				AND t2.flat_file_naming_template = \'' . $flat_file_template . '\'
				AND t1.id_connection = \'' . $id_connection . '\'
			ORDER BY 
				connection_name, 
				flat_file_name';

        $deb = getmicrotime();
        //__debug($query,"Requete qui vérifie les fichiers de la collecte (UNE SEULE HEURE)",0);
        $result = $this->database->execute($query);
        $fin = getmicrotime();
        //__debug("temps de traitement : ".round($fin-$deb,3)." sec");

        return $result;
    }

    // 11/07/2007 : Jérémy
    //Fonction de traitement du tableau récupéré à l'issue des fonctions éxécutées précédemment
    //Cette fonction permet de vérifier le contenu du tableau, avec la fonction debug
    function show_table_contain() {
        $deb = getmicrotime();
        if (count($this->absent_files) > 0) {
            foreach ($this->absent_files AS $type_traitement => $tab_periodes) {
                //__debug("- Traitement : $type_traitement");
                if (count($tab_periodes) > 0) {
                    foreach ($tab_periodes AS $periode => $tab_sonde) {
                        //__debug("--- Période : $periode");
                        if (count($tab_sonde) > 0) {
                            foreach ($tab_sonde AS $sonde => $tab_files) {
                                //__debug("------ Sonde : $sonde");
                                $cpt = count($tab_files);
                                for ($i = 0; $i < $cpt; $i++) {
                                    //__debug("- - - - - Fichier $i : ".$tab_files[$i]);
                                }
                            }
                        }
                    }
                }
            }
        }
        $fin = getmicrotime();
        //__debug("temps de traitement : ".round($fin-$deb,3)." sec");
    }

    // 30/07/2007 : Jérémy
    //Fonction de traitement du tableau récupéré à l'issue des fonctions éxécutées précédemment
    //Cette fonction consiste a récupérer les informations du tableau et à les afficher dans le tracelog
    function generate_tracelog_messages($periode_type) {
        if (count($this->absent_files) > 0) {
            foreach ($this->absent_files AS $type_traitement => $tab_periodes) {
                if (count($tab_periodes) > 0) {
                    foreach ($tab_periodes AS $periode => $tab_sonde) {
                        if (count($tab_sonde) > 0) {
                            foreach ($tab_sonde AS $sonde => $tab_files) {
                                $cpt = count($tab_files);
                                for ($i = 0; $i < $cpt; $i++) {

                                    // 16/04/2008 - Modif. benoit : correction du bug 6289. On indique les fichiers absents dans le tracelog uniquement pour le traitement de la tempo

                                    /* if ($type_traitement == "tracelog")
                                      {
                                      $severity = "Warning";
                                      $module = __T("A_TRACELOG_MODULE_LABEL_COLLECT");
                                      }
                                      else
                                      {
                                      $severity = "Critical";
                                      $module = __T("A_TRACELOG_MODULE_LABEL_ALARM");
                                      }

                                      $message = __T('A_FLAT_FILE_UPLOAD_ALARM_MISSING_FILE_FOR_PROBE',$tab_files[$i],$sonde,$periode_type,$this->transform_date($periode));

                                      sys_log_ast($severity, $this->system_name, $module, $message, "support_1", ""); */

                                    if ($type_traitement == "tempo") {
                                        $severity = "Warning";
                                        $module = __T("A_TRACELOG_MODULE_LABEL_COLLECT");

                                        $message = __T('A_FLAT_FILE_UPLOAD_ALARM_MISSING_FILE_FOR_PROBE', $tab_files[$i], $sonde, $periode_type, $this->transform_date($periode));

                                        sys_log_ast($severity, $this->system_name, $module, $message, 'support_1', '');
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    //Création d'UNE trap SNMP qui va envoyer les nom des connexions pour lesquelles les sonde n'ont pas générées tous les fichier pour la période de la tempo
    // @param $period_tempo_value : Date en fonction du mode hourly : AAAAMMDD (daily) ou AAAAMMDDHH (hourly)
    //
	// modif 14:41 23/11/2007 Gwen  suppression du paramètre
    //
	function generate_SNMP_trap() {
        // Récupération des variables nécessaires
        $trap_version = get_sys_global_parameters('snmp_trap_format');
        $trap_server = get_sys_global_parameters('snmp_server');
        $trap_port = get_sys_global_parameters('snmp_port');
        $cmd_hostname = exec('hostname', $array_result);
        // maj 14/04/08 christophe : correction bug BZ6327, mauvais format de la trap SNMP > on affiche le contenu du champ 'publisher' comme premier param.
        $hostname = get_sys_global_parameters('publisher');
        //$hostname = $array_result[0];
        $version_name = get_sys_global_parameters('product_name') . ' - ' . get_sys_global_parameters('product_version');

        //reconstitution de la liste des nom de connexions qui n'ont pas récupérées tous les fichiers
        $liste_sonde = '';
        $flag_pipe = false;
        // 07/04/2010 BBX : on test $this->absent_files['tempo'] avant d'appliquer le foreach
        if (is_array($this->absent_files['tempo'])) {
            // 08/07/2013 NSE bz 34788: SNMP Trap community global parameter
            $community = get_sys_global_parameters("snmp_community", "public");
            $agent_address = get_sys_global_parameters("snmp_agent_address", "");
            $trapSendOk = $trapSendKo = 0;
            foreach ($this->absent_files['tempo'] AS $period_tempo_value => $tabsondes) {
                foreach ($tabsondes AS $sonde => $periode) {
                    if ($flag_pipe)
                        $liste_sonde .= ' | '; //On ne met pas de pipe au début, ni à la fin
                    $liste_sonde .= $sonde;
                    $flag_pipe = true;
                }

                // Construction de la trap snmp
                switch ($trap_version) {
                    // 08/07/2013 NSE bz 34789: SNMP Trap community global parameter
                    // ajout de " autour du paramètre community
                    case '1':
                        $trap[0] = "-v " . $trap_version . ' -c "' . $community . '" ' . $trap_server . ":" . $trap_port . " $this->entreprise \"" . $agent_address . "\" 6 4 \"\" ";
                        break;
                    case '2c':
                        $trap[0] = "-v " . $trap_version . ' -c "' . $community . '" ' . $trap_server . ":" . $trap_port . " \"\" $this->entreprise.0.4 ";
                        if ($agent_address != "") {//there is an agent address to specify
                            $trap[0] .= "snmpTrapAddress.0 a \"" . $agent_address . "\" "; //RFC 2576 Coexistence between SNMP versions => cf 3.1.4
                        }
                        break;
                    default:
                        // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
                        displayInDemon('The version of the trap in sys_global_paramleters is not correct (either 1 or 2c must be types)<br>' . "\n");
                } // switch

                $trap[1] = ' s "' . $hostname . '" ';    //Probname
                $trap[2] = ' s "' . $version_name . '" ';   //Application Name
                // maj 14/04/08 christophe : correction bug BZ6327, mauvais format de la trap SNMP > on affiche 'system alerts' à la place de static alarm
                $trap[3] = ' s "System alerts" ';    //Alarm Group
                $trap[4] = ' s "' . $period_tempo_value . '" ';  //Alarm Date (HOUR or DAY)
                // maj 12/06/2008 - maxime : Correction bug BZ6663 - L'alarme système a comme severité Warning et non Critical
                //					La MIB d'astellia est modifié, on ajoute dans arm Level la valeur 4 en lui attribuant le label Warning...
                $trap[5] = ' i "4" '; //Alarm Level
                $trap[6] = ' s "Probes missing file(s)" '; // Alarm name
                $trap[7] = ' s "' . $liste_sonde . '" ';   //Nom des connexions pour lesquelles il manque des fichier pour l'heure de la TEMPO
                $trap[8] = ' s "PROBE" ';     // Name of the Network Element
                $trap[9] = ' s "See Trending&Aggregation tracelog for more details" ';
                $trap[10] = 'i "1"';      //Alarm destination ( 1 = Alarm will be displyed in all console mode )
                // On va tronquer les chaine de caractères suivant la taille maximale acceptée par chaque champ
                $trap[1] = $this->getMaxChaine($trap[1], 50);
                $trap[2] = $this->getMaxChaine($trap[2], 50);
                $trap[3] = $this->getMaxChaine($trap[3], 50);
                $trap[4] = $this->getMaxChaine($trap[4], 50);
                $trap[6] = $this->getMaxChaine($trap[6], 50);
                $trap[7] = $this->getMaxChaine($trap[7], 255);
                $trap[8] = $this->getMaxChaine($trap[8], 50);
                $trap[9] = $this->getMaxChaine($trap[9], 50);

                // on compte le nombre de paramètre de la trap snmp
                $nb_param_trap = count($trap);

                //Reconstitution de la trap final, on colle un a un les paramètres
                $trap_finale = $trap[0];
                // On insère "enterprise" pour chaque paramètre de la trap snmp
                for ($i = 1; $i <= $nb_param_trap - 1; $i++)
                    $trap_finale .= " $this->entreprise.$i $trap[$i] ";

                // Envoi de la Trap qui vient d'être générée
                $cmd = '/usr/bin/snmptrap ' . $trap_finale;
                ////__debug($cmd, "Commande finale");
                // 08/07/2013 NSE bz 34788: SNMP Trap community global parameter
                // ajout de messages plus précis dans le file_demon
                $output = array();
                $ret = exec($cmd, $output, $return);
                if ($return == 0)
                    $trapSendOk++;
                else {
                    $trapSendKo++;
                    echo "Erreur d'envoi pour : " . '/usr/bin/snmptrap ' . $trap_finale . '<br>';
                    echo "Retour : " . $return . (!empty($ret) ? "<br>$ret<br>" : "<br>");
                    if (!empty($output))
                        print_r($output);
                }
                unset($output);
            }
            // rapport de l'envoi dans le file_demon et le tracelog
            if ($trapSendKo == 0) {
                echo 'Toutes les trapes ont été envoyées avec succès<br/><br/>';
                $message = __T('A_SNMP_SEND', $trapSendOk);
                sys_log_ast("Info", get_sys_global_parameters("system_name"), __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
            } else {
                echo '<b>' . $trapSendKo . ' erreurs</b> sur ' . ($trapSendKo + $trapSendOk) . ' traps envoyées<br/><br/>';
                $message = __T('A_SNMP_SEND_ERROR', $trapSendKo, count($this->trap));
                sys_log_ast("Critical", get_sys_global_parameters("system_name"), __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
            }
        }
    }

    //Function développée par Maxime disponible dans la classe  " alarmSNMP.class.php "
    function getMaxChaine($chaine, $max) {
        $Tchaine = '';
        $value = explode('"', $chaine); //récupération des éléments  compris entre
        // Si le contenu entre les deux guillemets est supérieur à MAX caractères alors on va tronquer cette chaine
        if (strlen($value[1]) > $max) {
            $value[1] = substr($value[1], 0, $max - 3);
            $Tchaine = $value[0] . '"' . $value[1] . '..."' . $value[2];
        } else {
            //Si la taille de la VALUE est inférieur à MAX alors on renvoit la chaine telle qu'elle
            $Tchaine = $chaine;
        }
        return $Tchaine;
    }

    //Création d'UN mail qui va envoyer les noms des connexions pour lesquelles les sondes n'ont pas générées tous les fichiers pour la période de la tempo
    // @param $period_tempo_value : Date en fonction du mode hourly : AAAAMMDD (daily) ou AAAAMMDDHH (hourly)
    // @param $period_type : "day" ou "jour" en fonction du compute_mode
    //
	// modif 14:44 23/11/2007 Gwen : suppression des paramètres
    // 
    //	- 11:44 24/10/2008 GHX : modification pour prendre en compte la DE sur les alarmes systemes (paramétrage par connexion)
    // 
    function generate_mail() {
        $product_name = get_sys_global_parameters('product_name');
        $nom_appli = get_sys_global_parameters('system_name');
        $mail_reply = get_sys_global_parameters('mail_reply');
        $email_activation = get_sys_global_parameters('automatic_email_activation');
        $today = date('Y-m-d');

        if ($email_activation == 1) {
            //RECUPERATION DES ADRESSE MAIL auquelles sera envoyé le mail d'alarme
            // 03/09/2007 christophe : les mails sont envoyés au aadmin dont le compte est actif et valide (on_off et date_valid).
            // 09:21 24/10/2008 GHX
            // DE sur les alarmes systemes
            // 09/06/2011 BBX -PARTITIONING-
            // Correction des casts
            // 20/02/2012 NSE DE Astellia Portal Lot2 : suppression de date_valid et on_off
            $query = "
				SELECT 
					user_mail,
					connection_name
				FROM 
					users t1, 
					sys_definition_users_per_connection t2,
					sys_definition_connection t3
				WHERE 
					id_user = sdupc_id_user
					AND sdupc_id_connection = id_connection
					AND t3.on_off = 1
			";


            $result = $this->database->execute($query);

            // mémorise pour chaque personne les connexions sur lesquelles il est abonné
            while ($row = $this->database->getQueryResults($result, 1))
                $mailing_list[$row['user_mail']][] = $row['connection_name'];

            // On boucle sur toutes les personnes qui sont abonnés à des connexions
            if (isset($mailing_list) && count($mailing_list) > 0) {
                foreach ($mailing_list AS $user_mail => $listing_connections) {
                    $nb_tot_line = 0;
                    $nb_line_periode = array();
                    foreach ($this->absent_files['tempo'] AS $period => $tab_sonde) {
                        $nb_temporaire = 0;
                        //on parcours chaque sonde
                        foreach ($tab_sonde AS $sonde => $tab_periode) {
                            // On test pour savoir si la personne est abonné à la connexion
                            if (in_array($sonde, $listing_connections)) {
                                $nb_tot_line += count($tab_periode);
                                $nb_temporaire += count($tab_periode);
                                //Pour chaque période on mémorise le nombre de ligne à fusionner, toute sonde comprise
                                $nb_line_periode[$period] = $nb_temporaire;
                            }
                        }
                    }

                    // Si aucune alarme de généré pour l'utilisateur on n'envoie pas de mail
                    if ($nb_tot_line == 0)
                        continue;

                    $m = new Mail("html"); // create the mail
                    $m->From($nom_appli . "<$mail_reply>");
                    $m->ReplyTo($mail_reply);
                    $m->To($user_mail);
                    $m->Subject(__T('A_FLAT_FILE_UPLOAD_ALARM_MAIL_SUBJECT', $nom_appli, $nb_tot_line, $today));

                    //CREATION du message
                    $message = '
					<center>
						<span style="font: normal 18pt Verdana, Arial, sans-serif; color : black;">
							Missing files
						</span>
						<br/><br/>
						<table align="center" valign="middle" cellpadding="15" cellspacing="0" width="600px" style="border: 1px dotted #787878; background-color: #ebebeb;">
							<tr>
								<td align="center" style="font: normal 11pt Verdana, Arial, sans-serif; color : #585858;">
									' . __T('G_MAIL_AUTOGENERATED_DO_NOT_REPLY') . '
									<br/><br/><br/>
									<table>
										<tr style="font: bold 10pt Verdana, Arial, sans-serif; color: #585858;">
											<th width="150px"> ' . ucfirst($period_type) . ' </th>
											<th class="texteGrisBold" width="200px"> Probe </th>
											<th class="texteGrisBold" width="200px"> Missing file type </th>
										</tr>';
                    //Compteur pour colorer une sonde sur 2
                    $cpt_row_color = 0;

                    foreach ($this->absent_files['tempo'] AS $period => $tab_sonde) {
                        if ($nb_line_periode[$period] == 0)
                            continue;

                        // flag_fusion_date permet de déterminer si la cellule (fusionnée) qui affiche la date a déjà été affichée
                        // En cas d'absence de ce booléen, le tableau est décalé, les cellules ne sont plus à leur place
                        $flag_fusion_date = true;
                        if ($flag_fusion_date) {
                            $message .= '
										<tr style="font: normal 10pt Verdana, Arial, sans-serif; color : #585858;">
											<td rowspan="' . $nb_line_periode[$period] . '" align="center" bgcolor="white">
												<font size="3">
													' . $this->transform_date($period) . '
												</font>
											</td>';
                        }

                        foreach ($tab_sonde AS $sonde => $tab_periode) {
                            if (!in_array($sonde, $listing_connections))
                                continue;

                            //En fonction de la parité du compteur de sonde, la couleur du groupe de ligne est différente
                            $style_row = ($cpt_row_color % 2 == 0) ? 'bgcolor="#DDDDDD"' : 'bgcolor="#ffffff"';
                            $flag_fusion_sonde = true;
                            //Nombre de fichier manquant pour la sonde en cours de traitement
                            $fusion_sonde = count($tab_periode);

                            //on doit créer une nouvelle ligne si la première est pleine pour y mettre une sonde et les fichiers manquant
                            if (!$flag_fusion_date AND $flag_fusion_sonde) {
                                $message .= '
										<tr style="font: normal 10pt Verdana, Arial, sans-serif; color : #585858;">';
                            }
                            //$flag_fusion_date prend false juste après la condition précédente pour ne pas créer 1 balise TR ouvrante EN TROP
                            $flag_fusion_date = false;

                            for ($i = 0; $i < $fusion_sonde; $i++) {
                                if ($flag_fusion_sonde) {
                                    $message .= '
											<td ' . $style_row . ' rowspan="' . $fusion_sonde . '">' . $sonde . '</td>';
                                }
                                if (!$flag_fusion_date and ! $flag_fusion_sonde) {
                                    $message .= '
										<tr style="font: normal 10pt Verdana, Arial, sans-serif; color: #585858;">';
                                }
                                //$flag_fusion_sonde prend false juste après la condition précédente pour ne pas créer une balise TR ouvrante en TROP
                                $flag_fusion_sonde = false;
                                $message .= '
											<td ' . $style_row . '>' . $tab_periode[$i] . '</td>
										</tr>';
                            } //Fin boucle type de fichier
                            $cpt_row_color++; //On change de ligne (sonde) donc on incrémente le compteur pour modifier la couleur de la prochaine ligne
                        }//Fin boucle sonde
                    }//Fin boucle periode

                    $message .= '
									</table>
								</td>
							</tr>
						</table>
					</center>';
                    //$message = "test pour free et d'autres boites mails externes";
                    $m->Body($message); //Paramètrage du corps du message
                    $m->Priority(1); //Paramétrage de la priorité (1 = haute, 4 = basse)
                    ////__debug($m->Get(),"Message envoyé");
                    $m->Send();   //Envoi du mail
                }
            }
        } else
        // 11:23 09/07/2009 SCT : amélioration de l'affichage du démon
            displayInDemon('La variable automatic_email_activation est égale à 0. L\'envoi des mails est inhibé.<br>' . "\n");
    }

    //Fonction qui prend en argument une date au format "Acurio" et qui renvoi une date formatée avec des tirets et un h pour les heures (s'il y en a)
    //La fonction accepte les dates au format :		- date + heure	AAAAMMJJHH	-> AAAA-MM-JJ HH:00
    //								- date			AAAAMMJJ	-> AAAA-MM-JJ
    function transform_date($old_date) {
        //FORMATAGE DE LA DATE
        $year = substr($old_date, 0, 4);
        $month = substr($old_date, 4, 2);
        $day = substr($old_date, 6, 2);
        if (strlen($old_date) > 8) {
            $hour = substr($old_date, 8, 2);
            $new_date = $year . '-' . $month . '-' . $day . ' ' . $hour;
            if (strlen($old_date) > 10) {
                $minute = substr($old_date, 10, 2);
                $new_date .= $minute;
            } else
                $new_date .= ':00';
        } else
            $new_date = $year . '-' . $month . '-' . $day;
        return $new_date;
    }

    // 19/02/2008 - Modif. benoit : ajout des fonctions 'decomposeExclusionValues()' et 'getIntervalValues()' permettant de retourner une chaine de la forme "0;10-22;5;7" en une suite de valeurs uniques
    function decomposeExclusionValues($exclusion) {
        $exclusion_values = array();

        for ($i = 0; $i < count($exclusion); $i++) {
            if (strpos($exclusion[$i], '-') === false) // Valeur unique
                $exclusion_values[] = $exclusion[$i];
            else // Intervalle
                $exclusion_values = array_merge($exclusion_values, $this->getIntervalValues(explode('-', $exclusion[$i])));
        }

        // On supprime les doublons
        $exclusion_values = array_unique($exclusion_values);

        // On trie le tableau
        sort($exclusion_values);

        return $exclusion_values;
    }

    function getIntervalValues($interval) {
        // Si les valeurs de départ et d'arrivée dans l'intervalle sont les mêmes, on retourne la première valeur
        if ((integer) $interval[0] == (integer) $interval[1])
            return array((integer) $interval[0]);

        // On decompose l'intervalle en un tableau de valeurs
        $interval_values = array();
        for ($i = $interval[0]; $i <= $interval[1]; $i++)
            $interval_values[] = (integer) $i;

        return $interval_values;
    }

    // maj 27/05/2010 MPR : Ajout de la fonction pour le calcul de Source Availability
    /**
     * Fonction qui calcul Source Availability sur le niveau d'agrégation temporelle minimum
     */
    function calculateSourceAvailability() {
        if (get_sys_global_parameters("activation_source_availability", 0)) {
            $SaCalculation = new SA_Calculation();
            $SaCalculation->setDebug(1);
            $SaCalculation->calculateSaConnectionsOnTaMin();
            $SaCalculation->calculateSaConnectionsOnDay();
        }
    }

    // End function calculateSourceAvailability()

    /**
     * Fonction permettant de supprimer des doublons dans la table sys_flat_file_uploaded_list_archive
     *
     */
    public function remove_duplicated_rows() {
        global $database_connection;
        $query = 'DELETE FROM sys_flat_file_uploaded_list_archive WHERE
                     ctid IN ( SELECT ctid FROM (
                                                  SELECT ctid, ROW_NUMBER() OVER
                                                   (partition BY hour, day, uploaded_flat_file_name
                                                      ORDER BY ctid
                                                    ) AS rnum 
                                                  FROM sys_flat_file_uploaded_list_archive
                                                 ) t WHERE t.rnum > 1
                              );';
        $res = pg_query($database_connection, $query);
        __debug($query, "QUERY");
        $nb_deleting = pg_affected_rows($res);
        displayInDemon(" ligne(s) effacée(s)<br>");
    }

}

?>
