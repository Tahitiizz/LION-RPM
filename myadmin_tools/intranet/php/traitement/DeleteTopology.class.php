<?php

/**
 * 20/09/2012 MMT DE 5.3 Delete Topology - creation de la classe
 * 24/10/2012 MMT Bz 30031 : pg_dump -d ne fonctionne pas sur PG 9.1 il faut placer le nom de la base à la fin
 *
 * @author m.monfort
 */

include_once(REP_PHYSIQUE_NIVEAU_0.'/php/edw_function.php');

/**
 * Delete topology 
 * Gere tous les traitements lié à la suppression de la topologie
 */
class DeleteTopology {
    //put your code here
    
    protected $tablesToTruncate; // liste des tables à tronquer
    
    protected $idProduct; //produit 
    protected $debug;
    protected $dbConn; //connection à la base produit
    
    protected $backupPath; // chemin du fichier de backup
    
    public function __construct($idProduct){
        $this->idProduct = $idProduct;
        $this->dbConn = DataBase::getConnection($idProduct);
        $this->tablesToTruncate = Array("edw_object",
                                  "edw_object_arc",
                                   "edw_object_arc_ref",
                                   "edw_object_ref",
                                   "edw_object_ref_parameters",
                                   "sys_gis_topology_voronoi");
        $this->backupPath = REP_PHYSIQUE_NIVEAU_0."topology/delete_backup/";
        
        
    }
    
    /**
     * effectue toutes les étapes de la suppresion de topologie
     */
    public function performDeletion(){
        $this->backupTopologyTables();
        $this->deleteAllElementsInDb();
    }

    /**
     * effectue le backup de la topologie
     */
    private function backupTopologyTables() {
        // création du repertoire si n'existe pas
        if(!is_dir($this->backupPath)){
            if(!mkdir($this->backupPath)){
                throw new Exception("Could not create directory'".$this->backupPath."'");
            }
        }
        
        $productsInformations = getProductInformations($this->idProduct);
        $productsInformations = $productsInformations[$this->idProduct];
        
        //definition du fichier de backup
        $backupFileMainName = $this->backupPath."/topo_backup_".$productsInformations["sdp_db_name"]."_".date('YmdHi');
        $backupFile = $backupFileMainName.".sql";
        
        // si le fichier de backup existe on itere sur le nom
        if(is_file($backupFile)){
            $i=2;
            while(is_file($backupFile)){
                $backupFile = $backupFileMainName."_".$i.".sql";
                $i=$i+1;
            }
        }
        
        // 24/10/2012 MMT Bz 30031 : pg_dump -d ne fonctionne pas sur PG 9.1 il faut placer le nom de la base à la fin
        // construction de la commande de backup
        $cmd =  "env PGPASSWORD=".$productsInformations["sdp_db_password"]." pg_dump -c -U ".$productsInformations["sdp_db_login"];
        $cmd .= " -p ".$productsInformations["sdp_db_port"];
        $cmd .= " -h ".$productsInformations["sdp_ip_address"];
        
        foreach ($this->tablesToTruncate as $table){
            $cmd .= " -t $table";
        }
        // 24/10/2012 MMT Bz 30031
        $cmd .= " ".$productsInformations["sdp_db_name"];
        $cmd .= " > $backupFile";
        
        $this->log("Backup command: ".$cmd);
        
        // execution de la commande dans proc_open pour récuperer l'erreur
        // 14/08/2013 GFS - Bug 35367 - [SUP][T&A Gateway][AVP NA][Zain KUWAIT] : delete topology failed
        // 04/11/2013 GFS - Bug 38199 - [REC][CorePS 5.3.1.01][TC#TA-62406][Topology]: The backup file is NOT created after topology delete process is performed 
        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin
            1 => array("pipe", "w"),  // stdout
            2 => array("pipe", "w"),  // stderr
         );
        
        $process = proc_open($cmd, $descriptorspec, $pipes);
        // on recupere l'erreur dans le pipe
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        proc_close($process);
        
        // recuperation de l'erreur si exite
        if(!empty($stderr)){
            throw new Exception("Error while creating the backup: '$stderr'");
        }
    }
    
    /**
     * log du debug dans l'IHM si debug est activé
     * @param type $str 
     */
    private function log($str){
        if($this->debug){
            echo "<br>DEBUG: $str<br>";
        }
    }
    
    /**
     * permet de specifier le niveau de debug "upload_topology" (0 = non, 1 = oui)
     * @param type $debug 
     */
    public function setDebug($debug){
        $this->debug = $debug;
    }
    
    /**
     * supprime toutes les tables de topo et log un message
     * @throws Exception 
     */
    private function deleteAllElementsInDb() {
        
        foreach ($this->tablesToTruncate as $table){
            $sql = "TRUNCATE $table";
            $this->log("Truncate Table ".$table);
            $this->dbConn->executeQuery($sql);
            $err = $this->dbConn->getLastError();
            if(!empty($err)){
                throw new Exception("Error while deleting table $table : '$err'");
            }
            
        }
        sys_log_ast( "Info", get_sys_global_parameters( "system_name" ),__T( "A_TRACELOG_MODULE_LABEL_TOPOLOGY") , __T( "A_TRACELOG_TOPOLOGY_DELETED",$_SESSION['login'] ), "support_1", "",$this->idProduct);
        
        
    }
    
    
    
    
    
    
}

?>
