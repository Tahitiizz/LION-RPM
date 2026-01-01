/**
 * Script JavaScript contenant les méthodes de gestion de la Fixed Hour
 *
 * $Author: o.jousset $
 * $Date: 2011-06-17 11:25:39 +0200 (ven., 17 juin 2011) $
 * $Revision: 27976 $
 */

/**
 * Méthode appelée pour l'initialisation du formulaire Fixed Hour.
 * Cette méthode est appellée après le chargement de la box NA.
 */
function initFixedHourMode()
{
    // Initialisation de la case à cocher en fonction de la valeur en base
    if( $( 'selecteur_fh_mode_ini' ) && $F( 'selecteur_fh_mode_ini' ) == "1" )
    {
         $( 'selecteur_fh_mode' ).checked = true;
    }
    else
    {
        // Si la case est décochée, on reset les valeurs initiales
        $( 'selecteur_fh_form_na_ini' ).value = '';
        $( 'selecteur_fh_form_ne_ini' ).value = '';
        $( 'selecteur_fh_form_na3_ini' ).value = '';
        $( 'selecteur_fh_form_ne3_ini' ).value = '';
        $( 'selecteur_fh_form_kpi_ini' ).value = '';
    }

    $( 'selecteur_fh_checkbox' ).onclick  = onFixedHourModeChange;
    $( 'selecteur_fh_form_na' ).onchange  = function(){updateFhNe( this.value, 1, true );}
    $( 'selecteur_fh_form_na3' ).onchange = function(){updateFhNe( this.value, 3, true );}
    $( 'selecteur_fh_form_ne' ).onchange  = updateFHFamilyBH;
    $( 'selecteur_fh_form_ne3' ).onchange = updateFHFamilyBH;

    Event.observe( 'selecteur_na_level', 'change', updateFhNa );
    
    if( $( 'selecteur_mode_overtime' ).checked )
    {
        majSelecteurOTONE( 'OT' );
    }
    else
    {
        majSelecteurOTONE( 'ONE' );
    }
}

/**
 * Mise à jour des niveaux d'agrégation 1er et 3ème axe
 */
function updateFhNa()
{
    var naLevels   = $( 'selecteur_na_level' );
    var naLevels3  = null;
    var elOptNew   = null;

    if( $( 'selecteur_na_level' ).selectedIndex == 0 )
    {
        // Si le NA sélectionné pour le Dash et le plus haut niveau, on désactive le Fixed Hour
        $( 'selecteur_fh_mode' ).checked    = false;
        $( 'selecteur_fh_mode' ).disabled   = true;
        $( 'selecteur_fh_form_na' ).length  = 0;
        $( 'selecteur_fh_form_ne' ).length  = 0;
        $( 'selecteur_fh_form_na3' ).length = 0;
        $( 'selecteur_fh_form_ne3' ).length = 0;
        $( 'selecteur_fh_info' ).update( 'Fixed Hour disabled for highest NA' );
        onFixedHourModeChange(); // Appel manuel ici
    }
    else
    {
        $( 'selecteur_fh_mode' ).disabled = false;
        $( 'selecteur_fh_info' ).update();
    }
    
    if( $( 'selecteur_fh_mode' ).checked )
    {
        if( $( 'selecteur_axe3' ) )
        {
            naLevels3 = $( 'selecteur_axe3' );
        }

        // Gestion des NA 1er axe
        $( 'selecteur_fh_form_na' ).options.length = 0;
        for( var i = 0 ; i < naLevels.selectedIndex ; i++ )
        {
            elOptNew       = new Element( 'option' );
            elOptNew.text  = naLevels.options[i].text;
            elOptNew.title = naLevels.options[i].text;
            elOptNew.value = naLevels.options[i].value;
            try
            {
                $( 'selecteur_fh_form_na' ).add( elOptNew, null );
            }
            catch( ex )
            {
                $( 'selecteur_fh_form_na' ).add( elOptNew );
            }
        }
        initFHFormWithDefaultValue( 'na' );
        
        // Gestion des NA 3ème axe
        if( naLevels3 == null )
        {
            updateFhNe( $( 'selecteur_fh_form_na' ).value, 1, true ); // Mise à jour des éléments réseaux (1er axe)
            $( 'selecteur_fh_form_na3' ).hide();
            $( 'selecteur_fh_form_ne3' ).hide();
        }
        else
        {
            updateFhNe( $( 'selecteur_fh_form_na' ).value, 1, false ); // Mise à jour des éléments réseaux (1er axe)
            $( 'selecteur_fh_form_na3' ).options.length = 0;
            for( i = 0 ; i < naLevels3.length ; i++ )
            {
                elOptNew = new Element( 'option' );
                elOptNew.text = naLevels3.options[i].text;
                elOptNew.value = naLevels3.options[i].value;
                try
                {
                    $( 'selecteur_fh_form_na3' ).add( elOptNew, null );
                }
                catch( ex )
                {
                    $( 'selecteur_fh_form_na3' ).add( elOptNew );
                }
            }
            initFHFormWithDefaultValue( 'na3' );
            updateFhNe( $( 'selecteur_fh_form_na3' ).value, 3, true ); // Mise à jour des éléments réseaux (3ème axe)
        }       
    }
}

/**
 * Initialise le formulaire avec les valeurs par défaut (celle en base de données).
 * Ces valeurs sont présentes dans les élément HTML cachés ayant le sufixe '_ini'
 * dans leur id.
 *
 * @param type Indique le type de l'élément à initialiser (na, na3, ne, ne3, kpi)
 */
function initFHFormWithDefaultValue( type )
{
    // Mise à jour dynamiquement en fonction du type fourni en paramètre
    if( $( 'selecteur_fh_form_' + type + '_ini' ) && $F( 'selecteur_fh_form_' + type + '_ini' ).length > 0 )
    {
        // Si des valeurs existent
        if( $( 'selecteur_fh_form_' + type ).length > 0 )
        {
            $( 'selecteur_fh_form_' + type ).value = $F( 'selecteur_fh_form_' + type + '_ini' );

            // Si l'initialisation du KPI à échoué ( la BH n'existe plus)
            if( type == 'kpi' && $F( 'selecteur_fh_form_' + type + '_ini' ) != $F( 'selecteur_fh_form_' + type ) )
            {
                $( 'selecteur_fh_mode' ).checked = false;
                onFixedHourModeChange();
            }
        }
        else if( type == 'kpi' )
        {
            // On désactive la Fixed Hour
            $( 'selecteur_fh_mode' ).checked = false;
            onFixedHourModeChange();
        }

        // Afin de ne pas mettre à jour systématiquement les champs et d'initialiser
        // une fois seulement, on renomme l'id '*_ini' en '*_old'
        $( 'selecteur_fh_form_' + type + '_ini' ).id = 'selecteur_fh_form_' + type + '_old'
    }
    else
    {
        // Déjà initialisé ou pas d'initialisation à effectuer
    }
}

/**
 * Mise à jour des éléments réseaux 1er OU 3ème axe
 *
 * @param na Niveau d'aggregation
 * @param axe Numéro de l'axe à mettre à jour (1 ou 3)
 * @param autoUpdateKpi Indique si la mise à jour automatique des Raw/Kpi doit être effectuée
 */
function updateFhNe( na, axe, autoUpdateKpi )
{
    $( 'selecteur_fh_form_info' ).update();
    var refSelect = $( 'selecteur_fh_form_ne' );
    if( axe == 3 )
    {
        refSelect = $( 'selecteur_fh_form_ne3' );
    }

    // Réinitialisation du <select> 1er axe
    refSelect.options.length = 0;
    refSelect.disabled = true;

    new Ajax.Request( urlSelcector + "php/fixedHour.ajax.php",
    {
        // Chargement synchrone afin de s'assurer que le chargement des NE 3ème
        // axe ne s'effectue qu'un fois que le premier axe est fini
        asynchronous:false,
        method:"post",
        parameters:"getNe=1&na=" + na + "&dashId=" + _dashboard_id_page,
        onSuccess: function( res )
        {
            if( res.responseText.length > 0 )
            {
                var neValues  = res.responseText.split( '|s|' );
                var neIdLabel = null;
                var elOptNew  = null;
                for( var i = 0 ; i < neValues.length ; i++ )
                {
                    neIdLabel      = neValues[i].split( '||' );
                    elOptNew       = new Element( 'option' );
                    elOptNew.text  = neIdLabel[1];
                    elOptNew.title = neIdLabel[1];
                    elOptNew.value = neIdLabel[0];

                    // En cas d'absence de label, on utilise l'identifiant'
                    if( elOptNew.text.length == 0 )
                    {
                        elOptNew.text  = neIdLabel[0];
                        elOptNew.title = neIdLabel[0];
                    }

                    try
                    {
                        refSelect.add( elOptNew, null );
                    }
                    catch( ex )
                    {
                        refSelect.add( elOptNew );
                    }
                }
                refSelect.disabled = false;

                // Initialisation de la valeur
                if( axe == 1 )
                {
                    initFHFormWithDefaultValue( 'ne' );
                }
                else
                {
                    initFHFormWithDefaultValue( 'ne3' );
                }

                // Chargement de la liste des KPI (si demandé)
                if( autoUpdateKpi == true )
                {
                    updateFHFamilyBH();
                }
            }
            else
            {
                $( 'selecteur_fh_form_kpi' ).length   = 0;
                $( 'selecteur_fh_form_kpi' ).disabled = true;
                $( 'selecteur_fh_form_info' ).update( 'Warning : No Network Element found' );
            }
        }
    });   
}

/**
 * Mise à jour des RAWs/KPIs utilisés comme référence pour la BH
 */
function updateFHFamilyBH()
{
    $( 'selecteur_fh_form_info' ).update();
    var na  = $F( 'selecteur_fh_form_na' );
    var ne  = $F( 'selecteur_fh_form_ne' );
    var na3 = $F( 'selecteur_fh_form_na3' );
    var ne3 = $F( 'selecteur_fh_form_ne3' );

    $( 'selecteur_fh_form_kpi' ).length   = 0;
    $( 'selecteur_fh_form_kpi' ).disabled = true;
    var param = "&na=" + na + "&ne=" + ne;

    // Si un des 2 paramètres 3ème est défini, on les ajoute
    if( na3 != null || ne3 != null )
    {
        param += "&na3=" + na3 + "&ne3=" + ne3;
    }
    new Ajax.Request( urlSelcector + "php/fixedHour.ajax.php",
    {
        method:"post",
        parameters:"getRawKpi=1&dashId=" + _dashboard_id_page + param,
        onSuccess: function( res )
        {
            if( res.responseText.length > 0 )
            {
                var rawKpiBH = res.responseText.split( '|s|' );
                var bhFields = new Array();
                var elOptNew  = null;
                for( var i = 0 ; i < rawKpiBH.length ; i++ )
                {
                    bhFields       = rawKpiBH[i].split( '||' );
                    elOptNew       = new Element( 'option' );
                    elOptNew.text  = bhFields[2];
                    elOptNew.title = bhFields[2];
                    elOptNew.value = bhFields[0] + '||' + bhFields[1];
                    try
                    {
                        $( 'selecteur_fh_form_kpi' ).add( elOptNew, null );
                    }
                    catch( ex )
                    {
                        $( 'selecteur_fh_form_kpi' ).add( elOptNew );
                    }
                }
                $( 'selecteur_fh_form_kpi' ).disabled = false;
                initFHFormWithDefaultValue( 'kpi' );
            }
            else
            {
                // Pas de Raw/Kpi de BH
                $( 'selecteur_fh_form_info' ).update( 'Warning : No Raw/Kpi Element found' );
                initFHFormWithDefaultValue( 'kpi' );
            }
        }
    });
}

/**
 * Fonction affichant ou cachant le Fixed Hour mode en fonction du type et de la TA
 * Cette fonction agit sur la case à cocher ET le formulaire de BH
 */
function showHideFixedHourMode()
{
    // L'option Fixed Hour n'est disponible qu'en ONE avec la TA Hour
    if ( ( $( 'selecteur_mode_overtime' ).checked ) || ( $F('selecteur_ta_level') != 'hour' ) )
    {
        // On chache la case à cocher et le formulaire
        if( $( 'selecteur_fh_checkbox' ) )  $( 'selecteur_fh_checkbox' ).hide();
        if( $( 'selecteur_fh_form' ) )      $( 'selecteur_fh_form' ).hide();
        $( 'selecteur_fh_info' ).hide();
    }
    else
    {
        if( $( 'selecteur_fh_checkbox' ) )  $( 'selecteur_fh_checkbox' ).show();
        $( 'selecteur_fh_info' ).show();
        onFixedHourModeChange();
    }
}

/**
 * Fonction appelée à chaque changement de mode du Fixed Hour mode (case à cocher)
 */
function onFixedHourModeChange()
{
    if( $( 'selecteur_fh_mode' ).checked )
    {
        // Si la case est cochée on affiche le formulaire
        $( 'selecteur_fh_form' ).show();

        // Si aucun NA n'est présent, on charge les données
        if( $( 'selecteur_fh_form_na' ).length == 0 )
        {
            updateFhNa();
        }
        else
        {
            // Dans ce cas on ne fait rien, le formulaire à déjà été rempli. Cela
            // permet d'éviter un rechargement systématique à chaque activation.
            // Le formulaire est donc réaffiché tel qu'il était.
        }
    }
    else
    {
        $( 'selecteur_fh_form' ).hide();
    }
}
