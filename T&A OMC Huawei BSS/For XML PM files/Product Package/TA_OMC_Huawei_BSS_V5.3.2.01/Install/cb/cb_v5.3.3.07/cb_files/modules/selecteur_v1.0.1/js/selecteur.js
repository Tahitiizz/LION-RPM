/**
*	Fonctions js spécifiques au sélécteur
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*/

/**
*	Cette fonction change la valeur d'un champ en l'entourant en rouge pendant une seconde
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*	@params	object	elem : élément HTML (le plus souvent un <input> dont on doit, ou dont on a changé la valeur)
*	@params	string		value : valeur qui doit être donnée à l'élément (facultatif)
*	@params	bool		focus : true si on doit donner le focus sur l'élément. facultatif.
*	@return	void		renseigne la valeur de elem
*/
function rouge(elem,value,focus) {
	myelem = $(elem);	// comme ça, on peut passer un ID ou un element HTML
	var rememberBorderColor	= myelem.style.borderColor;
	var rememberColor		= myelem.style.color;
	myelem.style.borderColor	= 'red';
	myelem.style.color		= 'red';
	// if (myelem.tagName == 'SELECT')
	if (focus) myelem.focus();
	if (value) myelem.value	= value;
	setTimeout("$('"+myelem.id+"').style.borderColor	= '"+rememberBorderColor+"';",1000);
	setTimeout("$('"+myelem.id+"').style.color		= '"+rememberColor+"';",1000);
}

/**
 * Fonction de validation du formulaire du Selecteur
 *
 * setTimeout de 1000 : Hack bancal, petit temps de latence entre la demande de
 * soumission du selecteur, et sa soumission effective afin de laisser le temps
 * aux actions onblur ou onchange des éléments du formulaire de corriger les valeurs
 *
 * 28/06/2011 OJT : Correction bz22664, sauvegarde impossible même en OT du au mode Fixed Hour
 */
function submit_selecteur()
{
    // Flag indiquant si le submit doit bien s'effectuer'
    var flagSubmit = true;

    // Suppression de l'éventuel message d'erreur
    $( "selecteur_submit_info" ).update();

    // Test de l'activation du mode Fixed Hour
    if( $( 'selecteur_fh_mode' ).checked )
    {
        // 24/06/2011 OJT : Correction bz22664, vérification de la TA et du mode
        if( $( 'selecteur_mode_overnetwork' ).checked &&  $F( 'selecteur_ta_level' ) == 'hour' )
        {
            // Si la case est cochée, on vérifie que tous les champs sont bien remplis
            if (
                    ( $( 'selecteur_fh_form_na' ).value.length > 0 ) && ( $( 'selecteur_fh_form_ne' ).value.length > 0 ) &&
                    (
                      ( $( 'selecteur_fh_form_na3' ).value.length > 0 && $( 'selecteur_fh_form_ne3' ).value.length > 0 ) ||
                      ( $( 'selecteur_fh_form_na3' ).style.display == 'none' && $( 'selecteur_fh_form_ne3' ).style.display == 'none' )
                    ) && ( $( 'selecteur_fh_form_kpi' ).value.length > 0 )
                )
            {
                // Ok le formulaire sera validé
            }
            else
            {
                flagSubmit = false;
                $( "selecteur_submit_info" ).update( "Unable to save \"Fixed Hour\" configuration, please fill all fields or uncheck option" );
                new Effect.Highlight( "selecteur_submit_info" );
            }
        }
    }

    if( flagSubmit == true )
    {
        setTimeout( "$( 'selecteur' ).submit();", 1000 );
    }
    return false;
}

/**
 * Cette fonction met à jour les box en fonction de la modification des radio OT/ONE de la box Mode
 *
 * @param mode OT ou ONE
 * @param valueOT Texte à afficher en mode OT
 * @param valueONE Texte à afficher en mode ONE
 */
function majSelecteurOTONE( mode, valueOT, valueONE )
{
    if( mode == 'OT' )
    {
        // Modifie le texte en fonction de mode
        if( $( 'dashboardnb_elements_label' ) )
        {
            $( 'dashboardnb_elements_label' ).update( valueOT );
        }
        if( $( 'selecteur_period_div' ) )
        {
            $( 'selecteur_period_div' ).show();
        }
    }
    else
    {
        if( $( 'dashboardnb_elements_label' ) )
        {
            $( 'dashboardnb_elements_label' ).update( valueONE );
        }
        if( $( 'selecteur_period_div' ) )
        {
            $( 'selecteur_period_div' ).hide();
        }
    }

    // Si la méthode showHideFixedHourMode existe, on l'appelle pour l'informer
    // qu'un chagement de mode a été réalisé
    if( typeof showHideFixedHourMode == 'function' ){
        showHideFixedHourMode();
    }
}
