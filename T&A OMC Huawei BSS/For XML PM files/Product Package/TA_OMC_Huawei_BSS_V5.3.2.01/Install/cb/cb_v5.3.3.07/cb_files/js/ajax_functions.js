/*
*  20/03/2009 - modif SPS : ajout de l'effet Hightlight 
	- maj 15/03/2007, benoit : définition d'une nouvelle fonction pour traiter les na_box, basée sur 'execute_query_to_update_select_na_selecteur()'

	- maj 19/06/2006, christophe : modification de la fonction execute_query_to_update_select_na_selecteur() (pour la prise en charge du 'search option')

	@create 09 12 2005
	@update
	@auteur christophe
	
	---------- AJAX functions ----------
	
	Librairies de fonctions permettant d'envoyer et recevoir de requêtes XMLHttpRequest entre le serveur / client.
*/

	// Créé et retourne un objet XMLHttpRequest.
	function getHTTPObject() {
		var xmlhttp;
		
		/*@cc_on
		@if (@_jscript_version >= 5)
		try {
		  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
		  } catch (e) {
		  try {
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (E) {
			xmlhttp = false;2
			}
		  }
		@else
		xmlhttp = false;
		@end @*/
			
		if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
			try {
				xmlhttp = new XMLHttpRequest();
			} catch (e) {
				xmlhttp = false;
			}
		}
			
		return xmlhttp;
	}
	
	var http = getHTTPObject();
	
	/*
		Execute un fichier sur le serveur distant.
		- url : url du fichier à exécuter.
		
		La page doit contenir obligatoirement 2 champs hidden dont les id doivent être : obj_cible, obj_alert_user.
	*/
	function ajouter_commentaire(url){
		url = url + document.getElementById('liste_param'). value;
		
		http.open("GET", url, true);
		http.onreadystatechange = maj_last_comment;	// Impossible de mettre des params sinon ça plante cf doc microsoft : http://msdn.microsoft.com/workshop/author/dhtml/reference/events/onreadystatechange.asp
		http.send(null);
	}
	
	
	/*
		Récupère les données renvoyées par le fichiers distant exécuté.
		- obj_cible : id de l'élément HIDDEN HTML qui sera mis-à-jour.
		- obj_alert_user :  id de l'élément à surligner lorsque la requête a été exécutée.
	*/
	function maj_last_comment() {
		if (http.readyState == 4) {
			resultat = 	http.responseText;
			innerObj = 	document.getElementById('obj_cible').value;
			fadeObj = 	document.getElementById('obj_alert_user').value;
			document.getElementById(innerObj).innerHTML = resultat;
			/* 20/03/2009 - modif SPS : ajout de l'effet Hightlight */
			new Effect.Highlight(fadeObj, {startcolor: '#fdcb55', endcolor: '#ffffff'});
		}
	}
	
	
	/*
		Execute une requête SQL dans un fichier distant qui permet de mettre à jour le contenu d"une balise select.
		La requête SQL doit OBLIGATOIREMENT avoir trois champs sélectionnés nommés : value, display et style.
		value = valeur 'value' de la balise <option> // display : valeur affichée dans le select // style : couleur de fond de la ligne (aucune par défaut).
		
		ATTENTION :
		* La page dans laquelle est appellée la fonction doit contenir une balise text de type hidden nommée : select_id_hidden
		(<input type="hidden" value="" id="select_id_hidden"/>)
		* La page dans laquelle est appellée la fonction doit contenir une balise text de type hidden nommée : div_id_hidden
		(<input type="hidden" value="" id="div_id_hidden"/>)
		* La balise select qui sera mise à jour devra être contenue dans une balise div d'identifiant div_id.
		* Info importante :
		Lorsque vous construisez la requête en php, elle doit être sur UNE SEULE LIGNE.
		
		Params de la fonction :
		- query : requête à exécutée (au format définit si dessus).
		- select_id : contenu de la balise id="" du <select id="">...
		- div_id : id du div qui contient le select
		- js_function : fonction javascript complète + appel + balise du select (ex : " onChange='alert(this.value)' ")
		- table_query : ici c'est le nom de la table du from de la query à exécuter
	*/

	// 07/12/2007 - Modif. benoit : ajout de l'argument 'niveau0' (par défaut vide) à la fonction 'execute_query_to_update_select_na_selecteur()'

	function execute_query_to_update_select_na_selecteur(na, select_id, div_id, js_function, table_query, niveau0){
	
		searchvalue 	 = ""; // A chaque changement au vide la variable contenant l'élément à rechercher dans la balise select
		old_select_value = "";
		
		document.getElementById('select_id_hidden'). value 	= 	select_id;
		document.getElementById('div_id_hidden'). value 	= 	div_id;
		
		document.getElementById(select_id).options[document.getElementById(select_id).selectedIndex].text = "Updating...";
		document.getElementById(select_id).disabled = true;
		
		url = niveau0+"ajax_update_select_na_selecteur.php?table_query="+table_query+"&na="+na+"&select_id="+select_id+"&js_function="+js_function;
		
		http.open("GET", url, true);
		http.onreadystatechange = recupere_query_to_update_select_na_selecteur;	
		http.send(null);
	}

	/* Redefinition de la fonction 'execute_query_to_update_select_na_box_selecteur' pour traiter les na_box */

	// 09/08/2007 - Modif. benoit : ajout de l'argument 'start_url' à la fonction

	function execute_query_to_update_select_na_box_selecteur(na, select_id, div_id, js_function, table_query, start_url){
	
		searchvalue 	 = ""; // A chaque changement au vide la variable contenant l'élément à rechercher dans la balise select
		old_select_value = "";
		
		document.getElementById('select_id_hidden'). value 	= 	select_id;
		document.getElementById('div_id_hidden'). value 	= 	div_id;
		
		document.getElementById(select_id).options[document.getElementById(select_id).selectedIndex].text = "Updating...";
		document.getElementById(select_id).disabled = true;
		
		url = start_url+"ajax_update_select_na_box_selecteur.php?table_query="+table_query+"&na="+na+"&select_id="+select_id+"&js_function="+js_function;

		http.open("GET", url, true);
		http.onreadystatechange = recupere_query_to_update_select_na_selecteur;	
		http.send(null);
	}
	
	/*
		Récupère et affche la réponse renvoyée par la requête de la fonction execute_query_to_update_select.
	*/
	function recupere_query_to_update_select_na_selecteur(){
		if (http.readyState == 4) {
			resultat = http.responseText;	// on récupère le résultat.
			innerObj = document.getElementById('div_id_hidden').value;
			document.getElementById(innerObj).innerHTML = "";
			document.getElementById(innerObj).innerHTML = resultat;			// mise à jour de la balise select
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	