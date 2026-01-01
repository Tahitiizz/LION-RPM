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
* Permet d'afficher le fichier PDF dans le navigateur.
*/
// Permet de dire que l'on veut afficher un fichier PDF.
//header('Content-type: application/pdf');

// On ouvre le fichier PDF dans le navigateur.
session_start();
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");

	$dir_pdf = get_sys_global_parameters("pdf_save_dir");
	$pdf = $niveau0.$dir_pdf.basename($_GET["pdf"]);
	//echo $pdf; exit;
?>
<script type="text/javascript">
	document.location="<?=$pdf?>";
</script>
