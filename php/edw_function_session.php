<?php
/*
	29/01/2009 GHX
		- modification des requêtes SQL qui fait appel au champ id_user de la table users pour mettre la valeur entre cote [REFONTE CONTEXTE]
   13/09/2011 MMT DE PAAL1
  		- ajout methode session_has_timedout pour factorisation code de index.php
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
	*	Retourne la variable $date au format  voulu.
	*/
	function dd($date) {
		return date("d/m/Y H:i:s",$date);
	}


	/*
	* fonction qui permet de mettre à jour la date / heure de connection d'un utilisateur d'identifiant id_user.
	*/
	function update_user_last_connection($id_user){

		global $database_connection;

		$date = date("j,m,Y@G:i:s");

		$query = " update users set last_connection = '$date' where id_user = '$id_user' ";
		pg_query($database_connection,$query);
	}

	/*
	* fonction qui permet de mettre à jour l'id session d'un utilisateur d'identifiant id_user.
	*/
	function update_user_id_session($id_user, $id_session){

		global $database_connection;

		$query = " update users set id_session = '$id_session' where id_user = '$id_user' ";
		pg_query($database_connection,$query);
	}

	/*
	* fonction qui retourne l'identifiant de la session pr un utilisateur donné id_user.
	*/
	function get_user_session_id($id_user){

		global $database_connection;

		$query = " select id_session from users where id_user= '$id_user' ";
		$result = pg_query($database_connection,$query);
		$result_array= pg_fetch_array($result, 0);
		return ($result_array["id_session"]);
	}


	/*
	* fonction qui retourne la valeur de la dernière connection du user id_user.
	*/
	function get_user_last_connection($id_user){

		global $database_connection;

		$query = " select last_connection from users where id_user= '$id_user' ";
		$result = pg_query($database_connection,$query);
		$result_array= pg_fetch_array($result, 0);
		return ($result_array["last_connection"]);
	}

	/*
	* Convertit $time  au format hh:mm:ss.
	*/
	function convert_time($time){
		if ($time < 60){
			return("00:" . $time . ":00");
		} else {
			$temp = $time / 60;
			$tab = explode(".", $temp);
			$h = $tab[0];
			if (count($tab) > 1){
				if (strlen($tab[1]) > 1){
					$m = substr($tab[1],0 , 2);
					$m = ($m / 100) * 60;
					$m = ceil($m);
				} else {
					$m = ($tab[1] / 10) * 60;
					$m = ceil($m);
				}
			} else {
				$m = "00";
			}

			if (strlen($h)==1) $h="0".$h;
		  	if (strlen($m)==1) $m="0".$m;
			$s = "00";
			return($h . ":" . $m . ":" . $s);
		}

	}

	/*
	* Heures au format (hh:mm:ss) la plus grande puis le plus petite
	*/
	function diff_time($t1 , $t2){
		  $tab = explode(":", $t1);
		  $tab2 = explode(":", $t2);

		  $h=$tab[0];
		  $m=$tab[1];
		  $s=$tab[2];
		  $h2=$tab2[0];
		  $m2=$tab2[1];
		  $s2=$tab2[2];

		  if ($h2>$h) $h=$h+24;
		  if ($m2>$m) {
		  	$m=$m+60;
		  	$h2++;
		  }
		  if ($s2>$s) {
		  	$s=$s+60;
		  	$m2++;
		  }

		  $ht=$h-$h2;
		  $mt=$m-$m2;
		  $st=$s-$s2;
		  if (strlen($ht)==1) $ht="0".$ht;
		  if (strlen($mt)==1) $mt="0".$mt;
		  if (strlen($st)==1) $st="0".$st;
		  return $ht.":".$mt.":".$st;
	}

	// convertit un format time h:m:s en minutes.
	// on néglige les secondes
	function convert_to_minutes($time){
		$time = explode(":",$time);
		return floor(($time[0]*60)+$time[1]);
	}

	/*
	* Retourne le nombre de jour entre deux dates
	*/
	function diff_date($jour , $mois , $an , $jour2 , $mois2 , $an2){
		$timestamp = mktime(0, 0, 0, $mois, $jour, $an);
		$timestamp2 = mktime(0, 0, 0, $mois2, $jour2, $an2);
		$diff = floor(($timestamp - $timestamp2) / (3600 * 24));
		return $diff;
	}

	/*
	*	Retourne true si la session passée en paramètre est enregistrée pour un utilisateur quelconque dans le BD.
	*/
	function session_register_check($id_session){
		global $database_connection;
		$query = " select id_session from users where id_session='$id_session' ";
		$result = pg_query($database_connection,$query);
		$result_nb = pg_num_rows($result);
		if($result_nb>0){
			return true;
		} else {
			return false;
		}
	}

	/*
	*	Permet d'effacer les champs last_connection et id_session de la BD pour l'utilisateur qui a la session id_session.
	*/
	function session_raz($id_session){
		global $database_connection;
		$query = " update users set last_connection='', id_session='' where id_session = '$id_session' ";
		pg_query($database_connection,$query);
	}


	// 13/09/2011 MMT DE PAAL1 - ajout methode pour factorisation code (deplacement de index.php)
	/*
	 * return true if session of given user has timedout
	 */
	function session_has_timedout($id_user){
		// on vérifie si le temps d'inactivité est dépasssé.
		$date = date("j,m,Y@G:i:s");
		$date = explode("@",$date);
		$jour = $date[0];
		$time = $date[1];

		$user_date = get_user_last_connection($id_user);
		$user_date = explode("@",$user_date);
		$jour_user = $user_date[0];
		$time_user = $user_date[1];

		$session_time = get_sys_global_parameters("session_time"); // durée d'inactivité max d'un utilisateur

		$difference =  diff_time($time, $time_user);
		$difference = convert_to_minutes($difference);

		return ($difference > $session_time);

	}


?>
