<?
/*
 *
 * 11/04/2011 MMT bz 18176 reopened: mauvais format de config de $_SESSION["TA"]["selecteur"]["ne_axe1"] pour pour le dashboard
 *
 * 06/06/2011 MMT DE 3rd Axis change le format de selection NE 3eme axe -> meme que le 1er
 *
 * 	@cb41000@
*
*	03/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4100
*
*	- maj 03/12/2008 - SLC - gestion multi-product
*
*	 20/08/2009 BBX : correction de la récupération des dashboards. BZ 11118
*	 20/08/2009 BBX : ajout de l'id_menu dans les valeurs à récupérer. BZ 11119
*
*	27/08/2009 GHX
*		- Correction du BZ 11252 [CB 5.0][ALARM MANAGEMENT] impossible d'avoir les dashboards associés pour les alarmes dynamqiues
*		- Correction d'un bug car on n'a pas toujours tous les dashboards associées à l'alarme
*
*	18/09/2009 BBX : le type doit être testé car si le type est "raw" il faut mettre "counter" dans le requête. BZ 11629
*/
?>
<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
	- maj 21/03/2008, benoit : correction du bug 4864
	- maj 18/04/2008, benoit : correction du bug 6342
	- maj 23/05/2008, benoit : recorrection du bug 6342
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14

	- mag 17:26 04/09/2007 Gwen : modification de la requete qui récupère le nom des dashboards (pbm du au changement de version de postgres)
	- maj 10/08/2007, benoit : lors de la definition du lien ('$link'), modification du masque de recherche de la    fonction 'str_replace()' en changeant la valeur "defaut" de "selecteur_scenario" par "normal" (le scenario     "defaut" n'existe plus)

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
*	- 20/04/2007 christophe : gestion du nouveau 3ème axe
*
*/
?>
<?
/*
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*/
?>
<?
/*
*	@cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
*/
?>
<?
/*
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
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

--------------------------------------------------------------------------------

Cette page liste tous les dashboards associés à une alarme.

Reçoit :
 - $alarm_type		: type de l'alarme
 - $id_alarm        : pour rechercher les dashboards associés
 - $na				: à inclure dans le lien à faire vers les dashboards trouvés
 - $na_value        : à inclure dans le lien à faire vers les dashboards trouvés

Affiche :
La liste des dashboards associés à l'alarme donnée.
Chaque dashboard listé est clickable.

--------------------------------------------------------------------------------

   - maj 28/04/2011 MMT bz 21987 close popup window after openning new one

   - maj 20/04/2011 MMT 21125 display Note message about list filtered by user's priviledges

   - maj 20/01/2011 MMT DE Xpert 606: refonte de l'api pour ajouter possibilité recherche de dashboard par KPI et counter
                   Deplacement de la logique dans la classe QueryDashboardKPI

	- maj 26/02/2007, Gwénaël :  - ajout d'une condition sur les graphes pour qu'ils appartiennent à la même famille que l'alarme
                                                               - ajout d'une condition sur les dashboards pour vérifier s'ils appartiennent à un menu accessible par l'utilisateur

	- maj 05/06/2006, christophe : Si il n'y a aucun enregistrement dans sys_user_parameter pour ce user et cette    famille, on insère une valeur par défaut (à partir de sys_selecteur_properties).

	- maj 09/06/2006, : on ne fait plus d'insertion de la date dans sys_user_parameter car tout plante sinon.

	- maj 28/06/2006, christophe : correction du bug avec les alarmes dynamiques.

	- maj 10/08/2006, christophe : prise en compte des modifications sur la structure des tables de définitions des   alarmes.

	- maj 24/08/2006, christophe : ligne 120 : correction du lien vers les dashboards des TWCL.

	- maj 31/08/2006, benoit : remplacement des appels aux tables 'sys_user_parameter' et                            'sys_selecteur_properties' par leur equivalent en session dans le cas ou l'on selectionne une famille de       dashboard qui ne possède pas de valeurs par défaut pour le selecteur

	- maj 20/11/2006, benoit : correction du titre de la fenêtre (apparaissait en francais)

*/
//error_reporting(E_ALL);

session_start();

//20/01/2011 MMT DE Xpert: si pas de $repertoire_physique_niveau0 accessible (pas de session) on la recré a partir
// du chemin du fichier courrant
if(empty ($repertoire_physique_niveau0)){
	$repertoire_physique_niveau0 = str_replace("reporting/intranet/php/affichage", "", dirname( __FILE__ ));
}

include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "reporting/intranet/php/class/QueryDashboardAPI.class.php");

// foreach ($_GET as $key => $val) echo "$key = $val<br/>";

if ( isset($_GET['externalLink']) )
	include_once(REP_PHYSIQUE_NIVEAU_0 . 'intranet_top.php');

//20/01/2011 MMT DE Xpert:
// utilisation de la class QueryDashboardAPI qui remplace la plupart de la logique
$queryDash = new QueryDashboardAPI();
$queryDash->checkAndAffectParams($_GET);
if(!$queryDash->hasEncounteredErrors()){
	$dashboardsToDisplay = $queryDash->getMatchingDashboardList();
	$queryDash->affectSelectorValuesToSession();
}


?>

<html>
<head>
	<title>List of dashboards</title>
	<script language="JavaScript1.2" src="<?=$niveau0?>js/gestion_fenetre.js"></script>
	<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css"/>
</head>
<body bgcolor="#fefefe">

<table align="center" width="100%">
	<tr><td align="center"><img src="<?=$niveau0?>images/titres/dashboard_switch.gif"/><br />&nbsp;</td></tr>
	<tr valign='center' align='center'>
		<td align='center' valign='center'>
			<table cellpadding="3" cellspacing="1" class="tabPrincipal">
				<tr align="center">
					<td align="center">
						<?
						  //20/01/2011 MMT DE Xpert: gestion de cas d'erreur avec QueryDashboardAPI
						  if ($queryDash->hasEncounteredErrors()){
								echo "<fieldset class='texteGrisBold'><legend style='color:red'>&nbsp;Error&nbsp;</legend>";
								foreach ($queryDash->getErrorMessages() as $msg){
									echo "<div >".$msg."</div>";
								}
								echo "</fieldset>";
							} else {
						?>
						<table width="350px" align="center"><tr>	<td>
							<fieldset>
								<legend>&nbsp;<img src="<?=$niveau0?>images/icones/icone_astuce.gif">&nbsp;</legend>
								<div class="texteGris" style='padding:3px;'>
									<?=$queryDash->getQuerySummary() ?>
								</div>
							</fieldset>
						</td></tr>
						</table>
					</td>
				</tr>
				<tr align='center'>
					<td align='center'>
						<table width="80%">
							<tr>
								<td>
									<fieldset>
										<legend class="texteGrisBold">
											&nbsp;
											<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif">
											&nbsp;
											Dashboards
											&nbsp;
										</legend>
										<table cellpadding="3" cellspacing="1" border=0 class="texteGris">
											<tr>
												<td>
													<?php
													if (count($dashboardsToDisplay) > 0) {
														foreach($dashboardsToDisplay as $dash)	{
															$url=$niveau0  . $dash->getLink() ;//. "&module_restitution=alarm";
															// 11/05/2010 BBX
															// Il faut ajouter le paramètre pour cacher le header. BZ 15450
															$url .= '&hide_header=1';
															if (isset($_GET['externalLink'])) {
																$url = str_replace('&affichage_header=0', '&affichage_header=1', $url);
																$url .= "&id_menu_encours=".$dash->getIdMenu();
																?>
											<li><a style='cursor:hand; text-decoration:underline;' href="<?=$url?>" ><?=$dash->getLabel()?></a></li>

															<?php } else {
																$url .= "&id_menu_encours=".$dash->getIdMenu();
																//28/04/2011 MMT bz 21987 close window after openning new one
															?>
																<li><a style='cursor:hand; text-decoration:underline;' onclick="javascript:ouvrir_fenetre('<?=$url?>','DataView','yes','yes',1000,700);self.close()" ><?=$dash->getLabel()?></a></li>

															<?php }
														}
													} else {
														//20/04/2011 MMT 21125
														echo __T('A_XPERT_NO_DASHBOARD_FOUND');
													} ?>
												</td>
											</tr>
										</table>
									</fieldset>
								</td>
							</tr>
							<tr>
								<td class="texteGris" style='padding:3px;'>
									<?
									//20/04/2011 MMT 21125 display Note message about list filtered by user's priviledges
									echo __T('A_XPERT_DASHBOARD_LIST_PRIVILEDGES_APPLY');?>
								</td>
							</tr>
						</table>
						<?}?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

</body>
</html>