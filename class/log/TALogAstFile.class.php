<?php
require_once(  dirname( __FILE__).'/TALogAst.class.php' );

class TALogAstFile extends TALogAst
{
    /**
     * Séparateur de champ dans le fichier de log
     */
    const SEPARATOR = "\t";

    /**
     * Chemin par defaut du fichier de log
     * @var String
     */
    protected $_defaultPath;

    public function __construct( DataBaseConnection $db, $start = 0, $end = 0, $fileMaxSize = 1048576 )
    {
        parent::__construct( $db, $start, $end, $fileMaxSize ); // Appel du constructeur parent
        $appliInfos = $this->_db->getRow( 'SELECT sdp_label,sdp_directory FROM sys_definition_product WHERE sdp_db_name=\''.$this->_db->getDbName().'\';' );
        $this->_defaultPath = '/home/'.$appliInfos['sdp_directory'].'/log/'.$appliInfos['sdp_label'].'_'.time().'.log';
    }

    public function getDefaultPath()
    {
        return $this->_defaultPath;
    }
    
    /**
     * Ecriture des logs dans un flux
     *
     * @abstract
     * @access public
     * @param  String path
     * @return Boolean
     */
    public function createLog( $path = '' )
    {
        /** @var Variable de sortie */
        $retVal = FALSE;

        /** @var File pointer Pointeur sur le fichier de log */
        $handle = NULL;

        /** @var String Chaîne de sortie */
        $output = '';

        // On test si un chemin à été spécifié
        if( strlen( trim( $path ) ) === 0 ){
            $path = $this->_defaultPath;

            // On test si le dossier de log existe, si non on le créé
            if( !is_dir( dirname( $path ) ) ){
                mkdir( dirname( $path ) );
            }
        }

        if( ( is_dir( dirname( $path ) ) === TRUE ) && ( is_writable( dirname( $path ) ) === TRUE ) )
        {
            $handle = fopen( $path, 'w' );
            if( $handle != FALSE ) // Si la création du fichier s'est bien passée
            {
                foreach( $this->getListLog() as $oneLog )
                {
                    $output .= $oneLog['timestamp'].self::SEPARATOR.
                                $oneLog['appli'].self::SEPARATOR.
                                $oneLog['severity'].self::SEPARATOR.
                                $oneLog['msggroup'].self::SEPARATOR.
                                $oneLog['object'].self::SEPARATOR.
                                $oneLog['astlog']."\n";
                }
                fwrite( $handle, $output );
                fclose( $handle );
                $retVal = TRUE;
            }
            else
            {
                // On retournera FALSE
            }
        }
        else
        {
            // On retournera FALSE
        }
        return $retVal;
    }

}
