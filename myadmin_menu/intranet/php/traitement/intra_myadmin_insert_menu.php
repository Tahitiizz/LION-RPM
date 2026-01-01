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
session_start();
include_once($repertoire_physique_niveau0."php/environnement_liens.php");
include_once($repertoire_physique_niveau0."php/database_connection.php");
include_once($repertoire_physique_niveau0."php/environnement_nom_tables.php");

function creer_arborescence($nom_repertoire,$chemin_easyoptima) // crée l'arborescence pour un nouveau menu de niveau1
{
        $repertoire=$chemin_easyoptima.$nom_repertoire;
        $repertoire=strtolower($repertoire);//nom du repertoire en minuscules
        print $repertoire;	//affiche le nom du repertoire dans la fenetre popup (debug)
        $old_umask = umask(0000);
        $mode=0777;
        mkdir($repertoire,$mode);
        mkdir($repertoire."/intranet",$mode);
        mkdir($repertoire."/intranet/php",$mode);
        mkdir($repertoire."/intranet/php/affichage",$mode);
        mkdir($repertoire."/intranet/php/traitement",$mode);
		umask($old_umask);
}

function nouveau_id_menu($database_connection)        // renvoie un id_menu non utilisé pour la table menu_deroulant_intranet
{
        $query="SELECT (1+id_menu) AS id_menu_libre FROM menu_deroulant_intranet WHERE (id_menu+1) NOT IN (SELECT id_menu FROM menu_deroulant_intranet) ORDER BY id_menu asc LIMIT 1";
        $result=pg_query($database_connection,$query);
        $array_result=pg_fetch_array($result);
        $new_id_menu=$array_result["id_menu_libre"];
        return $new_id_menu;
}

if ($Submit)
{
 $sql="SELECT * FROM $nom_table_menu_deroulant WHERE id_menu='$idmenu'";
 $result=pg_query($database_connection,$sql);
 $row = pg_fetch_array($result,0);
 $niveau_dep=$row["niveau"];
 $position=$row["position"];
 $menuparent=$row["id_menu_parent"];

 if ($niveau==1) $menuparent=0;
 if (($niveau==2 && $niveau_dep==1)||($niveau==3 && $niveau_dep==2)) $menuparent=$idmenu;

//------------------------------------------------------------------------------

        if($niveau==1) // on crée le repertoire qui hebergera les fichiers du nouveau menu
        {
                $repertoire=str_replace(' ','_',$libelle);
                creer_arborescence($repertoire,$repertoire_physique_niveau0);
        }
        if($niveau==2||$niveau==3) // on crée par copie, un fichier qui sera appelé par le menu.
        {
			//on va chercher le repertoire dans la BDD
			if($niveau==2) $query="SELECT repertoire,libelle_menu FROM menu_deroulant_intranet WHERE id_menu=$idmenu";
			if($niveau==3) $query="SELECT repertoire,libelle_menu FROM menu_deroulant_intranet WHERE id_menu=(SELECT id_menu_parent FROM menu_deroulant_intranet WHERE id_menu=$idmenu)";
			$result=pg_query($query);
			$array_result=pg_fetch_array($result);
			$repertoire=$array_result["repertoire"];
			$repertoire=strtolower($repertoire);
			$repertoire_de_secours=str_replace(' ','_',$array_result["libelle_menu"]);
			$repertoire_de_secours=strtolower($repertoire_de_secours);
			if ($repertoire=="")
			{
			     $repertoire=$repertoire_de_secours;
			     $repertoire=strtolower($repertoire);
			}
	    	$filename=str_replace(' ','_',$libelle);
	        $filename=strtolower($filename);
	        if(!file_exists($niveau0.$repertoire))
	        {
	        	creer_arborescence($repertoire,$repertoire_physique_niveau0);
	        }
	        $new_page_filename=$repertoire_physique_niveau0."$repertoire/intranet/php/affichage/".$filename.".php";
	        $empty_new_page=$repertoire_physique_niveau0.$niveau0_vers_php."empty_page_model.php";
	        copy($empty_new_page,$new_page_filename);
        }

        //on determine le id_menu pour le nouvel element(on prend la plus petite valeur non utilisée)
        $new_id_menu=nouveau_id_menu($database_connection);

        // on update le champ position pour les sous-menu qui ont le même menu parent
        $query="UPDATE menu_deroulant_intranet SET position = position + 1 WHERE niveau='$niveau' and position >= '$position' and id_menu_parent='$menuparent'";
        pg_query($query) or die("Update Failed");

        //on insère les données dans la BDD
        //ATTENTION : Il faut d'abord virer la sequence qui determinait le id_menu puisque c'est nous qui le determinons maitenant
        //...ou alors, il faut refaire un update derrière la précédente requete pour fixer le id_menu
        $query="INSERT INTO menu_deroulant_intranet (id_menu,niveau,position,id_menu_parent,libelle_menu,lien_menu,complement_lien,liste_action,largeur,hauteur,repertoire) VALUES ('$new_id_menu','$niveau','$position','$menuparent','$libelle','$lien','$complement','$action','$largeur','$hauteur','$repertoire')";
        pg_query($query) or die("Insert Failed");

}
?>
<html>
<head>
        <title>Insert a Menu</title>

</head>

<body onload="javascript=window.opener.location='<?=$traitement_vers_affichage?>intra_myadmin_management_menu.php'">

 <script language="JavaScript1.2">
  self.close();
 </script>

</body>
</html>
