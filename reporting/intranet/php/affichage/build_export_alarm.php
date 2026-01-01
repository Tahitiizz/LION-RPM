<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	maj 11/03/2008 - maxime : On génère le fichier des résultats d'alarmes en fonction du type doc,xls ou pdf
*	
* 	08/06/2009 - SPS : ajout de quotes pour les requetes contenant id_user
*
*	18/09/2009 BBX : gestion multiproduit. BZ 11632
 *
 * 02/03/2011 MMT bz 19128: capture la generation sur stdout pour eviter le plantage du download dans les export alarmes
 * le seul retour de ce script doit être le fichier généré.
 *
*/
?>
<?
	
   // 02/03/2011 MMT bz 19128
	// capture tout echo pour controller la sortie
	ob_start();

	session_start();

	include_once(dirname(__FILE__)	. "/../../../../php/environnement_liens.php");
	include_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php");
	include_once(REP_PHYSIQUE_NIVEAU_0."php/postgres_functions.php");
	include_once(REP_PHYSIQUE_NIVEAU_0."class/alarmCreateHTML.class.php");

        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
	$database = Database::getConnection($_GET['product']);
	
	$queries = $_SESSION['queries'];
	$id_user = $_SESSION['id_user'];
	
	// 18/08/2009 BBX : nettoyage de la table avant traitement. BZ 11632
	$query = "DELETE FROM sys_contenu_buffer WHERE object_id=0 AND id_user='$id_user'";
	$result = $database->execute($query);

	switch($_GET['type_file'])
	{		
		case 'pdf' :
			// Appel de la classe htmlTablePDF
			include_once(REP_PHYSIQUE_NIVEAU_0."class/htmlTablePDF.class.php");
			
			// Mise à jour de la table sys_contenu_buffer
			// 18/08/2009 BBX : on ajoute l'id du produit. BZ 11632
			$pdf = new alarmCreateHTML($_GET['mode'],$_GET['sous_mode'],'pdf',$queries,'',$_GET['product']);
			
			// Définition des chemins
			$pdf_dir = REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("pdf_save_dir");
			$pdf_dir_display = NIVEAU_0.get_sys_global_parameters("pdf_save_dir");
			
			// Instanciation de htmlTablePDF
			$html_to_pdf = new PDF_HTML_Table();
			
			// Header
			$header = 'Alarm '.ucfirst($_GET['mode']);
			if($_GET['mode'] == 'twcl') $header = 'Top/Worst Cell Lists';

			// Préparation de la génération du PDF
			$html_to_pdf->generatePDF($_GET['sous_mode'],$header);
			$html_to_pdf->set_PDF_directory($pdf_dir);
			$html_to_pdf->set_PDF_file_name(generate_acurio_uniq_id("alarm_".$_GET['mode']).".pdf");
      
			/*08/06/2009 SPS ajout de quotes autour de l'id_user*/
			$query = "SELECT object_type,object_source,object_title,id_page FROM sys_contenu_buffer WHERE object_id=0 AND id_user='$id_user'";
			$result = $database->execute($query);
			
			while ($row = pg_fetch_array($result)) {
				$html[]=array($row['object_title'],$row['object_source'],$row['id_page']);
			}
			/*08/06/2009 SPS ajout de quotes autour de l'id_user*/
			$query = "DELETE FROM sys_contenu_buffer WHERE object_id=0 AND id_user='$id_user'";
			$result = $database->execute($query);
			if (count($html)) $html_to_pdf->WriteHTML ($html);
			
			// Génération du fichier 
			$pdf_name = $html_to_pdf->get_PDF_file_name();
			
			$html_to_pdf->savePDF();
			
			// On affiche le lien 
			// if (isset($_GET['save_in_file']) && $_GET['save_in_file'] == "true") {

				$download_label = (strpos(__T('U_PDF_FILE_DOWNLOAD'), "Undefined") === false) ? __T('U_PDF_FILE_DOWNLOAD') : "Click here to download the Pdf file";

				// echo '<link rel="stylesheet" href="'.NIVEAU_0.'css/global_interface.css" type="text/css">';

				// Pour forcer le téléchargement du PDF (et non son ouverture), on passe par le script 'php\force_download.php' qui permet de télécharger les fichiers normalement ouvert par le navigateur

				$filepath = NIVEAU_0.get_sys_global_parameters("pdf_save_dir").$pdf_name;
		break;
		
		case 'xls' :
			include_once(REP_PHYSIQUE_NIVEAU_0."class/htmlTableExcel.class.php");
		
			// 18/08/2009 BBX : on ajoute l'id du produit. BZ 11632
			$excel = new alarmCreateHTML($_GET['mode'],$_GET['sous_mode'],'excel',$queries,'',$_GET['product']);

			// 15/01/2008 - Modif. benoit : modification de l'appel à la classe 'Excel_HTML_Table()'

			/*$excel_dir = REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("pdf_save_dir");
			$excel_dir_display = NIVEAU_0.get_sys_global_parameters("pdf_save_dir");

			$html_to_excel = new Excel_HTML_Table();

			$html_to_excel->set_Excel_directory($excel_dir);
			$html_to_excel->set_Excel_file_name(generate_acurio_uniq_id("alarm_".$_GET['mode']).".xls");*/

			$excel_filepath	= REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("pdf_save_dir");
			$excel_filename	= generate_acurio_uniq_id("alarm_".$_GET['mode']).".xls";
			$header_title	= 'Alarm';
			$header_img		= array("operator" => REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("pdf_logo_operateur"), "client" => REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("pdf_logo_dev"));
			$sous_mode		= $_GET['sous_mode'];
			   
			$html_to_excel = new Excel_HTML_Table($excel_filepath, $excel_filename, $header_img, $header_title, $sous_mode, true);
			/*08/06/2009 SPS ajout de quotes autour de l'id_user*/
			$query = "SELECT object_type,object_source,object_title,id_page FROM sys_contenu_buffer WHERE object_id=0 AND id_user='$id_user'";
			$result = $database->execute($query);
			while ($row = pg_fetch_array($result)) {
				$html[]=array($row['object_title'],$row['object_source'],$row['id_page']);
			}
			/*08/06/2009 SPS ajout de quotes autour de l'id_user*/
			$query = "DELETE FROM sys_contenu_buffer WHERE object_id=0 AND id_user='$id_user'";
			$result = $database->execute($query);
			if (count($html)) $html_to_excel->writeContent($html);

			// 15/01/2008 - Modif. benoit : remise en forme du lien de telechargement du fichier Excel

			$filepath = NIVEAU_0.get_sys_global_parameters("pdf_save_dir").$excel_filename;

			$download_label = (strpos(__T('U_EXCEL_FILE_DOWNLOAD'), "Undefined") === false) ? __T('U_EXCEL_FILE_DOWNLOAD') : "Click here to download the Excel file";

		
		break;
		case 'doc' : 
		
			include_once(REP_PHYSIQUE_NIVEAU_0."class/htmlTableWord.class.php");
			
			// 18/08/2009 BBX : on ajoute l'id du produit. BZ 11632
			$word = new alarmCreateHTML($_GET['mode'],$_GET['sous_mode'],'word',$queries,'',$_GET['product']);
	
		    $word_filepath	= REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("pdf_save_dir");
		    $word_filename	= generate_acurio_uniq_id("alarm_".$_GET['mode']);
		    $header_title	= $header;
		    $header_img		= array("operator" => REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("pdf_logo_operateur"), "client" => REP_PHYSIQUE_NIVEAU_0.get_sys_global_parameters("pdf_logo_dev"));
		    $sous_mode		= $_GET['sous_mode'];
		       
		    $html_to_word = new Word_HTML_Table($word_filepath, $word_filename, $header_img, $header_title, $sous_mode, true);
		
			/*08/06/2009 SPS ajout de quotes autour de l'id_user*/
			$query = "SELECT object_type,object_source,object_title,id_page FROM sys_contenu_buffer WHERE object_id=0 AND id_user='$id_user'";
			$result = $database->execute($query);
			
			while ($row = pg_fetch_array($result)) {
				$html[]=array($row['object_title'],$row['object_source'],$row['id_page']);
			}
			/*08/06/2009 SPS ajout de quotes autour de l'id_user*/
			$query = "DELETE FROM sys_contenu_buffer WHERE object_id=0 AND id_user='$id_user'";
			$result = $database->execute($query);
			if (count($html)) $html_to_word->writeContent($html);

			// 15/01/2008 - Modif. benoit : remise en forme du lien de telechargement du fichier Word
		
			$filepath = NIVEAU_0.get_sys_global_parameters("pdf_save_dir").$word_filename.".doc";
			
			$download_label = (strpos(__T('U_WORD_FILE_DOWNLOAD'), "Undefined") === false) ? __T('U_WORD_FILE_DOWNLOAD') : "Click here to download the Word file";

		break;
	}

// end of output capture
ob_end_clean();

echo $filepath;

?>
