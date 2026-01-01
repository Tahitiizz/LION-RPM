<?
/**
 *	@cb50414@
 *
 *	05/01/2011 - Copyright Astellia
 *
 *	Composant de base version cb_5.0.4.12
 *
 *	05/01/2011 10:48 SCT : BZ 19673 => Access to login page and admin page is very slow when in multiproduct with 5 products
 *      + mise à jour des gestions de connexion à la bdd : "new Databaseconnection()" par "Database::getConnection()"
 *      + remplace de pg_fetch_array par database->getOne
 *      + mise en dur du nom des tables de topologie (à la place de la définition dans une variable utilisée à un seul endroit
 *      + mise en commentaire de l'appel de la méthode get_main_family
 *      + le gis est automatiquement activé sur chaque produit => on prend la valeur du master (ce qui était déjà fait)
 *      + déplacement de l'affichage du contenu de la topologie de chaque famille dans le script ajax : intra_homepage_admin_ajax.inc.php
 *
 */
?>
<?
/*
*	@cb41000@
*
*	14/03/2007 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.0
*
*	03/11/2008 - Maxime : La table de référence est maintenant edw_object_ref_parameters
*	06/11/2008 - Maxime : On boucle sur tous les produits
*	16/04/2009 - modif SPS : ajout des balises manquantes <acronym> et <span>
*	03/06/2009 BBX : on se base désormais sur longitude et lattitude pour compter. BZ 9902
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

	- maj 01/06/2007, benoit : désactivation des infos x,y si le gis est désactivé

*/
?>
<?
/*

Cette page est incluse dans un iFrame de la colonne de droite de la page
http://192.168.0.2/iu_114/index_page.php?file_a_charger=intranet_homepage_admin.php

Elle liste les elements n'ayant pas de coordonnées, NA, ou NA label.

-- sls 04/11/2005

*/
?>

<style type="text/css">acronym { border-bottom: 1px dotted gray; }</style>

<fieldset class='box'>
    <legend class="texteGrisBold" style='font-size:10px;'>
        <img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>&nbsp;Topology
    </legend>
    <table width="100%" height="100%" border="0" cellpadding="2" cellspacing="0" valign="top" style="margin:4px 0;">
    <?php
        if (count($products)>0)
        {
            // 05/01/2011 11:03 SCT : BZ 19673 => le gis est automatiquement activé sur chaque produit => on prend la valeur du master (ce qui était déjà fait)
            // on cherche les elements qui n'ont pas de coordonnées
            // 01/06/2007 - Modif. benoit : si le gis est désactivé, on désactive les infos sur les x, y
            $gis_activated = get_sys_global_parameters('gis');

            foreach( $products as $infos )
            {
                // 18/04/2011 OJT : Exclusion du produit blanc dans la liste
                if( !ProductModel::isBlankProduct( $infos['sdp_id'] ) )
                {
                    // 04/01/2011 17:06 SCT : BZ 19673 => remplacement de "new Databaseconnection()" par "Database::getConnection()"
                    //$database = Database::getConnection($infos['sdp_id']);

                    // 06/07/2009 BBX : ajout d'un colspan + calcul de la coupe du label. BZ 9781
                    $productLabel = homepageAdminCorrectProductLabel($infos['sdp_label']);
                    $imageAction  = '<img src="'.NIVEAU_0.'images/icones/bouton_selecteur_plus.gif" id="topology_action_'.$infos['sdp_id'].'" width="9" height="9" alt="+" border="0" />&nbsp;';
                    if( count( $products ) == 1 )
                    {
                        $imageAction = '<img src="'.NIVEAU_0.'images/icones/bouton_selecteur_moins.gif" id="topology_action_'.$infos['sdp_id'].'" width="9" height="9" alt="+" border="0" />&nbsp;';
                    }
                    echo '<tr class="js_product_handle" name="topology_'.$infos['sdp_id'].'" id="topology_'.$infos['sdp_id'].'"><td colspan="2" class="texteGrisBoldU" style="padding-top:10px;font-size:10px;">'.$imageAction.$productLabel.'</td></tr>';

                    //$main_family = get_main_family();
                    // 15:03 03/11/2008 - Maxime : La table de référence est maintenant edw_object_ref_parameters
                    //$object_ref  = "edw_object_ref";
                    //$object_ref_parameters  = "edw_object_ref_parameters";
                    if( count( $products ) == 1 )
                    {
                        echo IntraHomepageAdmin::homepageDynamicTopologyInfo($infos);
                    }
                }
            }
        } else { ?>
            <div class="texteGrisBold">No Products.</div>
    <?	} ?>
    </table>
</fieldset>

