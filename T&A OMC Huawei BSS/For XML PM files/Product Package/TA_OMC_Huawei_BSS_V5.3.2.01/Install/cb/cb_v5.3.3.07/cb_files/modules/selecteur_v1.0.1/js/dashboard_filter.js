/**
*	JavaScript allant avec les boites filter du sélecteur
*
*	@author	BBX - 26/11/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*
*/


/*****************************************************
* 	Fonction toggleSelecteur
* 	Affiche / masque le sélecteur avec effet de slide
* 	@author : BBX
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
* 	@return void
*****************************************************/
function toggleSelecteur()
{
	// Décomposition du chemin de l'image contenu dans le bouton
	var tabPath = $('selecteur_img_toogle').src.split('/');
	var imageName = tabPath[tabPath.length-1];
	// Décomposition du chemin de l'image img1
	var tabPath = _selecteurFilterImageHide.split('/');
	var img1Name = tabPath[tabPath.length-1];
	// Décomposition du chemin de l'image img2
	var tabPath = _selecteurFilterImageShow.split('/');
	var img2Name = tabPath[tabPath.length-1];
	// Test
	if(imageName == img1Name) {
		$('selecteur_img_toogle').src = _selecteurFilterImageShow;
		$('selecteur_status').value = "none";
	}
	else {
		$('selecteur_img_toogle').src = _selecteurFilterImageHide;
		$('selecteur_status').value = "block";
	}
	Effect.toggle('selecteur_container', 'slide');
}

/*****************************************************
* 	Fonction toggleSelecteurPopup
* 	Affiche la tooltip du bouton selon l'état (show/hide)
* 	@author : BBX
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
* 	@return void
*****************************************************/
function toggleSelecteurPopup()
{
	// Décomposition du chemin de l'image contenu dans le bouton
	var tabPath = $('selecteur_img_toogle').src.split('/');
	var imageName = tabPath[tabPath.length-1];
	// Décomposition du chemin de l'image img1
	var tabPath = _selecteurFilterImageHide.split('/');
	var img1Name = tabPath[tabPath.length-1];
	// Décomposition du chemin de l'image img2
	var tabPath = _selecteurFilterImageShow.split('/');
	var img2Name = tabPath[tabPath.length-1];
	// Test
	if(imageName == img1Name) {
		popalt(_selecteurFilterTextHide);
	}
	else {
		popalt(_selecteurFilterTextShow);
	}
}

/*****************************************************
* 	Lancement de l'observateur d'évènement sur les touches pressées
*	Si on presse F2, on affiche / cache le sélecteur
*****************************************************/
document.observe("dom:loaded", function() {
	Event.observe(document, 'keyup', function(event) 
	{
		var key = event.which || event.keyCode;
		if(key == 113) {
			toggleSelecteur();
		}
	});
});

/*****************************************************
* 	Lancement de l'observateur sur le statut du sélecteur
*	Si on a caché le sélecteur, on le recache lors de l'autorefresh
*****************************************************/
document.observe("dom:loaded", function() {
	if($('selecteur_status')) {
		if($('selecteur_status').value != '') {			
			if($('selecteur_status').value == 'block') $('selecteur_img_toogle').src = _selecteurFilterImageHide;
			else $('selecteur_img_toogle').src = _selecteurFilterImageShow;
			$('selecteur_container').style.display = $('selecteur_status').value;
		}
	}
});



