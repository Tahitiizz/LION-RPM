<?php
/*
*	@cb41000@
*
*	14/11/2007 - Copyright Astellia
*
*	Composant de base version cb_4.1.0.00
*
*
*	01/12/2008 GHX
*		- Renomage de tous les fichiers csv cr��s en fichier topo (plus facile pour supprimer les fichiers � la fin de l'upload)
*		- Ajout de la fonction updateLabelOnOff
*		- R�cup�ration des nouveaux �l�ments pour les mettre dans le tableau changes summary 
*		- Ajout de l'initialisation du tableau naLabel dans le constructeur (r�cup�re le label des niveaux d'aggr�gation)
*	12:06 14/10/2009 SCT
*		- Ajout du champ eor_color pour la conservation des valeurs del a table edw_object_ref lors du chargement de la topo via un fichier
*	09/11/2009 MPR
*		- Correction BZ 12248 - Chargement d'un fichier contenant trx/charge et/ou/sans coords
*	06/01/2010 GHX
*		- RE-Correction du BZ 13251 [REC][T&A IU 5.0][TC#5904][TOPOLOGY]: max_3rd_axis non pris en compte dans upload topo 3� axe
*		- Modification d'une requete SELECT dans la fonction setParametersErlang() sinon erreur dans l'upload
*       26/07/2010 BBX : BZ 14969 
*               - Tri et d�doublonnage en PHP car certains caract�res sp�ciaux ne passent pas avec la commande "sort"
*               - Utilisation de la fonction "sort" PHP et suppression du seconde param�tre de la fonction "array_unique" qui bug en PHP < 5.2.9
*
*/
?>
<?php
/**
 *	Classe TopologyAddElements 	- On ajoute les nouveaux �l�ments r�seau ou 3�me axe dans la table edw_object_ref
 *						- Elle h�rite de la classe TopologyLib
 *
 * @version 4.1.0.00
 * @package Topology
 * @author MPR
 * @since CB4.1.0.0
 *
 *	maj MPR : R��criture du fichier 
 */
class TopologyAddElements extends TopologyLib
{

	// -------------------------------------------- M�thodes--------------------------------------------//

	/**
	 *
	 */
	private $file_result_tmp;

	/**
	 *
	 */
	private $file_result_ref;

	/**
	 *
	 */
	private $file_result_complet_tmp;

	/**
	 *
	 */
	private $file_result;

	/**
	 *
	 */
	private $file_parameters;

	/**
	 * Tableau contenant tous les niveaux uniques pr�sents dans l'entete
	 * @since CB4.1.0.00
	 * @var array
	 */
	private $naUniqueInHeader = array();
	
	/**
	 * Constructeur
	 */
	function __construct()
	{
		$this->initFiles();
		$this->process();
	} // End function __construct

	/**
	 *
	 */
	private function process()
	{
	
		$file = pathinfo(self::$file);
		$filename = date('Ymd_His');

		$file_result = self::$rep_niveau0 .'upload/'.$file['filename'].'_'.$filename.'_res.topo';
		$file_parameters = self::$rep_niveau0 .'upload/'.$file['filename'].'_'.$filename.'_parameters_tmp.topo';
		$file_parameters_erlang = self::$rep_niveau0 .'upload/'.$file['filename'].'_'.$filename.'_parameters_erlang_tmp.topo';
		
		self::$na = array();

		// Cr�ation du fichier temporaire contenant les �l�ments r�seau ou 3�me axe du fichier
		$this->createFileResultTmp();

		// Cr�ation du fichier temporaire contenant les �l�ments r�seau ou 3�me axe de la base
		$this->createFileResultRef();

		// On identifie les nouveaux �l�ments de tous les niveaux d'agr�gation
		$this->getNewElements($file_result);
				
		/*
		* 09:05 01/12/2008 GHX
		*	- Mise � jour des labels 
		*	- Mise � jour du on/off
		*/
		$this->updateLabelOnOff();
		$this->fixLabels();
		$this->demon( count($file) ,"NB IN FILE ");
		if( count($file)>0 )
		{
			// On compl�te les donn�es pour ces nouveaux �l�ments r�seaux
			$this->completeFileResult($file_result,$file_parameters,$file_parameters_erlang);
		}
		else
		{
			$this->demon("Aucun ajout");
		}
	} // End function process

	/**
	 *
	 */
	private function initFiles()
	{
		$file = pathinfo(self::$file);
		$filename = date('Ymd_His');
		// Fichier contenant les �l�ments r�seau ou 3�me axe du fichier charg� ( ex format :  na, obj_type )
		$this->file_result_tmp = self::$rep_niveau0 .'upload/'.$file['filename'].'_'.$filename.'_result_tmp.topo';
		$this->demon($this->file_result_tmp,"this->file_result_tmp");
		
		// Fichier contenant les �l�ments r�seau ou 3�me axe de la table edw_object_ref du m�me type que les na du fichier charg� ( ex format :  na, obj_type )
		$this->file_result_ref = self::$rep_niveau0 .'upload/'.$file['filename'].'_'.$filename.'_result_ref.topo';
		$this->demon($this->file_result_ref,"this->file_result_ref");
		
		// Fichier contenant les donn�es suppl�mentaires des na ( ex : format :  na, na_label obj_type,on_off )
		$this->file_result_complet_tmp = self::$rep_niveau0 .'upload/'.$file['filename'].'_'.$filename.'_complet_tmp.topo';
		$this->demon($this->file_result_complet_tmp,"this->file_result_complet_tmp");
		// Fichier modifi� � plusieurs reprises. il contient
		//	- 1 : na; obj_type
		//	- 2 : na; na_label; obj_type
		//	- 3 : na; na_label; obj_type; on_off
		$this->file_result = self::$rep_niveau0 .'upload/'.$file['filename'].'_'.$filename.'_result.topo';

		// Fichier contenant les coordonn�es g�ographiques des nouveaux na_min (format : na_min, longitude, latitude, azimuth)
		$this->file_parameters = self::$rep_niveau0 .'upload/'.$file['filename'].'_'.$filename.'_parameters.topo';
		$this->file_parameters_erlang = self::$rep_niveau0 .'upload/'.$file['filename'].'_'.$filename.'_parameters_erlang.topo';
		
		// Cr�ation d'un fichier contenant les donn�es de la table edw_object_ref
		// pour pouvoir mettre � jour les labels et le on/off
		
		$date = date('Ymd_His');		
		
		$this->fileObjectRef = self::$rep_niveau0 .'upload/'.$file['filename'].'_'.$date.'_objectRef.topo';		
		$this->cmd("touch ".$this->fileObjectRef);
		$this->cmd("chmod 777 ".$this->fileObjectRef);
		// $this->cmd("chown astellia.astellia ".$fileObjectRef);
		
		// Si les niveaux uniques sont des nouveaux �l�ments,
		// on ne prend pas la ligne correspondante dans la table de topo.
		$queryWhere = '';
		if ( count($this->naUniqueInHeader) > 0 )
		{
			$queryWhere = "WHERE eor_obj_type NOT IN ('".implode("','",$this->naUniqueInHeader)."')";
		}
		
		// 12:07 14/10/2009 SCT : ajout du champ eor_color pour la conservation de la valeur de ce champ lors du chargement de la topo
		$query = "
					--- R�cup�re la table edw_object_ref dans un fichier
					
					COPY (
						SELECT eor_id, eor_label, eor_obj_type, eor_on_off,eor_date,eor_blacklisted,eor_id_codeq,eor_color
						FROM ".self::$table_ref."
						".$queryWhere."
						)
					TO '".$this->fileObjectRef."'
					WITH DELIMITER '".self::$delimiter."'
						 NULL ''
				";		
		$this->sql($query);
		
	} // End function initFiles

	/**
	 * Fonction qui v�rifie si le label est pr�sent ou non dans le fichier
	 * @param string $_na : niveau d'agr�gation recherch�
	 * @return bool : true => label rep�r� / false => pas de label sur la na
	 */
	private function checkNaLabelExist($_na)
	{
		if( in_array($_na."_label", self::$header_db) )
		{
			return true;
		}
		else
		{
			return false;
		}
	} // End function checkNaLabelExist

	/**
	* Fonction qui  extrait du fichier charg� l'id, le label et le na de chaque �l�ment
	*
	*/
	private function createFileResultTmp()
	{
	
		$this->demon("createFileResultTmp => extraction du fichier charg� l'id, le label et le na de chaque �l�ment");
		$params = array("longitude","latitude","azimuth","trx","charge");
		

		foreach(self::$header_db as $column)
		{
			$cmd = array();
			$id_na 	= $this->getIdField($column, self::$header_db);

			if ( $id_na === null )
				continue;
			
			$this->demon("-> Gestion $column");
		
			$file = pathinfo(self::$file);
			$filename = date('Ymd_His');
			$file_result_complet_tmp =  self::$rep_niveau0 .'upload/'.$file['filename'].'_'.$filename.'_complet_tmp_tmp.topo';
			$file_result_tmp =  self::$rep_niveau0 .'upload/'.$file['filename'].'_'.$filename.'_result_tmp_tmp.topo';

			// On g�n�re le fichier sous la forme "na;na_label;obj_type"
			// On v�rifie la pr�sence du label du na dans le header
			// On exclut le on_off qui ne poss�de pas de label
			// maj 17:44 14/10/2009 MPR : Ajout des colonnes trx et charge � traiter comme les param�tres on_off et coordinates
			if( !preg_match ("/_label/",$column) and $column !== "on_off" and !in_array($column, $params))
			{
				self::$na[] = $column;

				// On r�cup�re toutes les donn�es du fichier dans $this->file_result_complet_tmp
				if( $this->checkNaLabelExist($column) )
				{
					// R�cup�ration de l'id de sa colonne
					$id_na_label 	= $this->getIdField($column."_label", self::$header_db);
					if ( $id_na_label === null )
						continue;
					
					$cmd = "cut -d'".self::$delimiter."' -f$id_na,$id_na_label ".self::$file_tmp_db." > ".$file_result_complet_tmp;
					$this->cmd($cmd);

					// On ajoute le na_label en v�rifiant que le na est <> de vide
					$awk = "awk 'BEGIN { FS=\"".self::$delimiter."\"; OFS=\"".self::$delimiter."\"}{
								if($1!=\"\"){
									print $0\"".self::$delimiter."$column\";
								}
							}' 	$file_result_complet_tmp >> ".$this->file_result_complet_tmp;

					// On supprime les commentaires
					$awk = preg_replace('/(.*)#.*/', '\1', $awk);
					// Supprime les tabulations et  les retours � la ligne
					$awk = preg_replace('/\s\s+/', ' ', $awk);

					$this->cmd($awk);

				}
				else
				{
					// On ajoute une colonne pour le label m�me s'il n'existe pas
					$cmd = "cut -d'".self::$delimiter."' -f$id_na ".self::$file_tmp_db." > ".$file_result_complet_tmp;
					$this->cmd($cmd);

					// On copy les donn�es si le na est <> null
					$awk = "awk 'BEGIN { FS=\"".self::$delimiter."\"; OFS=\"".self::$delimiter."\"}{
								if($1!=\"\"){
									print $0\"".self::$delimiter.self::$delimiter."$column\";
								}
							}' $file_result_complet_tmp >> ".$this->file_result_complet_tmp;

					// On supprime les commentaires
					$awk = preg_replace('/(.*)#.*/', '\1', $awk);
					// Supprime les tabulations et  les retours � la ligne
					$awk = preg_replace('/\s\s+/', ' ', $awk);

					$this->cmd($awk);
				}

				$cmd = "cut -d'".self::$delimiter."' -f$id_na ".self::$file_tmp_db." > ".$file_result_tmp;
				$this->cmd($cmd);

				// On copy les donn�es si le na est <> null
				$awk = "awk 'BEGIN { FS=\"".self::$delimiter."\"; OFS=\"".self::$delimiter."\"}{
								if($1!=\"\"){
									print $0\"".self::$delimiter."$column\";
								}
						}' $file_result_tmp >> ".$this->file_result_tmp;

				// On supprime les commentaires
				$awk = preg_replace('/(.*)#.*/', '\1', $awk);
				// Supprime les tabulations et  les retours � la ligne
				$awk = preg_replace('/\s\s+/', ' ', $awk);

				$this->cmd($awk);

				// 26/07/2010 BBX
				// Tri et d�doublonnage en PHP car certains caract�res sp�ciaux
				// ne passent pas avec la commande "sort"
				// Utilisation de la fonction "sort" PHP et suppression du seconde param�tre
				// de la fonction "array_unique" qui bug en PHP < 5.2.9
				// BZ 14969
				$tmpArcFile = file($this->file_result_tmp);
				$tmpArcFile = array_unique($tmpArcFile);
				sort($tmpArcFile,SORT_STRING);
				file_put_contents($this->file_result_tmp,$tmpArcFile);
			}
			
		}
		
		// 11:47 01/12/2008 GHX
		// prise en compte de la colonne on_off
		if ( in_array('on_off', self::$header_db) )
		{
			// R�cup�re le niveau minimum du fichier pour savoir sur quel niveau le on/off s'applique
			$naMin = $this->getNaMinIntoFile();
			
			// R�cup�ration de l'id de la colonne on_off
			$id_on_off	= $this->getIdField('on_off', self::$header_db);
			$id_naMin	= $this->getIdField($naMin, self::$header_db);

			
			$cmdAwk = "awk '
					BEGIN { 
						FS=\"".self::$delimiter."\";
						OFS=\"".self::$delimiter."\";
						file1=\"\";
					}
					{
						if ( 1==FNR )
						{
							if ( file1==\"\" )
							{
								file1=FILENAME;						
							}
						}
						# Si on est sur le premier fichier
						# on m�morise donc les valeurs du on/off
						if ( file1==FILENAME )
						{
							tabOnOff[$".$id_naMin."]=$".$id_on_off.";
						}
						else # Si on est sur le deuxieme fichier
						{
							#si on est sur le niveau minimum
							if ( $3==\"".$naMin."\" )
							{
								# on prend la valeur du on/off qui est dans le fichier
								if ( tabOnOff[$1] != \"\" )
								{
									# Correction du bug 10667 : Mise � jour du on_off NOK on remplace l'index id_naMin par $1
									print $0\"".self::$delimiter."\"tabOnOff[$1];
								}
								else
								{
									print $0\"".self::$delimiter."1\";
								}
							}
							else
							{
								print $0\"".self::$delimiter."1\";
							}
						}
					}
				' ".self::$file_tmp_db." ".$this->file_result_complet_tmp." > ".$this->file_result_complet_tmp."_tmp";
			
			// On supprime les commentaires
			$cmdAwk = preg_replace('/(.*)#.*/', '\1', $cmdAwk);
			// Supprime les tabulations et  les retours � la ligne
			$cmdAwk = preg_replace('/\s\s+/', ' ', $cmdAwk);

			$this->cmd($cmdAwk);
			
			$cmd = "uniq ".$this->file_result_complet_tmp."_tmp > ".$this->file_result_complet_tmp;
			$this->cmd($cmd);
		}
	} // End function createFileResultTmp

	/**
	 * Fonction qui r�cup�re les �l�ments r�seau pr�sents en base (on r�cup�re uniquement l'id et le na de chaque �l�ment
	 */
	private function createFileResultRef()
	{
	
		$this->demon("R�cup�ration des �l�ments r�seau pr�sents en base");
		
		$this->cmd("touch ".$this->file_result_ref);
		$this->cmd("chmod 777 ".$this->file_result_ref);
		// $this->cmd("chown astellia:astellia ".$this->file_result_ref);
		
		if ( self::$axe == 3 && $this->limitMax3rdAxis() ){
			$keepsElementsAlreadyInBase = false;
			
			// Compte le nombre d'�l�ment d�j� pr�sent en base
			$query = "SELECT count(*) FROM edw_object_ref WHERE eor_obj_type = '".self::$naMinIntoFile."'";
			$result = $this->sql($query);
			
			while( $row = pg_fetch_array($result) )
			{
				$nb_ne_limited = intval($row[0]);
			}
			
			// On r�cup�re la limite du nombre d'�l�ments 3�me axe
			$limit_third_axis = intval( get_sys_global_parameters("max_3rd_axis" ) );
				
			// D�termine le nombre d'�l�ment que l'on peut ajouter
			self::$nb_elems_axe3_limited = ($limit_third_axis)-($nb_ne_limited);
			
			// 11:31 06/01/2010 GHX
			// 17:54 13/01/2010 MPR
			// BZ 13251
			// Compte le nombre de ligne dans le fichier
			$nb_field_in_file = $this->cmd("awk -F\"".self::$delimiter."\" 'BEGIN{cpt=0;} $2==\"".self::$naMinIntoFile."\" {cpt=cpt+1;} END {print cpt}' $this->file_result_tmp",true);
			if ( $nb_field_in_file[0] < $limit_third_axis && self::$nb_elems_axe3_limited == 0 )
			{
				// Quand la valeur vaut -1 c'est qu'il n'y a rien � faire
				// La valeur vaut 0 et non -1 
				self::$nb_elems_axe3_limited = 0;
			}
		}
		
		if ( !( self::$nb_elems_axe3_limited == 0 && self::$axe == 3 && $this->limitMax3rdAxis() && self::$mode == "manuel" ) ){  
	
			$query = "
						COPY (
							SELECT eor_id, eor_obj_type
							FROM ".self::$table_ref."
							WHERE eor_obj_type IN ('".implode( "','",self::$na )."')
							)
						TO '".$this->file_result_ref."'
						WITH DELIMITER '".self::$delimiter."'
							 NULL ''
					";
			$this->demon($query,"query");
			$this->sql($query);
		}
	} // End function createFileResultRef
	
	/**
	* Fonction qui r�cup�re les nouveaux �l�ments � ins�rer
	*/
	private function getNewElements($file_result)
	{
		$this->cmd("dos2unix ".$this->file_result_ref);
		$this->cmd("dos2unix ".$this->file_result_tmp);
		
		if( file_exists($this->file_result_ref) )
			$nb_field_in_db = $this->cmd("awk 'END {print NR}' $this->file_result_ref",true);
		else
			 $nb_field_in_db[0] = 0;
			 
		$this->demon($nb_field_in_db," nbre de lignes en base");

		if ( $nb_field_in_db[0] >= 1 )
		{
			$limit = 0;
			$_condition = "print $0";
			if ( self::$axe == 3 && $this->limitMax3rdAxis() ){
		
				$limit = self::$nb_elems_axe3_limited;
				$_condition = "
				if( $2==\"".self::$naMinIntoFile."\" && ".self::$axe." == 3 && $limit >= 0 ){
									# Si la limite vaut 0 c'est qu'on ajout tous les �l�ments du fichier
									
						if($limit == 0 && \"".self::$mode."\" == \"manuel\"){
							print $0;
						} else {
							# sinon on complete pour arriver � la limite
							if( cpt < $limit ){
								print $0;
							}
							cpt=cpt+1;
						}
				} else {
					print $0;
				}
						";
			}
		
			// On extrait les nouveaux �l�ments
			$awk = "awk  ' BEGIN { file1=\"\"; cpt=0;  FS=\"".self::$delimiter."\" ;OFS=\"".self::$delimiter."\" } {
						if ( 1==FNR ) {
								if ( file1==\"\" ) {
									file1=FILENAME;
									header=$0;
								}
						} if ( file1==FILENAME ) {
							lines[$0]=1;
						} else {
							if ( lines[$0]==\"\") {
								# Gestion de la limite max_third_axis
								# 11:31 06/01/2010 GHX
								# BZ 13251
								# $limit >= -1 au lieu de $limit >= 0
								$_condition
							}
						}
					}' ".$this->file_result_ref." ".$this->file_result_tmp." > ".$this->file_result;

			// On supprime les commentaires
			$awk = preg_replace('/(.*)#.*/', '\1', $awk);
			// Supprime les tabulations et  les retours � la ligne
			$awk = preg_replace('/\s\s+/', ' ', $awk);

			$this->demon("awk ref/tmp");

			$this->cmd($awk);
			
			$file_uniq = $this->file_result."_".uniqid("")."_tmp.topo";
			$this->cmd("uniq ".$this->file_result." > ".$file_uniq);
			
			$this->cmd("mv $file_uniq ".$this->file_result);
			
		}
		else
		{
			// 26/07/2010 BBX
			// Tri et d�doublonnage en PHP car certains caract�res sp�ciaux
			// ne passent pas avec la commande "sort"
			// Utilisation de la fonction "sort" PHP et suppression du seconde param�tre
			// de la fonction "array_unique" qui bug en PHP < 5.2.9
			// BZ 14969
			$tmpArcFile = file($this->file_result_tmp);
			$tmpArcFile = array_unique($tmpArcFile);
			sort($tmpArcFile,SORT_STRING);
			file_put_contents($this->file_result,$tmpArcFile);

			$file = file($this->file_result_tmp);
            $this->demon($file,"file");
            // $this->demon($file,"file");
		}
		
		// 03/12/2008 GHX
		// R�cup�re les niveaux unique
		$allNaUnique = array();
		$queryNaUnique = "SELECT agregation FROM sys_definition_network_agregation WHERE na_max_unique = 1";
		$resultsNaUnique = $this->sql($queryNaUnique);
		if ( pg_num_rows($resultsNaUnique) > 0 )
		{
			while( list($naUnique) = pg_fetch_row($resultsNaUnique) )
			{
				$allNaUnique[] = $naUnique;
			}
		}
		
		// 01/12/2008 GHX
		// Ajout les nouveaux �l�ments au tableau change summary
		$newElements = file($this->file_result);
		
		// $this->demon(self::$naLabel,"naLabel");
		// $this->demon($newElements,"tab newElements");
		// 11:31 06/01/2010 GHX
		// BZ 13251 : ajout des 2 derniers conditions dans le if
		if ( self::$mode == 'manuel' and $limit == 0 && self::$axe == 3 && $this->limitMax3rdAxis() )
		{
		
			foreach(self::$header_db as $id=>$column){
			
				$params = array("longitude","latitude","azimuth","trx","charge");
				if( !preg_match ("/_label/",$column) and $column !== "on_off" and !in_array($column, $params))
					$lst_na[] = $column;
			}
			
			$this->queries[] = "DELETE FROM edw_object_ref WHERE eor_obj_type IN ('".implode("','", $lst_na)."')";//self::$naMinIntoFile."'";
			$this->queries[] = "DELETE FROM edw_object_arc_ref WHERE eoar_arc_type LIKE '".self::$naMinIntoFile."|s|%'";
			// $this->setQueries( implode(";",$queries) );
		}
		$nb_new_elems = count($newElements);
		foreach ( $newElements as $newElement )
		{
			$newElement = explode(self::$delimiter, trim($newElement));
			
			$this->demon($newElement,"New element identifi�");
			
			$this->set_changes(
				array(
					self::$naLabel[$newElement[1]], 
					$newElement[0],
					__T('A_UPLOAD_TOPO_NEW_ELEMENT', self::$naLabel[$newElement[1]]),
					' ',
					$newElement[0]
				)
			);
			// 03/12/2008 GHX
			// Si le niveau est unique
			if ( in_array($newElement[1], $allNaUnique) )
			{
				$this->naUniqueInHeader[] = $newElement[1];
			}
		}
	} // End function getNewElements

	/**
	* Function completeFileResult : Compl�te le fichier contenant les nouveaux �l�ments
	* @param string $file_result : fichier contenant les nouveaux �l�ments r�seau ou 3�me axe
	* @param string $file_parameters : fichier contenant tous les �l�ments r�seau de niveau minimum (� charger dans edw_object_ref_parameters
	*/
	private function completeFileResult($file_result,$file_parameters,$file_parameters_erlang)
	{		
		// On ajoute la colonne label
		$awk = "awk  ' BEGIN { file1=\"\"; FS=\"".self::$delimiter."\" ;OFS=\"".self::$delimiter."\" } {

					if ( 1==FNR ) {
						if ( file1==\"\" ) {
							file1=FILENAME;
						}
					}
					if ( file1==FILENAME ) {
						lines[$0]=1;

					}
				    else {
						# Correction du bug 10672 : Le charment d'un fichier avec le delimiter , ne fonctionnait pas
						# Remplacement du s�parateur ; par self::delimiter
						if ( lines[$1\"".self::$delimiter."\"$3]==1 ) {
							print $0;
						}
					}
				}' $this->file_result $this->file_result_complet_tmp > $file_result";

		// On supprime les commentaires
		$awk = preg_replace('/(.*)#.*/', '\1', $awk);
		// Supprime les tabulations et  les retours � la ligne
		$awk = preg_replace('/\s\s+/', ' ', $awk);
		$this->debug($awk,"awk complet file result");
		$this->cmd($awk);
 
		// On ajoute la date d'insertion
		$cmd = "awk '{print \"".self::$day.self::$delimiter."\"$0'} $file_result > $this->file_result";
		$this->cmd($cmd);

		// Pr�paration de la requ�te COPY
		$select = "eor_date, eor_id, eor_label,eor_obj_type";

		// Gestion du on_off
		if( in_array('on_off',self::$header_db) )
		{
			$select.= ',eor_on_off'; // Pr�paration de la requ�te COPY
		}


		// 26/07/2010 BBX
		// Tri et d�doublonnage en PHP car certains caract�res sp�ciaux
		// ne passent pas avec la commande "sort"
		// Utilisation de la fonction "sort" PHP et suppression du seconde param�tre
		// de la fonction "array_unique" qui bug en PHP < 5.2.9
		// BZ 14969
		$tmpArcFile = file($this->file_result);
		$tmpArcFile = array_unique($tmpArcFile);
		sort($tmpArcFile,SORT_STRING);
		file_put_contents($this->file_result,$tmpArcFile);

		// $file = file($this->file_result);
		// $this->demon($file,"file result :".$this->file_result);
		
		// On ajoute les donn�es en base dans la table edw_object_ref
		$query = "COPY ".self::$table_ref."($select)
				  FROM '$this->file_result' WITH DELIMITER '".self::$delimiter."' NULL '';
				 ";
				 
		// Ajout de la requ�te SQL dans le tableau self::queries
		$this->setQueries($query);
		$this->debug($query,"query");

		// Gestion des coordonn�es G�ographiques
		
		$this->demon(self::$na_min,"na_min");
		$this->demon(self::$header_db);
		if( in_array( self::$na_min, self::$header_db ) )
		{
		
			$table_temp = $this->setGeographicalCoordinates($file_parameters);

			if( self::$activate_capacity_planing &&  self::$activate_trx_charge_in_topo ){
				// On r�utilise la table temp si elle a �t� cr��
				$table = ($table_temp !== false) ? $table_temp: false;
				$table_temp2 = $this->setParametersErlang($file_parameters_erlang, $table);
				$table = $table_temp2;
				
			} else {
				$table = $table_temp;
			}
			// On ajoute les donn�es en base dans la table edw_object_ref
			
			// Construction du SELECT
			$select = "eorp_id,eorp_longitude,eorp_latitude,eorp_azimuth";

			if( self::$activate_capacity_planing &&  self::$activate_trx_charge_in_topo )
				$select.= ",eorp_trx,eorp_charge";
				
			$file = file($this->file_parameters);
			$this->demon($file,"FILE PARAMETERS");
			
			// R�cup�ration de la table temp g�n�r� soit pendant la maj des coordonn�es soit pdt la maj des params trx et charge
			// $table = false;
			// if( $table_temp !== false ){
				// $table = $table_temp;
			// } 
			// if($table_temp2 !== false ) {
				// $table = $table_temp2;
			// }
			
			$this->demon($table,"TABLE TEMP");
			
			// 14:45 03/12/2008 GHX
			// si le nom de la table temporaire n'existe pas c'est que les colonnes des coordonn�es ne sont pas dans le fichier donc pas de mise � jour a faire sur la table
			if( count($file)>0 && $table !== false  ){
			
				
				$querie1 = "DELETE FROM ".self::$table_params_ref." WHERE eorp_id IN (SELECT na_min FROM $table)";
				$querie2 = "COPY ".self::$table_params_ref."($select)
						  FROM '$this->file_parameters' WITH DELIMITER '".self::$delimiter."' NULL '';
						 ";

                // 07/07/2010 OJT : Correction bz15710 Mise � NULL des valeurs long/lat � z�ro
                $querie3 = 'UPDATE '.self::$table_params_ref.' SET eorp_longitude=NULL,eorp_latitude=NULL,eorp_azimuth=NULL WHERE eorp_longitude=\'0\' AND eorp_latitude=\'0\';';

				// Ajout de la requ�te SQL dans le tableau self::queries
				$this->setQueries($querie1);
				$this->setQueries($querie2);
                $this->setQueries($querie3);
			}
		}

	} // End function completeFileResult

	/**
	* Function setGeographicalCoordinates : Met � jour les coordonn�es g�ographiques pour les �l�ments
	* @param string $file_parameters : fichier temporaire contenant les nouveaux �l�ments
	* @return string $table_temp : Table temporaire o� sont stock�s les nouveaux �l�ments (n�cessaire pour �viter les doublons)
	*/
	public function setGeographicalCoordinates($file_parameters)
	{
		$id_longitude = $this->getIdField('longitude', self::$header_db);
		$id_latitude = $this->getIdField('latitude', self::$header_db);
		$id_azimuth = $this->getIdField('azimuth', self::$header_db);
		$file_coords_db = "{$file_parameters}.db.topo";

		$awk = "awk 'BEGIN{FS=\"".self::$delimiter."\";OFS=\"".self::$delimiter."\"}{
						if($2==\"".self::$na_min."\"){
							print $1;
						}
					}' $this->file_result_tmp > $file_parameters";

		// On supprime les commentaires
		$awk = preg_replace('/(.*)#.*/', '\1', $awk);
		// Supprime les tabulations et  les retours � la ligne
		$awk = preg_replace('/\s\s+/', ' ', $awk);
		$this->demon("Check mise � jour coordonn�es");
		$this->cmd($awk);
		
		// 14:44 03/12/2008 GHX
		// initialisation � false sinon probl�me car la table n'existe pas et du coup pas de mise � jour de la topo
		$table_temp = false;

		$this->demon("Mise � jour des Coordonn�es");
		$table_temp = "edw_object_ref_parameters_".uniqid("");
		$queries[] = "CREATE TEMP TABLE $table_temp(na_min TEXT)";
		$queries[] = "COPY $table_temp FROM '$file_parameters' WITH DELIMITER ';' NULL ''";
			
		$queries[] = "	COPY (
						SELECT eorp_id, eorp_longitude, eorp_latitude, eorp_azimuth
						FROM edw_object_ref_parameters, $table_temp
						WHERE eorp_id = na_min
						)
					TO '{$file_coords_db}'
					WITH DELIMITER '".self::$delimiter."'
						 NULL ''
					";
				
		self::sql(implode(";",$queries)); 	// R�cup�ration des Id des colonnes
			
		if( in_array("longitude",self::$header_db) and in_array("latitude",self::$header_db) and in_array("azimuth",self::$header_db) )	{
		
			$this->demon(self::$header_db,"Maj coordinates with AWK");
			
			
			$id_na_min_in_tmp = $this->getIdField(self::$na_min,self::$header);

			// Construction de la commande
			$awk = "awk  ' BEGIN { file1=\"\"; FS=\"".self::$delimiter."\" ;OFS=\"".self::$delimiter."\" } {

						if ( 1==FNR ) {
							if ( file1==\"\" ) {
								file1=FILENAME;
							}
						}
						if ( file1==FILENAME ) { # Traitement sur le premier fichier

							tableauCoordinates[$$id_na_min_in_tmp] = $$id_longitude\"".self::$delimiter."\"$$id_latitude\"".self::$delimiter."\"$$id_azimuth; # On m�morise la valeur du on_off pour chaque na_min
						}
						else {  # Traitement sur le deuxieme fichier
								if( tableauCoordinates[$1]!=\"\" ){
									print $0\"".self::$delimiter."\"tableauCoordinates[$1];
								}else{
									print $0\"".self::$delimiter.self::$delimiter.self::$delimiter."\";
								}

						}
					}' ".self::$file_tmp." $file_parameters > $this->file_parameters";
		}
		else
		{
			$this->demon("Maj coordinates with CAT");
			// On ajoute les colonnes
			$awk = "cat ".$file_coords_db." > $this->file_parameters";
		}

		// On supprime les commentaires
		$this->demon($awk);
		$awk = preg_replace('/(.*)#.*/', '\1', $awk);
		// Supprime les tabulations et  les retours � la ligne
		$awk = preg_replace('/\s\s+/', ' ', $awk);

		$this->cmd($awk);
		
		return $table_temp;
	} // End function setGeographicalCoordinates

	/**
	* Function setParametersErlang : Met � jour les parameters du calcul de Erlang (trx et charge)
	* @param string $file_parameters : fichier temporaire contenant les nouveaux �l�ments
	* @return string $table_temp : Table temporaire o� sont stock�s les nouveaux �l�ments (n�cessaire pour �viter les doublons)
	*/
	public function setParametersErlang($file_parameters, $table_temp =false )
	{
		$coords=false;
		$file_all_parameters = $this->file_parameters_erlang.".result.topo";
		$file_erlang_db = $this->file_parameters_erlang.".db.topo";
		
		$id_trx = $this->getIdField('trx', self::$header_db);
		$id_charge = $this->getIdField('charge', self::$header_db);
		
		$awk = "awk 'BEGIN{FS=\"".self::$delimiter."\";OFS=\"".self::$delimiter."\"}{
						if($2==\"".self::$na_min."\"){
							print $1;
						}
					}' $this->file_result_tmp > $file_parameters";

		// On supprime les commentaires
		$awk = preg_replace('/(.*)#.*/', '\1', $awk);
		// Supprime les tabulations et  les retours � la ligne
		$awk = preg_replace('/\s\s+/', ' ', $awk);
		$this->demon("Check mise � jour params NbErlang");
		$this->cmd($awk);
		
			
		$this->demon("Mise � jour des Parameters trx et charge (Erlang)");
		$file_final = $this->file_parameters_erlang;
		if( $table_temp == false){
			$table_temp = "edw_object_ref_parameters_".uniqid("");
			$queries[] = "CREATE TEMP TABLE $table_temp(na_min TEXT)";
			$queries[] = "COPY $table_temp FROM '$file_parameters' WITH DELIMITER ';' NULL ''";
			
			
			$coords = true;
			$file_final = $this->file_parameters;
		}
		
		// 14:19 06/01/2010 GHX
		// Modification de la requete SELECT
		$queries[] = "	COPY (
						SELECT na_min, eorp_trx, eorp_charge
						FROM $table_temp LEFT JOIN edw_object_ref_parameters  ON (na_min = eorp_id)
						)
					TO '{$file_erlang_db}'
					WITH DELIMITER '".self::$delimiter."'
						 NULL ''
					";
		
		self::sql(implode(";",$queries)); 
		// R�cup�ration des Id des colonnes
		$id_na_min_in_tmp = $this->getIdField(self::$na_min,self::$header);
		$this->demon($id_na_min_in_tmp,"ID NA MIN");
		
		if( in_array("trx",self::$header_db) and in_array("charge",self::$header_db) )	{

			// Construction de la commande
			$awk = "awk  ' BEGIN { file1=\"\"; FS=\"".self::$delimiter."\" ;OFS=\"".self::$delimiter."\" } {

						if ( 1==FNR ) {
							if ( file1==\"\" ) {
								file1=FILENAME;
							}
						}
						if ( file1==FILENAME ) { # Traitement sur le premier fichier

							tableauParams[$$id_na_min_in_tmp] = $$id_trx\"".self::$delimiter."\"$$id_charge; # On m�morise la valeur des parameters pour chaque na_min
						}
						else {  # Traitement sur le deuxieme fichier
								if( tableauParams[$1]!=\"\" ){
									print $0\"".self::$delimiter."\"tableauParams[$1];
								}else{
									print $0\"".self::$delimiter.self::$delimiter."\";
								}

						}
					}' ".self::$file_tmp." $file_parameters > {$file_final}";
		}
		elseif( in_array("trx",self::$header_db) )
		{
			$awk = "awk  ' BEGIN { file1=\"\"; FS=\"".self::$delimiter."\" ;OFS=\"".self::$delimiter."\" } {

						if ( 1==FNR ) {
							if ( file1==\"\" ) {
								file1=FILENAME;
							}
						}
						if ( file1==FILENAME ) { # Traitement sur le premier fichier

							tableauTrx[$$id_na_min_in_tmp] = $$id_trx; # On m�morise la valeur de trx pour chaque na_min
						}
						else {  # Traitement sur le deuxieme fichier
							if( tableauTrx[$1]!=\"\" ){
								print $0\"".self::$delimiter."\"tableauTrx[$1]\"".self::$delimiter."1\";
							}else{
								print $0\"".self::$delimiter.self::$delimiter."1\";
							}
						}
					}' ".self::$file_tmp." {$file_parameters} > {$file_final}";
				
		}elseif( in_array("charge",self::$header_db)){
			
			$awk = "awk  ' BEGIN { file1=\"\"; FS=\"".self::$delimiter."\" ;OFS=\"".self::$delimiter."\" } {

						if ( 1==FNR ) {
							if ( file1==\"\" ) {
								file1=FILENAME;
							}
						}
						if ( file1==FILENAME ) { # Traitement sur le premier fichier

							tableauCharge[$$id_na_min_in_tmp] = $$id_charge; # On m�morise la valeur de trx pour chaque na_min
						}
						else {  # Traitement sur le deuxieme fichier
							if( tableauCharge[$1]!=\"\" ){
								print $0\"".self::$delimiter.self::$delimiter."\"tableauCharge[$1];
							}else{
								print $0\"".self::$delimiter.self::$delimiter."\";
							}
						}
					}' ".self::$file_tmp." {$file_parameters} > {$file_final}";
		
		} else {
			
			// On ajoute les colonnes
			$awk = "cat {$file_erlang_db} > {$file_final}";
			
		}

		// On supprime les commentaires
		// $this->demon($awk);
		$awk = preg_replace('/(.*)#.*/', '\1', $awk);
		// Supprime les tabulations et  les retours � la ligne
		$awk = preg_replace('/\s\s+/', ' ', $awk);

		$this->demon("Complete File with params Erlang");
		$this->cmd($awk);
		
		// $this->demon( $file_final,"file_final");
		// $this->demon( $this->file_parameters_erlang,"file_parameters_erlang");
		if($file_final == $this->file_parameters_erlang ){
			
			// $this->demon(file($this->file_parameters),"FILE file_parameters");
			// $this->demon(file($this->file_parameters_erlang),"FILE file_parameters_erlang");
			
			// On joint les deux fichiers coord + erlang
			$cmd = "join -t\"".self::$delimiter."\" -a1 -1 1 -2 1 {$this->file_parameters} {$this->file_parameters_erlang} | tee /home/cb5022_iu50005_multi_rec/upload/tototoot > ".$file_all_parameters;
			$this->cmd($cmd);

			$this->cmd("mv ".$file_all_parameters." ".$this->file_parameters);
		
		}

		return $table_temp;
	} // End function setGeographicalCoordinates

	
	/**
	 * Ajout dans le fichier pass� en param�tre les qui ont un changement de label et on/off
	 *
	 *	01/12/2008 GHX
	 *		- cr�ation de la fonction
	 *
	 * @param string $file_update fichier qui contiendra le r�sultat
	 */
	private function getUpdateElements ( $file_update )
	{
		if( file_exists($this->file_result_ref) ){
			$nb_field_in_db = $this->cmd("awk 'END {print NR}' $this->file_result_ref",true);
		}else{
			$nb_field_in_db[0] = 0;
		}
		$this->demon($nb_field_in_db," nbre de lignes en base");

		if ($nb_field_in_db[0] >= 1)
		{
			// On extrait les nouveaux �l�ments
			$awk = "awk  '
						BEGIN { file1=\"\"; }
						{
							if ( 1==FNR )
							{
								if ( file1==\"\" )
								{
									file1=FILENAME;
									header=$0;
								}
							}
							if ( file1==FILENAME ) 
							{
								lines[$0]=1;
							}
							else 
							{
								if ( lines[$0]==\"\" ) 
								{
									print $0;
								}
							}
						}
					' ".$this->file_result_ref." ".$this->file_result_tmp." > ".$this->file_result;

			// On supprime les commentaires
			$awk = preg_replace('/(.*)#.*/', '\1', $awk);
			// Supprime les tabulations et  les retours � la ligne
			$awk = preg_replace('/\s\s+/', ' ', $awk);

			// $this->demon($awk,"awk ref/tmp");

			$this->cmd($awk);
		}
		else
		{
                        // 26/07/2010 BBX
                        // Tri et d�doublonnage en PHP car certains caract�res sp�ciaux
                        // ne passent pas avec la commande "sort"
                        // Utilisation de la fonction "sort" PHP et suppression du seconde param�tre
                        // de la fonction "array_unique" qui bug en PHP < 5.2.9
                        // BZ 14969
                        $tmpArcFile = file($this->file_result_tmp);
                        $tmpArcFile = array_unique($tmpArcFile);
                        sort($tmpArcFile,SORT_STRING);
                        file_put_contents($this->file_result,$tmpArcFile);

			// $file = file($this->file_result_tmp);
			// $this->demon($file,"file");
		}
	} // End function getNewElements
	
	/**
	 * Cr�ation d'un fichier si n�cessaire pour mettre � jour les labels et le on/off
	 *
	 * @author GHX
	 * @since CB4.1.0.00
	 */
	private function updateLabelOnOff ()
	{
		$this->demon('<br /><b>Mise � jour des labels et du on/off</b>');
		
		// Cr�ation d'un fichier contenant les donn�es de la table edw_object_ref
		// pour pouvoir mettre � jour les labels et le on/off
		$file = pathinfo(self::$file);
		$date = date('Ymd_His');		
		$fileObjectRef = $this->fileObjectRef;
		
		// Cr�ation du fichier de resultat
		$fileObjectRefResult = self::$rep_niveau0 .'upload/'.$file['filename'].'_'.$date.'_objectRefResult.topo';		
		$this->cmd("touch ".$fileObjectRefResult);
		$this->cmd("chmod 777 ".$fileObjectRefResult);
		// $this->cmd("chown astellia.astellia ".$fileObjectRefResult);
		
		// R�cup�re le niveau minimum du fichier pour savoir sur quel niveau le on/off s'applique
		$naMin = $this->getNaMinIntoFile();
		// prise en compte de la colonne on_off
		// Si la colonne on_off n'est pas pr�sente on commente certaines lignes de la commande awk suivante
		$hasOnOff = (in_array('on_off', self::$header_db) ? '' : '#' );
		
		// Correction du bug 11224 : On r�cup�re la valeur du label en base si la colonne na_label n'est pas pr�sente. 
		//				       Sinon on �crase la valeur par celle pr�sente dans le fichier charg�
		$checkLabel = array();
		foreach(self::$header_db as $field){
			
			$tab = explode("_",$field);			

			if( count($tab) > 1 ){
				$checkLabel[] = "($3 == \"{$tab[0]}\")";
			}
		}
		
		
		$conditionLabel = "";
		if(count($checkLabel) > 0){
			$conditionLabel = "&& (".implode("||", $checkLabel ).")";
		}
		
		$condition = ($conditionLabel !== "") ? "if ( tabLabels[$3, $1] != $2 {$conditionLabel} ) {	$2 = tabLabels[$3, $1]; }" : "";

			
		
		$hasLabel = (in_array($naMin.'_label', self::$header) ? '' : '#' );

		// Cr�ation d'une commande awk qui effectuera les changements de labels
		$cmdAwk = "awk '
					BEGIN { 
						FS=\"".self::$delimiter."\";
						OFS=\"".self::$delimiter."\";
						file1=\"\";
					}
					{
						if ( 1==FNR )
						{
							if ( file1==\"\" )
							{
								file1=FILENAME;						
							}
						}
						# Si on est sur le premier fichier
						if ( file1==FILENAME )
						{
							# M�morise les labels de chaques �l�ments reseaux
							# du fichier upload�
							tabExists[$3, $1]=1;
					tabLabels[$3, $1]=$2;
				{$hasOnOff} tabOnOff[$3, $1]=$4;
						}
						else # Si on est sur le deuxieme fichier
						{
							# si le label est pr�sent dans le tableau
							# cest que l�l�ment est dans le fichier upload�
							if ( tabExists[$3, $1]==1 )
							{
								# si le label change
								{$condition}
								
								# Si on est sur le niveau minimum
								# on prend la valeur du on/off qui est dans le fichier
					{$hasOnOff} if ( $3 == \"{$naMin}\" )
					{$hasOnOff} {
					{$hasOnOff}		$4 = tabOnOff[$3, $1];
					{$hasOnOff} }
							}
							print $0;
						}
					}
				' ".$this->file_result_complet_tmp." ".$fileObjectRef." > ".$fileObjectRefResult;

		// On supprime les commentaires
		$cmdAwk = preg_replace('/(.*)#.*/', '\1', $cmdAwk);
		// Supprime les tabulations et  les retours � la ligne
		$cmdAwk = preg_replace('/\s\s+/', ' ', $cmdAwk);
		
		$this->demon("Mise � jour du on_off et des labels");
		$this->cmd($cmdAwk, true);
		
		// R�garde si on a eu des changements
		$cmdDiff = 'diff '.$fileObjectRef.' '.$fileObjectRefResult;
		$resultDiff = $this->cmd($cmdDiff, true);
		
		// Ajout d'une variable pour savoir si on r�inserte les donn�es
		// car dans le cas d'un nouveau �l�ment max et pas changement de on/off ou label
		// il y aura des lignes en trop en base
		$reinsertData = false;
		
		// Si on a des diff�rences
		if ( count($resultDiff) > 0 )
		{
			$reinsertData = true;
			// R�cup�re les changements de label et on/off pour les mettre dans un tableau
			$tmpBefore = array();
			foreach ( $resultDiff as $oneLine )
			{
				/*
					$line[1] = eor_id
					$line[2] = eor_label
					$line[3] = eor_obj_type
					$line[4] = eor_on_off
				*/
				if ( preg_match(str_replace('/;/', self::$delimiter ,'/< ([^;]*);([^;]*);([^;]*);([^;]*);.*/'), $oneLine, $line) )//l'expression reguliere est modifi�e pour utiliser le delimiateur courrant au lieu de ';'
				{
					// R�cup�re les �l�ments avant changement
					$tmpBefore[$line[3]][$line[1]] = array(
						'label' => $line[2],
						'on_off' => $line[4]
					);
				}
				elseif ( preg_match(str_replace('/;/', self::$delimiter ,'/> ([^;]*);([^;]*);([^;]*);([^;]*);.*/'), $oneLine, $line) )//l'expression reguliere est modifi�e pour utiliser le delimiateur courrant au lieu de ';'
				{
	
					// Changement de label
					if ( $tmpBefore[$line[3]][$line[1]]['label'] != $line[2] )
					{
						$this->set_changes(
								array(
									self::$naLabel[$line[3]],
									$line[1],
									__T('A_UPLOAD_TOPO_CHANGE_LABEL'),
									( $tmpBefore[$line[3]][$line[1]]['label'] == '' || $tmpBefore[$line[3]][$line[1]]['label'] == null ? 'NULL' : $tmpBefore[$line[3]][$line[1]]['label']),
									$line[2]
								)
							);
					}
					// Changement du on/off
					if ( $tmpBefore[$line[3]][$line[1]]['on_off'] != $line[4] )
					{
						$this->set_changes(
							array(
								self::$naLabel[$line[3]],
								$line[1],
								__T('A_UPLOAD_TOPO_CHANGE_ON_OFF'),
								$tmpBefore[$line[3]][$line[1]]['on_off'],$line[4]
							)
						);
					}
				}
			}
		}
		
		// Si il y a eu des changements ou qu'il y a un nouveau �l�ment unique 
		// On r�int�gre le fichier de contenant la table actuelle de topo avec les changements de label, on/off
		if ( $reinsertData || count($this->naUniqueInHeader) > 0 )
		{
			// Cr�ation des requetes pour mettre � jour les labels et on/off
			$queryTruncate = "TRUNCATE ".self::$table_ref;
			$this->setQueries($queryTruncate);
			
			// 12:07 14/10/2009 SCT : ajout du champ eor_color pour la conservation de la valeur de ce champ lors du chargement de la topo
			$queryCopy = "
					COPY ".self::$table_ref."(eor_id, eor_label, eor_obj_type, eor_on_off, eor_date, eor_blacklisted,eor_id_codeq,eor_color)
					FROM '".$fileObjectRefResult."' 
					WITH DELIMITER '".self::$delimiter."' NULL '';
					";
			$this->setQueries($queryCopy);
			
		}
		if( isset( $this->queries ) ){
			$this->setQueries( implode(";", $this->queries ) );
		}
	} // End function updateLabelOnOff
	
	/**
	 * 
	 * Creation des requetes permettant de modifier les labels posant problemes
	 */
	private function fixLabels(){		
		 $labelNotUnique = array();

		foreach ( self::$header_db as $header )
		{
			// Si c'est une colonne contenant des labels
			if ( preg_match('/([^_]*)_label$/', $header, $res ) )
			{
				// Nom de l'�l�ment r�seau correspondant � la colonne label
				$na = $res[1];
				
				if( ! NaModel::IsNonUniqueLabelAuthorized($na,$this->_productId) ){
					$query = "
	                    SELECT ".$na." as ne_in_file, ne_in_db, ".$na."_label
	                    FROM ".self::$table_ref."_tmp
	                    LEFT JOIN (
	                        SELECT eor_label,eor_id  as ne_in_db
	                        FROM ".self::$table_ref." WHERE eor_obj_type='".$na."'
	                    ) as e ON (eor_label=".$na."_label)
	                    WHERE ne_in_db IS NOT NULL AND {$na} <> ne_in_db AND ne_in_db NOT IN (SELECT ".$na." FROM ".self::$table_ref."_tmp);";
	
                    $result = $this->sql($query);

                    if ( @pg_num_rows($result) )
                    {
                        while ( list($na_value_in_file, $na_value_in_db, $na_value_label) = pg_fetch_row($result) )
                        {
                            $labelNotUnique[$na][$na_value_label][$na_value_in_db][] 	 = $na_value_in_file;
                        }
                    }
				}
			}
		}
		
		if ( count($labelNotUnique) > 0 )
		{
			foreach ( $labelNotUnique as $na => $na_values )
			{
				foreach ( $na_values as $na_value => $na_value_labels )
				{
					foreach( $na_value_labels as $db => $file){ 
						//supprime les labels posant problemes
						$query = "UPDATE ".self::$table_ref." SET eor_label=NULL WHERE eor_obj_type='".$na."' AND eor_id='".$db."'";
						$this->setQueries($query);				
						$this->set_changes(
							array(
								$na,
								$db,
								__T('A_UPLOAD_TOPO_CHANGE_LABEL'),
								$na_value,
								'NULL'
							)
						);
					}
				}
			}
		}
	}// End function fixLabels
	
} // End class TopologyAddElements
?>