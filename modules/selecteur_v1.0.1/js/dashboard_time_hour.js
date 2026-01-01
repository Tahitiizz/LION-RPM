/**
*	Cette fonction force une valeur correcte sur le champ hour lorsqu'on le quite
*	Elle est appelée par $('selecteur_hour').onblur
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*	@params	void		part de $F('selecteur_hour')
*	@return	void		renseigne la valeur de $('selecteur_hour')
*/

// incompatible avec le format AM/PM : ne marche qu'en mode 12/24

function selecteurHourBlur()
{
	var hour = parseInt($F('selecteur_hour'),10);
	now = new Date();
	if (!hour && (hour!=0))		hour = now.getHours();
	if (hour > 23)	hour = now.getHours();
	if (hour < 0)	hour = now.getHours();
	if (hour<10) {
		hour = '0'+hour+':00';
	} else {
		hour = ''+hour+':00';
	}
	if (hour != $F('selecteur_hour')) {
		rouge('selecteur_hour',hour);
	}
}


$('selecteur_hour').onblur = selecteurHourBlur;

