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
//BUILDER_REPORT et MY REPORTS
$limite_affichage_resultat=10000;
$message_erreur_requete="<center><b>An error has occured.<br>Please check your query.</b></center><script>top.contenu_equation_sql.formulaire_sql.sauver.disabled=true;</script>";
 /* description des bases et des queries */
 $label_level0[0]="QUERIES";
 $label_query_prive="Private";
 $label_query_public="Public";
 $nom_table_query="forum_queries";
 $label_level0[1]="EASYOPTIMA DATABASE";
 $label_level0[2]="FORMULAS";
 $label_formula_prive="Private";
 $label_formula_public="Public";
 $nom_table_formula="forum_formula";
 /*uniquement pour MY REPORTS*/
 $label_queries_level0[0]="QUERIES";
//MYDAMIN - GESTION DES USERS
$couleur_ligne_pair="#EEEEEE";
$couleur_ligne_impair="#FFFFFF";
/* Nombre de ligne affiché */
$nombre_ligne=11;
/* Nombre de position pour le compteur du moteur de recherche */
$nombre_position=8; /* Ce chiffre ne peut être qu'un chiffre pair*/
$couleur_chiffre_en_cours="#0000FF";
$couleur_autre_chiffre="#444444";
/* Structure des autorisations : le premier 1=enregistrer. 2ème=modifier. 3ème=supprimer. 4ème=rechercher .Remplacer par un zéro pour suprimer les autorisations */
$autorisation="1111";
//BSS
$largeur_graphe_bss_adjancies=450;
$rapport_hauteur_largeur_graphe_bss_adjancies=1.6;
$hauteur_graphe_bss_adjancies=$largeur_graphe_bss_adjancies/$rapport_hauteur_largeur_graphe_bss_adjancies;
$nombre_adjacencies_max=8;
//SHARING
$nb_visu_ref=5;
$path_fichier_ref = "fichier/ref";
//FAVORIS
$nombre_favoris_maxi=8;
//HOMEPAGE
//couleur de fond des ligne du tableau des TT sur la homepage
$tt_line1_color_default="#D3E1EB";
$tt_line2_color_default="#E2E2E2";
$nombre_tt_par_page=5;
$nombre_wcl_default=10;
//TABLE DE CORRESPONDANCE
$nombre_lignes_table_correspondance=13; //nombre de lignes affichées par défaut dans la table de correspondance
$nombre_lignes_table_correspondance_max=100;
//formule SQL utilisée pour le retrieve des données
$type_sql_formule[0]="SUM";
$type_sql_formule[1]="AVG";
$type_sql_formule[2]="MAX";
$type_sql_formule[3]="MIN";
$type_sql_formule[4]="NONE";
//TYPE DE DONNEES
$data_type[0]="Roaming";
//FLAT FILE
$upload_directory="/home/roaming_114/upload/";
$flat_file_directory="/home/roaming_114/flat_file_template/";
//REQUETE SQL STANDARD
$sql_database_table_list="SELECT tablename FROM pg_tables WHERE tablename !~* 'pg_*'";
$sql_table_field_list_part1="SELECT a.attname AS field, a.attnum,  t.typname AS type, a.attlen AS length, a.atttypmod AS length_var, a.attnotnull AS not_null, a.atthasdef as has_default FROM pg_class c, pg_attribute a, pg_type t WHERE c.relname = " ;
$sql_table_field_list_part2=" AND a.attnum > 0 AND a.attrelid = c.oid AND a.atttypid = t.oid ORDER BY a.attnum;";
//MULTI OBJECT
$transparence_color=false;
//GESTION DE L'OVERLOAD DE LA BDD
$commande_check_overload = "du -hcsS /usr/local/pgsql/data/base/";
$valeur_limite_overload = 40; //limite de 40Giga
//HOMEPAGE CELL STATS MODIFICATION
$nb_jours=7;
?>
