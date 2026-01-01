<?php
/*
	29/01/2009 GHX
		- modification des requetes SQL pour mettre la valeur id_user entre cote [REFONTE PARSER]
	18/03/2009 GHX
		- modification de la requete d'insertion pour mettre sds_id_page entre cote [REFONTE PARSER]
	05/03/2010 BBX
		- Ajout de la méthode "manageConnections"
		- Utilisation de la méthode "manageConnections" au lieu de DatabaseConnection afin d'éviter les instances redondantes
	08/03/2010 BBX
		- Suppression de la méthode "manageConnections"
		- Utilisation de la méthode "Database::getConnection" à la place.
   06/06/2011 MMT
  		- DE 3rd Axis prise en compte de la nouvelle colonne sds_na_axe3_list de sys_definition_selecteur
*/
?>
<?php
/**
*	Classe permettant de manipuler un sélecteur
*	Travaille sur la table sys_definition_selecteur
*
*	@author	BBX - 26/11/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*
*
*/
class SelecteurModel
{
	/**
	* Propriétés
	*/
	private $idSelecteur = 0;	
	private $database = null;
	private $selecteurValues = Array();
	private $error = false;
	
	// Mémorise les instances de connexions ouvertes
	private static $connections = Array();

	/************************************************************************
	* Constructeur
	* @param : int	id selecteur
	************************************************************************/
	public function __construct($idSelecteur)
	{
		// Sauvegarde de l'id dashbord
		$this->idSelecteur = $idSelecteur;
		// Connexion à la base de données
		$this->database = Database::getConnection(0);
		// Récupération des valeurs par défaut du dashboard
		if (is_numeric($idSelecteur)) {
			$query = "SELECT * FROM sys_definition_selecteur
			WHERE sds_id_selecteur = {$this->idSelecteur}";
			$array = $this->database->getRow($query);
			// Si les infos dashboards ne sont pas récupérées, on renvoie une erreur
                        // 29/11/2011 BBX
                        // BZ 24886 : correction du test sur $array
			if (count($array) == 0 || !is_array($array)) {
				$this->error = true;
			}
			else {
                // 19/05/2011 OJT : Gestion des colonnes pour la Fixed Hour
				$this->selecteurValues = Array(
					'id_page'         => $array['sds_id_page'],
					'mode'            => $array['sds_mode'],
					'ta_level'        => $array['sds_ta'],
					'period'          => $array['sds_period'],
					'na_level'        => $array['sds_na'],
					'nel_selecteur'   => $array['sds_na_list'],
					'axe3'            => $array['sds_na_axe3'],
					'axe3_2'          => $array['sds_na_axe3_list'],
					'top'             => $array['sds_top'],
					'sort_by'         => $this->formatSortByValue( $array['sds_sort_by'] ),
					'order'           => $array['sds_order'],
					'filter_id'       => $array['sds_filter_id'],
					'filter_operande' => $array['sds_filter_operande'],
					'filter_value'    => $array['sds_filter_value'],
                    'fh_mode'         => $array['sds_fh_on_off'],
                    'fh_na'           => $array['sds_fh_na'],
                    'fh_ne'           => $array['sds_fh_ne'],
                    'fh_na_axe3'      => $array['sds_fh_na_axe3'],
                    'fh_ne_axe3'      => $array['sds_fh_ne_axe3'],
                    'fh_family_bh'    => $array['sds_fh_family_bh'],
                    'fh_product_bh'   => $array['sds_fh_product_bh']
				);

                // Cast du boolean en boolean PHP
                if( $this->selecteurValues['fh_mode'] == 't' )
                {
                    $this->selecteurValues['fh_mode'] = true;
                }
                else
                {
                    $this->selecteurValues['fh_mode'] = false;
                }
			}
		}
		else
        {
			// Si le format de l'id est incorrect, on renvoie une erreur
			$this->error = true;
		}
	}
	
	/************************************************************************
	* Méthode getValues : retourne un tableau associatif contenant les paramètres du sélecteur
	* @return : array	Tableau associatif 
	************************************************************************/
	public function getValues()
	{
		return $this->selecteurValues;
	}
	
	/************************************************************************
	* Méthode getValue : retourne une valeur du tableau associatif contenant les paramètres du sélecteur
	* @param : string	nom de la variable voulue
	* @return : string	valeur
	************************************************************************/
	public function getValue($var)
	{
		return $this->selecteurValues[$var];
	}
	
	/************************************************************************
	* Méthode setValue : ajoute une valeur à l'objet
	* @return : void()
	************************************************************************/
	public function setValue($key,$value)
	{
		$this->selecteurValues[$key] = $value;
	}
	
        /**
         * Vérifier le format du SortBy et retourne une chaîne correcte. Si la
         * chaîne fournie n'est pas conforme, 'none' sera retourné
         *
         * @param  string $sortBy formatter
         * @return string
         */
        protected function formatSortByValue( $sortBy )
        {
            // Test si le SortBy est de la forme "kpi@kpis.0004.01.00104@1" ou vaut none
            if( ( preg_match( '/^.+@.+@[0-9]+$/', $sortBy ) > 0 ) || ( $sortBy === 'none' ) )
            {
                return $sortBy;
            }
            return 'none';
        }

	/**
	 * Enregistre un sélecteur en base de données
	*
	*	18/03/2009 GHX
	*		- modification de la requete d'insertion pour mettre sds_id_page entre cote
	*
	* @return : void	
	 */
	public function addSelecteur()
	{
		// Création de valeurs par défaut pour les valeurs nulles
        if ( $this->selecteurValues['id_page'] == '' )      $this->selecteurValues['id_page']       = 0;
        if ( $this->selecteurValues['period'] == '' )       $this->selecteurValues['period']        = 'NULL';
        if ( $this->selecteurValues['top'] == '' )          $this->selecteurValues['top']           = 'NULL';
        if ( $this->selecteurValues['filter_value'] == '' ) $this->selecteurValues['filter_value']  = 'NULL';

        $fhFamily  = '';
        $fhProduct = '';
        $fhKpi = explode( '||', $this->selecteurValues['fh_kpi'] );
        if( count( $fhKpi ) === 2 )
        {
            $fhProduct = $fhKpi[0];
            $fhFamily  = $fhKpi[1];
        }

        // Test de l'éligibilité du Fixed Hour mode
        if( strtolower( $this->selecteurValues['mode'] ) != 'one' || strtolower( $this->selecteurValues['ta_level'] ) != 'hour' )
        {
            $this->selecteurValues['fh_mode'] = 'f';
        }

		// Définition de l'id du nouveau sélecteur
		$query = "SELECT sds_id_selecteur FROM sys_definition_selecteur ORDER BY sds_id_selecteur DESC LIMIT 1";
        $newIdSelecteur = intval( $this->database->getone( $query ) );
		$newIdSelecteur++;
		// Génération de la requête d'enregistrement
		// 18/03/2009 GHX : La valeur de sds_id_page est mise entre cote ce n'est plus un INT mais un TEXT
        // 19/05/2011 OJT : Gestion des colonnes pour la Fixed Hour
		// 06/06/2011 MMT DE 3rd Axis nouvelle colonne sds_na_axe3_list
		$query = "INSERT INTO sys_definition_selecteur
        (   sds_id_selecteur,
		  sds_id_page,
		  sds_mode,
		  sds_ta,
		  sds_period,
		  sds_na,
		  sds_na_list,
		  sds_na_axe3,
		  sds_na_axe3_list,
		  sds_top,
		  sds_sort_by,
		  sds_order,
		  sds_filter_id,
		  sds_filter_operande,
          sds_filter_value,
          sds_fh_on_off,
          sds_fh_na,
          sds_fh_ne,
          sds_fh_na_axe3,
          sds_fh_ne_axe3,
          sds_fh_family_bh,
          sds_fh_product_bh
        )
        VALUES
        ($newIdSelecteur,
		'{$this->selecteurValues['id_page']}',
		'{$this->selecteurValues['mode']}',
		'{$this->selecteurValues['ta_level']}',
		{$this->selecteurValues['period']},
		'{$this->selecteurValues['na_level']}',
		'{$this->selecteurValues['nel_selecteur']}',
		'{$this->selecteurValues['axe3']}',
		'{$this->selecteurValues['axe3_2']}',
		{$this->selecteurValues['top']},
		'{$this->selecteurValues['sort_by']}',
		'{$this->selecteurValues['order']}',
		'{$this->selecteurValues['filter_id']}',
		'{$this->selecteurValues['filter_operande']}',
        {$this->selecteurValues['filter_value']},
        '{$this->selecteurValues['fh_mode']}',
        '{$this->selecteurValues['fh_na']}',
        '{$this->selecteurValues['fh_ne']}',
        '{$this->selecteurValues['fh_na_axe3']}',
        '{$this->selecteurValues['fh_ne_axe3']}',
        '{$fhFamily}',
        '{$fhProduct}'
        )";
		// Exécution de la requête
		$this->database->execute($query);
		// On place le nouvel id dans l'objet
		$this->idSelecteur = $newIdSelecteur;
	}
	
	/**
	 * Met à jour un sélecteur en base de données
     *
	 * @return void
	 */
	public function updateSelecteur()
	{
		// Création de valeurs par défaut pour les valeurs nulles
        if ( $this->selecteurValues['id_page'] == '')      $this->selecteurValues['id_page']      = 0;
        if ( $this->selecteurValues['period'] == '')       $this->selecteurValues['period']       = 'NULL';
        if ( $this->selecteurValues['top'] == '')          $this->selecteurValues['top']          = 'NULL';
        if ( $this->selecteurValues['filter_value']	== '') $this->selecteurValues['filter_value'] = 'NULL';

        $fhKpi = explode( '||', $this->selecteurValues['fh_kpi'] );
        if( count( $fhKpi ) === 2 )
        {
            $this->selecteurValues['fh_family_bh']  = $fhKpi[1];
            $this->selecteurValues['fh_product_bh'] = $fhKpi[0];
        }
        unset( $this->selecteurValues['fh_kpi'] );

        // Test de l'éligibilité du Fixed Hour mode
        if( strtolower( $this->selecteurValues['mode'] ) != 'one' || strtolower( $this->selecteurValues['ta_level'] ) != 'hour' )
        {
            $this->selecteurValues['fh_mode'] = 'f';
        }

		// Définition des champs à mettre à jour
		// 06/06/2011 MMT DE 3rd Axis nouvelle colonne sds_na_axe3_list
        // 19/05/2011 OJT : Gestion des colonnes pour la Fixed Hour
		$array_fields_to_update = Array(
			'id_page'         => 'sds_id_page',
			'mode'            => 'sds_mode',
			'ta_level'        => 'sds_ta',
			'period'          => 'sds_period',
			'na_level'        => 'sds_na',
			'nel_selecteur'   => 'sds_na_list',
			'axe3'            => 'sds_na_axe3',
			'axe3_2'          => 'sds_na_axe3_list',
			'top'             => 'sds_top',
			'sort_by'         => 'sds_sort_by',
			'order'           => 'sds_order',
			'filter_id'       => 'sds_filter_id',
			'filter_operande' => 'sds_filter_operande',
            'filter_value'    => 'sds_filter_value',
            'fh_mode'         => 'sds_fh_on_off',
            'fh_na'           => 'sds_fh_na',
            'fh_ne'           => 'sds_fh_ne',
            'fh_na_axe3'      => 'sds_fh_na_axe3',
            'fh_ne_axe3'      => 'sds_fh_ne_axe3',
            'fh_family_bh'    => 'sds_fh_family_bh',
            'fh_product_bh'   => 'sds_fh_product_bh',
		);

		// Mise à jour des valeurs
        foreach ( $this->selecteurValues as $key=>$value )
        {
            if ( array_key_exists( $key, $array_fields_to_update ) )
            {
				$value = (($value == 'NULL') || (is_numeric($value))) ? $value : "'{$value}'";
				$query = "UPDATE sys_definition_selecteur SET {$array_fields_to_update[$key]} = {$value} WHERE sds_id_selecteur = {$this->idSelecteur}";
                $this->database->execute( $query );
			}
		}		
	}
	
	/************************************************************************
	* Méthode deleteSelecteur : supprime un sélecteur
	* @return : void	
	************************************************************************/
	public function deleteSelecteur()
	{
		$query = "DELETE FROM sys_definition_selecteur WHERE sds_id_selecteur = {$this->idSelecteur}";
		$this->database->execute($query);
	}

	/************************************************************************
	* Méthode setAsUserHomepage : définit ce sélecteur comme homepage d'un utilisateur
	* @param : int	id user
	* @return : void	
	************************************************************************/	
	public function setAsUserHomepage($idUser)
	{
		// 29/01/2009 GHX
		// modification de la requete SQL pour mettre la valeur id_user entre cote
		$query = "UPDATE users SET homepage = {$this->idSelecteur} WHERE id_user = '{$idUser}'";
		$this->database->execute($query);
		// On propage la modification sur tous les produits
		UserModel::deployUsers();
	}
	
	/************************************************************************
	* Méthode setAsReportFilter : définit ce sélecteur pour un rapport
	* @param : int	id report
	* @return : void	
	************************************************************************/	
	public function setAsReportFilter($idReport)
	{
		$query = "UPDATE sys_definition_selecteur SET sds_report_id = '{$idReport}' WHERE sds_id_selecteur = {$this->idSelecteur}";
		$this->database->execute($query);	
	}

	/************************************************************************
	* Méthode getError : retourne le code d'erreur du sélecteur
	* @return : true = pas d'erreur, false = objet inutilisable
	************************************************************************/
	public function getError() 
	{
		return $this->error;
	}
	
	/************************* STATIC FUNCTIONS **************************/
	
	/************************************************************************
	* Méthode getUserHomepage : récupère l'id sélecteur de la homepage d'un user
	* @param : int	id user
	* @return : int	id selecteur ou 0	
	************************************************************************/	
	public static function getUserHomepage($idUser)
	{
		// Connexion à la base de données
		$database = Database::getConnection(0);
		// Requête
		// 29/01/2009 GHX
		// modification de la requete SQL pour mettre la valeur id_user entre cote
		$query = "SELECT homepage FROM users WHERE id_user = '{$idUser}'";
		// Résultat
		$result = $database->getRow($query);
		return ($result['homepage'] != '') ? $result['homepage'] : 0;
	}
	
	/************************************************************************
	* Méthode getSelecteurId : récupère l'id du sélecteur à partir de l'id d'un rapport et de l'id d'un de ses dashboards
	* @param : int	id report
	* @param : int	id dashboard
	* @return : int	id selecteur ou 0	
	************************************************************************/	
	public static function getSelecteurId($idReport,$idDashboard)
	{
		// Connexion à la base de données
		$database = Database::getConnection(0);
		// Requête
		$query = "SELECT sds_id_selecteur FROM sys_definition_selecteur WHERE sds_report_id = '{$idReport}' AND sds_id_page='{$idDashboard}' ";
		// Résultat
		$result = $database->getone($query);
		if ($result) return $result;
		
		return 0;
	}
}
