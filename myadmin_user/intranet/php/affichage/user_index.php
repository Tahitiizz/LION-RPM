<?php
/**
 * CB 5.3.1
 * 
 * 15/04/2013 NSE Phone number managed by Portal
 * 
 */
?><?php
/**
 * 
 *  CB 5.2
 * 
 * 07/02/2012 NSE DE Astellia Portal Lot2
 */
?><?php
/**
 * @cb51000@
 * 01/07/2010 - Copyright Astellia
 * BZ16310 : Ajout du login dans la liste des utilisateurs
 */
?>
<?php
/*
*	@cb41000@
*
*	28/11/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	- 28/11/2008 BBX : Reprise de 90% du script
*		=> Utilisation du model "UserModel"
*		=> simplification du code
*		=> utilisation des nouvelles variables globales
*/
?>
<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	- maj 15/04/2008 Benjamin : modification du process du suppression d'un user
*	- maj 15/04/2008 Benjamin : ajout de la gestion de l'affiche d'un éventuel message d'erreur
	- maj 11/03/2008, benoit : suppression d'un point d'interrogation en doublon dans le message de confirmation de suppression
 * - maj 13/09/2011 MMT DE PAAL1 - ajout warning synchronization users si mode CAS
*
*/
?>
<?
/*
*	@cb30000@
*
*	20/07/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- maj 20/07/2007 jérémy : 	suppression de l'iframe et intégration du code source du fichier user_list.php
*						Suppression des champs INPUT pour les informations sur les utilisateurs
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
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
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Librairies et classes requises
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/UserModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/GroupModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/ProfileModel.class.php');

// Connexion à la base de données
$database = Database::getConnection();

// 27/07/2010 OJT : Correction bz16899, mise en commentaire du contrôle d'accès
/*// Contrôle d'accès
$userModel = new UserModel($_SESSION['id_user']);
$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu ILIKE 'User Management'";
$result = $database->getRow($query);
$idMenu = ($result['id_menu'] == '') ? 0 : $result['id_menu'];
if(!$userModel->userAuthorized($idMenu)) {
	include_once(REP_PHYSIQUE_NIVEAU_0."intranet_top.php");
	echo '<div class="errorMsg">'.__T('A_USER_NOT_ALLOWED_TO_ACCESS').'</div>';
	exit;
}*/

// 07/02/2012 NSE DE Astellia Portal Lot2
// suppression de la gestion des utilisateurs

// Intranet top + menu contextuel
include_once(REP_PHYSIQUE_NIVEAU_0 . "intranet_top.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/menu_contextuel.php");

// 13/09/2011 MMT DE PAAL1 - ajout warning synchronization users si mode CAS
include_once(REP_PHYSIQUE_NIVEAU_0 . '/api/paa/PAAAuthenticationService.php');
$PAAAuthentication = PAAAuthenticationService::getAuthenticationService();

// 15/04/2013 NSE Phone number managed by Portal
// Ajout du bouton pour tester l'envoie de SMS
?>
<script lang="javascript">
    /**
     * Fonction appellé au clic sur l'icone de test SMS. Cette fonction effectue
     * un appel A.J.A.X envoyant un SMS de test.
     *
     * 05/08/2011 OJT : bz23259, ajout d'un random en GET
     * 18/10/2011 BBX BZ 24266 : ajout du produit en paramètre
     */
    function sendTestSMS(product)
    {
        $( 'user_phone_test' ).hide();
        $( 'user_phone_wait' ).show();
        $( 'userEditResponseMsg' ).update();
        $( 'userEditResponseMsg' ).hide();

        var dateObj = new Date();
        new Ajax.Request( '<?=NIVEAU_0?>scripts/sendTestSMS.ajax.php',{
            method:'get',
            parameters:'action=launchTest&product='+product+'&phone_number=' + encodeURIComponent( $F( 'user_phone' ) ) + '&rand=' + dateObj.getTime(),
            onSuccess:function( res )
            {
                var msg      = res.responseText;
                var divClass = 'okMsg';
                if( msg.length > 0 )
                {
                    // Si le message ne commence pas par ok, il s'agit d'une erreur
                    if( !msg.startsWith( 'ok|' ) )
                    {
                        divClass = 'errorMsg'
                    }
                    $( 'userEditResponseMsg' ).className = divClass;
                    $( 'userEditResponseMsg' ).update( msg.replace( 'ok|', '' ) );
                    $( 'userEditResponseMsg' ).show();
                    setTimeout( '$( \'userEditResponseMsg\' ).hide();', 5000 );
                }

                // Réinitialisation des icones
                $( 'user_phone_test' ).show();
                $( 'user_phone_wait' ).hide();
            }
        });
    }
    
    /**
     * Fonction qui va regarder si il y a des conflits de configuration SMSC
     * entre tous les produits.
     * 18/10/2011 BBX BZ 24266
     */
    function prepareTestSMS()
    {
        var dateObj = new Date();
        new Ajax.Request('<?=NIVEAU_0?>scripts/sendTestSMS.ajax.php',{
            method:'get',
            parameters:'action=identifyProducts&rand=' + dateObj.getTime(),
            onSuccess:function( res ) {
                productList = res.responseText.split('|');  
                if(productList == '') {
                    alert('<?=__T('A_USER_MANAGEMENT_SMSC_NO_SMSC')?>');
                }
                else if(productList.length > 1) {
                    $('sms_test_choose_product').setStyle({display:'block'});
                    $('testsms_loader_background').setStyle({display:'block'});
                }
                else {
                    sendTestSMS(productList[0]);
                }                
            }
        });
    }
    
    /**
     * Fonction permettant de sélectionner un produit à utiliser comme conf SMSC
     * 18/10/2011 BBX BZ 24266
     * @param integer product
     */
    function chooseConfig(product)
    {
        $('sms_test_choose_product').setStyle({display:'none'});
        $('testsms_loader_background').setStyle({display:'none'});
        sendTestSMS(product);
    }
</script>
        
<center>
	<img src="<?=NIVEAU_0?>images/titres/user_management_titre.gif" border="0" alt="User Management" />
	<br /><br />
	<div style="width:850px;">
		<?=$msg_erreur?>
		<form name="formulaire" action="user_index.php" method="post">
			<table width="100%" border="0" class="tabPrincipal">
				<tr>
					<td align="center">
						<table width="710" cellpadding="2" cellspacing="2" border="0">
							<?php
							// 07/02/2012 NSE DE Astellia Portal Lot2
                                                        // modification du message 
							?>
								<tr><td colspan="5" align="center" STYLE="padding: 7px"><table><tr>
									<td align="center">
										<img src="<?=NIVEAU_0?>images/icones/info.png" border="0" height="25" width="25" />
									</td>
									<td align="center" class="texteGris">
										<?php
                                                                                // 15/04/2013 Phone Number On Portal
                                                                                $phoneNumberOnPortal = $PAAAuthentication->doesPortalServerApiManage('phone_number');
                                                                                if($phoneNumberOnPortal)
                                                                                    echo __T('A_USER_USER_MANAGEMENT_FULLY_LIST_CAS_WARNING');
                                                                                else
                                                                                    echo __T('A_USER_USER_MANAGEMENT_LIST_CAS_WARNING');
                                                                                ?>
									</td>
								</tr></table></td></tr>
							<?php
							// 07/02/2012 NSE DE Astellia Portal Lot2
                                                        // réorganisation de l'affichage
							?>
                                                        <tr>
								<td colspan="5" align="center"></td>
							<tr><tr>
								<td colspan="5" align="center">
                                                                    							
                            <?php
							// 16/07/2013 MGO bz 27170
							if (PAA_SERVICE != PAAAuthenticationService::$TYPE_CAS) { ?>
                                <div title="<?php echo __T('A_USER_USER_MANAGEMENT_BTN_SYNCHRO_USER_DISABLED'); ?>" ><input type="submit" name="synchro" id="synchro" value="<?php echo __T('A_USER_USER_MANAGEMENT_BTN_SYNCHRO_USER'); ?>" class="bouton" disabled /></div>
							<?php
							}
							else {?>
                                <input type="submit" name="synchro" id="synchro" value="<?php echo __T('A_USER_USER_MANAGEMENT_BTN_SYNCHRO_USER'); ?>" class="bouton" />
								<?php

									if (isset($_POST['synchro'])) {
										$ret = UserModel::updateLocalUsersList();
										if($ret == "no user on PAA" )
											echo '<div class="errorMsg">'.__T('A_USER_USER_MANAGEMENT_NO_USER_ON_PAA').'</div>';
									}
								}?>                                                                
                                </td>
							<tr>        
							<tr>
								<td colspan="5" align="center"></td>
							<tr>
                                <th class="texteGrisBold" width="150px"><? echo __T('A_USER_USER_MANAGEMENT_LABEL_LOGIN'); ?></th>
								<th class="texteGrisBold" width="150px"><? echo __T('A_USER_USER_MANAGEMENT_LABEL_USER_NAME'); ?></th>
								<th class="texteGrisBold" width="190px"><? echo __T('G_PROFILE_FORM_LABEL_EMAIL'); ?></th>
								<th class="texteGrisBold" width="150px"><? echo __T('A_USER_USER_MANAGEMENT_LABEL_USER_PHONE_NUMBER'); ?></th>
								<th class="texteGrisBold">&nbsp;</th>
							</tr>
							<?php
								$j = 0;
								foreach(UserModel::getUsers() as $info_user) {
									$style_admin = ($info_user["profile_type"] == "admin") ? "style=\"border : 1px solid #FFA243;\"" : "";
									$style_row = ($j%2 == 0) ? "bgcolor=#DDDDDD" : "bgcolor=#ffffff";
							?>
									<tr class="texteGris">
                                                                                <td <?=$style_row?> <?=$style_admin?>><? echo $info_user["login"]; ?></td>
										<td <?=$style_row?>><? echo $info_user["username"]; ?></td>
										<td <?=$style_row?>><? echo $info_user["user_mail"]; ?></td>
										<td <?=$style_row?>><? echo $info_user["phone_number"]; ?></td>
										<td <?=$style_row?>><?php
                                                                                // 15/04/2013 NSE Phone number managed by Portal
                                                                                    if($phoneNumberOnPortal){
                                                                                        // lien pour éditer l'utilisateur
                                                                                        ?>
                                                                                        <img id="user_phone_test" style="cursor:pointer;" src="<?=NIVEAU_0?>images/icones/icon_mobile.gif" border="0" title="<?=__T( 'SMS_TEST_BUTTON_TOOLTIP' )?>" onclick="prepareTestSMS();" />
                                                                                        <img id="user_phone_wait" style="display:none;" src="<?=NIVEAU_0?>images/animation/indicator_snake.gif" border="0"/>
                                                                                        <?php
                                                                                    }
                                                                                    else{
                                                                                        // lien pour tester l'envoie de SMS
                                                                                        ?><a title="Edit user" href="user_edit.php?user_to_edit=<? echo $info_user["id_user"]; ?>" >
											  <img src="<?=NIVEAU_0?>images/icones/A_more.gif" border="0" alt="Edit user" >
											</a><?php
                                                                                    }?>
										</td>
									</tr>
							<?php
									$j++;
								}
							?>
						</table>
						<br />
					</td>
				</table>
		</form>
	</div>
</center>

	</body>
</html>
