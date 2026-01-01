<?php
/*
	Ajout de la version 1.2.0 :
	- gestion des boutosn radio.
	- debug divers.
	
	27/05/2009 SPS :
		- adaptation de la classe pour instancier n selecteurs

   17/08/2010 MMT
      -  bz 16749 Firefox compatibility use setAttribute for popalt(this.alt_on_over)

   06/06/2011 MMT DE 3rd Axis
      - ajout de la methode setSelectionSaveHookURL pour resoudre problème affichage selection courrante sous
  		  firefox


*/
/**
* Classe networkElementSelection
*
* Permet d'afficher une IHM listant des éléments réseaux
*
* @package networkElementSelection
* @author christophe chaput c.chaput@astellia.com
* @version 1.2.0
* @copyright ©2008 Astellia 
*/
class networkElementSelection
{
	// Liste des propriétés.
	
	/**
	* Titre de la fenêtre javascript.
	* @var string
	*/
	protected $windowTitle;
		
	/**
	* Style CSS à appliquer au bouton qui ouvre l'interface lorsque aucun élément n'est sélectionné.
	*	$this->openButtonProperties['id']  : id de l'élément html qui permet d'afficher l'IHM
	*	$this->openButtonProperties['css_class_on']  : classe css à appliquer quand un élément est sélectionné
	*	$this->openButtonProperties['css_class_off'] : classe css à appliquer quand aucun élément n'est sélectionné
	*
	* @var array
	*/
	protected $openButtonProperties;
	
	/**
	* Tableau contenant la liste des onglets à afficher. Pour chaque onglet on définit la label et les url (avec paramètre GET) vers
	* le fichier qui permet de liste le contenu et celui qui permet de faire la recherche.
	* $tabList[$i]['label']
	*		  ['url_get']
	*		  ['url_search']
	* @var array
	*/
	protected $tabList;
	
	/**
	* Contient le nom de la propriété name et id du champ input hidden dans lequel la sélection courante est sauvegardée.
	* 	$saveFieldProperties['id']
	* 	$saveFieldProperties['separator']
	* 	$saveFieldProperties['display']
	* 	$saveFieldProperties['value']
	* @var array
	*/
	protected $saveFieldProperties;

	
	/**
	* Préfixe à ajouter à tous les identifiants 'id' html
	* @var string
	*/
	protected $htmlIdPrefix;
	
	/**
	* fichier qui sera intérrogé via une requête ajax lorsque l'utilisateur clique sur le bouton 'View current selection content'
	* @var string
	*/
	protected $viewCurrentSelectionContentUrl;
	
	/**
	* définit si le bouton 'View current selection content' est visible.
	* @var bool
	*/
	protected $viewCurrentSelectionContent;
	
	/**
	* booléen qui définit si on doit afficher le champ de saisie libre.
	* @var bool
	*/
	protected $freeInputField;
	
	/**
	* booléen qui définit si on doit afficher le champ de saisie libre.
	* @var bool
	*/
	protected $setOpenButtonPropertiesCalled;
	
	/**
	* Définit si l'application fonctionne avec des boutons radio ou des checkbox.
	* @var string
	*/
	protected $buttonMode;
	
	/**
	* 0 = debug désactivé, 1 = debug activé.
	* @var string
	*/
	protected $debug;
	
	// AJout BBX 01/12/2009
	// BZ 11482
	/**
	* Ce tableau contient la liste des structures HTML des icônes à afficher dans l'IHM
	* @var array
	*/
	protected $optionList = array();        
            
        /**
         * 16/01/2013 BBX
         * DE Ne Filter
         * @var boolean 
         */
        protected $oldVersion = false;

	/**
	* Constructeur de la classe networkElementSelection
	*
	*/
	public function __construct()
	{
		// On initialise les valeurs par défaut.
		$this->setWindowTitle('Default title');
		$this->setHtmlIdPrefix('htmlPrefix');
		$this->setSaveFieldProperties('nel_selecteur', '', '|s|', 0);
		$this->viewCurrentSelectionContent = false;
		$this->setFreeInputField(0);
		$this->setOpenButtonPropertiesCalled = false;
		// Par défaut le debug est désactivé.
		$this->setDebug(0);
                // 14/01/2012 BBX
                // DE Ne Filter : RAZ du filtre au chargement
                if(isset($_SESSION['nefiltering'])) unset($_SESSION['nefiltering']);
	}
	
	/**
	* setWindowTitle : permet de définir le titre de la fenêtre
	*
	* @param string $title titre de la fenêtre
	*/
	public function setWindowTitle($title)
	{
		$this->windowTitle = $title;
	}
	
	/**
	* setDebug : permet d'activer / désactiver le debug. Par défaut 
	*
	* @param string $val 0 = désactivé, 1 = activé.
	*/
	public function setDebug($val)
	{
		$this->debug = $val;
	}
	
	/**
	* setFreeInputField : permet de définir si le champ de saisie libre est visible.
	*
	* @param bool $display : 0 ou 1 
	*/
	public function setFreeInputField($display)
	{
		$this->freeInputField = $display;
	}
	/**
	* setButtonMode : Définit si l'application fonctionne avec des boutons radio ou des checkbox.
	*
	* @param string $mode : 'radio' ou 'checkbox'
	*/
	public function setButtonMode($mode)
	{
		if ( $mode != 'checkbox' && $mode != 'radio' )
		{
			$this->displayException(__METHOD__." : $mode parameter must be equal to 'radio' or 'checkbox'.");
		}
		$this->buttonMode = $mode;
		$this->setOpenButtonPropertiesCalled = true;
	}
	
	/**
	* setHtmlIdPrefix : permet de définir un préfixe à ajouter à tous les identifiants 'id' html
	* cela permet d'éviter d'avoir des id identiques.
	* Attention, si cette méthode est utilisée, il faut l'appeler juste après le constructeur de la classe et 
	* venant la méthode setOpenButtonProperties pour que le préfixe soit bien pris en compte..
	*
	* @param string $prefix préfixe
	*/
	public function setHtmlIdPrefix($prefix)
	{
		// 22/07/2008 - Modif. benoit : on prend en compte le cas du prefixe vide. Dans ce cas, on n'ajoute pas l'underscore
		$this->htmlIdPrefix = (($prefix == "") ? "" : $prefix.'_');
	}
	
	/**
	* setOpenButtonProperties : permet de définir le style css du bouton qui permet d'ouvrir l'interface et son id.
	*
	* @param string $css_class_on classe css à appliquer au bouton quand un ou des éléments sont sélectionnés
	* @param string $css_class_off classe css à appliquer au bouton quand aucun élément n'est sélectionné
	* @param string $id id de l'élément html qui permet d'afficher l'IHM
	*/
	public function setOpenButtonProperties($id, $css_class_on='', $css_class_off='')
	{
		$this->openButtonProperties['id'] = $id;
		$this->openButtonProperties['css_class_on'] = 	$css_class_on;
		$this->openButtonProperties['css_class_off'] = 	$css_class_off;
		$this->setOpenButtonPropertiesCalled = true;
	}
	
	/**
	* setSaveFieldProperties : permet de choisir le nom du champ 'name' et 'id' du champ input hidden qui contient la liste des éléments sélectionnés.
	*
	* @param string $id nom de la propriété 'id' du champ
	* @param string $value valeur courrante
	* @param string $separator séparateur des valeurs stockées dans le champ input
	* @param bool $display 0 ou 1 si = 1, le champ caché qui permet de stocker les données est affiché.
	*/
	public function setSaveFieldProperties($id, $value, $separator, $display, $name="")
	{
		$this->saveFieldProperties['id'] = 			$id;
		$this->saveFieldProperties['name'] = 		($name !=="") ? $name : $id;
		$this->saveFieldProperties['separator'] = 	$separator;
		$this->saveFieldProperties['display'] = 	$display;
		$this->saveFieldProperties['value'] = 		$value;
	}

	/**
	 * 06/06/2011 MMT DE 3rd Axis
	 * set the url to be used in the JS function networkElementSelectionSaveHook to get the current selected elements
	 * This solves issues under firefox (current selection does not appear in tooltip)
	 * @param String $url
	 */
	public function setSelectionSaveHookURL($url)
	{
		$this->saveFieldProperties['url'] = $url;
	}
	
	/**
	* displayException :  affiche un message d'erreur.
	*
	* @param string $text texte à afficher.
	* @return void
	*/
	function displayException($text)
	{
		echo ('<div class="networkElementSelectionError"><b>'.__CLASS__.' class Error </b>:'.$text.'</div>');
	}
	
	/**
	* addTabInIHM : permet d'ajouter un onglet à l'interface.
	*
	* @param id string $id identifiant id qui sera donné au div contenant la liste des éléments (note : le div qui affiche le nom de l'onglet a pour id : 'id'_title)
	* @param string $label label à afficher dans l'IHM
	* @param string $url_to_get_list url complète vers le fichier qui permet de récupérer la liste des éléments de l'onglet. Il faut que cette url contienne les paramètre GET si il y en a.
	* @param string $url_for_search url complète vers le fichier qui permet de recherche des éléments. Il faut que cette url contienne les paramètre GET si il y en a.
	* @pram string $selected_value valeur de l'onglet /!\ ce paramètre n'est utilisé que quand on est en buttonMode = 'radio', c'est la valeur sélectionnée dans l'onglet concerné.
	*/
	public function addTabInIHM($id, $label, $url_to_get_list, $url_for_search='', $selected_value='')
	{
		if ( empty($id) || empty($label) || empty($url_to_get_list) )
		{
			$this->displayException(__METHOD__.' : one or several parameters missing.');
		}
		$i = count($this->tabList) + 1;
		$this->tabList[$i]['id'] = 			$id;
		$this->tabList[$i]['label'] = 		$label;
		$this->tabList[$i]['url_get'] = 	$url_to_get_list;
		$this->tabList[$i]['url_search'] = 	$url_for_search;
		$this->tabList[$i]['selected_value'] = 	$selected_value;
		
		// On vérifie si l'utilisateur n'a pas saisie
	}
	
	/**
	* setViewCurrentSelectionContentButtonProperties : permet de paramétrer le fichier qui sera intérrogé via une requête ajax lorsque l'utilisateur
	* clique sur le bouton 'View current selection content'. Ce fichier peut avoir des paramètres. Il doit retourner la liste des éléments contenu dans la sélection courante avec leurs labels.
	*
	* Le script rajoute automatiquement en paramètre $_GET :
	*	$_GET['save_input'] : contenu du champ input dans lequel est stocké la sélection courrante. Attention, le nom du paramètre GET correspond au nom du champ input
	*	paramétré dans la méthode setSaveFieldProperties. (save_input est noté à totre d'exemple)
	* 	$_GET['separator'] : contient le séparateur des champs enregistrés.
	*
	* Attention : si url_to_get_selection est vide, le bouton sera affiché mais le contenu retourné sera une simple liste des identifiants stockés
	* dans le champ input.
	*
	* Note : si cette méthode n'est pas appelée, le bouton n'est pas affiché.
	*
	* @param string $url_to_get_selection 
	*/
	public function setViewCurrentSelectionContentButtonProperties($url_to_get_selection = '')
	{
		$this->viewCurrentSelectionContentUrl = $url_to_get_selection;
		$this->viewCurrentSelectionContent = true;
	}

	
	/**
	* generateTab : permet de générer le code html des onglets
	*
	* @return html
	*/
	private function generateTab($checkall = true)
	{
		$html_tab = 'No tab specified : use addTabInIHM() method to add some tabs.';
		
		// Si on a des onglets à afficher, on construit la structure html.
		if ( count($this->tabList) >= 1 )
		{
			$html_tab = '';
			foreach ($this->tabList as $tab)
			{
                            // Titre d'une barre d'onglet.
                            if($checkall) {
				$html_tab .= '
                                <div style="position:absolute;left:0px;">
                                    <input type="checkbox" id="'.$this->htmlIdPrefix.$tab['id'].'_checkall" 
                                        style="display:none" 
                                        value="1" 
                                        onclick="neselCheckall()"
                                        onmouseover="updateNeSelectionWindowStatus(\'Click here to check / uncheck all displayed elements\')"
                                        onmouseout="updateNeSelectionWindowStatus(\'\')" />
                                </div>';
                            }
                            $html_tab .= '
                                <div id="'.$this->htmlIdPrefix.$tab['id'].'_title" 
                                    class="accordion_title" 
                                    onclick="openTab(\''.$tab['id'].'\',\''.$tab['url_get'].'\',\''.$tab['url_search'].'\')">
                                    '.$tab['label'].'
                                </div>
                            ';

                            // Div de l'onglet qui contiendra la liste des données
                            $html_tab .= "
                                    <div id='".$this->htmlIdPrefix.$tab['id']."' class='accordion_element' style='display:none'>
                                    </div>
                            ";
			}
		}
		else
		{
			$this->displayException(__METHOD__.', No tab specified, use addTabInIHM() method to add some tabs.');
		}
		
		return $html_tab;
	}
	
	/**
	* generateSearch : permet de générer le code html de la partie de recherche
	*
	* @return html
	*/
	private function generateSearch()
	{
		$html_search = '';
		/*
			Restriction : dans le champ input où l'utilisateur saisit l'élément à recherche (id='prefixe+neSearchInput')la propriété autocomplete='off' permet de ne pas afficher 
			les suggestions du navigateur si l'utilisateur a sélectionné l'option 'enregistrer les données saisies dans les formulaires'. 
			Cela ne fonctione que sur Internet Explorer.
			25/11/2008 BBX : on ajoute un div "neSearchInputContainer" qui contient el champs de saisie afin de pouvoir détruire puis reconstruire son contenu.
		*/
		$html_search = "
			<div style='margin:4px;' id='".$this->htmlIdPrefix."divNeSearch' style='display:none;'>
				<fieldset style='padding:2px;'>
					<label for='".$this->htmlIdPrefix."neSearchInput'>
						Search on : <span id='".$this->htmlIdPrefix."divNeSearch_label'></span>
					</label> 
					<br />
					<div id='".$this->htmlIdPrefix."neSearchInputContainer'>
						<input type='text' name='".$this->htmlIdPrefix."neSearchInput' id='".$this->htmlIdPrefix."neSearchInput' autocomplete='off' 
							class='zoneTexteStyleNeSelection' style='width:340px;'/>
					</div>
					<div class='autocomplete' id='".$this->htmlIdPrefix."auto_completor'></div>
					&nbsp;
					<input type='submit' id='".$this->htmlIdPrefix."neSearchSubmit' class='bouton_go' value='' />
				</fieldset>
			</div>
		";
		
		return $html_search;
	}
	
	/**
	* generateOption : permet de générer le code html de la zone options (icônes view current selection...)
	*
	* @return html
	*/
	private function generateOption()
	{
		$html_option = '';
		$html_option = '
			<div style="width:20px;float:left; padding:3px; padding-left:10px;">
			<input type="button" class="bouton_view_selection" 
				onclick="loadCurrentSelection(\''.$this->viewCurrentSelectionContentUrl.'\')" 
				value=""
				onmouseover="updateNeSelectionWindowStatus(\'View current selection content\')" 
				onmouseout="updateNeSelectionWindowStatus(\'\')"/>
			</div>
		';
		
		return $html_option;
	}
	
	/**
	* generateButton : permet de générer le code html des boutons de l'IHM (bouto save, reset...)
	*
	* @return html
	*/
	private function generateButton()
	{
		$html_button = '';
		$html_button = '
			<div class="buttonsNaSelection">
				<!-- Bouton Reset -->
				<input type="button" onclick="resetNeSelection(); closeNeSelection();" 
					value="Reset" class="buttonNaSelection"
					onmouseover="updateNeSelectionWindowStatus(\'Reset current selection\')" 
					onmouseout="updateNeSelectionWindowStatus(\'\')"/>
				&nbsp;&nbsp;
				<!-- Bouton Save -->
				<input type="button" onclick="saveCurrentSelection(); closeNeSelection();" 
					value="Save" class="buttonNaSelection"
					onmouseover="updateNeSelectionWindowStatus(\'Save current selection\')" 
					onmouseout="updateNeSelectionWindowStatus(\'\')"/>
			</div>
		';
		
		return $html_button;
	}
	
	/**
	* generateSaveField : permet de créer le champ input qui stocke les données sélectionnées.
	* On créer 2 champs : un pour stocker les éléments sélectionnés et l'autre pour stocker le séparateur choisit.
	* Si le mode buttonMode est 'radio', on créé une liste de champ caché. Il y a un champ par onglet. Le nom de chaque champ
	* correspond à l'ide de l'onglet+'_accordion_save'.
	* @return html
	*/
	private function generateSaveField()
	{
		$btSavedisplay = ($this->debug==0) ? 'hidden' : 'text' ;
		
		if ( $this->buttonMode != 'radio' )
		{
			echo "
				<input type='$btSavedisplay' 
					id='".$this->saveFieldProperties['id']."'
					name='".$this->saveFieldProperties['name']."' 
					value='".$this->saveFieldProperties['value']."' 
					style='width:900px'/>
				";
		}
		else
		{
			// On parcourt la liste des onglets et on créé un champ caché pour chaque onglet dans lequel sera stockée la valeur de chaque onglet.
			if ( count($this->tabList) >= 1 )
			{
				$html_tab = '';
				foreach ($this->tabList as $tab)
				{
					$name = $this->htmlIdPrefix.$tab['id'].'_accordion_save';
					if ( $this->debug ) $html_tab .= " <br/> $name : ";
					$html_tab .= " 
						<input type='$btSavedisplay' 
							id='$name' name='$name' 
							value='".$tab['selected_value']."' 
							style='width:400px'/>
					";
				}
				echo $html_tab;
			}
			else
			{
				$this->displayException(__METHOD__.', No tab specified, use addTabInIHM() method to add some tabs.');
			}
		}

		
	}
	
	/**
	* generateFreeInputField : permet de créer le champ input qui permet à l'utilisateur de saisir directement un élément.
	* @return html
	*/
	private function generateFreeInputField()
	{
		$html = '';
		$html = "
		<div id='".$this->htmlIdPrefix."FreeInputFieldDiv' style='display:none;'>
			<div class='freeInputFieldNaSelection' style='width:235px;' >
				<input type='text' class='zoneTexteStyleNeSelection' value='' 
					id='".$this->htmlIdPrefix."FreeInputField' style='width:200px'/>
				<input type='button' value='' id='".$this->htmlIdPrefix."FreeInputFieldBtn' 
					onclick='saveInNeSelection($(\"".$this->htmlIdPrefix."FreeInputField\").value); displayCurrentSelection(\"\");' class='addInNeSelection_btn'/>
			</div>
		</div>
		";
		return $html;
	}
	
	/**
	* generateIHM : permet de générer le code html de l'IHM
	*
	* 27/05/2009 SPS : on ecrit ici tous les elements propres a chaque selecteur, avec egalement les boutons d'appel (auparavant a l'exterieur de cette classe)
	* 
	* @return html
	*/
	public function generateIHM()
	{
		if ( !$this->setOpenButtonPropertiesCalled )
		{
			$this->displayException('You must called the method setOpenButtonProperties before the method '.__METHOD__);
		}
		
		$html = '';
	
		/* 27/05/2009 SPS : ajout du bouton et des feuilles de style*/
                // 13/06/2013 NSE bz 34258 : filter icon under drop down list
		$html .= "
		<!-- feuille de style pour le bouton d'appel -->
		<style type=\"text/css\">
			#".$this->htmlIdPrefix."img { padding-top:5px; margin-left:2px; height:16px; width:20px; cursor:pointer; float: left;}
			#".$this->htmlIdPrefix."img.bt_off { background: url(".NIVEAU_0."images/icones/select_na_on.png) left no-repeat;}
			#".$this->htmlIdPrefix."img.bt_on { background: url(".NIVEAU_0."images/icones/select_na_on_ok.png) left no-repeat;}
		</style>\n";
	
		// bouton appellant la fenetre (cf fonction openNaSelection du fichier networkElementSelection.js)
		$html .= "
		<!-- bouton appellant le selecteur -->

      <!-- 17/08/2010 MMT bz 16749 Firefox compatibility use getAttribute for popalt(this.alt_on_over) -->
		<div id=\"".$this->htmlIdPrefix."img\" class=\"bt_off\"
			onmouseover=\"popalt(this.getAttribute('alt_on_over'));\"
			onmouseout=\"kill()\"
			alt_on_over=\"".$this->windowTitle."\"
			onclick=\"openNaSelection('".$this->htmlIdPrefix."')\">
		</div>\n";
		
	
		// Div principal
		$html .= "<div id='".$this->htmlIdPrefix."window_select_na' style='width:400px; display:none;'>";
			// Div contenant l'image de loading.
			$html .= "
				<div id='".$this->htmlIdPrefix."selection_na_loading' style='display:none;'>
					<span class='loadingNaSelectionContent'>Loading content</span>
				</div>
			";
		
			// Ajout de la zone de recherche.
			$html .= $this->generateSearch();
		
			// Affichage du contenu.
			$html .= "<div id='".$this->htmlIdPrefix."contenu_na_selection' class='contenu_na_selection_class'>";
			
				// On ajoute les onglets.
				$html .= $this->generateTab();
			
			$html .= "</div>";// fin affichage du contenu.
			
			// Affichage de la zone contenant les icônes d'options, le bouton save, reset...
			$html .= "<div class='toolsNaSelection'>";

				// ajout BBX 01/12/2009
				// BZ 11482
				// On affiche les icônes personnalisées si il y en a.
				if ( !empty($this->optionList) )
				{
					$html .= '<div style="float:left;"><small>Option :</small>&nbsp;';
					foreach($this->optionList as $one_icon)
						$html .= '&nbsp;'.$one_icon;
					$html .= '</div>';
				}
				// FIN BZ 11482

				// Affichage de champ de saisie libre.
				if ($this->freeInputField)
					$html .= $this->generateFreeInputField();
				// Affichage de la zone options (view current selection...)
				if ($this->viewCurrentSelectionContent)
					$html .= $this->generateOption();
				// Affichage des boutons (save, reset)
				$html .= $this->generateButton();

			$html .= "</div>";// fin affichage du options + boutons.
			
			// Div permettant d'afficher des messages.
			$html .= "
				<div id='".$this->htmlIdPrefix."msgNeSelection' style='margin:3px;display:none;'>				
					<fieldset>
						<legend>List of selected elements&nbsp;</legend>
						<div style='height:80px; overflow:auto; margin:2px; border:1px #D0D0D0 solid;'
							id='".$this->htmlIdPrefix."msgNeSelectionContent'>
						</div>
					</fieldset>
				</div>
				";

		$html .= "</div>"; // fin Div principal
		
		echo $html;
		
		// Initialisation des variables javascript.
		$this->initJavascriptValues();
		
		// Initialisation du champ caché contenant les valeurs sélectionnées.
		$this->generateSaveField();
		
		// 20/06/2013 GFS - Bug 32688 - [REC][IU 5.3.0.01][TC TA-57176][GIS] There is a problem about GUI
		// La fonction de génération de tooltip n'est pas appelé dans le cas où le sélecteur vient du GIS 
		// 08/07/2013 MGO - Bug 32688 - [REC][IU 5.3.0.01][TC TA-57176][GIS] There is a problem about GUI
		// Suppression de l'appel à la fonction lors de la sélection du niveau d'agrégation si le sélecteur vient du GIS
		if (strpos($this->htmlIdPrefix, "gis_counters") === false && strpos($this->htmlIdPrefix, "gis_nel") === false) {
			//si on a un selecteur, on appelle la fonction qui genere le tooltip
			// 22/11/2011 BBX
			// BZ 23263 : On attend que l'élément soit chargé avant d'agir
			echo "
				<script type=\"text/javascript\">
		                Event.observe(window, 'load', function() {
					if ($('".$this->htmlIdPrefix."selecteur')) {
						networkElementSelectionSaveHook('".$this->htmlIdPrefix."');
					}
		                });
				</script>";
		}
	}

	/**
	* initJavascriptValues : permet de créer des champ caché contenant les paramètres nécessaires au fonctionnement
	* du script javascript.
	*
	*/
	private function initJavascriptValues()
	{
		$html_input_hidden = '';
	
		// Seul ce champ a un id fixe car on doit récupérer le préfixe appliqué au id de l'interface.
		$html_input_hidden .= "
			<input type='hidden' 
				id='networkElementSelection_htmlIdPrefix' 
				value='".$this->htmlIdPrefix."' />
		";
		
		// Titre de la fenêtre.
		$html_input_hidden .= "
			<input type='hidden' 
				id='".$this->htmlIdPrefix."windowTitle' 
				value='".$this->windowTitle."' />
		";
		
		// Identifiant du bouton qui permet d'afficher l'interface.
		$html_input_hidden .= "
			<input type='hidden' 
				id='".$this->htmlIdPrefix."openButtonId' 
				value='".$this->openButtonProperties['id']."' 
				css_class_on='".$this->openButtonProperties['css_class_on']."'
				css_class_off='".$this->openButtonProperties['css_class_off']."'
				/>
		";
		
		// Identifiant du champ où sont stockées les valeurs.
		$html_input_hidden .= "
			<input type='hidden' 
				id='".$this->htmlIdPrefix."saveFieldId' 
				value='".$this->saveFieldProperties['id']."' />
		";
		
		
		$html_input_hidden .= "
			<input type='hidden'
				id='".$this->htmlIdPrefix."saveFieldId_separator'
				value='".$this->saveFieldProperties['separator']."' />
		";
		
		// Mode de l'IHM 'radio' ou 'checkbox'.
		$html_input_hidden .= "
			<input type='hidden' 
				id='".$this->htmlIdPrefix."buttonModeValue' 
				value='".$this->buttonMode."' />
		";
                
                // 16/01/2013 BBX
                // DE Ne Filter
                // Old mode
                $html_input_hidden .= "
                        <input type='hidden'
                                id='".$this->htmlIdPrefix."oldVersion'
                                value='".($this->oldVersion ? '1' : '0')."' />
                ";

		//06/06/2011 MMT DE 3rd Axis utilise un champ caché plutot qu'une division pour stoquer l'url
		// ceci permet d'eviter des problèmes sous firefox: utilisation dans networkElementSelectionSaveHook
		if(array_key_exists('url', $this->saveFieldProperties)){
			$html_input_hidden .= "
			<input type='hidden' 
				id='".$this->htmlIdPrefix."url' 
				value='".$this->saveFieldProperties['url']."' />
			";
		}
		
		echo $html_input_hidden;
	}
	
	// Ajout BBX 01/12/2009
	// BZ 11482
	/**
	* addIcon : permet d'ajouter un onglet à l'interface.
	*
	* @param $label string : label à afficher lorsque l'utilisateur survol l'icône.
	* @param $css_class string :  classe css du bouton
	* @param $js_onclick string :  fonction js à appeler
	*/
	public function addIcon($label, $css_class, $js_onclick)
	{
		if ( empty($label) || empty($css_class) || empty($js_onclick) )
		{
			$this->displayException(__METHOD__.' : one or several parameters missing.');
		}
		$to_add = ''; // chaine html à ajouter
		$to_add = '
			<input type="button" class="'.$css_class.'" onClick="'.$js_onclick.'"
				value=""
				onmouseover="updateNeSelectionWindowStatus(\''.$label.'\')" 
				onmouseout="updateNeSelectionWindowStatus(\'\')"
				/>
		';
		// On met le contenu HTML dans le tableau optionList que l'on parcourt dans la méthode generateIHM()
		$this->optionList[] = $to_add;
	}
	// FIN BZ 11482
        
        /**
         * 16/01/2013 BBX
         * DE Ne Filter
         */
        public function setoldVersion()
        {
            $this->oldVersion = true;
        }
}
?>
