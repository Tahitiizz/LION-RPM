<?php

/**
 * Class permettant de récupérer les informations sur les fichiers collectés
 *
 * @author e.camara
 */
class LogFlatFile {

    
    private $flat_file_info;

    public function __construct() {
        $this->purge_table();
        $this->flat_file_info = array();
        $this->get_type_template();
        $this->not_to_treate = array();
        $this->flat_file_template_treated = array();
        $this->system_name = get_sys_global_parameters("system_name");        
    }

    /**
     * Comptage des fichiers traités
     * @param type $id_connection
     */
    public function log_nb_file_treated($id_connection) {
        if (isset($this->flat_file_info[$id_connection]['nb_files_treated'])) {
            $nb_file_treated = $this->flat_file_info[$id_connection]['nb_files_treated'];
            $nb_file_treated++;
            $this->flat_file_info[$id_connection]['nb_files_treated'] = $nb_file_treated;
        } else {
            $this->flat_file_info[$id_connection]['nb_files_treated'] = 1;
        }
    }

    /**
     * Gestion des fichiers dont la collecte a été désyncronisée
     * @param type $id_connection
     * @param type $file_name
     */
    public function log_nb_desync_flat_file_treated($id_connection, $file_name) {
        if (isset($this->flat_file_info[$id_connection]['nb_files_no_sync'])) {
            $nb_files_no_sync = $this->flat_file_info[$id_connection]['nb_files_no_sync'];
            $nb_files_no_sync++;
            $this->flat_file_info[$id_connection]['nb_files_no_sync'] = $nb_files_no_sync;
        } else {
            $this->flat_file_info[$id_connection]['nb_files_no_sync'] = 1;
        }

        if (isset($this->flat_file_info[$id_connection]['list_files_no_sync'])) {
            $list_files_no_sync = $this->flat_file_info[$id_connection]['list_files_no_sync'];
            array_push($list_files_no_sync, $file_name);
            $this->flat_file_info[$id_connection]['list_files_no_sync'] = $list_files_no_sync;
        } else {
            $list_files_no_sync = array();
            array_push($list_files_no_sync, $file_name);
            $this->flat_file_info[$id_connection]['list_files_no_sync'] = $list_files_no_sync;
        }
    }

    /**
     * Traitement des fichiers avec un mauvais timestamp (l'heure indiquée
     * dans le nom du fichier est différent de l'heure traitée
     * @param type $id_connection
     * @param type $file_name
     */
    function log_nb_flat_file_bad_tmp($id_connection, $file_name) {
        if (isset($this->flat_file_info[$id_connection]['nb_files_bad_tmp'])) {
            $nb_flat_file_bad_tmp = $this->flat_file_info[$id_connection]['nb_files_bad_tmp'];
            $nb_flat_file_bad_tmp++;
            $this->flat_file_info[$id_connection]['nb_files_bad_tmp'] = $nb_flat_file_bad_tmp;
        } else {
            $this->flat_file_info[$id_connection]['nb_files_bad_tmp'] = 1;
        }

        if (isset($this->flat_file_info[$id_connection]['list_files_bad_tmp'])) {
            $list_files_bad_tmp = $this->flat_file_info[$id_connection]['list_files_bad_tmp'];
            array_push($list_files_bad_tmp, $file_name);
            $this->flat_file_info[$id_connection]['list_files_bad_tmp'] = $list_files_bad_tmp;
        } else {
            $list_files_bad_tmp = array();
            array_push($list_files_bad_tmp, $file_name);
            $this->flat_file_info[$id_connection]['list_files_bad_tmp'] = $list_files_bad_tmp;
        }
    }

    /**
     * Retourne l'objet
     * @return type
     */
    public function getFlatFileInfo() {
        return $this->flat_file_info;
    }

    /**
     * Enregistre l'ID connexion
     * @param type $id_connection
     */
    public function setIdConnection($id_connection) {
        $this->flat_file_info[$id_connection]['id_connexion'] = $id_connection;
    }

    /**
     * Renseigne le nombre de fichiers traités
     * @param type $flat_file_treated
     */
    public function setNbFlatFileTreated($flat_file_treated) {
        $this->flat_file_info['nb_files_treated'] = $flat_file_treated;
    }

    /**
     * Renseigne le nombre de fichiers à collecter
     * @global type $database_connection
     */
    public function calculateNbFilesExpected() {
        $total_file_expected = 0;
        foreach ($this->flat_file_info as $id_connexion => $infos) {
            if (is_array($infos)) {
                if (isset($infos['flat_file_naming_template_to_treate'])) {
                    $total_file_expected = $total_file_expected + sizeof($infos['flat_file_naming_template_to_treate']);
                    $this->flat_file_info[$infos['id_connexion']]['nb_files_expected'] = sizeof($infos['flat_file_naming_template_to_treate']);
                }
            }
        }


        $this->flat_file_info['nb_total_files_expected'] = $total_file_expected;
    }

    /**
     * Permet de récupérer les types de template attendus pour chaque connexion
     * @global type $database_connection
     */
    public function get_type_template() {
        global $database_connection;
        $query = " SELECT flat_file_naming_template,sys_definition_sa_file_type_per_connection.sdsftpc_id_connection FROM sys_definition_sa_file_type_per_connection
          LEFT JOIN sys_definition_flat_file_lib ON sys_definition_sa_file_type_per_connection.sdsftpc_id_flat_file = sys_definition_flat_file_lib.id_flat_file";
        $res = pg_query($database_connection, $query);

        if ($res) {
            while ($row = pg_fetch_array($res)) {
                if (!isset($this->flat_file_info[$row['sdsftpc_id_connection']]['flat_file_naming_template_to_treate'])) {
                    $this->flat_file_info[$row['sdsftpc_id_connection']]['flat_file_naming_template_to_treate'] = array();
                }
                array_push($this->flat_file_info[$row['sdsftpc_id_connection']]['flat_file_naming_template_to_treate'], $row['flat_file_naming_template']);
            }
        }
    }
    
    /**
     * Permet de vider la table sys_definition_collect_info avant chaque collect
     * @global type $database_connection
     */
    public function purge_table() {
        global $database_connection;
        $query = "DELETE FROM sys_definition_collect_info";
        $res = pg_query($database_connection, $query);
    }

    /**
     * Renseigne la liste des fichiers traités et qui ne doivent pas être traités
     * @param type $id_connection
     * @param type $template_name
     * @param type $file_name
     */
    public function checkTemplateName($id_connection, $template_name, $file_name) {

        if (!isset($this->flat_file_info[$id_connection]['flat_file_naming_template_not_to_treate'])) {
            $this->flat_file_info[$id_connection]['flat_file_naming_template_not_to_treate'] = array();
        }

        if (isset($this->flat_file_info[$id_connection]['flat_file_naming_template_to_treate']) && is_array($this->flat_file_info[$id_connection]['flat_file_naming_template_to_treate'])) {
            if (!in_array($template_name, $this->flat_file_info[$id_connection]['flat_file_naming_template_to_treate'])) {
                array_push($this->flat_file_info[$id_connection]['flat_file_naming_template_not_to_treate'], $file_name);
            }
        }

        if (!isset($this->flat_file_info[$id_connection]['flat_file_template_treated'])) {
            $this->flat_file_info[$id_connection]['flat_file_template_treated'] = array();
        }

        array_push($this->flat_file_info[$id_connection]['flat_file_template_treated'], $file_name);
    }

    /**
     * Stokage des messages dans les tables de log
     * @global type $database_connection
     */
    public function log_treatement() {
        global $database_connection;

        $to_log = false;

        if ($this->flat_file_info['nb_total_files_expected'] == 0) {
            $message = "NO FILE EXPECTED. Please check Setup Connections menu";
            displayInDemon($message);
            sys_log_ast("Warning", $this->system_name, __T('A_TRACELOG_MODULE_LABEL_COLLECT'), $message, "support_1", "");
        }

        foreach ($this->flat_file_info as $id_connexion => $infos) {
            if (is_array($infos)) {

                if (isset($infos['nb_files_treated'])) {

                    $nb_files_bad_tmp = (isset($infos['nb_files_bad_tmp'])) ? $infos['nb_files_bad_tmp'] : "0";

                    $flat_file_naming_template_not_to_treate = (isset($infos['flat_file_naming_template_not_to_treate']) && !empty($infos['flat_file_naming_template_not_to_treate'])) ? implode(',', $infos['flat_file_naming_template_not_to_treate']) : NULL;

                    $nb_files_no_sync = (isset($infos['nb_files_no_sync'])) ? $infos['nb_files_no_sync'] : "0";

                    $list_files_no_sync = (isset($infos['list_files_no_sync'])) ? implode(',', $infos['list_files_no_sync']) : "";

                    $nb_files_expected = (isset($infos['nb_files_expected'])) ? $infos['nb_files_expected'] : "0";
                    
                    if ($nb_files_bad_tmp == 0 && $flat_file_naming_template_not_to_treate == NULL && $nb_files_no_sync == 0) {
                        $to_log = false;
                    }

                    if ($flat_file_naming_template_not_to_treate != NULL) { // Il y a des fichiers qui ont été traité alors qu'ils ne devaient pas l'être
                        $message = "CONNECTION ".$infos['id_connexion']." >> NOT TO COLLECT: " . implode(",", $infos['flat_file_naming_template_not_to_treate']);
                        displayInDemon($message, "alert");
                        sys_log_ast("Warning", $this->system_name, __T('A_TRACELOG_MODULE_LABEL_COLLECT'), $message, "support_1", "");
                        $to_log = true;
                    }

                    if ($nb_files_no_sync != 0) { // Il y a des fichiers collectés qui ne devraient pas l'être
                        $message = "CONNECTION ".$infos['id_connexion']." >> NOT TO COLLECT FOR THIS TIME (" . date("d F Y - H:i:s") . "): " . implode(",", $infos['list_files_no_sync']);
                        displayInDemon($message, "alert");
                        sys_log_ast("Warning", $this->system_name, __T('A_TRACELOG_MODULE_LABEL_COLLECT'), $message, "support_1", "");
                        $to_log = true;
                    }

                    if ($nb_files_bad_tmp != 0) { // Il y a des fichiers collectés qui ont un mauvais timestamp
                        $message = "CONNECTION ".$infos['id_connexion']." >> BAD TIMESTAMP:" . implode(",", $infos['list_files_bad_tmp']);
                        displayInDemon($message, "alert");
                        sys_log_ast("Warning", $this->system_name, __T('A_TRACELOG_MODULE_LABEL_COLLECT'), $message, "support_1", "");
                        $to_log = true;
                    }

                    if ($infos['nb_files_expected'] < $infos['nb_files_treated']) { // Le nombre de fichiers collectés est supérieur au nombre de fichiers à traiter
                        $message = "CONNECTION ".$infos['id_connexion']." >> EXCESS COLLECT. Number of files expected: " . $nb_files_expected . ". Number of files treated: " . $infos['nb_files_treated'];
                        displayInDemon($message, "alert");
                        sys_log_ast("Warning", $this->system_name, __T('A_TRACELOG_MODULE_LABEL_COLLECT'), $message, "support_1", "");
                        $to_log = true;
                    }


                    if ($to_log == true) {
                        $hour_collect = date("d F Y - H:i:s");
                        $query = "INSERT INTO sys_definition_collect_info (sdci_hour_collect, "
                                . "sdci_nb_files_treated, "
                                . "sdci_nb_files_expected, "
                                . "sdci_nb_files_bad_tmp, "
                                . "sdci_flat_file_naming_template_not_to_treate, "
                                . "sdci_nb_files_no_sync, sdci_list_files_no_sync, "
                                . "sdci_connection_id) "
                                . "VALUES('$hour_collect', "
                                . "'" . $infos['nb_files_treated'] . "', "
                                . "'$nb_files_expected', "
                                . "'" . $nb_files_bad_tmp . "', "
                                . "'" . $flat_file_naming_template_not_to_treate . "', "
                                . "'" . $nb_files_no_sync . "', "
                                . "'" . $list_files_no_sync . "',"
                                . "'$id_connexion')";
                      
                        pg_query($database_connection, $query);
                    }
                } else { // Aucun fichier traité pour cette connexion                  
                    $message = "CONNECTION ".$id_connexion." >> NO FILE COLLECTED";
                    displayInDemon($message, "alert");
                    sys_log_ast("Warning", $this->system_name, __T('A_TRACELOG_MODULE_LABEL_COLLECT'), $message, "support_1", "");
                    $hour_collect = date("d F Y - H:i:s");
                    $nb_files_expected = (isset($infos['nb_files_expected'])) ? $infos['nb_files_expected'] : "0";
                    $query = "INSERT INTO sys_definition_collect_info (sdci_hour_collect, "
                            . "sdci_nb_files_treated ,"
                            . "sdci_nb_files_expected ,"
                            . "sdci_connection_id) "
                            . "VALUES('$hour_collect', "
                            . "'0',"
                            . "'" . $nb_files_expected . "',"
                            . "'$id_connexion'"
                            . ")";
                    pg_query($database_connection, $query);
                }
            }
        }
    }

    /**
     * Traitement des fichiers IU
     * @param type $date_information
     * @param type $template_name
     * @param type $file_name
     * @param type $unique_identifier
     * @param type $source_info_heure_upload
     * @param type $id_connection
     */
    function log_collect_treatment_for_IU($date_information, $template_name, $file_name, $unique_identifier, $source_info_heure_upload, $id_connection) {
        $template_name = str_replace("*", "", $template_name);
        $template_name = str_replace("$", "", $template_name);
        // Comptage des fichiers traités
        $this->log_nb_file_treated($id_connection);
        $hour_from_date_information = substr($date_information, -2);

        $hour_from_file_name = substr($file_name, -12, -10); //heure extraite du nom du fichier
        $source_info_heure_upload = str_replace("-", "", $source_info_heure_upload);
        // Traitement sur le unique_identifier pour extraire la date
        $explode_hour_from_uniqid = explode("-", $unique_identifier); // délimiteur - . Par exple ittagong02_iucs_011705160000H-16/05/2017 00:00:00-19/05/2017 00:00:00 => ["ttagong02_iucs_011705160000H"] ["16/05/2017 00:00:00"] ["19/05/2017 00:00:00"]
        $explode_hour_from_uniqid = explode(" ", $explode_hour_from_uniqid[2]); // délimeteur [espace]. Par exple 19/05/2017 00:00:00 => ["19/05/2017"] ["00:00:00"]
        $explode_explode_hour_from_uniqid = explode("/", $explode_hour_from_uniqid[0]); // délimiteur /. Par exple 19/05/2017 => ["19"] ["05"] ["2017"]
        $format_date_explode_explode_hour_from_uniqid = $explode_explode_hour_from_uniqid[2] . "-" . $explode_explode_hour_from_uniqid[1] . "-" . $explode_explode_hour_from_uniqid[0];

        $format_hour = substr($explode_hour_from_uniqid[1], 0, 2) . ":00:00";
        $format_date_explode_explode_hour_from_uniqid = $format_date_explode_explode_hour_from_uniqid . " " . $format_hour; // Construction de la date au format souhaité : 2017-05-19 00:00:00

        $datetime = new DateTime();

        $diff_exists = array($datetime, 'diff');

        if (is_callable($diff_exists)) { // Test si DateTime::diff est définie
            // Calcul de la différence 
            $d = new DateTime($source_info_heure_upload);
            $diff = $d->diff(new DateTime($format_date_explode_explode_hour_from_uniqid));

            if ($diff->d <= 0) { //La différence ne dépasse pas 1 journée
                if ($diff->h > 1) { // La différence dépasse 1 heure donc des fichiers ont été collecté en retard
                    $message = 'CONNECTION '.$id_connection.' >> SYNC PROBLEM: ' . $file_name . ". Collect time: " . $source_info_heure_upload . ". Hour treated: " . $format_date_explode_explode_hour_from_uniqid;
                    displayInDemon($message, 'alert');
                    sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");

                    // Traitement des fichiers désyncronisés
                    $this->log_nb_desync_flat_file_treated($id_connection, $file_name);

                    if ($template_name == "R04.txt") {
                        // On vérifie également si le timestamp est correct
                        if ($hour_from_date_information != $hour_from_file_name) {
                            // Traitement des fichiers avec un mauvais tmp 
                            $this->log_nb_flat_file_bad_tmp($id_connection, $file_name);
                            $message = 'CONNECTION '.$id_connection.' >> BAD TIMESTAMP: the hour indicated in the file name ' . $file_name . ' does not corresponding with hour treated : ' . $hour_from_date_information . ':00';
                            displayInDemon($message, 'alert');
                            sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
                        }
                    }
                }
            } else {
                // Dans ce cas aussi des fichiers ont été collecté en retard
                $message = 'CONNECTION '.$id_connection.' >> SYNC PROBLEM: ' . $file_name . ". Collect time: " . $source_info_heure_upload . ". Hour treated: " . $format_date_explode_explode_hour_from_uniqid;
                displayInDemon($message, 'alert');
                sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
                // Traitement des fichiers désyncronisés
                $this->log_nb_desync_flat_file_treated($id_connection, $file_name);
                if ($template_name == "R04.txt") {
                    // On vérifie également si le timestamp est correct
                    if ($hour_from_date_information != $hour_from_file_name) {
                        // Traitement des fichiers avec un mauvais tmp
                        $this->log_nb_flat_file_bad_tmp($id_connection, $file_name);
                        $message = 'CONNECTION '.$id_connection.' >> BAD TIMESTAMP: the hour indicated in the file name ' . $file_name . ' does not corresponding with hour treated : ' . $hour_from_date_information . ':00';
                        displayInDemon($message, 'alert');
                        sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
                    }
                }
            }
        } else { // La methode DateTime::diff n'existe pas
            if ($hour_from_date_information != $hour_from_file_name) {
                $this->log_nb_desync_flat_file_treated($id_connection, $file_name);
                $message = 'CONNECTION '.$id_connection.' >> BAD TIMESTAMP: the hour indicated in the file name ' . $file_name . ' does not corresponding with hour treated : ' . $hour_from_date_information . ':00';
                displayInDemon($message, 'alert');
                sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
            }
        }
    }
    
    /**
     * Traitement des fichiers RRAN
     * @param type $date_information
     * @param type $template_name
     * @param type $file_name
     * @param type $unique_identifier
     * @param type $source_info_heure_upload
     * @param type $id_connection
     */
    function log_collect_treatment_for_RRAN($date_information, $template_name, $file_name, $unique_identifier, $source_info_heure_upload, $id_connection) {
        $template_name = str_replace("*", "", $template_name);
        $template_name = str_replace("$", "", $template_name);
        // Comptage des fichiers traités
        $this->log_nb_file_treated($id_connection);
        $source_info_heure_upload = str_replace("-", "", $source_info_heure_upload);
        // Extraction de la date pour le formater
        $year = substr($date_information, 0, 4);
        $month = substr($date_information, 4, 2);
        $day = substr($date_information, 6, 2);
        $hour = substr($date_information, 8, 2);
        // Construction de la date au format souhaité : 2009-01-20 01:00:00
        $format_date = $year . "-" . $month . "-" . $day . " " . $hour . ":00:00";
        $datetime = new DateTime();

        $diff_exists = array($datetime, 'diff');
        if (is_callable($diff_exists)) { // Test si DateTime::diff est définie
            // Calcul de la différence 
            $d = new DateTime($source_info_heure_upload);
            $diff = $d->diff(new DateTime($format_date));
            if ($diff->d <= 0) { //La différence ne dépasse pas 1 journée
                if ($diff->h > 1) { // La différence dépasse 1 heure donc des fichiers ont été collecté en retard
                    $message = 'CONNECTION '.$id_connection.' >> SYNC PROBLEM: ' . $file_name . ". Collect time: " . $source_info_heure_upload . ". Hour treated: " . $format_date;
                    displayInDemon($message, 'alert');
                    sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");

                    // Traitement des fichiers désyncronisés
                    $this->log_nb_desync_flat_file_treated($id_connection, $file_name);
                }
            } else {
                // Dans ce cas aussi des fichiers ont été collecté en retard
                $message = 'CONNECTION '.$id_connection.' >> SYNC PROBLEM: ' . $file_name . ". Collect time: " . $source_info_heure_upload . ". Hour treated: " . $format_date;
                displayInDemon($message, 'alert');
                sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");

                // Traitement des fichiers désyncronisés
                $this->log_nb_desync_flat_file_treated($id_connection, $file_name);
            }
        }
    }

    /**
     * Traitement des fichiers CORE CS
     * @param type $date_information
     * @param type $template_name
     * @param type $file_name
     * @param type $unique_identifier
     * @param type $source_info_heure_upload
     * @param type $id_connection
     */
    function log_collect_treatment_for_CoreCS($date_information, $template_name, $file_name, $unique_identifier, $source_info_heure_upload, $id_connection) {
        $template_name = str_replace("*", "", $template_name);
        $template_name = str_replace("$", "", $template_name);
        // Comptage des fichiers traités
        $this->log_nb_file_treated($id_connection);
        $source_info_heure_upload = str_replace("-", "", $source_info_heure_upload);
        $hour_from_date_information = substr($date_information, -2);
        $hour_from_file_name = substr($file_name, -8, -6); //heure extraite du nom du fichier
        $explode_hour_from_uniqid = substr($unique_identifier, -7, -5);
        // Récupération de la date traitée
        $format_date_from_file_name = substr($file_name, -19, -9); // SF_pipe_3_3_3_b_psuh649_2009_01_20_0100.cor => 2009_01_20
        $explode_hour_from_format_date_from_file_name = str_replace("_", "-", $format_date_from_file_name); // 2009_01_20 => 2009-01-20
        // Construction de la date au format souhaité : 2009-01-20 01:00:00
        $format_date = $explode_hour_from_format_date_from_file_name . " " . $explode_hour_from_uniqid . ":00:00";

        $datetime = new DateTime();
        $diff_exists = array($datetime, 'diff');
        if (is_callable($diff_exists)) { // Test si DateTime::diff est définie
            // Calcul de la différence 
            $d = new DateTime($source_info_heure_upload);
            $diff = $d->diff(new DateTime($format_date));
            if ($diff->d <= 0) { //La différence ne dépasse pas 1 journée
                if ($diff->h > 1) { // La différence dépasse 1 heure donc des fichiers ont été collecté en retard
                    $message = 'CONNECTION '.$id_connection.' >> SYNC PROBLEM: ' . $file_name . ". Collect time: " . $source_info_heure_upload . ". Hour treated: " . $format_date;
                    displayInDemon($message, 'alert');
                    sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");

                    // Traitement des fichiers désyncronisés
                    $this->log_nb_desync_flat_file_treated($id_connection, $file_name);

                    if ($template_name == ".COR") {
                        // On vérifie également si le timestamp est correct
                        if ($hour_from_date_information != $hour_from_file_name) {
                            // Traitement des fichiers avec un mauvais tmp 
                            $this->log_nb_flat_file_bad_tmp($id_connection, $file_name);
                            $message = 'CONNECTION '.$id_connection.' >> BAD TIMESTAMP: the hour indicated in the file name ' . $file_name . ' does not corresponding with hour treated : ' . $hour_from_date_information . ':00';
                            displayInDemon($message, 'alert');
                            sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
                        }
                    }
                }
            } else {
                // Dans ce cas aussi des fichiers ont été collecté en retard
                $message = 'CONNECTION '.$id_connection.' >> SYNC PROBLEM: ' . $file_name . ". Collect time: " . $source_info_heure_upload . ". Hour treated: " . $format_date;
                displayInDemon($message, 'alert');
                sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");

                // Traitement des fichiers désyncronisés
                $this->log_nb_desync_flat_file_treated($id_connection, $file_name);
                if ($template_name == ".COR") {
                    // On vérifie également si le timestamp est correct
                    if ($hour_from_date_information != $hour_from_file_name) {
                        // Traitement des fichiers avec un mauvais tmp
                        $this->log_nb_flat_file_bad_tmp($id_connection, $file_name);

                        $message = 'CONNECTION '.$id_connection.' >> BAD TIMESTAMP: the hour indicated in the file name ' . $file_name . ' does not corresponding with hour treated : ' . $hour_from_date_information . ':00';
                        displayInDemon($message, 'alert');
                        sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
                    }
                }
            }
        } else {
            if ($hour_from_date_information != $hour_from_file_name) {
                $this->log_nb_desync_flat_file_treated($id_connection, $file_name);
                $message = 'CONNECTION '.$id_connection.' >> BAD TIMESTAMP: the hour indicated in the file name ' . $file_name . ' does not corresponding with hour treated : ' . $hour_from_date_information . ':00';
                displayInDemon($message, 'alert');
                sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
            }
        }
    }

    /**
     * Traitement des fichiers CORE PS
     * @param type $date_information
     * @param type $template_name
     * @param type $file_name
     * @param type $unique_identifier
     * @param type $source_info_heure_upload
     * @param type $id_connection
     */
    function log_collect_treatment_for_CorePS($date_information, $template_name, $file_name, $unique_identifier, $source_info_heure_upload, $id_connection) {
        $template_name = str_replace("*", "", $template_name);
        $template_name = str_replace("$", "", $template_name);
        $this->log_nb_file_treated($id_connection);
        $source_info_heure_upload = str_replace("-", "", $source_info_heure_upload);
        if ($template_name == '.cps') {
            $hour_from_date_information = substr($date_information, -2);
            $hour_from_file_name = substr($file_name, -8, -6); //heure extraite du nom du fichier
            $explode_hour_from_uniqid = substr($unique_identifier, -7, -5);
            // Récupération de la date traitée
            $format_date_from_file_name = substr($file_name, -19, -9); // SF_gn_EXPERTISE_PS_2012_01_04_0300.CPS => 2012_01_04
            $explode_hour_from_format_date_from_file_name = str_replace("_", "-", $format_date_from_file_name); // 2009_01_20 => 2009-01-20
            // Construction de la date au format souhaité : 2009-01-20 01:00:00
            $format_date = $explode_hour_from_format_date_from_file_name . " " . $explode_hour_from_uniqid . ":00:00";

            $datetime = new DateTime();
            $diff_exists = array($datetime, 'diff');
            if (is_callable($diff_exists)) { // Test si DateTime::diff est définie
                // Calcul de la différence 
                $d = new DateTime($source_info_heure_upload);
                $diff = $d->diff(new DateTime($format_date));
                if ($diff->d <= 0) { //La différence ne dépasse pas 1 journée
                    if ($diff->h > 1) { // La différence dépasse 1 heure donc des fichiers ont été collecté en retard
                        $message = 'CONNECTION '.$id_connection.' >> SYNC PROBLEM: ' . $file_name . ". Collect time: " . $source_info_heure_upload . ". Hour treated: " . $format_date;
                        displayInDemon($message, 'alert');
                        sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");

                        // Traitement des fichiers désyncronisés
                        $this->log_nb_desync_flat_file_treated($id_connection, $file_name);

                        // On vérifie également si le timestamp est correct
                        if ($hour_from_date_information != $hour_from_file_name) {
                            // Traitement des fichiers avec un mauvais tmp 
                            $this->log_nb_flat_file_bad_tmp($id_connection, $file_name);
                            $message = 'CONNECTION '.$id_connection.' >> BAD TIMESTAMP: the hour indicated in the file name ' . $file_name . ' does not corresponding with hour treated : ' . $hour_from_date_information . ':00';
                            displayInDemon($message, 'alert');
                            sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
                        }
                    }
                } else {
                    // Dans ce cas aussi des fichiers ont été collecté en retard
                    $message = 'CONNECTION '.$id_connection.' >> SYNC PROBLEM: ' . $file_name . ". Collect time: " . $source_info_heure_upload . ". Hour treated: " . $format_date;
                    displayInDemon($message, 'alert');
                    sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");

                    // Traitement des fichiers désyncronisés
                    $this->log_nb_desync_flat_file_treated($id_connection, $file_name);
                    // On vérifie également si le timestamp est correct
                    if ($hour_from_date_information != $hour_from_file_name) {
                        // Traitement des fichiers avec un mauvais tmp
                        $this->log_nb_flat_file_bad_tmp($id_connection, $file_name);

                        $message = 'CONNECTION '.$id_connection.' >> BAD TIMESTAMP: the hour indicated in the file name ' . $file_name . ' does not corresponding with hour treated : ' . $hour_from_date_information . ':00';
                        displayInDemon($message, 'alert');
                        sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
                    }
                }
            } else {
                if ($hour_from_date_information != $hour_from_file_name) {
                    $this->log_nb_desync_flat_file_treated($id_connection, $file_name);
                    $message = 'CONNECTION '.$id_connection.' >> BAD TIMESTAMP: the hour indicated in the file name ' . $file_name . ' does not corresponding with hour treated : ' . $hour_from_date_information . ':00';
                    displayInDemon($message, 'alert');
                    sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
                }
            }
        } else {
            // Extraction de la date pour le formater
            $year = substr($date_information, 0, 4);
            $month = substr($date_information, 4, 2);
            $day = substr($date_information, 6, 2);
            $hour = substr($date_information, 8, 2);
            // Construction de la date au format souhaité : 2009-01-20 01:00:00
            $format_date = $year . "-" . $month . "-" . $day . " " . $hour . ":00:00";
            $datetime = new DateTime();

            $diff_exists = array($datetime, 'diff');
            if (is_callable($diff_exists)) { // Test si DateTime::diff est définie
                // Calcul de la différence 
                $d = new DateTime($source_info_heure_upload);
                $diff = $d->diff(new DateTime($format_date));
                if ($diff->d <= 0) { //La différence ne dépasse pas 1 journée
                    if ($diff->h > 1) { // La différence dépasse 1 heure donc des fichiers ont été collecté en retard
                        $message = 'CONNECTION '.$id_connection.' >> SYNC PROBLEM: ' . $file_name . ". Collect time: " . $source_info_heure_upload . ". Hour treated: " . $format_date;
                        displayInDemon($message, 'alert');
                        sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");

                        // Traitement des fichiers désyncronisés
                        $this->log_nb_desync_flat_file_treated($id_connection, $file_name);
                    }
                } else {
                    // Dans ce cas aussi des fichiers ont été collecté en retard
                    $message = 'CONNECTION '.$id_connection.' >> SYNC PROBLEM: ' . $file_name . ". Collect time: " . $source_info_heure_upload . ". Hour treated: " . $format_date;
                    displayInDemon($message, 'alert');
                    sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");

                    // Traitement des fichiers désyncronisés
                    $this->log_nb_desync_flat_file_treated($id_connection, $file_name);
                }
            }
        }
    }

    /**
     * Traitement des fichiers GPRS
     * @param type $date_information
     * @param type $template_name
     * @param type $file_name
     * @param type $unique_identifier
     * @param type $source_info_heure_upload
     * @param type $id_connection
     */
    function log_collect_treatment_for_GPRS($date_information, $template_name, $file_name, $unique_identifier, $source_info_heure_upload, $id_connection) {
        $template_name = str_replace("*", "", $template_name);
        $template_name = str_replace("$", "", $template_name);
        // Comptage des fichiers traités
        $this->log_nb_file_treated($id_connection);
        $hour_from_date_information = substr($date_information, -2);

        $hour_from_file_name = substr($file_name, -12, -10); //heure extraite du nom du fichier
        $source_info_heure_upload = str_replace("-", "", $source_info_heure_upload);
        // Traitement sur le unique_identifier pour extraire la date
        $explode_hour_from_uniqid = explode("-", $unique_identifier); // délimiteur - . Par exple ittagong02_iucs_011705160000H-16/05/2017 00:00:00-19/05/2017 00:00:00 => ["ttagong02_iucs_011705160000H"] ["16/05/2017 00:00:00"] ["19/05/2017 00:00:00"]
        $explode_hour_from_uniqid = explode(" ", $explode_hour_from_uniqid[2]); // délimeteur [espace]. Par exple 19/05/2017 00:00:00 => ["19/05/2017"] ["00:00:00"]
        $explode_explode_hour_from_uniqid = explode("/", $explode_hour_from_uniqid[0]); // délimiteur /. Par exple 19/05/2017 => ["19"] ["05"] ["2017"]
        $format_date_explode_explode_hour_from_uniqid = $explode_explode_hour_from_uniqid[2] . "-" . $explode_explode_hour_from_uniqid[1] . "-" . $explode_explode_hour_from_uniqid[0];

        // Conversion de l'heure extraite du flat_file_uniqid
        $format_hour = substr($explode_hour_from_uniqid[1], 0, 2) . ":00:00";
        $format_date_explode_explode_hour_from_uniqid = $format_date_explode_explode_hour_from_uniqid . " " . $format_hour; // Construction de la date au format souhaité : 2017-05-19 00:00:00

        $datetime = new DateTime();
        $diff_exists = array($datetime, 'diff');
        if (is_callable($diff_exists)) { // Test si DateTime::diff est définie
            // Calcul de la différence 
            $d = new DateTime($source_info_heure_upload);
            $diff = $d->diff(new DateTime($format_date_explode_explode_hour_from_uniqid));

            if ($diff->d <= 0) { //La différence ne dépasse pas 1 journée
                if ($diff->h > 1) { // La différence dépasse 1 heure donc des fichiers ont été collecté en retard
                    $message = 'CONNECTION '.$id_connection.' >> SYNC PROBLEM: ' . $file_name . ". Collect time: " . $source_info_heure_upload . ". Hour treated: " . $format_date_explode_explode_hour_from_uniqid;
                    displayInDemon($message, 'alert');
                    sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");

                    // Traitement des fichiers désyncronisés
                    $this->log_nb_desync_flat_file_treated($id_connection, $file_name);

                    if ($template_name == 'R03.txt') {
                        // On vérifie également si le timestamp est correct
                        if ($hour_from_date_information != $hour_from_file_name) {
                            // Traitement des fichiers avec un mauvais tmp 
                            $this->log_nb_flat_file_bad_tmp($id_connection, $file_name);
                            $message = 'CONNECTION '.$id_connection.' >> BAD TIMESTAMP: the hour indicated in the file name ' . $file_name . ' does not corresponding with hour treated : ' . $hour_from_date_information . ':00';
                            displayInDemon($message, 'alert');
                            sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
                        }
                    }
                }
            } else {
                // Dans ce cas aussi des fichiers ont été collecté en retard
                $message = 'CONNECTION '.$id_connection.' >> SYNC PROBLEM: ' . $file_name . ". Collect time: " . $source_info_heure_upload . ". Hour treated: " . $format_date_explode_explode_hour_from_uniqid;
                displayInDemon($message, 'alert');
                sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");

                // Traitement des fichiers désyncronisés
                $this->log_nb_desync_flat_file_treated($id_connection, $file_name);
                if ($template_name == 'R03.txt') {
                    // On vérifie également si le timestamp est correct
                    if ($hour_from_date_information != $hour_from_file_name) {
                        // Traitement des fichiers avec un mauvais tmp
                        $this->log_nb_flat_file_bad_tmp($id_connection, $file_name);

                        $message = 'CONNECTION '.$id_connection.' >> BAD TIMESTAMP: the hour indicated in the file name ' . $file_name . ' does not corresponding with hour treated : ' . $hour_from_date_information . ':00';
                        displayInDemon($message, 'alert');
                        sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
                    }
                }
            }
        } else { // La methode DateTime::diff n'existe pas
            if ($hour_from_date_information != $hour_from_file_name) {
                $this->log_nb_desync_flat_file_treated($id_connection, $file_name);
                $message = 'CONNECTION '.$id_connection.' >> BAD TIMESTAMP: the hour indicated in the file name ' . $file_name . ' does not corresponding with hour treated : ' . $hour_from_date_information . ':00';
                displayInDemon($message, 'alert');
                sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
            }
        }
    }

    /**
     * Traitement des fichiers GSM
     * @param type $date_information
     * @param type $template_name
     * @param type $file_name
     * @param type $unique_identifier
     * @param type $source_info_heure_upload
     * @param type $id_connection
     */
    public function log_collect_treatment_for_GSM($date_information, $template_name, $file_name, $unique_identifier, $source_info_heure_upload, $id_connection) {
        $template_name = str_replace("*", "", $template_name);
        $template_name = str_replace("$", "", $template_name);
        // Comptage des fichiers traités
        $this->log_nb_file_treated($id_connection);
        $hour_from_date_information = substr($date_information, -2);

        $hour_from_file_name = substr($file_name, -16, -14); //heure extraite du nom du fichier
        $source_info_heure_upload = str_replace("-", "", $source_info_heure_upload);
        // Traitement sur le unique_identifier pour extraire la date
        $explode_hour_from_uniqid = explode("-", $unique_identifier); // délimiteur - . Par exple ittagong02_iucs_011705160000H-16/05/2017 00:00:00-19/05/2017 00:00:00 => ["ttagong02_iucs_011705160000H"] ["16/05/2017 00:00:00"] ["19/05/2017 00:00:00"]
        $explode_hour_from_uniqid = explode(" ", $explode_hour_from_uniqid[2]); // délimeteur [espace]. Par exple 19/05/2017 00:00:00 => ["19/05/2017"] ["00:00:00"]
        $explode_explode_hour_from_uniqid = explode("/", $explode_hour_from_uniqid[0]); // délimiteur /. Par exple 19/05/2017 => ["19"] ["05"] ["2017"]
        $format_date_explode_explode_hour_from_uniqid = $explode_explode_hour_from_uniqid[2] . "-" . $explode_explode_hour_from_uniqid[1] . "-" . $explode_explode_hour_from_uniqid[0];

        // Conversion de l'heure extraite du flat_file_uniqid
        $format_hour = substr($explode_hour_from_uniqid[1], 0, 2) . ":00:00";
        $format_date_explode_explode_hour_from_uniqid = $format_date_explode_explode_hour_from_uniqid . " " . $format_hour; // Construction de la date au format souhaité : 2017-05-19 00:00:00

        $datetime = new DateTime();
        $diff_exists = array($datetime, 'diff');
        if (is_callable($diff_exists)) { // Test si DateTime::diff est définie
            // Calcul de la différence 
            $d = new DateTime($source_info_heure_upload);
            $diff = $d->diff(new DateTime($format_date_explode_explode_hour_from_uniqid));

            if ($diff->d <= 0) { //La différence ne dépasse pas 1 journée
                if ($diff->h > 1) { // La différence dépasse 1 heure donc des fichiers ont été collecté en retard
                    $message = 'CONNECTION '.$id_connection.' >> SYNC PROBLEM: ' . $file_name . ". Collect time: " . $source_info_heure_upload . ". Hour treated: " . $format_date_explode_explode_hour_from_uniqid;
                    displayInDemon($message, 'alert');
                    sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");

                    // Traitement des fichiers désyncronisés
                    $this->log_nb_desync_flat_file_treated($id_connection, $file_name);

                    if ($template_name == 'R02.txt') {
                        // On vérifie également si le timestamp est correct
                        if ($hour_from_date_information != $hour_from_file_name) {
                            // Traitement des fichiers avec un mauvais tmp 
                            $this->log_nb_flat_file_bad_tmp($id_connection, $file_name);
                            $message = 'CONNECTION '.$id_connection.' >> BAD TIMESTAMP: the hour indicated in the file name ' . $file_name . ' does not corresponding with hour treated : ' . $hour_from_date_information . ':00';
                            displayInDemon($message, 'alert');
                            sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
                        }
                    }
                }
            } else {
                // Dans ce cas aussi des fichiers ont été collecté en retard
                $message = 'CONNECTION '.$id_connection.' >> SYNC PROBLEM: ' . $file_name . ". Collect time: " . $source_info_heure_upload . ". Hour treated: " . $format_date_explode_explode_hour_from_uniqid;
                displayInDemon($message, 'alert');
                sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");

                // Traitement des fichiers désyncronisés
                $this->log_nb_desync_flat_file_treated($id_connection, $file_name);
                if ($template_name == 'R02.txt') {
                    // On vérifie également si le timestamp est correct
                    if ($hour_from_date_information != $hour_from_file_name) {
                        // Traitement des fichiers avec un mauvais tmp
                        $this->log_nb_flat_file_bad_tmp($id_connection, $file_name);

                        $message = 'CONNECTION '.$id_connection.' >> BAD TIMESTAMP: the hour indicated in the file name ' . $file_name . ' does not corresponding with hour treated : ' . $hour_from_date_information . ':00';
                        displayInDemon($message, 'alert');
                        sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
                    }
                }
            }
        } else { // La methode DateTime::diff n'existe pas
            if ($hour_from_date_information != $hour_from_file_name) {
                $this->log_nb_desync_flat_file_treated($id_connection, $file_name);
                $message = 'CONNECTION '.$id_connection.' >> BAD TIMESTAMP: the hour indicated in the file name ' . $file_name . ' does not corresponding with hour treated : ' . $hour_from_date_information . ':00';
                displayInDemon($message, 'alert');
                sys_log_ast("Warning", $this->system_name, __T("A_TRACELOG_MODULE_LABEL_COLLECT"), $message, "support_1", "");
            }
        }
    }

}
