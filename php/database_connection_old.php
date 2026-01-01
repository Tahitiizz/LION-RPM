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
$DBHost="localhost";
$DBUser="mysql_722";
$DBPass="fstch#4";
$DBName='mysql_722';
*/

if(isset($_GET["database"]))
    $database=$_GET["database"];
else $database="";

//echo "database $database<br>";
if ($database==2)
{
$DBHost = "localhost";
$DBport = "5432";
$DBUser = "postgres";
$DBPass = "";
$DBName = "cb1170_1200_gsm104_105";
//$database_connection = pg_connect("host=$DBHost port=$DBport dbname=$DBName user=$DBUser password=$DBPass");
$database_connection = pg_connect("port=$DBport dbname=$DBName user=$DBUser password=$DBPass");
}
else
{
$DBHost = "localhost";
$DBport = "5432";
$DBUser = "postgres";
$DBPass = "";
$DBName = "cb1170_1200_gsm104_105";
//$database_connection = pg_connect("host=$DBHost port=$DBport dbname=$DBName user=$DBUser password=$DBPass");
$database_connection = pg_connect("port=$DBport dbname=$DBName user=$DBUser password=$DBPass");

}
?>
