<?
/*
*	@cb51000@
*
*	28-06-2010 - Copyright Astellia
* 
*	Composant de base version cb_5.1.0.00
*
*	28/06/2010 NSE : Division par zéro - remplacement de l'opérateur / par //
*	15/07/2010 NSE : Suppression de l'opérateur //
 *      02/11/2011 NSE bz 21894 : on ne teste pas la formule des Kpi qui seront supprimés (new_field = 2)
*
*/
?><?
/**
 *      @cb50312@
 *      23/09/2010 - Copyright Astellia
 *
 *      Composant de base version cb_5.0.3.12
 *
 *
 *      - 23/09/2010 - MPR : Nettoyage du fichier
 *              -> Utilisation de DataBase::getConnection() pour se connecter à la base de données
 *              -> Suppression de $database_connection
 *              -> Suppression des include inutiles
 *              -> Utilisation de displayInDemon à la place des echo et print
 *
 *      - 23/09/2010 - MPR : Correction du bz18035
 *              -> Le contrôle sur les formules des kpis est maintenant effectué avant leur déploiement
 *                 On ne déploie pas un kpi ayant une formule incorrecte)
 *              -> pg_last_error() retournait systématiquement une chaine vide '' (erreur ou pas)
 *      - 04/10/2010 NSE bz 18295 : erreur pour les formules contenant une fonction erlang.
 *      - 24/11/2010 - MMT : Bz 19384 -> Test Raw non activé dans la formule KPI edw_field_name à la place de nms_field_name pour le raw name
 *      04/02/2011 NSE bz 19666 : ajout du message d'erreur retourné par pgsql en cas de réapparition du bug
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
	- maj 08/02/2008, benoit : ajout de l'evaluation des formules des kpis pour prendre en compte l'utilisation de variables php dans celles-ci
	- maj 08/02/2008, benoit : definition de la variable '$network' valant '$network_min' utilisée dans les formules des kpis 'TS_UTILISATION'    et 'TS_NEEDED'
	
	02/02/2009 GHX
		- modification du script pour supprimer l'appel de fichiers supprimés
		- suppression du code inutile
	10:09 22/12/2009 SCT : on ajoute 2 variables pour récupérer le 1er axe et le 3ème axe
	
*/
?><?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*/
?><?
/*
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?><?php
/*
  - 20 07 2006 : MD :
 	  >> deploiement des tables de type adv_kpi : modif de la fonction "detect_new_field_w" ajout de la fonction "checkAdvKpiAndDisable" et "get_adv_kpi_to_disable"
 	  >> activation - desactivation du module de calculs statistiques (Adv. KPI)  : fonction toggle_adv_kpi_state($state)
*/


// pour la gestion des logs
$start_date=date("F j, Y, H:i:s");
$time_start=microtime( true );
// attention  quand on cree in mixed kpi le edw_group_table doit soit etre a edw_motorola_0 ou edw_alcatel_0
include_once(dirname(__FILE__)."/../php/environnement_liens.php");
// 23/09/2010 - MPR : Nettoyage du fichier - Suppression des include en trop
include_once(REP_PHYSIQUE_NIVEAU_0."php/edw_function_family.php");
include_once(REP_PHYSIQUE_NIVEAU_0."php/deploy_and_compute_functions.php");

// Classe de partitionning nécessaires
include_once(REP_PHYSIQUE_NIVEAU_0.'class/Partition.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'class/Partitionning.class.php');


function detect_new_field_w($group_id) // ne traite pas les nouvelles agregations
{
    // 23/09/2010 - MPR : Nettoyage du fichier - Utilisation de DataBase::getConnection();
    $_db = DataBase::getConnection();
    $group=get_group_table_name($group_id);
    $min_net=get_min_net($group_id,"raw");
    $min_time=get_min_time($group_id,"raw");
    $new_fields_kpi = array(
                    'raw' => array('table' => 'sys_field_reference',
                            'fname' => 'edw_target_field_name',
                            'ftype' => 'edw_field_type'),
                    'kpi' => array('table' => 'sys_definition_kpi',
                            'fname' => 'kpi_name',
                            'ftype' => 'kpi_type')
                    );

   // $queries=array();
    foreach(array_keys($new_fields_kpi) as $raw)
    {
        if( $raw == 'kpi' )
        {
           // $queries = array();
            // maj 23/09/2010 - MPR : Correction du bz18035
            // Le contrôle est maintenant effectué avant le déploiement des kpis (on ne déploie pas un kpi ayant une formule incorrecte)
            displayInDemon("Desactivation des KPIs ayant des formules incorrectes<br />");
                    checkKpiAndDisable($group_id);
        }

        $source_table = $new_fields_kpi[$raw]['table'];
        $edw_field_name = $new_fields_kpi[$raw]['fname'];
        $edw_field_type = $new_fields_kpi[$raw]['ftype'];
        // 23/09/2010 - MPR : Nettoyage du fichier - Utilisation de DataBase::getConnection();
                // 07/06/2011 BBX -PARTITIONING-
                // Suppression du LOCK TABLE inutile
                // $_db->execute("LOCK TABLE $source_table");

        // creation des tables row ( alter )
        $sql = "SELECT
                distinct t0.$edw_field_name as name,
                                 t0.$edw_field_type as type,
                                 t0.new_field,
                                 t1.edw_group_table,
                                 t1.edw_group_table||'_'||'$raw'||'_'||t3.network_agregation||'_'||t2.time_agregation as cible
                                         ";
                $source_table_group="=t0.edw_group_table";
        if ($raw == 'kpi')
                $sql .= ",t0.numerator_denominator";
        elseif($raw == 'raw')
                $sql .= ",'total'::text as numerator_denominator";

        $sql .= "
                FROM
                $source_table t0 ,
                sys_definition_group_table t1,
                sys_definition_group_table_time t2,
                sys_definition_group_table_network t3

                        WHERE
                        t0.new_field!=0 and t0.on_off=1 and
                        t1.edw_group_table $source_table_group and
                        t2.id_group_table=t1.id_ligne and
                        t2.data_type='$raw' and
                        t3.data_type='$raw' and
                        t3.id_group_table=t1.id_ligne and
                        t1.edw_group_table='$group'
                        ";
        // 23/09/2010 - MPR : Nettoyage du fichier - Utilisation de DataBase::getConnection();
        $query_results = $_db->execute($sql);
        if ($_db->getLastError() != "" )
        {
           displayInDemon($sql);
        }
        displayInDemon("$raw : " . pg_num_rows($query_results) . " elements à modifier (créer ou supprimer)<BR>\n");
        // 23/09/2010 - MPR : Nettoyage du fichier - Utilisation de DataBase::getConnection();
        $nombre_resultat = $_db->getNumRows($query_results);
        // 23/09/2010 - MPR : Nettoyage du fichier - Utilisation de DataBase::getConnection();
        while ( $row = $_db->getQueryResults($query_results,1) )
        {
            $sql1 = "ALTER TABLE " . $row["cible"];

            switch ($row['new_field'])
            {
                case 1:
                    // 02/05/2011 OJT : bz21946 Test si la colonne existe
                    // 10/06/2011 NSE Merge 5.0.5 -> 5.1.3 : remplacement de columnExists() par doesColumnExist()
                    if( !$_db->columnExists( $row["cible"], $row["name"] ) )
                    {
                        $sql1 .= " ADD " . $row["name"] . ' ' . $row["type"];
                        if($raw=="raw")
                                $toadd[]=$row["name"].' '.$row["type"];
                    }
                    else
                    {
                        $sql1 = '';
                    }
                        break;
                case 2:
                    // 02/05/2011 OJT : bz21946 Test si la colonne existe
                    if( $_db->columnExists( $row["cible"], $row["name"] ) )
                    {
                        $sql1 .= " DROP " . $row["name"];
                        if($raw=="raw")
                                $todrop[]=$row["name"];
                    }
                    else
                    {
                        $sql1 = '';
                    }
                        break;
            }

            // Si une requête est à exécuter
            if( strlen( $sql1 ) > 0 )
            {
                $res = $_db->execute( $sql1 );
                if ( $_db->getLastError() != "" )
                {
                    // 04/02/2011 NSE bz 19666 : enrichissement log demon
                    displayInDemon( "Erreur lors de l'exécution de la requête : ".$sql1."<br>Erreur psql: ".$_db->getLastError(), 'alert');
                }
            }
        }

        $_db->execute("UPDATE $source_table SET new_field = 0 WHERE new_field = 1 and on_off = '1' and edw_group_table='$group'");
        if ($raw == 'kpi')
        {
            $_db->execute("DELETE FROM $source_table WHERE new_field = 2 and edw_group_table='$group'");
        }
        else
        {
            // Pour les élements de type RAW, on ne supprime par l'entrée
            // dans sys_field_reference met jsute à jour new_field et on_off
            $_db->execute("UPDATE $source_table SET new_field=1, on_off=0 WHERE new_field=2 and edw_group_table='$group'");
        }
    }
}


/**
 * 24/11/2010 MMT Bz 19384
 * Return true if the raw name is used in the KPI formula, functions checks for exact string match none case sensitive
 * if the string found is preceeded or postfixed by an anlphanumerical or '_' it will return false
 *
 * @param String $rawName name of the raw counter (col edw_field_name from sys_field_reference)
 * @param String $formula KPI formula
 * @return bool
 */
function isRawUsedInKpiFormula($rawName,$formula){
	$noneRawCharRegEx = '[^a-z0-9._]';

	// construit la regEx, test la présence du raw encadré par deux caracteres non accpeté dans les noms des raws
	$regex = '/'.$noneRawCharRegEx.strtolower($rawName).$noneRawCharRegEx.'/';
	// encapsule la chaine pqr des espaces (car non accepté)  pour que $noneRawCharRegEx soit validé
	// dans le cas ou le nom du raw debute/finit la formule
	$searchContent = ' '.strtolower($formula).' ';
	return preg_match($regex, $searchContent);

}


function checkKpiAndDisable($group_id)
{
    // 23/09/2010 - MPR : Nettoyage du fichier - Utilisation de DataBase::getConnection();
    $_db = DataBase::getConnection();

    // 06/10/2011 BBX
    // BZ 19666 : Info sur la famille avec une seule instance de connexion + les modèles
    $family         = FamilyModel::getFamilyFromIdGroupTable($group_id);
    $familyModel    = new FamilyModel($family);
    $group          = $familyModel->getEdwGroupTable();
    $network_min    = $familyModel->getValue('network_aggregation_min');

    // 04/02/2011 NSE bz 19666 : ajout d'une sortie dans le démon en cas de réapparition du bug
    if(empty($network_min)){
        displayInDemon("Function 'get_network_aggregation_min_from_family($family)' returns empty result.",'alert');
        displayInDemon("family=$family<br>get_axe3_information_from_gt($group_id)",'normal');
    }

    // 06/10/2011 BBX
    // BZ 19666 : Gestion du 3ème axe avec une seule instance de connexion + les modèles
    $na_axe3 = NaModel::getNaFromFamily($family, '', 3);
    if(!empty($na_axe3)) {
        $network_min.= "_".$na_axe3[0];
    }

    // Recupere les kpis actifs
    // maj 29/09/2010 - MPR : On caste la formule en réel afin d'éviter les formules du genre "raw1,raw2" qui feraient planter le compute
    // 02/11/2011 NSE bz 21894 : on ne teste pas la formule des Kpi qui seront supprimés (new_field = 2)
    $sql = "SELECT id_ligne,kpi_name, kpi_label,kpi_formula,edw_group_table,
               'SELECT ('||kpi_formula||')::real FROM '||edw_group_table||'_raw_".$network_min."_day limit 1;' as sqlquery
            FROM sys_definition_kpi
            WHERE numerator_denominator = 'total'
                AND on_off = 1
                AND new_field <> 2
                AND edw_group_table='$group'
            ORDER BY kpi_name ASC";

    // Récupere la liste des compteurs déployé mais désactivé (on_off = 0)
    // 24/11/2010 MMT Bz 19384 utilise edw_field_name à la place de nms_field_name pour le raw name
    // 06/10/2011 BBX
    // BZ 19666 : exclusion des colonnes supprimées
    $check_2 = "
                SELECT attname as id, edw_field_name_label as label
                FROM pg_class c, pg_attribute a, sys_field_reference sfr
                WHERE a.attrelid = c.oid
                AND relname = '{$group}_raw_{$network_min}_day'
                AND a.attname = lower( sfr.edw_field_name ) AND edw_group_table = '{$group}'
                AND a.attisdropped = false
                AND sfr.on_off = 0;
                ";
    $result_check_2 = $_db->getAll($check_2);
        
    /**
     * Récupération des raws déployés mais désactivés
     */
    $system_name = get_sys_global_parameters("system_name");
    $_module = __T('A_TRACELOG_MODULE_LABEL_COMPUTE');

    // 23/09/2010 - MPR : Nettoyage du fichier - Utilisation de DataBase::getConnection();
    $res = $_db->getAll( $sql );
    if(count($res) > 0 )
    {
        $firstPartition = '';
        foreach ( $res as $row )
        {

            $error = null;

            $id_ligne = $row['id_ligne'];
            $kpi_name = $row['kpi_name'];
            // 23/09/2010 - MPR : Correction du bz18035 - On récupère le label pour le Warning dans le Tracelog
            $kpi_label = $row['kpi_label'];
            $kpi_formula = $row['kpi_formula'];

            // 19/05/2011 BBX - PARTITIONING -
            // Si le partitioning est activé on va utiliser la première partition existante
            // pour tester notre formule. Si pas de partition ou si le partitioning
            // n'est pas activé on prend la table de donnée mère comme avant.
            if( $firstPartition == '' )
            {
                $firstPartition = $row['edw_group_table']."_raw_".$network_min."_day";
                if($_db->isPartitioned())
                {
                    $listPartition = Partitionning::getPartitionFromDataTable( $row['edw_group_table']."_raw_".$network_min."_day" );
                    if( count( $listPartition ) > 0 )
                    {
                        // Utilisation des méthodes SplObjectStorage
                        $listPartition->rewind();
                        $firstPartition = $listPartition->current()->getName();
			}
                }
            }

            // 08/02/2008 - Modif. benoit : ajout de l'evaluation des formules des kpis pour prendre en compte l'utilisation de variables php dans celles-ci
            // 08/02/2008 - Modif. benoit : definition de la variable '$network' valant '$network_min' utilisée dans les formules des kpis 'TS_UTILISATION' et 'TS_NEEDED'

            $network = $network_min;
            // 10:09 22/12/2009 SCT : on ajoute 2 variables pour récupérer le 1er axe et le 3ème axe
            $tempNa = explode('_', $network);
            $network1stAxis = $tempNa[0];
            $network3rdAxis = $tempNa[1];
            unset($tempNa);
				
            // 04/10/2010 NSE bz 18295 on prépare la formule
            // si elle contient une fonction erlang
            if (!(strpos(strtolower($row['sqlquery']), 'erlangb')===false)) {
                $row['sqlquery'] = preg_replace('/(erlangb\([^,]+,[^,]+,[^,]+,.+,\s*null)([^:])/i','$1::real$2',$row['sqlquery']);
            }

            // 19/05/2011 BBX - PARTITIONING -
            // Remplcament du nom de la table de données par une partition
            $sql = $row['sqlquery'];
            $sql = str_replace( $row['edw_group_table']."_raw_".$network_min."_day" , $firstPartition, $sql);
            eval("\$sql = \"$sql\";");

            // 23/09/2010 - MPR : Nettoyage du fichier - Utilisation de DataBase::getConnection();
            $restry = $_db->execute( $sql );
            $error = $_db->getLastError();

            if ( $error != '' )
            {
                $msg = __T('A_CLEAN_TABLES_STRUCTURE_DEMON_WARNING_KPI_DISABLE',$kpi_name);
                $msg.= __T('A_CLEAN_TABLES_STRUCTURE_DEMON_WARNING_KPI_DISABLE_DETAILS',$sql);
                // 04/02/2011 NSE bz 19666 : ajout du message d'erreur retourné par pgsql en cas de réapparition du bug
                $msg.= __T('A_CLEAN_TABLES_STRUCTURE_DEMON_WARNING_KPI_DISABLE_ERROR',$error);
                
                // 23/09/2010 MPR : bz18035, ajout d'un Warning dans le Tracelog
                // 28/11/2011 OJT : bz24855, ajout du nom de la famille dans le message
                $msg_tracelog = __T('A_TRACELOG_WARNING_KPI_DISABLE', $kpi_label, $familyModel->getValue( 'family_label' ) );
                sys_log_ast("Warning", $system_name,  $_module, $msg_tracelog, "support_1", "");

                displayInDemon($msg,'alert');
                displayInDemon("*************",'alert');
                $sql = "UPDATE sys_definition_kpi set on_off = 0, new_field = 0 WHERE id_ligne = '$id_ligne' and kpi_name = '$kpi_name' and edw_group_table='$group'";

                $reson = $_db->execute($sql);
                if ($_db->getLastError() != '')
                {
                    displayInDemon(" Erreur : impossible de le desactiver " . $_db->getLastError(),'alert' );
                }
            }
            else
            {
                // On vérifie que la formule ne contient pas de compteurs désactivé (sinon Erreur lors du compute)
                if( count($result_check_2) > 0)
                {
                    $lst_raw_disable = array();

                    foreach( $result_check_2 as $raw_disable )
                    {
				    	// 24/11/2010 MMT Bz 19384 utilise methode plus complexe de recherche de raw dans formule
					    if(isRawUsedInKpiFormula($raw_disable['id'],$row['kpi_formula']))
                        {
                            $lst_raw_disable[] = $raw_disable['label'];
                        }
                    }

                    if( count($lst_raw_disable) > 0 )
                    {
                        $msg = __T('A_CLEAN_TABLES_STRUCTURE_DEMON_WARNING_KPI_DISABLE',$kpi_name);

                        // 28/11/2011 OJT : bz24855, ajout du nom de la famille dans le message
                        $msg_tracelog = __T('A_TRACELOG_WARNING_KPI_DISABLE', $kpi_label, $familyModel->getValue( 'family_label' ));

                        foreach( $lst_raw_disable as $raws)
                        {
                            $msg.= __T('A_CLEAN_TABLES_STRUCTURE_DEMON_WARNING_KPI_DISABLE_SHOW_RAW_DISABLE',$raws);
                        }
                        $msg_tracelog.= implode(" - ", $lst_raw_disable);
                        sys_log_ast("Warning", $system_name,  $_module, $msg_tracelog, "support_1", "");
                        displayInDemon($msg,'alert');
                        displayInDemon("*************",'alert');

                        $sql = "UPDATE sys_definition_kpi set on_off = 0, new_field = 0 WHERE id_ligne = '$id_ligne' and kpi_name = '$kpi_name' and edw_group_table='$group'";
                        $reson = $_db->execute($sql);
                        if ($_db->getLastError() != '')
                        {
                            displayInDemon(__T('A_CLEAN_TABLES_STRUCTURE_ERROR_KPI_DISABLE_IMPOSSIBLE',$_db->getLastError()),'alert' );
				        }
                    }
                }
                else
                {
                    $sql = "UPDATE sys_definition_kpi set on_off = 1 WHERE id_ligne = '$id_ligne' and kpi_name = '$kpi_name'";
                    // 23/09/2010 - MPR : Nettoyage du fichier - Utilisation de DataBase::getConnection();
                    $reson = $_db->execute($sql);
                }
            }
        }
    }
}

/*on parcourt tous les groupes de tables*/
// 23/09/2010 - MPR : Nettoyage du fichier - Utilisation de DataBase::getConnection();
$_db = DataBase::getConnection();

$query="select id_ligne from sys_definition_group_table";
// 23/09/2010 - MPR : Nettoyage du fichier - Utilisation de DataBase::getConnection();
$res=$_db->execute($query);

while( $row = $_db->getQueryResults($res,1) )
	$ids[]=$row['id_ligne'];

foreach($ids as $id)
{
	displayInDemon("Traitement du group table :".$id."<br>",'title');
	$id_group_table=$id;
	$query = "UPDATE sys_definition_kpi
			set edw_group_table='edw_mixed'
			where edw_group_table!='edw_mixed' and numerator_denominator='mixed'";
	displayInDemon("Creation et Suppression<br>");
	detect_new_field_w($id_group_table);
    // 23/09/2010 - MPR : Nettoyage du fichier - Utilisation de DataBase::getConnection();
	$_db->execute($query);
}

// 22/02/2012 SPD1 : Give a read only access to user "read_only_user" on all tables
displayInDemon("Give read only access to user read_only_user on all tables", 'title');
displayInDemon("Start");
ProductModel::setReadOnlyUserAccess();
displayInDemon("Finished !");

// 30/11/2009 BBX
// Ajout VACUUM + REINDEX des tables de RAW et de KPI. BZ 12983
// sys_field_reference
// 23/09/2010 - MPR : Nettoyage du fichier - Utilisation de DataBase::getConnection();
displayInDemon("Vacuum sys_field_reference<br>","title");
$query = "VACUUM FULL VERBOSE ANALYZE sys_field_reference";
$_db->execute($query);
$query = "REINDEX TABLE sys_field_reference";
$_db->execute($query);
$query = "VACUUM VERBOSE ANALYZE sys_field_reference";
$_db->execute($query);
// sys_definition_kpi
displayInDemon("Vacuum sys_definition_kpi<br>");
$query = "VACUUM FULL VERBOSE ANALYZE sys_definition_kpi";
$_db->execute($query);
$query = "REINDEX TABLE sys_definition_kpi";
$_db->execute($query);
$query = "VACUUM VERBOSE ANALYZE sys_definition_kpi";
$_db->execute($query);
// FIN BZ 12983

$time_finish=time();
$duree=$time_finish-$time_start;
displayInDemon("duree :".$duree." secondes<br>");
?>
