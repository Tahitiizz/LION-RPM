<?php
/*
*	CLASSE DashboardExport
*	Cette classe va permettre de créer un export de Dashboard et Caddie
*
*	@version : 4.1.0.00
*	@author : BBX
*	@date : 19/01/2009
*
*	04/01/2010 BBX
*		=> Gestion du cas sans données (il faut afficher no data). BZ 12231
*	18/01/2010 NSE bz 13789
*		- on attrape l'erreur de génération du fichier excel et on l'affiche
*
*/
class DashboardExport 
{
	// Tableau de données
	private $data = Array();
	// Répertoire de destination
	private $exportDir = '';
	// Nom du fichier sans extension
	private $fileName = '';
	// Fichier odf
	private $odt = null;
	// Logo Astellia
	private $astelliaLogo = '';
	// Logo Client
	private $clientLogo = '';
	// Image de puce
	private $puceImage = '';
	// Mode d'affichage
	private $modeAffichage = 'landscape';
	
	/****
	* Constructeur
	* @param array data
	* @param string export dir
	*****/
	public function __construct($data,$modeAffichage,$exportDir,$fileName,$astelliaLogo,$clientLogo,$puceImage) {
		$this->data = $data;
		$this->exportDir = $exportDir;
		$this->astelliaLogo = $astelliaLogo;
		$this->clientLogo = $clientLogo;	
		$this->puceImage = $puceImage;	
		$this->fileName = $fileName.uniqid();
		$this->modeAffichage = $modeAffichage;
	}

	/****
	* buildOdt
	* Construit le fichier odt
	*****/	
	public function buildOdt() {
		// Instanciation de la classe PHPOdf
		$modeAffichage = ($this->modeAffichage == 'landscape4') ? 'landscape' : $this->modeAffichage;
		$this->odt = new PHPOdf($this->exportDir.'/'.$this->fileName.'.odt',$modeAffichage);
                
                // 01/08/2011 BBX
                // Encodage des &
                // BZ 23221
                $this->data['titre'] = str_replace("&", "&amp;", $this->data['titre']);
                $this->data['comment'] = str_replace("&", "&amp;", $this->data['comment']);
                
		// Création du header
		$this->odt->astelliaHeader($this->data['titre'],$this->astelliaLogo,$this->clientLogo);
		// Création du footer
		$this->odt->astelliaFooter();
		// 31/07/2009 BBX : Gestion du commentaire sur le Dashboard. BZ 10633
		if(isset($this->data['comment']) && !empty($this->data['comment']))
		{
			$this->odt->dashComment($this->data['comment'],$this->puceImage);
			$this->odt->pageBreak();
		}
		$i = 0;
		$gtmBuffer = Array();
		// Insertion des GTM
		foreach($this->data['data'] as $idGtm => $infosGtm) 
		{
                    // 01/08/2011 BBX
                    // Encodage des &
                    // BZ 23221
                    $infosGtm['titre'] = str_replace("&", "&amp;", $infosGtm['titre']);
                    $infosGtm['lastComment'] = str_replace("&", "&amp;", $infosGtm['lastComment']);
                    
                    
			// 04/01/2010 BBX. BZ 12231
			// Test de l'image
			if(empty($infosGtm['image']))
			{
				$infosGtm['image'] = $this->noDataImg($infosGtm['nodata']);
			}
			// FIN BZ 12231
			// Mode portrait
			if($this->modeAffichage == 'portrait') {
				if($i%2 == 0 && $i > 0) $this->odt->pageBreak();
				if($i%2 == 0)
					$this->odt->astelliaGTMPortraitTop($infosGtm['titre'],$infosGtm['image'],$this->puceImage,$infosGtm['lastComment']);
				else
					$this->odt->astelliaGTMPortraitBottom($infosGtm['titre'],$infosGtm['image'],$this->puceImage,$infosGtm['lastComment']);
			}
			// Mode paysage, 4 graphes par page
			elseif($this->modeAffichage == 'landscape4') {	
				$gtmBuffer[] = Array($infosGtm['titre'],$infosGtm['image'],$infosGtm['lastComment']);
				if(count($gtmBuffer) == 4) {
					if($i > 3) $this->odt->pageBreak();
					$this->odt->astelliaGTMArray(
						$gtmBuffer[0][0],$gtmBuffer[0][1],
						$gtmBuffer[1][0],$gtmBuffer[1][1],
						$gtmBuffer[2][0],$gtmBuffer[2][1],
						$gtmBuffer[3][0],$gtmBuffer[3][1],						
						$gtmBuffer[0][2],$gtmBuffer[1][2],
						$gtmBuffer[2][2],$gtmBuffer[3][2]);		
					$gtmBuffer = Array();
				}
			}
			// Mode paysage standard
			else {
				if($i > 0) $this->odt->pageBreak();
				$this->odt->astelliaGTMLandscape($infosGtm['titre'],$infosGtm['image'],$this->puceImage,$infosGtm['lastComment']);
			}
			$i++;
		}
		// Cas particulier : mode 4 par page avec nombre de GTM à afficher non divisible par 4
		// S'il reste des graphes dans le buffer, on les affiche
		if(($this->modeAffichage == 'landscape4') && (count($gtmBuffer) != 0)) {
			$this->odt->pageBreak();
			$this->odt->astelliaGTMArray(
				$gtmBuffer[0][0],$gtmBuffer[0][1],
				$gtmBuffer[1][0],$gtmBuffer[1][1],
				$gtmBuffer[2][0],$gtmBuffer[2][1],
				$gtmBuffer[3][0],$gtmBuffer[3][1],						
				$gtmBuffer[0][2],$gtmBuffer[1][2],
				$gtmBuffer[2][2],$gtmBuffer[3][2]);	
		}
		// Sauvegarde du fichier
		$this->odt->save();
	}
	
	/****
	* Export PDF
	* Construit le fichier pdf
	* @return string chemin du fichier d'export
	*****/
	public function pdfExport() {
		$this->buildOdt();
		$this->odt->exportPdf();
		$this->deleteOdt();
		return $this->exportDir.'/'.$this->fileName.'.pdf';
	}
	
	/****
	* Export Word
	* Construit le fichier doc
	* @return string chemin du fichier d'export
	*****/
	public function wordExport() {
		$this->buildOdt();
		$this->odt->exportWord();
		$this->deleteOdt();
		return $this->exportDir.'/'.$this->fileName.'.doc';
	}
	
	/****
	* Export Excel
	* Construit le fichier xls
	* @return string chemin du fichier d'export
	*****/
	public function excelExport() {
		// création de fichier de type xls
		include_once(REP_PHYSIQUE_NIVEAU_0. "dashboard_display/class/XmlExcel.class.php");
		
		// on se construit la liste des fichiers de données XML
		$xml_data_files = array();
		foreach ($this->data['data'] as $idGtm => $infosGtm)
			$xml_data_files[] = $infosGtm['xml'];
		
		try{
		
			// appel de la classe qui va generer le fichier Excel
			$xmlExcel = new XmlExcel();
			// on recupere les donnees de la liste de xml
			$xmlExcel->getMultipleData($xml_data_files);
			// on sauvegarde le fichier
			$filePath = $xmlExcel->save(REP_PHYSIQUE_NIVEAU_0.'report_files/');
					
		}catch(Exception $e) {
			//on capture l'exception et on affiche l'erreur dans le demon
			displayInDemon( $e->getMessage() );
		}

		// Retour du chemin du fichier (si aucune Exception)
		return $filePath;
	}

	/****
	* Suppression du fichier "temporaire" odt
	*****/	
	public function deleteOdt() {
		$cmd = 'rm -f '.$this->exportDir.'/'.$this->fileName.'.odt';
		exec($cmd);
	}
	
	/****
	* 04/01/2010 BBX. BZ 12231
	* Génération de l'image dans le cas où il n'y a pas de données
	* @param string : message à afficher
	* @return string : chemin du fichier image généré
	*****/
	public function noDataImg($message)
	{
		// Chemin de l'image qui va être générée
		$graph = REP_PHYSIQUE_NIVEAU_0.'png_file/nodata_'.uniqid().'.png';
		
		// Traitement du message
		$message = str_replace(Array('<br>','<br />','<br/>'),"\n",$message);
		$message = strip_tags($message);
		$message = str_replace('Information :','',$message);
		
		// Récupération des lignes du message
		$lines = explode("\n",$message);
	
		// Création de l'image depuis le template
		$image = imagecreatefrompng(REP_PHYSIQUE_NIVEAU_0.'images/graph/template_nodata.png');
		
		// Création de la couleur du texte
		$rouge = 247;
		$vert = 88;
		$bleu = 88;
		$couleur = imagecolorallocate($image,$rouge,$vert,$bleu);

		// Affichage du texte sur l'image
		$y = 50;
		$x = 65;
		foreach($lines as $line)
		{
			imagestring($image, 2, $x, $y, $line, $couleur); //on écrit horizontalement
			$y += 15;
		}
		
		// Ecriture de l'image sur le disque
		imagepng($image,$graph); //renvoie une image sous format png
		
		// Retour du chemin vers l'image
		return $graph;
	}
}
?>