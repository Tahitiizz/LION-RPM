/*
	Affiche la pseudo-fenetre pour entrer le commentaire

	- liste_param : paramètres spécifiques à l'objet.
	- obj_cible : id de l'élément à mettre à jour après l'exécution de la requête.
	- obj_alert_user : id de l'élément à surligner lorsque la requête a été exécutée.

	exemple d'appel dans gtm_stroke_graph.class.php
	toggle_commentaire('ajouter_commentaire','$params_list','dernier_commentaire_graph_$value_id','dernier_commentaire_graph_$value_id'); initComment();\"
	
	20/03/2009 - modif SPS : on recupere la position et les dimensions de l'objet cible pour positionner la fenetre, et suppression des elements inutiles
	23/03/2009 - modif SPS : on detruit toutes les fenetres crees precedemment
*/
function toggle_commentaire(obj, liste_param, obj_cible, obj_alert_user) {
	
	/* 23/03/2009 - modif SPS : on detruit toutes les fenetres crees precedemment*/
	Windows.closeAll();
	
	/* 20/03/2009 - modif SPS : on recupere les dimensions et la position de l'objet cible*/
	//on recupere la position de l'element obj_cible
	var tpos = $(obj_cible).positionedOffset();
	var topObjetCible = tpos[1];
	var leftObjetCible = tpos[0];
	//on recupere la taille de l'objet cible
	var widthObjetCible = $(obj_cible).getDimensions().width;
	var heightObjetCible = $(obj_cible).getDimensions().height;
	
	// on ouvre la fenêtre
	// on remplit le formulaire des commentaires
	document.getElementById('liste_param'). value = 	liste_param;
	document.getElementById('obj_cible'). value = 		obj_cible;
	document.getElementById('obj_alert_user'). value = 	obj_alert_user;

	
	_winComment = new Window({ 
		className:"alphacube",
		title: "Add a comment",
		width:350,
		height:200,
		minWidth:135,
		minHeight:160,
		resizable:false,
		minimizable:false,
		recenterAuto: false,
		maximizable:false
	});

	_winComment.setZIndex(10000);
	_winComment.setContent(obj);
	
	/* 20/03/2009 - modif SPS : on positionne la fenetre en fonction de l'objet cible*/
	_winComment.setLocation(topObjetCible -heightObjetCible - 200,leftObjetCible + widthObjetCible - 345);
	_winComment.show();
	
	$(obj).style.display = 'block';
	_winComment.updateHeight();

}



/*
Initialise les champs contenu dans le div caché.
Appelé à chaque clic car dans une même page on peut saisir plusieurs commentaires
sur des éléments différents.
*/
function initComment(){
	//document.getElementById('comment_type').value = "1";  // MODIF DELTA value="0"
	document.getElementById('comment_level').value = "1"; // MODIF DELTA value="0"
	document.getElementById('comment_trouble_ticket').value = "Trouble ticket";
	document.getElementById('comment_content').value = "Your comment...";
	document.getElementById('comment_action').value = "Action...";
	document.getElementById('comment_content').style.border = "  #7F9DB9 1px solid ";
	document.getElementById('comment_alert').innerHTML = "";
}


/*
Permet de vérifier si un commentaire a été correctement saisi.
*/
// Enlève des caractères spéciaux de la chaine passée en paramètre.
function virer_car(chaine){
	temp = chaine.replace(/[àâä]/gi,"a");
	temp = temp.replace(/[éèêë]/gi,"e");
	temp = temp.replace(/[îï]/gi,"i");
	temp = temp.replace(/[ôö]/gi,"o");
	temp = temp.replace(/[ùûü]/gi,"u");
	temp = temp.replace(/[ç]/gi,"c");
	temp = temp.replace(/["''&]/gi," ");
	return temp;
}
function verifier_commentaire(){
	document.getElementById('comment_alert').innerHTML = "";
	valSaisie = document.getElementById('comment_content').value;
	var newVal = valSaisie.replace(/\s/g,"");        // on enlève tous les espaces blancs.
	var nb = newVal.length;

	if(newVal == '' || valSaisie == 'Your comment...' || valSaisie == 'Please write your comment.'){
		document.getElementById('comment_content').value = 'Please write your comment.';
		document.getElementById('comment_content').style.border = " 2px solid #FF0000 ";
	} 
	else {
		// On construit la chaine de paramètres.
		//var comment_type =				document.getElementById('comment_type').value;
		var comment_trouble_ticket =	virer_car(document.getElementById('comment_trouble_ticket').value);
		var comment_content =			virer_car(document.getElementById('comment_content').value);
		var comment_action =			virer_car(document.getElementById('comment_action').value);
		var comment_level =				document.getElementById('comment_level').value;
		// Cas où le user saisit seulement des ''''''''''''''''''''''''''''''''''.
		sString = comment_content;
		sString = sString.replace(/ /g,""); // on supprime tous les espaces.
		if(sString.length > 0){
			//var params = "?comment_type="+comment_type;
			//params += "&;
			var params = "?comment_trouble_ticket="+comment_trouble_ticket;
			params += "&comment_content="+comment_content;
			params += "&comment_action="+comment_action;
			params += "&comment_level="+comment_level;
			ajouter_commentaire('php/ajax_ajout_commentaire.php'+params);         // exécution du fichier distant.
			// fermeture de la pseudo fenêtre.
			_winComment.close();
			// toggle('ajouter_commentaire');
		} 
		else {
			document.getElementById('comment_content').value = 'Please write your comment. (comment not valid)';
			document.getElementById('comment_content').style.border = " 2px solid #FF0000 ";
		}
	}
}

// Permet de limiter le nombre de caractères saisis dans un textarea.
function limite(zone,max){
	if(zone.value.length>=max){
		zone.value=zone.value.substring(0,max);
		document.getElementById('comment_alert').innerHTML = "Limited to 255 characters.";
	}
}
