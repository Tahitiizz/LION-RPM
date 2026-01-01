<?php
/**
 * 
 *  CB 5.2
 * 
 * Génère et envoie le fichier XML de déclaration d'une appli auprès du PAA
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 * 24/04/2012 NSE bz 26636 : le profile Astellia Administrator n'est plus exporté sur le Portail
 */
?><?php
include_once dirname(__FILE__)."/../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0 . '/models/ProfileModel.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0 . '/api/paa/PAAAuthenticationService.php');
        
// Création du nouvel objet XML. On utilise pour ce faire la classe Dom de PHP
$dom = new DOMDocument('1.0', 'UTF-8');

// active un export indenté du XML
$dom->formatOutput = true;

/*$guid_hexa = '4ff986fd865';
$guid_appli = 'nseiucb52';
$appli_name = 'TA IU';
$appli_path = 'http://10.35.10.152/nse_iu_cb52/';
$description = '';
$casIp = ;*/

$p = new ProductModel('');
$product = $p->getValues();
$appli_name = $product['sdp_label'];;

foreach ($_SERVER['argv'] as $arg){
    list ($var,$val) = explode('=', $arg);
    if($var!=$arg){
        $$var=trim($val);
    }
}

// En mode dev, on effectue quelques modifications
if(is_dir("/home/ta_install/cles_produit")) {
    $appli_name .= " (".$product['sdp_db_name'].")";
    if(file_exists("/home/ta_install/cles_produit/color.txt") ) {
        $customColor = trim(file_get_contents("/home/ta_install/cles_produit/color.txt"));
        if(preg_match("/^[a-zA-Z0-9]{6}$/", $customColor) ) {
            $appli_name = '<span style="color:#'.$customColor.'">'.$appli_name.'</span>';
        }
    }
}

// Création du namespace
$racine = $dom->createElementNS('http://'.$casIp.'/webservice/','tns:application');
$racine->setAttribute( 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance' );
$racine->setAttribute( 'xsi:schemaLocation', 'http://'.$casIp.'/webservice/soap/paa_import_application.xsd' );

// Définition de l'application
// 16/03/2012 NSE bz 26419 : conflit nom variable $element
$elementtns = $dom->createElement('tns:guid', $guid_hexa.'.'.$guid_appli);
$racine->appendChild( $elementtns );
$elementtns = $dom->createElement('tns:name', htmlentities($appli_name));
$racine->appendChild( $elementtns );
$elementtns = $dom->createElement('tns:path', $appli_path.($appli_path[strlen($appli_path)-1]!='/'?'/':''));
$racine->appendChild( $elementtns );
$elementtns = $dom->createElement('tns:description', $description);
$racine->appendChild( $elementtns );
$elementtns = $dom->createElement('tns:type', 1);
$racine->appendChild( $elementtns );
$elementtns = $dom->createElement('tns:category', 2);
$racine->appendChild( $elementtns );

// Ajout des droits à partir des profiles TA
$rights = $dom->createElement('tns:rights');

// 24/04/2012 NSE bz 26636 : le profile Astellia Administrator n'est plus exporté sur le Portail
// car méthode isSupportUser() maintenant dispo sur Portail
$profiles = ProfileModel::getProfiles();
//getAllProfiles() retourne aussi le profile correspondant à astellia_admin 
//(nécessaire pour que le profile astellia_admin remonte sur le portail et soit disponible pour le super utilisateur astellia_admin)
foreach ($profiles as $profile){
    $right = $dom->createElement('tns:Right');
    // 22/03/2012 NSE bz 26496 : Ajout de .'.'.APPLI_GUID_NAME pour identifier le droit
    $elementtns = $dom->createElement('tns:guid', $guid_hexa.'.'.$guid_appli.'.'.$profile['id_profile']);
    $right->appendChild( $elementtns );
    $elementtns = $dom->createElement('tns:name', $profile['profile_name']);
    $right->appendChild( $elementtns );
    // 16/03/2012 NSE bz 26404 : Pour astellia admin, on met le type 0 : ni user, ni admin pour ne pas faire partie des rôles All Application User / Admin
    $elementtns = $dom->createElement('tns:type', ($profile['profile_type']=='user'?1:($profile['client_type']=='protected'?0:2)) );
    $right->appendChild( $elementtns );
    
    $rights->appendChild( $right );
}

$racine->appendChild( $rights );

// Ajout des Ip
$ips = $dom->createElement('tns:ips');
$racine->appendChild( $ips );

// Ajout des rôles
$roles = $dom->createElement('tns:roles');
// sans le profile astellia_admin
//$profiles = ProfileModel::getProfiles();

// rôle Admin
$role = $dom->createElement('tns:Role');
$elementtns = $dom->createElement('tns:guid', 'TA_ADMIN');
$role->appendChild( $elementtns );
$elementtns = $dom->createElement('tns:name', 'TA Administrator');
$role->appendChild( $elementtns );
$elementtns = $dom->createElement('tns:type', 0 );
$role->appendChild( $elementtns );
$elementtns = $dom->createElement('tns:description', 'TA Administrator' );
$role->appendChild( $elementtns );
$rights = $dom->createElement('tns:rights_guid');
foreach ($profiles as $profile){
    // 16/03/2012 NSE bz 26404 : on ne liste pas ici les profiles protégés
    if($profile['profile_type']=='admin' && $profile['client_type']!='protected'){
        $right = $dom->createElement('tns:rights', $guid_hexa.'.'.$guid_appli.'.'.$profile['id_profile']);
        $rights->appendChild( $right );
    }
}
$role->appendChild( $rights );
$roles->appendChild( $role );

// rôle User
$role = $dom->createElement('tns:Role');
$elementtns = $dom->createElement('tns:guid', 'TA_USER');
$role->appendChild( $elementtns );
$elementtns = $dom->createElement('tns:name', 'TA User');
$role->appendChild( $elementtns );
$elementtns = $dom->createElement('tns:type', 0 );
$role->appendChild( $elementtns );
$elementtns = $dom->createElement('tns:description', 'TA User' );
$role->appendChild( $elementtns );
$rights = $dom->createElement('tns:rights_guid');
foreach ($profiles as $profile){
    if($profile['profile_type']=='user'){
        // 22/03/2012 NSE bz 26496 : Ajout de .'.'.APPLI_GUID_NAME pour identifier le droit
        $right = $dom->createElement('tns:rights', $guid_hexa.'.'.$guid_appli.'.'.$profile['id_profile']);
        $rights->appendChild( $right );
    }
}
$role->appendChild( $rights );
$roles->appendChild( $role );

// 24/04/2012 NSE bz 26636 : le profile Astellia Administrator n'est plus exporté sur le Portail

$racine->appendChild( $roles );

// Mantis 2547 : ajout automatique de la documentation
// création de la balise documents
$documents = $dom->createElement('tns:documents');
// Premier document : Admin Manual
// création de la balise Document
$document = $dom->createElement('tns:Document');
// ajout des éléments de la balise Document
// 2014/09/16 NSE bz 43282 : remplacement des & par &amp; dans les champs texte
$elementtns = $dom->createElement('tns:name','Trending &amp; Aggregation Administration Guide');
$document->appendChild( $elementtns );
$elementtns = $dom->createElement('tns:description','A technical manual for configuring and/or administrating');
$document->appendChild( $elementtns );
$version = explode('.',get_sys_global_parameters("product_version"));
$elementtns = $dom->createElement('tns:version',$version[0].'.'.$version[1].'.'.$version[2]);
$document->appendChild( $elementtns );
$elementtns = $dom->createElement('tns:keywords','T&amp;A, Admin Manual, Trending and Aggregation');
$document->appendChild( $elementtns );
$elementtns = $dom->createElement('tns:scope','2');
$document->appendChild( $elementtns );

// création de la balise addresses
$addresses = $dom->createElement('tns:addresses');
$addresse = $dom->createElement('tns:Address');
// ajout des éléments de la balise Address
$elementtns = $dom->createElement('tns:format', 'pdf');
$addresse->appendChild( $elementtns );
// 2014/09/16 NSE bz 43282 : remplacement des & par %26 dans les champs url
$elementtns = $dom->createElement('tns:value', $appli_path.($appli_path[strlen($appli_path)-1]!='/'?'/':'').'doc/Trending%26Aggregation_AdminManual.pdf');
$addresse->appendChild( $elementtns );
// ajout de l'addresse dans les addresses
$addresses->appendChild( $addresse );
// ajout des addresses dans le document
$document->appendChild( $addresses );

// ajout du document dans les documents
$documents->appendChild( $document );


// Deuxième document : User Manual
// création de la balise Document
$document = $dom->createElement('tns:Document');
// ajout des éléments de la balise Document
$elementtns = $dom->createElement('tns:name','Trending &amp; Aggregation User Manual');
$document->appendChild( $elementtns );
$elementtns = $dom->createElement('tns:description','A manual intended to give assistance to people using T&amp;A');
$document->appendChild( $elementtns );
$elementtns = $dom->createElement('tns:version',$version[0].'.'.$version[1].'.'.$version[2]);
$document->appendChild( $elementtns );
$elementtns = $dom->createElement('tns:keywords','T&amp;A, Admin User, Trending and Aggregation');
$document->appendChild( $elementtns );
$elementtns = $dom->createElement('tns:scope','2');
$document->appendChild( $elementtns );

// création de la balise addresses
$addresses = $dom->createElement('tns:addresses');
$addresse = $dom->createElement('tns:Address');
// ajout des éléments de la balise Address
$elementtns = $dom->createElement('tns:format', 'pdf');
$addresse->appendChild( $elementtns );
$elementtns = $dom->createElement('tns:value', $appli_path.($appli_path[strlen($appli_path)-1]!='/'?'/':'').'doc/Trending%26Aggregation_UserManual.pdf');
$addresse->appendChild( $elementtns );
// ajout de l'addresse dans les addresses
$addresses->appendChild( $addresse );
// ajout des addresses dans le document
$document->appendChild( $addresses );

// ajout du document dans les documents
$documents->appendChild( $document );


// ajout des documents dans le xml
$racine->appendChild( $documents );

// fin Mantis 2547

$dom->appendChild($racine);

$fic = REP_PHYSIQUE_NIVEAU_0."upload/paaAppliImport_".(date('YmdHi')).".xml";
if(!$dom->save($fic)){
    echo "File not saved";
    
}

// supprimer le fichier de conf
$PAAAuthentication = PAAAuthenticationService::getAuthenticationService(REP_PHYSIQUE_NIVEAU_0 . '/api/paa/conf/PAA.inc');
// 15/03/2012 NSE bz 26387 : gestion du retour pour signaler l'erreur 
$retour = $PAAAuthentication->setApplication($dom->saveXML());
if($retour == $guid_hexa.'.'.$guid_appli || $retour == 0){
    echo 'ok';
}
else{
    echo "Application not registered on PAA ($retour).";
}
//echo $dom->saveXML();
?>