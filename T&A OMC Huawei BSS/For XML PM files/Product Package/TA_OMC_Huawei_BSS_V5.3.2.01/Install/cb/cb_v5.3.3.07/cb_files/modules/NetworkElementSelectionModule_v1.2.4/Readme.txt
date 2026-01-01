Changements pour la version 1.2.4 :	-- BBX le 25/11/2008
	- "networkElementSelection.class.php" : ajout d'un div qui contient el champs de saisie 
afin de pouvoir détruire puis reconstruire son contenu
	- "networkElementSelection.js" : destruction puis reconstruction du champ de recherche
pour que l'autocomplétion fonctionne correctement
	- Mise à jour de "controls.js" et "scriptaculous.js" en version 1.8.1
	- Gestion de la recherche absolue : si une liste est trop longue et tronquée mais que l'élément est choisit par la recherche,
on ajoute cet élément à la liste
	
Changements pour la version 1.2.3 :	-- BBX le 09/10/2008

	- modification de la recherche : suppression des lignes concernant la recherche dans le fichier js et utilisation des méthodes fournies 
par script aculous.
	- modification de la recherche côté php (get_search.php)
	- ajout de la librairie js "controls.js" fournie par script aculous et nécessaire à l'auto-complétion.


Changements pour la version 1.2.2 :	-- SLC le 23/09/2008

	- ajout de networkElementSelectionSaveHook() permettant de redonner la main à la page qui contient le module après sauvegarde


Changements pour la version 1.2.1 :

- mise en conformité avec les normes W3C du fichier networkElementSelection.css (suite au mail de mickael Lebreton).
- quand on est en mode bouton radio et que on clique sur le bouton 'View current selection' si on a paramétré un fichier 
php à l'appel de la méthode setViewCurrentSelectionContentButtonProperties(), on peut personnaliser l'affichage. En variables
_GET de ce fichier sont ajoutés la valeur de chaque onglet. Ainsi on peut savoir quelle valeur appartient à quel onglet.