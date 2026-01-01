<?php
/*
	29/07/2009 GHX
		- Correction du BZ 10798 [REC][T&A Cb 5.0][Query Builder] : warning PHP sur query avec formule
		 Correction du BZ 10791 [REC][T&A Cb 5.0][Query Builder] : erreur dans la requete SQL si on utilise une NA de 'My Network Aggregation' sans choisir de NA
	07/08/2009 GHX
		- Correction du BZ 10803
		- Modification de la requete SQL pour faire avoir la bonne condition sur le niveau d'aggrégation BZ 10966
		- Modification de la fonction qui récupère les labels des niveaux supérieurs  BZ 10966
	31/08/2009 : MPR
		- Correction du bug 11303 - Une requête avec un order by sur la na retourne systématiquement No results
	07/01/2010 GHX
		- correction du BZ 13659 [SUP][V5.0][11325][OutremerTELECOM]: Erreur query builder
	08/03/2010 - MPR
		- Correction du BZ 14317 [SUP][Datatrends Huawei 4.0][AVP 10913][Comium Côte d'Ivoire]: Les agregations BH ne sont pas affichées dans les résultats du query builder
	26/04/2010 NSE
		- bz 15232 : ajout de la condition sur la na 3° axe
	10/05/2010 BBX
		- BZ 14317 : Si la BH n'est pas activée, il ne faut pas continuer.
        09/09/2010 BBX
                - BZ 14317 : Gestion de la BH
 *      15/02/2011 NSE DE Query Builder :
 *              Suppression de la limite à 10000 résultats
 *              Gestion des limites de résultats affichés (à 1000)
 *              Création de getRequete, getTableauResultat et getEnteteTableau
 *      21/02/2011 NSE DE Query Builder :
 *              Modification de la requête de comptage pour essayer de minimiser les cas de jointure,
 *                  avec tout de même la gestion des conditions définies par l'utilisateur
                Création de getProduct
 *
 *      06/04/2011 MMT bz 20776 gere operateurs = et <> pour les conditions sur NA level
 * 25/05/2011 NSE bz 22218 : présence de [na]|s|[ne] dans les exports -> création de la méthode d'initialisation de la généalogie
 * 10/11/2011 ACS BZ 24517 hide virtual SAI in query builder result page
 * 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
 * 21/12/2011 ACS BZ 25191 Warnings displayed in query builder
*/
?>
<?
/*
*	@cb41000@
*
*	Composant de base version cb_4.1.0.00
*
*	- 25/11/2008 - SLC - gestion multi-produit
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
	- maj 26/03/2008, benoit : correction du bug 5380
	- maj 28/03/2008, benoit : correction du bug 4577
	- maj 31/03/2008, benoit : mise en commentaire de l'ajout de la requete "select 'builder_report';" à la requete à executer
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14

	- maj 27/07/2007, benoit : dans la sous-requete du select, si la colonne na_label de la na de la condition est vide alors on utilise la   na comme label

*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01

	- maj 10/05/2007, benoit : extension de la duree maximale d'execution du script pour les requetes un peu      longues

	- maj 10/05/2007, benoit : correction de l'affichage du label de la na axe1 + na axe3 dans la partie FROM de   la requete

	- maj 23/05/2007, benoit : decodage des valeurs de la conditions pour pouvoir les utiliser dans la requete

*/
?>
<?
/*
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*/
?>
<?
/*
*	@cb20040@
*
*	30/11/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.40
*/
?>
<?
/*
*	16/06/2006 - Copyright Acurio
*
*	Composant de base version cb_1.2.0.2p
*
*	Parser version iub_1.1.0b
*/
?>
<?php
/*
* 25/11/2005 - GH : reprise depuis 0 du module de generation de la requete pour prendre en compte le 3eme axe et rendre les choses plus claires
* 					Une formule n'accepte pas de fonction (SUM etc..) mais en revanche le script le traite déjà. Il sufirait de l'activer via le .js
*
* 14/01/2006 - GH : probleme des conditions lorsuq'on a pas de troisième axe
*
* 23/01/2006 - GH : ajout d'un distinct sur l'élément réseau pour éviter des doublons dans les resultats
*
* 01/02/2006 - GH : le order by doit contenir une information présente dans le select
*
* 30/03/2006 : selection des elements reseau dans la clause from en lieu et place de la table complete 	edw_object_xx_ref
*
* 12/05/2006 : meilleure gestion des valeurs saisies dans la condition notamment les ' et les " ainsi que les valeurs numeriques ou non
*
* 04/07/2006 : mise en place du kill des requetes trop longues
*
* 01/03/2007 : intégration du on_off sur les niveau d'aggrégation minimum (lignes 256)
*/

// 10/05/2007 - Modif. benoit : extension de la duree maximale d'execution du script pour les requetes un peu longues
// DE Query Builder (passage de 600 à 6000)
set_time_limit(6000);

// __debug($_POST);



// __debug(getPathNetworkAggregation('','no',1,false));exit;

class builder_report_requete {

	function builder_report_requete($family,$product = '')
	{
		// 20/04/2007 - Modif. benoit : on determine si l'axe3 est present. Si c'est le cas, on determine le na d'axe3 à utiliser
		if (GetAxe3($family,$product)) {
			$this->flag_axe3 = true;

			// On recherche dans le tableau de conditions si il existe une condition sur l'axe3. Auquel cas, le nom de la na 3eme axe est celle de la condition
			$condition_hidden = $_POST["condition_hidden"];

			for ($i=0; $i < count($condition_hidden); $i++) {
				$condition = explode(":", $condition_hidden[$i]);
				if ($condition[0] == 8) $this->axe3_na = $condition[3];
			}

			// Si la condition sur le 3eme axe n'existe pas, le nom de la na 3eme axe est celui de la na axe3 minimum
			if (!isset($this->axe3_na))
				$this->axe3_na = get_network_aggregation_min_axe3_from_family($family,$product);

		} else {	// pas d'axe3
			$this->flag_axe3 = false;
		}

		// initialise les données de base issues du formulaire
		$this->init($family,$product);
		$this->put_in_array();

		// Determine à partir de toutes les données quel vont être les tables de données sources servant à la requete
		$this->get_query_source_table();

		if (!$this->flag_error) {
			// partie qui sert pour l'axe 3
			// Determine l'id group table. Sert dans le cas du 3eme axe.
			$id_group_table = get_group_table_id($this->group_table,$family,$product);
			$axe_info = get_axe3_information_from_gt($id_group_table,$product);
			$this->axe_index = $axe_info["axe_index"];

			$this->get_query_select_part();
			$this->get_query_from_part();
			$this->get_query_where_condition_part();
			$this->get_query_where_jointure_part();
			$this->get_query_order_by_part();
			$this->checkSelectAndConditionOnTaBH();
			// formation de la requete
			$this->query = $this->query_select . $this->query_from . $this->query_where_condition . $this->query_where_jointure;

			if (count($this->group_by) > 0)
				 $this->query .= "\n GROUP BY " . implode(",", $this->group_by);

			 $this->query .= $this->query_order_by . $this->limit;


		} elseif ( empty($this->error_message))  {
			$this->error_message = "Some data can not be combined";
		}
	}


	/**
	* Fonction qui initiliase les données qui vont servir de base pour déterminer la requete
	* Ces informations sont pour la plupart issues du formulaire de creation de la requete
	*
	* @param string	$family : famille
	* @param int 		$product : id du produit
	*
	*/
	function init($family,$product = '') {
		$this->family = $family;
		$this->product = $product;
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$this->db = Database::getConnection($this->product);

		// Sert à savoir le niveau minimum dans le cas de liste d'éléments créées par l'utilisateur
		$this->network_aggregation_min = get_network_aggregation_min_from_family($family,$this->product);

		// 20/04/2007 - Modif. benoit : si l'axe3 est defini, on concatene le nom de la na min avec celui du 3eme axe
		// 25/11/2008 - SLC - Il n'y a plus de concatenation, et en fait on garde deux colonnes
		// if ($this->flag_axe3)  $this->network_aggregation_min .= "_".$this->axe3_na;

		$this->object_ref_table = get_object_ref_from_family($family,$this->product);

		$this->fonction			= $_POST["fonction"];
		$this->condition_hidden	= $_POST["condition_hidden"];
		$this->value_condition	= $_POST["value_condition"];
		$this->op_condition		= $_POST["op_condition"];
		$this->donnees_hidden	= $_POST["donnees_hidden"]; //on traite en premier les données provenant de la zonne select
		$this->nb_donnees		= $_POST["nbr_donnees"];
		$this->nb_condition		= $_POST["nb_row_condition"];
		$this->param_order		= $_POST["param_order_hidden"];
		$this->param_sort		= $_POST["param_sort"];
		$this->limit			= " LIMIT " . $_POST["param_limit"];

	}

	// 23/04/2007 - Modif. benoit : modification de la fonction 'detect_min_network()'. Ajout du parametre 'family'
	// 						et d'une condition liée à ce dernier dans la requete de selection des agregations

    /*
	* fonction qui determine à partir d'un table d'élément réseau quel est le plus petit
	* pour cela utilise les colonnes agregation_level et agregation_rank dela table sys_definition_network_agregation
	* @param : array contenant une liste d'élement réseau
	* @return :nom de l'élément le plus petit
	*
	*/
    function detect_min_network($array_network, $family = '%')
    {
        $query = "SELECT agregation FROM sys_definition_network_agregation WHERE family LIKE '$family' ORDER BY agregation_level ASC, agregation_rank ASC";
        $res = $this->db->getall($query);
        $array_net = ( is_array($array_network)?$array_network:array($array_network) );
	foreach ($res as $row) {
            $agregation = $row['agregation'];
            if (in_array($agregation, $array_net))
                return $agregation;
        }
    }

    /*
	* fonction qui determine à partir d'un table d'élément time quel est le plus petit
	* pour cela utilise les colonnes agregation_level et agregation_rank de la table sys_definition_network_agregation
	* @param : array contenant une liste d'élement time
	* @return :nom de l'élément le plus petit
	*
	*/
    function detect_min_time($array_time)
    {
        $query = "SELECT agregation FROM sys_definition_time_agregation ORDER BY agregation_level ASC, agregation_rank ASC";
        $res = $this->db->getall($query);
        foreach ($res as $row) {
            $agregation = $row['agregation'];
            if (in_array($agregation, $array_time)) {
                return $agregation;
            }
        }
    }

	/**
	* fonction qui va mettre tous les elements POST sour forme de array pour mieux s'y retrouver
	* chaque element possède 5 parametres définis comme suit :
	1 : fonction (ne posede que les trois premiers arguments)
	2 : network_agregation
	3 : time_agregation
	4 : kpi de type mixed,non mixed ou raw
	5 : requetes sauvegardees
	6 : formules
	7 : my_network_aggregation
	8 : 3eme axe
		param2 est le libellé qui sera visisble de l'utilisateur
		param3 est la valeur du champ qui va servir pour la requete
		param4 est le type raw,kpi,mixed (utilise uniquement pour les compteurs et les listes d'éléments)
		param5 : est le group table lorsqu'il existe (uniquement utilisé en KPI,mixed ou RAW  =>param1=4)
	*/
	function put_in_array() {
		for ($i = 0;$i < $this->nb_condition;$i++) {
			$condition = $this->condition_hidden[$i];

			$array_condition = explode(":", $condition);

			// 23/05/2007 - Modif. benoit : on decode les conditions de la requete pour disposer de la bonne valeur

			// param1 est utilise comme le premier index
			$this->array_condition[$array_condition[0]]["param2"][] = urldecode($array_condition[1]);
			$this->array_condition[$array_condition[0]]["param3"][] = urldecode($array_condition[2]);
			$this->array_condition[$array_condition[0]]["param4"][] = $array_condition[3];
			$this->array_condition[$array_condition[0]]["param5"][] = $array_condition[4];

			$this->array_condition_op[$array_condition[0]][] = $this->op_condition[$i];
			$this->array_condition_value[$array_condition[0]][] = stripslashes(urldecode($this->value_condition[$i]));
		}

		for ($i = 0;$i < $this->nb_donnees;$i++) {
			$donnees = $this->donnees_hidden[$i];
			$array_donnees = explode(":", $donnees);

			$this->array_donnees[$array_donnees[0]]["param2"][] = $array_donnees[1];
			$this->array_donnees[$array_donnees[0]]["param3"][] = $array_donnees[2];
			$this->array_donnees[$array_donnees[0]]["param4"][] = $array_donnees[3];
			$this->array_donnees[$array_donnees[0]]["param5"][] = $array_donnees[4];

			if ($this->fonction[$i] != "") {
				$this->flag_fonction = true;
				$this->array_donnees[$array_donnees[0]]["fonction"][] = explode(":", $this->fonction[$i]);
			} else {
				$this->array_donnees[$array_donnees[0]]["fonction"][] = "";
			}
		}

		// __debug($this->array_donnees);

		// 20/04/2007 - Modif. benoit : si le 3eme axe est defini, on concatene les noms des na avec celui du 3eme axe
		// 25/11/2008 - SLC - Il n'y a plus de concatenation, et en fait on garde deux colonnes
		/* if ($this->flag_axe3)
			for ($i=0; $i < count($this->array_donnees[2]['param3']); $i++)
				$this->array_donnees[2]['param3'][$i] .= "_".$this->axe3_na;
		*/
	}

	/**
	* maj 08/03/2010 - MPR : Correction du BZ 14317
	* Fonction qui vérifie s'il y a une condition sur une TA et que celle-ci est bien de type BH lorsqu'on visualise des données BH
	*/
	function checkSelectAndConditionOnTaBH()
	{
		// 10/05/2010 BBX
		// Si la BH n'est pas activée, il ne faut pas continuer. BZ 14317
		if(!array_key_exists('hour',getTaList('', $_GET['product'])))
			return;

		$time_select = array_unique($this->array_donnees[3]["param2"]);

		$agregations_BH =  getTaList("AND agregation ILIKE '%bh%'", $this->product );

		if( in_array( $this->min_time_in_select, array_keys($agregations_BH)  ) && is_array($this->array_condition[3]["param2"]) )
		{
			if( count( array_intersect ($this->array_condition[3]["param2"], $agregations_BH ) ) == 0 ){
				$this->flag_error = true;
				$this->error_message = __T('U_QUERY_BUILDER_NO_CORRESPONDANCE_SELECT_CONDITION_ON_TA_BH');
			}
		}

		return;
	}

	/*
	* fonction qui determine à partir des données du formulaire de creation de la requete quelles vont
	* être les tables sources utilisées (Important surout s'il y a un 3eme axe)
	* Par ailleurs, cela permet également de vérifier s'il doit y avoir une jointure (raw et kpi)
	* S'il y a plusieurs group table, cela renvoie une erreur
	*/
	function get_query_source_table()
	{
		// cela va permettre à partir de cette table de faire une jointure pour avoir le label de l'élément
		$this->source_table["object_ref"] = $this->object_ref_table;
		// les elements qui vont determiner la table source sont les données de type Time,Network, 3eme axe

		// Recupère tous les elements de type time

		// 26/03/2008 - Modif. benoit : correction du bug 5380. Suppression des debug

		//__debug($this->array_donnees[3]["param3"],"array_donnees");
		//__debug($this->array_condition[3]["param3"],"array_condition");

		// maj maxime - 10/09/2007 -> migration php4->php5

		// 15:02 29/07/2009 GHX
		// Correction du BZ 10791
		if ( $this->array_donnees[3]["param3"] == '' || count($this->array_donnees[3]["param3"]) == 0 )
		{
			$this->flag_error = true;
			$this->error_message = __T('U_QUERY_BUILDER_NO_TA_SELECTED');
			return;
		}


		if (is_array($this->array_condition[3]["param3"]))
			$array_time = array_unique(array_merge($this->array_donnees[3]["param3"], $this->array_condition[3]["param3"]));
		else
			$array_time = array_unique(array_merge($this->array_donnees[3]["param3"],array($this->array_condition[3]["param3"])));

		//__debug($array_time,"array_time");
		$this->min_time_for_query = $this->detect_min_time($array_time);
		$this->min_time_in_select = $this->detect_min_time($this->array_donnees[3]["param3"]);

		// Recupère tous les elements de type network
		// maj maxime - 10/09/2007 -> migration php4->php5

		// 15:02 29/07/2009 GHX
		// Correction du BZ 10791
		if ( $this->array_donnees[2]["param3"] == '' || count($this->array_donnees[2]["param3"]) == 0 )
		{
			$this->flag_error = true;
			$this->error_message = __T('U_QUERY_BUILDER_NO_NA_SELECTED');
			return;
		}

		if ( is_array($this->array_condition[2]["param3"]) and is_array($this->array_condition[7]["param3"]) )
			$array_network = array_unique(array_merge($this->array_donnees[2]["param3"], $this->array_condition[2]["param3"], $this->array_condition[7]["param3"]));
		elseif ( is_array($this->array_condition[2]["param3"]) )
			$array_network = array_unique(array_merge($this->array_donnees[2]["param3"], $this->array_condition[2]["param3"], array($this->array_condition[7]["param3"]) ));
		else
			$array_network = array_unique(array_merge($this->array_donnees[2]["param3"],array($this->array_condition[2]["param3"]), array($this->array_condition[7]["param3"])) );

		// __debug($array_network,"array_network");

		// 23/04/2007 - Modif. benoit : si l'axe3 est defini, on reformate le tableau des na en enlevant à ces derniers le nom des na 3eme axe
		if (isset($this->axe3_na))
			for ($i=0; $i < count($array_network); $i++)
				$array_network[$i] = str_replace('_'.$this->axe3_na, '', $array_network[$i]);

		// 23/04/2007 - Modif. benoit : ajout du parametre 'family' à la fonction 'detect_min_network()'
		$this->min_network_for_query = $this->detect_min_network($array_network, $this->family);

		// 23/04/2007 - Modif. benoit : si l'axe3 est défini, on ajoute son nom à la na min
		// 18/12/2008 - SLC - mise en commentaire : je crois pas que ça aille avec la nouvelle topo
		// if (isset($this->axe3_na))	$this->min_network_for_query .= '_'.$this->axe3_na;
		//__debug($this->min_network_for_query);

		// 12:11 29/07/2009 GHX
		// Correction du BZ 10798
		// Le problème vient du fait qu'on n'a pas de raw ou kpi dans de sélectionné dans la requete
		if ( !isset($this->array_donnees[4]) )
		{
			$this->array_donnees[4] = array(
				'param2' => array(),
				'param3' => array(),
				'param4' => array(),
				'param5' => array(),
				'param6' => array(),
				'fonction' => array(),
			);
		}
		if ( !isset($this->array_condition[4]) )
		{
			$this->array_condition[4] = array(
				'param2' => array(),
				'param3' => array(),
				'param4' => array(),
				'param5' => array(),
				'param6' => array(),
				'fonction' => array()
			);
		}

		// Recupère tous les elements de type raw et kpi
		// maj maxime - 10/09/2007 -> migration php4->php5
		if( $this->array_donnees[6]["param4"] == '' )
			$this->array_donnees[6]["param4"] = array();
		if( $this->array_condition[6]["param4"] == '')
			$this->array_condition[6]["param4"] = array();
		if( $this->array_condition[4]["param4"] == '')
			$this->array_condition[4]["param4"] = array();
		if( $this->array_condition[4]["param5"] == '')
			$this->array_condition[4]["param5"] = array();
		if( $this->array_donnees[6]["param5"] == '')
			 $this->array_donnees[6]["param5"] = array();

		$array_raw_kpi = array_unique(array_merge($this->array_donnees[4]["param4"], $this->array_condition[4]["param4"], $this->array_donnees[6]["param4"], $this->array_condition[6]["param4"]));

		// teste si on a une information pour le 3eme axe auquel cas c'est cette information qui va être utilisee
		// sinon on prend en compte les group table issus des raw counters et KPI
		if (count($this->array_condition[8]["param5"]) > 0) {
			$array_group_table = array_unique($this->array_condition[8]["param5"]);
		} else {
			$array_group_table = array_unique(array_merge($this->array_donnees[4]["param5"], $this->array_condition[4]["param5"], $this->array_donnees[6]["param5"]));
		}
		// s'il y a plusieurs Group table c'est qu'il y a un probleme
		if (count($array_group_table) > 1) {
			$this->flag_error = true;
		} else {
			$this->group_table = $array_group_table[0];
			// on n'a qu'une seule table source au maximum par raw et par KPI
			foreach ($array_raw_kpi as $raw_kpi_type) {
				$this->source_table[$raw_kpi_type] = $array_group_table[0] . "_" . $raw_kpi_type . "_" . $this->min_network_for_query .((isset($this->axe3_na))?'_'.$this->axe3_na:''). "_" . $this->min_time_for_query;
			}
		}

		// teste si on a 3 tables ce qui signifie qu'on devra faire une jointure entre raw et kpi
		if (count($this->source_table) > 2)
			$this->jointure = true;

                // 09/09/2010 BBX
                // Gestion de la BH
                // BZ 14317
                foreach ($this->source_table as $index => $table_name) {
                    $existingColumns = $this->db->getColumns($table_name);
                    if(in_array('bh',$existingColumns)) {
                        $this->array_donnees[4]['param2'][] = 'Busy Hour';
                        $this->array_donnees[4]['param3'][] = 'bh';
                        $this->array_donnees[4]['param4'][] = $index;
                        $this->array_donnees[4]['param5'][] = '';
                        break;
                    }
                }

		//__debug($this->source_table);
                //__debug($this->array_donnees);
	}


	/**
	 * 06/04/2011 MMT bz 20776
	 * Get the SQL operator ( =, <>, IN or NOT IN ) depending on the Condition operator (=,>=,>,<,<=,<>) for NA level
	 * Condition ()
	 * @param int $conditionIndex condition array index
	 * @param <type> $type single (returns = or <>) or list (IN or NOT IN)
	 * @return string SQL operator
	 */
	private function getSQLoperatorFromNAConditionOperator($conditionIndex,$type='single'){
		$numOp = " = ";
		$selOp = " IN ";
		if($this->array_condition_op[2][$conditionIndex] == "<>"){
			$numOp = " <> ";
			$selOp = " NOT IN ";
		}
		if($type == 'single'){
			$ret = $numOp;
		} else {
			$ret = $selOp;
		}
		return $ret;
	}


	/**
	* fonction qui genere la partie FROM de la requete
	*/
	function get_query_from_part()
	{
		$this->query_from = "\n FROM ";
		foreach ($this->source_table as $index => $table_name) {
			// l'index sert d'alias. L'alias sera utilise devant les nom des élements de la requete

			if ($index == "object_ref") {
				// on cree une requete pour selectionner les elements network distincts plutôt que de prendre tout object_ref
				$this->query_from .= "\n (SELECT distinct ";

				// 20/04/2007 - Modif. benoit : si l'axe3 est defini, on concatene les labels de la na axe1 et axe3 dans la requete de selection
				// 25/11/2008 - SLC - non, on doit plus le faire maintenant
				if (isset($this->axe3_na)) {
					$na_axe1 = str_replace("_".$this->axe3_na, "", $network);
					$this->query_from .= "eor_id, \n\t(CASE WHEN eor_label IS NULL THEN eor_id ELSE eor_label END) AS eor_label,";
				}
				else
				{
					$this->query_from .= " eor_id,eor_label,";
				}

				// 16:52 06/01/2010 GHX : BZ 13659
				$other_tables = "";
				$other_where = "";

				if (count($this->array_condition[2]["param3"]) > 0)
				{
					// Debut correction du BZ 13659
					// 16:52 06/01/2010 GHX
					// parcoure les network element présent dans la condition
					$pathNA = getPathNetworkAggregation($this->product,$this->family,1,false);
					$naUsed = array();
					//06/04/2011 MMT bz 20776 use getSQLoperatorFromNAConditionOperator to get operator for NA level conditions
					$condIndex = 0;
					foreach ( $this->array_condition[2]["param3"] as $k => $network)
					{
						$numOperator = $this->getSQLoperatorFromNAConditionOperator($condIndex);
						$selOperator = $this->getSQLoperatorFromNAConditionOperator($condIndex,'list');
						$condIndex++;

						// Cas d'un filtre sur le niveau minimum, avec un élément particulier
						if ( $network == $this->min_network_for_query ){
							//06/04/2011 MMT bz 20776 manage = and <> operator
							//11/04/2011 MMT bz 20776 reopened manage AND /or operator for label/id condition
							if($numOperator == " <> "){
								$labelAndOr = " AND ";
							} else {
								$labelAndOr = " OR ";
							}
							$other_where .= " AND (eor_id $numOperator '".$this->array_condition_value[2][$k]."' $labelAndOr eor_label $numOperator '".$this->array_condition_value[2][$k]."')";
						}

						if ( $network == $this->min_network_for_query || in_array($network, $naUsed) ) continue;

						while ( list($naChild) = $pathNA[$network] )
						{
							if ( $naChild == $this->min_network_for_query ) $alias1 = $naChild.'.eor_id';
							else $alias1 = $naChild.'.eoar_id_parent';

							$other_tables = " LEFT JOIN edw_object_arc_ref AS $network ON ($alias1 = $network.eoar_id)".$other_tables;
							$other_where .= " AND $network.eoar_arc_type='$naChild|s|$network'";

							if ( !in_array($network, $naUsed) )
							{
								//06/04/2011 MMT bz 20776 manage = and <> operator
								$other_where .= " AND $network.eoar_id_parent $selOperator (SELECT eor_id FROM edw_object_ref where eor_obj_type = '$network' AND (eor_id = '".$this->array_condition_value[2][$k]."' OR eor_label = '".$this->array_condition_value[2][$k]."'))";
							}

							array_push($naUsed, $network, $naChild);
							if ( $naChild == $this->min_network_for_query )
							{
								//06/04/2011 MMT bz 20776 manage = and <> operator
								$arrayIndex = array_search($naChild, $this->array_condition[2]["param3"],true);
								if ( $arrayIndex != false )
								{
									$localSelOperator = $this->getSQLoperatorFromNAConditionOperator($arrayIndex,'list');
									$_ = $this->array_condition_value[2][array_search($naChild,$this->array_condition[2]["param3"])];
									$other_where .= " AND $naChild.eor_id $localSelOperator (SELECT eor_id FROM edw_object_ref WHERE eor_obj_type = '$naChild' AND (eor_id = '".$_."' OR eor_label = '".$_."'))";
								}
								break;
							}

							$network = $naChild;
							$alias1 = $network.'.eoar_id_parent';
						}
					}
					// Fin correction du BZ 13659
				}

				// on enleve la derniere virgule
				$this->query_from = substr($this->query_from, 0, -1);

				// 10/11/2011 ACS BZ 24517 hide virtual SAI in query builder result page
				// 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
				// 21/12/2011 ACS BZ 25191 Warnings displayed in query builder
				// on vérifie que lorsque le niveau d'aggrégation minimum est présent dans la requête on inclus la condition on_off = 1 et != virtual
				$where = '';
				if (in_array($this->network_aggregation_min, $this->liste_champ["network"]))
					foreach ($this->liste_champ["network"] as $val)
						if ($val==$this->network_aggregation_min)
							$where.= " WHERE eor_id not in (select eor_id from $table_name where eor_obj_type='$val' and (eor_on_off = 0 OR ".NeModel::whereClauseWithoutVirtual('', 'eor_id', false)." ) )";

				// on met le where principal - 25/11/2008 - SLC
				// 13:48 07/08/2009 GHX
				// Correctoin d'un bug, on n'appelait pas la bonne variable
				if (!$where)	$where = " WHERE eor_obj_type='{$this->min_network_for_query}' ";
				else 			$where .= " AND eor_obj_type='{$this->min_network_for_query}' ";

				// 16:52 06/01/2010 GHX : BZ 13659
				$this->query_from .= " FROM  " . $table_name ." AS ".$this->min_network_for_query." ".$other_tables." ".$where." ".$other_where.") AS " . $index . ",";
			} else {
				$this->query_from .= "\n\t" . $table_name . " AS " . $index . ",";
			}
		}
		// supprime la derniere virgule
		$this->query_from = substr($this->query_from, 0, -1);

	}


	/**
	* fonction qui genere la partie SELECT de la requete
	*/
	function get_query_select_part() {
		$this->query_select = "SELECT ";

		// fixe quelle table attaquer pour tout sauf les elements raw et KPI
		if ($this->jointure) {
			// on choisit d'attaquer la table raw car elle contient les donnees time alors que par forcement pour les KPIs
			$type = "raw";
		} elseif ($this->source_table["raw"]) {
			$type = "raw";
		} else {
			$type = "kpi";
		}

		// gestion de la partie TIME
		$time_select = array_unique($this->array_donnees[3]["param3"]);


		// teste s'il y aura une jointure entre la table raw et kpi car on privilegie l'accès à la table kpi qui en general contient moins de colonnes
		foreach ($time_select as $time) {
			$query_select_time .= $type . "." . $time . ",";
			$this->liste_champ["time"][] = $time;

			if ($this->flag_fonction)
				$this->group_by[] = $type . "." . $time;
		}

		// gestion de la partie RAW KPI
		if (count($this->array_donnees[4]) > 0) {
			$raw_kpi_select = array_unique($this->array_donnees[4]["param3"]);

			foreach ($raw_kpi_select as $key => $raw_kpi_name) {

				// si une fonction est appliquée au compteur alors on l'ajoute dans la query
				if ($this->array_donnees[4]["fonction"][$key] != "") {
					$query_select_raw_kpi .= $this->array_donnees[4]["fonction"][$key][2] . "(" . $this->array_donnees[4]["param4"][$key] . "." . $raw_kpi_name . ") as " . $raw_kpi_name . ",";
					$this->liste_champ["raw_kpi"][] = $this->array_donnees[4]["fonction"][$key][2] . "(" . $raw_kpi_name . ")";
				}
				else
				{
					$this->liste_champ["raw_kpi"][] = $raw_kpi_name;
					$query_select_raw_kpi .= $this->array_donnees[4]["param4"][$key] . "." . $raw_kpi_name . ",";
					if ($this->flag_fonction) {
						$this->group_by[] = $this->array_donnees[4]["param4"][$key] . "." . $raw_kpi_name;
					}
				}
			}
		}

		// gestion de la partie NETWORK
		$network_select = array_unique($this->array_donnees[2]["param3"]);

		foreach ($network_select as $network) {
			$this->liste_champ["network"][] = $network;

			// 24/04/2007 - Modif. benoit : ajout d'un libellé au resultat du CASE WHEN (sert pour le tri)
			if ($network == $this->min_network_for_query) {
				// on est dans le cas du NA minimum
				$query_select_network .= "\n\t CASE WHEN object_ref.eor_label IS NOT NULL THEN object_ref.eor_label ELSE  object_ref.eor_id END";
					// . "\n\t\t || (select eor_label from edw_object_ref where eor_id=$type.$this->axe3_na and eor_obj_type='$this->axe3_na')"
				if ($this->axe3_na)
					$query_select_network .= "\n\t\t || ' ' || $type.$this->axe3_na";
				$query_select_network .= " AS {$network}_label,";

			} else {
				// on est dans le cas d'un NA plus grand : et comme la requête pour avoir leur label est à se pendre, on fait ça plus tard en PHP avec get_cols()
				$query_select_network .= "\n\t '$network|s|' || object_ref.eor_id AS {$network}_label,";
			}

			// 24/04/2007 - Modif. benoit : ajout de la na dans la partie select
			//$query_select_network .= " ".$type.".".$network.",";

			if ($this->flag_fonction) {
				$this->group_by[] = "object_ref.eor_id";
				$this->group_by[] = "object_ref.eor_label";
			}
		}

		// gestion de la partie Formule. Une formule contient systematiquement des raw counters
		if ( is_array($this->array_donnees[6]["param3"]) and count($this->array_donnees[6]) > 0) {

			$formula_select = array_unique($this->array_donnees[6]["param3"]);

			foreach ($formula_select as $key => $string_formula) {
				$array_formula = explode("|", $string_formula); //on separe la formula de son identifiant (separateur= |)

				// si une fonction est appliquée à la formule alors on l'ajoute dans la query
				if ($this->array_donnees[6]["fonction"][$key] != "") {
					$query_select_raw_kpi .= $this->array_donnees[6]["fonction"][$key][2] . "(" . $array_formula[0] . "),";
					$this->liste_champ["raw_kpi"][] = $this->array_donnees[6]["fonction"][$key][2] . "(" . $this->array_donnees[6]["param2"][$key] . ")";
				}
				else
				{
					$this->liste_champ["raw_kpi"][] = $this->array_donnees[6]["param2"][$key];
					// 12:24 29/07/2009 GHX
					// Ajout d'un alias pour la formule
					$query_select_raw_kpi .= $array_formula[0] .' AS "'.$this->array_donnees[6]["param2"][$key].'"'.",";

					if ($this->flag_fonction)
						$this->group_by[] = $array_formula[0];
				}
			}
		}

		// gestion de la partie Liste d'éléments
		if (count($this->array_condition[7]) > 0) {
			// a priori on aura qu'un élément car une liste ne se fait que sur le niveau le plus bas
			$liste_element_select = array_unique($this->array_condition[7]["param3"]);

			if (!in_array($liste_element_select[0], $this->liste_champ["network"])) {

				// 23/04/2007 - Modif. benoit :
				$this->liste_champ["network"][] = $liste_element_select[0];

				$query_select_network .= "CASE WHEN object_ref.eor_label IS NOT NULL THEN object_ref.eor_label ELSE  object_ref.eor_id END  AS {$liste_element_select[0]}_label," ;

				if ($this->flag_fonction) {
					$this->group_by[] = "object_ref.eor_id";
					$this->group_by[] = "object_ref.eor_label";

					// 25/04/2007 - Modif. benoit : pour les liste d'elements, on rajoute la na minimum au GROUP BY

					$na_min = $this->min_network_for_query;

					// TODO : ???? y a certainement un truc à faire ici  :-(
					if (isset($this->axe3_na))
						$na_min = str_replace("_".$this->axe3_na, "", $na_min);

					// 28/10/2009 BBX : celà n'a plus lieu d'être
					//$this->group_by[] = "object_ref." . $na_min;
					//$this->group_by[] = "object_ref." . $na_min . "_label";
				}
			}
		}

		// 23/04/2007 - Modif. benoit : mise en commentaire de la gestion du 3eme axe dans la construction du SELECT de la requete

		// gestion du 3eme AXE s'il existe qui ne peut venir de la partie Condition
		// il n'existe qu'un seul troisième axe par requete

		/*if (count($this->array_condition[8]) > 0) {
			$axe3_select = array_unique($this->array_condition[8]["param3"]);
			$query_select_axe3 = $type . "." . $axe3_select[0];
			$this->liste_champ["axe3"][] = $axe3_select[0];

			if ($this->flag_fonction) {
				$this->group_by[] = $type . "." . $axe3_select[0];
			}

			$this->query_select .= $query_select_network . $query_select_time . $query_select_raw_kpi . $query_select_axe3;
		}
		else
		{*/
			$this->query_select .= $query_select_network . $query_select_time . substr($query_select_raw_kpi, 0, -1);
		//}

		// tableau qui contient le nom des champs et qui va servir pour l'affichage du resultat
		if( $this->liste_champ["time"] == '')
			$this->liste_champ["time"] = array();
		if( $this->liste_champ["raw_kpi"] == '')
			$this->liste_champ["raw_kpi"] = array();
		if( $this->liste_champ["axe3"] =='')
			$this->liste_champ["axe3"] = array();

		// 2010/08/12 - MGD - BZ 14632 : Affichage du nom du NA plutôt que son code
		$network_labels = array();
		foreach ($this->liste_champ["network"] as $net_code) {
			$query = "SELECT agregation_name from sys_definition_network_agregation where lower(agregation)=lower('{$net_code}');";
			$network_labels[] = $this->db->getOne($query);
		}

		// 2010/08/12 - MGD - BZ 14632 - Remplacement du tableau utilisé pour les nom des NA
		$this->liste_entete_tableau = array_merge($network_labels, $this->liste_champ["time"], $this->liste_champ["raw_kpi"], $this->liste_champ["axe3"]);
	}

	/**
	* fonction qui va modifier la valeur de la condition pour gérer les ' et les "
	*
	*/
	function modify_condition_value($condition_value) {
		if (!is_numeric($condition_value)) {
			if (substr($condition_value, 0, 1) == '\'')
				$condition_value = substr($condition_value, 1);

			if (substr($condition_value, -1) == '\'')
				$condition_value = substr($condition_value, 0, -1);

			$condition_value=str_replace("'", "\'", $condition_value);

			return $condition_value;
		} else {
			return $condition_value;
		}
	}


	/**
	* fonction qui genere la clause WHERE mais uniquement pour la partie condition
	* les elements pour les jointures sont traités dans la fonction get_query_where_jointure_part
	*
	*/
	function get_query_where_condition_part()
	{
		$query_where_condition = "\n WHERE ";
		$compteur = 0;
		if (count($this->array_condition)>0) {
			foreach ($this->array_condition as $type => $condition) {
				if ($type != "") { // lorsqu'on ne saisit pas de condition alos on a quand même le champ vide qui est retourné
					if(count($condition["param3"])>0){
						foreach ($condition["param3"] as $index => $valeur_champ) {
							if ($compteur > 0 && substr($query_where_condition, -4) != "AND ") {
								$query_where_condition .= "\n\t AND ";
							}
							switch ($type) {
								case "2": // cas des elements network
									// 09:21 07/01/2010 GHX
									// correction du BZ 13659
									$compteur--;
								break;
								case "4": // cas des compteurs et KPI;
									$raw_kpi_type = $condition["param4"][$index] . ".";
								case "6": // cas des compteurs et KPI;
									$raw_kpi_type = ""; //une formule est basée sur des raw donc il ne peut pas y avoir d'ambiguite sur les compteurs. On ne met donc pas l'alias de la table car il faudrait la mettre devant chaque compteur présent dans la formule sinon lorsqu'on ajoute une fonction, cela plante
									$array_formula = explode("|", $valeur_champ); //Separe la formule de son identifiant (separateur = |)
									$valeur_champ = $array_formula[0];
								break;
								case "7": // cas des listes d'éléments
									$raw_kpi_type = 'object_ref.';
								break;
								default:
									if ($this->jointure) {
										$raw_kpi_type = "raw.";
									} elseif ($this->source_table["raw"]) {
										$raw_kpi_type = "raw.";
									} else {
										$raw_kpi_type = "kpi.";
									}
								break;
							} // switch
							// gestion de la saisie de  NULL dans la condition
							if ($this->array_condition_value[$type][$index] != 'NULL') {
								$this->array_condition_value[$type][$index] = $this->modify_condition_value($this->array_condition_value[$type][$index]);

								switch ($type) {
									case 2 :	// cas des network element, la condition porte sur le label
										// 09:21 07/01/2010 GHX
										// correction du BZ 13659
									break;

									case 7 :	// my network aggregation
										// on pourrait faire autrement mais de toute façon il ne faut pas avoir les ' comme pour la condition précédente
										$query_where_condition .= $raw_kpi_type . "eor_id IN " . stripslashes($condition["param4"][$index]);
									break;

									case 8 :	// Cas du 3 eme axe (type=8)
										$query_where_condition .= $raw_kpi_type.$this->axe3_na." LIKE '".$this->array_condition_value[$type][$index]."'";
										// 26/11/2008 - hack monstrueux de SLC permettant d'ajouter la valeur choisie pour l'axe3 dans le label du network element
										// on va chercher le LABEL de l'element axe3 dans edw_object_ref
										$axe3_label = $this->db->getone(" --- on va chercher le label de l'elem axe3
											select eor_label
											from edw_object_ref
											where eor_id='{$this->array_condition_value[$type][$index]}'
												and eor_obj_type='$this->axe3_na'");
										if (!$axe3_label) $axe3_label = $this->array_condition_value[$type][$index];
										$this->query_from = str_replace('##axe3_label##',$axe3_label,$this->query_from);
									break;

									default:
										// une valeur de condition de type 4 (raw ou kpi) doit nécessairement être numerique sinon ca plante
										if (($type == 4 || $type == 3) && !is_numeric($this->array_condition_value[$type][$index])) {
											$this->flag_error = true;
											$this->error_message = "Condition value on time aggregation, raw counters and KPI must be set to numeric values<br>";
										} else {
											$query_where_condition .= $raw_kpi_type . $valeur_champ . $this->array_condition_op[$type][$index] . "'" . $this->array_condition_value[$type][$index] . "'";
										}
									break;
								}
							}
							else if ($this->array_condition_op[$type][$index] == '=' || $this->array_condition_op[$type][$index] == '<>')
							{
								// ne prends en compte que les operateurs = et <> pour gérer le NULL sinon cela n'a pas de sens
								if ($this->array_condition_op[$type][$index] == '=') {
									$query_where_condition .= $raw_kpi_type . $valeur_champ . " IS NULL ";
								} else {
									$query_where_condition .= $raw_kpi_type . $valeur_champ . " IS NOT NULL ";
								}
							} else {
								$query_where_condition .= $raw_kpi_type . $valeur_champ . $this->array_condition_op[$type][$index] . "'" . $this->array_condition_value[$type][$index] . "'";
							}
							$compteur++;
						}
					}
				}
			}

		}
		// 27/11/2008 - SLC - pour le cas où on est dans une famille ayant un axe3, mais qu'on ne l'utilise pas dans nos conditions
		$this->query_from = str_replace(' ##axe3_label##','',$this->query_from);

		$this->query_where_condition = $query_where_condition;
	}


	function get_query_where_jointure_part()
	{
		if ($this->query_where_condition != "\n WHERE " && substr($this->query_where_condition, -4) != "AND ")
			$query_where_jointure = "\n\t AND ";

		// jointure systematique avec la table object_ref pour la jointure sur l'element network minimum
		if (isset($this->axe3_na))
			$this->min_network_for_query = str_replace("_".$this->axe3_na, "", $this->min_network_for_query);


		if ($this->jointure) {
			$query_where_jointure .= "object_ref.eor_id=raw." . $this->min_network_for_query;
			$query_where_jointure .= "\n\t AND raw." . $this->min_network_for_query . "=kpi." . $this->min_network_for_query;
			// 26/04/2010 NSE bz 15232 : ajout de la condition sur la na 3° axe
			if($this->flag_axe3){
				$query_where_jointure .= "\n\t AND raw." . $this->axe3_na . "=kpi." . $this->axe3_na;
			}
		} elseif ($this->source_table["raw"]) {
			$query_where_jointure .= "object_ref.eor_id=raw." . $this->min_network_for_query;
		} else {
			$query_where_jointure .= "object_ref.eor_id=kpi." . $this->min_network_for_query;
		}

		// jointure systematique sur l'element time s'il y a des tables raw et kpi
		if ($this->jointure)
			$query_where_jointure .= "\n\t AND raw." . $this->min_time_for_query . "=kpi." . $this->min_time_for_query;

		$this->query_where_jointure = $query_where_jointure;
	}


	function get_query_order_by_part()
	{
		if ($this->param_order != "") {
			// teste si l'element du Order by est contenu dans le Select
			// si ce n'est pas le cas, cela va générer une erreur dans la requete et donc il faut envoyer un message
			if (in_array($this->param_order, $this->donnees_hidden)) {

				$array_order_by = explode(":", $this->param_order);
				switch ($array_order_by[0]) {
					case "4": // cas des compteurs et KPI;
						$raw_kpi_type = $array_order_by[3] . ".";
						$condition_order_by = $array_order_by[2];
					break;

					case "6": // cas des formules;
						$raw_kpi_type = ""; //une formule est basée sur des raw donc il ne peut pas y avoir d'ambiguite sur les compteurs. On ne met donc pas l'alias de la table car il faudrait la mettre devant chaque compteur présent dans la formule sinon lorsqu'on ajoute une fonction, cela plante
						$array_formula = explode("|", $array_order_by[2]);
						$array_order_by[2] = $array_formula[0];
						$condition_order_by = $array_formula[0];
					break;

					// maj 11:58 31/08/2009 : MPR
					//		Correction du bug 11303 - Une requête avec un order by sur la na retourne systématiquement No results
					case "2" && $array_order_by[4] == "network" :
						// Depuis le CB 5.0, les labels de topo sont tous dans eor_label de la table edw_object_ref
						$condition_order_by = "eor_label";
					break;

					default:
						if ($this->jointure) {
							$raw_kpi_type = "raw.";
						} elseif ($this->source_table["raw"]) {
							$raw_kpi_type = "raw.";
						} else {
							$raw_kpi_type = "kpi.";
						}
						$condition_order_by = $array_order_by[2];
					break;
				} // switch

				// On réorganise les éléments de façon à avoir les valeurs nulles en dernières
				if($this->param_sort == "ASC")
				$this->query_order_by = "\n ORDER BY " . $condition_order_by . " " . $this->param_sort;
				else
					$this->query_order_by = "\n ORDER BY " . $condition_order_by . " IS NOT NULL " . $this->param_sort . ", " . $condition_order_by . " " . $this->param_sort;
			} else {
				$this->flag_error = true;
				$this->error_message = "The data used for Order by MUST appear in the list of selected items<br>";
			}
		}
		else {
			$this->query_order_by = "\n ORDER BY ".$this->liste_champ["time"][0]." DESC";
		}
	}

	/**
	*	Cette fonction recherche la filiation entre un NA descendant, et un NA ancètre.
	*
	*	02/12/2008 - SLC - CB 4100
	*
	*	Ex:  get_NA_lineage('sai','network') = array('sai|s|rnc','rnc|s|network');
	*
	*	@param string NA de bas niveau duquel on part
	*	@param string NA ancètre jusqu'où on veut remonter
	*	@return array Retourne un tableau contenant tous les sauts pour aller de $descendant à $anscestor
	*/
	function get_NA_lineage($descendant,$anscestor) {

		$sons = $this->NA_genealogy[$anscestor];
		if ($sons) {
			if (in_array($descendant,$sons)) {
				return array($descendant.'|s|'.$anscestor);
			} else {
				foreach ($sons as $son) {
					$next_hop = $this->get_NA_lineage($descendant,$son);
					if ($next_hop)
						return array_merge(array($son.'|s|'.$anscestor),$next_hop);
				}
			}
		}
		return false;
	}

    /**
     * Cette fonction va chercher les noms des "gros" NA labels (tous les NA elements dont le NA level > $NA_min)
     * 02/12/2008 - SLC - CB 4100
    *
     * @param array	$tableau des données reçues suite à la query principale
     * @return void	Cette fonction ne retourne rien, mais elle modifie directement le tableau de données qui lui est passé, car elle force le passage de variable par référence
    */
    function getBigNALabels(&$data) {
       global $product,$family,$db_prod;

       $NA_min = $this->min_network_for_query;

       // 14/03/2011 OJT : bz21054, ajout d'un test avant le foreach
       if( is_array( $data ) ) {
           foreach ($data as &$row) {
               foreach ($row as &$value) {
                   if (strpos($value,'|s|')) {
                       list($na,$NA_min_id) = explode('|s|',$value,2);
                       $lineage = $this->get_NA_lineage($NA_min,$na);	// ex: $lineage = array( 'sai|s|rnc', 'rnc|s|network')
                       $child_id = $NA_min_id;
                       // on remonte jusqu'au ID du $na père
                       // 13:54 07/08/2009 GHX
                       // Ajout de la fonction array_reverse
                       // 16/02/2011 BBX
                       // Correction des test sur $child_id
                       // afin de ne pas confondre une valeur "0" avec "false"
                       // BZ 20629
                       if ( is_array($lineage) ) {
                           foreach (array_reverse($lineage) as $hop) {
                               if ($child_id != '') {
                                   $child_id = $db_prod->getone("select eoar_id_parent from edw_object_arc_ref where eoar_id='$child_id' and eoar_arc_type='$hop'");
                               }
                           }
                       }
                       // on va chercher le label du $na
                       if ($child_id != '') {
                           $label = $db_prod->getone("
                            SELECT CASE WHEN eor_label IS NOT NULL THEN eor_label ELSE eor_id END
                            FROM edw_object_ref
                            WHERE eor_id = '$child_id'
                                AND eor_obj_type = '$na'
                                   ");
                       }
                       else {
                           $label = ' ';
                       }
                       // on remplace la valeur
                       $value = $label;
                   }
               }
           }
       }
    }

    /**
     * Retourne la requête complète
     * @return string
     * 15/02/2011 NSE DE Query Builder : création fonction
     */
    function getRequete(){
        return $this->query;
    }

	/**
     * Retourne la première ligne du tableau de résultat
     * @return array
     * 15/02/2011 NSE DE Query Builder : création fonction
     */
    function getEnteteTableau(){
        return $this->liste_entete_tableau;
    }
	/**
	* fonction qui execute la requete
         * 15/02/2011 NSE DE Query Builder : Ajout du paramètre limit pour indiquer si on veut limiter le résultat retourné à un certain nombre
         * Cette limite remplace la limite définie dans la requête.
	*/
	function executer_requete($limit=0)
	{
            // 07/06/2011 BBX -PARTITIONING-
            // Commande valable uniquement avec PG 8.2
            if($this->db->getVersion() < 9.1)
		$this->db->execute("set stats_command_string to on;");
            __debug($this->query,'Requête utilisateur');
            $tps = time();
            // on récupère la limite définie dans la requête
            $limit_num = trim(preg_replace('/LIMIT/', '', $this->limit));
            // si une limite est passée en paramètre et que la limite demandée par l'utilisateur est supérieure à la limite passée
            if($limit!=0 && $limit_num>$limit){
                // On va compter le nombre de résultats retournés par la requête avec la limite de l'utilisateur
                // On modifie la requête de façon à ce que son excécution soit plus rapide
                $tps = time();
                // le résultat est limité avec la limite passée en paramètre. Elle remplace la limite utilisateur
                $this->resultat_query = $this->db->getall(preg_replace('/'.$this->limit.'/'," LIMIT $limit",$this->query));
                __debug('durée execution de la requête limitée à '.$limit.' : '.(time()-$tps).'s');
                // on supprime la jointure sur les tables raw et kpi. On ne va en conserver qu'une seule pour aller plus vite.
                $requete_count_from = preg_replace('/.*AS (kpi|raw).*/',' ',$this->query_from);
                // sur quel table allons-nous travailler (raw ou kpi)?
                // 11/02/2011 NSE DE Query Builder on gère la jointure
                // On va essayer d'exécuter la requête avec un seul datatype
                // de façon à éviter de faire une jointure si elle n'est pas nécessaire
                // on espère ainsi ne pas passer trop de temps sur l'exécution de la requête.
                // si la requête retourne une erreur, alors on tente avec l'autre datatype
                // si la requête retroune toujours une erreur, on est obligé de passer par la jointure.
                $data_type_type = isset($this->source_table['raw'])&&!empty($this->source_table['raw'])?'raw':'kpi';
                // requête pour compter les résultats
                // 11/02/2011 NSE DE Query Builder : on gère les conditions définies par l'utilisateur
                $requete_count = 'SELECT COUNT(*) FROM ( '.
                                    ' SELECT object_ref.eor_id '.
                                    $requete_count_from.$this->source_table[$data_type_type].
                                    ' as '.$data_type_type.
                                    ' WHERE object_ref.eor_id='.$data_type_type.'.'.$this->min_network_for_query.
                                    ($this->query_where_condition != "\n WHERE " && substr($this->query_where_condition, -4) != "AND "?preg_replace('/WHERE/',"AND",$this->query_where_condition):'').
                                    ' '.$this->limit.
                                 ') AS tmp ';
                $tps = time();
                $res_count = $this->db->getall($requete_count);
                // 11/02/2011 NSE DE Query Builder : on gère la jointure
                $erreur = $this->db->getLastError();
                if($erreur && $data_type_type=='raw'){
                    __debug($erreur,'erreur 1 (on va donc tenter avec le datatype kpi)');
                    // on tente alors sur data_type kpi
                    if(isset($this->source_table['kpi'])&&!empty($this->source_table['kpi'])){
                        $data_type_type = 'kpi';
                        $requete_count = 'SELECT COUNT(*) FROM ( '.
                                            ' SELECT object_ref.eor_id '.
                                            $requete_count_from.$this->source_table[$data_type_type].
                                            ' as '.$data_type_type.
                                            ' WHERE object_ref.eor_id='.$data_type_type.'.'.$this->min_network_for_query.
                                            ($this->query_where_condition != "\n WHERE " && substr($this->query_where_condition, -4) != "AND "?preg_replace('/WHERE/',"AND",$this->query_where_condition):'').
                                            ' '.$this->limit.
                                         ') AS tmp ';
                        $res_count = $this->db->getall($requete_count);
                        $erreur = '';
                    }
                }
                $erreur = $this->db->getLastError();
                if($erreur && $data_type_type=='kpi'){
                    __debug($erreur,'erreur 2 : échec avec datatype kpi également (on fait donc la jointure)');
                    // on est obligé de faire la jointure
                    if(isset($this->source_table['kpi'])&&!empty($this->source_table['kpi'])){
                        $requete_count = 'SELECT COUNT(*) FROM ( '.
                                            ' SELECT object_ref.eor_id '.
                                            $this->query_from.
                                            ($this->query_where_condition != "\n WHERE " && substr($this->query_where_condition, -4) != "AND "?preg_replace('/WHERE/',"AND",$this->query_where_condition):'').
                                            $this->query_where_jointure.
                                            ' '.$this->limit.
                                         ') AS tmp ';
                        $res_count = $this->db->getall($requete_count);
                        $erreur = '';
                    }
                }
                __debug($requete_count,'Requête du nombre de résultats');
                __debug('durée comptage du nombre de résultats : '.(time()-$tps).'s');

                if(count($res_count)>1)
                    // s'il y a un group by dans la requête utilisateur
                    $this->nombre_resultat_builder_report = count($res_count);
                else
                    $this->nombre_resultat_builder_report = $res_count[0]['count'];
            }
            else{
                $tps = time();
                // pas de limite en paramètre ou limite supérieure à celle définie dans la requête
		$this->resultat_query = $this->db->getall($this->query);
                __debug('execution de la requête : '.(time()-$tps).'s');
		$this->nombre_resultat_builder_report = count($this->resultat_query);
            }
		// on utilise la fonction de Benjamin pour connaître la généalogie des niveaux d'aggregation
        // 25/05/2011 NSE bz 22218 : utilisation de la méthoe pour accéder à la variable
        $this->setupNaGenealogy(); //NA_genealogy = getPathNetworkAggregation($this->product,$this->family,1,false);
		$this->getBigNALabels($this->resultat_query);
	}

	/**
     * Initialise la généalogie des NA dans la famille
     */
    function setupNaGenealogy(){
        $this->NA_genealogy = taCommonFunctions::getPathNetworkAggregation($this->product,$this->family,1,false);

    }

	/**
	*  Affiche le resultat sous forme d"un tableauformaté
         * 15/02/2011 NSE DE Query Builder : Ajout du paramètre limit pour indiquer si on veut limiter les résultats affiché à un certain nombre
         * Cette limite remplace le nombre de résultats retournés par la requête.
	*/
	function afficher_resultat($limite_affichage=0)
	{
		global $tableau_abscisse_export_excel, $tableau_data_export_excel, $tableau_legend_export_excel;
		global $formule, $nombre_resultat_builder_report;
                $tps = time();
		$nombre_champ = count($this->liste_entete_tableau);
		$array_font_debut = array();
		$array_font_fin = array();

		$nombre_resultat = $this->nombre_resultat_builder_report;
		// teste si le nombre de résultats ne dépasse pas le nombre limite
                // 15/02/2011 NSE DE Query Builder : il n'y a plus de limite haute, suppression de <= 10000
		if ($nombre_resultat > 0) {
			// génère le tableau de résultat
			$image = '../../../../images/graphe.gif';
                        // nombre de résultats retournés par la requête
			print '<p style="text-align: center;font-family: arial;font-weight: bold;">Your query returned '.$nombre_resultat.' lines.<br>';
                        // le nombre maximum de résultats qui doivent être affichés en fonction de la limite définie
                        if($nombre_resultat>$limite_affichage){
                            // le nombre effectif de résultats qui vont être affichés
                            print '<span style="color: red">Only the first '.$limite_affichage.' lines are displayed below.<br>';
                            print "All results can be downloaded as a CSV export.</span>";
                            // le nombre effectif de résultats qui vont être affichés = la limite passée en paramètre
                            $nombre_resultat_affiche = $limite_affichage;
                        }
                        else
                            // le nombre effectif de résultats qui vont être affichés = le nombre réel de résultats
                            $nombre_resultat_affiche = $nombre_resultat;
                        echo "</p><br>";
                        // 15/02/2011 NSE DE Query Builder : fin modifs
			$tableau_resultat = new Tableau_HTML(0, 0);
			$tableau_resultat->tableau_number			= 0; //numero uniquement utilisé pour le builder report
			$tableau_resultat->tableau_entete_colonnes	= $this->liste_entete_tableau;
			$tableau_resultat->line_counter			= "no";
			$tableau_resultat->tableau_width			= 100;
			$tableau_resultat->line_height				= 22;
			$tableau_resultat->header_color			= "FFFFFF";
			$tableau_resultat->line1_color				= $line1_color_default;
			$tableau_resultat->line2_color				= $line2_color_default;
			$tableau_resultat->tableau_font_debut		= array_pad($array_font_debut, $nombre_champ, $font_debut_default);
			$tableau_resultat->tableau_font_fin			= array_pad($array_font_fin, $nombre_champ, $font_fin_default);
			$tableau_resultat->nombre_lignes_tableau	= $nombre_resultat;
			$tableau_resultat->tableau_data			= $resultat_query;

			// transforme le résultat de la requete pour obtenir un format exploitable par la fonction table_generation

			$tableau_data = array();
			$first_row = $this->resultat_query[0];
			$i = 0;
			foreach ($first_row as $field_name => $some_value) {
                            // 15/02/2011 NSE DE Query Builder : on limite le nombre de résultats affichés
				for ($j = 0;$j < $nombre_resultat_affiche;$j++)
					$tableau_data[$i][$j] = $this->resultat_query[$j][$field_name];
				$i++;
			}

			$tableau_resultat->tableau_data = $tableau_data;
			// echo '<hr />';__debug($tableau_data);

			// sauveagarde des données pour l'export vers Excel avec un l'identifiant du graphe qui vaut 0
			// on utilise egalement ces données pour générer le graph du builder Report
			$tableau_legend_export_excel[$tableau_resultat->tableau_number] = $tableau_resultat->tableau_entete_colonnes;
			session_register("tableau_legend_export_excel");
			$tableau_data_export_excel[$tableau_resultat->tableau_number] = $tableau_resultat->tableau_data;
			session_register("tableau_data_export_excel");
			$tableau_abscisse_export_excel[$tableau_resultat->tableau_number] = $tableau_data_export_excel[$tableau_resultat->tableau_number][0];
			session_register("tableau_abscisse_export_excel");
			// Generation du tableau
			$tableau_resultat->Tableau_Generation();
			// ENABLE le bouton de sauvegarde de la query dans le cas où la query a retourné un resultat valable
			?>
			<script>//top.contenu_equation_sql.formulaire_sql.sauver.disabled=false;</script>
			<?php
		} else {
			// DISABLE le bouton "SAve" de la query
			?><script>//top.contenu_equation_sql.formulaire_sql.sauver.disabled=true;</script><?php
			if ($nombre_resultat == 0) {
				print "<center><font face=arial><b>$nombre_resultat Results for your query</b></font></center>";
			} else {
				print "<center><b>Your query returned over $limite_affichage_resultat results";
				print "<br>";
				print "Please be more precise in your query</b></center>";
			}
		}
        __debug('Affichage des résultats : '.(time()-$tps));
	}

    /**
     * Retourne les résultats de la requête
     * @return array(tableau_legend_export_excel,tableau_data_export_excel,tableau_abscisse_export_excel)
     * 15/02/2011 NSE DE Query Builder : création fonction
     */
    function getTableauResultat(){
        $tableau_data = array();
        $first_row = $this->resultat_query[0];
        $i = 0;
        foreach ($first_row as $field_name => $some_value) {
            for ($j = 0;$j < $this->nombre_resultat_builder_report;$j++)
                $tableau_data[$i][$j] = $this->resultat_query[$j][$field_name];
            $i++;
        }

        // sauveagarde des données pour l'export vers fichier avec un l'identifiant du graphe qui vaut 0
        // on utilise egalement ces données pour générer le graph du builder Report
        $tableau_legend_export_excel[0] = $this->liste_entete_tableau;
        $tableau_data_export_excel[0] = $tableau_data;
        $tableau_abscisse_export_excel[0] = $tableau_data_export_excel[0][0];
        // tableau_legend_export_excel, tableau_data_export_excel, tableau_abscisse_export_excel
        return array($this->liste_entete_tableau,$tableau_data,$tableau_data_export_excel[0][0]);
    }

    /**
     * Retourne les résultats de la requête
     * @return <integer> id produit
     * 21/02/2011 NSE DE Query Builder : création fonction
     */
    function getProduct(){
        return $this->product;
    }
}
?>