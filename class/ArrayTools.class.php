<?php
/**
* class ArrayTools
* @author BBX
* @version 5.1.1.0
* Enables developpers to create their own
* complex/advanced array management methods
*/
class ArrayTools
{
    /**
     * Compare 2 valeurs avec possibilités de paramétrer les clés à comparer
     * @param mixed $valueOne valeur 1
     * @param mixed $valueTwo valeur 2
     * @param array $compareKeys clés à comparer
     * @param boolean $caseSensitive sensibilité à la casse (vrai / faux)
     * @return boolean
     */
    public static function compareValues($valueOne,
                                            $valueTwo,
                                            array $compareKeys = array(),
                                            $caseSensitive = false)
    {
        // Si pas de clés spécifiées, on considère les clés du premier
        // tableau comme clés à comparer
        if(empty($compareKeys))
            $compareKeys = is_array($valueOne) ?
                array_keys($valueOne) : array('whatever');

        // Compteurs de comparaisons réussies
        $nbComOk = 0;
        // Pour toutes les clés à comparer
        foreach($compareKeys as $key)
        {
            // Gestion des vlaurs à comparer
            $valueCompareOne = is_array($valueOne) ? $valueOne[$key] : $valueOne;
            $valueCompareTwo = is_array($valueTwo) ? $valueTwo[$key] : $valueTwo;
            
            // On vérifie l'égalité des valeurs correspondantes
            // avec ou sans sensibilité à la casse
            $check = ($valueCompareOne == $valueCompareTwo);
            if(!$caseSensitive)
                $check = (strtolower($valueCompareOne) == strtolower($valueCompareTwo));
            // Si les valeurs sont égales, on comptabilise le test
            if($check) $nbComOk++;
        }

        // S'il y a autant de tests réussis que de tests effectués
        // les tableau sont considérés comme égaux
        return ($nbComOk == count($compareKeys));
    }

    /**
     * Compare 2 tableaux sur le niveau désiré, selon les clés désirées.
     * Permet des comparaisons complexes entre tableaux.
     * @param array $arrayOne premier tableau
     * @param array $arrayTwo second tableau
     * @param integer $level niveau de comparaison (tableaux multidimentionnels)
     * @param array $compareKeys tableau des clés à utiliser pour la comparaison
     * @param boolean $caseSensitive comparaison sensible à la casse (vrai / faux)
     * @return array Tableau résultant, de la forme [clé tableau 1] => [clé tableau 2] => valeurs
     */
    public static function conditionnalComparison(array $arrayOne,
                                                    array $arrayTwo,
                                                    $level = 0,
                                                    array $compareKeys = array(),
                                                    $caseSensitive = false)
    {
        // Préparation du tableau de résultat
        $compareResults = array();

        // Traitement du niveau de comparaison
        $level = abs((int)$level);

        // Cas 1 : la comparaison n'est pas multidimentionnelle
        if($level == 0)
        {
            // Parcours du premier tableau
            foreach($arrayOne as $keyOne => $valueOne)
            {
                // Parcours du second tableau
                foreach($arrayTwo as $keyTwo => $valueTwo)
                {
                    // On doit comparer les mêmes clés
                    if($keyOne != $keyTwo) continue;

                    // Si des clés sont spécifiées, on ne compare que celles-ci
                    if(!empty($compareKeys))
                        if(!in_array($keyOne,$compareKeys)) continue;

                    // Si les valeurs à comparer sont considérés
                    // comme égales, on ajoute la ligne correspondante au tableau
                    // de résultat
                    if(self::compareValues($valueOne,$valueTwo,$compareKeys,$caseSensitive))
                        $compareResults[$keyOne] = array($keyTwo => $valueTwo);
                }
            }
        }
        // Cas 2 : la comparaison est multidimentionnelle
        elseif($level > 0)
        {
            // Parcours du premier tableau
            foreach($arrayOne as $keyOne => $valueOne)
            {
                // On vérifie que la valeur courante est bien un tableau
                if(!is_array($valueOne))
                    break;

                // On extrait le sous-tableau du niveau à comparer
                $arrayCompareOne = self::extractArraylevel($valueOne, $level);

                // Parcours du second tableau
                foreach($arrayTwo as $keyTwo => $valueTwo)
                {
                    // On vérifie que la valeur courante est bien un tableau
                    if(!is_array($valueTwo))
                        break;

                    // On extrait le sous-tableau du niveau à comparer
                    $arrayCompareTwo = self::extractArraylevel($valueTwo, $level);

                    // Si les sous-tableaux du niveau à comparer sont considérés
                    // comme égaux, on ajoute la ligne correspondante au tableau
                    // de résultat
                    if(self::compareValues($arrayCompareOne,$arrayCompareTwo,$compareKeys,$caseSensitive))
                        $compareResults[$keyOne] = array($keyTwo => $valueTwo);
                }
            }
        }

        // Retour du tableau de résultat
        return $compareResults;
    }

    /**
     * Extrait un sous-tableau depuis un tableau sur le niveau désiré
     * @param array $arrayGiven tableau source
     * @param integer $level niveau de recherche
     * @return array sous-tableau extrait
     */
    public static function extractArraylevel(array $arrayGiven, $level = 0)
    {
        // Tableau à traiter
        $arrayToExtract = $arrayGiven;

        // Traitement du niveau de comparaison
        $level = abs((int)$level);
        
        // On commence à parcourir notre tableau
        // en partant du niveau 1
        $currentLvl = 1;
        while($currentLvl < $level)
        {
            // On test la valeur à parcourir
            // Il doit obligatoirement s'agit d'un tableau
            // Si le test échoue, on retourne un tableau vide.
            if(!is_array($arrayToExtract))
                return array();

            // On extrait le sous-tableau qui doit se trouver sur ce niveau
            // et on le mémorise pour la boucle suivante
            foreach($arrayToExtract as $subKey => $subValue)
                $arrayToExtract = $subValue;

            // Sans oublier d'indiquer le niveau suivant
            $currentLvl++;
        }

        // On test à nouveau notre sous-valeur afin d'être certain de
        // retourner un tableau.
        // Si la valeur n'est pas un tableau, on retourne alors cette valeur
        // dans un tableau.
        if(!is_array($arrayToExtract))
            return array($arrayToExtract);

        // On retourne le tableau extrait
        return $arrayToExtract;
    }
}
?>
