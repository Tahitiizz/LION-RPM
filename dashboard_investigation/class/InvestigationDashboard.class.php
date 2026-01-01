<?php
/*
	16/07/2009 GHX
		- Modification de la prise en compte du nombre maximum d'affichage avec les familles 3 troisiemes axe
		- Modification du ORDER BY de la requete qui récupère les données
		- Modification du SQL dans la fonction getGroupTableByCounter()
		- Ajout de l'id du produit lors de l'appel de la fonction get_sys_global_parameters()
		-  Correction du BZ 10637 [REC][T&A Cb 5.0][Investigation Dashboard] :  on affiche le nombre maximum de sélection + 1
	12/08/2009 GHX
		- Correction du BZ 10605
			-> Affichage d'un message si on dépasse la limite
	13/08/2009 GHX
		- Correction du BZ 11013 [REC][Investigation dahsboard]] Code des NE au lieu des labels
		- Correction du BZ 6758 [Investigation dashboard] Affichage d'une erreur jpgraph si des valeurs négatives sont affichées
	28/08/2009 MPR :
		- Ajout d'un message indiquant que le caddy a été mis à jour
	16:45 28/10/2009 SCT :
		- BZ 12349 : aucun résultat affiché sur l'investigation dashboard
	29/10/2009 BBX :
		- correction des requêtes pour le filtre. BZ 12358
   23/02/2011 MMT :
      - bz 18003 : utilisation de ouvrir_fenetre pour l'export Xls
   06/06/11 MMT DE 3rd Axis:
 *		- ajout des methodes create_query et execute_query pour gestion des elements 3eme axes comme pour le
 *      1er axe
 * 15/07/2011 MMT Bz 22600 ajout gestion du mapping de topo
*/
?>
<?php
/**
 * classe InvestigationDashboard
 * a partir des donnees du selecteur (SelecteurInvestigation), elle va creer les requetes et chercher les donnees
 * a noter, la classe reprend en partie des elements du module present en v4.0
 *
 * 	@author SPS
 * 	@date 28/05/2009
 * 	@version CB 5.0.0.0
 * 	@since CB 5.0.0.0
 *
 *	05/06/2009 SPS
 *		- limitation du nombre de resultats (correction bug 9963)
 **/
include_once dirname(__FILE__)."/../../php/environnement_liens.php";

include_once(REP_PHYSIQUE_NIVEAU_0."class/Date.class.php");
include_once(MOD_CHARTFROMXML . "class/graph.php");
include_once(MOD_CHARTFROMXML . "class/SimpleXMLElement_Extended.php");
include_once(MOD_CHARTFROMXML . "class/chartFromXML.php");

//nombre maximum de selection
$MAX_SELECTION = get_sys_global_parameters("investigation_dashboard_max_selection");


/**
 * classe InvestigationDashboard
 * a partir des donnees du selecteur (SelecteurInvestigation), elle va creer les requetes et chercher les donnees
 *
 * @author SPS
 * @date 28/05/2009
 * @version CB 5.0.0.0
 * @since CB 5.0.0.0
 * @package InvestigationDashboard
 * @see DatabaseConnection Date, chartFromXML
 **/
class InvestigationDashboard {

	/**
	 * id du produit
	 * @var int
	 **/
	private $product;

	/**
	 * id de la famille
	 * @var int
	 **/
	private $family;

	/**
	 * connexion a la base
	 * @var DatabaseConnection
	 **/
	private $database;

	/**
	 * donnees du selecteur
	 * @var mixed
	 **/
	private $selecteur_values;

	/**
	 * mode debug (par defaut a 0)
	 * @var int
	 **/
	private $debug = 0;

	/**
	 * Tableau contenant les labels des éléments réseaux troisiemes axes
	 * @var array
	 */
	private $labelThirdAxis = array();
	/**
	 * Tableau contenant les labels des éléments raw ou kpi
	 * @var array
	 */
	private $labelRawKpi = array();


	/**
	 * 06/06/11 MMT DE 3rd Axis
	 * true if the current family/product contains a 3rd axis
	 * if -1 means the query hasn't been run yet (we don't know)
	 * @var boolean/int
	 */
	private $has3rdAxis = -1;

	/**
	 * constructeur de la classe
	 * @param int $product id du produit
	 * @param int $famimy id de la famille
	 **/
	public function __construct($product, $family) {
		$this->product = $product;
		$this->family = $family;
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$this->database = Database::getConnection($product);
	}

	/**
	 * setteur des valeurs du selecteur
	 * @param mixed $tab_values donnees du selecteur
	 **/
	public function setSelecteurValues($tab_values) {

		// 06/06/11 MMT DE 3rd Axis ajoute test sur 3eme axe obligatoire
		if ($tab_values['investigation_nel_selecteur'] != ''
				&& $tab_values['investigation_counters_selecteur'] != ''
				&& (!$this->has3rdAxis() || $tab_values['axe3_selecteur'] != ''))
		{
			$this->selecteur_values = $tab_values;
		}
		else {
			throw new Exception(__T('U_INVESTIGATION_DASHBOARD_NOT_ENOUGH_ELEMENTS'));
		}
	}

	/**
	 * getteur des valeurs du selecteur
	 * @return mixed valeurs du selecteur
	 **/
	public function getSelecteurValues() {
		return $this->selecteur_values;
	}

	/**
	 * on met en mode debug
	 **/
	public function setDebugMode() {
		$this->debug = 1;
		//on met en debug la classe de connexion
		$this->database->setDebug(1);
	}

	/**
	 * methode qui va faire tout le traitement
	 * a partir des valeurs du selecteur, elle cree les requetes pour recuperer les valeurs, puis les execute
	 * elle retourne ensuite les valeurs pretes a etre utilisee pour la generation du graph par la classe InvestigationXml
	 * @return mixed tableau des donnees pretes a etre affichees
	 **/
	public function getData() {
		$this->na_selection 			= 	$this->selecteur_values['investigation_nel_selecteur'];
        $this->counter_selection 		= 	$this->selecteur_values['investigation_counters_selecteur'];
		$this->ta_min_max 				= 	$this->calculate_ta_min_max($this->selecteur_values['ta_level'], $this->selecteur_values['date']);
		$this->all_ta 					= 	$this->calculate_all_ta($this->selecteur_values['ta_level'], $this->ta_min_max["date_fin"], $this->selecteur_values['period']);
		$this->network_aggregation_list = 	$this->calculate_network_aggregation_list($this->na_selection);
		// 06/06/11 MMT DE 3rd Axis ajoute network_axe3_aggregation_list
		$this->network_axe3_aggregation_list = $this->calculate_network_aggregation_list($this->selecteur_values['axe3_selecteur']);
		$this->counter_aggregation_list = 	$this->calculate_counter_aggregation_list($this->counter_selection);

		//creation des requetes SQL
		$this->queries_list 			= 	$this->create_queries_list();
        //on execute les requetes crees
		$this->result_queries 			= 	$this->execute_queries();
		//a partir des resultats, on cree un tableau de resultat
        $this->tableau_complet_resultat = 	$this->create_table_result();

		return $this->tableau_complet_resultat;
	}




	/**
	* Retourne un tableau contenant la date de début et la date de fin
	* des données à afficher en fonction de la période.
	* @param string $ta : time aggregation.
	* @param int $ta_value : valeur de la time aggregation.
	* @return array $array : de la forme $result["date_debut"] // $result["date_fin"]
	*/
	private function calculate_ta_min_max($ta, $ta_value)
    {
        switch ($ta) {
            case "hour":
                $date_fin_timestamp = mktime(substr($ta_value, -2) - $this->selecteur_values['period'], 0, 0, substr($ta_value, 4, 2), substr($ta_value, 6, 2), substr($ta_value, 0, 4));
                $date_debut = date("YmdH", $date_fin_timestamp);
                break;
            case $ta == "day" || $ta == "day_bh":
                $date_fin_timestamp = mktime(0, 0, 0, substr($ta_value, 4, 2), substr($ta_value, 6, 2) - $this->selecteur_values['period'], substr($ta_value, 0, 4));
                $date_debut = date("Ymd", $date_fin_timestamp);
                break;
            case $ta == "week" || $ta == "week_bh":
                $convert_ta_value = $this->getLastDayFromWeek($ta_value);
                $date_fin_timestamp = mktime(0, 0, 0, substr($convert_ta_value, 4, 2), substr($convert_ta_value, 6, 2)-7 * $this->selecteur_values['period'], substr($convert_ta_value, 0, 4));
				// 11/03/2013 GFS - BZ#31364 - [SUP][TA HPG][AVP 32586][Telus][Partionning]: All weekly history is delete on last week of the year compute
                $date_debut = date("oW", $date_fin_timestamp);
                if (strlen($date_debut) == 5) { // si la longueur est de 5, le numero de la semaine est <10, il faut donc ajouter un 0 devant.
                    $date_debut = substr($date_debut, 0, 4) . "0" . substr($date_debut, -1);
                }
                break;
            case $ta == "month" || $ta == "month_bh":
                // force au premier jour du mois
                $date_fin_timestamp = mktime(0, 0, 0, substr($ta_value, -2) - $this->selecteur_values['period'], "01", substr($ta_value, 0, 4));
                $date_debut = date("Ym", $date_fin_timestamp);
                break; ;
        } // switch
        $result["date_debut"] = $date_debut;
        $result["date_fin"] = $ta_value;
        return $result;
    }

    /*
	* fonction qui determine toutes les ta en prenant en compte la ta_fin et la periode
	* Cela sert pour avoir un axe d'abscisse qui met à NULL les valeurs pour lesquelles il n'y a pas de ta_value dans les tables de données
	* @param $ta : qui est la time aggregation
	* @param $ta_value_fin qui est la det renvoyée par le selecteur
	* @param $period : periode du selecteur
	* @return $array_all_ta : tableau qui contient toutes les valeurs temporelles
	*/
    private function calculate_all_ta($ta, $ta_value, $period)
    {
        switch ($ta) {
            case "hour":
                for ($i = 0;$i < $period;$i++) {
                    $date_fin_timestamp = mktime(substr($ta_value, -2) -$period+$i+1, 0, 0, substr($ta_value, 4, 2), substr($ta_value, 6, 2), substr($ta_value, 0, 4));
                    $date_debut = date("YmdH", $date_fin_timestamp);
                    $array_all_ta[$date_debut] = $date_debut;
                }
                break;
            case $ta == "day" || $ta == "day_bh":
                for ($i = 0;$i < $period;$i++) {
                    $date_fin_timestamp = mktime(0, 0, 0, substr($ta_value, 4, 2), substr($ta_value, 6, 2)  -$period+$i+1, substr($ta_value, 0, 4));
                    $date_debut = date("Ymd", $date_fin_timestamp);
                    $array_all_ta[$date_debut] = $date_debut;
                }

                break;
            case $ta == "week" || $ta == "week_bh":

				$convert_ta_value = $this->getLastDayFromWeek($ta_value);
                for ($i = 0;$i < $period;$i++) {
                    $date_fin_timestamp = mktime(0, 0, 0, substr($convert_ta_value, 4, 2), substr($convert_ta_value, 6, 2)-7 * ($period-$i-1), substr($convert_ta_value, 0, 4));
					// 11/03/2013 GFS - BZ#31364 - [SUP][TA HPG][AVP 32586][Telus][Partionning]: All weekly history is delete on last week of the year compute
                    $date_debut = date("oW", $date_fin_timestamp);
                    if (strlen($date_debut) == 5) { // si la longueur est de 5, le numero de la semaine est <10, il faut donc ajouter un 0 devant.
                        $date_debut = substr($date_debut, 0, 4) . "0" . substr($date_debut, -1);
                    }
                    $array_all_ta[$date_debut] = $date_debut;
                }

                break;
            case $ta == "month" || $ta == "month_bh":
                for ($i = 0;$i < $period;$i++) {
                    // force au premier jour du mois
                    $date_fin_timestamp = mktime(0, 0, 0, substr($ta_value, -2)  -$period+$i+1, "01", substr($ta_value, 0, 4));
                    $date_debut = date("Ym", $date_fin_timestamp);
                    $array_all_ta[$date_debut] = $date_debut;
                }

                break; ;
        } // switch
        return $array_all_ta;
    }

	/**
	 * retourne le dernier jour de la semaine
	 * @params string $week semaine
	 * @returns string dernier jour de la semaine
	 * @see Date
	 **/
	function getLastDayFromWeek($week) {
		$firstDayOfWeek = get_sys_global_parameters('week_starts_on_monday');

		$lastDayOfWeek = Date::getLastDayFromWeek($week,$firstDayOfWeek);

		return $lastDayOfWeek;
	}

    /**
	* fonction qui determine une liste des elements d'aggregation à partir de ce qui est retourne par le selecteur
	* @param string $na_selection chaine de caractères précisant les niveau d'aggregation et leur valeurs
	* @return mixed tableau à 2 dimensions dont le premier index est le niveau d'aggregation, le deuxième une valeur d'aggregation de même que la valeur du tableau
	*/
    private function calculate_network_aggregation_list($na_selection)
    {
        $array_temp = explode("|s|", $na_selection);
        // les elements sont separés par des |s| puis à l'interieur on a 3 parties séparées par des (type de na, na_value,na_label)
        foreach ($array_temp as $element) {
            $array_element = explode("@", $element);
				// 15/07/2011 MMT Bz 22600 ajout gestion du mapping de topo
				$na =  $array_element[0];
				$ne_code = NeModel::getUnMappedNE($na,$array_element[1],$this->product);
				if($ne_code === false){
					$ne_code = $array_element[1];
				} 
				$array_result[ $na ][ $ne_code ] = $array_element[2];
        }
		 return $array_result;
    }

	/**
	 * fonction qui determine une liste de raw/kpi a partir des valeurs du selecteur
	 * @param string $counter_selection liste des compteurs selectionnes
	 * @return mixed tableau contenant les compteurs
	 **/
	private function calculate_counter_aggregation_list($counter_selection)
    {
        $array_temp = explode("|s|", $counter_selection);
        // les elements sont separés par des |s| puis à l'interieur on a 3 parties séparées par des (type de na, na_value,na_label)
        foreach ($array_temp as $element) {
            $array_element = explode("@", $element);

			$array_result[ $array_element[0] ][ $array_element[1] ] = $array_element[2];
        }
		return $array_result;
    }

	 /**
	  * 06/06/11 MMT DE 3rd Axis
	  * asks if the current family/product contains a 3rd axis or not
	  * This will return true even if no value are set for the 3rd axis filter
	  * uses the get_axe3 from edw_function
	  * @return boolean true if a 3rd axis is set for this family
	  */
	 private function has3rdAxis(){

		 //only execute the query once by testing the default value -1
		 if($this->has3rdAxis == -1){
			 $this->has3rdAxis = get_axe3($this->family, $this->product);
		 }
		 return $this->has3rdAxis;
	 }

    /**
	* fonction qui determine la liste des requetes pour chaque niveau d'aggregation
	* @return : tableau contenant pour chaque niveau (index) une query a executé (valeur)
	*
	* 06/06/11 MMT DE 3rd Axis subdivise la function avec  des appels à create_query,
	* maintenant on a une query pour chaque combinaison de NA 1er/3eme axe/KPI selectionnés
	* le tableau de retour est de la forme : ret[NA axe1][Counter][NA axe3] = SQL query
	* dans le cas sans 3eme axe: ret[NA axe1][Counter] = SQL query
	*/
	private function create_queries_list()
    {
		// 15:30 16/07/2009 GHX
		// Ajout de l'id du produit
		// 16:43 28/10/2009 SCT BZ 12349 : aucun résultat affiché sur l'investigation dashboard
        //$module = get_sys_global_parameters("module", null, $this->product);
		$familyInformation = get_gt_info_from_family($this->family, $this->product);

		$network_aggregation_list = $this->network_aggregation_list;
		$counter_aggregation_list = $this->counter_aggregation_list;
		/*on recupere qqs donnees du selecteur*/

		//echo "<pre> network_aggregation_list ";print_r($network_aggregation_list);echo "</pre>";
		//echo "<pre> network_axe3_aggregation_list ";print_r($this->network_axe3_aggregation_list);echo "</pre>";
		$queries = array();
		//pour chacun des niveaux d'aggregation choisis
		foreach ($network_aggregation_list as $network_aggregation => $array_network_aggregation_values)
		{
			//pour chacun des types de compteurs choisis
			foreach ($counter_aggregation_list as $counter_aggregation => $counter_aggregation_values) {
				//pour chacun des compteurs
				foreach ($counter_aggregation_values as $counter_value=>$counter_label) {

					// 06/06/11 MMT DE 3rd Axis on appelle create_query pour chaque combinaison element 1er/3eme axe/kpi

					$counterName = strtolower($counter_label);
					if(!$this->has3rdAxis()){ // pas de 3eme axe, une requète par combinaison 1er axe/KPI
						$newQuery = $this->create_query($familyInformation,
															 $network_aggregation,$array_network_aggregation_values,
															 $counter_aggregation,$counter_value,$counter_label);
						if($newQuery){
							$queries[$network_aggregation][$counterName] = $newQuery;
						}
					} else { // 3eme axe, on multiplie les requète par la selection 3eme axe
						foreach ($this->network_axe3_aggregation_list as $na_axe3 => $nes_axe3) {

							$newQuery = $this->create_query($familyInformation,
																 $network_aggregation,$array_network_aggregation_values,
																 $counter_aggregation,$counter_value,$counter_label,
																 $na_axe3,$nes_axe3);
							if($newQuery){
								$queries[$network_aggregation][$counterName][$na_axe3] = $newQuery;
							}
						}
					}
				}
			}
		}
		return $queries;
    }


	 /**
	  * 6/6/11 MMT DE 3rd axis crée la fonction create_query à partir de create_queries_list
	  * génère une requète SQL pour un NA 1er axe, un KPI/raw et NA 3eme axe optionel definis
	  *
	  * @global <type> $MAX_SELECTION
	  * @param array $familyInformation general family information
	  * @param String $network_aggregation NA 1er axe
	  * @param array $array_network_aggregation_values  Selection NEs 1er axe
	  * @param String $counter_aggregation  type kpi ou raw
	  * @param String $counter_value counter/KPI name
	  * @param String $counter_label counter/KPI Label
	  * @param Sting $axe3 NA 3eme axe, optionel
	  * @param array $axe3_values Selection NEs 3eme axe, optionel
	  * @return String requète SQL, peut être vide
	  */
	 private function create_query($familyInformation,
											 $network_aggregation,$array_network_aggregation_values,
											 $counter_aggregation,$counter_value,$counter_label,
											 $axe3='',$axe3_values='')
	 {
	   $ta_level = $this->selecteur_values['ta_level'];

		 //on cree les requetes
		$query_select = "SELECT " . $network_aggregation . "," . $counter_label . "," . $ta_level;
		// 6/6/11 MMT DE 3rd axis modifie selection 3eme axe optionelle
		if($axe3){
			$query_select .= ",". $axe3;
		}
		// 16:43 28/10/2009 SCT BZ 12349 : aucun résultat affiché sur l'investigation dashboard
		//$nom_table = "edw_".$module."_".$this->family."_".$family_info["axe_gt_id"][0]."_".$counter_aggregation."_".$network_aggregation."_".$ta_level;
		$nom_table = $familyInformation['edw_group_table']."_".$counter_aggregation."_".$network_aggregation;
		// 6/6/11 MMT DE 3rd axis modifie selection 3eme axe optionelle
		if($axe3){
			$nom_table .= "_".$axe3;
		}
		$nom_table .= "_".$ta_level;

		$ret = '';
		//on verifie que le compteur existe sur la table choisie, sinon on ne va pas plus loin
		if (ereg( $this->getGroupTableByCounter($counter_aggregation, $counter_label), $nom_table )) {

			//si on est en bh, on rajoute le champ bh dans la requete
			if (!(strpos($ta_level, '_bh') === false)) {
				$query_select .= ", $nom_table.bh AS bh ";
			}

			$query_from = "\nFROM " . $nom_table;

			// Conditions sur la TA.
			$query_where = "\nWHERE " . $ta_level . "<=" . $this->ta_min_max["date_fin"]
				. " AND " . $ta_level . ">=" . $this->ta_min_max["date_debut"];

			$query_where .= "\n AND ".$counter_label." IS NOT NULL";

			// Condition sur la sélection des NA.
			$query_where_condition_na = '';

			//pour chacun des elements reseaux choisis, on l'ajoute dans la clause where
			$query_where_condition_na  .= "\n AND ".$network_aggregation." IN (";
			foreach ($array_network_aggregation_values as $na_value=>$na_label) {
				$query_where_condition_na .= "'".$na_value."',";

			}
			//on supprime le dernier caractere (virgule)
			$query_where_condition_na = substr($query_where_condition_na, 0,-1);
			$query_where_condition_na .= ")";

			// 6/6/11 MMT DE 3rd axis supprimme la gestion du 'ALL' et remplace par le meme model que pour
			// 1er axe
			if($axe3){
				//pour chacun des elements reseaux choisis, on l'ajoute dans la clause where
				$query_where_condition_na  .= "\n AND ".$axe3." IN (";
				foreach ($axe3_values as $na_value=>$na_label) {
					$query_where_condition_na .= "'".$na_value."',";

				}
				//on supprime le dernier caractere (virgule)
				$query_where_condition_na = substr($query_where_condition_na, 0,-1);
				$query_where_condition_na .= ")";
			}

			// 15:09 16/07/2009 GHX
			// Le ORDER BY se fait sur la date au lieu des données
			// $query_order = "\nORDER BY ".$counter_label." DESC";
			$query_order = "\nORDER BY ".$ta_level." DESC";


			// si le choix du KPI est accompagné par un trigger et un operande, la requete doit calculer les élements réseau qui a la date du selecteur reponde à la contrainte
			if ($this->selecteur_values['filter_id'] != '')
			{
				/*
					24 01 2007 christophe : gestion du nouveau filtre des raw /kpi.

					Il existe plusieurs cas possibles.
					cas 1 :
						l'élément de tri et de filtre sont les mêmes (même type et nom).
					cas 2 :
						ils sont de même type mais différents.
					cas 3 :
						ils sont de types différents.
				*/

				$filter = $this->selecteur_values['filter_id'];
				//la valeur du filtre contient le type (raw/kpi) et le nom du compteur sur lequel on applique ce filtre
				$values = explode("@",$filter);
				$filter_type = $values[0];
				$filter_name = $values[2];

				$filter_operand = $this->selecteur_values['filter_operande'];
				$filter_value = $this->selecteur_values['filter_value'];

				/*****
				*	29/10/2009 BBX : correction des requêtes pour le filtre. BZ 12358
				*****/

				// Cas 1 & Cas 2
				if($counter_aggregation == $filter_type)
				{
					// On filtre simplement sur le raw/kpi choisi
					$query_where .= " AND " . $filter_name . " " . $filter_operand . " " . $filter_value
					. $query_where_condition_na;
				}
				// Cas 3
				else
				{
					/*	Le nom de la table change car les types sont différents.
						> on remplace dans le nom de la table le type de l'élément de tri par celui de la condition
					*/
					$nom_table_new = str_replace('_'.$counter_aggregation.'_','_'.$filter_type.'_',$nom_table);

					$query_where .= " AND " . $network_aggregation
					. " IN (SELECT " . $network_aggregation
					. " FROM " . $nom_table_new
					. " WHERE " . $filter_name . " " . $filter_operand. " " . $filter_value
					. " AND " . $ta_level . "=" . $this->ta_min_max["date_fin"]
					. $query_where_condition_na . " )";
				}

				/*****
				*	FIN correction BBX BZ 12358
				*****/
			}
			else
			{
				$query_where .= $query_where_condition_na;
			}

			global $MAX_SELECTION;

			$ret = $query_select . $query_from . $query_where. $query_order;

			// 6/6/11 MMT DE 3rd axis supprime ancienne gestion 3eme axe et retourne la requete

		}

		return $ret;
	}


    /*
	* fonction qui execute les requetes crees par la methode create_queries_list
	* @return : tableau _resultat dont les dimensions sont : na puis na_value puis ta_value. la valeur de ce tableau est la valeur du KPI
	*
	* 05/06/2009 SPS correction bug 9963 : limitation du nombre de resultat
	* 06/06/2011 MMT DE 3rd axis separe la fonction en deux: ajout de execute_query
	*/
    private function execute_queries()
    {
		global $MAX_SELECTION;

		//on recupere le nombre de compteurs
		$tab_counters = explode("|s|",$this->counter_selection);
		$nb_counters = count($tab_counters);
		//nombre maximum d'elements du 3eme axe
		// 6/6/11 MMT DE 3rd axis  renome variables generiques
		$nb_elem_max = floor($MAX_SELECTION / $nb_counters);
		$list_elem = array();

		//on recupere la liste des requetes crees precedemment
		$queries_list = $this->queries_list;

		// 10:07 12/08/2009 GHX
		$numberResultDisplay = 0;
		$numberResultTotal = 0;

		$ret = array();

		if ($queries_list) {
			//pour chacun des niveaux d'aggregation reseau
			foreach ($queries_list as $na => $counterValues) {
				foreach ($counterValues as $counterName => $values) {
					// 6/6/11 MMT DE 3rd axis dans le cas 3eme axe, on boucle sur tous les elements selectionnés
					if($this->has3rdAxis()){
						foreach ($values as $na_axe3 => $req) {
							$this->execute_query($req,$counterName,$ret,$list_elem,$nb_elem_max,$numberResultDisplay,$numberResultTotal,$na,$na_axe3);
						}
					} else {
						$req = $values;
						$this->execute_query($req,$counterName,$ret,$list_elem,$nb_elem_max,$numberResultDisplay,$numberResultTotal,$na);
					}
				}
			}

			// 10:29 12/08/2009 GHX
			// Correction du BZ 10605
			if ( $numberResultDisplay < $numberResultTotal )
			{
				echo '<span class="msgOrderByAlpha">'.__T('U_INVESTIGATION_DASHBOARD_MAXIMUM_VALUE_EXCEEDED', $MAX_SELECTION).'</span>';
			}
			return $ret;
		}
		else return null;
    }


	 /**
	  * 6/6/11 MMT DE 3rd axis fonction créée à partir de execute_queries
	  * Execute the given SQL request for the NA 1st axe, counter and optional 3rd axis NA
	  * The results are populated in the given reference variables
	  * @param String $req SQL request
	  * @param String $counterName  counter name
	  * @param array BY REFERENCE $array_result result array to be populated byt the query result
	  * @param array BY REFERENCE $list_elem list of existing elements from already executed requests
	  * @param int $nb_elem_max maximum number of displayed elements
	  * @param int BY REFERENCE $numberResultDisplay number of results displayed, updated via query results
	  * @param int BY REFERENCE $numberResultTotal total number of results,  updated via query results
	  * @param String $na 1st axis NA code
	  * @param String $na_axe3 3rd axis NA code, optionel
	  */
	 private function execute_query($req,$counterName,
											  &$array_result,
											  &$list_elem,$nb_elem_max,
											  &$numberResultDisplay,&$numberResultTotal,
											  $na,$na_axe3=''){

		//on execute la requete
		//6/6/11 MMT DE 3rd axis replace le tableau 'req' par 3 variables : $req, $counterName et $na_axe3
		$res = $this->database->getAll( $req );
		$nombre_resultat = count($res);

		//si on a un resultat, on stocke le resultat, sinon on ne fait rien
		if ($nombre_resultat > 0) {

			for ($i = 0;$i < $nombre_resultat;$i++) {
				//on recupere ensuite les valeurs

				$na_value = $res[$i][$na];
				$time_value = $res[$i][ $this->selecteur_values['ta_level'] ];
				//6/6/11 MMT DE 3rd axis replace le tableau 'req' par 3 variables : $req, $counterName et $na_axe3
				$counter_value = $res[$i][ $counterName ];

				//si on a une valeur pour le compteur selectionne, on va plus loin
				// 17:29 13/08/2009 GHX
				// Correction du BZ 6758
				// Modification de la condition, la valeur peut être négative ou valoir zéro
				if ($counter_value != null) {

					/* 05/06/2009 SPS correction bug 9963 */
					//si on est sur un 3eme axe, et si on ne depasse pas le nombre d'elements max
					//6/6/11 MMT DE 3rd axis replace le tableau 'req' par 3 variables : $req, $counterName et $na_axe3
					if ($na_axe3)
					{
						//on recupere le resultat venant de la requete
						$axe3_value = $res[$i][$na_axe3];
						$axe3_full = $na_axe3."@".$axe3_value."@".$axe3_value;
						// 15:07 16/07/2009 GHX
						// Récupère les X premiers éléments à afficher
						if ( count($list_elem) < $nb_elem_max )
						{
							$list_elem[] = $na_value."@".$axe3_value;
						}

						if ( in_array($na_value."@".$axe3_value, $list_elem) )
						{
							$numberResultDisplay++;
							//on stocke le type de 3eme axe, le label et la valeur de celui ci
							//ce tableau servira a connaitre toutes les valeurs de 3eme axe possible
							//exple : tos@ALL@HTTP
							if ( !self::isInArray($axe3_full, $array_result['axe3']) ) {
								$array_result['axe3'][] = $axe3_full;
							}

							//on stocke la valeur du compteur
							//6/6/11 MMT DE 3rd axis replace le tableau 'req' par 3 variables : $req, $counterName et $na_axe3
							$array_result['data'][$na][$na_value][ $counterName ][$time_value][$axe3_value] = $counter_value;

							//on stocke les couples (element reseau - axe3)
							if ( !self::isInArray($na_value."@".$axe3_full, $array_result['elem_axe3']) ) {
								$array_result['elem_axe3'][] = $na_value."@".$axe3_full;
							}
						}
					}

					//si on n'est pas en axe3
					//6/6/11 MMT DE 3rd axis replace le tableau 'req' par 3 variables : $req, $counterName et $na_axe3
					if(!$na_axe3)
					{
						// 15:50 16/07/2009 GHX
						// Correction du BZ 10637 [REC][T&A Cb 5.0][Investigation Dashboard] :  on affiche le nombre maximum de sélection + 1
						// Récupère les X premiers éléments à afficher
						if ( count($list_elem) < $nb_elem_max )
						{
							$list_elem[] = $na_value;
						}

						if ( in_array($na_value, $list_elem) )
						{
							$numberResultDisplay++;
							//6/6/11 MMT DE 3rd axis replace le tableau 'req' par 3 variables : $req, $counterName et $na_axe3
							$array_result['data'][$na][$na_value][ $counterName ][$time_value] = $counter_value;
						}
					}

					//si on a de la bh, on cree un tableau specifique, sur le meme modele que le precedent
					if (!(strpos($this->selecteur_values['ta_level'], '_bh') === false)) {
						//6/6/11 MMT DE 3rd axis replace le tableau 'req' par 3 variables : $req, $counterName et $na_axe3
						if ($na_axe3) {
							$array_result['bh'][$na][$na_value][ $counterName ] [$time_value][$axe3_value] = $res[$i]['bh'];
						}
						else {
							$array_result['bh'][$na][$na_value][ $counterName ] [$time_value] = $res[$i]['bh'];
						}
					}

					$numberResultTotal++;

				}
				//si on n'a aucune valeur, on ne stocke rien
			}
		}
	}

    /*
	* fonction qui a partir du tableau de result des requetes et du tableau complet de la ta génère un tableau de resultat complet avec des valeurs NULL pour les elements ta qui ne sont pas en base
	*
	*	05/06/2009 SPS correction bug 9963 : limitation du nombre de resultats
	*
	*	11:07 13/08/2009 GHX
	*		- Modification de la fonction pour corriger le BZ 11013
	*
	* @return : tableau resultat complet
	*/
    private function create_table_result()
    {
		$result_queries = $this->result_queries;
		$all_ta = $this->all_ta;
		$network_aggregation_list = $this->network_aggregation_list;
		$counter_aggregation_list = $this->counter_aggregation_list;

		$result_queries_data = $result_queries['data'];
		$result_queries_bh = $result_queries['bh'];
		$result_queries_axe3 = $result_queries['axe3'];
		$result_queries_elem_axe3 = $result_queries['elem_axe3'];

     	//on recupere le label de chacun des compteurs
		foreach($counter_aggregation_list as $counter_aggregation => $array_counters  ) {
			foreach($array_counters as $id_counter => $counter_label) {
				// 11:03 13/08/2009 GXH
				// Correction du BZ 11013
				// Modification de la structure du tableau
				$tab_counter[strtolower($counter_label)] = $this->getLabelRawKpi($counter_aggregation, $id_counter);
			}
		}

		//on recupere les valeurs de l'axe 3
		if (count($result_queries_axe3) > 0) {
			foreach($result_queries_axe3 as $axe3_data ) {
				//$axe3_data est du type tos@ALL@HTTP
				$d = explode("@",$axe3_data);
				//valeur de l'axe 3, par exple HTTP
				$axe3_value = $d[2];
				$tab_axe3[$axe3_data] = $axe3_value;
			}
		}

		//si on a des donnees
		if ( count($result_queries_data) > 0) {

			foreach ($network_aggregation_list as $na => $nel) {

				foreach($nel as $na_label => $na_value) {
					//pour chacun des compteurs
					foreach( $tab_counter as $counter => $counterLabel ) {

						//on compte le nombre de resultat par element reseau et par compteur
						$taille_nel = count($result_queries_data[$na][$na_label][$counter]);

						//si on a des donnees, on va plus loin
						if ($taille_nel > 0) {

							//si on est en 3eme axe
							if (count($tab_axe3) > 0) {

								/*05/06/2009 SPS correction bug 9963 */
								//on recupere les elements reseaux que l'on a garde pour le 3eme axe
								foreach($result_queries_elem_axe3 as $elem_axe3) {
									$elem = explode("@",$elem_axe3);

									$nel_axe3 = $elem[0];
									//axe3 avec les parametres du type tos@ALL@HTTP
									// 10:56 13/08/2009 GHX
									// Correction du BZ 11013
									$axe3 = $elem[1]."@".$elem[2]."@".$this->getLabelThirdAxis($elem[1], $elem[3]);
									//valeur du 3eme axe exple: HTTP
									$axe3_value = $elem[3];

									//pour toutes les dates
									foreach ($all_ta as $ta_value) {

										//si l'element reseau est bien dans le tableau des elements d'axe3
										if ($nel_axe3 == $na_label) {

											//si on est en bh
											if(count($result_queries_bh) > 0) {
												$array_result[$na_value][$counterLabel."@".$axe3][$ta_value."@".$result_queries_bh[$na][$na_label][$counter][$ta_value]] = $result_queries_data[$na][$na_label][$counter][$ta_value][$axe3_value];
											}
											else {
												$array_result[$na_value][$counterLabel."@".$axe3][$ta_value] = $result_queries_data[$na][$na_label][$counter][$ta_value][$axe3_value];
											}
										}
									}

								}

							}
							else {
								//en mode "normal" (sans 3eme axe)
								//pour toutes les dates
								foreach ($all_ta as $ta_value) {

									if(count($result_queries_bh) > 0) {
										$array_result[$na_value][$counterLabel][$ta_value."@".$result_queries_bh[$na][$na_label][$counter][$ta_value]] = $result_queries_data[$na][$na_label][$counter][$ta_value];
									}
									else {
										$array_result[$na_value][$counterLabel][$ta_value] = $result_queries_data[$na][$na_label][$counter][$ta_value];
									}

								}
							}
						}
					}
				}
			}
			return $array_result;
		}
		else {
			return null;
		}
    }


	/**
	 * on genere le graph (methode adaptee de la methode d'affichage de la classe chartFromXML)
	 * @param mixed $data tableau de donnees
	 * @param string $filename chemin du fichier xml
	 * @param string $title titre du graph
	 * @return string html a afficher
	 * @see chartFromXML
	 * */
	public function generateGraph($data, $filename, $title) {

		//on recupere l'id du graph (id calcule aleatoirement)
		$file_id  = substr(basename($filename),0,-4);
		//a partir du chemin du xml, on determine celui du png final
		$file_png  = ereg_replace(".xml",".png",basename($filename));

		$chart = new chartFromXML( $filename );
		$chart->setBaseUrl(NIVEAU_0.'/png_file/');
		$chart->setBaseDir(REP_PHYSIQUE_NIVEAU_0.'png_file/');
		$chart->setHTMLURL(NIVEAU_0);
		// on charge les valeurs par défaut (depuis un autre fichier XML)
		$chart->loadDefaultXML(MOD_CHARTFROMXML . "class/chart_default.xml");
		//titre du graph
		$gtm_title = '<div id="gtm_title">
				<div id="gtm_infos" class="gtmTitleBack">
					<img class="imgTitle" src="'.NIVEAU_0.'images/graph/puce_graph.gif" alt="arrow"/>
					<span class="dashTitle">'.$title.'</span>
				</div>';

		//Barre d'icones
		$gtm_title .= '	<div id="gtm_icons" class="gtmIcons">
		<ul>';
		//lien vers 'data information'
		$gtm_buttons['infos'] = array('link' => "open_window('".NIVEAU_0."/dashboard_investigation/php/investigation_info.php?id_product=".$this->product."','comment','yes','yes',950,500);return false;", 'img' => NIVEAU_0."images/graph/btn_info.gif", 'msg' => __T('U_DATA_INFORMATION_FIEDSET_DATA_INFORMATION'));

		$caddy_params = array(NIVEAU_0, $_SESSION['id_user'], $title, 'investigation_dashboard', $title, $file_png, $file_id, '');

		//lien vers le caddy
		// 02/12/2009 BBX
		// On remplace caddy par cart. BZ 13095
		$gtm_buttons['caddy'] = array('link' => "caddy_update(".implode(",", array_map(array($this, 'labelizeValue'), $caddy_params)).")" , 'img' => NIVEAU_0."images/graph/btn_cart.gif", 'msg' => "Add to the cart");


		//si on a des donnees, on affiche le lien vers l'export excel
		if (count($data) > 0) {
			// 23/02/2011 MMT bz 18003 : utilisation de ouvrir_fenetre pour l'export Xls
			$gtm_title .= "<li><a onclick=\"javascript:ouvrir_fenetre('".NIVEAU_0."dashboard_investigation/php/export_excel_from_graph.php?file_id=".$file_id."','popup_toto','no','no',250,30);\">".'<img src="'.NIVEAU_0."images/graph/btn_excel.gif".'" onmouseout="kill()" onmouseover="popalt(\''.__T('U_TOOLTIP_CADDY_EXCEL_EXPORT').'\'); style.cursor=\'pointer\';"/></a></li>';
		}

		foreach ($gtm_buttons as $key => $value) {
			if ($key != 'excel'  && count($value) > 0) {
				$gtm_title .= '<li><img onclick="'.$value['link'].'" onmouseout="kill()" onmouseover="popalt(\''.$value['msg'].'\'); style.cursor=\'pointer\';" src="'.$value['img'].'"/></li>';
			}
		}

		$gtm_title .= '	</ul>

			</div>
		</div>';

		// maj 28/08/2009 - MPR : Ajout d'un message indiquant que le caddy a été mis à jour
		$gtm_title .= '	<div class="updateCaddy" id="updateCaddy_'.$file_id.'" style="display:none">'.__T('U_CART_UPDATED').'</div>';

		//si on a des donnees, on affiche l'image
		if (count($data) > 0) {

			$gtm_img  = '<div id="gtm_picture_'.$file_id.'" class="imgGraph contourImage fondGraph">';
			//on cree l'image et les balises maps
			$gtm_img .= $chart->getHTML($file_png);
			$gtm_img .= '</div>';

			// Une fois l'image cree, on récupère sa taille
			$image_file = ereg_replace(".xml",".png",$filename);
			$png_size = getimagesize($image_file);

			$png_width	= $png_size[0];
		}
		else {
			//si on n'a aucune donnee, on affiche un message d'erreur a l'interieur du graph
			$png_width = 700;
			$gtm_img  = '<div id="gtm_picture" class="imgGraph contourImage" style="width:'.$png_width.'px">
				<div class="graph-no-data" style="background: no-repeat 10px url('.NIVEAU_0.'images/graph/gtm_error.png) #fde6e6;">'.__T('U_INVESTIGATION_DASHBOARD_NO_DATA_FOUND').'</div>
				</div>';
		}
		//on construit le html a afficher
		$png_frame .= '<div class="gtm" style="width:'.$png_width.'px">'.$gtm_title.$gtm_img.'</div>';

		return $png_frame;
	}


	/**
	 * on va chercher le debut du nom des tables contenant les compteurs
	 * @param string $counter_type type du compteur (raw/kpi)
	 * @param string $counter_name nom du compteur
	 * @return string/null le debut du nom des tables
	 * */
	private function getGroupTableByCounter($counter_type, $counter_name) {
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$db = Database::getConnection($this->product);
		$query = '';
		if ($counter_type == 'kpi') {
			$query="
			SELECT edw_group_table
			FROM sys_definition_kpi
			WHERE kpi_name = '$counter_name'";
		}

		// 13:38 16/07/2009 GHX
		// Modification du nom de la colonne sur laquelle on fait la condition
		if ($counter_type == 'raw') {
			$query="
			SELECT edw_group_table
			FROM sys_field_reference
			WHERE edw_target_field_name = '$counter_name'";
		}

		$resultat = $db->getOne($query);
		if ($resultat) return $resultat;
		else return null;
	}



	/**
	 * Permet de "labeliser" une valeur cad de l'entourer de quotes
	 *
	 * @param numeric $value la valeur à labeliser
	 * @return string la valeur labelisée
	 */
	private function labelizeValue($value){
		return "'".$value."'";
	}

	/**
	 *	fonction qui verifie que la valeur est presente dans le tableau
	 *	@param string $value la valeur recherchee
	 *	@param mixed $array tableau dans lequel on cherche
	 *	@return bool resultat de la recherche (trouve ou non)
	 */
	private function isInArray($value, $array) {
		$found = 0;
		if (count($array) > 0) {
			foreach($array as $d) {
				if ($d == $value) {
					$found = 1;
				}
			}
		}
		return $found;
	}

	/**
	 * Retourn le label d'un élément réseau par rapport à son niveau d'aggrégation et ca valeur
	 *
	 *	10:57 13/08/2009 GHX
	 *		- Ajout de la fonction pour corriger le BZ 11013
	 *
	 * @param string $na
	 * @param string $value
	 * @return string
	 */
	private function getLabelThirdAxis ( $na, $value )
	{
		if ( array_key_exists($na.'@'.$value, $this->labelThirdAxis) )
		{
			return $this->labelThirdAxis[$na.'@'.$value];
		}

		$label = $this->database->getOne("SELECT eor_label FROM edw_object_ref WHERE eor_id = '{$value}' AND eor_obj_type = '{$na}'");

		if ( empty($label) )
		{
			$label = '('.$value.')';
		}

		$this->labelThirdAxis[$na.'@'.$value] = $label;

		return $label;
	} // End function getLabelThirdAxis

	/**
	 * Retourne le label d'un raw ou kpi en fonction de son identifiant
	 *
	 *	11:02 13/08/2009 GHX
	 *		- Ajout de la fonction pour la correction du BZ 11013
	 *
	 * @param string $type type de l'élément raw ou kpi
	 * @param string $id identifiant
	 * @return string
	 */
	private function getLabelRawKpi ( $type, $id )
	{
		if ( array_key_exists($type.'@'.$id, $this->labelRawKpi) )
		{
			return $this->labelRawKpi[$type.'@'.$id];
		}

		switch ( $type )
		{
			case 'raw':
				$sql = "SELECT edw_field_name_label FROM sys_field_reference WHERE id_ligne = '{$id}'";
				break;
			case 'kpi':
				$sql = "SELECT kpi_label FROM sys_definition_kpi WHERE id_ligne = '{$id}'";
				break;
		}
		$label = $this->database->getOne($sql);

		if ( $this->database->getNumRows() == 0 )
		{
			$label = $id;
		}

		$this->labelRawKpi[$type.'@'.id] = $label;

		return $label;
	} // End function getLabelRawKpi
}
?>
