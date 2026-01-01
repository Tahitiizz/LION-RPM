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
*	@cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
*/
?>
<?
/*
	- maj 11/10/2006, xavier : remplacement de "t&a" par le contenu de product_name

	- maj 21/11/2006, benoit : on force le nom de l'onglet Excel à "Data Export" (pour éviter le bug si le nom de    l'onget est supérieur à 31 caractères)
*/
session_start();

// 20/05/2010 NSE : relocalisation du module excel dans le CB
require_once( dirname(__FILE__)."/environnement_liens.php" );
require_once(REP_PHYSIQUE_NIVEAU_0."modules/excel/class.writeexcel_workbook.inc.php");
require_once(REP_PHYSIQUE_NIVEAU_0."modules/excel/class.writeexcel_worksheet.inc.php");
// 11/10/2006 xavier
require_once($repertoire_physique_niveau0 . "php/database_connection.php");
require_once($repertoire_physique_niveau0 . "php/edw_function.php");

$name = "export_excel_".rand().".xls";

$fname = tempnam("", $name);
$workbook = &new writeexcel_workbook($fname);

$id_graph=$_GET['id_graph'];

//echo "id graph =$id_graph";
$onglet=$onglet_excel[$id_graph];
$tableau_excel_export=$tableau_data_excel[$id_graph];
$tableau_excel_export_abscisse=$tableau_data_excel_abscisse[$id_graph];
$tableau_excel_export_ordonnee=$tableau_data_excel_ordonnee[$id_graph];
/*
echo "<pre>";
	print_r($onglet);
	echo "<br>---------------------------------<br>";
	print_r($tableau_excel_export);
	echo "<br>---------------------------------<br>";
	print_r($tableau_excel_export_abscisse);
	echo "<br>---------------------------------<br>";
	print_r($tableau_excel_export_ordonnee);
echo "</pre>";

exit;
//*/


// 11/10/2006 xavier
//$worksheet = &$workbook->addworksheet(get_sys_global_parameters('product_name'));

// 21/11/2006 - Modif. benoit : on force le nom de l'onglet à "Data Export"

$worksheet = &$workbook->addworksheet("Data Export");
$worksheet->write(0,0,$onglet);

$row=0;
$col=1;

      for($j=0;$j<count($tableau_excel_export_ordonnee);$j++)
            {
                   $worksheet->write($row,$col, $tableau_excel_export_ordonnee[$j]);

               //$row++;
               $col++;
            }
$row=1;
$col=1;


     for($j=0;$j<count($tableau_excel_export);$j++)
            {
             $row=1;
              for( $k=0;$k<count($tableau_excel_export[$j]);$k++)
                  {
//                   echo $tableau_excel_export[$j][$k]."<br>";
                                   $worksheet->write($row,$col, $tableau_excel_export[$j][$k]);
                   $row++;
                  }
               $col++;
            }

$row=1;
$col=0;

      for($j=0;$j<count($tableau_excel_export_abscisse);$j++)
            {
                   $worksheet->write($row,$col, $tableau_excel_export_abscisse[$j]);

               $row++;
            }



$workbook->close();
// header("Content-Type: application/x-msexcel");
header("Content-Type: application/x-msexcel");
$fh = fopen($fname, "rb");
fpassthru($fh);
unlink($fname);


if(isset($onglet_excel[1]))
{
	session_unregister($onglet_excel[1]);
	session_unregister($tableau_data_excel[1]);
	session_unregister($tableau_data_excel_abscisse[1]);
	session_unregister($tableau_data_excel_ordonnee[1]);
}


/*
header("Content-Type: application/x-excel");
header('Content-Disposition: attachment; filename="Export.xls"');
header("Pragma: public");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
$fh=fopen($fname, "rb");
fpassthru($fh);
unlink($fname);
*/
?>
