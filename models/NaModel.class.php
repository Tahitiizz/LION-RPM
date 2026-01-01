<?php
/*
	07/10/2009 NSE
		- Modification de la fonction getCommomNaBetweenAllProducts() : ajout du paramètre excludedProducts
 * 31/05/2011 NSE bz 22349 : Création des fonctions createAgregationPath, deployAgregationPath et getNaInfo
*/
?>
<?php
/**
 *	Classe permettant de manipuler les niveaux d'agrégation
 *	Travaille sur la table sys_definition_network_agregation, sys_definition_group_table_network
 *
 *	@author	BBX - 08/04/2009
 *	@version	CB 4.1.0.0
 *	@since	CB 4.1.0.0
 *
 *  07/10/2009 NSE : Modification de la fonction getCommomNaBetweenAllProducts()
 *                   ajout du paramètre excludedProducts
 *
 *  27/07/2010 OJT : Correction Bz16656
 * 21/04/2011 NSE DE Non unique Labels : création de la méthode IsNonUniqueLabelAuthorized
 * 04/05/2011 NSE bz 22035 : ajout du order by de façon à prendre en compte l'autorisation si elle existe sur une des famille
 * 31/05/2011 NSE bz 22349 : Création des fonctions createAgregationPath, deployAgregationPath et getNaInfo
 * 10/06/2011 MMT :bz 22535 ajout param $firstAxisOnly dans la methode getCommomNaBetweenAllProducts
 * 13/12/2011 SPD1 : Add getNaLabelFromId method for Query builder V2
*/
class NaModel
{
    	/**
	* Propriétés
	*/
        // 14/04/2011 BBX : dans le cadre du BZ 20704
        protected $_id = '';


	/************************************************************************
	* Constructeur
	************************************************************************/
	public function __construct($id = '')
	{
            // 14/04/2011 BBX : dans le cadre du BZ 20704
            $this->_id = $id;
	}

        /**
         * Récupère le label
         * @return string
         * 14/04/2011 BBX : dans le cadre du BZ 20704
         * 27/11/2012 GFS : BZ 30479 - NA levels are not displayed in GIS Supervision
         */
        public function getLabel($product = null)
        {
            $db = Database::getConnection($product);
            $query = "SELECT agregation_label
            FROM sys_definition_network_agregation
            WHERE agregation = '".$this->_id."'
            LIMIT 1";
            return $db->getOne($query);
        }

	/************************ STATIC FUNCTIONS **************************/

	/**
        * Retourne un tableau associatif contenant les niveaux d'agrégation commun
        * entre tous les produits, ordonnés par niveau. Code agrégation en clé,
        * label agrégation en valeur.
	* @param array : tableau des identifiants des produits à exclure
	* @param boolean : uniquement les elements 1er axe (default non)
	* @return : Array()
	*
   *  MaJ 07/10/2009 - NSE - ajout $excludedProducts
	*  10/06/2011 MMT bz 22535 ajout param $firstAxisOnly
   */
	public static function getCommomNaBetweenAllProducts( $excludedProducts = '',$firstAxisOnly = false)
	{
		// Initialisation des variables pour le traitement
		$nbProd = 0;
		$na_levels = $na_temp = $na_labels = $na_ranks = $na_epure = Array();
		if(empty($excludedProducts))
			$excludedProducts = array();
		// Parcours de tous les produits actifs pour récupérer ses niveaux d'agrégation
		foreach(ProductModel::getActiveProducts() as $productArray) {
		  if(!in_array($productArray['sdp_id'],$excludedProducts)){
                    // 31/01/2011 BBX
                    // On remplace new DatabaseConnection() par Database::getConnection()
                    // BZ 20450
		    $dbTemp = Database::getConnection($productArray['sdp_id']);
		    // Requête qui récupère les niveaux d'agrégation du produit
		    $query_na = "SELECT DISTINCT agregation, agregation_label, MAX(agregation_rank) AS rank
					FROM sys_definition_network_agregation ";
			 // 10/06/2011 MMT bz 22535 n'affiche pas les elements 3eme axe dans mes favoris
			 if($firstAxisOnly){
				 $query_na .= " WHERE axe IS NULL ";
			 }
			 $query_na .= "GROUP BY agregation, agregation_label";
		    $result = $dbTemp->execute($query_na);

                    // 27/07/2010 OJT : Bz16656, on exclut les produits sans NA
                    if( pg_num_rows( $result ) > 0 )
                    {
                      while($network = $dbTemp->getQueryResults($result,1)) {
                        // Tableau qui récupère tous les niveaux d'agrégation de tous les produits
                        $na_temp[] = $network['agregation'];
                        // Tableau de référence des labels des niveaux
                        $na_labels[$network['agregation']] = $network['agregation_label'];
                        // Tableau de référence du niveau des niveaux
                        $na_ranks[$network['agregation']] = $network['rank'];
                      }
                    $nbProd++; // Compte les produits
                    }
		  }
		}
		arsort( $na_ranks ); // Tri des niveaux par ordre décroissant
		// On épure les niveaux. On ne garde que les niveaux présents dans tous les produits
		foreach(array_count_values($na_temp) as $value => $freq) {
			if($freq == $nbProd) {
				$na_epure[$value] = $na_labels[$value];
			}
		}
		// On tri les niveaux selon l'ordre de na_ranks
		foreach($na_ranks as $na => $rank) {
			if(array_key_exists($na,$na_epure)) {
				$na_levels[$na] = $na_epure[$na];
			}
		}
		unset($nbProd,$na_temp,$na_labels,$na_ranks,$na_epure); // Libération de la mémoire
		return $na_levels; // Retour du tableau de résultat
	}

	/************************************************************************
	* Méthode buildGroupTableNetwork : construit l table sys_definition_group_table_network
	* en fonction du paramétrage de la base de données.
	*	@param int : id produit
	************************************************************************/
	public static function buildGroupTableNetwork($product='')
	{
		// Instanciation de DatabaseConnection
        // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
		$database = Database::getConnection($product);

		//////////////////////////////////////////////////////////////
		//SOUS ETAPE 1 : Récupération des NA
                // 29/07/2011 BBX
                // Correction de la requête, ajout d'un ORDER BY dans la sous requête
                // BZ 22869
		$req_select = "
		SELECT
			sdna.agregation_rank,
			sdna.agregation,
			sdna.agregation_label,
			sdna.level_operand,
			sdna.level_source,
			sdna.axe,
			sdna.family,
			sdc.rank,
			(SELECT DISTINCT axe FROM sys_definition_network_agregation sdna2 WHERE sdna.family = sdna2.family ORDER BY axe ASC LIMIT 1) AS \"3rdaxis\"
		FROM sys_definition_network_agregation sdna
		JOIN sys_definition_categorie sdc USING (family)
		ORDER BY rank ASC, agregation_level ASC,  axe desc, agregation_rank ASC";

		$req_nbCombinaison = "
		SELECT sum(nbNaByFamily) AS total
		FROM (
			SELECT COUNT(CASE WHEN axe IS NULL THEN agregation ELSE null END)*(CASE WHEN COUNT(CASE WHEN axe IS NULL THEN null ELSE agregation END) = 0 THEN 1 ELSE COUNT(CASE WHEN axe IS NULL THEN null ELSE agregation END) END) AS nbNaByFamily
			FROM sys_definition_network_agregation  GROUP BY family
		) t0
		";

		$statement = $database->getAll($req_select);
		$statement2 = $database->getRow($req_nbCombinaison);

		//////////////////////////////////////////////////////////////
		//SOUS ETAPE 2	: Création des chemins d'agrégation
		//initialisation des variables pour le traitement du tableau
		$rank = 0;
		$id_ligne = 0;
		$previous_family = null;
		$nbLines = $statement2["total"];
		$na_used = array();
		$na_used_axe3 = array();
		$idLignes = array(); // tableau contenant la liste des id_lignes utilisées par NA
		foreach ( $statement as $key => $line )
		{
			$family = $line['family'];
			$id_group_table = $line['rank'];

			if ( $previous_family != $family )
			{
				// Réinitialiser des variables si on change de famille
				$idLignes[$family] = array();
				$rank = -1;
				$na_used = array();
				$na_used_axe3 = array();
			}

			/*
			// On cherche à savoir si le NA est agrégé sur lui même
			$query = "SELECT agregation
			FROM sys_definition_network_agregation
			WHERE family = '".$family."'
			AND agregation = '".$line['agregation']."'
			AND level_source = '".$line['agregation']."'
			AND axe IS NULL";
			$database->execute($query);
			// Détermination du rank
			$rank = ($database->getNumRows() > 0) ? -1 : $rank;*/

			if ($line['3rdaxis'] != 3) // Famille sans troisieme axe
			{
				if ( !in_array($line['agregation'], $idLignes[$family]) )
				{
					$idLignes[$family][$line['agregation']] = ++$id_ligne;
				}
				// RAW
				$patch[$family][] = array (
					'id_ligne' => $id_ligne,
					'id_group_table' => $id_group_table,
					'network_agregation' => $line['agregation'],
					'network_agregation_label' => $line['agregation_label'],
					'rank' => ( $rank == -1 ? -1 : $idLignes[$family][$line['level_source']] ),
					'data_type' => 'raw',
					'on_off' => 1,
					'deploy_status' => 1,
					'id_source' => $idLignes[$family][$line['level_source']]
				);
				//KPI
				$patch[$family][] = array (
					'id_ligne' => $id_ligne+$nbLines,
					'id_group_table' => $id_group_table,
					'network_agregation' => $line['agregation'],
					'network_agregation_label' => $line['agregation_label'],
					'rank' => ( $rank == -1 ? -1 : $idLignes[$family][$line['level_source']]+$nbLines ),
					'data_type' => 'kpi',
					'on_off' => 1,
					'deploy_status' => 1,
					'id_source' => $idLignes[$family][$line['level_source']]+$nbLines
				);

				$rank = 0;
			}
			else // Famille avec un troisième axe
			{
				// Si l'élément réseau est du troisième axe
				if ( $line['axe'] == 3 )
				{
					if ( count($na_used) > 0 )
					{
						foreach ( $na_used as $na => $na_label )
						{
							if ( !in_array($na.'_'.$line['agregation'], $idLignes[$family]) )
							{
								$idLignes[$family][$na.'_'.$line['agregation']] = ++$id_ligne;
							}

							// RAW
							$patch[$family][] = array (
								'id_ligne' => $id_ligne,
								'id_group_table' => $id_group_table,
								'network_agregation' => $na.'_'.$line['agregation'],
								'network_agregation_label' => $na_label.' - '.$line['agregation_label'],
								'rank' => ( $rank == -1 ? -1 : $idLignes[$family][$na.'_'.$line['level_source']] ),
								'data_type' => 'raw',
								'on_off' => 1,
								'deploy_status' => 1,
								'id_source' => $idLignes[$family][$na.'_'.$line['level_source']]
							);
							// KPI
							$patch[$family][] = array (
								'id_ligne' => $id_ligne+$nbLines,
								'id_group_table' => $id_group_table,
								'network_agregation' => $na.'_'.$line['agregation'],
								'network_agregation_label' => $na_label.' - '.$line['agregation_label'],
								'rank' => ( $rank == -1 ? -1 : $idLignes[$family][$na.'_'.$line['level_source']]+$nbLines ),
								'data_type' => 'kpi',
								'on_off' => 1,
								'deploy_status' => 1,
								'id_source' => $idLignes[$family][$na.'_'.$line['level_source']]+$nbLines
							);
						}
						$rank = 0;
					}
					$na_used_axe3[$line['agregation']] = $line['agregation_label'];
				}
				else // si l'élément réseau est du seconde axe
				{
					if ( count($na_used_axe3) > 0 )
					{
						foreach ( $na_used_axe3 as $na => $na_label )
						{
							if ( !in_array($line['agregation'].'_'.$na, $idLignes[$family]) )
							{
								$idLignes[$family][$line['agregation'].'_'.$na] = ++$id_ligne;
							}

							// RAW
							$patch[$family][] = array (
								'id_ligne' => $id_ligne,
								'id_group_table' => $id_group_table,
								'network_agregation' => $line['agregation'].'_'.$na,
								'network_agregation_label' => $line['agregation_label'].' - '.$na_label,
								'rank' => ( $rank == -1 ? -1 : $idLignes[$family][$line['level_source'].'_'.$na] ),
								'data_type' => 'raw',
								'on_off' => 1,
								'deploy_status' => 1,
								'id_source' => $idLignes[$family][$line['level_source'].'_'.$na]
							);
							// KPI
							$patch[$family][] = array (
								'id_ligne' => $id_ligne+$nbLines,
								'id_group_table' => $id_group_table,
								'network_agregation' => $line['agregation'].'_'.$na,
								'network_agregation_label' => $line['agregation_label'].' - '.$na_label,
								'rank' => ( $rank == -1 ? -1 : $idLignes[$family][$line['level_source'].'_'.$na]+$nbLines ),
								'data_type' => 'kpi',
								'on_off' => 1,
								'deploy_status' => 1,
								'id_source' => $idLignes[$family][$line['level_source'].'_'.$na]+$nbLines
							);
						}
						$rank = 0;
					}
					$na_used[$line['agregation']] = $line['agregation_label'];
				}
			}


			$previous_family = $family;
		}

		//////////////////////////////////////////////////////////////
		//SOUS ETAPE 3 : Insertion des chemins d'agrégation en base

		// BEGIN
		$database->execute('BEGIN');

		$req_insert_na = array("TRUNCATE sys_definition_group_table_network", "SELECT pg_catalog.setval('sys_definition_group_table_network_id_ligne_seq', ".($nbLines*2).",true)");

		foreach ( $patch as $family => $lines )
		{
			foreach ( $lines as $line )
			{
				$req_insert_na[] = "INSERT INTO sys_definition_group_table_network (".implode(',',array_keys($line)).") VALUES ('".implode("','",$line)."');";
			}
		}

		// Variable de contrôle
		$execCtrl = true;

		foreach ( $req_insert_na as $req)
		{
			$execCtrl = $execCtrl && (!$database->execute($req) ? false : true);
		}

		// Test de la variable de contrôle. Si une des requêtes précédente à échouée, on Rollback. Sinon on commit.
		$execCtrl ? $database->execute('COMMIT') : $database->execute('ROLLBACK');

		// Retour de la valeur de la variable de contrôle
		return $execCtrl;
	}

	/************************************************************************
	* Méthode deleteNA : surrpime un niveau d'agrégation
	* @param string : niveau d'agrégation
	* @param string : famille
	* @param int : id produit
	* @return : booléen
	************************************************************************/
	public static function deleteNA($tana,$family,$product='')
	{
		// Connexion à la base de données du produit
        // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
		$database = Database::getConnection($product);

		// On regarde si un process est en cours
		$queryProcess = "SELECT * FROM sys_process_encours WHERE encours = 1";
		if(count($database->getAll($queryProcess)) > 0) {
			return false;
		}

		// Pour chaque groupe table existant
		$query = "SELECT DISTINCT id_ligne FROM sys_definition_group_table WHERE family='$family'";
		foreach($database->getAll($query) as $row) {

			$gt[]=$row["id_ligne"];
		}

		for ($k=0;$k<count($gt);$k++)
		{
			// Variable de contrôle
			$execCtrl = true;

			// BEGIN
			$database->execute('BEGIN');

			if(!get_axe3($family, $product))
			{
				$query = "UPDATE sys_definition_group_table_network SET deploy_status=2
				WHERE network_agregation='$tana' AND id_group_table=$gt[$k]";
				$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
			}
			else{
				$lst_na_axe3 = getNaLabelList("na_axe3",$family, $product);
				foreach($lst_na_axe3[$family] as $net=>$val){
					$na = $tana."_".$net;
					$query = "UPDATE sys_definition_group_table_network SET deploy_status=2
					WHERE network_agregation='$na' AND id_group_table=$gt[$k]";
					$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
				}
			}
			$query = "UPDATE sys_definition_group_table SET raw_deploy_status=3,kpi_deploy_status=3,adv_kpi_deploy_status=3
			WHERE id_ligne=$gt[$k]";
			$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);

			// Test de la variable de contrôle. Si une des requêtes précédente à échouée, on Rollback. Sinon on commit.
			$execCtrl ? $database->execute('COMMIT') : $database->execute('ROLLBACK');

			// Si tout est ok, on déploie
			if($execCtrl)
			{
                                // 19/05/2011 BBX - PARTITIONING -
                                // On peut désormais passer une instance de connexion
				$deploy = new deploy($database,$gt[$k]);
				if(count($deploy->types) > 0) $deploy->operate();
				$deploy->display(0);
			}
			else return false;
		}
		$query = "DELETE FROM sys_definition_network_agregation WHERE agregation='$tana' AND family='$family'";
		return (!$database->execute($query) ? false : true);
	}

	/************************************************************************
	* Méthode createNA : crée un niveau d'agrégation
	* @param string : niveau d'agrégation
	* @param string : niveau d'agrégation source
	* @param string : famille
	* @param int : id produit
	* @return : booléen
	************************************************************************/
	public static function createNA($na,$naSource,$family,$product='',$na_max_unique='NULL')
	{
		// Connexion à la base de données du produit
                // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
		$database = Database::getConnection($product);

		// Récupération des infos de la famille
		$query = "SELECT * FROM sys_definition_categorie WHERE family = '{$family}'";
		$familyInfos = $database->getRow($query);

		// Variable de contrôle
		$execCtrl = true;

		// BEGIN
		$database->execute('BEGIN');

		// Calcul des champs
		$table = 'sys_definition_network_agregation';
		$sdna_id = generateUniqId($table);
		$query_max = "Select max(agregation_rank) as nb from $table ";
		$row = $database->getRow($query_max);
		$rank = $row["nb"];
		$rank ++;
		$tana = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $na));
		$tana_label = $na;
		$level_operand = ($na == $naSource) ? '=' : '>';

		// Calcul du level
		$query = "SELECT agregation_level FROM $table WHERE agregation='$naSource'";
		$tana_level = $database->getOne($query);
		switch($level_operand) {
			case "=" : break;
			case ">" : $tana_level++; break;
			case "<" : $tana_level--; break;
		}

		// Requête de création du NA
		$query = "INSERT INTO $table
		(
			agregation_rank,
			agregation,
			agregation_type,
			agregation_label,
			source_default,
			on_off,
			agregation_name,
			agregation_level,
			level_operand,
			level_source,
			mandatory,
			family,
			na_max_unique,
			sdna_id)
		VALUES
		($rank,'$tana','text','$tana_label','$naSource',1,'$tana','$tana_level','$level_operand','$naSource', NULL, '$family', $na_max_unique,'$sdna_id')";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);

        // 31/05/2011 NSE bz 22349 : factorisation du code
		$execCtrl = $execCtrl && self::createAgregationPath($family,$familyInfos,$tana,$naSource,$tana_label,$product);

		// S'il n'y a pas eu d'échecs dans les requêtes, on peut lancer le déploiement
		if($execCtrl)
		{
			$execCtrl = $execCtrl && self::deployAgregationPath($family,$familyInfos,$product);
		}

		// Retour booléen
		return $execCtrl;
	}

    /**
     * Crée les chemins d'agrégation réseau d'un NA
     * @param String $family Famille
     * @param Array $familyInfos tableau des infos sur la famille
     * @param String $tana Network Agregation
     * @param String $naSource Niveau d'agrégation réseau source du NA courant
     * @param String $tana_label Label du Network Agregation
     * @param Integer $product identifiant du produit
     * @return boolean False en cas d'échec de création
     *
     * 31/05/2011 NSE bz 22349 : Création
     */
    public static function createAgregationPath($family,$familyInfos,$tana,$naSource,$tana_label,$product){

        $database = Database::getConnection($product);

		// Détermination des niveaux
		$na = array();
		$na_label = array();
		$na_source = array();
		if(get_axe3($family, $product))
		{
			$lst_na = getNaLabelList("na_axe3",$family, $product);
			// On récupère tous les niveaux d'agrégation 3ème axe afin de créer toutes les combinaisons na_naAxe3 possibles
			foreach($lst_na[$family] as $_na=>$_na_label)
			{
				$na[] = $tana.'_'.$_na;
				$na_label[] = $tana.'_'.$_na;
				$na_source[] = $naSource.'_'.$_na;
			}
		}
		else
		{
			$na[] = $tana;
			$na_label[] = $tana_label;
			$na_source[] = $naSource;
		}
		$execCtrl = true;
		// Traitement
		foreach(Array('raw','kpi') as $op)
		{

			// On créé toutes les combinaisons na_naAxe3 possibles
			foreach($na as $k=>$v)
			{
				$query = "SElECT id_ligne FROM sys_definition_group_table_network
				WHERE id_group_table = '{$familyInfos['rank']}' AND network_agregation = '{$na_source[$k]}' AND data_type = '{$op}'";

				$result = $database->getRow($query);
				$idSource = $result['id_ligne'];

				// Calcul du nouvel id ligne
				$query = "SELECT MAX(id_ligne)+1 AS next_id_ligne FROM sys_definition_group_table_network";
				$result = $database->getRow($query);
				$nextIdLigne = ($result['next_id_ligne'] == '') ? 1 : $result['next_id_ligne'];

				// Insertion dans la table sys_definition_group_table_network
				$query = "INSERT INTO sys_definition_group_table_network
				(id_ligne,id_group_table,network_agregation,network_agregation_label,rank,data_type,on_off,comment,deploy_status,id_source)
				VALUES
				({$nextIdLigne},{$familyInfos['rank']},'{$na[$k]}','{$na_label[$k]}',{$idSource},'{$op}',1,NULL,1,{$idSource});";

				// Exécution de la requête
				$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
			}
		}

		// Test de la variable de contrôle. Si une des requêtes précédente à échouée, on Rollback. Sinon on commit.
		$execCtrl ? $database->execute('COMMIT') : $database->execute('ROLLBACK');

        return $execCtrl;
    }
    /**
     * Déploie les chemins d'agrégation réseau d'une famille
     * @param string $family Famille
     * @param array $familyInfos Tableau des informations sur la famille
     * @param int $product identifiant du produit
     * @return boolean False en cas d'échec de la requête PSQL
     *
     * 31/05/2011 NSE bz 22349 : Création
     */
    public static function deployAgregationPath($family,$familyInfos,$product){

        $database = Database::getConnection($product);

	    // Mise à jour du statut de la famille
		$query = "UPDATE sys_definition_group_table
			SET raw_deploy_status = 3,
			kpi_deploy_status = 3,
			adv_kpi_deploy_status = 3
			WHERE family = '{$family}'";
        $execCtrl = (!$database->execute($query) ? false : true);

		// Déploiement
                        // 19/05/2011 BBX - PARTITIONING -
                        // On peut désormais passer une instance de connexion
			$deploy = new deploy($database, $familyInfos['rank'], $product);
		if(count($deploy->types) > 0) $deploy->operate();
		$deploy->display(0);

		// Retour booléen
		return $execCtrl;
	}

	/********
	* 02/12/2009 BBX
	* Ajouté dans le cadre de la correction du bug 11482
	* Récupère les niveaux d'agrégation d'une famille d'un produit donné
	* @param string : famille
	* @param int : id produit
	* @param int : axe
	* @return : array : tableau des NA
	*******/
	public static function getNaFromFamily($family,$product,$axe=1)
	{
		// Connexion à la base de données du produit
                // 31/01/2011 BBX bz 20450 : On remplace new DatabaseConnection() par Database::getConnection()
		$database = Database::getConnection($product);
		// Axe
		$axeCondition = ($axe == 3) ? 'axe = 3' : 'axe IS NULL';
		// Requête
		$query = "SELECT agregation
		FROM sys_definition_network_agregation
		WHERE family = '{$family}'
		AND {$axeCondition}
		ORDER BY agregation_rank";
		$result = $database->execute($query);
		// Tableau de résultat
		$naArray = Array();
		while($array = $database->getQueryResults($result,1)) {
			$naArray[] = $array['agregation'];
		}
		// Retour du tableau
		return $naArray;
	}

    /**
     * Vérifie si les doublons sur les labels sont autorisés pour les éléments
     * réseau du niveau d'agrégation d'un produit donnés
     *
     * 21/04/2011 NSE DE Non unique Labels : création de la méthode
     *
     * @param String $na niveau d'agrégation vérifié
     * @param int Produit concerné
     * @return Boolean True si les doublons sont autorisés false sinon
     */
    public static function IsNonUniqueLabelAuthorized($na,$product=''){
        $database = Database::getConnection($product);
        // si la colonne uniq_label existe
        if($database->columnExists('sys_definition_network_agregation','uniq_label')){
            // on vérifie sa valeur
            // 04/05/2011 NSE bz 22035 : ajout du order by de façon à prendre en compte l'autorisation si elle existe sur une des famille
            $query = "SELECT uniq_label
                FROM sys_definition_network_agregation
                WHERE agregation = '$na'
                ORDER BY uniq_label asc";
            $uniq_label = $database->getOne($query);
            //echo 'IsNonUniqueLabelAuthorized : '.$query.' -> '.($uniq_label==0?'true':'false').'<br>';
            // si les labels ne doivent pas forcément être unique, c'est que les non uniques sont autorisé
            if($uniq_label==0)
                return true;
            else
                // pour toute autre valeur, on retourne false
                return false;
        }
        else
            return false;
    }

    /**
     * 09/09/2011 BBX BZ 23641 : ajout de l'id produit
     * Retourne les informations sur un Network Agregtion
     * @param Stirng $family Famille
     * @param Stirng $na Network Agregtion
     * @return Array tableau des infos du NA
     */
    public static function getNaInfo($family,$na,$product = 0){
        $database = Database::getConnection($product);
        $query = "SELECT agregation_rank, agregation, agregation_label, source_default, level_operand, level_source
FROM sys_definition_network_agregation
WHERE agregation = '{$na}' AND family = '{$family}'";
        return $database->getRow($query);
    }

    /**
     * Retourne le label d'un Network Aggregation en fonction de son nom et de sa famille
     *
     * @since 5.0.6.00
     * @param string $naName Nom du niveau d'agrégation
     * @param string $famName Nom de la famille
     * @param integer $prodId Identifiant du produit
     * @return string Label du niveau d'aggrégation (ou false)
     */
    public static function getAggregationLabel( $naName, $famName, $prodId )
    {
        // Connexion au produit et exécution de la requête (getOne retournera false en cas de problème)
        $database = Database::getConnection( $prodId );
        return $database->getOne( "SELECT agregation_label FROM sys_definition_network_agregation WHERE family='{$famName}' AND agregation='{$naName}';" );
    }
    
    /**
     * Returns true if given NA exists
     * Added 12/10/2011 BBX for BZ 22256
     * @param type $na NA code or Label
     * @param type $family
     * @param type $product
     * @return type Boolean
     */
    public static function exists($na , $family, $product = '')
    {
        // Database connection
        $db = Database::getConnection( $product );
        
        // Guessing Code from given NA
        $na_code = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $na));
        
        // Query
        $query = "SELECT agregation
        FROM sys_definition_network_agregation 
        WHERE family = '$family'
        AND (agregation IN ('$na_code','$na') OR agregation_label IN ('$na_code','$na'))
        LIMIT 1";
        $db->execute($query);
        
        // Boolean return
        return ($db->getNumRows() > 0);
    }
    
	/* Get NA label from its id
	 * @param $id : na id
	 * @param $product : the product id
	 * @return NA the label
	 * @created SPD1 on 13/12/2011 - Querybuilder v2 */
	public static function getNaLabelFromId($id, $product = null) {		
	    $database = Database::getConnection($product);
	    $query = "SELECT agregation_label FROM sys_definition_network_agregation WHERE agregation = '$id' LIMIT 1";
	    return $database->getOne($query);
	}    
        
        /**
         * Returns an array of all children of a NA
         * @param type $na
         * @param type $product
         * @return type
         */
        public static function getChildrenNa($na, $product = null)
        {
            $db = Database::getConnection($product);
            $children = array();
            $query = "SELECT level_source 
                FROM sys_definition_network_agregation 
                WHERE agregation = '$na'
                AND level_source != '$na'
                GROUP BY level_source";
            $result = $db->execute($query);
            while($row = $db->getQueryResults($result, 1)) {
                $children[] = $row['level_source'];
                $children = array_merge($children, self::getChildrenNa($row['level_source'], $product));
            }
            return array_unique($children);
        }
        
        /**
         * Returns an array of all children of a NA over all products 
         * @param string $na
         * @return array
         * 04/06/2013 NSE bz 34040 : création
         */
        public static function getChildrenNaOnAllProducts($na)
        {
            $children = array();
            $products = ProductModel::getActiveProducts();
            foreach($products as $product){
                $children = array_merge($children, self::getChildrenNa($na, $product['sdp_id']));
            }
            return array_unique($children);
        }
}
