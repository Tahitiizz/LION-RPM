<?php
/*
	11/12/2008 GHX
		- création du fichier
	10/02/2009 GHX
		- ajout de la fonction displayTitle()
		- ajout du formulaire de download
		- implémentation de l'action download
	06/04/2009 SPS : adaptation du CSS pour IE8
	05/08/2009 - CCT1 : ajout de l'image titre. correction BZ 10290 
	22/09/2009 MPR : Correction du bug 11652 - Ajout d'un message d'info
 *      12/10/2010 NSE bz 18425 : suppression du test sur le type de fichier
 *      14/10/2010 NSE bz 18517 : ajout de force_download
 *		30/11/2011 ACS BZ 24777 Appearing warning error msg when clicking on Download button
 */
?>
<?php
/**
 * Fichier contenant dives fonctions concernant l'IHM du mapping de la topologie
 *
 * @author : GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 */

/**
 * Retourne TRUE si la topologie est vide dans le cas contraire FALSE
 *
 * @author : GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 * @param int $idProduct identifiant du produit
 * @return boolean
 */
function isEmptyTopology ( $idProduct )
{
	// On suppose que si la requette retourne un élément la topologie n'est pas vide
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$db = Database::getConnection($idProduct);
	$result = $db->executeQuery("SELECT eor_id FROM edw_object_ref LIMIT 1");
	return ( $db->getNumRows() == 1 ? false : true );
} // End function isEmptyTopology

/**
 * Affichage d'un message d'erreur comme quoi aucun produit n'est défini en tant que
 * master topologie. Et on affiche aussi un lien vers IHM "Setup Product" pour que l'utilisateur choisit
 * un produit master topologie
 *
 * @author : GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 */
function displayErrorNoMasterTopo ()
{
	// Récupère le lien vers IHM "Setup Product"
        // 30/12/2010 BBX
        // Correction de l'URL vers Setup products
        // BZ 19936
	$menuSetupProduct = new MenuModel(MenuModel::getIdMenuFromLabel('Setup Products'));
	$menuSetupProductValues = $menuSetupProduct->getValues();
	$urlSetupProduct = NIVEAU_0.$menuSetupProductValues['lien_menu'];

	echo '<div class="errorMsg"><p>'.__T('A_E_MAPPING_TOPO_UNDEFINE_MASTER_TOPO').'<p><p>'.__T('A_MAPPING_TOPO_INFO_FOR_CHOOSE_MASTER_TOPO', $urlSetupProduct).'</p></div>';
	exit();
} // End function displayErrorNoMasterTopo

/**
 * Affiche un message comme quoi il n'y a qu'un seul produit donc pas la possibilité de faire du mapping
 *
 * @author : GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 */
function displayNoOhtersProducts ()
{
	echo '<div style="margin:10px">';
	echo '<img src="'.NIVEAU_0.'images/icones/information.png" border="0" />';
	echo '<div class="infoBox"><p>'.__T('A_MAPPING_TOPO_ONE_PRODUCT').'<p></div>';
	echo '</div>';
	exit();
} // End function displayNoOhtersProducts

/**
 * Affiche un message comme quoi la topologie master est vide
 *
 * @author : GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 * @param string $label label du master de la topologie
 */
function displayMasterTopoIsEmpty ( $label )
{
	echo '<div style="margin:10px">';
	echo '<img src="'.NIVEAU_0.'images/icones/information.png" border="0" />';
	echo '<div class="infoBox"><p>'.__T('A_MAPPING_MASTER_TOPO_IS_EMPTY', $label).'<p></div>';
	echo '</div>';
	exit();
} // End function displayMasterTopoIsEmpty

function displayErrorNoTopologySlave()
{
    echo '<div style="margin:10px">';
	echo '<img src="'.NIVEAU_0.'images/icones/information.png" border="0" />';
	echo '<div class="errorMsg"><p>'.__T('A_MAPPING_SLAVE_TOPO_IS_EMPTY').'<p></div>';

}

/**
 * Ouvre une popup pour proposer de télécharger le fichier de mapping
 *
 * @author : GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 * @param string $filename nom du fichier
 */
function displayLinkFileForDownload ( $filename )
{
	// 14/10/2010 NSE bz 18517 : ajout de force_download
	echo '
	<html>
		<head>
			<title>Mapping Topology File</title>
			<link rel="stylesheet" href="'.NIVEAU_0.'css/global_interface.css" type="text/css">
		</head>
	<body class="tabPrincipal">
		<div align="center" style ="width:100%;padding-bottom:10px">
		<fieldset style="width:90%">
			<legend>&nbsp;<img src="'.NIVEAU_0.'images/icones/download.png">&nbsp;</legend>
			<a id="link_to_file" name="link_to_file" href="#" onclick="document.location.replace(\''.NIVEAU_0.'/php/force_download.php?filepath=/home/'.NIVEAU_0.'upload/'.$filename.'\');document.getElementById(\'link_to_file\').innerHTML = \'<p class=texteGrisBold onclick=window.close()>'.__T('U_CLOSE_POPUP').'</p>\';" class="texteGrisBold"><p class="texteGrisBold">Click here to download the Mapping Topology file</p></a>
		</fieldset>
		</div>
	</body>
	</html>
	';
	exit;
} // End function displayLinkFileForDownload

/**
 * Affiche le titre du formulaire
 *
 *	10/02/2009 GHX
 *		- création de la fonction
 *
 * @author : GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 */
function displayTitle ()
{
	// 05/08/2009 - CCT1 : ajout de l'image titre. correction BZ 10290 
	echo '<div style="padding:10px;"><center><img src="'.NIVEAU_0.'images/titres/topology_mapping.gif"/></center></div>';
} // End function displayTitle

/**
 * Affiche le formulaire de download et d'upload du mapping de la topologie
 * 
 * 06/04/2009 SPS : adaptation du CSS pour IE8
 *
 * @author : GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 * @param array $productMasterTopo tableau les informations sur le produit master topologie
 * @param array $productOthers tableau contenant la liste des produits
 */
function displayForm ( $productMasterTopo, $productOthers )
{
	?>
	<style>
		#communMapping {
			margin: 20px;
		}
		#communMapping p {
			margin-top:20px;
			clear:both;
		}
		#communMapping p label {
 			display:block;
			float:left;
			width:250px;
		}
		#communMapping p input,#communMapping p select {
			margin-top:-5px;
		}
		
		#fieldUpload {
			float:left;
			width:45%;
			/* 06/04/2009 : modif SPS : adaptation pour IE8*/
			margin-left:10px;
			margin-bottom:20px;
		}
		#fieldDownload {
			width:45%;
			margin-bottom:20px;
			/* 06/04/2009 : modif SPS : adaptation pour IE8*/
			margin-right:10px;
			margin-left:20px;
			float:left;
		}
	</style>
	<script>
	/**
	 * L'utilisateur doit confirmer qu'il veut bien vider le mapping
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	function confirmTruncate ()
	{
		var labelProduct = $('product').options[$('product').selectedIndex].text;
		var message = "<?php echo __T('A_MAPPING_TOPO_TRUNCATE_CONFIRM'); ?>";
		return confirm(message + ' ' + labelProduct + ' ?');
	} // End function  confirmTruncate
	</script>
	<div style="margin:10px auto 10px auto; width:900px" class="tabPrincipal">
		<form enctype="multipart/form-data" name="formMapping" method="post" action="mapping.php">
			<div id="communMapping">
				<!-- AFFICHE LE NOM DU PRODUIT QUI EST LE MASTER TOPO-->
				<p class="texteGris" style="padding-bottom:2px;">
					<label class="texteGrisBold"><?php echo __T('A_MAPPING_TOPO_FORM_LABEL_MASTER_TOPO_NAME'); ?></label>
					<label><?php echo $productMasterTopo['sdp_label']; ?></label>
				</p>
				<!-- AFFICHE LA LISTE DES AUTRES PRODUITS -->
				<p class="texteGris">
					<label for="product" class="texteGrisBold"><?php echo __T('A_MAPPING_TOPO_FORM_LABEL_LIST_OTHERS_PRODUCTS'); ?></label>
					<?php echo getSelectOthersProduct($productOthers); ?>					
					<br /><span class='texteGrisPetit'><?=__T('A_MAPPING_TOPO_FORM_LABEL_LIST_OTHERS_PRODUCTS_INFO')?></span>
				</p>
				<!-- 
				maj 07/08/2009 Correction du bug 10766 
				AFFICHE LE CHOIX DU DELIMITEUR 
				-->
				<p class="texteGris">
					<input type="hidden" name='delimiter' value=';' /> 
				</p>
				<!-- AFFICHE LA CHECKBOX DU HEADER -->
				<p class="texteGris">
					<label class="texteGrisBold"><?php echo __T('A_UPLOAD_TOPO_HEADER')?></label>
					<input type="checkbox" name="header" value="1" disabled="disabled" checked="checked">
					<br /><span class='texteGrisPetit'><?php echo __T('A_UPLOAD_TOPO_FIRST_LINE_INFO')?></span>
				</p>
				<img src="<?php echo NIVEAU_0; ?>/images/icones/information.png" title="help" alt="help" />
				<p class="infoBox" style="margin-top:-3px;">
					<b><u>Name of the columns for header :</u></b>
					<br />
					<br /><b><?php echo __T('A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_MAPPED')?></b> : <?php echo __T('A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_MAPPED_INFO')?>
					<br /><b><?php echo __T('A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_CODEQ')?></b> : <?php echo __T('A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_CODEQ_INFO')?>
					<br /><b><?php echo __T('A_MAPPING_TOPO_COLUMN_LABEL_NA_TYPE')?></b> : <?php echo __T('A_MAPPING_TOPO_COLUMN_LABEL_NA_TYPE_INFO')?>
				</p>
			</div>
			
			<!-- FORMULAIRE UPLOAD -->
			<fieldset id="fieldUpload">
				<legend class="texteGrisBold"><img src="<?php echo NIVEAU_0; ?>images/icones/small_puce_fieldset.gif"/>&nbsp;Upload</legend>
				<p>
					<label class="texteGrisBold" for="uploadFile"><?php echo __T('A_UPLOAD_TOPO_CSV_TO_UPLOAD')?></label>
					<input type='file' id="uploadFile" name='uploadFile' size='30'>
				</p>
				<p style="text-align:center">
					<input type='submit' name='action' value='Upload' class="bouton"/>
				</p>
			</fieldset>
			<!-- FIN FORMULAIRE UPLOAD -->
			
			<!-- FORMULAIRE DOWNLOAD -->
			<fieldset id="fieldDownload">
				<legend class="texteGrisBold"><img src="<?php echo NIVEAU_0; ?>images/icones/small_puce_fieldset.gif"/>&nbsp;Download</legend>
				<!-- maj 09:46 22/09/2009 MPR : Correction du bug 11652 - Ajout d'un message d'info --> 
				<p class="texteGris">
					<?php echo __T('A_MAPPING_TOPO_INFO_DOWNLOAD'); ?>
				</p>
				<!-- On masque le type de header valeur par défault T&A header -->
				<input type="hidden" name="typeColumnNa" value="ta" />
				
				<p style="text-align:center">
					<input type='submit' name='action' value='Download' class="bouton"/>
				</p>
			</fieldset>
			<!-- FIN FORMULAIRE DOWNLOAD -->
			
			<!-- FORMULAIRE TRUNCATE -->
			<fieldset id="fieldUpload">
				<legend class="texteGrisBold"><img src="<?php echo NIVEAU_0; ?>images/icones/small_puce_fieldset.gif"/>&nbsp;Empty</legend>
				<p class="texteGris">
					<?php echo __T('A_MAPPING_TOPO_INFO_TRUNCATE'); ?>
				</p>
				<p style="text-align:center">
					<input type='submit' name='action' value='Truncate' class="bouton" onclick="return confirmTruncate();"/>
				</p>
			</fieldset>
			<!-- FIN FORMULAIRE TRUNCATE -->
			
		</form>
		<hr style="clear:both;visibility:hidden" />
	</div>
	<?php
} // End function displayForm

/**
 * Retourne le code HTML pour la sélection des produits qui peuvent être mappé
 *
 * @author : GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 * @param array $productOthers tableau contenant la liste des produits
 */
function getSelectOthersProduct ( $productOthers )
{
	$str = '<select id="product" name="product">';
	foreach ( $productOthers as $oneProduct)
	{
		if ( isEmptyTopology($oneProduct['sdp_id']) == true )
		{
			$str .= '<option value="-'.$oneProduct['sdp_id'].'" disabled="disabled">'.$oneProduct['sdp_label'].' (Topology is empty)</option>';
		}
		else
		{
			$str .= '<option value="'.$oneProduct['sdp_id'].'" >'.$oneProduct['sdp_label'].'</option>';
		}
	}
	$str .= '</select>'; // 02/09/2010 OJT : Correction bz16927 pour DE Firefox (fermuture de la balise)
	
	return $str;
} // End function getSelectOthersProduct


/***************************************************************
 * ACTION SUR LE MAPPING
 ***************************************************************/

/**
 * Vérifie si le fichier peut être uploadé. Si non affiche un message d'erreur et retourne FALSE si c'est bon on retourne TRUE.
 *
 * @author : GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 * @return boolean
 */
function checkFileUpload ()
{
	// Vérification sur le fichier uploadé par l'utilsateur
	$file_size  = $_FILES['uploadFile']['size'];
	$file_type  = $_FILES['uploadFile']['type'];
	$file_error = $_FILES['uploadFile']['error'];
	$msgError = null;
	
	/*
	 * Le fichier est trop volumineux
	 * 	- 1 : excède le poids autorisé par la directive upload_max_filesize de php.ini 
	 * 	- 2 : excède le poids autorisé par le champ MAX_FILE_SIZE s'il a été donné 
	 */
	if ( $file_error == 1 ||  $file_error == 2 )
	{
		$msgError = __T('A_UPLOAD_TOPOLOGY_FILE_IS_TOO_BIG');
	}
	elseif ( $file_error == 3 ) // Le fichier n'a été uploadé que partiellement 
	{
		$msgError = __T('A_E_UPLOAD_TOPOLOGY_FILE_PARTIAL');
	}
	elseif ( $file_error == 4 ) // Aucun fichier n'a été uploadé 
	{
		$msgError = __T('A_E_UPLOAD_TOPOLOGY_FILE_MISSING');
	}
	elseif ( $file_size == 0 ) // Fichier vide
	{
		$msgError = __T('A_E_UPLOAD_TOPOLOGY_FILE_IS_EMPTY');
	}
	// 12/10/2010 NSE bz 18425 : suppression du test sur le type de fichier
	
	// S'il y a un message d'erreur on affiche un message
	if ( $msgError != null )
	{
		echo '<div class="errorMsg">'.$msgError.'</div>';
		
		return false;
	}
	
	return true;
} // End function checkFileUpload

/**
 * Retourne TRUE si le produit sélectionné n'a pas une topologie vide dans le cas contraire FALSE
 *
 * @author : GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 * @return boolean
 */
function checkSelectProductNoTopoEmpty ()
{
	if ( $_POST['product'] < 0 )
	{
		echo '<div class="errorMsg">'.__T('A_E_MAPPING_TOPO_SELECT_PRODUCT_TOPO_EMPTY').'</div>';
		return false;
	}
	
	return true;
} // End function checkSelectProductNoTopoEmpty

/**
 * Effectue une action sur la mapping 
 *
 * @author : GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 * @param string $action type d'action à effectuer sur le mapping
 * @param array $masterProduct tableau d'information sur le master produit
 * @param array $masterTopology tableau d'information sur le master topologie
 * @param array $productMapped tableau d'information du produit mappé
 */
function actionMapping ( $action, $masterProduct, $masterTopology, $productMapped )
{
	include REP_PHYSIQUE_NIVEAU_0.'/class/mapping/Mapping.class.php';

	try
	{
		// Instansiation de la classe
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$mapping = new Mapping(Database::getConnection());
		// DEBUG
		$mapping->setDebug((int)get_sys_debug('mapping'));
		// Initialise certains paramètres
		$mapping->setDirectoryUpload(REP_PHYSIQUE_NIVEAU_0.'/upload/');
		$mapping->setDelimiter($_POST['delimiter']);		
		$mapping->setMasterProduct($masterProduct);
		$mapping->setMasterTopology($masterTopology);
		$mapping->setProductMapped($productMapped);
		
		$msg = '';
		switch  ( $action )
		{
			case 'Upload':
				// 30/11/2011 ACS BZ 24777 Appearing warning error msg when clicking on Download button
				$file = str_replace(' ', '_', $_FILES['uploadFile']['name']);
				copy($_FILES['uploadFile']['tmp_name'], REP_PHYSIQUE_NIVEAU_0.'upload/'.$file);
				
				$isOk = checkFileUpload();
				if ( $isOk === true )
				{
					$mapping->setFile($file);
					// Préparation des fichiers
					$mapping->prepareFiles();
					// Vérification du fichier chargé
					$mapping->check();
					// Charge le fichier dans la topologie du produit mappé
					$mapping->load();
					
					$msg = __T('A_MAPPING_TOPO_UPLOAD_FILE_OK', $productMapped['sdp_label']);
				}
				break;
			
			case 'Download':
				// 10/02/2009 GHX
				// Prise en compte du download
				$mapping->setTypeColumnNa($_POST['typeColumnNa']);
				$filename = $mapping->download();
				
				echo '
					<script>
						window.parent.ouvrir_fenetre("mapping.php?action=download&file='.$filename.'","nouvellepage","yes","yes",450,30);
					</script>
				';
				break;
			
			case 'Truncate':
				$mapping->truncate();
				
				$msg = __T('A_MAPPING_TOPO_TRUNCATE_OK', $productMapped['sdp_label']);
				break;
		}
		
		if ( $msg != '' )
		{
			echo '<div class="okMsg">'.$msg.'</div>';
		}
	}
	catch ( Exception $e )
	{
		echo '<div class="errorMsg">'.$e->getMessage().'</div>';
	}
} // End function actionMapping
?>