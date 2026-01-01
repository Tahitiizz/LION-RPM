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
 * 09/02/2012 NSE DE Astellia Portal Lot2
 */
?><?
/**
 * @cb5100@
 * 2010 - Copyright Astellia
 *
 * 23/07/2010 OJT : Correction bz15544
 */
/*
*	@cb41000@
*
*	27/11/2008 - Copyright Astellia
*
*	Refonte de la majorité du traitement :
*		- Utilisation des nouvelles classes, méthodes et variables
*		- Traitement de l'enregistrement dans ce fichier
*
*	29/01/2009 GHX
*		- modification de la requete SQL pour mettre la valeur id_user entre cote [REFONTE PARSER]
*
*	08/04/2009 BBX
*		- Intégration de la nouvelle fenêtre de sélection des éléments réseau

*	22/07/2009 GHX
*		- Correction du BZ 10427 [REC][T&A Cb 5.0][User Profile] : le choix d'éléments réseaux dans l'interface Navigation / My Profile ne fonctionne pas
*			-> La liste des éléments réseaux sélectionnés se trouve en POST et plus en SESSION
*			-> On affichait une icone vert quand on avait rien en base et vice-versa quand on arrivait sur la page.
*
*	31/08/2009 MPR :
*		- Correction du bug 11291
*
*	28/10/2009 BBX : correction du CSS pour le bouton de sélection des NE afin qu'il ne disparaisse pas sous IE6. BZ 11886
*
*	23/02/2010 NSE bz 14134
*		Ajout de deux items Homepage et Default dashboard.
*		Suppression du texte "Dashboard" devant la liste des Dash/homepage.
*		Désactivation du bouton « Setup filter » si c'est l'item homepage qui est sélectionné.
*       03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
 *      13/09/2010 NSE bz 17808 : champ mot de passe pré-rempli
 *   10/06/2011 MMT bz 22535 n'affiche pas les elements 3eme axe dans mes favoris
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
*	- maj 06/12/2007 christophe : modification de la requête $query_dash qui allait chercher dans la table sys_pauto_config
*		une requête  ave comme condition : "where class_object = 'page' and family_name = 'Designer_dashboard' LIMIT 1"
*		le champ family_name peut évoluer et changer, il ne faut donc pas se baser dessus, de plus la requête récupérée évolue
*		dans le CB 4.X et ne correspond plus au besoin.
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- maj  14:54 27/09/2007 Gwen : Si "None" est sélectionné, le dashboard de la homepage par défaut ne doit pas être sélectionné.
*	- maj 10:03 06/09/2007 Gwénaël : affichage d'un message d'information
*	- 03/09/2007 christophe : si l'icone n'existe pas, c'est que l'on est dans my profile de la partie admin, on retourne true.
*	- maj 15/06/2007 Gwénaël :
*		- suppression de l'utilisation de la class phpObjectForms par du simple html/css
*		- ajout de la partie pour personnalisé ça homepage
*		- utilisation de la fonction __T
*	- maj 27/06/2007 christophe : intégration de l'interface 'Network element preferences'.
*	- 14/08/2007 christophe : ajout de l'initialisation de la variables $_SESSION["selecteur_general_values"]["list_of_na"]
*
*BUGS DE QUALIF
*
*	24/08/2007 - JL : Ajout de 2 fonctions :
*					une pour modifier l'icone SETUP FILTER lorsque l'on selectionne une autre dashboard
*					l'autre pour vérifier que le setup filter est bien paramétré lors de l'envoi du formulaire
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
/*
	- maj 23 05 2006 christophe : si $repertoire_physique_niveau0 est perdu, on redirige vers la page d'accueil.
	- maj 29 05 2006 sls : ajout de la contrainte a-zA-Z0-9_ sur le mot de passe (ligne 252 et 253)
	- maj 06 07 2006 xavier : la vérification se fait sur le seul login plutôt que sur le couple login/password. ligne 36
*/
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Librairies et classes requises
require_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php");
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/DashboardModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/SelecteurModel.class.php');

// Inclusion du header
include_once(REP_PHYSIQUE_NIVEAU_0."intranet_top.php");

// maj 27/06/2007 christophe
//affichage du choix des network aggregation.
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/genDashboardNaSelection.class.php");

require_once(MOD_NETWORK_ELEMENT_SELECTION.'class/networkElementSelection.class.php');

// Connexion à la base de données
$database = Database::getConnection();

// On cherche les infos de l'utilisateur
$query = "SELECT * FROM users WHERE id_user = '{$_SESSION['id_user']}'";
$info_user = $database->getRow($query);
if (strlen($info_user['date_creation'])==8) {
	$info_user['date_creation'] = substr($info_user['date_creation'],0,4).'-'.substr($info_user['date_creation'],4,2).'-'.substr($info_user['date_creation'],6);
}
if (strlen($info_user['date_valid'])==8) {
	$info_user['date_valid'] = substr($info_user['date_valid'],0,4).'-'.substr($info_user['date_valid'],4,2).'-'.substr($info_user['date_valid'],6);
}

// 21/12/2010 BBX
// Produits désactivés
// BZ 18510
$inactiveProducts = ProductModel::getInactiveProducts();

//********
// Traitement du formulaire
if(isset($_POST) && ($_POST['myFormSubmitIndicator'] == true)) {
	// Vérification de l'unicité du login
	$query = "SELECT id_user FROM users WHERE login='{$_POST['user_login']}' AND id_user != '{$_SESSION['id_user']}'";
	if (count($database->getAll($query)) > 0) {
		echo '
		<script type="text/javascript">
			alert("'.__T('A_JS_USER_MANAGEMENT_LOGIN_ALREADY_USED',$_POST['user_login']).'");
		</script>';
	}
	else {
            // 09/02/2012 NSE DE Astellia Portal Lot2
            // on ne met à jour QUE le téléphone
		// Requête de mise à jour des infos utilisateur
                // 20/07/2011 OJT : DE SMS, ajout du phone_number
		$query="UPDATE users
		SET phone_number='".$_POST['phone_number']."'";

		// 15:00 22/07/2009 GHX
		// Correction du BZ 10427
		// Les éléments réseaux sélectionnés se trouvent en POST et plus en SESSION
		if ( !empty($_POST['selecteur']['nel_selecteur']) )
		{
			$query .= ",network_element_preferences='{$_POST['selecteur']['nel_selecteur']}'";
		}
		else
		{
			$query .= ",network_element_preferences=NULL";
		}

		// 29/01/2009 GHX
		// modification de la requete SQL pour mettre la valeur id_user entre cote
		$query .= "	WHERE id_user = '{$_SESSION['id_user']}'";
		// Exécution de la requête
		$database->execute($query);
		// Si le dashboard est sur None, on supprime les éventuelles saisies précédentes
		// 23/02/2010 14134 NSE ajout de "ou si homepage personnalisée"
		if($_POST['homepage'] == 'none' or $_POST['homepage'] === '' or $_POST['homepage'] == -1) {
			$id_selecteur = SelecteurModel::getUserHomepage($_SESSION['id_user']);
			$selecteur = new SelecteurModel($id_selecteur);
			if(!$selecteur->getError()) {
				// Si le sélecteur existe, on le supprime
				$selecteur->deleteSelecteur();
			}
			//NSE 14134 23/02/2010
			// si l'utilisateur a sélectionné la homepage personnalisée
			// on le mémorise dans ses préférences (ce n'est pas fait via le sélecteur car il est désactivé dans ce cas).
			if($_POST['homepage'] == -1){
				$query = "UPDATE users SET homepage = '".$_POST['homepage']."' WHERE id_user = '{$_SESSION['id_user']}'";
			}
			else{
				// On supprime l'id homepage
				$query = "UPDATE users SET homepage = '' WHERE id_user = '{$_SESSION['id_user']}'";
			}
			$database->execute($query);
		}
		// 30/01/2009 - SLC - déploiement de la modifs sur les produits actifs
		UserModel::deployUsers();
		// 30/01/2009 - SLC - on fait une redirection, parce que sinon le formulaire n'affiche PAS les modifs qui viennent d'être effectuées
		echo "<script type='text/javascript'>document.location = './user_edit_me.php?no_loading={$_GET['no_loading']}&id_menu_en_cours={$_GET['id_menu_en_cours']}&reload=".date('U')."';</script>";
		//exit;
	}
}
//********

// 16/01/2014 GFS - Bug 38614 - [SUP][T&A Gateway][AVP 40767][MCI Iran] : Every dashboards are available in homepage configuration
$query = "SELECT profile_to_menu FROM profile WHERE id_profile = '{$_SESSION['user_profil']}'";
$menu_list = explode("-", $database->getOne($query));

//Si c'est un USER il a la possibilié de configurer sa homepage
if ( $_SESSION['profile_type'] == 'user' ) {
	// Déclaration de l'image détail
	$icone_filter = "detail_vert.gif";
	// Déclaration de l'id page
	$id_page = 0;
	//NSE 14134 23/02/2010
	// si l'utilisateur a sélectionné la homepage personnalisée, on récupère cet "identifiant"
	if($info_user['homepage']==-1){
		$id_page = $info_user['homepage'];
	}
	else{
		// Récupération de sa Homepage
		$id_selecteur = ($info_user['homepage'] != 'none' and $info_user != null) ? $info_user['homepage'] : 0;

		// Instanciation du sélecteur
		$selecteur = new SelecteurModel($id_selecteur);
		// Si le sélecteur existe, on récupère ses informations
		if(!$selecteur->getError()) {
			$selecteur_data = $selecteur->getValues();
			// Récupération du Dashboard correspondant
			$id_page = $selecteur_data['id_page'];

		}
	}
	// Création du sélecteur des dashboard
	// maj 02/07/2009 - MPR : Correction du bug 10348 : on remplace == par ===
	// 23/01/2010 NSE bz 14134 on supprimer none et on ajoute homepage et default dashboard
	// la homepage installée, si elle existe
	if(file_exists(REP_PHYSIQUE_NIVEAU_0.'homepage/index.php') || file_exists(REP_PHYSIQUE_NIVEAU_0.'homepage/index.html')){
		$list_dashboards.= '<option value="-1" '. (( $id_page == -1) ? 'selected="selected"' : ''  ).'>Homepage</option>';
	}
	// le default dashboard (défini par l'admin), s'il existe
	if(get_sys_global_parameters('id_homepage', '')!=''){
		$list_dashboards.= '<option value="none" '. (( $id_page === 0) ? 'selected="selected"' : ''  ).'>Default dashboard</option>';
	}

	foreach(DashboardModel::getAllDashboard() as $array_dash) {
		// __debug($array_dash['id_page'],"array_dash['id_page']");
		// maj 02/07/2009 - MPR : Correction du bug 10348 : on remplace == par ===

            // 21/12/2010 BBX
            // Récupération des produits liés à l'élément courant
            // BZ 18510
            $query = "SELECT id_product
            FROM sys_pauto_config
            WHERE id_page = '{$array_dash['id_page']}'
            GROUP BY id_product";
            $result = $database->execute($query);
            // Comparaison des produits liés avec les produits désactivés
            $readOnly = false;
            while($row = $database->getQueryResults($result,1)) {
                foreach($inactiveProducts as $p) {
                    if($row['id_product'] == $p['sdp_id'])
                        $readOnly = $p['sdp_label'];
                }
            }
            // Traitement de l'affichage
            // 16/01/2014 GFS - Bug 38614 - [SUP][T&A Gateway][AVP 40767][MCI Iran] : Every dashboards are available in homepage configuration
            if (in_array($array_dash['sdd_id_menu'], $menu_list)) {
	            if($readOnly) {
	                $list_dashboards .= '<option value="0" '. ( $array_dash['id_page'] === $id_page ? 'selected' : ''  ).'>'.$array_dash['page_name'].'</option>';
	            }
	            else {
					$list_dashboards .= '<option value="'.$array_dash['id_page'].'" '. ( $array_dash['id_page'] === $id_page ? 'selected' : ''  ).'>'.$array_dash['page_name'].'</option>';
		    	}
	    	}
        }
}

?>

<script src="<?=NIVEAU_0?>js/table_surlignement.js"></script>
<script src="<?=NIVEAU_0?>js/user_management.js"></script>
<script src="<?=NIVEAU_0?>js/xmlhttp.js"></script>

<link rel="stylesheet" href="<?=URL_NETWORK_ELEMENT_SELECTION?>css/networkElementSelection.css" type="text/css">
<script type="text/javascript" src="<?=URL_NETWORK_ELEMENT_SELECTION?>js/prototype/controls.js"></script>
<script type="text/javascript" src="<?=URL_NETWORK_ELEMENT_SELECTION?>js/networkElementSelection.js"></script>

<script language="JavaScript" type="text/javascript">
<!--
<? // 09/02/2012 NSE DE Astellia Portal Lot2 : plus de vérification sur les champs login, mdp, name ?>
function _fp_validateMyFormElement(re, elt, title, isRequired) {
	if (isRequired && elt.value == "") {
		alert("Please, fill in the " + title + " field");
		elt.focus();
		return false;
	}
	if (elt.value != "" && !re.test(elt.value)) {
		alert("" + title + " field value you entered is not valid");
		elt.focus();
		return false;
	}
	else
		return true;
}

/**
 * Valide le formulaire My Profile
 * 23/07/2010 OJT : Correction bz15544
 * @return Boolean
 */
function _fp_validateMyForm()
{
	var els = document.forms["myForm"].elements;
	if (!testSetupFilterIcon()) return false;

    // Test déporté pour le numéro de téléphone (DE SMS)
    var phoneRetVal = true;
    if( els["phone_number"].value.length > 0 )
    {
        new Ajax.Request( '<?=NIVEAU_0?>scripts/testPhoneNumber.ajax.php',{
            method:'get',
            asynchronous:false,
            parameters:'phone_number=' + encodeURIComponent( els["phone_number"].value ),
            onSuccess:function( res )
            {
                if( res.responseText != 'ok' && res.responseText.length > 0 )
                {
                    alert( res.responseText );
                    $( 'phone_number' ).focus();
                    phoneRetVal = false;
                }
            }
        });
    }
    if( phoneRetVal == false ) return false;
	return true;
}

/**
 * Ouvrir la fenete pour configurer le selecteur
 * 12/08/2010 OJT : Correction bz16923, Réorganisation de la fonction pour DE Firefox
 */
function ouvrir_conf_selecteur()
{
	var id_page = $F('select_setup_filter'); // On récupère l'id du dashboard sélectionné
	if ( id_page == 'none') return; // Si none est sélectionné, il y a aucun sélecteur à configurer
	var params_url = 'id_page=' + id_page;
	<?php if ( isset($id_selcteur) ) : ?>
		if ( id_page == <?php echo $id_page; ?>)
			params_url = params_url + '&' + 'id_selecteur=<?php echo $id_selcteur; ?>';
	<?php endif; ?>
	selecteur = ouvrir_fenetre('user_edit_me_homepage_selecteur.php?'+params_url,'selecteur','yes','no',990,175);
}

/**
 * Gestion de l'image icon_setup_filter
 * 23/02/2010 NSE 14134 3° état : null : gris : non actif (on ne peut pas configurer)
 * 12/08/2010 OJT : Correction bz16923, Réorganisation de la fonction pour DE Firefox
 */
function changeSetupFilterIcon()
{
    // 21/12/2010 BBX
    // Gestion de l'icone de configuration de sélecteur
    // en cas de produit désactivé
    // BZ 18510
    if( $F( 'select_setup_filter' ) == 0) {
        $('icon_setup_filter').style.display='none';
    }
    else {
        $('icon_setup_filter').style.display='inline';
    }

    var on_off = 'off';
    if( $F( 'select_setup_filter' ) == -1 || $F( 'select_setup_filter' ) == 'none' ){
        on_off = 'null';
    }

    if ($('icon_setup_filter')){
        icon_setup_filter = $('icon_setup_filter');
        src = icon_setup_filter.src;
		//Si on_off = ON on passe de l'icone rouge à la verte SI on_off=OFF (ou autre chose) on passe de l'icone Verte à la rouge
        if ( on_off == 'on' ){
			// on met l'icône à vert
			if(src.match(/detail_gris.gif/)){
					src_final = src.replace(/detail_gris.gif/, 'detail_vert.gif');
			}
			else if(src.match(/detail_rouge.gif/)){
				src_final = src.replace(/detail_rouge.gif/, 'detail_vert.gif');
			}
			// on remet les bons comportements sur l'icône
			icon_setup_filter.onclick=ouvrir_conf_selecteur;
			icon_setup_filter.onmouseover=function() { popalt('<?php echo __T('U_TOOLTIP_PROFILE_SETUP_FILTER');?>'); };
			// maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
                        icon_setup_filter.style.cursor='pointer'
        }else if(on_off == 'null'){
			// on met l'icône à gris
			if(src.match(/detail_rouge.gif/)){
				src_final = src.replace(/detail_rouge.gif/, 'detail_gris.gif');
			}
			else if(src.match(/detail_vert.gif/)){
				src_final = src.replace(/detail_vert.gif/, 'detail_gris.gif');
			}
			// on annule le comportement de l'icône
			icon_setup_filter.onclick='';
			icon_setup_filter.onmouseover='';
			icon_setup_filter.style.cursor=''
		}
		else {
			// on remet les bons comportements sur l'icône
			icon_setup_filter.onclick=ouvrir_conf_selecteur;
			icon_setup_filter.onmouseover=function() { popalt('<?php echo __T('U_TOOLTIP_PROFILE_SETUP_FILTER');?>'); };
			// maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
                        icon_setup_filter.style.cursor='pointer'
			//Si on selectionne NONE, l'icone ne doit pas devenir rouge
            if ($('select_setup_filter').value != 'none'){
				if(src.match(/detail_gris.gif/)){
					src_final = src.replace(/detail_gris.gif/, 'detail_rouge.gif');
				}
				else if(src.match(/detail_vert.gif/)){
					src_final = src.replace(/detail_vert.gif/, 'detail_rouge.gif');
				}
			} else {
				//Si l'élément selectionné est none, et que l'icone est rouge on re passe l'icone en vert
				if(src.match(/detail_gris.gif/)){
					src_final = src.replace(/detail_gris.gif/, 'detail_vert.gif');
				}
				else if(src.match(/detail_rouge.gif/)){
					src_final = src.replace(/detail_rouge.gif/, 'detail_vert.gif');
				}
			}
            ouvrir_conf_selecteur();
		}
        icon_setup_filter.src = src_final;
    }
}
//24/08/2007 - JL : Fonction qui permet de savoir quelle image est affichée
	//Si rouge pas de sauvegarde et alerte, si vert sauvegarde
function testSetupFilterIcon(){
	expression = new RegExp("detail_rouge.gif");
	if  ($('icon_setup_filter')){
		icon_setup_filter = $('icon_setup_filter');
		src = icon_setup_filter.src;
		if (expression.exec(src)){
			alert ('Please save your preferences in the setup filter');
			return false;
		} else {
			return true;
		}
	} else {
		// 03/09/2007 christophe : si l'icone n'existe pas, c'est que l'on est dans my profile de la partie admin, on retourne true.
		return true;
	}
}

//-->
</script>
<style>
#user_container { margin-left: auto; margin-right: auto; width: 600px; }
#user_container #user_imgTitle { margin-bottom: 10px; }
#user_container #user_form { padding: 0 10px 0 10px; text-align:left; }
fieldset { margin-bottom:10px; }
fieldset div { display: block; margin: 10px; }
fieldset div.comment { font:normal 8pt verdana,arial; font-style: italic; color:#000; }
fieldset div.red { color:red }
fieldset div label { width: 80px; }
fieldset div label.lab2 { width: 125px; }
fieldset div label sup { color: #f00; }
fieldset div input { margin: 0 8px 0 5px; }
fieldset div img { margin-bottom:-2px; }
div.buttonSave { text-align:center; margin: 10px 0 5px 0; }
#img_select_na { position:relative; height:20px; width:20px; cursor:pointer; top:-7px;}
<?php
// 28/10/2009 BBX : correction du CSS pour le bouton de sélection des NE afin qu'il ne disparaisse pas sous IE6. BZ 11886
?>
.bt_off { background: url(<?=NIVEAU_0?>images/icones/select_na_on.png) left no-repeat;}
.bt_on { background: url(<?=NIVEAU_0?>images/icones/select_na_on_ok.png) left no-repeat;}
</style>
<?
	// On initialise la variable de session avec ce qui est enregistré en base.
	// 14/08/2007 christophe : ajout de l'initialisation de la variables $_SESSION["selecteur_general_values"]["list_of_na"]
	$_SESSION["selecteur_general_values"]["list_of_na"] = $_SESSION["network_element_preferences"] = $info_user['network_element_preferences'];
	$na_selection = new genDashboardNaSelection('all', $database_connection, 'interface_edition');
?>
<div id="user_container">
	<center>
		<div id="user_imgTitle">
			<img src="<?=NIVEAU_0?>images/titres/user_administration_interface_titre.gif"/>
		</div>
	</center>
	<div id="user_form" class="tabPrincipal">
		<form name="myForm" method="POST" action="user_edit_me.php">
			<input type="hidden" name="myFormSubmitIndicator" value="true">
			<fieldset>
				<legend class="texteGrisBold">&nbsp;<?php echo __T('G_PROFILE_FORM_FIELDSET_MY_PROFILE'); ?>&nbsp;</legend>
				<div>
                <?php // 09/02/2012 NSE DE Astellia Portal Lot2
                      // réorganisation de l'affichage ?>
                    <!-- 01/02/2011 OJT : Correction bz 20408, mise du login en read only -->
                    <label class="texteGris"><?php echo __T('G_PROFILE_FORM_LABEL_USER_LOGIN'); ?></label>
                    <input type="text" value="<?php echo $info_user['login']; ?>" readonly="readonly" name="user_login" size="20"/>
				</div>
				<div>
                    <label class="texteGris"><?php echo __T('G_PROFILE_FORM_LABEL_USERNAME'); ?></label>
                    <input type="text" value="<?php echo $info_user['username']; ?>" name="username" size="20" readonly="readonly" />
       				</div>
				<div>
					<label class="texteGris"><?php echo __T('G_PROFILE_FORM_LABEL_EMAIL'); ?></label>
                                        <input type="text" value="<?php echo $info_user['user_mail']; ?>" name="user_mail" size="40" readonly="readonly" />
				</div>
                <div>
                    <!-- 20/07/2011 OJT : DE SMS, ajout du champ "phone_number" -->
                    <label class="texteGris"><?php echo __T('G_PROFILE_FORM_LABEL_USER_PHONE_NUMBER'); ?></label>
                    <!--  15/04/2013 Phone Number On Portal -->
                    <input type="text" value="<?php echo $info_user['phone_number']; ?>" id="phone_number" name="phone_number" <?=$PAAAuthentication->doesPortalServerApiManage('phone_number')?'readonly="readonly"':''?> />
                </div>
			</fieldset>
    <?php
        if ( $_SESSION['profile_type'] == 'user' )
        {
    ?>
				<fieldset>
					<legend class="texteGrisBold">&nbsp;<?php echo __T('U_PROFILE_FORM_FIELDSET_MY_HOMEPAGE'); ?>&nbsp;</legend>
					<?php if ( isset($page_name_by_default) ){ ?>
						<!-- maj 27/06/2007 christophe  : on doit afficher la variable 'publisher' de sys_global_parameters -->
						<div class="comment"><?php echo __T('U_PROFILE_INFORMATION_HOMEPAGE', get_sys_global_parameters('publisher') ,$page_name_by_default); ?></div>
					<?php } ?>
					<div>
						<!-- 23/02/2010 NSE 14134 : suppression label "Dashboards" et ajout condition pour onchange -->
                        <!-- 12/08/2010 OJT : Correction bz16923, DE Firefox -->
                        <!-- 19/04/2011 OJT : Ajout de robustesse si aucun dashboard n'existe -->
                        <?php if( strlen( $list_dashboards ) > 0 ){ ?>
						<select name="homepage" id="select_setup_filter" onchange="changeSetupFilterIcon();">
                                <?php echo $list_dashboards; ?>
                        </select>
						<img id="icon_setup_filter" src="<?php echo NIVEAU_0.'images/icones/'.$icone_filter; ?>" onMouseOver="popalt('<?php echo __T('U_TOOLTIP_PROFILE_SETUP_FILTER');?>');style.cursor='help';" onMouseOut="kill()" onClick="ouvrir_conf_selecteur()" />
    					<div class="comment red"><?php echo __T('U_PROFILE_INFORMATION_LOGOUT'); ?></div>
                        <?php }else{ ?>
                        <div class="infoBox">
                            <?= __T( 'U_PROFILE_HOMEPAGE_NO_DASH' ); ?>
					</div>
                        <?php } ?>
                    </div>
                    <?php
					// NSE 22/02/2010 bz 14434 on met à jour l'icône d'accès au sélecteur
					if( ( strlen( $list_dashboards ) > 0 ) && ($id_page === 0 || $id_page == -1) )
                    {
						?><script language="JavaScript" type="text/javascript">
						<!--
						changeSetupFilterIcon('null');
						-->
						</script><?php
					}
					?>
				</fieldset>
				<!-- maj 27/06/2007 christophe -->
				<fieldset>
					<legend class="texteGrisBold">&nbsp;<?php echo __T('U_PROFILE_FORM_FIELDSET_NETWORK_ELEMENT_PREFERENCE'); ?>&nbsp;</legend>
					<div>
						<?
							// On affiche une icône verte si des éléments réseaux préférés ont déjà été choisis.
							// 14:59 22/07/2009 GHX
							// Correction du BZ 10427
							// La classe de l'image étaient inversé
							$image_select_na = ( !empty($info_user['network_element_preferences']) ) ? 'bt_on': 'bt_off';

							// Récupération des niveaux d'agrégagtion communs à tous les produits
                            // Exclusion du produit blanc
                            $excludeProduct = array();
                            if( ProductModel::isBlankProduct( ProductModel::getProductId() ) ){
                                $excludeProduct []= ProductModel::getProductId();
                            }

                            // 10/06/2011 MMT bz 22535 n'affiche pas les elements 3eme axe dans mes favoris
							$na_levels = NaModel::getCommomNaBetweenAllProducts( $excludeProduct, true );

                            // Test si des NA en commun existe
                            if( count( $na_levels ) > 0 )
                            {

						?>
						<div id="img_select_na" class="<?=$image_select_na?>"
							onmouseover="popalt('<?=__T('SELECTEUR_NEL_SELECTION')?>')"
							onmouseout="kill()">
						</div>
						<?php
							// Nouvelle instance de networkElementSelection
							$neSelection = new networkElementSelection();
							// On définit le type de bouton des éléments réseau
							$neSelection->setButtonMode('checkbox');
							// Initialisation du titre de la fenêtre.
							$neSelection->setWindowTitle(__T('SELECTEUR_NEL_SELECTION'));
							// Debug à 0
							$neSelection->setDebug(0);
							// On initialise le bouton qui permet d'afficher l'iHM, ainsi que les classes css du bouton.
							$neSelection->setOpenButtonProperties('img_select_na', 'bt_on', 'bt_off');
							// On définit dans quel champ la sauvegarde sera effectuée.

							$neSelection->setSaveFieldProperties('nel_selecteur', $info_user['network_element_preferences'],'|s|', 0, "selecteur[nel_selecteur]");
							// Définit les propriétés du bouton View current selection content (NB : si la méthode n'est pas appelée, le bouton n'est pas affiché).
							$neSelection->setViewCurrentSelectionContentButtonProperties(URL_SELECTEUR."php/get_NA_selected.php?na=$na");
							// On ajoute des onglets
							foreach ($na_levels as $na => $na_label)
								$neSelection->addTabInIHM($na,$na_label, URL_SELECTEUR."php/selecteur.ajax.php?action=6&idT=$na&product=ALL",URL_SELECTEUR."php/selecteur.ajax.php?action=3&idT=$na");
							// Génération de l'IHM.
							$neSelection->generateIHM();
						?>

					<!-- maj 09:57 31/08/2009 MPR : Correction du bug 11291 -->
					<div class="infoBox">
						<?= __T( 'U_MYPROFILE_NASELECTION_NOTICE_COMMON_ONLY' ); ?>
						<div class="comment red"><?php echo "Notice : ".__T('U_PROFILE_INFORMATION_LOGOUT'); ?></div>
					</div>
                    <?php
                    }else{?>
                    <div class="infoBox">
                        <?= __T( 'U_MYPROFILE_NASELECTION_NOTICE_NO_NA' ); ?>
                    </div>
                    <?php } ?>
                    </div>
				</fieldset>
			<?php } 
                        if ( $_SESSION['profile_type'] == 'user' ){?>
			<div class="buttonSave">
				<input type="submit" value=" <?php echo __T('G_FORM_BTN_SAVE'); ?> " name="submit" class="bouton" onclick="if (!_fp_validateMyForm()) return false;" />
			</div>
                        <?php }?>
		</form>
	</div>
<div>
<script language="JavaScript" type="text/javascript">
<!--
window.focus();
els = document.myForm.elements;
//-->
</script>
</body>
</html>