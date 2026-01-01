<?php
/**
 * 
 *  CB 5.2
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 */
?><?
/**
 *	@cb41000@
 *
 *	08/12/2008 - Copyright Astellia
 *
 *	Composant de base version cb_4.1.0.00
 *
 *	08/12/2008 BBX : modifications pour le CB 4.1 :
 *	=> Répercution des modification de la D.E (patch CB 4.0.2.XX)
 *	=> Utilisation de la classe DatabaseConnection
 *	=> Contrôle d'accès
 *	=> Sélection de produit
 *
 *	15/04/2009 - SPS correction erreur JS ie6
 *
 *	25/05/2009 BBX :
 *	=> utilisation des méthodes "propres" de UserModel pour générer les listes des users. BZ 9705, 9706
 *	=> ajout du label du produit en cours
 *	=> ajout d'une icone pour parcourir le répertoire FTP
 *	=> setup_connection_add_parameter : ajout de l'i produit dans les requêtes AJAX. BZ 9704
 *
 *	07/07/2009 MPR :
 *	=> Correction des bugs 10330 et 9704
 *
 *	17/07/2009  MPR :
 *	=> Correction  du bug 10656 - On génère un script php afin de tester la connexion depuis le serveur distant
 *
 *	07/08/2009 GHX
 *		- Correction du BZ 10902 [REC][T&A CB 5.0][TP#1][TS#AC26-CB40][TC#29711][SETUP CONNECTIONS]: Unknown error
 *			-> Problème sur la correction du BZ10656
 *
 *	16/09/2009 BBX : gestion Corporate
 *
 *	04/01/2010 BBX : suppression de la condition sur le vieux paramètre Corporate. BZ 13261
 *
 *	14:18 07/01/2010 SCT : BZ 13663 => désactivation du contrôle sur le répertoire configuré pour une connexion en mode corporate
 *	18/01/2010 NSE : BZ 13767 ajout de encodeURIComponent sur le password pour échaper les caractères ?&'
 *
 *	21/01/2009 BBX : on propose quand même la sauvegarde, même si une connexion plante. BZ 13844
 *
 *	01/02/2010 - MPR : Ajout du mode FTP (actif ou passif) pour les connexions FTP
 *
 *	03/03/2010 BBX : encodage URL du login et du mot de passe afin d'échapper les caractères spéciaux. BZ 14154
*       22/06/2010 - MPR : Correction du bz 16161 - Suppression du div qui est déclaré plus loin dans le script (erreur de merge)
*       21/07/2010 MPR - Correction du bz16244 : On remplace cnx[c] par c puisque champs chunks se nomme "chunk_'[0-X]'_'filename'"
*       22/07/2010 - MPR : Dans check_chunk_field : On boucle sur le nombre total de connexion et non sur une seule nouvelle connexion
*                           Il est possible de créer n connexions sans les avoir enregistré au préalable
*
*       30/07/2010 - MPR : Correction du bz17126 : - On empêche le cochage des types de fichier zip pour SA
*       04/08/2010 - MPR : Le champs reference ne peut pas être utiliser (certains parsers ne renseigne pas cette colonne (HPG par exemple)
 *	17/05/2010 NSE : Installation standard, utilisation d'une variable globale pour les chemins vers les exécutables psql et php (PSQL_DIR et PHP_DIR)
 *  28/07/2010 OJT : Suppression de la constante PHP_DIR (bz17016)
*      13/09/2010 NSE bz 17798 : validation valeur chunck KO sous Firefox : ajout id au formulaire
*      15/09/2010 - MPR : Correction du bz 17802 - Ouverture dans une pop-up du Serveur FTP KO
*      17/09/2010 NSE 17798 : on ne vérifie la valeur du champ que s'il est expected
*      31/01/2011 MMT bz 20347 : ajout sdp_ssh_port dans appel new SSHConnection
*	   09/12/2011 ACS Mantis 837 DE HTTPS support
*	   22/12/2011 ACS BZ 25285 send ajax request to "ProxyRequest" instead of a distant server
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
*	- maj 15:51 20/03/2008 GHX : modif pour la prise en compte du mode CORPORATE
*	- 14/08/2008 BBX : Le champ password n'est plus obligatoire. BZ7223
*	-  04/11/2008 BBX : si les alarmes systèmes sont désactivées, on condamne la zone. BZ 7903
*
*/
?>
<?
/*
*	@cb30000@
*
*	03/07/2007 - Copyright Acurio - JL
*
*	Composant de base version cb_3.0.0.00
*
*	Déplacement du formulaire dans ce même fichier
*	Suppression du fichier "SETUP_CONNECTION.PHP" et donc de l'iframe dans ce même fichier qui y faisait appel
*	Arrêt d'utilisation de la classe "FormProcessor" pour la création des formulaires
*	Ajout de nouveau champs : "Code Sonde" et "Directory"
*	19/07/2007 - Suppression de la limite du nombre de connexion après une demande à Paul
*
*	NOUVEAU CONCEPT      :     UNE   CONNEXION   est   associée   à   UNE   et   UNE   SEULE   SONDE
*	@cb30002@
*	30/08/2007 - Jérémy :	- Suppression du mode de connection SSH
*					- Ajout des messages d'erreur dans la table message_display
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
<?php
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
// maj 17/07/2009 MPR - Correction du bug 10656
include_once (REP_PHYSIQUE_NIVEAU_0.'class/SSHConnection.class.php');

// 22/10/2008 BBX : Timeout FTP en secondes
$ftp_timeout = 30;

// 21/11/2011 BBX
// BZ 24764 : correction des messages "Notice" PHP
if(!isset($_GET['product'])) $_GET['product'] = null;

// Lecture du paramètre global SFTP (DE SFTP, OJT), si il n'existe pas, on prend 0
$enableSFTP = intval( get_sys_global_parameters( 'enable_sftp', 0, $_GET['product'] ) );

/*************************************************************
* Traitement AJAX : génère le code html d'une nouvelle connection
*************************************************************/
if(isset($_GET['id_new_connection'])) {
	// Préparation des valeurs par défaut
	$row = Array('id_connection'=>'',
	'connection_type'=>'local',
	'connection_name'=>'',
	'connection_ip_address'=>'',
    'connection_port'=>'',
	'connection_login'=>'',
	'connection_password'=>'',
	'connection_code_sonde'=>'',
	// maj 01/02/2010 MPR : Ajout du mode de connexion (uniquement utilisé poru les connexions FTP) => mode passif ou actif 
	'connection_mode'=>'',
	'id_region'=>'',
	'on_off'=>'1',
	'connection_directory'=>'',
	'protected'=>0);
	// Génération du code html
	if($_GET['part_to_display'] == 'top')
        display_connection_top($row,$_GET['id_new_connection'], $enableSFTP );
	else
		display_connection_bottom($row,$_GET['id_new_connection'],true);
	exit();
}

$prod = ( isset( $_GET['product'] ) ) ? $_GET['product'] : "";
// 21/11/2011 BBX
// BZ 24764 : correction des messages "Notice" PHP
// 09/12/2011 ACS Mantis 837 DE HTTPS support
if ($prod != "") {
	$productModel = new ProductModel($prod);
	$prodInfos = $productModel->getValues();
}
else {
	$prodInfos = getProductInformations( $prod );
}

/*************************************************************
* Traitement AJAX : test une connexion FTP ou SFTP
*************************************************************/
if( isset( $_GET['ftp_check'] ) )
{
	// Récupération des infos de connexion
    $type          = $_GET['type'];
    $ftp_server    = $_GET['server'];
    $ftp_login     = $_GET['login'];
    $ftp_password  = stripslashes($_GET['password']); // 18/01/2010 NSE BZ 13767 ajout de stripslashes
    $ftp_directory = stripslashes( $_GET['directory'] );
    $ftp_mode      = $_GET['mode']; // maj 16:28 01/02/2010 - MPR : Mode de la connexion FTP (actif ou passif)
    $sftp_port     = $_GET['port']; // 24/02/2011 OJT : DE SFTP Ajout du numéro de port
	
    // 17/07/2009 MPR : Correction du bug 10656 - On génère un script php afin de tester la connexion depuis le serveur distant
    // 07/08/2009 GHX : Correction du BZ 10902 Modification du nom de la variable dans la condiion qui n'était pas bonne
    if( get_adr_server() !== $prodInfos['sdp_ip_address'] )
		{
        // 11:16 07/08/2009 GHX Ajout d'un controle avant de faire la connexion SSH
        if ( empty($prodInfos['sdp_ssh_user']) ){
                exit( '4' );
		}
		try
		{
            //31/01/2011 MMT bz 20347 : ajout sdp_ssh_port dans appel new SSHConnection
            $ssh_connection = new SSHConnection( $prodInfos['sdp_ip_address'], $prodInfos['sdp_ssh_user'], $prodInfos['sdp_ssh_password'], $prodInfos['sdp_ssh_port'] );
			
            // On génère un fichier à exécuter sur le serveur distant
            switch( $type )
            {
                case 'remote_ssh' :
                    $file = '<?php
                                \$res = @ssh2_connect( \''.$ftp_server.'\', \''.$sftp_port.'\' );
                                if( !\$res ) exit( \'1\' );
                                if( !@ssh2_auth_password( \$res, \''.$ftp_login.'\', \''.$ftp_password.'\' ) ) exit( \'2\' );
                                \$stream=@ssh2_exec(\$res, \"test -d \''.$ftp_directory.'\';echo \$?\" );
                                stream_set_blocking(\$stream, true);
                                if( intval( stream_get_contents(\$stream) ) != 0 ) exit( \'3\' );
                                exit( \'OK\' );
                            ?>';
                    break;

                case 'remote' :
			// maj 16:28 01/02/2010 - MPR : Ajout de connection_mode pour enregistrer le mode FTP utilisé ou non
                    $file = '<?php
                                \$ftp_mode=\''.$ftp_mode.'\';
                                \$conn_id = @ftp_connect( \''.$ftp_server.'\', 21, '.$ftp_timeout.' );
                                if( !\$conn_id ) exit( \'1\' );
                                if(!@ftp_login (\$conn_id, \''.$ftp_login.'\', \''.$ftp_password.'\'))exit( \'2\' );
								if( \$ftp_mode == \'1\'){
                                    if( !@ftp_pasv(\$conn_id, false) ) exit( \'6|Active Mode not Available\' );
									}
                                if(\$ftp_mode == \'0\'){
                                    if( !@ftp_pasv(\$conn_id,true) ) exit( \'6|Passive Mode not Available\' );
									}			
								
                                if(@ftp_chdir(\$conn_id, \''.$ftp_directory.'\')){
									@ftp_close(\$conn_id);
                                        exit( \'OK\' );
								}
									@ftp_close(\$conn_id);
                                exit( \'3\' );
                            ?>';
                    break;
								}
			$file_test = '/home/'.$prodInfos['sdp_directory'].'/upload/test_ftp_connexion.php';
			$create_file_test_ftp = $ssh_connection->exec('echo "'.$file.'" > '.$file_test);	
            $test_ftp = $ssh_connection->exec('php -q '.$file_test);
			$ssh_connection->exec("rm -f ".$file_test);
            exit( $test_ftp[0] );
		}
		catch ( Exception $e )
		{
            exit( '5|'.$e->getMessage() );
		}
    }
    else
    {
        switch( $type )
        {
            case 'remote_ssh': // SFTP
                try
                {
                    $res = new SSHConnection( $ftp_server, $ftp_login, $ftp_password, $port );
                    if( !$res->fileExists( $ftp_directory ) ){
                            exit( '3' );
                    }
                }
                catch( Exception $e )
                {
                    exit( (string)$e->getCode() );
                }
                exit( 'OK' );
                break;

            default:
            case 'remote' : // FTP
		// Test de la connexion
                $conn_id = @ftp_connect( $ftp_server, 21, $ftp_timeout );
                if( !$conn_id ){
                    exit( '1' );
		}
		
                $login_result = @ftp_login( $conn_id, $ftp_login, $ftp_password );
                if( !$login_result ){
                    @ftp_close( $conn_id );
                    exit( '2' );
	    } 
		
			// maj 16:28 01/02/2010 - MPR : Test du mode utilisé (actif ou passif)
                if( $ftp_mode == "1" ){
                    if( !@ftp_pasv( $conn_id, false ) ){
                        exit( '6|Active Mode not Available' );
				}
				}			
                if( $ftp_mode == "0" ) {
                    if( !@ftp_pasv( $conn_id, true ) ){
                        exit( '6|Passive Mode not Available' );
			}
                }
		
                if(!@ftp_chdir($conn_id, $ftp_directory)) {
                    @ftp_close( $conn_id );
                    exit( '3' );
			}
                @ftp_close( $conn_id );
                exit( 'OK' );
                break;
			}
	    }
	}

/**
 * Traitement AJAX : test une connexion FTP ou SFTP : test d'écriture
 */
if( isset( $_GET['ftp_check_write'] ) )
{
	// Récupération des infos de connexion	
    $type      = $_GET['type'];
    $server    = $_GET['server'];
	// 01/06/2012 ACS BZ 27378 The website cannot display the page when clicking on Save button
    $loginConnection = $_GET['login'];
    $passwordConnection = stripslashes( $_GET['password'] ); // 18/01/2010 NSE BZ 13767 ajout de stripslashes
    $directory = $_GET['directory'];
    $port      = $_GET['port'];
	
    if( substr( $directory, -1 ) != '/' ){
        $directory .= '/';
    }

    switch( $type )
    {
        case 'remote' :
	// Fichier temporaire
	file_put_contents(REP_PHYSIQUE_NIVEAU_0.'upload/testfile.txt','This is a simple test');
            if( !( $conn_id = @ftp_connect( $server, 21, $ftp_timeout ) ) ) exit( 'KO' );
            if( !@ftp_login( $conn_id, $loginConnection, $passwordConnection ) ) exit( 'KO' );
	
	// maj 16:28 01/02/2010 - MPR : Test du mode utilisé (actif ou passif)
            if( $ftp_mode == "1" ){
                if( !@ftp_pasv( $conn_id, false ) ) exit( 'KO' );
            }
            if( $ftp_mode == "0" ){
                if( !@ftp_pasv( $conn_id, true ) ) exit( 'KO' );
            }
            if( !@ftp_put( $conn_id, $directory.'testfile.txt', REP_PHYSIQUE_NIVEAU_0.'upload/testfile.txt', FTP_ASCII ) ){
                exit( 'KO' );
            }
            unlink( REP_PHYSIQUE_NIVEAU_0.'upload/testfile.txt' );
            if( !@ftp_delete( $conn_id, $directory.'testfile.txt' ) ) exit( 'KO' );
            exit( 'OK' );
            break;

        case 'remote_ssh' :
            try
	{
                $res = new SSHConnection( $server, $loginConnection, $passwordConnection, $port );
                $res->mkdir( $directory.'sftpCheck' );
                $res->rmdir( $directory.'sftpCheck' );
            }
            catch( Exception $e )
	{
                exit( 'KO' );
	}
            exit( 'OK' );
            break;
}
}

/*************************************************************
* Traitement AJAX : test si un rep local est accessible en écriture
*************************************************************/
if(isset($_GET['local_check_write'])) {
	
	// Récupération des infos de connexion	
	$directory = $_GET['directory'];
	
	// LOCAL
	$execCtrl = is_writable($directory);
	
	// OK ou KO ?
	echo ($execCtrl ? 'OK' : 'KO');
	exit;
}

/*************************************************************/

// Intranet top + menu contextuel
include_once(REP_PHYSIQUE_NIVEAU_0 . "intranet_top.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/menu_contextuel.php");

// 27/07/2010 OJT : Correction bz16899, mise en commentaire du contrôle d'accès
/*// Contrôle d'accès
$database = Database::getConnection();
$userModel = new UserModel($_SESSION['id_user']);
$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu ILIKE 'Setup Connection'";
$result = $database->getRow($query);
$idMenu = ($result['id_menu'] == '') ? 0 : $result['id_menu'];
if(!$userModel->userAuthorized($idMenu)) {
	include_once(REP_PHYSIQUE_NIVEAU_0."intranet_top.php");
	echo '<div class="errorMsg">'.__T('A_USER_NOT_ALLOWED_TO_ACCESS').'</div>';
	exit;
}*/

// Sélection du produit
if(!isset($_GET['product'])) {
	require_once(REP_PHYSIQUE_NIVEAU_0.'/class/select_family.class.php');
	new select_family(basename(__FILE__), '', 'connection settings', false, '', 2);
	exit;
}

// Connexion à la base de données
$database = DataBase::getConnection( $_GET['product'] );

/**
 * Afficher la partie haute d'une connection
 *
 * @param array   $row Champs d'une connexion
 * @param integer $i Identifiant unique d'une connexion
 * @param integer $enableSFTP Etat du mode SFTP dans sys_global_parameter
 */
function display_connection_top( $row, $i, $enableSFTP )
{
    $color         = ($i%2==0) ? 'F2F2F2' : 'E9E9E9'; // Détermination des couleurs
    $login_off     = "";
    $pass_off      = "";
    $ip_off        = "";
    $mode_off      = "";
    $port_off      = "";
    $localSelected = ""; // La balise option locale est-elle à selectionner
    $ftpSelected   = ""; // La balise option ftp est-elle à selectionner
    $sftpSelected  = ""; // La balise option sftp est-elle à selectionner
    $autoSelected  = "selected='selected'"; // Selection mode Auto (ftp)
    $passSelected  = ""; // Selection mode Passive (ftp)
    $actiSelected  = ""; // Selection mode Active (ftp)

    // En fonction du type de connection (local, remote (ftp) ou remote_ssh (sftp))
    switch ( $row['connection_type'] )
{
        case 'local' :
            $localSelected = "selected='selected'";
            $login_off     = "disabled='true' style='background-color:#e4e4e4; border:none;'";
            $pass_off      = "disabled='true' style='background-color:#e4e4e4; border:none;'";
            $ip_off        = "disabled='true' style='background-color:#e4e4e4; border:none;'";
            $mode_off      = "disabled='true' style='background-color:#e4e4e4; border:none;'";
            $port_off      = "disabled='true' style='background-color:#e4e4e4; border:none;'";
            $row['connection_port'] = ''; // Robustesse, si un port est en base
            break;

        case 'remote' :
            $ftpSelected = "selected='selected'";
            if( $row["connection_mode"] == "1" )
            {
                $actiSelected = "selected='selected'";
            }
            elseif( $row["connection_mode"] == "0" )
            {
                $passSelected = "selected='selected'";
            }
            $port_off = "disabled='true' style='background-color:#e4e4e4; border:none;'";
            $row['connection_port'] = ''; // Robustesse, si un port est en base
            break;

        case 'remote_ssh' :
            $sftpSelected  = "selected='selected'";
            $mode_off      = "disabled='true' style='background-color:#e4e4e4; border:none;'";
            break;
    }

	?>
	<tr id="setup_connection_<?=$i?>">	
		<td align="center" valign="middle" style="border-bottom:0;background-color:#<?=$color?>">
			<input type="hidden" name="id_connection<?=$i?>" value="<? echo $row["id_connection"];?>"/>
			<?php if($row['protected'] == 1  ) echo '<input type="hidden" id="connection_type'.$i.'" name="connection_type'.$i.'" value="'.$row["connection_type"].'" />';?>
            <select <?php if($row['protected'] == 0) echo 'id="connection_type'.$i.'"'; ?> class="connectionSelect" name="connection_type<?=$i?>" onchange="disable_field('<?=$i?>');" <?php if($row['protected'] == 1) echo 'disabled';?>>
                <option value="local" <?php echo $localSelected ?>>Local</option>
                <option value="remote" <?php echo $ftpSelected ?>>FTP</option>
				<?
                    if( $enableSFTP === 1 )
                    {
                        echo '<option value="remote_ssh" '.$sftpSelected.'>SFTP</option>';
                    }
                ?>
			</select>
		</td>
		<td align="center" valign="middle" style="border-bottom:0;background-color:#<?=$color?>">
			<select <?php if($row['protected'] == 0) echo 'id="connection_mode'.$i.'"'; ?>  <?=$mode_off?> name="connection_mode<?=$i?>" <?php if($row['protected'] == 1) echo 'disabled';?> width="4">
                <option value="" <?php echo $autoSelected; ?>>Auto</option>
                <option value="0" <?php echo $passSelected; ?>>Passive</option>
                <option value="1" <?php echo $actiSelected; ?>>Active</option>
			</select>
		</td>

        <!-- 10/09/2010 BBX : bz17814 Ajout d'un id sur chaque champ -->
		<td align="center" valign="middle" style="border-bottom:0;background-color:#<?=$color?>">
			<input type="text" id="connection_name<?=$i?>" name="connection_name<?=$i?>" value="<? echo $row["connection_name"];?>" size="20" <?php if($row['protected'] == 1) echo 'readonly';?> />
		</td>
		<td align="center" valign="middle" style="border-bottom:0;background-color:#<?=$color?>">
			<input type="text" id="connection_ip_address<?=$i?>" name="connection_ip_address<?=$i?>" value="<? echo $row["connection_ip_address"];?>" <?=$ip_off?> size="16" />
		</td>
        <?php if( $enableSFTP == 1 ){?>
		<td align="center" valign="middle" style="border-bottom:0;background-color:#<?=$color?>">
           <input type="text" id="connection_port<?=$i?>" name="connection_port<?=$i?>" value="<? echo $row["connection_port"];?>" <?=$port_off?> size="4" />
        </td>
        <?php } ?>
        <td align="center" valign="middle" style="border-bottom:0;background-color:#<?=$color?>">
			<input type="text" id="connection_login<?=$i?>" name="connection_login<?=$i?>" value="<? echo $row["connection_login"];?>" <?=$login_off?> size="16" />
		</td>
		<td align="center" valign="middle" style="border-bottom:0;background-color:#<?=$color?>">
			<input type="password" id="connection_password<?=$i?>" name="connection_password<?=$i?>" value="<? echo htmlspecialchars($row["connection_password"]);?>" <?=$pass_off?> size="16" />
		</td>
		<td align="center" valign="middle" style="border-bottom:0;background-color:#<?=$color?>">
			<input type="text" id="connection_code_sonde<?=$i?>" name="connection_code_sonde<?=$i?>" value="<? echo $row["connection_code_sonde"];?>" style='text-align:center;' maxlength="3" size="5" />
		</td>
		<td align="center" valign="middle" style="border-bottom:0;background-color:#<?=$color?>">
			<input type="text" id="id_region<?=$i?>" name="id_region<?=$i?>" value="<? echo $row["id_region"];?>" style='text-align:center;' maxlength="2" size="5" />
		</td>
		<td style="border-bottom:0;background-color:#<?=$color?>">
			<?
            switch ( $row["on_off"] )
            {
				CASE 0 :?>
					<input type="checkbox" name="on_off<?=$i?>" value="1" style="border:0" />
					<?break;
				CASE 1 :?>
					<input type="checkbox" name="on_off<?=$i?>" value="1" style="border:0" CHECKED />
					<?break;
			}?>
		</td>
		<td style="border-bottom:0;background-color:#<?=$color?>">
			<?php if($row['protected'] == 0){ ?>
			<a href="javascript:setup_connection_delete(<? echo $row["id_connection"];?>);" title="Delete this connection">
				<img src="<?=NIVEAU_0?>images/icones/drop.gif" border="0">
			</a>
			<?php }?>
		</td>
	</tr>
	<?php
}

/*******************************************************************
* Cette fonction permet d'afficher la partie basse d'une connection
*******************************************************************/
function display_connection_bottom($row,$i,$new=false)
{
	// Connexion à la base de données
	$database = DataBase::getConnection( $_GET['product'] );
	// Détermination des couleurs
	$color = ($i%2==0) ? 'F2F2F2' : 'E9E9E9';
	// 20/10/2008 BBX : configuration avancée. DE CB
	// 16/09/2009 BBX : gestion corporate
	if(CorporateModel::isCorporate($_GET['product']))
	{
		// Si on est en mode corporate et que l'on ajoute une connexion, on force une valeur au répertoire
		if(empty($row["connection_directory"])) {
			/* /!\ NE PAS MODIFIER LE REPERTOIRE */
			$row["connection_directory"] = '/home/application_ta/upload/export_files_corporate';
		}
	}
	?>
	<tr>
		<td valign="top" style="background-color:#<?=$color?>">
			<img id="bouton_advp_<?=$i?>" src="<?=NIVEAU_0?>images/icones/plus_alarme.gif" border="0" onmouseover="popalt('Display / hide advanced parameters')" onclick="show_hide_advanced_parameters('bouton_advp_<?=$i?>','advanced_<?=$i?>')" style="cursor:pointer;" />
		</td>
		<td align="left" colspan="9" style="background-color:#<?=$color?>">	

		<span style="font-weight:bold"><? echo __T('A_SETUP_CONNECTION_FORM_LABEL_DIRECTORY'); ?></span><font color="red">*</font>																										
			
			<input type="text" id="connection_directory<?=$i?>" name="connection_directory<?=$i?>" value="<? echo $row["connection_directory"];?>" size="120" <?php if($row['protected'] == 1) echo 'readonly';?>/>
			<?php
				// 25/05/2009 BBX : ajout d'une icone pour parcourir le répertoire FTP
                            // 08/03/2011 OJT : Correction bz21147. Gestion dynamque du 'Browse Remote Directory'.
                            $displayRemoteIcon = 'display:none';
                            if($row["connection_type"] == 'remote')
                            {
                                $displayRemoteIcon = '';
				}
			?>
                        <img id="browseRemoteDir<?=$i?>" src="<?=NIVEAU_0?>images/icones/folder.gif" onclick="openRemoteDirectory('<?=$i?>')" onmouseover="popalt('<?=__T('A_SETUP_CONNECTION_BROWSE_REMOTE')?>')" style="cursor:pointer;<?=$displayRemoteIcon?>" />
			<div id="connection_test_<?=$i?>" style="display:inline;width:20px;height:20px;margin-left:5px;"></div>
			<span class="texteRouge" id="files_message_<?=$i?>"></span>
			<span class="texteRouge" id="users_message_<?=$i?>"></span>	
			<span class="texteRouge" id="expected_message_<?=$i?>"></span>

			<?php
			// 03/11/2008 BBX : si les alarmes systèmes sont désactivées, on condamne la zone. BZ 7903
			if(get_sys_global_parameters('alarm_systems_activation','',$_GET['product']) == 0) {
				echo '<span class="texteRouge"><br />'.__T('A_SETUP_CONNECTION_SYSTEM_ALERTS_DISABLED').'</span>';
			}
			?>
			
			<div id="advanced_<?=$i?>" style="display:none;width:725px;padding-top:5px;">	

			<?php
			// 03/11/2008 BBX : si les alarmes systèmes sont désactivées, on condamne la zone. BZ 7903
			$readonly = '';
			$disabled = '';

			$alarm_systems_activation = (get_sys_global_parameters('alarm_systems_activation','',$_GET['product']) == 1);
			$activation_source_availability = (get_sys_global_parameters('activation_source_availability','',$_GET['product']) == 1);

			if($alarm_systems_activation == 0) {
				$readonly = ' readonly';
				$disabled = ' disabled';
			?>
                        <!--
                            maj 22/06/2010 - MPR : Correction du bz16161 - Suppression du div qui est déclaré plus loin dans le script (erreur de merge)
				<div style="position:absolute;width:750px;height:155px;z-index:999;background-color:#EEEEEE;-moz-opacity:0.5;opacity: 0.5;filter:alpha(opacity=50);">
				&nbsp;
				</div>
                        -->
			<?php }

                        // recupere la liste des flat_file
			// et ajoute les colonnes suivantes (desactive par defaut) :
			// system_alert, file_expected, data_chunks
                        // maj 30/07/2010 - MPR : Correction du bz17126 : - On empêche le cochage des types de fichier zip pour SA
                        //                         - Récupération du champs reference pour identifier les types de fichier ZIP
			// maj 04/08/2010 - MPR :  - Le champs reference ne peut pas être utiliser (certains parsers ne renseigne pas cette colonne (HPG par exemple)
                        $query = "
				SELECT id_flat_file, flat_file_name,
				data_collection_frequency, data_chunks, granularity,
				0 as system_alert, 0 AS file_expected, reference

				FROM sys_definition_flat_file_lib
									WHERE on_off = 1
                                    AND (lower(flat_file_naming_template) NOT ILIKE '%.zip%' )
									ORDER BY flat_file_name";
			$files = $database->getAll($query);

			// renseigne la colonne trigger_alarm
                        $trigger_alarm = $file_expected = array();

                        if( $row["id_connection"] !== "" ){
                            // maj 30/07/2010 - MPR : Correction du bz17126 : On ne prend pas en compte les types de fichier ZIP
                            $query = "
                                    SELECT f.id_flat_file
										FROM sys_definition_flat_file_lib f, sys_definition_flat_file_per_connection c
										WHERE f.id_flat_file = c.sdffpc_id_flat_file
                                        AND (lower(flat_file_naming_template) NOT ILIKE '%.zip%' )
										AND f.on_off = 1
                                    AND sdffpc_id_connection = {$row["id_connection"]}";
                            $trigger_alarm = $database->getAll($query);

                            // renseigne la colonne file_expected
                            $query = "
                                    SELECT sdsftpc_id_flat_file, sdsftpc_data_chunks
                                    FROM sys_definition_sa_file_type_per_connection
                                    WHERE sdsftpc_id_connection={$row["id_connection"]}";

                            $file_expected = $database->getAll($query);
										}

			foreach ($files as &$f) {
				foreach ($trigger_alarm as $trig) {
					if ($trig['id_flat_file'] == $f['id_flat_file']) {
						$f['system_alert'] = true;
						break;
									}
										}

				foreach ($file_expected as $fe) {
					if ($fe['sdsftpc_id_flat_file'] == $f['id_flat_file']) {
						$f['file_expected'] = true;
						$f['data_chunks'] = $fe['sdsftpc_data_chunks'];
						break;
										}
									}
			}
			// Utilisé pour le mapping entre valeur<->affichage pour la fréquence du SA
			$sa_freq_name = array('0.25' => '15min', '1' => 'Hour', '24' => 'Day');
								?>
			<fieldset style="padding:0">
			<table width="100%" border="0">
<style>
/* setup connexion style*/
.sc th, .sc_dark td {
	background-color: #E9E9E9;
	font-weight: normal;
}
.sc th, .sc td {
	width: 92px;
	min-width: 92px;
	max-width: 92px;
	margin: 0;
	padding: 2px 4px 2px 4px;
	text-align: center;
	font-size: 9pt;
}
.sc th.spacer, .sc td.spacer {
	margin: 0;
	padding: 0;
	width: 12px;
	min-width: 12px;
	max-width: 12px;
}
</style>
				<tr>
					<td align="left" style="background-color:#E9E9E9;" colspan="3">
						<div>
							<table class="zoneTexteStyleXP sc" border="0" width="720px">
								<tr>
									<th>Send system alerts</td>
									<?php if ($activation_source_availability) { ?>
									<th>File expected for Source Availability</td>
									<?php } ?>
									<th>Data file name</td>
									<?php if ($activation_source_availability) { ?>
									<th>Data granularity</td>
									<th>Data collection frequency</td>
									<th>Data chunks</td>
									<?php } ?>
									<th class="spacer">&nbsp;</td>
								</tr>
							</table>
							</div>
						<div style="height: 140px;overflow-y : scroll;">
							<table class="zoneTexteStyleXP sc" border="0" width="700px">
								<?php
								$color = 'F2F2F2';
								foreach ($files as $file) {
									$color = ($color == 'F2F2F2') ? 'E9E9E9': 'F2F2F2';
									$style = "style=\"background-color:#$color;\"";
									$html = "<tr>";

									// system alert
									$id = "selected_file_{$i}_{$file['id_flat_file']}";
									$checked = ($alarm_systems_activation && ($new || $file['system_alert'])) ? 'checked': '';
									if ($alarm_systems_activation) {
                                                                                // maj 30/07/2010 - MPR : Correction du bz17126 : - On empêche le cochage des types de fichier zip
                                                                                $onclick = ( strtolower($file['reference']) != 'zip' ) ? "onclick=\"checkFiles({$i})\"" : " disabled ";

										// si les alertes sont activées
										$html .= "<td $style>";
										$html .= "<input type=\"checkbox\" id=\"{$id}\" name=\"{$id}\" value=\"{$file['id_flat_file']}\" style=\"border:0\" {$checked} {$disabled} {$onclick} />";
										$html .= "</td>";
									}
                                                                        else
                                                                        {
										// si les alertes sont desactivées
										$html .= "<td $style>";
                                                                            $html .= "<input type=\"checkbox\" id=\"{$id}\" name=\"{$id}\" value=\"{$file['id_flat_file']}\" style=\"border:0\" {$checked} {$disabled} {$onclick} />";
										$html .= "</td>";
										}

									// file expected
									if ($activation_source_availability) {
										$id = "expected_file_{$i}_{$file['id_flat_file']}";
										$checked = ($file['file_expected']) ? 'checked': '';

										$html .= "<td $style>";
                                                                                // maj 30/07/2010 - MPR : Correction du bz17126 : - On empêche le cochage des types de fichier zip
                                                                                $onclick=( strtolower($file['reference']) != 'zip') ? "onclick=\"checkExpectedFiles({$i})\"" : "disabled";
										$html .= "<input type=\"checkbox\" id=\"{$id}\" name=\"{$id}\" value=\"{$file['id_flat_file']}\" style=\"border:0\" {$checked} {$onclick} />";
										$html .= "</td>";
									}

									// Data file name
									$html .= "<td $style>{$file['flat_file_name']}</td>";

									if ($activation_source_availability) {
										// Data granularity
										$html .= "<td $style>".ucfirst($file['granularity'])."</td>";

										// Data collection frequency
										$id = "frenquency_{$i}_{$file['id_flat_file']}";
										$html .= "<td $style>";
										$html .= "{$sa_freq_name[$file['data_collection_frequency']]}";
										$html .= "<input type=\"hidden\" id=\"{$id}\" name=\"{$id}\" value=\"{$file['data_collection_frequency']}\" />";
										$html .= "</td>";

										// Data chunks
										$id = "chunk_{$i}_{$file['id_flat_file']}";
										$chunk_disabled = ($file['file_expected']) ? '': 'disabled="true" style="background-color:#e4e4e4"';
										$html .= "<td $style>";
										$html .= "<input type=\"text\" id=\"{$id}\" name=\"{$id}\" value=\"{$file['data_chunks']}\" style=\"text-align:center;\" maxlength=\"2\" size=\"2\" {$chunk_disabled} />";
										// Granularity
										$id = "granularity_{$i}_{$file['id_flat_file']}";
										$html .= "<input type=\"hidden\" id=\"{$id}\" name=\"{$id}\" value=\"{$file['granularity']}\" />";
										$html .= "</td>";
									}
									$html .= "</tr>";
									echo $html;
								}
								?>
							</table>
							</div>
						<div>
							<table class="zoneTexteStyleXP sc sc_dark" border="0" width="720px">
								<tr>
									<td>
										<input type="checkbox" id="selection_files<?=$i?>" style="border:0" onclick="check_uncheck_all(<?=$i?>)"<?=$disabled?> />
										<label><i>Select / Unselect all</i></label>
						</td>
									<?php if ($activation_source_availability) { ?>
									<td>
										<input type="checkbox" id="selection_files_expected<?=$i?>" style="border:0" onclick="check_uncheck_all_expected(<?=$i?>)" />
										<label><i>Select / Unselect all</i></label>
									</td>
									<?php } ?>
									<td>&nbsp;</td>
									<?php if ($activation_source_availability) { ?>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<?php } ?>
									<td class="spacer">&nbsp;</td>
								</tr>
							</table>
						</div>
					</td>
				</tr>
				<?php if ($disabled) {?>
				<tr>
					<td>
						<div style="position:absolute;width:720px;height:140px;z-index:999;background-color:#EEEEEE;-moz-opacity:0.5;opacity: 0.5;filter:alpha(opacity=50);">
						&nbsp;
						</div>
					</td>
				</tr>
				<?php }?>
				<tr>
						<!-- Configuration des users -->
						<td align="right" style="background-color:#<?=$color?>">

							<span style="margin-right:125px;font-weight:bold">Available users</span>
							<select class="zoneTexteStyleXP" id="all_users_list_<?=$i?>" name="all_users_list_<?=$i?>" multiple style="width:225px;height:125px;" ondblclick="move_elements(<?=$i?>,1)"<?=$disabled?>>
								<?php
									// Récupération de la liste des utilisateurs non cochés									
									if($new)
										// 25/05/2009 BBX : utilisation de la méthode getUsers.
										$array_all_users = UserModel::getUsers();
									else
										// 25/05/2009 BBX : utilisation de la méthode getUsersNotCheckedForAConnection.
										// 07/07/2009 MPR : Correction du bug 9704 - Ajout de l'id du produit
										$array_all_users = UserModel::getUsersNotCheckedForAConnection($row['id_connection'],$_GET['product']);
									// Affichage des users non cochés
                                                                        // 20/02/2012 NSE DE Astellia Portal Lot2 : suppression de user_prenom
									foreach($array_all_users as $userInfo){
										echo '<option value="'.$userInfo['id_user'].'">'.$userInfo['username'].' </option>';
									}
								?>
							</select>
						</td>
						<td align="center" valign="middle" width="30" style="background-color:#<?=$color?>">
							<?php
							// 03/11/2008 BBX : si les alarmes systèmes sont désactivées, on condamne la zone. BZ 7903
							$onclick_left = 'onclick="move_elements('.$i.',1)"';
							$onclick_right = 'onclick="move_elements('.$i.',2)"';
							if(get_sys_global_parameters('alarm_systems_activation','',$_GET['product']) == 0) {
								$onclick_left = '';
								$onclick_right = '';
							}
							?>
							<button type="button" style="width:14px;height:15px;border:0;cursor:pointer" <?=$onclick_left?>>
								<img src="<?=NIVEAU_0?>images/calendar/right1.gif" border="0" />
							</button>
							<br /><br />
							<button type="button" style="width:14px;height:15px;border:0;cursor:pointer" <?=$onclick_right?>>
								<img src="<?=NIVEAU_0?>images/calendar/left1.gif" border="0" />
							</button>
						</td>
						<td align="left" style="background-color:#<?=$color?>">
							<span style="font-weight:bold">Subscribed users</span>
							<select class="zoneTexteStyleXP" id="selected_users_list_<?=$i?>" multiple style="width:225px;height:125px;" ondblclick="move_elements(<?=$i?>,2)"<?=$disabled?>>
								<?php
									$value_selected = Array();
									if(!$new)
									{									
										// 25/05/2009 BBX : utilisation de la méthode getUsersCheckedForAConnection.
										// 07/07/2009 MPR : Correction du bug 9704 - Ajout de l'id du produit
										$array_all_users = UserModel::getUsersCheckedForAConnection($row['id_connection'],$_GET['product']);																		
										// Affichage des users cochés
                                                                                // 20/02/2012 NSE DE Astellia Portal Lot2 : suppression de user_prenom
										foreach($array_all_users as $userInfo){
											echo '<option value="'.$userInfo['id_user'].'">'.$userInfo['username'].' </option>';
											$value_selected[] = $userInfo['id_user'];
										}
									}									
								?>							
								<input type="hidden" id="selected_users<?=$i?>" name="selected_users<?=$i?>" value="<? echo implode('|',$value_selected); ?>" />
							</select>
						</td>
					</tr>
				</table>
			</fieldset>
			</div>
			<script type="text/javascript" charset="iso-8859-1">
				// Appel des fonctions qui vont permettre d'afficher une alerte si la selection des users ou des fichiers est vide.
				checkFiles(<?=$i?>);
                                <? if ($activation_source_availability) { ?>
				checkExpectedFiles(<?=$i?>);
                                <?}?>
				move_elements(<?=$i?>,2);
			</script>
		</td>
	</tr>
	<?php
}

/*******************************************************************
* Cette fonction permet d'afficher une connection
*******************************************************************/
function display_connection($row, $i, $enableSFTP )
{
    // On affiche pas les connexions SFTP si le paramètre global est à 0 (impossible en théorie)
    if( $row['connection_type'] != 'remote_ssh' || $enableSFTP == 1 )
    {
	    display_connection_top( $row, $i, $enableSFTP );
	    display_connection_bottom($row, $i );
    }
}
/*************************************************************/
?>
<script type="text/javascript" charset="iso-8859-1">

	/****
	* 21/10/2008 BBX : ajout d'une fonction qui test si des fichiers sont cochés
	* @param int : i, index de la connextion
	****/
	function checkFiles(i)
	{	// Tableau contenant les id des cases
		var files = new Array();
		<?php
		// Récupération de la liste complète des fichiers
		$query = "SELECT id_flat_file, flat_file_name FROM sys_definition_flat_file_lib
		WHERE on_off = 1
                    AND (lower(flat_file_naming_template) NOT ILIKE '%.zip%' )
		ORDER BY flat_file_name";
		$result_all_files = $database->getAll($query);
		
		foreach($result_all_files as $array) {
			?>
			files.push('selected_file_'+i+'_<?=$array['id_flat_file']?>');
			<?php
		}
		?>
		var all_checked = true;
		var check = false;
		for(var f = 0; f < files.length; f++){
			var current_id = files[f];
			if($(current_id).checked) check = true;
			if(!$(current_id).checked) all_checked = false;
		}
		// Si rien n'est coché, on affiche une alerte
		if(!check){
			$('files_message_'+i).update("<br />No file type selected for System Alerts. No alert will be displayed.");
		}
		// Sinon on vide la zone d'alerte
		else{
			$('files_message_'+i).update();
		}
		
		// Si tout est coché,on coche la case de sélection
		if(all_checked){
			$('selection_files'+i).checked = true;
		}
		// Sinon on la décoche
		else{
			$('selection_files'+i).checked = false;
		}
	}
	
	/****
	* 22/10/2008 : Cette fonction va cocher / décocher tous els types de fichiers
	* @param int : i, index de la connextion
	****/
	function check_uncheck_all(i)
	{
		// Tableau contenant les id des cases
                // maj 30/07/2010 - MPR : Correction du bz17126 On ne prend pas en compte les types de fichier ZIP
		var files = new Array();
		<?php
		// Récupération de la liste complète des fichiers
		$query = "SELECT id_flat_file, flat_file_name FROM sys_definition_flat_file_lib
		WHERE on_off = 1
                     AND (lower(flat_file_naming_template) NOT ILIKE '%.zip%' )
		ORDER BY flat_file_name";
		$result_all_files = $database->getAll($query);
		foreach( $result_all_files as $array) {
			?>
			files.push('selected_file_'+i+'_<?=$array['id_flat_file']?>');
			<?php
		}
		?>
		// Si la case est cochée, on coche tout
		if($('selection_files'+i).checked)
		{
			for(var f = 0; f < files.length; f++){
				var current_id = files[f];
				$(current_id).checked = true;
			}
		}
		// Sinon on décoche tout
		else
		{
			for(var f = 0; f < files.length; f++){
				var current_id = files[f];
				$(current_id).checked = false;
			}
		}	
		// Exécution de checkFiles pour générer les alertes
		checkFiles(i)				
	}

	/**
	 * Fonction qui test si des fichiers "expected" sont cochés.
	 * Si un fichier expected n'est pas coché, on desactive l'input chunk correspondant.
	 * @param int : i, index de la connextion
	*/
	function checkExpectedFiles(i)
	{	// Tableau contenant les id des cases
		var files = new Array();
		var chunk = new Array();
		<?php
		// Récupération de la liste complète des fichiers
                // Correction du bz17126 - On ne prend pas en compte les fichiers ZIP
		$query = "
			SELECT id_flat_file, flat_file_name
			FROM sys_definition_flat_file_lib
			WHERE on_off = 1
                            AND (lower(flat_file_naming_template) NOT ILIKE '%.zip%' )
			ORDER BY flat_file_name";
		$result_all_files = $database->getAll($query);

		foreach($result_all_files as $array) {
			echo "files.push('expected_file_'+i+'_{$array['id_flat_file']}');";
                        echo "chunk.push('chunk_'+i+'_{$array['id_flat_file']}');";
                }
		?>
		var all_checked = true;
		var check = false;
		for(var f = 0; f < files.length; f++){
			var current_id = files[f];
			if($(current_id).checked) {
				check = true;
				$(chunk[f]).disabled = false;
				$(chunk[f]).style.backgroundColor = "#fff";
			}
			if(!$(current_id).checked) {
				all_checked = false;
				$(chunk[f]).disabled = true;
				$(chunk[f]).style.backgroundColor = "#e4e4e4";
			}
		}
		// Si rien n'est coché, on affiche une alerte
		if(!check){
			$('expected_message_'+i).update("<br />No file type selected in 'File expected for Source Availability'.");
		}
		// Sinon on vide la zone d'alerte
		else{
			$('expected_message_'+i).update();
		}

		// Si tout est coché,on coche la case de sélection
		if(all_checked){
			$('selection_files_expected'+i).checked = true;
		}
		// Sinon on la décoche
		else{
			$('selection_files_expected'+i).checked = false;
		}
	}

	/**
	 * Cette fonction va cocher / décocher tous les types de fichiers
	 * de la colonne "File expected"
	 * @param int : i, index de la connextion
	*/
	function check_uncheck_all_expected(i)
	{
		// Tableau contenant les id des cases
		var files = new Array();
		<?php
		// Récupération de la liste complète des fichiers
                // Correction du bz17126 : On ne prend pas en compte les types de fichier ZIP
		$query = "
			SELECT id_flat_file, flat_file_name
			FROM sys_definition_flat_file_lib
			WHERE on_off = 1
                           AND (lower(flat_file_naming_template) NOT ILIKE '%.zip%' )
			ORDER BY flat_file_name";
		$result_all_files = $database->getAll($query);
		foreach( $result_all_files as $array) {
			echo "files.push('expected_file_'+i+'_{$array['id_flat_file']}');";
		}
		?>
		// Si la case est cochée, on coche tout
		if($('selection_files_expected'+i).checked)
		{
			for(var f = 0; f < files.length; f++){
				var current_id = files[f];
				$(current_id).checked = true;
			}
		}
		// Sinon on décoche tout
		else
		{
			for(var f = 0; f < files.length; f++){
				var current_id = files[f];
				$(current_id).checked = false;
			}
		}
		// Exécution de checkFiles pour générer les alertes
		checkExpectedFiles(i);
	}

	/**
	 * Fonction qui va lancer les tests de connexion FTP
         * 12/08/2010 OJT : Correction bz16847 pour DE Firefox
	 */
	function launchFtpChecks()
	{
		// Modification du bouton
		$('check_ftp_button').value = "Checking in progress...";
		$('check_ftp_button').disabled = true;
		$('add_connection_button').disabled = true;
		$('save_button').disabled = true;	
		// Récupération du nombre de lignes
		F = document.formulaire;
		var row_value = F.row_table.value;
		// Récupération du nombre de connections FTP
		var nbftp = 0;
		var testedftp = 0;

                // 18/07/2011 BBX
                // Seules les connexions actives sont testées
                // BZ 13844
		for(var i = 0; i <= row_value; i++)
		{
                    if( ($F( 'connection_type' + i ) == 'remote' || $F( 'connection_type' + i ) == 'remote_ssh') && F.elements['on_off' + i].checked )
			{
				nbftp++;
			}
		}

		// Parcours des lignes
		for(var i = 0; i <= row_value; i++)
		{
                        if( ($F( 'connection_type' + i ) == 'remote' || $F( 'connection_type' + i ) == 'remote_ssh') && F.elements['on_off' + i].checked )
			{
                                // Fonction à éxécuter en fin de test
                                // 12/08/2010 OJT : Repositionnement de la fonction pour bz16847
				function displayResult(result,i,row_value)
				{
					testedftp++;
					var resultat = result.responseText;
					if(resultat == 'OK'){
						$('connection_test_'+i).update('<img src="<?=NIVEAU_0?>images/icones/accept.png" border="0" />');
					}
					else
                                        {
                                            switch(resultat)
                                            {
                                            case '1':
                                                // Gestion FTP/SFTP
                                                switch( $F( 'connection_type' + i ) )
                                                {
                                                        case 'remote' :
                                                                var timing2 = new Date();
                                                                var seconds2 = timing2.getTime();
                                                                var diff = (seconds2 - seconds) / 1000;
                                                                var message = "No response from server";
                                                                if(diff >= <?=$ftp_timeout?>) message += ". Maximum connection time exceeded";
                                                        break;

                                                        case 'remote_ssh' :
                                                                var message = "No response from SSH server";
                                                        break;
                                                }
                                                break;
							case '2':
								var message = "Identification failed";
							break;
							case '3':
								var message = "Can\\'t find directory";
							break;
							// 11:38 07/08/2009 GHX
							// Correction du BZ 10902
							case '4':
								var message = "<?php echo __T('A_E_CONNECTION_NO_SSH_USER_DEFINED', $prodInfos['sdp_label']); ?>";
							break;
							default:
								// 11:38 07/08/2009 GHX
								// Correction du BZ 10902
								// Si c'est une erreur SSH
								if ( resultat.slice(0,2) == '5|' || resultat.slice(0,2) == '6|' )
								{
									var res = resultat.split('|');
									var message = res[1];
								}
								else
								{
									var message = "Unknown error";
									alert(resultat);
								}
							break;
						}
						$('connection_test_'+i).update('<img src="<?=NIVEAU_0?>images/icones/exclamation.png" border="0" onmouseover="popalt(\''+message+'\')" />');
					}
					if(testedftp == nbftp){
						// Modification du bouton
						$('check_ftp_button').value = "<?php echo __T('A_SETUP_CONNECTION_FORM_BTN_FTP_CHECK'); ?>";
						$('check_ftp_button').disabled = false;
						$('add_connection_button').disabled = false;
						$('save_button').disabled = false;
					}
				}

				// Durée avant
				var timing = new Date();
				var seconds = timing.getTime();
				// Patience :)
				$('connection_test_'+i).update('<img src="<?=NIVEAU_0?>images/animation/indicator_snake.gif" border="0" />');
                                // Il s'agit d'une connection FTP ou SFTP, on récupère les infos de connection
                                var type      = $F( 'connection_type' + i );
                                var login     = $F( 'connection_login' + i );
                                var password  = $F( 'connection_password' + i );
                                var server    = $F( 'connection_ip_address' + i );
                                var ftp_mode  = $F( 'connection_mode' + i ); // 01/02/2010 MPR : Test du mode utilisé (actif ou passif)
                                var directory = $F( 'connection_directory' + i );
                                var sftp_port = 0;

                        if( type == 'remote_ssh' ){
                            sftp_port = $F( 'connection_port' + i )
                        }

			// Envoie des infos par ajax
				// 18/01/2010 NSE BZ 13767 : ajout de encodeURIComponent sur le password pour échaper les caractères ?&'
				new Ajax.Request('setup_connection_index.php', {
					method: 'get',
					// maj 16:28 01/02/2010 - MPR : Test du mode utilisé (actif ou passif)
                                parameters: 'product=<?=$_GET['product']?>&ftp_check=1&type='+type+'&login='+encodeURIComponent(login)+'&password='+encodeURIComponent(password)+'&server='+server+'&directory='+encodeURIComponent( directory )+'&mode='+ftp_mode+'&port='+sftp_port,
                                onComplete: displayResult.bindAsEventListener(this,i,row_value)});
			}				
		}
		if(nbftp == 0){
			// Modification du bouton
			$('check_ftp_button').value = "<?php echo __T('A_SETUP_CONNECTION_FORM_BTN_FTP_CHECK'); ?>";
			$('check_ftp_button').disabled = false;
			$('add_connection_button').disabled = false;
			if(row_value != -1) $('save_button').disabled = false;	
		}	
	}

	
	/****
	* modif 21/10/2008 BBX : modification de la fonction pourinsérer une connection via ajax
	* modif 25/05/2009 BBX : ajout de l'i produit dans les requêtes AJAX. BZ 9704
	* fonction qui rajoute une ligne group table
	****/
	function setup_connection_add_parameter() 
	{
		F = document.formulaire;
		var contenu=new Array(12);
		var table = $('setup_connection');
		var row_value = F.row_table.value;
		//document.formulaire.add_connection.disabled = true;

		//Activation du bouton "SAVE" si c'est la première connection que l'on ajoute
		if (F.submit.disabled == true){F.submit.disabled = false;}

		//vérification que TOUS les champs mandatory du formulaire actuel sont pleins
                // 18/07/2011 BBX
                // On ne test plus les saisies lors de l'ajout d'une connexion
                // BZ 13844
		//if (check_field(false)){
			//Incrémentation du compteur de nombre de ligne dans le tableau pour la nouvelle ligne créée
			row_value++;

			// Traitement si aucune connection dans la base de données
			if (table.style.display == 'none') {
				table.style.display = 'block';
				$('noconnection').style.display = 'none';
			}
			
			//Mise à jour du compteur du nombre de ligne dans le tableau
			F.row_table.value = row_value;

			// Ajout d'une connection via ajax
			new Ajax.Request('setup_connection_index.php', {
				method: 'get',
				parameters: 'id_new_connection='+row_value+'&part_to_display=bottom&product=<?=$_GET['product']?>',
				onComplete: function(htmlcode) {
					new Insertion.After('setup_connection_header', htmlcode.responseText);
					new Ajax.Request('setup_connection_index.php', {
						method: 'get',
						parameters: 'id_new_connection='+row_value+'&part_to_display=top&product=<?=$_GET['product']?>',
						onComplete: function(htmlcode) {
							new Insertion.After('setup_connection_header', htmlcode.responseText);	
						}
					});				
				}
			});			
	
			//
		//}
	}

        /**
         * Fonction activant ou désactivant certains champs en fonction du protocole
         *
         * @param cpt Index de la connection en cours de traitement
         *
         * 24/02/2011 DE SFTP. Gestion du champ 'port'
         * 08/03/2011 OJT : bz21147 Gestion de l'affichage du 'Browse remote directory'
         */
	function disable_field(cpt)
        {
		var F = document.formulaire;
		switch (F.elements['connection_type'+cpt].value)
        {
			case 'local' :
				F.elements['connection_mode'+cpt].options[0].text='';
				disable_this_field(F.elements['connection_ip_address'+cpt]);
				disable_this_field(F.elements['connection_login'+cpt]);
				disable_this_field(F.elements['connection_password'+cpt]);
				disable_this_field(F.elements['connection_mode'+cpt]);
                disable_this_field(F.elements['connection_port'+cpt]);
                if( $( 'browseRemoteDir' + cpt ) ){
                    $( 'browseRemoteDir' + cpt ).hide();
                }
				break;
			case 'remote' :
				F.elements['connection_mode'+cpt].options[0].text='Auto';
				enable_this_field(F.elements['connection_ip_address'+cpt]);
				enable_this_field(F.elements['connection_login'+cpt]);
				enable_this_field(F.elements['connection_password'+cpt]);
				enable_this_field(F.elements['connection_mode'+cpt]);
                disable_this_field(F.elements['connection_port'+cpt]);
                if( $( 'browseRemoteDir' + cpt ) ){
                    $( 'browseRemoteDir' + cpt ).show();
                }
                break;
								
			case 'remote_ssh' :
				enable_this_field(F.elements['connection_ip_address'+cpt]);
				enable_this_field(F.elements['connection_login'+cpt]);
                enable_this_field(F.elements['connection_port'+cpt]);
                enable_this_field(F.elements['connection_password'+cpt]);
				disable_this_field(F.elements['connection_mode'+cpt]);
                if( $( 'browseRemoteDir' + cpt ) ){
                    $( 'browseRemoteDir' + cpt ).hide();
                }

                // Initialisation du port à 22 si non renseigné
                if( F.elements['connection_port'+cpt].value.length == 0 ){
                    F.elements['connection_port'+cpt].value = 22;
                }
				break;
		}
	}

        /**
         * Fonction désactivant un champ. Fonction appellée par disable_field
         *
         * @param field Le champ à désactiver
         */
	function disable_this_field( field )
        {
            // Ajout d'un test de robustesse pour la DE SFTP (le champ port n'est pas forcement présent)
            if( field != null )
            {
			field.disabled = true; //On Désactive le champ concerné
			field.style.backgroundColor = "#e4e4e4"; //On applique une couleur de fond grise pour mieux distinguer que le champ est désactivé
			field.style.border = "none";
			field.value = ''; //On réinitialise les champs qui vont être grisés
	    }
	}

    /**
     * Fonction activant un champ. Fonction appellée par disable_field
     *
     * @param field Le champ à activer
     */
	function enable_this_field( field )
    {
        // Ajout d'un test de robustesse pour la DE SFTP (le champ port n'est pas forcement présent)
        if( field != null )
        {
            field.disabled = false; //On active le champs
            field.style.backgroundColor = "#ffffff"; //On active le champsde fond BLANCHE pour mieux distinguer que le champ est actif
			if (field.className == "zoneTexteStyleXPFondGris"){
				field.style.border = "1px solid red";
		} else {
				field.style.border = "1px solid #7F9DB9";
			}
		}
	}

	/**
	 *
	 * @author GHX
	 * @version CB4.0.6.00
	 * @since CB4.0.6.00
	 * @param mixed elem : élément à rechercher dans le tableau
	 * @param Array tab : tableau
	 * @return boolean
	 */
	function in_array( elem, tab)
	{
		for ( var x = 0; x<tab.length; x++ )
		{
			if ( elem == tab[x] )
				return true;
		}
		
		return false;
	} // End function in_array

	function check_field(check_name)
	{
<?php
		// Si corporate, test d'écriture FTP (bz25285, oubli d'un debug)
		if( CorporateModel::isCorporate($_GET['product'])) {
?>
			if(!testWriteAccess()) {
				return false;
			}
<?php
		}
?>	
	
		F = document.formulaire;
		var nb_connection = F.row_table.value;
		//Parcours de TOUS les champs textes du formulaire
		
		// 18/02/2009 GHX
		// Création d'un tableau qui contiendra les noms des connexions
		var listConnectionName = new Array(nb_connection);

        // 15/02/2011 OJT : bz15445, RegExp pour la restriction sur le nom de la connexion
        var pathStringExp = new RegExp(/^[\w][\w\.\s\-]+$/);

		// 18/02/2009 GHX
		// Correction du bug BZ 8684 [SUP][V4.0][7843][SFR IdF]: impossible de sauvegarder les connections du Setup Connection
		// Modification de la boucle pour vérifier les champs, on utilise prototype et plus 2 boucles for (si on avait 15 connexions on arrivait à un total d'environ 4500 boucles :'(  )
		var resultIsNok = $$('form[name="formulaire"] input[type="text"]').find(
			function (el) {
				champ = el;
				j = el.name.match(/[0-9]*$/);					
				if ( champ.type == 'text' || champ.type == 'password'){
					if (champ.disabled == false){
						switch (champ.name){
							case 'connection_name'+j :
								// 18/02/2009 GHX
								// Teste si le nom de la connexion existe déjà
								if ( in_array(champ.value, listConnectionName) )
								{
									champ.focus();
									alert('<?=__T('A_JS_SETUP_CONNECTION_NAME_ALREAD_USED');?>"'+champ.value+'"');
									return true;
								}
								listConnectionName.push(champ.value);
								
								var re = /^[\w][\w\.\s\-]+$/;
								var name_to_compare = champ.name; //On ne passe que le champ pour pouvoir manipuler la valeur et le focus
								if ( champ.value == '' && !re.test(champ.value)){
									champ.focus();
									alert('<?=__T('A_JS_SETUP_CONNECTION_EMPTY_FIELD');?>');
									return true;
								}

                                // Test des caractères interdits
                                if(!pathStringExp.test( champ.value ) ){
                                    alert( '<?=__T('A_JS_SETUP_CONNECTION_NAME_REGEXP' );?>' );
                                    return true;
                                }
								break;
							case 'connection_ip_address'+j :
								if ( champ.value == '' ){
									champ.focus();
									alert('<?=__T('A_JS_SETUP_CONNECTION_EMPTY_FIELD');?>');
									return true;
								}
								break;

							case 'connection_port'+j :
                                                                if( !( ( parseInt( champ.value ) > 0 ) && ( parseInt( champ.value ) <= 65535 ) ) ){
									champ.focus();
									alert('<?=__T('A_JS_SETUP_CONNECTION_PORT_RANGE');?>');
									return true;
								}
								break;

							case 'connection_login'+j :
								var re = /^[\w][\w\.\s\-]+$/;
								if ( champ.value == '' && !re.test(champ.value)){
									champ.focus();
									alert('<?=__T('A_JS_SETUP_CONNECTION_EMPTY_FIELD');?>');
									return true;
								}
								break;
							case 'connection_directory'+j :
								//Un slash au début et à la fin
								//var re = /^\/$/;
								// Un slash au début, une ou plusieur fois : une chaine de caractère et un slash
								var re = /^[\w][\w\.\s\-]+$/;
								if ( champ.value == '' && !re.test(champ.value)){
									champ.focus();
									alert('<?=__T('A_JS_SETUP_CONNECTION_EMPTY_FIELD');?>');
									return true;
								}
								break;
						}
					}
				}
			}
		);
		
		return typeof(resultIsNok) == 'object' ? false : true;
	}

	/**
	 * Test si les champs chunk ont des valeurs cohérentes
	 */
	function check_chunk_field()
    {
		var files = new Array(); // Tableau contenant les id des types de fichiers
		var cnx = new Array(); // Tableau contenant les id des connexions

        // maj 22/07/2010 - MPR : On récupère le nombre de connexion
        // 13/09/2010 NSE bz 17798 : ajout getElementById
        F = document.getElementById('formulaire');
		var nb_connections = F.row_table.value;


                <?php

		$activation_source_availability = (get_sys_global_parameters('activation_source_availability','',$_GET['product']) == 1);

		if( $activation_source_availability ){
			// Récupération de la liste complète des fichiers
			$query = "
					SELECT id_flat_file, flat_file_name
					FROM sys_definition_flat_file_lib
					WHERE on_off = 1
                                        AND (lower(flat_file_naming_template) NOT ILIKE '%.zip%' )
					ORDER BY flat_file_name";
			$result_all_files = $database->getAll($query);
			foreach($result_all_files as $array) {
					echo "files.push('{$array['id_flat_file']}');\n";
			}

			// Récupération de la liste complète des connexions
			$query = "
					SELECT id_connection, connection_name
					FROM sys_definition_connection
					ORDER BY id_connection";
			$result_all_cnx = $database->getAll($query);
			foreach($result_all_cnx as $array) {
					// maj On effectue - 1 pour retrouver la correspondance avec les connexions enregistrées en base de données
					echo "
					cnx.push('".($array['id_connection']-1)."');";
			}
			?>

                        // maj 22/07/2010 - MPR : On boucle sur le nombre total de connexion et non sur une seule nouvelle connexion
                        //                        Il est possible de créer n connexions sans les avoir enregistré au préalable
                        //
                        for(i=<?php echo $database->getNumRows($query); ?>;i<=nb_connections;i++)
                        {
                            // vérifie si une nouvelle connexion a été demandée
                            if($('connection_name'+i)){
                                    if(document.getElementsByName('connection_name'+i)[0].value){
                                            cnx.push(i);
                                    }
                            }
                        }

			var field = '';
            var expected_file = '';
			var value = 0;
			var gran = null;
			var freq = null;
			var err_msg = 'Maximum value of Data Chunks has exceeded. Maximum values expected are:\n';
			err_msg += '\t- Data Granularity = Day / Data collection frequency =  day / Data chunks   = 1\n';
			err_msg += '\t- Data Granularity = Hour / Data collection frequency =  day / Data chunks   = 24\n';
			err_msg += '\t- Data collection frequency = hour / Data chunks   = 24\n';
			err_msg += '\t- Data collection frequency = 15mn / Data chunks = 96';
			var connexion_error_list = new Array(); // Liste des connexions contenant un problème

			for(var c=0; c < cnx.length; c++)
            {
				for(var f=0; f < files.length; f++)
                {
                    // 21/07/2010 MPR - Correction du bz16244 : On remplace cnx[c] par c puisque champs chunks se nomme "chunk_'[0-X]'_'filename'"
                    field = $('chunk_'+c+'_'+files[f]);

                    // 17/09/2010 NSE 17798 : on ne vérifie la valeur du champ que s'il est expected
                    expected_file = $('expected_file_'+c+'_'+files[f]);
                    if(expected_file.checked)
                    {
                        value = parseFloat(field.value);

                        // on test si ce n'est pas un numerique
                        if (value.toString() != field.value)
                        {
                                field.focus();
                                alert('Data Chunks value must be a positive integer.');
                                return false;
                        }
                        if (value < 0)
                        {
                                alert('Data Chunks value must be a positive integer.');
                                field.focus();
                                return false;
                        }

                        // On test la concordance frequence/granularité/chunk
                        // 21/07/2010 MPR - Correction du bz16244 : On remplace cnx[c] par c puisque champs chunks se nomme "chunk_'[0-X]'_'filename'"
                        gran = $('granularity_'+ c +'_'+files[f]).value;
                        freq = $('frenquency_'+ c +'_'+files[f]).value;

                        if ((gran=='day' && freq == '24' && value > 1) ||
                            (gran=='day' && freq == '1' && value > 24) ||
                            (gran=='hour' && freq == '24' && value > 24) ||
                            (gran=='hour' && freq == '1' && value > 24) ||
                            (gran=='hour' && freq == '0.25' && value > 96)
                            )
                        {
                            // rechercher dans le tableau que la connexion n'existe pas déjà
                            var found = false;
                            for(var i=0 ; i<connexion_error_list.length ; i++)
                            {
                                // 21/07/2010 MPR - Correction du bz16244 : On remplace cnx[c] par c puisque champs chunks se nomme "chunk_'[0-X]'_'filename'"
                                if(connexion_error_list[i] == $('connection_name'+c).value)
                                    found = true;
                            }

                            if(found == false)
                                connexion_error_list.push($('connection_name'+c).value);
                        }
                    }
				}
			}

			if( connexion_error_list.length > 0 ){
				err_msg += '\n\nList of connections with an error:';
				for(var i=0 ; i < connexion_error_list.length ; i++)
					err_msg += '\n\t- '+connexion_error_list[i];
				alert(err_msg);
				return false;
			}

		<?
		}
		?>

		return true;
	}

	/* Fonction retirée par besoin de saisir un DNS à la place d'une IP
	function isValidIPAddress(ipaddr) {
		//var re = /^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/;
		var re = /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/;
		if (re.test(ipaddr)) {
			var parts = ipaddr.split(".");
			if (parseInt(parseFloat(parts[0])) == 0) {
				return false;
			}
			for (var i=0; i<parts.length; i++) {
				if (parseInt(parseFloat(parts[i])) > 255) {
					return false;
				}
			}
			return true;
		} else {
			return false;
		}
		return true;
	}*/


	function setup_connection_delete(id_connection){
		reponse = confirm('<?=__T('A_JS_SETUP_CONNECTION_CONFIRM_DELETION');?>');
		if (reponse){
			window.location="setup_connection_update.php?action=delete&product=<?=$_GET['product']?>&id="+id_connection;
		}
	}
	
	/****
	* 20/10/2008 BBX : ajout d'une fonction qui affiche/cache les paramètres avancés
	* @param string : id_image
	* @param string : id_zone
	****/
	function show_hide_advanced_parameters(id_image,id_zone)
	{
        if($(id_zone))
        {
			if($(id_zone).style.display == 'none')
			{
				$(id_zone).style.display = 'block';
				$(id_image).src = '<?=NIVEAU_0?>images/icones/moins_alarme.gif';
			}
			else
			{
				$(id_zone).style.display = 'none';
				$(id_image).src = '<?=NIVEAU_0?>images/icones/plus_alarme.gif';
			}
		}
	}
	
	/****
	* 20/10/2008 BBX : permet de transvaser des éléments d'une liste à une autre
	* @param int : i
	* @param int : sens
	****/
	function move_elements(i,sens)
	{			
		// Fonction qui bouge les éléments
		function move(idz1,idz2)
		{
			var array_to_remove = new Array();
			for(var i = 0; i < $(idz1).options.length; i++)
			{
				if($(idz1).options[i].selected)
				{
					$(idz2)[$(idz2).options.length] = new Option($(idz1).options[i].text, $(idz1).options[i].value);						
					$(idz1).options[i] = null;
					i--;
				}
			}	
		}
		// Id des zones
		var id_zone_maitre = 'all_users_list_'+i;
		var id_zone_esclave = 'selected_users_list_'+i;
		var id_input = 'selected_users'+i;
		var id_message_tag = 'users_message_'+i;
		// Selon le sens
		if(sens == 1) {
			move(id_zone_maitre,id_zone_esclave);
		}
		else {
			move(id_zone_esclave,id_zone_maitre);
		}	
		// Sauvegarde des éléments de la zone esclave
		$(id_input).value = '';
		for(var i = 0; i < $(id_zone_esclave).options.length; i++)
		{
			var sep = (i == 0) ? '' : '|';
			$(id_input).value += sep+$(id_zone_esclave).options[i].value;
		}
		// Message si zone escalve vide
		if($(id_zone_esclave).options.length == 0) {
			$(id_message_tag).update("<br />No user selected. No email will be sent.");
		}
		else{
			$(id_message_tag).update();
		}
	}
	
	/****
	* 28/10/2008 BBX : permet de griser les boutons lors de la sauvegarde pour éviter de lancer la sauvegarde plusieurs fois
	****/
	function griseBoutons()
	{
		$('save_button').disabled = true;
		$('check_ftp_button').disabled = true;	
		$('add_connection_button').disabled = true;	
		$('reinitialize').disabled = true;	
	}
	
	/****
	* 25/05/2009 BBX : ouvre le répertoire ftp dans le navigateur
	****/
	function openRemoteDirectory(i)
    {
		var login = $('connection_login'+i).value;
		var password = $('connection_password'+i).value;
		var host = $('connection_ip_address'+i).value;
		var directory = $('connection_directory'+i).value;

		if(host == '' || directory == '')
        {
			alert('<?=__T('A_JS_SETUP_CONNECTION_EMPTY_FIELD');?>');
			return false;
		}
		else
        {
            // maj 15/09/2010 - MPR : Correction du bz 17802
            /*/ Problème de compatibilité Firefox/IE :
             Lorsque FF exécute la commande suivante ftp://user:password@server:directory,
                directory = répertoire par défaut du user FTP + directory
             IE exécute la même commande différement
                directory = directory (ne prend pas en compte le répertoire par défaut du user FTP)
            */

            // Identification du navigateur
            // Si Firefox ou IE6 alors on redirige l'URL
            if (!Prototype.Browser.IE || navigator.appVersion.indexOf("MSIE 6.0") != -1 )
            {
                // Pour les autres navigateurs que IE (Firefox par exemple)
                // On compte le nombre de /
                // On remonte de 5 répertoires pour s'assurer qu'on est bien dans /
                // Impossible d'identifier la conf dans /etc/passwd, on n'a pas systématiquement les id connexions ssh
                redirection = "/../../../../..";
                directory = redirection + directory;

            }

            // BZ 14154
            // 03/03/2010 BBX : encodage URL du login et du mot de passe afin d'échapper les caractères spéciaux
            var Uri = 'ftp://'+encodeURIComponent(login)+':'+encodeURIComponent(password)+'@'+host+':'+directory;
			window.open(Uri);
			// Fin BZ 14154
		}
	}

	/****
	* 16/09/2009 BBX : test d'écriture FTP
	****/	
	function testWriteAccess() {
		<?php
			echo "var useProxy = ".(($prodInfos['sdp_master'] != '1')?'true':'false').";"
		?>
		// Modification du bouton
		$('check_ftp_button').disabled = true;
		$('add_connection_button').disabled = true;
		$('save_button').disabled = true;	
		// Récupération du nombre de lignes
		F = document.formulaire;
		var row_value = F.row_table.value;	
		// Parcours des connexions
		for(var i = 0; i <= row_value; i++) {
            // 18/07/2011 BBX
            // Seules les connexions actives sont testées
            // BZ 13844
            // TEST FTP et SFTP
            if(F.elements['on_off' + i].checked)
            {
                if( $F( 'connection_type' + i ) == 'remote' || $F( 'connection_type' + i ) == 'remote_ssh' )
                {
                    // Il s'agit d'une connection FTP ou SFTP, on récupère les infos de connection
                    var type      = $F( 'connection_type' + i );
                    var login     = $F( 'connection_login' + i );
                    var password  = $F( 'connection_password' + i );
                    var ftp_mode  = $F( 'connection_mode' + i ); // 01/02/2010 PR : Test du mode utilisé (actif ou passif)
                    var server    = $F( 'connection_ip_address' + i );
                    var directory = $F( 'connection_directory' + i );
                    var affiliate = $F( 'connection_name' + i );
                    var sftp_port = 0;

                    if( type == 'remote_ssh' ){
                        sftp_port = $F( 'connection_port' + i )
                    }

                    // Test répertoire export_files_corporate
                    // 28/10/2009 BBX : amélioration du test du répertoire. On n'accepte que ce type de chemin : /home/application_ta/upload/export_files_corporate
                    // 14:11 07/01/2010 SCT : BZ 13663 => connexion vers serveur FTP configuré vers un répertoire spécifique
                    /* code supprimé */

                    // Test Ajax
					// Gestion de l'url à appeler
					// 09/12/2011 ACS Mantis 837 DE HTTPS support
					// 22/12/2011 ACS BZ 25285 send ajax request to "ProxyRequest" instead of a distant server
                    var test = true;
                    var ajaxUrl = 'setup_connection_index.php?ftp_check_write=1&login='+encodeURIComponent(login)+'&password='+encodeURIComponent(password)+'&server='+server+'&directory='+directory+'&mode='+ftp_mode+'&port='+sftp_port+'&type='+type;
                    if (useProxy) {
                    	ajaxUrl = '<?= NIVEAU_0 ?>/php/proxyRequest.php?productId=<?= $prod ?>&url=myadmin_setup/intranet/php/affichage/setup_connection_index.php/' + encodeURIComponent(ajaxUrl);
                    }
                    
                    // 18/01/2010 NSE BZ 13767 : ajout de encodeURIComponent sur le password pour échaper les caractères ?&'
                    new Ajax.Request(ajaxUrl,{
                        method:'get',
                        asynchronous:false,
                        // maj 16:28 01/02/2010 - MPR : Test du mode utilisé (actif ou passif)
                        onComplete:function(result) {
                            // Test du résultat
                            if(result.responseText != 'OK') {
                                // Modification du bouton
                                $('check_ftp_button').disabled = false;
                                $('add_connection_button').disabled = false;
                                $('save_button').disabled = false;
                                // 21/01/2009 BBX : on propose quand même la sauvegarde, même si une connexion plante. BZ 13844
                                // 06/01/2012 OJT : bz25405, correction d'une faute d'orthographe (wirte-protected)
                                test = confirm('Cannot write into the directory defined for connection "'+affiliate+'".\nThe directory does not exist or is write-protected.\nSave anyway ?');
                            }
                        }
                    });
                    if(!test) {
                            return false;
                    }
                }
                // TEST LOCAL
                else
                {
                    var directory = $('connection_directory'+i).value;
                    var affiliate = $('connection_name'+i).value;
                    // Test Ajax
                    var test = true;
					// 22/12/2011 ACS BZ 25285 send ajax request to "ProxyRequest" instead of a distant server
                    var ajaxUrl = 'setup_connection_index.php?local_check_write=1&directory=' + directory;
                    if (useProxy) {
                    	ajaxUrl = '<?= NIVEAU_0 ?>php/proxyRequest.php?productId=<?= $prod ?>&url=myadmin_setup/intranet/php/affichage/setup_connection_index.php/' + encodeURIComponent(ajaxUrl);
                    }
                    new Ajax.Request(ajaxUrl,{
                        method:'get',
                        asynchronous:false,
                        onComplete:function(result) {
                            // Test du résultat
                            if(result.responseText != 'OK') {
                                // Modification du bouton
                                $('check_ftp_button').disabled = false;
                                $('add_connection_button').disabled = false;
                                $('save_button').disabled = false;
                                // 21/01/2009 BBX : on propose quand même la sauvegarde, même si une connexion plante. BZ 13844
                                // 06/01/2012 OJT : bz25405, correction d'une faute d'orthographe (wirte-protected)
                                test = confirm('Cannot write into the directory defined for connection "'+affiliate+'".\nThe directory does not exists or is write-protected.\nSave anyway ?');
                            }
                        }
                    });
                    if(!test) {
                            return false;
                    }
                }
            }
		}
		// Modification du bouton
		$('check_ftp_button').disabled = false;
		$('add_connection_button').disabled = false;
		$('save_button').disabled = false;	
		return true;
	}
	
	function reinitializeFTPMode()
	{
		var F = document.formulaire;
		
		var row_value = F.row_table.value;
		// Récupération du nombre de connections FTP
		var nbftp = 0;
		for(var i = 0; i <= row_value; i++)
		{
			if($('connection_type'+i).value == 'remote')
			{
				F.elements['connection_mode'+i].selectedIndex=0; 

			}
		}
	}
</script>
<style>
table {
	color:#666699;
	font-family:Arial;
	font-size:8pt;
	border-collapse:collapse;
	border:1px solid #898989;
}
table label {
	color:#585858;
	font-family:Arial;
	font-size:8pt;
}
table input {
	border: #7F9DB9 1px solid;
	font-size:8pt;
	color: #585858;
	background-color: #ffffff;
}
table select {
	color:#585858;
	font-family:Arial;
	font-size:8pt;
	border: #7F9DB9 1px solid;
	background-color: #ffffff;
}
table span {
	color:#585858; 
}
th {
	background-color:#D4D2D2;
	font-weight:bold;
	font-size:9pt;
	padding:5px;
	border-bottom:1px solid #898989;
	/*border-top:4px solid #aabcfe;*/
	color:#585858;
}
td {
	background-color:#585858;
	padding:5px;
	border-bottom:1px solid #898989;
}
</style>

	<?
		// 25/05/2009 BBX : récupération des infos Produits
		$product = new ProductModel($_GET['product']);
		$productInfos = $product->getValues();
	
		// Récupération des connexions éxistantes dans la table "sys_definition_connection" de la BdD
		$query = "SELECT * FROM sys_definition_connection ORDER BY connection_name DESC;";
		$arrayConnections = $database->getAll($query);
		$nombre_connection = count($arrayConnections);
		$nombre_connection--;

		//Test pour désactivé le bouton save  et afficher le message d'avertissement lorsqu'il n'y a pas de données dans la table
		if($nombre_connection == -1){
			$disable_bouton_save = "disabled='true'";
			$display_msg = "display:block;";
			$hide_table = "display:none;";
		} else {
			$disable_bouton_save = "";
			$display_msg = "display:none;";
			$hide_table = "display:block;";
		}

    // Check if FTP connections exists (DE SFTP)
    $bestFTPModeType = 'hidden';
    $i = 0;
    while( $i <= $nombre_connection && $bestFTPModeType == 'hidden' )
    {
        if( $arrayConnections[$i]['connection_type'] == 'remote' ){
            $bestFTPModeType = 'button';
        }
        $i++;
    }

	?>

<div id="container" style="width:100%;text-align:center">
<center>
	
	<!-- titre de la page -->
	<div>
		<img src="<?=NIVEAU_0?>images/titres/connection_setup_titre.gif" border="0" />
	</div>
	<br />
	
	<!-- Table principale qui contient les boutons du formulaire  -->
        <?php // 13/09/2010 NSE bz 17798 : ajout de id ?>
	<form id="formulaire" name="formulaire" action="setup_connection_update.php?action=save" method="post" onsubmit="griseBoutons()">
		
		<div class="tabPrincipal" style="width:900px;text-align:center;padding:10px;">
		<center>
			
			<input type="hidden" name="product" value="<?=$_GET['product']?>" />
			
<?php
		// 16/09/2009 BBX : si on est en mode Corporate, on vérifie si une erreur affiliate est renvoyée
		if(CorporateModel::isCorporate($_GET['product']))
		{
			if(isset($_GET['affiliate']))
			{
				$A_SETUP_CORPORATE_ERROR_DATA_EXPORT = 'Error while sending the Data Export configuration to affiliate "'.$_GET['affiliate'].'".<br />Please check that the directory corresponds to /home/application_ta/upload/export_files_corporate and <u>is not write-protected</u>.';		
				echo '<div class="errorMsg">'.$A_SETUP_CORPORATE_ERROR_DATA_EXPORT.'</div>';				
			}
		}
?>
			
<?php
		// On permet le changement de produits s'il en existe d'autres
		if(count(getProductInformations()) > 1)
		{
?>
			<div class="texteGris" style="height:35px;position:relative;text-align:center;">				
				<fieldset>				
					<div style="height:20px;padding-top:5px;">
						<?=__T('G_CURRENT_PRODUCT')?> : <?=$productInfos['sdp_label']?>
					</div>
					<div style="position:absolute;top:5px;right:5px;">						
						<a href="<?php echo basename(__FILE__); ?>">
							<img src="<?=NIVEAU_0?>images/icones/change.gif" border="0" onmouseover="popalt('<?=__T('A_U_CHANGE_PRODUCT')?>')" />
						</a>					
					</div>					
				</fieldset>				
			</div>
			<br />
<?php
		}
?>
			<div style="position:relative;">
				<?php
                    $addConnectionButtonType = 'button';
                    // 29/06/2010 OJT : bz16237 Pas de [NEW] pour un produit mixed KPI
                    if( ProductModel::getIdMixedKpi() == $productInfos['sdp_id'] ){
                        $addConnectionButtonType = 'hidden';
                    }
                ?>
                <input
                    type="<?php echo $addConnectionButtonType; ?>"
                    id="add_connection_button"
                    name="add_connection"
                    value="<?php echo __T('A_SETUP_CONNECTION_FORM_BTN_NEW'); ?>"
                    class="bouton"
                    onClick="setup_connection_add_parameter();"
                />
                <?php // 31/03/2011 NSE Merge 5.0.5 -> 5.1.1 : OnClick = ajout de && check_chunk_field() ?>
                <input
                    type="submit"
                    id="save_button"
                    name="submit"
                    value="<?php echo __T('A_SETUP_CONNECTION_FORM_BTN_SAVE'); ?>"
                    class="bouton"
                    onClick="return (check_field(true) && check_chunk_field());" <?=$disable_bouton_save;?>
                />
				<input
                    type="button"
                    id="check_ftp_button"
                    value="<?php echo __T('A_SETUP_CONNECTION_FORM_BTN_FTP_CHECK'); ?>"
                    class="bouton"
                    onClick="launchFtpChecks()"
                />
                <?php // 31/03/2011 NSE Merge 5.0.5 -> 5.1.1 : type = echo $bestFTPModeType;?>
				<input
                    type="<?php echo $bestFTPModeType; ?>"
                    id="reinitialize"
                    value="Detect best FTP mode(auto)"
                    class="bouton"
                    onclick="reinitializeFTPMode()"
                />
			</div>
			<br />
			<div>					
				<?php
				// 03/11/2008 BBX : ajout d'un contrôle sur l'activation ou non des alarmes systèmes. BZ 7903
				$readonly = (get_sys_global_parameters('alarm_systems_activation','',$_GET['product']) == 0) ? ' readonly' : '';
				?>
						<table id="setup_connection" style="width:850px;<?=$hide_table?>" cellspacing="0" cellpadding="5">
							<tr id="setup_connection_header">
								<th width="100" colspan='2'><? echo __T('A_SETUP_CONNECTION_FORM_LABEL_TYPE'); ?><font color="red">*</font></th>
								<th><? echo __T('A_SETUP_CONNECTION_FORM_LABEL_NAME'); ?><font color="red">*</font></th>
								<th><? echo __T('A_SETUP_CONNECTION_FORM_LABEL_ADDRESS_IP'); ?><font color="red">*</font></th>

                            <!-- DE SFTP. On affiche la colonne uniquement si au moins une connexion existe -->
                            <?php if( $enableSFTP == 1 ){ ?>
                            <th><? echo __T('A_SETUP_CONNECTION_FORM_LABEL_PORT'); ?><font color="red"></font></th>
                            <?php } ?>
								<th><? echo __T('A_SETUP_CONNECTION_FORM_LABEL_LOGIN'); ?><font color="red">*</font></th>
								<?php
								// 14/08/2008 BBX : Le champ password n'est plus obligatoire. BZ7223
								?>
								<th><? echo __T('A_SETUP_CONNECTION_FORM_LABEL_PASSWORD'); ?><font color="red"></font></th>
								<th><? echo __T('A_SETUP_CONNECTION_FORM_LABEL_SONDE_CODE'); ?></th>
								<th><? echo __T('A_SETUP_CONNECTION_FORM_LABEL_REGION_CODE'); ?></th>
								<th><? echo __T('A_SETUP_CONNECTION_FORM_LABEL_ON_OFF'); ?></th>
								<th>&nbsp;</th>
							</tr>
							<?
								//echo "<input type='hidden' name='row_form' value='$nombre_connection' />";
								echo "<input type='hidden' name='row_table' value='$nombre_connection' />";
																		
								// modif 20/10/2008 BBX : ajout des paramètres avancés pour gérer les types de fichiers et les utilisateurs
								//Affichage des informations
								for ($i=$nombre_connection;$i>=0;$i--) {
									$row = $arrayConnections[$i];
                                    display_connection($row, $i, $enableSFTP );
									//Réinitialisation des variables pour la désactivation des champs pour les connections suivantes
									$login_off = "";
									$pass_off = "";
									$ip_off = "";
								}
							?>
						</table>
				</div>
				<div id="noconnection" style="<?=$display_msg;?>" class="texteGrisBold">
					<br />
					<strong>
						<?=__T('A_E_SETUP_CONNECTION_NO_CONNECTION_CREATED');?>
					</strong>
				</div>
			</center>
			</div>
	</div>
</center>
</div>

</body>
</html>