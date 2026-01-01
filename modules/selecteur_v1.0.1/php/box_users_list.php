<?php
/**
*	Ce fichier génère la boite "Users Sélection" du sélecteur.
*
*	Les différents éléments de cette boite sont :
*		- users
*
*	@author	MPR - 04/11/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*/

// $to_hide est une chaine qui contient tous les éléments à NE PAS afficher dans la boite.
// ex: $params = array('hide' => 'ta_level date hour period')
$to_hide = ' '.$params['hide'];

// Users List
$users_list = isset($selecteur_values[0]) ? $selecteur_values[0] : Array();

// defaults values for this box
$defaults = isset($selecteur_values[1]) ? $selecteur_values[1] : Array();
$this->setDefaults($defaults);



//		==========	DISPLAY selecteur		==========

?>
<!--	na_level	-->
<?php if (!strpos($to_hide,'users_list')) { ?>
	<div class="selecteur" id="selecteur_users_list_div">
		<select id="selecteur_users_list" name="selecteur[user]" class="zoneTexteStyleXP">
			<?php foreach ($users_list as $id_user=>$user) { ?>
				<option value="<?php echo $id_user ?>" <?php if ($id_user == $this->selecteur['user']) echo "selected='selected'"; ?>><?= $user ?></option>
			<?php } ?>
		</select>
<?php } ?>




