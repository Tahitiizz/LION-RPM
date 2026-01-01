<?
/*
*	@cb41000@
*
*	Composant de base version cb_4.1.0.00
* 
*	- 25/11/2008 - SLC - gestion multi-produit
*
*      maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
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
<?php
/*
	- maj 17 05 2006 : mise à jour du champ caché id_query de builder_report_graph_result.php ligne 30
	- maj 02/06/2006, benoit : verification de l'inégrité du nom de la query avant sauvegarde ou maj
*/
session_start();
include($repertoire_physique_niveau0."php/environnement_liens.php");
include_once($repertoire_physique_niveau0."php/database_connection.php");

// gestion multi-produit - 21/11/2008 - SLC
include_once('../affichage/connect_to_product_database.php');

// l'identifiant id_query est passé dans l'URL
// si l'identifiant est vide on insère un enregistrement sinon on le met à jour
$query_name = 	$_POST["query_name"];
$data = 		/*stripslashes(*/$_POST["data"]/*)*/;
//var_dump($data);
$requete = 		$_POST["requete"];
$formula_ids = 	$_POST["formula_ids"];
$id_query = 	$_POST["id_query"];
$family = 		$_POST["family"];
$remplacer = 	$_POST["remplacer"];
$liste_formula = "{" . $formula_ids . "}";
$formula_ids = 	explode(",", $formula_ids);
$nbr_fomula = 	count($formula_ids);

function checkQueryName( $query_name, $db_prod){

	$query = "SELECT DISTINCT * FROM report_builder_save WHERE texte='" . trim($query_name) . "' LIMIT 1";
	
	$res = $db_prod->execute($query);
	$nombre_query_identique = $db_prod->getNumRows();
	
	if($nombre_query_identique == 0 ){
		return true;
	}else
		return false;
}

// 07/06/2011 BBX -PARTITIONING-
// Correction de l'échappement des quotes
$unserializedData = unserialize($data_builder_report);
$newCondition = array();
foreach($unserializedData['condition_hidden'] as $condition)
{
    $condition = str_replace("\'","\''",$condition);
    $newCondition[] = $condition;
}
$unserializedData['condition_hidden'] = $newCondition;
$data_builder_report = serialize($unserializedData);

// 06/01/2011 BBX
// Ajout de slash pour échapper les quotes merdiques
// BZ 20007
//$data = addslashes($data_builder_report);
$data = $data_builder_report;


if (($id_query != "") && ($remplacer == "0")) {
    $creation_date = date("Y/m/d"); //crée la date du jour;

	if(ereg("[^[:space:]a-zA-Z0-9*_.-]", $query_name)){
		echo "<script>alert('Special chars are not allowed');history.back()</script>";
	}
	else if(trim($query_name) == ""){
		echo "<script>alert('The name of your query is empty');history.back()</script>";
	}
	else 
	{
		$requete_mise_a_jour = "UPDATE report_builder_save SET  date_creation='$creation_date' ,texte='" . trim($query_name) . "', requete='$data',query='$requete',nbr_formula='$nbr_fomula',ids_formula='$liste_formula'   WHERE (id_query='$id_query')";
	}
} else {
    $creation_date = date("Y/m/d"); //crée la date du jour;
	
	if(ereg("[^[:space:]a-zA-Z0-9*_.-]", $query_name)){
		echo "<script>alert('Special chars are not allowed');history.back()</script>";
	}
	else if(trim($query_name) == ""){
		echo "<script>alert('The name of your query is empty');history.back()</script>";
	}
	// 27/07/2009 - Correction du bug 10576 - Le nom de la query enregistrée doit être unique
	else if ( !checkQueryName($query_name, $db_prod) ){
		echo "<script>alert('The name of your query already exists');history.back()</script>";
	}
	else 
	{
		$requete_mise_a_jour = "INSERT INTO report_builder_save (id_user, texte,requete,type,on_off,date_creation,query,nbr_formula,ids_formula,date_derniere_utilisation,nbr_utilisation,family) VALUES ('$id_user','".trim($query_name)."', '$data', 'private', '1', '$creation_date','$requete' ,'$nbr_fomula','$liste_formula','$creation_date','1','$family')";		
	}
} 

$res = $db_prod->execute($requete_mise_a_jour);
if ($id_query == "" && $remplacer != "0") {
	$last_oid = $db_prod->getPgLastOid();
	$last_id_query = " SELECT oid, id_query FROM report_builder_save WHERE oid=$last_oid ";
	$row = $db_prod->getrow($last_id_query);
	//echo "> ".$row["id_query"];
	?>
		<script>
			window.opener.document.getElementById('id_query').value = <?=$row["id_query"]?>;
		</script>
	<?
}
?>
<html>
<head>
<script>
function close_window()
         {
	 self.close();
         }
</script>
<title>Query saved/updated</title>
<link rel="stylesheet" href="<?=$lien_css?>" type="text/css">
</head>
<body bgcolor="<?=$couleur_fond_global?>">
<table width="100%" height="100%" border="0">
 <tr>
     <td align="center">
         <font class="font_12_b">Your Query has been saved/updated.</font>
         <p>
         <!-- maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer -->
         <input type="submit" name="Submit" value="Close Window" class="bouton" onMouseOver="style.cursor='pointer';" onclick="close_window();">
     </td>
 </tr>
</table>
</body>
</html>
