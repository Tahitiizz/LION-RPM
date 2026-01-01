<?
/*
 * @cb50000@
 * 09/11/2011 - Copyright Astellia
 *
 * 09/11/2011 ACS BZ 24526 Display a message when saving or deleting data history configuration
 */
?>
<?
/*
*	@cb41000@
*
*	09/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	09/12/2008 BBX : modifications pour le CB 4.1 :
*	=> Utilisation des nouvelles classes (DatabaseConnection, etc...)
*	=> Contrôle d'accès
*	=> Utilisation des nouvelles constantes
*	=> Gestion du produit
*
*	26/11/2009 BBX:
*	=> Ajout de l'id produit à la fonction getFamilyList. BZ 10718
*
*/
?>
<?
/*
*	@cb30000@
*
*	23/07/2007 - Copyright Acurio
*
*	Composant de base version cb_3.0.0.00
*
*	- maj 07/08/2007 Jérémy : 	Ajout d'une condition pour afficher l'icone de retour au choix des familles
*						Si le nombre de famille est supérieur à 1 on affiche l'icône, sinon, on la cache
*	- maj 13/08/2007 Jérémy : 	Ajout du nom de l'interface dans la table message_display (G_INTERFACE_TITLE_SETUP_DATA_HISTORY)
*
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
*	@cb21001_gsm20010@
*
*	08/12/2006 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.00
*
*	Parser version gsm_20010
*
*/
?>
<?
// Permet de modifier les paramètres time agregation pour une famille donnée.
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Connexion à la base de données locale
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection();

// Librairies et classes nécessaires
include_once(REP_PHYSIQUE_NIVEAU_0.'php/edw_function_family.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/ProfileModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/UserModel.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'/class/select_family.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'/intranet_top.php');

// Récupération des valeurs
$family = $_GET["family"];
$product = $_GET["product"];
$saveSuccessful = ($_GET["save"] == true);

// Intranet top + menu contextuel
include_once(REP_PHYSIQUE_NIVEAU_0 . "/intranet_top.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "/php/menu_contextuel.php");

// 27/07/2010 OJT : Correction bz16899, mise en commentaire du contrôle d'accès
/*// Contrôle d'accès
$userModel = new UserModel($_SESSION['id_user']);
$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu ILIKE 'Setup Data History'";
$result = $database->getRow($query);
$idMenu = ($result['id_menu'] == '') ? 0 : $result['id_menu'];
if(!$userModel->userAuthorized($idMenu)) {
	include_once(REP_PHYSIQUE_NIVEAU_0."intranet_top.php");
	echo '<div class="errorMsg">'.__T('A_USER_NOT_ALLOWED_TO_ACCESS').'</div>';
	exit;
}*/

// Connexion à la base du produit
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection($product);
?>
<html>

<title>Edit History parameters</title>
<link rel="stylesheet" type="text/css" media="all" href="<?=NIVEAU_0?>css/global_interface.css">
<body>
<?
	if(!isset($_GET["family"])){
		$select_family = new select_family($_SERVER['PHP_SELF'], $_SERVER['argv'][0], __T('G_INTERFACE_TITLE_SETUP_DATA_HISTORY'));
		exit;
	}

// On récupère dans un premier temps les paramètres dans la table sys_definition_history. Si l'un des paramètres n'est pas défini dans sys_definition_history on le récupère dans sys_global_parameters
// Le paramètre offset_day est directement récupéré dans sys_global_parameters

// Liste des ta actives
$lst_ta = getTaList(" and primaire = 1");

$cpt = 0;

foreach($lst_ta as $k=>$v) {
	$parameters['lib'][$cpt] = $k;
	$parameters['name'][$cpt] = 'history_'.$k;
	$parameters['label'][$cpt] = $v;
	$cpt++;
	$nb_parameters++;
}

$parameters['unit'][0] = 'days';
$parameters['unit'][1] = 'days';
$parameters['unit'][2] = 'weeks';
$parameters['unit'][3] = 'months';

// On récupère les paramètres présents ds sys_definition_history
$query = "SELECT ta,duration FROM sys_definition_history WHERE family = '".$family."'";
$res = $database->execute($query);

// On récupère les paramètres présents ds sys_gobal_parameters
$q = "SELECT parameters,value FROM sys_global_parameters WHERE parameters = '".$parameters['name'][0]."'
OR parameters = '".$parameters['name'][1]."'
OR parameters = '".$parameters['name'][2]."'
OR parameters = '".$parameters['name'][3]."'";

$r = $database->execute($q);
unset($offsets);
// On recherche d'abord les paramètres dans la table sys_defintion_history
while($row = $database->getQueryResults($res,1)) {
	if(($row['duration']!=null)or($row['duration']!=''))
		 $offsets['history_'.$row['ta']] = $row['duration'];
}

// On récupère les paramètres non définis dans sys_defintion_history
while($row = $database->getQueryResults($r,1))
{
	$trouve = false;
	$cpt = 0;
	while(($cpt<=$nb_parameters)and($trouve==false))
	{
		//Si le paramètre n'est pas présent dans sys_definition_history, on récupère les paramètres dans sys_global_parameters
		if(isset($offsets[$row['parameters']]))
				$trouve = true;
		else
				$offsets[$row['parameters']] = $row['value'];
		$cpt++;
	}
}
// 11/07/2014 NSE Mantis 5449: Amélioration de l'historique des données 
// On récupère les maximums autorisés pour chaque TA
$query = "SELECT parameters,value FROM sys_global_parameters WHERE parameters like '".$parameters['name'][0]."_max_%'
OR parameters like '".$parameters['name'][1]."_max_%'
OR parameters like '".$parameters['name'][2]."_max_%'
OR parameters like '".$parameters['name'][3]."_max_%'";

$res = $database->getAll($query);
if(count($res) > 0){
    foreach($res as $row) {
        // Customisateur
        if($row['parameters']==$parameters['name'][0]."_max_customisateur")
            $parameters['max_customisateur'][0] = $row['value'];
        if($row['parameters']==$parameters['name'][1]."_max_customisateur")
            $parameters['max_customisateur'][1] = $row['value'];
        if($row['parameters']==$parameters['name'][2]."_max_customisateur")
            $parameters['max_customisateur'][2] = $row['value'];
        if($row['parameters']==$parameters['name'][3]."_max_customisateur")
            $parameters['max_customisateur'][3] = $row['value'];
        // Client admin
        if($row['parameters']==$parameters['name'][0]."_max_client")
            $parameters['max_client'][0] = $row['value'];
        if($row['parameters']==$parameters['name'][1]."_max_client")
            $parameters['max_client'][1] = $row['value'];
        if($row['parameters']==$parameters['name'][2]."_max_client")
            $parameters['max_client'][2] = $row['value'];
        if($row['parameters']==$parameters['name'][3]."_max_client")
            $parameters['max_client'][3] = $row['value'];
    }
}

// 09/11/2011 ACS BZ 24526 Display a message when saving or deleting data history configuration
?>
<script type="text/javascript">
  function verifMax(){
    <?php
    foreach($parameters['lib'] as $k=>$v){
        // préparation des tests pour toutes les TA
        $tabMinZero[] = "document.getElementById('{$v}_value').value>=0 && document.getElementById('{$v}_value').value % 1 == 0";
        $tabMaxClient[] = "!document.getElementById('{$v}_value').disabled && document.getElementById('{$v}_value').value>{$parameters['max_client'][$k]}";
        $tabMaxCusto[] = "document.getElementById('{$v}_value').value>{$parameters['max_customisateur'][$k]}";
    }
    ?>
    if(!(<?=implode(' && ', $tabMinZero)?>)){
        alert('<?=__T('A_SETUP_DATA_HISTORY_POSITIVE_INTEGER')?>');
        return false;
    }
    if(<?=implode(' || ', $tabMaxClient)?>){
        // on dépasse le maximum client
        <?php
        if($client_type=='client'){?>
            //autorisé pour le client
            alert('<?=__T('A_SETUP_DATA_HISTORY_LIMITS_VALUES',$parameters['max_client'][0],$parameters['max_client'][1],$parameters['max_client'][2],$parameters['max_client'][3])?>');
            return false;
            <?php
        }
        else{?>
            if(<?=implode(' || ', $tabMaxCusto)?>){
                // on dépasse le maximum customisateur
                //autorisé
                alert('<?=__T('A_SETUP_DATA_HISTORY_LIMITS_VALUES',$parameters['max_customisateur'][0],$parameters['max_customisateur'][1],$parameters['max_customisateur'][2],$parameters['max_customisateur'][3])?>');
                return false;
            }
            else{
                // conseillé
                if(confirm('<?=__T('A_SETUP_DATA_HISTORY_LIMITS_RECOMMENDED_CONFIRM')?>'))
                    return true;
                else
                    return false;
            }
        <?php
        }?>
    }
    return true;
  }
</script>
	<form name="sdh_form" method="POST" action="setup_data_history_traitement.php?family=<?=$family?>&product=<?=$product?>" onsubmit="if( $('msg_info')) $('msg_info').style.display = 'none';return verifMax();">
	<table cellpadding="0" cellspacing="0" border="0" align="center">
		<tr>
			<td align="center">
				<img src="<?=NIVEAU_0?>/images/titres/setup_data_history.gif"/>
			</td>
		</tr>
		<tr>
			<td>
				&nbsp;
			</td>
		</tr>
		<tr>
			<td>
				<table cellspacing="2" cellpadding="3" class="tabPrincipal" align="center">
					<tr>
						<td class="texteGrisBold">
						<?
						// 26/11/2009 BBX
						// Ajout de l'id produit à la fonction getFamilyList. BZ 10718
						//$familyList = getFamilyList($product);
						// Fin BZ 10718
						?>
							<div align="center" style="padding-top:10px;padding-bottom:10px;">
								<?php
								// Recuperation du label du produit
								$productInformation = getProductInformations($product);
								$productLabel = $productInformation[$product]['sdp_label'];
								echo $productLabel."&nbsp;:&nbsp;";

								// Recuperation du label de la famille
								$family_information = get_family_information_from_family($family, $product);
								echo (ucfirst($family_information['family_label']));

								// MàJ 07/08/2007 - JL :  Ajout condition d'affichage de l'icone
									if (get_number_of_family() > 1){ 
										// maj 06/08/2009 - MPR : Correction du bug 10828 - Retour vers la famille
									?>
										<a href="setup_data_history.php?product=<?=$product?>">
										<img src="<?=NIVEAU_0?>images/icones/change.gif" border="0" style="vertical-align:middle;" onmouseover="popalt('Change family');"/>
										</a>
								<? 	} //fin condition sur les familles ?>
									<br />
							</div>

							<fieldset>
							<legend class="texteGrisBold">&nbsp;<img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif"/>&nbsp;Parameters list&nbsp;</legend>
							<table cellpadding="2" cellspacing="2" border="0">
								<tr>
									<td class="texteGrisBold" align="center">Time Aggregation</td>
									<td class="texteGrisBold" align="center">Value</td>
									<td>&nbsp;</td>
								</tr>
								<?
								//var_dump($offsets);
									foreach($parameters['lib'] as $k=>$v)
									{
										?>
										<tr>
                                                                                    <td style="font-weight: normal; color: #000;"><?=$parameters['label'][$k]?> <span class="texteGris">(<?=$parameters['max_client'][$k].' '.$parameters['unit'][$k]?> max.)<?=$client_type=='customisateur'?'*':''?></span></td>
                                                                                    <td><input type="text" style="text-align:right; width:40px;" name="<?=$v?>_value" id="<?=$v?>_value" value="<?=$offsets[$parameters['name'][$k]]?>"<?=$client_type!='customisateur'&&$offsets[$parameters['name'][$k]]>$parameters['max_client'][$k]?' disabled title="'.__T('A_SETUP_DATA_HISTORY_PROTECTED').'"':''?>/>
											</td>
											<td class="texteGris">&nbsp;<?=$parameters['unit'][$k]?></td>
										<input type="hidden" name="<?=$v?>" value="<?=$v?>" />
										</tr>
									<?
									}
									?>
								<tr>
                                                                    <td class="texteGris" style="font-size:8px;	vertical-align: bottom;"><?=$client_type=='customisateur'?__T('A_SETUP_DATA_HISTORY_LIMITS_RECOMMENDED'):''?></td>
									<td align="right">
										<input type="submit" class="bouton" name="save_parameters" value="Save"/>
									</td>
									<td>&nbsp;</td>
								</tr>
							</table>
                            <?php
                            // 09/11/2011 ACS BZ 24526 Display a message when saving or deleting data history configuration
                            if ($saveSuccessful) {
                            	echo '<div id="msg_info" class="okMsg">'.__T('A_SETUP_DATA_HISTORY_SAVE_OK').'</div>';
                            }
                            ?>
							</fieldset>

						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	</form>
</body>
</html>
