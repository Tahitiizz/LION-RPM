<?php
/*
	21/07/2009 GHX
		- Ajout du paramètre reload à l'url quand on fait clique droit reload (ce paramètre sert pour la restitution des dashboars)
*/
?>
<?php

/**
 * Cette classe permet de récupérer l'ensemble des informations permettant de construire le menu contextuel
 * 
 * @package Menu
 * @author BAC b.audic@astellia.com
 * @version 1.0.0
 * @copyright 2009 Astellia
 *
 */

class ContextMenuModel
{
	private $idMenu;
	private $database = null;
	
	/**
	 * Constructeur de la classe
	 *
	 * @param int $id_menu identifiant du menu dont on souhaite les items
	 */
	
	public function __construct($id_menu)
	{
		$this->idMenu = $id_menu;
                // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
		$this->database = Database::getConnection();
		
	}
	
	/**
	 * Retourne l'ensemble des items du menu contextuel
	 *
	 * @param boolean $add_reload indique si l'on ajoute l'item de rechargement de la page courante
	 * @return mixed la liste des items et leurs propriétés ou false si aucun item n'a été trouvé
	 */
	
	public function getMenus($add_reload = true)
	{
		$all_menus = array();

		// 1 - Avant de rechercher les items disponibles, on ajoute l'item de rechargement de la page (ssi le parametre '$add_refresh' vaut "true")

		// 15:25 21/07/2009 GHX
		// Dans le cas d'un reload on ajout le paramètre reload à la fin de l'url
		// ce paramètre sert pour la restitution des dashbaords
		if ($add_reload === true) {
			$all_menus[0] = array(	'name' => "'Reload'",
									'className' => "'reload'",
									'callback' => "function() {var link = window.parent.location.href; link=link.split('#'); window.parent.location.href = link[0]+'&reload';}");
		}
		
		// 2 - On récupère la liste des actions du menu contextuel en fonction du menu dans lequel on se trouve
		
		$sql = "SELECT liste_action FROM menu_deroulant_intranet WHERE id_menu = '".$this->idMenu."'";
		$row = $this->database->getRow($sql);
		
		if ($row['liste_action'] != "")
		{
			// 3  - On décompose la liste des menus et l'on recherche leurs informations dans la table 'menu_contextuel'
			
			$liste_action = explode("-", $row['liste_action']);
			
			$sql = "SELECT * FROM menu_contextuel WHERE id IN (".implode($liste_action, ", ").")";
			$row = $this->database->getAll($sql);
			
			$menus_infos = array();
						
			for ($i=0; $i < count($row); $i++)
			{			
				$menu = $row[$i];				
				$menus_infos[$menu['id']] = array
											(	
												'name' => "'".$menu['nom_action']."'", 
												'className' => "'".$menu['nom_icone']."'", 
												'callback' => "function() {".$menu['url_action'].";}"
											);
			}
			
			// 4 - On fusionne la liste des menus trouvés à l'étape 1 et les informations issues de l'étape 2
						
			for ($i=0; $i < count($liste_action); $i++)
			{
				if (isset($menus_infos[$liste_action[$i]]) || ($liste_action[$i] == 0)) {
					
					if ($liste_action[$i] == 0) // Le menu 0 correspond à un séparateur
					{
						$all_menus[] = array('separator' => "true");
					}
					else 
					{
						$all_menus[] = $menus_infos[$liste_action[$i]];
					}
				}
			}

			// 5 - Si le dernier élément est un séparateur, on le supprime du tableau d'items

			if (isset($all_menus[count($all_menus)-1]['separator'])) {
				array_pop($all_menus);
			}
		}
		else 
		{
			return false;
		}
		
		return $all_menus;
	}
}