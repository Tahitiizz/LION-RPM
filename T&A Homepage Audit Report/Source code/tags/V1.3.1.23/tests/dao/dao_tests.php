<?php


/*
 * Pre-requis pour Xdebug :
 *
 * 		Soucrce du CB non cryptees.
 *
 * 		xenv.inc (sans d'anti slash !) :
 * 			- Avant : $repertoire_physique_niveau0="/home/jgu2_eribss_hpmod/";
 * 			- Apres : $repertoire_physique_niveau0="W:\jgu2_eribss_hpmod\";
 *
 * 		xbdd.inc :
 * 			- Avant : $AHost = "localhost";
 * 			- Apres : $AHost = "192.168.172.129";
 *
 *
 *
 * Scripts CB necessaires :
 * 		class\Bean.class.php
 * 		class\QueryBean.class.php
 * 		models\QueryDataModel.class.php => public ici : public function getData($query) {...
 *  	models\RawKpiModel.class.php pour au moins getByIdFam()
 *  	ajout de "../" dans les "include_once" des scripts (ex: kpi_listhtml.php) mais pas
 *  			devant celui des classes comme "class/QbRawKpi.class.php".
 *  	dossier api => ajout de "../" dans certains "include_once"
 *  	dossier "querybuilderExtJS4" (ses classes, etc).
 */

include_once '/../../../class/QueryBean.class.php';
include_once '/../../../homepage/proxy/dao/models/FamilyModel.class.php';
include_once '/../../../homepage/proxy/dao/models/NeModelBis.class.php';
include_once '/../../../homepage/proxy/dao/models/NaModelBis.class.php';
include_once '/../../../models/Productmodel.class.php';
include_once '/../../../class/DatabaseConnection.class.php';
include_once '/../../../class/DatabaseConnection.class.php';
include_once '/../../../class/Database.class.php';
include_once(dirname(__FILE__)."/../../../php/environnement_liens.php");
include_once '/../../../homepage/proxy/dao/models/QueryDataModel.class.php';
include_once $repertoire_physique_niveau0.'homepage/tests/dao/class/JsonProviderTest.class.php';
include_once $repertoire_physique_niveau0.'homepage/proxy/dao/class/JsonProvider.class.php';
include_once $repertoire_physique_niveau0.'homepage/tests/dao/class/HtmlProviderTest.class.php';
include_once $repertoire_physique_niveau0.'homepage/proxy/dao/querybuilderExtJS4/class/QbRawKpi.class.php';
include_once $repertoire_physique_niveau0.'homepage/proxy/dao/querybuilderExtJS4/class/QbFacade.class.php';


echo "START \n";

$parameters = array();

$parameters["product_id"] = "1";
$parameters["kpi_id"] = "kpis.0075.01.05034"; // C_Nb_Assignment_Attempts1
$parameters["time_level"] = "hour";
$parameters["ne_network_level"] = "bsc";
$parameters["ne_id"] = "EABBS01";
$parameters["raw_id"] = "raws.0055.bss.00004";


$jsonProviderTest = new JsonProviderTest();
$jsonProvider = new JsonProvider();
$qBfacade = new QbFacade();

//For XDebug with Eclipse :

/*
 // Seulement si "getData" est publc dans "QueryDataModel.class.php"
 $jsonResult = $jsonProviderTest->getGaugeData($parameters);
 // Resultat, ex: {"count":1,"label":["hour","kpis.0055.01.03004"],"data":[["2010062819","180909"]]}
 echo "<br>\nResult: ".$jsonResult."\n";

 $jsonResult = $jsonProviderTest->getNeLabel($parameters);
 echo "<br>\nResult: ".$jsonResult."\n";

 $jsonResult = $jsonProviderTest->getKpiLabel($parameters);
 echo "<br>\nResult: ".$jsonResult."\n";

 $jsonResult = $jsonProviderTest->getRawLabel($parameters);
 echo "<br>\nResult: ".$jsonResult."\n";
 */

/*
 // getGaugeData
 $link = "http://192.168.172.129/jgu2_siebss_val/homepage/proxy/homepage.php?method=getGaugeData&product_id=".$parameters["product_id"]."&kpi_id=".$parameters["kpi_id"]."&time_level=".$parameters["time_level"]."&ne_network_level=".$parameters["ne_network_level"]."&ne_id=".$parameters["ne_id"];
 echo "<br><br><a href='$link'>$link</a><br>";

 // getNeLabel
 $link = "http://192.168.172.129/jgu2_siebss_val/homepage/proxy/homepage.php?method=getNeLabel&product_id=".$parameters["product_id"]."&ne_network_level=".$parameters["ne_network_level"]."&ne_id=".$parameters["ne_id"];
 echo "<br><a href='$link'>$link</a><br>";

 // getKpiLabel
 $link = "http://192.168.172.129/jgu2_siebss_val/homepage/proxy/homepage.php?method=getKpiLabel&product_id=".$parameters["product_id"]."&kpi_id=".$parameters["kpi_id"];
 echo "<br><a href='$link'>$link</a><br>";

 // getRawLabel
 $link = "http://192.168.172.129/jgu2_siebss_val/homepage/proxy/homepage.php?method=getRawLabel&product_id=".$parameters["product_id"]."&raw_id=".$parameters["raw_id"];
 echo "<br><a href='$link'>$link</a><br>";

 // getGraphLineData
 // TODO
 */

$htmlProvider = new HtmlProviderTest();
$parameters['filterOptions']=
// "id":"1","families":["3","4"]
<<<EOD
{
	"products":[
		{
			"id":"1"
		}	
	],
	"text":"Packet_Data_Access"
}
EOD;


//$htmlresult = $htmlProvider->getKpiList($parameters);
//echo $htmlresult;

// lancement d'un echo (Cf. stdout) sur tous les couples produit / famille
//echo "<br>\nResult for 'getProductsFamilies': ";
//$qBfacade->getProductsFamilies();

// recherche d'elements reseaux
/*
 $neModelBis = new NeModelBis();
 $naTab = array('bsc');
 $product = (object) array("id" => 1, "na" => $naTab);
 $productsAndNa = array($product);
 $limit = 5;
 $labelFilter = "AB";
 $result = $neModelBis->getFilteredNe($productsAndNa, $limit, $labelFilter);
 */


/*
 $jsonResult = $jsonProvider->getNaFromFamily("bss", "1");
 echo "<br>\nResult: ".$jsonResult."\n";
 */

// ID de la cell Iu : 41027_52 our le label "SAI_401_41027"
/*
$myParameters=
<<<EOD
{
"parameters": {
        "select": {
            "data": [
            ...
*/
echo "<br>\nResult:\n";
$myParameters=
<<<EOD
{
        "select": {
            "data": [
                {
                    "id": "hour",
                    "type": "ta",
                    "order": "Descending"
                },
                {
                    "id": "cell",
                    "type": "na"
                },
                {
                    "id": "host",
                    "type": "na_axe3"
                },
                {
                    "id": "kpis.0016.09.00006",
                    "productId": "1",
                    "type": "KPI"
                }
            ]
        },
        "filters": {
            "data": [
                {
                    "type": "na",
                    "value": "4201_5",
                    "operator": "In",
                    "id": "cell"
                },
                {
                    "type": "na_axe3",
                    "operator": "In",
                    "value": "pvs02p.cvf.fr",
                    "id": "host"
                },
                {
                    "id": "maxfilter",
                    "type": "sys",
                    "value": 10
                }
            ]
        }
 }
EOD;

$myQueryDataModel = new QueryDataModel();
$myDecodedParameters = json_decode($myParameters);
$myJsonResult = $myQueryDataModel->getDataAndLabels($myDecodedParameters);
echo $myJsonResult;


echo "\nEND\n";

?>