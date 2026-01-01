<?php
/**
 * 13/07/2012 ACS BZ 27717 Can't generate a data export that is available for HTTPS (slave) 
 * 
 *
 * Ce fichier lance le script export_ajax.php de vérification du formulaire.
 * On utilise ce fichier en local pour pouvoir lancer la vérification sur un slave distant
 * (car le lancement du script Ajax sur un slave distant ne fonctionne pas)
 */
 
session_start();
include_once dirname(__FILE__)."/../php/environnement_liens.php";

// 09/05/2012 NSE reopen bz 23633 : on revient au POST pour compatibilité cb 5.0
// 25/09/2012 BBX BZ 29065 : plus de GET pour ce traitement : la variable GET ne pourra de toutes
// façon pas stocker la totalité des éléments demandés.
// Plus d'appel du script Slave, il n'y a vraisemblablement aucune raison de faire celà ici.
include 'export_ajax.php';
?>
