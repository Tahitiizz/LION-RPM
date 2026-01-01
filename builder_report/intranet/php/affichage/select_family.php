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
		Permet à l'utilisateur de choisir une famille avant d'afficher une page.
	*/
	session_start();
	include_once("/home/roaming_114/check_session.php");
	include_once($repertoire_physique_niveau0."php/environnement_liens.php");
	include_once($repertoire_physique_niveau0."php/database_connection.php");
	include_once($repertoire_physique_niveau0."php/environnement_donnees.php");
	include_once($niveau4_vers_niveau0."intranet_top.php");
	$lien_css=$path_skin."easyopt.css";
	global $niveau0, $database_connection;

	// Récupération des données de l'URL.
	if(isset($_GET["target_page"])){
		$target_page = $_GET["target_page"];
		switch($target_page){
			case "query_builder" :	$target_page ="builder_report_index.php";
									$label = "Query builder";
									break;
		}
	} else {
		echo "Error : an argument is missing.";
		exit;
	}

?>
<html>
<head>
<title>Graph_Table Creation</title>
<script src="<?=$niveau0?>js/gestion_fenetre.js" ></script>
<script src="<?=$niveau0?>js/fenetres_volantes.js" ></script>
<script src="<?=$niveau0?>js/fonctions_dreamweaver.js"></script>
<link rel="stylesheet" href="<?=$lien_css?>" type="text/css">
<link rel="stylesheet" href="<?=$niveau0?>css/pauto.css" type="text/css"/>
</head>
<body>
	<table width="100%" border="0" height="79%" cellspacing="0" cellpadding="3" align="center">
		<tr>
			<td align="center">
				<img src="<?=$path_skin?>choose_a_family.gif"/>
			</td>
		</tr>
		<tr>
			<td align="center" valign="top" width="100%">
			<table cellpadding="10" cellspacing="0" align="center" border="0" class="tabFramePauto">
			<tr>
			<td align="center">
				<fieldset>
				<legend class="texteGrisBold">&nbsp;<img src="<?=$path_skin?>puce_fieldset.gif"/>&nbsp;Choose a family to display <?=$label?>&nbsp;</legend>
				<table cellpadding="5" cellspacing="0" align="center" border="0">
					<tr>
						<td align="center">
							<table cellpadding="5" cellspacing="0">
							<?
								// On récupère la liste des familles à afficher.
								$family_query = " select * from sys_definition_categorie where on_off=1 and visible = 1 order by rank asc ";
								$result_family = pg_query($database_connection,$family_query);
								$nombre_resultat=pg_num_rows($result_family);
								if($nombre_resultat > 0){
									for ($i = 0;$i < $nombre_resultat;$i++){
										$ligne_famille = pg_fetch_array($result_family, $i);
										?>
										<tr>
											<td class="texteGris" style="font-style:underline;">
											<li>
												<a href="<?=$target_page?>?family=<?=$ligne_famille["family"]?>" class="texteGris">
													<?=$ligne_famille["family_label"]?>
												</a>
											</li>
											</td>
										</tr>
										<?
									}
								} else {
									echo "<span class=texteRouge>Error, no family in the database.</span>";
									exit;
								}
							?>
							</table>
						</td>
					</tr>
				</table>
				</fieldset>
				</td>
				</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>
