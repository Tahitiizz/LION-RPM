<?
/*
 * 13/09/2011 MMT DE PAAL1 utilisation methode session_has_timedout pour factorisation code
 * 28/11/2011 NSE bz 24845 : séparation des cas de figure, si session en cours no time out : pas d'affichage de message Time out.
*/
?>
<?
	/*
	* Permet de vérifier la validité d'une session utilisateur et de mettre à  jour le champ durée.
	*/
	include_once(REP_PHYSIQUE_NIVEAU_0 . "/php/edw_function_session.php");
	include_once(REP_PHYSIQUE_NIVEAU_0 . "/class/session_lister.class.php");

        // implémentation du Logout automatique du Portail
        // 03/12/2012 BBX
        // BZ 29463 : including CAS.php
        include_once REP_PHYSIQUE_NIVEAU_0.'/api/paa/lib/CAS.php';
        include_once REP_PHYSIQUE_NIVEAU_0.'/api/paa/PAAAuthenticationService.php';
        $paaConfigFile = REP_PHYSIQUE_NIVEAU_0.'/api/paa/conf/PAA.inc';
        $PAAAuthentication = PAAAuthenticationService::getAuthenticationService($paaConfigFile);

        // si l'authentification de l'utilisateur échoue, on se délogue
        if(!$PAAAuthentication->validateAuthentication()){
            global $PHPCAS_CLIENT;
            if (!is_object($PHPCAS_CLIENT)) {
                phpCAS::client(CAS_VERSION_2_0, CAS_SERVER, CAS_PORT, CAS_URI, false);
            }
            $PAAAuthentication->logout();
            exit;
        }
        
	$session = session_id(); // identifiant de la session courrante.
	//echo "identifiant de la session courrante : ".$session."<br>";


	// On vérifie si la session courrante pour cet utilisateur est égale à la session enregistrée.
	//13/09/2011 MMT DE PAAL1 utilisation methode session_has_timedout pour factorisation code
        // 28/11/2011 NSE bz 24845 : séparation des cas de figure, si session en cours no time out : pas d'affichage de message Time out.
	if(empty($id_user) || $session != get_user_session_id($id_user)){
                if(!empty($id_user) && !session_has_timedout($id_user)){
                    // le temps d'inactivité n'est pas dépassé, donc le user n'a pas le droit de se connecter.
                    $url = $niveau0."index.php?error=used";
                    echo '
                            <script language="JavaScript">
                                    window.location = "'.$url.'";
                            </script>';
                    exit;
                }
		elseif(empty($id_user)){	
			$msg_erreur = "Inactivity : timeout.";
			$url = $niveau0."index.php?logout=ok&erreur_session=".$msg_erreur;
			//echo $url; exit;
			 echo '
				<script language="JavaScript">
					window.location = "'.$url.'";
				</script>';
			//header("location:".$niveau0."index.php?msg_erreur=$msg_erreur");
			exit;
			//echo $msg_erreur."<br> tps inactivité : ".$difference."<".$session_time;
			//echo $msg_erreur;
		} else { 
			// Le temps d'inactivité est dépassé, on remplace donc l'id session.
			//echo "tmp d'inactivité dépassé connection OK";
			update_user_id_session($id_user, $session);
			update_user_last_connection($id_user);
		}
	} else {
		// C'est bien l'utilisateur courrant qui est connecté.
		//echo "Utilisateur courrant, continuer...";
		update_user_last_connection($id_user);
	}


?>
