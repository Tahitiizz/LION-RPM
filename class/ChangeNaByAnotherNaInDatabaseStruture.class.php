<?php
/**
 * Cette classe permet de change le nom d'un niveau d'agrégation d'une famille par un autre nom.
 * Les changements a appliquer sont nombreuses
 *	- Renomage des tables de données
 *	- Renomage des colonnes dans les tables de données
 *	- Changement du paramétrage en base
 *	- Changement en topologie
 *	- Changement dans les alarms
 *	- Changement dans divers tables
 *
 * 05/08/2010 MPR : Correction du bz 17003
 *          - Réécriture complète de la fonction changeTopology()
 *              -> On ne créé pas de doublons en renommant le NA
 *
 * 08/07/2011 MMT bz 21896: ajout du traitement des tables sys_definition_corporate, sys_definition_categorie, edw_object_arc
 *		edw_object et sys_definition_network_aggregation_bckp + gestion du corporate lors de la migration
 * 22/01/2013 GFS Bug 31298 - [SUP][TA Cigale IU][AVP 32128][ZAIN HQ] : Error on check KPI formula after migration sai-cell
 *
 * @author GHX
 * @version CB5.0.0.00
 * @since CB5.0.0.00
 */
class ChangeNaByAnotherNaInDatabaseStruture
{
	/**
	 * Constante de classe qui permet d'exécuter où non les changements
	 * Sens servir uniquement pour les devs
	 */
	const APPLY_CHANGE = true;

	/**
	 * Identifiant du produit
	 * @var int (default null soit le produi courant)
	 */
	private $_idProduct = '';

	/**
	 * Instance à la base de donnée
	 * @var DatabaseConnection
	 */
	private $_db = null;

	/**
	 * Nom de la famille sur laquelle on veut change le nom d'un niveau d'agrégation par un autre
	 * @var string
	 */
	private $_family = null;

	/**
	 * Identifiant de la famille
	 * @var int
	 */
	private $_idFamily = null;

	/**
	 * Axe sur lequel se trouve le niveau d'agrégatoin
	 * var int
	 */
	private $_axe = null;

	/**
	 * True si la famille possède un troisième axe
	 * var boolean
	 */
	private $_hasAxe3 = false;

	/**
	  * Nom de l'ancien niveau d'agrégation
	  * var string
	  */
	private $_oldNa = null;

	/**
	  * Nom du nouvel niveau d'agrégation
	  * var string
	  */
	private $_newNa = null;

	/**
	  * Nom du nouvel label pour le niveau d'agrégation
	  * var string
	  */
	private $_newNaLabel = null;

	/**
	 * 08/07/2011 MMT bz 21896
	  * Nom du nouveau nom pour le niveau d'agrégation (colonne agreagtion_name dans sys_definition_network_aggregation)
	  * var string
	  */
	private $_newNaAgName = null;

	/**
	 * Chemin du fichier de log
	 * @var string
	 */
	private $_fileLog = null;

	/**
	 * Format des logs :
	 *	- html
	 *	- text
	 * var string
	 */
	private $_formatLog = 'html';

	/**
	 * 08/07/2011 MMT bz 21896
	 * le produit est-il un corporate
	 * @var bool
	 */
	private $_isCorporate = false;

	/**
	 * Constructeur
	 *
	 * @param int $idProduct identifiant d'un produit (default null soit le produit courant)
	 */
	public function __construct( $idProduct = '')
	{
		$this->_idProduct = $idProduct;
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
		$this->_db = Database::getConnection($idProduct);
	} // End function __construct

	/**
	 * Spécifie la famille sur laquelle il faut changer le niveau d'agrégation
	 *
	 * @param string $family nom de la famille
	 */
	public function setFamily ( $family )
	{
		$resultIdFamily = $this->_db->getOne("SELECT rank FROM sys_definition_categorie WHERE family = '{$family}' LIMIT 1");
		if ( $this->_db->getNumRows() == 0 )
			throw new Exception("Family '{$family}' does not exist");

		$this->_family = $family;
		$this->_idFamily = $resultIdFamily;
	} // End function setFamily

	/**
	 * Spécifié le nom de l'ancien niveau d'agrégation à remplacer
	 *
	 * @param string $oldNa : nom du niveau d'agrégation
	 */
	public function setOldNa ( $oldNa )
	{
		if ( $this->_family === null )
		{
			$this->_db->execute("SELECT axe FROM sys_definition_network_agregation WHERE agregation = '{$oldNa}'");
			if ( $this->_db->getNumRows() == 0 ) {
				// 22/01/2013 GFS Bug 31298 - [SUP][TA Cigale IU][AVP 32128][ZAIN HQ] : Error on check KPI formula after migration sai-cell
				if ($this->_db->getOne("SELECT count(*) FROM information_schema.tables WHERE table_name ilike 'edw%_{$oldNa}_%'") == 0)	
					throw new Exception("Network aggregation '{$oldNa}' does not exist");
			}
		}
		else
		{
			$this->_db->getOne("SELECT axe FROM sys_definition_network_agregation WHERE agregation = '{$oldNa}' AND family = '{$this->_family}'");
			if ( $this->_db->getNumRows() == 0 ) {
				// 22/01/2013 GFS Bug 31298 - [SUP][TA Cigale IU][AVP 32128][ZAIN HQ] : Error on check KPI formula after migration sai-cell
				if ($this->_db->getOne("SELECT count(*) FROM information_schema.tables WHERE table_name ilike 'edw%_{$oldNa}_%'") == 0)
					throw new Exception("Network aggregation '{$oldNa}' does not exist for the family '{$this->_family}'");
			}
		}
		$this->_oldNa = $oldNa;
	} // End function setOldNa

	/**
	 * Spécifié le nom du nouvel niveau d'agrégation qui remplacera l'ancien. Si aucun label n'est précisé, le label de l'ancien niveau d'agrégation
	 * sera gardé.
	 * 08/07/2011 MMT bz 21896 ajout paramètre newNaAgName
	 *
	 * @param string $newNa : nom du niveau d'agrégation
	 * @param string $newNaLabel :  label du niveau d'agrégation (default null)
	 * @param string $newNaName :  name du niveau d'agrégation (colonne agregation_name) (default null)
	 */
	public function setNewNa ( $newNa, $newNaLabel = null, $newNaName = null )
	{
		$this->_newNa = $newNa;
		if ( $newNaLabel !== null )
		{
			if ( trim($newNaLabel) == '' )
				throw new Exception ('The label for the new network aggregation does not empty');

			$this->_newNaLabel = trim($newNaLabel);
		}
		//08/07/2011 MMT bz 21896 ajout paramètre _newNaAgName
		if ( $newNaName !== null )
		{
			if ( trim($newNaLabel) == '' )
				throw new Exception ('The agregation name for the new network aggregation can not be empty');

			$this->_newNaAgName = trim($newNaName);
		}
	} // End function setNewNa

	/**
	 * Spécifie le nom du fichier de log et le format. Si aucun fichier de log n'est spécifié au log ne sera généré
	 *
	 * @param string $filename chemin vers le fichier de log
	 * @param string $format format de sortie des logs soit "html" ou "text" (default html)
	 */
	public function setFileLog ( $filename, $format = 'html' )
	{
		$this->_fileLog = $filename;

		if ( $format == 'html' || $format = 'text' )
			$this->_formatLog = $format;
	} // End function setFileLog

	/**
	 * Applique les changements : c'est à dire remplace l'ancien niveau d'agrégation par le nouveau
	 *
	 */
	public function applyChange ()
	{
		try
		{
			$this->log("<h1>Change le niveau d'agrégation '{$this->_oldNa}' par '{$this->_newNa}' pour la famille {$this->_family}</h1><br />");
			/*
				Vérifie le paramétrage
			*/
			$this->checkParameters();

			/*
				Initilisation de quelques variables
			*/
			$resultAxe = $this->_db->getOne("SELECT axe FROM sys_definition_network_agregation WHERE agregation = '{$this->_oldNa}' AND family = '{$this->_family}'");
			$this->_axe = (empty($resultAxe) ? 1 : 3);
			$this->_hasAxe3 = get_axe3($this->_family, $this->_idProduct);

			//08/07/2011 MMT bz 21896 ajout gestion corporate
			$iscorpo = $this->_db->getOne("SELECT COUNT(relname) FROM pg_class WHERE relname='sys_definition_network_agregation_bckp'");
			$this->_isCorporate = ($iscorpo == 1);

			/*
				Applique les changements
			*/
			$this->changeDataTables();
			$this->changeTopology();
			$this->changeAlarm();
			$this->changeComment();
			$this->changeExportRawKpi();
			$this->changeSelecteur();
			$this->changeDashboard();
			$this->changeSetting();
		}
		catch ( Exception $e )
		{
			$this->log('<br /><h1 style="color:red">ERROR : '.$e->getMessage().'</h1>');
			echo $e->getMessage();
		}
	} // End function applyChange

	/**
	 * Vérifie si les changements peuvent être appliqués
	 */
	private function checkParameters ()
	{
		/*
			On vérifie si tous les paramètres nécessaires ont été spécifiés
		*/
		$noValue = array();

		if ( $this->_family === null )
			$noValue[] = 'family';
		if ( $this->_oldNa === null )
			$noValue[] = 'old network aggregation';
		if ( $this->_newNa === null )
			$noValue[] = 'new network aggregation';

		if ( count($noValue) > 0 )
			throw new Exception("The parameters following are not specified : ".implode(', ', $noValue));

		/*
			On vérifie si le niveau d'agrégation existe bien pour la famille spécifiée
		*/
		// 22/01/2013 GFS Bug 31298 - [SUP][TA Cigale IU][AVP 32128][ZAIN HQ] : Error on check KPI formula after migration sai-cell
		// On a encore des tables avec le vieux NA tout pourri ?
		$this->_db->execute("SELECT * FROM pg_tables t WHERE t.tablename ~ '^edw_[a-z]+_{$this->_family}_[a-z0-9_]+_{$this->_oldNa}_[a-z0-9_]+$' AND t.schemaname = 'public'");
		$stillSomeTablesWithOldNa = $this->_db->getNumRows();
		// On a encore des traces du vieux NA tout flettri dans la table de conf ?
		$this->_db->execute("SELECT axe FROM sys_definition_network_agregation WHERE agregation = '{$this->_oldNa}' AND family = '{$this->_family}'");
		$sillTracesOfOldNa = $this->_db->getNumRows();
		// Nouveau NA bien présent ?
		$this->_db->execute("SELECT axe FROM sys_definition_network_agregation WHERE agregation = '{$this->_NewNa}' AND family = '{$this->_family}'");
		$newNaIsPresent = $this->_db->getNumRows();		
		
		// Plus de traces du tout, on stoppe
		if ( $newNaIsPresent && !$stillSomeTablesWithOldNa && !$sillTracesOfOldNa ) {
			throw new Exception("Network aggregation '{$this->_newNa}' already exists and '{$this->_oldNa}' completely removed for the family '{$this->_family}'");
		}

		/*
			On vérifie si le label du nouveau niveau d'agrégation n'est pas déjà utilisé par un autre niveau de la famille
		*/
		if ( $this->_newNaLabel !== null )
		{
			$na = $this->_db->execute("SELECT agregation FROM sys_definition_network_agregation WHERE agregation_label = '{$this->_newNaLabel}' AND family = '{$this->_family}' AND agregation != '{$this->_oldNa}'");
			if ( $this->_db->getNumRows() > 0 )
				throw new Exception("Network aggregation label '{$this->_newNaLabel}' already exists for the family '{$this->_family}'");
		}

		//08/07/2011 MMT bz 21896 ajout paramètre _newNaAgName
		/*
			On vérifie si le agg name du nouveau niveau d'agrégation n'est pas déjà utilisé par un autre niveau de la famille
		*/
		if ( $this->_newNaAgName !== null )
		{
			$na = $this->_db->execute("SELECT agregation FROM sys_definition_network_agregation WHERE agregation_name = '{$this->_newNaAgName}' AND family = '{$this->_family}' AND agregation != '{$this->_oldNa}'");
			if ( $this->_db->getNumRows() > 0 )
				throw new Exception("Network aggregation name '{$this->_newNaAgName}' already exists for the family '{$this->_family}'");
		}

	} // End function checkParameters

	/**
	 * Applique les changements sur les tables de données
	 */
	private function changeDataTables ()
	{
		// Récupère le edw_group_table de la famille
		$edwGroupTable = $this->_db->getOne("SELECT edw_group_table FROM sys_definition_group_table WHERE family = '{$this->_family}'");

		if ( $this->_db->getNumRows() == 0 )
			throw new Exception("edw_group_table not found for the family '{$this->_family}'");

		// Récupère les niveaux temporelles déployés pour la familles
		$tas = $this->_db->getAll("SELECT DISTINCT time_agregation FROM sys_definition_group_table_time WHERE id_group_table = {$this->_idFamily}");

		// Récupère les niveaux temporelles déployés pour la familles
		// Si on n'a pas de troisieme axe
		if ( !$this->_hasAxe3 )
		{
			$nas = $this->_db->getAll("SELECT DISTINCT network_agregation FROM sys_definition_group_table_network WHERE id_group_table = {$this->_idFamily} AND network_agregation = '{$this->_oldNa}'");
		}
		else // Si on a un troisieme axe
		{
			// Si le niveau d'agrégatoin est sur le premier axe
			if ( $this->_axe == 1)
			{
				$nas = $this->_db->getAll("SELECT DISTINCT network_agregation FROM sys_definition_group_table_network WHERE id_group_table = {$this->_idFamily}  AND network_agregation ~ '{$this->_oldNa}_.*'");
			}
			else // Si le niveau d'agrégatoin est sur le troisieme axe
			{
				$nas = $this->_db->getAll("SELECT DISTINCT network_agregation FROM sys_definition_group_table_network WHERE id_group_table = {$this->_idFamily}  AND network_agregation ~ '.*_{$this->_oldNa}'");
			}
		}

		$this->log('<br /><b>Renomage des tables de données</b>');

		$tablesUpdated = array();
		foreach ( array('raw', 'kpi') as $type )
		{
			foreach ( $nas as $na )
			{
				$na = $na['network_agregation'];
				foreach ( $tas as $ta )
				{
					$ta = $ta['time_agregation'];

					// Nom de l'ancienne table
					$oldTable = sprintf('%s_%s_%s_%s', $edwGroupTable, $type, $na, $ta);
					$tablesUpdated[] = $oldTable;
					// Nom de la nouvelle table
					$newTable = str_replace('_'.$this->_oldNa.'_', '_'.$this->_newNa.'_', $oldTable);

					$this->log('<br /><br />Table : "'.$oldTable.'"');

					/*
						Suppression des index
					*/
					$this->log('<br />&nbsp;&nbsp;-> Suppression des index');
					$indexes = $this->_db->getIndexes($oldTable);

					if ( count($indexes) > 0 )
					{
						foreach ( $indexes as $index => $createIndex )
						{
							$sqlDropIndex = sprintf('DROP INDEX %s;', $index);
							$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlDropIndex.'</span>');
							if ( self::APPLY_CHANGE ) $this->_db->execute($sqlDropIndex);
						}
					}
					else
					{
						$this->log(' (aucun index de trouvé)');
					}

					/*
						Renomage de la table
					*/
					$this->log('<br />&nbsp;&nbsp;-> Renome la table en : '.$newTable);
					$sqlRenameTable = sprintf('ALTER TABLE %s RENAME TO %s;', $oldTable, $newTable);
					$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlRenameTable.'</span>');
					if ( self::APPLY_CHANGE ) $this->_db->execute($sqlRenameTable);

					/*
						Renomage des colonnes
					*/
					$this->log('<br />&nbsp;&nbsp;-> La colonne "'.$this->_oldNa.'" en "'.$this->_newNa.'"');
					$sqlRenameColumn = sprintf("ALTER TABLE %s RENAME COLUMN %s TO %s", $newTable, $this->_oldNa, $this->_newNa);
					$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlRenameColumn.'</span>');
					if ( self::APPLY_CHANGE ) $this->_db->execute($sqlRenameColumn);

					/*
						Re-création des index
					*/
					$this->log('<br />&nbsp;&nbsp;-> Re-création des index');
					if ( count($indexes) > 0 )
					{
						foreach ( $indexes as $index => $createIndex )
						{
							$sqlCreateIndex = preg_replace('/'.$this->_oldNa.'/', $this->_newNa, $createIndex);
							$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlCreateIndex.'</span>');
							if ( self::APPLY_CHANGE ) if ( self::APPLY_CHANGE )$this->_db->execute($sqlCreateIndex);
						}
					}
					else
					{
						$this->log(' (aucun)');
					}
				}
			}
		}


		$na_min = $this->_db->getOne("SELECT DISTINCT network_agregation FROM sys_definition_group_table_network WHERE id_group_table = {$this->_idFamily} AND rank = -1 AND data_type = 'raw'");
		$oldTable = sprintf('%s_%s_%s_%s', $edwGroupTable, 'raw', $na_min, 'hour');
		$newTable = str_replace('_'.$this->_oldNa.'_', '_'.$this->_newNa.'_', $oldTable);
		if ( $na_min != $this->_oldNa )
		{
			$newTable = $oldTable;
		}
		if ( !in_array($oldTable, $tablesUpdated) )
		{
			$this->log('<br /><br />Table : "'.$oldTable.'"');
			/*
				Suppression des index
			*/
			$this->log('<br />&nbsp;&nbsp;-> Suppression des index');
			$indexes = $this->_db->getIndexes($oldTable);

			if ( count($indexes) > 0 )
			{
				foreach ( $indexes as $index => $createIndex )
				{
					if ( ereg($this->_oldNa, $createIndex) )
					{
						$sqlDropIndex = sprintf('DROP INDEX %s;', $index);
						$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlDropIndex.'</span>');
						if ( self::APPLY_CHANGE ) $this->_db->execute($sqlDropIndex);
					}
				}
			}
			else
			{
				$this->log(' (aucun index de trouvé)');
			}
			/*
				Renomage des colonnes
			*/
			$this->log('<br />&nbsp;&nbsp;-> La colonne "'.$this->_oldNa.'" en "'.$this->_newNa.'"');
			$sqlRenameColumn = sprintf("ALTER TABLE %s RENAME COLUMN %s TO %s", $newTable, $this->_oldNa, $this->_newNa);
			$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlRenameColumn.'</span>');
			if ( self::APPLY_CHANGE ) $this->_db->execute($sqlRenameColumn);

			/*
				Re-création des index
			*/
			$this->log('<br />&nbsp;&nbsp;-> Re-création des index');
			if ( count($indexes) > 0 )
			{
				foreach ( $indexes as $index => $createIndex )
				{
					if ( ereg($this->_oldNa, $createIndex) )
					{
						$sqlCreateIndex = preg_replace('/'.$this->_oldNa.'/', $this->_newNa, $createIndex);
						$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlCreateIndex.'</span>');
						if ( self::APPLY_CHANGE ) if ( self::APPLY_CHANGE )$this->_db->execute($sqlCreateIndex);
					}
				}
			}
			else
			{
				$this->log(' (aucun)');
			}
		}
		
		// 22/01/2013 GFS Bug 31298 - [SUP][TA Cigale IU][AVP 32128][ZAIN HQ] : Error on check KPI formula after migration sai-cell
		// S'il en reste !
		$result = $this->_db->execute("SELECT tablename, replace(tablename,'{$this->_oldNa}','{$this->_newNa}') as newname
		FROM pg_tables
		WHERE tablename ~ '^edw_[a-z]+_{$this->_family}_[a-z0-9_]+_{$this->_oldNa}_[a-z0-9_]+$'");
		while($row = $this->_db->getQueryResults($result,1)) 
		{
			/*
			 Suppression des index
			*/
			$indexes = $this->_db->getIndexes($row['tablename']);			
			if ( count($indexes) > 0 )
			{
				foreach ( $indexes as $index => $createIndex )
				{
					if ( ereg($this->_oldNa, $createIndex) )
					{
						$sqlDropIndex = sprintf('DROP INDEX %s;', $index);
						$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlDropIndex.'</span>');
						if ( self::APPLY_CHANGE ) $this->_db->execute($sqlDropIndex);
					}
				}
			}
			else
			{
				$this->log(' (aucun index de trouvé)');
			}

			// Traitement
			$this->_db->execute("ALTER TABLE ".$row['tablename']." RENAME TO ".$row['newname']);
			$this->_db->execute("ALTER table ".$row['newname']." RENAME COLUMN {$this->_oldNa} TO {$this->_newNa}");
			
			/*
			 Re-création des index
			*/
			$this->log('<br />&nbsp;&nbsp;-> Re-création des index');
			if ( count($indexes) > 0 )
			{
				foreach ( $indexes as $index => $createIndex )
				{
					if ( ereg($this->_oldNa, $createIndex) )
					{
						$sqlCreateIndex = preg_replace('/'.$this->_oldNa.'/', $this->_newNa, $createIndex);
						$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlCreateIndex.'</span>');
						if ( self::APPLY_CHANGE ) if ( self::APPLY_CHANGE )$this->_db->execute($sqlCreateIndex);
					}
				}
			}
			else
			{
				$this->log(' (aucun)');
			}
		}
		
	} // End function changeDataTables

	/**
	 * Change le paramétrage en base
	 */
	private function changeSetting ()
	{
		$this->log('<br /><br /><b>Changement du paramétrage en base</b>');

		/*
			sys_definition_network_agregation
		*/
		//08/07/2011 MMT bz 21896 ajout de modifs sur de nouvelles tables

		$reqs = array();

		$this->log('<br />&nbsp;&nbsp;-> sys_definition_network_agregation');

		$req = "UPDATE sys_definition_network_agregation SET agregation = '".$this->_newNa."' ";
		if($this->_newNaLabel != null){
			$req .= ", agregation_label = '".$this->_newNaLabel."'";
		}
		if($this->_newNaAgName != null){
			$req .= ", agregation_name = '".$this->_newNaAgName."'";
		}
		$req .= " WHERE family = '".$this->_family."' AND agregation = '".$this->_oldNa."'";
		$reqs[] = $req;

		//08/07/2011 MMT bz 21896 ajout source_default et level_source
		$reqs[] = "UPDATE sys_definition_network_agregation SET source_default = '".$this->_newNa."' WHERE source_default = '".$this->_oldNa."' AND family = '".$this->_family."'";
		$reqs[] = "UPDATE sys_definition_network_agregation SET level_source = '".$this->_newNa."' WHERE level_source = '".$this->_oldNa."' AND family = '".$this->_family."'";

		 foreach ($reqs as $sdnaReq) {
			$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sdnaReq.'</span>');
			if ( self::APPLY_CHANGE ) {
				$this->_db->execute($sdnaReq);
			}

		}

		//08/07/2011 MMT bz 21896 ajout sys_definition_network_agregation_bckp
		if($this->_isCorporate){
			 $this->log('<br />&nbsp;&nbsp;-> sys_definition_network_agregation_bckp');

			 foreach ($reqs as $sdnaReq) {

				//execute the same on sys_definition_network_agregation_bckp
				$sdnabckpReq = str_replace("sys_definition_network_agregation", "sys_definition_network_agregation_bckp", $sdnaReq);
				$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sdnabckpReq.'</span>');
				if ( self::APPLY_CHANGE ) {
					$this->_db->execute($sdnabckpReq);
				}
			 }
		}
		
		//08/07/2011 MMT bz 21896 ajout sys_definition_categorie
		/*
			sys_definition_categorie
		*/
		$this->log('<br />&nbsp;&nbsp;-> sys_definition_categorie');
		$req = "UPDATE sys_definition_categorie SET network_aggregation_min = '".$this->_newNa."' WHERE network_aggregation_min = '".$this->_oldNa."'";
		$req .= " AND family = '".$this->_family."'";
		$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$req.'</span>');
		if ( self::APPLY_CHANGE ) {
			$this->_db->execute($req);
		}

		//08/07/2011 MMT bz 21896 ajout sys_definition_corporate
		if($this->_isCorporate){
			/*
				sys_definition_corporate
			*/
			$this->log('<br />&nbsp;&nbsp;-> sys_definition_corporate');
			$req = "UPDATE sys_definition_corporate SET na_min = '".$this->_newNa."' WHERE na_min = '".$this->_oldNa."'";
			$req .= " AND id_group_table IN (SELECT id_ligne FROM sys_definition_group_table WHERE family = '".$this->_family."')";
			$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$req.'</span>');
			if ( self::APPLY_CHANGE ) {
				$this->_db->execute($req);
			}
		}

		/*
			sys_definition_group_table_network
		*/
		$this->log('<br />&nbsp;&nbsp;-> sys_definition_group_table_network');
		if ( !$this->_hasAxe3 )
		{
			$nas = $this->_db->getAll("SELECT DISTINCT network_agregation, id_ligne FROM sys_definition_group_table_network WHERE id_group_table = '{$this->_idFamily}' AND network_agregation = '{$this->_oldNa}'");
		}
		else // Si on a un troisieme axe
		{
			// Si le niveau d'agrégatoin est sur le premier axe
			if ( $this->_axe == 1)
			{
				$nas = $this->_db->getAll("SELECT DISTINCT network_agregation, id_ligne FROM sys_definition_group_table_network WHERE id_group_table = '{$this->_idFamily}'  AND network_agregation ~ '{$this->_oldNa}_.*'");
			}
			else // Si le niveau d'agrégatoin est sur le troisieme axe
			{
				$nas = $this->_db->getAll("SELECT DISTINCT network_agregation, id_ligne FROM sys_definition_group_table_network WHERE id_group_table = '{$this->_idFamily}'  AND network_agregation ~ '.*_{$this->_oldNa}'");
			}
		}
		foreach ( $nas as $row )
		{
			$na = $row['network_agregation'];
			$id = $row['id_ligne'];

			$newNa = preg_replace('/'.$this->_oldNa.'/', $this->_newNa, $na);
			$sqlUpdateSDGTN = sprintf("UPDATE sys_definition_group_table_network SET network_agregation = '%s', network_agregation_label = '%s' WHERE id_ligne = %d", $newNa, $newNa, $id);

			$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlUpdateSDGTN.'</span>');

			if ( self::APPLY_CHANGE ) $this->_db->execute($sqlUpdateSDGTN);
		}
	} // End function changeSetting

	/**
	 * Change le nom du niveau d'agrégation dans la topologie
	 */
	private function changeTopology ()
	{
		$sep_axe3 = get_sys_global_parameters('sep_axe3');

		/*
			On vérifie si le nom de l'ancien niveau d'agrégation est unique ou non
		*/
		$nbOldNa = $this->_db->getOne("SELECT count(agregation) FROM sys_definition_network_agregation WHERE agregation = '{$this->_oldNa}'");

		$this->log('<br /><br /><b>Changement du niveau d\'agrégation dans les tables de topologies (edw_object_ref, edw_object_arc_ref)</b>');

		// Si on a une seule fois le niveau
		if ( $nbOldNa == 1 )
		{
			  // 08/07/2011 MMT bz 21896 factorization et ajout du remplacement sur edw_object
			  $this->changeObjectTable("edw_object_ref","eor");
			  $this->changeObjectTable("edw_object","eo");

			  // 08/07/2011 MMT bz 21896 factorization et ajout du remplacement sur edw_object_arc
			  $this->changeObjectArcTable("edw_object_arc_ref","eoar");
			  $this->changeObjectArcTable("edw_object_arc","eoa");
		 }
		
	} // End function changeTopology

	/**
	 *  08/07/2011 MMT bz 21896 factorization du remplacement NA de table edw_object et edw_object_ref
	 *
	 * @param String $tableName table edw_object ou edw_object_ref
	 * @param String $tblPref eor ou eo
	 */
	private function changeObjectTable($tableName, $tblPref)
	{
		// On insère dans une table temporaire les éléments de type oldNA et newNa en supprimant les doublons
		$this->_db->execute("DROP TABLE IF EXISTS {$tableName}_tmp");
		$query_ref = "SELECT max({$tblPref}_date) as {$tblPref}_date, {$tblPref}_id, {$tblPref}_label, '{$this->_newNa}'::text as {$tblPref}_obj_type INTO {$tableName}_tmp
									FROM {$tableName} WHERE {$tblPref}_obj_type IN ('{$this->_oldNa}','{$this->_newNa}')
									GROUP BY {$tblPref}_id, {$tblPref}_label;";
		$this->_db->execute($query_ref);
		$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$query_ref.'</span>');

		// Nettoyage de la table de topologie {$tableName}
		if ( self::APPLY_CHANGE ) $this->_db->execute("DELETE FROM {$tableName} WHERE {$tblPref}_obj_type IN ('{$this->_newNa}','{$this->_oldNa}');");

		// On réinsère notre topologie à partir de la table temporaire (plus de doublons éventuels)
		$query_ref = "INSERT INTO {$tableName} ({$tblPref}_date, {$tblPref}_id, {$tblPref}_label, {$tblPref}_obj_type ) SELECT * FROM {$tableName}_tmp;";
		if ( self::APPLY_CHANGE ) $this->_db->execute($query_ref);
		$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$this->_db->getAffectedRows().' = '.$query_ref.'</span>');

		// Suppression de la table temporaire
		$this->_db->execute("DROP TABLE IF EXISTS {$tableName}_tmp");
	}

	/**
	 *  08/07/2011 MMT bz 21896 factorization du remplacement NA de table edw_object_arc et edw_object_arc_ref
	 *
	 * @param String $tableName table edw_object_arc ou edw_object_arc_ref
	 * @param String $tblPref eoar ou eoa
	 */
	private function changeObjectArcTable($tableName, $tblPref)
	{
		$this->log('<br />&nbsp;&nbsp;-> '.$tableName);

		$this->_db->execute("DROP TABLE IF EXISTS {$tableName}_tmp;");
		$this->_db->execute("CREATE TABLE {$tableName}_tmp ({$tblPref}_id text, {$tblPref}_id_parent text, {$tblPref}_arc_type text);");

		// Insertion des arcs dont les NA old et new sont les parents
		$query_arc[] = "INSERT INTO {$tableName}_tmp ({$tblPref}_id, {$tblPref}_id_parent, {$tblPref}_arc_type )
								  SELECT {$tblPref}_id, {$tblPref}_id_parent, SPLIT_PART({$tblPref}_arc_type,'|s|',1) || '|s|{$this->_newNa}' as {$tblPref}_arc_type
								  FROM $tableName
								  WHERE {$tblPref}_arc_type LIKE '%|s|{$this->_newNa}'
										OR {$tblPref}_arc_type LIKE '%|s|{$this->_oldNa}'
								  GROUP BY {$tblPref}_id, {$tblPref}_id_parent,SPLIT_PART({$tblPref}_arc_type,'|s|',1)
								  ORDER BY {$tblPref}_id;";

		// Insertion des arcs dont les NA old et new sont les children
		$query_arc[] = "INSERT INTO {$tableName}_tmp ({$tblPref}_id, {$tblPref}_id_parent, {$tblPref}_arc_type )
								  SELECT {$tblPref}_id, {$tblPref}_id_parent, '{$this->_newNa}|s|' || SPLIT_PART({$tblPref}_arc_type,'|s|',2)  as {$tblPref}_arc_type
								  FROM $tableName
								  WHERE {$tblPref}_arc_type LIKE '{$this->_newNa}|s|%'
										OR {$tblPref}_arc_type LIKE '{$this->_oldNa}|s|%'
								  GROUP BY {$tblPref}_id, {$tblPref}_id_parent,SPLIT_PART({$tblPref}_arc_type,'|s|',2)
								  ORDER BY {$tblPref}_id";

		// Insertion des arcs
		foreach($query_arc as $q)
		{
			 if ( self::APPLY_CHANGE ){
				 $this->_db->execute($q);
				 $this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$this->_db->getAffectedRows().' = '.$q.'</span>');
			 } 
		}

		// Nettoyage de la table $tableName
		if ( self::APPLY_CHANGE )
			 $this->_db->execute("  DELETE FROM $tableName
								  WHERE {$tblPref}_arc_type LIKE '{$this->_newNa}|s|%'
										 OR {$tblPref}_arc_type LIKE '{$this->_oldNa}|s|%'
										 OR {$tblPref}_arc_type LIKE '%|s|{$this->_newNa}'
										 OR {$tblPref}_arc_type LIKE '%|s|{$this->_oldNa}';");
		// Insertion des arcs sans doublons de la table $tableName
		$query_arc_ref = "INSERT INTO $tableName ({$tblPref}_id, {$tblPref}_id_parent, {$tblPref}_arc_type )
		SELECT * FROM {$tableName}_tmp;";
		if ( self::APPLY_CHANGE ) $this->_db->execute($query_arc_ref);
		$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$this->_db->getAffectedRows().' = '.$query_arc_ref.'</span>');

		// Suppression de la table temporaire
		$this->_db->execute("DROP TABLE {$tableName}_tmp;");

	}



	/**
	 * Change le niveau d'agrégation dans les alarmes
	 */
	private function changeAlarm ()
	{
		$this->log('<br /><br /><b>Changement du niveau d\'agrégation dans les tables des alarmes</b>');

		$alarmsTypes = array(
			'sys_definition_alarm_static',
			'sys_definition_alarm_dynamic',
			'sys_definition_alarm_top_worst'
		);

		$sqlUpdateAlarm = "UPDATE %s SET network = '%s' WHERE alarm_id = '%s'";
		$sqlUpdateResultAlarm = "UPDATE edw_alarm SET na = '%s' WHERE id_alarm = '%s'";

		foreach ( $alarmsTypes as $tableAlarm )
		{
			$this->log('<br />&nbsp;&nbsp;-> Table '.$tableAlarm);
			$alarms = $this->_db->getAll(sprintf("SELECT alarm_id FROM %s WHERE family = '%s' AND network = '%s'", $tableAlarm, $this->_family, $this->_oldNa));
			if ( count($alarms) > 0 )
			{
				foreach ( $alarms as $alarm )
				{
					$idAlarm = $alarm['alarm_id'];

					$sql = sprintf($sqlUpdateAlarm, $tableAlarm, $this->_newNa, $idAlarm);
					$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sql.'</span>');
					if ( self::APPLY_CHANGE ) $this->_db->execute($sql);

					$sql = sprintf($sqlUpdateResultAlarm, $this->_newNa, $idAlarm);
					$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sql.'</span>');
					if ( self::APPLY_CHANGE ) $this->_db->execute($sql);
				}
			}
		}
	} // End function changeAlarm

	/**
	 * Change le niveau d'agrégation dans les commentaires des dashboards
	 */
	private function changeComment ()
	{
		$this->log('<br /><br /><b>Changement du niveau d\'agrégation dans les commentaires des dashboards</b>');
		$sqlComment = "UPDATE edw_comment SET na = '{$this->_newNa}' WHERE family = '{$this->_family}' AND na = '{$this->_oldNa}'";
		$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlComment.'</span>');
		if ( self::APPLY_CHANGE ) $this->_db->execute($sqlComment);
	} // End changeComment

	/**
	 * Change le niveau d'agrégation dans les export
	 */
	private function changeExportRawKpi ()
	{
		$this->log('<br /><br /><b>Changement du niveau d\'agrégation dans les exports</b>');
		if ( $this->_axe == 1 )
		{
			$sqlExport = "UPDATE sys_export_raw_kpi_config SET network_aggregation = '{$this->_newNa}' WHERE family = '{$this->_family}' AND network_aggregation = '{$this->_oldNa}'";
		}
		else
		{
			$sqlExport = "UPDATE sys_export_raw_kpi_config SET na_axe3 = '{$this->_newNa}' WHERE family = '{$this->_family}' AND na_axe3 = '{$this->_oldNa}'";
		}
		$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sqlExport.'</span>');
		if ( self::APPLY_CHANGE ) $this->_db->execute($sqlExport);
	} // End function changeExportRawKpi

	/**
	 * Change le niveau d'agrégation dans la table sys_definition_selecteur
	 */
	private function changeSelecteur ()
	{
		/*
			On vérifie si le nom de l'ancien niveau d'agrégation est unique ou non
		*/
		$nbOldNa = $this->_db->getOne("SELECT count(agregation) FROM sys_definition_network_agregation WHERE agregation = '{$this->_oldNa}'");

		$this->log('<br /><br /><b>Changement du niveau d\'agrégation dans la table sys_definition_selecteur</b>');

		// Si on a une seule fois le niveau
		if ( $nbOldNa == 1 )
		{
			if ( $this->_axe == 1 )
			{
				$sql = "UPDATE sys_definition_selecteur SET sds_na = '{$this->_newNa}' WHERE sds_na = '{$this->_oldNa}'";
			}
			else
			{
				$sql = "UPDATE sys_definition_selecteur SET sds_na_axe3 = '{$this->_newNa}' WHERE sds_na_axe3 = '{$this->_oldNa}'";
			}
			$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sql.'</span>');
			if ( self::APPLY_CHANGE ) $this->_db->execute($sql);
		}
		else
		{
		}
	} // End function changeSelecteur

	/**
	 * Change le niveau d'agrégation dans la table sys_definition_selecteur
	 */
	private function changeDashboard ()
	{
		/*
			On vérifie si le nom de l'ancien niveau d'agrégation est unique ou non
		*/
		$nbOldNa = $this->_db->getOne("SELECT count(agregation) FROM sys_definition_network_agregation WHERE agregation = '{$this->_oldNa}'");

		$this->log('<br /><br /><b>Changement du niveau d\'agrégation dans la table sys_definition_dashboard</b>');

		// Si on a une seule fois le niveau
		if ( $nbOldNa == 1 )
		{
			if ( $this->_axe == 1 )
			{
				$sql = "UPDATE sys_definition_dashboard SET sdd_selecteur_default_na = '{$this->_newNa}' WHERE sdd_selecteur_default_na = '{$this->_oldNa}'";
			}
			else
			{
				$sql = "UPDATE sys_definition_dashboard SET sdd_selecteur_default_na_axe3 = '{$this->_newNa}' WHERE sdd_selecteur_default_na_axe3 = '{$this->_oldNa}'";
			}
			$this->log('<br />&nbsp;&nbsp;&nbsp;&nbsp;- <span style="color:#3399ff">'.$sql.'</span>');
			if ( self::APPLY_CHANGE ) $this->_db->execute($sql);
		}
		else
		{
		}
	} // End function changeSelecteur

	/**
	 * Ecrit une chaine de caractère dans le fichier de log
	 *
	 * @param string $str
	 */
	private function log ( $str )
	{
		if ( $this->_fileLog !== null )
		{
			// Si le format des logs est du texte on supprime les balises html
			if ( $this->_formatLog == 'text' )
			{
				// Remplace les <br> par un retour à la ligne
				$str = str_replace(array('<br />', '<br>', '<br/>'), "\n", $str);
				// Remplace les &nbsp; par un espace
				$str = str_replace('&nbsp;', ' ', $str);
				$str = strip_tags($str);
			}
			file_put_contents($this->_fileLog, $str, FILE_APPEND);
		}
	} // End function log

} // End class ChangeNaByAnotherNaInDatabaseStruture
?>