// 02/12/2009 BBX
// Creation du fichier pour la correction du bug 11482
//

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
	// 16:34 28/07/2009 GHX
	// Ajout de la condition sinon erreur JS dans l'édition du sélecteur sur My Profile
	if ( !$('img_select_na') )
		return;
	
	loadNelSelecteurFromSession();
	
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
		
		// var saveNelSelected = true;
		// on regarde si le id est dans la liste correspondant au na_level selectionne
		if (na2na[na_selected].indexOf(acc_id) != -1) {
			$('htmlPrefix_'+acc_id+'_title').style.display = 'block';
		} else {
			$('htmlPrefix_'+acc_id+'_title').style.display = 'none';
			// saveNelSelected = false;
		}
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
*/
function networkElementSelectionSaveHook() {
	var listeElems = $('nel_selecteur').value;
	if (listeElems == '') {
		$('img_select_na').setAttribute('alt_on_over', "No element selected");
	} else {
		new Ajax.Request(url_get_NA_selected,
			{
				// 09:23 26/08/2009 GHX
				// BZ 11230
				method:'post',
				parameters: {current_selection: listeElems, separator: '|s|', labels_only: 1, product: id_product},
				onSuccess: function(transport) {	
					var response = transport.responseText || 'No element selected';
					$('img_select_na').setAttribute('alt_on_over', response);
				},
				onFailure: function(){ $('img_select_na').setAttribute('alt_on_over', $('message_SELECTEUR_APPLICATION_CANT_ACCESS_TO').innerHTML+" \n"+url); }
			}
		)
	}
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
			parameters: {product:id_product,family:family_name},
			onSuccess: function(transport) {
				$('nel_selecteur').value = transport.responseText || '';
				if ( transport.responseText != '' )
				{
					$('img_select_na').className = 'bt_on';
				}
				else
				{
					$('img_select_na').className = 'bt_off';
				}
				networkElementSelectionSaveHook();
			}
		}
	)
} // End function loadNelSelecteurFromSession

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