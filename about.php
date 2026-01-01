<?php
/**
*	Fichier qui affiche la liste de tous les produits avec leurs d�tails ( version cb, parser, contexte, cl�, ...)
*/
?>
<?php
/*
 *	@cb51000@
 *	23/06/2010 - Copyright Astellia
 *	Composant de base version cb_5.1.0.00
 *
 *	- 23/06/2010 OJT : Gestion de la documentation produit
 *  - 30/07/2010 NSE bz 15423 : utilisation des constantes au lieu de sys global parameters pour la documentation
 *	- 09/12/2011 ACS Mantis 837 DE HTTPS support
 */
?>
<?php
/*
*	@cb4100@
*
*	11/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.0
*
*	maj 11/12/2008 - MPR : Appel � la classe Key pour y extraire ses donn�es
*	maj 13/01/2009 - MPR : On boucle sur tous les produits
*	maj 13/01/2009 - MPR : On masque le d�tail des infos de chaque produit
*	maj 13/01/2009 - MPR : Mise � jour de la cl� si celle-ci a �t� modifi�e
*	maj MPR R�cup�ration des donn�es de la cl�
*	maj 13/01/2009 - MPR : Affichage de la cl� dans un champs �ditable
*	maj 13/01/2009 - MPR : Initialisation du lien vers la la doc admin ou user
*	maj 13/01/2009 - MPR : R�cup�ration du lien vers la doc sur le serveur local ou distant
*	maj 13/01/2009 - MPR : Copie du fichier sur le serveur local dans le r�pertoire upload du produit master
*	maj 13/01/2009 - MPR : Si le fichier existe, on ajoute un lien vers ce fichier
*	maj 13/01/2009 - MPR R�cup�ration des donn�es de la cl�
*	maj 13/01/2009 - MPR : Utilisation de la classe DatabaseConnection
*	maj 25/05/2009 BBX : On test la nouvelle cl�. Si invalide, affichage d'un message + pas d'enregistrement en base + pas d'affichage de la nouvelle cl�. BZ 9721
*	maj 08/06/2009 - SPS : si le fichier de doc n'est pas present, on n'affiche rien (correction bug 9714)
*	maj 09/06/2009 - MPR : Correction bug 9593 - On v�rifie que le niveau d'agr�gation existe bien sur le produit
*
*	maj 06/07/2009 BBX. BZ 9714
*		- r�organisation du code
*		- correction du lien distant vers les docs
*		- suppression du code qui concernait les anciennes about box (cb 4.0)
*
*	21/10/2009 GHX
*		- On n'affiche pas la cl� pour le produit Mixed KPI car il n'y en a pas
*/
?>
<?php
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
	- maj 22/01/2008, benoit : ajout du lien vers le fichier d'historique des versions
*/
?>
<?php
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- 13/09/2007 christophe : affichage du lien vers la doc admin ou user si elle existe.
*	- maj 08:30 05/09/2007 Gwen : affichage du nombre d'�l�ment r�seau de la cl�
*
*/
?>
<?php
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?php
/*
*	@cb1300p_gb100b_060706@
*
*	06/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0p
*
*	Parser version gb_1.0.0b
*/
?>
<?php
/*
*	16/06/2006 - Copyright Acurio
*
*	Composant de base version cb_1.2.0.2p
*
*	Parser version gb_1.0.0b
*/
?>
<?php
	/*
		Affichage des informations sur l'application et la version de l'application.
		- 2006-02-16	Stephane	MaJ : modification de la taille du champ de cl�
	  - maj 23 02 2006 christophe : affichage et calcul de la date d'expiration de l'appli.

		- maj DELTA christophe 25 04 2006. cf MODIF DELTA NOUVEAU(ajout)   MODIF DELTA(mise en commentaires des modifications)
		- maj 09 06 2006 christophe : maj du champ max_na_key_exceeded � 0 dans sys_global_parameters quand on fait une maj de la key logicielle
	*/

	session_start();
	include_once(dirname(__FILE__)."/php/environnement_liens.php");
	include_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php");
	include_once(REP_PHYSIQUE_NIVEAU_0."class/SSHConnection.class.php");

	global $database_connection;

	// on va chercher les infos sur l'appli :

    // 02/11/2010 BBX bz 18928 : Mise � jour de la version produit Mixed KPI
        MixedKpiModel::updateProductVersion();

	// R�cup�ration de tous les produits
	$products = getProductInformations();
	function get_version_info($field, $db) {

		// maj 13/01/2009 - MPR : Utilisation de la classe DatabaseConnection
		$query = "SELECT * FROM sys_versioning WHERE item = '$field' ORDER BY id DESC LIMIT 1";
		$result = $db->getAll($query);
		$nombre_resultat = count($result);
		if ($nombre_resultat > 0) {
			$result_array = $result[0];
			return $result_array["item_value"];
		} else { return "ND"; }

	}

	$arborescence = 'About';
	include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');
?>
<style>
	a:hover{
		font : normal 7pt Verdana, Arial, sans-serif;
		color : #585858;
		text-decoration: none;
	}
	a:active{
		font : normal 7pt Verdana, Arial, sans-serif;
		color : #585858;
		text-decoration: none;
	}
	a:visited{
		font : normal 7pt Verdana, Arial, sans-serif;
		color : #585858;
		text-decoration: none;
	}
	a:link{
		font : normal 7pt Verdana, Arial, sans-serif;
		color : #585858;
		text-decoration: none;
	}

	.displayInfosProduct {
		width:auto;
		margin:5px;
		padding:5px;
	}

</style>
<form name="key_form" method="post" action="about.php">
	<table align="center" border="0" cellspacing="2" cellpadding="1">
		<tr>
			<td align="center">
				<img src="images/client/logo_client_moyen.png">
			</td>
		</tr>
		<tr>
			<td valign="middle">
				<fieldset>
				<table cellpadding="0" cellspacing="2" border="0">
					<tr>
						<td class="texteGrisPetit">
<?php
if(count($products) > 0)
{
	echo '<div class="displayInfosProduct">';
	// On boucle sur tous les produits
	foreach($products as $prod)
	{
		// Connexion � la base de donn�es (24/06/2010 OJT : avec getConnection)
		$db = Database::getConnection( $prod['sdp_id'] );
		// 09/12/2011 ACS Mantis 837 DE HTTPS support
		$productModel = new ProductModel($prod['sdp_id']);

		/*
		* R�cup�ration des infos
         *
         * 28/04/2011 OJT : Gestion du num�ro de version pour un patch CB
		*/
		$cb_name         = get_version_info('cb_name', $db);
		$cb_version      = get_version_info('cb_version', $db);
        $cb_patch        = get_version_info('cb_patch', $db);
		$parser_name     = get_version_info('parser_name', $db);
		$parser_version  = get_version_info('parser_version', $db);
		$context_name    = get_version_info('context_name', $db);
		$context_version = get_version_info('context_version', $db);
		$product_name    = get_sys_global_parameters('product_name',null, $prod['sdp_id']);
		$product_version = get_sys_global_parameters('product_version',null, $prod['sdp_id']);
		$old_key         = get_sys_global_parameters('key',null, $prod['sdp_id']);
		$htmlKey = '';

        // Test si le patch est sur la derni�re version
        if( version_compare( $cb_version, $cb_patch ) < 0 ) {
            $cb_version = str_replace('_', ' ', $cb_patch );
        }

		// Test profil
		$isAdmin = (($_GET["profile_type"] == "admin#") || ($_GET["profile_type"] == "admin") || ($_POST["profile_type"] == "admin"));
		// maj 11/12/2008 - MPR : Appel � la classe Key pour y extraire ses donn�es
		$key_instance = new Key();

		// maj 25/05/2009 BBX : R�cup�ration des valeurs de la nouvelle cl�. BZ 9721
		if($isAdmin)
		{
			if ( $_POST['key_'.$prod['sdp_id']] && $_POST['hidden_key_'.$prod['sdp_id']] !== $_POST['key_'.$prod['sdp_id']] )
			{
				$keyObj = new Key();
				$decriptedKey = $keyObj->Decrypt($_POST['key_'.$prod['sdp_id']]);
				// maj 09/06/2009 - MPR : Correction bug 9593 - On v�rifie que le niveau d'agr�gation existe bien sur le produit
				$checkNa = $keyObj->checkNaExistInProduct($keyObj->getNaKey(), $prod['sdp_id']);
				$keyNbElem = $keyObj->getNbElemsKey();
				$naKey = $keyObj->getNaKey();
				$keyEndDate = $keyObj->displayKeyEndDate();

				// maj 25/05/2009 BBX : On test la nouvelle cl�. BZ 9721
				if(!is_numeric($keyNbElem) || empty($naKey) || (substr_count($keyEndDate,'--') == 1)) {
					$htmlKey = '<div id="updateMsg" class="errorMsg">'.__T('A_ABOUT_KEY_NOT_VALID').'</div>';
					$htmlKey .= '<script>setTimeout("Effect.toggle(\'updateMsg\',\'appear\')",4000);</script>';
				}
				elseif( !$checkNa ) {
					$htmlKey = '<div id="updateMsg" class="errorMsg">'.__T('A_ABOUT_NA_IN_KEY_NOT_VALID').'</div>';
					$htmlKey .= '<script>setTimeout("Effect.toggle(\'updateMsg\',\'appear\')",4000);</script>';
				}
				else
				{
					$query = "UPDATE sys_global_parameters
						SET value = '".$_POST['key_'.$prod['sdp_id']]."'
						WHERE parameters = 'key'
						";
					$db->execute($query);
					$query = "
						UPDATE sys_global_parameters
							SET value=0
							WHERE parameters='max_na_key_exceeded'
					";
					$db->execute($query);
					$htmlKey = '<div id="updateMsg" class="okMsg">'.__T('A_ABOUT_KEY_SUCCESSFULLY_UPDATED').'</div>';
					$htmlKey .= '<script>setTimeout("Effect.toggle(\'updateMsg\',\'appear\')",2000);</script>';
				}
			}

			// modif 08:23 05/09/2007 Gw�na�l
			// affichage du nombre d'�l�ment dans la cl�
			$key = get_sys_global_parameters('key',null, $prod['sdp_id']);

			// maj MPR R�cup�ration des donn�es de la cl�
			$key_decript = $key_instance->Decrypt($key);
			$nb_elems_in_key 		= 	$key_instance->getNbElemsKey();
			$na_in_key 				= 	$key_instance->getNaKey();
			$date_expiration_in_key	= 	$key_instance->displayKeyEndDate();

			// maj 09/06/2009 - MPR : On r�cup�re le label du na
			if( $na_in_key !== '' ){
				$licence = $nb_elems_in_key.' '.getNetworkLabel($na_in_key , '', $prod['sdp_id']);
			}
			$hidden_key = (!isset( $_POST['key_'.$prod['sdp_id']])) ? $key : $_POST['key_'.$prod['sdp_id']];
		}

		// Gestion de la documentation produit (visible en admin et user)
        // 23/06/2010 OJT : Gestion des liens et labels sous forme de listes
        $docList[0]['label'] = 'Click to download Product documentation';
        $docList[0]['name'] = get_sys_global_parameters( 'path_to_product_doc', null, $prod['sdp_id'] );
		if($isAdmin) {
            // Documentation administrateur
			// 30/07/2010 NSE bz 15423 : utilisation des constantes au lieu de sys global parameters
			$docList[1]['label'] = "Click to download Admin documentation";
			$docList[1]['name'] = DOC_ADMIN;
		}
		else {
            // Documentation utilisateur
			// 30/07/2010 NSE bz 15423 : utilisation des constantes au lieu de sys global parameters
			$docList[1]['label'] = "Click to download User documentation";
			$docList[1]['name'] = DOC_USER;
		}

		// 22/01/2008 - Modif. benoit : ajout du lien vers le fichier d'historique des versions
		$history_link	= NIVEAU_0."class/versionHistory.php?from=about&product=".$prod['sdp_id'];
		$history_label	= (strpos(__T('VERSION_HISTORY_LINK'), "Undefined") === false) ? __T('VERSION_HISTORY_LINK') : "Show version history";

		/*
		*	Label du produit
		*/
		?>
		<a href="#" onclick="Effect.toggle('displayInfosProduct_<?=$prod['sdp_id']?>', 'slide');" onmouseover="popalt('Show details')">
			<li>
			<span class="texteGris" style="color:#7F9DB9;font-weight:bold;">
				<?=$product_name?> - version <?=$product_version." [".$prod['sdp_label']."]"?>.
			</span>
			</li>
		</a>
		<br />
		<br />

		<?php
		/*
		*	Infos du produit
		*/
		//08/10/2014 - FGD - Bug 43444 - [REC][CB 5.3.2.03][Top Banner and About Window] The warning message when updating an invalid key must be showed for client
		//The product details are always displayed if we just tried to modify its key
		?>
		<div id="displayInfosProduct_<?=$prod['sdp_id']?>" class="fondGrisClair" style="display:<?=($prod['sdp_master'] && count($products) == 1)||( $_POST['key_'.$prod['sdp_id']] && $_POST['hidden_key_'.$prod['sdp_id']] !== $_POST['key_'.$prod['sdp_id']] )?"block":"none"?>;">
			<div style="margin:5px;">
				<br />
				<u>Base component :</u> <?=$cb_name?> - version <?=$cb_version?><br />
				<u>Parser :</u> <?=$parser_name?> - version <?=$parser_version?><br />
		<?php
		if ($context_name != 'ND') {
			echo '<u>Context :</u> '.$context_name.' - version '.$context_version.'<br />';
		}

		/*
		*	Doc distante
		*/
		// 23/06/2010 OJT : Ajout de la gestion de la doc produit
		// 09/12/2011 ACS Mantis 837 DE HTTPS support
        foreach( $docList as $oneDoc )
        {
            $link = $productModel->getCompleteUrl($oneDoc['name']);
            if( ( strlen( $oneDoc['name'] ) > 0 ) && ( ( $h = @fopen( $link, 'r' ) ) !== FALSE ) )
            {
                fclose( $h );
                echo '<br /><a class="texteGrisPetit"
                style="text-decoration : underline;font : normal 7pt Verdana, Arial, sans-serif;color : #585858;"
                href="'.$link.'" target="_blank">'.$oneDoc['label'].'</a><br />';
            }
        }

		/*
		*	Affichage Admin
		*/
		// 18/04/2011 OJT : Exclision des produit ne g�rant pas de cl�
		if( $isAdmin && Key::isProductManageKey( $prod['sdp_id'] ) )
		{
			// R�sultat de traitement de la cl�
			echo $htmlKey;
			// maj 13/01/2009 - MPR : Affichage de la cl� dans un champs �ditable
			// maj 25/05/2009 BBX : affichage de la cl� en base et non celle en POST. BZ 9721
			?>
			<input type="hidden" id="hidden_key_<?=$prod['sdp_id']?>" name="hidden_key_<?=$prod['sdp_id']?>" value="<?=$hidden_key?>" />
			<input type="hidden" name="profile_type" value="admin" />
			<br /><br />
			<table cellpadding="0" cellspacing="2" border="0">
				<tr class="texteGrisPetit">
					<td>
						Product key (licence for <?=$licence?>) :<br />
						<input type="text" id="key_<?=$prod['sdp_id']?>" class="zoneTexteStyleXP" name="key_<?=$prod['sdp_id']?>" size="55"
							value="<?=$key?>">
					</td>
					<td>&nbsp;<br />
						<input type="submit" class="boutonPlat" value="Update" />
					</td>
				</tr>
				<tr>
					<td align="left" class="texteGrisPetit">
					<?=$date_expiration_in_key?>&nbsp;
					</td>
				</tr>
			</table>
			<?php
		}

		/*
		*	Affichage User
		*/
		else
		{

		}

		/*
		*	Version History
		*/
		echo '<br/>
		<a class="texteGrisPetit" style="text-decoration : underline;font : normal 7pt Verdana, Arial, sans-serif;color : #585858;" href="'.$history_link.'">'.$history_label.'</a>
		<br />';

		/*
		*	Fin infos produit
		*/
		echo '<br /></div></div><br />';
	}
}
?>

							</td>
						</tr>
					</table>
				</fieldset>
			</td>
		</tr>
	</table>
</form>
<center>
	<input type="button" class="bouton" value="  Close" onClick="window.close()" />
</center>
</body>
</html>
