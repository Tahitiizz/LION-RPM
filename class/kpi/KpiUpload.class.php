<?
/**
 * @cb51000@
 *
 * 28-06-2010 - Copyright Astellia
 * Composant de base version cb_5.1.0.00
 *
 * 17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les exécutables psql et php (PSQL_DIR et PHP_DIR)
 * 28/06/2010 NSE : Division par zéro - remplacement de l'opérateur / par //
 * 15/07/2010 NSE : Suppression de l'opérateur //
 * 28/07/2010 OJT : Suppression de la constante PHP_DIR (bz17016)
 */
?>
<?php
/*
	02/11/2009 GHX
		- Ajout d'une vérification sur les nom des KPI contiennent que des caractères alphanuméric et underscores
	01/03/2010 MPR
		- Correction du BZ14255 - Prise en compte de la limite du nombre de KPIs actifs (maximum_mapped_counters)
	18/03/2010 NSE 
		- bz 14326 : ajout de  LIMIT 1 dans la requète select pour vérifier si la formule du kpi est correcte
		- bz 14254 : on effectue la vérification de validité de la formule sur la partie erlang.
	09/04/2010 NSE  bz 14256 problème quand la formule contient null pour tch_counter
		- on caste null en real
	22/04/2010 NSE bz 14713 : regexp trop restrictive modifiée
	27/04/2010 NSE bz 15045 : ajout de la vérification des droits de l'utilisateur avant la mise à jour des KPI
        04/08/2010 - MPR : Correction du BZ 16538
                On vérifie que le champs kpi_name ne possède pas plus de 63 caractères sinon message d'erreur
        04/08/2010 MPR BZ 15045 : la méthode getUserProfileType() n'est pas static
        23/09/2010 MPR BZ 18035 : Le contrôle sur les formules des kpi n'identifie pas toutes les erreurs
        24/09/2010 MPR BZ 18035 : Création de la méthode CorrectFormulaPourcentage()
        27/09/2010 - MPR : Correction du bz18035 -> Possibilité de générer un kpi contenant un raw déployé mais désactivé
	24/11/2010 MMT : Bz 19384 -> Test Raw non activé dans la formule KPI edw_field_name à la place de nms_field_name pour le raw name
 * 07/06/2011 NSE bz 22155 : new_field n'est pas passé à 1 lorsqu'un Kpi avec une formule invalide est uploadé avec une bonne formule.

 */
?>
<?php
/**
 * Cette classe permet de gérer l'upload d'un fichier contenant une liste de KPI d'une famille ou pour l'ensemble des famille
 *
 * @author GHX
 */

// 18/03/2010 NSE bz 14254 nécessaire pour get_network_aggregation_min_from_family
include_once REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php"; 

include_once dirname(__FILE__)."/../../php/environnement_liens.php";
include_once REP_PHYSIQUE_NIVEAU_0."class/KpiFormula.class.php";

class KpiUpload
{
	/**
	 * Instance de connexion sur une base données
	 * @var DatabaseConnection
	 */
	private $_db = null;
	 
	/**
	 * Identifiant du produit
	 * @var int
	 */
	private $_idProduct = null;
	
	/**
	 * Tableau d'information sur le produit
	 * @var array
	 */
	private $_infoProduct = array();
	
	/**
	 * Nom de la famille
	 * @var string
	 */
	private $_family = null;
	
	/**
	 * Caractère de délimiteur dans le fichier csv 
	 * @var string
	 */
	private $_delimiter = ';';
	
	/**
	 * Chemin complet vers le fichier à charger
	 * @var string
	 */
	private $_file = null;
	
	/**
	 * Tableau contenant la liste des colonnes communes entre le fichier et la table sys_definition_kpi
	 * @var array
	 */
	private $_commonsFields = array();
	
	/**
	 * Message d'erreur
	 * @var sting
	 */
	private $_msgError = '';
	
	/**
	 * Message d'erreur
	 * @var sting
	 */
	private $limit_nb_elems_actived = 4000;
	
	/**
	 * Message d'information
	 * @var sting
	 */
	private $_msgInfo = '';
	
	/**
	 * Constructeur
	 *
	 * @author GHX
	 * @param int $idProduct identifiant du produit (default master product)
	 */
	public function __construct ( $idProduct = null )
	{
		$this->_idProduct = $idProduct;
		
		// Pour ne pas avoir de soucis si la valeur de $idProduct est vide on force la valeur de l'ID du master
		if ( is_null($idProduct) || empty($idProduct) || $idProduct == 0 )
			$this->_idProduct = ProductModel::getIdMaster();
		
		$this->limit_nb_elems_actived = get_sys_global_parameters( 'maximum_mapped_counters', 1570, $this->_idProduct);
		$this->_db = DataBase::getConnection( $this->_idProduct );
		
		$p = new ProductModel($this->_idProduct);
		$this->_infoProduct = $p->getValues();
	} // End function __construct
	
	/**
	 * Destructeur
	 *
	 * @author GHX
	 */
	public function __destruct ()
	{
		// Suppression de la table temporaire
		$this->_db->execute("DROP TABLE IF EXISTS sys_definition_kpi_tmp");
		
		// Vide certaines variables de classes
		$this->_msgError = '';
		$this->_msgInfo = '';
		$this->_commonsFields = array();
		
		// Supprime le fichier
		@unlink($this->_file);
		$this->_file = null;
	} // End function __destruct
	
	/**
	 * Définit le caractère du délimiteur dans le fichier csv
	 *
	 * @author GHX
	 * @param string  $delimiter caractère de délimiteur dans le fichier csv (default ";")
	 */
	public function setDelemiter ( $delimiter = ';' )
	{
		$this->_delimiter = $delimiter;
	} // End function setDelemiter
	
	/**
	 * Définit la famille sur laquel on travaille
	 *
	 * @author GHX 
	 * @param string $family nom de la famille (ex: ept, traffic, roaming, core...)
	 */
	public function setFamily ( $family )
	{
		$this->_family = $family;
	} // End function setFamily
	
	/**
	 * Définit le fichier csv à charger en base
	 *
	 * @author GHX
	 * @param string $filename chemin complet vers le fichier csv
	 */
	public function setFile ( $filename )
	{
		$this->_file = $filename;
	} // End function setFile
	
	/**
	 * Retourne un message avec toutes les erreurs
	 *
	 * @author GHX
	 * @return string
	 */
	public function getMessageError ()
	{
		return $this->_msgError;
	} // End function getMessageError
	
	/**
	 * Retourne un message avec les informations
	 *
	 * @author GHX
	 * @return string
	 */
	public function getMessageInfo ()
	{
		return $this->_msgInfo;
	} // End function getMessageInfo
	
	/**
	 * Charge un fichier au format CSV dans la table sys_definition_kpi
	 *
	 * @author GHX
	 */
	public function loadFile ()
	{
		/*
			Met à jour les KPI déjà existant
		*/
		$queryUpdate = "UPDATE sys_definition_kpi SET ";
		foreach ( $this->_commonsFields as $field )
		{
			if ( $field != 'kpi_name' && $field != 'kpi_ligne')
				$queryUpdate .= " {$field} = tmp.{$field},";
		}
		$queryUpdate = substr($queryUpdate, 0, -1);
		// 27/04/2010 NSE 15045 ajout de la vérification sur l'utilisateur
		$queryUpdate .= " FROM sys_definition_kpi_tmp AS tmp 
							WHERE lower(sys_definition_kpi.kpi_name) = lower(tmp.kpi_name) 
							AND sys_definition_kpi.edw_group_table = tmp.edw_group_table";
        // 07/06/2011 NSE bz 22155 : Ajout de la condition "mise à jour uniquement s'il y a un changement sur le KPI"
        // pour éviter par exemple de redéployer des Kpi déjà déployés
        foreach ( $this->_commonsFields as $field )
		{
			if ( $field != 'kpi_name' && $field != 'kpi_ligne' && $field != 'new_field' && $field != 'edw_group_table ')
				$queryOr .= " OR sys_definition_kpi.{$field} <> tmp.{$field}";
		}
        if(!empty ($queryOr))
            $queryUpdate .= " AND (FALSE $queryOr) ";

		// 27/04/2010 NSE bz 15045 : on ne met pas à jour les Kpi Astellia si l'utilisateur n'en a pas le droit
		$UserModel = new UserModel($_SESSION['id_user']);
                // 04/08/2010 MPR BZ 15045 : la méthode getUserProfileType() n'est pas static
                $userProfileType = $UserModel->getUserProfileType();
		if( $userProfileType != 'customisateur' )
			$queryUpdate .= " AND sys_definition_kpi.value_type <> 'customisateur'";
		$this->_db->execute($queryUpdate);
		// Message d'information pour dire le nombre KPI mis à jour
		$this->_msgInfo .= (empty($this->_msgInfo) ? '' : '<br />').__T('A_KPI_BUILDER_NB_KPI_UPDATED', $this->_db->getAffectedRows());
		// 27/04/2010 NSE bz 15045 : on indique que les Kpi Astellia ne sont pas mis à jour
		if($userProfileType!='customisateur')
			$this->_msgInfo .= ' ('.__T('A_KPI_BUILDER_KPI_CUSTO_NOT_UPDATED').')';

		/*
			Insertion des nouveaux KPI
		*/
		$this->_db->execute('UPDATE sys_definition_kpi_tmp SET new_field = 0 WHERE on_off = 0');
		$this->_db->execute('UPDATE sys_definition_kpi_tmp SET new_field = 1 WHERE on_off = 1');
		
		$queryInsert = "INSERT INTO sys_definition_kpi SELECT * FROM sys_definition_kpi_tmp WHERE ROW(lower(kpi_name),edw_group_table) NOT IN (SELECT lower(kpi_name),edw_group_table FROM sys_definition_kpi)";
		$this->_db->execute($queryInsert);
		// Message d'information pour dire le nombre de nouveaux KPI
		$this->_msgInfo .= (empty($this->_msgInfo) ? '' : '<br />').__T('A_KPI_BUILDER_NB_KPI_NEW', $this->_db->getAffectedRows());
	} // End function loadFile
	
	/**
	 * Charge le fichier uploadé dans une table temporaire
	 *
	 * @author GHX
	 * @param string $filename chemin complet vers le fichier
	 * @param array $commonsFields liste des champs communs entre le fichier et la table
	 * @param array $header liste des colonnes du fichier
	 * @return int retourne 0 si le fichier a bien été chargé sinon 1 ou 2
	 */
	private function loadFileInTableTemp ( $filename, $commonsFields, $header )
	{
		$cmd = '';

		// Pour les colonnes suivantes si les valeurs du fichiers sont incorrects ont les remplaces tous ce qui n'est pas zéro ou null est remplacé par 1 sinon 0
		foreach ( array('on_off', 'visible', 'new_field') as $col )
		{
			if ( in_array($col, $commonsFields) )
			{
				$index = array_search($col, $header)+1;
				$cmd .= " if(\${$index}==\"\" || \${$index}==\"NULL\" || \${$index}==0){\${$index}=0;}else{\${$index}=1;} ";
							
			}
		}
		// Si pour la colonne pourcentage on a une valeur différente de 1 on met zéro
		if ( in_array('pourcentage', $commonsFields) )
		{
			$index = array_search('pourcentage', $header)+1;
			$cmd .= " if(\${$index}!=\"1\"){\${$index}=0;} ";
		}
		// On supprime la colone id_ligne, celle-ci est remplie de facon automatique
		if ( !in_array('id_ligne', $commonsFields) )
		{
			$cmd .= " \$".(count($commonsFields))."=\$".(count($commonsFields))."\";tmp\"NR; ";
			$commonsFields[] = 'id_ligne';
		}
		
		// Création du print pour ne récupérer que les colonnes nécessaires
		$cmd .= " print ";
		foreach ( $commonsFields as $col )
		{
			if ( $col != 'id_ligne')
			{
				$index = array_search($col, $header)+1;
				$cmd .= "\${$index}\";\"";
			}
		}
		// Supprime les 3 derniers caractères ";" de la commande
		$cmd = substr($cmd, 0 , -3);
		
		// Création d'une table temporaire par précaution on essaie de la supprimer avant
		$this->_db->execute("DROP TABLE IF EXISTS sys_definition_kpi_tmp");
		$this->_db->execute("CREATE TABLE sys_definition_kpi_tmp (LIKE sys_definition_kpi)");
		
		$queryLoadFile = sprintf(
			"COPY sys_definition_kpi_tmp (%s) FROM stdin WITH DELIMITER '\"'\"'{$this->_delimiter}'\"'\"' NULL '\"'\"''\"'\"';",
			implode(',',$commonsFields)
		);
		// Création de la commande qui sert a remplir la table temporaire
		// - affichage du fichier
		// - awk
		//	-> BEGIN pour afficher la COPY pour insérer les données en base
		//	-> affichage des colonnes du fichier que l'on a besoin avec modification des valeurs pour certaines colonnes si elles sont incorrectes
		// - psql pour exécuter l'insertion des données (option -h permet de préciser sur quel serveur se trouve la base)
		// - 2&>1 pour afficher les erreurs dans la sortie standart
		$cmdLoadFile = sprintf(
				'cat "%s" | awk \'BEGIN{FS="%s"; OFS=FS; print "%s"} NR>1{ %s }\' | env PGPASSWORD=%s '.PSQL_DIR.'/psql -U %s %s -h %s 2>&1',
				$filename, // Nom du fichier a afficher
				$this->_delimiter, // Délimiteur des colonnes pour la commande awk
				$queryLoadFile, // commande COPY
				$cmd, // Commande AWK pour afficher certaines colonnes uniquement
				$this->_infoProduct['sdp_db_password'], // Password à utiliser pour se connecter
				$this->_infoProduct['sdp_db_login'], // Login à utiliser pour se connecter
				$this->_infoProduct['sdp_db_name'], // Nom de la base sur laquel insérer les données
				$this->_infoProduct['sdp_ip_address'] // IP du serveur sur lequel se trouve la base
			);
	
		exec($cmdLoadFile, $r);
		// __debug($cmdLoadFile);
		if ( strstr(implode('', $r), 'error') != false )
			return false;
		
		// On fait un trim sur toutes les colonnes qui peuvent contenir une chaine de caractère
		$queryTrim = '';
		foreach ( $commonsFields as $field )
		{
			if ( in_array($this->_db->getFieldType('sys_definition_kpi_tmp', $field), DataBaseConnection::getPGStringTypes()) )
			{
				$queryTrim .= $field.'=trim('.$field.'),';
			}
		}
		if ( !empty($queryTrim) )
		{
			$queryTrim = 'UPDATE sys_definition_kpi_tmp SET '.substr($queryTrim, 0, -1);// Supprime la dernière virgule
			$this->_db->execute($queryTrim);
		}
		
		/*
		 * On force automatiquement les colonnes suivantes
         * 2011/09/21 OJT : bz23804, problème de cast sur le md5(random())
		 */
		$queryUpdateFields = "UPDATE sys_definition_kpi_tmp SET ";
		$queryUpdateFields .= "id_ligne = 'sdk_'||md5(random()::text),";
		$queryUpdateFields .= "kpi_type = 'float4',";
		$queryUpdateFields .= "numerator_denominator='total',";
		$queryUpdateFields .= "new_date='".date('Ymd')."',";
		$queryUpdateFields .= "value_type='".getClientType($_SESSION['id_user'])."',";

		/*
			On force les valeurs suivantes si elles ne sont pas présentes dans le fichier
		*/
		if ( !in_array('on_off', $commonsFields) )
		{
			$queryUpdateFields .= "on_off=1,";
		}
		if ( !in_array('new_field', $commonsFields) )
		{
			$queryUpdateFields .= "new_field=1,";
		}
		if ( !in_array('visible', $commonsFields) )
		{
			$queryUpdateFields .= "visible=1,";
		}
		if ( !in_array('pourcentage', $commonsFields) )
		{
			$queryUpdateFields .= "pourcentage=0,";
		}
		if ( !in_array('kpi_label', $commonsFields) )
		{
			$queryUpdateFields .= "kpi_label=kpi_name,";
		}
		// Suppression des cotes et guillemets des commentaires
		if ( in_array('comment', $commonsFields) )
		{
			$queryUpdateFields .= "comment=replace(replace(comment,'\"',' '),'''',' '),";
		}
		
		if( !$this->checkNbKPIsActivatedInDb() )
		{
			return false;
		}
		
		// Suppression du dernier point virgule
		$queryUpdateFields = substr($queryUpdateFields, 0, -1);
		$this->_db->execute($queryUpdateFields);
		
		return true;
	} // End function loadFileInTableTemp
	
	
	// maj 01/03/2010 - MPR : Correction du BZ14255 - Prise en compte de la limite du nombre de KPIs actifs (maximum_mapped_counters)
	/**
	* Function checkNbKPIsActivatedInDb
	* @return boolean : true = Pas d'erreur / false = Nombre de kpis actifs excédé
	*/
	private function checkNbKPIsActivatedInDb()
	{
		// Récupération du nombre de KPIs en base de données déjà déployés 
		$query = "SELECT count(*) as nb_elems, edw_group_table FROM sys_definition_kpi WHERE on_off = 1 GROUP BY edw_group_table;";
		$result =  $this->_db->getAll($query);
		
		$nb_kpis_in_db = array();
		if( count($result) > 0 )
		{
			foreach( $result as $row )
			{
				$nb_kpis_in_db[ $row['edw_group_table'] ]  = $row['nb_elems'];
			}
		}
		
		$query = "SELECT count(*) as nb_elems, family_label, k.edw_group_table
				  FROM sys_definition_kpi_tmp k, sys_definition_categorie c , sys_definition_group_table g
				  WHERE kpi_name NOT IN (
						SELECT kpi_name 
						FROM sys_definition_kpi 
						WHERE edw_group_table = k.edw_group_table
						AND on_off = 1 
					  
				 )
				 AND k.on_off = 1
				 AND k.edw_group_table = g.edw_group_table AND g.family = c.family
				 GROUP BY family_label, k.edw_group_table;
				 ";
		$result = $this->_db->getAll($query);
		
		if( count($result) > 0 )
		{
			$errors = array();
			foreach($result as $row)
			{
				// Si aucun kpi n'est déployé sur la famille concernée
				if( !isset($nb_kpis_in_db[ $row['edw_group_table'] ]) )
				{
					$nb_kpis_in_db[ $row['edw_group_table'] ] = 0;
				}
				// On vérifie que la limite n'est pas dépassée
				// Nombre de kpis à déployer via le fichier + nombre de kpis déjà déployés doit être inféreur ou égal à la limite
				if( $row['nb_elems'] + $nb_kpis_in_db[ $row['edw_group_table'] ] > $this->limit_nb_elems_actived  )
				{
					// Calcul du nombre de kpis qu'on peut encore déployer
					$nb_activated = __T('G_GDR_BUILDER_NONE');
					if( $this->limit_nb_elems_actived - $nb_kpis_in_db[ $row['edw_group_table'] ] > 0 )
					{
						$nb_activated = $this->limit_nb_elems_actived - $nb_kpis_in_db[ $row['edw_group_table'] ];
					}
					$errors[] = __T(A_E_KPI_BUILDER_UPLOAD_NB_ACTIVATED_KPI_EXCEEDED, 
									$row['family_label'], 
									$this->limit_nb_elems_actived,
									$nb_kpis_in_db[ $row['edw_group_table'] ],
									$nb_activated,
									$row['nb_elems']
									);
				}
			}
			
			// On affiche toutes les erreurs rencontrées
			if(count($errors) > 0 )
			{
				$this->_msgError = implode("<br />",$errors );
				return false;
			}
		}
		
		return true;
	}
	/**
	 * Vérification sur le fichier uploadé
     * 02/07/2010 OJT : Bz14796 Gestion du nombre max de fonctions ErlangB
	 *
	 * @author GHX
	 * @return boolean
	 */
	public function check ()
	{
		if ( !file_exists($this->_file) )
			return false;
		
		// Entete du fichier
		$header = $this->getHeader($this->_file);
		// Colonnes de la table
		$fieldsSDK = $this->_db->getColumns('sys_definition_kpi');
		
		// On supprime les espaces avant et apres le nom des colonnes
		$header = array_map('trim', $header);
		// On met tous en minuscule
		$header = array_map('strtolower', $header);
		$fieldsSDK = array_map('strtolower', $fieldsSDK);
		
		// La colonne "id_ligne" ne doit pas être prise en compte
		if ( in_array('id_ligne', $header) )
			unset($header[array_search('id_ligne', $header)]);
		
		// Liste des colonnes communes avec la liste des colonnes de la table
		$commonsFields = array_intersect($header, $fieldsSDK);
		
		/*
			Verification sur l'entete du fichier
		*/
		// Si on a aucun champ en commun
		if ( count($commonsFields) == 0 )
		{
			$this->_msgError = __T('A_E_KPI_BUILDER_UPLOAD_NO_COLUMN_IN_COMMON');
			return false;
		}
		
		// Si la colonne kpi_name n'est pas présente
		if ( !in_array('kpi_name', $commonsFields) )
		{
			$this->_msgError = __T('A_E_KPI_BUILDER_UPLOAD_NO_COLUMN_KPI_NAME');
			return false;
		}
		// Si on a que l'entete dans le fichier pas la peine d'aller plus loin c'est que le fichier est vide
		$nbLines = exec('cat "'.$this->_file.'" | awk \'END{print NR}\'');
		if ( $nbLines == 1 )
		{
			$this->_msgError = __T('A_E_UPLOAD_TOPOLOGY_FILE_IS_EMPTY');
			return false;
		}
		
		
		// Vérifie que l'on n'a pas des kpi_names vides
		$indexColKpiName = array_search('kpi_name', $header)+1;
		$cmdAwkKpiName = "awk -F'{$this->_delimiter}' '\${$indexColKpiName} == \"\"{print NR}' {$this->_file}";
		$resultKpiName = exec($cmdAwkKpiName);
		if ( !empty($resultKpiName) )
		{
			$this->_msgError = __T('A_E_KPI_BUILDER_UPLOAD_EMPTY_KPI_NAME');
			return false;
		}
		
		// On regarde si on a des colonnes qui ne font pas parties de la table sys_definition_kpi
		$diff = array_diff($header, $fieldsSDK);
		if ( count($diff) > 0 )
		{
			$this->_msgInfo .= (empty($this->_msgInfo) ? '' : '<br />').__T('A_E_KPI_BUILDER_UPLOAD_IGNORED_COLUMNS').'<ul style="margin-top:0px; margin-bottom:0px;">';
			// Création d'un message pour dire que certaines colonnes ont étés ignorées			
			foreach ( $diff as $col )
			{
				// On ajout "(empty)" pour dire que le nom de la colonne est vide
				$this->_msgInfo .= '<li>'.(empty($col) ? '(empty)' : $col).'</li>';
			}
			$this->_msgInfo .= '</ul>';
		}
		
		// Charge le fichier dans une table temporaire
		if ( $this->loadFileInTableTemp($this->_file, $commonsFields, $header) )
		{
			/*
				Vérification sur le contenu du fichier
			*/
			// Si la colonne edw_group_table est présente on vérifie les valeurs
			if ( in_array('edw_group_table', $commonsFields) )
			{
				$resultsEdw = $this->_db->execute("
						SELECT DISTINCT edw_group_table 
						FROM sys_definition_kpi_tmp 
						WHERE edw_group_table NOT IN (SELECT edw_group_table FROM sys_definition_group_table)
							OR edw_group_table IS NULL
					");
				if ( $this->_db->getNumRows() > 0 )
				{
					$this->_msgError .= (empty($this->_msgError) ? '' : '<br />').__T('A_E_KPI_BUILDER_UPLOAD_EDW_GROUP_TABLE_INVALID').'<ul style="margin-top:0px;margin-bottom:0px;">';
					while ( $row = $this->_db->getQueryResults($resultsEdw, 1) )
					{
						$this->_msgError.= '<li>'.(empty($row['edw_group_table']) ? '(empty value)' : $row['edw_group_table']).'</li>';
					}
					$this->_msgError.= '</ul>';
				}
			}
			else
			{
				// Si la colonne edw_group_table on considère que tous les KPI du fichier sont de la même famille
				$this->_db->execute("
					UPDATE sys_definition_kpi_tmp
					SET edw_group_table = (SELECT edw_group_table FROM sys_definition_group_table WHERE family = '{$this->_family}')
					");
			}
			
			// Vérifie que l'on n'a pas des KPI en doubles (on se base sur le champ kpi_name)
			$queryCountKPI = "
				SELECT LOWER(kpi_name) AS name, LOWER(edw_group_table), COUNT(LOWER(kpi_name)) AS nb 
				FROM sys_definition_kpi_tmp 
				GROUP BY LOWER(kpi_name), LOWER(edw_group_table)
				HAVING COUNT(LOWER(kpi_name)) > 1
				";
			$resultsCount = $this->_db->execute($queryCountKPI);
			if ( $this->_db->getNumRows() > 0 )
			{
				$this->_msgError .= (empty($this->_msgError) ? '' : '<br />').__T('A_E_KPI_BUILDER_UPLOAD_KPI_NAME_NOT_UNIQUE').' : <ul style="margin-top:0px;margin-bottom:0px;">';
				while ( $row = $this->_db->getQueryResults($resultsCount, 1) )
				{
					$this->_msgError.= '<li>'.$row['name'].' (x '.$row['nb'].')</li>';
				}
				$this->_msgError.= '</ul>';
			}
			
            // maj 04/08/2010 - MPR : Correction du BZ 16538
            // On vérifie que le nom ne possède pas plus de 63 caractères
            // Vérifie que l'on n'a pas des KPI en doubles (on se base sur le champ kpi_name)
			$queryKPItooLong = "
				SELECT kpi_name AS name, edw_group_table
				FROM sys_definition_kpi_tmp
				GROUP BY kpi_name, edw_group_table
				HAVING char_length(kpi_name) >= 63
				";
			$resultsCount = $this->_db->execute($queryKPItooLong);
			if ( $this->_db->getNumRows() > 0 )
			{
				$this->_msgError .= (empty($this->_msgError) ? '' : '<br />').__T('A_E_KPI_BUILDER_UPLOAD_KPI_NAME_TOO_LONG').' : <ul style="margin-top:0px;margin-bottom:0px;">';
				while ( $row = $this->_db->getQueryResults($resultsCount, 1) )
				{
					$this->_msgError.= '<li>'.$row['name'].' on '.$row['edw_group_table'].'</li>';
				}
				$this->_msgError.= '</ul>';
			}

			// Vérifie que l'on n'a pas d'espace dans le nom d'un KPI
			$querySpaceInKpiName = "
				SELECT kpi_name
				FROM sys_definition_kpi_tmp
				WHERE kpi_name LIKE '% %'
				";
			$resultsSpace = $this->_db->execute($querySpaceInKpiName);
			if ( $this->_db->getNumRows() > 0 )
			{
				$this->_msgError .= (empty($this->_msgError) ? '' : '<br />').__T('A_E_KPI_BUILDER_UPLOAD_SPACE_IN_KPI_NAME').'<ul style="margin-top:0px;margin-bottom:0px;">';
				while ( $row = $this->_db->getQueryResults($resultsSpace, 1) )
				{
					$this->_msgError.= '<li>'.$row['kpi_name'].'</li>';
				}
				$this->_msgError.= '</ul>';
			}
			// 02/11/2009 GHX
			// Vérifie que le nom des KPI ne contient pas de caractère spéciaux (ne peut contenir que des caractères alphanuméric et des underscores)
			// 12/03/2012 SPD : BZ 15936 Verifie que le nom du KPI ne commence pas pas un underscore
			$queryKpiNameInvalid = "SELECT kpi_name FROM sys_definition_kpi_tmp WHERE kpi_name ~ '[^a-zA-Z0-9_]' OR kpi_name like '\\\\_%'";
			$resultsKpiNameInvalid = $this->_db->execute($queryKpiNameInvalid);
			if ( $this->_db->getNumRows() > 0 )
			{
				$this->_msgError .= (empty($this->_msgError) ? '' : '<br />').__T('A_E_KPI_BUILDER_UPLOAD_KPI_NAME_INVALID').'<ul style="margin-top:0px;margin-bottom:0px;">';
				while ( $row = $this->_db->getQueryResults($resultsKpiNameInvalid, 1) )
				{
					$this->_msgError.= '<li>'.$row['kpi_name'].'</li>';
				}
				$this->_msgError.= '</ul>';
			}
			
			// Vérifie que le nom des KPI ne commence pas par un nombre
			$queryKpiName = "SELECT kpi_name FROM sys_definition_kpi_tmp WHERE kpi_name ~ '^[0-9]'";
			$resultsKpiName = $this->_db->execute($queryKpiName);
			if ( $this->_db->getNumRows() > 0 )
			{
				$this->_msgError .= (empty($this->_msgError) ? '' : '<br />').__T('A_E_KPI_BUILDER_UPLOAD_KPI_NAME_INVALID_CANNOT_START_NUMERIC').'<ul style="margin-top:0px;margin-bottom:0px;">';
				while ( $row = $this->_db->getQueryResults($resultsKpiName, 1) )
				{
					$this->_msgError.= '<li>'.$row['kpi_name'].'</li>';
				}
				$this->_msgError.= '</ul>';
			}
			
			// Si on n'a pas eu d'erreur dans le check des données on peut aller plus loin
			if ( !empty($this->_msgError) )
				return false;
			
			// Vérifie que les formules sont correctes
			// NSE ajout de family et visible = 1
			$results = $this->_db->execute("
				SELECT
					sdkt.kpi_name,
					sdkt.kpi_formula,
					sdgt.edw_group_table||'_raw_'||sdgtn.network_agregation||'_'||sdgtt.time_agregation AS edw,
                    sdgt.edw_group_table,
					sdgt.family,
                    sdkt.on_off
				FROM
					sys_definition_kpi_tmp AS sdkt,
					sys_definition_group_table AS sdgt,
					sys_definition_group_table_network  AS sdgtn,
					sys_definition_group_table_time AS sdgtt
				WHERE
					sdkt.edw_group_table = sdgt.edw_group_table
					AND sdgt.id_ligne = sdgtn.id_group_table
					AND sdgt.id_ligne = sdgtt.id_group_table
					AND sdgtn.rank = -1
					AND sdgtt.id_source = -1
					AND sdgtn.data_type = 'raw'
					AND sdgtt.data_type = 'raw'
					AND sdgt.visible=1
				");
			if ( $this->_db->getNumRows() > 0 )
			{
				$firstError = true;

				// 23/09/2010 MPR BZ 18035 Correction du bz 18035 - Le contrôle sur les formules des kpi n'identifie pas toutes les erreurs
                // Remplacement de $lastError par lastKpi
                // Si l'erreur sur la formule du kpi précédent est la même, le kpi n'est pas désactivé
                $lastKpi = null;
                $nbKpiUsingErlangB = KpiFormula::getNbKpiUsingErlangB( $this->_db );
                $nbMaxKpiUsingErlangB = get_sys_global_parameters( 'max_kpi_using_erlang', 1, $this->_idProduct );

				// On boucle sur tous les KPI
				while ( $row = $this->_db->getQueryResults($results, 1) )
				{
                    $error = '';
                    $errorMaxErlangB = FALSE;

                    // 09:47 18/10/2010 SCT : BZ 18518 => Vérification de la formule KPI avant activation depuis KPI_Builder
                    //DEBUG BUG 18518
                    //  + appel de la méthode de vérification du Kpi
                    $kpiFormulaResult = KpiFormula::checkFormula($this->_db, $row['kpi_formula'], $row['family'], $this->_idProduct);
                    //  + vérification du nombre de Kpi possédant Erlangb uploadé
                    if(KpiFormula::isFormulaUsingErlangB($row['kpi_formula']))
                    {
                        if($nbKpiUsingErlangB >= $nbMaxKpiUsingErlangB)
                            $errorMaxErlangB = TRUE;
                        $nbKpiUsingErlangB++;
                    }
                    //  + on teste si le retour de la fonction est en erreur
                    if(!$kpiFormulaResult)
                        $error = $this->_db->getLastError();
                    //  + on teste si le Kpi est utilisé en tant que BH et qu'il est désactivé via l'upload
                    $errorBhKpiDesactivation = 0;
                    if($row['on_off'] == 0 && KpiModel::isKpiLockedBh($this->_idProduct, $row['kpi_name'], $row['family']))
                    {
                        $this->_db->execute("UPDATE sys_definition_kpi_tmp SET on_off=1 WHERE kpi_name='{$row['kpi_name']}'");
                        $errorBhKpiDesactivation = 1;
                    }
/*            					// 18/03/2010 NSE bz 14254 on prépare la formule
					// si elle contient une fonction erlang
					if (!(strpos(strtolower($row['kpi_formula']), 'erlangb')===false))
					{
						// on remplace le paramètre $network1stAxis par le niveau min de la famille
						$net_min = get_network_aggregation_min_from_family($row['family'],$this->_idProduct);
						$row['kpi_formula'] = str_replace("\$network1stAxis", $net_min, $row['kpi_formula']);
						// oui, double stripslashes, sinon il en reste autour du mode
						$row['kpi_formula']=stripslashes($row['kpi_formula']);
                                                $row['kpi_formula']=stripslashes($row['kpi_formula']);
						// NSE 09/04/2010 bz 14256 problème quand la formule contient null pour tch_counter
						// on caste null en real
						// 22/04/2010 NSE bz 14713 : regexp trop restrictive modifiée
						$row['kpi_formula'] = preg_replace('/(erlangb\([^,]+,[^,]+,[^,]+,.+,\s*null)([^:])/i','$1::real$2',$row['kpi_formula']);

						// On teste si la limite du nombre de KPI utilisant erlangB n'est pas atteinte
						if( $nbKpiUsingErlangB >= $nbMaxKpiUsingErlangB ){
							$errorMaxErlangB = TRUE;
						}
						$nbKpiUsingErlangB ++;
					}

					if( !$errorMaxErlangB ){ // Si il n'y a pas encore eu d'erreur, on test la formule avec un requête
						// Test la formule
						// 18/03/2010 NSE bz 14326 : ajout de  LIMIT 1
                        // maj 29/09/2010 - MPR : On caste la formule en réel afin d'éviter les formules du genre "raw1,raw2" qui ferait planter le compute
						$this->_db->execute("SELECT ({$row['kpi_formula']})::real AS {$row['kpi_name']} FROM {$row['edw']} LIMIT 1");
						// Si on a une erreur c'est que la formule est incorrecte
						$error = $this->_db->getLastError();
					}
 *
 */
					// On teste aussi que l'erreur n'est pas déjà traité car pg_last_error retourne toujours la dernière erreur meme si entre temps il y a eu des requetes SQL correctes
					if( ( !empty($error) && $lastKpi != $row['kpi_name'] ) || ( $errorMaxErlangB ) || ($errorBhKpiDesactivation) )
					{
						// On désactive le KPI dont la formule est incorrecte
						if(!$errorBhKpiDesactivation)
                            $this->_db->execute("UPDATE sys_definition_kpi_tmp SET new_field=0, on_off=0 WHERE kpi_name='{$row['kpi_name']}'");
						// Création d'un message pour informer l'utilisateur que le KPI a été désactivé mais que l'on insert quand même le KPI en base
						if ( $firstError ){
							$this->_msgInfo .= (empty($this->_msgInfo) ? '' : '<br />').__T('A_E_KPI_BUILDER_UPLOAD_FORMULA_INVALID').'<ul style="margin-top:0px; margin-bottom:0px;">';
                        }
                        if( empty($row['kpi_formula']) ){
                            $this->_msgInfo .= '<li>'.$row['kpi_name'].' (empty formula)</li>';
                        }
                        else if( $errorMaxErlangB ){
                            $this->_msgInfo .= '<li>'.$row['kpi_name'].' (max ErlangB function use reached)</li>';
                        }
                        elseif($errorBhKpiDesactivation) {
                            $this->_msgInfo .= '<li>'.$row['kpi_name'].' (cannot be desactivated, used as Busy Hour)</li>';
                        }
                        else{
                            $this->_msgInfo .= '<li>'.$row['kpi_name'].'</li>';
                        }
                        $lastError = $error;
						// 23/09/2010 MPR BZ 18035 Correction du bz 18035 - Le contrôle sur les formules des kpi n'identifie pas toutes les erreurs
                        $lastKpi = $row['kpi_name'];
						$firstError = false;
                        // 07/06/2011 NSE bz 22155 : déplacement des tests
					}
                    else
                    {
                        // 27/09/2010 - MPR : Correction du bz18035
                        //  Possibilité de générer un kpi contenant un raw déployé mais désactivé
                        $lst_raw_disable = array();
                        if( !isset( $result_raws_disabled[$row['family']]) )
                        {
                             // Récupere la liste des compteurs déployé mais désactivé (on_off = 0)
						 // 24/11/2010 MMT Bz 19384 utilise edw_field_name à la place de nms_field_name pour le raw name
                            $check_2 = "
                                    SELECT attname as id, edw_field_name_label as label
                                    FROM pg_class c, pg_attribute a, sys_field_reference sfr
                                    WHERE a.attrelid = c.oid
                                    AND relname = '{$row['edw']}'
                                    AND attname = lower( sfr.edw_field_name ) AND edw_group_table = '{$row['edw_group_table']}'
                                    AND sfr.on_off = 0;
                                    ";

                            $result_raws_disabled[$row['family']] = $this->_db->getAll($check_2);
                        }
                        if( count($result_raws_disabled[$row['family']]) > 0 )
                        {
                            $error = false;
                            foreach($result_raws_disabled[$row['family']] as $raw_disable)
                            {
								// 24/11/2010 MMT Bz 19384 utilise methode plus complexe de recherche de raw dans formule
								if(KpiUpload::isRawUsedInKpiFormula($raw_disable['id'], $row['kpi_formula']))
                                {
                                   $error = true;
                                   break;
                                }
                            }
                            if( $error )
                            {
                                // On désactive le KPI dont la formule est incorrecte
                                $this->_db->execute("UPDATE sys_definition_kpi_tmp SET new_field = 0, on_off = 0 WHERE kpi_name = '{$row['kpi_name']}'");
                                // Création d'un message pour informer l'utilisateur que le KPI a été désactivé mais que l'on insert quand même le KPI en base
                                if ( $firstError )
                                        $this->_msgInfo .= (empty($this->_msgInfo) ? '' : '<br />').__T('A_E_KPI_BUILDER_UPLOAD_FORMULA_INVALID').'<ul style="margin-top:0px; margin-bottom:0px;">';
                                $this->_msgInfo .= '<li>'.$row['kpi_name'].(empty($row['kpi_formula']) ? ' (empty formula)' : '').'</li>';
                                // maj 29/09/2010 - MPR :Correction du bz18035 - Affichage en escalier des erreurs
                                $firstError = false;
                            }
                        }
                    }
				}
				if ( !$firstError )
					$this->_msgInfo .= '</ul>';

				// 2010/08/11 - MGD - BZ 15261 - Correction des formules de pourcentage :
				// Ajout du 'CASE WHEN formule > 100 THEN 100 ELSE formule END' pour les pourcentages qui ne l'ont pas déjà
				$query = "UPDATE sys_definition_kpi_tmp
				SET kpi_formula = 'CASE WHEN '||kpi_formula||' > 100 THEN 100 ELSE '||kpi_formula||' END'
				WHERE pourcentage = 1 AND kpi_formula !~ 'CASE WHEN .+ THEN 100 ELSE .+ END';";
				$this->_db->execute($query);
				// 2010/08/11 - MGD - BZ 15261 - Fin correction
			}
			else
			{
				$this->_msgError = __T('A_E_UPLOAD_TOPOLOGY_ERROR_DURING_UPADTE');
				return false;
			}
            // Si les colonnes suivantes ne sont pas dans le fichier : on les ajoute pour ne pas activer les compteurs dont la formule plante
			// 07/06/2011 NSE bz 22155 : on sort ces tests du if pour qu'ils soient obligatoirement vérifiés.
            // On les sort également de la boucle car il n'est pas utile de les répéter.
            if ( !in_array('on_off', $commonsFields) )
                $commonsFields[] = 'on_off';
            if ( !in_array('new_field', $commonsFields) )
                $commonsFields[] = 'new_field';
			
			// La vérification du fichier est OK : il est possible d'avoir des WARNINGS (ex : formule incorrecte, colonne ignorée...)
			$this->_commonsFields = $commonsFields;
			return true;
		}
		
		if( empty($this->_msgError) )
			$this->_msgError = __T('A_E_UPLOAD_TOPOLOGY_ERROR_DURING_UPADTE');
		return false;
	} // End function check
	
	/**
	 * Retourne l'entete du fichier
	 *
	 * @author
	 * @param string $filename chemin complet vers le fichier csv
	 * @return array
	 */
	private function getHeader ( $filename )
	{
		$cmd = "awk -F'{{$this->_delimiter}}' 'NR==1{print \$0}' \"{$filename}\" | sed \"s/{$this->_delimiter}/\\n/g\" ";
		exec($cmd, $header);
		
		return $header;
	} // End function getHeader 
	
	/**
	 * Lance le script clean_tables_structure.php pour déployer tous les nouveaux KPI
	 *
	 * @author GHX
	 */
	public function launchCleanTablesStructure ()
	{
		exec( 'php -q '.REP_PHYSIQUE_NIVEAU_0.'scripts/clean_tables_structure.php 2&>1', $r);
	} // End function launchCleanTablesStructure
	
	/**
	 * Génére un fichier csv contenant la liste des KPI
	 *
	 * @author GHX
	 * @param string $filename chemin complete vers le fichier à générer
	 * @param string $family nom de la famille pour laquelle on veut la liste des KPI (ex: ept, traffic, efferl ...)
	 * @param int $idProduct idenfiant du produit sur lequel se trouve la famille 
	 */
	public static function createFile ( $filename, $family, $idProduct )
	{
		// Requête de récupération des compteurs
		$query = "
		COPY(
			SELECT
				kpi_name,
				kpi_formula,
				kpi_label,
				edw_group_table,
				on_off,
				visible,
				pourcentage,
				comment
			FROM sys_definition_kpi 
			WHERE edw_group_table = (
				SELECT edw_group_table FROM sys_definition_group_table
				WHERE family = '{$family}'
				LIMIT 1)
			ORDER BY kpi_name
		)
		TO stdout
		WITH CSV HEADER DELIMITER AS ';' NULL AS ''";
		
		$product = new ProductModel($idProduct);
		$infosProduct = $product->getValues();
		
		$cmd = sprintf(
			'env PGPASSWORD=%s '.PSQL_DIR.'/psql -U %s %s -h %s -c "%s" >> "%s"',
			$infosProduct['sdp_db_password'],
			$infosProduct['sdp_db_login'],
			$infosProduct['sdp_db_name'],
			$infosProduct['sdp_ip_address'],
			$query,
			$filename
		);
		exec($cmd, $r, $error);
		
		if($error)
			return false;
		
		return true;
	} // End function createFile

    /**
     * Fonction qui corrige les formules(%)
     * Ajout du CASE WHEN formule > 100 THEN 100 ELSE formule END si la condition n'existe pas
     */
    public function CorrectFormulaPourcentage()
    {
        $query = "
                SELECT kpi_formula
                FROM sys_definition_kpi
                WHERE on_off = 1
                AND pourcentage = 1
                AND kpi_formula !~* '^case +when.*>*100 +then +100+.*else +.*end'
                LIMIT 1;
                ";
         $this->_db->execute($query);
        if( $this->_db->getNumRows() > 0)
        {
            $query = "
                      UPDATE sys_definition_kpi
                      SET kpi_formula = 'CASE WHEN '||kpi_formula||' > 100 THEN 100 ELSE '|| kpi_formula|| ' END'
                      WHERE on_off = 1
                            AND pourcentage = 1
                            AND kpi_formula !~* '^case +when.*>*100 +then +100+.*else +.*end'
                     ";

             $this->_db->execute($query);
        }
    }

		 /**
		 * 24/11/2010 MMT Bz 19384
		 * Return true if the raw name is used in the KPI formula, functions checks for exact string match none case sensitive
		 * if the string found is preceeded or postfixed by an anlphanumerical or '_' it will return false
		 *
		 * @param String $rawName name of the raw counter (col edw_field_name from sys_field_reference)
		 * @param String $formula KPI formula
		 * @return bool
		 */
		public static function isRawUsedInKpiFormula($rawName,$formula){
			$noneRawCharRegEx = '[^a-z0-9._]';

			// construit la regEx, test la présence du raw encadré par deux caracteres non accpeté dans les noms des raws
			$regex = '/'.$noneRawCharRegEx.strtolower($rawName).$noneRawCharRegEx.'/';
			// encapsule la chaine pqr des espaces (car non accepté)  pour que $noneRawCharRegEx soit validé
			// dans le cas ou le nom du raw debute/finit la formule
			$searchContent = ' '.strtolower($formula).' ';
			return preg_match($regex, $searchContent);

		}


} //End class KpiUpload
?>