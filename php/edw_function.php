<?php
/**
 * 
 *  CB 5.3.1
 * 
 * 22/05/2013 : Link to Nova Explorer
 */
/**
 * 
 *  CB 5.2
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 */
?><?php
/*
*	Fichier contenant un ensemble de fonctions utilisées dans l'application entière
*/
?>
<?php
/**
 * @cb51000
 *  - OJT : 21/06/2010 Modification de toutes les connexions à la base de données (utilisation de getConnection)
 * 	25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
 */
?>
<?php
/**
 *      @cb503010@
 *      maj 13/09/2010 - MPR : Correction du bz16214 - Ajout d'un paramètre d'entrée optionel permettant d'indiquer ou non si on récupère l'IP publique ou privrée
 *      @cb50414@
 *       + 03/01/2011 16:35 SCT : ajout de la méthode "getAllSysGlobalParameters"
 *
 * 07/07/2011 NSE bz 22888 : dans getPathNetworkAggregation(), la variable $product peut être une chaîne ou un tableau de produits
 * 22/09/2011 MMT bz 23824 getTaList : il ne faut pas faire d'exit si pas de TA
 *
 */
?>
<?php
/**
 * 	@cb41000@
 * 	- MaJ SLC	 29/10/2008 - ajout de la fonction get_na_levels_in_common()
 * 	- MAJ CCT1 24/10/08 : on supprime toute référence à la table sys_selecteur_properties qui n'existe plus.
 * 	- MaJ SLC	 24/07/2008 - déménagement de la fonction getUserDashboarList() depuis intranet_top.php vers edw_function.php
 * 	- MaJ	SLC	 21/10/2008 - ajout de la jointure sur sys_definition_dashboard sur la fonction getUserDashboarList()
 * 	- Maj		MPR	 01/11/2008 - Création de la fonction getProductInformations

  - maj 14/11/2008, benoit : création des fonctions 'GetGTInfoFromFamily()' et 'GetAxe3()' pour retourner les informations group_table et la présence ou non de l'axe3 pour une famille donnée. Ces fonctions remplacent 'get_gt_info_from_family()' de "php/edw_function_family.php" et 'get_axe3()' de ce fichier en utilisant la nouvelle classe de connexion à la base de données

  - maj 24/11/2008 BBX : ajout de la fonction 'getMasterProduct' qui récupère les infos du produit maître

  - maj 10/12/2008, benoit : ajout de la fonction 'getTAInterval()' permettant de renvoyer un ensemble de valeurs de ta
  - maj 11/12/2008, MPR : Suppression des fonctions de gestion de la clé. Ces fonctions sont maintenant regroupées dans la class /models/Key.class.php
  -> displayKeyEndDate, getNaKey, getNbNaKey, Decrypt, Encrypt

  - maj 15/12/2008, benoit : ajout du paramètre '$order' à la fonction 'getTAInterval()'
  - maj 15/12/2008, benoit : ajout de la fonction 'getNetworkLabel()' permettant de renvoyer le label d'une na
  - ajout 15:58 12/12/2008 SCT : ajout de la fonction getFamilySeparator() qui permet de récupérer le type de séparateur pour les couples de cellules
  - maj 14/01/2009 - MPR : Ajout du paramètre id_prod
  - maj 16/01/2009 - MPR : Ajout du produit pour récupérer le séparateur d'axe3

  - maj 27/01/2009, benoit : création de la fonction 'getTaValueToDisplayReverse()'

  - maj 29/01/2009, gwenael : modification de la requete SQL pour mettre la valeur id_user entre cote dnas la fonction getUserInfo()  +      getClientType() + getUserDashboarList() [REFONTE CONTEXTE]
  - maj 02/02/2009 GHX :  Création d'une fonction qui génére un unique ID generateUniqId() [REFONTE CONTEXTE]

  - maj 05/02/2009, benoit : modification de la fonction 'getTaValueToDisplay()' avec l'ajout du parametre '$separator' afin de pouvoir      spécifier le séparateur à utiliser pour le formatage de la date

  - maj 05/02/2009, benoit : création de la fonction 'getTaSourceDefault()'

  - maj 09/02/2009, benoit : création de la fonction 'getLastDayFromMonth()'

  - maj 28/04/2009 GHX : mode débug texte aligné à gauche plus facile à lire si la page est centré

  29/04/2009 GHX
  - Correction de la boucle du for de la fonction getTAInterval() qui retournait une période en trop
  05/05/2009 GHX
  - Correction de la boucle du for de la fonction getTAInterval()  car il nous manque la période de départ

  11/05/2009 GHX
  - Modification de la fonction __T pour prendre en compte les $XX supérieur à 9
  13/07/2009  GHX
  - Modification de la fonction get_axe3() pour ajouter un deuxieme paramètre idProduct
  -> Correction du BZ 10601 [REC][Investigation Dashboard]: 3° axe ne fonctionne pas
  16/07/2009 gHX
  - Modification de la fonction get_adr_server() pour supprimer le retour à la ligne de la valeur retournée
  Correction du BZ 10643 [REC][T&A Cb 5.0][Task Scheduler / Reporting ] : dans le mail envoyé, le lien n'est pas cliquable

  30/07/2009 BBX
  - modification de la fonction get_time_to_calculate (modification des conditions week et month)
  - ajout de la fonction getLastIntegratedDay qui permet de récupérer le dernier jour intégré
  27/10/2009 MPR
  - Correction du bug - Ajout du produit dans l'index du tableau ( problème :  multiprod avec roaming et gsm => famille roaming présente dans les deux produits
  12/01/2010MPR
  - Correction du bug 13960 - Ajout de la fonction getFamilyFromGroupTable() pour récupérer le nom de la famille en fonction de edw_group_table
  23/02/2010 - MPR :
  - Correction du BZ 14390 - pas de restriction des liens vers AA pour les familles activées
  16/03/2010 - MPR :
  - Correction du BZ 14574 - Ordre d'affichage des NA incorrect
  20/07/2010 - MPR :
  - Fonction get_ta_min() : Ajout d'un paramètre facultatif $_condition pour ajouter ou non une condition supplémentaire

  26/02/2011 MMT :
  - bz 20191 renomage de fichier si caractères spéciaux
 *
  20/09/2011 MMT Bz 23462 utilisation de la nouvelle fonction getmonth pour récupérer le mois
  24/10/2011 ACS BZ 24356 Product indicated is master even if reprocessing has been launched on slave
  09/12/2011 ACS Mantis 837 DE HTTPS support

 */
?><?php
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
- maj 13/11/2007, gwen : ajout de la fonction get_history
- maj 02/01/2008, christophe : ajout de la fonction getUserProfileType() / getProfileType()
- maj 07/01/2008, christophe : suppression de la fonction getProfileType > devient getUserInfo

- maj 25/02/2008, benoit : modification de la fonction 'get_time_to_calculate()' pour gérer le compute switch et les listes d'heures dans    'hour_to_compute'

- maj 26/02/2008, benoit : modification de la detection du changement de week et de month dans 'get_time_to_calculate()'. On compare         maintenant l'offset day en base avec celui du jour courant et non plus avec offset day - 1

- maj 26/02/2008, christophe : ajout de la fonction displayInDemon

- maj 28/02/2008, benoit : correction du calcul des semaines dans les fonctions 'getweek()', 'GetweekFromAcurioDay()' et                     'GetweekFromAcurioDay2()'

- maj 04/03/2008, maxime :  Ajout d'un filtre dans le TraceLog ce qui implique l'ajout des paramètres severity / module / message / date

- maj 07/03/2008, benoit : désactivation dans '//__debug()' de l'appel à la fonction 'debug_backtrace()' (et également à la fonction         'debug_print_backtrace()' ajoutée postérieurement) qui est incompatible dans certains cas avec "Zend Optimizer"

- maj 25/03/2008, maxime : Ajout de la fonction get_adr_server() : Fonction qui retourne l'adresse du serveur ($_SERVER = NULL lorsque le    script est lancé via cron)

- maj 15/04/2008, benjamin : dans le cas d'un TA BH, on récupère l'historique du TA non BH. Il faut donc traiter la chaine pour retiré le    "_bh"

- maj 16/04/2008, benoit : correction du bug 6328

- maj 17/04/2008, benoit : correction du bug 6330

- maj 26/05/2008 - maxime : On boucle sur toutes les connexions eth pour récupérer l'adresse IP du serveur : Correciton du bug 6328

- maj 27/05/2008 - maxime : Modification de la fonction print_log_ast : Correction du bug 6762

- maj 05/06/2008 - Si le fichier contenant l'adresse IP du server existe on le supprime sinon le lien est mort

- maj 19/06/2008, benoit : correction du bug 6933
*/
?>
<?php
/*
*	@cb30000@
*
*	23/07/2007 - Copyright Acurio
*
*	Composant de base version cb_3.0.0.00
*
*	- 14/08/2007 christophe : modifications de la fonction getNaLabel, quand un élément réseau n'a pas de label et que la fonction retourne
*	sa valeur comme label, on ajout des parenthèses.
*	- maj 23/07/2007 Jérémy : Création de la fonction  " update_serials() "  qui permet de mettre à jour les valeurs START des tables SEQUENCE
*					en allant chercher la valeur maximale du serial dans chaque table concernée
*	- màj 07/08/2007 Jérémy : Création de la fonction getnumber_of_family() afin de cacher l'icone de retour au choix des famille lorsque l'on a qu'une seule famille disponible
*
*/
?>
<?php
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- maj 118/06/2007 Gwénaël : création de la fonction __T, affiche un texte en fonction d'un ID (cf. table sys_definition_messages_display)
*	- maj 21/06/2007 Gwénaël : modification de la fonction __T, le texte est mis dans la SESSION au lieu de GLOBAL (évite de recharge la table à chaque changement de page)
*
*/
?>
<?php
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*
*	- maj 11/05/2007 christophe : ajout d'un paramètre optionnel à la fonction get_sys_global_parameters
* 	- maj 16/04/2007 christophe : modification de la fonction getNaLabelList
*	- maj02/04/2007 christophe :
*		> modification de la fonction get_axe3 pour prendre en compte la nouvelle gestion du 3 ème axe.
*		> ajout de la fonction //__debug pour gérer le debug.
*	- maj 03/04/2007 christophe : ajout d'un groupe de fonctions permettant de gérer le 3 ème axe.
*	- maj 05/04/2007 christophe : nettoyage des fonctions inutlisées, mise à jour des en-têtes des fonctions, mise-à-jour de la fonction getNaLabel.
* 	- maj 06/04/2007 maxime : ajout de la fonction delete_tables($name)  qui supprime toutes les tables dont le nom commence par la variable passée en paramètre
*/
?>
<?php
/*
*	@cb21002@
*
*	23/02/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.02
*/
?>
<?php
/*
*	@cb21000@
*
*	08/12/2006 - Copyright Acurio
*
*	Composant de base version cb_2.1.0.00
*
*	- maj 22 01 2007 christophe : ajout de la fonction getTaValueToDisplayV2_en qui permet de transformer une date au format acurio
*	en format anglais.
*
*/
?>
<?php
/*
*	@cb2001_iu2030_111006@
*
*	11/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.1
*
*	Parser version iu_2.0.3.0
*/
?>
<?php
/*
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?php
/*
*	16/06/2006 - Copyright Acurio
*
*	Composant de base version cb_1.2.0.2p
*
*	Parser version gb_1.0.0b
*/
?>
<?php
/*
  dernière mise à jour 30 12 2005 christophe création de la fonction get_activated_faiture
  dernière mise à jour 23 01 2006 christophe ajout des fonctions de gestion de l'internal_id (generate_acurio_uniq_id, save_acurio_uniq_id, update_acurio_uniq_id, get_acurio_uniq_id)
  dernière mise à jour 26 01 2006 christophe maj de la fonction print_log_ast  et print_log_ast_homepage_admin : il n'y avait plus de fond orange sur le mouseOver
  dernière mise à jour 16 02 2006 stephane        ajout de fonctions de cryptage (en fin de fichier)
  la clé de cryptage est dans la variable $crypto_key
  maj 23 02 2006 christophe : fonction displayKeyEndDate, permet d'afficher la date d'expiration de la clef.
  maj 07 04 2006 : fonction de sauvegarde dans la table edw_alarm_log_error

  - maj DELTA christophe 25 04 2006. cf MODIF DELTA NOUVEAU(ajout)   MODIF DELTA(mise en commentaires des modifications)
  - maj 24 05 2006 christophe ; modification de la fonction getTaQueryMail. (mise en commentaires de certaines lignes)
  - maj 23 06 2006 stephane ; modif des fonctions de cryptage -> plus robustes

  - maj 26/06/2006 benoit : correction de la fonction 'getTaValueToDisplayReverse()' qui ne renvoyait pas la bonne valeur de temps pour les heures

  - maj 18 08 2006 christophe : ajout de la fonction get_sys_debug() ,getNaLabelList(), getTaList(),getFamilyList()

  - maj 08 09 2006 xavier : ajout des fonction get_kpi() et get_counter()

  - maj 29 09 2006 chrisotphe : modification de la fonction getNaLabelList.

  - maj 03 10 2006 xavier : ajout de la fonction get_report_ta_value() utilisée pour l'envoi de rapport.
  - maj 11 10 2006 christophe : maj de la fonction getNaLabelList.

  - maj 27/02/2007 gwénaël : ajout d'une fonction qui permet de couper le numéro de version du produit  > get_product_version
  - maj 05/09/2017 : ajout du case 'dump' dans la fonction displayInDemon  
 */


/* * **
 * 	05/03/2010 BBX
 * 	Cette classe permet de redéfinir les ancienne fonction gloables à T&A comme méthodes statiques.
 * 	Le grand avatage est que toutes ces méthodes vont se partager les mêmes instances de connexion
 * 	à la base de données et ainsi contribuer fortement à l'amélioration des performances.
 *
 * 	Par soucis de compatibilité, toutes les méthodes de la classe "taCommonFunctions" sont mappées
 * 	un peu plus bas dans le script. Celà signifie que chaque appel d'une fonction sera redirigé 
 * 	vers la méthode correspondante.
 *
 * 		08/03/2010 BBX
  - Suppression de la méthode "manageConnections"
  - Utilisation de la méthode "Database::getConnection" à la place.
 * ** */

class taCommonFunctions {

    // Va mémoriser les instances de connexion à la base
    private static $connections = Array();

    /**
     * Fonction get_sys_debug($parameter)
     * Interroge la table sys_debug et retourne la valeur du paramètre $parameter.
     * @param string $parameter nom du paramètre.
     * @param int $id_prod id du produit concerné
     * @return retourne la valeur du parametre ou false
     */
    public static function get_sys_debug($parameter, $id_prod = '') {
        $database = Database::getConnection($id_prod);

        return $database->getone("--- get_sys_debug($parameter,$id_prod)
			SELECT value FROM sys_debug WHERE parameters='$parameter' ");
    }

    /*
     * fonction qui retourne leparamètre de sys_global_parameters
     * @global $database_connection.
     * @param $param : nom du paramètre = colonne parameters de la table sys_global_parameters
     * @param $default_value : si le paramètre $param n'existe pas on retounr la valeur de $default_value
     */

    //- maj 11/05/2007 christophe : ajout d'un paramètre optionnel à la fonction get_sys_global_parameters
    public static function get_sys_global_parameters($param, $default_value = 0, $id_prod = '') {
        $db = Database::getConnection($id_prod);
        $val = $db->getOne("select value from sys_global_parameters where parameters='$param' ");
        if ($val === false)
            return $default_value;
        return $val;
    }

    /**
     * fonction qui retourne l'ensemble des paramètres de la table sys_global_parameters
     *
     * 03/01/2011 16:35 SCT : ajout de la méthode
     *
     * @param $id_prod : si le produit n'est pas définit, le produit courant
     * @return array : tableau contenant l'ensemble des paramètres
     */
    public static function getAllSysGlobalParameters($id_prod = '') {
        $db = Database::getConnection($id_prod);
        $tabRetour = array();
        $result = $db->getAll("select parameters, value from sys_global_parameters");
        foreach ($result as $valeurParameter) {
            $tabRetour[$valeurParameter['parameters']] = $valeurParameter['value'];
        }
        return $tabRetour;
    }

    // maj 23/02/2010 - MPR : Correction du BZ 14390 - pas de restriction des liens vers AA pour les familles activées
    /**
     * Function checkLinktoAAForNA
     * @param string $na : Niveau d'agrégation
     * @param integer $id_prod : Niveau d'agrégation
     * @return string $result : Retourne 1 si les liens vers AA sont actifs pour le NA $na / retourne "" si pas de liens vers AA
     */
    public static function checkLinktoAAForNA($na, $family, $id_prod) {
        $query = "SELECT link_to_aa FROM sys_definition_network_agregation WHERE agregation = '{$na}' AND family = '{$family}' LIMIT 1";
        $db = Database::getConnection($id_prod);
        $result = $db->getOne($query);
        return $result;
    }

    //CB 5.3.1 : Link to Nova Explorer
    public static function checkLinktoNEForNA($na, $family, $id_prod) {
        $query = "SELECT link_to_ne FROM sys_definition_network_agregation WHERE agregation = '{$na}' AND family = '{$family}' LIMIT 1";
        $db = Database::getConnection($id_prod);
        $result = $db->getOne($query);
        return $result;
    }

    // maj 14/01/2009 - MPR : Ajout du paramètre id_prod
    /**
     * getProductInformations : Récupère toutes les informations de tous les produits
     *
     * @since cb4.1.0.00
     * @version cb4.1.0.00
     * @param string $id_prod
     * @return array $result
     */
    public static function getProductInformations($id_prod = '') {
        $database = Database::getConnection(0);

        $condition = ($id_prod !== "") ? " AND sdp_id = $id_prod" : "";
        $query = "SELECT * FROM sys_definition_product WHERE sdp_on_off = 1 $condition ORDER BY sdp_master DESC";
        $result = $database->getAll($query);

        $products = array();

        foreach ($result as $product) {

            $products[$product['sdp_id']] = $product;
        }

        return $products;
    }

    // MAJ CCT1 24/10/08 : on supprime toute référence à la table sys_selecteur_properties qui n'existe plus.
    // MaJ 12/11/2008 - SLC - copie de la fonction getNaLabelList() et ajout du paramètre $id_product et de la gestion des produits
    //	toutes les requêtes sont inchangées, il n'y a juste que la connexion à la base de données du produit qui change
    /**
     * Charge les labels des na de la table SYS_DEFINITION_NETWORK_AGREGATION dans un tableau.
     * Format du tableau (mode par défaut) :
     * $na_label_array[na] [family] = na_label.
     * (quand on utilise les paramètres on se passe sur la requête de sys_selecteur_properties)
     * Format avec les paramètres :
     * $na_label_array[family] [na] = na_label.
     * @param $family : permet de lister seulement les na d'une famille, sinon les na de toutes les familles sont retournées.
     * @param $type : ce paramètre peut prendre 3 valeurs, si = 'all' : liste toutes les na, si = 'na' : liste toutes les na qui ne sont pas 3ème axe.
     * si = 'na_axe3' 
     * @param int $id_product : id du produit considéré
     */
    public static function getNaLabelListForProduct($type = '', $family = '', $id_product = '') {

        $db_temp = Database::getConnection($id_product);

        // Tableau retourné.
        $na_label_array = array();

        if (empty($family) && empty($type)) {
            $liste = " --- get aggregations from sdna
				SELECT DISTINCT agregation, agregation_label, family
					FROM sys_definition_network_agregation
				";
            $result = $db_temp->getall($liste);
            if ($result)
                foreach ($result as $row)
                    $na_label_array[$row["agregation"]][$row["family"]] = ($row["agregation_label"] != "") ? $row["agregation_label"] : $row["agregation"];
        }
        else {
            /*
              Oon récupère la requête qui liste les NA.
             */
            // MAJ CCT1 24/10/08 : on supprime toute référence à la table sys_selecteur_properties qui n'existe plus (avant on allait chercher une partie de la requête dans cette table).
            $q = " --- we get list of NA
				SELECT DISTINCT t0.agregation_label, t0.agregation, t0.mandatory, t0.agregation_rank, t0.family , t0.axe
				FROM sys_definition_network_agregation t0, sys_definition_group_table_network t1, sys_definition_group_table_ref t2 
				WHERE t0.agregation IS NOT NULL 
				AND t0.agregation<>'' 
				AND t0.on_off=1 
				AND t1.id_group_table = t2.id_ligne 
			";

            switch ($type) {
                case 'all' :
                    break;
                case 'na' :
                    // Rien pour l'instant la requête affiche par défaut les NA qui ne sont pas 3ème axe.
                    $q .= " AND t0.axe IS NULL ";
                    $q .= " AND t0.agregation = split_part( t1.network_agregation, '_', 1)";
                    break;
                case 'na_axe3' :
                    // On modifie la condition sur l'axe
                    $q .= " AND t0.axe=3 ";
                    $q .= "  AND t0.agregation = split_part( t1.network_agregation, '_', 2)  ";
                    break;
                default :
                // Rien
            }

            // Si on spécifie une famille, on ajoute la condition.
            if (!empty($family))
                $q .= " AND t0.family='$family' ";

            // On ajoute le order by dans la requête.
            // maj 16/03/2010 - MPR : Correction du BZ 14574 - Ordre d'affichage des NA incorrect
            $q .= " ORDER BY t0.family, t0.axe desc, t0.agregation_rank desc, t0.mandatory asc ";

            // exécution de la requête.
            $result = $db_temp->getall($q);
            if ($result)
                foreach ($result as $row)
                    $na_label_array[$row['family']][$row['agregation']] = $row['agregation_label'];
        }
        return $na_label_array;
    }

    // MaJ 12/11/2008 - SLC  - ajout de la fonction get_na_levels_in_common() compatible avec le multi-produits
    // MaJ 29/06/2011 - SPD1 - la fonction est scindée en deux getNALabelsInCommon et getNALabelsInCommonFromList pour plus de souplesse et pouvoir être utilisée ailleur (query builder)
    /**
     * 	Cette fonction retourne la liste des NA levels en commun pour un GTM donné (par son id_page)
     *
     * 	Pour comprendre :
     * 		- un GTM contient des éléments
     * 		- chaque élément appartient à UNE famille
     * 		- chaque famille contient un ou plusieurs niveau d'aggrégation (NA level)
     *
     * 	Cette fonction :
     * 		-1- va chercher tous les éléments du GTM
     * 		-2- va chercher les familles de ces éléments ( = requête dans la base du produit correspondant)
     * 		-3- fait le tableau de toutes les familles différentes
     * 		-4- va chercher la liste de tous les NA levels de chaque famillle
     * 		-5- cherche l'intersection de tous les NA levels communs à toutes les familles
     *
     *
     * 	30/01/2009 GHX
     * 		- modification des requetes SQL pour mettre les valeurs id_page et id_elem entre cote [REFONTE CONTEXTE]
     *
     * 	@param int	$id_page est l'id du GTM
     * 	@param string	$type type de na levels qu'on cherche (par defaut 'na', mais peut être 'all' ou 'na_axe3')
     * 	@return array	retourne false s'il n'y a aucun na level en commun, et retourne la liste des niveaux d'agrégation en commun sous forme d'un array sinon.
     */
    public static function getNALabelsInCommon($id_page, $type = 'na') {
        $db = Database::getConnection(0);

        // -1- on va chercher la liste des data (raw/kpi) qui composent le GTM $id_page
        $query = " --- fetch kpi/raw that are in GTM $id_page
			select * from sys_pauto_config where id_page='$id_page'";
        $elements = $db->getall($query);
        unset($db);

        // Retourne la liste des NA en commun pour la liste des elements '$elements'
        return self::getNALabelsInCommonFromList($elements, $type);
    }

    /**
     * 	Retourne la liste des NA en commun pour la liste des elements '$elements'
     * 	(voir la méthode getNALabelsInCommon juste au dessus pour plus de commentaires)
     * 
     * 	@param array $elements: la liste des elements dont on veux récupérer les NA en commun
     * 	@param string $type: type de na levels qu'on cherche (par defaut 'na', mais peut être 'all' ou 'na_axe3')
     * 	@return array retourne false s'il n'y a aucun na level en commun, et retourne la liste des niveaux d'agrégation en commun sous forme d'un array sinon.
     */
    public static function getNALabelsInCommonFromList($elements, $type) {
        // -2- on boucle sur tous les éléments et on va chercher leur famille
        foreach ($elements as &$elem) {
            // make the query
            if ($elem['class_object'] == 'counter') {
                $query = " --- get family for counter {$elem['id_elem']}
					(SELECT family FROM sys_definition_group_table WHERE edw_group_table IN
						(SELECT edw_group_table FROM sys_field_reference WHERE id_ligne = '{$elem['id_elem']}')
					)";
            } else {
                $query = " --- get family for kpi {$elem['id_elem']}
					(SELECT family FROM sys_definition_group_table WHERE edw_group_table IN
						(SELECT edw_group_table FROM sys_definition_kpi WHERE id_ligne = '{$elem['id_elem']}')
					)";
            }
            // choose db
            $db_temp = Database::getConnection($elem['id_product']);
            $elem['family'] = $db_temp->getone($query);
            unset($db_temp);
        }

        // -3- maintenant qu'on a les elements et leurs familles, on compose le tableau de toutes les familles différentes des éléments
        if ($elements) {
            $families = array();

            // maj 27/10/2009 MPR : Correction du bug 12246 - Ajout du produit dans l'id de la famille
            foreach ($elements as $e) {
                if (!in_array($e["family"], $families)) {
                    $families[] = $e["id_product"] . "_" . $e["family"];
                }
            }
        }

        // -4- on va chercher toutes les listes de na_label sur tous les produits
        $na_levels = getNaLabelListAcrossProducts($type);
        // ex:		$na_labels['1_apn'] =  [ 'apnamegroup' => 'APName Group', 'apname' => 'APName' ]

        $na_levels_in_common = null;

        // -5- on prend les na_levels de la première famille
        // SPD1 - evite une erreur si $families[0] nous trouvée
        if (isset($na_levels[$families[0]])) {
            $na_levels_in_common = $na_levels[$families[0]];
        }

        if (!is_array($na_levels_in_common))
            return false;

        // on boucle sur toutes les familles pour trouver tous les NA levels communs à ces familles
        // var_dump($na_levels);
        for ($i = 1; $i < sizeof($families); $i++) {
            if (!isset($na_levels[$families[$i]]) || !is_array($na_levels[$families[$i]]))
                return false;
            $na_levels_in_common = array_intersect_assoc($na_levels_in_common, $na_levels[$families[$i]]);
        }

        // si on a rien :
        if (sizeof($na_levels_in_common) == 0) {
            return false;
        } else {
            // on renvoie la liste
            return $na_levels_in_common;
        }
    }

    /**
     * Indique si l'axe3 est défini pour une famille et un produit passés en paramètres
     *
     * @param string $family nom de la famille
     * @param integer $product identifiant du produit (optionnel)
     * @return boolean présence (true) / absence (false) de l'axe3 pour la famille / le produit
     */
    public static function GetAxe3($family, $product = '') {
        $db = Database::getConnection($product);

        $axe3_agreg = $db->getOne("SELECT agregation FROM sys_definition_network_agregation WHERE family = '$family' AND axe = 3");

        return ($axe3_agreg != "");
    }

    /**
     * Retourne la liste des ta de l'application
     * @return array
     */
    public static function getTaLabelList($id_prod = '') {
        // 20/12/2010 BBX
        // Corection de la fonction
        // => Utilisation d'un while sur le résultat
        // => Plus de génération d'un "exit" ici !!!!!!!!!
        // Cadre du BZ 18510
        $database = Database::getConnection($id_prod);

        $query = "SELECT agregation, agregation_label FROM sys_definition_time_agregation
				WHERE on_off=1 AND visible=1 ORDER BY agregation_rank";

        $result = $database->execute($query);

        $tab_ta = array();
        while ($row = $database->getQueryResults($result, 1)) {
            $tab_ta[$row['agregation']] = ($row["agregation_label"] != "") ? $row["agregation_label"] : $row["agregation"];
        }

        return $tab_ta;
    }

    /**
     * Retourne la liste des ta en commun pour un ensemble de produits
     * @param $prodList array : la liste des id produits 
     * @return array : la liste des ta en commun
     */
    public static function getCommonTa($prodList) {
        $ta = Array();
        foreach ($prodList as $p) {
            if (count($ta) == 0) {
                $ta = getTaLabelList($p);
            } else {
                $ta = array_intersect_key($ta, getTaLabelList($p));
            }
        }
        return $ta;
    }

    /**
     * Retourne un tableau contenant les chemins d'aggrégations de toutes les familles pour un produit
     *
     * Il est possible de n'avoir qu'une seule ou ne pas avoir de notion de famille pour ca il suffit de spécifié le deuxième paramètres
     * en mettant par le nom d'une famille si on veut avoir que les chemins d'une famille ou alors en mettant "no" si on ne veut pas de notion de famille.
     * S'il n'y a pas de notion de famille toutes les éléments réseaux sont "ensembles"
     * 	"" => notion de famille
     * 	"no" => pas de notion de famille
     * 	"nom d'une famille" => uniquement les éléments réseaux d'une famille
     *
     * Il est aussi possible de ne pas avoir de notion d'axe réseau ou alors de n'avoir qu'un seul axe. Pour cela on spécifie le 3ieme paramètre lors de l'appel de la fonction.
     * 	"" => notion d'axe
     * 	"no" => pas de notion d'axe
     * 	1 => uniquement les éléments réseaux du premier axe
     * 	3 => uniquement les éléments réseaux du troisieme axe
     *
     *
     * En fonction des paramètres 2 et 3, la structure du tableau n'est pas la même.
     *
     * Si la fonction ne peut pas récupérer les niveaux d'aggrégation FALSE est retourné
     *
     * @author GHX
     * @version CB_v4.1.0.00
     * @param array/string $product : identifiant du produit (vide par défaut)
     * @param string $family : nom de la famille (toutes par défaut)
     * @param int $axe : axe sur lequel on veut les niveaux d'aggrégation 1 ou 3 (les 2 par défaut)
     * @param boolean $ChildToParent Vrai si l'on veut avoir les niveaux fils en index (false par défaut : c'est le niveau parent qui est en index)
     * @return array
     */
    public static function getPathNetworkAggregation($product = '', $family = '', $axe = '', $ChildToParent = false) {
        $fields = "agregation,level_source,";

        // Si on veut les fils en index on inverse les colonnes
        if ($ChildToParent == true) {
            $fields = "level_source,agregation,";
        }

        $query = "
				--- Récupère les relations entre les niveaux d'aggrégation réseaux de chaque famille
				
				SELECT
					family,
					$fields
					CASE WHEN axe IS NULL THEN 1 ELSE axe END AS axe
				FROM 
					sys_definition_network_agregation 
				WHERE agregation IN (
							SELECT distinct t2.agregation
							FROM (sys_definition_group_table t0 LEFT JOIN sys_definition_group_table_network t1 ON (t0.id_ligne = t1.id_group_table))
								LEFT JOIN sys_definition_network_agregation t2 ON (t0.family = t2.family)
							WHERE (
								t1.network_agregation LIKE  t2.agregation || '%' 
								OR t1.network_agregation LIKE '%' || t2.agregation
							)
						)
				ORDER BY family, axe
			";


        $pathNA = array();

        // 07/07/2011 NSE bz 22888 : la variable peut être une chaîne ou un tableau
        if (empty($product)) {
            $listProduct = array_keys(getProductInformations());
        } else {
            if (is_array($product)) {
                $listProduct = $product;
            } else {
                $listProduct = array($product);
            }
        }

        foreach ($listProduct as $idProduct) {
            // Si le produit est vide on se connecte sur la base par défaut
            $db = Database::getConnection($idProduct);

            $results = $db->getAll($query);
            $numberResult = count($results);

            // Si aucun résultat on retourne FALSE
            if ($numberResult == 0)
                continue;

            if ($axe == 'no') { // Si on ne veut pas de notion d'axe
                // On ne veut pas de notion de famille
                if ($family == 'no') {
                    foreach ($results as $line) {
                        list($tmpFamily, $tmpAgregation, $tmpAgregation_source, $tmpAxe) = array_values($line);

                        if ($tmpAgregation == $tmpAgregation_source)
                            continue;

                        // CAS : pas de notion de famille / pas de notion d'axe
                        $pathNA[$tmpAgregation][] = $tmpAgregation_source;
                        $pathNA[$tmpAgregation] = array_unique($pathNA[$tmpAgregation]);
                    }
                }
                elseif ($family == '' || $family == null) { // On a toutes les familles
                    foreach ($results as $line) {
                        list($tmpFamily, $tmpAgregation, $tmpAgregation_source, $tmpAxe) = array_values($line);

                        if ($tmpAgregation == $tmpAgregation_source)
                            continue;

                        // CAS : notion de famille / pas de notion d'axe
                        $pathNA[$tmpFamily][$tmpAgregation][] = $tmpAgregation_source;
                    }
                }
                else {
                    foreach ($results as $line) {
                        list($tmpFamily, $tmpAgregation, $tmpAgregation_source, $tmpAxe) = array_values($line);

                        if ($tmpAgregation == $tmpAgregation_source)
                            continue;

                        if ($tmpFamily == $family) {
                            // CAS : 1 seule famille / pas de notion d'axe
                            $pathNA[$tmpAgregation][] = $tmpAgregation_source;
                        }
                    }
                }
            } elseif ($axe == '' || $axe == null) { // Si on a les 2 axes réseaux
                // On ne veut pas de notion de famille
                if ($family == 'no') {
                    foreach ($results as $line) {
                        list($tmpFamily, $tmpAgregation, $tmpAgregation_source, $tmpAxe) = array_values($line);

                        if ($tmpAgregation == $tmpAgregation_source)
                            continue;

                        // CAS : pas de notion de famille / notion d'axe
                        $pathNA[$tmpAxe][$tmpAgregation][] = $tmpAgregation_source;
                        $pathNA[$tmpAxe][$tmpAgregation] = array_unique($pathNA[$tmpAxe][$tmpAgregation]);
                    }
                }
                elseif ($family == '' || $family == null) { // On a toutes les familles
                    foreach ($results as $line) {
                        list($tmpFamily, $tmpAgregation, $tmpAgregation_source, $tmpAxe) = array_values($line);

                        if ($tmpAgregation == $tmpAgregation_source)
                            continue;

                        // CAS :  notion de famille / notion d'axe (cas par défaut)
                        $pathNA[$tmpFamily][$tmpAxe][$tmpAgregation][] = $tmpAgregation_source;
                    }
                }
                else {
                    foreach ($results as $line) {
                        list($tmpFamily, $tmpAgregation, $tmpAgregation_source, $tmpAxe) = array_values($line);

                        if ($tmpAgregation == $tmpAgregation_source)
                            continue;

                        if ($tmpFamily == $family) {
                            // CAS : 1 seule famille / notion d'axe
                            $pathNA[$tmpAxe][$tmpAgregation][] = $tmpAgregation_source;
                        }
                    }
                }
            } else { // Un seul axe réseau
                // On ne veut pas de notion de famille
                if ($family == 'no') {
                    foreach ($results as $line) {
                        list($tmpFamily, $tmpAgregation, $tmpAgregation_source, $tmpAxe) = array_values($line);

                        if ($tmpAgregation == $tmpAgregation_source)
                            continue;

                        if ($tmpAxe == $axe) {
                            // CAS : pas de notion de famille / 1 seul axe
                            $pathNA[$tmpAgregation][] = $tmpAgregation_source;
                            $pathNA[$tmpAgregation] = array_unique($pathNA[$tmpAgregation]);
                        }
                    }
                } elseif ($family == '' || $family == null) { // On a toutes les familles
                    foreach ($results as $line) {
                        list($tmpFamily, $tmpAgregation, $tmpAgregation_source, $tmpAxe) = array_values($line);

                        if ($tmpAgregation == $tmpAgregation_source)
                            continue;

                        if ($tmpAxe == $axe) {
                            // CAS : notion de famille / 1 seul axe
                            $pathNA[$tmpFamily][$tmpAgregation][] = $tmpAgregation_source;
                        }
                    }
                } else {
                    foreach ($results as $line) {
                        list($tmpFamily, $tmpAgregation, $tmpAgregation_source, $tmpAxe) = array_values($line);

                        if ($tmpAgregation == $tmpAgregation_source)
                            continue;

                        if ($tmpFamily == $family && $tmpAxe == $axe) {
                            // CAS : 1 seule famille / 1 seul axe
                            $pathNA[$tmpAgregation][] = $tmpAgregation_source;
                        }
                    }
                }
            }
        }

        // Si aucun résultat on retourne FALSE
        if (count($pathNA) == 0)
            return false;

        return $pathNA;
    }

// End function getPathNetworkAggregation

    /**
     * Permet de récupérer le label d'une network aggregation à partir d'une table object_ref.
     * @global $database_connection
     * @param $network_aggregation_value : valeur de la NA exemple : sai_332
     * @param $network_aggregation : nom de la na  exemple : sai
     * @param $family : nom de la famille de la NA
     * @return retourne le lbel de la na_value passée en paramètre, si $all='all' on retourne
     * alors un tableau contenant la liste de tous les labels/na_value ne la NA/famille passée en paramètre
     * de la forme suivante : $tab[family][na][na_value] = na_label exemple : $tab['ept']['sai']['sai_angS1'] = 'Angers sud 1'
     */

    /**
     * Permet de récupérer le label d'une network aggregation à partir d'une table object_ref.
     * @global $database_connection
     * @param $network_aggregation_value : valeur de la NA exemple : sai_332
     * @param $network_aggregation : nom de la na  exemple : sai
     * @param $family : nom de la famille de la NA
     * @param string $product = id du produit en cours
     * @return retourne le label de la na_value passée en paramètre, si $all='all' on retourne
     * alors un tableau contenant la liste de tous les labels/na_value ne la NA/famille passée en paramètre
     * de la forme suivante : $tab[family][na][na_value] = na_label exemple : $tab['ept']['sai']['sai_angS1'] = 'Angers sud 1'
     */
    // 05/12/2008 - SLC - supression de toute reference à $database_connection et utilisation du parametre $product dans tous les cas
    public static function getNaLabel($network_aggregation_value, $network_aggregation, $family, $product) {
        $db = Database::getConnection($product);

        // $nom_table = $db->getone("SELECT object_ref_table FROM sys_definition_categorie WHERE family='$family' ");
        $nom_table = 'edw_object_ref';
        if ($nom_table) {
            // On gère la cas où l'on a une agrégation de type sai_tos
            if (strstr($network_aggregation, '_')) {
                // maj 16/01/2009 - MPR : Ajout du produit pour récupérer le séparateur d'axe3
                $sep = get_sys_global_parameters('sep_axe3', '', $product);
                $network_aggregation_temp = explode("_", $network_aggregation);
                $network_aggregation_label_1 = $network_aggregation_temp[0] . "_label";
                $network_aggregation_label_2 = $network_aggregation_temp[1] . "_label";
                $network_aggregation_1 = $network_aggregation_temp[0];
                $network_aggregation_2 = $network_aggregation_temp[1];
                /*
                  - 14/08/2007 christophe : modifications de la fonction getNaLabel, quand un élément réseau n'a pas de label et que la fonction retourne
                  sa valeur comme label, on ajout des parenthèses.
                 */
                $query_search_na_label = "
					SELECT
						CASE WHEN $network_aggregation_label_1 is null AND $network_aggregation_label_2 is null
							THEN '('||$network_aggregation_1||')'||'$sep'||'('||$network_aggregation_2||')'
								ELSE
									CASE WHEN $network_aggregation_label_1 is null AND $network_aggregation_label_2 is not null
										THEN '('||$network_aggregation_1||')'||'$sep'||$network_aggregation_label_2
											ELSE
												CASE WHEN $network_aggregation_label_1 is not null AND $network_aggregation_label_2 is null
													THEN $network_aggregation_label_1||'$sep'||'('||$network_aggregation_2||')'
														ELSE
															$network_aggregation_label_1||'$sep'||$network_aggregation_label_2
												END
									END
						END
						FROM $nom_table
						WHERE $network_aggregation='$network_aggregation_value'
						LIMIT 1
				";
            } else {
                $network_aggregation_label = $network_aggregation . "_label";
                /*
                  - 14/08/2007 christophe : modifications de la fonction getNaLabel, quand un élément réseau n'a pas de label et que la fonction retourne
                  sa valeur comme label, on ajout des parenthèses.
                 */
                $query_search_na_label = "
					SELECT DISTINCT CASE WHEN eor_label IS NULL THEN '('||eor_id||')' ELSE eor_label END 
					FROM $nom_table
					WHERE eor_id IS NOT NULL
						AND	eor_obj_type = '$network_aggregation'
						AND eor_id='$network_aggregation_value'
					LIMIT 1
				";
            }
            $get_na_label = $db->getone($query_search_na_label);
            if ($get_na_label) {
                return $get_na_label;
            } else {
                return '';
            }
        }
    }

    // MAJ CCT1 24/10/08 : on supprime toute référence à la table sys_selecteur_properties qui n'existe plus.
    /**
     * Charge les labels des na de la table SYS_DEFINITION_NETWORK_AGREGATION dans un tableau.
     * Format du tableau (mode par défaut) :
     * $na_label_array[na] [family] = na_label.
     * (quand on utilise les paramètres on se passe sur la requête de sys_selecteur_properties)
     * Format avec les paramètres :
     * $na_label_array[family] [na] = na_label.
     * @param $family : permet de lister seulement les na d'une famille, sinon les na de toutes les familles sont retournées.
     * @param $type : ce paramètre peut prendre 3 valeurs, si = 'all' : liste toutes les na, si = 'na' : liste toutes les na qui ne sont pas 3ème axe.
     * si = 'na_axe3'
     */
    // MaJ 20/11/2008 - SLC - ajout $product, supression $database_connection
    public static function getNaLabelList($type = '', $family = '', $product = '') {
        $db = Database::getConnection($product);

        // Tableau retourné.
        $na_label_array = array();

        if (empty($family) && empty($type)) {
            $liste = "
				SELECT DISTINCT agregation, agregation_label, family
				FROM sys_definition_network_agregation
				";
            $result = $db->getAll($liste);
            $nombre_resultat = count($result);

            if ($result)
                foreach ($result as $row)
                    $na_label_array[$row["agregation"]][$row["family"]] = ($row["agregation_label"] != "") ? $row["agregation_label"] : $row["agregation"];
        } else {

            /* 	On récupère la requête qui liste les NA.	 */
            // MAJ CCT1 24/10/08 : on supprime toute référence à la table sys_selecteur_properties qui n'existe plus (avant on allait chercher une partie de la requête dans cette table).
            $q = "
				SELECT DISTINCT t0.agregation_label, t0.agregation, t0.mandatory, t0.agregation_rank, t0.family , t0.axe
				FROM sys_definition_network_agregation t0, sys_definition_group_table_network t1, sys_definition_group_table_ref t2 
				WHERE t0.agregation IS NOT NULL 
					AND t0.agregation<>'' 
					AND t0.on_off=1 
					AND t1.id_group_table = t2.id_ligne 
			";

            switch ($type) {
                case 'all' :
                    break;
                case 'na' :
                    // Rien pour l'instant la requête affiche par défaut les NA qui ne sont pas 3ème axe.
                    $q .= " AND t0.axe IS NULL ";
                    $q .= " AND t0.agregation = split_part( t1.network_agregation, '_', 1)";
                    break;
                case 'na_axe3' :
                    // On modifie la condition sur l'axe
                    $q .= " AND t0.axe=3 ";
                    $q .= "  AND t0.agregation = split_part( t1.network_agregation, '_', 2)  ";
                    break;
                default :
                // Rien
            }

            // Si on spécifie une famille, on ajoute la condition.
            if (!empty($family))
                $q .= " AND t0.family='$family' ";

            // On ajoute le order by dans la requête.
            // maj 16/03/2010 - MPR : Correction du BZ 14574 - Ordre d'affichage des NA incorrect
            $q .= " ORDER BY t0.family, t0.axe desc, t0.agregation_rank desc, t0.mandatory asc ";

            // exécution de la requête.
            $resultat = $db->getAll($q);

            if ($resultat > 0)
                foreach ($resultat as $row)
                    $na_label_array[$row['family']][$row['agregation']] = $row['agregation_label'];
        }

        return $na_label_array;
    }

    /**
     * 	Met à jour le log
     * */
    public static function sys_log($quoi, $theme, $url, $start) {
        $db = Database::getConnection(0);
        list($usec, $sec) = explode(" ", microtime());
        $stop = ((float) $usec + (float) $sec);
        $duree = ceil($stop - $start);
        $quand = date("F j, Y, H:i s");
        $query = "insert into sys_log values ('$quoi','$url','$quand','$theme','$duree')";
        $db->execute($query);
    }

    /**
     * fonction qui insere dans la log les données envoyées par un script
     */
    // 24/10/2011 ACS BZ 24356 Product indicated is master even if reprocessing has been launched on slave
    public static function sys_log_ast($severity = 'Info', $application = '', $module = '', $message = '', $type_message = '', $object = '', $idProduct = '') {

        $db = Database::getConnection($idProduct);

        $date = date("Y/m/d H:i:s");
        $query = "INSERT INTO sys_log_ast(message_date,severity,
									application,module,message,type_message,object)
											values ('$date','$severity',
															'$application','$module','$message','$type_message','$object')";
        $db->execute($query);
    }

}

/* * **
 * 	05/03/2010 BBX
 * 	Par soucis de compatibilité, toutes les méthodes de la classe "taCommonFunctions" sont mappées
 * 	ci-dessous. Celà signifie que chaque appel d'une fonction sera redirigé vers la méthode correspondante
 * 	dans la classe taCommonFunctions.
 * ** */

function get_sys_debug($parameter, $id_prod = '') {
    return taCommonFunctions::get_sys_debug($parameter, $id_prod);
}

function get_sys_global_parameters($param, $default_value = 0, $id_prod = '') {
    return taCommonFunctions::get_sys_global_parameters($param, $default_value, $id_prod);
}

function checkLinktoAAForNA($na, $family, $id_prod) {
    return taCommonFunctions::checkLinktoAAForNA($na, $family, $id_prod);
}

//CB 5.3.1 : Link to Nova Explorer
function checkLinktoNEForNA($na, $family, $id_prod) {
    return taCommonFunctions::checkLinktoNEForNA($na, $family, $id_prod);
}

function getProductInformations($id_prod = '') {
    return taCommonFunctions::getProductInformations($id_prod);
}

function getNaLabelListForProduct($type = '', $family = '', $id_product = '') {
    return taCommonFunctions::getNaLabelListForProduct($type, $family, $id_product);
}

function getNALabelsInCommon($id_page, $type = 'na') {
    return taCommonFunctions::getNALabelsInCommon($id_page, $type);
}

function getNALabelsInCommonFromList($elements, $type) {
    return taCommonFunctions::getNALabelsInCommonFromList($elements, $type);
}

function GetAxe3($family, $product = '') {
    return taCommonFunctions::GetAxe3($family, $product);
}

function getTaLabelList($id_prod = '') {
    return taCommonFunctions::getTaLabelList($id_prod);
}

function getCommonTa($prodList) {
    return taCommonFunctions::getCommonTa($prodList);
}

function getPathNetworkAggregation($product = '', $family = '', $axe = '', $ChildToParent = false) {
    return taCommonFunctions::getPathNetworkAggregation($product, $family, $axe, $ChildToParent);
}

function getNaLabel($network_aggregation_value, $network_aggregation, $family, $product) {
    return taCommonFunctions::getNaLabel($network_aggregation_value, $network_aggregation, $family, $product);
}

function getNaLabelList($type = '', $family = '', $product = '') {
    return taCommonFunctions::getNaLabelList($type, $family, $product);
}

function sys_log($quoi, $theme, $url, $start) {
    return taCommonFunctions::sys_log($quoi, $theme, $url, $start);
}

/**
 * Ecrit une ligne dans le trace log.
 * 
 * @param string $severity niveau du log 'Info', 'Warning', 'Critical' (liste officiel : cf. IHM T&A) 
 * @param string $application @deprecated  nom de l'application (en général : 'Trending&Agregation')
 * @param string $module nom du module T&A par exemple 'Data Collect' (cf. IHM T&A)  
 * @param string $message le texte du message : doit être concis et en anglais
 * @param string $type_message @deprecated  tout le temps la même chaîne 'support1'
 * @param string $object @deprecated chaine vide
 * @param string $idProduct: id of the product
 * 03/12/2012 BBX
 * BZ 30310 : correction de la valeur par défaut support_1
 */
function sys_log_ast($severity = 'Info', $application = '', $module = '', $message = '', $type_message = 'support_1', $object = '', $idProduct = '') {
    return taCommonFunctions::sys_log_ast($severity, $application, $module, $message, $type_message, $object, $idProduct);
}

/* * **
 * 	Fin mapping
 * ** */

/**
 * Renvoie un texte par rapport à son identifiant, si celui-ci n'existe pas "Undefined [$id_msg]" est retourné. Si le texte contient des arguments
 * ils seront remplacés par ceux passés en paramètres de la fonction. S'il y a trop d'arguments dans le texte par rapport au nombres d'argument de la fonction,
 * ceux-ci seront supprimé. Et vice-versa, si trop d'arguments en paramètre de la fonction, ils ne seront pas pris en compte dans le texte.
 *
 * Exemple :
 * 	ID_MSG = "na = $1, na_value = $2 "
 * 	echo __T("ID_MSG", 'Cell', 'c1'); // Affiche : na = Cell, na_value = c1
 *
 * Style de nomenclature des ID :
 * 	1. La première lettre correspond à la partie Admin (A) User (U) ou général (G)
 * 	2. On peut avoir E (pour lune erreur) TOOLTIP, JS (si c'est un message d'alert javascript) ou rien du tout si message classique
 * 	3. Le nom du module
 * 	4. Le titre du message (celui doit être très explicite)
 *
 *
 * Exemples :
 * 	A_KPI_BUILDER_FORM_BTN_DROP = "Drop"
 * 	A_COUNTER_ACTIVATION_DBL_CLICK_FOR_DISPLAY_INFO = "Double-click on item to get info."
 * 	A_E_GTM_BUILDER_ELEMENT_EXISTS_IN_GRAPH = "This element already exists in this graph"
 * 	A_JS_ALARM_CREATION_FIELD_NAME = "Please, fill in the 'Alarm name' field"
 * 	U_CART_IS_EMPTY = "Your cart is empty"
 * 	U_QUERY_BUILDER_ONGLET_EQUATION_DEFINE = "EQUATION DEFINE"
 * 	U_ALARM_CALCULATION_TIME = "Calculation time"
 * 	U_JS_SELECTEUR_KPI_FILTER_NOT_OPERAND_AND_VALUE  = "The filter operand is not set.\nThe filter value is empty"
 * 	U_TOOLTIP_SELECTEUR_OPEN_CALENDER = "Open calendar"
 * 	G_E_LOGIN_INVALID = "Invalid Login or Password"
 *
 * @param string $id_msg : id du message
 * @params (optionnel) string liste d'argurments
 * @return string
 */
// maj 10/11/2008 On ajoute l'id du produit afin de récupérer le message à afficher sur la base concernée (par défault valeur = "")
function __T($id_msg) {
    //  - modif 21/06/2007 Gwénaël :
    //	_SESSION au lieu de GLOBAL
    $database = Database::getConnection(0);

    //Création du tableau $_SESSION['msg_display'] s'il n'existe pas
    if (!isset($_SESSION['msg_display'])) {

        $query_msg = "SELECT id, text FROM sys_definition_messages_display";
        $result_msg = $database->getAll($query_msg);
        if (count($result_msg) > 0) {
            $_SESSION['msg_display'] = array();
            foreach ($result_msg as $row)
                $_SESSION['msg_display'][$row['id']] = $row['text'];
        }
    }

    //Renvoie undefined si l'ID du message n'existe pas
    if (!isset($_SESSION['msg_display']) || !isset($_SESSION['msg_display'][$id_msg]))
        return 'Undefined [' . $id_msg . ']';

    //Récupère le texte en fonction de l'ID
    $txt = $_SESSION['msg_display'][$id_msg];
    // Si le nombre d'arguments est supérieur à 1 (le premier étant l'id du message)
    // on remplace les arguments dans le texte
    $numArgs = func_num_args();
    if ($numArgs > 1) {
        $arg_list = func_get_args();
        // remplace tous les arguments dans le texte
        //for ( $i = 1; $i < $numArgs; $i++ )
        // 14:48 11/05/2009 GHX
        // On change l'inverse la boucle for
        for ($i = $numArgs - 1; $i > 0; $i--)
            $txt = str_replace('$' . $i, (string) $arg_list[$i], $txt);
    }
    // Supprime les arguments dans le texte qui sont en trop
    $txt = preg_replace('/\$[0-9]+/', '', $txt);

    //Renvoi le texte
    return $txt;
}

// End function __T

/**
 * displayInDemon : permet d'afficher du texte dans le démon.
 *
 * 02/01/2012 BBX
 * BZ 25230 : adding a 3d parameters to function displayInDemon 
 * in order to write directly into html log file
 *
 * @since cb4.0.0.00
 * @version cb4.0.0.00
 * @param string $text texte à afficher dans le démon
 * @param string $type type de texte : title = c'est un titre, normal par défaut, list = c'est une liste, alert = en rouge
 * @param boolean direct writing
 * @return string $to_display
 */
function displayInDemon($text, $type = 'normal', $directWriting = false) {
    $to_display = '';

    switch ($type) {
        case 'title' :
            $to_display = "<div style='background-color:#e6e6e6;padding:5px;font-family:Verdana, Arial, sans-serif;margin:5px;'><h3>$text</h3></div>";
            break;
        case 'normal' :
            $to_display = "<div style='font : normal 7pt Verdana, Arial, sans-serif;color : #000000;'>$text</div>";
            break;
        case 'list' :
            $to_display = "<li style='font : normal 9pt Verdana, Arial, sans-serif;color : #000000; margin-left:10px;'>$text</li>";
            break;
        // 11:36 09/07/2009 SCT : ajout d'un niveau de display
        case 'alert' :
            $to_display = '<p style="font : normal 9pt Verdana, Arial, sans-serif;color : #ff0000; margin-left:10px;">' . $text . '</p>';
            break;
        case 'dump':
            echo '<pre>';
            var_dump($text);
            echo '</pre>';
            break;
        default :
            $to_display = $text;
    }

    if ($directWriting) {
        file_put_contents(REP_PHYSIQUE_NIVEAU_0 . 'file_demon/demon_' . date('Ymd') . '.html', $to_display, FILE_APPEND);
    } else
        echo $to_display;
}

// End function displayInDemon

/*
 * Fonction qui supprime les tables dont le nom commence par la variable passée en paramètre
 * @param string $name : est le debut du nom des tables à supprimer
 * Le nombre de tables supprimées est renvoyé
 */

function delete_tables($name) {
    global $database_connection;
    $nb_tables_deleted = 0;
    $query = "SELECT tablename FROM pg_tables WHERE schemaname = 'public' and tablename like '" . $name . "%';";
    $result = pg_query($database_connection, $query);
    $nb_res = pg_num_rows($result);
    if ($nb_res > 0) {
        for ($i = 0; $i < $nb_res; $i++) {
            $row = pg_fetch_array($result, $i);
            $delete_tables = "drop table " . $row["tablename"] . "";
            $res = pg_query($delete_tables);
            if ($res)
                $nb_tables_deleted++;
        }
    }
    return $nb_tables_deleted;
}

/**
 * Fonction qui permet d'afficher le contenu d'une variable de façon formaté
 * le contenu de la variable n'est affiché que si la variable debug_global de la table
 * sys_debug est à 1
 * @param mixed $variable : est la variable dont on veut afficher le contenu
 * @param int $display_type : type d'affichage
 * 	- 0 : print_r	(defaut)
 * 	- 1 : var_dump
 * 	- 2 : krumo [pour plus d'info http://krumo.sourceforge.net/]
 * @param string $texte : est le texte à afficher avant l'affichage du contenu de la variable
 * @param string $kill_type : si vide ren ne se passe si = exit il y aura un exit après l'affichage si = die il y aura un die après l'affichage
 * @return le contenu de la variable quelque soit son type
 */
function __debug($variable, $texte = '', $display_type = 0, $kill_type = '', $id_prod = '') {
    global $repertoire_physique_niveau0;

    if ($id_prod !== '') {

        $check = get_sys_debug('debug_global', $id_prod);
    } else {

        $check = get_sys_debug('debug_global');
    }

    if ($check) {

        // 07/03/2008 - Modif. benoit : on n'utilise plus la fonction 'debug_backtrace()'. En effet, il apparait que celle-ci présente des incompatibilités avec l'extension 'Zend Optimizer' et peut potentiellement bloquer l'application. On utilise donc à la place la fonction 'debug_print_backtrace()'

        /* $_ = debug_backtrace();

          while( $d = array_pop($_) ) {

          if ( (strToLower($d['function']) == '//__debug') ) {
          break;
          }
          }

          echo '<div style="test-align:left">';

          if ( !empty ( $texte ) )
          echo "<u><b>$texte : </b></u>&nbsp;";

          echo '<span style="font: 9pt Verdana;color:#585858">[file <code>'.$d['file'].'</code> - line <code>'.$d['line'].'</code>]</span><br />'; */

        // 07/03/2008 - Modif. benoit : la fonction 'debug_print_backtrace()' est également incompatible avec "Zend Optimizer". On la désactive donc également

        /* // La fonction 'debug_print_backtrace()' n'ayant pas de valeur de retour, on utilise les fonctions PHP de bufferisation pour sauvegarder dans une variable le résultat de cette fonction tel qu'il apparait dans le navigateur

          ob_start();
          debug_print_backtrace();
          $print_trace = ob_get_contents();
          ob_end_clean();

          $tab_trace = explode('#', $print_trace);

          for ($i=0; $i < count($tab_trace); $i++) {
          if (!(strpos($tab_trace[$i], '//__debug') === false)) {

          // On extrait de la ligne son emplacement et son numéro
          // Forme de la ligne : "0 //__debug(20080205, date) called at [/home/cb40000_iu31012_dev/class/alarm_calculation.class.php:404]"

          $start_chr_pos	= strpos($tab_trace[$i], '[')+1;
          $stop_chr_pos	= strpos($tab_trace[$i], ']');

          $function_info = explode(':', substr($tab_trace[$i], $start_chr_pos, $stop_chr_pos-$start_chr_pos));
          }
          }

          echo '<span style="font: 9pt Verdana;color:#585858">[file <code>'.$function_info[0].'</code> - line <code>'.$function_info[1].'</code>]</span><br/>'; */

        // 28/04/2009 GHX
        // Ajout du style
        echo '<div style="text-align:left">';

        if (!empty($texte))
            echo "<u><b>$texte : </b></u>&nbsp;";

        switch ($display_type) {
            case 1 :
                echo '<pre>';
                var_dump($variable);
                echo '</pre>';
                break;
            case 2 :
                include_once $repertoire_physique_niveau0 . 'class/class.krumo.php';
                krumo($variable);
                break;
            case 0 :
            default:
                echo '<pre>' . print_r($variable, 1) . '</pre>';
        }

        echo '</div>';

        if ($kill_type == 'die')
            die;
        if ($kill_type == 'exit')
            exit;
    }
}

/**
 * Récupère dans un tableau toutes les nom / label des Time aggregation actives.
 * @return $ta_array[nom de la TA] = label de la TA
 * 	17/07/2009 BBX : adaptation pour CB 5.0
 */
function getTaList($where = '', $product = '') {
    // 31/01/2011 BBX
    // On remplace new DatabaseConnection() par Database::getConnection()
    // BZ 20450
    $database = Database::getConnection($product);

    $query = "SELECT agregation, agregation_label 
	FROM sys_definition_time_agregation
	WHERE on_off=1 AND visible=1 $where
	ORDER BY agregation_rank";

    $result = $database->execute($query);
    //22/09/2011 MMT bz 23824 il ne faut pas faire d'exit si pas de TA
    $ta_array = Array();
    if ($database->getNumRows() > 0) {
        $ta_array = Array();
        while ($row = $database->getQueryResults($result, 1)) {
            $ta_array[$row["agregation"]] = ($row["agregation_label"] != "") ? $row["agregation_label"] : $row["agregation"];
        }
    }
    return $ta_array;
}

// 05/02/2009 - Modif. benoit : création de la fonction ci-dessous

/**
 * Retourne la ta source par defaut d'une ta donnée
 *
 * @param string $na nom de la na dont on souhaite la source
 * @param int $product identifiant du produit sur lequel on souhaite déterminer la ta source par defaut
 * @return array un tableau contenant le nom et le label de la ta source par defaut
 *
 */
function getTaSourceDefault($ta, $product = '') {
    // 31/01/2011 BBX
    // On remplace new DatabaseConnection() par Database::getConnection()
    // BZ 20450
    $db = Database::getConnection($product);

    $sql = " SELECT s2.agregation, s2.agregation_label"
            . " FROM sys_definition_time_agregation s1, sys_definition_time_agregation s2"
            . " WHERE s1.agregation = '" . $ta . "' AND s1.source_default = s2.agregation";

    $row = $db->getRow($sql);

    return array('name' => $row['agregation'], 'label' => $row['agregation_label']);
}

// 24/02/2009 - Modif. benoit : correction de la fonction ci-dessous

/**
 * Récupère dans un tableau la liste des nom/label des familles.
 *
 * @param int $product identifiant du produit sur lequel on recherche les familles
 * @return array liste des familles sous la forme d'un tableau où les clées sont les noms des familles et les valeurs les labels
 */
function getFamilyList($product = '') {
    // 31/01/2011 BBX
    // On remplace new DatabaseConnection() par Database::getConnection()
    // BZ 20450
    $db = Database::getConnection($product);

    $sql = " SELECT family, family_label FROM sys_definition_categorie"
            . " WHERE on_off=1 AND visible=1"
            . " ORDER BY rank";

    $row = $db->getAll($sql);

    $families = array();

    for ($i = 0; $i < count($row); $i++) {
        $families[$row[$i]['family']] = (($row[$i]['family_label'] != "") ? $row[$i]['family_label'] : $row[$i]['family']);
    }

    return $families;
}

/**
 * Retourne l'identifiant unique acurio en fonction avec le prefixe passé en paramètre.
 * @param $prefixe : préfixe qui sera mis avant l'identifiant unique.
 * @return identifiant unique.
 */
function generate_acurio_uniq_id($prefixe) {
    return(uniqid($prefixe . "_", true));
}

/**
 * Permet d'enregistrer un nouvel élément dans la table acurio_uniq_id
 * @param $uniq_id : identifiant unique.
 * @param $type_element : type de l'élément enregistré.
 */
function save_acurio_uniq_id($uniq_id, $type_element) {
    global $database_connection;

    $date = date("Ymd");
    $date_label = date("d-m-Y g:i a");

    $insert = " INSERT INTO sys_internal_id
			(internal_id, type_element, date_ajout_label, date_ajout, date_modif_label, date_modif)
			VALUES
			('$uniq_id','$type_element','$date_label','$date','$date_label','$date')
			";
    $result = pg_query($database_connection, $insert);
}

/**
 * Permet de mettre à jour la date de dernière modification d'un élément
 * en fonction de son id unique
 * @param $uniq_id : identifiant unique.
 */
function update_acurio_uniq_id($uniq_id) {
    global $database_connection;

    $date = date("Ymd");
    $date_label = date("d-m-Y g:i a");

    $update = " UPDATE sys_internal_id
			SET
					date_modif_label = '$date_label',
					date_modif = '$date'
			WHERE
					internal_id = '$uniq_id'
			";
    $result = pg_query($database_connection, $update);
}

/**
 * Retourne la colonne acurio_uniq_id d'une table
 * @param $table : table sur laquelle s'effectue la requête.
 * @param $id_val : valeur de l'identifiant.
 * @param $id_name : nom du champ identifiant.
 * @return colonne acurio_uniq_id de lta ble passée en paramètre
 */
function get_acurio_uniq_id($table, $id_val, $id_name) {
    global $database_connection;

    $query = "
			SELECT internal_id FROM $table
					WHERE $id_name='$id_val' ";
    $result = pg_query($database_connection, $query);
    $nombre_resultat = pg_num_rows($result);
    $val = "";
    if ($nombre_resultat > 0) {
        $result_array = pg_fetch_array($result, 0);
        $val = $result_array["acurio_uniq_id"];
    } else {
        echo $query;
        echo "<br><b>Error : no uniq_id for this table.</b>";
        exit;
    }
    return ($val);
}

/**
 * Retourne un tableau de session contenant la liste des modules activés de l'appli en fonction de la clef d'activation.
 * @return $_SESSION["activated_faiture"]["comment"] = 1;        >> module des commentaires sur les graph / pie / alarm / PDF et dashbord.
 */
function get_activated_faiture() {
    // 21/11/2011 BBX
    // Correction de messages "Notices" vu pendant les corrections
    if (!isset($_SESSION))
        session_start();
    $_SESSION["activated_faiture"]["comment"] = 1;
}

/**
 * Fonction qui retourne la ganularité temporelle la plus petite (day ou hour)
 * en fonction du champs visible dans la table sys_defin ition_time_aggregation
 *
 *  maj 20/07/2010 - MPR : Ajout d'un paramètre facultatif $_condition pour ajouter ou non une condition supplémentaire
 * @param integer $id_prod : ID du produit (falculatif)
 * @param string $_condition : condition supplémentaire (falcultatif)
 * @return string $ta_min : TA minimum déployée
 */
function get_ta_min($id_prod = "", $_condition = "") {
    // maj 28/05/2010 MPR : Utilisation de DataBase au lieu de $database_connection
    //                      Ajout du produit en paramètre d'entrée
    $database = Database::getConnection($id_prod);

    $query = "select agregation from sys_definition_time_agregation  where visible=1 {$_condition} order by agregation_rank asc limit 1";
    $ta_min = $database->getOne($query);

    return $ta_min;
}

/**
 * retourne la liste des home network sous la forme d'un tableau.
 * @param $famly : nom de la famille
 * @return hn_options[valeur@axe gt id] = élément à afficher dans un select
 * axe gt id sont définis dans la table SYS_DEFINITION_GT_AXE
 */
function get_hn($family) {
    global $database_connection;

    $hn_options = array(); // tableau retourné.
    $gt_axe = array();

    // On récupère les valeurs axe_gt_id de la famille.
    $query = "SELECT * FROM sys_definition_gt_axe WHERE family = '$family'";
    $result = pg_query($database_connection, $query);
    if (pg_num_rows($result) > 0) {
        while ($row = pg_fetch_array($result)) {
            $gt_axe[$row['external_reference']] = $row['axe_gt_id'];
        }
    } else {
        echo "<b><u>Error :</u> DATA are not configured for this family ($family).<br>Please contact your application administrator.</b>";
        exit;
    }


    // All network.
    $query = 'SELECT DISTINCT all_network,network_label FROM sys_ref_home_network_labels ORDER BY all_network asc,network_label asc';
    $result = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($result)) {
        if ($row['all_network']) {
            $hn_options[$row['all_network'] . '@' . $gt_axe['all_network'] . '@' . $row['all_network']] = $row['all_network'];
        }
    }

    // Séparateur.
    $hn_options['sep1'] = '--------';

    // Corporate.
    $query = 'SELECT DISTINCT corporate FROM sys_ref_home_network_labels ORDER BY corporate asc';
    $result = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($result)) {
        if ($row['corporate']) {
            $hn_options[$row['corporate'] . '@' . $gt_axe['corporate'] . '@' . $row['corporate']] = $row['corporate'];
        }
    }

    // Séparateur.
    $hn_options['sep2'] = '--------';

    // Country.
    $query = 'SELECT DISTINCT country FROM sys_ref_home_network_labels ORDER BY country asc';
    $result = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($result)) {
        if ($row['country']) {
            $hn_options[$row['country'] . '@' . $gt_axe['country'] . '@' . $row['country']] = $row['country'];
        }
    }

    // Séparateur.
    $hn_options['sep3'] = '--------';

    // Country.
    $query = 'SELECT DISTINCT network,network_label FROM sys_ref_home_network_labels ORDER BY network_label asc';
    $result = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($result)) {
        if ($row['network']) {
            $hn_options[$row['network'] . '@' . $gt_axe['network'] . '@' . $row['network_label']] = $row['network_label'] . ' - ' . $row['network'];
        }
    }

    return $hn_options;
}

// idem que la fonction get_hn mais avec une structure différente.
function get_hn_2($family) {
    global $database_connection;

    $hn_options = array(); // tableau retourné.
    $gt_axe = array();

    // On récupère les valeurs axe_gt_id de la famille.
    $query = "SELECT * FROM sys_definition_gt_axe WHERE family = '$family'";
    $result = pg_query($database_connection, $query);
    if (pg_num_rows($result) > 0) {
        while ($row = pg_fetch_array($result)) {
            $gt_axe[$row['external_reference']] = $row['axe_gt_id'];
        }
    } else {
        echo "<b><u>Error :</u> DATA are not configured for this family ($family).<br>Please contact your application administrator.</b>";
        exit;
    }


    // All network.
    $query = 'SELECT DISTINCT all_network,network_label FROM sys_ref_home_network_labels ORDER BY all_network asc,network_label asc';
    $result = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($result)) {
        if ($row['all_network']) {
            $hn_options[$row['all_network'] . '@' . $gt_axe['all_network']] = $row['all_network'];
        }
    }

    // Séparateur.
    $hn_options['sep1'] = '--------';

    // Corporate.
    $query = 'SELECT DISTINCT corporate FROM sys_ref_home_network_labels ORDER BY corporate asc';
    $result = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($result)) {
        if ($row['corporate']) {
            $hn_options[$row['corporate'] . '@' . $gt_axe['corporate']] = $row['corporate'];
        }
    }

    // Séparateur.
    $hn_options['sep2'] = '--------';

    // Country.
    $query = 'SELECT DISTINCT country FROM sys_ref_home_network_labels ORDER BY country asc';
    $result = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($result)) {
        if ($row['country']) {
            $hn_options[$row['country'] . '@' . $gt_axe['country']] = $row['country'];
        }
    }

    // Séparateur.
    $hn_options['sep3'] = '--------';

    // Country.
    $query = 'SELECT DISTINCT network,network_label FROM sys_ref_home_network_labels ORDER BY network_label asc';
    $result = pg_query($database_connection, $query);
    while ($row = pg_fetch_array($result)) {
        if ($row['network']) {
            $hn_options[$row['network'] . '@' . $gt_axe['network']] = $row['network_label'] . ' - ' . $row['network'];
        }
    }

    return $hn_options;
}

/**
 * Permet de connaître la liste des TA à calculer.
 * @param $offset_day : nombre de jours
 * @return tableau avec le fomat suivant : $time_to_calculate["hour"] = 22... > $time_to_calculate[time_agregation] = value
 */
function get_time_to_calculate($offset_day) {
    // 23/11/2012 BBX
    // BZ 30587 : utilisation de la classe date + paramètre day_to_compute
    $hour = get_sys_global_parameters("hour_to_compute");
    $day = get_sys_global_parameters("day_to_compute");
    if (empty($day))
        $day = Date::getDayFromDatabaseParameters($offset_day);

    $compute_mode = get_sys_global_parameters("compute_mode"); // Valeurs possibles : hourly ou daily.
    $compute_processing = get_sys_global_parameters("compute_processing"); // Valeurs possibles : hour ou day.
    // 25/02/2008 - Modif. benoit : ajout de la variable '$compute_switch' provenant de 'sys_global_parameters' et indiquant si l'on a switché du mode "hourly" à "daily"
    $compute_switch = get_sys_global_parameters('compute_switch');

    /*
      En compute_mode = daily : 1 compute / jour pour les données >= hour.

      En compute_mode = hourly:
      - si compute_processing = day : 1 compute / jour pour les données > hour.
      - si compute_processing = hour : 24 compute / jours pour les données hour.
     */

    if ($compute_mode == "daily") {
        $time_to_calculate["day"] = $day;
        $time_to_calculate["day_bh"] = $day;

        // 25/02/2008 - Modif. benoit : suivant la valeur de '$compute_switch', on affecte une valeur arbitraire d'heure (23h) ou on utilise une liste d'heures

        if (($compute_switch == "hourly") && ($hour != "")) {

            $sep_hours = get_sys_global_parameters('sep_axe3');

            if (strpos($hour, $sep_hours) === false) { // Une seule valeur horaire dans "hour_to_compute"
                $time_to_calculate["hour"] = $hour;
            } else { // Liste d'heures
                $time_to_calculate["hour"] = explode($sep_hours, $hour);
            }
        } else {
            $time_to_calculate["hour"] = $day . "23";
        }

        // 26/02/2008 - Modif. benoit : pour detecter le changement de week, on compare l'offset day en base avec celui du jour courant et non plus avec offset day - 1
        // 30/07/2009 BBX : modification des conditions de calcul week
        // 21/09/2009 BBX : modification des conditions de calcul week. BZ 11531
        // Condition 1 : la semaine de l'offset day est différente de la semaine courante 
        //	=> intégration de données de la semaine précédente (reprise de données, réintégration de données, intégration du jour précédent étant le dernier jour de la semaine précédente)
        // Condition 2 : le jour des données à intégrer correspond au dernier jour de la semaine des données à intégrer
        // 23/11/2012 BBX
        // BZ 30587 : utilisation du day plutôt que l'offset day
        $weekFromOffsetDay = Date::getWeekFromDay($day);
        $currentWeek = Date::getWeekFromDatabaseParameters('0');
        $lastDayOfWeek = Date::getLastDayFromWeek($weekFromOffsetDay, get_sys_global_parameters('week_starts_on_monday'));
        $dayFromOffsetDay = Date::getDayFromDatabaseParameters($offset_day);

        // Test des conditions
        if (($weekFromOffsetDay != $currentWeek) || ($lastDayOfWeek == $dayFromOffsetDay)) {
            $time_to_calculate["week"] = $weekFromOffsetDay;
            $time_to_calculate["week_bh"] = $weekFromOffsetDay;
        }

        // 26/02/2008 - Modif. benoit : même modification pour le mois		
        // 30/07/2009 BBX : modification des conditions de calcul month
        // 21/09/2009 BBX : modification des conditions de calcul month. BZ 11531
        // Condition 1 : le mois de l'offset day est différente du mois courant
        //	=> intégration de données du mois précédent (reprise de données, réintégration de données, intégration du jour précédent étant le dernier jour du mois précédent)
        // Condition 2 : le jour des données à intégrer correspond au dernier jour du mois des données à intégrer
        $monthFromOffsetDay = Date::getMonthFromDatabaseParameters($offset_day);
        $currentMonth = Date::getMonthFromDatabaseParameters('0');
        $lastDayOfMonth = Date::getLastDayFromMonth($monthFromOffsetDay);

        // Test des conditions
        if (($monthFromOffsetDay != $currentMonth) || ($lastDayOfMonth == $dayFromOffsetDay)) {
            $time_to_calculate["month"] = $monthFromOffsetDay;
            $time_to_calculate["month_bh"] = $monthFromOffsetDay;
        }
    } else { // compute_mode == "hourly"
        if ($compute_processing == "hour") {
            // 25/02/2008 - Modif. benoit : on gère à présent les listes d'heures dans 'hour_to_compute'

            $sep_hours = get_sys_global_parameters('sep_axe3');

            if (strpos($hour, $sep_hours) === false) { // Une seule valeur horaire dans "hour_to_compute"
                $time_to_calculate["hour"] = $hour;
            } else { // Liste d'heures
                $time_to_calculate["hour"] = explode($sep_hours, $hour);
            }
        } else {
            $time_to_calculate["day"] = $day;
            $time_to_calculate["day_bh"] = $day;

            // 26/02/2008 - Modif. benoit : pour detecter le changement de week, on compare l'offset day en base avec celui du jour courant et non plus avec offset day - 1
            // 30/07/2009 BBX : modification des conditions de calcul week
            // 21/09/2009 BBX : modification des conditions de calcul week. BZ 11531
            // Condition 1 : la semaine de l'offset day est différente de la semaine courante 
            //	=> intégration de données de la semaine précédente (reprise de données, réintégration de données, intégration du jour précédent étant le dernier jour de la semaine précédente)
            // Condition 2 : le jour des données à intégrer correspond au dernier jour de la semaine des données à intégrer
            // 23/11/2012 BBX
            // BZ 30587 : utilisation du day plutôt que l'offset day
            $weekFromOffsetDay = Date::getWeekFromDay($day);
            $currentWeek = Date::getWeekFromDatabaseParameters('0');
            $lastDayOfWeek = Date::getLastDayFromWeek($weekFromOffsetDay, get_sys_global_parameters('week_starts_on_monday'));
            $dayFromOffsetDay = Date::getDayFromDatabaseParameters($offset_day);

            // Test des conditions
            if (($weekFromOffsetDay != $currentWeek) || ($lastDayOfWeek == $dayFromOffsetDay)) {
                $time_to_calculate["week"] = $weekFromOffsetDay;
                $time_to_calculate["week_bh"] = $weekFromOffsetDay;
            }

            // 26/02/2008 - Modif. benoit : même modification pour le mois		
            // 30/07/2009 BBX : modification des conditions de calcul month
            // 21/09/2009 BBX : modification des conditions de calcul month. BZ 11531
            // Condition 1 : le mois de l'offset day est différente du mois courant
            //	=> intégration de données du mois précédent (reprise de données, réintégration de données, intégration du jour précédent étant le dernier jour du mois précédent)
            // Condition 2 : le jour des données à intégrer correspond au dernier jour du mois des données à intégrer
            $monthFromOffsetDay = Date::getMonthFromDatabaseParameters($offset_day);
            $currentMonth = Date::getMonthFromDatabaseParameters('0');
            $lastDayOfMonth = Date::getLastDayFromMonth($monthFromOffsetDay);

            // Test des conditions
            if (($monthFromOffsetDay != $currentMonth) || ($lastDayOfMonth == $dayFromOffsetDay)) {
                $time_to_calculate["month"] = $monthFromOffsetDay;
                $time_to_calculate["month_bh"] = $monthFromOffsetDay;
            }
        }
    }

    $debug = false;
    if ($debug) {
        echo "
			<div class='debug'>
				<div class='function_call'>get_time_to_calculate(offset_day=<strong>$offset_day</strong>)</div>
				edw_day=<strong>$edw_day</strong><br/>
				Compute mode=<strong>$compute_mode</strong><br/>
				Compute processing=<strong>$compute_processing</strong><br/>
				Contenu du tableau Time To Calculate :<br/>
			<table>";
        foreach ($time_to_calculate as $k => $v)
            echo "\n	<tr><td>$k</td><td><strong>$v</strong></td></tr>";
        echo "\n</table>\n</div>";
    }

    return $time_to_calculate;
}

/**
 * 30/07/2009 BBX : 
 * Récupère le dernier jour intégré
 * @param string $product = id du produit en cours
 * @return string : dernier jour intégré (YYYYMMDD)
 */
function getLastIntegratedDay($product = '') {
    // 31/01/2011 BBX
    // On remplace new DatabaseConnection() par Database::getConnection()
    // BZ 20450    
    $database = Database::getConnection($product);

    // 15/09/2011 BBX
    // BZ 22802 : Depuis que la collecte est décoréllée du retrieve
    // on ne peut plus regarder simplement dans la table des jours collectés
    // Il faut aller regarder directement dans les données
    $queryTable = "SELECT
            (SELECT edw_group_table FROM sys_definition_group_table WHERE id_ligne = 1) || '_raw_' ||
            (SELECT agregation
            FROM sys_definition_network_agregation a, sys_definition_categorie c
            WHERE a.family = c.family
            AND c.rank = 1
            ORDER BY agregation_rank
            LIMIT 1) || '_day'";
    $dataTable = $database->getOne($queryTable);

    $queryData = "SELECT MAX(day) FROM $dataTable
        WHERE day NOT IN (SELECT DISTINCT day FROM sys_to_compute)";

    /*
      $query = "SELECT MAX(day) as last_day FROM sys_flat_file_uploaded_list_archive
      WHERE day NOT IN (SELECT DISTINCT day FROM sys_to_compute)
      AND day != ".Date::getDayFromDatabaseParameters(); */

    return $database->getOne($queryData);
}

// 15/12/2008 - Modif. benoit : ajout de la fonction ci-dessous
// 09/06/2009 - MPR : Ajout d'une valeur par défault pour la famille
/**
 * Retourne le label d'une na
 *
 * @param string $na nom de la na
 * @param string $family famille de la na
 * @param mixed $product produit auquel appartient la na (par défaut, le produit courant)
 *
 * @return string le label de la na
 */
function getNetworkLabel($na, $family = '', $product = '') {
    // 31/01/2011 BBX
    // On remplace new DatabaseConnection() par Database::getConnection()
    // BZ 20450
    $db = Database::getConnection($product);

    $sql = "SELECT agregation_label FROM sys_definition_network_agregation WHERE agregation = '" . $na . "'";

    if ($family !== '') {
        $sql .= " AND family = '" . $family . "'";
    }
    return $db->getOne($sql);
}

// A SUPPRIMER, mise en commentaire le 03/01/2008 christophe
/**
 * Retourne la valeur du champ ta_rattachement de la table sys_definition_time_aggregation
 * @param $ta : nom de la time aggregation
 * @return
 */
/*
  function getTaAttachment($ta){

  global $database_connection;

  $query = " SELECT tagetTaAttachment_rattachement FROM sys_definition_time_agregation WHERE agregation='$ta' ";
  //echo $query."<br>";
  $result = pg_query($database_connection,$query);
  $result_array= pg_fetch_array($result, 0);
  $val = ($result_array["ta_rattachement"] == "") ? $ta : $result_array["ta_rattachement"];
  return ($val);

  }
 */

/**
 * Retourne le label d'un type d'alarme
 * @param $type : type de l'alarme
 * @return label du type de l'alarme
 */
function getAlarmTypeLabel($type) {
    switch ($type) {
        case "static" :
            $type_label = "Static Alarm";
            break;
        case "dyn_alarm" :
            $type_label = "Dynamic Alarm";
            break;
        case "top-worst" :
            $type_label = "Top / Worst Cell List";
            break;
        default :
            $type_label = "Alarm";
    }
    return ($type_label);
}

/**
 * Retourne le label d'une time aggregation, si le champ label est vide, on retourne la ta
 * @param $ta : nom de la time aggregation.
 * @return label de la TA.
 */
function getTaLabel($ta) {

    global $database_connection;

    $query = " SELECT  agregation_label FROM sys_definition_time_agregation WHERE agregation='$ta' ";
    $result = pg_query($database_connection, $query);
    $nombre_resultat = pg_num_rows($result);
    if ($nombre_resultat > 0) {
        $result_array = pg_fetch_array($result, 0);
        $ta_label = ($result_array["agregation_label"] != "") ? $result_array["agregation_label"] : $ta;
    } else {
        $ta_label = "ND";
    }

    return ($ta_label);
}

/**
 * Retourne une partie de la clause where d'une requpete pour la condition sur les TA dans le calcul des alarmes.
 */
function getTaQueryForCompute($ta, $ta_value) {

    $compute_mode = get_sys_global_parameters("compute_mode"); // Valeurs possibles : hourly ou daily.
    $compute_processing = get_sys_global_parameters("compute_processing"); // Valeurs possibles : hour ou day.
    $chaine = " ";
    // Cas où l'on doit faire un compute de toutes les heures de la journée.
    if ($compute_mode == "daily" && $ta == "hour") {
        // On nous passe une ta_value 2005080923. On enlève donc les 2 derniers caractères afin de pouvoir afficher
        // toutes les heures de la journée.
        $new_ta_value = substr($ta_value, 0, -2);
        $chaine = "day = $new_ta_value ";
        // NB
        // On utilise le day comme référence pour sélectionner toutes les heures de la journée car  hour like '$new_ta_value%' n'utilise pas l'index de pgsql.
    } else {
        $chaine = "$ta = $ta_value ";
    }
    return $chaine;
}

// 05/02/2009 - Modif. benoit : modification de la fonction 'getTaValueToDisplay()' avec l'ajout du parametre '$separator' afin de pouvoir spécifier le séparateur à utiliser pour le formatage de la date

/**
 * Retourne la valeur de la time_aggregation_value à afficher pour l'utilisateur.
 * @param $ta : nom de la TA (hour, day...).
 * @param $ta_value : valeur de la TA (20060719...).
 * @return string à afficher.
 */
function getTaValueToDisplay($ta, $ta_value, $separator = "-") {

    switch ($ta) {
        case "hour" :
            $ta_value_to_display = substr($ta_value, 6, 2) . $separator . substr($ta_value, 4, 2) . $separator . substr($ta_value, 0, 4) . " " . substr($ta_value, 8, 2) . ":00";
            break;

        case "day" :
        case "day_bh" :
            $ta_value_to_display = substr($ta_value, 6, 2) . $separator . substr($ta_value, 4, 2) . $separator . substr($ta_value, 0, 4);
            break;

        case "week" :
        case "week_bh" :
            $ta_value_to_display = "W" . substr($ta_value, 4, 2) . $separator . substr($ta_value, 0, 4);
            break;

        case "month" :
        case "month_bh" :
            $ta_value_to_display = substr($ta_value, 4, 2) . $separator . substr($ta_value, 0, 4);
            break;
    }
    return($ta_value_to_display);
}

/**
 * Retourne la valeur de la time_aggregation_value à afficher pour l'utilisateur avec un séparateur passé en paramètre.
 * @param $ta : nom de la TA.
 * @param $ta_value : valeur de la TA.
 * @param $separator : séparateur à utiliser pour la date.
 * @return string à afficher.
 */
function getTaValueToDisplayV2($ta, $ta_value, $separator) {

    switch ($ta) {
        case "hour" :
            $compute_mode = get_sys_global_parameters("compute_mode");
            if ($compute_mode == "daily") {
                $ta_value_to_display = substr($ta_value, 6, 2) . $separator . substr($ta_value, 4, 2) . $separator . substr($ta_value, 0, 4);
            } else {
                $ta_value_to_display = substr($ta_value, 6, 2) . $separator . substr($ta_value, 4, 2) . $separator . substr($ta_value, 0, 4) . $separator . substr($ta_value, 8, 2) . $separator . "00";
            }
            break;
        case "day" :
        case "day_bh" :
            $ta_value_to_display = substr($ta_value, 6, 2) . $separator . substr($ta_value, 4, 2) . $separator . substr($ta_value, 0, 4);
            break;
        case "week" :
        case "week_bh" :
            $ta_value_to_display = "W" . substr($ta_value, 4, 2) . $separator . substr($ta_value, 0, 4);
            break;
        case "month" :
        case "month_bh" :
            $ta_value_to_display = substr($ta_value, 4, 2) . $separator . substr($ta_value, 0, 4);
            break;
    }
    return($ta_value_to_display);
}

// 27/01/2009 - Modif. benoit : création de la fonction ci-dessous

/**
 * Retourne la valeur d'une ta à partir de sa valeur formatée
 *
 * @param string $ta nom de la ta
 * @param int $ta_value valeur formatée de la ta
 * @param string séparateur utilisée dans la date formatée
 *
 * @return int valeur de la ta non formatée
 */
function getTaValueToDisplayReverse($ta, $ta_value, $separator) {
    $ta_value_to_display = "";

    switch ($ta) {
        case "hour" :
            $tmp = explode($separator, $ta_value);
            $tmp2 = explode(" ", $tmp[2]);
            $tmp3 = explode(":", $tmp2[1]);
            $ta_value_to_display = $tmp2[0] . $tmp[1] . $tmp[0] . $tmp3[0];
            break;

        case "day" :
        case "day_bh" :
            $tmp = explode($separator, $ta_value);
            $ta_value_to_display = $tmp[2] . $tmp[1] . $tmp[0];
            break;

        case "week" :
        case "week_bh" :

            if (strpos($ta_value, $separator) === false) {
                $separator = "-";
            }

            $tmp = explode($separator, $ta_value);
            $ta_value_to_display = $tmp[1] . str_replace("W", "", $tmp[0]);
            break;

        case "month" :
        case "month_bh" :
            $tmp = explode($separator, $ta_value);
            $ta_value_to_display = $tmp[1] . $tmp[0];
            break;
    }

    return($ta_value_to_display);
}

/**
 * idem fontion getTaValueToDisplayV2 mais retourne la date
 * au format anglais : 2007_08_23...
 */
function getTaValueToDisplayV2_en($ta, $ta_value, $separator) {

    switch ($ta) {

        case "hour" :
            $compute_mode = get_sys_global_parameters("compute_mode");
            if ($compute_mode == "daily") {
                $ta_value_to_display = substr($ta_value, 0, 4) . $separator . substr($ta_value, 4, 2) . $separator . substr($ta_value, 6, 2);
            } else {
                $ta_value_to_display = substr($ta_value, 0, 4) . $separator . substr($ta_value, 4, 2) . $separator . substr($ta_value, 6, 2);
                $ta_value_to_display .= $separator . substr($ta_value, 8, 2) . $separator . "00";
            }
            break;
        case "day" :
        case "day_bh" :
            $ta_value_to_display = substr($ta_value, 0, 4) . $separator . substr($ta_value, 4, 2) . $separator . substr($ta_value, 6, 2);
            break;
        case "week" :
        case "week_bh" :
            $ta_value_to_display = substr($ta_value, 0, 4) . $separator . "W" . substr($ta_value, 4, 2);
            break;
        case "month" :
        case "month_bh" :
            $ta_value_to_display = substr($ta_value, 0, 4) . $separator . substr($ta_value, 4, 2);
            break;
    }
    return($ta_value_to_display);
}

/*
 * Fonction qui retourne la version de l'application enregistrée dans SYS_GLOBAL_PARAMETERS.
 */

function get_version() {

    global $database_connection;

    $query = " select value from sys_global_parameters where parameters='version' ";
    $result = pg_query($database_connection, $query);
    $result_array = pg_fetch_array($result, 0);
    return ($result_array["value"]);
}

// 14/11/2008 - Modif. benoit : la fonction ci-dessous est dépréciée, utiliser 'GetAxe3($family, $product = "")'

/**
 * Fonction qui à partir d'un famille détermine si cette famille possède un 3ème axe
 *
 * 	09:58 13/07/2009 GHX
 * 		- Ajout du deuxieme paramètre idProduct
 *
 * @param :$family
 * @param $idProduct (default null)
 * @return : bool
 */
function get_axe3($family, $idProduct = '') {
    // 31/01/2011 BBX
    // On remplace new DatabaseConnection() par Database::getConnection()
    // BZ 20450
    $db = Database::getConnection($idProduct);

    /*
      Au 02/04/2007
      Maintenant quand une famille a une 3 ème axe,
      il existe pour cette famille au moins 1 niveau d'aggrégation
      dont la colonne axe de la table sys_definition_network_agregation
      est égal à 3.
      (le reste des colonnes est NULL)
     */
    $query = "
		SELECT agregation
			FROM sys_definition_network_agregation
			WHERE  family = '$family'
			AND axe = 3
	";
    $result = $db->execute($query);
    if ($db->getNumRows() > 0) {
        return true;
    }

    return false;
}

/**
 * Fonction qui retourne la famille du kpi en fonction de son id
 *
 * string $id : l'identifiant du raw
 * @param DatabaseConnection $database : intance de DatabaseConnection
 * @return string
 */
function getFamilyFromGroupTable($group_table, $product) {

    $module = get_sys_global_parameters("module", "", $product);
    $family = str_replace(array("edw_" . $module . "_", "_axe1"), "", $group_table);

    return $family;
}

/**
 * Fonction qui à partir d'un identifiant de group table retourne les informations sur l'axe du group table
 * @param int $id_gt_value : identifiant du group table
 * @param int $product : id du product
 * @return : $axe_information qui contient toutes les données ou FALSE si aucun axe n'est trouve
 */
function get_axe3_information($id_gt_value, $product = 0) {
    // 31/01/2011 BBX
    // On remplace new DatabaseConnection() par Database::getConnection()
    // BZ 20450
    $db = Database::getConnection($product);

    $query = "
	    	SELECT axe_gt_id, axe_index, axe_index_label, axe_label, axe_type, family, axe_order, id_group_table
		FROM sys_definition_gt_axe
		WHERE id_group_table='$id_gt_value' ";

    return $db->getrow($query);
}

/*
 * Fonction qui a partir d'une week YYYYWW retourne le dernier jour de la semaine.
 * prends en compte le jour de début de semaine (Dimanche ou lundi) présent dans sys_global_parameters
 * @param :$week
 *
 */

function GetLastDayFromAcurioWeek($week) {
    // 12/12/2012 BBX
    // BZ 30489 : utilisation de la classe Date
    return Date::getLastDayFromWeek($week);
}

/**
 * Retourne le dernier jour d'un mois donné. On tient compte dans la recherche de l'année du mois de recherche
 *
 * @param int $month mois dont on cherche le dernier jour
 * @param int $year année du mois de recherche
 * @param string $return_sep separateur utilisé pour formater la date de sortie
 * @return string valeur du dernier jour du mois
 */
function getLastDayFromMonth($month, $year, $return_sep = '') {
    return date('Y' . $return_sep . 'm' . $return_sep . 'd', strtotime('-1 second', strtotime('+1 month', strtotime($month . '/01/' . $year . ' 00:00:00'))));
}

/*
 * Fonction qui a partir d'un jour YYYYMMDD retourne la week en format YYYYWW
 * prends en compte le jour de début de semaine (Dimanche ou lundi) présent dans sys_global_parameters
 * @param :$day
 */

function GetweekFromAcurioDay($day) {
    // 12/12/2012 BBX
    // BZ 30489 : utilisation de la classe Date
    return Date::getWeekFromDay($day);
}

/*
 * Fonction qui a partir d'un jour YYYYMMDD retourne la week en format W26-2005
 * prends en compte le jour de début de semaine (Dimanche ou lundi) présent dans sys_global_parameters
 * @param :$day
 */

function GetweekFromAcurioDay2($day) {
    // 12/12/2012 BBX
    // BZ 30489 : utilisation de la classe Date
    $week = Date::getWeekFromDay($day);
    return 'W' . substr($week, -2) . '-' . substr($week, 0, 4);
}

function GetOffsetDayFromAcurioDay($date) {
    // 12/12/2012 BBX
    // BZ 30489 : utilisation de la classe Date
    return Date::getOffsetDayFromDay($date);
}

function CapacityErlangB($nbts, $hr) {
    if ($hr == true) {
        $nbts = ceil($nbts * 1.5);
    }

    $max_capa = 0;
    switch ($nbts) {
        case 0 : $max_cap = 0;
            break;
        case 1 : $max_cap = 0.020601;
            break;
        case 2 : $max_cap = 0.223601;
            break;
        case 3 : $max_cap = 0.602401;
            break;
        case 4 : $max_cap = 1.092401;
            break;
        case 5 : $max_cap = 1.657301;
            break;
        case 6 : $max_cap = 2.276001;
            break;
        case 7 : $max_cap = 2.935601;
            break;
        case 8 : $max_cap = 3.627201;
            break;
        case 9 : $max_cap = 4.344901;
            break;
        case 10 : $max_cap = 5.097601;
            break;
        case 11 : $max_cap = 5.857601;
            break;
        case 12 : $max_cap = 6.627601;
            break;
        case 13 : $max_cap = 7.417601;
            break;
        case 14 : $max_cap = 8.217601;
            break;
        case 15 : $max_cap = 9.027601;
            break;
        case 16 : $max_cap = 9.847601;
            break;
        case 17 : $max_cap = 10.6676;
            break;
        case 18 : $max_cap = 11.5076;
            break;
        case 19 : $max_cap = 12.3476;
            break;
        case 20 : $max_cap = 13.1976;
            break;
        case 21 : $max_cap = 14.0476;
            break;
        case 22 : $max_cap = 14.9076;
            break;
        case 23 : $max_cap = 15.7776;
            break;
        case 24 : $max_cap = 16.6476;
            break;
        case 25 : $max_cap = 17.5176;
            break;
        case 26 : $max_cap = 18.3976;
            break;
        case 27 : $max_cap = 19.2776;
            break;
        case 28 : $max_cap = 20.1676;
            break;
        case 29 : $max_cap = 21.0576;
            break;
        case 30 : $max_cap = 21.9476;
            break;
        case 31 : $max_cap = 22.8376;
            break;
        case 32 : $max_cap = 23.7376;
            break;
        case 33 : $max_cap = 24.6376;
            break;
        case 34 : $max_cap = 25.5476;
            break;
        case 35 : $max_cap = 26.4476;
            break;
        case 36 : $max_cap = 27.3576;
            break;
        case 37 : $max_cap = 28.2676;
            break;
        case 38 : $max_cap = 29.1776;
            break;
        case 39 : $max_cap = 30.0976;
            break;
        case 40 : $max_cap = 31.0076;
            break;
        case 41 : $max_cap = 31.9276;
            break;
        case 42 : $max_cap = 32.8476;
            break;
        case 43 : $max_cap = 33.7776;
            break;
        case 44 : $max_cap = 34.6976;
            break;
        case 45 : $max_cap = 35.6176;
            break;
        case 46 : $max_cap = 36.5476;
            break;
        case 47 : $max_cap = 37.4776;
            break;
        case 48 : $max_cap = 38.4076;
            break;
        case 49 : $max_cap = 39.3376;
            break;
        case 50 : $max_cap = 40.2676;
            break;
        case 51 : $max_cap = 41.2076;
            break;
        case 52 : $max_cap = 42.1376;
            break;
        case 53 : $max_cap = 43.0776;
            break;
        case 54 : $max_cap = 44.0076;
            break;
        case 55 : $max_cap = 44.9476;
            break;
        case 56 : $max_cap = 45.8876;
            break;
        case 57 : $max_cap = 46.8276;
            break;
        case 58 : $max_cap = 47.7776;
            break;
        case 59 : $max_cap = 48.7176;
            break;
        case 60 : $max_cap = 49.6576;
            break;
        case 61 : $max_cap = 50.6076;
            break;
        case 62 : $max_cap = 51.5476;
            break;
        case 63 : $max_cap = 52.4976;
            break;
        case 64 : $max_cap = 53.4476;
            break;
        case 65 : $max_cap = 54.3876;
            break;
        case 66 : $max_cap = 55.3376;
            break;
        case 67 : $max_cap = 56.2876;
            break;
        case 68 : $max_cap = 57.2376;
            break;
        case 69 : $max_cap = 58.1876;
            break;
        case 70 : $max_cap = 59.1476;
            break;
        case 71 : $max_cap = 60.0976;
            break;
        case 72 : $max_cap = 61.0476;
            break;
        case 73 : $max_cap = 62.0076;
            break;
        case 74 : $max_cap = 62.9576;
            break;
        case 75 : $max_cap = 63.9176;
            break;
        case 76 : $max_cap = 64.8676;
            break;
        case 77 : $max_cap = 65.8276;
            break;
        case 78 : $max_cap = 66.7876;
            break;
        case 79 : $max_cap = 67.7476;
            break;
        case 80 : $max_cap = 68.7076;
            break;
        case 81 : $max_cap = 69.6576;
            break;
        case 82 : $max_cap = 70.6176;
            break;
        case 83 : $max_cap = 71.5876;
            break;
        case 84 : $max_cap = 72.5476;
            break;
        case 85 : $max_cap = 73.5076;
            break;
        case 86 : $max_cap = 74.4676;
            break;
        case 87 : $max_cap = 75.4276;
            break;
        case 88 : $max_cap = 76.3976;
            break;
        case 89 : $max_cap = 77.3576;
            break;
        case 90 : $max_cap = 78.3176;
            break;
        case 91 : $max_cap = 79.2876;
            break;
        case 92 : $max_cap = 80.2476;
            break;
        case 93 : $max_cap = 81.2176;
            break;
        case 94 : $max_cap = 82.1776;
            break;
        case 95 : $max_cap = 83.1476;
            break;
        case 96 : $max_cap = 84.1176;
            break;
        case 97 : $max_cap = 85.0876;
            break;
        case 98 : $max_cap = 86.0476;
            break;
        case 99 : $max_cap = 87.0176;
            break;
        case 100 : $max_cap = 87.9876;
            break;
    }

    return $max_cap;
}

/**
 * Retourne le temps ecoule depuis l'origine des temps Unix (4 decimales apres les secondes):
 */
function getmicrotime() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float) $usec + (float) $sec);
}

/**
 * Renvoie une date day par rapport à un offset du jour
 * @param $offset
 * @return returns date(minus offset) with format DD.MM.YYYY
 */
function getDay($offset) {
    // 12/12/2012 BBX
    // BZ 30489 : utilisation de la classe Date
    return Date::getDayFromDatabaseParameters($offset);
}

/**
 * Renvoie une date day  par rapport à un offset du jour pour Motorola
 * @param $offset
 * @return returns date(minus offset) with format DD.MM.YYYY
 */
function getDayMotorola($offset) {
    global $database_connection;
    // trigger_error("L'offset de getweek : $offset_day");
    if (trim($offset) == '') {
        $offset = 0;
    }
    $sql = "select to_char(now() - ' $offset days'::interval,'DD-MM-YYYY')";
    $result = pg_query($database_connection, $sql);
    if (pg_last_error() != '') {
        trigger_error(pg_last_error() . " " . $sql . "\n");
    } else {
        if (pg_num_rows($result) == 1) {
            $row = pg_fetch_array($result, 0);
            return $row[0];
        }
    }
    return;
}

//fin function getday

/**
 * Fonction obsolète laissée pour compatibilité. Elle est mappée vers la nouvelle
 *
 * @deprecated See Date::getHour
 * @param int $offset_hour
 * @return String Date au format YYYYMMDDHH
 */
function getHour($offset_hour) {
    return Date::getHour($offset_hour);
}

/**
 * Fonction obsolète laissée pour compatibilité. Elle est mappée vers la nouvelle
 *
 * @deprecated See Date::getHour
 * @param int $offset_hour
 * @return String Date au format HH
 */
function getHour_HH($offset_hour) {
    return Date::getHour($offset_hour, 'H');
}

function getweek($offset_day) {
    // 20/08/2009 BBX : utilisation de la nouvelle fonction pour récupérer une semaine. BZ 8626
    return Date::getWeekFromDatabaseParameters($offset_day);
}

function getmonth($offset_day) {
    // 20/09/2011 MMT Bz 23462 utilisation de la nouvelle fonction pour récupérer le mois
    return Date::getMonthFromDatabaseParameters($offset_day);
}

function getyear($offset_day) {
    /*
      $aujourdhui = getdate();
      $annee = $aujourdhui['year'];
      $numero_jour_annee_offset=$aujourdhui['yday']-$offset_day;
      $numero_jour_annee=$aujourdhui['yday'];
      $arr=getdate(strtotime("-$numero_jour_annee day"));

      //semaine commence le dimanche, on calcule donc quand tombe le 1 janvier pour avoir l'offset de la week dans le calcul

      $offset_debut_year=$arr["wday"];
      $week=$annee.ceil(($numero_jour_annee_offset+$offset_debut_year)/7+0.000001);
     */
    global $database_connection;
    // trigger_error("L'offset de getweek : $offset_day");
    if (trim($offset_day) == '') {
        $offset_day = 0;
    }
    $sql = "select to_char(now() - ' $offset_day days'::interval,'YYYY')";
    $result = pg_query($database_connection, $sql);
    if (pg_last_error() != '') {
        trigger_error(pg_last_error() . " " . $sql . "\n");
    } else {
        if (pg_num_rows($result) == 1) {
            $row = pg_fetch_array($result, 0);
            return $row[0];
        }
    }
    return;
    // return $week;
}

//fin function getweek

function edw_day_format($offset, $offset_type) { // returns date(minus offset) with format YYYYMMDD
    switch ($offset_type) {
        case "year":
            $arr = getdate(strtotime("-$offset year"));
            break;
        case "month":
            $arr = getdate(strtotime("-$offset month"));
            break;
        case "week":
            $arr = getdate(strtotime("-$offset week"));
            break;
        default:
            $arr = getdate(strtotime("-$offset day"));
            break;
    }

    $string = "";
    $string_day = $arr["mday"];
    $string_month = $arr["mon"];
    $string_year = $arr["year"];
    $string = $string_year * 10000 + $string_month * 100 + $string_day;
    return $string;
}

//fin edw_day_format
// fonction qui gère l'envoi de commentaires à l'écran et/ou dans un log file

function outdisplay($string) {
    global $global_parameters;
    global $edw_day;

    if ($global_parameters["functions_navigator_display"] == "on") {
        echo "$string<br>";
    }

    if ($global_parameters["functions_log_display"] == "on") {
        $string = "*       $string" . "\n";
        $fp = fopen($global_parameters["log_file"] . "_$edw_day.txt", 'a');
        fwrite($fp, $string);
        fclose($fp);
    }
}

/**
 * Retourne un tableau contenant la liste de tous les paramètres de sys_global_parameters
 * @return format du tableau : $tab[ nom du param] = valeur
 */
function edw_LoadParams() {
    global $global_parameters, $database_connection;
    $query = "select * from sys_global_parameters";
    $res = pg_query($database_connection, $query);
    $nombre_resultat = pg_num_rows($res);

    for ($i = 0; $i < $nombre_resultat; $i++) {
        $row = pg_fetch_array($res, $i);
        $valeur = $row["parameters"];
        $global_parameters["$valeur"] = $row["value"];
    }

    return $global_parameters;
}

/**
 * Affiche une date au format RFC 2822
 * Exemple : Thu, 21 Dec 2000 16:01:07 +0200
 */
function printdate() {
    echo "Time stamp : " . date('r') . "<BR>\n";
}

// maj 11:00 04/03/2008 maxime - On récupère tous les niveaux de criticté des messages du tracelog
function get_element_log_ast($element) {
    global $database_connection;

    $condition = ($element == 'module' ) ? "WHERE $element = lower($element)" : "";

    switch ($element) {
        case "module" :
            $query = "SELECT text FROM sys_definition_messages_display WHERE id like 'A_TRACELOG_MODULE_LABEL%' ORDER BY text asc";
            //__debug($query);
            $res = pg_query($database_connection, $query);

            $tab_tmp = array();
            while ($row = pg_fetch_array($res)) {
                $tab[] = $row['text'];
            }
            break;

        case "severity":
            $tab = array('Info', 'Critical', 'Warning');
            break;
    }

    return $tab;
}

/*
  Fonction de cryptage / décryptage d'une chaine.
  Cette fonction utilise un algorythme simple, mais avec une clé (cryptage symétrique).
  Toute la "puissance" de l'algorythme réside dans le fait que la clé est gardée secrete.

  2006-02-14        Stephane        Creation
 */

// evidement, cette chaine doit être cryptée avec Zend, sinon ça sert à rien.
$crypto_key = "la sagrada familia est a barcelone";

// on construit notre table de caracteres et de correspondance lettre <-> nombre
$chr_table = array();
// de 0 à 9
for ($i = 48; $i < 58; $i++)
    $chr_table[] = chr($i);
// de A à Z
for ($i = 65; $i < 91; $i++)
    $chr_table[] = chr($i);
// de a à z
for ($i = 97; $i < 123; $i++)
    $chr_table[] = chr($i);
// on ajoute ' ' et '@'
$chr_table[] = ' ';
$chr_table[] = '@';
// on inverse la table
$ord_table = array_flip($chr_table);

/**
 * fonction qui a une lettre associe un nombre
 */
function my_ord($chr) {
    global $ord_table;
    return $ord_table[$chr];
}

/**
 * fonction qui a un nombre associe une lettre
 */
function my_chr($int) {
    global $chr_table;
    return $chr_table[$int];
}

/**
 * récupère la liste des nom et label des raw counters.
 * la liste de noms est stockée dans la clef du tableau $counter_array
 * la liste de labels est stockée dans la valeur du tableau $counter_array
 * @param $family : nom de la famille
 * @param $product : id du produit
 */
function get_counter($family, $product = '') {
    // 31/01/2011 BBX
    // On remplace new DatabaseConnection() par Database::getConnection()
    // BZ 20450
    $db = Database::getConnection($product);
    $query = "
		SELECT distinct edw_field_name_label,edw_field_name
		FROM sys_field_reference a, sys_definition_group_table b
		WHERE b.edw_group_table=a.edw_group_table
			AND a.visible = 1 AND a.on_off=1
			AND b.family='$family'
		ORDER BY edw_field_name_label,edw_field_name";
    //echo "$query<br>";
    $resultat = $db->getall($query);
    if ($resultat) {
        foreach ($resultat as $row) {

            if ($row["edw_field_name_label"] != '')
                $counter_array[$row["edw_field_name"]] = $row["edw_field_name_label"];
            else
                $counter_array[$row["edw_field_name"]] = $row["edw_field_name"];
        }
    }
    return $counter_array;
}

/*
 * Récupère la liste des nom et label des kpi d'une famille donnée.
 * la liste de noms est stockée dans la clef du tableau $kpi_array
 * la liste de labels est stockée dans la valeur du tableau $kpi_array
 * @param $family : nom de la famille.
 * @param $product : id du produit
 * @return tableau de la forme $kpi_array[kpi_name]=kpi_label
 */

function get_kpi($family, $product = '') {
    // 31/01/2011 BBX
    // On remplace new DatabaseConnection() par Database::getConnection()
    // BZ 20450
    $db = Database::getConnection($product);
    $query = "
		SELECT distinct kpi_label, kpi_name
		FROM sys_definition_kpi a,sys_definition_group_table b
		WHERE b.edw_group_table=a.edw_group_table
			AND a.visible = 1 AND a.on_off=1
			AND b.family='$family'
		ORDER BY kpi_label,kpi_name";
    //echo "$query<br>";
    $resultat = $db->getall($query);
    if ($resultat) {
        foreach ($resultat as $row) {

            if ($row["kpi_label"] != '')
                $kpi_array[$row["kpi_name"]] = $row["kpi_label"];
            else
                $kpi_array[$row["kpi_name"]] = $row["kpi_name"];
        }
    }
    return $kpi_array;
}

/**
 * permet de définir la TA_Value correspondant à l'offset_day
 * $ta_value_selecteur permet de récupérer l'heure saisie depuis le sélecteur en mode hour.
 * lorsque $ta_value_selecteur n'est pas renseigné, on le fixe à '23'.
 */
function get_report_ta_value($ta, $ta_value_selecteur) {
    global $edw_day, $edw_week;

    // 19/06/2008 - Modif. benoit : correction du bug 6933. Dans le cas du month, la valeur de '$ta_value' n'était pas correcte (correspondait à celle du day)

    switch ($ta) {
        case 'hour':
            if ($ta_value_selecteur == '')
                $hour = '23';
            else
                $hour = substr($ta_value_selecteur, -2);
            $ta_value = $edw_day . $hour;
            break;
        case 'day':
            $ta_value = $edw_day;
            break;
        case 'week':
            $ta_value = $edw_week;
            break;
        case 'month':
            $ta_value = substr($edw_day, 0, 6);
            break;
        case 'day_bh':
            $ta_value = $edw_day;
            break;
        case 'week_bh':
            $ta_value = $edw_week;
            break;
        case 'month_bh':
            $ta_value = substr($edw_day, 0, 6);
            break;
    } // switch
    return $ta_value;
}

/**
 * Fonction qui retourne une partie du numéro de version
 * @param $nb_digits :  nombre de chiffres à afficher
 * @param $num : numéro de la version.
 */
function reduce_num_version($num, $nb_digits = 2) {
    $digits = explode('.', $num);
    return implode('.', array_slice($digits, 0, $nb_digits));
}

/** 23/07/2007  -  JL
 * Met à jour les  " Séquences "  en allant chercher la valeur maximale dans les tables concernées.
 * Ensuite la valeur du "START" de la séquence est remplacée par la valeur MAX trouvée
 */
function update_serials() {
    global $database_connection;

    //Recherche des table qui utilisent un serial avec leur table de séquence correspondante
    $query = "	SELECT t1.relname AS nom_table, t2.relname AS nom_seq
				FROM pg_class t1, pg_class t2
				WHERE t1.relkind='r' AND t2.relkind='S' AND t2.relname LIKE t1.relname || '%'
				ORDER BY nom_seq  ";
    $result = pg_query($database_connection, $query);

    //Boucle sur chaque couple : "table SEQUENCE - table NORMALE (avec serial)" pour creer et ajouter une requête de mise à jour du compteur START dans la table SEQUENCE
    while ($row = pg_fetch_array($result)) {
        $nom_table = $row['nom_table'];
        $nom_seq = $row['nom_seq'];

        //Reconstitution du champ serial
        $words_nom_seq = explode("_", $nom_seq);
        $cpt_nom_table = count(explode("_", $nom_table));
        $cpt_nom_seq = count($words_nom_seq);

        $first = $cpt_nom_table;
        $last = $cpt_nom_seq - 2;
        $field = "";
        for ($i = $first; $i <= $last; $i++) {
            $field .= $words_nom_seq[$i];
            if ($i < $last) {
                $field .= "_";
            }
        }

        //Vérification de l'éxistence du nom du champ dans la table "pg_attribute". En cas d'absence, on ne traitera pas ce champ qui provoquerait une erreur SQL
        $query2 = "	SELECT attname FROM pg_attribute
					WHERE attname = '$field'";
        $result2 = pg_query($database_connection, $query2);
        if (pg_num_rows($result2) > 0) {
            //Récupération du max pour ce champ serial et mise à jour de la valeur START dans la sequence correspondante à ce serial
            $query3 .= " SELECT setval('$nom_seq', (SELECT max($field) FROM $nom_table) ); ";
        }
    }
    return pg_query($database_connection, $query3);
}

//FIN update_serials

/** 07/08/2007 Jérémy :
 * Fonction qui retourne le nombre de familles qui sont ACTIVES et VISIBLES
 * L'intérêt est de cacher l'icône que l'on trouve dans toutes les interfaces où l'on a plusieurs familles
 */
// MaJ 20/11/2008 - SLC - ajout $product, supression $database_connection
function get_number_of_family($axe3 = false, $product = '') {
    // 31/01/2011 BBX
    // On remplace new DatabaseConnection() par Database::getConnection()
    // BZ 20450
    $db = Database::getConnection($product);

    // Si l'on travail avec l'axe 3, alors on lance la première requête qui va récupérer les familles actives et qui ont un 3ème axe, SINON on lance la 2ème requête plus générale
    if ($axe3) {
        $family_query = "
			SELECT DISTINCT t1.family_label, t2.axe
			FROM sys_definition_categorie t1, sys_definition_network_agregation t2
			WHERE t1.on_off=1 AND t1.visible = 1
			AND t1.family = t2.family
			AND t2.axe = 3";
    } else {
        $family_query = "
			SELECT * FROM sys_definition_categorie
			WHERE on_off=1 AND visible = 1
			ORDER BY rank ASC ";
    }
    $result = $db->getall($family_query);
    return sizeof($result);
}

/**
 * Renvoie la durée de l'historique en fonction de la famille et de la TA
 *
 * 	- ajout 12:06 13/11/2007 Gwénaël
 *
 * @param string $family : nom de la famille
 * @param string $ta : nom de la time agregation pour lequel on veut la durée de l'historique
 * @param string $product : id du produit
 * @return int
 */
function get_history($family, $ta, $product = '') {
    // 31/01/2011 BBX
    // On remplace new DatabaseConnection() par Database::getConnection()
    // BZ 20450
    $db = Database::getConnection($product);

    $query = "SELECT duration FROM sys_definition_history WHERE family='" . $family . "' AND ta='" . $ta . "'";
    $duration = $db->getone($query);
    if (!$duration) {
        // maj 15/04/2008 Benjamin : dans le cas d'un TA BH, on récupère l'historique du TA non BH. Il faut donc traiter la chaine pour retiré le "_bh"
        return (int) get_sys_global_parameters('history_' . str_replace('_bh', '', $ta), 0, $product);
    }

    return (int) $duration;
}

// End function get_history

/**
 * Renvoie la valeur de la TA en fonction de la période et de ta_value
 *
 * 	- ajout 24/12/2007 Gwénaël
 * 
 * @param string $ta : nom de la time agrégation
 * @param int $ta_value : valeur la time agrégation
 * @param int $period : valeur de la période
 * @return integer
 */
function getTAMinusPeriod($ta, $ta_value, $period) {
    $result = null;
    $h = 0; // heure
    $d = 0; // jour
    $w = 0; // semaine
    $m = 0; // mois
    $y = intval(substr($ta_value, 0, 4)); // année
    switch ($ta) {
        case 'hour':
            $h = intval(substr($ta_value, 8, 2)) - $period;
            $d = intval(substr($ta_value, 6, 2));
            $m = intval(substr($ta_value, 4, 2));
            $mktime = mktime($h, 0, 0, $m, $d, $y);
            $result = date("YmdH", $mktime);
            break;
        case 'day':
        case 'day_bh':
            $d = intval(substr($ta_value, 6, 2)) - $period;
            $m = intval(substr($ta_value, 4, 2));
            $mktime = mktime($h, 0, 0, $m, $d, $y);
            $result = date("Ymd", $mktime);
            break;
        case 'week':
        case 'week_bh':
            $w = intval(substr($ta_value, 4, 2));
            $continuer = true;
            do {
                if ($period >= 52) {
                    $y--;
                    $period = $period - 52;
                }
                if (($w - $period) > 0) {
                    $result = $y . ($w - $period);
                    $continuer = false;
                } else {
                    // 17/04/2008 - Modif. benoit : correction du bug 6330. La boucle ne partait pas dans le bon sens et faisait qu'on ne sortait jamais de celle-ci. De plus, le fait de se baser sur le 31/12 pour déterminer la semaine posait des problèmes (ex. le 31122007 est considéré comme la semaine 01 et non la semaine 52)
                    // 14/01/2014 GFS - Bug 38987 - [SUP][T&A CB][#NA][SFR/TELUS] : Wrong Display on WEEK time view. 
                    $y--; // On va sur l'année précédente
                    // Test du numéro de la dernière semaine de l'année précédente
                    $num_week = date('W', mktime(0, 0, 0, 12, 30/* 31 */, $y));
                    // Si la dernière semaine est la semaine 53 alors on la prend en compte
                    $w = ($num_week == "53" ? $w + 53 - $period : $w + 52 - $period);
                    $result = $y . $w;
                    $continuer = false;
                }
            } while ($continuer == true);
            // 17/04/2008 - Modif. benoit : correction du resultat pour les numéros de semaines < 10
            $week_result = str_replace($y, '', $result);
            if (($week_result < 10) && (strlen($week_result) == 1))
                $result = $y . "0" . $week_result;
            break;
        case 'month':
        case 'month_bh':
            $m = intval(substr($ta_value, 4, 2)) - ($period - 1);
            $mktime = mktime($h, 0, 0, $m, $d, $y);
            $result = date("Ym", $mktime);
            break;
    }
    return $result;
}

// End function getTAMinusPeriod
// 10/12/2008 - Modif. benoit : ajout de la fonction ci-dessous
// 15/12/2008 - Modif. benoit : ajout du paramètre '$order' à la fonction

/**
 * Renvoie un ensemble de valeurs de ta en fonction d'une ta, d'une valeur de départ et d'une période.
 * Cette fonction boucle sur la fonction 'getTAMinusPeriod' pour déterminer toutes les valeurs de ta 
 * 
 * 04 29/04/2009 GHX
 * 	- correction du for
 * 
 * @param string $ta : nom de la ta
 * @param int $ta_value : valeur de départ de la ta
 * @param int $period : valeur de la période
 * @param string $order sens de stockage des valeurs (par défaut, descendant)
 *
 * @return array ensemble des valeurs de ta
 */
function getTAInterval($ta, $ta_value, $period, $order = "desc") {
    $all_ta_values = array();

    // 05/05/2009 GHX
    // - Correction de la boucle du for de la fonction getTAInterval()  car il nous manque la période de départ
    // 17:02 29/04/2009 GHX
    // Modification du for car on a toujours une période en trop
    for ($i = --$period; $i >= 0; $i--) {
        $all_ta_values[] = getTAMinusPeriod($ta, $ta_value, $i);
    }

    if ($order == "asc")
        $all_ta_values = array_reverse($all_ta_values);

    return $all_ta_values;
}

/**
 * Fonction getUserProfileType($id_user)
 * retourne le type du client qui est connecté(customisateur ou client)
 * 
 * @param int $id_user : identifiant de l'utilisateur.
 * @return : type du client 
 */
function getClientType($id_user) {
    global $database_connection;
    // 20/02/2012 NSE DE Astellia Portal Lot2 : suppression de la jointure des tables users et profile
    if ($id_user != $_SESSION['id_user']) {
        throw new BadMethodCallException("Calling getClientType(id_user) with parameter id_user($id_user) != _SESSION['id_user'](" . $_SESSION['id_user'] . ")");
    }
    $query = "SELECT client_type FROM profile WHERE id_profile = '" . $_SESSION['user_profil'] . "' ";
    $result = pg_exec($database_connection, $query);
    list($client_type) = pg_fetch_row($result);

    // Si c'est le compte astellia_admin le client type est protected
    if ($client_type == 'protected')
        return 'customisateur';
    elseif (empty($client_type))
        return 'client';

    return $client_type;
}

// End function getClientType

/**
 * Fonction getUserInfo($id_user)
 * retourne un tableau contenant toutes les colonnes de la table users ainsi que le type de profil de l'utilisateur.
 * les index du tableau sont les noms des colonnes
 * @parameter : int $id_user identifiant de l'utilisateur.
 * @return : array 
 */
function getUserInfo($id_user = 0) {
    global $database_connection;

    $to_return = array();
    // 20/02/2012 NSE DE Astellia Portal Lot2 : suppression de la jointure des tables users et profile
    if ($id_user != $_SESSION['id_user']) {
        throw new BadMethodCallException("Calling getUserInfo(id_user) with parameter id_user($id_user) != _SESSION['id_user'](" . $_SESSION['id_user'] . ")");
    }
    $query = " 
		SELECT * 
			FROM users 
			WHERE id_user='" . $id_user . "'
		";
    $result = pg_query($database_connection, $query);
    $nombre_resultat = pg_num_rows($result);
    if ($nombre_resultat > 0) {
        $row = pg_fetch_array($result, 0);
        $i = pg_num_fields($result);
        for ($j = 0; $j < $i; $j++)
            $to_return[pg_field_name($result, $j)] = $row[pg_field_name($result, $j)];
    }
    $to_return['user_profil'] = $_SESSION['user_profil'];
    $to_return['profile_type'] = $_SESSION['profile_type'];
    return ($to_return);
}

// End function getUserInfo

/**
 * Fonction qui récupère le label du lien vers AA
 * 3 possibilités : 
 * 	- "Go to related CDR" pour filtre Telecom
 * 	- "Go to CDR  Database" pour filtre basique
 * 	- Label définit pour kpi dans sys_aa_filter_kpi
 */
function get_title_link_to_AA($data) {

    global $database_connection;

    $filtre_telecom = __T('U_LINK_TO_AA_LABEL_FILTER_TELECOM');
    $filtre_basic = __T('U_LINK_TO_AA_LABEL_FILTER_BASIC');

    $query = "SELECT CASE WHEN saafk_label_link IS NOT NULL THEN saafk_label_link 
					      WHEN saafk_idfilter IN (SELECT saalf_idfilter FROM sys_aa_list_filter) THEN '$filtre_telecom'
					      ELSE '$filtre_basic'
					 END		
			  FROM sys_aa_filter_kpi WHERE saafk_idkpi = '$data'";

    $res = pg_query($database_connection, $query);
    $label = "";
    if (pg_num_rows($res) > 0) {
        while ($row = pg_fetch_array($res)) {
            $label = $row[0];
        }
    }

    return $label;
}

// 29/03/2012 BBX
// BZ 26521 : la fonction va rechercher en priorité dans sys_definition_product pour récupérer l'ip.
// L'utilisation des variables serveur / commandes linux n'est utilisée 
// qu'en dernier recours ou lorsque l'on souhaite rechercher une IP pulique.
// 
// maj 13/09/2010 - MPR : Correction du bz16214
// Ajout d'un paramètre d'entrée optionel permettant d'indiquer ou non si on récupère l'IP publique ou privrée
/**
 * Fonction qui retourne l'adresse du serveur
 *   @param boolean $ip_public : true s'il faut récupérer dans un premier temps l'adresse IP publique
 *   @return string $adr_server : Adresse du serveur
 *
 *
 */
function get_adr_server($ip_public = false) {
    // Commande linux permettant de récupérer les IP configurées au format IPV4
    $cmdv4 = 'ip -o -4 addr | grep -v 127.0.0.1 | tr -s " :/" ";" | cut -d";" -f4';
    // Commande linux permettant de récupérer les IP configurées au format IPV6
    $cmdv6 = 'ip -o -6 addr | grep -v ::1 | tr -s " /" ";" | cut -d";" -f4';
    // Expression régulière permettant de filtrer les IP privées IPv4
    $regexp = "(^127\.0\.0\.1)|(^10\.)|(^172\.1[6-9]\.)|(^172\.2[0-9]\.)|(^172\.3[0-1]\.)|(^192\.168\.)";
    // Valeur de résultat
    $ipAddr = '';
    // Stocke le retour de la commande exec
    $ipList = array();
    // Avertissement dans le tracelog si l'ip n'a pas été récupérée depuis la base de données
    $warning = false;
    // Active ou non la compatibilité IPV6
    $ipv6Format = false;

    // Demande d'une IP publique (IPV4 uniquement)
    if ($ip_public && !$ipv6Format) {
        exec($cmdv4, $ipList);
        foreach ($ipList as $ip) {
            if (preg_match("/$regexp/x", $ip) !== 0)
                continue;
            $ipAddr = trim($ip);
            break;
        }
    }

    // Comportement par défaut : lecture de l'IP depuis sys_definition_product
    if (empty($ipAddr)) {
        $productModel = new ProductModel(ProductModel::getProductId());
        $productInfos = $productModel->getValues();
        $ipAddr = trim($productInfos['sdp_ip_address']);
    }

    // Comportement dégradé 1 : lecture de l'IP depuis Apache
    if (empty($ipAddr)) {
        if (!empty($_SERVER['SERVER_ADDR']))
            $ipAddr = trim($_SERVER['SERVER_ADDR']);
        $warning = 1;
    }

    // Comportement dégradé 2 : lecture de l'IP depuis Linux
    if (empty($ipAddr)) {
        $ipList = array();
        if ($ipv6Format)
            exec($cmdv6, $ipList);
        exec($cmdv4, $ipList);
        $ipAddr = trim($ipList[0]);
        $warning = 2;
    }

    // Avertissement dans le tracelog
    if ($warning) {
        $used = ($warning === 1) ? '$_SERVER' : 'Linux command';
        $message = __T('A_SYSTEM_GET_ADR_SERVER_FAILSAFE', $used);
        sys_log_ast('Warning', 'Trending&Aggregation', 'System', $message);
    }

    // Retour de l'IP
    return $ipAddr;
}

// maj christophe 20/02/2008 : ajout de la fonction getUserDashboarList : liste les id_page de la table sys_pauto_page_name des dahsboards appartenant à l'utilisateur courant. 
/**
 * getUserDashboarList
 * liste les id_page de la table sys_pauto_page_name des dashboards appartenant à l'utilisateur id_user dans un tableau les id_page sont en index.
 * */
function getUserDashboarList($id_user, $database_connection) {
    $to_return = array();
    // maj 26/03/2008 christophe : modification de la fonction getUserDashboarList on liste tous les id_page sauf ceux des dashboards créés par d'autres utilisateurs.
    $query = "
		(SELECT sppn.id_page FROM  sys_pauto_page_name as sppn,sys_definition_dashboard as sdd WHERE sppn.page_type='page' AND sdd.sdd_is_online=1 AND sppn.id_user='" . $id_user . "' AND sppn.id_page=sdd.sdd_id_page)
		UNION
		(SELECT sppn.id_page FROM  sys_pauto_page_name as sppn,sys_definition_dashboard as sdd WHERE sppn.page_type='page' AND sdd.sdd_is_online=1 AND sppn.id_user IS NULL AND sppn.id_page=sdd.sdd_id_page)
	";
    $result = pg_query($database_connection, $query);
    $result_nb = pg_num_rows($result);
    for ($k = 0; $k < $result_nb; $k++) {
        $result_array = pg_fetch_array($result, $k);
        $to_return[$result_array['id_page']] = $result_array['id_page'];
    }
    return $to_return;
}

/*
 * 	MaJ 29/10/2008 - ajout de la fonction get_na_levels_in_common()
 * 	Mise en commentaire 12/11/2008 - SLC - mise en commentaire de cette version de get_na_levels_in_common() qui ne fonctionne plus avec des products issus de bases différentes

  /**
 * 	Cette fonction retourne la liste des NA levels en commun pour un GTM donné (par son id_page)
 *
 * 	@param int		$id_page est l'id du GTM
 * 	@return array	retourne false s'il n'y a aucun na level en commun, et retourne la liste des niveaux d'agrégation en commun sous forme d'un array sinon.
 * /
  function get_na_levels_in_common($id_page)
  {
  global $db;
  // on va chercher les différentes familles des data qui composent le GTM $id_page
  $query = "	--- we get the families of the plots of the GTM $id_page
  SELECT
  CASE WHEN spc.class_object = 'counter' THEN
  (SELECT family FROM sys_definition_group_table WHERE edw_group_table IN
  (SELECT edw_group_table FROM sys_field_reference WHERE id_ligne = spc.id_elem)
  )
  ELSE
  (SELECT family FROM sys_definition_group_table WHERE edw_group_table IN
  (SELECT edw_group_table FROM sys_definition_kpi WHERE id_ligne = spc.id_elem)
  )
  END AS family
  FROM sys_pauto_config AS spc
  WHERE spc.id_page = $id_page
  ORDER BY spc.ligne ASC";
  $elements = $db->getall($query);

  // echo "<pre>$query</pre>";exit;
  // echo "<pre>";echo var_dump($elements);echo "</pre>";

  if ($elements) {
  $families = array();

  foreach ($elements as $elem)
  if (!in_array($elem["family"],$families))
  $families[] = $elem["family"];
  }

  // debug :
  // echo "<p>Families: ".implode(', ',$families)."</p>";

  // on va chercher toutes les listes de na_label par famille
  // $na_levels = getNaLabelList('all');
  $na_levels = getNaLabelList('na');
  // ex:		$na_labels['apn'] =  [ 'apnamegroup' => 'APName Group', 'apname' => 'APName' ]

  // __debug($na_levels);

  // on prend les na_levels de la première famille
  $na_levels_in_common = $na_levels[$families[0]];

  //	if (!is_array($na_levels_in_common)	)	return false;

  for ($i=1; $i<sizeof($families); $i++) {
  $na_levels_in_common = array_intersect_assoc( $na_levels_in_common, $na_levels[$families[$i]] );
  }


  // si on a rien :
  if (sizeof($na_levels_in_common) == 0) {
  return false;
  } else {
  // on renvoie la liste
  return $na_levels_in_common;
  }
  }
 */

// MaJ 12/11/2008 - SLC - ajout de la fonction 
/**
 * 	Cette fonction va chercher tous les NA levels dispercés sur tous les produits
 */
function getNaLabelListAcrossProducts($type = '', $family = '') {
    $products = getProductInformations();
    $all_na_labels = array();
    foreach ($products as $p) {
        $na_labels = getNaLabelListForProduct($type, $family, $p['sdp_id']);
        foreach ($na_labels as $key => $val) {
            // maj MPR : Correction du bug - Ajout du produit dans l'index du tableau ( problème :  multiprod avec roaming et gsm => famille roaming présente dans les deux produits
            $all_na_labels[$p['sdp_id'] . "_" . $key] = $val;
        }
    }

    return $all_na_labels;
}

// 14/11/2008 - Modif. benoit : creation des fonctions 'GetGTInfoFromFamily()' (reprise de 'get_gt_info_from_family()' de "php/edw_function_family.php") et 'GetAxe3()' (reprise de 'get_axe3()' de ce fichier)

/**
 * Renvoie les informations du group_table d'une famille et d'un produit passés en paramètres
 *
 * @param string $family nom de la famille
 * @param integer $product identifiant du produit (optionnel)
 * @return array les informations du group_table correspondant à la famille et au produit
 */
function GetGTInfoFromFamily($family, $product = "") {
    // 31/01/2011 BBX
    // On remplace new DatabaseConnection() par Database::getConnection()
    // BZ 20450
    $db = Database::getConnection($product);

    return $db->getRow("SELECT * FROM sys_definition_group_table WHERE family='$family'");
}

/**
 * fonction qui à partir d'un nom de family retourne les informations sur l'axe de la famille
 * @param string $family : nom de la famille
 * @param string $product : id du product, ou ''
 * @return array $axe_information qui contient toutes les données ou FALSE si aucun axe n'est trouve
 *
 */
// 19/11/2008 - SLC - creation de cette fonction en remplacement de get_axe3_information_from_family($family) de edw_function_family.php
function GetAxe3Information($family, $product = "") {
    // global $database_connection;
    // 31/01/2011 BBX
    // On remplace new DatabaseConnection() par Database::getConnection()
    // BZ 20450
    $db = Database::getConnection($product);

    $query = " --- on va chercher les informations des axes de la famille
		SELECT axe_gt_id,axe_index,axe_index_label,axe_label,axe_type,family,axe_order,id_group_table, axe_type_label
		FROM sys_definition_gt_axe
		WHERE family='$family'";
    $results = $db->getall($query);
    if ($results) {
        $nb_results = sizeof($results);
        for ($i = 0; $i < $nb_results; $i++) {
            $row = $results[$i];
            $axe_information["axe_gt_id"][$i] = $row["axe_gt_id"];
            $axe_information["axe_index"][$i] = $row["axe_index"];
            $axe_information["axe_index_label"][$i] = $row["axe_index_label"];
            $axe_information["axe_label"][$i] = $row["axe_label"];
            $axe_information["axe_type"][$i] = $row["axe_type"];
            $axe_information["family"][$i] = $row["family"];
            $axe_information["axe_order"][$i] = $row["axe_order"];
            $axe_information["id_group_table"][$i] = $row["id_group_table"];
            $axe_information["axe_type_label"][$i] = $row["axe_type_label"];
        }
        return $axe_information;
    } else {
        return false;
    }
}

/**
 * 24/11/2008 BBX : fonction qui récupère les infos du produit MASTER
 * @since cb4.1.0.00
 * @return array : tableau associatif des infos produits
 *
 */
function getMasterProduct() {
    // 31/01/2011 BBX
    // On remplace new DatabaseConnection() par Database::getConnection()
    // BZ 20450
    $db = Database::getConnection();
    $query = "SELECT sdp_id,
	sdp_label,
	sdp_ip_address,
	sdp_directory,
	sdp_db_name,
	sdp_db_port,
	sdp_db_login,
	sdp_db_password,
	sdp_ssh_user,
	sdp_ssh_password,
	sdp_on_off,
	sdp_master
	FROM sys_definition_product
	WHERE sdp_master = 1";
    return $db->getRow($query);
}

/**
 * 10/12/2008 BBX : fonction qui récupère les infos du produit MASTER TOPO
 * @since cb4.1.0.00
 * @return array : tableau associatif des infos produits
 *
 */
function getTopoMasterProduct() {
    // 31/01/2011 BBX
    // On remplace new DatabaseConnection() par Database::getConnection()
    // BZ 20450
    $db = Database::getConnection();
    $query = "SELECT sdp_id,
	sdp_label,
	sdp_ip_address,
	sdp_directory,
	sdp_db_name,
	sdp_db_port,
	sdp_db_login,
	sdp_db_password,
	sdp_ssh_user,
	sdp_ssh_password,
	sdp_on_off,
	sdp_master
	FROM sys_definition_product
	WHERE sdp_master_topo = 1";
    return $db->getRow($query);
}

/**
 * 17:06 30/12/2008 SCT : fonction qui récupère le séparateur entre les éléments composant un NA
 * Fonction qui retourne le séparateur pour l'ensemble des familles
 * 	@return array $tableau_separator : tableau contenant l'ensemble des séparateurs pour les familles
 *
 */
function getFamilySeparator() {
    // 31/01/2011 BBX
    // On remplace new DatabaseConnection() par Database::getConnection()
    // BZ 20450
    $db = Database::getConnection();
    $query = 'SELECT rank, separator FROM sys_definition_categorie';
    $results = $db->getall($query);
    if ($results) {
        $nb_results = sizeof($results);
        for ($i = 0; $i < $nb_results; $i++) {
            $row = $results[$i];
            $tableau_separateur[$row['rank']] = $row['separator'];
        }
        return $tableau_separateur;
    } else {
        return array();
    }
}

// End function getFamilySeparator

/**
 * Génère un identifiant unique à partir du nom de la table
 *
 * @author GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 * @param string $table nom de la table
 * @param boolean $fixe spécifie si le nom de la table est déjà un préfixe [ default false]
 * @return string
 */
function generateUniqId($table, $fixe = false) {
    $prefix = $table;
    if ($fixe == false) {
        $prefix = '';
        foreach (explode('_', $table) as $t) {
            $prefix .= $t[0];
        }
    }

    return $prefix . '.' . md5(uniqid(rand(), true));
}

// End function generateUniqId
// 02/02/2009 - Modif. benoit : création de la fonction ci-dessous

/**
 * Permet de retourner une ne à partir d'une na donnée
 *
 * @param string $na nom de la na dont on cherche une valeur
 * @return string la ne correspondant à la na
 */
function getOneNeFromNa($na, $product = '') {
    // 31/01/2011 BBX
    // On remplace new DatabaseConnection() par Database::getConnection()
    // BZ 20450
    $db = Database::getConnection($product);

    $sql = "SELECT eor_id FROM edw_object_ref WHERE eor_obj_type = '" . $na . "' LIMIT 1";
    $row = $db->getRow($sql);

    return $row['eor_id'];
}

// 19/02/2009 - Modif. benoit : creation de la fonction ci-dessous

/**
 * Retourne le label d'une ne donnée
 *
 * @param string $na nom de la na définissant la ne dont on cherche le label
 * @param string $ne valeur de la ne à labelliser
 * @param int $product identifiant du produit sur lequel on cherche le label de la ne
 * @return string le label de la ne
 */
function getNELabel($na, $ne, $product = '') {
    // 31/01/2011 BBX
    // On remplace new DatabaseConnection() par Database::getConnection()
    // BZ 20450
    $db = Database::getConnection($product);

    $sql = " SELECT eor_label FROM edw_object_ref"
            . " WHERE eor_obj_type = '" . $na . "'"
            . " AND eor_id = '" . $ne . "'";

    $ne_label = $db->getOne($sql);

    return ($ne_label != "") ? $ne_label : $ne;
}

// maj 17/06/2009 MPR : Ajout des fonctions utilisés par le downlaod topology et l'export data
/**
 * Fonction qui retourne les champs possibles à uploader selon le produit
 *
 * @param $id_prod : id du produit
 * @param $database : connexion à la base de données
 * @param $fields : liste des na
 * @param $coordinates : liste des coordonnées
 * @return array $topology : Tableau contenant les correspondances entre les deux types de header 
 *
 */
function getTopologyProduct($id_prod, $database, $fields, $coordinates) {

    $product = get_sys_global_parameters("module", $id_prod);
    $query = "SELECT * FROM edw_object_ref_header
				  WHERE eorh_id_produit = '" . $product . "' ORDER BY eorh_id_column_db";

    $res = $database->getAll($query);


    if (count($res) > 0) {
        foreach ($res as $row) {

            // On récupère uniquement les colonnes sélectionnées dans l'interface
            if (in_array($row['eorh_id_column_db'], $fields) or in_array($row['eorh_id_column_db'], $coordinates)) {
                $topology[$row['eorh_id_column_db']] = $row['eorh_id_column_file'];
            }
        }
    } else {
        echo "<div class='errorMsg'>Erreur - Product " . $product . " does not exist</div>";
    }

    return $topology;
}

// End function getTopologyProduct

/**
 * Fonction qui convertit le fichier au format Astellia (fichier de topologie commune) 
 * @param $file : fichier contenant les données à convertir
 * @param $topology : tableau contenant la correspondance entre les entête Astellia et T&A
 * @param $header : entête de format T&A
 * @return $new_header : entête de format Astellia
 */
function convertFileInAstelliaFormat($file, $topology, $header, $na_list, $infosProd, $connexionSSH = "") {

    $new_header = array();

    $lst_na = array_keys($na_list);

    $nb_na = 0;
    // On conserve uniquement les colonnes utiles 
    // Dans le cas où l'on regroupe deux colonnes ex: network, network_label => network_name, on conserve que la colonne network
    if (count($topology) > 0) {

        foreach ($topology as $column_db => $column_ast) {

            $id_col = array_keys($header, $column_db);

            if (!in_array($column_ast, $new_header)) {

                if (in_array($column_db, $lst_na)) {
                    $nb_na++;
                }

                $columns_to_conserve[$id_col[0] + 1] = $id_col[0] + 1;
                $new_header[$id_col[0] + 1] = $column_ast;
            }
        }
    }


    if ($nb_na > 0) {

        // Réordonne les colonnes en fonction de leur id
        ksort($columns_to_conserve);
        ksort($new_header);

        $file_tmp = REP_PHYSIQUE_NIVEAU_0 . "upload/admintool_download_topology_new_header" . uniqid("") . ".csv";

        if ($connexionSSH == "") {

            // On récupère uniquement les colonnes à conserver
            $cmd = "cut -d \";\" -f" . implode(",", $columns_to_conserve) . " $file > $file_tmp";
            exec($cmd);

            // On renvoie le résultat vers le fichier d'entrée
            $cmd = "mv $file_tmp $file";
            exec($cmd);
        } else {

            $file_tmp = str_replace(REP_PHYSIQUE_NIVEAU_0, "/home/{$infosProd['sdp_directory']}/", $file_tmp);
            $cmd = "cut -d \";\" -f" . implode(",", $columns_to_conserve) . " $file > $file_tmp";
            $connexionSSH->exec($cmd);

            // On renvoie le résultat vers le fichier d'entrée
            $cmd = "mv $file_tmp $file";
            $connexionSSH->exec($cmd);
        }

        return $new_header;
    } else {

        // MPR : Correction du bug 9636 - On récupère systématiquement les labels des colonnes du header (type header T&A)
        // On remplace les id par les labels
        foreach ($header as $k => $field) {

            if (in_array($field, $lst_na)) {

                $header[$k] = $na_list[$field];
            } elseif (ereg("_label", $field)) {

                $na = explode("_", $field);

                $header[$k] = $na_list[$na[0]] . " label";
            }
        }

        return $header;
    }
}

/**
 * Retourne la requête qui récupère tous les enfants des éléments réseau sélectionnés 
 * MPR 19/11/2008
 * @since cb4.1.0.00
 * @version cb4.1.0.00
 * @param array $na_values : liste des éléments réseau sélectionnés
 * @param array $array_levels : liste des na enfants que l'on doit récupérer
 * @return array : tableau contenant les requêtes sélectionnant tous les enfants des éléments réseau sélectionnés
 */
function createQueryTopology($array_levels, $net_min, $fields) {

    // Construction de la requête principale
    // Format de la requête
    // MPR : Correction du bug 9638 - Modification de la reuqête SQL qui récupère les données topologiques ( pbl cas particulier cell->bsc->msc->network non gérer avec l'ancienne requête)
    /*
      SELECT e0.eor_id as cell, e1.eor_id as bsc , e1.eor_label as bsc_label , e2.eor_id as msc , e2.eor_label as msc_label , e3.eor_id as network , e3.eor_label as network_label  FROM edw_object_ref as e0  LEFT JOIN edw_object_arc_ref as arc1
      RIGHT JOIN (
      SELECT DISTINCT eor_id, eor_label, eor_obj_type FROM edw_object_ref
      WHERE eor_obj_type  ='bsc'
      ) as e1 ON (arc1.eoar_id_parent = e1.eor_id)
      ON (e0.eor_id=arc1.eoar_id)
      LEFT JOIN edw_object_arc_ref as arc2
      RIGHT JOIN (
      SELECT DISTINCT eor_id, eor_label, eor_obj_type FROM edw_object_ref
      WHERE eor_obj_type  ='msc'
      ) as e2 ON (arc2.eoar_id_parent = e2.eor_id)
      ON (e1.eor_id=arc2.eoar_id)
      LEFT JOIN edw_object_arc_ref as arc3
      RIGHT JOIN (
      SELECT DISTINCT eor_id, eor_label, eor_obj_type FROM edw_object_ref
      WHERE eor_obj_type  ='network'
      ) as e3 ON (arc3.eoar_id_parent = e3.eor_id)
      ON (e2.eor_id=arc3.eoar_id)
      WHERE e0.eor_obj_type = 'cell' AND (arc1.eoar_arc_type = 'cell|s|bsc' OR arc1.eoar_arc_type IS NULL) AND (arc2.eoar_arc_type = 'bsc|s|msc' OR arc2.eoar_arc_type IS NULL) AND (arc3.eoar_arc_type = 'msc|s|network' OR arc3.eoar_arc_type IS NULL)

     */

    $table_arc = 'edw_object_arc_ref';
    $table_ref = 'edw_object_ref';
    // Construction de la sous-requête qui récupère les arcs
    $j = 0;

    $nb = count($array_levels);

    $_select = "SELECT e0.eor_id as {$array_levels[0]}";
    $query['header'][] = $array_levels[0];

    if (in_array($array_levels[0] . "_label", $fields)) {
        $_select .= ", e0.eor_label as {$array_levels[0]}_label";
        $query['header'][] = $array_levels[0] . "_label";
    }

    $_from = " FROM {$table_ref} as e0 ";
    $_where = " WHERE e0.eor_obj_type = '{$array_levels[0]}'";


    for ($i = 1; $i < ($nb); $i ++) {

        $j = $i - 1;

        $na = $array_levels[$i];
        $na_label = $array_levels[$i] . "_label";

        $na_inf = $array_levels[$j];


        // Récupération du na dans le select
        if (in_array($na, $fields)) {
            $_select .= ", e{$i}.eor_id as {$na} ";
            $query['header'][] = $na;
        }

        // Récupération du na_label dans le select			
        if (in_array($na_label, $fields)) {
            $_select .= ", e{$i}.eor_label as {$na_label} ";
            $query['header'][] = $na_label;
        }

        // MPR : Correction du bug 9867 - On ajoute la condition sur eor_obj_type afin de ne pas mélanger les éléements réseau de niveau d'agreg <>

        $_from .= " LEFT JOIN {$table_arc} as arc{$i} 
							RIGHT JOIN (
								SELECT DISTINCT eor_id, eor_label, eor_obj_type FROM {$table_ref} 
								WHERE eor_obj_type  ='{$na}'
							) as e{$i} ON (arc{$i}.eoar_id_parent = e{$i}.eor_id)
						 ON (e{$j}.eor_id=arc{$i}.eoar_id)
						";

        $_where .= " AND (arc{$i}.eoar_arc_type = '{$na_inf}|s|{$na}' OR arc{$i}.eoar_arc_type IS NULL)";
    }

    // 25/11/2011 ACS BZ 24792 Virtual SAI of corporate are visibles in multiple GUI
    $_where .= " AND " . NeModel::whereClauseWithoutVirtual('e0');

    $sql = $_select . $_from . $_where;

    $query['sql'] = $sql;

    return $query;
}

// maj 24/11/2008 - MPR : ajout de la fonction getAgregPath qui récupère tous les niveaux d'agrégation enfant du niveau d'agrégation sélectionné
/**
 * Retourne le chemin d'agrégation d'un niveau
 *
 * MPR 24/11/2008
 * @since cb4.1.0.0
 * @version cb4.1.0.0
 * @param string $family_min_net : niveau d'agregation minimum de la famille
 * @param string $family : famille
 * @param string $database : Instance de la classe DatabaseConnection pour exécuter les requêtes SQL
 * @param string $level : niveau dont on veut connaître le chemin jusqu'au
 * @return array : tableau contenant les niveaux d'agrégations depuis $level jusqu'au niveau minimum
 */
function getAgregPath($family_min_net, $level, $family, $database) {

    // Récupération du chemin			
    $query = "SELECT * FROM get_path('{$level}','{$family_min_net}','{$family}');";

    $array_result = Array();

    $result = $database->getAll($query);

    foreach ($result as $array) {

        $array_result[] = $array['get_path'];
    }

    // Sauvegarde du résultat dans l'objet pour éviter de rééxécuter les requêtes si on cherche de nouveau les mêmes informations
    $agregPathArray = array_reverse($array_result);

    return $agregPathArray;
}

// End function getAgregPath

/**
 * Retourne le statut de la topologie (mappée ou non)
 *
 * BBX 04/08/2009
 * @since cb5.0.0
 * @version cb5.0.0
 * @return boolean : topo mappée (true) ou topo non mappée (false)
 */
function getTopologyMappingInfo() {
    // Connexion à la base de données
    // 31/01/2011 BBX
    // On remplace new DatabaseConnection() par Database::getConnection()
    // BZ 20450
    $db = Database::getConnection();
    // Récupération du master topo
    $masterTopo = getTopoMasterProduct();
    $masterTopoId = $masterTopo['sdp_id'];
    // Récupération des produits actifs
    foreach (ProductModel::getActiveProducts() as $productArray) {
        // On élimine le master topo de la recherche
        if ($productArray['sdp_id'] != $masterTopoId) {
            // Connexion au produit
            // 31/01/2011 BBX
            // On remplace new DatabaseConnection() par Database::getConnection()
            // BZ 20450
            $db_temp = Database::getConnection($productArray['sdp_id']);
            // On regarde si le codeq existe
            $query = "SELECT * FROM edw_object_ref 
			WHERE eor_id_codeq IS NOT NULL
			LIMIT 1";
            $db_temp->execute($query);
            // Si codeq renseigné, alors la topo est mappée
            if ($db_temp->getNumRows() > 0) {
                return true;
            }
        }
    }
    return false;
}

/**
 * 16/02/2011 MMT bz 20191
 * Retourne un nom de fichier qui ne contient pas de caractères spéciaux afin qu'il puisse être
 * utilisé dans une commande unix sans devoir être mis entre quote ex:
 * exec("cat $filename > toto")   si $filename contient des caractères espace, & ou ; la commande echouera
 * cette methode les remplace par des _
 *
 * @param String $fileName nom du fichier en entré
 * @return String  nom du fichier "command safe" basé sur le fichier en entré
 */
function getCommandSafeFileName($fileName) {
    return preg_replace("/[^a-zA-Z0-9\.\/-]/", "_", $fileName);
}

// 09/12/2011 ACS Mantis 837 DE HTTPS support
/**
 * Check if an url really exist or not
 * 
 * @param String $url
 * @return boolean true if url really exists
 */
function urlExists($url) {
    $result = false;

    $fp = @fopen($url, 'r');
    if ($fp) {
        fclose($fp);
        $result = true;
    }
    return $result;
}

/**
 * 02/05/2011 MMT bz 21899 creation function pour factorizer et pour eviter erreur systeme si il y a trop de ficher a supprimer
 * delete all files from given folder and sub-folder older than the nb of given days but does not delet any folder
 * The function run a find command to look for file and then loop on them to unlink them all
 * 12/04/2013 GFS - Bug 30942 - [GATEWAY]: clean_file never launched
 *
 * @param String $dir root folder to start the delete from
 * @param int $nbDays nb of days from today were file is kept, older files will be deleted
 * @param String $additionalFindFilters optional aditionnal filter for the find Unix command that look for files to delete
 */
function purgeDirFromOldFiles($dir, $nbDays, $fileFilterFormat = "") {
    $nbFilesDeleted = 0;
    $fileCanBeDeleted = true;
    if (is_dir($dir)) {
        $cdir = scandir($dir);
        foreach ($cdir as $key => $value) {
            // On n'analyse pas les sous-répertoires
            $file = $dir . DIRECTORY_SEPARATOR . $value;
            if (!in_array($value, array(".", "..")) && !is_dir($file)) {
                if (!empty($fileFilterFormat) && !preg_match($fileFilterFormat, $value)) {
                    $fileCanBeDeleted = false;
                }
                if ($fileCanBeDeleted) {
                    // 27/06/2013 MGO - Bug 33749 - Missing information in demon file
                    $triggerDate = strtotime("+ " . $nbDays . " day", filemtime($file));
                    if ($triggerDate < time()) {
                        if (!unlink($file)) {
                            Print "Error: could not delete '$file'<br>\n";
                        } else {
                            $nbFilesDeleted += 1;
                        }
                    }
                }
            }
            // 01/08/2014 GFS - Bug 42689 - [SUP][T&A CB][#47109][SFR]: Problem of Robustness on T&A clean_files.php task
            $fileCanBeDeleted = true;
        }
        Print "Cleaning  files in $dir (older than $nbDays day(s)) :" . $nbFilesDeleted . " element(s) found<br/>\n";
    }
}
