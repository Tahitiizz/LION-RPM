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
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?
	/*
		Permet de rediriger l'utilisateur vers la page d'accueil de l'application si la session est perdue.

		- maj 20 09 2006 christophe : on affiche un message d'erreur quand l'utilisateur est redirigé vers la page d'accueil.
	*/

	if (!isset($repertoire_physique_niveau0) || $repertoire_physique_niveau0 == ""){
		$msg_erreur = "Session expired.";
		$file = "../../../../index.php?msg_erreur=$msg_erreur";
		header("Location:$file");
	}
?>
