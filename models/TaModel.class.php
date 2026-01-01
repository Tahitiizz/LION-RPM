<?php
/**
*	Classe permettant de manipuler les TA de chaque famille
*
*	@author	SCT - 15/10/2010
*	@version	CB 5.1.0.07
*	@since	CB 5.1.0.07
 * 
 * 10/10/2011 NSE DE Bypass temporel : création de la méthode IsTABypassedForFamily()
 * 12/10/2011 MMT DE Bypass temporel : ajout de ma methode getAllTaForFamily
*
*/
class TaModel
{
    /**
     * Retourne le TA Min d'une famille
     * @param string $family nom de la famille
     * @param integer $pId Id du produit
     * @return string $taValue
     */
    public static function getTaRawMinFromFamily($idFamily, $pId = 0)
    {
        $database = Database::getConnection($pId);
        $taValue = '';
        $query = '
            SELECT
                time_agregation
            FROM
                sys_definition_group_table_time
            WHERE
                id_group_table = '.$idFamily.'
                AND data_type = \'raw\'
                AND id_source = -1';
        return $database->getOne($query);
    }

    /**
     *
     * @param string $idGroupTable
     * @param integer $pId
     * @return array
     */
    public static function getAllTa($idGroupTable, $pId = 0)
    {
        $allTa = array();
        $database = Database::getConnection($pId);
        $query = "SELECT
                DISTINCT id_source, time_agregation
            FROM
                sys_definition_group_table_time
            WHERE
                 data_type = 'raw'
		 AND id_group_table = {$idGroupTable}
            ORDER BY id_source";
        $result = $database->execute($query);
        while($row = $database->getQueryResults($result, 1)) {
           $allTa[] = $row['time_agregation'];
        }
        return $allTa;
    }


	 /**
	  * 12/10/2011 MMT DE Bypass temporel
	  *
	  * Get all the TAs for a family code (from sys_definition_category - family)
	  *
	  * @param String $family code from column family in sys_definition_categorie
	  * @param type $pId Product id (optional, default value is set to current product id)
	  * @return Array<String> list of TA codes enabled for that family
	  */
	 public static function getAllTaForFamily($family, $pId = 0)
    {
        $allTa = array();
        $database = Database::getConnection($pId);
        $query = "SELECT
                DISTINCT gtt.id_source, time_agregation
            FROM
                sys_definition_group_table_time gtt,
					 sys_definition_group_table gt
            WHERE
					  gt.id_ligne = gtt.id_group_table
             AND gtt.data_type = 'raw'
		       AND gt.family = '{$family}'
            ORDER BY gtt.id_source";
        $result = $database->execute($query);
        while($row = $database->getQueryResults($result, 1)) {
           $allTa[] = $row['time_agregation'];
        }
        return $allTa;
    }

    /**
     * Test if a Time Aggregation is bypassed for a family.
     * @param String $ta Time aggregation code 
     * @param String $idFamily Family code
     * @param Integer $pId Product id (optional, default value is set to current product id)
     * @return type  1: the given family has the given Ta calculation bypassed activated.
     *               0: the family is not bypassed for given Ta.
     *              -1: other cases (Ta or family unknown).
     * 
     * 10/10/2011 NSE DE Bypass temporel
     */
    public static function IsTABypassedForFamily($ta ,$idFamily, $pId = 0){

        // recupere la liste des TA existante
        $tas = TaModel::getAllTaForFamily($idFamily,$pId);
        $database = Database::getConnection($pId);
        if(!in_array($ta, $tas)){
            // si le bypass est ouvert à d'autres Ta, on pourra vérifier dans la table sys_definition_time_aggregation
           return -1;
		  } else if ($ta != "day") {
			  return 0;
		  }
        $query = "SELECT ta_bypass FROM sys_definition_categorie WHERE family='$idFamily'";
        if($database->getNumRows() < 1){
            return -1;
		  }
        $bypassedTa = $database->getOne($query);
        if($bypassedTa==$ta){
            return 1;
		  }else{
            return 0;
		  }
    }

    
    /**
     * Méthode récursive pour suivre la ligne des Ta à partir d'une première Ta, jusqu'à trouver une deuxième Ta
     * @param string $ta1 Time agregation
     * @param string $ta2 Time agregation
     * @param int $product Product Id
     * @return boolean 
     * 
     * 11/10/2011 NSE DE Bypass temporel
     */
    private static function isTa1Inf($ta1,$ta2,$product=''){
        $database = Database::getConnection($product);
        $query = "SELECT agregation,source_default FROM sys_definition_time_agregation WHERE agregation='$ta2'";
        $res = $database->getRow($query);
        if($res['source_default']==$ta1){
            // la ta1 est source de la ta2
            return true;
        }
        elseif($res['source_default']==$res['agregation']){
            // si on est arrivé sur la Ta min, c'est que la ta1 n'est pas dans les antécédants de la Ta2
            return false;
        }
        else{
            // on regarde si ta1 est source de la Ta source de la ta2 : ta1 -> taX -> ta2
            return self::isTa1Inf($res['source_default'],$ta1);
        }
    }

    /**
     * Compare deux Ta
     * @param type $ta1 Time Agregation
     * @param type $ta2 Time Agregation
     * @return int  0 les deux Ta sont égales
     *              1 la première Ta est supérieure à la deuxième
     *             -1 la première Ta est inférieure à la deuxième
     *             -2 comparaison impossible
     * 
     * 11/10/2011 NSE DE Bypass temporel
     */
    public static function isTa1Greater($ta1,$ta2,$product=''){
        if($ta1==$ta2)
            return 0;
        elseif(self::isTa1Inf($ta2,$ta1,$product))
                return 1;
            elseif(self::isTa1Inf($ta1,$ta2,$product))
                    return -1;
                else 
                    return -2;
    }
}
?>
