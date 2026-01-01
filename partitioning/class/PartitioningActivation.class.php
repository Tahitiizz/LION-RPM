<?php
/**
 * This class manages activation of partitioning on a T&A database
 *
 * 13/10/2011 MMT DE Bypass temporel ajout de parametre global pour max Nb db partitionné
 *
 */
class PartitioningActivation
{
    // Constants
    const CONF_INI_FILE         = 'partitioning/pgparameters.ini';
    const EXPECTED_PG_VERSION   = '9.1';
    const PARTITIONING_FILE     = '/tmp/partitioning';

    // Variables
    protected $_database = null;
    protected $_nbPartitionedDb = 0;

    /**
     * Constructor
     * @param DataBaseConnection $database
     */
    public function __construct(DataBaseConnection $database)
    {
        $this->_database = $database;
    }

    /**
     * Checks Postgresql Parameters
     * @return array or boolean
     */
    public function checkPgParameters()
    {
        $nonConformParameters = array();

        if(!file_exists(REP_PHYSIQUE_NIVEAU_0.self::CONF_INI_FILE))
            throw new Exception('Postgresql expected configuration file does not exist');

        $pgExpectedConf = parse_ini_file(REP_PHYSIQUE_NIVEAU_0.self::CONF_INI_FILE);
        foreach($pgExpectedConf as $parameter => $expectedValue)
        {
            $value = $this->_database->getOne("SHOW $parameter");
            if($value != $expectedValue)
                $nonConformParameters[$parameter] = $expectedValue;
        }

        if(count($nonConformParameters) == 0)
            return true;

        return $nonConformParameters;
    }

    /**
     * Checks Postgresql version
     * @return boolean
     */
    public function checkVersion()
    {
        return ($this->_database->getVersion() >= self::EXPECTED_PG_VERSION);
    }

    /**
     * Returns number of partitioned databases
     * @return integer
     */
    public function getNbPartitionedDb()
    {
        return $this->_nbPartitionedDb;
    }

    /**
     * Returns Configuration status. False if limit is reached
     * @return boolean
     */
    public function isPostgresqlConfigurationLimitReached()
    {
        // Current DB
        $currentDB = $this->_database->getDbName();
        // Get existing Databases
        $existingDatabases = $this->_database->getDatabases();
        // Browsing database
        $calculations = array();
        foreach($existingDatabases as $id => $database)
        {
            $this->_database->changeDatabase($database);
            if($this->_database->isPartitioned() || ($database == $currentDB))
            {
                // Fetch max history value
                $query = "SELECT MAX(duration)
                FROM
                ((SELECT
                        MAX(CASE WHEN parameters = 'history_hour' THEN value::integer*24 ELSE value::integer END) AS duration
                FROM
                        sys_global_parameters
                WHERE
                        parameters LIKE 'history_%')
                UNION ALL
                (SELECT
                        MAX(CASE WHEN ta = 'hour' THEN duration::integer*24 ELSE duration::integer END) AS duration
                FROM
                        sys_definition_history)) AS t0;";

                $H = $this->_database->getOne($query);
                $calculations[] = (1 + $H + 3 * $H + 1) * 2;
                $this->_nbPartitionedDb++;
            }
        }
        // Restoring initial database
        $this->_database->changeDatabase($currentDB);
        // Returning result
		  // 13/10/2011 MMT DE Bypass temporel ajout de parametre global pour max Nb db partitionné
        return ((array_sum($calculations) > 10000) || ($this->_nbPartitionedDb > get_sys_global_parameters( 'partitioned_max_db_allowed')));
    }

    /**
     * Gathers all needed information for each product to partition
     * @param array $listOfProducts
     * @return array $neededInfo
     */
    public function gatherPartitioningInformation(array $listOfProducts, $email = '')
    {
        $neededInfo = array();
        foreach($listOfProducts as $prodId)
        {
            $productModel = new ProductModel($prodId);
            $productInfos = $productModel->getValues();
            $neededInfo[$prodId]['app_name']        = $productInfos['sdp_label'];
            $neededInfo[$prodId]['app_dir']         = $productInfos['sdp_directory'];
            $neededInfo[$prodId]['ip_address']      = $productInfos['sdp_ip_address'];
            $neededInfo[$prodId]['database_name']   = $productInfos['sdp_db_name'];
            $neededInfo[$prodId]['ssh_user']        = empty($productInfos['sdp_ssh_user']) ? 'astellia' : $productInfos['sdp_ssh_user'];
            $neededInfo[$prodId]['ssh_port']        = empty($productInfos['sdp_ssh_port']) ? 22 : $productInfos['sdp_ssh_port'];
            $neededInfo[$prodId]['ssh_password']    = empty($productInfos['sdp_ssh_password']) ? 'astellia' : $productInfos['sdp_ssh_password'];
            $neededInfo[$prodId]['email']           = $email;
        }
        return $neededInfo;
    }

    /**
     * Creates a new partitioning file
     */
    public function createPartitioningFile()
    {
        $f = fopen(self::PARTITIONING_FILE, 'w+');
        fclose($f);
    }

    /**
     * Writes data in partitioning file
     * @param array $neededInfo
     */
    public function writePartitioningFile(array $neededInfo)
    {
        $f = fopen(self::PARTITIONING_FILE, 'a+');
        foreach($neededInfo as $prodId => $infos) {
            fwrite($f, implode(",",$infos)."\n");
        }
        fclose($f);
    }

    /**
     * Checks if partitioning file exists
     * @return boolean
     */
    public static function checkPartitioningFile()
    {
        return file_exists(self::PARTITIONING_FILE);
    }

    /**
     * Checks if partitioning file concerns current product
     * Added for BZ 22820
     * @author BBX
     * @param array $neededInfo
     * @return boolean
     */
    public function isPartitioningConcerningMe(array $neededInfo)
    {
        // Browsing products
        foreach(ProductModel::getActiveProducts() as $product)
        {
            foreach($neededInfo as $prodId => $infos)
            {
                if(($product['sdp_ip_address'] == $infos['ip_address'])
                && ($product['sdp_db_name'] == $infos['database_name']))
                {
                    return true;
                }
            }
        }

        // Returning result
        return false;
    }

    /**
     * Parses partitioning file
     * @return array
     */
    public function parsePartitioningFile()
    {
        $neededInfo = array();
        if(self::checkPartitioningFile())
        {
            $partitioningFile = file(self::PARTITIONING_FILE);
            foreach($partitioningFile as $line) {
                if(trim($line) != '')
                {
                    $offset = count($neededInfo);
                    $lineInfos = explode(",", trim($line));
                    $neededInfo[$offset]['app_name']        = $lineInfos[0];
                    $neededInfo[$offset]['app_dir']         = $lineInfos[1];
                    $neededInfo[$offset]['ip_address']      = $lineInfos[2];
                    $neededInfo[$offset]['database_name']   = $lineInfos[3];
                    $neededInfo[$offset]['ssh_user']        = $lineInfos[4];
                    $neededInfo[$offset]['ssh_port']        = $lineInfos[5];
                    $neededInfo[$offset]['ssh_password']    = $lineInfos[6];
                    $neededInfo[$offset]['email']           = $lineInfos[7];
                }
            }
        }
        return $neededInfo;
    }

    /**
     * Returns monitoring for partitioning
     * @param array $neededInfo
     * @return array
     */
    public function monitorPartitioning(array $neededInfo)
    {
        // Array for result
        $monitor = array();
        // Fetching master ip adress
        $masterModel = new ProductModel(ProductModel::getIdMaster());
        $masterInfos = $masterModel->getValues();
        $masterIp    = $masterInfos['sdp_ip_address'];
        // Browsing products
        foreach($neededInfo as $prodId => $infos)
        {
            // Number of tries to get information
            $nbTries = 0;
            while(!isset($monitor[$infos['app_name']]) && ($monitor[$infos['app_name']] == ''))
            {
                // If a try already occured, let's wait 250ms. The log file needs time to be written.
                if($nbTries >= 1) usleep (250000);
                // Command to test number of lines
                $commandNbLines = 'awk \'END {print NR}\' /tmp/'.$infos['app_dir'];
                // Command to fetch last line
                $command = 'tail -n1 /tmp/'.$infos['app_dir'];
                // Local product
                if($masterIp == $infos['ip_address']) {
                    $nbLines = exec($commandNbLines);
                    $current = exec($command);
                }
                // Remote product
                else {
                    // 28/06/2011 BBX
                    // Ajout de trim pour supprimer les espaces malicieux
                    // BZ 22812
                    $ssh = new SSHConnection($infos['ip_address'], $infos['ssh_user'], $infos['ssh_password'], $infos['ssh_port']);
                    $results = $ssh->exec($command);
                    $current = trim($results[0]);
                    $results = $ssh->exec($commandNbLines);
                    $nbLines = trim($results[0]);
                }
                // If nothing done yet, let's force 0%
                if($nbLines < 2) {
                    $monitor[$infos['app_name']] = "0";
                }
                // Interpreting data
                elseif(!preg_match('#^:#',$current, $toto)) {
                    // Process complete, reading execution result
                    $monitor[$infos['app_name']] = ($current == "0") ? "true" : "false";
                }
                else
                {
                    // Process running fetching percentage done
                    preg_match('/\(([0-9]+) %\)/',$current,$matches);
                    $monitor[$infos['app_name']] = $matches[1];
                }
                // Counting tries
                $nbTries++;
            }
        }
        return $monitor;
    }

    /**
     * Copies log files into upload directory so that they can be downloaded
     * @param array $neededInfo
     */
    public function fetchLogFiles(array $neededInfo)
    {
        // Fetching master ip adress
        $masterModel = new ProductModel(ProductModel::getIdMaster());
        $masterInfos = $masterModel->getValues();
        $masterIp    = $masterInfos['sdp_ip_address'];
        // Browsing products
        foreach($neededInfo as $prodId => $infos)
        {
            // Local product
            if($masterIp == $infos['ip_address']) {
                copy('/tmp/'.$infos['app_dir'], REP_PHYSIQUE_NIVEAU_0.'upload/'.$infos['app_dir']);
            }
            // Remote product
            else {
                $ssh = new SSHConnection($infos['ip_address'], $infos['ssh_user'], $infos['ssh_password'], $infos['ssh_port']);
                $ssh->getFile('/tmp/'.$infos['app_dir'], REP_PHYSIQUE_NIVEAU_0.'upload/'.$infos['app_dir']);
            }
        }
    }

    /**
     * Returns start time
     * @param array $neededInfo
     * @return integer
     */
    public function getStartTime(array $neededInfo)
    {
        // Array for result
        $startTime = array();
        // Fetching master ip adress
        $masterModel = new ProductModel(ProductModel::getIdMaster());
        $masterInfos = $masterModel->getValues();
        $masterIp    = $masterInfos['sdp_ip_address'];
        // Browsing products
        foreach($neededInfo as $prodId => $infos)
        {
            // Command to fetch last line
            $command = 'head -n1 /tmp/'.$infos['app_dir'];
            // Local product
            if($masterIp == $infos['ip_address']) {
                $start = exec($command);
            }
            // Remote product
            else {
                $ssh = new SSHConnection($infos['ip_address'], $infos['ssh_user'], $infos['ssh_password'], $infos['ssh_port']);
                $results = $ssh->exec($command);
                $start = $results[0];
            }
            $startTime[] = $start;
        }
        return min($startTime);
    }

    /**
     * Deletes partitioning file
     */
    public function deletePartitioningFile()
    {
        unlink(self::PARTITIONING_FILE);
    }

    /**
     * Returns number max of threads to use for migration
     * @return integer
     */
    public static function getMaxThreads()
    {
        return (int)exec('grep "processor" /proc/cpuinfo | wc -l');
    }

    /**
     * Launches partitioning operations on each selected products
     * @param array $neededInfo
     */
    public function preparePartitioning(array $neededInfo)
    {
        // Fetching master ip adress
        $masterModel = new ProductModel(ProductModel::getIdMaster());
        $masterInfos = $masterModel->getValues();
        $masterIp    = $masterInfos['sdp_ip_address'];

        // Browsing products to partition
        foreach($neededInfo as $productInfos)
        {
            // LOCAL
            if($masterIp == $productInfos['ip_address'])
            {
                // Creates process file locally
                $f = fopen('/tmp/'.$productInfos['app_dir'], 'w+');
                fwrite($f, time()."\n");
                fclose($f);
                $mode = 'local';
            }
            // REMOTE
            else
            {
                // Creates process file remotely
                $ssh = new SSHConnection($productInfos['ip_address'], $productInfos['ssh_user'], $productInfos['ssh_password'], $productInfos['ssh_port']);
                $ssh->exec('echo "'.time().'" > /tmp/'.$productInfos['app_dir']);
                $mode = 'remote';
            }
            // Invoking launcher thah will launch process on products
            $phpCmd = 'php -f '.REP_PHYSIQUE_NIVEAU_0.'partitioning/launcher.php '.$mode.' '.$productInfos['app_dir'].' '.$productInfos['ip_address'].' '.$productInfos['ssh_user'].' '.$productInfos['ssh_password'].' '.$productInfos['ssh_port'].' '.$productInfos['email'];
            $cmd = 'nohup nice -n 10 '.$phpCmd.' action=generate var1_id=23 var2_id=35 gen_id=535 > /dev/null & echo $!';
            $pid = shell_exec($cmd);
        }
    }

    /**
     * Check for processes that are checked or are running
     * @param array $neededInfo
     * @return type
     */
    public function checkProcesses(array $neededInfo)
    {
        // Browsing products to partition
        foreach($neededInfo as $productInfos)
        {
            // Product ID
            $prodId = ProductModel::getProductFromDatabase($productInfos['database_name'], $productInfos['ip_address']);
            $productModel = new ProductModel($prodId);
            // If processes are checked
            if(count(MasterModel::getCheckedProcesses($prodId)) > 0) {
                $productValues = $productModel->getValues();
                return $productValues['sdp_label'];
            }
            // If current product has processes running
            if($productModel->isProcessRunning()) {
                $productValues = $productModel->getValues();
                return $productValues['sdp_label'];
            }
        }
        return false;
    }
}
?>
