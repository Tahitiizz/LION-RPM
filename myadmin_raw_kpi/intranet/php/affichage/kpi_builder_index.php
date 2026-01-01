<?
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
*	=> Gestion du produit
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
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";

// Librairies et classes requises
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/select_family.class.php");

// Connexion à la base de données locale
$database = DataBase::getConnection();

// Intranet top + menu contextuel
include_once(REP_PHYSIQUE_NIVEAU_0 . "/intranet_top.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "/php/menu_contextuel.php");

// 27/07/2010 OJT : Correction bz16899, mise en commentaire du contrôle d'accès
/*// Contrôle d'accès
$userModel = new UserModel($_SESSION['id_user']);
$query = "SELECT id_menu FROM menu_deroulant_intranet WHERE libelle_menu ILIKE 'Kpi Builder'";
$result = $database->getRow($query);
$idMenu = ($result['id_menu'] == '') ? 0 : $result['id_menu'];
if(!$userModel->userAuthorized($idMenu)) {
	echo '<div class="errorMsg">'.__T('A_USER_NOT_ALLOWED_TO_ACCESS').'</div>';
	exit;
}*/

// Sélection de la famille
if(!isset($_GET["family"])){
	$select_family = new select_family($_SERVER['PHP_SELF'], $_SERVER['argv'][0], 'Kpi builder');
	exit;
}

// récupération du produit et de la famille
$family = $_GET["family"];
$product = $_GET["product"];
?>
        <table border="0" cellpadding="3" cellspacing="0" width="100%">
            <tbody>
                <tr valign="top">
                    <td width="18%">
                        <table  border="0" cellpadding="2" cellspacing="0" width="100%">
                            <tbody>
                                <tr>
                                    <td>
                                        <iframe name="raw_data" src="kpi_builder_raw_counters_list.php?family=<?=$family?>&product=<?=$product?>" leftmargin="5px" topmargin="5px" marginwidth="0" marginheight="0" frameborder="0" height="430" scrolling="auto" width="100%"></iframe>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td width="60%">
                        <!-- bz15569 OJT : Passage du height à 700 et mise à auto du scrolling -->
                        <iframe name="kpi_builder" src="kpi_builder_interface.php?family=<?=$family?>&product=<?=$product?>" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" frameborder="0" height="700" scrolling="auto" width="100%"></iframe>
                    </td>
                    <td width="18%">
                        <table border="0" cellpadding="2" cellspacing="0" width="100%">
                            <tbody>
                                <tr>
                                    <td>
                                        <!-- 02/09/2010 OJT : Correction bz17328 pour DE Firefox, ajout d'un id à l'iFrame -->
                                        <iframe id="kpi_list" name="kpi_list" src="kpi_builder_kpi_list.php?family=<?=$family?>&product=<?=$product?>" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" frameborder="0" height="430" scrolling="auto" width="100%"></iframe>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
    </body>
</html>
