<?php
/*
*	Classe de gestion des dates
*	@Since : 	cb_v5.0.0.00 (patch cb_v4.0.10.0.1)
*	@Date : 	26/05/2009
*	@Author : 	BBX
*	@Version : 	1.0
*
*
*	19/02/2010 NSE bz 14427 : le dernier jour de la semaine 52 ou 53 n'est pas forcément le 31/12. 
*					 La fin de la semaine 52 ou 53 peut être début janvier.
*					 Suppression de la condition qui retournait lastDayOfYear... et simplification de la fonction.
*					 Le dernier jour de la semaine arrive forcément 6 jours après le premier.
*	23/02/2010 NSE ajout des fonctions
*		- getLastDayOfMonth() Retourne la date du dernier jour du mois passé en paramètre
*		- convertAstelliaWeekToAstelliaWeekWithSeparator() Retourne une semaine Astellia en semaine Astellia avec séparateur type W26-2010
 * 25/05/2011 NSE bz 22316 : getWeek($date,$firstDayOfWeek='') est utilisée par les parseurs (iu, gsm...) sans le paramètre optionnel.
 *                           Initialisation avec global parameters value.
 *                              idem pour getWeekFromDay et getWeekFromHour qui mettaient comme valeur par défaut 1
*
*       22/06/2010 BBX BZ 14034
*           - Correction des méthodes ConvertAstelliaHourToFRHour et ConvertAstelliaHourToUsHour
 *
 *      28/03/2011 NSE merge 5.0.5 -> 5.1.1 : Ajout de la fonction getHour() (since 5.0.4.14)
 * 11/10/2011 NSE DE Bypass temporel : ajout de la méthode getTaValueFromHour
 *
*/
class Date {
    
    const SECONDS_BY_DAY = 86400;
    const SECONDS_BY_WEEK = 604800;

	public function __construct() {
	
	}
	
	// ********************************************************************** //
	//					STATIC FUNCTIONS					       //
	// ********************************************************************** //

	/********************************************************
	* Retourne une date format sélecteur vers un format TA
	* @param String : Time aggregation (hour, day, day_bh, etc...)
	* @param String : Date (04/06/2009 23:00, 04/06/2009, ...)
	* @return String : date au format TA
	********************************************************/		
	public static function getDateFromSelecteurFormat($ta,$date) 
	{	
		// Valeur par defaut
		$taValue = $date;
		switch($ta) 
		{		
			// Hour 12/11/2008 15:00 => 2008111215
			case 'hour' : 
				$hour = substr($date,11,2);
				$day = substr($date,0,2);
				$month = substr($date,3,2);
				$year = substr($date,6,4);
				$taValue = $year.$month.$day.$hour;
			break;
			// Day 12/11/2008 => 20081112
			case 'day':
			case 'day_bh':
				$day = substr($date,0,2);
				$month = substr($date,3,2);
				$year = substr($date,6,4);
				$taValue = $year.$month.$day;
			break;
			// Week  W46-2008 => 200846
			case 'week' :
			case 'week_bh' :
				$week = substr($date,1,2);
				$year = substr($date,4,4);
				$taValue = $year.$week;
			break;
			// Month 12/2008 => 200812
			case 'month' :
			case 'month_bh' :
				$month = substr($date,0,2);
				$year  = substr($date,3,4);
				$taValue = $year.$month;
			break; 
		}
		// Retour de la TA convertie
		return $taValue;
	}

	/********************************************************
	* Retourne une date TA vers un format sélecteur
	* @param String : Time aggregation (hour, day, day_bh, etc...)
	* @param String : Date (2009060423, 20090604, 200906, etc...)
	* @return String : date au format selecteur
	********************************************************/		
	public static function getSelecteurDateFormatFromDate($ta,$date) 
	{	
		// Valeur par defaut
		$taValue = $date;
		switch($ta) 
		{		
			// Hour 2008111215 => 12/11/2008 15:00
			case 'hour' : 
				$year = substr($date,0,4);
				$month = substr($date,4,2);
				$day = substr($date,6,2);
				$hour = substr($date,8,2);				
				$taValue = $day."/".$month."/".$year." ".$hour.":00";
			break;
			// Day 20081112 => 12/11/2008
			case 'day':
			case 'day_bh':
				$year = substr($date,0,4);
				$month = substr($date,4,2);
				$day = substr($date,6,2);
				$taValue = $day."/".$month."/".$year;
			break;
			// Week  200846 => W46-2008
			case 'week' :
			case 'week_bh' :
				$year = substr($date,0,4);
				$week = substr($date,4,2);
				$taValue = "W".$week."-".$year;
			break;
			// Month 200812 => 12/2008
			case 'month' :
			case 'month_bh' :
				$year = substr($date,0,4);
				$month = substr($date,4,2);
				$taValue = $month."/".$year;
			break; 
		}
		// Retour de la TA convertie
		return $taValue;
	}
	
	/********************************************************
	* Retourne la journée correspondant aux paramètres en base de données
	* /!\ C'est cette fonction à utiliser lors des compute. C'est la seule à utiliser l'offset day.
	* @param Int : offset day, nombre de jours séparant aujourd'hui et le jour à traiter
	* @return String : jour au format YYYYMMDD
	********************************************************/		
	public static function getDayFromDatabaseParameters($offsetDay='')
	{
		// Récupération de l'offset Day en paramètre ou en base
		$offsetDay = ($offsetDay === '') ? get_sys_global_parameters('offset_day') : $offsetDay;
		// Calcul du jour correspondant à l'offset day
		$day = date('Ymd', strtotime('-'.$offsetDay.' day'));
		// Retour de la week
		return $day;
	}
	
	/********************************************************
	* Retourne la semaine correspondant aux paramètres en base de données
	* /!\ C'est cette fonction à utiliser lors des compute. C'est la seule à utiliser l'offset day.
	* @param Int : offset day, nombre de jours séparant aujourd'hui et le jour à traiter
	* @return String : semaine au format YYYYWW
	********************************************************/		
	public static function getWeekFromDatabaseParameters($offsetDay='')
	{
		// Récupération du premier jour de la semaine en base de données
		$firstDayOfWeek = get_sys_global_parameters('week_starts_on_monday');
		if($firstDayOfWeek === '') $firstDayOfWeek = 1;
		// Récupération de l'offset Day en paramètre ou en base
		$offsetDay = ($offsetDay === '') ? get_sys_global_parameters('offset_day') : $offsetDay;
		// Calcul du jour correspondant à l'offset day
		$day = self::getDayFromDatabaseParameters($offsetDay);
		// Retour de la week
		return self::getWeek($day,$firstDayOfWeek);
	}
	
	/********************************************************
	* Retourne le mois correspondant aux paramètres en base de données
	* /!\ C'est cette fonction à utiliser lors des compute. C'est la seule à utiliser l'offset day.
	* @param Int : offset day, nombre de jours séparant aujourd'hui et le jour à traiter
	* @return String : mois au format YYYYMM
	********************************************************/		
	public static function getMonthFromDatabaseParameters($offsetDay='')
	{
		// Récupération de l'offset Day en paramètre ou en base
		$offsetDay = ($offsetDay === '') ? get_sys_global_parameters('offset_day') : $offsetDay;
		// Calcul du jour correspondant à l'offset day
		$day = self::getDayFromDatabaseParameters($offsetDay);
		// Retour de la week
		return self::getMonth($day);
	}
	
	/********************************************************
	* Retourne le mois correspondant à la date passée en paramètre
	* @param String : date au format day YYYYMMDD ou hour YYYYMMDDHH
	* @return String : mois au format YYYYMM
	********************************************************/	
	public static function getMonth($date) 
	{
		return substr($date,0,6);
	}

	/********************************************************
	* Retourne la semaine correspondant à la date passée en paramètre
	* @param String : date au format day YYYYMMDD ou hour YYYYMMDDHH
	* @param Int : premier jour de la semaine (0=dimanche,1=lundi,2=mardi, etc...). Par defaut = Lundi
	* @return String : semaine au format YYYYWW
	********************************************************/	
	public static function getWeek($date,$firstDayOfWeek='')
	{
        // 25/05/2011 NSE bz 22316 : les parsers (iu, gsm) utilisent cette méthode et ne passent pas le param firstDayOfWeek, ce qui pose problème.
        // on le cherche donc en base s'il n'est pas spécifié.
		if($firstDayOfWeek == '')
            $firstDayOfWeek = taCommonFunctions::get_sys_global_parameters('week_starts_on_monday');
		if($firstDayOfWeek === '') $firstDayOfWeek = 1;
		// Day
		if(strlen($date) == 8) {
			return self::getWeekFromDay($date,$firstDayOfWeek);
		}
		// Hour
		if(strlen($date) == 10) {
			return self::getWeekFromHour($date,$firstDayOfWeek);
		}
	}

	/********************************************************
	* Retourne la semaine correspondant au jour passé en paramètre
	* @param String : jour au format YYYYMMDD
	* @param Int : premier jour de la semaine (0=dimanche,1=lundi,2=mardi, etc...). Par defaut = Lundi
	* @return String : semaine au format YYYYWW
	********************************************************/
	public static function getWeekFromDay($day,$firstDayOfWeek='')
	{
        // 25/05/2011 NSE bz 22316 : les parsers (iu, gsm) utilisent cette méthode et ne passent pas le param firstDayOfWeek, ce qui pose problème.
        // on le cherche donc en base s'il n'est pas spécifié.
		if($firstDayOfWeek == '') {
            $firstDayOfWeek = taCommonFunctions::get_sys_global_parameters('week_starts_on_monday'); }
        if($firstDayOfWeek === '') { $firstDayOfWeek = 1; }
        
        // FDE - Convert input day to Unix timestamp
        $inputTimestamp = strtotime($day);

		if($firstDayOfWeek == 1) {
            // Standart case: Gregorian date ISO-8601, week begins on monday (PHP default)
            return date('oW',$inputTimestamp);
        }
        
        // Week begins other days, weeks numbering must be ajusted
        $year = date('Y',$inputTimestamp);

        $firstdayOfYearTimestamp = self::getTimestampFromWeekBeginningOfYear($year, $firstDayOfWeek);
        $week = 1;
        
		// Input date is on previous year ?
        if($inputTimestamp < $firstdayOfYearTimestamp) {
            --$year;
            $week = 53;
		}
        // Calculate week of input date
        if($inputTimestamp > $firstdayOfYearTimestamp) {
            // Calculate week number
            $diffseconds = $inputTimestamp - $firstdayOfYearTimestamp;
            $week += (int)($diffseconds / self::SECONDS_BY_WEEK);
            // Date in last week of year ?
            if($week == 53) {
                // The last week of year belongs to this year ?
                $nextYearTimestamp = strtotime(($year+1).'0101');
                $lastweekOfYearTimestamp = $firstdayOfYearTimestamp + self::SECONDS_BY_WEEK*52;
                $daysThisYear = ($nextYearTimestamp - $lastweekOfYearTimestamp)/self::SECONDS_BY_DAY;
                if($daysThisYear < 4) {
                    $week = 1;
                    ++$year;
                }
            }
        }
        //displayInDemon( 'day='.$day.', firstday='.$firstDayOfWeek.', offset='.$offset.', ret='.date('oW',$timestamp), 'normal', true);

        // Format return string
        return sprintf('%d%02d', $year, $week);
	}	
	
	/********************************************************
	* Retourne la semaine correspondant à l'heure passée en paramètre
	* @param String : heure au format YYYYMMDDHH
	* @param Int : premier jour de la semaine (0=dimanche,1=lundi,2=mardi, etc...). Par defaut = Lundi
	* @return String : semaine au format YYYYWW
	********************************************************/	
	public static function getWeekFromHour($hour,$firstDayOfWeek='')
	{
        // 25/05/2011 NSE bz 22316 : les parsers (iu, gsm) utilisent cette méthode et ne passent pas le param firstDayOfWeek, ce qui pose problème.
        // on le cherche donc en base s'il n'est pas spécifié.
		if($firstDayOfWeek == '')
            $firstDayOfWeek = taCommonFunctions::get_sys_global_parameters('week_starts_on_monday');
		if($firstDayOfWeek === '') $firstDayOfWeek = 1;
		return self::getWeekFromDay(substr($hour,0,8),$firstDayOfWeek);
	}
	
	/********************************************************
	* Retourne le timestamp correspondant à l'heure passée en paramètre
	* @param String : heure au format YYYYMMDDHH
	* @return Int : timestamp
	********************************************************/	
	public static function getTimestampFromHour($hour)
	{
		$dayPart = substr($hour,0,8);
		$hourPart = substr($hour,-2);
		return strtotime($dayPart.' '.$hourPart.':00');
	}
	
	/********************************************************
	* Retourne le timestamp correspondant au jour passée en paramètre
	* @param String : jour au format YYYYMMDD
	* @return Int : timestamp
	********************************************************/	
	public static function getTimestampFromDay($day)
	{
		return strtotime($day);
	}
	
	/********************************************************
	* Retourne le timestamp correspondant au premier jour du mois passé en paramètre
	* @param String : mois au format YYYYMM
	* @return Int : timestamp
	********************************************************/	
	public static function getTimestampFromFirstDayOfMonth($month)
	{
		return strtotime($month.'01');
	}
	
	/********************************************************
	* Retourne le timestamp correspondant au dernier jour du mois passé en paramètre
	* @param String : mois au format YYYYMM
	* @return Int : timestamp
	********************************************************/	
	public static function getTimestampFromLastDayOfMonth($month)
	{
		$nbDaysInMonth = self::getLastDayFromMonth($month);
		return strtotime($month.$nbDaysInMonth);
	}

	/********************************************************
	* Return timestamp corresponding to the first day of the the first week of a year,
    * depending on the first day of the week
	* @param String : year format YYYY
	* @param Int    : first day of the week (0=sunday,1=monday,2=thuesday, etc...). default = monday
	* @return Int   : timestamp
	********************************************************/	
	public static function getTimestampFromWeekBeginningOfYear($year, $firstDayOfWeek=1)
	{
        // Find the first day of the transition week at year beginning (week 53 or 01)
        // Start with first of january
        $janv01Timestamp = strtotime($year.'0101');
		// weekday: 0=sunday to 6=saturday
        $janv01WeekdayNb = date("w", $janv01Timestamp);
        $firstdayOfYearTimestamp = $janv01Timestamp;
        $weekday = $janv01WeekdayNb;
        $daysOnPreviousYear = 0;

        // Go back to week beginning
        while ($weekday != $firstDayOfWeek) {
            --$weekday;
            ++$daysOnPreviousYear;
            // Substract a day = 86400 seconds (timestamp in sec)
            $firstdayOfYearTimestamp -= self::SECONDS_BY_DAY;
            if($weekday < 0) { $weekday = 6; }
        }
        // Transition week belongs to previous year ?
		// Rule: this week belongs to the year that contains 4 days or more
		if($daysOnPreviousYear > 3 ) {
			// week 1 is on next year: jump next week
            $firstdayOfYearTimestamp += self::SECONDS_BY_WEEK;
        }
        
        return $firstdayOfYearTimestamp;
	}

	/********************************************************
	* Retourne la date du dernier jour du mois passé en paramètre
	* @param String : mois au format YYYYMM
	* @return String : date du dernier jour du mois au format YYYYMMDD
	* @author : NSE
	********************************************************/	
	public static function getLastDayOfMonth($month)
	{
		return date('Ymd',self::getTimestampFromLastDayOfMonth($month));
	}

	/********************************************************
	* Retourne le dernier jour du mois passé en paramètre
	* @param String : mois au format YYYYMM
	* @return Int : dernier jour du mois (=nombre de jours dans le mois)
	********************************************************/		
	public static function getLastDayFromMonth($month)
	{
		return date('t',self::getTimestampFromFirstDayOfMonth($month));
	}
	
	/********************************************************
        * 22/01/2013 BBX
        * BZ 31364 : réécriture de la méthode pour gérer les week/year ISO
	* Retourne la date du premier jour de la semaine passée en paramètre au format YYYYMMDD
	* @param String : semaine au format YYYYWW
	* @param Int : premier jour de la semaine (0=dimanche,1=lundi,2=mardi, etc...). Par defaut = Lundi
	* @return Int : date du premier jour de la semaine (YYYYMMDD)
	********************************************************/	
	public static function getFirstDayFromWeek($week,$firstDayOfWeek=1)
	{
        // Déclaration des sous-dates nécessaires
        $year = substr($week,0,4);
        $week = substr($week,-2);

        if($firstDayOfWeek == 1) {
            // standard case : week begins on monday
            $firstDayTimeStamp = strtotime("{$year}-W{$week}");
        } else {
            // Not ISO-8601 calculation: week number depends on first day of week
            // get timestamp of first day of week 1
            $firstdayOfYearTimestamp = self::getTimestampFromWeekBeginningOfYear($year, $firstDayOfWeek);
            // add week offset (number of seconds)
            $firstDayTimeStamp = $firstdayOfYearTimestamp + self::SECONDS_BY_WEEK*($week-1);
        }

        // Retour de la date au format ISO YYYYMMDD
        // 26/02/2013 GFS - Bug 31889 - [INT][TA HPG] : Source availability displays wrong month for week 01 
        return date("Ymd", $firstDayTimeStamp);
	}
	
	/********************************************************
	* Retourne la date du dernier jour de la semaine passée en paramètre au format YYYYMMDD
	* @param String : semaine au format YYYYWW
	* @param Int : premier jour de la semaine (0=dimanche,1=lundi,2=mardi, etc...). Par defaut = Lundi
	* @return Int : date du dernier jour de la semaine (YYYYMMDD)
	*
	*	19/02/2010 NSE bz 14427 : le dernier jour de la semaine 52 ou 53 n'est pas forcément le 31/12. 
	*					 La fin de la semaine 52 ou 53 peut être début janvier.
	*					 Suppression de la condition qui retournait lastDayOfYear... et simplification de la fonction.
	*					 Le dernier jour de la semaine arrive forcément 6 jours après le premier.
	*
	********************************************************/	
	public static function getLastDayFromWeek($week,$firstDayOfWeek=1)
	{  	
		//si $week n'est pas au bon format, on prend la semaine en cours
		if (strlen($week) != 6) $week = date('oW');
		// le dernier jour de la semaine arrive forcément 6 jours après le premier.
		return date('Ymd',strtotime(self::getFirstDayFromWeek($week,$firstDayOfWeek).' +6 day'));
	}	
	
	/********************************************************
	* Retourne le nombre de jour entre 2 dates
	* @param String : date au format hour YYYYMMDDHH ou day YYYYMMDD
	* @return Int : nombre de jour
	********************************************************/		
	public static function getDatesDiff($date1,$date2) 
	{
		// Hour to Day
		if(strlen($date1) == 10)
			$date1 = substr($date1,0,8);
		// Hour to Day
		if(strlen($date2) == 10)
			$date2 = substr($date2,0,8);
		// To timastamp
		$timestampDate1 = self::getTimestampFromDay($date1);
		$timestampDate2 = self::getTimestampFromDay($date2);
		
		// Calcul de la différence
		$diff = round(abs($timestampDate1-$timestampDate2)/86400);
		//Bug 34104 - [REC][CB 5.3.1.01][Perf] : compute day is wrong when compute during the first hour of the day
        /*        $adjust = 0;
                // 25/01/2012 BBX
                // BZ 25452 : Prise en compte du changement d'heure
                if(($diff/3600)%24 == 1) $adjust = -1;
		// Retour du nombre de jours
		return ceil($diff/(3600*24))+$adjust;*/
		$diff -= 5;		
		$datetest = ($timestampDate1 > $timestampDate2) ? $date2 : $date1;				
		$datecur = date('Ymd',strtotime("-$diff day"));			

		while ($datetest != $datecur) {
				$diff++;					
				$datecur = date('Ymd',strtotime("-$diff day"));           				
		}

		return $diff;
	}
	
        
        
    /**
     * Retourne la valeur de la Ta value dans une Ta donnée à partir de la Ta Value hour.
     * @param $ta string nom de la TA (hour, day...).
     * @param $hour int valeur de la TA hour (2011060719).
     * @return string Ta value dans la Ta demandée.
    */
    public static function getTaValueFromHour($ta, $hour){
	switch($ta){
		case "hour" :
			$ta_value = $hour;
			break;
		case "day" :
		case "day_bh" :
			$ta_value = substr($hour, 0, 8);
			break;
		case "week" :
		case "week_bh" :
			$ta_value = self::getWeek($hour);
			break;
		case "month" :
		case "month_bh" :
			$ta_value = self::getMonth($hour);
			break;
	}
	return($ta_value);

    }
        
	/********************************************************
	* Retourne un jour Astellia en jour US
	* @param String : date au format day YYYYMMDD
	* @param String : séparateur ('-' par défaut)
	* @return String : date au format US
	********************************************************/	
	public static function convertAstelliaDayToUsDay($day,$sep='-')
	{
		return date('Y'.$sep.'m'.$sep.'d',strtotime($day));
	}
	
	/********************************************************
	* Retourne une heure Astellia en heure US
	* @param String : date au format hour YYYYMMDDHH
	* @param String : séparateur ('-' par défaut)
	* @return String : date au format US
     *
     * 22/06/2010 BBX BZ 14034 : Correction des méthodes ConvertAstelliaHourToFRHour et ConvertAstelliaHourToUsHour
     *
	********************************************************/	
	public static function convertAstelliaHourToUsHour($hour,$sep='-')
	{
            $day = substr($hour,0,8);
            $hour = substr($hour,-2);
            return date('Y'.$sep.'m'.$sep.'d H:00',strtotime($day.' '.$hour.':00'));
	}
	
	/********************************************************
	* Retourne un jour Astellia en jour FR
	* @param String : date au format day YYYYMMDD
	* @param String : séparateur ('-' par défaut)
	* @return String : date au format FR
	********************************************************/	
	public static function convertAstelliaDayToFRDay($day,$sep='/')
	{
		return date('d'.$sep.'m'.$sep.'Y',strtotime($day));
	}
	
	/********************************************************
	* Retourne une heure Astellia en heure FR
	* @param String : date au format hour YYYYMMDDHH
	* @param String : séparateur ('-' par défaut)
	* @return String : date au format FR
     *
     * 22/06/2010 BBX BZ 14034 : Correction des méthodes ConvertAstelliaHourToFRHour et ConvertAstelliaHourToUsHour
     *
	********************************************************/	
	public static function convertAstelliaHourToFRHour($hour,$sep='/')
	{
            $day = substr($hour,0,8);
            $hour = substr($hour,-2);
            return date('d'.$sep.'m'.$sep.'Y H:00',strtotime($day.' '.$hour.':00'));
	}
	
	/********************************************************
	* Retourne une semaine Astellia en semaine Astellia avec séparateur
	* @param String : semaine au format YYYYWW
	* @param String : préfixe (W par défaut)
	* @param String : séparateur ('-' par défaut)
	* @return String : semaine au format WWW-YYYY (le 1° W est la lettre réelle : W26-2010)
	* @author : NSE
	********************************************************/	
	public static function convertAstelliaWeekToAstelliaWeekWithSeparator($week,$pref='W',$sep='-')
	{
		return $pref.substr($week,4,2).$sep.substr($week,0,4);
	}
	
	/********************************************************
	* Calcul l'offset day en fonction du jour passé en paramètre
	* @param String : date au format day YYYYMMDD
	* @return Int : offset day
	********************************************************/	
	public static function getOffsetDayFromDay($day)
	{
		//return abs(floor((time()-strtotime($day))/(3600*24)));
		return self::getDatesDiff(date('Ymd', time()), $day);
	}	

    /**
     * Retoune une heure au format
     * Un offset et un format optionnel peuvent être fournis
     *
     * @since 5.0.4.14
     * @param int $offset Nombre d'heures à enlever (optionnel)
     * @param string $format Format de sortie de la date (optionnel)
     * @return string
     */
    public static function getHour( $offset = 0, $format = 'YmdH' )
    {
        return date( $format, strtotime( '-'.$offset.' hours' ) );
    }
}
?>
