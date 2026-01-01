<?php
/**
 * CB 5.3.1 WebService Topology
 * Class for interacting with an SMB server using the system command "smbclient".
 * Of course this assumes that you have the smbclient executable installed and
 * in your path.
 */
class SmbClient
{
    private $_service;
    private $_username;
    private $_password;
    
    private $_cmd;
    
    private $_last_cmd_stdout;
    /**
     * Gets stndard output from the last run command; can be useful in
     * case the command reports an error; smbclient writes a lot of
     * diagnostics to stdout.
     *
     * @return array each line of stdout is one string in the array
     */
    public function get_last_cmd_stdout () { return $this->_last_cmd_stdout; }
    
    private $_last_cmd_stderr;
    /**
     * Gets stndard error from the last run command
     *
     * @return array each line of stderr is one string in the array
     */
    public function get_last_cmd_stderr () { return $this->_last_cmd_stderr; }

    private $_last_cmd_exit_code;
    /**
     * Gets the exit code of the last command run
     *
     * @return int
     */    
    public function get_last_cmd_exit_code () { return $this->_last_cmd_exit_code; }
    
    /**
     * Creates an smbclient object
     *
     * @param string $service the UNC service name
     * @param string $username the username to use when connecting
     * @param string $password the password to use when connecting
     */
    public function __construct ($service, $username, $password)
    {
        $this->_service = $service;
        $this->_username = $username;
        $this->_password = $password;
    }

    /**
     * Gets a remote file
     *
     * @param string $remote_filename remote filename (use the local system's directory separators)
     * @param string $local_filename the full path to the local filename
     * @return bool true if successful, false otherwise
     */
    public function get ($remote_filename, $local_filename)
    {
        // convert to windows-style backslashes
        $remote_filename = str_replace (DIRECTORY_SEPARATOR, '\\', $remote_filename);
        //Bug 34115 - [REC][CB 5.3.1.01][Webservice]UploadFileRequest with right parameters is error for the special character in filename
        //$cmd = "get \"$remote_filename\" \"$local_filename\"";
        $cmd = "get \\\"$remote_filename\\\" \\\"$local_filename\\\"";
        $retval = $this->execute ($cmd);
        return $retval;
    }

    
    public function execute ($cmd)
    {
        $this->build_full_cmd($cmd);
        
        $outfile = tempnam(".", "cmd");
        $errfile = tempnam(".", "cmd");
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("file", $outfile, "w"),
            2 => array("file", $errfile, "w")
        );

        displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : Samba command = " . $this->_cmd, "normal", true); 
        $proc = proc_open($this->_cmd, $descriptorspec, $pipes);
       
        if (!is_resource($proc)) return 255;
    
        fclose($pipes[0]);    //Don't really want to give any input
    
        $exit = proc_close($proc);
        $this->_last_cmd_stdout = file($outfile);
        $this->_last_cmd_stderr = file($errfile);
        $this->_last_cmd_exit_code = $exit;

        displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : Samba stdout = " . print_r($this->_last_cmd_stdout,true), "normal", true); 
        displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : Samba stderr = " . print_r($this->_last_cmd_stderr,true), "normal", true); 
        displayInDemon(date("Y-m-d H:i:s") . " [WebService Topo] : Samba code = " . print_r($this->_last_cmd_exit_code,true), "normal", true); 

        $ret = strstr($this->_last_cmd_stdout[0], "NT_STATUS_OBJECT_NAME_NOT_FOUND");   //filename
        if (!$ret)
            $ret = strstr($this->_last_cmd_stdout[0], "NT_STATUS_BAD_NETWORK_NAME");    //netbios and repository
        if (!$ret)
            $ret = strstr($this->_last_cmd_stdout[0], "NT_STATUS_LOGON_FAILURE");       //sambaUser and sambaPwd

        if ($ret)
            return -1;
        else if ($exit)
            return 0;
       
        return 1;
    }    
    
    private function build_full_cmd ($cmd = '')
    {
        $this->_cmd = "smbclient '" . $this->_service . "'";
        
        if ($this->_username)
            $this->_cmd .= " -U '" . $this->_username . "'";
        
        if ($cmd)
            //Bug 34115 - [REC][CB 5.3.1.01][Webservice]UploadFileRequest with right parameters is error for the special character in filename
            //$this->_cmd .= " -c '$cmd'";
            $this->_cmd .= " -c \"$cmd\"";
        
        if ($this->_password)
            $this->_cmd .= " '" . $this->_password . "'";
    }
}


?>
