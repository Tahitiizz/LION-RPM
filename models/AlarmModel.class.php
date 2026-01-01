<?php
/**
* Modèle de données des alarmes
*
* Permet de manipuler les alarmes
* @author BBX
* @version CB 5.1.0.00
* @package Alarmes
* @since CB 5.1.0.00
 * 
 * 12/09/2011 NSE DE Master 5.1 / slave 5.0 compatibility : 
 *          - méthodes getNetworkElementSelection(), getNetworkElements(), resetNetworkElementSelection(), addNetworkElements() gestion si la fonctionalité n'est pas disponible sur le slave
 *          - ajout de la méthode isAlarmModuleWithNeParent()
 * 13/09/2011 NSE DE Master 5.1 / slave 5.0 compatibility : méthode getNetworkElements(), gestion si la fonctionalité n'est pas disponible sur le slave
 * 17/11/2011 ACS BZ 24535 Setup alarm: cannot save mapped Element in alarm
 * 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
 * 19/06/2012 NSE Suppresion d'un Warning
 * 20/09/2012 MMT DE 5.3 Delete Topology - ajout jointure sur edw_object_ref pour ne pas afficher les elements supprimés
 * 27/11/2012 GFS BZ 30718 Static alarm creation form uncompleted
*/
?>
<?php
/**
 * Permet de manipuler les alarmes
 * @package test phpDocumentor
 */
class AlarmModel
{
    /**
    * Stocke l'instance de connexion à la base de données
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var object
    */
    private $database;

    /**
    * Stocke l'i du produit
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var object
    */
    private $product;

    /**
    * Stocke les informations relatives à l'alarme
    * @since CB 5.1.0.00
    * @version CB 5.1.0.00
    * @var array
    */
    private $alarmInfos;

    /**
    * Charge une alarme dans l'objet
    * @since CB 5.1.0.00
    * @param string $idAlarm id de l'alarme
    * @param string $typeAlarm type de l'alarme
    * @param integer $idProd id du produit
    */
    public function __construct($idAlarm,$typeAlarm,$idProd=0,$family='')
    {
        // Connexion à la base de données du produit
        //16/11/2012 MMT bz 30276 ajout gestion top-worst pour DE GIS 3D only 
        if($typeAlarm == "top-worst"){
            $typeAlarm = "top_worst";
        }
        
        $this->database = Database::getConnection($idProd);
        $this->product = $idProd;
        // Récupération de l'alarme
        $query = "SELECT * FROM sys_definition_alarm_".$typeAlarm."
        WHERE alarm_id = '$idAlarm'";
        $this->alarmInfos = $this->database->getRow($query);
        // Test  du résultat
        if(empty($this->alarmInfos))
        {
            // Si nouvelle alarme, valeurs par défaut
            $this->alarmInfos['network'] = get_network_aggregation_min_from_family($family,$idProd);
            $this->alarmInfos['alarm_id'] = generateUniqId("sys_definition_alarm_".$typeAlarm);
            $this->alarmInfos['family'] = $family;
        }
        // Mémorisation du type
        $this->alarmInfos['alarm_type'] = $typeAlarm;
    }

    /**
    * Retourne la valeur d'un champ de l'alarme
    * @since CB 5.1.0.00
    * @param string $key champ recherché
    * @return mixed $return valeur du champ ou false si le champ n'existe pas
    */
    public function getValue($key)
    {
        if(isset($this->alarmInfos[$key]))
            return $this->alarmInfos[$key];
        else return false;
    }

    /**
    * Met à jour une valeur de l'alarme
    * @since CB 5.1.0.00
    * @param string $key champ recherché
    * @param string $val nouvelle valeur
    * @return boolean $execCtrl résultat d'éxécution
    */
    public function setValue($key,$value)
    {
        $execCtrl = true;
        if(isset($this->alarmInfos[$key]))
        {
            // Récupération du type de la colonne
            $fieldType = $this->database->getFieldType('sys_definition_alarm_'.$this->alarmInfos['alarm_type'],$key);
            // Type textes
            $PGStringTypes = Array('character varying','varchar','character','char','text');
            // Mise à jour en base
            $query = "UPDATE sys_definition_alarm_".$this->alarmInfos['alarm_type']."
            SET {$key} = ".((in_array($fieldType,$PGStringTypes)) ? "'".$value."'" : $value)."
            WHERE alarm_id = '{$this->alarmInfos['alarm_id']}'";
            $execCtrl  = (!$this->database->execute($query) ? false : true);
            // Mise à jour dans l'objet
            if($execCtrl) $this->alarmInfos[$key] = $value;
        }
        else {
            $execCtrl = false;
        }
        // Retour booléen
        return $execCtrl;
    }

    /**
    * Retourne la liste des éléments réseau sélectionnés pour l'alarme
    * @since CB 5.1.0.00
    * @return array $networkElementSelection liste des éléments réseau sélectionnés
    */
    public function getNetworkElementSelection()
    {
        // Variable de retour
        $networkElementSelection = array();
        // 12/09/2011 NSE DE Master 5.1 / slave 5.0 compatibility
        // si la fonctionalité est disponible sur le slave
        if($this->isAlarmModuleWithNeParent()){
            // Récupération des éléments réseau pour cette alarme
            // 20/09/2012 MMT DE 5.3 Delete Topology - ajout jointure sur edw_object_ref pour ne pas afficher les elements supprimés
            $query = "SELECT na, na_value
                FROM sys_definition_alarm_network_elements sdane, edw_object_ref
                WHERE alarm_id = '".$this->getValue('alarm_id')."'
                AND type_alarm = '".$this->getValue('alarm_type')."'
                AND (edw_object_ref.eor_id = sdane.na_value AND edw_object_ref.eor_obj_type = sdane.na)";
            $result = $this->database->execute($query);
            // Parcours des éléments réseaux
            while($row = $this->database->getQueryResults($result,1)) {
                $networkElementSelection[$row['na']][] = $row['na_value'];
            }
        }
        else{
            $query = "SELECT *
                        FROM sys_definition_alarm_network_elements
                        WHERE id_alarm = '".$this->getValue('alarm_id')."'
                        AND type_alarm = 'alarm_".$this->getValue('alarm_type')."'
                        "; 
            $result = $this->database->execute($query);
            $row = $this->database->getQueryResults($result,1);
            // 19/06/2012 NSE Suppresion d'un Warning: pg_query() Query failed: ERROR
            if($this->database->getNumRows()>0 && $row['lst_alarm_compute'] != 'all')
            {
               // Récupération du Network Level
               $queryNL = "SELECT network FROM sys_definition_".$row['type_alarm']."
                        WHERE alarm_id = '".$row['id_alarm']."'";
               $networkLevel = $this->database->getOne($queryNL);
               // Récupération de la sélection
               // 17/11/2011 ACS BZ 24535 retrieve mapped elements
               $subQueryOperator = ($row['not_in'] == 1) ? 'NOT IN' : 'IN';
               $subQueryElements = explode('||',trim($row['lst_alarm_compute']));
               $queryFetch = "SELECT eor_id, eor_id_codeq
                        FROM edw_object_ref
                        WHERE eor_obj_type='".$networkLevel."' 
                         AND ((eor_id ".$subQueryOperator." ('".implode("','",$subQueryElements)."') AND eor_id_codeq IS NULL)
                         		OR eor_id_codeq ".$subQueryOperator." ('".implode("','",$subQueryElements)."')) 
                        AND eor_on_off = 1";
               $resultNE = $this->database->execute($queryFetch);
               while ($rowNE = $this->database->getQueryResults($resultNE,1)) {
               		if ($rowNE['eor_id_codeq'] != '') {
						$networkElementSelection[$networkLevel][] = $rowNE['eor_id_codeq'];
               		}
               		else {
						$networkElementSelection[$networkLevel][] = $rowNE['eor_id'];
               		}
               }
            }
        }
        // Retour des éléments
        return $networkElementSelection;
    }

    /**
    * Retourne la liste des éléments réseau enfants à l'élément réseau passé en paramètre
    * @since CB 5.1.0.00
    * @param string $na network level
    * @param array $networkElements éléments réseaux
    * @return array $childElements liste des éléments réseau sélectionnés
    */
    public function getChildElements($na,$networkElements)
    {
        // Tableau de retour
        $childElements = array();
        // Récupération des chemins d'agrégations
        $agregationReference = getPathNetworkAggregation($this->product,'no',1);
        // Récupération de tous les enfants par récurcion
        if(isset($agregationReference[$na]))
        {
            foreach($agregationReference[$na] as $childLevel)
            {
                if($childLevel != $na)
                {
                    if(count($networkElements) > 0)
                    {
                        $query = "SELECT r.eor_id
                            FROM edw_object_ref r, edw_object_arc_ref a
                            WHERE r.eor_id = a.eoar_id
                            AND r.eor_on_off = 1
                            AND a.eoar_arc_type = '$childLevel|s|$na'
                            AND a.eoar_id_parent IN ('".implode("','",$networkElements)."')";
                        $result = $this->database->execute($query);
                        while($row = $this->database->getQueryResults($result,1))
                            $childElements[$childLevel][] = $row['eor_id'];
                        $childElements = array_merge($childElements,$this->getChildElements($childLevel,$childElements[$childLevel]));
                    }
                }
            }
        }
        // Retour des enfants
        return $childElements;
    }

    /**
    * Retourne la liste des parents dans la sélection passées en paramètre
    * @since CB 5.1.0.00
    * @param string $na niveau d'agrégation
    * @param array $networkElements liste des éléments réseau enfants
    * @return array $parentsElements liste des éléments réseau parents
    */
    public function getParentElements($na,$networkElements)
    {
        // Tableau de retour
        $parentsElements = array();
        // Récupération des chemins d'agrégations
        $agregationReference = getPathNetworkAggregation($this->product,'no',1,true);
        // Récupération de tous les enfants par récurcion
        if(isset($agregationReference[$na]))
        {
            foreach($agregationReference[$na] as $parentLevel)
            {
                if($parentLevel != $na)
                {
                    if(count($networkElements) > 0)
                    {
                        $query = "SELECT DISTINCT a.eoar_id_parent
                            FROM edw_object_ref r, edw_object_arc_ref a
                            WHERE r.eor_id = a.eoar_id
                            AND r.eor_on_off = 1
                            AND a.eoar_arc_type = '$na|s|$parentLevel'
                            AND r.eor_id IN ('".implode("','",$networkElements)."')";
                        $result = $this->database->execute($query);
                        while($row = $this->database->getQueryResults($result,1))
                            $parentsElements[$parentLevel][] = $row['eoar_id_parent'];
                        $parentsElements = array_merge($parentsElements,$this->getParentElements($parentLevel,$parentsElements[$parentLevel]));
                    }
                }
            }
        }
        // Retour des enfants
        return $parentsElements;
    }

    /**
    * Retourne la liste des éléments réseau à cocher
    * @since CB 5.1.0.00
    * @param string $na niveau d'agrégation à récupérer
    * @return array $networkElementSelection liste des éléments réseau sélectionnés
    */
    public function getNetworkElements($na)
    {
        // Configuration des chemins d'agrégation
        $na2na = $this->getNa2Na();
        // Va contenir les éléments réseau "cochés"
        $networkElementSelection = array();
        // Récupération de la sélection réelle (avec parents)
        $realNeSelection = $this->getNetworkElementSelection();
        // Parcours des éléments réseaux
        foreach($realNeSelection as $realNA => $realNaValues)
        {
            // S'il s'ait du niveau courant, on lé récupère directement
            if($realNA == $na)
            {
                $networkElementSelection = array_merge($networkElementSelection, $realNaValues);
            }
            // Sinon on récupère ses enfants si ce niveau est un parent
            // 13/09/2011 NSE DE Master 5.1 / slave 5.0 compatibility
            // et si la fonctionalité est disponible sur le slave
            elseif($this->isAlarmModuleWithNeParent() && in_array($realNA,$na2na[$na]))
            {
                $childElements = $this->getChildElements($realNA,$realNaValues);
                if(count($childElements[$na]) > 0)
                    $networkElementSelection = array_merge($networkElementSelection,$childElements[$na]);
            }
        }
        // On dédoublonne notre tableau d'éléments réseaux
        // On a alors un tableau contenant tous les éléments à cocher :)
        $networkElementSelection = array_unique($networkElementSelection);
        return $networkElementSelection;
    }

    /**
    * Permet de savoir si la topologie est vide
    * @since CB 5.1.0.00
    * @return boolean $topologyEmpty true si la topologie est vide
    */
    public function isTopologyEmpty()
    {
        $query = "SELECT eor_id FROM edw_object_ref WHERE eor_on_off=1";
        $this->database->execute($query);
        return ($this->database->getNumRows() == 0);
    }

    /**
    * Retourne la liste des éléments réseau existants
    * @since CB 5.1.0.00
    * @param string $na niveau d'agrégation à récupérer
    * @return array $networkElements liste des éléments réseau sélectionnés
    */
    public function getTopologyElements($na)
    {
        // Début de la transaction
        $this->database->execute("BEGIN");
        // 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
        // Définition des requêtes qui récupèrent les éléments réseau
        // Requête master, on récupère l'id et le label
        $query_master = "
                SELECT DISTINCT eor_id,
                        CASE WHEN eor_label IS NULL THEN '('||eor_id||')' ELSE eor_label END AS eor_label
                FROM edw_object_ref
                WHERE eor_id IS NOT NULL
                        AND	eor_obj_type = '".$na."'
                        AND	eor_on_off=1
                        AND ".NeModel::whereClauseWithoutVirtual()."
                ORDER BY eor_label
        ";
        // Requête par défaut, on récupère l'id, le label et l'id équivalent
        $query_default = "
                SELECT DISTINCT eor_id,
                        CASE WHEN eor_label IS NULL THEN '('||eor_id||')' ELSE eor_label END AS eor_label,
                        eor_id_codeq
                FROM edw_object_ref
                WHERE eor_id IS NOT NULL
                        AND	eor_obj_type = '".$na."'
                        AND	eor_on_off=1
                        AND ".NeModel::whereClauseWithoutVirtual()."
                ORDER BY eor_label
        ";
        // Infos sur le produit maitre
        $array_master_prod = getTopoMasterProduct();
        // Récupération de la liste des éléments du maître
        $database = Database::getConnection($array_master_prod['sdp_id']);
        $array_master_elements = Array();
        $result = $database->execute($query_master);
        while($elem = $database->getQueryResults($result,1)) {
            $array_master_elements[$elem['eor_id']] = $elem['eor_label'];
        }
        // Tableau qui va récupérer les éléments réseau
        $networkElements = Array();
        // Si nous sommes sur la maître, on récupère les éléments sans se poser de question
        if($array_master_prod['sdp_id'] == $this->product) {
            foreach($array_master_elements as $id_elem=>$elem) {
                $networkElements[$id_elem] = $elem;
            }
        }
        // Si nous ne sommes pas sur le produit master et que le master est présent dans la liste des produits,
        // on va gérer le mapping
        else {
            // Connexion au produit
            $database = $this->database;
            $result = $database->execute($query_default);
            while($elem = $database->getQueryResults($result,1)) {
                // On regarde si cet élément est mappé
                if(array_key_exists($elem['eor_id_codeq'],$array_master_elements)) {
                    // Si cet élément est mappé, on le récupère avec son code / label du master
                    $networkElements[$elem['eor_id_codeq']] = $array_master_elements[$elem['eor_id_codeq']];
                }
                else {
                    // Cas standard : l'élément n'est pas mappé. On le récupère avec son code / label du produit en cours
                    $networkElements[$elem['eor_id']] = $elem['eor_label'];
                }
            }
        }
        // Fin de la transaction
        $this->database->execute("COMMIT");
        // Notre liste d'éléments est désormais complète.
        // On va cependant enlever les doublons si par hasard certains ont réussi à se glisser dans le tableau.
        $networkElements = array_unique($networkElements);
        // On trie le tableau afin de conserver l'ordre sur les labels
        asort($networkElements,SORT_STRING);
        // Retour de la sélection
        return $networkElements;
    }

    /**
    * Retourne la liste des niveaux d'agrégation à afficher
    * @since CB 5.1.0.00
    * @return array $networkLevel liste des niveaux d'agrégation à afficher
    */
    public function getNa2Na()
    {
        // Récupération de nos niveaux disponibles
        $nalevels = $this->getNALevels();
        // Récupération des chemins agrégations
        $agreg_array = getPathNetworkAggregation($this->product,'no',1,true);
        // BZ 30718 GFS Static alarm creation form uncompleted
        if (!$agreg_array)
        {
        	$agreg_array = array();
        }
        // On boucle maintenant sur tous les niveaux pour construire notre tableau $na2na
        $na2na = Array();
        foreach($nalevels as $level=>$label)
        {
            // Reset $temp_array
            $temp_array = Array();
            // Agrégation sur ce niveau
            $levels_agregated_on_current_level = getLevelsAgregOnLevel($level,$agreg_array);
            // Intersection entre les niveaux disponibles et les niveaux agrégés afin d'épurer le tableau des niveaux qui n'ont pas de lien avec le niveau courant
            $temp_array = array_intersect(array_keys($nalevels),$levels_agregated_on_current_level);
            // Tri sur les clés
            ksort($temp_array);
            // Sauvegarde du résultat
            $na2na[$level] = $temp_array;
        }
        // Retour du tableau
        return $na2na;
    }

    /**
    * Retourne la liste des niveaux d'agrégation à afficher
    * @since CB 5.1.0.00
    * @return array $networkLevel liste des niveaux d'agrégation à afficher
    */
    public function getNaLevels()
    {
        // Récupération des NA de la famille
        $query = "SELECT agregation,agregation_label
        FROM sys_definition_network_agregation
        WHERE axe IS NULL
        AND family = '".$this->getValue('family')."'
        ORDER BY agregation_level,agregation_rank";
        $result = $this->database->execute($query);

        // Récupération des NA
        $networkLevels = array();
        while($array = $this->database->getQueryResults($result,1)) {
            $networkLevels[$array['agregation']] = $array['agregation_label'];
        }

        // Retour du tableau
        return array_unique($networkLevels);
    }

    /**
    * Efface la sélection des éléments réseaux pour l'alarme
    * @since CB 5.1.0.00
    * @return boolean $execCtrl résultat de l'éxécution du traitement
    */
    public function resetNetworkElementSelection()
    {
        // Variable de contrôle
        $execCtrl = true;
        // Requête de suppression
        // 12/09/2011 NSE DE Master 5.1 / slave 5.0 compatibility
        // si la fonctionalité est disponible sur le slave
        if($this->isAlarmModuleWithNeParent()){
            $query = "DELETE FROM sys_definition_alarm_network_elements
                WHERE alarm_id = '".$this->alarmInfos['alarm_id']."'
                AND type_alarm = '".$this->alarmInfos['alarm_type']."'";
        }
        else{
             $query = "DELETE FROM sys_definition_alarm_network_elements
                        WHERE id_alarm = '".$this->alarmInfos['alarm_id']."'
                        AND type_alarm = 'alarm_".$this->alarmInfos['alarm_type']."'
                        ";
        }
        $execCtrl = $this->database->execute($query) ? true : false;
        // Retour du résultat
        return $execCtrl;
    }

    /**
    * Ajoute une liste d'éléments réseau à l'alarme
    * @since CB 5.1.0.00
    * @param array $networkElementSelection tableau de la sélection des éléments réseau
    * @return boolean $execCtrl résultat de l'éxécution du traitement
    */
    public function addNetworkElements($networkElementSelection)
    {
        // Variable de contrôle
        $execCtrl = true;
        // Création du tableau d'insertion
        $linesToInsert = array();
        // 12/09/2011 NSE DE Master 5.1 / slave 5.0 compatibility
        $moduleWithNeParent = $this->isAlarmModuleWithNeParent();
        foreach($networkElementSelection as $na => $allValues)
        {
            // si la fonctionalité est disponible sur le slave
            if($moduleWithNeParent){
                // Si le niveau fait bien parti des niveaux parents au niveau sélectionné
                foreach($allValues as $naValue)
                    $linesToInsert[] = $this->alarmInfos['alarm_id']."\t".$this->alarmInfos['alarm_type']."\t".$na."\t".$naValue;
            }
            else{
                $lst_compute = $lst_interface = '';
                // on crée la liste des NA
                foreach($allValues as $naValue){
                   $lst_compute .= $naValue.'||';
                   $lst_interface .= $na.'@'. $naValue.'@'.NeModel::getLabel($naValue, $na, $this->product).'|';
                }
                if(substr($lst_compute,strlen($lst_compute)-1)=='|')
                   $lst_compute = substr($lst_compute,0,strlen($lst_compute)-2); 
                if(substr($lst_interface,strlen($lst_interface)-1)=='|')
                   $lst_interface = substr($lst_interface,0,strlen($lst_interface)-1);

                $linesToInsert[] = $this->alarmInfos['alarm_id']."\talarm_".$this->alarmInfos['alarm_type']."\t0\t".$lst_compute."\t".$lst_interface;
            }
        }
        // Insertion des lignes
        $execCtrl = $this->database->setTable('sys_definition_alarm_network_elements',$linesToInsert) ? true : false;
        // Retour booléen
        return $execCtrl;
    }

    /**
    * Récupère les triggers de l'alarme
    * @since CB 5.1.0.00
    * @return array $triggers triggers de l'alarme
    */
    public function getTriggers()
    {
        // Tableau de retour
        $triggers = array();

        // Les top worst n'ont pas de critical_level
        if($this->alarmInfos['alarm_type'] == 'top_worst')
        {
             // Récupération des triggers
            $query = "SELECT alarm_trigger_data_field, alarm_trigger_operand, alarm_trigger_type, alarm_trigger_value
                FROM sys_definition_alarm_".$this->alarmInfos['alarm_type']."
                WHERE alarm_id = '".$this->alarmInfos['alarm_id']."'
                AND alarm_trigger_data_field IS NOT NULL";
            $result = $this->database->execute($query);
            while($row = $this->database->getQueryresults($result,1)) {
                $triggers[''][] = $row;
            }
        }
        else
        {
             // Récupération des triggers
            $query = "SELECT alarm_trigger_data_field, alarm_trigger_operand, alarm_trigger_type, alarm_trigger_value, critical_level
                FROM sys_definition_alarm_".$this->alarmInfos['alarm_type']."
                WHERE alarm_id = '".$this->alarmInfos['alarm_id']."'
                AND alarm_trigger_data_field IS NOT NULL
                AND critical_level IS NOT NULL
                ORDER BY critical_level";
            $result = $this->database->execute($query);
            while($row = $this->database->getQueryresults($result,1)) {
                $triggers[$row['critical_level']][] = $row;
            }
        }

        // Retour des triggers
        return $triggers;
    }

    /**
    * Récupère les champs additionnels de l'alarme
    * @since CB 5.1.0.00
    * @return array $additionnalFields champs additionnels de l'alarme
    */
    public function getAdditionalFields()
    {
        // Tableau de retour
        $additionnalFields = array();

        // Récupération des triggers
        $query = "SELECT additional_field, additional_field_type
            FROM sys_definition_alarm_".$this->alarmInfos['alarm_type']."
            WHERE alarm_id = '".$this->alarmInfos['alarm_id']."'
            AND additional_field IS NOT NULL
            AND additional_field_type IS NOT NULL";
        $result = $this->database->execute($query);
        while($row = $this->database->getQueryresults($result,1)) {
            $additionnalFields[$row['additional_field_type']][] = $row['additional_field'];
        }

        // Retour des triggers
        return $additionnalFields;
    }

    /**
    * Récupère les champs de tri de l'alarme
    * @since CB 5.1.0.00
    * @return array $sortFields champs de tri de l'alarme
    */
    public function getSortField()
    {
        // Tableau de retour
        $sortField = array();

        // Uniquement top worst
        if($this->alarmInfos['alarm_type'] == 'top_worst')
        {
            // Récupération des triggers
            $query = "SELECT list_sort_field, list_sort_asc_desc, list_sort_field_type
                FROM sys_definition_alarm_".$this->alarmInfos['alarm_type']."
                WHERE alarm_id = '".$this->alarmInfos['alarm_id']."'
                AND list_sort_field IS NOT NULL
                AND list_sort_asc_desc IS NOT NULL";
            $result = $this->database->execute($query);
            $sortField = $this->database->getQueryresults($result,1);
        }

        // Retour des triggers
        return $sortField;
    }
    
    /**
    * Récupère le champ de seuil de l'alarme
    * @since CB 5.1.0.00
    * @return array $thresholdField champ de seuil de l'alarme
    */
    public function getThresholdField()
    {
        // Tableau de retour
        $thresholdField = array();

        // Uniquement top worst
        if($this->alarmInfos['alarm_type'] == 'dynamic')
        {
            // Récupération du seuil
            $query = "SELECT alarm_field, alarm_field_type, alarm_threshold, critical_level, alarm_threshold_operand
                FROM sys_definition_alarm_".$this->alarmInfos['alarm_type']."
                WHERE alarm_id = '".$this->alarmInfos['alarm_id']."'
                AND alarm_field IS NOT NULL
                AND critical_level IS NOT NULL
                ORDER BY critical_level";
            $result = $this->database->execute($query);
            while($row = $this->database->getQueryresults($result,1)) {
                $thresholdField[$row['critical_level']][] = $row;
            }
        }

        // Retour des triggers
        return $thresholdField;
    }

    /**
    * Récupère les criticités possibles pour l'alarme
    * @since CB 5.1.0.00
    * @return array $criticities criticités possibles
    */
    public function getCriticities()
    {
        // Tableau de retour
        $criticities = array();

        // Les alarmes Top/Worst n'ont pas de criticité
        if($this->alarmInfos['alarm_type'] != 'top_worst')
        {
            // Récupération des triggers
            $query = "SELECT DISTINCT critical_level
                FROM sys_definition_alarm_".$this->alarmInfos['alarm_type']."
                WHERE alarm_id = '".$this->alarmInfos['alarm_id']."'
                AND critical_level IS NOT NULL
                ORDER BY critical_level";
            $result = $this->database->execute($query);
            while($row = $this->database->getQueryresults($result,1)) {
                $criticities[] = $row['critical_level'];
            }
        }

        // Retour des triggers
        return $criticities;
    }

    /**
    * Récupère les périodes exlues pour l'alarme
    * @since CB 5.1.0.00
    * @return array $excludedPeriods Périodes exclues(day => hours[])
    */
    public function getExcludedPeriods()
    {
        // Tableau de retour
        $excludedPeriods = array();

        // Récupération des périodes
        $query = "SELECT * FROM sys_definition_alarm_exclusion
            WHERE id_alarm = '".$this->alarmInfos['alarm_id']."'
            AND type_alarm = 'alarm_".$this->alarmInfos['alarm_type']."'";
        $result = $this->database->execute($query);
        while($row = $this->database->getQueryresults($result,1)) {
            if($row['id_parent'] == 0)
                $excludedPeriods[$row['id']]['day'] = $row['ta_value'];
            else
                $excludedPeriods[$row['id_parent']]['hour'][] = $row['ta_value'];
        }

        // Retour des triggers
        return $excludedPeriods;
    }

    /**
    * Permet de savoir si l'alarme est itérative ou non
    * @since CB 5.1.0.00
    * @return boolean $isIterative alarme itérative ou non
    */
    public function isIterative()
    {
        // Requête SQL
        $query = "SELECT DISTINCT nb_iteration, period
                FROM sys_definition_alarm_".$this->alarmInfos['alarm_type']."
                WHERE alarm_id = '".$this->alarmInfos['alarm_id']."'
                AND nb_iteration IS NOT NULL
                AND period IS NOT NULL";
        $result = $this->database->execute($query);
        $row = $this->database->getQueryresults($result,1);
        // Retour booléen
        return (($row['nb_iteration'] > 1) && ($row['period'] > 1));
    }

    /**
    * Lances un vacuum analyse sur les tables de conf des alarmes
    * @since CB 5.1.0.00
    * @return boolean $execCtrl résultat de l'éxécution du traitement
    */
    public function vacuum()
    {
        // Variable de contrôle
        $execCtrl = true;
        // Tables à vacummer
        $query = array();
        $query[] = "VACUUM ANALYSE sys_definition_alarm_static";
        $query[] = "VACUUM ANALYSE sys_definition_alarm_dynamic";
        $query[] = "VACUUM ANALYSE sys_definition_alarm_top_worst";
        $query[] = "VACUUM ANALYSE sys_definition_alarm_exclusion";
        $query[] = "VACUUM ANALYSE sys_definition_alarm_network_elements";
        // Exécution
        foreach($query as $vacuum) {
            $execCtrl = $execCtrl && (!$this->database->execute($vacuum) ? false : true);
        }
        // Retour du résultat
        return $execCtrl;
    }

    /**
    * Méthode statique permettant de récupérer un tableau contenant toutes les alarmes existantes et actives,
    * triées par type (static, dynamic, top_worst).
    * @since CB 5.1.0.00
    * @return array $activeAlarms tableau des alarmes
    */
    public static function getAlarms($idProd = 0)
    {
        // Tableau de retour
        $activeAlarms = array();
        // Récupération de l'instance de connexion à la base
        $database = Database::getConnection($idProd);
        // Récupération des Alarmes
        foreach(Array('static','dynamic','top_worst') as $alarmType)
        {
            $query = "SELECT DISTINCT alarm_id FROM sys_definition_alarm_{$alarmType}
                WHERE on_off = 1";
            $result = $database->execute($query);
            while($row = $database->getQueryResults($result,1)) {
               $activeAlarms[$alarmType][] = $row['alarm_id'];
            }
        }
        // Retour du résultat
        return $activeAlarms;
    }
    
    /**
     * Indique si le produit utilise la nouvelle version du module Alarme.
     * @return boolean 
     */
    public function isAlarmModuleWithNeParent(){
        // si la colonne spécifique à la nouvelle version du module existe sur le produit considéré
        return $this->database->columnExists( 'sys_definition_alarm_network_elements', 'alarm_id')>0?true:false;
    }
    
}
?>
