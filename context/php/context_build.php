<?php
/*
	10/04/2009 GHX
		- Génération du log même s'il y a une erreur
		- Prise en compte des dépendances entres les éléments (ex: dash -> graphe)
	18/06/2009 GHX
		- Correction du BZ 9586 [REC][T&A CB 5.0][contexte][import]: le réimport de contexte application ne restaure pas les alarmes supprimées
*/
?>
<?php 
/*
* generation d'un contexte 
* @author SPS
* @date 31/03/2009
* @version CB 4.1.0.0
* @since CB 4.1.0.0
*/
include_once dirname(__FILE__)."/../../php/environnement_liens.php";
//classes necessaires pour la creation du contexte
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/Context.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/ContextElement.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/ContextMigration.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/ContextMount.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/ContextActivation.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'context/class/ContextGenerate.class.php';
// 20/10/2010 BBX : ajout de la classe ArrayTools pour le DE des compteurs clients
include_once REP_PHYSIQUE_NIVEAU_0.'class/ArrayTools.class.php';

/**
* fonction de generation de contexte
* @params array $elements elements a sauvegarder
* @params array $elements_data data des elements a sauvegarder
* @params bool $deleted_elements inclure ou non les elements a sauvegarder (defaut 1)
* @params string $context_name nom du contexte
* @params string $context_version version du contexte
* @returns string msg (erreur ou succes)
*/
function buildContext($elements,$elements_data,$deleted_elements,$context_name,$context_version) {	
	
	//repertoire de creation des contextes
	$dir = REP_PHYSIQUE_NIVEAU_0.'png_file/';
	
	// 08/10/2009 BBX : on remet l'ancien module pour les produits DEF
	$modifiedProducts = ProductModel::restoreOldModule();
	
	//si on a des elements
	if (count($elements) > 0) {
	
		//on nettoie le nom de contexte
		$tofind = "ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ '\"";
		$replac = "AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn___";
		$context_name=   strtr($context_name, $tofind, $replac);
		
		ob_start();
		
		try {
			$cg = new ContextGenerate();
			$cg->setDebug(1);
			$cg->setDirectory($dir);
			//on va supprimer les elements que l'on a supprime
			$cg->addDeleteElements($deleted_elements);
			
			// 16:34 09/04/2009 GHX
			// Gestion des dépendances
			$tmpElements = $elements;
			foreach ( $tmpElements as $prodElem )
			{
                            // on extrait les identifiants de l'élément et du produit
                            list($idp,$elem) = explode('_',$prodElem);
				//on ajoute les elements selectionnes
				$ce = new ContextElement($elem);
                                // 06/02/2014 NSE bz 39066
                                // on utilise l'indice complet (produit_elem) et non pas simplement l'id de l'éléménent qui n'est pas forcémemnt unique
				$ce->setSelected($elements_data[$prodElem]);
                                // on ne veut récupérer que les éléments du produit concerné
				if ( $dependency = $ce->getDependency($idp) )
				{
					foreach ( $dependency as $idElement => $elementsSelected )
					{
						if ( !in_array("${idp}_$idElement", $elements) )
						{
							$elements[] = "${idp}_$idElement";
						}
                                                // on utilise l'indice complet (produit_elem) et non pas simplement l'id de l'éléménent qui n'est pas forcémemnt unique
						if ( array_key_exists("${idp}_$idElement", $elements_data) )
						{
							// 11:46 18/06/2009 GHX
							// Correction de l'index passé dans le premier tableau du premier argument de la fonction array_merge
							// Correction dy BZ 9586 [REC][T&A CB 5.0][contexte][import]: le réimport de contexte application ne restaure pas les alarmes supprimées
                                                        // on utilise l'indice complet (produit_elem) et non pas simplement l'id de l'éléménent qui n'est pas forcémemnt unique
							$elements_data["${idp}_$idElement"] = array_merge($elements_data["${idp}_$idElement"], $elementsSelected);
						}
						else
						{
                                                        // on utilise l'indice complet (produit_elem) et non pas simplement l'id de l'éléménent qui n'est pas forcémemnt unique
							$elements_data["${idp}_$idElement"] = $elementsSelected;
						}
					}
				}
			}
			$prodList = array();
			foreach($elements as $prodElem) {
                            // on extrait les identifiants de l'élément et du produit
                            list($idp,$elem) = explode('_',$prodElem);
                            if ( !in_array($idp, $prodList) )
                                    $prodList[] = $idp;
				//on ajoute les elements selectionnes
				$ce = new ContextElement($elem);
				//on ajoute les donnees de chaque element selectionne
                                // on utilise l'indice complet (produit_elem) et non pas simplement l'id de l'éléménent qui n'est pas forcémemnt unique
				$ce->setSelected($elements_data[$prodElem]);      
                                // 2014/01/21 bz 39073 ajout du paramètre id product
				$cg->addElement($ce,$idp);
			}
			
			//on genere le contexte
			$context_archive = $cg->generate($context_name,$context_version,null,$prodList);
		}
		catch(Exception $e) {
			$error = $e->getMessage();
			echo $error;
		}
		
		// 08:42 10/04/2009 GHX
		// génération du log même s'il y a une erreur
		$date=date("Y-m-d H:i:s");
		
		//on recupere la sortie des fonctions appelees plus haut
		$str = ob_get_contents();
		ob_end_clean();
		
		//ecriture des logs
		$date=date("Ymd");
		$log_name = $dir.$date."_".$context_name.".log";
		//on ecrit le fichier de log
		file_put_contents($log_name,$str,FILE_APPEND);
	}
	else {
		//si aucun element, on affiche une erreur
		$error = __T('A_E_CONTEXT_NO_ELEMENTS_SELECTED');
	}
	
	if ($error) $msg['error'] = $error;
	else {
		//si on a pas d'erreur, on envoie le nom de l'archive cree
		$msg['archive_name'] = $context_archive;
	}
	
	// 08/10/2009 BBX : on remet DEF aux produits modifiés
	foreach($modifiedProducts as $productId)
	{
		$productModel = new ProductModel($productId);
		$productModel->setAsDef();
	}
	
	return $msg;
}	
	
?>