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

/*

Fonction de cryptage / décryptage d'une chaine.
Cette fonction utilise un algorythme simple, mais avec une clé (cryptage symétrique).
Toute la puissance de l'algorythme réside dans le fait que la clé est gardée secrete.

2006-02-14	Stephane	Creation

*/

// evidement, cette chaine doit être cryptée avec Zend, sinon ça sert à rien.
$key = "la sagrada familia est a barcelone";

// on construit notre table de caracteres et de correspondance lettre <-> nombre
$chr_table = array();
// de 0 à 9
for ($i=48;$i<58;$i++)	$chr_table[] = chr($i);
// de A à Z
for ($i=65;$i<91;$i++)	$chr_table[] = chr($i);
// de a à z
for ($i=97;$i<123;$i++)	$chr_table[] = chr($i);
// on ajoute ' ' et '@'
$chr_table[] = ' ';
$chr_table[] = '@';
// on inverse la table
$ord_table = array_flip($chr_table);

// fonction qui a une lettre associe un nombre
function my_ord($chr) {
	global $ord_table;
	return $ord_table[$chr];
}
// fonction qui a un nombre associe une lettre
function my_chr($int) {
	global $chr_table;
	return $chr_table[$int];
}

function Encrypt($string, $key) {
	// $string	: la chaine à crypter
	// $key		: la clé de cryptage (une chaine aussi)
	$result = '';
	// on peut choisir de faire un padding 20
	$string = str_pad($string,32,' ');
	// on encode la chaine
	for($i=1; $i<=strlen($string); $i++) {
		$char = substr($string, $i-1, 1);
		$keychar = substr($key, ($i % strlen($key))-1, 1);
		$ze_ord = my_ord($char)+my_ord($keychar);
		if ($ze_ord > 63) $ze_ord = $ze_ord - 64;
		$result.=my_chr($ze_ord);
	}
	// on met des tirets tous les 4 caractères
	$j = 0;
	for ($i = 0; $i < strlen($result); $i++) {
		if (!($j % 4)) $result2 .= '-';
		$result2 .= $result[$i];
		$j++;
	}
	$result = trim($result2,'-');
	// on remplace les espaces dans la chaine
	$result = str_replace(' ','#',$result);
	return $result;
}

function Decrypt($string, $key) {
	// $string	: la chaine à decrypter
	// $key		: la clé de cryptage (une chaine aussi, la même qu'au cryptage)
	$string = str_replace('-','',$string);
	$string = str_replace('#',' ',$string);
	// on decode la chaine
	for($i=1; $i<=strlen($string); $i++) {
		$char = substr($string, $i-1, 1);
		$keychar = substr($key, ($i % strlen($key))-1, 1);
		$ze_ord = my_ord($char)-my_ord($keychar);
		if ($ze_ord < 0) $ze_ord = $ze_ord + 64;
		$result.= my_chr($ze_ord);
	}
	return trim($result);
}

$string = '25@bsc@20060214@20060222@E0';
$crypted = Encrypt($string,$key);
echo "<h4>$crypted</h4>";


/* exemple :
$string = "025@20060228@bsc";
echo "<h4>$string</h4>";
// on crypte
$crypted = Encrypt($string,$key);
echo "<h4>$crypted</h4>";
// on decrypte
$decrypted = Decrypt($crypted,$key);
echo "<h4>$decrypted</h4>";
*/



?>
