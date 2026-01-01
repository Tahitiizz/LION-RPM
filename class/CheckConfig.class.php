<?php
/**
 * @cb5100@
 *
 * 17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les exécutables psql et php (PSQL_DIR et PHP_DIR)
 * 28/07/2010 OJT : Suppression de la constante PHP_DIR (bz17016)
 */
?>
<?php
/*
 *	24/09/2009 GHX
 *		- Correction du BZ 11728 [CB 5.0] le test d'OOo sur la page de login fait planter OO
 *
 *	09/10/2009 BBX
 *		- Suppression du test PDF : le test WORD est suffisat et permet d'économiser du temps.
 *	01/12/2009 GHX
 *		- Correction du BZ 12979 [CB 5.0][Test Open Office] problème de lenteur connexion à cause de OOo
 *			-> Suppression de l'anciène fonction et création des 2 nouvelles fonctions sur le test de OOo
 *  30/12/2010 17:19 SCT : Optimisation du code BZ 19673
 *      - déplacement de la méthode "get_adr_server" hors de la boucle "foreach"
 *      - appel d'une méthode modèle "ProductModel::getActiveProducts()" au lieu de "getProductInformations()"
 *      - amélioration de la gestion des erreurs dans le "catch"
 *      - suppression du test de la présence de la méthode "ssh2_connect" sur le produit slave
 */
?>
<?php
/**
 * Cette classe regroupe différences fonctions statiques qui vérifie si les serveurs est bien configurés, que certains modules sont bien installés...
 *
 * @author GHX
 * @version CB 5.0.0.10
 */
class CheckConfig
{
	/**
	 * Vérifie la présence du module SSH et s'il fonctionne. Si c'est bon, on retourne TRUE sinon un message d'erreur
     * 26/01/2011 OJT : Utilisation de la nouvelle méthode SSHConnection::testConnection
	 *
	 * @author GHX
	 * @version CB 5.0.0.07
	 * @since CB 5.0.0.07
	 * @return mixed
	 */
	public static function SSH ()
	{
		// Récupération de tous les produits
        // 30/12/2010 16:40 SCT => appel d'une méthode modèle
        $allProducts = ProductModel::getActiveProducts();

		// Si la fonction ssh2_connect n'est pas présente c'est qu'on n'a pas le module SSH2 de PHP d'installé
		if ( !function_exists("ssh2_connect") )
		{
			return 'master'; //__T('G_E_SSH2_NOT_INSTALLED');
		}
		
		$errors       = '';
		$ipChecked    = array();
        // 30/12/2010 16:40 SCT => déplacement de l'appel de la méthode "get_adr_server" hors du foreach
        $serverAdress = get_adr_server();
		foreach ( $allProducts as $product )
		{
			// Si le produit est sur le même serveur on ne test pas SSH2 car on a déjà fait le teste avec la condition précédente
			if ( $product['sdp_ip_address'] == $serverAdress )
				continue;
			
			// Si le serveur a déjà été testé on passe au suivant
			if ( in_array($product['sdp_ip_address'], $ipChecked) )
				continue;
			
			$ipChecked[] = $product['sdp_ip_address'];
			
            	// 03/01/2011 SCT : annulation de la vérification de la présence des méthodes ssh2_connect sur les slave : pas de connexion slave vers master via SSH, c'est le master qui se connecte sur le slave
            	// 26/01/2011 OJT : REOPEN bz 19673, on utilise la nouvelle méthode SSHConnection::testConnection
           		if( !SSHConnection::testConnection( $product['sdp_ip_address'], $product['sdp_ssh_port'] ) ) {
                		$errors .= (empty($errors) ? '' : '|s|').str_replace('.', '', $product['sdp_ip_address']);
                		sys_log_ast('Critical', get_sys_global_parameters('system_name'), 'Setup Product', __T('G_E_SSH2_NOT_AVAILABLE_ON_REMOTE_SERVER',$product['sdp_ip_address']), "support_1", "");
	    		}
		}
		
		if ( empty($errors) )
			return true;
		else
			return $errors;
	} // End function SSH
	
	/**
	 * Vérifie la présence d'OpenOffice
	 *
	 * @author GHX
	 * @version CB 5.0.2.1
	 * @since CB 5.0.2.1
	 * @param string $host: IP sur lequelle on doit tester la présence d'OpenOffice
	 * @param string $sshUser : login ssh dans le cas d'un test sur un serveur distant (default null)
	 * @param string $sshPassword : mot de passe ssh dans le cas d'un test sur un serveur distant (default null)
	 * @param int $sshPort : port pour ssh (default 22)
	 * @return boolean
	 */
	public static function OpenOfficeInstalled ( $host, $sshUser = null, $sshPassword = null, $sshPort = 22 )
	{
		// Si on est en local
		if ( $host == get_adr_server() || $host == 'localhost' || $host == '127.0.0.1' )
		{
			$res = exec('whereis soffice | sed "s/soffice://"');
			if ( trim($res) == '' )
			{
				return false;
			}
		}
		else // Si c'est on est sur un serveur distant
		{
			try
			{
				$sshConnect = new SSHConnection($host, $sshUser, $sshPassword, $sshPort);
				
				$res = $sshConnect->exec('whereis soffice | sed "s/soffice://"');
				if ( trim($res[0]) == '' )
				{
					return false;
				}
			}
			catch ( Exception $e )
			{
				return false;
			}
		}
		
		return true;
	} // End function OpenOfficeInstalled
	
	/**
	 * Vérifie si OpenOffice fonctionne correctement
	 *
	 * @author GHX
	 * @version CB 5.0.2.1
	 * @since CB 5.0.2.1
	 * @param string $host: IP sur lequelle on doit tester la présence d'OpenOffice
	 * @param string $host: IP sur lequelle on doit tester la présence d'OpenOffice
	 * @param string $sshUser : login ssh dans le cas d'un test sur un serveur distant (default null)
	 * @param string $sshPassword : mot de passe ssh dans le cas d'un test sur un serveur distant (default null)
	 * @param int $sshPort : port pour ssh (default 22)
	 * @param string $directory : répertoire de l'application uniquement dans le cas d'un test sur un serveur distant (default null)
	 * @return boolean
	 */
	public static function OpenOfficeAvailable ( $host, $sshUser = null, $sshPassword = null, $sshPort = 22, $directory = null )
	{
		// Si on est en local
		if ( $host == get_adr_server() || $host == 'localhost' || $host == '127.0.0.1' )
		{
			include REP_PHYSIQUE_NIVEAU_0.'class/PHPOdf.class.php';
		
			$testOOo = new PHPOdf(REP_PHYSIQUE_NIVEAU_0.'upload/checkOOo.odt');
			$testOOo->addContent($testOOo->makeAsParagraph("test checkConfig"));
			$testOOo->save();
			
			/*
				2 : Test si on peut créer un fichier text au format OpenOffice (.odt)
			*/
			if ( !file_exists(REP_PHYSIQUE_NIVEAU_0.'upload/checkOOo.odt') )
			{
				return false;
			}
			else
			{
				/*
					3 : Test si on peut créer un fichier Word (.doc) avec OpenOffice (test de la macro)
				*/
				PHPOdf::AnyToDoc(REP_PHYSIQUE_NIVEAU_0.'upload/checkOOo.odt');
				if ( !file_exists(REP_PHYSIQUE_NIVEAU_0.'upload/checkOOo.doc') )
				{
					@unlink(REP_PHYSIQUE_NIVEAU_0.'upload/checkOOo.odt');
					return false;
				}
				
				@unlink(REP_PHYSIQUE_NIVEAU_0.'upload/checkOOo.doc');
			}
			@unlink(REP_PHYSIQUE_NIVEAU_0.'upload/checkOOo.odt');
		}
		else // Si c'est on est sur un serveur distant
		{
			try
			{
				$sshConnect = new SSHConnection($host, $sshUser, $sshPassword, $sshPort);

				// Test de la création d'un document OpenOffice sur le serveur distants
				$cmdCreateFileOOo = 'php -r \'include "/home/'.$directory.'/class/PHPOdf.class.php";$testOOo = new PHPOdf("/home/'.$directory.'/upload/checkOOo.odt");$testOOo->addContent($testOOo->makeAsParagraph("test checkConfig"));$testOOo->save();\'';
				$sshConnect->exec($cmdCreateFileOOo);
				if ( !$sshConnect->fileExists('/home/'.$directory.'/upload/checkOOo.odt') )
				{
					return false;
				}
				else
				{
					// Test de la création d'un document Wordt sur le serveur distant
					$cmdCreateFileWord = 'php -r \'include "/home/'.$directory.'/class/PHPOdf.class.php";PHPOdf::AnyToDoc("/home/'.$directory.'/upload/checkOOo.odt");\'';
					$sshConnect->exec($cmdCreateFileWord);
					if ( !$sshConnect->fileExists('/home/'.$directory.'/upload/checkOOo.doc') )
					{
						// Suppression des fichiers créés
						$sshConnect->exec('rm -f /home/'.$directory.'/upload/checkOOo.odt');
						return false;
					}
				}
				// Suppression des fichiers créés
				$sshConnect->exec('rm -f /home/'.$directory.'/upload/checkOOo.doc');
				$sshConnect->exec('rm -f /home/'.$directory.'/upload/checkOOo.odt');
			}
			catch ( Exception $e )
			{
				return false;
			}
		}
		
		return true;
	} // End function OpenOfficeAvailable
	
} // End class  CheckConfig
?>