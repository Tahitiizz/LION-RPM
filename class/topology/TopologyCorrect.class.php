<?php

/*
 * 	@cb41000@
 *
 * 	14/11/2007 - Copyright Astellia
 *
 * 	Composant de base version cb_4.1.0.00
 *
 *
 * 	01/12/2008 GHX
 * 		- Renomage de tous les fichiers csv cr��s en fichier topo (plus facile pour supprimer les fichiers � la fin de l'upload)
 * 		- R�cup�ration des �l�ments qui changent de parents pour l'affichage dans Topology Summary
 * 	21/08/2009 GHX
 * 		- Mise en commentaire 2 dos2unix
 * 	25/08/2009 MPR 
 * 		- Correction du bug 11196 - On ex�cute la commande awk uniquement s'il y a eu des modifs
 * 	01/12/2009 MPR 
 * 		- Correction du bug 12959 - Chargement de plusieurs fichiers - On ne remplace pas la valeur existante par une valeur nulle 
 * 	06/01/2010 GHX
 * 		- RE-Correction du BZ 13251 [REC][T&A IU 5.0][TC#5904][TOPOLOGY]: max_3rd_axis non pris en compte dans upload topo 3� axe
 * 	 	- Modif dans la fonction getNewArc()
 * 	13/01/2010 MPR
 * 		-  RE-Correction du BZ 13251 [REC][T&A IU 5.0][TC#5904][TOPOLOGY]: max_3rd_axis non pris en compte dans upload topo 3� axe
 * 		-  modif inverse la limite doit �tre 0 et non -1
 * 	17/03/2010 - MPR : Correction du BZ 14778 
 * 		- Affichage des nouveaux arcs dans summary changes
 * 		- Remplacement de la commande sed par awk (permet d'enlever les parents vides et de reformater le fichier)
 *       26/07/2010 BBX : BZ 14969
 *               - Tri et d�doublonnage en PHP car certains caract�res sp�ciaux ne passent pas avec la commande "sort"
 *               - Utilisation de la fonction "sort" PHP et suppression du second param�tre de la fonction "array_unique" qui bug en PHP < 5.2.9
 * 	02/02/2011 MMT : Correction du BZ 19696
 *    - affiche correctement les valeures modifi�s lors d'un reparenting: les valeures nouvelles et le reparenting
 * 12/02/2015 JLG bz 45947/20604 : if one msc is associated to a network, 
 * 	all other items with unique parent (msc, smscenter...) will be associated with this network
 */
?>
<?php

/**
 * 	Classe TopologyCorrect 	- On ajoute ou modifie les relations entre les �l�ments r�seau ou 3�me axe dans la table edw_object_arc_ref 
 * 					- On ajoute ou modifie leur param�tres dans la table edw_object_ref_parameters
 * 					- Elle h�rite de la classe TopologyLib
 */
?>
<?php

class TopologyCorrect extends TopologyLib {

    // -------------------------------------------- M�thodes--------------------------------------------//
    //02/02/2011 MMT :  BZ 19696
    // contains list of NEs that have already been logged as reparented
    // prevents from logging the line with oldValue "null" if already logged as reparented with
    //an existing oldValue
    private $reparentedNesForChanges = array();

    /**
     * Construtor
     *
     * @acces public
     */
    public function __construct() {
        $this->demon("<hr/>CORRECT TOPOLOGY<hr/>");

        $this->initFiles();

        $this->reparenting();
    }

// End function __construct()

    /**
     * Fonction qui ex�cute les �tapes du reparenting
     *
     * @acces private
     */
    private function reparenting() {

        $this->getArc();
        $this->demon($this->arc, "this->arc");

        // Cr�ation du fichier temporaire contenant les arcs du fichier charg�
        $this->createFileArcTmp();

        // Cr�ation du fichier temporaire contenant les arcs de la table edw_object_arc_ref 
        $this->createFileArcRef();

        if (file_exists($this->file_arc_ref))
            $nb_field_in_arc_ref = $this->cmd("awk 'END {print NR}' $this->file_arc_ref", true);
        else
            $nb_field_in_arc_ref[0] = 0;
        $this->demon($nb_field_in_arc_ref, " nbre de lignes en base");
        // Si la table est vide, on int�gre directement les arcs du fichier en base
        if ($nb_field_in_arc_ref[0] >= 1) {

            // R�cup�ration des nouveaux arcs
            $this->getNewArc();
            if (file_exists($this->file_new_arc)) {
                $nb_field_in_new_arc = $this->cmd("awk 'END {print NR}' $this->file_new_arc", true);
            } else {
                $nb_field_in_new_arc[0] = 0;
            }
            $this->demon($nb_field_in_new_arc, " nbre de lignes ds file_new_arc");
            // On v�rifie qu'il y a bien des changements � effectuer
            if ($nb_field_in_new_arc[0] >= 1) {
                $this->createFileArcResult();
                // maj 25/08/2009 - MPR : Correction du bug 11196 - On ex�cute la commande awk uniquement s'il y a eu des modifs
                $maj = true;
            } else {
                $this->demon("Aucune Mise � jour");
                // maj 25/08/2009 - MPR : Correction du bug 11196 - On ex�cute la commande awk uniquement s'il y a eu des modifs
                $maj = false;
            }

            // Insertion des donn�es en base
            // maj 25/08/2009 - MPR : Correction du bug 11196 - On ex�cute la commande awk uniquement s'il y a eu des modifs
            if ($maj) {
                $nb_field_in_result = $this->cmd("awk 'END {print NR}' $this->file_copy_arc_ref", true);
            } else {
                $nb_field_in_result[0] = 0;
            }
            $this->demon($nb_field_in_result, " nbre ds file_copy_arc_ref");


            if ($nb_field_in_result[0] >= 1) {

                $this->insertTopologyArcs($this->file_copy_arc_ref);
            }
        } else {
            // Insertion des donn�es en base

            if (file_exists($this->file_arc_tmp)) {
                $nb_field_in_file_arc_tmp = $this->cmd("awk 'END {print NR}' $this->file_arc_tmp", true);
            } else {
                $nb_field_in_file_arc_tmp[0] = 0;
            }
            $this->demon($nb_field_in_file_arc_tmp, " nbre ds file_copy_arc_ref");

            // $file = file($this->file_arc_tmp);
            if ($nb_field_in_file_arc_tmp[0] >= 1) {
                // 26/07/2010 BBX
                // Tri et d�doublonnage en PHP car certains caract�res sp�ciaux
                // ne passent pas avec la commande "sort"
                // Utilisation de la fonction "sort" PHP et suppression du seconde param�tre
                // de la fonction "array_unique" qui bug en PHP < 5.2.9
                // BZ 14969
                $tmpArcFile = file($this->file_arc_tmp);
                $tmpArcFile = array_unique($tmpArcFile);
                sort($tmpArcFile, SORT_STRING);
                file_put_contents($this->file_new_arc, $tmpArcFile);

                $this->insertTopologyArcs($this->file_new_arc);
            }
        }
        //2/2/2011 MMT :  BZ 19696
        // always log arc changes from file 'file_new_arc'
        $this->addNewArcsChanges();
    }

// End function reparenting

    /**
     * Fonction qui construit les requ�tes � ex�cuter
     *
     * @acces private
     */
    private function insertTopologyArcs($file) {

        $lst_arcs = $this->getListArcs();

        $delete = "	DELETE FROM " . self::$table_arc_ref . " 
					WHERE eoar_arc_type IN ('" . implode("','", $lst_arcs) . "')";

        $insert = "	COPY " . self::$table_arc_ref . "(eoar_id, eoar_id_parent, eoar_arc_type) 
						FROM '$file' 
						WITH DELIMITER '" . self::$delimiter . "' NULL ''
					";

        $this->setQueries($delete);
        $this->setQueries($insert);
    }

// End function insertTopologyArcs
    //2/2/2011 MMT :  BZ 19696
    /**
     * Add changes (screen output) for each new arc found
     * Old Network parent value is always null
     */
    private function addNewArcsChanges() {
        //2014/10/01 - FGD - Bug 44225 - [REC][CB 5.3.2.06][Demon Topo File] The is error message in demon topo file
        //check that the file exists before trying to read it with cat
        if (file_exists($this->file_new_arc)) {
            $newArc = $this->cmd("cat  $this->file_new_arc");
            foreach ($newArc as $oneLine) {
                $line = explode(self::$delimiter, $oneLine);
                /*
                  $line[1] = eoar_id
                  $line[2] = eoar_id_parent
                  $line[3] = eoar_arc_type
                 */
                $na = explode('|s|', $line[2]);

                $this->addReparentingChange(
                        self::$naLabel[$na[0]], $line[0], self::$naLabel[$na[1]], "null", $line[1]
                );
            }
        }
    }

    /**
     * Fonction qui initialise les fichiers temporaires
     *
     * @acces private
     */
    private function initFiles() {

        $file = pathinfo(self::$file);
        $filename = date('Ymd_His');
        // Fichier contenant les arcs du fichier charg� ( ex format :  id,id_parent,arc_type ) 
        $this->file_arc_tmp = self::$rep_niveau0 . 'upload/' . $file['filename'] . '_' . $filename . '_arc_tmp.topo';

        // Fichier contenant les arcs de la table edw_object_arc_ref du m�me type que les na du fichier charg� ( ex format :  id,id_parent,arc_type ) 
        $this->file_arc_ref = self::$rep_niveau0 . 'upload/' . $file['filename'] . '_' . $filename . '_arc_ref.topo';

        // Fichier  contenant les arcs de la table edw_object_arc_ref modifi�s ou non + les nouveaux arcs 
        $this->file_copy_arc_ref = self::$rep_niveau0 . 'upload/' . $file['filename'] . '_' . $filename . '_copy_arc_ref.topo';

        $this->file_new_arc = self::$rep_niveau0 . 'upload/' . $file['filename'] . '_' . $filename . '_arc_new.topo';

        $this->file_arc_chgt = self::$rep_niveau0 . 'upload/' . $file['filename'] . '_' . $filename . '_arc_chgt.topo';


        /*
          // Fichier contenant les donn�es suppl�mentaires des na ( ex : format :  na, na_label obj_type,on_off )
          // $this->file_result_complet_tmp = self::$rep_niveau0 .'upload/'.$file['filename'].'_'.$filename.'_complet_tmp.topo';

          // Fichier modifi� � plusieurs reprises. il contient
          // - 1 : na; obj_type
          // - 2 : na; na_label; obj_type
          // - 3 : na; na_label; obj_type; on_off
          // $this->file_result = self::$rep_niveau0 .'upload/'.$file['filename'].'_'.$filename.'_result.topo';

          // Fichier contenant les coordonn�es g�ographiques des nouveaux na_min (format : na_min, longitude, latitude, azimuth)
          // $this->file_parameters = self::$rep_niveau0 .'upload/'.$file['filename'].'_'.$filename.'_parameters.topo';
         */
    }

    /**
     * Fonction qui identifie les arcs du fichier
     *
     * @acces private
     */
    private function getArc() {

        $this->demon(self::$na, "na");

        // Niveau d'agr�gation le plus petit dans le fichier
        $na_min = $this->getNaMinIntoFile();

        $this->arc = array();

        $na_max = $this->getNaMaxIntoFile();

        $_condition_family = "";
        if ((self::$_family) !== "") {
            $_condition_family = "AND family = '" . self::$_family . "'";
        }
        // maj 03/02/2010 - MPR : Correction du BZ
        // 22/08/2013 GFS - Bug 35295 - [REC][Core PS 5.3.0.04][TC#TA-57368][Topology]: The "sgsn|s|network" relationship should not exist on PDP family after uploading the topology file
        // 29/10/2013 GFS - Bug 37284 - [REC][T&A CB 5.3.0.20][Topology] The topology upload fails 
        $query = "	SELECT distinct level_source, agregation 
					FROM sys_definition_network_agregation 
					WHERE (
						level_source IN ('" . implode("','", self::$na) . "') 
						AND agregation IN ('" . implode("','", self::$na) . "') 
						AND level_source <> agregation
						{$_condition_family}
						)
						" . ($na_max !== null ? " OR agregation = '" . $na_max . "' {$_condition_family}" : '') . "
						;
				";
        $this->demon($query, "get arc");

        $res = $this->sql($query);

        while ($row = pg_fetch_array($res)) {

            $this->arc[$row['level_source']][] = $row['level_source'] . "|s|" . $row['agregation'];
        }
    }

    /**
     * Fonction qui envoie dans le fichier $this->file_new_arc les nouveaux arcs et/ou les arcs modifi�s 
     *
     * @acces private
     */
    private function getNewArc() {

        // 14:03 21/08/2009 GHX
        // $this->cmd("dos2unix ".$this->file_result_ref);
        // $this->cmd("dos2unix ".$this->file_result_tmp);

        $limit = self::$nb_elems_axe3_limited;

        $_condition = "print $0";
        if (self::$axe == 3 && $this->limitMax3rdAxis()) {
            // 20/07/2012 BBX
            // BZ 27718 : correction de la comptabilisation des NA
            $_condition = "# 11:31 06/01/2010 GHX
							# BZ 13251
							# $limit >= -1 au lieu de $limit >= 0
							# 17:52 13/01/2010 MPR
							# BZ 13251
							# modif inverse...
							if( index($3,\"" . self::$naMinIntoFile . "|s|\") > 0 && " . self::$axe . " == 3 && $limit >= 0 ){
                                                            if( $limit == 0 && \"" . self::$mode . "\" == \"manuel\" ){
                                                                    print $0;
                                                            } else {
                                                                nalist[$1]=1;
                                                                cpt=0;
                                                                for (x in nalist){
                                                                    cpt++;
                                                                }
                                                                if( cpt <= $limit ){
                                                                    print $0;
                                                                }                                                                
                                                            }
                                                        } else {
                                                            print $0;
                                                        }";
        }

        $na_parent_not_unique = $this->getNaParentUnique();

        if (count($na_parent_not_unique) > 0) {
            foreach ($na_parent_not_unique as $_na) {
                $_condition_parent_not_unique[] = "index($3,\"" . $_na . "|s|\") > 0";
            }
            $_condition_na_parent = "
				if(" . implode(" && ", $_condition_parent_not_unique) . "){
					print $0;
				}
				else{
					if ( lines[$0]==\"\" ) {
							$_condition
					}
				}
			";
        } else {

            $_condition_na_parent = "
				if ( lines[$0]==\"\" ) {
						$_condition
				}";
        }


        // 15:15 06/01/2010 GHX
        //Initilisation de la variable sinon ERREUR dans le awk (ca na pas �t� test� !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!)
        if (empty($limit))
            $limit = 0;

        // On extrait les nouveaux �l�ments 
        $awk = "awk  ' BEGIN { file1=\"\"; cpt=0; FS=\"" . self::$delimiter . "\" ;OFS=\"" . self::$delimiter . "\"  } { 
					if ( 1==FNR ) {
							if ( file1==\"\" ) {
								file1=FILENAME;
								header=$0;
							}
					}if ( file1==FILENAME ) {
						lines[$0]=1;
					} else {
						{$_condition_na_parent}
					}
				}' " . $this->file_arc_ref . " " . $this->file_arc_tmp . " > " . $this->file_new_arc;

        // On supprime les commentaires
        $awk = preg_replace('/(.*)#.*/', '\1', $awk);
        // Supprime les tabulations et  les retours � la ligne
        $awk = preg_replace('/\s\s+/', ' ', $awk);

        $this->cmd($awk);
        $this->demon($awk, "awk ref/tmp");

        $file_uniq = $this->file_new_arc . "_" . uniqid("") . "_tmp.topo";
        $this->cmd("uniq " . $this->file_new_arc . " > " . $file_uniq);

        $this->cmd("mv $file_uniq " . $this->file_new_arc);
    }

// End function getNewArc

    private function getNaParentUnique() {
        $query = "SELECT agregation FROM sys_definition_network_agregation WHERE na_parent_unique = 0 AND family = '" . self::$_family . "'";

        $res = $this->sql($query);

        while ($row = pg_fetch_array($res)) {

            $na_parent_not_unique[] = $row['agregation'];
        }

        return $na_parent_not_unique;
    }

    private function getAllNaParentNotUnique() {
        $query = "SELECT agregation FROM sys_definition_network_agregation WHERE na_parent_unique = 0";

        $res = $this->sql($query);

        while ($row = pg_fetch_array($res)) {

            $na_parent_not_unique[] = $row['agregation'];
        }

        return $na_parent_not_unique;
    }

    /**
     * Fonction qui g�n�re le fichier qui sera charg� en base
     *
     * @acces private
     */
    private function createFileArcResult() {


        $file = pathinfo(self::$file);
        $filename = date('Ymd_His');
        $file_copy_arc_ref_tmp = self::$rep_niveau0 . 'upload/' . $file['filename'] . '_' . $filename . '_copy_arc_ref_tmp.topo';

        // 26/07/2010 BBX
        // Tri et d�doublonnage en PHP car certains caract�res sp�ciaux
        // ne passent pas avec la commande "sort"
        // Utilisation de la fonction "sort" PHP et suppression du seconde param�tre
        // de la fonction "array_unique" qui bug en PHP < 5.2.9
        // BZ 14969
        $tmpArcFile = file($this->file_new_arc);
        $tmpArcFile = array_unique($tmpArcFile);
        sort($tmpArcFile, SORT_STRING);
        file_put_contents($file_copy_arc_ref_tmp, $tmpArcFile);

        $na_max = $this->getNaMaxIntoFile();
        $this->demon($na_max, '--- NA MAX ----');
        $na_parent_not_unique = $this->getAllNaParentNotUnique();
        $this->demon($na_parent_not_unique, '--- NA PARENT NOT UNIQUE ----');
        $_condition_parent_not_unique[] = "false";
        if ($na_parent_not_unique != null) {
            foreach ($na_parent_not_unique as $_na) {
                $_condition_parent_not_unique[] = "index($3,\"" . $_na . "|s|\") > 0";
            }
        }
        $awk = "awk  ' BEGIN { file1=\"\"; nbChildInRef=0; FS=\"" . self::$delimiter . "\" ;OFS=\"" . self::$delimiter . "\" } {
		
					if ( 1==FNR ) {
						if ( file1==\"\" ) {
							file1=FILENAME;
						}
					}
					if(" . implode(" || ", $_condition_parent_not_unique) . "){
						#if there are multiple parents allowed for this arc, we add the parent to the key
						#it will prevent these items to be removed from the result
						key=$3 SUBSEP $1 SUBSEP $2;
					}else{
						key=$3 SUBSEP $1;
					}
					if ( file1==FILENAME ) { # Traitement sur le premier fichier
						
						 if ( $1 != \"\" ) { tableauChild[$1] = $1 };
						";
        if ($na_max !== null) {

            $awk .= "if( index($3,\"$na_max\") != 0 && $2 != \"\"){
									val=$2;
							 }
						
						";
        }
        $awk .= "tableau[key] = $0;
						if( $2 != \"\" ) {
							tableauParent[key] = $2; # On m�morise la valeur du id_parent pour chaque id_child
						}
					}
					else {  # Traitement sur le deuxieme fichier
						# 17:17 03/12/2008 GHX
						# si le niveau eoar_id est vide on n insere pas l arc
					
						";
        //----------------------------------------------------------------------------------------------------------------------//
        $lst_arcs = $this->getListArcs();

        foreach ($lst_arcs as $arc) {
            $awk .= "if ( $3 == \"$arc\" ) {
										";
            if ($na_max !== null) {
                // 17:12 03/12/2008 GHX
                // Ajout de la condition au lieu de la laiss� dans celle du dessus car si le niveau max n'est pas pr�sent dans le fichier
                // une erreur est produite
                // 14:41 10/12/2008 GHX
                // Ajout du @ pour �viter des erreurs
                if (@substr_count($arc, $na_max) > 0) {
                    // 09:28 01/12/2009 MPR : Correction du bug 12959 - Chargement de plusieurs fichiers - On ne remplace pas la valeur existante par une valeur nulle 
                    $awk .= " if ($2 != \"\" && val != \"\") { $2 = val; }";
                } else {
                    $awk .= "if ( tableauChild[$1] != \"\" ) {
												if ( tableauParent[key] != \"\" ){
													$2 = tableauParent[key];
												}
											}
											";
                }
            } else {
                $awk .= "if ( tableauChild[$1] != \"\" ) {
											if ( tableauParent[key] != \"\" ){
													$2 = tableauParent[key];
											}
										}
										";
            }

            $awk .= "  if ( $1 != \"\" ) { tableau[key] = $0 };
									}";
        }
        //----------------------------------------------------------------------------------------------------------------------//
        $awk .= "}
				}
				END { 
					
					# On boucle sur tous les r�sultats pour les ins�rer dans notre fichier final
					for(field in tableau){
						print tableau[field];
					}
			
                 }' $file_copy_arc_ref_tmp " . $this->file_arc_ref . "  > $this->file_copy_arc_ref";


        $this->demon('<pre>' . $awk . '</pre>', "awk");

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
        $tmpArcFile = file($this->file_copy_arc_ref);
        $tmpArcFile = array_unique($tmpArcFile);
        sort($tmpArcFile, SORT_STRING);
        file_put_contents($this->file_copy_arc_ref, $tmpArcFile);

        // 01/12/2008 GHX
        // R�cup�re les changements de reparenting pour les ajouter au tableau change summary
        $changeArc = $this->cmd("diff " . $this->file_arc_ref . "  " . $this->file_copy_arc_ref, true);

        $tmpBefore = array();
        // 29/11/2010 BBX
        // Utilisation du bon s�parateur dans le pattern
        // BZ 19491
        $archUpdatePattern = '([^' . self::$delimiter . ']*)' . self::$delimiter . '([^' . self::$delimiter . ']*)' . self::$delimiter . '([^' . self::$delimiter . ']*)';
        // R�cup�re les �l�ments avant changement
        foreach ($changeArc as $oneLine) {
            /*
              $line[1] = eoar_id
              $line[2] = eoar_id_parent
              $line[3] = eoar_arc_type
             */

            if (preg_match('/< ' . $archUpdatePattern . '/', $oneLine, $line)) {
                $tmpBefore[$line[3]][$line[1]] = $line[2];
            }
        }
        // R�cup�re les �l�ments apr�s changement
        foreach ($changeArc as $oneLine) {
            if (preg_match('/> ' . $archUpdatePattern . '/', $oneLine, $line)) {
                if ($tmpBefore[$line[3]][$line[1]] == '' || $tmpBefore[$line[3]][$line[1]] == null || $tmpBefore[$line[3]][$line[1]] == $line[2])
                    continue;

                $na = explode('|s|', $line[3]);

                // Changement de parent				
                //2/2/2011 MMT :  BZ 19696   use addReparentingChange function
                // add all the reparenting changes from existing parents

                $this->addReparentingChange(
                        self::$naLabel[$na[0]], $line[1], self::$naLabel[$na[1]], $tmpBefore[$line[3]][$line[1]], $line[2]
                );
            }
        }
    }

// End function createFileArcResult
    //02/02/2011 MMT  BZ 19696
    /**
     * add a screen info change for the given reparenting information
     * if the given $na_value has been reparented already, it will be discarded
     * @param <type> $na_level Network Level
     * @param <type> $na_value Network value
     * @param <type> $parent_na_level Parent Network Level
     * @param <type> $oldValue Parent Network old value
     * @param <type> $newValue Parent Network new value
     */
    private function addReparentingChange($na_level, $na_value, $parent_na_level, $oldValue, $newValue) {

        if (!in_array($na_value, $this->reparentedNesForChanges)) {
            $changeInfo = $na_level . ' <=> ' . $parent_na_level;
            $this->set_changes(array($na_level, $na_value, $changeInfo, $oldValue, $newValue));
            $this->reparentedNesForChanges[] = $na_value;
        }
    }

    /**
     * Fonction qui g�n�re le fichier temporaire $this->file_arc_tmp contenant les arcs du fichier charg�
     *
     * @acces private
     */
    private function createFileArcTmp() {

        $lst_arcs = $this->getListArcs();

        $file = pathinfo(self::$file);
        $filename = date('Ymd_His');
        $file_arc_tmp = self::$rep_niveau0 . 'upload/' . $file['filename'] . '_' . $filename . '_arc_tmp_tmp.topo';


        foreach ($lst_arcs as $arc) {

            $tab_na = explode("|s|", $arc);

            $id_na = $this->getIdField($tab_na[0], self::$header_db);
            $id_na_parent = $this->getIdField($tab_na[1], self::$header_db);

            if ($id_na === null || $id_na_parent === null)
                continue;


            // maj 17/03/2010 - MPR : Correction du BZ 14778  - Remplacement du sed par un awk le caract�re � faisait planter la commande
            $cmd = "cut -d'" . self::$delimiter . "' -f$id_na,$id_na_parent " . self::$file_tmp;
            if ($id_na_parent < $id_na) {
                // 29/11/2010 BBX
                // Utilisation du bon s�parateur
                // BZ 19491
                $cmd .= ' | awk -F "' . self::$delimiter . '" \'{if($1 != ""){print $2"' . self::$delimiter . '"$1}}\'';
            }
            $cmd .= " > " . $file_arc_tmp;

            $this->cmd($cmd);

            // On ajoute la colonne arc_type
            $cmd = "awk '{ print $0\"" . self::$delimiter . $tab_na[0] . "|s|" . $tab_na[1] . "\"' } $file_arc_tmp >> $this->file_arc_tmp";

            // On ajoute le arc_type en v�rifiant que le na_parent est <> de vide
            $awk = "awk 'BEGIN { FS=\"" . self::$delimiter . "\"; OFS=\"" . self::$delimiter . "\"}{
						if($1!=\"\" && $2!=\"\"){
							print $0\"" . self::$delimiter . $tab_na[0] . "|s|" . $tab_na[1] . "\";
						}
					}' 	$file_arc_tmp >> " . $this->file_arc_tmp;

            // On supprime les commentaires
            $awk = preg_replace('/(.*)#.*/', '\1', $awk);
            // Supprime les tabulations et  les retours � la ligne
            $awk = preg_replace('/\s\s+/', ' ', $awk);

            $this->cmd($awk);

            // 23/06/2010 BBX
            // Tri et d�doublonnage en PHP car certains caract�res sp�ciaux
            // ne passent pas avec la commande "sort"
            // 26/07/2010 BBX
            // Utilisation de la fonction "sort" PHP et suppression du seconde param�tre
            // de la fonction "array_unique" qui bug en PHP < 5.2.9
            // BZ 14969
            $tmpArcFile = file($this->file_arc_tmp);
            $tmpArcFile = array_unique($tmpArcFile);
            sort($tmpArcFile, SORT_STRING);
            file_put_contents($this->file_arc_tmp, $tmpArcFile);
        }
    }

// End function createFileArcTmp

    /**
     * Fonction qui retourne la liste des arcs du fichier charg�
     *
     * @acces private
     * @return array $lst_arcs : liste des arcs du fichier charg�
     */
    private function getListArcs() {

        $lst_arcs = array();
        if (count($this->arc) > 0) {

            foreach ($this->arc as $id_child => $tab_arc) {

                foreach ($tab_arc as $arc) {

                    $lst_arcs[] = $arc;
                }
            }
        }

        return $lst_arcs;
    }

// End function getListArcs

    /**
     * Fonction qui g�n�re le fichie contenant les arcs de la table edw_object_arc_ref
     *
     * @acces private
     */
    private function createFileArcRef() {


        $cmd = "touch $this->file_arc_ref";
        $this->cmd($cmd);

        $cmd = "chmod 777 $this->file_arc_ref";
        $this->cmd($cmd);

        // Dans le cas, o� la limite du nombre d'�l�ments 3�me axe est atteinte et qu'on est en mode manuel, on r��crase la topologie
        if (!( self::$nb_elems_axe3_limited == 0 && self::$axe == 3 && $this->limitMax3rdAxis() && self::$mode == "manuel" )) {

            $arcs = $this->getListArcs();

            $query = "	COPY (
							SELECT eoar_id,eoar_id_parent,eoar_arc_type 
							FROM " . self::$table_arc_ref . " 
							WHERE eoar_arc_type IN ('" . implode("','", $arcs) . "')
							ORDER BY eoar_arc_type, eoar_id
							) 
						TO '$this->file_arc_ref' 
						WITH DELIMITER '" . self::$delimiter . "' NULL ''";


            $this->demon($query, "get arc ref");

            $this->sql($query);
        }
    }

// End function createFileArcRef

    /**
     * Destructor
     *
     * @acces public
     */
    function __destruct() {
        
    }

// End function __destruct()
}

?>