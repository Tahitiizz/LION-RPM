<?php
/*
	- 11/12/2008 GHX : création du fichier
*/
?>
<?php
/**
 * Cette classe permet de centralisé les fonctions communes aux différents classes de Mapping
 *
 * @author : GHX
 * @version CB4.1.0.00
 * @since CB4.1.0.00
 */
abstract class MappingAbstract
{
	/**
	 * Permet d'activer le mode de débuggage
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var int
	 */
	protected $_debug = 0;
	
	/**
	 * Ressource de connexion à la base de données
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var Ressource
	 */
	protected $_db;
	
	/**
	 * Tableau contenant les entêtes Astellia
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	protected $_headerAstellia;
	
	/**
	 * Tableau contenant les noms des trois colonnes de l'entête.
	 *
	 *  Les noms des colonnes correspondent aux messages suivantes des id  de la table sys_definition_message_display
	 *
	 *	A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_MAPPED : nom de la colonne correspondant aux identifiants réseau du produit mappé
	 *	A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_CODEQ : nom de la colonne correspondant aux identifiants réseau du master topologie
	 *	A_MAPPING_TOPO_COLUMN_LABEL_NA_TYPE : nom de la colonne correspondant aux niveaux d'aggrégations
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @var array
	 */
	protected $_columnsName;
	
	/**
	 * Constructeur
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param Ressource $db Ressource de connexion à la base de données
	 */
	public function __construct ( $db )
	{
		$this->_db = $db;
		
		// Initialisation du tableau contenant le nom des colonnes de l'entête
		$this->_columnsName = array(
				'A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_MAPPED' => __T('A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_MAPPED'),
				'A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_CODEQ' => __T('A_MAPPING_TOPO_COLUMN_LABEL_NA_ID_CODEQ'),  
				'A_MAPPING_TOPO_COLUMN_LABEL_NA_TYPE' => __T('A_MAPPING_TOPO_COLUMN_LABEL_NA_TYPE')
			);
	} // End function __construct
	
	/**
	 * Génère un fichier avec les entêtes possibles d'Astellia et retourne le nom du fichier généré
	 * 
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $dirUpload répertoire upload du produit master
	 * @param string $delimiter délimiteur
	 * @return string
	 */
	protected function topologyHeaderAstellia ( $dirUpload, $delimiter )
	{
		$fileHeaderAstellia = uniqid('headerAstellia', true).'.mapping';
		
		$queryHeaderAstellia = sprintf("
						COPY (SELECT DISTINCT eorh_id_column_db, eorh_id_column_file FROM edw_object_ref_header WHERE position('_label' in eorh_id_column_db) = 0) 
						TO '%s'
						WITH DELIMITER '%s'
						NULL ''
					",
					$dirUpload.$fileHeaderAstellia,
					$delimiter
				);
		
		$this->_db->executeQuery($queryHeaderAstellia);
		
		return $fileHeaderAstellia;
	} // End function topologyHeaderAstellia
	
	/**
	 * Permet d'activer ou de désactiver le mode débug
	 *	0 : désactivé
	 *	1 : activé
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param int $debug valeur du mode débug
	 */
	public function setDebug ( $debug )
	{
		$this->_debug = $debug;
	} // End function setDebug
	
	/**
	 * Affiche une information uniquement en mode débug
	 *
	 *	cf paramètre debug "mapping" dans la table sys_debug
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param mixed $info information à afficher peut être de n'importe quel type (array, int, string, boolean...)
	 * @param string $title titre de l'information à afficher, permet d'identifier facilement les informations affichés (par défaut nul)
	 */
	protected function debug ( $info, $title = null )
	{
		if ( !$this->_debug )
			return;
		
		if ( $title !== null )
		{
			echo '<u><b>'. $title .' : </b></u><br/>';
		}
	
		echo '<pre>';
		switch( gettype($info) )
		{
			case 'bool' :
			case 'boolean' :
				echo ( $info ? 'TRUE' : 'FALSE' );
				break;

			case 'float' :
			case 'double' :
			case 'int' :
			case 'integer' :
			case 'string' :
				echo $info;
				break;

			case 'NULL' :
				echo 'NULL';
				break;

			default:
				print_r($info);
		}
		echo '</pre>';
	} // End function debug
	
	/**
	 * Exécute une commande Linux et retourne le résultat
	 *
	 * @author : GHX
	 * @version CB4.1.0.00
	 * @since CB4.1.0.00
	 * @param string $cmd commande Linux à exécuter
	 * @param boolean $deleteComment supprimer les commentaires de la commande et les retours à la ligne (sert pour les commandes awk écrit sur plusieurs lignes)
	 * @return array
	 */
	protected function exec( $cmd, $deleteComment = false )
	{
		if ( $deleteComment == true )
		{
			// On supprime les commentaires
			$cmd = preg_replace('/(.*)#.*/', '\1', $cmd);
			// Supprime les tabulations et  les retours à la ligne
			$cmd = preg_replace('/\s\s+/', ' ', $cmd);
		}
		
		$startExec = microtime(true);
		
		@exec($cmd, $result, $error);
		
		if ( $error )
		{
			printf('<br /><span style="color:black; background-color:red;">ERROR : %s</span><br /><br /><br />', $cmd);
			throw new Exception('Error during execution');
		}
		
		if ( $this->_debug & 2 )
		{
			$endExec = microtime(true);
			printf('<br /><span style="color:yellow; background-color:black;">%s</span><br />> Temps d\'exec : %d secondes<br /><br />', $cmd, ($endExec - $startExec));
		}
	
		return $result;
	} // End function exec
	
} // End class MappingAbstract
?>