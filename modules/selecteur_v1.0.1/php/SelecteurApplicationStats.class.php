<?php
/**
 * 
 *  CB 5.2
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 */
?><?php
/**
*	Classe permettant de manipuler un sélecteur pour les dashboard
*
*	@author	BBX - 30/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*
*	12/08/2009 GHX
*		- Correction du BZ 6652
*			-> Modification dans la fonction getUsers()
*/
class SelecteurApplicationStats
{
	/**
	* Propriétés
	*/
	// Tableau contenu les propriétés du sélecteur
	private $selecteur_values = Array();
	// Contiendra un objet sélecteur
	private $selecteur = null;
	// Contiendra une instance de connexion à la base de données
	private $database = null;
	
	private $mode = 'traffic';
	

	
	/**
	* Constructeur
	* @param : int $mode
	*/
	public function __construct($mode)
	{
		$this->mode = $mode;
		
		if( $mode == 'traffic' )
		{
			$this->selecteur_values = array("date"=> date("d/m/Y") );
		}else
		{
			$this->selecteur_values = array("date"=> date("d/m/Y"), "period"=> 24, "ta_level"=> 'day',"user"=> 'all' );
		}
		
	}
	
	/**
	* Méthode getSelecteurFromArray : paramètre un sélecteur depuis un tableau de valeurs
	* @param : array	Tableau contenant un paramétrage sélecteur
	*/
	public function getSelecteurFromArray($array)
	{
		if(count($array) > 0) {
			foreach($array as $key=>$value) {
				$this->selecteur_values[$key] = $value;
			}
		}
	}

	/**
	* Méthode getTaArray : génère un tableau contenant la liste des TA
	* @return : array(array,array)	Tableau contenant les niveaux d'agrégation temporels et les valeurs par défault
	*
	*/
	public function getTaArray()
	{
		// EN DUR POUR LE MOMENT
	
		// TA levels	
		$ta_levels = array(
		//	'value'	=> 'label',
			'hour'		=> 'Hour',
			'day'		=> 'Day',
			'week'	=> 'Week',
			'month'	=> 'Month'

		);

		// defaults values for this box
		$defaults = array(
			'ta_level'	=> 'Hour',
			'date'		=> date('d/m/Y'),
			'hour'		=> date('H:00'),
			'period'	=> 24,
		);
		return Array($ta_levels,$defaults);
	}
	
	/**
	* Méthode build : construit un sélecteur
	* @return array $this->selecteur_values : paramètres du sélecteur
	*/
	public function build()
	{
		// Instanciation d'un sélecteur
		$this->selecteur = new selecteur();
		
		// On met les valeurs dans le sélecteur
		if(is_array($this->selecteur_values)) $this->selecteur->setValues($this->selecteur_values);
		
		// on ajoute la boite "time"
		if($this->mode == 'traffic') 
		{
			// on ajoute la boite "time"
			$this->selecteur->addBox(__T('SELECTEUR_TIME'),'dashboard_time',array(),array('hide' => 'hour,period,ta_level,autorefresh'));
		}
		else
		{
			// on ajoute la boite "time"
			$this->selecteur->addBox(__T('SELECTEUR_TIME'),'dashboard_time', $this->getTaArray(), array('hide' => 'autorefresh'));
			// on ajoute la boite "users"
			$this->selecteur->addBox(__T('SELECTEUR_USER'),'users_list', $this->getUsers(), array());
		}
	
		// Affichage du sélecteur
		$this->selecteur->display();
		
		return $this->selecteur_values;
	}
	
	
	/**
	* Méthode get_users : getUsers
	*
	* @return array(array,array)
	*/
	public function getUsers()
    {
        $database = DataBase::getConnection();

		// 14:53 12/08/2009 GHX BZ 6652
		(getClientType($_SESSION['id_user']) == 'client')? $view_admin = "AND visible = 1" : $view_admin = "";
                        // 20/02/2012 NSE DE Astellia Portal Lot2 : suppression de user_prenom, on_off et date_valid
			$sql = " SELECT id_user, username AS users_list FROM users 
					ORDER BY username
				   ";

			$users_list = array(0=>'ALL');			
			$defaults = array(0);			
			$result = $database->getAll($sql);
		
			foreach($result as $field){
				if( !in_array($field['users_list'],$users_list) )
					$users_list[$field['id_user']] = $field['users_list'];

			}

			// $this->selecteur_values['users_list'] = $users_list;
			return array($users_list,$defaults);
	}
	
	/************************************************************************
	* Méthode debug : affiche les valeurs du sélecteur
	************************************************************************/
	public function debug()
	{
		echo '<div>Valeurs du s&eacute;lecteur :</div>';
		echo '<pre>';
		print_r($this->selecteur_values);
		echo '</pre>';
	}
	
	/************************************************************************
	* Méthode debug : affiche les valeurs du sélecteur
	************************************************************************/
	public function __destruct(){
		unset($this->selecteur_values);
	}
}
?>