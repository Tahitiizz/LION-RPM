<?
/**
 *	@cb50412@
 *
 *	05/01/2011 - Copyright Astellia
 *
 *	Composant de base version cb_5.0.4.12
 *
 *	04/01/2011 16:57 SCT : BZ 19673 => Access to login page and admin page is very slow when in multiproduct with 5 products
 *      + ajout du script par déplacement de la méthode "print_log_ast_homepage_admin"
 *
 */
?>
<fieldset>
    <legend class="texteGrisBold" style="font-size:10px;">
        &nbsp;<img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif"/>&nbsp;&nbsp;Latest trace logs&nbsp;
    </legend>
    <div align="center">
        <table cellspacing="0" cellpadding="0" border="0" class="tracelog texteGris">
            <tr class="fondGrisClair">
                <th>Date</th>
                <th>Severity</th>
                <th>Message</th>
            </tr>
            <?php
            foreach ($products as $prod)
            {
                $imageAction  = '<img src="'.NIVEAU_0.'images/icones/bouton_selecteur_plus.gif" id="log_action_'.$prod['sdp_id'].'" width="9" height="9" alt="+" border="0" />&nbsp;';
                if( count( $products ) == 1 )
                {
                    $imageAction = '<img src="'.NIVEAU_0.'images/icones/bouton_selecteur_moins.gif" id="log_action_'.$prod['sdp_id'].'" width="9" height="9" alt="+" border="0" />&nbsp;';
                }
                echo '<tr class="js_product_handle" name="log_'.$prod['sdp_id'].'" id="log_'.$prod['sdp_id'].'"><th colspan="3" style="text-decoration:underline;padding-top:8px;text-align:left;">'.$imageAction.$prod['sdp_label'].'</th></tr>';

                // dans le cas de la famille principale, on affiche le contenu
                if( count( $products ) == 1 )
                {
                    echo IntraHomepageAdmin::homepageDynamicLogInfo($prod);
                }
            }

            ?>
        </table>
    </div>
</fieldset>