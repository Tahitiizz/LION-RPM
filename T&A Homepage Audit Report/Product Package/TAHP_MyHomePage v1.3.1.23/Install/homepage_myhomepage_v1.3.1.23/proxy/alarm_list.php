<?php

include_once('../../php/environnement_liens.php');
include_once('dao/models/AlarmModelHomepage.class.php');


$task = '';
if (isset($_POST['task'])){
	// Get task from Ext JS
	$task = $_POST['task'];
}


$params = '';
if (isset($_POST['params'])){
	// Get params from Ext JS
	$params = $_POST['params'];
}


switch($task){
	// Initialize the user configuration files
	case 'GET_CELLSSURVEILLANCE':
		getCellsSurveillance($params);
		break;

	case 'GET_WARNING_PENALTY_CSV';
		getWarningPenaltyForCsv($params);
		break;
		
	case 'GET_WARNING_PENALTY_MONTH';
		getWarningPenaltyByMonth($params);
		break;
	
	case 'GET_WARNING_PENALTY_MONTH_EVO';
		getWarningPenaltyByMonthEvo($params);
		break;
		
	case 'GET_WARNING_INTERSECTION_MONTHS';
		getWarningIntersectionMonths($params);
		break;

	// Get the list of months from current month and scale
	case 'GET_MONTHS_LIST':
		getMonthList($params);
		break;
	
	case 'GET_ALARMS_OCCURENCE_MONTH';
		getAlarmsOccurenceByMonth($params);
		break;
		
	case 'GET_ALARMS_NAMES':
		getAlarmsNames($params);
		break;
	
	case 'GET_DETAILED_REPORT':
		getDetailedReport($params);
		break;	
	
	default:
		echo 'failure';
		break;
}


function getCellsSurveillance($params){
	
	// get alarm options JSON object
	$params = json_decode(stripslashes($params));
	
	$AlarmModelHomepage = new AlarmModelHomepage();
	//$alarmValues = $AlarmModelHomepage->calculateReferencePeriodPenalisation($params["sdp_id"], $params["alarm_id"], $params["current_date"], $params["ref_period"], $params["min_days"], $params["ratioforpenalisation"]);
	$alarmValues = $AlarmModelHomepage->calculateReferencePeriodPenalisation($params->sdp_id, $params->alarm_id, $params->current_date, $params->ref_period, $params->min_days,$params->selectedmode,$params->ratioforpenalisation,$params->nbdaysforpenalisation);
	
	echo json_encode($alarmValues);
}		
		

function getWarningPenaltyByMonth($params){
	
	
	
	$params = json_decode(stripslashes($params));
	
	$AlarmModelHomepage = new AlarmModelHomepage();
	
	if(is_array($params->current_date)){
		
		$finalres=array();
		
		foreach($params->current_date as $month){
			
			$values=$AlarmModelHomepage->calculatePenaltyWarningCells($params->sdp_id,$params->alarm_ids, $month, $params->ref_period, $params->selectedmode , $params->ratioforpenalisation,$params->nbdaysforpenalisation,true,true,true);
				
			$finalres[$month]=$values;
			
			
		}
		
		$values=$finalres;
		
	}
	else{
		$values=$AlarmModelHomepage->calculatePenaltyWarningCells($params->sdp_id,$params->alarm_ids, $params->current_date, $params->ref_period, $params->selectedmode , $params->ratioforpenalisation,$params->nbdaysforpenalisation,true,true,true);
		
	}
	
	echo json_encode($values);
}

function getWarningPenaltyByMonthEvo($params){
	
	
	
	$params = json_decode(stripslashes($params));
	
	$AlarmModelHomepage = new AlarmModelHomepage();
	
	if(is_array($params->current_date)){
		
		$finalres=array();
		
		foreach($params->current_date as $month){
			
			$values=$AlarmModelHomepage->calculatePenaltyWarningCellsEvo($params->sdp_id,$params->alarm_ids, $month, $params->ref_period, $params->selectedmode , $params->ratioforpenalisation,$params->nbdaysforpenalisation,true,true,true);
				
			$finalres[$month]=$values;
			
			
		}
		
		$values=$finalres;
		
	}
	else{
		$values=$AlarmModelHomepage->calculatePenaltyWarningCellsEvo($params->sdp_id,$params->alarm_ids, $params->current_date, $params->ref_period, $params->selectedmode , $params->ratioforpenalisation,$params->nbdaysforpenalisation,true,true,true);
		
	}
	
	echo json_encode($values);
}

function getWarningIntersectionMonths($params){
	
	
	
	$params = json_decode(stripslashes($params));
	
	$AlarmModelHomepage = new AlarmModelHomepage();
	
	if(is_array($params->current_date)){
		
		$finalres=array();
		
		foreach($params->current_date as $month){
			
			$values=$AlarmModelHomepage->calculateIntersectionWarningCells($params->sdp_id,$params->alarm_ids, $month, $params->ref_period, $params->selectedmode , $params->ratioforpenalisation,$params->nbdaysforpenalisation,true,true,true);
				
			$finalres[$month]=$values;
			
			
		}
		
		$values=$finalres;
		
	}
	else{
		$values=$AlarmModelHomepage->calculateIntersectionWarningCells($params->sdp_id,$params->alarm_ids, $params->current_date, $params->ref_period, $params->selectedmode , $params->ratioforpenalisation,$params->nbdaysforpenalisation,true,true,true);
		
	}
	
	echo json_encode($values);
}



function getWarningPenaltyForCsv($params){
	
	$params = json_decode(stripslashes($params));
	
	$AlarmModelHomepage = new AlarmModelHomepage();

	$values=$AlarmModelHomepage->calculatePenaltyWarningCells($params->sdp_id,$params->alarm_ids, $params->current_date, $params->ref_period, $params->ratioforpenalisation,$params->nbdaysforpenalisation,true,true,true);
	
	echo json_encode($values);
}

function getMonthList($params){



	$params = json_decode(stripslashes($params));

	$AlarmModelHomepage = new AlarmModelHomepage();
	
	
	$year = substr($params->current_date,0,4);
	$month = substr($params->current_date,4,2);
	$current_month_pretty = $year.'-'.$month;

	$values=$AlarmModelHomepage->get_months($AlarmModelHomepage->get_scale_month($params->history,$current_month_pretty),$current_month_pretty);
	
	echo json_encode($values);
}

function getAlarmsOccurenceByMonth($params){

	$params = json_decode(stripslashes($params));
	
	$alarmObj = $params->alarms;
	$alarmArray = array();
	foreach ($alarmObj as $id => $alarm){
		array_push($alarmArray,$alarm);
	}
	$alarmNameObj = $params->alarms_name;
	$alarmArrayName = array();
	foreach ($alarmNameObj as $name => $alarm_name){
		array_push($alarmArrayName,$alarm_name);
	}
	
	$AlarmModelHomepage = new AlarmModelHomepage();
	$alarmOccurenceValues = $AlarmModelHomepage->getAlarmOccurence($params->sdp_id,$params->current_month,$params->scale,$alarmArray,$alarmArrayName);

	echo $alarmOccurenceValues;

}

function getAlarmsNames($params){

	$params = json_decode(stripslashes($params));

	$AlarmModelHomepage = new AlarmModelHomepage();

	$values=$AlarmModelHomepage->get_alarms_name($params->sdp_id,$params->alarm_ids);

	echo json_encode($values);
}

function getDetailedReport($params){

	$params = json_decode(stripslashes($params));

	$AlarmModelHomepage = new AlarmModelHomepage();

	$values=$AlarmModelHomepage->getDetailedReport($params->sdp_id,$params->alarm_ids,$params->current_date);

	echo json_encode($values);
}



?>
