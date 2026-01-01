<?php
/**
 * CB 5.3.1 WebService Topology
 */
    require_once( dirname( __FILE__).'/../Database.class.php' );
    require_once( dirname( __FILE__).'/../DataBaseConnection.class.php' );
    
    function insertFile($f)
    {
        try{
            $db = Database::getConnection();
        }
        catch( Exception $e ){
            return '-1';
        }
        
        //Bug 34115 - [REC][CB 5.3.1.01][Webservice]UploadFileRequest with right parameters is error for the special character in filename
        //Specials characters for sql
        $file = str_replace("'", "''", $f);
        
        $time = date('Y-m-d H:i:s');
        $query = "INSERT INTO sys_file_uploaded_archive( file_name, id_user, uploaded_time, file_type, initial_request_time, file_name_request, last_state) 
            VALUES ('{$file}', '" . uploadFileInterface::sfuaIdUserAsm . "', '{$time}', 'topology', CURRENT_TIMESTAMP, '{$file}', '" . uploadFileInterface::sRequestReceived . "')";

        $result = $db->execute($query);
        displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : " . pg_affected_rows($result) . "=" . $query, "normal", true);
            
        $query = "SELECT id_file FROM sys_file_uploaded_archive 
                    WHERE file_name = '{$file}' AND 
                        id_user = '" . uploadFileInterface::sfuaIdUserAsm . "' AND 
                        uploaded_time = '{$time}' AND 
                        file_type = 'topology' AND 
                        last_state = '" . uploadFileInterface::sRequestReceived . "'
                            ORDER BY initial_request_time DESC LIMIT 1";

        $result = $db->execute($query);
        displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : " . pg_affected_rows($result) . "=" . $query, "normal", true);
        $row = $db->getQueryResults($result, 1);
        return $row['id_file'];
    }
    
    
    function selectFile($f, $states = "array()")
    {                           
        try{
            $db = Database::getConnection();
        }
        catch( Exception $e ){
            return '-1';
        }
        
        //Bug 34115 - [REC][CB 5.3.1.01][Webservice]UploadFileRequest with right parameters is error for the special character in filename
        //Specials characters for sql
        $file = str_replace("'", "''", $f);
        
        $query = "SELECT id_file, last_state FROM sys_file_uploaded_archive 
                    WHERE file_name_request LIKE '{$file}' AND
                        (is_cancelled IS NULL OR is_cancelled != 'true') AND
                        id_user = '" . uploadFileInterface::sfuaIdUserAsm . "'";
        
        $cpt=0;
        foreach($states as $state){
            if($cpt == 0)
                $query .= " AND (last_state = '$state'";
            else 
                $query .= " OR last_state = '$state'";
            $cpt++;
            if($cpt == count($states))
                $query .= ")";
        }
        
        $query .= " ORDER BY initial_request_time DESC LIMIT 1";

        $result = $db->execute($query);
        displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : " . pg_affected_rows($result) . "=" . $query, "normal", true);
        $row = $db->getQueryResults($result, 1);
        return $row;
    }
    
    
    function selectFileById($id_file, $states = "array()")
    {            
        try{
            $db = Database::getConnection();
        }
        catch( Exception $e ){
            return '-1';
        }     
        
        $query = "SELECT last_state FROM sys_file_uploaded_archive 
                    WHERE id_file = '{$id_file}' AND
                    (is_cancelled IS NULL OR is_cancelled != 'true') AND 
                    id_user = '" . uploadFileInterface::sfuaIdUserAsm . "'";
        
        $cpt=0;
        foreach($states as $state){
            if($cpt == 0)
                $query .= " AND (last_state = '$state'";
            else 
                $query .= " OR last_state = '$state'";
            $cpt++;
            if($cpt == count($states))
                $query .= ")";
        }

        $result = $db->execute($query);
        displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : " . pg_affected_rows($result) . "=" . $query, "normal", true);
        $row = $db->getQueryResults($result, 1);
        return $row;
    }
    

    function selectAllFiles()
    {            
        try{
            $db = Database::getConnection();
        }
        catch( Exception $e ){
            return '-1';
        }     
        
        $query = "SELECT file_name_request, last_state FROM sys_file_uploaded_archive 
                    WHERE (is_cancelled IS NULL OR is_cancelled != 'true') AND 
                        id_user = '" . uploadFileInterface::sfuaIdUserAsm . "'
                    ORDER BY initial_request_time DESC";

        $result = $db->execute($query);
        displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : " . pg_affected_rows($result) . "=" . $query, "normal", true);
        $row = $db->getQueryResults($result);

        $res = array();
        foreach($row as $r){
            $res[] = $r["file_name_request"] . "|" . $r["last_state"];
        }

        return $res;
    }
    
    
    function updateState($id_file, $state)
    {
        try{
            $db = Database::getConnection();
        }
        catch( Exception $e ){
            return '-1';
        }
        $query = "UPDATE sys_file_uploaded_archive SET 
                        uploaded_time='" . date('Y-m-d H:i:s') . "', 
                        last_state = '{$state}' 
                            WHERE id_file = '{$id_file}'";

        $result = $db->execute($query);
        displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : " . pg_affected_rows($result) . "=" . $query, "normal", true);
    }
    
    
    function updateStateAndName($id_file, $name, $uploadedTime, $state)
    {
        try{
            $db = Database::getConnection();
        }
        catch( Exception $e ){
            return '-1';
        }
        $query = "UPDATE sys_file_uploaded_archive SET 
                        file_name = '{$name}', 
                        uploaded_time = '{$uploadedTime}', 
                        last_state = '{$state}' 
                            WHERE id_file = '{$id_file}'";

        $result = $db->execute($query);
        displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : " . pg_affected_rows($result) . "=" . $query, "normal", true);
    }
    
    
    function cancelFile($id_file)
    {
        try{
            $db = Database::getConnection();
        }
        catch( Exception $e ){
            return '-1';
        }
        $query = "UPDATE sys_file_uploaded_archive SET 
                        is_cancelled='true'
                            WHERE id_file = '{$id_file}'";

        $result = $db->execute($query);
        displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : " . pg_affected_rows($result) . "=" . $query, "normal", true);
    }
?>
