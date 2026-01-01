<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- 01/08/2007 christophe : quand on retourne la liste des éléments sélectionné (cf case 'display'), on affiche l'icône de sélection 
*	des éléments réseaux enfants si définit.
*	- 18/07/2007 christophe : ajout de la sélection des éléments réseaux enfants.
*		> format de la chaine contenant la liste des éléments réseaux :
*			rnc@307@Cannes11@1|rnc@306@(306)|sai@10099_305@(10099_305)|...
*			>> rnc@307@Cannes11@1 : on ajoute un paramètre qui est soit 
*				- non présent ou égal à 0 : les éléments enfants ne sont pas sélectionnés.
*				- égal à 1 : les éléments enfants sont sélectionnés.
*	- 03/07/2007 christophe : chargement des préférences utilisateur.
*	- 21/06/2007 christophe : On conserve toutes les sélections indépendamment des familles et des interfaces.
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
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*
*	- 27 02 2007 christophe : correction bug FS 510, si 2 na de niveaux différents ont le même na_value
*	- 28 02 2007 christophe : ajout d'un htmlentities pour que les caractères spéciaux s'affichent correctement.
*
*/
?>
<?
/*
*	@cb21000@
*
*	08/12/2006 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.00
*
*	- 22 01 2007 christophe : correction d'un petit bug sur case 'nb_of_selected_element'.
*	- 16 01 2007 christophe :
*	> ajout de l'option action=display_popalt qui permet d'afficher la liste
*	des na sélectionnées dans un popalt lorsque l'utilisateur passe la souris au dessus de l'icône
* 	de sélection des na dans le sélecteur.
*	> ajout de l'action=nb_of_selected_element qui retourne le nombre d'éléments sélectionnés.
*
*/
?>
<?
/*
*	@cb20040@
*
*	30/11/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.40
*
*	- 01 12 2006 christophe : gestion des NA '00000000' / '000', voir FMAINT_MOD_20061130_5
*/
?>
<?
	/*
		Permet d'enregistrer / supprimer / afficher
		les netwoakr aggregation séectionnées par l'utilisateur
		dans le dahsboard générique.

		$action :
			si = 'reset' : vide du tableau de session toutes les na précédement sélectionnées.
			si = 'ajout' : on ajoute une na dans la tableau de session > Attention si la na est déjà présente, on la supprime du tableau. (c'est que l'utilisateur a décoché la checkbox)
			si = 'display' : on affiche la liste des na contenues dans le tableau de session.
		$na_value : nom de la na.
		$na : type de na.
		$na_label : label de la na.

		- maj 25 09 2006 christophe : modification message d'erreur en anglais in > from
	*/
	session_start();
	
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");

	global $database_connection, $niveau0;
	
	// Récupération des variables $_GET
	$action = 	(isset($_GET['action'])) ? $_GET['action'] : '' ;
	$na = 		(isset($_GET['na'])) ? $_GET['na'] : '' ;
	$na_value = (isset($_GET['na_value'])) ? $_GET['na_value'] : '' ;
	$na_label = (isset($_GET['na_label'])) ? $_GET['na_label'] : '' ;

	// Gestion du caractères + : le caractère + posait problème, on le remplace par @@@ (pourri mais temporaire).
	$na_label = str_replace("@@@", "+", $na_label);

	switch($action){
		case 'ajout':
			// Liste des éléments réseaux actuellement sélectionnés.
			$liste = (isset($_SESSION["selecteur_general_values"]["list_of_na"]))? $_SESSION["selecteur_general_values"]["list_of_na"] : "" ;
			
			if($liste != ""){
				// On vérifie si la na_value existe déjà si oui, on la supprime sinon on l'ajoute.
				$liste = explode('|',$liste);
				$new_list = "";
				$find = false;
				foreach($liste as $elem){
					$na_value_svg = explode('@',$elem);
					if( $na_value != $na_value_svg[1] ){
					//if( strcmp($na_value_svg[1],$na_value) > 0 ){
						$new_list .= $elem."|";
					} else {
						/*
							On compare également sur la longueur de la chaine pour ne pas confondre
							les NA qui sont de type '00000000' et '00'
						*/
						if( strlen($na_value) == strlen($na_value_svg[1]) && $na == $na_value_svg[0] )
							$find = true;
						else
							$new_list .= $elem."|";
					}
				}
				/*
					Si l'élément a été trouvé, il est supprimé de la nouvelle liste car il été déjà présent
					donc l'utilisateur a désélectionné la checkbox.
					Sinon la nouvelle network aggregation est ajoutée à la liste.
				*/
				if($find){
					$new_list = substr($new_list,0,-1);
					$_SESSION["selecteur_general_values"]["list_of_na"] = $new_list;
					$message = "<li> ".stripslashes(htmlentities($na_label))." removed from current selection </li>";
				} else {
					$_SESSION["selecteur_general_values"]["list_of_na"] .= "|".$na."@".$na_value."@".$na_label;
					$message = "<li> ".stripslashes(htmlentities($na_label))." added in currrent selection </li>";
				}
			} else {
				$_SESSION["selecteur_general_values"]["list_of_na"] = $na."@".$na_value."@".$na_label;
				$message = "<li>$tt ".stripslashes(htmlentities($na_label))." added in currrent selection </li>";
			}
			
			/*
				21/06/2007 christophe : On conserve toutes les sélections indépendamment des familles et des interfaces.
			*/
			$liste = (isset($_SESSION["network_element_preferences"]))? $_SESSION["network_element_preferences"] : "" ;
			
			if($liste != ""){
				// On vérifie si la na_value existe déjà si oui, on la supprime sinon on l'ajoute.
				$liste = explode('|',$liste);
				$new_list = "";
				$find = false;
				foreach($liste as $elem){
					$na_value_svg = explode('@',$elem);
					if( $na_value != $na_value_svg[1] ){
						$new_list .= $elem."|";
					} else {
						/*
							On compare également sur la longueur de la chaine pour ne pas confondre
							les NA qui sont de type '00000000' et '00'
						*/
						if( strlen($na_value) == strlen($na_value_svg[1]) && $na == $na_value_svg[0] )
							$find = true;
						else
							$new_list .= $elem."|";
					}
				}
				/*
					Si l'élément a été trouvé, il est supprimé de la nouvelle liste car il été déjà présent
					donc l'utilisateur a désélectionné la checkbox.
					Sinon la nouvelle network aggregation est ajoutée à la liste.
				*/
				if($find){
					$new_list = substr($new_list,0,-1);
					$_SESSION["network_element_preferences"] = $new_list;
				} else {
					$_SESSION["network_element_preferences"] .= "|".$na."@".$na_value."@".$na_label;
				}
			} else {
				$_SESSION["network_element_preferences"] = $na."@".$na_value."@".$na_label;
			}
			
			/////////////////////////////////
			

			break;
		
		// 18/07/2007 christophe : ajout de la sélection des éléments réseaux enfants.
		case 'select_children' :
			$liste_element_reseau = (isset($_SESSION["network_element_preferences"]))
				? $_SESSION["network_element_preferences"] 
				: "" ;
				
			/*
				Le format de stockage des éléments réseaux est :
				$na@$na_value@$na_label@ 0 ou 1 ou vide|... : pour le dernier paramètre si 0 ou vide,
				on n'affiche pas les éléments réseaux enfants si = 1 oui.
				On recherche si la chaine $na@$na_value@$na_label@1 existe.
				> si elle existe, c'est que l'utilisateur veut désactiver l'affichage des éléments enfants pour cet élément réseau,
				> sinon c'est que le format est $na@$na_value@$na_label ou $na@$na_value@$na_label@0 et donc l'utilisateur
				souhait activé l'affichage des éléments enfants pour cet élément réseau.
			*/
			$element_reseau = $na.'@'.$na_value.'@'.$na_label;
			//echo 'element reseau : '.$element_reseau.'<br>';
			if ( strpos($liste_element_reseau, $element_reseau.'@1') === false )
			{
				if ( strpos($liste_element_reseau, $element_reseau.'@0') === false )
				{
					$liste_element_reseau = str_replace($element_reseau, $element_reseau.'@1', $liste_element_reseau);
				}
				else
				{
					$liste_element_reseau = str_replace($element_reseau.'@0', $element_reseau.'@1', $liste_element_reseau);
				}
			}
			else
			{
				$liste_element_reseau = str_replace($element_reseau.'@1', $element_reseau.'@0', $liste_element_reseau);
			}
			$_SESSION["network_element_preferences"] = $liste_element_reseau;
			$_SESSION["selecteur_general_values"]["list_of_na"] = $liste_element_reseau;
			
			break;
		
		case 'reset':
			// 21/06/2007 christophe : on ne supprime de $_SESSION["network_element_preferences"] que les éléments sélectionné dans le sélecteur.
			$tab_network_element_preferences = array();
			$tab_selecteur_general_values_list_of_na = array();
			
			$tab_network_element_preferences_temp = 		explode('|', $_SESSION["network_element_preferences"]);
			$tab_selecteur_general_values_list_of_na_temp = explode('|', $_SESSION["selecteur_general_values"]["list_of_na"]);
		    
			foreach($tab_network_element_preferences_temp as $elem) {
				$tab_network_element_preferences[$elem] = $elem;
		    }
			
			foreach($tab_selecteur_general_values_list_of_na_temp as $elem) {
				$tab_selecteur_general_values_list_of_na[$elem] = $elem;
		    }
			
			$_SESSION["network_element_preferences"] = implode('|', array_diff($tab_network_element_preferences,$tab_selecteur_general_values_list_of_na) );
			
			
			// On supprime la variable de session et son contenu.
			if(isset($_SESSION["selecteur_general_values"]["list_of_na"])){
				unset($_SESSION["selecteur_general_values"]["list_of_na"]);
			}
			$message = "No element selected.";
			break;
		case 'display':
			// On affiche tout le contenu de la variable de session.
			$liste = (isset($_SESSION["selecteur_general_values"]["list_of_na"]))? $_SESSION["selecteur_general_values"]["list_of_na"] : "" ;
			if($liste != ""){
				$liste = explode('|',$liste);
				$titre = "<strong>List of selected elements (".count($liste).") :</strong>";
				$message .= "<fieldset><legend class='texteGris'>&nbsp;$titre&nbsp;</legend>";
				$message .= "<div style='height:80px; overflow:auto; margin:5px; border:1px #D0D0D0 solid;'>";
				foreach($liste as $elem){
					$na_value_svg = explode('@',$elem);
					
					/*
						- 01/08/2007 christophe : quand on retourne la liste des éléments sélectionné (cf case 'display'), on affiche l'icône de sélection 
						des éléments réseaux enfants si définit.
					*/
					$img = '';
					if ( isset($na_value_svg[3]) )
					{
						if ( $na_value_svg[3] == 1 )
						{
							$img = "
								<img src='".$niveau0."images/icones/unselect_child.png' style='cursor:pointer' 
									style='position:absolute; padding-top:5px;'/>
							";
						}
					}
					
					$message .= "<li>".stripslashes(htmlentities($na_value_svg[2]))." [".$na_value_svg[0]."]. $img </li>";
				}
				$message .= "</div></fieldset>";
			} else {
				$message = "No element selected";
			}
			break;
		case 'display_popalt' :
			// On affiche tout le contenu de la variable de session.
			$liste = (isset($_SESSION["selecteur_general_values"]["list_of_na"]))? $_SESSION["selecteur_general_values"]["list_of_na"] : "" ;
			if($liste != ""){
				$liste = explode('|',$liste);
				$message .= "List of selected elements (".count($liste).") :<br/>";
				foreach($liste as $elem){
					$na_value_svg = explode('@',$elem);
					$message .= stripslashes(htmlentities($na_value_svg[2]))." [".$na_value_svg[0]."], ";
				}
			} else {
				$message = "No element selected";
			}
			// On affiche au max 100 caractères.
			if (strlen($message) > 100)
			{
				$message = substr($message,0,100).'...';
			}
			break;
		case 'nb_of_selected_element':
			if ( isset($_SESSION["selecteur_general_values"]["list_of_na"]) ) {
				$nb = 0;
				if ( !empty($_SESSION["selecteur_general_values"]["list_of_na"]) ) $nb = 1;
			} else {
				$nb = 0;
			}
			$message = $nb;
			break;
		// 03/07/2007 christophe : charment des préférences utilisateur.
		case 'load_preferences':
			/*
				On charge les préférences utilisateurs.
				On ne doit charger que les préférences utilisateurs dont le NA
				est affiché dans a séleciton des NA.
				1. on enlève tous les éléments réseaux de la sélection courante.
				2. on récupère les éléments réseaux préférés de l'utilisateur sauvegardés en bdd.
				3. on ajoute les éléments réseaux de la bdd dans lé sélection courante si leur NA sont affichés dans la sélection des NA.
			*/
			
			// 1. on enlève tous les éléments réseaux de la sélection courante.
			$tab_network_element_preferences = array();
			$tab_selecteur_general_values_list_of_na = array();
			
			$tab_network_element_preferences_temp = 		explode('|', $_SESSION["network_element_preferences"]);
			$tab_selecteur_general_values_list_of_na_temp = explode('|', $_SESSION["selecteur_general_values"]["list_of_na"]);
		    
			foreach($tab_network_element_preferences_temp as $elem) {
				$tab_network_element_preferences[$elem] = $elem;
		    }
			
			foreach($tab_selecteur_general_values_list_of_na_temp as $elem) {
				$tab_selecteur_general_values_list_of_na[$elem] = $elem;
		    }
			
			$_SESSION["network_element_preferences"] = implode('|', array_diff($tab_network_element_preferences,$tab_selecteur_general_values_list_of_na) );
			
			// On vide la variable de session et son contenu.
			if(isset($_SESSION["selecteur_general_values"]["list_of_na"])){
				unset($_SESSION["selecteur_general_values"]["list_of_na"]);
			}
			
			// 2. on récupère les éléments réseaux préférés de l'utilisateur sauvegardés en bdd.
			$network_element_preferences_bdd = '';
			$q = " SELECT network_element_preferences FROM users WHERE id_user=".$_SESSION['id_user'];
			$res = pg_query($database_connection, $q);
			$nombre_resultat = pg_num_rows($res);
			if ($nombre_resultat > 0) {
				$row = pg_fetch_array($res, 0);
				$network_element_preferences_bdd = $row['network_element_preferences'];
			}
			
			
			
			// 3. on ajoute les éléments réseaux de la bdd dans lé sélection courante si leur NA sont affichés dans la sélection des NA.
			$tab_network_element_preferences_bdd = array();
			
			$tab_network_element_preferences_bdd_temp = explode('|', $network_element_preferences_bdd);
		    
			foreach($tab_network_element_preferences_bdd_temp as $elem) {
				$tab_temp = explode('@',$elem);
				$tab_network_element_preferences_bdd[$tab_temp[0]][] = $elem;
		    }
			
			// On parcourt la liste des NA affichés dans la sélection des NA.
			$liste_na = '';
			foreach ($_SESSION["na_listed_in_na_selection"] as $key=>$value)
			{
				if ( isset($tab_network_element_preferences_bdd[$value['value']]) )
				{
					foreach($tab_network_element_preferences_bdd[$value['value']] as $elem)
					{
						if ( empty($liste_na) )
							$liste_na .= $elem;
						else
							$liste_na .= '|'.$elem;
					}
				}
			}
			
			// On ajoute les préférences à la sélection courante.
			if ( !empty($liste_na) )
			{
				if ( empty($_SESSION["network_element_preferences"]) )
					$_SESSION["network_element_preferences"] = $liste_na;
				else
					$_SESSION["network_element_preferences"] .= '|'.$liste_na;
					
				$_SESSION["selecteur_general_values"]["list_of_na"] = $liste_na;
			}
			break;
			
		default:
			$message = "Rien";
			break;
	}

	echo $message;	// on affiche le message de sortie.
?>
