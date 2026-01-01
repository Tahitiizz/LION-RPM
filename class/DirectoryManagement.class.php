<?php
/**
*	26/05/2010 NSE gestion de l'inexistence de la fonciton posix
*/
?><?php
/**
*	Classe permettant de générer les répertoires
*
*	@author	BBX - 13/08/2009
*	@version	CB 5.0.0.00
*	@since	CB 5.0.0.00
*
*
*/
class DirectoryManagement
{
	/*	Répertoire à manipuler */
	private $directoryPath = '';

	/*
	*	Constructeur
	*/	
	public function __construct($directoryPath='.')
	{
		$this->directoryPath = $directoryPath;
	}
	
	/*
	*	Vérifie l'existence du répertoire
	*/
	public function exists()
	{
		return (is_dir($this->directoryPath));
	}

	/*
	*	Vérifie si le répertoire est accessible en écriture
	*/	
	public function writable()
	{
		return is_writable($this->directoryPath);
	}

	/*
	*	Retourne le propriétaire du répertoire
	*/		
	public function getOwner()
	{
		// 26/05/2010 NSE test de l'existence de la fonciton posix
		if(function_exists('posix_getpwuid')){
			$onwerInfos = posix_getpwuid(fileowner($this->directoryPath));
			return $onwerInfos['name'];
		}
		else{
			return false;
		}
	}

	/*
	*	Nous si l'utilisateur est propriétaire du répertoire
	*/	
	public function isMine()
	{
		// 26/05/2010 NSE gestion de l'inexistence de la fonciton posix
		if(function_exists('posix_getpwuid')){
			exec('whoami',$result);
			$me = trim($result[0]);
			return ($this->getOwner() == $me);
		}
		else{
			// si la fonction posix n’existe pas, on compare directement les id sans passer par les noms
			exec('id',$result);
			preg_match('/^uid=([0-9]+)\(/',$result[0],$match);
			return ($match[1]==fileowner($this->directoryPath));
		}
	}

	/*
	*	Créé le répertoire
	*/		
	public function create()
	{
		return mkdir($this->directoryPath,0777,true);
	}
	
	/*
	*	Supprime le répertoire
	*/		
	public function delete()
	{
		exec('rm -Rf '.$this->directoryPath,$result,$error);
		return $error;
	}

	/*
	*	Affecte un umask
	*/	
	public function chmod($umask)
	{
		return chmod($this->directoryPath, $umask);
	}

	/*
	*	Gestion auto : si le répertoire n'existe pas, on le créé
	*/	
	public function autoFix($umask)
	{
		// Variable de check
		$isOk = true;
		
		// Test d'exitence
		if(!$this->exists()) {
			$isOk &= $this->create();
		}

		// Mise à jour du umask
		if($this->isMine()) {
			$isOk &= $this->chmod($umask);
		}

		// Retour
		return $isOk &= $this->writable();
	}
}
?>