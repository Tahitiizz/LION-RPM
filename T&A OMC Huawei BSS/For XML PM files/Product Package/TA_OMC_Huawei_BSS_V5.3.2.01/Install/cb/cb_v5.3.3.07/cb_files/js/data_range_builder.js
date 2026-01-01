
// fichier appelé par la page pageframe_range.php
// 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
// 06/09/2010 NSE DE Firefox bz 16865 : Drag&Drop Ko

var structure=new Array(2)
// 06/09/2010 NSE DE Firefox bz 16865 : passage de event en paramètre
function setupDrag(event,id_elem,id_img) {
	var passedData = [id_elem,id_img]
	event.dataTransfer.setData("text", passedData.join(":"))
	event.dataTransfer.effectAllowed = "copy"
	// alert (passedData)
}

// 06/09/2010 NSE DE Firefox bz 16865 : différence IE/Firefox : srcElement/target + text et non Text pour le dataTransfert.getData
function handleDrop(event,family,product) {
    if (document.all){
        var elem = event.srcElement // IE
    }
    else{
        var elem = event.target
    }

	var passedData = event.dataTransfer.getData("text")
	var errMsg = ""

	if (passedData) {
		passedData = passedData.split(":")

		event.dataTransfer.dropEffect = "copy"
		//event.srcElement.innerHTML ="<img id="+passedData[0]+" src=\'"+passedData[1]+"\'>"+passedData[2]+"("+passedData[0]+")"
		structure[0]=passedData[0]
		structure[2]=passedData[2]
		structure[3]=passedData[3]
		structure[1]=elem.id
		
		// [id_img,label_txt,objectclass,id_object]
		update_frame(family,product)
		//alert(structure)
		//alert(id_page)
	}
}

// 06/09/2010 NSE DE Firefox bz 16865 : passage de event en paramètre
function cancelDefault(event) {
	event.dataTransfer.dropEffect = "copy"
	event.returnValue = false
}

function update_frame(family,product) {
	url = "update_pauto_frame_range.php?product="+product+"&family="+family+"&object_class="+structure[2]+"&object_id="+structure[3]+"&pos="+structure[1];
	// alert(url);
	window.location=url;
}

function setEmpty(){
	document.getElementById('page_name').value = ""
}

function verifRange(elem){
	/*var chaine = elem;
	indice = chaine.substring(chaine.length - 1,chaine.length);
	nom = chaine.substring(0,chaine.length - 1);
	var result = 0;
	var val1 = document.getElementById('min_range'+indice).value;
	var val2 = document.getElementById('max_range'+indice).value;
	test = " result = val1 >= val2 ";
	eval (test);
	alert(result);*/
	
	//alert (document.getElementById('min_range'+indice).value + "  "+document.getElementById('max_range'+indice).value);
	/*if(document.getElementById('min_range'+indice).value >= document.getElementById('max_range'+indice).value){
		alert("Invalid range.");
		switch(nom){
			case "min_range" : document.getElementById(chaine).value = 0;
			break;
			case "max_range" : document.getElementById(chaine).value = 100;
			break;
		}
	}*/
}


// maj 16/04/2008 Benjamin : création de la fonction checkRangeConflicts qui vérifie que les range n'entrent pas en conflit. BZ6253
// 21/07/2009 BBX : parseInt => parseFloat afin de prendre en compte les décimaux. BZ 10626
function checkRangeConflicts()
{
	var f = document.getElementById("data_range_form");
	if(f) 
	{
		// Filtre sur les balises input
		var champs = f.getElementsByTagName("input");
		var n=champs.length;
		var tabRanges = new Array();
		// Parcours des champs
		for(var i=0; i<n; i++) 
		{
			if((f.elements['min_range'+i]) && (f.elements['max_range'+i]))
			{
				var min_range = parseFloat(f.elements['min_range'+i].value);
				var max_range = parseFloat(f.elements['max_range'+i].value);
				
				// 21/07/2009 BBX : Test des valeurs. BZ 10626
				if((f.elements['min_range'+i].value.lastIndexOf(',') != -1) || isNaN(min_range)) {
					alert(f.elements['min_range'+i].value+' is not a valid number');
					return false;				
				}
				if((f.elements['max_range'+i].value.lastIndexOf(',') != -1) || isNaN(max_range)) {
					alert(f.elements['max_range'+i].value+' is not a valid number');
					return false;				
				}
				
				for(var j = 0; j<n; j++)
				{
					// Reparcours des champ pour comparer avec le champ en cours
					if((f.elements['min_range'+j]) && (f.elements['max_range'+j]) && (i!=j))
					{
						var min_range_test = parseFloat(f.elements['min_range'+j].value);
						var max_range_test = parseFloat(f.elements['max_range'+j].value);
						
						// 21/07/2009 BBX : Test des valeurs. BZ 10626
						if((f.elements['min_range'+j].value.lastIndexOf(',') != -1) || isNaN(min_range_test)) {
							alert(f.elements['min_range'+j].value+' is not a valid number');
							return false;				
						}
						if((f.elements['max_range'+j].value.lastIndexOf(',') != -1) || isNaN(max_range_test)) {
							alert(f.elements['max_range'+j].value+' is not a valid number');
							return false;				
						}
				
						// On regarde si le range i est en conflit avec un range j
						if(((min_range_test >= min_range) && (min_range_test < max_range)) || ((max_range_test > min_range) && (max_range_test <= max_range)) || ((min_range_test >= min_range) && (max_range_test <= max_range)))
						{
							alert("Range n° "+i+" conflicts with range n° "+j);
							return false;
						}
					}
				}
			}
		}
	}
	return true;
}



// Nombre de range ajoutée par le script javascript.
j = 0;
nb_range_max = 5;

function ajouterRange(nbRangeDefaut)
{
	j++;
	i = nbRangeDefaut + j;	// Index de la partie à ajouter.
	// 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
	txt = "<div id='range"+i+"'>";
	txt += "		<fieldset>";
	txt += "		<legend class='texteGris'>&nbsp;<img src='../../../../images/icones/small_puce_fieldset.gif'/>&nbsp;&nbsp;<b>Range "+i+"</b>&nbsp;</legend>";
	txt += "			<table cellpadding='0' cellspacing='2' align='left'>";
	txt += "			<tr>";
	txt += "				<td class='texteGris'>";
	txt += "					Stroke color :";
	txt += "					<input type='button' name='stroke_color_btn"+i+"' value='' size='16' style='background-color:#000000;' ";
	txt += "						class='hexfield' onMouseOver=\"style.cursor='pointer';\" ";
	txt += "						onclick=\"javascript:ouvrir_fenetre('../../../../php/palette_couleurs_2.php?form_name=myForm&field_name=stroke_color_btn"+i+"&hidden_field_name=stroke_color"+i+"','Palette','no','no',304,100);\" />";
	txt += "					<input type='hidden' name='stroke_color"+i+"' value='#000000'/>";
	txt += "				</td>";
	txt += "				<td>&nbsp;</td>";
	txt += "				<td class='texteGris'>";
	txt += "					Fill color :";
	txt += "					<input type='button' name='fill_color_btn"+i+"' value='' size='16' style='background-color:#FFFFFF;'";
	txt += "						class='hexfield' onMouseOver=\"style.cursor='pointer';\" ";
	txt += "						onclick=\"javascript:ouvrir_fenetre('../../../../php/palette_couleurs_2.php?form_name=myForm&field_name=fill_color_btn"+i+"&hidden_field_name=fill_color"+i+"','Palette','no','no',304,100);\" />";
	txt += "						<input type='hidden' name='fill_color"+i+"' value='#FFFFFF'>";
	txt += "				</td>";
	txt += "				<td>&nbsp;</td>";
	txt += "				<td class='texteGrisPetit'>";
	txt += "					transparency :";
	txt += "					<select style='width:60px;' name='filled_transparence"+i+"'>";
	txt += "						<option value='0.0'>0%</option>";
	txt += "						<option value='0.1'>10%</option>";
	txt += "						<option value='0.2'>20%</option>";
	txt += "						<option value='0.3' selected=selected>30%</option>";
	txt += "						<option value='0.4'>40%</option>";
	txt += "						<option value='0.5'>50%</option>";
	txt += "						<option value='0.6'>60%</option>";
	txt += "						<option value='0.7'>70%</option>";
	txt += "						<option value='0.8'>80%</option>";
	txt += "						<option value='0.9'>90%</option>";
	txt += "						<option value='1'>100%</option>";
	txt += "					</select>";
	txt += "				</td>";
	txt += "			</tr>";
	txt += "			<tr>";
	txt += "				<td class='texteGris'>";
	txt += "					Min range :";
	txt += "					<input type='text' name='min_range"+i+"' id='min_range"+i+"' value='' style='width:40px;font-size:10px;'/>";
	txt += "				</td>";
	txt += "				<td>&nbsp;</td>";
	txt += "				<td class='texteGris'>";
	txt += "					Max range :";
	txt += "					<input type='text' name='max_range"+i+"' id='max_range"+i+"' value='' style='width:40px;font-size:10px;'/>";
	txt += "				</td>";
	txt += "				<td>&nbsp;</td>";
	txt += "			</tr>";
	txt += "		</table>";
	txt += "	</fieldset>";
	txt += "	</div> ";

	document.getElementById('range'+ (i - 1)).innerHTML += txt;
}
