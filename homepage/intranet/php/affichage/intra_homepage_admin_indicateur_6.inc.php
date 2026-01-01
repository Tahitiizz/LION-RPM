<?
/**
 *	@cb50414@
 *
 *	04/01/2011 - Copyright Astellia
 *
 *	Composant de base version cb_5.0.4.12
 *
 *	04/01/2011 16:57 SCT : BZ 19673 => Access to login page and admin page is very slow when in multiproduct with 5 products
 *      + mise à jour des gestions de connexion à la bdd : "new Databaseconnection()" par "Database::getConnection()"
 *      + déplacement de l'affichage du contenu des NA de chaque famille dans le script ajax : intra_homepage_admin_ajax.inc.php
 *
 */
?>
<?
/*
*	@cb41000@
*
*	03/11/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.0
*
*	maj 12:10 03/11/2008 - Maxime :  Il y a maintenant une seule table de référence edw_object_ref
*

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
<?php

// petite modif cosmétique (une seule var par ligne au lieu de 3) -- sls 04/11/2005
// modif sls 01/03/2006 - verification que les network aggregation sont bien déployés (presents dans edw_object_1_ref)
?>

<fieldset class='box'>
    <legend class="texteGrisBold" style='font-size:10px;'>
        <img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif"/><?=__T('A_HOMEPAGE_ADMIN_NETWORK_INFORMATIONS')?>
    </legend>
    <?php
    if (count($products)>0)
    {
        echo "<div class='list_network_informations' id='container_network'><table>";
        foreach( $products as $infos )
        {
            $id_prod = $infos['sdp_id'];

            // 18/04/2011 OJT : Exclusion du produit blanc dans la liste
            if( !ProductModel::isBlankProduct( $id_prod ) )
            {
                // On réinitialise la base de donnée en fonction du produit
                // 04/01/2011 17:06 SCT : BZ 19673 => remplacement de "new Databaseconnection()" par "Database::getConnection()"
                //$database = Database::getConnection($id_prod);

                // 06/07/2009 BBX : ajout d'un colspan + calcul de la coupe du label. BZ 9781
                $productLabel = homepageAdminCorrectProductLabel($infos['sdp_label']);
                $imageAction  = '<img src="'.NIVEAU_0.'images/icones/bouton_selecteur_plus.gif" id="network_action_'.$infos['sdp_id'].'" width="9" height="9" alt="+" border="0" />&nbsp;';
                if( count( $products ) == 1)
                {
                    $imageAction = '<img src="'.NIVEAU_0.'images/icones/bouton_selecteur_moins.gif" id="network_action_'.$infos['sdp_id'].'" width="9" height="9" alt="+" border="0" />&nbsp;';
                }
                echo '<tr class="js_product_handle" name="network_'.$infos['sdp_id'].'" id="network_'.$infos['sdp_id'].'"><td class="texteGrisBoldU" style="padding-top:10px;font-size:10px;">'.$imageAction.$productLabel.'</td></tr>';

                // dans le cas de la famille principale, on affiche le contenu
                if( count( $products ) == 1)
                {
                    echo IntraHomepageAdmin::homepageDynamicNetworkInfo($infos);
                }
            }
        }
        echo "</table></div>";

    }
    else
    {
    ?>
    <div class="texteGrisBold">No Products.</div>

    <?	} ?>
</fieldset>
