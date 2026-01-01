<?
/*
*	Ce script effectue le déploiement du parser sur le cb et effectue la purge les données en fonction des paramètres d'historique de données
* 
*       13/10/2011 BBX BZ 20636 : merge des correction 5.1.4 sur les tests des colonnes (commentaires PARTITIONING) + ajouts de tests supplémentaires
*       04/06/2012 MMT Bz 27408 :27408 ajout de droit sur la table créée 
*/
?>
<?
/*
*	@cb41000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
*	- maj 30/10/2008 - MPR : On ne génère plus les combinaisons na_na3A dans les tables de données
*	 - maj 11:50 30/10/2008 - MPR : On ajoute uniquement le niveau d'agrégation réseau étant donné que le na 3ème axe existe déjà
*	- maj 20/11/2008 - MPR : On supprime les éléments ds les table edw_object_ref, edw_object_ref_parameters et edw_object_arc_ref pour le clean history
*
*	- 31/03/2009 BBX : on ne supprime plus les éléments sur edw_object_ref, edw_object_ref_parameters & edw_object_arc_ref
*	- maj 15/07/2009 - MPR : Correction du bug 10625 - Ajout du produit dans l'appel des fonctions getFamilyFromIdGroup, getNaLabelList et get_axe3
*	- maj 28/07/2009  MPR : Correction du bug 10865 - Les tables hour ne sont pas générées - présence de colonne sans nom
*
*	- maj 06/08/2009 - MPR : Correction du bug 10945 : Tous les niveaux d'agrégation 3ème axe
*	- maj 11/08/2009 - MPR : Correction du bug 10943 Décomment de l'appel de la fonction create_indexes()
*	- CCT1 28/08/09 : mise en commentaire de la création d'index sur les tables edw_object_x_ref qui n'existent plus.
*	- CCT1 28/08/2009 :  on n'affiche pas les erreurs retournées par pg_query si debug_global=0
*
*	02/09/2009 GHX
*		- Correction du BZ 11338 [REC][T&A CB5.0][ACTIVATION CONTEXTE] Loading bloqué
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
	- maj 13/05/2008, benoit : correction du bug 6254. Ajout des colonnes longitude et latitude à la table 'edw_object_1_ref'
*/
?>
<?
/*
*	@cb22012@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.12
*/
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
*	@cb21000@
*
*	08/12/2006 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.00
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
*	@cb1300p_gb100b_060706@
*
*	06/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0p
*
*	Parser version gb_1.0.0b
*/
?>
<?php
/**
 *
 * @package deploy
 */

/**
 * Utilisation :
 * Pour déployer un group table en partant de zéro :
 * insérer une ligne pour ce group table dans sys_definition_group_table. En fonction de ce que l'on veut déployer, mettre les colonnes "data_type"_deploy_status à 1. Lorsque le déploiement est lancé, toutes les tables pour chaque group_table seront créées avec les TA et les NA présents dans sys_definition_group_table_(time|network) pour lesquels deploy_status vaut 0.
 * Pour ajouter un TA :
 * insérer une ligne pour ce TA dans sys_definition_group_table_time et positionner deploy_status à 1. mettre ensuite à jour sys_definition_group_table en positionnant "data_type"_deploy_status à 3. Le déploiement créera alors les tables group_table_data_type_NA_TA pour le TA ajouté et tous les NA présents où deploy_status vaut 0.
 * Pour ajouter un NA :
 * insérer une ligne pour ce NA dans sys_definition_group_table_network et positionner deploy_status à 1. mettre ensuite à jour sys_definition_group_table en positionnant "data_type"_deploy_status à 3. Le déploiement créera alors les tables group_table_data_type_NA_TA pour le NA ajouté et tous les TA présents où deploy_status vaut 0. Une colonne portant le nom de ce NA sera ajoutée dans la table de base (la table où les NA et TA sont les plus bas).
 *
 * 02/11/2005 : amélioration de la gestion des index et creation des labels dans la table object_ref lorsque l'on part d'une base vide
   23/11/2005 : pour le clean tables : modification afin de gérer l'effacement des BH
   03/07/2006: clf corrige le bug lors du deploement du compsoant de base ligne 272
   06/07/2006 : MD, modifications pour le déploiement des RAWs et KPIs désactivés lors de l'ajout d'un nouveau NA
		ligne 1030 dans la fonction get_fields_raw() - Modification de la requête de sélection des RAWs pour le déploiement (suppression de la condition on_off='1')
		ligne 1046 dans la fonction get_fields_kpi() - Modification de la requête de sélection des KPIs pour le déploiement (suppression de la condition on_off='1')
   - maj 20 07 2006 : ADV KPI, deploiement des tables (MD)
      >> ajout de la fonction get_fields_adv_kpi()
      >> modif de la fonction get_query_create
      >> modif de la fonction init()
  - maj 11 09 2006 : Alarmes (MD)
	>> modif de la fonction update_object_ref() : ajout d'une colonne a la table edw_object_ref (blacklisted)
   - maj 16 08 2006 : Busy Hour (MD)
      >> ajout de la fonction getTimeFieldValue() : retourne la valeur d'une colonne de la table sys_definition_time_agregation
      >> modif de la fonction get_query_create() : une colonne nommee 'bh' a ete ajoutee au table de type 'Busy Hour' pour permettre de retrouver l'heure la plus chargee
   - maj 02 10 2006 : MD - modification concernant le deploiement des NA
	>> ligne 340 : on ajoute plus les colonnes sur la table principale lorsque l'on deploie une NA pour une autre famille
	>> ligne 357 : idem pour la suppression
  - maj 07 05 2007 : MP - Création des indexes pour les familles normales et 3ème axe
			   MP - Création d'un indexe lorsque l'on ajoute  une na via l'application (Setup network aggregation / Setup Family)
*  */

class deploy {
    /**
     * tableau associatif de data_types à gérer (peut contenir kpi, mixed_kpi, advanced_kpi, raw)
     * "group_table"=>("data_types" => "type d'opération (1->deploiement, 2->supp, 3->update)")
     *
     * @var array
     */
    var $types;
    /**
     * L'identifiant de connection à la base
     *
     * @var string
     */
    var $database_connection;
    /**
     * Tableau de tables à créer
     *
     * @var array
     */
    var $tables_to_create = array();
    /**
     * Tableau de tables à modifier
     *
     * @var array
     */
    var $tables_to_update;
    /**
     * Tableau de tables à supprimer
     *
     * @var array
     */
    var $tables_to_drop = array();
    /**
     * Tableau associatif group => tableau de requêtes à effectuer pour ce groupe
     *
     * @var array
     */
    var $requetes;
    /**
     * L'identifiant du groupe à traiter
     */
    var $group_id;
    /**
     * Tableau associatif data_type => nouveaux networks ajoutés (dans le cas d'un update)
     */
    var $nets_to_add;
    /**
     * Tableau associatif data_type => nouveaux times ajoutés (dans le cas d'un update)
     */
    var $times_to_add;
    /**
     * Prend une instance de databaseConnection
     */
    var $database = null;

    /**
     * le constructeur de la classe
     *
     * @param string $database_connection
     */
    function deploy($database_connection, $group_id, $product = "")
    {
        $this->product = $product;
        // maj 15/07/2009 - MPR : Correction du bug 10625 - Ajout du produit dans l'appel de la fonction getFamilyFromIdGroup
        $this->family = getFamilyFromIdGroup($group_id, $product);

        // 19/05/2011 BBX - PARTITIONING -
        // Permet à la classe de travailler avec une instance de DatabaseConnection
        // Tout en gardant la compatibilité avec les requêtes qu'elle contient.
        $this->database_connection = $database_connection;
        if(is_object($database_connection)) {
            $this->database = $database_connection;
            $this->database_connection = $database_connection->getCnx();
        }

        $this->group_id = $group_id;
        $this->min_net = $this->get_min_network_level($group_id, "raw");
        $this->min_time = $this->get_min_time_level($group_id, "raw");
        // echo "min net = ".$this->min_net." min time =".$this->min_time."<br>";
        $this->done = 0;
        $this->init();
    }

    /**
     * initialise la variable $types de l'objet deploy
     */
    function init()
    {
        $query = "select edw_group_table, raw_deploy_status,
                        kpi_deploy_status, adv_kpi_deploy_status,
                        mixed_kpi_deploy_status
                                from sys_definition_group_table
                                where (raw_deploy_status<>'0'
                                                or adv_kpi_deploy_status<>'0'
                                                or kpi_deploy_status<>'0'
                                                or  mixed_kpi_deploy_status<>'0')
                                and id_ligne='$this->group_id'";
        $res = pg_query($this->database_connection, $query);
        while ($row = pg_fetch_array($res)) {
            unset($t1);
            for($i = 0;$i < pg_num_fields($res);$i++) {
                $field = pg_fieldname($res, $i);
                if ($row[$i] != '0' && $i > 0)
                    $t1[$field] = $row[$i];
            }
            $todo[$row[0]] = $t1;
        }
        $this->types = $todo;
    }

    /**
     * construction des tables sur lesquelles on doit effectuer des requêtes
     */


    function operate()
    {
        // 13/10/2011 BBX
        // BZ 20636 : Compatibilité avec l'ancien fonctionnement
        $db = $this->database;
        if(!is_object($this->database)) {
            $db = Database::getConnection($this->product);
        }
        
        foreach($this->types as $group => $ope)
        {
            foreach ($ope as $data_type => $todo)
            {
                unset($tables_crea, $tables_sup, $tables_mod);
                $tables_crea1 = array();
                $tables_crea2 = array();
                $tables_sup = array();
                $data_type = preg_replace("/_deploy_status/", "", $data_type);
                $group_id = $this->get_group_id($group);
                $this->op = $todo;
                switch ($todo)
                {
                    case "1":
                        // echo "déploiement de $group_id avec le type $data_type<br>";
                        $times = $this->select_time_fields($group_id, $data_type, "0");
						$nets = $this->select_net_fields($group_id, $data_type, "0");
						// On retient uniquement les na présents dans la table sys_definition_group_table_network
						$tables_crea = $this->get_tables($group,$this->nets_axe3, $times, $data_type);
						break;

                    case "2":
                        // echo "suppression de $group_id avec le type $data_type<br>";
                        $times = $this->select_time_fields($group_id, $data_type, "-1");
                        $nets = $this->select_net_fields($group_id, $data_type, "-1");
						// maj 15/07/2009 - MPR : Correction du bug 10625 - Ajout du produit dans l'appel de la fonction get_axe3
						if(get_axe3($this->family, $this->product))
							$tables_sup = $this->get_tables($group, $this->nets_axe3, $times, $data_type);
						else
							$tables_sup = $this->get_tables($group, $nets, $times, $data_type);
						break;

                    case "3":
                        // niveaux de tps et de networks déjà déployés
						unset($nets_exist);
						$times_exist = $this->select_time_fields($group_id, $data_type, "-1");
                        $nets_exist = $this->select_net_fields($group_id, $data_type, "-1");

                        // niveaux de tps et de networks à supprimer (si ils existent)
                        $del_times = $this->select_time_fields($group_id, $data_type, "2");
                        if (count($del_times) > 0) {
                            $tables_sup = $this->get_tables($group, $nets_exist, $del_times, $data_type);
                            for($d = 0;$d < count($del_times);$d++)
                            $this->del_sys_line($group, $data_type, $del_times[$d], "time");
                        }
                        $del_nets = $this->select_net_fields($group_id, $data_type, "2");
					   if (count($del_nets) > 0) {
                            $tables_sup1 = $this->get_tables($group, $this->nets_axe3, $times_exist, $data_type);
							$tables_sup = array_merge($tables_sup, $tables_sup1);
                            for($d = 0;$d < count($del_nets);$d++) {
                                $this->del_sys_line($group, $data_type, $del_nets[$d], "network");
                                if ($data_type == "raw")
                                    $this->get_query_alter($group, $data_type, $del_nets[$d], "del_net");
                            }
                        }
						// niveaux de temps et de networks à ajouter (si ils existent)
                        $new_times = $this->select_time_fields($group_id, $data_type, "1");
                        $nets = $this->select_net_fields($group_id, $data_type, "1");
                        if(count($nets)>0 and is_array($nets_exist))
							$new_nets = array_diff($this->nets_axe3, $nets_exist);
						else
							$new_nets = $this->nets_axe3;
                        $times = array_merge($times_exist, $new_times);

                        // ajout d'un niveau de temps -> création des tables
                        if (count($new_times) > 0) {
							$nets = array_merge($this->nets_axe3,$nets_exist);
                            $this->times_to_add[$data_type] = $new_times;
                            $tables_crea1 = $this->get_tables($group, $nets, $new_times, $data_type);
                            foreach($new_times as $new_time) {
                                $this->update_sys_line($group, $data_type, $new_time, "time");
                                $this->get_query_alter($group,$data_type,$new_time,"time");
                            }
                        }
                        // ajout d'un niveau de net -> création des tables
                        // et ajout du network dans la table de base et dans les tables edw_object
                        if (count($new_nets) > 0) {
                            $this->nets_to_add[$data_type] = $new_nets;
                            // echo "<br>net to add<br>";
                            $tables_crea2 = $this->get_tables($group, $this->nets_axe3, $times, $data_type);

                            foreach($new_nets as $new_net) {
                                $this->update_sys_line($group, $data_type, $new_net, "network");
                                if ($data_type == "raw")
                                    $this->get_query_alter($group, $data_type, $new_net, "new_net");
                            }
                        }
                        $tables_crea = array_merge($tables_crea1, $tables_crea2);
                        break;
                }
                $this->tables_to_create = array_merge($this->tables_to_create, $tables_crea);
                // $this->tables_to_update=array_merge($this->tables_to_update,$tables_mod);
                $this->tables_to_drop = array_merge($this->tables_to_drop, $tables_sup);
                for($t = 0;$t < count($tables_crea);$t++) {                    
                    // 13/10/2011 BBX
                    // BZ 20636 : ajout d'un test sur l'existence de la table
                    $tableName = str_replace('/', '_', $tables_crea[$t]);
                    if(!$db->doesTableExist($tableName))
                        $this->requetes[$group][] = $this->get_query_create($tables_crea[$t]);
                }

                for($v = 0;$v < count($tables_sup);$v++) {
                    // echo $this->get_query_drop($group,$tables_sup[$v])."<br>";
                    $this->requetes[$group][] = $this->get_query_drop($group, $tables_sup[$v]);
                }
            }
            // mise à jour
            $this->update_sys_tables($group, $todo);

            // maj 11/08/2009 - MPR : Correction du bug 10943 Décomment de l'appel de la fonction create_indexes()
            // 24/10/2011 BBX
            // BZ 24386 : création des index uniquement si base non partitionnée
            if(!$db->isPartitioned()) $this->create_indexes();
        }
        
        // 19/12/2011 BBX
        // BZ 25190 : Nettoyage des colonnes obsolètes
        $this->cleanObsoleteNetworkColumns();
    }

    /**
     * 19/12/2011 BBX
     * BZ 25190
     * Vérifie les tables de données et supprime les colonnes de NA qui ne sont plus utilisées
     * Ce cas apparait lorsque l'on change le niveau min dans un Corporate.
     * Les colonnes de tous les NA sont toujours ajoutées dans la table de niveau min de chaque famille.
     * Si l'on change le niveau min, il faut nettoyer les tables du niveau min précédent.
     */
    function cleanObsoleteNetworkColumns()
    {
        // Base de données
        $db = $this->database;
        if(!is_object($this->database)) {
            $db = Database::getConnection($this->product);
        }

        // Famille courante
        $familyModel = new FamilyModel(
                FamilyModel::getFamilyFromIdGroupTable($this->group_id,$this->product),
                $this->product);

        // Nom de la famille
        $family         = $familyModel->getValue('family');
        // Récupération du group table
        $edwGroupTable  = $familyModel->getEdwGroupTable();
        // Ta Min de la famille
        $taMin          = TaModel::getTaRawMinFromFamily($familyModel->getValue('rank'), $this->product);
        // Niveau minimum de la famille
        $naMin          = $familyModel->getValue('network_aggregation_min');
        // Récupération des NA 1er axe
        $firstAxisNets  = NaModel::getNaFromFamily($family, $this->product, 1);
        // Gestion du 3ème axe
        $naMinThrisAxis = '';
        $thirAxisNets   = NaModel::getNaFromFamily($family,$this->product,3);
        if(count($thirAxisNets) > 0)
            $naMinThrisAxis = $thirAxisNets[0];
        // Construction de la table de niveau min de la famille
        $tableMin = $edwGroupTable.'_raw_'.$naMin.'_'.(!empty($naMinThrisAxis) ? $naMinThrisAxis.'_' : '').$taMin;
        // On récupère les tables à nettoyer
        $query = "SELECT *
            FROM
            (
                SELECT
                        c.relname,
                        split_part(replace(c.relname, '{$edwGroupTable}_raw_', ''),'_',1) AS net_1,
                        CASE WHEN split_part(replace(c.relname, '{$edwGroupTable}_raw_', ''),'_',2) IN ('".implode("','",TaModel::getAllTa($familyModel->getValue('rank'), $this->product))."')
                        THEN NULL ELSE split_part(replace(c.relname, '{$edwGroupTable}_raw_', ''),'_',2) END AS net_3,
                        a.attname
                FROM
                        pg_class c,
                        pg_attribute a
                WHERE
                        c.oid = a.attrelid
                AND     relname LIKE '{$edwGroupTable}_raw_%'
                AND     relname != '{$tableMin}'
                AND     attnum >= 0
                AND     attname IN ('".implode("','",$firstAxisNets)."'".((count($thirAxisNets) > 0) ? ",'".implode("','",$thirAxisNets)."'" : "").")
                AND     relname !~ '[0-9]$'
            )t0
            WHERE
                    (net_1 != attname AND net_3 != attname)
            OR      (net_1 != attname AND net_3 IS NULL)";
        $result = $db->execute($query);
        // Nettoyage des colonnes
        while($row = $db->getQueryResults($result, 1)) {
            $db->execute("ALTER TABLE ".$row['relname']." DROP COLUMN ".$row['attname']);
        }
    }

    /**
     * crée la requête qui met deploy_status à 0 pour le data_type $data_type et le groupe $group dans la table sys_definition_group_table_"$type_agreg", puis insère cette requête dans le tableau $this->requetes[$group][].
     *
     * @param string $group
     * @param string $data_type
     * @param string $toupdate
     * @param string $type_agreg
     */
    function update_sys_line($group, $data_type, $toupdate, $type_agreg)
    {
        $group_id = $this->get_group_id($group);
        $query = "update sys_definition_group_table_" . $type_agreg . "
                        set deploy_status='0'
                        where id_group_table='$group_id' and " . $type_agreg . "_agregation='$toupdate'
                        and data_type='$data_type'";
        $this->requetes[$group][] = $query;
    }

    /**
     * crée la requête qui supprime la ligne concernant l'enregistrement $todel pour le data_type $data_type et le groupe $group dans la table sys_definition_group_table_"$type_agreg", puis insère cette requête dans le tableau $this->requetes[$group][].
     *
     * @param string $group
     * @param string $data_type
     * @param string $todel
     * @param string $type_agreg
     */
    function del_sys_line($group, $data_type, $todel, $type_agreg)
    {
        $group_id = $this->get_group_id($group);
        $query = "delete from sys_definition_group_table_" . $type_agreg . "
                        where id_group_table='$group_id' and " . $type_agreg . "_agregation='$todel'
                        and data_type='$data_type'";
        $this->requetes[$group][] = $query;
    }


    /**
     * retourne tous les niveaux de network contenus dans sys_definition_group_table_network où data_type=raw
     *
     * @return array
     */
    function select_all_net()
    {
        // $query="select network_agregation
        // from sys_definition_group_table_network
        // where data_type='raw' and  id_group_table='$this->group_id'";
        // $res=pg_query($this->database_connection,$query);
        // while($row=pg_fetch_array($res))
        // $nets1[]=trim($row[0]);
        $query2 = "select network_agregation
                        from sys_definition_group_table_network t1, sys_definition_group_table t2
                        where data_type='raw' and raw_deploy_status!=1
                        and t1.id_group_table=t2.id_ligne";
        $res1 = pg_query($this->database_connection, $query2);
        while ($row1 = pg_fetch_array($res1))
        $nets2[] = trim($row1[0]);
        // $nets=array_merge($nets1,$nets2);
        return $nets2;
    }

    /**
     * Vérifie si une colonne existe
     * @param string $table
     * @param string $col
     * @return boolean
     */
    function columnExists($table, $col)
    {
        $db = $this->database;
        if(!is_object($db)) {
            $db = Database::getConnection();
        }
        return $db->columnExists($table, $col);
    }

    /**
     * crée les requêtes permettant d'ajouter ou de supprimer (en fct de $type_agreg) la colonne $field dans les tables de $group et $data_type
     *
     * @param string $group
     * @param string $data_type
     * @param string $field
     * @param string $type_agreg = new_net ou del_net ou time
     */
    function get_query_alter($group, $data_type, $field, $type_agreg)
    {
        $group_id = $this->get_group_id($group);
        switch ($type_agreg) {
            case "new_net":

                $min_time = $this->get_min_time_level($group_id, $data_type);
                $min_net = $this->get_min_network_level($group_id, $data_type);
                $table = $group . "_" . $data_type . "_" . $min_net . "_" . $min_time;
                // si le net ajouté n'est pas le niveau le plus bas
                if ($field != $min_net) {
					// maj 11:50 30/10/2008 - MPR : On ajoute uniquement le niveau d'agrégation réseau étant donné que le na 3ème axe existe déjà
					// maj 15/07/2009 - MPR : Correction du bug 10625 - Ajout du produit dans l'appel de la fonction get_axe3
					if( get_axe3($this->family, $this->product) )
					{
						$_net = explode("_",$field);

                                                // 08/06/2011 BBX -PARTITIONING-
                                                // Ajout vérification existence colonne pour ne pas générer d'erreur SQL
                                                if(!empty($_net[0])) {
                                                    if(!$this->columnExists($table, $_net[0]))
                                                        $this->requetes[$group][] = "alter table $table add ".$_net[0]." text";
                                                }

						// 06/08/2009 - MPR :
						//	- Correction du bug 10945 : Tous les niveaux d'agrégation 3ème axe

                                                // 08/06/2011 BBX -PARTITIONING-
                                                // Ajout vérification existence colonne pour ne pas générer d'erreur SQL
                                                if(!empty($_net[1])) {
                                                    if(!$this->columnExists($table, $_net[1]))
                                                        $this->requetes[$group][] = "alter table $table add ".$_net[1]." text";
                                                }
					}
					else
					{
                                                // 08/06/2011 BBX -PARTITIONING-
                                                // Ajout vérification existence colonne pour ne pas générer d'erreur SQL
                                                if(!empty ($field)) {
                                                    if(!$this->columnExists($table, $field))
                                                        $this->requetes[$group][] = "alter table $table add $field text";
                                                }
					}
				}
                break;

            case "del_net":

                $min_time = $this->get_min_time_level($group_id, $data_type);
                $min_net = $this->get_min_network_level($group_id, $data_type, "0");
                $table = $group . "_" . $data_type . "_" . $min_net . "_" . $min_time;
				$_net = explode("_",$field);
                // si le net supprimé n'est pas le niveau le plus bas
                if ($field != $min_net) {
					// maj 15/07/2009 - MPR : Correction du bug 10625 - Ajout du produit dans l'appel de la fonction get_axe3
					if( get_axe3($this->family, $this->product) )
					{
                                            // 08/06/2011 BBX -PARTITIONING-
                                            // Ajout vérification existence colonne pour ne pas générer d'erreur SQL
                                            if($this->columnExists($table, $_net[0]))
                                                $this->requetes[$group][] = "alter table $table drop ".$_net[0];
					}else{
                                            // 08/06/2011 BBX -PARTITIONING-
                                            // Ajout vérification existence colonne pour ne pas générer d'erreur SQL
                                            if($this->columnExists($table, $field))
						$this->requetes[$group][] = "alter table $table drop $field";
					}
                } else { // sinon on ajoute tous les champs network dans la table qui devient la table de base
                    $new_min_net = $this->get_min_network_level($group_id, $data_type, "new");
                    $table = $group . "_" . $data_type . "_" . $new_min_net . "_" . $min_time;
                    $nets0 = $this->select_net_fields($group_id, $data_type, "0");
                    $nets1 = $this->select_net_fields($group_id, $data_type, "1");

					$nets = array_merge($nets0, $nets1);
                    for($n = 0;$n < count($nets);$n++)
                    if ($nets[$n] != $new_min_net){
						// maj 15/07/2009 - MPR : Correction du bug 10625 - Ajout du produit dans l'appel de la fonction get_axe3
						if( get_axe3($this->family, $this->product) ){
							$_nets = explode("_",$nets[$n]);
							foreach($_nets as $_net){
                                                            // 08/06/2011 BBX -PARTITIONING-
                                                            // Ajout vérification existence colonne pour ne pas générer d'erreur SQL
                                                            if(!$this->columnExists($table, $_net))
								$queries[] = "alter table $table add $_net text";
							}
						}else{
                                                    // 08/06/2011 BBX -PARTITIONING-
                                                    // Ajout vérification existence colonne pour ne pas générer d'erreur SQL
                                                    if(!$this->columnExists($table, $nets[$n]))
							$queries[] = "alter table $table add $nets[$n] text";
						}

                    }
					$this->requetes[$group] = array_merge($queries, $this->requetes[$group]);
                }
                break;

            case "time":
                $times = $this->get_time_levels($field, "smaller");
                $nets = $this->select_net_fields($group_id, $data_type, "-1");
                $tables_mod = $this->get_tables($group, $nets, $times, $data_type);
                for($t = 0;$t < count($tables_mod);$t++) {
                    $_table = preg_replace('/\//', "_", $tables_mod[$t]);

                    // 08/06/2011 BBX -PARTITIONING-
                    // Ajout vérification existence colonne pour ne pas générer d'erreur SQL
                    if(!$this->columnExists($_table, $field))
                        $this->requetes[$group][] = "alter table $_table add $field int4";
                }
                break;
        }
    }

	/* Retourne la valeur du champ passe en parametre dans la table sys_definition_time_agregation
	  * @param time - le nom d'un niveau d'agregation de temps (hour, day, week, day_bh...)
	  * @param field - le nom d'une colonne de la table sys_definition_time_agregation
	  * @return la valeur du champ
	  */
	function getTimeFieldValue($time,$field){
		$field_value=null;
		$query="SELECT $field FROM sys_definition_time_agregation WHERE agregation='$time'";
		$res = pg_query($this->database_connection, $query);
		if(pg_num_rows($res)>0){
			$row=pg_fetch_array($res,0);
			$field_value=$row[$field];
		}
		return $field_value;
	}

    /**
     * retourne la requête de création de la table passée en paramètre
     *
     * @param string $table
     * @return string
     */
    function get_query_create($table)
    {
        $infos = explode("/", $table);
   		$group_id = $this->get_group_id($infos[0]);
     /*
        if (ereg("_temp$", $infos[3])) {
            $infos[3] = ereg_replace("_temp", "", $infos[3]);
            $temp = 1;
        }*/
        unset($time_fields);
        $time_fields = $this->get_time_levels($infos[3], "bigger");

    /*modif MD pour conserver l'heure ou le jour dans les tables concernant la busy hour*/
		//print '<font style="color:dimgray">'.$table.'</font><br/>';
		if($this->getTimeFieldValue($infos[3],"bh_list")=="bh"){//le niveau est de type bh
        $time_fields[count($time_fields)]="bh";
			//print '<font style="color:red">'.$table.'</font> => '.$time_inf.'<br/>';
		}

        //$group_id = $this->get_group_id($infos[0]);
        // si table = raw et que les niveaux time et network sont les plus bas,
        // la table contient tous les niveaux de network
        if ($infos[2] == $this->get_min_network_level($group_id, $infos[1]) && $infos[3] == $this->get_min_time_level($group_id, $infos[1]) && $infos[1] == "raw")
            $net_fields = $this->get_net_levels_greater($infos[2], $group_id, $infos[1]);
        else
            $net_fields[0] = $infos[2];

        if ($infos[1] == "mixed_kpi")
            $infos[0] = "edw";

        $_table = implode("_", $infos);


        // 08/06/2011 BBX -PRTITIONING-
        // Si on est sous PG 9.1 on test l'existence de la table
        $tableExists = "";
        if(is_object($this->database)) {
            if($this->database->getVersion() >= 9.1)
                $tableExists = "IF NOT EXISTS";
        }

        $query = "create table $tableExists $_table (";

		$tab = array();

        for($s = 0;$s < count($net_fields);$s++){
			// maj 10:57 30/10/2008 - maxime : On éclate les combinaisons na_na3A
			$_net = array();

			// Correction du bug 10311 : On effectue un contrôle supplémentaire afin d'éviter les colonnes en double (uniquement pour les tables de données sur le na min

			// maj 15/07/2009 - MPR : Correction du bug 10625 - Ajout du produit dans l'appel de la fonction get_axe3
			if( get_axe3($this->family, $this->product) ){


				$t = explode("_",$net_fields[$s]);
				if(!in_array($t[0], $tab) and $t[0] !== null){
					$tab[] = $t[0];
					$_net[] = $t[0];
				}
				if( !in_array($t[1], $tab) and $t[1] !== null ){
					$tab[] = $t[1];
					$_net[] = $t[1];
				}
				// $_net =  explode("_",$net_fields[$s]);

			} else {

				if( !in_array($net_fields[$s], $tab)){

				$_net = array($net_fields[$s]);

				}
			}

			// 28/07/2009  MPR : Correction du bug 10865 - Les tables hour ne sont pas générées - présence de colonne sans nom
			if( count($_net) > 0 )
				$query .= implode(" text, ",$_net)." text,";

		}

        for($t = 0;$t < count($time_fields);$t++){
			$times[] = "$time_fields[$t] int4";
		}
        $query .= implode(",", $times);
        // si on est dans le cas d'un ajout de network ou de time,
        // il faut aller chercher les champs existants à ajouter
        // dans sys_field_reference et sys_definition_kpi
        if (count($this->nets_to_add[$infos[1]]) > 0 || count($this->times_to_add[$infos[1]]) > 0)
            if (@in_array($infos[2], $this->nets_to_add[$infos[1]]) || @in_array($infos[3], $this->times_to_add[$infos[1]])) {
                if ($infos[1] == "raw")
                    $fields = $this->get_fields_raw($infos[0]);
                else if ($infos[1] == "kpi")
                    $fields = $this->get_fields_kpi($infos[0], 0);
				        else if ($infos[1] == "adv_kpi")
					         $fields = $this->get_fields_adv_kpi($infos[0]);

                if (count($fields) > 0) {
                    $query .= ",";
                    foreach ($fields as $champ => $type)
                    $champs[] = "$champ $type";
                    $query .= implode(",", $champs);
                }
            }
            $query .= ") WITH OIDS;";

            // 19/05/2011 BBX - PARTITIONING -
            // Si la base de données est partitionnée il faut ajouter le trigger
            // qui va permettre de bloquer l'insertion directe dans les tables de données
            if(is_object($this->database))
            {
                if($this->database->isPartitioned()) {
                    $query .= "CREATE TRIGGER {$_table}_trig_lock BEFORE INSERT OR UPDATE OR DELETE ON {$_table}
                    FOR EACH ROW EXECUTE PROCEDURE lock_data_tables();";
                }
            }
            // 04/06/2012 MMT Bz 27408 - ajout de droit sur la table créée pour read_only_user afin que cela fonctionne
            // en query builder slave
            $query .= "GRANT SELECT ON public.{$_table} TO read_only_user;";

            return $query;
        } 

        /**
         * retourne le plus bas niveau d'agrégation de network souhaité pour le group_table $group_table de type $data_type (raw, kpi, ...), pris dans sys_definition_group_table_network
         *
         * @param int $group_id
         * @param string $data_type
         * @return string
         */
        function get_min_network_level($group_id, $data_type)
        {
            $query = "select network_agregation
                        from sys_definition_group_table_network
                        where id_group_table='$group_id'
                        and data_type='$data_type'";

            $query .= "order by rank limit 1";

            $res = pg_query($this->database_connection, $query);
            while ($row = pg_fetch_array($res))
            $level = $row[0];
            return $level;
        }

        /**
         * retourne le plus bas niveau d'agrégation de time souhaité pour le group_table $group_table de type $data_type (raw, kpi, ...), pris dans sys_definition_group_table_time
         *
         * @param int $group_id
         * @param string $data_type
         * @return string
         */
        function get_min_time_level($group_id, $data_type)
        {
            $query = "select time_agregation from sys_definition_group_table_time
                        where id_group_table='$group_id' and data_type='$data_type'
                        and id_source in
                        (select min(id_source) from sys_definition_group_table_time
                         where id_group_table='$group_id'
                         and data_type='$data_type')";
            $res = pg_query($this->database_connection, $query);
            while ($row = pg_fetch_array($res))
            $level = $row[0];
            return $level;
        }

        /**
         * retourne la requête de suppression de la table passée en paramètre (table au format group/data_type/net/time)
         *
         * @param string $table
         * @return string
         */
        function get_query_drop($g, $table)
        {
            // echo "drop table $table<br>";
            $infos = explode("/", $table);
            if ($infos[1] == "mixed_kpi")
                $infos[0] = "edw";
            // print_r($infos);
            $_table = implode("_", $infos);
            // echo "drop table $table /$g<br>";
            $this->postgres_drop_table($g, strToLower($_table));
            /*
            if ($infos[2] == $this->min_net && $infos[3] == $this->min_time) {
                // echo "2eme appel";
                $this->postgres_drop_table($g, strToLower($_table . "_temp"));
            } */
        }

        /**
         * Génère les requêtes de suppression des tables
         * @param type $g : groupe
         * @param type $table_name : nom de la table
         * 27/08/2012 BBX
         * BZ 28639 : utilisation de la classe Database pour éxécuter les requêtes
         * Avis au merger : il est préférable de prendre la fonction entière
         */
        function postgres_drop_table($g, $table_name)
        {
            // Base de données
            $db = $this->database;
            if(!is_object($this->database)) {
                $db = Database::getConnection($this->product);
            }
            
            // Requête standard
            $queryDrop = "DROP TABLE IF EXISTS ".$table_name;
            // Cas partitionné
            if ( $db->getVersion() >= 9.1 && $db->isPartitioned() ) {
                $queryDrop .= " CASCADE";
            }
            
            // Ajout de la requête en queue
            $this->requetes[$g][] = $queryDrop;
        }

        /**
         * retourne les niveaux d'agrégation de time (pris dans sys_definition_time_agregation) supérieurs ou inférieurs à $time, en fonction de $type
         *
         * @param string $time
         * @param string $type = bigger or smaller
         * @return array
         */
        function get_time_levels($time, $type)
        {
            $query = "select agregation
                        from sys_definition_time_agregation where agregation_rank";
            if ($type == "bigger")
                $query .= " >= ";
            else if ($type == "smaller")
                $query .= " < ";
            $query .= " (select agregation_rank from sys_definition_time_agregation
                        where agregation='$time')
                        order by agregation_rank";
            $res = pg_query($this->database_connection, $query);
            while ($row = pg_fetch_array($res)) {
                $times[] = $row[0];
            }
            return $times;
        }

        /**
         * retourne les niveaux de net supérieurs à $net, pris dans sys_definition_group_table_network
         *
         * @param string $net
         * @param string $data_type
         * @param int $group_id
         * @return array
         */
        function get_net_levels_greater($net, $group_id, $data_type)
        {
            $query = "select network_agregation from sys_definition_group_table_network
                        where  id_group_table='$group_id'
                        and data_type='$data_type' and rank>=
                        (select rank from sys_definition_group_table_network where
                         network_agregation='$net' and id_group_table='$group_id'
                         and data_type='$data_type') order by rank";
            $res = pg_query($this->database_connection, $query);


            while ($row = pg_fetch_array($res))
            $nets[] = $row[0];
            return $nets;
        }

        /**
         * retourne les niveaux d'agrégation de temps pour le group_id et le data_type avec deploy_status=$op. si $op=-1, retourne ts les time déployés pour le group_id
         *
         * @param int $group_id
         * @param string $data_type
         * @param int $op
         * @return array
         */
        function select_time_fields($group_id, $data_type, $op)
        {
			$fields = array();
            $query = "select time_agregation from sys_definition_group_table_time
                        where data_type='$data_type'
                        and id_group_table='$group_id'";
            if ($op != "-1")
                $query .= " and deploy_status='$op'";
            else
                $query .= " and deploy_status<>'1'";

            $res = pg_query($this->database_connection, $query);
            while ($row = pg_fetch_array($res)) {
                $fields[] = $row[0];
            }
            return $fields;
        }

        /**
         * retourne les niveaux de network pour le group et le type passés en paramètres.
         * si $op=1, retourne les niveaux à déployer.
         * si $op=-1, retourne tous les niveaux déployés au temps t.
         *
         * @param int $group_id
         * @param string $data_type
         * @param int $op
         * @return array
         */
        function select_net_fields($group_id, $data_type, $op)
        {
			$nets = array();
			unset($this->nets);
			unset($this->nets_axe3);
			$this->nets = array();
			$this->nets_axe3 = array();
			$query = "select network_agregation from sys_definition_group_table_network
						where data_type='$data_type'
						and id_group_table='$group_id'";
			if ($op != "-1")
				$query .= " and deploy_status='$op'";
			else
				$query .= " and deploy_status!='1'";
			$query .= " order by rank";
			$res = pg_query($this->database_connection, $query);

			while ($row = pg_fetch_array($res)){
				$nets[] = $row[0];
				$this->nets_axe3[] = $row[0];
			}

			// maj 15/07/2009 - MPR : Correction du bug 10625 - Ajout du produit dans l'appel de la fonction get_axe3
			if(get_axe3($this->family, $this->product) and $this->op==1){
				// maj 15/07/2009 - MPR : Correction du bug 10625 - Ajout du produit dans l'appel de la fonction getNaLabelList
				$lst_na = getNaLabelList('all',$this->family, $this->product);
				foreach($lst_na[$this->family] as $na=>$v){
					$this->nets[] = $na;
				}
			}
			$nets = ( (count($this->nets)>0) ? array_unique(array_merge($this->nets,$this->nets_axe3)) : $this->nets_axe3);
			// On regroupe les na et les na du troisème axe
		return $nets;
        }

        /**
         * retourne l'id du groupe dont le nom est $name
         *
         * @param string $name
         * @return int
         */
        function get_group_id($name)
        {
            global $database_connection;
            $query = "select id_ligne from sys_definition_group_table
                        where edw_group_table='$name'";
            $res = pg_query($this->database_connection, $query);
            while ($row = pg_fetch_array($res))
            $group = $row[0];
            return $group;
        }

        /**
         * retourne le nom du groupe dont l'id est $id
         *
         * @param int $id
         * @return string
         */
        function get_group_name($id)
        {
            $query = "select edw_group_table  from sys_definition_group_table
                        where id_ligne='$id'";
            $res = pg_query($this->database_connection, $query);
            while ($row = pg_fetch_array($res))
            $group = $row[0];
            return $group;
        }

        /**
         * retourne un tableau contenant l'ensemble des combinaisons de tables possibles à partir des éléments des 3 tableaux passés en paramètres, ATTENTION les tables cibles produites ont le format group_table/data_type/network/time
         *
         * @param string $group
         * @param array $networks ensemble des networks souhaités pour le group donné
         * @param array $times ensemble des times souhaités pour le group donné
         * @return array
         */
        function get_tables($group, $networks, $times, $data_type)
        {
            $cibles3 = array();
            $size = count($networks) * count($times);
			//_debug($networks,"network");
            $n1 = count($networks);
            $t1 = count($times);

            for($j = 0;$j < $size;$j++)
            $cibles[$j] = $group . "/" . $data_type . "/";

            for($n = 0;$n < count($networks);$n++)
			for($j = $n;$j < $size;$j += $n1)
            $cibles[$j] .= $networks[$n] . "/";

            @sort($cibles);

            for($t = 0;$t < count($times);$t++)
            for($i = $t;$i < $size;$i += $t1)
            $cibles[$i] .= $times[$t];
            // on remet tout dans le bon ordre
            for($n = 0;$n < count($networks);$n++)
            for($m = 0;$m < count($cibles);$m++)
            if (preg_match('/' . $networks[$n] . '/', $cibles[$m]))
                $cibles3[] = $cibles[$m];

            $group_id = $this->get_group_id($group);
            $net_min = $this->get_min_network_level($group_id, $data_type);

            /*
                        if ($data_type == "raw" and in_array($net_min, $networks)) { // if($data_type=="raw" )
                $group_id = $this->get_group_id($group);
                $cibles3[] = $group . "/raw/" . $this->get_min_network_level($group_id, $data_type) . "/" . $this->get_min_time_level($group_id, $data_type) . "_temp";
            } */

            return $cibles3;
        }

        /**
         * parcourt le tableau tables_to_create et crée les requêtes d'index pour chaque table.
         * Tous les paramètres sont optionnels et ne sont utilisés
         * que si on intervient "manuellement" sur les index,
         * sinon les index sont créés dès qu'une table est créée.
         *
         * @param array $groups_todo tableau de group_ids à prendre en compte
         * @param array $data_todo tableau de data_type à prendre en compte
         * @param int $op type d'opération (create ou drop)
         */
        function create_indexes($op = 0, $groups_data_todo = 0)
        {
            // gestion "manuelle"
            if ($op != "0") {

                foreach($groups_data_todo as $group_id => $data_types) {
                    unset($tables);
                    for($d = 0;$d < count($data_types);$d++) {
                        $group = $this->get_group_name($group_id);
                        $times = $this->select_time_fields($group_id, $data_types[$d], "0");
                        $nets = $this->select_net_fields($group_id, $data_types[$d], "0");
                        $tables = array_merge($this->get_tables($group, $nets, $times, $data_types[$d]), $tables);
                    }
                    $tables = array_unique($tables);
                    // print_r($tables);
                    for($t = 0;$t < count($tables);$t++) {
                        $tab_indexes = array();
						print "**** creation d'index pour " . $tables[$t] . "<br>";
                        $infos = explode("/", $tables[$t]);
                        $_table_index = preg_replace('/'.$infos[0].'/', "$group_id", $tables[$t]);
                        $_table_index = preg_replace('/\//', "_", $_table_index);
                        $_table = preg_replace('/\//', "_", $tables[$t]);

						if(!in_array($infos[2],$tab_indexes) and !in_array($infos[3],$tab_indexes)){
							$tab_indexes[] = $infos[2];
							$tab_indexes[] = $infos[3];

							if ($op == "drop") {
								// A PRIORI CELA NE SERT PAS CAR POUR DROPPER UN INDEX IL SUFFIT DE DROPPER UN CHAMP OU LA TABLE
								$queries[] = "drop index index_" . $_table_index . "_$infos[2]";
								$queries[] = "drop index index_" . $_table_index . "_$infos[3]";
								$queries[] = "drop index index_" . $_table_index . "_$index";
								$queries[] = "drop index index_" . $_table_index . "_$index" . "_$infos[2]";
								$queries[] = "drop index index_" . $_table_index . "_$index" . "_$infos[3]";
								$queries[] = "drop index index_" . $_table_index . "_$infos[2]_$infos[3]";
							}
							if ($op == "create") {
								$query1 = "create index ix_" . uniqid("") . " on $_table using btree($infos[2])";
								print $query1 . "<br>";
								pg_query($this->database_connection, $query1);
								$query2 = "create index ix_" . uniqid("") . " on $_table using btree($infos[3])";
								print $query2 . "<br>";
								pg_query($this->database_connection, $query2);
								$query3 = "create index ix_" . uniqid("") . " on $_table using btree($infos[2],$infos[3])";
								print $query3 . "<br>";
								pg_query($this->database_connection, $query3);
								if ($infos[3] == "hour") {
									$query7 = "create index ix_" . uniqid("") . " on $_table using btree(day)";
									print $query7 . "<br>";
									pg_query($this->database_connection, $query7);
								}
								if ($infos[3] == "day" and $infos[1] == 'raw') {
									$query8 = "create index ix_" . uniqid("") . " on $_table using btree(week)";
									print $query8 . "<br>";
									pg_query($this->database_connection, $query8);
									$query9 = "create index ix_" . uniqid("") . " on $_table using btree(month)";
									print $query9 . "<br>";
									pg_query($this->database_connection, $query9);
								}
							}
						}
                    }
                }
            } else {
				$listIndexes = array();

                // gestion automatique, index créés dès qu'une table est créée
			   for ($t = 0;$t < count($this->tables_to_create);$t++) {
					$tab_indexes = array();
					$_table = preg_replace('/\//', "_", $this->tables_to_create[$t]);

					// 12:11 02/09/2009 GHX
					// BZ 11338
					// On récupère la liste des indexes de la tables
					if ( !array_key_exists($_table, $listIndexes) )
					{
						$sqlIndexes = "SELECT indexname, indexdef from pg_indexes where tablename='$_table'";
						$resultIndexes = pg_query($sqlIndexes);
						if ( pg_num_rows($resultIndexes) > 0 )
						{
							while ( list($indexName, $indexSql) = pg_fetch_row($resultIndexes) )
							{
								$listIndexes[$_table] .= str_replace('"', '', $indexSql).';';
							}
						}
					}

                    $infos = explode("/", $this->tables_to_create[$t]);

                    $group_id = $this->get_group_id($infos[0]);
					if(!in_array($infos[2].$infos[3],$tab_indexes)){
						$tab_indexes[] = $infos[2].$infos[3];

						// Si on a un troisieme axe
						if ( !(strpos($infos[2], '_') === false) )
						{
							$_ = explode('_', $infos[2]);
							$infos[2] = $_[0];
							$infos[4] = $_[1];
						}

						// 14:55 02/09/2009 GHX
						// Ajout de toutes les conditions pour savoir si les index sont déjà créé si oui on ne crée pas l'index
						if ( stripos($listIndexes[$_table], "ON $_table USING btree ($infos[2]);") === false )
						{
							$query1 = "create index ix_" . uniqid("") . " on $_table using btree($infos[2])";
							$this->requetes[$infos[0]][] = $query1;
						}
						if ( stripos($listIndexes[$_table], "ON $_table USING btree ($infos[3]);") === false )
						{
							$query2 = "create index ix_" . uniqid("") . " on $_table using btree($infos[3])";
							$this->requetes[$infos[0]][] = $query2;
						}
						// Si on n'a pas de troisieme axe
						if ( !isset($infos[4]) )
						{
							if ( stripos($listIndexes[$_table], "ON $_table USING btree ($infos[2], $infos[3]);") === false )
							{
								$query6 = "create index ix_" . uniqid("") . " on $_table using btree($infos[2],$infos[3])";
								$this->requetes[$infos[0]][] = $query6;
							}
						}
						else // Si on a un troisieme axe
						{
							if ( stripos($listIndexes[$_table], "ON $_table USING btree ($infos[4]);") === false )
							{
								$query1 = "create index ix_" . uniqid("") . " on $_table using btree($infos[4])";
								$this->requetes[$infos[0]][] = $query1;
							}
							if ( stripos($listIndexes[$_table], "ON $_table USING btree ($infos[2], $infos[4], $infos[3]);") === false )
							{
								$query6 = "create index ix_" . uniqid("") . " on $_table using btree($infos[2],$infos[4],$infos[3])";
								$this->requetes[$infos[0]][] = $query6;
							}
						}

						// l'index day sert pour les raw lors de l'aggregation et lors du clean history
						// l'index day sert pour les KPI lors du clean history
						if ($infos[3] == "hour" && stripos($listIndexes[$_table], "ON $_table USING btree (day);") === false) {
							$query7 = "create index ix_" . uniqid("") . " on $_table using btree(day)";
							$this->requetes[$infos[0]][] = $query7;
						}
						if ($infos[3] == "day" and $infos[1] == 'raw' ) {
							if ( stripos($listIndexes[$_table], "ON $_table USING btree (week);") === false )
							{
								$query8 = "create index ix_" . uniqid("") . " on $_table using btree(week)";
								$this->requetes[$infos[0]][] = $query8;
							}
							if ( stripos($listIndexes[$_table], "ON $_table USING btree (month);") === false )
							{
								$query9 = "create index ix_" . uniqid("") . " on $_table using btree(month)";
								$this->requetes[$infos[0]][] = $query9;
							}
						}
					}
			    }
            }
			// maj 07 05 2007 - Création des indexes sur tous les na de chaque famille (normale ou 3ème axe)
			// maj 15/07/2009 - MPR : Correction du bug 10625 - Ajout du produit dans l'appel de la fonction get_axe3
			if(get_axe3($this->family, $this->product) and $this->op!=2){
				// Pour les familles 3ème axe on récupère tous les niveaux d'aggrégation et on créé les combinaisons na_naAxe3
				// maj 15/07/2009 - MPR : Correction du bug 10625 - Ajout du produit dans l'appel de la fonction getNaLabelList
				$lst_na = getNaLabelList('na',$this->family, $this->product);
				// maj 15/07/2009 - MPR : Correction du bug 10625 - Ajout du produit dans l'appel de la fonction getNaLabelList
				$lst_na_axe3 = getNaLabelList('na_axe3',$this->family, $this->product);
				foreach($lst_na[$this->family] as $na=>$na_label){
					$nets[] = $na;
                                        if(is_array($lst_na_axe3[$this->family])) {
                                            foreach($lst_na_axe3[$this->family] as $na_axe3=>$na_axe3_label){
                                                    $nets[] = $na."_".$na_axe3;
                                            }
                                        }
				}
			}
			else{
				// maj 15/07/2009 - MPR : Correction du bug 10625 - Ajout du produit dans l'appel de la fonction getNaLabelList
				$lst_na = getNaLabelList('na',$this->family, $this->product);
				foreach($lst_na[$this->family] as $na=>$na_label){
					$nets[] = $na;
				}
			}
			// CCT1 28/08/09 : mise en commentaire de la création d'index sur les tables edw_object_x_ref qui n'existent plus.
			/*
			if(count($nets)>0){
				$group_id = $this->get_group_id($infos[0]);
				foreach($nets as $net){
					$query10 = "create index index_edw_object_" . $group_id . "_$net on edw_object_" . $group_id . " using btree($net)";
					$this->requetes[$infos[0]][] = $query10;
					$query11 = "create index index_edw_object_" . $group_id . "_ref_$net on edw_object_" . $group_id . "_ref  using btree($net)";
					$this->requetes[$infos[0]][] = $query11;
				}
			}
			*/
        }

        /**
         * Purge des données Source Availability
         */
        function cleanTableSA()
        {
            $offsets_sa = $this->get_history(true);

            $off_day_sa = $offsets_sa["offset_day"] + $offsets_sa["history_day"];
            $off_hour_sa = $offsets_sa["offset_day"] + $offsets_sa["history_hour"];

            // 09/06/2011 BBX -PARTITIONING-
            // Correction des casts

            // Purge des données SA Hour
            $query_hour = "
                    DELETE FROM sys_definition_sa_view
                    WHERE sdsv_ta = 'hour' AND sdsv_ta_value <= '".getDay($off_hour_sa)."23'";

            // Purge des données SA Day
            $query_day = "
                    DELETE FROM sys_definition_sa_view
                    WHERE sdsv_ta = 'day' AND sdsv_ta_value <= '".getDay($off_day_sa)."'";

            $res = pg_query($this->database_connection, $query_hour);
            displayInDemon("Purge des données Hour :");
            displayInDemon(pg_affected_rows($res) . "=" . $query_hour);
            displayInDemon("Purge des données Day :");
            $res = pg_query($this->database_connection, $query_day);
            displayInDemon(pg_affected_rows($res) . "=" . $query_day);

        }

        function clean_tables()
        {
            $query = "select distinct data_type from sys_definition_group_table_time
                        where deploy_status='0' and id_group_table='$this->group_id' and on_off='1'";

            $res = pg_query($this->database_connection, $query);
            while ($row = pg_fetch_array($res))
				$types[] = $row[0];

            $offsets = $this->get_history();
            //_debug($offsets,'offset',1); //Affiche les élements d'historique
            print "<br>";

            $off_month = $offsets["offset_day"] + $offsets["history_month"] * 30; //converti l'interval en nombre de jours : en moyenne 30 jours par mois
            $off_week = $offsets["offset_day"] + $offsets["history_week"] * 7; //converti l'interval en nombre de jours : 7 jours par semaine
            $off_day = $offsets["offset_day"] + $offsets["history_day"];
            $off_hour = $offsets["offset_day"] + $offsets["history_hour"];

			// effacement de la table object du group table. On en conserve qu'un historique correspondant à l'historique hourly
			// maj 20/11/2008 - maxime : On supprime les éléments ds les table edw_object_ref, edw_object_ref_parameters et edw_object_arc_ref

			// 31/03/2009 BBX : on ne supprime plus les éléments sur edw_object_ref, edw_object_ref_parameters & edw_object_arc_ref
			/*
			$sub_query_params = "SELECT eor_id FROM edw_object_ref WHERE eor_date<=" . getDay($off_hour)."
								AND eor_obj_type = (
										SELECT network_aggregation_min
										FROM sys_definition_categorie
										WHERE family = '".get_main_family()."'
										LIMIT 1
								)";

			// Suppression des données topologiques dans edw_object_ref_parameters
			$query = "DELETE FROM edw_object_ref_parameters WHERE eorp_id IN ($sub_query_params)";
			$res = pg_query($this->database_connection, $query);
			print pg_affected_rows($res) . "=" . $query . "<br>";

			// Suppression des données topologiques dans edw_object_arc_ref
			// On supprime les arcs contenant les éléments trop anciens qu'ils soient parent ou enfant
			$query = "DELETE FROM edw_object_arc_ref
					  WHERE EXISTS(
							SELECT eor_id
							FROM edw_object_arc_ref , edw_object_ref
							WHERE
							(eoar_id = eor_id
								AND ( SPLIT_PART(eoar_arc_type,'|s|',1) = eor_obj_type)
								AND eor_date<=".getDay($off_hour)."
							)
							OR
							(
							eoar_id_parent = eor_id
							AND ( SPLIT_PART(eoar_arc_type,'|s|',2) = eor_obj_type)
							AND eor_date<=".getDay($off_hour).")

					 )";

			$res = pg_query($this->database_connection, $query);
			print pg_affected_rows($res) . "=" . $query . "<br>";
			*/

			// 31/03/2009 BBX : modification de la suppression de l'historique. Il faut supprimer dans edw_object et non pas dans edw_object_ref
            //$query = "DELETE FROM edw_object_ref WHERE eor_date<=" . getDay($off_hour);
            // 09/06/2011 BBX -PARTITIONING-
            // Correction des casts
			$query = "DELETE FROM edw_object WHERE eo_date<='" . getDay($off_hour)."'";
            $res = pg_query($this->database_connection, $query);
			print pg_affected_rows($res) . "=" . $query . "<br>";

			$tables = array();
            for($d = 0;$d < count($types);$d++) {
                $times = $this->select_time_fields($this->group_id, $types[$d], "0");
                $nets = $this->select_net_fields($this->group_id, $types[$d], "0");
                $group_name = $this->get_group_name($this->group_id);
                $tables = array_merge($this->get_tables($group_name, $nets, $times, $types[$d]), $tables);
            }

            for($t = 0;$t < count($tables);$t++) {
				// Construction des requêtes de suppression des données
                unset($day_w, $day_d, $day_h, $_table);
                $_table = preg_replace('/\//', "_", $tables[$t]);
                $query = "delete from $_table";
                if (preg_match("/month$/", $_table)) {
                    $month_w = getmonth($off_month);
                    $query .= " where month <= " . $month_w ;
                }
                if (preg_match("/week$/", $_table)) {
                    $week_w = getweek($off_week);
                    $query .= " where week <= " . $week_w ;
                }
                if (preg_match("/day$/", $_table)) {
                    $day_d = getDay($off_day);
                    $query .= " where day <= " . $day_d ;
                }
                if (preg_match("/month_bh$/", $_table)) {
                    $month_w = getmonth($off_month);
                    $query .= " where month_bh <= " . $month_w ;
                }
                if (preg_match("/week_bh$/", $_table)) {
                    $week_w = getweek($off_week);
                    $query .= " where week_bh <= " . $week_w ;
                }
                if (preg_match("/day_bh$/", $_table)) {
                    $day_d = getDay($off_day);
                    $query .= " where day_bh <= " . $day_d ;
                }
                if (preg_match("/hour$/", $_table)) {
                    $day_h = getDay($off_hour);
                    $query .= " where day <= " . $day_h ;
                }
				$res = pg_query($this->database_connection, $query);
                print pg_affected_rows($res) . "=" . $query . "<br>";
            }
        }

        /**
         * met à jour les tables sys_definition_group_table(_network|_time), en remettant les deploy_status à 0 (valeur neutre)
         */
        function update_sys_tables($gr, $action)
        {
            foreach($this->types as $group => $ope) {
                foreach ($ope as $data_type => $todo) {
                    // echo "todo = ".print_r($todo)."<br>";
                    if ($group == $gr)
                        $to_update[] = $group . "/" . preg_replace("/_deploy_status/", "", $data_type);
                }
            }
            $to_update = array_unique($to_update);
            for($t = 0;$t < count($to_update);$t++) {
                unset($queries);
                $infos = explode("/", $to_update[$t]);
                $group_id = $this->get_group_id($infos[0]);
                $queries[] = "update sys_definition_group_table set " . $infos[1] . "_deploy_status='0'
                                where edw_group_table='$infos[0]'";
                switch ($action) {
                    case "1":
                        $queries[] = "update sys_definition_group_table set " . $infos[1] . "_deploy_status='0'
                                        where edw_group_table='$infos[0]'";
                        $queries[] = "update sys_definition_group_table_time set deploy_status='0'
                                        where id_group_table='$group_id' and data_type='$infos[1]'";
                        $queries[] = "update sys_definition_group_table_network set deploy_status='0'
                                        where id_group_table='$group_id' and data_type='$infos[1]'";
                        break;
                    case "2":
                        $queries[] = "delete from sys_definition_group_table
                                        where edw_group_table='$infos[0]' ";
                        $queries[] = "delete from sys_definition_group_table_time
                                        where id_group_table='$group_id' and data_type='$infos[1]'";
                        $queries[] = "delete from sys_definition_group_table_network
                                        where id_group_table='$group_id' and data_type='$infos[1]'";
                        // echo "delete from sys_definition_group_table_network
                        // where id_group_table='$group_id' and data_type='$infos[1]'";
                        break;
                }
                // suppression des doublons
				if(!is_array( $this->requetes[$infos[0]] ) )
					$this->requetes[$infos[0]] = array();
                $this->requetes[$infos[0]] = array_merge($this->requetes[$infos[0]], $queries);
                // $this->requetes[$infos[0]]=array_unique(array_merge($this->requetes[$infos[0]],$queries));
            }
        }

		//retourne l'offset_day, l'history_day, l'history_week et l'history_hour pris dans sys_global_parameters ou bien dans sys_definition_history
		function get_history()
		{
			// Liste des ta actives
			$lst_ta = getTaList(" and primaire = 1");
			$cpt = 0;

			foreach($lst_ta as $k=>$v){
			$parameters[$cpt] = 'history_'.$k;
			$cpt++;
			$nb_parameters++;
			}

			// On récupère les paramètres présents ds sys_definition_history

			$query = "select ta,duration from sys_definition_history where family = '".$this->family."'";
			$res = pg_query($this->database_connection, $query);

			// On récupère les paramètres présents ds sys_gobal_parameters
			$q = "select parameters,value from sys_global_parameters where parameters='".$parameters[0]."'
				 or parameters='".$parameters[1]."'
				 or parameters='".$parameters[2]."'
				 or parameters='".$parameters[3]."'
				 ";
			$r = pg_query($this->database_connection, $q);
			unset($offsets);

			$offsets["offset_day"] = get_sys_global_parameters("offset_day");
			// On recherche d'abord les paramètres dans la table sys_defintion_history
			while($row = pg_fetch_array($res))
			{
				if(($row[1]!=null)or($row[1]!=''))
					 $offsets['history_'.$row[0]] = $row[1];
			}

			// On récupère les paramètres non définis dans sys_defintion_history

			while($row = pg_fetch_array($r))
			{
						$trouve = false;
						$cpt = 0;
						while(($cpt<=$nb_parameters)and($trouve==false))
						{
						//Si le paramètre n'est pas présent dans sys_definition_history, on récupère les paramètres dans sys_global_parameters
							if(isset($offsets[$row[0]]))
									$trouve = true;
							else
									$offsets[$row[0]] = $row[1];
							$cpt++;
						}

			}
			return $offsets;
		}

        function get_fields_raw($group_table)
        {
            $query = "select distinct edw_field_name, edw_field_type
                        from sys_field_reference
                        where edw_group_table='$group_table' and new_field='0'";
            $res = pg_query($this->database_connection, $query);
            while ($row = pg_fetch_array($res))
            $fields[$row[0]] = $row[1];
            return $fields;
        }

        function get_fields_kpi($group_table, $mixed)
        {
            if ($mixed == 0) {
                $num_den = "total";
                $group = $group_table;
            } else {
                $num_den = "mixed";
                $group = "edw_mixed";
            }
            $query = "select kpi_name,kpi_type
                        from sys_definition_kpi
                        where edw_group_table='$group' and numerator_denominator='$num_den'
                        and new_field='0'";
            $res = pg_query($this->database_connection, $query);
            while ($row = pg_fetch_array($res))
            $fields[$row[0]] = $row[1];
            return $fields;
        }

		/* Retourne les adv_kpi appartenant au groupe $groupe_table
		 * Le groupe est connu en interrogeant les tables "sys_field_reference" et "sys_definition_kpi"
		 * @param string : un groupe de tables (ex : edw_omc_gsm_axe1)
		 * @return array : un tableau associatif entre les champs adv_kpi_name et field_type de la table "sys_definition_adv_kpi"
		 */
		function get_fields_adv_kpi($group_table)
		{
		$query="select adv_kpi_name,field_type from sys_definition_kpi t0,sys_definition_adv_kpi t1
				where indicator_id=internal_id and edw_group_table='$group_table' and t1.new_field='0'
				union
				select adv_kpi_name,field_type from sys_field_reference t0,sys_definition_adv_kpi t1
				where indicator_id=internal_id and edw_group_table='$group_table' and t1.new_field='0'";
		$res = pg_query($this->database_connection, $query);
        while ($row = pg_fetch_array($res))
			$fields[$row[0]] = $row[1];
		return $fields;
		}


        /**
         * fonction d'affichage
         * 27/08/2012 BBX
         * BZ 28639 : utilisation de la classe Database pour éxécuter les requêtes
         * Avis au merger : il est préférable de prendre la fonction entière
         */
        function display($echo)
        {
            $db = $this->database;
            if(!is_object($this->database)) {
                $db = Database::getConnection($this->product);
            }
            
            if (count($this->types) > 0) 
            {            
                if ($echo == "1") {
                    echo "<table border=1 width=\"1000\">
                    <tr><td align=\"center\">group_table</td>
                    <td align=\"center\">opération</td>
                    <td align=\"center\">requêtes</td></tr>";
                }

                foreach($this->types as $group => $ope)
                {
                    $this->requetes[$group] = array_unique($this->requetes[$group]);
                    foreach ($this->requetes[$group] as $nb_requete => $requete)
                    {
                        if ($echo == "1") {
                            foreach ($ope as $data_type => $todo) {
                                echo $data_type . " => " . $todo . "<br>";
                                echo "</td><td>";
                            }
                            echo "</td><td>";
                        }

                        if(!empty($requete)) {
                            if($echo == "1") echo $requete . "<br>";
                            $db->execute($requete);
                        } 
                    }
                    if($echo == "1") echo "</td></tr>";
                }
                if($echo == "1") echo "</table>";            
            }
        }
    }

    ?>
