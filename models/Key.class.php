<?
/**
 *  5.3.1
 * 
 *  23/04/2013 : Ajout de la méthode getKeyEndDateInfo (dans le cadre de la DE HI License Key)
 * 
 *//**
 *	@cb50414@
 *
 *	07/01/2011 - Copyright Astellia
 *
 *	Composant de base version cb_5.0.4.12
 *
 *	07/01/2011 15:02 SCT : initialisation de variables avant utilisation pour éviter les NOTICES php
?>
<?php
/**
*	Classe permettant de crypter, de décrypter et d'extraire les informations de la clé
*
*	@author	MPR - 11/12/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*
*/
class Key
{

	/**
	* Chaine permettant de crypter ou de décrypter la clé
	* @var string $crypto_key
	*/
	private $crypto_key;
	
	/**
	* Clé décryptée
	* @var string $decrypted_key
	*/ 
	private $decrypted_key;
	
	/**
	*  Niveau d'agrégation de la clé
	* @var string $na_key
	*/
	private $na_key;
	
	/**
	* Nombre d'éléments réseau autorisé par la clé
	* @var string $nb_na_key 
	*/
	private $nb_elems_key;

	public function __construct()
	{
		// Chaine qui sert à décrypter 
		$this->crypto_key = "la sagrada familia est a barcelone";
	}
	
	/**
	* Decryte la chaine passée en paramètre pour la clef de l'application
	* @param $old_version : mettre à vrai si il faut utiliser l'ancien algo de decryptage
	* @ return clé
	*/
	public function Decrypt($string, $old_version = 0) {
		$string = str_replace('-','',$string);
		$string = str_replace('#',' ',$string);
                // 07/01/2011 15:02 SCT : initialisation de la variable result
                $result = '';
		if ($old_version) {
			// on utilise l'ancien algo de decryptage
			// on decode la chaine
			for ($i=1; $i<strlen($string); $i++) {
				$char = substr($string, $i-1, 1);
				// on prend le caractere correspondant dans la cle
				$keychar = substr($this->crypto_key, ($i % strlen($this->crypto_key))-1, 1);
				// on enleve le char correspondant dans la cle, le sel
				$ze_ord = my_ord($char) - my_ord($keychar);
				// on prend le modulo 64
				$ze_ord = ($ze_ord + (4*64)) % 64;
				// on reconverti en caractere
				$result.= my_chr($ze_ord);
			}
		} else {
			// nouvel algo de decryptage
			// on recupere le grain de sel (c'est le dernier caractere)
			$sel = substr($string,-1);
			// on decode la chaine
			for($i=1; $i<strlen($string); $i++) {
				$char = substr($string, $i-1, 1);
				// on prend le caractere precedent
				if ($i>1) {
					$char_avant = substr($result, $i-2, 1);
				} else {
					$char_avant = '0';
				}
				// on prend le caractere correspondant dans la cle
				$keychar = substr($this->crypto_key, ($i % strlen($this->crypto_key))-1, 1);

				// on enleve le char correspondant dans la cle, le sel
				$ze_ord = my_ord($char) - my_ord($keychar) - my_ord($sel) - my_ord($char_avant);
				// on prend le modulo 64
				$ze_ord = ($ze_ord + (4*64)) % 64;
				// on reconverti en caractere
				$result.= my_chr($ze_ord);
			}
		}
		
		$this->decrypted_key = trim($result);
		
		$tab_temp = explode("@", $this->decrypted_key);
		
		$this->nb_elems_key = $tab_temp[0];
		
                // 07/01/2011 15:08 SCT : initialisation de la variable vide tab_temp[1] pour éviter les NOTICES php
                if(!isset($tab_temp[1]))
                  $tab_temp[1] = '';
		$this->na_key = $tab_temp[1];
		
		
	    return $this->decrypted_key;
	} // End function Decrypt
	
	// maj 09/06/2009 - MPR : Correction du bug 9593 - On vérifie que le niveau d'agrégation existe bien sur le produit
	/**
	* Fonction qui vérifie si le niveau d'agrégation existe bien sur le produit
	* @param string $na_in_key
	* @return boolean (true si le na existe false s'il n'existe pas)
	*/
	public function checkNaExistInProduct( $_na, $_product ) {
	
		$database_connection = Database::getConnection( $_product );
		
		$query = " SELECT DISTINCT agregation "
				." FROM sys_definition_network_agregation "
				." WHERE agregation = '{$_na}' AND axe IS NULL"
				." LIMIT 1";
		$res = $database_connection->getOne($query);
		
	
		if( $res != "" ){
			
			return true;
			
		}else{
			
			return false;
		}
			
	}
	
	/**
	* Cryte la chaine passée en paramètre pour la clef de l'application
	* @param string $string : la chaine à crypter
	* @return string : clé cryptée
	*/
	public function Encrypt($string) {
		$result = '';
		// on fait un padding 31
		$string = str_pad($string,31,' ');
		// grain de sel (une lettre prise au hazard)
		$sel = my_chr(rand(1,61));
		// on encode la chaine
		for($i=1; $i<=strlen($string); $i++) {
			// on prend le caractere a crypter
			$char = substr($string, $i-1, 1);
			// on prend le caractere precedent
			if ($i>1) {
				$char_avant = substr($string, $i-2, 1);
			} else {
				$char_avant = '0';
			}
			// on prend le caractere correspondant dans la clef
			$keychar = substr($this->crypto_key, ($i % strlen($this->crypto_key))-1, 1);

			// on ajoute le caractere en clair, le caractere de la cle et le sel
			$ze_ord = my_ord($char) + my_ord($keychar) + my_ord($sel) + my_ord($char_avant);
			// on prend le modulo 64
			$ze_ord = $ze_ord % 64;
			// on convertit en charactere
			$result.=my_chr($ze_ord);
		}
		
		// on ajoute le grain de sel au resultat
		$result .= $sel;
		// on met des tirets tous les 4 caractères
		$j = 0;
		for ($i = 0; $i < strlen($result); $i++) {
			if (!($j % 4)) $result2 .= '-';
			$result2 .= $result[$i];
			$j++;
		}
		
		$result = trim($result2,'-');
		// on remplace les espaces dans la chaine
		$result = str_replace(' ','#',$result);
		
		return $result;
		
	} // End function Encrypt
		
	
	/**
	* Retourne le type nombre d'éléments réseaus autorisés par la dans la clef : c'est le 1er paramètre.
	* @return string $tab_temp[0] : nombre d'éléments réseau autorisé par la clé.
	*/
	public function getNbElemsKey(){
			
		return($this->nb_elems_key);
	} // End function getNbElemsKey
	
	/**
	* Retourne le type d'élément réseau contenu dans la clef : c'est le second paramètre.
	* @return string $tab_temp[1] : niveau d'agrégation de la clé
	*/
	public function getNaKey(){
		
		return($this->na_key);
	} // End function getNaKey
	
        /**
         * Récupère les informations concernant la date d'expiration dans la clef de licence
         * Renvoie 
         *      - null s'il n'y a pas de limitation de date
         *      - un tableau contenant la date d'expiration et le mode (false si évaluation ou true si normal)
         * @return array 
         */
        public function getKeyEndDateInfo(){
            // On va rechercher la date parmis tous les éléments de la clef.
            $tab_elem = explode("@",$this->decrypted_key);

            $E0 = false;
            $unlimited = false;
            for($i=0; $i < count($tab_elem); $i++){

                    if(strlen($tab_elem[$i]) == 8 &&  is_numeric($tab_elem[$i])){
                            // maj maxime : On retire 1 jour à la date d'expiration
                            $unixdate = mktime(6, 0, 0, substr($tab_elem[$i], 4, 2), substr($tab_elem[$i], 6, 2), substr($tab_elem[$i], 0, 4));
                            $day = date('Ymd', $unixdate - 24 * 60 * 60 );

                            if(!isset($date_1)) $date_1 = $day;
                            else $date_2 = $day;
                    }

                    if($tab_elem[$i] == "E0") $E0 = true;
                    if($tab_elem[$i] == "E2") $unlimited = true;
            }
            
            if(!isset($date_1))
                  $date_1 = '';
            if(!isset($date_2))
              $date_2 = '';
            $date = ($date_1 > $date_2) ? $date_1 : $date_2 ;
            
            return $unlimited ? null : array($date,$E0);
        }
        
	/**
	* Fonction qui permet d'afficher la date d'expiration de la clef.
	* @return string $msg : Affichage de la date d'expiration de la clé ou message d'erreur
	*/
	function displayKeyEndDate(){
		
            $dateInfo = $this->getKeyEndDateInfo();
            
            if(!$dateInfo)
                
                return '';
            
            else{
                $date = $dateInfo[0];
                $E0   = $dateInfo[1];
                
		$texte = ($E0) ? "Valid until : " : "Evaluation valid until : " ;
		$style = ($E0) ? "font : normal 8pt Arial, sans-serif; color : #585858;  text-decoration : none; font-style: italic ;" : "font : normal 8pt Arial, sans-serif; color : #FF0000;  text-decoration : none; font-weight : bold ;" ;
                // 07/01/2011 15:08 SCT : initialisation des variables vides date_1 et date_2 pour éviter les NOTICES php

                return "<span style='$style'> $texte".substr($date, 6, 2)."-".substr($date, 4, 2)."-".substr($date, 0, 4)."</span>";
            }
				
	} // End function displayKeyEndDate
	
    /**
     * Determine si un produit gère ou non une clé (Mixed Kpi, Blank Product...)
     *
     * @since  5.1.5.00
     * @param  integer $idProduct Identifiant du produit
     * @return boolean
     */
    public static function isProductManageKey( $idProduct )
    {
        // On retounre false si le produit est un MixedKPI ou un Produit Blanc
        if ( MixedKpiModel::isMixedKpi( $idProduct ) || ProductModel::isBlankProduct( $idProduct ) )
        {
            return false;
        }
        else
        {
            return true;
        }
    }
}
?>