<?php

class ParserHuaBssXml extends Parser
{
    const PARSER_FILE_NAME = "XML";
    private $params;
    private $xmlReader;
    private $bscList;
    private $unknownNE;
    private $listFamilies;
    private $specif_enable_trx;
    private $entities;
    private $min;

    public function __construct(DataBaseConnection $dbConnection, FileTypeCondition $fileType = null, $single_process_mode = true)
    {
        $conf = new Configuration();
        $this->params = $conf->getParametersList();
        $dBServices = new DatabaseServicesHuaBss($dbConnection);
        parent::__construct($dBServices, $this->params, self::PARSER_FILE_NAME, $fileType, $single_process_mode);
        $this->xmlReader = new XMLReader();
        $this->bscList = [];
        $this->unknownNE = [];
        $this->listFamilies = ["bss", "bssgprs", "bsstrx"];
        $this->specif_enable_trx = get_sys_global_parameters('specif_enable_trx');
        $this->entities = $dBServices->getFamilyByEntity();
    }

    public function createCopyBody(FlatFile $flat_file, $topologyHour = 'ALL')
    {
        $hour = $flat_file->hour;
        $this->topologyHour = $topologyHour;
        $this->currentHour = $hour;
        $day = substr($hour, 0, 8);
        $week = Date::getWeek($day);
        $month = substr($hour, 0, 6);
        $this->time_data = $hour . ';' . $day . ';' . $week . ';' . $month . ';' . $flat_file->capture_duration . ';' . Parser::$capture_duration_expected . ';' . $flat_file->capture_duration;

        $pattern = "/A([0-9]{4})([0-9]{2})([0-9]{2})\.([0-9]{2})([0-9]{2})[+-]{1}.*_(.*)\.xml/i";
        $this->min = "00";
        if (preg_match($pattern, $flat_file->uploaded_flat_file_name, $regs)) {
            $this->min = $regs[5];
        }

        $filePath = $flat_file->flat_file_location;
        if (file_exists($filePath)) {
            $this->parseFichierParameters($filePath, $hour);
        } else {
            displayInDemon(__METHOD__ . " ERROR : fichier {$filePath} non présent", "alert");
        }

        if (Tools::$debug) {
            Tools::traceMemoryUsage();
        }
    }

    private function parseFichierParameters($filePath, $hour)
    {
        $this->xmlReader->open($filePath, null, 1 << 19);
        $process = 0;
        $counterPos = 0;
        $measObjLdnList = [];

        while ($this->xmlReader->read()) {
            if ($this->xmlReader->name == "measInfo") {
                if ($this->xmlReader->nodeType == XMLReader::ELEMENT) {
                    $object_type = "";
                    $counterList = [];
                    $this->CounterValuesListPerBlock = [];
                    $process = 0;
                    $counterPos = 0;
                    $nms_table = trim($this->xmlReader->getAttribute('measInfoId'));
                    if ($key = array_search($nms_table, $this->entities[0])) {
                        $family = $this->entities[1][$key];

                        if ($family == "bsstrx" && $this->specif_enable_trx == "0") {
                            while ($this->xmlReader->read()) {
                                if (($this->xmlReader->name == "measInfo") && ($this->xmlReader->nodeType == XMLReader::END_ELEMENT)) break;
                            }
                        }
                    } else {
                        while ($this->xmlReader->read()) {
                            if (($this->xmlReader->name == "measInfo") && ($this->xmlReader->nodeType == XMLReader::END_ELEMENT)) break;
                        }
                    }
                } else if ($this->xmlReader->nodeType == XMLReader::END_ELEMENT) {
                    if (count($counters_list_per_todo) != 0) {
                        $this->addToCountersList($counterList, $nms_table, $id_group_table, "c_" . $nms_table);
                        foreach ($counters_list_per_todo as $todo => $counters_list) {
                            $family = substr($todo, 0, strpos($todo, "_"));
                            $param = $this->params->getParamWithTodo($todo);
                            $id_group_table = $param->id_group_table;

                            foreach ($this->CounterValuesListPerBlock as $valueTab) {
                                $tabTopo = $valueTab[0];
                                $values = $valueTab[1];

                                $networkElement = $tabTopo[$family]["base_ne"];
                                $topoInfo = $tabTopo["topoInfo"];

                                if (($tabTopo["topoInfo"]) != null) {
                                    if ($this->topologyHour == 'ALL' || $this->currentHour == $this->topologyHour)
                                        $param->addTopologyInfo($networkElement, $tabTopo["topoInfo"], $this->currentHour, "rawCase");
                                }

                                $csv_sql .= "{$this->min};{$networkElement};{$this->time_data}";

                                foreach ($counters_list as $counter) {
                                    $csv_sql .= ';' . $this->getCounterValue($counter, $values, false, false);
                                }

                                $csv_sql .= "\n";
                            }

                            $parser_fileSqlName = Tools::getCopyFilePath($param->network[0], $todo, $hour);
                            $this->fileSauvSql($parser_fileSqlName, $csv_sql);
                            unset($csv_sql);
                        }
                        $measObjLdnList = [];
                    } else {
                        $this->addToCountersList($counterList, $nms_table, $id_group_table, "c_" . $nms_table);
                    }
                }
            } elseif ($this->xmlReader->name == "measTypes") {
                if ($this->xmlReader->nodeType == XMLReader::ELEMENT) {
                    $this->xmlReader->read();
                    $counters = $this->xmlReader->value;
                    $new_counters = rtrim($counters);
                    $array_counters = explode(" ", $new_counters);
                    foreach ($array_counters as $i => $counter) {
                        $counterList[] = $counter;
                    }
                }
            } elseif ((($this->xmlReader->name == "measResults")) && $this->xmlReader->nodeType == XMLReader::ELEMENT) {
                $this->xmlReader->read();
                $countersValue = rtrim($this->xmlReader->value);
                $currentCptValueList = explode(" ", $countersValue);
            } elseif ($this->xmlReader->name == "measValue") {
                if ($this->xmlReader->nodeType == XMLReader::ELEMENT) {
                    $measObjLdn = NULL;
                    $measObjLdn = $this->xmlReader->getAttribute('measObjLdn');
                    array_push($measObjLdnList, $measObjLdn);
                    $tabTopo = $this->get_ne_info($measObjLdn, $family);
                    
                    if ($tabTopo != null) {
                        $family = $tabTopo["family"];
                        $id_group_table = array_keys($this->listFamilies, $family);
                        $id_group_table = $id_group_table[0] + 1;
                        $counters_list_per_todo = $this->getCptsByNmsTable($nms_table);

                        if (count($counters_list_per_todo) != 0) {
                            $process = 1;
                        }
                    } else {
                        while ($this->xmlReader->read()) {
                            if (($this->xmlReader->name == "measValue") && ($this->xmlReader->nodeType == XMLReader::END_ELEMENT)) break;
                        }
                    }
                    
                    $countersValue = [];
                } elseif ($this->xmlReader->nodeType == XMLReader::END_ELEMENT) {
                    if ($process == 1) {
                        $countersValue = [];
                        $values = [];

                        for ($i = 0; $i < count($counterList); $i++) {
                            $currentValue = $currentCptValueList[$i];
                            $values[strtolower($counterList[$i])] = $currentValue;
                        }

                        $this->CounterValuesListPerBlock[] = array($tabTopo, $values);
                        $currentCptValueList = [];
                    }
                }
            } else {
                // Handle unknown tag
            }
        }

        $this->xmlReader->close();
        return true;
    }

    private function fileSauvSql($filename, $csv_sql)
    {
        if (!$handle = fopen($filename, 'at')) {
            displayInDemon(__METHOD__ . " ERROR : impossible d'ouvrir le fichier (" . $filename . ")", "alert");
        } else {
            flock($handle, LOCK_EX);
            if (fwrite($handle, $csv_sql) === FALSE)
                displayInDemon(__METHOD__ . " ERROR : impossible d'écrire dans le fichier (" . $filename . ")<br>\n", "alert");
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function get_ne_info($moid, $family)
    {
        $cellPattern = "/(.*)\/GCELL:LABEL=(.*)\s*,\s*CellIndex=(.*)\s*,\s*CGI=(.*)/i";
        $bscPattern = "/(.*)\/(BSC).*:(.*)/i";
        $trxPattern = "/(.*)\/Cell:LABEL=(.*)\s*,\s*CellIndex=(.*)\s*,\s*CGI=(.*).*\/TRX.*Index=(.*)\s*,\s*TRX Name=(.*)/i";
        $topoTab = array();

        if (preg_match($cellPattern, $moid, $matches)) {
            $cell = trim($matches[4]) . "_" . trim($matches[3]);
            $cell_label = trim($matches[2]);
            $bsc = trim($matches[1]);

            switch ($family) {
                case "bss":
                    $topoInfo["Cell"] = $cell;
                    $topoInfo["Cell label"] = $cell_label;
                    $topoInfo["BSC"] = $bsc;

                    break;
                case "bssgprs":
                    $topoInfo["Cell"] = $cell;
                    $topoInfo["Cell label"] = $cell_label;
                    $topoInfo["PCU"] = $bsc;

                    break;
                case "bsstrx":
                    displayInDemon(__METHOD__ . " ERROR : measObjLdn Réservé pour famille bss et gprs<br>\n", "alert");
                    break;
            }
        } else if (preg_match($bscPattern, $moid, $matches)) {

            $bsc = trim($matches[3]);
            $cell = "virtual_" . $bsc;

            switch ($family) {
                case "bss":
                    $topoInfo["Cell"] = $cell;
                    $topoInfo["BSC"] = $bsc;
                    break;
                case "bssgprs":
                    $topoInfo["Cell"] = $cell;
                    $topoInfo["PCU"] = $bsc;

                    break;
                case "bsstrx":
                    displayInDemon(__METHOD__ . " ERROR : measObjLdn Réservé pour famille bss et gprs<br>\n", "alert");
                    break;
            }
        } else if (preg_match($trxPattern, $moid, $matches)) {

            $cell = trim($matches[4]) . "_" . trim($matches[3]);
            $cell_label = trim($matches[2]);
            $trx = $cell . "_" . trim($matches[5]);
            $trx_label = trim($matches[6]);

            switch ($family) {
                case "bss":
                    displayInDemon(__METHOD__ . " ERROR : measObjLdn Réservé pour famille trx <br>\n", "alert");
                    break;
                case "bssgprs":
                    displayInDemon(__METHOD__ . " ERROR : measObjLdn Réservé pour famille trx <br>\n", "alert");
                    break;
                case "bsstrx":
                    $topoInfo["Cell"] = $cell;
                    $topoInfo["Cell label"] = $cell_label;
                    $topoInfo["TRX"] = $trx;
                    $topoInfo["TRX label"] = $trx_label;
                    break;
            }
        } else {
            return null;
        }

        $topoTab["family"] = $family;
        $topoTab["topoInfo"] = $topoInfo;

        if ($family == "bssgprs" || $family == "bss") {
            $topoTab[$family]["base_ne"] = $cell;
        } else {
            $topoTab[$family]["base_ne"] = $trx;
        }

        return $topoTab;
    }

    public static function getConditionProvider($dbServices)
    {
        return new XMLConditionProvider($dbServices);
    }
}

?>
