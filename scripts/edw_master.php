<?php
/*
	30/11/2009 GHX
		- Reprise des modifs de RBL sur la parallisation des process plus modif suivantes
			- Modification de la fonction check_last_process_launched() concernant le tri
			-Suppression de bout code inutile en commentaire
			- Modification des echo (balise <li> inutile)
	16/12/2009 GHX
		- Correction du BZ 9936 [REC][T&A IU 4.0][COLLECTE]: lancement de deux process en même temps
 *  03/02/2011 NSE bz 20582 : lancement de plusieurs Collects pendant un Compute
 * 28/06/2011 NSE bz 22736 : problème de cast implicit
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
*	- maj 04/03/2008 christophe : quand le message 'Lancement via cron' est affiché dans le démon, on ajoute la date.
*
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	maj 14/08/2007 - Jérémy : Suppression de la fonction " update_config " qui fait appel au table SYS_DEFINITION_FAMILY_REF et SYS_DEFINITION_STEP_REF
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
<?php
/*
 *      - maj 19/03/2007 Gwénaël : Modification de la fonction ToSend, pour vérifier que le process ne se lance pas avant l'offset
 */
?>
<?php
/**
 * c'est dans ce fichier que les masters sont gérés
 *
 * @package master
 * @author Cyrille Gourvès
 */
include_once(dirname(__FILE__)."/../php/environnement_liens.php");
include_once("$repertoire_physique_niveau0"."php/database_connection.php");
include_once("$repertoire_physique_niveau0"."php/edw_function.php");

require_once $repertoire_physique_niveau0 . "class/Scheduler.php";

/**
 * classe de gestion des masters
 */
class edw_master {
	// 14:30 16/12/2009 GHX
	// BZ 9936
	private $checkAuto = false;
	
    /**
     * le constructeur de la classe
     *
     * @param string $MasterType le type du master récupéré dans sys_definition_master
     */
    function edw_master($MasterType)
    {
        global $database_connection;

        $this->master_id = $MasterType;
        $this->compute_mode = get_sys_global_parameters("compute_mode");
        $this->master_name = $this->get_name($MasterType);
        $this->master_proprietes();
        $this->master_start_time = date('YmdHi', time());
        // echo "id du master :".$this->master_id."<br>";
    }

	/**
	 * Spécifie si l'on doit vérifier aussi les scripts qui sont en attende d'être lancé en mode auto
	 *
	 *	16/12/2009 GHX
	 *		- BZ 9936
	 *
	 * @param boolean $checkAuto
	 */
	function setCheckAuto ($checkAuto) 
	{
		$this->checkAuto = $checkAuto;
	}
    function process($config)
    {
        $do_it = true;
        $do_it_twice = true;
        $do_it_auto = true;
        
        $scheduler = new Scheduler();	
        //check if the process we try to start is compatible with running process
		$do_it = !$scheduler->processusCanRun(new Process($this->master_id), $this->checkAuto);

        // en mode daily, on doit pouvoir avoir par exemple 2 retrieve qui se lancent à plusieurs heures d'intervalles sans compute
        if ($this->compute_mode == "hourly") {
            $do_it_twice = $this->check_last_process_launched();
        } else {
            $do_it_twice = false;
        }
		
		// 14:53 16/12/2009 GHX
		// BZ 9936
        $do_it_auto = false;//$this->check_process_launch_auto();
		
        if (!$do_it and !$do_it_twice and !$do_it_auto) {
            // récupération de la succession de familles à exécuter pour ce master
			//maj 14/08/2007 - Jérémy
            // if ($config == "crontab")
                // $this->update_config();
            $this->fam_ids = $this->get_families($this->master_id);
            if (count($this->fam_ids) > 0) {
                // mise à jour process en cours
                $this->update_sys_process_encours("encours");
                // update de la table log
                $this->update_log("encours");
                // insertion dans la table sys_family_track
                $this->ins_family_ids($this->fam_ids, $this->master_id);
                echo "<pre><font color=red ><b>" . "Time stamp : " . date('r') . "" . " -> Lancement master $this->master_name</b></font><br>";
            }
        } else {
            $this->update();
        }
    }

    function update()
    {
        // si toutes les familles pour un master sont terminées, on déclare ce master terminé :
        if ($this->check_finished_families($this->master_id)) {
            $this->update_sys_process_encours("done");
            $this->update_log("done");
            $this->clean($this->master_id);
            // echo "$this->id terminé<br>";
        }
    }

    function get_name($id)
    {
        global $database_connection;
        // 28/06/2011 NSE bz 22736 : problème de cast implicit
        $query = "select master_name from sys_definition_master where master_id=$id";
        $res = pg_query($database_connection, $query);
        while ($row = pg_fetch_array($res))
        $name = $row[0];
        return $name;
    }

    function clean($master)
    {
        global $database_connection;
        $query = "delete from sys_family_track where master_id='$master'";
        pg_query($database_connection, $query);
    }

    function check_finished_families($master)
    {
        global $database_connection;
        $query1 = "select count(*) from sys_family_track where master_id='$master'";
        $res1 = pg_query($database_connection, $query1);
        $row1 = pg_fetch_array($res1);
        $query2 = "select count(*) from sys_family_track where master_id='$master' and done='true'";
        $res2 = pg_query($database_connection, $query2);
        $row2 = pg_fetch_array($res2);
        if ($row1[0] == $row2[0] && $row1[0] != 0)
            return true;
        else
            return false;
    }

    /**
     * retourne la succession de familles à exécuter pour le master $MasterType
     *
     * @return array tableau d'ids de familles
     * @param string $MasterType tableau d'identifiants de familles
     */
    function get_families($Master_id)
    {
        global $database_connection;
        $query = "select family_id from sys_definition_family where master_id='$Master_id'
                        and on_off=1 order by ordre";
        $res = pg_query($database_connection, $query);
        while ($row = pg_fetch_array($res)) {
            $fam_ids[] = $row[0];
        }
        return $fam_ids;
    }

    /**
     * insère dans sys_family_track les éléments de $ids
     *
     * @return array
     * @param array $ids tableau d'identifiants de familles
     * @param string $MasterType type du master
     */
    function ins_family_ids($ids, $MasterType)
    {
        global $database_connection;
        $date = date('YmdHi', time());
        for($i = 0;$i < count($ids);$i++) {
            $query = "insert into sys_family_track(family_id,master_id,family_order,encours,done,date) values ('$ids[$i]','$MasterType','$i','false','false','$date')";
            pg_query($database_connection, $query);
        }
    }

    /**
     * récupère les propriétés du master (resolution time) dans la table sys_definition_master
     */
    function master_proprietes()
    {
        global $database_connection;
        $query = "select * from sys_definition_master where master_id='$this->master_id'";
        $result = pg_query($database_connection, $query);
        if (pg_last_error() != '') {
            echo pg_last_error() . " " . $query . "<br>";
        }

        $nombre_resultat = pg_num_rows($result);
        for ($i = 0;$i < $nombre_resultat;$i++) {
            $row = pg_fetch_array($result, $i);
            $this->master_tr = $row['utps'];
        }
    }

    /**
     * renvoie true si un master est lancé via le mode auto=true dans la table sys_definition_master
     *
     * @return bool
     */
    function check_process_launch_auto()
    {
        global $database_connection;
        $query = "select master_id from sys_definition_master where  auto='t' and master_id<>$this->master_id";
        $result = pg_query($database_connection, $query);
        if (pg_num_rows($result) > 0) {
            echo "Un process en Mode Auto doit être exécuté<br>";
            return true;
        } else {
            return false;
        }
    }
    // fonction qui recupere l'identifiant du dernier process execute pour les process à on_off=1 (cela exclus les process qui sont lancés par d'autres)
    function check_last_process_launched()
    {
        global $database_connection;
		// 14:04 30/11/2009 GHX
		// Le tri se fait sur la colonne timestamp au lieu de l'OID
        // 09/06/2011 BBX -PARTITIONING-
        // Correction des casts
        $query = "SELECT process FROM sys_process_encours WHERE process in (select distinct master_id::text from sys_definition_master where on_off=1) ORDER BY timestamp DESC LIMIT 1";
        $result = pg_query($database_connection, $query);
        if (pg_num_rows($result) > 0) {
            $row = pg_fetch_array($result, 0);
            $process = $row[0];
            if ($process == $this->master_id) {
                // le dernier process lancé correspond au process courant
                // 03/02/2011 NSE bz 20582 : lancement de plusieurs Collects pendant un Compute
                // on vérifie si un autre process est en cours
                // 27/06/2011 BBX
                // Correction du cast de la requête
                // BZ 22783
                $query = "SELECT process FROM sys_process_encours WHERE encours = 1 AND done = 0 AND process <> '".$this->master_id."'";
                $result = pg_query($database_connection, $query);
                // si un autre process est en cours, on autorise plusieurs lancements successifs
                if (pg_num_rows($result) > 0) {
                    $row = pg_fetch_array($result, 0);
                    echo "Le process ".$this->master_id." est le dernier process à avoir été lancé. On ne devrait pas le lancer 2 fois de suite.<br> Mais un autre process étant en cours (".$row[0]."), on vérifie s'il est compatible pour tenter de le relancer tout de même.<br>";
                    // la vérification de compatibilité entre les process est effectuée ailleurs
                    return false;
                }
                echo "Le process $process ne peut pas être lancé 2 fois de suite<br>";
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * met à jour la table sys_process_log pour le master courant
     */
    function update_log($type)
    {
        global $database_connection;
        // update de la table sys_process_log
        $now = date('YmdHi', time());
        if ($type == "encours")
            $query = "INSERT INTO sys_process_log (type, master, utps, date, encours,done)
                                VALUES ('master','$this->master_name','$this->master_tr','$now','true','false')";
        else
            $query = "INSERT INTO sys_process_log (type, master, utps, date, encours,done)
                                VALUES ('master','$this->master_name','$this->master_tr','$now','false','true')";
        pg_query($database_connection, $query);
        if (pg_last_error() != '') {
            echo pg_last_error() . " " . $query . "<BR>\n";
        }
    }

    /**
     *
     * @param string $done met à jour la table sys_process_encours
     */
    function update_sys_process_encours($done)
    {
        global $database_connection;
        // update de la table sys_process_encours
        switch ($done) {
            case "encours":
                $now = date('YmdHi', time());
                $query = "INSERT INTO sys_process_encours (process,utps,date,encours,done) VALUES ('$this->master_id','$this->master_tr','$now','1','0')";
                pg_query($database_connection, $query);
                if (pg_last_error() != '') {
                    echo pg_last_error() . " " . $query . "<BR>\n";
                }
                break;

            case "done":
                $query = "update sys_process_encours set encours='0',done='1' where process='$this->master_id'";
                pg_query($database_connection, $query);

                echo "<font color=green><b>" . "Time stamp : " . date('r') ."->master $this->master_name terminé</b></font><br>";
                break;
        }
    }

}
// ///////////////////////
// debut du script     //
// //////////////////////
// 15:13 16/12/2009 GHX
// BZ 9936
$query = "select * from sys_definition_master ORDER BY auto DESC, ordre DESC"; // where on_off=1";
$result = pg_query($database_connection, $query);
while ($row = pg_fetch_array($result)) {
    $param = $row["master_id"];
    $utps = $row["utps"];
    $offset_time = $row["offset_time"];
    $auto = $row["auto"];
    $on_off = $row["on_off"];
    
    $do_it = ToSend($utps, $offset_time);
    $master = new edw_master($param);
    // true vaut "t" en string
	
    if ($auto == "t") {
         echo "<br><font color='#AA0033'><b>->Lancement  via auto=true</b></font><br>";
        $master->process("manuel");
        $query2 = "update sys_definition_master set auto='false' where master_id='$param'";
        pg_query($database_connection, $query2);
    } else {
        if ($do_it && $on_off == '1') 
		{
			// maj 04/03/2008 christophe : quand le message 'Lancement via cron' est affiché dans le démon, on ajoute la date.
			echo "<br><font color='#00AA33'><b>-> Lancement  via cron </b> (".date('r').")</font><br>";
			$master->process("crontab");
        } 
		else 
		{
            $master->update();
        }
    }
	// 15:13 16/12/2009 GHX
	// BZ 9936
	$master->setCheckAuto(true);
}

/*
 *  - modif 19/03/2007 Gwénaël : rajoute d'une variable $delay_offset : pour le comparer avec l'offset passé en paramètre si ce dernier est supérieur à $delay_offset on ne doit pas lancé le process (le temps d'offset n'est pas fini)
 */
function ToSend($unite, $offset)
{
    $heure_courante = date("H", time());
    $minute_courante = date("i", time());
    if ($unite <= 60) {
        $tps =  $delay_offset = $minute_courante;
    } else {
		$delay_offset = ($heure_courante) * 60 + $minute_courante;
        $tps = 1440 + ($heure_courante) * 60 + $minute_courante;
    }
	if( ($offset <= $delay_offset) && (($tps - $offset) % $unite == 0) )
        return true;
    else
        return false;
}
?>