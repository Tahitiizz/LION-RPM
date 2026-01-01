<?php
/**
 * @cb5100@
 *
 * 23/07/2010 OJT : Correction BZ 16821
 *
 */
/*
* page qui permet de gerer les contextes
*
*	06/04/2009 GHX
*		- correction d'un bug d'affichage (mauvais appel de fonction)
*	09/04/2009 SPS : ajout d'une fonction pour cocher l'element parent en fonction du/des fils
*	13/05/2009 SPS
*		- on verifie si l'element du contexte doit etre afficher (correction bug 9551)
*	05/08/2009 - CCT1 : ajout de l'image titre. correction BZ 10290
*
*	04/09/2009 GHX
*		- (Evo) Data Export champ target_dir incorrecte
*	04/12/2009 GHX
*		- Correction du BZ 12160 [REC][T&A Cb 5.0][Context Management] : si on monte un contexte contenant trop de data export, leur chemin n'est pas mis à jour
*			-> Utilisation de $_POST au lieu de $_GET au niveau de l'ajax
*
* @author SPS
* @date 31/03/2009
* @version CB 4.1.0.0
* @since CB 4.1.0.0
*/

session_start();
include_once dirname(__FILE__)."/../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0."php/database_connection.php");
include_once(REP_PHYSIQUE_NIVEAU_0."context/php/context_upload.php");
include_once(REP_PHYSIQUE_NIVEAU_0."context/php/context_install.php");
include_once(REP_PHYSIQUE_NIVEAU_0."context/php/context_build.php");
include_once(REP_PHYSIQUE_NIVEAU_0."context/class/Context.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0."intranet_top.php");

//repertoire de creation des contextes
$dir = REP_PHYSIQUE_NIVEAU_0.'png_file/';
?>
	<script type="text/javascript">
		//afficher/cacher un element
		function cacher(div_id) {
			$(div_id).toggle();
		}

                function openClose(id,ref){
                    if(Element.visible($(ref))){
                        $('arrow_'+id).src='../images/icones/alarm_ta_cursor.gif';
                        $('folder_'+id).src='../images/icones/afolder.gif';
                    }
                    else{
                        $('arrow_'+id).src='../images/icones/tri.gif';
                        $('folder_'+id).src='../images/icones/folder.gif';
                    }
                }

		//fonction qui coche/decoche les data d'un element
		function check_data(id) {
			//on recupere l'etat de l'element parent
			var elem = $('id_'+id).checked;
			//on recupere les elements fils
			var tElementsData = $$('input[type="checkbox"].elements_'+id);
			for(var k=0;k<tElementsData.length;k++) {
                            // on regarde les petit fils
                            var tElementsDataFils = $$('input[type="checkbox"].elements_'+tElementsData[k].id.substring(3));
				//si le parent n'est pas coche, alors on decoche les fils
                               // alert(tElementsData[k].id);exit;
				if (elem == '') {
					tElementsData[k].checked = '';
                                        for(var l=0;l<tElementsDataFils.length;l++) {
                                            tElementsDataFils[l].checked = '';
				}
				}
				//si le parent est coche, alors on coche les fils
				if (elem == 1) {
					tElementsData[k].checked = 1;
                                        for(var l=0;l<tElementsDataFils.length;l++) {
                                            tElementsDataFils[l].checked = 1;
				}
			}
			}
			//affiche/cache le div contenant les data
			var tDivContextData = $$('div.context_data_'+id);
			if (tDivContextData.length > 0 ) tDivContextData[0].toggle();
		}

		// 09/04/2009 : SPS
		//fonction qui coche l'element parent d'un data
		function check_parent(id, id_parent) {
			//on compte le nombre de data coches
			nb_checked = count_checked_elements(id_parent);
			//on recupere l'etat de l'element que l'on veut selectionner
			var isChecked = $('id_'+id).checked;
			//si le data est le dernier coche, alors on peut decocher le parent
			if (isChecked == '' && nb_checked == 0) {
				//on decoche l'element parent
				$('id_'+id_parent).checked = '';
			}
			if (isChecked == 1) {
				//on coche l'element parent
				$('id_'+id_parent).checked = 1;
			}
		}

		//on compte le nombre de data coches pour un parent donne
		function count_checked_elements(id_parent) {
			var nb_checked = 0;
			var tElementsData = $$('input[type="checkbox"].elements_'+id_parent);
			for(var k=0;k<tElementsData.length;k++) {
				if (tElementsData[k].checked == 1) {
					nb_checked++;
				}
			}
			return nb_checked;
		}

		//quand la page se charge, on applique les evenements suivants
		/*Event.observe(window, 'load', function() {


			//a chaque clic sur check_all, on coche toutes les cases
			Event.observe($('check_all'), 'click', function(event) {
				var tCheck = $$('input[type="checkbox"].check');
				//cocher tous les checkbox
				for(var i=0;i<tCheck.length;i++) {
					tCheck[i].checked = 'checked';
				}
				Event.stop(event);
			}.bindAsEventListener());

			//a chaque clic sur check_all, on decoche toutes les cases
			Event.observe($('uncheck_all'), 'click', function(event) {
				var tCheck = $$('input[type="checkbox"].check');
				//decocher tous les checkbox
				for(var i=0;i<tCheck.length;i++) {
					tCheck[i].checked = '';
				}
				Event.stop(event);
			}.bindAsEventListener());


		});*/


		//demande de confirmation de l'installation
		function confirm_context_installation(context) {
			var msg = '<?php echo __T('A_JS_CONTEXT_BUILD_SURE_TO_INSTALL', 'xxxx'); ?>';
			//on remplace la chaine xxxx par la valeur du contexte
			var exp = new RegExp(/xxxx/);
			msg = msg.replace(exp, context);
			//demande une confirmation
			if( confirm(msg) ) return true;
			else return false;
		}

        /**
         * Demande de confirmation de la restauration
         * 23/07/2010 OJT : Correction BZ 16821
         */
		function confirm_context_restoration(context) {
			var msg = '<?php echo __T('A_JS_CONTEXT_BUILD_SURE_TO_RESTORE', 'xxxx', 'xxxx', 'xxxx' ); ?>';
			var exp = new RegExp(/xxxx/g);
			msg = msg.replace(exp, context);
			if( confirm(msg) ) return true;
			else return false;
		}

		//demande de confirmation de la suppression
		function confirm_context_deletion(context) {
			var msg = '<?php echo __T('A_JS_CONTEXT_BUILD_SURE_TO_DELETE', 'xxxx'); ?>';
			var exp = new RegExp(/xxxx/);
			msg = msg.replace(exp, context);
			if( confirm(msg) ) return true;
			else return false;
		}

		//on fait des controles avant de valider le formulaire
		function validate() {
			//on recupere les valeurs des champs d'id context_name et context_version
			var context_name = $F('context_name');
			var context_version = $F('context_version');
			var error=false;
			var error_msg = "";

			if (context_name == '') {
				//on place le curseur ds le champ d'id context_name
				Form.Element.focus('context_name');
				error_msg += '<?php echo __T('A_JS_CONTEXT_BUILD_PLEASE_WRITE_CONTEXT_NAME'); ?><br/>';
				error = true;
			}
			//on regarde si le nom de version respecte le format
			if (!(/^[_a-zA-Z0-9\.]+$/.test(context_name))) {
				error_msg += '<?php echo __T('A_JS_CONTEXT_NAME_NOT_VALID'); ?><br/>';
				Form.Element.focus('context_name');
				error = true;
			}

			if (context_version == '') {
				Form.Element.focus('context_version');
				error_msg += '<?php echo __T('A_JS_CONTEXT_BUILD_PLEASE_WRITE_CONTEXT_VERSION'); ?><br/>';
				error = true;
			}

			if (!(/^[_a-zA-Z0-9\.]+$/.test(context_version))) {
				error_msg += '<?php echo __T('A_JS_CONTEXT_VERSION_NOT_VALID'); ?><br/>';
				Form.Element.focus('context_version');
				error = true;
			}
			//si on a une erreur, on affiche le message dans la zone d'erreur
			if (error) {
				$('error_build').update(error_msg);
				$('error_build').show();
				return false;
			}
			else {
				return true;
			}
		}

		//previsualisation du nom de fichier de contexte (appele a chaque evenement onkeyup sur les champs du nom et de la version de contexte)
		function prev() {
			//on recupere les valeurs des champs d'id context_name et context_version
			var context_name = $F('context_name');
			var context_version = $F('context_version');
			if ( context_name != "") {
				msg = '<?php echo __T('A_JS_CONTEXT_FILE_BE_CREATED','name','version');?>';
				var nameExp = new RegExp(/name/);
				var versionExp = new RegExp(/version/);
				msg = msg.replace(nameExp, context_name);
				msg = msg.replace(versionExp, context_version);
				//mise a jour de la zone de previsualisation
				$('prev').update(msg);
				$('prev').show();
			}
			else {
				$('prev').update("");
				$('prev').hide();
			}
		}

		/*on met a jour la liste des contextes (a chacune des actions sur un des contextes)*/
		//id correspond a un identifiant aleatoire pour avoir une url differente a chaque appel et eviter les pbls de cache (ms pas utilise dans le traitement)
		function updateContextList(id) {
			//la requete ajax va afficher la liste des contextes dans l'element d'id context_list
			new Ajax.Request (
				'php/context_list.php?id='+id,
				{
					method:'get',
					onSuccess: function(resultat) {
						$('context_list').update(resultat.responseText);
					}
				}
			);
		}

		/*on traite le message retourne par les appels ajax*/
		function traitementMsg(msg) {
			var str ="";

			/*on cache les zones d'informations et d'erreurs precedentes*/
			var tOkMsg = $$('div.okMsg');
			var tErrorMsg = $$('div.errorMsg');
			var tDownloadBox = $$('div.downloadBox');

			for(var i=0;i < tOkMsg.length;i++) {
				tOkMsg[i].hide();
			}
			for(var j=0;j < tErrorMsg.length;j++) {
				tErrorMsg[j].hide();
			}
			for(var k=0;k < tDownloadBox.length;k++) {
				tDownloadBox[k].hide();
			}

			//dans le reponse recue, on a SUCCESS ou ERROR pour identifier l'etat du resultat
			//on enleve donc cette chaine et on met le resultat dans la case correspondante
			if (/SUCCESS/.test(msg)) {
				var successStr = new RegExp(/SUCCESS/);
				str = msg.replace(successStr, "");
				$('okMsg_context_list').update(str);
				$('okMsg_context_list').show();
			}
			if (/ERROR/.test(msg)) {
				var errorStr = new RegExp(/ERROR/);
				str = msg.replace(errorStr, "");
				$('errorMsg_context_list').update(str);
				$('errorMsg_context_list').show();
			}
		}

		/*installation du contexte*/
		//l'id passe va servir a eviter les pbls de cache du navigateur
		function context_install(id,context, product) {
			//on demande confirmation a l'utilisateur
			if(confirm_context_installation(context)) {
				//on affiche le chargement
				showLoading();
				new Ajax.Request(
					'php/context_install.php?id='+id,
					{
						method: 'get',
						parameters: {filename: context, product: product},
						onSuccess: function(data) {
							//on cache le chargement
							hideLoading();
							//on met a jour la liste des contextes
							updateContextList(id);
							//on traite la reponse recue et on l'affiche
							traitementMsg(data.responseText);
							dataExport();
						},
						onFailure: function() { alert('Request failed') }
					}
				);
			}
		}

		/*restoration du contexte*/
		function context_restore(id,context) {
			if (confirm_context_restoration(context)) {
				showLoading();
				new Ajax.Request(
					'php/context_restore.php?id='+id,
					{
						method: 'get',
						parameters: {filename: context},
						onSuccess: function(data) {
							hideLoading();
							updateContextList(id);
							traitementMsg(data.responseText);

						},
						onFailure: function() { alert('Request failed') }
					}
				);
			}

		}

		/*suppression du contexte*/
		function context_delete(id,context) {
			if (confirm_context_deletion(context)) {
				showLoading();
				new Ajax.Request(
					'php/context_delete.php?id='+id,
					{
						method: 'get',
						parameters: {filename: context},
						onSuccess: function(data) {
							hideLoading();
							updateContextList(id);
							traitementMsg(data.responseText);
						},
						onFailure: function() { alert('Request failed') }
					}
				);
			}
		}

		var t_id = null;

		//on affiche le chargement
		function showLoading() {
			// initialisation de la tempo
			// maj 04/03/2010 - MPR : Correction du BZ 11686 - Le loading reste bloqué
			// 						 Utilisation de la fonction taLoaderStartLoading (lié aux modifs BBX)
			t_id = setInterval(taLoaderStartLoading,20);
			var pos=0;
			var dir=2;
			var len=0;
			//on affiche les blocs pour le chargement
			$('loader_container').style.display = 'block';
			$('loader_container').style.visibility = 'visible';
			$('loader_background').style.display = 'block';
			$('loader_background').style.visibility = 'visible';
			//on lance l'animation
			// Suppression de l'appel à la fonction animate() ( lié aux modifs BBX)
			// animate();
		}

		//on cache le chargement
		function hideLoading() {
			//on arrete la tempo
			this.clearInterval(t_id);
			//on cache les blocs
			$('loader_container').style.display = 'none';
			$('loader_container').style.visibility = 'hidden';
			$('loader_background').style.display = 'none';
			$('loader_background').style.visibility = 'hidden';
		}

		/**
		 * On appelle un script PHP qui va verifier les champs target_dir des Data Exports pour savoir s'ils sont bons
		 *
		 *	04/09/2009 GHX
		 *		- Création de la fonction
		 *	04/12/2009 GHX
		 *		- Correction du BZ 12160
		 *			-> Utilisation de la methode POST au lieu de GET
		 *
		 * @author GHX
		 * @since CB 5.0.0.08
		 * @version CB 5.0.0.08
		 */
		function dataExport ()
		{
			new Ajax.Request(
				'php/context_dataExport.php',
				{
					method: 'post',
					onSuccess: function(transport) {
						if ( transport.responseText != '' )
						{
							var dataExports = transport.responseText.split('|||');
							for ( i = 0; i < dataExports.length; i++ )
							{
								var d = dataExports[i].split('@@@');
								if ( confirm(d[0]) )
								{
									new Ajax.Request(
										'php/context_dataExport.php',
										{
											method: 'post',
											parameters: {id: d[1]},
											onSuccess: function(transport) {},
											onFailure: function() { alert('Request failed') }
										}
									);
								}
							}
						}
					},
					onFailure: function() { alert('Request failed') }
				}
			);
		} // End function dataExport
	</script>
	<!-- 05/08/2009 - CCT1 : ajout de l'image titre. correction BZ 10290 -->
	<div style='padding:10px;'><center><img src="<?=NIVEAU_0?>images/titres/context_management.gif"/></center></div>
	<div id="adminZone">
		<div align="center" class="texteGrisGrand">Quick links : <a href="#fieldset_upload"><?php echo __T('A_CONTEXT_UPLOAD'); ?></a>, <a href="#fieldset_uploaded_context"><?php echo __T('A_CONTEXT_UPLOADED'); ?></a>, <a href="#fieldset_build"><?php echo __T('A_CONTEXT_BUILD'); ?></a></div>
		<!-- upload context -->
		<div class="adminElement">
			<fieldset>
				<legend><a class="texteGrisGrand" id="fieldset_upload"><?php echo __T('A_CONTEXT_UPLOAD'); ?>&nbsp;</a></legend>
				<div >
					<form method="post" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF'];?>?action=upload">
						<span class="texteGris">
							<input type="file" name="fichier" size="30"/>
							<input type="submit" name="upload" value="<?php echo __T('A_CONTEXT_UPLOAD_BUTTON');?>" class="bouton"/>
						</span>
					</form>
					<?php
                                        // 21/11/2011 BBX
                                        // BZ 24764 : correction des messages "Notice" PHP
					if(isset($_GET['action']) && $_GET['action'] == "upload") {
						$msg_upload = uploadContext();
					}
                                        // 21/11/2011 BBX
                                        // BZ 24764 : correction des messages "Notice" PHP
					if (!empty($msg_upload['error'])) {
					?>
						<div class="errorMsg"><?php echo $msg_upload['error'];?></div>
					<?php
					}
                                        // 21/11/2011 BBX
                                        // BZ 24764 : correction des messages "Notice" PHP
					if (!empty($msg_upload['success'])) {
					?>
						<div class="okMsg"><?php echo $msg_upload['success'];?></div>
					<?php
					}
					?>
				</div>
			</fieldset>
		</div>
		<br/>
		<!-- uploaded contexts -->
		<div class="adminElement">
			<fieldset>
				<legend><a class="texteGrisGrand" id="fieldset_uploaded_context"><?php echo __T('A_CONTEXT_UPLOADED'); ?>&nbsp;</a></legend>
				<div id="context_list">
					<?php
					/*
					le script php/context_list.php est inclus pour effectuer l'affichage au chargement de la page
					apres, les differentes actions effectuent un rechargement ajax via la fonction updateContextList (cf plus haut)
					*/
					include("php/context_list.php");
					?>
				</div>
				<div id="okMsg_context_list" class="okMsg" style="display:none;"></div>
				<div id="errorMsg_context_list"  class="errorMsg" style="display:none;"></div>
			</fieldset>
		</div>
		<br/>
		<!-- build a context -->
		<div class="adminElement">
			<fieldset>
				<legend><a class="texteGrisGrand" id="fieldset_build"><?php echo __T('A_CONTEXT_BUILD'); ?>&nbsp;</a></legend>
				<div>
					<form id="form_build_context" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>?action=build">
						<!-- context properties -->
						<div class="context_title"><?php echo __T('A_CONTEXT_PROPERTIES');?></div>
						<div id="context_properties">
							<div id="context_info">
								<div id="prev" style="display:none;float:right;margin-top:15px;width:350px" class="infoBox"></div>
								<p><label><?php echo __T('A_CONTEXT_NAME');?></label>&nbsp;<input type="text" id="context_name" name="context_name" onkeyup="prev();"/></p>
								<p><label><?php echo __T('A_CONTEXT_VERSION');?></label>&nbsp;<input type="text" id="context_version" name="context_version" onkeyup="prev();"/></p>
							</div>
							<?php
							//on valide les champs du nom et de version de contexte avant de lancer la creation
							?>
							<div style="clear:both;text-align:center;margin-bottom:5px;">
								<input type="submit" id="submit_build" name="submit" value="<?php echo __T('A_CONTEXT_BUILD_BUTTON');?>" class="bouton" onclick="return validate();"/>
							</div>
							<div id="error_build" class="errorMsg" style="display:none;">
							</div>
							<?php
                                                      //  print_r($tab);
							if(isset($_GET['action']) && $_GET['action'] == "build") {
                                                            $elements = array();
                                                            $elements_data = array();
                                                            foreach($_POST['tab'] as $id => $value){
                                                                if(is_array($value)){
                                                                    if(is_int($id)){
                                                                        $elements = array_merge($elements,$value);
                                                                    }else{
                                                                        // 06/02/2014 NSE bz 39066
                                                                        // on utilise l'indice complet (produit_elem) et non pas simplement l'id de l'éléménent qui n'est pas forcémemnt unique
                                                                        if(!is_array($elements_data[$id])){
                                                                            $elements_data[$id] = array();
							}
                                                                        $elements_data[$id] = array_merge($elements_data[$id],$value); 
                                                                    }
                                                                }
                                                            }
                                                           // echo '<br>$elements: ';print_r($elements); print_r($elements_data);
								$msgbuild = buildContext($elements,$elements_data,$_POST['deleted_items'],$_POST['context_name'],$_POST['context_version']);
							}
							if(isset($msgbuild['error'])) {
								echo "<div class=\"errorMsg\">".$msgbuild['error']."</div>";
							}
							//si la creation a marche, on propose le fichier genere en telechargement
							if(isset($msgbuild['archive_name'])) {
								echo "<div class=\"downloadBox\">
									<a href=\"php/download.php?file=".$dir.$msgbuild['archive_name']."\"><b>".__T('A_CONTEXT_DOWNLOAD',$msgbuild['archive_name'])."</b></a>
								</div>";
							}
							?>
						</div>
						<div class="context_title"><?php echo __T('A_CONTEXT_CONTENT');?></div>
						<!-- context elements -->
						<div id="context_content">
                                                    <div class="infoBox" style="margin:12px;">
                                                    <table border="0" width="90%"><tr><td><img src="../images/icones/information.png" border="0" /></td><td class="texteGris">Multiple product Reports, Dashboards and Graphs are not displayed and cannot be included in context.</td></tr></table>
                                                    </div>
							<div id="context_elements">
								<span class="texteGrisBoldU">Elements</span>
								<?php
								$context = new Context();
								//on recupere tous les elements du contexte
								$elts = $context->getAllElements();
                                                                // on récupère les slaves actifs
                                                                // récupère les labels de tous les produits 
                                                                $productsLabels = ProductModel::getProductsLabel();
                                                                foreach ($productsLabels as $product => $label) {
                                                                    // ProductModel::on exclus la Gateway
                                                                    if (!ProductModel::isBlankProduct($product)) {
                                                                        // on grise les produits inactifs
                                                                        if (!ProductModel::isActive($product)) {
                                                                            echo "
                                                                                <div class=\"context_elements\" style=\"margin-bottom:5px;\">
											<input disabled=\"disabled\" type=\"checkbox\" id=\"id_{$product}\" style=\"margin-left: 9px;\" /><img src=\"../images/icones/folder.gif\" id=\"folder_{$product}\" style=\"margin-bottom: -3px;\" /><label for=\"id_{$product}\" style=\"color: #999;font-style:italic;\">{$label}</label>
										</div>\n";
                                                                        }
                                                                        else {
                                                                            echo "
                                                                                <div class=\"context_elements\" style=\"margin-bottom:5px;\">
											<a onclick=\"cacher('ctxt_product_{$product}');openClose({$product},'ctxt_product_{$product}');\" style=\"cursor:pointer;\"><img src=\"../images/icones/tri.gif\" id=\"arrow_{$product}\" /></a><input type=\"checkbox\" onclick=\"check_data('{$product}');\" class=\"check\" name=\"tab[]\" id=\"id_{$product}\" value=\"{$product}\" checked='checked'/><img src=\"../images/icones/folder.gif\" id=\"folder_{$product}\" style=\"margin-bottom: -3px;\" /><label for=\"id_{$product}\">{$label}</label>
										</div>\n";
                                                                            echo "<div class=\"context_product\" id=\"ctxt_product_{$product}\" style=\"display: none\">";


								foreach($elts as $ce) {
									/* 13/05/2009 - SPS
										- on verifie si l'element doit etre afficher (correction bug 9551)
									*/
									if ($ce->isVisible()) {
										//si l'element doit etre affiche par defaut, on le coche
                                                                                    if ($ce->isDefault())
                                                                                        $checked_element = " checked='checked'";
                                                                                    else
                                                                                        $checked_element = "";
										//on recupere les donnees pour chaque element
                                                                                    // pour le produit courant
                                                                                    $data = $ce->getDataForDisplay($product);

										if ($data) {
                                                                                        $dossier = "rempli";
                                                                                        $lien = "<a onclick=\"cacher('context_data_{$product}_{$ce->getId()}');openClose('{$product}_{$ce->getId()}','context_data_{$product}_{$ce->getId()}');\" style=\"cursor:pointer;\"><img src=\"../images/icones/tri.gif\" id=\"arrow_{$product}_{$ce->getId()}\" /></a>";
										}
										// 15:30 06/04/2009 GHX
										// appel de la fonction isSelected au lieu de isDefault
										//	isDefault => si l'élément doit être coché par défaut (donc si la checkbox doit être coché ou non)
										//	isSelected => si on peut sélectionner qu'un seul élément (ex: pour les graphes, on peut choisir de ne mettre qu'un seul graphe dans le contexte)
										elseif ($ce->isSelected()) {
                                                                                        $lien = "";
                                                                                        $dossier = "vide";
                                                                                    } else {
                                                                                        $dossier = "non";
                                                                                        $lien = "";
										}
                                                                                    // 06/02/2014 NSE bz 39031 : alignement des checkbox entre IE et Firefox (la largeur par défaut sur IE est plus grande que sous Firefox)
										echo "<div class=\"context_elements\" style=\"margin-bottom:5px;\">
											$lien<input type=\"checkbox\" onclick=\"check_data('{$product}_{$ce->getId()}');check_parent('{$product}_{$ce->getId()}','{$product}')\" class=\"check elements_{$product}\" name=\"tab[{$product}][]\" id=\"id_{$product}_{$ce->getId()}\" value=\"{$product}_{$ce->getId()}\"".($dossier=='vide'?' disabled="disabled"':$checked_element).($dossier!='rempli'?' style="margin:0 3px 0 12px; width: 12px;"':'')."  />";
                                                                                    if ($dossier != 'non') {
                                                                                        echo "<img src=\"../images/icones/folder.gif\" id=\"folder_{$product}_{$ce->getId()}\" />";
                                                                                    }
                                                                                    echo "<label for=\"id_{$product}_{$ce->getId()}\">{$ce->getLabel()}</label>
										</div>\n";
                                                                                    //on affiche les données pour chaque element
										if ($data) {
                                                                                        echo "<div class=\"context_data elements_{$ce->getId()}\" id=\"context_data_{$product}_{$ce->getId()}\" style=\"display:none;margin-left:25px;\">\n";
											foreach($data as $id => $valeur) {
												//si la donnee doit etre selectionnee
												// 15:30 06/04/2009 GHX
												// appel de la fonction isDefault au lieu de isSelected
                                                                                            if ($ce->isDefault())
                                                                                                $checked_data = "checked='checked'";
                                                                                            else
                                                                                                $checked_data = "";
												echo "<div class=\"context_data_items\">
													<input type=\"checkbox\" class=\"check elements_{$product}_{$ce->getId()}\" id=\"id_{$product}_{$ce->getId()}_{$id}\" onclick=\"check_parent('{$product}_{$ce->getId()}_{$id}','{$product}_{$ce->getId()}');check_parent('{$product}_{$ce->getId()}','{$product}');\" name=\"tab[{$product}_{$ce->getId()}][]\" value=\"{$id}\" $checked_data/><label for=\"id_{$product}_{$ce->getId()}_{$id}\">{$valeur}</label>
												</div>\n";
											}
											echo "</div>\n";
										}
									}
								}
                                                                            echo "</div>";
                                                                        }
                                                                    }
                                                                } // fin foreach product
								?>
							</div>
							<!-- build options -->
							<div id="build_options">
								<span class="texteGrisBoldU">Options</span>
								<p><input type="checkbox" id="deleted_items" name="deleted_items" value="1" checked="checked"/><label for="deleted_items">Include Deleted items</label></p>
                                                                <!--
                                                                <span class="texteGrisBoldU">Products</span>-->
                                                                <?php
                                                               /* $products = ProductModel::getProducts();
                                                                foreach($products as $product){
                                                                    echo "<div class=\"context_elements\" style=\"margin-bottom:5px;\">
											<input type=\"checkbox\" ".($product['sdp_on_off']==0?'disabled ':'')."class=\"check\" name=\"products[]\" id=\"product_".$product['sdp_id']."\" value=\"".$product['sdp_id']."\" $checked_element/><label for=\"product_".$product['sdp_id']."\">".$product['sdp_label']."</label>&nbsp;$lien
										</div>\n";
                                                                }
                                                                */?>
                                                                
                                                                
							</div>
						</div>
					</form>
				</div>
			</fieldset>
		</div>
	</div>
</body>
</html>
