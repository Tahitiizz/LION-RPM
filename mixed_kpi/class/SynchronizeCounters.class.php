<?php
/**
 *
 *	22/04/2010 NSE bz 15058 : on affiche [FAMILY LABEL], [RAW] lors de la confirmation des compteurs à mettre à jour
 *      06/05/2010 MPR bz 15273 : Synchronisation non fonctionnelle lorsque la formule contient des $
 */
/*
 * Description of SynchronizeCounters
 * Classe de Synchronization des compteurs Mixed KPI par rapport
 * aux produits parents
 *
 * @author m.peignier
 * @since cb_v5.0.2.12
 * @date
 */
set_time_limit(3600);

class SynchronizeCounters
{
    /**
     * Famille du produit Mixed KPI concernée
     * @var string
     * @access private
     */
    private $idFamily = "";
    
    /**
     * Id du produit Mixed KPI
     * @var <type>
     * @access private
     */
    private $_id;

    /**
     * Object DataBaseConnection : Connexion à la base de données
     * @var object
     */
    private $_db;

    /**
     * Tableau contenant l'ensemble des infos des compteurs du produit MixedKpi
     * @var array
     */
    private $raw_mixed_kpi = array();

    /**
     * Tableau contenant l'ensemble des infos des compteurs des Produits Parents
     * @var array
     * @access private
     */
    private $raw_parent_products = array();

    /**
     * Liste des compteurs Mixed KPI
     * @var array
     * @access private
     */
    private $lst_raws = array();

    /**
     * Liste des produits
     * @var array
     * @access private
     */
    private $products = array();

    /**
     * Tableau contenant l'ensemble des compteurs à synchroniser
     * @var array
     * @access private
     */
    private $raw_to_synchro = array();

    /**
     * Table temporaire où on enregistre les données en attendant la confirmation du User (via IHM)
     * @var string
     * @access private
     */
    private $table_temp = "sys_field_reference_synchro_mk";

    /**
     * Tableau contenant tous les compteurs du produit MixedKPI
     * @var array(id, nms_field_name, edw_field_name)
     * @access private
     */
    private $AllCodesCounters_mixedKpi = array();
    
    /**
     * Tableau contenant tous les compteurs
     * @var array(id, nms_field_name, edw_field_name)
     * @access private
     */
    private $AllCodesCounters_parent = array();

     /**
     * Fichier temporaire permettant d'insérer les données dans la table temporaire
     * @var array(id, nms_field_name, edw_field_name)
     * @access private
     */
    private $file_temp;

    /**
     * Constructeur de la classe
     * @access public
     */
    public function  __construct( $family )
    {
        $this->idFamily = $family;
        $this->_id = ProductModel::getIdMixedKpi();
        $this->_db = Database::getConnection($this->_id);
        $this->file_temp = REP_PHYSIQUE_NIVEAU_0.'upload/temp_file_to_copy_synchro_mk.csv';
        $this->MixedKpiModel = new MixedKpiModel();

    } // End function __construct()

    /**
     * Fonction qui créée la table temporaire
     * @access private
     */
    private function createTableTemp()
    {
        // Suppression de la table temporaire si elle existe
        $query = "DROP TABLE IF EXISTS {$this->table_temp}";
        $this->_db->execute($query);

        // Création de la table temporaire
        $query = "CREATE TABLE {$this->table_temp} (
                        edw_field_name_label text,
                        edw_agregation_function text,
                        edw_agregation_formula text,
                        comment text,
                        id_ligne text
                  )
                  ";
        $this->_db->execute($query);
    } // End function createTableTemp
    
   /**
     * Fonction qui récupère les compteurs du produit Mixed KPI
     * @access private
     */
    private function getRawMixedKPI()
    {
       $this->raw_mixed_kpi = $this->MixedKpiModel->getInfosCountersMixedKpibyFamily( $this->idFamily );
       $products = array_keys($this->raw_mixed_kpi);
       foreach($products as $prod)
       {
          if($prod !== null and $prod !== "")
                $this->products[] = $prod;
       }
       // Récupration de la liste des compteurs par produit
       foreach($this->products as $idProduct)
       {
           foreach($this->raw_mixed_kpi[$idProduct] as $lst_raws)
           {
               $this->lst_raws[$idProduct][] = $lst_raws['id_ligne_parent'];
           }
       }

    } // End function getRawMixedKPI()

    /**
     * Fonction qui récupere tous les compteurs du produit Mixed KPI et des produits Parents
     * @access private
     */
    private function getAllCodesCounters()
    {
         // 05/08/2010 MPR
         //       - bz 14953 : On exclute également le compteur capture_duration_real

        $select = "SELECT DISTINCT  f.old_id_ligne";
        // 19/03/2014 GFS - Bug 39160 - [SUP][5.3.1.11][#41596][Zain KW] : Problem with ByPass counters on mixed kpi
        $query = ", f.edw_field_name, f.nms_field_name,g.family
                        FROM sys_field_reference f, sys_definition_group_table  g
                            WHERE on_off = 1
                                -- AND f.visible = 1
                                AND f.edw_group_table = g.edw_group_table
                                AND lower(nms_field_name) NOT IN ('capture_duration_expected','capture_duration','capture_duration_real')";

        $query_mixed_kpi = $select.$query . " AND family = '{$this->idFamily}'";

        // Produit Mixed KPI
        $result = $this->_db->getAll($query_mixed_kpi);

        foreach($result as $row )
        {
            $this->AllCodesCounters_mixedKpi["nms_field_name"][] = $row["nms_field_name"];
            $this->AllCodesCounters_mixedKpi["id_ligne_parent"][]= $row["old_id_ligne"];
            $this->AllCodesCounters_mixedKpi["edw_field_name"][] = $row["edw_field_name"];
            $this->AllCodesCounters_mixedKpi["family"][]         = $row["family"];
        }

        $select = "SELECT DISTINCT  f.id_ligne";
        // On boucle sur tous les produits
        foreach( $this->products as $idProduct )
        {
            $sub_query = "";
            if( count($this->lst_raws[$idProduct]) > 0 )
            {
                $sub_query .= " AND f.id_ligne IN ('".implode("','", $this->lst_raws[$idProduct])."')";
            }

            // Produit Parent
            $db = Database::getConnection($idProduct);
            $result_parent = $db->getAll($select.$query.$sub_query);

            // Récupération des codes de tous les compteurs
            foreach($result_parent as $row_parent)
            {
                $this->AllCodesCounters_parent[$idProduct]["nms_field_name"][] = $row_parent["nms_field_name"];
                $this->AllCodesCounters_parent[$idProduct]["id_ligne"][]       = $row_parent["id_ligne"];
                $this->AllCodesCounters_parent[$idProduct]["edw_field_name"][] = $row_parent["edw_field_name"];
                $this->AllCodesCounters_parent[$idProduct]["family"][]         = $row_parent["family"];
            }
        }
    } // End function getAllCodesCounters

    /**
     * Fonction qui récupÃ¨re les infos des compteurs des produits parents
     * @access private
     */
    private function getRawByProductsParents()
    {
        foreach($this->products as $idProduct)
        {
            $this->raw_parent_products[$idProduct] = $this->MixedKpiModel->getInfosCountersProductParent($idProduct, $this->lst_raws[$idProduct] );
        }
    } // End function getRawByProductsParents()

    /**
     * Fonction identifie les comtpeurs ayant des différences sur les labels / comment / formule
     * @access private
     */
    private function checkInfos()
    {

        foreach($this->products as $idProduct)
        {
            foreach($this->raw_mixed_kpi[$idProduct] as $key => $val )
            {
                $find = false;
                $change_function = false;
                // Comparaison des labels
                $prefix = MixedKpiModel::getPrefix($idProduct, $val['family'], true);
                
                // 11/08/2011 BBX
                // Il manque une relation entre les clés du tableau provenant du Mixed KPI
                // et les clés du tableau provenant du parent.
                // BZ 23077
                $parentKey = $key;
                foreach($this->raw_parent_products[$idProduct] as $pKey => $pVal) {
                    if($pVal['id_ligne'] == $val['id_ligne_parent']) {
                        $parentKey = $pKey;
                        break;
                    }
                }                
                
                if( !$this->compareLabels( $val['label'], $prefix.$this->raw_parent_products[$idProduct][$parentKey]['label'] ) )
                {
                    // On corrige directement le label sans oublier le prÃ©fix (trigramProd + " " + familyCode + " "
                    $old = $val['label'];
                    $val['label'] = $prefix.$this->raw_parent_products[$idProduct][$key]['label'];
                    $find = true;
                }

                // Comparaison des comment
                if( !$this->compareComments( $val['comment'], $this->raw_parent_products[$idProduct][$parentKey]['comment'] ) )
                {
                    // On corrige directement le comment
                    $val['comment'] = $this->raw_parent_products[$idProduct][$parentKey]['comment'];
                    $find = true;
                }

                // Comparaison des fonctions
                if( !$this->compareFonctions( $val['fonction'], $this->raw_parent_products[$idProduct][$parentKey]['fonction'] ) )
                {
                    // On corrige directement le comment
                    $val['fonction'] = $this->raw_parent_products[$idProduct][$parentKey]['fonction'];
                    $find = true;
                }

                // Comparaison des formules
                $formule_mixedKpi   = $this->new_formulas_mixedKpi[$idProduct][$val['id_ligne_parent']]['formule'];
                $formule_parent     = $this->new_formulas_parent[$idProduct][$val['id_ligne_parent']]['formule'];

                if( !$this->compareFormules( $formule_mixedKpi , $formule_parent ) )
                {
                    // Cas 1 :  La formule est différente mais contient les mÃªmes compteurs
                    //          On remplace donc la formule MK par celle du parent avant de la reformater
                    //          Ajout d'un @ pour le array_diff - Cas oÃ¹ le compteur présent dans la formule n'existe pas

                    $tab = @array_diff($this->new_formulas_mixedKpi[$idProduct][$val['id_ligne_parent']], $this->new_formulas_mixedKpi[$idProduct][$val['id_ligne_parent']]);
                    if( count($tab) == 0 )
                    {
                        $this->new_formulas_mixedKpi[$idProduct][$val['id_ligne_parent']]['formule'] = $this->new_formulas_parent[$idProduct][$val['id_ligne_parent']]['formule'];
                    }

                    // Correction finale de la formule
                    $val['formule'] = $this->correctFormula( $val, $idProduct );
                    $find = true;
                }
                // On enregistre les compteurs Ã  synchroniser
                if( $find )
                { 
					// 22/04/2010 NSE bz 15058 : on aura besoin de l'idProduct pour l'affichage, on l'ajoute donc au tableau
					$val['idProduct'] = $idProduct;
                    $this->raw_to_synchro[] = $val;
                }
            }
        }
    } // End function checkInfos()

    /**
     * Fonction qui corrige la formule du compteur
     * @param array $tab : tableau contenant l'ensemble des données du compteur
     * @param integer $idProduct : id du produit parent
     * @return string $formule_finale : Formule mise Ã  jour
     */
    private function correctFormula( $tab, $idProduct )
    {
        // Formule du compteur sur Mixed KPI contenant nms_field_name Ã  la place de edw_field_name
        $old_formule = $this->new_formulas_mixedKpi[$idProduct][$tab['id_ligne_parent']]['formule'];

        // Formule du compteur sur Produit Parent contenant nms_field_name Ã  la place de edw_field_name
        $new_formule = $this->new_formulas_parent[$idProduct][$tab['id_ligne_parent']]['formule'];

        $formule_with_nms_field_name = $new_formule;
        //__debug( $formule_with_nms_field_name, "FORMULE PRE");
        if( $old_formule != $new_formule)
        {
            //__debug("formule différente => $old_formule != $new_formule");
            $formule_with_nms_field_name = $this->raw_mixed_kpi[$idProduct][$tab['id_ligne_parent']]['formule'];

        
            // Correction de la formule (nms_field_name toujours présent)
            $formule_with_nms_field_name = preg_replace("/".$old_formule."/",$new_formule, $formule_with_nms_field_name);
        }
        // Recherche de edw_field_name par rapport au nouveau nms_field_name
        $formule_finale = $formule_with_nms_field_name;
        

        // On boucle sur tous les compteurs identifiés dans la formule
        if( count($this->new_formulas_mixedKpi[$idProduct][$tab['id_ligne_parent']]['id']) > 0 ){
            foreach($this->new_formulas_mixedKpi[$idProduct][$tab['id_ligne_parent']]['id'] as $k=>$cpt_in_formule)
            {
                $find = false;
                $i = 0;
                $nb_raws = count($this->AllCodesCounters_mixedKpi["nms_field_name"]);

                // On boucle sur tous les compteurs du produit Mixed KPI
                while( $i< count($nb_raws) && !$find )
                {
                    $nms_field_name = $this->AllCodesCounters_mixedKpi["nms_field_name"][$i];
                    $edw_field_name = $this->AllCodesCounters_mixedKpi["edw_field_name"][$i];
                    // Si le compteur est trouvé, on remplace nms_field_name par edw_field_name du mÃªme compteur
                    if( $cpt_in_formule == strtolower($nms_field_name) && $nms_field_name != $edw_field_name )
                    {
                        $find = true;
                        $formule_finale = preg_replace("/".$cpt_in_formule."/",$edw_field_name, $formule_finale);
                    }
                    $i++;
                }
            }
        }
        return $formule_finale;
    } // End function correctFomula()

    /**
     * Fonction qui enregistre les données des compteurs Ã  synchroniser dans une table temporaire
     * @access private
     */
    private function saveRawsToSynchro()
    {
        foreach($this->raw_to_synchro as $raws)
        {
            $label      = str_replace("'","\'",$raws['label']);
            $comment    = str_replace("'","\'",$raws['comment']);
            $id_ligne   = str_replace("'","\'",$raws['id_ligne']);
            // maj 06/05/2010 - MPR : Correction du BZ15273 - Synchronisation non fonctionnelle lorsque la formule contient des $
            $formule    = str_replace(array("'","$"),array("\'","\\$"),$raws['formule']);
            $fonction    = str_replace("'","\'",$raws['fonction']);
            
            $cmd = 'echo "'.$id_ligne.';'.$label.';'.$comment.';'.$formule.';'.$fonction.'" >> '.$this->file_temp;
            exec($cmd);
        }
        // 19/03/2014 GFS - Bug 39160 - [SUP][5.3.1.11][#41596][Zain KW] : Problem with ByPass counters on mixed kpi
        if (count($this->raw_to_synchro) > 0) {
	        $query = "COPY {$this->table_temp}(id_ligne, edw_field_name_label, comment,edw_agregation_formula,edw_agregation_function)
	                        FROM '{$this->file_temp}' WITH DELIMITER ';' NULL ''";
	        $this->_db->execute($query);
	        exec("rm -f ".$this->file_temp);
        }

    } // End Function saveRawsToSynchro()

    /**
     * Fonction qui détermine si les labels sont différents
     * @param string $label_mixedKpi
     * @param string $label_parentProducts
     * @access private
     * @return boolean (true : labels identiques / false : labels différents)
     */
    private function compareLabels($label_mixedKpi, $label_parentProduct )
    {
        // Label du cpt MK = Trigramme produit
        //                  + " "
        //                  + famille du cpt du produit parent
        //                  + " "
        //                  + label du cpt du produit parent
                    
        if( $label_parentProduct == $label_mixedKpi )
        {
            return true;
        }
        return false;
    } // End function compareLabels()

    /**
     * Fonction qui détermine si les commentaires sont différents
     * @access private
     * @param string $comment_mixedKpi
     * @param string $comment_parentProducts
     * @return boolean (true : commentaires identiques / false : commentaires différents)
     */
    private function compareComments($comment_mixedKpi, $comment_parentProducts)
    {
        if( $comment_mixedKpi == $comment_parentProducts )
        {
            return true;
        }
        return false;
    } // End function compareComments()

    /**
     * Fonction qui détermine si les commentaires sont différents
     * @access private
     * @param string $comment_mixedKpi
     * @param string $comment_parentProducts
     * @return boolean (true : commentaires identiques / false : commentaires différents)
     */
    private function compareFonctions($fonction_mixedKpi, $fonction_parentProducts)
    {
        if( $fonction_mixedKpi == $fonction_parentProducts )
        {
            return true;
        }
        return false;
    } // End function compareComments()

    /**
     * Fonction qui compare la formule du compteur sur le produit Mixed KPI et sur le produit parent
     * @access private
     * @param string $formule_mixedKpi
     * @param string $formule_parentProduct
     * @return boolean (true : formules identiques / false : formules différentes)
     */
    private function compareFormules($formule_mixedKpi, $formule_parentProduct)
    {
        if( $formule_mixedKpi == $formule_parentProduct )
        {
            return true;
        }
        return false;
    } // End function compareFormules()

    /**
     * Fonction qui reformate les formules sur le produit Mixed KPI et sur les produits parents
     * afin d'avoir une correspondance qui est la suivante : nms_field_name(MK) = prefix+"_"+edw_field_name(Parent)
     * @access private
     */
    private function formatFormulas()
    {
        foreach( $this->products as $idProduct )
        {

            foreach( $this->raw_mixed_kpi[$idProduct] as $key => $val)
            {
                 // Cas Produit Mixed KPI
                foreach($this->AllCodesCounters_mixedKpi["edw_field_name"] as  $k=>$v )
                {
                    $cpt = 0;
                    $formule = strtolower($val['formule']);

                    // On remplace edw_field_name dans la formule par nms_field_name afin d'avoir la correspondance avec le produit parent
                    $val['formule'] = preg_replace ('/'.strtolower($v).'/', strtolower($this->AllCodesCounters_mixedKpi["nms_field_name"][$k]), $formule, -1 , $cpt );

                    if( $cpt > 0 ){
                        $this->new_formulas_mixedKpi[$idProduct][ $val['id_ligne_parent'] ]['formule'] = $formule;
                        // On enregistre la valeur du compteur (nms_field_name) qui a changé (nécessaire pour reformater la formule)
                        $this->new_formulas_mixedKpi[$idProduct][ $val['id_ligne_parent'] ]['id'][] = strtolower($this->AllCodesCounters_mixedKpi["nms_field_name"][$k]);
                    }
                }
                

                $formule_parent = strtolower($this->raw_parent_products[$idProduct][$key]['formule']);
                
                foreach( $this->AllCodesCounters_parent[$idProduct]["edw_field_name"] as $k=>$v ){
                    // Cas Produit Parent
                    // On vérifie la présence de n'importe quel compteur du produit dans la formule
                    $old_formule = $formule_parent;
                    // Définition du préfix -> trigramme du produit + "_" + code famille + "_"
                    $prefix = MixedKpiModel::getPrefix($idProduct, $this->AllCodesCounters_parent[$idProduct]["family"][$k]);
                    // On remplace edw_field_name par predix + "_" + edw_field_name afin d'avoir la correspondance avec le produit Mixed KPI
                    $pattern = '/([^[:alnum:]_]|^)'.strtolower($v).'([^[:alnum:]_]|$)/';
                    $replace = '\\1'.strtolower($prefix.$v).'\\2';
                    
                    $formule_parent = preg_replace($pattern, $replace, $formule_parent );

                    
                     if( $old_formule != $formule_parent )
                     {
                        $this->new_formulas_parent[$idProduct][ $val['id_ligne_parent'] ]['formule'] = $formule_parent;
                        // On enregistre la valeur du compteur (prefix + edw_field_name) qui a changé (nécessaire pour reformater la formule)
                        $this->new_formulas_parent[$idProduct][ $val['id_ligne_parent'] ]['id'][]      = strtolower($prefix.$v);
                     }
                }
            }
        }
    } // End function formatFormulas()

    /**
     * Récupére la liste des compteurs à supprimer
     * @return array
     */
    public function getCountersToDelete()
    {
        // Liste des comteurs supprimés
        $deletedCounters = array();
        // Connexion à la base de données du Mixed KPI
        $dbMK = Database::getConnection(ProductModel::getIdMixedKpi());
        // Récupération des compteurs du Mixed KPI liés à des compteurs produits
        $query = "SELECT
                r.id_ligne, r.sfr_sdp_id, r.old_id_ligne, r.sfr_product_family, r.edw_field_name_label
            FROM sys_field_reference r, sys_definition_gt_axe a
            WHERE r.id_group_table = a.id_group_table
            AND r.sfr_sdp_id IS NOT NULL
            AND r.old_id_ligne IS NOT NULL
            AND r.sfr_product_family IS NOT NULL
            AND a.family = '".$this->idFamily."'";
        $result = $dbMK->execute($query);
        // Parcours des compteurs
        while($row = $dbMK->getQueryResults($result,1)) {
            // Connexion au produit source
            $dbSource = Database::getConnection($row['sfr_sdp_id']);
            // Statut du compteur sur le produit source
            $statut = (int)$dbSource->getOne("SELECT r.on_off
                FROM sys_field_reference r, sys_definition_gt_axe a
                WHERE r.id_group_table = a.id_group_table
                AND a.family = '".$row['sfr_product_family']."'
                AND r.id_ligne = '".$row['old_id_ligne']."'");
            // Si le compteur a été désactivé, on supprime le compteur
            if(!$statut) {
                $deletedCounters[] = $row;
            }
        }
        // Retour de la liste supprimée
        return $deletedCounters;
    }

    /**
     * Supprime les compteurs du MixedKpi dont les compteurs produits source
     * ont été désactivés
     * @return array $deletedCounters
     */
    public function cleanCounters()
    {
        // Liste des comteurs supprimés
        $deletedCounters = array();
        // RawModel
        $rawModel = new RawModel();
        // Parcours des compteurs
        foreach($this->getCountersToDelete() as $row) {
                $rawModel->drop($row['id_ligne'], ProductModel::getIdMixedKpi());
                $deletedCounters[$row['sfr_sdp_id']] = $row['edw_field_name_label'];
        }
        // Retour de la liste supprimée
        return $deletedCounters;
    }

    /**
     * Fonction qui effectue la synchronisation finale
     * @return string (ok pour mise Ã  jour effectué / ko pour echec de la mise Ã  jour
     * @access public
     */
    public function confirmSynchro()
    {
        // Mise à jour des compteurs sur le produit Mixed KPI
        $query = "UPDATE sys_field_reference s
                  SET edw_field_name_label      = t0.new_label,
                      comment                   = t0.new_comment,
                      edw_agregation_formula    = t0.new_formula,
                      edw_agregation_function   = t0.new_fonction
                  FROM (
                        SELECT  id_ligne, edw_field_name_label as new_label,
                                comment as new_comment,
                                edw_agregation_formula as new_formula,
                                edw_agregation_function as new_fonction
                        FROM {$this->table_temp}

                  ) t0 WHERE s.id_ligne = t0.id_ligne
            ";

        $result = $this->_db->execute($query);

        if($result !== false)
        {
            $msg = "ok";
            // 03/01/2011 BBX
            // Nettoyage des compteurs + ajout au tracelog
            // BZ 20369
            $deletedCounters = $this->cleanCounters();
            if(!empty($deletedCounters)) {
                $message = __T('A_SETUP_MIXED_KPI_COUNTERS_HAVE_BEEN_DELETED');
                $message .= implode(", ",$deletedCounters);
                sys_log_ast('Warning', 'Trending&Aggregation', 'Mixed KPI', $message, 'support_1');
            }
        }
        else
        {
            $msg = $this->_db->getLastError();
        }
        
        // Suppression de la table temporaire si elle existe
        $query = "DROP TABLE IF EXISTS {$this->table_temp}";
        $this->_db->execute($query);
        return $msg;

    } // End function confirmSynchro()

    /**
     * Fonction qui prépare la synchronisation (Enregistrement des données dans table temporaire
     * @access public
     * @return string contenu de la prototype window (Affichage de la liste des compteurs Ã  synchroniser)
     */
    public function prepareSynchro()
    {
        // Création de la table temporaire permettant les mises Ã  jour
        $this->createTableTemp();
        
        // Récupration des compteurs Ã  synchroniser
        $this->getRawMixedKPI();

        // Récupération des infos des compteurs des produits parents
        $this->getRawByProductsParents();

        // Récupération de tous les codes (Mixed KPI + Produits parents)
        $this->getAllCodesCounters();

        // Formatage des formules
        $this->formatFormulas();

        // Comparaison des labels, comment et formules
        $this->checkInfos();

        // Enregistrement des compteurs à synchroniser dans une table temporaire
        $this->saveRawsToSynchro();

        $nb_raw = count($this->raw_to_synchro);

        // IHM - Défintion du contenu de la box
        if( $nb_raw > 0 )
        {
            $nb_raw = 0;
            foreach( $this->raw_to_synchro as $product=>$raws )
            {
                $nb_raw++;
				// 22/04/2010 NSE bz 15058
                // on récupère le préfixe pour le supprimer du label
				$prefix = MixedKpiModel::getPrefix($raws['idProduct'], $raws['family'], true);
				// on affiche [FAMILY LABEL], [RAW]
                $content.= "<tr><td class='alphacube_table'>".FamilyModel::getLabel($raws['family'],$raws['idProduct']).', '.preg_replace('/^'.$prefix.'/','',$raws['label'])."</td></tr>";
            }
            $content.= "</table>";
        
             // IHM - Entête de la Box
            $html = $nb_raw. " counters will be updated during the synchronization.<br />";
            $html.= "List of Counters to update:<br /><br /><table class='alphacube_table'>";
            $html.= $content;

            // 03/01/2011 BBX
            // On informe l'utilisateur de ce qui va se passer
            // BZ 20369
            $countersToDelete = $this->getCountersToDelete();
            if(!empty($countersToDelete)) {
                $html .= "<br /><div class='errorMsg'>";
                $html .= __T('A_SETUP_MIXED_KPI_COUNTERS_WILL_BE_REMOVED');
                foreach ($countersToDelete as $p => $row)
                {
                    list($trigram,$family,$counter) = explode(' ',$row['edw_field_name_label']);
                    $ProductModel = new ProductModel(ProductModel::getIdProductFromTrigram($trigram));
                    $FamilyModel =
                    $html .= "<li>".FamilyModel::getLabel($family, $p);
                    $html .= ", ".$counter."</li>";
        }
            }
            // Fin BZ 20369
        }
        else
        {
            // IHM - Si rien à synchroniser on retourne directement 0
            $html = "0";
        }
           

        return $html;
    } // End function getInfosCounters()

    public function  __destruct() {
        unset($this);
    }
} // End Classe SynchronizeCounters
?>
