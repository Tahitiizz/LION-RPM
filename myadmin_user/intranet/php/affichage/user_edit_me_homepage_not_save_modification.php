<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- 30/08/2007 christophe : ajout d'un session_start() et suppression d'un caractère ? en trop.	
*	- maj 20/06/2007 Gwénaël : on remet les valeurs du sélecteurs comme elles étaient avant l'ouverture de la popup 
*				dans le cas où l'utilisateur aurait changé les valeurs du sélecteur sans faire DISPLAY
*	
*/
?>
<?php

session_start();

$_SESSION['sys_user_parameter_session'] = $_SESSION['save_sys_user_parameter_session'];

?>