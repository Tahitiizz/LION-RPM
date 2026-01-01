<?php
/* 
 * 09/10/2012 ACS DE GIS 3D ONLY
 *
 * @cb_v5.1.4
 */

/**
 * Description of GisModel
 * 
 * @author MPR m.peignier
 */
class GisModel
{
    static $_gis_mode;
    static $_gis_display_mode;
    static $_gis_alarm;
    static $_gis_na;

    /**
     * Constructeur
     */
    private function __construct(){}

    /**
     * Clone
     */
    private function __clone(){}

    /**
     * Destructeur
     */
    private function __destruct(){}

    /**
     * Fonction qui indique si le GIS est activé ou non
     * @param <type> $idProd
     * @return <type> 
     */
     // 09/10/2012 ACS DE GIS 3D ONLY
	public static function getGisMode($idProd = "") {
        if ( !isset(self::$_gis_mode[$idProd]) ) {
			// Récupération du paramètre gis_mode qui indique si le GIS est activé ou non
			// 23/09/2011 OJT : le paramètre global est 'gis' et non 'gis_mode'
			$return = (int)get_sys_global_parameters( "gis", 1, $idProd );
			self::$_gis_mode[$idProd] = ( $return == 0 ) ? false : $return;
		}
        return self::$_gis_mode[$idProd];
    } // End function gisIsAvailable()

    /**
     * Fonction qui retourne le Mode d'affichage du GIS / GIS 3D
     * @param Id du produit $idProd
     * @return int $displayMode : Retourne 0 si les polygones sont désactivés / 1 si les polygones sont disponibles
     */
    public static function getGisDisplayMode( $idProd="" )
    {
        if( !isset(self::$_gis_display_mode[$idProd]) )
        {
            //10/10/2012 MMT DE GIS 3D ONLY - si getGisMode == 2 on force le displaymode à 0
            if(self::getGisMode($idProd) == 2){
                self::$_gis_display_mode[$idProd] = 0;
            }else{
                self::$_gis_display_mode[$idProd] = get_sys_global_parameters("gis_display_mode", 1, $idProd);
            
                // Controle sur la valeur du paramètre
                // Si la valeur est fausse on retourne 1
                if( self::$_gis_display_mode[$idProd] != 0 && self::$_gis_display_mode[$idProd] != 1 )
                    self::$_gis_display_mode[$idProd] = 1;
            }
        }
        return self::$_gis_display_mode[$idProd];
    } // End function getGisDisplayMode()

    /**
     * Fonction qui vérifie que les liens vers le GIS et le GIS 3D depuis les alarmes sont disponibles
     * @param integer $idProd
     * @return boolean 
     */
    public static function getGisAlarm( $idProd="")
    {
        if( !isset( self::$_gis_alarm[$idProd]) )
        {
            self::$_gis_alarm[$idProd] = ( get_sys_global_parameters("gis_alarm", 1, $idProd) == 0 ) ? false : true;
        }
        return self::$_gis_alarm[$idProd];
    }

    public static function getNaWithGIS( $idProd="", $family = "" )
    {
        // Si $_gis_na n'existe pas, on l'initialise
        if( !isset( self::$_gis_na[$idProd] ) )
        {
            $family = ( $family == "") ? get_main_family( $idProd ) : $family;
             
            if( self::getGisDisplayMode( $idProd ) == 0 ) 
            {
                self::$_gis_na[$idProd] = array( get_network_aggregation_min_from_family( $family, $idProd ) );  
            }
            else 
            {
                $na = getNaLabelList("na", $family, $idProd );
                self::$_gis_na[$idProd] = $na[$family];
                
            }
        }
        __debug(self::$_gis_na[$idProd],"NA");
        return self::$_gis_na[$idProd];
    }

    /**
     * Fonction qui indique si les liens vers le GIS et GIS 3D sont disponibles
     * @param int $idProd : id product
     * @param string $module : Module en cours = 'alarm' ou 'dash'
     * @param  string $na : NA concerné
     * @return boolean : true si les liens sont dispo / false si ce n'est pas le cas
     */
    public static function linksToGisAvailable( $module, $na, $idProd="" )
    {
        if( !isset( self::$_gis_display_mode[$module][$na][$idProd]) )
        {
            // Le gis est activé ?
            $gisMode  = self::getGisMode($idProd);


            // On check le paramètre gis_alarm lorsqu'on se trouve dans une IHM Alarm Management, Alarm History ou Alarm Top/worst
            $gisAlarm = ( $module == 'alarm' ) ? self::getGisAlarm( $idProd ) : true;


            // Si un des deux paramètres est à 0, on retourne false
            if( !( $gisAlarm && $gisMode) )
            {
                self::$_gis_display_mode[$module][$na][$idProd] = false;
                return self::$_gis_display_mode[$module][$na][$idProd];
            }

            // Récupération du mode d'affichage
            // 2011/09/20 OJT : bz23546, ajout de l'identifiant produit
            $gisDisplayMode = self::getGisDisplayMode( $idProd );

            // Si mode d'affichage = 1, les liens vers GIS et GIS 3D sont disponibles quelquesoit le NA
            if( $gisDisplayMode == 1 && array_key_exists($na, self::getNaWithGIS( $idProd) ) )
            {
                self::$_gis_display_mode[$module][$na][$idProd] = true;
                return self::$_gis_display_mode[$module][$na][$idProd];
            }

            // Récupération du NA minimum de la famille principale
            // Le NA min possède les coordonnées
            // 2011/09/20 OJT : bz23546, ajout de l'identifiant produit
            $naMinimum = get_network_aggregation_min_from_family( get_main_family( $idProd ), $idProd );
            
            // Liens vers GIS et GIS 3D disponibles uniquement si le NA = NA min
            if( $na == $naMinimum )
            {
                self::$_gis_display_mode[$module][$na][$idProd] = true;
                return self::$_gis_display_mode[$module][$na][$idProd];
            }
            self::$_gis_display_mode[$module][$na][$idProd] = false;
            return self::$_gis_display_mode[$module][$na][$idProd];
        }
        return self::$_gis_display_mode[$module][$na][$idProd];
    } // End function linksToGisAvailable()
}
?>
