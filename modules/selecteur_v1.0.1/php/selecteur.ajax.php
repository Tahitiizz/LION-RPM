<?php
/*
	20/11/2008 MPR
		- Ajout du cas 5 pour le sélecteur des Top-Worst
	21/11/2008 BBX
		Création du cas 1, récupération de la dernière valeur de la ta
	27/05/2009 SPS
		- Ajout des cas 7 à 11, pour le selecteur d'investigation dashboard
	23/06/2009 GHX
		- Ajout du cas 12 : sauvegarde en SESSION des éléments réseaux sélectionnnés
		- Ajout du cas 13 : reset de la SESSION des éléments réseaux sélectionnées par rapport au dashboard courant
	13/07/2009 GHX
		- Correction du BZ10594 [REC][Investigation Dashboard]: affichage warning pour certains NA
	16/07/2009 GHX
		- Modif dans le case 7 pour Investigation Dashboard
	20/07/2009 GHX
		- Ajout d'une condition dans le case 3
	28/07/2009 GHX
		- Ajout du case 17
		- Modification du case 12 et 13

	26/08/2009 GHX
		- Correction du BZ 11230 [REC][T&A CB 5.0][TC#40586][TS#UC16-CB50][TP#1][INVESTIGATION DASHBOARD]: erreur accès ajax pour grand nb NE
			-> Utilisation de POST au lieu de GET
		- Modification dans les CASE d'Investigation Dashboard (encodage des caractères, échapement des cotes...)
	27/08/2009 - MPR :
		- +Correction du bug 11248 et 11249 - Si aucun raw/kpi n'est déployé, on ne sélectionne pas le premier raw de la famille
	28/08/2009 GHX
		- Modifitication de la condition sur isset($_GET)
	01/09/2009 GHX
		- Modification dans le case 10 (Investigation Dashboard)
	10:13 17/09/2009 GHX
		- Correction du BZ 11465 => Impossible de faire une recherche sur le caractère +

	22/09/2009 BBX
		- Ajout de balises title sur les éléments réseaux (CASE 6,7 & 9) => title='".utf8_encode($label_elem)."'. BZ 11463
	24/09/2009 GHX
		- On utilise htmlentities au lieu de utf8_encode dans le case 9
   14/01/2011 NSE bz 19965 : erreur dans le label lorsque le code se termine par | -> remplacement du separateur || par |*|
  
   06/06/2011 MMT
      - DE 3rd Axis choisit la variable de session concernée en fonction de l'axe
   27/07/2011 MMT Bz 22896 add 3rd axis preferences session variable
   25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
*/
?>
<?php
/**
*	Ce fichier gère les requêtes ajax du sélecteur
*
*
*	@author	BBX - 29/10/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*/

session_start();
include_once dirname(__FILE__)."/../../../php/environnement_liens.php";
require_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php");
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/DashboardModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/GTMModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/InvestigationModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/GisSelecteurModel.class.php');

set_time_limit(3600);

// Récupération des données
// 16:06 28/08/2009 GHX
// Modification de la condition
if(isset($_POST) && (!isset($_GET) || count($_GET) == 0)) $_GET = $_POST;

// 07/04/2009 BBX : ajout d'un test sur la valeur 'ALL'
$id_prod = "";
if(isset($_GET['product']) && ($_GET['product'] != 'ALL'))
	$id_prod = $_GET['product'];

// Connexion à la base de données
$database   = DataBase::getConnection($id_prod);
$url        = URL_SELECTEUR."php/".basename(__FILE__);

// En fonction de l'action demandées
switch($_GET['action'])
{
	//**
	// Cas 1 : Récupération de la dernière valeur de la ta
	case 1:
		// Récupération des valeurs + test des valeurs obligatoires
		$id_dashboard = isset($_GET['id_page']) ? $_GET['id_page'] : '';
		if(!isset($_GET['na'])) exit;
		$na = $_GET['na'];
		if(!isset($_GET['ta'])) exit;
		$ta = $_GET['ta'];
		$na_axe3 = "";
		if(isset($_GET['na_axe3']))
			$na_axe3 = $_GET['na_axe3'];
		// On retire la partie bh si présente
		$ta_column = $ta;
		if(substr($ta,-2) == 'bh') $ta_column = substr($ta,-3);
		// Si l'id dashboard est présent, on récupère les produits liés à ce dashboard.
		// Sinon, on récupère tous les produits
		$products = Array();
		if($id_dashboard != '') {
			$dashboad = new DashboardModel($id_dashboard);
			$products = $dashboad->getInvolvedProducts();
		}
		else {
			$query = "SELECT sdp_id FROM sys_definition_product WHERE sdp_on_off = 1";
			foreach($database->getAll($query) as $array_products) {
				$products[] = $array_products['sdp_id'];
			}
		}
		// Pour tous les produits, on récupère la ta la plus récente
		$last_ta = '';
		foreach($products as $p) {
			$db_temp = DataBase::getConnection($p);
			// Quelles familles accueillent notre na ?
			if ($na_axe3 == "")
				$cond_axe3 = "family NOT IN (SELECT DISTINCT family FROM sys_definition_network_agregation WHERE axe = 3)";
			else
				$cond_axe3 = "family IN (SELECT DISTINCT family FROM sys_definition_network_agregation WHERE agregation = '{$na_axe3}' AND axe = 3)";

			$query = "SELECT DISTINCT family FROM sys_definition_network_agregation WHERE agregation = '{$na}' AND {$cond_axe3}";
			// Pour toutes les familles, on récupère la dernière ta et on conserve la plus récente
			foreach($db_temp->getAll($query) as $array_family) {
				$family = $array_family['family'];
				// Récupération du group table
				$query_gt = "SELECT edw_group_table FROM sys_definition_group_table WHERE family = '{$family}'";
				$result_gt = $db_temp->getRow($query_gt);
				$group_table = $result_gt['edw_group_table'];
				// Construction de la table de données dans laquelle aller chercher la ta
				$table = $group_table . '_raw_' . $na . '_' . (($na_axe3 != '') ? $na_axe3.'_' : '') . $ta;
				// Récupération de la dernière TA
				$query_ta = "SELECT MAX({$ta_column}) AS ta FROM {$table}";

				$result_ta = $db_temp->getRow($query_ta);
				// Si la ta récupérée est plus récente que la dernière ta, on écrase la valeur
				if($result_ta['ta'] > $last_ta)
					$last_ta = $result_ta['ta'];
			}
		}
		// On retourne la ta la plus récente, ou chaine vide si pas de ta récupérée. Si la valeur est vide, il ne faudra pas mettre à jour.
		// La valeur retournée est formattée comme le sélecteur l'attend. Pour Hour, on concatène le jour avec l'heure à l'aide d'un pipe.
		if(($last_ta != '') && is_numeric($last_ta)) {
			switch($ta_column)
			{
				case 'hour':
					echo date('d/m/Y',strtotime(substr($last_ta,0,8))).'|'.substr($last_ta,-2).':00';
				break;
				case 'day':
					echo date('d/m/Y',strtotime($last_ta));
				break;
				case 'week':
					echo 'W'.substr($last_ta,-2).'-'.substr($last_ta,0,4);
				break;
				case 'month':
					echo substr($last_ta,-2).'/'.substr($last_ta,0,4);
				break;
			}
		}
	break;

	//**
	// Cas 2 : Mise à jour de la session
	case 2:
		$id_user = $_SESSION['id_user'];
		$new_time = date('d,m,Y@H:i:s');
		$query = "UPDATE users SET last_connection = '{$new_time}' WHERE id_user = '{$id_user}'";
		$database->execute($query);
	break;

	//**
	// Cas 3 : Autocompletion
	// maj 20/11/2008 MPR : Ajout du cas 5 pour le sélecteur des Top-Worst
	// maj 24/11/2008 BBX : utilisation de $_SESSION['selecteur']['saved_search'] pour la recherche
	case 3:
	case 5:
		// Génération du header xml
		$headers['Pragma']        = 'no-cache';
		$headers['Expires']       = '0';
		$headers['Last-Modified'] = gmdate("D, d M Y H:i:s") . " GMT";
		$headers['Cache-Control'] = 'no-cache, must-revalidate';
		$headers['Content-type']  = 'application/xml; charset=UTF-8';
		foreach ( $headers as $key => $value ){
			header($key. ':' . $value);
		}

		// Récupération  de la chaîne tapée par l'utilisateur
		$debut = "";
		if (isset($_GET['debut'])) {
			// 10:13 17/09/2009 GHX
			// Correction du BZ 11465
			// On echape le plus pour pouvoir faire une recherche dessus
		    $debut = str_replace('+', '\+', utf8_decode($_GET['debut']));
		}

		// Récupération du NA (idT)
		$idT = isset($_GET['idT']) ? utf8_decode($_GET['idT']) : '';
		$MAX_RETURN = 10;
		$found = 0;
		// On recherche les occurences de la chaînes dans le tableau des éléments, sur le code et le label.
		echo "<ul>";
		// 14:54 20/07/2009 GHX
		// Ajout d'une condition sinon plantage si la valeur n'est pas un tableau ?!
		if ( gettype($_SESSION['selecteur']) == 'array' )
		{
			foreach($_SESSION['selecteur']['saved_search'] as $key=> $val) {
				if(@preg_match( '/'. strtolower($debut) .'/', strtolower($key) ) || @preg_match( '/'. strtolower($debut) .'/', strtolower($val) )) {
					$id = $idT."||".$key;
					// maj 20/11/2008 MPR : Ajout du cas 5 pour le sélecteur des Top-Worst
					if($_GET['action'] == 5){
						$separateur = "||";
						$val_na2na = 1;
						$id = $idT.$separateur.$key.$separateur.$val_na2na;
					}
					echo(utf8_encode("<li id='li_$id' id_to_check='$id'>{$val}</li>"));
					$found++;
				}
				if($found == $MAX_RETURN)
					break;
			}
		}
		if(!$found)
			echo "<li>".__T('SELECTEUR_NO_RESULT')."</li>";
		echo "</ul>";
	break;

	//**
	// Cas 3 : Récupération des éléments 3ème axe
	case 4:
		// On récupère le contenu de la sélection courante et le séparateur.
		$axe3	= $_GET['axe3'];
		$axe3_2	= $_GET['axe3_2'];
		// Id du dashboard
		$id_dashboard = isset($_GET['id_page']) ? $_GET['id_page'] : '';
		// Si l'id dashboard est présent, on récupère les produits liés à ce dashboard.
		// Sinon, on récupère tous les produits


		$products = Array();
		if($id_dashboard != '') {
			$dashboad = new DashboardModel($id_dashboard);
			$products = $dashboad->getInvolvedProducts();
		}
		else if( $_GET['product'] == "" ) {

			$database = DataBase::getConnection();
			$query = "SELECT sdp_id FROM sys_definition_product WHERE sdp_on_off = 1";
			foreach($database->getAll($query) as $array_products) {
				$products[] = $array_products['sdp_id'];
			}
		} else {

			$products = array($_GET['product']);

		}
		// Parcours des produits
		foreach($products as $p) {
			// On se connecte à notre produit
			$db_temp = DataBase::getConnection($p);
			// On récupère les éléments appartenant à ce niveau pour ce produit
			$query_third_axis_elements = "SELECT DISTINCT eor_id, eor_label
											FROM edw_object_ref
											WHERE eor_obj_type = '{$axe3}'
											ORDER BY eor_label, eor_id";
			$array_elements = Array();
			$result = $db_temp->execute($query_third_axis_elements);
			while($array = $db_temp->getQueryResults($result,1)) {
				$eor_id = $array['eor_id'];
				$eor_label = (trim($array['eor_label']) != '') ? $array['eor_label'] : $eor_id;
				$array_elements[ $eor_id ] = $eor_label;
			}
		}
		// On créé la chaine de retour

		// 26/01/2009 - Modif. benoit : ajout dans le tableau de l'element "ALL" (toutes les valeurs de l'element 3ème axe)

		// 26/01/2009 - Modif. benoit : modification du séparateur nom / label ne -> remplacement de "|" par "||"
		// 14/01/2011 NSE bz 19965 : remplacement du separateur || par |*|
		$html = array('ALL|*|ALL');
		foreach ($array_elements as $eor_id => $eor_label)
		{
			// maj 16:02 06/11/2009 : Correction du bug 12159 - GEstion des caractères spéciaux
			$html[] = utf8_encode($eor_id)."|*|".utf8_encode($eor_label);
		}
		// on envoie le tout
		echo implode('|s|', $html);
	break;

	//**
	// Cas 6 : Récupération de la liste des éléments réseau
	case 6:
		// On fixe la limite du nombre de NA par div
		$MaxElements = 5000;
		$limit = 0;
		// Récupération des valeurs
		$id_page = isset($_GET['id_page']) ? $_GET['id_page'] : '';
		$id_prod = isset($_GET['product']) ? $_GET['product'] : '';
		$na = isset($_GET['idT']) ? $_GET['idT'] : '';
		$count = isset($_GET['count']) ? $_GET['count'] : 0;
		// Longueur max d'un label
		$labelMaxLength = get_sys_global_parameters('na_label_character_max');
		// Si l'id du dashboard est présent, on recherche ses éléments parmis ses produits
		// Sinon on utilise le produit passé en paramètre (id d'un produit ou chaine vide)
		$products = Array($id_prod);
		if($id_page != '') {
			$dashboad = new DashboardModel($id_page);
			$products = $dashboad->getInvolvedProducts();
		}
		// 07/04/2009 BBX
		// On peut récupérer la topo sur tous les produits avec ALL comme valeur
		if($id_prod == 'ALL') {
			$products = Array();
			$database = DataBase::getConnection();
			$query_prod = "SELECT sdp_id FROM sys_definition_product WHERE sdp_on_off = 1";
			$result = $database->execute($query_prod);
			while($prod = $database->getQueryResults($result,1)) {
				$products[] = $prod['sdp_id'];
			}
		}
                
                // Infos sur le produit maitre
		$array_master_prod = getTopoMasterProduct();
                
                // 14/01/2013 BBX
                // DE Ne Filter : condition de filtrage sur les éléments
                $conditionFilter = "";
                if(!empty($_SESSION['nefiltering']))
                    $conditionFilter = getNeFilterCondition($_SESSION['nefiltering'], $na, $array_master_prod['sdp_id']);

		// 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
		// Définition des requêtes qui récupèrent les éléments réseau
		// Requête master, on récupère l'id et le label
		$query_master = "
			SELECT DISTINCT eor_id,
				CASE WHEN eor_label IS NULL THEN '('||eor_id||')' ELSE eor_label END AS eor_label
			FROM edw_object_ref
			WHERE eor_id IS NOT NULL
				AND	eor_obj_type = '$na'
				AND	eor_on_off=1
				AND ".NeModel::whereClauseWithoutVirtual()."
                                {$conditionFilter}
			ORDER BY eor_label
		";
                         
		// Requête par défaut, on récupère l'id, le label et l'id équivalent
                // 18/06/2013 NSE bz 34353 : dans le cas d'un multi-produit, dans My profile (product = ALL)
                // on perdait %CONDITION_FILTER% après le premier passage dans la boucle -> _orig
		$query_default_orig = "
			SELECT DISTINCT eor_id,
				CASE WHEN eor_label IS NULL THEN '('||eor_id||')' ELSE eor_label END AS eor_label,
				eor_id_codeq
			FROM edw_object_ref
			WHERE eor_id IS NOT NULL
				AND	eor_obj_type = '$na'
				AND	eor_on_off=1
				AND ".NeModel::whereClauseWithoutVirtual()."
                                 %CONDITION_FILTER%
			ORDER BY eor_label
		";
		
		// Récupération de la liste des éléments du maître
		$database = DataBase::getConnection($array_master_prod['sdp_id']);
		$array_master_elements = Array();
		$result = $database->execute($query_master);
		while($elem = $database->getQueryResults($result,1)) {
                    $array_master_elements[$elem['eor_id']] = $elem['eor_label'];
		}
		// Tableau qui va récupérer les éléments réseau
		$array_network_elements = Array();
		// Parcours des produits et récupération des éléments réseau
		foreach($products as $p) 
                {
                    // 14/01/2013 BBX
                    // DE Ne Filter : condition de filtrage sur les éléments
                    $conditionFilter = "";
                    if(!empty($_SESSION['nefiltering']))
                        $conditionFilter = getNeFilterCondition($_SESSION['nefiltering'], $na, $p);
                    // 18/06/2013 NSE bz 34353 : _orig
                    $query_default = str_replace('%CONDITION_FILTER%',$conditionFilter,$query_default_orig);
                    
                    // Si nous sommes sur la maître, on récupère les éléments sans se poser de question
                    if($array_master_prod['sdp_id'] == $p) {
                        foreach($array_master_elements as $id_elem=>$elem) {
                            $array_network_elements[$id_elem] = $elem;
                        }
                    }
                    // Si nous ne sommes pas sur le produit master et que le master est présent dans la liste des produits, on va gérer le mapping
                    else {
                        // Connexion au produit
                        $database = DataBase::getConnection($p);
                        $result = $database->execute($query_default);
                        while($elem = $database->getQueryResults($result,1)) {
                            // On regarde si cet élément est mappé
                            if(array_key_exists($elem['eor_id_codeq'],$array_master_elements)) {
                                // Si cet élément est mappé, on le récupère avec son code / label du master
                                $array_network_elements[$elem['eor_id_codeq']] = $array_master_elements[$elem['eor_id_codeq']];
                            }
                            else {
                                // Cas standard : l'élément n'est pas mappé. On le récupère avec son code / label du produit en cours
                                $array_network_elements[$elem['eor_id']] = $elem['eor_label'];
                            }
                        }
                    }
		}
		// Si on demande simplement à compter les élements, on retourne le nombre d'élements de $array_network_elements
		if($count == 1) {
			$nb_na = count($array_network_elements);
			if ($nb_na > 1) {
				// pluriel
				echo "&nbsp;". __T("SELECTEUR_ON_N_ELEMENTS",intval($nb_na));
			} else {
				// singulier
				echo "&nbsp;". __T("SELECTEUR_ON_N_ELEMENT",intval($nb_na));
			}
			exit;
		}
        // 15/02/2011 OJT : bz19869, suppression du array_unique et asort (déjà dans la requête)

                // On sauvegarde notre tableau pour l'utiliser avec la recherche
		if(gettype($_SESSION['selecteur']) != 'array') unset($_SESSION['selecteur']);
		$_SESSION['selecteur']['saved_search'] = $array_network_elements;
		// Désormais, on peut parcourir notre tableau pour générer la chaine HTML de retour
		$html = '';
		$separateur = "||";
		foreach($array_network_elements as $id_elem=>$label_elem) {
			$idN = $na.$separateur.$id_elem;
			// maj 15/11/2008 - MPR : Pour le sélecteur des Top-Worst, la valeur des id des checkbox est différente / format de l'id de chaque checkbox => na||na_value||na_desc
			if($_GET['idN']){
				$val_na2na = "1";
				$idN.= "||".$val_na2na;
			}
                        $bold = '';
                        if(!empty($_SESSION['nefiltering'][$na])) {
                            if(in_array($id_elem,$_SESSION['nefiltering'][$na])) {
                                $bold = 'font-weight:bold';
                            }
                        }
                        if(empty($_POST['oldVersion']))
                        {
                            $html .= "
                            <input type='checkbox' id='".$idN."' value='".$id_elem."_$na' title='".utf8_encode($label_elem)."' 
                                onclick=\"saveInNeSelection('".$idN."');neselUpdateCheckall();\" 
                                onmouseover=\"updateNeSelectionWindowStatus('Click here to select / unselect this element')\"
                                onmouseout=\"updateNeSelectionWindowStatus('')\" />
                            <span style='cursor:pointer;$bold' 
                                onclick='neselFilter(\"$na\",\"$id_elem\",\"$url\",this)'
                                onmouseover=\"updateNeSelectionWindowStatus('Click here to filter / unfilter the list of the lower-level elements')\"
                                onmouseout=\"updateNeSelectionWindowStatus('')\">".utf8_encode((strlen($label_elem) > $labelMaxLength) ? substr($label_elem,0,$labelMaxLength).'...' : $label_elem)."</span><br />";
                        }
                        else
                        {
                            $html .= "
                            <input type='checkbox' id='".$id_elem."' value='".$id_elem."' title='".htmlentities($label_elem)."' 
                                onclick=\"saveInNeSelection('".$id_elem."','".htmlentities($label_elem)."');saveCurrentSelection();\" />
                            <label for='{$id_elem}'>".((strlen($label_elem) > $labelMaxLength) ? htmlentities(substr(stripslashes($label_elem),0,$labelMaxLength)).'...' : htmlentities(stripslashes($label_elem)))."</label><br />";  
                        }
			// Test de la limite
			if($limit == $MaxElements) {
				$html .= "<div class='texteRouge' style='padding:5px;'>".__T('U_SELECTEUR_NE_TOO_MANY_ELEMENTS')."</div>";
				break;
			}
			$limit++;
		}
		// Si rien n'a été trouvé, on retourne le message d'erreur correspondant
		if($html == '') $html = __T('SELECTEUR_NO_VALUE_FOUND',$id_prod);
		// Retour du code HTML
		echo $html;
		break;

	/**
	 * 27/05/2009 SPS - investigation dashboard : raw/kpi selection
	 **/
	case 7 :
		// On fixe la limite du nombre de NA par div
		$MaxElements = 5000;
		$limit = 0;
		// Récupération des valeurs

		$id_prod = isset($_GET['product']) ? $_GET['product'] : '';
		$id_family = isset($_GET['family']) ? $_GET['family'] : '';
		$type = isset($_GET['type']) ? $_GET['type'] : '';

		// Longueur max d'un label
		$labelMaxLength = get_sys_global_parameters('na_label_character_max');

		//suivant le type, on va chercher les raw/kpis
		$investigation = new InvestigationModel($id_prod, $id_family);
		if ($type == 'raw') {
			$counters = $investigation->getRaws();
		}
		if ($type == 'kpi') {
			$counters = $investigation->getKpis();
		}

		foreach($counters as $c_id => $c_value) {
			$array_counters[$type."@".$c_id.'@'.$c_value[0]] = $c_value[1];
		}

		// On sauvegarde le tableau en session pour l'utiliser avec la recherche
		if(gettype($_SESSION['selecteur']) != 'array') unset($_SESSION['selecteur']);
		$_SESSION['selecteur']['saved_search'] = $array_counters;

		// Désormais, on peut parcourir notre tableau pour générer la chaine HTML de retour
		$html = '';
		if ( count($array_counters) > 0 )
		{
			foreach($array_counters as $elem=>$label_elem) {

				$elements = explode("@",$elem);
				//type du compteur (raw ou kpi)
				$type = $elements[0];
				//id du compteur
				$id_elem = $elements[1];
				// 15:35 16/07/2009 GHX
				// L'id de l'élément contient une valeur suplémentaire
				// nom de l'élément
				$name_elem = $elements[2];

				//l'id correspond a : type_compteur@id_compteur@nom_compteur
				$id = $elem;

				$html .= "
				<input type='checkbox' id='".$id."' value='".$id."' title='".utf8_encode($label_elem)."' onclick=\"saveInNeSelection('".$id."','".$label_elem."');\" />
				<label for='".$id."' title='".utf8_encode($label_elem)."'>".((strlen($label_elem) > $labelMaxLength) ? substr($label_elem,0,$labelMaxLength).'...' : $label_elem)."</label><br />";
				// Test de la limite
				if($limit == $MaxElements) {
					$html .= "<div class='texteRouge' style='padding:5px;'>".__T('U_SELECTEUR_NE_TOO_MANY_ELEMENTS')."</div>";
					break;
				}
				$limit++;
			}
		}
		// Si rien n'a été trouvé, on retourne le message d'erreur correspondant
		if($html == '') $html = __T('SELECTEUR_NO_VALUE_FOUND',$id_prod);
		// Retour du code HTML
		echo $html;
	break;


	/**
	 * 27/05/2009 SPS - investigation dashboard : autocompletion des raw/kpi pour la zone de recherche
	 **/
	case 8:
		// Génération du header xml
		$headers['Pragma']        = 'no-cache';
		$headers['Expires']       = '0';
		$headers['Last-Modified'] = gmdate("D, d M Y H:i:s") . " GMT";
		$headers['Cache-Control'] = 'no-cache, must-revalidate';
		$headers['Content-type']  = 'application/xml; charset=UTF-8';
		foreach ( $headers as $key => $value ){
			header($key. ':' . $value);
		}
		// Récupération  de la chaîne tapée par l'utilisateur
		$debut = "";
		if (isset($_GET['debut'])) {
		    $debut = utf8_decode($_GET['debut']);
		}

		$MAX_RETURN = 10;
		$found = 0;
		// On recherche les occurences de la chaînes dans le tableau des éléments, sur le code et le label.
		echo "<ul>";
		foreach($_SESSION['selecteur']['saved_search'] as $key => $val) {

		   //l'id correspond a : type_compteur@id_compteur@nom_compteur@label_compteur
		   $id = $key."@".$val;

		   if(@preg_match( '/'. strtolower($debut) .'/', strtolower($id) ) || @preg_match( '/'. strtolower($debut) .'/', strtolower($val) )) {
				echo(utf8_encode("<li id='li_$id' id_to_check='$id'>{$val}</li>"));
				$found++;
			}
			if($found == $MAX_RETURN)
				break;
        }
		if(!$found)
			echo "<li>".__T('SELECTEUR_NO_RESULT')."</li>";
		echo "</ul>";
	break;

	/**
	 * 27/05/2009 SPS - investigation dashboard : selection des elements reseaux
	 **/
	case 9:
		$MaxElements = 5000;
		$limit = 0;
		// Récupération des valeurs

		$id_product = isset($_GET['product']) ? $_GET['product'] : '';
		$na = isset($_GET['idT']) ? $_GET['idT'] : '';

		// Longueur max d'un label
		$labelMaxLength = get_sys_global_parameters('na_label_character_max');
                
                // Infos sur le produit maitre
		$array_master_prod = getTopoMasterProduct();
                
                // 14/01/2013 BBX
                // DE Ne Filter : condition de filtrage sur les éléments
                $conditionFilter = "";
                if(!empty($_SESSION['nefiltering']))
                    $conditionFilter = getNeFilterCondition($_SESSION['nefiltering'], $na, $array_master_prod['sdp_id']); 
		// Définition des requêtes qui récupèrent les éléments réseau
		// Requête par défaut, on récupère l'id, le label et l'id équivalent
		$query_master = "
			SELECT DISTINCT eor_id,
				CASE WHEN eor_label IS NULL THEN '('||eor_id||')' ELSE eor_label END AS eor_label
			FROM edw_object_ref
			WHERE eor_id IS NOT NULL
				AND	eor_obj_type = '$na'
				AND	eor_on_off=1
				AND ".NeModel::whereClauseWithoutVirtual()."
                                {$conditionFilter}
			ORDER BY eor_label
		";
                                
                // 14/01/2013 BBX
                // DE Ne Filter : condition de filtrage sur les éléments
                $conditionFilter = "";
                if(!empty($_SESSION['nefiltering']))
                    $conditionFilter = getNeFilterCondition($_SESSION['nefiltering'], $na, $id_product); 
		$query = "
			SELECT DISTINCT eor_id,
				CASE WHEN eor_label IS NULL THEN '('||eor_id||')' ELSE eor_label END AS eor_label,
				eor_id_codeq
			FROM edw_object_ref
			WHERE eor_id IS NOT NULL
				AND	eor_obj_type = '$na'
				AND	eor_on_off=1
				AND ".NeModel::whereClauseWithoutVirtual()."
                                {$conditionFilter}
			ORDER BY eor_label
		";

		// Récupération de la liste des éléments du maître
		$database = DataBase::getConnection( $array_master_prod['sdp_id'] );

		$array_network_elements_master = array();
		$result = $database->execute($query_master);
		while($elem = $database->getQueryResults($result,1)) {
			$array_network_elements_master[$elem['eor_id']] = $elem['eor_label'];
		}

		// 10:06 13/07/2009 GHX
		// Correction du BZ10594 [REC][Investigation Dashboard]: affichage warning pour certains NA
		// Initialisation de la variable sinon problème avec la fonction array_unique
		$array_network_elements = array();
		// On se connecte sur le produit
		$db_temp = DataBase::getConnection($id_product);

		$result = $db_temp->execute($query);
		while($elem = $database->getQueryResults($result,1)) {

			if(array_key_exists($elem['eor_id_codeq'],$array_network_elements_master)) {
				// Si cet élément est mappé, on le récupère avec son code / label du master
				$array_network_elements[ $na."@".$elem['eor_id_codeq']."@".addslashes($array_network_elements_master[ $elem['eor_id_codeq'] ]) ] = addslashes($array_network_elements_master[ $elem['eor_id_codeq'] ]);

			}else{
				// Cas standard : l'élément n'est pas mappé. On le récupère avec son code / label du produit en cours
				$array_network_elements[  $na."@".$elem['eor_id']."@".htmlentities( $elem['eor_label']) ] = addslashes( $elem['eor_label'] );

			}

		}


		// Si on demande simplement à compter les élements, on retourne le nombre d'élements de $array_network_elements
		if($count == 1) {
			$nb_na = count($array_network_elements);
			if ($nb_na > 1) {
				// pluriel
				echo "&nbsp;". __T("SELECTEUR_ON_N_ELEMENTS",intval($nb_na));
			} else {
				// singulier
				echo "&nbsp;". __T("SELECTEUR_ON_N_ELEMENT",intval($nb_na));
			}
			exit;
		}
                // 15/02/2011 OJT : bz19869, suppression du array_unique et asort (déjà dans la requête)

		// On sauvegarde notre tableau en session pour l'utiliser avec la recherche
		if(gettype($_SESSION['selecteur']) != 'array') unset($_SESSION['selecteur']);

		$_SESSION['selecteur']['saved_search'] = $array_network_elements;
		// Désormais, on peut parcourir notre tableau pour générer la chaine HTML de retour

		$html = '';

		foreach($array_network_elements as $id_elem=>$label_elem) {
			// 11:16 26/08/2009 GHX
			// Ajout du addslashes
			$id_elem = addslashes($id_elem);
                        list($realNaId, $realNeId) = explode('@',$id_elem);
			// 11:08 26/08/2009 GHX
			// Ajout de htmlentities
                        
                        // 10/06/2013 NSE bz 34235 : le NE sélectionné n'est pas remis en gras
                        $bold = '';
                        if(!empty($_SESSION['nefiltering'][$na])) {
                            if(in_array($realNeId,$_SESSION['nefiltering'][$na])) {
                                $bold = 'font-weight:bold';
                            }
                        }
                        
                        if(empty($_POST['oldVersion']))
                        {
                            $html .= "
                            <input type='checkbox' id='".$id_elem."' value='".$id_elem."' title='".htmlentities($label_elem)."' 
                                onclick=\"saveInNeSelection('".$id_elem."','".htmlentities($label_elem)."');saveCurrentSelection();\"
                                onmouseover=\"updateNeSelectionWindowStatus('Click here to select / unselect this element')\"
                                onmouseout=\"updateNeSelectionWindowStatus('')\" />
                            <span style='cursor:pointer;$bold' 
                                onclick='neselFilter(\"$na\",\"$realNeId\",\"$url\",this)'
                                onmouseover=\"updateNeSelectionWindowStatus('Click here to filter / unfilter the list of the lower-level elements')\"
                                onmouseout=\"updateNeSelectionWindowStatus('')\">".((strlen($label_elem) > $labelMaxLength) ? htmlentities(substr(stripslashes($label_elem),0,$labelMaxLength)).'...' : htmlentities(stripslashes($label_elem)))."</span><br />";
                        }
                        else
                        {
                            $html .= "
                            <input type='checkbox' id='".$id_elem."' value='".$id_elem."' title='".htmlentities($label_elem)."' 
                                onclick=\"saveInNeSelection('".$id_elem."','".htmlentities($label_elem)."');saveCurrentSelection();\" />
                            <label for='{$id_elem}'>".((strlen($label_elem) > $labelMaxLength) ? htmlentities(substr(stripslashes($label_elem),0,$labelMaxLength)).'...' : htmlentities(stripslashes($label_elem)))."</label><br />";  
                        }
			// Test de la limite
			if($limit == $MaxElements) {
				$html .= "<div class='texteRouge' style='padding:5px;'>".__T('U_SELECTEUR_NE_TOO_MANY_ELEMENTS')."</div>";
				break;
			}
			$limit++;
		}
		// Si rien n'a été trouvé, on retourne le message d'erreur correspondant
		if($html == '') $html = __T('SELECTEUR_NO_VALUE_FOUND',$id_prod);
		// Retour du code HTML
		echo $html;

	break;

	/**
	 * 27/05/2009 SPS - investigation dashboard : autocompletion des elements reseaux
	 **/
	case 10:
		// Génération du header xml
		$headers['Pragma']        = 'no-cache';
		$headers['Expires']       = '0';
		$headers['Last-Modified'] = gmdate("D, d M Y H:i:s") . " GMT";
		$headers['Cache-Control'] = 'no-cache, must-revalidate';
		$headers['Content-type']  = 'application/xml; charset=UTF-8';
		foreach ( $headers as $key => $value ){
			header($key. ':' . $value);
		}
		// Récupération  de la chaîne tapée par l'utilisateur
		$debut = "";
		if (isset($_GET['debut'])) {
			// 10:13 17/09/2009 GHX
			// Correction du BZ 11465
			// On echape le plus pour pouvoir faire une recherche dessus
		    $debut = str_replace('+', '\+', utf8_decode($_GET['debut']));
		}

		$MAX_RETURN = 10;
		$found = 0;
		// On recherche les occurences de la chaînes dans le tableau des éléments, sur le code et le label.
		echo "<ul>";
		foreach($_SESSION['selecteur']['saved_search'] as $key=> $val) {

		   if(@preg_match( '/'. strtolower($debut) .'/', strtolower($key) ) || @preg_match( '/'. strtolower($debut) .'/', strtolower($val) )) {
				// 11:21 26/08/2009 GHX
				// Ajout du addslashes
				echo("<li id='li_".addslashes($key)."' id_to_check='".addslashes($key)."'>".utf8_encode($val)."</li>");
				$found++;
			}
			if($found == $MAX_RETURN)
				break;
        }
		if(!$found)
			echo "<li>".__T('SELECTEUR_NO_RESULT')."</li>";
		echo "</ul>";
	break;

	/**
	 * 27/05/2009 SPS - investigation dashboard : 3eme axe
	 **/
	case 11:
		// On récupère le contenu de la sélection courante et le séparateur.
		$axe3	= $_GET['axe3'];
		$axe3_2	= $_GET['axe3_2'];
		$id_product = $_GET['id_product'];

		// On se connecte à notre produit
		$db_temp = Database::getConnection($id_product);
		// On récupère les éléments appartenant à ce niveau pour ce produit
		$query_third_axis_elements = "SELECT DISTINCT eor_id, eor_label
										FROM edw_object_ref
										WHERE eor_obj_type = '{$axe3}'
										ORDER BY eor_label, eor_id";
		$array_elements = Array();
		$result = $db_temp->execute($query_third_axis_elements);
		while($array = $db_temp->getQueryResults($result,1)) {
			$eor_id = $array['eor_id'];
			$eor_label = (trim($array['eor_label']) != '') ? $array['eor_label'] : $eor_id;
			$array_elements[$eor_id] = $eor_label;
		}

		$html = array('ALL||ALL');

		foreach ($array_elements as $eor_id => $eor_label)
		{
			$html[] = "$eor_id||$eor_label";
		}
		// on envoie le tout
		echo implode('|s|', $html);
	break;

	/*
		22/06/2009 GHX
		Sélection des éléments réseaux en SESSION

		27/07/2009 GHX
			- Modification de la variable de session qui contient la liste des éléments réseaux de préférences de l'utilisateur
	*/
	case 12 :
		// 09:47 26/08/2009 GHX
		// BZ 11230
		$separator = $_POST['separator'];
		$activeNa = $_POST['current_na'];
		$idDashboard = $_POST['id_page'];
		// 06/06/2011 MMT DE 3rd Axis add axe parameter
		$axe = $_POST['axe'];
		
		// Récupère la sélection active (i.e. c'est présente dans la sélection des éléments réseaux)
		$activeSelection = explode($separator, $_POST['current_selection']);

		// Récupère la sélection qui est en session
		// 10/06/2011 MMT DE 3rd Axis uniquement pour axe 1, pas de preferences sur axe 3
		// 27/07/2011 MMT Bz 22896 add 3rd axis preferences session variable
		if($axe != 3){
			$prefSessionVar = &$_SESSION['TA']['network_element_preferences'];
		} else {
			$prefSessionVar = &$_SESSION['TA']['ne_axeN_preferences'];
		}
		$currentSelectionTmp = explode($separator, $prefSessionVar);

		$currentSelection = array();

		// echo $activeNa."\n";
		if ( count($currentSelectionTmp) > 0 )
		{
			// 06/06/2011 MMT DE 3rd Axis. ne requète que si nécéssaire
			$dashModel = new DashboardModel($idDashboard);
			$na2na = $dashModel->getNa2Na($axe);

			foreach ( $currentSelectionTmp as $select )
			{
				if ( empty($select) )
					continue;

				$_ = explode('||', $select);

				// Si le niveau d'agrégation fait parti de la liste des éléments réseaux du dashboard
				if ( array_key_exists($_[0], $na2na) )
				{
					if ( !in_array($_[0], $na2na[$activeNa]) )
					{
						// echo $_[0]." non visible_n";
						// Garde uniquement les éléments qui ne sont pas visible
						$currentSelection[] = $select;
					}
				}
				else
				{
					// On garde l'élément sélectionné qui n'a pas de relation avec le dashboard
					$currentSelection[] = $select;
				}
			}
			$currentSelection = array_merge($currentSelection, $activeSelection);
		}
		else
		{
			$currentSelection = $activeSelection;
		}

		// Ajout les éléments sélectionnées
		// Sauvegarde en session
		// 27/07/2011 MMT Bz 22896 add 3rd axis preferences session variable
		$prefSessionVar = implode($separator, $currentSelection);
	break;

	/*
		23/06/2009 GHX
		Reset de la sélection des éléments réseaux en SESSION
	*/
	case 13 :
		// 06/06/2011 MMT DE 3rd Axis add axe parameter
		// selection des variables de sessions en fonction de l'axe

		$axe = $_GET['axe'];
		/*
		 * 09/06/2011 MMT ajouter le code suivant pour gestion  Preferences 3eme axe:
		$idDashboard = $_GET['id_page'];
		$separator = $_GET['separator'];

		$otherAxis = getAxeNEsFromNEString($idDashboard,$axe,$_SESSION['TA']['network_element_preferences'],$separator,true);

		$_SESSION['TA']['network_element_preferences'] = $otherAxis;
		 *
		 */

		if($axe != 3){
			$_SESSION['TA']['selecteur']['ne_axe1'] = '';
			$_SESSION['TA']['network_element_preferences'] = '';
		} else {
			$_SESSION['TA']['selecteur']['ne_axeN'] = '';
			// 27/07/2011 MMT Bz 22896 add 3rd axis preferences session variable
			$_SESSION['TA']['ne_axeN_preferences'] = '';
		}
		
	break;

	/*
		29/07/2009 - MPR
			Selection des raws/kpis pour le selecteur du GIS
	*/
	case 14 :

		// On fixe la limite du nombre de NA par div
		$MaxElements = 5000;
		$limit = 0;
		// Récupération des valeurs

		$id_prod = isset($_GET['product']) ? $_GET['product'] : '';
		$id_family = isset($_GET['family']) ? $_GET['family'] : '';
		$type = isset($_GET['type']) ? $_GET['type'] : '';
		$reset = $_GET['reset'];

		// Longueur max d'un label
		$labelMaxLength = get_sys_global_parameters('na_label_character_max');

		//suivant le type, on va chercher les raw/kpis
		$gismodel = new GisModel($id_prod, $id_family);
		if ($type == 'raw') {
			$counters = $gismodel->getRaws();
		}
		if ($type == 'kpi') {
			$counters = $gismodel->getKpis();
		}

		foreach($counters as $c_id => $c_value) {
			$array_counters[$type."@".$c_id] = "{$c_value}";
		}

		// On sauvegarde le tableau en session pour l'utiliser avec la recherche
		if(gettype($_SESSION['selecteur']) != 'array') unset($_SESSION['selecteur']);
		$_SESSION['selecteur']['saved_search'] = $array_counters;

		// Désormais, on peut parcourir notre tableau pour générer la chaine HTML de retour
		$html = '';
		// maj 27/08/2009 - MPR : Correction du bug 11248 et 11249 - Si aucun raw/kpi n'est déployé, on ne sélectionne pas le premier raw de la famille
		if( count($array_counters) > 0 ){
			foreach($array_counters as $elem=>$label_elem) {

				$elements = explode("@",$elem);
				//type du compteur (raw ou kpi)
				$type = $elements[0];
				//id du compteur
				$id_elem = $elements[1];

				$label = explode("||", $label_elem);
				$label_db = $label[0];
				$label_display = $label[1];

				//l'id correspond a : type_compteur@id_compteur@label_compteur
				$id = $elem."@".$label_db;

				$html .= "
				<input type='radio' name = 'gis_counters_selection' id='".$id."' value='".$id."' onclick=\"resetNeSelection();saveInNeSelection('".$id."','".$label_db."');\" />
				<label for='".$id."'>".((strlen($label_display) > $labelMaxLength) ? substr($label_display,0,$labelMaxLength).'...' : $label_display)."</label><br />";
				// Test de la limite
				if($limit == $MaxElements) {
					$html .= "<div class='texteRouge' style='padding:5px;'>".__T('U_SELECTEUR_NE_TOO_MANY_ELEMENTS')."</div>";
					break;
				}
				$limit++;
			}
		}
		// Si rien n'a été trouvé, on retourne le message d'erreur correspondant
		if($html == '') $html = __T('SELECTEUR_NO_VALUE_FOUND',$id_prod);
		// Retour du code HTML
		echo $html;
	break;

	case 15 :
	// Génération du header xml
		$headers['Pragma']        = 'no-cache';
		$headers['Expires']       = '0';
		$headers['Last-Modified'] = gmdate("D, d M Y H:i:s") . " GMT";
		$headers['Cache-Control'] = 'no-cache, must-revalidate';
		$headers['Content-type']  = 'application/xml; charset=UTF-8';
		foreach ( $headers as $key => $value ){
			header($key. ':' . $value);
		}
		// Récupération  de la chaîne tapée par l'utilisateur
		$debut = "";
		if (isset($_GET['debut'])) {
		    $debut = utf8_decode($_GET['debut']);
		}

		$MAX_RETURN = 10;
		$found = 0;
		// On recherche les occurences de la chaînes dans le tableau des éléments, sur le code et le label.
		echo "<ul>";
		foreach($_SESSION['selecteur']['saved_search'] as $key => $val) {

		   //l'id correspond a : type_compteur@id_compteur@label_compteur
		   $val_tmp = explode("||",$val);
		   $val_display = $val_tmp[1];
		   $val_search = $val_tmp[0];
		   $id = $key."@".$val_search;

		   if(@preg_match( '/'. strtolower($debut) .'/', strtolower($id) ) || @preg_match( '/'. strtolower($debut) .'/', strtolower($val) )) {

				echo(utf8_encode("<li id='li_$id' id_to_check='$id'>{$val_display}</li>"));
				$found++;
			}
			if($found == $MAX_RETURN)
				break;
        }
		if(!$found)
			echo "<li>".__T('SELECTEUR_NO_RESULT')."</li>";
		echo "</ul>";
	break;

	/*
		15:20 28/07/2009 GHX
			Recharge les éléments réseaux favories de l'utilisateur
	*/
	case 17:
		$_SESSION['TA']['network_element_preferences'] = $_SESSION['network_element_preferences'];

		/*
		 * 09/06/2011 MMT ajouter le code suivant pour gestion  Preferences 3eme axe:
		 *
		$axe = $_GET['axe'];
		$idDashboard = $_GET['id_page'];
		$separator = $_GET['separator'];

		$keepPrefs = getAxeNEsFromNEString($idDashboard,$axe,$_SESSION['network_element_preferences'],$separator);

		$existingOtherAxis = getAxeNEsFromNEString($idDashboard,$axe,$_SESSION['TA']['network_element_preferences'],$separator,true);
		$prefForAxis = getAxeNEsFromNEString($idDashboard,$axe,$_SESSION['network_element_preferences'],$separator);
		if(!empty($existingOtherAxis) && !empty($prefForAxis)){
			$newVal = $existingOtherAxis.$separator.$prefForAxis;
		} else {
			$newVal = $existingOtherAxis.$prefForAxis;
		}
		$_SESSION['TA']['network_element_preferences'] = $newVal;
		*/

	break;
    
        /**
         * Ajout d'un filtre sur un élément
         */
        // 10/06/2013 NSE bz 34249 : filtered label is no more displayed
        case 18:
            $_SESSION['nefiltering'][$_POST['na']][] = $_POST['ne'];
            // 06/06/2013 NSE bz 34042 : pas d'id_prod sur le Gateway, donc pas de Na fils retourné
            // si on est sur un Gateway
            if(empty($id_prod)){
                // on récupère les Na enfant sur tous les produits
                echo implode('|',NaModel::getChildrenNaOnAllProducts($_POST['na']));
            }
            else{
                echo implode('|',NaModel::getChildrenNa($_POST['na'],$id_prod));
            }
        break;
    
        /**
         * Suppression d'un filtre sur un élément
         */
        case 19:
            $neList = $_SESSION['nefiltering'][$_POST['na']];
            foreach($neList as $offset => $ne) {
                if($ne == $_POST['ne']) unset($neList[$offset]);
            }
            $_SESSION['nefiltering'][$_POST['na']] = $neList;

            $stillFiltered = array();
            foreach($_SESSION['nefiltering'] as $na => $neList) {
                if(!empty($neList)) {
                    // 06/06/2013 NSE bz 34042 : pas d'id_prod sur le Gateway, donc pas de Na fils retourné
                    // si on est sur un Gateway
                    if(empty($id_prod)){
                        // on récupère les Na enfant sur tous les produits
                        foreach(NaModel::getChildrenNaOnAllProducts($na) as $child)
                            $stillFiltered[] = $child;
                    }
                    else{
                        foreach(NaModel::getChildrenNa($na,$id_prod) as $child)
                            $stillFiltered[] = $child;
                        }
                }
            }
            
            echo implode('|',array_unique($stillFiltered));
        break;
        
        /**
         * Reset des fitres
         */
        case 20:
            unset($_SESSION['nefiltering']);
        break;
}

/**
 * 
 * @param type $idDashboard
 * @param type $axe
 * @param type $neString
 * @param type $separator
 * @param type $invert
 * @return type
 */
function getAxeNEsFromNEString($idDashboard,$axe,$neString,$separator,$invert=false)
{
    $dashModel = new DashboardModel($idDashboard);
    $allAxeNAs = $dashModel->getNALevels($axe);

    $prefTuples = explode($separator,$neString);
    $ret = array();
    foreach($prefTuples as $tuple){
        $_ = explode('||', $tuple);
        if(($invert && !array_key_exists($_[0],$allAxeNAs))
          || (!$invert && array_key_exists($_[0],$allAxeNAs)))
        {
            $ret[] = $tuple;
        }
    }
    return implode($separator,$ret);
}

/**
 * 14/01/2013 BBX
 * DE Ne Filter : condition de filtrage sur les éléments
 * @param array $filter
 * @param type $na
 * @param type $product
 * @return string
 */
function getNeFilterCondition(array &$filter, $na, $product = null)
{
    $conditionFilter = "";                    
    $children = array();
    foreach($filter as $naParent => $neParents) {
        $childrenNa = NaModel::getChildrenNa($naParent, $product);
        if(in_array($na, $childrenNa)) {
            // 11/06/2013 NSE bz 34237 : filter of same na erased previous one
            $children = array_merge_recursive($children, NeModel::getChildrenFromParents($naParent, $neParents, $product));
        }
    }
    if(!empty($children)) {
        $conditionFilter = " AND eor_id IN ('".implode("','",$children[$na])."') ";
    }
    return $conditionFilter;
}

?>
