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

	if ($_GET['action'] != "show") return;

	session_start();

	include '../gis_class/gisExec.php';

?>
<HTML>
<HEAD>
<TITLE>GIS Legend</TITLE>
<LINK REL="stylesheet" HREF="../css/gis_styles.css" TYPE="text/css">
</HEAD>
<BODY topmargin="2" leftmargin="2" align="center">
<?php

	if (isset($_SESSION['gis_exec'])) {

		$gis_instance = unserialize($_SESSION['gis_exec']);

		echo '<table border="0" width="95%" align="center" cellspacing="0">';

		// Construction du titre

		echo '<tr><td colspan="2" class="texte_commun" style="font-weight:bold">'.$gis_instance->sort_name.'</td></tr><tr height="10"><td colspan="2"></td></tr>';

		// Construction du corps

		$data_range = $gis_instance->data_range_values;
		$tab_styles = $gis_instance->tab_styles;

		// Pour les graphes, on inclut le style defaut dans le tableau de data_ranges

		if ($gis_instance->data_type == "graph") {
			$range_default = array();

			$range_default[] = array('style_name'=>"style_voronoi_defaut", 'range_inf'=>"n/a", 'range_sup'=>"default style");
			$range_default[] = array('style_name'=>"style_defaut_cone", 'range_inf'=>"n/a", 'range_sup'=>"default style (direction)");

			$data_range = array_merge($data_range, $range_default);
		}

		for ($i=0; $i < count($tab_styles); $i++) {
			$style_def = array();
			$style_def_tmp1 = explode(';', $tab_styles[$i]['style_def']);
			for ($j=0; $j < count($style_def_tmp1); $j++) {
				$style_def_tmp2 = explode(':', $style_def_tmp1[$j]);
				$style_def[$style_def_tmp2[0]] = $style_def_tmp2[1];
			}
			$style_html[$tab_styles[$i]['style_name']] = $style_def;
		}

		for ($i=0; $i < count($data_range); $i++) {

			$style = $style_html[$data_range[$i]['style_name']];

			$opacity = $style['fill-opacity']*100;

			$rect_color = '<table width="20" height="90%" align="center" cellspacing="0" style="border: 1px solid '.$style['stroke'].';background-color:'.$style['fill'].';filter: alpha(opacity='.$opacity.')" ><tr><td></td></tr></table>';

			$range_inf = $data_range[$i]['range_inf'];
			$range_sup = $data_range[$i]['range_sup'];

			if(is_numeric($range_inf)) $range_inf = round($range_inf, 2);
			if(is_numeric($range_sup)) $range_sup = round($range_sup, 2);

			if(($range_inf != "") && ($range_sup != "")) $sep_range = " - ";

			if($sep_range == "" && ($range_inf == 0 || $range_sup == 0)) $sep_range = " - ";

			echo '<tr height="20"><td>'.$rect_color.'</td><td class="texte_commun" width="100%">&nbsp;'.$range_inf."".$sep_range."".$range_sup.'</td></tr>';

		}

		echo '</table>';

	}

?>
</BODY>
</HTML>
