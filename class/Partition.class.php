<?php
/* 
 *	@cb51400@
 *      @author BBX / MPR
 *	17-03-2011 - Copyright Astellia
 *      Evolution T&A : Partionning
 *
 */

/**
 *      Classe qui permet de manipuler une partition
 */
class Partition
{

    /**
     * Nom de la table mère
     * @var string $dataTable
     */
    protected $_dataTable;

    /**
     * Date (YYYYMMDDHH, YYYYMMDD, YYYYWW, YYYYMM)
     * @var integer $date
     */
    protected $_date;

    /**
     * Agrégation Temporelle
     * @var string $ta
     */
    protected $_ta;

    /**
     * Connexion à la base de données
     * @var DataBaseConnection $database
     */
    protected $_database;

    /**
     * Niveau d'agrégation
     * @var string
     */
    protected $_na;

    /**
     * Niveau d'agrégation 3ème axe
     * @var string
     */
    protected $_na3;
    
    /**
     * Constructeur
     * @param string $dataTable : Nom de la table mère
     * @param integer $date : Date (YYYYMMDDHH, YYYYMMDD, YYYYWW, YYYYMM)
     * @param DataBaseConnection $database : Connexion à la base de données
     */
    public function __construct( $dataTable, $date, DataBaseConnection $database, $existing_tables=array() )
    {
        // 12/06/2012 NSE bz 27382 : les fichiers day sont intégrés dans les tables hour. Il faut indiquer l'heure (23).
        // Si on est en partitionné et que le tableau des tables est renseigné
        if($database->isPartitioned() && !empty($existing_tables)){
            // si on est sur une table hour et que, dans la date, l'heure n'est pas spécifiée
            if(substr($dataTable, strlen($dataTable)-4) == 'hour' && strlen($date)<10) {
                $tmpTableTarget = $dataTable.'_'.$date;
                // en principe, les données sont intégrées sur l'heure 23
                // si la table de l'heure 23 existe, c'est celle-ci qui est utilisée
                if(in_array($tmpTableTarget.'23', $existing_tables)){
                    $date .= '23';
                }
                else{
                    // si ce n'est pas le cas, on cherche l'heure sur laquelle elles le sont
                    $query = "SELECT tablename FROM pg_tables WHERE tablename like '".$dataTable.'_'.$date."__'";
                    $tabhour = $database->getOne($query);
                    if(!empty($tabhour)){
                         $date .= substr($tabhour, strlen($tabhour)-2);
                         displayInDemon("Données day intégrées sur l'heure ".substr($tabhour, strlen($tabhour)-2)." ({$dataTable}_$date)");
                    }
                    else{
                        if(strpos($dataTable, '_raw_'))
                            $tmpEGT = substr($dataTable,0,strpos($dataTable, '_raw_'));
                        else
                            $tmpEGT = substr($dataTable,0,strpos($dataTable, '_kpi_'));
                        $query = "SELECT tablename FROM pg_tables WHERE tablename like '".$tmpEGT .'%'.$date."__'";
                        $tabhour = $database->getOne($query);
                        $date .= substr($tabhour, strlen($tabhour)-2);
                         displayInDemon("Données day intégrées sur l'heure ".substr($tabhour, strlen($tabhour)-2)." ({$dataTable}_$date)", 'alert');
                    }
                }
            }
        }
        $this->_dataTable = $dataTable;
        $this->_date      = $date;
        $this->_database  = $database;

        // Récupération d'informations complémentaires (ta, ...)
        $this->parseTable();

    } // End function __construct

    /**
     * Fonction qui retourne le nom de la partition
     * @return string : Nom de la parition
     */
    public function getDataTable()
    {
        return $this->_dataTable;
    } // End function getDataTable()
    
    /**
     * Fonction qui retourne le nom de la partition
     * @return string : Nom de la parition
     */
    public function getName()
    {
        return $this->_dataTable."_".$this->_date;
    } // End function getName()

    /**
     * Fonction qui parse le nom de la table mère pour en tirer des informations (ta, ...)
     */
    protected function parseTable()
    {
        // Fetching infos
        $tableInfos = explode('_', $this->_dataTable );
        $groupTable = $tableInfos[0].'_'.$tableInfos[1].'_'.$tableInfos[2];
        $dataType = $tableInfos[4];
        $this->_na = $tableInfos[5];
        $this->_na3 = $tableInfos[6];
        $this->_ta = isset($tableInfos[7]) ? $tableInfos[7] : '';

        if( isset( $tableInfos[8] ) && ( $tableInfos[8] == 'bh' ) )
            $this->_ta .= '_bh';

        $timeAgregations = array("hour","day","day_bh","week","week_bh","month","month_bh");
        if( in_array( $tableInfos[6],$timeAgregations ) )
        {
            $this->_ta = $tableInfos[6];
            if( isset( $tableInfos[7] ) && ( $tableInfos[7] == 'bh') )
            $this->_ta .= '_bh';
            $this->_na3 = '';
        }
    } // End function parseTable()

    /**
     * Fonction qui détermine si une table existe ou non
     * @param string $table : Table à tester
     * @return boolean : true si elle existe / false si elle n'existe pas
     */
    protected function tableExists( $table )
    {
        $query = "SELECT relname FROM pg_class WHERE relname = '{$table}'";
        $result = $this->_database->execute($query);

        if( $this->_database->getNumRows() > 0 )
        {
            return true;
        }
        else
        {
            return false;
        }
    } // End function tableExists()

    /**
     * Fonction qui défini les contraintes d'une table partition en fonction de la TA
     * @return array[string] $constraints : Tableau contenant les contraintes de la table partition
     */
    protected function getConstraints()
    {
        $constraints = array();
        
        switch( $this->_ta)
        {
            // En mode hour : contraintes sur hour, day
            case 'hour':
                $constraints[] = "CONSTRAINT ".uniqid("cst_")."_{$this->_date}_hour_check CHECK (\"hour\" = {$this->_date})";
                $constraints[] = "CONSTRAINT ".uniqid("cst_")."_{$this->_date}_day_check CHECK (\"day\" = ".substr($this->_date,0,8).")";
            break;
            // En mode day/day_bh : contraintes sur day[bh], week[bh], month[bh]
            // Week et month sont rajoutés pour le calcul des tables week/month
            case 'day':
            case 'day_bh':
                $suffix = ($this->_ta == 'day_bh') ? "_bh": "";

                $constraints[] = "CONSTRAINT ".uniqid("cst_")."_{$this->_date}_{$this->_ta}_check CHECK (\"{$this->_ta}\" = {$this->_date})";
                $constraints[] = "CONSTRAINT ".uniqid("cst_")."_{$this->_date}_week_check CHECK (\"week{$suffix}\" = ".Date::getWeek( $this->_date ).")";
                $constraints[] = "CONSTRAINT ".uniqid("cst_")."_{$this->_date}_month_check CHECK (\"month{$suffix}\" = ".Date::getMonth( $this->_date ).")";

            break;
            // En mode month/month_bh : contrainte unique sur month/month_bh
            default :
                    $constraints[] = "CONSTRAINT ".uniqid("cst_")."_{$this->_date}_{$this->_ta}_check CHECK (\"{$this->_ta}\" = {$this->_date})";
            break;
        }

        return $constraints;
    } 

    /**
     * Création de la partition + création des index
     * @return boolean : true = Création de la table + index / false = Si une erreur est survenue
     */
    public function create()
    {
        // Création de la table
        $query = "
        CREATE TABLE {$this->getName()}
            (".implode(",", $this->getConstraints() ).")
            INHERITS ({$this->_dataTable})
            WITH (
              OIDS=TRUE
            );
        ";
        $result = $this->_database->execute($query);

        if( !$result )
            return false;

        // Création des index
        if( !$this->createIndexes() )
            return false;

        return true;
    }  // End function create()
    
    /**
     * Retourne un nom d'index inexistant en base de données
     * @return string $indexName : Nom d'index 
     */
    protected function getNewIndexName()
    {
        $indexName = uniqid("ix_")."_".$this->_date;
        
        while( $this->indexExists( $indexName ) )
        {
            $indexName = uniqid("ix_")."_".$this->_date;
        }
        return $indexName;
    } // End function getNewIndexName()

    /**
     * Vérifie l'existence d'un nom d'index
     * @param string $indexName
     * @return boolean : true quand l'index existe / false quand l'index n'existe pas
     */
    protected function indexExists( $indexName )
    {
        $query = "SELECT indexname FROM pg_indexes WHERE indexname='{$indexName}' LIMIT 1";

        $this->_database->execute($query);
        if( $this->_database->getNumRows() > 0 )
        { 
            return true;
        }
        return false;
    } // End function indexExists()
    
    /**
     * Création des indexes sur la partition
     * @param array $indexes[table] = array(index)
     * @return boolean : true si aucune erreur rencontrée / false si une erreur est survenue
     */
    protected function createIndexes()
    {
        // Construction de l'index sur le NA
        $indexName = $this->getNewIndexName();
        $query = "BEGIN;";
        $execCtrl = $this->_database->execute($query);

        $query = "CREATE INDEX {$indexName}
              ON {$this->getName()}
              USING btree
              ({$this->_na});
            ";
        $execCtrl = (!$this->_database->execute($query) ? false : true);

        // Construction de l'index sur le NA 3ème axe
        if( $this->_na3 != "" )
        {
            $indexName = $this->getNewIndexName();
            $query = "CREATE INDEX {$indexName}
                  ON {$this->getName()}
                  USING btree
                  ({$this->_na3});
                ";
            $execCtrl = $execCtrl && (!$this->_database->execute($query) ? false : true);
		

            // Construction de l'index multiple sur les champs NA, NA 3ème axe
            $indexName = $this->getNewIndexName();
            $query = "CREATE INDEX {$indexName}
                  ON {$this->getName()}
                  USING btree
                  ({$this->_na}, {$this->_na3});
                ";
            $execCtrl = $execCtrl && (!$this->_database->execute($query) ? false : true);
        }
        // Test de la variable de contrôle. Si une des requêtes précédente à échouée, on Rollback. Sinon on commit.
        $this->_database->execute($execCtrl ? 'COMMIT' : 'ROLLBACK');
        return $execCtrl;
    } // End Function createIndexes()

    /**
     * Fonction qui vérifie l'existence de la partition
     * @return boolean : true si la table existe / false si elle n'existe pas
     */
    public function exists()
    {
        $query = "SELECT tablename FROM pg_tables WHERE tablename = '{$this->getName()}'";
        $this->_database->execute($query);

        if($this->_database->getNumRows() == 1 )
                return true;
        return false;
    } // End function exists()
    
    /**
     * Fonction qui supprime la partition
     */
    public function drop()
    {
        $result = $this->_database->execute("DROP TABLE IF EXISTS ".$this->getName() );
        if( !$result )
            return false;
        
        return true;
    } // End function drop()

    /**
     * Fonction qui vide la partition
     */
    public function truncate()
    {
        $result = $this->_database->execute("TRUNCATE ".$this->getName() );

        if( !$result )
        {
            return false;
        }
        return true;
    } // End function truncate()

    /**
     * Fonction qui retourne les indexes de la partition
     */
    protected function getIndexes()
    {
        $indexes[$this->_dataTable] = $this->_database->getIndexes( $this->_dataTable );
        if( count($indexes) == 0 )
        {
            // Si aucun index n'est présent sur la table mère, on le récupère sur une des partitions existante
            $query = "
                SELECT tablename
                FROM pg_tables, pg_class c
                WHERE tablename LIKE '{$this->_dataTable}%'
                        AND schemaname = 'public'
                        AND relname = tablename
                        AND tablename <> '{$this->getName()}'
                        AND c.oid IN (SELECT inhrelid FROM pg_inherits)
                LIMIT 1;";
            $result = $this->_database->execute($query);
            while($row = $this->_database->getQueryResults($result,1))
            {
                $tableSrc = $row['tablename'];
            }
            $indexes[$tableSrc] = $this->_database->getIndexes( $tableSrc );
        }
        return $indexes;
    } // End function getIndexes()
    
}
?>
