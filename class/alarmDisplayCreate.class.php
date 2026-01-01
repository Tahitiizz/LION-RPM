<?
/*
 * @cb514@
 *
 *  04/07/2011 NSE bz 22870 : mauvaise légende dans Gis 3D, ajout de urlencode
 *	22/11/2011 ACS BZ 21554 does not work with SGSN and All PLMNs
 *	09/12/2011 ACS Mantis 837 DE HTTPS support
 *  20/09/2012 MMT DE 5.3 Delete Topology - ajout jointure sur edw_object_ref pour ne pas afficher les elements supprimés
 *  16/11/2012 MMT bz 30408 alarm history sometimes runs forever 
 *
 */
?><?
/*
 * @cb504@
 *
 *  16/08/2010 NSE DE Firefox bz 16918 : le - ne fonctionne pas et les cellules ne sont pas alignées
 *  14/09/2010 OJT : Correction bz17819, ajout de la propriété title aux images
 *  03/02/2011 NSE bz 20445 : tri des alarmes par ordre aplhabétique
 *  24/2/2011 MMT bz 19628 : groupe par severité et nom par rank + correction sur 20445
 *
 */
?><?
/*
*	@cb41000@
*
*	03/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	- maj 03/12/2008 - SLC - gestion multi-produit, suppression de $database_connection
*	- maj 16/01/2009 - MPR : Ajout du paramètre produit dans l'appel de la fonction get_sys_global_parameters
*	- maj 02/06/2009  MPR : Ajotu du produit dans les paramètres du GIS
*
*	04/08/2009 GHX
*		- Correction du BZ 10639
*	31/08/2009
*		- Correction du BZ 11272
*	09/11/2009 GHX
*		- Correction du BZ 12502  [REC][T&A CORE 5.0][Alarm MGNT] Message d'erreur dans la page des alarmes.
*	23/02/2010 NSE
*		- remplacement des fonctions GetweekFromAcurioDay et getLastDayFromWeek par leur équivalent de la classe Date
*
*  17/08/2010 MMT
*    - bz 16749 Firefox compatibility use getAttribute for popalt(alt_on_over)
*
*  13/09/2010 MMT bz 16749, manque fin de parenthèze dans l'appel de popalt
*
*  01/08/2011 MMT Bz 22887 ajout du seuil dans le nom de l'id pour differenciation des ID des +/-
*  20/12/2011 ACS BZ 25213 change family on Slave loose https
 */
?>
<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	- maj 15/04/2008 Benjamin : affichage des dates au format US
*	- maj 15/04/2008 Benjamin : affectation de la variable $largeur_zone_heure pour pouvoir modifier la largeur de la zone des heures. De plus celle-ci était trop petite (520px à l'origine)
*	- maj 10/03/2008 christophe : ajout d'une nouvelle condition pour gérer le cas de order by capture time.
*	- maj 10/03/2008 christophe : ajout d'une nouvelle subquery pour gérer le cas de order by capture time.
*	- maj 10/03/2008 christophe : on tient compte de la variable order_on pour faire un order by sur calculation_time ou ta_value.
*	- maj 10/03/2008 christophe : ajout d'une requête pour le order by sur la TA de capture.
*	- maj 10/03/2008 christophe : création de la méthode setOrderOn : permet d'initialiser la variable order_on
*	- ajout 10/03/2008 christophe : ajout de la variable $order_on.
*	- maj 16/11/2007, benoit : le GIS s'ouvre désormais dans une pop-up et non plus dans un div. On remplace donc la fonction 'top.showGIS()'    par la fonction d'ouverture de pop-up
*	- maj 16/11/2007, benoit : utilisation de la fonction '__T()' pour définir le tooltip du bouton du GIS
*	- maj 17:17 25/01/2008 - maxime : ajout d'un lien vers GIS 3D ( Google Earth )
*	- maj 26/05/2008 - maxime : On remplace l'url export_gearth_dash_alarm.php par export_file.php // Fichier intermédiaire permettant d'afficher un loading
*	- maj 10/06/2008 Benjamin : modification de la requête de récupération des alarmes. BZ6634
*	- maj 10/06/2008 Benjamin : détermination de la dernière heure. BZ6634
*	- maj 10/06/2008 Benjamin : correction des dates au format US : prise en charges des cas mois et week
*
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- 24/08/2007 christophe : ajout du timestamp dans l'url de collapse / axpand all.
*	- 24/07/2007 - Modif. benoit : intégration des modifications dans le gis pour son affichage dans toutes les familles : création getFamilyOfNa / getListOfNetworkElementQuery.
*	- 24/07/2007 christophe : on affiche seulment les résultats des éléments réseaux sélectionnés.
*	- 23/07/2007 christophe : ajout des méthodes  setListOfNetworkElement et getListOfNetworkElementQuery
*	- 16/07/2007 christophe : intégration de la sélection des NA.
*	- 02/08/2007 christophe :Ajout de la condition sur la sélection des éléments réseaux pour la requête qui affiche le graphe.
*
*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?
/*
*	@cb21000@
*
*	08/12/2006 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.00
*
*	- maj 19 01 2007 christophe : branchement de la nouvelle version du gis.
*	- maj 11 12 2006 christophe : amélioration de la vitesse d'affichage sur lécran 'alarm management'.
*/
?>
<?
/*
*	@cb20040@
*
*	30/11/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.40
*
*	- 01 12 2006 christophe : correction >> quand on choisissait une TA dans Alarm Management, le menu contextuel disparassait, seul 'refresh subsistait'.
*/
?>
<?
/*
*	@cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
*/
?>
<?
/*
*	@cb2001_iu2030_111006@
*
*	11/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.1
*
*	Parser version iu_2.0.3.0
*/
?>
<?
/*
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?
	/*
		Création : christophe le 01/08/2006
		Cette classe permet en fonction des paramètres de :
		- créer l'affichage des alarmes sur les écrans alarm management,alarm history ou Top/worst Cell List.
		- maj 23 08 2006  : expand / collapse sont toujours affiché en même temps. ligne 995/1000
		- maj 18 09 2006 christophe :
			> les couleurs de fond des différents seuils d'alarme viennent de sys_global_parameters
			> changement du style d'affichage du choix du timestamp de calcul en mode alarm management.
			> affichage du lien vers le gis pour la fmaille proncipale.
		- 29 09 2006 christophe :
			> modification des requêtes d'affichage : si une alarme est supprimée, ses résultats ne sont plus affichés.
			> la ta_value est passée en mode par alarme.
		- 11 10 2006 christophe : modif displayHourSelect() , ajout de '...where calculation_time is not null...'
		- maj 25 10 2006 , christophe : correction d'un bug > si gis = 0 & gis_alarm=1, le gis n'est pas activé.
		- maj 20 11 2006 christophe :
			> correction orthographe minors > minor.
			> quand il y a un autorefresh, les ta sélectionnées en alarm management sont conservées (changement dans la méthode displayTaSelection, le formulaire
			avec method=post devient method=get).
		- maj 05/04/2007 Gwénaël : modification pour prendre en compte le troisième axe
			- modificition de la fonction get_na_label (anciennement getNaLabel, le nom a été changé pour ne par préter à confusion avec celle du fichier edw_function.php)
			- et modif aux endroits où est appelé cette fonction
		- maj 13/04/2007 Gwénaël
			changement de séparateur |@| par |s| sauf pour le GIS
			>> prise en compte du troisième axe dans les liens vers les dashboards << SUPRESSION DES MODIFS en commentaires !!!!!!!!
	*/

class alarmDisplayCreate
{
    /** Définition des constante de seuils */
    const CRITICAL_TRESHOLD = 'critical';
    const MAJOR_TRESHOLD = 'major';
    const MINOR_TRESHOLD = 'minor';

    /** @var Array Liste des seuils d'alarmes existants */
    protected $_listSeuils = array( self::CRITICAL_TRESHOLD , self::MAJOR_TRESHOLD, self::MINOR_TRESHOLD );

	/*
		Paramètre du constructeur de classe.
		$database_connection		paramètres de connexion à la base de données. --> OBSOLETE
		$na					network_aggregation. Cela peut-être une na spécifique , si toutes mettre 'all'.
							Ce paramètre est toujours à all si c'est de l'affichage (on met une na spécifique lors de l'envoi des PDF).
		$ta					time_aggregation, nom de la ta, si toutes mettre 'all'.
							Si il y a plusieurs TA, elles sont séparées par des ','  exemple 'hour,day'
							Ce paramètre est toujours à all si c'est de l'affichage (on met une ta spécifique lors de l'envoi des PDF).
		$ta_value				Valeur de la time aggregation.
		$display 				Affichage expand all ou collapse (valeur = 'block' ou none)
		$mode				'management', 'condense' ou 'twcl'
		$sous_mode			pour 'management' > 'elem_reseau' / 'condense'

		$product				id du produit en cours
	*/

	var $id_user;
	var $na;
	var $ta;
	var $ta_value;
	var $display;
	var $mode;
	var $sous_mode;
	var $timestamp;
	var $query_select;
	var $query_from;
	var $query_where;
	var $query_order_by;
	var $period;
    protected $product;

	/*
		Tableau php contenant la liste des éléments affichés.
		Structure du tableau :
			$tab_data_seuil[seuil]->array[nom_colonne]
	*/
	var $tab_data_seuil;

    protected $nb_resultats_total_seuil;

	/**
	* ajout 10/03/2008 christophe : ajout de la variable $order_on
	* définit l'ordre temporel de tri des résultats d'alarmes
	*
	* @since cb4.0.0.00
	* @var string
	*/
	protected $order_on;


	/*
		Tableau contenant les données du graph
	*/
	var $graph_data;

	var $list_of_network_element; // liste des éléments réseaux à afficher.

	// 11/12/2008 - SLC - ajout du parametre $period qui n'était instantié nulle part, malgrès son utilisation à l'intérieur de la classe  :-/
	function  __construct( $database_connection, $na, $ta, $ta_value, $display, $mode, $sous_mode, $timestamp, $product = '', $period = '')
	{

		// Permet d'activer / désactiver l'affichage du  mode débug.
		$this->debug = get_sys_debug('alarm_management_and_history');
		$this->debug_too_much_result = get_sys_debug('alarm_too_much_result');

		$this->na			= $na;
		$this->ta			= $ta;
		$this->ta_value		= $ta_value;
		$this->display		= $display;
		$this->mode         = $mode;
		$this->sous_mode	= $sous_mode;
		$this->timestamp	= $timestamp;
		$this->period 		= $period;

		$this->separateur_values 	= "||";
		$this->separateur_elements 	= "|s|";
		
        // Initialisation du nombre de resultat pour chaque seuil
        foreach( $this->_listSeuils as $oneSeuil )
        {
            $this->nb_resultats_total_seuil[$oneSeuil] = 0;
        }

		// 02/12/2008 - SLC - gestion multi-produit
		// 09/12/2011 ACS Mantis 837 DE HTTPS support
		$this->product = $product;
		$this->db = DataBase::getConnection( $this->product );

		//__debug($timestamp,'$timestamp');

		if($this->debug > 0){
			$debug = "<div class='texteGris'>";
			$debug .= "Time aggregation : ".$this->ta. ", valeur : ".$this->ta_value." - ";
			$debug .= "<br>mode : ".$this->mode. ", sous_mode : ".$this->sous_mode." - ";
			$debug .= "display : ".$this->display." - ";
			$debug .= "<br>timestamp : ".$this->timestamp;
			$debug .= "</div>";
			__debug($debug);
			__debug($_SESSION["selecteur_general_values"]["list_of_na"],'$_SESSION["selecteur_general_values"]["list_of_na"]');
			__debug($_SESSION["network_element_preferences"],'$_SESSION["network_element_preferences"]');
		}

		// Paramètres de la table sys_global_parameters.
		// maj 16/01/2009 - MPR : Ajout du paramètre produit dans l'appel de la fonction get_sys_global_parameters
		// maj 10/06/2011 - MPR : Suppression des variables $this->gis et this->gis_alarm
        //                      Utilisation du GisModel
		$this->dashboard_alarm	= get_sys_global_parameters("dashboard_alarm");		// si = 1 alors on affiche l'icône de liaison vers les dashboards.
		$this->trouble_ticket 	= get_sys_global_parameters("trouble_ticket_alarme");	// si = 1 alors on affiche l'icône de liaison vers les trouble ticket.

		// Famille principale (utile pour le gis.)
		$this->main_family 		= get_main_family($this->product); // cette fonction se trouve dans php/edw_function_family.php

		// 24/07/2007 - Modif. benoit : on liste les na de la famille principale (sert pour l'affichage du gis)
		$this->na_in_main_family	= getNaLabelList('na', $this->main_family,$this->product);
		$this->list_of_na_main_family	= array_keys($this->na_in_main_family[$this->main_family]);

		// Paramètrage des couleurs des alarmes.
		$this->alarmColor[ self::CRITICAL_TRESHOLD ]	= get_sys_global_parameters("alarm_critical_color");
		$this->alarmColor[ self::MAJOR_TRESHOLD ]	= get_sys_global_parameters("alarm_major_color");
		$this->alarmColor[ self::MINOR_TRESHOLD ]	= get_sys_global_parameters("alarm_minor_color");

		// Tableau de session contenant toutes les requêtes d'affichage.
		if (isset($_SESSION['queries']))
			unset($_SESSION['queries']);

		/*
			Url de redirection.
			(Quand le selecteur est affiché, des paramètres sont ajouté à l'url donc il y a plusieurs '?', voilà pourquoi pour éviter tout problème je fais ça.)
		*/
		$url_redirection = explode("?",$_SESSION["url_alarme_courante"]);
		$this->url_redirection = $url_redirection[0]."?".$url_redirection[1];


		// Paramètres nécessaires à l'appel du GIS.
		$this->module_application = get_sys_global_parameters('module','', $this->product);

		// 23/07/2007 christophe ajout d'un paramètre list_of_network_elements.
		$this->list_of_network_element = ''; // initialisé via un setter.
	}

	/*
		23/07/2007 christophe
		Setter de $this->list_of_network_elements
	*/
	function setListOfNetworkElement($liste)
	{
		$this->list_of_network_element = $liste;
	}


	/**
	* Retourne la famille à laquelle appartient la NA $na passée en paramètre :
	* si elle est présente dans la famille principale
	*@param $na niveau d'agrégation
	*/
	function getFamilyOfNa($na)
	{
		$family_return = '';
		$liste_na_main_family_tab = $this->na_in_main_family[$this->main_family];

		if (isset($liste_na_main_family_tab[$na])) {
			// Si la NA est présente dans la famille principale, on retourne la fmaille principale.
			$family_return = $this->main_family;

		} else {
			// Sinon on vat chercher la famille de la NA
			$q = " SELECT family FROM sys_definition_network_agregation WHERE agregation='$na' ";
			$row = $this->db->getrow($q);
			if ($row > 0)
				$family_return = $row['family'];
		}
		return $family_return;
	}

	/**
	* Retourne la liste des NA enfants d'une NA donnée
	*@param $na niveau d'agrégation
	*@param $family fmaille de $na
	*/
	function getListeNaEnfant($na, $family)
	{
		$tab_liste_na_enfant = array();
		$q = "
			SELECT DISTINCT level_source FROM sys_definition_network_agregation
				WHERE family='$family'
				AND agregation='$na'
				OR agregation IN
				(
					SELECT level_source
						FROM sys_definition_network_agregation
						WHERE family='$family'
						AND agregation='$na'
				)
		";
		$result = $this->db->getall($q);
		if ($result)
			foreach ($result as $row)
				$tab_liste_na_enfant[$row['level_source']] = $row['level_source'];

		return $tab_liste_na_enfant;
	}

	/**
	*23/07/2007 christophe
	*Retourne une chaine de condition à inclure dans la partie 'Where' de
	*la requête listant la slite des résultats des alarmes à afficher.
	*On vat créer 2 chaines de condition :
	*- une pour sélectionner les éléments que l'utilisateur a coché.
	*- une pour afficher tous les éléments enfants des éléments réseaux que l'utilisateur a choisit.
	*> si l'utilisateur a sélectionné le plus haut niveau de la famille principale, la chaine retournée sera vide
	*car on doit sélectionner tous les éléments réseaux.
	*
	*@param $liste chaine de caractère contenant la liste des éléments réseaux sélectionnéx via l'interface 'network element selection'.
	*Le format de cette chaine est na@na_value@na_label@1 ou 0|na@na_value@na_label@1 ou 0...
	*| : séparateur entre 2 éléments réseaux.;
	*@ : séparateur de sinformations sur un élément réseau :
	*	na : niveau d'agrégation de l'élément réseau
	*	na_value : valeur de l'élément réseau
	*	na_label : label de l'élément réseau
	*	0 ou 1 : ce paramètre n'est pas obligatoirement présent, si = 1 cela
	*	veut dire que l'on doit afficher les éléments réseaux 'enfants' de cet élément, 0 sinon.
	*/
	// 09/12/2008 - slc - avec le nouveau module Network Element Selector, la liste est formattée comme suit :
	// 22/11/2011 ACS BZ 21554 START - correct the retrieve of the ne children (copy of top-worst list management).
	//	|s| est le séparateur entre les différents éléments réseau
	//	|| est le séparateur entre les différents attributs de l'élément : na||na_value||0 ou 1
	function getListOfNetworkElementQuery($liste)
	{
		$conditions_to_return = ''; // chaine à retourner

		if ( !empty($liste) )
		{
			$conditions_to_return = 'AND ('; // chaine à retourner

			/*
				étape 1 : on construit la condition pour sélectionner les résultats des éléments réseaux cochés par l'utilisateur.
				> NB : on est obligé de faire des 'LIKE' car il faut pouvoir gérer les valeurs des familles qui ont un 3ème axe et celles qui n'en ont pas.
			*/
			$liste_tab = explode($this->separateur_elements, $liste);
		
			$tab_na_to_child = array();
			
			foreach ( $liste_tab as $key=>$element_reseau_chaine )
			{
				// On éclate la chaine na||na_value||desc_na
				$element_reseau_tab = explode($this->separateur_values, $element_reseau_chaine);
				
				// On ajoute la condition sur les éléments réseau sélectionnés
				$conditions_to_return .= ' ( t2.na_value = \''.$element_reseau_tab[1].
					'\' AND t2.na = \''.$element_reseau_tab[0].'\') OR ';
			
				// Initialisation des éléments réseau où l'on va recherché leurs enfants
				if($element_reseau_tab[2])
					$tab_na_to_child[$element_reseau_tab[0]][] = $element_reseau_tab[1];
				
			}
			// On supprime le dernier OR
			$conditions_to_return = substr($conditions_to_return,0,strlen($conditions_to_return)-3);

			/*
				Etape 2 : construction des conditions pour sélectionner tous les éléments enfants
				des éléments réseaux ayant le 4ème paramètre à 1.
			*/
			$tab_elem_reseau_enfant = array();
		
			$lst_parents = array();
			
			if(count($tab_na_to_child) > 0){
				foreach( $tab_na_to_child as $na=>$na_value){
					$family_na = $this->getFamilyOfNa($na);
					
					$nivMin = get_network_aggregation_min_from_family($family_na, $this->product);
					
					$liste_na_enfants = getAgregPath($nivMin, $na, $family_na, $this->db);
					$get_list_enfants = $this->createQueryGetElementsChild($tab_na_to_child[$na],$liste_na_enfants);
					
					if (count($get_list_enfants) > 0) {
						$conditions[] = implode(" OR ", $get_list_enfants);
					}
				}
			
				if (count($conditions) > 0) {
					$conditions_to_return.= ' OR ( '.implode(' OR ', $conditions).' )';
				}
			}			
			$conditions_to_return.= ')';
		}
		if ( $this->debug ) __debug($conditions_to_return,'Condition sur les éléments réseaux sélectionnés');

		return $conditions_to_return;
	}

	/** 
	* Retourne la requête qui récupère tous les enfants des éléments réseau sélectionnés 
	* MPR 19/11/2008
	* @since cb4.1.0.00
	* @version cb4.1.0.00
	* @param array $na_values : liste des éléments réseau sélectionnés
	* @param array $array_levels : liste des na enfants que l'on doit récupérer
	* @return array : tableau contenant les requêtes sélectionnant tous les enfants des éléments réseau sélectionnés
	*/
	public static function createQueryGetElementsChild($na_values,$array_levels){
		/*
		Exemple des requêtes générés : Récupération des LAC et des SAI pour deux MSC donnés
			t2.na_value IN (
				SELECT e0.eoar_id as sai 
				FROM edw_object_arc_ref e0 ,edw_object_arc_ref e1  
				WHERE e0.eoar_id_parent = e1.eoar_id 
					AND e0.eoar_arc_type = 'sai|s|lac' 
					AND e1.eoar_arc_type = 'lac|s|msc' 
					AND e1.eoar_id_parent IN ('TAG10_MSC_00000','TAG10_MSC_18102') 
			) 
			AND t2.na = 'sai'  
			OR t2.na_value IN (
				SELECT e1.eoar_id as lac 
				FROM edw_object_arc_ref e0 ,edw_object_arc_ref e1  
				WHERE e0.eoar_id_parent = e1.eoar_id 
				AND e0.eoar_arc_type = 'sai|s|lac' 
				AND e1.eoar_arc_type = 'lac|s|msc' 
				AND e1.eoar_id_parent IN ('TAG10_MSC_00000','TAG10_MSC_18102') ) AND t2.na = 'lac'  )
			)
			AND t2.na = 'lac'  
		
		*/
		
		$queries = array();
		
		// only usefull if case of child network level
		if (count($array_levels) > 1) {
			
			$select = "t2.na_value IN ( SELECT DISTINCT ";
			// from
			$j = 0;
			$from ="";
			$where = "";
			// loop on all levels minus 1. Last level is the current one. It is used as "parent" level for arc creations
			for ($i = 0; $i < (count($array_levels)-1); $i++) {
				$val = $array_levels[$i];
	
				// On définit un champs à sélectionner par requête
				$select_tab[$i] = "e{$i}.eoar_id as ".$val;//$array_levels[$i];
				$condition_na[$i] = " AND t2.na = '".$val."'";//$array_levels[$i]."' ";
				$from .= (($from == "") ? " FROM " : ",")."edw_object_arc_ref e{$i} ";
				$j++;
			}
			
			// Création des jointures 
			for($i = 0; $i < $j-1; $i++) {
				$id_parent = $i+1;
				$where .= (($where == "" ? " WHERE " : " AND "))."e{$i}.eoar_id_parent = e{$id_parent}.eoar_id";	
			}
				
			// Création des arcs
			for($i = 0; $i < $j; $i++) {
				$id_parent = $i+1;
				$where .= (($where == "" ? " WHERE " : " AND "))."e{$i}.eoar_arc_type = '".$array_levels[$i]."|s|".$array_levels[$id_parent]."'";
				
			}
			
			// condition sur le ou les éléments réseau sélectionnés
			$where .= " AND e".($j-1).".eoar_id_parent IN ('".implode("','",$na_values)."') )";
		
			// On récupère toutes les conditions
	
			foreach($select_tab as $k=>$sel) {
				// Construction des requêtes 
				$queries[] = $select.$sel.$from.$where.$condition_na[$k];
				
			}
		}
		
		return $queries;
	}
	// 22/11/2011 ACS BZ 21554 END



	/*
		Retourne le label d'un type d'alarme.
		(le type provient du champ alarm_type de la table edw_alarm)
	*/
	function getAlarmTypeLabel($type){
		$label = "";
		switch($type){
			case 'static':		$label = "static"; break;
			case 'dyn_alarm':	$label = "dynamic"; break;
			case 'top-worst':	$label = "top/worst cell"; break;
			default:			$label = $type; break;
		}
		return $label;
	}

	/*
		Affiche la liste des 24 derniers timestamp de calcul,
		l'utillisateur peut cliquer sur chaque heure.
	*/
	function displayHourSelect()
	{
		global $niveau0;
		// Style de l'heure de timestamp affiché.
		$style_ref = " style='text_decoration:underline; font-weight:bold; font-size:10pt; background-color:#fff;' ";
		// Largeur en pixels de la zone d'affichage des heures.
		$largeur_zone_heure = 600;

		if ( $this->order_on=='calculation_time' )
		{
			$query = "
				SELECT
					DATE_PART('hour', calculation_time) as derniere_heure ,
					calculation_time as derniere_heure_ts
					FROM edw_alarm
					WHERE calculation_time IS NOT NULL
					AND visible = 1
					ORDER BY calculation_time desc LIMIT 1
			";
		} else {
			// maj 10/03/2008 christophe : ajout d'une requête pour le order by sur la TA de capture.
			// 17:33 09/11/2009 GHX
			// correction du BZ 12502.
			// Ajout du champ ta dans le SELECT
            // 07/06/2011 BBX -PARTITIONING-
            // Correction des casts
			$query = "
				SELECT
					CASE WHEN substring(ta_value::text from 9 for 2) = '' THEN '23' ELSE substring(ta_value::text from 9 for 2) END as derniere_heure ,
					substring(ta_value::text from 1 for 4)||'-'||substring(ta_value::text from 5 for 2)||'-'||substring(ta_value::text from 7 for 2)||' '||
					CASE WHEN substring(ta_value::text from 9 for 2) = '' THEN '23' ELSE substring(ta_value::text from 9 for 2) END
					||':00:00' as derniere_heure_ts,
					ta
					FROM edw_alarm
					WHERE calculation_time IS NOT NULL
					AND visible = 1
					AND alarm_type <> 'top-worst'
					ORDER BY ta_value desc LIMIT 1
			";
		}
		$row = $this->db->getrow($query);
		if ($row) {
			$date = explode(' ',$row["derniere_heure_ts"]);
			$date_ts = 	explode('-',$date[0]);
			$heure_ts = $date[1];
			$heure_ts = explode(':',$heure_ts);

			// 17:33 09/11/2009 GHX
			// Début correction du BZ 12502.
			// Ajout de la condition suivante
			if ( $this->order_on!='calculation_time')
			{
				if ( $row['ta'] == 'month' )
				{
					$date_ts[2] = 1; // Ajout de la date
				}
				elseif ( $row['ta'] == 'week' )
				{
					$date_ts[1] = date('m', strtotime($date_ts[0].'-W'.$date_ts[1])); // Ajout du mois
					$date_ts[2] = date('d', strtotime($date_ts[0].'-W'.$date_ts[1])); // Ajout du jour
				}
			}
			// Fin correction du BZ 12502

			$dernier_ts = mktime($heure_ts[0], $heure_ts[1], $heure_ts[2], $date_ts[1], $date_ts[2], $date_ts[0]);

			$veille = $dernier_ts - (1 * 24 * 60 * 60);

			$indice = 24 - $heure_ts[0];

			$largeur =  $largeur_zone_heure *(1 - $heure_ts[0]/24);
			$largeur .= "px";

			if ( $this->order_on=='calculation_time' )
				$title = __T('U_ALARM_MANAGEMENT_CALCULATION_TIME_LABEL');
			else
				$title = __T('G_ALARM_MANAGEMENT_ORDER_ON_CAPTURE_TIME_TITLE');

			$borne = 24-(24-$heure_ts[0]);

			$default_display = '';
			// Si une heure a été sélectionnée, on affiche le lien qui permet de charger l'affichage par défaut.
			if ( !empty($this->timestamp) )
			{
				// 15:46 04/08/2009 GHX
				// Correction du BZ 10639
				// 09/12/2011 ACS Mantis 837 DE HTTPS support
				// 20/12/2011 ACS BZ 25213 connect to the master product
				$url = ProductModel::getCompleteUrlForMasterGui($_SERVER['PHP_SELF'].'?');

				$tmpGet = $_GET;
				unset($tmpGet['timestamp_alarme']);

				$url .= http_build_query($tmpGet);

				$default_display = "
					<span style='float:right;margin-top:10px;margin-right:5px;'>
						<a href='".$url."'>".__T('U_ALARM_MANGEMENT_DEFAULT_DISPLAY')."</a>
					</span>
				";
			}

			echo "
				<div id='select_ta' class='texteGris' style='
					background-image:url(\"".$niveau0."images/fonds/horloge_alarm.gif\");
					background-repeat:no-repeat;
					height:35px;
				'>
				$default_display
				<span class='texteGris' style='float:left; width:110px; padding:4px; padding-top:8px;'>
					$title
				</span>";
				// maj 15/04/2008 Benjamin : affectation de la variable $largeur_zone_heure pour pouvoir modifier la zone. De plus celle-ci était trop petite (520px à l'origine)
				echo "
				<span style='width:{$largeur_zone_heure}px;'>
				<table cellpadding='0' cellspacing='0'>
					<tr style='
						margin-bottom:0px;
						color:#000000;
						'
					>";

			if ( $borne != 23 )
				echo	"
							<td style='border-bottom: 1px #585858 solid; border-right: 0px #FFFFFF solid;'>".date('d-m-Y',$veille)."</td>
							<td style='width:20px;'></td>
						";

			echo	"
						<td style='border-bottom: 1px #585858 solid;'>".date('d-m-Y',$dernier_ts)."</td>
					</tr>
			";

			echo "
					<tr>
			";

			if ( $borne != 23 )
			{
				echo "<td>";
				for($i=23; $i > $borne; $i--){
					$time = $dernier_ts - ($i * 60 * 60);
					$date_link = date('Y-m-d H',$time).":00:00";
					$style = ($date_link == $this->timestamp) ? $style_ref : "";
					echo "
						<div class='arrow_link'>
							<a href='".$_SESSION["url_alarme_courante"]."&timestamp_alarme=$date_link' $style>
								". date('H',$time)."
							</a>
						</div>
					";
				}
				echo "</td><td style='width:20px;'></td>";
			}

			echo "<td>";
			for($i=$heure_ts[0]; $i >= 0; $i--){
				$time = $dernier_ts - ($i * 60 * 60);
				$date_link = date('Y-m-d H',$time).":00:00";
				$style = ($date_link == $this->timestamp) ? $style_ref : "";
				echo "
					<div class='arrow_link'>
						<a href='".$_SESSION["url_alarme_courante"]."&timestamp_alarme=$date_link' $style>
							".date('H',$time)."
						</a>
					</div>
					";
			}
			echo "</td>";

			echo "
					</tr>
				</table>
				</span>

				</div>

				<style type='text/css'>
				.arrow_link {
					float:left;
					background:url(".$niveau0."images/icones/alarm_ta_cursor.gif) no-repeat top center;
					padding-top:4px;
					padding-right:4px;
				}
				</style>

			";
		}

	}

	// maj 10/03/2008 christophe : création de la méthode setOrderOn : permet d'initialiser la variable order_on
	/**
	 * setOrderOn : permet d'initialiser la variable order_on
	 *
	 * @since cb4.0.0.00
	 * @version cb4.0.0.00
	 */
	function setOrderOn($value)
	{
		$this->order_on = $value;
	}// End function setOrderOn

	/*
		Retourne un tableau qui compte le nombre de résultats
		par éléments réseau ou par alarme en fonction du mode
		$result_query : résultat de la requête.
		$nombre_resultat : nombre de résultats de la requête.
		Structure du tableau :
			- mode elem_reseau : $tab[na_value][seuil] = nombre de résultats
			- mode condense : $tab[alarm_name][seuil] = nombre de résultats
	*/
        // 16/11/2012 BBX
        // BZ 30509 : Prise en compte de la TA
	function getNbResultats($tab_data, $seuil){
            $tab = array();
            foreach($tab_data as $ligne){
                if($this->sous_mode == 'elem_reseau'){
                    if(isset($tab[$ligne["na_value"]][$ligne["critical_level"]][$ligne["calculation_time"]])){
                            $tab[$ligne["na_value"]][$ligne["critical_level"]][$ligne["calculation_time"]][$ligne["ta"]]++;
                    } else {
                            $tab[$ligne["na_value"]][$ligne["critical_level"]][$ligne["calculation_time"]][$ligne["ta"]] = 1;
                    }
                } else if($this->sous_mode == 'condense') {
                    if(isset($tab[$ligne["alarm_name"]][$ligne["critical_level"]][$ligne["calculation_time"]])){
                            $tab[$ligne["alarm_name"]][$ligne["critical_level"]][$ligne["calculation_time"]][$ligne["ta"]]++;
                    } else {
                            $tab[$ligne["alarm_name"]][$ligne["critical_level"]][$ligne["calculation_time"]][$ligne["ta"]] = 1;
                    }
                }
            }
            return $tab;
	}

    /**
     * Retourne la chaine de caractère du nombre de résultats à afficher en fonction du mode.
     * les break ont été volontairement enlevés pour l'exécution en cascade des cases (OJT, 22/06/2010)
     * @param Array $tab Tableau provenant de la fonction getNbResultats
     * @param String $seuil Seuil affiché
     * @param String $value Valeur de l'élément (na_value ou nom d'une alarme) dont on veut le nombre de résultats.
     * @param String $ts Timestamp
     * @return String
     */
    // 16/11/2012 BBX
    // BZ 30509 : Prise en compte de la TA
    function getNbResultatsToDisplay($tab,$seuil,$value,$ts,$ta = ""){
        // 24/2/2011 MMT bz 19628 : renvoit simplement le nombre d'alarme de la sévéritée
        if(!empty($ta))
            return $tab[$value][$seuil][$ts][$ta]. " alarms";
        if(is_array($tab[$value][$seuil][$ts])) {
            return array_sum($tab[$value][$seuil][$ts])." alarms";
        }
        return $tab[$value][$seuil][$ts]. " alarms";
    }

	/**
     * Retourne le niveau de rank d'un seuil en fonction du seuil passé en paramètre
     * @param String $seuil
     * @return Integer
     */
	function getRankValue( $seuil )
    {
		$rank = 0;
		switch( $seuil )
        {
			case self::MAJOR_TRESHOLD :
                $rank=2;
                break;

			case self::MINOR_TRESHOLD :
                $rank=1;
                break;

			case self::CRITICAL_TRESHOLD :
            default :
                $rank=3;
                break;
		}
		return $rank;
	}

	/*
		Construit la requête servant à afficher les alarmes en fonction :
			> du mode / sous_mode.
			> du seuil.
			> de la ta/ta_value/timestamp/na si précisé.
	*/
	function getAlarmQuery($seuil)
	{
        // Initialisation des variables
		$condition_ta               = "";
		$condition_ta_value         = "";
		$condition_seuil            = "";
		$condition_timestamp        = "";
		$condition_order_by         = "";
		$condition_acknowledgement  = "";
        $condition_ta_alarm_history = "";
        $condition_seuil            = "";
        $condition_elements_reseaux = "";

		// 24/07/2007 christophe : ajout du param de filtrage sur les éléments réseaux sélectionné
		$condition_elements_reseaux = $this->getListOfNetworkElementQuery($this->list_of_network_element);

		// maj 10/03/2008 christophe : on tient compte de la variable order_on pour faire un order by sur calculation_time ou ta_value.
		if ( $this->order_on=='calculation_time' )
			$colonne_order_by_ta = 'calculation_time';
		else
			$colonne_order_by_ta = 'ta_value';

		/*
			Constrution de la requête
		*/

		// Cas où l'utilisateur choisit un timestamp précis : seulement pour le mode management.
		if($this->mode == 'management'){

			$condition_acknowledgement = " AND t2.acknowledgement=0 ";

			if($this->timestamp != ''){
				/*
					Si un timestamp a été choisit par l'utilisateur, on affiche toutes les alarmes
					calculées dans la même heure que le timestamp passé en paramètre.
					Exemple :
						si le timestamp passé en paramètre est 2006-08-11 14:40:32, on vat afficher
						tous les réulstats dont le timestamp commence par  2006-08-11 14
				*/
				// maj 10/03/2008 christophe : ajout d'une nouvelle condition pour gérer le cas de order by capture time.
				if ( $this->order_on=='calculation_time' )
				{
					$condition_timestamp = "
						AND
							date_trunc('hour', calculation_time) = date_trunc('hour', timestamp'".$this->timestamp."')
					";
				}
				else
				{
					// on récupère la date du timestamp au format T&A.
					$day = substr($this->timestamp,0,4).substr($this->timestamp,5,2).substr($this->timestamp,8,2);
					$hour = $day.substr($this->timestamp,11,2);
					$condition_timestamp = "
						AND ta_value = '$hour'
					";
				}
			} else {
				/*
					Par défaut, en mode alarm managment, on affiche les les 24 dernières heures calculées à partir
					du timestamp le plus récent.
					On met 'calculation_time IS NOT NULL' : quand le calcul des alarmes est en cours mais non terminé, le champ
					calculation_time est vide. On n'aaffiche pas les résultats des alarmes dont le calcul est encore en cours.

					* Le 16 08 2006 je vire
					AND
						calculation_time <= (SELECT calculation_time FROM edw_alarm ORDER BY calculation_time desc LIMIT 1)
						à la demande de cyrille.
				*/
				// maj 10/03/2008 christophe : ajout d'une nouvelle subquery pour gérer le cas de order by capture time.
				if ( $this->order_on=='calculation_time' )
				{
					$subquery = " calculation_time >=
						(SELECT calculation_time FROM edw_alarm WHERE visible=1 ORDER BY calculation_time desc LIMIT 1)
						- INTERVAL '1 day' ";
				}
				else
				{
					$q = " SELECT ta_value, ta FROM edw_alarm WHERE visible=1 AND rank IS NOT NULL ORDER BY ta_value desc LIMIT 1 ";
					$row = $this->db->getrow($q);
					if ($row > 0) {
						// maj 10/06/2008 Benjamin : récupération de la TA
						$ta = $row['ta'];
						$day_temp = (strlen($row['ta_value'])>9)? substr($row['ta_value'],0,-2) : $row['ta_value'] ;
                                                // 12/12/2012 BBX
                                                // BZ 30489 : utilisation de la classe Date
						$offsetday_temp = Date::getOffsetDayFromDay($day_temp);
						$day_for_query = getDay($offsetday_temp+1);
						$day = $day_temp;
						// 23/02/2010 NSE : remplacement GetweekFromAcurioDay($day) par Date::getWeekFromDay($day,$firstDayOfWeek=1)
						$week = Date::getWeekFromDay($day,get_sys_global_parameters('week_starts_on_monday',1));
						$month = substr($day,0,6);

						$day24 = $day_for_query;
						// 23/02/2010 NSE : remplacement GetweekFromAcurioDay($day) par Date::getWeekFromDay($day,$firstDayOfWeek=1)
						$week24 = Date::getWeekFromDay($day24,get_sys_global_parameters('week_starts_on_monday',1));
						$month24 = substr($day24,0,6);
						// maj 10/06/2008 Benjamin : détermination de la dernière heure. BZ6634
                        // 07/06/2011 BBX -PARTITIONING-
                        // Correction des casts
						$query_last_hour = "
							SELECT
								CASE WHEN substring(ta_value::text from 9 for 2) = '' THEN '23' ELSE substring(ta_value::text from 9 for 2) END as derniere_heure
							FROM edw_alarm
							WHERE calculation_time IS NOT NULL
								AND visible = 1
								AND alarm_type <> 'top-worst'
							ORDER BY ta_value desc
							LIMIT 1";
						$array_last_hour = $this->db->getrow($query_last_hour);
						$last_hour = ($array_last_hour['derniere_heure'] == 23) ? $array_last_hour['derniere_heure'] : $array_last_hour['derniere_heure']+1;
						/*
						$subquery = "
							( ta_value IN  ('$day','$week','$month','$day24','$week24','$month24')
								OR ta_value LIKE '$day".'%'."' OR ta_value LIKE '$day24".'%'."')
						";*/
						// maj 10/06/2008 Benjamin : modification de la requête de récupération des alarmes. BZ6634
                        // 09/06/2011 BBX -PARTITIONING-
                        // Correction des casts
						$subquery = "(ta_value::text IN ('$day','$week','$month') OR ta_value::text LIKE '$day".'%'."' OR ta_value::text >= '$day24".$last_hour."')";
					}
				}

				if ( !empty($subquery) )
					$condition_timestamp = "
						AND
							$subquery
						AND
							calculation_time IS NOT NULL
					";
				else
					$condition_timestamp = "
						AND
							calculation_time IS NOT NULL
					";
			}
		}


		/*
			Options de la requêtes en fonction  du seuil
		*/
		// 24/2/2011 MMT bz 19628 : groupe par severité et nom par rank
		$condition_seuil = '';
		if($seuil != 'all'){
			$condition_seuil = " AND critical_level = '$seuil' ";
		}

		// Si l'utilisateur souhaite afficher une / des ta précise. $this->ta contient une liste de ta séparé par des ','
		if ($this->ta != '') {
			if($this->mode == 'management'){
				$condition_ta = " AND t2.ta in (".$this->ta.")  ";
			} else if($this->mode == 'history') {
				$condition_ta = " AND t2.ta = '".$this->ta."' ";
			}
		}
		// Si l'utilisateur souhaite affiche une ta_value précise.  >> utilisé dans le cas où il y a un sélecteur dans la page
		if ($this->ta_value != '')	$condition_ta_value = " AND t2.ta_value = '".$this->ta_value."' ";

		/*
			Si on est en mode history je dois également faire une requête pour récupèrer les données des 30 derniers jours / ou heures...
			afin de construire un tableau avec le nombre de résultats pour créer le graph de cyrille
		*/
		if($this->mode == "history"){
			switch ($this->ta){
				case "hour" :
					$date_format = "YmdH";
					$unixdate = mktime(substr($this->ta_value, 8, 2), 0, 0, substr($this->ta_value, 4, 2), substr($this->ta_value, 6, 2), substr($this->ta_value, 0, 4));
					$interval_value = $this->period * 3600;

					$time = date($date_format, $unixdate - $interval_value);
					break;
				case "day" :
				case "day_bh" :
					$date_format = "Ymd";
					$unixdate = mktime(6, 0, 0, substr($this->ta_value, 4, 2), substr($this->ta_value, 6, 2), substr($this->ta_value, 0, 4));
					$interval_value = $this->period * 24 * 3600;

					$time = date($date_format, $unixdate - $interval_value);
					break;
				case "week" :
				case "week_bh" :
					// 11/03/2013 GFS - BZ#31364 - [SUP][TA HPG][AVP 32586][Telus][Partionning]: All weekly history is delete on last week of the year compute
					$date_format = "oW";
					// On récupère le dernier jour de la semaine damandée.
					// 23/02/2010 NSE : remplacement GetLastDayFromAcurioWeek($week) par Date::getLastDayFromWeek($week,$firstDayOfWeek=1)
					$dernier_jour = Date::getLastDayFromWeek($this->ta_value,get_sys_global_parameters('week_starts_on_monday',1));
					$unixdate = mktime(0, 0, 0, substr($dernier_jour, 4, 2), substr($dernier_jour, 6, 2) - ($this->period * 7), substr($dernier_jour, 0, 4));

					$time = date($date_format, $unixdate);
					break;
				case "month" :
				case "month_bh" :
					$date_format = "Ym";
					$unixdate = mktime(6, 0, 0, substr($this->ta_value, 4, 2) - $this->period, 1, substr($this->ta_value, 0, 4));

					$time = date($date_format, $unixdate);
					break;
			}


			/*
			$condition_ta_alarm_history = "
				AND t2.ta_value >= to_char('$this->ta_value'::date - '$interval_value'::interval,'$date_format')
				AND t2.ta_value <= '$this->ta_value'
				AND t2.ta = '$this->ta'
			";
			*/
			if ($this->ta != '') {
				$condition_ta_alarm_history = "
					AND t2.ta_value >= '$time'
					AND t2.ta_value <= '$this->ta_value'
					AND t2.ta = '$this->ta'
				";
			}
		}

		// Condition sur le order by.
      // 03/02/2011 NSE bz 20445 : tri des alarmes par ordre aplhabétique
		// 25/02/2011 MMT bz 19628 : correction sur 20445 : trie par na_value d'abord puis alarm_name
		// Condition sur le order by.
		if($this->sous_mode == "elem_reseau"){
			$condition_order_by = "
				ORDER BY
					t2.$colonne_order_by_ta DESC,
					t2.na_value,
					alarm_name,
					t2.critical_level
			";
		} else {
			$condition_order_by = "
				ORDER BY
					t2.$colonne_order_by_ta DESC,
					alarm_name,
					t2.id_alarm,
					t2.critical_level
			";
		}

		$this->condition_ta = $condition_ta;
		$this->condition_ta_value = $condition_ta_value;
		$this->condition_timestamp = $condition_timestamp;

		// Clause select de la requête.
		$this->query_select = "
			SELECT
				t2.oid, t2.*,
				CASE WHEN t2.alarm_type='dyn_alarm' THEN
					(SELECT alarm_name FROM sys_definition_alarm_dynamic WHERE alarm_id=t2.id_alarm LIMIT 1)
				ELSE
					(SELECT alarm_name FROM sys_definition_alarm_static WHERE alarm_id=t2.id_alarm LIMIT 1)
				END as alarm_name,
				CASE WHEN t2.alarm_type='dyn_alarm' THEN
					(SELECT family FROM sys_definition_alarm_dynamic WHERE alarm_id=t2.id_alarm LIMIT 1)
				ELSE
					(SELECT family FROM sys_definition_alarm_static WHERE alarm_id=t2.id_alarm LIMIT 1)
				END as family
		";
		// Clause from de la requête.
        // 20/09/2012 MMT DE 5.3 Delete Topology - ajout jointure sur edw_object_ref pour ne pas afficher les elements supprimés
		$this->query_from = "
			FROM edw_object_ref eor1, edw_alarm t2 LEFT JOIN edw_object_ref eor2 on (eor2.eor_id = t2.a3_value AND eor2.eor_obj_type = t2.a3) 
		";
		// Clause where de la requête.
		// le 29 09 2006 christophe ajout des exists pour que les résultats des alarmes supprimées ne soient pas affichées.
		// 24/07/2007 christophe : ajout de la variable $condition_elements_reseaux.
		// modif 12/12/2007 Gwen
			// Ajout de la condition visible = 1
        // 20/09/2012 MMT DE 5.3 Delete Topology - ajout jointure sur edw_object_ref pour ne pas afficher les elements supprimés
		$this->query_where = "
			WHERE
				(alarm_type='static' OR alarm_type='dyn_alarm')
				AND
					CASE WHEN t2.alarm_type='dyn_alarm' THEN
						EXISTS (SELECT * FROM sys_definition_alarm_dynamic WHERE alarm_id=t2.id_alarm)
					ELSE
						EXISTS (SELECT * FROM sys_definition_alarm_static WHERE alarm_id=t2.id_alarm)
					END
				AND rank is not null
				AND rank_alarm is not null
				AND visible = 1
                AND (eor1.eor_id = t2.na_value AND eor1.eor_obj_type = t2.na)
                AND ((t2.a3 IS NULL  ) OR (eor2.eor_id = t2.a3_value AND eor2.eor_obj_type = t2.a3))

				$condition_ta
				$condition_ta_value
				$condition_seuil
				$condition_timestamp
				$condition_acknowledgement
				$condition_elements_reseaux
		";
		// Clause where pour construire le graph de cyrille.
		// 02/08/2007 christophe :Ajout de la condition sur la sélection des éléments réseaux pour la requête qui affiche le graphe.
		// modif 12/12/2007 Gwen
			// Ajout de la condition visible = 1
        // 16/11/2012 MMT bz 30408 alarm history sometimes runs forever 
		$this->query_where_graph = "
			WHERE
				(alarm_type='static' OR alarm_type='dyn_alarm')
				AND
					CASE WHEN t2.alarm_type='dyn_alarm' THEN
						EXISTS (SELECT * FROM sys_definition_alarm_dynamic WHERE alarm_id=t2.id_alarm)
					ELSE
						EXISTS (SELECT * FROM sys_definition_alarm_static WHERE alarm_id=t2.id_alarm)
					END
				AND rank is not null
				AND rank_alarm is not null
				AND visible = 1
                AND (eor1.eor_id = t2.na_value AND eor1.eor_obj_type = t2.na)
                AND ((t2.a3 IS NULL  ) OR (eor2.eor_id = t2.a3_value AND eor2.eor_obj_type = t2.a3))
                
				$condition_ta_alarm_history
				$condition_seuil
				$condition_elements_reseaux
		";

		// Clause order by de la requête.
		$this->query_order_by = "
			$condition_order_by
		";
		$query = 	$this->query_select .
					$this->query_from .
					$this->query_where .
					$this->query_order_by;

		// Query pour générer le graph de cyrille.;
		$this->query_graph = 	$this->query_select .
							$this->query_from .
							$this->query_where_graph .
							$this->query_order_by;

		// cette variable de session est utilisée pour générer le fichier PDF.
		$_SESSION['queries'][$seuil]['query_select'] = 		$this->query_select;
		$_SESSION['queries'][$seuil]['query_from'] = 		$this->query_from;
		$_SESSION['queries'][$seuil]['query_where'] = 		$this->query_where;
		$_SESSION['queries'][$seuil]['query_order_by'] = 	$this->query_order_by;

		if($this->debug > 1){
			echo '<div style="text-align:left">';
			__debug($query,$seuil);
			echo "</div>";
		}

		return $query;
	}

	/*
		Retourne le header des tableau généré en fonction du mode
	*/
	function getHeaderTableau(){
		switch($this->mode){
			case 'management':
				if($this->sous_mode == "elem_reseau"){
					$header_tableau = Array('Calculation time','Network element',	'Alarm name / source time', 'Options');
				} else {
					$header_tableau = Array('Calculation time','Alarm Name','Network element / source time ', 'Options');
				}
				break;
			case 'history' :
				$header_tableau = Array('Calculation time','Alarm Name',	'Network element / source time ', 'Options');
				break;
			default:
				$header_tableau = Array('Calculation time','Network element',	'Alarm name / source time', 'Options');
				break;
		}
		return $header_tableau;
	}

	/**
	 * Renvoie le label d'une network agrégation donnée suivi du niveau d'agrégation entre crochet. S'il y a un troisième axe, le valeur du 3° axe et son niveau sera aussi renvoié
	 * dans à la suite du premier network.
	 *
	 *	- maj 05/04/2007 Gwénaël
	 *		- Modification de la function pour prendre en compte le troisième axe, et rependre la function getNaLabel du fichier edw_function.php
	 *		- Renomage de la fonction getNaLabel en get_na_label (pour ne pas préter à confusion avec celle du fichier edw_function.php)
	 *	- maj 04/12/2008 SLC
	 		- les infos d'axe 3 ne sont plus sérialisées dans la colonne na, donc il aurait fallu ajouter deux arguments à la fonction
				au lieu, j'ai passé tout le tableau $row
	 *
	 * exemple : sai 1[SAI]                              << Pas de troisième axe
	 *                  sai 2[SAI] - tos 1[TOS]         << Présence d'un troisième axe
	 *
	 * @param array $row : le tableau contenant toutes les informations concernant l'alarme
	 * @return string
	 */
	function get_na_label($row) {
		$monAxe3 = '';

		if (GetAxe3($row['family'],$this->product))
			$monAxe3 = " - " . getNaLabel($row['a3_value'], $row['a3'], $row['family'],$this->product) . "[" . $this->na_label_array[$row['a3']][$row['family']] . "]";

		// echo "getNaLabel({$row['na_value']}, {$row['na']}, {$row['family']},$this->product) = ".getNaLabel($row['na_value'], $row['na'], $row['family'],$this->product);
		return getNaLabel($row['na_value'], $row['na'], $row['family'],$this->product) . " [" . $this->na_label_array[$row['na']][$row['family']] . "]" . $monAxe3;
	}

	/*
		Execute la query pour construire les données du graph
		en mode history
	*/
	function getGraphDataToDisplay(){
		$ta_seuil = array('critical','major','minor');
		// 24/07/2007 christophe : on récupère la conditon 'where' pour filtrer sur les éléments réseaux.
		//$this->listOfNetworkElementQuery = $this->getListOfNetworkElementQuery($this->list_of_network_element);

		foreach($ta_seuil as $seuil){
			$query = $this->getAlarmQuery($seuil);		// Construction de la requête permettant l'affichage.
			if ($this->debug) {
				echo "<u>Requête pour construire le graph seuil $seuil :</u>";
				echo $this->query_graph . "<br>";
                //exit();
			}
            
			$tab_data = $this->executeQuery($this->query_graph);
			$this->tab_data_seuil_graph[$seuil] = $tab_data;	// on stocke les résultats.
		}
	}

	/*
		Contruit le tableau graph_data
		$tab_data_seuil[seuil]->array[nom_colonne]
	*/
	function  generateGraph_data() {
		$this->graph_data  = array();
		$liste_seuil = array('critical','major','minor');
		foreach($this->tab_data_seuil_graph as $seuil=>$tab){
			if($tab != ""){
				foreach($tab as $row){
					$alarm_type = $row['alarm_type'];
					$na = $row['na'];
					$ta = $row['ta'];
					$ta_value = $row['ta_value'];
					$seuil_resultat = $row['critical_level'];
					if(isset($this->graph_data[$seuil_resultat][$alarm_type][$na][$ta][$ta_value])){
						$this->graph_data[$seuil_resultat][$alarm_type][$na][$ta][$ta_value] = $this->graph_data[$seuil_resultat][$alarm_type][$na][$ta][$ta_value]+1;
					} else {
						$this->graph_data[$seuil_resultat][$alarm_type][$na][$ta][$ta_value] = 1;
					}
				}
			}
		}
	}

	/*
		executeQuery
			$query : requête à exécuter.
			retourne un tableau contenant la liste des éléments à afficher.
	*/
	function executeQuery($query){

		if($this->debug > 0){
			echo '<pre>'.$query.'</pre>';
		}

		$result = $this->db->getall($query);

		if ($result) {

			foreach ($result as $row) {
				$tab_data[] = array(
					"alarm_name"		=> $row["alarm_name"],
					"family"			=> $row["family"],
					"oid"				=> $row["oid"],
					"id_alarm"			=> $row["id_alarm"],
					"id_result"			=> $row["id_result"],
					"ta"				=> $row["ta"],
					"ta_value"			=> $row["ta_value"],
					"na"				=> $row["na"],
					"na_value"		=> $row["na_value"],
					"a3"				=> $row["a3"],
					"a3_value"		=> $row["a3_value"],
					"alarm_type"		=> $row["alarm_type"],
					"critical_level"		=> $row["critical_level"],
					"acknowledgement"	=> $row["acknowledgement"],
					"calculation_time"	=> $row["calculation_time"],
					"rank"			=> $row["rank"],
				);
			}

		} else {
			$tab_data = "";
		}

		return $tab_data;
	}


	/*
		Affiche les résultats des alarmes.
		$seuil : si définit affiche le seuil passsé en paramètre.
	*/
	function displayAlarms($seuil){
		global $niveau0;

		// On affiche le titre.
		echo "
			<div id='$seuil' class='texteGris contenu'>
				<h1>
					<img src='".$niveau0."images/icones/puce_fieldset.gif'/>
					&nbsp;".ucfirst($seuil)."
					( ".$this->nb_resultats_total_seuil[$seuil]." results)
				</h1>
				<hr/>
			";

		// Libellés de l'en-tête des tableaux en fonction du mode / type d'alarme.
		$header_tableau = $this->getHeaderTableau();

		/*
			Construction de l'affichage général.
		*/
		$id_tableau = 'tableau_'.$seuil;
		/*
			chaque tableau de seuil est contenu dans un div différent.
		*/
			echo "<div id='sous_$seuil'>";
			// echo "<div id='myurl'></div>";
			echo "<table cellpadding='2' cellspacing='1' width='100%' id='$id_tableau' border='0'>";
				// Affichage du header du tableau.
				echo "<tr>";
					foreach($header_tableau as $titre)
						echo "<th>$titre</th>";
				echo "</tr>";

				// On récupère le tableau de données correspondant au seuil à afficher.
				$tab_data = $this->tab_data_seuil[$seuil];

				if ($tab_data != "") {

					$element_precedent = "";
					$calculation_time_precedent = "";
                                        // 16/11/2012 BBX
                                        // BZ 30509 : Prise en compte de la TA
					$tab_nb_resultats = $this->getNbResultats($tab_data, $seuil);

					foreach ($tab_data as $row) {

						$CT = str_replace(" ","",$row["calculation_time"]);
						$CT = str_replace("-","",$CT);
						$CT = str_replace(":","",$CT);

						if ($this->sous_mode == 'elem_reseau') {
							$element = $row["na_value"]."_".$CT;
						} else {
							$element = $row["id_alarm"]."_".$row["alarm_type"]."_".$CT;
						}

						// On ferme la balise tbody des enfants si on change de NA et que ce n'est pas la première ligne
						if($element_precedent != $element && $element_precedent != "") echo "</tbody>";

						// Affichage du nom de la NA ou du nom de l'alarme.
						if(($element_precedent != $element) || ($element_precedent == $element && $calculation_time_precedent!=$row["calculation_time"])){
							// 01/08/2011 MMT Bz 22887 ajout du seuil pour differenciation des ID
							$id = $element."_".$seuil."_".$row["ta_value"];

							// On stocke la na_min de la famille courante.
							$this->na_min = get_network_aggregation_min_from_family($row["family"],$this->product);
							// Nom de la table de définition des alarmes en fonction du type.
							switch ( $row["alarm_type"] )
							{
								case 'static':		$this->table_name_gis = "sys_definition_alarm_static"; break;
								case 'dyn_alarm':	$this->table_name_gis = "sys_definition_alarm_dynamic"; break;
								case 'top-worst':	$this->table_name_gis = "sys_definition_alarm_top_worst"; break;
								default:			$this->table_name_gis = "sys_definition_alarm_static"; break;
							}

							echo "<tr id='$id' bgcolor='".$this->alarmColor[$seuil]."' onmouseover=\"surligner('$id');\" onmouseout=\"surligner('$id');\" marquer='false'>";

								// Affichage du time calculation et de l'icône plus / moins.
								$img = ($this->display == 'none') ? 'plus_alarme.gif' : 'moins_alarme.gif' ;
                                // 16/08/2010 NSE DE Firefox bz 16918 : ajout de Element.
								echo "<td onclick=\"Element.toggle('".$id."_child'); change_img('".$id."_img');\" style='cursor:pointer;'>"	;
									echo "<img id='".$id."_img' title='Expand/Collapse' alt='Expand/Collapse' src='".$niveau0."images/icones/$img' style='cursor:pointer'/>&nbsp;".$row["calculation_time"];
								echo "</td>";

								// Affichage de la NA / ou nom alarme
								// 17:59 31/08/2009 GHX
								// Correction du BZ 11272
								// Ajout du utf8_encode
								echo "<td>";
									if ($this->sous_mode == 'elem_reseau') {
										//modif 05/04/2007 Gwénaël
											//change de nom pour la fonction et plus besoin rajouter le niveau d'agrégation car maintenant il est retourné aussi par la fonction
										echo utf8_encode($this->get_na_label($row));
									} else {
										// faire une fonction pour afficher le label d'un type d'alarme.
										echo  utf8_encode($row["alarm_name"]." [".$this->getAlarmTypeLabel($row["alarm_type"]).",".$this->family_array[$row["family"]]."]");
									}
									// Affichage du 3ème axe si présent.
									if ($row["a3_value"] != "" && GetAxe3($row["family"],$this->product))
										echo " (HN=".get_axe3_label($row["a3_value"],$this->product).") ";

								echo "</td>";

								// Affichage du nombre de résultats par seuil et de la time aggregation.
                                                                // 16/11/2012 BBX
                                                                // BZ 30509 : Prise en compte de la TA
								if($this->sous_mode == 'elem_reseau'){
                                                                    $nb_resultats_par_seuil = $this->getNbResultatsToDisplay($tab_nb_resultats,$seuil,$row["na_value"],$row["calculation_time"],$row["ta"]);
                                                                    if($this->order_on == "calculation_time") {
                                                                        $nb_resultats_par_seuil = $this->getNbResultatsToDisplay($tab_nb_resultats,$seuil,$row["na_value"],$row["calculation_time"]);
                                                                    }									
								} else  {
                                                                    $nb_resultats_par_seuil = $this->getNbResultatsToDisplay($tab_nb_resultats,$seuil,$row["alarm_name"],$row["calculation_time"],$row["ta"]);
                                                                    if($this->order_on == "calculation_time") {
									$nb_resultats_par_seuil = $this->getNbResultatsToDisplay($tab_nb_resultats,$seuil,$row["alarm_name"],$row["calculation_time"]);
                                                                    }
								}
								echo "<td>$nb_resultats_par_seuil  / ".$this->ta_array[$row["ta"]]."</td>";

								/*
									EN mode par élément de réseau, on affiche sur la 1ère ligne les icônes
									de lien vers les dasboard
									En mode condense, on affiche ces icônes sur les lignes 'enfant'.
								*/
								echo "<td>";
									// Affichage de l'icone de validation de l'alarme.
									if($this->mode == 'management'){
										if($this->sous_mode == 'elem_reseau'){
											$params_acquittement = "0,'NA','{$row['ta']}','{$row['ta_value']}','{$row['na']}','{$row['na_value']}',0,'','{$row['calculation_time']}' ";
										} else {
											$params_acquittement = "0,'ALARM','{$row['ta']}','{$row['ta_value']}','','','{$row['id_alarm']}','{$row['alarm_type']}','{$row['calculation_time']}' ";
										}
										echo "<img onclick=\"validateAlarm($this->product,'$id_tableau','$id', $params_acquittement)\" src='".$niveau0."images/icones/validate_alarme.gif' title='Alarm acknowledgement' alt='Alarm acknowledgement' style='cursor:pointer'/>";
									}
									if($this->sous_mode == 'elem_reseau'){
										// Lien vers les dashboards.
										if($this->dashboard_alarm){
											$type_alarm_tmp = ($row["alarm_type"] == "dyn_alarm") ? "dyn" : $row["alarm_type"];
											$param_dashboard = "alarm_type=".$type_alarm_tmp;
											$param_dashboard .= "&id_alarm=".$row["id_alarm"];
											$param_dashboard .= "&na_value=".$row["na_value"]."&na=".$row["na"];
											// modif 13/04/2007 Gwénaël
												//modification des liens vers les dashboards pour prendre en compte le troisième
											// $_na_value = explode(get_sys_global_parameters('sep_axe3'), $row["na_value"]);
											// $_na = explode(get_sys_global_parameters('sep_axe3'), $row["na"]);
											// $param_dashboard .= "&na_value=".$_na_value[0]."&na=".$_na[0];
											// if(isset($_na_value[1]) && isset($_na[1]))
												// $param_dashboard .= "&na_box_value=".$_na_value[1]."&na_box=".$_na[1];

											// 14/09/2006 - Modif. benoit : ajout de la ta et de la ta_value dans la chaine transmise à 'dashboard_associe.php'
											$param_dashboard .= "&ta_value=".$row["ta_value"]."&ta=".$row["ta"];

                                                                                        // 23/09/2010 BBX
                                                                                        // Ajout de l'élément 3ème axe
                                                                                        // BZ 18036
                                                                                        if(!empty($row["a3_value"]))
                                                                                            $param_dashboard .= "&na3_value=".$row["a3_value"];

											// 03/12/2008 - SLC - ajoute le product + calculation_time
											$param_dashboard .= "&product=".$this->product;
											$param_dashboard .= "&calculation_time=".$row["calculation_time"];

											echo "<img src='".$niveau0."images/icones/dashboard_link_alarme.gif' title='Display dashboard' alt='Display dashboard' onclick=\"javascript:ouvrir_fenetre('".$niveau0."reporting/intranet/php/affichage/dashboard_associe.php?$param_dashboard','comment','yes','yes',450,300);\" style='cursor:pointer;'/>";
										}
									}


								echo "</td>";

							echo "</tr>";
						}
						/*
							affichage des 'enfants'
						*/
						// Affichage de la liste des alarmes de la NA ou de la liste des na de l'alarme.
						// 01/08/2011 MMT Bz 22887 ajout du seuil pour differenciation des ID
						$id_tbody = $element."_".$seuil."_".$row["ta_value"].'_child';
						$id_tr = $id_tbody."_".$row["oid"];
                        // 16/08/2010 NSE DE Firefox bz 16918 : si on utilise "display: block" : les cellules ne sont pas alignées avec celles du dessus.
						if($element_precedent != $element) echo "<tbody id='$id_tbody'".($this->display=='none'?' style="display:none;"':'')."'>";

						if($this->sous_mode == 'elem_reseau'){
							// faire une fonction pour afficher le label d'un type d'alarme.
							$label_element = $row["alarm_name"]." [".$this->getAlarmTypeLabel($row["alarm_type"]).",".$this->family_array[$row["family"]]."]";
						} else {
							//modif 05/04/2007 Gwénaël
								//change de nom pour la fonction et plus besoin rajouter le niveau d'agrégation car maintenant il est retourné aussi par la fanction
							$label_element = $this->get_na_label($row);
						}

						// 17:59 31/08/2009 GHX
						// Correction du BZ 11272
						// Ajout du utf8_encode
						$label_element = utf8_encode($label_element);

						$source_time = getTaValueToDisplay($row["ta"], $row["ta_value"]);

						// maj 15/04/2008 Benjamin : affichage des dates au format US
						// Cas Week
						if (substr($source_time,0,1) == 'W')
						{
							$array_string = explode('-',$source_time);
							$source_time = $array_string[1].'-'.$array_string[0];
						}
						else
						{
							// Masque day / hour
							$masque = (strpos($source_time,':') === false) ? 'Y-m-d' : 'Y-m-d H:i';
							// maj 15/04/2008 Benjamin : prise en charge du cas mois
							if (strlen($source_time) == 7)
							{
								$source_time = '01-'.$source_time;
								$masque = 'Y-m';
							}
							$source_time = date($masque,strtotime($source_time));
						}

						echo "
							<tr id='$id_tr' bgcolor='".$this->alarmColor[$row["critical_level"]]."' onmouseover=\"surligner('$id_tr');\" onmouseout=\"surligner('$id_tr');\" marquer='false'>
								<td bgcolor='#FFFFFF'>&nbsp;</td><td bgcolor='#FFFFFF'>&nbsp;</td><td>$label_element / $source_time</td>
								<td>
							";

								// Validation d'une alarme (seulement valable en mode management)
								if($this->mode == 'management'){
									$params_acquittement = $row["oid"].",'NA-ALARM','','','','',0,'','".$row['calculation_time']."'";

									echo "<img onclick=\"validateAlarm($this->product,'$id_tableau','$id_tr', $params_acquittement)\" src='{$niveau0}images/icones/validate_alarme.gif' title='Alarm acknowledgement' alt='Alarm acknowledgement' style='cursor:pointer'/>";
								}
								// Icone pour afficher les détails de l'alarme dans une popup.
								echo "<img src='{$niveau0}images/icones/plus_alarme.gif' onclick=\"ouvrir_fenetre('alarm_detail.php?oid_alarm={$row['oid']}&product=$this->product','alarm','yes','yes',700,600); marquer('$id_tr');\" title='View details' alt='View details' style='cursor:pointer;'/>";

								// Affichage des trouble ticket.
								if($this->trouble_ticket){
									$url = "network_aggregation_trouble_ticket_list.php?ta={$row['ta']}&ta_value={$row['ta_value']}&calculation_time={$row['calculation_time']}&na={$row['na']}&na_value={$row['na_value']}&product=".$this->product;
									echo "<img src='{$niveau0}images/icones/liste_alarme.gif' title='Trouble ticket list' alt='Trouble ticket list' onclick=\"ouvrir_fenetre('$url','alarm','yes','yes',450,350); \" style='cursor:pointer;'/>";
								}

								if ($this->sous_mode == 'condense') {

									// Lien vers les dashboards.
									if($this->dashboard_alarm){
										$type_alarm_tmp = ($row["alarm_type"] == "dyn_alarm") ? "dyn" : $row["alarm_type"];
										$param_dashboard = "alarm_type=".$type_alarm_tmp;
										$param_dashboard .= "&id_alarm=".$row["id_alarm"];
										$param_dashboard .= "&na_value=".$row["na_value"]."&na=".$row["na"];
										$param_dashboard .= "&ta_value=".$row["ta_value"]."&ta=".$row["ta"];
										$param_dashboard .= "&product=".$this->product;

                                                                                // 23/09/2010 BBX
                                                                                // Ajout de l'élément 3ème axe
                                                                                // BZ 18036
                                                                                if(!empty($row["a3_value"]))
                                                                                    $param_dashboard .= "&na3_value=".$row["a3_value"];

										// modif 13/04/2007 Gwénaël
											//modification des liens vers les dashboards pour prendre en compte le troisième
										// $_na_value = explode(get_sys_global_parameters('sep_axe3'), $row["na_value"]);
										// $_na = explode(get_sys_global_parameters('sep_axe3'), $row["na"]);
										// $param_dashboard .= "&na_value=".$_na_value[0]."&na=".$_na[0];
										// if(isset($_na_value[1]) && isset($_na[1]))
											// $param_dashboard .= "&na_box_value=".$_na_value[1]."&na_box=".$_na[1];

										echo "<img src='".$niveau0."images/icones/dashboard_link_alarme.gif' title='Display dashboard' alt='Display dashboard' onclick=\"javascript:ouvrir_fenetre('".$niveau0."reporting/intranet/php/affichage/dashboard_associe.php?$param_dashboard','comment','yes','yes',450,300);\" style='cursor:pointer;'/>";
									}
								}

								// Lien vers le GIS. TODO
								//if($this->gis_alarm && $row["alarm_type"] == "static"){
								//avant modificaition du 24/07/2007 : if($this->gis_alarm && $this->gis && $row["family"] == $this->main_family){
                                // 20/06/2011 NSE : merge Gis without polygons
								if( GisModel::linksToGisAvailable("alarm", $row["na"], $this->product) )
								{
									// maj 02/06/2009  MPR : Ajotu du produit dans les paramètres du GIS
									$this->gis_parametre = $this->product. '|@|';
									$this->gis_parametre .=  $row["na"] . '|@|';
									$this->gis_parametre .= $row["na_value"] . '|@|';
									$this->gis_parametre .= $this->na_min . '|@|';
									$this->gis_parametre .= 'alarm|@|';
									$this->gis_parametre .= $row["alarm_type"] . '|@|';
									$this->gis_parametre .= $row["family"] . '|@|';
									$this->gis_parametre .= $row["ta"] . '|@|';
									$this->gis_parametre .= $row["ta_value"] . '|@|';
									$this->gis_parametre .= $row["id_alarm"] . '|@|';
									$this->gis_parametre .= $this->module_application . '|@|';
									$this->gis_parametre .= "critical:".$this->alarmColor['critical'].";".
															"major:".$this->alarmColor['major'].";".
															"minor:".$this->alarmColor['minor']
															. '|@|';
									$this->gis_parametre .= '|@|';
									$this->gis_parametre .= '|@|';
									$this->gis_parametre .= '|@|';
									$this->gis_parametre .= '|@|';
									$this->gis_parametre .= $this->table_name_gis;
									// 24/07/2007 - Modif. benoit : ajout des infos na 3eme axe dans les parametres transmis au gis
									if (GetAxe3($row["family"],$this->product)) {
									    $this->gis_parametre .= '|@|'.$row["a3"];
									    $this->gis_parametre .= '|@|'.$row["a3_value"];
									}

									// 16/11/2007 - Modif. benoit : le GIS s'ouvre désormais dans une pop-up et non plus dans un div. On remplace donc la fonction 'top.showGIS()' par la fonction d'ouverture de pop-up
                                    // 20/06/2011 NSE : merge Gis without polygons : bug Gis non lié à la DE
									$gis_url = $niveau0."gis/index.php?gis_data=".$this->gis_parametre;

									// 26/05/2008 - maxime : On remplace l'url export_gearth_dash_alarm.php par export_file.php // Fichier intermédiaire permettant d'afficher un loading
                                                                        // 20/06/2011 NSE : merge Gis without polygons : bug Gis non lié à la DE
                                    // 04/07/2011 NSE bz 22870 : mauvaise légende dans Gis 3D, ajout de urlencode
									$gis_3D_url = $niveau0."gis/gis_scripts/export_file.php?gis_data=".urlencode($this->gis_parametre);

									// 19/11/2007 - Modif. benoit : definition de la taille de la fenêtre du GIS (la largeur vaut deux fois la hauteur)
									$gis_side	= $this->db->getone("SELECT gis_side FROM sys_gis_config_global");
									if ($gis_side)	$side = $gis_side;
									else			$side = 400;

									$fonction_gis = "ouvrir_fenetre('$gis_url','MapView','yes','yes',".($side*2).",$side);return false;";
                                    //10/10/2012 MMT DE GIS 3D only redimentionne la popup 3D
									$fonction_gearth = "ouvrir_fenetre('$gis_3D_url','DisplayGIS3D','yes','yes',400,110);return false;";

									//$fonction_gis = "top.showGIS(encodeURIComponent('".$this->gis_parametre."'));";

									if ( $this->debug )
									{
										// maj 02/06/2009  MPR : Ajotu du produit dans les paramètres du GIS
										$alt_gis = " prod =  ".$this->product;
										$alt_gis = " na =  ".$row["na"];
										$alt_gis .= " \n na_value =  ".$row["na_value"];
										$alt_gis .= " \n na_base =  ".$this->na_min;
										$alt_gis .= " \n data_type =  alarm ";
										$alt_gis .= " \n mode =  ".$row["alarm_type"];
										$alt_gis .= " \n famille =  ".$row["family"];
										$alt_gis .= " \n ta =  ".$row["ta"];
										$alt_gis .= " \n ta_value =  ".$row["ta_value"];
										$alt_gis .= " \n id_data_type =  ".$row["id_alarm"];
										$alt_gis .= " \n module =  ".$this->module_application;
										$alt_gis .= " \n alarm_color = ".
														"critical:".$this->alarmColor['critical'].";".
														"major:".$this->alarmColor['major'].";".
														"minor:".$this->alarmColor['minor'];
										$alt_gis .= " \n table_name =  ".$this->table_name_gis;
										// 24/07/2007 - Modif. benoit : ajout des lignes concernant le 3eme axe dans le debug du gis
										if (GetAxe3($row["family"],$this->product)) {
										    $alt_gis .= " \n na axe3 :".$row["a3"];
										    $alt_gis .= " \n na_value axe3 :".$row["a3_value"];
										}
									}
									else
									{
										// 16/11/2007 - Modif. benoit : utilisation de la fonction '__T()' pour définir le tooltip du bouton du GIS

										$alt_gis = (strpos(__T('U_TOOLTIP_DASH_ALARM_DISPLAY_GIS'), "Undefined") === false) ? __T('U_TOOLTIP_DASH_ALARM_DISPLAY_GIS') : "Display GIS";

										//$alt_gis = 'Display GIS';
									}
									// 09/10/2012 ACS DE GIS 3D ONLY
									if (GisModel::getGisMode($this->product) == 1) {
                                        echo '<img src="'.$niveau0.'images/icones/gis_link_alarme.gif" title="'.$alt_gis.'"" alt="'.$alt_gis.'"" style="cursor:pointer;" onclick="'.$fonction_gis.'"/>';
									}
									// maj 17:17 25/01/2008 - maxime : ajout d'un lien vers GIS 3D ( Google Earth )
									echo '<img src="'.$niveau0.'images/icones/gis_link_alarme_g_earth.gif" title="Display GIS 3D" alt="Display GIS 3D" style="cursor:pointer;" onclick="'.$fonction_gearth.'"/>';

								}

						echo	"
								</td>
							</tr>
						";

						$element_precedent = $element;
						$calculation_time_precedent = $row["calculation_time"];
					}
				} else {
					echo "<tr><td><i>No result.</i></td></tr>";
				}
			echo "</table>";
		echo "</div>";


		echo "</div>";
	}


	/*
		affiche le lien vers le top de la page des alarmes.
	*/
	function displayGoToTheTop(){
		global $niveau0;

		echo "
			<div id='to_the_top'>
				<a href='#top' class='texteGris' style='font : normal 9pt Verdana, Arial, sans-serif;'>
				<img src='".$niveau0."images/icones/top_alarme.gif' style='border:none'/>&nbsp;
				Top.
				</a>
			</div>
		";
	}

	/*
		Liste les Time aggregation active :
		> les labels sont affichés avec des checkbox.
		> un bouton 'refresh' permet de mettre-à-jour l'affichage (rechargement de la page).
	*/
	function displayTaSelection(){

		global $niveau0;

		//
		// 23/06/2009 BBX : Récupération des paramètres passés via l'URL
		list($scriptName,$parameterList) = explode('?',$_SESSION["url_alarme_courante"]);
		$parameterArray = Array();
		$urlValues = explode('&',$parameterList);
		foreach($urlValues as $oneValue) {
			list($parameter,$value) = explode('=',$oneValue);
			$parameterArray[$parameter] = $value;
			//echo '<input type="hidden" name="'.urldecode($parameter).'" value ="'.urldecode($value).'" />'."\n";
		}

		echo "
		<div class='texteGris' id='choix_ta' >
			<form action='".$scriptName."?timestamp_alarme=$this->timestamp' method='GET' style='margin:0px'>
				<input type='hidden' name='product' value='$this->product' />\n
				<input type='hidden' name='id_menu_encours' value ='".$parameterArray['id_menu_encours']."' />\n";


		//

		// On stocke le timestamp si il y en a un et le sous mode des alarmes.
		if ( $this->timestamp != '' )
			echo "<input type='hidden' name='timestamp_alarme' value='".$this->timestamp."'/>";

		echo "<input type='hidden' name='sous_mode' value='".$this->sous_mode."'/>";

		echo "<table><tr><td>";
			foreach($this->ta_array as $ta=>$ta_label){
				$checked = '';
				if($this->ta == ''){
					$checked = " checked='checked' ";
				} else {
					$tab_ta = explode(',',str_replace("'","",$this->ta));
					$tab_ta = array_flip($tab_ta);
					$checked = (isset($tab_ta[$ta])) ? " checked='checked' " : "";
				}
				echo "<label for='cb_$ta'>$ta_label</label>";
				echo "<input type='checkbox' id='cb_$ta' value='$ta' name=\"cb_ta[]\" $checked />";
				echo "&nbsp;&nbsp;&nbsp;";
			}
		echo "</td>";

		// selection des NA
		// 24/09/2009 BBX : modification des classes CSS pour l'icone de selection des NA. BZ 11662
		// 02/12/2009 BBX : Ajout des instruction nécessaires pour le fonctionnement de "load favorites". BZ 11482
		// 08/12/2009 BBX : Déclaration de la variable JS family_name. BZ 11482
		echo "
			<td>
				<style type='text/css'>
					#img_select_na { height:16px; width:20px; cursor:pointer;}
					.bt_off { background: url({$niveau0}images/icones/select_na_on.png) left no-repeat;}
					.bt_on { background: url({$niveau0}images/icones/select_na_on_ok.png) left no-repeat;}
				</style>
		        <!-- 17/08/2010 MMT bz 16749 Firefox compatibility use getAttribute for popalt(alt_on_over) -->
                <!-- 13/09/2010 MMT bz 16749, manque fin de parenthèze dans l'appel -->
				<div id='img_select_na' class='bt_".($_GET['nel_selecteur']?'on':'off')."'
					onmouseover='popalt(this.getAttribute(\"alt_on_over\"));'
					onmouseout='kill()'
					alt_on_over='".__T('SELECTEUR_NEL_SELECTION')."'></div>
		</div>

				<link rel='stylesheet' href='".URL_NETWORK_ELEMENT_SELECTION."css/networkElementSelection.css' type='text/css'>
				<script type='text/javascript' src='".URL_NETWORK_ELEMENT_SELECTION."js/prototype/controls.js'></script>
				<script type='text/javascript' src='".URL_NETWORK_ELEMENT_SELECTION."js/networkElementSelection.js'></script>
				<script type='text/javascript' src='".URL_SELECTEUR."js/alarmes_NA.js'></script>

				<span style='padding:4px; padding-left:20px; padding-right:10px;'>
					<script>
						id_product	= '".$this->product."';
						family_name = '';
						url_selecteur_rep_php	= '".URL_SELECTEUR."php/';
						url_get_NA_session	= '".URL_SELECTEUR."php/get_alarmes_NA_session.php';
						url_get_NA_selected	= '".URL_SELECTEUR."php/get_NA_selected.php';
						setLinkToAjax('{$niveau0}reporting/intranet/php/affichage/');
						updateNaSelection('all_family');
					</script>
				</span>
			</td>
			";

			include_once(MOD_NETWORK_ELEMENT_SELECTION.'class/networkElementSelection.class.php');
			$neSelection = new networkElementSelection();
			$neSelection->setButtonMode('checkbox');

			// Initialisation du titre de la fenêtre.
			$neSelection->setWindowTitle(__T('SELECTEUR_NEL_SELECTION'));
			$neSelection->setDebug(0);

			// On initialise le bouton qui permet d'afficher l'iHM, ainsi que les classes css du bouton.
			$neSelection->setOpenButtonProperties('img_select_na', 'bt_on', 'bt_off');

			// 02/12/2009 BBX
			// Ajout de l'icône des favoris. BZ 11482
			$neSelection->addIcon(__T('U_SELECTEUR_LABEL_LOAD_FAVORITES'),'favorite_icon','loadFavoritesNetworkElements()');

			// FIN BZ 11482

			// On définit dans quel champ la sauvegarde sera effectuée.
			$neSelection->setSaveFieldProperties('nel_selecteur', $_GET["nel_selecteur"], '|s|', 0);

			// Définit les propriétés du bouton View current selection content (NB : si la méthode n'est pas appelée, le bouton n'est pas affiché).
			$neSelection->setViewCurrentSelectionContentButtonProperties(URL_SELECTEUR."php/get_NA_selected.php?product=$this->product&na=$na");

			// On ajoute des onglets.
			// pour l'instant, on prend TOUS les onglets présents dans le menu NA_level
			// on va chercher tous les na_levels du produit
				// 10/12/2009 - SLC - getNaLabelListForProduct ne semble helas pas nous renvoyer les bons na levels,
				//	je m'inspire donc de la requête de getNaLabelList... et la modifie pour coller à nos besoins
				// $na_levels_all = getNaLabelListForProduct('na','',$this->product);
				$sql = " --- get na levels
					SELECT DISTINCT t0.agregation_label, t0.agregation, t0.mandatory, t0.agregation_rank, t0.family , t0.axe
					FROM	sys_definition_network_agregation t0,
							sys_definition_group_table_network t1,
							sys_definition_group_table_ref t2
					WHERE t0.agregation IS NOT NULL
						AND t0.agregation<>''
						AND t0.on_off=1
						AND t1.id_group_table = t2.id_ligne
						AND t0.axe IS NULL
						AND t0.agregation = split_part( t1.network_agregation, '_', 1)
					ORDER BY t0.agregation_rank desc ";
				$na_levels_all = $this->db->getall($sql);

				// on reprend maintenant tous les na_levels en dédoublonant :
				$na_levels = array();
				foreach ($na_levels_all as $row)
					if (!array_key_exists($row['agregation'],$na_levels))
						$na_levels[$row['agregation']] = $row['agregation_label'];

				//__debug($na_levels_all);
				//__debug($na_levels);

			// on crée les tabs dans le NA level selector
			foreach ($na_levels as $na => $na_label)
				$neSelection->addTabInIHM($na,$na_label, URL_SELECTEUR."php/selecteur.ajax.php?action=6&idN=1&idT=$na&product=$this->product",URL_SELECTEUR."php/selecteur.ajax.php?action=5&idT=$na&product=$this->product");

			// Génération de l'IHM.
			$neSelection->generateIHM();
		?>
		</div>

		<script type="text/javascript">
			networkElementSelectionSaveHook()
		</script>
		<?php

		// maj 10/03/2008 christophe : on affiche une liste déroulante avec le choix de order by temporel.
		echo "
			<td>
				<span style='margin-left:20px;margin-right:20px;'>
					<select id='order_on' name='order_on' class='texteGrisPetit'>
						<option value='capture_time' ".(($this->order_on=='capture_time')? 'selected' : '').">
							".__T('G_ALARM_MANAGEMENT_ORDER_ON_CAPTURE_TIME_LABEL')."
						</option>
						<option value='calculation_time' ".(($this->order_on=='calculation_time')? 'selected' : '').">
							".__T('G_ALARM_MANAGEMENT_ORDER_ON_CALCULATION_TIME_LABEL')."
						</option>
					</select>
				</span>

				<input type='submit' value=''
					style=\"
						background-image: url('{$niveau0}images/icones/refresh.gif');
						background-repeat:no-repeat;
						background-color: '#FFFFFF';
						border:0px #000 solid;
						width:62px;
						height:16px;
						cursor:pointer;
					\"/>
			</td>
			</tr>
		</table>
		</form>
		</div>";


	}


	/*
		Génère le tableau stockant toutes les données à afficher.
	*/
	function getDataToDisplay()
    {
		// 24/07/2007 christophe : on récupère la conditon 'where' pour filtrer sur les éléments réseaux.
		//$this->listOfNetworkElementQuery = $this->getListOfNetworkElementQuery($this->list_of_network_element);

		foreach( $this->_listSeuils as $seuil )
        {
            // Construction, exécution et stockage de la requête.
			$this->tab_data_seuil[$seuil] = $this->executeQuery( $this->getAlarmQuery( $seuil ) );

			// On stocke le nombre de résultats par seuil.
            if( $this->tab_data_seuil[$seuil] != "" ){
                $this->nb_resultats_total_seuil[$seuil] = count( $this->tab_data_seuil[$seuil] );
            }

			/*
				On va chercher si il existe des alarmes avec trop de résultats.
				Ce n'est pas la peine de créer un objet pour chaque seuil car cela ne dépend pas des seuils donc
				je ne l'exécute qu'une seule fois.
			*/
			if ( $seuil == self::CRITICAL_TRESHOLD )
            {
                $alarmLogError_parameters["product"]            = $this->product;
				$alarmLogError_parameters["query_timestamp"]    = $this->condition_timestamp;
				$alarmLogError_parameters["query_ta"]           = $this->condition_ta ." ".$this->condition_ta_value;
				$alarmLogError_parameters["mode"]				= $this->mode;
				$alarmLogError_parameters["sous_mode"]			= $this->sous_mode;

				$this->alarmes_trop_de_resultats = new alarmLogError($alarmLogError_parameters);
				if ($this->debug_too_much_result)
					echo "<br><u>Requêtes des alarmes qui ont trop de résultats</u><br>".$this->alarmes_trop_de_resultats->query."<br>";
			}
		}
	}

	/*
		Génère l'affichage d'un mode précis :
		- 'management' : écran alarm mangement avec 2 sous modes possibles :
			* 'condense' : 	on affiche la liste des alarmes et leurs résultats.
			* 'elem_reseau' :	on affiche les alarmes par éléments de réseau.
		- 'history' : affiche l'écran alarm history.
	*/
	function generateDisplay(){
		global $niveau0;

		$this->ta_array			= getTaList('',$this->product);			// chargement du tableau contenant la liste des TA/TA_label
		$this->family_array		= getFamilyList($this->product);		// chargement du tableau contenant la liste des family/family_label
		$this->na_label_array	= getNaLabelList('','',$this->product);	// chargement du tableau contenant la liste des  na/na_label mais PAs les na_value !!!!!! seulement les nom des network aggregation

		echo "<div id='top'>";
            // 13/09/2010 OJT : Correction bz16765 pour DE Firefox, modification width et margin
			echo "<div style='text-align:left;width:970px;margin:auto;'>";

				if($this->mode == "management"){
					// Affichage du choix de la TA.
					$this->displayHourSelect();
					// Affichage de la sélection de la TA.
					$this->displayTaSelection();
				}

				// Affichage de l'en-tête (expand all, quick linls et valeur courante de time).
				// 24/08/2007 christophe : ajout du timestamp dans l'url de collapse / axpand all.
				echo "<div id='en_tete' class='texteGris'>";
					// affichage de EXPAND / COLLAPSE ALL
					echo "<a href='$this->url_redirection&display_mode=none&timestamp_alarme=$this->timestamp' style='font : normal 9pt Verdana, Arial, sans-serif;'>Collapse All</a>";
					echo "&nbsp; / &nbsp;";
					echo "<a href='$this->url_redirection&display_mode=block&timestamp_alarme=$this->timestamp' style='font : normal 9pt Verdana, Arial, sans-serif;'>Expand All</a>";
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
					// Affichage des QUICK LINKS
					echo "<a href='#critical' class='texteGris' style='font : normal 9pt Verdana, Arial, sans-serif;'>Critical</a> &nbsp;&nbsp;&nbsp;";
					echo "<a href='#major' class='texteGris' style='font : normal 9pt Verdana, Arial, sans-serif;'>Major</a> &nbsp;&nbsp;&nbsp;";
					echo "<a href='#minor' class='texteGris' style='font : normal 9pt Verdana, Arial, sans-serif;'>Minor</a> &nbsp;&nbsp;&nbsp;";

					/*
						Affichage du lien vers la popup des alarmes ayant trop de résultats si il y en a.
					*/
					if ($this->alarmes_trop_de_resultats->existsResults()) {
						$this->alarmes_trop_de_resultats->buildDisplay();
						$this->alarmes_trop_de_resultats->saveInBuffer();
						echo "&nbsp;&nbsp;";
						echo "<span class='texteRouge'
								style='cursor:pointer'
								onclick=\"javascript:ouvrir_fenetre('".$niveau0."reporting/intranet/php/affichage/alarm_log_error.php','comment','yes','yes',750,300);\"
								>";
						echo "<u>Warning : limit of maximum results exceeded.</u>";
						echo "</span>";
					}
				echo "</div>";
				if($this->mode == "management") {
					echo "<div style=\"text-align:right\" class='texteGris'><br>Acknowledge all alarms <img onclick=\"validateAllAlarms()\" src='" . $niveau0 . "images/icones/validate_alarme.gif' title='Acknowledge all alarms' alt='Acknowledge all alarms' style='cursor:pointer'/></div>";
				}
                foreach( $this->_listSeuils as $oneSeuil )
                {
                    $this->displayAlarms( $oneSeuil );
                    $this->displayGoToTheTop();
                }
			echo "</div>";

		echo "</div>";
	}
} // fin de la classe
?>
