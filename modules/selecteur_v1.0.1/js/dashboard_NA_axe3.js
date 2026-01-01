/**
*	Cette fonction gère la soumission du second menu déroulant du sélecteur de 3eme axe.
*	Ce script est appelé sur $('selecteur_axe3').onchange pour peupler les options de $('selecteur_axe3_2')
*	
*	Cette fonction lance une requête AJAX sur /php/get_NA_axe3.js
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*	@params	none		réagi à la valeur de $('selecteur_na_level')
*	@return	void		renseigne le <div id="img_select_na" alt_on_over="XXX" />
*
*	- maj MPR : Correction du bug 11257 : On passe l'id du produit pour le GIS Supervision
*	14/01/2011 NSE bz 19965 : erreur dans le label lorsque le code se termine par | -> remplacement du separateur || par |*|
*/

function changeAxe3()
{

	
	var id_page = (_dashboard_id_page) ? '&id_page='+_dashboard_id_page : '';
	// maj MPR : Correction du bug 11257 : On passe l'id du produit pour le GIS Supervision
	var product = ( id_product ) ? '&product='+id_product : '';
	
	new Ajax.Request(url_get_NA_axe3,
		{
			method:'get',
			parameters : 'action=4'+id_page+product+'&axe3='+$F('selecteur_axe3')+'&axe3_2='+$F('selecteur_axe3_2_hidden'),
			onSuccess: function(transport) {
				var no_response = $('message_SELECTEUR_NO_RESPONSE').innerHTML;
				var response = transport.responseText || no_response;
				// on efface les anciennes options
				var mysel = $('selecteur_axe3_2');
				mysel.options.length = 0;
				// On regarde si des éléments on été envoyés
				if(transport.responseText == '') {
					mysel.options[0] = new Option($('message_U_SELECTEUR_NO_ELEMENT').innerHTML,'',true,true);
				}
				else {				
					var items = response.split("|s|")
					var count = items.length;
					for (var i=0; i<count; i++)
					{
						// 26/01/2009 - Modif. benoit  :remplacement du separateur "|" par "||"
                        // 14/01/2011 NSE bz 19965 : remplacement du separateur || par |*|
						var new_option = items[i].split("|*|");

						if (new_option[0] == $F('selecteur_axe3_2_hidden')) {
							mysel.options[i] = new Option(new_option[1],new_option[0],true,true);
						} else {
							mysel.options[i] = new Option(new_option[1],new_option[0]);
						}
					}
				}
			},
			onFailure: function(){ alert($('message_SELECTEUR_APPLICATION_CANT_ACCESS_TO').innerHTML+"\n"+url); }
		}
	)
}

changeAxe3();
$('selecteur_axe3').onchange = changeAxe3;
