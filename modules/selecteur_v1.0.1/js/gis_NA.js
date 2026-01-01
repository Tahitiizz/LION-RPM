/*
 *  22/09/2010 NSE bz 11339 : liste de NE mal initialisée au premier lancement du Gis
 *	25/11/2011 ACS BZ 24784 raw/kpi tooltip
 */

	function networkElementSelectionSaveHook() {

		var listeElems = $F('gis_nel_selecteur');


		if (listeElems == '') {
			$('gis_nel_img').setAttribute('alt_on_over', "No element selected");
		} else {

			// 22/09/2010 NSE bz 11339 : utilisation de la variable js à la place de la variable php non fonctionnelle
			// 25/11/2011 ACS BZ 24784 "Value" is not a valid html attribute for div => use getAttribute to retrieve it
            new Ajax.Request($('gis_nel_url').getAttribute('value'),
				{
					method:'get',
					parameters: { current_selection: listeElems, labels_only: 1},
					onSuccess: function(transport) {
						var no_response = $('message_SELECTEUR_NO_RESPONSE').innerHTML;
						var response = transport.responseText || no_response;
						$('gis_nel_img').setAttribute('alt_on_over', response);
						listeElems = response;

					},
					onFailure: function(){ $('gis_nel_img').setAttribute('alt_on_over', $('message_SELECTEUR_APPLICATION_CANT_ACCESS_TO').innerHTML+" \n"+url);  }
				}
			)
		}
	}
	
	/**
	* resetNeSelection : vide la selection courante (champ input et decoche les checkbox)
	*/
	function resetNelSelectionGIS(){
		// On mets le champ de stockage a vide
		_listOfSelectedElements = '';
		var saveFieldId = $('gis_nel_saveFieldId').value;
		$(saveFieldId).clear();
		$('gis_nel_img').className = 'bt_off';
		
			
	}
	
	
	function updateNelSelecteurGIS( )
	{
	
		var na_selected = $F('selecteur_na_level');
	
    // on va chercher tous les accordeons
		var accs = $$('.accordion_title');
		
		var nb_acc = accs.length;
		
		for(i=0;i<accs.length;i++){
	
			var acc_id = accs[i].id;					// ex: acc_id = 'htmlPrefix_sgsn_title'
			acc_id = acc_id.slice(8);					// ex: acc_id = 'sgsn_title'
			acc_id = acc_id.slice(0,acc_id.lastIndexOf('_'));	// ex: acc_id = 'sgsn'
			
			if( $('gis_nel_'+acc_id+'_title') ){
				
				if ( acc_id == na_selected ) {
				
					$('gis_nel_'+na_selected+'_title').style.display = 'block';
				
				} else {

					$('gis_nel_'+acc_id+'_title').style.display = 'none';
				}
			}
		}	
	}