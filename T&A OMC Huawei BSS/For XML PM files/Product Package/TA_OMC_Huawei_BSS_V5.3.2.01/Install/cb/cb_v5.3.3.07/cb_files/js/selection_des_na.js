/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*
*  - 14/09/2010 MMT bz 17890 tooltip selection NA mal dimensionné/positionné
*      utilisation de la fonction popalt deja modifié pour supporter tous navigateurs
*      suppression des fonctions popalt2 et kill2
*
*	- maj 10/03/2008, benoit : correction du bug 2834. Ajout des fonctions permettant de récupérer le nombre de valeurs de na disponible et de mettre à jour la balise Top concernée dans le selecteur
*
*	- maj 13/03/2008, benoit : ajout du parametre 'path' à la fonction 'getNumberOfNa()'
*	
*	09/04/2009 - SPS : ajout recherche de l'objet xmlhttp pour internet explorer dans la fonction getHTTPObject
* 	10/04/2009 - SPS : ajout de tests pour verifier si les elements et les variables existent 
*
*/
/*
	CB 2.2.0.14

	- 13/09/2007 christophe : dans l'interface d'édition des alarmes, même si il n'y a pas d'éléments sélectionné, on met 'all' par défaut
	- 31/08/2007 christophe : on vide la variable _naSelectedJS sinon l'utilisateur ne pourra plus ouvrir le div _idCurrentElement
	- 29/08/2007 christophe : quand on ferme la fenêtre de sélection des NA on : vide les messages affichés, on ferme le div de la NA courante sélectionnée.
	- 24/08/2007 christophe aajout du modeNaSelection à l'appel de genDashboard_get_liste_na_mere_ajax
	- 18/07/2007 christophe : 
		> ajout de la fonction selectChildren, selectChildrenAjax
		> modification de la fonction saveInSession pour prendre en compte l'icône selectChildren si elle est présente.
	- 16/07/2007 christophe : quand l'utilisateur charge une liste d'éléments réseaux, on met-à-jour la taille de la pseudo-fenêtre.
	-  11/07/2007 christophe : 
		> Si on est dans l'interface d'édition des alarmes, quand l'utilisateur ferme la fenêtre, on vérifie sa sélection.
		> dans checkNaSelect on vérifie si l'utilisateur a sélectionné au moins un élément réseau quand on est dans l'interface d'édition des alarmes.
		> ajout du param check_all  0/1 à la fonction chargerContenu
	- du 25/06/07 au 29/06/07 christophe :
		> gestion du moteur de recherche : initSearch
		> la famille n'est plus une variable globale mais un paramètre des fonctions.
		> variable globale + fonction d'initialisation du chemin vers les fichiers de traitement ajax.
	- 20/06/07 christophe : utilisation de la classe js prototype / window.js pour l'ouverture de la pseudo fenêtre.
	- 04/07/2007 christophe : on recharge le contenu du div listant les élément réseaux à chaque clic, permet de gérer les changements de sélection enfants/parents.
	-05/07/2007 Affichage du popalt quand on ne se trouve pas dans le sélecteur (iframe non présente).
*/
/*
	Patch 1 sur le cb2.0.0.40
	- maj 24 11 2006 christophe : gestion des caractères spéciaux (fonction saveInSession)
*/
/*
	- 25/06/2007 : ajout / intégration appels au moteur de recherche.
	- 28/06/2007 christophe : ajout du param famille dans chargerContenu() et openAccordion().
*/
/*
 *      - maj 20/03/20007 Gwénaël    Création d'une iframe sur la fenetre volante au moment de l'affichage du texte et suppression de l'iframe lorsque l'on masque le message
 *						Comme la requete n'est pas instantanée delay d'affichage est plus grand donc si l'utilisateur fait que passer sur le bouton, le tooltip n'est pas encore affiché qu'on le mas
 *						Donc on ajoute un paramêtre (killpopalt2) qui permet de ne pas afficher le tooltip si la souris n'est plus sur le bouton et qu'il n'est pas encore afficher 				
 */

// variable globale précisant le chemin d'accès aux fichiers Ajax
var _linkToAjax = '';
var debug = false;


// Objet XMLHttprequest.
function getHTTPObject() {
	var xmlhttp;
	
	if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
		// Firefox et autres
		try {
			xmlhttp = new XMLHttpRequest();
		} catch (e) {
			xmlhttp = false;
		}
	}/* 09/04/2009 - SPS : recherche de l'objet xmlhttp pour internet explorer */
	else if(window.ActiveXObject) { 	
		// Internet Explorer 
		try {   
			xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
		} 
		catch (e) {	
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
	}
	return xmlhttp;
}


var _httpNaSelection = getHTTPObject();

// Fenêtre de sélection des NA.
var _winNaSelection;
// Définit si l'utilisateur a déjà ouvert la fenêtre.
var _winNaSelection_create = false;

/*
	Initialise la recherche sur une NA précise.
	cf selection_des_na_recherche.js
*/
function initSearch(na,famille){
	// Affichage du moteur de recherche.
	if ( $('div_search').style.display == 'none' )
		toggle('div_search');
		
	// On met-à-jour la NA sur laquelle on fait la recherche.
	$('div_search_on_na').innerHTML = $(na+'_title').innerHTML;
	
	// On vide le buffer de recherche afin que l'utilisateur ne se retrouve pas avec des résultats déjà affichés.
	_resultCache = 		new Object();
	_resultCacheNa = 	new Object();
	
	// On vide le champ de saisie.
	if ( $('na-search-input') )
		$('na-search-input').value = '';
	
	// Initialisation du moteur de recherche.
	initAutoComplete($('form-na-search'),
		$('na-search-input'),
		$('na-search-submit'),
		na,famille);
}

// Permet d'ouvrir la fenêtre de sélection des éléments réseaux (utilise la class js prototype window)
function openNaSelection(titre)
{
	if ( !_winNaSelection_create )
	{
		// modif 09:32 28/09/2007 Gwenael : possibilité de bouger la fenetre dans l'edition des alarmes
		// 11/07/2007 christophe : Si on est dans l'interface d'édition des alarmes, quand l'utilisateur ferme la fenêtre, on vérifie sa sélection.
		if ( _modeNaSelection == 'interface_edition_alarme' )
		{
			_winNaSelection = new Window({ 
				className:"alphacube",
				title: titre,
				width:410, height:200,
				minWidth:410, minHeight:100,
				resizable:false,
				minimizable:false,
				recenterAuto: false,
				maximizable:false,
				onClose : checkNaSelect
				}); 
		}
		else
		{
			_winNaSelection = new Window({ 
				className:"alphacube",
				title: titre,
				width:410, height:200,
				minWidth:410, minHeight:100,
				resizable:false,
				minimizable:false,
				recenterAuto: false,
				maximizable:false
				}); 
		}
		_winNaSelection.setZIndex(2000);
		_winNaSelection.setContent('window_select_na');
		_winNaSelection.showCenter(false,135);
		_winNaSelection.updateHeight();
		
		_winNaSelection_create = true;
	}
	else
	{
		_winNaSelection.showCenter(false,135);
		_winNaSelection.updateHeight();
	}
}
// Permer de fermer la fenêtre NA selection
function closeNaselection()
{
	// On ferme la fenêtre mais on ne la détruit pas.
	_winNaSelection.close();
	
	// 29/08/2007 christophe : quand on ferme la fenêtre de sélection des NA on : vide les messages affichés, on ferme le div de la NA courante sélectionnée.
	// Si le DIV des messages contint des éléments html, on le vide.
	if ( $('selection_des_na_message').innerHTML != '' )
		$('selection_des_na_message').innerHTML = '';
	
	// On cache le Div courant affichant la liste des NA.
	if ( _idCurrentElement != '' )
			$(_idCurrentElement).style.display = 'none';

	// 31/08/2007 christophe : on vide la variable _naSelectedJS sinon l'utilisateur ne pourra plus ouvrir le div _idCurrentElement
	_naSelectedJS = '';
}
// Pemet de mettre-à-jour la barre de status de la fenêtre NA selection
function updateNaselectionStatus(texte)
{
	// Si on remet la barre des status à vide, on met quand même &nbsp; car sinon l'affichage de la pseudo fenêtre est moche.
	texte = (texte == '') ? '&nbsp;' : texte;
	if ( _winNaSelection_create )
		_winNaSelection.setStatusBar(texte);
}

// Vérifie si la na_value est déjà enregistrée.
function na_in(na,na_value)
{
	find = false;	// définit si on a toruvé l'élément réseau.
	tab_temp = new Array();
	for(i=0; i < _listeSelectedNa.length; i++){
		if(_listeSelectedNa[i] != na_value+'_'+na){
			tab_temp.push(_listeSelectedNa[i]);
		} else {
			find = true;
		}
	}
	if(find) _listeSelectedNa = tab_temp;
	return find;
}

// Sauvegarde l'élément réseau passée en paramètre dans le tableau de session.
function saveInSession(na,na_value,na_label){

	// Si l'icône de sélection des éléments enfants est présentes et que des enfant sont sélectionnés, il faut les désélectionner.
	id_image = na_value+'_'+na+'_imgSelectChild';
	if ( $(id_image) )
	{
		toggle(id_image);
	}
	
	// Si le nombre limite n'est pas dépassé.
	flag = na_in(na,na_value);
	if(_nbLimiteElements == _nbElements && !flag){
		// on affiche une message d'erreur
		$('selection_des_na_message').innerHTML = "<strong style='color:#ff0000'>Maximum number of network elements reached ("+_nbLimiteElements+")</strong>";
		// on décoche la checkbox.
		$(na_value+'_'+na).checked = false;
	} else {
		// On stocke la na si la na n'est pas déjà dans le tableau .
		if(!flag){
			_nbElements++;
			_listeSelectedNa.push(na_value+'_'+na);
		} else {
			_nbElements--;
		}
		na_value = encodeURIComponent(na_value);
		var str_label = new String(na_label);
		na_label = str_label.replace('+','@@@');//encodeURIComponent(na_label);
		url = _linkToAjax+'genDashboard_na_in_session_ajax.php?action=ajout';
		url += '&na='+na+'&na_value='+na_value+'&na_label='+na_label;
		
		_httpNaSelection.open('GET', url, true);
		_httpNaSelection.onreadystatechange = xmlHttpRequestDisplayReponse;	
		_httpNaSelection.send(null);
	}
}

// Affiche le contenu du tableau de session courante.
function displaySessionTab(){
	_httpNaSelection.open('GET', _linkToAjax+'genDashboard_na_in_session_ajax.php?action=display', true);
	_httpNaSelection.onreadystatechange = xmlHttpRequestDisplayReponse;	
	_httpNaSelection.send(null);
}

/*
	18/07/2007 christophe : ajout de la fonction selectChildren
	
*/
function selectChildren(na,na_value,na_label)
{
	// On change l'image et le message alt.
	id_image = na_value+'_'+na+'_imgSelectChild';
	
	/*	
		on change l'image de sélection des éléments enfants.
		si la checkbox n'est pas coché, on ajoute l'élément réseau dans la session puis on coche la checkbox.	*/
	if ( $(id_image) )
	{
		img_src = $(id_image).src;
		if( img_src.indexOf('unselect_child.png') != -1 )
		{
			img_src_final = img_src.replace(/unselect_child.png/, 'select_child.png');
			img_alt_final = _msgRollOverSelectChildren;
		}
		else
		{
			img_src_final = img_src.replace(/select_child.png/, 'unselect_child.png');
			img_alt_final = _msgRollOverUnSelectChildren;
		}
		$(id_image).src = img_src_final;
		$(id_image).alt = img_alt_final;
		// Mise à jour de la variable de session.
		selectChildrenAjax(na,na_value,na_label);
	}
}

/*
	selectChildrenAjax
		Permet d'enregistrer si on doit sélectionner les enfants d'une na_value ou non.
	na : netork agregation
	na_value : élément réseau
*/
function selectChildrenAjax(na,na_value,na_label)
{
	na_value = encodeURIComponent(na_value);
	var str_label = new String(na_label);
	na_label = str_label.replace('+','@@@');//encodeURIComponent(na_label);
	url = _linkToAjax+'genDashboard_na_in_session_ajax.php?action=select_children';
	url += '&na='+na+'&na_value='+na_value+'&na_label='+na_label;
	
	_httpNaSelection.open('GET', url, true);
	_httpNaSelection.onreadystatechange = xmlHttpRequestDisplayReponse;	
	_httpNaSelection.send(null);
}


/*
	Vide le tableau de session courant et décoche toutes les checkbox.
*/
function razSession(){
	// On récupère la liste des na sélectionnées pour pouvoir décocher les checkbox.
	for(i=0; i < _listeSelectedNa.length; i++){
		/*
			On ne décoche la checkbox que si l'élément existe car si l'utilisateur a fait un reload (après une sélection)
			de la page et qu'il n'a cliqué sur aucune na, les contenus ne sont pas encore chargé.
		*/
		if($(_listeSelectedNa[i])){
			$(_listeSelectedNa[i]).checked = false;	
		}
	}
	_listeSelectedNa = new Array(); // on efface le tableau.
	_nbElements = 0;
	
	_httpNaSelection.open('GET', _linkToAjax+'genDashboard_na_in_session_ajax.php?action=reset', true);
	_httpNaSelection.onreadystatechange = xmlHttpRequestDisplayReponse;	
	_httpNaSelection.send(null);
}

// Affiche la réponse renvoyée l'objet http courant.
function xmlHttpRequestDisplayReponse(){
	if (_httpNaSelection.readyState == 4){
		$('selection_des_na_message').innerHTML = _httpNaSelection.responseText;
		_winNaSelection.updateHeight(); // On met-à-jour la hauteur de la fenêtre
	}
}

// Change l'icône de sélection des NA : si on l'icône est verte sinon elle est grise.
function changeNaSelectionIcon(on_off)
{
	if ($('img_select_na'))
	{
		image_select_na = $('img_select_na');
		src = image_select_na.src;
		if ( on_off == 'on' )
			src_final = src.replace(/select_na_on.png/, 'select_na_on_ok.png');
		else
			src_final = src.replace(/select_na_on_ok.png/, 'select_na_on.png');
		
		image_select_na.src = src_final;
	}
}

/**
 * Verifie si une selection NA à été effectuée
 * 2010/06/30 OJT : Modification du test suite à la DE sur les NE parents
 */
function naSelectionIsOk()
{
    if( $F('net_to_sel') != 'makeSelection' ){
        return true;
    }
    alert( _errorSelectOneNetworkElement );
    return false;
}


/*
	Permet de mettre-à-jour le contenu du div de sélection des NA.
	> affiche tous les niveau d'agrégation sélectionnable.
	na_min : NA minimale à affichée.
	Si = 'all_family', on affiche tous les niveaux d'agrégation existants.
	
	10/04/2009 - SPS : ajout de tests pour verifier si les elements et les variables existent
*/
function updateNaSelection(na_min){
	
	if ($('div_search')) { 
		// On masque le moteur de recherche..
		if ( $('div_search').style.display == 'block' )
			toggle('div_search');
	}
	if ($('selection_des_na_message')) { 
		$('selection_des_na_message').innerHTML = "";
	}
	
	//on teste si les variables existent
	if ( typeof _product != "undefined"  && typeof _family != "undefined"  && typeof _selectChild != "undefined" ) {
		url = _linkToAjax+'genDashboard_get_liste_na_mere_ajax.php';
			url += '?na='+na_min;
			url += '&product='+_product;
			url += '&family='+_family;
			url += "&selectChild="+_selectChild;
			// 24/08/2007 christophe aajout du modeNaSelection à l'appel de genDashboard_get_liste_na_mere_ajax
			url += '&modeNaSelection='+_modeNaSelection;
		
		_httpNaSelection.open('GET', url, true);
		_httpNaSelection.onreadystatechange = updateNaSelection_display;	
		_httpNaSelection.send(null);
	}
}
// Affiche la liste des na principales.
function updateNaSelection_display(){
	if (_httpNaSelection.readyState == 4){
		// On remet toutes les variable js à 0.
		//_listeSelectedNa = new Array(); 
		listeNaJS = new Array(); 
		_nbElements = 0;
		_idCurrentElement = '';	
		_naSelectedJS = '';
		$('contenu_na_selection').innerHTML = _httpNaSelection.responseText;
		if($('list_na_mere')){
			list_na = $('list_na_mere').value;
			listeNaJS = list_na.split("@");
		}
		
		if($('list_elements_reseaux')){
			list_na = $('list_elements_reseaux').value;
			//alert(list_na);
			_listeSelectedNa = list_na.split("|ss|");
			if ( _modeNaSelection == 'dashboard_normal' || _modeNaSelection == 'dashboard_generique')
			{
				if(list_na.length > 0)
				{	
					changeNaSelectionIcon('on'); 
					//alert('on'); 
				}
				else
				{	
					changeNaSelectionIcon('off');
					//alert('off'); 
				}
			}
		}
	}
	
	if ( _winNaSelection_create )
		_winNaSelection.updateHeight(); // On met-à-jour la hauteur de la fenêtre
}

/*
	Chargement des Préférences de l'utilisateur enregistré en BDD.
*/
function loadUserPreferences()
{
	url = _linkToAjax+'genDashboard_na_in_session_ajax.php?action=load_preferences';
	_httpNaSelection.open('GET', url, true);
	_httpNaSelection.onreadystatechange = loadUserPreferences_result;	
	_httpNaSelection.send(null);
	// On met-à-jour la barre de status de la pseudo fenêtre.
	updateNaselectionStatus('Updating in progress...');
}
function loadUserPreferences_result()
{
	if (_httpNaSelection.readyState == 4){
		//alert(http.responseText);
		// On met-à-jour la barre de status de la pseudo fenêtre.
		updateNaselectionStatus('');
		// On ferme la pseudo fenêtre de sélection des NA.
		closeNaselection();
		// Mise-à-jour du bouton de sélection des NA.
		checkNaSelect();
		// 24/08/2007 christophe : on ferme le div des NA courant.
		if ( _idCurrentElement != '' )
			$(_idCurrentElement).style.display = 'none';
	}
}


/*
	Affiche les valeurs de variables javascript pour le debug
*/
function displayJsVars(){
	alert('Produit :\n'+_product+'\nFamille :\n'+_family+'\nListe des Na :\n'+_listeSelectedNa+'\nNb elements choisis :\n'+_nbElements+'\nNb max d elements:\n'+_nbLimiteElements+'\nDiv actuellement ouvert\n'+_idCurrentElement+'\nListe des na mères :\n'+listeNaJS);
}

/*
	- 28/06/2007 christophe : ajout du param famille
	> Charge / affiche la liste des na_value d'une na donnée.
	- 11/07/2007 christophe : ajout du param check_all  0/1 à la fonction chargerContenu
*/
function chargerContenu(obj,famille,check_all,product) {
	if (debug) alert('selection_des_na.js l.463: chargerContenu(obj='+obj+',famille='+famille+',check_all='+check_all+',product='+product+')');
	
	url = _linkToAjax+"genDashboard_get_liste_na_ajax.php";
		url += "?family="+famille;
		url += "&product="+product;
		url += "&na="+obj;
		url += "&modeNaSelection="+_modeNaSelection;
		url += "&check_all="+check_all;
		url += "&selectChild="+_selectChild;
	
	// Si le div qui vat contenir la liste des léments réseaux est vide, on y ajoute le div affichant le loading.
	if ( check_all == 'yes' || check_all == 'no' )
	{	
		$(_idCurrentElement).innerHTML == ''
		$(_idCurrentElement).innerHTML = $('selection_na_loading').innerHTML;
	}
	
	_winNaSelection.toFront();
		
	_httpNaSelection.open("GET", url, true);
	_httpNaSelection.onreadystatechange = afficherContenu;	
	_httpNaSelection.send(null);
}
function afficherContenu(){
	if (_httpNaSelection.readyState == 4){
		$(_idCurrentElement).innerHTML = _httpNaSelection.responseText;
		_winNaSelection.updateHeight(); // On met-à-jour la hauteur de la fenêtre
		_winNaSelection.toFront();
	}
}


// Id de la NA sur laquelle l'utilisateur a cliqué.
var _idCurrentNa = null;

/*
	- 28/06/2007 christophe : ajout du param famille
	Ouvre / ferme les div disposés les uns en dessous des autres.
	> ouvre le div cliquez current_obj
	> ferme tous les autres div contenu dans le tableau listeNaJS.
	> charge si besoins le contenu du div ouvert.
*/
function openAccordion(current_obj,famille,product){
	if (debug) alert('selection_des_na.js l.506: openAccordion(current_obj='+current_obj+',famille='+famille+',product='+product+')');
	
	// Au cas où l'utilisateur cliquerais plusiseurs fois à la suite le même élément.
	if(current_obj != _naSelectedJS){
		// On affiche le div.
		$(current_obj).style.display = 'block';
		_idCurrentElement = current_obj; 
		
		// On initialise le moteur de recherche avec la NA désiré.
		_idCurrentNa = _idCurrentElement;
		initSearch(_idCurrentElement,famille);
		
		// Si le div affichant les infos sur la sélection courante est visible, on le cache.
		$('selection_des_na_message').innerHTML = "";
		
		// On ne charge le contenu qu'une seule fois.
		// 04/07/2007 christophe : on recharge le contenu du div en permanence
		// On affiche le loading.
		$(current_obj).innerHTML = $('selection_na_loading').innerHTML;
		
		// 16/07/2007 christophe : quand l'utilisateur charge une liste d'éléments réseaux, on met-à-jour la taille de la pseudo-fenêtre.
		_winNaSelection.updateHeight();
		
		// Chargement.
		chargerContenu(current_obj,famille,1,product);
		
		// On ferme tous les autres div. Le tableau listeNaJS est inialisé par le script PHP.
		_naSelectedJS = current_obj; 
		for(i=0;i<listeNaJS.length;i++){
			if(listeNaJS[i] != _naSelectedJS){
				if(listeNaJS[i] != ""){
					$(listeNaJS[i]).style.display = 'none';   
				}
			}
		}
	}
}


/*
	Permet de vérifier si l'utilisateur a sélectionné des éléments réseaux.
	Si oui, on change l'image du filtre des éléments réseaux.
	nb_of_selected_element
*/
function checkNaSelect()
{       // 12/08/2010 NSE DE firefox bz 16903 : on passe en mode synchrone (dernier paramètre à false) pour qu'à la fermeture de la sélection des NE, il n'y ait pas de message d'erreur sour Firefox 'You must select at least one network element'
	_httpNaSelection.open('GET', _linkToAjax+'genDashboard_na_in_session_ajax.php?action=nb_of_selected_element', false);
	_httpNaSelection.onreadystatechange = function(){
		if (_httpNaSelection.readyState == 4){
			if ( $('iframe_selecteur') )
				image_select_na = $('iframe_selecteur').contentWindow.$('img_select_na');
			else
				image_select_na = $('img_select_na');
				
			src = image_select_na.src;
			
			/*
				11/07/2007 christophe :
				si on est dans l'interface d'édition des alarmes et que aucun élément réseau n'a été sélectionné,
				on affiche un message d'erreur et on réaffiche l'interface de sélection des NA.
			*/
			if ( _httpNaSelection.responseText == 1 )
			{
				src_final = src.replace(/select_na_on.png/, 'select_na_on_ok.png');
			} 
			else 
			{
				src_final = src.replace(/select_na_on_ok.png/, 'select_na_on.png');
				if ( _modeNaSelection == 'interface_edition_alarme' )
				{
					/*
						04/09/2007 christophe :
						- si l'utilisateur n'a pas cliqué sur le nom de la NA pour afficher la liste des éléments réseaux et qu'il clique sur le bouton
						save, on enregistre par défaut 'all' en base. Cela est vérifié par la condition $(_idCurrentElement).innerHTML != '' (sinon, c'est que
						la liste des éléments réseaux sera affichées.) 
						- si l'utilisateur affiche la liste des éléments réseaux d'un NA et qu'il y a 'No element', le champ caché d'id = 'no_element'
						est présent dans la réponse ajax.
					*/
					if ( !$(_idCurrentElement) || $('no_element') )
					{
						src_final = src.replace(/select_na_on.png/, 'select_na_on_ok.png');
					} else {
						alert(_errorSelectOneNetworkElement);
						_winNaSelection.showCenter(false,135);
						_winNaSelection.updateHeight();
						return false;
					}
				}
			}
			image_select_na.src = src_final;
			
		}
	}
	_httpNaSelection.send(null);
}

/*
	Coordonnées d'un élément html.
*/
function calculeOffsetLeft(r){
  return calculeOffset(r,"offsetLeft")
}

function calculeOffsetTop(r){
  return calculeOffset(r,"offsetTop")
}

function calculeOffset(element,attr){
  var offset=0;
  while(element){
    offset+=element[attr];
    element=element.offsetParent
  }
  return offset
}

/*
	Permet d'afficher la liste des NA sélectionnée
	via la fonction popalt
<<<<<<< selection_des_na.js

 14/09/2010 MMT bz 17890 tooltip selection NA mal dimensionné/positionné
   - utilisation de la fonction popalt deja modifié pour supporter tous navigateurs
=======
        // 14/09/2010 NSE passage en paramètre de event
>>>>>>> 1.5.10.2
*/
function popalt_na_selection(event,msg)
{
	
	//14/09/2010 MMT bz 17890  remplacement de popalt2 par popalt

	if ( _listeSelectedNa.length == 0 )
	{
      popalt(msg);
	}
	else
	{
		_httpNaSelection.open('GET', _linkToAjax+'genDashboard_na_in_session_ajax.php?action=display_popalt', true);
		_httpNaSelection.onreadystatechange = function(){
			if (_httpNaSelection.readyState == 4){

					reponse = _httpNaSelection.responseText;
					/*
						13/09/2007 christophe : dans l'interface d'édition des alarmes, même si il n'y a pas d'éléments sélectionné, on met 'all' par défaut
					*/
					if ( _modeNaSelection == 'interface_edition_alarme' && reponse == 'No element selected' )
							reponse = 'All';
					
               popalt(reponse);

			}
		}
		_httpNaSelection.send(null);
	}
	
	
}


//14/09/2010 MMT bz 17890  remplacement de popalt2 par popalt
// 10/03/2008 - Modif. benoit : ajout des fonctions permettant de récupérer le nombre de valeurs de na disponible et de mettre à jour la balise Top concernée dans le selecteur

// 13/03/2008 - Modif. benoit : ajout du parametre 'path' à la fonction 'getNumberOfNa()'

var _nb_na_container = "";

function getNumberOfNa(na, object_ref_table, nb_na_container, path){
	
	_nb_na_container = nb_na_container;

	new Ajax.Request(path+'get_number_of_na.php', 
	{
		method: 'get',
		parameters: "na="+na+"&object_ref_table="+object_ref_table,
		onComplete : updateTopMessage
	});
}

function updateTopMessage(data){
	if($(_nb_na_container) != null) $(_nb_na_container).innerHTML = data.responseText;
}