/**
 * Fonctions JavaScript pour l'ajout d'une ligne
 * 
 * @author SPS
 * @date 07/05/2009
 * @version CB 4.1.0.0
 * @since CB 4.1.0.0
 **/

//declaration de la fenetre qui sera affichee
var _winDrawLine;
var _originalCaddieFunctions = new Array();

/**
 * choix de l'axe d'alignement
 **/
function align_line(side) {
	$('align').value = side;
}

/**
 * lors du click sur la case a cocher "remove all lines", on met a disabled les champs de saisie
 **/
function remove_line() {
	if ($('remove_line').checked == '1') {
		$('line_value').disabled = '1';
		$('legend').disabled = '1';
		$('update_all').checked = '';
	}
	else {
		$('line_value').disabled = '';
		$('legend').disabled = '';
	}
}

/**
 * envoi des infos pour l'ajout de ligne
 **/
function sendDrawLine() {
	
	//si la case 'remove line' est cochee
	if ( $('remove_line').checked == 1) {
		updateGTMsInSession('remove');
		closeWinDrawLine();
	}
	else {
	
		//on teste si la valeur de la ligne est numerique 
		if ( /^-?[0-9]{1,}(\.?[0-9]+)?$/.test( $('line_value').value ) ) {
			
			//on teste si la legende n'est pas vide
			if ($('legend').value != "" ) {
				
				//ajout de la ligne sur un seul graph
				if ( $('update_all').checked != 1) {
					addLineToGTM($('gtm_name').value);	
				}
				else {
					//ajout de ligne sur tous les graphs
					updateGTMsInSession('add');					
				}
				
				closeWinDrawLine();
			}
			else {
				//message d'erreur defini dans dashboard_display/index.php
				alert(_msgAlertLegend);
				//le curseur pointe sur le champ en erreur
				$('legend').focus();
			}
		}
		else {
			//message d'erreur defini dans dashboard_display/index.php
			alert(_msgAlertLineValue);
			//le curseur pointe sur le champ en erreur
			$('line_value').focus();
		}
	}
}

/**
 * ouverture de la fenetre d'ajout de ligne
 * @params string nom du gtm
 **/
function showWindowDrawLine(gtm_name) {
	
	//on detruit toutes les fenetres crees precedemment
	Windows.closeAll();
					
	//creation de la fenetre
	_winDrawLine = new Window({
		className:"drawline", 
		title: 'Draw a line :',
		width:350, height:150,
		minWidth:0, minHeight:0,
		closable:false,
		resizable:false,
		draggable:false,
		minimizable:false,
		recenterAuto: false,
		maximizable:false
	}); 

	_winDrawLine.setZIndex(2500);
	
	//la fenetre va prendre pour contenu le contenu du div d'id 'add_line'
	_winDrawLine.setContent('add_line');
	
		
		
	//on regarde si le gtm a un axe y a droite, sinon on desactive le radio pour l'alignement a droite
	new Ajax.Request( "php/draw_line.php", 
		{method: "get",
			parameters: {
				gtm_name:gtm_name,
				action:'hasRightAxis'
			},
			onSuccess:function(data) {
				hasRightAxis = data.responseText;
				
				if ( !hasRightAxis ) {
					 $('draw_right').disabled = 1;
					 $('lbl_draw_right').update('');
				}
			}
		}
	);
	
	//on ajoute le nom du gtm dans l'element 'gtm_name'
	$('gtm_name').value = gtm_name;
	
	//le clic sur sur le bouton fill_color_btn appellera le colorPicker
	var picker = new ColourPicker('fill_color', 'fill_color_btn');
	
	_winDrawLine.showCenter();
	_winDrawLine.updateWidth();
	_winDrawLine.updateHeight();
	
}


/**
 * fermeture de la fenetre d'ajout
 **/
function closeWinDrawLine() {
	_winDrawLine.close();
}


/**
 * mise a jour de tous les GTM de la page
 * @params string action nom de l'action
 **/ 
function updateGTMsInSession(action) {
	
	//on va chercher le nom des graphs en session
	new Ajax.Request( "php/draw_line.php", 
		{method: "get",
			parameters: {
				multi:'1'
			},
			onSuccess:function(data) {
				
				//on recupere les donnees globales
				var donneesRecues = data.responseText;
				//on recupere le nom des gtm
				var tgtm = donneesRecues.split("-");
				
				for(var j=0;j < tgtm.length;j=j+1) {
					if (action == 'add') {
						//pour chacun des gtm, on ajoute la ligne
						addLineToGTM(tgtm[j]);
					}
					if (action == 'remove') {
						//pour chacun des gtm, on supprime la ligne
						removeLineFromGTM(tgtm[j]);
                        // 20/09/2010 BBX
                        // Il faut mettre à jour le lien du bouton caddie
                        // BZ 11945
                        restoreCartAction(tgtm[j]);
					}
				}
			}
		}
	);
}


/**
 * ajout de la ligne sur le gtm : mise a jour des map et de l'image
 * @params string gtm nom du gtm a modifier
 **/ 
function addLineToGTM(gtm) {
	GTMLoader(gtm);
	new Ajax.Request( "php/draw_line.php", 
		{method: "get",
			parameters: {
				gtm_name:gtm,
				action:"add",
				line_value:$('line_value').value,
				legend:$('legend').value,
				align:$('align').value,
				color:$('fill_color').value
			},
			onSuccess:function(data) {
				GTMLoader(gtm);
				// 20/09/2010 BBX : bz 11945, il faut mettre à jour le lien du bouton caddie
                var newGtm = extractNewName(data.responseText);
                updateCartAction(gtm,newGtm);
				$('gtm_picture_' + gtm).update(data.responseText);                               
				$( 'gtm_dt_' + gtm + "_close" ).hide(); // 02/11/2010 OJT : bz18732, on affiche l'icone open par défaut
				$( 'gtm_dt_' + gtm + "_open" ).show();
			}
		}
	);
}

/**
 * Restore la fonction d'origine du caddie
 * 20/09/2010 BBX : créé dans le cadre du BZ 11945
 */
function restoreCartAction(gtm) {
    if(_originalCaddieFunctions[gtm]) {
        var originalAction = _originalCaddieFunctions[gtm];

        // On récupère les nouveaux paramètres
        new Ajax.Request( "php/draw_line.php", {
           method: 'get',
           parameters: 'action=restoreCart&gtm_name='+gtm+'&currentAction='+originalAction,
           onSuccess: function(data) {

                var newFunction   = 'caddy_update(';
                newFunction       += data.responseText;
                newFunction       += ')';

                $('cart_btn_' + gtm).onclick = function () {
                    eval(newFunction);
                };
           }
        });
    }
}

/**
 * Met à jour le nom de l'image dans la fonction du caddie
 * 20/09/2010 BBX : créé dans le cadre du BZ 11945
 */
function updateCartAction(gtm,newGtm)
{
    // On mémorise la fonction d'origine si pas encore enregistrée
    if(!_originalCaddieFunctions[gtm]) {
        var currentAction = $('cart_btn_' + gtm).onclick;
        _originalCaddieFunctions[gtm] = currentAction;
    }

    // On récupère les nouveaux paramètres
    new Ajax.Request( "php/draw_line.php", {
       method: 'get',
       parameters: 'action=updateCart&gtm_name='+gtm+'&currentAction='+_originalCaddieFunctions[gtm]+'&newGtm='+newGtm,
       onSuccess: function(data) {

            var newFunction   = 'caddy_update(';
            newFunction       += data.responseText;
            newFunction       += ')';

            $('cart_btn_' + gtm).onclick = function () {
                eval(newFunction);
            };
       }
    });
}

/**
 * Permet d'extraire le nouveau nom du gtm dans les résultats reçus
 * 20/09/2010 BBX : créé dans le cadre du BZ 11945
 **/
function extractNewName(result) {
    var Expression  = new RegExp('<map name="([a-zA-Z0-9]+)"','');
    var matches     = Expression.exec(result);
    return (matches[1] || '');
}

/**
 * suppression de la ligne sur le gtm : mise a jour des map et de l'image
 * @params string gtm nom du gtm a modifier
 **/ 
function removeLineFromGTM(gtm) {
	GTMLoader(gtm);
	new Ajax.Request( "php/draw_line.php", 
		{method: "get",
			parameters: {
				action:"remove",
				gtm_name:gtm
			},
			onSuccess:function(data) {
				$('gtm_picture_' + gtm).update(data.responseText);
			}
		}
	);
}

/**
 * pendant le chargement, on affiche un loader sur chacune des images
 * @params string gtm nom du gtm
 * */
function GTMLoader(gtm) {
	$('gtm_picture_' + gtm).update("<div id=\"gtmloader\"><img src=\"../images/animation/ajax-loader.gif\"/>"+_msgUpdatingGTM+"</div>");
} 

/**
 * affichage du chargement
 **/ 
/*function showLoading() {
	//initialisation de la tempo
	var t_id = setInterval(animate,20);
	var pos=0;
	var dir=2;
	var len=0;
	//on affiche les blocs pour le chargement
	$('loader_container').style.display = 'block';
	$('loader_container').style.visibility = 'visible';
	$('loader_background').style.display = 'block';
	$('loader_background').style.visibility = 'visible';
	//changement du texte du loader
	$('texteLoader').update(_msgUpdatingGTM);
	
	//on lance l'animation	
	animate();
	
	//on positionnne le bloc de chargement 
	//document.documentElement.scrollTop --> endroit ou l'utilisateur est positionne dans la page
	//document.documentElement.offsetHeight --> taille de l'ecran
	var scrollTop = document.documentElement.scrollTop + (document.documentElement.offsetHeight / 3);
	$('loader_container').style.top = scrollTop;
}*/

/**
 * on cache le chargement
 **/
/*function hideLoading() {
	//on arrete la tempo
	this.clearInterval(t_id);
	//on cache les blocs
	$('loader_container').style.display = 'none';
	$('loader_container').style.visibility = 'hidden';
	$('loader_background').style.display = 'none';
	$('loader_background').style.visibility = 'hidden';
}*/
