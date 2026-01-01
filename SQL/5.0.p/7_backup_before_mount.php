<?php
/*
*	22/03/2010 NSE bz 14790
*		- suppression de la première sauvegarde du contexte : correspond au contexte de base créé lors de l'installation du produit
*			on vérifie l'existence de la sauvegarde avant le premier contexte
*			on vérifie que la sauvegarde est bien "vide", ie qu'il n'y a pas de "users" de renseignés 
*			(si le patch est appliqué 2 fois, on risquerait de supprimer une bonne sauvegarde si le même contexte a été réappliqué après le premier passage du patch) 
*
*/
include_once dirname(__FILE__)."/../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0."php/database_connection.php");

$contextDir = REP_PHYSIQUE_NIVEAU_0.'upload/context/';

// Recherche du fichier à supprimer à partir des contextes installés
$query = "SELECT item_value FROM sys_versioning WHERE item = 'contexte' ORDER BY date asc LIMIT 1";
$result = pg_query($database_connection,$query);
list($fileContexte) = pg_fetch_row($result);
$backupContext = $contextDir.'backup_before_mount_context_'.$fileContexte;

if(file_exists($backupContext)){

	// on ne supprime pas le fichier de sauvegarde... à moins que...
	//$suppressionBackup = false;
	// on crée un répertoire pour vérifier le fichier de backup
	$worksDir = $contextDir.'ctx_verifbackup';
	if(is_dir($worksDir)){
		exec('rm -rf "'.$worksDir.'"');
	}
	
	if(mkdir($worksDir, 0777)){
		// on extrait le dump
		exec('tar jxvf "'.$backupContext.'" -C "'.$worksDir.'/"', $r, $error);	
		if ( $error ){
			echo "An error occured during the extraction of context archive.";
			__debug("<br>Commande 'tar xfjv \"$backupContext\" -C \"$worksDir/\"'<br>");
		}
		else{
			// pour le premier contexte, on n'est pas en multiproduit -> il n'y a donc qu'un seul fichier dans l'archive : product_1.sql
			if(file_exists($worksDir.'/product_1.sql')){ 
				$fd = fopen($worksDir.'/product_1.sql', 'r');
				if($fd){
					if(!feof($fd)){
						// le fichier n'est pas vide, on le parcourt jusqu'à la table users
						$ligne = fgets($fd);
						while (preg_match('/^COPY users \(id_user/',$ligne)==0 && !feof($fd))
							$ligne = fgets($fd);
						if(preg_match('/^COPY users \(id_user/',$ligne)==1){
							// la ligne a été trouvée
							// qu'y-a-t-il sur la ligne suivante ?
							$ligne = fgets($fd);
							// on vérifie si la table users est vide
							if(preg_match('/^\\\./',$ligne)>0){ 
								// aucun enregistrement pour la table users -> c'est la sauvegarde est à supprimer
								// on supprime le fichier de sauvegarde inutilisable
								echo "Removing of the corrupted context archive.<br>";
								exec('rm -f "'.$backupContext.'"', $r, $error);
								if ( $error ){
									echo "An error occured during removing the corrupted context file archive.<br>";
								}
								else
									echo "Corrupted context file archive removed.<br>";
								// et le répertoire créé pour la vérification 
								exec('rm -rf "'.$worksDir.'"', $r, $error);
								if ( $error ){
									echo "An error occured during removing temporary files.<br>";
								}
							}
						}
						else{
							// on n'a pas trouvé la table users
						}
					}
					else{
						// le fichier dump est vide
					}
				}
			}
			else{
				// le fichier product_1.sql n'existe pas dans l'archive
			}
		}
	}
	else{
		// erreur lors de la création du répertoire temporaire
	}
}
// else echo "fichier $backupContext n'existe pas<br>";
?>