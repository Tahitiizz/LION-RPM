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
//  Ces deux fonction servent a suprimer les doublons dans les tableau (array) multidimensionnel
// l'apelle se fait par : array_unique2("mon tableau ") et renvoit une copie du tableau sans les doublons , mais ne modifie pas le tableau d'origine
// exemple d'utilisation : $multi_array=array_unique2($multi_array)
// function recursivemakehash($tab)
//  function array_unique2($input)
function recursivemakehash($tab)
 {
   if(!is_array($tab))
    return $tab;
   $p = '';
   foreach($tab as $a => $b)
    $p .= sprintf('%08X%08X', crc32($a), crc32(recursivemakehash($b)));
   return $p;
 }
 function array_unique2($input)
 {
   $dumdum = array();
   foreach($input as $a => $b)
    $dumdum[$a] = recursivemakehash($b);
   $newinput = array();
   foreach(array_unique($dumdum) as $a => $b)
    $newinput[$a] = $input[$a];
   return $newinput;
 }


 ?>
