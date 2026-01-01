<?php

/**
 * 
 *  CB 5.3.1
 * 
 * 22/05/2013 : WebService Topology
 */
/*
 * 	@cb41000@
 *
 * 	14/11/2007 - Copyright Astellia
 *
 * 	Composant de base version cb_4.1.0.00
 *
 * 	26/11/2008 GHX
 * 		- ajout de l'appel � la fonction updateObjectRef dans la fonction laod car sinon aucune ex�cution des requetes pour la mise � jour des tables de topo
 *
 * 	01/12/2008 GHX
 * 		- Suppression de tous les fichiers topo cr��s pendant l'upload (tous les fichiers cr��s pendant l'upload ont pour extension .topo)
 * 		- Suppression des lignes dans le fichier upload�
 *
 * 	02/12/2008 GHX
 * 		- modif pour prendre en compte TopologyChanges via IHM et Upload auto
 * 	21/08/2009 GHX
 * 		- Correction du BZ 11144  => message d'erreur plus pr�cis
 * 	18/09/2009 GHX
 * 		- Correction d'un probl�me sur la suppression des fichiers
 * 	09/11/2009 MPR
 * 		- D�but correction BZ 12248 - Chargement d'un fichier contenant trx/charge et/ou/sans coords
 * 06/04/2011 NSE bz 21719 : ajout du param�tre facultatif $time_limit au constructeur de la classe.
 * 21/04/2011 NSE DE Non unique Labels : passage de l'id product en param�tre lors du new TopologyCheck
 * 17/05/2011 NSE DE Topology characters replacement : Cr�ation de la fonction de remplacement des caract�res dans la topo
 * 09/06/2011 NSE bz 22512 : le header du label peut aussi �tre postfix� par '_label'
 * 12/02/2015 JLG bz 45947/20604 : if one msc is associated to a network, 
 * 	all other items with unique parent (msc, smscenter...) will be associated with this network
 *
 */
?>
<?php

/**
 * Classe Topology 	- Classe principale du module Topology
 * 				- Execute toutes les �tapes d'un upload Topology
 * 				- Elle h�rite de la classe TopologyLib
 *
 * @version 4.1.0.00
 * @package Topology
 * @author MPR
 * @since CB4.1.0.0
 *
 * 	maj MPR : R��criture du fichier 
 */
// 16/05/2011 NSE DE Topology characters replacement
include_once(REP_PHYSIQUE_NIVEAU_0 . '/class/topology/TopologyCharactersReplacementRules.class.php');

class Topology extends TopologyLib {

    /**
     * Construteur
     * 06/04/2011 NSE bz 21719 : ajout du param�tre facultatif $time_limit
     */
    public function __construct($time_limit = '') {
        parent::__construct();
        // 06/04/2011 NSE bz 21719 : on ne limite pas syst�matiquement la dur�e d'ex�cution � 1200.
        // Ainsi, si on ne sp�cifie pas de limite, le processus global pourra g�rer le TL (param�tre max_process_execution_time) dans le retrieve par exemple
        if (!empty($time_limit))
            set_time_limit($time_limit);
        //initialisation des variables locales
        self::$day = date("Ymd");
    }

// End function __construct()

    /**
     * Fonction qui initialise les variables de la classe
     */
    public function init() {
        $this->file = self::$file;
        $this->mode = self::$mode;
        $this->delimiter = self::$delimiter;
        $this->type_maj = self::$type_maj;
        $this->errors = self::$errors;
        $this->messages = array();
        $this->changes = self::$changes;
    }

// End function init

    /**
     * Fonction qui retourne les erreurs rencontr�es
     *
     * @return  array $this->errors :  liste des erreurs rencontr�es
     */
    public function getErrors() {
        return self::$errors;
    }

// End function getErrors

    /**
     * 06/12/2011 BBX
     * BZ 24854 : ajout d'une m�thode permettant d'ajouter les erreurs au tracelog
     */
    public function tracelogErrors($module = 'Topology') {
        $app = get_sys_global_parameters('system_name');
        foreach (self::$errors as $topoError) {
            sys_log_ast("Warning", $app, $module, $topoError, 'support_1', '');
        }
    }

    /**
     * Fonction qui retourne les messages indiquant les changements effectu�s
     *
     * @return  array $this->messages :  liste des messages indiquant les changements effectu�s
     */
    public function getMessages() {
        return $this->messages;
    }

// End function getMessages

    /**
     * Fonction qui lance la liste de checks � effectuer sur le fichier charg�
     */
    private function check() {
        $this->demon('<br/>');
        $this->demon('<h3 style="color:#fff;background-color:#0080ff">D�but - Contr�le de coh�rence des donn�es</h3>');
        // 21/04/2011 NSE DE Non unique Labels : passage de l'id produit en param�tre
        $check = new TopologyCheck(self::$id_prod);

        $this->demon('<h3 style="color:#fff;background-color:#0080ff">Fin - Contr�le de coh�rence des donn�es</h3>');
    }

// End function check()

    /**
     * Fonction qui ajoute les nouveaux �l�ments r�seau ou 3�me axe dans la table edw_object_ref
     */
    private function addElements() {
        $this->demon('<h3 style="color:#fff;background-color:#0080ff">D�but - Insertion des nouveaux �l�ments r�seau ou 3�me axe dans la topologie</h3>');

        $AddElements = new TopologyAddElements();

        $this->demon('<h3 style="color:#fff;background-color:#0080ff">Fin - Insertion des nouveaux �l�ments r�seau ou 3�me axe dans la topologie</h3>');
    }

// End function addElements

    /**
     * On convertie les coordon�es longitude et latitude en x et y
     *
     * @param boolean $gps TRUE ou FALSE si c'est des donn�es GPS a convertir
     * @param boolean coords TRUE ou FALSE si c'est des donn�es coordonn�es � convertir
     * @return string query : requ�te qui convertie les coordonn�es GPS en x et y
     */
    private function convertLatitudeLongitudeToXY($gps, $coords) {
        $deb = getmicrotime();

        $isOk = true;
        // 4326 correspond au srid des coordonn�es GPS
        $_srid = "(SELECT srid FROM sys_gis_config_global LIMIT 1)";

        // if ( $gps == false && $coords == true )
        // {
        // CAST AUTO PG 9.1
        // 12/05/2011 BBX
        $query = "
			UPDATE " . self::$table_params_ref . "
			SET eorp_x = x(AsEWKT(Transform(GeomFromEWKT(geomfromtext('POINT('||eorp_longitude::text||' '||eorp_latitude::text||')', 4326)), $_srid))),
			eorp_y = y(AsEWKT(Transform(GeomFromEWKT(geomfromtext('POINT('||eorp_longitude::text||' '||eorp_latitude::text||')', 4326)), $_srid)))
			WHERE eorp_longitude IS NOT NULL;
			";
        // }
        // elseif ( $gps == true && $coords == false )
        // {
        // $query = "
        // UPDATE ".self::$table_params_ref."
        // SET eorp_longitude = x(AsEWKT(Transform(GeomFromEWKT(geomfromtext('POINT('||eorp_x||' '||eorp_y||')', (SELECT srid FROM sys_gis_config_global LIMIT 1))), 4326))),
        // eorp_latitude = y(AsEWKT(Transform(GeomFromEWKT(geomfromtext('POINT('||eorp_x||' '||eorp_y||')', (SELECT srid FROM sys_gis_config_global LIMIT 1))), 4326)))
        // WHERE eorp_longitude IS NOT NULL;
        // ";
        // }else{
        // $query = "SELECT true";
        // }

        $this->sql($query);

        if ($this->sql($query) === false) {

            $error = pg_last_error(self::$database_connection);
            $this->demon($error, "ERROR COORDINATES");
            // 15:38 21/08/2009 GHX 
            // Correction du BZ  11144
            // Message d'erreur plus pr�sis
            $msgError = __T("A_E_UPLOAD_TOPOLOGY_COORDINATES_CONVERSION_NOK");
            if (preg_match("/Cannot find SRID/", $error)) {
                $msgError = __T("A_E_UPLOAD_TOPOLOGY_COORDINATES_CONVERSION_CANNOT_FIND_SRID");
                $this->demon("Error => CAS 1 : " . $msgError);
            } elseif (preg_match("/couldn't project point/", $error)) {
                $msgError = __T("A_E_UPLOAD_TOPOLOGY_COORDINATES_CONVERSION_LONGITUDE_LATITUDE_INCORRECT");
                $this->demon("Error => CAS 2 : " . $msgError);
            } elseif (preg_match("/current transaction is aborted/", $error)) {
                $msgError = __T("A_E_UPLOAD_TOPOLOGY_COORDINATES_CONVERSION_NOK") . "***";
                $this->demon("Error => CAS 3 : " . $msgError);
            } else {
                $msgError .= $error;
                $this->demon("Error => CAS default : " . $msgError);
            }
            // ransform: couldn't project point: -45 (geocentric transformation missing z or ellps)
            // Cannot find SRID (32767) in spatial_ref_sys

            $isOk = false;
            $this->sql('ROLLBACK');
            $this->setErrors($msgError);
        } else {

            $this->demon("Pas d'erreur => convertion OK");
        }

        $fin = getmicrotime();

        $i = 0;
        $find = false;
        $nb_queries = count(self::$queries);
        if ($isOk) {
            while ($i <= $nb_queries and ! $find) {

                if (preg_match("/parameters/", self::$queries[$i])) {

                    // $this->sql($query);

                    $query = "UPDATE sys_global_parameters SET value = 1 WHERE parameters ='update_coord_geo'";
                    // 15:19 21/08/2009 GHX
                    // La requete sera ex�cut�e apr�s
                    $this->sql($query);

                    $find = true;
                }
                $i++;
            }
        }

        $this->traceLog("Convertion from GPS coordinates to current projection coordinates", $deb, $fin);

        $this->displayLoadingDebug("Fin Conversion des coordonn�es g�ographuqes ...");
        return $isOk;
    }

// End function convertLatitudeLongitudeToXY

    /**
     * Fonction qui ajoute ou modifie les relations entre les �l�ments r�seau ou 3�me axe dans la table edw_object_arc_ref et leur param�tres dans la table edw_object_ref_parameters
     * 		- Mise � jour de label
     * 		- Reparenting
     * 		- Mise � jour de on_off
     * 		- Mise � jour des coordonn�es g�ographiques
     */
    private function correct() {
        $this->demon('<h3 style="color:#fff;background-color:#0080ff">D�but - Reparenting + mise � jour des param�tres (coordonn�es g�ographiques)</h3>');

        $correct = new TopologyCorrect();

        $this->demon('<h3 style="color:#fff;background-color:#0080ff">Fin - Reparenting + mise � jour des param�tres (coordonn�es g�ographiques)</h3>');
    }

// End function correct()

    /**
     * Archive le fichier upload�
     */
    private function changes() {
        if (self::$mode == 'manuel') {
            $changes = new TopologyChanges();
            //CB 5.3.1 WebService Topology
            //$this->createArchive ( self::$rep_niveau0.'upload/'.self::$file, self::$id_user )
            $changes->setFilenameInArchive($this->createArchive(self::$rep_niveau0 . 'upload/' . self::$file, self::$id_user));
        }
    }

// End function changes()

    /**
     * Fonction qui retourne les changements effectu�s
     * @return array self::$changes : liste des changements effectu�s
     */
    public function getChanges() {
        return self::$changes;
    }

// End function getChanges

    /**
     * Fonction qui permet de charger un fichier de topologie via un upload topology manuel ou auto
     */
    public function load() {
        $file = file(self::$rep_niveau0 . "upload/" . self::$file);

        if (count($file) > 0) {
            $type = "Upload Topology";
            $tdeb = getmicrotime();
            $this->demon('<hr/>');
            $this->demon('<h2 style="color:#fff;background-color:#800000">> > > DEBUT - ' . $type . ' => ' . self::$file . ' (product : ' . self::$product . ')</h2>');

            // Initilisation
            self::$changes = array(); //create a new $changes array each time we load a file
            $this->getTopologyProduct();
            $this->initHeader();
            $this->setFileTmp();
            $this->setFileTmpDb();
            // 16:20 26/11/2008 GHX
            // On convertit le fichier avant les checks pour faire certains checks sur le fichier
            $this->convertFileInDatabaseFormat();

            $this->debug(self::$topology, "topology");

            // V�rification du fichier charg�
            $this->check();

            if (count(self::$errors) == 0) {
                // 01/12/2008 GHX
                $this->createArrayNaLabel();

                $this->addElements();
                $this->correct();

                //  26/11/2008  GHX
                // Ex�cution des requ�tes SQL
                $this->updateObjectRef();

                $this->changes();


                //display modifications in demon
                $changesContent = '';
                for ($i = 0; $i < count(self::$changes); $i++) {
                    $change = self::$changes[$i];
                    $changesContent .= '<tr><td>' . implode('</td><td>', $change) . '</td></tr>';
                }
                $this->demon("<br /><b>Topology modifications:</b><table border='1' style='border-collapse:collapse; font:normal 7pt Verdana, Arial, sans-serif;color:#000000;'><tr>"
                        . "<th>" . __T('A_UPLOAD_TOPOLOGY_TITLE_COL_NETWORK_LEVEL') . "</th>"
                        . "<th>" . __T('A_UPLOAD_TOPOLOGY_TITLE_COL_NETWORK_VALUE') . "</th>"
                        . "<th>" . __T('A_UPLOAD_TOPOLOGY_TITLE_COL_CHANGE_INFO') . "</th>"
                        . "<th>" . __T('A_UPLOAD_TOPOLOGY_TITLE_COL_OLD_VALUE') . "</th>"
                        . "<th>" . __T('A_UPLOAD_TOPOLOGY_TITLE_COL_NEW_VALUE') . "</th>"
                        . "</tr>"
                        . $changesContent
                        . "</table>");
            }

            $this->demon('<h2 style="color:#fff;background-color:#800000">> > > FIN - ' . $type . ' => ' . self::$file . '</h2>');
            $this->demon('<hr>');
        }
    }

// End function load

    /**
     * Nettoie la topologie :
     * Supprime les �l�ment r�seau maximum les plus anciens
     * Si na_max_unique = 1
     * Cr�� dans le cadre de la correction du bug 16042
     * @author BBX
     */
    protected function cleanMaxUniqueLevels() {
        // R�cup�ration des niveaux d'agr�gation concern�s
        $query = "SELECT DISTINCT agregation
            FROM sys_definition_network_agregation
            WHERE na_max_unique = 1";
        $result = $this->sql($query);
        // Pour tous les niveaux max concern�s
        while ($row = pg_fetch_assoc($result)) {
            // On compte le nombre existant
            $queryCount = "SELECT eor_id
                FROM edw_object_ref
                WHERE eor_obj_type = '" . $row['agregation'] . "'";
            $resultCount = $this->sql($queryCount);
            // Si plus de 1 �l�ment
            if (pg_num_rows($resultCount) > 1) {
                // On commence par v�rifier qu'il existe des arcs
                $queryArc = "SELECT DISTINCT eoar_id_parent
                    FROM edw_object_arc_ref
                    WHERE eoar_arc_type LIKE '%|s|" . $row['agregation'] . "'";
                $resultArc = $this->sql($queryArc);
                // Si on a des arcs, on se base dessus
                if (pg_num_rows($resultArc) > 0) {
                    // On supprime les �l�ments les plus anciens
                    $queryDelete = "DELETE FROM edw_object_ref
                        WHERE eor_id NOT IN
                        (
                            SELECT DISTINCT eoar_id_parent
                            FROM edw_object_arc_ref
                            WHERE eoar_arc_type LIKE '%|s|" . $row['agregation'] . "'
                        )
                        AND eor_obj_type = '" . $row['agregation'] . "'";
                    $this->sql($queryDelete);
                }
                // On se base sur la date pour supprimer les �l�ments en trop restants
                // On supprime les �l�ments les plus anciens
                $queryDelete = "DELETE FROM edw_object_ref
                    WHERE eor_id <>
                    (
                       SELECT eor_id
                       FROM edw_object_ref
                       WHERE eor_obj_type = '" . $row['agregation'] . "'
                       ORDER BY eor_date DESC
                       LIMIT 1
                    )
                    AND eor_obj_type = '" . $row['agregation'] . "'";
                $this->sql($queryDelete);
            }
        }
    }

    /**
     * Fonction qui convertie le fichier au format de la base de donn�es
     * ex : sai;cell_name; rnc_id; node_name;network_name => sai; sai_label;rnc;rnc_label;network; network_label
     */
    private function convertFileInDatabaseFormat() {
        // 15:19 01/12/2008 GHX
        // Supprime les lignes vide du fichier
        $this->cmd("sed -i '/^$/d' " . self::$file_tmp);

        $this->demon(self::$header, "header");
        $header = implode(self::$delimiter, self::$header);
        $header_db = self::$delimiter . $header . self::$delimiter;

        // 11:04 19/11/2008 GHX modif
        // R�cup�re les labels des niveaux d'aggr�gation
        $fields_na_label = array();
        $tmp_fields_na = getNaLabelList('na');
        $tmp_fields_na_axe3 = getNaLabelList('na_axe3');

        // R�cup�re tous les niveaux d'agr�gation du seconde axe
        foreach ($tmp_fields_na as $allna) {
            foreach ($allna as $na => $na_label) {
                $fields_na_label[$na_label] = $na;
                $fields_na_label[$na_label . ' label'] = $na . '_label';
            }
        }
        // R�cup�re tous les niveaux d'agr�gation du troisieme axe
        foreach ($tmp_fields_na_axe3 as $allna) {
            foreach ($allna as $na => $na_label) {
                $fields_na_label[$na_label] = $na;
                $fields_na_label[$na_label . ' label'] = $na . '_label';
            }
        }

        $fields_na_label_flip = array_flip($fields_na_label);
        $this->debug($fields_na_label_flip, "liste des colonnes possible dans l'entete");

        $awk = "awk 'BEGIN { FS=\"" . self::$delimiter . "\"; OFS=\"" . self::$delimiter . "\"}{";

        foreach (self::$header as $key => $column_file) {
            // Id de la colonne pour la commande awk ( la colonne 0 = 1)
            $id_column = $key + 1;

            $replace = array();
            $replace_column = array();
            // 17/05/2011 NSE suppression d'un NOTICE
            if (isset(self::$topology[$column_file]) && count(self::$topology[$column_file]) > 1) {
                $awk .= "$" . $id_column . "=";
            }

            // $this->demon(self::$topology[$column_file],"topo Astellia $column_file");
            // $this->demon($fields_na_label[$column_file],"topo TA $column_file");
            // modif GHX
            // Si la colonne fait partie du tableau on la remplace
            if (array_key_exists($column_file, self::$topology)) {
                if (count(self::$topology[$column_file]) > 1) {
                    foreach (self::$topology[$column_file] as $k => $column_db) {

                        if (!in_array($column_db, self::$header) && !in_array($fields_na_label_flip[$column_db], self::$header)) {
                            // On remplace le nom de la colonne par celui ou ceux de la base
                            $replace[] = $column_db;

                            // Si la colonne du fichier correspond � plusieurs colonnes en base, on modifie le fichier en �clatant la colonne par n colonnes
                            if (count(self::$topology[$column_file]) > 1) {
                                $replace_column[] = '$' . $id_column;
                            }
                        }
                    }
                } else {
                    foreach (self::$topology[$column_file] as $k => $column_db) {
                        // On remplace le nom de la colonne par celui ou ceux de la base
                        $replace[] = $column_db;
                    }
                }
            } elseif (array_key_exists($column_file, $fields_na_label)) { // si c'est le label du niveau d'aggr�gation
                $replace[] = $fields_na_label[$column_file];
                $replace_column[] = '$' . $id_column;
            } else { // sinon on garde ca valeur
                $replace[] = $column_file;
                $replace_column[] = '$' . $id_column;
            }

            // Si la colonne du fichier correspond � plusieurs colonnes en base, on modifie le fichier en �clatant la colonne par n colonnes
            // 17/05/2011 NSE suppression d'un NOTICE
            if (isset(self::$topology[$column_file]) && count(self::$topology[$column_file]) > 1) {
                $awk .= implode('"' . self::$delimiter . '"', $replace_column) . ";";
            }

            // On remplace le nom des colonnes du fichier par ceux des colonnes en base  pour le header
            // maj 09/11/2009 - MPR 
            // D�but correction BZ 12248 - Chargement d'un fichier contenant trx/charge et/ou/sans coords
            $header_db = str_replace(
                    self::$delimiter . $column_file . self::$delimiter, self::$delimiter . implode(self::$delimiter, $replace) . self::$delimiter, $header_db
            );
            // Fin  correction BZ 12248
        }

        self::$header_db = explode(self::$delimiter, substr($header_db, 1, strlen($header_db) - 2));
        self::$header_db = array_map("strtolower", self::$header_db);
        // 16:43 26/11/2008 GHX
        // mise en commentaire de la condition
        // On ex�cute la commande awk si le nombre de colonnes a chang�
        // if(count(self::$header) !== count(self::$header_db))
        // {
        $awk .= "print $0}' " . self::$file_tmp . " > " . self::$file_tmp_db;
        $this->cmd($awk);
        // }

        $this->demon(self::$header_db, "header db");
    }

// End function convertFileInDatabaseFormat

    /**
     * Fonction qui ex�cute toutes les requ�tes SQL - Elle met � jour les tables edw_object_ref, edw_object_ref_parameters et edw_object_arc_ref
     */
    public function updateObjectRef() {
        $this->demon(self::$queries);
        if (count(self::$queries) > 0) {
            $this->sql('BEGIN');
            $isOk = true;

            foreach (self::$queries as $query) {
                if (empty($query))
                    continue;

                if ($this->sql($query) === false) {
                    $isOk = false;
                    $this->sql('ROLLBACK');

                    $this->setErrors(__T('A_E_UPLOAD_TOPO_MAJ_DATA'));
                    break;
                }
            }

            /**
             * 22/06/2010 BBX
             * Nettoyage des niveaux d'agr�gation max
             * BZ 16042
             */
            $this->cleanMaxUniqueLevels();
            // JLG - BZ 45947/20604 - Generate summary and update arcs
            $this->updateChildrenWithUniqueParent();

            // Correction du bug 
            if (in_array(self::$na_min, self::$header_db)) {
                $this->demon("CONVERTION DES COORDONNEES");
                $gps = in_array('y', self::$header_db) && in_array('x', self::$header_db) ? true : false;

                $coords = in_array('longitude', self::$header_db) && in_array('latitude', self::$header_db) ? true : false;

                $isOk = $this->convertLatitudeLongitudeToXY($gps, $coords);
            }

            if ($isOk)
                $this->sql('COMMIT');
        }
    }

// End function updateObjectRef

    /**
     * Fonction qui permet de charger un fichier de topologie lors du Retrieve Int�gration de la topologie via l'int�gration des fichiers de donn�es
     */
    public function loadParserInLoadData() {
        /*
          $type =  'Correction de la topologie (appel Parser)';

          $tdeb = getmicrotime();
          $this->demon('<hr>');
          $this->demon('<h2 style="color:#fff;background-color:#800000">> > > DEBUT - '.$type.'</h2>');
          // On initialise le trace log avec un premier enregistrement regroupant les informations g�n�rales de l'upload
          $this->traceLog("mode : ".$this->mode." / type : ".$type);

          $this->getTopologyProduct();
          $this->initHeader();
          $this->setFileTmp();
          $this->setFileTmpDb();

          self::$header_db = self::$header;
          $this->cmd("cp -p ".self::$file_tmp." ".self::$file_tmp_db);

          // Ajout des nouveaux �l�ments
          $this->addElements();

          // On effectue les corrections sur la topologie
          $this->correct();

          // Ex�cution des requ�tes SQL
          $this->updateObjectRef();
         */
        $this->load();
    }

// End function loadParserInLoadData

    /**
     * Efface les fichiers cr��s lors d'un traitement 
     * Ils ne sont pas effacer en mode d�bug
     */
    private function cleanDirUpload() {
        // 01/12/2008 GHX
        //	- Suppression de tous les fichiers topo cr��s pendant l'upload
        if (self::$debug == 0) {
            // maj 16:54 31/08/2009 MPR : Modification de la suppression des fichiers // rm -f impossible lorsqu'il y a trop de fichiers
            // Correction d'un bug sur la suppresion des fichiers 
            $cmd = "ls " . self::$rep_niveau0 . "/upload/ |grep '\.topo' | awk '{print \"" . self::$rep_niveau0 . "upload/\"$0}' |  xargs rm -f {}";

            $this->cmd($cmd);
        }
    }

// End function cleanDirUpload

    /**
     * Creation du fichier de log dans le meme repertoire que le fichier de topologie $file_topo
     * 	Le fichier de log porte comme extension ".log"
     * 	Contenu : >> la liste des erreurs rencontrees lors d'une mise a jour (controle de coherence)
     * 			OU
     * 		     >> la liste des changements effectues suite a une mise a jour
     *
     * @param string Fichier charg�
     * @return string logFile
     */
    public function createFileLog($file_topo) {
        $changes = new TopologyChanges();
        return $changes->createLogFile($file_topo);
    }

// End function createFileLog

    /**
     * Envoi un courrier a chaque administrateur du systeme
     * lorsqu'un probleme est survenu lors d'une mise a jour de topologie
     * Le fichier de topology ainsi que le fichier de log concerne sont attaches au message
     *
     * @param string $topo_file
     * @param string $log_file
     */
    public function SendMailToAdmin($topo_file, $log_file) {
        $changes = new TopologyChanges();
        $changes->alertAdmin($topo_file, $log_file);
    }

// End SendMailToAdmin

    /**
     * Envoi un courrier a Alerts Recipient
     * lorsqu'un probleme est survenu lors d'une mise a jour de topologie
     * Le fichier de topology ainsi que le fichier de log concerne sont attaches au message
     *
     * @param string $topo_file
     * @param string $log_file
     */
    public function SendMailToAlertsRecipient($topo_file, $log_file) {
        $changes = new TopologyChanges();
        $changes->alertAlertsRecipient($topo_file, $log_file);
    }

// End SendMailToAlertsRecipient

    /**
     * Archive le fichier de log $log_file associe a l'archive de la topo passee en parametre
     *
     * @param string $log_file fichier de log
     * @param string $archive_file fichier d'archive
     */
    public function createLogArchive($log_file, $archive_file) {
        $changes = new TopologyChanges();
        $changes->addLogFileToArchive($log_file, $archive_file);
    }

// End createLogArchive

    /**
     * Fonction qui archive un fichier de topologie
     *
     * @param $file - contient le chemin complet jusqu'au fichier de topologie a archiver
     * @param $id_user - l'id utilisateur ayant effectue la mise a jour
     * 			 dans le cas d'une mise a jour automatique mettre -1
     */
    public function createArchive($file, $id_user) {
        $changes = new TopologyChanges();
        return $changes->addFileToArchive($file, $id_user);
    }

// End function createArchive

    /**
     * Remplace les caract�res sp�ciaux dans le fichier de topo en fonction de r�gles d�finies
     * 
     * @return <String> erreur rencontr�e (si elle n'est pas trait�e plus loin dans le load topo)
     *
     * 16/05/2011 NSE DE Topology characters replacement : Cr�ation 
     */
    public function charactersReplacement() {
        $ret = "";

        // si on n'est pas sur un produit Mixed Kpi
        if (!MixedKpiModel::isMixedKpi()) {

            // fichier upload� par l'utilisateur
            $infile = self::$rep_niveau0 . 'upload/' . (!isset($this->file) || empty($this->file) ? self::$file : $this->file);

            if ($fd = fopen($infile, "r")) {

                // recuperation du header
                $headerLine = fgets($fd);
                $delimiteur = (!isset($this->delimiter) || empty($this->delimiter) ? self::$delimiter : $this->delimiter);

                // si le header contient le d�limiteur
                if (strpos($headerLine, $delimiteur) !== false) {
                    $headers = explode($delimiteur, $headerLine);
                    $this->debug($headers, "Topology : Remplacement des caract�res, headers");

                    $nbCols = count($headers);

                    // tableau contenant pour chaque indice de colonne son type : label, code, other
                    $columnType = array();
                    $tabOther = array('on_off', 'azimuth', 'longitude', 'latitude', 'trx', 'charge');
                    for ($coli = 0; $coli < $nbCols; $coli += 1) {
                        // test si le header contient le postfix 'label'
                        // 09/06/2011 NSE bz 22512 : le header du label peut aussi �tre postfix� par '_label'
                        if (strpos($headers[$coli], " label") !== false || strpos($headers[$coli], "_label") !== false)
                            $columnType[$coli] = 'label';
                        elseif (in_array(rtrim($headers[$coli]), $tabOther))
                            $columnType[$coli] = 'other';
                        else
                            $columnType[$coli] = 'code';
                    }
                    $this->debug($columnType, "Topology : Remplacement des caract�res, colonnes qui sont des labels");

                    // Pr�paration des r�gles de remplacement
                    $replacementRules = new TopologyCharactersReplacementRules((!isset($this->id_prod) || empty($this->id_prod) ? self::$id_prod : $this->id_prod), ProductModel::getIdMaster());
                    try {
                        $replacementRules->loadRules();
                        $replacementRules->checkRules();
                    } catch (Exception $e) {
                        $this->setErrors($e->getMessage());
                        return $e->getMessage();
                    }
                    $replacementRules->IgnoreRules($delimiteur);

                    // String resultat, initialise avec le header
                    $out = $headerLine;

                    // parcourt le reste du fichier
                    while (!feof($fd)) {
                        $line = fgets($fd);

                        if (strpos($headerLine, $delimiteur) !== false) {
                            $newLine = "";
                            // recupere les valeurs de la ligne
                            $values = explode($delimiteur, $line);

                            if (count($values) == $nbCols) {
                                // parcourt les valeures par indice pour comparer a l'indice des colonnes
                                for ($coli = 0; $coli < $nbCols; $coli += 1) {
                                    //remplaces les caract�res d'enclosure au cas ou (sauvegarde excel sur des ")
                                    //$oldValue = $this->removeCsvEnclosureOnValue($values[$coli],$this->csvEnclosureChar);
                                    $oldValue = $values[$coli];
                                    if ($columnType[$coli] == 'label') {
                                        // remplace les caract�res pour labels par des espaces
                                        $newValue = $replacementRules->applyLabelRules($oldValue);
                                    } elseif ($columnType[$coli] == 'code') {
                                        // remplace les caract�res pour code par des underscores
                                        $newValue = $replacementRules->applyCodeRules($oldValue);
                                    } else
                                        $newValue = $oldValue;
                                    // reconstruit la ligne modifi�e
                                    $newLine .= $newValue;
                                    if ($coli < $nbCols - 1) {
                                        $newLine .= $delimiteur;
                                    }
                                }
                            } else if (count($values) > 1) {
                                // si des caract�res ';' sont pr�sent dans les valeures
                                // $ret = "ERROR: unexpected number of delimiter '".$this->delimiter."' in line '".$line;
                                //break;
                                // 09/09/2011 BBX
                                // BZ 23034 : Si le nombre de colonnes est erronn�e
                                // il ne faut rien faire. Ce probl�me sera g�r� par la topologie.
                                $newLine = $line;
                            } else {
                                // ligne vide accept�e
                                $newLine = $line;
                            }
                        } else {
                            // si erreur utilise ligne originale
                            $this->demon("WARNING: unexpected format in line '" . $line . "'");
                            //$ret = "WARNING: unexpected format in line '".$line."'";
                            $newLine = $line;
                        }
                        $out .= $newLine;
                    }
                    // ecriture du fichier de sortie
                    fclose($fd);

                    if ($fd = fopen($infile, "w+")) {
                        fwrite($fd, $out);
                        fclose($fd);
                    } else {
                        $ret = "ERROR: could not override file '" . $infile . "'";
                    }
                } else {
                    //$ret = "ERROR: no headers found in topology file '".$infile."'";
                    fclose($fd);
                }
            } else {
                $ret = "ERROR: Could not open topology file '" . $infile . "'";
            }
        }
        return $ret;
    }

    /**
     * Fonction appel�e automatiquement lors de la destruction de l'object soit ici
     * � la fin du script
     */
    public function __destruct() {
        $this->cleanDirUpload();
        if (self::$debug == 0) {
            // Correction du bug 10340 - MPR : Suppression de la table si elle existe
            $query = "DROP TABLE IF EXISTS " . self::$table_ref . "_tmp";
            $this->sql($query);
        }
    }

// End function __destruct
}

// End class Topology
?>