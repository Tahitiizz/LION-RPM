<?/*
 *	@cb5310@
 *
 * 18/03/2013 SNMP Trap community global parameter
 *
 */
?><?
/*
 *	@cb51404@
 *
 * 02/08/2011 NSE bz 18039 : les informations passées à la Trap ne sont pas bonnes dans le cas des alarmes dynamiques.
 * 2012/09/27 NSE bz 29055 : réécriture de la méthode getMaxChaine qui faisait un peu n'importe quoi...
 *
 */
?><?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
	- maj 26/02/2008, benoit : dans la fonction 'get_alarm_results()', s'il existe plusieurs valeurs de ta à rechercher (ex : liste d'heures),    on les convertit en liste de valeurs
*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*
*	- 13/04/2007 christophe : gestion du 3ème axe.
*
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
<?php
/*
*	@cb21000@
*
*	08/12/2006 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.00
*/
?>
<?php
/*
*
CREATE TABLE sys_alarm_snmp_sender
(
  id_alarm integer,
  alarm_type text
)
WITH OIDS;

INSERT INTO sys_global_parameters VALUES ('snmp_server','localhost','1','client', 'SNMP Server ','IP address of the server that will receive the SNMP Traps');
INSERT INTO sys_global_parameters VALUES ('snmp_trap_format','1','1','client', 'SNMP trap format version ','Version of the format used to send SNMP traps (either 1 for version 1 or 2c)');
*
*
		class alarmSNMP.calss.php
		Permet d'envoyer des alarmes au format SNMP
		01-02-2007 MP : creation du fichier

*
*
*   - maj 28/02/2007 Gwénaël : ajout du paramètre PORT dans l'envoi de trap
*
*/
class alarmSNMP {
    /**
     * Constructeur
     */
    function  __construct($database_connection, $offset_day)
    {
        $this->database_connection = $database_connection;
        $this->offset_day = $offset_day;
        $this->time_to_calculate = get_time_to_calculate($this->offset_day); // fonctions définies dans edw_function.php qui permet de savoir quels sont les time aggregations pris en compte pour le compute et donc le calcul et l'envoi d'alarmes
        $this->compute_mode = get_sys_global_parameters("compute_mode"); // Valeurs possibles : hourly ou daily.
        $this->debug = get_sys_debug('alarm_SNMP'); // Affichage du mode Debug ou non.
        $this->flag_axe3 = false;
        $this->alarm_type = array('static', 'dyn_alarm'); //pour les alarmes SNMP, on ne prend pas les Top/Worst. On a donc seulement 2 types d'alarmes
		$this->alarm_type_table['dyn_alarm'] = 'dynamic'; // libellé des tables pour les différents types d'alarmes
		$this->alarm_type_table['static'] = 'static';
		$this->entreprise = "enterprises.4318";
		$this->trap = array(); // Tableau comprenant l'ensemble des traps snmp
		$this->lst_ta_label = getTaList(); // Tableau comprenant les labels des ta
		$this->lst_na_label = getNaLabelList(); // Tableau comprenant les labels des na
		$this->lst_family = getFamilyList(); // Tableau comprenant toutes les familles
    }

    /**
     * Retourne le label d'un élément réseau donné. Si aucun label n'existe pour
     * l'élément réseau son identidiant est retourné
     *
     * 18/01/2011 OJT : Correction bz19693. Réécriture de la méthode via le modèle
     *
     * @param  string $na_value Identifiant de l'élément réseau
     * @param  string $na       Niveau d'aggregation correspondant
     * @return string
        */
    function getNaValueLabel ( $na_value, $na )
	{
        // Si le le label de l'élément réseau n'a pas déjà été recherché
		if ( !isset ( $this->neLabelList[$na_value] ) )
        {
            // On récupère le label via le modèle NeModel
            $this->neLabelList[$na_value] = NeModel::getLabel( $na_value, $na );

            // Si aucun label n'existe, on prend son identifiant
            if( $this->neLabelList[$na_value] == false )
            {
                $this->neLabelList[$na_value] = $na_value;
                }
            }
        return $this->neLabelList[$na_value];
        }

	// On récupère tous les kpi
	function set_kpi_label()
	{
		foreach($this->lst_family as $name=>$label) {
			$this->kpi_label_list[$name] = get_kpi($name);
		}
	}
	// On récupère tous les raw
	function set_raw_label()
	{
		foreach($this->lst_family as $name=>$label) {
			$this->raw_label_list[$name] = get_counter($name);
		}
	}

	// Retourne le niveau de criticité sous forme d'entier
	function get_critical_level($criticite) {
		switch($criticite) {
			case "critical": $this->criticite = 0;
							 break;
			case "major": $this->criticite = 1;
							 break;
			case "minor": $this->criticite = 2;
							 break;
			case "warning" : $this->criticite = 4;
							 break;
			case "default": $this->criticite = 3;
							 break;
		}
		return $this->criticite;
	}

	// Retourne le label d'un kpi à partir de son nom et de la famille
	function get_kpi_label($name,$family)
	{
		return $this->kpi_label_list[$family][$name];
	}

	// Retourne le label d'un raw à partir de son nom et de la famille
	function get_raw_label($name,$family)
	{
		return $this->raw_label_list[$family][$name];
	}

	/*
	On récupère une chaîne d'une trap, si la longueur de celle-ci est > à la valeur max, on coupe la chaîne au dernier |
         * 2012/09/27 NSE bz 29055 : réécriture de la méthode qui faisait un peu n'importe quoi...
	*/
	function getMaxChaine($chaine,$max)
	{
		if(strlen($chaine)>$max)
		{
			$fields = explode('|',$chaine); // on récupère tous les éléments compris entre les | de la chaîne
			$nb = count($fields);
                        // si on a au moins 1 |, donc 2 paramètres
			if($nb>1)
			{
                            $Tchaine='';
                            // tant que la chaîne est trop longue, on enlève 1 champ
                            while(strlen($Tchaine)>$max){
                                array_pop($fields);
                                $Tchaine = '"'.implode('|',$fields).'..." '; // On réinsère tous les éléments mise à part le dernier
				}
			}
			else{
                            $Tchaine = substr($chaine,0,$max-3).'..." ';

                        }

		}
		else
			{$Tchaine = $chaine;}
	return $Tchaine;
	}

	// Fonction qui retourne la valeur trouvée pour threshold concernant les alarmes dynamiques
	function get_threshold_value_result($value,$additional_details){

		$fields = explode("@",$additional_details);
		// Calcul de la valeur trouvée qui a déclenché l'alarme
        // 02/08/2011 NSE bz 18039 : le calcul est faux. On remplace par la bonne formule (moyenne - valeur) / écart
        // (bon anniversaire à Timothée : 3 ans !)
        $threshold_value_result = "abs( ({$fields[0]} - $value) / {$fields[1]})";
		$threshold_value_result = round($threshold_value_result,2);
        return $threshold_value_result;
	}

 /**
     * Construit un tableau contenant la liste des alarmes à envoyer en fonction de la définition des alarmes et de leur envoi ou non au format SNMP
     * le tableau est au format $tab[type d'alarme][identifiant de l'alarme][nom de la famille][time aggregation][network aggregation] = identifiant de l'alarme
     */
    function getAlarms()
    {
        $this->tabVide = true;
        // il y a 2 types d'alarmes donc 2 requetes qui vont être exécutées
        for ($i = 0;$i < count($this->alarm_type);$i++) {
            // On construit la requête qui récupère la liste des id_alarmes.
            $query_alarm = "
					SELECT
						alarm_id,
						family,
						time as ta,
						network as na,
						id_group_table,
						hn_value
					FROM
						sys_definition_alarm_" . $this->alarm_type_table[$this->alarm_type[$i]] . " t3,
						sys_alarm_snmp_sender t4
					WHERE
						t3.alarm_id = t4.id_alarm
					AND
            alarm_type = '" . $this->alarm_type[$i] . "'
					AND (

			";
            // On construit l'autre condition basée sur le time_to_calculate
            $find = false;
            foreach($this->time_to_calculate as $time_aggregation => $time_to_calculate) {
                if ($find) $query_alarm .= " OR ";
                $query_alarm .= " t3.time = '$time_aggregation' ";
                $find = true;
                if ($time_aggregation == 'day') $this->ta_value_day = getTaValueToDisplayV2("day", $this->time_to_calculate["day"], "-");
            }
            $query_alarm .= ") GROUP BY alarm_id,alarm_name,family,time,network, id_group_table, hn_value ";
            print $query_alarm . "<br><br>";
            $result = pg_query($this->database_connection, $query_alarm);
            if ($this->debug) echo "<br><u>Query de la liste des alarmes à envoyer :</u>" . $query_alarm . "<br>";

            /*
    		On construit le tableau contenant la liste des alarmes par famille / TA et NA.
    		Structure du tableau :
    			$tab[type d'alarme][identifiant de l'alarme][nom de la famille][time aggregation][network aggregation] = identifiant de l'alarme
    		*/
            $nombre_resultat = pg_num_rows($result);


		    if ($this->debug) {echo "Il y a <b>" . $nombre_resultat . "</b> résultats pour les alarmes de type " . $this->alarm_type_table[$this->alarm_type[$i]] . ".<br>";}
            if ($nombre_resultat > 0) {
                for ($k = 0;$k < $nombre_resultat;$k++) {
                    $row = pg_fetch_array($result, $k);
                    $tab_alarms[$this->alarm_type[$i]][$row["alarm_id"]][$row["family"]][$row["ta"]][$row["na"]] = $row["alarm_id"] ;
                    // if (get_axe3($row["family"])) {
                        // $this->flag_axe3 = true;
                    // }
                }

                $this->tabVide = false;
            } else {
                echo "<b>Il n'y a aucune alarme de type " . $this->alarm_type_table[$this->alarm_type[$i]] . " pour ce compute mode.</b><br>";
            }
        }

        if ($this->debug) {
            echo "<br><b> Tableau des alarmes </b><pre>";
            var_dump($tab_alarms);
            echo "</pre><br>";
        }

        return $tab_alarms;
    }

    /**
     * fonction qui va collecter les informations concernant la définition de chaque alarme pour celles qui doivent être envoyée au format SNMP
     * Le tableau est organisé comme suit :
     * $this->tab_alarm["type de l'alarme]["identifiant de l'alarme"]
     * les sous tableaux suivant sont créés :
     * 			["family"]= nom de la famille
     * 			["alarm_name"]= nom de l'alarme
     * ["na"]= niveau d'agrégation réseau
     * ["ta"]= niveau d'agrégation temporel
     * ["niveau critique"]. Puis pour chaque niveau critique 2 sous tableaux sont crées contenant d'un contenant les triggers (avec leur definition) et les champs additionnels. Pour ces tableaux la clé est le nom du trigger présent en base
     */

    function get_alarm_definition ()
    {
        // on execute 2 requetes (2 types différents) afin de collecter la definition de chaque alarme qui doit être envoyée au format SNMP
		foreach ($this->alarm_type as $type) {
            // on récupère les infos de l'alarme
			if($this->tab_snmp_alarms[$type]!=null){

				$liste_alarme = implode("','", array_keys($this->tab_snmp_alarms[$type]));
				// Recupère toutes les informations concernant les alarmes et les stockes dans un tableau
				$query = "SELECT * FROM sys_definition_alarm_" . $this->alarm_type_table[$type] . " WHERE alarm_id IN ('" . $liste_alarme . "')";
				$resultat = pg_query($this->database_connection, $query);
				$nombre_resultat = pg_num_rows($resultat);
				if ($nombre_resultat > 0) {
					for ($k = 0;$k < $nombre_resultat;$k++) {
						$row = pg_fetch_array($resultat, $k);
						// sauvegarde des informations générales sur l'alarmes
						// les données sont réécrites plusieurs fois car pour une même alarme, il y a plusieurs lignes. Cela n'a pas d'importance
						$this->tab_alarm[$type][$row["alarm_id"]]["family"] = $row["family"];
						$this->tab_alarm[$type][$row["alarm_id"]]["name"] = $row["alarm_name"];
						$this->tab_alarm[$type][$row["alarm_id"]]["na"] = $row["network"];
						$this->tab_alarm[$type][$row["alarm_id"]]["ta"] = $row["time"];

						// Sauvegarde des informations détaillées (trigger et additional field)
						switch($type) {
							case "static":
								if ($row["alarm_trigger_data_field"] != "") { // si l'information n'est pas vide c'est un trigger sinon c'est un champ additionnel
									$this->tab_alarm[$type][$row["alarm_id"]][$row["critical_level"]]["triggers"][$row["alarm_trigger_data_field"]]["trigger_name"] = $row["alarm_trigger_data_field"];
									$this->tab_alarm[$type][$row["alarm_id"]][$row["critical_level"]]["triggers"][$row["alarm_trigger_data_field"]]["trigger_operand"] = $row["alarm_trigger_operand"];
									$this->tab_alarm[$type][$row["alarm_id"]][$row["critical_level"]]["triggers"][$row["alarm_trigger_data_field"]]["trigger_value"] = $row["alarm_trigger_value"];
									$this->tab_alarm[$type][$row["alarm_id"]][$row["critical_level"]]["triggers"][$row["alarm_trigger_data_field"]]["trigger_type"] = $row["alarm_trigger_type"];
								}
								else {
									$this->tab_alarm[$type][$row["alarm_id"]]["additional_fields"][$row["additional_field"]]["field_name"] = $row["additional_field"];
									$this->tab_alarm[$type][$row["alarm_id"]]["additional_fields"][$row["additional_field"]]["field_type"] = $row["additional_field_type"];

								}
								break;
							case "dyn_alarm":
								if($row["alarm_field"] != "") { // si l'info n'est pas vide et que l'alarme est dynamique, on récupère le trigger correspondant au champs alarm_field
									$this->tab_alarm[$type][$row["alarm_id"]][$row["critical_level"]]["triggers"][$row["alarm_field"]]["threshold_name"] = $row["alarm_field"];
									$this->tab_alarm[$type][$row["alarm_id"]][$row["critical_level"]]["triggers"][$row["alarm_field"]]["threshold_value"] = $row["alarm_threshold"];
									$this->tab_alarm[$type][$row["alarm_id"]][$row["critical_level"]]["triggers"][$row["alarm_field"]]["trigger_type"] = $row["alarm_field_type"];

									if ($row["alarm_trigger_data_field"] != "") { // si l'information n'est pas vide, on récupère le deuxième trigger
									$this->tab_alarm[$type][$row["alarm_id"]][$row["critical_level"]]["triggers"][$row["alarm_trigger_data_field"]]["trigger_name"] = $row["alarm_trigger_data_field"];
									$this->tab_alarm[$type][$row["alarm_id"]][$row["critical_level"]]["triggers"][$row["alarm_trigger_data_field"]]["trigger_operand"] = $row["alarm_trigger_operand"];
									$this->tab_alarm[$type][$row["alarm_id"]][$row["critical_level"]]["triggers"][$row["alarm_trigger_data_field"]]["trigger_value"] = $row["alarm_trigger_value"];
									$this->tab_alarm[$type][$row["alarm_id"]][$row["critical_level"]]["triggers"][$row["alarm_trigger_data_field"]]["trigger_type"] = $row["alarm_trigger_type"];
									}
								}
								else {	// si l'information n'est pas vide c'est un trigger sinon c'est un champ additionnel
									$this->tab_alarm[$type][$row["alarm_id"]]["additional_fields"][$row["additional_field"]]["field_name"] = $row["additional_field"];
									$this->tab_alarm[$type][$row["alarm_id"]]["additional_fields"][$row["additional_field"]]["field_type"] = $row["additional_field_type"];
								}

								break;
						}// switch
					}
				}
			}
		}
    }

    /**
     * fonction qui va collecter les résultats pour les alarmes
     * qui font partie de la liste des alamres à envoyer par SNMP et qui corresponde au time_to_calculate
     */
    function get_alarm_results()
    {
        // produit la liste des alarmes pour lesquelles récuperer les résultat pour les types pris en compte
        foreach ($this->time_to_calculate as $ta => $ta_value) {
            foreach ($this->alarm_type as $type) {
                if($this->tab_snmp_alarms[$type]!=NULL)
				{
					$liste_alarme = implode("','", array_keys($this->tab_snmp_alarms[$type]));

					// 26/02/2008 - Modif. benoit : s'il existe plusieurs valeurs de ta à rechercher (ex : liste d'heures), on les convertit en liste de valeurs

					if(is_array($ta_value)){
						$ta_condition = "AND ta_value IN (".(implode(", ", $ta_value)).")";				
					}
					else 
					{
						$ta_condition = "AND ta_value = ".$ta_value;
					}

					// Execute la requete de récupération des résultats
					$query = "
						SELECT id_alarm,t0.id_result,ta_value,na_value,a3,a3_value,alarm_type,additional_details,critical_level,trigger,value,field_type
						FROM edw_alarm t0,edw_alarm_detail t1
						WHERE t0.id_result=t1.id_result
						AND ta='$ta'
						$ta_condition
						AND alarm_type='$type'
						AND id_alarm IN ('" . $liste_alarme . "')
					";
					print "->" . $query . '<br>';
					$resultat = pg_query($this->database_connection, $query);

					$nombre_resultat = pg_num_rows($resultat);

					// pour chaque résulat d'alarme, on complète un tableau dont la structure est identique à celui qui contient la définition des alarmes
					// les éléments servant à raccrocher les morceaux sont d'une part le type, l'identifiant d'alarme et le nom du raw ou du kpi pour faire le lien avec le trigger ou le champ additionnel
					if ($nombre_resultat > 0) {
						for ($i = 0;$i < $nombre_resultat;$i++) {
							$row = pg_fetch_array($resultat, $i);
							$id_alarm = $row["id_alarm"];
							$id_result = $row["id_result"];
							$this->tab_alarm_result[$type][$id_alarm][$id_result]["ta_value"] = $row["ta_value"];
							$this->tab_alarm_result[$type][$id_alarm][$id_result]["na_value"] = $row["na_value"];
							$this->tab_alarm_result[$type][$id_alarm][$id_result]["a3"] = $row["a3"];
							$this->tab_alarm_result[$type][$id_alarm][$id_result]["a3_value"] = $row["a3_value"];
							$this->tab_alarm_result[$type][$id_alarm][$id_result]["criticite"] = $row["critical_level"];
							if ($row["field_type"] == 'trigger') {
								$this->tab_alarm_result[$type][$id_alarm][$id_result][$row["critical_level"]]["triggers"][$row["trigger"]]["trigger_result_value"] = $row["value"];
								if($type=="dyn_alarm" and $row["additional_details"]!=NULL){
									// On récupère la valeur trouvée pour threshold => calcul : (value - avg) / ecart_type
									$this->tab_alarm_result[$type][$id_alarm][$id_result][$row["critical_level"]]["triggers"][$row["trigger"]]["threshold_value_result"] = $this->get_threshold_value_result($row["value"],$row["additional_details"]);
									}
							} else {
								$this->tab_alarm_result[$type][$id_alarm][$id_result]["additional_fields"][$row["trigger"]]["field_value"] = $row["value"];
							}
						}
					} else {
						print "<b>No results for SNMP Alarms</b><br><br>";
					}
				}
			}
        }
	}

    // fonction qui génère pour chaque alarm la TRAP SNMP correspondante
    function generate_SNMP_trap()
    {
        $trap_version = get_sys_global_parameters("snmp_trap_format");
        $trap_server = get_sys_global_parameters("snmp_server");
        $trap_port = get_sys_global_parameters("snmp_port");
        $cmd_hostname = exec("hostname", $array_result);
        $hostname = $array_result[0];
        $version_name = get_sys_global_parameters("product_name") . " - " . get_sys_global_parameters("product_version");
        // 18/03/2013 SNMP Trap community global parameter
        // 08/07/2013 NSE bz 34789: SNMP Trap community global parameter
        // ajout de " autour du paramètre community
        $community = get_sys_global_parameters("snmp_community","public");
        $agent_address = get_sys_global_parameters("snmp_agent_address","");
        switch ($trap_version) {
            case "1":
                $trap[0] = "-v " . $trap_version . ' -c "'.$community.'" ' . $trap_server . ":" . $trap_port . " $this->entreprise \"". $agent_address ."\" 6 4 \"\" ";
                break;
            case "2c":
                $trap[0] = "-v " . $trap_version . ' -c "'.$community.'" ' . $trap_server . ":" . $trap_port . " \"\" $this->entreprise.0.4 ";
                if($agent_address!=""){//there is an agent address to specify
                	$trap[0] .= "snmpTrapAddress.0 a \"" . $agent_address ."\" "; //RFC 2576 Coexistence between SNMP versions => cf 3.1.4
                }
                break;
            default:
                print "The version of the trap in sys_global_paramleters is not correct (either 1 or 2c must be types)<br>";
        } // switch
        $trap[1] = " s \"$hostname\" "; //Probname
        $trap[2] = " s \"$version_name\" "; //Application Name

		echo '<br/>';
		$nb_trap = 0; // on initialise le nombre de trap à générer
        foreach ($this->tab_alarm_result as $type => $id_alarm) {

			$nb_param_trap = 0;

			foreach($id_alarm as $id=>$id_result) {
				foreach($id_result as $result=>$val) {
					$this->trap[$nb_trap] = $trap[0];

					// On récupère les informations propres à chaque alarme
					$family = $this->tab_alarm[$type][$id]["family"];
					$alarm_name = $this->tab_alarm[$type][$id]["name"];
					$na = $this->tab_alarm[$type][$id]["na"];
					$na_value = $val["na_value"];
                    $a3 = trim( $val["a3"] ); // Variables permettant de gérer le troisième axe
                    $a3_value = trim( $val["a3_value"] );
					$ta = $this->lst_ta_label[$this->tab_alarm[$type][$id]["ta"]];
					$ta_value = $val["ta_value"];
					$criticite = $val["criticite"];

					// On récupère les labels pour la construction de la trap snmp
					$label_type = $this->alarm_type_table[$type]; // Label du type de l'alarme (dynamic pour dyn_alarm)
					$label_family = $this->lst_family[$family]; // Label de la famille
					$critical = $this->get_critical_level($criticite); // Niveau de criticite sous forme d'entier
					$label_na = $this->lst_na_label[$na][$family]; // Label du niveau d'aggrégation

					// On ajoute les triggers
					// Alarm description (raw/kpi + sign + value pour les alarmes statiques - raw/kpi + threshold_value + raw/kpi + sign + value pour les alarmes dynamiques)
					$trap[7] = " s \"";
					$nb_triggers = 1;
					$triggers = $this->tab_alarm_result[$type][$id][$result][$criticite]["triggers"]; // On récupère tous les triggers

                    // 02/08/2011 NSE bz 18039 : les informations passées à la Trap ne sont pas bonnes dans le cas des alarmes dynamiques.
                    // On sépare le traitement alarme stat/dyn 
                    if($type=='dyn_alarm') { // Si l'alarme est dynamique
                        // initialisation des variables
                        $threshold_name = $threshold_value_result = $threshold_label = $threshold_value = '';
                        $trigger_name = $trigger_label = $trigger_operand = $trigger_value = $trigger_value_result = '';

                        foreach($triggers as $triggerThreshold_name=>$trigger_values){
                        
                            $trigger_type = $this->tab_alarm[$type][$id][$criticite]["triggers"][$triggerThreshold_name]["trigger_type"];

                            // on récupère la condition du trigger ainsi que le résultat obtenu
                            // on détermine si on est sur un Threshold ou un Trigger
                            if(isset($this->tab_alarm[$type][$id][$criticite]["triggers"][$triggerThreshold_name]['threshold_name'])){
                                // on est sur un threshold
                                $threshold_name = $triggerThreshold_name;
                                $threshold_value_result = $trigger_values["trigger_result_value"];
                                if($trigger_type=="kpi") {
                                    // On remplace le nom par le label
                                    $threshold_label = $this->get_kpi_label($this->tab_alarm[$type][$id][$criticite]["triggers"][$threshold_name]["threshold_name"],$family);
                                }
                                else {
                                    $threshold_label = $this->get_raw_label($this->tab_alarm[$type][$id][$criticite]["triggers"][$threshold_name]["threshold_name"],$family);
                                }
                                $threshold_value = $this->tab_alarm[$type][$id][$criticite]["triggers"][$threshold_name]["threshold_value"];
                            }
                            if(isset($this->tab_alarm[$type][$id][$criticite]["triggers"][$triggerThreshold_name]['trigger_name'])){ 
                                // on est sur un trigger
                                $trigger_name = $triggerThreshold_name;
                                if($trigger_type=="kpi") { // on remplace le nom du raw/kpi par son label
                                    $trigger_label = $this->get_kpi_label($trigger_name,$family);
                                }
                                else {
                                    $trigger_label = $this->get_raw_label($trigger_name,$family);
                                }
                                $trigger_operand = $this->tab_alarm[$type][$id][$criticite]["triggers"][$trigger_name]["trigger_operand"];
                                $trigger_value = $this->tab_alarm[$type][$id][$criticite]["triggers"][$trigger_name]["trigger_value"];
                                $trigger_value_result = round($trigger_values["trigger_result_value"],2);
                            }
                        } // fin foreach trigger
 
                        $trap[7].= "$threshold_label $threshold_value ($threshold_value_result)";

                        // Si le trigger n'a pas été défini on retient uniquement le threshold
                        if($trigger_name!=null && !empty($trigger_name)) {
                            $trap[7].= " | $trigger_label $trigger_operand $trigger_value ($trigger_value_result)";
                        }
                        $nb_triggers++;                    
                    }
                    else { // Si l'alarme est statique on retient uniquement les informations du ou des triggers
                    
                        foreach($triggers as $trigger_name=>$trigger_values){
                            $trigger_type = $this->tab_alarm[$type][$id][$criticite]["triggers"][$trigger_name]["trigger_type"];
                            if($trigger_type=="kpi") { // on remplace le nom du raw/kpi par son label
                                $trigger_label = $this->get_kpi_label($trigger_name,$family);}
                            else {
                                $trigger_label = $this->get_raw_label($trigger_name,$family);}
                            // on récupère la condition du trigger ainsi que le résultat obtenu
                            $trigger_operand = $this->tab_alarm[$type][$id][$criticite]["triggers"][$trigger_name]["trigger_operand"];
                            $trigger_value = $this->tab_alarm[$type][$id][$criticite]["triggers"][$trigger_name]["trigger_value"];
                            $trigger_value_result = round($trigger_values["trigger_result_value"],2);

                            if($nb_triggers>1){
                                $trap[7].= "|";} // On ajoute un | entre les triggers
                            $trap[7].= "$trigger_label $trigger_operand $trigger_value ($trigger_value_result)";

                            $nb_triggers++;
                        }
                    }
					// On ajoute les champs additionnels

                    // 18/01/2011 OJT : Correction bz 19693 (modification appel getNaValueLabel) et gestion 3ème axe
					if ( strlen( $a3 ) > 0 )
					{
						$lst_na = explode('_', $na);
						$trap[9] = " s \"".$this->lst_na_label[$lst_na[0]][$family]."|".$this->lst_na_label[$lst_na[1]][$family]."|";
					}
					else
					{
						$trap[9] = " s \"".$label_na."|"; // Informations complémentaires ( na + ta + champs additionels)
					}

					$trap[9].= " $ta";
					$additionals = $this->tab_alarm[$type][$id]["additional_fields"];// On récupère les tableaux des champs additionnels
					if($additionals!=NULL){
						foreach($additionals as $additional_field=>$field_value) {
							$trap[9].="|";
							$field_type = $field_value["field_type"];
							$field_name = $field_value["field_name"];
							// on remplace le nom par le label
							if($field_type=="kpi") {
								$field_label = $this->get_kpi_label($field_value["field_name"],$family);}
							else {
								$field_label = $this->get_raw_label($field_value["field_name"],$family);}
							$field_value = round($this->tab_alarm_result[$type][$id][$result]["additional_fields"][$field_name]["field_value"],2);
							$trap[9].="$field_label : $field_value";
						}
					}
					// Construction de la trap snmp
					$trap[3] = " s \"$label_type | $label_family\" "; //Alarm Group
					$trap[4] = " s \"$ta_value\" "; //Alarm Date
					$trap[5] = " i \"$critical\" "; //Alarm Level
					$trap[6] = " s \"$alarm_name\" "; // Alarm name
					$trap[7].= "\" ";

                    // 18/01/2011 OJT : Correction bz 19693 (modification appel getNaValueLabel) et gestion 3ème axe
					if ( strlen( $a3 ) > 0 )
					{
						$lst_na = explode('_', $na);
						$trap[8] = " s \"".$this->getNaValueLabel($na_value,$lst_na[0])."|".$this->getNaValueLabel($a3_value,$lst_na[1])."\" ";
					}
					else
					{
						$trap[8] = " s \"".$this->getNaValueLabel($na_value,$na)."\" "; // Na value
					}
					$trap[9].= " \" ";
					$trap[10] = "i \"1\""; //Alarm destination

				// On limite la longueur des paramètres de type chaîne de la trap snmp par rapport à un nombre max de caractères
				$trap[1] = $this->getMaxChaine($trap[1],50);
				$trap[2] = $this->getMaxChaine($trap[2],50);
				$trap[3] = $this->getMaxChaine($trap[3],50);
				$trap[4] = $this->getMaxChaine($trap[4],50);
				$trap[6] = $this->getMaxChaine($trap[6],50);
				$trap[7] = $this->getMaxChaine($trap[7],255);
				$trap[8] = $this->getMaxChaine($trap[8],50);
				$trap[9] = $this->getMaxChaine($trap[9],50);

				// on compte le nombre de paramètre de la trap snmp
				$nb_param_trap = count($trap);

				// on insère enterprise pour chaque paramètre de la trap snmp
					for($i=1;$i<=$nb_param_trap-1;$i++) {
						$this->trap[$nb_trap].="$this->entreprise.$i $trap[$i]";
					}
					$nb_trap++;
				}
			}
		}
		return $this->trap;
	}
    // fonction qui execute l'envoie d'une TRAP SNMP
    function send_SNMP_TRAP()
    {
        // 11/04/2013 SNMP Trap community global parameter
        // modification du message du file_demon
		echo '<b>'.count($this->trap).'</b> traps snmp vont être envoyées<br/>';
                $trapSendOk = $trapSendKo = 0;
		foreach($this->trap as $key=>$val){
			$cmd = '/usr/bin/snmptrap '.$val;
			__debug($cmd,"cmd $key");
                    // ajout de messages plus précis dans le file_demon
                    $output = array();
                    $ret = exec($cmd, $output, $return);
                    if($return==0)
                        $trapSendOk++;
                    else{
                        $trapSendKo++;
                        echo "Erreur d'envoi pour : ".'/usr/bin/snmptrap '.$val.'<br>';
                        echo "Retour : ".$return."<br>$ret<br>";
                        print_r($output);
                    }
			// Affichage des traps snmp pour tester
			if($this->debug){
				echo "$cmd<br/><br/>";
			}
                    unset($output);
		}
                // rapport de l'envoi dans le file_demon et le tracelog
                if($trapSendKo==0){
                    echo 'Toutes les trapes ont été envoyées avec succès<br/><br/>';
                    $message = __T('A_SNMP_SEND',$trapSendOk);
                    sys_log_ast("Info", get_sys_global_parameters("system_name"), __T("A_TRACELOG_MODULE_LABEL_ALARM"), $message, "support_1", "");
                }
                else{
                    echo '<b>'.$trapSendKo.' erreurs</b> sur '.count($this->trap).' traps envoyées<br/><br/>';
                    $message = __T('A_SNMP_SEND_ERROR',$trapSendKo,count($this->trap));
                    sys_log_ast("Critical", get_sys_global_parameters("system_name"), __T("A_TRACELOG_MODULE_LABEL_ALARM"), $message, "support_1", "");
                }

    }
} // fin class

?>
