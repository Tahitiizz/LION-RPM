/**
*	Cette fonction force une valeur correcte sur le champ date lorsque l'on le quite
*	Elle est appelée par $('selecteur_date').onblur
*
*	@author	SLC - 10/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*	@params	void		part de $F('selecteur_date')
*	@return	void		renseigne la valeur de $('selecteur_date')
*/

function selecteurDateBlur()
{
	var date	= $F('selecteur_date');
	var format	= '';
	var now	= new Date();
	
	// on determine quel format doit avoir la date
	if (!$('selecteur_ta_level')) {
		format = 'dd/mm/yyyy';
	} else {
		var ta_level = $F('selecteur_ta_level');
		if (ta_level.indexOf('hour') != -1) {
			format = 'dd/mm/yyyy';
		} else if (ta_level.indexOf('day') != -1) {
			format = 'dd/mm/yyyy';
		} else if (ta_level.indexOf('week') != -1) {
			format = 'Ww-yyyy';
		} else if (ta_level.indexOf('month') != -1) {
			format = 'mm/yyyy';
		} else {
			format = 'dd/mm/yyyy';
		}
	}
	
	//alert(format);
	
	var corrected = '';

	if (format == 'dd/mm/yyyy') {
		var arrDate = date.split('/');
		// check day
		arrDate[0] = parseInt(arrDate[0],10);
		if (!arrDate[0]) arrDate[0] = now.getDate();
		if (arrDate[0] < 1) arrDate[0] = 1;
		if (arrDate[0] > 31) arrDate[0] = 30;
		if (arrDate[0] < 10) {
			corrected = '0'+arrDate[0];
		} else {
			corrected = ''+arrDate[0];
		}
		// check month
		arrDate[1] = parseInt(arrDate[1],10);
		if (!arrDate[1]) arrDate[1] = parseInt(now.getMonth(),10)+1;
		if (arrDate[1] < 1) arrDate[1] = 1;
		if (arrDate[1] > 12) arrDate[1] = 12;
		if (arrDate[1] < 10) {
			corrected += '/0'+arrDate[1];
		} else {
			corrected += '/'+arrDate[1];
		}
		// check year
		arrDate[2] = parseInt(arrDate[2],10);
		if (!arrDate[2]) arrDate[2] = now.getFullYear();
		if (arrDate[2] < 100) arrDate[2] = 2000 + arrDate[2];
		if (arrDate[2] > now.getFullYear()) arrDate[2] = now.getFullYear();
		if (arrDate[2] < 2000) arrDate[2] = now.getFullYear();
		corrected += '/'+arrDate[2];

	} else if (format == 'Ww-yyyy') {
		var arrDate = date.split('-');
		// check week
		if (arrDate[0].slice(0,1) == 'W')
			arrDate[0] = arrDate[0].slice(1);
		arrDate[0] = parseInt(arrDate[0],10);
		if (arrDate[0] < 10) {
			corrected = 'W0'+arrDate[0];
		} else {
			corrected = 'W'+arrDate[0];
		}
		// check year
		arrDate[1] = parseInt(arrDate[1],10);
		if (!arrDate[1]) arrDate[1] = now.getFullYear();
		if (arrDate[1] < 100) arrDate[1] = 2000 + arrDate[1];
		if (arrDate[1] > now.getFullYear()) arrDate[1] = now.getFullYear();
		if (arrDate[1] < 2000) arrDate[1] = now.getFullYear();
		corrected += '-'+arrDate[1];
		// last check on week that override all
		if ((!arrDate[0]) || (arrDate[0] < 1) || (arrDate[0] > 53)) {
			//alert(dayToWeek(date10(''+now.getDate()+'/'+(parseInt(now.getMonth(),10)+1)+'/'+now.getFullYear())));
			corrected = dayToWeek(date10(''+now.getDate()+'/'+(parseInt(now.getMonth(),10)+1)+'/'+now.getFullYear()));
		}



	}
	
//	alert(format);
	
	if (corrected != $F('selecteur_date'))		rouge('selecteur_date',corrected);

}


$('selecteur_date').onblur = selecteurDateBlur;

