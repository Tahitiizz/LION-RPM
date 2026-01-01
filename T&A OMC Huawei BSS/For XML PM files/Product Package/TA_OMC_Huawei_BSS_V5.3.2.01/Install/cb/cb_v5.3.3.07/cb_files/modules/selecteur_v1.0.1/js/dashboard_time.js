
/**
*	Cette fonction met à jour le champ date lorsque l'on change le TA level
*
*	@author	SLC - 10/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*	@params	void		part de $F('selecteur_ta_level')
*	@return	void		renseigne la valeur de $('selecteur_date')
*
*	09/03/2010 NSE bz 13648, 13650, 14642
*		- ajout paramètre week_starts_on_monday
*
*	18/03/2010 MMT bz 16753 division de changeSelecteurTALevel en deux fonction, d'ou création de
*     convertDateToTALevel qui est utilisé par le nouveau calendirer datePicker
*
*/

// converti la date 'inputDate' (peu importe son format TA) au nouveau format TA passe en parametre
function convertDateToTALevel(inputDate, ta_level) {

	// les 3 formats de date :   dd/mm/yyyy		mm/yyyy		Ww/yyyy



	// 09/03/2010 NSE bz 13648, 13650, 14642 quel jour la semaine commence-t-elle?
	_weekStarts = $('week_starts_on_monday').value;


   //18/03/2010 MMT bz 16753 remplace valeures en dur selecteur_date et selecteur_ta_level
   // par paramètres inputDate, ta_level

	 var ret = inputDate;

	// update Date selecteur
	if ($('selecteur_date')) {

		//mydate = $F('selecteur_date');
      mydate = inputDate;
		// TA level = hour  or  day
		if ((ta_level.indexOf('hour') != -1) || (ta_level.indexOf('day') != -1)) {
			// on affiche la date : --> turn into 'dd/mm/yyyy' format
         //18/03/2010 MMT si meme format, retourne valeure entrée
			if (mydate.length == 10) return inputDate;
			if (mydate.slice(0,1) == 'W') {
				// turn week into day
				var strWeek = mydate.slice(mydate.indexOf('-')+1,mydate.length) + mydate.slice(1,mydate.indexOf('-'));
				// alert(strWeek);
				// 09/03/2010 NSE bz 13648, 13650, 14642 ajout paramètre week_starts_on_monday
				aDay = weekToDay(strWeek,_weekStarts);
			} else {
				// turn month into day		mm/yyyy
				var strMonth = mydate.slice(mydate.indexOf('/')+1,mydate.length) + mydate.slice(0,2);
				// alert(strMonth);
				aDay = monthToDay(strMonth);
			}
			strDay	= aDay[0]+'/'+aDay[1]+'/'+aDay[2];
			strDay2	= date10(strDay);
			strDay_reverse	= strDay2.slice(6)+strDay2.slice(3,5)+strDay2.slice(0,2);
			// verifie qu'on est pas dans le futur
			Today	= new Date();
			strToday	= ''+ Today.getFullYear();
			myMonth	= parseInt(Today.getMonth()) + 1;
			if (myMonth < 10)	strToday += '0'+myMonth;
			else				strToday += myMonth;
			myDate	= parseInt(Today.getDate());
			if (myDate < 10)		strToday += '0'+myDate;
			else				strToday += myDate;
			if (strToday < strDay_reverse) {
				strDay	= ''+myDate + '/' + myMonth + '/' + Today.getFullYear();
				strDay2	= date10(strDay);
			}
         //18/03/2010 MMT retourne nouveau format
			ret = strDay2;
		}

		// TA level = week
		if (ta_level.indexOf('week') != -1) {
			// on affiche la date : --> turn into 'Ww/yyyy' format
         //18/03/2010 MMT si meme format, retourne valeure entrée
			if (mydate.slice(0,1) == 'W') return inputDate;
			if (mydate.length == 10) {
				// turn day into week
				// 09/03/2010 NSE bz 13648, 13650, 14642 ajout paramètre week_starts_on_monday
				week = dayToWeek(mydate,_weekStarts);
			} else {
				// turn month into week : month -> day -> week
				strMonth = mydate.slice(3) + mydate.slice(0,2);
				// alert(strMonth);
				aDay		= monthToDay(strMonth);
				strDay	= date10(aDay[0]+'/'+aDay[1]+'/'+aDay[2]);
				strDay2	= strDay.slice(6)+strDay.slice(3,5)+strDay.slice(0,2);
				// alert ("strMonth= "+strMonth+"\nstrDay= "+strDay+"\nstrDay2= "+strDay2);
				// 09/03/2010 NSE bz 13648, 13650, 14642 ajout paramètre week_starts_on_monday
				week = dayToWeek(strDay2,_weekStarts);
			}
         //18/03/2010 MMT retourne nouveau format
			ret = week;
		}

		// TA level = month
		if (ta_level.indexOf('month') != -1) {
			// on affiche la date : --> turn into 'mm/yyyy' format
			if (mydate.length == 10) {
				// turn day into month
            //18/03/2010 MMT retourne nouveau format
				ret = mydate.slice(3,mydate.length);
			} else if (mydate.slice(0,1) == 'W') {	// mydate = W35-2008
				// turn week into month : week -> day -> month
				var strWeek = mydate.slice(mydate.indexOf('-')+1,mydate.length) + mydate.slice(1,mydate.indexOf('-'));
				// alert(strWeek);
				// 09/03/2010 NSE bz 13648, 13650, 14642 ajout paramètre week_starts_on_monday
				aDay		= weekToDay(strWeek,_weekStarts);
				strDay	= date10(aDay[0]+'/'+aDay[1]+'/'+aDay[2]);
				// verifie qu'on est pas dans le futur
				// on compose today_yyyymm
				Today	= new Date();
				today_yyyymm = ''+Today.getFullYear();
				myMonth	= parseInt(Today.getMonth()) + 1;
				if (myMonth < 10)	today_yyyymm += '0'+myMonth;
				else				today_yyyymm += myMonth;
				// on compose yyyymm
				yyyymm = ''+strDay.slice(6)+strDay.slice(3,5);
				if (yyyymm > today_yyyymm) {
					strDay	= ''+Today.getDate()+ '/' + myMonth + '/' + Today.getFullYear();
					strDay	= date10(strDay);
				}

            //18/03/2010 MMT retourne nouveau format
				ret = strDay.slice(3);
			}
		}
	}
   //18/03/2010 MMT retourne nouveau format
   return ret;
}

/**
 * Actions a executer spécifiques au changement de valeure du TA dans le selecteur
 */
function changeSelecteurTALevel()
{
    // Show/Hide hour selecteur
   ta_level = $F('selecteur_ta_level');
    if ( $( 'selecteur_hour_div' ) ) {
		if (ta_level.indexOf('hour') != -1) {
            $( 'selecteur_hour_div' ).show();
		}
        else {
            $( 'selecteur_hour_div' ).hide();
	}
    }

   // update Date selecteur
    if ( $( 'selecteur_date' ) ) {
        $( 'selecteur_date' ).value = convertDateToTALevel($F('selecteur_date'),ta_level);
   }

    // DE Sélecteur. Gestion de la période maximum en fonction de la ta (en OT uniquement)
    if( $( "selPerMaxVal" ) ) // Test de présence, dans certains cas la période n'est pas affichée
    {
        if( $( "maxHist_" + ta_level.sub( '_bh', '' ) ) )
        {
            $( "selPerMaxVal" ).update( $F( "maxHist_" + ta_level.sub( '_bh', '' ) ) );
            selecteur_max_period = parseInt( $F( "maxHist_" + ta_level.sub( '_bh', '' ) ) );
            selecteurPeriodBlur();
		}
        else
        {
            $( "selPerMaxVal" ).update( '?' );
        }
    }

    // Le changement de TA doit etre indiqué au module Fixed Hour (si activé)
    if( typeof showHideFixedHourMode == 'function' ){
        showHideFixedHourMode();
    }
}


// on attache la fonction au selecteur de TA level
$('selecteur_ta_level').onchange = changeSelecteurTALevel;

// on initialise le selecteur
changeSelecteurTALevel();
