/*
- ajout 23/10/2008 BBX : ajout d'une fonction new_kpi. BZ 7612
- maj 14/04/2008 Benjamin : reset du nom uniquement s'il s'agit d'un nouveau KPI. BZ5096
- maj 14/04/2008 Benjamin : application d'un readOnly plutôt qu'un disabled sur le champ name pour permettre la mise à jour d'un KPI existant. BZ5096
- maj 14/04/2008 : benjamin : ajout de la (des)activation de la checkbox percentage. BZ5096
- maj 23/05/2006 sls : dans la fonction affiche_equation() lorsque la checkbox du kpi choisi est disabled, les champs et boutons du formulaire sont désactivés
- maj 22/08/2006 fonction save_kpi() - verifie que le label n est pas vide
- maj 14/05/2007 Gwénaël  ajout de condition dans les fonctions reset_kpi and affiche_equation pour savoir si les champs sont présent afin de savoir dans quel formulaire on se trouve New Formula (Query Builder) ou Kpi Builder

	- maj 19/03/2008, benoit : correction du bug 5096

	13/10/2009 GHX
		- Ajout de la fonction showUploadDownload()

    13/08/2010 DE Firefox NSE bz 17080 : formulaire Querie Builder / New Formula HS -> remplacement de tous les formulaire par document.formulaire
    29/09/2010 NSE bz 18159 : suppression Kpi impossible -> mise à jour du champ zone_id_generic_counter

*/
function gestion_formule(operation, zone_formule)
{
	switch (operation)
	{
		case 'delete' :
			eval("document.formulaire.zone_formule_numerateur.value=''");
			break;

		case 'back' :
			var operators=new Array("+","-","*","/","(",")");
			formule=document.formulaire.zone_formule_numerateur.value;
			lastCharacter=formule.charAt(formule.length-1);
			lastCharacterIsAnOperator=inArray(lastCharacter,operators);
			if(lastCharacterIsAnOperator)	// if it's an operator, we just delete this character
			{
				formule=formule.substr(0,formule.length-1);
			} else {	// if it's not an operator, we delete until the previous operator (or we stop if length is 0)
				var previousOperatorHasNotBeenReached=true;
				while(previousOperatorHasNotBeenReached && formule.length>0)
				{
					formule=formule.substr(0,formule.length-1);
					lastCharacter=formule.charAt(formule.length-1);
					lastCharacterIsAnOperator=inArray(lastCharacter,operators);
					previousOperatorHasNotBeenReached=(!lastCharacterIsAnOperator);
				}
			}
			document.formulaire.zone_formule_numerateur.value=formule;
			break;

		case 'add' :
			if (zone_formule=='zone_formule_numerateur')
			{
				numerique="numerique_numerateur";
			} else {
				numerique="numerique_denominateur";
			}

			if (isNaN(eval("document.formulaire."+numerique+".value"))) //test si la valeur est numérique
			{
				alert('Add only a numeric value');
				eval("document.formulaire."+numerique+".value=''");
			} else {
				eval("valeur=document.formulaire."+numerique+".value");
				eval("document.formulaire."+zone_formule+".value=document.formulaire."+zone_formule+".value+valeur");
				eval("document.formulaire."+numerique+".value=''");
			}
			break;

		case 'add_field' : //utilisé uniquement pour la création des formules dans le builder report
			valeur=document.formulaire.field_name.value;
			eval("document.formulaire."+zone_formule+".value=document.formulaire."+zone_formule+".value+valeur");
			break;

		case 'add_table' : //utilisé uniquement pour la création des formules dans le builder report
			valeur=document.formulaire.table_name.value;
			eval("document.formulaire."+zone_formule+".value=document.formulaire."+zone_formule+".value+valeur+'.'");
			break;

		default :
			eval("document.formulaire."+zone_formule+".value=document.formulaire."+zone_formule+".value+operation");
			break;
	}
}


function delete_raw_data ()
{
	if (document.formulaire.generic_counter.value!="")
	{
		if (confirm('Delete this KPI ?'))
		{
			id_kpi=document.formulaire.zone_id_generic_counter.value;
			family=document.formulaire.family_group_table_name.value;
			product=document.formulaire.product.value;
			//alert (id_kpi);
			eval("window.location='../traitement/kpi_builder_kpi_management.php?action=delete&family="+family+"&id_kpi="+id_kpi+"&product="+product+"'");
			}
	} else {
		alert('You must choose a KPI to be deleted');
	}
}


function delete_formula()
{
	if (document.formulaire.generic_counter.value!="")
	{
		if (confirm('Delete this Formula ?'))
		{
			document.formulaire.action+='?action=delete';
			document.formulaire.submit();	// if no critical error, submit the form
		}
	} else {
		alert('You must choose a Formula to be deleted');
	}
}


function add_raw_data(raw_data,group_table)
{
	//group_table is not used any more in this function. Kept for compatibility
	document.formulaire.zone_formule_numerateur.value+=raw_data;
}

/**
 * Affiche l'équation de la formule en cours
 * 02/07/2010 OJT : Correction Bz14796
 * 23/07/2010 OJT : Correction Réopen BZ14796
 */
function affiche_equation(generic_counter_name, generic_counter_numerateur, id_generic_counter, family,kpi_comment,kpi_label,pourcentage)
{
	// maj 25/11/2009 MPR : On remplace les formules affichées par les formules réelles pour erlangb
	value = generic_counter_numerateur;
	var exp1 = new RegExp("erlangb\(.*\)","g");
	search = exp1.test(value);

	if( search )
    {
		document.formulaire.zone_formule_numerateur.value = value.replace('\\','');
        document.formulaire.ErlangB.disabled = false;
        document.formulaire.ErlangB.title = '';
	}
    else
    {
        // Pour un KPI n'utilisant pas ErlangB doit on désactivé le bouton
        // 13/10/2010 OJT : Correction bz18414, ajour d'un test sur ErlangBDefValue
        if( document.formulaire.ErlangBDefValue )
        {
            if( document.formulaire.ErlangBDefValue.value.length > 0 )
            {
                // On desactive le bouton et on remet le title
                document.formulaire.ErlangB.disabled = true;
                document.formulaire.ErlangB.title = document.formulaire.ErlangBDefValue.value;
            }
            else
            {
                document.formulaire.ErlangB.disabled = false;
                document.formulaire.ErlangB.title = '';
            }
        }
		document.formulaire.zone_formule_numerateur.value=generic_counter_numerateur;
	}

	document.formulaire.generic_counter.value=generic_counter_name;
	// parent.kpi_builder.formulaire.zone_formule_numerateur.value=generic_counter_numerateur;
        // 16/08/2010 NSE supprimé pour que l'affichage d'un Kpi sauvegardé du Formula Builder : un peu tordu de faire un if avant si on écrase systématiquement, non ?
	// document.formulaire.zone_formule_numerateur.value=id_generic_counter;
        // 29/09/2010 NSE bz 18159 : mise à jour du champ zone_id_generic_counter
        document.formulaire.zone_id_generic_counter.value=id_generic_counter;

	//modif 14/05/2007 gwénaël
		// le champ 'family_group_table_name' n'existe pas c'est qu'on est sur la page  New Formula de Query Builder donc tous les champs suivant n'existe pas donc on quitte la fonction
	if( document.formulaire.family_group_table_name == undefined) return;



	document.formulaire.family_group_table_name.value=family;
	document.formulaire.comment_kpi.value=kpi_comment;
	document.formulaire.label_kpi.value=kpi_label;

	if (pourcentage==1){
		document.formulaire.pourcentage.checked=true;
	}else{
		document.formulaire.pourcentage.checked=false;
	}

	// maj 14/04/2008 : benjamin : ajout de la (des)activation de la checkbox percentage. BZ5096
	// on verifie si la checkbox correspondant au kpi est disabled
    // 02/09/2010 OJT : Correction bz17328 pour DE Firefox, gestion des iFrame en JS
    var kpiListIFrame = window.parent.document.getElementById('kpi_list');
    kpiListIFrame = kpiListIFrame.contentWindow.document || kpiListIFrame.contentDocument;
    if (kpiListIFrame.zeform.elements['on_off_'+id_generic_counter].disabled) {
		// la checkbox est disabled -> on disable les boutons et input type=text du formulaire d'edition de kpi
		document.formulaire.generic_counter.disabled = true;
		document.formulaire.label_kpi.disabled = true;
		document.formulaire.comment_kpi.disabled = true;
		document.formulaire.drop_kpi.disabled = true;
		document.formulaire.reset_kpi_button.disabled = true;
		document.formulaire.save.disabled = true;
		document.formulaire.zone_formule_numerateur.disabled = true;
		document.formulaire.pourcentage.disabled = true;
        document.formulaire.ErlangB.disabled = true;

	} else {
		// la checkbox est enabled -> on enable les boutons et input type=text du formulaire d'édition de kpi
		document.formulaire.generic_counter.disabled = false;
		document.formulaire.label_kpi.disabled = false;
		document.formulaire.comment_kpi.disabled = false;
		document.formulaire.drop_kpi.disabled = false;
		document.formulaire.reset_kpi_button.disabled = false;
		document.formulaire.save.disabled = false;
		document.formulaire.zone_formule_numerateur.disabled = false;
		document.formulaire.pourcentage.disabled = false;
	}

	// maj 14/04/2008 Benjamin : application d'un readOnly plutôt qu'un disabled pour permettre la mise à jour d'un KPI existant.BZ5096
	// 19/03/2008 - Modif. benoit : correction du bug 5096. Lors de l'edition d'un kpi précedemment crée, on désactive le champ name de manière à ne pas pouvoir modifier celui-ci
	if (generic_counter_name != "")
	{
		document.formulaire.generic_counter.readOnly = true;
		document.formulaire.generic_counter.style.color = "#898989";
	}
}


//un peu différent de la fonction Dreamweaver car cela permet d'ajouter des paramètres à l'URL venant de champs d'un formulaire

function jumpMenu(targ,selObj,restore)
{ //v3.0
	eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"&generic_counter="+formulaire.generic_counter.value+"&formule="+formulaire.zone_formule.value+"'");
	if (restore) selObj.selectedIndex=0;
}

function reset_kpi()
{
	document.formulaire.zone_formule_numerateur.value="";	// delete the KPI formula
	document.formulaire.zone_formule_numerateur.disabled = false;
	// maj 14/04/2008 Benjamin : reset du nom uniquement s'il s'agit d'un nouveau KPI BZ5096
	if(!document.formulaire.generic_counter.readOnly) document.formulaire.generic_counter.value="";	// delete the KPI name
	document.formulaire.generic_counter.disabled = false;
	document.formulaire.drop_kpi.disabled = false;
	document.formulaire.reset_kpi_button.disabled = false;
	document.formulaire.save.disabled = false;

	//formulaire.reset();

	// modif 14/05/2007 Gwénaël
		// si le champ label_kpi n'existe pas, c'est qu'on est sur la page New Formuale du Query Builder donc les champs suivants n'existe pas
	if( document.formulaire.label_kpi != undefined ) {
		document.formulaire.label_kpi.value="";	// delete the KPI label
		document.formulaire.label_kpi.disabled = false;
		document.formulaire.comment_kpi.value="";	// delete theKPI comment
		document.formulaire.comment_kpi.disabled = false;
	}
}

// ajout 23/10/2008 BBX : ajout d'une fonction new_kpi. BZ 7612
function new_kpi()
{
	document.formulaire.generic_counter.readOnly = false;
	document.formulaire.zone_formule_numerateur.readOnly = false;
	document.formulaire.comment_kpi.readOnly = false;
	document.formulaire.label_kpi.readOnly = false;
	document.formulaire.pourcentage.readOnly = false;

	document.formulaire.generic_counter.disabled = false;
	document.formulaire.zone_formule_numerateur.disabled = false;
	document.formulaire.comment_kpi.disabled = false;
	document.formulaire.label_kpi.disabled = false;
	document.formulaire.pourcentage.disabled = false;

	document.formulaire.generic_counter.value = '';
	document.formulaire.zone_formule_numerateur.value = '';
	document.formulaire.comment_kpi.value = '';
	document.formulaire.label_kpi.value = '';
	document.formulaire.pourcentage.checked = false;

	document.formulaire.drop_kpi.disabled = false;
	document.formulaire.reset_kpi_button.disabled = false;
	document.formulaire.save.disabled = false;

    // Test si le bouton ErlangB doit être grisé
    if( document.formulaire.ErlangBDefValue.value.length > 0 ){
            document.formulaire.ErlangB.disabled = true;
            document.formulaire.ErlangB.title = document.formulaire.ErlangBDefValue.value;
    }
}

function is_a_valid_kpi_label(kpi_label){
	var valid=false;
	var reg_ex1 = new RegExp('^[^"]+$');
	var reg_ex2 = new RegExp('^[\\s]+$');
	if (kpi_label.match(reg_ex1) && !kpi_label.match(reg_ex2)) {
		valid=true
	}
	return valid;
}

function save_kpi() {
	// this function is meant to execute some JavaScript code before the
	// submission of the form (which will execute some PHP code)
	// an example of useful JavaScript code to execute is a check on the
	// syntax of the equation
	//var error_01=(formulaire.zone_formule_numerateur.value=="")? true : false;	// kpi formula is empty
	//var error_02=(formulaire.generic_counter.value=="")? true : false;	// kpi name is empty

	if(document.formulaire.zone_formule_numerateur.value=="") { // kpi formula is empty
		alert("You must enter a formula.");
	} else if(document.formulaire.generic_counter.value=="")	{ // kpi name is empty
		alert("You must enter a name for the KPI.");
	} else if(!is_a_valid_kpi_label(document.formulaire.label_kpi.value)) {
		alert("KPI label not accepted.");
	} else {
		document.formulaire.submit();	// if no critical error, submit the form
	}
}

function save_kpi_formula() {
	// this function is meant to execute some JavaScript code before the
	// submission of the form (which will execute some PHP code)
	// an example of useful JavaScript code to execute is a check on the
	// syntax of the equation
	//var error_01=(formulaire.zone_formule_numerateur.value=="")? true : false;	// kpi formula is empty
	//var error_02=(formulaire.generic_counter.value=="")? true : false;	// kpi name is empty

	if(document.formulaire.zone_formule_numerateur.value=="") { // kpi formula is empty
		alert("You must enter a formula.");
	} else if(document.formulaire.generic_counter.value=="")	{ // kpi name is empty
		alert("You must enter a name for the KPI.");
	} else {
		document.formulaire.submit();	// if no critical error, submit the form
	}
}

//fontion qui recopie le nom du KPI dans le label si le label est vide
function copy_name_to_label() {
	if(document.formulaire.label_kpi.value=="") { // kpi formula is empty
		document.formulaire.label_kpi.value=document.formulaire.generic_counter.value;
	}
}

function showUploadDownload ()
{
	if ( document.getElementById('tableUploadDownload').style.display == 'none' )
	{
		document.getElementById('tableUploadDownload').style.display = 'block';
		document.getElementById('imgUploadDownload').src = document.getElementById('imgUploadDownload').src.replace(/tri.gif/,"tridown.gif");
	}
	else
	{
		document.getElementById('tableUploadDownload').style.display = 'none';
		document.getElementById('imgUploadDownload').src = document.getElementById('imgUploadDownload').src.replace(/tridown.gif/,"tri.gif");
	}
}