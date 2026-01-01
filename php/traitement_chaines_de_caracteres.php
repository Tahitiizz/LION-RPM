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
		Fonctions permettant de traiter des chaine de caractères.
		christophe le 22 06 2005
	*/

	// Fonction renameString ($string )
	// param : une chaine de caractère.
	// retourne la chaine de caractère sans les caractères invalides se trouvant dans le tableau.
	function renameString ($fichier){
		// Tableau contenant tous les caractères invalides.
		$tab_caracteres_invalide = array ("é","è","É","À","Á","Æ","Ç","à","'","&","\"","(","-","ç",")","=","~","#","{","[","|","`","\\","^","@","]","}","<",">",",",";",":","!","?","/","§","ù","*","%","µ","$","£","¤","€","%","%%","+");
		// Tableau contenant les caractères qui remplacement des caractères invalides (même ordre).
		$tab_caracteres_de_remplacement = array ("e","e","e","a","a","a","c","a","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","","");

		$chaine_valide = str_replace ($tab_caracteres_invalide, $tab_caracteres_de_remplacement, $fichier);

		return ($chaine_valide);
	}
?>
