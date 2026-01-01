<?php
/*
	29/01/2009 GHX
		- modification des requêtes SQL pour mettre id_user entre cote  [REFONTE CONTEXTE]
*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?php

/*
rajouter dans menu_deroulant intranet :

une colonne

droit_visible int2 (0=all,1=astellia,2=acurio)

la liste des actions
le droit_visible
dans l'url id_menu_encours = id_menu

dans menu_contextuel

page=$IdPage


dans liste_of_id_element()

gerer la requete  en fonction de la class_object dans pauto_display.class.php

$query="select id_elem from sys_pauto_config where class_object<>'selecteur' and  id_page=".$this->id_page;







*/
session_start();
include_once($repertoire_physique_niveau0 . "php/environnement_liens.php");
include_once($repertoire_physique_niveau0 . "php/database_connection.php");

$query="delete from sys_contenu_buffer where id_user='$id_user'";
        //echo "query selecteur $query";
        pg_query($database_connection,$query);

include_once($repertoire_physique_niveau0 . "intranet_top.php");

    global $niveau0;
    $file = $PHP_SELF;
?>
<table width="100%" cellpadding="100">
<tr>
  <td align="center" valign="middle">
        <img src="<?=$niveau0?>images/titres/no_content_defined.gif"/>
  </td>
</tr>
</table>
</body>
</html>
