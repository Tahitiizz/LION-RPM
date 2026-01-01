<?php
/**
 * 
 *  CB 5.2
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 */
?><?
/**
* Affichage du nombre de connexions par user
* 
* @author MPR
* @version CB4.1.0.0
* @package Application Statistics
* @since CB4.1.0.0
*
*	maj 03/11/2008	- maxime : On construit les graphes à partir de fichier xml généré
*					        Nouveau sélecteur
*
*	12/08/2009 GHX
*		- Correction du BZ 6652
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
<?
/*
*	@cb1300_iu2000b_pour_cb2000b@
*
*	19/07/2006 - Copyright Acurio
*
*	Composant de base version cb_1.3.0.0
*
*	Parser version iu_2.0.0.0
*/

/*

        Object : affichage des connections sur l'application

        2006-09-21        Creation

		- maj 03/11/2006, benoit : exclusion de l'utilisateur 'astellia_admin' des statistiques si l'utilisateur administrateur est différent de celui-ci

*/

        session_start();
        include_once(dirname(__FILE__) . "/../../../../php/environnement_liens.php");
        include_once(REP_PHYSIQUE_NIVEAU_0 . "php/database_connection.php");
        include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function.php");
        include_once(REP_PHYSIQUE_NIVEAU_0 . "intranet_top.php");
        $transparence_color=1;

		$log_number_result=400;
		//il faut utiliser le même mode que les alarmes pour pouvoir utiliser ce selecteur
		$_SESSION["url_alarme_courante"]=$_SERVER['PHP_SELF'];
		
		// Classe de connexion
		include_once(REP_PHYSIQUE_NIVEAU_0."class/DataBaseConnection.class.php");
		
		// Fichiers nécessaires à la construction du sélecteur
		include_once(REP_PHYSIQUE_NIVEAU_0."modules/conf.modules.inc");
		include_once(MOD_SELECTEUR."php/selecteur.class.php");
		include_once(MOD_SELECTEUR."php/SelecteurApplicationStats.class.php");
		
		
		// Fichiers nécessaires à la construction des graphes
		include_once(MOD_CHARTFROMXML . "class/graph.php");
		include_once(MOD_CHARTFROMXML . "class/SimpleXMLElement_Extended.php");
		include_once(MOD_CHARTFROMXML . "class/chartFromXML.php");

		?>


		<script type='text/javascript' src='<?php echo URL_CHARTFROMXML ?>js/prototype/prototype.js'> </script>
		<script type='text/javascript' src='<?php echo URL_CHARTFROMXML ?>js/prototype/window.js'> </script>
		<script type='text/javascript' src='<?php echo URL_CHARTFROMXML ?>js/prototype/scriptaculous.js'> </script>
		<script type='text/javascript' src='<?php echo URL_CHARTFROMXML ?>js/fenetres_volantes.js'></script>

		<link rel="stylesheet" type="text/css" href="<?php echo URL_CHARTFROMXML ?>css/global_interface.css" />

		<style type="text/css">
		.error {
			margin: 5px;
			border: 2px solid #990000;
			padding: 5px;
			background: #FFFF99;
			font-weight: bold;
			color: #990000;
		}
		.error .chartFromXML	{
		}
		</style>

<?

	// maj 03/11/2008 - maxime : Nouveau Sélecteur
	$selecteur = new SelecteurApplicationStats('connexion');
	$selecteur->getSelecteurFromArray($_POST['selecteur']);
	$selecteur_general_values = $selecteur->build();
	// $selecteur->debug();
	
?>
<html>
        <head>
                <title>Traffic Watch</title>
                <link rel="stylesheet" type="text/css" media="all" href="<?=NIVEAU_0?>css/global_interface.css">
</head>
<body>
<div align='center'>
<img src="<?=NIVEAU_0?>images/titres/traffic_watch.gif">
</div>
<?
// include_once(REP_PHYSIQUE_NIVEAU_0 . "php/selecteur.php");
include(REP_PHYSIQUE_NIVEAU_0 . "myadmin_user/intranet/php/affichage/user_activity_result.php");

?>
<table cellpadding="5" cellspacing="5" border="0" align="center">
    <!-- Contenu -->
    <tr>
       <td>
           <table cellpadding="4" cellspacing="2" border="0" class="tabPrincipal" align="center" width="640">
		   	   <tr>
			   		<td align='center'>
						<?=$graphe?>
					</td>
			   </tr>
               <tr>
                  <td class="texteGrisBold" align="center">
                     <fieldset>
                            <legend class="texteGrisBold">
                            &nbsp;
                            <img src="<?=NIVEAU_0?>images/icones/small_puce_fieldset.gif" />
                            &nbsp;
								Number of Connections (Last <?=$log_number_result?>)
                             &nbsp;
                            </legend>
                        <table cellspacing="2" cellpadding="2">
                           <tr>
                               <td align=center><font class=texteGrisBold>Name</font></td>
                               <td align=center><font class=texteGrisBold>Start Connection</font></td>
                               <td align=center><font class=texteGrisBold>End Connection</font></td>
                               <td align=center><font class=texteGrisBold>Duration (sec.)</font></td>
                           </tr>

                        	<?

							// 03/11/2006 - Modif. benoit : on exclut de la selection l'utilisateur "astellia_admin" si l'administrateur connecté n'est pas celui-ci
							// ($_SESSION['id_user'] != 1) ? $exclude_admin = "AND t0.id_user != 1" : $exclude_admin = "";

							// 03/06/2009 BBX : correction de la requête afin de ne pas afficher les utilisateurs astellia. BZ 9751
                                // 20/02/2012 NSE DE Astellia Portal Lot2 : suppression de user_prenom
							$query="SELECT username,start_connection,end_connection,duration_connection 
							FROM track_users t0, users
							WHERE t0.id_user = users.id_user 
							$query_part_id_user 
							$exclude_admin 
							order by t0.oid desc LIMIT $log_number_result";

                                                        // 07/11/2011 BBX BZ 24533 : remplacement new DataBaseConnection() par Database::getConnection()
							$database_connection = Database::getConnection();
							$res=$database_connection->getAll($query);

							$nb_result=count($res);
                        	for ($i=0; $i < $nb_result; $i++) {
             
                                ?>

                                <tr <?= ($i % 2 ? 'class="fondGrisClair"':'') ?> onMouseOver="javascript:this.className='fondOrange'" onMouseOut="javascript:this.className='<?= ($i % 2 ? 'fondGrisClair':'fondVide') ?>'">
                                        <td nowrap  align="left"  class=texteGris style="color:;"><?=$res[$i]["username"]?></td>
                                        <td nowrap  align="right"  class=texteGris style="color:;"><?=$res[$i]["start_connection"]?></td>
                                        <td nowrap  align="right"  class=texteGris style="color:;"><?=$res[$i]["end_connection"]?></td>
                                        <td nowrap  align="right"  class=texteGris style="color:;"><?=$res[$i]["duration_connection"]?></td>
                                </tr>

                        <? } ?>

                        </table>
                     </fieldset>
                  </td>
              </tr>
         </table>
       </td>
     </tr>
   </table>
 </body>
</html>
