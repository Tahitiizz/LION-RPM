<?php
/*
	28/05/2009 GHX
		- Suppression du paramètre passé au constructeur
		- Prise en compte de la classe DatabaseConnection()
		- Modification des requetes SQL sur les tables de topologie
	03/08/2009 GHX
		- Correction du BZ 7427
	25/08/2009 GHX
		- Correction du BZ 11056 [REC][T&A CB 5.0][TC#18253][TP#1][Lien AA]: le champ id_column Type 2008 ne fonctionne pas

	07/12/2009 NSE/GHX BZ 13349
	 	- Modif pour prendre en compte les formats de jour et mois à 1 chiffre (US : M/d/YY)
	10/12/2009 NSE BZ 13342
		- Ajout dans la fonction filter_contextuel de la condition IS NULL sur les groupes de filtres pour que la requête retourne un résultat même si aucun groupe n'est défini.

	14/12/2009 BBX BZ 13300
		- Gestion du split sur l'élément réseau (fonctions filter_contextuel + listServers)

	11/01/2010 BBX : report de la correction du bug 8652 par SCT. BZ 13696
	27/01/2010 : Correction du BZ13934 : Modification de la condition lorsque le NA max ne possède pas de liens vers AA
	22/02/2010 NSE bz 14429 : comparaison avec version 4.0.15.0
		- ajout de la condition sur la famille
		- ajout des versions en majuscules sur un switch Node 1, Node 2, CIC sur la colonne saac_name
	23/03/2010 NSE bz 14831
		- suppression du paramètre data_value inutilisé

   31/08/2010 MMT - DE firefox bz 17306
      - Pour navigateurs autre que IE, creation d'un fichier .aacontrol qui est téléchargé par le
        Navigateur et executé par AAcontrol (doit être correctement défini dans la base de registre)

   08/09/2010 MMT - DE liens Horraire + bz 17732: liens vers AA ne fonctionnent pas avec valeures horraires sous Firefox
      - Ajout de l'option hashing si on est en TA hour
      - Utilisation d'un format de date fixe pour filtres de date et paramètre hashing si on utilise pas ActiveX
        Car firefox ne peut récuperer le format de date court utilisé par le client dans ses paramètres regionaux
      - Ajout d'une fonctionnalité de test de la version de AAcontrol pour n'utiliser le hashing que si > 3.12.5
   21/10/2010 NSE bz 18715 : AA ne se lance pas sous IE si le filtre contient des "
   30/11/2010 NSE : réinitialisation du debug à partir de la valeur en base.
 *
 * 09/06/2011 MMT bz 22322 : mapping inverse pour le NE dans les liens AA
 * 15/07/2011 MMT Bz 22810 saacf_before_value et saacf_after_value ne sont pas pris en compte
 * 09/12/2011 NSE DE new parameters in AA links contextual filters
 * 22/12/2011 NSE bz 25255 : saacf_after_value not available + ajout des valeurs before et after pour sgsnggsn et nod1nod2
 *
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
*	- maj 14:14 17/03/2008 Maxime : Prise en compte du 3ème axe
*	- maj 14:32 17/03/2008 Maxime : On intègre un groupe de filtre
*	- maj 08:47 07/12/2007 Gwénaël : répercution d'un dev du patch cb_v3.0.1.01 : CAS PARTICULIER pour Iub sur le filtre contextuel (fonction getNaLabel())
*	- maj 10:05 14/11/2007 Gwénaël  :
*		- Répercution des bugs 5224 & 5205
*		- Correction pour afficher l'erreur quand Activity Analysis n'est pas installé sur le poste client
*
*	- ajout 14:40 18/12/2008 SCT : ajout des combinaisons pour le parser CORE

*/
?>
<?
/*
*	@cb30000@
*
*	18/06/2007 - Copyright Astellia
*
*	Composant de base version cb_3.0.0.00
*
*	- maj 11:28 11/03/2008 Gwénaël : recherche des bases sans NA spécifiques dans le cas où on ne trouve pas de base pour la NA (pour Roaming principalement) (foncton listServers())
*	- maj 14:02 06/12/2007 Gwénaël : CAS PARTICULIER pour Iub sur le filtre contextuel (fonction getNaLabel())
*	- maj 17:03 25/10/2007 Gwénaël : modification de la requête qui récupère la liste des serveurs pour regrouper les bases quand plusieurs liens vers AA pointant vers meme base [BUG : 5224]
*	- maj 13:32 23/10/2007 Gwénaël : modification de la condition WHERE, concernant les tags, qui récupère les bases/serveurs [BUG 5205]
*	- maj 12:09 03/10/2007 Gwénaël : modification de la condition qui récupère les codes colonnes
*	- maj 09:50 03/09/2007 Gwénaël : modif condition WHERE pour récupérer la liste des serveurs
*	- maj 16:01 22/08/2007 Gwénaël : modif condition WHERE pour récupérer la liste des serveurs
*	- maj 09:19 17/08/2007 Gwénaël : on prend le label au lieu de la valeur pour la NA
*/
?>
<?php
/**
 * Classe permettant de créer le lien vers Activity Analysis et de le lancer.
 * La création du lien ce fait en deux parties, la première (en PHP) qui récupère les données en base pour créer le filtre et avoir les paramètres de connexion à la base nécessaire à AA.
 * La deuxième (en HTML/JS) qui permet de récupérer le chemin où se trouve AAControl.exe, le répertoire temporaire de Windows dans lequel est créé le fichier filtre, puis lancement de AA
 * Si plusieurs base/serveur sont possibles, l'utilisateur aura la possibilité de choisir.
 *
 * NOTE :
 *	- Le chemin vers AAControl.exe est récupére dans la base de registre.
 *	- Pour que IE puisse lancer AA il faut que ActiveX soit activé et que l'IP du serveur sur quel se trouve T&A soit enregistré dans la base de registre du poste client en tant que site de confiance.
 *		Affichage d'un message d'erreur si une des conditions n'est pas remplies.
 *	- Ne gère pas le troisième axe pour le moment.
 *
 * cf. la page suivante est une documentation intéressante pour tout ce qui concerne lecture/écriture/... dans un fichier via javascript avec ActiveXObject
 *	http://marcel-bultez.chez-alice.fr/documents/SupportsRepertoiresFichiers.htm
 * Pour plus d'info sur l'exécutable AAControl.exe voir le pdf suivant :
 *	MAA37D_MU_AAControl_RB.pdf
 *
 * Liste des fichiers modifiés pour prendre en compte le lien vers AA
 *	- reporting/intranet/php/affichage/launchAA.php
 *	- reporting/intranet/php/affichage/gtm_stroke_graph.class.php
 *	- reporting/intranet/php/affichage/gtm_stroke_pie.class.php
 *	- reporting/intranet/php/affichage/gtm_query_graph.class.php
 *	- graphe/jpgraph_bar.php
 *	- graphe/jpgraph_plotmark.inc
 *	- graphe/jpgraph_plotmark.inc.php
 *	- graphe/jpgraph_pie3d.php
 *	- js/fenetre_volantes.js
 *
 * Fichiers modifiés aussi dans le but de pouvoir faire le lien vers AA et dans le but d'une optimisation,
 * ils n'ont pas plus besoins d'être modifié en ce qui concerne le lien vers AA normalement
 *	- reporting/intranet/php/affichage/gtm.class.php
 *	- reporting/intranet/php/affichage/gtm_query.class.php
 *	- js/menu_contextuel.js
 *	- php/menu_contextuel.js
 *
 * Note utilisation AAcontrol.exe pour navigateurs ne supportant pas ActiveX:
 * - Javascript envoie en paramètre ($useActiveX) si navigateur supporte ActiveX oui/non
 * - si pas de support ActiveX, un fichier .aacontrol est ecris sur le serveur, contenant tous les paramètres
 * nécéssaires a l'execution puis est téléchargé et executé par le navigateur client. si le poste client
 *  est correctement configuré (a ete mis a jours avec AA contenant la DE), ceci lancera AA avec les bon paramètres.
 * Voir Specification d'Interface pour details
 * \\ast_sf\R&D$\Forum\projets\TRENDING_AGGREGATION\CB5.0\Classeur\02.DonneesEntree\Demandes d'évolutions\FireFox_Mozilla\TCB50_InterfaceTA_AAFirefox_RA.doc
 *
 *
 *
 */

// 09/12/2011 NSE DE new parameters in AA links contextual filters
require_once( dirname( __FILE__ ).'/CbCompatibility.class.php' );

class LinkToAA {

   // 31/08/2010 MMT - DE firefox bz 17306 ajout variable statiques pour utilisation fichier .aacontrol

   //separateur pour extraire valeures definie par Javascripts des autres valeures pour AA
   static $JS_SEPARATOR = "|j|";

   // nom de la configuration par default dans le fichier .aacontrol généré
   static $AACTRL_DEFAULTCONFIG = "Default";

   // prefixe du nom du fichier .aacontrol généré
   static $AACTRL_FILE_PREFIX = "TA_AAexport";

   // MMT 08/09/2010 netoyage
	/**
	 * Nom du fichier filtre qui sera créé sur le poste client
	 * @access private
	 * @var string
	 */
	var $COOKIE_NAME = 'TrendingAggregation_AA.txt';

	/**
	 * Chemin dans la base de registre dans lequel se trouve le chemin absolu vers Analysis.exe
	 *	>> Ne pas oublié de doubler tous les anti-slashes
	 * @access private
	 * @var string
	 */
	var $HKEY_AA = 'HKEY_LOCAL_MACHINE\\\\SOFTWARE\\\\Astellia\\\\AAControl\\\\EXENAME';

	/**
	 * Identifiant du produit
	 * @since CB4.1.0.00
	 * @var int
	 */
	private $id_product;

	/**
	 * Connexion sur la base de données T&A
	 * @access private
	 * @var Ressource
	 */
	var $db_connec;

	/**
	 * Si valeur à 1 le mode débugage est activé dans le cas contraire non
	 * en mode debugage la fenêtre ne se ferme pas
	 *	cf. get_sys_debug('launch_AA')
	 *		0 : désactivé / 1 : activé / 2 : activé mais ne lance pas AA
	 * @access private
	 * @var integer
	 */
	var $debug = 0;

	/**
	 * Nom de la famille
	 * @access private
	 * @var string
	 */
	var $family;

	/**
	 * Time Agregation
	 * @access private
	 * @var string
	 */
	var $ta;
	var $ta_value;

	/**
	 * Network Agregation
	 * @access private
	 * @var string
	 */
	var $na;
	var $na_value;

	/**
	 * Network Agregation du troisième axe
	 * @access private
	 * @var string
	 */
	var $na_axe3 = null;
	var $na_axe3_value = null;

	/**
	 * Tableau contenant les données RAW/KPI
	 * Structure du tableau :
	 *	array ['type']   => raw ou kpi
	 *	array ['id']       => identifiant du raw ou du kpi
	 * @access private
	 * @var array
	 */
	var $data = array();

	/**
	 * Numéro de la vue
	 * @access private
	 * @var integer
	 */
	var $view;

	/**
	 * Nom du tag (PS / CS / null)
	 * @access private
	 * @var string
	 */
	var $tag;

	/**
	 * Tableau contenant les données du filtre
	 * Structure du tableau :
	 *	array [index] [0]  => nom de la colonne
	 *	array [index] [1]  => opérateur
	 *	array [index] [2] => valeur
	 * @access private
	 * @var array
	 */
	var $filter = array();

	/**
	 * Tableau contenant les paramètres de connexion au serveur, peut avoir plusieurs connexion possible
	 * Structure du tableau :
	 *	array [index] ['host'] => serveur sur lequel se trouve la base
	 *	array [index] ['port'] => port de connexion
	 *	array [index] ['login'] => login de connexion à la base
	 *	array [index] ['pwd'] => mot de passe de connexion à la base
	 *	array [index] ['db'] => nom de la base
	 * @access private
	 * @var array
	 */
	var $servers = array();

	/**
	 * Message d'errreur
	 * @access private
	 * @var string
	 */
	var $error = null;

	/**
	 * Type du module parser (iu, iub, gsm ....)
	 * @access private
	 * @var string
	 */
	var $module_parser = null;

	/**
	 * Définit si le NA doit être splitté
	 * @access private
	 * @var bool
	 */
	var $splitNa = false;

   /**
    * 31/08/2010 MMT - DE firefox bz 17306 ajout option sans ActiveX
    * si vrai, utilise ActiveX pour lancer AA, sinon utilise fichier .aacontrol
    * @access private
    * @var bool
    */
   var $useActiveX = true;

  /**
   * 08/09/2010 MMT - DE liens Horraire
   * Definit si l'on doit utiliser le parametre de hashing ou non
   * c'est a dire si on a des filtres de date
   * @var bool
   */
   var $useHashingDateTimes = false;

	/**
	 * Constructeur
	 *
	 * 	15:55 28/05/2009 GHX
	 *		- Suppression du paramètre passé au constructeur
	 *
	 * @version CB4.1.0.00
	 */
	public function LinkToAA () {
		$this->debug = get_sys_debug('launch_AA');
		$this->module_parser = get_sys_global_parameters('module');

		if ( $this->debug )
			echo '<b> >>>>>>>>>> MODE DEBUG : '. $this->debug .' <<<<<<<<<< </b><br /><br />';
	} // End function LinkToAA

	/**
	 * Définir les différents paramètres.
	 *
	 *	28/05/2009 GHX
	 *		- Prise en compte de l'identifiant du produit
	 *	23/03/2010 NSE bz 14831
	 *		- suppression du paramètre data_value inutilisé
	 *
    * 31/08/2010 MMT - DE firefox bz 17306
    *    - ajoute mode de lancement AA sans ActiveX via fichier .aacontrol
    *    - ajout parametre $this->useActiveX
    *
	 * Le paramètre doit passé doit être de la forme :
	 *	family|s|ta|s|ta_value|s|na|s|na_value|s|na_axe3|s|na_axe3_value|s|data_type@data_id|s|data_type@data_id@data_value|s|id_product|j|useActiveX
	 *
	 *	>>> |s| : correspond au séparateur pour le troisième axe [ get_sys_global_parameters('sep_axe3') ]
	 *	>>> ta : peut avoir uniquement les valeurs hour et day
	 *	>>> na : uniquement les niveaux d'agrégation dont le champ "link_to_aa" est à 1 dans sys_definition_network_agregation
	 *	>>> na_axe3 & na_axe3_value : peuvent être vide si la famille n'a pas de troisième axe (pour le moment ce n'est pas utilisé)
	 *	>>> data_type : peut avoir uniquement les valeurs raw et kpi
	 *	>>> data_id : correspond à l'identifiant dans sys_field_reference pour un RAW et sys_definition_kpi pour un KPI
	 *	>>> id_product : identifiant du produit sur lequel se trouve les infos vers AA
	 * >>> |j| : separateur de valeures fournies par Javascript
    * >>> useActiveX : javascript identifie si le client est capabale d'utiliser ActiveX (utilise IE), valeure 'true' ou 'false'
    *     voir fenetres_volantes.js
	 * @version CB4.1.0.00
	 * @param string $values
	 */
	public function setParameters ( $values ) {
		$separateur = get_sys_global_parameters('sep_axe3');

      // extrait tout d'abord les paramètres Javascripts, la premiere valeure est
      // toujours la liste de valeures pour lien AA
      $_JSvalues = explode(self::$JS_SEPARATOR, $values);
      $this->useActiveX = ($_JSvalues[1] == "true");

		$_values = explode($separateur, $_JSvalues[0]);

		$this->family        = $_values[0];
		$this->ta            = $_values[1];
		$this->ta_value      = $_values[2];
		$this->na            = $_values[3];
		$this->na_value      = $_values[4];
		$this->na_axe3       = ( !empty($_values[5]) ? $_values[5] : null );
		$this->na_axe3_value = ( !empty($_values[6]) ? $_values[6] : null );

		$values_data = explode('@', $_values[7]);
		$this->data = array (
							'type'  => $values_data[0],
							'id'    => $values_data[1],
						);

		// 15:57 28/05/2009 GHX
		// Prise en compte de l'identifiant du produit
		$this->id_product = $_values[8];
                // 31/01/2011 BBX
                // On remplace new DatabaseConnection() par Database::getConnection()
                // BZ 20450
		$this->db_connec = Database::getConnection($this->id_product);
		$this->db_connec->setDebug($this->debug);

		//09/06/2011 MMT bz 22322 : mapping inverse pour le NE dans les liens AA
		$na_value_unmapped = NeModel::getUnMappedNE($this->na,$this->na_value,$this->id_product);
		// si pas de mapping original sur le produit en cours on garde le NE
		if($na_value_unmapped === false){
			$debugNa_value = $this->na_value;
		} else {
			$debugNa_value = $this->na_value." (Unmapped: $na_value_unmapped)";
			$this->na_value = $na_value_unmapped;
		}

		//09/06/2011 MMT bz 22322 : ajout du mapping dans la debug si existe
		if ( $this->debug ) {
			echo  '>>>'.$values.'<<<<br />'
				. '<b>$this->id_product = </b>'. $this->id_product .'<br />'
				. '<b>$this->family = </b>'. $this->family .'<br />'
				. '<b>$this->ta = </b>'. $this->ta .'<br />'
				. '<b>$this->ta_value = </b>'. $this->ta_value .'<br />'
				. '<b>$this->na = </b>'. $this->na .'<br />'
				. '<b>$this->na_value = </b>'. $debugNa_value .'<br />'
				. '<b>$this->na_axe3 = </b>'. $this->na_axe3 .'<br />'
				. '<b>$this->na_axe3_value = </b>'. $this->na_axe3_value .'<br />'
            . '<b>$this->useActiveX = </b>'. $this->useActiveX .'<br />'
				. '<b>$this->data = </b>';
			echo '<pre>';
			print_r($this->data);
			echo '</pre>';
		}
	} // End function setParameters

	/**
	 * Lancer Activity Analysis
	 *
	 * @access public
	 */
	function launch () {
		// Récupère l'identifiant de la vue et le tag
		$this->viewAndTag();

		// Récupère toutes les données pour la création du filtre en deux parties
		$this->filter();
		$this->filter_contextuel();

		// Récupère la liste des arguments pour AAControl.exe 	host - port - login - password - database
		// il peut y avoir plusieurs bases/serveurs possibles à l'utilisateur de faire le choix
		$this->listServers();

		//Création du code HTML/JS qui permet de lancer AA
      // 31/08/2010 MMT - DE firefox bz 17306 - ajoute mode de lancement AA sans ActiveX via fichier .aacontrol
      if($this->useActiveX){
         echo $this->createCodeHtml();
      } else {
         echo $this->createNonActiveXCodeHtml();
      }
	} // End function launch

        // MPR : 26/10/2011 - Correction du bz 24403 - Creation de la méthode listTags()
	/**
         * Fonction qui retourne la liste de l'ensemble des tags existants pour le produit
         * @return type 
         */
        private function listTags()
        {
            // Requ$ete qui retourne l'ensemble des tags de l'application
            $queryTag = "SELECT DISTINCT saafk_tag FROM sys_aa_filter_kpi ORDER BY saafk_tag ASC";

            
            $resultTag = $this->db_connec->execute( $queryTag );

            $lstTags = array();
            while( $row = $this->db_connec->getQueryResults( $resultTag, 1 ) )
            {
                $lstTags[] = $row['saafk_tag'];
            }

            return $lstTags;
        } // End function listTags()
        
        // MPR : 26/10/2011 - Correction du bz 24403 - Creation de la méthode listTags()
        /**
         * Fonction qui retourne tous les tags possibles correspondants au tag passé en paramètre d'entrée
         * @param string $tagSource : tag source
         */
        private function generateAllTagsPossible( $tagSource )
        {
            // Récupération de tous les tags possibles
            $listTags = $this->listTags();
            
            // Pour chaque tag, on génère les différents Tags multiples possible
            // Exemple : CSPS / CSLOR actuellement en prod
            $lstTags[$tagSource] = array( $tagSource );
            foreach( $listTags as $tag )
            {
                
                if( $tag != $tagSource )
                {
                    $lstTags[$tagSource] = array_merge( $lstTags[$tagSource], array( $tag.$tagSource ,$tagSource.$tag ) );
                }
            }
            
            return array_unique( $lstTags[$tagSource] );
        } // End function generateAllTagsPossible()

        
	/**
	 * Récupère l'identifiant de la vue associé au raw/kpi ainsi que le tag en fonction de la donnée
	 *
	 *	16:01 28/05/2009 GHX
	 *		- Modif pour prendre en compte la classe DatabaseConnection()
	 *
	 * @version CB4.1.0.00
	 */
	private function viewAndTag () {
		$query_view = "
			SELECT saafk_idvue, saafk_tag
			FROM sys_aa_filter_kpi
			WHERE saafk_idkpi = '". $this->data['id'] ."'
				AND saafk_type = '". $this->data['type'] ."'
			";

		if ( $this->debug ) {
			echo '<b>$query_view = </b><pre>'. $query_view .'</pre>';
		}

		$result_view = $this->db_connec->getRow($query_view);
		if ( $result_view ) {
			if ( $this->db_connec->getNumRows() > 0 ) {
				$this->view = $result_view['saafk_idvue'];
				$this->tag = $result_view['saafk_tag'];
			}
			else // Logiquement cette erreur ne devrait jamais avoir lieu car si aucun résultat le lien vers AA ne devrait pas être présent dans le menu contextuel
				$this->error = __T('U_E_LINK_AA_NO_RESULT_VUE_FOR_FILTER');
		}
		else
			$this->error = __T('U_E_LINK_AA_SQL_INVALID_VUE_FOR_FILTER');

		if ( $this->debug ) {
			echo '<b>$this->view = </b>'.$this->view.'<br />';
			echo '<b>$this->tag = </b>'.$this->tag.'<br />';
		}
	} // End function viewAndTag

	/**
	 * Récupère toutes les données nécessaires pour pouvoir créer le fichier filtre par rapport au raw/kpi et la famille (dans le cas où une donnée à le même dans dans 2 familles différentes)
	 *
	 * 	16:04 28/05/2009 GHX
	 * 		- Modif pour prendre en compte la classe DatabaseConnection()
	 *
	 * @version CB4.1.0.00
	 */
	private function filter () {
		if ( $this->error !== null ) // S'il y a eu une erreur avant pas la peine d'aller plus loin
			return;

		$query_filter = "
			SELECT saac_name, saao_name, saalf_value, CASE WHEN saac_withcode THEN 'code' ELSE 'nocode' END AS index, saac_idcolumn
			FROM sys_aa_filter_kpi, sys_aa_list_filter, sys_aa_operator, sys_aa_column
			WHERE saafk_idfilter = saalf_idfilter
				AND saalf_idoperator = saao_idoperator
				AND saalf_idcolumn = saac_idcolumn
				AND saafk_idkpi = '". $this->data['id'] ."'
				AND saafk_type = '". $this->data['type'] ."'
				AND saafk_family = '". $this->family ."'
			ORDER BY saalf_order";

		if ( $this->debug ) {
			echo '<b>$query_filter = </b><pre>'. $query_filter .'</pre>';
		}

		$result_filter = $this->db_connec->execute($query_filter);
		if ( $result_filter ) {
			if ( $this->db_connec->getNumRows() > 0 ) {
				$_filter = array();
				while ( $row =  $this->db_connec->getQueryResults($result_filter, 1) ) {
					$_filter[$row['index']][] = array(
													'column'   => $row['saac_name'], // nom colonne
													'operator' => $row['saao_name'], // opérateur
													'value'    => $row['saalf_value'], // valeur
													'idcolumn' => $row['saac_idcolumn'] // identifiant de la column
												);
				}
				unset($result_filter);

				// si des conditions du filtre ont des codes colonnes
				if ( count($_filter['code']) > 0 ) {
					// Récupère le type du parser
					// 15:48 25/08/2009 GHX
					// Correction du BZ 11056
					// Il faut passé l'id du produit
					$module = get_sys_global_parameters('module', null, $this->id_product);
					// Pour chaque condition on va récupére les codes colonnes en fonction de value
					foreach ( $_filter['code'] as $index => $condition ) {
						$tmp_result = array();

						// modif 12:08 03/10/2007 Gwénaël
							//modification de la requete qui récupère les codes colonnes
						$replace_old = array(
									0 => '*',
									1 => ','
								);
						$replace_new = array(
									0 => '%',
									1 => '|'
								);

						/*
						 * La condition suivante (dans le WHERE) permet de faire une comparaison binaire en SQL
						 * 	saacc_interface & saai_interface > 0
						 *
						 * Exemples :
						 *	saai_interface = 20 (correspond à GSM & IU)
						 *
						 *	cas 1 :
						 * 		saacc_interface = 4 (correspond à GSM)
						 *		saacc_interface & saai_interface > 0 === TRUE
						 *		00100 & 10100 = 00100
						 *
						 *	cas 2 :
						 * 		saacc_interface = 8 (correspond à GPRS)
						 *		saacc_interface & saai_interface > 0 === FALSE
						 *		01000 & 10100 = 00000
						 */
						$query_condition = "
							SELECT saacc_aacode
							FROM sys_aa_column_code, (SELECT saai_interface FROM sys_aa_interface WHERE saai_module = '". $module ."') t0
							WHERE saacc_interface & saai_interface > 0
								AND saacc_idcolumn = '". $condition['idcolumn'] ."'
								AND saacc_fc_label SIMILAR TO '". str_replace($replace_old, $replace_new, $condition['value']) ."'
							";

						if ( $this->debug ) {
							echo '<b>$query_condition = </b><pre>'. $query_condition .'</pre>';
						}

						$result_condition = $this->db_connec->getAll($query_condition);
						foreach ( $result_condition as $row )
						{
							$tmp_result[] = $row['saacc_aacode'];
						}
						unset($result_condition);
						// Remplace la valeur par le contenu du tableau $tmp_result
						$_filter['code'][$index]['value'] = '('. implode(',', $tmp_result) .')';
					}

					if ( count($_filter['nocode']) > 0 ) { // Union des 2 tableaux
						$this->filter = array_merge($_filter['nocode'],$_filter['code']);
					}
					else
						$this->filter = $_filter['code'];
				}
				else // Les conditions n'ont pas de filtre spécifiques par rapport à des codes colonnes
					$this->filter = $_filter['nocode'];
			}
		}
		else // Erreur SQL
			$this->error = __T('U_E_LINK_AA_SQL_INVALID_FILTER');

		if ( $this->debug ) {
			echo '<b>$this->filter = </b><pre>';
			print_r($this->filter);
			echo '</pre>';
		}
	} // End function filter

        /**
	 * Récupère toutes les données nécessaires pour pouvoir créer le fichier filtre par rapport à la TA et la NA
	 *
	 *	- 09:21 17/08/2007 Gwénaël : on prend le label au lieu de la valeur de la NA
	 *	- 14:14 17/03/2008 Maxime : On récupère le filtre en fonction de son groupe
	 *	- 14:14 17/03/2008 Maxime : On prend en compte le 3ème axe
	 *
	 *	16:06 28/05/2009 GHX
	 *		- Modif pour prendre la classe DatabaseConnection()
	 *	- 10:19 10/12/2009 NSE : ajout de la condition IS NULL sur les groupes de filtres
	 *
	 * @version CB4.1.0.00
	 */
	private function filter_contextuel () {
            if ( $this->error !== null ) // S'il y a eu une erreur avant pas la peine d'aller plus loin
                return;

            // 14:44 18/12/2008 SCT : recherche de l'élément séparateur de la famille
            // NSE 22/02/2010 ajout de la condition sur la famille (comme en 4.0)
            $query_separator = 'SELECT separator FROM sys_definition_categorie WHERE family = \''.$this->family.'\'';
            $separator = $this->db_connec->getOne($query_separator);

            // 10/12/2009 NSE : ajout de la condition IS NULL sur les groupes de filtres pour que la requête retourne un résultat même si aucun groupe n'est défini. BZ 13342
            // 15/07/2011 MMT Bz 22810 saacf_before_value et saacf_after_value ne sont pas pris en compte
            // 09/12/2011 NSE DE new parameters in AA links contextual filters : ajout des colonnes si le slave les connait
            // 22/12/2011 NSE bz 25255 : saacf_after_value not available
            $query_contextuel_filter = "
                    SELECT DISTINCT saac_name, saacf_type, saacf_before_value, saacf_after_value".(CbCompatibility::isModuleAvailable('code_case_AALinks',$this->id_product)?", saacf_use_code, saacf_use_case":'')."
                    FROM sys_aa_contextuel_filter, sys_aa_column, sys_aa_filter_kpi
                    WHERE saacf_idcolumn = saac_idcolumn
                            AND saacf_idvue = '". $this->view ."'
                            AND (
                                    saacf_type = '". $this->na ."' ".( $this->na_axe3 != null ? "OR saacf_type = '".$this->na_axe3."'" : "" )."
                                    ". ( $this->ta == 'hour' ? " OR saacf_type = 'timestart' OR saacf_type = 'timeend'" : "" ) ."
                            )
                            AND (
                                    saacf_group_filter = saafk_group_filter
                                    OR (  saacf_group_filter IS NULL AND saafk_group_filter IS NULL )
                            )
                            AND saafk_idkpi = '".$this->data['id']."'
                    ";

            if ( $this->debug ) {
                echo '<b>$query_contextuel_filter = </b><pre>'. $query_contextuel_filter .'</pre>';
            }

            $filter_contextuel = array();
            $result_contextuel_filter = $this->db_connec->execute($query_contextuel_filter);
            if ( $result_contextuel_filter ) {
                if ( $this->db_connec->getNumRows() > 0 ) {
                    while ( $row = $this->db_connec->getQueryResults($result_contextuel_filter, 1) ) {

                        // Récupère la valeur pour le filtre
                        // dateAA est une fonction JS et pour qu'elle soit appelé elle doit en dehors de la chaine de caractère qui compose une ligne du filtre
                        // pour ça on ferme les guillemets -> concaténation avec le plus -> appelle fonction -> concaténation de nouveau -> réouverture des guillemets
                        // NE PAS SUPPRIMER les " et + sauf si modifs fait en conséquence
                        switch ( $row['saacf_type'] ) {
                            case 'timestart' :
                                $_operateur = '>=';
                                // 08/09/2010 MMT - bz 17732 :liens vers AA ne fonctionnent pas avec valeures horraires sous Firefox
                                // ne peut utiliser ActiveX pour recuperer format de date client
                                //utilisation d'un format fixe sous firefox, DE AA necessaire
                                if($this->useActiveX){
                                   // La date est créée en javascript pour pouvoir la mettre au format du poste client
                                   $_value = '"+dateAA(true)+"';
                                } else {
                                   $_value = $this->getTADateInFixedAAformat();
                                }
                                // 08/09/2010 MMT - DE liens Horraire
                                // si on a des filtres de date, utiliser parametre de hashing
                                $this->useHashingDateTimes = true;
                            break;

                            case 'timeend' :
                                $_operateur = '<=';
                                // 08/09/2010 MMT - bz 17732 :liens vers AA ne fonctionnent pas avec valeures horraires sous Firefox
                                if($this->useActiveX){
                                    // La date est créée en javascript pour pouvoir la mettre au format du poste client
                                    $_value = '"+dateAA(false)+"';
                                } else {
                                    $_value = $this->getTADateInFixedAAformat('5959');
                                }
                                // 08/09/2010 MMT - DE liens Horraire
                                // si on a des filtres de date, utiliser parametre de hashing
                                $this->useHashingDateTimes = true;
                            break;

                            // modif 17/09/2008 BBX :Spécificité HPG, si on se trouve sur tacsv, on split tac / sv. BZ 7427
                            case 'tacsv' :
                                // 15/07/2011 MMT Bz 22810 saacf_before_value et saacf_after_value ne sont pas pris en compte
                                $_operateur = '=';
                                // 09/12/2011 NSE DE new parameters in AA links contextual filters
                                if(isset($row['saacf_use_code']) && $row['saacf_use_code']==1){
                                    // on utilise obligatoirement le code
                                    $_array_value = explode('_',$this->na_value);
                                }
                                else{
                                   $_array_value = explode('_',$this->getNaLabel($this->na, $this->na_value));
                                }
                                if(isset($row['saacf_use_case']))
                                    $_array_value = StringModel::updateCase($row['saacf_use_case'],$_array_value);
                                $_value = $row['saacf_before_value'].(($row['saac_name'] == 'sv') ? $_array_value[1] : $_array_value[0]).$row['saacf_after_value'];
                            break;

                            // modif SCT 14:39 18/12/2008 : prise en compte des combinaisons pour le parser CORE
                            case 'nod1nod2' :
                                $_operateur = '=';
                                // 09/12/2011 NSE DE new parameters in AA links contextual filters
                                if(isset($row['saacf_use_code']) && $row['saacf_use_code']==1){
                                    // on utilise obligatoirement le code
                                    $_array_value = explode($separator,$this->na_value);
                                }
                                else
                                    $_array_value = explode($separator,$this->getNaLabel($this->na, $this->na_value));
                                if(isset($row['saacf_use_case']))
                                    $_array_value = StringModel::updateCase($row['saacf_use_case'],$_array_value);
                                // 11/01/2010 BBX : report de la correction du bug 8652 par SCT. BZ 13696
                                //$_value = ($row['saac_name'] == 'node1') ? $_array_value[0] : $_array_value[1];
                                // 22/12/2011 NSE bz 25255 : ajout des valeurs before et after
                                $_value = $row['saacf_before_value'].($row['saac_name'] == 'Node 1' ? $_array_value[0] : $_array_value[1]).$row['saacf_after_value'];
                                // Fin BZ 13696
                                // 14/12/2009 BBX : on indique que le NA doit être splitté. BZ 13300
                                $this->splitNa = true;
                            break;
                            
                            // NSE 16/12/2011 : Gestion pour Core PS
                            // Pour sgsnggsn, on décompose "SGSNRNC-GGSN" en "Src Node" et "Dest Node"
                            case 'sgsnggsn' :
								// 01/10/2013 MGO bz 36628 : recherche directe des parents car problème rencontré si on décompose l'élément réseau lorsque SGSN ou GGSN contiennent le séparateur
								$sgsnParent = "
									SELECT eoar_id_parent
									FROM edw_object_arc_ref
									WHERE
									eoar_arc_type = 'sgsnggsn|s|sgsn'
									AND eoar_id = '{$this->na_value}'";
								$ggsnParent = "
									SELECT eoar_id_parent
									FROM edw_object_arc_ref
									WHERE
									eoar_arc_type = 'sgsnggsn|s|ggsn'
									AND eoar_id = '{$this->na_value}'";
								
								if ( !($sgsnValue = $this->db_connec->getOne($sgsnParent)) or !($ggsnValue = $this->db_connec->getOne($ggsnParent))) {
									$this->error = __T('U_E_LINK_AA_SQL_INVALID_SERVER');
								}
								if ( $this->debug ) {
									echo '<b>$sgsnValue = </b>'. $sgsnValue .'<br />';
									echo '<b>$ggsnValue = </b>'. $ggsnValue .'<br />';
								}
                                $_operateur = '=';
                                // new parameters in AA links contextual filters
                                if(isset($row['saacf_use_code']) && $row['saacf_use_code']==1){
                                    // on utilise obligatoirement les codes
									$_array_value[] = $sgsnValue;
									$_array_value[] = $ggsnValue;
                                }
                                else{
									// on recupere les alias des elements parents
									$_array_value[] = $this->getNaLabel('sgsn',$sgsnValue);
									$_array_value[] = $this->getNaLabel('ggsn',$ggsnValue);
								}
                                if(isset($row['saacf_use_case']))
                                    $_array_value = StringModel::updateCase($row['saacf_use_case'],$_array_value);
                                // 22/12/2011 NSE bz 25255 : ajout des valeurs before et after
                                $_value = $row['saacf_before_value'].($row['saac_name'] == 'Src Node' ? $_array_value[0] : $_array_value[1]).$row['saacf_after_value'];
								// 01/10/2013 MGO bz 36628 : pas besoin de "spliter" le parent
                                //$this->splitNa = true;
                            break;
                            
                            case 'cic' :
                                $_operateur = '=';
                                // 09/12/2011 NSE DE new parameters in AA links contextual filters
                                if(isset($row['saacf_use_code']) && $row['saacf_use_code']==1){
                                    // on utilise obligatoirement le code
                                    $_array_value = explode($separator, $this->na_value);
                                }
                                else
                                    $_array_value = explode($separator,$this->getNaLabel($this->na, $this->na_value));
                                if(isset($row['saacf_use_case']))
                                    $_array_value = StringModel::updateCase($row['saacf_use_case'],$_array_value);
                                //NSE 22/02/2010 : ajout des versions en majuscules comme en 4.0
                                switch($row['saac_name'])
                                {
                                    case 'node1' :case 'Node 1' :
                                        $_value = $row['saacf_before_value'].$_array_value[0].$row['saacf_after_value'];
                                        break;
                                    case 'node2' :case 'Node 2' :
                                        $_value = $row['saacf_before_value'].$_array_value[1].$row['saacf_after_value'];
                                        break;
                                    case 'cic' :case 'CIC' :
                                        $_value = $row['saacf_before_value'].$_array_value[2].$row['saacf_after_value'];
                                        break;
                                }
                                // 14/12/2009 BBX : on indique que le NA doit être splitté. BZ 13300
                                $this->splitNa = true;
                            break;

                            case $this->na : // correspond à la NA
                                $_operateur = '=';
                                // 09/12/2011 NSE DE new parameters in AA links contextual filters
                                if(isset($row['saacf_use_code']) && $row['saacf_use_code']==1){
                                    // on utilise obligatoirement le code
                                    $_value = $this->na_value;
                                }
                                else
                                    $_value = $this->getNaLabel($this->na, $this->na_value);
                                if(isset($row['saacf_use_case']))
                                    list($_value) = StringModel::updateCase($row['saacf_use_case'],array($_value));
                                
                                $_value = $row['saacf_before_value'].$_value.$row['saacf_after_value'];
                                break;

                            case $this->na_axe3 : // correspond à la NA 3rd axis
                                $_operateur = '=';
                                // 09/12/2011 NSE DE new parameters in AA links contextual filters
                                if(isset($row['saacf_use_code']) && $row['saacf_use_code']==1){
                                    // on utilise obligatoirement le code
                                    $_value = $this->na_axe3_value;
                                }
                                else
                                    $_value = $this->getNaLabel($this->na_axe3, $this->na_axe3_value);
                                if(isset($row['saacf_use_case']))
                                    list($_value) = StringModel::updateCase($row['saacf_use_case'],array($_value));
                                
                                $_value = $row['saacf_before_value'].$_value.$row['saacf_after_value'];
                                break;

                        }

                        $filter_contextuel[] = array(
                                                    'column'   => $row['saac_name'], // nom colonne
                                                    'operator' => $_operateur, // opérateur
                                                    'value' => $_value, // opérateur
                                            );
                    }

                    if ( count($this->filter) > 0 )
                            $this->filter = array_merge($filter_contextuel, $this->filter);
                    else
                            $this->filter = $filter_contextuel;
                }
            }
            else // Erreur SQL
                $this->error = __T('U_E_LINK_AA_SQL_INVALID_FILTER');

            if ( $this->debug ) {
                echo '<b>$filter_contextuel = </b><pre>';
                print_r($filter_contextuel);
                echo '</pre>';
                echo '<b>$this->filter = </b><pre>';
                print_r($this->filter);
                echo '</pre>';
            }
	} // End function filter_contextuel

	/**
	 * Renvoie le label d'une niveau d'aggrégation en fonction de sa valeur
	 *
	 *	- 09:21 17/08/2007 Gwénaël : Ajout de la fonction
	 *	- 06/12/2007 Gwénaël : modification spécifique à Iub sur la NA cell
	 *	- 17/03/2008 Maxime : On prend en compte l'axe3 -> Ajout des deux paramètres na et na_value
	 *
	 *	16:13 28/05/2009 GHX
	 *		- Modif pour prendre en compte la classe DatabaseConnection()
	 *
	 * @version CB4.1.0.00
	 * @param string : niveau d'aggrégation réseau ou niveau d'aggrégation 3ème axe
	 * @param string : élément réseau ou élément 3ème axe
	 * @return string : le label de la na_value
	 */
	private function getNaLabel ($na, $na_value) {

		// 1 : on récupère la table dans laquel on doit aller chercher le label
		// 16:53 28/05/2009 GHX
		// Suppression de cette partie car maintenant il n'y a qu'une seule table de topo

		// 2 : on récupère le label
		$query_na_label = "
			SELECT DISTINCT eor_label
			FROM edw_object_ref
			WHERE
				eor_obj_type = '". $na ."'
				AND eor_id = '". $na_value ."'
			";

		if ( $this->debug ) {
			echo '<b>$query_na_label = </b><pre>'. $query_na_label .'</pre>';
		}

		$label = $this->db_connec->getOne($query_na_label);

		// On récupère l'id de l'élément si son label est vide
		$na_label = ( $label != "" and $label != null) ? $label : $na_value;

		if ( $this->debug ) {
			echo '<b>$na_label = </b>'. $na_label .'<br />';
		}

		// >>>>>>>>>>>>>>>
		/*
		 * modif 12:14 06/12/2007 Gwen
		 *
		 * CAS PARTICULIER : Spécifique à Iub
		 *
		 * Concernant Iub, les filtres contextuels ne sont pas suffisants pour la partie cellule. En effet, le champ sur lequel nous faisons
		 * le filtre contient une liste de cellule (ce qui correspond à un active set en UMTS)  ce qui nécessite de rajouter le caractère * avant et après le label de la cellule.
		 *
		 *    Ce mécanisme fonctionne uniquement sur le niveau Cell.
		 */
		/*if ( $this->module_parser == 'iub' ) {
			if ( $this->na == 'cell' ) {
				$na_label = '*'. $na_label .'*';
			}
		}*/
		// <<<<<<<<<<<<<<<

		// 3 : on renvoie le résultat
		return $na_label;
	} // End function getNaLabel

	/**
	 * Créer le code JS qui permet de créer le fichier filtre et le retourne sinon une chaine vide si aucune condition dans le filtre
	 * On a une ligne écrite par condition sur le filtre
	 * Format de la ligne :
	 *	nom_colonne  operande  valeur
	 *
	 * NOTE : les valeurs doivent être séparées par une tabulation
	 *
	 * @access private
	 * @return string
	 */
	function createFileFilter () {
		if ( count($this->filter) == 0 || $this->error !== null )
			return '';

		$js = 'var dirtmp = oActiveXfso.GetSpecialFolder(2);' // Récupère le chemin vers le dossier temporaire
			. 'filterfile = dirtmp+\'\\\\'. $this->COOKIE_NAME .'\';' // Nom du fichier filtre avec le chemin absolut
			. 'var handlefilterfile = oActiveXfso.CreateTextFile(filterfile, true);'; // Création du fichier, écrase le fichier s'il est déjà présent
		// 21/10/2010 NSE bz 18715 : on échappe les " présents dans le champ value si on n'est pas dans le cas d'une fonction javascript !
		foreach ( $this->filter as $f )
			$js .= 'handlefilterfile.WriteLine("'. $f['column'] ."\t". $f['operator'] ."\t". (preg_match('/"\s*\+/', $f['value'])?$f['value']:str_replace('"','\"',$f['value'])) .'");';
		$js .= 'handlefilterfile.close();'; // Fermeture de la ressource sur le fichier

		return $js;
	} // End function createFileFilter

	/**
	 * Retourne un booléen indiquant si l'on doit utiliser le 3ème axe pour AA
	 * Créé le 17/09/2008 par BBX. BZ 7427
	 *
	 *	16:15 28/05/2009 GHX
	 *		- Modif pour prendre en compte la classe DatabaseConnection()
	 *
	 * @version CB4.1.0.00
	 * @return bool
	 */
	private function useThirdAxis()
	{
		$link_to_aa_axe3 = false;
		$query_aa_axis = "SELECT link_to_aa_3d_axis FROM sys_definition_categorie WHERE family = '{$this->family}'";
		$array_aa_axis = $this->db_connec->getRow($query_aa_axis);
		// 16:04 03/08/2009 GHX
		// Correction du BZ 7427
		if($array_aa_axis['link_to_aa_3d_axis']=='t') $link_to_aa_axe3 = true;
		return $link_to_aa_axe3;
	}

	/**
	 * Retourne la valeur du niveau parent si on est sur le niveau minimum sinon la valeur contenu dans $this->na_value
	 * Exemple :
	 *	- si la na est SAI, la valeur retourné est la valeur du RNC
	 *	- si la na est RNC, la valeur $this->na_value est retourné
	 *
	 *	16:17 28/05/2009 GHX
	 *		- Modif pour prendre en compte la classe DatabaseConnection()
	 *
	 * @version CB4.1.0.00
	 * @return string
	 */
	private function getNaValue () 
        {
            // modif 17/09/2008 BBX : on regarde si on doit prendre en compte le 1er axe ou le 3eme axe
            if($this->useThirdAxis()) return $this->na_axe3_value;

            // 16/01/2012 BBX
            // BZ 25296 : requête qui récupère les parents avec AA activé
            // Si plusieurs parents, le parent de level et rank minimum est retourné
            $query = "SELECT agregation
            FROM sys_definition_network_agregation
            WHERE family = '{$this->family}'
            AND link_to_aa = 1
            AND level_source = '{$this->na}'
            AND agregation != '{$this->na}'
            ORDER BY agregation_level ASC, agregation_rank ASC
            LIMIT 1";
            $naParent = $this->db_connec->getOne($query);

            // Debug
            if ( $this->debug ) {
                    echo '<b>Query parents = </b><pre>';
                    print_r($query);
                    echo '</pre>';
                    echo '<b>$naParent = </b>'. (empty($naParent) ? 'No parent with AA links enabled' : $naParent) .'<br />';
            }

            // 16/01/2012 BBX
            // BZ 25296 : Si pas de parent, on retourne l'élément courant
            if(empty($naParent))
            {
                return $this->na_value;
            }
            // Sinon, on retourne le parent
            else
            {
                // 16:59 28/05/2009 GHX
                // Modification pour récupérer le parent
                $queryParent = "
                SELECT eoar_id_parent
                FROM edw_object_arc_ref
                WHERE
                eoar_arc_type = '{$this->na}|s|{$naParent}'
                AND eoar_id = '{$this->na_value}'";

                // Débug
                if ( $this->debug ) {
                    echo '<b>$queryParent = </b><pre>'. $queryParent .'</pre>';
                }

                // maj 27/01/2010 : Correction du BZ13934 : Modification de la condition lorsque le NA max ne possède pas de liens vers AA
                if ( !($parentValue = $this->db_connec->getOne($queryParent))) {
                    $this->error = __T('U_E_LINK_AA_SQL_INVALID_SERVER');
                }

                if ( $this->debug ) {
                    echo '<b>$parentValue = </b>'. $parentValue .'<br />';
                }

                return $parentValue;
            }
	} // End getNaValue

	/**
	 * Créer la(les) liste(s) des paramètres nécessaire à AACrontol.exe
	 *
	 *	 - modif 11:28 11/03/2008 Gwen : recherche des bases sans NA spécifiques dans le cas où on ne trouve pas de base pour la NA
	 *	 - modif 17:05 25/10/2007 Gwen : ajout des fonctions MIN et MAX dans le SELECT + ajout du GROUP BY
	 * 	 - modif 13:30 23/10/2007 Gwen : prise en compte du tag CSPS
	 *
	 *	16:17 28/05/2009 GHX
	 *		- Modif pour prendre en compte la classe DatabaseConnection()
	 *
	 * @version CB4.1.0.00
	 */
	private function listServers () {
		if ( $this->error !== null ) // S'il y a eu une erreur avant pas la peine d'aller plus loin
			return;

		// modif 13:30 23/10/2007 Gwen
			// Ajout d'une condition dans le WHERE : saab_tag = 'CSPS'
			// et ajout de la condition $this->tag != 'CSPS' : si on a le tag CSPS cele revient à choisir toutes les bases donc pas bession de présicer le tag
		// modif 17:01 25/10/2007 Gwen
			// Ajout des fonctions MIN et MAX [bug: 5224 : Plusieurs liens vers AA pointant vers meme base]


		// 14/12/2009 BBX
		// DEBUT BZ 13300
		// Récupération du séparateur de la famille
		$query_separator = 'SELECT separator FROM sys_definition_categorie WHERE family = \''.$this->family.'\'';
		$separator = $this->db_connec->getOne($query_separator);
		// Préparation des éléments de la condition sur le NA (sys_aa_base)
		$saabNaOperator = " = ";
		$saabNaCondition  = "'".$this->getNaValue()."'";
		// Si on doit splitter l'élément réseau, alors on fait un IN sur les éléments découpés
		if(!empty($separator) && $this->splitNa)
		{
			$saabNaOperator = " IN ";
			$saabNaCondition = "('".implode("','",explode($separator,$this->getNaValue()))."')";
		}
		// Pour saab_na, l'opérateur et la valeur sont désormais calculés ci-dessus
		$query_server = "
			SELECT saas_host, saas_port, saas_login, saas_password, saab_database, MIN(saab_hourstart) AS hourstart, MAX(saab_hourend) AS hourend, saab_tag
			FROM sys_aa_server, sys_aa_base
			WHERE saas_idserver = saab_idserver
				AND saab_ta = '". substr($this->ta_value, 0, 8) ."'
				AND saab_na ".$saabNaOperator." ".$saabNaCondition;
                
                $saabTagsCondition = "";
                // MPR : 26/10/2011 - Correction du bz 24403
                // Suppression de la condition écrite en dur avec CSPS et ajout d'un traitement générique sur les tags AA    
                if( !empty( $this->tag ) )
                {
                    $saabTags = array();
                
                    $saabTagsCondition = " AND ( ";
                    
                    foreach ( $this->generateAllTagsPossible( $this->tag ) as $tag )
                    {
                        $saabTags[] = "'{$tag}'";
                    }
                    $saabTagsCondition.= " saab_tag IN (". implode( ",", $saabTags ).") OR saab_tag IS NULL OR saab_tag = '' )";
                }
                // ( empty($this->tag )&& $this->tag != 'CSPS' ? '' : "AND (saab_tag = '". $this->tag ."' OR saab_tag = 'CSPS' OR saab_tag IS NULL OR saab_tag = '')" ) ."
                $query_server .= $saabTagsCondition;
                
		// FIN BZ 13300

		if ( $this->ta == 'hour' ) {
			$_hour = substr($this->ta_value, -2);
			// modif 16:00 22/08/2007 Gwénaël
				//modif de la condition WHERE
			// modif 09:49 03/09/2007 Gwénaël
				// ajout de la condition BETWEEN dans le WHERE
			$query_server .= "
				AND (
					'". $_hour ."00' BETWEEN saab_hourstart AND saab_hourend
					OR (
						saab_hourstart LIKE '". $_hour ."%'
						OR saab_hourend LIKE '". $_hour ."%'
						AND saab_hourend NOT LIKE '%00'
					)
				)
				";
		}

		// 17:00 25/10/2007 Gwen
			// Ajout du GROUP BY suite à l'utilisation des fonctions MIN et MAX dans le SELECT
		$query_server .= " GROUP BY saas_host, saas_port, saas_login, saas_password, saab_database,saab_tag";

		if ( $this->debug ) {
			echo '<b>$query_server = </b><pre>'. $query_server .'</pre>';
		}

		if ( $result_server = $this->db_connec->execute($query_server) ) {
			if ( $this->db_connec->getNumRows() > 0 ) {

				// >>>>>>>>>>
				// modif 11:15 11/03/2008 Gwen
					// Si aucun résultat on regarde s'il n'y a pas des bases qui n'ont pas de NA spécifiés (NA == null)
				if ( $this->db_connec->getNumRows() == 0 ) {
					$query_server = str_replace("saab_na = '". $this->getNaValue() ."'", "(saab_na IS NULL OR saab_na = '')", $query_server);

					if ( $this->debug ) {
						echo '<b>Recherche des bases sans NA spécifiés</b><br />$query_server BIS =<pre>'.$query_server.'</pre>';
					}

					if ( !($result_server == $this->db_connec->execute($query_server)) ) {
						$this->error = __T('U_E_LINK_AA_SQL_INVALID_SERVER');
						return;
					}
				}
				// <<<<<<<<<<
				while ( $row = $this->db_connec->getQueryResults($result_server, 1) ) {
					$this->servers[] = array(
											'host'      => $row['saas_host'],
											'port'      => $row['saas_port'],
											'login'     => $row['saas_login'],
											'pwd'       => $row['saas_password'],
											'db'        => $row['saab_database'],
											'tag'       => $row['saab_tag'],
											'hour_start'=> ( isset($row['hourstart']) ? $row['hourstart'] : null ),
											'hour_end'  => ( isset($row['hourend']) ? $row['hourend'] : null )
										);
				}
			}
			else // Si aucun résultat, il sera impossible de se connecter à une base avec AA
				$this->error = __T('U_E_LINK_AA_NO_RESULT_SERVER');
		}
		else // Erreur SQL
			$this->error = __T('U_E_LINK_AA_SQL_INVALID_SERVER');

		if ( $this->debug ) {
			echo '<b>$this->servers = </b><pre>';
			print_r($this->servers);
			echo '</pre>';
		}
	} // End function listServers

	/**
	  * Retourne le code Javascript qui permet de lancer AA via ActiveX
	 * Le code JS
	 * 	- récupère dans la base de registre le dossier vers AAControl.exe
	 *	- création du fichier filtre
	 *		- on récupère le chemin vers le dossier temporaire de Windows dans lequel on va écrire le fichier filtre
	 *		- on crée le fichier s'il existe celui-ci est écrasé.
	 *		- on écrit dedans toutes les conditions du filtre (une ligne par condition)
	 *	- lance AA
	 *
	 * @access private
	 * @return string
	 *
	 *	17:05 07/12/2009 NSE/GHX BZ 13349
	 *		- Modif pour prendre en compte les formats de jour et mois à 1 chiffre (US : M/d/YY) : dateAA.replace(/([mM]{2})/,month); et dateAA.replace(/([dD]{2})/,day); => dateAA.replace(/([mM]{1,2})/,month); et dateAA.replace(/([dD]{1,2})/,day);
    *
    * 31/08/2010 MMT - DE firefox bz 17306 - deplace CSS & HTML commun dans createGenericHTML
    *
	 */
	function createCodeHtml () {

      // 31/08/2010 MMT - DE firefox bz 17306 - deplace CSS & HTML commun dans createGenericHTML

		// ***** JS ***** //
		$js = '
			var error = false;
			var oActiveX = null;
         // 10/09/2010 MMT DE liens horraire deplacement oActiveXfso pour utilisation
         var oActiveXfso = null;
			var dirAA = null;
			var filterfile = null;

			function init () {
            // 10/09/2010 MMT DE liens horraire deplacement oActiveXfso pour utilisation
				var step = 0;
				try {
					oActiveX = new ActiveXObject("Wscript.Shell");
					oActiveXfso = new ActiveXObject("Scripting.FileSystemObject");
					// Récupère le répertoire dans lequel est installé AA donc là où se trouve AAControl.exe
					step = 1;
					dirAA = oActiveX.RegRead("'. $this->HKEY_AA .'"); // Récupère dans la base de registre le lien vers Analysis.exe afin de récupérer le chemin pour AAControl.exe
					dirAA = oActiveXfso.GetParentFolderName(dirAA);
					// Création du fichier filtre dans le dossier temporaire de Windows
					step = 2;
					'. $this->createFileFilter() .'

				}
				catch ( e ) {
					if ( oActiveXfso == null || oActiveX == null ) {
						document.getElementById("cannotBeLaunched").style.display = "block";
						document.getElementById("error").style.display = "none";
						if ( document.getElementById("listServer") != null )
							document.getElementById("listServer").style.display = "none";
					}
					else {
						var strMsgError = "";
						switch ( step ) {
							case 1 : strMsgError = "'. __T('U_E_LINK_AA_CANNOT_FIND_AA') .'"; break;
							case 2 : strMsgError = "'. __T('U_E_LINK_AA_CANNOT_CREATE_FILE_FILTER') .'"; break;
						}
						displayError(strMsgError + " '. __T('U_E_LINK_AA_CONTACT_ADMIN') .'");
					}
					error = true;
				}
			}

         // 31/08/2010 MMT - DE firefox bz 17306 - deplace function displayError dans createGenericHTML

			// Lancer AAControl s\'il n\'y a pas eu d\'erreur avant
         // 08/09/2010 MMT - DE liens Horraire, ajout param Hashing
			function launchAA ( host, port, login, pwd, db, hashing ) {
				if ( error == true ) return;
				try {
               // 10/09/2010 MMT DE liens horraire ajout de test sur version de AAcontrol
               aaControlFile = dirAA + "\\\AAcontrol.exe";
               activeXcmd = \'"\'+ aaControlFile + \'" -host \'+host+\' -port \'+port+\' -login \'+login+\' -pwd \'+pwd;
               activeXcmd += \' -db \'+db+\' -view '. $this->view .' '. ( count($this->filter) > 0 ? ' -filtersfile \'+filterfile' : '\'' ) .'

               // paramètre hashing ne doit pas être envoyé si la version installé de AA ne le supporte pas ou les filtres
               // ne sont plus pris en compte par AA
               // récuperation de la version de AAcontrol avec ActiveX
               clientAAversion = oActiveXfso.GetFileVersion(aaControlFile);
               minVersionForHashing = "'.get_sys_global_parameters('version_AAcontrol_firefoxAndhashing').'";

               // compare la version mini avec la version actuelle
               if(hashing != null && compareAAVersions(minVersionForHashing,clientAAversion) >= 0){
                  // ajoute le paramètre hashing si OK
                  activeXcmd += \' -hashing \'+hashing ;
               }

					'. ( $this->debug == 2 ? '//' : '' ) .'oActiveX.Run(activeXcmd, 0, true);
               '. ( $this->debug ? '' : 'self.close();' ) .'

               // 08/09/2010 MMT fin changements
				}
				catch ( e ) {
					displayError("'.__T('U_E_LINK_AA_CANNOT_LAUNCH_AA').'");
				}
			}


         // compare deux version de software de format x.y ex : 3.15.2, 4.0, 12.5..
         // retourne -1 si v1 > v2, 1 si v1 < v2 et 0 si v1 = v2
         function compareAAVersions(v1,v2){
            ret = 0;
            nums1 = v1.split(".");
            nums2 = v2.split(".");

            // si pas la meme longueur de version, par default, la plus longue est superieure ( 3.2 < 3.2.5)
            if(nums1.length > nums2.length){
               ret = -1;
            } else if(nums1.length < nums2.length){
               ret = 1;
            }

            // compare elements un après les autre
            for(i=0;i<nums1.length;i++){
               if(i<nums2.length){
                  n1 = parseInt(nums1[i]);
                  n2 = parseInt(nums2[i]);
                  // stop si une valeure est différente
                  if(n1 > n2){ret = -1;break;}
                  if(n1 < n2){ret = 1;break;}
               }
            }

            return ret;
         }


			// Renvoie la date et l\'heure dans le format du poste client
			// start = true si c\'est l\'heure de début et false si c\'est l\'heure de fin
			function dateAA ( start ) {
				try {
					// DATE
					var dateTA = "'. $this->ta_value .'";
					var dateAA = oActiveX.RegRead("HKEY_CURRENT_USER\\\\Control Panel\\\\International\\\\sShortDate");
					var expression = /(\w{4})(\w{2})(\w{2})/;
					expression.exec(dateTA);
					var year  = RegExp.$1;
					var month = RegExp.$2;
					var day   = RegExp.$3;
					dateAA = dateAA.replace(/([yY]{2,4})/, year);
					dateAA = dateAA.replace(/([mM]{1,2})/,month);
					dateAA = dateAA.replace(/([dD]{1,2})/,day);

					// HOUR
					var hourTA = "'. ( strlen($this->ta_value) == 10 ? substr($this->ta_value, -2) : '' ) .'";
					var hourAA = "";
					if ( hourTA != "" ) {
						if ( start )
							var d = new Date(year, month, day, hourTA);
						else
							var d = new Date(year, month, day, hourTA, 59, 59);
						hourAA = " " + d.toLocaleTimeString();
					}
					return dateAA + hourAA;
				}
				catch ( e ) {
					displayError("'. __T('U_E_LINK_AA_FORMAT_DATE_HOUR') .'");
				}
			}

			// Permet d\'attendre que la page soit entièrement charger avant de lancer le script
			init();';


       // 31/08/2010 MMT - DE firefox bz 17306 - deplace code commun dans createGenericHTML
       return $this->createGenericHTML('getActiveXLaunchJsForSever',$js);
	} // End function createCodeHtml

   /**
    * Genere le code HTML/JS/CSS generic aux deux type de lancement de AA: ActiveX et .aacontrol
    * Retourne le code Javascript qui permet de lancer AA, le code HTML+CSS
    * (pour l'affichage des erreurs ou pour le choix des bases/serveurs)
    *
    * @global <type> $niveau0
    * @param function $getLaunchJsForSeverCallback callback générant le code JS a
    *   executer pour le liens sur le serveur donné
    * @param String $extraJs optionel, additionel JavaScript Code a inserer dans la page
    * @return String HTML+JS+CSS à afficher
    */
   function createGenericHTML($getLaunchJsForSeverCallback,$extraJs='')
   {
      // ****** CSS ***** //
		$css = '
			<style type="text/css">
				.red {color:red}
				#cannotBeLaunched, #error {display:none; margin:10px}
				#cannotBeLaunched ul {margin:0; padding:7px 0 0 25px; list-style-type:square}
				#msgError {padding:10px}
				#listServer {margin:10px}
				#listServer ul {list-style-type:none; margin:10px}
				//a:hover {font-weight:bold}
            a:hover {text-decoration:underline}
            #noActiveXWarningTxt {font-weight:none}
			</style>';

		// ***** HTML **** //
		$html = '
			<div id="cannotBeLaunched" class="texteGrisBold">
				<span class="red">'. __T('U_E_LINK_AA_CANNOT_LAUNCH_AA') .'</span>
				<ul>
					<li>'. __T('U_E_LINK_AA_ACTIVEX') .'</li>
					<li>'. __T('U_E_LINK_AA_NOT_SITE_CONFIDENCE') .'</li>
				</ul>
			</div>
			<div id="error">
				<fieldset class="texteGrisBold"><legend class="red">&nbsp;Error&nbsp;</legend>
				<div id="msgError"></div>
				</fieldset>
			</div>';
      // ***** JS **** //
      $js = '
      // Affiche un d\'erreur
			function displayError ( msg ) {
				if ( error == true ) return;
				error = true;
				document.getElementById("error").style.display = "block";
				document.getElementById("msgError").innerHTML = msg;
				if ( document.getElementById("listServer") )
					document.getElementById("listServer").style.display = "none";
			}
      ';
      $js .= $extraJs;

      // ***** LANCEMENT DE AA ***** //

      if ( $this->error === null ) {
         if ( count($this->servers) == 1 ) { // S'il n'y a qu'un seul choix possible on lance directement AA...
            $js .= call_user_func(array($this,$getLaunchJsForSeverCallback),$this->servers[0]);
         }
         else {

            global $niveau0;
            $html .= '<div><fieldset id="listServer" >
                  <legend class="texteGrisBold">&nbsp;'. __T('U_LINK_AA_CHOICE_BASE_SERVER') .'&nbsp;</legend>
                  <ul>';
            foreach ($this->servers as $s) {
               // utilise callback pour la fonction onclick du lien, valeure depends de l'utilisation de ActiveX ou non
               $html .= '
                  <li>
                     <img src="'. $niveau0 .'images/icones/small_puce_fieldset.gif"/>&nbsp;
                     <a href="javascript:void(0);" onclick="'.call_user_func(array($this,$getLaunchJsForSeverCallback),$s).'">'
                     . $s['db'] .' ['. $s['host'] .']'
                     . (!empty($s['tag']) ? ' - '. $s['tag'] .' - ' : '' )
                     . ' ('. ereg_replace('([0-9]{2})([0-9]{2})', '\\1:\\2', $s['hour_start']) .' / '. ereg_replace('([0-9]{2})([0-9]{2})', '\\1:\\2', $s['hour_end']) .')</a>
                  </li>';
            }
            $html .= '</ul></fieldset></div>';
         }
      }else{
			$js .= 'displayError("'. $this->error .'");';
      }
      $js = '<script language="javascript" type="text/javascript">'. $js .'</script>';
		return $css.$html.$js;
   }

   /**
    * Callback pour la methode 'createGenericHTML'
    * Lance l'execution de AA via ActiveX sur le serveur donné
    * @param Array $s tableau contenant les parametres du serveur
    * @return string JS code a executer
    */
   function getActiveXLaunchJsForSever($s)
   {
      // 08/09/2010 MMT - DE liens Horraire, ajout param Hashing si en TA horraire
      $hashing = 'null';
      if($this->useHashingDateTimes){
         // utilisation du format de date utilisé pour filtres start et end
         $hashing = "dateAA(true) + ';' + dateAA(false)";
      }
      return 'launchAA(\''. $s['host'] .'\',\''. $s['port'] .'\',\''. $s['login'] .'\',\''. $s['pwd'] .'\',\''. $s['db'] .'\','. $hashing .');';
   }

   /**
    * Callback pour la methode 'createGenericHTML'
    * Lance la création du fichier .aacontrol et créer le code js a executer pour le
    * Serveur passée en paramètre
    * @param Array $s tableau contenant les parametres du serveur
    * @return string JS code a executer
    */
   function getNonActiveXLaunchJsForSever($s)
   {
      $file = $this->createAAControlFileContent($s);

      // utilise le export_file.php existant
      $exportURL = NIVEAU_0.'dashboard_display/export/export_file.php?file='.base64_encode($file);

      // lance telechargement du fichier
      $js = 'document.location.href = \''.$exportURL.'\';';
      return $js;
   }

   /**
    * retourne HTML de la page dans le cas ou l'on utilise pas ActiveX
    * cette page doit afficher un message d'explication sur le fait que l'utilisateur doit executer le fichier
    * .aacontrol
    * @return String HTML de la page
    */
   function createNonActiveXCodeHtml ()
   {
      $html = $this->createGenericHTML('getNonActiveXLaunchJsForSever');
      // 10/09/2010 MMT - DE liens Horraires, ajout version_AAcontrol_firefoxAndhashing pour message warning
      $warnMsg = '<div><fieldset class="texteGrisBold" style="margin:10px"><legend >&nbsp;Warning&nbsp;</legend>
            <p style="font-weight:normal" > '.__T('U_E_LINK_AA_WARNING_NO_ACTIVEX',get_sys_global_parameters('version_AAcontrol_firefoxAndhashing')).'</p>
            <p align="center" >
               <a href="javascript:window.close();" >
                  Click here to close the window
               </a>
            </p>
				</fieldset></div>';

      // le warning doit être placé après la liste dans le cas ou il y a plusieurs servers
      if ( count($this->servers) > 1) {
         $html = $html.$warnMsg;
      } else {
         $html = $warnMsg.$html;
      }
      return $html;
   }


   /**
    * Creer un fichier .aacontrol temporaire qui peut être téléchargé par l'utilisateur
    * et executé par AAcontrol.exe pour lancer AA avec les paramètres contenu
    * @param Array $server tableau contenant les parametres du serveur
    * @return String  retourne chemin et nom complet (physique) du fichier créer
    */
   function createAAControlFileContent($server)
   {
      // dans notre cas, le fichier .aacontrol ne contient toujours qu'une configuration
      // avec toujours le même nom
      $fileContent  = "[".self::$AACTRL_DEFAULTCONFIG."]\n";
      $fileContent .= "Host=".$server['host']."\n";
      $fileContent .= "Login=".$server['login']."\n";
      $fileContent .= "Port=".$server['port']."\n";
      $fileContent .= "Database=".$server['db']."\n";
      $fileContent .= "View=".$this->view."\n";

      // 08/09/2010 MMT - pas de param si pas de filtres.
      if(count($this->filter) > 0){
         $fileContent .= "Filter=".$this->getAAcontrolFileFilterParamValue()."\n";
      }

      // 08/09/2010 MMT - DE liens Horraire, ajout param Hashing.
      if($this->useHashingDateTimes){
         $fileContent .= "Hashing=".$this->getTADateInFixedAAformat().";".$this->getTADateInFixedAAformat('5959')."\n";
      }

      $workdir = REP_PHYSIQUE_NIVEAU_0.'upload/';
      $filename = self::$AACTRL_FILE_PREFIX.uniqid().'.aacontrol';

      // MMT 08/09/2010 netoyage
      // ecrit fichier
      file_put_contents($workdir.$filename,$fileContent);

      if ( $this->debug ){
         echo '<b>'.$server['db'].'['.$server['host'].'] generated .aacontrol file = </b><pre>'. $workdir.$filename .'</pre>';
         echo '<pre>'.str_replace('\n','<br>',$fileContent).'</pre>';
      }

      return $workdir.$filename;
   }


   /**
    * retourne la valeure du paramètre "Filter" pour le fichier .aacontrol
    * en concatenant les filtres avec le separateur filterSeparator
    *
    * @return String valeure du paramètre "Filter3 pour fichier aacontrol
    */
   function getAAcontrolFileFilterParamValue()
   {
      $ret = "";

      // separateur de filtre AA est un paramètre global
      $filterSeparator = get_sys_global_parameters('sep_AAcontrol_filter');

      // 08/09/2010 MMT - DE liens Horraire + bz 17732.
      // changement de format AA, ajout du delimiteur '"' autour des filtres si > 1
      $delim = '"';
      if(count($this->filter) == 1){
          $delim = '';
      }
        
        // 19/10/2011 BBX
        // BZ 24270 : ajout d'espaces autour de l'opérateur
        foreach ( $this->filter as $f ) {
            $ret .= $delim.$f['column'].' '.$f['operator'].' '.$f['value'].$delim.$filterSeparator;
      }
      $ret = substr($ret,0,-strlen($filterSeparator));
		return $ret;
   }

    // 08/09/2010 MMT - DE liens Horraire + bz 17732 :liens vers AA ne fonctionnent pas avec valeures horraires sous Firefox
   /**
    * permet de récuperer la valeur de la date et l'heure selectionné dans le selecteur au format de date fixe AA
    * Voir DE liens horraire et DE firefox
    *
    * @param String $MMSS_value optionel, valeure des Minutes et secondes
    * @return string valeure au format fixe, prédefinit avec AA
    */
   function getTADateInFixedAAformat($MMSS_value='0000')
   {
      // $dateTAFormat toujours au format yyyymmddHH
      $date_TAFormat = $this->ta_value;

      // recuperation du prefixe
      $fixedDatePrefix = get_sys_global_parameters('prefix_AAcontrol_fixedDateFormat');

      $ret = $fixedDatePrefix.$date_TAFormat.$MMSS_value;
      return $ret;
   }

} // End class LinkToAA
?>
