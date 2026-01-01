<?
/*
*	@cb50000@
*
*	27/07/2009 - Copyright Astellia
*
*	27/07/2009 BBX : adaptation CB 5.0
*/
?>
<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
	- maj 11/03/2008, benoit : correction du bug 3819. Le truncage du label du kpi ne se fait plus ici mais dans les fonctions JS de retour (si   le parametre "trunc" n'est pas défini
*/
?>
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
*	@cb21101@
*
*	08/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.1.01
*/
?>
<?
/*
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*/
?>
<?
/*
*	@cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
*/
?>
<?php

/*
	- maj 03/10/2006, xavier : résolution du bug lorsqu'aucun kpi ou raw dans une famille

	- maj 06/11/2006, benoit : reduction de la taille du label raw/kpi à 50 caractères et vérification de la      valeur du champ (label non nul)

	- maj 27/02/2007, benoit : limitation du label des raws / kpis à $_GET['max_size'] caractères

	- maj 08/03/2007, benoit : on verifie que '$_GET['max_size']' n'est pas nul sinon, on limite la taille à 40   caracteres

*/

session_start();

// on force l'encodage des caractères en ISO (caractères spéciaux)
header('Content-Type: text/html; charset=ISO-8859-1');
include_once dirname(__FILE__).'/environnement_liens.php';

$product	= $_GET['product'];
$family	= $_GET['family'];


// spécifie quel sera le champ 'select' du formulaire qui recevra les données
$valeurs_champ = $_GET['cible']."|column|";

	switch($_GET['champ']){

		// si le type de champ est 'kpi', on renvoit la liste des kpi sous ce format :
		// kpi_name |field| kpi_label (si kpi label est nul, on le remplace par kpi_name)
		case 'kpi'		:

			$kpi_array = get_kpi($family,$product);

			if (count($kpi_array)){
				foreach ($kpi_array as $name => $label){

					// 06/11/2006 - Modif. benoit : limitation de la taille du label raw/kpi à 40 caractères

					// 27/02/2007 - Modif. benoit : extension de la limitation à $_GET['max_size'] caractères

					// 08/03/2007 - Modif. benoit : on verifie que '$_GET['max_size']' n'est pas nul sinon, on limite la taille à 40 caracteres

					if(trim($label) == "") $label = $name;
					
					// 11/03/2008 - Modif. benoit : le truncage du label du kpi ne se fait plus ici mais dans les fonctions JS de retour (si le parametre "trunc" n'est pas défini
					
					if ((!isset($_GET['trunc']))) {
						$m_size = $_GET['max_size'];
						if ($m_size == "") $m_size = 40;
						if (strlen($label) > $m_size) $label = substr($label, 0, $m_size)."...";						
					}

					$valeurs_champ .= $name."|field|".$label."|column|";
				}
			}

		break;

		// si le type de champ est 'raw', on renvoit la liste des raw counters sous ce format :
		// counter_name |field| counter_label (si label est nul, on le remplace par name)
		case 'raw'		:

			$counter_array = get_counter($family,$product);

			if (count($counter_array)){
				foreach ($counter_array as $name => $label){

					// 06/11/2006 - Modif. benoit : limitation de la taille du label raw/kpi à 40 caractères

					// 27/02/2007 - Modif. benoit : extension de la limitation à $_GET['max_size'] caractères

					if(trim($label) == "") $label = $name;
					
					// 11/03/2008 - Modif. benoit : le truncage du label du raw ne se fait plus ici mais dans les fonctions JS de retour
					
					/*$m_size = $_GET['max_size'];
					if ($m_size == "") $m_size = 40;
					if (strlen($label) > $m_size) $label = substr($label, 0, $m_size)."...";*/

					$valeurs_champ .= $name."|field|".$label."|column|";
				}
			}

		break;
	}

	echo $valeurs_champ;

?>
