<?php
/**
 * @cb5100@
 *
 * 06/07/2010 - Copyright Astellia
 *  - 06/07/2010 OJT : Ajout du header standard et correction bz16401
 */
?>
<?
/*
*	@cb41000@
*
*	11/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	11/12/2008 BBX : modifications pour le CB 4.1 :
*	=> Utilisation des nouvelles méthodes et constantes
*	=> Contrôle d'accès
*	=> Utilisation de la classe de connexion àa la base de données
*	=> Gestion du produit
*
*	22/07/2009 BBX : ajout de addslashes sur $generic_counter_numerateur afin d'éviter les erreurs JS avec les KPI capacity planning. BZ 10289
*
*	23/09/2009 GHX
*		- Ajout d'un trim sur le commentaire pour éviter d'avoir des JS si on a des retours à la ligne à la fin des commentaires
*	12:54 22/12/2009 SCT : transformation de $network par $network1stAxis pour Erlang
*	16:44 15/01/2010 SCT : modification de la condition d'exécution de la fonction erlangb
*	24/02/2010 NSE bz 13799 : ajout de htmlentities sut title et comment
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
*	- 08:56 25/01/2008 Gwénaël :  modif pour la récupération du paramètre client_type
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
 * 	@cb1300_iu2000b_pour_cb2000b@
 *
 * 	19/07/2006 - Copyright Acurio
 *
 * 	Composant de base version cb_1.3.0.0
 *
 * 	Parser version iu_2.0.0.0

  /**
 * Affiche laliste de tous les compteurs génériques batis à partir
 * des raw data sélectionnés dans les OMC/flat file
 *
 * - maj 23/05/2006 sls : lorsque le client_type = 'client', les checkboxes des kpis créés par un customisateur sont disabled.
 * - 22-08-2006 : Gestion des formules qui sont identifiées comment pourcentage et qui ont des CASE WHEN DANS LA FORMULE
 *
 * - 03 10 2006 : MD - correction pb lorsque deux kpis portent le meme nom dans des familles differentes
 * 	>> ligne 88 : ajout d'une condition pour recuperer les infos du kpi pour la famille courante
 *
 * - 11 10 2006 : MD - tri de la liste des KPIs par label l.79
 */

session_start();
include_once dirname(__FILE__) . "/../../../../php/environnement_liens.php";
include_once REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php";

// Connexion à la base produit
$database = Database::getConnection($_GET['product']);

include_once(REP_PHYSIQUE_NIVEAU_0 . '/php/header.php' );

$edw_group_table = $_GET["edw_group_table"];
// modif 08:56 25/01/2008 Gwénaël
// Modif pour la récupération du paramètre client_type
$client_type = getClientType($_SESSION['id_user']);
?>
<script type="text/javascript">
    function set_on_off(id)
    {
        var url = '../traitement/kpi_builder_kpi_on_off.php?id=' + id + '&product=<?= $_GET['product'] ?>&family=<?= $_GET["family"] ?>';

        if (document.zeform['on_off_' + id].checked)
        {
            url += '&on_off=1';
        } else
        {
            url += '&on_off=0';
        }
        var previous_value = document.zeform['on_off_' + id].checked;
        // Appel A.J.A.X. pour la génération de la table de données
        new Ajax.Request(url,
                {
                    method: "get",
                    onSuccess: function (res)
                    {
                        if (res.responseText.length > 0) {
                            alert(res.responseText);
                            // 09:47 18/10/2010 SCT : BZ 18518 => Vérification de la formule KPI avant activation depuis KPI_Builder
                            //  + possibilité de forcé un Kpi sur On lorsqu'il est utilisé comme BH
                            if (previous_value)
                                document.zeform['on_off_' + id].checked = false;
                            else
                                document.zeform['on_off_' + id].checked = true;
                        }
                    }
                });
    }
</script>
<form name="zeform">
    <table cellspacing="0" cellpadding="3" border="0" class="tabPrincipal">
        <tr>
            <td>
                <fieldset>
                    <legend class="texteGrisBold">
                        &nbsp;<img src="<?= NIVEAU_0 ?>images/icones/puce_fieldset.gif">&nbsp;KPI List&nbsp;
                    </legend>
                    <table width="100%" border="0" cellpadding="2" cellspacing="0">
                        <?php
// On récupère la famille.
                        $family = $_GET["family"];

                        $query = "
                                SELECT distinct kpi_name,kpi_label, value_type
                                FROM sys_definition_kpi
                                WHERE edw_group_table in
								(
										select edw_group_table from sys_definition_group_table
										where family='$family'
										and visible=1
								)
                                and numerator_denominator='total'
                                and new_field<>'2'
                                and visible=1
                                ORDER BY kpi_label ASC
                        ";
// echo $query."<br>";
                        $result = $database->execute($query);
                        $nombre_resultat = $database->getNumRows();
                        if ($nombre_resultat > 0) {
                            foreach ($database->getAll($query) as $row) {
                                $nom_generic_counter = $row["kpi_name"];
                                $kpi_owner = $row['value_type'];
                                // On récupère les info sur le kpi.
                                $query_kpi = " select id_ligne, kpi_type, kpi_formula, on_off, comment,kpi_label,pourcentage from sys_definition_kpi where kpi_name='$nom_generic_counter'
						AND edw_group_table IN (SELECT edw_group_table FROM sys_definition_group_table WHERE family='$family')";
                                $row_kpi = $database->getRow($query_kpi);
                                $id_generic_counter = $row_kpi["id_ligne"];
                                $on_off = $row_kpi["on_off"];
                                $pourcentage = $row_kpi["pourcentage"];
                                $kpi_comment = addslashes($row_kpi["comment"]);
                                // 24/09/2010 - MPR correction du bz18035 : Ajout utf8_encode pour afficher les caractères spéciaux
                                $kpi_label = utf8_encode(addslashes($row_kpi["kpi_label"]));
                                if ($kpi_label != "") {
                                    $diplay_kpi = $kpi_label;
                                } else {
                                    $diplay_kpi = $nom_generic_counter;
                                }
                                // Verifie si le KPI possède la fonction zero. POur cela il suffit de verifier dans la formula si on a <0 car la formule est CASE WHEN xxx <0 THEN 0 ELSE xxx END
                                if (strstr($row_kpi['kpi_formula'], "<0")) {
                                    $search[0] = "<0 THEN 0 ELSE";
                                    $fonction_negative = 1;
                                } else {
                                    $fonction_negative = 0;
                                }

                                // maj 29/11/2009 MPR : On réécrit la formule pour l'affichage de la fonction erlangb
                                if (strstr($row_kpi['kpi_formula'], "erlangb")) {

                                    $net_min = get_network_aggregation_min_from_family($family, $_GET['product']);

                                    // 12:54 22/12/2009 SCT : transformation de $network par $network1stAxis
                                    // 16:44 15/01/2010 SCT : modification de la condition d'exécution de la fonction erlangb
                                    $row_kpi['kpi_formula'] = str_replace("erlangb(\$network1stAxis,'\$network1stAxis','$net_min',", "erlangb(", $row_kpi['kpi_formula']);
                                    //$row_kpi['kpi_formula'] = str_replace(") ELSE 0",")",$row_kpi['kpi_formula']);
                                }

                                $nb_casewhen = substr_count($row_kpi['kpi_formula'], "CASE WHEN");
                                $pos_casewhen = strpos($row_kpi['kpi_formula'], "CASE WHEN");

                                
                                // 22/08/2017 IN 3305 - Gestion des requêtes customisées
                                if ($pos_casewhen == 0 && $nb_casewhen == 1) {
                                    // remplace les éventuels ajouts présents dans la formule du KPI
                                    $search[1] = "::float4";
                                    $search[2] = "CASE WHEN ";
                                    $search[3] = "> 100 THEN 100 ELSE";
                                    $search[4] = ">100 THEN 100 ELSE";
                                    $search[5] = " END";
                                    $generic_counter_numerateur = str_replace($search, "", $row_kpi['kpi_formula']);
                                } else {
                                    $generic_counter_numerateur = $row_kpi['kpi_formula'];
                                }

                                // s'il y a un case WHEN, on a donc 2 fois la formule séparée par un espace. Il faut donc prendre la première valeur trouvéee
                                if ($pourcentage == 1 || $fonction_negative == 1) {
                                    //IN 3305 - Si il y a qu'un seul CASE WHEN et que la requete commence par CASE WHEN
                                    if ($nb_casewhen == 1 && $pos_casewhen == 0) {
                                        // maj 24/09/2010 - MPR : Correction du bz18035 - Si un espace présent dans une formule(%) alors la formule est tronquée
                                        $new_formula = implode(" ", array_unique(explode(' ', $generic_counter_numerateur)));
                                        $generic_counter_numerateur = $new_formula; //substr($generic_counter_numerateur, 0, strpos($generic_counter_numerateur, " "));
                                    }
                                }
                                if ($fonction_negative == 1) {
                                    $generic_counter_numerateur = "zero" . $generic_counter_numerateur;
                                }

                                // 22/07/2009 BBX : ajout de addslashes sur $generic_counter_numerateur afin d'éviter les erreurs JS avec les KPI capacity planning. BZ 10289		
                                ?>
                                <tr height="20">
                                    <td>
                                        <input type="hidden" value="<?= $generic_counter_numerateur ?>" name="<?= $generic_counter_numerateur ?>">
                                        <input type="checkbox" onclick="set_on_off('<?= $id_generic_counter ?>');" name="on_off_<?= $id_generic_counter ?>" value="<?= $on_off ?>" <?php if ($on_off) {
                                    ?>checked="checked"<?php }
                                ?> <?php
                                               if (($kpi_owner != 'client') and ( $client_type == 'client'))
                                                   echo ' disabled="disabled"';
                                               // 24/02/2010 NSE bz 13799 : ajout de htmlentities sut title et comment
                                               ?> />
                                    </td>
                                    <td>
                                        <a href="#<?= $nom_generic_counter ?>" name="<?= $nom_generic_counter ?>" title="<?= ($row_kpi['comment']) ? trim(htmlentities($row_kpi['comment'])) : 'No description data for that field.';
                                               ?>" onclick="parent.kpi_builder.affiche_equation('<?= $nom_generic_counter ?>', '<?= addslashes($generic_counter_numerateur) ?>', '<?= $id_generic_counter ?>', '<?= $family ?>', '<?= trim(htmlentities($kpi_comment)) ?>', '<?= $kpi_label ?>', '<?= $pourcentage ?>')">
                                            <font class="texteGrisPetit"><?= strtoupper($diplay_kpi) ?></font>
                                        </a>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr height="20">
                                <td align="center">
                                    <font class="texteGrisBold">No Kpis</font>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </table>
                </fieldset>
            </td>
        </tr>
    </table>
</form>
</body>
</html>
