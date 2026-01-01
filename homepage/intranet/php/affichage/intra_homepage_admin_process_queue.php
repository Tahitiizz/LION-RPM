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
 *      + déplacement de l'affichage du contenu des process de chaque famille dans le script ajax : intra_homepage_admin_ajax.inc.php
 *
 */
?>
<?
/**
* Affichage de la liste des jours et/ou heures à calculer
*
* @author MPR
* @version CB4.1.0.0
* @package Application Statistics
* @since CB2.1.2.01
*
*	maj  05/11/2008 - maxime : Suppression des balises <table> et de l'iframe
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
<?

/*

Affichage de la process queue dans la page /intranet_homepage_admin.php

2006-02-14	Stephane	Creation

*/

?>
<!-- 14/09/2010 OJT : Correction bz 16764 pour DE Firefox, ajout de la class 'box' au fieldset -->
<!-- 26/01/2011 OJT : Correction bz 20305 + optimisation script -->
<fieldset class="box">
    <legend class="texteGrisBold" style='font-size:10px;'>
        &nbsp;<img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif"/>&nbsp;&nbsp;Process Queue&nbsp;
    </legend>
    <table class="texteGris" style="font-size:10px;">
    <?php
    foreach ( $products as $product )
    {
        // Initialisation des variables
        $id_prod      = $product['sdp_id'];
        $database     = DataBase::getConnection($id_prod);
        $nb_sub       = intval( $database->getOne( "SELECT COUNT(id) FROM sys_to_compute" ) );
        $productLabel = homepageAdminCorrectProductLabel($product['sdp_label']);
        $imageAction  = '<img src="'.NIVEAU_0.'images/icones/bouton_selecteur_plus.gif" id="process_action_'.$product['sdp_id'].'" width="9" height="9" alt="+" border="0" />&nbsp;';

        // 18/04/2011 OJT : Exclusion du produit blanc dans la liste
        if( !ProductModel::isBlankProduct( $id_prod ) )
        {
            if( count( $products ) == 1 && $nb_sub <= 5 )
            {
                $imageAction = '<img src="'.NIVEAU_0.'images/icones/bouton_selecteur_moins.gif" id="process_action_'.$product['sdp_id'].'" width="9" height="9" alt="+" border="0" />&nbsp;';
            }
            echo <<<EOS
                <tr class="js_product_handle" name="process_{$product['sdp_id']}" id="process_{$product['sdp_id']}">
                <td colspan="2" class="texteGrisBoldU" style="padding-top:10px;font-size:10px;">
                <div style='float:right;font-weight:normal;text-decoration:none;'>($nb_sub)</div>
                {$imageAction}{$productLabel}
                </td>
                </tr>
EOS;
            if( count( $products ) == 1 )
            {
                echo IntraHomepageAdmin::homepageDynamicProcessInfo( $product, $nb_sub > 5 );
            }
        }
    }
    ?>
    </table>
</fieldset>