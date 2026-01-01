<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?/************************************************
Affiche toutes les connections sous forme d'onglet
et appelle les 3 fichiers qui permettent de gérer la table de correspondance :
tables des compteurs OMC
table de correspondance
table des compteurs easyoptima
***************************************************/
session_start();
include_once("/home/roaming_114/check_session.php");
include_once($repertoire_physique_niveau0."php/environnement_liens.php");
include_once($repertoire_physique_niveau0."php/database_connection.php");
include_once($repertoire_physique_niveau0."php/environnement_donnees.php");
include_once($repertoire_physique_niveau0."php/menu_contextuel.php");
include_once($repertoire_physique_niveau0."intranet_top.php");
$lien_css=$path_skin."easyopt.css";
?>
<html>
<head>
<title>Raw Data Table</title>
<script language="JavaScript1.2" src="<?=$niveau0?>js/fonctions_dreamweaver.js"></script>
<script language="JavaScript1.2" src="<?=$niveau0?>js/menu_contextuel.js"></script>
<script language="JavaScript1.2" src="<?=$niveau0?>js/myadmin_mapping_raw_counters.js"></script>
<link rel="stylesheet" href="<?=$lien_css?>" type="text/css">
</head>
<table width="100%" border="0" cellspacing="0" cellpadding="3">
  <tr valign="top" height="20">
    <td colspan="3">
       <?
         //selectionne toutes les connections
         $query="SELECT id_ligne, edw_group_table FROM sys_definition_group_table WHERE visible=1 order by edw_group_table ASC";
         $result=pg_query($database_connection,$query);
         $nombre_group_table=pg_num_rows($result);
       ?>
       <table id="group_table_list" border="0" cellspacing="0" cellspacing="0" cellpadding="0" align="center">
         <tr valign="top">
          <?
     $i=0;
     //affcihe les connections sous forme d'onglet
     for ($j=0;$j<$nombre_group_table;$j++)
         {
          $row=pg_fetch_array($result,$j);
         ?>
          <td bgcolor="<?=$couleur_fond_global?>">
             <img align="absmiddle" src="<?=$path_skin?>coin_hg.gif">
             <a href="javascript:change_color('<?=$i?>','<?=$row["id_ligne"]?>')">
             <font id="onglet<?=$i?>" class="font_12_b"><?=$row["edw_group_table"]?></font>
             </a>
             <img align="absmiddle" src="<?=$path_skin?>coin_hd.gif">
          </td>
          <td width="20">
             &nbsp;
          </td>
        <?
         $i=$i+2;
         }
        ?>
        </tr>
       </table>
       <table width="100%" cellspacing="0">
         <tr>
           <td colspan="3" bgcolor="<?=$couleur_fond_global?>">
           </td>
         </tr>
       </table>
    </td>
  </tr>
  <tr valign="top">
    <td width="25%">
       <iframe name="provider" width="100%" height="470" frameborder="0" src="mapping_raw_counters_external.php" scrolling="auto" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
       </iframe>
    </td>
    <td width="50%">
       <iframe name="table_correspondance" width="100%" height="470" frameborder="0" src="mapping_raw_counters_correspondance_table.php" scrolling="auto" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
       </iframe>
    </td>
      <td width="25%">
       <iframe name="easyoptima_counter" width="100%" height="470" frameborder="0" src="mapping_raw_counters_internal.php" scrolling="auto" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
       </iframe>
    </td>
  </tr>
</table>
</body>
</html>
