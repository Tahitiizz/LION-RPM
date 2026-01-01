<?php
/**
*	Classe permettant de gérer le selecteur
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*
*
*	$selecteur			= le tableau associatif des valeurs du sélecteur
*	$html			= contenu HTML du selecteur à afficher
*
*	les méthodes
*	__construct()		crée le tableau $selecteur
*	dump()			affiche le contenu de $selecteur
*	addBox()			ajoute un <fieldset> au sélecteur
*	display()			affiche le selecteur
*
*/


class selecteur
{

	public $selecteur 	  = array();
	public $max_period	  = 200;
	public $max_top		  = 100;

    // Option Fixed Hour par défaut non présente
    public $fixedHourMode = false;

    // Determine le mode d'affiche du bouton display ('display' ou 'save');
    public static $displayMode = 'display';

	//	function __construct() {	}

/**
*	Cette fonction copie les valeurs dans $this->selecteur
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*	@param	arrray	$aValues est le _tableau_ des valeurs à copier
*	@return 	void		
*/
	function setValues($aValues) {
		if (is_array($aValues)) {
			foreach ($aValues as $key => $val)
				$this->selecteur[$key] = $val;
		} else {
			global $debug;
			if ($debug) {
				echo "<div class='debug'>";
				echo "selecteur->setValues() must be given an array, and not a <strong>".gettype($aValues)."</strong><br/>";
				debug_print_backtrace();
				echo "</div>";
			}
		}
	}
	

/**
*	Cette fonction copie les valeurs dans $this->selecteur uniquement si elles n'existent pas
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*	@param	arrray	$aValues est le tableau des valeurs à copier
*	@return 	void		
*/
	function setDefaults($aValues) {
		foreach ($aValues as $key => $val)
			if (!isset($this->selecteur[$key]))
				$this->selecteur[$key] = $val;
	}
	
/**
*	Cette fonction copie UNE valeur dans $this->selecteur
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*	@param	string		$key est la clé de la valeur à copier
*	@param	string		$val est la valeur de la valeur à copier
*	@return 	void		
*/
	function set($key,$val) {
		$this->selecteur[$key] = $val;
	}
	
/**
*	Cette fonction affiche le contenu de $this->selecteur sous forme d'un tableau. Elle n'est utile que pous le développement.
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*	@param	void
*	@return 	void		
*/
	function dump() {
		echo "<table id='selecteur_posted_array'>";
		echo "<tr><th colspan='2' bgcolor='#6ABFE8'>Content of \$selecteur</th></tr>";
		foreach ($this->selecteur as $key => $val)
			echo "<tr><td bgcolor='#6ABFE8' align='right'>&nbsp;$key:&nbsp;</td><td bgcolor='#E7E7E7'>&nbsp;$val&nbsp;</td></tr>";
		echo "</table>";
	}
	
/**
*	Cette fonction affiche le contenu de $_POST sous forme d'un tableau. Elle n'est utile que pous le développement.
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*	@param	void
*	@return 	void		
*/
	function postDump() {
		echo "<table id='selecteur_posted_array'>";
		echo "<tr><th colspan='2' bgcolor='#6ABFE8'>Content of \$_POST array</th></tr>";
		foreach ($_POST as $key => $val)
			echo "<tr><td bgcolor='#6ABFE8' align='right'>&nbsp;$key:&nbsp;</td><td bgcolor='#E7E7E7'>&nbsp;$val&nbsp;</td></tr>";
		echo "</table>";
	}


	//		==========	analysing POSTED selecteur		==========
	
	
	
	
	
	
	//		==========	DISPLAY selecteur		==========
/**
*	Cette fonction ajoute une boite au sélecteur. La boite est ajoutée au buffer : this->html
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*	@param	string		$title est le titre de la boite. Il est affiché en tant que <legend> du <fieldset> de la boite.
*	@param	string		$file est le nom du module à ajouter. Il est utilisé pour inclure le fichier du module et ainsi provoquer son execution.
*	@param	variable	$params est optionel. Il peut contenir des paramètres à passer au module pour en piloter la génération. On choisit souvent de donner à $params la forme d'un tableau associatif.
*	@param	array		tableau contenant les valeurs à placer dans la box
*	@return 	void		Ajoute le module généré à $this->html
*/
	function addBox($title,$file,$selecteur_values=Array(),$params = '') {
		
		global $niveau0,$repertoire_physique_niveau0;
		
		$file = MOD_SELECTEUR.'php/box_'.$file.'.php';

		// on verifie que le fichier existe bien
		if (!file_exists($file)) {
			$this->html .= "
				<td><fieldset id='selecteur_fieldset_".str_replace('_','',strtolower($title))."'><legend>$title</legend>
				<strong>Error:</strong> file <strong>$file</strong> could not be found.	
					</fieldset></td>";
			return;
		}
		
		ob_start();
		include($file);
		$txt = ob_get_contents();
		ob_end_clean();
	
		$this->html .= "
			<td><fieldset id='selecteur_fieldset_".str_replace('_','',strtolower($title))."'><legend>$title</legend>$txt</fieldset></td>";
	}

/**
*	Cette fonction ajoute le bouton à gauche de l'écran pour afficher / masquer le sélecteur
*
*	@author	BBX - 26/11/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*/	
	function addFilter()
	{
		// on verifie que le fichier existe bien
		$file = MOD_SELECTEUR.'php/box_dashboard_filter.php';
		if (file_exists($file)) {
			include($file);
		}		
	}


/**
*	Cette fonction affiche le selecteur.
*
*	@author	SLC - 26/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*
*	maj 26/11/2008 BBX : ajout d'un div pour contenir le sélecteur
*	maj 05/12/2008 BBX : ajout du style display sur le conteneur du sélecteur
*
*	@param	string		$action est l'URL à laquelle doit être posté le sélecteur. C'est un paramètre optionel. S'il n'est pas spécifié, le sélecteur est posté à l'adresse de la page en cours
*	@return 	void		echo le sélecteur.
*/
	function display($action = '')
    {
		global $niveau0;

        $displayButon = 'selecteur_submit';
        if( self::$displayMode == 'save' )
        {
            $displayButon = 'selecteur_save';
        }

		if (!$action)	$action = $_SERVER["REQUEST_URI"];
		?>

			<link rel="stylesheet" href="<?= URL_SELECTEUR ?>css/selecteur.css" type="text/css" />
			<script type='text/javascript' src='<?=URL_SELECTEUR?>js/selecteur.js' charset='iso-8859-1'></script>
			
			<div id="selecteur_container" style="display:block;">
				<div>
			
			<form id="selecteur" action="<?php echo $action; ?>" method="post" onsubmit="return submit_selecteur();">
				<table id="selecteur_table">
					<tr>
						<?php echo $this->html; ?>
					</tr>
					<tr>
						<td align="center" colspan="4">
                            <span class="texteSelecteur" id="selecteur_submit_info" style="color:#C03000;" ></span>
						</td>
					</tr>
					<tr>
						<td align="center" colspan="4">
							<input type="submit" id="<?= $displayButon ?>" class="boutonDisplay" value="" style="cursor:pointer;" />
						</td>
					</tr>
				</table>
			</form>
			
				</div>
			</div>
			
			<script type="text/javascript">
				<?php
				// maj 21/11/2008 BBX : sauvegarde du répertoire du module en js
				echo 'var _selecteur_url_module = "'.URL_SELECTEUR.'";';
				?>
			</script>

	<?php }

}

?>
