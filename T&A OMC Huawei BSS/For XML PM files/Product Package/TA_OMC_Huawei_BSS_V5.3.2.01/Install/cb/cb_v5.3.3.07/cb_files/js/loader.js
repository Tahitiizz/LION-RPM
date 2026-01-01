/**
* @cb4100@
* Fonctions de gestion du loader.
* Morceau de javascript extrait de intranet_top.php dans l'optique de séparer PHP et JS
* Stéphane Le Solliec - skype:stef_ls
*
*/

var t_id = setInterval(animate,20);
var pos=0;
var dir=2;
var len=0;

function animate(){
	var elem = document.getElementById('progress');
	if(elem != null) {
		if (pos==0) len += dir;
		if (len>32 || pos>79) pos += dir;
		if (pos>79) len -= dir;
		if (pos>79 && len==0) pos=0;
		elem.style.left = pos;
		elem.style.width = len;
	}
}

function remove_loading() {
	this.clearInterval(t_id);
	var targelem = document.getElementById('loader_container');
	targelem.style.display='none';
	targelem.style.visibility='hidden';
	// 20/08/2007 - Modif. benoit : masquage du div "loader_background" à la fin du chargement de la page
	var targelem = document.getElementById('loader_background');
	targelem.style.display='none';
	targelem.style.visibility='hidden';
}

