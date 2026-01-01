<?
/**
* 
* Récupération des données en fonction du sélecteur dans application statistics
*
* @author MPR
* @version CB4.1.0.0
* @package Application Statistics
* @since CB4.1.0.0
*
*  maj 21/09/2011 - MMT 23834  ajout cast explicit pour PG9.1
*	maj 05/11/2008 MPR : On prepare le contenu du fichier xml (necessaire pour la creatino du graphe)  - Création du fichier xml - Génération du graphe
*	maj 05/11/2008 : MPR - Construction de l'axe des abscisses
*	maj 05/11/2008 : MPR - Appel à la classe DataBaseConnection
* 	maj 05/11/2008 - MPR : Réécriture des calculs de date_debut et date_fin définissant sur quel intervalle temporel on doit récupérer les données (utilisés dans la requete sql) 
* 	maj 04/11/2008 - MPR : Réécriture de la récupération de la date de fin ( paramètre du sélecteur qui change)
*	maj 04/11/2008 - MPR : Les graphes sont maintenant générés à partir de fichiers xml
*	maj 03/11/2008 - MPR : Génération du graphe à partir du fichier xml créé précédemment
*
*	12/08/2009 GHX
*		- Modification de la requete SQL qui récupère les résultats sinon résultats incorrects
*		- Correction du BZ 6652
*	09/11/2009 GHX
*		- Correction du BZ 12633 [REC][T&A Roaming] Erreur openoffice au login, Homepage quasi vide
*	23/02/2010 NSE
*		- remplacement de la fonction GetLastDayFromAcurioWeek par leur équivalent de la classe Date
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
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?php

/*

	- maj 03/11/2006, benoit : exclusion de l'utilisateur 'astellia_admin' des statistiques si l'utilisateur administrateur est différent de celui-ci

*/

$go_display = true;

// si on affiches les statistics sur la page d'accueil, on se positionne dans un mode hour en affichant les 24 dernières heures
if ($homepage_display and !isset($selecteur_general_values['user']) ) {

    $date_fin = date("YmdH");
    $statistics_time_agregation = "hour";
    $statistics_hour = substr($date_fin, 8, 2);
    $statistics_day = substr($date_fin, 6, 2);
    $statistics_month = substr($date_fin, 4, 2);
    $statistics_year = substr($date_fin, 0, 4);
    $date_fin_timestamp = mktime($statistics_hour, 0, 0, $statistics_month, $statistics_day, $statistics_year);

    $statistics_period = "24";
    $statistics_id_user = "all";
} else {

    // au premier chargement, $selecteur_general_values vaut NULL et cela fait planter la page car les données renvoyées au graphe sont inexistantes
    if ($selecteur_general_values != null) {
        $transparence_color = 1;
        // METTRE LES INFOS DU SELECTEUR
		// maj 04/11/2008 - maxime : On fait appel au nouveau sélecteur ( Traitement de ta_level modifié ) 
        $statistics_id_user = $selecteur_general_values["user"];
		
        if ($statistics_id_user == null) { // gere l'exception lorsque le selecteur est affcihé, on a pas de user par défaut
            $statistics_id_user = 'all';
        }
        $statistics_period = $selecteur_general_values["period"];
		$date_fin = $selecteur_general_values["date"];
       

		/*
		maj 04/11/2008 - MPR : Réécriture de la récupération de la date de fin ( paramètre du sélecteur qui change)
		
		Gestion de ta_level			
		
		$selecteur_values['date'] = 'd/m/Y h:00' 	// pour hour
		$selecteur_values['date'] = 'd/m/Y' 	 	// pour day
		$selecteur_values['date'] = 'W23/2008'    // pour week
		$selecteur_values['date'] = '11/2008'   	// pour month 
		*/
		
	    switch( $selecteur_general_values["ta_level"] ){
			case 'hour' : 
				
				$hour = substr($selecteur_general_values["hour"], 0, 2);
				$date_fin = $selecteur_general_values["date"].$hour;
	            $statistics_time_agregation = "hour";
				$statistics_day = substr($date_fin, 0, 2);
				$statistics_month = substr($date_fin, 3, 2);
				$statistics_year = substr($date_fin, 6, 4);
				$statistics_hour = substr($date_fin, 10, 2);
				$date_fin = $statistics_year.$statistics_month.$statistics_day.$statistics_hour;
				$date_fin_timestamp = mktime($statistics_hour, 0, 0, $statistics_month, $statistics_day, $statistics_year);
		
			break;
						
			case 'day' : 
				
				$statistics_time_agregation = "day";
				$statistics_hour = substr($date_fin, 10, 2);
				$statistics_day = substr($date_fin, 0, 2);
				$statistics_month = substr($date_fin, 3, 2);
				$statistics_year = substr($date_fin, 6, 4);
				$date_fin = $statistics_year.$statistics_month.$statistics_day;
				$date_fin_timestamp = mktime($statistics_hour, 0, 0, $statistics_month, $statistics_day, $statistics_year);
		
			break;
			
			case 'week' : 
				
				$date_fin = substr($date_fin,4,4).substr($date_fin,1,2);
				$statistics_time_agregation =  'week';
				//Recupère le dernier jour de la semaine selectionnée
				// 23/02/2010 NSE : remplacement GetLastDayFromAcurioWeek($week) par Date::getLastDayFromWeek($week,$firstDayOfWeek=1)
				$lastday_week = Date::getLastDayFromWeek($date_fin,get_sys_global_parameters('week_starts_on_monday',1));
				$statistics_hour = substr($lastday_week, 8, 2);
				$statistics_day = substr($lastday_week, 6, 2);
				$statistics_month = substr($lastday_week, 4, 2);
				$statistics_year = substr($lastday_week, 0, 4);	
			
			break;
			
			case 'month' :
			
				$statistics_time_agregation =  'month';  
				$statistics_month = substr($date_fin, 0, 2);
				$statistics_year = substr($date_fin, 3, 4);
				$$statistics_day = substr($lastday_week, 6, 2);
				$statistics_hour = substr($lastday_week, 8, 2);
				$date_fin = $statistics_year.$statistics_month; 
				$date_fin_timestamp = mktime($statistics_hour, 0, 0, $statistics_month, $statistics_day, $statistics_year);
		
			break;
	    }
		
		unset($selecteur_general_values);
		
    } else {
        $go_display = false;
    }
}
if ($go_display) {
    // determination du nombre de connection par time agregation
	
	// maj 05/11/2008 - MPR : Réécriture des calculs de date_debut et date_fin définissant sur quel intervalle temporel on doit récupérer les données (utilisés dans la requete sql)	$timestamp_month = date("m", $date_fin_timestamp);
$timestamp_day   	= date("d", $date_fin_timestamp);
$timestamp_hour   	= date("H", $date_fin_timestamp);
$timestamp_month   	= date("m", $date_fin_timestamp);
$timestamp_year  	= date("Y", $date_fin_timestamp);	
	
    switch ($statistics_time_agregation) {
		
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
		
        case "hour":
					
			$query_part = "extract(year from start_connection)::text||CASE WHEN extract(month from start_connection)<10 THEN '0'||extract(month from start_connection)::text ELSE extract(month from start_connection)::text END ||CASE WHEN extract(day from start_connection)<10 THEN '0'||extract(day from start_connection)::text ELSE extract(day from start_connection)::text END||CASE WHEN extract(hour from start_connection)<10 THEN '0'||extract(hour from start_connection)::text ELSE extract(hour from start_connection)::text END";
			
            $date_debut = date("YmdH", mktime($timestamp_hour - $statistics_period, 0, 0, $timestamp_month, $timestamp_day, $timestamp_year));
			
			// maj MPR On définit l'axe des axis
            for ($i = 0;$i < $statistics_period;$i++)
			{
				$label_x[$i] = date("YmdH",  mktime($timestamp_hour - $statistics_period + $i + 1, 0, 0, $timestamp_month, $timestamp_day, $timestamp_year));
            }
				
            
        break;
		
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
		
		case "day":
		
			// Construction de la requête SQL 
            $query_part = "extract(year from start_connection)::text||CASE WHEN extract(month from start_connection)<10 THEN '0'||extract(month from start_connection)::text ELSE extract(month from start_connection)::text END ||CASE WHEN extract(day from start_connection)<10 THEN '0'||extract(day from start_connection)::text ELSE extract(day from start_connection)::text END";
            
			$date_debut = date("Ymd", mktime(0, 0, 0, $timestamp_month, $timestamp_day - $statistics_period, $timestamp_year));
			
            for ($i = 0;$i < $statistics_period;$i++) 
			{
                $label_x[$i] = date("Ymd", mktime(0, 0, 0, $timestamp_month, $timestamp_day - $statistics_period + $i + 1, $timestamp_year));
            }
        break;
		
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
        
        case "week":
		
            $query_part = "extract(year from start_connection)::text||CASE WHEN extract(week from start_connection)<10 THEN '0'||extract(week from start_connection)::text ELSE extract(week from start_connection)::text END";
          
            $date_fin_timestamp = mktime($statistics_hour, 0, 0, $statistics_month, $statistics_day, $statistics_year);
            $timestamp_day   = date("d", $date_fin_timestamp);
            $timestamp_month  = date("m", $date_fin_timestamp);
			$timestamp_year  = date("Y", $date_fin_timestamp);
			
			// la date W retourne une week sans le 0 si la week est <10.
            // il faut ajouter le 0 pour que la requete Postgresql retourne un resultat correct
            if (date("W", mktime(0, 0, 0, $timestamp_month, $timestamp_day-7 * $statistics_period, $timestamp_year)) < 10) {
				// 11/03/2013 GFS - BZ#31364 - [SUP][TA HPG][AVP 32586][Telus][Partionning]: All weekly history is delete on last week of the year compute
                $date_debut = date("o0W", mktime(0, 0, 0, $timestamp_month, $timestamp_day-7 * $statistics_period, $timestamp_year));
				
            } else {
				// 11/03/2013 GFS - BZ#31364 - [SUP][TA HPG][AVP 32586][Telus][Partionning]: All weekly history is delete on last week of the year compute
                $date_debut = date("oW", mktime(0, 0, 0, $timestamp_month, $timestamp_day-7 * $statistics_period, $timestamp_year));
				
            }

            for ($i = 0;$i < $statistics_period;$i++) {
                // on multiplie par 7 le nombre de jours pour se recaler sur un calcul week
                if (date("W", mktime(0, 0, 0, $timestamp_month, $timestamp_day + (- $statistics_period + $i + 1) * 7, $timestamp_year)) < 10) {
					// 11/03/2013 GFS - BZ#31364 - [SUP][TA HPG][AVP 32586][Telus][Partionning]: All weekly history is delete on last week of the year compute
                    $label_x[$i] = date("o0W", mktime(0, 0, 0, $timestamp_month, $timestamp_day + (- $statistics_period + $i + 1) * 7, $timestamp_year));
					
                } else {
					// 11/03/2013 GFS - BZ#31364 - [SUP][TA HPG][AVP 32586][Telus][Partionning]: All weekly history is delete on last week of the year compute
                    $label_x[$i] = date("oW", mktime(0, 0, 0, $timestamp_month, $timestamp_day + (- $statistics_period + $i + 1) * 7, $timestamp_year));
					
                }
            }
        break;
			
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
        
        case "month":
		
            $query_part = "extract(year from start_connection)::text||CASE WHEN extract(month from start_connection)<10 THEN '0'||extract(month from start_connection)::text ELSE extract(month from start_connection)::text END";
            // on force le mois à +1 car la donnée du selecteur ne contient pas le jour
			
            $date_debut = date("Ym", mktime(0, 0, 0, $timestamp_month - $statistics_period + 1, 1, $timestamp_year));
			
            for ($i = 0;$i < $statistics_period;$i++) {
                // on force le day à 1 et le mois  à +1 pour être dans le mois courant.
                $label_x[$i] = date("Ym", mktime(0, 0, 0, $timestamp_month - $statistics_period + $i + 1 + 1, 1, $timestamp_year));
				
            }
            break;
    } // switch
		
		//-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//

    if ($statistics_id_user != '0' and $statistics_id_user != "all" ) {
        $query_part_id_user = " AND t0.id_user='$statistics_id_user'  ";
	}

		
	// 03/11/2006 - Modif. benoit : on exclut l'utilisateur 'astellia_admin' des statistiques si l'utilisateur administrateur connecté n'est pas celui-ci
	// 14:53 12/08/2009 GHX
	// BZ 6652
	(getClientType($_SESSION['id_user']) == 'client') ? $exclude_admin = "AND users.visible = 1" : $exclude_admin = "";

	// 02/06/2009 BBX : correction de la requête
	// 11:00 12/08/2009 GHX
	// Modification de la requete pour ajouter la jointure entre les tables sinon résultats incorrect
	//21/09/2011 MMT 23834  ajout cast explicit pour PG9.1
    $query = "SELECT $query_part AS debut, count(t0.id_user) AS total 
	FROM track_users t0, users 
	WHERE ($query_part)<=$date_fin::text
	AND ($query_part)>=$date_debut::text
	AND users.id_user = t0.id_user
	$query_part_id_user 
	$exclude_admin 
	GROUP BY debut 
	ORDER BY debut DESC";

	// maj 05/11/2008 : MPR - Appel à la classe DataBaseConnection
    // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
    $database_connection = Database::getConnection();
    
    $res = $database_connection->getAll( $query);
    $nombre_resultat = count($res);

    foreach( $res as $row) 
    {
        $array_result[$row["debut"]] = $row["total"];
		
    }

	// maj 05/11/2008 : MPR - Construction de l'axe des abscisses
    foreach ($label_x as $key => $time_value) 
	{
        $array_display[] = $array_result[$time_value];
		
        switch ($statistics_time_agregation) {
			//------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
            case "hour":
                if (substr($value_old, 0, 8) != substr($label_x[$key], 0, 8) && isset($value_old)) {
				
                    $value_old = $label_x[$key];
                    $label_x[$key] = substr($label_x[$key], 6, 2) . "-" . substr($label_x[$key], 4, 2) . "-" . substr($label_x[$key], 0, 4) . "\n         " . substr($label_x[$key], 8, 2) . ":00";
					
                } else {
				
                    $value_old = $label_x[$key];
                    $label_x[$key] = substr($label_x[$key], 8, 2) . ":00"; //n'affiche que les heures tant que le jour de la valeur précédente est la même que pour la valeur courante
                }
                break;
			//------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
            case "day":
			
                $label_x[$key] = substr($label_x[$key], 6, 2) . "-" . substr($label_x[$key], 4, 2) . "-" . substr($label_x[$key], 0, 4);
				
                break;
			//------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
            case "week":
			
                $label_x[$key] = "W" . substr($label_x[$key], 4, 2) . "-" . substr($label_x[$key], 0, 4);
				
                break;
			//------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
            case "month":
			
                $label_x[$key] = substr($label_x[$key], 4, 2) . "-" . substr($label_x[$key], 0, 4);
				
                break;
			//------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------//
        } // switch
		
		// maj 05/11/2008 : On prepare le contenu du fichier xml (necessaire pour la creatino du graphe)
		$axis_label[] = '<label ta_level="'.$key.'" color="#0000FF">'.$label_x[$key].'</label>';
		$data_value = 0;
		if($array_result[$time_value] !== '' and $array_result[$time_value] !== null)
			$data_value = $array_result[$time_value];
		$data_values[] = '<value data_values="'.$key.'_data">'.$data_value.'</value>';
    }
	
    if ($array_display != null) {
	
		// maj 05/11/2008 - maxime : Génération du graphe
		// Génération du fichier xml
		$file = '<?xml version="1.0" encoding="UTF-8" ?><chart>';
		$file.= ' 
						<properties>
							
							<tabtitle>
								<text>'.__T("A_APPLICATION_STATS_LABEL_NB_CONNECTIONS").'</text>
							</tabtitle>
							<margin_top>30</margin_top>
						    <margin_left>0</margin_left>
						    <margin_bottom>0</margin_bottom>
						    <margin_color>#ffffff</margin_color>
						    <margin_right>30</margin_right>
							<legend_position>top</legend_position>
							<width>600</width>
							<height>250</height>
							<left_axis_label></left_axis_label>
						</properties>
					';
		// On définit l'axe des abscisses
		$file.= '<xaxis_labels interval="1">'.implode("",$axis_label).'</xaxis_labels>';
	
		// On définit les valeurs
		$file.= '<datas><data label="Nb Connections" type="bar" stroke_color="blue" fill_color="blue@0.7" yaxis="left" interval="5">'.implode("",$data_values).'</data></datas>';
		
		$file.= '</chart>';
		
		// A commenté en mode debug
		// $file = preg_replace('/\s\s+/', ' ', $file);
		
		// 03/06/2009 BBX
		// Création physique du fichier xml
		$file_xml = $repertoire_physique_niveau0."png_file/appli_stats_".uniqid("").".xml";
		// 15:27 09/11/2009 GHX
		// Correction du BZ 12633
		// Ajout de la condition if/else pour vérifier que le fichier XML a bien été créé
		if ( @file_put_contents($file_xml,$file) )
		{
			//   maj 05/11/08 - MPR : Génération du graphe à partir du fichier xml créé précédemment
			$myGraph = new chartFromXML($file_xml);
			
			$myGraph->loadDefaultXML(MOD_CHARTFROMXML."class/chart_default.xml");
			$myGraph->setBaseDir(REP_PHYSIQUE_NIVEAU_0.'png_file/');

			// on définit l'url des images sauvées
			$myGraph->setBaseUrl(NIVEAU_0.'png_file/');

			$graphe = $myGraph->getHTML();
		}
		else
		{
			// 15:27 09/11/2009 GHX
			// Correction du BZ 12633
			// Si on n'a pas pu créer le fichier XML, on affiche un message pour dire qu'il est impossible de créer le graphe
			echo "<div class='errorMsg'>".__T('A_E_UNABLE_CREATE_GRAPH')."</div>";
		}
    }
}

?>
