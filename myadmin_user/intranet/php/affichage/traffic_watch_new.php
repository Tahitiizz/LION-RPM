<?php
/**
 * 
 *  CB 5.2
 * 
 * 23/02/2012 NSE DE Astellia Portal Lot2
 */
?><?
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

        Object : affichage du traffic sur l'application

        2006-02-16        Stephane        Creation

        - maj 23 02 2006 christophe : modif sur la gnérations des graph, modif images + css.
        - maj 08 03 2006 christophe : on affiche l'historique sur 6 mois et non 12.
*/

        session_start();
        include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
        include_once($repertoire_physique_niveau0 . "php/database_connection.php");
        include_once($repertoire_physique_niveau0 . "php/environnement_donnees.php");
        include_once($repertoire_physique_niveau0 . "php/environnement_nom_tables.php");
        include_once($repertoire_physique_niveau0 . "php/edw_function.php");
        include_once($repertoire_physique_niveau0 . "intranet_top.php");

        global $database_connection, $niveau0;
         $transparence_color=1;
        include_once($repertoire_physique_niveau0 . "graphe/jpgraph.php");
        include_once($repertoire_physique_niveau0 . "graphe/jpgraph_bar.php");


        // on recupere les donnees envoyées
        $day        = $_GET['day'];
        $period        = $_GET['period'];

        // a la demande de Cyrille, mais la je vois pas ce que ça fait -- stephane
        $general_values  = array();

?>
<html>
        <head>
                <title>Traffic Watch</title>
                <link rel="stylesheet" type="text/css" media="all" href="<?=$niveau0?>css/global_interface.css">
                <script src="<?=$niveau0?>js/fenetres_volantes.js"></script>
                <script src="<?=$niveau0?>js/gestion_fenetre.js"></script>
                <script src="<?=$niveau0?>js/fenetres_volantes.js"></script>
                <script src="<?=$niveau0?>js/fonctions_dreamweaver.js"></script>
                <script type='text/javascript' src='<?=$niveau0?>js/toggle_functions.js'></script>

<script>

function update_hour(obj, hour){
        toggle('div_clock');
        var hour_to_display = (hour < 10) ? "0"+hour: hour;
        document.getElementById(obj).value = hour_to_display+":00 ";
}

function change_clock(){
        if(document.getElementById('am').checked == true){
                document.getElementById('am_clock').style.display = '';
                document.getElementById('pm_clock').style.display = 'none';
        } else {
                document.getElementById('am_clock').style.display = 'none';
                document.getElementById('pm_clock').style.display = '';
        }
}

</script>


</head>
<body>

<table cellpadding="5" cellspacing="5" border="0" align="center">
        <!-- Image Titre -->
        <tr>
                <td align="center"><img src="<?=$niveau0?>images/titres/traffic_watch.gif"/></td>
        </tr>
        <!-- Contenu -->
                        <tr>
                                <td>
                                        <table cellpadding="4" cellspacing="2" border="0" class="tabPrincipal" align="center" width="640">
                                                <tr>
                                                        <td class="texteGrisBold" align="center">


                <?

                // on affiche le selecteur
                include('traffic_watch_selecteur.php');

                if (!$day) $day = date('d-m-Y');

                // on va faire la premiere requête sur les 30 derniers jours
                $day_end        = substr($day,6,4).substr($day,3,2).substr($day,0,2);
                // on remonte de 30 jours, en créant le tableau $graph_data
                $graph_data        = array();
                for ($i = 30;$i >= 0; $i--) {
                        $graph_data[date('d-m-y',mktime(1,0,0,substr($day_end,4,2),substr($day_end,6,2)-$i,substr($day_end,0,4)))] = 0;
                }
                $day_30        = date('Ymd',mktime(1,0,0,substr($day_end,4,2),substr($day_end,6,2)-30,substr($day_end,0,4)));

                $query = "
                        SELECT count(*) as nb,access_day
                        FROM track_pages
                        WHERE access_day >= '$day_30'
                                AND access_day <= '$day_end'
                        GROUP BY access_day
                        ORDER BY access_day
                        ";
                $result                = pg_query($database_connection,$query);
                $nb_result        = pg_num_rows($result);

                if ($nb_result) {

                        for ($i=0; $i < $nb_result; $i++) {
                                $row = pg_fetch_array($result,$i);
                                $graph_data[substr($row['access_day'],6,2).'-'.substr($row['access_day'],4,2).'-'.substr($row['access_day'],2,2)] = $row['nb'];
                        }

                        $data_x = array();
                        $data_y = array();
                        $i = 0;
                        foreach ($graph_data as $key => $val) {
                                $i++;
                                if ($i % 2) {
                                        $data_x[] = '';
                                } else {
                                        $data_x[] = $key;
                                }
                                $data_y[] = $val;
                        }


                     $graph = new Graph(580,150,"auto");
                                                $graph->SetScale("textlin");

                                                // Adjust the margin a bit to make more room for titles
                                                $graph->img->SetMargin(50,50,20,50);
                                                $graph->SetMarginColor('white');

                                                // Create a bar pot
                                                $bplot = new BarPlot($data_y);
                                                // $bplot->value->Show();
                                                // $bplot->value->SetFormat('%d');
                                                $bplot->SetColor("blue");
                                                $color="blue@0.5";
                                                $bplot->SetFillColor($color);

                                                $bplot2 = new BarPlot($data_y);
                                                $color="blue@0.5";
                                                $bplot2->SetFillColor($color);

                                                $graph->Add($bplot);
                                                $graph->xaxis->SetTickLabels($data_x);
                                                $graph->xaxis->SetFont(FF_ARIAL,FS_NORMAL,7);
                                                $graph->xaxis->SetLabelAngle(60);
                                                $color_font="#444444";
                                                // Setup the titles
                                                $graph->title->Set("Pages viewed over the last 30 days");
                                                $graph->title->SetFont(FF_FONT1,FS_BOLD);
                                                $graph->title->SetColor("$color_font");

                                                $graph->xaxis->title->Set('');
                                                $graph->xaxis->title->SetFont(FF_FONT1,FS_NORMAL);
                                                $graph->xaxis->SetColor("$color_font");

                                                $graph->SetFrame(false);
                                                $graph->ygrid->SetFill(true,'#EFEFEF@0.5','blue@0.98');

                                                $graph->yaxis->SetTitleMargin(35);
                                                $graph->yaxis->title->Set("Total");
                                                $graph->yaxis->title->SetColor("#FF0000");
                                                $graph->yaxis->title->SetFont(FF_FONT1,FS_NORMAL);
                                                $graph->yaxis->scale->SetGrace(10);
                                                $graph->yaxis->SetColor("$color_font");

                                                // Display the graph
                                                $random=rand(1,1000000);
                                                $nom_graph="traffic_watch_30d_$random.png";
                                                $graph->Stroke($repertoire_physique_niveau0 . "png_file/$nom_graph");

                                                ?>
                                                <img src="<?=$niveau0?>png_file/<?=$nom_graph?>" />
                                                <?
                            } else { ?>

                        <p><strong>No visitor found from <?= $day_30 ?> to <?= $day_end ?>.</strong></p>

                <?        }


                // on fait la requete sur les 6 derniers mois

                // on remonte de 6 mois, en créant le tableau $graph_data
                unset($graph_data);
                $graph_data        = array();
                for ($i = 6;$i >= 0; $i--) {
                        $graph_data[date('m-Y',mktime(1,0,0,substr($day_end,4,2)-$i,substr($day_end,6,2),substr($day_end,0,4)))] = 0;
                }
                $month_6 = date('Ym',mktime(1,0,0,substr($day_end,4,2)-6,substr($day_end,6,2),substr($day_end,0,4)));

                $query = "
                        SELECT count(*) as nb,SUBSTR(access_day,1,6) as access_month
                        FROM track_pages
                        WHERE SUBSTR(access_day,1,6) >= '$month_6'
                                AND SUBSTR(access_day,1,6) <= '".substr($day_end,0,6)."'
                        GROUP BY access_month
                        ORDER BY access_month
                        ";
                // echo "<pre>$query</pre>";
                $result                = pg_query($database_connection,$query);
                $nb_result        = pg_num_rows($result);

                if ($nb_result) {

                        for ($i=0; $i < $nb_result; $i++) {
                                $row = pg_fetch_array($result,$i);
                                $graph_data[substr($row['access_month'],4,2).'-'.substr($row['access_month'],0,4)] = $row['nb'];
                        }

                        $data_x = array();
                        $data_y = array();
                        foreach ($graph_data as $key => $val) {
                                $data_x[] = $key;
                                $data_y[] = $val;
                        }

                     $graph = new Graph(580,150,"auto");
                                                $graph->SetScale("textlin");

                                                // Adjust the margin a bit to make more room for titles
                                                $graph->img->SetMargin(50,50,20,50);
                                                $graph->SetMarginColor('white');

                                                // Create a bar pot
                                                $bplot = new BarPlot($data_y);
                                                // $bplot->value->Show();
                                                // $bplot->value->SetFormat('%d');
                                                $bplot->SetColor("blue");
                                                $color="blue@0.5";
                                                $bplot->SetFillColor($color);

                                                $bplot2 = new BarPlot($data_y);
                                                $color="blue@0.5";
                                                $bplot2->SetFillColor($color);

                                                $graph->Add($bplot);
                                                $graph->xaxis->SetTickLabels($data_x);
                                                $graph->xaxis->SetFont(FF_ARIAL,FS_NORMAL,7);
                                                $graph->xaxis->SetLabelAngle(60);
                                                $color_font="#444444";
                                                // Setup the titles
                                                $graph->title->Set("Pages viewed over the last 30 days");
                                                $graph->title->SetFont(FF_FONT1,FS_BOLD);
                                                $graph->title->SetColor("$color_font");

                                                $graph->xaxis->title->Set('');
                                                $graph->xaxis->title->SetFont(FF_FONT1,FS_NORMAL);
                                                $graph->xaxis->SetColor("$color_font");

                                                $graph->SetFrame(false);
                                                $graph->ygrid->SetFill(true,'#EFEFEF@0.5','blue@0.98');

                                                $graph->yaxis->SetTitleMargin(35);
                                                $graph->yaxis->title->Set("Total");
                                                $graph->yaxis->title->SetColor("#FF0000");
                                                $graph->yaxis->title->SetFont(FF_FONT1,FS_NORMAL);
                                                $graph->yaxis->scale->SetGrace(10);
                                                $graph->yaxis->SetColor("$color_font");

                                                // Display the graph
                                                $random=rand(1,1000000);
                                                $nom_graph="traffic_watch_30d_$random.png";
                                                $graph->Stroke($repertoire_physique_niveau0 . "png_file/$nom_graph");

                                                ?>
                                                <img src="<?=$niveau0?>png_file/<?=$nom_graph?>" />

                <?        } else { ?>

                        <p><strong>No visitor found from <?= $month_12 ?> to <?= substr($day_end,0,6) ?>.</strong></p>

        <?        }

                // on affiche la liste des visiteurs de la journée
        // 20/02/2012 NSE DE Astellia Portal Lot2 : suppression de user_prenom
                $query = "
                        SELECT count(*) as nb,users.username
                        FROM track_pages, users
                        WHERE track_pages.access_day = '$day_end'
                                AND track_pages.id_user = users.id_user
                        GROUP BY users.username
                        ORDER BY users.username
                        ";
                //echo "<pre>$query</pre>";
                $result                = pg_query($database_connection,$query);
                $nb_result        = pg_num_rows($result);

                if ($nb_result) {
                        ?>

                        <fieldset>
                                <legend class="texteGrisBold">
                                &nbsp;
                                <img src="<?=$niveau0?>images/icones/small_puce_fieldset.gif" />
                                &nbsp;
                                Page views per user on <?= $day ?>
                                &nbsp;
                                </legend>
                        <table cellspacing="2" cellpadding="2">
                                <tr>
                                        <td align=center><font class=texteGrisBold>Name</font></td>
                                        <td align=center><font class=texteGrisBold>Page views</font></td>
                                </tr>

                        <?
                        for ($i=0; $i < $nb_result; $i++) {
                                $row = pg_fetch_array($result,$i);
                                ?>

                                <tr <?= ($i % 2 ? 'class="fondGrisClair"':'') ?> onMouseOver="javascript:this.className='fondOrange'" onMouseOut="javascript:this.className='<?= ($i % 2 ? 'fondGrisClair':'fondVide') ?>'">
                                        <td nowrap  align="left"  class=texteGris style="color:;"><?=$row["username"]?></td>
                                        <td nowrap  align="right"  class=texteGris style="color:;"><?=$row["nb"]?></td>
                                </tr>

                        <? } ?>

                        </table>
                        </fieldset>

                <?        } else { ?>

                        <p><strong>No visitor found from <?= $day ?>.</strong></p>

                <?        } ?>





                                                        </td>
                                                </tr>
                                        </table>
                                </td>
                        </tr>
                </table>
        </body>
</html>
