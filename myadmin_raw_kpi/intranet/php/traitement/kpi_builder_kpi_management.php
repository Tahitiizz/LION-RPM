<?
/*
*	@cb41000@
*
*	11/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	11/12/2008 BBX : modifications pour le CB 4.1 :
*	=> Utilisation des nouvelles méthodes et constantes
*	=> Utilisation de la classe de connexion àa la base de données
*	=> Gestion du produit
*
*	Suppression de l'inclusion de  adv_kpi_functions.php. BZ 10526
*
*	16/03/2010 NSE bz 14768 : suppression d'une requète de vérification d'existence et de suppresion des adv_kpi
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
*	09/08/2007 : Jérémy : Ajout d'une requête de suppression des ligne dans sys_data_range_style qui correspondent à un KPI qui vient d'être supprimé
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
/* MD : modification 09/06/2006 - ajout de la fonction get_graphs_with() pour connaitre le nombre de graphes dans lesquels un kpi est utilise et suppression du code ecrit par SL
 * MD : modification 20/07/2006 -  suppression des Adv. KPI lors de la suppression d un KPI - ligne 114 a 131
 */
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Librairies et classes requises
//include_once($repertoire_physique_niveau0 ."php/adv_kpi_functions.php");//pour acceder aux fonctions de suppression et de mise a jour d'un Adv. KPI

// Connexion à la base produit
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection($_GET['product']);

// Récuépration de la famille
$family=$_GET["family"];

/* Retourne le nombre de graphes dans lesquels le kpi est utilise
  * @param : id_kpi :  : l'identifiant d'un kpi
  * @return : int, le nombre de graphes dans lesquels le kpi est utilise
  */
function get_graphs_with($id_kpi){
	/*la requete suivante retourne la liste des adv_kpi utilises dans des graphes*/
	// Il faut se connecter au master
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$db = Database::getConnection();
	$query="SELECT id_page FROM sys_pauto_page_name WHERE id_page IN 
	(SELECT id_page FROM sys_pauto_config 
	WHERE class_object = 'kpi' AND id_product = '{$_GET['product']}' AND id_elem = '{$id_kpi}')";
	$db->execute($query);
	return $db->getNumRows();
}
/* Retourne le nombre d'alarmes dans lesquels le kpi est utilise
  * @param : id_kpi :  : l'identifiant d'un kpi
  * @return : int, le nombre d'alarme dans lesquels le kpi est utilise
  */
function get_alarms_with($id_kpi,$database){
	/*la requete suivante retourne la liste des adv_kpi utilises dans des graphes*/
	$query="
		SELECT alarm_id
		FROM sys_definition_alarm_static, sys_definition_kpi
		WHERE id_ligne = '".$id_kpi."' 
			AND (
				( alarm_trigger_data_field = kpi_name AND alarm_trigger_type = 'kpi' )
				OR
				( additional_field = kpi_name AND additional_field_type = 'kpi' )
			)
		UNION
		SELECT alarm_id
		FROM sys_definition_alarm_dynamic, sys_definition_kpi
		WHERE id_ligne = '".$id_kpi."'
			AND (
				( alarm_field = kpi_name AND alarm_field_type = 'kpi' )
				OR
				( alarm_trigger_data_field = kpi_name AND alarm_trigger_type = 'kpi' )
				OR
				( additional_field = kpi_name AND additional_field_type = 'kpi' )
			)
		UNION
		SELECT alarm_id
		FROM sys_definition_alarm_top_worst, sys_definition_kpi
		WHERE id_ligne = '".$id_kpi."'
			AND (
				( list_sort_field = kpi_name AND list_sort_field_type = 'kpi' )
				OR 
				( additional_field = kpi_name AND additional_field_type = 'kpi' )
			)";
	$res=$database->execute($query);
	return $database->getNumRows();
}

function kpiUsedInDataExport($id_ligne, $database)
{
	$query_de = "SELECT DISTINCT export_id
	FROM sys_export_raw_kpi_data
	WHERE raw_kpi_id = '".$id_ligne."'";
	$res_de = $database->execute($query_de);
	$result_nb_de = $database->getNumRows();

	if($result_nb_de > 0)
	{
		$inHidden = false;
		while($values = $database->getQueryResults($res_de,1)) {
			$DataExportModel = new DataExportModel($values['export_id']);
			if($DataExportModel->getConfig('visible') == '0') $inHidden = true;
		}
		if($inHidden) {
			return 2;
		}
		else {
			return 1;
		}
	}
	else
	{
		return false;
	}
}

switch ($action) //$action est un parametre passe par l'URL d'appel
       {
        CASE "create":    //crée un KPI
        $kpi_name=strtoupper($_POST["kpi_name"]);
        //teste si le nom du kpi existe deja
        $query_verif="SELECT kpi_name FROM sys_definition_kpi WHERE kpi_name='$kpi_name'";
        $database->execute($query_verif);
        $nombre_kpi_identique=$database->getNumRows();
        if ($nombre_kpi_identique==0) //il n'existe pas de doublon
           {
            $date_du_jour=edw_day_format(0,"day");
            if ($edw_group_table!="mixed")
               {
                $query="INSERT INTO sys_definition_kpi (edw_group_table,kpi_name, new_field,numerator_denominator) VALUES ('$edw_group_table','$kpi_name','0','total')";
               }
               else
               {
                $query="INSERT INTO sys_definition_kpi (kpi_name,new_field,numerator_denominator) VALUES ('$kpi_name','0','mixed')";
               }
            $database->execute($query);
          }
          else //il existe un doublon
          {?>
           <script>
              alert('the name of the KPI already exist.\nYour kpi needs to have another name in order to be created');
           </script>
        <?}
        ?>
        <script>
         window.close();
         window.opener.location="<?=$traitement_vers_affichage?>intra_myadmin_generic_counters_table.php?family=<?=$family?>&edw_group_table=<?=$edw_group_table?>";
         </script>
        <?
        break;


		CASE "delete":    // supprime un KPI

			$id_kpi				= $_GET["id_kpi"];
			$edw_group_table	= $_GET["edw_group_table"];

			$nb_graphs=get_graphs_with($id_kpi);//contient le nombre de graphes contenant id_kpi
			$nb_alarms=get_alarms_with($id_kpi,$database);//contient le nombre d'alarmes contenant id_kpi
                        $kpiManagement = new KpiModel();
                        $kpiName = $kpiManagement->getNameFromId($id_kpi, $database);
                        // 09:47 18/10/2010 SCT : BZ 18518 => Vérification de la formule KPI avant activation depuis KPI_Builder
                        $isKpiUsedAsBh = KpiModel::isKpiLockedBh($_GET['product'], $kpiName, $family);
			if ($nb_graphs>0) { // le kpi est utilisé dans un graphe
				?><script>
					alert('<?php echo __T('A_KPI_BUILDER_CANNOT_DELETE_USED_IN_GRAPH', $nb_graphs); ?>');
					history.go(-1);
				</script><?
			}
			elseif ($nb_alarms>0) { // le kpi est utilisé dans une alarme
				?><script>
					alert('<?php echo __T('A_KPI_BUILDER_CANNOT_DELETE_USED_IN_ALARM', $nb_alarms); ?>');
					history.go(-1);
				</script><?
			}
                        elseif($isKpiUsedAsBh)
                        {
				?><script>
					alert('<?php echo __T('A_SETUP_KPI_BUILDER_ERROR_BH_KPI_DEL', $kpiName); ?>');
					history.go(-1);
				</script><?
                        }
			// 14/08/2009 BBX : Test KPI dans Data Export
			elseif($result = kpiUsedInDataExport($id_kpi, $database))
			{
				?><script>
				alert('<?php echo ($result === 1) ? "This KPI is used in a Data Export. It cannot be deleted" : "This KPI is used in an automatic Data Export (hidden). It cannot be deleted"; ?>');
				history.go(-1);
				</script><?
			}
			else {
				//recherche si la valeur new_field est à 1. Ce qui signifie que les scripts d'agregation ne l'ont pas encore pris en compte
				//si new_field vaut 1 alors on delete le KPI sinon on met new field à 2 et le KPI sera effacé lors de l' aggregation
				$query="SELECT new_field,kpi_name FROM sys_definition_kpi WHERE (id_ligne='$id_kpi')";
				$row=$database->getRow($query);
				$new_field=$row["new_field"];
				$kpi_name = $row["kpi_name"];

				if ($new_field==1){
					$query="DELETE FROM sys_definition_kpi WHERE id_ligne in (
						select id_ligne from sys_definition_kpi
							where kpi_name='$kpi_name'
							and edw_group_table in (
								select edw_group_table from sys_definition_group_table_ref
									where family='$family'
							)
						)
					";
					$database->execute($query);

				} else {

					// 16/03/2010 NSE bz 14768 suppression d'une requète de vérification d'existence et de suppresion des adv_kpi

					//Au lieu de supprimer, on mets la valeur new_field à 2 et c'est le script daily qui vient effacer les données
					if(isset($edw_group_table) && $edw_group_table=='mixed') {
						$query="UPDATE sys_definition_kpi SET new_field=2,edw_group_table='edw_alcatel_0' WHERE (id_ligne='$id_kpi')";
					} else {
						$query="UPDATE sys_definition_kpi SET new_field=2 WHERE (id_ligne='$id_kpi')";
					}
					$database->execute($query);
				}
				
				//09/08/2007 : Jérémy : On supprime les lignes de la table data range qui correspondent à ce KPI (correction BUG 687)
				//		La suppression se fait à l'exterieur de la boucle qui vérifie la valeur de new field, puisque de toute manière le KPI sera supprimé
				$query = "
					DELETE FROM sys_data_range_style 
					WHERE id_element = '$id_kpi'";
				$database->execute($query);
				
				//include_once($repertoire_physique_niveau0 ."scripts/edw_clean_structure_on_the_fly.php");
				?>
				<script>
				//if(confirm('continuer')){
				window.location="<?=NIVEAU_0?>myadmin_raw_kpi/intranet/php/affichage/kpi_builder_interface.php?family=<?=$family?>&product=<?=$_GET['product']?>";
				parent.kpi_list.location="<?=NIVEAU_0?>myadmin_raw_kpi/intranet/php/affichage/kpi_builder_kpi_list.php?family=<?=$family?>&edw_group_table=<?=$edw_group_table?>&product=<?=$_GET['product']?>";
				//}
				</script>
				<?
			}
        break;

        CASE "save":    //mets un jours à KPI

        $quotient=0;
        $group_table_name=$_POST["group_table_name"];
        if ($group_table_name!="mixed")
           {
        //teste si on est dans le cas d'un quotient
        if (trim($zone_formule_denominateur)!="")
           {
            $quotient=1;
            //insère le kpi complet
            $query="UPDATE sys_definition_kpi SET new_field=1, kpi_type='float4', kpi_formula='($zone_formule_numerateur)/($zone_formule_denominateur)', on_off=1, quotient=$quotient, numerator_denominator='total'  WHERE (id_ligne='$zone_id_generic_counter')";
            pg_query($database_connection,$query);

            //vérifie s'il existe déjà des lignes numérateur et dénominateur pour le KPI
            $query="SELECT id_ligne, numerator_denominator FROM sys_definition_kpi WHERE id_ligne_parent='$zone_id_generic_counter'";
            $resultat_recherche=pg_query($database_connection,$query);
            $nombre_resultat=pg_num_rows($resultat_recherche);
            if ($nombre_resultat>0)
               {
                //il faut mettre à jour les valeurs numérateur et dénominateur
                //met à jour le numérateur
                $query="UPDATE sys_definition_kpi SET kpi_formula='($zone_formule_numerateur)' WHERE (id_ligne_parent='$zone_id_generic_counter' and numerator_denominator='num')";
                pg_query($database_connection,$query);
                //met à jour le dénominateur
                $query="UPDATE sys_definition_kpi SET kpi_formula='($zone_formule_denominateur)'  WHERE (id_ligne_parent='$zone_id_generic_counter' and numerator_denominator='den')";
                pg_query($database_connection,$query);
               }
               else
               {
                //il faut créer les partie numérateur et dénominateur
                //insére la partie numérateur
                $nom_numerateur=$generic_counter.'_N';
                $query="INSERT INTO sys_definition_kpi (edw_group_table, kpi_name, kpi_type, kpi_formula, on_off, id_ligne_parent, numerator_denominator) VALUES ('$group_table_name','$nom_numerateur', 'float4', '($zone_formule_numerateur)', 1, $zone_id_generic_counter, 'num')";
                pg_query($database_connection,$query);
                //insère la partie dénominateur
                $nom_denominateur=$generic_counter.'_D';
                $query="INSERT INTO sys_definition_kpi (edw_group_table, kpi_name, kpi_type, kpi_formula, on_off, id_ligne_parent, numerator_denominator) VALUES ('$group_table_name','$nom_denominateur', 'float4', '($zone_formule_denominateur)', 1, $zone_id_generic_counter, 'den')";
                pg_query($database_connection,$query);
               }
           }
           else
           {
            //si l'équation n'a pas de quotient alors on met à jour la formule globale
            $query="UPDATE sys_definition_kpi SET new_field=1,kpi_type='float4', kpi_formula='$zone_formule_numerateur', on_off='1', quotient=$quotient, numerator_denominator='total'  WHERE (id_ligne='$zone_id_generic_counter')";
            pg_query($database_connection,$query);
            //et on efface les parties numérateur et dénominateur (même si elles n'existaient pas)
            $query="DELETE FROM sys_definition_kpi WHERE (id_ligne_parent='$zone_id_generic_counter')";
            pg_query($database_connection,$query);
           }
        }
        else //on est dans le cas d'un KPI mixte
        {
         $combine=$zone_formule_numerateur;
         $liste_combine=split('[(),]',$combine); //separe les () et , du reste
         $kpi1=$liste_combine[1]; //la premiere valeur 0 contient COMBINE
         $kpi2=$liste_combine[2];
         $query="SELECT kpi_formula, edw_group_table FROM sys_definition_kpi WHERE kpi_name='$kpi1' or kpi_name='$kpi2'";
         $resultat=pg_query($database_connection,$query);
         $nombre_resultat=pg_num_rows($resultat);
         for ($i=0;$i<$nombre_resultat;$i++)
             {
              $row=pg_fetch_array($resultat,$i);
              $formule_combine.="(".$row["kpi_formula"].")+";
              $edw_group_table_mixed=$row["edw_group_table"];  //prend un groupe table peut importe lequel afin d'etre utilise dans les agregations mixtes
             }
         $formule_combine=substr($formule_combine,0,-1);
         $query="UPDATE sys_definition_kpi SET new_field=1,kpi_type='float4', kpi_formula='$formule_combine',kpi_formula_combined='$combine',edw_group_table='$edw_group_table_mixed', on_off='1' WHERE (id_ligne='$zone_id_generic_counter')";
         pg_query($database_connection,$query);
        }

        ?>
        <script>
         window.location="<?=$traitement_vers_affichage?>intra_myadmin_generic_counters_builder.php?id_generic_counter=<?=$zone_id_generic_counter?>&generic_counter_name=<?=$generic_counter?>&generic_counter_numerateur=<?=$zone_formule_numerateur?>&generic_counter_denominateur=<?=$zone_formule_denominateur?>&edw_group_table=<?=$group_table_name?>";
         parent.kpi_list.location="<?=$traitement_vers_affichage?>intra_myadmin_generic_counters_table.php?edw_group_table=<?=$group_table_name?>";
        </script>
        <?
        break;
       }
?>
