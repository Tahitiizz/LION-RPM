<?php
/**
 * 
 * classe qui permet de décrire une condition entre une colonne de base de donnée et une valeur donnée en spécifiant l'opérateur.
 * @author m.diagne
 *
 */
class FileTypeCondition{
	

	public $column;
	public $value;
	/**
	 * 
	 * operateur liant la colonne à la valeur
	 * @var unknown_type
	 */
	public $operator;
	

	/**
	 * 
	 * operateur qui lie la condition courante à celle de droite (si elle se trouve dans une liste de condition)
	 * @var unknown_type
	 */
	public $operatorInterCondition;
	
	public function __construct($column=NULL, $operator=NULL, $value=NULL) {
		$this->column=$column;
		$this->operator=$operator;
		$this->value=$value;
		$this->offset=NULL;
		$this->limit=NULL;
	}
	
	/**
	 * 
	 * renvoie la condition sous la forme d'une chaine de caractère
	 */
	public function getDBCondition(){
		// dans la colonne hour, ce sont des entiers, donc quotes inutiles
		if($this->column=='hour') return "($this->column $this->operator $this->value)";
		return "($this->column $this->operator '$this->value')";
	}

}

/**
 * 
 * Classe qui sert à exprimer des conditions plus complexes, des listes de conditions. Toutes les conditions peuvent etre construites
 * @author m.diagne
 *
 */


class FileTypeConditionExp extends FileTypeCondition implements Iterator {
	
	
/**
	 * 
	 * Index de la liste
	 * @var int
	 */
	private $index;
	/**
	 * 
	 * Liste contenant les objets FileTypeCondition
	 * @var array
	 */
	private $list;
	
	/**
	 * 
	 * Constructeur
	 */
	public function __construct() {
		parent::__construct();
		$this->list = array();
		$this->index = 0;
	}
	
	/**
	 * 
	 * Ajoute un objet FileTypeCondition à la liste
	 * @param Parameters $param
	 */
	public function add(FileTypeCondition $condition) {
		$this->list[] = $condition;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see parser/nsnnss/scripts/lib/FileTypeCondition::getDBCondition()
	 */
	public function getDBCondition(){
		$expression="";
		$optr="";
		foreach ($this as $condition){
			$expression.= " $optr {$condition->getDBCondition()}";
			$optr=$condition->operatorInterCondition;
		}
		//mise en parenthèse si plus d'une condition
		if(count($this->list)>1) $expression="($expression)";
		return $expression;
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
        return $this->list[$this->index]->family;
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
}
?>