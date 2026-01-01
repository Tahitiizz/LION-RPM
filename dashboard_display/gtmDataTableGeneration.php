<?php
/**
 *  Script générant la table de données d'un Dashboard en fonction du fichier XML
 *
 * $Author: o.jousset $
 * $Date: 2010-05-17 09:45:29 +0200 (lun., 17 mai 2010) $
 * $Revision: 26444 $
 */
    // Définitions de defines pour ce script
    define( 'DATA_TABLE_MAX_COL_WIDTH', 35 ); // Nombre maximum de caractères pour une colonne
    define( 'DATA_TABLE_LINE_OFFSET', 1 ); // Numéro de la ligne (après export XML) à partir de laquelle les données doivent être affichées

    // Inclusion des librairies et variables d'environnements
    include_once dirname(__FILE__)."/../php/environnement_liens.php";
    include_once dirname(__FILE__)."/class/XmlExcel.class.php";

    /** @var XmlExcel Object de type XmlExcel utilisé pour parser le XML */
    $xmlExcelObj = NULL;

    /** @var Array Tableau contenant les données du XML */
    $xmlData = array();

    /** @var Integer Itérateur pour les lignes */
    $i = DATA_TABLE_LINE_OFFSET;

    /** @var Integer Itérateur pour les colonnes */
    $u = 0;

    // Test si le fichier XML est définit et accessible
    if( ( isset( $_POST['filename'] ) === TRUE ) && ( file_exists( trim( $_POST['filename'] ) ) === TRUE ) )
    {
        try // Xmlexcel est succeptible de générer une exception
        {
            // On appel la classe XmlExcel, non pas pour générer la fichier XLS
            // mais juste pour extraire les données du XML
            $xmlExcelObj = new XmlExcel(); // On créer l'objet
            $xmlExcelObj->setXmlFile( trim( $_POST['filename'] ) ); // On définit le XML
            $xmlData = $xmlExcelObj->getSingleData(); // On extrait les données
        }
        catch( Exception $e )
        {
            die( $e->getMessage() ); // On fait confiance au message créé dans l'exception
        }

        // Création de la sortie HTML
        echo '<table>';
        for( $i = DATA_TABLE_LINE_OFFSET ; $i < count( $xmlData ) ; $i ++ )
        {
            if( count( $xmlData[$i] ) > 1 )
            {
                if( ( $i % 2 ) === 0 ) // Gestion des lignes paires ou impaires
                {
                    echo '<tr class=\'odd\'>';
                }
                else
                {
                    echo '<tr class=\'even\'>';
                }
                for( $u = 0 ; $u < count( $xmlData[$i] ) ; $u++ )
                {
                    echo '<td title=\''.htmlspecialchars( $xmlData[$i][0].' | '.$xmlData[DATA_TABLE_LINE_OFFSET][$u] ).'\'>'.formatValueForDataTable( trim( $xmlData[$i][$u] ), $i, $u ).'</td>';
                }
                echo '</tr>';
            }
            else
            {
                // Si la ligne ne possède pas plus de 1 colonne, on le l'affiche pas
            }
        }
        echo '</table>';
    }

    // Si le fichier n'est pas définit ou inaccessible, on renvoi une erreur
    else
    {
        die( __T( 'U_E_GTM_DATA_TABLE_ERROR' ) );
    }

/**
 * Fonction qui met en forme un texte afin d'être affiché dans un table de données (mise en gras, split, ...)
 * @param String $str Chaine à mettre en forme
 * @param Integer $lineNum 
 * @param Integer $colNum
 * @return String (au format HTML)
 */
function formatValueForDataTable( $str, $lineNum, $colNum )
{
    /** @var String Variable de retour */
    $retVal = '';

    // Exclusion de la première case
    if( ( $lineNum != DATA_TABLE_LINE_OFFSET ) || ( $colNum != 0 ) )
    {
        if( strlen( $str ) > DATA_TABLE_MAX_COL_WIDTH )
        {
            foreach( str_split( $str, DATA_TABLE_MAX_COL_WIDTH ) as $splitPart )
            {
                $retVal .= htmlentities( $splitPart ).'<br />';
            }
        }
        else // Si la chaîne fait moins de DATA_TABLE_MAX_COL_WIDTH caractères
        {
            $retVal = htmlentities( $str ); // On retournera la chaîne entière
        }

        // Mise en gras de la première ligne, première colonne
        if ( ( $lineNum === DATA_TABLE_LINE_OFFSET ) || ( $colNum === 0 ) )
        {
            $retVal = '<b>'.$retVal.'</b>';
        }
        else
        {
            // Pas de mise en gras
        }
    }
    else
    {
        // Pour la première case, on applique le style dataTableFirstCell (voir CSS)
        $retVal = '<div class=\'dataTableFirstCell\'>&nbsp;</div>';
    }

    // Astuce pour forcer le remplissage des cases du tableau même si il n'y a
    // aucune donnée (évite les cases vides sans bordure sous IE)
    if( strlen( $retVal ) === 0 )
    {
        $retVal = '&nbsp;';
    }
    else
    {
        // Ok
    }
    return $retVal;
}
// ?php> Volontairement mis en commentaire
