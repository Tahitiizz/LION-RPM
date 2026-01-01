<?php
/**
 * Script to redirect to Nova Explorer
 * CB 5.3.1 : Link to Nova Explorer
 *
 * 06/05/2015 JLG : mantis 6254 : [DE R&D] Amélioration des perfs des liens vers NEx sur bases CAA
 */
$t1=microtime(true);

session_start();

if ( $repertoire_physique_niveau0 == "" ) {
    $msg_erreur = urlencode("Session time expired.");
    $file = "../../index.php?error=$msg_erreur";
    header("Location:$file");
}

include_once("../../php/environnement_liens.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/database_connection.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "models/NeModel.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/LinkToNE.class.php");

//Create object for link to Nova Explorer
$linkNE = new LinkToNE();

//Initialize object
$linkNE->setParameters($_GET['value']);

//Set parameters
$linkNE->setUrl();
$linkNE->setXdrType();
$linkNE->setDateStart();
$linkNE->setDateEnd();
$linkNE->setInterface();
$linkNE->setFilter();

//Get parameters for URL
$url = $linkNE->getUrl();
$xdrType = $linkNE->getXdrType();
$dateStart = $linkNE->getDateStart();
$dateEnd = $linkNE->getDateend();
//17/10/2013 MGO - Bug 37322 - [QAL][5.3.1.08] Link to Nova Explorer is incorrect when containing several interfaces
//Management of a list of interfaces
$listInterface = $linkNE->getInterface();
$listInterface_url= "";
$interface = explode(",", $listInterface);
for($i=0 ; $i<count($interface) ; $i++){
            $listInterface_url=$listInterface_url."&interface=" . trim($interface[$i]);
}
$filter = $linkNE->getFilter();

//Build encoded URL for Nova Explorer
$urlEncoded = $url . "?xdrType=" . $xdrType . 
                     "&dateStart=" . $dateStart . 
                     "&dateEnd=" . $dateEnd . 
                     $listInterface_url 
                     . $filter['encoded'] //optionnal parameter
					 . $linkNE->getServerAndDb();

//For debug mode, build not encoded URL
if($linkNE->debug){
    $urlNoEncoded = $url . "?xdrType=" . $xdrType . 
                           "&dateStart=" . $dateStart . 
                           "&dateEnd=" . $dateEnd . 
                           $listInterface_url 
                           . $filter['noencoded'] //optionnal parameter
						   . $linkNE->getServerAndDb();
    
    echo '<br><b>********************************************************************</b><br>'
            . '<b>' . basename(__FILE__) . '</b> : function <b>' .'</b><br><br>'
            . '<b>Size of url not encoded : </b>'. strlen($urlNoEncoded) .'<br><br>'
            . '<b>Url not encoded : </b>'. $urlNoEncoded .'<br><br>'
            . '<b>Size of url encoded : </b>'. strlen($urlEncoded) .'<br><br>'
            . '<b>Url encoded : </b>'. $urlEncoded .'<br><br>';
}

//Check size of encoded url
$sizeMaxUrl = get_sys_global_parameters('size_max_url');
if(strlen($urlEncoded) > ($sizeMaxUrl-1)) {
    //Depending on the browser, Nova Explorer will be maybe opened with an url truncated => warning message in tracelog
    $message = 'An url longer than ' . ($sizeMaxUrl-1) . ' characters has been generated for link to Nova Explorer: ' . $urlEncoded;
    sys_log_ast("Warning", get_sys_global_parameters("system_name"), "Link to Nova Explorer", "$message", "support_1", "");
    
    if($linkNE->debug)
        echo '<b>Warning : An url longer than ' . ($sizeMaxUrl-1) . 
            ' characters has been generated for link to Nova Explorer: </b><br>' . $urlEncoded;
}

//Redirect to Nova Explorer
header("Location:$urlEncoded");

//In debug mode, new windows for Nova Explorer
if($linkNE->debug || strlen($urlEncoded) > ($sizeMaxUrl-1))    
    echo "<script language='JavaScript'>
        window.open('".$urlEncoded."','_blank','width=700, height=350, top=0, left=0, resizable=yes, scrollbars=yes');
            </script>";

//Get duration
$t2=microtime(true);
echo '<br><b>********************************************************************</b><br>'
            . '<b>Time for ' . basename(__FILE__) . ' = </b>'. ($t2-$t1)*1000 .' msec<br>'
            . '<b>********************************************************************</b><br>';
