<?
/*
 *  @cb5.1.6.23
 * 
 * 19/06/2012 NSE bz 27674 : suppression de la méthode check_uncheck_all_NA() trop lente 
 */
?>
<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	- maj 08:31 16/11/2007 Gwénaël : ajout des champs nombre d'itération et période

	- maj 11/03/2008, benoit : correction du bug 3819
	- maj 12/03/2008, benoit : correction du bug 3819
	- maj 17/03/2008, benoit : utilisation des messages en base pour les exclusions horaires (correction du bug 5400)
	
	23/07/2009 GHX
		- Correction du BZ 10511 / 10512 / 10513
			-> Suppression des accents dans les commentaires JS
*
*/
?>
<?
/*
*	@cb30000@
*
*	30/07/2007 - Copyright Acurio
*
*	Composant de base version cb_3.0.0.00
*
*
*	- 30/07/2007 jeremy : ajout de tests dans la fonction JS check_final afin de vérifier que le nom saisie par l'admin n'éxiste pas déjà pour le type
*				d'alarme courant, toutes familles confondue
*
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- 10/07/2007 christophe : initialisation du chemin vers les fichiers appelés via des requêtes ajax.
*	- 09/07/2007 christophe : intégration de la sélection des NA.
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
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*/
?>
<?
/*
*	@cb1300_iu2000b_pour_cb2000b@
*
*	19/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?php
/*

	- maj 27/02/2007, benoit : ajout du parametre 'max_size' à la fonction 'getFieldValue()' afin de limiter le   nombre de caracteres du label des raws/kpis des selects

 */

session_start();

include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");
include_once($repertoire_physique_niveau0 . "intranet_top.php");
include_once($repertoire_physique_niveau0 . "php/edw_function.php");
include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");
include_once($repertoire_physique_niveau0 . "class/setup_alarm.class.php");

// - 09/07/2007 christophe : intégration de la sélection des NA.
include_once($repertoire_physique_niveau0 . "class/genDashboardNaSelection.class.php");

// si la famille n'est pas définie, on arrive sur une page permettant de le faire

// on récupere les variables
// (par défaut, alarm_type est à 'alarm_static')
$product	= $_GET['product'];
$family	= $_GET['family'];
$alarm_id	= $_GET['alarm_id'];

if (isset($_GET["alarm_type"]))		$alarm_type = $_GET['alarm_type'];
else							$alarm_type = 'alarm_static';

// on se connecte à la db
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$db = Database::getConnection($product);

// on inclue la classe correspondant au type d'alarme
include_once($repertoire_physique_niveau0 . "class/setup_".$alarm_type.".class.php");

//on enregistre le nom de la table utilisée par ce type d'alarme
$sys_definition_alarm_table = 'sys_definition_'.$alarm_type;

global $niveau0;

?>
<!--  // - 09/07/2007 christophe : intégration de la sélection des NA. -->
<link rel="stylesheet" href="<?=$niveau0?>css/selection_des_na_recherche.css" type="text/css">
<script type="text/javascript" charset="iso-8859-1" src="<?=$niveau0?>js/selection_des_na_recherche.js"></script>
<script type="text/javascript" charset="iso-8859-1" src="<?=$niveau0?>js/selection_des_na.js"></script>
<link rel="stylesheet" href="<?=$niveau0?>css/selection_na.css" type="text/css">


<script type="text/javascript" src="<?=$niveau0?>js/tab-view-ajax.js"></script>
<script type="text/javascript" src="<?=$niveau0?>js/tab-view.js"></script>
<link rel="stylesheet" href="<?=$niveau0?>css/tab-view.css" type="text/css" media="screen">
<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css" >

<script type="text/javascript" charset="iso-8859-1">
	// 10/07/2007 christophe : initialisation du chemin vers les fichiers appeles via des requêtes ajax pour la selection des NA.
	setLinkToAjax('<?=$niveau0."reporting/intranet/php/affichage/"?>'); // cf fichier js/selection_des_na_recherche.js

	var family		= '<?php echo $family; ?>';
	var product	= '<?php echo $product; ?>';
	
	
	// modif 08:33 16/11/2007 Gwen 
		// Creation d'un tableau js pour avoir les valeurs maximum des periodes
	var MAX_PERIOD = {
		hour: <?=get_history($family, 'hour')*24;?>,
		day: <?=get_history($family, 'day');?>,
		week: <?=get_history($family, 'week');?>,
		month: <?=get_history($family, 'month');?>,
		day_bh: <?=get_history($family, 'day');?>,
		week_bh: <?=get_history($family, 'week');?>,
		month_bh: <?=get_history($family, 'month');?>
	};
	var TA_LABEL = {
		hour: 'hours',
		day: 'days',
		week: 'weeks',
		month: 'months',
		day_bh: 'days',
		week_bh: 'weeks',
		month_bh: 'months'
	};
	// Defintion de la XMLHttpRequest en fonction du navigateur

  var strictDocType = false;
	var http;	// Variable js globale du XMLHttpRequest

	if (window.XMLHttpRequest) { // Mozilla, Safari, ...
		http = new XMLHttpRequest();
	} else if (window.ActiveXObject) { // IE
		http = new ActiveXObject("Microsoft.XMLHTTP");
	}

	// Fonction de recherche des valeurs du selecteur de raw counters/kpi
	// La variable fieldType correspond au type de donnees (raw/kpi)
	// La cible correspond a la liste du formulaire qui recevra les donnees
	// La famille est necessaire pour les requêtes de recuperation des raw counters et kpi
	// Avant de lancer la requête, on desactive le champ 'cible' et on affiche le message 'loading...'

	// 11/03/2008 - Modif. benoit : definition de la variable globale '_max_size' utilisee dans la fonction 'handleHttpResponse()'

	var _max_size = 40;

	// 27/02/2007 - Modif. benoit : ajout du parametre 'max_size' a la fonction pour limiter le nombre de caracteres dans les selects
	// 06/02/2009 - maj stephane : ajout du parametre product avant family
	function getFieldValue(fieldType,cible,product,family, max_size){

		_max_size = max_size;
		
 		$(cible).disabled = true;
  		$(cible).options.length = 1;
  		$(cible).options[0].text = "Loading ...";
  		$(cible).options[0].value = "makeSelection";
  
  		var url =  "<?=$niveau0?>php/ajax_select_raw_kpi.php?champ="+fieldType+"&cible="+cible+"&product="+product+"&family="+family+"&max_size="+max_size+"&trunc=0";
  
  		http.open("GET", url, true);
  		http.onreadystatechange = handleHttpResponse;
  		http.send(null);
	}

	// Fonction de traitement des valeurs du filtre retournees depuis 'ajax_select_raw_kpi.php'
	// une fois toutes les donnees integrees, on remplace le message 'loading' par 'make a selection'
	// le champ 'cible' est alors reactive.

	function handleHttpResponse() {
		if (http.readyState == 4) {
			/* les donnees de construction de la liste sont decapsulees de la chaîne
			* |field| est le separateur entre deux options
			*/
			var tableau_liste = http.responseText.split('|column|');
			var maListe = $(tableau_liste[0]);
			
			maListe.options.length = tableau_liste.length-1;
				
			for (i=1; i< tableau_liste.length - 1; i++) {
				tableau_option = tableau_liste[i].split('|field|');
				maListe.options[i].value = tableau_option[0];

				// 11/03/2008 - Modif. benoit : ajout de l'attribut 'label_complete' a l'option du select et troncage du label dans cette fonction plutôt que dans le script php 'ajax_select_raw_kpi.php'

				maListe.options[i].label_complete = tableau_option[1];

				var option_label = tableau_option[1];
				if (option_label.length > _max_size) option_label = option_label.substr(0, _max_size)+"...";

				maListe.options[i].text = option_label;
			}
			maListe.options[0].text='Make a Selection';
			maListe.disabled = false;
			
		}
	}

	// 11/03/2008 - Modif. benoit : ajout de la fonction d'ajout du libelle complet du raw / kpi selectionne

	function showCompleteAdditionalFieldLabel(numero){
		
		if ($('additional_field'+numero)) {
			try {
				// On recupere le label complet du champ additionnel selectionne
				
				var triggerLabel = $('additional_field'+numero).options[$('additional_field'+numero).selectedIndex].label_complete;

				// On supprime l'ancienne ligne d'information sur le label (si elle existe)

				deleteCompleteAdditionalFieldLabel(numero);

				// Si la taille du label est superieure a 52 caracteres, on ajoute l'information sous forme de ligne de tableau en dessous du select

				if (triggerLabel.length > 52){

					var tableRef = $('additional_field_table'+numero);	// tableau du champ additionnel

					// Ajout de la ligne
					var newRow = tableRef.insertRow(3);
					newRow.id = "additional_trigger_complete_label"+numero;
						
					// Ajout de la colonne
					newRow.insertCell(0);
					var newCell = newRow.insertCell(1);
					newCell.colSpan = "2";
					newCell.className = "texteGris";
					newCell.style.fontSize = "8pt";

					// Ajout du label complet dans la nouvelle colonne
					var newText = document.createTextNode(triggerLabel);
					newCell.appendChild(newText);
				}	
			}
			catch (e) {}
		}
	}

	// 12/03/2008 - Modif. benoit : ajout de la fonction permettant de supprimer la ligne d'information sur le label du field long (plus de 52 caracteres) 

	function deleteCompleteAdditionalFieldLabel(numero){
		if ($('additional_trigger_complete_label'+numero) != null)
			$('additional_field_table'+numero).deleteRow(3);
	}

	// on retire le titre de la liste des choix possible de 'cible'
        // 12/08/2010 NSE DE Firefox bz 16876 : remplacement cible.options.remove(0) par cible.options[0].remove
	function remove_choice(cible) {
		if (cible.options[0].value == 'makeSelection')
			cible.options[0].remove;

		// 12/03/2008 - Modif. benoit : creation de la fonction permettant d'afficher le label complet d'un trigger (ou champ) top/worst
		
		if ((cible.getAttribute('id') == "sort_field") || (cible.getAttribute('id') == "trigger_field"))
			showCompleteTriggerTWLabel(cible);
	}


	// 12/03/2008 - Modif. benoit : creation de la fonction permettant d'afficher le label complet d'un trigger (ou champ) top/worst
	function showCompleteTriggerTWLabel(cible) {
		try {
                        // 14/09/2010 BBX
                        // Correction de la lecture du label complet
                        // BZ 17889
                        var champLabel = cible.options[cible.selectedIndex].label_complete;
                        if(typeof(champLabel) == 'undefined')
                            champLabel = cible.options[cible.selectedIndex].readAttribute('label_complete');

			var cible_id = cible.getAttribute('id');

			deleteCompleteTriggerTWLabel(cible_id);

			if (champLabel.length > 40) {
				var tableRef = $('trigger_list');	// tableau

				// Ajout de la ligne
				var row = cible.parentNode.parentNode.rowIndex;
				var newRow = tableRef.insertRow(row+1);
				newRow.id = cible_id+"_complete_label";

				// Ajout de la colonne
				newRow.insertCell(0);
				var newCell = newRow.insertCell(1);
				newCell.colSpan = "2";
				newCell.className = "texteGris";
				newCell.style.fontSize = "8pt";

				// Ajout du label complet dans la nouvelle colonne
				var newText = document.createTextNode(champLabel);
				newCell.appendChild(newText);
			}			
		}
		catch (e){}
	}

	// 12/03/2008 - Modif. benoit : 
	function deleteCompleteTriggerTWLabel(cible_id) {
		if ($(cible_id+'_complete_label') != null) $('trigger_list').deleteRow($(cible_id+'_complete_label').rowIndex);
	}

	// 10/07/2007 christophe :  Permet de mettre-a-jour la selection des NA.
	function updateNaSelection_alarme(select)
	{
		// - 09/07/2007 christophe : integration de la selection des NA : si l'utilisateur choisi une NA, l'icône de selection des NA est visible.
		// On affiche licône de selection des NA si elle n'etait pas deja visible.
		if ( $('icone_selection_des_na').style.display == 'none' )
			$('icone_selection_des_na').style.display = 'block';
		// On mets-a-jour le contenu de la selection des NA avec le niveau d'agregation choisit.
		updateNaSelection( select.options[select.selectedIndex].value );
	}

	// Fonction qui affiche les periodes d'exclusion quand ta = hour ou ta = day
	function getTaExclusion(ta,cible) {
		// alert("setup_alarm_detail.php line:341 : getTaExclusion("+ta+","+cible+")");
		// modif 08:32 16/11/2007 Gwen
			// Mise a jour du tooltip sur le champ periode en fonction de la TA
		setTootlipPeriod(TA_LABEL[ta], MAX_PERIOD[ta]);
		// 12/08/2010 NSE DE firefox bz 16876 avec block, l'affichage est décalé sous Firefox
		if((ta == 'hour') || (ta == 'day') || (ta == 'day_bh'))
			$(cible).style.display = "";
		else
			$(cible).style.display = "none";

		unselect_all_times_exlcusion();
	}

	// On affiche la fenetre d'information regroupant la liste des alarmes
	function hide_hour_exclusion(ta,day,cible){
		if (ta=='hour' && day.checked==1)
			$(cible).style.padding = "20px";
		else
			$(cible).style.padding = "0px";
	}

	// Variables globales qui enregistre les heures exclues pour les jours selectionnes ainsi que la ta selectionnee
	
	var __hour_excluded = new Object();
	var __time_to_sel = new Object();
	var __apply_all_days = new Object();
	var __hour_saved = new Object(); // Si l'utilisateur sauvegarde directement
	__apply_all_days = 0;

	// On sauvegarde l'aggregation temporelle existante
	function save_time_to_sel(time_to_sel) {
		__time_to_sel = time_to_sel;
	}

	/* 
	On ouvre une fenetre temporaire pour selectionner les heures a exclure du jour coche
	*/
	
	function select_hour_exclusion(ta,valeur,contenu) {
		if (ta=='hour' && valeur.checked==1) {
			
			/* 
			ouverture d'une prototype window contenant la liste des heures a exclure pour le jour coche
			*/
			
			Dialog.confirm($(contenu).innerHTML, { 
				className:"alphacube", 
				title:"Exclusion of hour for "+valeur.name,
				width:450,
				zindex:20000,
				top:200,
				buttonClass:"bouton",
			okLabel:"Save", cancelLabel:"Cancel",
			onShow:function(win){
				var id;
				tab_temp = new Object();
				tab_temp2 = new Array();
				tab_temp2 = __hour_excluded[valeur.value];
				if(tab_temp2){
					for(j=0;j<tab_temp2.length;j++){
						tmp = tab_temp2[j].split(":");
						id = tmp[0];
						if(id=='00'){
							id = 0;
						}else{
							if(id<10){
								id_tmp = id.split("0");
								id = id_tmp[1];
							}
						}
						tab_temp[id] = tab_temp2[j];
					}
					for (i=0; i<24; i++){
						if(tab_temp[i]){
							$("hours["+valeur.value+"]["+i+"]").checked = 1;
							$("hour["+valeur.value+"]["+i+"]").value = tab_temp[i];
						}else{
							$("hour["+valeur.value+"]["+i+"]").value = '';
							$("hours["+valeur.value+"]["+i+"]").checked = 0;
						}
					}
				}
			},
			onOk:function(win){
			__hour_excluded[valeur.value] = new Array();
			var cpt;
			cpt = 0;
			for (i=0; i<24; i++) {
				if($("hours["+valeur.value+"]["+i+"]").checked== 1){
					__hour_excluded[valeur.value][i] = $("hours["+valeur.value+"]["+i+"]").value;
					$("hour["+valeur.value+"]["+i+"]").value = $("hours["+valeur.value+"]["+i+"]").value;
				}else{
					__hour_excluded[valeur.value][i] = "";
					$("hour["+valeur.value+"]["+i+"]").value = '';
					cpt++;
				}

			}
			if(cpt==24){// Si aucune heure n'est cochee, on decoche le jour
				valeur.checked = 0;
			}
			$(valeur.value).value = get_tooltip(valeur.value);
			if(__apply_all_days==1){
				var cpt;
				for(i=0;i<7;i++){
					cpt = 0;
					__hour_excluded[i] = new Array();
					$('day['+i+']').checked = 1;
					$('days['+i+']').value = i;
					for(j=0;j<24;j++){
						if($("hours["+valeur.value+"]["+j+"]").checked==1){
							$("hour["+i+"]["+j+"]").value = $("hours["+valeur.value+"]["+j+"]").value;
							__hour_excluded[i][j] = $("hours["+i+"]["+j+"]").value;
							$("hours["+i+"]["+j+"]").checked;
						}else{
							cpt++;
							__hour_excluded[i][j]="";
							$("hour["+i+"]["+j+"]").value = '';
							$("hours["+i+"]["+j+"]").checked=0;
						}
					}
					if(cpt == 24){
						$('day['+i+']').checked = 0;
					}
				}

				// Integration du tooltip en dur (  Dans une boucle for(i=0;i<7;i++)  =>traitement bcp trop long ()plantage de ie )
				
				$('days[0]').value = get_tooltip(0);
				$('days[1]').value = get_tooltip(1);
				$('days[2]').value = get_tooltip(2);
				$('days[3]').value = get_tooltip(3);
				$('days[4]').value = get_tooltip(4);
				$('days[5]').value = get_tooltip(5);
				$('days[6]').value = get_tooltip(6);
				// On reinitialise le apply_all_day a 0
				
				__apply_all_days = 0;
			}
			return true;
			},
			onCancel:function(win){
				var id;
				tab_temp = new Object();
				tab_temp2 = new Array();
				
				tab_temp2 = __hour_excluded[valeur.value];
				if(tab_temp2){
					for(j=0;j<tab_temp2.length;j++){
						tmp = tab_temp2[j].split(":");
						id = tmp[0];
						if(id<10){
							id_tmp = id.split("0");
							id = id_tmp[1];
						}
						tab_temp[id] = tab_temp2[j];
					}
					for (i=0; i<24; i++){
						// On check les hours qui etaient deja cochees
						
						if(tab_temp[i]){
							$("hours["+valeur.value+"]["+i+"]").checked = 1;
						}
					}
				}else{
					for (i=0; i<24; i++) {
						$("hours["+valeur.value+"]["+i+"]").checked== 0;
					}
				}
				
				// Si aucune heure n'est cochee, on decoche le jour
				
				cpt = 0;
				for (i=0; i<24; i++) {
					
					// On decoche toutes les hours cochees
					
					if($("hours["+valeur.value+"]["+i+"]").checked == 1)
						cpt++;
				}
				if(cpt==0){
					valeur.checked=0;
				}
			}
			}
			);
		}
	}

	// La variable globale __apply_all_days est un booleen
	// 	1 = Appliquer les modifications a tous les jours de la semaine
	// 	2 = Appliquer les modifications uniquement au jour selectionne
	
	function apply_all_days(day){
		if( confirm ('The changes will be applied to all days of the week') )
			__apply_all_days = 1;
		else
			__apply_all_days = 0;
	}

	// On Enregistre la valeur des jours selectionnes
	
	function select_day_exclusion(id){
		if($('day['+id+']').checked == 1){
			$('days['+id+']').value = id;
			if($('day_to_exclude')) $('day_to_exclude').value = id;
			for(i=0;i<24;i++){
				$("hour["+id+"]["+i+"]").value = "";
				$("hours["+id+"]["+i+"]").checked = 0;
			}
		}else{
			__hour_excluded[id] = new Array();
			$('days['+id+']').value = '';
			for(i=0;i<24;i++){
				$("hour["+id+"]["+i+"]").value = "";
				$(id).value = get_tooltip(id);
				__hour_excluded[id][i] = "";
			}
		}
	}

	// On coche toutes les heures a exclure du jour selectionne
	
	function check_all(day) {
		var valeur;
		if($('checkall').value==true)
			valeur = false;
		else
			valeur = true;
		for (i=0; i<24; i++) {
			$("hours["+day+"]["+i+"]").checked = valeur;
		}
		$('checkall').value = valeur;
	}

	/**
	 *   MP 29/08/2007 - On decoche toutes les periodes d'exclusion temporelles si l'utilisateur change de ta ( day -> hour / hour -> day )
	 **/
	 
function unselect_all_times_exlcusion(){
   if ($('time_to_sel')) {
  		if( $('time_to_sel').value !== __time_to_sel){
  			// On decoche tous les jours
  			for( d=0; d<7; d++ ){
  				$('day['+d+']').checked = 0;
  				$('days['+d+']').value = '';
  				<?php
  				
  					// 17/03/2008 - Modif. benoit : utilisation des messages en base pour les exclusions horaires (correction du bug 5400)
  				
  					echo "$(d).value = '".__T('A_JS_ALARM_NO_EXCLUDED_HOURS_INFORMATION')."';";
  				?>
  				
				__hour_excluded[d] = new Array();
  				// On decoche toutes les heures
  				for( h=0; h<24; h++ ){
  					__hour_excluded[d][h] = '';
  					$("hours["+d+"]["+h+"]").checked = 0;
  					$("hour["+d+"]["+h+"]").value = '';
  				}
  			}
  		}
	 }
  }

	// MP - 30/08/2007 On recupere toutes les heures de la semaine avant de controler si elles sont toutes cochees
	
	function get_hours_excluded(init){
		for(d=0;d<7;d++){
			if( $('day['+d+']').checked == 1 ){
				__hour_excluded[d] = new Array();
				for( h = 0; h < 24 ; h++ ){
					__hour_excluded[d][h] = $("hour["+d+"]["+h+"]").value;
				}
			}
		}
	}

	// Fonction qui verifie les donnees enregistrees
	//On passe la liste des noms d'alarme pour le type d'alarme courant afin de verifier que le nom saisi n'existe pas
	
	function check_final(list_alarm_name)
    {
        // Contrôle sur la selection des na
        var check_na;
        var cpt;var cpt_h;
        var msg;

        check_na = naSelectionIsOk();
        if(check_na)
        {
            cpt = 0;
            for(i=0;i<7;i++)
            {
                if( $('time_to_sel').value == 'hour' )
                {
                    tab = new Array();
                    tab = __hour_excluded[i];

                    if(tab)
                    {
                        cpt_h=0;
                        for(j=0;j<tab.length;j++)
                        {
                            if(tab[j]!=""){
                                cpt_h++;
                            }
                        }
                        if(cpt_h==24) cpt++;
                    }
                }
                else
                {
                    if( $('day['+i+']').checked==1 )
                        cpt++;
                }
            }
            // Si toutes les checkbox concernees (day ou hour)
            if(cpt==7)
            {
                if($('time_to_sel').value=='hour')
                        msg = "<?=__T('A_JS_ALARM_ALL_HOURS_EXCLUDED')?>";
                else
                        msg = "<?=__T('A_JS_ALARM_ALL_DAYS_EXCLUDED')?>";
                alert(msg);
                return false;

                //30/07/2007 jeremy : Test de doublons du nom d'alarme sur toutes les alarmes d'un même type, toute famille confondu

            }
            else if (list_alarm_name.length>0)
            {
                tab = list_alarm_name;
                //Recuperation du nom saisi dans le formulaire
                new_name = $('alarm_name').value;

                //On parcourt le tableau a la recherche du nom saisie dans la liste creee a partir de la base de donnees
                //Si le nom est trouve, on genere une alerte et l'utilisateur est renvoye au formulaire afin de modifier le nom de l'alarme.

                for (i=0;i<tab.length;i++)
                {
                    if (new_name == list_alarm_name[i]){
                        // Modification messages dans BZ13093
                        alert('Please, choose another name for this alarm as it is already used.');
                        $('alarm_name').focus();
                        return false;
                    }
                }
            } else return true;
        }else return false;
	}
	
	/**
	 * Modifie la valeur de la periode dans les tooltips
	 *
	 *	- ajout 08:32 16/11/2007 Gwen
	 *	- modif 24/12/2007 Gwen :  ajout du nombre de jours correspondant a l'heure
	 *
	 * @param string critical_level
	 * @param string value
	 */
	function setTootlipPeriod ( ta, value ) {
		var msg =  "<?=__T('A_TOOLTIP_ALARM_MAX_PERIOD', 'XXX')?>";
		msg = msg.replace(/XXX/, value+' '+ta);
		
		// modif 24/12/2007
		if ( ta == 'hours' ) {
			msg += ' ('+(value/24)+ ' days)';	
		}
		
		if ( $('period_critical') ) {	
		
		// alarmes statiques & dynamiques	
		
			$('period_critical').onmouseover = function () { popalt(msg); };
			$('period_major').onmouseover = function () { popalt(msg); };
			$('period_minor').onmouseover = function () { popalt(msg); };
		}
		else if ( $('period_') ) { // top worst list
			$('period_').onmouseover = function () { popalt(msg); };
		}
	} // End function setTootlipPeriod
        
        // 19/06/2012 NSE bz 27674 : suppression de la méthode check_uncheck_all_NA() trop lente        
	</script>

<?
	// On initialise la variable de session avec ce qui est enregistré en base.
	$_SESSION["network_element_preferences"] = '';
	$na_selection = new genDashboardNaSelection($family, 'whatever', 'interface_edition_alarme',false,$product);
?>


<?	
	$debug = false;
	if ($debug)
		echo "<div class='debug'>\$alarm = new alarm(family=<strong>$family</strong>, alarm_id=<strong>$alarm_id</strong>, alarm_type=<strong>$alarm_type</strong>, sys_definition_alarm_table=<strong>$sys_definition_alarm_table</strong>, <strong>null</strong>, product=<strong>$product</strong>);</div>";
?>


<table width="550px" align="center">
	<tr>
		<td>
			<? $alarm = new alarm($family, $alarm_id, $alarm_type,$sys_definition_alarm_table, null, $product); ?>
		</td>
	</tr>
</table>

