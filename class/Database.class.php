<?php
/**
 * 
 *  CB 5.3.1
 * 
 * 22/05/2013 : T&A Optimizations
 */
/*****
*	Cette classe permet de mémoriser les instances de DatabaseConnection créés
*	pour ensuite les réutiliser quand ce là est nécessaire.
*	Celà permet de gagner en performances en limitant le nombre de "new DatabaseConnection()".
*
*	Pour utiliser cette péthode d'émulation des connexions persistantes, utiliser :
*		$database = Database::getConnection($idProd);
*	Au lieu de :
*		$database = new DataBaseConnection($idProd);
*
*****/
class Database
{
    /***
    *	Mémorise les instance de connexion créés
    ***/
    private static $connections = Array();

    /**
    * Fonction permettant de retourner une instance de DatabaseConnection
    * Ajout test de validité pour le BZ 18510
    * @param int $idProd id du produit concerné
    * @param boolean : $readOnly true pour obtenir une connection en lecture seule sur la base
    * @return object : retourne une instance de DatabaseConnection
    */	
    public static function getConnection($idProd = 0, $readOnly = false)
    {	
        // On force $id_prod à 0 si la valeur est vide
        $idProd = empty($idProd) ? 0 : $idProd;

        if ($readOnly) {
            $connectionName = $idProd.'_readonly';
        } else {
            $connectionName = $idProd;
        }

        // Si l'instance existe déjà et qu'elle est valide
        if(self::connectionExists($connectionName) && self::checkConnection($connectionName)) {
            // On retourne l'instance existante
            $database = self::$connections[$connectionName];			
        }
        else {			
            // Sinon on créé l'instance et on la retourne
            $database = self::createConnection($idProd, $readOnly);
        }

        // Retour de l'instance
        return $database;
    }
	
    /**
    * Fonction indiquant si une instance de DatabaseConnection pour
    * le produit donné existe déjà ou non
    * @param int $idProd id du produit concerné
    * @return retourne la valeur du parametre ou false
    */	
    public static function connectionExists($idProd)
    {
        return isset(self::$connections[$idProd]);
    }

    /**
    * Fonction qui créé une nouvelle instance de DatabaseConnection
    * et la mémorise
    * @param int $idProd id du produit concerné
    * @param boolean $readOnly true pour obtenir une connexion en lecture seule sur la base
    * @return object : instance de DatabaseConnection
    */
    public static function createConnection($idProd, $readOnly = false)
    {					
        $database = new DataBaseConnection($idProd, $readOnly);		

        if ($readOnly) {
            $idProd.='_readonly';
        }

        self::$connections[$idProd] = $database;
        return $database;
    }

    /**
    * Fonction qui détruit une instance de DatabaseConnection
    * @param int $idProd id du produit concerné
    */	
    public static function dropConnection($idProd)
    {
        if(self::connectionExists($idProd))
            unset(self::$connections[$idProd]);
    }

    /**
     * Vérifie l'état d'une connexion.
     * Détruit les connexions cassées.
     * Ajouté pour le BZ 18510
     * @param int $idProd id du produit concerné
     * @return boolean
     */
    public static function checkConnection($idProd)
    {
        // Récupération de la ressource de connexion.
        $database   = self::$connections[$idProd];

        // Test de la connexion
        if(pg_connection_status($database->getCnx()) === PGSQL_CONNECTION_OK)
            return true;

        // Si connexion cassée, on ferme et détruit la référence
        self::dropConnection($idProd);
        return false;
    }
}

?>