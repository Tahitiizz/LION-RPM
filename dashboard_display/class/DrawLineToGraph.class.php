<?php
/*
* ajout d'une ligne sur un graphe
*
* @author SPS
* @date 07/05/2009
* @version CB 4.1.0.0
* @since CB 4.1.0.0
*/

include_once dirname(__FILE__)."/../../php/environnement_liens.php";

include_once(MOD_CHARTFROMXML."class/graph.php");
include_once(MOD_CHARTFROMXML."class/SimpleXMLElement_Extended.php");
include_once(MOD_CHARTFROMXML."class/chartFromXML.php");

/*
* classe qui ajoute une ligne sur un graph
* 
* on va tout d'abord lire le xml du graphe, puis rajouter une balise <horizontal_line> dans <properties>
*
* @author SPS
* @version CB 4.1.0.0
* @since CB 4.1.0.0
*/
class DrawLineToGraph {

	/*
	* nom du GTM
	* @var string
	*/
	private $gtmName;
	
	/**
	 * repertoire de stockage des xml
	 * @var string
	 */
	private $tmpDir;
	
	/*
	 * document xml
	 * var DOMDocument
	 **/
	private $xmlDocument;
	
	/*
	* constructeur
	* @param string $xmlFile nom du fichier xml
	* @param string $tmpDir repertoire de stockage
	**/
	public function __construct($gtmName, $tmpDir) {
		
		$this->setGtmName($gtmName);
		$this->setTmpDir($tmpDir);
		
		//on charge le xml depuis DOM
		$document = new DOMDocument();
		$document->load($tmpDir.$gtmName.".xml");
		
		$this->setXmlDocument($document);
	}
	
	/**
	* setter du nom de fichier xml
	* @param string $xmlFile nom du xml
	**/
	public function setGtmName($gtmName) {
		$this->gtmName = $gtmName;
	}
	
	/**
	 * setter du repertoire de stockage
	 * @param string $tmpDir repertoire
	 **/
	public function setTmpDir($tmpDir) {
		$this->tmpDir = $tmpDir;
	}
	
	/**
	 * setter du document
	 * @param DOMDocument document xml charge
	 **/ 
	public function setXmlDocument($document) {
		$this->xmlDocument = $document;
	}
	
	/**
	 * ajout de la ligne
	 * @param int $value ordonnee de la ligne
	 * @param string $legend legende de la ligne
	 * @param string $color couleur de la ligne
	 * @param string $yaxis axe vertical auquel est associe la ligne
	 **/ 
	public function addLine($value,$legend,$color,$yaxis) {
		
		//on cree l'element a ajouter (balises horizontal_line)
		$node = $this->xmlDocument->createElement('horizontal_line',$value);
		//ajout des attributs pour cet element
		$node->setAttribute('legend',$legend);
		$node->setAttribute('color',$color);
		$node->setAttribute('yaxis',$yaxis);
		
		//on parcourt le xml avec xpath
		$xpath = new DOMXPath($this->xmlDocument);  
		
		//on cherche les noeuds properties
		$properties_node = $xpath->query("//properties");
		//on parcourt les noeuds trouves (un seul noeud en realite)
		foreach($properties_node as $pn) {
			//on ajoute le noeud cree plus haut, entre les balises <properties>
			$pn->appendChild($node);
		}
		
		//on enregistre les modifications dansn le xml
		$this->xmlDocument->save( $this->tmpDir.$this->gtmName.".xml" );
	}
	
	
	/**
	 * suppression de la ligne
	 **/
	public function removeLine() {
		//on charge le fichier xml
		$xml = simplexml_load_file($this->tmpDir.$this->gtmName.".xml");		
		
		//on parcourt le fichier xml a la recherche des balises <horizontal_line> et on les supprime
		unset( $xml->properties->horizontal_line );
		
		//on enregistre les modifications dans le xml
		$xml->asXML( $this->tmpDir.$this->gtmName.".xml" );
	}
	
	
	/**
	 * creation de la nouvelle image
	 * @see chartFromXML
	 * @param string $http_dir chemin d'affichage de l'image
	 * @return mixed tableau contenant le texte a mettre a jour et le nom de l'imgae
	 **/
	public function createGraph($http_dir) {
		
		$chart = new chartFromXML( $this->tmpDir.$this->gtmName.".xml" );
		$chart->setBaseUrl(NIVEAU_0.'/png_file/');
		$chart->setBaseDir($this->tmpDir);
		$chart->setHTMLURL(NIVEAU_0);
		// on charge les valeurs par défaut (depuis un autre fichier XML)
		$chart->loadDefaultXML(MOD_CHARTFROMXML . "class/chart_default.xml");
		
		//on rajoute une chiffre aleatoire pour le nom du png afin que le navigateur affiche la nouvelle image 
		$new_graph = $this->gtmName.rand().".png";
		
		//on genere les maps
		$html = $chart->getMap( $new_graph, $this->tmpDir.$new_graph, $http_dir );
		
		// 02/11/2010 OJT : bz18732, on ajoute le conteneur pour le Graph Data Table
		$html .= '<div id=\'gtm_dt_'.$this->gtmName.'\' class=\'dataTableGraph\' style=\'display:none;width:'.$chart->grinfo->properties->width.'px;\'></div>';

		//texte a mettre a jour 
		$tab['text'] = $html;
		//nom de la nouvelle image
		$tab['filename'] = $new_graph;
		
		return $tab;
	}
	
}
?>
