<?php
/*
 * Model file for PAA.inc configuration of Portal Astellia
 * This configuration is currently set up for API PAA Lot 1
 *
 * This file is used by tools/configurePortal.sh to generate the PAA.inc file
 *
 * 27/08/2011 MMT - DE Portal Lot 1 - creation du ficher a partir de PAA.inc
 *    desactive les elements uniquement utiles au lot 2
 *
 */

// -----------------------------------------------------------------------
//
// Services
//  CAS  : connexion au Portail
//  File : mode StandAlone
//  FileCustomLogin : mode StandAlone avec fenêtre de login personnalisée
//  TA : mode historique (lot 1 uniquement)
// -----------------------------------------------------------------------


define('PAA_SERVICE','<PAA_SERVICE_TO_REPLACE>');

// URL de l'API PAA (nécessaire pour le service File)
// utile pour Lot 2
define('PAA_API_URL', '/<TA_NAME_TO_REPLACE>/api/paa/');

// fenêtre de login personnalisée (nécessaire pour le service FileCustomLOGIN)
// utile pour Lot 2
define('PAA_LOGIN_PAGE', 'form_login.php');


define('CAS_SERVER','<CAS_SERVER_TO_REPLACE>');
define('CAS_SERVER_PUBLIC','<CAS_SERVER_TO_REPLACE>');
define('CAS_PORT',8443);
define('CAS_URI','/cas/');
define('CAS_TIMEOUT',10);
define('CAS_DEBUG',false);
define('CAS_LOG_PATH','d:/temp/');
define('CAS_LOG_FILE','cas.log');
define('WEB_SERVICE_URL', 'http://<CAS_SERVER_TO_REPLACE>/webservice/webservice.wsdl');
define('SOAP_TRACE', 1);
define('SOAP_CACHE', 0);
// Gestion du mode dégradé
// 16/03/2012 NSE bz 26417
define('CAS_DEGRADEDMODE_ENABLED', 1);  // Enabled degraded mode for CAS mode used
define('CAS_DEGRADEDMODE_SQLITE_DIR', '/home/<TA_NAME_TO_REPLACE>/api/paa/conf/');
define('CAS_DEGRADEDMODE_SQLITE_FILE', 'astellia.sqlite3');
define('CAS_DEGRADEDMODE_SQLITE_PUBLIC_KEY', 'astellia.pub');
 
// NSE DE Astellia Portal Lot2
define('APPLI_GUID_HEXA','<APPLI_GUID_HEXA_TO_REPLACE>');
define('APPLI_GUID_NAME','<APPLI_GUID_NAME_TO_REPLACE>');

// utile pour Lot 2
define('SQLITE_DIR', '/home/<TA_NAME_TO_REPLACE>/api/paa/conf/');
define('SQLITE_FILE', 'astellia.sqlite3');
define('SQLITE_PUBLIC_KEY', 'astellia.pub');
// 08/03/2012 NSE DE Logout : fichier de base de données des session mémorisées
define('SQLITE_SESSION_FILE', 'localSession.sqlite');
?>