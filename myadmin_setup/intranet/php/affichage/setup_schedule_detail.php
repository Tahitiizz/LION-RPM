<?php
/**
 * 
 *  CB 5.2
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 */
?><?
/*
*	@cb41000@
*
*	11/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	11/12/2008 BBX : modifications pour le CB 4.1 :
*	=> Utilisation des nouvelles méthodes et constantes
*	=> Contrôle d'accès
*	=> Utilisation de la classe de connexion àa la base de données
*	=> Réécriture de la requête qui récupère les infos des rapports
*
*	29/01/2009 GHX
*		- modification des requêtes SQL pour mettre id_groupe & id_user entre cote au niveau des inserts  [REFONTE CONTEXTE]
*	15/07/2009 GHX
*		- Correction du BZ 10569 [REC][CB 5.0][REPORTING] Rapport contenant des alarmes est considéré non configuré
*			-> Modification de la requete SQL qui permet de savoir si un rapport est configuré ou non
*			-> Ajout aussi du cas où le rapport est vide
*	27/07/2009 MPR
*		- Correction B10592 
*			->  On check que le mail est bien valide 
*			-> Création de la fonction verifMail()
*	04/08/2009 MPR bz 10844 : On récupère la valeur enregistrée en base si elle existe
*      20/09/2010 NSE bz 17109 : deux sauvegardes de suites pas possibles.
*  06/01/2011 NSE bz 19128 : ajout d'un message pour indiquer que les alames sont toujours générées en pdf
*  01/03/2011 MMT bz 19128: elaboration du message pour indiquer les slaves ne supportant pas l'export alarme non-pdf
*/
?>
<?
/*
*	@cb40002@
*
*	14/04/2008 - Copyright Astellia
*
*	Composant de base version cb_4.0.0.12
*	
*	maj 14/04/2008  - benjamin :  Landscape doit toujours être à l'offset 2. A jout de '2=>' pour la valeur landscape et '1=>' pour la valeur portait. BZ6275
*	maj 14/04/2008 - benjamin : landscape => valeur 2 et non 1. BZ6275
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
*	maj 13/12/2007 - maxime :  L'utilisateur choisit si les fichiers seront en pièces jointes ou téléchargeable depuis un lien dans le mail
*	maj 07/01/2007 - maxime : L'utilisateur choisit le type de fichier du rapport ( word, excel et pdf )
*/
?>
<?
/*
*	@cb30000@
*
*	20/07/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- maj 25/07/2007 jérémy : 	suppression de la popup, le contenu de cette page va être intégré dans l'aplication même
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
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
*	@cb20040@
*
*	30/11/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.40
*/
?>
<?php
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
	- maj le 04 05 2006 :  comme une TA=hour ne fonctionne pas, hour est inhibée ligne 255 (demande de cyrille)
	- maj le 04 05 2006 : delta entre la version 1191 et la version 1200 (écrasement du fichier venant de la version 1200)
	- 24-08-2006 : impossibilité de saisir 2 fois le même nom pour un schedule
	- 25-09-2006 xavier : possibilité de saisir le même nom pour un schedule déja défini (ligne 397)
                        le message '(not configured)' apparait en face des rapports dont au moins un sélecteur n'est pas défini (ligne 143)

Cette page permet de CREER et MODIFIER un schedule.
On est en mode EDITION, si $schedule_id est supérieur à zero.

Arguments reçus par cette page :
	$schedule_id = l'id du schedule à éditer. Si NULL ou ==0, on est en mode création de schedule.


Les tables de données impliquées sont :
	Lecture :
	- users
	- sys_user_group
	- sys_pauto_page_name

	Lecture / Ecriture :
	- sys_report_schedule
	- sys_report_sendmail


Plan du script :
	0.	Includes, En tête ...
	1.	Initialise les valeurs si on est en mode edition
	2.	Creation de la structure logique du formulaire (avec PhpObjectForm)
	3.	Creation du LAYOUT du formulaire (avec PhpObjectForm)
	4.	Traitement des données issues du formulaire
		4.1	Modif d'un schedule deja existant			-> sys_report_schedule
		4.2 Creation d'un nouveau schedule				-> sys_report_schedule
		4.3 Gestion des abonnés							-> sys_report_sendmail
			- groupes
			- utilisateurs
			- emails

--
Stephane Le Solliec -- stephane@metacites.net
02/06/2005

*/

session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Connexion à la base de données locale
// 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
$database = Database::getConnection();

// Intranet top + menu contextuel
include_once(REP_PHYSIQUE_NIVEAU_0 . "/intranet_top.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "/php/menu_contextuel.php");

// 27/07/2010 OJT : Correction bz16899, mise en commentaire du contrôle d'accès
/*// Contrôle d'accès
$userModel = new UserModel($_SESSION['id_user']);
$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu ILIKE 'Reporting'";
$result = $database->getRow($query);
$idMenu = ($result['id_menu'] == '') ? 0 : $result['id_menu'];
if(!$userModel->userAuthorized($idMenu)) {
	echo '<div class="errorMsg">'.__T('A_USER_NOT_ALLOWED_TO_ACCESS').'</div>';
	exit;
}*/

/**
* Fonction qui check la validité du mail
* @param string $mail : 
*/
function verifMail($mail) {

	if($mail == ""){
		$check = false;
	}else{
	
	if(ereg( "^[^@  ]+@([a-zA-Z0-9\-]+\.)+([a-zA-Z0-9\-]{2}|net|com|gov|mil|org|edu|int)\$",$mail) )
 		return true;
 	else
 		return false;
	}
	return $check;
}
//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
// 1. Formulaire d'edition : on peuple les valeurs du formulaire correctement
//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
// au cas où on a reçu un $schedule_id > 0
// 21/11/2011 BBX
// BZ 24764 : correction des messages "Notice" PHP
// 29/11/2011 BBX
// BZ 24887 : correction de l'édition des schedules
$schedule_id = $_GET['schedule_id'];
if (!empty($schedule_id)){
	$query = "SELECT * from sys_report_schedule where schedule_id='$schedule_id'";
    $result = $database->execute($query);
    $nb_result = $database->getNumRows();
    if ($nb_result) {
        $mySchedule = $database->getRow($query);
        $report = array($mySchedule['report_id']);
    } else {
        $schedule_id = 0; // on se met en mode création de schedule --> formulaire vide
    }
}

//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
// 2. On cree la structure logique du formulaire
//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

// Initialize the phpObjectForms class
require_once REP_PHYSIQUE_NIVEAU_0 . "class.phpObjectForms/lib/FormProcessor.class.php";
$fp = new FormProcessor(REP_PHYSIQUE_NIVEAU_0 . "class.phpObjectForms/lib/");
$fp->importElements(array("FPButton", "FPHidden", "FPRadio", "FPSelect", "FPText", "FPTextField", "extra/FPSplitSelect", "extra/FPHalfSplitSelect"));
$fp->importLayouts(array("FPColLayout", "FPRowLayout", "FPGridLayout"));
$fp->importWrappers(array("FPLeftTitleWrapper"));
$leftWrapper = new FPLeftTitleWrapper(array());

// Create the form object
$myForm = new FPForm(array(
        // "title" => '<span class="font_12_b">Report Setup Interface</span>',
        "name" => 'myForm',
        "action" => $_SERVER["PHP_SELF"],
        "display_outer_table" => true,
        "table_align" => 'center',
        "enable_js_validation" => false,
        ));
		
// We create the form elements  -- FORM DATA STRUCTURE
// schedule id
$form_element_schedule_id = new FPHidden(array("name" => 'schedule_id',
        "value" => '0'
        ));

// schedule name
// 21/11/2011 BBX
// BZ 24764 : correction des messages "Notice" PHP
if (!empty($mySchedule)) $form_element_schedule_id->setValue($mySchedule['schedule_id']);

$form_element_schedule_name = new FPTextField(array("title" => '<span class="texteGris">'.__T('A_TASK_SCHEDULER_SCHEDULE_SETUP_SCHEDULE_NAME').'</span>',
        "name" => 'schedule_name',
        "required" => true,
        "valid_RE" => FP_VALID_NAME,
        "css_style" => 'width:300px;',
        "wrapper" => &$leftWrapper,
        ));
		
// 21/11/2011 BBX
// BZ 24764 : correction des messages "Notice" PHP
if (!empty($mySchedule)) $form_element_schedule_name->setValue($mySchedule['schedule_name']);

//maj 13/12/2007 - maxime :  L'utilisateur choisit si les fichiers seront en pièces jointes ou téléchargeable depuis un lien dans le mail
// join into file
$checked = true;
// 21/11/2011 BBX
// BZ 24764 : correction des messages "Notice" PHP
if (!empty($mySchedule)){
	$checked = ($mySchedule['join_into_mail'])? true : false;
}

$form_element_scheduler_content_radio[0] = new FPRadio(array("group_name" => 'scheduler_radio',
                "item_value" => 'y',
                "title" => "<span class=texteGris>Files attached</span>",
                "checked" => $checked,
				"left_title" => true, // permet d'afficher le titre à gauche du bouton radiobox
                ));

$form_element_scheduler_content_radio[1] = new FPRadio(array("group_name" => 'scheduler_radio',
                "title" => "<span class=texteGris>Html link to files</span>",
				"item_value" => 'n',
                "checked" => !$checked,
				"left_title" => true,
                ));

// Création d'une grille Layout pour afficher les deux radiobox
$form_layout_scheduler_containt = new FPGridLayout(array("table_padding" => 1,
        "columns" => 2,
        ));
$form_layout_scheduler_containt->addElement($form_element_scheduler_content_radio[0]);
$form_layout_scheduler_containt->addElement($form_element_scheduler_content_radio[1]);


// maj 07/01/2007 - maxime : L'utilisateur choisit le type de fichier du rapport ( word, excel et pdf )	
// type of file		
$types['value'] = array(0=>'Pdf',1=>'Word',2=>'Excel');
$types['icones'] = array(0=>NIVEAU_0."images/icones/page_white_acrobat.png",1=>NIVEAU_0.'images/icones/page_white_word.png',2=>NIVEAU_0.'images/icones/page_white_excel.png');
// echo "<img src='/images/icones/page_white_acrobat.png' border='0' alt=''/>"; 
// Création d'une grille Layout pour afficher les deux radiobox
$form_layout_scheduler_type_file = new FPGridLayout(array("table_padding" => 1,
        "columns" => 3,
 ));

$leftWrapper = new FPLeftTitleWrapper(array());


foreach($types['value'] as $k=>$type){
	$second_title = "";
	if($k==0){
		$second_title = "<span class='texteGris'>Files type  :</span>";
	}
        // 21/11/2011 BBX
        // BZ 24764 : correction des messages "Notice" PHP
	if(!empty($mySchedule))
		$checked = (strtolower($type) == $mySchedule['type_files']) ? true : false;
	else
		$checked = ($type == 'Pdf') ? true : false;
	$form_element_scheduler_type_files[$k] = new FPRadio(array("group_name" => 'scheduler_type_files',
                "title" => "&nbsp;<img src='".$types['icones'][$k]."' alt=''/>&nbsp;",
				"item_value" => $types['value'][$k],
                "checked" => $checked,
				// "nowrap_title" => 1,
                "second_title" => $second_title,
                // 06/01/2011 NSE bz 19128 : ajout d'un message pour indiquer que les alames sont toujours générées en pdf
                // 21/01/2010 : remplacement onChange par OnClick
                "events" => array('onClick' => 'afficheRestriction(this.value)'),
                ));
	$form_layout_scheduler_type_file->addElement($form_element_scheduler_type_files[$k]);
}

// maj 14/04/2008  - benjamin :  Landscape doit toujours être à l'offset 2. A jout de '2=>' pour la valeur landscape et '1=>' pour la valeur portait. BZ6275
// Ajout de option

/*$options = array(
	'landscape'	=> "Landscape mode (one graph per page)",
	'portrait'		=> "Portrait Mode (two graphs per page)",
	'landscape4'	=> "Landscape mode (four graphs per page)",
	);
*/
$options = array(
	1 => "Landscape mode (one graph per page)",
	2 => "Portrait Mode (two graphs per page)",
	3 => "Landscape mode (four graphs per page)",
	);
	

$options_reversed = array(
	'landscape'	=> 1,
	'portrait'		=> 2,
	'landscape4'	=> 3,
	);
	
$form_element_scheduler_select_option = new FPSelect( array("name" => 'option_file',
        "title" => '<span class="texteGris">Option : </span>',
		"options" => $options,
		"multiple" => false,
		"selected" => array(),
        // "css_style" => 'width:270px;',
		"wrapper" => &$leftWrapper,
));

// maj 04/08/2009 - MPR : Correction du bug 10844 - On récupère la valeur enregistrée en base si elle existe
if( isset( $options_reversed[$mySchedule['display_mode']] ) )
{
	$form_element_scheduler_select_option->setValue( array($options_reversed[$mySchedule['display_mode']]) );
}

	

// SPLIT SELECTOR : report
// on va chercher la liste des reports dans sys_pauto_page_name
$list_all_reports = array();
// 14:17 15/07/2009 GHX
// Correction du BZ10569
// Modification de la requete SQL
$query = "
SELECT
	sppn.id_page,
	sppn.page_name,
	MIN((CASE WHEN sds_mode IS NULL AND class_object = 'page' THEN 0 ELSE 1 END)) as page_mode,
	COUNT(class_object) AS empty
FROM 
	sys_pauto_page_name AS sppn 
	LEFT JOIN sys_pauto_config AS spc ON (sppn.id_page = spc.id_page)
	LEFT JOIN sys_definition_selecteur AS sds ON (sppn.id_page = sds_report_id  AND sds_id_page = spc.id_elem)
WHERE 
	sppn.page_type = 'report' 
GROUP BY 
	sppn.id_page,
	sppn.page_name
";
foreach($database->getAll($query) as $current_report) {
	$list_all_reports[$current_report['id_page']] = $current_report['page_name'];
	if ($current_report['page_mode']!='1') $list_all_reports[$current_report['id_page']] .= ' (not configured)';
	// 14:37 15/07/2009 GHX
	// Ajout aussi du cas ou le rapport est vide
	if ($current_report['empty']=='0') $list_all_reports[$current_report['id_page']] .= ' (empty)';
}

// on construit le tableau des rapports choisis
if ($mySchedule['report_id']) {
	//la liste des rapport selectionnée ne prend en compte que les rapports existants. Ceux qui font partie du schedule mais ont été effacés de la liste des rapports ne sont pas affcihés.
	$list_reports=array_intersect(explode(',', $mySchedule['report_id']),array_keys($list_all_reports));

} else {
    $list_reports = array();
}
// we create the split select
$form_element_select_report = new FPSplitSelect(array("title" => "<span class='texteGrisBold'>".__T('A_TASK_SCHEDULER_SCHEDULE_SETUP_TITLE_REPORT_SELECTOR')."</span>",
        "name" => "report",
		"form_name" => "myForm",
        "multiple" => true,
        "size" => 8,
        "options" => $list_all_reports,
        "left_title" => "<span class='texteGris'>".__T('A_TASK_SCHEDULER_SCHEDULE_SETUP_LIST_TITLE_AVAILABLE_REPORT')."</span>",
        "right_title" => "<span class='texteGris'>".__T('A_TASK_SCHEDULER_SCHEDULE_SETUP_LIST_TITLE_SUSCRIBED_REPORT')."</span>",
        "right_ids" => $list_reports,
        "css_style" => "width:300px;",
        "table_padding" => 5,
        ));

// the split selector for groups
// we look for the list of all the groups in the db
$list_all_groups = array();
$query = "SELECT DISTINCT id_group,group_name FROM sys_user_group WHERE on_off=1 ORDER BY group_name ASC";
foreach($database->getAll($query) as $current_user) {
	$list_all_groups[$current_user['id_group']] = $current_user['group_name'];
}

// we look for the list of subscribed groups in the db
$list_subscribed_groups = array();
if ($mySchedule) {
    $query = "SELECT mailto from sys_report_sendmail where schedule_id='$schedule_id' and mailto_type='group'";
	foreach($database->getAll($query) as $current_user) {
		$list_subscribed_groups[] = $current_user['mailto'];
	}
}

// we create the split select
$form_element_group_selector = new FPSplitSelect(array("title" => "<span class='texteGrisBold'>".__T('A_TASK_SCHEDULER_SCHEDULE_SETUP_TITLE_GROUP_SELECTOR')."</span>",
        "name" => "to_groups",
		"form_name" => "myForm",
        "multiple" => true,
        "size" => 10,
        "options" => $list_all_groups,
        "left_title" => "<span class='texteGris'>".__T('A_TASK_SCHEDULER_SCHEDULE_SETUP_LIST_TITLE_AVAILABLE_GROUP')."</span>",
        "right_title" => "<span class='texteGris'>".__T('A_TASK_SCHEDULER_SCHEDULE_SETUP_LIST_TITLE_SUSCRIBED_GROUP')."</span>",
        "right_ids" => $list_subscribed_groups,
        "css_style" => "width:300px;",
        "table_padding" => 5,
        ));
// the split selector for users

// 02/10/2012 BBX
// BZ 29278 : Suppression des comptes invalides
// 05/10/2012 NSE bz 29278 : ajout du traitement du status (on/off)
$queryClean = "DELETE FROM sys_report_sendmail
    WHERE mailto IN (    
        SELECT id_user FROM users 
        WHERE CURRENT_DATE::text>substring(date_valid::text from 1 for 4)||'-'||substring(date_valid::text from 5 for 2)||'-'||substring(date_valid::text from 7 for 2)
        OR on_off=0 OR on_off ISNULL
    ) ";
$database->execute($queryClean);

// we look for the list of all the users in the db
$list_all_users = array();
// 07/06/2011 BBX -PARTITIONING-
// Correction des casts
// 05/10/2012 NSE bz 29278 (reopen) : ajout du cas date courante = date limite de validité
$query = "SELECT id_user,username,login,user_mail FROM users WHERE on_off=1 and 
    ( CURRENT_DATE::text<substring(date_valid::text from 1 for 4)||'-'||substring(date_valid::text from 5 for 2)||'-'||substring(date_valid::text from 7 for 2) 
     OR CURRENT_DATE::text = substring(date_valid::text from 1 for 4)||'-'||substring(date_valid::text from 5 for 2)||'-'||substring(date_valid::text from 7 for 2)
    )
    ORDER BY username ASC";
foreach($database->getAll($query) as $current_user) {
	$list_all_users[$current_user['id_user']] = $current_user['login'] . ' - ' . $current_user['username'];
}

// we look for the list of subscribed users in the db
$list_subscribed_users = array();
if ($mySchedule) {
    $query = "SELECT mailto from sys_report_sendmail where schedule_id='$schedule_id' and mailto_type='user'";	
	foreach($database->getAll($query) as $current_user) {
		$list_subscribed_users[] = $current_user['mailto'];
	}
}

// we create the split select
$form_element_user_selector = new FPSplitSelect(array("title" => "<span class='texteGrisBold'>".__T('A_TASK_SCHEDULER_SCHEDULE_SETUP_TITLE_USER_SELECTOR')."</span>",
        "name" => "to_users",
		"form_name" => "myForm",
        "multiple" => true,
        "size" => 10,
        "options" => $list_all_users,
        "left_title" => "<span class='texteGris'>".__T('A_TASK_SCHEDULER_SCHEDULE_SETUP_LIST_TITLE_AVAILABLE_USER')."</span>",
        "right_title" => "<span class='texteGris'>".__T('A_TASK_SCHEDULER_SCHEDULE_SETUP_LIST_TITLE_SUSCRIBED_USER')."</span>",
        "right_ids" => $list_subscribed_users,
        "css_style" => "width:300px;",
        "table_padding" => 5,
        ));
// the HALF - split selector for emails

// we look for the list of subscribed emails in the db
$list_subscribed_emails = array();
if ($mySchedule) {
    $query = "SELECT mailto from sys_report_sendmail where schedule_id='$schedule_id' and mailto_type='email'";
	foreach($database->getAll($query) as $current_user) {
		$list_subscribed_emails[] = $current_user['mailto'];
	}
}

// we create the half split select
$form_element_email_selector = new FPHalfSplitSelect(array("title" => "<span class='texteGrisBold'>".__T('A_TASK_SCHEDULER_SCHEDULE_SETUP_TITLE_EMAIL_SELECTOR')."</span>",
        "name" => "to_emails",
	"form_name" => "myForm",
        "multiple" => true,
        "size" => 10,
        // "options" => $list_all_users,
        "left_title" => "<span class='texteGris'>".__T('G_PROFILE_FORM_LABEL_EMAIL')."</span>",
        "right_title" => "<span class='texteGris'>".__T('A_TASK_SCHEDULER_SCHEDULE_SETUP_LIST_TITLE_SUSCRIBED_EMAIL')."</span>",
        "right_ids" => $list_subscribed_emails,
        "css_style" => "width:300px;",
        "table_padding" => 5,
        ));

/*
			ATTENTION
			dans la query suivante on met and agregation <> 'hour' mais normalement une tA ne s'afficha pas quand pr visible = 0 et on_off = 0
			mais pr l'instant c la seule interface où hour n'a pas été testée.
		*/
$query = "SELECT agregation,agregation_label FROM sys_definition_time_agregation WHERE primaire=1 and visible=1 and on_off=1 ORDER BY agregation_rank";
foreach($database->getAll($query) as $row) {
    if ($row["agregation"] != "hour") {
        // radio buttons for the scheduler
        $form_element_scheduler_radio[$row["agregation"]] = new FPRadio(array("group_name" => 'scheduler',
                "item_value" => $row["agregation"],
                "title" => "<span class=texteGris>" . $row["agregation_label"] . "</span>",
                "checked" => false
                ));
    }
}

$weekday_array = array(1 => "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");
$form_element_scheduler_select_weekday = new FPSelect(array("name" => 'weekday',
        // "title" => 'Your Country',
        "multiple" => false,
        "options" => $weekday_array,
        // "selected" => array(4),
        "css_style" => 'width:100px;',
        ));
	
$day_array = array();
for($i = 1;$i < 32;$i++) $day_array[$i] = $i;
$form_element_scheduler_select_day = new FPSelect(array("name" => 'day',
        // "title" => 'Your Country',
        "multiple" => false,
        "options" => $day_array,
        // "selected" => array(4),
        "css_style" => 'width:100px;',
        ));
	
// mode edition
if ($mySchedule) {
    switch ($mySchedule['period']) {
        case 'hour':
            $form_element_scheduler_radio['hour']->setValue('hour');
            break;
        case 'day':
            $form_element_scheduler_radio['day']->setValue('day');
            break;
        case 'week':
            $form_element_scheduler_radio['week']->setValue('week');
            $form_element_scheduler_select_weekday->setValue(array($mySchedule['day']));
            break;
        case 'month':
            $form_element_scheduler_radio['month']->setValue('month');
            $form_element_scheduler_select_day->setValue(array($mySchedule['day']));
            break;
    }
}

// the submit button
$form_element_submit_button = new FPButton(array("submit" => true,
        "name" => 'submit',
        "title" => __T('G_FORM_BTN_SAVE'),
		"form_name" => 'myForm',
		"enable_js_validation" => true,
        "css_class" => 'bouton',
        ));
		
//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------		
		
// 3. On met en page le formulaire - FORM LAYOUT  pour pouvoir l'afficher. 

//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
	//On a besoin d'une grille pour le scheduler
	$form_layout_scheduler = new FPGridLayout(array("table_padding" => 4,
	        "columns" => 2,
	        ));

	foreach (array_keys($form_element_scheduler_radio) as $key) {
	    $form_layout_scheduler->addElement($form_element_scheduler_radio[$key]);
	    switch ($key) {
	        case 'hour':
	            $form_layout_scheduler->addElement(new FPText(array())); //remplis la grille avec un champ vide
	            break;
	        case 'day':
	            $form_layout_scheduler->addElement(new FPText(array())); //remplis la grille avec un champ vide
	            break;
	        case 'week':
	            $form_layout_scheduler->addElement($form_element_scheduler_select_weekday);
	            break;
	        case 'month':
	            $form_layout_scheduler->addElement($form_element_scheduler_select_day);
	            break;
	    }
	}
	// on a besoin d'ajouter un layout au bouton submit pour qu'il soit centré
	$form_layout_submit_button = new FPRowLayout(array("table_align" => "center",
	        "table_padding" => 20,
	        "elements" => array($form_element_submit_button)
	        ));
	// We define the complete form with a simple layout
    // 06/01/2011 NSE bz 19128 : ajout d'un message pour indiquer que les alames sont toujours générées en pdf
	// 01/03/2011 MMT bz 19128: elaboration du message pour indiquer les slaves ne supportant pas l'export alarme non-pdf
	include_once(REP_PHYSIQUE_NIVEAU_0 . "/class/Exporter.class.php");
	$unsupportedSlaves = Exporter::getUnsupportedXlsAndDocAlarmReportingSlaves();
	if(!empty($unsupportedSlaves)){
		$slaveNames = array();
		foreach ($unsupportedSlaves as $slave){
			$slaveNames[] = $slave['sdp_label'];
		}
		$warningMsg = __T("A_TASK_SCHEDULER_SCHEDULE_SETUP_FILE_FORMAT",implode(", ", $slaveNames));
	} else {
		$warningMsg = '';
	}

	$myForm->setBaseLayout(new FPColLayout(array("table_padding" => 0,
	            "table_cellspacing" => 2,
	            "element_align" => "left",
	            "elements" => array($form_element_schedule_id,
	                $form_element_schedule_name,
	                $form_layout_scheduler_containt,
					$form_title_scheduler_type_file,
					$form_layout_scheduler_type_file,
	                new FPText(array("text" => '<div class="texteGris" id="typeAlert" style="text-align:left;margin-left: 10px;color: red;'.(!isset($mySchedule)||$mySchedule['type_files']=='Pdf'?'display:none;':'').'">'.$warningMsg.'</div>')),
					$form_element_scheduler_select_option,
	                $form_element_select_report,
	                new FPText(array("text" => '<div class="texteGris" style="text-align:left;margin-top:20px;">'.__T('A_TASK_SCHEDULER_SCHEDULE_SETUP_SUSCRIBERS').'</div>')),
	                $form_element_group_selector,
	                $form_element_user_selector,
	                $form_element_email_selector,
	                new FPText(array("text" => '<div class="texteGris" style="text-align:left;margin-top:20px;">'.__T('A_TASK_SCHEDULER_SCHEDULE_SETUP_SCHEDULE').' <sup>*</sup></div>')),
	                $form_layout_scheduler,
	                $form_layout_submit_button,
	                )
	            ))
	    );

//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------	
// 4. On traite les données issues du formulaire
//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
if ($myForm->getSubmittedData() && $myForm->isDataValid()) {

	// Obtain submitted data and check the values correctness
	
	    $data_sent = $myForm->getElementValues();

	    switch ($data_sent['scheduler']) {
	        case 'hour':
	            $sched_time = 0;
	            $sched_day = 0;
	            break;
	        case 'day':
	            $sched_time = intval($data_sent['hour']);
	            $sched_day = 0;
	            break;
	        case 'week':
	            $sched_time = 0;
	            $sched_day = intval($data_sent['weekday']);
	            break;
	        case 'month':
	        default:
	            $sched_time = 0;
	            $sched_day = intval($data_sent['day']);
	            break;
	    }
			
		// maj 14/04/2008  - benjamin : landscape => valeur 2 et non 1. BZ6275
		// On récupère le type de mise en page des fichiers
		$display_mode_options = array(
			1 => 'landscape',
			2 => 'portrait',
			3 => 'landscape4',
		);
		
		$display_mode = $display_mode_options[$data_sent['option_file']]; 

		
	    $deal_with_users = false;
	    // vérifie si le nom du schedule n'existe pas déjà auquel cas on empêche la creation
		$schedule_name=$data_sent["schedule_name"];
	    $query = "SELECT schedule_name FROM sys_report_schedule WHERE schedule_name='$schedule_name' AND schedule_id!='".$data_sent['schedule_id']."' LIMIT 1";
	    $res = $database->execute($query);
	    $nombre_schedule_identique = $database->getNumRows();
		
		$schedule_errors = false;
		
		$queries = array();
		
	    if ($nombre_schedule_identique == 0) {

	        if ($data_sent['schedule_id']) {
	            // modification d'un schedule
	            $schedule_id = $data_sent['schedule_id'];
				$join_into_mail  =  ($data_sent['scheduler_radio']=='y') ? 1 : 0;
				$type_files = strtolower( $data_sent['scheduler_type_files'] );


	            // on UPDATE le schedule dans la db
	            $queries[] = "UPDATE sys_report_schedule
						SET schedule_name = '" . $data_sent['schedule_name'] . "',
							report_id = '" . implode(',', $data_sent['report']) . "',
							period = '" . $data_sent['scheduler'] . "',
							\"time\" = $sched_time,
							join_into_mail = $join_into_mail,
							type_files = '$type_files',
							display_mode = '$display_mode',
							\"day\" = $sched_day
						WHERE schedule_id = '" . $data_sent['schedule_id']."'";
	            // $result = $database->execute($query);

	            // on dit au systeme de gèrer les abonements
	            $deal_with_users = true;
	        } elseif ($data_sent['schedule_id'] == 0) {
	            // creation d'un nouveau schedule
	            // on cherche l'ID le plus haut
				// 14:01 11/02/2009 GHX
				// Nouveau formar de l'identifiant schedule_id dans le cas de la refont contexte
				$schedule_id = generateUniqId('sys_report_schedule');

                // 20/09/2010 NSE bz 17109 : on met à jour le schedule_id
                $form_element_schedule_id->setValue($schedule_id);
/*
				$Query = "SELECT schedule_id FROM sys_report_schedule ORDER BY schedule_id DESC LIMIT 1";
				$database->execute($Query);
				$nb_result = $database->getNumRows();
				if ($nb_result) {
					$highest_schedule = $database->getRow($Query);
					$schedule_id = $highest_schedule['schedule_id'] + 1;
				} else {
					$schedule_id = 1;
				}*/
	            // on INSERT le schedule dans la db
				$join_into_mail  =  ($data_sent['scheduler_radio']=='y') ? 1 : 0;
				$type_files = strtolower( $data_sent['scheduler_type_files'] );
				
	            $queries[] = "INSERT INTO sys_report_schedule (schedule_id,schedule_name,period,\"time\",\"day\",on_off,report_id,join_into_mail,type_files,display_mode)
						VALUES ('$schedule_id','" . $data_sent['schedule_name'] . "','" . $data_sent['scheduler'] . "',$sched_time,$sched_day,1,'" . implode(',', $data_sent['report']) . "',$join_into_mail,'$type_files','$display_mode')";
	            // $result = $database->execute($query);

	            // on dit au systeme de gèrer les abonements
	            $deal_with_users = true;
	        }
	        // on gere les abonnements
	        if ($deal_with_users) {
	            // on ajoute les liens schedule <-> group dans sys_report_sendmail
	            // on supprime d'abord tous les groupes abonnés
	            $queries[] = "DELETE FROM sys_report_sendmail where schedule_id='$schedule_id' and mailto_type='group'";
	            // $result = $database->execute($query);
				
	            // on boucle sur toutes les valeurs du formulaire pour ajouter chaque groupe
	            foreach($data_sent['to_groups'] as $to_user) {
				
				
	                $queries[] = "INSERT INTO sys_report_sendmail (schedule_id,mailto,mailto_type,on_off) VALUES ('$schedule_id','$to_user','group',1)";
	                // $result = $database->execute($query);
	            }
	            // on ajoute les liens rapport <-> user dans sys_report_sendmail
	            // on supprime d'abord tous les utilisateurs abonnés
	            $queries[] = "DELETE FROM sys_report_sendmail where schedule_id='$schedule_id' and mailto_type='user'";
	            // $result = $database->execute($query);
	            // on boucle sur toutes les valeurs du formulaire pour ajouter chaque utilisateur
	            foreach($data_sent['to_users'] as $to_user) {
	                $queries[] = "INSERT INTO sys_report_sendmail (schedule_id,mailto,mailto_type,on_off) VALUES ('$schedule_id','$to_user','user',1)";
	                // $result = $database->execute($query);
	            }
	            // on ajoute les liens rapport <-> email dans sys_report_sendmail
	            // on supprime d'abord tous les utilisateurs abonnés
	            $query = "DELETE FROM sys_report_sendmail where schedule_id='$schedule_id' and mailto_type='email'";
	            $result = $database->execute($query);
	            // on boucle sur toutes les valeurs du formulaire pour ajouter chaque utilisateur
	            foreach($data_sent['to_emails'] as $to_user) {
				

	                $queries[] = "INSERT INTO sys_report_sendmail (schedule_id,mailto,mailto_type,on_off) VALUES ('$schedule_id','$to_user','email',1)";
	                // $result = $database->execute($query);
	            }
	            // pour finir, on reload la fenetre parent et ferme cette fenetre.
	          
	        }
			
			$errors = array();

			if(count($data_sent['to_emails']) > 0){
				foreach( $data_sent['to_emails'] as $to_emails ) {
					
					$mail_tmp = explode("<", $to_emails );
					$user = $mail_tmp[0];
					$mail_tmp = $mail_tmp[1];
					$mail = str_replace(">","",$mail_tmp);
					$mail = trim($mail);
					
					// 27/07/2009 - maj MPR : 10592 -  On check que le mail est bien valide 
					if( !verifMail( $mail ) ){
					
						$errors[] = "The $user\'s mail \"$mail\" is invalid";
						
						$schedule_errors = true;
					}
				}
			}
			if( !$schedule_errors ){

				$result = $database->execute( implode(";",$queries) );

			}else{
				
				echo "
					<script  language='JavaScript'>
						alert('".implode("\\n", $errors)."');
					</script>
				";
			}
			
		} else {
				$schedule_errors = true;
				?>
				<script language="JavaScript">
					alert('<?=__T('A_JS_TASK_SCHEDULER_SCHEDULE_SETUP_SCHEDULE_NAME_ALREADY_USED')?>');
				</script>
				<?
				
	    }
}
?>
<html>
<head>
	<title>Schedule Setup Interface</title>
	<link rel="stylesheet" href="<?=NIVEAU_0?>css/global_interface.css" />
	<script src="<?=NIVEAU_0?>js/myadmin_omc.js"></script>
	<script src="<?=NIVEAU_0?>js/gestion_fenetre.js"></script>
	<script src="<?=NIVEAU_0?>js/fonctions_dreamweaver.js"></script>
	<script src="<?=NIVEAU_0?>js/split_select.js"></script>

</head>
<body leftmargin="0" topmargin="0">

<table width="550" align="center" valign=middle cellpadding="3" cellspacing="3">
	<tr>
		<td align="center"><img src="<?=NIVEAU_0?>images/titres/schedule_setup_interface_new.gif"/></td>
	</tr>
	<tr>
		<td  style="padding: 20px;">
			<table cellpadding="15" cellspacing="0" align="center" valign="middle" class="tabPrincipal">
				<tr>
					<td class="texteGris">
					<div id="texteGris" align="center"><a href="setup_schedule_index.php"><b><?=__T('G_PROFILE_FORM_LINK_BACK_TO_THE_LIST')?></b></a></div>

<?

$myForm->display();

?>

<script language="JavaScript">
<!--


//
// On over ride la fonction de validation de PhpObjectForm en la redefinissant,
// parce qu'il faut gèrer les radio buttons (il faut qu'il y en ait au moins un de coché)
// et POF ne sait pas le faire.
//

function _fp_validateMyForm() {
	var els = document.forms["myForm"].elements;
	// on verifie que le champ "name" est renseigné
	if(els["schedule_name"].value == ''){
		alert('Please, fill in the <?=__T('A_TASK_SCHEDULER_SCHEDULE_SETUP_SCHEDULE_NAME')?> field');
		return false;
	}

	// if (! _fp_validateMyFormElement(/^[\w][\w\.\s\-]+$/,els["schedule_name"],'<?=__T('A_TASK_SCHEDULER_SCHEDULE_SETUP_SCHEDULE_NAME')?>',true)) {
		// return false;
	// }

	// on verifie que la périodicité a été choisie
	ok = false;
	for (var a=0; a<document.myForm.scheduler.length;a++) {
		if (document.myForm.scheduler[a].checked)
			ok = true;
	}
	if (!ok) {
		alert('<?=__T('A_JS_TASK_SCHEDULER_SCHEDULE_SETUP_NO_PERIOD_SELECTED')?>');
		return false;
	}

	// on verifie qu'on a choisi un rapport
	if (document.myForm.report.value=='') {
		alert('<?=__T('A_JS_TASK_SCHEDULER_SCHEDULE_SETUP_NO_REPORT_SELECTED')?>');
		return false;
	}

	return true;
}

// 06/01/2011 NSE bz 19128 : ajout d'un message pour indiquer que les alames sont toujours générées en pdf
// 03/03/2011 MMT bz 19128 : corrige pb : message affiché par default si schedule sauvegardé en PDF
function afficheRestriction(value){
	 value = value.toLowerCase();
    if(value=='pdf' || value=='')
       document.getElementById('typeAlert').style.display='none';
    else
		 document.getElementById('typeAlert').style.display='block';
}
afficheRestriction('<?=$mySchedule['type_files']?>');
// -->
</script>
</body>
</html>
