/**
*	@cb4100@
*	- Creation SLC	 29/10/2008
*
*	Fonctions du Dashboard builder
*
*	Fonctions qui pilotent le Dashboard builder au niveau du Dashboard (pas des éléments)
*
*	03/06/2009 SPS : on verifie que le nom du dashboard ne contient pas de " (correction bug 9785)
*
*	15/07/2009 GHX
*		- Modification de la fonction get_na_levels_in_common() pour ajouter un paramètre dans la requete AJAX
*	16/02/2010 NSE bz 14281 
*		- ajout du paramètre periodMax (période max autorisée) à la fonction check_gtmForm pour vérifier que le paramètre entré ne dépasse pas ce qui est autorisé
*
*/

var ajax_loader = "<img src='images/ajax-loader.gif' alt='waiting for ajax reply' width='16' height='16'/>";

//	Affiche ou cache les propriétés du Dashboard
function get_dashboard_properties() {
	if ($('gtm_properties').style.display=='block') {
		// on cache les properties
		$('gtm_properties').style.display='none';
		// on change l'image (off)
		myImg = $('gtm_list').getElementsByClassName('info')[0].getElementsByTagName('IMG')[0];
		myImg.src = myImg.src.replace(/.png/gi,'_off.png');
	} else {
		// on affiche le formulaire d'édition des properties
		$('gtm_properties').style.display='block';
		// on met à jour le menu pie_order_by
		//update_select_from_elems('pie_order_by');
		//update_select_from_elems('gis_based_on');
		// on change l'icone (on)
		myImg = $('gtm_list').getElementsByClassName('info')[0].getElementsByTagName('IMG')[0];
		myImg.src = myImg.src.replace(/_off.png/gi,'.png');
	}
}


// affiche ou non le menu "homepage default mode" suivant que la checkbox "is homepage" est cochée ou pas
function click_is_homepage() {
	if ($('sdd_is_homepage').checked == true) {
		$('homepage_default_mode_div').style.display = 'block';
	} else {
		$('homepage_default_mode_div').style.display = 'none';
	}
}


// fonction qui calcule les na levels en commun en fonction des counters / kpis choisis
function get_na_levels_in_common() {
	
	// 16:15 15/07/2009 GHX
	// Envoi en paramètre le NA sélectionné dans le dashboard
	// si nouveau dashboard ou aucun NA d'enregistré en base => la valeur est vide
	if (($('inside_gtm')) && ($('inside_gtm').style.display != 'none')) {
		// on affiche l'ajax loader
		$('na_levels_in_common').innerHTML = "<li style='background:white;'>"+ajax_loader+"</li>" + $('na_levels_in_common').innerHTML;
		// on va chercher les NA
		new Ajax.Request("dashboard_ajax_get_nalevels_in_common.php", {
			method: "get",
			parameters: {
				id_page:id_page,
				current_selecteur_na:$F('sdd_selecteur_default_na'),
				nonce:makeNonce()
			},
			onSuccess: function(transport) {
				// on recupere tous les <li>
				txt = (transport.responseText);
				// on remplace ça dans le <ul id="na_levels_in_common">
				$('na_levels_in_common').innerHTML = txt;
				// maintenant on attrape <ul id="na_levels_in_common"> et <select id="sdd_selecteur_default_na">
				var myUL = $('na_levels_in_common');
				var mySelect = $('sdd_selecteur_default_na');
				var actual_na = $F('sdd_selecteur_default_na');
				// on supprime les options du select
				mySelect.options.length = 0;
				// on attrape les li
				lis = myUL.getElementsByTagName('LI');
				nb_lis = lis.length;
				if (nb_lis > 0) {
					for (i=0; i<nb_lis; i++) {
						li = lis[i];
						if ((li.className != '') && (li.className.slice(0,4) != 'axe3')) {
							zeclass = li.className.slice(8);
							if (zeclass==actual_na) {
								mySelect.options[i] = new Option(li.innerHTML,zeclass,true,true);
							} else {
								mySelect.options[i] = new Option(li.innerHTML,zeclass,false,false);
							}
						}
					}
				}
			}
		});
	}
}


// fonction de confirmation de la suppression du dashboard
function delete_dashboard() {
	if (confirm("Are you sure you want to delete that dashboard?")) {
		$('delete_gtm_form').submit();
	}
}

// validation du formulaire d'édition des properties du GTM
// 16/02/2010 NSE bz 14281 : ajout du paramètre periodMax (période max autorisée)
function check_gtmForm(periodMax) {
	/*03/06/2009 SPS : on verifie que le nom du dashboard ne contient pas de " (correction bug 9785)*/
	var res;
	if ( /"/.test( $F('page_name') ) ) {
		alert('Please enter a valid name to that dashboard');
		rouge('page_name','',true);
		res = false;
	}
	// check page_name is filled
	if ($F('page_name') == '') {
		alert("You need to give a name to that dashboard.");
		rouge('page_name','',true);
		res = false;
	}
	// 16/02/2010 NSE bz 14281 : vérifie si la période ne dépasse pas le max autorisé
	if ($F('sdd_selecteur_default_period') > periodMax) {
		alert("The maximum value for the period is "+periodMax+".");
		rouge('sdd_selecteur_default_period',periodMax,true);
		res = false;
	}
	return res;
}
