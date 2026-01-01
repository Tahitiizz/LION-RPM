/*
	26/08/2009 GHX
		- Correction du BZ 11230 [REC][T&A CB 5.0][TC#40586][TS#UC16-CB50][TP#1][INVESTIGATION DASHBOARD]: erreur accès ajax pour grand nb NE
			-> Utilisation de POST au lieu de GET
			
	02/12/2009 BBX (BZ 11482) :
		- Ajout de la fonction loadFavoritesNetworkElements()
		- Ajout de la fonction loadNelSelecteurFromSession()

 06/06/2011 MMT DE 3rd Axis networkElementSelectionSaveHook : generalisation de l'initialisation des variables via l'id
 25/11/2011 ACS BZ 24784 raw/kpi tooltip
*/
/**
*	JavaScript allant avec la boite Investigation NA du sélecteur 
*
*	@author	SPS - 27/05/2009
*	@version	CB 5.0.0
*	@since	CB 5.0.0
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
*/
function updateNelSelecteur()
{
	 // on va chercher la valeur du menu na_level
	 var na_selected = $F('selecteur_na_level');
	
    // on va chercher tous les accordéons
		var accs = $$('.accordion_title');
		var nb_acc = accs.length;
		for (var i=0; i < nb_acc; i++) {
			// pour chaque accordéon, on cherche le id
			var acc_id = accs[i].id;					// ex: acc_id = 'htmlPrefix_sgsn_title'
			acc_id = acc_id.slice(11);					// ex: acc_id = 'sgsn_title'
			acc_id = acc_id.slice(0,acc_id.lastIndexOf('_'));	// ex: acc_id = 'sgsn'
			
			// on regarde si le id est dans la liste correspondant au na_level selectionné
			
			if (na2na[na_selected].indexOf(acc_id) != -1) {
				$('htmlPrefix_'+acc_id+'_title').style.display = 'block';
			} else {
				$('htmlPrefix_'+acc_id+'_title').style.display = 'none';
			}
		}
}



/**
*	Ce script donne la liste des network elements selectionnés
*	et les met dans le alt_on_over du bouton network element selecteur.
* 	cette fonction fait appel a une page via AJAX dont l'url est de la forme id_selecteur_url
*	Cette fonction est appelée lorsque l'on SAVE ou RESET le network element selecteur.
*	
*	@author	SPS - 27/05/2009
*	@version	CB 5.0.0
*	@since	CB 5.0.0
*	@params	string id id du selecteur
*	@return	void
*
*/
function networkElementSelectionSaveHook(id) {

	//06/06/2011 MMT DE 3rd Axis generalisation de l'initialisation des variables via l'id
	var imgId = id+'img';
	var urlId = id+'url';
	var nelSelecteurName = id+'selecteur';

	var listeElems = $(nelSelecteurName).value;
	if (listeElems == '') {
		$(imgId).setAttribute('alt_on_over', "No element selected");
	} else {
                // 22/11/2011 BBX
                // BZ 23263 : récupération du contenu du div
                var url = $(urlId).innerHTML;
		new Ajax.Request(url,
			{
				// 11:41 26/08/2009 GHX
				// BZ 11230
				method:'post',
				parameters: {
						current_selection: listeElems, 
						separator: $(id+'saveFieldId_separator').value, 
						labels_only: 1
				},
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
 * Recharge les éléments réseaux favories de l'utilisateur
 *
 * 15:19 28/07/2009 GHX
 *	Ajout de la fonction
 */

function loadFavoritesNetworkElements ()
{
	new Ajax.Request(url_selecteur_rep_php+'selecteur.ajax.php',
		{
			method:'get',
			parameters: {action: 17},
			onSuccess: function(transport) {
				loadNelSelecteurFromSession();
				closeNeSelection();
			}
		}
	)
}

/**
  * Appel en ajax un script PHP qui permet de récupérer à partir de ce qu'il y a en SESSION et des niveaux d'agrégatoin visible dans la sélectoin des NA
  * les éléments réseaux sélectionnées
  *
  *	23/06/2009 GHX
  *		- Création de la fonction
  */
function loadNelSelecteurFromSession ()
{
	new Ajax.Request(
		url_get_NA_session,
		{
			method:'get',
			parameters: {product: id_product,family: family_name},
			onSuccess: function(transport) {
				$('investigation_nel_selecteur').value = transport.responseText || '';
				if ( transport.responseText != '' )
				{
					$('investigation_nel_img').className = 'bt_on';
				}
				else
				{
					$('investigation_nel_img').className = 'bt_off';
				}
				networkElementSelectionSaveHook('investigation_nel_');
			}
		}
	)
} // End function loadNelSelecteurFromSession

