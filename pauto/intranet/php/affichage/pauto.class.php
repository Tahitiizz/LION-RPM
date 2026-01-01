<?
/*
 * @cb50401
 *
 *  06/09/2010 NSE DE Firefox bz 16865 : Drag & Drop Ko
 */
?><?
/*
*	@cb4100@
*
*	14/11/2007 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.0
*
*	- maj 15/06/2009 - MPR : Correction du bug 9600 - Erreur js lors d'un drag d'un raw/kpi
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
*
*	- maj 17/04/2008 Benjamin : j=0 au lieu de j=1 dans get_tree_branches_sql_all_families BZ6362
*	- maj 15/02/2008 christophe : quand on est en admin, on n'affiche pas la branche avec 'Yours GTMs'
*	- maj 05/02/2008 christophe : ajout de id_user qui est utilisé dans les requêtes.
*	- maj 05/12/2007 - maxime : On ajoute une branche à l'arbre regroupant toutes les familles. Les rapports deviennent multi-familles
*	- maj 06/12/2007 - maxime : Création d'un fichier demon lorsque le mode debug est activé ( fichier debug : demon_report_builder)
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14

	- maj 01/08/2007, benoit : ajout du parametre '$family' au constructeur de la classe

	- maj 01/08/2007, benoit : on ne fait plus de requete pour selectionner la famille, celle-ci fait partie des     variables de classe instanciées par le constructeur

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
*	16/06/2006 - Copyright Acurio
*
*	Composant de base version cb_1.2.0.2p
*
*	Parser version gb_1.0.0b
*/
?>
<?
/*
* Classe de PAUTO.
* permet de construie le menu de la page automatique (treeview).
* @package pauto
* @author
* @version V1 2005-09-20
*	- maj 01 03 2006 christophe : variable de session pr filtrer l'affichage des kpi et raw se trouvant dans les graphs du dashboard
	- maj 28 06 2006 christophe : si le mot designer est trouvé on le remplace par le paramètre publisher : get_family_sql()
	- maj 30/10/2006 xavier : si un label dépasse du cadre, on fait apparaitre un ascenseur au lieu de passer à la ligne.
*/

Class pauto {
	// -------------------------------------------- Attributs--------------------------------------------//

	/**
	 * Univers concerné
	 * @access private
	 * @var string
	 */
	var $univers;


	// -------------------------------------------- Méthodes -------------------------------------------//
	// 01/08/2007 - Modif. benoit : ajout du parametre '$family' au constructeur de la classe
	/**
	 * Constructor
	 *
	 *  @acces private
	 *  @param  string $univers : univers concerné ( dashboard , gtm, report ...)
	 *  @param integer $ordre : ordre d'affichage des éléments de l'arbre
	 *  @param boolean $drag : drag activé / désactivé
	 *  @param boolean $drop : drop activé / désactivé
	 *  @param string $family  :  famille concernée
	 *  @param integer $product : produit concerné
	 */
	function pauto($univers,$ordre,$drag,$drop, $family, $product = ''){

		//$this->debug = 1;
		if ($this->debug) {
			echo "<div class='debug'><div class='function_call'>pauto(univers=<strong>$univers</strong>, ordre=<strong>$ordre</strong>, drag=<strong>$drag</strong>, drop=<strong>$drop</strong>, family=<strong>$family</strong>, product=<strong>$product</strong>)</div></div>";
		}


		$this->init_script();	// initialisation du script .js
		$this->debug		= get_sys_debug('create_report'); // Mode debug activé / désactivé
		$this->id_univers	=$univers;
		$this->family		= $family;
		$this->product		= $product;
		$this->ordre		= $ordre;
		$this->drop		= $drop;
		$this->drag		= $drag;

		global $niveau0;
		$this->niveau0		= $niveau0;

                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$this->db = Database::getConnection($this->product);

		$this->init(); // initialistion des familles
		$this->tree_racine_sql(); // Construction de la racine de l'arbre
		$this->get_family_sql(); // Récupération des types d'éléments de l'arbre ( dash astellia / admin dash / alarm static ... )
		$this->demon("","<hr/>Construction de l'arbre","blue");
		$this->get_tree_level_0(); // Construction du niveau 0
		$this->demon("","Ajout des éléments dans chaque branche","blue");

		// Récupération de tous les éléments
		if ($this->id_pauto == 'report') { // pour toutes les familles
			$this->get_tree_branches_sql_all_families();
			$this->display_tree();
		} else {
			$this->get_tree_branches_sql();
			$this->tree_display();

		}
		$this->demon("","fin - CONSTRUCTION DES RAPPORTS MULTI-FAMILLES - ".date('d/m/Y H:i'),"red");
	} // End function pauto

	/**
	 * Fonction qui écrit dans un fichier démon quand le mode debug est activé ( nom du fichier : demon_report_builder )
	 *
	 * @acces private
	 * @param string $text : texte à afficher
	 * @param string $title : titre
	 * @param string $color : couleur du titre
	 */
	function demon ( $text , $title = '', $color = 'black' ) {

		if ($this->debug) {
			echo "<div class='debug'><div class='function_call'>demon( text=<strong>$text</strong> , title=<strong>$title</strong> = '', color =<strong>$color </strong>= 'black' )</div></div>";
		}

		if( $this->debug and $this->id_pauto == 'report'){
			ob_start();

			if ( !empty ( $title ) )
				echo '<span style="color: '. $color .'"><u><b>'. $title .' : </b></u></span><br/>';

			switch( gettype($text) ) {
				case 'bool' :
				case 'boolean' :
					echo ( $text ? 'TRUE' : 'FALSE' ) .'<br />';
					break;

				case 'float' :
				case 'double' :
				case 'int' :
				case 'integer' :
				case 'string' :
					echo $text .'<br />';
					break;

				case 'NULL' :
					echo 'NULL<br />';
					break;

				default:
					echo "<pre>";
					print_r ( $text );
					echo "</pre>";
			}
			unset($str);
			$str = ob_get_contents();
			ob_end_clean();

			$filename_demon = 'demon_report_builder_'. date('Ymd') .'.html';
			$rep_demon = '/home/'.$this->niveau0 .'file_demon';

			// Nom du fichier de démon avec la date du jour
			if ( is_writable($rep_demon) ) {
				// modif 11:05 07/11/2007 Gwen
					// Met de résoudre le problème de fopen
				if ( !file_exists($rep_demon.'/'.$filename_demon) ) {
					exec('touch '.$rep_demon.'/'.$filename_demon);
					exec('chmod 777 '.$rep_demon.'/'.$filename_demon);
				}
				// Si le fichier existe on écrit à la fin sinon on le crée
				if ( $handle = fopen($rep_demon.'/'.$filename_demon, 'a+') ) {
					fwrite($handle, $str);
					fclose($handle);
				}

			}else{
				echo "impossible d'ecrire dans le fichier";
			}
		}
	} // End function demon

	function init(){

		if ($this->debug) {
			echo "<div class='debug'><div class='function_call'>init()</div></div>";
		}

		$this->id_pauto = $this->db->getone(" SELECT id_pauto FROM sys_pauto_univers WHERE id_univers='$this->id_univers' ");
		$this->demon("id_pauto : ".$this->id_pauto);

		$this->demon("","début - CONSTRUCTION DES RAPPORTS MULTI-FAMILLES - ".date('d/m/Y H:i'),"green");
		$this->demon("","<hr/>Initialistaion des familles","blue");

		if ($this->id_pauto == 'report') {
			// On récupère toutes les familles
			$query = "SELECT family, family_label FROM sys_definition_categorie";
			$res = $this->db->getall($query);
			$cpt = 0;
			if ($res) {
				foreach ($res as $row) {
					$this->family['name'][$cpt]	= $row['family'];
					$this->family['label'][$cpt]	= $row['family_label'];
					$this->demon("family $cpt : ".$this->family['name'][$cpt]." / ".$this->family['label'][$cpt]);
					$cpt++;
				}
			}
		}
	}

	function init_script() {
		if ($this->debug) {
			echo "<div class='debug'><div class='function_call'>init_script()</div></div>";
		}

	?>
	<SCRIPT LANGUAGE="JavaScript">

		function update_object_table() {
			url = "pauto_update_object_table.php?pos="+structure[1]+"&object_class="+structure[2]+"&object_id="+structure[3];
			alert('url: '+url);
			window.location = url;
			// alert(structure[1]+"/"+structure[2]+"/"+structure[3]);
		}

	</script>
	<?
	}


	function tree_display(){
		if ($this->debug) {
			echo "<div class='debug'><div class='function_call'>tree_display()</div></div>";
		}

		?>
		<div valign="center">
			<table border=0 class="tabMenuPauto" align="center">
				<tr>
				<td width="100%">
					<table cellpadding="5" cellspacing="2" border="0" width="100%" align="center">
						<tr align="center">
							<td><img src="<?=$this->niveau0?>images/pauto/dragndrop.gif"/></td>
						</tr>
						<tr align="center">
							<td class="texteGrisPetit" align="center">
							<?
								// Recuperation du label du produit
								$productInformation = getProductInformations($this->product);
								$productLabel = $productInformation[$this->product]['sdp_label'];
								echo $productLabel."&nbsp;:&nbsp;";

								$family_information = get_family_information_from_family($this->family, $this->product);
								echo (ucfirst($family_information['family_label']));
							?>
							</td>
						</tr>
					</table>
				</td>
				</tr>
				<tr>
				<td <? // 30/10/2006 xavier ?>nowrap="nowrap">
					<div class="texteGrisPetit" >
					<script language="Javascript">
						if (document.getElementById) {
							<? $sfr_id_00 = str_replace('.','_',$this->tree[0][0]["object_id_tree"]); ?>
							var <?=$sfr_id_00;?> = new WebFXTree('<?=$this->tree[0][0]["label"]?>','','','','','<?=$sfr_id_00;?>');
								<?=$sfr_id_00;?>.openIcon = '<?=$this->tree[0][0]["icon"]?>';
								<?=$sfr_id_00;?>.icon = '<?=$this->tree[0][0]["icon"]?>';

							<?
							for ($j=1;$j<count($this->tree);$j++) {
								for ($i=0;$i<count($this->tree[$j]);$i++) {
									$sfr_id = str_replace('.','_',$this->tree[$j][$i]["object_id_tree"]);
									// echo $j."-".$i."object id=".$this->tree[$j][$i]["label"]."<br>";
									?>
									var <?=$sfr_id;?> = new WebFXTreeItem('<?=$this->tree[$j][$i]["label"]?> ', '','','','','<?=$sfr_id;?>','<?=$this->tree[$j][$i]["isobject"];?>','<?=$this->tree[$j][$i]["object_class"]?>','<?=$this->tree[$j][$i]["object_id_elem_in"]?>');
										<?=$sfr_id;?>.openIcon = '<?=$this->tree[$j][$i]["icon"]?>';
										<?=$sfr_id;?>.icon = '<?=$this->tree[$j][$i]["icon"]?>';
										<?=$sfr_id;?>.drag = '<?=$sfr_id;?>';
										<?=$sfr_id;?>.gestion_ordre = '<?=$this->ordre;?>';
										<?=$sfr_id;?>.drop_enable = '<?=$this->drop;?>';
										<?=$sfr_id;?>.drag_enable = '<?=$this->drag;?>'
										<?=$sfr_id;?>.IdElemTable ='<?=$this->tree[$j][$i]["object_id"]?>';
										<?=$sfr_id;?>.niveau ='<?=$j?>';

										<?=$this->tree[$j][$i]["parent"];?>.add(<?=$sfr_id;?>);

								<?}
							}?>

							document.write(<?=$sfr_id_00;?>);
						}

						// 06/09/2010 NSE DE Firefox bz 16865 : passage de event en paramètre
                        function setupDrag(event,object_id,id_img,label_txt,objectclass,id_object){
							// alert('setupDrag');
							var passedData = [id_img,label_txt,objectclass,id_object]
							event.dataTransfer.setData("text", passedData.join(":"))
							event.dataTransfer.effectAllowed = "copy"
							// alert(passedData);
						}

						// maj 15/06/2009 - MPR : Correction du bug 9600 - Erreur js lors d'un drag d'un raw/kpi
						// 06/09/2010 NSE DE Firefox bz 16865 : passage de event en paramètre
                        function cancelDefault(event) {
							event.dataTransfer.dropEffect = "copy"
						    event.returnValue = false
						}

					</script>
					</div>
				</td>
				</tr>
			</table>
		</div>
	<?
		if ($this->debug) echo $this->db->displayQueries();
	}

	// connection à la BD postgresql.
	function connection(){
		if ($this->debug) {
			echo "<div class='debug'><div class='function_call'>connection()</div></div>";
		}

	}

	// racine de l'arbre
	function tree_racine_sql() {
		if ($this->debug) {
			echo "<div class='debug'><div class='function_call'>tree_racine_sql()</div></div>";
		}

		$query = " select * FROM sys_pauto_univers where  id_univers='$this->id_univers' ";
		$result = $this->db->getall($query);
		$result_nb = count($result);

		for ($k = 0;$k < $result_nb;$k++) {
			$result_array = $result[$k];
			$this->univers_name	= $result_array["univers_name"];
			$this->univers_icon	= $result_array["univers_icon"];
			$this->id	= $result_array["id"];
		}

		$this->tree[0][0]["object_id_tree"]		= $this->univers_name;
		$this->tree[0][0]["object_id"]			= $this->univers_name;
		$this->tree[0][0]["object_id_elem_in"]	= $this->univers_name;
		$this->tree[0][0]["object_class"]		= "univers_racine";
		$this->tree[0][0]["label"]				= "&nbsp;".$this->univers_name;
		$this->tree[0][0]["parent"]			= "0";
		$this->tree[0][0]["icon"]				= $this->niveau0."images/pauto/".$this->univers_icon;
		$this->tree[0][0]["isobject"]			= "0";
		$this->tree[0][0]["id"]				= $this->id;
	}


	function get_family_sql() {

		if ($this->debug) {
			echo "<div class='debug'><div class='function_call'>get_family_sql()</div></div>";
		}

		$query = "select * FROM sys_pauto_family where  id_univers='$this->id_univers' order by family_order";
		$result = $this->db->getall($query);
		$result_nb = count($result);

		if ($result) {
			foreach ($result as $result_array) {
				$this->family_query[]	= $result_array["family_query"];
				$this->icon_element[]	= $result_array["icon_element"];
				$this->icon_family[]		= $result_array["icon_family"];
				$this->family_order[]	= $result_array["family_order"];

				// On remplace designer par publisher de sys_global_parameters
				if( substr_count( strtolower( $result_array["family_name"] ),"designer" ) >= 1 ){
					$temp_val = str_replace("designer",get_sys_global_parameters("publisher"),strtolower($result_array["family_name"]));
					$this->family_name[] = ucfirst($temp_val);
				}else{
					$this->family_name[] = $result_array["family_name"];
				}

				$this->id_family[]=		$result_array["family_name"];
			}
		}
	}

	function get_tree_level_0(){

		if ($this->debug) {
			echo "<div class='debug'><div class='function_call'>get_tree_level_0()</div>";
		}

		$id = 0;
		$cpt = 2;

		$nb_family_name = count($this->family_name);

		$userParam = getUserInfo($_SESSION['id_user']);

		for ($i=0;$i<$nb_family_name;$i++) {
			$display_branche = true;

			// maj 15/02/2008 christophe : quand on est en admin, on n'affiche pas la branche avec 'Yours GTMs'
			if ( $userParam['profile_type'] == 'admin' && $this->family_name[$i]=='Your GTMs' )
				$display_branche = false;

			if ( $display_branche ) {

				$id_value = $this->family_order[$i];
				if( $this->id_pauto=='report' )
					$id_value = $cpt;

				$this->tree[1][$i]["object_id_tree"]		= $this->univers_name.$id_value;
				$this->tree[1][$i]["object_id"]			= $this->univers_name.$id_value;
				$this->tree[1][$i]["object_id_elem_in"]	= $this->univers_name.$id_value;
				$this->tree[1][$i]["object_class"]		= "famille_racine";
				$this->tree[1][$i]["label"]				= $this->family_name[$i];
				$this->tree[1][$i]["parent"]			= $this->univers_name;
				$this->tree[1][$i]["icon"]				= $this->niveau0."images/pauto/".$this->icon_family[$i];
				$this->tree[1][$i]["isobject"]			= "0";
				$this->tree[1][$i]["id"]				= $this->id_family[$i];

				$id_parent = $this->univers_name.$cpt;
				$cpt++;

				// maj 05/12/2007 - maxime : On ajoute une branche à l'arbre regroupant toutes les familles
				if ($this->id_pauto == 'report') {
					$msg.= "<h4>id parent $i: $id_parent</h4>";
					$msg.= "<table border='1'>";

						foreach($this->family['name'] as $k=>$family){
							$msg.= "<tr><td>&nbsp;&nbsp;&nbsp;child $k => ".$this->family['label'][$k]."(".$family.") </td>";
							$msg.= "<td>&nbsp;&nbsp;&nbsp;object_id_tree : ".$this->univers_name.$cpt." </td><td>object_id : ".$this->univers_name.$cpt."</td>";
							$msg.= "<td>&nbsp;&nbsp;&nbsp;object_id_elem_in : ".$this->univers_name.$cpt." </td><td>object_class : famille_racine</td>";
							$msg.= "<td>&nbsp;&nbsp;&nbsp;label : &nbsp;".$this->family['label'][$k]." </td><td>&nbsp;&nbsp;&nbsp;id : $cpt </td></tr>";

							$this->tree[2][$id]["object_id_tree"]	= $this->univers_name.$cpt;
							$this->tree[2][$id]["object_id"]		= $this->univers_name.$cpt;
							$this->tree[2][$id]["object_id_elem_in"]	= $this->univers_name.$cpt;
							$this->tree[2][$id]["object_class"]		= "famille_racine";
							$this->tree[2][$id]["label"]			= "&nbsp;".$this->family['label'][$k];
							$this->tree[2][$id]["parent"]			= $id_parent;
							$this->tree[2][$id]["icon"]			= $this->niveau0."images/pauto/".$this->univers_icon;
							$this->tree[2][$id]["isobject"]			= "0";
							$this->tree[2][$id]["id"]				= $family;

							$cpt++;
							$id++;
						}
					$msg.= '</table>';
				}
			}
		}
		if ($this->debug) {
			echo "<pre>";
			print_r($this->tree);
			echo "</pre></div>";
		}
		$this->demon($msg);
	}

	//determine le nombre de niveau d'une branche
	function get_nb_branches_level(){
		if ($this->debug) {
			echo "<div class='debug'><div class='function_call'>get_nb_branches_level()</div></div>";
		}

		return 1;
	}

	function get_tree_branches_sql(){
		if ($this->debug) {
			echo "<div class='debug'><div class='function_call'>get_tree_branches_sql()</div>";
		}

		// nécessaire car on a des '$family' dans les family queries qu'on va évaluer. (eval())
		$family = $this->family;

		// 01/08/2007 - Modif. benoit : on ne fait plus de requete pour selectionner la famille, celle-ci fait partie des variables de classe instanciées par le constructeur
		$id_pauto = $this->db->getone(" select id_pauto from sys_pauto_univers where id_univers='$this->id_univers' ");

		if (isset($_SESSION["id_page_overtime"])) $id_page = $_SESSION["id_page_overtime"];

		//a gerer plusieurs niveau dans la branche
		$j=0;
		$this->niveau_old=0;
		for ($i=0;$i<count($this->family_name);$i++){
			// maj 05/02/2008 christophe : ajout de id_user qui est utilisé dans les requêtes.
			$id_user = $_SESSION['id_user'];
			$query_eval = $this->family_query[$i];
			eval( "\$query_eval = \"$query_eval\";" );

			$query = $query_eval;
			if ($this->debug) echo "<pre>$query</pre>";
			$result = $this->db->getall($query);
			$result_nb = count($result);

			for ($k = 0;$k < $result_nb;$k++){
				//dans la table sys_pauto_family le champs select de query family commence tjrs
				//par object_id,id_parent,libelle
				//si l'id_parent 0 est detecté alors on renvoie $this->family_order[$i]
				//id_parent et object_id sont remplace par family_name._.object_id / family_name._.id_parent

				$result_array = $result[$k];
				$this->id				= $result_array['id'];
				$this->object_class		= $result_array['object_class'];
				$this->object_id		= $result_array['object_id'];
				$this->object_id_elem_in	= $result_array['object_id_elem_in'];
				$this->id_parent		= $result_array['object_id_parent'];
				$this->id_label			= $result_array['object_libelle'];
				$this->niveau			= $result_array['object_niveau']+1;//pour eviter le zero
				//$this->niveau=2;
				//echo  "niveau".$this->niveau."<br>";

				$this->niveau_new=$this->niveau;
				if ($this->niveau_new>$this->niveau_old) {
					$this->niveau_old=$this->niveau_new;
					$j=0;
				}

				$this->tree[$this->niveau][$j]["object_class"]		= $this->object_class;
				$this->tree[$this->niveau][$j]["object_id"]		= $this->object_id;
				$this->tree[$this->niveau][$j]["object_id_elem_in"]	= $this->object_id_elem_in;
				$this->tree[$this->niveau][$j]["object_id_tree"]	= $this->object_class.$this->id;
				$this->tree[$this->niveau][$j]["label"]			= $this->id_label;

				//echo "j=$j-".$this->family_name[$i]."famille order=".$this->family_order[$i]."<br>";

				if ($this->niveau<3){
					$this->tree[$this->niveau][$j]["parent"]=$this->univers_name.$this->family_order[$i];
				} else {
					$this->tree[$this->niveau][$j]["parent"]=$this->id_parent;
				}

				$this->tree[$this->niveau][$j]["icon"]=$this->niveau0."images/pauto/".$this->icon_element[$i];

				//
				// Gestion de l'affichage d'icônes différentes dans le GTM et le data range builder pour les KPI et les RAW COUNTER.
				//
				if ($id_pauto == "data_range" || $id_pauto == "gtm"){
					// On vérifie si la data range est déjà configurée;
					// si oui on affiche une autre icone pr le signaler à l'utilisateur.
					$data_type = $this->object_class == 'counter' ? 'raw' : $this->object_class;
					$icone = $this->object_class == 'counter' ? 'icone_tree_pauto_valid_vert.gif' : 'icone_tree_pauto_valid_orange.gif';
					$id_element = $this->object_id_elem_in;
					$query_verif_data_range = " select * from sys_data_range_style where id_element='$id_element' and data_type='$data_type' ";
					$result_data_range= $this->db->getall($query_verif_data_range);
					$result_nb_data_range = count($result_data_range);
					//echo $query_verif_data_range."<br>".$result_nb_data_range;
					if ($result_nb_data_range > 0)
						$this->tree[$this->niveau][$j]["icon"]=$this->niveau0."images/icones/".$icone;
					//exit;
				}
				//
				//
				//

				$this->tree[$this->niveau][$j]["isobject"]="1";
				$j++;
				$this->niveau_old=$this->niveau_new;
			}
		} //fin pour chaque famille

		if ($this->debug) {
			echo "</div>";
		}

	}

	// On récupère l'id parent de l'élément
	function get_tree_branches_sql_all_families() {
		if ($this->debug) {
			echo "<div class='debug'><div class='function_call'>get_tree_branches_sql_all_families()</div></div>";
		}

		// 01/08/2007 - Modif. benoit : on ne fait plus de requete pour selectionner la famille, celle-ci fait partie des variables de classe instanciées par le constructeur
		$id_pauto = $this->db->getone(" select id_pauto from sys_pauto_univers where id_univers='$this->id_univers' ");

		if (isset($_SESSION["id_page_overtime"])) $id_page = $_SESSION["id_page_overtime"];

		//a gerer plusieurs niveau dans la branche
		$j=1;
		$this->niveau_old=0;
		$nb_families = count($this->family_name);
		$cpt = 0 ;
		for ($i=0;$i<$nb_families;$i++){
			// maj 6/12/2007 - maxime :  On boucle sur toutes les familles
			$this->demon($this->family['name']);
			foreach($this->family['name'] as $f=>$family){

				$query_eval = $this->family_query[$i];
				eval( "\$query_eval = \"$query_eval\";" );

				$query=$query_eval;
				$tab_sql[$family][] = "$family : query ".$this->family_name[$i]." / $query";
				//__debug($query);
				$result = $this->db->getall($query);
				$result_nb = count($result);

				// maj 17/04/2008 Benjamin : while au lieu de for
				while ($result_array= array_shift($result)) {
					//dans la table sys_pauto_family le champs select de query family commence tjrs
					//par object_id,id_parent,libelle
					//si l'id_parent 0 est detecté alors on renvoie $this->family_order[$i]
					//id_parent et object_id sont remplace par family_name._.object_id / family_name._.id_parent

					$this->id				= $result_array['id'];
					$this->object_class		= $result_array['object_class'];
					$this->object_id		= $result_array['object_id'];
					$this->object_id_elem_in	= $result_array['object_id_elem_in'];
					$this->id_parent		= $result_array['object_id_parent'];
					$this->id_label			= $result_array['object_libelle'];
					$this->niveau			= $result_array['object_niveau']+1;//pour eviter le zero

					//$this->niveau=2;
					//echo  "niveau".$this->niveau."<br>";
					$this->niveau_new	= $this->niveau;
					if ($this->niveau_new>$this->niveau_old){
						$this->niveau_old=$this->niveau_new;
						// maj 17/04/2008 Benjamin : j=0 au lieu de j=1 BZ6362
						$j=0;
					}

					$this->tree[$this->niveau][$j]["object_class"]		= $this->object_class;
					$this->tree[$this->niveau][$j]["object_id"]		= $this->object_id;
					$this->tree[$this->niveau][$j]["object_id_elem_in"]	= $this->object_id_elem_in;
					$this->tree[$this->niveau][$j]["object_id_tree"]	= $this->object_class.$this->id;
					$this->tree[$this->niveau][$j]["label"]			= $this->id_label;

					//echo "j=$j-".$this->family_name[$i]."famille order=".$this->family_order[$i]."<br>";
					if ($this->niveau<3){
						$this->tree[$this->niveau][$j]["parent"]=$this->univers_name.$this->family_order[$i];
					} else {

						// $r = $this->tree[1][$i]['object_id'].'/'.$this->tree[1][$i]['label'];
						// $t = $this->tree[2][$f]['object_id'].'/'.$
						// $this->demon('t >');
						// $this->demon($t);
						// $this->demon('<br>r >')
						// $this->get_id_family($family,$this->tree[1][$i]['object_id_tree']);
						// $this->demon($this->tree[2]);

						$this->tree[$this->niveau][$j]["parent"] = $this->get_id_parent($family,$this->tree[1][$i]['object_id_tree']);
						$this->demon("id parent de ".$this->tree[$this->niveau][$j]["label"]." est ".$this->tree[$this->niveau][$j]["parent"]);
						// $this->tree[$this->niveau][$j]["parent"] = $this->tree[2][0]['object_id_tree'];
						// $this->demon("$family : id_parent de ".$this->tree[$this->niveau][$j]["label"]." => ".$this->tree[$this->niveau][$j]["parent"] );
					}

					$this->tree[$this->niveau][$j]["icon"]=$this->niveau0."images/pauto/".$this->icon_element[$i];
					$this->tree[$this->niveau][$j]["isobject"]="1";
					$j++;
					$this->niveau_old=$this->niveau_new;
				}

			}
		} //fin pour chaque famille
		$this->demon($tab_sql);
		// $this->demon($this->tree);
	}

	function get_id_parent($family,$id_type){
		if ($this->debug) {
			echo "<div class='debug'><div class='function_call'>get_id_parent(family=<strong>$family</strong>, id_type=<strong>$id_type</strong>)</div></div>";
		}

		$cpt = 0;
		$nb_families = count($this->family_name)*count($this->family['name']);
		$trouve = false;
		$this->demon("","Récupération ids familles","blue");
		// On récupère l'id de la famille pour le type l'élément ( astellia dashboard / admin dash / alarm static /....)
		$this->demon("<ul>");
		while ($cpt <=$nb_families and !$trouve){
			if( $id_type == $this->tree[2][$cpt]['parent'] and $family == $this->tree[2][$cpt]['id']){
				$id_parent = $this->tree[2][$cpt]['object_id_tree'];
				$this->demon("<li><b>parent => ".$this->tree[2][$cpt]['object_id_tree']." found </b></li>");

				$trouve = true;
			}else
				$this->demon("<li>parent => ".$this->tree[2][$cpt]['object_id_tree']."</li>");
			$cpt++;
		}
		$this->demon("</ul>");

		return $id_parent;

		// foreach($this->tree[2] as $values){
			// if( $id_type==$values['parent']){
				// $t[] = $values['object_id_tree'];
			// }
		// }

		// $this->demon($t);

	}


	function display_tree() {
		if ($this->debug) {
			echo "<div class='debug'><div class='function_call'>display_tree()</div></div>";
		}
		?>
		<div valign="center">
			<table border=0 class="tabMenuPauto" align="center">
				<tr>
					<td width="100%">
						<table cellpadding="5" cellspacing="2" border="0" width="100%" align="center">
							<tr align="center">
								<td><img src="<?=$this->niveau0?>images/pauto/dragndrop.gif"/></td>
							</tr>
							<? if ( $this->id_pauto != 'report' ) {	?>
								<tr align="center">
									<td class="texteGrisPetit" align="center"><u>Current family :</u>
										<?
										$family_information = get_family_information_from_family($this->family,$this->product);
										echo (ucfirst($family_information['family_label']));
										?>
									</td>
								</tr>
							<? } ?>
						</table>
					</td>
				</tr>
				<tr>
					<td <? // 30/10/2006 xavier ?>nowrap="nowrap">
					<div class="texteGrisPetit" >
					<script language="Javascript">
						if (document.getElementById){
							<?php $sfr_id_00 = str_replace('.','_',$this->tree[0][0]["object_id_tree"]); ?>
							var <?=$sfr_id_00 ?> = new WebFXTree('<?=$this->tree[0][0]["label"]?>','','','','','<?=$sfr_id_00 ?>');
								<?=$sfr_id_00 ?>.openIcon = '<?=$this->tree[0][0]["icon"]?>';
								<?=$sfr_id_00 ?>.icon = '<?=$this->tree[0][0]["icon"]?>';

							<?
							for ($j=1;$j<count($this->tree);$j++) {
								for ($i=0;$i<count($this->tree[$j]);$i++) {
									if ($this->tree[$j][$i]["object_id_tree"]!=NULL) {
										$sfr_id = str_replace('.','_',$this->tree[$j][$i]["object_id_tree"]);
										//echo $j."-".$i."object id=".$this->tree[$j][$i]["object_id_tree"]."<br>";
										?>
										var <?=$sfr_id ?> = new WebFXTreeItem('<?=$this->tree[$j][$i]["label"]?> ', '','','','','<?=$this->tree[$j][$i]["object_id_tree"];?>','<?=$this->tree[$j][$i]["isobject"];?>','<?=$this->tree[$j][$i]["object_class"]?>','<?=$this->tree[$j][$i]["object_id_elem_in"]?>');
											<?=$sfr_id ?>.openIcon = '<?=$this->tree[$j][$i]["icon"]?>';
											<?=$sfr_id ?>.icon = '<?=$this->tree[$j][$i]["icon"]?>';
											<?=$sfr_id ?>.drag = '<?=$sfr_id ?>';
											<?=$sfr_id ?>.gestion_ordre = '<?=$this->ordre;?>';
											<?=$sfr_id ?>.drop_enable = '<?=$this->drop;?>';
											<?=$sfr_id ?>.drag_enable = '<?=$this->drag;?>'
											<?=$sfr_id ?>.IdElemTable ='<?=$this->tree[$j][$i]["object_id"]?>';
											<?=$sfr_id ?>.niveau ='<?=$j?>';

											<?=$this->tree[$j][$i]["parent"];?>.add(<?=$sfr_id ?>);

										<?
									}
								}
							}
							?>
							document.write(<?=$sfr_id_00 ?>);
						}

						/*
						function setupDrag(object_id,id_img,label_txt,objectclass,id_object){
							var passedData = [id_img,label_txt,objectclass,id_object]
							event.dataTransfer.setData("text", passedData.join(":"))
							event.dataTransfer.effectAllowed = "copy"
							//alert(passedData)
						}
						*/
					</script>
					</div>
				</td>
				</tr>
			</table>
		</div>
	<?
	}
}//fin class


?>
