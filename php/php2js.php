<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?
//fonction gérant la conversion des variables php en js

function php2js ($var) {
     if (is_array($var)) {
         $res = "[";
         $array = array();
         foreach ($var as $a_var) {
             $array[] = php2js($a_var);
         }
         return "[" . join(",", $array) . "]";
     }
     elseif (is_bool($var)) {
         return $var ? "true" : "false";
     }
     elseif (is_int($var) || is_integer($var) || is_double($var) || is_float($var)) {
         return $var;
     }
     elseif (is_string($var)) {
         return "\"" . addslashes(stripslashes($var)) . "\"";
     }
     // autres cas: objets, on ne les gère pas
     return FALSE;
 }
?>
