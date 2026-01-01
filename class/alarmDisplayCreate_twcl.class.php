<?
/**
 * @cb504@
 *
 * 14/09/2010 OJT : Correction bz17819, ajout de la propriété title aux images
 * 03/02/2011 NSE bz 20445 : tri des alarmes par ordre aplhabétique
 * 02/11/2011 ACS BZ 23284 Can not create right menu (reload, pdf, word, excel) by right click on the page in the same session when change family
 * 03/11/2011 ACS BZ 23927 Network Aggregation field has not displayed how many alarm results/ Time Aggregation
 * 22/11/2011 ACS BZ 21554 does not work with SGSN and All PLMNs
 * 16/11/2012 MMT bz 30276 ajout gestion top-worst pour DE GIS 3D only
 */
/*
*	@cb41000@
*
*	13/11/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
	- maj 13/11/2008 : MPR 	- séparation du paramètre na_box du paramètre na
						- modification des tables de référence
						- dans le cas où l'on affiche les enfants => modification de la requête
	- maj 19/11/2008 - MPR : 	- Réécriture de la fonction getListOfNetworkElementQuery 
						- ajout de la fonction getAgregPath qui récupère tous les niveaux d'agrégation enfant du niveau d'agrégation sélectionné
						   Cette fonction remplace la fonction getListeNaEnfant ( supprimée )
						- ajout de la fonction createQueryGetElementsChild qui génère les requêtes 
						   Cette fonction retourne les éléments réseau enfants des éléments réseau sélectionnés
						 - modification de la colonne qui possède les données 3ème axe dans edw_alarm
						 - Fonction convertDate qui reformate les dates en fonction de la ta ( nécessaire pour les envoyer dans $_GET pour conserver les paramètres du sélecteur)
						 - Ajout des paramètres du sélecteur dans l'url des liens 'colapse All' , 'Expand All' et 'Change Family'
	- maj 03/12/2008 - SLC	- gestion multi-produit 
	- maj 16/01/2009 - MPR : 	- Ajout du paramètre produit dans l'appel de la fonction get_sys_global_parameters
	- 					- Ajout du produit pour récupérer le séparateur axe3
	- maj 02/06/2009  MPR : Ajotu du produit dans les paramètres du GIS
	
	- 17/06/2009 BBX : passage de l'id produit dans l'url du détail des alarmes. BZ 9708

	20/07/2009 GHX
		- Ajout de l'id produit dans l'url des dashboards associés
	maj 24/08/2009 - MPR : Correction du bug 11011 : Utilisation du fichier export_file.php à la place de gis_manager*
	
	01/09/2009 GHX
		- Correction du BZ 11272 [REC][T&A CB 5.0][INVESTIGATION DASHBOARD]: caractères accentués sous forme de carrés
		- Correction du BZ 11320 [CB 5.0][Top/Worst List] les niveaux d'aggrégations ne sont pas affichés pour un slave
		
*
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
*	- maj 10/03/2008 christophe : ajout du lien vers l'export google earth
	- maj 16/11/2007, benoit : le GIS s'ouvre désormais dans une pop-up et non plus dans un div. On remplace donc la fonction 'top.showGIS()'    par la fonction d'ouverture de pop-up
	- maj 16/11/2007, benoit : utilisation de la fonction '__T()' pour définir le tooltip du bouton du GIS
	
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

	- maj 10/08/2007, benoit : changement du scenario du selecteur dans le lien "Change family"

	- maj 09/08/2007, jérémy : ajout d'une condition pour afficher l'icone de retour au choix des familles. Si le    nombre de famille est supérieur à 1 on affiche l'icône, sinon, on la cache

	- maj 03/08/2007, benoit : '$this->table_name_gis' n'existe pas dans cette classe. Comme celle-ci ne traite      qu'un seul type d'alarme, on force la valeur "sys_definition_alarm_top_worst" quand cela est nécessaire

	- maj 01/08/2007, christophe : mise-à-jour du lien vers le gis.
	- maj 01/08/2007, christophe : on liste les na de la famille principale (sert pour l'affichage du gis).
	- maj 01/08/2007, christophe : ajout des méthode pour gérer le filte sur les éléments réseaux
	  > NOTE : rapide copier /coller de la classe alarmDisplayCreate >> penser à faire un héritage puis redéfinir
	  certaines méthodes si temps.

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
*	- maj 22 01 2007 christophe : branchement du nouveau gis.
*
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
	Permet de créer l'affichage des alarmes Top/Worst cell list.

	- maj 24 08 2006 christophe, ligne 77/91/130 : ajout de la sélection de la na dans la requête. (iil manquait la condition)
	- maj 18 09 2006 christophe : affichage du lien vers le gis pour la fmaille proncipale.
	- maj 29 09 2006 christophe : ajout de l'oid dans le order by qui sélectionne la liste des résultats pour qu'ils s'affichent dna sle bon ordre.
	- maj 25 10 2006 , christophe : correction d'un bug > si gis = 0 & gis_alarm=1, le gis n'est pas activé.
	- maj 13/04/2007 Gwénaël
		modificition de la fonction get_na_label (anciennement getNaLabel, le nom a été changé pour ne par préter à confusion avec celle du fichier edw_function.php)
		>> prise en compte du troisième axe dans les liens vers les dashboards  << SUPRESSION DES MODIFS en commentaires !!!!!!!!
*/

Class alarmDisplayCreate_twcl{
	/*
		Paramètre du constructeur de classe.
		$database_connection	paramètres de connexion à la base de données. -- OBSOLETE
		$na					network_aggregation.
		$ta					time_aggregation, nom de la ta
		$ta_value				Valeur de la time aggregation.
		$display 				Affichage expand all ou collapse (valeur = 'block' ou none)
		$family				famille.
	*/

	var $na;
	var $ta;
	var $ta_value;
	var $display;
	var $family;
	var $query_select;
	var $query_from;
	var $query_where;
	var $query_order_by;

	var $tabData;
	var $nbResults;

	/**
	 *
	 * @param integer $product : Id du produit :
	 * @param string $na :
	 * @param string $na_axe3 :
	 * @param string $ta :
	 * @param string $ta_value :
	 * @param string $display :
	 * @param string $family :
	 */
	function alarmDisplayCreate_twcl($product, $na, $na_axe3, $ta, $ta_value, $display, $family){

		$this->debug	= get_sys_debug('alarm_twcl'); // Permet d'activer / désactiver l'affichage du  mode débug.
		$this->db		= DataBase::getConnection( $product );
		$this->na 		= $na;
		$this->na_axe3 	= $na_axe3;
		$this->ta 		= $ta;
		$this->ta_value = $ta_value;
		$this->display 	= $display;
		$this->family 	= $family;
		$this->product	= $product;
		
		// maj 13/11/2008 - MPR : Modification des séparateurs dans la chaine contenant les éléments réseau sélectionnés
		$this->separateur_values 	= "||";
		$this->separateur_elements 	= "|s|";
		
		// Paramètres de la table sys_global_parameters.
		// maj 16/01/2009 - MPR : Ajout du paramètre produit dans l'appel de la fonction get_sys_global_parameters
        // 20/06/2011 NSE : merge Gis without polygons
        // utilisation de GisModel
		//$this->gis 				=	get_sys_global_parameters("gis",0,$product);
		// si = 1 alors on affiche l'icône de liaison vers le gis.
		//$this->gis_alarm 		=	get_sys_global_parameters("gis_alarm");	
		// si = 1 alors on affiche l'icône de liaison vers les dashboards.		
		$this->dashboard_alarm 	=	get_sys_global_parameters("dashboard_alarm");
		// si = 1 alors on affiche l'icône de liaison vers les trouble ticket.
		$this->trouble_ticket 	=	get_sys_global_parameters("trouble_ticket_alarme");	

		// Famille principale (utile pour le gis.)
		$this->main_family = get_main_family($this->product); // cette fonction se trouve dans php/edw_function_family.php

		// 01/08/2007 - christophe : on liste les na de la famille principale (sert pour l'affichage du gis)
		$this->na_in_main_family		= getNaLabelList('na', $this->main_family,$product);
		$this->list_of_na_main_family	= array_keys($this->na_in_main_family[$this->main_family]);

		// Couleurs de lignes de résultats.
		$this->couleur_fond1 = "#f2f2f2";	// couleur de la 1ère ligne
		$this->couleur_fond2 = "#d3d3d3";

		// Paramètrage des couleurs des alarmes (ajouté pour être passé en param au gis mais ne sert pas pour le display des top_worst_list).
		$this->alarmColor['critical']	= get_sys_global_parameters("alarm_critical_color");
		$this->alarmColor['major']	= get_sys_global_parameters("alarm_major_color");
		$this->alarmColor['minor']	= get_sys_global_parameters("alarm_minor_color");

		$this->na_min = get_network_aggregation_min_from_family($this->family,$this->product);

		//Paramètres nécessaires à l'appel du GIS.
		$this->module_application = get_sys_global_parameters('module');

		// Tableau de session contenant toutes les requêtes d'affichage.
		if (isset($_SESSION['queries']))
			unset($_SESSION['queries']);

		// 01/08/2007 christophe : intégration de la sélection des éléments réseaux.
		$this->list_of_network_element = ''; // initialisé via un setter.
		// 01/08/2007 christophe, ajout de la variable main_family_na_max.
		$this->main_family_na_max = get_network_aggregation_max_from_family($this->main_family,$this->product); // cf edw_function_family
	}

	/**
	*	01/08/2007 christophe : ajout des méthode pour gérer le filte sur les éléments réseaux
	*	Setter de $this->list_of_network_elements
	*/
	function setListOfNetworkElement($liste)
	{
		$this->list_of_network_element = $liste;
	}

	/**
	* 01/08/2007 christophe : ajout des méthode pour gérer le filte sur les éléments réseaux
	* Retourne la famille à laquelle appartient la NA $na passée en paramètre :
	* si elle est présente dans la famille principale
	*@param $na niveau d'agrégation
	*/
	function getFamilyOfNa($na)
	{
		$family_return = '';
		$liste_na_main_family_tab = $this->na_in_main_family[$this->main_family];
		// Si la NA est présente dans la famille principale, on retourne la fmaille principale.
		if( isset($liste_na_main_family_tab[$na]) )
		{
			$family_return = $this->main_family;
		}
		// Sinon on vat chercher la famille de la NA
		else
		{
			$q = " SELECT family FROM sys_definition_network_agregation WHERE agregation='$na' ";
			$result_fam = $this->db->getone($q);
			if ($result_fam)
				$family_return = $result_fam;
		}
		return $family_return;
	}

	// maj 19/11/2008 - MPR : ajout de la fonction getAgregPath qui récupère tous les niveaux d'agrégation enfant du niveau d'agrégation sélectionné
	/**
	 * Retourne le chemin d'agrégation d'un niveau
	*
	* MPR 19/11/2008
	* @since cb4.1.0.00
	* @version cb4.1.0.00
	* @param string $family : famille
	* @param string $level : niveau dont on veut connaître le chemin jusqu'au
	* @return array : tableau contenant les niveaux d'agrégations depuis $level jusqu'au niveau minimum
	*/
	private function getAgregPath($level) 
	{
		// Récupération du niveau d'agrégation réseau minimum
		$family_min_net = get_network_aggregation_min_from_family($this->family,$this->product);
		
		// Récupération du chemin			
		$query = "SELECT * FROM get_path('{$level}','{$family_min_net}','{$this->family}');";
		$result = $this->db->getAll($query);

		$array_result = Array();
		foreach ($result as $row)
			$array_result[] = $row['get_path'];
		
		// Sauvegarde du résultat dans l'objet pour éviter de rééxécuter les requêtes si on cherche de nouveau les mêmes informations
		$agregPathArray[$this->family][$level] = array_reverse($array_result);
		
		return $agregPathArray[$this->family][$level];		
	}
	
	//maj 19/11/2008 - MPR : 	- Réécriture de la fonction getListOfNetworkElementQuery 
	/**
		@param $liste chaine de caractère contenant la liste des éléments réseaux sélectionnés via l'interface 'network element selection'.
		Le format de cette chaine est na||na_value||1 ou 0... (0 ou 1 indique si l'on va rechercher les enfants de l'élément réseau)
		| : séparateur entre 2 éléments réseaux.;
		@ : séparateur de sinformations sur un élément réseau :
			na : niveau d'agrégation de l'élément réseau
			na_value : valeur de l'élément réseau
			na_label : label de l'élément réseau
	*/
	function getListOfNetworkElementQuery($liste)
	{
		
		if ( !empty($liste) )
		{
			$conditions_to_return = 'AND ('; // chaine à retourner
			$main_family_na_max_selected = false; // définit si l'utilisateur a sélectionné le plus haut niveau de la fmaille principale

			/*
				étape 1 : on construit la condition pour sélectionner les résultats des éléments réseaux cochés par l'utilisateur.
				> NB : on est obligé de faire des 'LIKE' car il faut pouvoir gérer les valeurs des familles qui ont un 3ème axe et celles qui n'en ont pas.
			*/
			$liste_tab = explode($this->separateur_elements, $liste);
		
			$tab_na_to_child = array();
			
			foreach ( $liste_tab as $key=>$element_reseau_chaine )
			{
				// On éclate la chaine na||na_value||desc_na
				$element_reseau_tab = explode($this->separateur_values,$element_reseau_chaine);
				
				// On initialise la variable $main_family_na_max_selected à true afin de supprimer toute condition sur les éléments réseau pour le na network
				if( $element_reseau_tab[0] == $this->main_family_na_max )
					$main_family_na_max_selected = true;
				
				
				// On ajoute la condition sur les éléments réseau sélectionnés
				$conditions_to_return .= ' ( t2.na_value = \''.$element_reseau_tab[1].
					'\' AND t2.na = \''.$element_reseau_tab[0].'\') OR ';
			
				// Initialisation des éléments réseau où l'on va recherché leurs enfants
				if($element_reseau_tab[2] and $element_reseau_tab[0] !== $this->main_family_na_max)
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
					$liste_na_enfants = $this->getAgregPath($na);
					// 22/11/2011 ACS BZ 21554 Call to static method in alarmDisplayCreate.class.php
					$get_list_enfants = alarmDisplayCreate::createQueryGetElementsChild($tab_na_to_child[$na],$liste_na_enfants);
					
					// __debug($get_list_enfants,"queries");
					
					// 22/11/2011 ACS BZ 21554 Manage correctly the "OR" condition if na has no child
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
		/*
			> si l'utilisateur a sélectionné le plus haut niveau de la famille principale, la chaine retournée sera vide
			car on doit sélectionner tous les éléments réseaux.
		*/
		if ( $main_family_na_max_selected ) $conditions_to_return = '';

		if ( $this->debug ) __debug($conditions_to_return,'Condition sur les éléments réseaux sélectionnés');

		return $conditions_to_return;
	}


	/**
	 * Construit la requête permettant d'afficher les TWCL
	 *		> On affiche la liste pour chaque alarme.
	 *
	 * @return string
	 */
	function getAlarmQuery(){

		$condition_ta 		= 	"";
		$condition_axe3 	= 	"";
		$condition_ta_value = 	"";
		$condition_order_by = 	"";
		$condition_na = $this->getListOfNetworkElementQuery($this->list_of_network_element);

		/*
			Constrution de la requête
		*/


		// Si l'utilisateur souhaite afficher une / des ta précise.
		if($this->ta != ''){
			$condition_ta = " AND t2.ta = '".$this->ta."' ";
		}
		// Si l'utilisateur souhaite affiche une ta_value précise.  >> utilisé dans le cas où il y a un sélecteur dans la page
		if($this->ta_value != ''){
			$condition_ta_value = " AND t2.ta_value = '".$this->ta_value."' ";
		}


		// Condition sur la famille.
		if($this->family != ''){
			$condition_family = " AND t1.family='".$this->family."' ";
		
		}
		
		// Condition sur le niveau d'agrégation 3ème axe
		// maj MPR : modification de la colonne qui possède les données 3ème axe dans edw_alarm
		if (GetAxe3($this->family,$this->product))
			$condition_axe3 = " AND a3 = '$this->na_axe3' ";

                // 03/02/2011 NSE bz 20445 : tri des alarmes par ordre aplhabétique
		$condition_order_by = "
			ORDER BY
				t2.calculation_time DESC,
                                alarm_name,
				t2.id_alarm,
				t2.critical_level,
				t2.oid ASC
		";

		// Clause select de la requête.
		$this->query_select = "
			SELECT
				DISTINCT t2.oid, t2.*,alarm_name, family
		";
		// Clause from de la requête.
		$this->query_from = "
			FROM edw_alarm t2, sys_definition_alarm_top_worst t1
		";
		// Clause where de la requête.
		// modif 12/12/2007 Gwen
			// Ajout de la condition visible = 1
		$this->query_where = "
			WHERE
				alarm_type='top-worst'
				AND t2.id_alarm = t1.alarm_id
				AND visible = 1
				$condition_family
				$condition_ta
				$condition_ta_value
				$condition_na
				$condition_axe3
		";
		// Clause order by de la requête.
		$this->query_order_by = "
			$condition_order_by
		";

		$query = 	$this->query_select .
					$this->query_from .
					$this->query_where .
					$this->query_order_by;

		// cette variable de session est utilisée pour générer le fichier PDF.
		$_SESSION['queries']['twcl']['query_select'] = 		$this->query_select;
		$_SESSION['queries']['twcl']['query_from'] = 		$this->query_from;
		$_SESSION['queries']['twcl']['query_where'] = 		$this->query_where;
		$_SESSION['queries']['twcl']['query_order_by'] = 	$this->query_order_by;

		if($this->debug) echo $query;

		return $query;
	}

	/**
	 * Renvoie le label d'une network agrégation donnée suivi du niveau d'agrégation entre crochet. S'il y a un troisième axe, le valeur du 3° axe et son niveau sera aussi renvoié
	 * dans à la suite du premier network.
	 *
	 *	- maj 13/04/2007 Gwénaël
	 *		- Modification de la function pour prendre en compte le troisième axe, et rependre la function getNaLabel du fichier edw_function.php
	 *		- Renomage de la fonction getNaLabel en get_na_label (pour ne pas préter à confusion avec celle du fichier edw_function.php)
	 *	- maj 17/04/2007 Gwénaël
	 *		- NA entre crochet
	 *		- Suppression de l'utilisation de la fonction get_axe3($family)
	 *	- maj 03/12/2008 SLC
	 *		- gestion multi-produit
	 *
	 * exemple : sai 1[SAI]                              << Pas de troisième axe
	 *                  sai 2[SAI] - tos 1[TOS]        << Présence d'un troisième axe
	 *
	 * @param sting $na_value : valeur du network agrégation
	 * @param sting $na            : niveau du network agrégation
	 * @param sting $family     : nom de la famille à laquelle appartient le nework agrégation
	 *
	 * @return string
	 */
	function get_na_label($na_value,$na_valueAxe3,$na,$family){
	
		$monAxe3 = "";
		
		$na = $na;
		if (isset($this->na_axe3) and $this->na_axe3 !== "") $naAxe3 = $this->na_axe3;
		
		if (isset($na_valueAxe3) && isset($naAxe3))
			$monAxe3 = " - " . getNaLabel($na_valueAxe3, $this->na_axe3,  $family, $this->product) . "[" . $this->na_label_array[$family][$naAxe3]. "]";
		
		return getNaLabel($na_value, $na,  $family, $this->product) . "[" . $this->na_label_array[$family][$na] . "]" . $monAxe3;
	}

	/**
	 * executeQuery $query : requête à exécuter.
	 * retourne un tableau contenant la liste des éléments à afficher.
	 *
	 * @param string $query
	 *
	 * @return array
	 */
	function executeQuery($query){
		
		$this->nbResults = array();
		
		$result = $this->db->getAll($query);
		
		if ($result) {
			foreach ( $result as $i=>$row ){

				$this->tabData[$i]["alarm_name"] = 		$row["alarm_name"];
				$this->tabData[$i]["family"] = 			$row["family"];
				$this->tabData[$i]["oid"] = 				$row["oid"];
				$this->tabData[$i]["id_alarm"] = 		$row["id_alarm"];
				$this->tabData[$i]["id_result"] = 		$row["id_result"];
				$this->tabData[$i]["ta"] = 				$row["ta"];
				$this->tabData[$i]["ta_value"] = 		$row["ta_value"];
				$this->tabData[$i]["na"] = 				$row["na"];
				$this->tabData[$i]["na_value"] = 		$row["na_value"];
				$this->tabData[$i]["a3"] = 				$row["a3"];
				$this->tabData[$i]["a3_value"] = 		$row["a3_value"];
				$this->tabData[$i]["alarm_type"] = 		$row["alarm_type"];
				$this->tabData[$i]["critical_level"] = 	$row["critical_level"];
				$this->tabData[$i]["acknowledgement"] = 	$row["acknowledgement"];
				$this->tabData[$i]["calculation_time"] = $row["calculation_time"];
				$this->tabData[$i]["rank"] = 			$row["rank"];
				
				// 03/11/2011 ACS BZ 23927 Network Aggregation field has not displayed how many alarm results/ Time Aggregation
				$resultName = $row["id_alarm"]."_".$row["alarm_type"];
				if (isset($this->nbResults[$resultName])) {
					$this->nbResults[$resultName] ++;
				}
				else {
					$this->nbResults[$resultName] = 1;
				}
			}

		} else {
			$this->tabData = "";
		}
	}
	
	/**
	 *	Affichage de la liste des alarme.
	 */
	function generateDisplay(){

		$this->ta_array			= getTaList('',$this->product);		// chargement du tableau contenant la liste des TA/TA_label
		$this->family_array		= getFamilyList($this->product);	// chargement du tableau contenant la liste des family/family_label
		// 08:55 01/09/2009 GHX
		// Correction du BZ 11320
		$this->na_label_array	= getNaLabelList('all', '', $this->product);

		// Construction de la requête permettant l'affichage.
		$query = $this->getAlarmQuery();
		
		// Affichage de la liste des résultats.
		$this->executeQuery($query);
		$nb_total_resultats = ($this->tabData != "")? count($this->tabData) : 0;

		echo "<div id='alarm_screen'>";
			echo "<div style='text-align:left; width:970px;margin:auto;'>";
			
			//	On affiche un en_tête avec : le label de la na courante, la famille courante.
			// Titre
			$label_na = "";
			if($this->na !== ""){
				$label_na = "on ".$this->na_label_array[$this->na][$this->family];
			}
			echo "
			<div id='twcl_top' class='texteGris contenu'>
				<h1>
					<img src='".NIVEAU_0."images/icones/puce_fieldset.gif'/>&nbsp;
					Top / Worst Cell List $label_na, current family : ".$this->family_array[$this->family].".
					&nbsp;
					&nbsp;
					($nb_total_resultats results)

					&nbsp;
					&nbsp;
					&nbsp;";

			 // MàJ 09/08/2007 - JL :  Ajout condition d'affichage de l'icone
			if (get_number_of_family() > 1) {

				// 10/08/2007 - Modif. benoit : changement du scenario du selecteur dans le lien "Change family"
				$tab['date'] = $this->ta_value;
			
				$url_arg = "&date=".$tab['date']."&ta_level=$this->ta";
				
				// 02/11/2011 ACS BZ 23284 Can not create right menu (reload, pdf, word, excel) by right click on the page in the same session when change family
				$url = $_SESSION["url_alarme_courante"];
				$url = ereg_replace("(&?family=[a-zA-Z0-9]+&?)", '', $url); // remove information about family in the actual uri
				$lastVarSerie = strrpos($url, '?');	
				$url = substr($url, $lastVarSerie); // keep only last list of parameters
				
				echo "
					<a href='$url$url_arg'>
						<img src='".NIVEAU_0."images/icones/change_family.gif' style='border:none; cursor:pointer;'	/>
					</a>";
			} //fin condition sur les familles

			echo "
				</h1>
				<hr/>
			";
			// affichage de EXPAND / COLLAPSE ALL
			// maj MPR - Ajout des paramètres du sélecteur dans l'url des liens 'colapse All' , 'Expand All' et 'Change Family'
			echo "<div class='texteGris' style='margin-bottom:5px;'>";
			echo "<a href='".$_SESSION["url_alarme_courante"]."&family=".$this->family."&display_mode=none$url_arg_na' style='font : normal 9pt Verdana, Arial, sans-serif;'>Collapse All</a>";
			echo "&nbsp; / &nbsp;";
			echo "<a href='".$_SESSION["url_alarme_courante"]."&family=".$this->family."&display_mode=block$url_arg_na' style='font : normal 9pt Verdana, Arial, sans-serif;'>Expand All</a>";
			echo "</div>";

			/*
				Affichage du contenu.
			*/
			$header_tableau = Array('Calculation time','Time aggregation','Alarm Name',
						'Network aggregation', 'Options');

			$id_tableau = 'tableau_twcl';

			echo "<div>";
				echo "<table cellpadding='2' cellspacing='1' width='100%' id='$id_tableau'>";
					// Affichage du header du tableau.
					echo "<tr>";
						foreach($header_tableau as $titre) echo "<th>$titre</th>";
					echo "</tr>";

					if ($this->tabData != "") {

						$element_precedent = "";
						//$tab_nb_resultats = $this->getNbResultats($result_cpt,$nombre_resultat);
						$bg_color = $this->couleur_fond1;

						foreach ($this->tabData as $row) {

							$element = $row["id_alarm"]."_".$row["alarm_type"];

							// On ferme la balise tbody des enfants si on change d'alarme et que ce n'est pas la première ligne
							if ($element_precedent != $element && $element_precedent != ""){
								echo "</tbody>";
								if($bg_color == $this->couleur_fond1){
									$bg_color = $this->couleur_fond2;
								} else {
									$bg_color = $this->couleur_fond1;
								}
							}

							// Affichage le nom de l'alarme.
							if($element_precedent != $element){
								$id = $element."_".$row["ta_value"];
								echo "
									<tr id='$id'
										style='cursor:default;font : normal 9pt Verdana, Arial, sans-serif;background-color:$bg_color;'
										onmouseover=\"surligner('$id');\"
										onmouseout=\"surligner('$id');\"
									>
									";

									// Affichage du time calculation et de l'icône plus / moins.
                                    // 13/09/2010 OJT : Correction bz17829
									$img = ($this->display == 'none') ? 'plus_alarme.gif' : 'moins_alarme.gif' ;
									echo "<td
                                            title='Expand/Collapse'
                                            alt='Expand/Collapse'
											style='cursor:pointer;'
											onclick=\"Element.toggle('".$id."_child');  change_img('".$id."_img')\" >"	;
									echo "<img
												id='".$id."_img'
                                                alt='Expand/Collapse'
												title='Expand/Collapse'
												src='".NIVEAU_0."images/icones/$img' style='cursor:pointer'
												/>";
									echo "&nbsp;".$row["calculation_time"];
                                                                         


									echo "</td>";

									// Affichage de la TA/TA_VALUE.
									echo "<td>".getTaValueToDisplay($row["ta"], $row["ta_value"])."[".$this->ta_array[$row["ta"]]."]</td>";

									// Affichage de la NA / ou nom alarme
									echo "<td>".$row["alarm_name"];
										// Affichage du 3ème axe si présent.
										
										if($row["a3_value"] !== "" and $row["a3_value"] !== null){
											echo " [HN=".$this->getAxe3Label($row["family"])."] ";
										}
									echo "</td>";

									// 03/11/2011 ACS BZ 23927 Network Aggregation field has not displayed how many alarm results/ Time Aggregation
									echo "<td>".$this->nbResults[$row["id_alarm"]."_".$row["alarm_type"]]." ".$this->na_label_array[$this->family][$row["na"]]." / ".$this->ta_array[$row["ta"]]."</td>";
									echo "<td>&nbsp;</td>";

								echo "</tr>";
							}

							// Affichage de la liste des  NA de l'alarme.
							$id_tbody = $element."_".$row["ta_value"].'_child';
							$id_tr = $id_tbody."_".$row["oid"];
                            // 13/08/2010 OJT DE Firefox : si on utilise "display: block" : les cellules ne sont pas alignées avec celles du dessus.
							if($element_precedent != $element) echo "<tbody id='$id_tbody'".($this->display=='none'?' style="display:none;"':'')."'>";

							$label_element = $this->get_na_label($row["na_value"],$row["a3_value"],$row["na"],$this->family);
							
							// 08:43 01/09/2009 GHX
							// correction du BZ 11272
							$label_element = utf8_encode($label_element);
							
							echo "
								<tr id='$id_tr'
									bgcolor='$bg_color'
									onmouseover=\"surligner('$id_tr');\"
									onmouseout=\"surligner('$id_tr');\" >
									<td bgcolor='#FFFFFF'>&nbsp;</td>
									<td bgcolor='#FFFFFF'>&nbsp;</td>
									<td bgcolor='#FFFFFF'>&nbsp;</td>
									<td> $label_element </td>
									<td>
								";

									// Icone pour afficher les détails de l'alarme dans une popup.
									// 17/06/2009 BBX : passage de l'id produit dnas l'url. BZ 9708
									echo "
										<img src='".NIVEAU_0."images/icones/plus_alarme.gif'
											onclick=\"ouvrir_fenetre('alarm_detail.php?oid_alarm=".$row['oid']."&product=".$this->product."','alarm','yes','yes',700,600)\"
                                            alt='View details'
                                            title='View details'
											style='cursor:pointer;'
										/>
										";

									// Affichage des trouble ticket.
									if($this->trouble_ticket){
										echo "
											<img
                                                src='".NIVEAU_0."images/icones/liste_alarme.gif'
                                                alt='Trouble ticket list'
                                                title='Trouble ticket list'
												onclick=\"ouvrir_fenetre('network_aggregation_trouble_ticket_list.php?na=".$row['na']."&na_value=".$row['na_value']."','alarm','yes','yes',450,350)\"
												style='cursor:pointer;'
											/>
											";
									}

									// Lien vers les dashboards.
									if($this->dashboard_alarm){
										$type_alarm_tmp = "top-worst"; // à voir avec cyrille.
										$param_dashboard = "alarm_type=".$type_alarm_tmp;
										$param_dashboard .= "&id_alarm=".$row["id_alarm"];
										$param_dashboard .= "&na_value=".$row["na_value"]."&na=".$row["na"];
										// 15:35 20/07/2009 GHX
										// Ajout de l'id produit
										$param_dashboard .= "&product=".$this->product;
										// modif 13/04/2007 Gwénaël
                                        //modification des liens vers les dashboards pour prendre en compte le troisième

                                        // 23/09/2010 BBX
                                        // Ajout de l'élément 3ème axe + TA
                                        // BZ 18036
                                        $param_dashboard .= "&ta_value=".$row["ta_value"]."&ta=".$row["ta"];
                                        if(!empty($row["a3_value"]))
                                            $param_dashboard .= "&na3_value=".$row["a3_value"];
										
										echo "
											<img
                                                src='".NIVEAU_0."images/icones/dashboard_link_alarme.gif'
                                                alt='Display dashboard'
                                                title='Display dashboard'
												onclick=\"javascript:ouvrir_fenetre('".NIVEAU_0."reporting/intranet/php/affichage/dashboard_associe.php?$param_dashboard','comment','yes','yes',450,300);\"
												style='cursor:pointer;'
											/>
											";
									}
									// Lien vers le GIS.
									// 01/08/2007 - christophe: on affiche le bouton du gis si la na selectionnée fait partie des na de la famille principale (et non uniquement si la famille est la famille principale comme precedemment)
									// Si la famille est une famille 3eme axe, on extrait les valeurs du 3eme axe et le nom des na des 2 axes	
									// maj 16/01/2009 - MPR : Ajout du produit pour récupérer le séparateur axe3
									if (GetAxe3($row["family"],$this->product)) {
									    $tab_na_value    = explode(get_sys_global_parameters('sep_axe3'), $row["na_value"],$this->product);
									    $na_value_axe3    = $tab_na_value[1];

									    $tab_na        = explode('_', $row["na"]);
									    $na_axe1    = $tab_na[0];
									    $na_axe3    = $tab_na[1];
									}
									else
									{
									    $na_axe1 = $row["na"];
									}
									// 01/08/2007 christophe : mise-à-jour du lien vers le gis.
									// maj 16/06/2011 - MPR : Utilisation du model GisModel
                                    if( GisModel::linksToGisAvailable("alarm", $na_axe1, $this->product) )
									{
										// 02/06/2009  MPR : Ajotu du produit dans les paramètres du GIS
										$this->gis_parametre =  $this->product . '|@|';
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

										// 03/08/2007 - Modif. benoit : '$this->table_name_gis' n'existe pas dans cette classe. Comme celle-ci ne traite qu'un seul type d'alarme, on force la valeur "sys_definition_alarm_top_worst"

										$this->gis_parametre .= 'sys_definition_alarm_top_worst';
										// 24/07/2007 - Modif. benoit : ajout des infos na 3eme axe dans les parametres transmis au gis
										if (GetAxe3($row["family"],$this->product)) {
										    $this->gis_parametre .= '|@|'.$na_axe3;
										    $this->gis_parametre .= '|@|'.$na_value_axe3;
										}

										// 16/11/2007 - Modif. benoit : le GIS s'ouvre désormais dans une pop-up et non plus dans un div. On remplace donc la fonction 'top.showGIS()' par la fonction d'ouverture de pop-up
                                        // 20/06/2011 NSE : merge Gis without polygons : bug non lié à la DE
										$gis_url = NIVEAU_0."gis/index.php?gis_data=".$this->gis_parametre;

										// 19/11/2007 - Modif. benoit : definition de la taille de la fenêtre du GIS (la largeur vaut deux fois la hauteur)
										$req_gis = $this->db->getone("SELECT gis_side FROM sys_gis_config_global");
										if ($req_gis)	$side = $req_gis;
										else			$side = 400;

										$fonction_gis = "ouvrir_fenetre('$gis_url','MapView','yes','yes',".($side*2).",$side);return false;";
										// Lien vers Gearth
										// $gis_3D_url = NIVEAU_0."gis/gis_scripts/gis_manager.php?gis_data=".urlencode($this->gis_parametre)."&action=send_to_gearth_from_graph_alarm_results";
										
										// maj 24/08/2009 - MPR :  Correction du bug 11011 :  Utilisation du fichier export_file.php à la place de gis_manager
                                        // 20/06/2011 NSE : merge Gis without polygons : bug non lié à la DE
                                        // 16/11/2012 MMT bz 30276 ajout gestion top-worst pour DE GIS 3D only
										$gis_3D_url = NIVEAU_0."gis/gis_scripts/export_file.php?gis_data=".urlencode($this->gis_parametre);
										
										$fonction_gearth = "ouvrir_fenetre('$gis_3D_url','GoogleEarthView','yes','yes',300,40);return false;";

										//$fonction_gis = "top.showGIS(encodeURIComponent('".$this->gis_parametre."'));";
										if ( $this->debug )
										{
											$alt_gis = " na =  ".$row["na"];
											$alt_gis = " prod =  ".$this->product;
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

											// 03/08/2007 - Modif. benoit : '$this->table_name_gis' n'existe pas dans cette classe. Comme celle-ci ne traite qu'un seul type d'alarme, on force la valeur "sys_definition_alarm_top_worst"
											$alt_gis .= " \n table_name =  sys_definition_alarm_top_worst";

											// 01/08/2007 christophe : ajout des lignes concernant le 3eme axe dans le debug du gis
											if (GetAxe3($row["family"],$this->product)) {
											    $alt_gis .= " \n na axe3 :".$na_axe3;
											    $alt_gis .= " \n na_value axe3 :".$na_value_axe3;
											}
										}
										else 
										{							
											// 16/11/2007 - Modif. benoit : utilisation de la fonction '__T()' pour définir le tooltip du bouton du GIS

											$alt_gis = (strpos(__T('U_TOOLTIP_DASH_ALARM_DISPLAY_GIS'), "Undefined") === false) ? __T('U_TOOLTIP_DASH_ALARM_DISPLAY_GIS') : "Display GIS";
											
											//$alt_gis = 'Display GIS';
										}
                                        //16/11/2012 MMT bz 30276 ajout gestion top-worst pour DE GIS 3D only
                                        if (GisModel::getGisMode($this->product) == 1) {
                                            echo '
                                                <img src="'.NIVEAU_0.'images/icones/gis_link_alarme.gif"
                                                    alt="'.$alt_gis.'"
                                                    title="'.$alt_gis.'"
                                                    style="cursor:pointer;"
                                                    onclick="'.$fonction_gis.'"
                                                />
                                                ';
                                        }
										// maj 10/03/2008 christophe : ajout du lien vers l'export google earth
										echo '
											<img src="'.NIVEAU_0.'images/icones/gis_link_alarme_g_earth.gif"
                                                alt="'.$alt_gis.'"
												title="Display GIS 3D"
												style="cursor:pointer;"
												onclick="'.$fonction_gearth.'"/>';
									}

							echo	"
									</td>
								</tr>
							";
							$element_precedent = $element;
						}
					} else {
						echo "<tr><td><i>No result.</i></td></tr>";
					}
				echo "</table>";
			echo "</div>";
			echo "</div>";
			echo "</div>";
		echo "<div>";

	}

	/**
	* Fonction qui récupère le label du HN 3ème axe
	* @param string $family : famille 
	* @return string $label : label du HN 3ème axe retourné
	*/
	function getAxe3Label($family){

		$query = "SELECT axe_type_label FROM sys_definition_gt_axe WHERE family='$family' ";
		$label = $this->db->getone($query);
		if (!$label) {
			// valeur à afficher par défaut.
			$query = " select distinct axe_type_label FROM sys_definition_gt_axe";
			$label = $this->db->getone($query);

			if (!$label)	$label = "axe 3";
		}
		return $label;
	}
	
}


?>
