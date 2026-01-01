<?
/*
*	@cb41000@
*
*	03/12/2008 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	- maj 03/12/2008 - SLC - gestion multi-produit, suppression de $database_connection
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
*	@cb1300p_gb100b_060706@
*
*	06/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0p
*
*	Parser version gb_1.0.0b
*/
?>
<?
	/*
	*	@Class 		AlarmDisplay
	*	@author		 christophe
	* 	@creation 		2005 09 26
	*	@last_update	2005 09 26
	*	Permet d'afficher les alarmes.
	*	On vat chercher les tableaux html dans la table SYS_CONTENU_BUFFER
	*/

	class alarmDisplay{

		/*
		*	Constructeur de la classe.
		*	$id_user				identifiant de 'utilisateur connecté.(utile pr pour la lecture de SYS_CONTENU_BUFFER)
		*/
		function alarmDisplay($id_user, $database_connection, $product = ''){
			$this->id_user = $id_user;
			// $this->database_connection = $database_connection;

			// 03/12/2008 - SLC - gestion multi-produit
			$this->product = $product;
                        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
			$this->db = Database::getConnection($this->product);

			$this->debug = false; 								// Permet d'activer / désactiver l'affichage du débugage.
		}

		// Permet d'afficher toutes les alarmes qui ont été enregistrées dans la table SYS_CONTENU_BUFFER.
		function displayAllAlarms() {
			$id_user = $this->id_user;
			$query = " SELECT *,oid FROM sys_contenu_buffer WHERE id_user=$id_user and object_contenu_type='html' and object_type='alarm' order by id_contenu desc ";
			$result = $this->db->getall($query);
			if ($this->debug) echo $query;
			if ($result) {
				echo '<div style="margin-top:6px;height:300px" id="accordion">';

				global $niveau0;
				foreach ($result as $row) {
					$tab_titre_alarme = explode("@",$row["object_title"]);
					$nom_alarme = $tab_titre_alarme[0];	// nom de l'alarme à afficher.
					$nb_resultats = $tab_titre_alarme[1];	// nombre de résultats calculés.
					$marges = ($nb_resultats >= 8) ? " style='padding : 3px 0px 0px 0px;' " : "  ";
					// On construit la structure en 'accordéon'
					?>
						<div id="<?=$i?>">
							<div id="<?=$i?>" class="accordionTabTitleBar">
								<img src="<?=$niveau0?>images/icones/fleche_alarme.gif" id="fleche" style="vertical-align:middle">
								<span style="font-weight:bold;"><?=$nom_alarme?></span>&nbsp;&nbsp;&nbsp;(<?=$nb_resultats?> results)
								<img src="<?=$niveau0?>images/icones/icone_more.gif" onClick='ouvrir_fenetre("alarmDetail.php?titre=<?=$nom_alarme?>  (<?=$nb_resultats?> results)&oid=<?=$row["oid"]?>","","yes","yes","800","600")' style="vertical-align:middle;">
							</div>
							<div id="<?=$i?>" style="margin: 0; padding: 0;">
								<div style="margin: 3px 3px 0px; padding: 0; overflow:auto; height:300px; width:99%; position:absolute;">
									<?=$row["complement"]?>
								</div>
							</div>
						</div>
					<?
				}
				?>
					</div>
					<script>
						/*
							panelHeight : hauteur du panneau.
						*/
						new Rico.Accordion(
							$('accordion') ,{
								expandedBg          : '#777B84',
								hoverBg             : '#777B84',
								collapsedBg         : '#EAEBED',
								expandedTextColor   : '#B0DDF8',
								expandedFontWeight  : 'bold',
								hoverTextColor      : '#ffffff',
								collapsedTextColor  : '#535760',
								collapsedFontWeight : 'normal',
								hoverTextColor      : '#ffffff',
								borderColor         : '#777B84',
								panelHeight         : 320,
								onHideTab           : null,
								onShowTab           : null
							}
						);
					</script>
				<?
			}
		}

		// Affiche la tableau html d'une alarme en fonction de l'oid.
		function displayAlarmFromOID($oid) {
			$query = " SELECT object_source FROM sys_contenu_buffer WHERE oid=$oid ";
			echo $result = $this->db->getone($query);
		}
	}
?>
