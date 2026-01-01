<?php
/*
*	@cb41000@
*
*	03/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	- maj 03/12/2008 - SLC - gestion multi-produit, suppression de $database_connection
*	08/06/2009 SPS 
*	  - ajout de quotes autour de l'id_user
*
*/
?><?php
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*/
?>
<?php
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*
*	- maj 31/05/2007 Gwénaël : correction sur l'affichage du label du na s'il y a une troisième axe
*/
?>
<?php
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
<?php
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
<?php
	/*
		Permet d'afficher les alarmes ayant trop de résultats.
		- maj 29 09 2006 christophe : modification du texte du titre .
		- maj 11 10 2006 christophe : correction bug apparition array.
		- maj 05 09 2007 maxime :  Si la table edw_alarm est vide on récupère le dernier calculation_time de la table edw_alarm_log_error
	*/
	class alarmLogError{

		/*
			$alarmLogError_parameters est un tableau contenant tous les paramètres nécessaires.

			$alarmLogError_parameters["query_timestamp"] : partie de la clause where de la requête avec la condition sur le timestamp.
			$alarmLogError_parameters["query_ta"] : partie de la clause where de la requête avec la condition sur la time aggregation.
			$alarmLogError_parameters["mode"] : mode d'affiche > management ou history.
			$alarmLogError_parameters["sous_mode"] :  sous mode d'affiche > elem_reseau ou condese.
			$alarmLogError_parameters["database_connection"] - OBSOLETE
			$alarmLogError_parameters["product"] : id du produit en cours
		*/

		function alarmLogError($alarmLogError_parameters){
			global $id_user;

			$this->alarmLogError_parameters = $alarmLogError_parameters;
			
			// 03/12/2008 - SLC - gestion multi-produit
			$this->product	= $this->alarmLogError_parameters['product'];
			$this->db	= Database::getConnection( $this->product );

			$this->id_user = $id_user;

			// Couleurs de lignes de résultats.
			$this->couleur_fond1 = "#f2f2f2";	// couleur de la 1ère ligne
			$this->couleur_fond2 = "#d3d3d3";

			$this->emptySysContenuBuffer();
			$this->tab_data = $this->executeQuery($this->getQuery());
			
		}

		/*
			Retourne la requête à exécuter.
		*/
		function getQuery(){
			// maj 05/09/2007 mp -> Si la table edw_alarm est vide on récupère le dernier calculation_time de la table edw_alarm_log_error
			$query	= "SELECT count(*) FROM edw_alarm";
			$nb_res	= $this->db->getone($query);
		
			if ($nb_res == 0) {
				$this->alarmLogError_parameters["query_timestamp"] = "
					AND
						calculation_time >= (SELECT calculation_time FROM edw_alarm_log_error ORDER BY calculation_time desc LIMIT 1) - INTERVAL '1 day'
					AND
						calculation_time IS NOT NULL
				";
			}
			
			// Clause select et from.
			$query_select_from = "
				SELECT
					t2.oid, t2.*,
					CASE WHEN t2.type='dyn_alarm' THEN
						(SELECT alarm_name FROM sys_definition_alarm_dynamic WHERE alarm_id=t2.id_alarm LIMIT 1)
					ELSE
						(SELECT alarm_name FROM sys_definition_alarm_static WHERE alarm_id=t2.id_alarm LIMIT 1)
					END as alarm_name,
					CASE WHEN t2.type='dyn_alarm' THEN
						(SELECT family FROM sys_definition_alarm_dynamic WHERE alarm_id=t2.id_alarm LIMIT 1)
					ELSE
						(SELECT family FROM sys_definition_alarm_static WHERE alarm_id=t2.id_alarm LIMIT 1)
					END as family
					FROM edw_alarm_log_error t2
			";
			$query_order_by = "
				ORDER BY
					t2.calculation_time, t2.critical_level
			";

			$query = 	$query_select_from.
						"
							WHERE
								(type='static' OR type='dyn_alarm')
						".
						$this->alarmLogError_parameters["query_ta"].
						$this->alarmLogError_parameters["query_timestamp"].
						$query_order_by;

			$this->query = $query;
			return $query;
		}


		/*
			executeQuery
				$query : requête à exécuter.
			retourne un tableau contenant la liste des éléments à afficher.
		*/
		function executeQuery($query){

			$result = $this->db->getall($query);
			if ($result) {
				foreach ($result as $i => $row) {
					$tab_data[$i]["alarm_name"] =		$row["alarm_name"];
					$tab_data[$i]["family"] = 			$row["family"];
					$tab_data[$i]["oid"] = 			$row["oid"];
					$tab_data[$i]["id_alarm"] = 		$row["id_alarm"];
					$tab_data[$i]["ta"] = 			$row["ta"];
					$tab_data[$i]["ta_value"] = 		$row["ta_value"];
					$tab_data[$i]["na"] = 			$row["na"];
					$tab_data[$i]["a3"] = 			$row["a3"];
					$tab_data[$i]["a3_value"] = 		$row["a3_value"];
					$tab_data[$i]["alarm_type"] = 		$row["type"];
					$tab_data[$i]["calculation_time"] = $row["calculation_time"];
					$tab_data[$i]["nb_result"] = 		$row["nb_result"];
					$tab_data[$i]["critical_level"] = 	$row["critical_level"];
				}

			} else {
				$tab_data = "";
			}

			return $tab_data;
		}

		/*
			Permet de savoir si il y a pour les paramètres donné des alarmes avec trop de résultats.
			Retourne un booléen
		*/
		function existsResults(){
			if($this->tab_data != "") return true;
			return false;
		}

		/*
			Retounre le header du tableau html.
		*/
		function getHeader(){
			switch($this->alarmLogError_parameters["mode"]){
				case 'management':
					if($this->alarmLogError_parameters["sous_mode"] == "elem_reseau"){
						$header_tableau = Array('Calculation time','Network element',	'Alarm name / source time', 'Number of results','Level');
					} else {
						$header_tableau = Array('Calculation time','Alarm Name',	'Network element / source time ', 'Number of results','Level');
					}
					break;
				case 'history' :
					$header_tableau = Array('Calculation time','Network element',	'Alarm name / source time', 'Number of results','Level');
					break;
				default:
					$header_tableau = Array('Calculation time','Network element',	'Alarm name / source time', 'Number of results','Level');
					break;
			}
			return $header_tableau;
		}

		/*
			Retourne le label d'un type d'alarme.
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
			Construit l'affichage des alarmes ayant trop de résultats.
			Le contenu est mis dans la variable $this->html.
		*/
		function buildDisplay(){
			global $niveau0;

			$this->ta_array			= getTaList('',$this->product);		// chargement du tableau contenant la liste des TA/TA_label
			$this->family_array		= getFamilyList($this->product);	// chargement du tableau contenant la liste des family/family_label
			$this->na_label_array	= getNaLabelList($this->product);	// chargement du tableau contenant la liste des  na/na_label mais PAs les na_value !!!!!! seulement les nom des network aggregation

			$titre = "Elements with too many results.";
			$header = $this->getHeader();	// header du tableau html.
			$this->html = "<h1><img src='".$niveau0."images/icones/puce_fieldset.gif'/>&nbsp;$titre</h1><hr/>";
		
			if($this->tab_data != ""){
				$this->html .= "<table cellpadding='2' cellspacing='1' width='100%'>";
				// On construit l'entête du tableau.
				$this->html .= "<tr>";
						foreach($header as $label) $this->html .= "<th>$label</th>";
				$this->html .= "</tr>";
				// Contenu du tableau.
				$cpt = 0;
				foreach($this->tab_data as $row){
					$boolLigne = (bool) (($cpt % 2) == 0);
					$bg_color = ($boolLigne) ? $this->couleur_fond1 : $this->couleur_fond2;
					
					$this->html .= "<tr style='font : normal 9pt Verdana, Arial, sans-serif; background-color:$bg_color;'>";
						$this->html .= "<td>".$row["calculation_time"]."</td>";

						$this->html .= "<td>";
						if($this->alarmLogError_parameters["sous_mode"] == 'elem_reseau'){
							// modif 31/05/2007 Gwénaël
								// On explode le na pour pouvoir récupérer le na de l'axe1 et celui ce l'axe3 s'il existe pour afficher leur label
							$na = explode('_', $row["na"]);
							$this->html .= $this->na_label_array[$na[0]][$row["family"]];
							if(isset($na[1]))
								$this->html .= ', '. $this->na_label_array[$na[1]][$row["family"]];
							$this->html .= " (".$this->family_array[$row["family"]].")";
						} else {
							$this->html .= $row["alarm_name"];
							$this->html .= " (".$this->getAlarmTypeLabel($row["alarm_type"]);
							$this->html .= ", ".$this->family_array[$row["family"]].")";
						}
						if($row["a3_value"] != "" && GetAxe3($row["family"],$this->product)){
							$this->html .= " (HN=".get_axe3_label($row["a3_value"],$this->product).") ";
						}
						$this->html .= "</td>";

						$this->html .= "<td>";
							if($this->alarmLogError_parameters["sous_mode"] == 'elem_reseau'){
								$this->html .= $row["alarm_name"];
								$this->html .= " (".$this->getAlarmTypeLabel($row["alarm_type"]).")";
							} else {
								$this->html .= $this->na_label_array[$row["na"]][$row["family"]];
							}
							$this->html .= " [";
							$this->html .= $this->ta_array[$row["ta"]];
							$this->html .= ", ".getTaValueToDisplay($row["ta"], $row["ta_value"]);
						$this->html .= "]</td>";

						$this->html .= "<td>".$row["nb_result"]."</td>";
						$this->html .= "<td>".$row["critical_level"]."</td>";
					$this->html .= "</tr>";

					$cpt++;
				}

				$this->html .= "</table>";
			}
		}

		/*
			Enregistre l'affichage dans sys_contenu_buffer
			08/06/2009 SPS ajout de quotes autour de l'id_user
		*/
		function saveInBuffer(){
			$html = addslashes ($this->html);
			$query_insert = "
				INSERT INTO sys_contenu_buffer
					(object_contenu_type, id_user, object_type, object_source)
					VALUES
					('html', '$this->id_user', 'alarm_too_much_result', '$html')
			";
			$this->db->execute($query_insert);
		}

		/*
			Vide le contenu de la table SYS_CONTENU_BUFFER pour le user d'identifiant id_user.
			08/06/2009 SPS ajout de quotes autour de l'id_user
		*/
		function emptySysContenuBuffer(){

			$query_delete = "
				DELETE FROM SYS_CONTENU_BUFFER
					WHERE id_user='$this->id_user'
					AND object_type='alarm_too_much_result'
				";
			$this->db->execute($query_delete);

		}


	} // fin class

?>
