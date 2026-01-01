<?php
/**
 * CB 5.3.1 WebService Topology
 */
    require_once( dirname( __FILE__).'/../Database.class.php' );
    require_once( dirname( __FILE__).'/../DataBaseConnection.class.php' );
    require_once( dirname( __FILE__).'/SmbClient.class.php' );
    require_once( dirname( __FILE__).'/TrendingAggregationApi.class.php' );
    require_once( dirname( __FILE__).'/UploadFileLib.php' );
    require_once( dirname( __FILE__)."/../../php/environnement_liens.php");

    //Get all input parameters
    $netbios = $argv[1];
    $userSamba = $argv[2];
    $pdwSamba = $argv[3];
    $repository = $argv[4];
    $file = $argv[5];
    $id_file = $argv[6]; 

    //Build final and temporary repository
    $rep_final = REP_PHYSIQUE_NIVEAU_0 . uploadFileInterface::repTopologyAsm;
    $rep_tmp = $rep_final . 'tmp/';
    if(!file_exists($rep_final)){
        mkdir($rep_final);
        chmod($rep_final, 0777);
    }
    if(!file_exists($rep_tmp)){
        mkdir($rep_tmp);
        chmod($rep_tmp, 0777);
    }

    //Try to get file with smb client
    $smbc = new SmbClient('//'.$netbios.'/'.$repository, $userSamba, $pdwSamba);
    //Bug 34410 - [REC][CB 5.3.1.03][Webservice]UploadFileRequest failed with specials characters
    //$filename = basename($file);
    $filename = getCommandSafeFileName(basename($file));
    $ret = $smbc->get ($file, $rep_tmp . $filename);
    
    //Check if there is been a new another request for the same file during getting file by samba
    $row = selectFileById($id_file, array(uploadFileInterface::sRequestReceived));
    //A request to cancel hasn't been done during getting file by samba
    if($row != ""){
        //Can't getting file  
        if($ret == -1){
            //STATE 2
            updateState($id_file, uploadFileInterface::sCanNotGettingFile);
            displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : " . uploadFileInterface::sCanNotGettingFile, "alert", true); 
        }
        //Samba error during getting file
        else if($ret == 0){
            unlink($rep_tmp . $filename);
            //STATE 3
            updateState($id_file, uploadFileInterface::sSambaError);
            displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : " . uploadFileInterface::sSambaError, "alert", true); 
        }
        //Getting file is OK
        else if($ret == 1){
            rename($rep_tmp . $filename, $rep_final . $filename);
            chmod($rep_final . $filename, 0777);
            //STATE 4
            updateState($id_file, uploadFileInterface::sWaitingForIntegration);
            displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : " . uploadFileInterface::sWaitingForIntegration, "normal", true);
        }
    }
    else{
        displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : A request to cancel is arrived during getting file", "alert", true);
        unlink($rep_tmp . $filename);
    }
?>
