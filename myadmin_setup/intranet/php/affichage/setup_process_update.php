<?php
/*
	30/11/2009 GHX
		- Reprise des modifs de RBL sur la // des process
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
	- maj 13/03/2008, benoit : correction du bug 4099

	- maj 19/03/2008, benoit : ajout de la variable '$message_type' indiquant le type du message ("info" ou "error")
	- maj 19/03/2008, benoit : modification de la chaine renvoyée. On inclut maintenant, en plus du message, son type
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
*	MàJ 10/08/2007 - Jérémy : Modification du message d'alerte en cas de conflit entre 2 process (BUG 641)
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
	- maj 19/03/2007 Gwénaël : avant d'enregistrer les modifications de l'utilisateur on vérifie si aucun process ne se lanceront en même temps
		1 ) Traitement du formulaire et création des requêtes SQL pour faire la mise à jour
		2 ) Récupère les données des autres process dans la base (ceux dont l'utilisateur ne peut pas modifier) => SUPPRIMER suite à la maj du 10/05/2007
		3 ) Calcul toutes les fois où les process seront exécutés dans une journée
		4 ) Comparaison des process pour voir si certains se lanceront en même temps + création du message d'alert sur le premier conflit trouvé
		5 ) Enregistre les requêtes créées dans l'étape 1 si aucun message d'alert sinon affiche le message
	- maj 10/05/2007 Gwénaël : modification pour comparer uniquement les process visibles et qui sont à ON
*/
?>
<?php
/**
 * Supprime, crée, modifie les connections aux OMC / flat file
 */
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
require_once $repertoire_physique_niveau0 . "class/Scheduler.php";

// Connexion à la base du produit
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection($_POST['product']);

// Déclaration du tableau qui contiendra les requêtes
$queryArray = Array();

//
// 1 ) On traite les données issues du formulaire et on crée les requetes SQL correspondantes aux données pour faire la mise à jour dans la base
//
$processArray = Array();

// 19/03/2008 - Modif. benoit : ajout de la variable '$message_type' indiquant le type du message ("info" ou "error")
$message_alert	= '';
$message_type	= '';

// Gestion spécifique pour le Produit Blanc
if( ProductModel::isBlankProduct( $_POST['product'] ) )
{
    $processId = $_POST['process_id'];
    $on_off    = 0;

    if( isset( $_POST['process'] ) && $_POST['process'][$processId]['on_off'] == 1 )
    {
        $on_off = 1;
    }

    // Pour ce produit, il y a uniquement le on_off à gérer
    $queryArray[] = "UPDATE sys_definition_master SET on_off = {$on_off} WHERE master_id = {$processId}";
}
else
{
    foreach($_POST['process'] as $masterId => $values)
    {
        // Récupération des valeurs de la time period
        $utps_h = $values['time_period']['hour'];
        $utps_mn = $values['time_period']['minute'];

        // Traitement de la Time Period
        if ($values['time'] == 'D') {
            $utps_h = 0;
            $utps_mn = 1440;
        } elseif ($values['time'] == 'H') {
            $utps_h = 0;
            $utps_mn = 60;
        } else {
            $utps_mn = 60 * $utps_h + $utps_mn;
            $utps_h = 0;
        }

        // Verification sur les valeurs des minutes (faut que ça soit dans [1 , 1440])
        if ($utps_mn > 1439) {
            $utps_mn = 1440;
        }
        if ($utps_mn < 1) {
            $utps_mn = 1;
        }

        // Récupération des valeurs de l'offset
        $offset_h = $values['offset']['hour'];
        $offset_mn = $values['offset']['minute'];

        // Traitement de l'offset
        $offset = 60 * $offset_h + $offset_mn;

        // Tratement on_off
        $on_off = (isset($values['on_off'])) ? 1 : 0;

        // Enregistrement des données
        $queryArray[] = "UPDATE sys_definition_master
        SET utps = {$utps_mn},
            offset_time = {$offset},
            on_off = {$on_off}
        WHERE master_id = {$masterId}";

        // Sauvegarde des valeurs
        $processArray[$masterId] = Array('utps_mn'=>$utps_mn,'offset'=>$offset,'on_off'=>$on_off);
    }

    //
    // 2 ) Pour chaque process on calcule toutes les fois où il sera exécuté dans une journée
    //
    foreach($processArray as $master_id => $on_process) {
        // modif 10/05/2007
            // Si le process est à 0, celui-ci ne sera pas lancé donc on ne le compare pas
        if ($on_process['on_off'] == 0) continue;

        $time = $offset = $on_process['offset'];
        $utps = $on_process['utps_mn'];
        $lauch_process[$master_id][] = $time;//premier lancement du process qui correspond à la valeur de l'offset

        if($utps == 0 ) continue;

        if($utps < 60) {//Process lancés toutes les X minutes
            $time += $utps;
            $compteur = 1;
            $hour = false;
            while($time < 1440) {
                if( $hour && (($time-$offset) % $utps) == 0)
                    $lauch_process[$master_id][] = $time;
                elseif(!$hour)
                    $lauch_process[$master_id][] = $time;

                $time += $utps;
                if($time/60 >= $compteur) {
                    $time = 60 * $compteur + $offset;
                    $compteur++;
                    $hour = true;
                }
            }
        }
        elseif($utps < 1440) {//Process lancés toutes les X heures Y minutes
            $time += $utps;
            while($time < 1440) {
                $lauch_process[$master_id][] = $time;
                $time += $utps;
            }
        }
        else {//Process lancé 1 fois par jour
            $lauch_process[$master_id][] = $time;
        }
    }

    //
    // 3 ) On regarde si des process risquent de tomber en même temps (on compare process par process afin de savoir lequels sont lancés en même temps)
    //       Un message d'alert est crée dès que deux process démarrent en même temps
    //

    // 14:18 30/11/2009 GHX
    // Reprise des modifs sur la // des process
    $scheduler = new Scheduler();

    // modif 10/05/2007 Gwénaël
    // Si aucun process a ON => rien à comparer
    if ( count($lauch_process) > 0) {
        $master_ids = array_keys($lauch_process);
        $nb_process = count($master_ids);
        for($num_id = 0 ;$num_id < $nb_process - 1; $num_id++) {

            $process = $lauch_process[$master_ids[$num_id]];
            for($num_id2 = $num_id + 1; $num_id2 < $nb_process; $num_id2++) {

                $process2 = $lauch_process[$master_ids[$num_id2]];
                //Calcul l'intersection des 2 tableaux
                $result_intersect = array_intersect($process, $process2);

                // >>>>>>>>>>
                // 14:18 30/11/2009 GHX
                // Reprise des modifs sur la // des process
                $process1 = new Process($master_ids[$num_id]);
                $process2 = new Process($master_ids[$num_id2]);

                $process1->checkCompatibleProcessus();
                if ($process1->isCompatible($process2)){
                    continue;	//Both process are compatible, verification is complete
                }
                // <<<<<<<<<

               //Si le tableau contient des valeurs c'est que les deux process se lanceront au moins 1 fois en même temps
               if(count($result_intersect) > 0) {

                    //Récupère le nom correspondant aux ids masters des deux process
                    $query = "
                        SELECT master_name
                        FROM sys_definition_master
                        WHERE master_id IN (".$master_ids[$num_id].", ".$master_ids[$num_id2].");";
                    $result_query = $database->execute($query);

                    //MàJ 10/08/2007 - Jérémy : Amélioration du message de conflit pas clair (bug 641)
                    $row = $database->getAll($query);
                    $process_1 = $row[0]['master_name'];
                    $process_2 = $row[1]['master_name'];

                    //Si la première valeur est inférieur à 60, les deux process ce lanceront toujours en même temps sur une période d'une heure aux minutes indiquées
                    $first_value = current($result_intersect);
                    if( $first_value < 60 ) {
                        $time = $first_value."mn";
                    } else {//sinon les deux process se lanceront en même temps aux heures indiquées
                        $time = sprintf('%02dH%02d ',($first_value/60),($first_value%60));
                    }
                    $message_alert	= __T('A_TASK_SCHEDULER_PROCESS_CONFLICT_BETWEEN_2_PROCESS',$process_1,$process_2,$time);
                    $message_type	= "error";
                    break;
                }
                if(!empty($message_alert)) break;
            }
            if(!empty($message_alert)) break;
        }
    }
}
//
// 4 ) On vérifie si le message d'alert est vide (les process ne se lanceront jamais en même temps)
//          si oui on exécute les requêtes
// 23/06/2009 BBX : on sauvegarde malgré l'avertissement
if( $message_type !== 'error')
{
        // Mets à jour la table sys_definition_master_ref à partir de la table sys_definition_master
        $queryArray[] = "TRUNCATE sys_definition_master_ref";
        $queryArray[] = "INSERT INTO sys_definition_master_ref SELECT * FROM sys_definition_master";

	foreach($queryArray as $query) {
		$database->execute($query);
	}
}
if(empty($message_alert)) {
	$message_type	= "info";
	$message_alert	= __T('A_SETUP_PROCESS_PARAMETERS_SAVED');
}

// 13/03/2008 - Modif. benoit : on affiche simplement le message d'alerte au lieu de renvoyer une alerte Javascript
// 19/03/2008 - Modif. benoit : modification de la chaine renvoyée. On inclut maintenant, en plus du message, son type
echo '{"message_alert":\''.$message_alert.'\', "message_type":\''.$message_type.'\'}';

/*
// Affichage le message comme quoi les process ont été enregistré ou qu'il y en a qui se lanceront en même temps
echo "<script>";
echo "alert('".$message_alert."');";
echo "//window.location=\"setup_process.php?nocache=".date('U')."\";";
echo "</script>";
*/
exit;
?>