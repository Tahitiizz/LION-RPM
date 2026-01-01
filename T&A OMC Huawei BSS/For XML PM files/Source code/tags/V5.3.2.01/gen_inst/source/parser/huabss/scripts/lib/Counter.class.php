<?php

class Counter {
	public $id_group_table;
	public $nms_table;
	public $nms_field_name;
	public $nms_field_name_in_file;
	public $edw_field_name;
	public $family;
	public $type;
	public $default_value;
	public $aggregation_formula;
	public $todo;
	public $on_off;
	public $flat_file_position; 
}

class CounterList implements Iterator {
		
	/**
	 * 
	 * Index de la liste
	 * @var int
	 */
	private $index;
	/**
	 * 
	 * Liste contenant les objets Counter
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
	 * Ajoute un objet Counter à la liste
	 * @param Counter $counter
	 */
	public function add(Counter $counter) {
		$this->list[] = $counter;
	}
	
	/**
	 * Récupère le tableau des compteurs faisant partie du groupe donné en paramètre.
	 * @param String $todo
	 * @return array
	 */
	public function getWithTodo($todo) {
		$counters = array();
		foreach ($this as $counter) {
			if (strncmp($counter->edw_field_name, "capture_duration", 16) === 0) { continue; }
			$counterTodo=Tools::getTodoString($counter->family, $counter->nms_table);
			if ($counterTodo == $todo) {
				$counters[] = $counter;
			}
		}
		return $counters;
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
		return $this->list[$this->index]->edw_field_name;
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
		return ($this->index < count($this->list));
	}	
}

?>