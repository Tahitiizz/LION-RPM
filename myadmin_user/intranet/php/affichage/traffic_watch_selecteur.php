<?
/**
* Traitement du sélecteur
* 
* @author MPR
* @version CB4.1.0.0
* @package Application Statistics
* @since CB4.1.0.0
*
*	maj 03/11/2008, maxime : Nouveau Sélecteur
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14

	maj 24/08/2007, benoit : suppression de l'appel à la fonction JS 'saisie_ok_selecteur()' lors du submit. Cette fonction n'existe plus

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
<?php

	// 24/08/2007 - Modif. benoit : suppression de l'appel à la fonction JS 'saisie_ok_selecteur()' lors du submit. Cette fonction n'existe plus
	require_once($repertoire_physique_niveau0."modules/conf.modules.inc");
	require_once(MOD_SELECTEUR."php/selecteur.class.php");
	require_once(MOD_SELECTEUR."php/SelecteurApplicationStats.class.php");
	?>
	
	<div align ="center">
	<?
			$selecteur = new SelecteurApplicationStats('traffic');
			$selecteur->getSelecteurFromArray($_POST['selecteur']);
			$selecteur_general_values =$selecteur->build();
	// $selecteur->debug();

/*
?>
	</div>
<form name="selecteur" method="get" action="">
	<input type="hidden" value=1 name="start_monday" id="start_monday" />
	<input type="hidden" value="<?=$niveau0?>" name="niveau0" id="niveau0" />
	<script src="<?=$niveau0?>js/popcalendar.js"></script>

	<table width='150' cellspacing="2" cellpadding="4" border="0" class="tabPrincipalClair">
		<tr valign="top">
		<td>

			<fieldset>
			<legend class="texteGrisBoldPetit">
				&nbsp;<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif">&nbsp;Time&nbsp;
			</legend>
				<table>
					<tr>
						<td><img id=calendrier onMouseOver="popalt('Open calendar'); style.cursor='pointer';"
								onMouseOut="kill()"
								onclick="popUpCalendar(this, selecteur.day,'dd-mm-yyyy','', -1, -1)"
								valign="top" align="absmiddle"
								src="<?=$niveau0?>images/icones/bouton_calendrier.gif" border="0" />
						</td>
						<td  align=center >
							<input contentEditable=false class=zoneTexteStyleXP type="text"	name="day" value="<? if ($_GET['day']) {echo $_GET['day'];} else {echo date('d-m-Y');} ?>" size="10"  maxlength="10" >
						</td>
					</tr>
					<tr>
						<td  align=left colspan=2>
							<script type="text/javascript" src="<?$niveau0?>js/mini_context.js"></script>
						</td>
					</tr>
				</table>
			</fieldset>

		</td>
	</tr>
</table>

	<div align="center"><input name="Submit" value="" class="boutonDisplay" onmouseover="style.cursor='hand';" type="submit"></div>

</form>
<? 
*/
?>
