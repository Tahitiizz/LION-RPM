<?
/*
 * CB 5.2.0
 * 
 * 09/05/2012 NSE bz 27033 : transfert du logo sur le slave
 * 
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?
	// Permet d'uploader l'image du logo operateur.
	session_start();
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");
	// Fichier permettant le traitement de l'upload des images.
	include_once($repertoire_physique_niveau0 . "php/picture_function.php");
	include_once($repertoire_physique_niveau0 . "php/upload_function.php");
	include_once($repertoire_physique_niveau0 . "class/SSHConnection.class.php");
	global $niveau0;
?>
<html>
	<head>
		<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" />
	    <title>Upload is processing...</title>
	</head>
<body>
	<table cellpadding="0" cellspacing="0">
		<tr>
			<td>
				<?
					$fichiers_a_uploader = $_FILES;
					$chemin_upload = $repertoire_physique_niveau0 ."images/bandeau/";
					$bool = false;
					if(uploadImage ($chemin_upload, $fichiers_a_uploader)){
                                            $bool = true;
                                            $currentProduct = new ProductModel(ProductModel::getProductId());
                                            $currentProductInfos = $currentProduct->getValues();
                                            // 09/05/2012 NSE bz 27033 : transfert du logo sur le slave
                                            // si on est sur un multiproduit
                                            if(ProductModel::isProductPartOfMultiproduct($currentProductInfos['sdp_id'])){
                                                // on boucle sur tous les produits actifs (en dehors du master
                                                $products = ProductModel::getActiveProducts(true);
                                                foreach ($products as $productInfos){
                                                    $ficDest = '/home/'.$productInfos['sdp_directory']."/images/bandeau/".'logo_operateur.jpg';
                                                    // Si le produit est sur le même serveur
                                                    if ( $productInfos['sdp_ip_address'] == $currentProductInfos['sdp_ip_address'] )
                                                    {
                                                            $res = @copy($chemin_upload.'logo_operateur.jpg', $ficDest);
                                                            exec('chmod 0777 "'.$chemin_upload.'logo_operateur.jpg'.'"');
                                                    }
                                                    else // Le produit est sur un serveur distant
                                                    {
                                                        try
                                                        {
                                                            $ssh = new SSHConnection($productInfos['sdp_ip_address'], $productInfos['sdp_ssh_user'], $productInfos['sdp_ssh_password'], $productInfos['sdp_ssh_port']);
                                                            // envoie du logo sur le slave
                                                            $ssh->sendFile($chemin_upload.'logo_operateur.jpg',$ficDest);
                                                            $ssh->exec('chmod 0777 "'.$chemin_upload.'logo_operateur.jpg'.'"');
                                                        }
                                                        catch (Exception $e){
                                                             sys_log_ast("Info",get_sys_global_parameters( 'system_name' ),
                                                                        __T('A_TRACELOG_MODULE_LABEL_CHECK_PROCESS_EXECUTION_TIME'),
                                                                        "Unable to send new logo to ".($productInfos['sdp_on_off']==0?'incative ':'')."slave product ".$productInfos['sdp_label']. " with error: ".$e->getMessage(),
                                                                        'support_1','',$currentProductInfos['sdp_id']);
                                                        }
                                                    }
                                                }
                                            }
                                        }
				?>
			</td>
		</tr>
	</table>
	<?
		if($bool){
         // 30/09/2010 MMT bz 18166 force le rechargement de la page parente pour afficher
         // la nouvelle image meme si le navigateur n'a pas option "recharger la page à chaque visite"
			echo '
			<script language="JavaScript">
            window.opener.location.reload(true);
			self.close();
			</script>';
		} else {
			$url = $niveau0."logo_operateur_change.php?url=".$_SESSION["url_reload_2"];
			echo '<script language="JavaScript">  window.location = "'.$url.'"; </script>';
			//echo '<script language="JavaScript"> window.location = "'.$url.'"; </script>';
		}
	?>
</body>
</html>
