<?php
/**
 * @version 1.0 $Id: validate_login.php 37590 2011-12-20 13:38:09Z y.audoux $
 *
 */
 
    /* ******************************************
	 * I N I T I A L I S A T I O N
	 * ******************************************/
	include_once('../PAAAuthenticationService.php');
	session_start();
	
	if (isset($_POST['username']))
		$_SESSION['login'] = $_POST['username'];
		
	if (isset($_POST['password']))
		$_SESSION['password'] = md5($_POST['password']);
	
		
	$PAAAuthentication = PAAAuthenticationService::getAuthenticationService();
	

    /* ******************************************
	 * V A L I D A T I O N du login
	 * ******************************************/
	if ($PAAAuthentication->validateAuthentication())
	{
		header("Location: ". $_GET["url"]);
        exit;
	}	
	else
	{		
		$uri = "?";
		if (isset($_GET["url"]))
		{
			$uri = $uri.'error=1&url='.urlencode($_GET["url"]);
		}
		else
		{
			$uri = $uri."error=1";
		}
        
        if (isset($_GET["degraded"]))
		{
			$uri = $uri.'&degraded';
		}
	
		header("Location: form_login.php".$uri);
        exit;
	}
	
?>