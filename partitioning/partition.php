<?php
// CLI mode only
if(isset($_SERVER['HTTP_USER_AGENT'])) {
    echo "CLI mode only\n";
    exit;
}

// Email parameter
$email = empty($argv[1]) ? '' : $argv[1];

// Scripts nécessaires
include_once dirname(__FILE__).'/../php/environnement_liens.php';
include_once REP_PHYSIQUE_NIVEAU_0.'class/SSHConnection.class.php';
include_once REP_PHYSIQUE_NIVEAU_0.'class/libMail.class.php';

// Connexion à la base de données locale
$databaseLocal = Database::getConnection();

// Infos produit
$productModel = new ProductModel(ProductModel::getProductId());
$productInfos = $productModel->getValues();

// Création fichier de parametres
$f = fopen('/tmp/parameters_'.$productInfos['sdp_directory'].'.txt','w+');
fwrite($f, 'connexion string = dbi:Pg:dbname='.$productInfos['sdp_db_name']."\n");
fwrite($f, 'connexion user = postgres'."\n");
fwrite($f, 'max threads = '.PartitioningActivation::getMaxThreads()."\n");
fwrite($f, 'debug = 1'."\n");
fclose($f);

// Partitioning command
$command = REP_PHYSIQUE_NIVEAU_0.'modules/partitioning/migration.pl /tmp/parameters_'.$productInfos['sdp_directory'].'.txt';
$command .= ' >> /tmp/'.$productInfos['sdp_directory'];

// Executing
exec($command, $output, $returnVar);

// Ajout du résultat d'éxécution au fichier
// 28/07/2011 BBX
// Ajout d'un retour chariot avant le code d'erreur
// BZ 22771
$f = fopen('/tmp/'.$productInfos['sdp_directory'],'a+');
fwrite($f,"\n".$returnVar."\n");
fclose($f);

// Email
if(!empty($email))
{
    $systemName	= get_sys_global_parameters('system_name');
    $mailReply  = get_sys_global_parameters('mail_reply');
    $mail = new Mail('html');
    $mail->From("$systemName <$mailReply>");
    $mail->ReplyTo($mailReply);
    $mail->To($email);
}

// Si la migration a réussi
if($returnVar == "0")
{
    // On modifie les steps
    $queryVacuum = "UPDATE sys_definition_step SET on_off = 0 WHERE step_name = 'Vacuum'";
    $queryCP = "UPDATE sys_definition_step SET on_off = 1 WHERE script = '/scripts/create_partitions.php'";
    $databaseLocal->execute($queryVacuum);
    $databaseLocal->execute($queryCP);

    // Et on applique le trigger sur toutes les tables de données
    // 27/06/2011 BBX
    // Modification de la requête qui récupère les tables de données
    // BZ 22812
    $queryMasterTables = "SELECT c.oid, c.relname
        FROM pg_class c, pg_tables t
        WHERE c.relname = t.tablename
        AND t.schemaname = 'public'
        AND relname LIKE 'edw_%_axe1_%'
	AND relname !~ '[0-9]+$'
        ORDER BY c.relname;";
    $result = $databaseLocal->execute($queryMasterTables);
    while($row = $databaseLocal->getQueryResults($result,1)) 
    {
        // data table
        $dataTable = $row['relname'];
        
        // 24/10/2011 BBX
        // BZ 24386 : Removing indexes
        $listIndexes = $databaseLocal->getIndexes($dataTable);
        if(count($listIndexes) > 0)
        {
            $databaseLocal->execute("BEGIN;");
            foreach($listIndexes as $indexName => $indexDef)
                $databaseLocal->execute("DROP INDEX IF EXISTS {$indexName};");
            $databaseLocal->execute("COMMIT;");
        }
        
        // Creating Trigger
        $queryTrigger = "CREATE TRIGGER {$dataTable}_trig_lock BEFORE INSERT OR UPDATE OR DELETE ON {$dataTable}
        FOR EACH ROW EXECUTE PROCEDURE lock_data_tables();";
        $databaseLocal->execute($queryTrigger);
    }

    if(!empty($email))
    {
        // Mail managemant
        $message = __T('A_SETUP_PARTITIONING_EMAIL_SUCCESS',$productInfos['sdp_label'],$productInfos['sdp_ip_address'],$productInfos['sdp_directory']);
        $mail->Subject($productInfos['sdp_label'].' successfully partitioned');
        $mail->Body($message);
    }
}
else
{
    if(!empty($email))
    {
        // Mail managemant
        $message = __T('A_SETUP_PARTITIONING_EMAIL_FAILED',$productInfos['sdp_label'],$productInfos['sdp_ip_address'],$productInfos['sdp_directory'],$mailReply);
        $mail->Subject('Partitioning failed on '.$productInfos['sdp_label']);
        $mail->Body($message);
    }
}

if(!empty($email)) {
    $mail->Send();
}
?>
