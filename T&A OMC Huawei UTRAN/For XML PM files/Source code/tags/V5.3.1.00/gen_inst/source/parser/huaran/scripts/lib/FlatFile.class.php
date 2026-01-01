<?php
/**
 * 
 * Contient la structure des informations des fichiers sources.
 * Le nom des propriétés est celui des champs de la table sys_definition_flat_file_lib
 * @author g.francois
 *
 */
class FlatFile {	
    public $flat_file_location;
    public $uploaded_flat_file_name;
    public $flat_file_name;
    public $flat_file_template;
    public $prefix_counter;
    public $capture_duration;
    public $hourOfUpload;
    public $special_conf;
	public $hour;
    public function get_special_conf(){
        $confFile = dirname(__FILE__)."/../conf/FlatFile_conf.ini";
        if (file_exists($confFile)){
            $this->special_conf=parse_ini_file($confFile,true);
        }
        else {
            $this->special_conf=0;
            //displayInDemon("Conf file {$confFile} does not exist");
        }
    }
}

/**
 * 
 * Est une liste d'objets FlatFile
 * @author g.francois
 *
 */
class FlatFileList implements Iterator {
	
	/**
	 * 
	 * Index de la liste
	 * @var int
	 */
	private $index;
	/**
	 * 
	 * Liste contenant les objets FlatFile
	 * @var array
	 */
	private $list;
	
	/**
	 * 
	 * Constructeur
	 */
	public function __construct() {
		$this->list = array();
		$this->index = 0;
	}
	
	/**
	 * 
	 * Ajoute un objet FlatFile à la liste
	 * @param FlatFile $flatFile
	 */
	public function add(FlatFile $flatFile) {
		$this->list[] = $flatFile;
	}

	/**
	 * 
	 * Récupère l'objet FlatFile
	 * @param String $uploaded_flat_file_name
	 * @return boolean
	 */
	public function get($uploaded_flat_file_name) {
		$index = $this->isExist($uploaded_flat_file_name);
		if ($index !== false) {
			return $this->list[$index-1];
		}
		return null;
	}
	
	/**
	 * 
	 * Teste l'existance d'un objet FlatFile dans la liste
	 * @param String $uploaded_flat_file_name
	 * @return l'index de l'objet trouvé (le premier élément à l'index 1) ou false si rien n'est trouvé
	 */
	public function isExist($uploaded_flat_file_name) {
		$this->rewind();
		foreach ($this->list as $flatFile) {
			if ($flatFile->uploaded_flat_file_name == $uploaded_flat_file_name) {
				return ($this->index+1);
			}
			$this->next();
		}
		return false;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::rewind()
	 */
	public function rewind() {
		$this->index = 0;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::next()
	 */
	public function next() {
		$this->index++;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::key()
	 */
	public function key() {
		return $this->list[$this->index]->flat_file_name;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::current()
	 */
	public function current() {
		return $this->list[$this->index];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Iterator::valid()
	 */
	public function valid() {
		return $this->index<count($this->list);
	}
	
	/**
	 * Retourne le nombre de fichiers présents dans cette liste.
	 */
	public function countFlatFiles() {
		$nbFlatFiles = 0;
		if(isset($this->list)) $nbFlatFiles=count($this->list);
		return $nbFlatFiles;
	}
}
?>