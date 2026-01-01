<?php
/*
 * @cb5040
 *
 * 14/09/2010 MMT bz 17890 tooltip selection NA mal dimensionné/positionné: suppression de la fonction kill2
 * 13/08/2010 DE Firefox bz 16905 : problèmes de présentation
 * 17/08/2010 NSE DE Firefox bz 16876 : ajout de Id en plus de name pour time_to_sel (affiche la liste des heures à cocher)
 * 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
 * 13/09/2010 NSE bz 16876 : lors du clic sur le label des jours pour l'exclusion des heures, la case liée change d'état cochée/décochée, ce qui fait que la mémorisation peut être incidieusement perdue
 * 29/07/2011 MMT bz 22261 :formulaire pas entierement visible sous IE8 et alarme dynamiques
 * 09/08/2011 MMT bz 22261 ajustement taille division alarme dynamiques au cas ou 2 KPIs à long labels sont selectionnées
 * 13/09/2011 NSE DE Master 5.1 / slave 5.0 compatibility : 
 *      - ajout de l'icône et du message de warning sur la sélection des NE parents non dispo sur slave 5.0
 *      - affichage d'un seul accordéon dans la fenêtre de sélection des NE si parents non dispo.
 * 29/01/2015 JLG bz 34921 : le caractère '/' n'est pas autorisé dans le nom de l'alarme comme dans la création de rapport et de graphe
 */
?><?
/*
*	@cb41000@
*
*	Composant de base version cb_4.1.0.00
* 
*	17/06/2009 BBX :
*	=> Constantes CB 5.0
*	=> Header CB 5.0
*	=> Gestion du produit
*	=> Déduction de la famille depuis l'id alarme
*
*       27/07/2010 BBX :
*           - BZ 16652 : ajout d'un mode debug
*
*       30/07/2010 BBX :
*           - BZ 17023 : icone de sélection des NE grise si pas de sélection par l'utilisateur
*           - BZ 17023 : message d'aide ajouté
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
*	- maj 08:30 16/11/2007 Gwénaël : ajout des champs nombre d'itération et périodes
* 	- maj 17:56 24/01/2008 Gwénaël  : Modif pour la récupération du client_type - getClientType($_SESSION['id_user'])
*
*	- maj 11/03/2008, benoit : correction du bug 3819
*	- maj 12/03/2008, benoit : correction du bug 3819
*
*	- maj 17/03/2008, benoit : création d'une nouvelle variable de classe '$this->activated' qui vaut 0 si l'alarme est désactivée ou 1 si elle   est activée
*	- maj 17/03/2008, benoit : création de la ligne du tableau contenant le champ d'activation / désactivation de l'alarme
*	- maj 17/03/2008, benoit : utilisation des messages en base pour les exclusions horaires (correction du bug 5400)
*
*	- maj 31/03/2008, benoit : reprise de la correction du bug 5862. Lors de l'affichage des détails d'une alarme dans un rapport, on ne doit    pas faire apparaitre le lien "Back to the list"
*	
*  08/04/2009 - modif SPS : ajout du charset sur un script javascript pour eviter qqs erreurs sous ie6	
*  10/04/2009 - modif SPS : test de l'existence des variables JS
*  15/04/2009 - modif SPS : 
*                   - ajout de <br/> pour bug affichage 
*                   - ajout de l'id pour le champ additional_field* (plantage JS)
*                   - ajout de l'id pour le champ additional_field_type* (plantage JS)
*/
?>
<?
/*
*	@cb30013@
*
*	27/09/2007 - Copyright Acurio
*
*	Composant de base version cb_3.0.0.13
*

	- maj 28/09/2007, benoit : traitement du cas de l'edition des resultats d'une alarme où la fonction             'save_time_to_sel' n'existe pas

*/
?>
<?
/*
*	@cb22014@
*
*	30/07/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*
*	- 04/09/2007 christophe : par défaut la liste des éléments réseaux est à ALL donc l'image de sélection des NA est on (verte)
*	- 13/08/2007 christophe : affichage de la liste des éléments réseaux sélectionnés.
*	- 30/07/2007 jeremy : 	- ajout d'une fonction PHP qui récupère TOUS les noms des alarmes pour le type courant (static, dynamic...)
*					et écarte l'ancien nom lors d'une modification
*Bugs de qualif
*	- 23/08/2007 - JL : 	- Remplacement de A_ALARM_FORM_LABEl_ALL par A_ALARM_FORM_LABEL_ALL, le l minuscule faussait l'affichage?>
*					- Ajout d'une condition pour ne pas afficher ce lien dans la partie USER mais seulement dans la partie admin où l'on peut modifier les informations
*					- En mode ADMIN on active des checkbox activées, mais pas en mode USER
*						- Malgré l'inclusion des fichier JS et CSS dans le fichiers appelant en mode USER (reporting/.../alarm_detail.php) l'affichage des tooltip ne fonctionne pas dans la partie USER. Il n'y a pas d'erreur de générée
*/
?>
<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- 10/07/2007 christophe ajout de  : get_alarm_network_elements /Permet de récupérer la liste des éléments réseaux de l'alarme
*	- 09/07/2007 christophe : intégration de la sélection des NA.
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
*
*	- 20/04/2007, christophe :  on vat chercher le label 3ème axe dans sys_definition_gt_axe, col 					  external_reference.

	- maj 05/04/2007, gwénaël : mise à jour afin de tenir compte de l'existence d'un 3 ème axe

	- maj 15/05/2007, benoit : si le 3eme axe existe, lors de la sauvegarde on verfie que le champ correspondant   est rempli

*
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
*	@cb1300_iu2000b_pour_cb2000b@
*
*	19/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0
*
*	Parser version iu_2.0.0.0

	- maj 22 11 2006 christophe : on affiche les NA dans l'ordre correspondant à la requête se trouvant dans sys_selecteur_properties.
*/
?>
<?

/*
	- maj 03/10/2006, xavier : modification de la fonction javascript check_field_format.
	  résolution du bug sur le nom d'alarme.

	- maj 24/10/2006, xavier : l'option discontinuous des alarmes dynamiques est disponible pour les heures et    les jours.

	- maj 25/10/2006 xavier : affichage d'un message lorsque le nom de l'alarme est mal renseigné.

	- maj 06/11/2006, benoit : limitation de la taille du label raw/kpi à 40 caractères et vérification de la     valeur du champ (label non nul)

	- maj 27/02/2007, benoit : reduction de la limitation du label à 52 caractères.

	- maj 27/02/2007, benoit : ajout d'un parametre à la fonction 'getFieldValue()' indiquant le nombre de        caracteres autorisé dans les selects.

*/
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/deploy_and_compute_functions.php");

// Destruction des variables de session
unset($_SESSION['alarmsSessionArray']);

Class alarm {

	var $univers;

	function alarm($family,$alarm_id,$alarm_type,$table_alarm,$display_result,$product='')
	{
		global $flag_axe3;

		$this->new_alarm=false;
		$this->display_result = $display_result;
		// connexion à la base de donnée
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
		$this->db = Database::getConnection($product);

		// on récupère le mode d'administration courant (client ou customisateur)
		// tant que le mode client est à 'customisateur' ou que l'alarme a été conçue en mode client,
		// $this->allow_modif reste à 'true'. si l'alarme a été conçue en mode customisateur et
		// que le mode client est à 'client', $this->allow_modif passe à false
		// modif 17:56 24/01/2008 Gwénaël
			// Modif pour la récupération du client_type
		$this->client_type = getClientType($_SESSION['id_user']);
		$this->allow_modif = true;
		
		// 17/06/2009 BBX : BZ 9708
		// Si la famille n'est pas renseignées, on va la récupérer depuis l'id produit et l'id alarme
		if(trim($family) == '') {
			$queryFamily = "SELECT DISTINCT family FROM $table_alarm
			WHERE alarm_id = '$alarm_id'";
			$resultFamily = $this->db->getRow($queryFamily);
			$family = $resultFamily['family'];
		}

		// récupération des données
		$this->alarm_type	= $alarm_type;
		$this->alarm_id		= $alarm_id;
		$this->product		= $product;
		$this->family		= $family;
		$this->table_alarm	= $table_alarm;

		//cas 1 nouvelle alarm
		if (!isset($alarm_id) or ($alarm_id == "")) {
			$this->new_alarm=true;
			$this->alarm_id=0;
			$this->alarm_client_type = $this->client_type;

			// 17/03/2008 - Modif. benoit : si l'alarme n'existe pas, la variable de classe '$this->activated' vaut 1

			$this->activated = 1;
		} else {
			//cas 2 update alarm
			$this->new_alarm=false;

			// on récupère les infos de l'alarme
			$query = "
				SELECT distinct on (alarm_trigger_data_field) *
				FROM $this->table_alarm
				WHERE alarm_id='$alarm_id'
					AND additional_field is null	";
			$row = $this->db->getrow($query);

			$this->alarm_name		= $row["alarm_name"];
			$this->alarm_description	= $row["description"];

            $this->existing_alarm_net_to_sel = '';
            $this->existing_alarm_axe3_to_sel = '';
			$network = explode('_', $row["network"]);
            if( count( $network ) === 2 ){
                $this->existing_alarm_net_to_sel = $network[0];
                $this->existing_alarm_axe3_to_sel = $network[1];
            }
            else if( count( $network ) === 1 ){
                $this->existing_alarm_net_to_sel = $network[0];
            }
			//__debug($row['time'],"time query");
			$this->existing_alarm_time_to_sel	= $row["time"];
			// On enregistre temporairement l'aggrégation temporelle existante
			?><script type="text/javascript">
				
				// 28/09/2007 - Modif. benoit : traitement du cas de l'edition des resultats d'une alarme où la fonction 'save_time_to_sel' n'existe pas

				if (typeof save_time_to_sel == 'function') {
					save_time_to_sel('<?=$this->existing_alarm_time_to_sel?>');
				} else {
					__time_to_sel = '<?=$this->existing_alarm_time_to_sel?>';
				}

			</script><?
			$this->existing_alarm_hn_to_sel	= $row["hn_value"];
			$this->alarm_client_type			= $row["client_type"];
			$this->internal_id				= $row["internal_id"];
			$this->id_group_table			= $row['id_group_table'];

			// l'option discontinuous ne s'applique que pour les alarmes dynamiques
			if ($this->alarm_type == 'alarm_dynamic')
				$this->discontinuous=$row["discontinuous"];

			// si l'alarme a été conçue en mode customisateur et que le mode client est à 'client',
			// $this->allow_modif passe à false
			if($this->alarm_client_type != "client" && $this->client_type == "client")
				$this->allow_modif=false;

			// MP - 21/06/2007 - On récupère les périodes d'exclusion de l'alarme
			if($this->existing_alarm_time_to_sel=="hour" or $this->existing_alarm_time_to_sel=="day" or $this->existing_alarm_time_to_sel=="day_bh")
				$this->get_informations_of_period_exclusion($this->alarm_id,$this->alarm_type);

			// 17/03/2008 - Modif. benoit : si l'alarme existe, '$this->activated' vaut le champ "on_off" (0 ou 1)
			$this->activated = $row['on_off'];
		}

		if ($this->display_result)
			$this->allow_modif=false;

		$this->net_to_sel	= $this->existing_alarm_net_to_sel;
		$this->time_to_sel	= $this->existing_alarm_time_to_sel;
		$this->axe3_to_sel	= $this->existing_alarm_axe3_to_sel;
		$this->time_exclusion_hour = array("00:00","01:00","02:00","03:00","04:00","05:00","06:00","07:00","08:00","09:00",
									"10:00","11:00","12:00","13:00","14:00","15:00","16:00","17:00","18:00","19:00",
									"20:00","21:00","22:00","23:00");
		$this->time_exclusion_day = explode(";",__T('A_ALARM_DAY_OF_WEEK'));
		// création de la liste des kpi et des raw counters
		$this->kpi			= get_kpi($this->family,$this->product);
		$this->counter		= get_counter($this->family,$this->product);

		// début de l'affichage de la partie commune à toutes les alarmes
		$this->display();
	}

	function display_header()	{
		echo '<form name="formulaire" method="post" action="traitement_alarm.php" onsubmit="return check_form()">';
	}

	function display_footer_design() {
		echo '
			</div>
			</td>
			</tr>
			</table>
		</td>
		</tr>
		</table>
		</form>';
	}

	function display_header_design() {	?>
		<table width="550" height=100% border=0 align="center" cellpadding="3" cellspacing="3">
		<tr>
		<td>
		<table width=100% align="center" cellspacing="3" cellpadding="3" class="tabPrincipal">
		<tr>
		<td >
		<div style="padding: 4px;">
		<?
	}

	function display_header_design_light() {	?>
		<table width="550" height="100%" border="0" align="center" valign=top cellpadding="0" cellspacing="0">
		<tr>
		<td>
		<table width=100% align="center" cellspacing="3" cellpadding="3" class="tabPrincipal">
		<tr>
		<td>
		<div style="padding: 4px;">
		<?
	}


	function display_alarm_header() {	?>
		<tr>
		<td width="150" class="texteGris"><li><?echo __T('A_ALARM_FORM_LABEL_ALARM_NAME')?>&nbsp;&nbsp;</td>
		<td>

		<?php /*<!--------------------------------------------------->*/ ?>
		<script type="text/javascript" charset="iso-8859-1">

		// maj 03 10 2006 xavier

		/**
		*  fonction qui renvoit le nom des champs communs aux alarmes qui n'ont pas été correctement renseignés.
		*/
		function check_field_format (champ) {
			nomChamp = false;
			if (champ.value == '') {
				champ.focus();
				nomChamp = "<?=__T('A_JS_ALARM_CREATION_FIELD_NAME')?>";
			}
			else {
				// 29/01/2015 : JLG bz 34921 : le caractère '/' n'est pas autorisé dans le nom de l'alarme comme dans la création de rapport et de graphe
				if (/[^a-zA-Z0-9_ -]/.test( champ.value )) {
					champ.focus();
					// maj 25/10/2006 xavier
					nomChamp = "Please, enter a correct name for this alarm";
				} else {
					if ($('net_to_sel').value == 'makeSelection') {
						$('net_to_sel').focus();
						nomChamp = "Please, fill in the 'Network level' field";
					}
					else if ($('time_to_sel').value == 'makeSelection') {
						$('time_to_sel').focus();
						nomChamp = "<?=__T('A_JS_ALARM_CREATION_TIME_RESOLUTION')?>";
					}
					// 15/05/2007 - Modif. benoit : on verifie que le champ 3eme axe, s'il existe, a été rempli
					else if ($('hn_to_sel'))
					{
						if($('hn_to_sel').value == 'makeSelection')
						{
							<?$axe_information = GetAxe3Information($this->family,$this->product);?>

							$('hn_to_sel').focus();
							nomChamp = "Please, fill in the '<?=$axe_information["axe_type_label"][0]?>' field";
						}
					}
				}
			}
			return nomChamp;
		}

		// maj 24 10 2006 xavier
		function toggle_discontinuous (value) {
			<?
			if ($this->alarm_type == 'alarm_dynamic')
			{
				?>
				if ($('discontinuous')) {
          if ((value == 'hour') || (value == 'day') || (value == 'day_bh')){
					 $('discontinuous').disabled='';
				  }
				  else {
  					$('discontinuous').disabled='disabled';
  				}
  			}
				<?
			}
			?>
		}
	/*
	            Permet de transformer un tableau PHP $tableauPHP
	            en un tableau javascript de nom $nomTableauJS.
	            ATTENTION : à exécuter entre 2 balises html script.
	            On utilise la fonction js decodeURIComponent pour pouvoir gérer la transmission de caractères spéciaux entre
	            javascirpt et php.
	 */

		/* MP - On récupère les hours sélectionnées ou celles présentes en base pour le jour donné.
		* Fonction get_tooltip($day) retourne le  qui doit être affiché sur le onmouseover du label du jour
		* @param $day int n° du jour concerné
		*/
		
		function get_tooltip(day){
			var tooltip;
			var cpt;
			var hours_in_tooltip = new Array();
			<?php
			/*10/04/2009 - modif SPS : test de l'existence des variables JS*/
			?>
      if($("time_to_sel")) {
				if($("time_to_sel").value=="hour"){
					<?php
						// 17/03/2008 - Modif. benoit : utilisation des messages en base pour les exclusions horaires (correction du bug 5400)
						echo "tooltip = '".__T('A_JS_ALARM_NO_EXCLUDED_HOURS_INFORMATION')."';";
					?>
					if($("day["+day+"]").checked==1){
						tab_tmp = new Array();
						if(__hour_excluded[day])
							tab_tmp = __hour_excluded[day];
						cpt= 0;
						if(tab_tmp){
							for(i=0;i<tab_tmp.length;i++){
								if(tab_tmp[i]!=''){
									hours_in_tooltip[cpt] = tab_tmp[i];
									if(cpt==10)
										hours_in_tooltip[cpt] += "<br>";
									cpt++;
								}
							}
							<?php
								// 17/03/2008 - Modif. benoit : utilisation des messages en base pour les exclusions horaires (correction du bug 5400)
								echo "tooltip = '".__T('A_JS_ALARM_EXCLUDED_HOURS_INFORMATION')."' + hours_in_tooltip.join(\" - \");";
							?>
						}
					}
					return tooltip;
				}
			}
		}

		function affiche_tooltip(day){
			<?php
			/*10/04/2009 - modif SPS : test de l'existence des variables JS*/
			?>
			if ($('time_to_sel')) {
                // maj 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
				if ($('time_to_sel').value == 'hour') {
					popalt(document.getElementById(day).value);
					$('lab_'+day).style.cursor='pointer';
				} else {
					$('lab_'+day).style.cursor='pointer';
				}
			}
		}
		</script>

		<? if (!$this->allow_modif) { 
			/* 10/04/2009 - SPS : ajout de l'id alarm_name */
			?>
			<input class="zoneTexteStyleXPFondGris" id="alarm_name" name="alarm_name" style="width:400px" value="<?=$this->alarm_name?>" readonly>
		<? } else {	?>
			<input class="zoneTexteStyleXP" id="alarm_name" name="alarm_name" type="text" maxlength="120" style="width:400px;" value="<?=$this->alarm_name?>">
			<input type='hidden' name='product'	value='<?=$this->product?>'/>
			<input type='hidden' name='family'		value='<?=$this->family?>'/>
			<input type='hidden' name='alarm_type'	value='<?=$this->alarm_type?>'/>
			<input type='hidden' name='alarm_id'	value='<?=$this->alarm_id?>'/>
			<input type='hidden' name='client_type'	value='<?=$this->alarm_client_type?>'/>
			<input type='hidden' name='internal_id'	value='<?=$this->internal_id?>'/>
		<? } ?>
<?php /*
		<!--------------------------------------------------->
*/ ?>
		</td>
		</tr>
		<tr valign='top'>
		<td width='150' class='texteGris'><li><?echo __T('A_ALARM_FORM_LABEL_ALARM_DESCRIPTION');?>&nbsp;&nbsp;</td>
		<td>
		<?php /*
			<!--------------------------------------------------->
*/ ?>
		<? if (!$this->allow_modif) { ?>
			<textarea class='zoneTexteStyleXPFondGris' name='alarm_description' rows='4' style='width:400px ;overflow:auto' readonly><?=$this->alarm_description?></textarea>
		<? } else { ?>
			<textarea class='zoneTexteStyleXP' name='alarm_description' rows='4' style='width:400px ;overflow:auto'><?=$this->alarm_description?></textarea>
		<? } ?>
<?php /*
		<!--------------------------------------------------->
*/?>
		</td>
		</tr>
		<?
	}

	function button_fonction()
	{
		/* 15/04/2009 - modif SPS : ajout de br pour bug affichage */
		?>
		<br/><br/>
		<table border="0" align="center">
		<tr align='center'>
		<?
		if ($this->allow_modif) {
			// 30/07/2007 jeremy : Récupération des informations nécessaire à la création de la liste de noms à tester lors de la validation du formalaire
			?>
			<script type="text/javascript" charset="iso-8859-1">
				//Récupération du nom courant de l'alarme en cours d'edition (i.e. le nom de l'alarme avant modification s'il y en a une)
				if ($('alarm_name')) {
					var _old_name = $('alarm_name').value;
					//Création d'une variable globale dans laquelle on place tous les noms des alarmes présentent dans la BdD pour le type d'alarme courant et la famille courante
					var _table_alarm_name = new Array();
				}
			</script>
			<?
			//Fonction qui créer la liste JS
			$this->get_alarm_name($this->alarm_type);

			//On passe en argument de la fonction check_final : le tableau de noms qui sont présents dans la base de données afin de vérifier que le nom saisie n'y est pas
			//Le bouton SUBMIT est remplacé en type button pour ne pas envoyer le formulaire et bien faire les vérification de doublon du nom d'alarme compte tenu de la complexité de la page
			?>
			<td>
			<input type='submit' class='bouton' name='Submit' onclick='get_hours_excluded();return check_final(_table_alarm_name);' value='Save'/>
			<input type='button' class='bouton' name='Cancel' onclick='javascript:history.back()' value='Cancel'/>
			</td>
			<td>&nbsp;</td>
			<?
		}
		?>
		</tr>
		</table>
		<?
	}

	// On récupère les informations concernant les périodes temporelles d'exclusion
	function get_informations_of_period_exclusion($id_alarm,$alarm_type){

		$query = "
			SELECT ta_value,id
			FROM sys_definition_alarm_exclusion
			WHERE id_alarm = '$id_alarm'
				AND type_alarm = '$alarm_type'
				AND id_parent = 0";
		// __debug($query,"select day to exclude");
		$res = $this->db->getall($query);
		if ($res) {
			foreach ($res as $row) {
				$this->day_exclusion[$row['ta_value']] = $row['ta_value'];
				if ($this->existing_alarm_time_to_sel=='hour') {
					$select_hour_from_day = "
						SELECT ta_value
						FROM sys_definition_alarm_exclusion
						WHERE id_alarm = '$id_alarm'
							AND type_alarm = '$alarm_type'
							AND id_parent = '{$row['id']}'";
					// __debug($select_hour_from_day,"select hour from day");
					$result = $this->db->getall($select_hour_from_day);
					if ($result)
						foreach ($result as $row_hour)
							$this->hour_exclusion[$row['ta_value']][$row_hour['ta_value']] = $row_hour['ta_value'];
				}
			}
		}
	}

	/**
	*10/07/2007 christophe
	*Permet de récupérer la liste des éléments réseaux de l'alarme.
	*@param : integer $id_alarm identifiant de l'alarme
	*@param : string $alarm_type type de l'alarme
	*/
	function get_alarm_network_elements($id_alarm,$alarm_type)
	{
		global $database_connection;
		$lst_alarm_interface = '';

		$query = "
			SELECT lst_alarm_interface
			FROM sys_definition_alarm_network_elements
			WHERE id_alarm = '$id_alarm'
				AND type_alarm = '$alarm_type'
			";
		$row = $this->db->getrow($query);
		if ($row) {
			$lst_alarm_interface = $row['lst_alarm_interface'];
			if ( empty($lst_alarm_interface) ) {
				// 13/08/2007 christophe : Quand tout les éléments réseaux ont été sélectionné, le champ est vide en base.
				// Afin de na pas changer tout le fonctionnement de la sélection des NA, on vat charger la liste
				// des éléments réseaux du NA sélectionné.
				//__debug('all sélectionné');
				//__debug($this->net_to_sel,'network choisit');
				//__debug($this->family,'family');
				//__debug(getNaLabel('all', $this->net_to_sel, $this->family));
				$na			= $this->net_to_sel;
				
				$query_liste	= "
					SELECT eor_id as value,
						CASE WHEN eor_label IS NULL OR eor_label=''
							THEN '(' || eor_id || ')'
							ELSE eor_label
						END as label
					FROM edw_object_ref
					WHERE eor_obj_type = '$na'
						AND eor_on_off=1
					ORDER BY label ASC
				";
				$result = $this->db->getall($query_liste);
				if ($result) {
					foreach ($result as $une_na)
						$lst_alarm_interface .= $na.'@'.$une_na['value'].'@'.$une_na['label'].'|';
					$lst_alarm_interface = substr($lst_alarm_interface,0,strlen($lst_alarm_interface)-1);
				}
			}
		}
		return $lst_alarm_interface;
	}


	/**
	*30/07/2007 Jérémy
	*Permet de créer une liste (javascript) des noms d'alarmes pour un type d'alarme donné
	*@param : string $alarm_type type de l'alarme
	*/
	function get_alarm_name($alarm_type){
		$query = "	SELECT DISTINCT alarm_id, alarm_name
				FROM sys_definition_$alarm_type";
		$result = $this->db->getall($query);

		if ($result) {
			foreach ($result as $row) {
				//On insère dans le tableau JS (qui est une variable globale) les noms des alarmes présentes dans la base de données
				// tout en prenant soin de ne pas insérer l'ancien nom utilisé si l'on est en train de modifier l'alarme
				/* 08/04/2009 - modif SPS : ajout du charset pour eviter qqs erreurs sous ie6
				*  10/04/2009 - modif SPS : test de l'existence des variables JS
        */
				?>
				<script type="text/javascript" charset="iso-8859-1">
					 //on teste l'existence du tableau
           if (typeof _table_alarm_name != "undefined") {
              // On écarte l'ancien nom pour éviter un conflit lorsque l'on conserve le nom
    					if (_old_name != '<?=$row['alarm_name']?>')
    						_table_alarm_name.push("<?=$row['alarm_name']?>");
						}
				</script>
				<?
			}
		}
	}


	// MP - 18/06/2007 - Affichage du bloc contenant lese informations des périodes temporelles d'exclusion
	function display_time_exclusion(){
		$display = 'none';

		if($this->existing_alarm_time_to_sel=='hour' or $this->existing_alarm_time_to_sel=='day' or $this->existing_alarm_time_to_sel=='day_bh')
			$display = 'display';
		?>
		<tr id ="period_exclusion" style="display:<?=$display?>;">
			<td style="width:150px" class="texteGris"><input type="hidden" name="day_to_exclude" value=""/>
			<li><?echo __T('A_ALARM_FORM_LABEL_PERIOD_EXCLUSION');?></li></td>
			<td>
			<fieldset>
				<table class="texteGris">
					<tr>

		<?
		$cpt = 0;
		foreach($this->time_exclusion_day as $k=>$v){

			$this->display_hour_exclusion($k);
			$cpt++;
			echo "<td><input type='hidden' name='$v' id='days[$k]' value=''/></td>";
			// MP - On coche la checkbox si l'alarme existe déjà et que le jour est déjà exclu
			$checked ='';
			if( isset( $this->day_exclusion[$k] ) && $this->day_exclusion[$k] != '' && $this->day_exclusion[$k] != NULL ){
				$checked = "checked = 'true'";
			}

			$popalt = "";
			$onclick= "";
			$tab = "";

			?>
			<script type="text/javascript">
        <?php
			 /*10/04/2009 - modif SPS : test de l'existence des variables JS*/
			 ?>
        if ($('time_to_sel')) {
  				if( __time_to_sel == $('time_to_sel').value ) {
  				<?
  					// 17/03/2008 - Modif. benoit : utilisation des messages en base pour les exclusions horaires (correction du bug 5400)
  					if(count($this->hour_exclusion[$k])>0){
  						if(count($this->hour_exclusion[$k])>10){
  							unset($tooltip);
  							$tooltip = __T('A_JS_ALARM_EXCLUDED_HOURS_INFORMATION')."<br/>";
  							$cpt_tlp = 0;
  							foreach($this->hour_exclusion[$k] as $value){
  								if($cpt_tlp==12)
  									$tooltip.= "<br/>";
  								$tooltip.= $value.":00 - ";
  								$cpt_tlp++;
  							}
  						}else
  							$tooltip = __T('A_JS_ALARM_EXCLUDED_HOURS_INFORMATION')."<br/>".implode(":00 - ", $this->hour_exclusion[$k]).":00";
  					}
  					else{
  						$tooltip = __T('A_JS_ALARM_NO_EXCLUDED_HOURS_INFORMATION');
  					}
  				?>
  				}
				}
			</script>
			<?

			echo '<input type="hidden" value="'.$tooltip.'" id="'.$k.'">';
			$popalt = "onMouseOver='affiche_tooltip($k)' onMouseOut='kill()'";
            // 13/09/2010 NSE bz 16876 : on coche systématiquement la case avant d'ouvrir la fenêtre (au lieu de décocher si elle est déjà cochée
			$onclick ="onclick='$(\"day[$k]\").checked=1;hide_hour_exclusion($(\"time_to_sel\").value,$(\"day[$k]\"),\"contenu$k\");select_hour_exclusion($(\"time_to_sel\").value,$(\"day[$k]\"),\"contenu$k\");'";

			// 23/08/2007 - JL : En mode ADMIN on active des checkbox activées, mais pas en mode USER
            // 13/08/2010 NSE DE Firefox bz 16905 : suppression des 2 espaces pour éviter le retour à la ligne.
			if ($this->allow_modif) {
            // 13/09/2010 NSE bz 16876 : on désolidarise le label de sa case (suppression de for) de façon à avoir un comportement légèrement différent.
				echo "<td><input type='checkbox' id='day[$k]' name='$v' value='$k' style='width:10px' $checked onclick='select_day_exclusion(\"$k\");hide_hour_exclusion($(\"time_to_sel\").value,this,\"contenu$k\");select_hour_exclusion($(\"time_to_sel\").value,this,\"contenu$k\");'/> <label id='lab_$k' $popalt $onclick >$v</label></td>";
			} else {
				echo "<td><input type='checkbox' id='day[$k]' name='$v' value='$k' style='width:10px' $checked disabled/><label for='day[$k]' id='lab_$k' $popalt >$v</label></td>";
			}
			if(is_int($cpt/4))echo "</tr><tr>";
		}
		echo "</tr><tr><tr></tr>";
		$cpt = 0;
		?>
					</tr>
				</table>
			</fieldset>
			</td>
		</tr>
		<?
	}

	// MP - On affiche les heures à exclure pour le jour sélectionné
	function display_hour_exclusion($day){
		?>
		<div id="contenu<?=$day?>" style="display:none;">
			<br/>
			<div style="margin-left:3px;">
			<fieldset>
				<?//23/08/2007 - JL : Remplacement de A_ALARM_FORM_LABEl_ALL par A_ALARM_FORM_LABEL_ALL, le l minuscule faussait l'affichage?>
				<legend>&nbsp;&nbsp;<span id="checkall" value="false"  style="font : normal 10pt Verdana, Arial, sans-serif;color : #585858;text-decoration : none;"><?echo __T('A_ALARM_FORM_FIELDSET_HOUR_EXCLUSION');?>&nbsp;<a href="javascript:check_all(<?=$day?>)"><?echo __T('A_ALARM_FORM_LABEL_ALL');?>&nbsp;</a></span>
				&nbsp;/&nbsp;&nbsp;<span id="apply_all_days" style="font : normal 10pt Verdana, Arial, sans-serif;color : #585858;text-decoration : none;"><a href="javascript:apply_all_days(<?=$day?>)">Apply to all days&nbsp;</a></span>
				</legend>
				<div style="padding:5px;">
				<?
				$cpt = 1;
				$hour_saved="";

				foreach($this->time_exclusion_hour as $key=>$val){
					$checked="";
					if($this->hour_exclusion[$day][substr($val,0,2)]!=NULL and $this->hour_exclusion[$day][substr($val,0,2)]!=''){
						$checked = 'checked = true';
						$value = "'".$this->hour_exclusion[$day][substr($val,0,2)]."'";
					}else
						$value = "''";
					echo "<input type='checkbox' name='hours[$day][$key]' id='hours[$day][$key]' value='$val'$checked />&nbsp;<label for='hours[$day][$key]'>$val</label>&nbsp;";

					$hour_saved.="<input type='hidden' name='hour[$day][$key]' id='hour[$day][$key]' value=$value/>";
					if(is_int($cpt/6) and $cpt!=0)
						echo "<br/>";
					$cpt++;
				}
				?>
				</div>
			</fieldset>
			</div>
		</div>
		<?
		echo $hour_saved;
	}

	function display_info_global_bloc()
	{
		// - 09/07/2007 christophe : ajout de $niveau0
		global $flag_axe3,$niveau0;
                
                // 13/09/2011 NSE DE Master 5.1 / slave 5.0 compatibility
                // Déplacement ici de la déclaration de la variable instanciant alarmModel pour pouvoir utiliser la méthode isAlarmModuleWithNeParent() de l'objet
                // Traitement du type de l'alarme
                $currentType = '';
                switch(trim($this->alarm_type))
                {
                    case 'alarm_static':
                        $currentType = 'static';
                    break;
                    case 'alarm_dynamic':
                        $currentType = 'dynamic';
                    break;
                    case 'alarm_top_worst':
                        $currentType = 'top_worst';
                    break;
                }

                // Instanciation du modèle Alarm
                $alarmModel = new AlarmModel($this->alarm_id,$currentType,$this->product,$this->family);
                
                $productModel = new ProductModel($this->product);              
		?>
		<table>
		<tr>
		<td valign=top class='texteGris' align=left >
		<?$this->display_alarm_header();?>
		</td>
		</tr>
                <?php //13/09/2011 NSE DE Master 5.1 / slave 5.0 compatibility : ajout de l'icône et du massage de warning ?>
                <tr><td class="texteGris"><li><?=__T('A_ALARM_FORM_LABEL_NETWORK_LEVEL')?><?=$alarmModel->isAlarmModuleWithNeParent()?'':" &nbsp; <img src='{$niveau0}/builder/images/error.png' onmouseover=\"popalt('".__T('A_ALARM_WARNING_NO_PARENT_NETWORK_LEVEL',$productModel->getCBVersion())."')\" width='16' height='16'/>"?></td>
<?php /*
		<!--------------------------------------------------->
*/?>
		<?

		//modif 05/04/2007 Gwénaël : récupère les na => na_label
		$net_lvl = getNaLabelList('na',$this->family,$this->product);
		$net_lvl = $net_lvl[$this->family];

		if (!$this->allow_modif) { ?>
			<td>
                                <table cellspacing='0' cellpadding='0'>
				<tr valign='middle'>
				<td>
                                    <input class="zoneTexteStyleXPFondGris" name="net_to_sel" style="width:150px" value="<?=$net_lvl[$this->net_to_sel]?>" readonly>
                                </td>

		<? } else {	?>
			<td>
				<table cellspacing='0' cellpadding='0'>
				<tr valign='middle'>
				<td>
				<!-- 10/07/2007 christophe : à chaque changement de NA, on actualise la sélection des éléments réseaux.  -->
				<!-- 04/09/2007 christophe : par défaut la liste des éléments réseaux est à ALL donc l'image de sélection des NA est on (verte) -->
                <!-- 13/10/2010 OJT : Correction bz18433 ajout du id -->
                <select class="zoneTexteStyleXP" id="net_to_sel" name="net_to_sel" style="width:200px;"
					onChange="updateWindowContent();resetNetworkElementSelection();">
					<? if ($this->net_to_sel == '')
						echo '<option value="makeSelection">'.__T('A_SETUP_ALARM_SELECT_NETWORK_LEVEL').'</option>';
	
					//-modif 05/04/2007 Gwénaël : affichage des na
					foreach($net_lvl as $na => $na_label) {
						$selected="";
						if ($na==$this->net_to_sel)
							$selected="selected='selected'";
						
						echo "\n	<option value='$na' $selected>$na_label</option>";
					}
					?>
				</select>
				</td>
<?php
                    }
?>
<?php
/*
 * 17/05/2010 BBX
 * Modification de la fenêtre de sélection des éléments réseaux
 * cb 5.1
 */

// 13/09/2011 NSE DE Master 5.1 / slave 5.0 compatibility
// déplacement vers plus haut de la déclaration de la variable instanciant alarmModel

// Mémorisation de la sélection déjà configurée en session
$_SESSION['alarmsSessionArray']['ne_selection'] = $alarmModel->getNetworkElementSelection();

// Récupération des informations sur les éléments réseau
$na2na = $alarmModel->getNa2Na();
$na_levels = array_reverse($alarmModel->getNaLevels());

// Eléments cochés
$_SESSION['alarmsSessionArray']['current_selection'] = array();
foreach($na_levels as $na => $naLabel) {
    $_SESSION['alarmsSessionArray']['current_selection'][$na] = $alarmModel->getNetworkElements($na);
}

// Sauvegarde de la sélection par défaut
$_SESSION['alarmsSessionArray']['ne_selection_default'] = $_SESSION['alarmsSessionArray']['ne_selection'];
$_SESSION['alarmsSessionArray']['current_selection_default'] = $_SESSION['alarmsSessionArray']['current_selection'];

// En mode USER, on affiche la sélection des éléments réseau seulement s'il y a une sélection
if(!$this->allow_modif && (count($_SESSION['alarmsSessionArray']['ne_selection']) == 0))
{
    echo '<td style="padding-left:5px"><div class="infoBox">'.__T('U_ALARM_NO_ELEMENT_SELECTED').'</div></td>';
}
// En mode ADMIN, si la topologie est vide, on ne permet pas de sélection et on affiche un message
elseif($this->allow_modif && $alarmModel->isTopologyEmpty())
{
    echo '<td style="padding-left:5px"><div class="infoBox">'.__T('A_SETUP_ALARM_FILTERING_DISABLED').'</div></td>';
?>
<script type="text/javascript">
    function updateWindowContent() {
        return true;
    }
    function resetNetworkElementSelection() {
        return true;
    }
    function naSelectionIsOk() {
        return true;
    }
</script>
<?php
}
// Dans tous les autres cas, on affiche la fenêtre de sélection des éléménts réseau
else
{
?>
                            <td>
                                <div>
                                    <script type="text/javascript">
                                        _listenerOn = false;
                                        
                                        /**
                                         * Cette fonction met à jour le contenu de la fenêtre de sélection
                                         */
                                        function updateWindowContent()
                                        {
                                            // récupération du NA sélectionné
                                            // 23/07/2010 BBX
                                            // il faut forcer na_selected en minuscule à cause de IE8 qui est désormais sensible à la casse
                                            // BZ 16674
                                            var na_selected = $F('net_to_sel').toLowerCase();

                                            // Test sur la sélection ou non d'un Network Level
                                            // 28/07/2010 BBX : Ajout du toggle sur na_selection_help bz17023
                                            // 03/08/2010 OJT : Reopen bz16674, le na_selection_help n'est pas toujours présent
                                            if( na_selected == 'makeselection')
                                            {
                                                $('img_select_na').setStyle({display:'none'});
                                                if( $('na_selection_help') != null ){
                                                    $('na_selection_help').setStyle({display:'none'});
                                                }
                                                return;
                                            }
                                            else
                                            {
                                                $('img_select_na').setStyle({display:'block'});
                                                if( $('na_selection_help') != null ){
                                                    $('na_selection_help').setStyle({display:'block'});
                                                }
                                            }

                                            // on va chercher tous les accordéons
                                            var accs = $$('.accordion_title');
                                            var nb_acc = accs.length;

                                            for (var i=0; i < nb_acc; i++)
                                            {
                                                // pour chaque accordéon, on cherche le id
                                                var acc_id = accs[i].id;				// ex: acc_id = 'htmlPrefix_sgsn_title'
                                                acc_id = acc_id.slice(11);                              // ex: acc_id = 'sgsn_title'
                                                acc_id = acc_id.slice(0,acc_id.lastIndexOf('_'));	// ex: acc_id = 'sgsn'

                                                // var saveNelSelected = true;
                                                // on regarde si le id est dans la liste correspondant au na_level selectionne
                                                if (<?php 
                                                // 13/09/2011 NSE DE Master 5.1 / slave 5.0 compatibility
                                                // si la fonctionalité est disponible sur le slave
                                                if($alarmModel->isAlarmModuleWithNeParent()){
                                                    echo 'na2na[na_selected].indexOf(acc_id) != -1';
                                                }
                                                else{
                                                    // si elle n'est pas disponible, il n'y a qu'un seul accordéon à afficher, celui du NA sélectionné
                                                    echo 'na_selected==acc_id';
                                                }
                                                ?>)
                                                {
                                                    $('htmlPrefix_'+acc_id+'_title').style.display = 'block';
                                                }
                                                else
                                                {
                                                    $('htmlPrefix_'+acc_id+'_title').style.display = 'none';
                                                }
                                            }
                                        }

                                        /**
                                         * Cette fonction gère la (dé)sélection de NE
                                         */
                                        function manageAutomaticSelection(checkboxObj,na,na_value)
                                        {
                                        	// 18/02/2014 GFS - Bug 39622 - [SUP][#42259][CB 5.3][ZKW]: unable to filter on a specific NE_label when the list is too long
                                        	saveInNeSelection(na+"||"+na_value);
                                            var status = (checkboxObj.checked) ? 'checked' : '';
                                            new Ajax.Request('<?=NIVEAU_0?>alarm/php/alarm.ajax.php',{
                                                method:'post',
                                                parameters:'action=manageAutomaticSelection&na='+na+'&na_value='+na_value+'&status='+status+'&id_alarm=<?=$this->alarm_id?>&type_alarm=<?=$currentType?>&product=<?=$this->product?>&family=<?=$this->family?>',
                                                asynchronous:false,
                                                onSuccess:function(stream) {
                                                    if(stream.responseText != '')
                                                    {
                                                        $('htmlPrefix_msgZone').update('<div id="manageAutomaticSelectionMsg" class="infoBox">'+stream.responseText+'</div>');
                                                        _winNaSelection.updateHeight();
                                                        setTimeout("Effect.Fade('manageAutomaticSelectionMsg')",5000);
                                                        setTimeout("$('manageAutomaticSelectionMsg').remove()",6000);
                                                        setTimeout("_winNaSelection.updateHeight()",6500);
                                                    }
                                                }
                                            });
                                        }

                                        /**
                                         * Cette fonction gère le reset de la fenêtre
                                         */
                                        function resetNetworkElementSelection()
                                        {
                                            new Ajax.Request('<?=NIVEAU_0?>alarm/php/alarm.ajax.php',{
                                                method:'post',
                                                parameters:'action=resetNetworkElementSelection',
                                                onSuccess:function(stream) {
                                                    updateSelectionStatus();
                                                }
                                            });
                                        }

                                        /**
                                         * Cette fonction permet d'ajouter un listener sur le bouton fermer
                                         */
                                        function addListener()
                                        {
                                            if(!_listenerOn)
                                            {
                                                // 28/07/2010 BBX
                                                // On ajoute le nouvel évenement sur le bouton fermé
                                                // dès que le bouton existe
                                                // BZ 17023
                                                new PeriodicalExecuter(function(pe) {
                                                    if(_winNaSelection_create)
                                                    {
                                                        var closeBtnId = _winNaSelection.getId()+'_close';
                                                        $(closeBtnId).observe('click', function(event){
                                                            restoreDefaultSelection();
                                                        });
                                                        _listenerOn = true;
                                                        pe.stop();
                                                    }
                                                }, 0.2);
                                            }
                                        }
                                        
                                        /**
                                        * Cette fonction restaure la sélection par défaut
                                        */
                                        function restoreDefaultSelection()
                                        {
                                            new Ajax.Request('<?=NIVEAU_0?>alarm/php/alarm.ajax.php',{
                                                method:'post',
                                                parameters:'action=closeWithoutSaving',
                                                onSuccess:function(stream) {
                                                    updateSelectionStatus();
                                                }
                                            });
                                        }

                                        /**
                                        * Cette fonction valide la sélection courante
                                        */
                                        function saveMySelection()
                                        {
                                            new Ajax.Request('<?=NIVEAU_0?>alarm/php/alarm.ajax.php',{
                                                method:'post',
                                                parameters:'action=saveCurrentSelection',
                                                onSuccess:function(stream) {
                                                    if(stream.responseText == 'no') {
                                                        return false;
                                                    }
                                                    updateSelectionStatus();
                                                }
                                            });
                                        }

                                        /**
                                        * Cette fonction permet d'afficher la liste des éléments réseau sélectionnés
                                        */
                                        function showSelectedElements()
                                        {
                                            if ( $(getId('msgNeSelection')).visible() )
                                            {
                                                $(getId('msgNeSelection')).hide();
                                                _winNaSelection.updateHeight();
                                            }
                                            else
                                            {
                                                $(getId('divNeSearch')).hide();
                                                if ( $(_idCurrentTab) )
                                                {
                                                    $(_idCurrentTab).update('');
                                                    $(_idCurrentTab).hide();
                                                    _idCurrentTab = '';
                                                }
                                                displayCurrentSelection($(getId('selection_na_loading')).innerHTML);
                                                new Ajax.Request("<?=NIVEAU_0?>alarm/php/alarm.ajax.php",{
                                                    method:'post',
                                                    parameters:'action=selectedElements&product=<?=$this->product?>',
                                                    onSuccess:function(stream){
                                                        displayCurrentSelection(stream.responseText);
                                                    }
                                                });
                                            }
                                        }

                                        /**
                                         * Cette fonction gère la (dé)sélection de NE
                                         */
                                        function deleteElementFromList(na,na_value)
                                        {
                                            var status = '';
                                            new Ajax.Request('<?=NIVEAU_0?>alarm/php/alarm.ajax.php',{
                                                method:'post',
                                                parameters:'action=manageAutomaticSelection&na='+na+'&na_value='+na_value+'&status='+status+'&id_alarm=<?=$this->alarm_id?>&type_alarm=<?=$currentType?>&product=<?=$this->product?>&family=<?=$this->family?>',
                                                asynchronous:false,
                                                onSuccess:function(stream) {
                                                    if(stream.responseText != '')
                                                    {
                                                        $('htmlPrefix_msgZone').update('<div id="manageAutomaticSelectionMsg" class="infoBox">'+stream.responseText+'</div>');
                                                        _winNaSelection.updateHeight();
                                                        setTimeout("Effect.Fade('manageAutomaticSelectionMsg')",5000);
                                                        setTimeout("$('manageAutomaticSelectionMsg').remove()",6000);
                                                        setTimeout("_winNaSelection.updateHeight()",6500);
                                                    }
                                                }
                                            });
                                        }

                                        /**
                                         * Met à jour le statuu de l'icone de la sélection des NE
                                         */
                                        function updateSelectionStatus()
                                        {
                                            new Ajax.Request('<?=NIVEAU_0?>alarm/php/alarm.ajax.php',{
                                                method:'post',
                                                // 03/08/2010 OJT : Reopen bz16674 (on force en lowercase)
                                                parameters:'action=getSelectionStatus&id_alarm=<?=$this->alarm_id?>&type_alarm=<?=$currentType?>&product=<?=$this->product?>&family=<?=$this->family?>&na='+$F('net_to_sel').toLowerCase(),
                                                onSuccess:function(stream) {
                                                    // 28/07/2010 BBX
                                                    // Si on a reset la sélection, icone grisée
                                                    // BZ 17023
                                                    var retour = stream.responseText.split('|');
                                                    if(retour[0] == 0) $('img_select_na').className = 'bt_off';
                                                    else $('img_select_na').className = 'bt_on';
                                                    $('img_select_na').writeAttribute("alt_on_over", retour[1]);
                                                    // 28/07/2010 BBX
                                                    // On nourrit également la zone d'aide
                                                    // BZ 17023
                                                    var helpMsg = "<?=__T('A_SETUP_ALARM_HELP_BOX','{NALIST}','{NA}')?>";
                                                    helpMsg = helpMsg.replace('{NALIST}', retour[1]);
                                                    helpMsg = helpMsg.replace('{NA}', $('net_to_sel').options[$('net_to_sel').selectedIndex].text);
                                                    $('na_selection_help').update(helpMsg);
                                                }
                                            });
                                        }

                                        /**
                                        * Fonction de débug permettant d'afficher la sélection courante
                                        */
                                        function getSelection()
                                        {
                                            new Ajax.Request('<?=NIVEAU_0?>alarm/php/alarm.ajax.php',{
                                                method:'post',
                                                parameters:'action=getSelection&id_alarm=<?=$this->alarm_id?>&type_alarm=<?=$currentType?>&product=<?=$this->product?>&family=<?=$this->family?>&na='+$F('net_to_sel'),
                                                onSuccess:function(stream) {
                                                    var retour = stream.responseText;
                                                    $('debug').update(retour);
                                                }
                                            });
                                        }

                                        // 28/07/2010 BBX
                                        // Dès que la page est chargée, on modifie l'évènement onmouseup
                                        // sur l'icone de sélection des NE
                                        // BZ 17023
                                        <?php
                                        if($this->allow_modif)
                                        {
                                        ?>
                                        Event.observe(window, 'load', function() {
                                            $('img_select_na').onmouseup = function() { addListener(); }
                                        });
                                        <?php
                                        }
                                        ?>
                                        
                                    </script>

                                    <script type="text/javascript">
                                        id_product = '<?=$product?>';
                                        na2na = {};

                                        <?php
                                        // 23/07/2010 BBX
                                        // On force les clés du tableau na2na en minuscule
                                        // BZ 16674
                                        ?>
                                        <?php	foreach ($na2na as $na => $na_list) echo "\n    na2na['".strtolower($na)."'] = ['".implode($na_list,"','")."'];"; ?>
                                    </script>

                                    <style type="text/css">
                                        #img_select_na { padding-top:5px; margin-left:2px; height:16px; width:20px; cursor:pointer;}
                                        .bt_off { background: url(<?=$niveau0?>images/icones/select_na_on.png) left no-repeat;}
                                        .bt_on { background: url(<?=$niveau0?>images/icones/select_na_on_ok.png) left no-repeat;}
                                    </style>

                                    <!-- 13/10/2010 OJT : Correction bz18433 getAttribute for popalt -->
                                    <div 
                                        id="img_select_na"
                                        class="bt_<?php if (count($_SESSION['alarmsSessionArray']['current_selection']) > 0) { ?>on<?php } else { ?>off<?php } ?>"
                                        onmouseover="popalt(this.getAttribute('alt_on_over'));"
                                        onmouseout="kill()"
                                        alt_on_over="<?= __T('SELECTEUR_NEL_SELECTION') ?>">
                                    </div>

                                    <link rel="stylesheet" href="<?=URL_NETWORK_ELEMENT_SELECTION?>css/networkElementSelection.css" type="text/css">
                                    <script type="text/javascript" src="<?=URL_NETWORK_ELEMENT_SELECTION?>js/prototype/controls.js"></script>
                                    <script type="text/javascript" src="<?=URL_NETWORK_ELEMENT_SELECTION?>js/networkElementSelection.js"></script>

<?php
                                    include_once(MOD_NETWORK_ELEMENT_SELECTION.'class/networkElementSelection.class.php');
                                    // Nouvelle instance de networkElementSelection
                                    $neSelection = new networkElementSelection();
                                    // 16/01/2013 BBX
                                    // DE Filter Ne
                                    $neSelection->setoldVersion();
                                    // Test du mode lecture seule
                                    if(!$this->allow_modif) $neSelection->setReadOnlyMode(true);
                                    // On définit le type de bouton des éléments réseau
                                    $neSelection->setButtonMode('checkbox');
                                    // Initialisation du titre de la fenêtre.
                                    $neSelection->setWindowTitle(__T('SELECTEUR_NEL_SELECTION'));
                                    // Debug à 0
                                    $neSelection->setDebug(0);
                                    // On initialise le bouton qui permet d'afficher l'iHM, ainsi que les classes css du bouton.
                                    $neSelection->setOpenButtonProperties('img_select_na', 'bt_on', 'bt_off');
                                    // Spécifie une fonction JS qui doit être appelé quand on clique sur reset
                                    $neSelection->setResetButtonProperties('resetNetworkElementSelection()');
                                    // On définit dans quel champ la sauvegarde sera effectuée.
                                    $neSelection->setSaveFieldProperties('nel_selecteur', 'selection', '|s|', 0, "selecteur[nel_selecteur]", 'saveMySelection()');
                                    // Définit les propriétés du bouton View current selection content (NB : si la méthode n'est pas appelée, le bouton n'est pas affiché).
                                    $neSelection->setViewCurrentSelectionContentButtonProperties('');
                                    // On ajoute des onglets
                                    foreach ($na_levels as $na => $na_label)
                                    {
                                        $contentUrl = NIVEAU_0."alarm/php/alarm.ajax.php?action=updateTabContent&idT=".$na."&product=".$this->product."&id_alarm=".$this->alarm_id."&type_alarm=".$currentType."&family=".$this->family."&readonly=".($this->allow_modif ? '0' : '1');
                                        $searchUrl = NIVEAU_0."alarm/php/alarm.ajax.php?action=searchNetworkElement&idT=".$na."&readonly=".($this->allow_modif ? '0' : '1');
                                        $neSelection->addTabInIHM($na,$na_label,$contentUrl,$searchUrl);
                                    }
                                    // Génération de l'IHM.
                                    $neSelection->generateIHM();
?>

                                    <script type="text/javascript">
                                        // 28/07/2010 BBX
                                        // Ajout d'un observer sur les actions à effectuer au chargement de la page
                                        // BZ 17023
                                        document.observe("dom:loaded", function() {
                                            // Refresh de la fenêtre
                                            updateWindowContent();

                                            // Mise à jour icone
                                            updateSelectionStatus();

                                            // Redéfinition de la fonction qui liste les NE sélectionnés
                                            var showCurSelBtn = $$('input.bouton_view_selection');
                                            showCurSelBtn.each(function(item) {
                                                if(item.descendantOf('htmlPrefix_window_select_na'))
                                                {
                                                    item.onclick = function() {
                                                        showSelectedElements();
                                                    }
                                                }
                                            });
                                        });
                                    </script>
                                </div>

<?php
    // 27/07/2010 BBX
    // Ajout d'un bouton de débug sur la sélection afin de simplifier les corrections
    // BZ 16652
    if((get_sys_debug('debug_global') == 1) && (get_sys_debug('alarm_detail') == 1))
    {
        echo '<input type="button" value="Debug" onclick="getSelection()" />';
        echo '<div id="debug"></div>';
    }
?>
                            </td>                            
                            <?php
                            // 28/07/2010 BBX
                            // Ajout de la zone d'aide na_selection_help
                            // BZ 17023
                            if($this->allow_modif)
                            {
                            ?>
                            <td>
                                <div id="na_selection_help" class="infoBox" style="display:block"></div>
                            </td>
                            <?php
                            }
                            ?>
<?php
}
?>
                            </tr>
                        </table>
                    </td>
<?php
/*
 * 25/05/2010 BBX
 * Fin modification de sélection des éléments réseau
 */
?>
<?php /*
		<!--------------------------------------------------->
*/ ?>
		</tr>

		<tr><td class="texteGris"><li><?echo __T('A_ALARM_FORM_LABEL_TIME_RESOLUTION');?></td>
<?php /*
		<!--------------------------------------------------->
*/ ?>
		<?
		$query = 'SELECT agregation, agregation_label
				FROM sys_definition_time_agregation
				WHERE on_off = 1
					AND visible=1
				ORDER BY agregation_rank';
		$result = $this->db->getall($query);
		if ($result) {
			foreach ($result as $row) {
				$array_time[]		= $row['agregation'];
				$array_time_label[]	= $row['agregation_label'];
			}
		}

		if (!$this->allow_modif) {
			$key = array_search($this->time_to_sel, $array_time);
			?>
			<td><input class="zoneTexteStyleXPFondGris" id="time_to_sel" name="time_to_sel" style="width:150px" value="<?=$array_time_label[$key]?>" readonly></td>
			<?
		} else {
			// maj 24 10 2006 xavier
			// maj 29 08 2007 maxime On décoche les périodes d'exclusions si on change de ta
            // 17/08/2010 NSE DE Firefox : ajout de Id en plus de name pour time_to_sel (affiche la liste des heures à cocher)
			?>
			<td><select class="zoneTexteStyleXP" id="time_to_sel" name="time_to_sel" style="width:200px" onclick="save_time_to_sel(this.value)" onchange="remove_choice(this);toggle_discontinuous(this.value);getTaExclusion(this.value,'period_exclusion');">
			<?
			if ($this->time_to_sel == '') { ?>
				<option value='makeSelection'><?=__T('A_SETUP_ALARM_SELECT_TIME_RES')?></option> <?
			}

			for ($i=0;$i<count($array_time);$i++) {
				$selected="";
				if ($array_time[$i]==$this->time_to_sel)
				$selected="selected='selected'";

				// en mode daily, les alarmes dynamiques hourly ne sont pas calculées
				if (($this->alarm_type == 'alarm_dynamic')
					and (get_sys_global_parameters('compute_mode',0,$this->product) == 'daily')
					and ($array_time[$i] == 'hour'))
				{
					?>	<optgroup label="<?=$array_time_label[$i]?>" <?=$selected?>></optgroup>	<?
				} else {
					?>	<option value='<?=$array_time[$i]?>'  <?=$selected?>><?=$array_time_label[$i]?></option>	<?
				}
			}
			?>
			</select></td>
			<?

		}
		?><?php /*
		<!--------------------------------------------------->*/?>
		</tr>
		<tr>
			<?	if(GetAxe3($this->family,$this->product))		$this->display_axe3();	?>
		</tr>

		<?
		$this->display_time_exclusion();

		//$flag_axe3 est calculé dans le fichier utilisant cette classe setup_alarm_detail.php

		if ($this->alarm_type == 'alarm_dynamic')
		{
			$checked="";
			if ($this->discontinuous == 1) $checked = "checked='checked'";
			?>
			<tr><td class="texteGris"><li>Discontinuous</td>
<?php /*
				<!--------------------------------------------------->
	*/?>
				<? if (!$this->allow_modif) { ?>
					<td><input type="checkbox" name="discontinuous" value="1" <?=$checked;?> disabled></td>
				<? } else {	?>
					<td><input type="checkbox" name="discontinuous" value="1" <?=$checked;?>></td>
					<script type="text/javascript">
					if ($('time_to_sel')) toggle_discontinuous($('time_to_sel').value);
					</script>
				<? } ?>
	<?php /*
				<!---------------------------------------------------> */?> 
			</tr>
			<?
		}
		
		// 17/03/2008 - Modif. benoit : création de la ligne du tableau contenant le champ d'activation / désactivation de l'alarme
        // 12/08/2010 NSE DE Firefox bz 16905 : on remet la case à cocher sur la même ligne (déplacement de </li>)
		?>
		<tr>
			<td colspan="2" class="texteGris">
				<li><?=__T('A_ALARM_FORM_LABEL_ALARM_CALCULATION_ACTIVATED')?>&nbsp;&nbsp;<input type="checkbox" id="alarm_activated" name="alarm_activated" value="1" <?=(($this->activated == 1) ? "checked" : "")?> <?=(!$this->allow_modif) ? "disabled" : ""?>/></li>
			</td>
		</tr>
		</table>
		<?
	}


	function display_bandeau() {
		global $niveau0;
		if ($this->alarm_type == 'alarm_static')		$image_name = "setup_alarm_titre_new.gif";
		if ($this->alarm_type == 'alarm_dynamic')		$image_name = "dynamic_alarm_new.gif";
		if ($this->alarm_type == 'alarm_top_worst')	$image_name = "top_worst_new_titre.gif";
		?>
		<table width="550"  align="center" border=0 cellpadding="0" cellspacing="0">
		<tr>
		<td colspan=3>
			<table width=100% cellpadding="3" cellspacing="3">
				<? if (!$this->display_result) { ?>
					<tr>
						<td align='center'><img src='<?=$niveau0?>images/titres/<?=$image_name?>'/></td>
					</tr>
				<? } ?>
			</table>
		</td>
		</tr>
		</table>
		<?
	}

	function display_bloc_info_global() {
		if ($this->display_result) {
			$fieldset = ' (';
			$this->alarm_type=='alarm_static'		? $fieldset .= 'Static alarm'	: 0;
			$this->alarm_type=='alarm_dynamic'	? $fieldset .= 'Dynamic alarm'	: 0;
			$this->alarm_type=='alarm_top_worst'	? $fieldset .= 'Top/Worst list'	: 0;
			$fieldset .= ')';
		}
		?>
		<?$this->display_header_design();?>
		<table width=100% >
		<?	//23/08/2007 - JL : Ajout d'une condition pour ne pas afficher ce lien dans la partie USER mais seulement dans la partie admin où l'on peut modifier les informations

		// 18/03/2008 - Modif. benoit : on doit toujours afficher le lien de retour à la liste en admin. Pour ce faire, on se base sur la présence ou non de la variable GET 'alarm_id'

		//if ($this->allow_modif){ //On affiche le lien back to the list si et seulement si on est en partie admin 
		
		// 31/03/2008 - Modif. benoit : reprise de la correction du bug 5862. Lors de l'affichage des détails d'une alarme dans un rapport, on ne doit pas faire apparaitre le lien "Back to the list"

		if ((isset($_GET['alarm_id'])) && (basename($_SERVER['PHP_SELF']) != "pauto_report_alarm_view.php")) { ?>
			<tr class="texteGris">
				<td align="center"><span style="font : normal 10pt Verdana, Arial, sans-serif;color : #585858;text-decoration : none;"><a href="javascript:history.back()"><u><?=__T('G_PROFILE_FORM_LINK_BACK_TO_THE_LIST')?></u></a></span></td>
			</tr>
		<?	} //Fin condition d'affichage du lien de retour à la page précédente?>
		<tr>

		<td align=left>
		<fieldset>
		<legend class="texteGrisBold">&nbsp;Alarm properties<?=$fieldset?>&nbsp;&nbsp;</legend>
		<?$this->display_info_global_bloc();?>
		</fieldset>
		</td>
		</tr>
		</table>
		<?
	}


  function display_bloc_trigger()
  {
		global $niveau0;
		?>
		
		<table width=100%>
		<tr>
		<td align=left>
		<div id="alarm_tab_view">

		<div class="dhtmlgoodies_aTab">
		<?
		// modif 08:31 16/11/2007 Gwen
			// valeur maximum pour la période pour afficher dans le tooltip
		$max_period = ''; 
		if ( $this->time_to_sel != '' )
			$max_period = get_history($this->family, $this->time_to_sel,$this->product);
		
		// modif 24/12/2007 Gwen
			// convertit le nombre de jour en heures
		if ( $this->time_to_sel == 'hour')
			$max_period *= 24;

		// 29/07/2011 MMT bz 22261 formulaire pas entierement visible sous IE8 et alarme dynamiques
		// 09/08/2011 MMT bz 22261 ajustement taille division alarme dynamiques au cas ou 2 KPIs à long labels sont selectionnées
		$tabDivHeight = 380;
		if ($this->alarm_type == 'alarm_static'){
			$alarm_static		= new alarm_static ($this->family, $this->alarm_id,$this->alarm_type,$this->table_alarm,$this->allow_modif, $max_period, $this->time_to_sel,$this->product);
		}if ($this->alarm_type == 'alarm_dynamic'){
			$tabDivHeight = 490;
			$alarm_dynamic	= new alarm_dynamic ($this->family, $this->alarm_id,$this->alarm_type,$this->table_alarm,$this->allow_modif, $max_period, $this->time_to_sel,$this->product);
		}if ($this->alarm_type == 'alarm_top_worst'){
			$alarm_list		= new alarm_list ($this->family, $this->alarm_id,$this->alarm_type,$this->table_alarm,$this->allow_modif, $max_period, $this->time_to_sel,$this->product);
		}
		?>
		</div>

		<div class="dhtmlgoodies_aTab">
		<?
		$this->display_additionalFields();
		?>
		</div>

		</div>
		<script type="text/javascript">
                <?php
                // 14/03/2011 BBX
                // Correction de la taille de la fenêtre pour les alarmes dynamiques
                // BZ 20906
                // 14/10/2011 BBX
                // BZ 23288 : correction de la taille des fenetres (IE8 / IE9)
                $frameSize = 680;
                if ($this->alarm_type == 'alarm_dynamic') $frameSize = 470;
                if ($this->alarm_type == 'alarm_top_worst') $frameSize = 350;
                // 21/11/2011 BBX
                // BZ 23288 : suppression pure et simple de la hauteur fixe
                ?>
		initTabs('alarm_tab_view',Array('Trigger','Additional fields'),0,600,'',null,'<?=$niveau0?>images/tab-view/');
		</script>
		</td>
		</tr>
		</table>
		<?
	}

	function display()
	{
		$this->display_header();
		?>
		<script>
		<!--
		function vider_additional_field(champ,champ_type) {
			$(champ).options[0].value='makeSelection';
			$(champ).options[0].text='Make a selection';
			$(champ).length=1;
			$(champ).selectedIndex=0
			$(champ_type).selectedIndex=0
		}
		-->
		</script>

		<table width="550"  align="center" border=0 cellpadding="0" cellspacing="0">
		<tr>
			<td  align=center>
			<?$this->display_bandeau();?>
			</td>
		</tr>
		<tr>
			<td>
			<?$this->display_bloc_info_global();?>
			</td>
		</tr>
		<tr>
			<td>
			<?$this->display_bloc_trigger();?>
			</td>
		</tr>
		<tr>
			<td align="right">
			<?$this->button_fonction();?>
			</td>
		</tr>
		<?$this->display_footer_design();?>
		</table>

		<script language="JavaScript">window.focus();</script>
		<?
	}


	function display_axe3()
	{
		if(isset($this->alarm_id))
		{
			// On vat chercher les infos dont on a besoins.
			$query= " select * from $this->table_alarm where alarm_id='$this->alarm_id'";
			$row = $this->db->getrow($query);
			if ($resultat) {
				$gt_name = get_gt_name($row['id_group_table']);
				$selected_axe3 = $row["home_net"]."@".$gt_name;
			}
		}
		?>
		<tr>
			<td>
				<li class="texteGris">
				<?
					// 20/04/2007 christophe :  on vat chercher le label 3ème axe dans sys_definition_gt_axe, col external_reference.
					$axe_information = get_axe3_information_from_family($this->family,$this->product);
					echo $axe_information["axe_type_label"][0];
				?>
				</li>
			</td>
			<td>
	<?php 
			/*<!--------------------------------------------------->
*/ ?>
			<?
			//- modif 05/04/2007 Gwénaël : affichage des na du 3° axe
			$net_lvl = getNaLabelList('na_axe3',$this->family,$this->product);
			$net_lvl = $net_lvl[$this->family];

			if (!$this->allow_modif) {
				foreach($net_lvl as $na => $na_label) {
					if ($na == $this->axe3_to_sel) { ?>
						<input class="zoneTexteStyleXPFondGris" name="hn_to_sel" style="width:150px" value="<?=$na_label?>" readonly> <?
					}
				}
			} else {
				echo '<select name="hn_to_sel" class="zoneTexteStyleXP" style="width:150px;" onchange="remove_choice(this)">';

				if ($this->axe3_to_sel == '')
					echo '<option value="makeSelection">Make a selection</option>';

				$selected = "";
				foreach($net_lvl as $na => $na_label) {
					$selected = ($this->axe3_to_sel == $na) ? " selected=selected " : "";
					echo "\n <option value='$na' $selected>$na_label</option>";
				}
				
				echo '</select>';
			}
		?>
<?php /*
		<!--------------------------------------------------->
*/?>
		</td>
		</tr>
		<?
	}

	function display_bloc_info_additionalFields($valeur)
	{
		global $niveau0;

		$query = "SELECT distinct alarm_id, additional_field_type, additional_field
				FROM $this->table_alarm
				WHERE alarm_id = '$this->alarm_id'
					AND additional_field is not null";
		$result = $this->db->getall($query);

		for ($j = 1; $j <= $valeur; $j++) {
			$row = array_shift($result);
			
			// 12/03/2008 - Modif. benoit : ajout d'un identifiant et d'un nom à la table "additionnal_field"
			
			?>
			<table id="additional_field_table<?=$j?>" name="additional_field_table<?=$j?>">
			<tr><td class="texteGrisPetit">&nbsp;</td></tr>
			<tr><td class="texteGris" colspan="2"><li> <?=__T('A_ALARM_FORM_LABEL_ADDITIONAL_FIELD')."&nbsp;".$j?></td></tr>
			<tr>
<?php /*
			<!--------------------------------------------------->
*/ ?>
			<?
			$array_additional_field_type = Array('kpi','raw');
			$array_additional_field_type_label = Array('KPI','Raw Counter');

			// si l'utilisateur n'a pas l'autorisation de modifier l'alarme, on affiche le label
			// du type de "l'additional field" dans un champ texte non modifiable.
			if (!$this->allow_modif) {
				$key = array_search($row['additional_field_type'], $array_additional_field_type);
				?>
				<td><input class="zoneTexteStyleXPFondGris" name="additional_field_type<?=$j?>" style="width:100px" value="<?=$array_additional_field_type_label[$key]?>" readonly></td>
				<?
			} else {
				// sinon, on affiche la liste des choix possibles

				/**
				 *  27/02/2007 - Modif. benoit : ajout d'un parametre sur la taille max du label dans 'getFieldValue()'
				 *  12/03/2008 - Modif. benoit : ajout de l'appel à la fonction 'deleteCompleteAdditionalFieldLabel()' lors du changement de type de champs additionnels (raw/kpi)
				 *  15/04/2009 - modif SPS : ajout de l'id pour le champ additional_field_type* (plantage JS)
				 **/           				 

				?>
				<td><select class="zoneTexteStyleXP" id="additional_field_type<?=$j?>" name="additional_field_type<?=$j?>" style="width:100px" onchange="deleteCompleteAdditionalFieldLabel(<?=$j?>);getFieldValue(this.value,'<?="additional_field".$j?>','<?=$this->product?>','<?=$this->family?>', 52)">
				<option value='makeSelection'>Type</option>
				<?
				for($i=0;$i<count($array_additional_field_type);$i++) {
					$selected="";
					if ($array_additional_field_type[$i]==$row['additional_field_type'])
					$selected="selected='selected'";
					?>
					<option value='<?=$array_additional_field_type[$i]?>'  <?=$selected?>><?=$array_additional_field_type_label[$i]?></option>
					<?
				}
				?>
				</select></td>
				<?
			}
			?>
<?php /*
			<!--------------------------------------------------->
*/ ?>
			<?
			$array_additional_field = array();
			if ($row['additional_field_type'] == "kpi")
			{
				$array_additional_field = $this->kpi;
			}
			if ($row['additional_field_type'] == "raw")
			{
				$array_additional_field = $this->counter;
			}

			// si l'utilisateur n'a pas l'autorisation de modifier l'alarme, on affiche le label
			// de "l'additional field" dans un champ texte non modifiable.
			if (!$this->allow_modif) {
				// 06/11/2006 - Modif. benoit : limitation du label à 40 caractères

				// 27/02/2007 - Modif. benoit : reduction de la limitation à 52 caractères

				// 12/03/2008 - Modif. benoit : definition d'une variable contenant le label complet du champ additionnel

				$additional_field_complete_label = $array_additional_field[$row['additional_field']];

				if (strlen($additional_field_complete_label) > 52) {
					$additional_field = substr($additional_field_complete_label, 0, 52)."...";
				} else {
					$additional_field = $additional_field_complete_label;
				}
				?>
				<td><input class="zoneTexteStyleXPFondGris" name="additional_field<?=$j?>" style="width:420px" value="<?=$additional_field?>" readonly></td>
				<?
			} else {
				// sinon, on affiche la liste des choix possibles
				
				// 11/03/2008 - Modif. benoit : ajout de l'appel à la fonction 'showCompleteAdditionalFieldLabel()' lors du changement de valeur dans le select des champs additionels
				/* 15/04/2009 - modif SPS : ajout de l'id pour le champ additional_field* (plantage JS)*/
                // 18/08/2010 NSE DE Firefox bz 16905 : on réduit la largeur du select
				?>
				<td>
                    <select class="zoneTexteStyleXP" id="additional_field<?=$j?>" name="additional_field<?=$j?>" style="width:410px" onchange="showCompleteAdditionalFieldLabel(<?=$j?>);remove_choice(this);">
                        <? if ($row['additional_field'] == '') {	?>
                            <option value='makeSelection'>Make a selection</option>
                        <? }

                        foreach($array_additional_field as $name => $label)
                        {
                            // 06/11/2006 - Modif. benoit : limitation du label à 40 caractères
                            // 27/02/2007 - Modif. benoit : reduction de la limitation à 52 caractères
                            // 12/03/2008 - Modif. benoit : ajout de l'attribut 'label_complete' à l'option de la liste des raws / kpis
                            if(trim($label) == "") $label = $name;
                            $label_complete = $label;
                            if (strlen($label) > 52) $label = substr($label, 0, 52)."...";
                            $selected="";
                            if ($name==$row['additional_field'])
                                $selected="selected='selected'";
                            echo "\n	<option value='$name' label_complete='$label_complete' $selected>$label</option>";
                        }
                        // 12/03/2008 - Modif. benoit : appel à la fonction JS 'deleteCompleteAdditionalFieldLabel()' lors du click sur le bouton de suppression de l'additional field
                        ?>
                    </select>
                </td>
				<td>
                    <!-- 19/10/2010 OJT : Correction bz18434, suppresion des &nbsp; inutiles -->
                    <img src='<?=$niveau0?>images/icones/drop.gif' style="cursor:pointer" onclick="deleteCompleteAdditionalFieldLabel(<?=$j?>);vider_additional_field('<?="additional_field".$j?>','<?="additional_field_type".$j?>')">
                </td>
				<?
			}
			?>
<?php /*
			<!--------------------------------------------------->
*/ ?>
			</tr>
			<?php

				// 12/03/2008 - Modif. benoit : si l'on est en mode lecture seule mais que le label du field est tronqué on crée une ligne pour afficher le label complet du field

				if ((!$this->allow_modif) && ($additional_field != $additional_field_complete_label)) {
			?>
				<tr>
					<td></td>
					<td colspan="2" class="texteGris" style="font-size:8pt"><?=$additional_field_complete_label?></td>
				</tr>
			<?php
				}
			?>
			</table>
			<?php
				// 12/03/2008 - Modif. benoit : 
				if ($this->allow_modif) {	?>
					<script type="text/javascript">
            showCompleteAdditionalFieldLabel(<?=$j?>);
          </script>
			<?php }
		}
	}


	function display_additionalFields() {	?>
		<table width="550" align="center" border=0 cellpadding="0" cellspacing="0">
		<tr>
		<td>
			<table width=100%>
			<tr>
				<td align='left'>
					<fieldset>
					<legend class="texteGrisBold">&nbsp;<?echo __T('A_ALARM_FORM_FIELDSET_ADDITIONAL_FIELD_LIST')?>&nbsp;&nbsp;</legend>
					<?$this->display_bloc_info_additionalFields(5);?>
					</fieldset>
				</td>
			</tr>
			</table>
		</td>
		</tr>
		</table>
		<?
	}

}//fin class
?>
