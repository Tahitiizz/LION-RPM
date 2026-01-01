<?php
/**
* Traitements Ajax des IHM des alarmes
*
* @author BBX
* @version CB 5.1.0.00
* @package Alarmes
* @since CB 5.1.0.00
*
*       27/07/2010 BBX :
*           - BZ 16652 : ajout d'un mode debug + RAZ des valeurs par défaut si plus de sélection
 * 13/09/2011 NSE DE Master 5.1 / slave 5.0 compatibility : gestion si la fonctionalité n'est pas disponible sur le slave
 * 02/11/2011 ACS BZ 23923 The browser throws a warning error when uncheck on child NEs
 * 19/06/2012 NSE bz 27674 : lenteur du check all
*/
?>
<?php
// Session
session_start();

// Includes
include_once('../../php/environnement_liens.php');
include_once('../../php/edw_function_family.php');

// Traitement de la demande
$action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'];

// Si la demande est correcte
if(!empty($action) && preg_match('/^([A-Z]*)$/i',$action))
{
    // On traite les demandes
    switch($action)
    {
        /*
         * Recherche d'un élément
         */
        case 'searchNetworkElement':
            // Récupération des valeurs
            $idT = isset($_GET['idT']) ? utf8_decode($_GET['idT']) : '';
            $readonly = isset($_GET['readonly']) ? $_GET['readonly'] : '1';

            // Test de la variable de session pour la recherche
            if(!isset($_SESSION['alarmsSessionArray']['saved_search']) || !is_array($_SESSION['alarmsSessionArray']['saved_search']))
                break;

            // Génération du header xml
            $headers['Pragma']        = 'no-cache';
            $headers['Expires']       = '0';
            $headers['Last-Modified'] = gmdate("D, d M Y H:i:s") . " GMT";
            $headers['Cache-Control'] = 'no-cache, must-revalidate';
            $headers['Content-type']  = 'application/xml; charset=UTF-8';
            foreach ( $headers as $key => $value )
                header($key. ':' . $value);

            // Récupération  de la chaîne tapée par l'utilisateur
            $debut = "";
            if (isset($_GET['debut'])) {
                // On echape le plus pour pouvoir faire une recherche dessus
                $debut = str_replace('+', '\+', utf8_decode($_GET['debut']));
            }

            $MAX_RETURN = 10;
            $found = 0;
            // On recherche les occurences de la chaînes dans le tableau des éléments, sur le code et le label.
            echo "<ul>";
            foreach($_SESSION['alarmsSessionArray']['saved_search'] as $key => $val)
            {
                // En mode lecture seule, on n'affiche pas les éléments non cochés
                if($readonly == '1')
                {
                    if(!in_array($key,$_SESSION['alarmsSessionArray']['current_selection'][$idT]))
                        continue;
                }
                if(@preg_match( '/'. strtolower($debut) .'/', strtolower($key) ) || @preg_match( '/'. strtolower($debut) .'/', strtolower($val) ))
                {
                    $id = $idT."||".$key;
                    echo(utf8_encode("<li id='li_$id' id_to_check='$id'>{$val}</li>"));
                    $found++;
                }
                if($found == $MAX_RETURN)
                    break;
            }
            if(!$found)
                echo "<li>".__T('SELECTEUR_NO_RESULT')."</li>";
            echo "</ul>";
        break;

        /*
         * On demande la mise à jour du contenu d'un onglet
         */
        case 'updateTabContent':
            // On fixe la limite du nombre de NA par div
            $MaxElements = 5000;
            $limit = 0;            
            // Longueur max d'un label
            $labelMaxLength = get_sys_global_parameters('na_label_character_max');
            // Récupération des valeurs
            $family = isset($_GET['family']) ? $_GET['family'] : '';
            $id_alarm = isset($_GET['id_alarm']) ? $_GET['id_alarm'] : '';
            $type_alarm = isset($_GET['type_alarm']) ? $_GET['type_alarm'] : '';
            $id_prod = isset($_GET['product']) ? $_GET['product'] : '';
            $na = isset($_GET['idT']) ? $_GET['idT'] : '';
            $readonly = isset($_GET['readonly']) ? $_GET['readonly'] : '1';
            // Instance Alarm Model
            $alarmModel = new AlarmModel($id_alarm,$type_alarm,$id_prod,$family);
            // Récupération des éléments présents en topologie
            $networkElements = $alarmModel->getTopologyElements($na);
            // Récupération des éléments à cocher
            $networkElementSelection = $_SESSION['alarmsSessionArray']['current_selection'][$na];
            // On sauvegarde notre tableau pour l'utiliser avec la recherche
            $_SESSION['alarmsSessionArray']['saved_search'] = $networkElements;
            // Désormais, on peut parcourir notre tableau pour générer la chaine HTML de retour
            $html = '';
            $separateur = "||";
            foreach($networkElements as $id_elem=>$label_elem)
            {
                // Mode lecture seule
                if($readonly == '1')
                {
                    if(in_array($id_elem,(array)$networkElementSelection))
                    {
                        $idN = $na.$separateur.$id_elem;
                        $html .= "
                        <input type='checkbox' id='".$idN."' value='".$id_elem."' title='".utf8_encode($label_elem)."' checked disabled />
                        <label for='".$idN."' title='".utf8_encode($label_elem)."'>".utf8_encode((strlen($label_elem) > $labelMaxLength) ? substr($label_elem,0,$labelMaxLength).'...' : $label_elem)."</label><br />";
                    }
                    else 
                    {
                        // Si l'élément n'est pas affiché, on le le comptabilise pas
                        $limit--;
                    }
                }
                // Mode lecture-écriture
                else
                {
                    // Doit-on cocher l'élément ?
                    $checked = (in_array($id_elem,(array)$networkElementSelection)) ? ' checked' : '';
                    $idN = $na.$separateur.$id_elem;
                    // 18/07/2012 BBX
                    // BZ 27746 : ajout de l'instruction utf8_encode autour des codes et labels
                    $html .= "
                    <input type='checkbox' id='".$idN."' value='".$id_elem."' title='".utf8_encode($label_elem)."'".$checked." onclick='manageAutomaticSelection(this,\"$na\",\"".utf8_encode($id_elem)."\")' />
                    <label for='".$idN."' title='".utf8_encode($label_elem)."'>".utf8_encode((strlen($label_elem) > $labelMaxLength) ? substr($label_elem,0,$labelMaxLength).'...' : $label_elem)."</label><br />";
                }
                // Test de la limite
                if($limit == $MaxElements) {
                    $html .= "<div class='texteRouge' style='padding:5px;'>".__T('U_SELECTEUR_NE_TOO_MANY_ELEMENTS')."</div>";
                    break;
                }
                $limit++;
            }
            // Si rien n'a été trouvé, on retourne le message d'erreur correspondant
            if($html == '') $html = __T('SELECTEUR_NO_VALUE_FOUND',$id_prod);
            // Retour du code HTML
            // 18/06/2012 NSE bz 27397 : ajout des liens check / uncheck en 5.1/5.0
            if($readonly != '1' && !$alarmModel->isAlarmModuleWithNeParent()){
                // 19/06/2012 NSE bz 27674 : utilisation de l'ancienne fonction chargerContenu()
                $html = "
		<div class='texteGrisPetit' style='padding:3px;'><script>alert('chargerContenu(\"$na\",\"$family\",\"yes\",\"$id_prod\")');</script>
			<span style='cursor:pointer' onclick='_idCurrentElement=\"htmlPrefix_$na\";chargerContenu(\"$na\",\"$family\",\"yes\",\"$id_prod\")'>".__T('U_NA_SELECTION_LABEL_CHECK_ALL')."</span>
			-  
			<span style='cursor:pointer' onclick='_idCurrentElement=\"htmlPrefix_$na\";chargerContenu(\"$na\",\"$family\",\"no\",\"$id_prod\")'>".__T('U_NA_SELECTION_LABEL_UNCHECK_ALL')."</span>
		</div>
                    ".$html;
            }
            echo $html;
        break;

        /*
         *  On demande la mise à jour automatique de la sélection
         */
        case 'manageAutomaticSelection':
            // Récupération des paramètres
            $id_alarm = $_POST['id_alarm'];
            $type_alarm = $_POST['type_alarm'];
            $id_prod = $_POST['product'];
            $family = $_POST['family'];
            $na = $_POST['na'];
            // 18/07/2012 BBX
            // BZ 27746 : ajout de l'instruction utf8_decode
            $na_value = utf8_decode($_POST['na_value']);
            $status = $_POST['status'];
            
            // Instance Alarm Model
            $alarmModel = new AlarmModel($id_alarm,$type_alarm,$id_prod,$family);
            // Récupération des éléments présents en topologie
            $networkElements = $alarmModel->getTopologyElements($na);
            // Récupération des niveaux d'agrégation
            $naLevels = $alarmModel->getNaLevels();

            // Si l'élément est coché
            if($status == 'checked')
            {
               // On l'ajoute à la sélection réelle
               if(!in_array($na_value,(array)$_SESSION['alarmsSessionArray']['ne_selection'][$na]))
                   $_SESSION['alarmsSessionArray']['ne_selection'][$na][] = $na_value;
               // On l'ajoute à la sélection courante
               if(!in_array($na_value,(array)$_SESSION['alarmsSessionArray']['current_selection'][$na]))
                   $_SESSION['alarmsSessionArray']['current_selection'][$na][] = $na_value;

               // 13/09/2011 NSE DE Master 5.1 / slave 5.0 compatibility
               // si la fonctionalité est disponible sur le slave
               if($alarmModel->isAlarmModuleWithNeParent()){
                   // On ajoute automatiquement ses enfants à la sélection courante
                   $childElements = $alarmModel->getChildElements($na,Array($na_value));
                   foreach($childElements as $childLevel => $allValues)
                   {
                        // Si la sélection pour le niveau courant n'existe pas encore, on créé l'entrée
                        if(!isset($_SESSION['alarmsSessionArray']['current_selection'][$childLevel]))
                            $_SESSION['alarmsSessionArray']['current_selection'][$childLevel] = array();
                        // Puis on y insère les éléments
                        $_SESSION['alarmsSessionArray']['current_selection'][$childLevel] = array_merge($_SESSION['alarmsSessionArray']['current_selection'][$childLevel],$allValues);
                        $_SESSION['alarmsSessionArray']['current_selection'][$childLevel] = array_unique($_SESSION['alarmsSessionArray']['current_selection'][$childLevel]);

                        // On supprime les enfants de la sélection réelle
                        if(isset($_SESSION['alarmsSessionArray']['ne_selection'][$childLevel]))
                            $_SESSION['alarmsSessionArray']['ne_selection'][$childLevel] = array_diff($_SESSION['alarmsSessionArray']['ne_selection'][$childLevel],$allValues);
                   }
               }
            }
            // Si l'élément est décoché
            else
            {
                // Suppression de l'élément de la sélection réelle
                if(isset($_SESSION['alarmsSessionArray']['ne_selection'][$na]))
                {
                    if(in_array($na_value,$_SESSION['alarmsSessionArray']['ne_selection'][$na]))
                        unset($_SESSION['alarmsSessionArray']['ne_selection'][$na][array_search($na_value, $_SESSION['alarmsSessionArray']['ne_selection'][$na])]);
                }
                // Suppression de l'élément de la sélection courante
                if(isset($_SESSION['alarmsSessionArray']['current_selection'][$na]))
                {
                    if(in_array($na_value,$_SESSION['alarmsSessionArray']['current_selection'][$na]))
                        unset($_SESSION['alarmsSessionArray']['current_selection'][$na][array_search($na_value, $_SESSION['alarmsSessionArray']['current_selection'][$na])]);
                }
                // 13/09/2011 NSE DE Master 5.1 / slave 5.0 compatibility
                // si la fonctionalité est disponible sur le slave
                if($alarmModel->isAlarmModuleWithNeParent()){
                    // Suppression des enfants de la sélection
                    $childElements = $alarmModel->getChildElements($na,Array($na_value));
                    foreach($childElements as $childLevel => $allValues)
                        $_SESSION['alarmsSessionArray']['current_selection'][$childLevel] = array_diff($_SESSION['alarmsSessionArray']['current_selection'][$childLevel],$allValues);

                    // Suppression des parents de la sélection
                    $relatedParents = $alarmModel->getParentElements($na,Array($na_value));
                    $msgReturn = '';
                    $nbParents = 0;
                    foreach($relatedParents as $parent => $allValues)
                    {
                        // Récupération des parents à décocher
                        // 11/04/2011 BBX
                        // Ajout d'un test sur l'élément à modifier car celui-ci peut être vide
                        // BZ 21786
                        $parentValues = array();
                        if(is_array($_SESSION['alarmsSessionArray']['current_selection'][$parent]))
                            $parentValues = array_intersect($_SESSION['alarmsSessionArray']['current_selection'][$parent],$allValues);

                        // Suppression des éléments de la sélection réelle
                        // 11/04/2011 BBX
                        // Ajout d'un test sur l'élément à modifier car celui-ci peut être vide
                        // BZ 21786
                        if(is_array($_SESSION['alarmsSessionArray']['ne_selection'][$parent]))
                            $_SESSION['alarmsSessionArray']['ne_selection'][$parent] = array_diff($_SESSION['alarmsSessionArray']['ne_selection'][$parent],$allValues);
                        // 02/11/2011 ACS BZ 23923 The browser throws a warning error when uncheck on child NEs
                        // Suppression des éléments de la sélection courante
	                    if (is_array($_SESSION['alarmsSessionArray']['current_selection'][$parent])) {
	                    	$_SESSION['alarmsSessionArray']['current_selection'][$parent] = array_diff($_SESSION['alarmsSessionArray']['current_selection'][$parent],$allValues);
	                    }

                        // Complétion du message de retour
                        foreach($parentValues as $parentValue)
                        {
                            // 28/07/2010 BBX
                            // Si pas de label, utilisation du code
                            // BZ 17023
                            $parentLabel = trim(NeModel::getLabel($parentValue, $parent, $id_prod));
                            if($msgReturn != '') $msgReturn .= ', ';
                            $msgReturn .= $naLevels[$parent].' "'.(!empty($parentLabel) ? $parentLabel : $parentValue).'"';
                            $nbParents++;
                        }

                        // Pour chaque parent à décocher, on remet ses enfants dans la sélection réelle
                        $childElements = $alarmModel->getChildElements($parent,$parentValues);
                        foreach($childElements as $childLevel => $childValues)
                        {
                            // Si la sélection pour le niveau courant n'existe pas encore, on créé l'entrée
                            if(!isset($_SESSION['alarmsSessionArray']['ne_selection'][$childLevel]))
                                $_SESSION['alarmsSessionArray']['ne_selection'][$childLevel] = array();

                            // Puis on ajoute les enfants
                            // 11/04/2011 BBX
                            // Ajout d'un test sur l'élément à modifier car celui-ci peut être vide
                            // BZ 21786
                            if(is_array($_SESSION['alarmsSessionArray']['current_selection'][$childLevel])) {
                                $childElementsToAdd = array_intersect($_SESSION['alarmsSessionArray']['current_selection'][$childLevel],$childValues);
                                $_SESSION['alarmsSessionArray']['ne_selection'][$childLevel] = array_merge($_SESSION['alarmsSessionArray']['ne_selection'][$childLevel],$childElementsToAdd);
                                $_SESSION['alarmsSessionArray']['ne_selection'][$childLevel] = array_unique($_SESSION['alarmsSessionArray']['ne_selection'][$childLevel]);
                            }
                        }
                    }
                }
                // Message de retour
                if($nbParents > 0)
                {
                    $verb = ($nbParents > 1) ? 'were' : 'was';
                    $poss = ($nbParents > 1) ? 'their' : 'its';
                    echo __T('A_SETUP_ALARM_AUTOMATIC_UNSELECTION',$msgReturn,$verb,$poss);
                }
            }
            
            // Contrôle de la variable de session
            foreach($_SESSION['alarmsSessionArray']['ne_selection'] as $na => $naValues)
            {
                // Si le niveau ne contient plus rien, on le supprime
                if(count($_SESSION['alarmsSessionArray']['ne_selection'][$na]) == 0)
                    unset($_SESSION['alarmsSessionArray']['ne_selection'][$na]);
            }
        break;

         /*
         *  Reset de la sélection
         */
        case 'resetNetworkElementSelection':
            // Reset des variables de session
            $_SESSION['alarmsSessionArray']['ne_selection'] = array();
            $_SESSION['alarmsSessionArray']['current_selection'] = array();
            $_SESSION['alarmsSessionArray']['ne_selection_default'] = array();
            $_SESSION['alarmsSessionArray']['current_selection_default'] = array();
        break;

         /*
         *  Fermeture sans sauvegarde
         */
        case 'closeWithoutSaving':
            // Restauration des valeurs par défaut
            $_SESSION['alarmsSessionArray']['ne_selection'] = $_SESSION['alarmsSessionArray']['ne_selection_default'];
            $_SESSION['alarmsSessionArray']['current_selection'] = $_SESSION['alarmsSessionArray']['current_selection_default'];
        break;

         /*
         *  Fermeture avec sauvegarde
         */
        case 'saveCurrentSelection':
            // Met à jour la sélection par défaut
            if(count($_SESSION['alarmsSessionArray']['ne_selection']) > 0) {
                $_SESSION['alarmsSessionArray']['ne_selection_default'] = $_SESSION['alarmsSessionArray']['ne_selection'];
                $_SESSION['alarmsSessionArray']['current_selection_default'] = $_SESSION['alarmsSessionArray']['current_selection'];
            }
            else {
                // 27/07/2010 BBX
                // Si la sélection est vide, alors il faut RAZ les valeurs par défaut
                // BZ 16652
                $_SESSION['alarmsSessionArray']['ne_selection_default'] = array();
                $_SESSION['alarmsSessionArray']['current_selection_default'] = array();
            }
        break;

         /*
         *  Liste des éléments sélectionnés
         */
         case 'selectedElements':
            $id_prod = isset($_POST['product']) ? $_POST['product'] : '';
            $html = '';
            foreach($_SESSION['alarmsSessionArray']['ne_selection'] as $na => $allValues)
            {
                foreach($allValues as $value)
                {
                    $naLabel = NeModel::getLabel($value, $na, $id_prod);
                    $naLabel = empty($naLabel) ? $value : $naLabel;
                    // 18/07/2012 BBX
                    // BZ 27746 : ajout de l'instruction utf8_encode autour des codes et labels
                    $html .= "
                    <li id='li_{$na}_{$value}' style='cursor:pointer;'>".utf8_encode($naLabel)."
                            <input type='button' class='boutonNeSelectionDeleteElement' title='".__T('SELECTEUR_DELETE_FROM_CURRENT_SELECTION')."'
                                    onclick=\"deleteElementFromList('$na','".utf8_encode($value)."'); $('li_{$na}_{$value}').remove();\" />
                    </li>";
                }
            }
            echo $html;
         break;

         /*
         *  Récupération du statut de la sélection
         */
         case 'getSelectionStatus':
            // Récupération des valeurs
            $id_alarm = $_POST['id_alarm'];
            $type_alarm = $_POST['type_alarm'];
            $id_prod = $_POST['product'];
            $family = $_POST['family'];
            $na = $_POST['na'];
            // Instance Alarm Model
            $alarmModel = new AlarmModel($id_alarm,$type_alarm,$id_prod,$family);
            // Récupération des parents
            $na2na = $alarmModel->getNa2Na();
            // Récupération des niveaux d'agrégation
            $naLevels = $alarmModel->getNaLevels();
            // Génération du message
            $msg = '';
            $nbLevel = 0;
            foreach($_SESSION['alarmsSessionArray']['current_selection_default'] as $level => $values)
            {
                // Si on a changé de Network Level, il ne faut plus lister les anciens niveaux
                if(!in_array($level,$na2na[$na]))
                    continue;
                // Si on a des valeurs pour ce niveau, on les affiche
                if(count($values) > 0)
                {
                    $sep = ($msg == '') ? '' : ', ';
                    $plural = (count($values) > 1) ? 's' : '';
                    $msg .= $sep.count($values).' '.$naLevels[$level].$plural;
                    $nbLevel++;
                }
            }
            // Si pas de valeurs personnalisées, affichage de ALL
            if($nbLevel == 0)
                $msg = '0|All '.$naLevels[$na].'s';
            // Sinon affichage du message normal
            else $msg = '1|'.$msg;
            echo $msg;
         break;

         /**
          * Debug
          */
         case 'getSelection':
             echo '<pre>';
             print_r($_SESSION['alarmsSessionArray']['current_selection_default']);
             echo '</pre>';
         break;
    }
}

?>