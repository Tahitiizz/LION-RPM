/**
 * @cb50402
 *
 * 13/09/2010 NSE bz 17845 : filtre par prod ko dans graph builder : différenciation IE / Firefox
 */
/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Fonctions qui pilotent l'affichage des raw/kpi de la colonne de gauche du GTM builder
*
*/


// show/hide les raw / kpi lists
function check_show(master_checkbox,slave_id) {
	
	if (slave_id != '') {
		// cas d'une checkbox kpi / raw
		if ($(master_checkbox).checked) {
			$(slave_id).style.display = 'block';
		} else {
			$(slave_id).style.display = 'none';
		}
	} else {
		// cas d'une checkbox product -- on est donc dans un cas kpi / raw (Graph builder, et PAS dashboard builder)
		
		// on cherche l'id du produit checked
		product_bits = master_checkbox.split('_');
		product_id = product_bits[2];

                // 13/09/2010 NSE bz 17845 : différenciation IE / Firefox
		if ($(master_checkbox).checked) {
                    if(document.all)
			getStyleRule('inline_products_css','#gtm_elements_list UL LI UL LI.prod_'+product_id).style.display = '';
                    else
			getStyleRule('inline_products_css','#gtm_elements_list ul li ul li.prod_'+product_id).style.display = '';
		} else {
                    if(document.all)
                        getStyleRule('inline_products_css','#gtm_elements_list UL LI UL LI.prod_'+product_id).style.display = 'none';
                    else
                        getStyleRule('inline_products_css','#gtm_elements_list ul li ul li.prod_'+product_id).style.display = 'none';
		}
	}
}


// filter RAW / KPI
// 29/07/2009 BBX : correction de la recherche. BZ 10823
function filter_list(li_id) {
	
	// we get the value of the filter
	filter_val = $('filter').value;
	filter_val = filter_val.toLowerCase();
	
	// on boucle sur tous les LI de la liste
	var reg = />([^<].*)</;
	if ($(li_id).style.display != 'none') 
	{
		for (i=0; i<elems_nb[li_id]; i++) 
		{
			var result = (reg.exec(elems_lists[li_id][i].innerHTML.toLowerCase()));
			var value = (result[1]).replace(/&nbsp;/,'');
			if (value.indexOf(filter_val) != -1) {
				elems_lists[li_id][i].style.display = '';	// donc ça prendra la valeur dictée par les autres règles css
			} else {
				elems_lists[li_id][i].style.display = 'none';
			}
		}
	}
}

/**
 * Affiche ou cache la zone de filtres. Utilisé dans GraphBuilder, Dashboard
 * builder et Report builder.
 *
 * 20/01/2011 OJT : Ajout effet scriptaculous suite bz20214
 */
function display_all_filters()
{
	var myImg = $( 'display_all_filters_img' );
    if( myImg != null )
    {
        if( myImg.src.endsWith( '_down.png' ) )
        {
            myImg.src = myImg.src.replace( /_down.png/gi, '_right.png' );
        }
        else
        {
            myImg.src = myImg.src.replace( /_right.png/gi, '_down.png' );
        }
    }
    Effect.toggle( 'all_filters', 'slide', {duration:0.3} );
}


// cette fonction cache / montre le prochain frère du tag en question
// on l'utilise pour cacher les listes de la colonne de gauche
function show_hide_nextSibling() {
	var mySibling	= this.nextSiblings()[0];	// on va chercher le prochain frère
	var myImg		= this.firstDescendant();	// on prend l'image >>
	if (mySibling.style.display == 'none') {
		mySibling.style.display = 'block';
		myImg.src = myImg.src.replace(/_right.png/gi,'_down.png');
	} else {
		mySibling.style.display = 'none';
		myImg.src = myImg.src.replace(/_down.png/gi,'_right.png');
	}
}

