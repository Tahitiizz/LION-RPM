/*
	29/05/2009 SPS adaptation du script dashboard_sort_by.js pour investigation dashboard
	
	@cree	31/07/2006
	@maj	31/07/2006
	@auteur ba
	
	---------- Kpi Filter Functions ----------
	
	Ensemble des fonctions servant pour le filtre raw/kpi du selecteur en mode overtime 
	
	- maj 01/03/2007, christophe : gestion des balises optgroup dans le select du sort by du sélecteur.
	
	- maj 23/02/2007, christophe : mise à jour du seuil des kpi > choix (ajout de la gestion de la balise select).
	
	- maj 05/02/2007, benoit : ajout du cas de traitement de la valeur du filtre lorsque cette valeur est 
	  un nombre à virgule et que la partie fractionnaire contient plus de 2 chiffres. Dans ce cas, on tronque 
	  le nombre à 2 chiffres après la virgule.
	  
	- maj 16/04/2007, gwénaël : ajout d'une condition avant d'afficher le nom du graph auquel appartient le raw/kpi, car dans Investigation Dashboard
	  les raw/kpi n'appartiennent à aucun graph du coup il y a marqué "Undefined".
	
	- maj 17/04/2007, christophe : modification  de l'appel à la fonction toggle pour gérer l'iframe (cf checkKpiFilterIsCorrect)

	- maj 08/08/2007, benoit : ajout d'une fonction d'ouverture de la fenêtre de selection des kpis via la libraire 'Prototype Window'
	
	12/05/2009 - SPS 
		- verification de la presence de l'element selecteur_sort_by_title
*/




// SLC 18/09/2008

/**
*	Cette fonction met à jour le titre du menu Sort By en cherchant le label de l'<optgroup> parent de l'<option> sélectionnée
*	du menu passé en paramètre
*	Cette fonction est lancée lors de $('selecteur_sort_by').onchange
*
*	@author	SLC - 18/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*	@params	object	obj	<select> concerné
*	@return	void		renseigne le innerHTML de <div id="selecteur_sort_by_title">
*/
function changeSortBy(obj)
{	/* 12/05/2009 - SPS : verification de la presence de l'element selecteur_sort_by_title	*/
	if( $('selecteur_sort_by_title') ) {
		if(obj.options[obj.selectedIndex].parentNode.label != undefined){
			// On récupère le label du optgroup auquel appartient la balise option cliquée.
			$('selecteur_sort_by_title').innerHTML = '['+obj.options[obj.selectedIndex].parentNode.label+']';
		} else {
			$('selecteur_sort_by_title').innerHTML = "";
		}
	}
}
changeSortBy($('selecteur_sort_by'));



// a partir de là, on gère le filtre Kpi

var _winKpiSelection;

// Fonction d'ouverture de la fenêtre de selection des kpis. La fonction utilise 'Prototype Window'

function openKpiSelection(titre)
{		
		
	_winKpiSelection = new Window({ 	
		className:"alphacube",
		title: titre,
		width:400, height:200,
		minWidth:0, minHeight:0,
		resizable:false,
		draggable:true,
		minimizable:false,
		recenterAuto: false,
		maximizable:false//,
		//onClose : checkNaSelect
		});

	_winKpiSelection.setZIndex(2500);
	_winKpiSelection.setContent('div_kpi_filter');
	_winKpiSelection.showCenter(false,135);
	_winKpiSelection.updateWidth();
	_winKpiSelection.updateHeight();
	
	_winKpiSelection_create = true;

}

// Fonction de fermeture de la fenêtre de selection des kpis

function closeKpiselection()
{
	// On ferme la fenêtre mais on ne la détruit pas.
	_winKpiSelection.close();
}


// Fonction permettant de changer la valeur du filtre (nom, operande et valeur)
function changeKpiFilterName(new_name, operande, value){
	
	var kpi_name_in_field = new_name;
	
	if (kpi_name_in_field.length > 20)
		kpi_name_in_field = kpi_name_in_field.substring(0,20)+"...";

	$('widget_selecteur_filter_name').value = kpi_name_in_field;
	$('widget_selecteur_filter_name').all_value = new_name;

	var operande_field = $('widget_selecteur_filter_operande');

	for (var i=0;i<operande_field.options.length;i++)
		if (operande_field.options[i].value == operande)
			operande_field.selectedIndex = i;

	$('widget_selecteur_filter_value').value = value;

	// on colle les valeurs dans le formulaire
	$("selecteur_filter_name").value		= new_name;
	$("selecteur_filter_operande").value	= operande;
	$("selecteur_filter_value").value		= value;

}

// Fonction permettant de remettre le filtre à sa valeur initiale (nom : celui du raw/kpi de tri, operande=aucun et valeur=nulle)
function removeKpiFilter(niveau, filter_name) {

	if (filter_name != null)
	{
		var filter_name_in_field = filter_name;
		
		if (filter_name_in_field.length > 20)
			filter_name_in_field = filter_name_in_field.substring(0,20)+"...";

		$('widget_selecteur_filter_name').value = filter_name_in_field;
		$('widget_selecteur_filter_name').all_value = filter_name;	
	}

	$('selecteur_filter_btn').src			= niveau+"images/icones/kpi_filter_off.png";
	$('selecteur_filter_btn').alt_on_over	= $('message_SELECTEUR_RAW_KPI_FILTER').innerHTML;
	$('widget_selecteur_filter_operande').selectedIndex = 0;
	$('widget_selecteur_filter_value').value = "";

	// on colle les valeurs dans le formulaire
	$("selecteur_filter_name").value		= "";
	$("selecteur_filter_operande").value	= "";
	$("selecteur_filter_value").value		= "";

}

/*
	Fonction permettant de verifier que la valeur du filtre est correcte (operande defini et valeur numérique et non nulle)
	> fonction appelée lorsque l'utilisateur clique sur le bouton 'Ok'
*/

function checkKpiFilterIsCorrect(){

	var allValid = true;
	var errorMsg = "";

	var operande_field	= $('widget_selecteur_filter_operande');
	var numeric_field	= $('widget_selecteur_filter_value');

	if (operande_field.options[operande_field.selectedIndex].value == "none")
	{
		allValid = false;
		errorMsg = $('message_SELECTEUR_FILTER_NOT_SET').innerHTML;"The filter operand is not set.";
		rouge(operande_field,'',1);
	}

	if (numeric_field.value == "")
	{
		allValid = false;
		if(errorMsg != "") errorMsg += "\n";
		errorMsg += $('message_SELECTEUR_FILTER_EMPTY').innerHTML;"The filter value is empty.";
		rouge(numeric_field,'',1);
	}
	else if (isNaN(numeric_field.value) == true)
	{
		allValid = false;
		if(errorMsg != "") errorMsg += "\n";
		errorMsg += $('message_SELECTEUR_FILTER_NOT_NUMERIC').innerHTML;"The filter value is not numeric.";
		rouge(numeric_field,'',1);
	} // 05/02/2007 - Modif. benoit : on traite le cas ou la partie fractionnaire de la valeur du filtre est supérieure à 2 chiffres
	else if ((numeric_field.value).indexOf('.') != -1)
	{
		var num_field_tab = (numeric_field.value).split('.');

		if (num_field_tab[1].length > 2) 
		{
			//num_field_tab[1] = Math.round(num_field_tab[1]/Math.pow(10, num_field_tab[1].length-2));
			num_field_tab[1] = num_field_tab[1].substr(0, 2);
			numeric_field.value = num_field_tab.join('.');
		}
	}

	if (!allValid)
	{
		alert(errorMsg);
	}
	else
	{		
		// on stocke les paramètres du filtre.
		filterSelect = $('widget_selecteur_filter_name');	// balise select du filtre.
		
		// on colle les valeurs dans le formulaire
		$("selecteur_filter_name").value		= $F('widget_selecteur_filter_name');
		$("selecteur_filter_operande").value	= $F('widget_selecteur_filter_operande');
		$("selecteur_filter_value").value		= $F('widget_selecteur_filter_value');

		// mise à jour de l'icône du filtre dans le selecteur > le filtre est actif.
		update_btn();
		
		closeKpiselection();
	}
}

// cette fonction met à jour la couleur et le alt_on_over du bouton du filter
/* adaptation pour investigation dashboard*/
function update_btn() {
	if ( $('selecteur_filter_btn') )
	{
		var img = $('selecteur_filter_btn');
		
		if ($("selecteur_filter_name").value != '') {
			
			var str_filter_name = $("selecteur_filter_name").value;
			
			//on recupere le nom du compteur
			var t_filter = str_filter_name.split("@");
			
			// Mise à jour du contenu du popalt
			img.alt_on_over = 	t_filter[2]
					+ ' ' + $('widget_selecteur_filter_operande').value
					+ ' ' + $('widget_selecteur_filter_value').value
					+ ' ' + $('widget_selecteur_filter_title').innerHTML;
			// Mise à jour de l'image
			if(img.src.indexOf('kpi_filter_on.png') == -1) {
				src_final = img.src.replace(/kpi_filter_off.png/, 'kpi_filter_on.png');
				img.src = src_final;
			}
		} else {
			// Mise à jour du contenu du popalt
			img.alt_on_over = $('message_SELECTEUR_RAW_KPI_FILTER').innerHTML;
			// Mise à jour de l'image
			if(img.src.indexOf('kpi_filter_on.png') == -1) {
				src_final = img.src.replace(/kpi_filter_on.png/, 'kpi_filter_off.png');
				img.src = src_final;
			}
		}
	}
}


// Fonction permettant de verifier qu'une valeur est bien numerique et non nulle
function chkNumeric(objName)
{
	var checkOK = "0123456789.-";
	var checkStr = objName;
	var allValid = true;
	var decPoints = 0;
	var allNum = "";

	for(var i=0;i<checkStr.value.length;i++)
	{
		for (var j=0;j<checkOK.length;j++)
		{
			if (checkStr.value.charAt(i) == checkOK.charAt(j))break;
			if (j == (checkOK.length-1))
			{
				allValid = false;
				break;
			}
		}
	}

	return allValid;
}
