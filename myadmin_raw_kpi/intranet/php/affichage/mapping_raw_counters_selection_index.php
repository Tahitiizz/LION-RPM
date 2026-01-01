<?
/*
 * @cb516@
 * 
 *	13/12/2011 ACS BZ 24853 Impossible to compute hours retreived after having activated a counter
 * 
 * 
*	@cb41000@
*
*	11/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	- maj 14/10/2008 SLC : centrage de la famille suite à ajout du DOCTYPE
*
*	11/12/2008 BBX : modifications pour le CB 4.1 :
*	=> Utilisation des nouvelles méthodes et constantes
*	=> Contrôle d'accès
*	=> Utilisation de la classe de connexion àa la base de données
*	=> Gestion du produit
*
*	02/02/2009 GHX
*		- modification des requetes SQL pour mettre certaines valeurs entre cote [REFONTE CONTEXTE]
*	17/07/2009 GHX
*		- Correction du BZ 10658 [REC][Mapping/counter activation]: désactivation compteur
*	22/07/2009 GHX
		- Correction du BZ 10655 [REC][Mapping/counter activation]: boutons de changements de famille non fonctionnelle
	30/07/2009 GHX
		- Correction d'un problème quand on désactive plusieurs compteur en même temps (plantage d'une requete SQL)
	04/01/2010 GHX
		- Correction du BZ 13180 [REC][T&A CB 5.0.2]: les compteurs capture_duration sont visibles
		- Correction du BZ 13595 [REC][CB 5.0.1.7][Mixed KPI][MAPPING][TC#36833]: erreur sur une famille ne contenant pas de compteur

       05/08/2010  MPR :
                - Correction du BZ 17206 - Activation d'un compteur,On passe également le champs new_field à 1
 *     14/09/2010 NSE bz 17825 : double-clic sur compteur non fonctionnel si retour à la ligne dans commentaire
 *     14/01/2011 NSE bz 19672 : on empêche la désactivation d'un compteur utilisé dans une alarme
 *	17/02/2015 JLG bz 45818 : add id to list if label is empty
*/
?>
<?
/*
*	@cb22014@
*
*	23/07/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- maj 07/08/2007 - Jérémy : 	Ajout d'une condition pour afficher l'icone de retour au choix des familles
*					Si le nombre de famille est supérieur à 1 on affiche l'icône, sinon, on la cache
*	- maj 10/08/2007 - Jérémy : 	Bug 618 : Désactivation d'un compteur qui éxiste dans 2 familles impossible
*					MEME SI l'une des deux famille ne l'utilise nullpart dans une formule de KPI
*						Mise à jour des message d'erreur, intégration dans la table message_display
*	- maj 13/08/2007 - Jérémy : 	Ajout du paramètre "show_modify_link" qui prend TRUE pour afficher le lien qui permet de modifier les compteurs
*					Le paramètre a été ajouté dans la class "FPSplitSelectWithToolTip"
*	- maj 14/08/2007 - Jérémy : 	Bug 619 : Le filtre de la requête est affiné de façon à ne pas écarter des compteurs qui ne sont utilisés dans aucun KPI
*
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
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*/
?>
<?
/*
*	@cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
*/
?>
<?
/*
*	@cb1300_iu2000b_pour_cb2000b@
*
*	19/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?php
/*
*	@cb1300b_iu2000b_070706@
*
*	12/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0b
*
*	Parser version iu_2.0.0.0b
*/
/*
	- maj 17/07/2006, xxx : verification qu'un compteur qui doit être desactivé n'est pas présent dans un KPI
	- maj 24/08/2006, xxx : correction erreur d'ortographe + comparaison des compteurs et des formules en mettant    tout en minuscule pour ne pas avoir de pb avec la casse
	- maj 06/11/2006, benoit : ajout d'un parametre dans l'appel de 'FPSplitSelectWithToolTip()' pour prendre en     compte le double click sur les elements du select
    - maj 28/02/2007 Gwénaël : ajout d'une condition pour que les compteurs qui devront être supprimer lors du prochain retrieve ne soient pas affichés
*/
?>
<?php
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Librairies et classes requises
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/select_family.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "context/class/ContextMount.class.php");

// Connexion à la base de données locale
$database = DataBase::getConnection();

// Intranet top + menu contextuel
include_once(REP_PHYSIQUE_NIVEAU_0 . "/intranet_top.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "/php/menu_contextuel.php");

// 27/07/2010 OJT : Correction bz16899, mise en commentaire du contrôle d'accès
/*// Contrôle d'accès
$userModel = new UserModel($_SESSION['id_user']);
$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu ILIKE 'Counters Activation'";
$result = $database->getRow($query);
$idMenu = ($result['id_menu'] == '') ? 0 : $result['id_menu'];
if(!$userModel->userAuthorized($idMenu)) {
	echo '<div class="errorMsg">'.__T('A_USER_NOT_ALLOWED_TO_ACCESS').'</div>';
	exit;
}*/

// Sélection de la famille et du produit
if (!isset($_GET["family"])) {
    $select_family = new select_family($_SERVER['PHP_SELF'], $_SERVER['argv'][0], 'Counter activation');
    exit;
}

// Connexion à la base de données produit
$database = DataBase::getConnection( $_GET['product'] );

// 13/12/2011 ACS BZ 24853 Check if files have been retrieved but not computed yet
$computeToRun = (count(MasterModel::getTimeToCompute($_GET['product'])) > 0);
// 18/07/2012 BBX
// BZ 24853 : pour les produits horaires, seuls les computes hourly comptent
$productModel = new ProductModel($_GET['product']);
if($productModel->isHourly())
{
    $computeToRun = false;
    foreach(MasterModel::getTimeToCompute($_GET['product']) as $sysToCompute) {
        if($sysToCompute['timeType'] == 'hour') {
            $computeToRun = true;
            break;
        }
    }
}
?>
<script>
	// On passe l'id produit à JS
	var product = '<?=$_GET['product']?>';
</script>
<table width="778" align="center" valign=middle cellpadding="0" cellspacing="2" class="tabPrincipal">
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td align="center">
			<img src="<?=NIVEAU_0?>images/titres/counter_selection_interface_roaming_titre.gif"/>
		</td>
	</tr>
	<tr>
		<td align="center" valign="middle" class="texteGris" style="padding-top:5px;padding-bottom:5px;text-align:center;">
		<?php
			// Recuperation du label du produit
			$productInformation = getProductInformations($product);
			$productLabel = $productInformation[$product]['sdp_label'];
			echo $productLabel."&nbsp;:&nbsp;";

			// Recuperation du label de la famille
			$family_information = get_family_information_from_family($family,$_GET['product']);
			echo (ucfirst($family_information['family_label']));

			// MàJ 07/08/2007 - JL :  Ajout condition d'affichage de l'icone
			// 10:11 22/07/2009 GHX
			// Correction du BZ 10655 [REC][Mapping/counter activation]: boutons de changements de famille non fonctionnelle
			// Ajout de l'id produit dans l'url
			if (get_number_of_family(false,$_GET['product']) > 1){ ?>
				<a href="mapping_raw_counters_selection_index.php?product=<?php echo $_GET['product']; ?>" target="_top">
					<img src="<?=NIVEAU_0?>images/icones/change.gif" onMouseOver="popalt('Change family');style.cursor='help';" onMouseOut='kill()' border="0" style="vertical-align:middle;"/>
				</a>
		<? 	} //fin condition sur les familles ?>
		</td>
	</tr>
	<tr>
		<td style="padding: 2px;">
<?php
// 1. si un groupe a été choisi, et que des données ont été envoyées, on traite les données
if ( isset( $_POST['submit'] ) )
{
    $chosen_fields = explode('||', $chosen_fields);
    $chosen_fields[count($chosen_fields)] = $chosen_fields[0]; // pour ne pas "perdre" la valeur [0] dans le array_flip()
    $chosen_fields = array_flip($chosen_fields);
    // On récupère les données vennat de l'URL.
    $family = $_GET["family"];
    // On enregistre dans la BD pour tous les groupes qui sont de la famille roaming et visible =1.
    // Sélectionne l'id_groupe_table du premier groupe table trouvé de la famille roaming.
    $query_groupe = "SELECT id_ligne FROM sys_definition_group_table WHERE visible = 1 AND family = '{$family}'";
    // echo $query_groupe;
    $result_groupe = $database->execute($query_groupe);
    $nb = $database->getNumRows();
    // echo "   nb enregistrement trouvé :  ".$nb;
    if ($nb == 0) {
        echo "<tr><td align=\"center\">";
        echo "<font style=\"font : normal 9pt Verdana, Arial, sans-serif; color : #585858;s\"><b>" . __T('A_MAPPING_COUNTERS_NO_DATA_FOUND') . "</b></font>";
        echo "</td></tr>";
        exit;
    }
    $result_array_groupe = $database->getRow($query_groupe);

	// BZ 13354
	// 02/03/2010 BBX : ajout d'un contrôle pour ne pas activer plus de 1570 compteurs

	// Label de la famille
	$queryFamilyLabel = "SELECT family_label FROM sys_definition_categorie WHERE family = '$family'";
	$familyLabel = $database->getOne($queryFamilyLabel);

	// 08/04/2010 BBX : On récupère le nombre maximum de compteurs dans les paramètres. BZ 13354
	$maxCounters = get_sys_global_parameters('maximum_mapped_counters', 1570, $_GET['product']);

	if(count($chosen_fields) > 1570)
	{
		echo '<div class="errorMsg">'.__T('A_E_CONTEXT_MOUNT_LIMIT_NB_COUNTER_EXCEEDED',1570,'counters',$familyLabel,(count($chosen_fields)).' in your list').'</div>';
	}
	// 08/04/2010 BBX : On test le nombre maximum de compteurs dans les paramètres. BZ 13354
	elseif(count($chosen_fields) > $maxCounters)
	{
		echo '<div class="errorMsg">'.__T('A_E_CONTEXT_MOUNT_LIMIT_NB_COUNTER_EXCEEDED',$maxCounters,'counters',$familyLabel,(count($chosen_fields)).' in your list').'</div>';
	}
	// FIN BZ 13354
	else
	{
		// on selectionne les valeurs de on_off
        $query = " SELECT id_ligne,on_off,new_field FROM sys_field_reference WHERE id_group_table=" . $result_array_groupe["id_ligne"] . " and visible=1 ";
		$result = $database->execute($query);
		$nombre_resultat = $database->getNumRows();
        if ($nombre_resultat > 0)
        {
            /*
             * Avant d'effectuer la moindre opération, on vérifié si tous les
             * compteurs vont pouvoir être activables (limite 1600 colonnes).
             */
            $nbRawsToActivate    = 0; // Nombre d'éléments à activer
            $nbRawsToDesactivate = 0; // Nombre d'éléments à désactiver
            $nbCountersLeft      = intval( $_POST['nbCountersLeft'] );
            $nbTaRemainingCols   = intval( $_POST['nbTaRemainingCols'] );
            $nbPgRemainingCols   = intval( $_POST['nbPgRemainingCols'] );
            foreach ( $database->getAll( $query ) as $res )
            {
                if ( $res['on_off'] == 0 && $chosen_fields[$res['id_ligne']] )
                {
                    // On incrémente le nombre de compteur à activer
                    $nbRawsToActivate++;
                }
                else if( ( ( $res['on_off'] == 1 ) && ( $res['new_field'] != 2 ) ) && !$chosen_fields[$res['id_ligne']] )
                {
                    // On incrémente le nombre de compteur à désactiver
                    $nbRawsToDesactivate++;
                }
            }

            // Si le nombre de colonnes à ajouter est inférieur au nombre de colonnes PG restantes
            // et si le nombre de RAWs à ajouter à inférieur à la limite de T&A
            if( ( $nbRawsToActivate <= $nbPgRemainingCols ) && ( ( $nbRawsToActivate - $nbRawsToDesactivate ) <= $nbTaRemainingCols ) )
            {
                foreach( $database->getAll( $query ) as $res )
                {
                    // 10/03/2011 OJT : bz20811, ajout du test sur le new_field (new_field=2 et on_off=1 est un RAW désactivé).
                    if ( ( $res['on_off'] == 1 ) && ( $res['new_field'] != 2 ) )
                    {
                        if ( !$chosen_fields[$res["id_ligne"]] ) // Il s'agit d'une désactivation
                        {
                        	// 28/05/2013 GFS - Bug 33864 - [SUP][TA Cigale GSM][MTN Iran][AVP 34007]: Raw activated by customer are deactivated during the upgrade
                        	// FIX BZ#33864 BEGIN 
                        	$ret = RawModel::desactivateCounter($res["id_ligne"], $family, ($res["new_field"]!=1)/*pas la peine de demander le undeploy si il n'a pas encore été déployé*/, $_GET['product']);
                            switch ($ret[0]) {
                            	case RawModel::$IFDOWN_COUNTER_OK:
                            		// Counter is desactivated
                            		break;
                            	case RawModel::$IFDOWN_COUNTER_KO_MIXED_KPI:
                            		// Mixed KPI failed
                            		echo "<span class='texteRouge'>" . $ret[1] . "</span><br>";
                            		break;
                            	case RawModel::$IFDOWN_COUNTER_KO_QUERY_QB:
                            		// Counter uses in a saved query from query builder
                            		echo "<span class='texteRouge'>". $ret[1] ."</span><br>";
                            		break;
                            	case RawModel::$IFDOWN_COUNTER_KO_GRAPH:
                            		// Graph use
                            		echo '<span class="texteRouge">'.$ret[1];
                            		echo '<ul>';
                            		foreach($ret[2] as $idGTM) {
                            			$gtmModel = new GTMModel($idGTM['id_page'], $_GET['product']);
                            			$gtmProperties = $gtmModel->getGTMProperties();
                            			echo '<li>'.$gtmProperties['page_name'].'</li>';
                            		}
                            		echo '</ul></span>';
                            		break;
                            	case RawModel::$IFDOWN_COUNTER_KO_ALARM:
                            		// Alarm use
                            		print "<span class='texteRouge'>" . $ret[1] . "</span><br>";
                            		break;        
                            	case RawModel::$IFDOWN_COUNTER_KO_DATA_EXPORT:
                            		// Data export use
                            		print "<span class='texteRouge'>" . $ret[1] . "</span><br>";
                            		break;
                            }
                            // FIX BZ#33864 END
                    	}
                    }
                    else
                    {
                        if ( $chosen_fields[$res['id_ligne']] ) // Il s'agit d'une activation
                        {
                            $newFieldToUse = 1; // Valeur du new_field à utiliser
                            if( $res['new_field'] == 2 ){
                                /*
                                 * Si le compteur à activer n'avait pas été encore
                                 * désactiver, on passe juste le new field à 0
                                 */
                                $newFieldToUse = 0;
                            }

                            // Exécution de la requête de mise à jour de sys_field_reference (18/03/2011)
                            // 28/05/2013 GFS - Bug 33864 - [SUP][TA Cigale GSM][MTN Iran][AVP 34007]: Raw activated by customer are deactivated during the upgrade
                            if ($database->columnExists("sys_field_reference", "owner")) {
                            	$query = "UPDATE sys_field_reference SET on_off=1, new_field={$newFieldToUse}, owner=".ContextMount::$OWNER_CUSTOMER." WHERE id_ligne='{$res['id_ligne']}'";
                            }
                            else {
                            	$query = "UPDATE sys_field_reference SET on_off=1, new_field={$newFieldToUse} WHERE id_ligne='{$res['id_ligne']}'";
                            }
                            $database->execute( $query );
                        }
                    }
                } // end foreach
            }
            else
            {
                echo '<div class="errorMsg">'.__T('A_E_CONTEXT_MOUNT_LIMIT_NB_COUNTER_EXCEEDED_SIMPLE','counters',$familyLabel).'</div>';
            }
        }
    }
}
unset($right_ids);
unset($les_champs);
unset($les_tips);
// 3. si un groupe a été choisi, on affiche le formulaire de choix
$family = $_GET["family"];
// On récupère les id de tous les groupes qui sont de la famille roaming et qui ont le champ visible à 1.
$query_groupe = "SELECT id_ligne FROM sys_definition_group_table WHERE visible=1 AND family='$family' ";
$result_groupe = $database->execute($query_groupe);
$result_nb_groupe = $database->getNumRows();
if ($result_nb_groupe == 0) { // Affichage du message d'erreur.
    echo "<tr><td align=\"center\">";
    echo "<font style=\"font : normal 9pt Verdana, Arial, sans-serif; color : #585858;s\"><b>" . __T('A_MAPPING_COUNTERS_NO_DATA_FOUND') . "</b></font>";
    echo "</td></tr>";
    exit;
}
// for ($k = 0;$k < $result_nb_groupe;$k++){
$result_array_groupe = $database->getRow($query_groupe);
$id_ligne = $result_array_groupe["id_ligne"];
//modif 28/02/2007 Gwénaël
    // Ajout de la condition new_field != 2 afin que les compteurs virtuellement supprimer ne soient pas affichés
$query = "SELECT id_ligne,new_field,
CASE WHEN edw_field_name_label IS NULL THEN '('||edw_field_name||')' ELSE edw_field_name_label END as edw_field,
on_off,comment FROM sys_field_reference
WHERE id_group_table=" . $id_ligne . " AND visible=1"; // AND new_field != 2";
// 15:30 04/01/2010 GHX (bonne année)
// Correction du BZ 13180
if( MixedKpiModel::isMixedKpi($_GET['product']) )
{
	$query .=" AND edw_field_name NOT IN ('capture_duration', 'capture_duration_expected')";
}
$query .=" ORDER BY edw_field ASC";
$result = $database->execute($query);
$nombre_resultat = $database->getNumRows();

// Recherche des informations sur le nombre de compteurs encore activable (bz20811)
$maxMappedCounters = get_sys_global_parameters( 'maximum_mapped_counters', -1 );
$nbCountersLeft    = 0;
$counterLeftMsg    = "";
if( $maxMappedCounters !== -1 )
{
    // Calcul du nombre de colonnes pouvant encore être créées (par T&A ET Postgresql)
    $famModelObj = new FamilyModel( $_GET["family"], $_GET['product'] );
    $rawModelObj = new RawModel();
    $maxCol      = $famModelObj->getMaxNumberOfColumns( 'raw' );

    // Nombre de colonnes que PG peut encore créer
    $nbPgRemainingCols = 1600 - ( $maxCol + $rawModelObj->getNbRawKpiToDeployed( $database, $_GET['family'] ) );

    // Nombre de colonnes encore autorisées par T&A
    $nbTaRemainingCols = ( $maxMappedCounters - $rawModelObj->getNbEnabledRawKpi( $database, $_GET['family'] ) );

    // Determination du nombre à afficher dans le message d'information
    $nbCountersLeft    = max( min( $nbPgRemainingCols, $nbTaRemainingCols ), 0 );
    $counterLeftMsg    = "({$nbCountersLeft} more counter(s) can still be activated)";

    // Il y a t-il un message supplémentaire à ajouter (afin de préciser le nombre de colonnes
    // libérées par la prochaine opération de maintenance
    $maxDropCol = $famModelObj->getMaxNumberOfDroppedColumns( 'raw' );
    if( $maxCol >= 1500 && $maxDropCol >= 100 )
    {
        // 30/06/2011 BBX
        // Modification du texte
        // BZ 22263
        $nbColAv = min( $maxDropCol, $nbTaRemainingCols );
        $counterLeftMsg .= "<br />({$nbColAv} counters will be available on next daily operation)";
    }
}

if ($nombre_resultat > 0) {
    $array_result = array(); // array temp servant de tampon
    $les_champs = array(); // array contenant la liste des champs : key = id_ligne , val = nms_field_name
    $les_tips = array(); // array contenant la liste des tips : key = id_ligne , val = tips
    $right_ids = array(); // array simple contenant les id des champs présents dans la colonne de droite
	foreach($database->getAll($query) as $dataArray) {
		$les_champs[$dataArray['id_ligne']] = $dataArray['edw_field']!=null?$dataArray['edw_field']:$dataArray['id_ligne'];
		// 07/01/2010 GHX : Ajout du htmlentities
        // 14/09/2010 NSE : bz 17825, on remplace le retour à la ligne
        // 10/03/2011 OJT : bz 20811, ajout du test sur le new_field
		$les_tips["'".$dataArray['id_ligne']."'"] = htmlentities(preg_replace('/[\n\r]+/', ' ', $dataArray['comment']));//htmlentities($dataArray['comment']);//
		if( $dataArray['on_off'] == 1 && $dataArray['new_field'] != 2 ) {
			 $right_ids[] = $dataArray['id_ligne'];
		}
	}

    // Initialize the phpObjectForms class
    // require $repertoire_physique_niveau0."class.phpObjectForms/lib/FormProcessor.class.php";
    require_once REP_PHYSIQUE_NIVEAU_0 . "class.phpObjectForms/lib/FormProcessor.class.php";
    $fp = new FormProcessor(REP_PHYSIQUE_NIVEAU_0 . "class.phpObjectForms/lib/");
    $fp->importElements(array("FPButton", "FPHidden", "extra/FPSplitSelectWithToolTip"));
    $fp->importLayouts(array("FPColLayout", "FPRowLayout"));
    // Create the form object
    $myForm = new FPForm
    (
        array(
            "name"                => 'myForm',
            "action"              => "mapping_raw_counters_selection_index.php?family=".$family."&product=".$_GET['product'],
            "display_outer_table" => true,
            "table_align"         => 'center'
        )
    );

    $myForm->setBaseLayout(
        new FPColLayout
        (
            array(
                "table_padding" => 5,
                "element_align" => "center",
                "elements"      => array(
                    new FPHidden(array("name" => "nbCountersLeft","value" => $nbCountersLeft)),
                    new FPHidden(array("name" => "nbTaRemainingCols","value" => $nbTaRemainingCols)),
                    new FPHidden(array("name" => "nbPgRemainingCols","value" => $nbPgRemainingCols)),
				new FPHidden(array("name" => "id_group_table","value" => $id_group_table)),
				new FPHidden(array("name" => "window_open","value" => "mapping_raw_counters_label_comment_popup.php")),
                    new FPSplitSelectWithToolTip
                    (
                        array(
                            "name"             => "chosen_fields",
                            "form_name"        => 'myForm',
                            "multiple"         => true,
                            "size"             => 16,
                            "options"          => $les_champs,
                            "option_tips"      => $les_tips,
                            "left_title"       => "<span class='texteGrisBold'>Counters</span>",
                            "right_title"      => "<span class='texteGrisBold'>Selected Counters</span><br /><span class='texteGrisPetit'>{$counterLeftMsg}</span>",
                            "right_ids"        => $right_ids,
                            "css_style"        => "width:300px;",
                            "table_padding"    => 5,
                            "dbl_click"        => true,	// 06/11/2006 - Modif. benoit
							"show_modify_link" => true,	// 13/08/2007 - Modif Jérémy
                            "forbidLeftToRight" => $computeToRun,
                            "forbidRightToLeft" => $computeToRun // 19/07/20152 BBX BZ 24853 on vérouille également la suppression
                        )
                    ),
                    new FPRowLayout
                    (
                        array(
                            "table_align"   => "center",
                            "table_padding" => 20,
                            "elements"      => array(
                                new FPButton
                                (
                                    array(
                                        "submit"    => true,
                                        "name"      => 'submit',
                                        "title"     => '  Save   ',
                                        "css_class" => 'bouton',
                                )
                                ),
                    )
                        )
                    ),
                )
            )
        )
        );
    $myForm->display();

	// 13/12/2011 ACS BZ 24853 Check if files have been retrieved but not computed yet
	if ($computeToRun) {
    	echo '<center><span class="texteRouge">'.__T('A_COUNTER_ACTIVATION_FORBIDDEN_RETRIEVE_COMPUTE').'</span></center>';
	}
    // 02/05/2011 OJT : Déplacement du message d'avertissement 'Automatic Mapping' vers 'Counters Activation'
    echo '<center><span class="texteRouge">'.__T('A_MAPPING_COUNTERS_HISTORY_WARNING').'</span></center>';

} else {
    echo '<div align="center">No fields were found in that group.</div>';
}
?>


		</td>
	</tr>
</table>
<br />
<script>
// 11/12/2009 BBX : ajout du style bouton sur le lien "modify". BZ 13279
// 15:45 04/01/2010 GHX
// Correction du BZ  13595 : ajout de la condition
if ( $('ouvrir_tooltip') ) $('ouvrir_tooltip').className = 'bouton';
</script>

</body>
</html>
