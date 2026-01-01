<?php/**
 * 
 *  CB 5.3.1
 * 
 * 07/05/2013 : ajout du caractère de séparation tabulation
 * 22/05/2013 : T&A Optimizations
 * 22/05/2013 : WebService Topology
 */
/**
 * 
 *  CB 5.2
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 */
?><?
/*
*	@cb50000@
*
*	14/11/2007 - Copyright Astellia
*
*	Composant de base version cb_5.0.0.00
*
*	-maj 01/07/2009 - MPR : Correction du bug 10345 : Ajout d'un message dans le tracelog
*	-maj 24/07/2009 - MPR : Correction du bug  10734 : On check que le fichier possède bien les droits 777 
*	- maj 02/09/2009 - MPR : Correction du bug 11226 : Contrôle sur l'existence du fichier avant la copie dans le répertoire upload/
*
*	14/09/2009 GHX
*		- Modification de l'upload topo pour le faire fonctionner en mode Corporate
*	03/11/2009 GHX
*		- Le produit Mixed KPI doit avoir le même mode de fonctionnement qu'un Corporate
*			-> Ajout d'une condition pour entre le même mode
*			-> Modification de 2 expressions régulières pour faire fonctionner correctement le cas en mode Mixed KPI
*	30/11/2009 GHX
*		- Reprise des modifs de RBL sur la parallélisation des process
*	02/03/2010 NSE bz 10734 
*		on remplace le test is_writable() par le retour de la commande pour vérifier si le chmod a fonctionné
*	15:27 08/03/2010 MPR
		- Correction du BZ 13251
*	09/03/2010 - MPR 
		- Correction du BZ 14552 - Ajout des " pour que la commande passe
*	31/03/2010 NSE
*		bz 14909 : utilisation de la valeur définie dans sys_global_parameters pour $sendmail dans l'appel à uploadFile
*	08/04/2010 NSE bz 10734 : 
*		- on teste le retour de la commande chmod pour prévenir l'utilisateur en cas de problème y compris en mixed kpi et corporate
 *  22/09/2010 NSE bz 18117 : initialisation du mode de connexion actif/passif
 *  10/02/2011 MMT  bz 20608 : mode passif ne marche pas (regression avec correction du bug 18117)
 *  28/02/2011 NSE bz 17516 : conservation des coordonnées des fichiers de topo lors de l'import dans un Mixed Kpi
 * 17/05/2011 NSE DE Topology characters replacement : appel de la fonction de remplacement
 * 19/05/2011 NSE DE Topology characters replacement : arrêt du process de Retrieve
*/
?>
<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	- maj 21/03/2008 - Maxime : On récupère le label du module en base pour le Tracelog
*/
?>
<?
/*
*	@cb30000@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*	- maj 12:04 03/10/2007 Gwenael : on met automatiquement le fichier dans le répertoire upload
*	- maj 20/09/2007 Maxime - On remplace les espaces par des _ dans le nom du fichier.
*	- maj 15:21 21/08/2007 Gwénaël : l'appel au constructeur de la classe Topology ne se fait plus dans le if mais avant sinon plantage dans le else lors de l'appel à la fonction demon
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
*	@cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
*
*	- maj 27 11 2006 christophe, coorection faute anglais occured > occurred
*/
?>
<?
/*
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?

/*
 * Permet de mettre a jour automatiquement une topologie
  * Ce mode de fonctionnement est valable seulement pour la famille principale
  *
  */

include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once(dirname(__FILE__)."/../class/api/UploadFileLib.php");
include_once(dirname(__FILE__)."/../class/api/TrendingAggregationApi.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/database_connection.php");
// include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/postgres_functions.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/libMail.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyLib.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/Topology.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyCheck.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyAddElements.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyCorrect.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/topology/TopologyChanges.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/SSHConnection.class.php");

// 14:10 30/11/2009 GHX
// Reprise de la modif de RBL sur la parallélision des process
// 22/03/2013	FRR1	DE TA Optim - Retrieve a blanc
//sys_log_ast("Info", get_sys_global_parameters("system_name"), 	
//					__T('A_TRACELOG_MODULE_LABEL_COLLECT'), 
//					__T('A_FLAT_FILE_UPLOAD_ALARM_START_RETRIEVE_PROCESS'), "support_1", "");

// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$_db = Database::getConnection();
$auto=get_sys_global_parameters("topology_auto");
$app=get_sys_global_parameters("product_name");

//mise a jour automatique
if($auto==1) 
{
	// Si c'est un CORPORATE
	// 13:47 03/11/2009 GHX
	// Ajout de la condition du cas d'un produit Mixed KPI
	if ( CorporateModel::isCorporate() || MixedKpiModel::isMixedKpi() )
	{
		$dir = trim(get_sys_global_parameters("topology_file_location"));
		if ( substr($dir, -1) != '/' ) $dir .= '/';
		
		try
		{
			// On vérifie que le paramètre est bien un répertoire
			if ( !is_dir($dir) )
				throw new UnexpectedValueException('');
			// On vérifie que le répertoire a bien les droits d'écriture
			if ( !is_writable($dir) )
				throw new Exception(__T('A_E_UPLOAD_TOPO_AUTO_CORPORATE_DIRECTORY_NO_RIGHT_TO_WRITE', $dir));
			
			// On récupère les fichiers de topo de chaques affiliates
			$listFiles = getFilesTopologyForEveryAffiliates($dir);
			
			if ( count($listFiles) > 0 )
			{
				// Parcourt les fichiers récupérés
				foreach ($listFiles as $file)
				{
					$nbLines = exec('cat "'.$file.'" | wc -l');
					if ( $nbLines > 1 )
					{
                                            uploadFile($file, 1, false); // Upload le fichier
					}
				}
			}
		}
		catch ( UnexpectedValueException $e )
		{
			displayInDemon(__T('A_E_UPLOAD_TOPO_AUTO_CORPORATE_DIRECTORY_NOT_EXISTS', $dir), 'alert');
                        // 06/12/2011 BBX
                        // BZ 24854 : Ajout des erreurs catchées au tracelog
                        $_module = __T('A_TRACELOG_MODULE_LABEL_TOPO_AUTO');
                        sys_log_ast("Critical",$app,$_module,__T('A_E_UPLOAD_TOPO_AUTO_CORPORATE_DIRECTORY_NOT_EXISTS', $dir),'support_1','');
		}
		catch ( Exception $e )
		{
			displayInDemon($e->getMessage(), 'alert');
                        // 06/12/2011 BBX
                        // BZ 24854 : Ajout des erreurs catchées au tracelog
                        $_module = __T('A_TRACELOG_MODULE_LABEL_TOPO_AUTO');
                        sys_log_ast("Critical",$app,$_module,$e->getMessage(),'support_1','');
		}
	}
	else // Upload standart
	{
            //on recupere les infos sur le fichier de topologie
            $topo_file= trim( get_sys_global_parameters("topology_file_location") );

            // 22/11/2012 NSE bz 30051 : traitement du répertoire (intégration de plusieurs ficheirs de topo)
            if (is_dir($topo_file)) {
                $directory = $topo_file ;
                if (substr($topo_file, -1) != '/')
                    $directory .= '/';

                // utilisation du répertoire local comme racine si un chemin relatif nous est donné
                if(substr($directory, 0, 1) != "/" && substr($directory, 0, 1) != "\\")
                    $directory = "/".$directory;

                // Récupère la liste des fichiers de $directory
                exec("ls -l \"$directory\"", $buff);
                // Parcourt la liste de tous les fichiers trouvés dans ce répertoire
                foreach ($buff as $dirline) {
                    if (substr($dirline, 0, 5) == 'total')
                        continue;

                    if (ereg("([-d])[rwxst-]{9}.* ([0-9]*) ([a-zA-Z]+) ([0-9: ]*[0-9]) (.+)", $dirline, $regs)) {
                        // Si c'est un dossier on passe à l'enregistrement suivant
                        if ($regs[1] == 'd')
                            continue;
                        $filename = $regs[5];
                    }

                    // Si c'est le fichier "." ou ".." on passe au suivant
                    if ($filename == '.' || $filename == '..')
                        continue;

                    // On regarde si le nom du fichier comporte bien le template voulu qui permet de savoir si c'est un fichier de topo ou non
                    if (!ereg("topology_", $filename)) 
                        continue;
                   
                    // Change les droits du fichiers
                    // 08/04/2010 NSE bz 10734 : on teste le retour de la commande chmod pour prévenir l'utilisateur en cas de problème
                    // Si elle échoue, on le signale (pb de droits), sinon, on poursuit
                    $cmd = "chmod 777 " . $directory . $filename;
                    system($cmd, &$statutretour);
                    if ($statutretour != 0) {
                        $message = "<u>No right to write:</u> $topo_file";
                        // 26/11/2012 NSE bz 30051 (reopen) : suprression du message dans le demon topo
                        sys_log_ast("Critical", $app, $_module, $message, "support_1", "");
                        displayInDemon($message, 'alert');
                    } else {
                        $listFiles[] = $directory . $filename;
                    }
                }
                    
                if (count($listFiles) > 0) {
                    // Parcourt les fichiers récupérés
                    foreach ($listFiles as $file) {
                        $nbLines = exec('cat "' . $file . '" | wc -l');
                        if ($nbLines > 1) {
                            uploadFile($file, 1, get_sys_global_parameters("automatic_email_activation",true)); // Upload le fichier
                        }
                    }
                }
            }
            else{
                $family=get_main_family();//toujours famille principale en mode auto
                $type_maj = 1;
                if( get_axe3($family) )
                        $type_maj = 2;

                // 06/12/2011 BBX
                // BZ 24854 : Ajout des erreurs catchées au tracelog
                try {
                        // 31/03/2010 NSE bz 14909 : utilisation de la valeur définie dans sys_global_parameters pour $sendmail
                        uploadFile($topo_file, $type_maj, get_sys_global_parameters("automatic_email_activation",true));
                }
                catch ( Exception $e )
                {
                        displayInDemon($e->getMessage(), 'alert');
                        $_module = __T('A_TRACELOG_MODULE_LABEL_TOPO_AUTO');
                        sys_log_ast("Critical",$app,$_module,$e->getMessage(),'support_1','');
                }
            }
        }   
}
else
{
	$topo=new Topology();
	// Initialisation des paramètres
	$topo->setRepNiveau0(REP_PHYSIQUE_NIVEAU_0);
	$topo->setDbConnection($database_connection);
	// 11:27 14/09/2009 GHX
	// Message en anglais dans le démon
        $topo->demon("Automatic Upload Topology Disabled<br/>");
	displayInDemon("<div class='errorMsg'>Automatic Upload Topology Disabled<br/>");
}

//CB 5.3.1 WebService Topology
$repAsm = REP_PHYSIQUE_NIVEAU_0 . "topology/asm/";
exec("ls " . $repAsm, $files);
foreach ( $files as $file )
{
    if(!is_dir($repAsm . $file)){
        $row = selectFile($file, array(uploadFileInterface::sWaitingForIntegration));
        updateState($row['id_file'], uploadFileInterface::sIntegrationInProgress);
        uploadFile($repAsm . $file, 1, false);
    }
}


/**
 * Upload un fichier de topologie
 *
 *	14/09/2009 GHX
 *		- Ajout de la fonction pour les dev Corporate
 *			-> Le code de la fonction est un copié/collé de ce fichier pour l'upload avec 2-3 petites modifications
 *
 * @author GHX
 * @since CB 5.0.0.09
 * @version CB 5.0.0.09
 * @param string $topo_file chemin complete vers le fichier de topologie
 * @param int $type_maj type de mise à jour
 * @param boolean $sendMail TRUE si on doit envoyer un mail après avoir charger le ficheir
 */
function uploadFile ( $topo_file, $type_maj, $sendMail )
{
	global $database_connection, $app, $_db;

	Topology::$errors = array();
	Topology::$queries = array();
	
	$topo=new Topology();
	// Initialisation des paramètres
	$topo->setRepNiveau0(REP_PHYSIQUE_NIVEAU_0);
	$topo->setDbConnection($database_connection);

	$delimiter=";";//fixe
	$path_infos=pathinfo($topo_file);
        
	// 21/03/2008 - Maxime : On récupère le label du module en base pour le Tracelog
	$_module = __T('A_TRACELOG_MODULE_LABEL_TOPO_AUTO');
	
	$topo_file_path=$path_infos['dirname']."/";

	// maj 20/09/2007 Maxime - On remplace les espaces par des _ dans le nom du fichier.
	$topo_file_name_tmp=$path_infos['basename'];
	$topo_file_name = str_replace(' ', '_', $topo_file_name_tmp);
	
	// On réinitialise la variable topo_file
	// modif 12:04 03/10/2007 Gwenael
		// on met automatique le fichier dans le répertoire upload
	$topo_file = REP_PHYSIQUE_NIVEAU_0.'upload/'.$topo_file_name;

	// 24/07/2009 MPR - Correction du bug 10736 : Topology file location is empty "affichage d'un message d'erreur dans le démon et dans le tracelog" 
	if( $topo_file_name == "" )
	{
		$topo->demon("No topology file specified for an automatic update<br/>");
		sys_log_ast("Critical",$app,$_module,"No topology file specified for an automatic update",'support_1','');
		DisplayInDemon("<div class='errorMsg'>No topology file specified for an automatic update<br/></div>");
	}
	else
	{
		// On renomme le fichier en remplaçants les espaces par des _
		// $cmd = "mv -f $topo_file_path/'$topo_file_name_tmp' ".REP_PHYSIQUE_NIVEAU_0.$topo_file_name;
		if( file_exists( $topo_file_path.$topo_file_name_tmp ) ){
		
			// maj 09/03/2010 - MPR : Correction du BZ 14552 - Ajout des " pour que la commande passe
			//Bug 34115 - [REC][CB 5.3.1.01][Webservice]UploadFileRequest with right parameters is error for the special character in filename
                        //$cmd = "mv -f \"".$topo_file_path.$topo_file_name_tmp."\" ".$topo_file;
                        $cmd = "mv -f \"".$topo_file_path.$topo_file_name_tmp."\" "."\"".$topo_file."\"";
			exec($cmd);

			$topo_file_prefix=$topo_file_name;
			$topo_file_suffix="";
			if(isset($path_infos['extension']))
			{
				$topo_file_prefix=substr($topo_file_name,0,-(strlen($path_infos['extension']) + 1));//nom sans l'ext
				$topo_file_suffix=$path_infos['extension'];//extension
			}

			
			$date=date("Ymd");

			// Correction du bug 11226 : Contrôle sur l'existence du fichier avant la copie dans le répertoire upload/
			if( file_exists( $topo_file ) ){	// un fichier est present

				//CB 5.3.1 WebService Topology
                                $row = selectFile($topo_file_name_tmp, array(uploadFileInterface::sIntegrationInProgress));
                                if($row == "")
                                {                
                                    // 02/03/2010 NSE bz 10734 : on remplace le test is_writable() par le retour de la commande 
                                    // Si elle échoue, on le signale (pb de droits), sinon, on poursuit
                                    $cmd = "chmod 777 ".$topo_file;
                                    $resultatcmd = system($cmd, &$statutretour);
                                }
				
				// maj 24/07/2009 - MPR : Correction du bug  10734 : On check que le fichier possède bien les droits 777 
				// 02/03/2010 NSE bz 10734 : on teste le retour de la commande
				if( $row == "" && $statutretour!=0 ){
				
					$message = "<u>No right to write :</u> $topo_file";
							
					$topo->demon($message);
					
					// maj 01/07/2009 - MPR : Correction du bug 10345 : Ajout d'un message dans le tracelog
					sys_log_ast("Critical", $app, $_module, $message, "support_1", "");
					
					displayInDemon($message, 'alert');
					
				}else{
				
					// 02/03/2010 NSE bz 10734 : suppression du exec
					displayInDemon("<br /><u>File found :</u> $topo_file");
					
                                         // 07/05/2013 : ajout du caractère de séparation tabulation                             
                                        exec('sed -i -e "s/\\t/;/g" '.REP_PHYSIQUE_NIVEAU_0.'upload/'.$topo_file_name);
                                                                                
					// On initialise les paramètres de la topologie avant d'effectuer le traitement
					// 02/12/2008 GHX
					// ajout du produit
					$topo->setDbConnection($database_connection);
					$topo->setRepNiveau0(REP_PHYSIQUE_NIVEAU_0);
					$topo->setProduct(get_sys_global_parameters('module'));
					$topo->setTypeMaj($type_maj);
					$topo->setDelimiter($delimiter);
					$topo->setFile($topo_file_name);
					
					// maj 15:27 08/03/2010 - MPR : Correction du BZ 13251
					//								- Remplacement du mode auto en mode manuel afin de prendre en compte 
					//								  les différents cas d'upload tenant compte de la limite max_third_axis
					$topo->setMode('manuel');
                    // 17/05/2011 NSE DE Topology characters replacement
                    $errorMessage = $topo->charactersReplacement();
                    // on ne poursuit pas si une erreur a été rencontrée
                    if(empty($errorMessage))
                        $topo->load();//chargement de la topologie
                    
					$errors=$topo->getErrors();
					
					//UPDATE KO
					if( count( $errors )>0 ){
                                            
                                                //CB 5.3.1 WebService Topology
                                                $row = selectFile($topo_file_name_tmp, array(uploadFileInterface::sIntegrationInProgress));
                                                if($row != ""){
                                                    updateState($row['id_file'], uploadFileInterface::sIntegrationError);
                                                    sys_log_ast("Critical",$app, __T('A_TRACELOG_MODULE_LABEL_TOPO_WEBSERVICE'), "Errors occurred during the topology update [ $topo_file_name ]",'support_1','');
                                                }

						//Preparation du fichier de log et du fichier de topo a envoyer
						$uploaded_time = date('Y-m-d H:i:s');
						$time_tag = str_replace('-','',$uploaded_time);
						$time_tag = str_replace(':','',$time_tag);
						$time_tag = str_replace(' ','-',$time_tag);
						
						if ( $sendMail )
						{
							$topo_file_to_send=$topo_file_path.$topo_file_prefix."_$time_tag.txt";
							copy( $topo_file, $topo_file_to_send );

							$log_file_to_send = $topo->createFileLog ( $topo_file_to_send );
							sys_log_ast("Critical",$app,$_module,"Errors occurred during the topology update [ $topo_file_name ]",'support_1','');
							
							$files_to_delete=array( $topo_file_to_send, $log_file_to_send );
							
							displayInDemon("<h4>file log => $log_file_to_send</h4>");
						}
						displayInDemon("<b> > Errors occurred during the topology update</b><br />", 'alert');
						displayInDemon(implode("<br />", $errors).'<br />','alert');

                        // 19/05/2011 NSE DE Topology characters replacement : arrêt du process de Retrieve
                        if(!empty($errorMessage)){
                            
                            // pas besoin de supprimer les tables temporaires du retrieve car l'update de topo en est la première étape.
                            // on arrête le process en cours qui correspond au retrieve                           $_db->execute("BEGIN");
                            $requettes = array();
                            $requettes[0] = "UPDATE sys_definition_master SET on_off=0 WHERE master_id=10";
                            $requettes[1] = "UPDATE sys_family_track SET encours=FALSE, done=TRUE WHERE master_id=10";
                            $requettes[2] = "UPDATE sys_process_en_cours SET encours=0, done=1 WHERE process=10";
                            $requettes[3] = "DELETE * FROM sys_crontab SET WHERE master=10";
                            $requettes[4] = "UPDATE sys_step_track SET encours=FALSE, done=TRUE WHERE master_id=10";
                            foreach($requettes as $req)
                                $_db->execute($req);
                            $_db->execute("COMMIT");

                            // messages dans le tracelog et file demon
                            sys_log_ast("Critical",$app,$_module,addslashes($errorMessage),'support_1','');
 							sys_log_ast("Critical",$app,$_module,"Retrieve Process is stopped",'support_1','');
                            displayInDemon("<hr><h3>Retrieve Process is stopped</h3><hr>", 'alert');
                        }
                        
					//UPDATE OK
					} else {
					
						displayInDemon(" > update topology successfully<br />");
						
						if ( $sendMail )
						{
							//On archive les fichiers seulement lorsque la mise a jour s'est bien passee
                                                        //CB 5.3.1 WebService Topology
							//$archive_file=$topo->createArchive($topo_file,-1);
                                                        $archive_file = $topo->getFilenameInArchive();
							displayInDemon($archive_file);
							if($topo_file_suffix!="")
								$topo_file_to_send=str_replace('.'.$topo_file_suffix,".txt",$archive_file);
							else
								$topo_file_to_send=$archive_file.".txt";
							copy($archive_file,$topo_file_to_send);
							$log_file_to_send = $topo->createFileLog($topo_file_to_send);
						
							displayInDemon("file param => $topo_file_to_send");
							displayInDemon("file archive => $archive_file");
							displayInDemon("file log => $log_file_to_send");
							
							$topo->createLogArchive($log_file_to_send, $archive_file);
							sys_log_ast("Info",$app,$_module,"Update topology with file $topo_file_name successfully",'support_1','');
							$files_to_delete=array($topo_file_to_send);
						}
					}
					if ( $sendMail )
					{
                                                // 23/02/2012 NSE DE Astellia Portal Lot2
                                                // on tente de mettre à jour la liste de utilisateurs
                                                $ret = UserModel::updateLocalUsersList();
                                                if($ret == "no user on PAA" )
                                                    sys_log_ast("Critical",$app,$_module,"Error occured during Users synchronisation with PAA",'support_1','');
                                                
						$topo->demon("Fichier de topo a envoyer : $topo_file_to_send<br/>");
						$topo->demon("Fichier de log a envoyer : $log_file_to_send<br/>");
						// 22/11/2012 NSE bz 30051 : mail à Alerts Recipient
						displayInDemon("Topology File to send : $topo_file_to_send<br/>");
						displayInDemon("Topology File to send : $log_file_to_send<br/>");
						$topo->SendMailToAlertsRecipient( $topo_file_to_send, $log_file_to_send );
					}
				}
				
				
				
				if( count($files_to_delete) > 0 ){
					foreach($files_to_delete as $file){
						if($file !== ""){
							if( unlink($file) );
								displayInDemon("File $file is deleted");
						}
					}
					
				}
				
				

			} else {//si aucun fichier trouve alors on ne fait rien
				$topo->demon("No File : $topo_file<br/>");
				displayInDemon("<div class='errorMsg'>No File : $topo_file<br/></div>");
				sys_log_ast("Info",$app,$_module,"No topology file found [$topo_file_name]",'support_1','');
			}
		}
		else
		{
			$topo->demon("No Files : ".$topo_file_path.$topo_file_name_tmp."<br/>");
			displayInDemon("<div class='errorMsg'>No File : ".$topo_file_path.$topo_file_name_tmp."<br/></div>");
			sys_log_ast("Info",$app,$_module,"No topology file found [".$topo_file_path.$topo_file_name_tmp."]",'support_1','');
		}
	}
        
        // 06/12/2011 BBX
        // BZ 24854 : Ajout des erreurs de topo au tracelog
        $topo->tracelogErrors($_module);
        
} // End function uploadFile

/**
 * Supprime du fichier les colonnes dont le niveau d'agrégation n'existe pas en base
 *
 *	14/09/2009 GHX
 *		- Ajout de la fonction pour les dev Corporate
 *
 * @author GHX
 * @since CB 5.0.0.09
 * @version CB 5.0.0.09
 * @param string $filename chemin complet vers le fichier de topologie
 */
function deleteNaNotInDatabase ( $filename )
{
	global $_db;

	// Récupère les header du fichiers
	$handle = fopen($filename, "r");
	$header = fgetcsv($handle, 0, ';');
	fclose($handle);
	
	// Récupère le nom de la famille
	// 13:47 03/11/2009 GHX
	// Modification de l'expression régulière pour Mixed KPI
	ereg("auto_[^_]*_([^_]*)_.*topology", $filename, $regs);
	$family = $regs[1];
		
	// Création d'une requete SQL qui retourne les éléments en communs entre le header du fichier et les NA en bases
	$query = "
		SELECT agregation_label
		FROM  (
			SELECT agregation_label, family FROM sys_definition_network_agregation 
			UNION
			SELECT agregation_label || ' label', family FROM sys_definition_network_agregation 
			) AS t0
		WHERE agregation_label IN ('".implode("','", $header)."')
			AND family = '{$family}'
	";
	
	$results = $_db->execute($query);
	$cols = array();
	$colsName = array();
	// Met dans le table cols les identifiants des colonnes  à garder
	while ( $row = $_db->getQueryResults($results , 1) )
	{
		if ( in_array($row['agregation_label'], $header) )
		{
			$cols[] = array_search($row['agregation_label'],$header)+1;
			$colsName[] = $row['agregation_label'];
		}
	}
	
	// Récupère le niveau min
        // 29/07/2011 BBX
        // Prise en compte des NA 3eme axe
        // BZ 22869
	$naMin = $_db->getOne("
		SELECT
			agregation_label
		FROM 
			sys_definition_network_agregation
		WHERE
			agregation_level = 1
			AND family = '{$family}'
                        AND agregation_label IN ('".implode("','", $header)."')
	");
	
	// Récupère le niveau min dans la table de backup
        // 29/07/2011 BBX
        // Prise en compte des NA 3eme axe
        // BZ 22869
	$naMinBckp = $_db->getOne("
		SELECT
			agregation_label
		FROM 
			sys_definition_network_agregation_bckp
		WHERE
			agregation_level = 1
			AND family = '{$family}'
                        AND agregation_label IN ('".implode("','", $header)."')
	");
	
    // 28/02/2011 NSE bz 17516 : dans le cas d'un Mixed Kpi, il faut ajouter les coordonnées
    // la table sys_definition_network_agregation_bckp n'est définie qu'en Corporate, on ne teste donc pas dessus dans le cas d'un Mixed Kpi
	if ( ( MixedKpiModel::isMixedKpi() && in_array($naMin, $header) ) || ( in_array($naMinBckp, $header) && $naMinBckp == $naMin ) )
	{
        // 28/02/2011 NSE bz 17516 : modification x, y -> longitude, latitude
		foreach ( array('azimuth', 'longitude', 'latitude', 'on_off') as $ortherCol )
		{
			if ( in_array($ortherCol, $header) )
			{
				$cols[] = array_search($ortherCol,$header)+1;
				$colsName[] = $ortherCol;
			}
		}
	}

	// Si on a le mettre de colonnes à garder et l'entete, on ne fait rien c'est qu'il faut garder le fichier tel quel
	if ( count($cols) != count($header) )
	{
		if ( count($cols) > 0 )
		{
			array_multisort($cols, SORT_ASC, $colsName);
			$indexSort = array_search($naMin, $header)+1;
			
			// Garde uniquement les bonnes colonnes
			$cmd = 'cat "'.$filename.'" | sed "1d" | sort -t ";" -k '.$indexSort.','.$indexSort.' | cut -d";" -f'.implode(',', $cols).' | sed "/^;*$/d" | uniq  | awk \'BEGIN{print "'.implode(';', $colsName).'"}{print $0}\' > "'.$filename.'.tmp" && mv "'.$filename.'.tmp" "'.$filename.'"';
			exec($cmd);
		}
		else
		{
			$cmd = 'touch "'.$filename.'.tmp" && mv "'.$filename.'.tmp" "'.$filename.'"';
			exec($cmd);
		}
		// Change les droits du fichiers
		exec("chmod 777 \"".$filename."\"");
	}
} // End function deleteNaNotInDatabase

/**
 * Récupère les fichiers de topologies générés par les Data Export de chaque affiliate
 *
 *	14/09/2009 GHX
 *		- Ajout de la fonction pour les dev Corporate
 *
 * @author GHX
 * @since CB 5.0.0.09
 * @version CB 5.0.0.09
 * @param string $dir chemin vers le répertoire dans lequel on doit copier les fichiers de topo des affiliates
 */
function getFilesTopologyForEveryAffiliates ( $dir )
{
	$listFiles = array();
	global $app, $_db;
	
	$connections = $_db->execute("SELECT * FROM sys_definition_connection WHERE on_off = 1");
	if ( $_db->getNumRows() > 0 )
	{
		while ( $connec = $_db->getQueryResults($connections, 1) )
		{
			$prefix = trim($connec['connection_code_sonde']);
			$directory = trim($connec['connection_directory']);
			if ( substr($directory, -1) != '/' ) $directory .= '/';
			
			switch ($connec['connection_type'])
			{
				case 'local':
                    
                    // 07/11/2011 BBX
                    // BZ 21897 : utilisation du répertoire local comme racine si un chemin relatif nous est donné
                    if(substr($directory, 0, 1) != "/" && substr($directory, 0, 1) != "\\")
                        $directory = "/".$directory;
                    
					// Récupère la liste des fichiers de $directory
					exec("ls -l \"$directory\"", $buff);
					// Parcourt la liste de tous les fichiers trouver dans ce répertoire
					foreach ( $buff as $dirline )
					{
                        if(substr($dirline, 0, 5) == 'total') continue;
						
						if(ereg("([-d])[rwxst-]{9}.* ([0-9]*) ([a-zA-Z]+) ([0-9: ]*[0-9]) (.+)", $dirline, $regs))
						{
							// Si c'est un dossier on passe à l'enregistrement suivant
                            if ( $regs[1] == 'd' ) continue;
							$filename = $regs[5];
						}
						
						// Si c'est le fichier "." ou ".." on passe au suivant
                        if ( $filename == '.' || $filename == '..' ) continue;

						// On regarde si le nom du fichier comporte bien le template voulu qui permet de savoir si c'est un fichier de topo ou non
                        if ( !ereg("_topology_(first|third)_axis", $filename) ) continue;

						// Copie le fichier dans le répertoire
						if ( @copy($directory.$filename, $dir.$filename) )
						{
							// Change les droits du fichiers
							// 08/04/2010 NSE bz 10734 : on teste le retour de la commande chmod pour prévenir l'utilisateur en cas de problème
							// Si elle échoue, on le signale (pb de droits), sinon, on poursuit
							$cmd = "chmod 777 ".$dir.$filename;
							system($cmd, &$statutretour);							
							if( $statutretour!=0 ){
								$message = "<u>No right to write :</u> $topo_file";
								$topo->demon($message);
								sys_log_ast("Critical", $app, $_module, $message, "support_1", "");								
								displayInDemon($message, 'alert');								
							}else{
								// Supprime si nécessaire les colonnes en trop
								deleteNaNotInDatabase($dir.$filename);
								// Gestion du préfix
								managementPrefix($dir.$filename, $prefix);
								
								$listFiles[] = $dir.$filename;
								
								// 17:04 19/11/2009 GHX
								// Supprime le fichier
								@unlink($directory.$filename);
							}
						}
					}
					break;
					
				case 'remote':
					$server = trim($connec['connection_ip_address']);
					$user = trim($connec['connection_login']);
					$pass = trim($connec['connection_password']);

					// Connexion au serveur FTP
					$ftpConnect = @ftp_connect($server);
					if ( $ftpConnect ) // Si connexion OK
					{
						// On essaie de se loger
						if ( @ftp_login($ftpConnect, $user, $pass) )
						{
                            // 22/09/2010 NSE bz 18117 : initialisation du mode de connexion
                            // 29/09/2010 MPR : $connection remplacé par $connec['connection_mode']
                            // 10/02/2011 MMT : bz 20608 : mode passif ne marche pas
                            if($connec['connection_mode'] == '0')
                                    @ftp_pasv($ftpConnect, true);
                            else
                                    @ftp_pasv($ftpConnect, false);
                            
                            // 07/11/2011 BBX
                            // BZ 21897 : utilisation du répertoire local comme racine si un chemin relatif nous est donné
                            if(substr($directory, 0, 1) != "/" && substr($directory, 0, 1) != "\\")
                                $directory = trim(ftp_pwd($ftpConnect))."/".$directory; 

							// Récupère la liste des fichiers de $directory
							$buff = ftp_rawlist($ftpConnect, $directory);
                            // 10/02/2011 MMT : bz 20608 ajout de count pour tester si erreur au niveau ftp_rawlist
							// Parcourt la liste de tous les fichiers trouver dans ce répertoire
                            for ($i = 0;$i < count($buff);$i++)
							{
                                $dirline = $buff[$i];
                                if(substr($dirline, 0, 5) == 'total') continue;
								
								if(ereg("([-d])[rwxst-]{9}.* ([0-9]*) ([a-zA-Z]+) ([0-9: ]*[0-9]) (.+)", $dirline, $regs))
								{
									// Si c'est un dossier on passe à l'enregistrement suivant
                                    if ( $regs[1] == 'd' ) continue;
									$filename = $regs[5];
								}
								
								// Si c'est le fichier "." ou ".." on passe au suivant
                                if ( $filename == '.' || $filename == '..' ) continue;
								// On regarde si le nom du fichier comporte bien le template voulu qui permet de savoir si c'est un fichier de topo ou non
                                if ( !ereg("_topology_(first|third)_axis", $filename) ) continue;

								// Télécharge le fichier de topo en local
								if ( @ftp_get($ftpConnect, $dir.$filename, $directory.'/'.$filename, FTP_ASCII) )
								{
									// Change les droits du fichiers
									// 08/04/2010 NSE bz 10734 : on teste le retour de la commande chmod pour prévenir l'utilisateur en cas de problème
									// Si elle échoue, on le signale (pb de droits), sinon, on poursuit
									$cmd = "chmod 777 ".$dir.$filename;
									system($cmd, &$statutretour);							
                                    if( $statutretour!=0 )
                                    {
										$message = "<u>No right to write :</u> $topo_file";
										$topo->demon($message);
										sys_log_ast("Critical", $app, $_module, $message, "support_1", "");								
										displayInDemon($message, 'alert');								
                                    }
                                    else
                                    {
                                        deleteNaNotInDatabase($dir.$filename); // Supprime si nécessaire les colonnes en trop
                                        managementPrefix($dir.$filename, $prefix); // Gestion du préfix
										$listFiles[] = $dir.$filename;
										
                                        // 19/11/2009 GHX : Supprime le fichier
										@unlink($ftpConnect, $directory.'/'.$filename);
									}
								}
							}
						}
						else // Si connexion a échoué avec le login/password
						{
							displayInDemon(__T('A_E_PARSER_CONNECTION_FTP_USER_FAILED',$user.'@'.$server), 'alert');
						}
						// Fermeture de la connexion FTP (libération de ressource)
						ftp_close($ftpConnect);  
					}
					else // Si la connexion au serveur a échoué
					{
						displayInDemon(__T('A_E_PARSER_CONNECTION_FTP_FAILED',$server), 'alert');
					}
					break;

                case 'remote_ssh' :
                    $server = $connec['connection_ip_address'];
                    $user   = $connec['connection_login'];
                    $pass   = $connec['connection_password'];
                    $port   = $connec['connection_port'];

                    try
                    {
                        $res = new SSHConnection( $server, $user, $pass, $port );
                        
                        // 07/11/2011 BBX
                        // BZ 21897 : utilisation du répertoire local comme racine si un chemin relatif nous est donné
                        if(substr($directory, 0, 1) != "/" && substr($directory, 0, 1) != "\\") {
                            $localDir = $res->exec('pwd');
                            $directory = trim($localDir[0])."/".$directory;   
                        }

                        $list = $res->exec( 'LANG=en_EN.UTF-8;ls -l "'.$directory.'"' );
                        foreach ( $list as $entry )
                        {
                            if(substr($entry, 0, 5) == 'total') continue;
                            if(ereg("([-d])[rwxst-]{9}.* ([0-9]*) ([a-zA-Z]+) ([0-9: ]*[0-9]) (.+)", $entry, $regs))
                            {
                                // Si c'est un dossier on passe à l'enregistrement suivant
                                if ( $regs[1] == 'd' ) continue;
                                $filename = str_replace( "\n", "", $regs[5] );

							}
                            if ( $filename == '.' || $filename == '..' ) continue;

                            // On regarde si le nom du fichier comporte bien le template voulu qui permet de savoir si c'est un fichier de topo ou non
                            if ( !ereg("_topology_(first|third)_axis", $filename) ) continue;

                            // Copie le fichier dans le répertoire
                            $res->getFile( $directory.$filename, $dir.$filename );
                            if( !chmod( $dir.$filename, 0777 ) )
                            {
                                $message = "<u>No right to write :</u> $dir$filename";
                                $topo->demon( $message );
                                sys_log_ast( 'Critical', $app, $_module, "No right to write : $dir$filename}", "support_1", "");
                                displayInDemon( $message, 'alert');
							}
                            else
                            {
                                deleteNaNotInDatabase( $dir.$filename ); // Supprime si nécessaire les colonnes en trop
                                managementPrefix( $dir.$filename, $prefix ); // Gestion du préfix
                                $listFiles[] = $dir.$filename;
                                $res->exec( 'rm -f "'.$directory.$filename.'"' );
							}
                        }
                    }
                    catch( Exception $ex )
                    {
                        displayInDemon( $ex->getMessage(), 'alert' );
                    }
                    break;
            }
        }
    }
	return $listFiles;
} // End function getFilesTopologyForEveryAffiliates

/**
 * Gestion du préfixe pour les NE
 *
 *	14/09/2009 GHX
 *		- Ajout de la fonction pour les dev Corporate
 *
 * @author GHX
 * @since CB 5.0.0.09
 * @version CB 5.0.0.09
 * @param string $filename chemin complet vers le fichier de topologie
 * @param string $prefix valeur du prefix
 */
function managementPrefix ( $filename, $prefix )
{
	// Si le préfixe est vide on ne va pas plus loin
	if ( empty($prefix) )
		return;
	
	global $_db;

	// Récupère le header du fichiers
	$handle = fopen($filename, "r");
	$header = fgetcsv($handle, 0, ';');
	fclose($handle);
	
	// Si pas de header c'est que le fichier est vide
	if ( $header == false )
		return;
	
	$cols = array();
	
	// Récupère le nom de la famille
	// 13:47 03/11/2009 GHX
	// Modification de l'expression régulière pour Mixed KPI
	ereg("auto_[^_]*_([^_]*)_.*topology", $filename, $regs);
	$family = $regs[1];
	
	// Récupère la liste des éléments réseaux qui doivent être préfixé du code sonde
	$usePrefix = $_db->getAll("SELECT DISTINCT agregation_label FROM sys_definition_network_agregation WHERE use_prefix = 1 AND family = '{$family}'");
	
	// On boucle sur tous les NA ayant la possibilité d'avoir le préfixe
	foreach ( $usePrefix as $row )
	{
		// Si le niveau est dans le header
		if ( in_array($row['agregation_label'], $header) )
		{
			$index = array_search($row['agregation_label'],$header)+1;
			// Création de la condition awk pour ce niveau pour ajouter le prefixe
			$cols[] = 'if($'.$index.'!=""){$'.$index.'="'.$prefix.'_"$'.$index.'}';
		}
		// Si le label du NA est dans le header
		if ( in_array($row['agregation_label'].' label', $header) )
		{
			$index = array_search($row['agregation_label'].' label',$header)+1;
			// Création de la condition awk pour ce niveau pour ajouter le prefixe
			$cols[] = 'if($'.$index.'!=""){$'.$index.'="'.$prefix.'_"$'.$index.'}';
		}
	}
	
	// Si on a des NE qui doivent avoir le prefixe on exécute la commande awk
	if ( count($cols) > 0 )
	{
		$cmd = "awk 'BEGIN{FS=\";\";OFS=FS} NR>1 {".implode(';', $cols).";} {print $0}' \"{$filename}\" >> \"{$filename}.tmp\" && mv \"{$filename}.tmp\" \"{$filename}\"";
		exec($cmd);
		// Change les droits du fichiers
		exec("chmod 777 \"".$filename."\"");
	}
} // End function managementPrefix

?>