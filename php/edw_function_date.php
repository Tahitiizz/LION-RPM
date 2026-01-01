<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?
	/*
		- maj 13 03 2006 christophe : fonction dateDBtoDisplay(date) permet d'afficher une date venant de la base.
	*/


	/*
		Permet d'afficher une date formaté pour le display venant de la base.
			$date : Le format de la date est un string commencant par une date de format 20060313
				Attention : si le format détecté est en hour : 2006031308 alors le format affiché sera 13-03-2006 08:00
				si la variable $displayAll est à true
			$separateur : caractère qui sépare jour mois année de la date.
			$displayAll : si à true on affiche les caractères restant dans la chaine se trouvant après la date (par exemple
			si il y a des heures ou d'autres info : 20060313 15:14, on veut modifier la date mais la le display de l'heure)
	*/
	function dateDBtoDisplay($date, $separateur, $displayAll){
		$suite = "";
		if($displayAll){
			if(trim(substr($date,8,1)) != ""){
				$suite = substr($date,8,2).":00";
			} else {
				$suite = substr($date,8,strlen($date));
			}

		}
		return(substr($date,6,2).$separateur.substr($date,4,2).$separateur.substr($date,0,4). " ".$suite);
	}
?>
