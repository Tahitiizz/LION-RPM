<?php

/**
* Formulaire de commentaire de graphiques
* 
* Ce fichier est une extraction d'intranet_top.php histoire de modulariser intranet_top.php
* 20/03/2009 - modif SPS : utilisation d'un tableau de priorite au lieu de faire une requete en base
*/

?>
<div id="ajouter_commentaire" class="texteGris" style="padding:2px;">
	<?
	// liste des type de commentaires.
	/*$query_type_comment = " select * from sys_type_comment ";
	$result_type_comment = pg_query($database_connection,$query_type_comment);
	$nombre_resultat_type_comment = pg_num_rows($result_type_comment);
	*/
	
	//tableau des types de priorites
	$tab_type_priority = array(
		array( 'id_type_priority' => 1, 'type_priority_label' => "Priority", 'icone' => ""),
		array( 'id_type_priority' => 2, 'type_priority_label' => "information", 'icone' => "cercle_info.gif"),
		array( 'id_type_priority' => 3, 'type_priority_label' => "medium", 'icone' => "cercle_orange.gif"),
		array( 'id_type_priority' => 4, 'type_priority_label' => "critical", 'icone' => "cercle_rouge.gif"),
	);
	$nombre_resultat_type_priority = count($tab_type_priority);

	
	?>

	<table cellpadding="3" cellspacing="2" align="center" class="texteGrisPetit" width="100%">
		<tr>
			<td align="left" colspan="2">
				
			</td>
			<td align="right">
				<input type="text" class="" onFocus="if(this.value=='Trouble ticket') this.value='';"
				id="comment_trouble_ticket" name="comment_trouble_ticket" class="zoneTexteStyleXP" value="Trouble ticket"
				style="font : normal 7pt Verdana, Arial, sans-serif;color : #585858;">
			</td>
		</tr>
		<tr>
			<td align="left" colspan="3">
					<? // 01/09/2014 FGD - Bug 43450 - [REC][CB 5.3.3.01][TC#TA-56752][GUI][FF 31 compatibility][Graph Comment] Add comment form is displayed not well ?>
					<textarea id="comment_content" onKeyUp="limite(this,255);" onKeyDown="limite(this,255);" name="comment_content" style="border: #7F9DB9 1px solid;font: 8pt verdana, arial, helvetica;color: #585858;width:335px" rows="6" cols="52" onFocus="if(this.value=='Your comment...' || this.value=='Please write your comment.') this.value='';">Your comment...</textarea>
			</td>
		</tr>
			<tr>
				<td align="left" colspan="3">
					<textarea id="comment_action" onKeyUp="limite(this,255);" onKeyDown="limite(this,255);" name="comment_action" style="border: #7F9DB9 1px solid;font: 8pt verdana, arial, helvetica;color: #585858;width:335px" rows="4" cols="52" onFocus="if(this.value=='Action...') this.value='';">Action...</textarea>
				</td>
			</tr>
			<tr>
				<td align="left">
					<select id="comment_level" name="comment_level" class="texteGrisPetit">
						<option value="0" selected="selected">No level</option>
						<?
						/*
						
						if($nombre_resultat_type_priority > 0){
							for ($i = 0;$i < $nombre_resultat_type_priority;$i++){
								$row = pg_fetch_array($result_type_priority, $i);
								echo $row["id_type_priority"]."<br>";
								//$selected = ($row["id_type_priority"]) == 1 ? " selected=\"selected\" " : "";
								?>
								<option value="<?=$row["id_type_priority"]?>"><?=$row["type_priority_label"]?></option>
								<?
							}
						}*/
						if($nombre_resultat_type_priority > 0){
							for ($i = 0;$i < $nombre_resultat_type_priority;$i++){
								$id_type_priority = $tab_type_priority[$i]["id_type_priority"];
								$label_type_priority = $tab_type_priority[$i]["type_priority_label"];
								
								echo "<option value=\"$id_type_priority\">$label_type_priority</option>";
							}
						}
					
						?>
					</select>
				</td>
				<td align="center">
					<div id="comment_alert" class="texteRougeBold">
					</div>
				</td>
				<td align="right">
					<input type="button" id="comment_save" name="comment_save" value="Save" class="bouton"
							onClick="verifier_commentaire();">
					<input type="hidden" id="obj_cible" value="">
					<input type="hidden" id="obj_alert_user" value="">
					<input type="hidden" id="liste_param" value="">
				</td>
			</tr>
	</table>
</div>

