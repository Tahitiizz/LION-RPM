<?
/*
*	@cb50000@
*
*	24/06/2009 - Copyright Astellia
*
*	Composant de base version cb_5.0.0.00
*
*	24/06/2009 BBX : adaptation pour CB 5.0
*/
?>
<?php
session_start();
// Includes
include_once dirname(__FILE__).'/../../../../php/environnement_liens.php';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/deploy_and_compute_functions.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/select_family.class.php');

// Début page
$arborescence = 'Dynamic Alarm Setup';
include_once(REP_PHYSIQUE_NIVEAU_0.'intranet_top.php');
?>
<div id="container" style="width:100%;text-align:center">
	<?php
		if (!isset($_GET["family"])) {
			$select_family = new select_family($_SERVER['PHP_SELF'], $_SERVER['argv'][0], 'Dynamic Alarm');
			exit;
		}
	?>
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td align="center"><img src='<?=NIVEAU_0?>images/titres/dynamic_alarm.gif'/></td>
		</tr>
		<tr valign="middle">
			<td>
				<br />
				<?php include("setup_dyn_alarm.php"); ?>
			</td>
		</tr>
	</table>
</div>
</body>
</html>
