<?php
/**
 * Classe de gestion des règles de remplacement des caractères en topologie
 *
 * 13/05/2011 NSE DE Topology characters replacement : Création de la classe
 *
 */
class TopologyCharactersReplacementRules {
    /**
     * Instance de connexion sur une base données
     * @var DatabaseConnection
     */
    private $_db = null;
    
    /**
     * Identifiant du produit
     * @var int
     */
    private $_productId = null;
    
    /**
     * Identifiant du produit master
     * @var int
     */
    private $_masterId = null;
    
    /**
     * Tableau des règles de rempalcement concernant les codes
     * @var array
     */
    private $_codesRules = array();
    
    /**
     * Tableau des règles de rempalcement concernant les labels
     * @var array
     */
    private $_labelsRules = array();
    
    /**
     * Chaîne indiquant que le caractère ne doit pas être remplacé
     * @var string
     */
    const NO_REPLACEMENT_STRING = '<noRempChar>';
    
    /**
     * Constructeur
     *
     * @author NSE
     * @param int $idProduct identifiant du produit (default master product)
     * @param int $idMaster identifiant du master (default master product)
     */
    public function __construct ( $idProduct=0, $idMaster=0 ) {
        $this->_productId = $idProduct;
        $this->_masterId = $idMaster;
        
        // Pour ne pas avoir de soucis si la valeur de $idProduct est vide on force la valeur de l'ID du master
        if ( is_null($idProduct) || empty($idProduct) || $idProduct == 0 )
            $this->_productId = ProductModel::getIdMaster();
        if ( is_null($idMaster) || empty($idMaster) || $idMaster == 0 )
            $this->_masterId = ProductModel::getIdMaster();

        $this->_db = DataBase::getConnection( $this->_productId );
        
        // initialisation des tableaux avec les expressions réservées par le CB
        $sep_axe3 = taCommonFunctions::get_sys_global_parameters( 'sep_axe3', '', $this->_productId);
        $tabReserved = array('|s|','||','|t|');
        if(!empty($sep_axe3) && !in_array($sep_axe3, $tabReserved))
            $tabReserved[] = $sep_axe3;
        
        foreach ($tabReserved as $reserved){
            $this->_codesRules[]['from'] = $reserved ;
            $this->_codesRules[sizeof($this->_codesRules)-1]['to'] = '_' ;
            $this->_labelsRules[]['from'] = $reserved ;
            $this->_labelsRules[sizeof($this->_labelsRules)-1]['to'] = '_' ;
        }
    } // End function __construct
    
    /**
     * Charge les règles de remplacement des caractères dans les variables 
     * codesRules et labelsRules, à partir de la base de données.
     * @exception: si aucune table de règle n'est trouvée (patch appliqué partiellement)
     */
    public function loadRules() {
        // si la table existe sur le produit
        if($this->_db->doesTableExist('sys_definition_topology_replacement_rules')){
            $query = "SELECT * FROM sys_definition_topology_replacement_rules";
            $result = $this->_db->getAll( $query );
            // si le contexte ne défini pas de règles.
            if(empty($result)){
                // on utilise celles du CB
                $query = "SELECT * FROM sys_definition_topology_replacement_rules_default";
                $result = $this->_db->getAll( $query );
            }
        }
        elseif($this->_db->doesTableExist('sys_definition_topology_replacement_rules_default')){
            // si la table existe sur le CB
            $query = "SELECT * FROM sys_definition_topology_replacement_rules_default";
            $result = $this->_db->getAll( $query );
        }
        elseif(isset($this->_masterId) && !empty($this->_masterId) && ($this->_masterId != $this->_productId) ){
            // vérification sur le master
            $query = "SELECT * FROM sys_definition_replacement_rules_default";
            $db = DataBase::getConnection( $this->_masterId );
            $result = $db->getAll( $query );
        }
        else{
            throw new Exception("No characters replacement rules found.");
        }
        foreach($result as $rule) {
            if($rule['sdtrr_code']!=self::NO_REPLACEMENT_STRING){
                $this->_codesRules[]['from'] = $rule['sdtrr_character'];
                $this->_codesRules[sizeof($this->_codesRules)-1]['to'] = $rule['sdtrr_code'] ;
            }
            if($rule['sdtrr_label']!=self::NO_REPLACEMENT_STRING){
                $this->_labelsRules[]['from'] = $rule['sdtrr_character'];
                $this->_labelsRules[sizeof($this->_labelsRules)-1]['to'] = $rule['sdtrr_label'] ;
            }
        }
    }
    
    /**
     * Vérifie la validité des règles
     * La seule invalidité répertoriée consiste à remplacer aucun caractère par un caractère : '' -> 'x'.
     * @exception: si une règle mal formée est rencontrée
     */
    public function checkRules(){
        $errorInRule = array();
        foreach($this->_codesRules as $rule) {
            if( empty($rule['from']) && !empty($rule['to']) ){
                $errorInRule[] = " - '".$rule['from']."' replaced by '".$rule['to']."' in codes";
            }
        }
        foreach($this->_labelsRules as $rule) {
            if( empty($rule['from']) && !empty($rule['to']) ){
                $errorInRule[] = " - '".$rule['from']."' replaced by '".$rule['to']."' in labels";
            }
        }
        if(empty($errorInRule))
            return true;
        else
            throw new Exception("Following characters replacement rules are incorrect: <br>".implode('<br>',$errorInRule));
    }

    /**
     * Supprime des règles des tableaux de remplacement en fonction du paramètre passé.
     * Permet de ne pas effectuer les remplacements qui mettent en jeu le délimiteur du fichier de Topo.
     * @param String toIgnore: caractère à ignorer
     * @param String column: colonne dans laquelle le caractère doit être ignoré (from ou to). Si non renseigné, ignore dans les 2.
     * @param String rules: tableau des règles dans lequel ignorer le caractère (code ou label). Si non renseigné, ignore dans les 2.
     * @exception si les arguments 'column' ou 'rules' sont incorrects
     */
    public function IgnoreRules($toIgnore, $column='', $rules=''){
        if(empty($column))
            $tabColumns = array('from','to');
        elseif($column=='from')
            $tabColumns = array('from');
        elseif ($column=='to') 
            $tabColumns = array('to');
        else{
            throw new InvalidArgumentException("Parameter 'column 'is not valid. Given $column, expected <empty>, 'from', 'to'");
        }
        if(!empty($rules)&&($rules!='code')&&($rules!='label') ){
            throw new InvalidArgumentException("Parameter 'rules' is not valid. Given $rules, expected <empty>, 'code' or 'label'");
        }
        
        $toDelete = array();
        if(empty($rules) || $rules=='code'){
            // pour toutes les règles portant sur le code
            for($i=0;$i<sizeof($this->_codesRules);$i++) {
                // pour toutes les colonnes concernées
                foreach($tabColumns as $column) {
                    // si la chaîne à ignorer fait partie de la chaîne de la cellule courante
                    if(strpos($this->_codesRules[$i][$column],$toIgnore) !== false){
                        // on mémorise qu'il faudra supprimer cette cellule
                        $toDelete[] = $i;
                    }
                }
            }
        }
        // si on a des éléments à supprimer
        if(!empty($toDelete)){
            // on renverse le tableau pour commencer par supprimer les éléments par la fin du tableau (pour éviter de fausser tous les indices)
            $toDelete = array_reverse($toDelete);
            foreach($toDelete as $i)
                array_splice($this->_codesRules, $i,1);
        }
        
        // idem pour les règles sur les labels
        $toDelete = array();
        if(empty($rules) || $rules=='label'){
            for($i=0;$i<sizeof($this->_labelsRules);$i++) {
                foreach($tabColumns as $column) {
                    if(strpos($this->_labelsRules[$i][$column],$toIgnore) !== false) {
                        $toDelete[] = $i;
                    }
                }
            }
        }
        if(!empty($toDelete)){
            $toDelete = array_reverse($toDelete);
            foreach($toDelete as $i)
                array_splice($this->_labelsRules, $i,1);
        }
    }
    
    /**
     * Optimise les règles de remplacement en regroupant les caractères ayant le même caractère de remplacement.
     *
     */
    public function optimizeRules(){

    }
    
    /**
     * Applique les règles de remplacement contenues dans codesRules à la chaîne passée en paramètre
     * @param String chaine dans laquelle remplacer les caractères
     * @return String chaine corrigée
     */
    public function applyCodeRules($theString){
        if(!empty($this->_codesRules))
            foreach($this->_codesRules as $rule)
                $theString = str_replace($rule['from'], $rule['to'], $theString);
        return $theString;
    }

    /**
     * Applique les règles de remplacement contenues dans labelsRules à la chaîne passée en paramètre
     * @param String chaine dans laquelle remplacer les caractères
     * @return String chaine corrigée
     */
    public function applyLabelRules($theString){
        if(!empty($this->_labelsRules))
            foreach($this->_labelsRules as $rule)
                $theString = str_replace($rule['from'], $rule['to'], $theString);
        return $theString;
    }

    /**
     *
     * @return <Array> tableau des règles de remplacement pour les labels
     */
    public function getLabelRules(){
        return $this->_labelsRules;
    }

    /**
     *
     * @return <Array> tableau des règles de remplacement pour les codes
     */
    public function getCodeRules(){
        return $this->_codesRules;
    }
}
?>
