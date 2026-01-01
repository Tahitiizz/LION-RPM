/*
 * @cb50400
 *
 * 16/08/2010 NSE DE Firefox bz 16862 : afficahge des NA en commun
 */
/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Fonctions du Graph builder
*
*	Fonctions qui pilotent le Graph builder au niveau des éléments (et pas du Graph)
*	
* 	17/04/2009 - SPS 
*		- ajout d'une fonction qui va afficher les elements sous ie6
*	07/07/2009 - SPS
* 		- on ne change pas le alt de l'image (conflit avec popalt) : correction bug 10406
*	05/08/2009 GHX
*		- Appel à la fonction hasCumulatedBar() quand on sauvegarde les propriétés d'un élément du graphe (BZ 6038)
*	14/08/2009 GHX
*		- (Evo) Modification pour prendre en compte le faite que dans un graphe on peut avoir plusieurs fois le meme KPI [code+label] identique et qu'il est considere comme un seul
*	30/11/2009 MPR 
*		- Correction du bug 13105 : On remplace les ' par des espaces
*	09/06/10 YNE/FJT : SINGLE KPI
*	07/04/2011 NSE bz 21698 : la méthode isSingleKpiHasElement retourne false alors qu'un graph a été ajouté
*/


// on rend la liste des éléments d'un Graph sortable
function make_elements_sortable() {
	if ($('gtm_elements')) {
		Sortable.create('gtm_elements', { onUpdate: function() {
			update_select_from_elems('pie_split_by');
			update_gis_select_from_elems('gis_based_on');
			update_select_from_elems('default_orderby');
			// alert(Sortable.serialize("gtm_elements"));
			new Ajax.Request("common_ajax_set_order.php", {
				method: "post", parameters: {
					id_page:$F('hidden_id_page'),
					ordered: Sortable.serialize("gtm_elements"),
					type:'GTM',
					nonce:makeNonce()
				},
				onSuccess: function(transport) {
					txt = (transport.responseText);
					if (txt != 'ok') {
						alert(txt);
						return false;
					} else {
						flash("Elements order saved");
					}
				}
			});
		}});
		
		 /**
     * 17/04/2009 - modif SPS : ajout d'une fonction qui va afficher les elements sous ie6
     *   en effet, sur ie6, on ne voit pas les elements au chargement du graph, ms on les voit qd on les trie
     **/     
    //test si ie6
    if (!window.XMLHttpRequest) {
      //on recupere les elements de la liste
      var elems_left = $('gtm_elements').getElementsByTagName('LI'); 
      //on met l'opacite a 1		
      for(var i=0;i < elems_left.length; i++) {
        elems_left[i].setOpacity(1);
      }   
    }
		
	}
}


/**
*	delete a plot in the graph
*
*	30/01/2009 GHX
*		- ajout d'un deuxieme "_" dans le split [REFONTE CONTEXTE]
*
*	@param string		li_id is the id of the li to delete 
*/
function delete_element(li_id) {
	// remove element from document
	$(li_id).remove();
	// get the id of the element to remove
	// 11:45 30/01/2009 GHX
	// Ajout d'un deuxième "_"
	id_bits = li_id.split('__');
	id = id_bits[1];
	// ajax to the script that will delete the element from the database
	new Ajax.Request("graph_ajax_del_elem.php", {
		method: "post",
		parameters: {
			id:id,
			nonce:makeNonce()
		},
		onSuccess: function(transport) {
			html = (transport.responseText);
			// gestion des erreurs
			if (html.slice(0,6) == 'Error:') {
				alert(html);
				return false;
			}
			flash("Data removed from Graph.");
			update_select_from_elems('pie_split_by');
			update_gis_select_from_elems('gis_based_on');
			update_select_from_elems('default_orderby');
			get_na_levels_in_common();
			// 11:09 14/08/2009 GHX
			checkElementsWithSameName();
            // Met à jour la référence produit ('multi product' ou label produit) (bz20214)
            updateProductReference( 'remove', $('gtm_elements').getElementsByTagName('LI').length );
		}
	});
}

/*
*  affiche les properties d'une courbe (raw/kpi)
*
*	30/01/2009 GHX
*		- ajout d'un deuxieme "_" dans le split [REFONTE CONTEXTE]
*/
function get_data_properties(prop_id) {
	var prop = $(prop_id);
	// on calcule l'id de la courbe dans sys_pauto_config
	// 11:45 30/01/2009 GHX
	// Ajout d'un deuxième "_"
	var id_bits = prop_id.split('__');
	var id = id_bits[1];
	// on cache les properties
	if (prop.style.display == 'block') {
		prop.innerHTML = '';
		prop.style.display = 'none';
		// change l'image (off)
		var myImg = prop.parentNode.getElementsByClassName('info')[0].getElementsByTagName('IMG')[0];
		myImg.src = myImg.src.replace(/.png/gi,'_off.png');
	// on montre les properties
	} else {
		// ajax pour recupérer le formulaire des properties
		new Ajax.Request("graph_ajax_get_elem_properties.php", {
			method: "post",
			parameters: {
				id:id,
				nonce:makeNonce()
			},
			onSuccess: function(transport) {
				html = (transport.responseText);
				prop.innerHTML = html;
				prop.style.display = 'block';
				// active les color pickers
				var picker = new ColourPicker('color__'+id, 'color_btn__'+id);
				var fill_picker = new ColourPicker('fill_color__'+id, 'fill_color_btn__'+id);
	//			$('elem_prop_form_'+id).onsubmit = submit_elem_prop;
				// change l'image (on)
				var myImg = prop.parentNode.getElementsByClassName('info')[0].getElementsByTagName('IMG')[0];
				myImg.src = myImg.src.replace(/_off.png/gi,'.png');
				// 29/03/10 YNE
				// Hide Cumulated bar and cumulated line choise when single kpi is selected
				if($('object_type_single_kpi').checked){
					var opt2 = document.createElement('optgroup');
					var option1 = $('display_type__cumulatedbar__'+id);
					opt2.label = option1.text;
					opt2.value = option1.value;
					$('display_type__'+id).replaceChild(opt2,option1);
					
					var opt2 = document.createElement('optgroup');
					var option2 = $('display_type__cumulatedline__'+id);
					opt2.label = option2.text;
					opt2.value = option2.value;
					$('display_type__'+id).replaceChild(opt2,option2);
				}
			}
		});
	}
}


/**
 * Fonction permettant de valider les proproétés d'un éléments via A.J.A.X.
 *
 *  @param id Identifiant du Graph (l'id du plot = sys_pauto_config.id = graph_data.id_data)
 */
function submit_elem_prop( id )
{
    var elform      = $( 'elem_prop_form__' + id ); // On trouve notre formulaire
    var pos_ordo    = 'left'; // On s'occupe des radio bouton position_ordonnee
    var data_legend = new String( elform.data_legend.value );
    var reg         = new RegExp( "'", "g" );
	
    if (elform.position_ordonnee[1].checked) {
        pos_ordo = 'right';
    }

	// maj 30/11/2009 - MPR : Correction du bug 13105 - On remplace tous les ' par des espace
    elform.data_legend.value = data_legend.replace(reg," ");

    new Ajax.Request("graph_ajax_set_elem_properties.php",
    {
		method: "post",
		parameters: {
			id:id,
			data_legend:elform.data_legend.value,
			display_type:elform.display_type.options[elform.display_type.selectedIndex].value,
			line_design:elform.line_design.options[elform.line_design.selectedIndex].value,
			position_ordonnee:pos_ordo,
			color:elform.color.value,
			fill_color:elform.fill_color.value,
			fill_transparency:elform.fill_transparency.options[elform.fill_transparency.selectedIndex].value,
			nonce:makeNonce()
		},
		onSuccess: function(transport) {
			txt = (transport.responseText);
			if (txt != 'ok') {
				alert(txt);
				return false;
			}
            $('elem_prop__'+id).style.display='none'; // On ferme le formulaire

			// on remet en gris l'icone "application edit"
			var myImg = $('elem_prop__'+id).parentNode.getElementsByClassName('info')[0].getElementsByTagName('IMG')[0];
			myImg.src = myImg.src.replace(/.png/gi,'_off.png');

            // 16/02/2011 OJT : bz17518, on ne met plus à jour le DataLegend

			// on met à jour les menus pie_order_by et gis_based_on
			update_select_from_elems('pie_split_by');
			update_gis_select_from_elems('gis_based_on');
			update_select_from_elems('default_orderby');
			flash("Element properties saved.");
			
            // 05/08/2009 GHX : Correction du BZ 6038
			hasCumulatedBar();
			// 14/08/2009 GHX
			checkElementsWithSameName();
            return true;
		}
	});
}


/**
* 	show/hide tous les NA levels de tous les éléments (raw/kpi) du GTM
*
*	07/07/2009 SPS
* 		- on ne change pas le alt de l'image (conflit avec popalt) : correction bug 10406
**/
function show_hide_na_levels() {
	var image_src = $('show_na_levels').src;
	var elems = $$('#gtm_elements LI');
	nb_elems = elems.length;
        // 17/08/2010 NSE DE Firefox bz 16862 : le paramètre selectordoit être en majuscules pour IE et en minuscules pour Firefox
        if ( document.styleSheets[0].cssRules ) {
            // pas IE
            var div_na_levels='div.na_levels';
        }
        else{
            // IE
            var div_na_levels='DIV.na_levels';
        }
	if (image_src.indexOf('_off.png') == -1) {
		$('show_na_levels').src = image_src.replace(/.png/gi,'_off.png');
		//$('show_na_levels').alt = "Show NA levels";
		getStyleRule('na_level_css',div_na_levels).style.display = 'none';
	} else {
		$('show_na_levels').src = image_src.replace(/_off.png/gi,'.png');
		//$('show_na_levels').alt = "Hide NA levels";
		getStyleRule('na_level_css',div_na_levels).style.display = 'block';
	}
}


/**
 * Méthode permettant d'ajouter un élément (raw/kpi) au GTM
 *
 * 30/01/2009 GHX : ajout d'un deuxieme "_" dans le split [REFONTE CONTEXTE]
 * 23/12/2010 OJT : bz19123 problème d'ajout de KPI sous Firefox en SingleKPI
 */
function add_element_to_GTM() {
	// on va chercher id_page
	var id_page = $F('hidden_id_page');
	if (id_page == 0) {
		alert("You need to create the graph before inserting data.");
		return;
	}
	
	// on va chercher les infos sur le raw/kpi
	// 11:45 30/01/2009 GHX
	// Ajout d'un deuxième "_"
	id_bits = this.id.split('__');
	var id_elem = id_bits[1];
	var id_product = id_bits[2];
	sub_id_bits = id_bits[0].split('_');
	var class_object = sub_id_bits[1];
	// alert('id_elem='+id_elem+"\nid_product="+id_product);return;
	
        // 02/02/2011 BBX
        // Si l'élément est désactivé on ne peut pas l'ajouter
        // BZ 20498
        if(id_bits[0] == 'disabled_element')
            return;

    // 22/03/10 YNE
    // Modification of insert's element when Single KPI is selected
    // Now it's not possible to add more one element on GTM with this configuration
   	if( $( 'object_type_single_kpi' ).checked && isSingleKpiHasElement() )
    {
		// user command windows to overwrite the old elements
		if(!confirm("Warning : A RAW Counter / KPI is already defined, do you want replace it ?")){
			return false;
		}
		else{
			// If user want to overwrite the element selected by the new
			new Ajax.Request("graph_ajax_change_elem.php", {
				method: "post",
				parameters: {
					id_page:id_page,
					id_elem:id_elem,
					id_product:id_product,
					class_object:class_object,
					nonce:makeNonce()
				},
				onSuccess: function(transport) {
					html = (transport.responseText);
					// error case
					if (html.slice(0,6) == 'Error:') {
						alert(html);
						return false;
					}
					
					// calcule le nouveau HTML lisant les elements du GTM
					html = html;
					// ajoute l'element en ayant détruit le sortable
					Sortable.destroy('gtm_elements');
					$('gtm_elements').innerHTML = html;
					// on efface le (empty) dans le nom du graph
					var selected_idx = $('main_select').selectedIndex;
					var myopt = $('main_select')['options'][selected_idx];
					var text = myopt['text'];
					text = text.replace(/ \(empty\)/gi,'');
					myopt['text'] = text;
					// MaJ des menus des properties du GTM
					update_select_from_elems('pie_split_by');
					update_gis_select_from_elems('gis_based_on');
					update_select_from_elems('default_orderby');
				// recrée le sortable
					make_elements_sortable();
					// va chercher les NA levels en commun
					get_na_levels_in_common();
					// 09:23 14/08/2009 GHX
					// On verifie si on des elements avec les memes nom et memes codes si oui on affiche un message d'erreur
					checkElementsWithSameName();
					// On cache les parties indésirables lorsque l'on est en Single KPI
                    // Met à jour la référence produit ('multi product' ou label produit) (bz20214)
                    updateProductReference( 'add', $('gtm_elements').getElementsByTagName('LI').length );
				}
			});
		}
	}
	else{
		// ajax to script that will add the element in the db
		new Ajax.Request("graph_ajax_add_elem.php", {
			method: "post",
			parameters: {
				id_page:id_page,
				id_elem:id_elem,
				id_product:id_product,
				class_object:class_object,
				nonce:makeNonce()
			},
			onSuccess: function(transport) {
				html = (transport.responseText);
				// gestion des erreurs
				if (html.slice(0,6) == 'Error:') {
					alert(html);
					return false;
				}
				
				// calcule le nouveau HTML lisant les elements du GTM
				html = $('gtm_elements').innerHTML + html;
				// ajoute l'element en ayant détruit le sortable
				Sortable.destroy('gtm_elements');
				$('gtm_elements').innerHTML = html;
				// on efface le (empty) dans le nom du graph
				var selected_idx = $('main_select').selectedIndex;
				var myopt = $('main_select')['options'][selected_idx];
				var text = myopt['text'];
				text = text.replace(/ \(empty\)/gi,'');
				myopt['text'] = text;
				// MaJ des menus des properties du GTM
				update_select_from_elems('pie_split_by');
				update_gis_select_from_elems('gis_based_on');
				update_select_from_elems('default_orderby');
				// recrée le sortable
				make_elements_sortable();
				// va chercher les NA levels en commun
				get_na_levels_in_common();
				// 09:23 14/08/2009 GHX
				// On verifie si on des elements avec les memes nom et memes codes si oui on affiche un message d'erreur
				checkElementsWithSameName();
                // Met à jour la référence produit ('multi product' ou label produit) (bz20214)
                updateProductReference( 'add', $('gtm_elements').getElementsByTagName('LI').length );
			}
		});
	}
}

/**
 * Test si un RAW/KPI est déjà configuré pour un Graph de type SingleKPI
 * bz19123 : Compatible multi navigateur (see http://www.w3schools.com/dom/prop_element_firstchild.asp)
 *
 * @return boolean
 */
function isSingleKpiHasElement()
{
    if ( ! $( 'gtm_elements' ).hasChildNodes() ) {
        return false;
    }

    // Récupère le premier enfant de type 1
    var child = $( 'gtm_elements' ).firstChild;
    var hasStyleChild = 0;
    // 07/04/2011 NSE bz 21698 : la balise de style est toujours présente en premier dans gtm_elements.
    // Il faut donc regarder ce qu'il y a comme autre bloc derrière
    while ( child != null && ( child.nodeType != 1 || child.nodeName.toLowerCase() == "style") ) {
        if(child.nodeName.toLowerCase() == "style"){
            // 07/04/2011 NSE bz 21698 : on mémorise qu'on a rencontré la balise style
			hasStyleChild = 1;
		}
        child = child.nextSibling;
    }

    // Si la balise est de type style, il ne s'agit pas d'un Raw/Kpi
    if ( child != null && child.nodeName.toLowerCase() == "style" ) {
        return false;
    }
    // 07/04/2011 NSE bz 21698 : si on a rencontré une balise style et rien d'autre
    if ( child == null && hasStyleChild){
        return false;
    }
    return true;
}

