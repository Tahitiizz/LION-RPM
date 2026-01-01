<?php
// vérification d'une adresse mail

$email = isset($_REQUEST['email'])? $_REQUEST['email'] : "";

if(filter_var($email, FILTER_VALIDATE_EMAIL))
	echo 'ok';
else
	echo 'ko';

?>