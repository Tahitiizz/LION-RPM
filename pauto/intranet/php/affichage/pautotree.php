<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14

	- maj 01/08/2007, benoit : ajout du parametre '$family' au constructeur de la classe 'pauto()'

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
*	@cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
*/
?>
<?
/*
*	16/06/2006 - Copyright Acurio
*
*	Composant de base version cb_1.2.0.2p
*
*	Parser version gb_1.0.0b
*/
?>
<?
	/*
		- maj 29 06 2006 christophe ajout du fichier edw_function dans les includes.
		- maj 25 10 2006 christophe :
			On vérifie si il existe des enregistrement avec l'image Function.png dans la table sys_pauto_family si oui
				on remplace cette image par function1.png.
	*/

	//une class javascript est appele la class tree

	//une class pauto est appele
	/*
	la classe pauto est liée aux tables sys_pauto

	la table sys_pauto_family contient une requete dans le champs query_family qui permet de creer un branche.
	la table cible de query_family doit tjrs contenir des elements tel que
			id_elem
			id_parent
			libelle
			position ou ordre
	Dans la requete les noms n'ont pas d'importance seul l'ordre,par exemple.
	select id_menu,id_menu_parent,libelle_menu from....order by position asc
	*/
	session_start();
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
//	include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");
	global $niveau0,$path_skin, $database_connection;

	// Récupération des données transmises via l'URL.
	$product		= $_GET['product'];
	$family		= $_GET['family'];
	$id_univers	= $_GET['id_univers'];
	
	// connexion à la base de données
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$db_prod = Database::getConnection($product);

?>
<html>
	<head>
		<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css"/>
		<link rel="stylesheet" href="<?=$niveau0?>css/pauto.css"/>
		<script src="<?=$niveau0?>js/classTree.js"></script>
		<script src="<?=$niveau0?>js/AcurioTree.js"></script>
	</head>
<body>
<?
	// On vérifie si l'image FUnction.png est présente dans sys_pauto_family.
	$query = "
		UPDATE sys_pauto_family
		SET icon_element='function1.png'
		WHERE icon_element='Function.png'
	";
	$db_prod->execute($query);

	include("pauto.class.php");

	// 01/08/2007 - Modif. benoit : ajout du parametre '$family' au constructeur de la classe 'pauto()'
	$id_page		= 1;
	$nbligne		= 2;
	$nbcolonne	= 3;
	$pauto=new pauto($id_univers,false,true,false, $family,$product);

?>
</body>
</html>
