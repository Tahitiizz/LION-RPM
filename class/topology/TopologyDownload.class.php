<?php
/*
	27/08/2009 GHX
		- Modification dont on exécute les COPY car si le slave est sur un serveur distant ca plante
	15/09/2009 GHX
		- Correction d'un probleme si on passe un ID produit qui vaut zéro
	26/11/2009 BBX
		- Correction du dédoublonnage du fichier de topo dans la fonction removeUselessFieldsInTopologyFile. BZ 12657
	04/12/2009 GHX
		- Modification de la correction du BZ 12657 car si on sélectionnait tous les NA d'une famille et on se retrouvait avec un fichier vide (dans le cas ou le NA max n'avait pas de valeur)
	08/12/2009 GHX
		- Correction du BZ 12973 [CB 5.0][Data Export][Mapping] : problème de label avec les éléments mappés
	23/12/2009 GHX
		- Correction du BZ 12657 [Module Corporate][optimisation] les fichiers de topo des affiliate sont trop volumineux
			-> Modification de la fonction removeUselessFieldsInTopologyFile()
	05/01/2010 GHX
		- Correction du BZ 13602 [CB 5.0.2.2][Upload Topology] Erreur "Error empty file" lors de l'upload d'un fichier de topologie pourtant correctement formatté
			-> Modif de la fonction getNaMinWanted()
	17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les exécutables psql et php (PSQL_DIR et PHP_DIR)
        30/07/2010 BBX
                - Réécriture de la méthode mapLabels. BZ 12973
          03/08/2010 MPR
                - Correction du BZ 16841 : Si un seul NA pour la famille, le fichier en entrée n'est pas le même
        13/09/2010 MPR : Correction du bz17815 - Fichier de topologie incorrect

  28/07/2011 MMT bz 22987
 			- si les valeurs de NA contiennent le caracteres de separation csv, les commandes echouent
 			  On code ces caractères apres la génération SQL et decode une fois les commandes effectuées
 
 * 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
 * 14/12/2011 ACS BZ 25130 The topology file must be created at the start of the script. Not at the end with "$commandsToExecute"
 * 28/03/2013 GFS BZ#32769 - [SUP][TA Cigale IU][Vidéotron][AVP 34177]: After upgrade data export generation is crashing
 * 
 * 
 *
 */
?>
<?php
/**
*	Classe permettant de télécharger une topologie 1er axe ou 3ème axe
*
*	@author	BBX - 10/08/2009
*	@version	CB 5.0.0.00
*	@since	CB 5.0.0.00
*
*	maj 12/08/2009 - MPR : Correction du bug 10916 - Les id des colonnes prennent -1
*	maj 15/10/2009 - MPR :  Ajout des colonnes trx et charge pour Downnload Topology
*
*/
class TopologyDownload
{
	/*
	*	Constantes
	*/
	// Table de config des familles
	const FAMILY_TABLE = 'sys_definition_categorie';
	// Table de config des niveaux d'agrégation
	const NETWORK_AGR_TABLE = 'sys_definition_network_agregation';
	// Table arc ref
	const ARC_REF_TABLE = 'edw_object_arc_ref';
	// Table object ref
	const OBJECT_REF_TABLE = 'edw_object_ref';
	// Table object_ref_parameters
	const OBJECT_REF_PARAM_TABLE = 'edw_object_ref_parameters';

	// 28/07/2011 MMT bz 22987   separetor code to replace in codeDelimiterInFileBeforeCmd
	const CVS_SEPARATOR_ENCODE = "|SePaRaToR|";

	// 28/07/2011 MMT bz 22987   define default enclosure csv char
	const CVS_ENCLOSURE_CHAR = '"';

	/*
	*	Variables
	*/
	// Instance de DatabaseConnection
	private static $database;
	// Produit
	private $product = 0;
	// Tableau d'information sur le produit
	private $infosProduct = array();
	// Famille
	private $family = '';
	// Séparateur
	private $separator = ';';
	// Champs demandés
	private $wantedFields = Array();
	// Chemin de destination
	private $targetDir; 
	// Booléen indiquant si on utilise la topologie mappée ou non
	private $useMappedTopology = false;
	// Type de corrdonnées. 0 = x/y. 1 = GPS (par défaut)
        //  13/09/2010 MPR : Correction du bz17815 - Fichier de topologie incorrect
	private $CoordsType = 1;
	// Gestion des labels nuls
	private $useCodesIfEmpty = false;
	// Niveaux d'agrégations 1er axe supérieurs au niveau minimum
	private $NALevels = Array();
	// Niveaux d'agrégations 3ème axe supérieurs au niveau minimum
	private $NALevelsThirdAxis = Array();
	// Niveau minimum 1er axe
	private $minLevel = '';
	// Niveau minimum 3ème axe
	private $minLevelThirdAxis = '';	
        // 07/09/2010 BBX
        // DE Bypass : Permet d'exporter ou non les éléments virtuels
        private $exportVirtualElements = false;

	/*
	*	Constructeur
	*	@param string : famille
	*	@param int : id du produit
	*/
	public function __construct($family,$product=0)
	{
		// Connection  la base de données du produit concerné par l'export
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		if (empty(self::$database))
			self::$database = Database::getConnection ($product);
		// Mémorisation du produit
		$this->product = $product;
		// Récupère les infos sur le produit
		$infosProduct = getProductInformations();
		$this->infosProduct = $infosProduct[$this->product];
		
		// 15:07 15/09/2009 GHX
		// Dans le cas on l'ID du produit est zéro on récupère les infos du fichier xbdd.inc pour ce connecter sur la base courante
		// sinon les commandes psql ne fonctionne pas car le nom de la base et l'IP sont vide 
		if ( $this->product == 0 )
		{
			include dirname(__FILE__).'/../../php/xbdd.inc';
			$this->infosProduct['sdp_db_password'] = $APass;
			$this->infosProduct['sdp_db_login'] = $AUser;
			$this->infosProduct['sdp_db_name'] = $DBName;
			$this->infosProduct['sdp_ip_address'] = $AHost;
		}
		
		// Mémorisation de la famille
		$this->family = $family;
		// Répertoire de destination par défaut
		$this->targetDir = REP_PHYSIQUE_NIVEAU_0.'upload/';
		// Initialisation des niveaux d'agrégation
		$this->initMinLevel();
		$this->initNaLevels();
		if(GetAxe3($this->family,$this->product)) {
			$this->initMinLevel(1);
			$this->initNaLevels(1);
		}
	}

	/*
	*	Récupère le niveau minimum 1er axe ou 3ème axe de la famille / produit concerné
	*	@param bool : vrai = 3ème axe
	*/		
	public function initMinLevel($thirdAxis=false)
	{
		if($thirdAxis) {
			$this->minLevelThirdAxis = get_network_aggregation_min_axe3_from_family($this->family,$this->product);
		}
		else {
			$this->minLevel = get_network_aggregation_min_from_family($this->family,$this->product);
		}
	}

	/*
	*	Récupère les niveau > minimum 1er axe ou 3ème axe de la famille / produit concerné
	*	@param bool : vrai = 3ème axe
	*/	
	public function initNaLevels($thirdAxis=false)
	{
		// Tableau des NA
		$naArray = Array();
		// Récupération du niveau minimum de la famille	
		$minLevel = $thirdAxis ? $this->minLevelThirdAxis : $this->minLevel;
		// Récupération de tous les NA supérieur au niveau minimum
		$axe = ($thirdAxis) ? '= 3' : 'IS NULL';
		$queryNA = "SELECT agregation, level_source
		FROM ".self::NETWORK_AGR_TABLE."
		WHERE family = '".$this->family."'
		AND axe {$axe}
		AND agregation_rank > (
			SELECT agregation_rank 
			FROM ".self::NETWORK_AGR_TABLE." 
			WHERE agregation = '".$minLevel."' 
			AND family = '".$this->family."')
		ORDER BY agregation_rank";
		$result = self::$database->execute($queryNA);
		while($values = self::$database->getQueryResults($result,1))
			$naArray[] = $values;
		if($thirdAxis)
			$this->NALevelsThirdAxis = $naArray;
		else $this->NALevels = $naArray;
	}

	/*
	*	Affecte un nouveau séparateur de champ
	*	@param string : séparateur de champ
	*/	
	public function setSeparator($separator=';')
	{
		$this->separator = $separator;
	}

	/*
	*	Indique à l'objet si l'in doit utilisé la topologie mappée ou non
	*	@param bool : vrai pour topo mappée
	*/		
	public function setMapping($mapping=false)
	{
		$this->useMappedTopology = $mapping;
	}

	/*
	*	Détermine quels champs devra contenir le fichier de topologie
	*	@param array : tableau contenant les champs à récupérer
	*/		
	public function setFields($fields=Array())
	{
		$this->wantedFields = $fields;
	}

	/*
	*	Affecte un nouveau répertoire de destination pour le fichier de topo
	*	@param string : répertoire de destination
	*/		
	public function setTargetDir($targetDir)
	{
		if(is_dir($targetDir)) 
			$this->targetDir = $targetDir;
	}

	/*
	*	Permet de modifier les types de corrdonnées recherchées
	*	@param bool : 1 = GPS. 0 = XY
	*/		
	public function setCoordsType($CoordsType=1)
	{
		$this->CoordsType = $CoordsType;
	}

	/*
	*	Définit comment seront construit les labels
	*	@param bool : si vrai, les labels vides seront populés avec le code correspondant.
	*/		
	public function setLabelFillingMethod($useCodesIfEmpty=false)
	{
		$this->useCodesIfEmpty = $useCodesIfEmpty;
	}

	/*
	*	Vérifie l'existance de la famille
	*	@return bool : vrai si la famille est correcte, sinon faux
	*/	
	public function checkFamily()
	{
		$query = "SELECT family 
		FROM ".self::FAMILY_TABLE."
		WHERE family = '".$this->family."'";
		self::$database->execute($query);
		return self::$database->getNumRows();
	}

	/*
	*	Génère le fichier de topologie
	*	@param string : nom du fichier souhaité pour la topologie
	*	@param bool : vrai pour générer un fichier de topo 3ème axe. Faux par défaut
	*/		
	public function exportTopology($topologyFileName='topology.csv',$thirdAxis=false)
	{
		// On commence par vérifier la famille
		if(!$this->checkFamily())
			return false;
			
		// Si on a demandé un 3ème axe, on vérifie bien que la famille en dispose
		if($thirdAxis && !GetAxe3($this->family,$this->product))
			return false;
		
		// Génération d'un fichier de topologie complet
		$columns = $this->createTopologyFile($topologyFileName,$thirdAxis);
		
		// Si mapping, il faut récupérer les labels mappés. Les codes sont gérés dans createTopologyFile
		if($this->useMappedTopology) $this->mapLabels($topologyFileName,$columns,$thirdAxis);
		
		$this->addHeaderToTopologyFile($topologyFileName,$columns,$thirdAxis);
		
		// On regarde si l'on doit réduire le fichier
		$reduce = false;
		$axe = ($thirdAxis) ? '= 3' : 'IS NULL';
		$query = "SELECT agregation
		FROM sys_definition_network_agregation 
		WHERE family = '".$this->family."'
		AND axe {$axe}
		ORDER BY agregation_rank";
		$result = self::$database->execute($query);
		while($values = self::$database->getQueryResults($result,1)) {
			// A-t-on demandé tous les niveaux d'agrégation et labels de la famille ?
			if(!in_array($values['agregation'],$this->wantedFields) || !in_array($values['agregation'].'_label',$this->wantedFields)) {
				$reduce = true;
				break;
			}
		}
		
		// A-t-on demandé les infos géographiques et le on_off ?
		if($this->CoordsType == 1)
		{
			foreach(Array('azimuth','longitude','latitude','on_off') as $field)
			{
				if(!in_array($field,$this->wantedFields)) {
					$reduce = true;
					break;
				}
			}
		}
		else
		{
			foreach(Array('azimuth','x','y','on_off') as $field)
			{
				if(!in_array($field,$this->wantedFields)) {
					$reduce = true;
					break;
				}
			}		
		}

		// maj 15/10/2009 - MPR :  Ajout des colonnes trx et charge pour Download Topology
		foreach(Array('trx','charge') as  $field)
		{
			if(!in_array($field,$this->wantedFields)) {
				$reduce = true;
				break;
			}
		}
		
		// Si on doit réduire le fichier, on appelle la méthode qui va s'en charger
		if($reduce) {
			$this->removeUselessFieldsInTopologyFile($topologyFileName,$columns,$thirdAxis);
		}

		// 28/07/2011 MMT bz 22987  need to decode the separator done by codeDelimiterInFileBeforeCmd
		$this->decodeDelimiterInFileAfterCmd($this->targetDir.$topologyFileName);

		// On retourne le chemin complet vers le fichier de topologie
		return $this->targetDir.$topologyFileName;
	}

	/*
	*	Supprime les colonnes non demandées par l'utilisateur dans le fichier de topologie
	*/		
	public function removeUselessFieldsInTopologyFile($topologyFile,$colArray,$thirdAxis=false)
	{
		// Fichier de topologie intermédiaire
		$tempTopoFile = $this->targetDir.'temp_topo_'.uniqid().'.csv';
		// Fichier de résultat
		$topologyFilePath = $this->targetDir.$topologyFile;
		
		// récupération du niveau minimum
		$minLevel = ($thirdAxis) ? $this->minLevelThirdAxis : $this->minLevel;

		// Construction de la commande awk qui va faire le sale boulot
		// 11:29 04/12/2009 GHX
		// Ajout du condition pour ne pas mettre dans le fichier de topo les NE vides
		// 09:32 23/12/2009 GHX12657
		// Récupère le niveau minimum démandé
		$minLevelWanted = $this->getNaMinWanted();
		// On ne prend pas les lignes où le NE min demandé est vide
		$cmd = 'cat '.$topologyFilePath.' | awk \'BEGIN{FS="'.$this->separator.'";OFS=FS}NR>1 && $'.$colArray[$minLevel].' != "" && $'.$colArray[$minLevelWanted].' != "" {print ';
		
		// On dit à awk quelles colonnes on veut garder
		$separator = '';
		$newCols = Array();
		foreach($this->wantedFields as $level)
		{
			// Ofsset de la colonne à conserver
			$offset = $colArray[$level];
			$cmd .= $separator.'$'.$offset;			
			$newCols[$level] = $offset;
			
			// Calcul du séparateur
			$separator = ($separator == '') ? '"'.$this->separator.'"' : $separator;
		}
		
		// Chaine correspondant à une ligne vide
		$emptyLine = str_repeat($this->separator,count($newCols)-1);
		
		// Fin de la commande awk
		$cmd .= '}\' > '.$tempTopoFile;
		exec($cmd);

		// On réécrase notre fichier de départ en éliminant les doublons
		//$cmd = 'cat '.$tempTopoFile.' | sort | uniq | sed "/^'.$emptyLine.'$/d" > '.$topologyFilePath.' && rm -f '.$tempTopoFile;
		// 26/11/2009 BBX
		// On change la technique de dédoublonnage pour gérer les caractères spéciaux. BZ 12657
		// 11:28 04/12/2009 GHX
		// Modification de la correction du BZ 12657
		// Suppression du awk de la commande car si on sélectionnait tous les NA et qu'on n'avait de Network (par exemple) le fichier généré était vide
		$cmd = 'cat '.$tempTopoFile.' | sort -u | sed "/^'.$emptyLine.'$/d" > '.$topologyFilePath.' && rm -f '.$tempTopoFile;
		exec($cmd);
		// Fin BZ 12657
		
		// Nouveau header
		$this->addHeaderToTopologyFile($topologyFile,$newCols,$thirdAxis);
	}
	
	/**
	*	Ajoute le header à la topologie
	**/	
	public function addHeaderToTopologyFile($topologyFile,$colArray,$thirdAxis=false)
	{
		$topologyFile = $this->targetDir.$topologyFile;
		$header = '';
		$axe = ($thirdAxis) ? '= 3' : 'IS NULL';
		foreach($colArray as $level => $position)
		{
			// maj 15/10/2009 - MPR :  Ajout des colonnes trx et charge pour Downnload Topology
			if(in_array($level,Array('trx','charge','azimuth','longitude','latitude','on_off','x','y')))
			{
				// Si l'élément n'est pas un NA mais un élément de topologie ou on_off, on garde l'élément tel quel
				$libelle = $level;
			}
			else
			{
				// Sinon on récupère son label
				$suffixe = '';
				$searchString = $level;
				if(substr_count($level , '_label') > 0) 
				{
					$suffixe = ' label';
					$searchString = substr($level,0,strpos($level,'_label'));
				}
				$query = "SELECT agregation_label 
				FROM ".self::NETWORK_AGR_TABLE."
				WHERE family = '".$this->family."'
				AND agregation = '{$searchString}'
				AND axe {$axe}";
				$libelle = self::$database->getOne($query).$suffixe;
			}
			$header .= ($header == '') ? $libelle : $this->separator.$libelle;
		}
		// Copie de notre topologie vers un fichier temporaire
		$tempTopoFile = $this->targetDir.'temp_topo_'.uniqid().'.csv';
		$cmd = 'cp '.$topologyFile.' '.$tempTopoFile;
		exec($cmd);
	
		// Ajout du Header
		$cmd = 'cat '.$tempTopoFile.' | awk \'BEGIN{FS="'.$this->separator.'";OFS=FS;print "'.$header.'"}{print $0}\' > '.$topologyFile;
		// Suppression du fichier temporaire
		$cmd .= ' && rm -f '.$tempTopoFile;
		exec($cmd);
	}
	
	/**
	*	Construit le fichier de topologie complet
	*	@param string : nom du fichier de topologie
	*	@param bool : vrai pour troisième axe, faux par défaut
	**/	
	public function createTopologyFile($topologyFileName='topology.csv',$thirdAxis=false)
	{
        // 24/11/2011 BBX
        // BZ 24832 : On place toutes les commandes dans un tableau
        // afin de tout exécuter à la fin dans un seul exec
        $commandsToExecute = array();
		
		// Récupération du niveau minimum de la famille	
		$minLevel = $thirdAxis ? $this->minLevelThirdAxis : $this->minLevel;		
		// Récupération de tous les NA supérieur au niveau minimum
		$naLevels = $thirdAxis ? $this->NALevelsThirdAxis : $this->NALevels;
	
		// Détermination du code à récupérer (mappé ou non)
		$NaQuery = 'eor_id';
		if($this->useMappedTopology)
			$NaQuery = 'CASE WHEN (eor_id_codeq IS NOT NULL) THEN eor_id_codeq ELSE eor_id END';
		// Méthode de récupération des labels
		$labelQuery = "eor_label";
		if($this->useCodesIfEmpty)
			$labelQuery = "CASE WHEN eor_label IS NULL THEN (".$NaQuery.") ELSE eor_label END";
		// Création du fichier de topologie du niveau minimum : NA;NA_label
		$fileName = $this->targetDir.'temp_topo_export_'.$minLevel.'.csv';
		
		if( file_exists($fileName) )
			unlink($fileName);
		
                // 07/06/2010 BBX
                // DE Bypass : Export des éléments virtuels
                // 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
                $virtuelExport = '';
                if(!$this->exportVirtualElements)
                    $virtuelExport = "AND ".NeModel::whereClauseWithoutVirtual();

		// 16:35 27/08/2009 GHX
		$query = "COPY (
		SELECT
			".$NaQuery." AS ".$minLevel.",
			".$labelQuery." AS ".$minLevel."_label
		FROM ".self::OBJECT_REF_TABLE."
		WHERE eor_obj_type = '".$minLevel."'
		$virtuelExport
		ORDER BY eor_id)
		TO stdout
		WITH CSV DELIMITER AS '".$this->separator."' NULL AS ''";

		$cmd = sprintf(
			'env PGPASSWORD=%s '.PSQL_DIR.'/psql -U %s %s -h %s -c "%s" >> "%s"',
			$this->infosProduct['sdp_db_password'],
			$this->infosProduct['sdp_db_login'],
			$this->infosProduct['sdp_db_name'],
			$this->infosProduct['sdp_ip_address'],
			$query,
			$fileName
		);
		// 14/12/2011 ACS BZ 25130 The topology file must be created at the start of the script. Not at the end with "$commandsToExecute"
		exec($cmd);

		// 28/07/2011 MMT bz 22987  need to escape the separator char in the csv values encapsulated by '"' by SQL csv
		// or some commands will fail (join, awk..)
		$this->codeDelimiterInFileBeforeCmd($fileName);
	

		// Fichier de jointure	
		$resultFile = '';			
		// Fichier temporaire pour les tris
		$sortFile = $this->targetDir.'temp_sort_file.csv';		
		// La colonne à joindre dans le fichier de topo sera toujours la première (NA_source)
		$joinCol2 = 1;
		// Mémorise les position des colonnes des NA
		$numCol = Array();
		$numCol[$minLevel] = 1;
		$numCol[$minLevel.'_label'] = 2;			
		// Numéro de la prochaine colonne à traiter
		$nbJoin = 3;
		
		// Détermination du code à récupérer (mappé ou non)
		$NaQuery = 'ref.eor_id';
		if($this->useMappedTopology)
			$NaQuery = 'CASE WHEN (ref.eor_id_codeq IS NOT NULL) THEN ref.eor_id_codeq ELSE ref.eor_id END';
		$NaSourceQuery = 'b.eor_id';
		if($this->useMappedTopology)
			$NaSourceQuery = 'CASE WHEN (b.eor_id_codeq IS NOT NULL) THEN b.eor_id_codeq ELSE b.eor_id END';
		// Méthode de récupération des labels
		$labelQuery = "ref.eor_label";
		if($this->useCodesIfEmpty)
			$labelQuery = "CASE WHEN ref.eor_label IS NULL THEN (".$NaQuery.") ELSE ref.eor_label END";

		// Création des fichiers pour tous les niveaux supérieurs : NA_source;NA;NA_label
		// Jointures
		foreach($naLevels as $values) 
		{
			// 16:35 27/08/2009 GHX
			// Fichier cible
			
			$fileName = $this->targetDir.'temp_topo_export_'.$values['agregation'].'.csv';
			
			if( file_exists($fileName) )
				unlink($fileName);
			
			$query = "COPY (
			SELECT
				".$NaSourceQuery." AS ".$values['level_source'].", 
				".$NaQuery." AS ".$values['agregation'].",
				".$labelQuery." AS ".$values['agregation']."_label
			FROM 
				".self::ARC_REF_TABLE." a, 
				".self::OBJECT_REF_TABLE." b,
				".self::OBJECT_REF_TABLE." ref
			WHERE 
				a.eoar_id = b.eor_id
				AND a.eoar_id_parent = ref.eor_id
				AND b.eor_obj_type = '".$values['level_source']."'
				AND a.eoar_arc_type = '".$values['level_source']."|s|".$values['agregation']."'
				AND ref.eor_obj_type = '".$values['agregation']."'
			ORDER BY b.eor_id, ref.eor_id)
			TO stdout
			WITH CSV DELIMITER AS '".$this->separator."' NULL AS ''";
			
			$cmd = sprintf(
				'env PGPASSWORD=%s '.PSQL_DIR.'/psql -U %s %s -h %s -c "%s" >> "%s"',
				$this->infosProduct['sdp_db_password'],
				$this->infosProduct['sdp_db_login'],
				$this->infosProduct['sdp_db_name'],
				$this->infosProduct['sdp_ip_address'],
				$query,
				$fileName
			);
			//exec($cmd);
			
                        $commandsToExecute[] = $cmd;
			
			// Mémorisation de la position de la colonne du niveau
			if(!isset($numCol[$values['agregation']])) 
			{
				$numCol[$values['agregation']] = $nbJoin;
				$numCol[$values['agregation'].'_label'] = $nbJoin+1;
				$nbJoin += 2;
			}	
			
			// Calcul de la colonne à joindre dans le fichier source
			$joinCol1 = $numCol[$values['level_source']];	
			
			// Si aucun fichier de résultat de jointure n'existe, la source sera le fichier de niveau minimum
			if($resultFile == '')
				$fileSource = $this->targetDir.'temp_topo_export_'.$values['level_source'].'.csv';
			// Sinon la source sera le dernier fichier de résultat de jointure
			else $fileSource = $resultFile;

			// Nom du fichier de topo
			$fileName = $this->targetDir.'temp_topo_export_'.$values['agregation'].'.csv';	
			// Nom du fichier de résultat de jointure
			$resultFile = $this->targetDir.'temp_topo_'.uniqid().'.csv';
			
			// Dos2unix
			//exec('dos2unix '.$fileSource);
			//exec('dos2unix '.$fileName);
			
			$commandsToExecute[] = 'dos2unix '.$fileSource;
			$commandsToExecute[] = 'dos2unix '.$fileName;
			
			// On trie le fichier sur la colonne à joindre
			$cmd = 'sort -t"'.$this->separator.'" -k'.$joinCol1.','.$joinCol1.' '.$fileSource.' > '.$sortFile;
			$cmd .= ' && mv -f '.$sortFile.' '.$fileSource;
			//exec($cmd);                        
            $commandsToExecute[] = $cmd;
			
			// On trie le fichier sur la colonne à joindre
			$cmd = 'sort -t"'.$this->separator.'" -k'.$joinCol2.','.$joinCol2.' '.$fileName.' > '.$sortFile;
			$cmd .= ' && mv -f '.$sortFile.' '.$fileName;
			//exec($cmd);
            $commandsToExecute[] = $cmd;
			
			// Détermination du format	
			$colOrder = '';
			
			// Colonnes déjà jointes avant la colonne de jointure
			for($c = 1; $c < $joinCol1; $c ++) {
				$colOrder .= ($colOrder == '') ? '1.'.$c : ' 1.'.$c;
			}
			
			// Colonne de jointure
			$colOrder .= ($colOrder == '') ? '0' : ' 0';
			
			// Colonnes déjà jointes après la colonne de jointure
			for($c = ($joinCol1+1); $c < ($nbJoin-2); $c++) {
				$colOrder .= ($colOrder == '') ? '1.'.$c : ' 1.'.$c;
			}
			
			// Colonne code et colonne label du fichier à joindre
			$colOrder .= ' 2.2 2.3';
			
			// Exécution de la jointure
			$cmd = 'join -t "'.$this->separator.'" -a1 -1 '.$joinCol1.' -2 '.$joinCol2.' -o "'.$colOrder.'" '.$fileSource.' '.$fileName.' > '.$resultFile;

                        //exec($cmd);
                        $commandsToExecute[] = $cmd;
		}

		// maj 03/08/2010 - MPR Correction du BZ 16841 : Si un seul NA pour la famille, le fichier en entrée n'est pas le même
		if(count($naLevels) == 0 )
		{
			$resultFile = $fileName;
		}
		// 25/06/2012 ACS BZ 27524 Download topo is not always working
		$completeFilePath = $this->targetDir.$topologyFileName;
		if (file_exists($completeFilePath)) {
			unlink($completeFilePath);
		}
		// Fichier Final, trié sur le NA min s'il vous plait :)
		$cmd = 'sort -t"'.$this->separator.'" -k'.$numCol[$minLevel].','.$numCol[$minLevel].' '.$resultFile;
		$cmd .= ' > '.$completeFilePath;
		
		//exec($cmd);
		$commandsToExecute[] = $cmd;
		
		// Détermination du code à récupérer (mappé ou non)
		$NaQuery = 'r.eor_id';
		if($this->useMappedTopology)
			$NaQuery = 'CASE WHEN (r.eor_id_codeq IS NOT NULL) THEN r.eor_id_codeq ELSE r.eor_id END';

		// Détermination du type de coordonnées à récupérer
        //  13/09/2010 MPR : Correction du bz17815 - Fichier de topologie incorrect
		$coordsQuery = "p.eorp_x AS x,p.eorp_y AS y";
		if($this->CoordsType == 1)
			$coordsQuery = "p.eorp_longitude AS longitude,p.eorp_latitude AS latitude";
		// maj 15/10/2009 - MPR :  Ajout des colonnes trx et charge pour Downnload Topology
		$paramsErlangQuery = "p.eorp_trx as trx, p.eorp_charge as charge";

		// On va maintenant récupérer les colonnes Longitude, Latitude, azimuth, on/off
		$geoFile = $this->targetDir.'temp_topo_geo_'.uniqid().'.csv';
		if( file_exists($geoFile) )
			unlink($geoFile);
		
                // 07/09/2010 BBX
                // DE Bypass : Export des éléments virtuels
                // 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
                $virtuelExport = '';
                if(!$this->exportVirtualElements)
                    $virtuelExport = "AND ".NeModel::whereClauseWithoutVirtual('r');

		// maj 15/10/2009 - MPR :  Ajout des colonnes trx et charge pour Downnload Topology
		$query = "COPY (
		SELECT 
			".$NaQuery." AS ".$minLevel.",
			p.eorp_azimuth AS azimuth,
			".$coordsQuery.",
			".$paramsErlangQuery.",
			CASE WHEN r.eor_on_off IS NULL THEN 0 ELSE r.eor_on_off END AS on_off
		FROM 
			".self::OBJECT_REF_TABLE." r LEFT JOIN ".self::OBJECT_REF_PARAM_TABLE." p 
		ON (r.eor_id = p.eorp_id )			
		WHERE r.eor_obj_type = '".$minLevel."' 
		$virtuelExport
		ORDER BY r.eor_id)
		TO stdout
		WITH CSV DELIMITER AS '".$this->separator."' NULL AS ''";
				
		$cmd = sprintf(
			'env PGPASSWORD=%s '.PSQL_DIR.'/psql -U %s %s -h %s -c "%s" >> "%s"',
			$this->infosProduct['sdp_db_password'],
			$this->infosProduct['sdp_db_login'],
			$this->infosProduct['sdp_db_name'],
			$this->infosProduct['sdp_ip_address'],
			$query,
			$geoFile
		);

		//exec($cmd);
        $commandsToExecute[] = $cmd;

		// On trie le fichier sur la colonne à joindre
		$cmd = 'sort -t"'.$this->separator.'" -k1,1 '.$geoFile.' > '.$sortFile;
		$cmd .= ' && mv -f '.$sortFile.' '.$geoFile;
		//exec($cmd);
        $commandsToExecute[] = $cmd;

		// On joint ce fichier avec le fichier de topology
		$resultFile = $this->targetDir.'temp_topo_'.uniqid().'.csv';
		$cmd = 'join -t "'.$this->separator.'" -a1 -1 1 -2 1 '.$this->targetDir.$topologyFileName.' '.$geoFile.' > '.$resultFile;
		$cmd .= ' && mv -f '.$resultFile.' '.$this->targetDir.$topologyFileName;
		//exec($cmd);
        $commandsToExecute[] = $cmd;

		// On ajoute les colonnes au tableau de colonnes
		// maj 12/08/2009 - MPR : Correction du bug 10916 - Les id des colonnes prennent -1
		$numCol['azimuth'] = $nbJoin;
		// Selon le type de coordonnées recherchées
		if($this->CoordsType == 1) {
			$numCol['longitude'] = $nbJoin+1;
			$numCol['latitude'] = $nbJoin+2;
		}
		else {
			$numCol['x'] = $nbJoin+1;
			$numCol['y'] = $nbJoin+2;		
		}
		// maj 15/10/2009 - MPR :  Ajout des colonnes trx et charge pour Downnload Topology
		$numCol['trx'] = $nbJoin+3;
		$numCol['charge'] = $nbJoin+4;	
		$numCol['on_off'] = $nbJoin+5;
	
        // 24/11/2011 BBX
		// BZ 24832 : Suppression des fichiers temporaires
		//exec('rm -f '.$this->targetDir.'temp_topo_*.csv');
		//exec('rm -f '.$sortFile);
		$commandsToExecute[] = 'rm -f '.$this->targetDir.'temp_topo_*.csv';
		$commandsToExecute[] = 'rm -f '.$sortFile;

		$allCommands = implode(' && ',$commandsToExecute);
		exec($allCommands);
	
		// On retourne le tableau de correspondance qui va permettre de savoir à quel NA correspond quelle colonne
		return $numCol;
	}

	/**
	 * Mappe les labels du fichier de topologie
     *
	 * @param string : fichier de topologie à modifier
	 * @param array : tableau des index de colonnes
	 * @param bool : vrai pour 3ème axe
     *
     * 29/07/2010 BBX : bz12973, recodage de la méthode pour permettre le mapping de NA sur 2 produits ayant des NA min différents.
     * 04/08/2010 BBX : bz12973, correction de la fonction afin d'avoir les labels également sur les éléments non mappés.
     * 22/09/2011 OJT : bz23631, mapping incorrect dans les data exports ayant l'option "Add Network Topology Reference"
	 */
	public function mapLabels($topologyToMap,$refCols,$thirdAxis=false)
	{
		// On récupère l'id du produit Master topo
		$masterTopo   = getTopoMasterProduct();
		$joinCol      = -1;
        $joinColLabel = -1;

		// Si le produit courant n'est pas le master topo, on procède au mapping
		if($masterTopo['sdp_id'] != $this->product)
		{
			// Connexion au produit master topo
			self::$database = Database::getConnection($masterTopo['sdp_id']);

			// Recherche du n° de la colonne de jointure
			foreach($this->wantedFields as $field)
			{
				if(substr_count($field,'_label') == 0)
				{
					$joinCol = $refCols[$field];
					break;
				}
			}
			// Récupération de l'élément mappé et de son label
			$query = "SELECT eor_id, eor_label
				FROM edw_object_ref
				WHERE eor_obj_type = '$field'
				AND eor_on_off = 1
				AND eor_blacklisted = 0";

			// Stockage des informations récupérées dans le tableau $mapping
			$mapping = array();
			$result = self::$database->execute($query);
			while($row = self::$database->getQueryResults($result,1))
				$mapping[$row['eor_id']] = !empty($row['eor_label']) ? $row['eor_label'] : $row['eor_id'];

			// Recherche du n° de la colonne du label à mapper
			foreach($this->wantedFields as $field)
			{
				if( substr_count( $field,'_label' ) > 0 )
				{
					$joinColLabel = $refCols[$field];
					break;
				}
			}

			/*
			 * Test si une jointure entre les colonnes a été trouvées.
			 * Ce n'est pas forcement le cas dans le cas d'un Data Export
			 * avec l'otion 'Use code network elements' 
			 */
			if( $joinColLabel != -1 && $joinCol != -1 )
			{
				// Mise en mémoire du fichier de topologie d'origine
				$topologySrc = file($this->targetDir.$topologyToMap);

				$topologyDst = "";

				// Mapping des labels...
				if(is_array($topologySrc) && (count($topologySrc) > 0))
				{
					foreach($topologySrc as $line)
					{
						if(trim($line) == '') continue;
						$columns = explode($this->separator,$line);
						if(array_key_exists($columns[($joinCol-1)], $mapping))
							$columns[($joinColLabel-1)] = $mapping[$columns[($joinCol-1)]];
						$topologyDst .= implode($this->separator,$columns);
					}

					// Mise à jour du fichier de topologie avec les labels mappés
					file_put_contents($this->targetDir.$topologyToMap, $topologyDst);
				}
		  
			}
			// Reconnexion au produit courant
			self::$database = Database::getConnection($this->product);
		}
	}
	
	/** 
	 * Retourne le niveau minimum dans les NA demandés
	 *
	 * @return string
	 */
	private function getNaMinWanted ()
	{
		// 14:14 05/01/2010 GHX
		// Correction du BZ 13602
		// Ajout de la condition sur la famille 
		return self::$database->getOne("SELECT agregation FROM sys_definition_network_agregation WHERE agregation IN ('".implode("','",$this->wantedFields)."') AND family = '".$this->family."' ORDER BY agregation_level ASC LIMIT 1");
	}

        /**
         * 07/09/2010 BBX : DE Bypass
         * permet de définir si on doit exporter les éléments virtuels
         * @param boolean $export
         */
        public function setExportVirtualElement($export = true)
        {
            $this->exportVirtualElements = (boolean)$export;
        }


   /**
	 * 28/07/2011 MMT bz 22987
	 * some executed commands like join or awk will have probleme if values of the CSV file contain the separator
	 * In order to prevent that we code the separator in the values in the file prior to command run
	 * then we decode via decodeDelimiterInFileAfterCmd once command executed
	 *
	 * @param String $filename path of the csv file to be coded
	 */
	private function codeDelimiterInFileBeforeCmd($filename){
	   self::replaceInCsvValues($filename,$this->separator,self::CVS_SEPARATOR_ENCODE,$this->separator);
	}

	/**
	 * 28/07/2011 MMT bz 22987
	 * decode file coded via codeDelimiterInFileBeforeCmd
	 *
	 * @param String $filename path of the csv file to be decoded
	 */
	private function decodeDelimiterInFileAfterCmd($filename){
		self::replaceInCsvValues($filename,self::CVS_SEPARATOR_ENCODE,$this->separator,$this->separator);
	}

	/**
	 * 28/07/2011 MMT bz 22987
	 * replace string by another in csv and save it to csv format
	 * if the value contain the separator on save, the value is encapsulated with enclusor characters "
	 * so the CSV is always valid
	 * The file is untouched if the $search is not found
	 * 28/03/2013 GFS - BZ#32769 - [SUP][TA Cigale IU][Vidéotron][AVP 34177]: After upgrade data export generation is crashing
	 * Function rewritten
	 *
	 * @param <type> $filename path of the csv file to be modified
	 * @param <type> $search patern to look up
	 * @param <type> $replace string to replace with
	 * @param <type> $csvSeparator separatior character (, or ;)
	 */
	public static function replaceInCsvValues($filename,$search,$replace,$csvSeparator)
	{
		// 28/03/2013 GFS - BZ#32769 - [SUP][TA Cigale IU][Vidéotron][AVP 34177]: After upgrade data export generation is crashing
		if(!empty($filename) && !empty($search) && !empty($replace) && !empty($csvSeparator) && file_exists($filename)){

			$in = fopen($filename, "r");
			$tempSwapFile = dirname($filename).'/temp_swap_'.uniqid().'.csv';
			$out = fopen($tempSwapFile, "w");

			while ($line = fgetcsv($in,0,$csvSeparator,self::CVS_ENCLOSURE_CHAR)) {
				foreach ($line as $k => $value) {
					if(strpos($value,$search) !== FALSE){
						// perform the replacement and store to out array
						$line[$k] = self::getSafeCsvValue(str_replace($search, $replace, $value), $csvSeparator);
					}
				}
				fwrite($out, implode($csvSeparator, $line)."\n");
			}
			fclose($in);
			fclose($out);
			rename($tempSwapFile, $filename);			
		}
	}

	/**
	 * return a CSV safe value to write in a CSV file defined by the given $csvSeparator
	 * the returned value is encaspulated by enclosure characters " if $csvSeparator is found in the value
	 *
	 * @param String $value value to become csv safe
	 * @param String $csvSeparator csv separator (, or ;)
	 * @return string safe csv value from gien value
	 */
	public static function getSafeCsvValue($value, $csvSeparator){
		$ret = $value;
		if(strpos($value,$csvSeparator) !== FALSE){
			$ret =  self::CVS_ENCLOSURE_CHAR.$value.self::CVS_ENCLOSURE_CHAR;
		}
		return $ret;
	}

}
?>