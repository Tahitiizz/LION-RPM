<?php
/*
	06/02/2009 MPR
		- ajout de la fonction setConnexionServerDistant() pour spécifier si la fusion se fait sur un serveur distant
*/
?>
<?php
/*
	v 1.2
		- 21/08/2008 GHX  : optimisation du script dans le cas où on fait appel à la functoin getStrRaw()
	v 1.1
		- 07/07/2008 GHX : modification de la commande awk pour prendre en cas certains cas particuliers (un fichier avec un seul compteur et pas de données pour ce compteur)
*/
?>
<?php
/**
 * Cette permet la fusion de plusieurs fichiers en un seul en faisant une jointure par rapport à une colonne  commune (ou plusieurs), par défaut c'est la première colonne
 * On peut spécifier le séparateur de champ en entré et le séparateur de sortie. La valeur par défaut est le poinr-virgule ";".
 *
 *  !!! ATTENTION !!! la liste des champs qui servent à la jointure doivent commencé à 1 et est consécutive
 *
 * Vérifier bien que les fichiers ont seulement le caractère \n (Unix) comme retourne à la ligne et  pas \r\n (Windows). Faire un dos2unix sur les fichiers dans le cas contraire.
 *
 * @author : GHX
 * @create : 19/06/2008
 * @modified : 18/07/2008
 * @version : 1.2
 */
class JoinFiles
{
	/**
	 * Tableau contenant la liste des fichiers à fusionner
	 * @var array
	 */
	var $_files;
	/**
	 * Caracètere qui sépare les champs (valeur par défaut ;)
	 * @var string
	 */
	var $_separator;
	/**
	 * Numéro des champs communs (valeur par défaut la première colonne)
	 * @var array
	 */
	var $_fields;
	/**
	 * Vrai si les fichiers possèdent un header dans le cas contraire false
	 * @var boolean (valeur par défaut false)
	 */
	var $_header;
	/**
	 * Connexion SSH sur un serveur distant
	 * @var SSHConnection
	 */
	var $_SSHConnection;
	
	/**
	 * Constructeur
	 */
	function JoinFiles ()
	{
		$this->_files = array();
		$this->_separator = ';';
		$this->_header = false;
		$this->_fields = array(1);
	} // End function JoinFiles
	
	/**
	 * Spécifie que la commande doit être exécutée sur un serveur distant
	 *
	 *	06/02/2009 MPR
	 *		- Ajout de la fonction
	 *		
	 * @author MPR
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param array $infosProd tableau d'information sur le produit
	 */
	function setConnexionServerDistant ( $infosProd )
	{
		include_once(dirname(__FILE__) . "/SSHConnection.class.php");
		
		try
		{
			$this->_SSHConnection = new SSHConnection($infosProd['sdp_ip_address'], $infosProd['sdp_ssh_user'], $infosProd['sdp_ssh_password'], $infosProd['sdp_ssh_port'], 1);
		}
		catch ( Exception $e )
		{
			echo "Could not connect to server {$infosProd['sdp_ip_address']}";
			return false;
		}
	} // End function setConnexionServerDistant
	
	/**
	 * Liste des fichiers à fusionner
	 *
	 * @param array $files
	 */
	function setFiles ( $files )
	{
		if ( is_array($files) )
			$this->_files = $files;
	} // End function setFiles
		
	/**
	 * Spécifie le caractère de séparation des champs
	 *
	 * @param string $separator
	 */
	function setSeparator ( $separator )
	{
		$this->_separator = $separator;
	} // End function setSeparator
	
	/**
	 * Numéro des olonnes en commun dans les fichiers, la valeur passé en paramètre peut être simple un entier s'il n'y a qu'une colonne
	 * sinon un tableau avec les différentes colonnes
	 * 
	 * @param mixed $fieldss
	 */
	function setFields ( $fields )
	{
		if ( is_array($fields) )
			$this->_fields = $fields;
		else
			$this->_fields = array($fields);
	} // End function setFields
	
	/**
	 * Spécifie si les fichiers possèdent un header
	 * @param boolean $header (default true)
	 */
	function hasHeader ( $header = true )
	{
		$this->_header = $header;
	} // End function hasHeader
	
	/**
	 * Fusionne les fichiers, retourne true si la fusion s'est pas sans problème sinon false
	 *
	 * @param string $filename
	 * @return boolean
	 */
	function join ( $filename )
	{
		$nbFiles = count($this->_files);
		if ( $nbFiles == 0 )
			return false;
		
		$listFiles = implode(' ',  $this->_files);
		// S'il n'y a pas de header on met quelques lignes en commentaires pour pas qu'elles ne soient prises en compte
		$hasHeader = ($this->_header == true ? '' : '#');
		
		if ( is_array($this->_fields[0]) )
		{
			$nbFields = count($this->_fields[0]);
			if ( $nbFiles != 2 || $nbFields != 2 || ($nbFields != count($this->_fields[1])) )
				return false;
			
			$fields1 = '$'.implode('";"$',$this->_fields[0]);
			$fields2 = '$'.implode('";"$',$this->_fields[1]);
			
			sort($this->_fields[1]);
			$max = array_pop($this->_fields[1]);
			$substr = "";
			foreach ( range(1, $max) as $f )
			{
				if ( !in_array($f, $fields2) )
					$substr = '$'.$f.'";"';
			}
			$fields = '$'.implode('";"$',range(1, $max));
			$substr1 = "substr($0,length({$fields})+1,length($0))";
			$substr = "substr($0,length({$fields})+2,length($0))";
			
		}
		else
		{
			$nbFields = count($this->_fields);
			$fields1 = '$'.implode('";"$',$this->_fields);
			$fields2 = '$'.implode('";"$',$this->_fields);
			$substr1 = "substr($0,length({$fields1})+1,length($0))";
			$substr = "substr($0,length({$fields1})+2,length($0))";
		}
		
		// Création de la commande
		$cmd = <<<EOF
awk -F"{$this->_separator}" '
		BEGIN {
			# Initialisation des variables avant traitement
			nbCol=0;
			tmpNbCol=0;
			header="";
			file1="";
			fileCurrent="";
			compteur=1;
			lastNbRaw = -1;
			lastResult = "";
		}
		function getStrRaw (nbRaw)
		{
			if ( lastNbRaw == nbRaw )
			{
				result = lastResult;
			}
			else
			{
				result = "";
				for ( i=0; i<nbRaw; i++ )
				{
					result = result"{$this->_separator}";
				}
				lastNbRaw = nbRaw;
				lastResult = result;
			}
			return result;
		}
		{
			# Si on est sur la premiere ligne du fichier
			# on mémorise le header
			if ( 1 == FNR )
			{
				fileCurrent = FILENAME;
				# Si on est sur le premier fichier
				if ( header == "" )
				{
					file1 = FILENAME;
					header = $0;
				}
				else
				{
					header = header";"{$substr};
				}
				# on memorise le nombre de colonne sans prendre en compte le fichier courant
				tmpNbCol = nbCol;
				# on memorise le nombre de colonne avec prise en compte du fichier courant moins de colonnes communes
				nbCol = nbCol + NF - {$nbFields};
			}
			# si on est sur les autres lignes du fichier
{$hasHeader}else
{$hasHeader}{
				if ( file1 == FILENAME )
				{
					fields = {$fields1};
				}
				else
				{
					fields = {$fields2};
				}
				
				# Si on est dans le cas de la macro diversité sur un même fichier
				if ( fileCurrent == FILENAME && numMacroDiversite[FILENAME, fields] != "" && numMacroDiversite[FILENAME, fields] >= 0 )
				{
					macrodiversite [compteur, 0] = fields;
					macrodiversite [compteur, 3] = nbCol;
					# si on est sur le premier fichier on met dans le tableau la ligne tel quelle
					if ( file1 == FILENAME )
					{
						macrodiversite [compteur, 1] = {$substr};
					}
					else
					{
						# si on est pas sur le premier fichier on ajoute le nombre de colonnes manquantes
						strRAW = "";
						if ( tmpNbCol != 0 )
						{
							if ( tmpNbCol == nbCol )
							{
								strRAW = getStrRaw(tmpNbCol-1);
							}
							else
							{
								strRAW = getStrRaw(tmpNbCol);
							}
						}
						macrodiversite [compteur, 1] = strRAW""{$substr};
						
					}
					compteur++;
				}
				else
				{
					# si l index est deja present dans le tableau
					numMacroDiversite[FILENAME, fields] = compteur;
					if ( memfile [fields] != "" )
					{
						# si on a un decalage dans le nombre de colonne on les complete par des valeurs null
						if ( lineNbCols[fields] != tmpNbCol )
						{
							missingCol = tmpNbCol-lineNbCols[fields];							
							strRAW = getStrRaw(missingCol);
							memfile [fields] = memfile[fields]""strRAW;
							tmpstr = {$substr};
							if ( tmpstr != "" )
							{
								memfile [fields] = memfile[fields]"{$this->_separator}"tmpstr;
							}
						}
						else
						{
							# si il n y a aucun decalage on ajoute la ligne courant a celle deja presente
							# sans la valeur servant a l index
							tmpstr = {$substr1};
							if ( tmpstr != "" )
							{
								memfile [fields] = memfile[fields]""tmpstr;
							}
						}
					}
					else
					{
						# si on est sur le premier fichier on met dans le tableau la ligne tel quelle
						if ( file1 == FILENAME )
						{
							memfile [fields] = $0;
						}
						else
						{
							# si on est pas sur le premier fichier on ajoute le nombre de colonnes manquantes
							strRAW = getStrRaw(tmpNbCol);
							memfile [fields] = fields""strRAW""{$substr1};
						}
					}
					# on memorise dans un autre tableau le nombre de colonne correspondant a l index
					lineNbCols[fields] = nbCol;
				}
{$hasHeader}}
		}
		END {
			# Une fois tous les fichiers parcourus on affichage le resultat
			
			# affichage du header
{$hasHeader}print header;
			# on parcourt le tableau
			for ( x in memfile )
			{
				# Si le nombre de colonne est correste on affichage la ligne
				if ( lineNbCols[x] == nbCol )
				{
					print memfile[x];
				}
				else # sinon on ajoute le nombre de colonnes manquantes
				{
					missingCol = nbCol-lineNbCols[x];
					strRAW = getStrRaw(missingCol);
					print memfile[x]""strRAW;
				}
			}
			# on gère la macro-diversité
			if ( compteur > 0 )
			{
				for ( i = 1 ; i < compteur ; i++ )
				{
					if ( macrodiversite[i, 1] != "" )
					{
						# Si le nombre de colonne est correste on affichage la ligne
						if ( macrodiversite[i, 3] == nbCol )
						{
							print macrodiversite[i, 0]"{$this->_separator}" macrodiversite[i, 1];
						}
						else # sinon on ajoute le nombre de colonnes manquantes
						{
							missingCol = nbCol-macrodiversite[i, 3];
							strRAW = getStrRaw(missingCol);
							print macrodiversite[i, 0]"{$this->_separator}"macrodiversite[i, 1]""strRAW;
						}
					}
				}
			}
		} ' {$listFiles} > {$filename}
EOF;
	
		// Supprime les commentaires
		// echo "<pre>".htmlentities($cmd)."</pre>";
		$cmd = preg_replace('/(.*)#.*/', '\1', $cmd);
		// Supprime les tabulations et  les retours à la ligne
		$cmd = preg_replace('/\s\s+/', ' ', $cmd);
		
		if ( $this->execCmd($cmd) === false )
			return false;
		
		return true;
	} // End function join
	
	/**
	 * Exécute une commande unix et renvoie le résutat, retourne false si la commande a échoué
	 *
	 * @param string $cmd 
	 * @return array
	 */
	function execCmd ( $cmd )
	{
		// echo "<br />$error -> $cmd <br />";
		// 06/02/2009 MPR : Utilisation de la classe SSHConnexion lorsque l'on est sur un serveur distant
		if( isset($this->_SSHConnection) && !empty($this->_SSHConnection) )
		{
			try
			{
				$this->_SSHConnection->exec($cmd);
			}
			catch ( Exception $e )
			{
				return false;
			}
		}
		else
		{
			exec($cmd, $result, $error);
			if ( $error )
				return false;
			return $result;
		}
	} // End function execCmd
} // End class JoinFiles
?>