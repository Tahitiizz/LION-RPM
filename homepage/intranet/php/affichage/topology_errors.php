<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?

/*
Cette page affiche les erreurs de topologie en fonction de la variable display :

$display == 'xy'                --> affiche les elements n'ayant pas de coordonnées
$display == 'na'                --> affiche les elements n'ayant pas de NA
$display == 'na_label'        --> affiche les elements n'ayant pas de NA label

A chaque fois, la page affiche un tableau ET crée un fichier Excell contenant les mêmes
informations que le tableau.

-- sls le 04/11/2005
*/

session_start();
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/environnement_donnees.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");

include_once($repertoire_physique_niveau0 . "class/alarm_export_excel.class.php");
include_once($repertoire_physique_niveau0 . "class/export_excel.class.php");
?>
<html>
<head>
<title>Topology Errors</title>
<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css">
<script src="<?=$niveau0?>js/gestion_fenetre.js"></script>
<script src="<?=$niveau0?>js/fonctions_dreamweaver.js"></script>
</head>

<body>

<?

//
// on recupere les principales variables
//

include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");

$main_family = get_main_family();
$object_ref  = get_object_ref_from_family($main_family);
$level_mini  = get_network_aggregation_min_from_family($main_family);
$NA          = get_mandatory_network_aggregation_from_family($main_family);
$level_mini_label = $level_mini . '_label';
$NA_label         = array();
foreach($NA as $key => $val) {
        $NA_label[$key.'_label'] = $val.'_label';
}
$xls_tab         = array();

// echo $level_mini.'|'.implode(':',$NA);


//
// cas des sai ou cells qui n'ont pas de coordonnées
//
if ($display == 'xy') {

        // titre
        $title = $level_mini."  without coordinates";

        // debut du tableau à afficher
        $html_table = "<table class='tabAlarme' cellpadding='2'>
                <tr>
                        <th class='fondEnteteAlarmes'><span class='texteBlancBold'> $level_mini </span></th>
                        <th class='fondEnteteAlarmes'><span class='texteBlancBold'> $level_mini_label </span></th>
                        <th class='fondEnteteAlarmes'><span class='texteBlancBold'>x</span></th>
                        <th class='fondEnteteAlarmes'><span class='texteBlancBold'>y</span></th>
                </tr>
                ";

        // on cherche les elements qui n'ont pas de coordonnées
        $query = "
                SELECT $level_mini,$level_mini_label
                FROM $object_ref
                WHERE x IS NULL OR y IS NULL
                ";
        $res   = pg_query($database_connection, $query);
        $nb_xy = pg_num_rows($res);

        // on parcours les réponses pour construire le corps tableau
        $i = 0;
        while ($row = pg_fetch_array($res)) {
                if ($i==0) {
                        $html_table .= "<tr class='tabLigneAlarmeBlanc' onMouseOver=\"javascript:this.className='fondBlanc'\"        onMouseOut=\"javascript:this.className='tabLigneAlarmeBlanc'\">\n";
                        $i++;
                } else {
                        $html_table .= "<tr class='tabLigneAlarmeGris' onMouseOver=\"javascript:this.className='fondBlanc'\"        onMouseOut=\"javascript:this.className='tabLigneAlarmeGris'\">\n";
                        $i = 0;
                }
                $html_table .= "
                        <td class='texteGrisPetit' nowrap='nowrap'>".$row[$level_mini]."&nbsp;</td>
                        <td class='texteGrisPetit'>".$row[$level_mini_label]."&nbsp;</td>
                        <td class='texteGrisPetit'>".$row['x']."&nbsp;</td>
                        <td class='texteGrisPetit'>".$row['y']."&nbsp;</td>
                </tr>
                ";
                // on construit le tableau de données pour le fichier excell
                $xls_row = array($row[$level_mini],$row[$level_mini_label],$row['x'],$row['y']);
                $xls_tab[] = $xls_row;
        }
        $html_table .= '</table>';

        // Hearders pour le fichier Excell
        $headers = array($level_mini,$level_mini_label,'x','y');
}

//
// cas des sai ou cells qui n'ont pas de NA
//
if ($display == 'na') {

        // titre
        $title = $level_mini."  without network agregation";

        // on compose le haut du tableau
        $html_table        = "
                <table class='tabAlarme' cellpadding='2'>
                        <tr>
                                <th class='fondEnteteAlarmes' nowrap='nowrap'><span class='texteBlancBold'> $level_mini </span></th>
                                <th class='fondEnteteAlarmes'><span class='texteBlancBold'> $level_mini_label </span></th>
                ";
        foreach ($NA as $key => $val)
                if ($key != $level_mini)
                        $html_table        .= "<th class='fondEnteteAlarmes'><span class='texteBlancBold'>$val</span></th>\n";
        $html_table        .= "\n        </tr>";

        // on cherche les elements qui n'ont pas de NA
        // on compose la query : SELECT
        $query = "SELECT $level_mini,$level_mini_label,";
        $NA_temp = array();
        foreach ($NA as $key => $val) {
                $NA_temp[] = $key;
        }
        $query .= implode(',',$NA_temp);
        unset($NA_temp);
        // FROM
        $query .= "        FROM $object_ref WHERE ";
        // WHERE
        $NA_temp = array();
        foreach ($NA as $key => $val) {
                $NA_temp[] = $key . ' IS NULL';
        }
        $query .= implode(' OR ',$NA_temp);
        $res   = pg_query($database_connection, $query);
        $nb_na = pg_num_rows($res);

        // corps du tableau de données
        $i = 0;
        while ($row = pg_fetch_array($res)) {
                if ($i==0) {
                        $html_table        .= "<tr class='tabLigneAlarmeBlanc' onMouseOver=\"javascript:this.className='fondBlanc'\"        onMouseOut=\"javascript:this.className='tabLigneAlarmeBlanc'\">\n";
                        $i++;
                } else {
                        $html_table        .= "<tr class='tabLigneAlarmeGris' onMouseOver=\"javascript:this.className='fondBlanc'\"        onMouseOut=\"javascript:this.className='tabLigneAlarmeGris'\">\n";
                        $i = 0;
                }
                $html_table        .= "
                        <td class='texteGrisPetit' nowrap='nowrap'>".$row[$level_mini]."&nbsp;</td>
                        <td class='texteGrisPetit' nowrap='nowrap'>".$row[$level_mini_label]."&nbsp;</td>
                        ";
                foreach ($NA as $key => $val) {
                        if ($key != $level_mini)
                                $html_table        .= '<td class="texteGrisPetit">'.$row[$key].'</td>'."\n";
                }
                $html_table        .= "
                        </tr>
                ";

                // on construit le tableau de données pour le fichier excell
                $xls_row = array($row[$level_mini],$row[$level_mini_label]);
                foreach ($NA as $key => $val)
                        if ($key != $level_mini)
                                $xls_row[] = $row[$key];
                $xls_tab[] = $xls_row;

                }
        $html_table        .= '</table>';

        // On prépare les headers pour le fichier excel.
        $headers = array($level_mini,$level_mini_label);
        foreach ($NA as $key => $val)
                if ($key != $level_mini)
                        $headers[] = $key;
}


//
// cas des sai ou cells qui n'ont pas de NA_labels
//
if ($display == 'na_label') {

        // titre
        $title = $level_mini."  without network agregation label";

        // on compose le haut du tableau
        $html_table        = "
                <table class='tabAlarme' cellpadding='2'>
                        <tr>
                                <th class='fondEnteteAlarmes' nowrap='nowrap'><span class='texteBlancBold'> $level_mini </span></th>
                                <th class='fondEnteteAlarmes'><span class='texteBlancBold'> $level_mini_label </span></th>
                ";
        foreach ($NA_label as $key => $val)
                if ($key != $level_mini_label)
                        $html_table        .= "<th class='fondEnteteAlarmes'><span class='texteBlancBold'>$val</span></th>\n";
        $html_table        .= "\n        </tr>";

        // on cherche les elements qui n'ont pas de NA_label
        $query = "SELECT $level_mini,$level_mini_label,";
        $NA_temp = array();
        foreach ($NA_label as $key => $val)
                $NA_temp[] = $key;
        $query .= implode(',',$NA_temp);
        unset($NA_temp);
        // FROM
        $query .= " FROM $object_ref WHERE ";
        // WHERE
        $NA_temp = array();
        foreach ($NA_label as $key => $val)
                $NA_temp[] = $key . ' IS NULL';
        $query .= implode(' OR ',$NA_temp);
        $res   = pg_query($database_connection, $query);
        $nb_na = pg_num_rows($res);

        // corps du tableau de données
        $i = 0;
        while ($row = pg_fetch_array($res)) {
                if ($i==0) {
                        $html_table        .= "<tr class='tabLigneAlarmeBlanc' onMouseOver=\"javascript:this.className='fondBlanc'\"        onMouseOut=\"javascript:this.className='tabLigneAlarmeBlanc'\">\n";
                        $i++;
                } else {
                        $html_table        .= "<tr class='tabLigneAlarmeGris' onMouseOver=\"javascript:this.className='fondBlanc'\"        onMouseOut=\"javascript:this.className='tabLigneAlarmeGris'\">\n";
                        $i = 0;
                }
                $html_table        .= "
                        <td class='texteGrisPetit' nowrap='nowrap'>".$row[$level_mini]."&nbsp;</td>
                        <td class='texteGrisPetit' nowrap='nowrap'>".$row[$level_mini_label]."&nbsp;</td>
                        ";
                foreach ($NA_label as $key => $val)
                        if ($key != $level_mini_label)
                                $html_table        .= '<td class="texteGrisPetit">'.$row[$key].'</td>'."\n";
                $html_table        .= "
                        </tr>
                ";

                // on construit le tableau de données pour le fichier excell
                $xls_row = array($row[$level_mini],$row[$level_mini_label]);
                foreach ($NA_label as $key => $val)
                        if ($key != $level_mini_label)
                                $xls_row[] = $row[$key];
                $xls_tab[] = $xls_row;

                }

        $html_table        .= '</table>';

        // On prépare les headers pour le fichier excel.
        $headers = array($level_mini,$level_mini_label);
        foreach ($NA as $key => $val)
                if ($key != $level_mini)
                        $headers[] = $key;
}


// on cree le fichier excell
$fileName = "topo_errors_".rand().".xls";
$title_shorter = str_replace('network agregation','NA',$title);
$excelFile = new exportExcel($title_shorter, $fileName, $xls_tab, $headers);
$excelFile->generateExcelFile();
$excel_file = $excelFile->getExcelFileName();
// le lien vers le fichier excel
$excell_link = "<div align='center'><a href='../../../../".get_sys_global_parameters('pdf_save_dir').$excel_file."'><img src=\"$niveau0"."images/icones/excel_icon.gif\" border='0'></a></div>";


// on affiche le tableau de données et le lien vers le fichier excell
echo "
        <div align='center'>
                <h2 class='texteGrisBold'> $title </h2>

                $excell_link <br />

                $html_table
        </div>
        ";

?>

</body>
</html>
