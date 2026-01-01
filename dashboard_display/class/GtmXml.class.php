<?php
/*
	07/05/2009
		- Ajout d'un str_replace pour échaper les simples cotes sinon plantage de IE à cause de JS dans le onmouseover des données en mode ONE
	11/05/2009 GHX
		- Ajout d'un str_replace pour échaper les simples cotes sinon plantage de IE à cause de JS dans le onmouseover des données
	13/05/2009 GHX
		- Suppression de la partie cas particulier avec le split by 3ieme axe
	26/05/2009 GHX
		- Prise en compte des liens vers AA dans la création du fichier XML
	16/07/2009 GHX
		- Ajout des 2 fonctions setGTMAutoScaleY() & setGTMAutoScaleY2()
		- Modification de la fonction BuildPropertiesPart() pour prendre en compte les 2 nouveaux attributs autoY et autoY2 de la balise scale
	22/07/2009 GHX
		- Ajout d'une nouvelle balise dans les propriétées pour avoir le nom du graphe et pour pouvoir corriger le BZ 10688
	03/08/2009 GHX
		- Encodage des éléments html des labels
	17/08/2009 GHX
		- (Evo) Prise en compte qu'il peut y avoir plusieurs KPI/RAW identiques dans un graphe
	12:14 14/10/2009 SCT
		- Ajout de la gestion de la couleur sur les éléments réseaux
	26/11/2009 MPR
		- Correction du bug 13052 : On définit le mode soit Investigation soit Dashboard OT
	12/01/2010 - MPR 
		- Correction du bug 13960 : Récupération du group table du raw/kpi concerné
	14:00 13/01/2010 SCT
		- BZ 13707 : Erreur lors du passage d'un graphe OT vers ONE en cliquant sur un pie
	22/01/2010 NSE 
		- bz 13873 : manque le lien vers AA en mode Over Network
	23/02/2010 - MPR :
		- BZ 14390 - pas de restriction des liens vers AA pour les familles activées
	04/03/2010 - BBX : 
		- BZ 14613 - Dans la fonction BuildDatasPart, on remplace le for par un foeach plus rapide.
		- BZ 14613 - Dans la fonction BuildDatasPart, il faut sortir le bloc avec la fonction "checkLinktoAAForNA" de la sous-boucle
	05/03/2010 BBX
		- Ajout de la méthode "manageConnections"
		- Utilisation de la méthode "manageConnections" au lieu de DatabaseConnection afin d'éviter les instances redondantes
	08/03/2010 BBX
		- Suppression de la méthode "manageConnections"
		- Utilisation de la méthode "Database::getConnection" à la place.
	09/06/10 YNE/FJT : SINGLE KPI
	11/06/10 FJT : bz 15983 mauvais affichage popup navigation OT/ONE via clic sur GTM
        27/07/2010 BBX :
             - BZ 15716 : Modification de la condition d'affichage des liens vers AA
                            Désormais, lien affiché si 1er ET 3ème actif (ou 1er actif si pas de 3ème axe)
  
   06/06/2011 MMT DE 3rd Axis change le format de selection NE 3eme axe -> meme que le 1er

   4/01/11 MMT DE Xpert 606
			use Xpert Dashboard manager to create links to AA and Xpert
 *
 * OJT DE SizeUnitConversion
 * OJT  DE Aircel BH
 * 17/12/2012 GFS : BZ#31083 - [Busy Hour]: Tooltips are incorrect for Single KPI Graph
 * 22/05/2013 : Link to Nova Explorer
*/
?>
<?php

/**
 * Classe GtmXml
 *
 * Cette classe permet de construire le fichier xml d'un GTM à partir de sa définition et de la valeur de ses éléments
 *
 * @package Dashboard
 * @author BAC b.audic@astellia.com
 * @version 1.0.0
 * @copyright 2008 Astellia
 *
 */

class GtmXml
{
	private $gtmId;
	private $gtmProperties;
	private $gtmData;
	private $gtmBHData;
	private $gtmDataLink;
	private $gtmTabTitle;
	private $gtmXAxis;
	private $gtmSplitBy;
	private $gtmType;
	// Single Kpi
	private $tab_ne = array();
	private $na_axe1;
	private $na_axe3;

	// 16:46 26/05/2009 GHX
	// Tableau de données sur les liens vers AA
	private $gtmDataLinkAA;
        //CB 5.3.1 : Link to Nova Explorer
        private $gtmDataLinkNE;
	
	// 09:49 16/07/2009 GHX
	// Ajout des 2 propriétées suivantes
	private $gtmScaleY = 0;
	private $gtmScaleY2 = 0;
	// 15:03 08/10/2009 SCT : ajout de la couleur sur les NE pour les PIES
	private $gtmDataNEColor;

		
	const ROAMING_NB_ELTS = 10;

	private $xml;
	
	// Mémorise les instances de connexions ouvertes
	private static $connections = Array();

        // OJT DE SizeUnitConversion
        // Tableau associatif contenant les réference d'unités de mesures en
        // octets ou bit. Initialisé dans la méthode BuildPropertiesPart
        protected $_sizeUnitRef = array();

        // Tableau de réference pour les unités de mesure de l'axe de gauche
        protected $_sizeUnitLeft = array();

        // Tableau de réference pour les unités de mesure de l'axe de droite
        protected $_sizeUnitRight = array();

        // Determine le nombre de digits pour l'arrondi (maj possible via le .ini)
        protected $_sizeUnitRound = 2;

        // 22/11/2011 BBX
        // BZ 24764 : correction des notices php
        protected $datas_extra = array();


        // Chaîne de caractères utilisée pour séparer le label de la BH sur l'axe des abscisses
        const BH_ABS_SEPARATOR = ' - ';

        // Chemin du fichier INI de référence contenant tous les types d'unités
        // de mesure en octets ou bits
        const SIZE_UNIT_FILE_REF = 'sizeUnit.ini';

	/**
	 * Constructeur de la classe
	 *
	 * @param int $gtm_id identifiant du GTM
	 */

	public function __construct($gtm_id)
	{
		$this->gtmId = $gtm_id;
	}

	// Setters

	/**
	 * Définition des données du GTM
	 *
	 * @param array $gtm_data données du GTM
	 */

	public function setGTMData($gtm_data)
	{
		$this->gtmData = $gtm_data;
	}

	/**
	 * Définition des données bh du GTM
	 *
	 * @param array $gtm_bh_data données bh du GTM
	 */

	public function setGTMBHData($gtm_bh_data)
	{
		$this->gtmBHData = $gtm_bh_data;
	}

	/**
	 * Définition des liens des données du GTM
	 *
	 * @param array $gtm_data_link liens des données du GTM
	 */

	public function setGTMDataLink($gtm_data_link)
	{
		$this->gtmDataLink = $gtm_data_link;
	}

	/**
	 * Définition de la couleur d'un NE
	 * 14:50 08/10/2009 SCT : ajout de la gestion de la couleur sur les NE
	 *
	 * @param array $gtm_data_ne_color couleur du NE pour la GTM
	 */

	public function setGTMDataNeColor($gtm_data_ne_color)
	{
		$this->gtmDataNEColor = $gtm_data_ne_color;
	}

	/**
	 * Définition des liens vers AA
	 *
	 * @author GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param array $gtm_data_link_aa tableau de données sur les liens vers AA
	 */
	public function setGTMDataLinkAA ( $gtm_data_link_aa )
	{
		$this->gtmDataLinkAA = $gtm_data_link_aa;
	} // End function setGTMDataLinkAA
        
        //CB 5.3.1 : Link to Nova Explorer
        public function setGTMDataLinkNE ( $gtm_data_link_ne )
	{
		$this->gtmDataLinkNE = $gtm_data_link_ne;
	} // End function setGTMDataLinkNE
	
	/**
	 * Définition du titre du GTM (balise <tabtitle> du XML)
	 *
	 * @param string $gtm_tabtitle titre du GTM
	 */

	public function setGTMTabTitle($gtm_tabtitle)
	{
		$this->gtmTabTitle = $gtm_tabtitle;
	}

	/**
	 * Définition de l'axe x du GTM (balise <xaxis> du XML)
	 *
	 * @param array $gtm_xaxis liste des valeurs de <xaxis>
	 */

	public function setGTMXAxis($gtm_xaxis)
	{
		$this->gtmXAxis = $gtm_xaxis;
	}

	/**
	 * Définition du raw / kpi de split du pie
	 *
	 * @param array $gtm_splitby liste des propriétés du raw / kpi de split
	 */

	public function setGTMSplitBy($gtm_splitby)
	{
		$this->gtmSplitBy = $gtm_splitby;
	}

	/**
	 * Définition du type du GTM
	 *
	 * @param string $gtm_type type du GTM
	 */

	public function setGTMType($gtm_type)
	{
		$this->gtmType = $gtm_type;
	}
	
	/**
	 * Définition du mode dud dashboard
	 *
	 * @param string $mode ( OverTime ou Over Network Element )
	 */

	public function setGTMMode($mode)
	{
		$this->dashMode = $mode;
	}

	/**
	 * Définition du message du GTM (affiché lorsqu'il n'existe pas de données dans le GTM)
	 *
	 * @param string $gtm_message message à afficher dans le GTM
	 */

	public function setGTMMessage($gtm_message)
	{
		$this->gtmMessage = $gtm_message;
	}

	/**
	 * Définition des propriétés du GTM
	 *
	 * @param array $gtm_properties liste des propriétés du GTM. Optionnel, si ce paramètre est non défini, on va aller chercher les propriétés du GTM en base via le modèle de celui-ci
	 */

	public function setGTMProperties($gtm_properties = '')
	{
		if ($gtm_properties == '')
		{
			$gtm_model = new GTMModel($this->gtmId);
			$this->gtmProperties = $gtm_model->getGTMProperties();
		}
		else
		{
			$this->gtmProperties = $gtm_properties;
		}
	}

	/**
	 * Construction du contenu de la balise <xaxis_labels> du document XML
	 *
	 * @param object $dom noeud parent de la balise <xaxis_labels>
	 * @return object balise <xaxis_labels> au format DOMDocument
	 */

	public function setGTMTa($ta_level)
	{
		$this->ta_level = $ta_level;
	}
	
	/**
	 * 4/01/11 MMT DE Xpert 606
	 *
	 * set the Xpert Dashboard manager who creates Links to Xpert from GTM
	 * @param XpertDashboardManager $xpertDashMger
	 */
	public function setXpertDashboardManager($xpertDashMger)
	{
		$this->xpertDashMger = $xpertDashMger;
	}

	/**
	 * 25/01/2011 MMT DE Xpert
	 * return true if Xpert Dashboard management is on and instanciated
	 * @return bool
	 */
	public function hasXpertDashboardManager()
	{
		return !empty($this->xpertDashMger);
	}

	/**
	 * 25/01/2011 MMT DE Xpert move getGTMonmouseoverJScall function from XpertDashboardManager here
	 * For the case where xpertDashMger is not instanciated
	 * Get the Javascript call for the onmouseover event of the GTM, this JS will add/remove contextual menus
	 * to add AA adn/or Xpert Links depending on the hovered data
	 * the JS code is in js/fenetres_volantes.js
	 * @return string JS method to call
	 */
	public function getGTMonmouseoverJScall(){

		// get the basic pop params title, messsage and AA values
                //$popParams = "'\".str_replace(\"'\", \"\'\", \"\$x\$bh_infos \$extra\").\"', '\$label=\".str_replace(\"'\", \"\'\", \"\$y\").\"', '\$link_aa' ";
                //CB 5.3.1 : Link to Nova Explorer
		$popParams = "'\".str_replace(\"'\", \"\'\", \"\$x\$bh_infos \$extra\").\"', '\$label=\".str_replace(\"'\", \"\'\", \"\$y\").\"', '\$link_aa', '\$link_ne' ";

		// add Xpert if allowed
		if($this->hasXpertDashboardManager() && $this->xpertDashMger->isGTMLinkAllowed()){
			$popParams .= ",'\$link_xpert'"; // Xpert link URL already in the GTM Xml
			$popParams .= ",'".__T('A_GTM_XPERT_CONTEXTUAL_MENU_LABEL')."'"; // Menu label
		}
		$ret = "popGTMwithLinks(".$popParams.");";
		return $ret;
	}

	/**
	 * Construction du contenu de la balise <xaxis_labels> du document XML
	 *
	 * @param object $dom noeud parent de la balise <xaxis_labels>
	 * @return object balise <xaxis_labels> au format DOMDocument
	 */

	public function setGTMTaValue($ta_value)
	{
		$this->ta_value = $ta_value;
	}
	
	/**
	 * Construction du contenu de la balise <xaxis_labels> du document XML
	 *
	 * @param object $dom noeud parent de la balise <xaxis_labels>
	 * @return object balise <xaxis_labels> au format DOMDocument
	 */

	public function setGTMNa($na_level)
	{
		$this->na_level = $na_level;
	}
	
	/**
	 * Construction du contenu de la balise <xaxis_labels> du document XML
	 *
	 * @param object $dom noeud parent de la balise <xaxis_labels>
	 * @return object balise <xaxis_labels> au format DOMDocument
	 */

	public function setGTMNaAxe3($na_axe3)
	{
		$this->na_axe3 = $na_axe3;
	}
	
	/**
	 * Définition de l'élément réseau
	 *
	 * @param string $gtm_ne élément réseau
	 */

	public function setGTMNe($gtm_ne)
	{
		$this->gtmNe = $gtm_ne;
	}
	
	/**
	 * Définition du tableau des 3 pires NE
	 *
	 * @param array $tab_ne élément réseau
	 */
	public function setGTMNeTab($tab_ne){
		$this->tab_ne = $tab_ne;
	}
	
	/**
	 * Active l'auto scale sur du graphe pour l'ordonné à gauche. C'est à dire que l'ordonnée minimum sera basé sur les données et non forcé à zéro
	 *
	 *	16/07/2009 GHX
	 *		- A
	 *
	 * @param int $scale 
	 */
	public function setGTMAutoScaleY ( $scale )
	{
		$this->gtmScaleY = $scale;
	}
	
	/**
	 * Active l'auto scale sur du graphe pour l'ordonné à droite. C'est à dire que l'ordonnée minimum sera basé sur les données et non forcé à zéro
	 *
	 * @param int $scale 
	 */
	public function setGTMAutoScaleY2 ( $scale )
	{
		$this->gtmScaleY2 = $scale;
	}
	
	private function extrapolationData(){

		$gtm_model = new GTMModel($this->gtmId);

		include_once(REP_PHYSIQUE_NIVEAU_0."dashboard_display/class/ExtrapolationData.class.php" );
		
		$lst_rawkpi 	 = array();
		$families 		 = array();
		$edw_group_table = array();
		$products		 = array();
		
		// On récupère tous les raw/kpi du graphe
		$nb_rawkpi=0;

		foreach( $this->gtmProperties['data'] as $tab )
		{
			
			switch($tab['class_object']){
				case "kpi":
					$rawkpi = new KpiModel();
				break;
				case "raw": 
					$rawkpi = new RawModel();	
				break;
			}
			
			// maj 14:12 12/01/2010 - MPR : Correction du bug 13960 - Récupération du group table du raw/kpi concerné
			$db = Database::getConnection($tab['id_product']);
			$group_table[] = $rawkpi->getGroupTableFromId($tab['id_elem'],$db);
			
			$lst_rawkpi[ $tab['class_object'] ]['id_elem'][] = $tab['id_elem'];
			$lst_rawkpi[ $tab['class_object'] ]['id_product'][] = $tab['id_product'];
			
			$nb_rawkpi++;
		}
		
		// On récupère les produits + familles + group table du graphe
		$gtmProductsAndFamilies = $gtm_model->getGTMProductsAndFamilies();
		$nb_group_table = count($group_table);
		foreach($gtmProductsAndFamilies as $id_prod=>$tab_families){
		
			$products[] = $id_prod;
			foreach($tab_families as $family){
				$i = 0;
				$find = false;
				// On récupère dans l'ordre les familles + produits en fonction des raws/kpis du graphe
				while(!$find and $i<$nb_group_table ){
					if( $group_table[$i] == $family['edw_group_table']){
						$families[$id_prod][$i] = $family['family'];
						$edw_group_table[$id_prod][$i] = $family['edw_group_table'];
						$find = true;
					}
					$i++;
				}	
			}
		}
		
		$extra_data = new ExtrapolationData();
		//26/11/2009 MPR - Correction du bug 13052 : On définit le mode soit Investigation soit Dashboard OT
		$extra_data->setMode("dash");
		
		// Récupération des infos nécessaires pour compléter les données si besoin est
		$extra_data->setProducts($products);				
		$extra_data->setFamilies($families);
		$extra_data->setParamsActivation();
		
		if( $extra_data->activate ){
		
			// Initialisation des éléments de topo
			$extra_data->setNa( $this->na_level );
			
			if( isset($this->na_axe3) )
				$extra_data->setNaAxe3( $this->na_axe3 );
				
			$extra_data->setNaValues( $this->gtmNe );
			
			// Initialisation de la TA et du ou des raws/kpis
			$extra_data->setTa( $this->ta_level );
			$extra_data->setTaValues( $this->gtmXAxis['values'] );
			$extra_data->setRawKpi( $lst_rawkpi );
			$extra_data->setGroupTable($edw_group_table);
			
			// Récupération des données 
			$extra_data->setDatas( $this->gtmData );
			
			// Extrapolation des données
			$this->gtmData = $extra_data->completeDatas();
			$this->datas_extra = $extra_data->getDatasExtra();
			
		}
		
		return $this->gtmData;
	}
	
	/**
	 * Permet de construire le fichier XML du GTM
	 *
	 */

	public function Build()
	{
		// 1 - Création du nouvel objet XML. On utilise pour ce faire la classe Dom de PHP

		$dom = new DOMDocument('1.0','utf-8');

		// active un joli export indenté du XML -- SLC 29/04/2009
		$dom->formatOutput = true;

		// Création de la racine du document

		$chart = $dom->createElement('chart');
		$dom->appendChild($chart);

		// 2 - Définition des propriétés du GTM

		$pptes = $this->BuildPropertiesPart($dom);
		$chart->appendChild($pptes);

		// 3 - Définition des informations de l'axe horizontal

		if (count($this->gtmXAxis) > 0)
		{
			$xaxis = $this->BuildXAxisPart($dom);
			$chart->appendChild($xaxis);
		}

		// 4 - Définition des données du GTM

		if (count($this->gtmData) > 0)
		{
			$datas = $this->BuildDatasPart($dom);
			$chart->appendChild($datas);
		}

        // OJT DE SizeUnitConversion
        // 5 - Conversion des unités de taille (bit/byte)
                // Cette fonction est uniquement appellée si un des axes contient
                // des valeurs Bit/Bytes ou Bit/s ou Bytes/s
                if( $this->_sizeUnitLeft != null || $this->_sizeUnitRight != null )
                {
                    $this->convertDatasPart( $dom );
                }
		$this->xml = $dom;
	}

	/**
	 * Retourne le document XML
	 *
	 * @return object le document XML au format DOMDocument
	 */

	public function getXML()
	{
		return $this->xml;
	}

	/**
	 * Permet de sauvegarder le document XML dans un fichier
	 *
	 * @param string $path url du fichier dans lequel sauvegarder le XML
	 * @return boolean état de création du fichier (true : crée, false : non crée)
	 */

	public function SaveXML($path)
	{
		return ($this->xml->save($path));
	}

	/**
	 * Construction du contenu de la balise <properties> du XML du GTM
	 *
	 * @param object $dom noeud parent de la balise <properties>
	 * @return object balise <properties> au format DOMDocument
	 */

	private function BuildPropertiesPart($dom)
	{
		// Définition du noeud "properties"

		$pptes = $dom->createElement('properties');

		// Défintion du titre du GTM et ajout de celui-ci au noeud "properties"

		//$title = $dom->createElement('title', $this->gtmProperties['page_name']);
		//$pptes->appendChild($title);

		// Définition du "tabtitle" (texte collé au dessus du GTM) et ajout de celui-ci au noeud "properties"

		$tabtitle = $dom->createElement('tabtitle');

		$title_dom = new DomDocument();
		$title_dom->loadXML('<root>'.utf8_encode($this->gtmTabTitle).'</root>');

		foreach ($title_dom->getElementsByTagName('text') as $text)
		{
			$text_node = $dom->importNode($text, true);
			$tabtitle->appendChild($text_node);
		}

		$pptes->appendChild($tabtitle);

		// Définition du reste des propriétés recensées dans '$this->gtmProperties'

		// 09:49 16/07/2009 GHX
		// Suppression de la propriété scale du tableau, on le fait après
		$tab_pptes = array('width' => 'graph_width', 'height' => 'graph_height', 'legend_position' => 'position_legende', 'left_axis_label' => 'ordonnee_left_name', 'right_axis_label' => 'ordonnee_right_name', 'type' => 'object_type');

                // 03/05/2011 OJT : Test si l'axe de gauche ou de droite contient
                // des unités de mesures en octets ou bits
                if( get_sys_global_parameters( 'enable_size_unit_conversion', 1 ) == 1 )
                {
                    if ( file_exists( dirname( __FILE__ ).'/'.self::SIZE_UNIT_FILE_REF ) )
                    {
                        $this->_sizeUnitRef = parse_ini_file( dirname( __FILE__ ).'/'.self::SIZE_UNIT_FILE_REF, true );
                        if ( $this->_sizeUnitRef !== false )
                        {
                            $leftAxisLabel  = trim( strtolower( $this->gtmProperties['ordonnee_left_name'] ) );
                            $rightAxisLabel = trim( strtolower( $this->gtmProperties['ordonnee_right_name'] ) );

                            // Pour toutes les sections du .INI, on recherche si le label est présent
                            $i = 0;
                            foreach ( $this->_sizeUnitRef as $section=>$unit )
                            {
                                // Exclusion de la section contenant la précision de l'arrondie
                                if( $section != "round_presicion" )
                                {
                                    $current  = 0;
                                    $treshold = 1024; // Par défaut le seuil est à 1024

                                    // Initialisation du seuil
                                    if( substr( $section, 0, 3 ) == "bit" )
                                    {
                                        // Pour les bit,bit/s 1kbits = 1000 bits
                                        $treshold = 1000;
                                    }

                                    foreach ( $unit as $unitRefName=>$strUnitList )
                                    {
                                        $arrUnitList = explode( ',', $strUnitList );

                                        // Test sur l'axe de gauche
                                        if( strcasecmp( $leftAxisLabel, $unitRefName ) == 0  || in_array( $leftAxisLabel, $arrUnitList ) )
                                        {
                                            // On change immédiatement la legende (par réference)
                                            $this->gtmProperties['ordonnee_left_name'] = $unitRefName;

                                            // On mémorise les unités à utiliser et la courante
                                            $this->_sizeUnitLeft['list']      = array_keys( $unit );
                                            $this->_sizeUnitLeft['current']   = $current;
                                            $this->_sizeUnitLeft['treshold'] = $treshold;
                                        }

                                        // Test sur l'axe de droite
                                        if( strcasecmp( $rightAxisLabel, $unitRefName ) == 0 || in_array( $rightAxisLabel, $arrUnitList ) )
                                        {
                                            // On change immédiatement la legende (par réference)
                                            $this->gtmProperties['ordonnee_right_name'] = $unitRefName;

                                            // On mémorise les unités à utiliser et la courante
                                            $this->_sizeUnitRight['list']     = array_keys( $unit );
                                            $this->_sizeUnitRight['current']  = $current;
                                            $this->_sizeUnitRight['treshold'] = $treshold;

                                        }
                                        $current++;
                                    }
                                }
                                else if( isset( $unit['digit'] ) && is_numeric( $unit['digit'] ) )
                                {
                                    // Pour la section [round_presicion], on sauvegarde le nombre de digits
                                    $this->_sizeUnitRound = intval( $unit['digit'] );
                                }
                                else
                                {
                                    // Dans ce cas on laisse l'arrondie par défaut (problème dans le fichier .ini)
                                }
                            }
                        }
                    }
                } // fin test enable_size_unit_conversion

		foreach ($tab_pptes as $key=>$value) {

			$gtm_ppte = $dom->createElement($key, $this->gtmProperties[$value]);

			$pptes->appendChild($gtm_ppte);
		}

		// 09:50 16/07/2009 GHX
		// Propriété scale
		$gtm_ppte = $dom->createElement('scale', $this->gtmProperties['scale']);
		// Prise en compte des 2 nouveaux attributes de la balise
		$gtm_ppte->setAttribute('autoY', $this->gtmScaleY);
		$gtm_ppte->setAttribute('autoY2', $this->gtmScaleY2);
		$pptes->appendChild($gtm_ppte);
		
		// 14:12 22/07/2009 GHX
		// Ajout d'une nouvelle balise dans les propriétés pour pouvoir corriger le BZ 10688
		$gtm_ppte = $dom->createElement('graph_name', $this->gtmProperties['page_name']);
		$pptes->appendChild($gtm_ppte);
		
		// Définition des propriétés fixes du GTM

		// Normalement, ces valeurs sont définies par défaut dans la classe de génération des GTMs à partir du XML

		return $pptes;
	}

	/**
	 * Construction du contenu de la balise <xaxis_labels> du document XML
	 *
	 * @param object $dom noeud parent de la balise <xaxis_labels>
	 * @return object balise <xaxis_labels> au format DOMDocument
	 */

	private function BuildXAxisPart($dom)
	{
        // OJT  DE Aircel BH
        $xAxisLabelsTitle = $this->gtmXAxis['title'];
        $gtmSortBy        = null;

        // Si le graph est de type BH
        if( isset( $this->gtmBHData ) )
        {
            // On affiche le Raw/Kpi utlisé pour le calcul
            $gtmModel  = new GTMModel( $this->gtmId );
            $gtmSortBy = $gtmModel->getGTMSortBy();
            $famModel  = new FamilyModel( $gtmSortBy['family'], $gtmSortBy['product'] );
            $bhInfos   = $famModel->getBHInfos();

            // Test si la BH est défini, sinon on laisse l'affichage intact
            if( count( $bhInfos ) > 0 )
            {
                if( strtolower( $bhInfos['bh_indicator_type'] ) == 'kpi' )
                {
                    $rawKpiModel = new KpiModel();
                    $field       = 'kpi_name';
                }
                else
                {
                    $rawKpiModel = new RawModel();
                    $field       = 'edw_field_name';
                }
                $bhRawKpiId    = $rawKpiModel->getIdFromSpecificField( $field, $bhInfos['bh_indicator_name'], Database::getConnection( $gtmSortBy['product'] ) );
                $bhRawKpiLabel = $rawKpiModel->getLabelFromId( $bhRawKpiId, Database::getConnection( $gtmSortBy['product'] ) );
                $xAxisLabelsTitle .= " (BH calculated on {$bhRawKpiLabel} ".ucfirst( strtolower( $bhInfos['bh_indicator_type'] ) ).")";
            }
        }

		$xaxis = $dom->createElement('xaxis_labels');
        $xaxis->setAttribute("title", $xAxisLabelsTitle );
		
		print_r($xaxis,"XAXIS");
		$values = $this->gtmXAxis['values'];

		// 13/05/2009 GHX : Suppression de la partie cas particulier avec le split by 3ieme axe
        $countValues = count( $values );
        for ( $i = 0 ; $i < $countValues ; $i++ )
        {
        $xaxis_sub = $dom->createElement('label');
            $cdataValue = $values[$i];

            // On regrade si des info BH sont présentes
            if( isset( $this->gtmBHData[$i] ) )
            {
                // On récupère la valeur de la BH dans le KPI utilisé pour le sort by
                $sufix = $this->gtmBHData[$i][$gtmSortBy['type']][$gtmSortBy['product']][$gtmSortBy['id']]['BH'];

                // On tronque la partie commune entre la légende actuelle et la value de la BH
                if( strpos( $sufix, $cdataValue ) === 0 )
                {
                    $sufix = substr( $sufix, strlen( $cdataValue ) );
                }
                // 17/12/2012 GFS : BZ31083 - [Busy Hour]: Tooltips are incorrect for Single KPI Graph
                //$cdataValue .= self::BH_ABS_SEPARATOR.trim( $sufix );
            }

            // 03/08/2009 GHX : Encodage des éléments html des labels
            // 26/06/2012 ACS BZ 27409 Upload special charactere in the topology make the display of graph failed
            $xaxis_sub->appendChild($dom->createCDATASection(utf8_encode($cdataValue)));
			$xaxis->appendChild($xaxis_sub);
		}
		return $xaxis;
	}

	/**
	 * Permet de récupérer la famille à partir d'un ID
	 * @param String $type = raw ou kpi
	 * @param integer $id
	 * @param Integer $id_prod
	 * @return String family
	 */
	private function getFamilyFromId($type,$id,$id_prod)
	{
		$db = Database::getConnection( $id_prod );
		if($type == 'raw')
		{
			$rawkpi = new RawModel();
			
		}
		else
		{
			$rawkpi = new KpiModel();
		}
		$edw_group_table = $rawkpi->getGroupTableFromId($id, $db);
		
		$family = getFamilyFromGroupTable($edw_group_table, $id_prod);
		
		return $family;
	}
	
	/**
	 * Fonction qui va chercher la couleur des NE pour le singleKPI
	 * si la couleur est signifié dans edw_object_ref, alors on prend celle-ci
	 * Sinon on prend dans un tableau pré-défini
	 * @param $tab_ne
	 * @param $db
	 * @return tableau[le ne] = sa couleur
	 */
	private function getColorNE($tab_ne,$db){
		

		$tab_result[]=array();
		//le remplissage de ce tableau est conforme à la spec TCB50_DSP_Single_Kpi_RE.doc
		$tab_color = Array(
			'1' => '#0000ff','2' => '#00ff80','3' => '#ff0000','4' => '#ffff00',
			'5' => '#8000ff','6' => '#00ffff','7' => '#00ff00','8' => '#ff0080',
			'9' => '#0080ff','10' => '#800000','11' => '#808080','12' => '#80ff00',
			'13' => '#000080','14' => '#ff8000','15' => '#008080','16' => '#ff00ff',
			'17' => '#000000','18' => '#000080','19' => '#008040','20' => '#800000',
			'21' => '#808000','22' => '#400080','23' => '#008080','24' => '#008000',
			'25' => '#800040','26' => '#004080','27' => '#400000','28' => '#404040',
			'29' => '#408000','30' => '#000040','31' => '#804000','32' => '#004040',
			'33' => '#800080','34' => '#0000C0','35' => '#00C060','36' => '#C00000',
			'37' => '#C0C000','38' => '#6000C0','39' => '#00C0C0','40' => '#00C000',
			'41' => '#C00060','42' => '#0060C0','43' => '#600000','44' => '#606060',
			'45' => '#60C000','46' => '#000060','47' => '#C06000','48' => '#006060',
			'49' => '#C000C0','50' => '#404040',
			);
		$i=1; //on commence à 1 car le tableau commence à 1 comme dans la spec
		foreach($tab_ne as $ne){			
			//on va chercher dans edw_object_ref si la couleur a été définie
			//si axe 3, on prend la couleur de l'axe 3
			//sinon on prend celle du ne de l'axe 1
			if( isset($this->na_axe3) ){
				$label_id_ne = explode("|",$ne);
				$label_ne_axe1 = $label_id_ne[0];
				$label_ne_axe3 = $label_id_ne[2];
				
				$query = "SELECT eor_color from edw_object_ref where eor_obj_type = '".$this->na_axe3."' and eor_id = '$label_ne_axe3'";
				
			}else{
				$query = "SELECT eor_color from edw_object_ref where eor_obj_type = '".$this->na_level."' and eor_id = '$ne'";
			}
			
			$result = $db->execute($query);
			$row = pg_fetch_array($result);
			
			//si la couleur a été renséignée, alors on prend celle-ci si elle n'existe pas déjà dans le tableau résultat
			//si la couleur n'a pas été renseignée, alors on prend dans le tableau $tab_color créé ci-dessus
			//et on prend les couleurs dans l'ordre
			if (pg_num_rows($result)>0 && $row[0]!=""){
				//si la valeur existe déjà, alors on prend dans le tableau (cas des couples 3 axes ayant le même 3ème axe)
				if (in_array($row[0],$tab_result)){
					$tab_result[$ne]= $tab_color[$i];
					$i++;
				}
				else{
					$tab_result[$ne] = $row[0];
				}
			}else{
				$tab_result[$ne] = $tab_color[$i];
				$i++;
				//si on atteint le nombre de 50 pour les couleurs => on repart de 0 dans le tableau
				if ($i==51){
					$i=1;
				}
			}
			
			
		}
		return $tab_result;
	}
	
	/**
	 * Cette fonction (uniquement appelé pour SingleKPI) permet de recréer le lien qui permet de passer de OT à ONE
	 * car c'est un traitement spécial pour les SingleKPI
	 * @param $ne le NE courant (pour 2ème et 3ème axe)
	 * @param $data_link le lien qui a pu être construit (une seule fois pour single KPI, c'est pour ça qu'on doit le reconstruire pour qu'il contiennent tous les NE)
	 * @param $dashMode = overtime ou ONE
	 * @param $datas_extra pour l'extrapolation de données
	 * @param $gtmDataLink
	 * @return String renvoie le lien utile pour passer de Ot à ONE et inversement
	 */
	private function getDataLink($ne,$data_link,$dashMode,$datas_extra,$gtmDataLink){
		// Informations du lien
		if( $dashMode == 'overtime')
		{		
				
			// maj 11:46 18/11/2009 : Si la donnée est extrapolée on supprime le lien
			if ($datas_extra or $gtmDataLink == ""){
				$data_link = "#";
			}else{ //construction du lien pour passer en ONE
				//si on est au niveau min de Time (en Hour par exemple), alors il ne faut pas encoder l'URL, car sinon ça ne marche pas...
				//on détecte le mode min en Time en fonction de l'entete de l'URL, à savoir index.php si niveau min
				$tab_debut = explode("?",$data_link);
				$link_debut = $tab_debut[0];
				$reste = $tab_debut[1];
				
				//exemple : javascript:open_window('gtm_navigation.php?id_dash=dshd.1024.03.001%26na_axe1%3Dnod1nod2%26ne_axe1%3Dnod1nod2%7C%7CISUP_SWITCH_1002%3A%3A%3AISUP_SWITCH_1018%26na_axeN%3Dlplmn%26ne_axeN%3D33401&ta=day&ta_value=20100307&mode=overtime&top=3&sort_by=raw@raws.1024.isuping.00004@1@gtms.1024.03.00001@desc&id_menu_encours=menu.dshd.1024.03.001.01')
				if ($link_debut != "index.php"){
					
					$tab_dash = explode("%26",$reste);
					$id_dash = $tab_dash[0];
					$tab_infos_ta = explode("&",$reste);
					$ta = $tab_infos_ta[1];
					$ta_value = $tab_infos_ta[2];
					$mode = $tab_infos_ta[3];
					$top = $tab_infos_ta[4];
					$sort_by = $tab_infos_ta[5];
					$id_menu = $tab_infos_ta[6];
					if (isset($this->na_axe3)){//mettre les axeN
						$tab_ne = explode("|s|",$ne);
						$ne_axe1 = $tab_ne[0];
						$ne_axeN = $tab_ne[1];
						//06/06/2011 MMT DE 3rd Axis change le format de selection NE 3eme axe -> meme que le 1er
						$data_link_ne = urlencode("&na_axe1=".$this->na_level."&ne_axe1=".$this->na_level.
								"||".$ne_axe1."&na_axeN=".$this->na_axe3."&ne_axeN=".$this->na_axe3."||".$ne_axeN);
					}else{
						$data_link_ne = urlencode("&na_axe1=".$this->na_level."&ne_axe1=".$this->na_level.
								"||".$ne);
					}
				}else{
					//exemple : index.php?id_dash=dshd.1024.03.001&na_axe1=nod1nod2&ne_axe1=nod1nod2||ISUP_SWITCH_1004:::ISUP_SWITCH_1011&na_axeN=lplmn&ne_axeN=33401&ta=hour&ta_value=2010032022&mode=overnetwork&top=3&sort_by=raw@raws.1024.isuping.00004@1@gtms.1024.03.00001@desc&id_menu_encours=menu.dshd.1024.03.001.01
					$tab_dash = explode("&",$reste);
					$id_dash = $tab_dash[0];
					if (isset($this->na_axe3)){
						$ta = $tab_dash[5];
						$ta_value = $tab_dash[6];
						$mode = $tab_dash[7];
						$top = $tab_dash[8];
						$sort_by = $tab_dash[9];
						$id_menu = $tab_dash[10];
						$tab_ne = explode("|s|",$ne);
						$ne_axe1 = $tab_ne[0];
						$ne_axeN = $tab_ne[1];
						//06/06/2011 MMT DE 3rd Axis change le format de selection NE 3eme axe -> meme que le 1er
						$data_link_ne = "&na_axe1=".$this->na_level."&ne_axe1=".$this->na_level.
								"||".$ne_axe1."&na_axeN=".$this->na_axe3."&ne_axeN=".$this->na_axe3."||".$ne_axeN;
					}else{
						$ta = $tab_dash[3];
						$ta_value = $tab_dash[4];
						$mode = $tab_dash[5];
						$top = $tab_dash[6];
						$sort_by = $tab_dash[7];
						$id_menu = $tab_dash[8];
						$data_link_ne = "&na_axe1=".$this->na_level."&ne_axe1=".$this->na_level.
								"||".$ne;
					}
					
				}
				// bz 15983
				if ($id_menu){				
				$data_link = $link_debut."?".$id_dash.$data_link_ne.
							"&".$ta."&".$ta_value."&".$mode."&".$top."&".$sort_by."&".$id_menu;
				}else{
					$data_link = $link_debut."?".$id_dash.$data_link_ne.
							"&".$ta."&".$ta_value."&".$mode."&".$top."&".$sort_by;//"&".$id_menu;
				}
			}
			
		}else{//mode overnetwork
			if ($gtmDataLink == ""){
				$data_link = "#";
			}else{
				
			}
		}
		return $data_link;
	}
	
	/**
	 * Cette fonction permet la construction du lien vers AA pour les graphes SingleKPI
	 * @param String $naAxe1AA
	 * @param String $neAxe1AA
	 * @param String $naAxe3AA
	 * @param String $neAxe3AA
	 * @param Array $tab_aa
	 * @return String le liend vers AA à ajouter dans les fichiers XML de graph
	 */
	private function getLinkAA($naAxe1AA,$neAxe1AA,$naAxe3AA,$neAxe3AA,$tab_aa){
		foreach($tab_aa as $ta_value => $tab_type){
			foreach($tab_type as $class_object => $tab_class_object){
				foreach($tab_class_object as $id_product => $tab_id_product){
					$tab_final = $tab_id_product;
					$id_elem = array_keys($tab_id_product);
					$link_aa = $tab_id_product[$id_elem[0]];
				}
				
			}
		}
		eval("\$link_aa = \"$link_aa\";");
		
		return $link_aa;
	}
        
        //CB 5.3.1 : Link to Nova Explorer
        private function getLinkNE($naAxe1NE,$neAxe1NE,$naAxe3NE,$neAxe3NE,$tab_ne){
		foreach($tab_ne as $ta_value => $tab_type){
			foreach($tab_type as $class_object => $tab_class_object){
				foreach($tab_class_object as $id_product => $tab_id_product){
					$tab_final = $tab_id_product;
					$id_elem = array_keys($tab_id_product);
					$link_ne = $tab_id_product[$id_elem[0]];
				}
				
			}
		}
		eval("\$link_ne = \"$link_ne\";");
		
		return $link_ne;
	}
	
	
	/**
	 * Construction du contenu de la balise <data> du document XML
	 *
	 * @param object $dom noeud parent de la balise <data>
	 * @return object balise <data> au format DOMDocument
	 */

	private function BuildDatasPart($dom)
	{
		$datas = $dom->createElement('datas');

		$gtm_data = $this->gtmProperties['data'];
		$gtm_same_kpis = $this->gtmProperties['same_kpis'];
		$gtm_same_raws = $this->gtmProperties['same_raws'];
		
                // 22/11/2011 BBX
                // BZ 24764 : correction des notices php
		$gtm_ne 	= isset($this->gtmProperties['index']['ne']) ? $this->gtmProperties['index']['ne'] : null;

		// Extrapolation des données uniquement si nous sommes en mode OVERTIME
		if( $this->dashMode == 'overtime' )
			$this->extrapolationData($gtm_data);
			
		if (strpos($this->gtmProperties['object_type'], "pie") === false) // Cas des graphes
		{
			// On parcours l'ensemble des éléments du graphe
			// Single Kpi
			//Pour les graphes SINGLEKPI, on boucle sur les NE et non sur les KPi/RAW
			if ($this->gtmType=='singleKPI' && $this->dashMode == 'overtime'){
				
				//création de la connexion en fonction du id_produit
				foreach( $this->gtmProperties['data'] as $tab )
				{
                                        // 31/01/2011 BBX
                                        // On remplace new DatabaseConnection() par Database::getConnection()
                                        // BZ 20450
					$db = Database::getConnection($tab['id_product']);
					
					//récupération des couleurs
					$tab_color_ne = $this->getColorNE($this->tab_ne,$db);	
				}				
				
				foreach($this->tab_ne as $ne){
					$color="";
					// Définition de la balise "data"
					$data = $dom->createElement('data');
					
					//si famille 3ème axe, alors il faut décomposer les labels
					if( isset($this->na_axe3) ){
					
						$label_id_ne = explode("|",$ne);
						//récupération du label en fonction de son id
						$label_ne1 = getNELabel($this->na_level, $label_id_ne[0], $values_singleKPI['id_product']);
						$label_ne3 = getNELabel($this->na_axe3, $label_id_ne[2], $values_singleKPI['id_product']);
						//affichage des 2 labels avec un slash en séparation.
						$label_ne = $label_ne1."/".$label_ne3;
						
					}else{
						$label_ne = getNELabel($this->na_level, $ne, $values_singleKPI['id_product']);;
					}
										
					//récupération des valeurs de <data> (pour single KPI il n'y a qu'une ligne dans ce tableau
					foreach($gtm_data as $values_singleKPI){
											
                                                // 31/01/2011 BBX
                                                // On remplace new DatabaseConnection() par Database::getConnection()
                                                // BZ 20450
						$db = Database::getConnection($values_singleKPI['id_product']);
						
						//ici le label est le nom du ne
						$data->setAttribute("label", $label_ne);
						
						$data->setAttribute("type", $values_singleKPI['display_type']);
						
						//si mode bar, la couleur de remplissage doit être le même que celle du contour
						//et transparence à 0.5
						if ($values_singleKPI['display_type']=='bar'){ 
							$data->setAttribute("fill_color", $tab_color_ne[$ne]."@0.5");
						}else{ //si en mode line => toujours la même couleur et transparence pour fill_color
						
							$data->setAttribute("fill_color", "#FFFFFF@1"); //faire un random sur la couleur, une pour chaque NE
						}
						$data->setAttribute("line_design", $values_singleKPI['line_design']);
						$data->setAttribute("stroke_color", $tab_color_ne[$ne]);
						$data->setAttribute("yaxis", $values_singleKPI['position_ordonnee']);
						
						// 4/01/11 MMT DE Xpert 606
						// create JS call to create Menus for AA or Xpert links
						$data->setAttribute("onmouseover", $this->getGTMonmouseoverJScall());
						$data->setAttribute("onmouseout", "kill();");
						
						$data->setAttribute("href", "\$data_link");
						
						
						
						for ($i=0; $i < count($this->gtmData); $i++) {

							$elt_value = $this->gtmData[$i][$values_singleKPI['class_object']][$values_singleKPI['id_product']][$ne];
							
							//////////////// COPIER-COLLER depuis les graphes (voir dessous) mais je ne sais pas ce que ça fait puisque mes tableaux $gtm_same_kpis et $gtm_same_raws sont toujours faux
							//////////////// et qu'il n'y a aucun commentaire pour la partie graphe pour m'aider
							if ( $values_singleKPI['class_object'] == 'kpi' && is_array($gtm_same_kpis) ) // KPI
							{
								
								if ( array_key_exists($values_singleKPI['id_elem'].'@'.$values_singleKPI['id_product'], $gtm_same_kpis) )
								{
									foreach ( $gtm_same_kpis as $k => $v )
									{
										if ( strtolower($v) ==  strtolower($key) )
										{
											$k = explode('@', $k); 
											$elt_value = $this->gtmData[$i][$values_singleKPI['class_object']][$k[1]][$k[0]];
											if ( !empty($elt_value) )
												break;
										}
									}
								}
							}
							elseif ( is_array($gtm_same_raws) ) // RAW
							{
								
								if ( array_key_exists($values_singleKPI['id_elem'].'@'.$values_singleKPI['id_product'], $gtm_same_raws) )
								{
									foreach ( $gtm_same_raws as $k => $v )
									{
										if ( strtolower($v) ==  strtolower($key) )
										{
											$k = explode('@', $k); 
											$elt_value = $this->gtmData[$i][$values_singleKPI['class_object']][$k[1]][$k[0]];
											if ( !empty($elt_value) )
												break;
										}
									}
								}
							}
							$data_value = $dom->createElement('value', $elt_value); //ouverture balise <value>
							
							
							// Informations du lien
							if( $this->dashMode == 'overtime')
							{			
								
								
								// maj 11:46 18/11/2009 : Si la donnée est extrapolée on supprime le lien
								$data_link = ($this->datas_extra[ $values_singleKPI['id_elem'] ][$i] or $this->gtmDataLink[$i] == "" ) ? "#" :  $this->gtmDataLink[$i];
							}else{
								
								$data_link = (($this->gtmDataLink[$i] == "") ? "#" : $this->gtmDataLink[$i]);
							}
							$data_link_SingleKPI = $this->getDataLink($ne,$data_link,$this->dashMode,$this->datas_extra[ $values_singleKPI['id_elem'] ][$i],$this->gtmDataLink[$i]);
							
							$data_value->setAttribute("data_link", $data_link_SingleKPI);

							// 19/01/11 MMT DE Xpert 606
							// add Data Xpert link URL into the XML as done for AA with link_AA, if allowed
							if($this->hasXpertDashboardManager() && $this->xpertDashMger->isGTMLinkAllowed()){
								$data_value->setAttribute("link_xpert",$this->xpertDashMger->getGTMlinkXpertValue($data_link_SingleKPI,$values_singleKPI['id_product']) );
							}

							// 16:48 26/05/2009 GHX
							// Traitement des liens vers AA 
							if ( !empty($this->gtmDataLinkAA) )
							{
								// 22/01/2010 NSE bz 13873 : modification de la condition,
								// on ne regarde plus le mode OT / ONE du dash ($this->dashMode == 'overtime'), 
								// on regarde seulement qu'il n'y a pas extrapolation de données
				
								if( !$this->datas_extra[ $values_singleKPI['id_elem'] ][$i] ){
								 	
									// 23/02/2010 - MPR : Correction du BZ 14390 - pas de restriction des liens vers AA pour les familles activées
									// On récupère la famille pour identifier si les liens vers AA sont actifs 
									$family = $this->getFamilyFromId( $values_singleKPI['class_object'], $values_singleKPI['id_elem'], $values_singleKPI['id_product'] );
									
									// Les liens vers AA sont actifs pour le na $this->na_level ?
									
									$check_linkAA_on_axe1 = ( checkLinktoAAForNA( $this->na_level, $family,  $values_singleKPI['id_product'])  == "1"  ) ? true : false;
									$check_linkAA_on_axe3 = false;
                                    $displayCondition = $check_linkAA_on_axe1;

									// Les liens vers AA sont actifs pour le na 3ème axe $this->na_axe3 ?
									if( isset( $this->na_axe3 ) )
									{
                                        $check_linkAA_on_axe3 = ( checkLinktoAAForNA( $this->na_axe3, $family,  $values_singleKPI['id_product'])  == "1" ) ? true : false;
                                        $displayCondition = ( $check_linkAA_on_axe3 && $check_linkAA_on_axe1 ) ;
									}
									// On utilise la condition or car le line vers AA pour se baser sur le 3ème axe ou sur le 1er
                                    // 27/07/2010 BBX
                                    // Modification de la condition d'affichage des liens vers AA
                                    // Désormais, lien affiché si 1er ET 3ème actif (ou 1er actif si pas de 3ème axe)
                                    // BZ 15716
									if ($displayCondition)
									{
										$naAxe1AA = $this->na_level;
										$tab_ne = explode("|s|",$ne);
										$neAxe1AA = $tab_ne[0];
										$naAxe3AA='';
										$neAxe3AA='';
										if( isset( $this->na_axe3 ) )
										{
											$naAxe3AA = $this->na_axe3;
											$neAxe3AA = $tab_ne[1];
										}
										$link_AA_tmp = $this->getLinkAA($naAxe1AA,$neAxe1AA,$naAxe3AA,$neAxe3AA,$this->gtmDataLinkAA[$i][$ne]);									
										$linkAA = $this->gtmDataLinkAA[$i][$values_singleKPI['class_object']][$values_singleKPI['id_product']][$values_singleKPI['id_elem']];
										
										$data_value->setAttribute("link_aa", $link_AA_tmp);
									}  
								}
							}
                                                        
                                                        //CB 5.3.1 : Link to Nova Explorer
                                                        // Traitement des liens vers NE 
							if ( !empty($this->gtmDataLinkNE) )
							{
								// 22/01/2010 NSE bz 13873 : modification de la condition,
								// on ne regarde plus le mode OT / ONE du dash ($this->dashMode == 'overtime'), 
								// on regarde seulement qu'il n'y a pas extrapolation de données
				
								if( !$this->datas_extra[ $values_singleKPI['id_elem'] ][$i] ){
								 	
									// 23/02/2010 - MPR : Correction du BZ 14390 - pas de restriction des liens vers AA pour les familles activées
									// On récupère la famille pour identifier si les liens vers AA sont actifs 
									$family = $this->getFamilyFromId( $values_singleKPI['class_object'], $values_singleKPI['id_elem'], $values_singleKPI['id_product'] );
									
									// Les liens vers AA sont actifs pour le na $this->na_level ?
									
									$check_linkNE_on_axe1 = ( checkLinktoNEForNA( $this->na_level, $family,  $values_singleKPI['id_product'])  == "1"  ) ? true : false;
									$check_linkNE_on_axe3 = false;
                                    $displayConditionNE = $check_linkNE_on_axe1;

									// Les liens vers NE sont actifs pour le na 3ème axe $this->na_axe3 ?
									if( isset( $this->na_axe3 ) )
									{
                                        $check_linkNE_on_axe3 = ( checkLinktoNEForNA( $this->na_axe3, $family,  $values_singleKPI['id_product'])  == "1" ) ? true : false;
                                        $displayConditionNE = ( $check_linkNE_on_axe3 && $check_linkNE_on_axe1 ) ;
									}
									// On utilise la condition or car le line vers AA pour se baser sur le 3ème axe ou sur le 1er
                                    // 27/07/2010 BBX
                                    // Modification de la condition d'affichage des liens vers AA
                                    // Désormais, lien affiché si 1er ET 3ème actif (ou 1er actif si pas de 3ème axe)
                                    // BZ 15716
									if ($displayConditionNE)
									{
										$naAxe1NE = $this->na_level;
										$tab_ne = explode("|s|",$ne);
										$neAxe1NE = $tab_ne[0];
										$naAxe3NE='';
										$neAxe3NE='';
										if( isset( $this->na_axe3 ) )
										{
											$naAxe3NE = $this->na_axe3;
											$neAxe3NE = $tab_ne[1];
										}
										$link_NE_tmp = $this->getLinkNE($naAxe1NE,$neAxe1NE,$naAxe3NE,$neAxe3NE,$this->gtmDataLinkNE[$i][$ne]);									
										$linkNE = $this->gtmDataLinkNE[$i][$values_singleKPI['class_object']][$values_singleKPI['id_product']][$values_singleKPI['id_elem']];
										
										$data_value->setAttribute("link_ne", $link_NE_tmp);
									}
								}
							}
                                                        
							// 22/01/2010 NSE bz 13873 : suppression du else
							// si $this->gtmDataLinkAA est vide, on ne l'utilise pas !			
							
							// Traitement de la / des bh(s)
		
							$bh_infos = "";
						
							if (count($this->gtmBHData[$i][$values_singleKPI['class_object']][$values_singleKPI['id_product']][$values_singleKPI['id_elem']]) > 0) {
								$bh_data = $this->gtmBHData[$i][$values_singleKPI['class_object']][$values_singleKPI['id_product']][$values_singleKPI['id_elem']];
								$bh_tab = array();
		
								foreach ($bh_data as $bh_label => $bh_value) {
									$bh_tab[] = $bh_label." : ".$bh_value;
								}
		
								$bh_infos = "<br>".implode(", ", $bh_tab);
							}
							$data_value->setAttribute("bh_infos", $bh_infos);
							
							// maj 18/11/2009 - Ajout de l'info sur l'extrapolation des données dans le titre du tooltip
							$extra = "";
							if( $this->datas_extra[ $values_singleKPI['id_elem'] ][$i] ){
								$data_value->setAttribute("extra", " - <span style='color:darkblue;'>Extrapolated Data</span>");
							}
							
							$data->appendChild($data_value);
							
						}
						$datas->appendChild($data);
					}
					
				}
				// Single KPI
			}else{
				//$tab_color_ne = $this->getColorNE($this->tab_ne,$db);
				foreach ($gtm_data as $key=>$value)
				{
					// Définition de la balise "data"
					$data = $dom->createElement('data');

					// Définition des attributs de "data"

					$data->setAttribute("label", $key);

					$data->setAttribute("type", $value['display_type']);
					
					if ($this->gtmType=='singleKPI' && $value['display_type']=='line'){ 
						$data->setAttribute("fill_color", "#0000ff@1");
						//$data->setAttribute("fill_color", $tab_color_ne[$ne]."@0.5");
					}else{ //si en mode line => toujours la même couleur et transparence pour fill_color
						$data->setAttribute("fill_color", $value['filled_color']);
						 //faire un random sur la couleur, une pour chaque NE
					}
						
					if ($this->gtmType=='singleKPI'){
						$data->setAttribute("line_design", "none");
						$data->setAttribute("stroke_color", "#0000ff@1");
					}else{
						$data->setAttribute("line_design", $value['line_design']);
						$data->setAttribute("stroke_color", $value['color']);
					}
					//$data->setAttribute("stroke_color", $value['color']);
					$data->setAttribute("yaxis", $value['position_ordonnee']);

					// 13:47 11/05/2009 GHX
					// Echappement des simples cotes pour le tooltip

					// 4/01/11 MMT DE Xpert 606
					// create JS call to create Menus for AA or Xpert links
					$data->setAttribute("onmouseover", $this->getGTMonmouseoverJScall());
					$data->setAttribute("onmouseout", "kill();");

					// Définition du lien

					$data->setAttribute("href", "\$data_link");
				
				/****/
				// DEBUT BZ 14613
				// 04/03/2010 BBX : On sort le morceau ci-dessous de la boucle sur les données
				// à cause de la fonction "checkLinktoAAForNA" qui créé une instance DataBaseConnection
				// à chaque appel. Ce bloc n'a pas besoin d'être appelé à chaque itération.
				// 23/02/2010 - MPR : Correction du BZ 14390 - pas de restriction des liens vers AA pour les familles activées
				// On récupère la famille pour identifier si les liens vers AA sont actifs 			
				if ( !empty($this->gtmDataLinkAA) )
				{
					$family = $this->getFamilyFromId( $value['class_object'], $value['id_elem'], $value['id_product'] );

					// Les liens vers AA sont actifs pour le na $this->na_level ?

					$check_linkAA_on_axe1 = ( checkLinktoAAForNA( $this->na_level, $family,  $value['id_product'])  == "1"  ) ? true : false;
					$check_linkAA_on_axe3 = false;
                    $displayCondition = $check_linkAA_on_axe1;

					// Les liens vers AA sont actifs pour le na 3ème axe $this->na_axe3 ?
					if( isset( $this->na_axe3 ) )
					{
                         $check_linkAA_on_axe3 = ( checkLinktoAAForNA( $this->na_axe3, $family,  $value['id_product'])  == "1" ) ? true : false;
                         $displayCondition = ($check_linkAA_on_axe1 && $check_linkAA_on_axe3);
					}
				}
                                //CB 5.3.1 : Link to Nova Explorer
                                if ( !empty($this->gtmDataLinkNE) )
				{
					$family = $this->getFamilyFromId( $value['class_object'], $value['id_elem'], $value['id_product'] );

					// Les liens vers AA sont actifs pour le na $this->na_level ?

					$check_linkNE_on_axe1 = ( checkLinktoNEForNA( $this->na_level, $family,  $value['id_product'])  == "1"  ) ? true : false;
					$check_linkNE_on_axe3 = false;
                    $displayConditionNE = $check_linkNE_on_axe1;

					// Les liens vers AA sont actifs pour le na 3ème axe $this->na_axe3 ?
					if( isset( $this->na_axe3 ) )
					{
                         $check_linkNE_on_axe3 = ( checkLinktoNEForNA( $this->na_axe3, $family,  $value['id_product'])  == "1" ) ? true : false;
                         $displayConditionNE = ($check_linkNE_on_axe1 && $check_linkNE_on_axe3);
					}
				}
                             
				// Fin BZ 14613
				/****/

				// Définition des valeurs de l'élément sous la forme d'un sous-élément "value"

				// 18:49 17/08/2009 GHX
				// Prise en compte du fait qu'il peut y avoir plusieurs KPI/RAW identiques dans un graphe (code+legende)
				//for ($i=0; $i < count($this->gtmData); $i++) 
				// 04/03/2010 BBX : On remplace le for par un foeach plus rapide. BZ 14613
				foreach($this->gtmData as $i => $currentValue)
				{
					$elt_value = $this->gtmData[$i][$value['class_object']][$value['id_product']][$value['id_elem']];
			
					if ( $value['class_object'] == 'kpi' && is_array($gtm_same_kpis) ) // KPI
					{
						if ( array_key_exists($value['id_elem'].'@'.$value['id_product'], $gtm_same_kpis) )
						{
							foreach ( $gtm_same_kpis as $k => $v )
							{
								if ( strtolower($v) ==  strtolower($key) )
								{
									$k = explode('@', $k); 
									$elt_value = $this->gtmData[$i][$value['class_object']][$k[1]][$k[0]];
									if ( !empty($elt_value) )
										break;
								}
							}
						}
					}
					elseif ( is_array($gtm_same_raws) ) // RAW
					{
						if ( array_key_exists($value['id_elem'].'@'.$value['id_product'], $gtm_same_raws) )
						{
							foreach ( $gtm_same_raws as $k => $v )
							{
								if ( strtolower($v) ==  strtolower($key) )
								{
									$k = explode('@', $k); 
									$elt_value = $this->gtmData[$i][$value['class_object']][$k[1]][$k[0]];
									if ( !empty($elt_value) )
										break;
								}
							}
						}
					}
					
					$data_value = $dom->createElement('value', $elt_value);
					
					// Informations du lien
					if( $this->dashMode == 'overtime')
					{					
						// maj 11:46 18/11/2009 : Si la donnée est extrapolée on supprime le lien
                                                // 22/11/2011 BBX
                                                // BZ 24764 : correction des notices php
                                                if(!isset($this->datas_extra[ $value['id_elem'] ][$i]))
                                                        $data_link = $this->gtmDataLink[$i];
                                                else
						$data_link = ($this->datas_extra[ $value['id_elem'] ][$i] or $this->gtmDataLink[$i] == "" ) ? "#" :  $this->gtmDataLink[$i];
					}else
					{
						$data_link = (($this->gtmDataLink[$i] == "") ? "#" : $this->gtmDataLink[$i]);
					}
					
					$data_value->setAttribute("data_link", $data_link);

					// 19/01/11 MMT DE Xpert 606
					// add Data Xpert link URL into the XML as done for AA with link_AA, if allowed
					if($this->hasXpertDashboardManager() && $this->xpertDashMger->isGTMLinkAllowed()){
						$data_value->setAttribute("link_xpert",$this->xpertDashMger->getGTMlinkXpertValue($data_link,$value['id_product']) );
					}

					// 16:48 26/05/2009 GHX
					// Traitement des liens vers AA 
					if ( !empty($this->gtmDataLinkAA) )
					{
						// 22/01/2010 NSE bz 13873 : modification de la condition,
						// on ne regarde plus le mode OT / ONE du dash ($this->dashMode == 'overtime'), 
						// on regarde seulement qu'il n'y a pas extrapolation de données

                                                // 22/11/2011 BBX
                                                // BZ 24764 : correction des notices php
						if( empty($this->datas_extra[ $value['id_elem'] ][$i]) )
						{		 
							// On utilise la condition or car le line vers AA pour se baser sur le 3ème axe ou sur le 1er

                                                        // 27/07/2010 BBX
                                                        // Modification de la condition d'affichage des liens vers AA
                                                        // Désormais, lien affiché si 1er ET 3ème actif (ou 1er actif si pas de 3ème axe)
                                                        // BZ 15716                                                     
                                                        if ($displayCondition)
							{
								$linkAA = $this->gtmDataLinkAA[$i][$value['class_object']][$value['id_product']][$value['id_elem']];
								$data_value->setAttribute("link_aa", $linkAA);                                                              
							}
                                                        //CB 5.3.1 : Link to Nova Explorer
                                                        if ($displayConditionNE)
							{
                                                                $linkNE = $this->gtmDataLinkNE[$i][$value['class_object']][$value['id_product']][$value['id_elem']];
								$data_value->setAttribute("link_ne", $linkNE);                                                               
							}
						}
					}
					// 22/01/2010 NSE bz 13873 : suppression du else
					// si $this->gtmDataLinkAA est vide, on ne l'utilise pas !			
					
					// Traitement de la / des bh(s)
					$bh_infos = "";

					if (count($this->gtmBHData[$i][$value['class_object']][$value['id_product']][$value['id_elem']]) > 0) {
						$bh_data = $this->gtmBHData[$i][$value['class_object']][$value['id_product']][$value['id_elem']];
						$bh_tab = array();

						foreach ($bh_data as $bh_label => $bh_value) {
							$bh_tab[] = $bh_label." : ".$bh_value;
						}

						$bh_infos = "<br>".implode(", ", $bh_tab);
					}
					$data_value->setAttribute("bh_infos", $bh_infos);
					
					// maj 18/11/2009 - Ajout de l'info sur l'extrapolation des données dans le titre du tooltip
					$extra = "";
                                        // 22/11/2011 BBX
                                        // BZ 24764 : correction des notices php
					if( !empty($this->datas_extra[ $value['id_elem'] ][$i]) ){
						$data_value->setAttribute("extra", " - <span style='color:darkblue;'>Extrapolated Data</span>");
					}
					
					$data->appendChild($data_value);
				}

				$datas->appendChild($data);
				}
			}
		}
		else // Cas des pies
		{
			// Note : sélectionner l'élément de tri en fonction de l'id_elem et du produit et non à partir du label

			// Récuperation de la liste des éléments (via leurs labels)

			$elt_list = array_keys($gtm_data);

			// Définition de l'élément de split. On se sert de celui-ci pour définir les camemberts
			
			$split_by = $gtm_data[$this->gtmSplitBy['label']];
			
			$data = $dom->createElement('data');

			// Définition des autres éléments. Ceux-ci sont définis sous la forme d'attributs "label_X" de "data"

			$others_elts = array();

			$idx = 0;

			for ($i=0; $i < count($elt_list); $i++)
			{
				if ($elt_list[$i] != $this->gtmSplitBy['label'])
				{
					$others_elts[$idx] = $elt_list[$i];
					$idx += 1;
				}
			}

			// Définition des autres attributs en référence au raw / kpi de tri

			$data->setAttribute("type", $split_by['display_type']);
			$data->setAttribute("fill_color", $split_by['filled_color']);
			$data->setAttribute("line_design", $split_by['line_design']);
			$data->setAttribute("stroke_color", $split_by['color']);
			$data->setAttribute("yaxis", $split_by['position_ordonnee']);

			// Définition des valeurs des camemberts du pie (balises "value")

			$elt_value = $this->gtmData;

			// 18:06 13/05/2009 GHX
			// Suppression de la partie cas particulier split 3ieme axe
			for ($i=0; $i < count($elt_value); $i++) {

				// La valeur de "value" est celle du raw / kpi de tri

				$split_value = $elt_value[$i][$split_by['class_object']][$split_by['id_product']][$split_by['id_elem']];
				
				// Traitement de la / des bh(s) du raw / kpi de tri
				if (count($this->gtmBHData[$i][$split_by['class_object']][$split_by['id_product']][$split_by['id_elem']]) > 0) {

					$bh_data = $this->gtmBHData[$i][$split_by['class_object']][$split_by['id_product']][$split_by['id_elem']];
					$bh_tab = array();

					foreach ($bh_data as $bh_label => $bh_value) {
						$bh_tab[] = $bh_label." : ".$bh_value;
					}

					$split_value .= "<br>".implode(", ", $bh_tab);
				}
				
				

				$data_value = $dom->createElement('value', $split_value);
				$data_value->setAttribute("label", $this->gtmSplitBy['label']);
				
				// 17:18 13/05/2009 GHX
				// Permet d'afficher la date ou le nom de l'élément réseau à coté du pourcentage
				// Problème illisible
				//$data_value->setAttribute("pie_label", "%.0f%% - ".$this->gtmXAxis['values'][$i]);
				
				// Informations du lien
				if($this->dashMode == 'overtime'){
					$data_link = (($this->gtmDataLink[$i] == "" and !$this->datas_extra[ $elt['id_elem'] ][$i] ) ? "#" : $this->gtmDataLink[$i]);
				}else{
					$data_link = (($this->gtmDataLink[$i] == "") ? "#" : $this->gtmDataLink[$i]);
				}
					

				$data_value->setAttribute("data_link", $data_link);

				// 14:43 08/10/2009 SCT : ajout de la couleur sur l'élément
				$data_ne_color = (($this->gtmDataNEColor[$i] == "") ? "" : $this->gtmDataNEColor[$i]);
				$data_value->setAttribute("color", $data_ne_color);

				// maj 18/11/2009 - MPR : Modification du tooltip afin d'ajouter l'info sur l'extrapolation des données
				
				// Les valeurs des autres éléments sont définis sous la forme d'attributs "value_X" de "value" et les labels comme attributs "label_X"
				for ($j=0; $j < count($others_elts); $j++)
				{
					$elt = $gtm_data[$others_elts[$j]];

					$label_others = $others_elts[$j];
					$value_others = $elt_value[$i][$elt['class_object']][$elt['id_product']][$elt['id_elem']];				

					// Traitement de la / des bh(s). Celles-ci étant liés à chaque raw / kpi, on les défini comme un attribut de chaque valeur

					if (count($this->gtmBHData[$i][$elt['class_object']][$elt['id_product']][$elt['id_elem']]) > 0) {

						$bh_data = $this->gtmBHData[$i][$elt['class_object']][$elt['id_product']][$elt['id_elem']];
						$bh_tab = array();

						foreach ($bh_data as $bh_label => $bh_value) {
							$bh_tab[] = $bh_label." : ".$bh_value;
						}

						$value_others .= "<br>".implode(", ", $bh_tab);
					}
					
					$data_value->setAttribute("label_".$j, $label_others);
					// maj 18/11/2009 - MPR : Modification du tooltip afin d'ajouter l'info sur l'extrapolation des données
					
			
					$extra[$j] = "";
					if( $this->dashMode == 'overtime' and $this->datas_extra[ $elt['id_elem'] ][$i] ){
						$extra[$j] = " - <span style=\'color:darkblue;\'>Extrapolated Data</span>";
					}
					
					$data_value->setAttribute("extra_".$j , $extra[$j]);
					
					$data_value->setAttribute("value_".$j, $value_others);
					
				}

				$data->appendChild($data_value);
			}


			// Définition du mouseover. Les données affichées sont la valeur et les attributs de "value"

			// 11/05/2009 GHX
			// Ajout d'un str_replace pour échaper les simples cotes sinon plantage de IE à cause de JS
			$mouseover_content = array("\".str_replace(\"'\", \"\'\", \$label).\"=\$y");
			$title_tooltip = array("\".str_replace(\"'\", \"\'\", \$x ).\"");
				
			for ($i=0; $i < count($others_elts); $i++) {
				// 14:54 07/05/2009 GHX
				// Ajout d'un str_replace pour échaper les simples cotes sinon plantage de IE à cause de JS
				$mouseover_content[] =  "\".str_replace(\"'\", \"\'\", \$label_".$i.").\"=\$value_".$i;
				$title_tooltip[] = "\$extra_$i";
			}

			//$data->setAttribute("onmouseover", "pop('\$x', '".implode("<br>", $mouseover_content)."');");
			// 14:54 07/05/2009 GHX
			// Ajout d'un str_replace pour échaper les simples cotes sinon plantage de IE à cause de JS
			$data->setAttribute("onmouseover", "pop('".implode(" ",$title_tooltip)."', '".implode("<br>",$mouseover_content)."');");
			$data->setAttribute("onmouseout", "kill();");

			// Définition du lien
			// 14:00 13/01/2010 SCT => BZ 13707 : Erreur lors du passage d'un graphe OT vers ONE en cliquant sur un pie
                        // 02/02/2011 OJT : Utilisation du window.location pour compatibilité Firefox
			$data->setAttribute("href", "javascript:window.location='\$data_link'");

			$datas->appendChild($data);
		}
		return $datas;
	}

    /**
     * Convertit les valeurs des données (dans le cas de données en Bit/Byte)
     * Modifie également la légende des Y-axis en conséquence.
     *
     * @since 5.0.5.06
     * @param DOMDocument $dom Objet DOMDocument décrivant le fichier XML
     */
    public function convertDatasPart( DOMDocument &$dom )
    {
        $leftDataNodesList  = array();
        $rightDataNodesList = array();
        $maxLeftElement     = 0;
        $maxRightElement    = 0;

        // Récupération de toutes les balises <data>
        $dataTag = $dom->getElementsByTagName( 'data' );
        for( $i = 0 ; $i < $dataTag->length ; $i ++ )
        {
            $dataNode = $dataTag->item( $i );
            if( $dataNode->hasAttributes() && $dataNode->attributes->getNamedItem( 'yaxis' ) != null )
            {
                switch( $dataNode->attributes->getNamedItem( 'yaxis' )->value )
                {
                    // Si l'élément est affiché à gauche, on recherche l'élément le plus grand
                    case 'left':
                        $childNodes = $dataNode->childNodes;
                        $leftDataNodesList []= $dataNode;
                        for( $u = 0 ; $u < $childNodes->length ; $u ++ )
                        {
                            $maxLeftElement = max( $maxLeftElement, $childNodes->item( $u )->nodeValue );
                        }
                        break;

                    // Si l'élément est affiché à droite, on recherche l'élément le plus grand
                    case 'right':
                        $childNodes = $dataNode->childNodes;
                        $rightDataNodesList []= $dataNode;
                        for( $u = 0 ; $u < $childNodes->length ; $u ++ )
                        {
                            $maxRightElement = max( $maxRightElement, $childNodes->item( $u )->nodeValue );
                        }
                        break;
                }
            }
        }

        // Traitement des données de l'axe de gauche
        if( ( $this->_sizeUnitLeft != null ) && ( ( $maxLeftElement >= $this->_sizeUnitLeft['treshold'] ) || ( $maxLeftElement < 1 ) ) )
        {
            $conversionOp = 1; // Opération de multiplication à effectuer
            $threshold    = $this->_sizeUnitLeft['treshold'];

            // Tant que la valeur est supérieur au seuil, on convertit (dans la limite de l'unité de mesure supérieure)
            while( ( $maxLeftElement >= $threshold ) && ( $this->_sizeUnitLeft['current'] < count( $this->_sizeUnitLeft['list'] ) - 1 ) )
            {
                $maxLeftElement /= $threshold; // On divise par le seuil
                $conversionOp   /= $threshold;

                // On passe à l'unité supérieur (MB=>GB par exemple)
                $this->_sizeUnitLeft['current']++;
            }

            // Tant que la valeur est inférieur à 1, on convertit (dans la limite de l'unité de mesure inférieure)
            while( ( $maxLeftElement < 1 ) && ( $this->_sizeUnitLeft['current'] > 0 ) )
            {
                $maxLeftElement *= $threshold; // On multiplie par le seuil
                $conversionOp   *= $threshold;

                // On passe à l'unité inférieur (MB=>kB par exemple)
                $this->_sizeUnitLeft['current']--;
            }

            // On convertit toutes les valeurs, en utilisant l'opérateur
            // calculé et l'arrondi définit dans le .ini
            foreach( $leftDataNodesList as $oneDataNode )
            {
                $cN = $oneDataNode->childNodes;
                for( $u = 0 ; $u < $cN->length ; $u ++ )
                {
                    $cN->item( $u )->nodeValue = round( $cN->item( $u )->nodeValue * $conversionOp, $this->_sizeUnitRound );
                }
            }

            // On modifie la legende de l'axe avec la nouvelle unité
            $dom->getElementsByTagName( 'left_axis_label' )->item( 0 )->nodeValue = $this->_sizeUnitLeft['list'][$this->_sizeUnitLeft['current']];
        }

        // Traitement des données de l'axe de droite
        if( ( $this->_sizeUnitRight != null ) && ( ( $maxRightElement >= $this->_sizeUnitRight['treshold'] ) || ( $maxRightElement < 1 ) ) )
        {
            $conversionOp = 1; // Opération de multiplication à effectuer
            $threshold    = $this->_sizeUnitRight['treshold'];

            // Tant que la valeur est supérieur au seuil, on convertit (dans la limite de l'unité de mesure supérieure)
            while( ( $maxRightElement >= $threshold ) && ( $this->_sizeUnitRight['current'] < count( $this->_sizeUnitRight['list'] ) - 1 ) )
            {
                $maxRightElement /= $threshold; // On divise par le seuil
                $conversionOp    /= $threshold;

                // On passe à l'unité supérieur (MB=>GB par exemple)
                $this->_sizeUnitRight['current']++;
            }

            // Tant que la valeur est inférieur à 1, on convertit (dans la limite de l'unité de mesure inférieure)
            while( ( $maxRightElement < 1 ) && ( $this->_sizeUnitRight['current'] > 0 ) )
            {
                $maxRightElement *= $threshold; // On multiplie par le seuil
                $conversionOp    *= $threshold;

                // On passe à l'unité inférieur (MB=>kB par exemple)
                $this->_sizeUnitRight['current']--;
            }

            // On convertit toutes les valeurs, en utilisant l'opérateur
            // calculé et l'arrondi définit dans le .ini
            foreach( $rightDataNodesList as $oneDataNode )
            {
                $cN = $oneDataNode->childNodes;
                for( $u = 0 ; $u < $cN->length ; $u ++ )
                {
                    $cN->item( $u )->nodeValue = round( $cN->item( $u )->nodeValue * $conversionOp, $this->_sizeUnitRound );
                }
            }

            // On modifie la legende de l'axe avec la nouvelle unité
            $dom->getElementsByTagName( 'right_axis_label' )->item( 0 )->nodeValue = $this->_sizeUnitRight['list'][$this->_sizeUnitRight['current']];
        }
    }
}