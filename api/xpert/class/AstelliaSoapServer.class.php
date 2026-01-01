<?php

/**
 * Objet surchargeant le serveur SOAP fournis par défaut avec PHP
 * 
 * Cela permet d'intercepter toutes les requètes à destination du serveur.
 * 
 * Ce sont des information très utile pour le débug. 
 * 
 *
 */
class AstelliaSoapServer extends SoapServer
{
	

	/**
	 * Constructor
	 *
	 * @param mixed $wsdl
	 * @param array[optional] $options
	 */
	public function __construct($wsdl, $options = null)
	{
		//$this->debugTimer = new Timer();
		//$this->debugTimer->start();

		return parent::__construct($wsdl, $options);
	}
	

	/**
	 * Collect some debuging values and handle the soap request.
	 *
	 * @param string $request
	 * @return void
	 */
	public function handle()
	{
		$request = $this->soaputils_autoFindSoapRequest();
		
		Log::getLog()->begin("AstelliaSoapServer::handle($request)");
		
		// store the remote ip-address
		Log::getLog()->debug('RemoteAddress : '.$_SERVER['REMOTE_ADDR']);

		// check variable HTTP_RAW_POST_DATA
		if (!isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
			$GLOBALS['HTTP_RAW_POST_DATA'] = file_get_contents('php://input');
		}

		// check input param
		if (is_null($request)) {
			$request = $GLOBALS['HTTP_RAW_POST_DATA'];
		}

		// get soap namespace identifier
		if (preg_match('°:Envelope[^>]*xmlns:([^=]*)="urn:NAMESPACEOFMYWEBSERVICE"°im',
		$request, $matches)) {
			$soapNameSpace = $matches[1];

			// grab called method from soap request
			$pattern = '°<' . $soapNameSpace . ':([^\/> ]*)°im';
			if (preg_match($pattern, $request, $matches)) {
				Log::getLog()->debug('MethodName : '.$matches[1]);
				$methodName = $matches[1];
			}
		}		
		
		//On décompose l'action passée en argument pour la logger
		$subAction = strstr($_SERVER['CONTENT_TYPE'],'action="');		
		Log::getLog()->info("LAST Acces Ip : ''".$_SERVER['REMOTE_ADDR']."'' ".$subAction);

		// store the request string
		Log::getLog()->debug('RequestString : '.$request);

		// store the request headers
		if (function_exists('apache_request_headers')) {
			Log::getLog()->debug('RequestHeader : '.serialize(apache_request_headers()));
		}

		ob_start();

		// finaly call SoapServer::handle() - store result
		$result = parent::handle($request);


		// store the response string
		Log::getLog()->debug('ResponseString : '.ob_get_contents());

		// flush buffer
		ob_flush();

		// store the response headers
		if (function_exists('apache_response_headers')) {
			Log::getLog()->debug('ResponseHeader : '.serialize(apache_response_headers()));
		}	
		
		Log::getLog()->end();
		
		return $result;
	}
	
	
	
	function soaputils_autoFindSoapRequest()    {
	    
		/*return '<?xml version="1.0" encoding="ISO-8859-15"?>
				<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope"><env:Body><iProductId2>0</iProductId></env:Body></env:Envelope>';
		return '<?xml version="1.0" encoding="UTF-8"?>
				<env:Envelope xmlns:env="http://www.w3.org/2003/05/soap-envelope"><env:Body><dummy2>0</dummy2></env:Body></env:Envelope>';*/
		global $HTTP_RAW_POST_DATA;
	   
	    if($HTTP_RAW_POST_DATA)
	        return $HTTP_RAW_POST_DATA;
	   
	    $f = file("php://input");
	    return implode(" ", $f);
	    
	    
	}
	
}
?>