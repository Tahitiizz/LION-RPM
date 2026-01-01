<?php
/*
 *
 *
 * 04/02/2011 MMT Bz 20114 change le critère de tri pour utiliser la date
 * 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
 *
 * 
 *
 * @cb50412@
 *
 *	04/01/2011 - Copyright Astellia
 *
 *	Ajax appelé depuis la homepage admin pour la recherche des informations contenues dans la homepage admin
 *  Script développé dans le cadre de la correction du BZ 19673
 *
 */
?>
<?php
include_once( dirname( __FILE__).'/../../../../php/environnement_liens.php');

// Librairies et classes requises
include_once(REP_PHYSIQUE_NIVEAU_0. "/php/edw_function_family.php");
include_once(REP_PHYSIQUE_NIVEAU_0. "/php/postgres_functions.php");

class IntraHomepageAdmin
{
    /**
     * fonction qui retourne l'affichage des informations réseaux d'une famille passée en paramètre
     * @param array $infosProduit : tableau contenant les informations du produit (depuis sys_definition_product)
     * @return string : le code html à afficher
     */
    public static function homepageDynamicNetworkInfo($infosProduit)
    {
        $retourFonction = '';
        $id_prod        = $infosProduit['sdp_id'];
        $database       = Database::getConnection($id_prod);
        $main_family    = get_main_family($id_prod);
        // 09/12/2009 BBX : ajout d'un test sur la famille principale. BZ 13182
        if(!empty($main_family))
        {
            $_net = getNaLabelList("na",$main_family, $id_prod);
            // On recupere le nombre d'elements reseau pour chaque produit de tous les niveaux d'agregation
            $query = "SELECT count(eor_id) as nb_elements, eor_obj_type FROM edw_object_ref WHERE eor_obj_type IN ('";
            $obj_type = array();
            foreach ($_net[$main_family] as $network_aggregation=>$net_label)
            {
                $obj_type[] = $network_aggregation;
            }

            // 13/10/2011 BBX
            // BZ 23712 : exclusion des éléments virtuels
            // 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
            $query.= implode("','",$obj_type)."') 
                AND ".NeModel::whereClauseWithoutVirtual()."
                GROUP BY eor_obj_type";

            $result = $database->getAll($query);
            $nombre_resultat           = count($result);
            $nets_find['eor_obj_type'] = array();
            $nets_find['nb_elements']  = array();

            if ($nombre_resultat > 0)
            {
                foreach($result as $row)
                {
                    $nets_find['eor_obj_type'][] = $row['eor_obj_type'];
                    $nets_find['nb_elements'][]	 = $row['nb_elements'];
                }
            }



            // Affichage des resultats
            foreach ($_net[$main_family] as $network_aggregation=>$net_label)
            {
                if (!in_array($network_aggregation, $nets_find['eor_obj_type']))
                {
$retourFonction .= <<<EOS
                    <tr align="left">
                        <td class="texteGrisBold" style='font-size:10px;'>0&nbsp<span class="texteGris" style='font-size:10px;'>{$net_label}</span></td>
                    </tr>
EOS;
                }else{
                    $id = array_keys($nets_find['eor_obj_type'], $network_aggregation );
$retourFonction .= <<<EOS
                    <tr align="left">
                        <td class="texteGrisBold" style='font-size:10px;'>{$nets_find['nb_elements'][$id[0]]}&nbsp<span class="texteGris" style='font-size:10px;'>{$net_label}</span></td>
                    </tr>
EOS;
                }
            }
        }
        // 09/12/2009 BBX : si pas de famille principale, on affiche un message d'erreur. BZ 13182
        else
        {
            $leMessageDErreur = __T('A_HOMEPAGE_NO_FAMILY_DEFINED');
$retourFonction .= <<<EOS
            <tr align="left">
                <td class="texteGrisBold" style='font-size:10px;'>
                    <span class="texteRouge" style='font-size:10px;'>{$leMessageDErreur}</span>
                </td>
            </tr>
EOS;
        }
        return $retourFonction;
    } // End homepageDynamicNetworkInfo()

    /**
     * fonction qui retourne l'affichage des informations de renseignement topologie d'une famille passée en paramètre
     * @param array $infosProduit : tableau contenant les informations du produit (depuis sys_definition_product)
     * @return string : le code html à afficher
     */
    public static function homepageDynamicTopologyInfo($infosProduit)
    {
        $retourFonction = '';
        $id_prod        = $infosProduit['sdp_id'];
        $database       = Database::getConnection($id_prod);
        $gis_activated  = get_sys_global_parameters('gis');
        // 09/12/2009 BBX : ajout d'un test sur la famille principale. BZ 13182
        if ($gis_activated) {
            // 03/06/2009 BBX : on se base désormais sur longitude et lattitude pour compter. BZ 9902
            $query = "
                    SELECT COUNT(eorp_id) AS nb
                    FROM edw_object_ref_parameters
                    WHERE eorp_longitude IS NULL OR eorp_latitude IS NULL
                    ";
            $nb_xy = $database->getOne($query);
            //unset($res);
            //unset($row);
        }

        // on cherche les elements qui n'ont pas de NA_label
        $query = "
            SELECT COUNT(*) AS nb
            FROM edw_object_ref
            WHERE eor_label IS NULL
            ";

        $nb_na_label = $database->getOne($query);


        // 01/06/2007 - Modif. benoit : si le gis est désactivé, on désactive les infos sur les x, y
        if ($gis_activated) {

            /*
            <td><a href="<??>topology_errors.php?display=xy&id_prod=<?=$infos['sdp_id']?>" onclick="window.open('<?=$niveau0?>homepage/intranet/php/affichage/topology_errors.php?display=xy&id_prod=<?=$infos['sdp_id']?>','topowindow','toolbar=0,addressbar=0,width=500,scrollbars=1');return false;"><span class="texteGrisBold"><?= $nb_xy ?></span></a></td>
            */
            $leMessageAffiche = __T('A_HOMEPAGE_ADMIN_TOPOLOGY_NO_COORDINATES');
$retourFonction .= <<<EOS
            <tr valign="top">
                <td class="texteGris" style="font-size:10px;"><nobr>{$leMessageAffiche}</nobr></td>
                <td class="texteGrisBold" style="font-size:10px;">{$nb_xy}</td>
            </tr>
EOS;
        }


        /* 16/04/2009 - modif SPS : ajout des balises manquantes <acronym> et <span>*/
        $leMessageAffiche = __T('A_HOMEPAGE_ADMIN_TOPOLOGY_NO_LABEL');
$retourFonction .= <<<EOS
                    <tr valign="top">
                            <td class="texteGris" style="font-size:10px;"><acronym>{$leMessageAffiche} </acronym></td>
                            <td class="texteGrisBold" style="font-size:10px;"><span>{$nb_na_label}</span></td>
                    </tr>
EOS;
        return $retourFonction;
    } // End homepageDynamicTopologyInfo()

    /**
     * fonction qui retourne l'affichage des informations de process d'une famille passée en paramètre
     * @param array $infosProduit : tableau contenant les informations du produit (depuis sys_definition_product)
     * @param bool $displayNone : booleen pour bloquer l'affichage de la liste des dates à intégrer
     * @return string : le code html à afficher
     */
    public static function homepageDynamicProcessInfo($infosProduit, $displayNone=false)
    {
        $retourFonction = '';
        $id_prod        = $infosProduit['sdp_id'];
        $database       = Database::getConnection($id_prod);
        $displayStyle   = $displayNone ? ' style="display: none;"' : '';

        // recherche des process
        $query = "
            SELECT day,hour,time_type
            FROM sys_to_compute
            ORDER BY day DESC, time_type DESC, hour DESC
        ";
        $process_queue = $database->getAll($query);

        // on affiche les process
        if ($process_queue)
        {
            foreach ($process_queue as $row)
            {
                $miseEnFormeDate = substr($row['hour'],8,2).( $row['hour'] != '' ? ':00&nbsp;' : '').substr($row['day'],6,2).'/'.substr($row['day'],4,2).'/'.substr($row['day'],0,4);
$retourFonction .= <<<EOS
                <tr{$displayStyle}>
                    <td style="font-weight:bold;" width="30">{$row['time_type']}</td>
                    <td>{$miseEnFormeDate}</td>
                </tr>
EOS;
            }
        } else {
$retourFonction .= <<<EOS
                <tr{$displayStyle}><td colspan="2">Process queue empty.</td></tr>
EOS;
        }
        return $retourFonction;
    } // END homepageDynamicProcessInfo()

    /**
     * fonction qui retourne l'affichage des informations du SI d'une famille passée en paramètre
     * @param array $infosProduit : tableau contenant les informations du produit (depuis sys_definition_product)
     * @return string : le code html à afficher
     */
    public static function homepageDynamicSystemInfo($infosProduit)
    {
        $retourFonction = '';
        $id_prod        = $infosProduit['sdp_id'];

        $shell_reply = null; // 2010/08/11 - MGD - BZ 10936
        // 06/04/2012 BBX
        // BZ 26732 : Utilisation de get_adr_server au lieu de $_SERVER['SERVER_ADDR']
        if ($infosProduit['sdp_ip_address'] == get_adr_server()) {
            // local
            // maj 02/12/2009 MPR : Correction du bug 10936 - On ignore la première ligne
            $shell_reply = explode("\n",shell_exec("df -h | awk 'NR>1{print $0}'"));

        } else {
            // remote -> accès via ssh
            include_once(REP_PHYSIQUE_NIVEAU_0.'class/SSHConnection.class.php');
            try {
                $ssh_cnx = new SSHConnection($infosProduit['sdp_ip_address'], $infosProduit['sdp_ssh_user'], $infosProduit['sdp_ssh_password'], $infosProduit['sdp_ssh_port'], 1);
                $shell_reply = $ssh_cnx->exec('df -h');
                array_shift($shell_reply); // 2010/08/11 - MGD - BZ 10936 - On supprime la ligne contenant l'entete (revient au meme que le | awk 'NR>1{print $0}')
                unset($ssh_cnx);
            } catch ( Exception $e ) {
                $retourFonction .= '<tr><td colspan="2" style="color:#E90;">'.$e->getMessage().'</td></tr>';
            }
        }

        // on va afficher le resultat
        if ($shell_reply) {
            foreach ($shell_reply as $line) {
                $line = trim($line);
                if ($line) {

                    // on supprime les espaces multiples
                    $line = ereg_replace(' +',' ',$line);
                    // on recupere les infos de la ligne
                    list($filesystem,$size,$used,$avail,$use_percent, $mounted_on) = explode(' ',$line,6);

                    // pour pas que ça dépasse
                    $mounted_on_short = $mounted_on;
                    if (strlen($mounted_on)>17) $mounted_on_short = substr($mounted_on,0,17).'...';


                    // on affiche
                    $class = '';
                    if ($use_percent>80) $class = 'lessThan10';
                    if ($use_percent==100) $class = 'equal0';
                    $espaceDisque = 100 - $use_percent;
$retourFonction .= <<<EOS
                        <tr class="{$class}">
                            <td title="{$espaceDisque}% Free ({$avail} / {$size})">{$avail}</td>
                            <td title="Filesystem {$filesystem} mounted on {$mounted_on}">{$mounted_on_short}</td>
                        </tr>
EOS;
                }
            }
        }
        unset($shell_reply);
        return $retourFonction;
    } // END homepageDynamicSystemInfo()

    /**
     * fonction qui retourne l'affichage des logs d'un produit passé en paramètre
     * @param array $infosProduit : tableau contenant les informations du produit (depuis sys_definition_product)
     * @return string : le code html à afficher
     */
    public static function homepageDynamicLogInfo($infosProduit)
    {
        $retourFonction = '';
        $database = Database::getConnection($infosProduit['sdp_id']);

        $severity_color_display['Critical'] = '#FF0000';
        $severity_color_display['Major']    = '#FF00AA';
        $severity_color_display['Info']     = '';
        $level                              = 'support_1';
        $limit                              = 10;

        // on compose la requête SQL
        if ($level != 'ALL')
            $condition_tmp[] = "type_message='$level'";

        $condition_tmp[] = "severity = 'Critical'";

        $condition = " WHERE ".implode(' AND ',$condition_tmp);
        $condition.= " OR message LIKE 'PROCESS%'";

		  // 04/02/2011 MMT Bz 20114 change le critère de tri pour utiliser la date
        $query = "SELECT message_date, severity, message FROM sys_log_ast $condition order by message_date DESC, oid DESC limit " . $limit;
        $res = $database->getAll($query);

        $compteur = 0;
        if ($res) {
            foreach($res as $row)
            {
                $gestionStyleColor = $compteur%2 ? 'class="fondGrisClair" ' : '';
                $gestionStyleAlign = $row['severity'] == 'Info' ? 'align="center" ' : '';
                $messageAffiche    = strlen($row['message']) >= 48 ? substr($row['message'], 0, 45).'...' : $row['message'];
$retourFonction .= <<<EOS
                <tr {$gestionStyleColor} align="left" style="color:{$severity_color_display[$row['severity']]}">
                    <td nowrap >{$row['message_date']}&nbsp;</td>
                    <td nowrap {$gestionStyleAlign}>{$row['severity']}&nbsp;</td>
                    <td nowrap title="{$row['message']}">{$messageAffiche}&nbsp;</td>
                </tr>
EOS;
                $compteur++;
            }
        }
        $retourFonction .= '<tr><td colspan="3" align="right"><a href="myadmin_tools/intranet/php/affichage/tracelog_index.php?id_product='.$infosProduit['sdp_id'].'">&gt;&gt; More...</a></td></tr>';
        return $retourFonction;
    } // END homepageDynamicLogInfo()
}

// code pour le traitement en ajax
if(isset($_POST['type']) && isset($_POST['idProduit']))
{
    switch ($_POST['type']) {
        case 'network':
            echo IntraHomepageAdmin::homepageDynamicNetworkInfo(array('sdp_id' => $_POST['idProduit']));
            break;
        case 'topology':
            echo IntraHomepageAdmin::homepageDynamicTopologyInfo(array('sdp_id' => $_POST['idProduit']));
            break;
        case 'process':
            echo IntraHomepageAdmin::homepageDynamicProcessInfo(array('sdp_id' => $_POST['idProduit']));
            break;
        case 'system':
            // on instancie le model Produit pour récupérer toutes les informations nécessaires
            $leProduit = new ProductModel($_POST['idProduit']);
            echo IntraHomepageAdmin::homepageDynamicSystemInfo($leProduit->getValues());
            break;
        case 'systemInfo':
            // on recherche l'ip de la machine
            $leProduit       = new ProductModel($_POST['idProduit']);
            $tableauResultat = $leProduit->getValues();
            echo $tableauResultat['sdp_ip_address'];
            break;
        case 'log':
            echo IntraHomepageAdmin::homepageDynamicLogInfo(array('sdp_id' => $_POST['idProduit']));
            break;
        default:
            break;
    }
}
