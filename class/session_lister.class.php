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
//########################################################################################
// -------------- Summary
// V1.0
// lists all concurrent sessions on a server. Need read access to temps sessions dir.
// V1.1
// as old sessions are sometimes present on the server, the new version returns the creation date
// the last modif date, but also the age of the session. if the session is older than 4 hours for
// example, you can choose to ignore it.
// So you can for example list all users logged within the last 4 hours.
//
// -------------- Author
// Logan Dugenoux - 2003
// logan.dugenoux@netcourrier.com
// http://www.peous.com/logan/
//
// -------------- License
// GPL
//
// -------------- Methods :
// - getSessionsCount()
// - getSessions()
//
// ------------- Example :
// echo $sl->getSessionsCount()." sessions available<br>";
// foreach( $sl->getSessions() as $sessName => $sessData )
// {
//    echo "<hr>Session ".$sessName." :<br>";
//    echo " Rawdata = ".$sessData["raw"]."<br>";
//    echo " creation date = ".date( "d/m/Y H:i:s",$sessData["creation"])."<br>";
//    echo " last modif date = ".date( "d/m/Y H:i:s",$sessData["modification"])."<br>";
//    echo " age = ".round($sessData["age"]/3600/24,1)." days<br>";
// }
//
// Have fun !!!
//
//########################################################################################

	class sessionLister
	{
		var $diffSess;

		function sessionLister()
   		{
   		}

   		function getSessionsCount()
   		{
   			if (!$this->diffSess)
   				$this->readSessions();
   			return sizeof($this->diffSess);
   		}

   		function getSessions()
   		{
   			if (!$this->diffSess)
   				$this->readSessions();
   			return $this->diffSess;
   		}

   		//------------------ PRIVATE ------------------
   		function readSessions()
   		{
			$sessPath = get_cfg_var("session.save_path")."\\";
			$sessPath_real = get_cfg_var("session.save_path")."/";
			$diffSess = array();

			$dh = @opendir($sessPath);
			while(($file = @readdir($dh)) !==false )
			{
				if($file != "." && $file != "..")
				{
					echo $file;
					$fullpath = $sessPath.$file;
					if(!@is_dir($fullpath))
					{
						// "sess_7480686aac30b0a15f5bcb78df2a3918"
						$fA = explode("_", $file);
						// array("sess", "7480686aac30b0a15f5bcb78df2a3918")
						$sessValues = file ( $fullpath );	// get raw session data
						var_dump($sessValues);
						// this raw data looks like serialize() result, but is is not extactly this, so if you can process it... le me know
						/*$this->diffSess[$fA[1]]["raw"] = $sessValues;
						$this->diffSess[$fA[1]]["age"] = time()-filectime( $fullpath );
						$this->diffSess[$fA[1]]["creation"] = filectime( $fullpath );
						$this->diffSess[$fA[1]]["modification"] = filemtime( $fullpath );*/
					}
				}
			}
			@closedir($dh);
   		}
	}
?>
