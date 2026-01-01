<?php
/*
	11/12/2008 GHX : 
		- création du fichier
	11/02/2009 GHX
		- contournement de la fonction wc qui posse problème
		- ajout un peu de mode debug
		- suppession d'un fichier pour éviter de surcharge le répertoire upload à la funct
	17/12/2009 NSE
		- on rend la comparaison des NA insensible à la casse (strtolower des NA)
*/
?>
<?php
/**
 * Cette classe permet de vérifie la structure et le contenu d'un fichier CVS de mapping
 *
 * @author : GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 */
class MappingCheck extends MappingAbstract
{
	/**
	 * Nombre de ligne dans le fichier comprenant aussi l'entête
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var int
	 */
	private $_numberLinesInFile;
	
	/**
	 * Instance de l 'objet Mapping à vérifier
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var Mapping
	 */
	private $_mapping;
	
	/**
	 * Constructeur
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param Ressource $db Ressource de connexion à la base de données
	 */
	public function __construct ( $db )
	{
		parent::__construct($db);
	} // End function __construct
	
	/**
	 * Détermine quel instance de mapping on doit vérifier
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param Mapping
	 */
	public function setCheck ( $mapping )
	{
		$this->_mapping = $mapping;
	} //  End function setCheck
	
	/**
	 * Lance la vérification du fichier CSV
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	public function process ()
	{
		// On supprime toutes les lignes vides du fichier avant de faire les vérifications
		$this->exec("dos2unix ".$this->_mapping->_dirUpload.$this->_mapping->_filename);
		// 11/02/2009 GHX
		// HACK : on ajoute un saut de ligne à la fin du fichier pour tromper la commande wc qui ne compte uniquement le nombre de retour à la ligne
		$this->exec("echo \"\n\" >> ".$this->_mapping->_dirUpload.$this->_mapping->_filename);
		$this->exec("sed -i '/./!d' ".$this->_mapping->_dirUpload.$this->_mapping->_filename);
		
		// Récupère le nombre de ligne dans le fichier
		$this->numberLinesInFile();
		
		$this->checkDelimiterAndHeader();
		$this->checkNumberColumns();
		$this->checkColumnEmpty();
		
		// Convertit le fichier dans le format TA
		// On peut convertir le fichier car on a le header
		$this->convertFile();
		
		$this->checkNEExists();
		
		/*
			CHECK 1 : vérification s'il n'y a pas de doublons dans la colonne des identifiants réseau du produit mappé
		*/
		unset($error1);		
		$error1 = $this->checkDuplicates($this->_columnsName['A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_MAPPED']);
		/*
			CHECK 2 : vérification s'il n'y a pas de doublons dans la colonne des identifiants réseau du produit master topologie
		*/
		unset($error2);
		$error2 = $this->checkDuplicates($this->_columnsName['A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_CODEQ']);		
		
		if ( $error1 != '' || $error2 != '' )
		{
			$error = ($error1 != '' && $error2 == '' ? $error1 : ($error1 == '' && $error2 != '' ? $error2 : $error1 .'<br />'.$error2) );
			throw new Exception($error);
		}
		
		/*
			CHECK 1 : vérification s'il n'y a pas des éléments qui n'existe pas par rapport au produit mappé
		*/		
		unset($error1);
		$error1 = $this->checkNAExists($this->_columnsName['A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_MAPPED'], $this->_mapping->_fileMappedTopo, $this->_mapping->_productMapped['sdp_label']);	
		/*
			CHECK 2 : vérification s'il n'y a pas des éléments qui n'existe pas par rapport au master topologie
		*/
		unset($error2);
		$error2 = $this->checkNAExists($this->_columnsName['A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_CODEQ'], $this->_mapping->_fileMasterTopo, $this->_mapping->_masterTopology['sdp_label']);		
		
		if ( $error1 != '' || $error2 != '' )
		{
			$error = ($error1 != '' && $error2 == '' ? $error1 : ($error1 == '' && $error2 != '' ? $error2 : $error1 .'<br /><br />'.$error2) );
			throw new Exception($error);
		}
		
		$this->checkCodeqNotUsed();
	} // End function process
	
	/**
	 * Convertit les NE du fichier dans le format de T&A. En effet, il est possible de spécifié les valeurs de Astellia au lieu de mettre les NE de T&A
	 * 
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	private function convertFile ()
	{
		$this->debug('Convertion du fichier (colonne NA au format T&A)');
	
		$fileHeaderAstellia = $this->topologyHeaderAstellia($this->_mapping->_dirUpload, $this->_mapping->_delimiter);
		
		// Création d'un nom de fichier temporaire
		$fileTmp = uniqid('tmp', true).'.mapping';
		
		// Inverse les clés <=> valeurs
		$header = array_flip($this->_mapping->_header);		
		$indexNE = $header[$this->_columnsName['A_MAPPING_TOPO_COLUMN_LABEL_NA_TYPE']];
		
		$cmdConvertColumnNE = sprintf("
					awk '
					BEGIN {
						FS=\"%s\";
						OFS = FS;
						file1=\"\";
					}
					{
						# Si on est la première ligne
						if ( 1==FNR )
						{
							# si on est sur le premier fichier
							if ( file1==\"\" )
							{
								file1=FILENAME;	
							}
							else
							{
								# Première ligne du fichier chargé, on saute l entête
								next;
							}
						}
						
						# Si on est sur le premier fichier
						if ( file1==FILENAME )
						{
							# values[eorh_id_column_file] = eorh_id_column_db
							values[$2] = $1;
						}
						else
						{
							if ( values[$%2\$s] != \"\" )
							{
								$%2\$s = values[$%2\$s];
							}
							print $0;
						}
					}
					' %3\$s %4\$s > %5\$s", 
					$this->_mapping->_delimiter,
					$indexNE,
					$this->_mapping->_dirUpload.$fileHeaderAstellia,
					$this->_mapping->_dirUpload.$this->_mapping->_filename,
					$this->_mapping->_dirUpload.$fileTmp
				);
		 
		$this->exec($cmdConvertColumnNE, true);
		
		$cmdMv = sprintf("
					mv %s %s", 
					$this->_mapping->_dirUpload.$fileTmp,
					$this->_mapping->_dirUpload.$this->_mapping->_filename
				);
		$this->exec($cmdMv, true);
		
		// 11/02/2009 GHX
		// Suppression du fichier on n'en a plus besoin
		@unlink($this->_mapping->_dirUpload.$fileHeaderAstellia);
	} // End function convertFile
	
	/**
	 * Récupère le nombre de ligne dans le fichier comprenant aussi l'entête
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	private function numberLinesInFile ()
	{
		$cmdNbLines = sprintf("cat %s | wc -l", $this->_mapping->_dirUpload.$this->_mapping->_filename);
		$resultNbLines = $this->exec($cmdNbLines);
		$this->_numberLinesInFile = $resultNbLines[0];
		if ( $this->_debug )
		{
			echo 'Nombre de ligne dans le fichier : '.$this->_numberLinesInFile;
		}
	} // End function numberLinesInFile
	
	/**
	 * Vérifie le délimiteur et le nom des 3 colonnes dans l'entête du fichier
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	private function checkDelimiterAndHeader ()
	{
		// Récupère l'entête du fichier
		$cmdGetHeader = sprintf(
				"awk 'NR == 1 {print $0; Exit}' %s", 
				$this->_mapping->_dirUpload.$this->_mapping->_filename
			);
		$resultGetHeader = $this->exec($cmdGetHeader);		
		$header = explode($this->_mapping->_delimiter, $resultGetHeader[0]);
		
		// Vérification du délimiteur
		if ( $header[0] == $resultGetHeader[0] )
		{
			throw new Exception(__T('A_E_UPLOAD_TOPO_DELIMITER_NOT_VALID'));
		}
		
		foreach ( $header as $index => $h )
		{
			if ( empty($h) )
			{
				throw new Exception(__T('A_MAPPING_MASTER_TOPO_HEADER_COLUMN_EMPTY', $index+1 ));
			}
		}
			
		// Regarde la différence entre les 2 tableaux
		$diff = array_diff($header, $this->_columnsName);
		$diff2 = array_diff($this->_columnsName, $header);
		// Si on obtient un résultat c'est qu'il y a des noms de colonnes incorrects
		if ( count($diff) > 0 )
		{
			// Si des colonnes sont incorrectes
			throw new Exception(__T('A_E_MAPPING_TOPO_HEADER_COLUMN_NOT_VALID', '<br />&nbsp;&nbsp;- '.implode('<br />&nbsp;&nbsp;- ', $diff)));
		}
		elseif ( count($diff2) > 0 )
		{
			// S'il manque des colonnes
			throw new Exception(__T('A_E_MAPPING_TOPO_HEADER_COLUMN_MISSING', '<br />&nbsp;&nbsp;- '.implode('<br />&nbsp;&nbsp;- ', $diff2)));
		}
		
		// On ajuste les index afin des les faire correspondre à un numéro des colonnes dans le fichier
		$header[3] = $header[2];
		$header[2] = $header[1];
		$header[1] = $header[0];
		unset($header[0]);
		
		$this->debug($header,'Entete du fichier avec les index correspondant');
		
		$this->_mapping->setHeader($header);
	} // End function checkDelimiterAndHeader
	
	/**
	 * Vérifie le nombre de colonnes sur chaque ligne
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	private function checkNumberColumns ()
	{
		// Commande awk qui regarde si toutes les lignes non vides on bien 3 colonnes
		$cmdCheckNbCol = sprintf(
				"awk -F\"%s\" 'NF != 3 && $0 != \"\" {print NR}' %s", 
				$this->_mapping->_delimiter, 
				$this->_mapping->_dirUpload.$this->_mapping->_filename
			);
		$resultCheckNbCol = $this->exec($cmdCheckNbCol);
		$nbResultCheckNbCol = count($resultCheckNbCol);
		
		if ( $this->_debug & 2 )
		{
			echo '<br />checkNumberColumns()<pre>';
			print_r($resultCheckNbCol);
			echo '</pre>';
		}
		
		/*
			Si toutes les lignes n'ont pas 3 colonnes (saut le header) on affiche "Invalid input file syntax", au lieu
			de dire de "The number of columns is not valid on following lines : <br />....." car si le fichier contient
			beaucoup de ligne on  aura un message un peu illisible car ce dernier affiche toutes les lignes
		*/
		if ( $nbResultCheckNbCol > 0 && $nbResultCheckNbCol < ($this->_numberLinesInFile-1) )
		{
			throw new Exception(str_replace('<br />', '', __T('A_E_UPLOAD_TOPOLOGY_NB_COLUMNS_NOT_VALID', implode(', ', $resultCheckNbCol))));
		}
		elseif ( $nbResultCheckNbCol == ($this->_numberLinesInFile-1) )
		{
			throw new Exception(__T('A_E_UPLOAD_TOPO_INVALID_FILE_SYNTAX'));
		}
	} // End function checkNumberColumns
	
	
	/**
	 * Vérifie s'il n'y a pas des lignes où A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_MAPPED ou A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_CODEQ est vide 
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	private function checkColumnEmpty ()
	{
		// Inverse les clés <=> valeurs
		$header = array_flip($this->_mapping->_header);	
		
		// Commande awk qui vérifie s'il n'y a pas des lignes où une colonne est vide
		$cmdCheckColumnEmpty = sprintf(
				"awk -F\"%s\" '$%s == \"\" || $%s == \"\" {print NR\"::::\"$0}' %s", 
				$this->_mapping->_delimiter, 
				$header[$this->_columnsName['A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_MAPPED']],
				$header[$this->_columnsName['A_MAPPING_TOPO_COLUMN_LABEL_NA_TYPE']],
				$this->_mapping->_dirUpload.$this->_mapping->_filename
			);
		$resultCheckColumnEmpty = $this->exec($cmdCheckColumnEmpty);
		$nbCheckColumnEmpty = count($resultCheckColumnEmpty);
		
		/*
			Si toutes les lignes n'ont pas 3 colonnes (saut le header) on affiche "Invalid input file syntax", au lieu
			de dire de (exemple) "It miss the column <b>id_codeq</b> lines : ..." car si le fichier contient
			beaucoup de ligne on  aura un message un peu illisible car ce dernier affiche toutes les lignes
		*/
		if ( $nbCheckColumnEmpty > 0 && $nbCheckColumnEmpty < ($this->_numberLinesInFile-1) )
		{
			// Tableau contenant les lignes correctes
			$resultsErrors = array();
			foreach ( $resultCheckColumnEmpty as $invalidLine )
			{
				$invalidLine = explode('::::', $invalidLine);
				$cols =  explode($this->_mapping->_delimiter, $invalidLine[1]);
				// Si c'est la première colonne qui est vide
				if ( $cols[0] == "" )
				{
					$resultsErrors[1][] = $invalidLine[0];
				}
				// Si c'est la deuxième colonne qui est vide
				if ( $cols[1] == "" )
				{
					$resultsErrors[2][] = $invalidLine[0];
				}
				// Si c'est la troisième colonne qui est vide
				if ( $cols[2] == "" )
				{
					$resultsErrors[3][] = $invalidLine[0];
				}
			}
			
			// Création du message d'erreur
			$messageError = '';
			foreach ( $resultsErrors as $index => $invalidLine )
			{
				$messageError .=  ($messageError != '' ? '<br />' : '');
				$messageError .= __T('A_E_MAPPING_TOPO_COLUMN_MISSING', $this->_mapping->_header[$index], implode(', ', $invalidLine));
			}
			
			throw new Exception($messageError);
		}
		elseif ( $nbCheckColumnEmpty == ($this->_numberLinesInFile-1) )
		{
			throw new Exception(__T('A_E_UPLOAD_TOPO_INVALID_FILE_SYNTAX'));
		}
	} // End function checkColumnEmpty
	
	/**
	 * Vérifie s'il n'y a pas des doublons dans les codes éléments par type de NE et retourne le message d'erreur. Si c'est une
	 * chaine vide c'est qu'il n'y pas de doublons
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $colname nom de la colonne à vérifier
	 * @param string
	 */
	private function checkDuplicates ( $colname )
	{
		// Inverse les clés <=> valeurs
		$header = array_flip($this->_mapping->_header);
		
		$indexNE = $header[$this->_columnsName['A_MAPPING_TOPO_COLUMN_LABEL_NA_TYPE']];
		
		// Récupère le numéro de la colonne dans le fichier
		$indexCol = $header[$colname];
		
		$cmdCheckDuplicates = sprintf("
					awk -F\"%s\"  ' 
					# Ne prend pas en compte l entete du fichier
					NR > 1 {
						#
						# %2\$s représente la valeur $indexCol
						# $%2\$s correspond donc à la colonne que l on vérifie
						#
						# %3\$s représente la valeur $indexNE
						# $%3\$s correspond donc à la colonne NE
						#
						# Si cest le premier élément
						if( lines[$%3\$s, $%2\$s] == \"\" )
						{
							lines[$%3\$s, $%2\$s] = 1;
						}
						else
						{
							doublons[$%3\$s, $%2\$s] = $%3\$s\"::::\"$%2\$s;
						}
					}
					END {
						#Affiche les doublons
						for( x in doublons )
						{
							print doublons[x];
						}
					}
					' %4\$s", 
					$this->_mapping->_delimiter,
					$indexCol,
					$indexNE,
					$this->_mapping->_dirUpload.$this->_mapping->_filename
				);
		
		$resultCheckDuplicates = $this->exec($cmdCheckDuplicates, true);

		$messageError = '';
		if ( count($resultCheckDuplicates) > 0 )
		{
			// Création du message d'erreur
			foreach ( $resultCheckDuplicates as $invalidLine )
			{
				$invalidLine = explode('::::',  $invalidLine);
				$messageError .=  ($messageError != '' ? '<br />' : '');
				$messageError .= __T('A_E_MAPPING_TOPO_NA_NOT_UNIQUE', $invalidLine[0], $invalidLine[1], $colname);
			}
		}
		
		return $messageError;
	} // End function checkDuplicates
	
	/**
	 * Vérification que les niveaux d'aggrégation existent sur le produit mappé et le master topologie.
	 * On vérifie d'abord si les niveaux existents dans la table sys_definition_network_agregation et ensuite on vérifie
	 * que ces niveaux sont aussi présents dans les tables de topologies. Car on ne peut pas mappé un niveau si celui n'est pas présent en base
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00	
	 */
	private function checkNEExists ()
	{
		$messageError = '';
		$header = array_flip($this->_mapping->_header);
		$indexNE = $header[$this->_columnsName['A_MAPPING_TOPO_COLUMN_LABEL_NA_TYPE']];
		
		// Commande qui récupère tous les niveaux d'agrégations dans le fichier de mapping
		$cmdGetNe = sprintf(
				"cut -d\"%s\" -f%d %s | sort | uniq | grep -ve \"^%s$\"",
				$this->_mapping->_delimiter,
				$indexNE,
				$this->_mapping->_dirUpload.$this->_mapping->_filename,
				$this->_columnsName['A_MAPPING_TOPO_COLUMN_LABEL_NA_TYPE']
			);
		$resultGetNe = $this->exec($cmdGetNe);
		
		/*
			CHECK 1 : Vérification que les niveaux d'aggrégations existent dans la table sys_definition_network_agregation
		*/
		// Produit master topologie
		// NSE bz 13263 : on rend la comparaison des NA insensible à la casse
		$diff = array_diff(array_map('strtolower',$resultGetNe), array_map('strtolower',$this->getNE($this->_mapping->_masterTopology['sdp_id'])));
		if ( count($diff) > 0 )
		{
			foreach ( $diff as $neNotExists )
			{
				$messageError .= ($messageError != '' ? '<br />' : '');
				$messageError .= __T('A_E_MAPPING_TOPO_NA_NOT_EXISTS', 'Network aggregation', '"'.$neNotExists.'"', $this->_mapping->_masterTopology['sdp_label']);
			}
		}
		
		// Produit mappé
		// NSE bz 13263 : on rend la comparaison des NA insensible à la casse
		$diff2 = array_diff(array_map('strtolower',$resultGetNe), array_map('strtolower',$this->getNE($this->_mapping->_productMapped['sdp_id'])));
		if ( count($diff2) > 0 )
		{
			foreach ( $diff2 as $neNotExists )
			{
				$messageError .= ($messageError != '' ? '<br />' : '');
				$messageError .= __T('A_E_MAPPING_TOPO_NA_NOT_EXISTS', 'Network aggregation', '"'.$neNotExists.'"', $this->_mapping->_productMapped['sdp_label']);
			}
		}
		
		if ( $messageError != '' )
		{
			throw new Exception($messageError);
		}
		
		/*
			CHECK 2 : vérifie que les niveaux présents dans le fichiers sont présent dans les tables de topologies
		*/
		// Produit master topologie
		$cmdGetNeMaster = sprintf(
				"cut -d\"%s\" -f2 %s | sort | uniq",
				$this->_mapping->_delimiter,				
				$this->_mapping->_dirUpload.$this->_mapping->_fileMasterTopo				
			);
		$resultGetNeMaster = $this->exec($cmdGetNeMaster);
		
		unset($diff);
		// NSE bz 13263 : on rend la comparaison des NA insensible à la casse
		$diff = array_diff(array_map('strtolower',$resultGetNe), array_map('strtolower',$resultGetNeMaster));
		if ( count($diff) > 0 )
		{
			foreach ( $diff as $neNotExists )
			{
				$messageError .= ($messageError != '' ? '<br />' : '');
				$messageError .= __T('A_E_MAPPING_TOPO_NE_NOT_IN_TOPO', $neNotExists, $this->_mapping->_masterTopology['sdp_label']);
			}
		}
	
		// Produit mappé
		$cmdGetNeMapped = sprintf(
				"cut -d\"%s\" -f2 %s | sort | uniq",
				$this->_mapping->_delimiter,				
				$this->_mapping->_dirUpload.$this->_mapping->_fileMappedTopo				
			);
		$resultGetNeMapped = $this->exec($cmdGetNeMapped);
		
		unset($diff2);
		// NSE bz 13263 : on rend la comparaison des NA insensible à la casse
		$diff2 = array_diff(array_map('strtolower',$resultGetNe), array_map('strtolower',$resultGetNeMapped));
		if ( count($diff) > 0 )
		{
			foreach ( $diff2 as $neNotExists )
			{
				$messageError .= ($messageError != '' ? '<br />' : '');
				$messageError .= __T('A_E_MAPPING_TOPO_NE_NOT_IN_TOPO', $neNotExists, $this->_mapping->_productMapped['sdp_label']);
			}
		}
		
		if ( $messageError != '' )
		{
			throw new Exception($messageError);
		}
		
		$this->debug($resultGetNe, 'Liste des NE ayant un mapping');
	} // End function checkNEExists
	
	/**
	 * Vérification avec le produit master topologie et retourne les messages d'erreurs si c'est une chaîne vide c'est qu'il n'y a pas d'erreur
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param int $colname colonne à vérifier dans le fichier chargé
	 * @param string $fileref nom de fichier de référence
	 * @param string $productLabel label du produit de ref
	 * @return string
	 */
	private function checkNAExists ( $colname, $fileref, $productLabel )
	{
		// Inverse les clés <=> valeurs
		$header = array_flip($this->_mapping->_header);
		
		$indexNE = $header[$this->_columnsName['A_MAPPING_TOPO_COLUMN_LABEL_NA_TYPE']];
		
		// Récupère le numéro de la colonne dans le fichier
		$indexCol = $header[$colname];
		
		$cmdSearchUndefinedNa = sprintf("
					awk -F\"%s\"  '
					BEGIN {
						file1=\"\";
					}
					{
						# Si on est la première ligne
						if ( 1==FNR )
						{
							# si on est sur le premier fichier
							if ( file1==\"\" )
							{
								file1=FILENAME;
							}
							else
							{
								next;
							}
						}
						
						# Si on est sur le premier fichier
						if ( file1==FILENAME )
						{
							# On mémorie les éléments réseaux par type
							# NSE bz 13263 : on rend les NA insensible à la casse
							lines[tolower($2),$1] = 1;
						}
						else
						{
							#
							# $%1\$s représente le délimiteur
							#
							# %2\$s représente la valeur $indexCol
							# $%2\$s correspond donc à la colonne que l on vérifie
							#
							# %3\$s représente la valeur $indexNE
							# $%3\$s correspond donc à la colonne NE
							#
							# NSE bz 13263 : on rend les NA insensible à la casse
							if ( lines[tolower($%3\$s),$%2\$s] == \"\" && $%2\$s != \"\" )
							{
								print $%3\$s\"%1\$s\"$%2\$s
							}
						}
						
					}
					' %4\$s %5\$s", 
					$this->_mapping->_delimiter,
					$indexCol,
					$indexNE,
					$this->_mapping->_dirUpload.$fileref,
					$this->_mapping->_dirUpload.$this->_mapping->_filename
				);
		
		//$this->debug($cmdSearchUndefinedNa, 'checkNAExists()');
		$resultSearchUndefinedNa = $this->exec($cmdSearchUndefinedNa, true);
		//$this->debug($resultSearchUndefinedNa);
		
		$messageError = '';
		if ( count($resultSearchUndefinedNa) > 0 )
		{
			// Création du message d'erreur
			foreach ( $resultSearchUndefinedNa as $invalidLine )
			{
				$invalidLine = explode($this->_mapping->_delimiter,  $invalidLine);
				$messageError .=  ($messageError != '' ? '<br />' : '');
				$messageError .= __T('A_E_MAPPING_TOPO_NA_NOT_EXISTS', $invalidLine[0], '"'.$invalidLine[1].'"', $productLabel);
			}
		}
		
		return $messageError;	
	} // End function checkNAExists
	
	/**
	 * Retourne un tableau contenant tous les niveaux d'aggrégations d'un produit
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param int $idProduct identifiant du produit
	 * @return array
	 */
	private function getNE ( $idProduct )
	{
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$db = Database::getConnection($idProduct);
		$query = "
					SELECT DISTINCT agregation 
					FROM sys_definition_network_agregation
				";	
		$result = $db->executeQuery($query);
		$neExists = array();
		while ( $row = $db->getQueryResults($result, 1))
		{
			$neExists[] = $row['agregation'];
		}
		
		return $neExists;
	} // End function getNE
	
	/**
	 * Vérifie qu'un codec n'est pas déjà utilisé sur d'autres éléments réseaux
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 */
	private function checkCodeqNotUsed ()
	{
		// Inverse les clés <=> valeurs
		$header = array_flip($this->_mapping->_header);
		
		$indexNE = $header[$this->_columnsName['A_MAPPING_TOPO_COLUMN_LABEL_NA_TYPE']];
		$indexMapped = $header[$this->_columnsName['A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_MAPPED']];
		$indexCodeq = $header[$this->_columnsName['A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_CODEQ']];
		
		$cmdCodeqNotUsed = sprintf("
					awk '
					BEGIN {
						FS=\"%s\";
						OFS = FS;
						file1=\"\";
					}
					{
						# Si on est la première ligne
						if ( 1==FNR )
						{
							# si on est sur le premier fichier
							if ( file1==\"\" )
							{
								file1=FILENAME;	
							}
							# Première ligne du fichier chargé, on saute l entête
							next;
						}
						
						# Si on est sur le premier fichier
						if ( file1==FILENAME )
						{
							# values[indexNE, indexCodeq] = indexMapped;
							values[$%s, $%s] = $%s;
						}
						else
						{
							# On regarde si le codeq est déjà utilisé
							if ( values[$2,$3] != \"\" && values[$2,$3] != $1 )
							{
								# codec:::idNewMapped:::idMapped::::NE
								print $3\"::::\"values[$2,$3]\"::::\"$1\"::::\"$2
							}
							
						}
					}
					' %s %s", 
					$this->_mapping->_delimiter,
					$indexNE,
					$indexCodeq,
					$indexMapped,
					$this->_mapping->_dirUpload.$this->_mapping->_filename,
					$this->_mapping->_dirUpload.$this->_mapping->_fileMappedTopo
				);
		 
		$resultCodeqNotUsed = $this->exec($cmdCodeqNotUsed, true);
		
		if ( count($resultCodeqNotUsed) > 0 )
		{
			$messageError = __T('A_E_MAPPING_TOPO_CODEQ_USED_INFO').'<br />';
			
			foreach ( $resultCodeqNotUsed as $invalidLine )
			{
				$invalidLine = explode('::::', $invalidLine);
				$messageError .=  ($messageError != '' ? '<br />' : '');
				$messageError .= __T('A_E_MAPPING_TOPO_CODEQ_USED', $invalidLine[3], $invalidLine[0], $invalidLine[2], $invalidLine[1]);
			}
			
			throw new Exception($messageError);
		}
	} // End functio checkCodeqNotUsed
	
} // End class MappingCheck
?>