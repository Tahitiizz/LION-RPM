<?php
/**
 *	@cb4100@
 *	- MaJ SLC	 29/10/2008
 *
 * Ce morceau de page liste les éléments disponibles (menu du haut Graph ou Dashboards ou Report)
 *
 * 29/01/2009 GHX : modification des requêtes SQL pour mettre id_user & id_page entre cote au niveau des inserts  [REFONTE CONTEXTE]
 * 30/01/2009 GHX : modification des requêtes SQL pour mettre id_page entre cote au niveau des inserts  [REFONTE CONTEXTE]
 * 20/01/2011 OJT : Correction bz 20214, ajout du label produit afin de différencier les graphs, dashboards et reports
 */

/**
 * Permet de retourner la liste déroulante de l'interface object builder,
 * liste tous les éléments (graph ou dash ou reports)
 *
* @access public
* @param string $page_type = 'graph' || 'page' || 'report'
* @return html select tag
*/
function current_list($page_type)
{
	global $user_info,$db;
	
    // 20/12/2010 BBX : BZ 18510 Récupération des produits inactifs
    $inactiveProducts = ProductModel::getInactiveProducts();

	// 30/01/2009 GHX : Suppression du formatage en INT
	$this_id_page = $_GET['id_page'];
	
	$optgroup_label_type   = '';// Label du type d'élément affiché daans la balise optgroup du select.
	$add_and               = ""; // clause 'AND' à ajouter à la requête si besoin.
	$add_table             = ""; // ajout de tables à la requête si besoin.
	$add_select            = ""; // clause 'SELECT' à ajouter à la requête si besoin.
	$column_name_for_label = 'T1.page_name'; // Nom de la colonne dans laquelle on récupère le label à afficher dans les balises option

	// L'indice correcpond à la colonne optgroup_order récupérer dans la requête $query, on fait correspondre à cet indice le label à afficher.
	$optgroup_label_array    = array();
	$optgroup_label_array[1] = get_sys_global_parameters('publisher');
	$optgroup_label_array[2] = __T('G_PAUTO_ADMINISTRATOR');
	$optgroup_label_array[3] = __T('G_PAUTO_USERS');
	$optgroup_label_array[4] = __T('G_PAUTO_YOUR');
	
    // Initialisation en fonction du type de page
    // 20/01/2011 Refactorisation du switch préalablement splitter
	switch ( $page_type )
	{
		case 'graph' :
			$optgroup_label_type = __T( 'G_PAUTO_GTM' );
            $select_title        = __T( 'G_GDR_BUILDER_CHOOSE_A_GTM_OR_CREATE_A_NEW_ONE' );
			$script              = 'graph';
			break;
		case 'page' :
			$optgroup_label_type = __T( 'G_PAUTO_DAHSBOARD' );
            $select_title        = "Choose a dashboard or create a new one:";
			$script              = 'dashboard';
			break;
		case 'report' :
			$optgroup_label_type = __T( 'G_PAUTO_REPORT' );
			$select_title        = "Choose a report or create a new one:";
			$script              = 'report';
			break;
		default :
			$optgroup_label_type = __T( 'G_GDR_BUILDER_UNDEFINED_TYPE' );
            break;
	}
	
	// Si c'est un client qui est connecté et que le profil est administrateur, on n'affiche pas toutes la liste des éléments.
	// maj 13/05/08 christophe, correction du bug BZ6263 : même un admin client doit pouvoir afficher les dash/graph astellia mais sans pouvoir les modifier ni les copier (mise en commentaire du code en cas de retour en arrière).
	//if ($this->droitCurrentUser == "client" && $this->userParam['profile_type'] != 'user')
		//$add_and .= " AND droit='$this->droitCurrentUser'";
	
	/*	
		Liste des noms des éléments.
		Le champ optgroup_order permet d'ordoner les éléments par groupe.
		Le champ nb_elem_in correspond au nombre d'éléments contenus dans chaque dash/rapport/graph
	*/
	// maj 05/02/2008 christophe : quand il y a des éléments créés par les utilisateurs, on affiche leurs noms dans le select > maj query. 
	$db_page_type = $page_type;
	if ($page_type=='graph') $db_page_type = 'gtm';

	$query=" --- get list of objects
		SELECT T1.id_page, 
				T1.page_name
				|| CASE WHEN T1.id_user IS NOT NULL AND T1.id_user <> '{$user_info['id_user']}' THEN 
						(SELECT ' ['||username||']' FROM users WHERE id_user=T1.id_user) 
						ELSE '' 
					END
				AS page_label, T1.share_it,
				T1.droit,T1.id_user,COUNT(T3.id_elem) AS nb_elem_in,
				CASE WHEN droit='customisateur' THEN 1
					ELSE CASE WHEN droit='client' AND id_user IS NULL THEN 2
					ELSE CASE WHEN droit='client' AND id_user IS NOT NULL AND id_user <> '{$user_info['id_user']}' THEN 3
					ELSE CASE WHEN droit='client' AND id_user IS NOT NULL AND id_user = '{$user_info['id_user']}' THEN 4
				END END END END AS optgroup_order
				FROM sys_pauto_page_name T1 LEFT JOIN sys_pauto_config T3 ON T1.id_page = T3.id_page
				WHERE 
					T1.page_type='$db_page_type' 
			GROUP BY T1.id_page,page_label,droit,id_user,T1.share_it $add_select
			ORDER BY optgroup_order,page_label
	";
	$current_elements = $db->getall($query);
	?>
	<div>
		<form method="get" style="padding:0 0 0 0; margin:0"
			action="<?php echo $script ?>.php" 
			id="formulaire_pauto">

			<select style="width:580px;margin-bottom:2px;" name="id_page" id="main_select" onChange="document.getElementById('formulaire_pauto').submit()">
				<option value="0"><?php echo $select_title; ?></option>
			<?
				$optgroup_order_prec = 0; // numéro de l'optgroup_order précédent.

                // Lecture des informations produits avant affichage des Graph
                $productsInfo = ProductModel::getProducts();

                // 21/11/2011 BBX
                // BZ 24764 : correction des messages "Notice" PHP
                if(!isset($this_affichage)) $this_affichage = '';
                if(!isset($is_configure)) $is_configure = false;
                if(!$this_page_gtm_client) $this_page_gtm_client = false;

				foreach ($current_elements as $element)
				{
                    $productIds    = array();
                    $gtmProductRef = ""; // Par defaut la réference au produit n'est pas définie
                    $selected      = "";
					$display_it    = true;
                    $elem_is_empty = ""; // permet d'afficher le label '(empty)' si l'élément est vide.
			
					// On n'affiche pas les éléments des utilisateurs qui ne l'ont pas partagé.
					if ( $element['optgroup_order']== 3 && $element['share_it'] != 1 ) $display_it = false;
			
					// Affichage de la balise optgroup.
					if ( $optgroup_order_prec != 0 && $optgroup_order_prec != $element['optgroup_order'] )
						echo "</optgroup>";
					if ($optgroup_order_prec != $element['optgroup_order'])
						echo "<optgroup label='".$optgroup_label_array[$element['optgroup_order']]." ".$optgroup_label_type."'>";

                    // 29/03/2011 NSE Merge 5.0.5 -> 5.1.1 : gestion des elses pour initialisation variables $selected et $elem_is_empty
					// Si c'est la page courrante on affiche le selected.
					if($element["id_page"] == $this_id_page)
					{
						$selected = "selected=\"selected\"";
						if ( isset($element['is_configure']) ) // Pour les GTMs
							$is_configure = $element['is_configure'];
					}
                    else
                        $selected = '';
					
					// permet d'afficher le label '(empty)' si l'élément est vide.
					if ( $element['nb_elem_in'] == 0 ) 
                        $elem_is_empty = "(empty)";
                    else
                        $elem_is_empty = '';
			
					if ( $display_it )
					{
                        /*
                         *  20/12/2010 BBX : bz18510, récupération des produits liés à l'élément courant
                         *  18/01/2011 OJT : Utilisation du model GTMModel
                         */
                        switch ( $page_type )
                        {
                            case 'graph' : // Dans le cas d'affichage de graphs
                                $gtmModel = new GTMModel( $element["id_page"] ); // Creation de l'object GTMObject
                                $productIds = $gtmModel->getGTMProducts();
                                break;

                            case 'page' : // Dans le cas d'affichage de dashboards
                                $productIds = DashboardModel::getDashboardProducts( $element["id_page"] );
                                break;

                            case 'report' : // Dans le cas d'affichage de rapports
                                $productIds = ReportModel::getReportProducts( $element["id_page"] );
                                break;
					}
			
                        // Comparaison des produits liés avec les produits désactivés
                        $readOnly = false;
                        foreach( $productIds as $id )
                        {
                            foreach( $inactiveProducts as $p )
                            {
                                if( $id == $p['sdp_id'] )
                                {
                                    $readOnly = $p['sdp_label'];
                                }
                            }
                        }

                        // Affichage du produit associé (si un seul produit concerné)
                        if( ( count( $productIds ) === 1 ) &&  ( count( $productsInfo ) > 1 ) )
                        {
                            // On cherche son label dans la liste de $productsInfo
                            $i = 0;
                            while( $i < count( $productsInfo ) && $productsInfo[$i]['sdp_id'] != $productIds[0] ) $i++;
                            if( $i < count( $productsInfo ) ){
                                $gtmProductRef = "(".$productsInfo[$i]['sdp_label'].")";
                            }
                        }
                        else if ( ( count( $productIds ) > 0 ) && ( count( $productsInfo ) > 1 ) )
                        {
                            $gtmProductRef = "(multi product)";
                        }

                        $displayValue = $element['page_label']." ".$gtmProductRef." ".$elem_is_empty;

                        // Les éléments liés à des produits désactivés ne plus pas sélectionnables
                        if( $readOnly )
                        {
                            $displayValue = $element['page_label']." ".$gtmProductRef." (".__T('A_SETUP_MIXED_KPI_FAMILY_LOCKED',$readOnly).")";
                            echo "\n<option title='{$displayValue}' value='0' style='font-style:italic;color:red'>
                                        {$displayValue}
                                    </option>\n";
                        }
                        else
                        {
                            echo "\n<option title='{$displayValue}' value='".$element['id_page']."' $selected>
                                        {$displayValue}
                                    </option>\n";
                        }
                        // Fin BZ 18510
					}

					$optgroup_order_prec = $element['optgroup_order'];
				}
		
				if($this_affichage == "new"){
					echo "<option value='' selected='selected' style='background-color:#C0C0C0'>".__T('G_GDR_BUILDER_EXISTING_OPTGROUP',$optgroup_label_type)."</option>";
				}
			?>
		</select>
		
			<?
			///////////////////////////////////////////////////////////////////////////////////
			// Affichage du bouton de prévisualisation des options du graph ////////////////////////////
			///////////////////////////////////////////////////////////////////////////////////
			if ( $page_type=="graph" && $this_affichage != "new")
			{
				$icone = "detail_rouge.gif";
				$msg_alt = __T('G_GDR_BUILDER_GRAPH_PROPERTIES');
				if ( $is_configure ){
					$icone = "detail_vert.gif";
					$msg_alt = __T('G_GDR_BUILDER_GRAPH_PROPERTIES');
				}

				// Si on est en mode client et que la page courante a été faites par un customisateur, on n'affiche pas les détails.
				$bloquer_gtm_config = false;
				if($this_page_gtm_client){
					if($result_array["droit"] == "customisateur") $bloquer_gtm_config = true;
				}

				
			}
			$this_dashboard_isonline = false;
			if ($page_type=="page"){
				// On vérifie si le dashboard est online.
				$query = " --- is the dashboard online ? 
					select sdd_is_online from sys_definition_dashboard where sdd_id_page = '$this_id_page'";
				if ($db->getone($query)==1)
					$this_dashboard_isonline = true;
			}


			// On affiche un message d'alerte quand le graph est un pie.
			if($page_type == "graph")
			{
				$query = " --- on cherche si le graph est un PIE
					select object_type from graph_information where id_page='$this_id_page'";
				$object_type = $db->getone($query);
				if ($object_type) {
					$temp = explode("@", $object_type);
					if($temp[0] == "pie") { ?>
						<div class="texteRouge">
							<img src="../images/icones/information.png">
							<!-- maj 10/06/08 christophe : mise en BDD du warning sur le split des Pie  -->
							<?=__T('G_PAUTO_GTM_PIE_CHART_WARNING');?>
						</div>
						<?
					}
				}
			}
		?>
	</form>
	</div>
	<?
}

?>
