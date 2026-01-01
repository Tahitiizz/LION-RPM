<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	- 09:02 25/01/2008 Gwénaël : modif pour la récupération du paramètre client_type
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
*	16/06/2006 - Copyright Acurio
*
*	Composant de base version cb_1.2.0.2p
*
*	Parser version gb_1.0.0b
*/
?>
<?
	/*
		- maj 22 06 2006 christophe : si le client_type=="client" on ne peut pas supprimer l'alarme
		- maj 23 06 2006 stephane : remplacement de network --> agregation_label dans les listings
		- maj 28 06 2006 christophe : correction de la requête de la liste des NA/alarmes, il manquait une jointure sur les familles.
	*/
	/**
	 * Gère les données de paramétrage qui servent à connecter l'application
	 * aux bases de données tiers et répertoire racine qui contient des flat file
	 */

	$lien_css = $path_skin . "easyopt.css";
	$comeback=$PHP_SELF;
	session_register("comeback");
	$family = $_GET["family"];
?>
<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css">
<script src="<?=$niveau0?>js/myadmin_omc.js"></script>
<script src="<?=$niveau0?>js/gestion_fenetre.js"></script>
<script src="<?=$niveau0?>js/fenetres_volantes.js"></script>
<script src="<?=$niveau0?>js/fonctions_dreamweaver.js"></script>

 <table width="550" align="center" valign=middle cellpadding="0" cellspacing="0" >
	<tr>
		<td align="center">
			<img src="<?=$niveau0?>images/titres/setup_alarm_titre.gif"/>
		</td>
	</tr>
<tr>
<td >

 <table width="100%" border=0 align="center" cellspacing="3" cellpadding="3" class="tabPrincipal">

<?
	// On vérifie qu'il existe des group table pour la famille spécifiée en paramètre.
	$query = "SELECT * FROM sys_definition_group_table_ref WHERE family='$family' ";
	$resultat = pg_query($database_connection, $query);
    $nb = pg_num_rows($resultat);
	if($nb == 0){
		?>
			<tr>
				<td class="texteGrisBold" align="center">Error : No group table for this family.</td>
			</tr>
			<tr>
				<td align="center"><a href="setup_alarm_main.php" class="texteGris"><u>>>> Back</u></a></td>
			</tr>
		<?
		exit;
	}

	$flag_axe3=get_axe3($family);
?>
  <tr>
    <td>
     <div style="padding: 2px;">
   <?
    $title="Set-up Alarm Interface";
    //include("header_design.php");
    //ici le contenu
    //liste les alarmes existantes
          $query = "SELECT DISTINCT sdsa.alarm_id,
					sdsa.alarm_name,
					sdsa.network,
					sdsa.time,
					sdsa.home_net,
					sdsa.client_type,
					sys_definition_network_agregation.agregation_label
		  	FROM sys_definition_static_alarm as sdsa
		  	LEFT OUTER JOIN sys_definition_network_agregation
			ON (sdsa.network = sys_definition_network_agregation.agregation AND sdsa.family = sys_definition_network_agregation.family)
			WHERE sdsa.family='$family'";

          $resultat = pg_query($database_connection, $query);
          $nombre_connection = pg_num_rows($resultat);
          ?>
          <table align=center>
             <tr align="center">
                 <td colspan="9">
                     <table width="50%" border="0">
                            <tr align="center">
                                <td>
                                    <a href="setup_alarm_detail.php?family=<?=$family?>"><input type="button"class="bouton" name="parameter" value="New Alarm"></a></td>
                                <td>
                            </tr>
                        </table>
                 </td>
            </tr>
			<tr>
				<td align="center">
					<fieldset>
					<legend class="texteGrisBold">&nbsp;<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>&nbsp;Current family&nbsp;</legend>
						<table cellspacing="2" cellpadding="2" border="0">
							<tr>
								<td align="left" class="texteGris">
								<?
									$family_information = get_family_information_from_family($family, $product);
									echo (ucfirst($family_information['family_label']));
								?>
								</td>
								<td align="center" valign="top" class="texteGris">
									<a href="setup_alarm_index.php" target="_top">
										<img src="<?=$niveau0?>images/icones/change.gif" onMouseOver="popalt('Change family');style.cursor='help';" onMouseOut='kill()' border="0"/>
									</a>
								</td>
							</tr>
						</table>
					</fieldset>
				</td>
			</tr>
			<tr>
				<td>
					<fieldset>
					<legend class="texteGrisBold">
						&nbsp;
						<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>
						&nbsp;
						Alarm list
						&nbsp;
					</legend>
					<table cellpadding="2" cellspacing="2" border="0">
					<?
						if ($nombre_connection == 0) {
					?>
						<tr><td class="texteGrisBold" align="center">No alarm registered.</td></tr>
					<?
						}
					?>
	<? if ($nombre_connection) { ?>
		  <tr>
		  	<td align=center><font class="texteGrisBold">Alarm Name</font></td>
		  	<td align=center><font class="texteGrisBold">Network Level</font></td>
		  	<td align=center><font class="texteGrisBold">Time Level</font></td>
			<?
			if ($flag_axe3) {
				?><td align=center><font class="texteGrisBold">Home Network</font></td><?
			}

			?>

			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
	<? } else { ?>

	<? } ?>

		<?
          for ($i = 0;$i < $nombre_connection;$i++)
              {
				$row = pg_fetch_array($resultat, $i);
				// On vérifie si l'envoie des alarmes par email a été configuré.
				$query_send = " select * from sys_alarm_email_sender where id_alarm = ".$row["alarm_id"];
				$resultat_send = pg_query($database_connection, $query_send);
				$nb_send = pg_num_rows($resultat_send);
				if($nb_send > 0){
					$send_icon = "send_vert.gif";
					$msg = "Sends of email is configured";
				} else {
					$send_icon = "send_rouge.gif";
					$msg = "Sends of email is NOT configured";
				}
			   ?>
               <tr>
                 <td><input class="zoneTexteBlanche"  type="text" size="30"  name="" value="<?=$row["alarm_name"]?>"></td>
                 <td><input class="zoneTexteBlanche" name="network<?=$i?>" type="text" size="20"  value="<?=$row["agregation_label"]?>"></td>
                 <td><input class="zoneTexteBlanche" name="network<?=$i?>" type="text" size="20"  value="<?=$row["time"]?>"></td>
                 <?
				 if ($flag_axe3) {
				 	?><td><input class="zoneTexteBlanche" name="network<?=$i?>" type="text" size="20"  value="<?=$row["home_net"]?>"></td><?
                 }
				 ?>
				 <td>
					<?
						$display = true;
						// modif 09:02 25/01/2008 Gwénaël
							// modif pour la récupération du paramètre client_type
						if(getClientType($_SESSION['id_user']) == "client" && $row["client_type"] != "client"){
							$display = false;
						}
						if($display){
					?>
					 <td><a href="setup_alarm_detail.php?family=<?=$family?>&alarm_id=<?=$row['alarm_id']?>"><img src="<?=$niveau0?>images/icones/A_more.gif" border="0"></a></td>
					<a href="javascript:setup_alarm_delete('<?=$row["alarm_id"]?>','<?=$row["alarm_name"]?>')"><img src="<?=$niveau0?>images/icones/drop.gif" border="0"></a>
					<? 	} ?>
				</td>

                 <td title="Send this alarm to ..." onclick="ouvrir_fenetre('setup_aflarm_send_to.php?alarm_id=<?=$row["alarm_id"]?>','Update_Alarm','yes','no',870,600)"><img src="<?=$niveau0?>images/icones/<?=$send_icon?>" border="0" alt="<?=$msg?>"></a></td>
               </tr>
       <?php
}
        ?>
			</table>
			</fieldset>
			</td>
		</tr>
	  </table>
   </div></td>
  </tr>

</table>
</td>
</tr>
</table>
</form>
