<?php
/*
*	@cb41000@
*
*	03/06/2009 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	27/11/2009 BBX
*		- Ajout de l'ouverture du fichier manquante. BZ 13110
*		- Correction de la commande de reindexing. BZ 13110
*
*/
?>
<?php
// Limite d'éxécution très grand
set_time_limit(3600000);

// Inclusion des librairies nécessaires
include_once dirname( __FILE__ ).'/../php/environnement_liens.php';
include_once REP_PHYSIQUE_NIVEAU_0 . 'php/edw_function.php';

// Connexion à la base de données
// 07/11/2011 BBX BZ 24533 : remplacement new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection();

// 06/12/2011 BBX
// BZ 20827 : ajout d'un paramètre permettant de désactiver le vacuum full
$enableVacuumFull = get_sys_global_parameters('vacuum_full', 1);

// VACUUM FULL : le dimanche
if(date('w') == 0 && $enableVacuumFull)
{
    // Ecrit un fichier qui va indiquer aux fichiers qui sont lancés via la cron qu'il ne faut pas se lancer.
    // Cela n'engendre pas de connection à la base. D'où pas de lock waiting à chaque lancement.
    $filename = REP_PHYSIQUE_NIVEAU_0 . "png_file/dump_processing.txt";
    // 27/11/2009 BBX
    // Ajout de l'ouverture du fichier manquante. BZ 13110
    $fp = fopen($filename,'w');
    if ($fp) 
    {
        // Ecriture dans le fichier temporaire
        echo "Ecriture du fichier temporaire :" . $filename."<br />";
        fwrite($fp, "1"); //Ecrit quelquechose dans le fichier
        fclose($fp);
		
        // Vacuum full sur la base de données
        echo "Vacuum FULL car aujourd'hui est un Dimanche<br />";
        $queryVaccum = "VACUUM FULL VERBOSE ANALYZE";
        $database->execute($queryVaccum);		

        // 03/06/2009 BBX : ajout d'un REINDEX sur les tables de topologie
        $tablesTopo = Array('edw_object_arc_ref','edw_object_ref','edw_object_ref_parameters');
        foreach($tablesTopo as $tableToReindex) {
            echo "Reindexing $tableToReindex...";
            // 27/11/2009 BBX
            // Correction de la commande de reindexing. BZ 13110
            $database->execute('REINDEX TABLE '.$tableToReindex.';');
            echo "OK<br />";
        }		
		
        // Suppression du fichier temporaire
        if (unlink($filename)) {
            echo "Le fichier $filename a été effacé\n<br />";
        } 
        else {
            echo "Le fichier $filename n'a pas été effacé\n<br />";
            $message = "Error : $filename not deleted";			
            //  maj 21/03/2008 - Maxime : On récupère le label du module en base pour le Tracelog
            $_module = __T("A_TRACELOG_MODULE_LABEL_VACCUM");
            sys_log_ast("Critical", "Trending&Aggregation", $_module, $message, "support_1", "");
        }
    }
    else {
        echo $filename . " n'a pas pu être généré\n<br />";
    }
}
// VACUUM SIMPLE : la semaine
else
{
    // Vacuum analyse sur la base de données
    echo "Vacuum simple<br/>";
    $queryVaccum = "VACUUM VERBOSE ANALYZE";
    $database->execute($queryVaccum);
}

?>
