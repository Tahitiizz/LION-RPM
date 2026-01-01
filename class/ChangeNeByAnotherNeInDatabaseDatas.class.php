<?
/**
 *	@cb5.1.0.x@
 *
 *	05/04/2011 SCT : amélioration de la robustesse
 *
 * 25/05/2011 MMT : DE 3rd AXIS traite la nouvelle colonne sds_na_axe3_list
 */
?>
<?php
/** 
 * Cette classe permet de change le nom d'un élément réseau d'une famille par un autre nom.
 * Les changements a appliquer sont nombreuses
 *	- Changement dans les tables de données
 *	- Changement dans les tables de topologie
 *	- Changement dans les alarmes
 *	- Changement dans divers tables
 *
 * @author SCT
 * @version CB5.0.3.05
 * @since CB5.0.3.05
 */
class ChangeNeByAnotherNeInDatabaseDatas
{
    /**
     * Constante de classe qui permet d'exécuter où non les changements
     * Sens servir uniquement pour les devs
     */
    const APPLY_CHANGE = true;

    /**
     * Identifiant du produit
     * @var int (default null soit le produi courant)
     */
    private $_idProduct = '';

    /**
     * Instance à la base de donnée
     * @var DatabaseConnection
     */
    private $_db = null;

    /**
     * Nom de la famille sur laquelle on veut change le nom d'un élément réseau par un autre
     * @var string
     */
    private $_family = null;

    /**
     * Identifiant de la famille
     * @var int
     */
    private $_idFamily = null;

    /**
     * Axe sur lequel se trouve le niveau d'agrégatoin
     * var int
     */
    private $_axe = null;

    /**
     * True si la famille possède un troisième axe
     * var boolean
     */
    private $_hasAxe3 = false;

    /**
     * Permet de connaître s'il existe plusieurs Na sur l'axe considéré par la modification : cela permet d'affiner les transformations sur les valeurs de Ne lorsqu'il n'y a pas de notion de famille
     * var int
     */
    private $_multiNa = 0;

    /**
      * Nom du niveau d'agrégation sur lequel on travaille
      * var string
      */
    private $_na = null;

    /**
      * Nom de l'ancien élément réseau
      * var string
      */
    private $_oldNe = null;

    /**
      * Nom du nouvel élément réseau
      * var string
      */
    private $_newNe = null;

    /**
      * Nom du nouvel label pour l'élément réseau
      * var string
      */
    private $_newNeLabel = null;

    /**
     * Chemin du fichier de log
     * @var string
     */
    private $_fileLog = null;

    /**
     * Format des logs :
     *	- html
     *	- text
     * var string
     */
    private $_formatLog = 'html';

    /**
     * Force la migration des éléments d'agrégation dans le cas d'un multi-axe
     * var bool
     */
    private $_forceMigration = false;

    /**
     * Permet de savoir si le Ne traité appartient à la famille principale
     * var bool true[famille principale et axe1]/false[tout sauf famille principale et axe1]
     */
    private $_belongMainFamily = false;

    /**
     * Constructeur
     *
     * @param int $idProduct identifiant d'un produit (default null soit le produit courant)
     */
    public function __construct($idProduct = '')
    {
        $this->_idProduct = $idProduct;
        $this->_db = Database::getConnection($idProduct);
    } // End function __construct
	
    /**
     * Spécifie la famille sur laquelle il faut changer le niveau d'agrégation
     *
     * @param string $family nom de la famille
     */
    public function setFamily($family)
    {
        $resultIdFamily = $this->_db->getOne("SELECT rank FROM sys_definition_categorie WHERE family = '{$family}' LIMIT 1");
        if($this->_db->getNumRows() == 0)
            throw new Exception("Family '{$family}' does not exist");
		
        $this->_family = $family;
        $this->_idFamily = $resultIdFamily;
    } // End function setFamily
	
    /**
     * Spécifie le nom du niveau d'agrégation sur lequel on travaille
     *
     * @param string $na : nom du niveau d'agrégation
     */
    public function setNa($na)
    {
        $this->_db->getOne("SELECT axe FROM sys_definition_network_agregation WHERE agregation = '{$na}' AND family = '{$this->_family}'");
        if($this->_db->getNumRows() == 0)
            throw new Exception("Network aggregation '{$na}' does not exist for the family '{$this->_family}'");
        $this->_na = $na;
    } // End function setNa

    /**
     * Spécifié le nom de l'ancien élément réseau à remplacer
     *
     * @param string $oldNe : nom de l'élément réseau
     */
    public function setOldNe($oldNe)
    {
        $this->_oldNe = $oldNe;
    } // End function setOldNe
	
    /**
     * Spécifié le nom du nouvel élément réseau qui remplacera l'ancien. Si aucun label n'est précisé, le label de l'ancien élément réseau
     * sera conservé.
     *
     * @param string $newNe : nom du nouvel élément réseau
     * @param string $newNeLabel : label du nouvel élément réseau (default null)
     */
    public function setNewNe($newNe, $newNeLabel = null)
    {
        $this->_newNe = $newNe;
        if($newNeLabel !== null)
        {
            if(trim($newNeLabel) == '')
                throw new Exception ('The label for the new network element can\t be empty');

            $this->_newNeLabel = trim($newNeLabel);
        }
    } // End function setNewNe
	
    /**
     * Spécifie le nom du fichier de log et le format. Si aucun fichier de log n'est spécifié au log ne sera généré
     *
     * @param string $filename chemin vers le fichier de log
     * @param string $format format de sortie des logs soit "html" ou "text" (default html)
     */
    public function setFileLog($filename, $format = 'html')
    {
        $this->_fileLog = $filename;

        if($format == 'html' || $format = 'text')
            $this->_formatLog = $format;
    } // End function setFileLog
	
    /**
     * Spécifie si on force la migration des éléments réseaux dans les tables de topologie
     *
     * @param bool $etatForce true[on force la migration]/false[on autorise la duplication]
     */
    public function setForceMigration($etatForce = false)
    {
        $this->_forceMigration = $etatForce;
    } // End function setForceMigration

    /**
     * Spécifie si on force l'appartenance à la famille principale du NA pour le NE traité
     *
     * @param bool $mainFamily true[on force l'appartenance à la famille principal]/false[la vérification sera automatiquement effectuée durant l'exécution de la méthode "checkParameters"]
     */
    public function setBelongMainFamily($mainFamily = false)
    {
        $this->_belongMainFamily = $mainFamily;
    } // End function setBelongMainFamily

    /**
     * Applique les changements : c'est à dire remplace l'ancien élément réseau par le nouveau
     *
     */
    public function applyChange()
    {
        try
        {
            $this->log("<br /><hr>--------------------------------------------------------------------------------------------------<br />");
            $this->log("<h1>Change l'élément réseau '{$this->_oldNe}' par '{$this->_newNe} ({$this->_newNeLabel})' pour le niveau d'agrégation {$this->_na} de la famille {$this->_family}</h1><br />");
            /*
                Vérifie le paramétrage
            */
            $this->checkParameters();

            /*
                Initilisation de quelques variables
            */
            $resultAxe = $this->_db->getOne("SELECT axe FROM sys_definition_network_agregation WHERE agregation = '{$this->_na}' AND family = '{$this->_family}'");
            $this->_axe = (empty($resultAxe) ? 1 : 3);
            $this->_hasAxe3 = get_axe3($this->_family, $this->_idProduct);
            $resultatMultiNa = $this->_db->getOne("SELECT axe FROM sys_definition_network_agregation WHERE agregation = '{$this->_na}' AND axe = '".($this->_axe == 1 ? 'null' : '3')."'");
            $this->_multiNa = intval($resultatMultiNa);

				//25/05/2011 MMT : DE 3rd AXIS ajoute log sur existance 3eme axe
				$this->log("<br />axe: ".$this->_axe."<h3></h3>");
            /*
                Applique les changements
            */
            $this->changeDataTables();
            $this->changeTopology();
            $this->changeAlarm();
            $this->changeComment();
            $this->changeSelecteur();
            $this->changeMyNetworkAgregation();
            $this->changeSysGisTopologyVoronoi();
        }
        catch(Exception $e)
        {
            $this->log('<br /><h1 style="color:red">ERROR : '.$e->getMessage().'</h1>');
            echo $e->getMessage();
        }
    } // End function applyChange
	
    /**
     * Vérifie si les changements peuvent être appliqués
     */
    protected function checkParameters()
    {
        /*
            On vérifie si tous les paramètres nécessaires ont été spécifiés
        */
        $noValue = array();

        if($this->_family === null)
            $noValue[] = 'family';
        if($this->_na === null)
            $noValue[] = 'network aggregation';
        if($this->_oldNe === null)
            $noValue[] = 'old network element';
        if($this->_newNe === null)
            $noValue[] = 'new network element';

        if(count($noValue) > 0)
            throw new Exception("The parameters following are not specified : ".implode(', ', $noValue));

        /*
            On vérifie si le niveau d'agrégation existe bien pour la famille spécifiée
        */
        $this->_db->execute("SELECT axe FROM sys_definition_network_agregation WHERE agregation = '{$this->_na}' AND family = '{$this->_family}'");
        if($this->_db->getNumRows() == 0)
            throw new Exception("Network aggregation '{$this->_na}' does not exist for the family '{$this->_family}'");
        /*
         * On vérifie si le NA traité appartient à la famille principale sur axe1
         * La vérification est effectuée seulement si on ne force pas la famille principale
         */
        $this->_db->execute("SELECT * FROM sys_definition_network_agregation AS sdna, sys_definition_categorie AS sdc WHERE sdna.family = sdc.family AND sdc.main_family = 1 AND sdna.axe IS NULL AND sdna.agregation = '{$this->_na}'");
        if($this->_db->getNumRows() != 0 && $this->_belongMainFamily == false)
            $this->setBelongMainFamily(true);
    } // End function checkParameters
	
    /**
     * Applique les changements sur les tables de données
     */
    protected function changeDataTables()
    {
        // Récupère le edw_group_table de la famille
        $edwGroupTable = $this->_db->getOne("SELECT edw_group_table FROM sys_definition_group_table WHERE family = '{$this->_family}'");

        if($this->_db->getNumRows() == 0)
            throw new Exception("edw_group_table not found for the family '{$this->_family}'");

        // Récupère les niveaux temporelles déployés pour la familles
        $tas = $this->_db->getAll("SELECT DISTINCT time_agregation FROM sys_definition_group_table_time WHERE id_group_table = {$this->_idFamily}");

        // Récupère les niveaux temporelles déployés pour la familles
        // Si on n'a pas de troisieme axe
        if(!$this->_hasAxe3)
            $nas = $this->_db->getAll("SELECT DISTINCT network_agregation FROM sys_definition_group_table_network WHERE id_group_table = {$this->_idFamily} AND network_agregation = '{$this->_na}'");
        else // Si on a un troisieme axe
        {
            // Si le niveau d'agrégation est sur le premier axe
            if($this->_axe == 1)
                $nas = $this->_db->getAll("SELECT DISTINCT network_agregation FROM sys_definition_group_table_network WHERE id_group_table = {$this->_idFamily}  AND network_agregation ~ '{$this->_na}_.*'");
            else // Si le niveau d'agrégatoin est sur le troisieme axe
                $nas = $this->_db->getAll("SELECT DISTINCT network_agregation FROM sys_definition_group_table_network WHERE id_group_table = {$this->_idFamily}  AND network_agregation ~ '.*_{$this->_na}'");
        }

        $this->log('<br /><b>Renommage des éléments réseaux dans les tables de données</b>');

        $tablesUpdated = array();
        $this->_db->execute('BEGIN');
        foreach(array('raw', 'kpi') AS $type) // le type RAW et KPI
        {
            foreach($nas AS $na) // le NA concerné par la modification
            {
                $na = $na['network_agregation'];
                foreach($tas AS $ta) // les TA déployés pour la famille
                {
                    $ta = $ta['time_agregation'];

                    // Nom de la table de données
                    $dataTable = sprintf('%s_%s_%s_%s', $edwGroupTable, $type, $na, $ta);
                    $tablesUpdated[] = $dataTable;
                    $this->log('<br /><br />Table : "'.$dataTable.'"');

                    /*
                        Renommage des éléments réseau de la table
                    */
                    $sqlUpdateDataTable = sprintf("UPDATE %s SET %s = '%s' WHERE %s = '%s';", $dataTable, $this->_na, $this->_newNe, $this->_na, $this->_oldNe);
                    $this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlUpdateDataTable.'</span>');
                    if(self::APPLY_CHANGE)
                        $this->_db->execute($sqlUpdateDataTable);
                }
            }
        }
        $this->_db->execute('COMMIT');


        // on renomme l'élément réseau dans la table de niveau minimum de type RAW
        $na_min = $this->_db->getOne("SELECT DISTINCT network_agregation FROM sys_definition_group_table_network WHERE id_group_table = {$this->_idFamily} AND rank = -1 AND data_type = 'raw'");
        $dataTableMin = sprintf('%s_%s_%s_%s', $edwGroupTable, 'raw', $na_min, 'hour');
        if(!in_array($dataTableMin, $tablesUpdated)) // on vérifie que la table de niveau minimum n'a pas déjà été traitée
        {
            $this->log('<br /><br />Table min contient les niveau d\'agrégation concerné : "'.$dataTableMin.'"');
            /*
                Renommage des éléments réseaux
            */
            $sqlUpdateDataTable = sprintf("UPDATE %s SET %s = '%s' WHERE %s = '%s';", $dataTableMin, $this->_na, $this->_newNe, $this->_na, $this->_oldNe);
            $this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlUpdateDataTable.'</span>');
            if(self::APPLY_CHANGE)
                $this->_db->execute($sqlUpdateDataTable);
        }
    } // End function changeDataTables

    /**
     * Change le nom de l'élément réseau dans la topologie
     *
     * CAS SPECIFIQUE :
     *      + Famille 1 (2 NA) : cell -> network
     *      + Famille 2 (2 NA) : sai  -> network
     *      + Famille 3 (2 NA) : pcu  -> network
     * avec pour chaque famille (1, 2 et 3), un élément max unique :
     *      + Famille 1 : network (code Ne; label Ne) = "Network"; "Network Astellia label"
     *      + Famille 2 : network (code Ne; label Ne) = "Net"; "Network Vern label"
     *      + Famille 3 : network (code Ne; label Ne) = "Net"; "Network Vern label"
     * avec un objectif de transformer le network de la Famille 2 vers "Network"; "Network Astellia label", il faut conserver en topologie les éléments réseaux max de la troisième famille
     * Pour cela, on vérifie si le niveau à transformer est utilisé pour d'autres famille. Si c'est le cas, on laissera un exemplaire dans edw_object_ref pour éviter d'écraser la topo de la famille qui conservera son ancienne niveau :
     *      + Famille 1 : network (code Ne; label Ne) = "Network"; "Network Astellia label"
     *      + Famille 2 : network (code Ne; label Ne) = "Network"; "Network Astellia label"
     *      + Famille 3 : network (code Ne; label Ne) = "Net"; "Network Vern label"
     * Dans ce cas, la Famille 3 conserve son network car il est différent de celui de la Famille 1
     */
    protected function changeTopology ()
    {
        $sep_axe3 = get_sys_global_parameters('sep_axe3');
		
        /*
            On vérifie si le nom de l'ancien niveau d'agrégation est unique ou non
        */
        $nbNa = $this->_db->getOne("SELECT count(agregation) FROM sys_definition_network_agregation WHERE agregation = '{$this->_na}'");
		
        $this->log('<br /><br /><b>Changement du niveau d\'agrégation dans les tables de topologies (edw_object_ref, edw_object_arc_ref)</b>');
		
        // Si on a une seule fois le niveau ou on force la migration (= pas de doublon dans le cas de plusieurs éléments réseaux répartis sur plusieurs famille => on doit donc migrer l'ensemble des familles)
        if($nbNa == 1 || $this->_forceMigration)
        {
            /*
                Table edw_object_ref
            */
            $this->log('<br />&nbsp;&nbsp;-> Table edw_object_ref');

            $sqlUpdateEdwObjectRef = sprintf("UPDATE edw_object_ref SET eor_id = '%s', eor_label = '%s' WHERE eor_id = '%s' AND eor_obj_type = '%s';", $this->_newNe, $this->_newNeLabel, $this->_oldNe, $this->_na);
            if($this->_newNeLabel == '')
                $sqlUpdateEdwObjectRef = sprintf("UPDATE edw_object_ref SET eor_id = '%s', eor_label = null WHERE eor_id = '%s' AND eor_obj_type = '%s';", $this->_newNe, $this->_oldNe, $this->_na);
            $this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlUpdateEdwObjectRef.'</span>');
            if(self::APPLY_CHANGE)
                $this->_db->execute($sqlUpdateEdwObjectRef);
			
            /*
                Table edw_object_arc_ref
            */
            $this->log('<br />&nbsp;&nbsp;-> Table edw_object_arc_ref');
			
            $sqlUpdateEdwObjectArcRef = "UPDATE edw_object_arc_ref SET eoar_id = '{$this->_newNe}' WHERE eoar_arc_type LIKE '{$this->_na}{$sep_axe3}%' AND eoar_id = '{$this->_oldNe}'";
            $this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlUpdateEdwObjectArcRef.'</span>');
            if(self::APPLY_CHANGE)
                $this->_db->execute($sqlUpdateEdwObjectArcRef);

            $sqlUpdateEdwObjectArcRef = "UPDATE edw_object_arc_ref SET eoar_id_parent = '{$this->_newNe}' WHERE eoar_arc_type LIKE '%{$sep_axe3}{$this->_na}' AND eoar_id_parent = '{$this->_oldNe}'";
            $this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlUpdateEdwObjectArcRef.'</span>');
            if(self::APPLY_CHANGE)
                $this->_db->execute($sqlUpdateEdwObjectArcRef);

            /*
                Table edw_object_arc (pour la tranquillité lors du prochain retrieve)
            */
            $this->log('<br />&nbsp;&nbsp;-> Table edw_object_arc');

            $sqlUpdateEdwObjectArc = "UPDATE edw_object_arc SET eoa_id = '{$this->_newNe}' WHERE eoa_arc_type LIKE '{$this->_na}{$sep_axe3}%' AND eoa_id = '{$this->_oldNe}'";
            $this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlUpdateEdwObjectArc.'</span>');
            if(self::APPLY_CHANGE)
                $this->_db->execute($sqlUpdateEdwObjectArc);

            $sqlUpdateEdwObjectArc = "UPDATE edw_object_arc SET eoa_id_parent = '{$this->_newNe}' WHERE eoa_arc_type LIKE '%{$sep_axe3}{$this->_na}' AND eoa_id_parent = '{$this->_oldNe}'";
            $this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlUpdateEdwObjectArc.'</span>');
            if(self::APPLY_CHANGE)
                $this->_db->execute($sqlUpdateEdwObjectArc);

            /*
                Table edw_object_ref_parameters (dans le cas où on se trouve sur un NA de la famille principale)
            */
            $this->log('<br />&nbsp;&nbsp;-> Table edw_object_ref_parameters');
            if($this->_belongMainFamily)
            {
                $sqlUpdateEdwObjectRefParameters = "UPDATE edw_object_ref_parameters SET eorp_id = '{$this->_newNe}' WHERE eorp_id = '{$this->_oldNe}'";
                $this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlUpdateEdwObjectRefParameters.'</span>');
                if(self::APPLY_CHANGE)
                    $this->_db->execute($sqlUpdateEdwObjectRefParameters);
            }
        }
        // si le niveau est présent plusieurs fois, on ne prend pas le risque de détruire le fonctionnement de la restitution des GTM : on ajoute le nouvel élément réseau
        else
        {
            /*
                Table edw_object_ref
            */
            $this->log('<br />&nbsp;&nbsp;-> Table edw_object_ref');

            $nbNe = $this->_db->getOne("SELECT count(eor_id) FROM edw_object_ref WHERE eor_obj_type = '{$this->_na}' AND eor_id = '{$this->_oldNe}'");
            if($nbNe > 0)
            {
                // on vérifie la présence du nouvel élément réseau
                $sqlSearchEdwObjectRef = "SELECT count(eor_id) FROM edw_object_ref WHERE eor_obj_type = '{$this->_na}' AND eor_id = '{$this->_newNe}'";
                $nbNewNe = $this->_db->getOne($sqlSearchEdwObjectRef);
                $this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlSearchEdwObjectRef.'</span>');
                if($nbNewNe == 0)
                {
                    $sqlUpdateEdwObjectRef = "INSERT INTO edw_object_ref (eor_date, eor_blacklisted, eor_on_off, eor_obj_type, eor_id, eor_label, eor_id_codeq, eor_color) VALUES ('".date('Ymd', time())."', 0, 1, '{$this->_na}', '{$this->_newNe}', ".($this->_newNeLabel != '' ? "'{$this->_newNeLabel}'" : "null").", null, null);";
                    $this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlUpdateEdwObjectRef.'</span>');
                    if(self::APPLY_CHANGE)
                        $this->_db->execute($sqlUpdateEdwObjectRef);
                }
            }
            /*
                Table edw_object_arc_ref
            */
            $this->log('<br />&nbsp;&nbsp;-> Table edw_object_arc_ref');

            $queryToAdd = array();
            $nbNe = $this->_db->getAll("SELECT CASE WHEN eoar_id = '{$this->_oldNe}' THEN '{$this->_newNe}' ELSE eoar_id END AS eoar_id, CASE WHEN eoar_id_parent = '{$this->_oldNe}' THEN '{$this->_newNe}' ELSE eoar_id_parent END AS eoar_id_parent, eoar_arc_type FROM edw_object_arc_ref WHERE (eoar_arc_type LIKE '{$this->_na}{$sep_axe3}%' OR eoar_arc_type LIKE '%{$sep_axe3}{$this->_na}') AND (eoar_id = '{$this->_oldNe}' OR eoar_id_parent = '{$this->_oldNe}')");
            if(count($nbNe) > 0)
            {
                foreach($nbNe AS $neToInsert)
                {
                    // on vérifie qu'il n'est pas déjà présent
                    $nbNewNe = $this->_db->getOne('SELECT COUNT(*) FROM edw_object_arc_ref WHERE eoar_id = "'.$neToInsert['eoar_id'].'" AND eoar_id_parent = "'.$neToInsert['eoar_id_parent'].'" AND eoar_arc_type = "'.$neToInsert['eoar_arc_type'].'"');
                    if($nbNewNe == 0)
                        $queryToAdd[] = "INSERT INTO edw_object_arc_ref (eoar_id, eoar_id_parent, eoar_arc_type) VALUES ('{$neToInsert['eoar_id']}', '{$neToInsert['eoar_id_parent']}', '{$neToInsert['eoar_arc_type']}');";
                }
            }
            if(count($queryToAdd) > 0)
            {
                foreach($queryToAdd AS $sqlInsertEdwObjectArcRef)
                {
                    $this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlInsertEdwObjectArcRef.'</span>');
                    if(self::APPLY_CHANGE)
                        $this->_db->execute($sqlInsertEdwObjectArcRef);
                }
            }
            unset($queryToAdd);

            /*
                Table edw_object_arc (pour la tranquillité lors du prochain retrieve)
            */
            $this->log('<br />&nbsp;&nbsp;-> Table edw_object_arc');

            $queryToAdd = array();
            $nbNe = $this->_db->getAll("SELECT CASE WHEN eoa_id = '{$this->_oldNe}' THEN '{$this->_newNe}' ELSE eoa_id END AS eoa_id, CASE WHEN eoa_id_parent = '{$this->_oldNe}' THEN '{$this->_newNe}' ELSE eoa_id_parent END AS eoa_id_parent, eoa_arc_type FROM edw_object_arc WHERE (eoa_arc_type LIKE '{$this->_na}{$sep_axe3}%' OR eoa_arc_type LIKE '%{$sep_axe3}{$this->_na}') AND (eoa_id = '{$this->_oldNe}' OR eoa_id_parent = '{$this->_oldNe}')");
            if(count($nbNe) > 0)
            {
                foreach($nbNe AS $neToInsert )
                {
                    // on vérifie qu'il n'est pas déjà présent
                    $nbNewNe = $this->_db->getOne('SELECT COUNT(*) FROM edw_object_arc WHERE eoa_id = "'.$neToInsert['eoa_id'].'" AND eoa_id_parent = "'.$neToInsert['eoa_id_parent'].'" AND eoa_arc_type = "'.$neToInsert['eoa_arc_type'].'"');
                    if($nbNewNe == 0)
                        $queryToAdd[] = "INSERT INTO edw_object_arc (eoa_id, eoa_id_parent, eoa_arc_type) VALUES ('{$neToInsert['eoa_id']}', '{$neToInsert['eoa_id_parent']}', '{$neToInsert['eoa_arc_type']}');";
                }
            }
            if(count($queryToAdd) > 0)
            {
                foreach($queryToAdd AS $sqlInsertEdwObjectArc)
                {
                    $this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlInsertEdwObjectArc.'</span>');
                    if(self::APPLY_CHANGE)
                        $this->_db->execute($sqlInsertEdwObjectArc);
                }
            }
            unset($queryToAdd);
            /*
                Table edw_object_ref_parameters (dans le cas où on se trouve sur un NA de la famille principale)
            */
            $this->log('<br />&nbsp;&nbsp;-> Table edw_object_ref_parameters');
            if($this->_belongMainFamily)
            {
                // on vérifie qu'il n'est pas déjà présent
                $nbOldNe = $this->_db->getOne("SELECT COUNT(*) FROM edw_object_ref_parameters WHERE eorp_id = '{$this->_oldNe}'");
                $nbNewNe = $this->_db->getOne("SELECT COUNT(*) FROM edw_object_ref_parameters WHERE eorp_id = '{$this->_newNe}'");
                if($nbOldNe > 0 && $nbNewNe == 0)
                {
                    $queryToAdd = "INSERT INTO edw_object_ref_parameters SELECT '".$this->_newNe."' AS eorp_id, eorp_delete_counter, eorp_x, eorp_y, eorp_azimuth, eorp_longitude, eorp_latitude, eorp_trx, eorp_charge FROM edw_object_ref_parameters WHERE eorp_id = '".$this->_oldNe."'";
                    $this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$queryToAdd.'</span>');
                    if(self::APPLY_CHANGE)
                        $this->_db->execute($queryToAdd);
                }
            }
        }
    } // End function changeTopology
	
    /**
     * Change l'élément réseau dans les exclusions d'alarmes
     */
    protected function changeAlarm ()
    {
        $this->log('<br /><br /><b>Changement des éléments réseaux définis sur les alarmes</b>');

        if($this->_axe == 1)
        {
            $queryToUpdate = array();
            $alarms = $this->_db->getAll("SELECT DISTINCT(a.alarm_id), b.lst_alarm_compute, b.lst_alarm_interface FROM sys_definition_alarm_static AS a, sys_definition_alarm_network_elements AS b WHERE a.alarm_id = b.id_alarm AND b.type_alarm = 'alarm_static' AND a.family = '{$this->_family}' AND (a.network LIKE ('%_{$this->_na}') OR a.network LIKE ('{$this->_na}_%') OR a.network = '{$this->_na}') AND (b.lst_alarm_compute LIKE ('%{$this->_oldNe}%') OR b.lst_alarm_interface LIKE ('%{$this->_oldNe}%')) UNION SELECT DISTINCT(a.alarm_id), b.lst_alarm_compute, b.lst_alarm_interface FROM sys_definition_alarm_dynamic AS a, sys_definition_alarm_network_elements AS b WHERE a.alarm_id = b.id_alarm AND b.type_alarm = 'alarm_static' AND a.family = '{$this->_family}' AND (a.network LIKE ('%_{$this->_na}') OR a.network LIKE ('{$this->_na}_%') OR a.network = '{$this->_na}') AND (b.lst_alarm_compute LIKE ('%{$this->_oldNe}%') OR b.lst_alarm_interface LIKE ('%{$this->_oldNe}%')) UNION SELECT DISTINCT(a.alarm_id), b.lst_alarm_compute, b.lst_alarm_interface FROM sys_definition_alarm_top_worst AS a, sys_definition_alarm_network_elements AS b WHERE a.alarm_id = b.id_alarm AND b.type_alarm = 'alarm_static' AND a.family = '{$this->_family}' AND (a.network LIKE ('%_{$this->_na}') OR a.network LIKE ('{$this->_na}_%') OR a.network = '{$this->_na}') AND (b.lst_alarm_compute LIKE ('%{$this->_oldNe}%') OR b.lst_alarm_interface LIKE ('%{$this->_oldNe}%'));");
            if(count($alarms) > 0)
            {
                foreach($alarms AS $alarm)
                {
                    $idAlarm = $alarm['alarm_id'];
                    $idAlarmCompute = $alarm['lst_alarm_compute'];
                    $idAlarmInterface = $alarm['lst_alarm_interface'];

                    // recherche où l'élément réseau se cache dans le champ 'lst_alarm_compute'
                    $tableauAlarmCompute = explode('||', $idAlarmCompute);
                    foreach($tableauAlarmCompute AS $tableauIndex => $tableauValeur)
                    {
                        if($tableauValeur == $this->_oldNe)
                                $tableauAlarmCompute[$tableauIndex] = $this->_newNe;
                    }
                    $idAlarmCompute = implode('||', $tableauAlarmCompute);
                    // recherche où l'élément réseau se cache dans le champ 'lst_alarm_interface'
                    $tableauAlarmInterface = explode('|', $idAlarmInterface);
                    foreach($tableauAlarmInterface AS $tableauIndex => $tableauValeur)
                    {
                        list($inchange, $neToModify, $neLabelToModify) = explode('@', $tableauValeur);
                        if($neToModify == $this->_oldNe)
                        {
                            $tableauAlarmInterface[$tableauIndex] = $inchange.'@'.$this->_newNe.'@'.$this->_newNeLabel;
                        }
                    }
                    $idAlarmInterface = implode('|', $tableauAlarmInterface);
                    $queryToUpdate[] = "UPDATE sys_definition_alarm_network_elements SET lst_alarm_compute = '{$idAlarmCompute}', lst_alarm_interface = '{$idAlarmInterface}' WHERE id_alarm = '{$idAlarm}';";
                }

            }
            if(count($queryToUpdate) > 0)
            {
                foreach($queryToUpdate AS $sqlUpdateAlarmExclusionElement)
                {
                    $this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlUpdateAlarmExclusionElement.'</span>');
                    if(self::APPLY_CHANGE)
                        $this->_db->execute($sqlUpdateAlarmExclusionElement);
                }
            }
            unset($queryToUpdate);
        }

        $this->log('<br /><br /><b>Changement des éléments réseaux trouvés sur les alarmes</b>');

        $queryToUpdate = array();
        $alarms = $this->_db->getAll("SELECT DISTINCT(a.alarm_id), b.na_value, b.a3_value FROM sys_definition_alarm_static AS a, edw_alarm AS b WHERE a.alarm_id = b.id_alarm AND a.family = '{$this->_family}' AND (a.network LIKE ('%_{$this->_na}') OR a.network LIKE ('{$this->_na}_%') OR a.network = '{$this->_na}') AND (b.na = '{$this->_na}' OR b.a3 = '{$this->_na}')AND (b.na_value = '{$this->_oldNe}' OR b.a3_value = '{$this->_oldNe}')UNION SELECT DISTINCT(a.alarm_id), b.na_value, b.a3_value FROM sys_definition_alarm_dynamic AS a, edw_alarm AS b WHERE a.alarm_id = b.id_alarm AND a.family = '{$this->_family}' AND (a.network LIKE ('%_{$this->_na}') OR a.network LIKE ('{$this->_na}_%') OR a.network = '{$this->_na}') AND (b.na = '{$this->_na}' OR b.a3 = '{$this->_na}')AND (b.na_value = '{$this->_oldNe}' OR b.a3_value = '{$this->_oldNe}')UNION SELECT DISTINCT(a.alarm_id), b.na_value, b.a3_value FROM sys_definition_alarm_top_worst AS a, edw_alarm AS b WHERE a.alarm_id = b.id_alarm AND a.family = '{$this->_family}' AND (a.network LIKE ('%_{$this->_na}') OR a.network LIKE ('{$this->_na}_%') OR a.network = '{$this->_na}') AND (b.na = '{$this->_na}' OR b.a3 = '{$this->_na}')AND (b.na_value = '{$this->_oldNe}' OR b.a3_value = '{$this->_oldNe}');");
        if(count($alarms) > 0)
        {
            foreach($alarms AS $alarm)
            {
                $idAlarm = $alarm['alarm_id'];
                $idAlarmNaValue = $alarm['na_value'];
                $idAlarmA3Value = $alarm['a3_value'];

                // remplacement de l'élément dans le champ 'na_value'
                if($this->_axe == 1 && $idAlarmNaValue == $this->_oldNe)
                    $idAlarmNaValue = $this->_newNe;
                // remplacement de l'élément dans le champ 'a3_value'
                if($this->_axe == 3 && $idAlarmA3Value == $this->_oldNe)
                    $idAlarmA3Value = $this->_newNe;

                $queryToUpdate[] = "UPDATE edw_alarm SET na_value = '{$idAlarmNaValue}', a3_value = '{$idAlarmA3Value}' WHERE alarm_id = '{$idAlarm}';";
            }

        }
        if(count($queryToUpdate) > 0)
        {
            foreach($queryToUpdate AS $sqlUpdateAlarmExclusionElement)
            {
                $this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlUpdateAlarmExclusionElement.'</span>');
                if(self::APPLY_CHANGE)
                    $this->_db->execute($sqlUpdateAlarmExclusionElement);
            }
        }
        unset($queryToUpdate);
    } // End function changeAlarm
	
    /**
     * Change le niveau d'agrégation dans les commentaires des dashboards
     */
    protected function changeComment()
    {
        // on va dupliquer le commentaire pour éviter de perdre une dépendance sur un GTM ou un dashboard : le GTM n'est plus rattaché à une famille
        $this->log('<br /><br /><b>Changement du niveau d\'agrégation dans les commentaires des dashboards : migration impossible</b>');
        //$sqlComment = "INSERT INTO edw_comment (id_user, id_comment_type, id_priority_type, date_ajout, date_selecteur, trouble_ticket_number, id_elem, type_elem, na, na_value, ta, hn, hn_value, \"family\", libelle_comment, libelle_action) SELECT id_user, id_comment_type, id_priority_type, date_ajout, date_selecteur, trouble_ticket_number, id_elem, type_elem, na, '{$this->_newNe}', ta, hn, hn_value, \"family\", libelle_comment, libelle_action  FROM edw_comment WHERE na = '{$this->_na}' AND na_value = '{$this->_oldNe}' EXCEPT SELECT id_user, id_comment_type, id_priority_type, date_ajout, date_selecteur, trouble_ticket_number, id_elem, type_elem, na, '{$this->_newNe}', ta, hn, hn_value, \"family\", libelle_comment, libelle_action FROM edw_comment WHERE na = '{$this->_na}' AND na_value = '{$this->_newNe}'";
        $this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">Migration impossible : ne peut pas déterminer les éléments réseaux</span>');
        //if(self::APPLY_CHANGE)
        //    $this->_db->execute($sqlComment);
    } // End changeComment


	 /**
	  * 25/05/2011 MMT DE 3rd AXIS traite la nouvelle colonne sds_na_axe3_list
     * Change l'élément réseau dans la table sys_definition_selecteur
     */
	 protected function changeSelecteur()
    {
		 $this->log('<br /><br /><b>Changement d\'élément réseau dans la table sys_definition_selecteur</b>');
		 // set query var depending on axis
		 if($this->_axe == 1){
			 $naCol = "sds_na";
			 $neCol = "sds_na_list";
			 $multiNeFormat = true;
		 } 
		 else if($this->_hasAxe3 && $this->_axe == 3)
		 {
			 $naCol = "sds_na_axe3";
			 // test if sds_na_axe3_list column exist, if yes it means that the 3rd axis evolution is on
			 // sds_na_axe3_list supports multiple ne like sds_na_list
			 $multiNeFormat = $this->_db->columnExists('sys_definition_selecteur','sds_na_axe3_list');
			 if($multiNeFormat){
				 $neCol = "sds_na_axe3_list";
			 } else {
				 $neCol = "sds_na_axe3_element";
				 
			 }
		 } else {
			 $this->log("<br /><br /><b>Configuration non attendue - axe: ".$this->_axe.", hasAxe3: ".$this->_hasAxe3." </b>");
		 }
		 if($neCol){
			 if($multiNeFormat){
				 // replace existing couple na||ne in the column using the Replace function
				 // warning: must not use the na column as in this configuration, NEs can be of superior NAs
				 $oldNeExp = $this->_na."||".$this->_oldNe;
				 $newNeExp = $this->_na."||".$this->_newNe;
				 $query = "UPDATE sys_definition_selecteur SET $neCol = Replace($neCol,'$oldNeExp','$newNeExp')
							  WHERE $neCol like '%$oldNeExp%'";
			 } else {
				 // single ne value to replace
				 $query = "UPDATE sys_definition_selecteur SET $neCol = '".$this->_newNe."'
							 WHERE $naCol = '".$this->_na."'
							 AND $neCol = '".$this->_oldNe."'";
			 }

			 $this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$query.'</span>');
			 if(self::APPLY_CHANGE){
				 $this->_db->execute($query);
			 }
		 }
	 }

    /**
     * Change lélément réseau dans la table my_network_agregation
     *
     * N'est modifiée que si le Na considéré est axe1 et niveau Min
     */
    protected function changeMyNetworkAgregation()
    {
        /*
            On vérifie si le nom de l'ancien niveau d'agrégation est unique ou non
        */
        $this->log('<br /><br /><b>Changement d\'élément réseau dans la table my_network_agregation</b>');

        // Si le Na traité est le min de la famille principal et est axe1
        if($this->_na == get_network_aggregation_min_from_family($this->_family,$this->_idProduct) && $this->_axe == 1)
        {

            $myAgregations = $this->_db->getAll("SELECT id_network_agregation, cell_liste FROM my_network_agregation WHERE family = '{$this->_family}' AND cell_liste LIKE '%{$this->_oldNe}%';");

            $sqlUpdateMyAgregationListe = array();
            if(count($myAgregations) > 0)
            {
                foreach($myAgregations AS $myAgregation)
                    $sqlUpdateMyAgregationListe[] = "UPDATE my_network_agregation SET cell_liste = '".addslashes(str_replace("'".$this->_oldNe."'", "'".$this->_newNe."'", $myAgregation['cell_liste']))."' WHERE id_network_agregation = '{$myAgregation['id_network_agregation']}'";
            }
            if(count($sqlUpdateMyAgregationListe) > 0)
            {
                foreach($sqlUpdateMyAgregationListe AS $sqlUpdateMyAgregation)
                {
                    $this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlUpdateMyAgregation.'</span>');
                    if(self::APPLY_CHANGE)
                        $this->_db->execute($sqlUpdateMyAgregation);
                }
            }
            unset($sqlUpdateMyAgregationListe);
        }
    } // End function changeMyNetworkAgregation

    /**
     * Change l'élément réseau dans la table sys_gis_topology_voronoi
     *
     * N'est modifiée que si le Na considéré est axe1
     */
    protected function changeSysGisTopologyVoronoi()
    {
        $this->log('<br /><br /><b>Changement d\'élément réseau dans la table sys_gis_topology_voronoi</b>');

        // Si le Na traité est axe1
        if($this->_axe == 1)
        {
            $sqlUpdateTopologyGis = "UPDATE sys_gis_topology_voronoi SET na_value = '{$this->_newNe}' WHERE na_value = '{$this->_oldNe}' AND na = '{$this->_na}';";
            $this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlUpdateTopologyGis.'</span>');
            if(self::APPLY_CHANGE)
                $this->_db->execute($sqlUpdateTopologyGis);
        }
    } // End function changeSysGisTopologyVoronoi
	
    /**
     * Ecrit une chaine de caractère dans le fichier de log
     *
     * @param string $str
     */
    protected function log($str)
    {
        if($this->_fileLog !== null)
        {
            // Si le format des logs est du texte on supprime les balises html
            if($this->_formatLog == 'text')
            {
                // Remplace les <br> par un retour à la ligne
                $str = str_replace(array('<br />', '<br>', '<br/>'), "\n", $str);
                // Remplace les &nbsp; par un espace
                $str = str_replace('&nbsp;', ' ', $str);
                $str = strip_tags($str);
            }
            file_put_contents($this->_fileLog, $str, FILE_APPEND);
        }
    } // End function log
	
} // End class ChangeNaByAnotherNaInDatabaseStruture 
?>