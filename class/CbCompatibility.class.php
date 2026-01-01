<?
/*
*	@cb51000@
*
*	09-09-2011 - Copyright Astellia
* 
*	Composant de base version cb_5.1.5
*
*	09/12/2011 ACS Mantis 837 DE HTTPS support
*	22/12/2011 ACS BZ 25254 test performed when click on "test" different to save test
*	05/05/2015 JLG Mantis 6470 : manage dynamic alarm threshold operand (min/max)
*/
?>
<?php
/**
 * 
 * Gérer la compatibilité des modules entre différentes versions de CB.
 */
class CbCompatibility {
	
	public static $HTTPS_SUPPORT = "https_support";
	public static $ALARM_THRESHOLD_OPERAND_MANAGEMENT = "alarm_threshold_operand_management";
	
    /**
     * Indique si un module est disponible sur le produit considéré.
     * @param String $code code du module
     * @param Int $productId identifiant du produit (valeur par défaut '')
     * @return boolean true if module available, false else.
     */
	// 09/12/2011 ACS Mantis 837 isModuleAvailable => static method
	// 22/12/2011 ACS BZ 25254 test performed when click on "test" different to save test
    public static function isModuleAvailable($code, $productId = '', $db = null) {
    	if ($db == null) {
        	$db = Database::getConnection($productId);
		}
        if($db->doesTableExist('sys_definition_module_availability')){
            $moduleOn = $db->getOne("SELECT sdma_on_off FROM sys_definition_module_availability WHERE sdma_code='{$code}'");
            return ( !empty($moduleOn) && ($moduleOn == 1));
        }
        return false;
    }
    
    /**
     * Retourne la liste des modules disponibles sur le produit
     * @param Int $productId identifiant du produit (valeur par défaut '')
     * @return array liste des module disponibles sur le produit
     */
    public function getAvailableModules($productId=''){
        $db = Database::getConnection($productId);
        $availableModule = array();
        if($db->doesTableExist('sys_definition_module_availability')){
            return $db->getAll("SELECT sdma_code, sdma_label FROM sys_definition_module_availability WHERE sdma_on_off=1");
        }
        else
            return $availableModule;
    }
    
    /**
     * Ajoute un module à la liste des modules disponibles sur le produit courant
     * @param type $code code du module (sans espaces ni caractères spéciaux, le plus simple possible, mais clair, identifiable et unique)
     * @param type $label label du module
     * @param type $comment commentaire sur le module
     * @example $compat->addModule('master51_slave50_compat','Master 5.1 / Slave 5.0 compatibility','Compatibility between a Master with CB 5.1.5 and a slave with CB 5.0.5.10 or higher');
     */
    public static function addModule($code, $label, $comment){
        $db = Database::getConnection();
        $query = "INSERT INTO sys_definition_module_availability (sdma_code, sdma_label, sdma_comment, sdma_on_off) 
                    VALUES('".addslashes($code)."', '".addslashes($label)."', '".addslashes($comment)."', 1)";
        $res = $db->execute($query);
        if($res){
            return true;
        }
        else
            return false;
    }
    
    /**
     * Met à jour la disponibilité du module sur le produit considéré
     * @param String $code code du module
     * @param Int $available disponibilité du module
     * @param Int $productId Identifiant du produit (valeur par défaut '')
     * @return boolean false si le module n’est pas enregistré, true si la mise à jour est effectuée
     */
    public function setModuleAvailability($code, $available, $productId=''){
        $db = Database::getConnection($productId);
        $query = "UPDATE sys_definition_module_availability set sdma_on_off={$available} WHERE sdma_code = '{$code}'";
        $res = $db->execute($query);
        if($res){
            return true;
        }
        else
            return false;
    }
    
    /**
     * Met à jour le label du module sur le produit considéré
     * @param String $code code du module
     * @param String $label label du module
     * @param Int $productId Identifiant du produit (valeur par défaut '')
     * @return boolean false si le module n’est pas enregistré, true si la mise à jour est effectuée 
     */
    public function setModuleLabel($code, $label, $productId=''){
        $db = Database::getConnection($productId);
        $query = "UPDATE sys_definition_module_availability set sdma_label='".addslashes($label)."' WHERE sdma_code = '{$code}'";
        $res = $db->execute($query);
        if($res){
            return true;
        }
        else
            return false;
    }
    
    /**
     * Met à jour le commentaire du module sur le produit considéré.
     * @param String $code code du module
     * @param String $comment String commentaire sur le module
     * @param Int $productId Identifiant du produit (valeur par défaut '')
     * @return boolean false si le module n’est pas enregistré, true si la mise à jour est effectuée 
     */
    public function setModuleComment($code, $comment, $productId=''){
        $db = Database::getConnection($productId);
        $query = "UPDATE sys_definition_module_availability set sdma_comment='".addslashes($comment)."' WHERE sdma_code = '{$code}'";
        $res = $db->execute($query);
        if($res){
            return true;
        }
        else
            return false;
    }
    
    /**
     * Retourne le label du module sur le produit considéré.
     * @param type $code code du module
     * @param Int $productId Identifiant du produit (valeur par défaut '')
     * @return type  label
     */
    public function getModuleLabel($code, $productId=''){
        $db = Database::getConnection($productId);
        if($db->doesTableExist('sys_definition_module_availability')){
            $module = $db->getOne("SELECT sdma_label FROM sys_definition_module_availability WHERE sdma_code='{$code}'");
            return $module;
        }
        return false;
    }
    
    /**
     * Retourne le commentaire du module sur le produit considéré.
     * @param type $code code du module
     * @param Int $productId Identifiant du produit (valeur par défaut '')
     * @return commentaire 
     */
    public function getModuleComment($code, $productId=''){
        $db = Database::getConnection($productId);
        if($db->doesTableExist('sys_definition_module_availability')){
            $module = $db->getOne("SELECT sdma_comment FROM sys_definition_module_availability WHERE sdma_code='{$code}'");
            return $module;
        }
        return false;
    }
	
	/**
	 * Check if evolution 6470 is available
	 * 
	 * 2 cases :
	 * - A gateway and a slave with "alarm_threshold_operand_management" module
	 * - Classic product (not a gateway)
	 *
	 * @param Int $productId product id
	 * @return true if product can manage threshold operand
	 */
	public function canManageThresholdOperand($productId) {
		$isBlankProduct = ProductModel::isBlankProduct(ProductModel::getIdMaster());
		return
			($isBlankProduct && CbCompatibility::isModuleAvailable(CbCompatibility::$ALARM_THRESHOLD_OPERAND_MANAGEMENT, $productId)) ||
			!$isBlankProduct;
	}
}
?>