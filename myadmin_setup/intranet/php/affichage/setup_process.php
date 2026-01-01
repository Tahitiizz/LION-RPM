<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	- 09:06 25/01/2008 Gwénaël : modif pour la récupération du paramètre client_type

	- maj 13/03/2008, benoit : correction du bug 4099
	- maj 19/03/2008, benoit : en fonction du type de message, on change le style d'affichage de celui-ci
*
*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?
/*
*	16/06/2006 - Copyright Acurio
*
*	Composant de base version cb_1.2.0.2p
*
*	Parser version gb_1.0.0b
*/
?>
<?php
/**
 * Dans cette page, on gère les process  (ce qui se trouve dans sys_definition_master

	- maj  22/05/2006 sls : ajout de boutons permettant de faire un lancement manuel (avec plein d'XMLHttpRequest)
	- maj  23/05/2006 sls : correction d'une erreur javascript qui arrivait quand on était en client_type='client'
	- maj 08 06 2006 christophe : je vire les fonds blanc sur le titre Manual launch et sur l'image de lancement compute
	- maj 28 06 2006 christophe : Le bouton save est affiché tout le temps quel que soit le type de client connecté.

 */
session_start();
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/environnement_donnees.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "php/environnement_nom_tables.php");
include_once($repertoire_physique_niveau0 . "php/menu_contextuel.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");

$comeback=$PHP_SELF;
session_register("comeback");

?>
<html>
<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" />
<script src="<?=$niveau0?>js/myadmin_omc.js"></script>
<script src="<?=$niveau0?>js/gestion_fenetre.js"></script>
<script src="<?=$niveau0?>js/xmlhttp.js"></script>
<?php
	// 13/03/2008 - Modif. benoit : inclusion de la librairie Prototype.js
?>
<script type="text/javascript" src="<?=$niveau0?>js/prototype/prototype.js"></script>

</head>
<body leftmargin="0" topmargin="0">

<table width="550" align="center" cellpadding="3" cellspacing="3">
<tr>
		<td align="center"><img src="<?=$niveau0?>images/titres/process_setup_interface.gif"/></td>
	</tr>

<tr>
<td>
	<table class="tabPrincipal">
		<tr>
			<td class="texteGris">

<?

	// modif 09:06 25/01/2008 Gwénaël
		// modif pour la récupération du paramètre client_type
	$client_type = getClientType($_SESSION['id_user']);

	//
	// 1. On cree la structure logique du formulaire
	//

	// Initialize the phpObjectForms class
	require_once $repertoire_physique_niveau0."class.phpObjectForms/lib/FormProcessor.class.php";
	$fp = new FormProcessor($repertoire_physique_niveau0."class.phpObjectForms/lib/");
	$fp->importElements(array("FPButton", "FPCheckBox", "FPHidden", "FPPassword", "FPRadio", "FPSelect", "FPText", "FPTextField", "extra/FPSplitSelect"));
	$fp->importLayouts(array("FPColLayout", "FPRowLayout", "FPGridLayout"));
	$fp->importWrappers(array( "FPLeftTitleWrapper" ));
	$fp->importWrappers(array( "FPRightTitleWrapper" ));
	$leftWrapper = new FPLeftTitleWrapper(array());
	$rightWrapper = new FPRightTitleWrapper(array(
		"table_title_cell_width" => "60",
		"table_field_cell_width" => "40",
		"table_padding" => 1,
		));

	// Create the form object

	// 13/03/2008 - Modif. benoit : ajout d'un id au formulaire et désactivation de l'action au submit

	$myForm = new FPForm(array(
	    // "title" => 'Connexion Setup Interface',
		"id" => 'formulaire',
	    "name" => 'formulaire',
	    //"action" => 'setup_process_update.php',
		"display_outer_table" => true,
		"table_align" => 'center',
		"enable_js_validation" => true,
	));

	// we fetch all the process
	$query = "SELECT * FROM sys_definition_master where visible=1 order by ordre ";
	$resultat = pg_query($database_connection, $query);
	$nb_process = pg_num_rows($resultat);
	$master_ids = array();

	// We create the form elements

		// On a besoin d'une grille pour la suite
		$grille = new FPGridLayout(array(
			"table_width" => "100%",
			"table_padding" => 5,
			"cell_valign" => "middle",
			"columns" => 7,
			// "cell_width" => array('3%','30%','20%','15%','10%','5%','5%','10%','2%'),
			"id" => "setup_connection"
		));


		// on affiche l'en-tête du tableau (seulement si on a des process)
		if ($nb_process) {

			$grille->addElement(new FPText(array("text" =>'')));
			$grille->addElement(new FPText(array("text" =>'<div align="center" class="texteGrisBold">Name</div>')));
			$grille->addElement(new FPText(array("text" =>'<div align="center" class="texteGrisBold">Time period</div>')));
			$grille->addElement(new FPText(array("text" =>'<div align="center" class="texteGrisBold">Offset</div>')));
			$grille->addElement(new FPText(array("text" =>'<div align="center" class="texteGrisBold">On</div>')));
			if ($client_type == 'customisateur') {
				$grille->addElement(new FPText(array("text" =>'&nbsp;&nbsp;&nbsp;')));
				$grille->addElement(new FPText(array("text" =>'<div align="center" class="texteGrisBold" style="background:;padding:5px;">Manual Launch</div>')));
			} else {
				$grille->addElement(new FPText(array("text" =>'')));
				$grille->addElement(new FPText(array("text" =>'')));
			}
		}

		$form_elements_utps_select = array();
		$form_elements_utps_hour = array();
		$form_elements_utps_minutes = array();
		$form_elements_offset_hour = array();
		$form_elements_offset_minutes = array();
		$form_layouts_utps = array();
		$form_layouts_offset = array();

		// On boucle sur tous les process
		for ($i=0;$i<$nb_process;$i++) {
			$row = pg_fetch_array($resultat, $i);

			$grille->addElement(new FPHidden(array(
				"name" => 'master_id'.$i,
				"value" => $row['master_id']
			)));

			$grille->addElement(new FPText(array("text" => "<span class=texteGris>".$row["master_name"].'</span>&nbsp;&nbsp;')));

			/*
			$grille->addElement(new FPTextField(array(
				"name" => 'utps'.$i,
				"title" => 'UTPS',
				"value" => $row["utps"],
				"size" => 15,
				"valid_RE" => FP_VALID_INTEGER,
				"css_class" => 'iform',
			)));
			*/


			// select menu for utps
				// on selectione l'option qu'il faut
				if ($row['utps'] == '1440') {
					$utps_selected = 'D';
				} elseif ($row['utps'] == '60') {
					$utps_selected = 'H';
				} else {
					$utps_selected = 'O';
				}
				// on cree le menu
				$form_elements_utps_select[$i] = new FPSelect(array(
					"name" => 'utps_select'.$i,
					// "title" => 'Your Country',
					"multiple" => false,
					"options" => array('D'=>'Daily','H'=>'Hourly','O'=>'Other'),
					"selected" => array($utps_selected),
					// "css_style" => 'width:200px;',
	                // "wrapper" => &$leftWrapper,
	   			));

			// utps : minutes
			$form_elements_utps_hours[$i] = new FPTextField(array(
				"name" => 'utps_h'.$i,
				"title" => '<span class=texteGris>h</span>',
				"value" => 0,
				"size" => 2,
				"max_value" => 2,
				"valid_RE" => FP_VALID_INTEGER,
				"css_class" => 'texteGris',
				"css_style" => 'width:30px;text-align:right;',
				"wrapper" => &$rightWrapper,
			));
			$form_elements_utps_minutes[$i] = new FPTextField(array(
				"name" => 'utps_mn'.$i,
				"title" => '<span class=texteGris>mn</span>',
				"value" => $row["utps"],
				"size" => 4,
				"max_value" => 4,
				"valid_RE" => FP_VALID_INTEGER,
				"css_class" => 'texteGris',
				"css_style" => 'width:30px;text-align:right;',
				"wrapper" => &$rightWrapper,
			));

			// on ajoute UTPS dans la grille via un rowlayout
			$form_layout_utps[$i] = new FPRowLayout(array(
				"table_align" => "center",
				"table_padding" => 0,
				"table_width" => 180,
				"elements" => array(
					$form_elements_utps_select[$i],
					new FPText(array("text"=>'&nbsp;&nbsp;&nbsp;')),
					$form_elements_utps_hours[$i],
					$form_elements_utps_minutes[$i],
				)
		    ));
			$grille->addElement($form_layout_utps[$i]);


			// offset : les heures
			$form_elements_offset_hour[$i] = new FPTextField(array(
				"name" => 'offset_h'.$i,
				"title" => '<span class=texteGris>h</span>',
				"value" => 0,
				"size" => 2,
				"valid_RE" => FP_VALID_INTEGER,
				"css_class" => 'texteGris',
				"css_style" => 'width:30px;text-align:right;',
				"wrapper" => &$rightWrapper,
			));
			// offset : les minutes
			$form_elements_offset_minutes[$i] = new FPTextField(array(
				"name" => 'offset_mn'.$i,
				"title" => '<span class=texteGris>mn</span>',
				"value" => $row["offset_time"],
				"size" => 2,
				"valid_RE" => FP_VALID_INTEGER,
				"css_class" => 'texteGris',
				"css_style" => 'width:30px;text-align:right;',
				"wrapper" => &$rightWrapper,
			));

			// on ajoute Offset dans la grille via un rowlayout
			$form_layout_offset[$i] = new FPRowLayout(array(
				"table_align" => "center",
				"table_padding" => 0,
				"table_width" => '100',
				"elements" => array(
					$form_elements_offset_hour[$i],
					$form_elements_offset_minutes[$i]
				)
		    ));
			$grille->addElement($form_layout_offset[$i]);

			// on_off
			$grille->addElement(new FPCheckbox(array(
				"name" => 'on_off'.$i,
				"value" => 1,
				"css_class" => 'texteGris',
				"checked" => ($row['on_off'])
			)));


			// manual launch
			if ($client_type == 'customisateur') {
				/* ancienne check box
				$grille->addElement(new FPCheckbox(array(
					"name" => 'auto'.$i,
					"value" => 't',
					"css_class" => 'texteGris',
					"checked" => ($row['auto'] == 't')
				)));
				*/
				// nouveau bouton
				if ($row['auto'] == 't') {
					$str = "<img src='".$niveau0."images/icones/bt_compute.gif' border='0' width='20' height='20' />";
				} else {
					$str = "<a href='#launch_process($i);' onclick='launch_process($i); return false;'><img src='".$niveau0."images/icones/bt_play1.gif' border='0' width='20' height='20' /></a>";
				}
				$str = "<div id='launch_$i' align='center' style='background:;padding-top:10px;padding-bottom:10px;'>$str</div>";
				$grille->addElement(new FPText(array("text" => '')));
				$grille->addElement(new FPText(array("text" => $str)));
			} else {
				$grille->addElement(new FPText(array("text" => '')));
				$grille->addElement(new FPText(array("text" => '')));
			}
		}

		// Hidden : nb_process
		$form_element_nb_process = new FPHidden(array(
			"name" => 'nb_process',
			"value" => $nb_process
		));

		// BUTTON : Save
		//if ($client_type == 'customisateur') {
		
		// 13/03/2008 - Modif. benoit : suppression du bouton de submit et remplacement de celui-ci par un simple bouton appelant une fonction JS de maj des process via Ajax

		$grille->addElement(new FPText(array("text" =>'<td align="center" colspan="6"><input type="button" id="save_btn" name="save_btn" value="Save" onClick="updateProcess()" class="bouton"/></td>')));
		
		/*$form_element_save_button = new FPButton(array(
			"submit" => true,
			"name" => 'Submit',
			"css_class" => 'bouton',
			"title" => 'Save'
		));*/

		/*} else {
			$form_element_save_button = new FPText(array("text" => ''));
		}*/

	//
	// 2. On termine le layout du formulaire
	//


	// on a besoin d'ajouter un row layout pour center le bouton de validation
	$form_layout_submit_buttons = new FPRowLayout(array(
		"table_align" => "center",
		"table_padding" => 20,
		"elements" => array($form_element_nb_process,$form_element_save_button)
    ));

	// on regroupe tous les éléments du formulaire
	$myForm->setBaseLayout(new FPColLayout(array(
		"table_padding" => 0,
		"table_cellspacing" => 2,
		"element_align" => "center",
		"elements" => array($grille,$form_layout_submit_buttons)
	)));


	// on affiche le formulaire
	$myForm->display();

	?>
			</td>
		</tr>
	</table>
</td>
</tr>
</table>

<script language="JavaScript">
<!--

niveau0 = "<?=$niveau0?>";
client_type = "<?=$client_type?>";

//
// javascript qui automatise certains comportements du formulaire
//

nb_rows = document.formulaire.nb_process.value;

// la selection du menu UTPS change la valeur des minutes (quand on choisit Daily et Hourly)
function check_select_utps_all() {
	var i;
	for (i=0;i<nb_rows;i++) {
		if (document.formulaire.elements['utps_select'+i+'[]'].options[0].selected) {
			document.formulaire.elements['utps_h'+i].value = '24';
			document.formulaire.elements['utps_mn'+i].value = '0';
		}
		if (document.formulaire.elements['utps_select'+i+'[]'].options[1].selected) {
			document.formulaire.elements['utps_h'+i].value = '1';
			document.formulaire.elements['utps_mn'+i].value = '0';
		}
	}
	return true;
}

// on associe la fonction au onChange de chaque menu select
for (i=0;i<nb_rows;i++) {
	document.formulaire.elements['utps_select'+i+'[]'].onchange = check_select_utps_all;
}


// quand on blur sur le champ minutes de utps, si la valeur est >60,
// on enlève les heures pour les passer dans le champ utps_h
function check_utps_mn_all() {
	var i;
	for (i=0;i<nb_rows;i++) {
		if (document.formulaire.elements['utps_mn'+i].value > 59) {
			document.formulaire.elements['utps_h'+i].value =
				parseInt(document.formulaire.elements['utps_h'+i].value)
				+ Math.floor(document.formulaire.elements['utps_mn'+i].value / 60);
			document.formulaire.elements['utps_mn'+i].value %= 60;
		}
		// on verifie aussi que la valeur globale (heure + minutes) d'utps n'est pas nulle
		if (!parseInt(document.formulaire.elements['utps_mn'+i].value) && !parseInt(document.formulaire.elements['utps_h'+i].value)) {
			check_select_utps_all();
		}
		// on re-verifie
		if (!parseInt(document.formulaire.elements['utps_mn'+i].value) && !parseInt(document.formulaire.elements['utps_h'+i].value)) {
			document.formulaire.elements['utps_mn'+i].value = 1;
			alert('The time period cannot be null.');
			document.formulaire.elements['utps_mn'+i].focus();
		}

	}
	return true;
}

// on associe la fonction au onBlur de chaque utps_mn et de chaque utps_h
for (i=0;i<nb_rows;i++) {
	document.formulaire.elements['utps_mn'+i].onblur = check_utps_mn_all;
	document.formulaire.elements['utps_h'+i].onblur = check_utps_mn_all;
}


// quand on blur sur le champ minutes de offset, si la valeur est >60,
// on enlève les heures pour les passer dans le champ offset_h
function check_offset_mn_all() {
	var i;
	for (i=0;i<nb_rows;i++) {
		if (document.formulaire.elements['offset_mn'+i].value > 59) {
			document.formulaire.elements['offset_h'+i].value =
				parseInt(document.formulaire.elements['offset_h'+i].value)
				+ Math.floor(document.formulaire.elements['offset_mn'+i].value / 60);
			document.formulaire.elements['offset_mn'+i].value %= 60;
		}
	}
	return true;
}

// on associe la fonction au onBlur de chaque offset_mn
for (i=0;i<nb_rows;i++) {
	document.formulaire.elements['offset_mn'+i].onblur = check_offset_mn_all;
}

// cette fonction lance le process
function launch_process(i) {
	if (!confirm('Are you sure you want to launch that process?')) return false;
	// on lance le http
	url = 'setup_process_launch.php?i='+i+'&master_id='+document.formulaire.elements['master_id'+i].value;
	http.open("GET", url, true);
	http.onreadystatechange = handleProcessResponse;
	http.send(null);
	return false;
}
// cette fonction gere la reponse issue du lancement de process
function handleProcessResponse() {
	if (http.readyState == 4) {
		response = http.responseText;
		if (response.slice(0,8) == 'launched') {
			// on change le bouton
			var btn_i  = response.slice(9);
			document.getElementById('launch_'+btn_i).innerHTML = '<img src="'+niveau0+'images/icones/bt_compute.gif" border="0" width="20" height="20" />';
			// on lance le timer qui verifie l'etat des process
			process_check('on');
		}else{
			alert("Error : the process failed to launch : "+response);
		}
	}
}


// cette fonction lance ou arrette la consultation des process toutes les 5 sec
var processChecker;
function process_check(a) {
	if (a == 'on') {
		window.clearInterval(processChecker);
		processChecker = window.setInterval("do_process_check();",5000);
	}
	if (a == 'off')
		window.clearInterval(processChecker);
}

// on va chercher la liste des process en cours via http
function do_process_check() {
	url = 'setup_process_check.php';
	http.open("GET", url, true);
	http.onreadystatechange = handle_do_process_check_response;
	http.send(null);
	return false;
}
// on analyse la réponse
function handle_do_process_check_response() {
	if (http.readyState == 4) {
		response = http.responseText;
		if (response == 'no running process found') {
			process_check('off');
		}
		// on scanne les process dans le formulaire
		f = document.forms['formulaire'];
		// on prend tous les champs input du formulaire
		inputs=f.getElementsByTagName('input');
		for (var i=0; i<inputs.length; i++) {
			one_input = inputs[i];
			// on ne garde que les input type="hidden"
			if (one_input.getAttribute("type") == 'hidden') {
				input_name = one_input.getAttribute("name");
				// on ne garde que ceux qui s'appelle name="master_idXXX"
				if (input_name.slice(0,9) == 'master_id') {
					// là on a un process
					input_i = input_name.slice(9);
					input_master_id = one_input.getAttribute("value");
					// on recupère le DIV correspondant
					corresponding_div = document.getElementById('launch_'+input_i);
					// on regarde la 1ere image se trouvant dans le div
					corresponding_img = corresponding_div.getElementsByTagName('img')[0];
					if (response.indexOf(','+input_master_id+',') != -1) {
						// ce process court toujours
						// on verifie que le contenu du div est bien le bouton bt_compute.gif
						if (corresponding_img.getAttribute("src") != niveau0+'images/icones/bt_compute.gif') {
							corresponding_div.innerHTML = '<img src="'+niveau0+'images/icones/bt_compute.gif" border="0" width="20" height="20" />';
						}
					} else {
						// ce process ne court plus
						// on verifie que le contenu du div est bien le bouton bt_play1.gif
						if (corresponding_img.getAttribute("src") != niveau0+'images/icones/bt_play1.gif') {
							corresponding_div.innerHTML = "<a href='#launch_process("+input_i+");' onclick='launch_process("+input_i+"); return false;'><img src='"+niveau0+"images/icones/bt_play1.gif' border='0' width='20' height='20' /></a>";
						}
					}
				}
			}
		}
	}
}



// initialisation du formulaire
check_offset_mn_all();
check_utps_mn_all();
if (client_type == 'customisateur') process_check('on');

// 13/03/2008 - Modif. benoit : ajout de la fonction 'updateProcess()' permettant de mettre à jour les process via l'appel du script php 'setup_process_update.php'

function updateProcess(){
	var formulaire = document.formulaire;
	formulaire.id = 'formulaire';

	var params = Form.serialize('formulaire');

	var url = 'setup_process_update.php';
	var myAjax = new Ajax.Request(
		url,
		{
			method: 'post',
			postBody: params,
			onComplete: updateProcessCallback
		});
}

// 13/03/2008 - Modif. benoit : ajout de la fonction 'updateProcessCallback()' appelée lors du retour du script php et indiquant l'etat de la maj

function updateProcessCallback(data){

	var response = eval('(' + data.responseText + ')');

	if ($('update_info') == null)
	{
		var tableRef = $('setup_connection');

		// Ajout de la ligne

		var newRow = tableRef.insertRow(0);
		newRow.id = "update_info";
		newRow.align = "center";
			
		// Ajout de la colonne

		newRow.insertCell(0);
		var newCell = newRow.insertCell(0);
		newCell.colSpan = "7";
		newCell.className = "texteGris";
	}
	else
	{
		var newCell = $('update_info').childNodes[0];
		newCell.innerHTML = "";
	}

	// 19/03/2008 - Modif. benoit : en fonction du type de message, on change le style d'affichage de celui-ci

	if (response.message_type == "error")
	{
		newCell.style.color = "red";
		newCell.innerHTML = response.message_alert;
	}
	else
	{
		newCell.innerHTML = '<img src="<?=$niveau0?>/images/icones/i.gif" style="vertical-align:bottom"/>&nbsp;&nbsp;'+response.message_alert;
	}
}

// -->
</script>

</body>
</html>
