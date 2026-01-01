<?php
/**
 *	@cb4100@
 *	- Creation SLC	 13/11/2008
 *
 *	Ajoute l'element au report et renvoie le <li> de l'element
 *
 *	30/01/2009 GHX
 *		- modification des requetes SQL pour mettre des valeurs entre cote [REFONTE CONTEXTE]
 *		- nouveau format pour id de table sys_pauto_config [REFONTE CONTEXTE]
 *	02/02/2009 GHX
 *		- Appel à la fonction qui génére un unique ID generateUniqId() [REFONTE CONTEXTE]
 *		- Suppression de la colonne internal_id [REFONTE CONTEXTE]
 *	05/02/2009 GHX
 *		- Correction pour savoir si l'élément est déjà dans le graphe [REFONTE CONTEXTE]
 *	15/07/2009 GHX
 *		- Correction du BZ 10570 [REC][T&A Cb 5.0][Report Builder]: pas de message si le rapport n'est pas configuré
 *
 *	23/07/2009 BBX : ajout du paramètre resizable sur la popup de configuration du sélecteur. BZ 9938
 *  05/07/2010 BBX : Utilisation de popalt au lieu de alt. BZ 13754
 *   20/10/2010 NSE bz 18635 on déplace le test sur id_product et on met null au lieu de vide
 *   20/01/2011 OJT : Correction bz 20214 Ajout d'informations sur le produit (label)
 */

$intranet_top_no_echo = true;
include_once('common.inc.php');

$class_name = array(
	'dash'	=> 'page',
	'static'	=> 'alarm_static',
	'dynamic'	=> 'alarm_dynamic',
	'top'		=> 'alarm_top_worst'
);


// on recupère les données
// 10:37 30/01/2009 GHX
// Suppression du formatage en INT
$id_page		= $_POST['id_page'];
$id_elem		= $_POST['id_elem'];
$id_product	= intval($_POST['id_product']);
$class_object	= $_POST['class_object'];

// 20/10/2010 NSE bz 18635 on déplace le test sur id_product et on met null au lieu de vide
// 29/09/2010 NSE on ne doit jamais avoir id_product=0
if(empty($id_product)||$id_product==0)
    $id_product='null';

// 20/10/2010 NSE bz 18636 on déplace le test sur id_product et on met null au lieu de vide
// 29/09/2010 NSE on ne doit jamais avoir id_product=0
if($id_product==0)
    $id_product='null';
// on va chercher le report
$query = " --- on va chercher le report $id_page
	SELECT * FROM sys_pauto_page_name WHERE id_page='$id_page'";
$report = $db->getrow($query);
if (!allow_write($report)) {
	echo __T('G_GDR_BUILDER_ERROR_YOU_DONT_HAVE_THE_RIGHT_TO_CHANGE_THAT_REPORT');
	exit;
}

// On verifie que ce dashboard/alarm n'est pas déjà dans le report
// 20/10/2010 NSE bz 18636 On ne test plus le id_product inutile
$query = "SELECT id FROM sys_pauto_config WHERE	id_elem='$id_elem' AND id_page='$id_page' AND class_object='{$class_name[$class_object]}'";
$check_data = $db->getone($query);

// 05/02/2009 GHX : Changement du format de l'id
if (!empty($check_data)) {
	echo __T('G_GDR_BUILDER_ERROR_THIS_IS_ALREADY_INSIDE_THAT_REPORT',$class_object);
	exit;
}

// on va chercher la valeur max de ligne pour les courbes actuellement dans le graph
$query = " --- get max(ligne) for page $id_page
	select ligne from sys_pauto_config where id_page='$id_page' order by ligne desc limit 1";
$ligne = intval($db->getone($query));
$ligne++;

// on ajoute le nouvel element dans le rapport
// on va donc d'abord chercher la prochaine valeur de id
// 10:38 30/01/2009 GHX
// Nouveau format pour l'ID, ce n'est plus géré par un serial
// 14:23 02/02/2009 GHX
// Appel à la fonction qui génére un unique ID
$next_id = generateUniqId('sys_pauto_config');

// on a besoin du nom de l'objet
switch ($class_object) {
	case 'dash' :
		$query = " --- we fetch the name of the raw dashboard
			select id_page,page_name as name,
				(select sdp_label from sys_definition_product where sdp_id=$id_product) as sdp_label
			from sys_pauto_page_name
			where id_page='$id_elem'";
		$elem_info = $db->getrow($query);
		
		// 10:51 15/07/2009 GHX
		// Correction du BZ 10570
		$db->execute("
			SELECT * 
			FROM sys_definition_selecteur
			WHERE 
				sds_report_id = '{$id_page}'
				AND sds_id_page = '{$id_elem}'
			");
		
		$isConfigured = $db->getNumRows();
		
	break;
	case 'static' :
		$query = " --- we fetch the name of static alarm
			select alarm_name as name,
				(select sdp_label from sys_definition_product where sdp_id=$id_product) as sdp_label
			from sys_definition_alarm_static
			where alarm_id='$id_elem'";
	break;
	case 'dynamic' :
		$query = " --- we fetch the name of dynamic alarm
			select alarm_name as name,
				(select sdp_label from sys_definition_product where sdp_id=$id_product) as sdp_label
			from sys_definition_alarm_dynamic
			where alarm_id='$id_elem'";
	break;
	case 'top' :
		$query = " --- we fetch the name of top/worst alarm
			select alarm_name as name,
				(select sdp_label from sys_definition_product where sdp_id=$id_product) as sdp_label
			from sys_definition_alarm_top_worst
			where alarm_id='$id_elem'";
	break;
}

if ($class_object != 'dash') {
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$db_temp = Database::getConnection($id_product);
	$elem_info = $db_temp->getrow($query);
}

// echo "<p>";echo $query."<hr />";var_dump($elem_info);echo "</p>";

// on insert le dash/alarm
// 20/10/2010 NSE bz 18635 : on déplace le test sur la valeur de id_product
$query = " --- insert new element in report
	insert into sys_pauto_config (id,id_elem,class_object,id_page,ligne,id_product)
	values ('$next_id','$id_elem','{$class_name[$class_object]}','$id_page',$ligne,$id_product)
";
$db->execute($query);



//
// a partir de maintenant on compose le HTML qui doit être retourné (avec icone, label, ... de l'élément)
//

// __debug($query);

// on compose l'HTML qui va être ajouté dans la page
if ($class_object=='dash') {

    $productLabel = $elem_info['sdp_label'];
    if( strlen( trim( $productLabel ) ) === 0 )
    {
        $productLabel = "multi product";
    }

	// ajout de dashboard
	// 11:19 15/07/2009 GHX
	// Correction du BZ10570
	// Ajout d'un message comme quoi le dashboard n'est pas configuré
	// 23/07/2009 BBX : ajout du paramètre resizable. BZ 9938
    // 12/10/2010 NSE 18412 : ajout d'un test JS avant redirection vers lien href : isDashElementDragging
	echo "
		<li id='gtm_element__$next_id'>
			<div style='padding-bottom:1px;'>
			<div class='icon'><img src='images/dashboard.png' alt='".__T('G_GDR_BUILDER_DASHBOARD')."' width='16' height='16'/></div>
				<div class='del' onclick=\"delete_element('gtm_element__$next_id');\"><img src='images/delete.png' onmouseover=\"popalt('".__T('G_GDR_BUILDER_DELETE')."')\" width='16' height='16'/></div>
				<div class='info'>
					<a	href='window.open(setup_report_detail.php?id_page={$elem_info['id_page']}&report_id={$id_page})'
						onclick='setIdDashEditSelecteur(\"{$next_id}\");window.open(\"setup_report_detail.php?id_page={$elem_info['id_page']}&report_id={$id_page}\",\"\",\"resizable=yes,menubar=no, status=no, scrollbars=yes, menubar=no, width=900, height=300\"); return false;'>
						<img src='images/application_edit_off.png' onmouseover=\"popalt('".__T('G_GDR_BUILDER_DETAILS')."')\" />
					</a>
				</div>
				<div class='label'><a onclick='return isDashElementDragging( this );' href='dashboard.php?id_page={$elem_info['id_page']}'>{$elem_info['name']}</a> ".($isConfigured ? "" : "<span class=\"dash_not_configured\">(".__T('G_GDR_BUILDER_DASHBOARD_NOT_CONFIGURED').")</span>")."</div>
			</div>
            <div class='product'>{$productLabel}</div>
			<div id='elem_prop__$next_id' class='properties'></div>
		</li>
	";
} else {
	// ajout d'alarme
	// maj 30/06/2009 - MPR : Correction du bug 10231 : Ajout de l'id produit dans l'url
        // 05/07/2010 BBX :
        // Utilisation de popalt au lieu de alt. BZ 13754
	echo "
		<li id='gtm_element__$next_id'>
			<div style='padding-bottom:1px;'>
			<div class='icon'><img src='images/alarm.png' alt='".__T('G_GDR_BUILDER_ALARM_FROM',$elem_info['sdp_label'])."' width='16' height='16'/></div>
				<div class='del' onclick=\"delete_element('gtm_element__$next_id');\"><img src='images/delete.png' onmouseover=\"popalt('".__T('G_GDR_BUILDER_DELETE')."')\" width='16' height='16'/></div>
				<div class='info'><a href='../pauto/intranet/php/affichage/pauto_report_alarm_view.php?product=$id_product&alarm_id=$id_elem&alarm_type={$class_name[$class_object]}' target='_blank'><img src='images/application_edit_off.png' alt='".__T('G_GDR_BUILDER_DETAILS')."' width='16' height='16'/></a></div>
				<div class='label'>{$elem_info['name']}</div>
			</div>
            <div class='product'>{$elem_info['sdp_label']}</div>
			<div id='elem_prop__$next_id' class='properties'></div>
		</li>
	";
}


?>
