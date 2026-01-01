<?
/**
* Homepage Admin
*
* @author MPR
* @version CB4.1.0.0
* @package Application Statistics
* @since CB1.3.0.0
*
*	maj  05/11/2008 - maxime : Suppression des balises <table> et des iframes - On les remplace par des div et include
*	06/04/2009 - modif SPS : pour les logs, on affiche au  max 45 caracteres (pbl d'affichage ie6)
*  	08/04/2009 - modif SPS : survol des lignes sous ie6 (ajout fonction javascript)
*
*	06/07/2009 BBX
*		- ajout de la fonction homepageAdminCorrectProductLabel pour traiter le label (on coue si trop long etc). BZ 9781
*      30/09/2010 NSE bz 18145 : affichage détérioré si ouverture/fermeture logs HP
 *
 *	04/01/2011 16:57 SCT : BZ 19673 => Access to login page and admin page is very slow when in multiproduct with 5 products
 *      + mise à jour des gestions de connexion à la bdd : "new Databaseconnection()" par "Database::getConnection()"
 *      + modification de la gestion javscript des dépliages de boîtes d'informations : lorsque le contenu de la boîte n'est pas chargée, on fait un appel ajax vers le script intra_homepage_admin_ajax.inc.php
 *  21/01/2011 OJT : Modification pour fonctionnement IE6;
*/

/*
2006-02-17	Stephane	MaJ : Ajout de la "launcher" window.
2006-02-14	Stephane	MaJ : Ajout de la process queue.
2005-11-04	Stephane	MaJ : Ajout de l'iFrame dans la colonne de gauche qui contient
						la liste des elements qui n'ont pas de coordonnées, NA ou NA labels.
*/

session_start();
include_once("php/environnement_liens.php");
$pgis=0;
$gis=1;
include_once("intranet_top.php");

// Fichiers nécessaires à la construction des graphes
include_once(MOD_CHARTFROMXML . "class/graph.php");
include_once(MOD_CHARTFROMXML . "class/SimpleXMLElement_Extended.php");
include_once(MOD_CHARTFROMXML . "class/chartFromXML.php");
include_once(REP_PHYSIQUE_NIVEAU_0. "/php/edw_function_family.php");
include_once(REP_PHYSIQUE_NIVEAU_0. "/php/postgres_functions.php");
include_once(REP_PHYSIQUE_NIVEAU_0. "homepage/intranet/php/affichage/intra_homepage_admin_ajax.inc.php");

/* 06/04/2009 - modif SPS : inutile*/
//$lien_css_structure=REP_PHYSIQUE_NIVEAU_0."css/homepage_admin.css";
$products = ProductModel::getActiveProducts();
$nb_tracelog = 10; // nombre de tracelog affiché sur la page d'accueil.

/*
*	06/07/2009 BBX
*	Cette fonction permet de traiter le label d'un produit. BZ 9781
*	(coupe la chaine)
*	@param string : label
*	@return string : label traité
*/
function homepageAdminCorrectProductLabel($productLabel)
{
	if(strlen($productLabel) >= 20) {
		if(substr_count($productLabel,' ') == 0)
		{
			$posU = strpos($productLabel, '_');
			$posT = strpos($productLabel, '-');
			if($posU === false) {
				if($posT === false) $pos = 10;
				else $pos = $posT;
			}
			else $pos = $posU;
			$productLabel = substr($productLabel,0,$pos).' '.substr($productLabel,$pos);
		}
	}
	return $productLabel;
}
?>

	<? /* 06/04/2009 - modif SPS : inutile => supprimer ?
	<link rel="stylesheet" type="text/css" href="<?php echo MOD_CHARTFROMXML ?>css/global_interface.css" />*/ ?>
	<script language="JavaScript" src="js/gestion_fenetre.js"></script>
	<script language="JavaScript" src="js/fonctions_dreamweaver.js"></script>
	<script type='text/javascript' src="js/gestionRequeteAjax.js"></script>
        <script type='text/javascript' src="<?=URL_NETWORK_ELEMENT_SELECTION?>js/networkElementSelection.js"></script>
	<link rel="stylesheet" href="<?=NIVEAU_0?>css/global_interface.css" type="text/css"/>
</head>
<body>
    <script type="text/javascript">
    var requeteAjax;
    var tabServerInfo = new Array();
    var tabBoiteInfo = new Array();
    tabBoiteInfo[0] = 'network';
    tabBoiteInfo[1] = 'topology';
    tabBoiteInfo[2] = 'process';
    tabBoiteInfo[3] = 'log';
    tabBoiteInfo[4] = 'system';
    </script>
<div class="page">
	<!-- Network information + Topology-->
	<div class="left_column"  >
		<!-- Network Information -->
		<?php	include("homepage/intranet/php/affichage/intra_homepage_admin_indicateur_6.inc.php"); ?>
		<!-- Topology -->
		<?php	include_once("homepage/intranet/php/affichage/intra_homepage_admin_show_topology_errors.php"); ?>
	</div>

	<!-- Application Statistics + Lastest trace logs-->
	<div class="center_column">
		<!-- Application Statistics -->
		<?php	include_once("homepage/intranet/php/affichage/intra_homepage_admin_indicateur_2.inc.php"); ?>
		<br/>
		<!-- Latest trace logs -->
            <?php	include_once("homepage/intranet/php/affichage/intra_homepage_admin_show_application_logs.php"); ?>
			</div>

	<div class='right_column'>
        <!-- 14/09/2010 OJT : Correction bz 16764 pour DE Firefox, suppression des div -->
        <!-- Process Started -->
        <?php	include_once('homepage/intranet/php/affichage/intra_homepage_admin_process_window.php'); ?>

		<!-- Process Queue -->
        <?php	include_once('homepage/intranet/php/affichage/intra_homepage_admin_process_queue.php'); ?>

		<!-- Disk Space-->
		<?php	include_once('homepage/intranet/php/affichage/intra_homepage_admin_indicateur_3.inc.php'); ?>
	</div>
</div>
<style type="text/css">
table.tracelog tr:hover {
	background-color:#FFCA43;
}
</style>
<script type="text/javascript">
<?php /* 08/04/2009 - modif SPS : survol des lignes sous ie6 */?>
<!--hover sur les lignes du tableau de logs(seulement pour ie6) -->
//on teste si on est sur ie6
if (!window.XMLHttpRequest)  {
    //quand la page est chargee, on applique la fonction suivante
	Event.observe(window, 'load', function() {
        $$('table.tracelog tr').each( function(e) {
            //a chaque survol de ligne, on change la couleur
			Event.observe(e, 'mouseover', function() {
                $(e).setStyle({ backgroundColor: '#FFCA43'});
            });
			//quand on ne survole plus, on remet la couleur initiale
            Event.observe(e, 'mouseout', function() {
                $(e).setStyle({ backgroundColor: 'transparent'});
            });
        });
    });
}

// cette fonction cache / affiche les petits-frères d'un élément produit
// 30/09/2010 NSE bz 18145 : remplacement display='block' par ''
        function show_hide_following_tds( id )
        {
            var currentElt = $( id );
            var tempProduitDemande = id.split( '_' );
            var idProduitDemande = tempProduitDemande[1];
            var cpt = 0;

            // Si le bloc n'est pas chargé, on effectue les mises à jour de l'ensemble des blocs nécessaires pour le produit
            // On detecte qu'un bloc n'est pas chargé en regardant si l'élément suivant à un attribut "name"
            if( currentElt.next() == null || currentElt.next().hasAttribute( 'name' ) == true )
            {
                for( cpt = 0; cpt < tabBoiteInfo.length; cpt++)
                {
                    if( $( tabBoiteInfo[cpt] + '_' + idProduitDemande ) )
                    {
                        // On modifie l'icone du groupe
                        var imageActionProduit = tabBoiteInfo[cpt] + '_action_' + idProduitDemande;
                        $( imageActionProduit ).src = 'images/animation/indicator_snake.gif';

                        // Recherche des paramètres à afficher
                        appelAjax( tabBoiteInfo[cpt], idProduitDemande, tabBoiteInfo[cpt] + '_' + idProduitDemande, imageActionProduit );
                    }
                }
            }

            // Le bloc existe, on va effectuer l'action sur les 5 boîtes : affichage ou masquage
            // L'affichage ou le masquage est décidé avant l'appel pour toutes les boites (bz20305)
            else
            {
                var action = "hide";
                if( currentElt.next() == null || currentElt.next().style.display == 'none' ){
                    action = "show";
                }
                for( cpt = 0; cpt < tabBoiteInfo.length; cpt++ )
                {
                    // Test si l'élément existe (les produits ne sont tous listés dans toutes les box)
                    if( $( tabBoiteInfo[cpt] + '_' + idProduitDemande ) )
                    {
                        show_hide_following_tds_action( action, $( tabBoiteInfo[cpt] + '_' + idProduitDemande ).next(), tabBoiteInfo[cpt] + '_action_' + idProduitDemande );
                    }
                }
            }
            moveTo( $( 'container_network' ), $( 'network_' + idProduitDemande ) );
        }

        function appelAjax(localType, localIdProduitDemande, localElementMaj, localImageActionProduit)
        {
            var skypeAjax = 0;
            var ipServeur = '';
            // Dans le cas d'un appel system, on verifie que les infos n'ont pas déjà été demandée
            if(localType == 'system')
            {
                // Appel ajax pour vérifier que le système n'a pas déjà été demandé (récupération de l'IP)
                requeteAjax = new Ajax.Request('homepage/intranet/php/affichage/intra_homepage_admin_ajax.inc.php',{
                    method:'post',
                    asynchronous:'false',
                    parameters:'type=systemInfo&idProduit='+localIdProduitDemande,
                    onSuccess: function(res) {
                        ipServeur = res.responseText;
                        // On vérifie que le résultat n'a pas déjà été récupéré
                        if(typeof(tabServerInfo[res.responseText]) !== 'undefined')
                        {
                            Element.insert($(localElementMaj), {after: tabServerInfo[ipServeur]});
                            $(localImageActionProduit).src='images/icones/bouton_selecteur_moins.gif';
                            skypeAjax = 1;
                        }
                    }
                });
            }
            if ( skypeAjax == 0 )
            {
                requeteAjax = new Ajax.Request('homepage/intranet/php/affichage/intra_homepage_admin_ajax.inc.php',{
                    method:'post',
                    asynchronous:'false',
                    parameters:'type='+localType+'&idProduit='+localIdProduitDemande,
                    onSuccess: function(res) {
                        Element.insert($(localElementMaj), {after: res.responseText});
                        $(localImageActionProduit).src='images/icones/bouton_selecteur_moins.gif';
                        if(localType == 'system')
                        {
                            tabServerInfo[ipServeur] = res.responseText;
                        }
                    }
                });
            }
        }

        // cette fonction est l'ancienne méthode "show_hide_following_tds" : les actions d'affichage et masquage sont déplacées afin de pouvoir être appelées depuis les 5 blocs lors du clic
        // me = this.next()
        // imageAction : nom de l'image à modifier : + <=> -
        // 26/01/2011 OJT : Ajout du paramètre action qui decide de l'affichage ou du masquage (bz20305)
        function show_hide_following_tds_action( action, me, imageAction )
        {
            // dans le cas où l'élément suivant est caché : on l'affiche
            if ( action == "show" && me.style.display == 'none' )
            {
		// on montre
		me.style.display = '';
                $(imageAction).src='images/icones/bouton_selecteur_moins.gif';
		while (me.next()) {
			me = me.next();
			if (me.className == 'js_product_handle') return;
			me.style.display = '';
		}
            // dans le cas où l'élément suivant est affiché : on le masque
            }
            else if( action == "hide" && me.style.display != 'none' )
            {
		// on cache
		me.style.display = 'none';
                $(imageAction).src='images/icones/bouton_selecteur_plus.gif';
		while (me.next()) {
			me = me.next();
			if (me.className == 'js_product_handle') return;
			me.style.display = 'none';
		}
	}
}

// attache la fonction précédente à tous les éléments de classe .js_product_handle :
//	- les noms de produit dans la process queue
//	- les noms de produit dans la boite disk space
handles = $$('.js_product_handle');
for (i=0; i<handles.length; i++) {
            handles[i].onclick = function(){show_hide_following_tds( this.id );}
}
</script>
<style type="text/css">
.js_product_handle { cursor:pointer; }
</style>
</body>
</html>
