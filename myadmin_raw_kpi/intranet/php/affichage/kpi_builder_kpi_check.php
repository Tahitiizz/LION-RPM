<?php
/*
 * 	@cb51000@
 *
 * 	28-06-2010 - Copyright Astellia
 *
 * 	Composant de base version cb_5.1.0.00
 *
 * 	28/06/2010 NSE : Division par zéro - remplacement de l'opérateur / par //
 * 	15/07/2010 NSE : Suppression de l'opérateur //
 *
 */
?>
<?php
/*
 * 	@cb5.0.2.19@
 *
 * 	17:45 08/06/2010 SCT : BZ 15925 => Blocage process intégration suite activation KPI non déployé
 *
 *      maj 04/08/2010 - MPR : Correction du bz 16538 -> Ajout d'un contrôle sur le nombre de caractères (limite = 63)
 *      14/09/2010 NSE bz 17828 : message "Operation canceled" parasite. Ajout d'un exit.
 *      maj 27/09/2010 - MPR : Correction du bz18035 -> Possibilité de générer un kpi contenant un raw déployé mais désactivé
 *      24/11/2010 MMT : Bz 19384 -> Test Raw non activé dans la formule KPI edw_field_name à la place de nms_field_name pour le raw name
 * 		28/01/2015 JLG : Bz 32245 : lorsque l'on ajoute un \ dans le commentaire d'un kpi et que l'on ajoute le kpi : le \ n'est plus doublé
 */
?>
<?php
/*
 * 	@cb40200@
 *
 * 	23/10/2008 - Copyright Acurio
 *
 * 	Composant de base version cb_4.0.2.00
 *
 * 	- modif 23/10/2008 BBX : utilisation de la fonction getClientType pour récupérer le type de l'utilisateur. Le paramètre client_type n'existant plus. BZ 7610
 *
 * 	02/02/2009 GHX
 * 		- suppression de la colonne internal_id [REFONTE CONTEXTE]
 * 		- mise entre cote des valeurs dans les requetes SQL [REFONTE CONTEXTE]
 *
 * 	06/07/2009 BBX :
 * 		- ajout du produit dans les valeurs du formulaire à envoyer dans la fonction ask_for_overwrite. BZ 10389
 * 	12:54 22/12/2009 SCT : transformation de $network par $network1stAxis pour Erlang
 * 	16:44 15/01/2010 SCT : modification de la condition d'exécution de la fonction erlangb
 * 	19/01/2010 NSE bz 13799 : effectuer une vérification sur le champ commentaire (interdire le " mais autoriser ')
 * 	16/03/2010 NSE bz 14254 : contrôle de la formule du kpi y compris si elle contient un appel erlang
 * 	18/03/2010 NSE bz 14800 on supprime les retours à la ligne de la formule des kpi
 * 	09/04/2010 NSE  bz 14256 problème quand la formule contient null pour tch_counter : on caste null en real
 * 	22/04/2010 NSE
 * 		- bz 14713 : regexp trop restrictive modifiée
 * 		- bz 14796 reopen : limitation également dans le cas d'une saisie directe de la fonction erlang dans la zone de formule
 */
?>
<?php
/*
 * 	@cb1300_iu2000b_pour_cb2000b@
 *
 * 	19/07/2006 - Copyright Acurio
 *
 * 	Composant de base version cb_1.3.0.0
 *
 * 	Parser version iu_2.0.0.0
 */
?>
<?php
session_start();
// This file is used to check everything when someone wants to save a new KPI or a modified equation for an existing KPI
/*
  -  maj 14/04/2008 : benjamin : correction de la récupération du type client
  - maj 01 03 2006 christophe : sauvegarde / génération de l'internal_id.
  - maj 23 05 2006 sls : ajout de l'information $client_type (client ou customisateur) lors de la creation d'un kpi
  - maj 23 05 2006 sls : ajout d'un vérouillage : un client ne peux pas modifier un kpi créé par un customisateur
  - 22-08-2006 : modification pour prendre en compte le pourcentage
  - maj 23/04/2007 Gwénaël : modification de la syntaxe wHeN en WHEN sinon impossible de modifier la formule ?!?!
  - maj 10/05/2007 Gwénaël : modification de la requete qui vérifie si la formule du KPI est correcte, suppression des cotes dans le SELECT ?!?!
  - maj 11/05/2007 Gwénaël : modification de la requete de mise à jour du KPI > ajout de la fonction upper dans la condition sur le nom du kpi pour la comparaison
 */
// List of things to be checked:
// The name of the KPI uses only "usual" characters
// The name of the KPI already exists (then, user must confirm that he wants to update or cancel)
// In case it's a new KPI, the field "new_field" must be set to the value "1". It must not be changed otherwise
// Check syntax by executing the KPI_formula as a query
session_start();
include_once dirname(__FILE__) . "/../../../../php/environnement_liens.php";
include_once REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php";
include_once REP_PHYSIQUE_NIVEAU_0 . "class/KpiFormula.class.php";
?>
<html>
    <head>
        <title>KPI</title>
        <script src="<?= NIVEAU_0 ?>fonctions_dreamweaver.js"></script>
        <script src="<?= NIVEAU_0 ?>generic_counters.js"></script>
        <script src="<?= NIVEAU_0 ?>verification_syntaxe_kpi.js"?></script>
    </head>
    <body bgcolor="#EFEFEF">
        <?php

// Returns true if a kpi definition already exists for the name chosen (case insensitive)
        function kpi_name_already_exists($kpi_name, $family, $id_ligne, $database) {
            $kpi_name_lower_case = strtolower($kpi_name);
            $query = "SELECT DISTINCT kpi_name FROM sys_definition_kpi
	WHERE edw_group_table IN
		(SELECT edw_group_table FROM sys_definition_group_table WHERE family='$family' AND visible=1)
	ORDER BY kpi_name";
            $array_lower_case_kpis_names = array();

            foreach ($database->getAll($query) as $current_kpi_definition) {
                $current_kpi_name = $current_kpi_definition["kpi_name"];
                $array_lower_case_kpis_names[] = strtolower($current_kpi_name);
            }

            if (in_array($kpi_name_lower_case, $array_lower_case_kpis_names)) {
                // echo "existe  déjà"; exit;
                return true;
            } else {
                // echo "ok"; exit;
                return false;
            }
        }

// Checks if the name of the KPI uses only "usual" characters or the name contains more than 63 caracters
        function kpi_name_is_accepted($kpi_name) {
            // maj 04/08/2010 - MPR : Correction du bz 16538
            // Ajout d'un contrôle sur le nombre de caractères (limite = 63)
            if (ereg("^[a-zA-Z][a-zA-Z0-9_]*$", $kpi_name) && strlen($kpi_name) < 64) {
                return true;
            } else {
                return false;
            }
        }

// 19/01/2010 NSE bz 13799 : effectuer une vérification sur le champ commentaire (interdire le " mais autoriser ')
// Checks if the comment of the KPI uses only "usual" characters
        function kpi_comment_is_accepted($kpi_comment) {
            if (ereg('^(]|[a-zA-Z0-9 \s\$\!\?\,\'\(\)\:\;\/\&\@\#\%_\'\.\+\*\[\{\}\=\<\>\-])*$', $kpi_comment)) {
                return true;
            } else {
                return false;
            }
        }

// 10:04 18/10/2010 SCT : BZ 18518 => Vérification de la formule KPI avant activation depuis KPI_Builder
// DEBUT BUG 18518
//  + remplacement de code dans la méthode "query_works"
//  + appel de la méthode KpiFormula::checkFormula
//  + suppression de l'utilisation de la méthode "get_real_formula"
        /*
          /// Begin Roya
          function get_real_formula($f='',$arg='is_erlang') {

          $return = true;
          $pos = strpos(strtolower($f), 'erlangb');

          if ($pos === false) $return = false;
          return $return;
          }
          //// End Roya
         */
        function query_works($kpi_formula, $kpi_name, $family, $data_type = '0', $database) {
            $verificationFormule = KpiFormula::checkFormula($database, $kpi_formula, $family, $_POST['product']);
            // On récupère le premier group table de la famille passée en paramètre.
            $query_group_table = "select edw_group_table from sys_definition_group_table where family='$family' and visible=1 limit 1";
            $result = $database->execute($query_group_table);
            $nombre_resultat = $database->getNumRows();
            $result_array = $database->getRow($query_group_table);
            $edw_group_table = $result_array["edw_group_table"];

            //  Possibilité de générer un kpi contenant un raw déployé mais désactivé
            $list_of_raw_tables = KpiFormula::createTableMinFamily($database, $family, $_POST['product']);
            if (!isset($result_raws_disabled[$family])) {
                // Récupere la liste des compteurs déployé mais désactivé (on_off = 0)
                // 24/11/2010 MMT Bz 19384 utilise edw_field_name à la place de nms_field_name pour le raw name
                $check_2 = "
                SELECT attname as id, edw_field_name_label as label
                FROM pg_class c, pg_attribute a, sys_field_reference sfr
                WHERE a.attrelid = c.oid
                AND relname = '{$list_of_raw_tables}'
                AND attname = lower( sfr.edw_field_name ) AND edw_group_table = '{$edw_group_table}'
                AND sfr.on_off = 0;
                ";

                $result_raws_disabled[$family] = $database->getAll($check_2);
            }
            if (count($result_raws_disabled[$family]) > 0) {
                foreach ($result_raws_disabled[$family] as $raw_disable) {
                    // 24/11/2010 MMT Bz 19384 utilise methode plus complexe de recherche de raw dans formule
                    if (isRawUsedInKpiFormula($raw_disable['id'], $kpi_formula)) {
                        return false;
                    }
                }
            }

            return $verificationFormule;
        }

// FIN BUG 18518

        /**
         * 24/11/2010 MMT Bz 19384
         * Return true if the raw name is used in the KPI formula, functions checks for exact string match none case sensitive
         * if the string found is preceeded or postfixed by an anlphanumerical or '_' it will return false
         *
         * @param String $rawName name of the raw counter (col edw_field_name from sys_field_reference)
         * @param String $formula KPI formula
         * @return bool
         */
        function isRawUsedInKpiFormula($rawName, $formula) {
            $noneRawCharRegEx = '[^a-z0-9._]';

            // construit la regEx, test la présence du raw encadré par deux caracteres non accpeté dans les noms des raws
            $regex = '/' . $noneRawCharRegEx . strtolower($rawName) . $noneRawCharRegEx . '/';
            // encapsule la chaine pqr des espaces (car non accepté)  pour que $noneRawCharRegEx soit validé
            // dans le cas ou le nom du raw debute/finit la formule
            $searchContent = ' ' . strtolower($formula) . ' ';
            return preg_match($regex, $searchContent);
        }

        function refuse_save($error_message) {
            if (strlen($error_message) > 0) { // if there is a message, display message
                ?>
                <script language="JavaScript">
                    alert('<?= $error_message ?>');
                </script>
                <?php
            }
            reload_kpi_builder();
        }

        function reload_kpi_builder($alert_msg = "") {
            // exit;
            global $family, $kpi_formula_real, $kpi_name, $id_ligne;
            // 19/01/2010 NSE bz 13799 : global $isKpiCommentAccepted
            global $kpi_label, $kpi_comment, $kpi_pourcentage, $isKpiNameAccepted, $isKpiCommentAccepted;

            $kpi_formula_real = str_replace("::float4", "", $kpi_formula_real);
            $kpi_formula_real = str_replace("\\", "", $kpi_formula_real);

// 19/01/2010 NSE bz 13799 : effectuer une vérification sur le champ commentaire
            ?>
            <script language="JavaScript">
                var msg = "<?= trim($alert_msg) ?>";
                if (msg != "")
                {
                    alert(msg.toString());
                }

                parent.kpi_list.location = 'kpi_builder_kpi_list.php?family=<?= $family ?>&product=<?= $_POST['product'] ?>';
                parent.kpi_builder.location = "kpi_builder_interface.php?generic_counter_numerateur=<?= urlencode($kpi_formula_real) ?>&generic_counter_name=<?= urlencode($kpi_name) ?>&family=<?= urlencode($family) ?>&zone_id_generic_counter=<?= urlencode($id_ligne) ?>&kpi_label=<?= urlencode(stripslashes(($kpi_label))) ?>&kpi_comment=<?= urlencode($kpi_comment) ?>&kpi_pourcentage=<?= urlencode(stripslashes($kpi_pourcentage)) ?>&product=<?= $_POST['product'] ?>&kpinameaccepted=<?= $isKpiNameAccepted ?>&kpicommentaccepted=<?= $isKpiCommentAccepted ?>";
            </script>
            <?php
        }

        function create_kpi($kpi_name, $kpi_formula, $family, $database) {
            global $kpi_comment, $kpi_label, $kpi_pourcentage;

            $kpi_name = strtoupper($kpi_name); // save new kpis in upper case only
            $kpi_type = "float4";
            $numerator_denominator = "total";
            $new_field = "1";
            $new_date = date("Ymd");
            $on_off = "1";

            // insère l'ajout de ::float4 dans le cas de division du type x/y avec x et y
            $pattern = "[0-9]*\.?[0-9]+/[0-9]*[\.]*[0-9]+";
            $kpi_formula = ereg_replace($pattern, "\\0::float4", $kpi_formula);
            /* if(strstr($edw_group_table,'mixed')!==false)        // this line is a little tricky. It just means: "in the case of mixed kpis"
              {
              $edw_group_table='edw_alcatel_0';
              $numerator_denominator="mixed";
              } */
            // On enregistre le kpi pour toutes les groupes de la famille concernée.
            $query_groupe = " select edw_group_table from sys_definition_group_table where visible=1 and family='$family' ";
            // maj 14/04/2008 : benjamin : correction de la récupération du type client
            // on va chercher le type_client du createur du kpi
            $client_type = getClientType($_SESSION['id_user']);

            foreach ($database->getAll($query_groupe) as $result_array_groupe) {
                $edw_group_table = $result_array_groupe["edw_group_table"];
                // 16:08 02/02/2009 GHX
                // Nouveau format pour id_ligne
                $new_id_ligne = generateUniqId('sys_definition_kpi');
                $query_create = "INSERT INTO sys_definition_kpi (id_ligne, kpi_name,kpi_formula,edw_group_table,kpi_type,numerator_denominator,new_field,new_date,on_off,visible,comment,kpi_label,value_type,pourcentage)";
                $query_create .= " VALUES ('$new_id_ligne', '$kpi_name',E'$kpi_formula','$edw_group_table','$kpi_type','$numerator_denominator','$new_field','$new_date','$on_off','1','$kpi_comment','$kpi_label','$client_type','$kpi_pourcentage')";

                $database->execute($query_create);
            }
        }

        function update_kpi($id_ligne, $kpi_formula, $family, $kpi_name, $database) {
            global $kpi_comment, $kpi_label, $kpi_pourcentage;

            // echo "update"; exit;
            // echo "<br> <b>update du kpi</b>";
            $new_date = "'" . date("Ymd") . "'";
            $kpi_name = strtoupper($kpi_name);
            // insère l'ajout de ::float4 dans le cas de division du type x/y avec x et y
            $pattern = "[0-9]*\.?[0-9]+/[0-9]*[\.]*[0-9]+";
            $kpi_formula = ereg_replace($pattern, "\\0::float4", $kpi_formula);
            // Gestion des KPI erlangb
            $kpi_formula = str_replace("\\\\\\", "\\", $kpi_formula);
            // 16:44 15/01/2010 SCT : modification de la condition d'exécution de la fonction erlangb
            //$kpi_formula = str_replace("\'GOS\')","\'GOS\') ELSE 0 END", $kpi_formula);
            //$kpi_formula = str_replace("\'TRAFFIC\')","\'TRAFFIC\') ELSE 0 END", $kpi_formula);
            //$kpi_formula = str_replace("\'CHANNELS\')","\'CHANNELS\') ELSE 0 END", $kpi_formula);
            // On met-à-jour tous les kpi de la famille concernée.
            $query_groupe = " select edw_group_table from sys_definition_group_table where visible=1 and family='$family' ";
            foreach ($database->getAll($query_groupe) as $result_array_groupe) {
                $edw_group_table = $result_array_groupe["edw_group_table"];
                // On fait l'update en fonction du nom du kpi
                // 11/05/2007 GHX : Ajout de la fonction upper sur la condition du kpi_name pour comparer les nom des KPIs
                // 06/07/2010 OJT : Suppression du on_off=1 (on laisse l'état actuel pour un update)
                $query_update = "
			UPDATE sys_definition_kpi
			SET kpi_formula=E'$kpi_formula',pourcentage='$kpi_pourcentage',new_date=$new_date,kpi_label='$kpi_label',comment='$kpi_comment'
			WHERE upper(kpi_name)='$kpi_name' AND edw_group_table='$edw_group_table'; ";
                $database->execute($query_update);
                // 17:45 08/06/2010 SCT : BZ 15925 => Blocage process intégration suite activation KPI non déployé
                // on vérifie que la colonne a bien été déployée, sinon on la déploie
                // + récupération de l'entête de la table de données
                // + récupération de la NA min
                // + récupération de la NA axe3 min
                // + récupération de la TA min
                // + construction de la table de données minimum
                // + vérification de l'existance de la colonne dans la table
                $tempFamilyEnteteTable = GetGTInfoFromFamily($family);
                $tempFamilyNaMin = get_network_aggregation_min_from_family($family);
                $tempFamilyNaAxe3Min = get_network_aggregation_min_axe3_from_family($family);
                $tempFamilyTaMin = get_ta_min();
                $tempTableDonneesMin = $tempFamilyEnteteTable['edw_group_table'] . '_kpi_' . $tempFamilyNaMin . ($tempFamilyNaAxe3Min ? '_' . $tempFamilyNaAxe3Min : '') . '_' . $tempFamilyTaMin;
                $tableauListeColonne = $database->getColumns($tempTableDonneesMin);
                if (!in_array(strtolower($kpi_name), $tableauListeColonne)) {
                    $query_update_bis = "
				UPDATE
					sys_definition_kpi
				SET
					new_field=1
				WHERE
					upper(kpi_name)='$kpi_name' AND edw_group_table='$edw_group_table';";
                    $database->execute($query_update_bis);
                }
            }
            reload_kpi_builder();
        }

        function get_kpi_info($id_ligne, $database) {
            $query_info = "SELECT kpi_name||'='||kpi_formula AS kpi_info FROM sys_definition_kpi WHERE id_ligne='$id_ligne'";
            $php_result = $database->getRow($query_info);
            $kpi_description = $php_result["kpi_info"];
            return $kpi_description;
        }

        // we fetch the kpi_owner ('client' or 'customisateur' ...)
        function get_kpi_owner($id_ligne, $database) {
            $query_info = "SELECT value_type FROM sys_definition_kpi WHERE id_ligne='$id_ligne'";
            $php_result = $database->getRow($query_info);
            return $php_result["value_type"];
        }

        function ask_for_overwrite($decision_for_overwrite, $information, $family) {
            global $kpi_formula_real, $kpi_name, $family, $id_ligne;
            global $kpi_comment, $kpi_label, $kpi_pourcentage;

            if ($decision_for_overwrite == 'yes') {
                return true;
            } elseif ($decision_for_overwrite == 'no') {
                return false;
            } else {
                if ($kpi_pourcentage == 1) {
                    $kpi_pourcentage = 'on';
                } else {
                    $kpi_pourcentage = 0;
                }
                // 14/09/2010 NSE bz 17828 : ajout de id
                ?>
                <form id="formulaire" name="formulaire" method="post" action="<?= $PHP_SELF ?>">
                    <input type="hidden" name="zone_formule_numerateur" value="<?= $kpi_formula_real ?>">
                    <input type="hidden" name="generic_counter" value="<?= $kpi_name ?>">
                    <input type="hidden" name="family_group_table_name" value="<?= $family ?>">
                    <input type="hidden" name="zone_id_generic_counter" value="<?= $_POST["zone_id_generic_counter"] ?>">
                    <input type="hidden" name="label_kpi" value="<?= stripslashes($kpi_label) ?>">
                    <input type="hidden" name="comment_kpi" value="<?= $kpi_comment ?>">
                    <input type="hidden" name="pourcentage" value="<?= $kpi_pourcentage ?>">
                    <?php
                    // 06/07/2009 BBX : ajout du produit dans les valeurs du formulaire à envoyer. BZ 10389
                    ?>
                    <input type="hidden" name="product" value="<?= $_POST['product'] ?>">
                </form>

                <script language="JavaScript">
                    var message = "This KPI already exists. Do you want to replace it?";
                    var choice = confirm(message.toString());
                    if (choice)
                    {
                        formulaire.action = '?overwrite=yes&family=<?= $family ?>';
                    } else {
                        formulaire.action = '?overwrite=no&family=<?= $family ?>';
                    }
                    formulaire.submit();
                </script>
                <?php
                // 14/09/2010 NSE bz 17828 : on arrête l'exécution car on vient de valider un formulaire, donc de relancer le traitement de la page.
                exit;
            }
        }

        function get_id_ligne($kpi_name, $id_ligne = null, $family, $database) {
            if ($id_ligne !== null) {
                // $query="SELECT kpi_name FROM sys_definition_kpi WHERE edw_group_table='$edw_group_table' and id_ligne=$id_ligne";
                // 15:44 02/02/2009 GHX
                // Mise entre cote de la valeur id_ligne
                $query = "SELECT kpi_name FROM sys_definition_kpi WHERE id_ligne='$id_ligne'";
                @$php_result = $database->getRow($query);
                $found_kpi_name = $php_result["kpi_name"];
                if (strtolower($found_kpi_name) == strtolower($kpi_name)) {
                    return $id_ligne;
                }
            }
            // if id_ligne==Null or if previous search failed
            $query = "
                SELECT id_ligne, kpi_name FROM sys_definition_kpi
				WHERE edw_group_table in (  select edw_group_table from sys_definition_group_table where family='$family' and visible=1 )
                ORDER BY kpi_name
                ";
            foreach ($database->getAll($query) as $php_result) {
                $found_kpi_name = $php_result["kpi_name"];
                if (strtolower($found_kpi_name) == strtolower($kpi_name)) {
                    $id_ligne = $php_result["id_ligne"];
                    return $id_ligne;
                }
            }
            // if we reach this part, a problem has occured (kpi_name found but no match)
        }

// - - - - - - - - - - - - - - - - - - - -
// Main part
// - - - - - - - - - - - - - - - - - - - -
// Connexion à la base produit
        $database = DataBase::getConnection($_POST['product']);

// 22/07/2009 BBX : on stock ici le statut de l'acceptation du nom. BZ 10516
        $isKpiNameAccepted = '1';
// 19/01/2010 NSE bz 13799 : initialisation
        $isKpiCommentAccepted = '1';

        $kpi_formula = $_POST["zone_formule_numerateur"];
        $kpi_name = $_POST["generic_counter"];
        $family = $_POST["family_group_table_name"];
        $id_ligne = $_POST["zone_id_generic_counter"];
        $kpi_label = $_POST["label_kpi"];
        $kpi_comment = stripslashes($_POST["comment_kpi"]);
        $kpi_pourcentage = $_POST["pourcentage"];
// 18/03/2010 NSE bz 14800 on supprime les retours à la ligne de la formule des kpi
        $kpi_formula = str_replace(array("\n", "\r"), "", $kpi_formula);
        $kpi_formula_real = $kpi_formula; //dans le cas d'un pourcentage on met un 'CASE WHEN' donc lorsqu'on relaod la page il ne faut pas que le 'CASE WHEN' apparaisse
// si la formule contient ZERO au début, on supprime ZERO et on laisse les parenthèses ouvrantes et fermantes car cela n'a pas d'impact
// modif 23/04/2007 Gwnénaël
// wHeN => WHEN
// maj 25/11/2009 MPR : On remplace les formules affichées par les formules réelles pour erlangb
        if (strripos($kpi_formula, "erlangb") !== false) {

            $net_min = get_network_aggregation_min_from_family($family, $_POST['product']);
            // 12:54 22/12/2009 SCT : transformation de $network par $network1stAxis
            // 16:44 15/01/2010 SCT : modification de la condition d'exécution de la fonction erlangb
            $kpi_formula = str_replace("erlangb(", "erlangb(\$network1stAxis,\'\$network1stAxis\',\'$net_min\',", $kpi_formula);
            //$kpi_formula = str_replace("\'GOS\')","\'GOS\') ELSE 0 END", $kpi_formula);
            //$kpi_formula = str_replace("\'TRAFFIC\')","\'TRAFFIC\') ELSE 0 END", $kpi_formula);
            //$kpi_formula = str_replace("\'CHANNELS\')","\'CHANNELS\') ELSE 0 END", $kpi_formula);
        }

        if (substr($kpi_formula, 0, 4) == 'zero') {
            if (strstr($kpi_formula, "CASE WHEN")) { // IN3305: 22/08/2017 - si la requête contient déjà un CASE WHEN c'est qu'elle est customisée
                $kpi_formula = substr($kpi_formula, 4);
            } else {
                $kpi_formula = 'CASE WHEN ' . substr($kpi_formula, 4) . '<0 THEN 0 ELSE ' . substr($kpi_formula, 4) . ' END';
            }
        }
        if ($kpi_pourcentage == 'on') {
            $kpi_pourcentage = 1;
            if (strstr($kpi_formula, "CASE WHEN")) { // IN3305: 22/08/2017 - si la requête contient déjà un CASE WHEN c'est qu'elle est customisée
                $kpi_formula = $kpi_formula;
            } else {
                $kpi_formula = 'CASE WHEN ' . $kpi_formula . '>100 THEN 100 ELSE ' . $kpi_formula . ' END';
            }
        } else {
            $kpi_pourcentage = 0;
        }

        $decision_for_overwrite = (isset($overwrite) && !empty($overwrite)) ? $overwrite : 'not decided';
// $family = $_GET["family"];
// echo "Formule : ".$kpi_formula." <br>nom : ".$kpi_name."<br> famille: ".$family."<br> id ligne : ".$id_ligne;
        if (kpi_name_is_accepted($kpi_name)) {
            // echo "dans kpi_name accepted";
            // the name must be validated in order to avoid special characters
            // 19/01/2010 NSE bz 13799
            // le commentaire est soumis à validation
            if (kpi_comment_is_accepted($kpi_comment)) {

                if (query_works($kpi_formula, $kpi_name, $family, '0', $database)) {
                    if (kpi_name_already_exists($kpi_name, $family, $id_ligne, $database)) {
                        // if a KPI already exists with the same name (we compare on lower case because fields names of the database take only lower case names)
                        $id_ligne = get_id_ligne($kpi_name, $id_ligne, $family, $database); // we keep it if already set; we get it from the database if not
                        $information = get_kpi_info($id_ligne, $database); // description of the existing KPI with the same name
                        $kpi_owner = get_kpi_owner($id_ligne, $database); // we get the kpi owner ('client' or 'customisateur' or '')
                        // modif 23/10/2008 BBX : utilisation de la fonction getClientType pour récupérer le type de l'utilisateur. Le paramètre client_type n'existant plus. BZ 7610
                        $client_type = getClientType($_SESSION['id_user']); //get_sys_global_parameters('client_type');
                        if (($kpi_owner != 'client') and ( $client_type == 'client')) {
                            // client cannot overwrite the kpi
                            reload_kpi_builder("This KPI cannot be modified.");
                        } else {
                            // user can overwrite the kpi
                            // 22/04/2010 NSE bz 14796 reopen
                            if (KpiFormula::isFormulaUsingErlangB($kpi_formula) && strripos($information, "erlangb") == false) {
                                // si la nouvelle formule contient un appel erlang et que l'ancienne n'en contenait pas, on vérifie si la limite n'est pas atteinte
                                if (KpiFormula::getNbKpiUsingErlangB($database) < get_sys_global_parameters('max_kpi_using_erlang', 1, $_POST['product'])) {
                                    $user_wants_to_overwrite = ask_for_overwrite($decision_for_overwrite, $information, $family); // user must agree or refuse to overwrite the existing KPI
                                    // user wants to overwrite existing KPI with the same name
                                    if ($user_wants_to_overwrite) {
                                        // remplace pour être certain qu'on va pas avoir 2 fois ::float4 car apparemment le script passe 2 fois dans cette boucle
                                        // $kpi_formula=str_replace("::float4","",$kpi_formula);
                                        //update_kpi($id_ligne, $kpi_formula, $family, $kpi_name, $database);
                                        reload_kpi_builder($kpi_formula);
                                    } else {
                                        // user doesn't want to overwrite existing KPI with the same name
                                        reload_kpi_builder("Operation canceled.");
                                    }
                                } else {
                                    // Action refusée, on affiche un erreur
                                    reload_kpi_builder('Error in the formula. The maximum number of Kpi using the erlang function in their formula has been reached.');
                                }
                            } else {
                                // pas de problème avec erlang
                                $user_wants_to_overwrite = ask_for_overwrite($decision_for_overwrite, $information, $family); // user must agree or refuse to overwrite the existing KPI
                                // user wants to overwrite existing KPI with the same name
                                if ($user_wants_to_overwrite) {
                                    // remplace pour être certain qu'on va pas avoir 2 fois ::float4 car apparemment le script passe 2 fois dans cette boucle
                                    // $kpi_formula=str_replace("::float4","",$kpi_formula);
                                    update_kpi($id_ligne, $kpi_formula, $family, $kpi_name, $database);
                                } else {
                                    // user doesn't want to overwrite existing KPI with the same name
                                    reload_kpi_builder("Operation canceled.");
                                }
                            }
                        }
                    } else {
                        // KPI name doesn't exist yet
                        // Vérifier que le nombre max de KPI n'est pas atteint (bz16401)
                        $maxKpi = intval(get_sys_global_parameters('maximum_mapped_counters', 1570, $_GET['product']));
                        $nbKpi = KpiModel::getNbActiveKpi($database, $family);
                        if ($nbKpi < $maxKpi) {
                            // 22/04/2010 NSE bz 14796 reopen : limitation du nombre de kpi utilisant la fonction erlang dans le cas d'une saisie directe de la fonction erlang
                            if (KpiFormula::isFormulaUsingErlangB($kpi_formula)) {
                                if (KpiFormula::getNbKpiUsingErlangB($database) < get_sys_global_parameters('max_kpi_using_erlang', 1, $_POST['product'])) {
                                    create_kpi($kpi_name, $kpi_formula, $family, $database);
                                    // include_once($repertoire_physique_niveau0 . "scripts/edw_clean_structure_on_the_fly.php");
                                    reload_kpi_builder();
                                } else {
                                    // action refusée
                                    reload_kpi_builder("Error in the formula. The maximum number of Kpi using the erlang function in their formula has been reached.");
                                }
                            } else {
                                create_kpi($kpi_name, $kpi_formula, $family, $database);
                                reload_kpi_builder();
                            }
                        } else {
                            reload_kpi_builder(__T('A_E_CONTEXT_MOUNT_LIMIT_NB_COUNTER_EXCEEDED', $maxKpi, 'kpi', $family, $nbKpi));
                        }
                    }
                } else {
                    // if query doesn't work
                    $error_message = "Error in the formula. Please check it again:" . $kpi_formula;
                    reload_kpi_builder($error_message);
                }
            }
            // 19/01/2010 NSE bz 13799
            else {
                // if kpi_comment is not accepted
                $error_message = "KPI comment not accepted.";
                $isKpiCommentAccepted = '0';
                reload_kpi_builder($error_message);
            }
        } else {
            // Définition du message d'erreur
            $error_message = "KPI name not accepted (only alphanumeric characters).";
            if (is_numeric(substr($kpi_name, 0, 1))) {
                $error_message = "KPI name can not start with numeric character.";
            }
            $isKpiNameAccepted = '0';
            reload_kpi_builder($error_message);
        }
        ?>
    </body>
</html>
