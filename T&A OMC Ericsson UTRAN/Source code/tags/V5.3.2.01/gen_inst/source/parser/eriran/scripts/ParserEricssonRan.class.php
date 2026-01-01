<?php

class ParserEricssonRan extends Parser {

    const PARSER_FILE_NAME="XML";

    public function __construct(DataBaseConnection $dbConnection,FileTypeCondition $fileType = null,$single_process_mode=TRUE,$topoFileId = null) {

        $conf = new Configuration();
        $this->params = $conf->getParametersList();
        $dBServices=new DatabaseServicesEriRan($dbConnection);
        parent::__construct($dBServices,$this->params,self::PARSER_FILE_NAME,$fileType,$single_process_mode);
        // Cf. http://fr.php.net/xmlreader
        $this->specif_enable_iurlink = get_sys_global_parameters('specif_enable_iurlink');
        $this->specif_enable_iublink = get_sys_global_parameters('specif_enable_iublink');
        $this->specif_enable_adjacencies = get_sys_global_parameters('specif_enable_adjacencies');
        $this->specif_enable_lac = get_sys_global_parameters('specif_enable_lac');
        $this->specif_enable_rac = get_sys_global_parameters('specif_enable_rac');
        $this->specif_enable_nodeb = get_sys_global_parameters('specif_enable_nodeb');
        $this->topoFileId = $topoFileId;
        $this->xmlReader = new XMLReader();
    }


    /**
     * Fonction qui parse le fichier et qui va integrer dans un fichier au format csv les données issues du fichier source
     *
     * @param int $id_fichier numero du fichier à traiter
     * @global text repertoire physique d'installation de l'application
     */
    public function createCopyBody(FlatFile $flat_file, $topologyHour='ALL') {
        $hour=$flat_file->hour; 
        $this->topologyHour=$topologyHour;
        $this->currentHour=$hour;
        $day = substr($hour, 0, 8); 
        $week = Date::getWeek($day);
        $month = substr($hour, 0, 6);
        $this->time_data = $hour.';'.$day.';'.$week.';'.$month.';'.$flat_file->capture_duration.';'.Parser::$capture_duration_expected.';'.$flat_file->capture_duration;
        $pattern="/A([0-9]{4})([0-9]{2})([0-9]{2})\\.([0-9]{2})([0-9]{2})([+,-][0-9]{2})[0-9]{2}\\-([0-9]{2})([0-9]{2})([+,-][0-9]{2})[0-9]{2}_SubNetwork=(.*),SubNetwork=(.*),MeContext=(.*)_statsfile/";
        $this->min="00";
        $this->fileType=$flat_file->flat_file_name;
        if( preg_match($pattern, $flat_file->uploaded_flat_file_name, $regs)){
            $this->min=$regs[5];

            // cas d'un fichier nodeB
            //A<YYYYMMDD>.<hhmm+0000>-<hhmm+0000>_SubNetwork=<xxx>,SubNetwork=<RNC>,MeContext=<Node B>_statsfile.xml
            if($regs[11]!=$regs[12]){
                if($this->specif_enable_nodeb == 0){
                    return;
                }
            }
        }
        $this->currentSubNetwork=$regs[11];
        if (file_exists($flat_file->flat_file_location)) {
            $this->parseFichierParameters($flat_file->flat_file_location,$hour);
        }
        else displayInDemon(__METHOD__ . " ERROR : fichier {$flat_file->flat_file_location} non présent", "alert");

        if (Tools::$debug) {Tools::traceMemoryUsage();}
    }


    //parsing fichier XML
    private function parseFichierParameters($fichierIn,$hour)
    {	

        $this->xmlReader->open($fichierIn,null, 1<<19);
        $listPostionMvcCounters = array();
        $mvcCounters = $this->getMvcCounters();

        // pour avoir un aperçu du n° de tour dans la boucle while
        $i=0;
        // déplace le curseur sur le prochain noeud du document XML
        while($this->xmlReader->read())
        {	
            if ($this->xmlReader->name == "mi")
            {
                if($this->xmlReader->nodeType == XMLReader::ELEMENT)
                {
                    $counterList=array();
                    $currentCptValueList=array();
                    $firstMvBlock=true;
                    $firstMoidBlock=true;
                }
                else if ($this->xmlReader->nodeType == XMLReader::END_ELEMENT)
                {

                }
            }
            elseif($this->xmlReader->name == "mt")
            {
                if($this->xmlReader->nodeType == XMLReader::ELEMENT)
                {
                    // on lit le nom de compteur (prochain noeud) dans cette balise
                    $this->xmlReader->read();

                    $counterList[]=	$this->xmlReader->value;
                    //On vérifie que le compteur récupéré dans le fichier source n'est pas un MultiValued counter
                    $position = count($counterList);
                    foreach ($mvcCounters[0] as $j => $mvcCounterExp){
                        if(strtolower($this->xmlReader->value) == $mvcCounterExp){
                            //On récupère la position du compteur
                            if(!in_array($position, $listPostionMvcCounters)){
                                array_push($listPostionMvcCounters,$position); 
                            }

                        }
                    }
                    foreach ($mvcCounters[1] as $i => $mvcCounterColl){
                        if(strtolower($this->xmlReader->value) == $mvcCounterColl){
                            //On récupère la position du compteur
                            if(!in_array($position, $listPostionMvcCounters)){
                                array_push($listPostionMvcCounters,$position); 
                            }

                        }
                    }	
                }
            }
            else if($this->xmlReader->name == "neun")
            {
                if($this->xmlReader->nodeType == XMLReader::ELEMENT)
                {
                    // on lit la valeur (prochain noeud) dans cette balise
                    $this->xmlReader->read();
                    $this->currentRncCode=$this->xmlReader->value;
                }
            }
            elseif($this->xmlReader->name == "moid")
            {
                if($this->xmlReader->nodeType == XMLReader::ELEMENT)
                {
                    // on lit la valeur (prochain noeud) dans cette balise
                    $this->xmlReader->read();
                    $moid = $this->xmlReader->value;
                    $tabTopo=$this->get_ne_info($moid);
                    if ($this->fileType=="RNC")
                        $tabTopoRncFamily=$this->get_ne_info_for_RNC_family($moid);

                    if($tabTopo==null){
                        //on saute jusqu'au prochain <md>
                        while($this->xmlReader->read()){
                            if (($this->xmlReader->name == "md")&&($this->xmlReader->nodeType == XMLReader::END_ELEMENT)) break;
                        }
                    }
                    //si aucun compteur n'est connu on saute
                    elseif(count($counters_list_per_todo)==0){
                        //on saute jusqu'au prochain <md>
                        while($this->xmlReader->read()){
                            if (($this->xmlReader->name == "md")&&($this->xmlReader->nodeType == XMLReader::END_ELEMENT)) break;
                        }
                    }

                    if(($firstMoidBlock)&&($tabTopo!=null)){
                        //automatic mapping
                        $family=$tabTopo["family"];
                        $id_group_table=$this->getIdGroupTable($family);
                        //BZ 30086
                        //$this->addToCountersList($counterList, "{$family}_unknown", $id_group_table);
                        $this->addToCountersList($counterList, "unknown", $id_group_table);
                        if($this->fileType=="RNC"){
                            $id_group_table=$this->getIdGroupTable("rnc");
                            $this->addToCountersList($counterList, "unknown", $id_group_table);
                        }
                    
                        $firstMoidBlock=false;
                    }



                }
            }else if($this->xmlReader->name == "mv")
            {
                if($this->xmlReader->nodeType == XMLReader::ELEMENT)
                {
                    if($firstMvBlock){
                        $counters_list_per_todo = array();
                        $counters_list_per_todo = $this->getCptsInFile($counterList);
                        $firstMvBlock=false;


                    }

                }
                elseif ($this->xmlReader->nodeType == XMLReader::END_ELEMENT)
                {
                    $values=array();
                    // On charge les données du fichiers sources dans le tableau $values

                    for ($i = 0; $i <  count($counterList); $i++) {
                        $values[strtolower($counterList[$i])] = $currentCptValueList[$i];
                    }

                    $this->CounterValuesListPerBlock[]=array($tabTopo,$values);
                    if ($this->fileType=="RNC")
                        $this->CounterValuesListPerBlock[]=array($tabTopoRncFamily,$values);

                    //remis à zero
                    $currentCptValueList=array();
                }
            }

            elseif ($this->xmlReader->name == "md")
            {
                if($this->xmlReader->nodeType == XMLReader::ELEMENT)
                {
                    $this->CounterValuesListPerBlock=array();
                    //construction de l'objet
                    //$this->mdBlock = new MdBlock();
                }
                // on ecrit l'ancien objet dans le fichier de sortie puis on créé le nouveau
                else if ($this->xmlReader->nodeType == XMLReader::END_ELEMENT)
                {
                    if(count($counters_list_per_todo)!=0)
                        foreach ($counters_list_per_todo as $todo => $counters_list) {
                            //quelle famille pour ce todo?
                            if(preg_match("/^([^_]*)_.*/", $todo,$match)){
                                $family=$match[1];
                            }
                            $param = $this->params->getParamWithTodo($todo);
                            $id_group_table=$param->id_group_table;
                            foreach ($this->CounterValuesListPerBlock as $valueTab) {
                                $tabTopo=$valueTab[0];
                                $values=$valueTab[1];

                                //il existe,par exemple, des compteurs qui sont et dans la famille Iur et dans cellb
                                //il ne faut pas intégrer tous les todos; seuls les compteurs adj peuvent aussi etre intégré en cellb
                                //on saute les todos à ne pas intégrer
                                if(!((($tabTopo["family"]=="adj")&&($family=="cellb"))||($tabTopo["family"]==$family)))
                                    continue;

                                //les compteur cellb du todo cellb_soft_handover sont souvent définis 2 fois dans le fichier source: on ne les intégre qu'en cellb
                                //bug 
                                if(($todo=="cellb_soft_handover")&&($tabTopo["family"]=="adj")) continue;


                                $networkElement = $tabTopo[$family]["base_ne"];
                                $topoInfo=$tabTopo["topoInfo"];
                                //if((($tabTopo["topoInfo"])!=null)&&($family==$tabTopo["family"])){
                                if((($tabTopo["topoInfo"])!=null)&&($tabTopo["family"]!="adj")){
                                    if($this->topologyHour=='ALL' || $this->currentHour==$this->topologyHour)
                                        $param->addTopologyInfo($networkElement, $tabTopo["topoInfo"],$this->currentHour,"rawCase");
                                }

                                //$networkElement = strtoupper($networkElement);
                                //virtual_ en minuscule
                                if(preg_match("/virtual_(.*)/i", $networkElement,$matches)){
                                    $networkElement="virtual_{$matches[1]}";
                                }
                                // ### Construction des lignes à ajouter dans le fichier SQL temporaire
                                // Ajoute les premiers éléments de la ligne dans la chaine de caractères $csv_sql
                                $csv_sql .= "{$this->min};{$networkElement};{$this->time_data}";

                                //$param=$this->params->getParamWithTodo($parser_todo);
                                $counters_list=$param->todo[$todo];
                                foreach ($counters_list as $counter) {								
                                    //cas des valeurs explicite
                                    if(in_array(strtolower($counter->nms_field_name[0]),$mvcCounters[0])){
                                        //Cas d'un compteur global explicite
                                        if($counter->flat_file_position == -1){
                                            $csv_sql .= ';' . $this->getCounterValue($counter,$values,true,true);
                                        }
                                        //Sinon on a à faire à une déclinaison du compteur avec des valeurs explicites
                                        else if ($counter->flat_file_position >= 0){
                                            $csv_sql .= ';' . $this->getCounterValue($counter,$values,false,true);
                                        }
                                    }
                                    //cas des valeurs collapsed
                                    else if (in_array(strtolower($counter->nms_field_name[0]),$mvcCounters[1])){
                                        //Cas d'un compteur global collapsed
                                        if($counter->flat_file_position == -2){
                                            $csv_sql .= ';' . $this->getCounterValue($counter,$values,true,false);
                                        }
                                        //Sinon on a à faire à une déclinaison du compteur avec des valeurs condensées
                                        else if ($counter->flat_file_position >= 0){
                                            $csv_sql .= ';' . $this->getCounterValue($counter,$values,false,false);
                                        }
                                    }else{
                                        $csv_sql .= ';' . $this->getCounterValue($counter,$values,false,false);
                                    }


                                }
                                // avec cette boucle, si on a plusieurs edw pour un nms (avec des aggregs diff par ex, pour erlang), ça fonctionne.
                                $csv_sql .= "\n";
                                // ### Fin de la construction de la ligne

                            }
                            $parser_fileSqlName = Tools::getCopyFilePath($param->network[0], $todo,$hour);
                            //sauvegarde dans le fichier SQL
                            $this->fileSauvSql($parser_fileSqlName, $csv_sql);
                            unset($csv_sql);
                            }


                        }
                }
                else if($this->xmlReader->name == "r")
                {
                    if($this->xmlReader->nodeType == XMLReader::ELEMENT)
                    {
                        // on lit la valeur (prochain noeud) dans cette balise 
                        $this->xmlReader->read();

                        $positonValue = count($currentCptValueList)+1;
                        // attention, certaines valeurs prennent la forme d'une liste de sous-valeurs
                        // exemple : <r>1476,1400,1429</r>
                        $virgulePosition = strpos($this->xmlReader->value, ",");
                        // si absence de virgule (valeur)
                        if ($virgulePosition === false) {
                            $currentCptValue = $this->xmlReader->value;
                            $currentCptValue = str_replace(array("\r\n", "\n", "\r"), array("", "", ""), $currentCptValue);
                        }
                        // sinon, il s'agit d'une liste de sous-valeurs qu'il faut sommer
                        else {
                            if(in_array(strval($positonValue), $listPostionMvcCounters)){
                                $currentCptValue = $this->xmlReader->value;
                                $currentCptValue = str_replace(array("\r\n", "\n", "\r"), array("", "", ""), $currentCptValue);
                            }else{
                                $subValuesArray = explode(",", $this->xmlReader->value);
                                $currentCptValue = array_sum($subValuesArray);	
                            }
                            // les balises <r/> ou <r></r> peuvent être interprétées comme un saut de ligne
                        }
                        $currentCptValueList[]=$currentCptValue;
                    }
                }

                else if($this->xmlReader->name == "nedn")
                {
                    if($this->xmlReader->nodeType == XMLReader::ELEMENT)
                    {
                        // on lit la valeur (prochain noeud) dans cette balise
                        $this->xmlReader->read();
                        $nedn=$this->xmlReader->value;


                        //cas des fichiers W10
                        if($this->currentRncCode=="")
                            //if(preg_match("/MeContext=([^,]*)/i", $nedn,$matches)){

                            if(preg_match("/SubNetwork=([^,]*),MeContext=([^,]*)/i", $nedn,$matches)){
                                $this->currentSubNetwork=$matches[1];
                                $this->currentRncCode=$matches[2];
                            }


                        //label rnc

                    }
                    }
                    else
                    {
                        //echo "cas de balise inconnue : $this->xmlReader->name <br>";
                    }
                    $i++;
                }		
                $this->xmlReader->close();
                return true;

            }



            /**
             * Fonction qui va sauvegarder les données $csv_sql dans un fichiers $filename
             *
             * @param string nom du fichier à utiliser pour la sauvegarde
             * @param string texte à insérer dans le fichier $filename
             * @param string $copy_header requete SQL qui sera executee, uniquement utile pour affichage debug
             */
            private function fileSauvSql($filename, $csv_sql) {
                //on ouvre le fichier en append
                if (!$handle = fopen($filename, 'at')) {
                    displayInDemon(__METHOD__ . " ERROR : impossible d'ouvrir le fichier (".$filename.")", "alert");
                }
                else {
                    //on écrit les données
                    flock($handle, LOCK_EX);
                    if (fwrite($handle, $csv_sql) === FALSE)
                        displayInDemon(__METHOD__ . " ERROR : impossible d'écrire dans le fichier (".$filename.")<br>\n", "alert");
                    flock($handle, LOCK_UN);
                    fclose($handle);
                }
            }

            public function get_ne_info_for_RNC_family($moid) {
                $family="rnc";
                $array=explode(",",$moid);
                $topoInfo["RNC"]=$this->currentRncCode;
                if (count($array)==2){
                    $topoInfo["RNCSE"]="virtual_".$this->currentRncCode;
                    $topoInfo["RNCSSE"]="virtual_".$this->currentRncCode;
                }
                else if (count($array)==3){
                    $topoInfo["RNCSE"]=$this->currentRncCode."_".str_replace("=","_",$array[2]);
                    $topoInfo["RNCSSE"]="virtual_".$this->currentRncCode;
                }
                else{
                    $topoInfo["RNCSE"]=$this->currentRncCode."_".str_replace("=","_",$array[2]);
                    $topoInfo["RNCSSE"]=$topoInfo["RNCSE"]."_".str_replace("=","_",$array[3]);
                }
                $topoTab["family"]="rnc";
                $topoTab["topoInfo"]=$topoInfo;
                $topoTab["rnc"]["base_ne"]=$topoInfo["RNCSSE"];
                return $topoTab;
            }
			
            public function get_ne_info($moid) {


                $adjPattern = "/ManagedElement=[0-9]*,RncFunction=[0-9]*,UtranCell=([a-zA-Z0-9_-]*)"
                    ."("
                    .",GsmRelation="
                    ."("
                    ."[a-zA-Z0-9-]+_([a-zA-Z0-9-]+)"
                    ."|([a-zA-Z0-9-]+)"
                    .")"
                    ."|,UtranRelation="
                    ."("
                    ."[a-zA-Z0-9-]+_([a-zA-Z0-9-]+)"
                    ."|([a-zA-Z0-9-]+)"
                    .")"
                    .")(,[^;]+)*/";
                $cellbPattern = "/ManagedElement=[0-9]*,RncFunction=[0-9]*,UtranCell=([a-zA-Z0-9_-]*)(,[^;]+)*/";
                $iublPattern = "/ManagedElement=[0-9]*,RncFunction=[0-9]*,IubLink=([a-zA-Z0-9_-]*)(,[^;]+)*/";
                $iurlPattern = "/ManagedElement=[0-9]*,RncFunction=[0-9]*,IurLink=([a-zA-Z0-9_-]*)(,[^;]+)*/";
                $racPattern  = "/ManagedElement=[0-9]*,RncFunction=[0-9]*,LocationArea=[a-zA-Z0-9_-]*,RoutingArea=([a-zA-Z0-9_-]*)(,[^;]+)*/";
                $lacPattern  = "/ManagedElement=[0-9]*,RncFunction=[0-9]*,LocationArea=([a-zA-Z0-9_-]*)(,[^;]+)*/";
                $rncPattern  = "/ManagedElement=[^;]*/";
                $nodebPattern = "/ManagedElement=[0-9]*,([a-zA-Z0-9-_=]*),*([a-zA-Z0-9-_=]*),*([a-zA-Z0-9-_=]*),*([a-zA-Z0-9-_=]*)/";
                $topoTab=array();
                if($this->currentRncCode == $this->currentSubNetwork){
                    if(preg_match($adjPattern, $moid, $matches)) {
                        if($this->specif_enable_adjacencies == 1){
                            $family = "adj";
                            $stc = $this->currentRncCode."_".$matches[1]."_".$matches[4].$matches[5].$matches[7].$matches[8];
                            $sourcecell = $this->currentRncCode."_".$matches[1];

                            //topoInfo
                            $topoInfo["Source Cell"]=$sourcecell;
                            $topoInfo["Source Target Cell"]=$stc;

                            $topoTab["family"]="adj";
                            $topoTab["topoInfo"]=$topoInfo;
                            $topoTab[$family]["base_ne"]="$stc;$sourcecell";
                            $topoTab["cellb"]["base_ne"]=$sourcecell;
                        }

                    }
                    else if(preg_match($cellbPattern, $moid, $matches)) {
                        $family = "cellb";
                        $cell = $this->currentRncCode."_".$matches[1];


                        //topoInfo
                        $topoInfo["Cell"]=$cell;
                        $topoInfo["RNC"]=$this->currentRncCode;
                        $topoInfo["RNC label"]=$this->currentRncCode;
                        //
                        $topoTab["family"]="cellb";
                        $topoTab["topoInfo"]=$topoInfo;
                        $topoTab[$family]["base_ne"]=$cell;
                    }
                    else if(preg_match($iublPattern, $moid, $matches)) {
                        if($this->specif_enable_iublink == 1){
                            $family = "iubl";
                            $iubl = $this->currentRncCode."_".$matches[1];

                            //topoInfo
                            $topoInfo["IUB_LINK"]=$iubl;
                            $topoInfo["RNC"]=$this->currentRncCode;			

                            $topoTab["family"]="iubl";
                            $topoTab["topoInfo"]=$topoInfo;
                            $topoTab[$family]["base_ne"]=$iubl;
                        }

                    }
                    else if(preg_match($iurlPattern, $moid, $matches)) {
                        if($this->specif_enable_iurlink == 1){
                            $family = "iurl";
                            $iurl = $this->currentRncCode."_".$matches[1];

                            //topoInfo
                            $topoInfo["IUR_LINK"]=$iurl;
                            $topoInfo["RNC"]=$this->currentRncCode;	

                            $topoTab["family"]="iurl";
                            $topoTab["topoInfo"]=$topoInfo;
                            $topoTab[$family]["base_ne"]=$iurl;
                        }
                    }
                    else if(preg_match($racPattern, $moid, $matches)) {
                        if($this->specif_enable_rac == 1){
                            $family = "rac";
                            $rac = $this->currentRncCode."_".$matches[1];

                            //topoInfo
                            $topoInfo["RAC"]=$rac;
                            $topoInfo["RNC"]=$this->currentRncCode;	

                            $topoTab["family"]="rac";
                            $topoTab["topoInfo"]=$topoInfo;
                            $topoTab[$family]["base_ne"]=$rac;
                        }
                    }
                    else if(preg_match($lacPattern, $moid, $matches)) {
                        if($this->specif_enable_lac == 1){
                            $family = "lac";
                            $lac = $this->currentRncCode."_".$matches[1];

                            //topoInfo
                            $topoInfo["LAC"]=$lac;
                            $topoInfo["RNC"]=$this->currentRncCode;


                            $topoTab["family"]="lac";
                            $topoTab["topoInfo"]=$topoInfo;
                            $topoTab[$family]["base_ne"]=$lac;
                        }
                    }
                    else if(preg_match($rncPattern, $moid, $matches)) {
                        // famille RNC supprimée et reversée vers famille Cell Based
                        // (ex RNC devenus des cellules virtuelles = cellules fantômes)
                        // $family = "rnc";
                        $family = "cellb";
                        //$cell = "CELL_RNC_".$this->currentRncCode."_".$this->currentRncCode;
                        $cell = "virtual_".$this->currentRncCode;

                        //topoInfo
                        $topoInfo["Cell"]=$cell;
                        $topoInfo["RNC"]=$this->currentRncCode;
                        $topoInfo["RNC label"]=$this->currentRncCode;
                        //
                        $topoTab["family"]="cellb";
                        $topoTab["topoInfo"]=$topoInfo;
                        $topoTab[$family]["base_ne"]=$cell;
                    }
                }else{
                    if(preg_match($nodebPattern, $moid, $matches)) {
                        if($this->specif_enable_nodeb == 1){
                            $family = "nodeb";
                            $nodeb = $this->currentSubNetwork.'_'.$this->currentRncCode;


                            if($matches[2] != ''){
                                $clearSE = str_replace("=", "_", $matches[2]);
                                $se = $nodeb."_".$clearSE;
                            }else{
                                $se = "virtual_".$nodeb;
                            }

                            if($matches[3] != ''){
                                $clearSSE = str_replace("=", "_", $matches[3]);
                                $sse = $se."_".$clearSSE;
                            }else{
                                $sse = "virtual_".$se;
                            }

                            //topoInfo
                            $topoInfo["SSE"]=$sse;
                            $topoInfo["SE"]=$se;
                            $topoInfo["NodeB"]=$nodeb;
                            $topoInfo["RNC"]=$this->currentSubNetwork;
                            $topoInfo["RNC label"]=$this->currentSubNetwork;			
                            $topoTab["family"]="nodeb";
                            $topoTab["topoInfo"]=$topoInfo;
                            $topoTab[$family]["base_ne"]=$sse;
                        }

                    }
                }
                return $topoTab;
            }

            function getIdGroupTable($family) {
                switch ($family) {
                    case "cellb":
                        $id_group_table=1;
                    break;
                    case "lac":
                        $id_group_table=5;
                    break;
                    case "iurl":
                        $id_group_table=3;
                    break;
                    case "rac":
                        $id_group_table=6;
                    break;

                    case "iubl":
                        $id_group_table=2;
                    break;
                    case "adj":
                        $id_group_table=4;
                    break;
                    case "nodeb":
                        $id_group_table=7;
                    break;
                    case "rnc":
                        $id_group_table=8;
                    break;
                    default:
                    $id_group_table=1;
                    break;
                }
                return $id_group_table;

            }


            /**
             *
             * Enter description here ...
             */
            public static function  getConditionProvider($dbServices){
                return new XMLConditionProvider($dbServices);
            }

            /**
             *
             * Récupère la liste des MultiValued Counter
             * @return tableau des nms_field_name lié à un MultiValued counter
             */
            public function  getMvcCounters(){
                $counters = $this->dbServices->getAllCounters($this->params);
                $expliciteCounters= array();
                $collapasedCounters= array();
                foreach($counters as $counter) {
                    // on récupère la liste des compteurs cumulés à partir de leur flat file position:
                    //-2 compteur cumulé condensé
                    //-1 compteur cumulé explicite
                    // >=0 déclinaisont de compteur cumulé
                    if( $counter->flat_file_position != null && $counter->flat_file_position < 0){
                        if($counter->flat_file_position == -1){
                            if(!in_array($counter->nms_field_name[0],$expliciteCounters)){
                                array_push($expliciteCounters, strtolower($counter->nms_field_name[0]));	
                            }
                        }else if ($counter->flat_file_position == -2){
                            if(!in_array($counter->nms_field_name[0],$collapasedCounters)){
                                array_push($collapasedCounters, strtolower($counter->nms_field_name[0]));	
                            }
                        }	
                    }	
                }
                return array($expliciteCounters,$collapasedCounters);
            }

        }
        ?>
