/**
 * @cb5100@
 *
 * 23/07/2010 OJT : Correction BZ16894
 */
/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Fonctions du Graph builder
*
*	Fonctions qui pilotent le Graph builder au niveau du Graph (pas des éléments)
*
* 	03/06/2009 SPS : on verifie que le nom du GTM ne contient pas de " (correction bug 9785)
*	30/06/2009 MPR : Correction du bug 9775 : On désactive le bouton GIS based on si aucun produit n'est actif
*
*	27/07/2009 GHX
*		- Correction du BZ 4459 [REC][T&A GB 2.0][B4459][F705][GTM]: changement de position legende KO pour pie
*			-> Si on est sur un PIE, on ne peut pas selectionne la legende en haut (top) cf fonction change_object_type() 
*	05/08/2009 GHX
*		- Ajout de la fonction hasCumulatedBar() Correction du BZ 6038
*	12/08/2009 GHX
*		- Recorrection du BZ 9683
*			-> Autorisation des caracteres ( ) % espace / dans les chamsp Y-Axis
*	14/08/2009 GHX
*		- (Evo) Modification pour prendre en compte le faite que dans un graphe on peut avoir plusieurs fois le meme KPI [code+label] identique et qu'il est considere comme un seul
*	17/08/2009 GHX
*		- On ignore la case dans la detection des kpi identiques (code+legende)
*	09/06/10 YNE/FJT : SINGLE KPI
*/

var ajax_loader = "<img src='images/ajax-loader.gif' alt='waiting for ajax reply' width='16' height='16'/>";

/**
 * Affiche ou cache les propriétés du graph
 * 23/07/2010 OJT : Suppression d'une ligne pour correction bz16894
 */
function get_GTM_properties()
{
	if ( $( 'gtm_properties').style.display == 'block' )
    {
		// on cache les properties
		$('gtm_properties').style.display='none';
		// on change l'image (off)
		myImg = $('gtm_list').getElementsByClassName('info')[0].getElementsByTagName('IMG')[0];
		myImg.src = myImg.src.replace(/.png/gi,'_off.png');
	} else {
		// on affiche le formulaire d'édition des properties
		$('gtm_properties').style.display='block';
		// on met a jour le menu pie_split_by
		if ($('inside_gtm') != null) {
			update_select_from_elems('pie_split_by');
			update_gis_select_from_elems('gis_based_on');
			update_select_from_elems('default_orderby');
		}
		// On cache les éléments indésirable lorsque l'on est en singleKPI
		if($('object_type_single_kpi').checked){
			$('td_title_builder_ordonnee_right').style.display = 'none';
			$('td_input_builder_ordonnee_right').style.display = 'none';
		}

		// on change l'icone (on)
		myImg = $('gtm_list').getElementsByClassName('info')[0].getElementsByTagName('IMG')[0];
		myImg.src = myImg.src.replace(/_off.png/gi,'.png');
		// on regarde si Split by Third Axis doit être active ou pas
		check_split_by_third_axis();
	}
}


// fonction qui active ou désactive le bouton radio "Third Axis" de "Split by"
function check_split_by_third_axis() {
	if ($('gtm_properties').style.display=='block') {
		// on va chercher les elements na_levels en commun de type 3eme axe
		elemsAxe3InCommon = $$('#na_levels_in_common li.axe3');
		// alert('split_by_third_axis disabled = '+$('split_by_third_axis').disabled);
	
		if (elemsAxe3InCommon.length > 0) {
			// s'il y en a, on active le bouton "Third Axis"
			$('split_by_third_axis').disabled = false;
			$('label_split_by_third_axis').style.color = '#585858';
		} else {
			// sinon on désactive le bouton et on coche le bouton "First Axis"
			$('split_by_first_axis').checked		= false;
			$('split_by_third_axis').disabled	= true;
			$('split_by_first_axis').checked		= true;
			// alert('split_by_third_axis disabled = '+$('split_by_third_axis').disabled);
			$('label_split_by_third_axis').style.color = '#999';
		}
	}
}



// show/hide les options relatives aux graph/pie/singleKPI
function change_object_type() {
	if ($('object_type_graph').checked) {
		// type = graph
		$('gtm_graph_option').style.display = 'block';
		$('gtm_pie_option').style.display = 'none';
		$('object_type_graph').parentNode.style.background = '#CCC';
		$('object_type_pie').parentNode.style.background = 'none';
		$('object_type_single_kpi').parentNode.style.background = 'none';
		getStyleRule('css_hide_if_pie','.hide_if_pie').style.display = 'block';
		getStyleRule('css_hide_if_pie_and_single_kpi','.hide_if_pie_and_single_kpi').style.display = 'block';
		getStyleRule('css_hide_if_single_kpi','.hide_if_single_kpi').style.display = 'block';
		
		// 18/03/10 YNE
		// Affichages des block non nécessaire en mode Graph
		$('td_title_builder_ordonnee_right').style.display = 'block';
		$('td_input_builder_ordonnee_right').style.display = 'block';
		
		// 11:10 27/07/2009 GHX
		// Correction du BZ 4459
		// On peut selectionne la position de la legende en top
		$('position_legende_top').disabled = false;
		$('position_legende_top').checked = true;
		$('position_legende_right').checked = false;
		$('position_legende_right').disabled = false;
	} else if($('object_type_pie').checked) {
		// type = pie
		$('gtm_pie_option').style.display = 'block';
		$('gtm_graph_option').style.display = 'none';
		$('object_type_graph').parentNode.style.background = 'none';
		$('object_type_single_kpi').parentNode.style.background = 'none';
		$('object_type_pie').parentNode.style.background = '#CCC';
		getStyleRule('css_hide_if_pie','.hide_if_pie').style.display = 'none';
		getStyleRule('css_hide_if_pie_and_single_kpi','.hide_if_pie_and_single_kpi').style.display = 'none';
		getStyleRule('css_hide_if_single_kpi','.hide_if_single_kpi').style.display = 'block';
		
		// 18/03/10 YNE
		// Affichages des block non nécessaire en mode Pie
		$('td_title_builder_ordonnee_right').style.display = 'block';
		$('td_input_builder_ordonnee_right').style.display = 'block';
		
		// 11:10 27/07/2009 GHX
		// Correction du BZ 4459
		// On ne peut pas selectionne la position de la legende en top et on selectionne en right par defaut
		$('position_legende_top').disabled = true;
		$('position_legende_right').checked = true;
	}
	// 18/03/10 YNE
	// If single KPI is checked and more than one serie is already defined for this GTM
	else if($('object_type_single_kpi').checked) {
		if($('gtm_elements')){

			if($('gtm_elements').firstChild != $('gtm_elements').lastChild){
				$('object_type_graph').checked = true;
				alert('Only one RAW Counter / KPI is allowed in Single KPI mode. Keep only one RAW counter / KPI in order to switch in Single KPI mode.');
			}
			
			// If Single KPI is checked and everythings is OK
			if($('object_type_single_kpi').checked) {
				// type = single KPI
				$('gtm_pie_option').style.display = 'none';
				$('gtm_graph_option').style.display = 'none';
				$('object_type_graph').parentNode.style.background = 'none';
				$('object_type_pie').parentNode.style.background = 'none';
				$('object_type_single_kpi').parentNode.style.background = '#CCC';
				getStyleRule('css_hide_if_pie','.hide_if_pie').style.display = 'block';
				getStyleRule('css_hide_if_pie_and_single_kpi','.hide_if_pie_and_single_kpi').style.display = 'none';
				getStyleRule('css_hide_if_single_kpi','.hide_if_single_kpi').style.display = 'none';
				
				// 09/03/10 YNE
				// Hide not used block in this mode (Single KPI)
				$('td_title_builder_ordonnee_right').style.display = 'none';
				$('td_input_builder_ordonnee_right').style.display = 'none';
				
				$('position_legende_top').disabled = true;
				$('position_legende_right').disabled = true;
				$('position_legende_top').checked = true;
				$('position_legende_right').checked = false;
			}
		}else{ //cas de la création d'un nouveau graph
			$('gtm_pie_option').style.display = 'none';
			$('gtm_graph_option').style.display = 'none';
			$('object_type_graph').parentNode.style.background = 'none';
			$('object_type_pie').parentNode.style.background = 'none';
			$('object_type_single_kpi').parentNode.style.background = '#CCC';
			getStyleRule('css_hide_if_pie','.hide_if_pie').style.display = 'block';
			getStyleRule('css_hide_if_pie_and_single_kpi','.hide_if_pie_and_single_kpi').style.display = 'none';
			getStyleRule('css_hide_if_single_kpi','.hide_if_single_kpi').style.display = 'none';
			
			// 09/03/10 YNE
			// Hide not used block in this mode (Single KPI)
			$('td_title_builder_ordonnee_right').style.display = 'none';
			$('td_input_builder_ordonnee_right').style.display = 'none';
			
			$('position_legende_top').disabled = true;
			$('position_legende_right').disabled = true;
			$('position_legende_top').checked = true;
			$('position_legende_right').checked = false;
		}
	}
	return true;
}


// show/hide les options relatives au GIS
function change_gis() {
	if ($('gis_1').checked) {
		$('gis_based_on_div').style.display = 'block';
		$('gis_1').parentNode.style.background = '#CCC';
	} else {
		$('gis_1').parentNode.style.background = 'none';
		$('gis_based_on_div').style.display = 'none';
		
	}
}


// affiche ou occulte la ligne "line_design" dans le formulaire d'édition de courbe en fonction du type de la courbe
function update_line_design(id) {
	var display_type = $F('display_type__'+id);
	if ((display_type == 'line') || (display_type == 'cumulatedline')) {
		$('line_design__'+id).style.display = 'block';
	} else {
		$('line_design__'+id).style.display = 'none';
	}
}


// fonction qui calcule les na levels en commun en fonction des counters / kpis choisis
function get_na_levels_in_common() {
	if (($('inside_gtm')) && ($('inside_gtm').style.display != 'none')) {
		// on affiche l'ajax loader
		$('na_levels_in_common').innerHTML = "<li style='background:white;'>"+ajax_loader+"</li>" + $('na_levels_in_common').innerHTML;
		// on va chercher les NA
		new Ajax.Request("graph_ajax_get_nalevels_in_common.php", {
			method: "get",
			parameters: {
				id_page:id_page,
				nonce:makeNonce()
			},
			onSuccess: function(transport) {
				txt = (transport.responseText);
				$('na_levels_in_common').innerHTML = txt;
				// on regarde si Split by Third Axis doit etre active ou pas
				check_split_by_third_axis();
			}
		});
	}
}


// fonction de confirmation de la suppression du GTM
function delete_gtm() {
	if (confirm("Are you sure you want to delete that GTM?")) {
		$('delete_gtm_form').submit();
	}
}


function trim (str, charlist) {
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: mdsjack (http://www.mdsjack.bo.it)
    // +   improved by: Alexander Ermolaev (http://snippets.dzone.com/user/AlexanderErmolaev)
    // +      input by: Erkekjetter
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: DxGx
    // +   improved by: Steven Levithan (http://blog.stevenlevithan.com)
    // +    tweaked by: Jack
    // +   bugfixed by: Onno Marsman
    // *     example 1: trim('    Kevin van Zonneveld    ');
    // *     returns 1: 'Kevin van Zonneveld'
    // *     example 2: trim('Hello World', 'Hdle');
    // *     returns 2: 'o Wor'
    // *     example 3: trim(16, 1);
    // *     returns 3: 6
 
    var whitespace, l = 0, i = 0;
    str += '';
    
    if (!charlist) {
        // default list
        whitespace = " \n\r\t\f\x0b\xa0\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u200b\u2028\u2029\u3000";
    } else {
        // preg_quote custom list
        charlist += '';
        whitespace = charlist.replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '$1');
    }
    
    l = str.length;
    for (i = 0; i < l; i++) {
        if (whitespace.indexOf(str.charAt(i)) === -1) {
            str = str.substring(i);
            break;
        }
    }
    
    l = str.length;
    for (i = l - 1; i >= 0; i--) {
        if (whitespace.indexOf(str.charAt(i)) === -1) {
            str = str.substring(0, i + 1);
            break;
        }
    }
    
    return whitespace.indexOf(str.charAt(0)) === -1 ? str : '';
}


// maj MPR : Correction du bug 10431 - On peut mettre des espaces dans le titre du graph
function check_gtmForm() {
	/*03/06/2009 SPS : on verifie que le nom du GTM ne contient pas de " (correction bug 9785)*/
	if ( /[^a-zA-Z0-9_ -]/.test( $F('page_name') ) ) {
		alert('Please enter a valid name');
		rouge('page_name','',true);
		return false;
	}
	
	// check page_name is filled
	if (trim( $F('page_name')) == '') {
		alert("You need to give a name to that Graph.");
		rouge('page_name','',true);
		return false;
	}
	// check Yaxis has a label
	if ($F('ordonnee_left_name') == '') {
		// 15/06/2009 MPR : Correction du bug 9660 - Modifiction du message d'erreur (faute de grammaire)
		alert("You need to set the label of the left Y-Axis.");
		rouge('ordonnee_left_name','',true);
		return false;
	}
	// check graph_width is not too small
	if (parseInt($F('graph_width')) < graph_width_minimum) {
		alert("The minimum width of a graph is "+graph_width_minimum+"px");
		rouge("graph_width",graph_width_minimum);
		return false;
	}
	// check graph_height is not too small
	if (parseInt($F('graph_height')) < graph_height_minimum) {
		alert("The minimum height of a graph is "+graph_height_minimum+"px");
		rouge("graph_height",graph_height_minimum);
		return false;
	}
	
	// 30/07/2009 BBx : verification des valeurs numeriques
	if(isNaN($F('graph_width'))) {
		alert("Please, enter a correct numeric value");
		rouge("graph_width",graph_width_minimum);
		return false;	
	}
	if(isNaN($F('graph_height'))) {
		alert("Please, enter a correct numeric value");
		rouge("graph_height",graph_height_minimum);
		return false;	
	}
	
	// 30/07/2009 BBx : verification des valeurs texte
	// 17:49 12/08/2009 GHX
	// Correction du BZ 9683
	if ( /[^a-zA-Z0-9_ \/%-\(\)]/.test( $F('ordonnee_left_name') ) ) {
		alert('Please enter a valid label');
		rouge('ordonnee_left_name','',true);
		return false;
	}
	if ( /[^a-zA-Z0-9_ \/%-\(\)]/.test( $F('ordonnee_right_name') ) ) {
		alert('Please enter a valid label');
		rouge('ordonnee_right_name','',true);
		return false;
	}
	
	return true;
}



/**
*	On met à jour le contenu du menu déroulant gis en fonction des éléments du Graph / Dash
*	Attention : la nuance de cette fonction par rapport à update_select_from_elems() définie dans common.js
*	c'est qu'ici, on affiche uniquement les elements dont le produit a un gis activé
*
*	@param	string	select_id est l'id du menu
*	@return	void
*/
function update_gis_select_from_elems(select_id) {
	var mySelect = $(select_id);
	if (mySelect.getValue()>0) {
		var actual_value = mySelect.getValue();
	} else {
		var actual_value = $F('hidden_'+select_id);
	}
	// on va chercher toutes les donnees (raw/kpi) qui constituent le Graph
	var elems = $$('#gtm_elements LI');
	nb_elems = elems.length;
	if (nb_elems > 0) {
		// on verifie que inside_gtm est affiche
		if ($('inside_gtm').style.display != 'block') {
			$('inside_gtm').style.display = 'block';
			$('nothing_inside').style.display = 'none';
		}
		// on efface les options du select order by
		mySelect.options.length=0;
		// on boucle sur les elements
		j = 0;
		listElements=[];

        // Expression régulière pour la lecture de l'identifiant
        var reg = new RegExp("^gtm_element__", "g");

		for ( i = 0; i < nb_elems ; i++ )
        {
            // Lecture de l'identifiant (2011/09/21 bz23749, utilisation du RegExp)
            var val = elems[i].id.replace( reg, '' );

			// on recupere le label
			var li_label = elems[i].getElementsByClassName('label')[0];
			label = li_label.innerHTML;
			// on recupere le label du produit
			var prod_elem = elems[i].getElementsByClassName('product')[0];
			prod_label = prod_elem.innerHTML;
			
			// 13:43 14/08/2009 GHX
			// On recupere le nom et type de l'element
			var name_elem = elems[i].getElementsByClassName('name')[0].innerHTML;
			var type_elem = elems[i].getElementsByClassName('type')[0].innerHTML;
			
			// on cree l'option
			// 14:42 14/08/2009 GHX
			// On regarde si le type/nom/label n'a pas ete deja ajoute
			// 18:43 17/08/2009 GHX
			// On met tous en minuscule 
			var tmp = type_elem+name_elem+label
			tmp = tmp.toLowerCase();

			//if (activated_gis[prod_label] == 1 && listElements.indexOf(tmp) == -1 ) {
            // 25/11/2010 BBX
            // Correction de la condition pour afficher les valeurs
            // BZ 17464
            if ($('gis_1').value == 1 && listElements.indexOf(tmp) == -1 ) {
				if (actual_value == val) {
					mySelect.options[j] = new Option(label,val,true,true);
				} else {
					mySelect.options[j] = new Option(label,val,false,false);
				}
				// 14:42 14/08/2009 GHX
				listElements[j] = tmp;
				j++;
			}
			
		}
	}
}

/**
 * On vérifie si on des bar cumulées (via AJAX) sur les ordonnées de gauche et de droite en même temps
 * Si c'est le cas on affiche un message
 *
 *	05/08/2009 GHX
 *		- Fonction créé pour corriger le BZ 6038
 *
 * @author GHX
 * @version CB 5.0.0.03
 * @since CB 5.0.0.03
 */
function hasCumulatedBar ()
{
	new Ajax.Request("graph_ajax_has_cumulatedbar.php", {
		method: "post",
		parameters: {
			id_page: id_page, // 17/08/2010 OJT : Correction bz16864, utilisation de la varaible id_page
			nonce:makeNonce()
		},
		onSuccess: function(transport) {
			txt = (transport.responseText);
			if (txt == 'ok')
			{
				$('errorCumulatedBar').style.display = 'none';
			}
			else
			{
				$('errorCumulatedBar').style.display = 'block';
			}
		}
	});
}

/**
 * Cette fonction permet de verifier si plusieurs elements on le meme nom et labels. La verife si fait via un appel AJAX
 * Si oui on affiche un message
 *
 * @author GHX
 * @version CB 5.0.0.05
 * @since CB 5.0.0.05
 */
function checkElementsWithSameName ()
{
	new Ajax.Request("graph_ajax_elemts_with_same_name.php", {
		method: "post",
		parameters: {
			id_page: id_page, // 17/08/2010 OJT : Correction bz16864, utilisation de la varaible id_page
			nonce:makeNonce()
		},
		onSuccess: function(transport) {
			txt = (transport.responseText);
			if (txt == 'ok')
			{
				$('elementsWithSameNames').style.display = 'none';
			}
			else
			{
				$('elementsWithSameNames').style.display = 'block';
				$('elementsWithSameNames').innerHTML = txt;
			}
		}
	});
} // End function checkElementsWithSameName