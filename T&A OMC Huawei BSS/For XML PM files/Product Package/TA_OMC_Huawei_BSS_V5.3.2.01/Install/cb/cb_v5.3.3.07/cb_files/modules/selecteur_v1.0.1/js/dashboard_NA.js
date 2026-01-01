/*
	23/06/2009 GHX
		- Modification dans la fonction updateNelSelecteur()
		- Ajout de la fonction updateSessionNelSelecteur()
	28/07/2009 GHX
		- Ajout de la fonction loadFavoritesNetworkElements()
		- Ajout de la condition sinon erreur JS dans l'édition du sélecteur sur My Profile dans le fonction updateNelSelecteur()
	26/08/2009 GHX
		- Correction du BZ 11230 [REC][T&A CB 5.0][TC#40586][TS#UC16-CB50][TP#1][INVESTIGATION DASHBOARD]: erreur accès ajax pour grand nb NE
			-> Utilisation de POST au lieu de GET

  06/06/2011 MMT DE 3rd Axis ajout du paramètre htmlIdPrefix pour differenciation 1er/3rd axis sur les fonctions
   27/07/2011 MMT Bz 22896 add 3rd axis preferences session variable
*/
/**
*	JavaScript allant avec la boite Network Aggregation du sélecteur
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*
*/


var _nb_na_container = "";

/**
*	Ce script appelle via AJAX /php/get_NA_number.php pour connaître le nombre d'élements correspondant
*	au NA level choisi.
*	Ce script est appelé sur $('selecteur_na_level').onchange
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*	@params	string		na	na_level sélectionné dans le menu na_level
*	@params	object	nb_na_container		<span> dans lequel la réponse est supposée être insérée
*	@params	string		path		url du repertoire dans lequel se trouve le script get_NA_number.php à apperler via ajax
*	@return	void		renseigne le innerHTML de $(nb_na_container)
*/
var _animation = null;
function getNumberOfNa(na, nb_na_container, path) {
	// Définition du container
	_nb_na_container = nb_na_container;
	// Attente
	function animate() {
		var content = $(_nb_na_container).innerHTML;
		if((content == '') || (content == '.') || (content == '..'))
			content = content+'.';
		else
			content = '';
		$(_nb_na_container).update(content);
	}
	_animation = setInterval(animate,500);
	// Récupération de l'id dashboard s'il existe
	var id_page = (_dashboard_id_page) ? _dashboard_id_page : '';
	// Requête Ajax
	new Ajax.Request(path+'selecteur.ajax.php',
	{
		method: 'get',
		parameters: "action=6&idT="+na+"&id_page="+id_page+"&count=1",
		onComplete : updateTopMessage
	});
}
function updateTopMessage(data) {
	if($(_nb_na_container) != null) {
	  clearInterval(_animation);
		$(_nb_na_container).update(data.responseText);
	}
}




/**
*	Cette fonction bascule l'affichage des accordéons dans le network element selector.
*	Ce script est appelé sur $('selecteur_na_level').onchange
*
*	Cette fonction utilise principalement le tableau na2na pour savoir quels accordéons afficher en fonction du na_level sélectionné.
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*	@params	none		réagi à la valeur de $('selecteur_na_level')
*	@return	void		renseigne le <div id="img_select_na" alt_on_over="XXX" />
*
*	06/06/2011 MMT DE 3rd Axis ajout du paramètre htmlIdPrefix pour differenciation 1er/3rd axis
*/
function updateNelSelecteur(htmlPrefix)
{
	// 06/06/2011 MMT DE 3rd Axis initialisation des champs dependant du 1er/3eme axe
	var imgId;
	var naLevelSelecteurId;
	if(!htmlPrefix || htmlPrefix == 'htmlPrefix_'){ // axe1
		htmlPrefix = 'htmlPrefix_';
		imgId = 'img_select_na';
		naLevelSelecteurId = 'selecteur_na_level';
	} else {//axe3
		imgId = 'img_select_na_axe3';
		naLevelSelecteurId = 'selecteur_axe3';
	}

	//alert('updateNelSelecteur ' + htmlPrefix + ' ' + imgId);
	// 16:34 28/07/2009 GHX
	// Ajout de la condition sinon erreur JS dans l'édition du sélecteur sur My Profile
	if ( !$(imgId) )
		return;

	loadNelSelecteurFromSession(htmlPrefix);

	// on va chercher la valeur du menu na_level
	var na_selected = $F(naLevelSelecteurId);

	// var separator = $F('htmlPrefix_saveFieldId_separator');
	// var listeElems = $F('nel_selecteur').split(separator);
	// var nbListeElems = listeElems.length;
	// var newListeElems = [];

    // on va chercher tous les accordéons
	var accs = $$('.accordion_title');
	var nb_acc = accs.length;
	for (var i=0; i < nb_acc; i++) {
		// pour chaque accordéon, on cherche le id
		var acc_id = accs[i].id;					// ex: acc_id = 'htmlPrefix_sgsn_title'
		var acc_id_prefix = acc_id.slice(0,acc_id.indexOf('_')+1);
		if(acc_id_prefix == htmlPrefix){
			acc_id = acc_id.slice(htmlPrefix.length);					// ex: acc_id = 'sgsn_title'
			
			acc_id = acc_id.slice(0,acc_id.lastIndexOf('_'));	// ex: acc_id = 'sgsn'

			// var saveNelSelected = true;
			// on regarde si le id est dans la liste correspondant au na_level selectionne
			if (na2na[na_selected].indexOf(acc_id) != -1) {
				$(htmlPrefix+acc_id+'_title').style.display = 'block';
			} else {
				$(htmlPrefix+acc_id+'_title').style.display = 'none';
				// saveNelSelected = false;
			}
		}
	// 06/06/2011 MMT DE 3rd Axis: netoyage
	}

}

/**
*	Ce script appelle via AJAX /php/get_NA_selected.php qui lui donne la liste des network elements selectionnés
*	et les met dans le alt_on_over du bouton network element selecteur.
*	Cette fonction est appelée lorsque l'on SAVE ou RESET le network element selecteur.
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*	@params	none
*	@return	void	renseigne le <div id="img_select_na" alt_on_over="XXX" />
*
*  06/06/2011 MMT DE 3rd Axis ajout du paramètre htmlIdPrefix pour differenciation 1er/3rd axis
*/
function networkElementSelectionSaveHook(htmlPrefix) {

	// 06/06/2011 MMT DE 3rd Axis initialisation des champs dependant du 1er/3eme axe
	var imgId;
	var nelSelecteurName;
	if(!htmlPrefix || htmlPrefix == 'htmlPrefix_'){ //axe 1
		htmlPrefix = 'htmlPrefix_';
		imgId = 'img_select_na';
		nelSelecteurName = 'nel_selecteur';
	} else {//axe3
		imgId = 'img_select_na_axe3';
		nelSelecteurName = 'axe3_2';
	}
	var listeElems = $(nelSelecteurName).value;

	if (listeElems == '') {
		$(imgId).setAttribute('alt_on_over', "No element selected");
	} else {
		new Ajax.Request(url_get_NA_selected,
			{
				// 09:23 26/08/2009 GHX
				// BZ 11230
				method:'post',
				parameters: {current_selection: listeElems, separator: $F(htmlPrefix + 'saveFieldId_separator'), labels_only: 1},
				onSuccess: function(transport) {
					var no_response = $('message_SELECTEUR_NO_RESPONSE').innerHTML;
					var response = transport.responseText || no_response;
					$(imgId).setAttribute('alt_on_over', response);
				},
				onFailure: function(){ $(imgId).setAttribute('alt_on_over', $('message_SELECTEUR_APPLICATION_CANT_ACCESS_TO').innerHTML+" \n"+url); }
			}
		)
	}
}


/**
  * Fonction appelée lorsque l'on clique sur SAVE dans la sélection des éléments réseaux.
  * Cette fonction permet d'enregistrer en session, la liste des éléments réseaux sélectionné
  *
  * 	22/06/2009 GHX
  * 		- Ajout de la fonction
  *
  * 	06/06/2011 MMT DE 3rd Axis ajout du paramètre htmlIdPrefix pour differenciation 1er/3rd axis
  *				Pour l'instant pas de preference 3eme axe mais prevision pour plus tard
  *
  * @param string path : chemin dans lequel se trouve le script selecteur.ajax.php
  */
function updateSessionNelSelecteur (htmlPrefix)
{
	// 06/06/2011 MMT DE 3rd Axis initialisation des champs dependant du 1er/3eme axe
	var naLevelSelecteurId;
	var nelSelecteurName
	var axe;
	if(!htmlPrefix || htmlPrefix == 'htmlPrefix_'){//axe 1
		htmlPrefix = 'htmlPrefix_';
		nelSelecteurName = 'nel_selecteur';
		naLevelSelecteurId = 'selecteur_na_level';
		axe = 1;
	} else {//axe3
		nelSelecteurName = 'axe3_2';
		naLevelSelecteurId = 'selecteur_axe3';
		axe = 3;
	}
	// 27/07/2011 MMT Bz 22896 add 3rd axis preferences session variable
	var listeElems = $(nelSelecteurName).value;
	new Ajax.Request(url_selecteur_rep_php+'selecteur.ajax.php',
		{
			// 09:23 26/08/2009 GHX
			// BZ 11230
			// 06/06/2011 MMT DE 3rd Axis ajout paramètre 'axe' pour action 12:sauve les éléments réseaux en SESSION
			method:'post',
			parameters: {action: 12, current_selection: listeElems, separator: $F(htmlPrefix+'saveFieldId_separator'), current_na : $F(naLevelSelecteurId), id_page : _dashboard_id_page, axe : axe},
			onSuccess: function(transport) {
			}
		}
	)
	
} // End function updateSessionNelSelecteur


/**
 * 06/06/2011 MMT DE 3rd Axis
 * callback for 3rd axis save field for session update, NOT USED as no 3rd axis preferences exists
 */
function updateSessionNelSelecteurAxe3()
{
	updateSessionNelSelecteur('axe3_');
}

/**
  * Appel en ajax un script PHP qui permet de récupérer à partir de ce qu'il y a en SESSION et des niveaux d'agrégatoin visible dans la sélectoin des NA
  * les éléments réseaux sélectionnées
  *
  *	23/06/2009 GHX
  *		- Création de la fonction
  *
  *	06/06/2011 MMT DE 3rd Axis ajout du paramètre htmlIdPrefix pour differenciation 1er/3rd axis
  */
function loadNelSelecteurFromSession (htmlPrefix)
{
	// 06/06/2011 MMT DE 3rd Axis initialisation des champs dependant du 1er/3eme axe
	var imgId;
	var naLevelSelecteurId;
	var nelSelecteurName;
	var axe;
	if(!htmlPrefix || htmlPrefix == 'htmlPrefix_'){ // axe1
		htmlPrefix = 'htmlPrefix_';
		imgId = 'img_select_na';
		naLevelSelecteurId = 'selecteur_na_level';
		nelSelecteurName = 'nel_selecteur';
		axe = 1;
	} else {//axe3
		imgId = 'img_select_na_axe3';
		naLevelSelecteurId = 'selecteur_axe3';
		nelSelecteurName = 'axe3_2';
		axe = 3;
	}
	
	
	new Ajax.Request(
		url_get_NA_session,
		{
			// 06/06/2011 MMT DE 3rd Axis ajout paramètre 'axe' pour get_NA_Session
			method:'get',
			parameters: {separator: $F(htmlPrefix + 'saveFieldId_separator'), current_na : $F(naLevelSelecteurId), id_page : _dashboard_id_page, axe : axe},
			onSuccess: function(transport) {
				$(nelSelecteurName).value = transport.responseText || '';
                // 16/08/2010 NSE DE Firefox : on ne peut pas inventer des attributs (css_class_on/css_class_off)
                if ( transport.responseText != '' )
				{
                    if($(imgId).className = $(htmlPrefix + 'openButtonId').css_class_on)
					    $(imgId).className = $(htmlPrefix + 'openButtonId').css_class_on;
                    else
                        $(imgId).className = 'bt_on';
				}
				else
				{
					if($(htmlPrefix + 'openButtonId').css_class_off)
                        $(imgId).className = $(htmlPrefix + 'openButtonId').css_class_off;
                    else
                        $(imgId).className = 'bt_off';
				}
				networkElementSelectionSaveHook(htmlPrefix);
			}
		}
	)
} // End function loadNelSelecteurFromSession

/**
  * Fonction appelé automatiquement quand on clique sur reset dans la sélection des éléments réseaux.
  * On fait un appel JS pour vider la sélection réseaux qui se trouve en session
  *
  *	23/06/2009 GHX
  *		- Création de la fonction
  *
  *	06/06/2011 MMT DE 3rd Axis ajout du paramètre htmlIdPrefix pour differenciation 1er/3rd axis
 */
function resetSessionNelSelecteur (htmlPrefix)
{
	// 06/06/2011 MMT DE 3rd Axis initialisation des champs dependant du 1er/3eme axe
	var axe;
	if(!htmlPrefix || htmlPrefix == 'htmlPrefix_'){
		htmlPrefix = 'htmlPrefix_';
		axe = 1;
	} else {
		axe = 3;
	}
	new Ajax.Request(url_selecteur_rep_php+'selecteur.ajax.php',
		{
			// 06/06/2011 MMT DE 3rd Axis ajout paramètre 'axe' pour action 13: recuperation des éléments réseaux de la SESSION
			method:'get',
			parameters: {action: 13, separator: $F(htmlPrefix + 'saveFieldId_separator'), id_page : _dashboard_id_page, axe:axe},
			onSuccess: function(transport) {
			}
		}
	)
} // End function resetSessionNelSelecteur

/**
 * 06/06/2011 MMT DE 3rd Axis
 * callback for 3rd axis resetSession, used in setResetButtonProperties()
 */
function resetSessionNelSelecteurAxe3()
{
	resetSessionNelSelecteur('axe3_');
}

/**
 * Recharge les éléments réseaux favories de l'utilisateur
 *
 * 15:19 28/07/2009 GHX
 *	Ajout de la fonction
 */
function loadFavoritesNetworkElements(htmlPrefix)
{
	// 06/06/2011 MMT DE 3rd Axis initialisation des champs dependant du 1er/3eme axe
	// actuelement uniquement utilisé pour Axe 1 mais prevision poour future
	var axe;
	if(!htmlPrefix || htmlPrefix == 'htmlPrefix_'){
		htmlPrefix = 'htmlPrefix_';
		axe = 1;
	} else {
		axe = 3;
	}

	new Ajax.Request(url_selecteur_rep_php+'selecteur.ajax.php',
		{
			method:'get',
			parameters: {action: 17,separator: $F(htmlPrefix + 'saveFieldId_separator'),id_page : _dashboard_id_page, axe:axe},
			onSuccess: function(transport) {
				loadNelSelecteurFromSession(htmlPrefix);
				closeNeSelection();
			}
		}
	)
}

/**
 * 06/06/2011 MMT DE 3rd Axis
 * callback for 3rd axis resetSession, NOT USED prevision for future 3rd axis element preferences
 */
function loadFavoritesNetworkElementsAxe3()
{
	loadFavoritesNetworkElements('axe3_');
}