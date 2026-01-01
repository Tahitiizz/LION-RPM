/*
 * @cb50400
 *
 * 16/08/2010 NSE DE Firefox bz 16924 : balises du paramètre selector en min/maj suivant navigateur
 */
/**
*	@cb4100@
*	- Creation SLC	 04/11/2008
*
*	Fonctions javascript communes au GTM et Dashboard builder
*	
*	07/07/2009 - SPS
* 		- on ne change pas le alt de l'image (conflit avec popalt) : correction bug 10406
*	14/08/2009 GHX
*		- (Evo) Modification pour prendre en compte le faite que dans un graphe on peut avoir plusieurs fois le meme KPI [code+label] identique et qu'il est considere comme un seul
*	17/08/2009 GHX
*		- On ignore la case dans la détection des kpi identiques (code+legende)
*
*/

var ajax_loader = "<img src='images/ajax-loader.gif' alt='waiting for ajax reply' width='16' height='16'/>";

/**
*	On met à jour le contenu d'un menu déroulant des propriétés du Graph ou Dash en fonction des éléments du Graph / Dash
*	Utile pour les menus "pie order by", "gis based on" (pour les Graphs) et "sdd_sort_by_id" (pour les dashs) 
*
*	@param	string	select_id est l'id du menu
*	@return	void
*/
function update_select_from_elems(select_id) {
	var mySelect = $(select_id);
	if (mySelect.getValue()>0) {
		var actual_value = mySelect.getValue();
	} else {
		var actual_value = $F('hidden_'+select_id);
	}
	// on va chercher toutes les donnees (raw/kpi) qui constituent le Graph (ou les graphs qui constituent le dashboard)
	var elems = $$('#gtm_elements LI');
	nb_elems = elems.length;
	if (nb_elems > 0) {
		// on verifie que inside_gtm est affiché
		if ($('inside_gtm').style.display != 'block') {
			$('inside_gtm').style.display = 'block';
			$('nothing_inside').style.display = 'none';
		}
		// on efface les options du select order by
		mySelect.options.length=0;
		// on boucle sur les elements
		listElements=[];
		j=0;

        // Expression régulière pour la lecture de l'identifiant
        var reg = new RegExp("^gtm_element__", "g");

		for ( i= 0; i < nb_elems ; i++ )
        {
            // Lecture de l'identifiant (2011/09/21 bz23749, utilisation du RegExp)
            var val = elems[i].id.replace( reg, '' );

			// on recupère le label
			var li_label = elems[i].getElementsByClassName('label')[0];
			if (li_label.innerHTML.indexOf('<a href=') != -1) {
				// cas des dashboards où il y a un lien vers le GTM sur le label du GTM
				label = li_label.getElementsByTagName('A')[0].innerHTML;
			} else {
				// cas des GTMs
				label = li_label.innerHTML;
				
				// 13:43 14/08/2009 GHX
				// On recupere le nom et type de l'element
				var name_elem = elems[i].getElementsByClassName('name')[0].innerHTML;
				var type_elem = elems[i].getElementsByClassName('type')[0].innerHTML;
				
				// 18:45 17/08/2009 GHX
				// - On ignore la case dans la detection des kpi identiques (code+legende)
				var tmp = type_elem+name_elem+label;
				tmp = tmp.toLowerCase();
				if ( listElements.indexOf(tmp) != -1 )
					continue;
			}
			
			// on cree l'option
			if (actual_value == val) {
				mySelect.options[j] = new Option(label,val,true,true);
			} else {
				mySelect.options[j] = new Option(label,val,false,false);
			}
			
			// 14:42 14/08/2009 GHX
			var tmp = type_elem+name_elem+label;
			tmp = tmp.toLowerCase();
			listElements[j] = tmp ;
			j++;
		}
	} else {
		$('inside_gtm').style.display = 'none';
		$('nothing_inside').style.display = 'block';
		// on efface le (empty) dans le nom du graph ...
		var selected_idx = $('main_select').selectedIndex;
		var myopt = $('main_select')['options'][selected_idx];
		var text = myopt['text'];
		text = text.replace(/ \(empty\)/gi,'');
		// ... pour être certain de pouvoir l'ajouter ensuite
		myopt['text'] = text+" (empty)";
	}
}


// fonction qui genere un nonce (une chaine unique)
function makeNonce() {
	// pour faire simple, on renvoie simplement l'heure à la miliseconde pres + un random(1000)
	var d = new Date();
	var nonce = 'nonce_' + d.getHours() + d.getMinutes() + d.getSeconds() + d.getMilliseconds() + Math.floor(Math.random()*10001);
	return nonce;
}




// fonction qui affiche le message Flash
function flash(msg) {
	if (msg == 'off') {
		// on cache le message
		Effect.Fade('flash_msg');
	} else {
		// on affiche le message
		$('flash_msg').innerHTML = msg;
		Effect.Appear('flash_msg', {duration: 0.2});
		setTimeout('flash(\'off\');',800);
	}
}


// fonction qui surligne les champs de formulaire
function rouge(elem,value,focus) {
	myelem = $(elem);	// comme ça, on peut passer un ID ou un element HTML
	var rememberBorderColor	= myelem.style.borderColor;
	var rememberColor		= myelem.style.color;
	myelem.style.borderColor	= 'red';
	myelem.style.color		= 'red';
	// if (myelem.tagName == 'SELECT')
	if (focus) myelem.focus();
	if (value) myelem.value	= value;
	setTimeout("$('"+myelem.id+"').style.borderColor	= '"+rememberBorderColor+"';",1000);
	setTimeout("$('"+myelem.id+"').style.color		= '"+rememberColor+"';",1000);
}



// cette fonction retourne l'objet stylesheet appelée par
// <link rel="stylesheet" type="text/css" id="sheet_id" href="path_to_style_sheet.css"/>
// ou bien <style id="sheet_id">
// parce que $('sheet_id') ne fonctionnera pas
function getStyleSheet(sheet_id) {
	// on regarde si on est avec IE ou pas
	if ( document.styleSheets[0].cssRules ) {
		var browser = 'notIE';
		for (var i in document.styleSheets)
			if (document.styleSheets[i].ownerNode.id == sheet_id)
				return document.styleSheets[i];
	} else {
		var browser = 'IE';
		for (var i in document.styleSheets)
			if (document.styleSheets[i]['id'] == sheet_id)
				return document.styleSheets[i];
	}

	// stylesheet not found
	return false;
}


// cette fonction retourne l'objet RULE d'une css
// sheet : ou bien on donne l'ID de la css (dans ce cas, sheet est un string) ou bien on donne carrément l'objet css
// selector : le sélecteur dans la feuille de style (ex: H2.blue  ou  #builder FIELDSET legend:hover)
// dans le selector, les tags html doivent être en HIGH CASE
// 16/08/2010 NSE DE Firefox bz 16924 : remarque concernant le paramètre selector
// selector : majuscules pour IE et en minuscules pour Firefox
function getStyleRule(sheet,selector) {
	// on attrape l'objet css sur lequel on va travailler (en fonction du fait qu'on nous ait donné l'objet directement ou juste son id)
	if (typeof sheet == 'string') {
		var mySheet = getStyleSheet(sheet);
	} else {
		var mySheet = sheet;
	}
	
	// on regarde si on est avec IE ou pas
	if ( document.styleSheets[0].cssRules ) {
		var browser = 'notIE';
		var rulesName = 'cssRules';
	} else {
		var browser = 'IE';
		var rulesName = 'rules';
	}
	// on recherche notre rule
	for (var i in mySheet[rulesName]) {
//		alert(i +' : selecteur='+ mySheet[rulesName][i].selectorText);
		if (mySheet[rulesName][i].selectorText == selector)
			return mySheet[rulesName][i];
	}
	
	// on a pas trouvé la rule
	return false;
}


// exemple utilisation :
// mySheet	= getStyleSheet('inline_products_css');
// getStyleRule(mySheet,'LI.prod_1').style.background = 'yellow';


/**
*	affiche ou cache les informations concernant l'utilisation des builders
*	
*	07/07/2009 - SPS
* 		- on ne change pas le alt de l'image (conflit avec popalt) : correction bug 10406
*/
function show_hide_information() {
	var image_src = $('show_builder_information').src;
	if ($('builder_information').style.display == 'none') {
		$('builder_information').style.display = 'block';
		$('show_builder_information').src = image_src.replace(/_off.png/gi,'.png');
		//$('show_builder_information').alt = "Hide builder informations";
	} else {
		$('builder_information').style.display = 'none';
		$('show_builder_information').src = image_src.replace(/.png/gi,'_off.png');
		//$('show_builder_information').alt = "Show builder informations";
	}
}

/**
 * Met à jour la référence du produit lié au Dash/Graph/Report dans la liste
 * déroulante.
 *
 * 26/01/2011 OJT : bz20325 Gestion des caractères spéciaux avec unescapeHTML
 *
 * @since 5.0.4.14
 * @param mode "add" ou "remove"
 * @param nbElt Nombre d'élément restant (gestion du empty)
 */
function updateProductReference( mode, nbElt )
{
    /*
        Test si la référence produit existe (mode multiproduit), sinon on ne
        fait rien. 'productsListFilter' n'est affiché dans Dash/Graph/Report
        que dans un mode multi produit
    */
    if ( ( $( 'productsListFilter' ) != null ) && ( $( 'productsListFilter' ).style.display != 'none' ) )
    {
        var divElts = $$( "div.product"  );
        var mainSelect = $( "main_select" );
        var productsLabels = new Array();
        var selectedIndexText = mainSelect.options[mainSelect.selectedIndex].text;

        // On récupère tous les labels produits des éléments en cours
        for ( var i = 0 ; i < divElts.length ; i++ )
        {
            productsLabels.push( divElts[i].innerHTML.unescapeHTML() );
        }
        productsLabels = array_unique( productsLabels ); // On supprime tous les doublons

        if( productsLabels.length == 1 ) // Si les élements sont tous du même produit...
        {
            if( mode == 'add' && nbElt == 1 ) { // Si il s'agit du premier ajout
                 selectedIndexText += " (" + productsLabels[0] + ")";
            }
            else {
                selectedIndexText = selectedIndexText.replace( /\([^)]*\)$/, "(" + productsLabels[0] + ")" );
            }
        }
        else if ( productsLabels.length == 0 ) // Si il n' y a plus d'élement...
        {
            selectedIndexText = selectedIndexText.replace( /\([^)]*\)$/, "" );
            selectedIndexText = selectedIndexText.replace( /\([^)]*\) $/, "(empty)" );
        }
        else // Si il n' y a plusieurs élements, on est dans un Dash/Graph/Report multi
        {
            selectedIndexText = selectedIndexText.replace( /\([^)]*\)$/, '(multi product)' );
        }
        mainSelect.options[mainSelect.selectedIndex].text = selectedIndexText;
    }
}

/**
 * Retourne le tableau source en enlevant tous les doublons
 *
 * @since 5.0.4.14
 * @param arr Tableau source à gérer
 * @return array
 */
function array_unique( arr )
{
    if ( arr.length > 1 )
    {
        arr = arr.sort();
        var arrUnique = new Array( arr[0] );
        for ( i = 1 ; i < arr.length ; i++ )
        {
            if( arr[i] != arrUnique[arrUnique.length-1] )
            {
                arrUnique.push( arr[i] );
            }
        }
        return arrUnique;
    }
    else
    {
        return arr;
    }
}
