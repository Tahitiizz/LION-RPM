<?php
/**
* Script de migration CB 5.0 vers CB 5.1
*
* @author BBX
* @version CB 5.1.0.00
* @package T&A
* @since CB 5.1.0.00

04/08/2010 NSE bz 17166 : Sélection des NE perdue après migration 5.0 vers 5.1

*/
?>
<?php
/***********************
 * DATABSE CONNECTION
 **********************/
include_once('../../php/environnement_liens.php');
$database = Database::getConnection();

/***********************
 * ALARMES
 **********************/
// MIGRATION DE sys_definition_alarm_network_elements
// 1. récupération de la sélection actuelle
$networkElementSelection = array();
$query = "SELECT * FROM sys_definition_alarm_network_elements";
$result = $database->execute($query);
while($row = $database->getQueryResults($result,1))
{
	//04/08/2010 NSE bz 17166 : lst au lieu de ls
    if($row['lst_alarm_compute'] != 'all')
    {
        // Récupération du Network Level
        $queryNL = "SELECT network FROM sys_definition_".$row['type_alarm']."
            WHERE alarm_id = '".$row['id_alarm']."'";
        $networkLevel = $database->getOne($queryNL);
        // Récupération de la sélection
        $subQueryOperator = ($row['not_in'] == 1) ? 'NOT IN' : 'IN';
        $subQueryElements = explode('||',trim($row['lst_alarm_compute']));
        // 04/08/2010 NSE bz 17166 : correction de la requête
		$queryFetch = "SELECT eor_id
            FROM edw_object_ref
            WHERE eor_obj_type='".$networkLevel."' 
			AND eor_id ".$subQueryOperator." ('".implode("','",$subQueryElements)."')
            AND eor_on_off = 1";
        $resultNE = $database->execute($queryFetch);
        while($rowNE = $database->getQueryResults($resultNE,1))
        {
            $networkElementSelection[$row['id_alarm']][] = $rowNE['eor_id'];
			//04/08/2010 NSE bz 17166 : ajout du tableau des types
			$alarmType[$row['id_alarm']] = $row['type_alarm'];
		}
    }
}
// 2. destruction de la table
$database->execute("DROP TABLE sys_definition_alarm_network_elements");
// 3. reconstruction de la table
$queryCreate = "CREATE TABLE sys_definition_alarm_network_elements (
    alarm_id text not null,
    type_alarm text,
    na text,
    na_value text
)";
$database->execute($queryCreate);
// 4. réinsertion des éléments de la table
$database->execute("BEGIN");
$queryInsert = '';
foreach($networkElementSelection as $alarm_id => $networkElements)
{
    // Récupération du Network Level
	//04/08/2010 NSE bz 17166 : modification de la requête (suppression de $row)
    $queryNL = "SELECT network FROM sys_definition_".$alarmType[$alarm_id]."
        WHERE alarm_id = '".$alarm_id."'";
    $networkLevel = $database->getOne($queryNL);
    // Récupération du type
    $type = str_replace('alarm_','',$alarmType[$alarm_id]);
    // Insertion des éléments
    foreach($networkElements as $na_value)
    {
        $queryInsert .= "INSERT INTO sys_definition_alarm_network_elements
            (alarm_id,type_alarm,na,na_value)
            VALUES ('$alarm_id','$type','$networkLevel','$na_value');\n";

    }
}
$database->execute($queryInsert);
$database->execute("COMMIT");

/*******************
 * API T&A and API XPERT
 ******************/
// Modification des droits du fichier WSDL
chmod( REP_PHYSIQUE_NIVEAU_0.'api/ta.wsdl', 0777 );
chmod( REP_PHYSIQUE_NIVEAU_0.'api/xpert/XpertApi.wsdl', 0777 );
?>
