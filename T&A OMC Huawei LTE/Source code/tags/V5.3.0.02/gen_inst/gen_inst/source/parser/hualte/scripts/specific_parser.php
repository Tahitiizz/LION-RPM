<?php
/**
 * Fichier  qui permet un traitement specifique à chaque parser à la fin du retrieve.
 * 
 * @package Parser_Hualte
 * @author YBT
 * @version 5.3.0.00
 */

//Pour tout les nms_field_name ajotués à l'automatic mapping (sys_field_reference_all), on rajoute un C devant pour éviter des erreurs lors de la création de la colonne dans les tables de données
$db = Database::getConnection();
$query = "update sys_field_reference_all set nms_field_name = concat('C',nms_field_name) where id_ligne in (select id_ligne from sys_field_reference_all where nms_field_name ~* '^[0-9]{7,}')";
$res = $db->executeQuery($query);
	
		
    

?>