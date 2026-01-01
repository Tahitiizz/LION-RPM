<?php
/**
 * 
 *  CB 5.2
 * 
 * 09/02/2012 NSE DE Astellia Portal Lot2
 */
?><?
/*
*	@cb41000@
*
*	28/11/2008 - Copyright Acurio
*
*	Composant de base version cb_4.1.0.00
*
*	- 28/11/2008 BBX : refonte du script
*		=> Prise en charge multiproduit (si modif sur le master, déploiement sur les autres produits)
*		=> Utilisation des Models "UserModel" et "ProfileModel"
*		=> Réécriture du formulaire sans passer par les classes FPForm
*	14/05/2009 SPS :
*		- changement des messages d'erreur
*	15/05/2009 SPS :
*		- si on desactive un utilisateur, on force la valeur on_off a 0 sinon elle n'est pas mise a jour (correction bug 9589)
*		- on remplit les champs passwords avec les valeurs recuperees en base
*		- on ne peut plus saisir un mot de passe vide (correction bug 9588)
*
*	30/06/2009 BBX : décodage du mot de passe dans les champs "password" et "confirm password". BZ 10302
*
*	09/07/2009 GHX
*		- Correction du BZ10422 [REC][T&A CB 5.0][USER MANAGEMENT] : Erreur sur la date lors de la creation d'un user avec IE 6
 *
 *  13/09/2011 MMT DE PAAL1 - ajout warning synchronization users si mode CAS
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
	- maj 11/03/2008, benoit : utilisation de 'A_USER_USER_MANAGEMENT_LABEL_LAST_NAME' et 'A_USER_USER_MANAGEMENT_LABEL_FIRST_NAME' pour le nom   / prénom de l'utilisateur dans le formulaire afin d'uniformiser l'interface de saisie avec la liste des utilisateurs
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
*	- maj 20/07/2007 jérémy : 	suppression de la popup, le contenu de cette page va être intégré dans l'aplication même
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
<?
/*
*	16/06/2006 - Copyright Acurio
*
*	Composant de base version cb_1.2.0.2p
*
*	Parser version gb_1.0.0b
*/
?>
<?
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Librairies et classes requises
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/UserModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/ProfileModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/class/sms/AstSMSC.class.php');

// Connexion à la base de données
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
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

// Déclaration de la variable de gestion d'erreurs
$msg_erreur = '';

// Id du user
$idUser = isset($_GET['user_to_edit']) ? $_GET['user_to_edit'] : 0;
if(isset($_POST['user']['id_user'])) $idUser = $_POST['user']['id_user'];

// Récupération du User
$userModel = new UserModel($idUser);
if($userModel->getError() === true) {
    // 09/02/2012 NSE DE Astellia Portal Lot2
    // Si le user n'existe pas, ce n'est pas normal
    $msg_erreur = __T('A_USER_USER_MANAGEMENT_LIST_CAS_WARNING');
}

// Si le formulaire a été soumis
if(isset($_POST['user'])) {
    // 09/02/2012 NSE DE Astellia Portal Lot2 : plus de vérification des champs
	
    // Tout est ok, on place les valeurs dans l'objet
    foreach($_POST['user'] as $key=>$value) {
            $userModel->setValue($key,$value);
    }
    // On sauvegarde
    // Update User
    $userModel->updateUser();
    // Redirection vers la liste des users
    header("Location: user_index.php");

}

// Récupération des infos user
$user_values = $userModel->getValues();
// Formattage des dates
$user_values['date_valid'] = date('Y-m-d',strtotime($user_values['date_valid']));

// Gestion des messages d'erreurs.
$msg_erreur = ($msg_erreur != '') ? '<div class="errorMsg">'.$msg_erreur.'</div>' : '';

// Inclusion du header + menu contextuel
include_once(REP_PHYSIQUE_NIVEAU_0."intranet_top.php");
include_once(REP_PHYSIQUE_NIVEAU_0."php/menu_contextuel.php");
include_once(REP_PHYSIQUE_NIVEAU_0.'/api/paa/PAAAuthenticationService.php');
$PAAAuthentication = PAAAuthenticationService::getAuthenticationService();
?>
<?php
// 18/10/2011 BBX 
// BZ 24266 : Création d'une popup de sélection de produit pour choisir une conf SMSC
?>
<style type="text/css">
.stdFPComment {font-size:10px;}
#sms_test_choose_product {
    display:none;
    position:absolute;
    width:300px;
    min-height:20px;
    border:1px solid black;
    top: 50%;
    left:50%;
    margin-top: -50px;
    margin-left: -150px;
    z-index:12;
}
#sms_test_choose_product_title {
    padding:5px;
    font-weight:bold;
    border-bottom: 1px solid #898989;
}
#sms_test_choose_product_message {
    padding:5px;
    line-height:20px;
}
#sms_test_choose_product_message li {
    cursor:pointer;
}
#testsms_loader_background {
    position:fixed;
    text-align:center;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background-color:#898989;
    filter: alpha(opacity=30);
    -moz-opacity:0.3;
    opacity:0.3;
    z-index:10;
    display:none;
}
</style>

<!--[if IE 6]>
<style type="text/css">
#testsms_loader_background {
    position:absolute;
    left:0px;
    top:expression(documentElement.scrollTop + 0);
    height:expression(screen.height);
}
</style>
<![endif]-->
<?php
// Fin BZ 24266
?>
<script type="text/javascript">
    // 09/02/2012 NSE DE Astellia Portal Lot2 : il ne reste plus que la vérification du n° de tel.
    // Validation du formulaire
    function _fp_validateMyForm() {

        // Contrôle du numéro de portable (DE SMS)
        var phoneRetVal = true;
        if( $F( 'user_phone' ).length > 0 )
        {
            new Ajax.Request( '<?=NIVEAU_0?>scripts/testPhoneNumber.ajax.php',{
                method:'get',
                asynchronous:false,
                parameters:'phone_number=' + encodeURIComponent( $F( 'user_phone' ) ),
                onSuccess:function( res )
                {
                    if( res.responseText != 'ok' && res.responseText.length > 0 )
                    {
                        alert( res.responseText );
                        $('user_phone').focus();
                        phoneRetVal = false;
                    }
                }
            });
        }
        if( phoneRetVal == false ) return false;

    }

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

<?php
// 18/10/2011 BBX 
// BZ 24266 : Création d'une popup de sélection de produit pour choisir une conf SMSC
?>
<div id="testsms_loader_background">&nbsp;</div>

<div id="sms_test_choose_product" class="infoBox">
    <div id="sms_test_choose_product_title"><?=__T('A_USER_MANAGEMENT_SMSC_SELECTION')?></div>
    <div id="sms_test_choose_product_message">
    <?php
    foreach(ProductModel::getProductsWithSMSC(true) as $p)
    {
        $productModel = new ProductModel($p);
        $productInfos = $productModel->getValues();
        echo '<li onclick="chooseConfig(\''.$p.'\')">'.$productInfos['sdp_label'].'</li>';
    }
    ?>
    </div>
</div>
<?php
// FIN BZ 2426
?>
<center>
	<img src="<?=NIVEAU_0?>images/titres/new_user_titre.gif" border="0" />
	<br /><br />
    <!-- 04/08/2011 OJT : bz23283 élargissement du div -->
	<div style="width:600px;">
	<?=$msg_erreur?>
		<form name="formulaire" action="user_edit.php" method="post" onsubmit="return _fp_validateMyForm();">
		<input type="hidden" name="user[id_user]" value="<?=(($user_values['id_user'] == '') ? 0 : $user_values['id_user'])?>" />
                <?php // 09/02/2012 NSE DE Astellia Portal Lot2
                      // réorganisation de l'affichage ?>
		<table cellpadding="10" cellspacing="0" width="75%" class="tabPrincipal">
			<tr>
				<td colspan="4" align="center">
					<div id="texteGris" align="center">
						<a href="user_index.php"><b><?=__T('G_PROFILE_FORM_LINK_BACK_TO_THE_LIST')?></b></a>
					</div>
				</td>
			</tr>
			<tr>
				<td></td><td></td>
				<td><span class=texteGris><?=__T('G_PROFILE_FORM_LABEL_USER_LOGIN')?></span></td>
				<td class=texteGris><?=$user_values['login']?></td>
			</tr>
			<tr>
				<td></td>
				<td></td>
				<td><span class=texteGris><?=__T('A_USER_USER_MANAGEMENT_LABEL_USER_NAME')?></span></td>
				<td class=texteGris><?=$user_values['username']?></td>
			</tr>
			
			<tr>
				<td></td>
				<td></td>
				<td><span class=texteGris><?=__T('G_PROFILE_FORM_LABEL_EMAIL')?></span></td>
				<td class=texteGris><?=$user_values['user_mail']?></td>
			</tr>
			<tr>
                <!-- 20/07/2011 OJT : DE SMS, ajout du champ "phone_number" -->
				<td></td><td></td>
				<td><span class=texteGris><?=__T('G_PROFILE_FORM_LABEL_USER_PHONE_NUMBER')?></span></td>
				<td>
                    <!-- 04/08/2011 OJT : bz23283 ajout d'un size pour éviter le débordement -->
                    <input id="user_phone" type="text" size="15" name="user[phone_number]" value="<?=$user_values['phone_number']?>" />
                    <img id="user_phone_test" style="cursor:pointer;" src="<?=NIVEAU_0?>images/icones/icon_mobile.gif" border="0" title="<?=__T( 'SMS_TEST_BUTTON_TOOLTIP' )?>" onclick="prepareTestSMS();" />
                    <img id="user_phone_wait" style="display:none;" src="<?=NIVEAU_0?>images/animation/indicator_snake.gif" border="0"/>
                </td>
			</tr>
			
			<?php
			// 13/09/2011 MMT DE PAAL1 - ajout warning synchronization users si mode CAS
			 ?>
				<tr><td colspan="4" align="center" STYLE="padding: 7px"><table><tr>
					<td align="center">
						<img src="<?=NIVEAU_0?>images/graph/gtm_error.png" border="0" height="22" width="22" />
					</td>
					<td align="center" class="texteGris">
						<?=__T('A_USER_USER_EDIT_CAS_WARNING')?>
					</td>
				</tr></table></td>
			<tr>
                <!-- DE SMS, ajout d'une zone de réponse pour le formulaire -->
				<td colspan="4" align="center">
                    <div id="userEditResponseMsg" class="okMsg" style="display:none;"></div>
					<input type="submit" class="bouton" value="Save" />
				</td>
			</tr>
		</table>
		</form>
	</div>
</center>

	</body>
</html>
