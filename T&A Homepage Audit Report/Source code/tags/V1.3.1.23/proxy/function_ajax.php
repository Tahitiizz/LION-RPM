<?php
if(!isset($_SESSION)) session_start();

//informations relatives à la redirection vers un dashboard
$url = isset($_REQUEST['url'])? $_REQUEST['url'] : '';
$mode=isset($_REQUEST['mode'])? $_REQUEST['mode'] : 'overtime';
$time_agregation = isset($_REQUEST['time_agregation'])? $_REQUEST['time_agregation'] : '';
$time_value = isset($_REQUEST['time_value'])? $_REQUEST['time_value'] : '';
//BZ 34928 ajout de la period sinon graph en overtime ne s'affiche pas bien affiché
//$period = ($time_agregation=='hour') ? '24' : '14';
$period=isset($_REQUEST['period'])? $_REQUEST['period'] : '20';
$network_agregation = isset($_REQUEST['network_agregation'])? $_REQUEST['network_agregation'] : '';
$nel_selecteur = isset($_REQUEST['nel_selecteur'])? $_REQUEST['nel_selecteur'] : '';
$network_name = isset($_REQUEST['network'])? $_REQUEST['network'] : 'toto';
$network_3_axe_agregation = isset($_REQUEST['network_3_axe_agregation'])? $_REQUEST['network_3_axe_agregation'] : '';
$network_3_axe_name = isset($_REQUEST['network_3_axe'])? $_REQUEST['network_3_axe'] : 'toto';

// --------------------------------------------
// redirection de la homepage vers un dashboard
if($url != ''){
	//clear selector
	unset($_SESSION['TA']['selecteur']);

	$_SESSION['TA']['selecteur']['ta'] = $time_agregation; // Useless ?
	$_SESSION['TA']['selecteur']['ta_level'] = $time_agregation;
	$_SESSION['TA']['selecteur']['ta_value'] = $time_value;
	$_SESSION['TA']['selecteur']['period'] = $period;
	$_SESSION['TA']['selecteur']['top']= $mode=='overnetwork' ? '12' : '3';
	//$_SESSION['TA']['selecteur']['nel_selecteur'] = $nel_selecteur;

	$_SESSION['TA']['selecteur']['na_axe1'] = $network_agregation;

	if($mode=='overtime'){
		$_SESSION['TA']['network_element_preferences'] = $network_name;
		$_SESSION['TA']['selecteur']['na_level']='';
	}
	else{
		$_SESSION['TA']['network_element_preferences']='';
		$_SESSION['TA']['selecteur']['na_level'] = $network_agregation;
	}
	if ($network_3_axe_agregation != '') $_SESSION['TA']['selecteur']['na_axeN'] = $network_3_axe_agregation;
	if ($network_3_axe_name != '') $_SESSION['TA']['ne_axeN_preferences'] = $network_3_axe_name;
	echo $url;
}
?>