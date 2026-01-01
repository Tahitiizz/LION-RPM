<?
/*
*	@cb50417
*
*	15/02/2011 NSE DE Query Builder : Ajout des boutons Export et des limites à 1000 résultats affichés
* 25/05/2011 NSE bz 22218 : présence de [na]|s|[ne] dans les exports -> initialisation de la généalogie pour qu'elle soit dispo au moment de l'export
*/
?><?
/*
*	@cb41000@
*
*	Composant de base version cb_4.1.0.00
* 
*	- 25/11/2008 - SLC - gestion multi-produit
* 
*/
?><?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?><?
/*
*	@cb20100_iu2030@
*
*	24/10/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.1.00
*
*	Parser version iu_2.0.3.0
*/
?><?
/*
	- maj 03 04 2006 : ajout de balises HTML afin de corriger le bug d'affichage du tableau entrainant un décalage de l'affichage
		des options du graph. (ligne 85)
 * - SPD1 le 04/01/2012: on desactive le bouton "save" car il faut desormais utiliser Query builder V2
*/
$ERROR= array();
$WARNING=array();

if(!$id_query2)
	$id_query2="";

$saved_data=$_POST;
$data = serialize($_POST);
$requete="";


// gestion multi-produit - 20/11/2008 - SLC
include_once('connect_to_product_database.php');
// include_once(REP_PHYSIQUE_NIVEAU_0 ."php/deploy_and_compute_functions.php");
	
	
if ($id_query2) {
	$sql="select * from report_builder_save where id_query=".$id_query2;
	$row = $db_prod->getrow($sql);
	$requete = $row["query"];
	$date = date("Y/m/d");
	$nbr_utilisation = $row["nbr_utilisation"]+1;
	$sql="update report_builder_save set date_derniere_utilisation='$date',nbr_utilisation='$nbr_utilisation' where id_query=".$id_query2 ;
	$db_prod->execute($sql);
}

if ($_POST["valider"]=="Display Result") {

	$builder_report=new builder_report_requete($family,$product);
	// si il y a une erreur on rappelle la page précédente en lui renvoyant les parametre necessaire au reaffichage de la page et des erreurs
	if($builder_report->flag_error){
		$saved_data["ERROR"]=$builder_report->error_message;
		?>
		<form method="post" id="error"  action="builder_report_onglet.php?nb_onglet=0&family=<?=$family?>&product=<?=$product?>" >
			<input type="hidden"   name="saved_data" id="saved_data'" value="<?=urlencode(serialize($saved_data))?>"/>
			<script >
				document.getElementById("error").submit();
			</script>
		</form>
		<?
	} else {
		// Si il 'y a pas d'erreur on execute la requete généré
		?>
		<table id="table_wait"></table>
		<script >
			//url='builder_report_waiting.php';
			//ouvrir_fenetre(url,'popup_waiting','no','no',260,100);	//	L'éxécution pouvant prendre un certain temp on affiche un popup "waiting"
		</script>
		<?
		echo "<table align=center>";
		foreach($WARNING as $war)							//	on affiche les warnings
			echo  "<tr><td><font class='texteGrisBold'> ".$war."</td></tr>";
		echo "</table>";
		echo "<br>";

		/*
		// On recherche l'id pour l'insertion du tableau html dans le caddy.
		$query=" select (case when max(id_contenu) IS NULL THEN 1 ELSE max(id_contenu)+1 END) as max_id from sys_contenu_buffer";
		$result=pg_query($database_connection,$query);
		$result_array = pg_fetch_array($result, 0);
		$id_obj_temp = $result_array["max_id"];
		*/
		?>
		<!--
		<table width=100%>
		<tr>
			<td align="center">
			 <img src="//$niveau0?>images/icones/caddy_icone_gf.gif" align="middle" border="0"  onMouseOver="popalt('Add to the  Caddy');style.cursor='hand';" onMouseOut="kill()" onClick="caddy_update('//$id_user?>','Builder report','builder_report','Builder report table','//$id_obj_temp?>','ND')">
			</td>
		</tr>
		<tr>
		-->

		<?
		$url="builder_report_query_save.php?family=$family&product=$product&id_query=".$id_query2;
		$data_builder_report=$data;
		$requete_builder_report=$requete;
		$formula_ids_builder_report=php2js($formula_ids);
		session_register("data_builder_report");
		session_register("requete_builder_report");
		session_register("formula_ids_builder_report");
        // 25/05/2011 NSE bz 22218 : avant de sérialiser, on mémorise la généalogie des NA dans l'objet (nécessaire pour l'export)
        $builder_report->setupNaGenealogy();
        // 15/02/2011 NSE DE Query Builder : on sérialize l'objet pour le transmettre à la génération de l'export
        $_SESSION["builder_report_session"] = serialize($builder_report);
		?>
		<table width=100%>
			<tr>
				<td align=center width=100%>
					<div style="font-family: Arial; background-color: rgb(255, 255, 136); border: 1px solid rgb(136, 68, 0); color: rgb(170, 0, 0); margin: 0pt 10px 20px;">Warning: Save is disabled, use the new 'Query builder' instead.</div>
					<input disabled="disabled" title="Save is disabled, use the new 'Query builder' instead." type="submit" class="bouton" onclick="javascript:ouvrir_fenetre('<?=$url?>','popup_waiting','no','no',250,30);" id="val0'"  name="save" value="Save query">
                                        <?/* 15/02/2011 NSE DE Query Builder : Ajout du bouton Export
                                         * identifiant= on peut passer 0 car utilisé uniquement dans builder report=$this->tableau_number*/?>
					<input type="submit" class="bouton" onclick="javascript:ouvrir_fenetre('<?=$niveau0?>php/export_excel_tab.php?u=<?=uniqid("")?>&identifiant=0&type=tableau','popup_waiting','no','no',250,30);" id="val0'"  name="export" value="Export">
				</td>
			</tr>
		</table>
<?
                // 15/02/2011 NSE DE Query Builder : Ajout du paramètre limit pour limiter à 1000 l'exécution de la requête
		$builder_report->executer_requete(get_sys_global_parameters('query_builder_nb_result_limit',1000));	// 	on execute la requete

		?>
		<script>
			document.getElementById('texteLoader').innerHTML = "Building display...<br><?=$builder_report->nombre_resultat_builder_report?> Results";
		</script>
		<?
                // 15/02/2011 NSE DE Query Builder : Ajout du paramètre limit pour limiter à 1000 l'affichage
		$builder_report->afficher_resultat(get_sys_global_parameters('query_builder_nb_result_limit',1000));
		?>
		<script >
		//win.close();
		//remove_loading();	//	on ferme le popup "win" qui est le nom de la fenetre ouverte dans gestion_fenetre.js
		</script>
	<?
	}
}
?>
<table width=100%>
	<tr>
		<td align=center width=100%>
			<input  disabled="disabled" title="Save is disabled, use the new 'Query builder' instead." type="submit" class="bouton" onclick="javascript:ouvrir_fenetre('<?=$url?>','popup_waiting','no','no',250,30);" id="val0'"  name="save" value="Save query"      >
                        <?/* 15/02/2011 NSE DE Query Builder : Ajout du bouton Export
                         * identifiant= on peut passer 0 car utilisé uniquement dans builder report=$this->tableau_number*/?>
                        <input  type="submit" class="bouton" onclick="javascript:ouvrir_fenetre('<?=$niveau0?>php/export_excel_tab.php?u=<?=uniqid("")?>&identifiant=0&type=tableau','popup_waiting','no','no',250,30);" id="val0'"  name="export" value="Export">
		</td>
	</tr>
</table>
