<?
/*
*	@cb50417
*
*	15/02/2011 NSE DE Query Builder : Refonte du fichier pour générer un CSV à la place du XLS
 *
 * 16/02/2011 MMT DE Query Builder : utilisation de export_display_download.php pour generation HTML
 * 21/02/2011 NSE DE Query Builder : suppression du fichier après téléchargement + gestion multi-produit (slave sur serveur != master)
 * 25/05/2011 NSE bz 22218 : présence de [na]|s|[ne] dans les exports : remplacement dans la requête
*/
?><?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*
*	maj 16/03/2010 - MPR : Ajout de header afin de forcer le download du fichier si Excel n'est pas installé
*/
?><?php
set_time_limit(3600); // 60 minutes
session_start();
// 15/02/2011 NSE DE Query Builder : on n'utilise plus le module excel et les classes writeexcel_workbookbig...
require_once( dirname(__FILE__)."/environnement_liens.php" );
// 16/02/2011 MMT DE Query Builder : utilisation de export_display_download.php pour generation HTML
include_once( REP_PHYSIQUE_NIVEAU_0 . "php/export_display_download.php");
$export_dir = REP_PHYSIQUE_NIVEAU_0.'/png_file/';

// si on n'a pas encore préparé le fichier, on affiche le building file et on le prépare
if(!isset($_GET['export_file'])){

	// 16/02/2011 MMT DE Query Builder : utilisation de export_display_download.php pour generation HTML
    // 21/02/2011 NSE DE Query Builder : ajout du paramètre pour supprimer le fichier après téléchargement
	displayFileGenerationAndDownload("export_excel_tab.php?export_file=yes","Export query results","Building CSV File ...",TRUE);
    }
else{
   // maj 16/03/2010 - MPR : Ajout de header afin de forcer le download du fichier si Excel n'est pas installé
    //si on passe le try, on a des données, on peut continuer
    //nom du fichier au format csv
    // 15/02/2011 NSE DE Query Builder : utilisation de la classe pour l'objet $builder_report
    require_once($repertoire_physique_niveau0."/builder_report/intranet/php/affichage/report_builder_determiner_requete.php");
    require_once($repertoire_physique_niveau0."/class/SSHConnection.class.php");
    // 15/02/2011 NSE DE Query Builder : tagage des fichiers temporaires pour gérer les accès multiples
    $fname = "/tmp/export_excel".date('YmdHis').".xls";
    $export_file = 'export_query_builder'.date('YmdHis').'.csv';

    // on récupère le builder Report mis en session dans builder_report_table_result
    $builder_report = unserialize($_SESSION["builder_report_session"]);

    // 21/02/2011 NSE DE Query Builder : on se connecte sur la bonne base
    // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
    $database = Database::getConnection($builder_report->getProduct());

    // 21/02/2011 NSE DE Query Builder : On détermine si le produit sur lequel on va générer le fichier est un slave
    $distantServer = 0;
    if(ProductModel::isSlave($builder_report->getProduct())){
        $prodSlave = new ProductModel($builder_report->getProduct());
        $tabProdSlave = $prodSlave->getValues();
        $masterId = $database->getOne('SELECT sdp_id FROM sys_definition_product WHERE sdp_master=1');
        $prodMaster = new ProductModel($masterId);
        $tabProdMaster = $prodMaster->getValues();
        // si le produit sur lequel on va générer le fichier n'est pas sur le même serveur que le master
        if($tabProdSlave['sdp_ip_address']!=$tabProdMaster['sdp_ip_address']){
            $distantServer = 1;
            // on prépare une connextion sur le serveur
            $ssh = new SSHConnection($tabProdSlave['sdp_ip_address'],$tabProdSlave['sdp_ssh_user'],$tabProdSlave['sdp_ssh_password'],$tabProdSlave['sdp_ssh_port']);
    }
                     }

    // le résultat de la requête est directement écrit dans le fichier d'export
    // 21/02/2011 NSE DE Query Builder : on prépare le fichier de façon à pouvoir le supprimer (sinon, user/group postgres en readonly pour group et other)
    if($distantServer)
        $ssh->exec("touch $fname && chmod 777 $fname");
    else
        exec("touch $fname && chmod 777 $fname");
    // 25/05/2011 NSE bz 22218 :
    // la requête peut contenir un select du type [na_parent]|s|[id_ne_enfant]
    // on le remplace par le label (si possible, l'id sinon) de l'élément parent
    // ex. : 'lac|s|' || object_ref.eor_id AS lac_label qui provient de "'$network|s|' || object_ref.eor_id AS {$network}_label,"

    // on récupère le NA min
    $naMin = $builder_report->min_network_for_query;

    // on va rechercher le chemin entre le NaMin et le Na considéré en parcourant l'arbre généalogique de la famille
    function parcoursNaRecursif($tab){
        $elem = array_shift($tab);
        if(empty($tab))
            return "(select eoar_id_parent from edw_object_arc_ref where eoar_id=object_ref.eor_id and eoar_arc_type='$elem')";
        else
            return "(select eoar_id_parent from edw_object_arc_ref where eoar_id=".parcoursNaRecursif($tab)." and eoar_arc_type='$elem')";
    }
    function getLineage($naMin,$naSup){
        global $builder_report;
        return $builder_report->get_NA_lineage($naMin,$naSup);
    }
    // on remplace
    $query = preg_replace("/'([a-z0-9]+)\|s\|' \|\| object_ref.eor_id AS ([a-z0-9]+)_label/e", "'(SELECT CASE WHEN eor_label IS NOT NULL THEN eor_label ELSE eor_id END
                                    FROM edw_object_ref
                                    WHERE eor_id = '.parcoursNaRecursif(getLineage($naMin,'$1')).'
                                            AND eor_obj_type = \'$1\') AS $1_label'", $builder_report->getRequete());

    $query = "COPY
            (".$query.")
    TO '".$fname."' WITH DELIMITER ';' NULL AS '';";
    $database->execute($query);
    // 21/02/2011 NSE DE Query Builder : on récupère le fichier si besoin
    if($distantServer){
        // Le fichier a été généré sur le slave. On le récupère en ssh et on le supprime du serveur distant.
        $ssh->getFile($fname, $fname);
        $ssh->unlink($fname);
}
    // Ajout du Header vers fichier final
    $cmd = 'awk \'BEGIN{FS=";";OFS=FS;print "'.implode(';',$builder_report->getEnteteTableau()).'"}{print $0}\' '.$fname.' > '.$export_dir.$export_file;
    // compression du fichier final et suppression du fichier temporaire
    $cmd .= ' && cd '.$export_dir.' && zip '.$export_file.'.zip '.$export_file.' && rm -f '.$fname;
    // Exécution locale de la commande
    exec($cmd);
    // NSE 21/02/2011 suppression du csv arpès zip
    unlink($export_dir.$export_file);
    //echo '<a href="'.NIVEAU_0.'png_file/export_query_builder.xls">download</a>';

    $export_file .= '.zip';

	 // 16/02/2011 MMT DE Query Builder : on renvoit simplement le chemin du fichier pour displayFileGenerationAndDownload
	 echo $export_dir.$export_file;
}
?>