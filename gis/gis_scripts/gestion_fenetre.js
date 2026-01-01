/************************************************************************************************
Fonction qui permet d'ouvrir une fenetre avec les dimensions souhaitees sans barre de navigation
*************************************************************************************************/


var win = null;

function ouvrir_fenetre(url,nom,scroll,resize,width,height)
{
LeftPosition = (screen.width) ? (screen.width-width)/2 : 0;
TopPosition = (screen.height) ? (screen.height-height)/2 : 0;

// 17/04/2008 - Modif. benoit : on déclare la variable 'settings' avant de l'utiliser via le mot-clé 'var' (sinon, erreur JS)

var settings ='height='+height+',width='+width+',top='+TopPosition+',left='+LeftPosition+',resizable='+resize+',scrollbars='+scroll;
win = window.open(url,nom,settings);
win.focus();
return win;
}

