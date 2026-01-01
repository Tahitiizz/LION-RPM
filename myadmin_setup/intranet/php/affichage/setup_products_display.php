<?php
/**
 * 14/05/2009 - SPS :
 *		- ajout du lien pour tester la connexion SSH (correction bug 9671)
 * 		- modif du lien pour tester la connexion a la base
 * 15/05/2009 - SPS :
 *		- ajout d'un message pour dire le nombre de caracteres max du label et modif du style
 * 09/07/2009 - MPR
 *		- Correction du bug 10502 : un admin client doit uniquement pouvoir consulter les infos des products (readonly)
 *	03/08/2009 GHX
 *		- Ajout d'un bouton delete sur les produits slave et si on n'est pas en readonly
 *	12/08/2009 GHX
 *		- Correction du BZ 10994 [REC][T&A CB 5.0][TC#37323][TP#1][SETUP Product]: permettre à un profil client de pouvoir modifier le nom de son produit
 *			-> Suppression du readonly sur le label du produit
 *			-> La bouton save est dispo même en client admin
 *	19/08/2009 GHX
 *		- Si nouveau produit pas de bouton delete
 *  27/07/2010 OJT : Correction bz15712
 *  15/09/2010 OJT : Optimisation graphique pour Firefox (mise en place des <div class="editProductLine">)
 * 13/12/2010 BBX BZ 18510
 * 18/01/2011 BBX BZ 20135
 * 02/11/2011 ACS BZ 22954 Div for product info is too small
 * 28/11/2011 ACS BZ 24868 use ajax call to delete a product
 * 09/12/2011 ACS Mantis 837 DE HTTPS support
 * 20/12/2011 ACS BZ 25191 PSQL error in setup product with disabled product
 * 21/12/2011 ACS BZ 25251 port number field not accessible under IE
 *  
 */

/*******************************************************************************************
* Cette fonction retourne le code HTML d'un produit
* @param array : 	valeurs d'un produit (tableau associatif)
* @return string	:	HTML
* ******************************************************************************************/
function displayProduct($product)
{
        // maj 09/07/2009 MPR : Correction du bug 10502 : un admin client doit uniquement pouvoir consulter les infos des products (readonly)
	// Récupération du type de client
	$client_type = getClientType($_SESSION['id_user']);

    // 18/01/2011 BBX BZ 20135 : On remonte la déclaration de la variable $readonly ici car elle est utilisée juste après
	// On bloque toutes les balises html afin de passer en mode readonly pour les clients
	$readonly = ( $client_type == 'client' ) ? " readonly" : "";

	// Détermination du div Master
	$divMaster = '';
	if($product['sdp_master'] && $product['sdp_master_topo'] and $readonly == "") {
		$divMaster = '<div class="masterProduct" onmouseover="popalt(\''.__T('A_SETUP_PRODUCTS_ALL_MASTER_PRODUCT').'\')"></div>';
	}
	elseif($product['sdp_master'] and $readonly == "" ) {
		$divMaster = '<div class="masterProduct" onmouseover="popalt(\''.__T('A_SETUP_PRODUCTS_MASTER_PRODUCT').'\')"></div>';
	}
	elseif($product['sdp_master_topo'] and $readonly == "") {
		$divMaster = '<div class="masterProduct" onmouseover="popalt(\''.__T('A_SETUP_PRODUCTS_TOPO_MASTER_PRODUCT').'\')"></div>';
	}
	
	// 16:53 03/08/2009 GHX
	// Ajout du bouton delete si c'est pas le master produit
	$buttonDelete = '';
	// 17:08 19/08/2009 GHX
	// 28/11/2011 ACS BZ 24868 replace formulary submit by javascript function call
	// Si on ajout un nouveau produit le bouton delete n'existe pas
	if ( !$product['sdp_master'] && $readonly == "" && $product['sdp_id'] != 0) {
		$buttonDelete = '&nbsp;<input id="delete_button_'.$product['sdp_id'].'" type="button" name="delete" class="bouton" value="Delete" onclick="deleteProduct(\''.$product['sdp_id'].'\')" />';
	}
	
	// Classe On - Off
	$classOnOff = ($product['sdp_on_off']) ? 'productBox' : 'productBoxOff';
	// Affichage icone si Off
	$divDisable = (!$product['sdp_on_off']) ? '<div id="disabled_icon_'.$product['sdp_id'].'" class="disabledProduct" onmouseover="popalt(\''.__T('A_SETUP_PRODUCTS_PRODUCT_DISABLED').'\')"></div>' : '';
	// Libelle master
	$masterLabel = ($product['sdp_master']) ? '<span style="color:#900;">[master]</span>' : '';
	$masterTopoLabel = ($product['sdp_master_topo']) ? '<span style="color:#009;">[topology master]</span>' : '';
	// Version CB & parser
	$cbVersion = '?';
	$parserVersion = __T('A_SETUP_PRODUCTS_NO_PARSER_DEFINED');
	if($product['sdp_id'] != 0) {
		$productModel = new ProductModel($product['sdp_id']);
		// 20/12/2011 ACS BZ 25191 check that connection to the product is ok
		if (!$productModel->isError()) {
			$cbVersion = $productModel->getCBVersion();
			$parserVersion = $productModel->getParserVersion();
		}
	}
	
        // 13/12/2010 BBX BZ 18510 : Affichage d'un message si le produit a été désactivé automatiquement
        $additionnalInformation = '';
        if($product['sdp_on_off'] == 0 && $product['sdp_last_desactivation'] != 0)
        {
            $additionnalInformation = '<div id="additionnal_information_'.$product['sdp_id'].'" style="color:red">';
            $additionnalInformation .= __T('A_DATABASE_PRODUCT_AUTO_DISABLED_INFO');
            $additionnalInformation .= '</div>';
        }
?>
	<div id="product_<?=$product['sdp_id']?>" class="<?=$classOnOff?>">
		<!-- Some point to make round corners -->
		<div class="corner_top_left"></div>
		<div class="corner_top_right"></div>
		<div class="corner_bottom_left"></div>
		<div class="corner_bottom_right"></div>
		<!-- END -->
		<?=$divMaster?>
		<?=$divDisable?>
		<div class="productIcone"></div>
		
		<!-- Gives general information -->
		<div class="productInfos">
			<div class="label"><span id="product_label_<?=$product['sdp_id']?>"><?=$product['sdp_label']?></span>&nbsp;<?=$masterLabel?><?=$masterTopoLabel?></div>
                        <?=$additionnalInformation?>
			<div id="product_ip_<?=$product['sdp_id']?>" class="ip"><?=$product['sdp_ip_address']?></div>
			<div>Base Component v<?=$cbVersion?></div>
			<div><?=$parserVersion?></div>
		</div>
		<!-- end -->
		
		<!-- Gives Dabatasbe information -->
		<div class="productInfos2">
			<div id="product_database_<?=$product['sdp_id']?>" class="db_name">Database: <?=$product['sdp_db_name']?></div>
			<?php 
				/* 14/05/2009 - SPS : on deplace le lien pour tester la connexion a la base dans le fieldset correspondant a la base */
				/*<div id="test_<?=$product['sdp_id']?>" class="testConnection" onclick="testDbConnection('<?=$product['sdp_id']?>')">Test connection</div> */ 
			?>
		</div>
		<!-- end -->
		
		<?php
		// Si le master n'est pas définit, on affiche un bouton qui met le produit en master
		$ArrayMaster = getMasterProduct();
		if(count($ArrayMaster) == 0 and $readonly !== "") {
		?>
		<div class="masterButton">
			<input type="button" class="bouton" value="Set as master" onclick="setAsMaster('<?=$product['sdp_id']?>')" />
		</div>
		<?php
		}
		?>
		
		<!-- show edition fields -->
		<div class="editButton">
			<input type="button" class="bouton" value="Edit" onclick="editProduct('<?=$product['sdp_id']?>')" />
		</div>
		<!-- end -->

		<!-- This Div will display information -->
		<div id="infos_<?=$product['sdp_id']?>" style="display:none;position:absolute;top:20px;left:200px;z-index:40;">		
		</div>
		<!-- end -->

		<div class="clear"></div>

		<!-- Formulaire -->
		<center>
		<div id="editProduct_<?=$product['sdp_id']?>" class="editProduct" style="display:none;">
			<form id="form_<?=$product['sdp_id']?>" action="" method="post">	
				<!-- This Div will be displayed to configure general values of a product -->
				<div>
					<fieldset>
                        <legend>
                            <span class="texteGris"><img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif" />&nbsp;General</span>
                        </legend>
                        <?php
                        /* 15/05/2009 - SPS : ajout d'un message pour dire le nombre de caracteres max du label et modif du style*/

                        ?>
                        <?php
                        // 25/03/2010 BBX
                        // Si le produit Mixed Kpi est activé, on affiche le champ trigramme
                        if(ProductModel::getIdMixedKpi())
                        {
                            ?>
                             <div class="editProductLine">
                                <div class="formLabel">Trigram: *</div>
                                <div style="float:left;width:100px;">
                                    <input id="trigram_<?=$product['sdp_id']?>" maxlength="3" type="text" name="product[<?=$product['sdp_id']?>][sdp_trigram]" value="<?=$product['sdp_trigram']?>" style="width:20px;" />
                                </div>
                                <div class="maxLabel" style="float:left;">Must be 3 letters</div>
                            </div>
                            <?php
                        }
                        // Fin BBX
                        ?>
                         <div class="editProductLine">
                            <div class="formLabel">Label: *</div>
                            <div style="float:left;width:100px;">
                                <input id="label_<?=$product['sdp_id']?>" maxlength="25" type="text" name="product[<?=$product['sdp_id']?>][sdp_label]" value="<?=$product['sdp_label']?>" />
                            </div>
                            <div class="maxLabel" style="float:left;">Limited to 25 chars</div>
                        </div>
                        <div class="editProductLine">
                            <div class="formLabel">Address: *</div>
                            <div style="float:left;width:100px;">
                                <input id="ip_<?=$product['sdp_id']?>" type="text" name="product[<?=$product['sdp_id']?>][sdp_ip_address]" value="<?=$product['sdp_ip_address']?>" <?=$readonly?> />
                            </div>
                             <div class="maxLabel" style="float:left;">IPv4 address or servername (alias, hostname, fqdn...)</div>
                        </div>
                        <div class="editProductLine">
                            <div class="formLabel">Directory: *</div>
                            <div style="float:left;width:100px;">
                                <input id="directory_<?=$product['sdp_id']?>" type="text" name="product[<?=$product['sdp_id']?>][sdp_directory]" value="<?=$product['sdp_directory']?>" style="width:150px;" <?=$readonly?>/>
                            </div>
                        </div>
                    </fieldset>
				</div>
				<!-- end -->
				
				<!-- This Div will be displayed to configure Database values of a product -->
				<div>
					<fieldset>
                        <legend>
                            <span class="texteGris"><img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif" />&nbsp;Database</span>
                        </legend>
                        <!-- 14/05/2009 - SPS : le lien pour tester la connexion a la base est deplace ici -->
                        <div id="test_db_<?=$product['sdp_id']?>" class="testConnection" onclick="testDbConnection('<?=$product['sdp_id']?>')">Test DB connection</div>
                        <div class="editProductLine">
                            <div class="formLabel">Database Name: *</div>
                            <div>
                                <input id="db_name_<?=$product['sdp_id']?>" type="text" name="product[<?=$product['sdp_id']?>][sdp_db_name]" value="<?=$product['sdp_db_name']?>" style="width:150px;" <?=$readonly?>/>
                            </div>
                        </div>
                        <div class="editProductLine">
                            <div class="formLabel">Database Port: *</div>
                            <div>
                                <input id="db_port_<?=$product['sdp_id']?>" type="text" name="product[<?=$product['sdp_id']?>][sdp_db_port]" value="<?=($product['sdp_db_port'] ? $product['sdp_db_port'] : '5432')?>" style="width:25px;" <?=$readonly?>/>
                            </div>
                        </div>
                        <div class="editProductLine">
                            <div class="formLabel">Database Login: *</div>
                            <div>
                                <input id="db_login_<?=$product['sdp_id']?>" type="text" name="product[<?=$product['sdp_id']?>][sdp_db_login]" value="<?=$product['sdp_db_login']?>" <?=$readonly?>/>
                            </div>
                        </div>
                        <!-- 27/07/2010 OJT : Correction bz15712 -->
                        <div class="editProductLine">
                            <div class="formLabel">Database Password: </div>
                            <div>
                                <input id="db_password_<?=$product['sdp_id']?>" type="password" name="product[<?=$product['sdp_id']?>][sdp_db_password]" value="<?=$product['sdp_db_password']?>" <?=$readonly?>/>
                            </div>
                        </div>
                        <!-- fin bz15712 -->
					</fieldset>
				</div>
				<!-- end -->	

				<!-- This Div will be displayed to configure SSH values of a product -->
				<div>
					<fieldset>
                        <legend>
                            <span class="texteGris"><img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif" />&nbsp;SSH</span>
                        </legend>
                        <!-- 14/05/2009 - SPS : ajout du lien pour tester la connexion SSH -->
                        <div id="test_ssh_<?=$product['sdp_id']?>" class="testConnection" onclick="testSSHConnection('<?=$product['sdp_id']?>')">Test SSH connection</div>
                        <div class="editProductLine">
                            <div class="formLabel">SSH User: *</div>
                            <div>
                                <input id="ssh_user_<?=$product['sdp_id']?>" type="text" name="product[<?=$product['sdp_id']?>][sdp_ssh_user]" value="<?=$product['sdp_ssh_user']?>" <?=$readonly?>/>
                            </div>
                        </div>
                        <div class="editProductLine">
                            <div class="formLabel">SSH Password: *</div>
                            <div>
                                <input id="ssh_password_<?=$product['sdp_id']?>" type="password" name="product[<?=$product['sdp_id']?>][sdp_ssh_password]" value="<?=$product['sdp_ssh_password']?>" <?=$readonly?>/>
                            </div>
                        </div>
                        <div class="editProductLine">
                            <div class="formLabel">SSH Port: *</div>
                            <div>
                                <input id="ssh_port_<?=$product['sdp_id']?>" type="text" name="product[<?=$product['sdp_id']?>][sdp_ssh_port]" value="<?=($product['sdp_ssh_port'] ? $product['sdp_ssh_port'] : '22')?>" style="width:25px;" <?=$readonly?>/>
                            </div>
                        </div>
					</fieldset>
					
				</div>
				<!-- end -->	
				
				<?
				// 09/12/2011 ACS Mantis 837 DE HTTPS support 
				// 21/12/2011 ACS BZ 25251 port number field not accessible under IE
				?>
				<!-- This Div will be displayed to configure protocol of a slave product -->
				<div>
					<fieldset>
						<legend>
							<span class="texteGris"><img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif" />&nbsp;<?= __T('A_SETUP_PRODUCT_PROTOCOL_TITLE')?></span>
						</legend>
						<? // 22/12/2011 ACS BZ 25194 Impossible to test protocol on a new product ?>
                        <div id="test_protocol_<?=$product['sdp_id']?>" class="testConnection" onclick="testProtocolConnection('<?=$product['sdp_id']?>', <? echo ($product['sdp_master'] == 1)?'1':'0'; ?>)"><?= __T('A_SETUP_PRODUCT_PROTOCOL_TEST')?></div>
						<?php
							$httpAllowed = (!isset($product['sdp_http']) || $product['sdp_http'] == 1);
							$httpsAllowed = ($product['sdp_https'] == 1);

			                if($product['sdp_master']) {
						?>
						<div class="editProductLine">
							<input id="radio_http_<?=$product['sdp_id']?>" type="radio" name="master_protocol" value="http" style="width:20px" onclick="changeHttpsStatus(<?=$product['sdp_id']?>, true);" onchange="changeHttpsStatus(<?=$product['sdp_id']?>, true);" <? if ($readonly != '') {echo "disabled=\"disabled\"";} ?>
							<?php if($httpAllowed) echo ' checked'; ?>
							/>
							<label for="radio_http_<?=$product['sdp_id']?>">&nbsp;<?=__T('A_SETUP_PRODUCT_USE_HTTP')?></label>
						</div>
						<div class="editProductLine">
							<input id="radio_https_<?=$product['sdp_id']?>" type="radio" name="master_protocol" value="https" style="width:20px" onclick="changeHttpsStatus(<?=$product['sdp_id']?>, true);" onchange="changeHttpsStatus(<?=$product['sdp_id']?>, true);" <? if ($readonly != '') {echo "disabled=\"disabled\"";} ?>
							<?php if($httpsAllowed) echo ' checked'; ?>
							/>
							<label for="radio_https_<?=$product['sdp_id']?>">&nbsp;<?=__T('A_SETUP_PRODUCT_USE_HTTPS')?></label>
						</div>
						<div class="editProductLine">
							<div class="formLabel"><?=__T('A_SETUP_PRODUCT_HTTPS_PORT')?>:</div>
							<div style="float:left;width:25px;">
								<input id="https_port_<?=$product['sdp_id']?>" type="text" name="product[<?=$product['sdp_id']?>][sdp_https_port]" value="<?=$product['sdp_https_port']?>" style="width:px;" <? if (!$httpsAllowed || $readonly != ''){echo "readonly=\"readonly\"";}?>/>
							</div>
							<div class="clear"></div>
						</div>
						<?php
							}
							else {
						?>
						<div class="editProductLine">
							<input id="chkbx_http_<?=$product['sdp_id']?>" type="checkbox" name="product[<?=$product['sdp_id']?>][sdp_http]" style="width:20px" <? if ($readonly != '') {echo "disabled=\"disabled\"";} ?>
							<?php if($httpAllowed) echo ' checked'; ?>
							/>
							<label for="chkbx_http_<?=$product['sdp_id']?>">&nbsp;<?=__T('A_SETUP_PRODUCT_ALLOW_HTTP')?></label>
						</div>
						<div class="editProductLine">
							<input id="chkbx_https_<?=$product['sdp_id']?>" type="checkbox" name="product[<?=$product['sdp_id']?>][sdp_https]" style="width:20px" onclick="changeHttpsStatus(<?=$product['sdp_id']?>);" onchange="changeHttpsStatus(<?=$product['sdp_id']?>);" <? if ($readonly != '') {echo "disabled=\"disabled\"";} ?>
							<?php if($httpsAllowed) echo ' checked'; ?>
							/>
							<label for="chkbx_https_<?=$product['sdp_id']?>">&nbsp;<?=__T('A_SETUP_PRODUCT_ALLOW_HTTPS')?></label>
						</div>
						<div class="editProductLine">
							<div class="formLabel"><?=__T('A_SETUP_PRODUCT_HTTPS_PORT')?>:</div>
							<div style="float:left;width:25px;">
								<input id="https_port_<?=$product['sdp_id']?>" type="text" name="product[<?=$product['sdp_id']?>][sdp_https_port]" value="<?=$product['sdp_https_port']?>" style="width:px;" <? if (!$httpsAllowed || $readonly != ''){echo "readonly=\"readonly\"";}?>/>
							</div>
							<div class="clear"></div>
						</div>
						<?php
							}
						?>
					</fieldset>
				</div>

				<!-- This Div will be displayed to configure product activation -->
				<div>
					<fieldset>
					<legend>
                                <span class="texteGris"><img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif" />&nbsp;Status</span>
					</legend>

						<div>
                            <table width="100%">
                                <tr>
                                    <td align="left">
                                        <?=__T('A_SETUP_PRODUCT_COMPONENT_STATUS')?> <span id="comp_status_<?=$product['sdp_id']?>"><?php echo $product['sdp_on_off'] ? 'ON' : 'OFF'; ?></span>
                                    </td>
                                    <td align="right">
                                        <?php if($product['sdp_master']) echo __T('A_SETUP_PRODUCTS_CANNOT_DISABLE_MASTER'); ?>
                                        <input id="on_off_button_<?=$product['sdp_id']?>"
                                               type="button" class="bouton"
                                               style="display:<?php echo $product['sdp_master'] ? 'none' : 'block'; ?>"
                                               value="<? echo $product['sdp_on_off'] ? 'Disable' : 'Enable'; ?>"
                                               onclick="<? echo $product['sdp_on_off'] ? 'disable' : 'enable'; ?>Product('<?=$product['sdp_id']?>')" />
							<input type="hidden" name="product[<?=$product['sdp_id']?>][sdp_on_off]" id="onOff_<?=$product['sdp_id']?>" value="<?=$product['sdp_on_off']?>" />
                                    </td>
                                </tr>
                            </table>
						</div>

                        <?php
                        if(!$product['sdp_master']) {
                        ?>
                        <hr width="100%">
                        <div>
                            <?php
                            // Pour que la case soit cochée :
                            // - automatic_activation = 1
                            $checked = $product['sdp_automatic_activation'];
                            // Pour que la case soit grisée :
                            // - Produit off
                            // - Last desactivation = 0
                            // 18/01/2011 BBX
                            // Modification de la variable $readonly vers $readonlyAutoAct
                            // Pour éviter un conflit avec la variable $readonly
                            // BZ 20135
                            $readonlyAutoAct = !$product['sdp_on_off'] && !$product['sdp_last_desactivation'];
                            ?>
                            <input id="chkbx_aa_<?=$product['sdp_id']?>" type="checkbox" style="width:20px"
                                <?php if($checked) echo ' checked'; ?>
                                <?php if($readonlyAutoAct) echo ' disabled'; ?>
                                onclick="autoAct('<?=$product['sdp_id']?>')" />
                            <label for="chkbx_aa_<?=$product['sdp_id']?>">&nbsp;<?=__T('A_SETUP_PRODUCT_AUTOMATIC_ACTIVATION')?></label>
                            <input type="hidden" name="product[<?=$product['sdp_id']?>][sdp_automatic_activation]" id="autoAct_<?=$product['sdp_id']?>" value="<?=$product['sdp_automatic_activation']?>" />
                            <input type="hidden" name="product[<?=$product['sdp_id']?>][sdp_last_desactivation]" id="lastDesac_<?=$product['sdp_id']?>" value="<?=$product['sdp_last_desactivation']?>" />
                            <input type="hidden" name="askForAct_<?=$product['sdp_id']?>" id="askForAct_<?=$product['sdp_id']?>" value="0" />
                            <div class="infoBox" id="help_auto_act_<?=$product['sdp_id']?>">
                                <?php echo $readonlyAutoAct ? __T('A_SETUP_PRODUCT_HELP_AUTO_ACT_RO') : __T('A_SETUP_PRODUCT_HELP_AUTO_ACT'); ?>
                            </div>
                        </div>
                        <?php } ?>

						<div class="downloadLogFile" style="display:none;" id="download_<?=$product['sdp_id']?>">
							<a href="" class="texteGris" target="_blank" id="downloadLogFile_<?=$product['sdp_id']?>">Download activation log file</a>
						</div>
					</fieldset>
				</div>
				<!-- end -->
				
				<!-- Save -->
				<br />
				<center>
					<input id="save_button_<?=$product['sdp_id']?>" type="button" class="bouton" value="Save" onclick="saveProduct('<?=$product['sdp_id']?>')" />
					<? if ( $readonly == "" ) {?>
						<?php echo $buttonDelete; ?>
					<?}?>
				</center>
				<!-- end -->
			</form>			
		</div>
		</center>
		<!-- end -->
		
	</div>
<?php
}
?>