<?php
/**
 * 
 *  CB 5.3.1
 * 
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
*	@cb41000@
*
*	14/11/2007 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	02/12/2008 GHX
*		- modification du fichier dans le cadre du CB4.1.0.00
*
*	26/08/2009 MPR :
*		- Correction du bug 11226 : On supprime les balises  html
*/
?>
<?
/**
 *	Classe TopologyChanges 	- On affiche les changements effectués pendant le chargement de la Topology en mode manuel ou auto(retrieve)
 *						- Elle hérite de la classe TopologyLib
 *
 * @version 4.1.0.00
 * @package Topology
 */
include_once(dirname(__FILE__)."/../api/UploadFileLib.php");
include_once(dirname(__FILE__)."/../api/TrendingAggregationApi.class.php");
class TopologyChanges extends TopologyLib
{	 
	/**
	 * Constructeur
	 */
	public function __construct()
	{
	} // End function __construct()

	/**	
	 * Fonction qui archive un fichier de topologie
	 *
	 *	- modif 14:00 13/09/2007 Gwen ; ajout d'une condition sur id_user avant insertion en base.
	 * 			Car si l'id est vide, on ne peut pas mettre '' car Postgres 8.2 considère comme une chaine de caractètre vide alors que le champ est de type int
	 *
	 *	@access private
	 *	@param $file - contient le chemin complet jusqu'au fichier de topologie a archiver
	 *	@param $id_user - l'id utilisateur ayant effectue la mise a jour
	 *				 dans le cas d'une mise a jour automatique mettre -1
	 */
	function addFileToArchive ( $file, $id_user )
	{
		$uploaded_time = date('Y-m-d H:i:s');
		$time_tag = str_replace('-','',$uploaded_time);
		$time_tag = str_replace(':','',$time_tag);
		$time_tag = str_replace(' ','-',$time_tag);
		
		$path_parts=pathinfo($file);
		
		$archive_file_name=$path_parts['basename']."_$time_tag";
		
		if ( isset($path_parts['extension']) )
		{
			$file_prefix=substr($path_parts['basename'],0,-(strlen($path_parts['extension']) + 1));
			$archive_file_name=$file_prefix."_$time_tag.".$path_parts['extension'];
		}
		
		copy($file, self::$rep_niveau0.'file_archive/'.$archive_file_name);

		// modif 13:57 13/09/2007 Gwen
			// Ajout d'une condition sur l'id_user, s'il n'existe pas on mais 0 et pas '' (problème dû à la version postgres 8.2)
		// 18:08 29/01/2009 GHX
		// modification des requetes SQL pour mettre l'id_user entre cote 		
        if ( empty($id_user) )
		{
			//CB 5.3.1 WebService Topology
			$basenameFile = basename($file);
			$row = selectFile($basenameFile, array(uploadFileInterface::sIntegrationInProgress));
			if($row != ""){
				updateStateAndName($row['id_file'], $archive_file_name, $uploaded_time, uploadFileInterface::sIntegrationFinished);
				sys_log_ast("Info", get_sys_global_parameters("product_name"), __T('A_TRACELOG_MODULE_LABEL_TOPO_WEBSERVICE'), "Update topology with file $basenameFile successfully",'support_1','');
			}
			else{        
				$query="
					INSERT INTO sys_file_uploaded_archive (file_name,id_user,uploaded_time,file_type)
					VALUES ('".$archive_file_name."','0','".$uploaded_time."','topology')
				";
            }	
		}
		else
		{
			$query="
					INSERT INTO sys_file_uploaded_archive (file_name,id_user,uploaded_time,file_type)
					VALUES ('".$archive_file_name."', '".$id_user."','".$uploaded_time."','topology')
				";
		}
		
		// $this->demon($query,"ARCHIVAGE DU FICHIER $file -> ".self::$rep_niveau0.'file_archive/'.$archive_file_name);
		
		$this->sql($query);
		
		return self::$rep_niveau0.'file_archive/'.$archive_file_name;
	} // End function addFileToArchive
	
	/**
	 * Creation du fichier de log dans le meme repertoire que le fichier de topologie $file_topo
	 *	Le fichier de log porte comme extension ".log"
	 *	Contenu : >> la liste des erreurs rencontrees lors d'une mise a jour (controle de coherence)
	 *			OU
	 *		     >> la liste des changements effectues suite a une mise a jour
	 *
	 *	- modif 13:51 15/11/2007 Gwen : si l'ancienne valeur est vide on met la chaîne de caractère 'Null' à la place (affichage Changes Summary)
	 *	- modif 15:57 13/09/2007Gwen : ajout d'une condition pour savoir si $this->errors est un tableau sinon c'est qu'il n'y a pas eu d'erreurs.
	 *
	 * @param string Fichier chargé
	 *@return string logFile
	 */
	public function createLogFile ( $file_topo )
	{
		$path_parts     = pathinfo($file_topo);

		$log_file       = $path_parts['dirname'].'/'."log_".$path_parts['filename'].'.txt';
		$this->log_file = $log_file;
		$this->debug("Creation du fichier de log : ",$log_file);

		$file = fopen($log_file,"w");//creer le fichier si il n'existe pas
		
		// MPR 26/08/2009 :
		//		- Correction du bug 11226 : On supprime les balises  html
		$search = array("<br/>","<br>","<br />","<br >","&nbsp;");
		$replace = array("\r\n","\r\n","\r\n","\r\n"," ");
		
		if ( $file )
		{
			// modif 15:55 13/09/2007 Gwen
				//Ajout d'une condition pour savoir si errors est bien un tableau
				//( car si c'est un tableau c'est qu'il y a eu des erreurs dans le cas contraire non)
			if ( is_array(self::$errors) && count(self::$errors) > 0 )
			{
				
				$msg = str_replace($search,$replace,__T('A_UPLOAD_TOPO_EMAIL_TITLE_ERROR_SUMMARY'));
				fwrite($file,$msg."\r\n"."\r\n");
				
				foreach(self::$errors as $error) //Ecriture des erreurs rencontrees
				{
					$msg = str_replace($search,$replace,$error);
					fwrite($file," >> ".$msg."\r\n");
				}
			}
			elseif ( count(self::$changes) > 0 )
			{
				$msg = str_replace($search,$replace,__T('A_UPLOAD_TOPO_EMAIL_TITLE_CHANGE_SUMMARY'));
				fwrite($file,$msg."\r\n"."\r\n");
				//parametrage du tableau recensant les changements effectues
				$content=array();
				$columns_width=array(15,25,20,30,30);//nb de caracteres
				$column_count=count($columns_width);
				$separator='|';
				$tab_width=array_sum($columns_width)+$column_count*strlen($separator);//+5 a cause des '|'
				//header
				$content[0][0]= ' '.__T('A_UPLOAD_TOPOLOGY_TITLE_COL_NETWORK_LEVEL');
				$content[0][1]= ' '.__T('A_UPLOAD_TOPOLOGY_TITLE_COL_NETWORK_VALUE');
				$content[0][2]= ' '.__T('A_UPLOAD_TOPOLOGY_TITLE_COL_CHANGE_INFO');
				$content[0][3]= ' '.__T('A_UPLOAD_TOPOLOGY_TITLE_COL_OLD_VALUE');
				$content[0][4]= ' '.__T('A_UPLOAD_TOPOLOGY_TITLE_COL_NEW_VALUE');
				//initialisation du contenu du tableau
				for($i=1;$i<=count(self::$changes);$i++) //Ecriture des changements effectues
				{
					$change=self::$changes[$i-1];
					$content[$i][0]=' '.$change[0];
					$content[$i][1]=' '.$change[1];
					$content[$i][2]=' '.$change[2];
					// modif 13:51 15/11/2007 Gwen
						// Ajout de la condition pour savoir si l'ancienne valeur est vide ou pas
						// Si oui on met la chaîne de caractère 'Null'
					$content[$i][3]=' '. (empty($change[3]) ? 'Null' : $change[3]);
					$content[$i][4]=' '.$change[4];
				}
				$msg = str_replace($search,$replace,str_repeat('-',$tab_width));
				//ecriture dans le fichier
				fwrite($file,$msg."\r\n");
				for ( $i = 0; $i < count($content); $i++ )
				{
					if ( $i == 1 ){
						$msg = str_replace($search,$replace,str_repeat('=',$tab_width));
						fwrite($file,$msg."\r\n");
					}
					for ( $c = 0; $c < count($content[$i]); $c++ )
					{
						$change_detail=substr($content[$i][$c], 0, $columns_width[$c]-1) . str_repeat(' ', ( ($columns_width[$c] - strlen($content[$i][$c])) > 0 ? $columns_width[$c] - strlen($content[$i][$c]) : 1 ) ) . $separator;
						$msg = str_replace($search,$replace,$change_detail);
						fwrite($file,$change_detail);
					}
					
					fwrite($file,"\r\n");//ligne suivante
				}
			}
			elseif ( count(self::$changes) == 0 )
			{
				$msg = str_replace($search,$replace,__T('A_UPLOAD_TOPO_EMAIL_TITLE_CHANGE_SUMMARY'));
				fwrite($file,$msg."\r\n"."\r\n");
				$msg = str_replace($search,$replace,__T('A_UPLOAD_TOPO_EMAIL_CONTENT_NO_CHANGE'));
				fwrite($file,$msg);
			}
		}
		fclose($file);
		
		return $log_file;
	} // End function createLogFile
	
	/**
	 * Archive le fichier de log $log_file associe a l'archive de la topo passee en parametre
	 *
	 * @param string $log_file fichier de log
	 * @param string $archive_file fichier d'archive
	 */
	public function addLogFileToArchive ( $log_file, $archive_file )
	{
		$log_file_path     = pathinfo($log_file);
		$archive_file_path = pathinfo($archive_file);
		$log_file_name     = $log_file_path['basename'];
		$archive_file_name = $archive_file_path['basename'];

		$this->debug("Archivage du fichier de log : ", $log_file_name);
				
		if ( !file_exists($this->archive_dir.$log_file_name) )
			@copy($log_file, self::$rep_niveau0.$log_file_name);

		$query="
				INSERT INTO sys_file_uploaded_archive (file_name,id_user,uploaded_time,file_type)
					SELECT '$log_file_name',id_user,uploaded_time,file_type
					FROM sys_file_uploaded_archive
					WHERE file_name='$archive_file_name' 
						AND file_type='topology'";		
		$this->sql($query);
	} // End function addLogFileToArchive
	
	/**	
	 * Envoi un courrier a chaque administrateur du systeme
	 *	lorsqu'un probleme est survenu lors d'une mise a jour de topologie
	 *	Le fichier de topology ainsi que le fichier de log concerne sont attaches au message
	 *
	 * @param string $topo_file
	 * @param string $log_file
	 */
	function alertAdmin ( $topo_file, $log_file )
	{
		//selection des utilisateur dont le profile est de type admin (sauf astellia admin)
            // 20/02/2012 NSE DE Astellia Portal Lot2
            $admProfiles = UserModel::getAdmins(false);
                
		$app=get_sys_global_parameters("product_name");
		$reply=get_sys_global_parameters("mail_reply");
		foreach($admProfiles as $row)
		{
			$this->debug("Mail envoyé à : ",$row['user_mail']);
			$mail=new Mail();
			$mail->From($app."<$reply>");
			$mail->ReplyTo($reply);
			$mail->To($row['user_mail']);
			if(count(self::$errors)>0)
				$mail->Subject(__T('A_UPLOAD_TOPO_EMAIL_SUBJECT_ERROR', $app));
			else
				$mail->Subject(__T('A_UPLOAD_TOPO_EMAIL_SUBJECT_OK', $app));
			$mail->Body(__T('G_MAIL_AUTOGENERATED_DO_NOT_REPLY'));
			//Attention sur certain client les .txt sont affiches dans le corps du message

			$mail->Attach($log_file);//fichier log (on affiche d'abord les changements)
			$mail->Attach($topo_file);//fichier topologie
			$mail->Send();
		}
	} // End function alertAdmin
	
        
        /**	
	 * Envoi un courrier a Alerts Recipient
	 *	lorsqu'un probleme est survenu lors d'une mise a jour de topologie
	 *	Le fichier de topology ainsi que le fichier de log concerne sont attaches au message
	 *
	 * @param string $topo_file
	 * @param string $log_file
	 */
	function alertAlertsRecipient ( $topo_file, $log_file )
	{
		$mail_addr=get_sys_global_parameters("astellia_alert_recipient",'support@astellia.com');
		$app=get_sys_global_parameters("product_name");
		$reply=get_sys_global_parameters("mail_reply");
		
                $this->debug("Mail envoyé à : ",$mail_addr);
                $this->demon('<hr/>');
                $mail=new Mail();
                $mail->From($app."<$reply>");
                $mail->ReplyTo($reply);
                $mail->To($mail_addr);
                if(count(self::$errors)>0)
                        $mail->Subject(__T('A_UPLOAD_TOPO_EMAIL_SUBJECT_ERROR', $app));
                else
                        $mail->Subject(__T('A_UPLOAD_TOPO_EMAIL_SUBJECT_OK', $app));
                $mail->Body(__T('G_MAIL_AUTOGENERATED_DO_NOT_REPLY'));
                //Attention sur certain client les .txt sont affiches dans le corps du message

                $mail->Attach($log_file);//fichier log (on affiche d'abord les changements)
                $mail->Attach($topo_file);//fichier topologie
                $mail->Send();
		
	} // End function alertAlertsRecipient
        
} // End class  TopologyChanges
?>