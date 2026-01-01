//***************************************
// Clock JS
// V1.0.5
// 16/09/2008
// BAC - BBX - SLC
//***************************************

// Variables globales
var _winClock = false; // fenêtre
var _ampm = false;

/** Classe Clock

	@params		input_clock
	@params		calendar_input	: element calendrier servant de référence. Si le jour définit par ce calendrier est le jour courant, alors on limite les heures sélectionnables dans la clock à l'heure qu'il est actuellement.
	@params	bool	mode_ampm : false si la clock est en mode 12/24, true si la clock est en mode AM/PM

*/

function Clock(input_clock,calendar_input_id,mode_ampm) 
{
	// alert("Clock("+input_clock+","+calendar_input_id+","+mode_ampm+")");
	// Propriétés
	this.input_clock = input_clock;
	this.selected_date = false;
	if (mode_ampm == true) {
		_ampm = true;
	} else {
		_ampm = false;
	}
	// alert(this.mode_ampm);

	// Créer la fenêtre
	this.create_window = function()
	{
		// Création de l'objet fenêtre
		if(_winClock) return false;
		_winClock = new Window({ 
		className:"alphacube",
		title: 'Select an hour',
		width:135, height:160,
		minWidth:135, minHeight:160,
		resizable:false,
		minimizable:false,
		recenterAuto: false,
		maximizable:false
		});
		// Paramétrage de la fenêtre
		var pos_input = js_position_element($(this.input_clock));
		_winClock.setZIndex(10000);
		_winClock.setDestroyOnClose();
		_winClock.setCloseCallback(reset_window);
		this.create_content();
		_winClock.showCenter(false, pos_input[1], pos_input[0]+_winClock.width);
	}
	
	// Contenu de la fenêtre
	this.create_content = function()
	{
		// Récupération de la date actuelle
		var actual_hour = this.getActualHour();
		var dateObject = 	new Date();
		var day = 			(dateObject.getDate() < 10) ? "0"+dateObject.getDate() : dateObject.getDate() ;
		var month = 		((dateObject.getMonth()+1) < 10) ? "0"+(dateObject.getMonth()+1) : dateObject.getMonth()+1 ;
		var today = ''+day+'/'+month+'/'+dateObject.getFullYear();
		var Linput_clock = this.input_clock;
		// Récupération de l'heure saisie dans le champ input
		var valeur_champ_input = $(input_clock).readAttribute('value');
		var hour_champ_input = new Array('0','0');
		if(valeur_champ_input != '') hour_champ_input = valeur_champ_input.split(':');
		// on gère le cas '06:00 PM' ou on doit faire +12
		if (_ampm && (valeur_champ_input.indexOf('PM') != -1))
			hour_champ_input[0] = parseInt(hour_champ_input[0],10) + 12;
		// Corrdonnées
		var tab_coords = ["65,20,10", "86,25,10", "104,42,10", "110,63,10", "103,85,10", "87,101,10", "65,106,10", "45,101,10", "28,85,10", "22,63,10", "29,42,10", "46,27,10"];
		// Style clock
		var style = (navigator.appName != "Microsoft Internet Explorer") ? "top:30px;" : "top:0px;";
		// Code HTML
		var retourHTML = '<div width="100%" align="center"><div style="width: 80%;"><div style="float: left; width: 35%;white-space: nowrap;"><label for="am" style="font-family: Verdana,Arial,sans-serif; font-style: normal; font-variant: normal; font-weight: normal; font-size: 7pt; line-height: normal; font-size-adjust: none; font-stretch: normal; -x-system-font: none; color: rgb(88, 88, 88);">AM</label><input id="am" name="ampm" onclick="change_clock()" type="radio"></div><div style="float: right; width: 35%;white-space: nowrap"><label for="pm" style="font-family: Verdana,Arial,sans-serif; font-style: normal; font-variant: normal; font-weight: normal; font-size: 7pt; line-height: normal; font-size-adjust: none; font-stretch: normal; -x-system-font: none; color: rgb(88, 88, 88);">PM</label><input id="pm" name="ampm" onclick="change_clock()" type="radio"></div></div><div align="center">';
		for (var i = 0; i <= 1; i++)
		{
			period = (i == 0) ? 'am' : 'pm';
			retourHTML += '<div id="'+period+'_clock">';
			if (_ampm) {
				retourHTML += '<div class="am_clock_img" style="'+style+'">';
			} else {
				retourHTML += '<div class="'+period+'_clock_img" style="'+style+'">';
			}
			for (var j=0;j<tab_coords.length;j++)
			{
				num_hour = label_hour = (j+(i*12));
				if (_ampm)  label_hour = j;
				if (num_hour == 0) label_hour = "Midnight";
				if (num_hour == 12) label_hour = "Noon";

				var has_link	= false;
				var alerted		= false;
				// cas il n'y a pas de champ date, il faut être avant l'heure actuelle
				if (!this.selected_date && (num_hour < actual_hour))		has_link = true;
				/*debug
				if (has_link && !alerted) {
					alert('1 h='+num_hour);
					alerted = true;
				}
				debug*/
				// cas il y a un champ date, mais il n'est pas sur aujourd'hui
				if ((this.selected_date) && (this.selected_date != today))	has_link = true;
				/*debug
				if (has_link && !alerted) {
					alert('2 h='+num_hour+' '+this.selected_date+' != '+today);
					alerted = true;
				}
				debug*/
				// le champ date est aujourd'hui, mais on est avant l'heure de maintenant
				if ((this.selected_date) && (this.selected_date == today) && (num_hour < actual_hour))	has_link = true;
				/*debug
				if (has_link && !alerted) {
					alert('3 h='+num_hour+' < '+actual_hour);
					alerted = true;
				}
				debug*/
				// on est avant la valeur actuelle de l'input heure
				if (parseInt(hour_champ_input[0],10) >= num_hour)		has_link = true;
				/*debug
				if (!has_link)	alert(hour_champ_input+' >= '+num_hour+' got false');
				if (has_link && !alerted) {
					alert('4 h='+num_hour+' <= '+hour_champ_input);
					alerted = true;
				}
				debug*/
				
				if (has_link)
				{
					var local_coords = tab_coords[j].split(',');
					var x = parseInt(local_coords[0],10) - 10;
					var y = parseInt(local_coords[1],10) - 10;
					retourHTML += '<div class="hour_clock_selector" id="hour_'+num_hour+'" title="'+label_hour+'" onclick="update_hour('+num_hour+',\''+Linput_clock+'\')" style="left:'+x+'px;top:'+y+'px;"></div>';
				}
			}
			retourHTML += '</div>';
			retourHTML += '</div>';
		}
		retourHTML += '	</div></div>';
		_winClock.setHTMLContent(retourHTML);
		change_clock();
	}
	
	// Retourne l'heure courante
	this.getActualHour = function()
	{
		var actual_date = new Date();
		return (actual_date.getHours() == 0) ? 24 : actual_date.getHours();
	}
	
	// INIT
	this.init = function()
	{
		if(calendar_input_id)
		{
			this.selected_date = $F(calendar_input_id);
		}	
		this.create_window();
//		var hour = ($(this.input_clock).readAttribute('value')) ? $(this.input_clock).readAttribute('value') : this.getActualHour();

		// mode AM/PM
		if (_ampm) {
			if ($F(this.input_clock).indexOf('PM') != -1)	$('pm').checked = true;
			else								$('am').checked = true;

		// mode 12/24
		} else {
			var hour = parseInt($F(this.input_clock));
			if (hour <= 12) $('am').checked = true;
				else $('pm').checked = true;
		}
		change_clock();
	}
	this.init();
}

// Permet la mise à jour du champ texte contenant l'heure courante sélectionnée
function update_hour(hour,input_clock)
{
	/*
		si le jour sélectionné = jour courant alors, on ne peut pas choisir une heure > heure courante - 1
	*/
	// 10/08/2007 - Modif. benoit : ajout d'une condition sur le contenu de la variable 'hour' (liée à la modif. sur le changement de fonction pour la fermeture de la fenêtre)
	// 19/03/2008 - Modif. benoit : la condition doit porter sur la valeur textuelle de l'heure car autrement l'heure 0 est considérée comme vide et pas prise en compte
	
	if (hour.toString() != "")
	{	
		if (_ampm) {
			// mode AM/PM
			var hour_as_integer = parseInt(hour,10);
			if (hour_as_integer < 10) {
				$(input_clock).value = '0'+hour_as_integer+':00 AM';
			} else if (hour_as_integer < 12) {
				$(input_clock).value = hour_as_integer+':00 AM';
			} else if (hour_as_integer < 22) {
				hour_as_integer = hour_as_integer - 12;
				$(input_clock).value = '0'+hour_as_integer+':00 PM';
			} else {
				hour_as_integer = hour_as_integer - 12;
				$(input_clock).value = hour_as_integer+':00 PM';
			}
		} else {
			// mode 12/24
			var hour_to_display		= (parseInt(hour,10) < 10) ? "0"+parseInt(hour,10): hour;
			$(input_clock).value	= hour_to_display+":00 ";
		}
	}	
	// Fermeture de la fenêtre d'horloge
	_winClock.close();
}

// Permet de changer l'horloge affichée (AM ou PM)
function change_clock()
{
	if($('am').checked == true){
		$('am_clock').style.display = '';
		$('pm_clock').style.display = 'none';
	} 
	else 
	{
		$('am_clock').style.display = 'none';
		$('pm_clock').style.display = '';
	}
}

// Reset la variable globale
function reset_window()
{
	_winClock.destroy();
	_winClock = false;
}

// Retourne la position d'un élément
function js_position_element(obj) 
{
   // Déclaration des variables de position
   var curleft = curtop = 0;
   if (obj.offsetParent) 
   {
     curleft = obj.offsetLeft
     curtop = obj.offsetTop
     while (obj = obj.offsetParent) 
     {
        curleft += obj.offsetLeft
        curtop += obj.offsetTop
     }
   }
   // Retour du tableau des coordonnées
   return [curleft,curtop];
}
