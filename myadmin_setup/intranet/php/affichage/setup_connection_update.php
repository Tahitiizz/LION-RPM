<?php
/*
* 	@cb5.0.2.10@
*
*	01/02/2010 - MPR : Ajout du mode FTP (actif ou passif) pour les connexions FTP
*	18/02/2010 NSE bz 14251 : la première connexion est installée avec id_connection=1 et non null, ce qui fait que l'auto incrément assigne 1 à la première créée via l'IHM => 2 connexions avec le même id
*/
?>
<?php
/*
*	@cb5.0.1.01@
*
*	27/10/2009 BBX :
*		- Gestion du Corporate
*		- Ajout d'un exit en cas d'erreur. BZ 12289
*
*
*/
?>
<?
/*
*	@cb40200@
*
*	21/10/2008 - Copyright Acurio
*
*	Composant de base version cb_4.0.2.00
*
*	21/10/2008 BBX : gestion des types de ficheirs et utilisateurs par connexion.
*
*	05/02/2009 GHX
*		- modification des requêtes SQL pour mettre id_user & id_page entre cote au niveau des inserts  [REFONTE CONTEXTE]
*
*	07/07/2009 MPR
*		- Correction du bug 9704
*/
?>
<?
/*
*	@cb22014@
*
*	03/07/2007 - Copyright Acurio - JL
*
*	Composant de base version cb_3.0.0.00
*
*	Suppression des opérations de mise à jour pour les tables "SYS_DEFINITION_PARSER" et "SYS_DEFINITION_PARSER_REF"
*	De nouveau champ peuvent être ajoutés ou mis à jour : "connection_directory"  &  "connection_code_sonde"
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
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
<?php
/**
 * Supprime, crée, modifie les connections aux OMC / flat file
 */
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
// 27/10/2009 BBX : appel de la classe SSHConnection. BZ 12289.
include_once (REP_PHYSIQUE_NIVEAU_0.'class/SSHConnection.class.php');

$action		= $_GET['action'];
$id			= $_GET['id'];
$product	= $_GET['product'];
if (!$product)	$product = $_POST['product'];

$reinitializeFtpMode = $_GET['retinitialize'];


// Si le produit n'est pas précisé, on redirige sans rien toucher
if (!$product) {
	header("Location: setup_connection_index.php?no_loading=yes");
	exit;
}

// Connexion à la base de données
$database = DataBase::getConnection($product);

// Alarmes systemes
$alarm_activated = (get_sys_global_parameters('alarm_systems_activation','',$product) == 1);

// Source availability
$sa_activated = (get_sys_global_parameters('activation_source_availability','',$product) == 1);
$query = "
	SELECT id_flat_file, flat_file_name,
	data_collection_frequency, data_chunks, granularity
	FROM sys_definition_flat_file_lib
	WHERE on_off = 1
	ORDER BY flat_file_name";
$flat_files = $database->getAll($query);
unset($query);

/**
 * Met à jour les informations SA de la connection $id_connection. Prend les infos dans $_POST.
 * @param $index : index de la connexion dans la variable $_POST
 * @param $id_connection : identifiant de la connexion dans sys_definition_connection
 */
function updateSaInfo($index, $id_connection) {
	global $flat_files;
	global $database;
	$info = array();

	// selectionne toutes les entrées existante en base pour cette connexion
	$query = "
		SELECT sdsftpc_id_flat_file, sdsftpc_data_chunks
		FROM sys_definition_sa_file_type_per_connection
		WHERE sdsftpc_id_connection={$id_connection}";
	$tmp = $database->getAll($query);
	$sdsftpc = array();
	foreach ($tmp as $file) {
		$sdsftpc[$file['sdsftpc_id_flat_file']] = $file['sdsftpc_data_chunks'];
	}
	unset($tmp);

	// Parcours tous les types de fichiers
	foreach ($flat_files as $file) {
		// recherche si le fichier est attendu
		$id = "expected_file_{$index}_{$file['id_flat_file']}";
		$expected = (isset($_REQUEST[$id]) && $_REQUEST[$id] == $file['id_flat_file']) ? true : false;

		if ($expected) {
			// recherche le nombre de chunks
			if (isset($_REQUEST["chunk_{$index}_{$file['id_flat_file']}"])) {
				$chunk = $_REQUEST["chunk_{$index}_{$file['id_flat_file']}"];
			}
			else {
				$chunk = $file['data_chunks']; // valeur par defaut
			}
			// s'il y a deja une entrée en base on la met à jour si nécessaire
			if (array_key_exists($file['id_flat_file'], $sdsftpc)) {
				if ($chunk != $sdsftpc[$file['id_flat_file']]) {
					$query = "
						UPDATE sys_definition_sa_file_type_per_connection
						SET sdsftpc_data_chunks={$chunk}
						WHERE sdsftpc_id_flat_file={$file['id_flat_file']}
						AND sdsftpc_id_connection={$id_connection}";
					$database->execute($query);
				}
			}
			// Sinon on l'ajoute
			else {
				$query = "
					INSERT INTO sys_definition_sa_file_type_per_connection
					(sdsftpc_id_connection, sdsftpc_id_flat_file, sdsftpc_data_chunks)
					VALUES ({$id_connection}, {$file['id_flat_file']}, {$chunk})";
				$database->execute($query);

			}
		}
		elseif( $id_connection != "undefined" ) {

			// suppression si existant
			$query = "
				DELETE FROM sys_definition_sa_file_type_per_connection
				WHERE sdsftpc_id_flat_file={$file['id_flat_file']}
				AND sdsftpc_id_connection={$id_connection}";
			$database->execute($query);


		}
	}
}

switch ($action) { // $action est un parametre passe par l'URL d'appel
	CASE "delete": // supprime une connection
		// Recherche puis supprime la table daily correspondant à la connection
		// suprime la connection
		// $query = "DELETE FROM sys_definition_connection where (id_connection='$id') AND protected = 0";

                if( $id != null )
                {
                    $query = "DELETE FROM sys_definition_connection where id_connection='$id' ";
                    @$database->execute($query);

                    // ajout 21/10/2008 BBX : on supprime les entrées correspondant à cet id connection
                    $query = "DELETE FROM sys_definition_flat_file_per_connection WHERE sdffpc_id_connection = {$id}";
                    @$database->execute($query);
                    $query = "DELETE FROM sys_definition_users_per_connection WHERE sdupc_id_connection = {$id}";
                    @$database->execute($query);

                    // suppression des infos SA
                    // Correction du BZ 16160 - Suppression du paramétrage SA propre à la connexion supprimée
                    $query = "DELETE FROM sys_definition_sa_file_type_per_connection WHERE sdsftpc_id_connection = {$id}";
                    @$database->execute($query);

                    $query = "DELETE FROM sys_definition_sa_view WHERE sdsv_id_connection = {$id}";
                    @$database->execute($query);
                }
        break;

	CASE "save" : // sauvegarde les connections saisies
		//Nombre TOTAL de ligne retournées par le formulaire (y compris les nouvelle lignes)
		$nombre_total_connection = $_POST["row_table"];

            for ($i = 0;$i<=$nombre_total_connection;$i++)
            {
			//Récupération des tableaux de valeurs du formulaire
                // 01/02/2010 - MPR : Ajout de connection_mode pour enregistrer le mode FTP utilisé ou non
                // 02/03/2011 OJT : DE SFTP, gestion du numéro de port
                $id_connection         = $_POST["id_connection$i"];
                $connection_name       = $_POST["connection_name$i"];
                $ip_address            = $_POST["connection_ip_address$i"];
                $connection_login      = $_POST["connection_login$i"];
                $connection_password   = $_POST["connection_password$i"];
                $connection_type       = $_POST["connection_type$i"];
                $connection_mode       = $_POST["connection_mode$i"];
                $id_region             = $_POST["id_region$i"];
                $connection_code_sonde = $_POST["connection_code_sonde$i"];
                $on_off                = intval($_POST["on_off$i"]);
                $connection_directory  = $_POST["connection_directory$i"];
                $connection_port       = NULL;
			
				// 20/06/2012 ACS BZ 27405 Retrieve failed in case of baskslash present
				$connection_directory = str_replace('\\\\', '/', $connection_directory);
			
                if( isset( $_POST["connection_port$i"] ) ){
                    $connection_port = intval( $_POST["connection_port$i"] );
                }

				$selected_files = array();
				foreach ($flat_files as $file) {
					// recherche si le fichier est attendu
					$select_id = "selected_file_{$i}_{$file['id_flat_file']}";
					$expected = (isset($_REQUEST[$select_id]) && $_REQUEST[$select_id] == $file['id_flat_file']) ? true : false;
					if ($expected) {
						$selected_files[] = $file['id_flat_file'];
					}
				}
				unset($select_id);


				$selected_users	=  explode('|', $_POST["selected_users$i"]);
			
				//Vérifions qu'il ne s'agit pas du formulaire patron qui est vide
				if ($connection_name != "") {
					//Vérifions si c'est une nouvelle connection ou une mise à jour
					if ($id_connection == "") { // cela signifie que la connexion n'existe pas encore
						// maj 16:28 01/02/2010 - MPR : Ajout de connection_mode pour enregistrer le mode FTP utilisé ou non
						// 18/02/2010 NSE bz 14251 : la première connexion est installée avec id_connection=1 et non null, ce qui fait que l'auto incrément assigne 1 à la première créée via l'IHM
						// on cherche donc le dernier id inséré
						$query_last_id	= "SELECT MAX(id_connection) FROM sys_definition_connection ";
						$id_new_cnx	= array_pop($database->getRow($query_last_id))+1;
						// on l'incrémente et on l'insère pour la nouvelle connexion.
                        // 08/03/2011 OJT : Ajout de robustesse sur la chaine (caractère % pour sprintf)
                        // 06/02/2015 FGD bz 30338 : suppression des slashs en trop et encodage postgres du mot-de-passe
                        $query = "INSERT INTO sys_definition_connection
								   (id_connection,connection_name,connection_ip_address,connection_login,
								 	connection_password,connection_type, connection_mode, id_region,
                                    connection_code_sonde,connection_directory,on_off%s)
								  VALUES ($id_new_cnx,
										 '$connection_name',
										 '$ip_address',
                                         '".str_replace( '%', '%%', $connection_login )."',
                                         '".str_replace( '%', '%%', pg_escape_string(stripslashes($connection_password)))."',
										 '$connection_type',
										 ".( ($connection_mode == "" or $reinitializeFtpMode == "1") ? "null" : $connection_mode).",
                                         '".str_replace( '%', '%%', $id_region )."',
                                         '".str_replace( '%', '%%', $connection_code_sonde )."',
                                         '".str_replace( '%', '%%', $connection_directory )."',
                                         $on_off%s);";
                       	if( $connection_port !== NULL )
                        {
                           	$query = sprintf( $query, ',connection_port', ','.$connection_port );
                       	}
                        else
                       	{
                       	    $query = sprintf( $query, '', '' );
                       	}
						@$database->execute($query);
					
						// ajout 21/10/2008 BBX : Récupération de l'id connection inséré
						// 18/02/2010 NSE : l'id_connection est déjà connu. Suppression de la récupération.

						if ($alarm_activated) {
							// ajout 21/10/2008 BBX : Par sécurité, on supprime les entrées correspondant à cet id connection
							$query = "DELETE FROM sys_definition_flat_file_per_connection WHERE sdffpc_id_connection = {$id_new_cnx}";
							@$database->execute($query);
							$query = "DELETE FROM sys_definition_users_per_connection WHERE sdupc_id_connection = {$id_new_cnx}";
							@$database->execute($query);
					
							// ajout 21/10/2008 BBX : gestion des fichiers liés à cette connexion
							if (count($selected_files) > 0) {
								foreach ($selected_files as $id_file) {
									if (trim($id_file) != '') {
										$query = "INSERT INTO sys_definition_flat_file_per_connection (sdffpc_id_connection,sdffpc_id_flat_file)
												  VALUES ({$id_new_cnx},{$id_file})";
										@$database->execute($query);
									}
								}
							}
					
							// ajout 21/10/2008 BBX : gestion des utilisateurs liés à cette connexion
							if (count($selected_users) > 0) {
								foreach ($selected_users as $id_user_selected) {
									if (trim($id_user_selected) != '') {
										// 14:54 05/02/2009 GHX
										// Modification de la requete SQL pour mettre des valeurs entre cote
										$query = "INSERT INTO sys_definition_users_per_connection (sdupc_id_connection,sdupc_id_user)
											 	  VALUES ({$id_new_cnx},'{$id_user_selected}')";
										@$database->execute($query);
									}
								}
							}
						}
						// maj SA
						if ($sa_activated) {
							$sa_info = updateSaInfo($i, $id_new_cnx);
						}
					}
              	  	else {
						// maj 16:28 01/02/2010 - MPR : Ajout de connection_mode pour enregistrer le mode FTP utilisé ou non
						// 06/02/2015 FGD bz 30338 : suppression des slashs en trop et encodage postgres du mot-de-passe
						$query = "	UPDATE sys_definition_connection
									SET connection_name='$connection_name',
										connection_ip_address='$ip_address',
										connection_login='$connection_login',
										connection_password='".pg_escape_string(stripslashes($connection_password))."',
										connection_type='$connection_type',
										id_region='$id_region',
										on_off=$on_off,
										connection_code_sonde='$connection_code_sonde',
										connection_mode=".( ( $connection_mode == "" or $reinitializeFtpMode == 1 ) ? "null" : $connection_mode).",
                                        connection_directory='$connection_directory'";
                        if( $connection_port !== NULL ){
                            $query .= ', connection_port='.$connection_port;
                        }
                        $query .= " WHERE id_connection='$id_connection';";
						@$database->execute($query);
					
						if ($alarm_activated) {
							// ajout 21/10/2008 BBX : on supprime les entrées correspondant à cet id connection
							$query = "DELETE FROM sys_definition_flat_file_per_connection WHERE sdffpc_id_connection = {$id_connection}";
							@$database->execute($query);
							$query = "DELETE FROM sys_definition_users_per_connection WHERE sdupc_id_connection = {$id_connection}";

							@$database->execute($query);
					
							// ajout 21/10/2008 BBX : gestion des fichiers liés à cette connexion
							if (count($selected_files) > 0) {
								foreach ($selected_files as $id_file) {
									if (trim($id_file) != '') {
										$query = "INSERT INTO sys_definition_flat_file_per_connection (sdffpc_id_connection,sdffpc_id_flat_file)
												  VALUES ({$id_connection},{$id_file})";
										@$database->execute($query);
								
									}
								}
							}
					
							// ajout 21/10/2008 BBX : gestion des utilisateurs liés à cette connexion
							if (count($selected_users) > 0) {
								foreach ($selected_users as $id_user_selected) {
									if (trim($id_user_selected) != '') {
										// 14:54 05/02/2009 GHX
										// Modification de la requete SQL pour mettre des valeurs entre cote
										$query = "INSERT INTO sys_definition_users_per_connection (sdupc_id_connection,sdupc_id_user)
										VALUES ({$id_connection},'{$id_user_selected}')";
										@$database->execute($query);
									}
								}
							}
						}
						// maj SA
						if ($sa_activated) {
							$sa_info = updateSaInfo($i, $id_connection);
						}
					}
				}
			}
			break;
}

// 16/09/2009 BBX : si on est en mode Corporate, on retransmet la conf des Data Export aux affiliates
if(CorporateModel::isCorporate($product))
{
	if(!CorporateModel::sendDataExport($product)) 
	{
		// Si un affiliate plante, on redirige en précidant l'affiliate en cause
		$affiliateFailed = CorporateModel::$affiliateFailed;
		header("Location: setup_connection_index.php?no_loading=yes&product=$product&affiliate=$affiliateFailed");
		// 27/10/2009 BBX : ajout d'un exit afin de stopper ici en cas d'erreur. BZ 12289
		exit;
	}
}

// modif 21/10/2008 BBX : redirection PHP
header("Location: setup_connection_index.php?no_loading=yes&product=$product");
?>
