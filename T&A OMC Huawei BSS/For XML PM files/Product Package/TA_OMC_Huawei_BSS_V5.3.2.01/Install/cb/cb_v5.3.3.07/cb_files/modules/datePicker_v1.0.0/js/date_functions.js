

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

// Fonctions de convertion de la semaine
// weekToDay transforme  '200835'   en   { 25, 08, 2008 } ou { 25, 8, 2008 } selon l'humeur
// 09/03/2010 NSE bz 13648, 13650, 14642 ajout paramètre week_starts_on_monday
function weekToDay(week_date,weekStarts)
{
	var year = parseInt(week_date.substr(0,4),10);
	var week = parseInt(week_date.substr(4,2),10);
		
	var jan10		= new Date(year,0,10,12,0,0);
	var jan4		= new Date(year,0,4,12,0,0);
	var wk1mon	= new Date(jan4.getTime()-(jan10.getDay())*86400000);
	var startdate	= new Date(wk1mon.getTime()+(week-1)*604800000);
	// NSE on ajoute le jour de début de la semaine -1 => pour lundi, ça ne change rien. Pour dimanche, la semaine se termine le samedi
	var enddate	= new Date(startdate.getTime()+518400000+(weekStarts-1)*86400000);

	return formatDate(enddate);
}

// ajout SLC 10/09/2008	- cb4100
/*	Cette fonction prend un jour et le trouve sa semaine
	@param	myday = 20080910	(or 2008-09-10 or 10/09/2008)
	@return	'W26-2008'
	
	09/03/2010 NSE bz 13648, 13650, 14642
		- remaniement de la fonciton pour gestion correcte semaine 53 et week_starts_on_monday
*/
function dayToWeek(myday,weekStarts)
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
	var day	= parseInt(myday.substr(6,2),10);

	//la date du d'entrée
	var jourj = new Date(year,month-1,day,14,0,0 );

	// Rq js getDay() : lundi=1 ; dimanche=0 ;

	//Calculer le jeudi de la même semaine que la date cherchée. 
	//On calculera le numéro de semaine de ce jeudi pour être sûr de compter dans la bonne année sans test particulier. 
	//(Ce jeudi peut tomber l'année précédente ou suivante.)
	var jeudi = new Date(year,month-1,day,14,0,0 );
	
	// on recherche le jeudi de la même semaine (jour <4 mais !=0 car 0 == dimanche, sauf si la semaine commence le dimanche)
	while(jeudi.getDay()<4&&(jeudi.getDay()!=0||weekStarts==0))
		jeudi.setDate(jeudi.getDate()+1); //			&&(jeudi.getDay()!=0||jeudi.getDay()==0&&weekStarts==0))
	while(jeudi.getDay()>4||jeudi.getDay()==0&&weekStarts!=0)
		jeudi.setDate(jeudi.getDate()-1);
	
	//Considérer le 4 janvier de la même année que ce jeudi.
	var jan4 = new Date(jeudi.getFullYear(),0,4,12,0,0);
	//Calculer le lundi de la même semaine que ce 4 janvier 
	//(identifiant ainsi le début du comptage; ce lundi peut tomber la même année ou avant)
	while(jan4.getDay()!=1){
		jan4.setDate(jan4.getDate()-1);
	}
	//Calculer le nombre de jours écoulés entre les deux dates particulières (ce jeudi et ce lundi).
	var ecart = jeudi.getTime()-jan4.getTime();
	//Diviser par 7 (arrondir à l'entier supérieur). On a le résultat voulu.
	var semaine = Math.ceil(ecart/86400000/7);
	if (semaine < 10) semaine = '0'+semaine;
	
	// si la semaine commence le lundi (weekStarts==1) et qu'on recherche la semaine d'un dimanche, on retranche 1
	if(weekStarts-1==jourj.getDay()){
		//semaine--;
	}
	return 'W'+semaine+'-'+jeudi.getFullYear();
}
/* tests de dayToWeek()
alert("dayToWeek('20080424') = "+dayToWeek('20080424'));
alert("dayToWeek('24/04/2008') = "+dayToWeek('24/04/2008'));
alert("dayToWeek('2008-04-24') = "+dayToWeek('2008-04-24'));
*/
// FIN ajout SLC 10/09/2008


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