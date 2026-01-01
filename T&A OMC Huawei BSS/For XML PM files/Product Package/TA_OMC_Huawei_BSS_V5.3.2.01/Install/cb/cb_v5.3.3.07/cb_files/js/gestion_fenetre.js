/************************************************************************************************
Fonction qui permet d'ouvrir une fenetre avec les dimensions souhaitees sans barre de navigation
*************************************************************************************************/


var win = null;

function ouvrir_fenetre(url,nom,scroll,resize,width,height)
{
LeftPosition = (screen.width) ? (screen.width-width)/2 : 0;
TopPosition = (screen.height) ? (screen.height-height)/2 : 0;
settings ='height='+height+',width='+width+',top='+TopPosition+',left='+LeftPosition+',resizable='+resize+',scrollbars='+scroll;
win = window.open(url,nom,settings);
win.focus();
return win;
}

// modif 16:41 17/07/2007 Gwénaël
	// of_2 = ouvrir_fenetre_2
	// appelle la function si dessus mais celle-ci ne renvoie rien, uniquement dans le but de pouvoir appalé cette fonction dans la balise a ( href)

// 13/02/2009 - Modif. benoit : renommage de la fonction 'of_2()' en 'open_window()'

function open_window(url,nom,scroll,resize,width,height){
	nom = nom || 'Navigation';
	scroll = scroll || 'yes';
	resize = scroll || 'yes';
	width = width || 400;
	height = height || 200;
	ouvrir_fenetre(url,nom,scroll,resize,width,height);
}

/**
 *
 */
function f_ot_one (ta, ta_value, mode, zoomplus, id_menu_en_cours, affHeader) {
	if (confirm('Switch to Over Network Element'))
		window.location="view_index.php?selecteur_scenario=byurl&ta="+ta+"&ta_value="+ta_value+"&mode="+mode+"&"+zoomplus+"&id_menu_en_cours="+id_menu_en_cours+"&affichage_header="+affHeader;
} // End function change view
/**
 *
 */
function f_one_ot (na, na_value, ta, ta_value, mode, zoomplus, id_menu_en_cours, affHeader) {
	if (confirm('Switch to Overtime'))
		window.location="view_index.php?selecteur_scenario=byurl&na="+na+"&na_type=network_agregation&na_value="+na_value+"&ta="+ta+"&ta_value="+ta_value+"&mode="+mode+"&"+zoomplus+"&id_menu_en_cours="+id_menu_en_cours+"&affichage_header="+affHeader;
} // End function change view