<?php
// maj 14/12/2009 MPR : Correction du bug 13310 - Ajout d'un set_time_limit afin de ne pas avoir de max execution time exceeded
set_time_limit(3600);

/**
*	17/12/2009 BBX. BZ 13473
*		- Ajout de commentaires dans les fonctions
*		- Réindentation
*		- Réécriture de la fonction traffic afin d'augmenter la précision et les performances
*		- Réécriture de la fonction channels afin d'augmenter les performances
*
**/

/**
 * Main ErlangB formula
 * Usage : ErlangB('A=1.658&N=5&mode=GOS') returns blocking probab = 0.02 i.e 2%
 * Usage : ErlangB('A=1.658&P=0.02&mode=CH') returns nb of channels = 5
 * Usage : ErlangB('P=0.02&N=5&mode=TR') returns Traffic = 1.658 Erlangs
 * @param int $a Carried Traffic.
 * @param int $n Number of Channels.
 * @return int
 */
function ErlangB ( $args = '' ) 
{
	$defaults = array(
		'A' => 0,
		'N' => 0,
		'P' => 0,
		'mode' => 'GOS'
	);
	if ( empty( $args ) ) return;
	$params = parse_args_now( $args, $defaults );
	extract( $params );
	$P/=100;
	if ( ($mode == 'GOS') && isset( $args['A'] ) && isset( $args['N'] ) ) return GOS($A,$N,4,true);
	if ( ($mode == 'CH') && isset( $args['A'] ) && isset( $args['P'] ) ) return channels($A,$P);
	if ( ($mode == 'TR') && isset( $args['P'] ) && isset( $args['N'] ) ) return traffic($P,$N);
	return;
}

/**
 * Given A (Traffic) and N (Nb of Channels) , GOS function evaluates the blocking probability (GOS) using Erlang's formula
 * Usage : GOS(1.658,5) returns blocking probab = 0.02 i.e 2%
 * @param int $a Carried Traffic.
 * @param int $n Number of Channels.
 * @return int
 */
function GOS($a=0,$n=0,$prec=4, $pourcentage=false) 
{
	// Si $a vaut 0, alors on retourne 0
	if ($a <= 0) return 0.0000;
 	// On initialise $s à 0.
	$s = 0.0000;
	// On boucle maintenant de 1 à $n pour calculer $s
	for ($i = 1; $i <= $n; $i++)
		$s = (1.0000 + $s)*($i/$a);
	// On retourne la valeur du GOS : 1 / (1+$s)
	// Arrondie à la précision paramétrée
	// maj 18/01/2010 - MPR : Format de GOS en % ( On multiplie le résultat par 100)
	$result = ( $pourcentage ) ? (1.0000/(1.0000 + $s)) * 100 : (1.0000/(1.0000 + $s));
	return round ( $result, $prec);
} 

/**
 * Given A (Traffic) and P (Blocking Probab.) , channels function evaluates the nb of channels using Erlang's formula
 * Usage : channels(1.658,0.02) returns Nb of channels = 5
 * @param int $a Traffic.
 * @param int $p blocking probability.
 * @return int
 */
function channels($a=0,$p=0) 
{
	// $n est la valeur que l'on recherche, celle qui sera retournée par la fonction.
	// On doit trouver $n pour que GOS($a,$n) tende vers $p
	// avec une précision à l'entier près.
	// On commence par initialisé $n à $a/2 car il ne pourra jamais être plus petit
	$n = ceil($a/2);
	
	// $start détermine à quelle puissance de 10 le pas de recherche va être choisi
	// Le pas par défaut est 1, soit 10 puissance 0, soit $start = 0
	$start = 0;
	if($a > 10)
		$start = floor(log10($a)) * (-1);
	
	// On va maintenant rechercher notre $n
	// en affinant à chaque fois le pas
	// jusqu'au pas de 1
	for($nbz = $start; $nbz <= 0; $nbz++)
	{
		// Calcul de l'incrémentation
		$inc = 1/pow(10,($nbz));
		// Clacul du Gos avec la valeur de $n actuelle
		$cp = GOS($a,$n);
		// Tant que $p n'est pas dépassé, on continue de chercher
		while($cp > $p)
		{		
			// Incrémentation de $n avec le pas courant
			$n = $n + $inc;
			// Calcul du Gos avec la valeur de $n actuelle
			$cp = GOS($a,$n);
		}
		// Décrémentation de $n afin de se placer à la dernière valeur avant d'avoir dépasser $p
		$n = $n - $inc;
	}
	// On retourne la valeur de $n pour laquelle GOS($a,$n) tend vers $p
	return round($n+$inc);
}

/**
 * Given P (Blocking Probab.) and and N (Nb of Channels) ,  this function evaluates the Traffic using Erlang's formula
 * Usage : traffic(0.02,5) returns GOS at 2% for 5 channels = 1.66
 * @param int $p blocking probability.
 * @param int $n nb of channels.
 * @return int
 */
function traffic($p,$n) 
{

	// $a est la valeur que l'on recherche, celle qui sera retournée par la fonction.
	// On doit trouver $a pour que GOS($a,$n) tende vers $p
	// avec la précision paramétrée dans $precision
	$a = 0;
	
	// $val va mémoriser la dernière valeur trouvée de $a
	// dans le champ de précision courant.
	// A chaque "zoom" dans la précision, on repartiera de $val
	// pour rechercher une valeur plus précise
	$val = 0;
	
	// $precision va déterminer le nombre de décimales que l'on souhaite
	// pour la valeur de $a.
	$precision = 4;
	
	// Si le %tage $p vaut 100% ou plus, la valeur retournée va correspondre
	// à : $n * $p * 100	
	if($p >= 1)
		return round($n * $p * 100,$precision);
	
	// $startLoop va permettre de calculer à quelle puissance de 10
	// nous allons débuter la recherche de $a.
	// La valeur de $startLoop correspond au nombre de chiffres de la partie entière de $n
	// auquel on retire 1. On signe ensuite cette valeur négativement.
	// Il s'agit de la valeur entière négative du logarithme de $n.
	// Exemple : si $n = 158, $startLoop = -2
	$startLoop = (strlen(round($n))-1) * (-1);

	// On va maintenant rechercher $a, en augmentant la précision.
	// On commence par recherche $a pour une précision de "10 puissance $startLoop"
	// jusqu'à une précision de "10 puissance - ($precision+1)"
	// Exemple : $startLoop = -2, $precision = 4
	// $NBZ prendra successivement les valeurs -2, -1, 0, 1, 2, 3, 4, 5
	// Cette boucle a pour rôle d'affiner la précision de $a jusqu'à la précision souaihtée
	for($NBZ = $startLoop; $NBZ <= ($precision+1); $NBZ++)
	{
		// Détermine le domaine de précision courant. 
		// Celà correspond au pas de la recherche de $a
		// On obtient notre pas en effectuant lé calcul suivant :
		// 1 / 10 puissance $NBZ
		// Exemple : si $NBZ = -1, $inc = 10 => le pas de sera de 10
		// Donc si $NBZ va de -2 à 5, la recherche de $a va s'effectuer
		// avec une précision de 100 à 0,00001
		$inc = 1/pow(10,($NBZ));
		
		// On initialise $a avec la valeur de $val
		$a = $val;
		
		// On calcul le GOS($a,$n) avec la valeur de $a courante.
		$cp = GOS($a,$n,$precision+3);

		// La boucle ci-dessous est celle qui va permettre de trouver $a
		// dans le domaine de précision courant.
		// On va parcourir toutes les valeurs possibles en partant de $val
		// avec un pas de $inc.	
		// Dès que le GOS calculé ($cp) dépasse $p
		// Alors on peut dire que l'on répond à la contrainte suivante :
		// On a trouvé $a pour que GOS($a,$n) tende vers $p
		// avec la précision paramétrée dans $precision
		while($cp <= $p)
		{			
			// On mémorise la valeur de $a pour la précision courante dans $val
			// On va repartir de $val à la prochaine boucle
			$val = $a;			
			// On incrémente $a avec le pas courant $inc
			$a += $inc;			
			// On calcul le GOS($a,$n) avec la valeur de $a courante.
			$cp = GOS($a,$n,$precision+3);
		}
	}
	
	// La valeur de $a est trouvée. On retourne $a arrondie à la précision paramétrée.
	return round($a,$precision);
}

/**
 * Cette fonction va parser les paramètres passés à la fonction ErlangB
 * Usage : parse_args_now( $args, $defaults )
 * @param mixed $args arguments.
 * @param array $defaults valeurs par défaut.
 * @return mixed
 */
function parse_args_now( $args = '' , $defaults = '' ) 
{
	if ( is_array( $args ) ) {
		$return =& $args;
	} 
	else 
	{
		parse_str( $args, $return );
		if ( get_magic_quotes_gpc() ) 
		{
			if (is_array($return)) {
				$return = array_map('stripslashes', $return); 
			} else {
				$return = stripslashes($return);
			}
		}
	}
	if ( is_array( $defaults ) ) return array_merge( $defaults, $return );
	return $return;
}

$list = $_GET['list'];
echo ErlangB($list);
?>