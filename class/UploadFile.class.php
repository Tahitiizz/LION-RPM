<?php
/**
 * Cette classe permet de manipuler un fichier uploadé à partir d'un formulaire
 *
 * @author GHX
 */
class UploadFile
{
	/**
	 * Tableau d'information sur le fichier uploadé
	 * @array
	 */
	private $_file = array();
	
	/**
	 * Contient le message d'erreur
	 * @var string
	 */
	private $_error = '';
	
	/** 
	 * Chemin complet vers le fichier uploadé
	 * @var string
	 */
	private $_filename = '';
	
	/**
	 * Constructeur
	 *
	 * @author GHX
	 * @param string $file tableau d'information correspondant à $_FILES['XXXX'] ou XXXX est le nom du champ input
	 */
	public function __construct ( $file )
	{
		$this->_file = $file;
	} // End function __construct
	
	/**
	 * Retourne le type de fichier uplaodé
	 *
	 * @author GHX
	 * @return string
	 */
	public function getType ()
	{
		return $this->_file['type'];
	} // Return getType
	
	/**
	 * Retourne le nom du fichier uploadé
	 *
	 * @author GHX
	 * @return string
	 */
	public function getName ()
	{
		return $this->_file['name'];
	} // End function getName 
	
	/**
	 * Retourne le nom du ficheir uploadé une fois celui-ci copié dans le répertoire de destination
	 * Cette fonction ne peut être appelée après la fonction moveTo
	 *
	 * @author GHX
	 * @return string
	 */
	public function getFilename ()
	{
		return $this->_filename;
	} // End functoin getFilename
	
	/**
	 * Retourne un message d'erreur
	 *
	 * @author GHX
	 * @return string
	 */
	public function getError ()
	{
		return $this->_error;
	} // End function getError
	
	/**
	 * Vérification sur le fichier uploadé
	 *	- s'il a bien été uplaodé
	 *	- s'il n'est pas corrompu (uploadé entierement)
	 *	- si on a bien un fichier
	 *	- s'il n'est pas vide
	 *
	 * @author GHX
	 * @param mixed $extensions nom d'une extension ou un tableau d'extensions autorisés (par défaut toutes)
	 * @param mixed $types type de fichier attendu ou un tableau des types de fichier attentu (par défaut tous)
	 * @return boolean
	 */
	public function check ( $extensions = null, $types = null )
	{
		// Vérification sur le fichier uploadé par l'utilsateur
		$file_size  = $this->_file['size'];
		$file_type  = $this->_file['type'];
		$file_error = $this->_file['error'];
		
		if ( $file_error == 1 ||  $file_error == 2 )
		{	
			$this->_error = __T('A_UPLOAD_TOPOLOGY_FILE_IS_TOO_BIG');
		}
		elseif ( $file_error == 3 ) // Le fichier n'a été uploadé que partiellement 
		{	
			$this->_error = __T('A_E_UPLOAD_TOPOLOGY_FILE_PARTIAL');
		}
		elseif ( $file_error == 4 ) // Aucun fichier n'a été uploadé 
		{	
			$this->_error = __T('A_E_UPLOAD_TOPOLOGY_FILE_MISSING');
		}
		elseif ( $file_size == 0 ) // Fichier vide
		{
			$this->_error = __T('A_E_UPLOAD_TOPOLOGY_FILE_IS_EMPTY');
		}
		
		// Si le fichier uploadé doit correspondre à une extension
		if ( $extensions !== null )
		{
			$extOK = false;
			$filename = $this->_file['name'];
			// Si c'est une liste d'extension
			if ( is_array($extensions) )
			{
				foreach ( $extensions as $ext )
				{
					if ( substr($filename, strlen($ext)*-1) == $ext )
						$extOK = true;
				}
			}
			else // Si c'est juste une extension
			{
				if ( substr($filename, strlen($extensions)*-1) == $extensions )
					$extOK = true;
			}
			
			if ( $extOK === false )
				$this->_error = __T('A_E_UPLOAD_FILE_EXTENSION_INCORRECT');
		}
		
		if ( $types != null )
		{
			// Si c'est une liste d'extension
			if ( !is_array($types) )
			{
				$types = array($types);
			}
			
			if ( !in_array($this->getType(), $types) )
				$this->_error = __T('A_E_UPLOAD_TOPOLOGY_FILE_TYPE');
		}
		
		if ( empty($this->_error) )
			return true;
		
		return false;
	} // End function check
	
	/** 
	 * Déplace le fichier uploadé dans un répertoire
	 *
	 * @author GHX
	 * @param string $to répertoire de destination
	 * @param string $filename nom du fichier (par défaut null = prend le nom du fichier uploadé)
	 * @param boolean $dos2unix TRUE si un dos2unix doit être appliqué sur le fichier (default FALSE)
	 * @return boolean
	 */ 
	public function moveTo ( $to, $filename = null, $dos2unix = false )
	{
		if ( !is_dir($to) )
		{
			$this->_error = __T('A_E_CONTEXT_DIRECTORY_NOT_EXISTS', $to);
			return false;
		}
		elseif ( !is_writable($to) )
		{
			$this->_error = __T('A_E_CONTEXT_DIRECTORY_NOT_WRITEABLE', $to);
			return false;
		}
		
		if ( $filename == null )
			$filename = $this->_file['name'];
		
		if ( substr($to, 0, -1) != '/' )
			$to .= '/';
			
		if ( @move_uploaded_file($this->_file['tmp_name'], $to.$filename) )
		{
			$this->_filename = $to.$filename;
			@chmod($this->_filename, 0777);
			
			if ( $dos2unix == true )
			{
				exec('/usr/bin/dos2unix "'.$this->_filename.'"');
			}
			return true;
		}
		
		$this->_error = __T('A_E_UPLOAD_FILE_NOT_COPIED');
		return false;
	} // End function moveTo
	
	/**
	 * Vérifie que toutes les lignes ont le même nombre de colonnes par rapport à un délimiteur
	 * Cette fonction ne peut être appelé avant la fonction moveTo
	 *
	 * @author GHX
	 * @param string $delimiter valeur du délimiteur dans le fichier CSV (default ";")
	 * @return booolean
	 */
	public function checkCSVColumns ( $delimiter = ';' )
	{
		$cmdAwk = "awk -F'{$delimiter}' 'BEGIN{nbCol=0;} NR==1{nbCol=NF;} nbCol!=NF{print NR}' {$this->_file['tmp_name']}";
		exec($cmdAwk, $result);
		
		if ( empty($result) )
			return true;
		
		// Création du message d'erreur pour préciser sur quelles lignes ont n'a pas le même nombre de colonnes que le header
		$msgError = array();
		$lastLine = array_shift($result);
		$startStraightLine = $lastLine;
		foreach ( $result as $line )
		{
			if ( $line != $lastLine+1 )
			{
				if ( is_null($startStraightLine) || $startStraightLine == $lastLine)
					$msgError[] = $lastLine;
				else
					$msgError[] = $startStraightLine.'-'.$lastLine;
				$startStraightLine = null;
			}
			if ( is_null($startStraightLine)  )
			{
				$startStraightLine = $line;
			}
			$lastLine = $line;
		}
		if ( is_null($startStraightLine) || $startStraightLine == $lastLine)
			$msgError[] = $lastLine;
		else
			$msgError[] = $startStraightLine.'-'.$lastLine;
		
		$this->_error = __T('A_E_UPLOAD_TOPOLOGY_NB_COLUMNS_NOT_VALID', implode('<br />',$msgError));
		return false;
	} // End function checkCSVColumns
	
	/**
	 * Supprime les lignes vides du fichier uploadé
	 * Cette fonction ne peut être appelé avant la fonction moveTo
	 *
	 * @author GHX
	 */
	public function deleteEmptyLines ()
	{
		$cmdSed = "sed -i '/^\s*$/d' {$this->_file['tmp_name']}";
		exec($cmdSed);
	} // End function deleteEmptyLines

} // End class UplaodFile
?>