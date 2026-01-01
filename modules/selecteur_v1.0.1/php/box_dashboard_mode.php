<?php
/**
*	Ce fichier génère la boite de sélection du mode (overtime ou overnetwork)
*
*	@author	BBX - 26/11/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*
*	29/03/2010 NSE bz 14592
*		- ajout du  onClick="majSelecteurOTONE('ONE')" pour mettre à jour la zone de texte du nombre d'élément OT, ONE
*/

//		==========	DATA		==========
$mode = isset($selecteur_values[0]) ? $selecteur_values[0] : '';
$checked_overtime = ($mode != 'one') ? ' checked' : '';
$checked_overnetwork = ($mode == 'one') ? ' checked' : '';

//		==========	DISPLAY ==========
?>

<div style="margin-left:-8px;">
	<nobr>
		<input id="selecteur_mode_overtime" type="radio" name="selecteur[mode]" value="ot"<?=$checked_overtime?> onClick="majSelecteurOTONE('OT','<?=addslashes(__T('SELECTEUR_TOP_OVER_TIME'))?>','<?=addslashes(__T('SELECTEUR_TOP_OVER_NETWORK'))?>');" />
		<label for="selecteur_mode_overtime"><span class='texteSelecteur'>Overtime</span></label>
	</nobr>
	<br />
	<nobr>
		<input id="selecteur_mode_overnetwork" type="radio" name="selecteur[mode]" value="one"<?=$checked_overnetwork?> onClick="majSelecteurOTONE('ONE','<?=addslashes(__T('SELECTEUR_TOP_OVER_TIME'))?>','<?=addslashes(__T('SELECTEUR_TOP_OVER_NETWORK'))?>');" />
		<label for="selecteur_mode_overnetwork"><span class='texteSelecteur'>Overnetwork</span></label>
	</nobr>
</div>
