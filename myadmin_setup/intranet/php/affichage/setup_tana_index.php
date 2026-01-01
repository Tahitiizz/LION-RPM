<?
/*
*	@cb41000@
*
*	08/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	08/12/2008 BBX : modification du script pour le CB 4.1
*	=> Utilisation des nouvelles variables globales
*	=> Utilisation de la classe DatabaseConnection
*	=> Contrôle d'accès
*	=> Suppression de l'iframe
*	=> Suppression des requête sur sys_selecteur_properties car cette table n'existe plus
*
*	- 10/02/2009 MPR : Ajout de la fonction setup_tana_delete()
*
*	- 31/03/2009 BBX : Si le serveur renvoie PROCESS, un process est en cours, on ne déploie pas.
*		=> Fonctions modifiées : setup_tana_delete & deployElement
*
*	- 18/05/2009 BBX : correction de la requête qui récupère les NA créés par le client.BZ 9774
*
*	- 03/06/2009 BBX : le bouton de suppression d'un NA est désormais toujours présent pour les NA user. BZ 9753
*		=> affichage du bouton de suppression en permanence
*		=> suppression du bouton de déploiement en fin de traitement au lieu de le transformer en bouton de suppression
*		=> ajout d'un test sur le statut de déploiement afin de ne pas supprimer en même temps qu'un déploiement
*
*	- 17/09/2009 BBX :
*		=> ajout de l'id produit dans la focntion "get_family_information_from_family". BZ 10504
*		=> img.remove() ne fonctionne pas avec IE6. Pour ce navigateur, on réduit l'image à 0px pour la faire diparaitre. BZ 11619
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

// Connexion à la base de données locale
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection();

// Librairies et classes nécessaires
require_once(REP_PHYSIQUE_NIVEAU_0.'models/ProfileModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'models/UserModel.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/select_family.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');

// Intranet top + menu contextuel
include_once(REP_PHYSIQUE_NIVEAU_0 . "intranet_top.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/menu_contextuel.php");

// 27/07/2010 OJT : Correction bz16899, mise en commentaire du contrôle d'accès
/*// Contrôle d'accès
$userModel = new UserModel($_SESSION['id_user']);
$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu ILIKE 'Setup Network Aggregation'";
$result = $database->getRow($query);
$idMenu = ($result['id_menu'] == '') ? 0 : $result['id_menu'];
if(!$userModel->userAuthorized($idMenu)) {
	include_once(REP_PHYSIQUE_NIVEAU_0."intranet_top.php");
	echo '<div class="errorMsg">'.__T('A_USER_NOT_ALLOWED_TO_ACCESS').'</div>';
	exit;
}*/



// 21/11/2011 BBX
// BZ 24764 : correction des messages "Notice" PHP
if(!isset($_GET["family"])) $_GET["family"] = null;
if(!isset($_GET["tana_type"])) $_GET["tana_type"] = null;
if(!isset($_GET["product"])) $_GET["product"] = null;
$family = $_GET["family"];
$tana_type = $_GET["tana_type"];
$product = $_GET["product"];

// Connexion à la base du produit
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection($_GET['product']);

if ($tana_type == 'na') {
    $table = "sys_definition_network_agregation";
    $tanatitre = "Network Aggregation";
    $clause_where = "where family='$family'";
}

if ($tana_type == 'ta') {
    $table = "sys_definition_time_agregation";
    $tanatitre = "Time Aggregation";
    $clause_where ="";
}
//echo "**$tana_type";

        if(!isset($_GET["family"])){
                $select_family = new select_family($_SERVER['PHP_SELF'], $_SERVER['argv'][0], 'Network aggregation');
                exit;
        }

?>
<script src="<?=$niveau0?>js/myadmin_omc.js"></script>
<script type="text/javascript">
// 03/06/2009 BBX : ajout d'une variable globale de statut
var _DeployStatus = false;

// maj 10/02/2009 - MPR : Ajout de la fonction setup_tana_delete()
/**
* Fonction qui supprime le niveau d'agrégation choisi
* @param object : image bouton
* @param string : nom du NA à supprimer
* @param string : table où est enregsitré le niveau d'agrégation
* @param string : famille du NA
* @param string : produit concerné
*/
function setup_tana_delete(img,tana,table,family,product)
{
	// 03/06/2009 BBX : test de _DeployStatus
	if(_DeployStatus) return false;
	// 31/03/2009 BBX :
	// Traitement via Ajax + Test sur process en cours
	if(confirm('Delete this Network Aggregation ('+tana+') ?\nAll related tables will be deleted !')) {
		// Image animée d'attente
		img.src = '<?=NIVEAU_0?>images/animation/indicator_snake.gif';
		// Désactivation des actions au clic
		img.onclick = null;
		// Requête Ajax
		new Ajax.Request('setup_tana_delete.php',{
			method:'get',
			parameters:'tana='+tana+'&table='+table+'&family='+family+'&product='+product+'&rand='+Math.random(),
			onSuccess:function(transport) {
				// Si le serveur renvoie OK
				var reponse = transport.responseText;
				if(reponse == 'OK') {
					document.location.href='setup_tana_index.php?family='+family+'&product='+product+'&tana_type=<?=$tana_type?>';
				}
				// Si le serveur renvoie PROCESS, un process est en cours, on ne déploie pas.
				if(reponse == 'PROCESS') {
					// On remplace l'image par l'image de suppression
					img.src = '<?=NIVEAU_0?>images/icones/drop.gif';
					img.onclick = function() {setup_tana_delete(img,tana,table,family,product);};
					// Désactivation de la tooltip Activate afind e mettre la tooltip delete
					img.onmouseover = function() {popalt('<?=__T('A_SETUP_NETWORK_AGGREGATION_DElETE')?>');};
					// Affichage du message
					alert('<?=__T('A_SETUP_NETWORK_AGGREGATION_DELETION_PROCESS_IS_RUNNING')?>');
				}
			}
		});
	}
	else {
		alert('No Network Aggregation deleted');
	}
}

/***************
* Cette fonction permet de déployer un NA via ajax
* @param object : image cliquée
* @param string : nom du NA à déployer
***************/
function deployElement(img,na)
{
	// 03/06/2009 BBX : _DeployStatus à true
	_DeployStatus = true;
	// Image animée d'attente
	img.src = '<?=NIVEAU_0?>images/animation/indicator_snake.gif';
	// Désactivation des actions au clic
	img.onclick = null;
	// Requête Ajax vers le script de déploiement
	new Ajax.Request('setup_grouptable_traitement.php',{
		method:'post',
		parameters:'family=<?=$family?>&product=<?=$product?>&na='+na+'&rand='+Math.random(),
		// Lorsque le déploiement est effectué :
		onSuccess:function(transport) {
			// Si le serveur renvoie OK
			var reponse = transport.responseText;
			if(reponse == 'OK') {
				// 03/06/2009 BBX : suppression du bouton au lieu de le transformer en bouton de suppression car désormais il est présent en permanence.
				// 17/09/2009 BBX : img.remove() ne fonctionne pas avec IE6. Pour ce navigateur, on réduit l'image à 0px pour la faire diparaitre. BZ 11619
				if (navigator.appVersion.indexOf('MSIE 6') != -1) {
					img.style.width = "0px";
					img.style.height = "0px";
				}
				else {
					img.remove();
				}
				// Suppression du fond rouge
				$('tr_'+na).style.backgroundColor = '';
				// 03/06/2009 BBX : _DeployStatus à false
				_DeployStatus = false;
			}
			// 31/03/2009 BBX
			// Si le serveur renvoie PROCESS, un process est en cours, on ne déploie pas.
			else if(reponse == 'PROCESS') {
				// On remplace l'image par l'image d'activation
				img.src = '<?=NIVEAU_0?>images/icones/bullet_go.png';
				// Redéfinition de la fonction : désormais, le clique sur le bouton déclence le process de suppression
				img.onclick = function() {deployElement(img,na);};
				// Affichage du message
				alert('<?=__T('A_SETUP_NETWORK_AGGREGATION_ACTIVATION_PROCESS_IS_RUNNING')?>');
			}
                        // 09/09/2011 BBX BZ 23641 : ajout de robustesse sur la condition
                        else{alert(reponse);}
		}
	});
}
</script>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr valign="middle">
    <td class='head' align=center>
       <br>
    </td>
  </tr>
  <tr valign="middle">
    <td>
		<!-- FORMULAIRE SNA -->
		<form name="formulaire" method="post" action="">
		<table width="550px" align="center" valign="middle" cellpadding="0" cellspacing="0">
			<tr>
				<td align="center">
						<img src="<?=NIVEAU_0?>images/titres/setup_na_interface.gif"/>
				</td>
			</tr>
			<tr>
				<td>
					<table width="100%" border="0" align="center" cellspacing="3" cellpadding="3" class="tabPrincipal">
						<tr>
							<td align="center">
								<input onclick="ouvrir_fenetre('setup_tana_new.php?action=1&tana_type=<?=$tana_type?>&family=<?=$family?>&product=<?=$product?>','New_na','yes','yes',550,350)" type="button" class="bouton" name="parameter" value="New">
							</td>
						</tr>
						<tr>
							<td align="center" class="texteGris" valign="middle">
							<fieldset>
								<div class="texteGris" style="text-align:center;">
									<?
									// Recuperation du label du produit
									$productInformation = getProductInformations($product);
									$productLabel = $productInformation[$product]['sdp_label'];
									echo $productLabel."&nbsp;:&nbsp;";

									// Recuperation du label de la famille
									// 22/07/2009 BBX : ajout de l'id produit. BZ 10504
									$family_information = get_family_information_from_family($family,$product);
									echo (ucfirst($family_information['family_label'])).'&nbsp;';

									// MàJ 07/08/2007 - JL :  Ajout condition d'affichage de l'icone
									if (get_number_of_family() > 1){ ?>
										<a href="setup_tana_index.php?tana_type=na&product=<?=$product?>&no_loading=yes" target="_top">
												<img src="<?=NIVEAU_0?>images/icones/change.gif" style="vertical-align:middle;" onMouseOver="popalt('Change family');style.cursor='help';" onMouseOut='kill()' border="0"/>
										</a>
									<? 	} //fin condition sur les familles ?>
								</div>
							</td>
						</tr>
						<!-- Info box -->
						<tr>
							<td>
								<div>
									<img src="<?=NIVEAU_0?>images/icones/information.png" border="0" onclick="Effect.toggle('help_box_1', 'slide');" />
									<div id="help_box_1" class="infoBox">
										<?=__T('A_SETUP_NETWORK_AGGREGATION_HELP')?>
									</div>
								</div>
							</td>
						</tr>
						<!-- Network agregation -->
						<tr>
							<td>
								<fieldset>
								<legend class="texteGrisBold">
										&nbsp;
										<img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif"/>&nbsp;
										Network aggregation list
										&nbsp;
								</legend>
								<table align="center" cellpadding="3" cellspacing="1">
								<?
									// Requête qui récupère les éléments non modifiables
									// 30/04/2009 MPR : On affiche uniquement les niveaux d'agrégation 3ème axe
									$queryMandatory = "SELECT DISTINCT n.agregation_rank,n.agregation,n.agregation_label,n.source_default
									FROM sys_definition_network_agregation n LEFT JOIN sys_definition_group_table_network g
									ON n.agregation = g.network_agregation
									WHERE n.family = '$family'
										AND axe is null
										AND n.mandatory = 1
									ORDER BY n.agregation_rank DESC";

									// Requête qui récupère les éléments créés par le client
									// maj 18/05/2009 BBX : correction de la requête. BZ 9774
									// 28/07/2009 BBX : modification de la requête pour prendre en compte le 3ème axe
									$queryAdditional = "SELECT DISTINCT n.agregation_rank,n.agregation,n.agregation_label,n.source_default,
									(CASE WHEN g.network_agregation IS NULL THEN 0 ELSE 1 END) as on_off
									FROM sys_definition_network_agregation n
									LEFT JOIN (
										SELECT network_agregation FROM sys_definition_group_table_network WHERE id_group_table =
										(SELECT rank FROM sys_definition_categorie WHERE family = '$family')) g
									ON CASE WHEN
										(SELECT count(agregation) FROM sys_definition_network_agregation WHERE family = '$family' AND axe = 3) = 0
										THEN (n.agregation = g.network_agregation)
										ELSE (g.network_agregation LIKE n.agregation||'_%')
									END
									WHERE n.family = '$family'
									AND n.mandatory IS NULL
									AND axe is null
									ORDER BY n.agregation_rank DESC";

									// Récupération du nombre d'éléments
									$resultat = $database->execute($queryMandatory);
									$nombre_connection = $database->getNumRows();
									$resultat = $database->execute($queryAdditional);
									$nombre_connection += $database->getNumRows();
								?>
									<input type="hidden" name="row_table" value="<?=$nombre_connection-1?>">
								<? if ($nombre_connection > 0) { ?>
										<tr>
											<th class="texteGrisBold">Name</th>
											<th class="texteGrisBold">Label</th>
											<th class="texteGrisBold">Aggregation Source</th>
										</tr>
								<? } else {
								?>
										<tr>
											<td class="texteGrisBold">No network aggregation defined for this family.</td>
										</tr>
								<?  }
									foreach($database->getAll($queryMandatory) as $row){
										$disabled = " disabled=\"disabled\" ";
								?>
										<tr>
											<td><input class="zoneTexte" name="tana_name<?=$i?>" type="text" size="20"  value="<?=$row["agregation"]?>" <?=$disabled?>></td>
											<td><input class="zoneTexte" size="20" name="tana_label" type="text"  value="<?=$row["agregation_label"]?>" <?=$disabled?>></td>
											<td align="center"><input class="zoneTexte" name="login<?=$i?>" type="text" size="10" value="<?=$row["source_default"]?>" <?=$disabled?>></td>
										</tr>
								<?  }
									foreach($database->getAll($queryAdditional) as $row){
										$disabled = " readonly";
										$isDeployed = ($row['on_off'] == 1);
										$backgroundColor = ($row['on_off'] == 1) ? '' : '#F8DED1';
								?>
										<tr id="tr_<?=$row["agregation"]?>" style="background-color:<?=$backgroundColor?>">
											<td><input class="zoneTexte" name="tana_name<?=$i?>" type="text" size="20"  value="<?=$row["agregation"]?>" <?=$disabled?>></td>
											<td><input class="zoneTexte" size="20" name="tana_label" type="text"  value="<?=$row["agregation_label"]?>" <?=$disabled?>></td>
											<td align="center"><input class="zoneTexte" name="login<?=$i?>" type="text" size="10" value="<?=$row["source_default"]?>" <?=$disabled?>></td>
											<td align="center">
												<?php
												// Si le niveau n'est pas déployé, on propose l'activation
												if(!$isDeployed) {
												?>
												<img src="<?=NIVEAU_0?>images/icones/bullet_go.png" border="0" style="cursor:pointer;" onmouseover="popalt('<?=__T('A_SETUP_NETWORK_AGGREGATION_ACTIVATE')?>')" onclick="deployElement(this,'<?=$row["agregation"]?>')" />
												<?php
												}
												// Dans tous les cas, on autorise la suppression
												?>
											</td>
											<td align="center">
												<img src="<?=NIVEAU_0?>images/icones/drop.gif" border="0" style="cursor:pointer;" onmouseover="popalt('<?=__T('A_SETUP_NETWORK_AGGREGATION_DElETE')?>')" onClick="javascript:setup_tana_delete(this,'<?=$row["agregation"]?>','<?=$table?>','<?=$family?>','<?=$product?>')">
											</td>
										</tr>
								<?  }  ?>
								</table>
								</fieldset>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</form>
		<!-- FIN DU FORMULAIRE -->
    </td>
  </tr>
</table>
</body>
</html>
