<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	- maj 08:39 16/11/2007 Gwénaël : ajout des champs nombre d'itération et période
*
	- maj 17/03/2008, benoit : prise en compte du nouveau champ d'activation/désactivation de l'alarme dans le formulaire
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
*
*
*	- 04/09/2007 christophe : par défaut, on sélectionne tous les éléments réseaux, même si l'utilisateur n'a pas ouvert la sélection des NA.
*	- 12/07/2007 christophe : correction d'un bug sur array_diff vide pour l'enregistrement des éléments réseaux.
*	- 11/07/2007 christophe : à la fin des traitements la redirection se fait en javascript, il faut la faire en php.
*	- 10/07/2007 christophe : gestion de la sauvegarde de la liste des éléments réseaux dans les alarmes.
*	- 19/06/2007 maxime : gestion de la sauvegarde de la liste des périodes temporelles hour et day exclues dans les alarmes.
*	05/05/2015 JLG Mantis 6470 : manage dynamic alarm threshold operand (min/max)
*/
?>
<?php

/*
  11/10/2006 xavier : la modification de la TA d'une alarme se répercute sur l'envoi de mail

  - moj 05/04/207 Gwénaël : modification pour prendre en compte le 3° l'axe dans l'enregistrement des alarmes (champs network = na . "_" . na_axe3)
*/

session_start();

include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
// BBX
//include_once($repertoire_physique_niveau0 . "php/database_connection.php");
//include_once($repertoire_physique_niveau0 . "php/edw_function.php");
include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");
include_once($repertoire_physique_niveau0 . "class/CbCompatibility.class.php");

$debug = get_sys_debug('traitement_alarm');
$debug = false;

if ($debug) {
	echo "\n<link rel='stylesheet' href='{$niveau0}css/global_interface.css' type='text/css'>";
	echo "\n<div class='debug'>";
	echo "\n	<table>	<tr><th colspan='2'>contenu de _POST</th></tr>";
	foreach ($_POST as $key => $val) echo "\n	<tr><td>$key</td><td><strong>$val</strong></td></tr>";
	echo "\n	</table>";
	echo "\n</div>";
}

// on définit les trois types de niveau critique
$critical_level = array('critical','major','minor');

$product		= $_POST['product'];
$family		= $_POST['family'];
$alarm_type	= $_POST['alarm_type'];

// on se connecte à la db
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$db = Database::getConnection($product);

// 05/05/2015 JLG : mantis 6470
$canManageThresholdOperand = CbCompatibility::canManageThresholdOperand($product);

$group_id = $db->getone("SELECT id_ligne	FROM sys_definition_group_table	WHERE family='$family'");

// si l'alarme est nouvelle, on génére un identifiant
if ($_POST['alarm_id'])	$alarm_id = $_POST['alarm_id'];
else {
	$prefixes = array(
		'alarm_static'		=>	'sdas',
		'alarm_dynamic'	=>	'sdad',
		'alarm_top_worst'	=>	'sdatw'
	);
	$alarm_id = $prefixes[$alarm_type].'.'.md5(uniqid(rand(), true));
}

// MP - On enregistre les périodes temporelles d'exclusion
if ($_POST['time_to_sel']=='day' or $_POST['time_to_sel']=='hour' or $_POST['time_to_sel']=='day_bh') {

	$time_exclusion_day = explode(";",__T('A_ALARM_DAY_OF_WEEK'));
	$time_exclusion_hour = array(	"00:00","01:00","02:00","03:00","04:00","05:00","06:00","07:00","08:00","09:00",
							"10:00","11:00","12:00","13:00","14:00","15:00","16:00","17:00","18:00","19:00",
							"20:00","21:00","22:00","23:00");
	// On enregsitre les jours exclus
	foreach ($time_exclusion_day as $val=>$day_label) {
		if ($_POST[$day_label]!==NULL and $_POST[$day_label]!='') {
			$day_excluded[$day_label] = $val;
			// On enregistre les heures exclues pour chaque jour
			if ($_POST['time_to_sel']=='hour')
				foreach ($_POST['hour'][$val] as $k=>$v)
					if ($v!=NULL and $v!="")
						$hour_to_exclude[$val][$k] = substr($v,0,2);
		}
	}
}

// si la case discontinuous n'est pas cochée, discontinuous vaut zéro
if ($_POST['discontinuous'] == '1')	$discontinuous = 1;
else							$discontinuous = 0;

// 17/03/2008 - Modif. benoit : prise en compte du nouveau champ d'activation / désactivation de l'alarme dans le formulaire
if (isset($_POST['alarm_activated']) && $_POST['alarm_activated'] == 1)
		$alarm_activated = 1;
else		$alarm_activated = 0;

// on affiche les champs communs à toutes les alarmes.
// seuls les champs additionnels remplis seront affichés.
// deux variables de type tableau récupèrent le nom et le type des raw ou kpi
// utilisés comme champs additionnels.
for ($nb_additional=1;$nb_additional<6;$nb_additional++) {
	if (($_POST['additional_field_type'.$nb_additional] != 'makeSelection') && ($_POST['additional_field'.$nb_additional] != 'makeSelection')) {
		$tab_additional_field_type[]	= $_POST['additional_field_type'.$nb_additional];
		$tab_additional_field[]		= $_POST['additional_field'.$nb_additional];
	}
}

$query = "DELETE FROM sys_definition_alarm_exclusion WHERE alarm_id = '$alarm_id' and type_alarm = '$alarm_type'";
// ???? et là on en fait rien de cette query ?  - SLC

//------------------------- s'il s'agit d'une alarme statique -------------------------------
if ($alarm_type == 'alarm_static') {

	for ($nb_trigger=1; $nb_trigger<6; $nb_trigger++) {
		if (($_POST['trigger_field'.$nb_trigger] != 'makeSelection') && ($_POST['trigger_type'.$nb_trigger] != 'makeSelection')) {
			for ($nb_critic=0;$nb_critic<count($critical_level);$nb_critic++) {
				// on enregistre le type, le nom et les valeurs que prend le trigger
				// pour les seuils de criticité 'critical', 'major' et 'minor'.
				$tab_trigger_operand[$critical_level[$nb_critic]][] = $_POST['trigger_operand_'.$critical_level[$nb_critic].$nb_trigger];
				$tab_trigger_value[$critical_level[$nb_critic]][] = $_POST['trigger_value_'.$critical_level[$nb_critic].$nb_trigger];
			}
			$tab_trigger_type[] = $_POST['trigger_type'.$nb_trigger];
			$tab_trigger_field[] = $_POST['trigger_field'.$nb_trigger];
		}
	}

	// on efface les anciennes versions de l'alarme si elles existent
	$db->execute("DELETE FROM sys_definition_alarm_static		WHERE alarm_id = '$alarm_id'");

	// on boucle autant de fois qu'il y a de triggers
	for ($i=0;$i<count($tab_trigger_field);$i++) {

		// pour chaque trigger, on boucle autant de fois qu'il y a de niveau de criticité
		for ($k=0;$k<count($critical_level);$k++) {

			// on récupère les données du trigger et la valeur du threshold
			$trigger_operand	= $tab_trigger_operand[$critical_level[$k]][$i];
			$trigger_value		= $tab_trigger_value[$critical_level[$k]][$i];
			$trigger_field		= $tab_trigger_field[$i];
			$trigger_type		= $tab_trigger_type[$i];

			// on n'insère pas les données du trigger si elles ne sont pas complètes
			if (($trigger_operand == "'none'") or ($trigger_value == '')) {
				$trigger_operand = null;
				$trigger_value = 'null';
				$trigger_field = 'null';
				$trigger_type = 'null';
			}

			// modif 08:40 16/11/2007 Gwen
			// Ajout des champs nb_iteration & period
			$nb_iteration = $_POST['nb_iteration_'.$critical_level[$k]];
			$period = $_POST['period_'.$critical_level[$k]];

			if ( empty($nb_iteration) || empty($period) ) {
				$nb_iteration = 1;
				$period       = 1;
			}

			// modif 05/04/2007 Gwénaël :  modification pour prendre en compte le 3° axe
			// 17/03/2008 - Modif. benoit : maj du champ "on_off" dans la requete d'insertion de l'alarme
			// on insère les informations de l'alarme statiques
                        // 10/05/2012 NSE bz 27128 : protection du commentaire pour supporter les '
			if ($trigger_value != 'null') {
				$new_alarm = array(
					'alarm_id'				=> $alarm_id,
					'alarm_name'			=> $_POST['alarm_name'],
					'network'				=> $_POST['net_to_sel'].(isset($_POST['hn_to_sel']) ? '_'.$_POST['hn_to_sel'] : '' ),
					'additional_field'		=> null,
					'additional_field_type'	=> null,
					'time'				=> $_POST['time_to_sel'],
					'id_group_table'		=> $group_id,
					'hn_value'				=> $hn_infos[0],
					'family'				=> $family,
					'client_type'			=> $client_type,
					'alarm_trigger_data_field'	=> $trigger_field,
					'alarm_trigger_type'		=> $trigger_type,
					'alarm_trigger_operand'	=> $trigger_operand,
					'alarm_trigger_value'	=> $trigger_value,
					'description'			=> str_replace("\\'", "''", $_POST['alarm_description']),
					'critical_level'			=> $critical_level[$k],
					'nb_iteration'			=> $nb_iteration,
					'period'				=> $period,
					'on_off'				=> $alarm_activated
				);

                                $db->autoExecute('sys_definition_alarm_static',$new_alarm,'INSERT');
			}
		}
	}
	
	// on supprime les informations de trigger dans $new_alarm car elles ne seront pas à ajouter pour les champs supplémentaires
	$new_alarm['alarm_trigger_data_field']	= null;
	$new_alarm['alarm_trigger_type']		= null;
	$new_alarm['alarm_trigger_operand']	= null;
	$new_alarm['alarm_trigger_value']		= null;
	$new_alarm['nb_iteration']			= null;
	$new_alarm['period']				= null;
	$new_alarm['critical_level']			= null;

	// on boucle autant de fois qu'il y a de champs additionnels
	for ($j=0;$j<count($tab_additional_field);$j++) {
		$new_alarm['additional_field']		= $tab_additional_field[$j];
		$new_alarm['additional_field_type']	= $tab_additional_field_type[$j];

		$db->autoExecute('sys_definition_alarm_static',$new_alarm,'INSERT');
	}
}


//------------------------ s'il s'agit d'une alarme dynamique -------------------------------

if ($alarm_type == 'alarm_dynamic') {

	for ($nb_critic=0;$nb_critic<count($critical_level);$nb_critic++) {
		$couleur='';
		if ($_POST['threshold_value_'.$critical_level[$nb_critic]] == '')
			$couleur=' style="color:red"';
	}
	
	for ($nb_critic=0;$nb_critic<count($critical_level);$nb_critic++) {
		$couleur='';
		if (($_POST['trigger_operand_'.$critical_level[$nb_critic]] == 'none') or ($_POST['trigger_value_'.$critical_level[$nb_critic]] == ''))
			$couleur=' style="color:red"';
	}
	
	// on efface les anciennes versions de l'alarme si elles existent
	$db->execute("DELETE FROM sys_definition_alarm_dynamic WHERE alarm_id = '$alarm_id'");
	
	// on boucle autant de fois qu'il y a de niveau de criticité
	for ($i=0;$i<count($critical_level);$i++) {
	
		// on récupère les données du trigger et la valeur du threshold
		$threshold_value	= $_POST['threshold_value_'.$critical_level[$i]];
		if ($canManageThresholdOperand) {
			$threshold_operand	= $_POST['threshold_operand_'.$critical_level[$i]];
		}
		$trigger_operand	= $_POST['trigger_operand_'.$critical_level[$i]];
		$trigger_value		= $_POST['trigger_value_'.$critical_level[$i]];
		$trigger_field		= $_POST['trigger_field'];
		$trigger_type		= $_POST['trigger_type'];
	
		// on n'insère pas les données du trigger si elles ne sont pas complètes
		if (($trigger_operand == "'none'") or ($trigger_value == '') or ($trigger_field == "'makeSelection'") or ($trigger_type == "'makeSelection'")) {
			$trigger_operand	= null;
			$trigger_value		= null;
			$trigger_field		= null;
			$trigger_type		= null;
		}
	
		// modif 08:41 16/11/2007Gwen
		// Ajout des champs nb_iteration & period
		$nb_iteration	= $_POST['nb_iteration_'.$critical_level[$i]];
		$period		= $_POST['period_'.$critical_level[$i]];
	
		if ( empty($nb_iteration) || empty($period) ) {
			$nb_iteration = 1;
			$period       = 1;
		}
		// modif 05/04/2007 Gwénaël :  modification pour prendre en compte le 3° axe
	
		// 17/03/2008 - Modif. benoit : maj du champ "on_off" dans la requete d'insertion de l'alarme
	
		// on insère les informations de l'alarme dynamique
		if ($threshold_value != '') {
			// 05/06/2012 NSE bz 27128 : protection du commentaire pour supporter les '
			$new_alarm = array(
				'alarm_id'				=> $alarm_id,
				'alarm_name'			=> $_POST['alarm_name'],
				'alarm_field'			=> $_POST['threshold_field'],
				'alarm_field_type'		=> $_POST['threshold_type'],
				'alarm_threshold'		=> $threshold_value,
				'network'				=> $_POST['net_to_sel'].(isset($_POST['hn_to_sel']) ? '_'.$_POST['hn_to_sel'] : '' ),
				'additional_field'		=> null,
				'additional_field_type'	=> null,
				'time'				=> $_POST['time_to_sel'],
				'id_group_table'		=> $group_id,
				'hn_value'				=> $hn_infos[0],
				'family'				=> $family,
				'discontinuous'			=> $discontinuous,
				'client_type'			=> $client_type,
				'alarm_trigger_data_field'	=> $trigger_field,
				'alarm_trigger_type'		=> $trigger_type,
				'alarm_trigger_operand'	=> $trigger_operand,
				'alarm_trigger_value'	=> $trigger_value,
				'description'			=> str_replace("\\'", "''", $_POST['alarm_description']),
				'critical_level'			=> $critical_level[$i],
				'nb_iteration'			=> $nb_iteration,
				'period'				=> $period,
				'on_off'				=> $alarm_activated
			);
			if ($canManageThresholdOperand) {
				$new_alarm['alarm_threshold_operand'] = $threshold_operand;
			}
			$db->autoExecute('sys_definition_alarm_dynamic',$new_alarm,'INSERT');
		}
	}
	
	// on supprime les informations de trigger dans $new_alarm car elles n'ont pas à être ajoutées pour les champs supplémentaires
	unset($new_alarm['alarm_field']);
	unset($new_alarm['alarm_field_type']);
	unset($new_alarm['alarm_threshold']);
	if ($canManageThresholdOperand) {
		unset($new_alarm['alarm_threshold_operand']);
	}

	$new_alarm['alarm_trigger_data_field']	= null;
	$new_alarm['alarm_trigger_type']		= null;
	$new_alarm['alarm_trigger_operand']	= null;
	$new_alarm['alarm_trigger_value']		= null;
	$new_alarm['nb_iteration']			= null;
	$new_alarm['period']				= null;
	$new_alarm['critical_level']			= null;

	// on boucle autant de fois qu'il y a de champs additionnels
	for ($j=0;$j<count($tab_additional_field);$j++) {
	
		// on récupère les données de l'additional field
		$new_alarm['additional_field']		= $tab_additional_field[$j];
		$new_alarm['additional_field_type']	= $tab_additional_field_type[$j];

		$db->autoExecute('sys_definition_alarm_dynamic',$new_alarm,'INSERT');
	}
}


//----------------------- s'il s'agit d'une top worst cell list -----------------------------

if ($alarm_type == 'alarm_top_worst') {
	
	// on efface les anciennes versions de l'alarme si elles existent
	$query = "DELETE FROM sys_definition_alarm_top_worst WHERE alarm_id = '$alarm_id'";
	$db->execute($query);
	
	// on récupère les données du trigger
	$trigger_operand	= $_POST['trigger_operand'];
	$trigger_value		= $_POST['trigger_value'];
	$trigger_field		= $_POST['trigger_field'];
	$trigger_type		= $_POST['trigger_type'];
	
	// on n'insère pas les données du trigger si elles ne sont pas complètes
	if (($trigger_operand == "'none'") or ($trigger_value == '') or ($trigger_field == "'makeSelection'") or ($trigger_type == "'makeSelection'")) {
		$trigger_operand	= null;
		$trigger_value		= null;
		$trigger_field		= null;
		$trigger_type		= null;
	}
	
	// modif 16:21 13/11/2007 Gwen
	// Ajout des champs nb_iteration & period
	$nb_iteration	= $_POST['nb_iteration_'];
	$period		= $_POST['period_'];
	
	if ( empty($nb_iteration) || empty($period) ) {
		$nb_iteration	= 1;
		$period		= 1;
	}
	
	// modif 05/04/2007 Gwénaël :  modification pour prendre en compte le 3° axe
	
	// 17/03/2008 - Modif. benoit : maj du champ "on_off" dans la requete d'insertion de l'alarme
	
	// on insère les informations de la top/worst cell list
        // 05/06/2012 NSE bz 27128 : protection du commentaire pour supporter les '
	$new_alarm = array(
		'alarm_id'				=> $alarm_id,
		'alarm_name'			=> $_POST['alarm_name'],
		'list_sort_field'			=> $_POST['sort_field'],
		'list_sort_field_type'		=> $_POST['sort_type'],
		'list_sort_asc_desc'		=> $_POST['sort_by'],
		'network'				=> $_POST['net_to_sel'].(isset($_POST['hn_to_sel']) ? '_'.$_POST['hn_to_sel'] : '' ),
		'additional_field'		=> null,
		'additional_field_type'	=> null,
		'time'				=> $_POST['time_to_sel'],
		'id_group_table'		=> $group_id,
		'hn_value'				=> $hn_infos[0],
		'family'				=> $family,
		'client_type'			=> $client_type,
		'alarm_trigger_data_field'	=> $trigger_field,
		'alarm_trigger_type'		=> $trigger_type,
		'alarm_trigger_operand'	=> $trigger_operand,
		'alarm_trigger_value'	=> $trigger_value,
		'description'			=> str_replace("\\'", "''", $_POST['alarm_description']),
		'nb_iteration'			=> $nb_iteration,
		'period'				=> $period,
		'on_off'				=> $alarm_activated
	);
	$db->autoExecute('sys_definition_alarm_top_worst',$new_alarm,'INSERT');
	
	// on enlève les valeurs qui ne doivent pas apparaitre dans les champs additionnels
	$new_alarm['list_sort_field']			= null;
	$new_alarm['list_sort_field_type']		= null;
	$new_alarm['list_sort_asc_desc']		= null;
	$new_alarm['alarm_trigger_data_field']	= null;
	$new_alarm['alarm_trigger_type']		= null;
	$new_alarm['alarm_trigger_operand']	= null;
	$new_alarm['alarm_trigger_value']		= null;
	$new_alarm['nb_iteration']			= null;
	$new_alarm['period']				= null;
	
	
	// on boucle autant de fois qu'il y a de champs additionnels
	for ($j=0;$j<count($tab_additional_field);$j++) {
	
		// on insère les champs additionnels de la top/worst cell list
		$new_alarm['additional_field']		= $tab_additional_field[$j];
		$new_alarm['additional_field_type']	= $tab_additional_field_type[$j];

		$db->autoExecute('sys_definition_alarm_top_worst',$new_alarm,'INSERT');
	}
}

// MP - On enregistre les périodes temporelles d'exclusion
// On génère un id pour la nouvelle période d''exclusion
// remarque SLC du 11/02/2009 : sys_definition_alarm_exclusion.id RESTE en int (pas de conversion en string)
$id = intval($db->getone("SELECT max(id) FROM sys_definition_alarm_exclusion"));
$id++;


// On supprime les enregistrements en base pour l'alarme
$db->execute("DELETE FROM sys_definition_alarm_exclusion WHERE id_alarm='$alarm_id' and type_alarm='$alarm_type'");
// On enregistre les périodes d'exclusion
if (count($day_excluded)>0) {
	$queries = array();
	foreach ($day_excluded as $key=>$val) {
		$id_day = $id;
		if ($_POST['time_to_sel'] == 'hour')		$time_to_sel = 'day';
		else								$time_to_sel = $_POST['time_to_sel'];
		// On enregistre les jours exclus qd la ta de l'alarme est day ou quand la ta est hour et qu'il y a au moins une heure exclue pour le jour en question
		if ((count($hour_to_exclude[$val])>0 and $_POST['time_to_sel']=="hour")or($_POST['time_to_sel']=="day")or($_POST['time_to_sel']=="day_bh")) {
			$queries[] = "
				INSERT INTO sys_definition_alarm_exclusion (id,id_alarm,type_alarm,ta,ta_value,id_parent) values (
					'$id_day',
					'$alarm_id',
					'$alarm_type',
					'$time_to_sel',
					'$val',
					0)	";
		}
		$id++;
		// On enregistre les heures exclues
		if ($_POST['time_to_sel']=='hour' and count($hour_to_exclude[$val])>0) {
			foreach ($hour_to_exclude[$val] as $k=>$v) {
				$queries[] = "
					INSERT INTO sys_definition_alarm_exclusion (id,id_alarm,type_alarm,ta,ta_value,id_parent) values (
						'$id',
						'$alarm_id',
						'$alarm_type',
						'hour',
						'$v',
						'$id_day')		";
				$id++;
			}
		}
	}
	@$db->execute(@implode(";",$queries));
}

// 11/10/2006 xavier
$query_update = "
	UPDATE sys_alarm_email_sender
	SET time_aggregation='{$_POST['time_to_sel']}'
	WHERE alarm_type='$alarm_type'
		AND id_alarm='$alarm_id'		";
$db->execute($query_update);


/*
 * 25/05/2010 BBX
 * Nouvel enregistrement de la sélection des éléments réseau
 */
// Gestion du type
$currentType = '';
switch(trim($alarm_type))
{
    case 'alarm_static':
        $currentType = 'static';
    break;
    case 'alarm_dynamic':
        $currentType = 'dynamic';
    break;
    case 'alarm_top_worst':
        $currentType = 'top_worst';
    break;
}
// Instanciation d'un objet Alarm Model
$alarmModel = new AlarmModel($alarm_id,$currentType,$product,$family);
// Suppression de la sélection précédente
$alarmModel->resetNetworkElementSelection();
// Ajout de la nouvelle sélection
if(isset($_SESSION['alarmsSessionArray']['ne_selection']) && !empty($_SESSION['alarmsSessionArray']['ne_selection']))
    $alarmModel->addNetworkElements($_SESSION['alarmsSessionArray']['ne_selection']);

// Destruction des variables de session
unset($_SESSION['alarmsSessionArray']);

// Vaccum des tables de conf
$alarmModel->vacuum();

//*/
/*
- 11/07/2007 christophe : à la fin des traitements la redirection se fait en javascript, il faut la faire en php.
En fonction du type d'alarme, on redirige l'utilisateur sur la liste des alarmes.
setup_static_alarm_index.php?no_loading=yes&id_menu_en_cours=31&family=ept
*/
$redir_file = '';
switch ($alarm_type) {
	case 'alarm_static' :
		$redir_file = 'setup_static_alarm_index.php';
	break;
	case 'alarm_dynamic' :
		$redir_file = 'setup_dyn_alarm_index.php';
	break;
	case 'alarm_top_worst' :
		$redir_file = 'setup_list_index.php';
	break;
	default :
		$redir_file = 'setup_static_alarm_index.php';
	break;
}

$redir_file .= "?product=$product&family=$family&no_loading=yes";
if ( !$debug ) {
	header("Location: $redir_file");
	exit;	
}

echo $db->displayQueries();
echo "<br/>poursuivre sur <a href='$redir_file'>$redir_file</a>";

?>
<script>
/* problème découvert lorsque l'on navigue avec la fenêtre principale avant de valider
une alarme. Pour éviter une erreur javascript, le rafaichissement (cause de l'erreur)
et la fermeture de la popup sont mis dans deux blocs séparés.*/
//history.go(-2);
</script>

