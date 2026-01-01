<?php
/**
*	Ce fichier génère la boite "filter" du sélecteur pour l'afficher ou le masquer
*
*	@author	BBX - 26/11/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*/

//		==========	DATA		==========

$img1 = NIVEAU_0.'images/boutons/onglet_hide_filter.gif';
$img2 = NIVEAU_0.'images/boutons/onglet_show_filter.gif';

//		==========	DISPLAY ==========
?>
<script type="text/javascript">
	// Images hide / show
	var _selecteurFilterImageHide = '<?=$img1?>';
	var _selecteurFilterImageShow = '<?=$img2?>';
	// Textes hide / show
	var _selecteurFilterTextHide = '<?php echo __T('U_SELECTEUR_TIP_HIDE_SHOW_SELECTEUR','Hide'); ?>';
	var _selecteurFilterTextShow = '<?php echo __T('U_SELECTEUR_TIP_HIDE_SHOW_SELECTEUR','Show'); ?>';
</script>
<script type="text/javascript" src="<?=URL_SELECTEUR?>js/dashboard_filter.js"></script>
<div id="selecteur_toggle" onclick="toggleSelecteur()" onmouseover="toggleSelecteurPopup()">
	<img id="selecteur_img_toogle" src="<?=$img1?>" />
</div>
