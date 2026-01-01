<?php
/*
	07/07/2009 GHX
		- Correction d'un problème sur la couleur des icones sur les niveaux (critical, medium...)
		- Correction du BZ 9811 [REC][T&A Cb 5.0][COMMENT]: impossible de saisir un commentaire
	
	22/09/2009 BBX
		- Mise à jour de la variable $_SESSION['dashboard_export_buffer']['comment'] lors de l'ajout d'un commentaire. BZ 11415
*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*
*	- 18/04/2007 christophe : changement du format de la chaine de param d'un commentaire.
* 	- 19/03/2009 modif SPS : utilisation de la classe DataBaseConnection pour les requetes SQL
*
*/
?>
<?
	/*
		Permet d'afficher la liste des commentaires sur un élément d'un type précis.

		- maj 06 03 2006 christophe : le hn ne s'affiche pas si il n'y a pas de 3ème axe (hn=0 s'affichait).
		- maj 13 03 2006 christophe : 	* les textarea sont entouré de orange.
							* affichage des dates conformes aux normes de l'appli.
							* affichage du nom du dashboard.
		- maj le 05 05 2006 christophe : correction bug flyspray n°292, maj du dernier commentaire saisit dans la fenêtre "comment list"
		- maj le 15 05 2006 christophe : correction, on pouvait saisir un lib de commentaire vide pour la modif d'un commentaire, modif ligne 65.
		- maj 30 08 2006 christophe : si il n'y a pas de type définit, on ne l'affiche pas.
	*/

	session_start();
	include_once("../../php/environnement_liens.php");
	include_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function.php");
	include_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_date.php");
	include_once(REP_PHYSIQUE_NIVEAU_0."class/Date.class.php");
	
	/*
		Initialisation des variables.
	*/
	$currentDate = date("Ymd");

	/*
		Permet de mettre à jour le dernier commentaire saisit dans la liste des dashboards.
	*/
	if(isset($_GET["id_dernier_commentaire"])){
		$id_dernier_commentaire = $_GET["id_dernier_commentaire"];
	}
	if(isset($_POST["id_dernier_commentaire"])){
		$id_dernier_commentaire = $_POST["id_dernier_commentaire"];
	}

	// On récupère les infos passées en paramètres.
	if(isset($_GET["dashboard"])){
		$titre_dashboard = $_GET["dashboard"];
	}
	if(!isset($_GET["params_list"])){
		echo "[ERROR] an argument is missing (params_list).";
		exit;
	}
	$liste_param_commentaire = explode("@",$_GET["params_list"]);
	$params_list = "params_list=".$_GET["params_list"];

	$type_elem = 	$liste_param_commentaire[0];
	$id_elem = 		$liste_param_commentaire[1];
	$ta = 			$liste_param_commentaire[2];
	$na = 			$liste_param_commentaire[3];
	$na_value = 	$liste_param_commentaire[4];
	$date_selecteur = $liste_param_commentaire[5];
	
	// Liste des label des NA mères.
	$lst_na_mere_label = getNaLabelList('all',$family);
	$lst_na_mere_label = $lst_na_mere_label[$family];

	/*
		MODIFICATION D'UN COMMENTAIRE
			enregistrement de la modification d'un commentaire.
	*/
	if(isset($_POST["id"])){
		if(trim($_POST["libelle_comment"]) != ""){
			$id = $_POST['id'];
			$comment = addslashes($_POST["libelle_comment"]);
			
			//si on n'a saisi aucun commentaire, on n'enregistre rien en base
			if ($_POST["libelle_action"]!="Action...") {
				$action = addslashes($_POST["libelle_action"]);
			} 
			else {
				$action = "";
			}
			
			$query_update = "
				UPDATE edw_comment
				SET
					libelle_comment = '$comment',
					libelle_action = '$action'
				WHERE
					id_comment = $id
			";
                        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
			$db = Database::getConnection();
			$db->executeQuery($query_update);
		}
	}

	/*
		AJOUT D UN NOUVEAU COMMENTAIRE
			enregistrement d'un nouveau commentaire.
	*/
	if(isset($_POST["comment_content"])){
		// On récupère les données.
		//$comment_type = 			isset($_POST["comment_type"]) ? $_POST["comment_type"] : 0;
		$comment_trouble_ticket = 	$_POST["comment_trouble_ticket"];
		$comment_content = 			$_POST["comment_content"];
		$comment_action = 			$_POST["comment_action"];
		$comment_level = 			$_POST["comment_level"];

		// On récupère la liste des paramètres.
		$id_comment_type = 			$comment_type;
		$id_priority_type = 		$comment_level;
		$date_ajout = 				date("Ymd G:i");
		$trouble_ticket_number = 	$comment_trouble_ticket;
		$lib_comment = 				$comment_content;
		
		//si on n'a saisi aucun commentaire, on n'enregistre rien en base
		if ($comment_action!="Action...") {
			$lib_action = $comment_action;
		}
		else {
			$lib_action = "";
		}
		
		$query_insert = "
			INSERT INTO edw_comment
			(
				id_user,
				id_priority_type,
				date_ajout,
				date_selecteur,
				trouble_ticket_number,
				id_elem,
				type_elem,
				na,
				na_value,
				ta,
				libelle_comment,
				libelle_action
			)
			VALUES
			(
				'$id_user',
				$id_priority_type,
				'$date_ajout',
				'$date_selecteur',
				'$trouble_ticket_number',
				'$id_elem',
				'$type_elem',
				'$na',
				'$na_value',
				'$ta',
				'$lib_comment',
				'$lib_action'
			)
		";

                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
		$db = Database::getConnection();
		$db->executeQuery($query_insert);
		
		// 22/09/2009 BBX : BZ 11415
		// Mise à jour de la variable $_SESSION['dashboard_export_buffer']. 
		// Modèle Dashboard
		$dashModel = new DashboardModel($id_elem);
		$_SESSION['dashboard_export_buffer']['comment']	= $dashModel->getLastComment();
		
		// Mise à jour du dernier commentaire dasn la liste des graph.;
		$lib_comment = strlen($lib_comment) > 200 ? substr($lib_comment, 0, 200)."..." : $lib_comment;

		if(isset($id_dernier_commentaire) && $type_elem=="graph"){
			// On vire les retours chariot pour éviter les erreurs javascripts.
			$lib_comment = str_replace(CHR(10)," ",$lib_comment);
			$lib_comment = str_replace(CHR(13),"",$lib_comment);
		?>
			<script>
				function virer_car(chaine){
					temp = chaine.replace(/[àâä]/gi,"a");
					temp = temp.replace(/[éèêë]/gi,"e");
					temp = temp.replace(/[îï]/gi,"i");
					temp = temp.replace(/[ôö]/gi,"o");
					temp = temp.replace(/[ùûü]/gi,"u");
					temp = temp.replace(/[ç]/gi,"c");
					temp = temp.replace(/["''&]/gi," ");
					return temp;
		        }
				var texte_dernier_commentaire ='<?=$lib_comment?>';
				window.opener.document.getElementById('<?=$id_dernier_commentaire?>').innerHTML = texte_dernier_commentaire;
			</script>
		<?
		}

	}

	/*
		On récupère les paramètres de tri si il y en a.
	*/
	$date_sort = 		(isset($_POST["radio_date_sort"]) && (!isset($_GET["all"]))) ? $_POST["radio_date_sort"] : "desc";	// desc par défaut.
	$priority_sort = 	(isset($_POST["comment_level"])) && (!isset($_GET["all"])) ? $_POST["comment_level"] : 0;
	$comment_sort = 	(isset($_POST["comment_type"])) && (!isset($_GET["all"])) ? $_POST["comment_type"] : 0;
	$priority_sort_query = 	($priority_sort == 0) ? "" : " and id_priority_type=$priority_sort ";
	$comment_sort_query = 	($comment_sort == 0) ? "" : " and id_comment_type=$comment_sort ";

	/*
		Liste des types de commentaires et de priorité
	*/
	// liste des type de commentaires.
	/*$query_type_comment = 			" select * from sys_type_comment ";
	$result_type_comment = 			pg_query($database_connection,$query_type_comment);
	$nombre_resultat_type_comment = pg_num_rows($result_type_comment);
*/
	
	//tableau des types de priorites
	$tab_type_priority = array(
		1 => array( 'id_type_priority' => 1, 'type_priority_label' => "Priority", 'icone' => ""),
		4 => array( 'id_type_priority' => 4, 'type_priority_label' => "Critical", 'icone' => "cercle_rouge.gif"),
		3 => array( 'id_type_priority' => 3, 'type_priority_label' => "Medium", 'icone' => "cercle_orange.gif"),
		2 => array( 'id_type_priority' => 2, 'type_priority_label' => "Information", 'icone' => "cercle_info.gif")
	);
	$nombre_resultat_type_priority = count($tab_type_priority);

	
	/*
		On récupère les commentaires.
	*/
	$query = "
		SELECT edw_comment.oid, edw_comment.id_user as iduser, id_comment, id_comment_type, date_ajout, date_selecteur, id_priority_type,
				trouble_ticket_number, id_elem, na, na_value, ta, family,
				libelle_comment, libelle_action, login,
				substring(date_ajout from 1 for 8) as date_jour
			FROM edw_comment, users
			WHERE
			id_elem = 				'$id_elem'
			AND type_elem = 		'$type_elem'
		";
	if($type_elem == "graph" || $type_elem == "pie"){
		$query .= "
				AND na = 				'$na'
				AND na_value = 			'$na_value'
				AND ta = 				'$ta'
			";
	}
	$query .= "
			AND edw_comment.id_user = users.id_user
			$priority_sort_query
			$comment_sort_query
		ORDER BY
			edw_comment.oid $date_sort

	";
	
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
	$db = Database::getConnection();
	$result = $db->getAll($query);
	$nombre_resultat = count($result);
	
	$display_add_comment = false;
	if($type_elem != "dashboard" && $nombre_resultat == 0) $display_add_comment = true;

?>
<html>
	<head>
		<title>List of comments</title>
		<link rel="stylesheet" href="<?=NIVEAU_0?>css/global_interface.css" />
        <script type='text/javascript' src='<?=NIVEAU_0?>js/prototype/prototype.js'> </script>
        <script type='text/javascript' src='<?=NIVEAU_0?>js/prototype/window.js'> </script>
        <script type='text/javascript' src='<?=NIVEAU_0?>js/prototype/scriptaculous.js'> </script>
		<script type='text/javascript' src='<?=NIVEAU_0?>js/gestion_formulaire.js'></script>
		<script type='text/javascript' src='<?=NIVEAU_0?>js/fade_functions.js'></script>
		<script type='text/javascript' src='<?=NIVEAU_0?>js/toggle_functions.js'></script>
		<script type='text/javascript' src='<?=NIVEAU_0?>js/ajax_functions.js'></script>
		<script type='text/javascript' src='<?=NIVEAU_0?>js/fenetres_volantes.js'></script>
		<style type="text/css">
			#nouveau_commentaire {
				/*display : none; 12/10/2010 OJT : Correction bz 18444 */
				text-align : center;
				width : 100%;
				border-right : 	1px solid #585858;
				border-bottom : 1px solid #585858;
				border-top : 	1px solid #C9C9C9;
				border-left : 	1px solid #C9C9C9;
                /* 01/09/2010 OJT : Correction bz16917 pour DE Firefox, modification width et height */
				height : 250px;
				background-color : #F0EFEE;
			}
		</style>
		<script>

		/*
			Permet de vérifier si un commentaire a été correctement saisi.
		*/
		function verifier_commentaire(){
			valSaisie = document.getElementById('comment_content').value;
			var newVal = valSaisie.replace(/\s/g,"");	// on enlève tous les espaces blancs.
			var nb = newVal.length;
			if(newVal == '' || valSaisie == 'Your comment...' || valSaisie == 'Please write your comment.'){
				document.getElementById('comment_content').value = 'Please write your comment.';
				document.getElementById('comment_content').style.border = " 2px solid #FF0000 ";
			} else {
				document.add_comment.submit();
			}
		}
		</script>
	</head>
	<body>
		<table cellpadding="3" cellspacing="3" border="0" class="tabPrincipal" align="center" width="700px">
			<!-- image titre -->
			<tr>
				<td align="center">
					<img src="<?=NIVEAU_0?>images/titres/comment_list_titre.gif">
				</td>
			</tr>
			<!-- options d'affichage des commentaires -->
			<tr>
				<td>
					<fieldset>
					<legend class="texteGrisBold">
						&nbsp;
						<img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif">
						&nbsp;
						Sort by
						&nbsp;
					</legend>
						<table cellpadding="1" cellspacing="3" border="0" align="left" width="100%">
							<tr>
								<td>
									<form name="comment_form" method="POST" action="comment_list.php?<?=$params_list?>">
									<input type="hidden" value="<?=$id_dernier_commentaire?>" name="id_dernier_commentaire"/>
									<table cellpadding="0" cellspacing="0" align="left" height="25px">
									<tr>
										<td align="left"><img src="<?=NIVEAU_0?>images/divers/barre_bord_gauche.gif"></td>
										<!--  liste des types de commentaires -->
										<td align="left" valign="middle" class="fondGrisMoyen">
											<?
											/*if($nombre_resultat_type_comment > 0){
											?>
											<select id="comment_type" name="comment_type"  class="texteGrisPetit">
												<?
													$tab_comment_type = array();
													for ($i = 0;$i < $nombre_resultat_type_comment;$i++){
														$row = pg_fetch_array($result_type_comment, $i);
														$tab_comment_type[$row["id_type_comment"]] = $row["type_comment_label"];
														$selected = ($comment_sort == $row["id_type_comment"]) ? " selected " : "";
														?>
														<option <?=$selected?> value="<?=$row["id_type_comment"]?>"><?=$row["type_comment_label"]?></option>
														<?
													}
												?>
												<option value="0" <?=($comment_sort == 0) ? "selected" : ""; ?>>No type</option>
											</select>
											<?
											}*/
											?>
											&nbsp;
										</td>
										<td class="fondGrisMoyen">&nbsp;</td>
										<!-- liste des priorités -->
										<td valign="middle" class="fondGrisMoyen">
											<select id="comment_level" name="comment_level" class="texteGrisPetit">
												<?
													$tab_comment_priority = array();
													if($nombre_resultat_type_priority > 0){
														//for ($i = 0;$i < $nombre_resultat_type_priority;$i++){
														foreach ($tab_type_priority as $one_tab_type_priority){
															
															$id_type_priority = $one_tab_type_priority["id_type_priority"];
															$label_type_priority = $one_tab_type_priority["type_priority_label"];
															$icon_type_priority = $one_tab_type_priority["icone"];
															
															$tab_comment_priority["label"][$id_type_priority] = $label_type_priority;
															$tab_comment_priority["icon"][$id_type_priority] = $icon_type_priority;
															$selected = ($priority_sort == $id_type_priority) ? " selected " : "";
															
															echo "<option $selected value=\"$id_type_priority\">$label_type_priority</option>";
															
														}
													}
												?>
												<option value="0" <?=($priority_sort == 0) ? "selected" : "";?>>No level</option>
											</select>
											&nbsp;
										</td>
										<td class="fondGrisMoyen">&nbsp;</td>
										<!-- Tris divers -->
										<td class="texteGrisPetit" style="background-color: #E4E3E3;" valign="middle">
											<u>Date :</u> &nbsp;
											<?
												$checkedDesc = 	($date_sort=="desc") ? "checked" : "";
												$checkedAsc = 	($date_sort=="asc") ? "checked" : "";
											?>
											<span>desc</span>
											<input type="radio" id="radio_date_sort" name="radio_date_sort" value="desc" <?=$checkedDesc?>>
											&nbsp;
											<span>asc</span>
											<input type="radio" id="radio_date_sort" name="radio_date_sort" value="asc" <?=$checkedAsc?>>
										</td>
										<td class="fondGrisMoyen">&nbsp;&nbsp;</td>
										<td class="fondGrisMoyen">
											<a href="comment_list.php?all=all&<?=$params_list?>" class="texteGrisPetit" style="text-decoration:underline">View all</a>
										</td>
										<td class="fondGrisMoyen">&nbsp;&nbsp;</td>
										<td align="right" valign="middle" class="fondGrisMoyen">
											<input type="submit" class="bouton" value="Update"/>&nbsp;
										</td>
										<td align="left"><img src="<?=NIVEAU_0?>images/divers/barre_bord_droit.gif"></td>
									</tr>
									</table>
									</form>
								</td>
								<td align="right" valign="middle">
                                    <!-- 12/10/2010 OJT : Correction bz 18444, Utilisation du toggle PrototypeJs -->
									<input 
                                        type="button"
                                        class="bouton"
                                        value="New"
                                        id="bouton_ajout_comment"
                                        onclick="$('nouveau_commentaire').toggle(); if(this.value=='New') { this.value='Hide'; } else { this.value='New'; }"
                                    />
								</td>
							</tr>
						<tr>
						<td colspan="2">
						<div id="nouveau_commentaire">
						<?
							$param_sup = (isset($titre_dashboard)) ? "&dashboard=".$titre_dashboard : "" ;
						?>
							<form name="add_comment" method="POST" action="comment_list.php?<?=$params_list.$param_sup?>">
							<input type="hidden" value="<?=$id_dernier_commentaire?>" name="id_dernier_commentaire"/>
							<table cellpadding="2" cellspacing="0" align="center" width="100%">
								<tr>
									<td colspan="2">
										<table cellpadding="3" cellspacing="2" align="center" class="texteGrisPetit" width="100%">
											<tr>
												<td align="left">
													<?/*
														if($nombre_resultat_type_comment > 0){
													?>
													<!--  Liste des types de commentaires -->
													<select id="comment_type" name="comment_type"  class="texteGrisPetit">
														<?
															for ($i = 0;$i < $nombre_resultat_type_comment;$i++){
																$row = pg_fetch_array($result_type_comment, $i);
																//$selected = $row["id_type_comment"] == 1 ? " selected=\"selected\" " : "";
																?>
																<option <?=$selected?> value="<?=$row["id_type_comment"]?>"><?=$row["type_comment_label"]?></option>
																<?
															}
														?>
														<option value="0" selected="selected">No type</option>
													</select>
													<?
														}*/
													?>
												</td>
												<td align="right">
													<input type="text" class="" onFocus="if(this.value=='Trouble ticket') this.value='';" id="comment_trouble_ticket" name="comment_trouble_ticket" class="zoneTexteStyleXP" value="Trouble ticket" style="font : normal 7pt Verdana, Arial, sans-serif;color : #585858;">
												</td>
											</tr>
											<tr>
												<td align="left" colspan="2">
													<textarea id="comment_content" name="comment_content" class="zoneTexteStyleXP" rows="6" cols="105" onFocus="if(this.value=='Your comment...' || this.value=='Please write your comment.') this.value='';">Your comment...</textarea>
												</td>
											</tr>
											<tr>
												<td align="left" colspan="2">
													<textarea id="comment_action" name="comment_action" class="zoneTexteStyleXP" rows="4" cols="105" onFocus="if(this.value=='Action...') this.value='';">Action...</textarea>
												</td>
											</tr>
											<tr>
												<td align="left">
													<select id="comment_level" name="comment_level" class="texteGrisPetit">
														<?
															if($nombre_resultat_type_priority > 0){
																//for ($i = 0;$i < $nombre_resultat_type_priority;$i++){
																foreach ($tab_type_priority as $one_tab_type_priority){
																
																	$id_type_priority = $one_tab_type_priority["id_type_priority"];
																	$label_type_priority = $one_tab_type_priority["type_priority_label"];
																	echo $id_type_priority."<br>";
																	$selected = ($id_type_priority) == 1 ? " selected=\"selected\" " : "";
																	echo "<option value=\"$id_type_priority\" $selected>$label_type_priority</option>";
																	
																}
															}
														?>
														<option value="0" selected="selected">No level</option>
													</select>
												</td>
												<td align="right">
													<input type="button" id="comment_save" name="comment_save" value="Save" class="bouton"
														onClick="verifier_commentaire();">
												</td>
											</tr>
										</table>
										<!--</fieldset>-->
									</td>
								</tr>
							</table>
							</form>
						</div>
						</td>
						</tr>
						</table>
					</fieldset>
				</td>
			</tr>
			<!-- liste des commentaires -->
			<tr>
				<td>
					<fieldset>
					<legend class="texteGrisBold">
						&nbsp;
						<img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif">
						&nbsp;
						List of all comments
						<?
							if(isset($titre_dashboard)) echo " (Dashboard : $titre_dashboard)";
						?>
						&nbsp;
					</legend>
						<table cellpadding="3" cellspacing="3" border="0" align="center">

						<?
							if($nombre_resultat > 0){
								for ($i = 0;$i < $nombre_resultat;$i++)
								{
									$allow_modify = ($currentDate == $result[$i]["date_jour"] && $id_user == $result[$i]["iduser"]) ? true : false;
									$icone_entete = $allow_modify ? "commentaire_ajout.gif" : "commentaire.gif";
						?>
								<tr>
								<td>
								<form name="modif_comment" method="POST" action="comment_list.php?<?=$params_list?>">
								<table cellspacing="1" cellpadding="3" class="commentaireFond" width="660px">
								<!-- En-tête de l'affichage d'un commentaire -->
								<tr class="texteGrisBoldPetit">
									<td align="left" class="commentaireEntete">
										<table cellpadding="0" cellspacing="0" width="100%">
											<tr>
												<td class="texteGrisBoldPetit" align="left">
													<img src="<?=NIVEAU_0?>images/icones/<?=$icone_entete?>">
													<?
														echo "<i>by " . $result[$i]["login"] . "</i>, " . dateDBtoDisplay($result[$i]["date_ajout"], "-", true) . ".";
														
														if(!$allow_modify) echo "&nbsp;&nbsp;<font style='color:#FF0000;'><i>Archived</i></font>";
													?>
												</td>
												<td class="texteGrisPetit" align="right">
													<?
														$id_type_priority = $result[$i]["id_priority_type"];
														
														$tab_comment_priority["icon"][$id_type_priority] = $tab_type_priority[$id_type_priority]["icone"];
														$priority = ($id_type_priority == 0) ? "none" : $tab_comment_priority["label"][$id_type_priority];
														
														echo "priority : ".$priority;
														if(isset($tab_comment_priority["icon"][$id_type_priority])) {
															if($tab_comment_priority["icon"][$id_type_priority] != ""){
															?>
															<img src="<?=NIVEAU_0?>images/icones/<?=$tab_comment_priority["icon"][$id_type_priority]?>">
															<?
															}
														}
													?>
												</td>
											</tr>
											<tr>
												<td class="texteGrisPetit" align="left">
													<?
														if(!isset($titre_dashboard)){
															
															echo "[ ";
															$sep = get_sys_global_parameters('sep_axe3');
															if (strstr($result[$i]["na_value"],$sep))
															{
																$tab_na = explode('_',$result[$i]["na"]);
																$tab_na_value = explode($sep,$result[$i]["na_value"]);
																if ( count($tab_na) == count($tab_na_value) )
																{
																	for ($j=0; $j < count($tab_na) ;$j++)
																	{
																		$lab = getNaLabel($tab_na_value[$j],$tab_na[$j],$family);
																		if (empty($lab)) $lab = 'ALL'; 
																		echo $lst_na_mere_label[$tab_na[$j]]
																			."=".$lab."  ";
																	}
																}
															}
															else
															{
																echo $result[$i]["na"]."=" . $result[$i]["na_value"].", ";
															}
															// echo $result[$i]["ta"]."=" . dateDBtoDisplay($result[$i]["date_selecteur"], "-", true);
															// 17:38 07/07/2009 GHX
															// Correction du BZ 9811 [REC][T&A Cb 5.0][COMMENT]: impossible de saisir un commentaire
															echo $result[$i]["ta"]."=" . Date::getSelecteurDateFormatFromDate($result[$i]["ta"],$result[$i]["date_selecteur"]);
															echo " ]";
														}
													?>
												</td>
												<? if($result[$i]["trouble_ticket_number"] != "Trouble ticket" && $result[$i]["trouble_ticket_number"] != ""){ 
												?>
													<td class="texteGrisPetit" align="right">
														<? echo " Trouble ticket reference number : ".$result[$i]["trouble_ticket_number"]; 
														?>
													</td>
												<? } else { ?>	<td>&nbsp;</td>	<? } ?>
											</tr>
										</table>
									</td>
								</tr>
								<!-- Libellé du commentaire et de l'action -->
								<tr><td class="texteGrisPetit"><u>Comment :</u></td></tr>
								<tr>
									<td style="text-align:justify" class="texteGrisPetit">
									<? if($allow_modify ){ ?>
									<? // 01/09/2014 FGD - Bug 43450 - [REC][CB 5.3.3.01][TC#TA-56752][GUI][FF 31 compatibility][Graph Comment] Add comment form is displayed not well ?>
										<textarea id="libelle_comment" name="libelle_comment" class="zoneTexteStyleXP" rows="4" cols="105" style="border : 1px solid #FFA243;width:650px;"><?=$result[$i]["libelle_comment"]?></textarea>
									<? } else {
										echo $result[$i]["libelle_comment"];
									 } ?>
									</td>
								</tr>
								<tr><td class="texteGrisPetit"><u>Action :</u></td></tr>
								<tr>
									<td style="text-align:justify" class="texteGrisPetit">
									<? if($allow_modify ){ ?>
										<textarea id="libelle_action" name="libelle_action" class="zoneTexteStyleXP" rows="3" cols="105" style="border : 1px solid #FFA243;width:650px;"><?=$result[$i]["libelle_action"]?></textarea>
									<? } else {
										echo $result[$i]["libelle_action"];
									 } ?>
									</td>
								</tr>
								<? if($allow_modify ){ ?>
								<tr>
									<td align="right">
										<input type="hidden" name="id" value="<?=$result[$i]["id_comment"]?>">
										<input type="submit" class="boutonMini" value="Save"/>
									</td>
								</tr>
								<? } ?>

								</table>
								</form>
								</td>
								</tr>
						<?
								}
							} else {
						?>
								<tr>
									<td class="texteGrisBold">No comment for this selection</td>
								</tr>
						<?  } ?>

						</table>
					</fieldset>
				</td>
			</tr>
		</table>

	</body>
	<?
        // 12/10/2010 OJT : Correction bz 18444
		if( $display_add_comment )
        {
            // Par défaut le formulaire est visible, on chage donc juste le texte
            echo "<script>document.getElementById('bouton_ajout_comment').value = 'Hide';</script>";
		}
        else
        {
            echo "<script>$( 'nouveau_commentaire' ).hide();</script>";
        }
    ?>
</html>
