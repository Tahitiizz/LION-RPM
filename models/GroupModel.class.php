<?php
/*
	29/01/2009 GHX
		- modification de la création du id_groupe. Ce n'est plus un MAX+1 mais un unique ID  [REFONTE CONTEXTE]
		- modification des requêtes SQL pour mettre id_groupe & id_user entre cote  [REFONTE CONTEXTE]
	02/02/2009 GHX
		- Appel à la fonction qui génére un unique ID generateUniqId() [REFONTE CONTEXTE]

	05/03/2010 BBX
		- Ajout de la méthode "manageConnections"
		- Utilisation de la méthode "manageConnections" au lieu de DatabaseConnection afin d'éviter les instances redondantes
	08/03/2010 BBX
		- Suppression de la méthode "manageConnections"
		- Utilisation de la méthode "Database::getConnection" à la place.
 *      16/12/2010 NSE bz 19745 : ajout d'un paramètre optionnel (id_product) à deployGroup pour requéter sur le bon produit (pas forcément le produit courant)
*/
?>
<?php
/**
*	Classe permettant de manipuler les groupes
*	Travaille sur la table users, sys_user_group, sys_report_sendmail, sys_alarm_email_sender
*
*	@author	BBX - 28/11/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*
*
*/
class GroupModel
{
	/**
	* Propriétés
	*/
	private $idGroup = 0;
	private $database = null;
	private $groupValues = Array();
	private $error = false;

	// Mémorise les instances de connexions ouvertes
	private static $connections = Array();

	/************************************************************************
	* Constructeur
	* @param : int	id user
	************************************************************************/
	public function __construct($idGroup)
	{
		// Sauvegarde de l'id groupe
		$this->idGroup = $idGroup;
		// Connexion à la base de données
		$this->database = Database::getConnection(0);
		// Récupération des valeurs du user
		if(is_numeric($idGroup) || is_string(idGroup)) {
			$query = "SELECT * FROM sys_user_group WHERE id_group = '{$idGroup}'";
			$array = $this->database->getRow($query);
			// Si les infos user ne sont pas récupérées, on renvoie une erreur
			if(count($array) == 0) {
				$this->error = true;
			}
			else {
				$this->groupValues = $array;
			}
		}
		else {
			// Si le format de l'id est incorrect, on renvoie une erreur
			$this->error = true;
		}
	}

	/************************************************************************
	* Méthode getValues : retourne un tableau associatif contenant les paramètres du groupe
	* @return : array	Tableau associatif
	************************************************************************/
	public function getValues()
	{
		return $this->groupValues;
	}

	/************************************************************************
	* Méthode setValue : ajoute une valeur à l'objet
	* @return : void()
	************************************************************************/
	public function setValue($key,$value)
	{
		$this->groupValues[$key] = $value;
	}

	/************************************************************************
	* Méthode addGroup : ajoute un groupe en mémoire. Rien n'est écrit en base tant que
	* la méthode "addUser" ou "addUserList" n'est pas appelée.
	* @return : void()
	************************************************************************/
	public function addGroup()
	{
		//Calcul du nouvel id igroupe
		// 29/01/2009 GHX
		// Dans la refonte du contexte, l'identifiant d'un groupe est unique. On ne fait plus MAX+1
		// 14:23 02/02/2009 GHX
		// Appel à la fonction qui génére un unique ID
		$newIdGroup = generateUniqId('sys_user_group');
		// Sauvegarde de l'id groupe
		$this->idGroup = $newIdGroup;
	}

	/************************************************************************
	* Méthode updateGroup : met à jour un groupe :
	* @return : void()
	************************************************************************/
	public function updateGroup()
	{
		foreach($this->groupValues as $key=>$value) {
			$value = ($value == '') ? "NULL" : ((is_numeric($value)) ? $value : "'{$value}'");
			$query = "UPDATE sys_user_group set {$key} = {$value} WHERE id_group = '{$this->idGroup}'";
			$this->database->execute($query);
		}
		// Il faut déployer les groupes sur tous les produits
		GroupModel::deployGroups();
	}

	/**
	 * Méthode deleteGroup : supprime un groupe avec les dépendances
     *
	 * @return void
	 */
	public function deleteGroup()
	{
		// Suppression du groupe
        $this->deleteGroupOnly();

		// Suppression dans sys_report_sendmail
		$query = "DELETE FROM sys_report_sendmail WHERE mailto = '{$this->idGroup}' AND mailto_type = 'group'";
		$this->database->execute($query);

		// Suppression dans sys_alarm_email_sender
		$query = "DELETE FROM sys_alarm_email_sender WHERE id_group = '{$this->idGroup}'";
		$this->database->execute($query);

        // Suppression dans sys_alarm_sms_sender (DE SMS)
        $query = "DELETE FROM sys_alarm_sms_sender WHERE recipient_id='{$this->idGroup}' AND recipient_type='group'";
        $this->database->execute($query);

		// Il faut déployer les groupes sur tous les produits
		GroupModel::deployGroups();
	}

	/************************************************************************
	* Méthode deleteGroupOnly : supprime un groupe uniquement dans la table des groupes
	* et sans déployer
	* @return : void()
	************************************************************************/
	public function deleteGroupOnly()
	{
		// Suppression du groupe
		$query = "DELETE FROM sys_user_group WHERE id_group = '{$this->idGroup}'";
		$this->database->execute($query);
	}

	/************************************************************************
	* Méthode addUser : ajoute un user au groupe
	*
	*
	* @param int	id user
	* @return : void()
	************************************************************************/
	public function addUser($idUser)
	{
		// Insertion du nouveau user
		$query = "INSERT INTO sys_user_group (id_group,group_name,on_off,id_user)
		VALUES ('{$this->idGroup}','{$this->groupValues['group_name']}',{$this->groupValues['on_off']},'{$idUser}')";
		$this->database->execute($query);
	}

	/************************************************************************
	* Méthode addUser : ajoute une liste d'utilisateur au groupe et déploie les groupes
	* @param array : 	tableau d'id utilisateurs
	* @return : void()
	************************************************************************/
	public function addUserList($arrayUsers)
	{
		foreach($arrayUsers as $idUser) {
			$this->addUser($idUser);
		}
		// Il faut déployer les groupes sur tous les produits
		GroupModel::deployGroups();
	}

	/**
	 * Méthode getUsers : récupère les users abonés à ce groupe
     *
     * 26/07/2011 OJT : DE SMS, ajout du phone_number
     *
	 * @return void
	 */
	public function getUsers()
	{
		$query = "SELECT u.id_user,u.username,u.login,u.phone_number
		FROM users u, sys_user_group g
		WHERE u.id_user = g.id_user
		AND g.id_group = '{$this->idGroup}'
		ORDER BY u.username";
		return $this->database->getAll($query);
	}

	/************************************************************************
	* Méthode getError : retourne le code d'erreur du user
	* @return : true = pas d'erreur, false = objet inutilisable
	************************************************************************/
	public function getError()
	{
		return $this->error;
	}

	/************************ STATIC FUNCTIONS **************************/

	/************************************************************************
	* Méthode deployGroups : Copie la table des groupes sur tous les produits
	* @return : void()
         *
         * 16/12/2010 NSE bz 19745 : ajout de l'id_prod pour utilser la bonne base (celle du master lors d'un appel à partir du slave)
	************************************************************************/
	public static function deployGroups($id_prod=0)
	{
		// Connexion à la base de données
		$database = Database::getConnection($id_prod);
		// Récupération de la table sys_user_group
		$groups_table = $database->getTable('sys_user_group');
		// On boucle sur tous les produits (sauf le maître)
		$query = "SELECT sdp_id FROM sys_definition_product WHERE sdp_master = 0 AND sdp_on_off = 1";
		foreach($database->getAll($query) as $array_prod) {
			// Connexion au produit
			$db_temp = Database::getConnection($array_prod['sdp_id']);
			// On vide la table sys_user_group
			$query_truncate = "TRUNCATE TABLE sys_user_group";
			$db_temp->execute($query_truncate);
			// On restaure les informations du maître
			$db_temp->setTable('sys_user_group',$groups_table);
		}
	}

	/************************************************************************
	* Méthode getGroups : Liste les groupes existants
	* @return : array	liste des groupes
	************************************************************************/
	public static function getGroups()
	{
		// Connexion à la base de données
		$database = Database::getConnection(0);
		// Requête
		$query = "SELECT DISTINCT id_group, group_name FROM sys_user_group";
		return $database->getAll($query);
	}
}
