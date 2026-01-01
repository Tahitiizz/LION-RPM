/*
 *  cb50400
 *
 *  17/08/2010 NSE DE Firefox bz 17018 : pas d'affichage dans l'autre liste quand on s�lectionne un compteur : remplacement de new Element('Option', ... par new Option
 *
*/
/**
 * R�cup�re une info raw ou kpi
 *
 * @author GHX
 * @param Element selObj : element select
 */
function getElementInfo ( selObj )
{
	if(selObj.selectedIndex > -1)
	{
		$('tooltip_raws').update(selObj.options[selObj.selectedIndex].comment);
	}
} // End function getElementInfo


function hideSynchroWindow(){

    $('div_contener').style.display='none';
}
/**
 * Permet de transvaser des elements d'une liste � une autre
 *
 * @author GHX
 * @param int sens : sens dans lequel on doit copier la liste des �l�ments 1 : maitre > slave / 2 : slave > maitre
 * @param string maitre :  ID de la balise select
 * @param string slave : ID de la balise select
 */
function move_elements ( sens, maitre, slave )
{
	// Id des zones
	var id_zone_maitre = maitre;
	var id_zone_esclave = slave;
	var id_input_maitre = 'hidden_'+maitre;
	var id_input_esclave = 'hidden_'+slave;

	// Selon le sens
	if(sens == 1) {
		move(id_zone_maitre,id_zone_esclave);
	}
	else {
		move(id_zone_esclave,id_zone_maitre);
	}
	
	// Sauvegarde des elements de la zone esclave
	$(id_input_esclave).value = '';
	for(var i = 0; i < $(id_zone_esclave).options.length; i++)
	{
		var sep = (i == 0) ? '' : '|';
		$(id_input_esclave).value += sep+$(id_zone_esclave).options[i].value;
	}
} // End function move_elements

/**
 * Fonction qui bouge les �l�ments
 *
 * @author GHX
 * @param string idz1 :  ID de la balise select
 * @param string idz2 : ID de la balise select
 */
function move ( idz1, idz2 )
{
	if($(idz1).options.selectedIndex != -1)
	{
		for(var i = 0; i < $(idz1).options.length; i++)
		{
			if($(idz1).options[i].selected)
			{
				// 17/08/2010 NSE DE Firefox bz 17018 : remplacement de new Element('Option', ... par new Option
                $(idz2)[$(idz2).options.length] = new Option($(idz1).options[i].text, $(idz1).options[i].value)
				$(idz1).options[i] = null;
				i--;
			}
		}
	}
} // End function move

/**
 * maj 24/03/2010 - MPR : DE - Mixed KPI - Ajout du bouton Synchronize
 * 
 * Ajout des fonctions : 
 *      - updateCounters() Mise à jour des compteurs
 *      - getContentWindow() Fonction qui détermine le contenu de notre prototype window
 *      - showLoading() Nécessaire pour le loading page
 *      - hideLoading() Nécessaire pour le loading page
 *      
 */

function updateContentWindow( content )
{
    Element.update('div_contener',content );
}

function checkProcessEncours()
{
    var process_encours = false;
    var msg = "";

    new Ajax.Request(
            "php/get_process_encours.php",
            {
                method: 'post',
                asynchronous:false,
                parameters: {},
                onSuccess: function(data) {
                    process_encours = data.responseText;

                    if( process_encours != "false" )
                    {
                        msg = "Synchronization cannot take place while a process is running.";
                        msg+= "Please stop all processes (Task Scheduler > Process)";
                    }  
                    $('div_contener').innerHTML = msg;
                },
                onFailure: function() {alert('Request failed')}
            }
    );

    return process_encours;
}

/**
 * Fonction qui ouvre une pop-up prototype window
 * contenant un message de confirmation ( nombre de compteurs à mettre à jour )
 *
 */
function synchronization()
{
    var div_contener = $('div_contener').innerHTML;
    
    // Contrôle si un process est en cours ou non
    var process_encours = checkProcessEncours();
    
    // Box d'alerte avec message bloquant
    if( process_encours != "false" )
    {
        // On masque le loading une fois le traitement fini 
        displayAlertBox("");
       // hideLoading();
    }
    else
    {
        // On récupère le contenu de notre prototype window
        getContentWindow();
    }
}

function getContentWindow()
{
    var family = $('idFamily').value;
    var msg = "";
    new Ajax.Request(
            "php/check_counters_to_synchronize.php",
            {
                method: 'post',
                //asynchronous:false,
                parameters: {idFamily:family},
                onCreate :function(data) {
                  showLoading("Checking counters...");
                },
                onSuccess: function(data) {

                   
                   msg = data.responseText;
                    //Mise a jour du contenu de la popup proto window
                   updateContentWindow(data.responseText);

                   hideLoading();

                    if( msg == "0" )
                    {
                        displayAlertBox( msg );
                    }
                    else
                    {
                    // n éléments sont à synchroniser
                        displayConfirmBox( msg );
                    }


                },
                onFailure: function() {alert('Request failed');}
            }
    );
}



//l'id passe va servir a eviter les pbls de cache du navigateur
function updateCounters()
{
    var family = $('idFamily').value;
    var msg = "";
    //on affiche le chargement
    showLoading("Synchronization...");
    
    new Ajax.Request(
            'php/confirm_synchro.php',
            {
                method: 'post',
                parameters: {idFamily:family},
                onSuccess: function(data) {

                    //on cache le chargement
                    hideLoading();
                    if ( data.responseText == 'ok')
                    {
                        $('synchroResult').value = "1";
                    }
                    else
                    {
                        $('synchroResult').value = "0";
                        $('SynchroMsgError').value = data.responseText;
                    }

                    $('synchroValid').submit();
                    
                },
                onFailure: function() {alert('Request failed')}
            }
    );
        
}

/**
 * Fonction qui affiche un message alert dans popup prototyep window
 */
function displayAlertBox( msg )
{
    if( msg == "0" )
    {
        updateContentWindow("Nothing to Synchronize");
    }
    var div_contener = $('div_contener').innerHTML;
    
    Dialog.alert(
            div_contener,
            {
                className:"alphacube",
                title:"Synchronization of Counters",
                width:400,
                zindex:20000,
                destroyOnClose: true,
                top:200,
                buttonClass:"bouton",
                okClose:"Close"
            }
        );
}

/**
 * Fonction qui affiche un message confirm dans popup prototype window
 */
function displayConfirmBox( msg )
{
    var div_contener = $('div_contener').innerHTML;
    
    Dialog.confirm(
        div_contener,
        {
            className:"alphacube",
            title:"Synchronization of Counters",
            width:600,
            height:300,
            draggable:true,
            wiredDrag: true,
            resizable: true,
            zindex:20000,
            destroyOnClose: true,
            top:200,
            buttonClass:"bouton",
            okLabel:"Confirm", cancelLabel:"Cancel",
            onShow:function(win){
                hideLoading();
            },
            onOk:function(win){
               updateCounters();
               
                return true;
            },
            onCancel:function(win){
            }
        }
    );
}

var t_id = null;
//on affiche le chargement
function showLoading(msg) {

        msg_in_loading_new = msg;
        t_id = setInterval(taLoaderStartLoading,20);

        if( msg !="" ){
            Element.update('texteLoader',msg_in_loading_new);
            $('loader').setStyle({width:'200px'});
            $('loader_bg').setStyle({width:'170px'});
            $('progress').setStyle({width:'170px'});
            //_ta_loader_max_len = 170;
        } else {
            Element.update('texteLoader','Loading...');
        }
        var pos=0;
        var dir=2;
        var len=0;
        //on affiche les blocs pour le chargement
        $('loader_container').style.display = 'block';
        $('loader_container').style.visibility = 'visible';
        $('loader_background').style.display = 'block';
        $('loader_background').style.visibility = 'visible';
        
}

//on cache le chargement
function hideLoading() {
        //on arrete la tempo
        this.clearInterval(t_id);
        //on cache les blocs
        $('loader_container').style.display = 'none';
        $('loader_container').style.visibility = 'hidden';
        $('loader_background').style.display = 'none';
        $('loader_background').style.visibility = 'hidden';
}

function sleep(timeout) {
    var loop = true;
    var current = new Date();
    var now;
    var cTimestamp = current.getTime();

    while(loop) {
        now = new Date();
        nTimestamp = now.getTime();
        if(nTimestamp - cTimestamp > timeout) {
            loop = false;
        }
    }
}