<?php

/**
 * 
 * Classe d'outils nécessaire aux développements des parseurs OMC
 * @author g.francois
 *
 */
class Tools {
	
	// Familles BSS
	static public $FAMILY_BSS 	= "bss";
	static public $FAMILY_GPRS 	= "bssgprs";
	static public $FAMILY_TRX 	= "bsstrx";
	static public $FAMILY_BSS_ADJ 	= "bssadj";

	
	// Familles NSS
	static public $FAMILY_MSC 	= "msc";
	static public $FAMILY_HLR 	= "hlr";
	static public $FAMILY_SCP 	= "scp";
	static public $FAMILY_MGW 	= "mgw";
	static public $FAMILY_SGSN 	= "sgsn";
	static public $FAMILY_GGSN 	= "ggsn";

	
	// Familles Wimax
	static public $FAMILY_APS 	= "aps";
	
	// Familles UTRAN
	static public $FAMILY_CELLB	= "cellb";
	static public $FAMILY_ADJ	= "adj";
	static public $FAMILY_IUB	= "iubl";
	static public $FAMILY_IUR	= "iurl";
	
	
	static private $chronoS;
	static private $chronoE;
	static private $chronoDiff;
	static private $chronoCumul;
	static public $debug;
	
	/**
	 * Fonction qui retourne un timestamp à partir d'une date
	 * 
	 * @param text $date format de date issu du fichier source (au format 24/09/2005 23:00:03)
	 * @return timestamp $timestamp timestamp représentant la date en entrée
	 * @link http://www.php.net/manual/en/function.strtotime.php
	 */
	public static function convert_date_to_timestamp($date)
	{
		return strtotime(str_replace('/','-',$date));
	}
	
	/**
	 * 
	 * Initialise le compteur temporel pour un identifiant $id.
	 * Cette fonction établit le t0 qui permettra de tracer un temps d'exécution.
	 * @param String $id
	 */
	public static function debugTimeExcStart($id = "defaut")
	{
		self::$chronoS[$id] = microtime(true);
	}

	/**
	 * 
	 * Termine le calcul du temps d'exécution de l'identifiant $id.
	 * @param String $id
	 * @param boolean $show
	 */
	public static function debugTimeExcEnd($id = "defaut", $show = true)
	{
		self::$chronoE[$id] = microtime(true);
		self::$chronoDiff[$id] = self::$chronoE[$id] - self::$chronoS[$id];
		if (!isset(self::$chronoCumul[$id])) { self::$chronoCumul[$id] = self::$chronoDiff[$id]; }
		else { self::$chronoCumul[$id] += self::$chronoDiff[$id]; }
		self::$chronoS[$id] = self::$chronoE[$id] = 0;
		$return=self::$chronoDiff[$id];
		if ($show) { 
			$time_txt  = sprintf("%01.5f", self::$chronoDiff[$id]);
			$cumul_txt = sprintf("%01.5f", self::$chronoCumul[$id]);
			$message = "<span style='color:blue;'><b>PERF : </b>$id ($time_txt sec, $cumul_txt sec cumulated)</span>";
			displayInDemon($message);
			self::$chronoDiff[$id] = self::$chronoCumul[$id] = 0;
		}
		return $return;
	}
	
	/**
	 * 
	 * Génère le chemin du fichier temporaire qui contient les données post parsing
	 * @param String $level Niveau de topologie réseau
	 * @param String $parser_todo Résultat de la fonction getFlatFileName2todo
	 */
	public static function getCopyFilePath($level , $parser_todo,$hour) {
		return REP_PHYSIQUE_NIVEAU_0 . "upload/copy_". $level . "_" . $parser_todo. "_" . $hour.".sql";
	}
	
	/**
	 * Construit le format des éléments $todo.
	 * Ces éléments sont utilisés pour identifier les traitements associés aux lots de compteurs.
	 * @param String $family Famille du produit
	 * @param String $nms_table Ensemble de compteurs
	 */
	public static function getTodoString($family, $nms_table) {
		//nms_table insensible à la casse
		return $family . "_" . strtolower($nms_table);
	}
	
	/**
	 * 
	 * Permet d'indiquer la quantité de mémoire utilisée
	 */
	public static function traceMemoryUsage() {
		// afficher le max de memoire utilisee, pour info dans le file_demon
		if (function_exists("memory_get_peak_usage")) {
			$maxmem = intval(memory_get_peak_usage(true)/1024/1024) . " Mo";
		}
		else {
			$maxmem = "inconnu";
		}
		displayInDemon(__METHOD__ . " : memoire max utilisee == $maxmem<br>");
	}
	
	public static function isPerfTraceEnabled() {
		$retrieve_perf_logs_enabled=get_sys_global_parameters("retrieve_perf_logs_enabled",0);
		// est à FALSE si le parametre n'est pas défini ou est à 0.
		$retrieve_perf_logs_enabled=$retrieve_perf_logs_enabled==1?TRUE:FALSE; 
		return $retrieve_perf_logs_enabled;
	}
	
/**
	 * 
	 * Formate la date de référence provenant du fichier source
	 * @param array $match tableau contenant année, mois, jour, heure, minute
	 * @return string date formatée
	 */
	public static function formatDateFileTime($match){
		if(!empty($match)){
			$year 	= $match[1];
			$month	= $match[2];
			$day 	= $match[3];
			$hour 	= $match[4];
			$min 	= $match[5];
			// date de début de capture au format yyyy-mm-dd hh:mm:ss
			return $year."-".$month."-".$day." ".$hour.":".$min.":00";
		}
		else{
			return null;
		}
	}

	/**
	 * 
	 * fonction pour extraire un fichier bz2 in en out
	 * @param String $in
	 * @param String $out
	 */
	public static function bunzip2($in, $out)
	{
		if(!file_exists($in) || !is_readable($in))
			return false;
		if((!file_exists($out) && !is_writeable(dirname($out)) || (file_exists($out) && !is_writable($out))))
			return false;

		$in_file  = bzopen($in, "r");
		$out_file = fopen($out, "wb");

		while($buffer = bzread ($in_file)){
			fwrite ($out_file, $buffer);
		}
		bzclose ($in_file);
		fclose ($out_file);
		return true;
	}
	/**
	 * 
	 * Calcul l'ecart type des valeurs du tableau
	 * @param $array
	 */
	public static function standard_deviation($array){
		if(!is_array($array)) return FALSE;
		$average=array_sum($array)/count($array);
		$variance=0;
		foreach ($array as $value) {
			// exponentielle
			$variance+=pow($value - $average,2);
		}
		$variance=$variance/count($array);
		// racine carrée
		return sqrt($variance);
	}
	
	/**
	 * 
	 * Cette fonction convertit un tableau associatif en tableau html
	 * @param unknown_type $arr Tableau associatif 
	 * @param unknown_type $arr_name Titre du tableau
	 * @param unknown_type $bg_color Background color
	 */
	public static function display_array($arr,$arr_name='',$bg_color = '#9CF') {
		if(is_array($arr)){
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
		}else{
			displayInDemon("display_array - warning: un tableau est attendu comme argument de la methode");
		}
	}
	
	/**
	 * Permet d'afficher un tableaux associatif à plusieurs dimensions
	 * Ex: tab[line1][col1]=valcol1
	 * 				 [col2]=valcol2       =>  donnera le tableau htms suivant  => line1|valcol1|valcol2|valcol3
	 * 				 [col2]=valcol3												  line2|...
	 * 	      [line2][col1]=  ...																	
	 * @param unknown_type $arr tableau à afficher
	 * @param unknown_type $arr_header entete à afficher pour chacune des colonnes
	 */
	public static function display_tab($arr,$arr_header=array()) {
		if(is_array($arr)){
			$bg_color = '#9CF';
			$txt = '<table cellspacing="2" cellpadding="2" border="0" style="font:11px tahoma;">';
			if ($arr_name) $txt .= "<tr><th colspan='2' style='background:$bg_color;'>$arr_name</th></tr>";
			$txt.= "<tr>";
			foreach ($arr_header as $header) {
				$txt .="<td style='background:#9CF;'><strong>$header</strong></td>";
			}
			$txt.= "</tr>";
			foreach ($arr as $line => $values) {
				$txt.= "<tr>
						<td style='background:$bg_color;'><strong>$line</strong></td>\n";
				foreach ($values as $v) {
					$txt.="<td style='background:#DDD;'>$v</td>\n";
				}
				
				$txt.="</tr>";
	
			}
			$txt .= "\n</table>";
			
			return $txt;
		}else{
			displayInDemon("display_tab - warning: un tableau est attendu comme argument de la méthode");
		}

	}
	
	
	/**
	* Vérifie si au moins un fichier temporaire existe pour le niveau, le todo et une des heures
	* @param string $level niveau de base de la famille
	* @param string $todo entité concernée exemple family_nmstable
	* @param array $hours liste des heures collectées
	* @return boolean vrai si au moins un fichier trouvé
	*/
	public static function tempFileExistsForEntity($level,$todo,$hours){
		$exist=false;
		foreach($hours as $hour){
			$exist=($exist || file_exists(Tools::getCopyFilePath($level,$todo,$hour)));
		}
		return $exist;
	}
	
	
	/**
	 * Renvoie les nms_table définit pour un flat_file_name donné, exemple "BSS & BSS GPRS - xxxxx - RBS_PS_PCU_BTS|PACKET_CONTROL_UNIT"
	 * @param string $flat_file_name
	 * @return array $entities tableau contenant les entités trouvées, null sinon
	 */
	public static function getEntitiesForFlatFileName($flat_file_name){
		$entities=array();
		$pattern="/.*\s-\s(.*)$/";
		if(preg_match($pattern,$flat_file_name,$matches)){
			$entitieslist=$matches[1];
			$entities=explode("|",$entitieslist);	
		}
		return $entities;
	}
}
?>