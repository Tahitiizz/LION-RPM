<?php

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include_once '../class/AlarmModel.class.php';

$alarm_model = new AlarmModel('');

switch ($_GET['action'])
{
	case "drop_alarm" :
		
		if ($alarm_model->dropAlarm($_GET['alarm_id'], $_GET['alarm_type'], $_GET['alarm_family'], $_GET['alarm_ta'])) {
			echo "deleted";
		}
		else 
		{
			echo "failed";
		}

	break;

	case "get_groups" :

		$all_groups_tmp	= $alarm_model->getApplicationGroups();
		$all_groups		= array();

		for ($i=0; $i < count($all_groups_tmp); $i++) {
			$all_groups[] = $all_groups_tmp[$i]['id']."|:|".$all_groups_tmp[$i]['name'];
		}

		$suscribers_groups_tmp	= $alarm_model->getAlarmSuscribers($_GET['alarm_id'], $_GET['alarm_type']);
		$suscribers_groups		= array();

		for ($i=0; $i < count($suscribers_groups_tmp); $i++) {
			$suscribers_groups[] = $suscribers_groups_tmp[$i]['id']."|:|".$suscribers_groups_tmp[$i]['name'];
		}

		$available_groups = implode('|s|', (array_diff($all_groups, $suscribers_groups)));

		echo "{'available':\"".$available_groups."\", 'suscribers':\"".implode('|s|', $suscribers_groups)."\"}";

	break;

	case "save_alarm_suscribers" :
		
		echo $alarm_model->saveAlarmSuscribers($_GET['alarm_id'], $_GET['alarm_ta'], $_GET['alarm_type'], explode('|s|', $_GET['suscribers_list']));

	break;

	case "get_type_values" :

		$type_values = $alarm_model->getTriggerTypeValues($_GET['family'], $_GET['type']);

		$array_values = array();

		foreach ($type_values[$_GET['type']] as $key=>$value) 
		{
			$array_values[] = $key."|field|".$value['label']."|field|".$value['label_complete'];
		}

		echo urlencode(implode('|column|', $array_values));

	break;

	case "get_ne" :

		$display_mode = ((!empty($_GET['display'])) ? $_GET['display'] : "");

		if ($display_mode) {

			$ne = $alarm_model->getNE($_GET['na'], $_GET['family']);

			if(count($ne) > 0){

				$html = "";

				for ($i = 0;$i < count($ne);$i++)
				{
					$html .= "	<input type='checkbox' id='".$ne[$i]['id']."' 
								value='".$ne[$i]['id']."' parent_id = '".$_GET['na']."'
								onclick=\"setBoxChecked(this);\"
								/>
								<label for='".$ne[$i]['id']."'>".$ne[$i]['label']."</label>
								<br />
							 ";
				}
			}
			else
			{
				$html = 'No result';
			}			
		}
		else 
		{
			$html = "all_".$_GET['na'];
		}

		echo $html;

	break;

	case "save_alarm" :

		$triggers[0]['critical'] = array(
			'data_field'	=> $_GET['trigger_field1'], 
			'operand'		=> $_GET['trigger_operand_critical1'],
			'type'		=> $_GET['trigger_type1'],
			'value'		=> $_GET['trigger_value_critical1']
		);

		if (strpos($_GET['ne_list'], "all") === false) {
			$a_ne = $_GET['ne_list'];
		} else {
			$a_ne = str_replace("_".$_GET['net_to_sel'], "", $_GET['ne_list']);
		}

		$activated = ((isset($_GET['alarm_activated'])) ? 1 : 0);
		
		echo $alarm_model->saveAlarmDefinition($_GET['alarm_id'], $_GET['alarm_type'], $_GET['alarm_family'], $_GET['alarm_name'], $_GET['alarm_description'], $_GET['net_to_sel'], $a_ne, $_GET['time_to_sel'], $activated, $triggers);

	break;
}

?>