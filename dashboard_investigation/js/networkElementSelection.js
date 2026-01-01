/*
	26/08/2009 GHX
		- Correction du BZ 11230 [REC][T&A CB 5.0][TC#40586][TS#UC16-CB50][TP#1][INVESTIGATION DASHBOARD]: erreur accès ajax pour grand nb NE
			-> Utilisation de POST au lieu de GET
*/
/**
*
* Permet d'afficher une IHM listant des éléments réseaux (Partie Javascript)
* Les fonctions sont utillsées par le module networkElementSelection
*
* CCT1 05/08/08 : gestion des boutons radio dans l'interface (spécifique TT).
*
* @package networkElementSelectionJS
* @author christophe chaput c.chaput@astellia.com
* @version 1.1.0
* @copyright ©2008 Astellia 
*/

/*
	Modfi CCT1 04/09/08 :
	- quand on est en mode bouton radio, on affiche le label de l'onglet dans lequel l'élément a été sauvegardé.
	- si on est en mode bouton radio et que il y a un appel ajax pour afficher la liste des éléments sélectionnés, on ajoute
	à l'url les valeurs de chaque onglet par onglet

	maj 19/11/2008 - MPR : On modifie la valeur de id_to_check
   			     Cette valeur doit correspondre à n'importe quelle valeur que reçoit chaque chekbox
				 
	maj 25/11/2008 BBX :
		- Destruction du champ de recherche lors d'un initSearch
		- Gestion de la recherche sur un élément non existant (création de l'élément si l'id_to_check est valide)

	27/05/2009 - SPS :
		- adaptation du script pour lancer n instances du selecteur
		
	17/06/2009 - BBX :
		- on ferme une éventuelle fenêtre déjà ouverte
	13/07/2009 GHX
		- Correction du BZ 10597 [REC][Investigation Dashboard]: view selection mal raffraîchi après reset
*/

/**
* Liste des variables globales. (toutes les variables globales commence par _)
*/
// Fenêtre de sélection des NA.
var _winNaSelection;
// Définit si l'utilisateur a déjà ouvert la fenêtre.
var _winNaSelection_create = false;
// Préfixe des identifiants html id.
var _htmlIdPrefix = '';
// Bouton qui permet d'ouvrir l'interface.
var _openButton;
// Id de l'onglet actuelement sélectionné.
var _idCurrentTab = '';	
// Liste des éléments sélectionnés après initialisation (utilisé lors du reset).
var _newSelectedValues = new Array();
// Permet de stocker le contenu du champ de la sélection courrante.
var _listOfSelectedElements = '';
// Mode de fonctionnement avec bouton 'radio' ou bouton 'checkbox'.
var _buttonMode = '';
// 16/01/2013 BBX
// DE Ne Filter
var _oldVersion = false;
var _filterLabel    = ' <b>(filtered)</b>';
var _filterLabelIE  = ' <B>(filtered)</B>';

/**
* getId : retourne l'identifiant passé en paramètre avec le préfixe _htmlIdPrefix
*
* @param string id identifiant de l'élément
* @return string
*/
function getId(id)
{	return _htmlIdPrefix+id;
}

/**
 * setHtmlIdPrefix : setter de la variable _htmlIdPrefix
 * 
 * @params string prefix prefixe de l'instance du selecteur
 * */
function setHtmlIdPrefix(prefix) {
	_htmlIdPrefix = prefix;
}

/**
 * setOpenButton : setter de la variable _openButton
 * 
 * @params string prefix prefixe de l'instance du selecteur
 **/
function setOpenButton(prefix) {
	_openButton = $(prefix+'openButtonId').value;
}
	
/**
* openNaSelection : Permet d'ouvrir la fenêtre de sélection des éléments réseaux (utilise la class js prototype window)
* 
* 27/05/2009 SPS : cette fonction va initialiser les selecteurs et non plus initNASelection
* 
* @params string prefix prefixe de de l'instance du selecteur
*/
function openNaSelection(prefix)
{	
	setHtmlIdPrefix(prefix);
	setOpenButton(prefix);
        
        // 16/01/2013 BBX
        // DE Ne Filter
        _oldVersion = parseInt($(_htmlIdPrefix+'oldVersion').value);
	
	var titre = $(getId('windowTitle')).value;
	$(getId('divNeSearch')).hide();
	// 17/06/2009 BBX : on ferme une éventuelle fenêtre déjà ouverte
	if(_winNaSelection) {
		closeNeSelection();
	}
		
	_winNaSelection = new Window({ 
			className:"alphacube",
			title: titre,
			width:410, height:200,
			minWidth:410, minHeight:100,
			resizable:false,
			minimizable:false,
			recenterAuto:false,
			onClose:closeNeSelection,
			maximizable:false
		}); 
	_winNaSelection.setZIndex(2000);
	_winNaSelection.setContent(getId('window_select_na'));
	_winNaSelection.showCenter(false,135);
	_winNaSelection.updateHeight();
	
	_winNaSelection_create = true;
        
        // 17/01/2013 BBX
        // DE Filter NE : permet de corriger le bug de hauteur
        _winNaSelection.setSize('410', '100', false);  
	
	// 10:42 13/07/2009 GHX
	// Correction du BZ 10597  [REC][Investigation Dashboard]: view selection mal raffraîchi après reset
	displayCurrentSelection('');
	
	// A chaque ouverture de la fenêtre ou initialise la liste des éléments sélectionné en fonction du mode.
	if ( _buttonMode != 'radio' )
	{
		var saveField = getId('saveFieldId');
		var saveFieldId = $(saveField).value;
		_listOfSelectedElements = $(saveFieldId).value;
	}
	else
	{
		/*
			On liste tous les éléments html dont l'id se termine par '_accordion_save'
		*/
		var elements = $$('input[id$="_accordion_save"]');
		_listOfSelectedElements = new Array();
		elements.each(function(item) {
			item_temp = new Array();
			item_temp['id'] = item.id;
			item_temp['value'] = item.value;
			_listOfSelectedElements.push(item_temp);
		});
	}
}

/**
*	Initialise la recherche sur une NA précise.
*	cf selection_des_na_recherche.js
*
* @param string url_search url complète vers le script de recherche.
* @return void
*/
function initSearch(url_search)
{	// Affichage du moteur de recherche.
	$(getId('divNeSearch')).show();
	
	// On met-à-jour le message qui se trouve au-dessus du champ input de recherche en copiant le titre de l'onglet qui est sélectionné ('Search on Titre onglet').
	$(getId('divNeSearch_label')).innerHTML = $(_idCurrentTab+'_title').innerHTML;
	
	// 25/11/2008 BBX : On détruit, puis on reconstruit le champ de saisie afin de réinitialiser l'autocomplétion
	if ( $(getId('neSearchInput')) ) {
		$(getId('neSearchInput')).remove();
		/* 27/05/2009 - SPS : on remplace _htmlIdPrefix par getId()*/
		var neSearchInputContent = "<input type='text' name='"+getId('neSearchInput')+"' id='"+getId('neSearchInput')+"' autocomplete='off' class='zoneTexteStyleNeSelection' style='width:340px;'/>";
		$(getId('neSearchInputContainer')).update(neSearchInputContent);		
	}
		
	// Calcul de l'id tab en cours
	var tab_array = _idCurrentTab.split(_htmlIdPrefix);
	var id_tab = tab_array[1];
	
	// Démarrage de l'auto completeur
	var array_url = url_search.split('?');
	var url = array_url[0];
	var parameterList = array_url[1];

	new Ajax.Autocompleter
	(
		getId('neSearchInput'), 
		getId('auto_completor'), url, 
		{
			method:'get',
			paramName:'debut',
			minChars:1,
			afterUpdateElement:scrollToItem,
			parameters: parameterList
		}
	);
}


/**
* closeNeSelection : permet de fermer la fenêtre de sélection.
*/
function closeNeSelection()
{
	// Si un Tab a été ouvert, on vide son contenu et on le cache.
	if ( _idCurrentTab != '' )
	{
		$(_idCurrentTab).innerHTML = '';
		$(_idCurrentTab).hide();
                // 16/01/2013 BBX
                // DE Ne Filter
                if(!_oldVersion) $(_idCurrentTab+'_checkall').hide();
		_idCurrentTab = ''; // permet de forcer le rechargement du contenu.
	}
	// On ferme la fenêtre mais on ne la détruit pas.
	_winNaSelection.close();
	// On change le style du bouton de sélection.
	var saveIsEmpty = true;

	if ( _buttonMode != 'radio' )
	{
		var saveFieldId = $(getId('saveFieldId')).value;
		if ($(saveFieldId).value != '')
			saveIsEmpty = false;
	}
	else
	{
		var elements = $$('input[id$="_accordion_save"]');
		elements.each(function(item) {
			if ( item.value != '' )
				saveIsEmpty = false;
		});
	}
	// 16/08/2010 NSE DE Firefox : on ne peut pas inventer des attributs (css_class_on/css_class_off)
	if ( saveIsEmpty ) {
            if($(_htmlIdPrefix+'openButtonId').css_class_off)
		$(_openButton).className = $(_htmlIdPrefix+'openButtonId').css_class_off;
            else
                $(_openButton).className = 'bt_off';
	} else {
            if($(_htmlIdPrefix+'openButtonId').css_class_on)
		$(_openButton).className = $(_htmlIdPrefix+'openButtonId').css_class_on;
            else
                $(_openButton).className = 'bt_on';
	}
}

/**
* updateNeSelectionWindowStatus : Pemet de mettre-à-jour la barre de status de la fenêtre NA selection
*
* @param string texte : texte à afficher
*/
function updateNeSelectionWindowStatus(texte)
{
	// Si on remet la barre des status à vide, on met quand même &nbsp; car sinon l'affichage de la pseudo fenêtre plante.
	texte = (texte == '') ? '&nbsp;' : texte;
	if ( _winNaSelection_create )
		_winNaSelection.setStatusBar(texte);
}

/**
* deleteQuote : permet de supprimer les quotes et double quotes de str
* @param string str
* @return string
*/
function deleteQuote(str)
{
	str = str.replace(/&/g, '') ;
	str = str.replace(/"/g, '') ;
	str = str.replace(/'/g, '') ;
	str = str.strip();
	return str;
}

/**
* openNaSelectionWith : permet d'ouvrir l'IHM dans l'onglet spécifier avec la valeur passée en paramètre.
* @param string tabId : identifiant de l'onglet
* @param string valueToSelect : identfiant de la valeur à sélectionner
* @param string url_get url vers le fichier qui permet de récupérer la liste des éléments à afficher
* @param string url_search url vers le fichier qui permet de gérer la recherche
*/
function openNaSelectionWith(tabId,valueToSelect,url_get,url_search)
{
	if( $(getId(tabId+'_accordion_save')) )
	{
		$(getId(tabId+'_accordion_save')).value=valueToSelect; 
		openNaSelection(); 
		openTab(tabId,url_get,url_search);
	}
}


/**
* saveInNeSelection : permet de stocker/supprimer la valeur val dasn la liste des éléments sélectionnés (_listOfSelectedElements)
* Cette liste n'est copiée dans le champ caché que si lu'ilisateur clique sur le bouton save.
*
*  @param string val valeur à sauvegarder
*/

function saveInNeSelection(val,label)
{	
	val = deleteQuote(val);
	if ( val.length > 0 )
	{
		// Gestion de la sauvegarde en mode checkbox
		if ( _buttonMode != 'radio')
		{
			var saveField = getId('saveFieldId');
			var saveFieldSeparator = getId('saveFieldId_separator');
			
			// On vérifie si la valeur est déjà présente. Si c'est le cas, on la supprime de la sélection courante sinon la valeur est ajoutée.
			// cas 1 bis : le champ de sauvegarde est vide, l'utilisateur sélectionne un élément puis l'enlève.
			
			if ( _listOfSelectedElements == val && _listOfSelectedElements.length == val.length )
			{
				_listOfSelectedElements = '';
			}
			// cas 1 : la valeur est stockée en début de chaine.
			else if ( _listOfSelectedElements.startsWith(val+$(saveFieldSeparator).value) )
			{
				_listOfSelectedElements = _listOfSelectedElements.substr(val.length+$(saveFieldSeparator).value.length,_listOfSelectedElements.length);
			}
			// cas 2 : la valeur est stockée en fin de chaîne.
			else if ( _listOfSelectedElements.endsWith($(saveFieldSeparator).value+val) )
			{
				_listOfSelectedElements = _listOfSelectedElements.substr(0,_listOfSelectedElements.length - (val.length+$(saveFieldSeparator).value.length));
			}
			// cas 3 : la valeur est stockée dans la chaine (sauf au début ou au milieu)
			else if ( _listOfSelectedElements.include($(saveFieldSeparator).value+val+$(saveFieldSeparator).value) )
			{
				var tabTemp = _listOfSelectedElements.split($(saveFieldSeparator).value+val+$(saveFieldSeparator).value);
				_listOfSelectedElements = tabTemp[0];
				if ( tabTemp[1] )
					_listOfSelectedElements += $(saveFieldSeparator).value+tabTemp[1];
			}	
			else
			{
				// Si il n'y a pas d'enregistrements dans le champ on ajoute simplement la valeur sinon on ajoute la valeur + le séparateur.
				if ( _listOfSelectedElements.length == 0 )
					_listOfSelectedElements += val;
				else
					_listOfSelectedElements += $(saveFieldSeparator).value + val;
			}
		}
		// Gestion de la sauvegarde en mode boutons radio.
		else
		{
			_listOfSelectedElements.each(function(item) {
				if ( item['id'] == (_idCurrentTab+'_accordion_save') )
				{
					// Si l'utilisateur clique sur un élément qui est déjà sélectionné, on le supprime.
					if ( item['value'] == val )
					{
						// Si c'est un champ de saisie, on le vide.
						if ($(getId('FreeInputField')))
							if ( $(getId('FreeInputField')).value != '' )
							{
								$(getId('FreeInputField')).clear();
								$(getId('FreeInputFieldBtn')).className = 'addInNeSelection_btn';
							}
						
						if ( $(item['value']) )
							$(item['value']).checked = false;
						
						item['value'] = '';
					}
					else
					{
						// Si un élément était enregistré dans l'onglet courant et que l'utilisateur saisit un élément dans le champ
						// de saisie libre, il faut désélectionner le bouton radio.
						if ( item['value'] != '' )
							if ( $(item['value']) )
								if ( $(item['value']).checked == true )
									$(item['value']).checked = false;
						
						if ($(getId('FreeInputField')))
						{
							if ( $(val) )
							{
								$(getId('FreeInputFieldBtn')).className = 'addInNeSelection_btn';
								$(getId('FreeInputField')).clear();
							}
							else
							{
								if ( val ==  $(getId('FreeInputField')).value )
									$(getId('FreeInputFieldBtn')).className = 'deleteInNeSelection_btn';
							}	
						}
						
						item['value'] = val;
					}
				}
			});
		}
	}
	
	saveCurrentSelection();
	
}



/**
* resetNeSelection : vide la sélection courante (champ input et décoche les checkbox)
*/
function resetNeSelection(){
	
	// On mets le champ de stockage à vide.
	if ( _buttonMode != 'radio' )
	{
		_listOfSelectedElements = '';
		var saveFieldId = $(getId('saveFieldId')).value;
		
		$(saveFieldId).clear();
	}
	else
	{
		_listOfSelectedElements.each(function(item) {
			$(item['id']).clear();
		});
		_listOfSelectedElements = new Array();
	}
	
	
	// MaJ SLC -- 23/09/2008
	// hook: si la fonction networkElementSelectionSaveHook() est définie, on l'execute
	// ce qui permet a la page qui héberge le module de lancer une fonction quand on
	// sauvegarde une selection
	
	/* 27/05/2009 SPS */
	//met a jour le tooltip du bouton qui lance le selecteur avec les elements selectionnes
	if (typeof(networkElementSelectionSaveHook) == 'function') networkElementSelectionSaveHook(_htmlIdPrefix);
}

/**
* loadTabContent : permet de charger le contenu d'un onglet lorsque l'utilisateur clique dessus
*
* @param string url url complète (avec les paramètres) vers le fichier php
* @return void
*/
function loadTabContent(url)
{
	var saveField = getId('saveFieldId');

	var saveFieldSeparator = getId('saveFieldId_separator');
	var saveFieldId = $(saveField).value;
	
	// Requête Ajax.
	
	new Ajax.Request(url,
	{
		// 11:43 26/08/2009 GHX
		// BZ 11230
		method:'post',
		parameters: {current_selection: _listOfSelectedElements, separator: $(saveFieldSeparator).value, oldVersion: _oldVersion},
		onSuccess: function(transport){
			var response = transport.responseText || "no response text";
			// Mise-à-jour du contenu du tab.
			// Si il y a un champ de saisie libre, on l'ajoute dans l'affichage.
			if ( $(getId('FreeInputFieldDiv')) )
				response = $(getId('FreeInputFieldDiv')).innerHTML + response;
			
			$(_idCurrentTab).innerHTML = response;
			// On met-à-jour la hauteur de la fenêtre
			_winNaSelection.updateHeight();
			
			if ( _buttonMode != 'radio' )
			{
				// On coche les checkbox (si besoin)
				var saveField = getId('saveFieldId');
				var saveFieldSeparator = getId('saveFieldId_separator');
				var saveFieldId = $(saveField).value;
				
				var tabTemp = _listOfSelectedElements.split($(saveFieldSeparator).value);
				tabTemp.each(function(item) {
					if( $(item) )
						$(item).checked = true;
				});
			}
			else
			{
				/*
					On coche la bouton radio si une valeur existe dans la sélection courrante
					Si il y a une valeur pour l'onglet courrant masi qu'elle n'existe pas dans la liste chargée, c'est une valeur saisie
					directement par l'utilisateur donc on la met dans le champ de saisie.
					
				*/
				_listOfSelectedElements.each(function(item) {
					if ( item['id'] == (_idCurrentTab+'_accordion_save') )
					{
						// Si l'élément existe, on le sélectionne
						if ( item['value'] != ''  )
						{
							// L'élément est dans la liste, on coche le bouton radio.
							if ( $(item['value']) )
							{
								$(item['value']).checked = true;
							}
							// L'élément n'est pas dans la liste, on le place donc dans le champ de saisie.
							else
							{
								$(getId('FreeInputField')).value = item['value'];
								$(getId('FreeInputFieldBtn')).className = 'deleteInNeSelection_btn';
							}
						}
					}
				});
			}
	    },
		onFailure: function(){ alert('Application can\'t access to \n'+url); }
	});
}

/**
* openTab : Ouvre / ferme les div disposés les uns en dessous des autres.
* - ouvre le div cliquez current_obj
* - ferme tous les autres div contenu dans le tableau listeNaJS.
* - charge si besoins le contenu du div ouvert.
* @param string id id du div contenant de l'songlet sur lequel l'utilisateur a cliqué
* @param string url_get url vers le fichier qui permet de récupérer la liste des éléments à afficher
* @param string url_search url vers le fichier qui permet de gérer la recherche
* @return void
*/
function openTab(id,url_get,url_search)
{	
	// Au cas où l'utilisateur cliquerait plusiseurs fois à la suite le même élément.
	if ( _idCurrentTab != getId(id) )
	{
		// Fermeture du Div précédent si il existe.
		if ( $(_idCurrentTab) )
		{
			// On vide le contenu. (permet d'alléger la page si les éléments chargés sont volumineux).
			$(_idCurrentTab).innerHTML = '';
			$(_idCurrentTab).hide();
                        // 16/01/2013 BBX
                        // DE Ne Filter
                        if(!_oldVersion) $(_idCurrentTab+'_checkall').hide();
		}
	
		_idCurrentTab = getId(id);
		// On affiche l'icône de chargement.
		$(_idCurrentTab).innerHTML = $(getId('selection_na_loading')).innerHTML;
		// On affiche le Div.
		$(_idCurrentTab).show();
                // 16/01/2013 BBX
                // DE Ne Filter
                if(!_oldVersion) $(_idCurrentTab+'_checkall').show();
		// On met-à-jour la taille de la pseudo-fenêtre.
		_winNaSelection.updateHeight();
		// Initialisation de la zone de recherche.
		// Si il n'y a pas d'url pour la recherhe, pas d'initialisation.
		$(getId('divNeSearch')).hide();
		if ( url_search != '' )
			initSearch(url_search);
		// Chargement du contenu.
		loadTabContent(url_get);
	}
	else
	{
		if( $(_idCurrentTab).visible() )
		{
			// On cache le Div.
			$(_idCurrentTab).hide();
                        // 16/01/2013 BBX
                        // DE Ne Filter
                        if(!_oldVersion) $(_idCurrentTab+'_checkall').hide();
			// On cache le moteur de recherche.
			$(getId('divNeSearch')).hide();
		}
		// Sinon on remontre le div.
		else
		{
			// On montre le Div.
			$(_idCurrentTab).show();
                        // 16/01/2013 BBX
                        // DE Ne Filter
                        if(!_oldVersion) $(_idCurrentTab+'_checkall').show();
			// On montre le moteur de recherche.
			if ( url_search != '' )
				initSearch(url_search);
			
		}
	}
	// On cache le div affichant la liste des éléments sélectionnés
	$(getId('msgNeSelection')).hide();
	
	// On met-à-jour la taille de la pseudo-fenêtre.
        // 17/01/2013 BBX
        // DE Filter NE : permet de corriger le bug de hauteur
        _winNaSelection.setSize('410', '100', false); 
        _winNaSelection.updateHeight();
}

/**
* displayCurrentSelection : permet d'afficher le div préfixe+msgNeSelection avec le message msg si msg est vide le div est caché.
* @param string msg éléments à afficher
*/
function displayCurrentSelection(msg)
{
	if ( msg.length == 0 )
	{
		$(getId('msgNeSelection')).hide();
	}
	else
	{
		$(getId('msgNeSelectionContent')).innerHTML = msg;
		$(getId('msgNeSelection')).show();
	}
	// On met-à-jour la taille de la pseudo-fenêtre.
	_winNaSelection.updateHeight();
}

/**
* displayCurrentSelection : permet d'afficher le contenu de la sélection courrante. Si l'url est vide on liste simplement les éléments contenu dans le champ de sauvegarde
* sinon on affiche le contenu retourné par le fichier url via une requête ajax.
*
* @param string url url complète vers le fichier
*/
function loadCurrentSelection(url)
{
	// Si l'utilisateur clique 2 fois sur le bouton, on cache la liste.
	if ( $(getId('msgNeSelection')).visible() )
	{
		$(getId('msgNeSelection')).hide();
		// On met-à-jour la taille de la pseudo-fenêtre.
		_winNaSelection.updateHeight();
	}
	else
	{
		// On cache le Div.
		if ( $(_idCurrentTab) )
		{
			$(_idCurrentTab).innerHTML = '';
			$(_idCurrentTab).hide();
			_idCurrentTab = '';
			// On cache le moteur de recherche.
			$(getId('divNeSearch')).hide();
		}
			
		if ( _buttonMode != 'radio' )
		{
			var saveField = getId('saveFieldId');
			var saveFieldSeparator = getId('saveFieldId_separator');
			var saveFieldId = $(saveField).value;
			if ( _listOfSelectedElements.length == 0)
			{
				displayCurrentSelection('No element selected');
			}
			else
			{
				if ( url.length == 0 )
				{
					var html = '';
					var tabTemp = _listOfSelectedElements.split($(saveFieldSeparator).value);
					tabTemp.each(function(item) {
						html += '<li id="li_'+item+'" style="cursor:pointer;">'
							html += item;
							html += '<input type="button" class="boutonNeSelectionDeleteElement" title="Delete from current selection" ';
							html += 'onclick="saveInNeSelection(\''+item+'\'); $(\'li_'+item+'\').remove();" />';
						html += '</li>';
					});
					displayCurrentSelection(html);
				}
				else
				{
					new Ajax.Request(url,
					{
						// 11:43 26/08/2009 GHX
						// BZ 11230
						method:'post',
						parameters: {
								current_selection: _listOfSelectedElements, 
								separator: $(saveFieldSeparator).value
							},
						onSuccess: function(transport){
							var response = transport.responseText || "no response text";
							displayCurrentSelection(response);
					    },
						onFailure: function(){ alert('Application can\'t access to \n'+url); }
					});
				
				}
			}
		}
		// Gestion du cas avec boutons radios.
		else
		{
			if ( url.length == 0 )
			{
				var elemFind = false;
				var html = '';
				_listOfSelectedElements.each(function(item) {
					if (item.value != '')
					{
						elemFind = true;
						var elemToDisplay = item.value;
						/*
							Modfi CCT1 04/09/08 : quand on est en mode bouton radio, on affiche le label de l'onglet dans lequel l'élément
							a été sauvegardé.
							Pour cela, quand on parcourt la liste des éléments sélectionnés, on remplace une partie de la chaine du nom de l'identifiant
							des champs cachés qui stockent les vlaeurs sélectionnées : remplacement de '_accordion_save' par '_title'.
							Cela nous permet de récupérer via un inner HTML le label de l'onglet correspondant.
						*/
						idTemp = item.id.replace(/_accordion_save/,'_title');
						if ( $(idTemp) )
							elemToDisplay = $(idTemp).innerHTML+' : '+item.value;
						html += '<li id="li_'+item.value+'">'
							html += elemToDisplay;
						html += '</li>';
						
					}
				});
				
				
				if ( !elemFind )
					displayCurrentSelection('No element selected');
				else
					displayCurrentSelection(html);
			}
			else
			{
				var saveField = getId('saveFieldId');
				var saveFieldSeparator = getId('saveFieldId_separator');
				var elementListParameters = new Array();
				_listOfSelectedElements.each(function(item) {
					if (item.value != '')
					{
						elementListParameters.push(item.id+'='+item.value);
					}
				});
				
				/*
					Modfi CCT1 04/09/08 : si on est en mode bouton radio et que il y a un appel ajax pour afficher la liste des éléments sélectionnés, on ajoute
					à l'url les valeurs de chaque onglet par onglet. L'url est de la forme :
					url.php?nom_champ_cache_onglet_1=valeur....
				*/
				if ( elementListParameters.length > 0 )
					url = url+'?'+elementListParameters.join('&');

				new Ajax.Request(url,
				{
					method:'get',
					onSuccess: function(transport){
						var response = transport.responseText || "no response text";
						displayCurrentSelection(response);
					},
					onFailure: function(){ alert('Application can\'t access to \n'+url); }
				});

			}
		}

		
	}
}

/**
* saveCurrentSelection : permet de copier la sélection courante sauvegardée dans la variable globale _listOfSelectedElements
* dans le champ caché de sauvegarde..
* @return void
*/
function saveCurrentSelection() {
	if ( _buttonMode != 'radio')
	{
		var saveField = getId('saveFieldId');		
		var saveFieldId = $(saveField).value;
		
		$(saveFieldId).value = _listOfSelectedElements;
	}
	else
	{
		// On met la valeur de chaque onglet dans le bon champ.
		_listOfSelectedElements.each(function(item) {
			if ( $(item['id']) ) 
				$(item['id']).value = item['value'];
		});
	}
	
	// MaJ SLC -- 23/09/2008
	// hook: si la fonction networkElementSelectionSaveHook() est définie, on l'execute
	// ce qui permet a la page qui héberge le module de lancer une fonction quand on
	// sauvegarde une selection
	
	/* 27/05/2009 SPS */

	//met a jour le tooltip du bouton qui lance le selecteur avec les elements selectionnes
	if (typeof(networkElementSelectionSaveHook) == 'function') networkElementSelectionSaveHook(_htmlIdPrefix);

}

/**************************************************************************************************************
* Gestion de l'auto-complétion
* Maj 08/10/2008 BBX : utilisation de la méthode fournie par script aculous
/*************************************************************************************************************/

/**
* On ajoute un effet de scroll en étendant prototype.
*/
Effect.Scroll = Class.create();
Object.extend(Object.extend(Effect.Scroll.prototype, Effect.Base.prototype), {
  initialize: function(element) {
    this.element = $(element);
    var options = Object.extend({
      x:    0,
      y:    0,
      mode: 'absolute'
    } , arguments[1] || {}  );
    this.start(options);
  },
  setup: function() {
    if (this.options.continuous && !this.element._ext ) {
      this.element.cleanWhitespace();
      this.element._ext=true;
      this.element.appendChild(this.element.firstChild);
    }
   
    this.originalLeft=this.element.scrollLeft;
    this.originalTop=this.element.scrollTop;
   
    if(this.options.mode == 'absolute') {
      this.options.x -= this.originalLeft;
      this.options.y -= this.originalTop;
    } else {
   
    }
  },
  update: function(position) {   
    this.element.scrollLeft = this.options.x * position + this.originalLeft;
    this.element.scrollTop  = this.options.y * position + this.originalTop;
  }
});

/**
* moveTo : permet de déplacer la position courante d'un div overflow hidden au niveau de l'id passé en paramètre
* note : cette fonction utilise le framework scriptaculous
* @param container id du div overflow hidden
* @param element id de l'élément sur lequel on doit se positionner
*/
function moveTo(container, element)
{
  Position.prepare();
  container_y = Position.cumulativeOffset($(container))[1];
  element_y = 	Position.cumulativeOffset($(element))[1];
  new Effect.Scroll(container, {x:0, y:(element_y-container_y)});
  return false;
}

// maj 19/11/2008 - MPR : On modifie la valeur de id_to_check
// 				     Cette valeur doit correspondre à n'importe quelle valeur que reçoit chaque chekbox
/**
* scrollToItem : Quand l'utilisateur a trouvé l'élément réseau recherché, on le sélectionne.
* @param text résultat text ajax
* @param li : élément li sélectionné
*
* 01/09/2010 OJT : Correction bz17385 pour DE Firefox
*/
function scrollToItem(text,li)
{
    // On récupère l'id de l'élément sélectionné par l'utilisateur.
    if( li.hasAttribute( 'id_to_check' ) )
    {
        id_to_check = li.getAttribute('id_to_check' );
        // Si id_to_check est défini

        // maj 25/11/2008 BBX : si l'élément n'existe pas, on le créé.
        // ce cas arrive lorsque la liste est tronquée à cause d'un nombre d'éléments trop important
        if (!$(id_to_check) ){
            // On regarde s'il faut cocher notre élément
            var separator = $(getId('saveFieldId_separator')).value;
			//var savedValues = $('nel_selecteur').value;
			var savedValues = $(getId('selecteur')).value;
            var isChecked = false;
            if(savedValues != '') {
                var tabIdElements = savedValues.split(separator);
                for(var i = 0; i < tabIdElements.length; i++) {
                    if(id_to_check == tabIdElements[i])
                        isChecked = true;
                }
            }
            // Création de l'élément
            var newElement = "<input type='checkbox' id='"+id_to_check+"' value='"+$(getId('neSearchInput')).value+"' onclick=\"saveInNeSelection('"+id_to_check+"');\" /><label for='"+id_to_check+"'>"+$(getId('neSearchInput')).value+"</label><br />";
			$(_idCurrentTab).insert(newElement, { position: 'bottom' });
            // Gestion du coché / décoché
            $(id_to_check).checked = isChecked;
        }

		// On colore la checkbox sélectionnée.
		$(id_to_check).style.background = '#f6a058';
		// On déplace la position du Div ouvert au niveau de l'id.
		moveTo(_idCurrentTab,id_to_check);
    }
}

/**
 * 16/01/2013 BBX
 * CB 5.3.1 : DE Ne Filter
 * Checks all elements of the current tab
 */
function neselCheckall()
{
    isChecked = $(_idCurrentTab+'_checkall').checked;
    acc = $(_idCurrentTab).childElements();
    acc.each(function(elem) {
        if(elem.id) 
        {
            if(elem.checked != isChecked) {
                saveInNeSelection(elem.id);
            }
            elem.checked = isChecked;
        }
    });
}

/**
 * 16/01/2013 BBX
 * CB 5.3.1 : DE Ne Filter
 * Updates the "checkall" checkbox status function of the elements checked
 */
function neselUpdateCheckall()
{
    allchecked = true;
    acc = $(_idCurrentTab).childElements();
    acc.each(function(elem) {
        if(elem.id) {
            if(!elem.checked) {
                allchecked = false;
                throw $break;
            }
        }
    }); 
    $(_idCurrentTab+'_checkall').checked = allchecked;
}

/**
 * 16/01/2013 BBX
 * CB 5.3.1 : DE Ne Filter
 * Applies or removes a filter
 */
function neselFilter(na, ne, url, span)
{
    // Remove the filter
    if(span.style.fontWeight == 'bold') 
    {
        span.style.fontWeight = 'normal';
        new Ajax.Request(url+'?action=19',{
            method:'post',
            parameters:'na='+na+'&ne='+ne,
            onSuccess:function(res) {
                children = res.responseText.split('|');
                // Méthode crade pour IE
                if(Prototype.Browser.IE)
                {
                    acc = document.getElementsByClassName('accordion_title');    
                    acc.each(function(elem) {
                        tagLabel = elem.innerHTML;
                        tagLabel = tagLabel.replace(_filterLabelIE, '');
                        for(var i in children) {
                            if( _htmlIdPrefix+children[i]+'_title' == elem.id ) {
                                tagLabel += _filterLabel;
                            }
                        }                        
                        elem.update(tagLabel);
                    });
                }
                // Méthode propre : vrais navigateurs
                else
                {
                    $$('div.accordion_title').each(function(elem) {
                        tagLabel = $(elem.id).innerHTML;
                        tagLabel = tagLabel.replace(_filterLabel, '');
                        for(var i in children) {
                            if( _htmlIdPrefix+children[i]+'_title' == elem.id ) {
                                tagLabel += _filterLabel;
                            }
                        }
                        $(elem.id).update(tagLabel);
                    });
                }
            }
        });
    }
    // Apply the filter
    else 
    {
        span.style.fontWeight = 'bold';
        new Ajax.Request(url+'?action=18',{
            method:'post',
            parameters:'na='+na+'&ne='+ne,
            onSuccess:function(res) {
                if(res.responseText != '') {
                    children = res.responseText.split('|');
                    for(var i in children) {
                        // 03/06/2013 NSE bz 34130 : si le niveau sélectionné a des enfants dans un niveau d'une autre famille que celle affichée dans la fenêtre
                        if($(_htmlIdPrefix+children[i]+'_title')){                        tagLabel = $(_htmlIdPrefix+children[i]+'_title').innerHTML;
                            tagLabel = tagLabel.replace(_filterLabel, '');
                            if(Prototype.Browser.IE) {
                                tagLabel = tagLabel.replace(_filterLabelIE, '');
                            }
                            tagLabel += _filterLabel
                            $(_htmlIdPrefix+children[i]+'_title').update(tagLabel);
                        }
                    }
                }
            }
        });
        // Sets a reset on filter for Reset button
        $$('input.buttonNaSelection').each(function(elem) {
            if(elem.value == 'Reset') {
                elem.onmouseup = function() {
                    resetFilter(url);
                }
            }
        });
    }
}

/**
 * 16/01/2013 BBX
 * CB 5.3.1 : DE Ne Filter
 * Resets the filter
 */
function resetFilter(url)
{
    new Ajax.Request(url+'?action=20',{method:'post'});
   
    // Méthode crade pour IE
    if(Prototype.Browser.IE)
    {
        acc = document.getElementsByClassName('accordion_title');        
        acc.each(function(elem) {
            tagLabel = elem.innerHTML;
            tagLabel = tagLabel.replace(_filterLabelIE, '');
            elem.update(tagLabel);
        });
    }
    // Méthode propre : vrais navigateurs
    else
    {
        $$('div.accordion_title').each(function(elem) {
            tagLabel = $(elem.id).innerHTML;
            tagLabel = tagLabel.replace(_filterLabel, '');
            $(elem.id).update(tagLabel);
        });
    }
}
