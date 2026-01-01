// Time in javascript is in millisec
const MILLISECONDS_BY_DAY = 86400 * 1000;
const MILLISECONDS_BY_WEEK = 604800 * 1000;


/********************************************************
* Return timestamp corresponding to the first day of the the first week of a year,
* depending on the first day of the week (default is monday, ISO 8601 definition)
* @param {int} year : format YYYY
* @param {int} [firstDayOfWeek = 1] : day of the week (0=sunday,1=monday,2=thuesday, etc...). default = monday
* @return {int} timestamp
********************************************************/	
function getTimestampFromWeekBeginningOfYear(year, firstDayOfWeek = 1)
{
    // Find the first day of the transition week at year beginning (week 53 or 01)
    // Start with first of january (months 0-11 in javascript)
    var myDate = new Date(year, 0);
    // timestamp in millisec
    var janv01Timestamp = myDate.getTime();
    // weekday: 0=sunday to 6=saturday
    var janv01WeekdayNb = myDate.getDay();
    var firstdayOfYearTimestamp = janv01Timestamp;
    var weekday = janv01WeekdayNb;
    var daysOnPreviousYear = 0;

    // Go back to week beginning
    while (weekday != firstDayOfWeek) {
        --weekday;
        ++daysOnPreviousYear;
        // Substract a day = 86400 seconds (timestamp in sec)
        firstdayOfYearTimestamp -= MILLISECONDS_BY_DAY;
        if(weekday < 0) { weekday = 6; }
    }
    // Transition week belongs to previous year ?
    // Rule: this week belongs to the year that contains 4 days or more
    if(daysOnPreviousYear > 3 ) {
        // week 1 is on next year: jump next week
        firstdayOfYearTimestamp += MILLISECONDS_BY_WEEK;
    }

    return firstdayOfYearTimestamp;
}


//	18/08/2010 MMT deplavement du module calendar a datePicker dans le cadre de la
// creation du module datePicker pour bug bz 16753

//	ajout SLC 10/09/2008	- cb4100
//	Cette fonction s'assure qu'on obtienne une date a 10 caractères : 06/08/2008 et non pas une date plus courte 6/8/2008
//	@param	string		strDate : chaine contenant une date de format type : 03/09/2008 ou 3/09/2008 ou 03/9/2008 ou 3/9/2008
//	@param	int		debug : remplissez cette variable si vous voulez être en mode debug
//	@return	string		retourne du chaine contenant une date au format 03/09/2008
function date10(strDate,debug)
{
	var jour	= strDate.slice(0,strDate.indexOf('/'));
	var reste	= strDate.slice(strDate.indexOf('/')+1);
	var mois	= reste.slice(0,reste.indexOf('/'));
	var annee	= reste.slice(reste.indexOf('/')+1);
	
	if (debug) alert('strDate = '+strDate+"\n"
		+'jour = '+jour+'  ('+jour.length+') '+"\n"
		+'mois = '+mois+' ('+mois.length+') '+"\n"
		+'annee = '+annee+' ('+annee.length+')');
	
	if (jour.length == 1) jour = '0'+jour;
	if (mois.length == 1) mois = '0'+mois;
	
	if (debug) alert(strDate + ' -> ' + jour+'/'+mois+'/'+annee);
	return jour+'/'+mois+'/'+annee;
}
/*	debug de la fonction
	date10('03/09/2008',1);
	date10('03/9/2008',1);
	date10('3/09/2008',1);
	date10('3/9/2008',1);
*/

/********************************************************
* Calculate the date of last day of the week given in parameter
* @param {String} week_date: year-week, format 'YYYYWW'
* @param {int} weekStarts: week beginning (0=sunday,1=monday,2=thuesday, etc...).
* @return {Array} date of week end : format { DD, MM, YYYY } - ex { 25, 08, 2008 }
********************************************************/	
function weekToDay(week_date, weekStarts)
{
	var year = parseInt(week_date.substr(0,4),10);
	var week = parseInt(week_date.substr(4,2),10);

    // Not ISO-8601 calculation: week number depends on first day of week
    // If weekStarts = 1 (monday), calculate an ISO date
    // get timestamp of first day of week 1
    var firstdayOfYearTimestamp = getTimestampFromWeekBeginningOfYear(year, weekStarts);
    // add weeks offset (number of milliseconds) and last week day
    var lastDayTimeStamp = firstdayOfYearTimestamp + MILLISECONDS_BY_WEEK*(week-1) + MILLISECONDS_BY_DAY*6;
    // Date from timestamp
	var enddate	= new Date(lastDayTimeStamp);

	return formatDate(enddate);    
}


/********************************************************
* Calculate the week number corresponding to the date (day) given in parameter
* @param {string} myday:  formet 'YYYYMMDD'	(or 2008-09-10 or 10/09/2008)
* @param {int} weekStarts: week beginning (0=sunday,1=monday,2=thuesday, etc...).
* @return 'W' + WW + '-' + YYYY  (ex: 'W26-2008')
********************************************************/
function dayToWeek(myday, weekStarts)
{
	// on transforme 10/09/2008 en 20080910 si besoin
	if (myday.indexOf('/') != -1) {
		myday = myday.substr(6,4) + myday.substr(3,2) + myday.substr(0,2);
	}
	
	// on transforme 2008-09-10 en 20080910 si besoin
	myday = myday.replace(/\-/gi,'');
	
	// on découpe la date d'entrée
	var year	= parseInt(myday.substr(0,4),10);
	var month	= parseInt(myday.substr(4,2),10);
	var day     = parseInt(myday.substr(6,2),10);

	// create a date object from the given date
	var mydate = new Date(year, month-1, day);
    // myday timestamp in millisec
    var inputTimestamp = mydate.getTime();
    // get timestamp of first day of week 1
    var firstdayOfYearTimestamp = getTimestampFromWeekBeginningOfYear(year, weekStarts);
    var week = 1;

    // Input date is on previous year ?
    if(inputTimestamp < firstdayOfYearTimestamp) {
        --year;
        week = 53;
    }
    // Calculate week of input date
    if(inputTimestamp > firstdayOfYearTimestamp) {
        // Calculate week number
        var diffseconds = inputTimestamp - firstdayOfYearTimestamp;
        week += Math.floor(diffseconds / MILLISECONDS_BY_WEEK);
        // Date is in the last week of year ?
        if(week == 53) {
            // The last week of year belongs to this year ?
            // Get first of january next year
            var nextYear = new Date(year+1,0,1);
            var nextYearTimestamp = nextYear.getTime();
            var lastweekOfYearTimestamp = firstdayOfYearTimestamp + MILLISECONDS_BY_WEEK*52;
            var daysThisYear = (nextYearTimestamp - lastweekOfYearTimestamp)/MILLISECONDS_BY_DAY;
            if(daysThisYear < 4) {
                // The week belongs to next year
                week = 1;
                ++year;
            }
        }
    }

    // Format return string: 'W' + WW + '-' + YYYY
    if (week < 10) week = '0' + week;
	return 'W'+week+'-'+year;
}	


function formatDate(kou) 
{
	var tabDate = new Array();
	
	var day	= (kou.getDate()+100).toString().substring(1,3);
	var month	= (kou.getMonth()+101).toString().substring(1,3);
	var year	= kou.getFullYear();
	
	return Array(day, month, year);
}

function checkDate(day, month, year){

	var actual = new Date();
	var actual_year = ((document.all) ? actual.getYear() : actual.getYear()+1900);

	if (year > actual_year) return checkDate(actual.getDate(), (actual.getMonth()+1), actual_year);

	if (month > (actual.getMonth()+1)) return checkDate(actual.getDate(), (actual.getMonth()+1), year);
	
	if((actual_year == year) && ((actual.getMonth()+1) == month) && (actual.getDate() < day))
	{
		return checkDate(actual.getDate(), month, year);
	}

	return Array(day, month, year);
}

// Fonction de convertion du mois

function monthToDay(month_date)
{
	var year	= parseInt(month_date.substr(0,4),10);
	var month	= parseInt(month_date.substr(4,2),10);
	var day	= daysInMonth(month,year);

	return Array(day, month, year);
}

function daysInMonth(month,year) {
	var m = [31,28,31,30,31,30,31,31,30,31,30,31];
	if (month != 2) return m[month - 1];
	if (year%4 != 0) return m[1];
	if (year%100 == 0 && year%400 != 0) return m[1];
	return m[1] + 1;
}

function formatDay(day_date){

	var year	= parseInt(day_date.substr(0,4),10);
	var month	= parseInt(day_date.substr(4,2),10);
	var day		= parseInt(day_date.substr(6,2),10);

	return Array(day, month, year);
}