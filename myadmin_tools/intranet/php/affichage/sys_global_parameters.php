<?php
/*
* @cb531@
* 
* 26/03/2013 NSE : Alarm Email subject format
* 
*/
?><?php
/*
 * 	fichier qui affiche la liste des param?tres de l'application d'un produit
 *
 *
 * 	12/10/2012 ACS BZ 29685 Blank content after saving last tab
 *
 */
?>
<?php
/*
 * @cb53000@
 * 
 * 14/09/2012 ACS DE Make it possible to configure the maximum number of displayed elements
 * 
 * 
 * 	@cb41000@
 * Permet de configurer la table sys_global_parameters
 * NB :
 * les lignes dont le champ configure = 1 sont configurables
 * les autres lignes sont ignor?es.
 *
 * 13/12/2012 BBX, BZ 30841 : r?organisation du script
 * 27/03/2017 : [AO-TA] formatage des nombres dans les graphs t&a Requirement [RQ:4893] 
 *
 */
session_start();
include_once(dirname(__FILE__) . "/../../../../php/environnement_liens.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/select_family.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/DataBaseConnection.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "reliability/class/SA_Activation.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "intranet_top.php");

// maj 14/01/2009 - MPR : S?lection du produit
if (!isset($_GET["product"]) and ! isset($_POST['product'])) {
    // 18/04/2011 OJT : Appel sp?cifique pour l'affichage du Produit Blanc
    $select_family = new select_family($_SERVER['PHP_SELF'], $_SERVER['argv'][0], 'Setup Global Parameters', false, '', 2, true);
    exit;
}

// 14/01/2009 MPR : R?cup?ration de l'id du produit
// 18/04/2011 OJT : Mise en commun du code GET et POST
$id_prod = '';
if (isset($_GET["product"])) {
    $id_prod = $_GET["product"];
} else if (isset($_POST["product"])) {
    $id_prod = $_POST["product"];
}

if ($id_prod != '') {
    $infos_prod = getProductInformations($id_prod);
    // 22/07/2009 BBX : affichage changement de produit uniquement si plus d'un produit. BZ 10642
    if (count(ProductModel::getActiveProducts()) > 1) {
        // maj 15/01/2009 - MPR : Ajout d'un bouton pour changer de produit
        $link = '<div class="texteGrisBold">Product ' . $infos_prod[$id_prod]['sdp_label'] . "&nbsp;&nbsp;";
        $link .= '<a href="' . NIVEAU_0 . 'myadmin_tools/intranet/php/affichage/sys_global_parameters.php">';
        $link .= '<img src="' . NIVEAU_0 . 'images/icones/change.gif" onMouseOver="popalt(\'Change Product\');" onMouseOut=\'kill()\' border="0"/></a></div>';
    }
}

// Connecting product Database
$db = Database::getConnection($id_prod);

/**
 * Fetches exisintg categories
 * @param type $pId
 * @return array
 */
function getCategories($pId = 0) {
    $db = Database::getConnection($pId);
    $query = "SELECT DISTINCT id_category, label_category 
        FROM sys_global_parameters_categories
        ORDER BY id_category";
    $result = $db->execute($query);

    while ($row = $db->getQueryResults($result, 1)) {
        $categories[$row['id_category']] = $row['label_category'];
    }
    return $categories;
}

/**
 * Fetches all parameters of a product
 * @param type $pId
 * @return array
 */
function getParameterMatrix($pId = 0) {
    // Fetching parameters
    $matrix = array();
    $db = Database::getConnection($pId);
    // 19/07/2013 GFS - Bug 30841 - [SUP][TA GSM][AVP 27215][ZAIN KSA] : Parameter "First Day of the week" define on TA master is not apply on slave
    // Suppression du filtre bp_visible = 1 dans le cas o? le produit est une gateway
    $query = "SELECT parameters, value, label, comment, client_type, category, bp_visible
    FROM sys_global_parameters
    WHERE configure = 1
    AND category IS NOT NULL
    $conditionBp
    ORDER BY category, order_parameter, label";
    $result = $db->execute($query);
    while ($row = $db->getQueryResults($result, 1)) {
        $matrix[$row['parameters']]['value'] = $row['value'];
        $matrix[$row['parameters']]['label'] = $row['label'];
        $matrix[$row['parameters']]['comment'] = $row['comment'];
        $matrix[$row['parameters']]['category'] = $row['category'];
        $matrix[$row['parameters']]['client_type'] = $row['client_type'];
        // 19/07/2013 GFS - Bug 30841 - [SUP][TA GSM][AVP 27215][ZAIN KSA] : Parameter "First Day of the week" define on TA master is not apply on slave
        $matrix[$row['parameters']]['bp_visible'] = $row['bp_visible'];
    }
    return $matrix;
}

// Fetching categories
$IdMaster = ProductModel::getIdMaster();
$categories = getCategories($IdMaster);

/*
 * RECORDING MANAGEMENT
 */
$category_active = 0;
// On met-?-jour les param?tres
if (isset($_POST["product"])) {
    // on parcours tous les param?tres.
    $query = " select p.*,label_category from sys_global_parameters p,sys_global_parameters_categories c where id_category = category AND configure=1 ";
    $resultat = $db->getAll($query);
    $nb = count($resultat);
    foreach ($resultat as $row) {
        // DE SMS, gestion sp?cifique pour le mot de passe
        if ($row["parameters"] == 'smsc_password') {
            $row['value'] = base64_decode($row['value']);
        }
        // 21/07/2011 OJT : Ajout du stripslashes (?vite de considerer comme nouveaux tous les champs ayant des ")
        if (isset($_POST[$row["parameters"]]) && isset($_POST[$row["parameters"] . "_value"]) && $row['value'] != stripslashes($_POST[$row["parameters"] . "_value"])) {
            // Encodage du mot de passe avant sauvegarde
            if ($row["parameters"] == 'smsc_password') {
                $_POST[$row["parameters"] . "_value"] = base64_encode($_POST[$row["parameters"] . "_value"]);
            }
            $query_update = "UPDATE sys_global_parameters SET value='{$_POST[$row["parameters"] . "_value"]}'
                                WHERE parameters='{$_POST[$row["parameters"]]}'";
            $db->execute($query_update);

            // On r?cup?re la cat?gorie du param?tre modifi? pour conserver l'onglet en cours
            //Bug 34830 - [QAL][CB531] : After saving global parameter, tab is void
            //$catFlipValues   = array_flip( array_values( $categories ) );
            //$category_active = $catFlipValues[$row["label_category"]];
        }
        if (isset($_POST[$row["parameters"]]) && isset($_POST[$row["parameters"] . "_value"])) {
            $categorie_courante = $row["label_category"];
        }
    }

    // maj 28/05/2010 MPR :Activation de Source Availability
    if ($_POST["activation_source_availability_value"] == 1) {
        $sa_activation = new SA_Activation($_POST["product"]);
        $sa_activation->activation();
    }
}

// Fetching master parameters
$masterMatrix = getParameterMatrix($IdMaster);

// Fetching parameters on local product
$localMatrix = $masterMatrix;
if ($id_prod != $IdMaster) {
    $localMatrix = getParameterMatrix($id_prod);
    foreach ($localMatrix as $parameters => $row) {
        if (!empty($masterMatrix[$parameters])) {
            $localMatrix[$parameters]['category'] = $masterMatrix[$parameters]['category'];
        }
    }
}

// Resorting matrix by category
$finalMatrix = array();
foreach ($localMatrix as $parameters => $row) {
    $finalMatrix[$row['category']][$parameters]['value'] = $row['value'];
    $finalMatrix[$row['category']][$parameters]['label'] = $row['label'];
    $finalMatrix[$row['category']][$parameters]['comment'] = $row['comment'];
    $finalMatrix[$row['category']][$parameters]['client_type'] = $row['client_type'];
    // 19/07/2013 GFS - Bug 30841 - [SUP][TA GSM][AVP 27215][ZAIN KSA] : Parameter "First Day of the week" define on TA master is not apply on slave
    $finalMatrix[$row['category']][$parameters]['bp_visible'] = $row['bp_visible'];
}
?>
<script src="<?= NIVEAU_0 ?>js/fenetres_volantes.js"></script>
<?php // On pr?pare les onglets ?>
<link rel="stylesheet" href="<?= NIVEAU_0 ?>css/tab-view.css" type="text/css" media="screen">
<script type="text/javascript" src="<?= NIVEAU_0 ?>js/tab-view.js"></script>
<script type="text/javascript" src="<?= NIVEAU_0 ?>js/tab-view-ajax.js"></script>

<script>
    var strictDocType = false;

    function go_delete(url) {
        if (confirm('Do you want to delete this parameter ?')) {
            window.location = url;
        }
    }
    function changeBackgroundColor(color, object) {
        document.getElementById(object).style.background = color;
    }

    /**
     * Fonction appell?e lors d'un changement de valeur d'un param?tre
     * 18/07/2011 OJT : DE SMS
     * 27/09/2011 OJT : bz29943, utilisation de RegExp pour le remplacement des patternes
     *
     * @param inputElt Element HTML <input>
     */
    function parameterValueChange(inputElt)
    {
        var reg = new RegExp("_value$", "g");
        var eltId = inputElt.id.replace(reg, '');
        switch (inputElt.id)
        {
            case 'alarm_sms_format_value' :
                if ($(eltId + '_info') != null)
                {
                    var tooltip = inputElt.value;
                    var date = new Date();
                    tooltip = tooltip.replace(new RegExp('\\[NB_ALA\\]', 'g'), '10');
                    tooltip = tooltip.replace(new RegExp('\\[NB_CRI\\]', 'g'), '2');
                    tooltip = tooltip.replace(new RegExp('\\[NB_MAJ\\]', 'g'), '3');
                    tooltip = tooltip.replace(new RegExp('\\[NB_MIN\\]', 'g'), '5');
                    tooltip = tooltip.replace(new RegExp('\\[APP_NAME\\]', 'g'), 'My application');
                    tooltip = tooltip.replace(new RegExp('\\[ALA_TYPE\\]', 'g'), 'dynamic');
                    tooltip = tooltip.replace(new RegExp('\\[ALA_NAME\\]', 'g'), 'No Traffic');
                    tooltip = tooltip.replace(new RegExp('\\[DATE\\]', 'g'), '' + date.getFullYear() + (date.getMonth() + 1) + date.getDate());
                    tooltip = tooltip.replace(new RegExp('\\[IP\\]', 'g'), '192.168.1.10');
                    tooltip = "<i>Example: </i>" + tooltip;
                    $(eltId + '_info').onmouseover = function () {
                        popalt(tooltip);
                    }
                }
                break;

                // 26/03/2013 NSE : Alarm Email subject format
                // mise ? jour du tooltip en fonction du format
            case 'alarm_email_subject_format_value' :
                if ($(eltId + '_info') != null)
                {
                    var tooltip = inputElt.value;
                    var date = new Date();
                    tooltip = tooltip.replace(new RegExp('\\[NB_ALA\\]', 'g'), '10');
                    tooltip = tooltip.replace(new RegExp('\\[NB_CRI\\]', 'g'), '2');
                    tooltip = tooltip.replace(new RegExp('\\[NB_MAJ\\]', 'g'), '3');
                    tooltip = tooltip.replace(new RegExp('\\[NB_MIN\\]', 'g'), '5');
                    tooltip = tooltip.replace(new RegExp('\\[APP_NAME\\]', 'g'), 'My T&A');
                    tooltip = tooltip.replace(new RegExp('\\[DATE\\]', 'g'), '' + date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate() + '-' + date.getHours() + '-00');
                    tooltip = tooltip.replace(new RegExp('\\[IP\\]', 'g'), '192.168.35.179');
                    tooltip = tooltip.replace(new RegExp('<', 'g'), '&lt;');
                    tooltip = "Allow to configure the subject of the email sent when an alarm is triggered.<br><i>Example: </i>" + tooltip;
                    $(eltId + '_info').onmouseover = function () {
                        popalt(tooltip);
                    }
                }
                break;

            default:
                // Pour tous les autres param?tres, on ne fait rien
                break;
        }
    }
</script>

<div align='center'><img src="<?= NIVEAU_0 ?>images/titres/sys_global_parameters.gif"/></div>

<br/>
<div align="center" style="margin-bottom:'10px'">
    <?php
    echo $link;
    ?>
    <div id="parameters_tab_view">

        <?php
// 12/10/2012 ACS BZ 29685 Blank content after saving last tab
        $cats = ''; // Liste des cat?gories disponibles
        foreach ($finalMatrix as $category => $parameters) {
            //Bug 34830 - [QAL][CB531] : After saving global parameter, tab is void
            if (isset($_POST["product"]))
                $list_categories_active[] = $category;

            // La cat?gorie Global Parameters (4) correspond aux param?tres globaux du produit master
            if (($id_prod != $IdMaster) && ($category == 4))
                continue;
            // La cat?gorie poss?de des param?tres, on l'ajoute ? la liste
            $cats .= $categories[$category] . ',';
            ?>
            <div class="dhtmlgoodies_aTab" >
                <br/>
                <form name="sgp_form<?= "_$category" ?>" method="POST" action="sys_global_parameters.php">

                    <!-- maj 14/01/2009 - MPR : On enregistre le produit en cours -->
                    <input type="hidden" name="product" value="<?= $id_prod ?>" />
                    <input type="hidden" name="category" value="<?= $categories[$category] ?>" />
                    <fieldset>
                        <legend align="left" class="texteGrisBold">&nbsp;<img src="<?= NIVEAU_0 ?>images/icones/small_puce_fieldset.gif"/>&nbsp;Parameters list&nbsp;</legend>
                        <table cellpadding="1" cellspacing="0" border="0">
                            <tr>
                                <td class="texteGrisBold" align="center">Parameter name</td>
                                <td class="texteGrisBold" align="center">Parameter value</td>
                                <td>&nbsp;</td>
                            </tr>
                            <?php
                            $j = 0;
                            foreach ($parameters as $parameter => $row) {
                                // 19/07/2013 GFS - Bug 30841 - [SUP][TA GSM][AVP 27215][ZAIN KSA] : Parameter "First Day of the week" define on TA master is not apply on slave
                                if (($id_prod != $IdMaster) && ($category == 4) && ($row['bp_visible'] == '0'))
                                    continue;
                                // 18/07/2011 OJT : DE SMS, gestion de plusieurs type d'input
                                $inputType = 'text';

                                if ($parameter === 'smsc_password') {
                                    $inputType = 'password';
                                    $row["value"] = base64_decode($row["value"]);
                                }


                                // 14/09/2012 ACS DE Make it possible to configure the maximum number of displayed elements
                                else if ($param['parameters'] == "max_topover_ot") {
                                    $max_topover_ot_value = $param['value'];
                                }

                                // maj 14/04/2008 Benjamin : ajout d'une couleur de fond modifi?e une ligne sur 2
                                $style_row = ($j % 2 == 0) ? "bgcolor=#DDDDDD" : "bgcolor=#ffffff";
                                // $row = pg_fetch_array($resultat, $i);
                                $paramLabel = ($row["label"]) ? $row["label"] : $parameter;
                                // si l'utilisateur est en mode client et que le param?tre
                                // courant ne lui est pas accessible, il apparait en gris?.
                                // modif 09:17 25/01/2008 Gw?na?l
                                // modif pour la r?cup?ration du param?tre client_type
                                $disabled = ((getClientType($_SESSION['id_user']) == "client") and ( $row['client_type'] != 'client')) ? " disabled" : "";

                                // modif 25/03/2008 GHX
                                // Si Corporate est ? 1 il est impossible de le d?sactiver
                                if ($parameter == 'corporate' && $row["value"] == '1')
                                    $disabled = " disabled";

                                if ($parameter === 'thousand_separator')
                                    $paramLabel = "Graph Thousand separator";
                                // Au survol, affiche une bulle contenant un commentaire
                                $bulleComment = "";
                                if ($row["comment"] != '') {
                                    $comment = addslashes(preg_replace('@"@', "&quot;", $row['comment']));
                                    $bulleComment = "onMouseOver=\"popalt('$comment'); style.cursor='pointer';\" onMouseOut='kill()'";
                                }
                                // maj 14/04/2008 : Benjamin : modification de l'aspect des labels
                                ?>
                                <tr width="80%" <?= $style_row ?>>
                                    <td>
                                        <input type="hidden" name="<?= $parameter ?>" value="<?= $parameter ?>" />
                                        <p class="texteGris">
                                            <?= $paramLabel ?>
                                            <?php
                                            if ($parameter == 'alarm_sms_format') {
                                                ?>
                                                <a href="#" onclick="Effect.toggle('helpSMSFormat', 'slide');" onmouseover="popalt('<?= __T('A_PROFILE_MANAGEMENT_HIDE_DISPLAY_HELP') ?>')">
                                                    <img src="<?= NIVEAU_0 ?>images/icones/help.png" border="0" />
                                                </a>
                                                <?php
                                            }
                                            // 26/03/2013 NSE : Alarm Email subject format
                                            // affichage de l'ic?ne d'aide
                                            elseif ($parameter == 'alarm_email_subject_format') {
                                                ?>
                                                <a href="#" onclick="Effect.toggle('helpAlarmEmailFormat', 'slide');" onmouseover="popalt('<?= __T('A_PROFILE_MANAGEMENT_HIDE_DISPLAY_HELP') ?>')">
                                                    <img src="<?= NIVEAU_0 ?>images/icones/help.png" border="0" />
                                                </a>
                                            <?php } ?>
                                        </p>
                                    </td>
                                    <td>
                                        <!-- 19/07/2011 OJT : Ajout du htmlentities -->
                                        <?php if ($parameter === 'thousand_separator') { ?>
                                            <select name="<?= $parameter . "_value" ?>" id="<?= $parameter . "_value" ?>">
                                                <option value="NONE" <?= ($row["value"] == 'NONE') ? "selected" : "" ?>>NONE</option>
                                                <option value="FR" <?= ($row["value"] == 'FR') ? "selected" : "" ?>>FR</option>
                                                <option value="EN" <?= ($row["value"] == 'EN' || $row["value"] == '') ? "selected" : "" ?>>EN</option>
                                            </select>

                                        <?php } else { ?>
                                            <input
                                                size="45%"
                                                type="<?= $inputType ?>"
                                                name="<?= $parameter . "_value" ?>"
                                                id="<?= $parameter . "_value" ?>"
                                                value="<?= htmlentities($row["value"]) ?>"
                                                class="zoneTexteStyleXP"
                                                onclick="changeBackgroundColor('#f7931e', '<?= $parameter . "_value" ?>')"
                                                onfocus="changeBackgroundColor('#f7931e', '<?= $parameter . "_value" ?>')"<?= $disabled ?>
                                                onkeyup="parameterValueChange(this);"
                                                />
                                            <?php } ?>
                                    </td>
                                    <!--  on vire le drop, virer les commentaires pr le remettre -->
                                    <!--
                                    <td><img src="<?//=NIVEAU_0?>images/icones/drop.gif" border="0" onMouseOver="popalt('Delete parameters');style.cursor='help';" onMouseOut='kill()' onclick="go_delete('sys_global_parameters.php?action=delete&oid=<?//=$param["oid"]?>')" /></td>
                                    -->
                                    <?
                                    if ($bulleComment)
                                    echo "<td><img id='{$parameter}_info' src='".NIVEAU_0."images/icones/cercle_info.gif' $bulleComment></td>";
                                    // maj 14/04/2008 : Benjamin : ajout d'une case vide dans le cas d'abscence d'info bulle
                                    else echo "<td>&nbsp;</td>";
                                    ?>
                                </tr>
                                <?php
                                // 19/07/2011 OJT : Gestion de l'aide pour le format SMS
                                if ($parameter == 'alarm_sms_format') {
                                    ?>
                                    <tr width="80%" <?= $style_row ?>>
                                        <td align="center" colspan=3>
                                            <div id="helpSMSFormat" class="infoBox" style="display:none;">
                                                <div>
                                                    You can create your own SMS message by using several predefined patterns:
                                                    <ul class="infoBoxList">
                                                        <li><b>[NB_ALA]</b>: total number of alarms (minor, major and critical)</li>
                                                        <li><b>[NB_CRI]</b>: number of critical alarms</li>
                                                        <li><b>[NB_MAJ]</b>: number of major alarms</li>
                                                        <li><b>[NB_MIN]</b>: number of minor alarms</li>
                                                        <li><b>[APP_NAME]</b>: application name</li>
                                                        <li><b>[ALA_TYPE]</b>: alarm type (static or dynamic)</li>
                                                        <li><b>[ALA_NAME]</b>: alarm name</li>
                                                        <li><b>[DATE]</b>: alarm date</li>
                                                        <li><b>[IP]</b>: server IP address</li>
                                                    </ul>
                                                    <br />
                                                    Default pattern is: <b>[ALA_NAME]</b> has been triggered <b>[NB_ALA]</b> times on <b>[APP_NAME]</b> (<b>[IP]</b>)
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                // 26/03/2013 NSE : Alarm Email subject format
                                // Gestion de l'aide pour le format du sujet des mails d'alarme
                                elseif ($parameter == 'alarm_email_subject_format') {
                                    ?>
                                    <tr width="80%" <?= $style_row ?>>
                                        <td align="center" colspan=3>
                                            <div id="helpAlarmEmailFormat" class="infoBox" style="display:none;">
                                                <div>
                                                    You can create your own Alarm e-mail subject by using several predefined patterns:
                                                    <ul class="infoBoxList">
                                                        <li><b>[NB_ALA]</b>: total number of alarms (minor, major and critical)</li>
                                                        <li><b>[NB_CRI]</b>: number of critical alarms</li>
                                                        <li><b>[NB_MAJ]</b>: number of major alarms</li>
                                                        <li><b>[NB_MIN]</b>: number of minor alarms</li>
                                                        <li><b>[APP_NAME]</b>: application name</li>
                                                        <li><b>[DATE]</b>: alarm date</li>
                                                        <li><b>[IP]</b>: server IP address</li>
                                                    </ul>
                                                    <br />
                                                    Default pattern is: <b>[APP_NAME]</b> Alarm (<b>[NB_ALA]</b> results) <b>[DATE]</b>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }

                                $j++;
                            }
                            ?>
                            <tr>
                                <td></td>
                            </tr>
                            <tr>
                                <td align="center" colspan=3>
                                    <input type="button" class="bouton" name="save_parameters" value="Save" onclick="javascript:checkForm('sgp_form<?= "_$category" ?>');" />
                                    <!-- 21/07/2011 OJT : Ajout du bouton de test SMS-C -->
                                    <?php
                                    // 15/05/2014 NSE bz 41686 : renommage de la variable pour affichage du bouton de test du SMS-C
                                    if ($category == 5) {
                                        ?>
                                        <input type="button" class="bouton" id="checkSMSCButton"
                                               name="checkSMSCButton" value="Check SMS-C connection"
                                               onclick="checkSMSCConnection();"
                                               style="<?= $testSMSCButtonStyle; ?>" />
                                        <img id="checkSMSCButtonWait" style="display:none;"
                                             src="<?= NIVEAU_0 ?>images/animation/indicator_snake.gif"
                                             border="0"/>
                                             <?php
                                         }
                                         ?>
                                </td>
                            </tr>
                        </table>
                    </fieldset>
                </form>

            </div>
            <?php
            //}
        }
//Bug 34830 - [QAL][CB531] : After saving global parameter, tab is void
        if (isset($_POST["product"])) {
            $category_active = array_search(array_search($categorie_courante, $categories), $list_categories_active);
            if ($category_active == null)
                $category_active = 0;
        }
        ?>
    </div>
</div>
<script>
    function create_onglets(cats, category_active) {
        // alert("*"+cats+"*");
        _categories = Array();
        _categories = cats.split(',');
        // _categories.push('Test4');
        // _categories.push('Test5');
        // _categories.push('Test6');
        initTabs('parameters_tab_view', _categories, category_active, '50%', '85%', null, '<?= NIVEAU_0 ?>images/tab-view/');
        // initTabs('parameters_tab_view', Array('test1','test2') ,category_active,600,'100%',null,'<?= NIVEAU_0 ?>images/tab-view/');
    }

    // 02/03/2010 BBX : modification du nom de la fonction vers un nom g?n?ral
    // 18/04/2011 OJT : Robustesse, les valeurs test?es ne sont toujours pr?sentes (produit blanc)
    function checkForm(form)
    {
        // maj 02/12/2009 Correction du bug 13043 - Valeur du param?tre compute mode doit ?tre obligatoirement hourly ou daily
        if ($('compute_mode_value') && $('compute_mode_value').value !== 'hourly' && $('compute_mode_value').value !== 'daily') {
            alert("<?= __T('A_SETUP_GLOBAL_PARAMS_COMPUTE_MODE_VALUE') ?>");
            return false;
        }

        // 02/03/2010 BBX : controle du nombre de compteurs max. BZ 13354
        if ($('maximum_mapped_counters_value') && $('maximum_mapped_counters_value').value > 1570) {
            alert("<?= __T('A_SETUP_GLOBAL_PARAMS_MAX_COUNTERS') ?>");
            return false;
        }

        // 19/07/2011 OJT : DE SMS, v?rification du num?ro de port
        // 02/08/2011 OJT : bz23232, ajout d'une expression r?guli?re pour le test du port
        var portReg = new RegExp("^[0-9]{1,5}$", "g");
        if ($('smsc_port_value') && (!portReg.test($F('smsc_port_value')) || $F('smsc_port_value') > 65535))
        {
            alert("<?= __T('SMSC_PORT_ERROR') ?>");
            return false;
        }

        // V?rification de la criticit? minimale (DE SMS)
        if ($('alarm_sms_minimum_severity_value') &&
                $F('alarm_sms_minimum_severity_value') != 'minor' &&
                $F('alarm_sms_minimum_severity_value') != 'major' &&
                $F('alarm_sms_minimum_severity_value') != 'critical')
        {
            alert("<?= addslashes(__T('SMS_ALARM_SEVERITY_ERROR')) ?>");
            return false;
        }

        // DE SMS, v?rification du SMS sender (facultatif)
        // 23/09/2011 OJT : bz23917, test si l'?l?ment HTML est bien la avant de tester
        if ($('alarm_sms_sender_value') && $F('alarm_sms_sender_value').length > 0)
        {
            var phoneRetVal = true;
            new Ajax.Request('<?= NIVEAU_0 ?>scripts/testPhoneNumber.ajax.php', {
                method: 'get',
                asynchronous: false,
                parameters: 'phone_number=' + encodeURIComponent($F('alarm_sms_sender_value')),
                onSuccess: function (res)
                {
                    if (res.responseText != 'ok' && res.responseText.length > 0)
                    {
                        alert(res.responseText);
                        phoneRetVal = false;
                    }
                }
            });
            if (phoneRetVal == false)
                return false;
        }

        // 14/09/2012 ACS DE Make it possible to configure the maximum number of displayed elements
<?php if (isset($max_topover_ot_value)) { ?>
            if ($('max_topover_ot_value').value != <?= $max_topover_ot_value ?>) {
                alert("<?= addslashes(__T('A_GLOBAL_PARAMS_MAX_NUMBER_DISPLAYED_ELEMENT')) ?>");
            }
<?php } ?>

        document.forms[form].submit();
    }

    /**
     * Test la connection au SMS-C avec les param?tres en cours d'editon
     *
     * 02/08/2011 OJT : bz23235, ajout de l'identifiant produit
     * 05/08/2011 OJT : bz23258, ajout d'un random en GET
     */
    function checkSMSCConnection()
    {
        $('checkSMSCButton').disabled = true;
        $('checkSMSCButtonWait').show();
        var dateObj = new Date();
        var params = 'host=' + encodeURIComponent($F('smsc_host_value'));
        params += '&port=' + encodeURIComponent($F('smsc_port_value'));
        params += '&pass=' + encodeURIComponent($F('smsc_password_value'));
        params += '&id=' + encodeURIComponent($F('smsc_system_id_value'));
        params += '&type=' + encodeURIComponent($F('smsc_system_type_value'));
        params += '&product=' + <?= $id_prod ?>;
        params += '&rand=' + dateObj.getTime();

        new Ajax.Request('<?= NIVEAU_0 ?>scripts/testSMSCSettings.ajax.php', {
            method: 'get',
            parameters: params,
            asynchronous: false,
            onSuccess: function (res) {
                alert(res.responseText);
            }
        });
        $('checkSMSCButtonWait').hide();
        $('checkSMSCButton').disabled = false;
    }

    create_onglets('<?= $cats ?>',<?= $category_active ?>);

    // 18/07/2011 OJT : DE SMS, initialisation du tooltip
    if ($('alarm_sms_format_value') != null)
    {
        parameterValueChange($('alarm_sms_format_value'));
    }
    // 26/03/2013 NSE : Alarm Email subject format
    // Initialisation du tooltip
    if ($('alarm_email_subject_format_value') != null)
    {
        parameterValueChange($('alarm_email_subject_format_value'));
    }
</script>