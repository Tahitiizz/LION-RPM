<?
/*
*	@cb40000@
*
*	14/11/2007 - Copyright Acurio
*
*	Composant de base version cb_4.0.0.00
*
	- maj 15/01/2008, benoit : modification de l'appel à la classe 'Excel_HTML_Table()'
	- maj 15/01/2008, benoit : remise en forme du lien de telechargement du fichier Excel	
	- maj 11/03/2008, maxime : On fait appel au fichier build_file afin qu'un message s'affiche dès le début de la génération du fichier dans la pop-up
*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<? session_start(); ?>
<?
/*
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?
/*
	- maj 16/04/2007 Gwénaël
		>> suite à un problème lors de l'ouverture de la popup qui propose le téléchargement du fichier Excel, on met un lien direct sur ce fichier (TEMPORAIRE)
		>> redimensionne la popup et la place au milieu de l'écran
		>> la fenêtre se ferme 2 secondes après avoir cliqué sur le lien
 */
?>
<?

	// INCLUDES.
	include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");
	include_once($repertoire_physique_niveau0 . "php/environnement_nom_tables.php");
	include_once($repertoire_physique_niveau0 . "php/environnement_donnees.php");
	include_once($repertoire_physique_niveau0 . "php/environnement_graphe.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function.php");
	include_once($repertoire_physique_niveau0 . "php/edw_function_family.php");
	include_once($repertoire_physique_niveau0 . "php/postgres_functions.php");
	include_once($repertoire_physique_niveau0 . "class/alarmCreateHTML.class.php");
	include_once($repertoire_physique_niveau0 . "class/htmlTableExcel.class.php");

	global $database_connection;


?>
<?
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<title>Excel Export</title>
	<link rel="stylesheet" href="<?=$niveau0?>css/global_interface.css" type="text/css">
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
	
	function getHTTPObject() {
		var xmlhttp;

		if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
			try {
				xmlhttp = new XMLHttpRequest();
			} catch (e) {
				xmlhttp = false;
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
#loader_container {text-align:left; position:absolute; top:25%; width:100%; left:40%}
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




<script>
	// maj 11/03/2008, maxime : On fait appel au fichier build_file afin qu'un message s'affiche dès le début de la génération du fichier dans la pop-up
	var http = getHTTPObject();
	
	// var xhr2 = new_xhr();//On crée un nouvel objet XMLHttpRequest
	http.onreadystatechange = function(){
		if ( http.readyState == 4 ){ 	//Actions executées une fois le chargement fini
			if(http.status  != 200){	//Message si il se preoduit une erreur
				document.getElementById("download_file").innerHTML ="Error code " + xhr2.status;
			} else {
				//On met le contenu du fichier externe dans la div "content"
				remove_loading();

				var html = "";
				
				html+= "<body><head><title>Excel File</title>";
				html+= "<link rel='stylesheet' href='<?=$niveau0?>css/global_interface.css' type='text/css'></head>";
				html+= "<body class='tabPrincipal'><div align='center' style ='width:100%;padding-bottom:10px'>";
				html+= "<fieldset style='width:90%'>";
				html+= "<legend>&nbsp;<img src='<?=NIVEAU_0?>images/icones/download.png'>&nbsp;</legend>";
				
				html+= "<a id=\"link_to_file\" name=\"link_to_file\" href='"+http.responseText+"' onclick='setTimeout('window.close()', 2000);\" class=\"texteGrisBold\"><p class='texteGrisBold'><?=(strpos(__T('U_EXCEL_FILE_DOWNLOAD'), "Undefined") === false) ? __T('U_EXCEL_FILE_DOWNLOAD') : "Click here to download the Excel file";?></p></a>";
				html+= "</fieldset></div></body></html>";
				
				document.write(html);
			}
		} else {	//Message affiché pendant le chargement

			document.getElementById("texteLoader").innerHTML = "Building Excel File ...";
		}
	}
	// http.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	http.open("GET", "build_file.php?type_file=xls&mode=<?=$_GET['mode']?>&sous_mode=<?=$_GET['sous_mode']?>", true);//Appel du fichier externe

	http.send();
</script>
</body>
<?/*?>
<body topmargin="0" leftmargin="0">
	<div align="center" class="tabPrincipal" style="width:100%;height:100%">
		<fieldset style="width:90%;padding-top:20px">
			<legend>&nbsp;<img src="<?=$niveau0?>images/icones/download.png">&nbsp;</legend>
			<a id="link_to_file" name="link_to_file" href="<?=$file_link?>" onclick="setTimeout('window.close()', 2000);" class="texteGrisBold"><?=$download_label?></a>
		</fieldset>
	</div>
</body>
<?*/?>
</html>