<?php

class Parameters {
	
	/**
	 * Tableau des niveaux de topologie
	 * @var array
	 */
	public $topoHeader;
	public $topoHeader3rdAxis;
	public $hasAxe3;
	public $aggregLevel;
	public $codeProduct;
	public $family;
	public $field;
	public $specific_field;
	public $id_group_table;
	public $network;
	public $topoCellsArray;
	public $topo3rdAxisArray;
	public $todo;
	//liste des éléments réseaux (de base) désactivés pour cette famille
	public $deactivated_NE;//false or an array
	
	public function __construct() {
		$this->topoCellsArray = array();
		$this->deactivated_NE = false;
	}

	/**
	 * 
	 * Enter description here ...
	 * @param $ne_base_level identifiant du niveau réseau de base de la famille (Expl cellId pour BSS)
	 * @param $topoInfo tableau associatif des éléments de topo en conformité avec TopoHeader
	 * @param $hour heure du fichier en cours de traitement
	 * @param $ne_id_case casse des éléments réseaux: upperCase (default), lowerCase, rawCase
	 */
	public function addTopologyInfo($ne_base_level_id, $topoInfo,$hour,$ne_id_case="upperCase",$is3rdAxis=FALSE){
		//$ne_base_level_id insensible à la casse
		foreach ($topoInfo as $key => $ne_id) {
			if($ne_id_case=="lowerCase"){
				$ne_id=strtolower($ne_id);
			}elseif($ne_id_case=="rawCase"){
				//no change
			}else{
				$ne_id=strtoupper($ne_id);
			}
			//virtual_ en minuscule
			if(preg_match("/virtual_(.*)/i", $ne_id,$matches)){
				$ne_id="virtual_{$matches[1]}";
			}
			$topoInfo_new[$key]=$ne_id;
		}

		if(!$is3rdAxis){
			$this->topoCellsArray[$hour][strtolower($ne_base_level_id)] = $topoInfo_new;
		}else{
			$this->topo3rdAxisArray[$hour][strtolower($ne_base_level_id)] = $topoInfo_new;
		}
	}
}

/**
 * Enter description here ...
 * @author g.francois
 *
 */
class ParametersList implements Iterator {
/**
	 * 
	 * Index de la liste
	 * @var int
	 */
	private $index;
	/**
	 * 
	 * Liste contenant les objets Parameters
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
	 * Ajoute un objet Parameters à la liste
	 * @param Parameters $param
	 */
	public function add(Parameters $param) {
		$this->list[] = $param;
	}

	/**
	 * 
	 * Récupère l'objet Parameters
	 * @param String $family
	 * @return Parameters
	 */
	public function getWithFamily($family) {
		foreach ($this as $param) {
			if ($param->family == $family) {
				return $param;
			}
		}
		return null;
	}
	
	/**
	 * 
	 * Récupère le ou les objets Counter à partir du compteur trouvé dans le fichier source
	 * Initialise la valeur qui définit le compteur dans le fichier source sous sa propriété nms_field_name_in_file
	 * @param String $nms_field_name
	 * @return array Tableau composé de la liste des compteurs (objet) correspondant à ce $nms_field_name
	 */
	public function getCounterFromFile($nms_field_name, $id_group_table=null) {
		//liste des compteurs trouvés sachant que plusieur compteurs peuvent avoir un meme $nms_field_name
		$countersFound= array();
		$found=false;
		
	
			foreach ($this as $param) {
				if(isset($id_group_table)){
					if($param->id_group_table!=$id_group_table) continue;
				}
				
				foreach ($param->todo as $counters) {
					foreach ($counters as $pos => $counter) {
                        $nms_field_name_array = array_map("strtolower", $counter->nms_field_name);
                        if (in_array(strtolower($nms_field_name), $nms_field_name_array))  {
                            $counter->nms_field_name_in_file = $nms_field_name;
                            $countersFound[]=$counter;
                            $found=true;
                            //return array($pos, $counter);
                        }
                        else{
                            /*cas des compteurs déclinés où nms_field_name contient des "@@".
                             * exemple : nms_field_name = NUMDEST_ANSWERED_CALLS@@DEST_DIR_ID=1&&DEST_TYPE_ID=10
                             * le nms_field_name passé en paramètre est NUMDEST_ANSWERED_CALLS. 
                             * on découpe donc $counter->nms_field_name suivant "@@" pour comparer"
                             */
                            foreach($nms_field_name_array as $nms_field_name_att){
                                if(strpos($nms_field_name_att,"@@")){
                                    $nms_field_name_exp = explode("@@",$nms_field_name_att);
                                    if (strtolower($nms_field_name)==$nms_field_name_exp[0]){
                                        $counter->nms_field_name_in_file = $nms_field_name;
                                        $countersFound[]=$counter;
                                        $found=true;
                                    }
                                }
                            }
                        }
                    }
                }
            }

        if($found==false)
            return null;
        else
            return $countersFound;
    }
    /**
     * 
     * Enter description here ...
     * @param unknown_type $nms_field_name
     * @param unknown_type $todo
     */
	public function getCountersByNmsTable($nms_table) {
		$countersFoundPerTodo=array();
		foreach ($this as $param) {
			foreach (array_keys($param->todo) as $todo) {
				if(preg_match("/_{$nms_table}$/i", $todo)){
					if(count($param->todo[$todo])!=0){
						$countersFoundPerTodo[$todo]=$param->todo[$todo];
					}
				}
			}
		}
		return $countersFoundPerTodo;
    }
    

    /**
     * 
     * Renvoie l'objet Parameters contenant le $todo dans sa liste des $todo
     * @param String $todo
     */
    public function getParamWithTodo($todo) {
        foreach ($this as $param) {
            if (in_array(strtolower($todo), array_map("strtolower", array_keys($param->todo)))) {
                return $param;
            }
        }
        return null;
    }

    /**
     * 
     * Récupère l'objet Parameters
     * @param String $id_group_table
     * @return Parameters
     */
    // TODO : dans la méthode "add", utiliser le $id_group_table comme clé
    public function getWithGroupTable($id_group_table) {
        foreach ($this as $param) {
            if ($param->id_group_table == $id_group_table) {
                return $param;
            }
        }
        return null;
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