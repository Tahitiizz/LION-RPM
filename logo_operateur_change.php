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
	// Permet de changer l'image du logo operateur.
	session_start();
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");
	global $niveau0;
	$_SESSION["url_reload_2"] = $_GET["url"];
?>
<html>
	<head>
		<title>Logo update</title>
	</head>
	<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" />
	<script type='text/javascript' src='<?=$niveau0?>js/gestion_fenetre.js'></script>
	<script>
		function refreshImage (objet, zoneAffichage){
			document.getElementById(zoneAffichage).src = objet.value;
			//alert(document.getElementById(zoneAffichage).width+" "+document.getElementById(zoneAffichage).height);
			if(document.getElementById(zoneAffichage).width > 130) document.getElementById(zoneAffichage).width = 130;
			if(document.getElementById(zoneAffichage).height > 55) document.getElementById(zoneAffichage).height = 55;
		}
	</script>
	<body>
	<form name="updateLogo" method="post" enctype="multipart/form-data" action="<?$niveau0?>php/upload_image.php">
		<table cellpadding="5" cellspacing="3" width="100%" class="tabPrincipal">
			<tr>
				<td align="center" valign="middle">
					<img src="<?=$niveau0?>images/titres/upload_picture_titre.gif">
				</td>
			</tr>
			<tr>
				<td valign="top">
					<fieldset>
						<legend class="texteGrisBold">&nbsp;Select the picture&nbsp;</legend>
						<table cellpadding="2" cellspacing="1" width="100%">
							<? if(isset($_SESSION["msg_erreur"])){ if($_SESSION["msg_erreur"]!=""){  ?>
							<tr><td class="texteRouge"><?=$_SESSION["msg_erreur"]?></td></tr>
							<? } } ?>
							<tr>
								<td class="texteGris">Current picture</td>
								<!-- <td class="texteGris">New picture preview</td> -->
							</tr>
							<tr>
								<td><img src="<?=$niveau0?>images/bandeau/logo_operateur.jpg"></td>
								<!-- <td><img src="<?=$niveau0?>images/bandeau/no_picture.gif" id="apercuImage"></td> -->
							</tr>
							<tr>
								<td>
									<input type="file" name=file[0] enctype="multipart/form-data"
											value="Browse" class="zoneTexte"
											onChange="javascript:refreshImage(this,'apercuImage');"/>
								</td>
							</tr>
							<tr>
								<td align="right">
									<input type="submit" value="Save" class="bouton">
								</td>
							</tr>
						</table>
					</fieldset>
				</td>
			</tr>
			<tr>
				<td valign="top">
					<fieldset>
						<legend class="texteGrisBold">&nbsp;<img src="<?=$niveau0?>images/icones/icone_astuce.gif">&nbsp;Update / Donwload Logo&nbsp;</legend>
						<table cellpadding="2" cellspacing="1" width="100%" class="texteGris">
							<tr>
								<td>
									<li>Click on the button 'Browse' to select a new picture logo.</li>
									<li><u>Picture extension :</u>.jpeg .</li>
									<li><u>Picture size :</u> max <b>width 130 pixels</b>, max <b>height 55 pixels</b>.</li>
									<li>Click on the button 'Save' to download and update the logo.</li>
								</td>
							</tr>
						</table>
					</fieldset>
				</td>
			</tr>
		</table>
		</form>
	</body>
	<? $_SESSION["msg_erreur"] = "";  unset($_SESSION["msg_erreur"]);?>
</html>
