<?php
/**
 * 
 * @cb5100@
 *
 *	17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les exÃ©cutables psql et php (PSQL_DIR et PHP_DIR)
 *  28/07/2010 OJT : Suppression de la constante PHP_DIR (bz17016)
 * 11/10/2011 NSE DE Bypass temporel
 */
?>
<?php
/*
	02/11/2009 GHX
		- Modification d'une requete SQL pour ajouter un champ (numerator_denominator) dans un SELECT
		- Le valeur du kpi_name reste la méme que sur les différents produits, on ne le change pas contrairement aux compteurs
	26/11/2009 GHX
		- Correction du BZ 12976 [CB 5.0][Mixed KPI][Edit counter] erreur SQL si la formule d'une compteur contient des cotes
			-> Modification de la fonction updateListCounters()
	09/12/2009 GHX
		- Correction du BZ 13225 [REC][MIXED-KPI][TC#51613] : erreur sur le dashboard
			-> Lancement du script clean_tables_structure.php dans les functions updateListCounters () et updateListKpis ( )
		- Correction du BZ 13255 [REC][MIXED-KPI] : erreur dans le compute sur les kpi ajoutés
			- Ajout des cotes pour le champ kpi_formula dans la fonction updateListKpis()
		- Correction du BZ 13265 [REC][MIXED-KPI] l'activation en mono produit ne créé par le répertoire upload/export_files_mixed_kpi
			- Création du répertoire export_files_mixed_kpi modification dans la fonction configureConnections ()
		- Correction du BZ 13177[REC][MIXED-KPI] : erreur lors de la création de la famille
			-> Modification dans la fonction launchDeploy()
	10/12/2009 GHX
		- Correction du BZ 13183[REC][MIXED-KPI] : probléme de TA dans "minimal time aggregation"
			-> Modification de la fonction checkConfigTAInAllProducts()
	11/12/2009 GHX
		- Echappement des cotes des commentaires des RAW et KPI
	15/12/2009 GHX
		- Prise en compte du paramétre maximum_mapped_counters lors de l'ajout de compteur
	21/12/2009 GHX
		- Correction du BZ13180 [REC][T&A CB 5.0.2]: les compteurs capture_duration sont visibles
			-> capture_duration/capture_duration_expected sont invsible en restitution
			-> capture_duration/capture_duration_expected sont crées automatiquement pour chaque famille mixed kpi
			-> RI crée automatiquement pour chaque famille mixed kpi
	18/03/2010 NSE  
		- bz 14531 : suppression du suffixage avec le label du produit. 
					 Ajout de l'id du produit dans les tests de mise é jour de faéon é différencier les kpi de mémes noms provenant de produits différents.
        05/05/2010 MPR
                - bz 15259 : Les simples quotes faisaient planter l'insertion des compteurs dans sys_field_reference_tmp
       05/08/2010 MPR
                - bz 14953 : On exclute également le compteur capture_duration_real
        02/11/2010 BBX
                - BZ 18928 : ajout de "updateProductVersion" qui met à jour la version du produit Mixed KPI
		28/10/2011 ACS BZ 23897 Impossible to add some KPI in familly
		
*/
?>
<?php
/**
 * Cette classe permet de manipuler le produit Mixed KPI
 *
 * @author GHX
 */
class MixedKpiModel
{
	/**
	 * Identifiant du produit Mixed KPI
	 * var int
	 */
	private $_idMK = null;
	
	/**
	 * Instance de connexion é la base Mixed KPI
	 * @var DatabaseConnection
	 */
	private $_dbMK = null;
	
	/**
	 * Message d'erreur
	 * @var string
	 */
	private $_msgErrors = '';
	
	/**
	 * Constructeur
	 *
	 * @author GHX
	 */
	public function __construct ()
	{
		$this->_idMK = ProductModel::getIdMixedKpi();
                // 31/01/2011 BBX
                // On remplace new DatabaseConnection() par Database::getConnection()
                // BZ 20450
		$this->_dbMK =  Database::getConnection($this->_idMK);
	} // End function __construct
	
	/**
	 * Retourne l'instance de DatabaseConnection sur la base de données du produit Mixed KPI
	 *
	 * @author GHX
	 * @return DatabaseConnection
	 */
	public function getConnection ()
	{
		return $this->_dbMK;
	} // End function getConnection
	
	/**
	 * Configure les connexions du Mixed KPI en fonction de tous les produits référencés dans le master hors Mixed KPI
	 *
	 * @author GHX
	 */
	public static function configureConnections ()
	{
		$ipServer = get_adr_server();
		
		// Récupére l'identifiant du produit Mixed KPI
		$idMK = ProductModel::getIdMixedKpi();
		// Création d'une connexion sur le produit Mixed KPI
                // 31/01/2011 BBX
                // On remplace new DatabaseConnection() par Database::getConnection()
                // BZ 20450
		$dbMK = Database::getConnection($idMK);
		
		// Boucle sur tous les produits référencés dans le master
		foreach ( ProductModel::getProducts() as $oneProduct )
		{
			// Si c'est le produit Mixed KPI, on passe au produit suivant il n'a pas besoin d'avoir de connexion
			if ( $oneProduct['sdp_id'] == $idMK )
				continue;
			
			$conProduct = $dbMK->getRow("SELECT * FROM sys_definition_connection WHERE connection_directory = '/home/{$oneProduct['sdp_directory']}/upload/export_files_mixed_kpi'");
			// La connexion vers le produit existe
			if ( $conProduct )
			{
				$conModel = new ConnectionModel($conProduct['id_connection'], $idMK);
				$conModel->setValue('on_off', $oneProduct['sdp_on_off']);
				$conModel->update();
			}
			else // La connexion vers le produit n'existe pas
			{
				$conModel = new ConnectionModel(0, $idMK);
				$conModel->setValue('connection_name', $oneProduct['sdp_label']);
				$conModel->setValue('connection_directory', '/home/'.$oneProduct['sdp_directory'].'/upload/export_files_mixed_kpi');
				$conModel->setValue('on_off', $oneProduct['sdp_on_off']);
				$conModel->setValue('protected', 0);
				if ( $oneProduct['sdp_ip_address'] == $ipServer )
				{
					$conModel->setValue('connection_type', 'local');
				}
				else
				{
					$conModel->setValue('connection_type', 'remote');
					$conModel->setValue('connection_ip_address', $oneProduct['sdp_ip_address']);
				}
				
				$conModel->add();
				
				// >>>>>>>>>>
				// 16:02 09/12/2009 GHX
				// Correction du BZ 13265
				$targetDir = '/home/'. $oneProduct['sdp_directory'].'/upload/export_files_mixed_kpi/';
				if ( $oneProduct['sdp_ip_address'] == get_adr_server() )
				{
					// On tente de créer le répertoire export_files_mixed_kpi s'il n'existe pas
					if ( !file_exists($targetDir) )
					{
						mkdir($conModel->getValue('connection_directory'), 0777);
						exec('chmod 0777 "'.$conModel->getValue('connection_directory').'"');
					}
				}
				else // Le produit est sur un serveur distant
				{
					try
					{
						$ssh = new SSHConnection($oneProduct['sdp_ip_address'], $oneProduct['sdp_ssh_user'], $oneProduct['sdp_ssh_password'], $oneProduct['sdp_ssh_port']);
						if ( !$ssh->fileExists($targetDir) )
						{
							$ssh->mkdir($conModel->getValue('connection_directory'));
							$ssh->exec('chmod 0777 "'.$conModel->getValue('connection_directory').'"');
						}
					}
					catch ( Exception $e )
					{
					}
				}
				// <<<<<<<<<<
			}
		}
	} // End function configureConnections
	
	/**
	 * Création d'une famille et retourne son ID
	 *
	 *	21/12/2009 GHX
	 *		- Modification pour la correction du BZ 13180
	 *			-> Création automatique du KPI RI
	 *
	 * @author GHX & NSE
	 * @param string $familyLabel label de la nouvelle famille
	 * @param int  $mainFamily 1 si c'est la famille ou 0 si c'est pas la famille principale
	 * @return int 
	 */
	public function createFamily ( $familyLabel, $mainFamily )
	{
		// Variable de contréle
		$execCtrl = true;
		$query_max = "SELECT CASE WHEN MAX(rank) IS NULL THEN 1 ELSE MAX(rank)+1 END as nb FROM sys_definition_categorie";
		$rank = $this->_dbMK->getOne($query_max);
		
		$query = "SELECT CASE WHEN MAX(id_ligne) IS NULL THEN 1 ELSE MAX(id_ligne)+1 END as nb FROM sys_definition_group_table";
		$rank = $rank>$this->_dbMK->getOne($query_max)?$rank:$this->_dbMK->getOne($query);
		
		$idFamily = 'mk'.$rank;
		
		// 29/03/2010 BBX : désactivation de l'automatic mapping
		$execCtrl = $execCtrl && (!FamilyModel::create($idFamily, $familyLabel, $mainFamily, $this->_idMK, $rank, false) ? false : true);
		
		$execCtrl = $execCtrl && (!$this->configureFlatFileLib($rank, $this->_idMK) ? false : true);
		
		// >>>>>>>>>>
		// 08:49 21/12/2009 GHX
		// Correction du BZ 13180
		// Création automatique du RI pour chaque famille
		$id_ligne = generateUniqId ('sys_definition_kpi');
		$new_date = date('Ymd');
		$familyMod = new FamilyModel($idFamily, $this->_idMK);
		$edw_group_table = $familyMod->getEdwGroupTable();
		$queryInsertRI = "
			INSERT INTO sys_definition_kpi
			(
				id_ligne,
				edw_group_table,
				kpi_type,
				kpi_formula,
				on_off,
				value_type,
				numerator_denominator,
				new_field,
				new_date,
				kpi_name,
				visible,
				kpi_label,
				pourcentage,
				comment
			)
			VALUES
			(
				'{$id_ligne}',
				'{$edw_group_table}',
				'float4',
				'CASE WHEN (capture_duration/capture_duration_expected)*100>100 THEN 100 ELSE (capture_duration/capture_duration_expected)*100 END',
				1,
				'customisateur',
				'total',
				1,
				{$new_date},
				'RI_CAPTURE_DURATION',
				0,
				'RI CAPTURE DURATION',
				1,
				'RI CAPTURE DURATION'
			)
		";
		$this->_dbMK->execute($queryInsertRI);
		// <<<<<<<<<<
		
		return $idFamily;
	}//End createFamily
	
	/**
         * Fonction qui récupére les infos suivantes des compteurs Mixed KPI
	 *
         * @param string family  : famille concernée
         * @return array : tableau contenant [formule,old_id_ligne,code,comment,label]
         *
         */
        public function getInfosCountersMixedKpibyFamily( $family )
        {
            // maj 05/08/2010 - MPR : Correction du BZ 14953  - On exclute également le compteur capture_duration_real
            $query  = " SELECT edw_agregation_formula as formule,
                                nms_field_name,
                                edw_field_name,
                                edw_agregation_function as fonction,
                                comment,
                                edw_field_name_label as label,
                                sfr_product_family as family_parent,
                                sfr_sdp_id as product_parent,
                                old_id_ligne as id_ligne_parent,
                                f.id_ligne
                        FROM    sys_field_reference f, sys_definition_group_table  g
                        WHERE   on_off = 1
                                AND f.visible = 1
                                AND family ='{$family}'
                                AND f.edw_group_table = g.edw_group_table
                                AND lower(nms_field_name) NOT IN ('capture_duration_expected','capture_duration','capture_duration_real')
                        ORDER BY product_parent,family_parent,id_ligne_parent,label
                        ";

            $result = $this->_dbMK->getAll($query);

            $counters = array();
            if( count($result) > 0 ){

                foreach( $result as $row )
                {
                    $counters[ $row['product_parent'] ][] = array(
                        'family'            => $row['family_parent'],
                        'id_ligne_parent'   => $row['id_ligne_parent'],
                        'label'             => $row['label'],
                        'nms_field_name'    => $row['nms_field_name'],
                        'edw_field_name'    => $row['edw_field_name'],
                        'comment'           => $row['comment'],
                        'formule'           => $row['formule'],
                        'fonction'           => $row['fonction'],
                        'id_ligne'          => $row['id_ligne']
                    );
                }
            }
            // __debug($counters,"RESULT");

            return $counters;
        }

        /**
         * Fonction qui récupére les infos suivantes des compteurs d'un produit parent
         *
         * @param integer idProduct : id du produit parent
         * @return array counters : Tableau contenant l'ensemble des comtpeurs
         */
        public function getInfosCountersProductParent( $idProduct, $countersMixedKpi)
        {
             // maj 05/08/2010 - MPR : Correction du BZ 14953  - On exclute  galement le compteur capture_duration_real
             // GFS 30/11/2013 Bug 23077 - [SUP][T&A OMC Ericsson BSS][Airtel Zambia][Mixed KPI] Compute Raw blocked after counter SYNCHRONIZE
            $query  = "SELECT
                            family,
                            nms_field_name,
                            edw_field_name,
                            sfr.edw_agregation_formula as formule,
                            sfr.edw_agregation_function as fonction,
                            sfr.comment,
                            sfr.edw_field_name_label as label,
                            sfr.id_ligne
                       FROM
                            sys_field_reference AS sfr
                            LEFT JOIN sys_definition_group_table USING(edw_group_table)
                            LEFT JOIN sys_definition_categorie AS sdc USING(family)
                       WHERE
                            sfr.id_ligne IN ('".implode("','",$countersMixedKpi)."')
                            AND lower(sfr.edw_target_field_name) NOT IN ('capture_duration_expected','capture_duration','capture_duration_real')
                       ORDER BY family,id_ligne,label
                    ";
                        //__debug($query,"QUERY");
            $db = Database::getConnection($idProduct);
            $result = $db->getAll($query);

            $counters = array();
            if( count($result) > 0 ){

                foreach( $result as $row )
                {
                    $counters[] = array(
                        'family'            => $row['family'],
                        'id_ligne'          => $row['id_ligne'],
                        'label'             => $row['label'],
                        'nms_field_name'    => $row['nms_field_name'],
                        'edw_field_name'    => $row['edw_field_name'],
                        'comment'           => $row['comment'],
                        'formule'           => $row['formule'],
                        'fonction'           => $row['fonction']
                    );
                }
            }
            // __debug($counters,"RESULT");

            return $counters;
        }

	/**
	 * Retourne la liste des compteurs par rapport an NA communs des diff?rents produits pour la famille Mixed KPI s?lectionn?
	 *
	 * @author GHX
	 * @param string $idFamily
	 * @param boolean $onOff permet de savoir on veut uniquement les compteurs activé (default true)
	 * @param boolean $visible permet de savoir on veut uniquement les compteurs visibles (default true)
	 * @return array
	 */
	public function getAvailableCountersDependingNaCommon ( $idFamily, $onOff = true, $visible = true  )
	{
		$counters = array();
		foreach ( $this->getFamiliesByProduct($idFamily) as $idProduct => $listFamily )
		{
                        // 31/01/2011 BBX
                        // On remplace new DatabaseConnection() par Database::getConnection()
                        // BZ 20450
			$db = Database::getConnection($idProduct);
			// RÃ©cupÃ¨re la liste des compteurs pour une famille et un
			// 08:35 21/12/2009 GHX
			// Correction du BZ 13180
			// On n'affiche pas les capture_duration et capture_duration_expected
                         // maj 05/08/2010 - MPR : Correction du BZ 14953  - On exclute également le compteur capture_duration_real
			$queryListCounters = "
					SELECT
						sfr.id_ligne,
						family,
						lower(edw_target_field_name) AS edw_target_field_name,
						lower(edw_field_name) AS edw_field_name,
						sfr.edw_agregation_function,
						sfr.edw_agregation_formula,
						sfr.comment,
						sfr.edw_field_name_label,
						sfr.edw_field_type,
						sfr.default_value,
						sfr.on_off,
						sfr.visible,
						family_label
					FROM
						sys_field_reference AS sfr
						LEFT JOIN sys_definition_group_table USING(edw_group_table) 
						LEFT JOIN sys_definition_categorie AS sdc USING(family)
					WHERE
						family IN ('".implode("','",$listFamily)."')
						AND lower(sfr.edw_target_field_name) NOT IN ('capture_duration_expected','capture_duration','capture_duration_real')

				";
			if ( $onOff ) // On veut uniquement les compteurs é ON
			{
				$queryListCounters .= " AND sfr.on_off = 1";
			}
			if ( $visible ) // On veut uniquement les compteurs visible
			{
				$queryListCounters .= " AND sfr.visible = 1";
			}
			$queryListCounters .= "
					ORDER BY
						sdc.rank,
						edw_field_name_label
				";
			
			$resultListCounters = $db->getAll($queryListCounters);
			
			if ( count($resultListCounters) > 0 )
			{
				$counters[$idProduct] = $resultListCounters;
			}
		}
		
		return $counters;
	} // End function getAvailableCountersDependingNaCommon
	
	/**
	 * Retourne la liste des kpis par rapport aux NA communs des différents produits pour la famille Mixed KPI sélectionnée
	 *
	 * @author NSE d'aprés GHX
	 * @param string $idFamily
	 * @param boolean $onOff permet de savoir si on veut uniquement les compteurs activés (default true)
	 * @param boolean $visible permet de savoir si on veut uniquement les compteurs visibles (default true)
	 * @return array
	 */
	public function getAvailableKpisDependingNaCommon ( $idFamily, $onOff = true, $visible = true  )
	{
		$kpis = array();
		foreach ( $this->getFamiliesByProduct($idFamily) as $idProduct => $listFamily )
		{
			$db = Database::getConnection( $idProduct );
			// Récupére la liste des compteurs pour les familles
			$queryListCounters = "
					SELECT
						family,
						lower(kpi_name) AS edw_target_field_name,
						lower(kpi_name) AS edw_field_name, --lower(edw_field_name) AS edw_field_name,
						--sdk.edw_agregation_function,
						sdk.kpi_formula as edw_agregation_formula,
						sdk.comment,
						sdk.kpi_label as edw_field_name_label,
						sdk.kpi_type as edw_field_type,
						--sdk.default_value,
						sdk.on_off,
						sdk.visible,
						family_label
					FROM
						sys_definition_kpi AS sdk
						LEFT JOIN sys_definition_group_table USING(edw_group_table) 
						LEFT JOIN sys_definition_categorie AS sdc USING(family)
					WHERE
						family IN ('".implode("','",$listFamily)."')
				";
			if ( $onOff ) // On veut uniquement les compteurs é ON
			{
				$queryListCounters .= " AND sdk.on_off = 1";
			}
			if ( $visible ) // On veut uniquement les compteurs visible
			{
				$queryListCounters .= " AND sdk.visible = 1";
			}
			$queryListCounters .= "
					ORDER BY
						sdc.rank,
						kpi_label --edw_field_name_label
				";
			$resultListCounters = $db->getAll($queryListCounters);
			
			if ( count($resultListCounters) > 0 )
			{
				$kpis[$idProduct] = $resultListCounters;
			}
		}
		
		return $kpis;
	} // End function getAvailableKpisDependingNaCommon
	
	/**
	 * Retourne la liste des compteurs par rapport an NA communs des différents produits pour la famille Mixed KPI sélectionné
	 *
	 * @author GHX
	 * @param string $idFamily
	 * @param boolean $onOff permet de savoir on veut uniquement les compteurs activé (default true)
	 * @param boolean $visible permet de savoir on veut uniquement les compteurs visibles (default true)
	 * @return array
	 */
	public function getAvailableCountersDependingNaCommonByFamily ( $idFamily, $onOff = true, $visible = true )
	{
		$counters = array();
		$lastIdProduct = null;
		$db = null;
		foreach ( $this->getFamiliesByProduct($idFamily) as $idProduct => $listFamilies )
		{
			if ( $idProduct != $lastIdProduct )
			{
				$db = Database::getConnection( $idProduct );
				$lastIdProduct = $idProduct;
			}
			foreach ( $listFamilies as $family )
			{
				// Récupére la liste des compteurs pour une famille et un
			 // maj 05/08/2010 - MPR : Correction du BZ 14953  - On exclute également le compteur capture_duration_real
				$queryListCounters ="
						SELECT
							sfr.id_ligne,
							family,
							nms_field_name,
							lower(edw_target_field_name) AS edw_target_field_name,
							lower(edw_field_name) AS edw_field_name,
							sfr.edw_agregation_function,
							sfr.edw_agregation_formula,
							sfr.comment,
							sfr.edw_field_name_label,
							sfr.edw_field_type,
							sfr.default_value,
							sfr.on_off,
							sfr.visible,
							family_label
						FROM
							sys_field_reference AS sfr
							LEFT JOIN sys_definition_group_table USING(edw_group_table) 
							LEFT JOIN sys_definition_categorie AS sdc USING(family)
						WHERE
							family = '{$family}'
							AND lower(sfr.edw_target_field_name) NOT IN ('capture_duration_expected','capture_duration','capture_duration_real')
						";
				if ( $onOff ) // On veut uniquement les compteurs é ON
				{
					$queryListCounters .= " AND sfr.on_off = 1";
				}
				if ( $visible ) // On veut uniquement les compteurs visible
				{
					$queryListCounters .= " AND sfr.visible = 1";
				}
				$queryListCounters .= " 
						ORDER BY
							edw_field_name_label
					";
				
				$resultListCounters = $db->execute($queryListCounters);
				if ( $db->getNumRows() > 0 )
				{
					while ( $row = $db->getQueryResults($resultListCounters, 1) )
					{
						$counters[$idProduct][$family][$row['edw_field_name']] = $row;
					}
				}
			}
		}
		
		return $counters;
	} // End function getAvailableCountersDependingNaCommonByFamily
	
	/**
	 * Retourne la liste des compteurs par rapport an NA communs des différents produits pour la famille Mixed KPI sélectionné
	 *
	 * @author GHX
	 * @param string $idFamily
	 * @param boolean $onOff permet de savoir on veut uniquement les compteurs activé (default true)
	 * @param boolean $visible permet de savoir on veut uniquement les compteurs visibles (default true)
	 * @return array
	 */
	public function getAvailableKpisDependingNaCommonByFamily ( $idFamily, $onOff = true, $visible = true )
	{
		$counters = array();
		$lastIdProduct = null;
		$db = null;
		foreach ( $this->getFamiliesByProduct($idFamily) as $idProduct => $listFamilies )
		{
			if ( $idProduct != $lastIdProduct )
			{
				$db = Database::getConnection( $idProduct );
				$lastIdProduct = $idProduct;
			}
			foreach ( $listFamilies as $family )
			{
				// Récupére la liste des compteurs pour une famille et un
				// 11:51 02/11/2009 GHX
				// Ajout du champ "numerator_denominator" dans le SELECT
                                // 11/02/2011 BBX
                                // Ajout de la récupération du pourcentage
                                // BZ 17515
				$queryListKpis ="
						SELECT
							sdk.id_ligne,
							family,
							--nms_field_name,
							lower(kpi_name) AS edw_target_field_name,
							lower(kpi_name) AS edw_field_name,
							--sdk.edw_agregation_function,
							sdk.kpi_formula AS edw_agregation_formula,
							sdk.comment,
                                                        sdk.pourcentage,
							sdk.kpi_label AS edw_field_name_label,
							sdk.kpi_type AS edw_field_type,
							--sdk.default_value,
							sdk.on_off,
							sdk.visible,
							sdk.numerator_denominator,
							family_label
						FROM
							sys_definition_kpi AS sdk
							LEFT JOIN sys_definition_group_table USING(edw_group_table) 
							LEFT JOIN sys_definition_categorie AS sdc USING(family)
						WHERE
							family = '{$family}'
						";
				if ( $onOff ) // On veut uniquement les compteurs é ON
				{
					$queryListKpis .= " AND sdk.on_off = 1";
				}
				if ( $visible ) // On veut uniquement les compteurs visible
				{
					$queryListKpis .= " AND sdk.visible = 1";
				}
				$queryListKpis .= " 
						ORDER BY
							edw_field_name_label
					";
				$resultListKpis = $db->execute($queryListKpis);
				if ( $db->getNumRows() > 0 )
				{
					while ( $row = $db->getQueryResults($resultListKpis, 1) )
					{
						$kpis[$idProduct][$family][$row['edw_field_name']] = $row;
					}
				}
			}
		}
		
		return $kpis;
	} // End function getAvailableCountersDependingNaCommonByFamily
	
	/**
	 * Retourne la liste des compteurs d'une famille sys_field_reference
	 * 29/03/2010 BBX : on utilise désormais nms_field_name pour faire le lien
	 * En gros, nms_field_name du produit MK = edw_field_name du produit parent + trigramme + famille
	 *
	 * @author GHX
	 * @param string $idFamily  identifiant de la famille Mixed KPI
	 * @return array
	 */
	public function getCounters ( $idFamily )
	{
		$counters = array();
                // maj 05/08/2010 - MPR : Correction du BZ 14953 - On exclute également le compteur capture_duration_real
		$resultListCounters = $this->_dbMK->execute("
				SELECT
					family,
					lower(nms_field_name) AS nms_field_name,
					lower(edw_target_field_name) AS edw_target_field_name,
					lower(edw_field_name) AS edw_field_name,
					edw_agregation_function,
					edw_agregation_formula,
					comment,
					edw_field_name_label,
					family_label,
					edw_field_type,
					default_value,
					sfr_sdp_id,
					sfr_product_family,
					old_id_ligne
				FROM
					sys_field_reference AS sfr
					LEFT JOIN sys_definition_group_table USING(edw_group_table) 
					LEFT JOIN sys_definition_categorie AS sdc USING(family)
				WHERE
					family = '{$idFamily}'
					AND sfr.on_off = 1
					AND sfr.visible = 1
					AND new_field <> 2
					AND lower(sfr.edw_target_field_name) NOT IN ('capture_duration_expected','capture_duration','capture_duration_real')
				ORDER BY
					edw_field_name_label
			");
		
		// Création de 3 tableaux qui serviront pour le tri
		$tabSortByProduct = array();
		$tabSortByFamily = array();
		$tabSortByCounter = array();
		if ( $this->_dbMK->getNumRows() > 0 )
		{
			while ( $row = $this->_dbMK->getQueryResults($resultListCounters, 1) )
			{
				$tabSortByProduct[] = $row['sfr_sdp_id'];
				$tabSortByFamily[] = FamilyModel::getLabel($row['sfr_product_family'], $row['sfr_sdp_id']);
				$tabSortByCounter[] = $row['edw_field_name_label'];
				
				$row['product_family_label'] = FamilyModel::getLabel($row['sfr_product_family'], $row['sfr_sdp_id']);
				$counters[$row['nms_field_name']] = $row;
			}
		}
		// On tri en fonction du produit, de la famille et du label du compteur
		array_multisort($tabSortByProduct, SORT_ASC, $tabSortByFamily, SORT_ASC, $tabSortByCounter, SORT_ASC, $counters);
		return $counters;
	} // End function getCounters
	
	
	/**
	 * Retourne la liste des Kpis d'une famille 
	 *
	 * @author NSE d'aprés GHX
	 * @param string $idFamily  identifiant de la famille Mixed KPI
	 * @return array
	 */
	public function getKpis ( $idFamily )
	{
		$counters = array();
		$resultListCounters = $this->_dbMK->execute("
				SELECT
					family,
					lower(kpi_name) AS edw_target_field_name,
					lower(kpi_name) AS edw_field_name,
					--edw_agregation_function,
					kpi_formula as edw_agregation_formula,
					comment,
					kpi_label as edw_field_name_label,
					family_label,
					sdk.kpi_type as edw_field_type,
					--default_value,
					sdk_sdp_id AS sfr_sdp_id,
					sdk_product_family AS sfr_product_family
				FROM
					sys_definition_kpi AS sdk
					LEFT JOIN sys_definition_group_table USING(edw_group_table) 
					LEFT JOIN sys_definition_categorie AS sdc USING(family)
				WHERE
					family = '{$idFamily}'
					AND sdk.on_off = 1
					AND sdk.visible = 1
					AND new_field <> 2
				ORDER BY
					kpi_label
			");
		// Création de 3 tableaux qui serviront pour le tri
		$tabSortByProduct = array();
		$tabSortByFamily = array();
		$tabSortByCounter = array();
		if ( $this->_dbMK->getNumRows() > 0 )
		{
			while ( $row = $this->_dbMK->getQueryResults($resultListCounters, 1) )
			{
				$tabSortByProduct[] = $row['sfr_sdp_id'];
				$tabSortByFamily[] = FamilyModel::getLabel($row['sfr_product_family'], $row['sfr_sdp_id']);

				// NSE bz 14531 : on ajoute l'indication du produit d'origine pour différencier les Kpi de méme nom
				$tabSortByCounter[] = $row['edw_field_name_label'].'-'.$row['sfr_sdp_id'];
				
				$row['product_family_label'] = FamilyModel::getLabel($row['sfr_product_family'], $row['sfr_sdp_id']);
				
				// NSE bz 14531 : on ajoute l'indication du produit d'origine pour différencier les Kpi de méme nom
				$counters[$row['edw_field_name'].'-'.$row['sfr_sdp_id']] = $row;
			}
		}
		// On trie en fonction du produit, de la famille et du label du Kpi
		array_multisort($tabSortByProduct, SORT_ASC, $tabSortByFamily, SORT_ASC, $tabSortByCounter, SORT_ASC, $counters);
		return $counters;
	} // End function getCounters
	
	
	/**
	 * Supprime une famille
	 *
	 * @author NSE
	 * @param string $idFamily identifiant de la famille
	 * @param int $product identifiant du produit
	 */
	public static function deleteFamily ( $idFamily, $product )
	{
		$database = Database::getConnection( $product );
		
		// Variable de contréle
		$execCtrl = true;
		
		$query = "DELETE FROM sys_definition_mixedkpi WHERE sdm_id = '$idFamily'";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		
		$query = "DELETE FROM sys_definition_flat_file_lib WHERE id_flat_file = (SELECT rank FROM sys_definition_categorie WHERE family = '$idFamily')";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		
		$query = "DELETE FROM sys_link_filetype_grouptable WHERE flat_file_id = (SELECT rank FROM sys_definition_categorie WHERE family = '$idFamily')";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		
		$execCtrl = $execCtrl && (!FamilyModel::delete($idFamily,$product) ? false : true);
		

		// 11:44 17/11/2009 GHX
		// Si on a supprime la famille principale on réaffacte la premier famille en tant que famille principale
		if ( (int)$database->getOne("SELECT COUNT(*) FROM sys_definition_categorie WHERE main_family = 1") == 0 )
		{
			$database->execute("UPDATE sys_definition_categorie SET main_family = 1 WHERE rank = (SELECT MIN(rank) FROM sys_definition_categorie)");
		}
		
		return $execCtrl;
	} // End function deleteFamily
	
	/**
	 *
	 *
	 * @author NSE
	 */
	public function updateSelectedFamilies($idFamily,$selectedFamilies)
	{
		// Variable de contréle
		$execCtrl = true;
		
		$query = "DELETE FROM sys_definition_mixedkpi where sdm_id='$idFamily'";
		$this->_dbMK->execute($query);
		
		foreach($selectedFamilies as $prod => $families){
			foreach ($families as $family) {
                            $query = "INSERT INTO sys_definition_mixedkpi (sdm_id, sdm_sdp_id, sdm_family) VALUES('$idFamily','$prod','$family')";
                            $execCtrl = $execCtrl && (!$this->_dbMK->execute($query) ? false : true);
                            // 11/10/2011 NSE DE Bypass temporel : 
                            // la famille mixed kpi est bypassée si une des familles qui la compose est bypassée
                            // pour chaque Ta de la famille composant la famille mixed kpi
                            foreach(TaModel::getAllTaForFamily($family,$prod) as $ta){
                                // si la Ta est supérieure à la Ta Min du Mixed kpi 
                                if(TaModel::isTa1Greater($ta,$this->getTaMin(),$prod)>0){
                                    if(TaModel::IsTABypassedForFamily($ta, $family, $prod)==1){
                                        // on bypasse la famille pour la Ta
                                        $query = "UPDATE sys_definition_categorie set ta_bypass='{$ta}' WHERE family='$idFamily'";
                                        $execCtrl = $execCtrl && (!$this->_dbMK->execute($query) ? false : true);
                                    }
                                }
                            }
			}
		}
		
		return $execCtrl;
	} // End function updateSelectedFamilies
	
	/**
	 * Retourne le TA minimum configuré pour le produit Mixed KPI
	 *
	 * @author GHX
	 * @return string
	 */
	public function getTaMin ()
	{
		// le niveau le plus bas configuré actuellement sur le produit Mixd KPI
		$talist = getTaList('', $this->_idMK);
		list($taMin) = array_keys($talist);
		return $taMin;
	} // End function getTaMin
	
	/**
	 * Configure la table sys_definition_flat_file_lib et sys_link_filetype_grouptable
	 *
	 * @author GHX
	 */
	public function configureFlatFileLib ()
	{
		$taMin = $this->getTaMin();
		
		$tempo = 6;
		if ( $taMin == 'day' ) $tempo = 48;
			
		$infoFamilies = $this->_dbMK->getAll("SELECT rank, family, family_label FROM sys_definition_categorie");
		
                // // 14/10/2011 NSE DE Bypass temporel
                // Recherche de l'id pour les flat_file bypassés
                $querymax = "SELECT MAX(rank) 
                FROM sys_definition_categorie ";
                $maxIdGroupTable = $this->_dbMK->getOne($querymax);  
                
                // 14/10/2011 NSE DE Bypass temporel : modification du fonctionnement : 
                // on ne met plus à jour la table, on la vide et on la rempli entièrement à chaque fois, comme en Corporate
                // On vide les tables sys_link_filetype_grouptable et sys_definition_flat_file_lib
                $query = "TRUNCATE TABLE sys_link_filetype_grouptable";
                $this->_dbMK->execute($query);
                $query = "TRUNCATE TABLE sys_definition_flat_file_lib";
                $this->_dbMK->execute($query);
                
		if ( count($infoFamilies) > 0 )
		{
			foreach ( $infoFamilies as $family )
			{
                            // 14/10/2011 NSE DE Bypass temporel : modification du fonctionnement on ne met plus à jour la table
				/*$this->_dbMK->execute("SELECT * FROM sys_definition_flat_file_lib WHERE id_flat_file = '{$family['rank']}'");
				// Le type de fichier é récupérer correspondant é la famille n'est pas défini
				if ( $this->_dbMK->getNumRows() == 0 )
				{*/
                            $freq = array("day" => "24", "hour"=>"1");
                            $chunks_max = array("day"=> 1,"hour"=>24);
                            $_columns_SA = "";
                            $_values_SA = "";
                            if(get_sys_global_parameters( "activation_source_availability", 0, $this->_idMK ) )
                            {
                                $_columns_SA = ", data_collection_frequency, data_chunks, granularity";
                                $_values_SA = ",{$freq[$taMin]},{$chunks_max[$taMin]},'{$taMin}'";
                            }
                            $queryInsert = "
                                        INSERT INTO sys_definition_flat_file_lib
                                        (
                                                id_flat_file,
                                                flat_file_name,
                                                flat_file_naming_template,
                                                on_off,
                                                alarm_missing_file_temporization,
                                                period_type,
                                                exclusion,
                                                prefix_counter,
                                                reference,
                                                ordre
                                            {$_columns_SA}
                                        )
                                        VALUES
                                        (
                                                {$family['rank']},
                                                'Mixed KPI {$family['family_label']}',
                                                'auto_*_{$family['family']}_*_[0-9_H]+.csv',
                                                1,
                                                {$tempo},
                                                '{$taMin}',
                                                NULL,
                                                NULL,
                                                NULL,
                                                {$family['rank']}
                                            {$_values_SA}
                                        )";
                            $this->_dbMK->execute($queryInsert);

                            $queryInsert2 = "
                                    INSERT INTO sys_link_filetype_grouptable
                                    (
                                            id_group_table,
                                            flat_file_id
                                    )
                                    VALUES
                                    (
                                            {$family['rank']},
                                            {$family['rank']}
                                    )";
                            $this->_dbMK->execute($queryInsert2);
                        /*}
                        else
                        {
                            $queryUpdate = "
                                    UPDATE sys_definition_flat_file_lib SET
                                            flat_file_name = 'Mixed KPI {$family['family_label']}',
                                            flat_file_naming_template = 'auto_*_{$family['family']}_*_[0-9_H]+.csv',
                                            alarm_missing_file_temporization = {$tempo},
                                            period_type = '{$taMin}'
                                    WHERE
                                            id_flat_file = {$family['rank']}
                            ";
                            $this->_dbMK->execute($queryUpdate);
                        }*/

                            // 11/10/2011 NSE DE Bypass temporel : ajout d'une ligne pour générer le Data Export de la Ta Bypassée
                            foreach(TaModel::getAllTaForFamily($family['family']) as $ta){

                                // si la Ta est supérieure à la Ta Min du Corporate et différente de la Ta de l’enregistrement courant
                                if(TaModel::isTa1Greater($ta,$family['ta_min'],$product)>0){
                                    if(TaModel::IsTABypassedForFamily($ta, $family['family'], $product)==1){ 
                                        $maxIdGroupTable++;
                                        if(get_sys_global_parameters( "activation_source_availability", 0, $this->_idMK ) )
                                        {
                                            $_columns_SA = ", data_collection_frequency, data_chunks, granularity";
                                            $_values_SA = ",{$freq[$ta]},{$chunks_max[$ta]},'{$ta}'";
                                        }
                                        // On ajoute un enregistrement avec la Ta trouvée
                                        // Construction de sys_definition_flat_file_lib
                                        $insert = "INSERT INTO sys_definition_flat_file_lib
                                        (
                                                id_flat_file,
                                                flat_file_name,
                                                flat_file_naming_template,
                                                on_off,
                                                alarm_missing_file_temporization,
                                                period_type,
                                                exclusion,
                                                prefix_counter,
                                                reference,
                                                ordre
                                            {$_columns_SA}
                                        )
                                        VALUES
                                        (
                                                {$maxIdGroupTable},
                                                'Mixed KPI {$family['family_label']}',
                                                'auto_*_{$family['family']}_*_bypass_{$ta}_[0-9]+.csv',
                                                1,
                                                {$tempo},
                                                '{$ta}',
                                                NULL,
                                                NULL,
                                                NULL,
                                                -{$maxIdGroupTable}
                                            {$_values_SA}
                                        )";
                                        $this->_dbMK->execute($insert);

                                        // Construction de sys_link_filetype_grouptable
                                        $insert = "INSERT INTO sys_link_filetype_grouptable
                                        (id_group_table,flat_file_id)
                                        VALUES (".$family['id_group_table'].",".$maxIdGroupTable.")";
                                        $this->_dbMK->execute($insert);
                                    }
                                }
                            }
			}
		}
	} // End function configureFlatFileLib
	
	/**
	 * Retourne la liste de NA en commun pour une famille Mixed KPI
	 *
	 * @author GHX
	 * @param string $idFamily
	 * @return array
	 */
	public function getCommonNaBetweenFamilyAndProducts ( $idFamily )
	{
		$selectedFamilies = $this->getFamiliesByProduct($idFamily);
		return FamilyModel::getCommonNaBetweenFamilyAndProducts($selectedFamilies);
	} // End function getCommonNaBetweenFamilyAndProducts
	
	/**
	 * Retourne la liste des familles des différents produit qui compose une famille Mixed KPI
	 *
	 * @author GHX
	 * @param string $idFamily 
	 * @return array
	 */
	public function getFamiliesByProduct ( $idFamily )
	{
		$selectedFamilies = array();
		$query_na = "SELECT * FROM sys_definition_mixedkpi WHERE sdm_id='{$idFamily}'";
		$result = $this->_dbMK->execute($query_na);
		while($network = $this->_dbMK->getQueryResults($result,1)) {
			$selectedFamilies[$network['sdm_sdp_id']][] = $network['sdm_family'];
		}
		return $selectedFamilies;
	} // End function getFamiliesByProduct

	/**
	 * Retourne la liste des familles des différents produit qui compose une famille Mixed KPI
	 *
	 * @author GHX
	 * @param string $idFamily 
	 * @return array
	 */
	public function getFamiliesWithCountersByProduct ( $idFamily )
	{
		$selectedFamilies = array();
		
		$query_na = "
		SELECT
			sdm_sdp_id, sdm_family
		FROM
			sys_definition_mixedkpi WHERE sdm_id='{$idFamily}'
			AND ROW(sdm_sdp_id, sdm_family) IN (
						SELECT sfr_sdp_id, sfr_product_family 
						FROM sys_field_reference 
						WHERE edw_group_table = (
									SELECT edw_group_table 
									FROM sys_definition_group_table 
									WHERE family = '{$idFamily}'
								)
					)
		";
		$result = $this->_dbMK->execute($query_na);
		while($network = $this->_dbMK->getQueryResults($result,1)) {
			$selectedFamilies[$network['sdm_sdp_id']][] = $network['sdm_family'];
		}
		return $selectedFamilies;
	} // End function getFamiliesWithCountersByProduct
	
	/**
	 * Retourne la liste des familles des différents produit qui possédent un kpi
	 *
	 * @author NSE 
	 * @param string $idFamily 
	 * @return array
	 */
	public function getFamiliesWithKpisByProduct ( $idFamily )
	{
		$selectedFamilies = array();
		
		$query_na = "
		SELECT
			sdm_sdp_id, sdm_family
		FROM
			sys_definition_mixedkpi WHERE sdm_id='{$idFamily}'
			AND ROW(sdm_sdp_id, sdm_family) IN (
						SELECT sdk_sdp_id, sdk_product_family 
						FROM sys_definition_kpi 
						WHERE edw_group_table = (
									SELECT edw_group_table 
									FROM sys_definition_group_table 
									WHERE family = '{$idFamily}'
									--AND raw_deploy_status != 2 AND kpi_deploy_status != 2
								)
					)
		";
		$result = $this->_dbMK->execute($query_na);
		while($network = $this->_dbMK->getQueryResults($result,1)) {
			$selectedFamilies[$network['sdm_sdp_id']][] = $network['sdm_family'];
		}
		return $selectedFamilies;
	} // End function getFamiliesWithKpisByProduct
	
	/**
	 * Met é jour la liste des compteurs d'une famille
	 * 31/03/2010 BBX : recodage d'une bonne partie de la fonction dans le cadre de la DE sur le trigramme	
	 *
	 * @author GHX
	 * @param string $idFamily identifiant de la famille Mixed KPI
	 * @param array $countersSelected liste des compteurs de la famille classé par identifant de produit > identifiant de la famille > liste des compteurs
	 */
	public function updateListCounters ( $idFamily, $countersSelected )
	{
		// Détermine l'ajout automatique de compteurs
		$addedCounter = false;

		// On récupére la liste des produits pour avoir leur label
		$productsInformations = getProductInformations();
		
		// Création d'une table temporaire dans laquel on va mettre les compteurs sélectionnées
		$this->_dbMK->execute("DROP TABLE IF EXISTS sys_field_reference_tmp");
		$this->_dbMK->execute("CREATE TABLE sys_field_reference_tmp (LIKE sys_field_reference EXCLUDING CONSTRAINTS)");
		
		// Valeur communes é tous les compteurs et qui ne change pas
		$familyMod = new FamilyModel($idFamily, $this->_idMK);

		// Champs communs é tous les compteurs
		$edw_group_table = $familyMod->getEdwGroupTable();
		$id_group_table  = $familyMod->getValue('rank');
		$new_date = date('Ymd');
		$new_field = 1;
		$on_off = 1;
		$visible = 1;
		
                // 26/01/2012 BBX
                // BZ 24844 : Création des compteurs capture duration * une seule fois apr famille mixed kpi
                $captureDurationCounters = false;

		// Notre liste de compteurs finale
		$selectedCountersList = Array();		
		
		// On parcours une premiére fois les compteurs sélectionnés afin d'effectuer les opérations suivantes :
		//	=> Si un compteur est référencé dans une formule d'agrégation, et qu'il est absent de la sélection, on l'ajoute automatiquement.
		//	=> Si un compteur est référencé dans une valeur par défaut, et qu'il est absent de la sélection, on l'ajoute automatiquement.
		foreach ($countersSelected as $idProduct => $listCounters)
		{
			// On se connecte au produit parent
			$dbParent = Database::getConnection($idProduct);
			
                        // 15/12/2010 BBX
                        // Si le produit a été désactivé, on passe la liste des compteurs le concernant
                        // BZ 18510
                        if(!ProductModel::isActive($idProduct))
                            continue;

			// Récupération des compteurs du produit
			$RawKpiModel = new RawModel();
			$countersParent = $RawKpiModel->getAll($dbParent);
			
			// Boucle sur les compteurs sélectionnés
			foreach($listCounters as $idCounter)
			{
				// Champs nécessaires
				$edw_agregation_formula	= $countersParent[$idCounter]['edw_agregation_formula'];
				$default_value		= $countersParent[$idCounter]['default_value'];
				$family			= $countersParent[$idCounter]['family'];
				
				// BOUCLE 1 : formule d'agrégation
				// BOUCLE 2 : valeur par défaut
				foreach( array($edw_agregation_formula,$default_value) as $field )
				{
					if(!empty($field) && ($field != 'NULL'))
					{
						// On découpe le champ sur ses valeurs alphanumériques
						if(preg_match_all('/[a-z0-9_]+|[^a-z0-9_]*/i', $field, $matches))
						{
							// On boucle tous les éléments de la formule / valeur par défaut
							foreach($matches[0] as $m)
							{
								// On extrait un éventuel compteur du champ
								$counterTest 	= strtolower($m);
								$idCounterTest	= null;
								
								// Si le compteur est un compteur capture_duration, on passe
								if(substr_count($counterTest, 'capture_duration') > 0)
									continue;
								
								// On vérifie que ce compteur existe
								$counterExists = false;
								foreach($countersParent as $currentId => $counterValues)
								{
									// On ne compare que les compteurs d'une méme famille
									if($family == $countersParent[$currentId]['family'])
									{
										// On ne se compte pas soi-méme
										if($idCounter != $currentId)
										{
											if($counterTest == strtolower($counterValues['edw_field_name'])) {
												$counterExists = true;
												$idCounterTest = $currentId;
												break;
											}
										}
									}
								}
								
								// Si le compteur n'existe pas, on est sans doute sur un morceau de formule
								// Exemple : CASE, WHEN, etc...
								// Il faut donc passer
								if(!$counterExists)
									continue;
								
								// Si le compteur n'a pas été sélectionné, on l'ajoute automatiquement
								// Afin de se présever des erreurs pendant les compute
								if(!in_array($idCounterTest,$countersSelected[$idProduct]))
								{
									$countersSelected[$idProduct][] = $idCounterTest;
									$addedCounter = true;
								}
							}
						}
					}					
				}
			}
		}


		// On parcours une seconde fois les compteurs sélectionnés,
		// la liste étant désormais compléte
		foreach ($countersSelected as $idProduct => $listCounters)
		{
			// On se connecte au produit parent
			$dbParent = Database::getConnection($idProduct);
			
                        // 15/12/2010 BBX
                        // Si le produit a été désactivé, on passe la liste des compteurs le concernant
                        // BZ 18510
                        if(!ProductModel::isActive($idProduct))
                            continue;

			// On récupére le trigramme
			$trigram = $productsInformations[$idProduct]['sdp_trigram'];
			
			// Récupération des compteurs du produit
			$RawKpiModel = new RawModel();
			$countersParent = $RawKpiModel->getAll($dbParent);
			
			// Boucle sur toutes les familles du produits
			foreach($listCounters as $idCounter)
			{	
				// Construction des informations du compteur
                                // 23/09/2010 OJT : Correction bz17068, on supprime le doublage des simple cote de la formule (systématiquement fait avant l'insert)
                                $counterPrefix = self::getPrefix($idProduct,$countersParent[$idCounter]['family']);
				$nms_field_name = $counterPrefix.$countersParent[$idCounter]['edw_field_name'];
                                
                                // 11/10/2011 BBX
                                // BZ 23358 : gestion des formules composées
                                $formula = $countersParent[$idCounter]['edw_agregation_formula'];
                                foreach($listCounters as $idC) {
                                    // 21/11/2011 BBX
                                    // BZ 24527 : amélioration et dédoublonnage de l'affectation des préfixes dans les formules
                                    //$formula = str_ireplace($countersParent[$idC]['edw_field_name'], $counterPrefix.$countersParent[$idC]['edw_field_name'], $formula);
                                    //$formula = str_ireplace($counterPrefix.$counterPrefix, $counterPrefix, $formula);
                                    // 11/04/2012 BBX
                                    // BZ 26748 : correction du préfixage des compteurs dans les formules
                                    $edwFieldName = $countersParent[$idC]['edw_field_name'];
                                    $replacement = $counterPrefix.$countersParent[$idC]['edw_field_name'];
                                    $formula = preg_replace("#\b$edwFieldName\b#i", $replacement, $formula);
                                    $formula = str_ireplace($counterPrefix.$counterPrefix, $counterPrefix, $formula);
                                }
                                
				$counterMK = Array();
				$counterMK['id_ligne']				= generateUniqId ('sys_field_reference');
				$counterMK['nms_field_name'] 			= $nms_field_name;
				$counterMK['edw_target_field_name']		= strtolower($nms_field_name);
				$counterMK['edw_field_name']			= $nms_field_name;
				$counterMK['edw_field_type']			= $countersParent[$idCounter]['edw_field_type'];
				$counterMK['edw_agregation_function']           = $countersParent[$idCounter]['edw_agregation_function'];
				$counterMK['edw_agregation_formula']            = $formula;
				$counterMK['comment']                           = $countersParent[$idCounter]['comment'];
				$counterMK['default_value']			= $countersParent[$idCounter]['default_value'];
				$counterMK['edw_field_name_label']		= self::getPrefix($idProduct,$countersParent[$idCounter]['family'],true).$countersParent[$idCounter]['edw_field_name_label'];
				$counterMK['sfr_sdp_id']			= $idProduct;
				$counterMK['sfr_product_family']		= $countersParent[$idCounter]['family'];
				$counterMK['old_id_ligne']			= $idCounter;
				$counterMK['edw_group_table']			= $edw_group_table;
				$counterMK['id_group_table']			= $id_group_table;
				$counterMK['new_date']				= $new_date;
				$counterMK['on_off']				= $on_off;
				$counterMK['visible']				= $visible;
				$counterMK['new_field']				= $new_field;
				// Et mémorisation par famille
				$selectedCountersList[$idProduct][$countersParent[$idCounter]['family']][$nms_field_name] = $counterMK;
				
				// 08/04/2010 BBX : les compteurs capture_duration et capture_duration expected doivent être invisibles. BZ 14953
				// Pour chaque famille, on créé les compteurs capture_duration, capture_duration_expected et capture_duration_real
                // 26/01/2012 BBX
                // BZ 24844 : Création des compteurs capture duration * une seule fois apr famille mixed kpi
                if(!$captureDurationCounters)
                {
                    if(!isset($selectedCountersList[$idProduct][$countersParent[$idCounter]['family']]['capture_duration']))
                    {
                        // Construction des informations du compteur
                        $counterMK = Array();
                        $counterMK['id_ligne']			= generateUniqId ('sys_field_reference');
                        $counterMK['nms_field_name'] 		= 'capture_duration';
                        $counterMK['edw_target_field_name']	= 'capture_duration';
                        $counterMK['edw_field_name']		= 'capture_duration';
                        $counterMK['edw_field_type']		= 'float4';
                        $counterMK['edw_agregation_function']	= 'SUM';
                        $counterMK['edw_agregation_formula']	= 'SUM(capture_duration)';
                        $counterMK['comment']			= 'Capture Duration';
                        $counterMK['default_value']		= '0';
                        $counterMK['edw_field_name_label']	= 'capture_duration';
                        $counterMK['edw_group_table']		= $edw_group_table;
                        $counterMK['id_group_table']		= $id_group_table;
                        $counterMK['new_date']			= $new_date;
                        $counterMK['on_off']			= $on_off;
                        $counterMK['visible']			= '0';
                        $counterMK['new_field']			= $new_field;
                        // Et mémorisation par famille
                        $selectedCountersList[$idProduct][$countersParent[$idCounter]['family']]['capture_duration'] = $counterMK;
                    }
                    // capture_duration_expected
                    if(!isset($selectedCountersList[$idProduct][$countersParent[$idCounter]['family']]['capture_duration_expected']))
                    {
                        // Construction des informations du compteur
                        $counterMK = Array();
                        $counterMK['id_ligne']			= generateUniqId ('sys_field_reference');
                        $counterMK['nms_field_name']            = 'capture_duration_expected';
                        $counterMK['edw_target_field_name']	= 'capture_duration_expected';
                        $counterMK['edw_field_name']		= 'capture_duration_expected';
                        $counterMK['edw_field_type']		= 'float4';
                        $counterMK['edw_agregation_function']	= 'SUM';
                        $counterMK['edw_agregation_formula']	= 'SUM(capture_duration_expected)';
                        $counterMK['comment']			= 'Capture Duration Expected';
                        $counterMK['default_value']		= '0';
                        $counterMK['edw_field_name_label']	= 'capture_duration_expected';
                        $counterMK['edw_group_table']		= $edw_group_table;
                        $counterMK['id_group_table']		= $id_group_table;
                        $counterMK['new_date']			= $new_date;
                        $counterMK['on_off']			= $on_off;
                        $counterMK['visible']			= '0';
                        $counterMK['new_field']			= $new_field;
                        // Et mémorisation par famille
                        $selectedCountersList[$idProduct][$countersParent[$idCounter]['family']]['capture_duration_expected'] = $counterMK;
                    }
                    // 24/11/2010 BBX
                    // ajout du compteur capture_duration_real
                    // BZ 18843
                    if(!isset($selectedCountersList[$idProduct][$countersParent[$idCounter]['family']]['capture_duration_real']))
                    {
                        // Construction des informations du compteur
                        $counterMK = Array();
                        $counterMK['id_ligne']			= generateUniqId ('sys_field_reference');
                        $counterMK['nms_field_name']            = 'capture_duration_real';
                        $counterMK['edw_target_field_name']	= 'capture_duration_real';
                        $counterMK['edw_field_name']		= 'capture_duration_real';
                        $counterMK['edw_field_type']		= 'float4';
                        $counterMK['edw_agregation_function']	= 'log';
                        $counterMK['edw_agregation_formula']	= 'CASE WHEN $aggreg_net_ri=0 THEN SUM(capture_duration_real) ELSE MAX(capture_duration_real) END';
                        $counterMK['comment']			= 'Capture Duration Real';
                        $counterMK['default_value']		= '0';
                        $counterMK['edw_field_name_label']	= 'capture_duration_real';
                        $counterMK['edw_group_table']		= $edw_group_table;
                        $counterMK['id_group_table']		= $id_group_table;
                        $counterMK['new_date']			= $new_date;
                        $counterMK['on_off']			= $on_off;
                        $counterMK['visible']			= '1';
                        $counterMK['new_field']			= $new_field;
                        // Et mémorisation par famille
                        $selectedCountersList[$idProduct][$countersParent[$idCounter]['family']]['capture_duration_real'] = $counterMK;
                    }
                    
                    // 26/01/2012 BBX
                    // BZ 24844 : Création des compteurs capture duration * une seule fois apr famille mixed kpi
                    $captureDurationCounters = true;
		        }
            }
		}
		
		// Maintenant que l'on a récupéré et traité nos compteurs, nous allons gérer le tronquage des compteurs trop longs.
		// => Si le nom du compteur dépasse 63 caractéres, la fin du nom du compteur sera supprimée pour obtenir une longueur de 60 caractéres.
		// => Afin dééviter les doublons, les 3 derniers caractéres seront utilisés comme incrément automatique (doublons par famille).
		// Etape 1 : on troque les codes trop longs. Champs visés : edw_field_name et edw_target_field_name
		foreach($selectedCountersList as $idProduct => $countersByFamily)
		{
			foreach($countersByFamily as $family => $counters)
			{
				foreach($counters as $nms_field_name => $counterMK)
				{
					if(strlen($nms_field_name) > 63)
					{
						$newFieldName = substr($nms_field_name,0,60).'001';
						$selectedCountersList[$idProduct][$family][$nms_field_name]['edw_field_name'] 			= $newFieldName;
						$selectedCountersList[$idProduct][$family][$nms_field_name]['edw_target_field_name'] 	= strtolower($newFieldName);
						$selectedCountersList[$idProduct][$family][$nms_field_name]['edw_agregation_formula']	= preg_replace('/'.$nms_field_name.'/i',$newFieldName,$selectedCountersList[$idProduct][$family][$nms_field_name]['edw_agregation_formula']);
					}
				}
			}
		}
		// Etape 2 : on dédoublonne les compteurs par famille
		$nbEncounters = Array();
		foreach($selectedCountersList as $idProduct => $countersByFamily)
		{
			// Pour chaque famille
			foreach($countersByFamily as $family => $counters)
			{
				// On extrait les compteurs
				foreach($counters as $nms_field_name => $counterMK)
				{
					// Et pour tous les compteurs
					foreach($counters as $nms_test => $counterTest)
					{
						// Pour 2 compteurs différents
						if($nms_field_name != $nms_test)
						{
							// On ne traite pas les compteurs capture_duration
							if(($counterMK['edw_field_name'] != 'capture_duration') && ($counterMK['edw_field_name'] != 'capture_duration_expected'))
							{
								// Si un doublons est détecté
								if($counterMK['edw_field_name'] == $counterTest['edw_field_name'])
								{
									// Comptabilisation
									if(isset($nbEncounters[$idProduct][$family][$idProduct]))
										$nbEncounters[$idProduct][$family][$idProduct]++;
									else
										$nbEncounters[$idProduct][$family][$idProduct] = 0;								
								
									// On incrémente le code du compteur
									$increment = substr($selectedCountersList[$idProduct][$family][$nms_field_name]['edw_field_name'],-3);
									$newFieldName = substr($selectedCountersList[$idProduct][$family][$nms_field_name]['edw_field_name'],0,-3).sprintf('%03d',$increment+$nbEncounters[$idProduct][$family][$idProduct]);
									$selectedCountersList[$idProduct][$family][$nms_field_name]['edw_field_name'] 			= $newFieldName;
									$selectedCountersList[$idProduct][$family][$nms_field_name]['edw_target_field_name'] 	= strtolower($newFieldName);
									$selectedCountersList[$idProduct][$family][$nms_field_name]['edw_agregation_formula']	= preg_replace('/'.$nms_field_name.'/i',$newFieldName,$selectedCountersList[$idProduct][$family][$nms_field_name]['edw_agregation_formula']);
								}
							}
						}
					}
				}
			}
		}
		
		// Le tableau des compteurs est prét. Il faut maintenant procéder é l'insertion en base.
		// On va insérer ces compteurs dans la table sys_field_reference_tmp
		// Création d'un tableau colonne => type (0 = numéric, 1 = textuel)
		$sfrTypes = Array();
		$sfrTypes['id_ligne']			= 1;
		$sfrTypes['nms_field_name']             = 1;
		$sfrTypes['edw_target_field_name']      = 1;
		$sfrTypes['edw_field_name']		= 1;
		$sfrTypes['edw_field_type']		= 1;
		$sfrTypes['edw_agregation_function']    = 1;
		$sfrTypes['edw_agregation_formula']     = 1;
		$sfrTypes['comment']			= 1;
		$sfrTypes['default_value']		= 0;
		$sfrTypes['edw_field_name_label']       = 1;
		$sfrTypes['sfr_sdp_id']                 = 0;
		$sfrTypes['sfr_product_family']         = 1;
		$sfrTypes['old_id_ligne']		= 1;
		$sfrTypes['edw_group_table']		= 1;
		$sfrTypes['id_group_table']		= 0;
		$sfrTypes['new_date']			= 0;
		$sfrTypes['on_off']			= 0;
		$sfrTypes['visible']			= 0;
                // 21/11/2011 BBX
                // BZ 24527 : déclaration de la colonne new_field
                $sfrTypes['new_field']			= 0;
		
		// Démarrage de la transaction
		$this->_dbMK->execute('BEGIN');
		
		// Requétes d'insertion
		$queries = '';
		foreach($selectedCountersList as $idProduct => $countersByFamily)
		{
			foreach($countersByFamily as $family => $counters)
			{
				foreach($counters as $nms_field_name => $counterMK)
				{
					// Préparation de la requéte
					$queryFields = "";
					$queryValues = "";
				
					foreach($counterMK as $field => $value)
					{
						// ecriture de la requéte
						$queryFields .= $field.",";
						// maj 05/05/2010 - MPR : Correction du BZ15259 Les simples quotes faisaient planter l'insertion des compteurs dans sys_field_reference_tmp
                                                $queryValues .= ($value == '' ? 'NULL' : ($sfrTypes[$field] ? "E'".str_replace("'","''",$value)."'" : $value)).",";
					}
					
					// Ajout de la requéte é la liste des requétes
					$queryFields = substr($queryFields,0,-1);
					$queryValues = substr($queryValues,0,-1);
					$queries .= "INSERT INTO sys_field_reference_tmp ($queryFields) VALUES ($queryValues);\n";
				}
			}
		}

		// Exécution !
		$execCtl = (!$this->_dbMK->execute($queries) ? false : true);
		// Commit ou rollback
		if($execCtl) $this->_dbMK->execute('COMMIT');
		else $this->_dbMK->execute('ROLLBACK');

		// 14:35 15/12/2009 GHX
		// Prise en compte du paramétre maximum_mapped_counters lors de l'ajout de compteur
		$maximumMappedCounters = get_sys_global_parameters("maximum_mapped_counters", 1000, $this->_idMK);
		$nbCounters = $this->_dbMK->getOne("SELECT count(*) FROM sys_field_reference_tmp");
		if ( $nbCounters > $maximumMappedCounters )
		{
			// Supprime les compteurs en trop
			$queryDeleteCounters = "
			DELETE FROM sys_field_reference_tmp
			WHERE id_ligne IN 
			(
				SELECT id_ligne 
				FROM sys_field_reference_tmp
				WHERE ROW(lower(nms_field_name),edw_group_table) NOT IN 
				(
					SELECT lower(nms_field_name), edw_group_table
					FROM sys_field_reference
					WHERE new_field = 2
					AND edw_group_table = '{$edw_group_table}'
				)
				ORDER BY lower(nms_field_name) DESC
				LIMIT ".($nbCounters-$maximumMappedCounters)."
			)";
			$this->_dbMK->execute($queryDeleteCounters);
			$this->_msgErrors .= (empty($this->_msgErrors) ? '' : '<br>').__T('A_E_SETUP_MIXED_KPI_LIMIT_NB_COUNTER_EXCEEDED', $maximumMappedCounters, ($nbCounters-$maximumMappedCounters));
		}				
			
		// Va compatiliser les mises é jours sur la table sys_field_reference
		$nbAffectedRows = 0;
		
		// Dans le cas ou on a supprimé des anciens compteurs et qu'ils sont toujours dans la table sys_field_reference
		// On les repasse en visible et new_field é 0
		$queryUpdateOldCounters = "
		UPDATE
			sys_field_reference 
		SET
			new_field = 0,
			visible = 1
		WHERE ROW(lower(nms_field_name),edw_group_table) IN 
		(
			SELECT lower(nms_field_name), edw_group_table 
			FROM sys_field_reference_tmp
		)
		AND new_field = 2
		AND edw_group_table = '{$edw_group_table}'";

		$this->_dbMK->execute($queryUpdateOldCounters);
		$nbAffectedRows += $this->_dbMK->getAffectedRows();				
				
		// Insertion des nouveaux compteurs
		$queryInsertNewCounters = "
		INSERT INTO sys_field_reference 
		SELECT * FROM sys_field_reference_tmp 
		WHERE ROW(lower(nms_field_name),edw_group_table) NOT IN 
		(
				SELECT lower(nms_field_name), edw_group_table 
				FROM sys_field_reference
		)";

		$this->_dbMK->execute($queryInsertNewCounters);
		$nbAffectedRows += $this->_dbMK->getAffectedRows();				
				
		// On recherche les compteurs qui ne peuvent pas étre supprimés
		// car ils sont utilisés dans un Kpi, Graphe, une Alarme, un Data Export
		// On récupére la liste des compteurs que l'on veut supprimer
		$querySelectOldCounters = "
		SELECT id_ligne as id
		FROM sys_field_reference 
		WHERE ROW(lower(nms_field_name),edw_group_table) NOT IN (
				SELECT 
					lower(nms_field_name), edw_group_table 
				FROM
					sys_field_reference_tmp
			)
			AND (
				new_field = 0 -- compteurs déjé déployés
				OR
				new_field = 1 -- compteurs qui n'ont pas encore été déployés
				)
			AND edw_group_table = '{$edw_group_table}'";
		$rawsToDelete = $this->_dbMK->GetAll($querySelectOldCounters);	
	
		// tableau des raw utilisés
		$rawUsedList = array();
		// tableau des endroits dans lesquels sont utilisés les raw
		$rawUsedInTable = array();

		$myRaw = new RawModel();
		
		// pour chaque raw que l'on veut supprimer, on vérifie
		foreach($rawsToDelete as $rawToDelete)
		{
			// s'il apparaét dans un kpi, graph, une alarme, un Data Export
			// on mémorise dans quel objet précisément (id)
			$elementList = $myRaw->getKpiListWith($rawToDelete['id'], $edw_group_table, $this->_dbMK);
			if(!empty($elementList))
				$rawUsedInTable[$rawToDelete['id']]['kpi'] = $elementList;
			$elementList = $myRaw->getGraphListWith($rawToDelete['id'],$this->_idMK);
			if(!empty($elementList))
				$rawUsedInTable[$rawToDelete['id']]['graph'] = $elementList;
			$elementList = $myRaw->getAlarmListWith($rawToDelete['id'],$this->_dbMK);
			if(!empty($elementList))
				$rawUsedInTable[$rawToDelete['id']]['alarm'] = $elementList;
			$elementList = $myRaw->getDataExportListWith($rawToDelete['id'],$this->_dbMK);
			if(!empty($elementList))
				$rawUsedInTable[$rawToDelete['id']]['dataexport'] = $elementList;
			if(isset($rawUsedInTable[$rawToDelete['id']])&&!empty($rawUsedInTable[$rawToDelete['id']])){
				// et on maintient une liste des Kpi utilisés
				$rawUsedList[] = $rawToDelete['id'];
			}
		}
	
		// On utilise le liste rawUsedList dans les deux requétes de suppression suivante
		// On veut supprimer des compteurs qui sont déjé déploiés
		// On met juste new field é 2 et visible é 0 : les compteurs seront supprimés automatiquement lors du prochain retrieve
		// sauf ceux utilisés dans une formule de KPI, un graphe, une alarme, un data export
		$queryDeleteOldCounters = "
		UPDATE
			sys_field_reference 
		SET
			new_field = 2,
			visible = 0
		WHERE ROW(lower(nms_field_name),edw_group_table) NOT IN 
		(
			SELECT 
				lower(nms_field_name), edw_group_table 
			FROM 
				sys_field_reference_tmp
		)
		AND new_field = 0
		AND edw_group_table = '{$edw_group_table}'
		AND id_ligne NOT IN ('".implode("','",$rawUsedList)."')";
		$this->_dbMK->execute($queryDeleteOldCounters);
		$nbAffectedRows += $this->_dbMK->getAffectedRows();

		// Suppression des compteurs qui ne sont pas dans la table temporaire
		// et qui n'ont pas encore été déployés
		// sauf ceux utilisés dans une formule de KPI, un graphe, une alarme, un data export
		$queryDeleteOldCounters = "
		DELETE FROM 
			sys_field_reference 
		WHERE ROW(lower(nms_field_name),edw_group_table) NOT IN 
		(
			SELECT 
				lower(nms_field_name), edw_group_table 
			FROM
				sys_field_reference_tmp
		)
		AND new_field = 1
		AND edw_group_table = '{$edw_group_table}'
		AND id_ligne NOT IN ('".implode("','",$rawUsedList)."')";
		$this->_dbMK->execute($queryDeleteOldCounters);
		$nbAffectedRows += $this->_dbMK->getAffectedRows();

		// Suppression de la table temporaire que l'on a créée
		// $this->_dbMK->execute("DROP TABLE IF EXISTS sys_field_reference_tmp");
		
		if ( $addedCounter === true )
		{
			$this->_msgErrors .= (empty($this->_msgErrors) ? '' : '<br>').__T('A_E_SETUP_MIXED_ADD_AUTO_COUNTER');
		}
		// si on a trouvé des raw utilisés dans des Graphes, Alarmes, Data Export, on prépare les messages.
		if ( count($rawUsedList) > 0 )
		{
			foreach ( $rawUsedList as $raw )
			{
				if(!empty($rawUsedInTable[$raw]['kpi'])){
					$this->_msgErrors .= (empty($this->_msgErrors) ? '' : '<br>').$myRaw->getLabelFromId($raw,$this->_dbMK).'. '.__T('A_KPI_BUILDER_CANNOT_DELETE_USED_IN_KPI', count($rawUsedInTable[$raw]['kpi']));
				}
				if(!empty($rawUsedInTable[$raw]['alarm'])){
					$this->_msgErrors .= (empty($this->_msgErrors) ? '' : '<br>').$myRaw->getLabelFromId($raw,$this->_dbMK).'. '.__T('A_KPI_BUILDER_CANNOT_DELETE_USED_IN_ALARM', count($rawUsedInTable[$raw]['alarm']));
				}
				if(!empty($rawUsedInTable[$raw]['graph'])){
					$this->_msgErrors .= (empty($this->_msgErrors) ? '' : '<br>').$myRaw->getLabelFromId($raw,$this->_dbMK).'. '.__T('A_KPI_BUILDER_CANNOT_DELETE_USED_IN_GRAPH', count($rawUsedInTable[$raw]['graph']));
				}
				if(!empty($rawUsedInTable[$raw]['dataexport'])){
					$this->_msgErrors .= (empty($this->_msgErrors) ? '' : '<br>').$myRaw->getLabelFromId($raw,$this->_dbMK).'. '.__T('A_KPI_BUILDER_CANNOT_DELETE_USED_IN_DATA_EXPORT', count($rawUsedInTable[$raw]['dataexport']));
				}
			}
		}		

		// Correction du BZ 13225
		// Lance le script clean_tables_structure.php
		if ( $nbAffectedRows > 0 )
		{
			$prodMK = new ProductModel($this->_idMK);
			$prodMK->launchCleanTablesStructure();
                        
                        // 21/11/2011 BBX
                        // BZ 24527 : correction de la suppression des compteurs
                        $queryDeleteOldCounters = "DELETE FROM sys_field_reference
                        WHERE new_field = 1 AND on_off = 0";
                        $this->_dbMK->execute($queryDeleteOldCounters);
                        $nbAffectedRows += $this->_dbMK->getAffectedRows();
		}
		
		return $nbAffectedRows == 0 ? false : true;
	} // End function updateListCounters
	
	/**
	 * Met é jour la liste des Kpi d'une famille
	 *
	 * @author NSE d'aprés GHX
	 * @param string $idFamily identifiant de la famille Mixed KPI
	 * @param array $kpisSelected liste des Kpis de la famille classés par identifant de produit > identifiant de la famille > liste des kpis
	 */
	public function updateListKpis ( $idFamily, $kpisSelected=array() )
	{
		$addedKpi = false;
		
		// Récupére la liste des compteurs commun en fonction des NA commun de la famille Mixed Kpi trié par produit et par famille
		$availableKpis = $this->getAvailableKpisDependingNaCommonByFamily($idFamily, true, false);

		// On récupére la liste des produits pour avoir leur label
		$productsInformations = getProductInformations();
		
		// Création d'une table temporaire dans laquel on va mettre les compteurs sélectionnées
		$this->_dbMK->execute("DROP TABLE IF EXISTS sys_definition_kpi_tmp");
		$this->_dbMK->execute("CREATE TABLE sys_definition_kpi_tmp (LIKE sys_definition_kpi EXCLUDING CONSTRAINTS)");
		
		// Valeur communes é tous les compteurs et qui ne change pas
		$familyMod = new FamilyModel($idFamily, $this->_idMK);

		$edw_group_table = $familyMod->getEdwGroupTable();
		$id_group_table  = $familyMod->getValue('rank');
		$new_date = date('Ymd');
		$new_field = 1;
		$on_off = 1;
		$visible = 1;
		
		// Boucle sur tous les produits
		foreach ( $kpisSelected as $idProduct => $listKpisByFamilies )
		{
			$sdk_sdp_id = $idProduct;
			
                        // 15/12/2010 BBX
                        // Si le produit a été désactivé, on passe la liste des compteurs le concernant
                        // BZ 18510
                        if(!ProductModel::isActive($idProduct))
                            continue;

			// Boucle sur toutes les familles du produits
			foreach ( $listKpisByFamilies as $idFamilyKpi => $listKpis )
			{
				$sdk_product_family = $idFamilyKpi;
				
				$nbKpi = count($listKpis);
				// Boucle sur tous les compteurs d'une famille
				for ( $cpt = 0; $cpt <$nbKpi; $cpt++ )
				{
					$kpi = $listKpis[$cpt];
					
					// Définition des valeurs de chaque Kpi
					$id_ligne                = generateUniqId ('sys_definition_kpi');
					// 11:57 04/11/2009 GHX
					// On mémorise l'id_ligne
					$old_id_ligne            = $availableKpis[$idProduct][$idFamilyKpi][$kpi]['id_ligne'];
					$kpi_name                = $kpi;
					$kpi_type         	 = $availableKpis[$idProduct][$idFamilyKpi][$kpi]['edw_field_type'];
					$value_type		 = getClientType($_SESSION['id_user']);
					$numerator_denominator	 = $availableKpis[$idProduct][$idFamilyKpi][$kpi]['numerator_denominator'];
					// 09:28 11/12/2009 GHX
					// Echappement des cotes des commentaires
					$comment                 = empty($availableKpis[$idProduct][$idFamilyKpi][$kpi]['comment']) ?  'NULL' : "'".str_replace("'", "''", $availableKpis[$idProduct][$idFamilyKpi][$kpi]['comment'])."'";
					// 18/03/2010 NSE bz 14531 : suppression du suffixage avec le label du produit
					$kpi_label   		 = $availableKpis[$idProduct][$idFamilyKpi][$kpi]['edw_field_name_label'];
					// 11/02/2011 BBX
                                        // Interprétation correcte du pourcentage
                                        // BZ 17515
                                        $pourcentage		 = ($availableKpis[$idProduct][$idFamilyKpi][$kpi]['pourcentage'] == 1) ?  '1' : '0';
					
					// on ne recopie pas la formule
					$kpi_formula = 'NULL';

					// Insertion du Kpi dans la table temporaire
					// 15:53 09/12/2009 GHX
					// Correction du BZ 13255 
					// Ajout des cotes pour le champ kpi_formula
					$queryInsert = "
						INSERT INTO sys_definition_kpi_tmp
						(
							id_ligne,
							edw_group_table,
							kpi_type,
							kpi_formula,
							on_off,
							value_type,
							numerator_denominator,
							new_field,
							new_date,
							kpi_name,
							visible,
							kpi_label,
							pourcentage,
							comment,
							sdk_sdp_id,
							sdk_product_family,
							old_id_ligne
						)
						VALUES
						(
							'{$id_ligne}',
							'{$edw_group_table}',
							'{$kpi_type}',
							'{$kpi_formula}',
							{$on_off},
							'{$value_type}',
							'{$numerator_denominator}',
							{$new_field},
							{$new_date},
							'{$kpi_name}',
							{$visible},
							'{$kpi_label}',
							{$pourcentage},
							{$comment},
							{$sdk_sdp_id},
							'{$sdk_product_family}',
							'{$old_id_ligne}'
						)
					";
					$this->_dbMK->execute($queryInsert);
				}
			}
		}
		
		$nbAffectedRows = 0;
		// Dans le cas où on a remis des anciens kpis supprimés et qu'ils sont toujours dans la table sys_definition_kpi
		// (on veut les remettre alors que leur suppression n'a pas été enterinée par un retrieve)
		// On les repasse en visible et new_field à 0
		// Concerne également les KPI dont le new field n'est pas renseigné
		// 19/03/2010 NSE bz 14531 : ajout de l'id du produit pour différencier les kpi de méme nom car on n'a plus le nom dans le label
		// 28/10/2011 ACS BZ 23897 Impossible to add some KPI in familly
		$queryUpdateOldCounters = "
				UPDATE
					sys_definition_kpi 
				SET
					new_field = 0,
					visible = 1
				WHERE ROW(lower(kpi_name),edw_group_table,sdk_sdp_id) IN (
						SELECT lower(kpi_name), edw_group_table,sdk_sdp_id 
						FROM sys_definition_kpi_tmp
					)
					AND (new_field = 2 or new_field IS NULL)
					AND edw_group_table = '{$edw_group_table}'
			";
		$this->_dbMK->execute($queryUpdateOldCounters);
		$nbAffectedRows += $this->_dbMK->getAffectedRows();
		
		// Insertion des nouveaux kpis
		// 19/03/2010 NSE bz 14531 : ajout de l'id du produit pour différencier les kpi de méme nom car on n'a plus le nom dans le label
		$queryInsertNewCounters = "
				INSERT INTO sys_definition_kpi 
				SELECT * FROM sys_definition_kpi_tmp 
				WHERE ROW(lower(kpi_name),edw_group_table,sdk_sdp_id) NOT IN (  
						SELECT lower(kpi_name), edw_group_table,sdk_sdp_id 
						FROM sys_definition_kpi
					)
			";
		$this->_dbMK->execute($queryInsertNewCounters);
		$nbAffectedRows += $this->_dbMK->getAffectedRows();
	
		// On a supprimé des kpis
		// On recherche les KPI qui ne peuvent pas étre supprimés car ils sont utilisés dans un Graphe, une Alarme, un Data Export
		
		// On récupére la liste des Kpis que l'on veut supprimer
		// 19/03/2010 NSE bz 14531 : ajout de l'id du produit pour différencier les kpi de méme nom car on n'a plus le nom dans le label
		$querySelectOldCounters = "
				SELECT id_ligne as id
				FROM sys_definition_kpi 
				WHERE ROW(lower(kpi_name),edw_group_table,sdk_sdp_id) NOT IN (
						SELECT 
							lower(kpi_name), edw_group_table,sdk_sdp_id 
						FROM 
							sys_definition_kpi_tmp
					)
					AND (new_field = 0 OR new_field = 1)
					AND edw_group_table = '{$edw_group_table}'
					AND sdk_sdp_id IS NOT NULL
			";
		$kpisToDelete = $this->_dbMK->GetAll($querySelectOldCounters);
		
		// tableau des kpi utilisés
		$kpiUsedList = array();
		// tableau des endroits dans lesquels sont utilisés les kpi
		$kpiUsedInTable = array();
		
		$myKpi = new KpiModel();
		
		// pour chaque kpi que l'on veut supprimer, on vérifie
		foreach ( $kpisToDelete as $kpiToDelete ){
			// s'il apparaét dans un graph, une alarme, un Data Export
			// on mémorise dans quel objet précisément (id)
			$elementList = $myKpi->getGraphListWith($kpiToDelete['id'],$this->_idMK);
			if(!empty($elementList))
				$kpiUsedInTable[$kpiToDelete['id']]['graph'] = $elementList;
			$elementList = $myKpi->getAlarmListWith($kpiToDelete['id'],$this->_dbMK);
			if(!empty($elementList))
				$kpiUsedInTable[$kpiToDelete['id']]['alarm'] = $elementList;
			$elementList = $myKpi->getDataExportListWith($kpiToDelete['id'],$this->_dbMK);
			if(!empty($elementList))
				$kpiUsedInTable[$kpiToDelete['id']]['dataexport'] = $elementList;
			if(isset($kpiUsedInTable[$kpiToDelete['id']])&&!empty($kpiUsedInTable[$kpiToDelete['id']])){
				// et on maintient une liste des Kpi utilisés
				$kpiUsedList[] = $kpiToDelete['id'];
			}
		}
		
		// on utilise le liste kpiUsedList dans les deux requétes de suppression suivante
		
		// On a supprimé des kpis mais qui sont déjé déploiés
		// On met juste new field é 2 et visible é 0 : les kpis seront supprimés automatiquement lors du prochain retrieve
		// sauf ceux utilisés dans une formule de KPI, un graphe, une alarme, un data export
		// 19/03/2010 NSE bz 14531 : ajout de l'id du produit pour différencier les kpi de méme nom car on n'a plus le nom dans le label
		$queryDeleteOldCounters = "
				UPDATE
					sys_definition_kpi 
				SET
					new_field = 2,
					visible = 0
				WHERE ROW(lower(kpi_name),edw_group_table,sdk_sdp_id) NOT IN (
						SELECT 
							lower(kpi_name), edw_group_table,sdk_sdp_id 
						FROM 
							sys_definition_kpi_tmp
					)
					AND new_field = 0
					AND edw_group_table = '{$edw_group_table}'
					AND id_ligne NOT IN ('".implode("','",$kpiUsedList)."')
					AND sdk_sdp_id IS NOT NULL
			";
		$this->_dbMK->execute($queryDeleteOldCounters);
		$nbAffectedRows += $this->_dbMK->getAffectedRows();
		
		// Suppression des compteurs qui ne sont pas dans la table temporaire
		// et qui n'ont pas encore été déployé
		// sauf ceux utilisés dans une formule de KPI, un graphe, une alarme, un data export
		// 19/03/2010 NSE bz 14531 : ajout de l'id du produit pour différencier les kpi de méme nom car on n'a plus le nom dans le label
		$queryDeleteOldCounters = "
				DELETE FROM 
					sys_definition_kpi 
				WHERE ROW(lower(kpi_name),edw_group_table,sdk_sdp_id) NOT IN (
						SELECT 
							lower(kpi_name), edw_group_table,sdk_sdp_id 
						FROM
							sys_definition_kpi_tmp
					)
					AND new_field = 1
					AND edw_group_table = '{$edw_group_table}'
					AND id_ligne NOT IN ('".implode("','",$kpiUsedList)."')
			";
		$this->_dbMK->execute($queryDeleteOldCounters);
		$nbAffectedRows += $this->_dbMK->getAffectedRows();
		
		// Suppression de la table temporaire que l'on a créée
		//$this->_dbMK->execute("DROP TABLE IF EXISTS sys_definition_kpi_tmp");
		
		if ( $addedKpi === true )
		{
			$this->_msgErrors .= (empty($this->_msgErrors) ? '' : '<br>').__T('A_E_SETUP_MIXED_ADD_AUTO_KPI');
		}
		// si on a trouvé des Kpi utilisés dans des Graphes, Alarmes, Data Export, on prépare les messages.
		if ( count($kpiUsedList) > 0 )
		{
			foreach ( $kpiUsedList as $kpi )
			{
				if(!empty($kpiUsedInTable[$kpi]['alarm'])){
					$this->_msgErrors .= (empty($this->_msgErrors) ? '' : '<br>').$myKpi->getLabelFromId($kpi,$this->_dbMK).'. '.__T('A_KPI_BUILDER_CANNOT_DELETE_USED_IN_ALARM', count($kpiUsedInTable[$kpi]['alarm']));
				}
				if(!empty($kpiUsedInTable[$kpi]['graph'])){
					$this->_msgErrors .= (empty($this->_msgErrors) ? '' : '<br>').$myKpi->getLabelFromId($kpi,$this->_dbMK).'. '.__T('A_KPI_BUILDER_CANNOT_DELETE_USED_IN_GRAPH', count($kpiUsedInTable[$kpi]['graph']));
				}
				if(!empty($kpiUsedInTable[$kpi]['dataexport'])){
					$this->_msgErrors .= (empty($this->_msgErrors) ? '' : '<br>').$myKpi->getLabelFromId($kpi,$this->_dbMK).'. '.__T('A_KPI_BUILDER_CANNOT_DELETE_USED_IN_DATA_EXPORT', count($kpiUsedInTable[$kpi]['dataexport']));
				}
			}
		}
		
		// Correction du BZ 13225
		// Lance le script clean_tables_structure.php
		if ( $nbAffectedRows > 0 )
		{
			$prodMK = new ProductModel($this->_idMK);
			$prodMK->launchCleanTablesStructure();
		}
		return $nbAffectedRows == 0 ? false : true;
	} // End function updateListKpis
	
	/**
	 * Retourne le message d'erreur 
	 *
	 * @author GHX
	 * @return string
	 */
	public function getErrors ()
	{
		return $this->_msgErrors;
	} // End function getErrorsUpdateListCounters
	
	/**
	 * Lance le déploiement des tables de données
	 *
	 * @author GHX
	 */
	public function launchDeploy ()
	{
		// Mise é jour des tables de déploiement
		$query1 = "
			UPDATE sys_definition_group_table 
			SET
				raw_deploy_status = 1,
				kpi_deploy_status = 1
			";
		$this->_dbMK->execute($query1);
	
		$query2 = "
			UPDATE sys_definition_group_table_network
			SET
				deploy_status = 1
			";
		$this->_dbMK->execute($query2);
		
		$mkModel = new ProductModel($this->_idMK);
		$infoMK = $mkModel->getValues();
		
		// Création de la commande pour lancer le déploiement en fonction du produit
		$cmd = 'php /home/'.$infoMK['sdp_directory'].'/scripts/deploy.php 2&>1';
		exec($cmd, $r);
		
		
		$query1 = "
			UPDATE sys_definition_group_table 
			SET
				raw_deploy_status = 3,
				kpi_deploy_status = 3
			";
		$this->_dbMK->execute($query1);
	
		$query2 = "
			UPDATE sys_definition_group_table_network
			SET
				deploy_status = 1
			";
		$this->_dbMK->execute($query2);
		
		foreach(getFamilyList($this->_idMK) as $family => $familyLabel)
		{
			// Infos sur la famille
			$familyInfos = GetGTInfoFromFamily($family,$this->_idMK);
			// Déploiement de la configuration
			// 17:55 09/12/2009 GHX
			// Correction du BZ 13177
			// Ajout de l'ID du produit dans le constructeur
                        // 19/05/2011 BBX - PARTITIONING -
                        // On peut désormais passer une instance de connexion
			$deploy = new deploy($this->_dbMK, $familyInfos['id_ligne'], $this->_idMK);
			if(count($deploy->types) > 0) $deploy->operate();
			$deploy->display(0);
		}
		
		// Récupére la liste de toutes les tables de données qui ne sont plus utilisées
		$dropTables = $this->_dbMK->getAll("
			SELECT
				tablename
			FROM
				pg_tables
			WHERE	
				schemaname = 'public' 
				AND tablename NOT LIKE 'pg_%' 
				AND tablename LIKE 'edw_".get_sys_global_parameters('module', 'def', $this->_idMK)."_%'
				AND tablename NOT IN (
					SELECT tablename
					FROM 
					(
						SELECT DISTINCT 
							'edw_def_'||family||'_%'||sdgtn.network_agregation ||'%' AS table
						FROM
							sys_definition_group_table AS sdgt,
							sys_definition_group_table_network AS sdgtn
						WHERE
							sdgt.id_ligne = sdgtn.id_group_table
					) AS t1
					WHERE 
						tablename LIKE t1.table
				)
			");
		if ( count($dropTables) )
		{
			foreach ( $dropTables as $table )
			{
				$this->_dbMK->execute("DROP TABLE ".$table['tablename']);
			}
		}
	} // End function launchDeploy
	
	/**
	 * Définit le niveau minimum pour la TA
	 *
	 * @author GHX
	 * @param string $ta hour ou day pour la TA minimum
	 * @return boolean
	 */
	public function setTAMin ( $ta )
	{
		// Requete qui désactive le niveau hour
		$query = " UPDATE sys_definition_time_agregation SET visible = 1 WHERE agregation = 'hour'";
		// Requete qui met é jour le type de compute_mode
		$query2 = "UPDATE sys_global_parameters SET value = 'hourly' WHERE parameters ='compute_mode'";
		
		if( $ta == 'day')
		{
			$query = "UPDATE sys_definition_time_agregation SET visible = 0 WHERE agregation = 'hour'";
			$query2 = "UPDATE sys_global_parameters SET value = 'daily' WHERE parameters ='compute_mode'";
		}
		
		$execCtrl = true;
		$execCtrl = $execCtrl && (!$this->_dbMK->execute($query) ? false : true);
		$execCtrl = $execCtrl && (!$this->_dbMK->execute($query2) ? false : true);
		
		return $execCtrl; 
	} // End function setTAMin
	
	/**
	 * Vérifie la configuration des différents produits hors Mixed KPI concernant le type de compute_mode
	 *
	 *	10/12/2009 GHX
	 *		- Correction du BZ 13183
	 *			-> Il faut se baser sur la TA min du Mixed KPI pour savoir si les compute mode des autres produits sont correctes au lieu de se base sur le compute mode du Mixed KPI
	 *
	 * @author GHX
	 * @return string
	 */
	public function checkConfigTAInAllProducts ()
	{		
		// Récupére la TA min du produit Mixed KPI
		list($taMinMK) = array_keys(getTaList('',  $this->_idMK));

		$computeMode = array();
		// Boucle sur tous les produits hors Mixed KPI
		foreach ( ProductModel::getActiveProducts() as $product )
		{
			// Si c'est le Mixed KPI on passe au produit suivant
			if ( $product['sdp_id'] == $this->_idMK )
				continue;
			// Récupére le type de compute_mode du produit
			$mode = get_sys_global_parameters('compute_mode', 'hourly', $product['sdp_id']);
			$modeSwitch = get_sys_global_parameters('compute_switch', '', $product['sdp_id']);
			if ( empty($modeSwitch) )
				$computeMode[$product['sdp_label']] = $mode;
			else
				$computeMode[$product['sdp_label']] = $modeSwitch;
		}
		// Recherche la liste des produits dans le méme compute mode que le produit Mixed KPI
		$search = array_keys($computeMode, 'hourly');
		
		$message = '';
		// Si on a pas le méme nombre d'éléments dans les 2 tableaux c'est qu'on a au moins un des produits qui n'est pas dans le
		// mode de compute que le Mixed KPI
		// Si on est en mode daily, les produits peuvent en mode hourly mais si on est en mode hourly
		// tous les produits doivent étre aussi en mode hourly. Sinon les data exports ne seront pas générés
		if ( count($search) != count($computeMode) && $taMinMK == 'hour' )
		{
			$message .= __T('A_SETUP_MIXED_INFO_COMPTUTE_MODE', $taMinMK);
			$message .= '<br />';
			$message .= __T('A_E_SETUP_MIXED_ALL_PRODUCTS_NOT_SAME_COMPTUTE_MODE');
			$message .= '<ul style="margin-top:0">';
			foreach ( $computeMode as $p => $mode)
			{
				if ( $mode == 'daily' )
					$message .= '<li>'.$p.' : '.$mode.'</li>';
			}
			$message .= '</ul>';
		}
		
		return $message;
	} // End function checkConfigTAInAllProducts
	
	/**
	 * Retourne TRUE si c'est un produit Mixed KPI sinon false
	 *
	 * @author GHX
	 * @return boolean
	 */		
	public static function isMixedKpi ( $idProduct='')
	{
		// Instanciation de DatabaseConnection
		$db = Database::getConnection( $idProduct );
		
		// Requéte qui regarde si la table corporate existe
		$query = "SELECT * FROM pg_tables
		WHERE schemaname = 'public'
		AND tablename = 'sys_definition_mixedkpi'";
		$db->execute($query);
		
		// Retourne le résultat
		return ($db->getNumRows() == 1 ? true : false);
	} // End function isMixedKpi
	
	/**
	 * Retourne la liste des identifiants des dashboards déjé dupliquer
	 * Normalement, on n'a pas besoin de préciser l'ID du produit car les dashboards du mixed Kpi sont toujours que sur le master
	 *
	 * @author GHX
	 * @param int $idProduct : identifiant du produit sur lequel on doit récupérer la liste des ID (default master product)
	 * @return array
	 */
	public function getListDashboardAlreadyDuplicate ( $idProduct = '' )
	{
		$listIds = array();
		$prefix = $this->getPrefixIdDashboard();
		
		$db = Database::getConnection( $idProduct );
		$results = $db->execute("SELECT replace(id_page, '".$prefix."','' ) AS id FROM sys_pauto_page_name WHERE id_page ~ '^".$prefix.".*'");
		if ( $db->getNumRows() > 0 )
		{
			while ( $row = $db->getQueryResults($results, 1) )
			{
				$listIds[] = $row['id'];
			}
		}
		
		return $listIds;
	} // End function getListDashboardAlreadyDuplicate
	
	/**
	 * Retourne le prefix des identifiants des dashboards
	 *
	 * @author GHX
	 * @return string
	 */
	public function getPrefixIdDashboard ()
	{
		$mkMod = new ProductModel($this->_idMK);
		$infoMK = $mkMod->getValues();
		
		$prefix = '';
		foreach ( explode ('_', $infoMK['sdp_db_name']) as $t )
		{
			$prefix .= $t[0];		
		}
		return $prefix.'.';
	} // End function getPrefixIdDashboard
	
	/**
	 * Retourne le prefix d'un compteur
	 *
	 * @author BBX
	 * @param int : id produit
	 * @param string : id du compteur
	 * @param array : tableau des compteurs (RawModel::getAll)
	 * @return string
	 */	
	public static function getPrefix($idProd,$family,$label = false)
	{
		// Connexion é la base de données du Master
		$dbMaster = Database::getConnection(0);
		
		// Récupération du trigramme
		$query = "SELECT sdp_trigram FROM sys_definition_product WHERE sdp_id = {$idProd}";
		$trigram = $dbMaster->getOne($query);
		
		// Retour du prefixe
		$prefix = $label ? $trigram.' '.$family.' ' : $trigram.'_'.$family.'_';
		return $prefix;
	}
	
        /**
         * Met à jour la version produit d'un Mixed KPI
         * 02/11/2010 BBX
         * BZ 18928
         */
        public static function updateProductVersion()
        {
            // Id Mixed KPI
            $mkProductId = ProductModel::getIdMixedKpi();

            // Test de l'id
            if(empty($mkProductId))
                return false;

            // Connexion à la base Mixed KPI
            $dbMk = DataBase::getConnection($mkProductId);

            // Récupération de l'id produit courant
            $query = "SELECT item_value
                FROM sys_versioning
                WHERE item = 'cb_version'
                ORDER BY date DESC, id DESC
                LIMIT 1";
            $productId = $dbMk->getOne($query);

            // Si l'id produit existe, on le met à jour
            if(!empty($productId))
            {
                $query = "UPDATE sys_global_parameters
                    SET value = '$productId'
                    WHERE parameters = 'product_version'
                    AND VALUE != '$productId'";
                $dbMk->execute($query);
            }
        }

         /**
         * Supprime les compteurs dans sys_field_reference_all
         * apparetenant à un produit particulier, selon le trigramme
         * @param text $trigram
         */
        public static function dropSysFieldReferenceAllCountersFromTrigram($trigram)
        {
            // Connexion à la base de données du Mixed KPI
            $dbMk = Database::getConnection(ProductModel::getIdMixedKpi());

            // Suppression
            $query = "DELETE FROM sys_field_reference_all
                WHERE nms_field_name LIKE '{$trigram}_%'";
            $dbMk->execute($query);
        }

        /**
         * Transforme les Kpis issus d'un produit en Kpis standards
         * @param integer $productId
         */
        public static function setKpisAsStandard($productId)
        {
            // Connexion à la base de données du Mixed KPI
            $dbMk = Database::getConnection(ProductModel::getIdMixedKpi());

            // Suppression de l'id produit
            $query = "UPDATE sys_definition_kpi
                SET sdk_sdp_id = NULL
                WHERE sdk_sdp_id = $productId";
            $dbMk->execute($query);
        }

        /**
         * Supprime la connexion liée à un produit source
         * 11/10/2011 BBX : ajouté pour la correction du BZ 20433
         * @param type $productId 
         */
        public static function dropProductConnection($productId)
        {
            // Connexion à la base de données du Mixed KPI
            $dbMk = Database::getConnection(ProductModel::getIdMixedKpi());
            
            // Récupération de l'id connection
            $query = "SELECT id_connection
            FROM sys_definition_connection c, sys_definition_product p
            WHERE c.connection_directory = '/home/' || p.sdp_directory || '/upload/export_files_mixed_kpi'
            AND p.sdp_id = $productId
            AND ((c.connection_ip_address = p.sdp_ip_address AND connection_type != 'local')
            OR (c.connection_ip_address IS NULL AND connection_type = 'local'))";
            $idC = $dbMk->getOne($query);
            
            // Suppression de la connexion à ce produit
            if($idC != '') {
                $connectionModel = new ConnectionModel($idC, ProductModel::getIdMixedKpi());
                $connectionModel->drop();
            }
        }
	
} // End class MixedKpiModel
?>