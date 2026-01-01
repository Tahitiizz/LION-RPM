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


include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once($repertoire_physique_niveau0."php/database_connection.php");
include_once($repertoire_physique_niveau0."php/edw_function.php");


$system_name=get_sys_global_parameters("system_name");
$attente=300;
$query="select value from sys_global_parameters where parameters='timer_killq_br'";
$result =pg_query($database_connection, $query);
$nombre_resultat =pg_num_rows($result);

if ($nombre_resultat>0) {
	$row = pg_fetch_array($result,0);
	$attente= $row["value"];
} else {
	$attente=300;
}

for ($k=0;$k<3;$k++){

	//$database_connection = pg_connect("port=$Aport dbname=$DBName user=$AUser password=$APass");
	
	unset($pid_requete);
	$query="select procpid,current_query from  pg_stat_activity where (to_char(now()-query_start,'hh24')::integer+to_char(now()-query_start,'mi')::integer*60+
	to_char(now()-query_start,'ss')::integer*60)>$attente and current_query like '%builder_report%' ";
	
	echo"$query\n";
	unset ($pid_requete);
	
	
	$result =pg_query($database_connection, $query);
	$nombre_resultat =pg_num_rows($result);
	
	if ($nombre_resultat>0) {
		for ($i = 0;$i < $nombre_resultat;$i++) {
			$row = pg_fetch_array($result, $i);
	        $pid_requete[$i]= $row["procpid"];
	        $pid_requete_current_query_array=explode(';',$row["current_query"]);
	        $pid_requete_current_query[$i]=$pid_requete_current_query_array[2];
		}
	}
	
	echo "<br>*******************************************<br>\n";
	var_dump($pid_requete);
	var_dump($pid_requete_current_query);
	echo "<br>*******************************************<br>\n";
	
	for ($q=0;$q<count($pid_requete);$q++) {
		echo "<font color='#ff0000'><b>Kill builder report query</b></font><br>";
		if (strlen($pid_requete[$q])>0) {
			sys_log_ast("Warning","$system_name","Kill Query","Canceling user query from the Builder Report:Timer out","support_1", "");
	
			echo "commande  kill -2 $pid_requete[$q]<br>";
			exec("kill -2 $pid_requete[$q]");
		}
	}
		
	/* Check queries_to_kill table: this table list SQL queries to kill */
	queries2kill($globalInstanceOfDatabase);
	
	$random=rand(10,30);
	sleep($random);
}



/* Check if there are queries to kill in the queries_to_kill table */
function queries2kill($db) {
		
	// Get queries to kill
	$sql = 'SELECT * FROM queries_to_kill';	
	$results = $db->getAll($sql);
	
	$lstIds = array();
	
	// Kill pids one by one
	if ($results) {
		echo "<br>********** check queries_to_kill table **********<br>\n";
		foreach($results as $row) {			
			// Search query PID from the search_string column value
			$sql = "SELECT procpid FROM pg_stat_activity WHERE current_query LIKE '%".$row['search_string']."%'";
			$pid = $db->getOne($sql);
			
			$lstIds[] = $row['id'];
			
			// If the query PID has been found send a TERM signal (15)
			if ($pid) {																							
				// bang ! bang ! ... fuuuu ...
				echo "commande  kill -15 $pid<br>";					// don't use a kill -9, the server and the memory don't like this !
	         	exec("kill -15 $pid");
	         }
		}
		
		// Remove killed pids from table
		$sql = 'DELETE FROM queries_to_kill WHERE id IN('.implode(',',$lstIds).')';		
		$db->execute($sql);		
		
		echo "<br>*******************************************<br>\n";
	}

}
?>
