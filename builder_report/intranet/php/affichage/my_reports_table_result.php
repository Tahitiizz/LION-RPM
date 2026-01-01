<?
/*
*	@cb41000@
*
*	Composant de base version cb_4.1.0.00
* 
*	- 25/11/2008 - SLC - gestion multi-produit
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

// gestion multi-produit - 21/11/2008 - SLC
include_once('connect_to_product_database.php');

?>
<?php
$ERROR = array();
$WARNING = array();
if ($id_query) { // la page est affiché avecune requete en parametre , en premier on met a jour ses statistiques
	$sql = "select * from report_builder_save where id_query=" . $id_query;
	$row = $db_prod->getrow($sql);
	$requete = $row["query"];
	$family=$row["family"];
	include_once("report_builder_determiner_requete.php");	//on a besoin de la famille pour determiner la requete
	$date = date("Y/m/d"); // on met  jour la date de  derniere utilisation
	$nbr_utilisation = $query["nbr_utilisation"] + 1; // on augmente d'un son nombre d 'utilisation
	$sql = "update   report_builder_save set date_derniere_utilisation='$date',nbr_utilisation='$nbr_utilisation' where id_query=" . $id_query ;
	$db_prod->execute($sql); // on met toutes les informations a jour dans la BDD 

	$data = $query["requete"];

	// 08/06/2006 - Modif. BA : recherche de listes dans la chaine de données et, le cas echeant, modification de la taille stockée pour la chaine liste avec sa vraie valeur (la valeur indiquant la taille est par defaut erronée car elle tient compte des antislashs nécessaires à l'intégration de la chaine dans la BD)

	// Selection des listes d'agregation disponibles pour la famille
	$sql_liste = "SELECT cell_liste FROM my_network_agregation WHERE family='$family'";
	$req_liste = $db_prod->getall($sql_liste);

	if ($req_liste) {
		foreach ($req_liste as $row){

			// Si la liste fait partie de la chaine '$data', on effectue le traitement de "recomptage" des caracteres correspondant à la liste
			if(!(strpos($data, $row[0]) === false)){
				
				// On coupe '$data' en 2 parties à partir de la position de la liste
				$data_liste_deb = substr($data, 0, strpos($data, $row[0]));
				$data_liste_fin = substr($data, strpos($data, $row[0]));
				
				// On explose la premiere partie de la chaine '$data' pour trouver la position du nombre de caractères stockée pour la liste
				$tab_tmp = explode(':', $data_liste_deb);
				$idx_nb_car = count($tab_tmp) - 5;

				// On va parcourir la chaine '$data' explosée, compter le réel nombre de caractères de la liste et remplacer l'ancienne valeur par le nouveau nombre de caractères effectivement comptabilisé
				$tab_data_all = explode(':', $data);

				for ($i=0; $i < count($tab_data_all); $i++) {
					if (($tab_data_all[$i] == $tab_tmp[$idx_nb_car]) && ($tab_data_all[$i+4] == $row[0])) {
						$tab_data_all[$i] = strlen(substr($tab_data_all[$i+1], 1).":".$tab_data_all[$i+2].":".$tab_data_all[$i+3].":".$tab_data_all[$i+4].":");
					}
				}

				// Enfin, on recompose la chaine de données
				$data = implode(':', $tab_data_all);
			}
		}
	}
	
	$_POST = unserialize($data); 

	//$_POST = unserialize($query["requete"]); // on redonne a la page toutes les données necessaire au traitement de la requete
	$builder_report=new builder_report_requete ($family,$product);
	if ($ERROR) { // une erreur peut se produir sur les requetes sauvegardées du a la modification d'une formule la composant, plus pécisement si le type de donnée concerné par la formule est modifié et qu il se trouve alors incompatible avec les autres données présentes
		?>
		<script>
			alert("Query invalid due to a modification of one formula.");	// on affiche un message d'erreur
			parent.location.reload();								// on recharge le my_reports
		</script>
		<?php
		} else {
			// si il 'y a pas d'erreur on execute la requete généré
			echo "<table align=center>";
			foreach ($WARNING as $war) // on affiche les warnings
				echo "<tr valign='top'><td><font class='texteGrisBold'>$war</td></tr>";
			echo "</table>";
			echo "<br>";
			$builder_report->executer_requete();									// 	on exexutela requete
			$builder_report->afficher_resultat();										//	on affiche le resultat
		} 
	} 
?>
