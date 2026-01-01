/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Fonctions du GTM builder
*
*	Fonctions qui pilotent le GTM builder au niveau des éléments (et pas du GTM)
*
* 17/04/2009 - modif SPS : ajout d'une fonction qui va afficher les elements sous ie6
*
*	15/07/2009 GHX
*		- Ajout de l'appel à la fonction displayReportNotConfigured() dans les fonctions delete_element() et add_element_to_report()
*/



// on rend la liste des éléments d'un GTM sortable
function make_elements_sortable() {
	if ($('gtm_elements')) {
		Sortable.create('gtm_elements', { onUpdate: function() {
			 // alert(Sortable.serialize("gtm_elements"));
			new Ajax.Request("common_ajax_set_order.php", {
				method: "post", parameters: {
					id_page:$F('hidden_id_page'),
					ordered: Sortable.serialize("gtm_elements"),
					type:'report',
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
*	delete a plot in the GTM
*
*	@param string		li_id is the id of the li to delete 
*/
function delete_element(li_id) {
	// remove element from document
	$(li_id).remove();
	// get the id of the element to remove
	id_bits = li_id.split('__');
	id = id_bits[1];
	// ajax to the script that will delete the element from the database
	new Ajax.Request("report_ajax_del_elem.php", {
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
			flash("Element deleted from report.");
			// on verifie s'il existe encore des elements
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
			// 13:10 15/07/2009 GHX
			// Appel de la fonction qui affiche/masque si certains dashboards du rapport ne sont pas configurés
			displayReportNotConfigured();

            // Met à jour la référence produit ('multi product' ou label produit) (bz20214)
            updateProductReference( 'remove', elems_left.length );
		}
	});
}


// on ajoute un element (dashboard/alarm) au report
function add_element_to_report() {
	// on va chercher id_page
	var id_page = $F('hidden_id_page');
	if (id_page == 0) {
		alert("You need to create the report before inserting data.");
		return;
	}
	// on va chercher les infos sur le dashboard/alarm
	id_bits = this.id.split('__');
	var id_elem = id_bits[1];
	var id_product = id_bits[2];
	sub_id_bits = id_bits[0].split('_');
	var class_object = sub_id_bits[1];
	// alert('id_elem='+id_elem+"\nid_product="+id_product+"\nclass_object="+class_object);return;
	
    // 02/02/2011 BBX
    // Si l'élément est désactivé on ne peut pas l'ajouter
    // BZ 20498
    if(id_bits[0] == 'disabled_element')
        return;

	// ajax to script that will add the element in the db
	new Ajax.Request("report_ajax_add_elem.php", {
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
			// on s'assure que tout ça est bien visible comme il faut
			$('nothing_inside').style.display = 'none';
			$('inside_gtm').style.display = 'block';
			// on efface le (empty) dans le nom du graph ...
			var selected_idx = $('main_select').selectedIndex;
			var myopt = $('main_select')['options'][selected_idx];
			var text = myopt['text'];
			text = text.replace(/ \(empty\)/gi,'');
			myopt['text'] = text;
			// recrée le sortable
			make_elements_sortable();
			// 13:10 15/07/2009 GHX
			// Appel de la fonction qui affiche/masque si certains dashboards du rapport ne sont pas configurés
			displayReportNotConfigured();
            // Met à jour la référence produit ('multi product' ou label produit) (bz20214)
            updateProductReference( 'add', $('gtm_elements').getElementsByTagName('LI').length );
		}
	});
	
}


/**
 * Fonction permettant de corriger le bug 18412 afin d'éviter la redirection en
 * cas de Drag n Drop sur l'élément HREF (inspiré de la fonction du bz 17840
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

