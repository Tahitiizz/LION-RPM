<?
/*
 * @cb50000@
 * 09/11/2011 - Copyright Astellia
 *
 * 09/11/2011 ACS BZ 24526 Display a message when saving or deleting data history configuration
 */
?>
<?
/*
*	@cb41000@
*
*	09/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	09/12/2008 BBX : modifications pour le CB 4.1 :
*	=> Utilisation des nouvelles classes (DatabaseConnection)
*	=> Utilisation des nouvelles constantes
*	=> Gestion du produit
*
*	14:46 16/10/2009 SCT : BZ 12033 => modification du paramètre d'enregistrement des données "hour" remplacé par "day"
*
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
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Récupération des données transmises via l'URL.
$family = $_GET["family"];
$product = $_GET["product"];

// Connexion à la base du produit
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection($product);

// 14:46 16/10/2009 SCT : BZ 12033 => modification du paramètre d'enregistrement des données "hour" remplacé par "day"
if(isset($_POST['day']))
{
	// On récupère le nom et la valeur des paramètres
	$offsets['name'][0] = $_POST['hour'];
	$offsets['value'][0] = $_POST['hour_value'];

	$offsets['value'][1] = $_POST['day_value'];
	$offsets['name'][1] = $_POST['day'];

	$offsets['value'][2] = $_POST['week_value'];
	$offsets['name'][2] = $_POST['week'];

	$offsets['value'][3] = $_POST['month_value'];
	$offsets['name'][3] = $_POST['month'];

	$cpt =0;
	foreach($offsets['name'] as $k=>$v)
	{
		// on récupère la valeur des paramètres dans sys_definition_history
		$query = "SELECT duration FROM sys_definition_history WHERE family = '".$family."' AND ta = '".$v."'";

		$res = $database->execute($query);
		$nb_res = $database->getNumRows();

		if($nb_res > 0)
		{
			while($row = $database->getQueryResults($res,1))
			{
				// Si le paramètre a été modifié on met à jour la table
				if($row['duration'] != $offsets['value'][$k])
				{
					$q = "UPDATE sys_definition_history SET duration = ".$offsets['value'][$k]." WHERE ta ='".$v."' AND family = '".$family."'";
					$database->execute($q);
				}
			}
		}
		else
		{
			// Si le paramètre n'est pas présent dans la table sys_definition_history, on l'insère dedans
			$q = "INSERT INTO sys_definition_history(family,ta,duration) 
			VALUES('".$family."','".$v."',".$offsets['value'][$k].")";
			$database->execute($q);
		}
		$cpt++;
	}
}

// Redirection
// 09/11/2011 ACS BZ 24526 Display a message when saving or deleting data history configuration
header("Location:setup_data_history.php?save=success&family=".$family."&product=".$product);
exit;
?>
