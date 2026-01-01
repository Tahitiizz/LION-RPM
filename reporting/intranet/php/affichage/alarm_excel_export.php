<?php
// 04/10/2011 BBX
// BZ 23287 : correction de l'export en utilisant le fichier export_file.php
// afin que le fichier téléchargé ait le bon nom.
session_start();
session_cache_limiter('private');
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/database_connection.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/postgres_functions.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/htmlTableExcel.class.php");

global $database_connection;

// 16:48 17/07/2009 GHX
// Mise entre cote de l'id user
$query = "SELECT object_id,object_title FROM sys_panier_mgt WHERE object_type='alarm_export' AND id_user='$id_user' ORDER BY object_title";
$result = pg_query($query);

$exportname = 'alarm_excel_export_'.uniqid().'.xls';

if (pg_num_rows($result)>0) {
    $excel_filepath	= $repertoire_physique_niveau0.get_sys_global_parameters("pdf_save_dir");
    $excel_filename	= generate_acurio_uniq_id("alarm_detail".$_GET['mode']);
    $header_title	= 'Alarm detail';
    $header_img		= array("operator" => $repertoire_physique_niveau0.get_sys_global_parameters("pdf_logo_operateur"), "client" => $repertoire_physique_niveau0.get_sys_global_parameters("pdf_logo_dev"));
    $sous_mode		= 'detail';

    $html_to_excel = new Excel_HTML_Table($excel_filepath, $excel_filename, $header_img, $header_title, $sous_mode);

    while ($row = pg_fetch_array($result)) {
        $mesDetails = explode ("</table>",$row['object_id']);
        for ($i=0;$i<count($mesDetails)-1;$i++)
            if ($i==0)
                $html[]=array($row['object_title'],$mesDetails[$i]."</table>",11);
            else
                $html[]=array('',$mesDetails[$i]."</table>",11);
    }

    // 04/10/2011 BBX
    // BZ 23287 : correction de l'export en utilisant le fichier export_file.php
    // afin que le fichier téléchargé ait le bon nom.
    if (count($html)) {
        ob_start();
        $html_to_excel->writeContent($html);
        file_put_contents($excel_filepath.$exportname,ob_get_contents());
        ob_end_clean();
        header("Location: export_file.php?file=".base64_encode($excel_filepath.$exportname));
    }
}
?>