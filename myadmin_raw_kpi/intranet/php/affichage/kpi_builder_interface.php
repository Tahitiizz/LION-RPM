<?
/**
 *	@cb41000@
 *
 *	11/12/2008 - Copyright Astellia
 *
 *	Composant de base version cb_4.1.0.00
 *
 *	11/12/2008 BBX : modifications pour le CB 4.1 :
 *	=> Utilisation des nouvelles méthodes et constantes
 *	=> Contrôle d'accès
 *	=> Utilisation de la classe de connexion àa la base de données
 *	=> Gestion du produit
 *	11:42 22/12/2009 SCT : vérification que la famille principale ne possède pas de 3ème axe pour l'utilisation de l'erlangB
 *	16:05 13/01/2010 SCT BZ 13718 : Limitiation GOS >= 0.01
 *	16:44 15/01/2010 SCT : modification de la condition d'exécution de la fonction erlangb
 *	17:45 15/01/2010 SCT : BZ 13757 => Format GOS
 *	03/03/2010 NSE bz 14244 : ajout condition activate_trx_charge_in_topo==1 pour affichage case "From Cell Parameters"
 *	11/03/2010 NSE bz 14713 :
 *		impossibilité de différencier dans la fonction erlang si un compteur a la veleur null ou si on a passé null à la fonction (TCH inconnu ou from cell parameters)
 *			-> pour le compteur TCH, on met false au lieu de null pour une question de typage.
 *			   	La modification nous permet dans la fonction erlang de différencier les cas from topo et unknown.
 *				Celà nous évite d'avoir à caster explicitement dans l'appel de la fonction.
 *	18/03/2010 NSE bz 14801 : erlang disponible sur toutes les familles
 *	24/03/2010 NSE bz 14796 : limitation du nombre de kpi utilisant la fonction erlang
 *	22/04/2010 NSE bz 15045 : on limite la fonctionnalité download/upload au produit Mixed Kpi
 *	27/04/2010 NSE bz 15045 : suppression de la limitation la fonctionnalité download/upload au produit Mixed Kpi (on vérifie à la place les droits de l'utilisateur)
 *       02/07/2010 OJT bz 14796 : [Réopen] limitation du nombre de kpi utilisant la fonction erlang
 *       04/08/2010 MPR bz 16538 : Ajout d'une icône d'information précisant les contraintes sur le nom (chaine alphanumeric de - de 64 caractères)
 *       14/09/2010 BBX BZ 17831 : Correction des conflits entre prototype et jquery
 *       11/03/2013 GFS BZ 31790 : Error with the valid formula
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
*	- 08:47 25/01/2008 Gwénaël : Modif pour la récupération du paramètre client_type
*
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
*	@cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
*/
?>
<?php
/*
*	@cb1300_iu2000b_pour_cb2000b@
*
*	19/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?php
/*
	le formulaire central d'édition de KPI

- maj 09/12/2009 NSE : autoriser le copier/coller dans la formule du KPI (suppression de la fonction textarea_guard) BZ 12259
- maj 23/10/2008 BBX : utilisation de la fonction new_kpi au lieu de reset_kpi pour le bouton new/ BZ 7612
- maj 14/04/2008 Benjamin : passage du champ generic_counter en lecture seule s'il s'agit de l'édition d'un KPI existant. BZ5096
- maj 23/05/2006 sls : les boutons "reset" et "new" avaient le même "name" ce qui faisait planter ma fonction javascript  de désactivation des boutons.
- maj 23/05/2006 sls : on ajoute textarea_guard() qui empeche d'écrire directement dans le textarea lorsque l'on est client.
- 22-08-2006 : ajout de la case à cocher pourcentage, du bouton Zeo et de la recopie du nom dans le cahmp label lorsque celui-ci est vide.
- 26/10/2006 xavier : remplacement du terme français "pourcentage" par le terme anglais "percentage"
*/
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
include_once REP_PHYSIQUE_NIVEAU_0."class/KpiFormula.class.php";

// Connexion à la base produit
$database = DataBase::getConnection($_GET['product']);

// 03/03/2010 NSE bz 14244 : récupéraiton de activate_trx_charge_in_topo
$activate_trx_charge_in_topo = get_sys_global_parameters('activate_trx_charge_in_topo', 1,$id_prod);

//-- MOD : Erlang Begin -----------------------------------------------------------------------------------

// Initialisation des variables pour ErlangB
$erlang = false;
$erlangButtonTitle = '';
$erlangButtonState = '';
$erlangButtonType = 'hidden';

if(isset($_GET["family"]))
{	
	$family = $_GET["family"];
	if( get_sys_global_parameters( 'activate_capacity_planing', 0, $_GET['product'] ) == 1 )
	{
		// 18/03/2010 NSE bz 14801 : erlang disponible sur toutes les familles
		$query="SELECT * FROM sys_definition_categorie WHERE family = '$family'";
		$values = $database->getRow($query);
		$aggregation = $values['network_aggregation_min'];
		// 11:37 22/12/2009 SCT : vérification que la famille ne possède pas de 3ème axe
		$query="SELECT COUNT(*) as total_famille_3eme_axe FROM sys_definition_network_agregation WHERE family = '$family' AND axe = 3";
		$values = $database->getRow($query);
		// 18/03/2010 NSE erlang ne doit pas être disponible pour les familles 3° axe.
		if( $values['total_famille_3eme_axe'] == 0 )
        {
			$erlang = true;
            $erlangButtonType = 'button';
            if( KpiFormula::getNbKpiUsingErlangB( $database ) >= get_sys_global_parameters( 'max_kpi_using_erlang', 1, $_GET['product'] ) ){
                $erlangButtonTitle = __T( 'A_E_KPI_BUILDER_MAX_KPI_USING_ERLANG' );
                $erlangButtonState = 'disabled="disabled"'; // Rendre le button disable si la limte à été atteinte
            }

            if( ( isset( $generic_counter_numerateur ) )
                && ( strpos( strtolower( $generic_counter_numerateur ), 'erlangb') !== FALSE ) )
            {
                $erlangButtonState = '';
            }
        }
    }
}

//-- MOD : Erlang End -----------------------------------------------------------------------------------
// Variables à définir
$lien_css = $path_skin . "easyopt.css";
?>
<?php if ($erlang) { ?>
<!DOCTYPE html>
<?php } ?>
<html>
<head>
<title>Generic Counters</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<script src="<?=NIVEAU_0?>js/fonctions_dreamweaver.js"></script>
<script src="<?=NIVEAU_0?>js/generic_counters.js"></script>
<script src="<?=NIVEAU_0?>js/verification_syntaxe_kpi.js"></script>

<!-- 04/08/2010 - MPR : Correction du BZ 16538 : Inclusion de fichiers js pour utiliser la fonction popalt() -->
<script type='text/javascript' src='<?=NIVEAU_0?>js/prototype/prototype.js'> </script>
<script type='text/javascript' src='<?=NIVEAU_0?>js/prototype/window.js'> </script>
<script type='text/javascript' src='<?=NIVEAU_0?>js/fenetres_volantes.js' charset='iso-8859-1'></script>

<link rel="stylesheet" href="<?=NIVEAU_0?>css/global_interface.css" type="text/css">
<link type="text/css" href="erlangb/css/style/jquery-ui-1.7.2.custom.css" rel="stylesheet" />
<?php if ($erlang) { ?>
<!-- //-- MOD : Erlang Begin ----------------------------------------------------------------------------------- -->	
<script type="text/javascript" src="erlangb/js/jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="erlangb/js/jquery-ui-1.7.2.custom.min.js"></script>
<script type="text/javascript">

                // 10/09/2010 BBX
                // On passe en mode no conflits pour prototype
                // On remplace tous les $ du script par jQuery
                // BZ 17831
                jQuery.noConflict();

		jQuery(document).ready(function(){
			jQuery("#mainbody").addClass('erlang');
			jQuery("#rki").css("display","inline");
			//Hide div effect
			jQuery("#effect").css("display","none");
			jQuery("#openR").css("display","none");
			jQuery("#closeR").css("display","none");

			// Ajouter un handler à checkbox fromR
			jQuery("#fromR").click(function(){
				// si checked
				if (jQuery("#fromR").is(":checked"))
				{
					jQuery("#closeR").show("fast");
					jQuery("#openR").hide("fast");

					/// si Tisx checked
					if (jQuery("#Tisx").is(":checked"))
					{
					// Deselectionner Tisx 
	 				jQuery("#Tisx").attr('checked', false);
					}

					// show la liste des compteurs
					jQuery("#effect").show("fast");
					/// effacer le contenu de traffic
					jQuery("#traffic").val('');
					
			
				}
				else
				{	
					jQuery("#closeR").hide("fast");
					jQuery("#openR").hide("fast");
					// hide la liste des compteurs  
					jQuery("#effect").hide("fast");
					/// deselectionner tout
					jQuery('input[name^="counter"]').attr('checked',false);
				}
			});

			jQuery("#closeR").click(function(){
				jQuery("#closeR").hide("fast");
				jQuery("#openR").show("fast");
				jQuery("#effect").hide("fast");

			});
			jQuery("#openR").click(function(){
				jQuery("#openR").hide("fast");
				jQuery("#closeR").show("fast");
				jQuery("#effect").show("fast");

			});

			jQuery("#keffect").css("display","none");
			jQuery("#kopenR").css("display","none");
			jQuery("#kcloseR").css("display","none");

			// Ajouter un handler à checkbox fromR
			jQuery("#kfromR").click(function(){
				// si checked
				if (jQuery("#kfromR").is(":checked"))
				{
					jQuery("#kcloseR").show("fast");
					jQuery("#kopenR").hide("fast");

					/// si Tisx checked
					if (jQuery("#Nisx").is(":checked"))
					{
					// Deselectionner Tisx 
	 				jQuery("#Nisx").attr('checked', false);
					}

					// show la liste des compteurs
					jQuery("#keffect").show("fast");
					/// effacer le contenu de traffic
					jQuery("#nbchannel").val('');
					
			
				}
				else
				{	
					jQuery("#kcloseR").hide("fast");
					jQuery("#kopenR").hide("fast");
					// hide la liste des compteurs  
					jQuery("#keffect").hide("fast");
					/// deselectionner tout
					jQuery('input[name^="kcounter"]').attr('checked',false);
				}
			});

			jQuery("#kcloseR").click(function(){
				jQuery("#kcloseR").hide("fast");
				jQuery("#kopenR").show("fast");
				jQuery("#keffect").hide("fast");

			});
			jQuery("#kopenR").click(function(){
				jQuery("#kopenR").hide("fast");
				jQuery("#kcloseR").show("fast");
				jQuery("#keffect").show("fast");

			});

			// Ajouter un handler à checkbox Tisx
			jQuery("#Tisx").click(function(){
				jQuery("#Gisx").attr('checked', false);
				jQuery("#Nisx").attr('checked', false);
				jQuery("#traffic").val('');
				jQuery("#closeR").hide("fast");
				jQuery("#openR").hide("fast");


				// si checked
				if (jQuery("#fromR").is(":checked"))
				{
					// hide La liste des compteurs et decocher le checkbox 
					jQuery("#effect").hide("fast");
	 				jQuery("#fromR").attr('checked', false);
					jQuery('input[name^="counter"]').attr('checked',false);
				}
			});

			// Ajouter le handler à checkbox Gisx
			jQuery("#Gisx").click(function(){
				jQuery("#Tisx").attr('checked', false);
				jQuery("#Nisx").attr('checked', false);
				jQuery("#gos").val('');
			});

			// Ajouter le handler à checkbox Nisx
			jQuery("#Nisx").click(function(){
				jQuery("#Tisx").attr('checked', false);
				jQuery("#Gisx").attr('checked', false);
<?php // 03/03/2010 NSE bz 14244 : ajout condition activate_trx_charge_in_topo==1 pour affichage case "From Cell Parameters"
	if($activate_trx_charge_in_topo==1){?>
				jQuery("#fromC").attr('checked', false);<?php
	}?>
				jQuery("#nbchannel").val('');
				if (jQuery("#kfromR").is(":checked"))
				{
					// hide La liste des compteurs et decocher le checkbox 
					jQuery("#keffect").hide("fast");
					jQuery("#kopenR").hide("fast");
	 				jQuery("#kfromR").attr('checked', false);
					jQuery('input[name^="kcounter"]').attr('checked',false);

				}
			});

<?php // 03/03/2010 NSE bz 14244 : ajout condition activate_trx_charge_in_topo==1 pour affichage case "From Cell Parameters"
	if($activate_trx_charge_in_topo==1){?>
			// Ajouter le handler à checkbox fromC
			jQuery("#fromC").click(function(){
				jQuery("#Nisx").attr('checked', false);
				jQuery("#nbchannel").val('');
				if (jQuery("#kfromR").is(":checked"))
				{
					// hide La liste des compteurs et decocher le checkbox 
					jQuery("#keffect").hide("fast");
					jQuery("#kopenR").hide("fast");
	 				jQuery("#kfromR").attr('checked', false);
					jQuery('input[name^="kcounter"]').attr('checked',false);

				}
			});<?php
}?>
			// Ajouter le handler à input traffic
			jQuery("#traffic").click(function(){
				jQuery("#Tisx").attr('checked', false);
				if (jQuery("#fromR").is(":checked"))
				{
					// hide La liste des compteurs et decocher le checkbox 
					jQuery("#effect").hide("fast");
	 				jQuery("#fromR").attr('checked', false);
					jQuery("#openR").hide("fast");
					jQuery('input[name^="counter"]').attr('checked',false);

				}
			});

			// Ajouter le handler à input gos
			jQuery("#gos").click(function(){
				jQuery("#Gisx").attr('checked', false);
			});

			// Ajouter le handler à input nbchannel
			jQuery("#nbchannel").click(function(){
				jQuery("#Nisx").attr('checked', false);
<?php // 03/03/2010 NSE bz 14244 : ajout condition activate_trx_charge_in_topo==1 pour affichage case "From Cell Parameters"
	if($activate_trx_charge_in_topo==1){?>
				jQuery("#fromC").attr('checked', false);<?php
	}?>
				if (jQuery("#kfromR").is(":checked"))
				{
					// hide La liste des compteurs et decocher le checkbox 
					jQuery("#keffect").hide("fast");
	 				jQuery("#kfromR").attr('checked', false);
					jQuery("#kopenR").hide("fast");
					jQuery('input[name^="kcounter"]').attr('checked',false);

				}
			});
		});




		jQuery(function(){
			var traffic = jQuery("#traffic"),
			hello ='',
			form1 = jQuery("#frm1"),
			gos = jQuery("#gos"),
			fromR =  jQuery("#fromR"),
			kfromR =  jQuery("#kfromR"),
<?php // 03/03/2010 NSE bz 14244 : ajout condition activate_trx_charge_in_topo==1 pour affichage case "From Cell Parameters"
	if($activate_trx_charge_in_topo==1){?>
			fromC =  jQuery("#fromC"),<?php
	}?>
			nbchannel = jQuery("#nbchannel"),
			Gisx = jQuery("#GisX"),
			Tisx = jQuery("#TisX"),
			Nisx = jQuery("#NisX"),
			allFields = jQuery([]).add(traffic).add(gos).add(nbchannel).add(Nisx).add(Gisx).add(Tisx).add(fromR).add(kfromR)<?php // 03/03/2010 NSE bz 14244 : ajout condition activate_trx_charge_in_topo==1 pour affichage case "From Cell Parameters"
	if($activate_trx_charge_in_topo==1){?>.add(fromC)<?php }?>,
			tips = jQuery("#validateTips");

			function updateTips(t) {
				tips.text(t);
			}

			function display_error(o,message) {
				o.addClass('ui-state-error');
				updateTips(message);
				return true;

			}

			function isDigit(o,n) {
				if (isNaN(o.val())) {
						display_error(o,n + " must be a number.");
						return false;
				} else {
					if (o.val() < 0) {
						display_error(o,n + " must be a positive number.");
						return false;
					} else {

						return true;
					}
					
				}
			}

			function isEmpty(o,n)
			{
				if ( (o.val() == null) || (o.val() == 0)) {
					display_error(o,"Value of " + n + " can't be empty.");
					return true;
				} else {
					return false;
				}
			}
			
			function checkVal(o,n,min,max) {
				if (!isDigit(o,n) || isEmpty(o,n)) return false;
				if ( (min != 0) && (max != 0) ) {
					
					if ( o.val() > max || o.val() < min ) {
					display_error(o,"Value of " + n + " must be between "+min+" and "+max+".");
					return false;
					} else {
						return true;
					}
				} 

				return true;

			}



			function checkUnknown() {
				var count =0;
				if ( (jQuery("#Gisx").is(":checked") ) || (jQuery("#Tisx").is(":checked") ) || (jQuery("#Nisx").is(":checked") ) )
				count++;
				if (count !=1) {

						display_error(traffic,"Please select one Unknown field.");
						display_error(nbchannel,"");
						display_error(gos,"");
						return false;


				}
				else return true;

			}
			function checkraws() {
				var kpis = [];
				var l;
				if ( jQuery("input[name^='counter']") ) {
					jQuery("input[name^='counter']").each(function() {
							if (this.checked) { kpis.push(this.value); }
							});
					l = kpis.length;

				} else {

				l = 0;
				}

				if ( fromR.is(":checked") && (l == 0) )  {
					display_error(traffic,"Please select at least one Raw counter.");
					return false;
				} else {

				return true;

				}

			}
			function checkkraws() {
				var kkpis = [];
				var l;
				if ( jQuery("input[name^='kcounter']") ) {
					jQuery("input[name^='kcounter']").each(function() {
							if (this.checked) { kkpis.push(this.value); }
							});
					l = kkpis.length;

				} else {

				l = 0;
				}

				if ( kfromR.is(":checked") && (l == 0) )  {
					display_error(nbchannel,"Please select at least one Raw counter.");
					return false;
				} else {

				return true;

				}

			}

			function from_php(list){
				jQuery.get("erlangb/erlang.php", { list: list},
				function(data){
					
					// maj 24/11/2009 - MPR : On ajoute le résultat du calcul à la place de tout réécraser
					// On vérifie que la formule n'est pas la même sinon on l'a en double
					if( data != jQuery("#formula_kpi").text() && data )
					{
                                            // 10/09/2010 BBX
                                            // Correction de la valorisation du champ
                                            // BZ 17831
                                            $('formula_kpi').value += data;
					}
					
				});
			}


			function misenforme(tr,go,nb,gx, tx,nx,cell,raw,kraw) {
				var result='';
				var list='';
				var send = false;
				var mode='CH';
				if ( ( nb == null) ||( nb == '') ||( nb < 0)) nb = 0;
				if ( ( tr == null) ||( tr == '') ||( tr < 0)) tr = 0;
				if ( ( go == null) ||( go == '') ||( go < 0)) go = 0;
				// 17:45 15/01/2010 SCT : BZ 13757 => Format GOS
				//if (go) go/=100;
				if (gx) {
					mode ='GOS';
					if ( (cell)||(raw) ||(kraw)) send = true;
					list = 'A='+tr+'&N='+nb+'&mode=GOS';
				} else if (tx) {
					mode ='TRAFFIC';
					if ((cell)||(kraw)) send = true;
					list = 'P='+go+'&N='+nb+'&mode=TR';
				} else if (nx) {
					mode ='CHANNELS';
					if (raw) send = true;
					list = 'A='+tr+'&P='+go+'&mode=CH';
				}
				if (send) {
					// 12:13 22/12/2009 SCT : remplacement de la variable $network par $network1stAxis
					// 16:44 15/01/2010 SCT : modification de la condition d'exécution de la fonction erlangb => passage de la variable $aggregation
					var prefix = "erlangb($network1stAxis,'$network1stAxis','<?php echo $aggregation; ?>',";
					var postfix = ")";
		
					var prefix_display = "erlangb(";
					var postfix_display = ")";

					result += prefix;
					var kpis = [];
					jQuery("input[name^='counter']").each(function() {
						if (this.checked) { kpis.push(this.value); }
						});

					var kkpis = [];
					jQuery("input[name^='kcounter']").each(function() {
						if (this.checked) { kkpis.push(this.value); }
					});
					
					
					
					// maj 24/11/2009 MPR - Formule a enregistrer
					// res = "'$network"',";
					if(kpis.join('+') == ""){param_kpi = "null"; }else{param_kpi =kpis.join('+');}
					if(kkpis.join('+') == ""){
					// 11/03/2010 NSE bz 14713 : on met false au lieu de null pour une question de typage. 
					// La modification nous permet dans la fonction erlang de différencier les cas from topo et unknown. 
					// Celà nous évite également d'avoir à caster explicitement.
					// Rq : il est inutile de faire un test sur la valeur de from cell parameters (pour passer null ou flase par exemple) 
					// car la fonction erlang distingue les cas de figure :
					// Traffic (Erl.) unknown, Gos 2%, TCH from cell parameters
					// erlangb(null,false,2,0,0,'TRAFFIC')
					// et
					// Traffic (Erl.) select Raw, Gos 8%, TCH unknown
					// erlangb(ASCMP_EFR_Tc,false,8,0,0,'CHANNELS')
					// grâce au paramètre mode (dernier param)
					param_kkpi = "false"; }
					else{param_kkpi =kkpis.join('+');}
					res = ""+param_kpi+",";
					res += ""+param_kkpi+",";
					res += ""+go+",";
					res += ""+nb+",";
					res += ""+tr+",";
					res += "'"+mode+"'";
					
					// maj 24/11/2009 MPR - Formule a afficher
					result_display = prefix_display + res + postfix_display;
					result += res + "," + postfix;
					
					// maj 25/11/2009 MPR : On conserve la formule du erlang à enregistrer 
					if( result != jQuery("#formula_kpi").text() && result)
					{
						//jQuery("#formula_kpi").append( result_display );
                        $('formula_kpi').value += result_display;
					}
					
					
					return result_display;
				} else {
					return from_php(list);
				}
			}
			// Accordion
			jQuery("#accordion").accordion({
				header: "h3",
				autoHeight: true
			 });

			// Dialog			
			jQuery('#dialog').dialog({
				autoOpen: false,
				width: 600,
				modal: true,
				close: function() {
					tips.text(hello);
					jQuery('#effect').hide('fast');
				},
				open: function() {
					tips.text(hello);
				},
				buttons: {
					"Ok": function() { 
						var formule ='';
						var bValid = true;
						var form1isactive = false;
						if (jQuery("#frm1 #Tisx").is(":checked") || jQuery("#frm1 #fromR").is(":checked") || jQuery("#frm1 #Tisx").is(":checked") || jQuery("#frm1 #Nisx").is(":checked") || jQuery("#frm1 #Gisx").is(":checked") || jQuery("#frm1 #fromR").is(":checked") || jQuery("#frm1 #fromC").is(":checked")) form1isactive =true;

						if ( (jQuery("#frm1 #traffic").val() != 0) || (jQuery("#frm1 #nbchannel").val() != 0) || (jQuery("#frm1 #gos").val() != 0) )
							form1isactive =true;



						if (form1isactive) {

							allFields.removeClass('ui-state-error');
							if (!jQuery("#frm1 #Gisx").is(":checked") )
							// 16:05 13/01/2010 SCT : BZ13178
							bValid = bValid && checkVal(jQuery("#frm1 #gos"),'Gos%',0.01,100);
							if (!jQuery("#frm1 #Tisx").is(":checked") && !jQuery("#frm1 #fromR").is(":checked"))
							bValid = bValid && checkVal(jQuery("#frm1 #traffic"),'Traffic (Erl.)',0,0);
							if (!jQuery("#frm1 #Nisx").is(":checked") <?php // 03/03/2010 NSE bz 14244 : ajout condition activate_trx_charge_in_topo==1 pour affichage case "From Cell Parameters"
	if($activate_trx_charge_in_topo==1){?>&& !jQuery("#frm1 #fromC").is(":checked")<?php }?>&& !jQuery("#frm1 #kfromR").is(":checked"))
							bValid = bValid && checkVal(jQuery("#frm1 #nbchannel"),'TCH (Number of Traffic Channel)',0,0, jQuery("#frm1 #Nisx"));
							bValid = bValid && checkUnknown();
							bValid = bValid && checkraws();
							bValid = bValid && checkkraws();
							if (bValid) {
							formule = misenforme(traffic.val(),gos.val(),nbchannel.val(), jQuery("#frm1 #Gisx").is(":checked"),jQuery("#frm1 #Tisx").is(":checked"),jQuery("#frm1 #Nisx").is(":checked"),jQuery("#frm1 #fromC").is(":checked"),jQuery("#frm1 #fromR").is(":checked"),jQuery("#frm1 #kfromR").is(":checked"));
							
							jQuery(this).dialog('close');
							}
						}
					}, 

					"Cancel": function() { 
						allFields.val('').removeClass('ui-state-error');
						jQuery(this).dialog("close");
					},
                                        // NSE 14/06/2013 bz 34015 : on cache la liste des compteur au clic sur Reset
					"Reset": function() { 
						tips.text('');
						jQuery("#closeR").hide("fast");
						jQuery("#openR").hide("fast");
						jQuery("#effect").hide("fast");
						jQuery("#kcloseR").hide("fast");
						jQuery("#kopenR").hide("fast");
						jQuery("#keffect").hide("fast");
						allFields.val('').removeClass('ui-state-error');
						jQuery('input:text').each( function() {     jQuery(this).val('');});
						jQuery('input:checkbox').each( function() {
								jQuery(this).attr('checked',false);
						});
						jQuery('#formula_kpi').val('');
					} 
				}
			});
			
			// Dialog Link
			jQuery('#dialog_link').click(function(){
				jQuery('#dialog').dialog('open');
				return false;
			});
			
			//hover states on the static widgets
			jQuery('#dialog_link, ul#icons li').hover(
				function() { jQuery(this).addClass('ui-state-hover'); },
				function() { jQuery(this).removeClass('ui-state-hover'); }

			);		

		});
</script>

<?php } 

$list1='';
$list2='';
if(isset($_GET["family"])){
	$family = $_GET["family"];
	$query = "
				SELECT distinct edw_field_name, comment,edw_field_name_label FROM sys_field_reference
				WHERE id_group_table in (
					SELECT id_ligne FROM sys_definition_group_table
					WHERE family='$family'
				and visible=1
				)
				AND visible=1 and on_off=1 and new_field!=1
				ORDER BY edw_field_name_label ASC";
	$result=$database->execute($query);
	$nombre_resultat=$database->getNumRows();
	if ($nombre_resultat == 0){
		$list1 =  "Error : [no data for this family]";
		$list2 =  "Error : [no data for this family]";

	}else{
		$compteur_field=0;
		foreach($database->getAll($query) as $row) {
			$field=$row['edw_field_name'];
			$group_table=$row['comment'];
			$field_label=$row['edw_field_name_label'];
			if ($field_label!="") {
				$display_raw=$field_label;
			}else{
				$display_raw=$field;
			}

			// maj  24/11/2009 - Ajout de balise label sur le nom des compteurs
			$list1 .= '<input class="check" type="checkbox" name="counter'.$compteur_field.'" id="counter['.$compteur_field.']" value="'.$field.'"/>';
			$list1 .= '<label for="counter['.$compteur_field.']">'.strtoupper($display_raw).'</label><br />';

			$list2 .= '<input class="check" type="checkbox" name="kcounter'.$compteur_field.'" id="kcounter['.$compteur_field.']" value="'.$field.'"/>';
			$list2 .= '<label for="kcounter['.$compteur_field.']">'.strtoupper($display_raw).'</label><br />';
			$compteur_field++;
		} /// foreach
	}
	
} /// isset

?>
<style type="text/css">
	/*demo page css*/
	body.erlang { font: 62.5% "Trebuchet MS", sans-serif;}
	.demoHeaders { margin-top: 2em; }
	#rki {display:none;}
	.notice {text-align:right;color:#6699FF;font-size:10px;font-weight:bold}
	#dialog_link {padding: .4em 1em .4em 20px;text-decoration: none;position: relative;}
	#dialog_link span.ui-icon {margin: 0 5px 0 0;position: absolute;left: .2em;top: 50%;margin-top: -8px;}
	ul#icons {margin: 0; padding: 0;}
	ul#icons li {margin: 2px; position: relative; padding: 4px 0; cursor: pointer; float: left;  list-style: none;}
	ul#icons span.ui-icon {float: left; margin: 0 4px;}
	fieldset.fset { width:90%;}
	label.lab { font-weight: bolder; font-size: 10px; width:36%; float : left;}
	label.lib { font-weight: bolder; font-size: 12px; width:15%; float : left;}
	label.lib2 { font-weight: bolder; font-size: 12px; width:23%; float : left;}
	input.text {width:50%;}
	input.check {text-align : right;}
	form.eform {}
	.boutonPlat{minWidth:"30px";minheight:"20px";}
	.wrap {text-align:left;padding-left:160px; margin:0px;}
</style>
<!-- //-- MOD : Erlang End ----------------------------------------------------------------------------------- -->
</head>
<body id="mainbody">
<!-- //-- MOD : Erlang Begin ----------------------------------------------------------------------------------- -->
<?php 
if ($erlang) { ?>
	<!-- roya begin -->
	<div id="rki">
		<!-- ui-dialog -->
		<div id="dialog" title="ErlangB">
			<div class="content">
				<div id="accordion" style="cursor:pointer;">
					<div>
						<h3><a href="#"><?=__T('A_KPI_BUILDER_ERLANG_TITLE_INFO')?></a></h3>
						<div><?=__T('A_KPI_BUILDER_ERLANG_DESCRIPTION')?></div>
					</div>
					<div>
						<h3><a href="#"><?=__T('A_KPI_BUILDER_ERLANG_USE_FORMULA')?></a></h3>
						<div>
							<p id="validateTips"></p>
							<form class="eform" name="frm1" id="frm1">
								<fieldset class="fset">
								
								<label class="lab" for="traffic"><?=__T('A_KPI_BUILDER_ERLANG_TRAFFIC')?></label>
								<input type="text" name="traffic" id="traffic" class="text ui-widget-content ui-corner-all" />
								
								<div class="wrap">
									<!-- maj 24/11/2009 MPR : Ajout d'une balise label -->
									<input class="check" type="checkbox" name="Tisx" id="Tisx" value="1" />
									
									<label for="Tisx"><?=__T('A_KPI_BUILDER_ERLANG_UNKOWN')?></label>
									<input class="check" type="checkbox" name="fromR" id="fromR" value="1" />
									
									<label for="fromR"><?=__T('A_KPI_BUILDER_ERLANG_SELECTION_RAW_COUNTERS')?></label>
									<div class="notice" id="openR" style="cursor: pointer;">Open</div>		
									<div id="effect">
										<div class="notice" id="closeR" style="cursor: pointer;">Close</div>
										<?php if(isset($_GET["family"])) echo $list1;?>
									</div>
								</div>
								<!-- maj 24/11/2009 MPR : Ajout de balises <label> pour les labels -->
								<label class="lab" for="gos"><?=__T('A_KPI_BUILDER_ERLANG_GOS_TITLE')?></label>
								<input type="text" name="gos" id="gos" value="" class="text ui-widget-content ui-corner-all" />
								<div class="wrap">
									<input class="check" type="checkbox" name="Gisx" id="Gisx" value="1" /><label for="Gisx"><?=__T('A_KPI_BUILDER_ERLANG_UNKOWN')?></label>
								</div>
								<label class="lab" for="nbchannel"><?=__T('A_KPI_BUILDER_ERLANG_TCH_LABEL')?></label>
								<input type="text" name="nbchannel" id="nbchannel" value="" class="text ui-widget-content ui-corner-all" />
								<div class="wrap">
								
									<input class="check" type="checkbox" name="Nisx" id="Nisx" value="1" />
									<label for="Nisx"><?=__T('A_KPI_BUILDER_ERLANG_UNKOWN')?></label>
									<?php // 03/03/2010 NSE bz 14244 : ajout condition activate_trx_charge_in_topo==1 pour affichage case "From Cell Parameters"
									if($activate_trx_charge_in_topo==1){?>
									<input class="check" type="checkbox" name="fromC" id="fromC" value="1" />
									<label for="fromC"><?=__T('A_KPI_BUILDER_ERLANG_CELL_PARAMETERS_LABEL')?></label><?php }?>
									<input class="check" type="checkbox" name="kfromR" id="kfromR" value="1" />
									<label for="kfromR"><?=__T('A_KPI_BUILDER_ERLANG_SELECTION_RAW_COUNTERS')?></label>
									<div class="notice" id="kopenR" style="cursor: pointer;">Open</div>		
									<div id="keffect">
									<div class="notice" id="kcloseR" style="cursor: pointer;">Close</div>
										<?php if(isset($_GET["family"])) echo $list2; ?>
									</div>

								</div>
								</fieldset>
							</form>
						</div>
					</div> <!-- --> 
				</div> <!-- End accordion-->
			</div> 
		</div>			
	<!-- ui-dialog end -->
	</div> <!-- End rki -->
	<!-- roya end -->
<?php } ?>
<!-- //-- MOD : Erlang End ----------------------------------------------------------------------------------- -->
<?php
$libelle_favoris = "Counter Builder";
$lien_favoris = getenv("SCRIPT_NAME"); //récupère le nom de la page
$family = $_GET["family"];
$kpi_comment = stripslashes($_GET["kpi_comment"]);
$kpi_label = stripslashes($_GET["kpi_label"]);
$kpi_onoff = $_GET["kpi_onoff"];
$kpi_pourcentage = stripslashes($_GET["kpi_pourcentage"]);
if ($kpi_pourcentage == 1) {
    $kpi_pourcentage = 'checked';
} else {
    $kpi_pourcentage = '';
}

// maj 14/04/2008 Benjamin : passage du champ generic_counter en lecture seule s'il s'agit de l'édition d'un KPI existant. BZ5096
$generic_counter_ro = ($generic_counter_name != '') ? ' readonly' : '';
$generic_counter_color = ($generic_counter_name != '') ? ' style="color:#898989;"' : '';

// 22/07/2009 BBX : si le nom n'est pas bon, on ne condamne pas le champ du nom. BZ 10516
if(isset($_GET['kpinameaccepted']) && ($_GET['kpinameaccepted'] == 0)) {
	$generic_counter_ro = '';
	$generic_counter_color  = '';
}

// maj 25/10/2008 BBX : ajout de la récupération de l'id kpi après sauvegarde
if(isset($_GET['zone_id_generic_counter'])) 
{
	// Si l'i n'est pas nulle on le récupère
	if(trim($_GET['zone_id_generic_counter']) != '')
		$id_generic_counter = $_GET['zone_id_generic_counter'];
	elseif(isset($_GET['generic_counter_name'])) {
		// Si il est nul mais qu'on a le nom, on retrouve l'id
		$query = "SELECT id_ligne FROM sys_definition_kpi WHERE kpi_name ILIKE '{$_GET['generic_counter_name']}'";
		$array_kpi = $database->getRow($query);
		$id_generic_counter = $array_kpi['id_ligne'];
	}
}

?>
<div align="center" valign="middle">

<form method="post" action="kpi_builder_interface.php<?php echo '?family='.$family.'&product='.$_GET['product']; ?>" enctype="multipart/form-data">
<table cellpadding="0" cellspacing="0" width="610px">
	<tr>
		<td>
			<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<table width="100%" border="0" cellspacing="0" cellpadding="4" class="tabPrincipal">
							<tr>
								<td>
									<fieldset>
										<legend class="texteGrisBold">
											<a href="#" onclick="showUploadDownload(); return false" style="font : bold 9pt Verdana, Arial, sans-serif; text-decoration:none;">&nbsp;<img src="<?=NIVEAU_0?>images/icones/puce_fieldset.gif">&nbsp;Upload / Download&nbsp;
											<img id="imgUploadDownload" src="<?=NIVEAU_0?>images/icones/<?php echo (isset($_POST['upload']) && $_POST['upload'] ==  __T('A_CONTEXT_UPLOAD_BUTTON') ? "tridown.gif" : "tri.gif"); ?>">&nbsp;</a>
										</legend>
										<table id="tableUploadDownload" width="100%"  border="0" cellpadding="4" cellspacing="0" style="display:<?php echo (isset($_POST['upload']) && $_POST['upload'] ==  __T('A_CONTEXT_UPLOAD_BUTTON') ? "block" : "none"); ?>">
											<tr>
												<td class="texteGris">Select a file to upload</td>
											</tr>
											<tr>
												<td>
													<input type="hidden" name="family" value="<?php echo (empty($_GET['family']) ? $_POST['family'] : $_GET['family']); ?>"/>
													<input type="hidden" name="idProduct" value="<?php echo (empty($_GET['product']) ? $_POST['idProduct'] : $_GET['product']); ?>"/>
													<input type="file" name="fichier" size="30"/>
													<input type="submit" name="upload" value="<?php echo __T('A_CONTEXT_UPLOAD_BUTTON');?>" class="bouton"/>
												</td>
											</tr>
											<tr>
												<td><a target="_blank" href="kpi_builder_download.php?idProduct=<?php echo (empty($_GET['product']) ? $_POST['idProduct'] : $_GET['product']); ?>&family=<?php echo (empty($_GET['family']) ? $_POST['family'] : $_GET['family']); ?>" class="texteGris">Download KPI list</a></td>
											</tr>
											<?php
											if ( isset($_POST['upload']) && $_POST['upload'] ==  __T('A_CONTEXT_UPLOAD_BUTTON') )
											{
												echo '<tr><td>';
												include dirname(__FILE__).'/kpi_builder_upload.php';
												echo '</td></tr>';
											}
											?>
										</table>
									</fieldset>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>

<br />
<form name="formulaire" method="post" action="kpi_builder_kpi_check.php?action=save">
<input type="hidden" name="product" value="<?=$_GET['product']?>" />
<table cellpadding="0" cellspacing="0" width="500px">
	<tr>
	<td>
	<table border="0" align="center" cellpadding="0" cellspacing="0">
		<tr>
			<td>
			<table width="100%" border="0" cellspacing="0" cellpadding="4" class="tabPrincipal">
				<tr>
					<td>
						<fieldset>
						<legend class="texteGrisBold">
							&nbsp;<img src="<?=NIVEAU_0?>images/icones/puce_fieldset.gif">&nbsp;KPI function&nbsp;
						</legend>
						<table width="100%" border="0" cellpadding="4" cellspacing="0">
							<tr>
								<td height="40" align="center">
								<table width="100%" border="0" cellpadding="4" cellspacing="0">
									<tr>
	<td style="text-align:left">
	<input type="hidden" name="zone_id_generic_counter" value="<?=$id_generic_counter?>">
	<input type="hidden" value="<?=$family?>" name="family_group_table_name">
        <!-- 04/08/2010 - MPR : Correction du BZ 16538  Ajout d'une icône d'information précisant les contraintes sur le nom (chaine alphanumeric de - de 64 caractères) -->
	<label class="lib2">Name&nbsp;<img alt="" onmouseover="popalt('<?=__T('A_KPI_BUILDER_UPLOAD_KPI_NAME_INFOS')?>')" src="<?=NIVEAU_0?>images/icones/cercle_info.gif" />&nbsp;:</label>
	<input type="text" onblur="copy_name_to_label();" name="generic_counter" class="ui-state-default boutonPlat" size="45" value="<?=$generic_counter_name?>"<?=$generic_counter_color?><?=$generic_counter_ro?>>
	</td>
	<td style="text-align:left">
	<input type="button" name="drop_kpi" class="ui-state-default boutonPlat" value=" Drop " onclick="javascript:delete_raw_data()">
	<input type="button" name="reset_kpi_button" class="ui-state-default boutonPlat" value=" Reset " onclick="javascript:reset_kpi()">
	<input type="button" name="save" class="ui-state-default boutonPlat" value=" Save " onclick="save_kpi()">
	<input type="button" name="reset_kpi_button_2" class="ui-state-default boutonPlat" value=" New " onclick="javascript:new_kpi()">
	</td>
	</tr>
	<tr>
	<td style="text-align: left" colspan="2">
	<label class="lib">Label :</label>
	<input type="text" name="label_kpi" class="ui-state-default boutonPlat" size="89" value="<?=$kpi_label?>">
	</td>
										</tr>
										<tr valign="top">
	<td colspan="2" valign="top" style="text-align:left">
	<label class="lib">Comment :</label> 
	<input type="text" name="comment_kpi" class="ui-state-default boutonPlat" size="89" value="<?=$kpi_comment?>">
	</td>
										</tr>
										<tr valign="top">
	<td colspan="2" valign="top" style="text-align:left">
	<label class="lib">Percentage :</label>
	<input type="checkbox" name="pourcentage" <?=$kpi_pourcentage?>>&nbsp;<font size="1" face="Arial, Helvetica, sans-serif">(Limit KPI value to 100%)</font>
										</td>
									</tr>
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
		<tr><td>&nbsp;</td></tr>
		<tr>
			<td class="tabPrincipal" style="padding:5px;">
			<fieldset>
			<legend class="texteGrisBold">
				&nbsp;<img src="<?=NIVEAU_0?>images/icones/puce_fieldset.gif">&nbsp;KPI formula&nbsp;
			</legend>
			<table valign="middle" align="center" cellpadding="0" cellspacing="0" width="300">
			<tbody>
				<tr>
					<td height="10" align="center"><br>
						<textarea class="formula_kpi" id="formula_kpi" rows="13" cols="70" name="zone_formule_numerateur"><?=str_replace("\\","",$generic_counter_numerateur)?></textarea>
						<br><br>
					</td>
				</tr>
				<tr>
					<td align="center" width="478">
						<table width="100%" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td align="left">
								<table width="" border="0" cellpadding="0" cellspacing="0">
									<tbody>
										<tr>
											<td> <input class="ui-state-default boutonPlat" name="n_back" value="back" onclick="gestion_formule('back','zone_formule_numerateur')" type="button">
											</td>
											<td>&nbsp; </td>
											<td> <input class="ui-state-default boutonPlat" name="n_delete" value="delete" onclick="gestion_formule('delete','zone_formule_numerateur')" type="button">
											</td>
										</tr>
									</tbody>
								</table>
							</td>
							<td align="right">
								<table width="" border="0" cellpadding="0" cellspacing="0">
								<tbody>
									<tr>
                                        <td>
                                            <input type="hidden" name="ErlangBDefValue" value="<?php echo $erlangButtonTitle; ?>" />
                                            <input class="ui-state-default boutonPlat" id="dialog_link" value="ErlangB" name="ErlangB" title="<?php echo $erlangButtonTitle; ?>" <?php echo $erlangButtonState; ?> type="<?php echo $erlangButtonType; ?>" />
                                        </td>
                                        <td>&nbsp;</td>
										<td> <input class="ui-state-default boutonPlat" id="n_parenthese_o" name="n_parenthese_o" value="(" onclick="gestion_formule('(','zone_formule_numerateur')" type="button">
										</td>
										<td>&nbsp; </td>
										<td> <input class="ui-state-default boutonPlat" name="n_plus" value="+" onclick="gestion_formule('+','zone_formule_numerateur')" type="button">
										</td>
										<td>&nbsp; </td>
										<td> <input class="ui-state-default boutonPlat" name="n_moins" value="-" onclick="gestion_formule('-','zone_formule_numerateur')" type="button">
										</td>
										<td>&nbsp; </td>
										<td> <input class="ui-state-default boutonPlat" name="n_multiplier" value="x" onclick="gestion_formule('*','zone_formule_numerateur')" type="button">
										</td>
										<td>&nbsp; </td>
										<td> <input class="ui-state-default boutonPlat" name="n_diviser" value="/" onclick="gestion_formule('/','zone_formule_numerateur')" type="button">
										</td>
										<td>&nbsp; </td>
										<td> <input class="ui-state-default boutonPlat" name="n_parenthese_f" value=")" onclick="gestion_formule(')','zone_formule_numerateur')" type="button">
										</td>
										<td>&nbsp; </td>
										<td> <input class="ui-state-default boutonPlat" name="n_abs" value="ABS" onclick="gestion_formule('ABS(','zone_formule_numerateur')" type="button">
										</td>
										<td>&nbsp; </td>
										<?php // 13/10/2014 FGD Bug 31790 - [REC][CB 5.2.0.34][TC#TA-62232][KPI Builder] Error with the valid formula ?>
										<td> <input class="ui-state-default boutonPlat" name="n_zero" value="Greater than 0" onclick="gestion_formule('GREATEST(0,','zone_formule_numerateur')" type="button">
										</td>
									</tr>
								</tbody>
								</table>
							</td>
						</tr>
						</table>
					</td>
				</tr>
			</tbody>
			</table>
			</fieldset>
			</td>
		</tr>
	</table>
	</td>
	</tr>
	</table>
	</form>

<script language="JavaScript" type="text/javascript">
<!--
// modif 08:47 25/01/2008 Gwénaël
	// Modif pour la récupération du paramètre client_type
client_type = "<?= getClientType($_SESSION['id_user']);
?>";

// modif 14:13 09/12/2009 NSE : autoriser le copier/coller dans la formule du KPI (suppression de la fonction textarea_guard) BZ 12259

// -->
</script>

</body>
</html>
