<?php
/**
 * Script gérant la création du serveur SOAP pour une API
 *
 * $Author: f.guillard $
 * $Date: 2015-01-16 14:15:46 +0100 (ven., 16 janv. 2015) $
 * $Revision: 156704 $
 */
    require_once( '../class/api/TrendingAggregationApi.class.php' );
	require_once( dirname( __FILE__)."/../php/environnement_liens.php");
	//ini_set( 'soap.wsdl_cache_enabled', '0' ); // Désactivation du cache pour le debug
    
    /** @var SoapServer object */
    $soapObj = NULL;

    /** @var DataBaseConnection object */
    $db = NULL;

    // 19/05/2014 - GFS Bug 39641 - [SUP] [AVP 41982] [Zain Koweit] File ta.wsdl corrupted
    /** @var String Chemin du fichier wsdl */
    $wsdlFile = dirname( __FILE__ ).'/ta.wsdl';

    /** @var String Adresse du server SOAP à insérer dans le WSDL */
    $soapLocation = '';

    try{
        $db = Database::getConnection();
		// 09/12/2011 ACS Mantis 837 Use ProductModel to deploy the definition_setup_product on slaves
        $idProduct = $db->getOne('SELECT sdp_id
                                    FROM sys_definition_product
                                    WHERE sdp_db_name=\''.$db->getDbName().'\';' );
		
		$productModel = new ProductModel($idProduct);
		$soapLocation = $productModel->getCompleteUrl('api/index.php');
    }
    catch( Exception $e ){
        // On laissera le WSDL inchangé
    }
    
    //Create a temporary wsdl file if the SOAP URL has been found
	$tmpWsdlFileName = NULL;
    if( strlen( $soapLocation ) > 0 )
    {
        if( ( $handle = fopen( $wsdlFile,'r' ) ) !== FALSE )
        {
            $contenu = file_get_contents( $wsdlFile );
            $contenuMod = preg_replace( '#(<soap:address location=")(.+)(" />)#', '${1}'.$soapLocation.'${3}', $contenu );
            fclose( $handle );

            //16/01/2015 - FGD - Bug 45086 - [SUP][NA][Webservice] : WSDL access return unconditionally "Couldn't bind to service"
            //Write in temporary wsdl file
            $tmpWsdlFileName = tempnam(sys_get_temp_dir(), 'wsdl');
            if( ( $handle = fopen($tmpWsdlFileName, 'w+' ) ) !== FALSE )
            {
                fwrite( $handle, $contenuMod );
                fclose( $handle );
            }
        }
    }

    $wsdlFileToUse = "ta.wsdl";
	if($tmpWsdlFileName!=NULL){
		//we succeffuly created a temporary wsdl file with the correct endpoint
		$wsdlFileToUse = $tmpWsdlFileName;
	}
    $soapObj = new SoapServer($wsdlFileToUse, array( 'soap_version' => SOAP_1_2, 'encoding'=>'UTF-8', 'uri' => __FILE__ ) );
    $soapObj->setClass( 'TrendingAggregationApi' );
    $soapObj->setPersistence( SOAP_PERSISTENCE_SESSION );
    $soapObj->handle();
    if($tmpWsdlFileName!=NULL){
    	unlink($tmpWsdlFileName);
    }
