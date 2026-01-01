<?php
/*
	07/05/2009 GHX : Prise en compte de l'update la période pour la navigation OT -> OT
	06/07/2009 GHX : Ajout de l'id_menu_encours pour la navigation OT day -> OT hour (sinon plantlage avec le menu contextuel)
	06/08/2009 GHX : Suppression du tooltip sur Overime Select Hour sinon erreur JS
	17/09/2009 GHX : BZ 11465 [REC][T&A GSM 5.0][Dashboard] : pour SMSCenter, suppression des '+' lors de la visualisation d'un élément en particulier
			-> Ajout de l'urlencode
	09/12/2009 GHX : BZ 12638 [REC][T&A Cigale GSM][NAVIGATION]: pas de navigation possible MSC vers LAC
	19/02/2010 NSE bz 14414 : remplacement de GetLastDayFromAcurioWeek() par Date::getLastDayFromWeek()
	23/02/2010 NSE : remplacement des fonctions getLastDayOfMonth et getWeekFromDay par leur équivalent de la classe Date
	11/06/2010 FJT bz 15983 :- popup navigation par clic sur GTM TO/ONE
        22/07/2010 MPR bz16945 : Suppression d'un print_r
 *      07/07/2011 NSE bz 22888 : on passe la liste des produits du Dash à getNaPaths() pour ne récupérer que les arcs existants sur le produit
 *      07/07/2011 NSE bz 22945 : report de la correction effectuée en 5.1 par SCT :
 *                                23/12/2010 SCT BZ 16993 : Empty link label when you navigate in Over Network Element (dans la cas d'un multiproduit)
 */
?>
<?php

	session_start();
	include_once dirname(__FILE__)."/../php/environnement_liens.php";

	$debug = false;

	// Connexion à la base de données
	// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$database = Database::getConnection();

if ( $_GET['mode'] == 'overtime' )
{
	// Définition des éléments transmis par GET à conserver

	$elts_to_keep = "";

	// Récupération des valeurs à transmettre sans modification

	$elts_by_get = array('na_axe1', 'ne_axe1', 'na_axeN', 'ne_axeN', 'top', 'sort_by', 'filter', 'id_menu_encours');

	for ($i=0; $i < count($elts_by_get); $i++) 
	{
		// 10:13 17/09/2009 GHX
		// Correction du BZ 11465
		// Ajout de l'urlencode sur le NE
		if (isset($_GET[$elts_by_get[$i]])) $elts_to_keep .= "&".$elts_by_get[$i]."=".urlencode($_GET[$elts_by_get[$i]]);
	}

	// 13:08 06/07/2009 GHX
	// Ajout de l'id_menu_encours dans l'url OT day -> hour
	$id_menu_encours = isset($_GET['id_menu_encours']) ? $_GET['id_menu_encours'] : 0;
	
	// Définition de la ta source

	$ta_source = getTaSourceDefault($_GET['ta']);

	// Définition de la valeur de la ta (au format day)

	// Valeur de la ta par défaut

	$ta_value = $_GET["ta_value"];

	// Valeur de la ta d'origine : week

	if (!(strpos($_GET['ta'], "week") === false)) {
		// 19/02/2010 NSE bz 14414 : remplacement de GetLastDayFromAcurioWeek() par Date::getLastDayFromWeek()
		$ta_value = Date::getLastDayFromWeek(substr($ta_value, 0, 6),get_sys_global_parameters('week_starts_on_monday',1));
	}

	// Valeur de la ta d'origine : month

	if (!(strpos($_GET['ta'], "month") === false)) {

		$year	= substr($ta_value, 0, 4);
		$month	= substr($ta_value, 4, 2);
		
		// 23/02/2010 NSE : remplacement getLastDayFromMonth($month, $year) par Date::getLastDayOfMonth($month)
		$ta_value = Date::getLastDayOfMonth($ta_value);
	}

		
	// Valeur de la ta source : week

	if (!(strpos($ta_source['name'], "week") === false)) {
		// 23/02/2010 NSE : remplacement GetweekFromAcurioDay($day) par getWeekFromDay($day,$firstDayOfWeek=1)
		$ta_value = Date::getWeekFromDay($ta_value,get_sys_global_parameters('week_starts_on_monday',1));
	}

	// Valeur de la ta source : month

	if (!(strpos($ta_source['name'], "month") === false)) {
                // 12/12/2012 BBX
                // BZ 30489 : utilisation de la classe Date
                $ta_value = Date::getMonth($ta_value);
	}
	
	// 16:51 07/05/2009 GHX
	// Définition de la période
	
	// Month -> Day = 30
	if (!(strpos($_GET['ta'], "month") === false) && !(strpos($ta_source['name'], "day") === false))
	{
		$period = 30;
	}
	// Week -> Day = 7
	if (!(strpos($_GET['ta'], "week") === false) && !(strpos($ta_source['name'], "day") === false))
	{
		$period = 7;
	}
	// Day -> Hour = 48
	if (!(strpos($_GET['ta'], "day") === false) && !(strpos($ta_source['name'], "hour") === false))
	{
		$period = 48;
	}
}
else
{
	require_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php");
	require_once(MOD_SELECTEUR."php/selecteur.class.php");
	require_once(MOD_SELECTEUR."php/SelecteurDashboard.class.php");
	
	$elts_to_keep = "";

	// Récupération des valeurs à transmettre sans modification

	$elts_by_get = array('id_dash', 'ta', 'ta_value', 'mode', 'ne_axe1', 'na_axeN', 'ne_axeN', 'top', 'sort_by', 'filter', 'id_menu_encours');
	
	for ($i=0; $i < count($elts_by_get); $i++) 
	{
		// 10:13 17/09/2009 GHX
		// Correction du BZ 11465
		// Ajout de l'urlencode sur le NE
		if (isset($_GET[$elts_by_get[$i]])) $elts_to_keep .= "&".$elts_by_get[$i]."=".urlencode($_GET[$elts_by_get[$i]]);
	}
	
	
	// On boucle sur l'ensemble des GTMS du dashboard
	$selecteur = new SelecteurDashboard($_GET['id_dash']);
        // 07/07/2011 NSE bz 22888 : on récupère la liste des produits
        $productTable = dashboardModel::getDashboardProducts($_GET['id_dash']);
        // on passe la liste des produits en paramètre
	$naAxe1Paths = $selecteur->getNaPaths(1,$productTable);
	$naAxe1Paths = $naAxe1Paths[$_GET['na_axe1']];
    /**
     * 27/12/2010 11:15 SCT : Empty link label when you navigate in Over Network Element (dans la cas d'un multiproduit) BZ 16993
     */
    // recherche des différents GTM contenu dans le dashboard
    $dashboard = new DashboardModel($_GET['id_dash']);
    $listGtmFromDashboard = $dashboard->getGtms();
    $gtmFamilyInfo = array();
    foreach($listGtmFromDashboard AS $idDashGtm => $gtmName)
    {
        $leGtm = new GTMModel($idDashGtm);
        $gtmFamilyInfo = $leGtm->getGTMProductsAndFamilies();
    }
    /**
     * FIN 27/12/2010 11:15 SCT (BZ 16993)
     */

}
?>
<html>
<style>
	#global{
		margin-left: auto;
		margin-right: auto;
		width: 370px;
		border:0px solid;
		padding: 5px;
	}
	body {
		margin: 0;
		text-align: center;
    }

	.div_link{
		text-decoration:underline; cursor:hand; padding:3px;
	}
	.div_link_2{
		text-decoration:none;  padding:3px;
	}
	.div_link:hover{ font-weight:bold; }
</style>
<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css">
<script language="JavaScript1.2" src="<?=$niveau0?>js/prototype/prototype.js"></script>
<script language="JavaScript1.2" src="<?=$niveau0?>js/fenetres_volantes.js"></script>
<body>
	<div id="global">
		<div>
			<img src="<?=$niveau0?>images/titres/path_selection.gif"/>
		</div>
		<script>
			function redir_hour(obj,url){
				if(obj.options[obj.selectedIndex].value != '')
				{
					lien = url+'&ta_value='+obj.options[obj.selectedIndex].value;
					window.opener.location = lien;
					self.close();
				}
			}

			function chg(obj){
				if(obj.style.fontWeight=='bold')
					obj.style.fontWeight='normal';
				else
					obj.style.fontWeight='bold';
			}
		</script>
		<div class="tabPrincipal" style="margin-top:15px; padding:4px;">
			<fieldset class="texteGris" style="text-align:left;">
				<legend class="texteGrisBold">&nbsp;Select your path&nbsp;</legend>
				<?php
				if ( $_GET['mode'] == 'overtime' )
				{
					// On vérifie que le mode "hour" est bien activé

					$sql =	 " SELECT COUNT(agregation) AS is_active FROM sys_definition_time_agregation"
							." WHERE agregation='hour' AND on_off=1 AND visible=1";

					$row = $database->getRow($sql);

					($row['is_active'] == 0) ? $is_active = false : $is_active = true;

					if($ta_source['name'] == "hour" && $is_active)
					{
						$OT_link = "index.php?id_dash=".$_GET["id_dash"]."&mode=overtime&ta=".$ta_source['name']."&period=".$period."&id_menu_encours=".$id_menu_encours;

						$select_hour = "<select onchange=\"redir_hour(this,'$OT_link')\">";
						
						$select_hour .= "<option selected value=''>Select an hour</option>";

						for($i=0; $i < 24; $i++)
						{
							$hour = ($i<10)? "0".$i : $i;
							$select_hour .= "<option value=".$ta_value.$hour.">".$hour.":00</option>";
						}
						
						$select_hour .= "</select>";
					}

					$OT_link = "index.php?id_dash=".$_GET["id_dash"]."&mode=overtime&ta=".$ta_source['name']."&ta_value=".$ta_value."&period=".$period.$elts_to_keep;

					$ONE_link = "index.php?id_dash=".$_GET["id_dash"]."&mode=overnetwork&ta=".$_GET["ta"]."&ta_value=".$_GET["ta_value"]."".$elts_to_keep;

					if($ta_source['name'] == "hour")
					{
						if ($is_active) {
						?>
							<div class="div_link_2">
								<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>
								<span
								>Overtime (<?=$ta_source['label']?> - <?=getTaValueToDisplay("day", $ta_value, "/")?>)
								</span>
								<?=$select_hour?>
							</div>
						<?php
						}
					}
					else
					{
						?>
						<div class="div_link"
							onclick="window.opener.location='<?=$OT_link?>'; self.close();" onMouseOver="chg(this)" onMouseOut='chg(this)'>
							<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>&nbsp;Overtime (<?=$ta_source['label']?> - <?=getTaValueToDisplay($ta_source['name'], $ta_value, "/")?>).
						</div>
						<?php
					}
					?>
					<div class="div_link"
						onclick="window.opener.location='<?=$ONE_link?>'; self.close();" onMouseOver="chg(this)" onMouseOut='chg(this)'>
						<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>&nbsp;Over network element (<?=getTaLabel($_GET["ta"])?> - <?=getTaValueToDisplay($_GET["ta"], $_GET["ta_value"], "/")?>).
					</div>
					<?php
				}
				else
				{
					// 11/06/2010 FJT bz15983 popup navigation par clic sur GTM TO/ONE
					// 22/07/2010 MPR bz16945 Suppression du print_r
                    // print_r($naAxe1Paths);
                    /**
                     * 27/12/2010 11:15 SCT : Empty link label when you navigate in Over Network Element (dans la cas d'un multiproduit) BZ 16993
                     */
                    // $naLabel = getNaLabelList();
                    $naLabelTemp = array();
                    $naLabel     = array();
                    foreach($gtmFamilyInfo AS $gtmProduit => $ssTabGtm)
                    {
                        // on concatène les tableaux
                        $naLabelTemp = array_merge($naLabelTemp, taCommonFunctions::getNaLabelList('na', $ssTabGtm['family'], $gtmProduit));
                    }
                    // transformation du tableau "$naLabel[FAMILY][NA] = NA_LABEL" vers "$naLabel[NA][FAMILY] = NA_LABEL" => problème dans la méthode getNaLabelList()
                    foreach($naLabelTemp AS $naLabelTempNa => $naLabelTempSsTableau)
                    {
                        foreach($naLabelTempSsTableau AS $naLabelTempSsTableauFamille => $naLabelTempSsTableauLabel)
                        {
                            $naLabel[$naLabelTempSsTableauFamille][$naLabelTempNa] = $naLabelTempSsTableauLabel;
                        }
                    }
                    /**
                     * FIN 27/12/2010 11:15 SCT : BZ 16993
                     */
					foreach ( $naAxe1Paths as $naPath )
					{
						$ONE_link = "index.php?na_axe1=".$naPath.$elts_to_keep
						?>
						<div class="div_link"
							onclick="window.opener.location='<?=$ONE_link?>'; self.close();" onMouseOver="chg(this)" onMouseOut='chg(this)'>
							<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>&nbsp;<?php echo current(array_values($naLabel[$_GET['na_axe1']]))." to ".current(array_values($naLabel[$naPath])); ?>
						</div>
						<?php
					}
				}
				?>
			</fieldset>
		</div>
	</div>
	</body>
</html>
