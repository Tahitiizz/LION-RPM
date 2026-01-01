<?
/*
*	@cb41000@
*
*	08/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	08/12/2008 BBX : modifications pour le CB 4.1
*	=> Utilisation de la classe DatabaseConnection
*	=> Utilisation des nouvelles constantes
*	=> Gestion du produit
*
*	- 10/02/2009 - MPR : Ajout de la colonne 3ème axe pour définir la nature du niveau d'agrégation (réseau ou 3ème axe)
*
*	- modif 18/05/2009 BBX : ajout de la famille dans la requête qui test l'unicité. BZ 9774
*	- maj 15/07/2009 MPR : Correction du bug 10506 - Ajout du produit dans l'url passée
*	- maj 13/08/2009 MPR : Ajout d'une ) Parse Error php // Correction du bug 10920
*
*	27/08/2009 GHX
*		- Re-correction du BZ 10920 [REC][T&A Cb 5.0][TP#1][TS#AA2-CB50][TC#35832][Setup Network Aggregation] : on peut saisir des caractères accentués dans le nom du NA
*
*	27/08/2009 BBX
*		- Re-correction du BZ 10920 : on supprime TOUS les caractères spéciaux + on quitte sans rien faire si après triatement du NA celui-ci est vide ou commence par un chiffre.
*/
?>
<?
/*
*	@cb30000@
*
*	23/07/2007 - Copyright Acurio
*
*	Composant de base version cb_3.0.0.00
*
*	- maj 20/08/2007 Jérémy : 	Ajout d'une contrainte d'unicité du label d'un NA TOUTE famille confondue
*						Si l'on trouve un label identique à celui saisi par l'utilisateur, on retourne le formulaire avec les informations données précédement
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
/*
 * - maj 16/04/2007 Gwénaël
 *		>> suppression de _ dans le nom du network agregation, pour éviter d'avoir des problèmes avec des "explode" sur _.
 */
?>
<?
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Librairies et classes nécessaires
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/ProfileModel.class.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/models/UserModel.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'/php/deploy_and_compute_functions.php');
require_once(REP_PHYSIQUE_NIVEAU_0.'/class/deploy.class.php');
include_once(REP_PHYSIQUE_NIVEAU_0.'/php/traitement_chaines_de_caracteres.php');

// récupération des valeurs
$action=$_GET["action"];
$tana_type=$_GET['tana_type'];
$family = $_GET["family"];
$product = $_GET["product"];

/**
	* Fonction qui verifie que le NA ne fait pas partie des noms reserves en PSQL
	* Il faudra remplacer cette fonction, par la vraie fonction pg_get_keywords() mise en place a partir de la version 8.4 de postgres
	* - $tana : nom du tana choisi par l'utilisateur
	*/
function pg_get_keywords($tana){
	$keywords_reserved = array ('ALL', 'ANALYSE', 'AND', 'ANY', 'ARRAY', 'AS', 'ASC', 'ASYMMETRIC', 'AUTHORIZATION',
															'BETWEEN', 'BINARY', 'BOTH',
															'CASE', 'CAST', 'CHECK', 'COLLATE', 'COLUMN', 'CONSTRAINT', 'CREATE', 'CROSS', 'CURRENT_DATE', 'CURRENT_ROLE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURRENT_USER',
															'DEFAULT', 'DEFERRABLE', 'DESC', 'DISTINCT', 'DO', 'ELSE', 'END', 'EXCEPT',
															'FALSE', 'FOR', 'FOREIGN', 'FREEZE', 'FORM', 'FULL', 'GRANT', 'GROUP',
															'HAVING', 'ILIKE', 'IN', 'INITIALLY', 'INNER', 'INTERSECT', 'INTO', 'IS', 'ISNULL', 'JOIN',
															'LEADING', 'LEFT', 'LIKE', 'LIMIT', 'LOCALTIME', 'LOCALTIMESTAMP', 'NATURAL',
															'NEW', 'NOT', 'NOTNULL', 'NULL', 'OFF', 'OFFSET', 'OLD', 'ON', 'ONLY', 'OR', 'ORDER', 'OUTER', 'OVERLAPS',
															'PLACING', 'PRIMARY', 'REFERENCES', 'RETURNING', 'RIGHT', 'SELECT', 'SESSION_USER', 'SIMILAR', 'SOME', 'SYMMETRIC',
															'TABLE', 'THEN', 'TRAILING', 'TRUE', 'UNION', 'UNIQUE', 'USER', 'USING',
															'VERBOSE', 'WHEN', 'WHERE'
															);
	return in_array(strtoupper($tana), $keywords_reserved);
}


// die;
// maj 15/07/2009 MPR : Correction du bug 10506 - Ajout du produit dans l'url passee
$form_info = "&product={$_GET["product"]}";
// Connexion à la base de données du produit
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection($product);

$table="sys_definition_network_agregation";

// maj 05/08/2009 MPR : Correction du bug 10920 On échappe tous les accents
$tana=utf8_decode($_POST['tana']);
$tana_label = utf8_decode($_POST['tana_label']);
$tana_source_default=$tana_source_default[0];
$tana_level_source=$tana_level_agreg[0];
$tana_level_operand=$tana_level_agreg_operand[0];

// 11:07 27/08/2009 GHX
// Re-correction du BZ 10920
// Modification de la partie qui remplace tous les accents
// 09:30 03/09/2009 MPR :
// Re-correction du BZ 10920
$accent  ="ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúüûýýþÿ";
$noaccent="aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuuyyby";
$tana = strtolower(strtr($tana,$accent,$noaccent));
$tana_label = strtr($tana_label,$accent,$noaccent);

// 17/09/2009 BBX : Suppression des caractères spéciaux.
// Re-correction du BZ 10920
$tana = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $tana));
$tana_label = preg_replace('/[^a-zA-Z0-9]/', '', $tana_label);
// Si après els modifs, le code commence par des chiffres ou est vide, on revient sans rien faire.
if(ereg('^[0-9]+',$tana))
{
	$tana = '';
}

//MàJ 20/08/2007 - Jérémy : Test de l'unicité du nom choisi par l'utilisateur
// modif 18/05/2009 BBX : ajout de la famille dans la requête. BZ 9774
$query = "
	SELECT * FROM $table
	WHERE agregation = '$tana'
	AND family = '$family'";

$result = $database->execute($query);
if($database->getNumRows() > 0){
	//récupération des informations du formulaire pour les renvoyer
	$action=$_GET["action_encours"];//1=new,2=update,3=info
	$tana_type=$_GET["tana_type"];
	if(!empty($tana))  					$form_info .= "&tana_name=".$tana;
	if(!empty($tana_label))  			$form_info .= "&tana_label=".$tana_label;
	if(!empty($tana_type))  			$form_info .= "&tana_type=".$tana_type;
	if(!empty($tana_source_default)) 	$form_info .= "&tana_source_default=".$tana_source_default;
	if(!empty($tana_level_source)) 		$form_info .= "&tana_level_source=".$tana_level_source;

	//Pour ne pas se retrouver en conflit avec le signe "=" on écrit le nom du signe
	if(!empty($tana_level_operand)){
		switch ($tana_level_operand){
			case "=" :
				$form_info .= "&tana_level_operand=egal";
				break;
			case ">" :
				$form_info .= "&tana_level_operand=sup";
				break;
			case "<" :
				$form_info .= "&tana_level_operand=inf";
				break;
		}
	}
	$error_num = "error1";

	header("location:setup_tana_new.php?erreur=".$error_num."&action=".$action.$form_info."&family=".$family);
}  //Fin test d'unicité



if(trim($tana)=="" || trim($tana_label)=="") {
	$action=$_GET["action_encours"];//1=new,2=update,3=info
	$tana=$_GET["old_name"];
	$tana_type=$_GET["tana_type"];
	if(!empty($tana))  $tana = "&tana=".$tana;
	if(!empty($tana_type))  $tana_type = "&tana_type=".$tana_type;
	$error_num = "error2";
	// maj 15/07/2009 MPR : Correction du bug 10506 - Ajout du produit dans l'url passée
	header("location:setup_tana_new.php?erreur=$error_num&action=".$action.$tana.$tana_type."&family=".$family);

}

if  ($tana_source_default == '0' || $tana_level_source == '0') {
	$action=$_GET["action_encours"];//1=new,2=update,3=info
	$tana_type=$_GET["tana_type"];
	if(!empty($tana))  $tana = "&tana_name=".$tana;
	if(!empty($tana_label))  $tana_label = "&tana_label=".$tana_label;
	if(!empty($tana_type))  $tana_type = "&tana_type=".$tana_type;
	$error_num = "error2";

	// maj 15/07/2009 MPR : Correction du bug 10506 - Ajout du produit dans l'url passée
	header("location:setup_tana_new.php?erreur=$error_num&action=".$action.$tana_label.$tana.$tana_type."&family=".$family);
}

// Gestion du cas ou le nom du niveau d'agregation correspond a un champ SQL
if (pg_get_keywords($tana)){
	//récupération des informations du formulaire pour les renvoyer
	$action=$_GET["action_encours"];//1=new,2=update,3=info
	$tana_type=$_GET["tana_type"];
	if(!empty($tana))  					$form_info .= "&tana_name=".$tana;
	if(!empty($tana_label))  			$form_info .= "&tana_label=".$tana_label;
	if(!empty($tana_type))  			$form_info .= "&tana_type=".$tana_type;
	if(!empty($tana_source_default)) 	$form_info .= "&tana_source_default=".$tana_source_default;
	if(!empty($tana_level_source)) 		$form_info .= "&tana_level_source=".$tana_level_source;

	//Pour ne pas se retrouver en conflit avec le signe "=" on écrit le nom du signe
	if(!empty($tana_level_operand)){
		switch ($tana_level_operand){
			case "=" :
				$form_info .= "&tana_level_operand=egal";
				break;
			case ">" :
				$form_info .= "&tana_level_operand=sup";
				break;
			case "<" :
				$form_info .= "&tana_level_operand=inf";
				break;
		}
	}
	$error_num = "error3";

	header("location:setup_tana_new.php?erreur=".$error_num."&action=".$action.$form_info."&family=".$family);
	exit;
}

if ($tana_type=='na') {
	//echo "aqui"; exit;
	$tanatitre="Network Aggregation";
	if($tana_source_default == "itself") $tana_source_default = $tana;

	$nombre_existant = 0;
	$query = "SELECT agregation_level FROM $table WHERE agregation='$tana_level_source'";
	$result = $database->execute($query);

	$nombre_existant = $database->getNumRows();
	if ($nombre_existant>0) {
		$row = $database->getRow($query);
		$tana_level=$row['agregation_level'];
	}

	switch($tana_level_operand) {
		case "=" : break;
		case ">" : $tana_level++; break;
		case "<" : $tana_level--; break;
	}
}

//verifie qu'il n'y a pas d'aggreg ayant le meme name existant
$nombre_existant = 0;
$query = "SELECT agregation FROM $table WHERE agregation='$tana' AND family='$family'";
$resultat = $database->execute($query);
$nombre_existant = $database->getNumRows();

if ($nombre_existant==0) {
	if ($action==1) {

		$sdna_id = generateUniqId('sys_definition_network_agregation');
		$query_max = " select max(agregation_rank) as nb from $table ";
		$row = $database->getRow($query_max);
		$rank = $row["nb"];
		$rank ++;

                // 07/06/2011 BBX -PARTITIONING-
                // Correction des casts
		$query = "INSERT INTO $table(
									 agregation_rank,agregation,agregation_type,agregation_label,source_default,
									 on_off,agregation_name,agregation_level,level_operand,level_source,family, sdna_id)
		VALUES ($rank,'$tana','text','$tana_label','$tana_source_default',1,'$tana',$tana_level,'$tana_level_operand','$tana_level_source','$family', '$sdna_id')";

		$database->execute($query);

		// exit;
		// exec('sleep 5');
	}
}

//echo "location:$comebacktana?tana_type=$tana_type&action=2&tana=$tana";
//header("location:$comebacktana?tana_type=$tana_type&action=2&tana=$tana");
?>
<html>
    <head>
        <title>New network aggregation</title>
        <script type="text/javascript">
            /**
             * Fonction de redirection permettant de fermer le popup et
             * recharger la page principale
             *
             * 17/08/2010 OJT : Correction bz16848
             */
            function redirect()
            {
                window.opener.location='setup_tana_index.php?tana_type=na&family=<?=$family?>&product=<?=$product?>';
                self.close();
            }
        </script>
    </head>
    <body onload="redirect();"></body>
</html>
