<?
/*
 * 09/10/2012 ACS DE GIS 3D ONLY
 *
 * @cb514@
 *
 *  04/07/2011 NSE bz 22870 : mauvaise légende dans Gis 3D, ajout de urlencode
 *  16/11/2012 MMT bz 30492 fenetre export GIS 3D crashe sur RH 6.2 en mode crypté - remplacement des characters \r, \n
 *  16/11/2012 MMT bz 30276 ajout gestion top-worst pour DE GIS 3D only
 *
 */
?><?php
/*
 *  cb50400
 *
 *  18/08/2010 NSE DE Firefox bz 17375 : download gis 3D KO
 */
?><?php
//	- maj 26/05/2008 - maxime : Fichier intermédiaire permettant d'afficher un loading dans la pop-up de l'export Google Earth
//	- maj 15:07 22/02/2010 - MPR :  - Correction du BZ9069 - Export Google Earth KO avec IE6
//					      	   - Ajout de condition pour gérer chacun des navigateurs (réécriture de  la fonction)

session_start();

include_once(dirname(__FILE__)."/../../php/environnement_liens.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");

?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<title>GIS 3D Export</title>
	<link rel="stylesheet" href="<?=NIVEAU_0?>css/global_interface.css" type="text/css">
	<script>
	<!--//
//  Fonctions de gestion du loader.

	 var t_id = setInterval(animate,20);
        var pos=0;
        var dir=2;
        var len=0;

        function animate(){
                var elem = document.getElementById('progress');
                if(elem != null) {
                        if (pos==0) len += dir;
                        if (len>32 || pos>79) pos += dir;
                        if (pos>79) len -= dir;
                        if (pos>79 && len==0) pos=0;
                        elem.style.left = pos;
                        elem.style.width = len;
                }
        }

        function remove_loading() {
                this.clearInterval(t_id);
                var targelem = document.getElementById('loader_container');
                targelem.style.display='none';
                targelem.style.visibility='hidden';
        }

        // maj 15:07 22/02/2010 - MPR :  - Correction du BZ9069 - Export Google Earth KO avec IE6
        //					      - Ajout de condition pour gérer chacun des navigateurs (réécriture de  la fonction)
        function getHTTPObject() {

                 var xmlhttp;

            if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
                    // Firefox et autres
                    try {
                            xmlhttp = new XMLHttpRequest();
                    } catch (e) {
                            xmlhttp = false;
                    }
            }
            else if(window.ActiveXObject) {
                    // Internet Explorer
                    try {
                            xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
                    }
                    catch (e) {
                            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
                    }
            }
                    return xmlhttp;
        }

    //-->
    </script>
</head>

<body style="margin:0; text-align:center;">
<style type="text/css">
.entete{
color: #fff;
background-color : #929292;
font : bold 9pt Verdana, Arial, sans-serif;
text-align: center;
}
#interface1 { z-index:1; }
#loader_container {text-align:left; position:absolute; top:25%; width:75%; left:25%;}
#loader {
	font-family:Tahoma, Helvetica, sans;
	font-size:11px;
	color:#000000;
	background-color:#FFFFFF;
	padding:10px 0 16px 0;
	margin:0 auto;
	display:block;
	width:130px;
	border:1px solid #6A6A6A;
	text-align:left;
	z-index:2;
}
#progress {
	height:5px;
	font-size:1px;
	width:1px;
	position:relative;
	top:1px;
	left:0px;
	background-color:#9D9D94
}
#loader_bg {background-color:#EBEBE4;position:relative;top:8px;left:8px;height:7px;width:113px;font-size:1px}
</style>

<div id="loader_container">
	<div id="loader">
		<div align="center" id="texteLoader"></div>
		<div id="loader_bg"><div id="progress"> </div>
	</div>
</div>

<div id = "download_file" style="position: absolute; top: 1px; left: 1px;   visibility: hidden;">


<?

// On complète l'url en fonction des paramètres à ajouter
if($_GET['gis_data']){
    // 04/07/2011 NSE bz 22870 : mauvaise légende dans Gis 3D, ajout de urlencode
    $link = "export_gearth.php?gis_data=".urlencode($_GET['gis_data']);

}else{

	$link = "export_gearth.php";

}
// $link = ($_GET['action'] == 'export_dash_alarm') ? "export_gearth_dash_alarm.php?": "export_gearth.php?";
// $link.= 'gis_data='.$_GET['gis_data'];
// 09/10/2012 ACS DE GIS 3D ONLY
if (isset($_GET['limitation'])) {
    $separator = (substr_count($link, "?") > 0) ? "&" : "?";
    $link .= $separator."limitation=".$_GET['limitation'];
    if (isset($_GET['order'])) $link .= "&order=".$_GET['order'];
}

?>

<script>

	var http = getHTTPObject();


	http.onreadystatechange = function(){
		if ( http.readyState == 4 ){ 	//Actions executées une fois le chargement fini
			if(http.status  != 200){	//Message si il se preoduit une erreur
				document.getElementById("download_file").innerHTML ="Error code " + xhr2.status;

			} else {
				//On met le contenu du fichier externe dans la div "content"
				remove_loading();
				var html = "";


				html+= "<body><head><title>GIS 3D File</title>\n";

				html+= "<link rel='stylesheet' href='<?=NIVEAU_0?>css/global_interface.css' type='text/css'></head>\n";               
				html+= "<body class='tabPrincipal'><div align='center' style ='width:100%;padding-bottom:10px'>\n";
				html+= "<fieldset style='width:90%'>\n";
				html+= "<legend>&nbsp;<img src='<?=NIVEAU_0?>images/icones/download.png'>&nbsp;</legend>\n";

				<?php
				// maj 26/05/2008 - maxime : On ajoute l'export depuis les dash ou résultat des alarmes
                                // 18/08/2010 NSE DE Firefox bz 17375 : remplacement \ par /
                                // maj 03/09/2010 - MPR : Correction du bz 17375 : Suppression du setTimeout - Si on ne clique pas rapidement sur "Ouvrir ou "Enregistrer Sous" la pop-up se ferme
				// 09/10/2012 ACS DE GIS 3D ONLY
      
                
                
//10/10/2012 MMT DE GIS 3D ONLY - code javascript utilisé pour la submittion du popup
$js="
function isNormalInteger(str) {
    var n = ~~Number(str);
    return String(n) === str && n > 0;
}


function submitPopup(){
    
   validOk = true;
    var additionalParameters = 'limitation=0';
    if (document.getElementById('export_choice_1').checked) {
        var limitation = document.getElementById('gis_limit').value;
        if(!isNormalInteger(limitation)){
            validOk = false;
            alert('Invalid limit value:'+limitation);
        } 
        var order = document.getElementById('export_order').value;
        additionalParameters = 'limitation=' + limitation + '&order=' + order;
    }
    if(validOk){
        var sep = '?';
        var loc = document.location.href;
        if (loc.indexOf('?', 0) > 0) {
            sep = '&';
        }
        document.location.href = window.location + sep + additionalParameters;
        window.resizeTo(600, 180);
    }
}
submitPopup();
";   
// il faut 'escaper' les ' et enlever les retour chariot pour inserer tout sur une ligne
// 16/11/2012 MMT 30492 fenetre export GIS 3D crashe sur RH 6.2 en mode crypté - remplacement des characters \r, \n
$js = str_replace(array("\r\n", "\r", "\n"), "", $js);
$submitJs = str_replace("'", "\'", $js);

//10/10/2012 Popup de warning/selection de la limite
				?>
				if (http.responseText.indexOf("|s|") != -1) {
                    //alert(http.responseText);
				    var returnValues = http.responseText.split("|s|");
				    var type = returnValues[0];
				    var numOfElements = returnValues[1];
				    var limitOfelements = returnValues[2];
				    var networkElement = returnValues[3];
				    var labelElement = returnValues[4];

					html += '<div id="export_box" class="gis3Dinfo">';
					html += '<div>';
					html += 'The export contains ' + numOfElements + ' ' + networkElement + '(s)</span>, ';
					html += 'it may lead to performance issue on Google Earth.<br />';
					html += 'It is suggested to limit the number of exported elements:';
					html += '</div>';
					html += '<div class="gis3DinfoSection">';
					html += '<input type="radio" id="export_choice_1" name="export_choice" value="1" checked="true" style="float: left; margin: 15px 3px 0 3px;" />';
					html += '<label for="export_choice_1">';
					html += 'Limit to the first <input type="text" id="gis_limit" size="10" name="export_number" value="' + limitOfelements + '" /> ';
					html += networkElement + '(s),';
                    // 16/11/2012 MMT bz 30276 ajout gestion top-worst
					if (type == "graph" || type == "supervision") {
                        html += '<br />ordered for "' + labelElement + '"';
						html += ' in <select id="export_order" name="export_order">';
						html += '<option value="asc">ascending</option>';
						html += '<option value="desc" selected="selected">descending</option>';
						html += '</select>';
						html += ' order';
					} else if (type == "top-worst") {
                        html += '<br />ordered by Top/Worst "' + labelElement + '"';
                        html += '<input type="hidden" id="export_order" name="export_order" value="asc" /> ';
                    } else {
                        html += '<br />ordered by severity of alarm "' + labelElement + '" in descending order.';
                        html += '<input type="hidden" id="export_order" name="export_order" value="asc" /> ';
                    }
					html += '</label>';
					html += '</div>';
					html += '<div class="gis3DinfoSection">';
					html += '<input type="radio" id="export_choice_2" name="export_choice" value="2" style="float: left; margin: 0 3px 0 3px" />';
					html += '<label for="export_choice_2">';
					html += 'Export all elements anyway';
					html += '</label>';
					html += '</div>';
					html += '<div class="infoButtons">';
					html += '<input class="bouton" type="button" value="Ok" onclick="javascript:<?=$submitJs?>" />';
					html += '<input class="bouton" type="button" value="Cancel" onclick="window.close()" />';
					html += '</div>';
					html += '</div>';

					document.write(html);
					var width = 800;
					window.resizeTo(width, 310);
					var leftPosition = (screen.width) ? (screen.width-width) / 2 : 0;
					window.moveTo(leftPosition, window.screenY ? window.screenY : (window.screenTop - 200));
					window.focus();
				}
				else {
					if (http.responseText == 'no_result') {
						html+= "<div align='center'><img src='<?=NIVEAU_0?>gis/gis_icons/no_result_gis.png'/></div>";
					}
					else if (http.responseText == 'notNaMin') {
						html += "<?= __T("A_GIS_NOT_NA_MIN") ?>";
					}
					else {
                                                // 18/06/2013 NSE bz 34370 : annulation de la correction du bug 34043 (click here to download should be replaced by Click here to close)
						html+= "<a id=\"link_to_file\" name=\"link_to_file\" href=\"<?=NIVEAU_0?>php/force_download.php?filepath="+http.responseText+"\" class=\"texteGrisBold\"><p class='texteGrisBold'>Click here to download the GIS 3D file</p></a>";
						html+= "</fieldset></div></body></html>";
	
					}
					
					document.write(html);
				}
			}
		} else {	//Message affiché pendant le chargement

			document.getElementById("texteLoader").innerHTML = "Building GIS 3D File ...";
		}
	}
	// http.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

	http.open("GET", "<?=$link?>", true);//Appel du fichier externe

	http.send();

</script>
</div>
</body>
</html>