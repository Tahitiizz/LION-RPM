<?
/*
*	@cb51000@
*
*	10/11/2011 - Copyright Astellia
*
*	Composant de base version cb_5.1.0.0
*
* 	10/11/2011 ACS BZ 24545 Bad change product link on third axis family
* 
*/
?>
<?
/*
*	@cb41000@
*
*	07/11/2007 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.0
*
*	- maj 07/11/2008 - Maxime : On intègre ou non la liste des produits
*						Ajout d'un bouton pour changer de produit lorsque l'on affiche la sélection des familles
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
	- maj 30/11/2007, benoit : ajout du parametre '$condition' au constructeur de la classe. Ce parametre sert à ajouter une condition à la       requete de sélection des familles

	- maj 30/11/2007, benoit : ajout de '$this->conditions' dans les requetes pour traiter les conditions supplémentaires dans les requetes
*/
?>
<?
/*
*	@cb22014@
*
*	24/07/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- maj 08/08/2007 Jérémy : 	Ajout d'une alternative si l'axe3 est actif, pour l'affichage des familles
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
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
*	- 19/04/2007 christophe : ajout d'un paramètre dans le constructeur de la classe qui permet
*	si il est définit à true de n'afficher que les familles qui possèdent un 3ème axe.
*	$display_family_axe3 est à false par défaut.
*
*/
include_once($repertoire_physique_niveau0 . "class/DataBaseConnection.class.php");
?>
<?
	/*
		Permet d'afficher la liste des familles visibles.
		Chaque ligne / nom de famille a un lien vers la page $lien passée en paramètre, auquel on rajoute la famille sélectionnée.
		Si il n'y a qu'une seule famille, on fait une redirection automatique sur le lien passé en paramètre.
	*/

	Class select_family{

		// 30/11/2007 - Modif. benoit : ajout du parametre '$condition' servant à ajouter une condition à la requete de sélection des familles

		// $lien : page à afficher.
		// $argv : liste des variables présentes dans l'URL.
		// $titre : nom à afficher dans l'en-tête du fieldset.
		// $display_family_axe3 est à false par défaut, si il est définit à true de n'afficher que les familles qui possèdent un 3ème axe.
		// $condition : condition supplémentaire à ajouter à la requete de sélection des familles
		// maj 07/11/2008 - Maxime : On intègre ou non la liste des produits
		// $select_product : On décide si on affiche ou non la liste des produits et des familles

		//				$select_product = 0 <=> Affichage de la liste des familles uniquement
		//				$select_product = 1 <=> Affichage de la liste des produits puis celle des familles
		//				$select_product = 2 <=> Affichage de la liste des produits uniquement

		function select_family($lien, $argv, $titre, $display_family_axe3=false, $condition='',$select_product=1, $displayBp=false ){

			$this->titre               = $titre;
			$this->file                = $lien;
			$this->lien                = ($argv != "") ? $lien."?".$argv : $lien;
			$this->display_family_axe3 = $display_family_axe3;
			$this->condition           = $condition;
			$this->product_arg         = "";
			$this->id_menu_argv        = "?";
                        $this->displayBp           = $displayBp; // Gestion de l'affichage du produit blanc

			// 10/11/2011 ACS BZ 24545 Bad change product link on third axis family
			// add third axis family information
			if ($display_family_axe3) {
				$this->id_menu_argv .= "axe3=true&";
			}
			
			// On force l'insertion de l'id menu en cours dans l'url
			if (strpos($argv, "id_menu_encours")!==false and isset($_GET['id_menu_encours']) ) {

				$this->id_menu_argv .= "id_menu_encours=".$_GET['id_menu_encours'];

			}
			// On vérifie si le lien $lien contient déjà des paramètres dans l'URL. Cela permet de déterminer si on met lien.php?family= ou lien.php?toto=titi&family=.
			// On recherche donc le nombre d'occurence du caractère '?'.

			if( !isset($_GET['product']) and $select_product !== 0 ){

				$this->product_arg = ($argv != "") ? "&product=" : "?product=";
				$this->products = getProductInformations();

			}else{

				$this->id_product = $_GET['product'];
			}



			if( $select_product == 0 ){ // On renseigne la famille dans le cas où l'on ne sélectionne pas le produit
				$this->family_arg = ($argv != "") ? "&family=" : "?family=";
			}
			elseif( $select_product == 1 ) {

				$this->family_arg ="&family=";

			}
			$this->debut_tableau();

			// maj 06/11/2008 - maxime
			$this->select_product = $select_product;


			if($this->product_arg !== ""){

				$this->afficher_titre_product();
				$this->liste_products();


			}elseif( $this->select_product <> 2 ){

					$this->afficher_titre_family();
					$this->liste_familles();
			}


			$this->fin_tableau();
		}

		// Construit la structure du début du tableau.
		function debut_tableau(){
		?>
			<table width="100%" border="0" height="79%" cellspacing="0" cellpadding="3" align="center">
		<?
		}


		// Affiche l'image titre de la page de sélection des familles.
		function afficher_titre_family(){
			global $niveau0;
		?>
			<tr>
				<td align="center">
					<img src="<?=$niveau0?>images/titres/choose_a_family.gif"/>
				</td>
			</tr>
		<?
		}

		function afficher_titre_product(){
			global $niveau0;
		?>
			<tr>
				<td align="center">
					<img src="<?=$niveau0?>images/titres/choose_a_family.gif"/>
				</td>
			</tr>
		<?
		}


		function liste_products()
                {
                    global $niveau0;
                    $redirectLink = "<script type='text/javascript'>window.location = '$this->lien$this->product_arg{$product['sdp_id']}';</script>";

                    // Dans le cas ou il n'y a qu'un produit
                    if ( count( $this->products ) == 1 )
                    {
                        $product = array_shift( $this->products );
                        // Gestion du produit blanc en stadalone
                        if( !$this->displayBp && ProductModel::isBlankProduct( $product['sdp_id'] ) )
                        {
                            // Si il s'agit d'un Produit Blanc en standalone et qu'il
                            // ne doit pas être affiché, on affiche un message d'info
                            die( '<tr>
                                    <td align="center">
                                        <div class="infoBox" style="width:50%;text-align:center">
                                            This page cannot be viewed as no T&A application
                                            is configured with the T&A Gateway. <br/>Please configure
                                            at least one T&A application before trying to view this page
                                        </div>
                                    </td>
                                    </tr>'
                                );
                        }
                        else
                        {
                            echo "<script type='text/javascript'>
                                        window.location = '$this->lien$this->product_arg{$product['sdp_id']}';
                                    </script>";
                        }
                    }

                    // Dans le cas ou il y a 2 produits dont un produit blanc
                    else if ( ( count( $this->products ) == 2 ) && ( ProductModel::isBlankProduct( ProductModel::getIdMaster() ) ) )
                    {
                        if( !$this->displayBp )
                        {
                            // Recherche de l'identifiant du produit slave
                            $product = array_pop( $this->products );
                            echo "<script type='text/javascript'>
                                        window.location = '$this->lien$this->product_arg{$product['sdp_id']}';
                                    </script>";
                        }
                    }



			?>
			<tr>
				<td align="center" valign="top" width="100%">
				<table cellpadding="10" cellspacing="0" align="center" border="0" class="tabPrincipal">
				<tr>
				<td align="center">
					<fieldset>
					<legend class="texteGrisBold">&nbsp;<img src="<?=$niveau0?>images/icones/puce_fieldset.gif"/>&nbsp;Choose a product for your <?= $this->titre ?>&nbsp;</legend>
					<table cellpadding="5" cellspacing="0" align="center" border="0">
						<tr>
							<td align="center">
								<table cellpadding="5" cellspacing="0">
				<?
					foreach($this->products as $product)
                    {
                        // Le produit blanc doit-il être affiché ?
                        if ( !ProductModel::isBlankProduct( $product['sdp_id'] ) || $this->displayBp )
                        {
						?>
							<tr>
								<td class="texteGris">
								<li>

								<?
									if( $this->product_arg !== "" ){
										$url = $this->lien.$this->product_arg.$product['sdp_id'];
									}

								?>
									<a href='<?=$url?>' class='texteGris' style='font : normal 9pt Verdana, Arial, sans-serif;'>
										<?=$product["sdp_label"]?>
									</a>

									<?php

									if ($product['sdp_master'])
										echo "\n	- <strong style='color:#900'><small>Master</small></strong>";

									if ($product['sdp_master_topo'])
										echo "\n	- <strong style='color:#009'><small>Topology master</small></strong>";

									?>

								</li>
								</td>
							</tr>
						<?
                        }
					}


		}

		// Affiche la liste des familles.
		// Si il n'y a qu'une seule famille, on fait un redirection automatique.
		function liste_familles(){
			global $niveau0;
		?>
			<tr>
				<td align="center" valign="top" width="100%">
				<table cellpadding="10" cellspacing="0" align="center" border="0" class="tabPrincipal">
				<tr>
				<td align="center">
					<fieldset>
					<legend class="texteGrisBold">&nbsp;<img src="<?=$niveau0?>images/icones/puce_fieldset.gif"/>&nbsp;Choose a family for your <?=$this->titre?>&nbsp;<a href="<?=$this->file.$this->id_menu_argv?>" target="_top"><img id="change_family_choose_family" src="<?=$niveau0?>images/icones/change.gif" onMouseOver="popalt('Change Product');style.cursor='help';" onMouseOut='kill()' border="0"/></a>&nbsp;&nbsp;</legend>
					<table cellpadding="5" cellspacing="0" align="center" border="0">
						<tr>
							<td align="center">
								<table cellpadding="5" cellspacing="0">
								<?
									// On récupère la liste des familles à afficher.
									// 08/08/2007 Jérémy : ajout d'une alternative si l'axe3 est actif, on va récupérer seulement les familles
									//			de l'axe 3 qui sont actives, sinon on prend toutes les familles

									// 30/11/2007 - modif. benoit : ajout de '$this->conditions' dans les requetes pour traiter les conditions supplémentaires définies lors de la création de l'instance de la classe

									if ($this->display_family_axe3){

										$family_query =	 " SELECT DISTINCT t1.family_label, t1.family"
														." FROM sys_definition_categorie t1, sys_definition_network_agregation t2"
														." WHERE t1.on_off=1 AND t1.visible = 1"
														." AND t1.family = t2.family"
														." AND t2.axe = 3"
														." $this->condition"
														." ORDER BY family_label ASC";
									}
									else
									{
										$family_query =	 " SELECT * FROM sys_definition_categorie"
														." WHERE on_off = 1"
														." AND visible = 1"
														." $this->condition"
														." ORDER BY rank ASC";
									}

									$database = Database::getConnection( $this->id_product );
									$result_family = $database->getAll($family_query);

									$nombre_resultat= count($result_family); // pg_num_rows($result_family);

									if($nombre_resultat > 0){

										foreach($result_family as $ligne_famille){

											// - 19/04/2007 christophe
											$display = true;
											if ( $this->display_family_axe3 )
											{
												if ( !get_axe3($ligne_famille["family"], $this->id_product) )
													$display = false;
											}

											if ( $display )
											{
											?>
											<tr>
												<td class="texteGris">
													<li>
														<a href="<? echo($this->lien.$this->family_arg.$ligne_famille["family"]); ?>" class="texteGris" style="font : normal 9pt Verdana, Arial, sans-serif;">
															<?=$ligne_famille["family_label"]?>
														</a>
													</li>
												</td>
											</tr>
											<?
											}
										}

									}
                                                                        // 13/04/2012 BBX
                                                                        // BZ 20585 : Dans le cas du Mixed Kpi il faut adapter le message
                                                                        elseif ( $this->titre == 'Automatic Mapping' && MixedKpiModel::isMixedKpi($this->id_product) )
                                                                        {
                                                                                echo '<div class="errorMsg">';
                                                                                echo __T('A_AUTOMATIC_MAPPING_MK_NOT_AVAILABLE');
                                                                                echo '</div>';
                                                                        }
                                                                        else {
										echo "<tr><td class='texteRouge' align='center'>Error, no family in the database.</td></tr>";
									}
                                                                        
                                                                        // 11/10/2011 BBX
                                                                        // BZ 21116 : on cache l'icone de changement de produit si un seul produit
                                                                        if(count(ProductModel::getActiveProducts()) == 1)
                                                                        {
                                                                            echo '<style type="text/css">';
                                                                            echo '#change_family_choose_family {';
                                                                            echo '  display:none;';
                                                                            echo '}';
                                                                            echo '</style>';
                                                                        }
								?>
								</table>
							</td>
						</tr>
					</table>
					</fieldset>
					</td>
					</tr>
					</table>
				</td>
			</tr>
		<?
		}

		// Construit la structure de fin du tableau.
		function fin_tableau(){
		?>
			</table>
		<?
		}
	}
?>
