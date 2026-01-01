<?php
/*
	16/07/2009 GHX
		- Ajout de la possibilité de prendre en compte des attributs pour les propriétés (pour la gestion du autoScale)
	26/11/2009 MPR
		- Correction du bug 13052 : On définit le mode soit Investigation soit Dashboard OT
 * 02/02/2011 MMT
 *		- Correction du bug 20415 : utilise ElementColorManager pour avoir des couleurs fixes pour la legende
 * 31/03/2011 NSE Merge 5.0.5 -> 5.1.1 : suppression de la correction du 18010 de BBX au profit de celle de MMT du 20415
 *                                       Toutefois, il faudrait l'enrichir de la partie $_lineDesigns de BBX.
*/
?>
<?php
/**
 * Classe InvestigationXml 
 * elle va creer le xml utilise pour les graphes d'investigation dashboard
 * la classe reprend en partie des elements de la classe GtmXml.class.php
 * 
 * @author SPS 
 * @date 28/05/2009
 * @version CB 5.0.0.0
 * @since CB 5.0.0.0
 */
//tableau des differentes couleurs disponibles pour les lignes
// 04/02/2011 MMT 20415 utilise ElementColorManager
require_once(REP_PHYSIQUE_NIVEAU_0."dashboard_investigation/class/ElementColorManager.class.php");
//on recupere le nombre maximum de selection
$MAX_SELECTION = get_sys_global_parameters("investigation_dashboard_max_selection");

/**
 * Classe InvestigationXml 
 * elle va creer le xml utilise pour les graphes d'investigation dashboard
 * 
 * @author SPS 
 * @date 28/05/2009
 * @version CB 5.0.0.0
 * @since CB 5.0.0.0
 * @package InvestigationDashboard
 */
class InvestigationXml
{	
	/**
	 * donnees a afficher
	 * les donnees sont sous la forme :
	 * 	- [nel][counter][ta_value][value]
	 * 	- [nel][counter@type_axe3@param_axe3@value_axe3][ta_value][value]
	 * 
	 * @var mixed 
	 * */
	private $data;

	/**
	 * nom du xml
	 * @var string
	 **/
	private $xml;
	
	/**
	 * titre du graph
	 * @var string
	 * */
	private $graph_title;
	
	/**
	 * titre de l'axe des abcisses
	 * @var string
	 * */
	private $xaxis_title;
	
	/**
	 * proprietes des lignes 
	 * @var mixed
	 * */
	private $properties;
	/**
	 * Attributs des propriétés
	 * @var array
	 * */
	private $propertiesAttributs;

       /**
         * Définit les différents plots affichables
         * @var array
         * NSE Merge : à utiliser dans le cadre de l'enrichissement MMT par BBX
         */
         //                   protected $_lineDesigns = array();
	
	/**
	 * Constructeur de la classe
	 *
	 * @param mixed $tab_data donnees de l'investigation dashboard
	 */
	public function __construct($tab_data) {
		
		__debug($tab_data,"TAB DATA");
		//on recupere la selection maxi
		global $MAX_SELECTION;

        // 21/09/2010 BBX
        // Construction des couleurs
        // BZ 18010
        // NSE Merge : à utiliser dans le cadre de l'enrichissement MMT par BBX
        //                    $this->generateLineDesigns();
		
		$too_much_data = 0;
		
		if($tab_data) {
			$nb_data = 0;
			
			//on parcourt les donnees
			foreach ($tab_data as $nel => $counter) {
				//on parcourt les compteurs
				foreach($counter as $counter_name => $ta) {
					__debug($counter_name,"COUTNER NAME  + 3ème axe");
					
					// Gestion du 3ème axe
					$t = explode("@",$counter_name);
					if( $t[0] !== $counter_name){
						$this->na_values_axe3[] = "{$t[1]}@{$t[2]}@{$t[3]}";
					}
					
					//on recree un tableau de donnees
					$tdata[$nel] = $counter;
					//on compte les compteurs
					$nb_data++;	
						
					//si on est au max, on affiche un message au desus du graph et on arrete le traitement (on ne s'occupe donc plus des autres donnees)
					if ($nb_data == $MAX_SELECTION) {
						//echo "<span class=\"texteRougeBold\">".__T('U_INVESTIGATION_DASHBOARD_TOO_MANY_ELEMENTS',$MAX_SELECTION)."</span>";
						//on utilise ce booleen pour sortir de la 1ere boucle
						$too_much_data = 1;
						//on sort de la boucle actuelle
						break;
					}
				}
				//si trop de donnees, on arrete le traitement, et on utilise le nouveau tableau cree
				if ($too_much_data) {
					break;
				}
			}
		}
		$this->data = $tdata;
	}


        /**
         * Génère les plots utilisables
         * NSE Merge : conservé
         */
         /*                   public function generateLineDesigns()
                            {
                                $this->_lineDesigns[] = 'square';
                                $this->_lineDesigns[] = 'circle';
                                $this->_lineDesigns[] = 'diamond';
                                $this->_lineDesigns[] = 'triangle';
                            }
          */


	/**
	 * Définition du titre du graph
	 *
	 * @param string $title titre du GTM
	 */
	public function setGraphTitle($title) {
		$this->graph_title = $title;
	}

	/**
	 * setteur du titre des abcisses 
	 * @param string $xtitle titre
	 **/
	public function setXAxisTitle($xtitle) {
		$this->xaxis_title = $xtitle;
	}

	/**
	 * setteur des proprietes du graph 
	 * @param array $props
	 * @param array $attr (default array())
	 **/
	public function setProperties($props, $attr = array()) {
		$this->properties = $props;
		$this->propertiesAttributs = $attr;
	}	
	

	/**
	 * Permet de construire le fichier XML
	 */
	public function build()
	{
		//Création du nouvel objet XML. On utilise pour se faire la classe Dom de PHP
		$dom = new DOMDocument();

		// active un joli export indenté du XML
		$dom->formatOutput = true;

		// Création de la racine du document
		$chart = $dom->createElement('chart');
		$dom->appendChild($chart);

		//Définition des propriétés
		$pptes = $this->buildPropertiesPart($dom);
		$chart->appendChild($pptes);

		//Définition des informations de l'axe horizontal
		$xaxis = $this->buildXAxisPart($dom);
		$chart->appendChild($xaxis);

		//Définition des données 
		if (count($this->data) > 0)
		{
			$datas = $this->buildDatasPart($dom);
			$chart->appendChild($datas);
		}

		$this->xml = $dom;
	}

	/**
	 * Retourne le document XML
	 *
	 * @return object le document XML au format DOMDocument
	 */
	public function getXML() {
		return $this->xml;
	}

	/**
	 * Permet de sauvegarder le document XML dans un fichier
	 *
	 * @param string $path url du fichier dans lequel sauvegarder le XML
	 * @return boolean état de création du fichier (true : crée, false : non crée)
	 */
	public function saveXML($path) {
		return ($this->xml->save($path));
	}

	/**
	 * Construction du contenu de la balise <properties> du XML
	 *
	 * @param object $dom noeud parent de la balise <properties>
	 * @return object balise <properties> au format DOMDocument
	 */
	private function buildPropertiesPart($dom)
	{
		// Définition du noeud "properties"
		$pptes = $dom->createElement('properties');

		//on ecrit le titre du graph
		$tabtitle = $dom->createElement('tabtitle',$this->graph_title);
		$pptes->appendChild($tabtitle);
		
		//on ecrit les proprietes du graph et des lignes
		foreach ($this->properties as $key=>$value) {
			$ppte = $dom->createElement($key, $value);
			// 10:18 16/07/2009 GHX
			// Prise en compte que les propriétés peuvent avoir des attributs
			if ( array_key_exists($key, $this->propertiesAttributs) )
			{
				foreach ( $this->propertiesAttributs[$key] as $attrName => $attrValue )
				{
					$ppte->setAttribute($attrName, $attrValue);
				}
			}
			$pptes->appendChild($ppte);
		}

		return $pptes;
	}

	/**
	 * Construction du contenu de la balise <xaxis_labels> du document XML
	 *
	 * @param object $dom noeud parent de la balise <xaxis_labels>
	 * @return object balise <xaxis_labels> au format DOMDocument
	 */
	private function buildXAxisPart($dom)
	{	
		//on ecrit le titre de l'axe des abcisses
		$xaxis = $dom->createElement('xaxis_labels');
		$xaxis->setAttribute("title", $this->xaxis_title);

		$tdata = $this->data;
		
		if($tdata) {
			foreach ($tdata as $nel => $counter) {
				foreach($counter as $counter_name => $ta) {
					foreach($ta as $date => $value) {	
						
						//si on a un @ dans la date, on est en bh
						if(ereg("@",$date)) {
							$d = explode("@",$date);
							//la variable xaxis_title correspond au type de TA
							$values[] = Date::getSelecteurDateFormatFromDate( $this->xaxis_title, $d[0]);
						}
						else {
							$values[] = Date::getSelecteurDateFormatFromDate( $this->xaxis_title, $date);
						}
						
					}
				}
			}
			
			//pour ne pas avoir de doublons
			$tdates = array_unique($values);
			
			//pour chacune des dates, on cree un label
			for ($i=0; $i < count($tdates); $i++) {
				$xaxis_sub = $dom->createElement('label', $tdates[$i]);
				$xaxis->appendChild($xaxis_sub);
			}
			
		}
		
		return $xaxis;
	}
	
	public function setNaValues($na_values){
		
		$tab = explode("|s|", $na_values);
		
		foreach($tab as $k=>$v)
		{
			$t = explode("@",$v);
			$this->na_level[] = $t[0]; 
			$this->na_values[] = $t[1];
			$this->na_values_label[ $t[1] ] = $t[2];
		}
		
	}
	
	public function setNaAxe3($na_values_axe3){
		
		$tab = explode("|s|", $na_values_axe3);
		
		foreach($tab as $k=>$v)
		{
			$t = explode("@",$v);
			$this->na_axe3[] = $t[0]; 
			$this->na_values_axe3[] = $t[1];
			$this->na_values_axe3_label[ $t[1] ] = $t[2];
		}
		
	}
	
	public function setTaValues(){
		
		foreach($this->data as $ne=>$tab_rawkpi){

			$t_values = array_values($tab_rawkpi);
			$ta_values = array_keys($t_values[0]);
		}
		
		
		$this->ta_values = $ta_values;
		
	}
	
	
	
	public function setLstRawKpis($lst_raw_kpis){
	
		$tab = explode("|s|", $lst_raw_kpis);
		
		foreach($tab as $k=>$v)
		{
			$t = explode("@",$v);
			$this->lst_raw_kpis[$t[0]][] = $t[1]; 
		}
	}
	
	public function setTaLevel($ta){
		$this->ta_level = $ta;
	}
	
	public function setProduct($prod){
		$this->product = $prod;
	}
	
	public function setFamilyInfos($family){
		$this->family = $family;
		$infos_family = get_gt_info_from_family($family,$this->product);
		$this->edw_group_table = $infos_family['edw_group_table'];
	}
	
	private function extrapolationData(){

		
		include_once(REP_PHYSIQUE_NIVEAU_0."dashboard_display/class/ExtrapolationData.class.php" );
				
		$products = array($this->product);
		$families[$this->product] = array($this->family);
		$edw_group_table[$this->product] = array($this->edw_group_table);
		
		$this->setTaValues();
		
		$extra_data = new ExtrapolationData();
		// maj 26/11/2009 - MPR : Correction du bug 13052 : On définit le mode soit Investigation soit Dashboard OT
		$extra_data->setMode("investigation");
		
		// Récupération des infos nécessaires pour compléter les données si besoin est
		$extra_data->setProducts($products);				
		$extra_data->setFamilies($families);
		$extra_data->setParamsActivation();
		
		if( $extra_data->activate ){
		
			// Initialisation des éléments de topo
			$extra_data->setNa( $this->na_level );
			
			if( isset($this->na_values_axe3) )
				$extra_data->setNaValueAxe3( $this->na_values_axe3 );
							
			// __debug($this->na_axe3,"NA AXE3");
			// Initialisation de la TA et du ou des raws/kpis
			$extra_data->setTa( $this->ta_level );
			$extra_data->setTaValues( $this->ta_values, true );
			
			$extra_data->setNaValues( $this->na_values, $this->na_values_label );
			// $extra_data->setNaValuesAxe3( $this->na_values, $this->na_values_label );

			$extra_data->setRawKpi( $this->lst_raw_kpis, $this->raw_kpis_labels );
			$extra_data->setGroupTable($edw_group_table);
			
			// Récupération des données 
			$extra_data->setDatas( $this->data );
			
			// Extrapolation des données 
			$this->data = $extra_data->completeDatasInvestigation();

			$this->datas_extra = $extra_data->getDatasExtra();
			
		}

		return $this->data;
	}
	
	/**
	 * Construction du contenu de la balise <data> du document XML
	 *
	 * @param object $dom noeud parent de la balise <data>
	 * @return object balise <data> au format DOMDocument
	 */
	private function buildDatasPart($dom)
	{	
		// 04/02/2011 MMT 20415 utilise ElementColorManager
		global $MAX_SELECTION;
		$nb_data=0;
		$datas = $dom->createElement('datas');

		// $tdata = $this->data;
		$tdata = $this->extrapolationData($this->data);
		
		// 04/02/2011 MMT 20415 utilise ElementColorManager
		$eltColorsMger = ElementColorManager::getInstance();

		foreach ($tdata as $nel => $counter) {	

			foreach($counter as $counter_name => $ta) {
				$data = $dom->createElement('data');
			
				//on verifie que le nombre de donnees est inferieur au maximum autorise (pour des soucis de lecture)
				if ($nb_data <= $MAX_SELECTION) {
				
					//si on a un 3eme axe, le compteur contient un @
					if (ereg("@",$counter_name)) {
						$c = explode("@",$counter_name);
						//on recupere ensuite les differentes valeurs du compteur et du 3eme axe
						$counter_label = $c[0];
						$type_axe3 = $c[1];
						$param_axe3 = $c[2];
						$value_axe3 = $c[3];
						//on definit le nom du label (utilise pour la legende, et le tooltip)
						// 04/02/2011 MMT 20415 utilise ElementColorManager
						$label = $nel.", ".$counter_label.", ".$type_axe3." [".$value_axe3."]";
					}
					else {
						//pas de 3eme axe
						$label =$nel.", ".$counter_name;
					}
					// 04/02/2011 MMT 20415 utilise ElementColorManager
					$data->setAttribute("label",$label);
					
					//on definit le type de ligne a afficher
					$data->setAttribute("type","line");
                                        // 21/09/2010 BBX
                                        // Correction de la sélection des formes
                                        // BZ 18010
                    // NSE Merge : à utiliser dans le cadre de l'enrichissement MMT par BBX
					//                    $data->setAttribute("line_design",$this->_lineDesigns[mt_rand(0, count($this->_lineDesigns)-1)]);
					$data->setAttribute("line_design","square");
					//random sur le tableau de couleurs
					//$data->setAttribute("stroke_color", $colors[rand( 0, count($colors)-1 )] );
                                        // 21/09/2010 BBX
                                        // Correction de la sélection des couleurs
                                        // BZ 18010
                    // NSE Merge : à utiliser dans le cadre de l'enrichissement MMT par BBX
                    //                    $data->setAttribute("stroke_color", self::$_colors[self::$_nbColorsPicked]);
                    //                    $data->setAttribute("fill_color", self::$_colors[self::$_nbColorsPicked]);
                    //                    self::$_nbColorsPicked++;
					// 04/02/2011 MMT 20415 utilise ElementColorManager
					$data->setAttribute("stroke_color",$eltColorsMger->getElementColor($label,"investigationDash"));
                    
					$data->setAttribute("yaxis","left");
					//on definit le tooltip
					$data->setAttribute("onmouseover", "pop('\".str_replace(\"'\", \"\'\", \"\$x \$bh_infos \$extra\").\"', '\$label = \".str_replace(\"'\", \"\'\", \"\$y\").\"');");			
					$data->setAttribute("onmouseout", "kill();");
					//meme si on n'a pas de lien sur la valeur affichee, il ne faut pas que cette valeur soit vide, sinon pas de tooltip
					$data->setAttribute("href","#");
					
					//pour chacune des dates, on ecrit la valeur de l'element
					foreach($ta as $date => $value) {	
						$data_value = $dom->createElement('value', $value);
						
						//si la date contient un @, on a une bh
						if (ereg("@",$date) && $value != 0) {
							$bh = explode("@",$date);
							$bh_infos = substr($bh[1],8,2).":00";
						}
						else {
							$bh_infos = '';
						}
						
						$data_value->setAttribute("bh_infos", $bh_infos);
						
						// maj 18/11/2009 - Ajout de l'info sur l'extrapolation des données dans le titre du tooltip
						$extra = "";
						if( $this->datas_extra[$nel][$counter_name][$date] ){
							$data_value->setAttribute("extra", " - <span style='color:darkblue;'>Extrapolated Data</span>");
						}
						
						$data->appendChild($data_value);
					}
					
					$datas->appendChild($data);
					$nb_data++;
				}
				else {
					//on affiche un message pour dire que l'on a pas pris toutes les donnees
					echo "<span class=\"texteRougeBold\">".__T('U_INVESTIGATION_DASHBOARD_TOO_MANY_ELEMENTS',$MAX_SELECTION)."</span>";
					break;
				}
			}
		}

		return $datas;
	}
}
