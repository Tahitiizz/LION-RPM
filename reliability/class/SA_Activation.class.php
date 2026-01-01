<?php
/**
 *  28/05/2010 - MPR : DE Source Availability - Création de la classe 
 */
?>
<?
/**
 *
 * Classe SA_Activation permettant d'activer Source Availability
 */
class SA_Activation{

    const TABLE_FILES_TYPE = "sys_definition_flat_file_lib";
    const TABLE_CONNEXION  = "sys_definition_sa_file_type_per_connection";

    private $_product;
    private $_params_default = array();
    private $_params_connection = array();
    private $_file_types = array();
    private $_connections = array();
    private $_tab_cnx = array();
    private $_data_chunks_max = array("hour" => "24","day" => "1");
    private $_data_collection_frequency_default = array("hour" => "1","day" => "24");
    
    /**
     * Constructeur de la classe
     */
    public function __construct($idProduct="")
    {
        $this->_product = $idProduct;
        $this->_db = Database::getConnection($idProduct);
    } // End function __construct

    /**
     * Fonction qui active Source Availability
     */
    public function activation()
    {
        $this->initDefaultParameters();
        $this->initConnections();
        
        return $this->update();
    } // End function activation()

    /**
     * Initialisation des paramètres SA par défaut
     */
    private function initDefaultParameters()
    {
        $this->_data_granularity = get_ta_min($this->_product);
        $this->_data_collection_frequency = $this->_data_collection_frequency_default[$this->_data_granularity];
        $this->_data_chunks = $this->_data_chunks_max[$this->_data_granularity];
    } // End function initDefaultParameters()

    /**
     * Récupération des types de fichier
     */
    private function getFileTypes()
    {
        $table  = $this->_db->getTable(self::TABLE_FILES_TYPE);

        if( count($table) > 0 )
        {
            foreach( $table as $row )
            {
                // On exclue les fichiers ZIP
                if( !strstr($row,".zip") ){
                    $temp = explode("\t",$row);
                    $this->_file_types[] = $temp[0];
                }
            }
        }
    } // End function getFileTypes()

    /**
     * Initialisation des Connexions
     */
    private function initConnections()
    {
        // On récupère uniquement les connexions qui ne sont encore pas paramétrées
        $_condition = "
                        WHERE id_connection NOT IN (
                                SELECT sdsftpc_id_connection
                                FROM ".self::TABLE_CONNEXION."
                        )";
        $this->_connections = ConnectionModel::getAllConnections($this->_product, $_condition);
        
        // Récupération des types de fichier
        $this->getFileTypes();
        
        if( count($this->_connections) > 0 && count( $this->_file_types ) > 0 )
        {
                // On boucle sur tous les types de fichier
                foreach( $this->_file_types as $id_file_type )
                {
                        // On boucle sur toutes les connexions
                        foreach( $this->_connections as $id_cnx )
                        {
                            $this->_tab_cnx[] = "{$id_cnx}\t{$id_file_type}\t{$this->_data_chunks}";
                        }
                }
         }
    } // End function initConnections()

    /**
     * Fonction qui met à jour la base de données
     */
    private function update()
    {
        // Mise à jour du paramétrage SA par défaut
        $query  = " UPDATE ".self::TABLE_FILES_TYPE."
                    SET
                        granularity = '{$this->_data_granularity}',
                        data_chunks = {$this->_data_chunks},
                        data_collection_frequency = {$this->_data_collection_frequency}
                    WHERE
                        granularity IS NULL
                        AND data_chunks IS NULL
                        AND data_collection_frequency IS NULL";

        $this->_db->execute($query);

        $_demon[]= $this->_db->getAffectedRows()." : ".$query."\n";
        $_demon[]= implode("\n",$this->_tab_cnx);
        // Initialisation des connexions avec le paramétrage SA par défaut
        $this->_db->setTable(self::TABLE_CONNEXION, $this->_tab_cnx);

        return $_demon;
    } // End function update()
}
?>
