/**
 * Définition des fonctions de gestion de l'index des dashboards
 *
 *	04/06/2009 GHX
 *		- Suppression de la fonction createIndex()
 *		- Modification de la fonction slideIndexContent() afin de pouvoir replier les listes
 *
 *	11/05/2010 OJT
 *	    - Ajout de la fonction showDataTable pour les tables de données
 */

/**
 * Permet d'afficher / masquer les éléments des GTMs
 * 
 * 11/03/09 - modif SPS : - changement des effets lors de l'affichage/masquage (slideup/slidedown -> show/hide)
 *						  - on cache toutes les listes à chaque appel, pour ensuite n'afficher que celle que l'on souhaite
 * 						  - suppression de la propriete height=auto
 *
 *	15:11 04/06/2009 GHX
 *		- Correction de la fonction pour que quand la liste est dépliée on puisse la repliée
 *
 * @param string id_toslide identifiant du div à afficher / masquer contenant les éléments du GTM
 * @param string id_btn_slide identifiant du bouton appelant
 */

function slideIndexContent(id_toslide, id_btn_slide)
{	

	if ($('index_dashboard_content')) {
		var tIndexElementsList = new Array();
		var tIndexButton = new Array();
		
		//On recupere la liste des input de type button
		tIndexButton = $('index_dashboard_content').select('input[type="button"]');
		
		//On change le nom de la classe de chacun des input
		for (var i=0;i < tIndexButton.length;i++)
		{
			// 15:12 04/06/2009 GHX
			// Ajout de la condition
			if ( tIndexButton[i].id != id_btn_slide )
			{
				tIndexButton[i].removeClassName('IndexGTMButtonDown');
				tIndexButton[i].addClassName('IndexGTMButtonUp');
			}
		}
		
		//On recupere la liste des elements de classe IndexElementsList
		tIndexElementsList = $('index_dashboard_content').getElementsByClassName('IndexElementsList');
		
		//On cache tous les elements trouves	
		for (var j=0;j < tIndexElementsList.length;j++)
		{
			tIndexElementsList[j].hide();
		}
		
		// Affichage du div
		if ( $(id_btn_slide).hasClassName('IndexGTMButtonUp') )
		{
			$(id_btn_slide).addClassName('IndexGTMButtonDown');
			$(id_btn_slide).removeClassName('IndexGTMButtonUp');
			$(id_toslide).show();
		}
		else // Masquage du div
		{
			$(id_btn_slide).addClassName('IndexGTMButtonUp');
			$(id_btn_slide).removeClassName('IndexGTMButtonDown');
			$(id_toslide).hide();
		}
	}
}

/**
 * Permet d'afficher / masquer le div contenant les GTMs présents dans le dashboard
 * 11/03/09 - modif SPS : - changement des effets lors de l'affichage/masquage (slideup/slidedown -> show/hide)
 * 						  - suppression de la propriete height=auto
 */
function slideIndexBox()
{	
	if ($('index_dashboard_content')) {
		// Affichage du div de contenu de l'index
	
		if ($('index_dashboard_content').style.display == "none")
		{
			//new Effect.SlideDown($('index_dashboard_content'), {scaleX: true});
			$('index_dashboard_content').style.display = "block";
		}
		else // Masquage du div de contenu de l'index
		{
			//new Effect.SlideUp($('index_dashboard_content'), {scaleX: true});
			$('index_dashboard_content').style.display = "none";
		}
	}
}

/**
 * Gestion de l'affichage d'une table de données sous un Dashboard
 * @param xmlFilePath Chemin/Nom/Extension du fichier XML source
 * @param xmlName Nom du fichier sans le chemin nin l'extension
 */
function showDataTable( xmlFilePath, xmlName )
{
    /** @var string Préfixe du conteneur de la table de données */
    var pre = "gtm_dt_";

    // Test si le conteneur est accessible via son identifiant
    if( $( pre + xmlName ) != null )
    {
        // Test si la table de données est déja affichée
        if( $( pre + xmlName ).getStyle( "display" ) == "none"  )
        {
            // La table n'est pas affichée, on test si elle contient déja des données
            if( $( pre + xmlName ).innerHTML.length == 0 )
            {
                $( pre + xmlName + "_open" ).hide();
                $( pre + xmlName + "_wait" ).show();

                // Appel A.J.A.X. pour la génarétion de la table de données
                new Ajax.Request( "gtmDataTableGeneration.php",
                    {
                        method:"post",
                        parameters:"filename=" + xmlFilePath,
                        onSuccess: function( res )
                        {
                            $( pre + xmlName ).insert( res.responseText );
                            $( pre + xmlName ).setStyle( {"height":Math.min( parseInt( $( pre + xmlName ).getHeight() + 55 ), 450 ) + "px"} );
                            showDataTableContainer( 0, pre + xmlName );
                            $( pre + xmlName + "_open" ).hide();
                            $( pre + xmlName + "_wait" ).hide();
                            $( pre + xmlName + "_close" ).show();                            
                        }
                    });
            }
            else
            {
                // Les données ont déjà été générées... On réaffiche simplement
                $( pre + xmlName + "_open" ).hide();
                showDataTableContainer( 0, pre + xmlName );
                $( pre + xmlName + "_close" ).show();
            }
        }
        else
        {
            // La table est affichée... On la ferme
            $( pre + xmlName + "_close" ).hide();
            showDataTableContainer( 1, pre + xmlName );
            $( pre + xmlName + "_open" ).show();
        }
    }
    else
    {
        // L'élément est introuvable
        alert( "Data could not be retrieve, please refresh the page" );
    }
}

/**
 * Gestion de l'effet graphique de la table de données avec vérification de la
 * présence des méthode de la librairie Scriptaculous
 * @param mode Mode de l'effet (0 pour l'ouverture, 1 pour la fermeture)
 * @param id Identifiant du conteneur à afficher ou cacher
 */
function showDataTableContainer( mode, id )
{
    /** @var Float Durée de l'effet graphique (0.5 recommandé) */
    var effectDuration = 0.5;

    // Vérification de la présence des méthode Scriptaculous
    if( ( typeof Effect == "object" ) && ( typeof Effect.BlindDown == "function" ) )
    {
        if( mode == 0 )
        {
            Effect.BlindDown( $( id ), {duration:effectDuration} );
        }
        else
        {
            Effect.BlindUp( $( id ), {duration:effectDuration} );
        }
    }
    else
    {
        // Si pas de scriptaculous, on utilise Prototype
        if( mode == 0 )
        {
            $( id ).show();
        }
        else
        {
            $( id ).hide();
        }
    }
}
