<?php
/**
 *  @version cb_51001
 *
 *  28/07/2010 OJT : Correction bz17078
 *  18/08/2010 NSE DE Firefox bz 16920 : loading ne disparaît pas
 *  20/08/2010 NSE DE Firefox bz 17383 : drag&drop non fonctionnel
 *  03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
 *  06/09/2010 NSE DE Firefox bz 17383 : Clear all non fonctionnel
 *  07/09/2010 NSE DE Firefox bz 17383 : modification d'une condition / d'un paramètre non fonctionnelle
 *  11/02/2011 NSE DE Query Builder :
 *      - ajout des limites jusqu'à 1 000 000 dans le select
 *      - ajout du bouton de suppression du paramètre Order By
 *      - modification de l'ordre des boutons et des labels
 *  21/02/2011 NSE DE Query builder : vérification s'il faut afficher le message d'avertissement après rechargement de la query
 */
?><?
/*
*	@cb41000@
*
*	Composant de base version cb_4.1.0.00
* 
*	- 25/11/2008 - SLC - gestion multi-produit
* 
*	- 22/09/2009 - MPR : Correction du bug 11481 - Le ORDER BY n'est pas conservé lorsque l'on revient sur EQUATION DEFINE
*				      Ajout de la boucle for afin de vérifier si le paramètre ORDER BY est présent dans chacun des éléments sélectionnés
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
?><?php
/*
*	@cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
*/

// fichier inclus par builder_report_onglet.php

?>
 <!--
 page gérant la création des requetes ( ainsi que les sauvegardes ou chargement de requetes)
 -->
<?php
$nbr_case_largeur = 5;
$nbr_case_hauteur = 5;
$nbr_case = $nbr_case_largeur * $nbr_case_hauteur-1;
// reaffiche une requete sauvegardé ou une requete qui vient juste d'être exécutée
function reafficher_equation_define()
{
    global $id_query2;

    if ($id_query2) {

        ?>
	<script>
	document.getElementById("id_query2").value=<?php echo php2js($id_query2)?>;		//on replace le numero de la query ayant généré la requete
	</script>
	<?php
    }
    $nbr = ($_POST["nbr_donnees"]);
    if ($nbr > 0) { // on reaffiche toutes les données séléctionnées et on aloue  les variable "hidden"
		for($i = 0;$i < $nbr;$i++) {
			$data = $_POST["donnees_hidden"][$i];
                        // 20/08/2010 NSE DE Firefox bz17383 : ajout de event en paramètre (null ici)
			?>
			<script LANGUAGE="JavaScript">
				HandleDrop(null,<?php echo php2js($i); ?>,"donnees",<?php echo php2js($data); ?>);
				//on simule le drag and drop pour reafficher_equation_define_equation_define grace au fonction js deja ecrite
			</script>
			<?php
		}
        $sort = $_POST["param_sort"];
        $Limit = $_POST["param_limit"];

        ?>
		<script LANGUAGE="JavaScript">
			Modif_sort(<?php echo php2js($sort); ?>);
			Modif_limit(<?php echo php2js($Limit); ?>);
		</script>
		<?php
    }
    // on remet en place les fonction de la meme maniere , par "simulation"  du drag& drop corespondant.
    $nbr = count($_POST["fonction"]);
    if ($nbr > 0) {
        for($i = 0;$i < $nbr;$i++) {
            if ($_POST["fonction"][$i] != "") {
                $fonction = $_POST["fonction"][$i];
                // 20/08/2010 NSE DE Firefox bz17383 : ajout de event en paramètre (null ici)
                ?>
			<script LANGUAGE="JavaScript">
				HandleDrop(null,<?php echo php2js($i); ?>,"donnees",<?php echo php2js($fonction); ?>);
			</script>
			<?php
            }
        }
    }

    $nbr = count($_POST["condition"]);
    // et on fini par les conditions , remisent en place de la meme maniere
    if ($nbr > 0) {
        if (($_POST["condition_hidden"][0] != "") && ($_POST["value_condition"][0] != "")) {
            for($i = 0;$i < $nbr;$i++) {
                $condition = $_POST["condition_hidden"][$i];
                $op_cond = $_POST["op_condition"][$i];
                $val_cond = $_POST["value_condition"][$i];
                // 20/08/2010 NSE DE Firefox bz17383 : ajout de event en paramètre (null ici)
                ?>
			<script LANGUAGE="JavaScript">
			Ajouter_condition(null,<?php echo php2js($i);
				?>,"condition",<?php echo php2js($condition);
				?>,<?php echo php2js($op_cond);
				?>,<?php echo php2js($val_cond);
				?>);
			</script>
		<?php
            
			}
	if ($nbr != 5) { ?>
		<script LANGUAGE="JavaScript">
			DelRow_condition();
		</script>
	<?php
	}
        }
    }
	
	// maj 12:08 22/09/2009 - MPR : Correction du bug 11481 - Le ORDER BY n'est pas conservé lorsque l'on revient sur EQUATION DEFINE
	// Ajout de la boucle for afin de vérifier si le paramètre ORDER BY est présent dans chacun des éléments sélectionnés
	if ($nbr > 0) {
		for($i = 0;$i < $nbr;$i++) {
			if ($_POST["param_order_hidden"][$i] != "") {
									
				$order = $_POST["param_order_hidden"];
                                 // 20/08/2010 NSE DE Firefox bz17383 : ajout de event en paramètre (null ici)
			?>
			<script LANGUAGE="JavaScript">
			HandleDrop(null,0,"order_by",<?php echo php2js($order);
				?>);
			</script>
			<?php
			}
		}
	}
        // 21/02/2011 NSE DE Query builder : on vérifie s'il faut afficher le message d'avertissement après rechargement de la query
        ?><script>
                change_limit(document.getElementById("param_limit"),<?=get_sys_global_parameters('query_builder_nb_result_limit',1000)?>)
	</script>
 <?php
}
// gere l'affichage de la popup d'erreur
function display_error($ERROR)
{
    global $hauteur;
    $hauteur = count($ERROR) * 20 + 100; // on calcul la ahuteur de la fenetre a affiche en fonction du nombre d'erreur
    global $ERROR;
    global $js_error;
    $js_error = php2js($ERROR);

    ?>
	<script >
	url='builder_report_error.php?ERROR='+<?php echo php2js($ERROR)?>;				//creation de l'url a appelée
	win=ouvrir_fenetre(url,'Error','no','no',520,<?php echo $hauteur?>);								//on ouvre la fenetre
	</script>
	<?php
}
// signifie que des erreurs etaient presentes dans la requete

$temp_POST = unserialize(urldecode($_POST["saved_data"]));
if ($temp_POST["ERROR"]!="") {
    $ERROR = $temp_POST["ERROR"];
    $hauteur = count($ERROR) * 20 + 100;
}

?>
<form method="post" id="form_requete" name="form_requete" action="builder_report_onglet.php?family=<?=$family?>&product=<?=$product?>" onSubmit="return Validation('<?php echo getDay(0);?>','<?php echo getWeek(0);?>')">
	<input type="hidden" id="nb_row_select" name="nb_row_select" value="1" />
	<input type="hidden" id="nb_row_condition" name="nb_row_condition" value="1" />
	<input type="hidden" id="family" name="family" value="<?=$family?>" />
	<input type="hidden" name="product" value="<?=$product?>" />

	<table width="400" border="0" cellspacing="0" cellpadding="0" >
	<?php
if ($ERROR) { // si des erreurs on etait généré par la requete on affiche un lien : "show errors" permetant des les afficher

        "<tr>";
    echo "<td colspan=\"2\" align=center>";
    echo "<A HREF=#    onClick=\" ontop('" . $ERROR . "','" . $hauteur . "'); \"><u><font class='texteGrisBold'> Show error(s)</u></A>";
    echo "</td>";
    echo "</tr>";
}

?>
    <tr>
      <td width="580"><table width="580" border="0" cellpadding="0" cellspacing="1">
          <tr>
            <td>
			<fieldset>
			<legend class="texteGrisBold">
				&nbsp;<img align="absmiddle" src="<?=$niveau0?>images/icones/puce_fieldset.gif" border="0">&nbsp;&nbsp;
				Select
				&nbsp;&nbsp;
			</legend>
				<table width="580" border="0" cellspacing="0" cellpadding="2">
                <?php	$num_case = 0;
                 // 20/08/2010 NSE DE Firefox bz17383 : on protège le champ pour éviter l'arrivée de parasites (2:SA:network::...) sous Firefox, puis on le débloque au bout de 100 milisecondes
                ?>
            <input id="nbr_donnees" type="hidden" name="nbr_donnees" value="0">
            <table width="580" border="0" cellspacing="0" cellpadding="2">
                <!--
                20/08/2010 NSE DE Firefox bz17383 : on protège le champ pour éviter
                l'arrivée de parasites sous Firefox, puis on le débloque au bout
                de 100 milisecondes -->
                <script>
                    var monid;
                    function fcenabled(){
                        document.getElementById(monid).disabled=false;
                    }
                </script>
		<?php
            $num_case = 0;
			for($cmpt_ligne = 0;$cmpt_ligne < $nbr_case_hauteur;$cmpt_ligne++) {
				echo "<tr><td>&nbsp</td>";
				for($cmpt_col = 0;$cmpt_col < $nbr_case_largeur;$cmpt_col++) {
					?>
					<td div align="center" width=20%>
	                    <input type="text" id="donnees_<?php echo $num_case;?>" OnDrop="HandleDrop(event,<?php echo $num_case;?>,'donnees');this.disabled=true;monid=this.id;setTimeout('fcenabled()',100);" name="donnees[]" class="br_caption" size=16 onFocus="saisie_disabled();" >
                    </td>
                    <!-- 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer -->
                    <td height="30" align="center"><img onclick="Erase(<?php echo $num_case;?>,<?php echo $nbr_case;?>);" src="<?=$niveau0?>images/icones/drop.gif" height="17" width="16" alt="Erase this case" border="0" onmouseover="style.cursor='pointer';" />

                        <input id="donnees_hidden_<?php echo $num_case;?>" type="hidden" name="donnees_hidden[]">
                        <input id="fonction_<?php echo $num_case;?>" type="hidden" name="fonction[]">
					</td>
					<?php
					$num_case++;
				}
				echo "</tr>";
			}

?>
		      </table>
			  </fieldset>
		</td>
	  </tr>

        </table></td>
    </tr>
  </table>
<br>

<table width="260"  border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td valign="top">
			<fieldset>
				<legend class="texteGrisBold">
					&nbsp;<img align="absmiddle" src="<?=$niveau0?>images/icones/puce_fieldset.gif" border="0">&nbsp;&nbsp;
					Condition
					&nbsp;&nbsp;
				</legend>
				<table width="240" border="0" cellpadding="1" cellspacing="0">
					<tr>
						<td>
							<table width="340" border="0" cellpadding="1" cellspacing="1">
								<tr>
									<td width="200" align="center">
										<strong><font size="2"><img src="<?=$niveau0?>images/icones/cube_multi.gif" height="18" width="20" alt="" border="0" />
										</font></strong>
									</td>
									<td width="40">&nbsp;</td>
									<td width="100" align="center">
										<strong><font size="2" face="Arial, Helvetica, sans-serif">Value</font></strong>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td>
							<!-- Affichage de la ligne de choix de condition -->
							<table width="340" border="0" cellpadding="1" cellspacing="0"  id="table_condition">
								<tr>
									<td width="200" align='center'>
                                        <?php // 20/08/2010 NSE DE Firefox bz17383 : ajout de event ?>
										<input type="text" id="condition_0" OnDrop="HandleDrop(event,0,'condition');this.disabled=true;monid=this.id;setTimeout('fcenabled()',100);" name="condition[]" class="br_caption" style='width:200px;' onFocus="saisie_disabled();" />
										<input id="condition_hidden_0" type="hidden" name="condition_hidden[]" />
									</td>
									<td width="40" align="center">
										<select name="op_condition[]" class="br_caption" id="op_condition_0">
											<option value="=">=</option>
											<option value="&gt;">&gt;</option>
											<option value="&gt;=">&gt;=</option>
											<option value="&lt;">&lt;</option>
											<option value="&lt;=">&lt;=</option>
											<option value="&lt;&gt;">&lt;&gt;</option>
										</select>
									</td>
									<td width="100" align="center">
										<input id="value_condition_0" class="br_caption" type="text" OnDrop="alert('Only figures can be captured');return false;" name="value_condition[]" size="12">
										<input type="hidden" name="bt_add_condition_1" value="1" />
									</td>
								</tr>
							</table>
							<!-- fin de l'Affichage de la ligne de choix de condition -->
						</td>
					</tr>
				</table>
			</fieldset>
		</td>
		<td width="40" align='center' style="padding:10px 4px 0 4px;">
                    <?php
                    // 20/08/2010 NSE DE Firefox bz17383 : ajout de event en paramètre
                    // 03/09/2010 MPR - BZ 17685 : Changement du curseur hand par pointer
                    ?>
			<img onclick="AddRow_condition(event,0);" src="<?=$niveau0?>images/icones/petit_plus.gif" height="20" width="20" alt="Add a line" border="0" onmouseover="style.cursor='pointer';" style="margin-bottom:6px;" />
			<img onclick="DelRow_condition(0);" src="<?=$niveau0?>images/icones/drop.gif" height="17" width="16" alt="Drop a line" border="0" onmouseover="style.cursor='pointer';" />
		</td>
	</tr>
</table>
<br>

  <table width="620" border="0" cellspacing="0" cellpadding="0"  >
    <tr>
      <td width="580">
	  <fieldset>
			<legend class="texteGrisBold">
				&nbsp;<img align="absmiddle" src="<?=$niveau0?>images/icones/puce_fieldset.gif" border="0">&nbsp;&nbsp;
				Parameters
				&nbsp;&nbsp;
			</legend>
	  <table width="580" border="0" cellpadding="0" cellspacing="1">
			 <tr><td>
              <table width="580" border="0" cellspacing="0" cellpadding="2">
                <tr align="center">
                  <td width="90" align="right" class="texteGrisBold">Order
                    By </td>
                  <!-- Merge 50503->51103 : augmentation 70 -> 150 -->
                  <td width="150">
 <!--        Affichage de l'order by -->
                    <?php  // 20/08/2010 NSE DE Firefox bz17383 : ajout de event en paramètre
                            // 11/02/2011 NSE DE Query Builder : ajout du bouton de suppression du paramètre Order By
                    ?>
 <input name="param_order" type="text" id="param_order" size="20" OnDrop="HandleDrop(event,0,'order_by');this.disabled=true;monid=this.id;setTimeout('fcenabled()',100);" class="br_caption" onFocus="saisie_disabled();" style="float:left;" /><img onclick="document.getElementById('param_order').value='';document.getElementById('param_order_hidden').value='';" src="<?=$niveau0?>images/icones/drop.gif" height="17" width="16" alt="Delete Order By parameter" border="0" onmouseover="style.cursor='pointer';"   />
                  	<input id="param_order_hidden" type="hidden" name="param_order_hidden" >
				  </td>
                  <td width="120"  >
                    <div align="left"><strong><font size="2" face="Arial, Helvetica, sans-serif">sort
                      <select name="param_sort" class="br_caption" id="param_sort">
                        <option value="DESC">DESC</option>
                        <option value="ASC">ASC</option>
                      </select>
                      </font></strong></div></td>
                  <td width="60" align="right"><strong><font size="2" face="Arial, Helvetica, sans-serif">Limit
                    </font></strong> </td>
                  <td width="80"><?php // 11/02/2011 NSE DE Query Builder : affichage du message de limitation des résultats affichés sur changement du select
                  // ajout des limites jusqu'à 1 000 000 ?>
                      <select name="param_limit" class="br_caption" id="param_limit" onchange="change_limit(this,<?=get_sys_global_parameters('query_builder_nb_result_limit',1000)?>)">
                             <option value="10">10</option>
                             <option value="20">20</option>
                             <option value="30">30</option>
                             <option value="40">40</option>
                             <option value="50">50</option>
                             <option value="75">75</option>
                             <option value="100">100</option>
                             <option value="150">150</option>
                             <option value="200">200</option>
                             <option value="300">300</option>
                             <option value="400">400</option>
                             <option value="500">500</option>
                             <option value="1000">1.000</option>
                             <option value="5000">5.000</option>
                             <option value="10000">10.000</option>
                             <option value="25000">25.000</option>
                             <option value="50000">50.000</option>
                             <option value="100000">100.000</option>
                             <option value="500000">500.000</option>
                             <option value="1000000">1.000.000</option>
                            </select>
                  </td>
                </tr>
              </table>
              <?php  // 15/02/2011 NSE DE Query Builder : message sur la limitation de l'affichage des résultats ?>
              <div id="alert_limit" style="width: 100%; text-align: center;color: red;font-family: Arial, Helvetica, sans-serif; display: none;"><?=__T('U_QUERY_BUILDER_ONLY_1000_RESULTS',get_sys_global_parameters('query_builder_nb_result_limit',1000))?></div>
            </td>
          </tr>
        </table>
	   </td>
    </tr>
  </table>
  <br>
  <?php  // 20/08/2010 NSE DE Firefox bz17383 : correction de l'id ?>
<input type="hidden"   name="show_onglet" id="show_onglet"  />
<input type="hidden"   name="onglet" id="onglet" value="0"/>
<input type="hidden"   name="id_query2" id="id_query2" value=""/>
<input type="hidden"   name="nbr_case" id="nbr_case" value="<?php echo $nbr_case;?>"/>
<p align="center">
<?php // NSE 11/02/2011 NSE DE Query Builder : Modification de l'ordre des boutons et des labels ?>
<input type="submit" class="bouton"  id="val1" name="valider" value="Display Result" onClick="val(1)" />
<input type="reset" class="bouton"  name="clear" value="Clear Query" onClick="document.getElementById('nbr_donnees').value=0;disable_drop();"/>
<?php
// if($_POST["id_query2"]||$id_query)
// {
// 18/08/2010 NSE DE Firefox bz 16920 : correction de id (suppression de ' superflux)
?>
<input type="submit" class="bouton"  id="drop" name="drop" value="Delete Query" onClick="drop_query()" />
<?php
// }
// 06/09/2010 NSE DE Firefox bz 17383 : remise à zéro du compteur nbr_donnees
?></p>
</form>
	<script >
	document.getElementById("drop").disabled=true;
	</script>
<?php
if ($id_query2) {

    ?>
	<script >
	document.getElementById("drop").disabled=false;
	</script>
	<?php
}
if (($drop == "Drop")) { // //si l'on a validé en cliquand sur display result
	$query = "delete from report_builder_save where id_query=" . $id_query2;
	$result = $db_prod->execute($query);
	?>

	<script >
		document.getElementById("id_query2").value="";
		document.getElementById("drop").disabled=true;
		parent.frames["contenu_database"].location.reload();
	</script>

<?php
}

if ($id_query) { // on vient de charger une requete sauvegardée
?>
	<script>
		document.getElementById("drop").disabled=false;
	</script>

<?php
	include_once($repertoire_physique_niveau0 . "php/database_connection.php");

	// on va recupérer les données de la requete  (seule l' ID est connue)
	$query	= "select requete, family from report_builder_save where id_query =".$id_query;
	$row		= $db_prod->getrow($query);
	$data	= $row["requete"];
	// 01/06/2006 - Modif. BA : recherche de listes dans la chaine de données et, le cas echeant, modification de la taille stockée pour la chaine liste avec sa vraie valeur (la valeur indiquant la taille est par defaut erronée car elle tient compte des antislashs nécessaires à l'intégration de la chaine dans la BD)
	// Selection des listes d'agregation disponibles pour la famille
	$sql_liste	= "SELECT cell_liste FROM my_network_agregation WHERE family='" . $row["family"] . "'";
	$req_liste	= $db_prod->getall($sql_liste);
	
	if ($req_liste) {
		foreach ($req_liste as $row) {
			// Si la liste fait partie de la chaine '$data', on effectue le traitement de "recomptage" des caracteres correspondant à la liste
			if (!(strpos($data, $row[0]) === false)) {
			// On coupe '$data' en 2 parties à partir de la position de la liste
			$data_liste_deb = substr($data, 0, strpos($data, $row[0]));
			$data_liste_fin = substr($data, strpos($data, $row[0]));
			// On explose la premiere partie de la chaine '$data' pour trouver la position du nombre de caractères stockée pour la liste
			$tab_tmp = explode(':', $data_liste_deb);
			$idx_nb_car = count($tab_tmp) - 5;
			// On va parcourir la chaine '$data' explosée, compter le réel nombre de caractères de la liste et remplacer l'ancienne valeur par le nouveau nombre de caractères effectivement comptabilisé
			$tab_data_all = explode(':', $data);
			
			for ($i = 0; $i < count($tab_data_all); $i++)
				if (($tab_data_all[$i] == $tab_tmp[$idx_nb_car]) && ($tab_data_all[$i + 4] == $row[0]))
					$tab_data_all[$i] = strlen(substr($tab_data_all[$i + 1], 1) . ":" . $tab_data_all[$i + 2] . ":" . $tab_data_all[$i + 3] . ":" . $tab_data_all[$i + 4] . ":");

			// Enfin, on recompose la chaine de données
			$data = implode(':', $tab_data_all);
		}
	}
}
	
// 07/06/2011 BBX -PARTITIONING-
// Gestion des échappements
$tmpdata = unserialize($data);
if(!$tmpdata)
{
    // Si la désrialisation échoue, il faut doubler les "\"
    $newdata = str_replace("\'","\\\'",$data);
    $tmpdata = unserialize($newdata);
    // Une fois la chaine désérialisée, il faut remettre des simples "\"
    $newCondition = array();
    foreach($tmpdata['condition_hidden'] as $condition)
    {
        $condition = str_replace("\\\'","\'",$condition);
        $newCondition[] = $condition;
    }
    $tmpdata['condition_hidden'] = $newCondition;
}
$data = $tmpdata;
// FIN MODIF PARTITIONING
	
$_POST = $data; //on met toute la requete dans la variable $_POST pour simplifier le reaffichage
$_POST["valider"] = "";
	
	?>
	<script >
	document.getElementById("id_query2").value=<?php echo php2js($id_query)?>;
	document.getElementById("form_requete").action="builder_report_onglet.php?nb_onglet=0";
	</script>
	<?php
	reafficher_equation_define(); //on reaffiche la page...
}

if ($saved_data) { // la page a été rappelée apres la détection d'erreur  lors de la creation de la requete,saved_data contient toutes les information pour le reaffichage des données + les information sur les erreurs constatées
	$_POST = unserialize(urldecode($_POST["saved_data"]));
	$ERROR=$_POST["ERROR"];
	$_POST["valider"] = "";
	display_error($ERROR);
	reafficher_equation_define();
}
	
if ($valider == "Display Result")		//si l'on a validé en cliquand sur display result
	reafficher_equation_define();		//on se contente de réafficher les infos sur la page
	
?>
