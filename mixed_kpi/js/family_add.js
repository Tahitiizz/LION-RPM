/**
 * Affiche ou masque la liste des NA de chaque famille
 *
 * @author NSE
 */
function show_hide_na_levels ()
{
	var image_src = $('show_na_levels').src;
	var elems = $$('#productList LI');
	nb_elems = elems.length;
	if ( image_src.indexOf('_off.png') == -1 )
	{
		$('show_na_levels').src = image_src.replace(/.png/gi,'_off.png');
                // 17/09/2010 NSE bz 18026 différenciation casse IE/FF
                if(document.all)
                    getStyleRule('na_level_css','DIV.na_levels').style.display = 'none';
                else
                    getStyleRule('na_level_css','div.na_levels').style.display = 'none';
	}
	else
	{
		$('show_na_levels').src = image_src.replace(/_off.png/gi,'.png');
                if(document.all)
                    getStyleRule('na_level_css','DIV.na_levels').style.display = 'block';
                else
                    getStyleRule('na_level_css','div.na_levels').style.display = 'block';
	}
} // End function show_hide_na_levels


/**
 * cette fonction retourne l'objet RULE d'une css
 * sheet : ou bien on donne l'ID de la css (dans ce cas, sheet est un string) ou bien on donne carrément l'objet css
 * selector : le sélecteur dans la feuille de style (ex: H2.blue  ou  #builder FIELDSET legend:hover)
 * dans le selector, les tags html doivent être en HIGH CASE
 */
function getStyleRule(sheet,selector)
{
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
		if (mySheet[rulesName][i].selectorText == selector)
			return mySheet[rulesName][i];
	}
	
	// on a pas trouvé la rule
	return false;
} // End function getStyleRule

/**
 * cette fonction retourne l'objet stylesheet appelée par
 * <link rel="stylesheet" type="text/css" id="sheet_id" href="path_to_style_sheet.css"/>
 *  ou bien <style id="sheet_id">
 * parce que $('sheet_id') ne fonctionnera pas
 */
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

/**
 * Retourne la liste des NA en communs des familles sélectionnées
 *
 * @author GHX
 */
function get_na_levels_in_common()
{
	// Création d'une variable contenant la liste des familles sélectionnées
	var familySelected = '';
	$$('input[name="familybox[]"]').each(
		function(el) {
			if (el.checked)
				familySelected += el.value+";";
		}
	);
	
	// on affiche l'ajax loader
	// $('na_levels_in_common').innerHTML = "<li style='background:white;'>"+ajax_loader+"</li>" + $('na_levels_in_common').innerHTML;
	
	// on va chercher les NA en communs
	new Ajax.Request("php/ajax_get_nalevels_in_common.php", {
		method: "get",
		parameters: {
			family: familySelected
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
	
} // End function get_na_levels_in_common

/**
 * Vérifie le label de la famille avant de poster.
 * BZ 13169
 * @author BBX
 */
function checkValues(form)
{
	// Test du label du produit
	var checkExp = new RegExp("[^a-zA-Z0-9 _-]","gi");
	if(checkExp.test(form.familyName.value)) {
		alert(A_SETUP_MIXED_KPI_FAMILY_LABEL_NOT_CORRECT);
		return false;
	}
	
	// Tout est OK chef
	return true;
}