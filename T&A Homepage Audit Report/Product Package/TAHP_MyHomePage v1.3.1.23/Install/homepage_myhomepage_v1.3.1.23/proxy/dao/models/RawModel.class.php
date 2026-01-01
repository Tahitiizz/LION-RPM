<?php
/**
 * Cette classe permet de manipuler les Raw Counters
 * $Author: s.pigeard $
 * $Date: 2011-12-27 17:05:21 +0100 (mar., 27 dÃ©c. 2011) $
 * $Revision: 38607 $
 *
 * @version 5.1.0.03
 *
 * 04/08/2010 OJT : Correction bz17175
 * 16:12 13/10/2010 SCT : BZ 18427 => Désactivation de compteur utilisé pour la BH possible
 *		+ ajout de la méthode isRawLockedBh(...)
 */
?>
<?
/**
 * @cb5.0.2.14
 *      maj 07/05/2010 MPR - Correction du bz 15316 : Ajout de la condition  AND sdk_sdp_id = idProdParent
 *
 *      12/05/2010 BBX
 *          - BZ 15316 : annulation des modification de MPR non fonctionnelles.
 *          - BZ 15291 : on conserve cependant la condition sur le produit parent pour la requête qui récupère les trigrammes.
 *
 *	23/11/2010 MMT - bz 19294 ne pas effectuer de remplacement dans renameCounters si les valeures oldCode ou OldLabel sont vides
 */
?>
<?class RawModel extends RawKpiModel
{
	/**
	 * Message d'erreur
	 * @var string
	 */
	private $_msgErrors = '';

	/**
	 * Constructeur
	 *
	 * @author NSE
	 */
	public function __construct ()
	{
		$this->_type1 = 'raw';
		$this->_type2 = 'counter';
		$this->_rawkpi_table = self::RAW_TABLE;
		$this->_fieldName = 'edw_field_name';
	} // End function __construct

	/**
	 * Retourne le nom du Raw é partir de son Id
	 *
	 * @param string $id : l'identifiant du raw
	 * @param DatabaseConnection $database : intance de DatabaseConnection
	 * @return string
	*/
	public function getNameFromId ( $id, $database )
	{
		$query = "SELECT edw_field_name FROM ".self::RAW_TABLE." WHERE id_ligne='{$id}'";
		return $database->getOne($query);
	}

	/**
	 * Retourne le label du Raw é partir de son Id
	 *
	 * @param string $id : l'identifiant du raw
	 * @param DatabaseConnection $database : intance de DatabaseConnection
	 * @return string
	*/
	public function getLabelFromId ( $id, $database )
	{
		$query = "SELECT edw_field_name_label FROM ".self::RAW_TABLE." WHERE id_ligne='{$id}'";
		return $database->getOne($query);
	}

	/**
	 * Retourne la liste des Kpi dans lesquels le raw est utilisé
	 *
	 * @param string $id : l'identifiant du raw
	 * @param string $edw_group_table : edw_group_table dans laquelle le compteur se trouve
	 * @param DatabaseConnection $database : intance de DatabaseConnection
	 * @return array
	*/
	public function getKpiListWith ( $id, $edw_group_table, $database )
	{
		// la requete suivante retourne la liste des adv_kpi utilises dans des alarmes
		$query="
			SELECT
				sdk.id_ligne
			FROM
				".self::RAW_TABLE." AS sfr,
				".self::KPI_TABLE." AS sdk
			WHERE
				sdk.edw_group_table = sfr.edw_group_table
				AND lower(kpi_formula) ~* ('([^[:alnum:]_]|^)'|| sfr.edw_field_name ||'([^[:alnum:]_]|$)')::TEXT
				AND sfr.id_ligne='{$id}'
				AND sfr.edw_group_table = '{$edw_group_table}'
			";
		return $database->getAll($query);
	}

	/**
	* Fonction qui retourne le group table du kpi en fonction de son id
	*
	 * string $id : l'identifiant du raw
	 * @param DatabaseConnection $database : intance de DatabaseConnection
	 * @return string
	*/
	public function getGroupTableFromId( $id, $database ){
		$query = "SELECT edw_group_table FROM ".self::RAW_TABLE." WHERE id_ligne='{$id}'";
		return $database->getOne($query);

	}

	/**
         * Supprime définitivement un compteur dans T&A
         * Remarque : il faudra cependant passer un Clean Table Structure
         * afin de pouvoir supprimer les compteurs dans les tables de données
         * et dans "sys_field_reference".
         * Dans le cadre du BZ 18510
         * @author BBX
         * @param string $rawId
         * @param integer $productId
         */
        public function drop( $rawId, $productId )
        {
            // Connexion au produit courant
            $database = DataBase::getConnection($productId);

            // On récupère le nom interne du compteur
            $rawName = $this->getNameFromId($rawId, $database);

            // On récupère le groupe et la famille du compteur
            $groupTable = $this->getGroupTableFromId($rawId, $database);
            $family = $database->getOne("SELECT family FROM sys_definition_group_table WHERE edw_group_table = '$groupTable'");

            // On passe le compteur à "new_field" = 2
            $this->setToDrop($rawId, $database);

            // On désactive les Kpis utilisant ce compteur
            foreach($this->getKpiListWith($rawId, $groupTable, $database) as $kpi) {
                $query = "UPDATE sys_definition_kpi
                    SET on_off = 0
                    WHERE id_ligne = '{$kpi['id_ligne']}'";
                $database->execute($query);
            }

            // Récupération de l'id du produit courant
            $myId = ($productId == 0) ? ProductModel::getProductId() : $productId;

            // Mise à jour de sys_data_range_style,
            // sys_export_raw_kpi_data, sys_pauto_config
            $query .= "DELETE FROM sys_data_range_style
                WHERE id_element = '$rawId';\n";
            $query .= "DELETE FROM sys_export_raw_kpi_data
                WHERE raw_kpi_id = '$rawId'
                AND raw_kpi_type = 'raw';\n";
            $query .= "DELETE FROM sys_pauto_config
                WHERE id_elem = '$rawId'
                AND id_product = $myId;\n";
            // Exécution des mises à jour
            $database->execute($query);

            // Mise à jour de sys_definition_time_bh_formula
            $query = "DELETE FROM sys_definition_time_bh_formula
                WHERE bh_indicator_name ILIKE '$rawName'
                AND bh_indicator_type ILIKE 'raw'";
            // Exécution des mises à jour
            $database->execute($query);

            // Mise à jour de sys_definition_alarm_static, sys_definition_alarm_dynamic,
            // sys_definition_alarm_top_worst, edw_alarm_detail
            $query = "DELETE FROM edw_alarm_detail
                WHERE id_result IN (
                        SELECT id_result FROM edw_alarm
                        WHERE id_alarm IN (

                                (SELECT alarm_id
                                FROM sys_definition_alarm_static
                                WHERE alarm_trigger_type = 'raw'
                                AND alarm_trigger_data_field ILIKE '$rawName'
                                AND family = '$family'
                                GROUP BY alarm_id)

                                UNION

                                (SELECT alarm_id
                                FROM sys_definition_alarm_dynamic
                                WHERE
                                        (alarm_field_type = 'raw'
                                        AND alarm_field ILIKE '$rawName'
                                        AND family = '$family')
                                OR
                                        (alarm_trigger_type = 'raw'
                                        AND alarm_trigger_data_field ILIKE '$rawName'
                                        AND family = '$family'))

                                UNION

                                (SELECT alarm_id
                                FROM sys_definition_alarm_top_worst
                                WHERE
                                        (list_sort_field_type = 'raw'
                                        AND list_sort_field ILIKE '$rawName'
                                        AND family = '$family')
                                OR
                                        (alarm_trigger_type = 'raw'
                                        AND alarm_trigger_data_field ILIKE '$rawName'
                                        AND family = '$family'))
                        )
                )
                AND trigger ILIKE '$rawName';\n";
            $query .= "DELETE FROM
                        sys_definition_alarm_static
                WHERE
                        (alarm_trigger_type = 'raw'
                        AND alarm_trigger_data_field ILIKE '$rawName'
                        AND family = '$family')
                OR
                        (additional_field_type = 'raw'
                        AND additional_field ILIKE '$rawName'
                        AND family = '$family');\n";
            $query .= "DELETE FROM
                        sys_definition_alarm_dynamic
                WHERE
                        (alarm_field_type = 'raw'
                        AND alarm_field ILIKE '$rawName'
                        AND family = '$family')
                OR
                        (additional_field_type = 'raw'
                        AND additional_field ILIKE '$rawName'
                        AND family = '$family')
                OR
                        (alarm_trigger_type = 'raw'
                        AND alarm_trigger_data_field ILIKE '$rawName'
                        AND family = '$family');\n";
            $query .= "DELETE FROM
                        sys_definition_alarm_top_worst
                WHERE
                        (list_sort_field_type = 'raw'
                        AND list_sort_field ILIKE '$rawName'
                        AND family = '$family')
                OR
                        (additional_field_type = 'raw'
                        AND additional_field ILIKE '$rawName'
                        AND family = '$family')
                OR
                        (alarm_trigger_type = 'raw'
                        AND alarm_trigger_data_field ILIKE '$rawName'
                        AND family = '$family');\n";
            // Exécution des mises à jour
            $database->execute($query);

            // Mise à jour de sys_aa_filter_kpi
            $query = "DELETE FROM sys_aa_filter_kpi
                WHERE saafk_type = 'raw'
                AND saafk_idkpi ILIKE '$rawName'
                AND saafk_family = '$family'";
            // Exécution des mises à jour
            $database->execute($query);

            // Connexion au master
            $masterId = ProductModel::getIdMaster();
            $database = Database::getConnection($masterId);

            // Mise à jour de sys_definition_selecteur
            $query = "UPDATE sys_definition_selecteur
                SET sds_sort_by = null
                WHERE sds_sort_by LIKE 'counter@{$rawId}@%';\n";
            $query .= "UPDATE sys_definition_selecteur
                SET sds_filter_id = null
                WHERE sds_filter_id LIKE 'counter@{$rawId}@%';\n";
            // Exécution des mises à jour
            $database->execute($query);

            // L'aplication concernée est-elle un slave ?
            if($myId != $masterId)
            {
                // Alors mise à jour de sys_pauto_config sur le master
                $query = "DELETE FROM sys_pauto_config
                    WHERE id_elem = '$rawId'
                    AND id_product = $myId";
                // Exécution des mises à jour
                $database->execute($query);
            }
        }

	/**
	* Fonction qui renomme un ou plusieurs compteurs et propage
	* la modification dans toute la base
	*
	* @param string : ancien code (ou début de l'ancien code)
	* @param string : va remplacer l'ancien code
	* @param string : ancien label (ou début de l'ancien label)
	* @param string : va remplacer l'ancien label
	* @param int : id produit
	* @return bool : résultat de l'éxécution
	*/
	public static function renameCounters($oldCode,$newCode,$oldLabel,$newLabel,$idProdMixedKpi=0,$idProdParent=0)
	{
                // Variable de controle
		$execCtrl = false;

                // MMT 23/11/2010 bz 19294
		//ne pas laisser le remplacement de chaines vide ou toutes les colonnes seront préfixés
		if(!empty($oldCode) && !empty($oldLabel)){
		// Connexion à la base de données du produit
		$database = Database::getConnection($idProdMixedKpi);

		// Récupération du module
		$query = "SELECT value
					FROM sys_global_parameters
					WHERE parameters IN ('old_module','module')
					ORDER BY parameters DESC
					LIMIT 1";
		$module = $database->getOne($query);

			// Variable de contrôle
		$execCtrl = true;

		// Longueur de la chaine
		$oldCodeLength = strlen($oldCode);

                // 12/05/2010 BBX
                // On n'utilise l'id du produit parent QUE pour la requête ci-dessous. BZ 15291
		// Récupération des compteurs concernés
		$query = "SELECT
		edw_field_name AS old_name,
		overlay(edw_field_name placing '{$newCode}' from 1 for {$oldCodeLength}) AS new_name
		FROM
			sys_field_reference
		WHERE
			edw_field_name ILIKE '{$oldCode}%'
                AND
                        sfr_sdp_id = {$idProdParent}";
		$newRawCodes = $database->getAll($query);

		// Démarrage de la transaction
		$database->execute('BEGIN');

		// Modification de la table edw_alarm_detail
		// Mise à jour de la colonne trigger
		$query = "UPDATE edw_alarm_detail
		SET trigger = overlay(trigger placing '{$newCode}' from 1 for {$oldCodeLength})
		WHERE trigger ILIKE '{$oldCode}%'";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);

		// Modification de la table forum_formula
		// Mise à jour de la colonne formula_equation
		$query = '';
                foreach($newRawCodes as $codes)
		{
			$query .= "UPDATE forum_formula
			SET formula_equation = replace(formula_equation,'".$codes['old_name']."','".$codes['new_name']."')
			WHERE formula_equation ILIKE '%".$codes['old_name']."%';\n";
		}
		if(!empty($query))
			$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);

		// Modification de la table report_builder_save
		// Mise à jour de la colonne requete
		$query = '';
		foreach($newRawCodes as $codes)
		{
			$query .= "UPDATE report_builder_save
			SET requete = replace(requete,'".$codes['old_name']."','".$codes['new_name']."')
			WHERE requete ILIKE '%".$codes['old_name']."%';\n";
		}
		if(!empty($query))
			$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);

		// Modification de la table sys_aa_filter_kpi
		// Mise à jour de la colonne saafk_idkpi
		$query = "UPDATE sys_aa_filter_kpi
		SET saafk_idkpi = overlay(saafk_idkpi placing '{$newCode}' from 1 for {$oldCodeLength})
		WHERE saafk_idkpi ILIKE '{$oldCode}%'";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);

		// Modification de la table sys_definition_alarm_dynamic
		// Mise à jour de la colonne alarm_field
		$query = "UPDATE sys_definition_alarm_dynamic
		SET alarm_field = overlay(alarm_field placing '{$newCode}' from 1 for {$oldCodeLength})
		WHERE alarm_field ILIKE '{$oldCode}%'";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		// Mise à jour de la colonne additional_field
		$query = "UPDATE sys_definition_alarm_dynamic
		SET additional_field = overlay(additional_field placing '{$newCode}' from 1 for {$oldCodeLength})
		WHERE additional_field ILIKE '{$oldCode}%'";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		// Mise à jour de la colonne alarm_trigger_data_field
		$query = "UPDATE sys_definition_alarm_dynamic
		SET alarm_trigger_data_field = overlay(alarm_trigger_data_field placing '{$newCode}' from 1 for {$oldCodeLength})
		WHERE alarm_trigger_data_field ILIKE '{$oldCode}%'";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);

		// Modification de la table sys_definition_alarm_static
		// Mise à jour de la colonne additional_field
		$query = "UPDATE sys_definition_alarm_static
		SET additional_field = overlay(additional_field placing '{$newCode}' from 1 for {$oldCodeLength})
		WHERE additional_field ILIKE '{$oldCode}%'";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		// Mise à jour de la colonne alarm_trigger_data_field
		$query = "UPDATE sys_definition_alarm_static
		SET alarm_trigger_data_field = overlay(alarm_trigger_data_field placing '{$newCode}' from 1 for {$oldCodeLength})
		WHERE alarm_trigger_data_field ILIKE '{$oldCode}%'";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);

		// Modification de la table sys_definition_alarm_top_worst
		// Mise à jour de la colonne additional_field
		$query = "UPDATE sys_definition_alarm_top_worst
		SET additional_field = overlay(additional_field placing '{$newCode}' from 1 for {$oldCodeLength})
		WHERE additional_field ILIKE '{$oldCode}%'";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
        // Mise a jour de la colonne list_sort_field (correction bz17175, 04/08/2010 OJT)
		$query = "UPDATE sys_definition_alarm_top_worst
		SET list_sort_field = overlay(list_sort_field placing '{$newCode}' from 1 for {$oldCodeLength})
		WHERE list_sort_field ILIKE '{$oldCode}%'";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		// Mise à jour de la colonne alarm_trigger_data_field
		$query = "UPDATE sys_definition_alarm_top_worst
		SET alarm_trigger_data_field = overlay(alarm_trigger_data_field placing '{$newCode}' from 1 for {$oldCodeLength})
		WHERE alarm_trigger_data_field ILIKE '{$oldCode}%'";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);

		// Modification de la table sys_definition_kpi
		// Mise à jour de la colonne kpi_formula
		$query = '';
		foreach($newRawCodes as $codes)
		{
			$query .= "UPDATE sys_definition_kpi
			SET kpi_formula = replace(kpi_formula,'".$codes['old_name']."','".$codes['new_name']."')
			WHERE kpi_formula ILIKE '%".$codes['old_name']."%';\n";
		}
		if(!empty($query))
			$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);

		// Modification de la table sys_field_reference
		// Mise é jour de la colonne nms_field_name
		$query = "UPDATE sys_field_reference
		SET nms_field_name = overlay(nms_field_name placing '{$newCode}' from 1 for {$oldCodeLength})
		WHERE nms_field_name ILIKE '{$oldCode}%' AND sfr_sdp_id = {$idProdParent}";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		// Mise é jour de la colonne edw_target_field_name
		$query = "UPDATE sys_field_reference
		SET edw_target_field_name = overlay(edw_target_field_name placing '".strtolower($newCode)."' from 1 for {$oldCodeLength})
		WHERE edw_target_field_name ILIKE '{$oldCode}%' AND sfr_sdp_id = {$idProdParent}";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		// Mise é jour de la colonne edw_field_name
		$query = "UPDATE sys_field_reference
		SET edw_field_name = replace(edw_field_name ,'{$oldCode}','{$newCode}')
		WHERE edw_field_name ILIKE '{$oldCode}%' AND sfr_sdp_id = {$idProdParent}";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		// Mise à jour de la colonne edw_field_name_label
		$query = "UPDATE sys_field_reference
		SET edw_field_name_label = overlay(edw_field_name_label placing '{$newCode}' from 1 for {$oldCodeLength})
		WHERE edw_field_name_label ILIKE '{$oldLabel}%' AND sfr_sdp_id = {$idProdParent}";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);
		// Mise à jour de la colonne edw_agregation_formula
		$query = '';
		foreach($newRawCodes as $codes)
		{
			$query .= "UPDATE sys_field_reference
			SET edw_agregation_formula = replace(edw_agregation_formula,'".$codes['old_name']."','".$codes['new_name']."')
			WHERE edw_agregation_formula ILIKE '%".$codes['old_name']."%';\n";
		}
		if(!empty($query))
			$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);

                // 22/09/2010
                // Certains compteurs sont écris en minuscules dans les fonctions
                // BZ 15273
                // Mise à jour des formules avec casse miniuscule
                $query = '';
		foreach($newRawCodes as $codes)
		{
			$query .= "UPDATE sys_field_reference
			SET edw_agregation_formula = replace(edw_agregation_formula,'".strtolower($codes['old_name'])."','".strtolower($codes['new_name'])."')
			WHERE edw_agregation_formula ILIKE '%".$codes['old_name']."%';\n";
		}
		if(!empty($query))
			$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);


		// Modification de la table sys_field_reference_all
		// Mise à jour de la colonne nms_field_name
		$query = "UPDATE sys_field_reference_all
		SET nms_field_name = overlay(nms_field_name placing '{$newCode}' from 1 for {$oldCodeLength})
		WHERE nms_field_name ILIKE '{$oldCode}%'";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);

		// Renommage des colonnes dans les tables de données
			// 22/12/2010 BBX
			// Ajout d'un condition sur attisdropped
			// BZ 18510
		$query = "UPDATE pg_attribute
		SET attname = overlay(attname placing '".strtolower($newCode)."' from 1 for {$oldCodeLength})
		WHERE attrelid IN (
			SELECT oid FROM pg_class WHERE relname LIKE 'edw_{$module}_%_axe1_raw_%')
		AND attnum >=0
			AND attname ILIKE '{$oldCode}%'
			AND attisdropped = false";
		$execCtrl = $execCtrl && (!$database->execute($query) ? false : true);

		// Si tout s'est bien passé, on commit, sinon on rollback
		if($execCtrl) $database->execute('COMMIT');
		else $database->execute('ROLLBACK');

		}
		// Retour du statut
		return $execCtrl;
	}

    /**
     * Retourne si le compteur est défini pour la BH
	 * 16:12 13/10/2010 SCT : BZ 18427 => Désactivation de compteur utilisé pour la BH possible
	 * @param integer $pId Id du produit
	 * @param string $counterName Nom du compteur
	 * @param string $family Famille traitée
     * @return bool $resultatRecherche
     */
    public static function isRawLockedBh($pId, $counterName, $family)
    {
        $database = Database::getConnection($pId);
		$query_bh = "SELECT * FROM sys_definition_time_bh_formula WHERE family = '".$family."' AND lower(bh_indicator_name) = '".$counterName."' AND bh_indicator_type = 'RAW'";
		$res_bh = $database->execute($query_bh);
		if($database->getNumRows() == 0)
			return false;
		else
			return true;
    }

    /**
     * Permet de mettre à jour un identifiant de compteur
     * @param string $oldIdLigne id ligne en cours d'utilisation
     * @param string $newIdLigne id ligne de substitution
     * 
     * 04/08/2011 NSE bz 22995 : possiblité de mise à jour des tables du contexte
     */
    public static function updateRawId($oldIdLigne,$newIdLigne,$productId = 0, $tableCtx = 0)
    {
        // Connexion au produit courant
        $database = DataBase::getConnection($productId);

        // Récupération de l'id du produit courant
        $myId = ($productId == 0) ? ProductModel::getProductId() : $productId;

        // 04/08/2011 NSE bz 22995 : mise à jour des tables contexte
        if($tableCtx != 0)
            $ctx = 'ctx_';
        else
            $ctx = '';
        
        // Mise à jour de sys_field_reference, sys_data_range_style,
        // sys_export_raw_kpi_data, sys_pauto_config
        $query = "UPDATE sys_field_reference
            SET id_ligne = '$newIdLigne'
            WHERE id_ligne = '$oldIdLigne';\n";
        if($database->doesTableExist("{$ctx}sys_data_range_style"))
            $query .= "UPDATE {$ctx}sys_data_range_style
                SET id_element = '$newIdLigne'
                WHERE id_element = '$oldIdLigne';\n";
        if($database->doesTableExist("{$ctx}sys_export_raw_kpi_data"))
            $query .= "UPDATE {$ctx}sys_export_raw_kpi_data
                SET raw_kpi_id = '$newIdLigne'
                WHERE raw_kpi_id = '$oldIdLigne'
                AND raw_kpi_type = 'raw';\n";
        if($database->doesTableExist("{$ctx}sys_pauto_config"))
            $query .= "UPDATE {$ctx}sys_pauto_config
                SET id_elem = '$newIdLigne'
                WHERE id_elem = '$oldIdLigne'".
                ($tableCtx == 0?" AND id_product = $myId":"").";
                \n";
        // Exécution des mises à jour
        $database->execute($query);

        // Connexion au master
        $masterId = ProductModel::getIdMaster();
        $database = Database::getConnection($masterId);

        // Mise à jour de sys_definition_selecteur
        if($database->doesTableExist("{$ctx}sys_definition_selecteur"))
            $query = "UPDATE {$ctx}sys_definition_selecteur
                SET sds_sort_by = replace(sds_sort_by,'$oldIdLigne','$newIdLigne')
                WHERE sds_sort_by LIKE 'counter@{$oldIdLigne}@%';\n";
        if($database->doesTableExist("{$ctx}sys_definition_selecteur"))
            $query .= "UPDATE {$ctx}sys_definition_selecteur
                SET sds_filter_id = replace(sds_filter_id,'$oldIdLigne','$newIdLigne')
                WHERE sds_filter_id LIKE 'counter@{$oldIdLigne}@%';\n";
        // Exécution des mises à jour
        $database->execute($query);

        // L'aplication concernée est-elle un slave ?
        if($myId != $masterId)
        {
            // Alors mise à jour de sys_pauto_config sur le master
            if($database->doesTableExist("{$ctx}sys_pauto_config"))
                $query = "UPDATE {$ctx}sys_pauto_config
                    SET id_elem = '$newIdLigne'
                    WHERE id_elem = '$oldIdLigne'".
                    ($tableCtx == 0?" AND id_product = $myId;":";");
            // Exécution des mises à jour
            $database->execute($query);
        }

        // Existe-t-il un Mixed KPI ?
        $mixedKpiId = ProductModel::getIdMixedKpi();
        if($mixedKpiId !== false)
        {
            // Connexion au Mixed KPI
            $database = Database::getConnection($mixedKpiId);

            // Mise à jour de sys_field_reference sur le Mixed KPI
            $query = "UPDATE sys_field_reference
                SET old_id_ligne = '$newIdLigne'
                WHERE old_id_ligne = '$oldIdLigne'
                AND sfr_sdp_id = $myId";
            // Exécution des mises à jour
            $database->execute($query);
        }
    }
}
?>