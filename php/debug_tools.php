<?php

// Ajouté par SLC le 03/04/2009 pour faciliter les debugs.


// cette fonction affiche la trace actuelle
function trace() {
	echo "<table style='color:#900;font:11px tahoma;'>
			<tr style='background:#BBB;'>
				<th colspan='4' align='left'>&nbsp;Backtrace :</th>
			</tr>
			<tr style='background:#BBB;'>
				<th>file</th>
				<th>line</th>
				<th>class</th>
				<th>function(<span style='color:#300;'>args</span>)</th>
			</tr>
			";
	$_ = debug_backtrace();
	while ( $d = array_pop($_) ) {
		foreach ($d['args'] as &$arg) {
				if (is_array($arg)) {
					$arg = '<pre>'.trim(var_export($arg,true)).'</pre>';
				} else {
					$arg = nl2br($arg);
				}
		}
		
		echo "
		<tr align='left' valign='top' style='background:#DDD;'>
			<td title='{$d['file']}'>".str_replace(REP_PHYSIQUE_NIVEAU_0,'/',$d['file'])."</td>
			<td align='right'>{$d['line']}</td>
			<td align='right'><strong>{$d['class']}</strong></td>
			<td>{$d['type']}<strong>{$d['function']}</strong>(<span style='color:#300;'>".implode('</span>,<span style="color:#300;">',$d['args'])."</span>)</td>
		</tr> ";
	}
	echo "</table>";
}



// cette fonction affiche un tableau associatif dans un tableau html
function display_array($arr,$arr_name='',$bg_color = '#9CF') {
	$txt = '<table cellspacing="2" cellpadding="2" border="0" style="font:11px tahoma;">';
	if ($arr_name) $txt .= "<tr><th colspan='2' style='background:$bg_color;'>$arr_name</th></tr>";
	// $txt .= '<tr><th>key</th><th>val</th></tr>';
	foreach ($arr as $k => $v) {
		$txt .= "
			<tr>
				<td style='background:$bg_color;'><strong>$k</strong></td>
				<td style='background:#DDD;'>$v</td>
			</tr>
			";
	}
	$txt .= "\n</table>";
	
	return $txt;
}

// cette fonction affiche un tableau associatif à 2 dimensions dans un tableau html
function display_2Darray($arr,$arr_name='',$bg_color = '#9CF') {
	$nb_cols = count($arr[0]);
	$txt = '<table cellspacing="2" cellpadding="2" border="0" style="font:12px tahoma;">';
	if ($arr_name) $txt .= "<tr style='background:$bg_color;'><th colspan='$nb_cols'>$arr_name</th></tr>";
	// header of the table
	$txt .= "\n	<tr style='background:$bg_color;'>";
	foreach ($arr[0] as $k => $v) {
		$txt .= "\n		<th>$k</th>";
	}
	$txt .= "\n	</tr>";
	// data
	foreach ($arr as $row) {
		$txt .= "\n <tr style='background:#DDD;'>";
		foreach ($row as $v) {
			$txt .= "\n		<td>$v</td>";
		}
		$txt .= "\n </tr>";
	}
	$txt .= "\n</table>";
	
	return $txt;
}


