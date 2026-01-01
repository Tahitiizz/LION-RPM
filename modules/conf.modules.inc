<?php
/** 	Ce fichier contient tous les chemins vers les modules
*	Pour chaque module, il y a en général deux constantes :
*		MOD_AAA, pour le chemin physique du module
*		URL_AAA, pour l'url du module par rapport à la racine du site
*
*	Grace a ce fichier, on peut facilement déplacer, ou changer les versions d'un module, sans avoir
*	à modifier tous les includes qui sont à l'intérieur du code.
*
*
*	@author	SLC - 17/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*	
*/

DEFINE("MOD_CHARTFROMXML",				$repertoire_physique_niveau0."modules/chartFromXML_v1.0.1/");
DEFINE("BASE_JPGRAPH",					MOD_CHARTFROMXML."jpgraph-2.4p/src/");

// 17/08/2010 MMT bz 16753 detruit references à ancien calendrier

DEFINE("MOD_CLOCK",						$repertoire_physique_niveau0."modules/clock_v1.0.5/");
DEFINE("URL_CLOCK",						$niveau0."modules/clock_v1.0.5/");

DEFINE("MOD_NETWORK_ELEMENT_SELECTION",	$repertoire_physique_niveau0."modules/NetworkElementSelectionModule_v1.2.4/");
DEFINE("URL_NETWORK_ELEMENT_SELECTION",	$niveau0."modules/NetworkElementSelectionModule_v1.2.4/");

DEFINE("MOD_SELECTEUR",					$repertoire_physique_niveau0."modules/selecteur_v1.0.1/");
DEFINE("URL_SELECTEUR",					$niveau0."modules/selecteur_v1.0.1/");

// 12/02/2009 - Modif. benoit : ajout des chemins vers le menu contextuel

DEFINE("MOD_CONTEXTMENU",				$repertoire_physique_niveau0."modules/contextMenu_v1.0.0/");
DEFINE("URL_CONTEXTMENU",				$niveau0."modules/contextMenu_v1.0.0/");

// 17/08/2010 MMT bz 16753 ajout des chemins pour module DatePicker
DEFINE("MOD_DATEPICKER",					$repertoire_physique_niveau0."modules/datePicker_v1.0.0/");
DEFINE("URL_DATEPICKER",					$niveau0."modules/datePicker_v1.0.0/");

?>