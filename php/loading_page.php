<?php
/**
*	@cb50212@
*	@author : BBX
*	@description : permet l'affichage de la fen�tre de chargement. Cr�� dans le cadre de la correction du bug 11686.
**/
?>

<!--[if IE 6]>
<style type="text/css">
/* 29/03/2010 BBX :  pour IE6. BZ  */
#loader_container {
	position:absolute;
	left:0px;
	top:expression(documentElement.scrollTop + (screen.height/2) - 150);
}
#loader_background {
	position:absolute;
	left:0px;
	top:expression(documentElement.scrollTop + 0);
	height:expression(screen.height);
}
#loader {
	height:30px;
	overflow:hidden;
}
</style>
<![endif]-->

<div id="loader_background">&nbsp;</div>
<div id="loader_container">
	<div id="loader">
		<div align="center" id="texteLoader">Loading...</div>
		<div id="loader_bg">
			<div id="progress"> </div>
		</div>
		<div align="center" id="texteLoaderCancel">Press &lt;ESC&gt; to abort</div>
	</div>
</div>

<script type="text/javascript">
// Declaration des variables globales
var _ta_loader_id = null;
var _ta_loader_pos = 0;
var _ta_loader_dir = 2;
var _ta_loader_len = 0;
var _ta_loader_max_len = 79;

// Fcontion qui permet l'animation
function taLoaderStartLoading() {
	var elem = $('progress');
	if( (elem != null) && ( typeof elem.setStyle == "function" ) )
	{
		if (_ta_loader_pos==0) _ta_loader_len += _ta_loader_dir;
		if (_ta_loader_len>32 || _ta_loader_pos>_ta_loader_max_len) _ta_loader_pos += _ta_loader_dir;
		if (_ta_loader_pos>_ta_loader_max_len) _ta_loader_len -= _ta_loader_dir;
		if (_ta_loader_pos>_ta_loader_max_len && _ta_loader_len==0) _ta_loader_pos=0;
        // 16/08/2010 OJT : Correction bz16755 pour DE Firefox, utilisation des fonctions prototype
		elem.setStyle( {left:_ta_loader_pos + 'px'} );
		elem.setStyle( {width:_ta_loader_len + 'px'} );
	}
}

// Fonction qui efface la fenetre de chargement
function taLoaderStopLoading() {
	_ta_loader_id.stop();
	$('loader_container').setStyle({display:'none'});
	$('loader_background').setStyle({display:'none'});
}

// Demarrage de l'animation
_ta_loader_id = new PeriodicalExecuter(function() {
	taLoaderStartLoading();
}, 0.02);

// Lancement du listener qui effacera la fenetre lorsque la page sera chargee
Event.observe(window, 'load', function() {
	taLoaderStopLoading();
});

// Lancement du listener qui effacera la fenetre lorsque le chargement sera interrompu
// 16/08/2010 OJT : Correction bz16757 pour DE Firefox
if( navigator.appName != "Microsoft Internet Explorer" )
{
    Event.observe(document, 'keydown', function( e ){if( e.keyCode == 27 ){taLoaderStopLoading();}});
}
else
{
    Event.observe(document, 'stop', function(){taLoaderStopLoading();});
}
</script>
