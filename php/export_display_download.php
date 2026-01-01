<?
/**
 * 16/02/2011 MMT DE Query Builder Limit
 * Creation du fichier a partir de download_topology.php
 *
 * 21/02/2011 NSE DE Query Builder :
 *      ajout d'un paramètre permettant la suppression du fichier après téléchargement
 *      ajout d'une action sur le onUnLoad pour gérer la suppression après fermeture de la fenêtre sans téléchargement : mode onlyDelete
 */

/**
 * affiche une barre de progression pendant la génération d'un fichier, une fois le fichier créé, la page offre le telechargement
 * ATTENTION cette fonction renvoit le contenu HTML de la page entière, il est prévu pour être affiché dans un popup de taille 250*30
 *
 * @param <type> $genFileUrl l'url qui declenche la génération du fichier, cette URL doit renvoyer le chemin complet du fichier un fois généré et non son contenu
 * @param <type> $windoTitle  Titre de la fenètre
 * @param <string> $progressMsg  Message à afficher pendant la generation du fichier
 * @param <boolean> $toDelete  Le fichier généré doit-il être supprimé (suppression gérée par le script $genFileUrl)
 */
function displayFileGenerationAndDownload($genFileUrl,$windoTitle,$progressMsg,$toDelete=FALSE){
?>
	<html>
		 <head>
			  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
			  <title><?=$title?></title>
			  <link rel='stylesheet' href='<?=NIVEAU_0?>css/global_interface.css' type='text/css'>
			  <script type='text/javascript' src='<?=NIVEAU_0?>js/prototype/prototype.js'> </script>
			  <script type="text/javascript">
					//  Fonctions de gestion du loader.
					var t_id = setInterval(animate,20);
					var pos=0;
					var dir=2;
					var len=0;

					function animate()
					{
						 var elem = $( 'progress' );
						 if( elem != null )
						 {
							  if (pos==0) len += dir;
							  if (len>32 || pos>79) pos += dir;
							  if (pos>79) len -= dir;
							  if (pos>79 && len==0) pos=0;
							  elem.style.left = pos;
							  elem.style.width = len;
						 }
					}

					function remove_loading()
					{
						 this.clearInterval( t_id );
						 var targelem = $( 'loader_container' );
						 targelem.style.display ='none';
						 targelem.style.visibility = 'hidden';
					}

					function getHTTPObject()
					{
						 var xmlhttp = null;

						 // maj 09/07/2009  - MPR : Correction du bug 9601 : Erreur JS IE6
						 if( window.XMLHttpRequest )
						 {
							  xmlhttp = new XMLHttpRequest(); // Firefox
						 }
						 else if( window.ActiveXObject )
						 {
							  // Internet Explorer (moderne ou archaique)
							  try
							  {
									xmlhttp = new ActiveXObject( "Msxml2.XMLHTTP" );
							  }
							  catch ( e )
							  {
									xmlhttp = new ActiveXObject( "Microsoft.XMLHTTP" );
							  }
						 }
						 return xmlhttp;
					}
			  </script>
			  <style type="text/css">
					.entete{
						 color: #fff;
						 background-color : #929292;
						 font : bold 9pt Verdana, Arial, sans-serif;
						 text-align: center;
					}
					#interface1 { z-index:1; }
					#loader_container {text-align:left; position:absolute; top:25%; width:75%; left:25%}
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
		 </head><?php // ?>
                 <body style="margin:0; text-align:center;" onunload="document.location.replace( '<?=NIVEAU_0 ?>php/force_download.php?<?=$toDelete?'onlyDelete=1&delete=1&':''?>filepath=' + http.responseText );">
			  <div id="loader_container">
					<div id="loader">
						 <div align="center" id="texteLoader"></div>
						 <div id="loader_bg"><div id="progress"> </div></div>
					</div>
			  </div>
			  <div id="link_to_file_container" align='center' style="display:none;width:100%;padding-bottom:10px">
					<fieldset style='width:90%'>
						 <legend>
							  &nbsp;<img src='<?=NIVEAU_0?>images/icones/download.png' alt="Download">&nbsp;
						 </legend>
						 <a id="link_to_file" name="link_to_file" href="#" class="texteGrisBold">
							  <p class='texteGrisBold'>Click here to download the CSV file</p>
						 </a>
					</fieldset>
			  </div>
			  <script type="text/javascript">
					// maj 11/03/2008, maxime : On fait appel au fichier build_file afin qu'un message s'affiche dès le début de la génération du fichier dans la pop-up
					var http = getHTTPObject();
					http.onreadystatechange = function()
					{
						 if ( http.readyState == 4 )
						 {
							  //Actions executées une fois le chargement fini
							  if( http.status != 200 )
							  {
									$( "texteLoader" ).update( "Error code " + http.status ); //Message si il se preoduit une erreur
							  }
							  else
							  {
									remove_loading(); // On met le contenu du fichier externe dans la div "content"
									$( "loader_container" ).remove();
									$( "link_to_file_container" ).show();
									$( document.body ).addClassName( "tabPrincipal" );
									$( "link_to_file" ).onclick = function()
									{
										 // 21/02/2011 NSE DE Query builder suppression du fichier téléchargé : paramètre delete
                                                                                 document.location.replace( '<?=NIVEAU_0 ?>php/force_download.php?<?=$toDelete?'delete=1&':''?>filepath=' + http.responseText );
										 $('link_to_file').update( '<p class=texteGrisBold onclick=window.close()><?=__T('U_CLOSE_POPUP')?></p>' );
									}
							  }
						 }
						 else
						 {
							  $( "texteLoader" ).update( "<?=$progressMsg?>" ); // Message affiché pendant le chargement
						 }
					}
					http.open("GET", "<?=$genFileUrl?>", true);//Appel du fichier externe
					http.send();
			  </script>
		 </body>
	</html>

<?php
} // end function
?>