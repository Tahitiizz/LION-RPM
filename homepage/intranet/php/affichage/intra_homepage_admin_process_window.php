<?
/*
 *	@cb51000@
 *	23/06/2010 - Copyright Astellia
 *	Composant de base version cb_5.1.0.00
 *
 *	- 23/06/2010 OJT : Correction Bug #15377
 */
?><?
/**
 *	@cb50414@
 *	07/01/2011 - Copyright Astellia
 *	Composant de base version cb_5.0.4.12
 *
 *	07/01/2011 15:02 SCT : vérification de l'existance de $_GET['action'] avant utilisation => on évite les NOTICES php
?>
<?
/*
 *	@cb41000@
 *	22/01/2009 - Copyright Astellia
 *	Composant de base version cb_4.1.0.00
 *
 *	- maj 22/01/2009 - SLC - ré-écriture pour gérer n produits
 *	- 02/06/2009 BBX : Modification du label de la box. Process Started => Process Launcher
 */
?>
<?
/*
 *	@cb40000@
 *	14/11/2007 - Copyright Acurio
 *	Composant de base version cb_4.0.0.00
 *
 *	- maj 15/04/2008, benoit : correction du bug 6313
 *	- 13/08/2008 GHX : ajout d'un message dans le tracelog pour prévenir qu'il y a eu un start/stop sur l'appli
 *
 */
?>
<?
/*
 *	@cb21201@
 *	14/03/2007 - Copyright Acurio
 *	Composant de base version cb_2.1.2.01
 */
?>
<?php
// 10/12/2009 BBX
// Gestion de l'affichage dans le démon de l'arret ou du lancement des process en multiproduits. BZ 13339
include_once(REP_PHYSIQUE_NIVEAU_0.'class/SSHConnection.class.php');
$productDirectory = REP_PHYSIQUE_NIVEAU_0;
$distantProduct = false;
$ipAdress = get_adr_server();

if(!empty($_GET['id_product']))
{
	$ProductModel = new ProductModel($_GET['id_product']);
	$ProductValues = $ProductModel->getValues();
	$productDirectory = '/home/'.$ProductValues['sdp_directory'].'/';
	$ipAdress = $ProductValues['sdp_ip_address'];
}

if(get_adr_server() != $ipAdress)
{
	$SSH = new SSHConnection($ProductValues['sdp_ip_address'],$ProductValues['sdp_ssh_user'],$ProductValues['sdp_ssh_password'],$ProductValues['sdp_ssh_port']);
	$distantProduct = true;
}
// FIN BZ 13339

// action = start process
if (isset($_GET['action']) && $_GET['action'] == 'process_start') {
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$database = Database::getConnection($_GET['id_product']);
	$database->execute("BEGIN");
	$database->execute("TRUNCATE sys_definition_master");
	$database->execute("INSERT INTO sys_definition_master SELECT * FROM sys_definition_master_ref");
	$database->execute("UPDATE sys_definition_master SET on_off=1 WHERE visible=1");
	$database->execute("COMMIT");

	// modif 14:31 13/08/2008 GHX
	// Ajout d'un message dans le tracelog pour savoir s'il y a eu un start/stop de l'appli
	$date = date("Ymd");
	$filedemon = $productDirectory.'file_demon/demon_'.$date.'.html';

	// 10/12/2009 BBX
	// Gestion de l'affichage dans le démon de l'arret ou du lancement des process en multiproduits. BZ 13339
	if( $distantProduct )
	{
		$SSH->exec( 'echo "<hr><h3>Process is started</h3><hr>" >> '.$filedemon );
        // 23/06/2010 OJT : Correction #15377
        $SSH->exec( 'chmod 777 '.$filedemon.' -f' );
	}
	else
	{
		exec( 'echo "<hr><h3>Process is started</h3><hr>" >> '.$filedemon );
        // 23/06/2010 OJT : bz15377, ajout du chmod
        // 20/09/2011 OJT : bz23735, potentiel WARNING caché par @
        @chmod( $filedemon, 0777 );
    }
	// FIN BZ 13339
}


// action = stop process
if (isset($_GET['action']) && $_GET['action'] == 'process_stop') {
        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
	$database = Database::getConnection($_GET['id_product']);
	$database->execute("BEGIN");
//	$database->debug = 1;

	// we get tables to drop
	$tables = $database->getall("SELECT table_name FROM sys_w_tables_list");
	if ($tables) {
		foreach ($tables as $row) {
			// supprimer les tables
			$database->execute("drop table {$row['table_name']}");
			// supprimer les lignes de sys_w_table_list (pour la vider)
			$database->execute("DELETE FROM sys_w_tables_list WHERE table_name='{$row['table_name']}'");
		}
	}
	unset($tables);

	// Efface toutes les tables du type w_ qui pourraient rester
        // 15/09/2011 BBX
        // BZ 23158 : ajout des tables temp_% dans la liste des tables à effacer
	$tables = $database->getall("SELECT tablename 
            FROM pg_tables 
            WHERE schemaname='public' 
            AND (tablename LIKE 'w_%' OR tablename LIKE 'temp_%')");
	if ($tables)
		foreach ($tables as $row)
                $database->execute("DROP TABLE {$row['tablename']}");

	$requettes = array();
	$requettes[0] = "TRUNCATE sys_process_encours";
	$requettes[1] = "TRUNCATE TABLE sys_requetes";
	$requettes[2] = "TRUNCATE TABLE sys_step_track";
	$requettes[3] = "TRUNCATE TABLE sys_family_track";
	$requettes[4] = "TRUNCATE TABLE sys_flat_file_uploaded_list";
	$requettes[5] = "TRUNCATE TABLE sys_crontab";
	$requettes[6] = "UPDATE sys_definition_master SET on_off='0'";
	$requettes[7] = "TRUNCATE TABLE sys_to_compute";

	foreach($requettes as $req)
		$database->execute($req);

	$database->execute("COMMIT");

	// 15/04/2008 - Modif. benoit : correction du bug 6313. Lors de l'arrêt des process, si le paramètre 'compute_switch' n'est pas vide on restaure la valeur de 'compute_mode' et l'on remet le 'compute_switch' à vide
	if (trim(get_sys_global_parameters('compute_switch',0,$_GET['id_product'])) != "") {
		// Restauration de la valeur de 'compute_mode' (valeur de 'compute_switch')
		$sql = "	UPDATE sys_global_parameters
				SET value='".get_sys_global_parameters('compute_switch',0,$_GET['id_product'])."'
				WHERE parameters='compute_mode'";
		$database->execute($sql);

		// RAZ de la valeur de 'compute_switch'
		$sql = "UPDATE sys_global_parameters SET value = NULL WHERE parameters='compute_switch'";
		$database->execute($sql);
	}

	// modif 14:31 13/08/2008 GHX
	// Ajout d'un message dans le tracelog pour savoir s'il y a eu un start/stop de l'appli
	$date = date("Ymd");
	$filedemon = $productDirectory.'file_demon/demon_'.$date.'.html';

        // 13/12/2011 BBX
        // BZ 24219 : nettoyage de flat_file_zip
        if($distantProduct) {
            $SSH->exec('rm -rf '.$productDirectory.'flat_file_zip/*');
	}
	else {
            exec('rm -rf '.$productDirectory.'flat_file_zip/*');
	}

	// 10/12/2009 BBX
	// Gestion de l'affichage dans le démon de l'arret ou du lancement des process en multiproduits. BZ 13339
	if($distantProduct)
	{
		$SSH->exec('echo "<hr><h3>Process is stopped</h3><hr>" >> '.$filedemon);
	}
	else
	{
		exec('echo "<hr><h3>Process is stopped</h3><hr>" >> '.$filedemon);
	}
	// FIN BZ 13339
}


?>
<!-- 14/09/2010 OJT : Correction bz 16764 pour DE Firefox, ajout de la class 'box' au fieldset -->
<fieldset class="box">
	<legend class="texteGrisBold" style='font-size:10px;'>
	<?php
	// 02/06/2009 BBX : Modification du label de la box. Process Started => Process Launcher
	?>
		&nbsp;<img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif"/>&nbsp;&nbsp;Process Launcher&nbsp;
	</legend>
	<form name="formulaire" method="post" action="" style='margin:0;margin-top:6px;'>

		<div align='center'>
		<table cellspacing="0" cellpadding="0" border="0">

		<?php

		// on boucle sur tous les produits
		foreach ( $products as $product ) {
                        // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::getConnection()
			$database = Database::getConnection($product['sdp_id']);

			$query = "SELECT * FROM sys_definition_master WHERE on_off='1'";
			$started = $database->getall($query);

			// 06/07/2009 BBX : calcul de la coupe du label. BZ 9781
			$productLabel = homepageAdminCorrectProductLabel($product['sdp_label']);

            // 14/09/2010 OJT : Correction bz 16764 pour DE Firefox, réajustement CSS du tableau
			if( $started )
            {
				echo "
					<tr>
						<td>
                            <a title='Stop' href='intranet_homepage_admin.php?action=process_stop&id_product={$product['sdp_id']}' onclick=\"return confirm('Confirm STOP process');\">
                                <img src='images/icones/20x16_pause.png' alt='Stop' width='20' height='16' border='1' style='border:1px solid black;'/>
                            </a>
                        </td>
						<td valign='middle' class='texteGrisBold' style='padding:0 5px;font-size:10px;color:black;text-align:left;'>{$productLabel}</td>
					</tr>
				";
			}
            else
            {
				echo "
					<tr>
						<td>
                            <a title='Start' href='intranet_homepage_admin.php?action=process_start&id_product={$product['sdp_id']}' onclick=\"return confirm('Confirm START process');\">
                                <img src='images/icones/20x16_play.png' alt='Start' width='20' height='16' border='1' style='border:1px solid black;'/>
                            </a>
                        </td>
						<td valign='middle' class='texteGrisBold' style='padding:0 5px;font-size:10px;color:#999;text-align:left;'>{$productLabel}</td>
					</tr>
				";
			}
		}

		?>
		</table>
		</div>
	</form>
</fieldset>

