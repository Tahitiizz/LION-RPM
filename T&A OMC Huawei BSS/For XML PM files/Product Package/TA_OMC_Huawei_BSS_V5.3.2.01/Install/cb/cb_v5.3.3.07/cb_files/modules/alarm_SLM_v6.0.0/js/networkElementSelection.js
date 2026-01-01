/**
*
* Permet d'afficher une IHM listant des éléments réseaux (Partie Javascript)
* Les fonctions sont utillsées par le module networkElementSelection
*
* @package networkElementSelectionJS
* @author christophe chaput c.chaput@astellia.com
* @version 1.0.0
* @copyright ©2008 Astellia 
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

/**
* initNaSelection : permet d'initialiser l'ensemble des variables globales
*/
function initNaSelection()
{
	// On récupère les valeurs stockées dans les champs cachés.
	_htmlIdPrefix= $('networkElementSelection_htmlIdPrefix').value;
	_openButton = $(_htmlIdPrefix+'openButtonId').value;
	
	// On initialise la fonction onclick du bouton qui permet d'afficher l'interface.
	if ( $(_openButton) )
		$(_openButton).onclick = openNaSelection;
	else
		alert('Open button id doesn\'t exist.');
}

/**
* getId : retourne l'identifiant passé en paramètre avec le préfixe _htmlIdPrefix
*
* @param string id identifiant de l'élément
* @return string
*/
function getId(id)
{
	return _htmlIdPrefix+id;
}

/**
* openNaSelection : Permet d'ouvrir la fenêtre de sélection des éléments réseaux (utilise la class js prototype window)
*/
function openNaSelection()
{
	if ( !_winNaSelection_create )
	{
		var titre = $(getId('windowTitle')).value;
		
		_winNaSelection = new Window({ 
			className:"alphacube",
			title: titre,
			width:410, height:200,
			minWidth:410, minHeight:100,
			resizable:false,
			minimizable:false,
			recenterAuto:false,
			maximizable:false
			}); 
		_winNaSelection.setZIndex(2000);
		_winNaSelection.setContent(getId('window_select_na'));
		_winNaSelection.showCenter(false,135);
		_winNaSelection.updateHeight();
		
		_winNaSelection_create = true;
		
	}
	else
	{
		// Si la fenêtre a déjà été ouverte, il faut fermer l'onglet ouvert si il y en a un.
		if ( _idCurrentTab != '' )
			$(_idCurrentTab).hide();
		// Idem pour le div contenant la recherche.
		$(getId('divNeSearch')).hide();
			
		_winNaSelection.showCenter(false,135);
		_winNaSelection.updateHeight();
		displayCurrentSelection('');
	}
	
	// A chaque ouverture de la fenêtre ou initialise la liste des éléments sélectionné.
	var saveField = getId('saveFieldId');
	var saveFieldId = $(saveField).value;
	_listOfSelectedElements = $(saveFieldId).value;
}

/**
*	Initialise la recherche sur une NA précise.
*	cf selection_des_na_recherche.js
*
* @param string url_search url complète vers le script de recherche.
* @return void
*/
function initSearch(url_search)
{
	// Affichage du moteur de recherche.
	$(getId('divNeSearch')).show();
	
	// On met-à-jour le message qui se trouve au-dessus du champ input de recherche en copiant le titre de l'onglet qui est sélectionné ('Search on Titre onglet').
	$(getId('divNeSearch_label')).innerHTML = $(_idCurrentTab+'_title').innerHTML;
	
	// On vide le buffer de recherche afin que l'utilisateur ne se retrouve pas avec des résultats déjà affichés.
	_resultCache = 		new Object();
	_resultCacheNa = 	new Object();
	
	// On vide le champ de saisie.
	if ( $(getId('neSearchInput')) )
		$(getId('neSearchInput')).value = '';
	
	// Initialisation du moteur de recherche (cf fichier selection_des_na_recherche.;js).
	initAutoComplete($(getId('neSearchInput')),
		$(getId('neSearchSubmit')),
		url_search);
	//*/
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
		_idCurrentTab = ''; // permet de forcer le rechargement du contenu.
	}
	// On ferme la fenêtre mais on ne la détruit pas.
	_winNaSelection.close();
	// On change le style du bouton de sélection.
	var saveFieldId = $(getId('saveFieldId')).value;
	if ($(saveFieldId).value == '')
		$(_openButton).className = $(_htmlIdPrefix+'openButtonId').css_class_off;
	else
		$(_openButton).className = $(_htmlIdPrefix+'openButtonId').css_class_on;
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
	return str;
}


/**
* saveInNeSelection : permet de stocker/supprimer la valeur val dasn la liste des éléments sélectionnés (_listOfSelectedElements)
* Cette liste n'est copiée dans le champ caché que si lu'ilisateur clique sur le bouton save.
*
*  @param string val valeur à sauvegarder
*/
function saveInNeSelection(val)
{
	val = deleteQuote(val);
	if ( val.length > 0 )
	{
		var saveField = getId('saveFieldId');
		var saveFieldSeparator = $(saveField).value+'_separator';
		
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
	//alert(val);
}



/**
* resetNeSelection : vide la sélection courante (champ input et décoche les checkbox)
*/
function resetNeSelection(){
	// On mets le champ de stockage à vide.
	_listOfSelectedElements = '';
	var saveFieldId = $(getId('saveFieldId')).value;
	$(saveFieldId).clear();
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
	var saveFieldSeparator = $(saveField).value+'_separator';
	var saveFieldId = $(saveField).value;
	
	// Requête Ajax.
	new Ajax.Request(url,
	{
		method:'get',
		parameters: {current_selection: _listOfSelectedElements, separator: $(saveFieldSeparator).value},
		onSuccess: function(transport){
			var response = transport.responseText || "no response text";
			// Mise-à-jour du contenu du tab.
			$(_idCurrentTab).innerHTML = response;
			// On met-à-jour la hauteur de la fenêtre
			_winNaSelection.updateHeight();
			
			// On coche les checkbox (si besoin)
			var saveField = getId('saveFieldId');
			var saveFieldSeparator = $(saveField).value+'_separator';
			var saveFieldId = $(saveField).value;
			
			var tabTemp = _listOfSelectedElements.split($(saveFieldSeparator).value);
			tabTemp.each(function(item) {
				if( $(item) )
					$(item).checked = true;
			});
	    },
		onFailure: function(xhr){ alert(xhr.status + ' : ' + xhr.statusText + '\nApplication can\'t access to \n'+url); }
	});
	//*/
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
	// Au cas où l'utilisateur cliquerais plusiseurs fois à la suite le même élément.
	if ( _idCurrentTab != getId(id) )
	{
		// Fermeture du Div précédent si il existe.
		if ( $(_idCurrentTab) )
		{
			// On vide le contenu. (permet d'alléger la page si les éléments chargés sont volumineux).
			$(_idCurrentTab).innerHTML = '';
			$(_idCurrentTab).hide();
		}
	
		_idCurrentTab = getId(id);
		// On affiche l'icône de chargement.
		$(_idCurrentTab).innerHTML = $(getId('selection_na_loading')).innerHTML;
		// On affiche le Div.
		$(_idCurrentTab).show();
		// On met-à-jour la taille de la pseudo-fenêtre.
		_winNaSelection.updateHeight();
		// Initialisation de la zone de recherche.
		// Si il n'y a pas d'url pour la recherhe, pas d'initialisatio.
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
			// On cache le moteur de recherche.
			$(getId('divNeSearch')).hide();
		}
		// Sinon on remontre le div.
		else
		{
			// On cache le Div.
			$(_idCurrentTab).show();
			// On cache le moteur de recherche.
			$(getId('divNeSearch')).show();
			// On coche les checkbox (si besoin)
			var saveField = getId('saveFieldId');
			var saveFieldSeparator = $(saveField).value+'_separator';
			var saveFieldId = $(saveField).value;
			
			var tabTemp = _listOfSelectedElements.split($(saveFieldSeparator).value);
			tabTemp.each(function(item) {
				if( $(item) )
					$(item).checked = true;
			});
		}
	}
	// On cache le div affichant la liste des éléments sélectionnés
	$(getId('msgNeSelection')).hide();
	
	// On met-à-jour la taille de la pseudo-fenêtre.
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
			
		var saveField = getId('saveFieldId');
		var saveFieldSeparator = $(saveField).value+'_separator';
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
					method:'get',
					parameters: {current_selection: _listOfSelectedElements, separator: $(saveFieldSeparator).value},
					onSuccess: function(transport){
						var response = transport.responseText || "no response text";
						displayCurrentSelection(response);
				    },
					onFailure: function(){ alert('TOTO Application can\'t access to \n'+url); }
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
function saveCurrentSelection()
{
	var saveField = getId('saveFieldId');
	var saveFieldId = $(saveField).value;
	$(saveFieldId).value = _listOfSelectedElements;
}

/**************************************************************************************************************/
/**************************************************************************************************************/
/**************************************************************************************************************/
/**************************************************************************************************************/

/**
*
* Permet de gérer une recherche avec autocomplétion.
*
*/

/**
* Script réalisé à partir de l'article de Denis Cabasson
* http://dcabasson.developpez.com/articles/javascript/ajax/ajax-autocompletion-pas-a-pas/
* "Ajax - une autocomplétion pas à pas"
*/
var _inputField = 	null; // le champ texte lui-même
var _submitButton = null; // le bouton submit de notre formulaire


/**
* Initialise le moteur de recherche (initialise les variables globales)
* @param string field : champ input dans lequel l'utilisateur tape la recherche.
* @param string submit : bouton submit.
* @param string url_search url complète vers le script de recherche.
* @return void
*/
function initAutoComplete(field,submit,url_search)
{
	_inputField = 		field;
	_submitButton = 	submit;
	_inputField.autocomplete = "off";
	creeAutocompletionDiv();
	_currentInputFieldValue = 	_inputField.value;
	_oldInputFieldValue = 		_currentInputFieldValue;
	cacheResults("",new Array());
	document.onkeydown =  onKeyDownHandler;
	_inputField.onkeyup = 	onKeyUpHandler;
	_inputField.onblur =  	onBlurHandler;
	_adresseRecherche = 	url_search;
	window.onresize = 		onResizeHandler;

	// Premier déclenchement de la fonction dans 200 millisecondes
	setTimeout("mainLoop()",200);
}

var _oldInputFieldValue=""; // valeur précédente du champ texte
var _currentInputFieldValue=""; // valeur actuelle du champ texte
var _resultCache=new Object(); // mécanisme de cache des requetes

/**
* mainLoop : tourne en permanence pour suggerer suite à un changement du champ texte
*/
function mainLoop()
{
	if(_oldInputFieldValue!=_currentInputFieldValue)
	{
		var valeur=escapeURI(_currentInputFieldValue);
		var suggestions=_resultCache[_currentInputFieldValue];
		if(suggestions){ // la réponse était encore dans le cache
			metsEnPlace(valeur,suggestions)
		}else{
			callSuggestions(valeur) // appel distant
		}
		_inputField.focus()
	}
	_oldInputFieldValue=_currentInputFieldValue;
	setTimeout("mainLoop()",200); // la fonction se redéclenchera dans 200 ms
	return true
}


/**
* escapeURI : echappe les caractère spéciaux
*/
function escapeURI(La){
  if(encodeURIComponent) {
    return encodeURIComponent(La);
  }
  if(escape) {
    return escape(La)
  }
}

var _xmlHttp = null; //l'objet xmlHttpRequest utilisé pour contacter le serveur

// Stocke les valeurs liées au label des éléments réseaux.
var _resultCacheNa = new Object();

function callSuggestions(valeur)
{
	new Ajax.Request(_adresseRecherche,
	{
		method:'get',
		parameters: {debut: valeur },
		onSuccess: function(transport){
			var response = transport.responseXML || "no response XML";
			var liste = traiteXmlSuggestions(response);
			cacheResults(valeur,liste);
			metsEnPlace(valeur,liste);
			// On affiche un message dans le statut.
			if (liste.length == 0) 
				updateNeSelectionWindowStatus('No result');
			else 
				updateNeSelectionWindowStatus('');	
			//*/
	    },
		onFailure: function(){ alert('Application can\'t access to \n'+url); }
	});
}

/**
* cacheResults : Mecanisme de caching des réponses
*/
function cacheResults(debut,suggestions){
  _resultCache[debut]=suggestions
}

/**
* traiteXmlSuggestions : Transformation XML en tableau
* @param xml xmlDoc document xml
*/
function traiteXmlSuggestions(xmlDoc) {
	var options = xmlDoc.getElementsByTagName('option');
	var optionsListe = new Array();
	for (var i=0; i < options.length; ++i) {
		str = options[i].firstChild.data;
		// Le format de la chaine et label |ss| valeur, on stocke la relation entre la valuer et le label dans un tableau
		tab_str = str.split('|ss|');
		_resultCacheNa[tab_str[0]] = tab_str[1];
		optionsListe.push(tab_str[0]);
	}
	return optionsListe;
}

/**
* insereCSS : insère une feuille de style avec son nom
*/
function insereCSS(nom,regle)
{
  if (document.styleSheets) {
    var I=document.styleSheets[0];
    if(I.addRule){ // méthode IE
      I.addRule(nom,regle)
    }else if(I.insertRule){ // méthode DOM
      I.insertRule(nom+" { "+regle+" }",I.cssRules.length)
    }
  }
}

/**
* initStyle : initialise les tyles utilisés par callsuggestion
*/
function initStyle(){
  var AutoCompleteDivListeStyle="font-size: 13px; font-family: arial,sans-serif; word-wrap:break-word; ";
  var AutoCompleteDivStyle="display: block; padding-left: 3; padding-right: 3; height: 16px; overflow: hidden; background-color: white;";
  var AutoCompleteDivActStyle="background-color: #3366cc; color: white ! important; ";
  insereCSS(".AutoCompleteDivListeStyle",AutoCompleteDivListeStyle);
  insereCSS(".AutoCompleteDiv",AutoCompleteDivStyle);
  insereCSS(".AutoCompleteDivAct",AutoCompleteDivActStyle);
}

/**
* setStylePourElement : change la classe CSS d'un lément passé en paramètre
*/
function setStylePourElement(c,name){
  c.className=name;
}

// calcule le décalage à gauche
function calculateOffsetLeft(r){
  return calculateOffset(r,"offsetLeft")
}

// calcule le décalage vertical
function calculateOffsetTop(r){
  return calculateOffset(r,"offsetTop")
}

function calculateOffset(r,attr){
  var kb=0;
  while(r){
    kb+=r[attr];
    r=r.offsetParent
  }
  return kb
}

// calcule la largeur du champ
function calculateWidth(){
  return _inputField.offsetWidth-2*1
}

function setCompleteDivSize(){
  if(_completeDiv){
    _completeDiv.style.left=calculateOffsetLeft(_inputField)+"px";
    _completeDiv.style.top=calculateOffsetTop(_inputField)+_inputField.offsetHeight-1+"px";
    _completeDiv.style.width=calculateWidth()+"px"
  }
}

function creeAutocompletionDiv() {
  initStyle();
  _completeDiv=document.createElement("DIV");
  _completeDiv.id="completeDiv";
  var borderLeftRight=1;
  var borderTopBottom=1;
  _completeDiv.style.borderRight="black "+borderLeftRight+"px solid";
  _completeDiv.style.borderLeft="black "+borderLeftRight+"px solid";
  _completeDiv.style.borderTop="black "+borderTopBottom+"px solid";
  _completeDiv.style.borderBottom="black "+borderTopBottom+"px solid";
  _completeDiv.style.zIndex="2100";
  _completeDiv.style.paddingRight="0";
  _completeDiv.style.paddingLeft="0";
  _completeDiv.style.paddingTop="0";
  _completeDiv.style.paddingBottom="0";
  setCompleteDivSize();
  _completeDiv.style.visibility="hidden";
  _completeDiv.style.position="absolute";
  _completeDiv.style.backgroundColor="white";
  document.body.appendChild(_completeDiv);
  setStylePourElement(_completeDiv,"AutoCompleteDivListeStyle");
}


function metsEnPlace(valeur, liste){
  while(_completeDiv.childNodes.length>0) {
    _completeDiv.removeChild(_completeDiv.childNodes[0]);
  }
  // mise en place des suggestions
  for(var f=0; f<liste.length; ++f){
    var nouveauDiv=document.createElement("DIV");
    nouveauDiv.onmousedown=divOnMouseDown;
    nouveauDiv.onmouseover=divOnMouseOver;
    nouveauDiv.onmouseout=divOnMouseOut;
    setStylePourElement(nouveauDiv,"AutoCompleteDiv");
    var nouveauSpan=document.createElement("SPAN");
    nouveauSpan.innerHTML = liste[f]; // le texte de la suggestion
    nouveauDiv.appendChild(nouveauSpan);
    _completeDiv.appendChild(nouveauDiv)
  }
  PressAction();
  if(_completeDivRows>0) {
    _completeDiv.height=16*_completeDivRows+4;
  } else {
    hideCompleteDiv();
  }

}

var _lastKeyCode=null;

// Handler pour le keydown du document
var onKeyDownHandler=function(event){
  // accès evenement compatible IE/Firefox
  if(!event&&window.event) {
    event=window.event;
  }
  // on enregistre la touche ayant déclenché l'evenement
  if(event) {
    _lastKeyCode=event.keyCode;
  }
}

var _eventKeycode = null;

// Handler pour le keyup de lu champ texte
var onKeyUpHandler=function(event){
  // accès evenement compatible IE/Firefox
  if(!event&&window.event) {
    event=window.event;
  }
  _eventKeycode=event.keyCode;
  // Dans les cas touches touche haute(38) ou touche basse (40)
  if(_eventKeycode==40||_eventKeycode==38) {
    // on autorise le blur du champ (traitement dans onblur)
    blurThenGetFocus();
  }
  // taille de la selection
  var N=rangeSize(_inputField);
  // taille du texte avant la selection (selection = suggestion d'autocomplétion)
  var v=beforeRangeSize(_inputField);
  // contenu du champ texte
  var V=_inputField.value;
  if(_eventKeycode!=0){
    if(N>0&&v!=-1) {
      // on recupere uniquement le champ texte tapé par l'utilisateur
      V=V.substring(0,v);
    }
    // 13 = touche entrée
    if(_eventKeycode==13||_eventKeycode==3){
      var d=_inputField;
      // on mets en place l'ensemble du champ texte en repoussant la selection
      if(_inputField.createTextRange){
        var t=_inputField.createTextRange();
        t.moveStart("character",_inputField.value.length);
        _inputField.select()
      } else if (d.setSelectionRange){
        _inputField.setSelectionRange(_inputField.value.length,_inputField.value.length)
      }
    } else {
      // si on a pas pu agrandir le champ non selectionné, on le mets en place violemment.
      if(_inputField.value!=V) {
        _inputField.value=V
      }
    }
  }
  // si la touche n'est ni haut, ni bas, on stocke la valeur utilisateur du champ
  if(_eventKeycode!=40&&_eventKeycode!=38) {
    // le champ courant n est pas change si key Up ou key Down
  	_currentInputFieldValue=V;
  }
  if(handleCursorUpDownEnter(_eventKeycode)&&_eventKeycode!=0) {
    // si on a préssé une touche autre que haut/bas/enter
    PressAction();
  }
}

// Change la suggestion selectionné.
// cette méthode traite les touches haut, bas et enter
function handleCursorUpDownEnter(eventCode){
  if(eventCode==40){
    highlightNewValue(_highlightedSuggestionIndex+1);
    return false
  }else if(eventCode==38){
    highlightNewValue(_highlightedSuggestionIndex-1);
    return false
  }else if(eventCode==13||eventCode==3){
    return false
  }
  return true
}

var _completeDivRows = 0;
var _completeDivDivList = null;
var _highlightedSuggestionIndex = -1;
var _highlightedSuggestionDiv = null;

// gère une touche pressée autre que haut/bas/enter
function PressAction(){
  _highlightedSuggestionIndex=-1;
  var suggestionList=_completeDiv.getElementsByTagName("div");
  var suggestionLongueur=suggestionList.length;
  // on stocke les valeurs précédentes
  // nombre de possibilités de complétion
  _completeDivRows=suggestionLongueur;
  // possiblités de complétion
  _completeDivDivList=suggestionList;
  // si le champ est vide, on cache les propositions de complétion
  if(_currentInputFieldValue==""||suggestionLongueur==0){
    hideCompleteDiv()
  }else{
    showCompleteDiv()
  }
  var trouve=false;
  // si on a du texte sur lequel travailler
  if(_currentInputFieldValue.length>0){
    var indice;
    // T vaut true si on a dans la liste de suggestions un mot commencant comme l'entrée utilisateur
    for(indice=0; indice<suggestionLongueur; indice++){
      if(getSuggestion(suggestionList.item(indice)).toUpperCase().indexOf(_currentInputFieldValue.toUpperCase())==0) {
        trouve=true;
        break
      }
    }
  }
  // on désélectionne toutes les suggestions
  for(var i=0; i<suggestionLongueur; i++) {
    setStylePourElement(suggestionList.item(i),"AutoCompleteDiv");
  }
  // si l'entrée utilisateur (n) est le début d'une suggestion (n-1) on sélectionne cette suggestion avant de continuer
  if(trouve){
    _highlightedSuggestionIndex=indice;
    _highlightedSuggestionDiv=suggestionList.item(_highlightedSuggestionIndex);
  }else{
    _highlightedSuggestionIndex=-1;
    _highlightedSuggestionDiv=null
  }
  var supprSelection=false;
  switch(_eventKeycode){
    // cursor left, cursor right, page up, page down, others??
    case 8:
    case 33:
    case 34:
    case 35:
    case 35:
    case 36:
    case 37:
    case 39:
    case 45:
    case 46:
      // on supprime la suggestion du texte utilisateur
      supprSelection=true;
      break;
    default:
      break
  }
  // si on a une suggestion (n-1) sélectionnée
  if(!supprSelection&&_highlightedSuggestionDiv){
    setStylePourElement(_highlightedSuggestionDiv,"AutoCompleteDivAct");
    var z;
    if(trouve) {
      z=getSuggestion(_highlightedSuggestionDiv).substr(0);
    } else {
      z=_currentInputFieldValue;
    }
    if(z!=_inputField.value){
      if(_inputField.value!=_currentInputFieldValue) {
        return;
      }
      // si on peut créer des range dans le document
      if(_inputField.createTextRange||_inputField.setSelectionRange) {
        _inputField.value=z;
      }
      // on sélectionne la fin de la suggestion
      if(_inputField.createTextRange){
        var t=_inputField.createTextRange();
        t.moveStart("character",_currentInputFieldValue.length);
        t.select()
      }else if(_inputField.setSelectionRange){
        _inputField.setSelectionRange(_currentInputFieldValue.length,_inputField.value.length)
      }
    }
  }else{
    // sinon, plus aucune suggestion de sélectionnée
    _highlightedSuggestionIndex=-1;
  }
}

var _cursorUpDownPressed = null;

// permet le blur du champ texte après que la touche haut/bas ai été pressé.
// le focus est récupéré après traitement (via le timeout).
function blurThenGetFocus(){
  _cursorUpDownPressed=true;
  _inputField.blur();
  setTimeout("_inputField.focus();",10);
  return
}

// taille de la selection dans le champ input
function rangeSize(n){
  var N=-1;
  if(n.createTextRange){
    var fa=document.selection.createRange().duplicate();
    N=fa.text.length
  }else if(n.setSelectionRange){
    N=n.selectionEnd-n.selectionStart
  }
  return N
}

// taille du champ input non selectionne
function beforeRangeSize(n){
  var v=0;
  if(n.createTextRange){
    var fa=document.selection.createRange().duplicate();
    fa.moveEnd("textedit",1);
    v=n.value.length-fa.text.length
  }else if(n.setSelectionRange){
    v=n.selectionStart
  }else{
    v=-1
  }
  return v
}

// Place le curseur à la fin du champ
function cursorAfterValue(n){
  if(n.createTextRange){
    var t=n.createTextRange();
    t.moveStart("character",n.value.length);
    t.select()
  } else if(n.setSelectionRange) {
    n.setSelectionRange(n.value.length,n.value.length)
  }
}


// Retourne la valeur de la possibilite (texte) contenu dans une div de possibilite
function getSuggestion(uneDiv){
  if(!uneDiv) {
    return null;
  }
  return trimCR(uneDiv.getElementsByTagName('span')[0].firstChild.data)
}

// supprime les caractères retour chariot et line feed d'une chaine de caractères
function trimCR(chaine){
  for(var f=0,nChaine="",zb="\n\r"; f<chaine.length; f++) {
    if (zb.indexOf(chaine.charAt(f))==-1) {
      nChaine+=chaine.charAt(f);
    }
  }
  return nChaine
}

// Cache completement les choix de completion
function hideCompleteDiv(){
  _completeDiv.style.visibility="hidden"
}

// Rends les choix de completion visibles
function showCompleteDiv(){
  _completeDiv.style.visibility="visible";
  setCompleteDivSize()
}

// Change la suggestion en surbrillance
function highlightNewValue(C){
  if(!_completeDivDivList||_completeDivRows<=0) {
    return;
  }
  showCompleteDiv();
  if(C>=_completeDivRows){
    C=_completeDivRows-1
  }
  if(_highlightedSuggestionIndex!=-1&&C!=_highlightedSuggestionIndex){
    setStylePourElement(_highlightedSuggestionDiv,"AutoCompleteDiv");
    _highlightedSuggestionIndex=-1
  }
  if(C<0){
    _highlightedSuggestionIndex=-1;
    _inputField.focus();
    return
  }
  _highlightedSuggestionIndex=C;
  _highlightedSuggestionDiv=_completeDivDivList.item(C);
  setStylePourElement(_highlightedSuggestionDiv,"AutoCompleteDivAct");
  _inputField.value=getSuggestion(_highlightedSuggestionDiv);
}

// Handler de resize de la fenetre
var onResizeHandler=function(event){
  // recalcule la taille des suggestions
  setCompleteDivSize();
}

// Handler de blur sur le champ texte
var onBlurHandler=function(event){
  if(!_cursorUpDownPressed){
    // si le blur n'est pas causé par la touche haut/bas
    hideCompleteDiv();
    // Si la dernière touche préssé est tab, on passe au bouton de validation
    if(_lastKeyCode==9){
      _submitButton.focus();
      _lastKeyCode=-1
    }
  }
  _cursorUpDownPressed=false
};

// declenchee quand on clique sur une div contenant une possibilite
var divOnMouseDown=function(){
  _inputField.value=getSuggestion(this);
  findElementInList();
  // 03/07/2007 christophe : quand on clique sur un élément, on cache le div de suggestions.
  hideCompleteDiv();
};

// declenchee quand on passe sur une div de possibilite. La div précédente est passee en style normal
var divOnMouseOver=function(){
  if(_highlightedSuggestionDiv) {
    setStylePourElement(_highlightedSuggestionDiv,"AutoCompleteDiv");
  }
  setStylePourElement(this,"AutoCompleteDivAct")
};

// declenchee quand la sourie quitte une div de possiblite. La div repasse a l'etat normal
var divOnMouseOut = function(){
  setStylePourElement(this,"AutoCompleteDiv");
};

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

/**
* findElementInList : Quand l'utilisateur a trouvé l'élément réseau recherché, on le sélectionne.
*/
function findElementInList()
{
	// On récupère l'id de l'élément sélectionné par l'utilisateur.
	id_to_check = _resultCacheNa[_inputField.value];
	// Si l'id existe dans l'onglet actuellement ouvert, on déplace la positon courante du div à son niveau.
	if ( $(id_to_check) ){
		// On colore la checkbox sélectionnée.
		$(id_to_check).style.background = '#f6a058';
		// On déplace la position du Div ouvert au niveau de l'id.
		moveTo(_idCurrentTab,id_to_check);
	}
}


















