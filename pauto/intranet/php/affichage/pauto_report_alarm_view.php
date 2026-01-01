<?
/*
*	@cb41000@
*
*	Composant de base version cb_4.1.0.00
* 
*	17/06/2009 BBX :
*	=> Constantes CB 5.0
*	=> Header CB 5.0
*	=> Gestion du produit
* 
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
?>
<?
/*
	Permet de prévisualiser une alarme depuis la création des rapports.
*/
session_start();
include_once dirname(__FILE__)."/../../../../php/environnement_liens.php";
include_once(REP_PHYSIQUE_NIVEAU_0 . "php/edw_function_family.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/setup_alarm.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/select_family.class.php");

// on récupère les variables
// (par défaut, alarm_type est à 'alarm_static')
$family = 		$_GET["family"];
$product = 		$_GET["product"];
$alarm_id = 	$_GET['alarm_id'];
$alarm_type = 	$_GET['alarm_type'];

// on inclue la classe correspondant au type d'alarme
include_once(REP_PHYSIQUE_NIVEAU_0 . "class/setup_".$alarm_type.".class.php");

//on enregistre le nom de la table utilisée par ce type d'alarme
$sys_definition_alarm_table = 'sys_definition_'.$alarm_type;

// determine si la famille possède un troisième axe
$flag_axe3 = get_axe3($family);

// DEBUT PAGE
$arborescence = ucfirst($alarm_type).' Detail';
include_once(REP_PHYSIQUE_NIVEAU_0.'php/header.php');
?>
<script type="text/javascript" src="<?=NIVEAU_0?>js/tab-view-ajax.js"></script>
<script type="text/javascript" src="<?=NIVEAU_0?>js/tab-view.js"></script>
<div id="container" style="width:100%;text-align:center">
	<script>
		// Defintion de la XMLHttpRequest en fonction du navigateur

	  var strictDocType = false;

		var http;	// Variable js globale du XMLHttpRequest

		if (window.XMLHttpRequest) { // Mozilla, Safari, ...
			http = new XMLHttpRequest();
		} else if (window.ActiveXObject) { // IE
			http = new ActiveXObject("Microsoft.XMLHTTP");
		}

		// Fonction de recherche des valeurs du sélecteur de raw counters/kpi
		// La variable fieldType correspond au type de données (raw/kpi)
		// La cible correspond à la liste du formulaire qui recevra les données
		// La famille est nécessaire pour les requêtes de récupération des raw counters et kpi
		// Avant de lancer la requête, on désactive le champ 'cible' et on affiche le message 'loading...'

		function getFieldValue(fieldType,cible,family){
	    document.getElementById(cible).disabled = true;
	    document.getElementById(cible).options.length = 1;
	    document.getElementById(cible).options[0].text = "Loading ...";
	    document.getElementById(cible).options[0].value = "makeSelection";
	  	http.open("GET", "<?=NIVEAU_0?>php/ajax_select_raw_kpi.php?champ="+fieldType+"&cible="+cible+"&family="+family, true);
			http.onreadystatechange = handleHttpResponse;
			http.send(null);

		}

		// Fonction de traitement des valeurs du filtre retournées depuis 'ajax_select_raw_kpi.php'
		// une fois toutes les données intégrées, on remplace le message 'loading' par 'make a selection'
		// le champ 'cible' est alors réactivé.

		function handleHttpResponse() {
			if(http.readyState == 4){
	      // les données de construction de la liste sont décapsulées de la chaîne
	      // |field| est le séparateur entre deux options
				var tableau_liste = http.responseText.split('|column|');
				var maListe = document.getElementById(tableau_liste[0]);
	      maListe.options.length = tableau_liste.length-1;
	      for (i=1; i< tableau_liste.length - 1; i++) {
	        tableau_option = tableau_liste[i].split('|field|');
	        maListe.options[i].value = tableau_option[0];
	        maListe.options[i].text = tableau_option[1];
	      }
				maListe.options(0).text='Make a Selection';
	      maListe.disabled = false;
			}
		}

	  // on retire le titre de la liste des choix possible de 'cible'
		function remove_choice(cible) {
	    if (cible.options[0].value == 'makeSelection') {
	      cible.options.remove(0);
	    }
	  }
	</script>
	<link rel="stylesheet" href="<?=NIVEAU_0?>css/tab-view.css" type="text/css" media="screen">
</head>
<body leftmargin="0" topmargin="0">
	<table width="550px" align="center">
		<tr>
			<td>
				<? $alarm = new alarm($family, $alarm_id, $alarm_type,$sys_definition_alarm_table, 1, $product); ?>
			</td>
		</tr>
	</table>
</div>
</body>
</html>
