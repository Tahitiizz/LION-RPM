<?php
/**
 * 
 *  CB 5.2
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 */
?><?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
	- maj 26/05/2008 Benjamin : la valeur reste toujours à 1, même si la case est décochée. On force donc la récupération de la valeur directement par POST. BZ6719
	- maj 18/03/2008, benoit : gestion du message "Select at least one Group/User/Email" en base
	- maj 18/03/2008, benoit : gestion du message "Invalid Email field" en base
	- maj 18/03/2008, benoit : correction du bug 4520. Ajout de "<$mail_reply>" dans l'expediteur du mail afin de ne plus avoir "@acurio.com"
	- maj 18/03/2009 SPS : generation du pdf avec la macro openoffice
 * - maj 21/09/2011 23743 MMT ajout cast explicit pour PG9.1
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
		Permet de sélectionner des groupes d'utilisateurs / utilisateurs
		à qui ont veut envoyer le PDF de la page courrante.

		- maj 14 03 2006 christophe : correction d'un bug de phpObjectForm.
		- maj 02 06 2006 christophe : correction du bug, envoi d'un mail à un groupe, des user et des nouveaux mail en même temps.
		- maj 08 06 2006 christophe : même bug ligne au dessus cf l.317.
	*/

	session_start();

	include_once dirname(__FILE__)."/../../php/environnement_liens.php";
	
	/* 18/03/2009 - modif SPS : librairies inutiles*/
	/*include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	include_once($repertoire_physique_niveau0 . "php/environnement_donnees.php");
	include_once($repertoire_physique_niveau0 . "php/environnement_nom_tables.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");*/
	include_once($repertoire_physique_niveau0 . "class/libMail.class.php");				// Gestion de l'envoi des mails

	require_once(REP_PHYSIQUE_NIVEAU_0.'/class/PHPOdf.class.php');
	require_once(REP_PHYSIQUE_NIVEAU_0.'/class/DashboardExport.class.php');

	global $database_connection, $niveau0, $repertoire_physique_niveau0;

	$is_debug = false;	// mettre à true pour afficher le mode debug.

	/*18/03/2009 - modif SPS : generation du pdf (meme mode operatoire pour l'export word/pdf)*/
	
	// Récupération des images
	$astelliaLogo = get_sys_global_parameters('pdf_logo_dev');
	$clientLogo = get_sys_global_parameters('pdf_logo_operateur');
	
	//on regarde la taille des donnees en session
	//si les donnees en session contiennent au moins un GTM
	if ( count($_SESSION['dashboard_export_buffer']) > 0 ) {
	
		// Instanciation de la classe d'export
		$DashboardExport = new DashboardExport($_SESSION['dashboard_export_buffer'],
												'landscape',
												REP_PHYSIQUE_NIVEAU_0.'/upload',
												'export_pdf_',
												REP_PHYSIQUE_NIVEAU_0.$astelliaLogo,
												REP_PHYSIQUE_NIVEAU_0.$clientLogo,
												REP_PHYSIQUE_NIVEAU_0.'/images/icones/pdf_alarm_titre_arrow.png');

		$pdf_file_name = $DashboardExport->pdfExport();
	}

?>
<html>
<head>
	<title>Send to Interface</title>
	<link rel="stylesheet" href="<?=NIVEAU_0?>css/global_interface.css" />
	<?php 
	/*<script src="<?=NIVEAU_0?>js/myadmin_omc.js"></script>
	<script src="<?=NIVEAU_0?>js/gestion_fenetre.js"></script>
	<script src="<?=NIVEAU_0?>js/fonctions_dreamweaver.js"></script>
	<script src="<?=NIVEAU_0?>js/split_select.js"></script>
	*/?>
</head>
<body leftmargin="0" topmargin="0">

<table width="550" align="center" valign=middle cellpadding="3" cellspacing="3">
	<tr>
		<td align="center"><img src="<?=NIVEAU_0?>images/titres/send_to_pdf.gif"/></td>
	<tr>
		<td  style="padding: 20px;">
			<table class="tabPrincipal">
			
				<tr>
					<td>
						<div id="msg_display"></div>
					</td>
				</tr>
				<tr>
					<td class="texteGris">
		<?


	require_once $repertoire_physique_niveau0 . "class.phpObjectForms/lib/FormProcessor.class.php";
	$fp = new FormProcessor($repertoire_physique_niveau0 . "class.phpObjectForms/lib/");
	$fp->importElements(array("FPTextArea", "FPButton","FPCheckBox", "FPHidden", "FPRadio", "FPSelect", "FPText", "FPTextField", "extra/FPSplitSelect", "extra/FPHalfSplitSelect"));
	$fp->importLayouts(array("FPColLayout", "FPRowLayout", "FPGridLayout"));
	$fp->importWrappers(array("FPLeftTitleWrapper"));
	$leftWrapper = new FPLeftTitleWrapper(array());

	$myForm = new FPForm(array(
		"name" => 'mail_send',
		"action" => $_SERVER["PHP_SELF"],
		"display_outer_table" => true,
		"table_align" => 'center',
		"enable_js_validation" => true,
	));

	// On stocke le type d'élément pour la création du PDF.
	$pdf_file_name = new FPHidden(array(
		"name" => 'pdf_file_name',
        "value" => $pdf_file_name
	));

	// Sujet de l'email
    // 08/06/2011 : Correction bz22398, passage de la règle de vaidation à FP_VALID_ANYTEXT
	$form_element_email_subject = new FPTextField(array(
		"title" => '<span class="texteGris">Subject</span>',
		"name" => 'email_subject',
		"required" => true,
		"valid_RE" => FP_VALID_ANYTEXT,
		"css_style" => 'width:300px;',
		"wrapper" => &$leftWrapper,
	));

	// Corps de l'email
	$form_element_email_corps = new FPTextArea(array(
		"title" => '<span class="texteGris">Message</span>',
		"name" => 'email_corps',
		"required" => false,
		"valid_RE" => FP_VALID_ANYTEXT,
		"wrapper" => &$leftWrapper,
		"rows" => 5,
		"cols" => 35,
	));

	// Checkbox pour l'inclusion du PDF
	$form_element_cb_pdf_join = new FPCheckbox(array(
		"title" => '<span class=texteGris>Join PDF file.</span>',
		"name" => 'pdf_join',
		"value" => 1,
		"checked" => true,
		"comment" => '<span class=texteGrisPetit>(checked = yes)</span>',
	));


	/*
		Affichage de la liste des groupes.
	*/
	$list_all_groups = array();
	$list_subscribed_groups = array();
	$query = "SELECT DISTINCT id_group,group_name FROM sys_user_group WHERE on_off=1 ORDER BY group_name ASC";
	$result = pg_query($database_connection, $query);
	$nb_result = pg_num_rows($result);
	if ($nb_result > 0) {
		for ($i = 0;$i < $nb_result;$i++) {
			$current_user = pg_fetch_array($result, $i, PGSQL_ASSOC);
			$list_all_groups[$current_user['id_group']] = $current_user['group_name'];
		}
	}

	$form_element_group_selector = new FPSplitSelect(array("title" => "<span class='texteGrisBold'>Group selector</span>",
		"name" => "to_groups",
		"form_name" => "mail_send",
		"multiple" => true,
		"size" => 10,
		"options" => $list_all_groups,
		"left_title" => "<span class='texteGris'>Available groups</span>",
		"right_title" => "<span class='texteGris'>Selected groups</span>",
		"right_ids" => $list_subscribed_groups,
		"css_style" => "width:300px;",
		"table_padding" => 5,
	));

	/*
		Affichage de la liste des mails utilisateurs valides.
	*/
	$list_all_users = array();
	$list_subscribed_users = array();
	//21/09/2011 23743 MMT ajout cast explicit pour PG9.1
        // 20/02/2012 NSE DE Astellia Portal Lot2 : suppression de date_valid et on_off
	$query = "
		SELECT id_user,username,login,user_mail
		FROM users 
		ORDER BY username ASC";
	$result = pg_query($database_connection, $query);
	$nb_result = pg_num_rows($result);
	if ($nb_result > 0) {
		for ($i = 0;$i < $nb_result;$i++) {
			$current_user = pg_fetch_array($result, $i, PGSQL_ASSOC);
			$list_all_users[$current_user['id_user']] = $current_user['login'] . ' - ' . $current_user['username'];
		}
	}

	$form_element_user_selector = new FPSplitSelect(array("title" => "<span class='texteGrisBold'>User selector</span>",
		"name" => "to_users",
		"form_name" => "mail_send",
		"multiple" => true,
		"size" => 10,
		"options" => $list_all_users,
		"left_title" => "<span class='texteGris'>Available users</span>",
		"right_title" => "<span class='texteGris'>Subscribed users</span>",
		"right_ids" => $list_subscribed_users,
		"css_style" => "width:300px;",
		"table_padding" => 5,
	));


	/*
		Saisie d'email.
	*/
	$list_subscribed_emails = array();
	$form_element_email_selector = new FPHalfSplitSelect(array("title" => "<span class='texteGrisBold'>Email selector</span>",
		"name" => "to_emails",
		"form_name" => "mail_send",
		"valid_RE" => FP_VALID_ANYTEXT,
		"multiple" => true,
		"size" => 10,
		"left_title" => "<span class='texteGris'>Email</span>",
		"right_title" => "<span class='texteGris'>Subscribed emails</span>",
		"right_ids" => $list_subscribed_emails,
		"css_style" => "width:300px;",
		"table_padding" => 5,
	));


	// Bouton submit.
	$form_element_submit_button = new FPButton(array("submit" => true,
		"name" => 'bt_save',
		"form_name" => "mail_send",
		"title" => 'Send',
		"css_class" => 'bouton',
	));
	
	$form_element_close_button = new FPButton(array("submit" => false,
		"name" => 'bt_close',
		"form_name" => "mail_send",
		"title" => 'Close',
		"css_class" => 'bouton',
		"on_click" => 'window.close()',
	));

	
	/*
		Mise en page du formulaire.
	*/

	// on a besoin d'ajouter un layout au bouton submit pour qu'il soit centré
	$form_layout_submit_button = new FPRowLayout(array("table_align" => "center",
		"table_padding" => 20,
		"elements" => array($form_element_submit_button, $form_element_close_button)
	));

	// Sujet, corps et checkbox pour l'email.
	$form_layout_mail = (new FPColLayout(array(
		"table_padding" => 0,
		"table_cellspacing" => 2,
		"element_align" => "left",
		"elements" => array(
			$form_element_email_subject,
			$form_element_email_corps,
			)
		))
	);
	$form_layout_mail_with_cb = new FPRowLayout(array(
		"table_align" => "left",
		"table_padding" => 2,
		"table_cellspacing" => 5,
		"elements" => array($form_layout_mail,$form_element_cb_pdf_join, )
	));

	$myForm->setBaseLayout(new FPColLayout(array(
		"table_padding" => 0,
		"table_cellspacing" => 2,
		"element_align" => "left",
		"elements" => array(
			$pdf_file_name,
			$form_layout_mail_with_cb,
			$form_element_group_selector,
			$form_element_user_selector,
			$form_element_email_selector,
			$form_layout_submit_button
			)
		))
	);

	$myForm->display();


	/*
		Traitement de l'envoie des mails.
	*/
	if ($myForm->getSubmittedData() && $myForm->isDataValid()) {

		$data_sent = $myForm->getElementValues();

		$email_subject = 	isset($data_sent["email_subject"]) ? $data_sent["email_subject"] : "";
		$email_corps = 		isset($data_sent["email_corps"]) ? $data_sent["email_corps"] : "";
		$to_groups = 		isset($data_sent["to_groups"]) ? $data_sent["to_groups"] : "";
		$to_users = 		isset($data_sent["to_users"]) ? $data_sent["to_users"] : "";
		$to_emails = 		isset($data_sent["to_emails"]) ? $data_sent["to_emails"] : "";
		$pdf_file_name = 	isset($data_sent["pdf_file_name"]) ? $data_sent["pdf_file_name"] : "";
		// maj 26/05/2008 Benjamin : la valeur reste toujours à 1, même si la case est décochée. On force donc la récupération de la valeur directement par POST. BZ6719
		//$pdf_join = 		isset($data_sent["pdf_join"]) ? $data_sent["pdf_join"] : "";
		$pdf_join = isset($_POST["pdf_join"]) ? $_POST["pdf_join"] : "";

		if($is_debug){
			echo "sujet : ".$email_subject."<br>";
			echo "corps : ".$email_corps."<br>";
			echo "groupes : ".$to_groups."<br>";
			echo "users : ".$to_users."<br>";
			echo "mails : ".$to_emails."<br>";
			echo "type de l'objet : ".$objetc_type." / id user courrant : ".$id_user."<br>";
			echo "nom du fichier PDF : ".$pdf_file_name."<br>";
			$rep = ($pdf_join == "") ? "NON" : "OUI";
			echo "le fichier est il joint au mail : ".$rep."<br>";
		}

		/*
			création du PDF.
		*/

		$dir_pdf = 				get_sys_global_parameters("pdf_save_dir");
		$pdf_attach = 			$pdf_file_name;
		$nom_appli = 			get_sys_global_parameters("system_name");
		$mail_reply = 			get_sys_global_parameters("mail_reply");

		/*
			On créé un tableau contenant tous les mails (groupes, users...) array_push
		*/
		$tab_mails = array(); // tableau contenant la liste de tous les mails.

		// On récupère les mails des groupes.
		foreach($data_sent['to_groups'] as $to_groups) {
			$id_group = $to_groups;
			$query_mail_group = "
				SELECT  user_mail FROM sys_user_group, users
					WHERE id_group = '$id_group'
					AND	users.id_user = sys_user_group.id_user
				";
			$result = pg_query($database_connection, $query_mail_group);
			$nb_resultat = pg_num_rows($result);
			if($nb_resultat > 0){
				for ($i = 0;$i < $nb_resultat;$i++){
					$row = pg_fetch_array($result, $i);
					array_push($tab_mails, $row["user_mail"]);
				}
			}
		}

		// On récupère les mails des utilisateurs.
		foreach($data_sent['to_users'] as $to_users) {
			$iduser = $to_users;
			$query_mail_user = "  SELECT  user_mail FROM users WHERE id_user = '$iduser' ";
			$result = pg_query($database_connection,$query_mail_user);
			$nb_resultat = pg_num_rows($result);
			if($nb_resultat > 0){
				for ($i = 0;$i < $nb_resultat;$i++){
					$row = pg_fetch_array($result, $i);
					array_push($tab_mails, $row["user_mail"]);
				}
			}
		}

		// Liste des mails saisit.
		//var_dump($data_sent['to_emails']);
		foreach($data_sent['to_emails'] as $to_emails=>$toto) {
			//echo "<br>$to_emails / $toto <br>";
			array_push($tab_mails, $toto); // modif le 01 06 2006
		}


		$tab_mails = array_unique($tab_mails);	// On enlève les mails doublons.

		// On remet les indice du tableau dans l'ordre afin de ne pas faire planter la classe d'envoi des mails
		$i2 = 0;
		$msg .= "<div class='okMsg'>";
		foreach($tab_mails as $mail_envoi){
			$tab_mails_bis[$i2] = $mail_envoi;
			$msg .=  "<li>mail send to : " . $mail_envoi."</li>";
			$i2++;
		}
		
		$msg .= "</div>";

		// On créé et on envoie un mail avec le PDF pour chaque utilisateur sélectionné.
		$un_mail = new Mail();

		// 18/03/2008 - Modif. benoit : correction du bug 4520. Ajout de "<$mail_reply>" dans l'expediteur du mail afin de ne plus avoir "@acurio.com"

		$un_mail->From($nom_appli."<$mail_reply>");
		
		$un_mail->ReplyTo($mail_reply);
		$un_mail->To($tab_mails_bis);
		

		$un_mail->Subject($email_subject);
		$un_mail->Body($email_corps);
		/* 18/03/2009 - modif SPS : verification de l'existence du pdf (si inexistant => on envoie pas de piece jointe*/
		if( ( $pdf_join != "") && file_exists($pdf_attach) ) $un_mail->Attach($pdf_attach);
		$un_mail->Send();
		
		echo $msg;
		
	}

?>
					</td>
				</tr>
			</table>
		</td>
	</tr>

</table>
<script language="JavaScript">
	window.focus();
	function verifier_mail_selectionne(){
		if(document.mail_send.email_subject.value == ''){
			alert("<?=__T("U_JS_SEND_TO_PDF_NO_SUBJECT")?>");
			return false;
		}

	<?php
		// 18/03/2008 - Modif. benoit : gestion du message "Select at least one Group/User/Email" en base
	?>
		
		if(document.mail_send.to_groups.value == '' && document.mail_send.to_users.value == '' && document.mail_send.to_emails.value == ''){
			alert('<?=__T("U_JS_SEND_TO_PDF_NO_RECIPIENT")?>');
			return false;
		}

	}

	document.mail_send.onsubmit = verifier_mail_selectionne;

	var to_emails_fp = 			document.forms['mail_send'];
	var to_emails_leftName = 	to_emails_fp.elements['to_emails_leftBox_name'];
	var to_emails_leftEmail = 	to_emails_fp.elements['to_emails_leftBox_email'];
	var to_emails_rightOpts = 	to_emails_fp.elements['to_emails_rightBox'].options;
	var to_emails_valueElt = 	to_emails_fp.elements['to_emails'];

	function to_emails_updateValueElt() {
		var packedRightIDs = '';
		for (var i=0; i<to_emails_rightOpts.length; i++) {
			packedRightIDs += to_emails_rightOpts[i].value +
				(i < to_emails_rightOpts.length - 1 ? "||" : "");
		}
		to_emails_valueElt.value = packedRightIDs;
	}

	function to_emails_halfsplitSelectRightToLeft() {
		a = to_emails_rightOpts
		Name = '';
		Email = '';
		for (var i=0; i<a.length; i++) {
			if (a[i].selected) {
				NameEmail = a[i].text;
				Name = NameEmail.substring(0,NameEmail.indexOf("<")-1);
				Email = NameEmail.substring(NameEmail.indexOf("<")+1,NameEmail.indexOf(">"));
				to_emails_leftName.value = Name;
				to_emails_leftEmail.value = Email;
				a[i] = null;
				i--;
			}
		}
		to_emails_updateValueElt();
	}

	function to_emails_halfsplitSelectLeftToRight() {
		// Rajout christophe, bug phpObjectForm.
		var reg = /^[a-z0-9._-]+@[a-z0-9.-]{2,}[.][a-z]{2,3}$/
		if (reg.exec(to_emails_leftEmail.value)!=null){
			NameEmail = to_emails_leftName.value + ' <' + to_emails_leftEmail.value + '>';
			to_emails_rightOpts[to_emails_rightOpts.length] = new Option(NameEmail,to_emails_leftEmail.value, false, true); // modif le 01 06 2006
			to_emails_updateValueElt();
		} else {

			<?php
				// 18/03/2008 - Modif. benoit : gestion du message "Invalid Email field" en base
			?>

			alert('<?=__T("U_JS_SEND_TO_PDF_INVALID_EMAIL_FIELD")?>');
		}
	}

	function to_emails_splitSelectAToB(a, b) {
		for (var i=0; i<a.length; i++) {
			if (a[i].selected) {
				b[b.length] = new Option(
					a[i].text, a[i].value, false, true
				);
				a[i] = null;
				i--;
			}
		}
	}

	function to_emails_splitSelectOnChangeLeft() {
		to_emails_rightOpts.selectedIndex = -1;
	}
</script>
</body>
</html>
