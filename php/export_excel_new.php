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
- MaJ 06/06/2006 sls : ajout de la prise en compte du cas où on a ni abscisses ni ordonnees
		(pour le script admintool_download_topology.php
*/

session_start();
// 20/05/2010 NSE : relocalisation du module excel dans le CB
require_once( dirname(__FILE__)."/environnement_liens.php" );
require_once(REP_PHYSIQUE_NIVEAU_0."modules/excel/class.writeexcel_workbook.inc.php");
require_once(REP_PHYSIQUE_NIVEAU_0."modules/excel/class.writeexcel_worksheet.inc.php");


$fname = tempnam("", "export_excel.xls");
$workbook = &new writeexcel_workbook($fname);
$worksheet = &$workbook->addworksheet('Excel Export');
$id_graph=$_GET['id_graph'];

//echo "id graph =$id_graph";

$tableau_excel_export=$tableau_data_excel[$id_graph];
$tableau_excel_export_abscisse=$tableau_data_excel_abscisse[$id_graph];
$tableau_excel_export_ordonnee=$tableau_data_excel_ordonnee[$id_graph];

// abscisse or not abscisse ?
if ($export_excel_no_abscisse) {
	$row_data_start=0;
} else {
	$row_data_start=1;
}

// ordonnee or not ordonnee ?
if ($export_excel_no_ordonnees) {
	$col_data_start=0;
} else {
	$col_data_start=1;
}


// ajout des ordonnees
if (!$export_excel_no_ordonnees) {
	$row = $row_data_start;
	$col=0;
	for($j=0;$j<count($tableau_excel_export_ordonnee);$j++){
		$worksheet->write($row,$col, $tableau_excel_export_ordonnee[$j]);
		$row++;
	}
}

	// ajout des données
	$row=$row_data_start;
	for($j=0;$j<count($tableau_excel_export);$j++){
		$col=$col_data_start;
		for( $k=0;$k<count($tableau_excel_export[$j]);$k++){
			$worksheet->write($row,$col, $tableau_excel_export[$j][$k]);
			$col++;
		}
		$row++;
	}

	// ajout des abscisses
	if (!$export_excel_no_abscisse) {
		$row=0;
		$col=$col_data_start;
		for($j=0;$j<count($tableau_excel_export_abscisse);$j++){
			$worksheet->write($row,$col, $tableau_excel_export_abscisse[$j]);
			$col++;
		}
	}


	$workbook->close();
	header("Content-Type: application/x-msexcel");
	// header("Content-Type: application/x-excel");

	// header('Content-Disposition: attachment; filename="Export.xls"');
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	$fh=fopen($fname, "rb");
	fpassthru($fh);
	unlink($fname);

?>
