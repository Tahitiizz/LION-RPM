<?php

/*
 * 	@cb51@
 *
 * 	09/12/2011 ACS Mantis 837 DE HTTPS support
 *
 */
/*
 * 	@cb41000@
 *
 * 	03/12/2008 - Copyright Astellia
 *
 * 	Composant de base version cb_4.1.0.00
 *
 * 	- maj 03/12/2008 - SLC - gestion multi-produit, suppression de $database_connection
 * 	- maj 12/03/2010 - MPR : Correction du BZ14709 - Les alarmes envoyées par pdf ne contiennent pas les informations 3ème axe
 *       - maj 13/09/2010 - MPR : Correction du bz16214 - Ajout du paramètre d'entrée "true" permettant de récupérer l'IP publique
 *
 */
?><?php

/*
 * 	@cb40000@
 *
 * 	14/11/2007 - Copyright Acurio
 *
 * 	Composant de base version cb_4.0.0.00
 *
  - maj 30/11/2007, maxime : mise à jour de l'affichage des additional field  par rapport au nouveau mode de calcul.

  - maj 16/04/2008, benoit : correction du bug 6238

  - maj 06/06/2008 - maxime : On ajoute le nom de la base dans l'url
 */
?>
<?php

/*
 * 	@cb21201@
 *
 * 	14/03/2007 - Copyright Acurio
 *
 * 	Composant de base version cb_2.1.2.01
 */
?>
<?php

/*
 * 	@cb2000b_iu2000b@
 *
 * 	20/07/2006 - Copyright Acurio
 *
 * 	Composant de base version cb_2.0.0.0
 *
 * 	Parser version iu_2.0.0.0
 */
?>
<?php

/*
  11/10/2006 xavier : résolution du bug du na_label différent selon la famille
  affichage de données cohérentes pour les alarmes dynamiques

  - maj 10/04/2007 Gwénaël
  modification apporté par rapport au troisième axe  dans la colonne network agrégation, affiche du 3° axe s'il existe
  suppression de la colonne "Critical Level", (les colonnes "Alarm name" et "Trigger - Threshold" ont été agrandies cf. fichier class/htmlTablePDF.class.php)
  - maj 13/04/2007 Gwénaël
  changement de séparateur |@| par |s|
 */

class alarmCreateHTML {

    /**
     * 01/03/2011 MMT bz 19128: ajout colone sévérité pour exports Excel + un seul tableau pour toute les severités
     *
     *  $queries : un tableau contenant un ou plusieurs tableaux de requêtes
     *               en provenance de la classe alarmdisplaycreate.
     *             Dans le cas d'une alarme top/worst cell list, $critical_level
     *               doit prendre la valeur 'twcl'.
     *  $queries[$critical_level][$query_select] = "SELECT *";
     *  $queries[$critical_level][$query_from] = "FROM *";
     *  $queries[$critical_level][$query_where] = "WHERE *";
     *  $queries[$critical_level][$query_order_by] = "ORDER BY *";
     *
     */
    function alarmCreateHTML($mode, $sous_mode, $support_type, $queries, $isMail, $productId = '') {
        global $path_skin, $repertoire_physique_niveau0, $niveau0;

        $this->debug = get_sys_debug('alarm_export_pdf'); // mode débugage

        if ($this->debug)
            echo "<div class='debug'><div class='function_call'>new alarmCreateHTML(
			<div style='margin-left:20px'>
				mode=<strong>$mode</strong>,<br/>
				sous_mode=<strong>$sous_mode</strong>,<br/>
				support_type=<strong>$support_type</strong>,<br/>
				queries=<strong>" . print_r($queries, 1) . "</strong>,<br/>
				isMail=<strong>$isMail</strong>,<br/>
				product=<strong>$product</strong>
			</div>	
		);</div>";

        // définit s'il s'agit d'un alarm management, alarm history ou tw lists
        $this->mode = $mode;

        // définit si les informations sont affichés en fonction des éléments réseaux ou des alarmes
        $this->sous_mode = $sous_mode;

        $this->nb_results = 0; // nombre d'alarmes ayant sautées
        // prend les valeurs 'excel' ou 'pdf'
        // utilisé pour limiter la taille d'un onglet excel
        $this->support_type = $support_type;

        // est ajouté devant le bookmark d'une static ou dynamic alarm
        // ignore les critical_level qui n'ont pas de résultats si $isMail n'est pas vide
        $this->isMail = $isMail;

        // 03/12/2008 - SLC - gestion multi-produit
        // 09/12/2011 ACS Mantis 837 DE HTTPS support
        $this->productId = $productId;
        $this->productModel = new ProductModel($this->productId);
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
        $this->db = Database::getConnection($this->productId);

        // 11/10/2006 xavier
        $this->naLabelList = getNaLabelList($this->productId);

        //on récupère les requêtes pour tous les niveaux de criticité
        $critical_levels = array_keys($queries);

        //01/03/2011 MMT bz 19128: si excel, un seul tableau, sinon un tableau par sévéritée
        if ($this->support_type != 'excel') {
            $this->saveSeverityTables($critical_levels, $queries);
        } else {
            $this->saveExcelTable($critical_levels, $queries);
        }

        if ($this->debug)
            echo "</div>";
    }

    /**
     * 01/03/2011 MMT bz 19128
     * Save the three severity alarm HTML tables in the buffer table
     * @param array $critical_levels list of critical level names
     * @param array $queries array of queries per critical levels
     */
    private function saveSeverityTables($critical_levels, $queries) {

        foreach ($critical_levels as $critical_level) {

            // on construit le tableau HTML à partir de la requête passée en paramètre dans le tableau $queries
            $this->nb_results = 0;
            $content = $this->generateSeverityTableContent($critical_level, $queries);
            $html = $this->encapsulateContentInTable($content);
            $this->saveHtmlToDB($html, $this->generateTableTitle($critical_level));
        }
    }

    /**
     * 01/03/2011 MMT bz 19128
     * Save the one alarm HTML table with all severities in the buffer table
     * @param array $critical_levels list of critical level names
     * @param array $queries array of queries per critical levels
     */
    private function saveExcelTable($critical_levels, $queries) {

        $content = '';
        foreach ($critical_levels as $critical_level) {
            // on construit le tableau HTML à partir de la requête passée en paramètre dans le tableau $queries
            $content .= $this->generateSeverityTableContent($critical_level, $queries);
        }
        $html = $this->encapsulateContentInTable($content);
        $this->saveHtmlToDB($html);
    }

    /**
     * 01/03/2011 MMT bz 19128
     * assign the severity color to $this->color depending on the critical level
     * @param array $critical_levels list of critical level names
     */
    private function assignSeverityColor($critical_level) {
        // utilisé dans le mode debug pour afficher des tableaux de couleur
        if ($critical_level == 'critical')
            $this->color = "#FF0000";
        if ($critical_level == 'major')
            $this->color = "#FF00FF";
        if ($critical_level == 'minor')
            $this->color = "#0000FF";
        if ($critical_level == 'twcl')
            $this->color = "#F0F0F0";
    }

    /**
     * 01/03/2011 MMT bz 19128
     * generate table title for word or pdf format
     * @param array $critical_levels list of critical level names
     * @return string title
     */
    private function generateTableTitle($critical_level) {
        //  le titre dépend :
        //      - du mode utilisé ('twcl' ou autre)
        //      - du type de support ('pdf' ou 'excel') pour l'export
        //        (taille d'un onglet excel < 31 caractères)
        //      - du paramètre $isMail s'il ne s'agit pas d'une alarme top/worst
        if ($this->mode == 'twcl') {
            if ($this->support_type == 'pdf')
                $title = 'Top / Worst Cell List on ' . $this->na_label . ', current family : ' . $this->family_label . '  (' . $this->nb_results . '  results)';
            if ($this->support_type == 'excel')
                $title = 'Top - Worst (' . $this->nb_results . '  results)';
            if ($this->isMail)
                $title = $this->isMail . '    (' . $this->nb_results . '  results)';
        }
        else
        if ($this->isMail)
            $title = $this->isMail . '    ' . ucfirst($critical_level) . '  (' . $this->nb_results . '  results)';
        else
            $title = ucfirst($critical_level) . '  (' . $this->nb_results . '  results)';

        return $title;
    }

    /**
     * 01/03/2011 MMT bz 19128
     * save the html table to the sys_contenu_buffer where it will be retrieved
     * @param string $html html of the table
     * @param string $title optional title of the table
     */
    private function saveHtmlToDB($html, $title = '') {
        // le tableau HTML n'est pas inséré dans la table s'il s'agit d'un envoi de PDF
        // par mail et qu'il n'y aucun résultat.
        if ((!$this->isMail) or ( $this->nb_results > 0)) {
            global $id_user;
            if (!$id_user)
                $id_user = '-1';

            // écriture des données du tableau HTML dans la table sys_contenu_buffer
            $query = "INSERT INTO sys_contenu_buffer
					(id_user,object_type,object_source,object_title,object_id,id_page)
					VALUES ('$id_user','alarm_export','" . addslashes($html) . "','" . addslashes($title) . "',0,'$this->nb_results')";
            $this->db->execute($query);
        }
    }

    /**
     *
     * 	- maj 11/04/2007 Gwénaël
     * 			suppression de la colonne "Critical Level"
     * 			prise en compte du troisième axe
     *
     * 01/03/2011 MMT bz 19128 renomage de la methode pour support xls
     *
     * @param array $critical_levels list of critical level names
     * @param array $queries array of queries per critical levels
     * @return String html table content (not including the <table> tags)
     */
    private function generateSeverityTableContent($critical_level, $queries) {

        if ($this->debug) {
            echo "<div><u><b>$critical_level</b></u></div>";
        }
        $this->assignSeverityColor($critical_level);

        $query = $this->generateTableQuery($queries[$critical_level]);
        $result = @$this->db->getall($query);

        // for excel format do not generate empty severity table as all severities are in one table
        if (!$result && $this->support_type != 'excel') {
            $ret = $this->generateEmptyTableContent();
        }
        $ret .= $this->generateResultRows($result);

        if ($this->debug)
            echo $ret;

        if ($this->debug)
            echo "</div>";

        return $ret;
    }

    /**
     * 01/03/2011 MMT bz 19128
     * create an html table from the given html content
     * @param String $content
     * @return String table
     */
    private function encapsulateContentInTable($content) {
        $ret = "<table cellpadding=2 cellspacing=1 style='border:1px solid red'>\n";
        $ret .= $this->generateTableHeader();
        $ret .= $content;
        $ret .= "</table>";

        return str_replace(array("\n", "'"), array("", "&#39;"), $ret);
    }

    /**
     * 01/03/2011 MMT bz 19128
     * Get the SQL query for one table content (one severity) generation
     * @param array $queries array of queries for a specific critical level
     * @return SQL query
     */
    private function generateTableQuery($queries) {
        $query_select = rtrim($queries['query_select']);
        $query_from = rtrim($queries['query_from']);
        $query_where = rtrim($queries['query_where']);
        $query_order_by = rtrim($queries['query_order_by']);

        if ($this->debug) {
            echo "<div class='debug'><div class='function_call'>generateTableQuery(
				<div style='margin-left:20px;'>
					query_select=<strong>$query_select</strong>,<br/>
					query_from=<strong>$query_from</strong>,<br/>
					query_where=<strong>$query_where</strong>,<br/>
					query_order_by=<strong>$query_order_by</strong></div>
				)</div>";
        }

        //global $repertoire_physique_niveau0;

        set_time_limit(3600); // limite de temps de calcul
        // si l'utilisateur vient de la page Top/Worst Lists, on récupère la famille
        if ($this->mode == 'twcl')
            $query_select .= ", (SELECT distinct family FROM sys_definition_alarm_top_worst WHERE id_alarm=alarm_id) as family";

        /*         * ************************ début de la requête ************************* */

        // 18/08/2011 BBX
        // Il faut faire un LEFT JOIN car tous les résultats d'alarme n'ont pas forcément de détail associé
        // BZ 19767
        $query_from = str_replace("edw_alarm t2", "edw_alarm t2  LEFT JOIN edw_alarm_detail ON t2.id_result = edw_alarm_detail.id_result", $query_from);

        $query = $query_select . ",
		CASE WHEN t2.alarm_type='dyn_alarm'
		THEN
			(SELECT DISTINCT family_label FROM sys_definition_categorie WHERE family in (SELECT family FROM sys_definition_alarm_dynamic WHERE alarm_id=t2.id_alarm LIMIT 1))
		ELSE
			CASE WHEN t2.alarm_type='static' THEN
				(SELECT DISTINCT family_label FROM sys_definition_categorie WHERE family in (SELECT family FROM sys_definition_alarm_static WHERE alarm_id=t2.id_alarm LIMIT 1))
			ELSE
				(SELECT DISTINCT family_label FROM sys_definition_categorie WHERE family in (SELECT family FROM sys_definition_alarm_top_worst WHERE alarm_id=t2.id_alarm LIMIT 1))
			END
		END as family_label,
		(SELECT agregation_label FROM sys_definition_time_agregation WHERE agregation=ta) as ta_label,
		trigger,
		trigger_operand,
		trigger_value,
		value,
		field_type,
		additional_details

		$query_from
		$query_where
		$query_order_by,
		t2.id_result,
		field_type desc,
		additional_details;";

        /*         * ************************* fin de la requête ************************** */

        // en mode débugage, on affiche la requête mise en forme
        if ($this->debug) {
            // 18/08/2011 BBX
            // Correction du débug qui cassait la requête
            // BZ 19767
            echo "<pre style='text-align:left;color:" . $this->color . "'>" . $query . "</pre>";
        }

        return $query;
    }

    /**
     * 01/03/2011 MMT bz 19128
     * generate empty table for case with no results
     * @return String html empty table
     */
    private function generateEmptyTableContent() {

        // si la requête ne renvoit aucune valeur,
        // on n'affichera un message d'erreur.
        // on renvoit les informations pour la construction du PDF
        $ret = "<tr>";
        $ret .= "<td>";
        $ret .= " >> ";
        $ret .= "</td>";
        $ret .= "<td>";
        $ret .= "No result";
        $ret .= "</td>";
        $ret .= "</tr>";
        return str_replace(array("\n", "'"), array("", "&#39;"), $ret);
    }

    /**
     * 01/03/2011 MMT bz 19128
     * generate table header line
     * @return String html table tr
     */
    private function generateTableHeader() {
        // on construit l'entête du tableau
        // l'entête change selon le sous mode utilisé (par élément réseau ou par alarme)
        // modif 11/05/2007 Gwénaël
        // suppression de la colonne "Critical Level"

        $ret = "<tr>\n";
        if ($this->sous_mode == 'elem_reseau')
            $ret .= "<td class='entete'>Network aggregation</td>\n";
        $ret .= "<td class='entete'>Alarm name</td>\n";
        $ret .= "<td class='entete'><b>Source Time</b></td>\n";
        if ($this->sous_mode == 'condense')
            $ret .= "<td class='entete'>Network aggregation</td>\n";
        //01/03/2011 MMT bz 19128 ajoute colonne severité pour excel
        if ($this->support_type == 'excel' && $this->mode != 'twcl') {
            $ret .= "<td class='entete'>Severity</td>\n";
        }

        $ret .= "<td class='entete'>Trigger - Threshold</td>\n";
        $ret .= "<td class='entete'>Condition</td>\n";
        $ret .= "<td class='entete'>Value</td>\n";
        if ($this->mode != 'twcl') {
            // Mantis 4178 : split additional details into two columns
            // on supprime la colonne si on est en static
            if ($this->alarm_type == 'dyn_alarm') {
                $ret .= "<td class='entete'>Average</td>\n";
                $ret .= "<td class='entete'>Overrun (%)</td>\n";
            }
        }
        $ret .= "<td class='entete'>Calculation time</td>\n";
        $ret .= "</tr>\n";

        return $ret;
    }

    /**
     * 01/03/2011 MMT bz 19128
     * return table data as html rows
     * @param array $result query results
     * @return string html
     */
    private function generateResultRows($result) {
        $couleur = array("#dddddd", "#f0f0f0"); // couleurs utilisées pour différencier les lignes des tableaux
        // modif 09/01/2008 Gwénaël
        // Ajout d'un lien externe dans le pdf

        global $repertoire_physique_niveau0;

        // 09/12/2011 ACS Mantis 837 DE HTTPS support
        $url_location = $this->productModel->getCompleteUrl('', true);

        $this->nb_lignes = count($result); // nombre de lignes dans le tableau HTML
        // on parcourt la liste des alarmes ayant sautées
        $ret = '';
        for ($i = 0; $i < $this->nb_lignes; $i++) {

            $row = $result[$i];
            $family = $row['family'];
            $ta = $row['ta'];
            $ta_label = $row['ta_label'];
            $ta_value = $row['ta_value'];
            $trigger = $row['trigger'];

            // modif  10/04/2007 Gwénaël - prise en compte du troisième axe
            $_na_value = explode('|s|', $row['na_value']); // modif 13/04/2007 Gwénaël changement de séparateur
            $na_value = $_na_value[0];
            // maj 12/03/2010 - MPR : Correction du BZ14709 - Les alarmes envoyées par pdf ne contiennent pas les informations 3ème axe
            if (isset($row['a3_value']) && $row['a3_value'] != "")
                $naAxe3_value = $row['a3_value'];
            $_na = explode('_', $row['na']);
            $na = $_na[0];
            $na_label = $this->naLabelList[$na][$family]; // 11/10/2006 xavier
            $naAxe3 = null;
            $naAxe3_label = null;
            // maj 12/03/2010 - MPR : Correction du BZ14709 - Les alarmes envoyées par pdf ne contiennent pas les informations 3ème axe
            if (isset($row['a3']) && $row['a3'] != "") {
                $naAxe3 = $row['a3'];
                $naAxe3_label = $this->naLabelList[$naAxe3][$family];
            }

            $kpi_array = get_kpi($family, $this->productId);
            $counter_array = get_counter($family, $this->productId);
            if (isset($kpi_array[$trigger]))
                $trigger = $kpi_array[$trigger];
            if (isset($counter_array[$trigger]))
                $trigger = $counter_array[$trigger];


            // s'il s'agit du mode top worst cell lists, on a besoin du label network
            // et du label famille pour le titre.
            if ($this->mode == 'twcl') {
                $this->na_label = $na_label;
                $this->family_label = $row['family_label'];
            }


            $show = true; // variable permettant d'afficher ou de cacher les données d'une colonne
            // on compare l'id_result en cours avec la précédente.
            // si les deux correspondent, il s'agit d'un nouveau trigger ou d'un champ additionel
            // de la même alarme. on n'affiche pas les informations générales de l'alarme.
            // la nouvelle ligne sera affichée avec la même couleur que la précédente.
            if ($i > 0) {
                $row_precedent = $result[$i - 1];
                if ($row['id_result'] == $row_precedent['id_result'])
                    $show = false;
            }


            // sera affiché à côté du nom de l'alarme dans alarm_history et alarm_management
            $alarmType['static'] = ' (static)';
            $alarmType['dyn_alarm'] = ' (dynamic)';


            // si la ligne du tableau contient les informations générales d'une alarme,
            // c'est qu'il s'agit d'un nouveau résultat
            if ($show)
                $this->nb_results++;

            // on construit le tableau de résultat
            // si deux lignes contiennent les mêmes informations générales (nom, na, criticité ...),
            // on affiche que les valeurs du trigger ou du champ additionnel.
            // la nouvelle ligne sera affichée avec la même couleur que la précédente.
            // l'ordre des colonnes change selon le sous mode utilisé (par élément réseau ou par alarme)
            // modif 09/01/2008 Gwénaël
            // ajout d'un lien dans le pdf
            $uri = '?alarm_type=' . $row['alarm_type'] . '&alarm_id=' . $row['id_alarm'] . '&na=' . $na . '&na_value=' . $na_value . '&ta=' . $ta . '&ta_value=' . $ta_value;
            if ($naAxe3)
                $uri .= '&na_axe3=' . $naAxe3 . '&na_axe3_value=' . $naAxe3_value;

            $ret .= "<tr>";
            if ($this->sous_mode == 'elem_reseau') {
                if ($show) //modif 10/04/2007 Gwénaël modif par rapport au troisième axe
                    $ret .= "<td style='background-color:" . $couleur[$this->nb_results % count($couleur)] . "'><a href='" . $url_location . $uri . "'>" . getNaLabel($na_value, $na, $family, $this->productId) . " ($na_label)" . ($naAxe3 ? '\n' . getNaLabel($naAxe3_value, $naAxe3, $family, $this->productId) . " ($naAxe3_label)" : '') . "</a></td>";
                else
                    $ret .= "<td style='background-color:" . $couleur[$this->nb_results % count($couleur)] . "'></td>";
            }

            if ($show)
                $ret .= "<td style='background-color:" . $couleur[$this->nb_results % count($couleur)] . "'>" . $row['alarm_name'] . $alarmType[$row['alarm_type']] . " (" . $row['family_label'] . ")</td>";
            else
                $ret .= "<td style='background-color:" . $couleur[$this->nb_results % count($couleur)] . "'></td>";
            if ($show)
                $ret .= "<td style='background-color:" . $couleur[$this->nb_results % count($couleur)] . "'>" . getTaValueToDisplay($ta, $ta_value) . " ($ta_label)</td>";
            else
                $ret .= "<td style='background-color:" . $couleur[$this->nb_results % count($couleur)] . "'></td>";
            if ($this->sous_mode == 'condense') {
                if ($show) //modif 10/04/2007 Gwénaël modif par rapport au troisième axe
                    $ret .= "<td style='background-color:" . $couleur[$this->nb_results % count($couleur)] . "'><a href='" . $url_location . $uri . "'>" . getNaLabel($na_value, $na, $family, $this->productId) . " ($na_label)" . ($naAxe3 ? '\n' . getNaLabel($naAxe3_value, $naAxe3, $family, $this->productId) . " ($naAxe3_label)" : '') . "</a></td>";
                else
                    $ret .= "<td style='background-color:" . $couleur[$this->nb_results % count($couleur)] . "'></td>";
            }

            //01/03/2011 MMT bz 19128 ajoute colonne severité pour excel
            if ($this->support_type == 'excel' && $this->mode != 'twcl') {
                $ret .= "<td style='background-color:" . $couleur[$this->nb_results % count($couleur)] . "'>{$row['critical_level']}</td>";
            }
            $ret .= "<td style='background-color:" . $couleur[$this->nb_results % count($couleur)] . "'>$trigger</td>";

            // maj 30/11/2007 - maxime  - Mise à jour de l'affichage des additional field  par rapport au nouveau mode de calcul.
            if ($row['alarm_type'] == 'dyn_alarm')
                $ret .= "<td style='background-color:" . $couleur[$this->nb_results % count($couleur)] . "'> " . $row['trigger_operand'] . " " . $row['trigger_value'] . "%</td>";
            else
                $ret .= "<td style='background-color:" . $couleur[$this->nb_results % count($couleur)] . "'> " . $row['trigger_operand'] . " " . $row['trigger_value'] . "</td>";

            if (!$row['additional_details']) {
                $ret .= "<td style='background-color:" . $couleur[$this->nb_results % count($couleur)] . "'>" . round($row['value'], 2) . "</td>";
            } else {
                // 11/10/2006 xavier
                $additional_details = explode('@', $row['additional_details']);
                $ret .= "<td style='background-color:" . $couleur[$this->nb_results % count($couleur)] . "'>" . round($row['value'], 2) . "</td>";
            }
            // modif 11/05/2007 Gwénaël
            // suppression de la colonne "Critical Level"
            if ($this->mode != 'twcl') {
                // Mantis 4178 : split additional details into two columns 
                // on supprime la colonne si on est en static
                if (isset($this->alarm_type) && $this->alarm_type == 'dyn_alarm' || $row['additional_details']) {
                    $ret .= "<td style='background-color:" . $couleur[$this->nb_results % count($couleur)] . "'>" . round($additional_details[0], 2) . " </td><td style='background-color:" . $couleur[$this->nb_results % count($couleur)] . "'>" . round($additional_details[1], 2) . "</td>";
                }
            }
            if ($show)
                $ret .= "<td style='background-color:" . $couleur[$this->nb_results % count($couleur)] . "'>" . $row['calculation_time'] . "</td>";
            else
                $ret .= "<td style='background-color:" . $couleur[$this->nb_results % count($couleur)] . "'></td>";
            $ret .= "</tr>\n";
        }
        // on mémorise le type de l'alarme.
        $this->alarm_type = $row['alarm_type'];
        return $ret;
    }

}

// end of class
?>
