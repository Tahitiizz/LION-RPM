<?php
/*
 * Permet de manipuler les chaînes de caractères
 */

class StringModel{

    /**
     * Applique une modification de casse aux éléments d'un tableau
     * 
     * @param int $case modification (1:upper, 2:lower, autre:pas de modif)
     * @param array $values tableau des valeurs à modifier
     * @return array tableau des valeurs modifiées (ou non)
     * 
     * Pas demodifications sur les caractères acentués : é -> é, È -> È
     * ni lors du passage en majuscules, ni en minuscules
     */
    public static function updateCase($case, $values){
        if($case==1){
            // on met en majuscules
            return array_map('strtoupper', $values);
        }
        elseif($case==2){
            // on met en minuscules
            return array_map('strtolower', $values);
        }
        else
            return $values;
    }
}
?>
