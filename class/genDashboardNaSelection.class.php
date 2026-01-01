<?
/*
* 	@cb50212@
*	26/02/2010
*
*	- maj 26/02/2010 - MPR : Correction du BZ 12572 : On supprime la limite sur le nombre d'éléments réseau
 *      13/09/2010 NSE bz 17818 : search toujours visible : suppression de la redondance de l'attribut style
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- 14/08/2007 christophe : ajout de message sur le rollover des icônes de sélection des éléments enfants.
*	- 16/07/2007 christophe : ajout d'un paramètre au constructeur de classe $select_child=false par défaut.
*	> permet d'afficher une icône à côté de chaque élément réseaux pour déterminer si on doit sélectionner les éléments réseaux enfants.
*	- 10/07/2007 christophe : Dans l'édition des alarmes les options suivantes ne sont pas disponibles : load preferences et reset.
*	- 28/06/2007 christophe on stocke le mode d'affichage dans la variable globale js _modeNaSelection
*	- 21/06/2007 christophe : modification constructeur.
*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*
* 	- 05/04/2007 christophe : modification de la fonction getNaFamilyList.
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
*	- 28 02 2007 christophe : correction bug FS 494/402, suppression du bouton save.
*
*/
?>
<?
/*
*	@cb21001_gsm20010@
*
*	08/12/2006 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.01
*
*	Parser version gsm_20010
*
*	- maj 16 01 2007 christophe : l'image de la sélection des NA change i l'utilisateur a sélectionné
*	des éléments réseaux.
*	- maj 27/12/2006 maxime : affichage des NA de type cell uniquement si elles sont actives
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
*
*	-- maj 24 11 2006 christophe : gestion des caractères spéciaux pour le javascript , fontion construisTableauJS
*/
?>
<?
/*
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?
	/*
		Permet d'afficher la liste des NA pour le dahsboard générique.

		Les network aggregation choisies sont stockées dans le tableau de session
		$_SESSION["selecteur_general_values"]["list_of_na"] = na@na_value@na_label|na@na_value@na_label|na@na_value@na_label...

		$_SESSION["selecteur_general_values"]["list_of_na_mode"] : définit le mode
				> 'dashboard_generique'
				> 'dahsbaord_normal'

		- maj 25 09 2006 christophe : la pseudo fenêtre ne peux plus être déplacée.
		- maj 21 11 2006 christophe : une croix apparait sur le bouton close (image).
		- maj 22 11 2006 chrisotphe : pour afficher la liste des NA, on se base sur la requête de la table sys_sélecteur properties.
		
		_constructeur genDashboardNaSelection($family, $na_base, $select_child, $database_connection)
		$family :
			si = 'all' : on affiche les NA de toutes les familles. (les NA présents dans plusieurs familles ne seront affichés qu'une seule fois)
			si = 'ept' : on affiche les NA de la famille passée en paramètre
		$mode :
			Mode de fonctionnement en fonction de l'interface dans laquelle la séleciton des NA est utilisée.
			='dashboard_normal' : utilisé dans le sélecteur d'un dashboard normal.
			='dashboard_generique' : utilisé dans le sélecteur du dashboard générique.
			='interface_edition' : la sélection des NA est utilisée pour sélectionner une liste de NA dans une interface (my profile user, édition des alarmes...).
			
		- 16/07/2007 christophe : ajout d'un paramètre au constructeur de classe $select_child=false par défaut.
			> permet d'afficher une icône à côté de chaque élément réseaux pour déterminer si on doit sélectionner les éléments réseaux enfants.
		
			- 06/02/2009 - maj SLC - ajout parametre product
	*/

	class genDashboardNaSelection {

		var $family;
		
		// Constructeur
		function genDashboardNaSelection($family, $database_connection, $mode, $select_child=false,$product = '')
		{

			$this->debug = get_sys_debug('selection_na_dashboard_generique');

			$this->product = $product;
			$this->family = $family;

			// connexion à la base de donnée
			// $this->db = new DataBaseConnection($product);
			// mise en commentaire car n'ai jamais appelée dans la classe

			$this->nbLimiteElements = get_sys_global_parameters('investigation_dashboard_max_selection',0,$product);
			$this->selectChild = $select_child;
			$this->mode = $mode;
			$_SESSION["selecteur_general_values"]["list_of_na_mode"]  = $this->mode;
			
			if ( $this->mode == 'interface_edition_alarme' )
				$_SESSION['na_precedente_na_selection'] != '';

			$this->display();	// affichage.
		}

		/*
			Permet de transformer un tableau PHP $tableauPHP
			en un tableau javascript de nom $nomTableauJS.
			ATTENTION : à exécuter entre 2 balises html script.
			On utilise la fonction js decodeURIComponent pour pouvoir gérer la transmission de caractères spéciaux entre
			javascirpt et php.
		*/
		function construisTableauJS($tableauPHP, $nomTableauJS){
			echo $nomTableauJS." = new Array();";
			for($i = 0; $i < count($tableauPHP); $i++){
				if(!is_array($tableauPHP[$i])){
					echo $nomTableauJS."[".$i."] = decodeURIComponent('".$tableauPHP[$i]."');";
				}
				else{
					construisTableauJS($tableauPHP[$i], $nomTableauJS."[".$i."]");
				}
			}
			return;
		}


		/*
			Crée le div contenant toutes les NA à afficher.
		*/
		function display(){

			global $niveau0;

			// Div principal
			echo "<div id='window_select_na' style='width:400px; display:none;'>";
				
				echo "
					<div id='selection_na_loading' style='display:none;'>
						<span class='texteGris'>Loading content &nbsp; <img src='".$niveau0."/images/icones/loading_anim.gif'/></span>
					</div>
				";
				
				// Affichage de la recherche.
				$this->display_search();
				
				// Affichage du contenu.
				echo "<div id='contenu_na_selection' class='contenu_na_selection_class'>";
				echo "</div>";

				// displayJsVars()
				/*if($this->debug){
					echo "
						<div><input type='button' class='bouton' onclick='displayJsVars()' value='Debug javascript'/></div>
					";
				}*/
				// Affichage des boutons : afficher contenu sélectionné, reset, close.
				echo '
					<div style="padding:5px; height:30px; background-color:#ececec; margin:2px;">
						<div style="border:1px #aba9a9 solid; padding:2px; width:110px; float:left; background-color:#dad8d8;">
							<label class="texteGrisPetit">
								'.__T('U_NA_SELECTION_LABEL_OPTIONS').'
							</label>
							&nbsp;
							';
				// 10/07/2007 christophe : Dans l'édition des alarmes les options suivantes ne sont pas disponibles : load preferences et reset.
				if ( $this->mode != 'interface_edition_alarme' )
				{
					echo '
								<input type="button" class="bouton_favoris" onclick="loadUserPreferences()" value=""
									onmouseover="updateNaselectionStatus(\''.__T('U_NA_SELECTION_BT_LOAD_PREFERENCES_LABEL_ROLLOVER').'\')" 
									onmouseout="updateNaselectionStatus(\'\')"/>
									&nbsp;
							';
				}
				echo '
							<input type="button" class="bouton_view_selection" onclick="displaySessionTab()" value=""
								onmouseover="updateNaselectionStatus(\''.__T('U_NA_SELECTION_BT_VIEW_SELECTION_LABEL_ROLLOVER').'\')" 
								onmouseout="updateNaselectionStatus(\'\')"/>
						</div>';
				echo '
						<div style="float:right;">';
				// 10/07/2007 christophe : Dans l'édition des alarmes les options suivantes ne sont pas disponibles : load preferences et reset.		
				if ( $this->mode != 'interface_edition_alarme' )
				{
					echo '
								<input type="button" class="bouton" onclick="razSession(); closeNaselection(); checkNaSelect();" value="'.__T('U_NA_SELECTION_BT_RESET_LABEL').'"
									onmouseover="updateNaselectionStatus(\''.__T('U_NA_SELECTION_BT_RESET_LABEL_ROLLOVER').'\')" 
									onmouseout="updateNaselectionStatus(\'\')"/>
								&nbsp;&nbsp;';
				}
				echo '
							<input type="button" class="bouton" onclick="closeNaselection(); checkNaSelect();" 
								value="'.__T('U_NA_SELECTION_BT_SAVE_LABEL').'"
								onmouseover="updateNaselectionStatus(\''.__T('U_NA_SELECTION_BT_SAVE_LABEL_ROLLOVER').'\')" 
								onmouseout="updateNaselectionStatus(\'\')"/>
						</div>
					</div>
					<div id="selection_des_na_message" class="texteGris" style="padding:5px;"></div>
				';

			echo "</div>"; // fermeture div id=window_select_na
			?>
			<script type="text/javascript">
				/*
					Initialisation des variables javascript
					> nécessaire au bon fonctionnement de la sélection des NA.
				*/
			<?
				$this->construisTableauJS($liste_na_for_js, 'listeNaJS');
				// maj 26/02/2010 - MPR : Correction du BZ 12572 : On supprime la limite sur le nombre d'éléments résea
				$nb_limited = ( $this->mode != 'interface_edition_alarme' ) ? $this->nbLimiteElements : "-1";
			?>
				var _nbElements = 0;	// nombre d'éléments sélectionnés.
				var _nbLimiteElements = <?=$nb_limited?>; // nombre maximum d'éléments sélectionnés.
				var _idCurrentElement = '';	// barre actuelement sélectionnée.
				var _naSelectedJS = '';
				var _product = '<?=$this->product?>'; // produit
				var _family = '<?=$this->family?>'; // famille
				var _listeSelectedNa = new Array(); // liste des na sélectionnées.
				// 28/06/2007 christophe : on stocke le mode d'affichage.
				var _modeNaSelection = '<?=$this->mode?>';
				// 11/07/2007 message erreur js pour l'interface d'édition des alarmes.
				var _errorSelectOneNetworkElement = '<?=__T('U_NA_SELECTION_ERROR_MSG_NO_NETWORK_ELEMENT_SELECTED_IN_ALARM')?>';
				// 14/08/2007 christophe : ajout de message sur le rollover des icônes de sélection des éléments enfants.
				var _msgRollOverSelectChildren = '<?=__T('U_MSG_ROLLOVER_ICON_SELECT_NA_CHILD_ALARM')?>';
				var _msgRollOverUnSelectChildren = '<?=__T('U_MSG_ROLLOVER_ICON_UNSELECT_NA_CHILD_ALARM')?>';
				// 16/07/2007 christophe : Détermine si on affiche l'icône de sélection des éléments enfants.
				var _selectChild = '<?=$this->selectChild?>';
			</script>
			<?
			// On doit remplir le tableau javascript _listeSelectedNa si des na ont été sélectionnées et que la page a été rechargée.
			$tab_na = "";
			if(isset($_SESSION["selecteur_general_values"]["list_of_na"]))
			{
				$liste = explode('|',$_SESSION["selecteur_general_values"]["list_of_na"]);
				foreach($liste as $elem){
					$na_value_svg = explode('@',$elem);
					$tab_na[] = $na_value_svg[1]."_".$na_value_svg[0];
				}
			}
			if($tab_na != "")
			{
			?>
				<script>
			<?
				$this->construisTableauJS($tab_na, 'tab_na_js');
			?>
				_listeSelectedNa = tab_na_js;
				_nbElements = _listeSelectedNa.length;
				</script>
			<?
			} else {
			?>
				<script>
					_listeSelectedNa = new Array();
				</script>
			<?
			}

		}
		
		/*
			Permet d'afficher le moteur de recherche.
		*/
		function display_search()
		{
                    // 13/09/2010 NSE bz 17818 : search toujours visible : suppression de la redondance de l'attribut style
			echo "
				<div style='margin:4px;display:none;' id='div_search'>
					<fieldset style='padding:2px;'>
						<form name='form-na-search' id='form-test' style='margin:0;'
						action=\"javascript:selectNetworkElement()\" >
							<label for='na-search-input' class='texteGrisPetit'>
								".__T('U_NA_SELECTION_LABEL_SEARCH_ON')."<span id='div_search_on_na'></span>
							</label> 
							<br />
				            <input type='text' name='na-search-input' id='na-search-input' autocomplete='off' 
								class='zoneTexteStyleXP' style='width:340px;'/>
							&nbsp;
				            <input type='submit' id='na-search-submit' class='bouton_go' value='' />
				        </form>
					</fieldset>
				</div>
			";
		}



	} // fin class


?>
