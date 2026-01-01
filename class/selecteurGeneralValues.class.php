<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*
*	- 10/04/2007 christophe : gestion du 3ème > ajout de la proprièté na_box.
*
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
*	- 24 01 2007 christophe : ajout de la gestion du nouveau filtre sur les raw / kpi
*
*/
?>
<?php
/**
	Classe selecteurGeneralValues
	Cette classe permet de décomposer la variable sélecteur général values.
	En entrée, on a le tableau selecteur_general_values du sélecteur.
	En sortie on a
		this->ta		time aggregation.
		this->ta_value	valeur de la time aggregation.
		this->na		network aggregation.
		this->hn		home network.
		this->hn_value	home network value.
*/

class selecteurGeneralValues {
    var $database_connection;
    var $ta;
    var $ta_value;
    var $hn_value;
    var $period;
    var $kpi_raw_value;
    var $kpi_raw_type;
    var $kpi_raw_order;
    var $kpi_raw_id;
    var $kpi_raw_label;
    var $kpi_raw_operand;
    var $kpi_raw_trigger;
	
	// Na_box / 3ème axe.
	var $na_box;		// nom de la NA de la na_box.
	var $na_box_value; 	// valeur de la NA de a na_box.

	// Filtre sur les raw / kpi.
	var $filter_kpi_raw_name;		// nom du raw / kpi du filtre
	var $filter_kpi_raw_label;		// label du raw / kpi du filtre
	var $filter_kpi_raw_type;		// type : raw ou kpi
	var $filter_kpi_raw_id;			// identifiant du raw / kpi du filtre
	var $filter_kpi_raw_operand;	// opérande du filtre
	var $filter_kpi_raw_value;		// valeur du filtre

    function selecteurGeneralValues($database_connection, $selecteur_general_values, $family)
    {
        $this->database_connection = $database_connection;
        $this->selecteur_general_values = $selecteur_general_values;
        $this->family = $family;

        $this->getAllValues();	// Récupération des valeurs.
    }

    /**
		Permet de récupérer toutes les valeurs contenues dans le sélecteur.
	*/
    function getAllValues()
    {
        /*
			On récupère la TA/TA_VALUE et NA.
		*/
        // on récupère la liste des NA et des TA et on vérifie si elles sont présentes dans les paramètres venant du sélecteur.
        $query_liste_ta = "
				SELECT agregation,  agregation_label FROM sys_definition_time_agregation
					WHERE on_off=1 AND visible=1
			";
        $result = pg_query($this->database_connection, $query_liste_ta);
        $nombre_resultat = pg_num_rows($result);
        if ($nombre_resultat > 0) {
            for ($i = 0;$i < $nombre_resultat;$i++) {
                $row = pg_fetch_array($result, $i);
                $ta_array[$row["agregation"]] = $row["agregation"];
            }
        }

        $query_liste_na = "
				SELECT agregation, agregation_label FROM sys_definition_network_agregation
			";
        $result = pg_query($this->database_connection, $query_liste_na);
        $nombre_resultat = pg_num_rows($result);
        if ($nombre_resultat > 0) {
            for ($i = 0;$i < $nombre_resultat;$i++) {
                $row = pg_fetch_array($result, $i);
                $na_array[$row["agregation"]] = $row["agregation"];
            }
        }
        // On parcours le tableau de TA et des NA et on récupère les paramètres du sélecteur.
        foreach ($ta_array as $ta) {
            if (isset($this->selecteur_general_values[$ta])) {
                $this->ta = $ta;
                $this->ta_value = $this->selecteur_general_values[$ta];
            }
        }
        foreach ($na_array as $na) {
            if (isset($this->selecteur_general_values[$na])) $this->na = $na;
        }

        /*
			On gère la na_box
		*/
        if ( isset($this->selecteur_general_values["na_box"]) ) 
		{
            // Le contenu est de la forme tosgroup@tosgroup1.
			$tmp = explode('@',$this->selecteur_general_values["na_box"]);
			$this->na_box = 		$tmp[0];
			$this->na_box_value = 	$tmp[1];
        }

        /*
				On récupère la période
			*/
        if (isset($this->selecteur_general_values["period"])) {
            $this->period = $this->selecteur_general_values["period"];
        }

        /*
			* On récupère les informations associées au tri via un compteur ou un KPI
			*/
        if (isset($this->selecteur_general_values["kpi_list"])) {
			/*
				Modification christophe le 24 01 2007
				les données concernant le filtre sont stockées dans la chaine 'kpi_list' après le
				séparateur '|||'
			*/
			/*
			echo '<pre>';
			print_r($this->selecteur_general_values);
			echo '</pre>';
			//*/
			$tmp = explode('|||', $this->selecteur_general_values["kpi_list"]);

            $array_info_kpi_raw = explode("@", $tmp[0]);
            $this->kpi_raw_value = $array_info_kpi_raw[0];
            $this->kpi_raw_type = $array_info_kpi_raw[1];
            $this->kpi_raw_order = $array_info_kpi_raw[2];
            $this->kpi_raw_id = $array_info_kpi_raw[3];
            $this->kpi_raw_label = $array_info_kpi_raw[4];
			$this->filter_kpi_raw_name = '';
			// Contenu du filtre (si définit)
			if ( isset($tmp[1]) )
			{
				//echo '<br>Filtre > '.$tmp[1].'<br>';
				$tmp = explode('@@@@',$tmp[1]);
				$tmp1 = explode('@',$tmp[1]);
				$tmp = explode('@',$tmp[0]);

				$this->filter_kpi_raw_name =	$tmp[0];
				$this->filter_kpi_raw_label = 	$tmp[4];
				$this->filter_kpi_raw_type = 	$tmp[1];
				$this->filter_kpi_raw_id = 		$tmp[3];
				$this->filter_kpi_raw_operand = $tmp1[0];
				$this->filter_kpi_raw_value =	$tmp1[1];
			}

            //$this->kpi_raw_operand = $array_info_kpi_raw[5];
            //$this->kpi_raw_trigger = $array_info_kpi_raw[6];
        }



    }
} // fin class

?>
