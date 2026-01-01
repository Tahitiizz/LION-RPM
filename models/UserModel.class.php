<?php
/**
 * CB 5.3.1
 * 
 * 15/04/2013 NSE Phone number managed by Portal
 * 
 */
?><?php
/**
 * 
 *  CB 5.2
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 * 
 * 06/03/2012 NSE bz 26214 : on supprime de la liste locale les utilisateurs qui n'ont plus de droits pour l'appli sur le Portail
 */
?><?php
/*
	16/01/2009 GHX
		- ajout du paramètre $deploy à la fonction addUser()
	29/01/2009 GHX
		- modification de la création du id_user. Ce n'est plus un MAX+1 mais un unique ID  [REFONTE CONTEXTE]
	30/01/2009 GHX
		- modification des requetes SQL pour mettre les valeurs entre cote [REFONTE CONTEXTE)
	02/02/2009 GHX
		- Appel à la fonction qui génére un unique ID generateUniqId() [REFONTE CONTEXTE]
	25/03/2009 GHX
		- ajout du paramètre $deploy à la fonction updateUser()


	25/05/2009 BBX
		- ajout de la méthode "getUsersNotCheckedForAConnection" qui récupère les users non abonnés aux alertes d'une connexion
		- ajout de la méthode "getUsersCheckedForAConnection" qui récupère les users abonnés aux alertes d'une connexion

	05/03/2010 BBX
		- Ajout de la méthode "manageConnections"
		- Utilisation de la méthode "manageConnections" au lieu de DatabaseConnection afin d'éviter les instances redondantes
	08/03/2010 BBX
		- Suppression de la méthode "manageConnections"
		- Utilisation de la méthode "Database::getConnection" à la place.
	27/04/2010 NSE
		- Ajout de la méthode "getUserProfileType"
	11/05/2010 BBX
		- Correction de la méthode getUsersNotCheckedForAConnection :
			Il faut utiliser l'id produit pour éxécuter la requête. BZ 15414
    04/08/2010 - MPR : Correction du BZ 15045
                Le paramètre id_menu n'existe pas dans la méthode getProfileType()
 *  16/12/2010 NSE bz 19745 : ajout d'un paramètre optionnel (id_product) au constructeur pour requéter sur le bon produit (pas forcément le produit courant)
 *  17/01/2013 GFS - BZ#31387 : [QAL][T&A Gateway][Remove Product] : Product not accesible after being removed from gateway
 *  17/02/2015 JLG - BZ#44341 : Use pg_escape_string on user db insertion
 */
?>
<?php
/**
*	Classe permettant de manipuler les users
*	Travaille sur la table users, profile, sys_user_group, sys_report_sendmail
*
*	@author	BBX - 27/11/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*
*
*/
// 23/02/2012 NSE DE Astellia Portal Lot2
include_once dirname(__FILE__)."/../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0 . '/api/paa/PAAAuthenticationService.php');

class UserModel
{
	/**
	* Propriétés
	*/
	private $idUser = 0;
	private static $database = null;
	private $userValues = Array();
	private $error = false;

	// Mémorise les instances de connexions ouvertes
	private static $connections = Array();

	/**
	* Constructeur
	* @param : int	id user
         * 
         * 16/12/2010 NSE bz 19745 : on ajoute la connexion à la bd pour pouvoir exécuter ce code à partir d'un slave
	*/
	public function __construct($idUser, $id_prod = "")
	{
		// Sauvegarde de l'id user
		$this->idUser = $idUser;
		// Connexion à la base de données
                // 16/12/2010 NSE bz 19745 : si on passe un id_produit, on récupère la connexion existante à la bd correspondante
                if( !empty($id_prod) ){
                    self::$database = Database::getConnection($id_prod);
                }
		if ( empty(self::$database) )
			self::$database = Database::getConnection(0);
		// Récupération des valeurs du user
		// 29/01/2009 GHX
		// modification de la condition
		if( is_numeric($idUser) || is_string($idUser) ) {
			$query = "SELECT * FROM users WHERE id_user = '{$idUser}'";
			$array = self::$database->getRow($query);
			// Si les infos user ne sont pas récupérées, on renvoie une erreur
			if(count($array) == 0) {
				$this->error = true;
			}
			else {
				$this->userValues = $array;
                                // 23/02/2012 NSE DE Astellia Portal Lot2
                                // si on utilise l'utilisateur courant, alors, on initialise la variable avec la valeur en session
                                if($this->idUser == $_SESSION['id_user'])
                                    $this->userValues['user_profil'] = $_SESSION['user_profil'];
                                // sinon, on ne l'initialise pas et elle ne doit pas être utilisée.
			}
		}
		else {
			// Si le format de l'id est incorrect, on renvoie une erreur
			$this->error = true;
		}
	}

	/************************************************************************
	* Méthode getValues : retourne un tableau associatif contenant les paramètres du user
	* @return : array	Tableau associatif
	************************************************************************/
	public function getValues()
	{
		return $this->userValues;
	}

	/************************************************************************
	* Méthode setValue : ajoute une valeur à l'objet
	* @return : void()
	************************************************************************/
	public function setValue($key,$value)
	{
		$this->userValues[$key] = $value;
	}

	/************************************************************************
	* Méthode addUser : ajoute un utilisateur :
	* Sauvegarde les informations contenues dans l'objet
	* Répercute les modifications sur tous les produits
	*
	* 	16/01/2009 GHX
	*		- ajout du paramètre $deploy
	*
	* @param boolean $deploy spécifie si on doit déploier le nouveau utilisateur sur tous les produits (defaut : true
	* @return : void()
	************************************************************************/
	public function addUser( $deploy = true)
	{
            // 23/02/2012 NSE DE Astellia Portal Lot2
            // suppression des paramètres qui ne sont plus gérés par T&A
		// Encodage des valeurs critiques
		$this->userValues['username'] = pg_escape_string(htmlentities($this->userValues['username']));
		$this->userValues['login'] = htmlentities($this->userValues['login']);
		$this->userValues['user_mail'] = htmlentities($this->userValues['user_mail']);
                $this->userValues['phone_number'] = htmlentities($this->userValues['phone_number']);
                // conservé pour compatibilité sur les slaves
                $this->userValues['date_creation'] = date('Ymd');
                $this->userValues['date_valid'] = date('Ymd',strtotime('+30 year'));
                
                // 16/03/2012 NSE bz 26404 : si visible n'est pas initialisé, on met 1
                if(!isset($this->userValues['visible']) || empty($this->userValues['visible']) && $this->userValues['visible']!=0)
                        $this->userValues['visible'] = 1;
                
                // 26/07/2012 BBX
                // BZ 27149 : gestion de la homepage
                $this->userValues['homepage'] = "NULL";
                if( file_exists(REP_PHYSIQUE_NIVEAU_0 . '/homepage/config.xml') )
                        $this->userValues['homepage'] = "'-1'";
                
		// Calcul du nouvel id
		// 29/01/2009 GHX
		// Dans la refonte du contexte, l'identifiant d'un utilisateur est unique. On ne fait plus MAX+1
		// 14:23 02/02/2009 GHX
		// Appel à la fonction qui génére un unique ID
		$newUserId = generateUniqId('users');

		// Requête de création d'un nouveau user
		$query = "INSERT INTO users (id_user,username,login,password,user_prenom,user_mail,user_profil,on_off,date_valid,phone_number,date_creation,visible,homepage)
		VALUES (
			'{$newUserId}',
			'{$this->userValues['username']}',
			'{$this->userValues['login']}',
			'',
			'',
			'{$this->userValues['user_mail']}',
			'',
			1,
			{$this->userValues['date_valid']},
            '{$this->userValues['phone_number']}',
			{$this->userValues['date_creation']},
			{$this->userValues['visible']},
            {$this->userValues['homepage']});";
		// Exécution de la requête
                self::$database->execute($query);
		// Récupération de l'id du user dans l'objet
		$this->idUser = $newUserId;

		// 16/01/2009 GHX
		// Ajout de la condition en fonction du paramètre de la fonction
		// Déploiement de la table sur les autres produits
		if ( $deploy )
			UserModel::deployUsers();
	}

	/************************************************************************
	* Méthode updateUser : met à jour un utilisateur :
	* Sauvegarde les informations contenues dans l'objet
	* Répercute les modifications sur tous les produits
	*
	* 	25/03/2009 GHX
	*		- ajout du paramètre $deploy
	*
	* @return : void()
	************************************************************************/
	public function updateUser( $deploy = true )
	{
            // 10/02/2012 NSE DE Astellia Portal Lot2
            // Suppression de la Gestion des dates, du mot de passe
            // conservée pour compatibilité sur les slaves
            $this->userValues['date_valid'] = date('Ymd',strtotime('+30 year'));
            
            // Parcours des valeurs
            foreach($this->userValues as $key => $value)
            {
                // Mise à jour de l'information (DE SMS, force phone_number en string)
                if( $value == '' )
                {
                    $value = "NULL";
                }
                else if( ( !is_numeric( $value ) || ( $key == 'phone_number' ) ) )
                {
                    $value = "'{$value}'";
                }
                $query = "UPDATE users SET {$key} = {$value} WHERE id_user = '{$this->idUser}'";
                self::$database->execute($query);
            }

            // 25/03/2009 GHX
            // Ajout de la condition en fonction du paramètre de la fonction
            // Déploiement de la table sur les autres produits
            if ( $deploy )
                    UserModel::deployUsers();
	}

	/************************************************************************
	* Méthode deleteUser : supprime un utilisateur :
	* En fonction de l'id user de l'objet
	* Répercute les modifications sur tous les produits
	* @return : void()
	************************************************************************/
	public function deleteUser()
	{
		// Suppression du user dans la table user
		$query = "DELETE FROM users WHERE id_user = '{$this->idUser}'";
		self::$database->execute($query);
		// Suppression du user dans la table sys_user_group
		$query = "DELETE FROM sys_user_group WHERE id_user = '{$this->idUser}'";
		self::$database->execute($query);
		// Suppression du user dans la table sys_report_sendmail
		$query = "DELETE FROM sys_report_sendmail WHERE mailto = '{$this->idUser}' AND mailto_type = 'user'";
		self::$database->execute($query);
        // Suppression du user dans la table sys_alarm_sms_sender (DE SMS)
        $query = "DELETE FROM sys_alarm_sms_sender WHERE recipient_id='{$this->idUser}' AND recipient_type='user'";
        self::$database->execute($query);

        // Déploiement de la table users sur les autres produits
		UserModel::deployUsers();
		// Il faut également déployer les groupes
		GroupModel::deployGroups();
	}

	/************************************************************************
	* Méthode getUserGroups : récupère les groupes auxquels le user est abonné
	* Répercute les modifications sur tous les produits
	* @return : void()
	************************************************************************/
	public function getUserGroups()
	{
		$query = "SELECT * FROM sys_user_group WHERE id_user = '{$this->idUser}'";
		return self::$database->getAll($query);
	}

	/************************************************************************
	* Méthode getError : retourne le code d'erreur du user
	* @return : true = pas d'erreur, false = objet inutilisable
	************************************************************************/
	public function getError()
	{
		return $this->error;
	}

	/**
	* Regarde si l'utilisateur a le droit d'accéder à cette zone
	* Nécessite "ProfileModel.class"
	* @param int $idMenu id menu
	* @return bool
         * 
         * Cette méthode ne doit pas être utilisée si $id_user =! $_SESSION['id_user']
	*/
	public function userAuthorized($idMenu)
	{
            // 20/02/2012 NSE DE Astellia Portal Lot2 : suppression de la jointure des tables users et profile
            if( $this->userValues['user_profil'] != $_SESSION['user_profil'] ){
                throw new BadMethodCallException("Calling userAuthorized() with  user_profil($user_profil) != _SESSION['user_profil'](".$_SESSION['user_profil'].")");
            }
            $ProfileModel = new ProfileModel($this->userValues['user_profil']);
            return $ProfileModel->isMenuInProfile($idMenu);
	}


	/**
	* Méthode getUserProfileType : retroune le profile_type du profile du user
	* Nécessite "ProfileModel.class"
	* @return : string (customisateur, admin, user)
         * Cette méthode ne doit pas être utilisée si $id_user =! $_SESSION['id_user']
	*/
	public function getUserProfileType()
	{
            // 23/02/2012 NSE DE Astellia Portal Lot2
            if( $this->idUser != $_SESSION['id_user'] ){
                throw new BadMethodCallException("Calling getUserProfileType() with id_user(".$this->idUser.") != _SESSION['id_user'](".$_SESSION['id_user'].")");
            }
            else{                
		$ProfileModel = new ProfileModel($this->userValues['user_profil']);
		// 04/08/2010 - MPR : Correction du BZ 15045
                // Le paramètre id_menu n'existe pas dans la méthode getProfileType()
                return $ProfileModel->getProfileType();
            }
	}

	/************************ STATIC FUNCTIONS **************************/

	/************************************************************************
	* Méthode deployUsers : Copie la table des utilisateurs sur tous les produits
	* @return : void()
         *
         * 16/12/2010 NSE bz 19745 : ajout de l'id_prod pour utilser la bonne base (celle du master lors d'un appel à partir du slave)
	************************************************************************/
	public static function deployUsers($id_prod=0)
	{
            // Connexion à la base de données
            $database = Database::getConnection($id_prod);

            // Récupération de la table Users (avec et sans le phone_number, DE SMS)
            $users_table   = $database->getTable( 'users' );
            $users_table_2 = $database->getTable( 'users', array( 'phone_number' ) );

            // On boucle sur tous les produits (sauf le maître)
            $query = "SELECT sdp_id FROM sys_definition_product WHERE sdp_master = 0 AND sdp_on_off = 1";
            foreach( $database->getAll( $query ) as $array_prod )
            {
                // Connexion au produit
                $db_temp = Database::getConnection( $array_prod['sdp_id']);

                // On vide la table Users
                $db_temp->execute( 'TRUNCATE TABLE users' );

                // On restaure les informations du maître
                // 21/07/2011 OJT : DE SMS, gestion de la compatibilité (enable_alarm_sms)
                if( get_sys_global_parameters( 'enable_alarm_sms', 0, $array_prod['sdp_id'] ) == 0 )
                {
                    $db_temp->setTable( 'users', $users_table_2 );
                }
                else
                {
                    $db_temp->setTable( 'users', $users_table );
                }
            }
	}

	/**
	* Retourne la liste des utilisateurs
	* @return array liste des utilisateurs
	*/
	public static function getUsers()
	{
            // NSE DE Astellia Portal Lot2 : 
            // le paramètre visible n'est plus utilisé, ainsi que la jointure avec la table profile
		$database = Database::getConnection(0);
		$query = "SELECT *
		FROM users 
                WHERE visible = 1
		ORDER BY username";
		return $database->getAll($query);
	}

	/************************************************************************
	* Méthode getUsers : retourne la liste des utilisateurs non abonnés aux alertes d'une connexion
	* @param : string	id de la connexion
	* @return : array	liste des utilisateurs
	************************************************************************/
	public static function getUsersNotCheckedForAConnection($connectionId,$idProd)
	{
		// 11/05/2010 BBX
		// Il faut utiliser l'id produit pour éxécuter la requête. BZ 15414.
                // 20/02/2012 NSE DE Astellia Portal Lot2 : suppression de la jointure avec la table profile et de user_prenom
		$database = Database::getConnection($idProd);
		$query = "SELECT id_user,username
		FROM users 
		WHERE id_user NOT IN
			(SELECT DISTINCT sdupc_id_user as id_user
			FROM sys_definition_users_per_connection
			WHERE sdupc_id_connection = {$connectionId})
                AND visible = 1
		-- AND (p.client_type <> 'protected' OR p.client_type IS NULL)
		ORDER BY username";
		return $database->getAll($query);
	}

	/************************************************************************
	* Méthode getUsers : retourne la liste des utilisateurs abonnés aux alertes d'une connexion
	* @param : string	id de la connexion
	* @return : array	liste des utilisateurs
	************************************************************************/
	public static function getUsersCheckedForAConnection($connectionId, $idProd="")
	{
            // 23/02/2012 NSE DE Astellia Portal Lot2
            // suppression de la jointure avec la table profile
		$database = Database::getConnection($idProd);
		$query = "SELECT id_user,username
		FROM users 
		WHERE id_user IN
			(SELECT DISTINCT sdupc_id_user as id_user
			FROM sys_definition_users_per_connection
			WHERE sdupc_id_connection = {$connectionId})
                AND visible = 1
		--AND (p.client_type <> 'protected' OR p.client_type IS NULL)
		ORDER BY username";

		__debug($query,"QUERY");
		return $database->getAll($query);
	}

        /**
         * Retourne la liste des users en double, triple, etc...
         * sous forme de tableau avec le login en clé et l'id en valeur
         * @author BBX
         * @param integer $idProd
         * @return array
         */
        public static function getUsersWithSameLogin($idProd = 0)
        {
            // Liste des users
            $usersWithSameLogin = array();

            // Connexion à la base de données
            $db = Database::getConnection($idProd);

            // Requête de déection des doublons
            $query = "SELECT login, id_user
                FROM users
                WHERE login IN (
                    SELECT login
                    FROM users
                    GROUP BY login
                    HAVING count(login) > 1)
                GROUP BY login, id_user";
            $result = $db->execute($query);

            // Récupération du résultat
            while($row = $db->getQueryResults($result,1)) {
                $usersWithSameLogin[$row['login']][] = $row['id_user'];
            }

            // Retour du tableau
            return $usersWithSameLogin;
        }

        /**
         * Permet de fusionner 2 comptes utilisateurs
         * @param text $userIdSource
         * @param text $userIdToMerge
         * @param integer $idMaster
         * @param integer $idSlave
         * @return boolean
         */
        public static function mergeAccounts($userIdSource, $userIdToMerge, $idMaster = 0, $idSlave = 0)
        {
            // Mono ou multi produits
            $multiProduits = (($idMaster != $idSlave) && $idSlave != 0);

            // Connexion à la base de données
            $dbMaster = Database::getConnection($idMaster);
            if($multiProduits)
                $dbSlave = Database::getConnection($idSlave);

            // Démarrage de la transaction
            $dbMaster->execute("BEGIN");
            if($multiProduits)
                $dbSlave->execute("BEGIN");

            // Variable de contrôle
            $execCtrl = true;

            // Liste des tables concernées
            $tables = Array("edw_comment",
                "forum_formula",
                "my_network_agregation",
                "report_builder_save",
                "sys_contenu_buffer",
                "sys_definition_users_per_connection",
                "sys_file_uploaded_archive",
                "sys_panier_mgt",
                "sys_pauto_page_name",
                "sys_user_group",
                "track_pages",
                "track_users",
                "users",
                "qb_queries");

            // Colonne par défaut : "id_user", sauf pour les tables suivantes
            $columns = Array("sys_definition_users_per_connection" => "sdupc_id_user",
                "qb_queries" => "user_id");

            // Action par défaut : remplacement, sauf pour les tables suivantes
            $delete = Array("users");

            // Traitement
            foreach($tables as $table)
            {
                // Colonne
                $column = array_key_exists($table,$columns) ?
                        $columns[$table] : "id_user";

                // Suppression
                if(in_array($table, $delete))
                {
                    // Requête de suppression
                    $query = "DELETE FROM $table
                        WHERE $column = '$userIdToMerge'";
                }
                // Remplacement
                else
                {
                    // Requête de remplacement
                    $query = "UPDATE $table
                        SET $column = '$userIdSource'
                        WHERE $column = '$userIdToMerge'";
                }

                // Execution !
                $execCtrl = $execCtrl && (!$dbMaster->execute($query) ? false : true);
                if($multiProduits)
                    $execCtrl = $execCtrl && (!$dbSlave->execute($query) ? false : true);
            }

            // Fin de la transaction
            if($execCtrl) {
                $dbMaster->execute("COMMIT");
                if($multiProduits)
                    $dbSlave->execute("COMMIT");
            }
            else {
                $dbMaster->execute("ROLLBACK");
                if($multiProduits)
                    $dbSlave->execute("ROLLBACK");
            }

            // Retour de la fonction
            return $execCtrl;
        }
        
        /**
         * 05/12/2011 BBX
         * Méthode qui récupère les admins
         * Créé dans le cadre de la correction du BZ 24843
         * @param boolean $astellia_admin indique s'il faut retrourner également l'utilisateur 'astellia_admin'
         * @return type 
         */
        public static function getAdmins($astellia_admin=true)
        {
            // 23/02/2012 NSE DE Astellia Portal Lot2
            // supprimer le fichier de conf
            $PAAAuthentication = PAAAuthenticationService::getAuthenticationService(REP_PHYSIQUE_NIVEAU_0 . '/api/paa/conf/PAA.inc');
 
            // on prépare la liste des droits PAA à partir de la liste des profiles TA
            $admProfiles = ProfileModel::getAdminProfiles($astellia_admin);
            // 22/03/2012 NSE bz 26496 : Ajout de .'.'.APPLI_GUID_NAME pour identifier le droit
            foreach($admProfiles as $profile)
                $rights[] = APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME.'.'.$profile['id_profile'];
             // on récupère les utilisateurs sur le Portail
            $adminsLogins = $PAAAuthentication->getUsersWithRights($rights,APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME,true);
            
            // 25/10/2012 BBX
            // BZ 30044 : ajout de quotes autour des éléments de la requête
            $db = Database::getConnection();
            $query = "SELECT id_user, username, login, user_mail
                FROM users
                WHERE login IN ('".  implode("', '", $adminsLogins)."')";
            
            return $db->getAll($query);
        }
        
        /**
         * Retourne l'identifiant de l'utilisateur à partir de son login
         * @param string $login
         * @param int $idProd
         * @return string 
         * 23/02/2012 NSE DE Astellia Portal Lot2
         */
        public static function getUserId($login,$idProd = 0)
        {
            // Connexion à la base de données
            $db = Database::getConnection($idProd);
            
            $query = "SELECT id_user
                FROM users
                WHERE login='$login'";
            
            return $db->getOne($query);
        }
        
        /**
         * Met à jour la table locale des utilisateurs à partir du PAA
         * 23/02/2012 NSE DE Astellia Portal Lot2
         */
        public static function updateLocalUsersList(){
            $d = microtime(true);
            // supprimer le fichier de conf
            $PAAAuthentication = PAAAuthenticationService::getAuthenticationService(REP_PHYSIQUE_NIVEAU_0 . '/api/paa/conf/PAA.inc');
            displayInDemon('Update of local users list from PAA ('.PAA_SERVICE.' mode)','title',true);
            displayInDemon(date('d/m/Y H:i:s'),'normal',true);
            
            // on récupère les utilisateurs sur le Portail
            // 22/03/2012 NSE bz 26496 : Ajout de .'.'.APPLI_GUID_NAME pour identifier le droit
            $profiles = ProfileModel::getProfiles();
            foreach($profiles as $profile){
                $rights[] = APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME.'.'.$profile['id_profile'];
                $profilesA[] = $profile['id_profile'];
            }
            $PAAusers = $PAAAuthentication->getUsersWithRights($rights,APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME,true);
            
            // 16/03/2012 NSE bz 26404
            // On récupère la liste des utilisateurs dont le profile est protégé
            // on met ces utilisateurs à visible = 0
            $allProfiles = ProfileModel::getAllProfiles();
            foreach($allProfiles as $profile){
                $allProfilesB[] = $profile['id_profile'];
            }
            $protectedProfiles = array_diff($allProfilesB, $profilesA);
            // 22/03/2012 NSE bz 26496 : Ajout de .'.'.APPLI_GUID_NAME pour identifier le droit
            foreach($protectedProfiles as $profile)
                $protectedRights[] = APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME.'.'.$profile;
            $protectedPAAusers = $PAAAuthentication->getUsersWithRights($protectedRights,APPLI_GUID_HEXA.'.'.APPLI_GUID_NAME,true);
            
            if(empty ($PAAusers)){
                displayInDemon('Empty Paa users list','alert',true);
                displayInDemon('Rights: '.print_r($rights,true).'<br>PAAusers: '.print_r($PAAusers,true),'normal',true);
                return 'no user on PAA';
            }
            // 15/04/2013 NSE Phone number managed by Portal
            $phoneNumberOnPortal = $PAAAuthentication->doesPortalServerApiManage('phone_number');
            // on parcourt les utilisateurs récurépés sur le Portail
            foreach ($PAAusers as $PAAuserlogin) {
                $att = $PAAAuthentication->getUserAttributes($PAAuserlogin);
                // on met à jour les utilisateurs locaux
                $userToUpdate = new UserModel(UserModel::getUserId($PAAuserlogin));
                $userToUpdate->setValue('user_mail',$att['mail']);
                $userToUpdate->setValue('username',$att['fullname']);
                // 15/04/2013 NSE Phone number managed by Portal
                if($phoneNumberOnPortal)
                    $userToUpdate->setValue('phone_number',$att['phonenumber']);
                if(in_array($PAAuserlogin, $protectedPAAusers))
                    $userToUpdate->setValue('visible',0);
                else
                    $userToUpdate->setValue('visible',1);
                if(UserModel::getUserId($PAAuserlogin)){
                    displayInDemon('mise à jour de "'.$PAAuserlogin.'" : '.$att['fullname'].' - '.$att['mail'].' - '.$att['phonenumber'],'list',true);
                    $userToUpdate->updateUser(false);
                }
                else{
                    $userToUpdate->setValue('login',$PAAuserlogin);
                    displayInDemon( 'ajout de "'.$PAAuserlogin.'" : '.$att['fullname'].' - '.$att['mail'].' - '.$att['phonenumber'],'list',true);
                    $userToUpdate->addUser(false);
                }
            }
            UserModel::deployUsers();

            // on parcourt la liste des utilisateurs locaux
            $localUsers = UserModel::getUsers();
            //print_r($localUsers);
            foreach ($localUsers as $localUser) {//echo "<br>".$localUser['login']. ' : ';
                $att = $PAAAuthentication->getUserAttributes($localUser['login']);
                //print_r($att);
                // on supprime ceux qui n'existent plus sur le Portail
                // 06/03/2012 NSE bz 26214 : 
                // ou existent sur le Portail mais n'ont pas de droit sur cette application
               if(empty ($att['fullname']) && empty ($att['mail']) || !in_array($localUser['login'], $PAAusers)){
                    $userToDelete = new UserModel(UserModel::getUserId($localUser['login']));
                    displayInDemon('suppression de "'.$localUser['login'].'"','list',true);
                    $userToDelete->deleteUser();
                }                    
            }  
            $f = microtime(true);
            displayInDemon('durée : '.round($f-$d, 6).' secondes','normal',true);
         }
         
         /**
         * Met à jour la table locale des utilisateurs à partir du PAA pour un utilisateur donné
          * @param string login login de l'utilisateur
          * 23/02/2012 NSE DE Astellia Portal Lot2
          * 03/12/2012 BBX
          * BZ 30310 : plus de messades dans le démon, mais uniquement dans le tracelog
         */
        public static function updateLocalUsersAttributes($login){
            $d = microtime(true);
            // supprimer le fichier de conf
            $PAAAuthentication = PAAAuthenticationService::getAuthenticationService(REP_PHYSIQUE_NIVEAU_0 . '/api/paa/conf/PAA.inc');
            //displayInDemon('Update of local user "'.$login.'" from PAA ('.PAA_SERVICE.' mode)','title',true);
            //displayInDemon(date('d/m/Y H:i:s'),'normal',true);
            
            $att = $PAAAuthentication->getUserAttributes($login);
            // on met à jour les utilisateurs locaux
            $userToUpdate = new UserModel(UserModel::getUserId($login));
            $userToUpdate->setValue('user_mail',$att['mail']);
            $userToUpdate->setValue('username',$att['fullname']);
            // 15/04/2013 NSE Phone number managed by Portal
            if($PAAAuthentication->doesPortalServerApiManage('phone_number'))
                $userToUpdate->setValue('phone_number',$att['phonenumber']);
            if(UserModel::getUserId($login)){
                //displayInDemon('mise à jour de "'.$login.'" : '.$att['fullname'].' - '.$att['mail'],'list',true);
                $message = 'Update of account "'.$login.'" : '.$att['fullname'].' - '.$att['mail'].' - '.$att['phonenumber']; 
                $userToUpdate->updateUser(true);
            }
            else{
                $userToUpdate->setValue('login',$login);
                //displayInDemon( 'ajout de "'.$login.'" : '.$att['fullname'].' - '.$att['mail'],'list',true);
                $message = 'Creating account "'.$login.'" : '.$att['fullname'].' - '.$att['mail'].' - '.$att['phonenumber']; 
                $userToUpdate->addUser(true);
            }

            $f = microtime(true);
            //displayInDemon('durée : '.round($f-$d, 6).' secondes','normal',true);
            $message .= ' (executed in '.round($f-$d, 6).'s)';
            sys_log_ast('Info', get_sys_global_parameters( 'system_name' ), 'Login', $message);
        }
        
        /**
         * Suppression des utilisateurs utilisés via portail
         * GFS 17/01/2013 - BZ#31387 : [QAL][T&A Gateway][Remove Product] : Product not accesible after being removed from gateway 
         * 
         */
        public static function cleanUsers($idProduct){
			// Connexion à la base de données
			$db = Database::getConnection($idProduct);		
			$db->execute("DELETE FROM users WHERE password IS NULL OR password = ''");
			$db->execute("DELETE FROM profile WHERE id_profile ilike 'prfl.9999%'");
		}
}
