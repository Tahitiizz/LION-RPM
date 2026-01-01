<?php
/*
	26/05/2009 GHX
		- Création d'une variable global JS pour le menu contextuel
*/
?>
<?php

// Récupération de la liste des items du menu contexuel correspondant au menu en cours puis construction du tableau de liens JS

// 21/11/2011 BBX
// BZ 24764 : correction des messages "Notice" PHP
if(!isset($id_menu_encours)) $id_menu_encours = null;

$menu_contextuel = new ContextMenuModel($id_menu_encours);

if ($items = $menu_contextuel->getMenus()) {

	$item_JS = array();

	for ($i=0; $i < count($items); $i++) {
		
		$item_pptes = array();
				
		foreach ($items[$i] as $ppte => $value) 
		{			
			eval( "\$value = \"$value\";" );
						
			$item_pptes[] = "\t".$ppte.": ".$value;
		}

		$item_JS[] = "{\n".implode(",\n", $item_pptes)."\n}";
	}

?>

	<link media="screen" type="text/css" href="<?= URL_CONTEXTMENU ?>css/proto.menu.0.6.css" rel="stylesheet">

	<script src="<?= URL_CONTEXTMENU ?>js/proto.menu.0.6.js" type="text/javascript"></script>

	<script type="text/javascript">

		// 17:27 26/05/2009 GHX
		// Création d'une variable global pour le menu contextuel, qui est utilisé dans la fonction pop
		var _myMenuContextuel;
		
		Event.observe(window, "load", function() { 

			var myMenuItems = [<?= implode(",\n", $item_JS) ?>];
			
			_myMenuContextuel = new Proto.Menu({
				selector: '#container', // context menu will be shown when element with class name of "contextmenu" is clicked
				className: 'menu desktop', // this is a class which will be attached to menu container (used for css styling)
				menuItems: myMenuItems // array of menu items
			});
		});

	</script>
<?php

}

?>