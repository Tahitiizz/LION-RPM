<?
/*
*	@cb41000@
*
*	14/11/2007 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*	- 26/11/2008 GHX : réécriture de la classe en entière.
*	- maj 11/06/2009 MPR : Correction des bugs 9645/9646 - Remplacement de NR > 1 par NR >= 1 dans le cas où le fichier comporte une seule ligne de données topologiques
*	- maj 08/07/2009 MPR :: Correction du bug 10350 : Le niveau minimum du fichier ne doit pas être null
*
*	21/08/2009 GHX
*		- Correction du BZ 11050 => on met tout le header en minuscule
*	11:53 18/09/2009 GHX
*		- Correction du BZ 11524 [REC][T&A CB 5.0][PARSER MOTOROLA] Update de la topologie via le menu admin
*			-> On prend en compte que les colonnes on_off/azimuth/longitude/latitude sont toujours des colonnes présents du coup plus besoin des les définir pour chaque produit dans la table edw_object_ref_header
*	21/09/2009 - MPR : Correction du bug 11050 : On récupère le code de l'élément réseau - Celui-ci peut être différent du label (   Exemple : usercluster1 <> usercellcluster1,...) 
*	10/12/2009 - MPR : Correction du buh 13196 : Contrôle d'unicité sur les labels 
*	13/01/2010 MPR -> Correction du bug 13719 - Check sur le nombre d'éléments réseau 3ème axe uniquement en mode manuel
*	05/03/2010 : MPR - Correction du BZ 14553 / Toutes les erreurs n'étaient pas affichées
*	23/04/2010 NSE bz 14046 : correction requète SQL pour déterminer la famille du fichier de topo
*	21/04/2011 NSE DE Non unique Labels : suppression des tests sur les labels en doubles si l'option est définie pour le NA
*	24/09/2012 ACS BZ 28781 Topology update requires two upload in order to swap labels
*/
?>
<?
/**
 * Classe permettant de vérifier la cohérance d'un fichier de topologie avant son intégratoin.
 * Certains erreurs entraines l'arret des checks suivant (ceux sur la structure et l'entete du fichier)
 *
 * Liste des différents checks effectuées
 *	- vérification sur le délimiteur
 *	- vérification de l'entête du ficheir (colonnes non null; colonnes en doublons; colonnes manquantes...)
 *	- vérification des relations entres les différents niveaux d'aggrégation de l'entête
 *	- vérification sur l'unicité des niveaux maximums si spécifié
 *	- vérification sur les labels (unique pour un élément) => pas de vérification sur des labels de niveau différents
 *	- vérification sur les valeurs du on/off
 *	- vérification entre les niveaux fils<-> parent (un seul parent...)
 *
 *	ATTENTION : les vérifications sont lancés dès l'instanciation de la classe
 *
 * @version 4.1.0.00
 * @package Topology
*/
class TopologyCheck extends TopologyLib
{
	/**
	 * Tableau contenant les uniquement les éléments réseaux présent dans l'entete du fichier
	 * @var array
	 */
	private $naInHeader = array();

	/**
	 *  Tableau contenant des colonnes qui ne sont pas éléments réseaux
	 * @var array
	 */
	
	private $elementsNoNa = array('on_off','longitude','latitude','azimuth','x', 'y');
	
    private $_productId;
    
	/**
	 * Construteur
     * 
     * 21/04/2011 NSE DE Non unique Labels : ajout de l'id product facultatif
	 */
	public function __construct($productId='')
	{
		parent::__construct();

        $this->_productId = $productId; //empty($productId) ? 1 : $productId;
        
		// maj  14/10/2009  - MPR : Ajout de trx et charge lorsque 
		if( self::$activate_capacity_planing && self::$activate_trx_charge_in_topo )
		{
			$this->elementsNoNa[] = "trx";
			$this->elementsNoNa[] = "charge";
		}
		
		// maj 01/07/2009 : MPR - Correction du bug 10340 La table 
		$query = "DROP TABLE IF EXISTS ".self::$table_ref."_tmp";
		$this->sql($query);
		
		// maj 07/08/2009 - Correction du bug 7461
		// $this->cleanFile();
		
		$this->check();
	} // End function __construct
	
	/**
	 * Fonction qui exécute tous les checks
	 *
	 * @return array : Tableau contenant la liste des erreurs rencontrées
	 */
	private function check()
	{
		// 01/10/2012 BBX
		// BZ 29059 : on part du principe que la table n'existe pas
		$tableTempExists = false;
		
		try
		{
			// Check qui dès qu'on a une erreur arrete les check suivants
			$this->checkDelimiter();
			$this->checkHeaderColumns();
			
			// Création d'une table temporaire avec les éléments du fichier
			$tableTempExists = $this->createTableTemp();
			$this->insertFileIntoTableTemp();
			
			// maj 17:41 14/10/2009 : MPR : On check la présence du niveau minimum de la famille principale lorsque l'on a les colonnes trx et charge dans le fichier
			$this->checkNaMinInFile();
			$this->checkRelationNA();
			$this->checkNbElementsThirdAxis();
		}
		catch ( Exception $e )
		{
			$this->demon('<h3 style="color:#fff;background-color:#f00">ERREURS STRUTURE DU FICHIER ou HEADER</h3>');
			$this->demon(self::$errors);
			// maj 11:00 05/03/2010 : MPR - Correction du BZ 14553 / Toutes les erreurs n'étaient pas affichées
			// Suppression du return
			//return self::$errors;
			// 01/10/2012 BBX
			// BZ 29059 : on effectue le return si la table temporaire n'a pas pu être créée
			if(!$tableTempExists) return self::$errors;
		}
		
		
		// maj 15/10/2009 - MPR :Modification de la fonction checkOnOff pour l'adapter à n'importe quelle colonnes de type booléen (0/1)
		$this->checkColumnBoolean("on_off",__T('A_TOPO_LABEL_PARAMETER_ON_OFF'));
		$this->checkColumnBoolean("charge",__T('A_TOPO_LABEL_PARAMETER_CHARGE'));
		//$this->checkNaMinUnique();
		$this->checkNaMinIsNull();
		$this->checkNaMaxUnique();
		$this->checkLabel();
		$this->checkTopology();
		
					
		$this->insertCheckedTableTempIntoFile();
		
		if ( count(self::$warnings) > 0 )
		{
			$this->demon('<h3 style="color:#000;background-color:#ff0">ERREURS NON BLOQUANTES DANS LE CONTENU DU FICHIER</h3>');
			$this->demon(self::$warnings);
		}
		
		if ( count(self::$errors) > 0 )
		{
			$this->demon('<h3 style="color:#fff;background-color:#f00">ERREURS BLOQUANTES DANS LE CONTENU DU FICHIER</h3>');
			$this->demon(self::$errors);
		}
		
		return self::$errors;
	} // End function check()

	/**
	 * Vérifie la présence du délimiteur et qu'on a le même nombre de colonne sur chaque ligne
	 *
	 * Une Exception est levée s'il y a une erreur
	 */
	private function checkDelimiter()
	{
		$this->demon('<b>checkDelimiter()</b><br />');
		
		$header = implode(self::$delimiter, self::$header);
		$nb_delimiter = substr_count($header, self::$delimiter);
	
		// Si aucun délimiteur n'est présent pas alors c'est pas le bon qui a été sélectionné
		// modif 14:19 19/11/2008 GHX
		// Ajout d'une condition dans le cas ou on n'a qu'une seule colonne
		if ( $nb_delimiter == 0 && count(self::$header) > 1 )
		{
			self::$errors[] = __T('A_E_UPLOAD_TOPO_DELIMITER_NOT_VALID');
			throw new Exception();
		}

		// Récupère le nombre de ligne dans le fichier
		$nbLinesInFiles = $this->cmd(" awk 'END {print NR}' ".self::$rep_niveau0 ."upload/".self::$file);
		$this->nbLinesInFiles = $nbLinesInFiles[0];
		
		
		$this->demon("NOMBRE DE LIGNES DANS LE FICHIER AVEC LE HEADER ". $this->nbLinesInFiles);
		
		// maj 01/09/2009 - MPR : Correction du bug 11303 : Si le fichier contient uniquement le header avec une ou deux lignes vides alors msg d'erreurs
		if( $this->nbLinesInFiles == 1 ){
			self::$errors[] = __T('A_E_UPLOAD_TOPOLOGY_FILE_IS_EMPTY');
			throw new Exception();
		}
		
		// Vérifie que sur chaque ligne du fichier on n'a bien le même nombre de colonnes
		// on rajoute plus 1 car on a le nombre de délimiteur et pas le nombre de colonnes
		$cmdAwk = 'awk -F"'.self::$delimiter.'" \' NF!='.($nb_delimiter+1).' {print "[line "NR"] "$0} \' '.self::$rep_niveau0 ."upload/".self::$file;
		$result = $this->cmd($cmdAwk, true);
		
		
		// Si le nombre de résultat est égale au nombre de ligne dans le fichier avec ou sans header on n'affiche pas toutees les lignes afin d'éviter de surcharge l'affichage
		// il en est de même si le nombre de lignes incorrectes dépasses 200 résultats.
		// if ( count($result) == $nbLinesInFiles || (count($result) == $nbLinesInFiles-1) || count($result) > 200 )
		// {
			// self::$errors[] = "erf cas 2";
			// self::$errors[] = __T('A_E_UPLOAD_TOPO_DELIMITER_NOT_VALID',"$space- ".implode("<br/>$space- ",$result));
			// throw new Exception();
		// }
		// else
		if ( count($result) > 0 )
		{
			$space = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			self::$errors[] = __T('A_E_UPLOAD_TOPOLOGY_NB_COLUMNS_NOT_VALID',"$space- ".implode("<br/>$space- ",$result) );
			throw new Exception();
		}
	} // End function checkDelimiter

	/**
	* Vérifie si le nombre d'éléments de 3ème axe est inférieur à la limite indiquée en base (paramètre max_3rd_axis)
	*/
	private function checkNbElementsThirdAxis () {

		// maj 27/09/2007 maxime -> On vérifie avant que le nombre d'élément 3ème axe doit bien être limité
		// maj 13/01/2010 MPR -> Correction du bug 13719 - Check sur le nombre d'éléments réseau 3ème axe uniquement en mode manuel
		// maj 14/01/2010 MPR -> self::$mode à la place de $this->mode
		if ( self::$axe == 3 && $this->limitMax3rdAxis() && self::$mode == "manuel" ){
			
			$limit = get_sys_global_parameters("max_3rd_axis");
			
			$id_na_min_axe3 = self::getIdField(self::$naMinIntoFile, self::$header_db);
			
			// Récupère les na_min en trop (ne seront pas pris en compte dans l'upload)
			$cmd = "awk -F\"".self::$delimiter."\" '{ if(na_min[$".$id_na_min_axe3."]==\"\"){ na_min[$".$id_na_min_axe3."]=1; count++; if(count>".$limit.") { print $".$id_na_min_axe3." } } }' ".self::$file_tmp;
			$this->demon('<pre>'.$cmd.'</pre>',"awk");
			
			$elements_exceeded = $this->cmd($cmd, true);

			// Si le tableau est vide alors aucun élément en trop
			if ( count($elements_exceeded) == 0 )
				return;

			$this->demon( __T('A_E_UPLOAD_TOPO_NB_ELEM_THIRD_AXIS_EXCEEDED', implode(' - ', $elements_exceeded) ) );
			
			self::$errors[] = __T('A_E_UPLOAD_TOPO_NB_ELEM_THIRD_AXIS_EXCEEDED', implode(' - ', $elements_exceeded) );
			
		
		} else
			return;
	} // End function checkNbElementsThirdAxis
	
	/**
	 * Vérifie les colonnes du header :
	 *	- s'il n'y a pas de valeur null et que tous les noms de colonnes existent
	 *	- on ne mélange pas du seconde et troisieme axe dans le même fichier
	 *	- vérification qu'il n'y a pas de colonnes en doublons
	 *	- si une colonne lable existe son niveau d'aggrégation doit etre présent
	 *
	 *	ATTENTION : la vérification se fait sur l'entete originale ET l'entete convertit dans le format de la base
	 *			Il faut donc convertir l'entete avant de lancer le check
	 *	=> Si l'entete est bonne, la convertion dans le format de la base sera bon aussi
	 */
	private function checkHeaderColumns()
	{
		$this->demon('<b>checkHeaderColumns()</b><br />');
		
		/*
			PRE-CHECK : vérification qu'on a au moins un élément réseau dans l'entete du fichier
		*/
		if ( count(self::$header_db) == count(array_intersect(self::$header_db, $this->elementsNoNa)) )
		{
			self::$errors[] = __T('A_E_UPLOAD_TOPO_HEADER_NOT_VALID', implode(self::$delimiter, self::$header));
			throw new Exception();
		}
		
		// Initilisation de quelques tableaux
		$columns_nok = array();
		$fields2 = array();
		$fields_na = array();
		$fields_na_axe3 = array();
		$fields_na_label = array();
		$fields = @array_values(self::$topology);
		$tmp_fields_na = getNaLabelList('na');
		$tmp_fields_na_axe3 = getNaLabelList('na_axe3');
		// Récupère tous les niveaux d'agrégation du seconde axe
		foreach ( $tmp_fields_na as $allna )
		{
			$fields_na = array_merge($fields_na, array_keys($allna));
			foreach ( $allna as $na => $na_label)
			{
			
				$fields_na_label[$na] = $na_label;
				$fields_na_label[$na.'_label'] = $na_label.' label';
			}
		}
		// Récupère tous les niveaux d'agrégation du troisieme axe
		foreach ( $tmp_fields_na_axe3 as $allna )
		{
			$fields_na_axe3 = array_merge($fields_na_axe3, array_keys($allna));

			foreach ( $allna as $na => $na_label)
			{
			
				$fields_na_label[$na] = $na_label;
				$fields_na_label[$na.'_label'] = $na_label.' label';
			}
		}
		$fields_na_label = array_map('strtolower',$fields_na_label);

		if( count($fields) > 0 ){
			foreach ( $fields as $na )
			{
				$fields2 = array_merge($fields2, $na);
			}
		}
		// Tableau contenant tous les valeurs de l'entete possible
		$fields2 = array_merge($fields2, $fields_na, $fields_na_axe3, array_keys($fields_na_label), array_values($fields_na_label) );
		
		// 16:19 21/08/2009 GHX
		// Correction du BZ 11050
		$fields2 = array_map('strtolower', $fields2);

		// 11:23 18/09/2009 GHX
		// Correction du BZ 11524
		// On ajout automatiquement les colonnes suivantes possibles dans l'entete d'un fichier de topo
		// maj 15/10/2009 - MPR : Ajout des colonnes trx et charge lorsque les deux params activate_capacity_planing et activate_trx_charge_into_topo sont à 1
		$tab = array('on_off','azimuth','longitude','latitude');
		if(self::$activate_capacity_planing && self::$activate_trx_charge_in_topo)
			$tab = array('on_off','azimuth','longitude','latitude','trx','charge');
			
		foreach ( $tab as $m )
		{
			if ( !in_array($m, $fields2) )
				$fields2[] = $m;
		}
		
		/*
			CHECK 1 : vérification que toutes les colonnes du header existes
				- vérification par rapport à la table  edw_object_ref_header
				- vérification par rapport au niveau d'aggrégation colone
		 */
		foreach(self::$header_db as $i => $column)
		{
			// 16:19 21/08/2009 GHX
			// Correction du BZ 11050
			$column = strtolower($column);
			
			if(!in_array($column,$fields2))
			{
				if($column !== "")
				{
					$columns_nok[] = $column;
				}
				else
				{
					// Si la valeur est c'est possible qu'il n'ai pas réussi à la convertir
					// si dans le header on a network,network_lablel;network_name
					// Si le sera impossible de changer la network_name => network + network_label car c'est 2 niveaux sont déjà présent dans le header
					if ( array_key_exists(self::$header[$i], self::$topology) )
					{
						foreach ( self::$topology[self::$header[$i]] as $_na )
						{
							// On vérifie si c'est le label du niveau d'aggrégation qui est utilisé dans le header
							if ( in_array($fields_na_label[$_na], self::$header) )
							{
								self::$errors[] = __T('A_E_UPLOAD_TOPOLOGY_HEADER_MUTLI_SAMES_COLUMNS', '<em>'.self::$header[$i].'</em> and <em>'.$fields_na_label[$_na].'</em>', '<em>'.self::$header[$i].'</em> or <em>'.$fields_na_label[$_na].'</em>');
							}
							else
							{
								self::$errors[] = __T('A_E_UPLOAD_TOPOLOGY_HEADER_MUTLI_SAMES_COLUMNS', '<em>'.self::$header[$i].'</em> and <em>'.$_na.'</em>', '<em>'.self::$header[$i].'</em> or <em>'.$_na.'</em>');
							}
						}
						throw new Exception();
					}

					$columns_nok[] = "the column ".($i+1)." is null";
				}
			}
			else
			{
				$column = str_replace(" ","_", $column);
				$columns[] = $column;
			}
		}

		if(count( $columns_nok ) > 0 )
		{
			$space = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";			
			self::$errors[] = "Error in the file's header - Columns are not valid :<br/><br/>$space- ".implode("<br/>$space- ",$columns_nok)."";
			throw new Exception();
		}

		
		/*
			CHECK 2 : vérification qu'on n'a pas du seconde axe et du troisieme axe
				- il est possible que les colonnes du seconde axe et du troisieme axe soient les mêmes cas de Roaming
		 */
		// Vérifie s'il n'y a pas des niveaux axe2 et axe3 dans le header
		$header_db_na = array_intersect(self::$header_db, $fields_na);
		$header_db_na_axe3 = array_intersect(self::$header_db, $fields_na_axe3);

		// maj 06/07/2009 - MPR : Correction des bugs 10248 et 10243
		if (count($header_db_na) > 0){
			$condition = " ('". implode("','", $header_db_na )."') AND axe IS NULL";
			$nb_elems = count($header_db_na);
			$tab_na =  $header_db_na;
		}
		elseif(count($header_db_na_axe3) > 0){
			$condition = " ('". implode("','", $header_db_na_axe3 )."') AND axe = 3";
			$nb_elems = count($header_db_na_axe3);
			$tab_na =  $header_db_na_axe3;
		}
		
		// S'il y a des éléments en commun...
		if ( count($header_db_na) > 0 && count($header_db_na_axe3) > 0 )
		{
			$fields_na_commun = array_intersect($header_db_na, $header_db_na_axe3);
			
			if( count($fields_na_commun) == 0 ) {
			
				// ... on regarde si c'est les meme
				// si la différences entres les 2 tableaux retourne un résultat c'est qu'on il y a des niveaux 1ier et 3ieme axes dans le header
				// Il est possible qu'on ne rentre pas dans la condition dans le cas de Roaming
				// La famille Core possède les mêmes niveaux d'aggrégation que les éléments troisiemes axe de la famille Roaming
				if ( count(array_diff($header_db_na, $header_db_na_axe3)) > 0 && count(array_diff($header_db_na_axe3, $header_db_na) > 0 ) )
				{
					self::$errors[] = __T('A_E_UPLOAD_TOPOLOGY_AXE1_AND_AXE3_NOT_POSSIBLE');
					throw new Exception();
				}
			}
		}
		
		$this->setAxe(1);
		if ( count($header_db_na_axe3) > 0 and count($fields_na_commun) == 0  )
		{
			$this->setAxe(3);
		}
		
		/*
			CHECK 3-1 : vérification qu'il n'y ait pas de colonnes en doublons comme par exemple rnc et rnc_id qui représente la même colonne sur l'header AVANT conversion
		 */
		$this->checkDoublonsColumnsInHeader(self::$header);

		/*
			CHECK 3-2 : vérification qu'il n'y ait pas de colonnes en doublons comme par exemple rnc et rnc_id qui représente la même colonne sur l'header APRES conversion
		 */
		$this->checkDoublonsColumnsInHeader(self::$header_db);

		/*
			CHECK 4 : Vérification que si une colonne label est présent le niveau d'agrégation correspondant aussi
		 */
		$missingCol = array();
		foreach ( self::$header_db as $index => $h )
		{
			if( in_array( $h, $fields_na_label ) ){
				$column_db = array_keys($fields_na_label, $h);
				self::$header_db[$index] = $column_db[0];
			}
			if ( preg_match('/([^_]*)_label$/', $h, $res ) )
			{
				if ( !in_array($res[1], self::$header_db) )
				{
					$missingCol[self::$header[$index]] = $res[1];
				}
			}
		}
		if ( count($missingCol) > 0 )
		{
			foreach ( $missingCol as $col => $col2 )
			{
				self::$errors[] = __T('A_E_UPLOAD_TOPO_HEADER_MISSING_FIELD', '<b>'.$col2.'</b>', '<b>'.$col.'</b>');
			}
			throw new Exception();
		}

		// -> le header est correcte
		// 16:18 21/08/2009 GHX
		// Corrrection du BZ 11050

		self::$header_db = array_map('strtolower', self::$header_db);
		$tab = array();

		foreach( self::$header_db as $field){
		
			$tab[] = str_replace(" ", "_", $field);
			
		}
		self::$header_db = $tab;
		
		self::$header = self::$header_db;
		
		$this->demon(self::$header, 'self::$header');
		
	} // End function checkHeaderColumns

	/**
	 * Vérifié la présente de colonnes en doublons dans le header.
	 *
	 * Une exception est levée s'il y a une erreur dans le header
	 *
	 * @param array $header : tableau contenant les colonnes à vérifier
	 */
	private function checkDoublonsColumnsInHeader ( $header )
	{
		$nb_values = array_count_values($header);
		$result_nok = array();
		foreach ( $nb_values as $value => $nb )
		{
			// Si c'est superieur à 1 c'est que la colonne est en doublons
			if ( $nb > 1 )
			{
				$result_nok_tmp = array();
				foreach ($header as $index => $na )
				{
					if ( $value == $na )
					{
						$result_nok_tmp[] = self::$header[$index];
					}
				}

				if ( count($result_nok_tmp) > 0)
				{
					$result_nok[] = $result_nok_tmp;
				}
			}
		}

		// Si on a des erreurs
		if ( count($result_nok) > 0 )
		{
			foreach ( $result_nok as $nok )
			{
				$uniq = array_unique($nok);
				if ( count($uniq) == 1 )
				{
					self::$errors[] = __T('A_E_UPLOAD_TOPOLOGY_HEADER_MUTLI_SAMES_COLUMNS_2', $uniq[0]);
				}
				else
				{
					self::$errors[] = __T('A_E_UPLOAD_TOPOLOGY_HEADER_MUTLI_SAMES_COLUMNS', '<b>'.implode('</b> and <b>', $nok).'</b>', '<b>'.implode('</b> or <b>', $nok).'</b>');
				}
			}

			throw new Exception();
		}
	} // End function checkDoublonsColumnsInHeader

	/**
	* Fonction qui récupère tous les niveaux d'agrégation existants du produit
	* @return array[ $na] = $na_label : liste des na 
	*/
	private function getLstNa(){
	
		$lst_na = array();
		$lst_na_tmp = getNaLabelList("all","","");
		
		foreach($lst_na_tmp as $family){
			
			foreach($family as $na=>$na_label){
				$lst_na[$na] = strtolower($na_label);
			}
		}
		
		return $lst_na;
		
	}
	/**
	 * Vérifie qu'il y a bien une relation entre tous les niveaux d'aggrétion présent dans l'entete du fichier
	 *
	 * Une exception est levée s'il y a une erreur
	 */
	private function checkRelationNA ()
	{
		$this->demon('<b>checkRelationNA()</b><br />');
		
		// Boolean permettant de savoir s'il y des erreurs
		$hasError = false;
		
		// Récupération de tous les niveaux d'agrégation possibles
		$lst_na = $this->getLstNa(); 

		// Récupère tous les chemins possible pour un produit sans notion de famille ni d'axe.
		// où c'est l'élément fils qui est en index
		$pathNA = getPathNetworkAggregation('', 'no', 'no', true);

		// Boucle sur toutes les valeurs de l'entetes pour récupérer uniquement les colonnes qui ne sont pas des labels
		foreach ( self::$header_db as $k=>$header )
		{
			// Si c'est pas une colonne label ...
			if ( !substr_count($header, 'label') )
			{
				if( array_key_exists($header, $lst_na) )
				{
					// ... on l'ajoute au tableau
					$this->naInHeader[] = $header;
				}
				else
				{	
					// maj 21/09/2009 - MPR : Correction du bug 11050 : On récupère le code de l'élément réseau - Celui-ci peut être différent du label (   Exemple : usercluster1 <> usercellcluster1,...)     
					foreach( $lst_na as $key=>$val ){
	
						if( strtolower( $val ) == $header ){
							$this->naInHeader[] = $key;
							self::$header_db[$k] = $key;
						}
					}
				}
				
				
			}
		}

		// Enlève des colonnes
		$this->naInHeader = array_diff($this->naInHeader, $this->elementsNoNa);

		// Compte le nombre d'élément dans le tableau qui ne sont pas des labels
		$nbNA = count( $this->naInHeader);
		// Boucle sur tous les éléments réseaux du header qui ne sont pas des labels
		foreach ( $this->naInHeader as $na )
		{
			/*
			On récupère tous les niveaux d'aggrégation à partir d'un niveau
			Ex CAS IU :
				Si le na est "sai" le tableau retourné contiendra
					network
					cluster1
					cluster2
					cluster3
					rnc
					sgsn
					msc
					lac
					rac
					sai

				Si le na est "tosgrous" le tableau retourné contiendra
					tosgroup

			*/
			$naParents = getLevelsAgregOnLevel($na, $pathNA);

			/*
			On regarde si tous les éléments réseaux présent dans le header sont dans le tableau présent
			si c'est le cas c'est qu'il y une relation entre tous les élément du header dans le cas contraire non

			Comme on ne sait pas dans quel ordre on teste, il est possible qu'on trouve une erreur alors qu'il n'y en a pas
			Ex CAS IU :
				on a comme header :  network;sai;rnc

				1 - Le tableau retourné par la fonction getLevelsAgregOnLevel pour le niveau "network" contiendra uniquement la valeur "network". La
				       condition suivante sera donc fausse, la variable $hasError sera mis à true.
				2 -  Le tableau retourné par la fonction getLevelsAgregOnLevel pour le niveau "sai" contiendra plusieurs valeurs dont toutes celles présentes dans le header
				      On rentrera donc dans la condition. Et comme il y a une relation entre tous les éléments, la variable $hasError sera remise à false. Afin de ne pas produit d'erreur

			$hasError sert uniquement dans le cas où il n'y a vraiment pas de relation comme par entre entre rac et lac
			*/
			if ( count(array_intersect($this->naInHeader, $naParents)) == $nbNA )
			{
				$this->setNaMinIntoFile($na);
				
				$result = array($na);
				// Récupère les chemins possible entre les différents élément réseaux du header
				$this->hasPathValid($na, $this->naInHeader, $pathNA, $result);

				// Si on n'a pas le nombre d'élément réseau c'est que tous les chemins ne sont pas valides
				// par exemple si on dans sai;lac;sgsn le chemin n'est pas valide. Il manque une relation : le sai->RAC->sgsn
				if ( count(array_intersect($this->naInHeader, $result)) != $nbNA )
				{
					// On récupère les éléments qui on un chemin incomplet
					$diff = array_diff($this->naInHeader, $result);
					$pathNAParentToChild = getPathNetworkAggregation('', 'no', 'no');
					foreach ( $diff as $naHasNoChild )
					{
						$resultMissing = array();
						// Récupère les éléments réseaux pour avoir le(s) chemin(s) manquant(s)
						$this->getChildMissing($na, $naHasNoChild, $naHasNoChild, $pathNAParentToChild, $resultMissing);

						/*
						Parcours les chemins manquant éléminer certains chemins qui n'ont pas de rapport avec les niveaus présent dans l'entete
						Ex  CAS IU:
							Si dans le header on a : network;lac

							Les chemins manquant trouvés sont
							1 = network -> msc -> lac
							2 = network -> sgsn -> rac -> sai
							3 = network -> rnc -> sai

							On a besoin que du chemin 1
						*/
						self::$errors[] = 'No relationship between <em>'.$na.'</em> and <em>'.$naHasNoChild.'</em>. List of relationship possible :<br />';
						foreach ( $resultMissing as $oneResultMissing )
						{
							if ( in_array($na, $oneResultMissing) && in_array($naHasNoChild, $oneResultMissing) )
							{
								krsort($oneResultMissing);
								// Affiche le chemin sous la forme : sai <-> rnc <-> network
								self::$errors[] = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".implode(' &harr; ',$oneResultMissing);
							}
						}
					}
				}
				else
				{
					/*
					On a une relation entre les tous les éléments réseaux présents dans l'entete
					S'il y a eu une erreur entre deux niveaux, on considère que c'est bon.

					Voir commentaire sur la condition if ( count(array_intersect($this->naInHeader, $naParents)) == $nbNA )
					*/
					$hasError = false;
					break;
				}
			}
			else
			{
				// On n'a pas pu déterminé des relations entre différents niveau d'aggrégation
				$hasError = true;
			}
		}

		// Vérifie s'il y a une erreur (pas de relation)
		if ( $hasError === true && count(self::$errors) == 0 )
		{
			self::$errors[] = 'No relationship between the networks aggregations in the header';
		}

		// Si on a des erreurs on lève une exception
		if ( count(self::$errors) > 0 )
		{
			throw new Exception('');
		}
	} // End function checkRelationNA

	/**
	 * Ajout dans le tableau $result (passé par référence) les éléments réseaux qui on un chemin valide à partir de l'élément $na
	 * par exemple si dans le header on a "sai:lac;sgsn". Le paramètre $na vaudra "sai". Donc le tableau $result contiendra les éléments
	 * sai et lac. (sai est déjà présent dans le tableau avant le premier appel de la fonction)
	 *
	 * Fonction résurcive.
	 *
	 * @param string $na niveau à partir du quel on récupère les éléments parents ayant une relation
	 * @param array $naInHeader les éléments réseaux présent dans le header
	 * @param array $pathNA tableau contenant toutes les relations
	 * @param array &$result tableau de résultat
	 */
	private function hasPathValid ( $na, $naInHeader, $pathNA, &$result )
	{
		if ( array_key_exists($na, $pathNA) )
		{
			foreach ( $pathNA[$na] as $naParent )
			{
				// Si le niveau parent est dans le header
				if ( in_array($naParent, $naInHeader) )
				{
					$result[] = $naParent;
					$this->hasPathValid($naParent, $naInHeader, $pathNA, $result);
				}
			}
		}
	} // End function hasPathValid

	/**
	 * Retourne un tableau avec les chemins possibles entre 2 niveaux
	 *
	 * Fonction résurcive.
	 *
	 * @param string $na niveau à partir du quel on récupère les éléments parents ayant une relation
	 * @param array $naHasNoChild élément réseau où il manque une relation
	 * @param array $naHasNoChildStart les éléments réseaux présent dans le header (même valeur que $naHasNoChild
	 * @param array $pathNA tableau contenant toutes les relations
	 * @param array &$result tableau de résultat
	 */
	private function getChildMissing ( $na, $naHasNoChild, $naHasNoChildStart, $pathNA, &$result, $level = 0 )
	{
		// tmpLevel permet jsute de gérér si on vient de sur la premier boucle
		$tmpLevel = $level;
		// Si le niveau d'aggrégation n'est pas dans le tableau c'est qu'on est rendu au bout du chemin
		if ( array_key_exists($naHasNoChild, $pathNA) )
		{
			// boucle sur tous les fils du niveau $naHasNoChild
			// Si on est sur sgsn il bouclera sur rac
			foreach ( $pathNA[$naHasNoChild] as $naChild )
			{
				// Tant qu'on n'est pas rendu sur le niveau minimum que l'on souhaite on continue
				if ( $naChild != $na )
				{
					/*
					Permet de gérer le cas ou un parent peut avoir 2 fis de type différents
					Ex :
						SAI -> LAC ->MSC -> Network
						SAI -> TOTO ->MSC -> Network
					*/
					if ( $tmpLevel > 0 && $level > $tmpLevel )
					{
						$result[$level] = array_slice($result[$level-1], 0, $tmpLevel);
					}
					elseif ( $tmpLevel == 0 )
					{
						$result[$level][] =  $naHasNoChildStart;
					}
					$result[$level][] = $naChild;
					// on rappelle la fonction pour voir les niveaux fils
					$level = $this->getChildMissing($na, $naChild, $naHasNoChildStart, $pathNA, $result, $level);
					$level++;
				}
				else
				{
					$result[$level][] = $na;
				}
			}
		}

		return $level;
	} // End function getChildMissing

	/**
	* maj 15/10/2009 - MPR : Modification de la fonction afin de checker n'importe quelle colonne de type boolean (0/1)
	 * Vérifie la valeur d'une colonne booléenne (on_off, charge,...)
	 *
	 * utilisation de la commande awk au lieu de faire une requete SQL
	 */
	function checkColumnBoolean($column, $column_label)
	{
		$this->demon('<b>checkColumnBoolean()</b><br />');
		
		// Si la colonne n'est pas présente dans l'entete on n'a pas besoin de faire de vérification dessus
		if( !in_array($column,self::$header_db) )
			return;

		$indexCols = array_flip(self::$header_db);
		$indexColBool = $indexCols[$column]+1;

		// maj 11/06/2009 MPR : Correction des bugs 9645/9646 - Remplacement de NR > 1 par NR >= 1 dans le cas où le fichier comporte une seule ligne de données topologiques
		$cmdAwk = 'awk -F"'.self::$delimiter."\" '\$".$indexColBool." != 1 && \$".$indexColBool." != 0 && NR >= 1 {print NR\";\"\$".$indexColBool."}' ".self::$file_tmp_db;
	
		$resultCmdAwk = $this->cmd($cmdAwk, true);

		// Si le tableau contient des valeurs c'est qu'i y a des erreurs sur le on_off
		if ( count($resultCmdAwk) > 0 )
		{
			$linesBooleanNull = array(); // tableau contenant les lignes où le on_off est nul
			$linesBooleanInvalid = array(); // tableau contenant les lignes où le on_off est invalide soit différents de 0, 1 et null

			foreach ( $resultCmdAwk as $oneResult )
			{
				list($nbLine, $valueColBool) = explode(';', $oneResult);

				if ( $valueColBool == null || $valueColBool == '' )
				{
					$linesBooleanNull[] = $nbLine;
				}
				else
				{
					$linesBooleanInvalid[] = $nbLine;
				}
			}

			if ( count($linesBooleanNull) > 0 )
			{
				self::$errors[] = __T('A_E_UPLOAD_TOPO_COLUMN_BOOLEAN_IS_NULL',$column_label, implode(', ',$linesBooleanNull) );
			}
			if ( count($linesBooleanInvalid) > 0 )
			{
				self::$errors[] = __T('A_E_UPLOAD_TOPO_COLUMN_BOOLEAN_INVALID', $column_label, implode(', ',$linesBooleanInvalid));
			}
		}
	}// End Function checkColumnBoolean

	/**
	 * Vérifie que si un niveau d'aggrégation accepte qu'une seule valeur, on en a qu'une dans le fichier
	 */
	function checkNaMaxUnique ()
	{
		$this->demon('<b>checkNaMaxUnique()</b><br />');
		
		$naNotUnique = array();
		
		// Boucle sur toutes les valeurs de l'entetes
		foreach ( self::$header_db as $header )
		{
			// Si c'est pas une colonne label
			if ( !preg_match('/([^_]*)_label$/', $header, $res ) )
			{
				$na = $header;
				// Requete qui permet de savoir si la colonne accepte ou non plusieurs valeurs différentes
				$query = "
						SELECT
							na_max_unique
						FROM
							sys_definition_network_agregation
						WHERE
							agregation = '".$na."'
							AND na_max_unique = 1
						LIMIT 1;
					";
				$result = $this->sql($query);

				// Si on a un résultat c'est qu'il ne peut y avoir qu'une SEULE valeur pour ce niveau d'aggrégation
				if ( @pg_num_rows($result) == 1 )
				{
					// On récupère toutes les valeurs s'il y en a plus d'uneqs
					$query = "
							SELECT
								DISTINCT ".$na."
							FROM
								".self::$table_ref."_tmp
							WHERE
								(SELECT COUNT(DISTINCT ".$na.") FROM  ".self::$table_ref."_tmp) > 1
 						";
					$result = $this->sql($query);
					if ( @pg_num_rows($result) )
					{
						while ( list($na_value) = pg_fetch_row($result) )
						{
							$naNotUnique[$na][] = $na_value;
						}
					}
				}
			}
		}

		// Si les tableaux n'est pas vide c'est qu'il y a des erreurs
		if ( count($naNotUnique) > 0 )
		{
			foreach ( $naNotUnique as $na => $na_values )
			{
				self::$errors[] = __T('A_E_UPLOAD_TOPO_NA_MAX_NOT_UNIQUE', $na, implode(' - ',$na_values));
			}
		}
	} // End function checkNaMaxUnique

	/**
	 * Vérifie que les labels sont uniques
	 */
	function checkLabel()
	{
		$this->demon('<b>checkLabel()</b><br />');
		
		$labelFoundButNotId = array();
		$labelNotUnique = array();
		$labelNotUnique2 = array();
        // initialisation du tableau
        $labelNotUnique3 = array();

		// Boucle sur toutes les valeurs de l'entetes
		foreach ( self::$header_db as $header )
		{
			// Si c'est une colonne contenant des labels
			if ( preg_match('/([^_]*)_label$/', $header, $res ) )
			{
				// Nom de l'élément réseau correspondant à la colonne label
				$na = $res[1];

				/*
					CHECK 1 : Si présence d'un label, son identifiant ne doit pas être null
				*/
				$query = "
						--- Si présence d'un label, son identifiant ne doit pas être null
						
						SELECT
							".$na."_label
						FROM
							".self::$table_ref."_tmp
						WHERE 
							".$na." IS NULL
							AND ".$na."_label IS NOT NULL
					";
				$result = $this->sql($query);

				if ( @pg_num_rows($result) )
				{
					while ( list($na_value_label) = pg_fetch_row($result) )
					{
						$labelFoundButNotId[$na][] = $na_value_label;
					}
				}
				
				/*
					CHECK 2 : Vérification qu'un élément n'a pas plusieurs label
				*/
				$query = "
						--- Vérification qu'un élément n'a pas plusieurs label
						
						SELECT DISTINCT
							".$na.",
							".$na."_label
						FROM
							(
								SELECT
									".$na."
								FROM
									".self::$table_ref."_tmp
								GROUP BY ".$na."
								HAVING count(distinct ".$na."_label) > 1
							) t0
							LEFT JOIN ".self::$table_ref."_tmp
							USING (".$na.")
					";
				$result = $this->sql($query);

				if ( @pg_num_rows($result) )
				{
					while ( list($na_value, $na_value_label) = pg_fetch_row($result) )
					{
						$labelNotUnique[$na][$na_value][] = $na_value_label;
					}
				}

                // 21/04/2011 NSE DE Non unique Labels
                // Si les labels en double ne sont pas autorisés, on effectue les vérifications sur les labels en double
                if( ! NaModel::IsNonUniqueLabelAuthorized($na,$this->_productId) ){
                    /*
                        CHECK 3 : Vérification que plusieurs éléments n'ont pas le même label
                    */
                    $query2 = "
                            --- Vérification que plusieurs éléments n'ont pas le même label

                            SELECT DISTINCT
                                ".$na.",
                                ".$na."_label
                            FROM
                                (
                                    SELECT
                                        ".$na."_label
                                    FROM
                                        ".self::$table_ref."_tmp
                                    WHERE ".$na." IS NOT NULL
                                    GROUP BY ".$na."_label
                                    HAVING count(distinct ".$na.") > 1
                                ) t0
                                LEFT JOIN ".self::$table_ref."_tmp
                                USING (".$na."_label)
                                WHERE ".$na." IS NOT NULL
                        ";
                    $result2 = $this->sql($query2);

                    if ( @pg_num_rows($result2) )
                    {
                        while ( list($na_value, $na_value_label) = pg_fetch_row($result2) )
                        {
                            $labelNotUnique2[$na][$na_value_label][] = $na_value;
                        }
                    }
                    /*
                        CHECK 4 : Vérification que plusieurs éléments n'ont pas le même label (Comparaison du fichier et en base)
                        maj 10/12/2009 - MPR : Correction du buh 13196 : Contrôle d'unicité sur les labels // ajout de la condition AND {$na} <> ne_in_db;
						24/09/2012 ACS BZ 28781 Topology update requires two upload in order to swap labels
                    */
                    $query3 = "
                    SELECT ".$na." as ne_in_file, ne_in_db, ".$na."_label
                    FROM ".self::$table_ref."_tmp
                    LEFT JOIN (
                        SELECT eor_label,eor_id  as ne_in_db
                        FROM ".self::$table_ref." WHERE eor_obj_type='".$na."'
                    ) as e ON (eor_label=".$na."_label)
                    WHERE ne_in_db IS NOT NULL AND {$na} <> ne_in_db AND ne_in_db NOT IN (SELECT ".$na." FROM ".self::$table_ref."_tmp);";

                    $result3 = $this->sql($query3);

                    if ( @pg_num_rows($result3) )
                    {
                        while ( list($na_value_in_file, $na_value_in_db, $na_value_label) = pg_fetch_row($result3) )
                        {
                            $labelNotUnique3[$na][$na_value_label][$na_value_in_db][] 	 = $na_value_in_file;
                        }
                    }
                }// fin du Si les labels en double ne sont pas autorisés
			}
		}

		// Création des messages d'erreurs s'il y en a
		if ( count($labelFoundButNotId) > 0 )
		{
			foreach ( $labelFoundButNotId as $na => $na_values )
			{
				foreach ( $na_values as $na_value_labels )
				{
                                    // 15/04/2011 BBX
                                    // Utilisation des labels des NA dans l'affichage des messages
                                    // BZ 20704
                                    $naModel = new NaModel($na);
                                    self::$errors[] = __T('A_E_UPLOAD_TOPO_NA_NULL_LABEL_NOT_NULL', $naModel->getLabel(), '<em>'.$na_value_labels.'</em>');
				}
			}
		}
		// Contrôle sur le contenu du fichier 
		if ( count($labelNotUnique) > 0 )
		{
			foreach ( $labelNotUnique as $na => $na_values )
			{
				foreach ( $na_values as $na_value => $na_value_labels )
				{
					//element avec plusieurs labels
					self::$warnings[] = __T('A_E_UPLOAD_TOPO_NOT_ONE_LABEL', count($na_value_labels), $na_value, $na, implode(' - ',$na_value_labels));
					$query = "UPDATE ".self::$table_ref."_tmp SET ".$na."_label=NULL WHERE ".$na."='".$na_value."'";
					$this->demon("Ignore problematic label: ".$query);
					$this->sql($query);
				}
			}
		}
		// Contrôle sur le contenu du fichier et de la base
		if ( count($labelNotUnique3) > 0 )
		{
			foreach ( $labelNotUnique3 as $na => $na_values )
			{
				foreach ( $na_values as $na_value => $na_value_labels )
				{
					foreach( $na_value_labels as $db => $file){ 					
						//ce n'est pas un probleme bloquant, on crée juste un message d'avertissement
						// the $1 $2[$3] already exists in database for $4
						self::$warnings[] = __T('A_E_UPLOAD_TOPO_LABEL_ALREADY_EXISTS_IN_DB', $na."_label", $na_value, implode(" - ", $file), $db);						
					}
				}
			}
		}
		if ( count($labelNotUnique2) > 0 )
		{
			foreach ( $labelNotUnique2 as $na => $na_values )
			{
				foreach ( $na_values as $na_label => $na_ids )
				{
					// 22/09/2010 BBX
					// Correction du message d'erreur
					// BZ 14516
					//meme label pour plusieurs elements
					self::$warnings[] = __T('A_E_UPLOAD_TOPO_LABEL_NOT_ONE_NA',$na_label,count($na_ids),$na,implode(' - ',$na_ids));
					foreach($na_ids as $na_id){
						$query = "UPDATE ".self::$table_ref."_tmp SET ".$na."_label=NULL WHERE ".$na."='".$na_id."'";
						$this->demon("Ignore problematic label: ".$query);
						$this->sql($query);
					}
				}
			}
		}
	} // End function checkLabel

	/**
	 * Vérification de l'unicité du niveau minimum présent dans le fichier
	 */
	private function checkNaMinUnique ()
	{
		$naMin = $this->getNaMinIntoFile();
		
		$queryNaMinUniq = "
				--- Vérification de l'unicité du niveau minimum présent dans le fichier
				SELECT $naMin
				FROM ".self::$table_ref."_tmp
				GROUP BY $naMin
				HAVING count($naMin) > 1
			";
		
		$resultNaMinUniq = $this->sql($queryNaMinUniq);
		$this->demon($resultNaMinUniq,"RESULT QUERY NA MIN UNIQUE");
		if ( @pg_num_rows($resultNaMinUniq) > 0 )
		{
			while( list($na_value) = pg_fetch_row($resultNaMinUniq) )
			{
				self::$errors[] = __T('A_E_UPLOAD_TOPO_NA_MIN_NOT_UNIQUE', $naMin, $na_value);
			}
		}	
	} // End function checkNaMinUnique
	
	
	/**
	* Fonction qui identifie si le niveau minimum de la famille principale est présent dans le fichier 
	* 
	*/
	
	private function checkNaMinInFile()
	{
	
		$params = array("longitude","latitude","azimuth","trx","charge");
		$tab = array_intersect( $params , self::$header_db );
		if( count($tab) > 0 ){
			$naMin = get_network_aggregation_min_from_family( get_main_family() );		
			$columns_nok=array();
			if( !in_array($naMin, self::$header)  ){
						
				foreach( $params as $param ){
					if( in_array( $param, self::$header ) ){
						
						$columns_nok [] = $param;
					}
				}
				
				self::$errors[] = __T('A_E_UPLOAD_TOPO_NA_MIN_IN_FILE', $naMin, implode(" - ",$columns_nok) );
			}
		}
		$coords = array("longitude","latitude","azimuth");
		$tab2 = array_intersect( $coords , self::$header_db );
		if( count($tab2) > 0 ){
			$this->checkCoordinates($naMin);
		}
	}	
	
	private function checkCoordinates ($naMin)
	{
	
		
		$query = "
				--- Vérification de l'unicité du niveau minimum présent dans le fichier
				SELECT $naMin as na_min, longitude, latitude, azimuth
				FROM ".self::$table_ref."_tmp
				WHERE longitude ILIKE '%,%' OR latitude ILIKE '%,%'
				OR longitude !~ '^-*[0-9]*.?[0-9]*$' OR latitude !~ '^-*[0-9]*.?[0-9]*$'
				OR azimuth !~ '^-*[0-9]*$'
			";
		
		$result = $this->sql($query);
		
		$this->demon($result,"RESULT");
		if ( @pg_num_rows($result) > 0 )
		{
			$this->demon("Erreur identifiee");
			$i = 0;
			while( $row = pg_fetch_array($result) )
			{
				$i++;
				
				self::$errors[] = __T('A_E_UPLOAD_TOPO_COORDS_FORMAT_ISNT_VALID',$naMin,$row['na_min'],$row['longitude'], $row['latitude'], $row['azimuth']);
			}
		}
		
	} // End function checkNaMinUnique
	
	// Correction du bug 10350 : Le niveau minimum du fichier ne doit pas être null
	/**
	* 
	 * On check si le niveau d'agrégation minimum du fichier n'a pas de valeur null
	 */
	private function checkNaMinIsNull ()
	{
            // 22/09/2010 BBX
            // Correction du contrôle d'éléments nuls.
            // BZ 14516
            $naMin = $this->getNaMinIntoFile();
            $queryNaMinUniq = "
                            --- Contrôle de la présence d'éléments nuls
                            SELECT $naMin
                            FROM ".self::$table_ref."_tmp";

            $resultNaMinUniq = $this->sql($queryNaMinUniq);
            if ( @pg_num_rows($resultNaMinUniq) > 0 )
            {
                $i = 1;
                while( list($na_value) = pg_fetch_row($resultNaMinUniq) ) {
                    // 12/04/2011 BBX
                    // On dit pouvoir avoir des élément à 0
                    // BZ 21792
                    if(trim($na_value) == '') {
                        // 15/04/2011 BBX
                        // Utilisation des labels des NA dans l'affichage des messages
                        // BZ 20704
                        $naModel = new NaModel($naMin);
                        self::$errors[] = __T('A_E_UPLOAD_TOPO_NA_MIN_IS_NULL', $naModel->getLabel(), $i);
                    }
                    $i++;
                }
            }
	} // End function checkNaMinUnique
	
	/**
	 * Vérifie la cohérence entre les éléments de niveaux différents fils <-> parent
	 * exemple : entre sai et rnc, rnc et network...;
	 */
	private function checkTopology ()
	{
		$this->demon('<b>checkTopology()</b><br />');
		
		// S'il n'y a qu'un élément réseau dans le tableau il n'y a pas besoin de faire de vérification
		if ( count($this->naInHeader) == 1 )
			return;

		// Récupère tous les chemins possible pour un produit sans notion de famille ni d'axe.
		$pathNA = getPathNetworkAggregation('', 'no', 'no');

		// Boucle sur toutes les éléments réseaux de l'entete
		foreach ( $this->naInHeader as $naParent )
		{
			// Si l'élément n'est pas en index du tableau c'est donc le niveau minimum
			if ( !array_key_exists($naParent, $pathNA) )
				continue;

			$queryWhereChildIsNull = array();
			
			// On boucle sur les éléments fils (par défaut un élément parent n'a qu'un seul fils)
			foreach ($pathNA[$naParent] as $naChild )
			{
				// Si l'élémént fils n'est pas dans l'entete pas besoin de faire un check dessus
				if ( !in_array($naChild, $this->naInHeader) )
					continue;

				/*
					CHECK 1 : un parent ne peut pas avoir d'enfants dont la valeur est NULL
						La requete est exécutée apres la boucle foreach
				*/
				$queryWhereChildIsNull[] = $naChild;
				
				/*
					CHECK 2 : un élément fils ne peut avoir qu'un seul parent d'un même type
				*/
				// Requete qui permet de savoir si l'élément avec plusieurs parent d'un même type
				$query = "
						SELECT
							na_parent_unique
						FROM
							sys_definition_network_agregation
						WHERE
							agregation = '$naChild'
							AND na_parent_unique = 1
						LIMIT 1;
					";
				$result = $this->sql($query);

				// Si on a un résultat c'est qu'il ne peut y avoir qu'une SEULE valeur pour le niveau parent
				if ( @pg_num_rows($result) == 0 )
					continue;
				
				
				$header_db_na = array_intersect(self::$header_db, $this->naInHeader);

				// maj 06/07/2009 - MPR : Correction des bugs 10248 et 10243
				// maj 05/03/2010 - MPR : Correction du BZ1046
				$condition = " ('". implode("','", $this->naInHeader )."')";
				$nb_elems = count($this->naInHeader);
				// 23/04/2010 NSE bz 14046 ajout de rank et n.[family] pour le Group By et n.family en premier dans le select
				// 22/01/2013 BBX
				// BZ 31306 : correction de la requête qui détermine la famille
				$query = "SELECT n.family, rank, axe, count(agregation)
					FROM sys_definition_network_agregation n, sys_definition_categorie c 
					WHERE agregation IN {$condition}
					AND c.family = n.family 
					AND n.level_source IN {$condition}
					GROUP BY n.family, rank, axe
					HAVING count(agregation) = {$nb_elems}
					ORDER BY rank DESC LIMIT 1";
                
				$result = $this->sql($query);
				
				if ( @pg_num_rows($result) > 0 ){
				
					$_family = pg_fetch_array($result,0);
					self::$_family = $_family[0];
					if( $this->getAxe() == 1 )
					{
						
						$na_min = get_network_aggregation_min_from_family( $_family[0] );
					}
					else
					{
						$na_min = get_network_aggregation_min_axe3_from_family( $_family[0] );
					}
					
					$query = "SELECT * FROM get_path('{$naParent}','{$na_min}','{$_family[0]}')";
					$res = $this->sql($query);
					
					$path_na = array();
					while( $row = pg_fetch_array($res) ){
						$path_na[] = $row[0];
						
					}
					
					if( in_array( $naChild, $path_na) ){
					
					// $this->demon("=>Pas de controle entre lac et msc<=");
						$queryParentNotOne = "
								--- un élément fils ne peut avoir qu'un seul parent d'un même type

								SELECT t0.$naChild
								FROM (
									SELECT DISTINCT $naChild, $naParent
									FROM ".self::$table_ref."_tmp
									ORDER BY $naChild DESC
								) t0
								GROUP BY $naChild
								HAVING COUNT($naChild) > 1
								";

						$resultParentNotOne = $this->sql($queryParentNotOne);

						// Si certains éléments on plusieurs parents
						if ( @pg_num_rows($resultParentNotOne) > 0 )
						{
							while ( list($naChildValue) = pg_fetch_row($resultParentNotOne) )
							{
								//recherche des differents label pour chaque resultat trouve
								$q = "
										SELECT DISTINCT $naParent
										FROM ".self::$table_ref."_tmp
										WHERE
											$naChild = '$naChildValue'
									";

								$res = $this->sql($q);

								$parents=array();

								while ( $p = pg_fetch_row($res) )
								{
									if ( $p[0] == null )
									{
										$parents[] = "NULL";
									}
									else
									{
										$parents[] = $p[0];
									}
								}

								self::$errors[] = __T('A_E_UPLOAD_TOPO_PARENT_NOT_ONE', $naChild, $naChildValue, implode(' - ',$parents), 1, $naParent);
							}
						}
					}else{
						$this->demon("La famille '" . $naChild . "' n'a pas été identifiée dans le chemin " . print_r($path_na, true));
					}
				}
				$this->demon("Family found : " . self::$_family);
			}
			
			/*
				CHECK 1 : suite
			*/
			if ( count($queryWhereChildIsNull) > 0 )
			{
				$queryChildIsNull = "
						---  un parent ne peut pas avoir d'enfants dont la valeur est NULL

						SELECT DISTINCT ON($naParent) $naParent
						FROM ".self::$table_ref."_tmp
						WHERE
							$naParent IS NOT NULL
							AND ".implode(" IS NULL AND ", $queryWhereChildIsNull)." IS NULL
						ORDER BY $naParent DESC
					";

				$resultChildIsNull = $this->sql($queryChildIsNull);

				if ( @pg_num_rows($resultChildIsNull) > 0 )
				{
					while ( $row = pg_fetch_assoc($resultChildIsNull) )
					{
                                                // 14/04/2011 BBX
                                                // Utilisation des labels des NA dans l'affichage des messages
                                                // BZ 20704


						// Si un seul niveau fils
						if ( count($queryWhereChildIsNull) == 1 )
						{
							$naModel = new NaModel($queryWhereChildIsNull[0]);
							self::$errors[] = __T('A_E_UPLOAD_TOPO_NULL_VALUE_FOUND', $naModel->getLabel(), $row[$naParent]);
						}
						else
						{
							$childWithLabel = array();
							foreach($queryWhereChildIsNull as $na) {
								$naModel = new NaModel($na);
								$childWithLabel[] = $naModel->getLabel();
							}
							$last = array_pop($childWithLabel);
							self::$errors[] = __T('A_E_UPLOAD_TOPO_NULL_VALUE_FOUND', implode(', ', $childWithLabel).' and '.$last, $row[$naParent]);
						}
					}
				}
			}
		}
	} // End function checkTopology

	/**
	 * Fonction qui génère la table temporaire
	 */
	private function createTableTemp()
	{
		$header = implode(self::$delimiter, self::$header);
		$select = str_replace(self::$delimiter," TEXT, ", $header);

		$query = "CREATE TABLE ".self::$table_ref."_tmp($select TEXT)";

                // 01/10/2012 BBX
                // BZ 29059 : on returne le résultat d'éxécution de la requête
		return $this->sql($query);
	} // End function createTableTemp

	/**
	 * Insère le fichier chargé par l'utilisateur dans la table temporaire
	 */
	private function insertFileIntoTableTemp()
	{
		$header = implode(self::$delimiter, self::$header);

		$select = str_replace( self::$delimiter,",",$header );

		$query = "TRUNCATE ".self::$table_ref."_tmp";
		$this->sql($query);

		$query = "COPY ".self::$table_ref."_tmp(".$select.")
				  FROM '".self::$file_tmp_db."'  WITH DELIMITER '".self::$delimiter."' NULL ''";
		$this->sql($query);
		
		
	} // End function insertFileIntoTableTemp
	
	/**
	 * Insère la edw_object_ref_tmp dans un fichier apres l'avoir verifiee
	 */
	private function insertCheckedTableTempIntoFile()
	{
		$header = implode(self::$delimiter, self::$header);

		$select = str_replace( self::$delimiter,",",$header );
		
		self::$file_tmp_db = self::$file_tmp_db.'_checked';//use checked file for next actions
		
		$query = "
					--- Récupère la table edw_object_ref_tmp dans un fichier
					
					COPY (
						SELECT ".$select."
						FROM ".self::$table_ref."_tmp
						)
					TO '".self::$file_tmp_db."'
					WITH DELIMITER '".self::$delimiter."'
						 NULL ''
				";
		$this->sql($query);		
	} // End function insertCheckedTableTempIntoFile

	/**
	 * Destructeur
	 */
	function __destruct()
	{
		
	} // End function __destruct

} // End class TopologyCheck
?>