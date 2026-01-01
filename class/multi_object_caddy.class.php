<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00

	- maj 04/01/2008, benoit : remise en forme de l'export avec ajout des boutons d'export Word et Excel
	
	- maj 08/01/2008, benoit : ajout de la variable '$this->graph_gis_export' qui indique s'il existe des graphes et / ou des rasters gis dans    le panier
	- maj 08/01/2008, benoit : on n'affiche l'export graph et gis que si il existe ces elements dans le panier
	- maj 08/01/2008, benoit : on n'affiche "object_summary" que si "object_type" est différent de "graph"
	
	- maj 09/01/2008, benoit : ajout de la variable '$this->graph_excel_export' qui indique s'il existe des graphes dans le panier pour l'export   excel

	- maj 10/01/2008, benoit : utilisation de la fonction '__T()' pour définir les tooltips des boutons d'export
	
	07/05/09 - SPS 
		- modification des liens pour l'export du caddy en word et pdf
	
	11/05/09 - SPS 
		- modification des liens pour l'export du caddy en excel
	
	29/05/2009 - SPS 
		- ajout des tests pour investigation_dashboard
	01/09/2009 GHX
		- Correction du BZ 11272 ajout du utf8_decode
	22/09/2009 GHX
		- Correction du BZ 11272 ajout du utf8_decode
		
	08/06/2010 YNE/FJT Single KPI

        03/09/2010 MPR - DE Firefox : BZ 17685 : Changement du curseur hand par pointer
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
*	@cb2001_iu2030_111006@
*
*	11/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.1
*
*	Parser version iu_2.0.3.0
*/
?>
<?
/*
*	@cb1300b_iu2000b_070706@
*
*	12/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0b
*
*	Parser version iu_2.0.0.0b
*/
?>
<?
/*
* Classe de gestion et d'affichage du caddy.
* @package Multi_Object_Caddy
* @author christophe chaput
* @version V1 2005-05-16

	- maj DELTA christophe 26 04 2006. cf MODIF DELTA NOUVEAU(ajout)   MODIF DELTA(mise en commentaires des modifications)
	- maj 30 05 2006 christophe : si l'image .png d'un graph n'existe plus, elle est supprimée de sys_panier_mgt. (voir la méthode retrieve_contenu() )
	- maj 13 07 2006 christophe : idem que maj 30 05 2006  mais avec les pdf.
	- maj 31 08 2006 christophe : gestion des alarmes dans le caddy.
	- maj 21 09 2006 christophe : gestion des raster venant du GIS dans le caddy.
*/
class Multi_Object_Caddy {

/*
* Constructeur.
* @param int id_user : identifiant de l'utilisateur.
*/
function  Multi_Object_Caddy($id_user,$tableau,$zoom_all){
	
	$this->id_user = $id_user;
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$this->database = Database::getConnection();
	$this->repertoire_png_file = NIVEAU_0."png_file/";
	$this->tableau_liste = $tableau;
	$this->zoom_all = $zoom_all;
}

/*
	Initialisation du caddy.
*/
function  caddy()
{
	// On redimensionne la page so besoin.
	if($this->zoom_all == 1){
	?>
		<script>
			LeftPosition = (screen.width) ? (screen.width-990)/2 : 0;
			TopPosition = (screen.height) ? (screen.height-700)/2 : 0;
			parent.window.moveTo(LeftPosition,TopPosition); resize(990,700);
		</script>
	<?
	} else {
	?>
		<script>
			LeftPosition = (screen.width) ? (screen.width-700)/2 : 0;
			TopPosition = (screen.height) ? (screen.height-450)/2 : 0;
			parent.window.moveTo(LeftPosition,TopPosition); resize(700,450);
		</script>
	<?
	}
   $this->retrieve_contenu();
   $this->display();
}

/*
* Permet de charger le contenu du caddy.
*/
function retrieve_contenu()
{
	/*
		Nettoyage de sys_panier_mgt, on supprime les lignes dont l'image n'existe plus.
	*/

	global $repertoire_physique_niveau0;

	// 16/02/2009 - Modif. benoit : remise en forme du code et reprise de la requete pour considérer 'id_user' comme une chaine et non comme un entier

	$sql = "SELECT oid, * FROM sys_panier_mgt WHERE id_user = '$this->id_user'";
	$row = $this->database->getAll($sql);

	$this->alarm_export = false;

	// 08/01/2008 - Modif. benoit : ajout de la variable '$this->graph_gis_export' qui indique s'il existe des graphes et / ou des rasters gis dans le panier

	$this->graph_gis_export = false;

	// 09/01/2008 - Modif. benoit : ajout de la variable '$this->graph_excel_export' qui indique s'il existe des graphes dans le panier pour l'export excel

	$this->graph_excel_export = false;

	for ($k = 0;$k < count($row);$k++)
	{
		$item = $row[$k];
		// Single KPI
		// 17/02/2009 - Modif. benoit : rajout du traitement des pies
		//29/05/2009 SPS ajout du test sur investigation dashboard
		if($item["object_type"] == "graph" || $item["object_type"] == "singleKPI" || $item["object_type"] == "pdf" || $item["object_type"] == "gis_raster" || $item["object_type"] == "investigation_dashboard" || (strpos($item["object_type"], "pie") !== false))
		{			
			$oid = 	$item["oid"];
			$temp = explode("/",$this->repertoire_png_file.$item["object_id"]);

			if(!file_exists($repertoire_physique_niveau0.$temp[2]."/".$temp[3]))
			{
				$this->database->executeQuery("DELETE FROM sys_panier_mgt WHERE oid=$oid");
			}
			
			$this->graph_gis_export = true;
		}
		// Single KPI
		/*29/05/2009 SPS ajout du type investigation_dashboard*/
		if($item["object_type"] == "graph" || $item["object_type"] == "singleKPI" || $item["object_type"] == "investigation_dashboard" ) $this->graph_excel_export = true;
		if (!(strpos($item["object_type"], "pie") === false)) $this->graph_excel_export = true;
		if($item["object_type"] == "alarm_export") $this->alarm_export = true;
	}
	
	// fin du nettoyage

	/*
		On va chercher le contenu du caddy pour l'utilisateur courant.
	*/
	
	$sql = "SELECT oid, * FROM sys_panier_mgt WHERE id_user = '$this->id_user'";
	$row = $this->database->getAll($sql);
		
	for ($k = 0;$k < count($row);$k++)
	{
		$item = $row[$k];

		$this->object_title[]		= $item["object_title"];
		$this->object_type[]		= $item["object_type"];
		$this->object_page_from[]	= $item["object_page_from"];
		// Single KPI
		if($item["object_type"] == "graph" || $item["object_type"] == "singleKPI" || $item["object_type"] == "gis_raster" || $item["object_type"] == "investigation_dashboard" || (!(strpos($item["object_type"], "pie") === false)))
		{
			$this->object_id[]		= $this->repertoire_png_file.$item["object_id"];
		} 
		else
		{
			$this->object_id[]		= $item["object_id"];
		}
		
		$this->object_id_png[]		= $item["object_id"];
		$this->object_summary[]		= $item["object_summary"];
		$this->oid[]				= $item["oid"];
    }
}

/*
	Permet d'afficher la page du contenu du caddy.
*/
function display()
{?>
	<table width="100%" align="center">
		<tr><td><?$this->display_title();?></td></tr>
		<tr><td><?$this->display_header();?></td></tr>
		<tr><td><?$this->display_body();?></td></tr>

		<!--<tr><td><?$this->display_file_attachment();?></td></tr>-->
	</table>
<?
}

/**
	fonction qui permet l'affichage de l'image d'en-tête lorsque l'utilisateur
	visualise le contenu de son caddy.
*/
function  display_title()
{
    global $niveau0;
?>
	<table width="100%" align="center">
		<tr>
			<td align="center">
				<img src="<?=$niveau0?>images/titres/caddy_header_title.gif" border="0" alt=""/>
			</td>
		</tr>
	</table>
<?
}

/*
* Permet d'afficher l'image d'en-tête du caddy, les options et
* le nombre d'éléments du caddy.
*/
function display_header()
{
	global $niveau0;
?>
	<table align="center">
		<tr>
			<td>
			<? $nb_element = $this->display_caddy_number();?>
			</td>
			<? if($nb_element != 0){ ?>
			<td>
				<fieldset>
				<legend class="caddyTxt">&nbsp;Options&nbsp;</legend>
					<table>
						<tr>
							<td>
								<? $this->button_reset(); ?>
							</td>
							<td>
								<? $this->button_zoom_all(); ?>
							</td>
							<td>
								<? $this->button_pdf_export(); ?>
							</td>
						</tr>
					</table>
				</fieldset>
			</td>
			<? } ?>
		</tr>
	</table>
<?
}

/*
* Affiche le bouton qui permet de vider un caddy.
*/
function  button_reset()
{
	global $niveau0;
?>
	<table>
		<tr>
			<td>
                            <!-- maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer -->
				<img src="<?=$niveau0?>images/icones/dustbin.gif" align="middle" border="0"
				onMouseOver="popalt('Reset all items');style.cursor='pointer';" onMouseOut="kill()"
				onClick="caddy_reset('<?=$this->id_user?>')">
			</td>
		</tr>
	</table>
<?
}

/*
* Affiche le bouton qui d'afficher toutes les images des graph.
*/
function  button_zoom_all()
{
	global $niveau0;
	$img_zoom = ($this->zoom_all == 1)? "zoom_all_moins.gif" : "zoom_all.gif";
?>
	<table>
		<tr>
			<td>
                                <!-- maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer -->
				<img src="<?=$niveau0."images/icones/".$img_zoom?>" align="middle" border="0"
				onMouseOver="popalt('View all graph in original size');style.cursor='pointer';" onMouseOut="kill()"
				onClick="caddy_zoom_all('<?=$this->id_user?>', <?=$this->zoom_all?>)">
			</td>
		</tr>
	</table>
<?
}

/*
* Affiche le bouton d'export vers un fichier PDF.
*/
function  button_pdf_export()
{
	global $niveau0;
	$page= "caddy";
	$type_PDF="caddy";

	// 04/01/2008 - Modif. benoit : remise en forme de l'export avec ajout des boutons d'export Word et Excel
?>
	<style type="text/css">
		.export_title
		{
			font: normal 8pt Trebuchet MS,Verdana, Arial, sans-serif; 
			color: #585858;
			vertical-align:center;
		}
		
		.export_buttons
		{
			vertical-align:middle;
			cursor: pointer;
			border:0px none;
		}
	</style>
<?php
	// 08/01/2008 - Modif. benoit : on n'affiche l'export graph et gis que si il existe ces elements dans le panier

	// 10/01/2008 - Modif. benoit : utilisation de la fonction '__T()' pour définir les tooltips des boutons d'export

	$tooltip_word_export = (strpos(__T('U_TOOLTIP_CADDY_WORD_EXPORT'), "Undefined") === false) ? __T('U_TOOLTIP_CADDY_WORD_EXPORT') : "Word Export";
	$tooltip_excel_export = (strpos(__T('U_TOOLTIP_CADDY_EXCEL_EXPORT'), "Undefined") === false) ? __T('U_TOOLTIP_CADDY_EXCEL_EXPORT') : "Excel Export";
	$tooltip_excel_no_export = (strpos(__T('U_TOOLTIP_CADDY_EXCEL_NO_EXPORT'), "Undefined") === false) ? __T('U_TOOLTIP_CADDY_EXCEL_NO_EXPORT') : "Not available";
	$tooltip_pdf_export = (strpos(__T('U_TOOLTIP_CADDY_PDF_EXPORT'), "Undefined") === false) ? __T('U_TOOLTIP_CADDY_PDF_EXPORT') : "Pdf Export";

	if ($this->graph_gis_export){

?>
<?php 
/**
 * 07/05/09 - SPS modification des liens pour l'export du caddy en word / pdf
 * 11/05/09 - SPS modification des liens pour l'export du caddy en excel
 **/
?>
	<div style="padding-left:5px">
		<span class="export_title">Export graph and GIS raster.</span>
		<?php /*<a href="multi_object_word_export.php?type_word=caddy&save_in_file=true" style="text-decoration:none" onclick="window.resize(400,140);">*/ ?>
		<a href="export_word_pdf.php?type=word" style="text-decoration:none" onclick="window.resize(400,180);">
			<img src="<?=$niveau0?>images/icones/page_white_word.png" class="export_buttons" onmouseover="popalt('<?=$tooltip_word_export?>');"  onmouseout="kill()">
		</a>
		<?php
			if ($this->graph_excel_export) {
		?>
			<?php /*<a href="multi_object_excel_export.php?save_in_file=true" style="text-decoration:none" onclick="window.resize(400,180);">*/ ?>
			<a href="export_excel.php" style="text-decoration:none" onclick="window.resize(400,180);">	
				<img src="<?=$niveau0?>images/icones/page_white_excel.png" class="export_buttons" onmouseover="popalt('<?=$tooltip_excel_export?>');" onmouseout="kill()">
			</a>
		<?php
			}
			else 
			{
		?>
			<a style="text-decoration:none">
				<img src="<?=$niveau0?>images/icones/page_white_excel_no.png" class="export_buttons" onmouseover="popalt('<?=$tooltip_excel_no_export?>');" onmouseout="kill()">
			</a>
		<?php	
			}
		?>
		<?php /*<a href="multi_object_pdf_export.php?page='Trending & Aggregation Graph Selection'&save_in_file=true&object_type=graph&type_PDF='<?=$type_PDF?>'" onclick="window.resize(400,140);" style="text-decoration:none">*/ ?>
		<a href="export_word_pdf.php?type=pdf" onclick="window.resize(400,180);" style="text-decoration:none">
			<img src="<?=$niveau0?>images/icones/page_white_acrobat.png" class="export_buttons" onmouseover="popalt('<?=$tooltip_pdf_export?>');" onmouseout="kill()">
		</a>
	</div>
<?php
	}

	if($this->alarm_export){
?>
		<div style="padding-left:5px;padding-top:3px">
			<span class="export_title">Export alarm.</span>
			<a href="alarm_word_export.php" style="text-decoration:none">
				<img src="<?=$niveau0?>images/icones/page_white_word.png" class="export_buttons" onmouseover="popalt('<?=$tooltip_word_export?>');" onmouseout="kill()">
			</a>
			<a href="alarm_excel_export.php" style="text-decoration:none">
				<img src="<?=$niveau0?>images/icones/page_white_excel.png" class="export_buttons" onmouseover="popalt('<?=$tooltip_excel_export?>');" onmouseout="kill()">
			</a>
			<a href="alarm_pdf_export.php" style="text-decoration:none">
				<img src="<?=$niveau0?>images/icones/page_white_acrobat.png" class="export_buttons" onmouseover="popalt('<?=$tooltip_pdf_export?>');" onmouseout="kill()">
			</a>
		</div>
<?php
	}
}

/**
* fonction qui permet d'afficher le nombre d'éléments contenus dans le caddy.
*/
function display_caddy_number()
{
	$nb_element=count($this->object_id);
	global $niveau0;
?>
	<table>
		<tr>
			<td><img src="<?=$niveau0?>images/icones/caddy_icon_reflection.gif"/></td>
			<td class="caddyTxt">
				<? if ($nb_element == 0) { ?>
				Your cart is empty
				<? } else {   ?> You have <? echo($nb_element); ?> element(s) in your cart. <? } ?>
			</td>
		</tr>
	</table>
<?  return($nb_element);
}

/*
	Permet d'afficher le contenu du caddy.
	L'affichage est différent selon le type de données contenues dans le caddy.
	type graph = graphique .png
	type table : tableau HTML.
*/
function display_body()
{
	global $niveau0, $repertoire_physique_niveau0;
?>
<table cellpadding="2" cellspacing="2" align="center" width="620px">
<tr>
<td>
	<fieldset>
	<legend>
			<font class="caddyTxtGras10">&nbsp;<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>&nbsp; Graph & Alarm &nbsp;</font>
	</legend>
	<table cellpadding="2" cellspacing="4" align="center">
	<?
        global $path_skin, $repertoire_physique_niveau0; // MODIF DELTA AJOUT
		$zero_element = true;

        for ($i=0;$i<count($this->object_id);$i++){
			// Single KPI
			// 17/02/2009 - Modif. benoit : rajout du traitement des pies
			if(($this->object_type[$i] == "graph") || $this->object_type[$i] == "singleKPI" || ($this->object_type[$i] == "gis_raster") || ($this->object_type[$i] == "investigation_dashboard") || (strpos($this->object_type[$i], "pie") !== false))
			{
				$zero_element = false;
				if($this->zoom_all == 1){
				
				?>
				<tr>
					<td width="100%">
						<table class="borderGray" align="center" width="600px">
							<tr>
								<td width="50%" align="left">
									<table cellpadding="0" cellspacing="2">
										<tr>
											<td class="caddyTxt"><?=utf8_decode($this->object_title[$i])?></td>
											<td class="caddyTxtNormal" align="left"><?=$this->object_type[$i]?></td>
											<td class="caddyTxtNormal" align="left">
											<?php
												
												// 08/01/2008 - Modif. benoit : on n'affiche "object_summary" que si "object_type" est différent de "graph"
												// Single KPI
												if(($this->object_type[$i] != "graph") && (strpos($this->object_type[$i], "pie") && strpos($this->object_type[$i],"singleKPI") && ($this->object_type[$i] == "investigation_dashboard") === false))
												{
													echo $this->object_summary[$i];
												}		
				
											?>
											</td>
											<td>
												<a href="multi_object_caddy_management.php?id_user=<?=$this->id_user?>&oid=<?=$this->oid[$i]?>&action=supprimer">
                                                                                                    <!-- maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer -->
                                                                                                    <img src="<?=$niveau0?>images/icones/delete_caddy_element.gif" border="0"  onMouseOver="popalt('Remove');style.cursor='pointer';"
													onMouseOut="kill()">
												</a>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td>
									<img src="<?=$this->object_id[$i]?>"/>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<?
				} else {
					$zero_element = false;
				?>
				<tr>
					<td width="100%">
						<table class="borderGray" align="center" width="600px">
							<tr>
							<td width="50%">
								<table cellpadding="0" cellspacing="2">
									<tr><td class="caddyTxt"><?=utf8_decode($this->object_title[$i])?></td></tr>
									<tr><td class="caddyTxtNormal" align="left"><?=$this->object_type[$i]?></td></tr>
									<tr>
										<td class="caddyTxtNormal" align="left">
										<?php
											
											// 08/01/2008 - Modif. benoit : on n'affiche "object_summary" que si "object_type" est différent de "graph"

											// 17/02/2009 - Modif. benoit : rajout du traitement des pies
											// Single Kpi
											if($this->object_type[$i] != "graph" && (strpos($this->object_type[$i], "pie") && strpos($this->object_type[$i],"singleKPI") && ($this->object_type[$i] == "investigation_dashboard") === false))
											{
												echo $this->object_summary[$i];
											}		
			
										?>
										</td>									
									</tr>
								</table>
							</td>
							<td>
								<img src="<?=$this->object_id[$i]?>" width="125px"/>
							</td>
							<td align="right">
								<table cellpadding="0" cellspacing="2">
									<tr>
									<td>
									<?
										global $repertoire_physique_niveau0;
										$temp = explode("/",$this->object_id[$i]);
										$size = getimagesize($repertoire_physique_niveau0.$temp[2]."/".$temp[3]);
										$width = $size[0];
										$height = $size[1];
									?>
                                                                        <!-- maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer -->
									<img src="<?=$niveau0?>images/icones/zoom_glass.gif" border="0"  onMouseOver="popalt('Zoom');style.cursor='pointer';" onMouseOut="kill()" onClick="increase_caddy_picture('<?=$this->object_id[$i]?>','graph',<?=$width+50?>,<?=$height+50?>)"></td>
									<td>
										<a href="multi_object_caddy_management.php?id_user=<?=$this->id_user?>&oid=<?=$this->oid[$i]?>&action=supprimer">
                                                                                    <!-- maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer -->
											<img src="<?=$niveau0?>images/icones/delete_caddy_element.gif" border="0"  onMouseOver="popalt('Remove');style.cursor='pointer';"
											onMouseOut="kill()">
										</a>
									</td>
									</tr>
								</table>
							</td>
							</tr>
						</table>
					</td>
				</tr>
		<?
				}
		} else if($this->object_type[$i]=="table") {
			$zero_element = false;
	   ?>
		<tr>
		<td width="100%">
			<table class="borderGray" align="center" width="600px">
				<tr>
					<td width="50%">
						<table cellpadding="0" cellspacing="2">
							<tr><td class="caddyTxt"><?=utf8_decode($this->object_title[$i])?></td></tr>
							<tr><td class="caddyTxtNormal" align="left"><?=$this->object_type[$i]?></td></tr>
						</table>
					</td>
					<td>
						<img src="<?=$niveau0?>images/divers/template_tableau.gif"/>
						<?
							$tab = $this->tableau_liste[$this->object_id[$i]];
							//echo $tab;
						?>
					</td>
					<td align="right">
						<table cellpadding="0" cellspacing="2">
							<tr>    <!-- maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer -->
								<td><img src="<?=$niveau0?>images/icones/zoom_glass.gif" border="0"  onMouseOver="popalt('Zoom');style.cursor='pointer';" onMouseOut="kill()" onClick="increase_caddy_picture('<?=$this->object_id[$i]?>','table')"></td>
								<td>
									<a href="multi_object_caddy_management.php?id_user=<?=$this->id_user?>&oid=<?=$this->oid[$i]?>&action=supprimer">
                                                                                <!-- maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer -->
										<img src="<?=$niveau0?>images/icones/delete_caddy_element.gif" border="0"  onMouseOver="popalt('Remove');style.cursor='pointer';"
										onMouseOut="kill()">
									</a>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
		</tr>
		<?
		} else if($this->object_type[$i]=="alarm_export") {
			$zero_element = false;
		?>
		<tr>
		<td width="100%">
			<table class="borderGray" align="center" width="100%">
				<tr>
					<td width="50%" valign="top">
						<table cellpadding="0" cellspacing="2">
							<tr><td class="caddyTxt"><?=$this->object_title[$i]?></td></tr>
							<tr><td class="caddyTxtNormal" align="left"><?=$this->object_type[$i]?></td></tr>
						</table>
					</td>
					<td>
						<?
						if($this->zoom_all){
							?>
							<style>
							#alarm{
								background-color : #fff;
								padding:5px;
								border : 2px #929292 solid;
							}
							#alarm tr{
								color: #000;
								font : 	normal 9pt Verdana, Arial, sans-serif;
								text-align: left;
							}
							.entete{
								color: #fff;
								background-color : #929292;
								font : 	normal 9pt Verdana, Arial, sans-serif;
								text-align: left;
							}
							</style>
							<div id="alarm">
							<?
								echo $this->object_id[$i];
							?>
							</div>
							<?
						} else {
						?>
						<img src="<?=$niveau0?>images/divers/template_tableau.gif"/>
						<?
						}
						?>
					</td>
					<td align="right" valign="top">
						<table cellpadding="0" cellspacing="2">
							<tr>
								<?
								if(!$this->zoom_all){
								?>
									<td>
                                                                                <!-- maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer -->
										<img src="<?=$niveau0?>images/icones/zoom_glass.gif" border="0"
											onMouseOver="popalt('Zoom');style.cursor='pointer';" onMouseOut="kill()"
											onClick="increase_caddy_picture('<?=$this->oid[$i]?>','builder_report')">
									</td>
								<?
								}
								?>
								<td>
									<a href="multi_object_caddy_management.php?id_user=<?=$this->id_user?>&oid=<?=$this->oid[$i]?>&action=supprimer">
                                                                            <!-- maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer -->
										<img src="<?=$niveau0?>images/icones/delete_caddy_element.gif" border="0"  onMouseOver="popalt('Remove');style.cursor='pointer';"
										onMouseOut="kill()">
									</a>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
		</tr>
		<?
		} else if($this->object_type[$i]=="builder_report") {
			$zero_element = false;
		?>
		<tr>
		<td width="100%">
			<table class="borderGray" align="center" width="600px">
				<tr>
					<td width="50%">
						<table cellpadding="0" cellspacing="2">
							<tr><td class="caddyTxt"><?=utf8_decode($this->object_title[$i])?></td></tr>
							<tr><td class="caddyTxtNormal" align="left"><?=$this->object_type[$i]?></td></tr>
						</table>
					</td>
					<td>
						<img src="<?=$niveau0?>images/divers/template_tableau.gif"/>
					</td>
					<td align="right">
						<table cellpadding="0" cellspacing="2">
							<tr>
                                                                <!-- maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer -->
								<td><img src="<?=$niveau0?>images/icones/zoom_glass.gif" border="0"  onMouseOver="popalt('Zoom');style.cursor='pointer';" onMouseOut="kill()" onClick="increase_caddy_picture('<?=$this->oid[$i]?>','builder_report')"></td>
								<td>
									<a href="multi_object_caddy_management.php?id_user=<?=$this->id_user?>&oid=<?=$this->oid[$i]?>&action=supprimer">
                                                                                <!-- maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer -->
										<img src="<?=$niveau0?>images/icones/delete_caddy_element.gif" border="0"  onMouseOver="popalt('Remove');style.cursor='pointer';"
										onMouseOut="kill()">
									</a>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
		</tr>
		<?
		}
		}
		if($zero_element){
		?>
		<tr>
			<td class="caddyTxtGras10" align="center">
				no file
			</td>
		</tr>
		<? } ?>
</table>
</fieldset>
</td>
</tr>
</table>
<?
}

/*
* Permet d'afficher les éléments, qui sont de type pdf,
* contenus dans les caddy tel que
* - les alarmes, les Top / WCL.
*/
function display_file_attachment()
{
	global $niveau0;
?>
<table cellpadding="2" cellspacing="2" align="center" width="620px">
<tr>
<td>
	<fieldset>
	<legend>
		<font class="caddyTxtGras10">&nbsp;<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>&nbsp;File attachment&nbsp;</font>
	</legend>
	<table cellpadding="2" cellspacing="4" align="center">
		<?
		$zero_element = true;
		for ($i=0;$i<count($this->object_id);$i++){
			if($this->object_type[$i]=="pdf"){
			$zero_element = false;
		?>
		<tr>
			<td width="100%">
			<table class="borderGray" align="center" width="600px">
				<tr>
				<td width="50%">
					<table cellpadding="0" cellspacing="2">
						<tr><td class="caddyTxt"><?=utf8_decode($this->object_title[$i])?></td></tr>
						<tr><td class="caddyTxtNormal" align="left">PDF document</td></tr>
						<tr><td class="caddyTxtNormal" align="left"><?=$this->object_page_from[$i]?></td></tr>
					</table>
				</td>
				<td align="center" class="caddyTxtNormal">
					<i>No preview for PDF files.</i>
				</td>
				<td align="right">
					<table cellpadding="0" cellspacing="4">
						<tr>
						<td>
							<!-- on ouvre le pdf dans une nouvelle fenêtre, le pdf doit se trouver dans le répertoire png_file -->
				
							<a href="<?=$niveau0?>png_file/<?=$this->object_id[$i]?>" target="_blank">
                                                            <!-- maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer -->
							<img src="<?=$niveau0?>images/icones/pdf_icon.gif" align="middle" border="0"  onMouseOver="popalt('View the PDF');style.cursor='pointer';" onMouseOut="kill()"/>
							</a>
						</td>
						<td>
							<a href="multi_object_caddy_management.php?id_user=<?=$this->id_user?>&oid=<?=$this->oid[$i]?>&action=supprimer">
                                                            <!-- maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer -->
								<img src="<?=$niveau0?>images/icones/delete_caddy_element.gif" border="0"  onMouseOver="popalt('Remove');style.cursor='pointer';"
								onMouseOut="kill()">
							</a>
						</td>
						</tr>
					</table>
				</td>
				</tr>
			</table>
			</td>
		</tr>
	<?
	}
	}
	if($zero_element){
		?>
		<tr>
			<td class="caddyTxtGras10" align="center">
				no file
			</td>
		</tr>
		<? } ?>
	</table>
	</fieldset>
</td>
</tr>
</table>
<?
}

}//fin class

?>
