<?php
/*
	07/07/2009 GHX
		- Correction du BZ 10298 [REC 2][Contexte] Impossible de restaurer un contexte lors d'uns install de base
	10/07/2009 GHX
		- Ajout d'un popalt pour savoir sur quels produits un contexte a été installé
	04/09/2009 GHX
		- Modification du curseur de la souris quand on passe sur le bouton vert pour dire que le contexte est installé
	04/09/2009 GHX
		- (Evo) indique pour chqque contexte sur quels produits peut être monté le contexte (uniquement en multi-produit)
	17/09/2009 GHX
		- Ajout de l'include SSHConnection.class.php
	22/09/2009 GHX
		- Correction du BZ 11533
			-> Le client ne peut pas supprimer les contextes ASTELLIA (ceux créés par les fichiers Excels)
	08/10/2009 BBX
		- Prise en charge des produits Corporate
	24/03/2010 NSE bz 14815
		- Ne pas lister les produits mixed kpi comme pouvant recevoir un contexte
*/
?>
<?php 
/*
*	Ce script envoye le fichier a l'utilisateur
*
*	@version CB 4.1.0.00
*	@author SPS
*	@date 31/03/2009
*
*/

include_once dirname(__FILE__)."/../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0."context/class/Context.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0."class/SSHConnection.class.php");

//repertoire d'upload des contextes
$upload_dir = REP_PHYSIQUE_NIVEAU_0.'upload/context/';

// 14:27 07/07/2009 GHX
// Si la variable $client_type n'existe pas on la définie
if ( !isset($client_type) )
{
	session_start();
	$client_type = getClientType($_SESSION['id_user']);
}

// Récupère les informations sur tous les produits
// 08/10/2009 BBX : on remet l'ancien module pour les produits DEF
$modifiedProducts = ProductModel::restoreOldModule();
$productsInformations = getProductInformations();
foreach ( $productsInformations as $clef => $product)
{
	// 24/03/2010 NSE bz 14815 : ne pas lister les produits mixed kpi comme pouvant recevoir un contexte
	// si le produit a une bd qui porte comme nom mixed_kpi...
	if($product['sdp_id']==ProductModel::getIdMixedKpi()){
		// on supprime le produit du tableau 
		unset($productsInformations[$clef]);
	}
	else{
        // 31/01/2011 BBX
        // On remplace new DatabaseConnection() par Database::getConnection()
        // BZ 20450
		$db = DataBase::getConnection($product['sdp_id']);
		$productsInformations[$product['sdp_id']]['type'] = $db->getOne("SELECT saai_interface FROM sys_global_parameters LEFT JOIN sys_aa_interface ON (value = saai_module) WHERE parameters = 'module'");
	}
}
// 08/10/2009 BBX : on remet DEF au produits modifiés
foreach($modifiedProducts as $productId)
{
	$productModel = new ProductModel($productId);
	$productModel->setAsDef();
}

/**
 * Création d'un tableau HTML qui dit si le contexte est monté sur tel ou tel produit
 *
 * @author GHX
 * @version CB 5.0.0.08
 * @since CB 5.0.0.08
 * @param array $listTypeProducts
 * @return string
 */
function formatListProduct ( $listTypeProducts )
{
	global $productsInformations;
	
	$resultHtml = '<table>';
	foreach ( $productsInformations as $product)
	{
		$resultHtml .= '<tr>';
		$resultHtml .= '<td>'.$product['sdp_label'].'</td>';
		$resultHtml .= '<td>'.(in_array($product['type'],  $listTypeProducts) ? '<img src=&quot;'.NIVEAU_0.'images/icones/is_valid.gif&quot;/>' : '<img src=&quot;'.NIVEAU_0.'images/icones/is_not_valid.gif&quot;/>').'</td>';
		$resultHtml .= '</tr>';
	}
	$resultHtml .= '</table>';
	return $resultHtml;
} // End function formatListProduct

$lcontexte = new Context();
$tcontexte = $lcontexte->getList();

if (count($tcontexte) > 0) {
	
	?>
	<!-- context list -->
	<table width="100%">
		<tr align="left">
			<th class="texteGrisBold"><?php echo __T('A_CONTEXT_NAME');?></th>
			<th class="texteGrisBold"><?php echo __T('A_DATE');?></th>
			<th style="text-align:center;" class="texteGrisBold" onmouseover="popalt('<img src=\'<?php echo NIVEAU_0;?>images/icones/bullet_green.png\'/>&nbsp;Installed<br/><img src=\'<?php echo NIVEAU_0;?>images/icones/bullet_red.png\'/>&nbsp;Not Installed');"><?php echo __T('A_DONE');?></th>
			<th style="text-align:center;" class="texteGrisBold" onmouseover="popalt('<?php echo __T('A_CONTEXT_MOUNT');?>');"><?php echo __T('A_MOUNT');?></th>
			<?php
				// 11:27 07/07/2009 GHX
				// Possibilité de faire un restaure uniquement en astellia_admin
				if ($client_type == 'customisateur')
				{
					?>
					<th style="text-align:center;" class="texteGrisBold" onmouseover="popalt('<?php echo __T('A_CONTEXT_RESTORE');?>');"><?php echo __T('A_RESTORE');?></th>
					<?php
				}
			?>
			<th style="text-align:center;" class="texteGrisBold" onmouseover="popalt('<?php echo __T('A_CONTEXT_DELETE');?>');"><?php echo __T('A_DELETE');?></th>
		</tr>
		<?php
		//le rand ajoute un identifiant aleatoire dans la requete ajax et permet d'eviter les pbls de cache
		foreach($tcontexte as $c) {
			//on verifie si le contexte est installe
			if ($c['installed']) $img_installation = "bullet_green.png";
			else $img_installation = "bullet_red.png";
			
			// 11:17 10/07/2009 GHX
			// Ajout sur quels produits le contexte a été installé
			if ($c['installed_info'] != '') $popalt = "style=\"cursor:help;\" onmouseover=\"popalt('".$c['installed_info']."', '".__T('A_CONTEXT_LAST_INSTALLATION')."');\"";
			else $popalt = '';
			
			//on verifie si le contexte contient un backup
			if ($c['backup']) $backup = "<img src=\"".NIVEAU_0."images/icones/database_refresh.png\" alt=\"restore\"/>";
			else $backup = "";
			
			echo "<tr class=\"texteNoirPetit\">
				<td>";
			if ( count($productsInformations) > 1 )
			{
				echo "<img src=\"".NIVEAU_0."images/icones/information.png\" style=\"cursor:help;margin-bottom:-2px\" onmouseover=\"popalt('".formatListProduct($c['info_products'])."', '".__T('A_CONTEXT_LIST_PRODUCT')."');\">
					&nbsp;
				";
			}
			echo "<a href=\"php/download.php?file=".$upload_dir.$c['filename'].'&product='.$c['id_product']."\">".$c['filename']."</a></td>
				<td>".$c['date']."</td>
				<td style=\"text-align:center;\" ><img src=\"".NIVEAU_0."images/icones/".$img_installation."\" ".$popalt."/></td>
				<td style=\"text-align:center;\"><a style=\"cursor:pointer;\" onclick=\"return context_install('".rand()."','".$c['filename']."', '".$c['id_product']."');\"><img src=\"".NIVEAU_0."images/icones/database_go.png\"/></a></td>
				";
			// 11:27 07/07/2009 GHX
			// Possibilité de faire un restaure uniquement en astellia_admin
			if ( $client_type == 'customisateur')
			{
				echo "<td style=\"text-align:center;\"><a style=\"cursor:pointer;\" onclick=\"return context_restore('".rand()."','".$c['filename']."', '".$c['id_product']."');\">".$backup."</a></td>";
			}
			// 08:57 22/09/2009 GHX
			// Correction du BZ 11533
			// Un client ne peut pas supprimer un contexte Astellia
			if ( ($client_type == 'client' && $c['customisateur'] == false) ||  $client_type == 'customisateur')
			{
				echo "<td style=\"text-align:center;\"><a style=\"cursor:pointer;\" onclick=\"return context_delete('".rand()."','".$c['filename']."', '".$c['id_product']."');\"><img src=\"".NIVEAU_0."images/icones/kill_filter.png\"/></a></td>";
			}
			else
			{
				echo "<td style=\"text-align:center;\">&nbsp;</td>";
			}
			echo "</tr>";
		} 
		?>
	</table><?php
}
else {
	//si on a aucun contexte, on affiche un message d'erreur
	$error_ctx_list = __T('A_E_CONTEXT_NONE');
	echo "<div id=\"errorMsg_context_list\" class=\"errorMsg\">".$error_ctx_list."</div>";
}
?>