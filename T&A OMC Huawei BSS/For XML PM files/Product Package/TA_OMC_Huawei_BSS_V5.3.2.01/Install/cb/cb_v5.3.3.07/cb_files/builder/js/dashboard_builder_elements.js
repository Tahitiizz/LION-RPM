/*
 * @cb50400
 *
 * 16/08/2010 NSE DE Firefox bz 16862 : afficahge des NA en commun
 */
/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Fonctions du GTM builder
*
*	Fonctions qui pilotent le GTM builder au niveau des éléments (et pas du GTM)
*	
* 	17/04/2009 - SPS 
*		- ajout d'une fonction qui va afficher les elements sous ie6
*	07/07/2009 SPS
* 		- on ne change pas le alt de l'image (conflit avec popalt) : correction bug 10406
*
*/


// on rend la liste des éléments d'un GTM sortable
function make_elements_sortable() {
	if ($('gtm_elements')) {
		Sortable.create('gtm_elements', { 
      onUpdate: function() {
  			// alert(Sortable.serialize("gtm_elements"));
  			new Ajax.Request("common_ajax_set_order.php", {
  				method: "post", parameters: {
  					id_page:$F('hidden_id_page'),
  					ordered: Sortable.serialize("gtm_elements"),
  					type:'dashboard',
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
 * Fonction permettant de corriger le bug 17840 afin d'éviter la redirection en
 * cas de Drag n Drop sur l'élément HREF
 * @param elt HtmlHrefElement object
 * @return Boolean
 */
function isDashElementDragging( elt )
{
    /*
    Au clic sur l'élément href,
    un test est effectué afin de
    determiner si le clic est du
    à un DragDrop ou non. Si oui
    la redirection href est annulée
    */
    if( elt.up( 2 ).getOpacity() < 1 ){
        return false; // L'élément parent était en cours de déplacement
    }
    return true; // Le clic est réelement du à une demande de redirection
}

/**
*	delete a plot in the GTM
*
*	@param string		li_id is the id of the li to delete 
*/
function delete_element(li_id) {
	id_page = $F('hidden_id_page');
	// remove element from document
	$(li_id).remove();
	// get the id of the element to remove
	id_bits = li_id.split('__');
	id = id_bits[1];
	// ajax to the script that will delete the element from the database
	new Ajax.Request("dashboard_ajax_del_elem.php", {
		method: "post",
		parameters: {
			id_page:id_page,
			id_elem:id,
			nonce:makeNonce()
		},
		onSuccess: function(transport) {
			html = (transport.responseText);
			// gestion des erreurs
			if (html != 'ok') {
				alert(html);
				return false;
			}
			flash("Graph removed from dashboard.");
			// on verifie qu'il reste encore des elements dans la liste
			var elems_left = $('gtm_elements').getElementsByTagName('LI'); 
			if (elems_left.length == 0) {
				$('nothing_inside').style.display = 'block';
				$('inside_gtm').style.display = 'none';
				// on efface le (empty) dans le nom du graph ...
				var selected_idx = $('main_select').selectedIndex;
				var myopt = $('main_select')['options'][selected_idx];
				var text = myopt['text'];
				text = text.replace(/ \(empty\)/gi,'');
				// ... pour être certain de pouvoir l'ajouter ensuite
				myopt['text'] = text+" (empty)";
			}
			// MaJ du menus "default order by" du dash
			setTimeout("update_default_order_by();",50);
			// find na levels in common
			get_na_levels_in_common();

            // Met à jour la référence produit ('multi product' ou label produit) (bz20214)
            updateProductReference( 'remove', elems_left.length );
		}
	});
}



/**
*	show/hide tous les NA levels de tous les éléments (raw/kpi) du GTM
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
		//$('show_na_levels').alt = "Show network aggregation levels";
		getStyleRule('na_level_css',div_na_levels).style.display = 'none';
		//	if (nb_elems > 0)
		//		for (i=0; i<nb_elems; i++)
		//			elems[i].getElementsByClassName('na_levels')[0].style.display = 'none';
	} else {
		$('show_na_levels').src = image_src.replace(/_off.png/gi,'.png');
		//$('show_na_levels').alt = "Hide network aggregation levels";
		getStyleRule('na_level_css',div_na_levels).style.display = 'block';
		//	if (nb_elems > 0)
		//		for (i=0; i<nb_elems; i++)
		//			elems[i].getElementsByClassName('na_levels')[0].style.display = 'block';
	}
}


// on ajoute un element (GTM) au Dashboard
function add_element_to_dash() {
	// on va chercher id_page
	var id_page = $F('hidden_id_page');
	if (id_page == 0) {
		alert("You need to create a dashboard before inserting data.");
		return;
	}
	
	// on va chercher les infos sur le raw/kpi
	id_bits = this.id.split('__');
	var id_elem = id_bits[1];
        var id_product = id_bits[2];
	
        // 02/02/2011 BBX
        // Si l'élément est désactivé on ne peut pas l'ajouter
        // BZ 20498
        if(id_bits[0] == 'disabled_element')
            return;

	// ajax to script that will add the element in the db
	new Ajax.Request("dashboard_ajax_add_elem.php", {
		method: "post",
		parameters: {
			id_page:id_page,
			id_elem:id_elem,
            id_product:id_product,
			nonce:makeNonce()
		},
		onSuccess: function(transport) {
			html = (transport.responseText);
			// gestion des erreurs
			if (html.slice(0,6) == 'Error:') {
				alert(html);
				return false;
			}
			// calcule le nouveau HTML listant les elements du Dash
			html = $('gtm_elements').innerHTML + html;
			// ajoute l'element en ayant détruit le sortable
			Sortable.destroy('gtm_elements');
			$('gtm_elements').innerHTML = html;
			// on s'assure que tout ça est bien visible comme il faut
			$('nothing_inside').style.display = 'none';
			$('inside_gtm').style.display = 'block';
			// on efface le (empty) dans le nom du graph ...
			var selected_idx = $('main_select').selectedIndex;
			var myopt = $('main_select')['options'][selected_idx];
			var text = myopt['text'];
			text = text.replace(/ \(empty\)/gi,'');
			myopt['text'] = text;
			// MaJ du menus "default order by" du dash
			setTimeout("update_default_order_by();",50);
			// recrée le sortable
			make_elements_sortable();
			// va chercher les NA levels en commun
			get_na_levels_in_common();
            // Met à jour la référence produit ('multi product' ou label produit) (bz20214)
            updateProductReference( 'add', $('gtm_elements').getElementsByTagName('LI').length );
		}
	});
	
}

// met à jour le menu "default order by" du dashboard
function update_default_order_by() {
	var id_page = $F('hidden_id_page');
	var id_rawkpi_selected = $F("sdd_sort_by_id");
	if (id_page) {
		new Ajax.Request("dashboard_get_order_by_options.php", {
			method: "post",
			parameters: {
				id_page:id_page,
				id_rawkpi_selected:id_rawkpi_selected,
				nonce:makeNonce()
			},
			onSuccess: function(transport) {
				txt = (transport.responseText);
				// gestion des erreurs
				if (txt.slice(0,6) == 'Error:') {
					alert(html);
					return false;
				}
				// on remplace les options
				mySelect = $('sdd_sort_by_id');
				mySelect.options.length = 0;
				var allOptions = txt.split('|sep2|');
				nb_options = allOptions.length;
				for (i=0; i<nb_options; i++) {
					var oneOption = allOptions[i].split('|sep1|');
					if (oneOption[0]==id_rawkpi_selected) {
						mySelect.options[i] = new Option(oneOption[1],oneOption[0],true,true);
					} else {
						mySelect.options[i] = new Option(oneOption[1],oneOption[0],false,false);
					}
				}
			}
		});
	}
}
