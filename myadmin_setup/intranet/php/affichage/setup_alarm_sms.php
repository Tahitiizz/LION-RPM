<?php
/**
 * Script affichant la popup de configuration des SMS pour les alarmes de types
 * statiques ou dynamiques
 * @copyright Copyright (c) 2011, Astellia
 * @since 5.0.7.00
 *
 * $Author: o.jousset $
 * $Date: 2011-08-03 17:17:36 +0200 (mer., 03 aoÃ»t 2011) $
 * $Revision: 28130 $
 */

session_start();
require_once $repertoire_physique_niveau0."php/environnement_liens.php";
require_once $repertoire_physique_niveau0."class.phpObjectForms/lib/FormProcessor.class.php";

// Initialisation des variables
$productId = $_REQUEST['product'];
$alarmId   = $_REQUEST['alarm_id'];
$alarmType = $_REQUEST['alarm_type'];
$titleImg  = $niveau0."images/titres/static_alarm_setup_interface_titre.gif";

// Mise à jour du titre pour les alarmes dynamiques
if( $alarmType == 'alarm_dynamic' )
{
    $titleImg = $niveau0."images/titres/dynamic_alarm_new.gif";
}

// Connexion à la base de données (du produit en cours)
$db = Database::getConnection( $productId );

// Si le formulaire a été validé
if( isset( $_POST['to_groups'] ) && isset( $_POST['to_users'] ) )
{
    // Initialisation des variables
    $fullList['user']  = explode( '||', $_POST['to_users'] );
    $fullList['group'] = explode( '||', $_POST['to_groups'] );

    // On efface les données deja existantes dans la table pour cette alarme
    $db->execute( "DELETE FROM sys_alarm_sms_sender WHERE id_alarm='{$alarm_id}' AND alarm_type='{$alarm_type}'");  

    // Insertion de chaque groupe et utilisateur
    foreach( $fullList as $type => $oneList )
    {
        foreach( $oneList as $list )
        {
            if( strlen( trim( $list ) ) > 0 )
            {
                $insertQuery = "INSERT INTO sys_alarm_sms_sender(id_alarm,alarm_type,recipient_type,recipient_id) VALUES ('%s','%s','%s','%s')";
                $insertQuery = sprintf( $insertQuery, $alarmId, $alarmType, $type, $list );
                $db->execute( $insertQuery );
            }
        }
    }

    // On ferme la fenetre et on recharge la page principale.
    echo '<script language="JavaScript">window.opener.location.reload(); window.close();</script>';
}

// Initialize the phpObjectForms class
$fp = new FormProcessor( $repertoire_physique_niveau0."class.phpObjectForms/lib/" );
$fp->importElements(array("FPButton", "FPHidden", "FPSelect", "FPText", "FPTextField",'extra/FPSplitSelect'));
$fp->importLayouts(array("FPColLayout", "FPRowLayout", "FPGridLayout"));
$fp->importWrappers(array( "FPLeftTitleWrapper" ));
$leftWrapper = new FPLeftTitleWrapper(array());

// Déclaration du formulaire
$myForm = new FPForm(array(
    "name"                 => 'myForm',
    "action"               => 'setup_alarm_sms.php',
    "display_outer_table"  => true,
    "table_align"          => 'center',
    "enable_js_validation" => false
));

// Gestion des champs cachés
$hiddenProductFormElt  = new FPHidden( array( "name" => "product","value" => $productId ) );
$hiddenAlarmIdFormElt  = new FPHidden( array( "name" => "alarm_id","value" => $alarmId ) );
$hidenAlarmTypeFormElt = new FPHidden( array( "name" => "alarm_type","value" => $alarmType ) );

// Gestion de la liste des groupes
$listAllGroups        = array(); // Liste de tous les groupes
$listSubscribedGroups = array(); // Liste des groupes configurés
$listWarningGroups    = array(); // Liste des groupes avec warning (missing phone number)

// Recherche de tous les groupes
foreach( GroupModel::getGroups() as $oneGroup )
{
    $listAllGroups[$oneGroup['id_group']] = $oneGroup['group_name'];

    // Détection des groupes ayant des utilisateurs sans phone number
    $grpModel   = new GroupModel( $oneGroup['id_group'] );
    $grpUsers   = $grpModel->getUsers();
    $grpNbUsers = count( $grpUsers );
    $i          = 0;
    while( $i < $grpNbUsers && !in_array( $oneGroup['id_group'], $listWarningGroups ) )
    {
        if( strlen( trim( $grpUsers[$i]['phone_number'] ) ) == 0 )
        {
            $listWarningGroups []= $oneGroup['id_group'];
        }
        $i++;
    }
}

// Recherche des groupes configurés pour cette alarme
$getSubscribed = $db->getall( "SELECT recipient_id FROM sys_alarm_sms_sender
                                WHERE id_alarm='{$alarmId}'
                                AND alarm_type='{$alarm_type}'
                                AND recipient_type='group'" );
if( $getSubscribed )
{
    foreach ( $getSubscribed as $row )
    {
        $listSubscribedGroups []= $row['recipient_id'];
    }
}

// Création du Warning pour les groupes (si il existe)
$leftTitle = '';
if( count( $listWarningGroups ) > 0 )
{
    $leftTitle = "</u><span style='display:block;color:orange;' class='texteGrisPetit'>".__T( 'SMS_SETUP_ALARM_GROUP_WARN' )."<span><u>";
}

$groupSelectorFormElt = new FPSplitSelect(array(
    "title"         => "<span class='texteGrisBold'>Group selector</span>",
    "name"          => "to_groups",
    "form_name"     => 'myForm',
    "multiple"      => true,
    "size"          => 10,
    "options"       => $listAllGroups,
    "left_title"    => "<span class='texteGrisBold'>Available groups</span>{$leftTitle}",
    "right_title"   => "<span class='texteGrisBold'>Subscribed groups</span>",
    "right_ids"     => $listSubscribedGroups,
    "warning_ids"   => $listWarningGroups,
    "css_style"     => "width:300px;",
    "table_padding" => 5
));

// Gestion de la liste des utilisateurs
$listAllUsers        = array(); // Liste de tous les utilisateurs
$listSubscribedUsers = array(); // Liste des utilisateurs configurés
$listWarningUsers    = array(); // Liste des utilisateurs avec warning (empty phone number)

// Recherche de tous les utilisateurs
foreach( UserModel::getUsers() as $oneUser )
{
    $listAllUsers[$oneUser['id_user']] = "{$oneUser['login']} - {$oneUser['username']}";

    // Détection des utilisateurs avec numéro de téléphone vide
    if( strlen( trim( $oneUser['phone_number'] ) ) == 0 )
    {
        $listWarningUsers [] = $oneUser['id_user'];
    }
}

// Recherche des utilisateurs configurés pour cette alarme
$getSubscribed = $db->getall( "SELECT recipient_id FROM sys_alarm_sms_sender
                                WHERE id_alarm='{$alarmId}'
                                AND alarm_type='{$alarm_type}'
                                AND recipient_type='user'" );
if( $getSubscribed )
{
    foreach ( $getSubscribed as $row )
    {
        $listSubscribedUsers []= $row['recipient_id'];
    }
}

// Création du Warning pour les groupes (si il existe)
$leftTitle = '';
if( count( $listWarningUsers ) > 0 )
{
    $leftTitle = "</u><span style='display:block;color:orange;' class='texteGrisPetit'>".__T( 'SMS_SETUP_ALARM_USER_WARN' )."<span><u>";
}

$userSelectorFormElt = new FPSplitSelect(array(
    "title"         => "<span class='texteGrisBold'>User selector</span>",
    "name"          => "to_users",
    "form_name"     => 'myForm',
    "multiple"      => true,
    "size"          => 10,
    "options"       => $listAllUsers,
    "left_title"    => "<span class='texteGrisBold'>Available users</span>{$leftTitle}",
    "right_title"   => "<span class='texteGrisBold'>Subscribed users</span>",
    "right_ids"     => $listSubscribedUsers,
    "warning_ids"   => $listWarningUsers,
    "css_style"     => "width:300px;",
    "table_padding" => 5
));

// Gestion du bouton de validation
$submitFormElt = new FPButton( array( "submit" => true,"name" => 'submit',"title" => 'Save',"css_class" => 'bouton' ) );

// Mise en form du formulaire
$myForm->setBaseLayout
(
    new FPColLayout(array(
    "table_align"   => 'center',
    'table_padding' => 0,
    "elements"      => array
    (
        $groupSelectorFormElt, // Selecteur des groupes
        $userSelectorFormElt, // Selecteur des utilisateurs
        new FPRowLayout(array("table_align"=>'center','table_padding'=>20,"elements" => array($submitFormElt))),
        $hiddenProductFormElt, // Identifiant du produit
        $hiddenAlarmIdFormElt, // Identifiant de l'alarme
        $hidenAlarmTypeFormElt // Type de l'alarme
    )))
);
?>
<html>
    <head>
        <title>Alarm SMS settings</title>
        <link rel="stylesheet" type="text/css" media="all" href="<?=$niveau0?>css/global_interface.css" />
    </head>
    <body leftmargin="0" topmargin="0">
        <table width="550" align="center" valign=middle cellpadding="5" cellspacing="0">
            <tr>
                <td align="center"><img src="<?=$titleImg?>"/></td>
            </tr>
            <tr>
                <td align="center">
                    <table cellpadding="3" cellspacing="3" align="center" class="tabPrincipal">
                        <tr>
                            <td>
                                <?php $myForm->display(); ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
