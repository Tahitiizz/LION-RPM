<?php

/*
 * 27/03/2017 : [AO-TA] formatage des nombres dans les graphs t&a Requirement [RQ:4893]
 */

/**
 * Application de la fonction number_format en fonction de la valeur du param?tre 
 * global thousand_separator ? l'axe Y de droite
 * @param type $aVal
 * @return type
 */
function numberFormatRightAxis($aVal) {
    $separator = get_sys_global_parameters("thousand_separator", "NONE") ;
    switch ($separator) {
        case 'EN':
            return number_format($aVal, 0, ".", ",");
            break;
        case 'FR':
            return number_format($aVal, 0, ",", " ");
            break;
        case 'NONE':
            return number_format($aVal, 0, ".", "");
            break;
        default:
            return number_format($aVal, 0, ".", ",");
            break;
    }
}

/**
 * Application de la fonction number_format en fonction de la valeur du param?tre 
 * global thousand_separator ? l'axe Y de gauche
 * @param type $aVal
 * @return type
 */
function numberFormatLeftAxis($aVal) {
    $separator = get_sys_global_parameters("thousand_separator", "NONE") ;
    switch ($separator) {
        case 'EN':
            return number_format($aVal, 2, ".", ",");
            break;
        case 'FR':
            return number_format($aVal, 2, ",", " ");
            break;
        case 'NONE':
            return number_format($aVal, 2, ".", "");
            break;
        default:
            return number_format($aVal, 2, ".", ",");
            break;
    }
}

