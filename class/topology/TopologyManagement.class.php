<?php
/**
 *      @cb51102@
 *	17/01/2011 - Copyright Astellia
 *
 *	Composant de base version cb_5.1.1.02
 *
 *	25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
 */
/**
 * Classe qui purge la topologie en fonction d'un nombre d'éléments donné en paramètre
 * 
 * Cette classe est utilisée dans le script key_management qui purge la  topologie en fonction de la licence
 */
class TopologyManagement
{
    protected static $_instance;
    const TABLE_REF = "edw_object_ref";
    const TABLE_ARC = "edw_object_arc_ref";
    const TABLE_PARAMS = "edw_object_ref_parameters";

    /**
     * Constructeur
     * @param integer $id_prod
     */
    protected function __construct($id_prod)
    {
        $this->database = Database::getConnection( $id_prod );
        $this->product = $id_prod;
        $this->system_name = get_sys_global_parameters('system_name',"",$id_prod);

    } // End function __construct()

    /**
     * Singleton
     * @param integer $id_prod
     * @return object
     */
    public static function getInstance( $id_prod = "" )
    {
        $id_prod = (empty($id_prod) ) ? ProductModel::getIdMaster() : $id_prod;
        
        if ( !isset( self::$_instance[$id_prod] ) )
        {
            $c = __CLASS__;
            
            self::$_instance[$id_prod] = new $c($id_prod);
        }
        return self::$_instance[$id_prod];
    } // End  function getInstance()

    /**
     * Fonction de clonage
     */
    protected function __clone(){}

    /**
     * Fonction qui compte le nombre de NE de type NA
     * @param string $_na
     * @return integer
     */
    public function getNbElements($_na )
    {
        
        $this->networkAgregation = $_na;

	// 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
	$query = "
                    SELECT count(eor_id) FROM edw_object_ref
                    WHERE eor_obj_type = '$_na'
                        AND ".NeModel::whereClauseWithoutVirtual()."
                        AND eor_on_off = 1
                 ";

	$nb_elements = $this->database->getOne($query);

	return $nb_elements;

    } // End fuinction getNbElements()

    /**
     * Fonction qui retourne les infos de la clé
     * @return string
     */
    public function getInfoKey()
    {  
            // maj 11/12/2008 - MPR : Appel à la classe Key pour y extraire ses données
            $key_instance = new Key();

            $query = "UPDATE sys_global_parameters set value=0 WHERE parameters='max_na_key_exceeded'";
            $this->database->execute($query);

            $key = get_sys_global_parameters("key");

                // Conversion de la clé
            $key_decript = $key_instance->Decrypt($key);

            // maj 11/12/2008 - MPR : Récupération des données de la clé décryptée
            $tab_key['na']          = $key_instance->getNaKey();
            $tab_key['nb_elements'] = $key_instance->getNbElemsKey();

            return $tab_key;

    } // End function getInfoKey()


    /**
     * Fonction qui nettoie la table edw_object_arc_ref
     * @param <type> $net_min
     * @param <type> $network_aggregation 
     */
    protected function cleanTableObjectArcRef(  )
    {        
        $condition = $this->generateCondition( );

        $query_arc = "DELETE FROM ".self::TABLE_ARC." WHERE $condition";
        
        $this->sqlDisplay(self::TABLE_ARC, $query_arc);
    } // End function cleanTableObjectArcRef()

    /**
     * Fonction qui génère la sous-requête depuis la table de données
     * @param string $main_family
     * @param string $network_aggregation
     * @param string $data_table
     * @param intger $nb_elements_a_eliminer
     * @return string 
     */
    protected function generateSubQueryDataTable($main_family, $network_aggregation, $data_table, $nb_elements_a_eliminer)
    {
            $sub_query = "SELECT eor_id FROM ".self::TABLE_REF." WHERE eor_obj_type = '$network_aggregation'";
			// 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
            // Construction de la sous-requête qui va supprimer les éléments réseau en trop
            // Cas avec 3ème axe
            if( get_axe3( $main_family ) )
            {
                    $separator = get_sys_global_parameters('sep_axe3');

                    //	1 - On supprime dans un premier temps les éléments qui n'ont pas de données
                    //	2 - On supprime les éléments réseau les plus récents
                    //  3 - On supprime les éléments par ordre alphanumérique inverse
                    $sub_query = "
                                    SELECT DISTINCT e.eor_id,
                                            ( CASE WHEN z.day IS NULL THEN (SELECT max(day)+1 FROM {$data_table} ) ELSE z.day END) as date
                                    FROM ".self::TABLE_REF." e LEFT JOIN
                                    (
                                            SELECT {$network_aggregation}, max(day) AS day
                                            FROM {$data_table} t0
                                            GROUP BY t0.{$network_aggregation}
                                    ) z ON (e.eor_id = z.{$network_aggregation})
                                    WHERE e.eor_obj_type = '{$network_aggregation}'
                                                    AND ".NeModel::whereClauseWithoutVirtual('e')."
                                    ORDER BY date DESC, e.eor_id DESC
                                    LIMIT $nb_elements_a_eliminer
                                    ";
            }
            // Cas standard
            else
            {
                    $sub_query = "
                                SELECT e.eor_id,
                                        ( CASE WHEN z.day IS NULL THEN (SELECT max(day)+1 FROM {$data_table} ) ELSE z.day END) as date

                                FROM ".self::TABLE_REF." e LEFT JOIN
                                        (
                                                SELECT t0.{$network_aggregation}, max(day) AS day
                                                FROM {$data_table} t0
                                                GROUP BY t0.{$network_aggregation}
                                        ) z ON (e.eor_id = z.{$network_aggregation})
                                WHERE e.eor_obj_type = '{$network_aggregation}'
                                                AND ".NeModel::whereClauseWithoutVirtual('e')."
                                ORDER BY date DESC, e.eor_id ASC
                                LIMIT $nb_elements_a_eliminer
                    ";

            }
            return $sub_query;
    } // End function generateSubQueryDataTable()

    /**
     * Fonction qui génère la sous-requête depuis la topologie
     * @param string $network_aggregation
     * @return string
     */
    protected function generateSubQueryTopoTable()
    {
        return "SELECT eor_id FROM ".self::TABLE_REF." WHERE eor_obj_type = '$this->networkAgregation'";
    } // End function generateSubQueryTopoTable()

    /**
     * Fonction qui nettoie la table edw_object_ref
     * @param string $main_family
     * @param string $network_aggregation
     * @param string $data_table
     * @param integer $nb_elements_a_eliminer
     * @return array
     */
    protected function cleanTableObjectRef( $data_table, $nb_elements_a_eliminer)
    {
        $sub_query = $this->generateSubQueryDataTable($this->mainFamily, $this->networkAgregation, $data_table, $nb_elements_a_eliminer);
        // Suppresion des éléments dans la table edw_object_ref
        $query = "
                DELETE FROM ".self::TABLE_REF."
                WHERE eor_id IN ( SELECT eor_id FROM ( $sub_query ) selection )
                                AND eor_obj_type = '{$this->networkAgregation}'
                RETURNING *
        ";
        $result = $this->sqlDisplay(self::TABLE_REF, $query, 1);
        
        return $result; 
    } // End functino cleanTableObjectRef()

    /**
     * Fonction qui nettoie la table edw_object_ref_parameters
     */
    protected function cleanTableObjectRefParameters()
    {
            $sub_query = $this->generateSubQueryTopoTable();
            
            $query_params = "DELETE FROM ".self::TABLE_PARAMS." WHERE eorp_id NOT IN ( $sub_query )";
            $this->sqlDisplay(self::TABLE_PARAMS, $query_params);
    } // End function cleanTableObjectRefParameters()

    /**
     * Fonction qui retourne le group table de la famille 
     * @return string
     */
    protected function getEdwGroupTable()
    {
        // Récupère le "edw_group_table" de la famille principale contenu dans la table "sys_defintion_group_table"
        $query = "SELECT edw_group_table FROM sys_definition_group_table WHERE family='".$this->mainFamily."';";
        $result = $this->database->getOne($query);

        if ( $result )
        {
            $edw_group_table = $result;

        } else {

            echo "<b><u>Error :</u> no group table for this family ($this->mainFamily).<br>";
                        echo "Please contact your application administrator.</b>";
            return false;

        }
        return $edw_group_table;
    } // end function getEdwGroupTable()

    /**
     * Fonction qui définie le fichier de log
     * @param string $file
     */
    public function setFileLog( $file )
    {
        if( file_exists( $file ) )
        {
            unlink($file);
        }
        $this->splFile = new SplFileObject($file, "a+");
    } // End function setFileLog()

    /**
     * Fonction qui détermine le mode de log (File ou Demon)
     * @param string $mode
     */
    public function setModeLog($mode)
    {
          $this->modeLog = $mode;
    } // End functino setModeLog

    /**
     * Fonction qui log des messages
     * @param string $msg
     * @param string $title
     */
    public function logMessage( $msg, $title = "normal" )
    {
        if( $this->modeLog == "demon" )
        {
            displayInDemon( $msg, $title );
        }
        elseif( "file" )
        {
            if( $title == "title" )
            {
                $this->splFile->fwrite("<h2>{$msg}</h2>");
            }
            else
            {
                $this->splFile->fwrite($msg);
            }
        }
    } // End function logMessage()
    
    /**
     * Fonction qui récupère l'axe du NA concerné
     * @param string $network_agregation
     * @param string $main_family
     * @return string
     */
    protected function getAxeOfNA()
    {
        $query = "SELECT axe FROM sys_definition_network_agregation
                    WHERE agregation = '{$this->networkAgregation}' AND family = '{$this->mainFamily}' LIMIT 1";
        return $this->database->getOne($query);
    } // End function getAxeOfNA()

    /**
     * Fonction qui récupère la table de données sur lequel on récupère les NE
     * @param string $edw_group_table
     * @return string 
     */
    protected function getTableData()
    {
        $axe = $this->getAxeOfNA();
        
        $na = $this->networkAgregation;
        
        if ( get_axe3( $this->mainFamily ) )
        {
            if( $axe == 3 )
            {
                $na = $this->netMin . "_" . $this->networkAgregation;
            }
            else
            {
                $na.=  '_'.get_network_aggregation_min_axe3_from_family($this->mainFamily);
            }
        }
        
        $data_table = $this->edwGroupTable."_raw_".$na."_day";

        return $data_table;
        
    } // End function getTableData()

    /**
     * function qui affiche et exécute les requêtes qui supprime les NE
     * @param string  $table
     * @param string $query
     * @param boolean $return
     * @return array
     */
    protected function sqlDisplay( $table, $query, $return = false )
    {
        $deb = getmicrotime();
        $result = $this->database->execute($query);
        $fin = getmicrotime();
        if( $this->database->getNumRows() ==  0 && $return )
        {
            return array();
        }
            
        $this->logMessage("<h3>Nettoyage de la table ".$table." :</h3><p style='color:#3399ff'>{$query}</p>");
        $this->logMessage('Temps exécution de la requête : '.round(($fin- $deb), 3).' sec<br />');
        if( $return )
            return $this->database->getQueryResults($result);
    } // End function sqlDisplay()

    /**
     * Fonction qui ajoute un message critical dans le tracelog
     * @param <type> $network_agregation
     * @param <type> $_module 
     */
    protected function addMsgInTracelog( $_module, $message, $first = false )
    {
        // Ajout d'un message dans le TraceLog
        sys_log_ast("Critical", $this->system_name,  $_module, $message, "support_1", "");

        if( $first )
        {
            // mets à jour le flag dans sys_global_parameters qui permet d'identifier que le nombre maximum d'éléments réseaux a été dépassé
            $query = "UPDATE sys_global_parameters set value=1 WHERE parameters='max_na_key_exceeded'";
            $database->execute( $query );
        }
    } // End function addMsgInTracelog()

    /**
     * Fonction qui génère la condition de la requête qui supprime les arcs
     * @param string $net_min
     * @param string $network_aggregation
     * @return string
     */
    protected function generateCondition()
    {
        // Condition la présence des éléments dans edw_object_ref
        $sub_query = $this->generateSubQueryTopoTable();

        if( $this->netMin == $this->networkAgregation )
        {
                $condition = "eoar_id NOT IN ($sub_query ) AND eoar_arc_type ILIKE '$this->networkAgregation%'";
        }
        else
        {
                // On supprime tous les arcs où les éléments réseau supprimés par la clé
                $condition_arc  = " AND eoar_arc_type ILIKE '$this->networkAgregation%'";
                $condition_arc2 = " AND eoar_arc_type ILIKE '%$this->networkAgregation'";

                $condition = "(eoar_id NOT IN ( $sub_query ) $condition_arc )
                    OR (eoar_id_parent NOT IN ( $sub_query ) $condition_arc2 )";
        }

        return $condition;
    } // End function generateCondition()

    /**
     * Fonction qui purge la topologie en fonction d'un nombre d'éléments à supprimer
     * @param string $network_aggregation
     * @param string $net_min
     * @param integer $nb_elements_a_eliminer
     * @param string $main_family
     * @param integer/null $id_prod
     * @param string $_module
     */
    public function cleanObjectRef( $nb_elements_a_eliminer, $main_family, $_module = "" )
    {
        $this->netMin = get_network_aggregation_min_from_family( $main_family );
        
        $this->mainFamily = get_main_family($this->product);

        // classe les elements reseaux du plus recent au plus anciens
        // Récupère le "edw_group_table" de la famille principale contenu dans la table "sys_defintion_group_table"
        $this->edwGroupTable = $this->getEdwGroupTable();

        // Récupère le nom de la table de données associée au NA
        $table_data = $this->getTableData();

        $elem_to_delete = $this->cleanTableObjectRef($table_data, $nb_elements_a_eliminer);
        
        $display = "<h3>Liste des {$network_aggregation} supprimés :</h3>";
        $message = "Maximum number of {$this->networkAgregation} has exceeded";
        $this->logMessage("<p style='color:#f00'>{$message} - Clean of topology tables</p>");
        if( $_module != "" )
            $this->addMsgInTracelog($_module, $message);
        // maj 10/12/2008 : MPR - Ajout du returning pour retourner les éléments supprimés de la requête       
        if( count($elem_to_delete)  > 0 )
        {
            $message = array();
            foreach( $elem_to_delete  as $elem )
            {
                    $network_element_name = $elem['eor_id'];
                    $display.= "<pre>Effacement du " . $network_aggregation . " : " . $network_element_name . "</pre>";
                    if( $_module != "" )
                        $this->addMsgInTracelog($_module, "Deletion of " . $network_aggregation . " " . $network_element_name);
            }

            if( $network_aggregation == $net_min )
            {
                $this->cleanTableObjectRefParameters();
            }
            // Création des conditions à ajouter en fonction du na
            $this->cleanTableObjectArcRef( );
            $this->logMessage($display);
        }
    } // End function cleanObjectRef

}
?>