<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01

	- maj 10/05/2007, benoit : correction des warnings via des conditions sur la presence de données et des       masquages via le caractère '@'
	
	- maj 10/05/2007, benoit : ajout de fonctions permettant de corriger les requetes avec liste de na            serialisées (recalcul de la longueur de la chaine serialisée, echappement des caractères) 

*/
?>
<?php
/*
* 29/11/2005 : GH : lors de l'ajout d'une requete à un autre utilisateur la famille n'était pas prise en compte
*
*
*
*/
session_start();
// détermine le type de données présent dans la requete
// parcourt dans un premier temps l'ensemble des information presentes dans le select , puis parcourt les conditions
function determine_type_donnees($donnees)
{
    global $data_type;
    $type_donnees = array();
    $data = unserialize($donnees);
    $donnees_select = $data["donnees_hidden"]; // on récupére les information du select
    foreach($donnees_select as $donnees_tmp) { // on les parcours toutes informtions
        $array_donnees_tmp = explode(":", $donnees_tmp);
        if ($array_donnees_tmp[0] == 4) { // si ce sont des données ( alcatel0  motorola0 ect.. ect...)
                if ($array_donnees_tmp[4] == "edw_mixed") { // si la donnée est mixed
                        if (!in_array("mixed", $type_donnees)) // on vérifie que la donnée n' est pas deja présente
                            $type_donnees[] = "mixed"; // on l ajoute a la liste
                    } else { // si ce c'est pas mixed
                            $tmp = $array_donnees_tmp[4]; //$tmp est de la forme : edw_alcatel_0
                        $tmp1 = explode("_", $tmp);
                        $tmp = $tmp1[2]; // on recupere le "numéro" du type de donnée
                        $tmp = ucfirst($tmp1[1]) . "-" . $data_type[$tmp]; // on convertit le numéro en sa signification : 0=cell stat  1= adj stat
                        if (!in_array($tmp, $type_donnees)) // on vérifie que la donnée n' est pas deja présente
                            $type_donnees[] = $tmp; // on l ajoute a la liste
                    }
                }
                if ($array_donnees_tmp[0] == 6) { // si l'information est une formule le type de donnée peut etre "edw_motorala_x","edw_alcatel_x""edw_motorala_x,edw_alcatel_x"
                        if (strrchr($array_donnees_tmp[4], ",")) { // vérifie si des information motorola ET alcatel sont présente
                                if (!in_array("mixed", $type_donnees))
                                    $type_donnees[] = "mixed"; // si c'est le cas ars on ajoute mixed a la liste des données
                            } else { // sinon on détermine le texte a afficher de la meme maniere que pour les données standarts
                                $tmp = $array_donnees_tmp[4];
                                $tmp1 = explode("_", $tmp);
                                $tmp = $tmp1[2];
                                $tmp = ucfirst($tmp1[1]) . "-" . $data_type[$tmp];
                                if (!in_array($tmp, $type_donnees))
                                    $type_donnees[] = $tmp;
                            }
                        }
                    }

                    $condition_hidden = $data["condition_hidden"]; // le travail est identique pour les conditions
                    foreach($condition_hidden as $donnees_tmp) {
                        $array_donnees_tmp = explode(":", $donnees_tmp);
                        if ($array_donnees_tmp[0] == 4) {
                            if ($array_donnees_tmp[4] == "edw_mixed") {
                                if (!in_array("mixed", $type_donnees))
                                    $type_donnees[] = "mixed";
                            } else {
                                $tmp = $array_donnees_tmp[4];
                                $tmp = explode("_", $tmp);
                                $tmp = $tmp[count($tmp)-1];
                                $tmp = $data_type[$tmp];
                                if (!in_array($tmp, $type_donnees))
                                    $type_donnees[] = $tmp;
                            }
                        }
                        if ($array_donnees_tmp[0] == 6) {
                            if (strrchr($array_donnees_tmp[4], ",")) {
                                if (!in_array("mixed", $type_donnees))
                                    $type_donnees[] = "mixed";
                            } else {
                                $tmp = $array_donnees_tmp[4];
                                $tmp = explode("_", $tmp);
                                $tmp = $tmp[count($tmp)-1];
                                $tmp = $data_type[$tmp];
                                if (!in_array($tmp, $type_donnees))
                                    $type_donnees[] = $tmp;
                            }
                        }
                    }
                    $type = implode("/", $type_donnees);
                    return $type;
                }
                // détermine l'ensemble des champs contenus dans la zonne "select" de la requete, le renvoi en format html
                function determine_select($donnees)
                {
                    $data = unserialize($donnees);

                    $donnees_select = $data["donnees_hidden"]; // on écupére l'ensemble des données issues de la zonne select
                    
					// 10/05/2007 - Modif. benoit : ajout d'une condition sur le nombre de données de '$donnees_select'
					
					if (count($donnees_select) > 0) {
						foreach($donnees_select as $donnees_tmp) { // pour chaque données on récupére le "label"
							if ($donnees_tmp) {
								$array_donnees_tmp = explode(":", $donnees_tmp);
								$select[] = strtolower($array_donnees_tmp[1]);
							}
						}						
					}

					// 10/05/2007 - Modif. benoit : on masque les warnings via '@'

                    $select = @array_unique($select); // on vérifie qu'aucune donnée ne soit en double
                    $select_ligne = array();
                    $ligne_en_cours = "";
                    // une fois la liste des données crée on la formate en html
                    
					// 10/05/2007 - Modif. benoit : ajout d'une condition sur le nombre de données de '$select'
					
					if ((count($select) > 0) && ($select[0] != "")) {
						foreach($select as $champ_select) {
							if ((strlen($ligne_en_cours) + strlen($champ_select)) > 30) { // on vérifie que la ligne ne sera pas trop longue avec une donnée suplémentaire
									// si la ligne devient trop longue :
									$select_ligne[] = $ligne_en_cours; // on stocke alors la ligne
								$ligne_en_cours = $champ_select; // et on en crée une nouvelle
							} else { // si la ligne peut encore s'allonger
								if ($ligne_en_cours != "") // on vérifie que la ligne existe deja
									$ligne_en_cours .= "," . $champ_select; // et on lallonge
								else
									$ligne_en_cours = $champ_select; // sinon on la crée
							}
						}
					}

                    $select_ligne[] = $ligne_en_cours;
                    $select = implode("<br>", $select_ligne); // on explose le tableau de ligne avec des saut de ligne <br>
                    return $select;
                }
                // détermine l ensemble des condition  présentes dans la requete sous la forme html:
                // condition1<br>condition2<br>condition3<br>condition4..........
                function determine_condition($donnees)
                {
                    $data = unserialize($donnees);
                    $donnees_select = $data["condition"]; // les données sur lesquels porte les conditions
                    $operateur = $data["op_condition"]; // les opérateurs
                    $value = $data["value_condition"]; // et les données saisie par l'utilisateur
                    $condition = array();
                    for($compteur = 0;$compteur < count($value);$compteur++) { // on parcourt l'ensemble des conditions
                        if ($value[$compteur]) // on vérifie que la condition est bien complete ( la derniere condition peut etre vide
                            $condition[] = $donnees_select[$compteur]." ".$operateur[$compteur]." ".$value[$compteur]; //on joint la donnée,l'opérateur et la valeur saisie
                    }
                    $condition = implode("<br>", $condition); //on sépareur toutes les conditions par un retour  a la ligne
                    return $condition;
                }
                // mais en forme les informations necessaire a l'affichae des fenetre volantes informative
                // retourne les information sous la forme d'un tableau :
                // $information_fenetre_volante["titre"]
                // $information_fenetre_volante["message"]
                function generer_fenetre_volante($id_query)
                {
                    $query = "select * from report_builder_save where id_query=" . $id_query; // on selectionne toutes les infos sur la requete
                   					
					$query = pg_query($query) ;
                    $query = pg_fetch_array($query);

					// 10/05/2007 - Modif. benoit : nettoyage de la requete via la fonction 'clean_report_builder_requete()'

					$donnees = clean_report_builder_requete($query["requete"], $query["family"]);

                    $select = determine_select($donnees); // on détermine les champs contenu dans le select
                    $condition = determine_condition($donnees); // on détermine les conditions
                    $query_info = "select username from users where id_user=" . $query["id_user"]; // on recupere l'user name du créateur de la requete
                    $result = pg_query($query_info) ;
                    $owner = pg_fetch_array($result);
                    $owner = $owner["username"];
                    // on génére le message directement en htm a partir des infos recupérées
                    $information_fenetre_volante["message"] = "<table><tr><td align=center><font class=font_11_b>Owner : " . $owner . "</td></tr><tr><td align=center><font class=font_11_b>Creation date : " . $query["date_creation"] . "</td></tr><tr><td><table><tr><td><font class=font_11_b>Select </td><td><font class=font_11>" . $select . "</td></tr><tr><td><br></td><td></td></tr><tr><td><font class=font_11_b>Condition </td><td><font class=font_11>" . $condition . "</td></tr></table></td></tr></table>";
                    // on génere le titre de la fenetre volante
                    $information_fenetre_volante["titre"] = "Query Details : " . $query["texte"];
                    return $information_fenetre_volante;
                }
                // fonction permettant de changer l'utilisateur d'une requete
                // plus précisement d'effectuer une copie de la requete pour l'utilisateur , la nouvelle requete aura ses statistiques mises a zéro
                function changer_owner_query($id_query_to_add)
                {
                    global $id_user;
                    $query_info = "select * from report_builder_save where id_query=" . $id_query_to_add; // on recupere les infos sur la requete
                    $result = pg_query($query_info) ;
                    $query_info = pg_fetch_array($result);
                    $creation_date = date("Y/m/d"); // on recupere la date courante
                    // on recrée une requete identique en changeant l'id_user
                    // la date de création et de derniere utilisation sont positionnées a la date du jour
                    // le nombre d'utilisation est remis a 0
                   	
					$query = "INSERT INTO report_builder_save (id_user, texte,requete,type,on_off,date_creation,query,nbr_formula,ids_formula,date_derniere_utilisation,nbr_utilisation,family) VALUES ('$id_user','" . $query_info["texte"] . "', '" . $query_info["requete"] . "', 'private', '1', '$creation_date','" . addslashes($query_info["query"]) . "' , '" . $query_info["nbr_formula"] . "', '" . $query_info["ids_formula"] . "','$creation_date','0','".$query_info["family"]."')";

					// 10/05/2007 - Modif. benoit : nettoyage de la requete d'insertion la query utilisateur avant son insertion en base

                    pg_query(format_query_to_save($query, $query_info["family"]));
                }

// 10/05/2007 - Modif. benoit : ajout des 2 fonctions ci-dessous

// Fonction permettant de "nettoyer" une requete du builder report sauvegardée. Ce nettoyage est impératif lorsque une liste de na a été utilisé dans la requete car la serialisation de cette liste provoque des erreurs dans la sauvegarde de la chaine serialisée (ajout d'antislashs permettant l'integration de la chaine dans la base comptés comme caractères)

function clean_report_builder_requete($data, $family)
{
	// Selection des listes d'agregation disponibles pour la famille

	$sql_liste = "SELECT cell_liste FROM my_network_agregation WHERE family='$family'";
	$req_liste = pg_query($sql_liste);

	if(pg_num_rows($req_liste) > 0){

		while($row = pg_fetch_row($req_liste)){

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
	
	return $data;
}

// Fonction permettant de formater une requete avant son insertion en base. Ce formatage est impératif lorsque la requete comporte une liste de na (il faut alors échapper les quotes présentes dans la liste)

function format_query_to_save($query, $family)
{
	// Selection des listes d'agregation disponibles pour la famille

	$sql_liste = "SELECT cell_liste FROM my_network_agregation WHERE family='$family'";
	$req_liste = pg_query($sql_liste);

	if(pg_num_rows($req_liste) > 0){
		while($row = pg_fetch_row($req_liste)){
			if (!(strpos($query, $row[0]) === false)) {
				$query = str_replace($row[0], addslashes($row[0]), $query);
			}
		}
	}
	return $query;
}

?>