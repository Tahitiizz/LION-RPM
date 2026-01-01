<?
/*
*	@cb22014@
*
*	18/06/2007 - Copyright Acurio
*
*	Composant de base version cb_2.2.0.14
*
*	- maj 18/07/2007 Gwénaël : ajout d'une fonction qui permet de vérifier si la couleur spécifié par le customisateur est possible dans le cas contraire la couleur par défault sera prise
*	- maj 02/07/2007 Gwénaël : prise en compte d'un onglet sur plusieurs ligne si celui-ci est trop long (testé uniquement avec un onglet sur 2 lignes)
*
*/
?>
<?php
/**
 * Cette classe hérite de la class GraphTabTitle de la librairie JPGraph. Elle permet d'afficher le texte d'un onglet avec plusieurs couleurs et styles différents.
 * De plus gère le retour à la ligne dès que le texte dépasse du graphe.
 *
 * ATTENTION : le retour à la ligne se fait par bloc de texte (un bloc correspond à une partie de l'onglet)
 *
 * Exemple 1 :
 *	$graph->tabtitle = new GraphTabTitleWithColor();
 *	$monOnglet = array (
 *		0 => array( 'text' => 'Le titre', 'color' => 'red', 'font_size' => 20),
 *		1 => array( 'text' => 'de', 'color' => 'yellow', 'font_style' => FF_BOLD),
 *		2 => array('text' => ' mon onglet',  'color' => 'yellow', 'font_style' => FS_ITALIC, 'font_size' => 15, 'font_family' => FF_COMIC)
 *	);
 *	$graph->tabtitle->Set($monOnglet);
 *
 * Exemple 2 :
 *	$graph->tabtitle = new GraphTabTitleWithColor();
 *	$monOnglet = array (
 *		0 => array('text' => 'Le titre' ),
 *		1 => array('text' => ' de', 'color' => 'red'),
 *		2 => array('text' => ' mon onglet')
 *	);
 *	$graph->tabtitle->Set($monOnglet);
 *	$graph->tabtitle->SetColor('blue'); // Le texte sera bleu sauf le mot "de" qui sera rouge
 *	$graph->tabtitle->SetFont(FF_ARIAL, FS_BOLD, 14); // Tout le texte sera en Arial, en gras et de taille 14
 */
class GraphTabTitleWithColor extends GraphTabTitle{
	/**
	 * Tableau contenant les différentes parties de l'onglet
	 *
	 * @var array
	 */
	var $tabTible;

	/**
	 * Alignement verticale
	 *
	 * @var string
	 */
	var $valign='bottom';

	/**
	 * Tableau contenant la liste des couleurs possibles
	 *
	 * @var array
	 */
	var $colors;

	/**
	 * Nom de la couleur par défault
	 *
	 * @var string
	 */
	var $default_color = 'orangered';

	/**
	 * Constructeur
	 */
	function GraphTabTitleWithColor () {
		parent::GraphTabTitle();

		// modif 18/07/2007 Gwénaël
			// Récupère la liste des couleurs possibles pour le nom de la couleur
		$rgb = new RGB();
			$this->colors = $rgb->rgb_table;
		unset($rgb);
	} // End function GraphTabTitleWithColor

	/**
	 * Spécifie le texte de l'onglet. Le paramètre passé peut être soit un tableau ou soit un texte (onglet classique, cf. class GraphTabTitle de JPGraph).
	 * Le tableau doit être de la forme clé => valeur. Où la clé est le texte qui sera affiché. La valeur permet de lui spécifier son style doit être aussi un tableau de la forme clé => valeur.
	 * Où la clé est le style que l'on veut changer ( color / font_size / font_family / font_style ) et la valeur est la valeur du style. Pour les valeurs possibles voir la doc JPGraph
	 * Les style sont optionnels, si un ou plusieurs styles ne sont pas précisés, la valeur par défaut sera sélectionné.
	 *
	 * Il est possible de mettre un style par défaut pour tous les textes en utilisant les fonctions SetColor, SetFont (cf doc JPGraph pour plus d'info)
	 *
	 * Exemple:
	 *	array (
	 *		0 => array('text' => 'Vive ', 'color' => 'red', 'font_size' => 20),
	 *		1 => array('text' => 'le ', 'color' => 'yellow', 'font_style' => FF_ITALIC),
	 *		2 => array('text' => 'PHP ', 'color' => 'yellow', 'font_style' => FS_ITALIC, 'font_size' => 15, 'font_family' => FF_COMIC),
	 *		3 => array('text' => '5');
	 *	)
	 *	// Affiche : Vive le PHP 5
	 * @param mixed $t
	 */
	function Set ( $t ) {
		$this->tabTible = $t;
		if ( is_array($this->tabTible) ) {
			// on récupère tout le text affiché dans le titre afin d'avoir une valeur non null pour $this->t
			// sinon problème lors de la création du graph.
			$text = array();
			foreach ( $this->tabTible as $title )
				$text[] = $title['text'];
			$this->t = implode(' ', $text);
		}
		else
			$this->t = $t;
		$this->hide = false;
	} // End function Set



	// MaJ SLC 28/08/2008 - on a besoin de savoir la taille du tabTitle avant le stroke() pour sécuriser les marges du graphe
	function computeSize()
	{
		$this->calculated_width = 0;
		$this->calculated_height = 0;

		if ( $this->hide )
		    return;

	    /* Le texte spécifié n'est pas un tableau, on est dans le cas d'un onglet classique
		if ( !is_array($this->tabTible) ) {
			parent::Stroke($aImg);
			return;
		}
		*/

		// Calcule la longeur exacte de l'onglet en fonction de chaque partie du titre
		$posX = array(); // contient la largeur de chaque texte
		$posY = array(); // contient la hauteur de chaque texte
		$maxWidthTab = -1;
		$tmpSum_posX = 0;
		$nbLine = 1; // nombre de ligne dans l'onglet du graphe
		$widthGaph = $aImg->width - ( $aImg->left_margin * 2 );
		foreach ( $this->tabTible as $index => $title ) {
			$wt = new Text($title['text']);
			$this->_set_font($wt, $title);
			$posX[$index] = $wt->GetWidth($aImg); // mémorise la largeur du texte
			$posY[$index] = $wt->GetTextHeight($aImg); // mémorise la hauteur du texte

			// modif 02/07/2007 Gwénaël
				// prise en compte d'un onglet sur plusieurs ligne
			if ( $tmpSum_posX + $wt->GetWidth($aImg) >= $widthGaph ) {
				if ( $tmpSum_posX > $maxWidthTab )
					$maxWidthTab = $tmpSum_posX;
				$tmpSum_posX = 0;
				$nbLine++;
			}
			else
				$tmpSum_posX += $wt->GetWidth($aImg);
		}
		unset($tmpSum_posX);

		$this->align = 'left';//fixe l'alignement
		$this->boxed = false;
		// modif 02/07/2007 Gwénaël
				// prise en compte d'un onglet sur plusieurs ligne
		if ( $maxWidthTab == -1 )
			$w = array_sum($posX) + 2 * $this->posx; // calcule la longueur de l'onglet
		else
			$w = $maxWidthTab + 2 * $this->posx; // calcule la longueur de l'onglet
		$h = ( max($posY) + 2 * $this->posy ) * $nbLine; // calcule la hauteur de l'onglet

		$this->calculated_width = $w;
		$this->calculated_height = $h;

		var_dump($this->calculated_width);exit;
	}


	/**
	 * function private
	 */
    function Stroke ( &$aImg ) {
		if ( $this->hide )
		    return;
		// Le texte spécifié n'est pas un tableau, on est dans le cas d'un onglet classique
		if ( !is_array($this->tabTible) ) {
			parent::Stroke($aImg);
			return;
		}

		// Calcule la longeur exacte de l'onglet en fonction de chaque partie du titre
		$posX = array(); // contient la largeur de chaque texte
		$posY = array(); // contient la hauteur de chaque texte
		$maxWidthTab = -1;
		$tmpSum_posX = 0;
		$nbLine = 1; // nombre de ligne dans l'onglet du graphe
		$widthGaph = $aImg->width - ( $aImg->left_margin * 2 );
		foreach ( $this->tabTible as $index => $title )
        {
			$wt = new Text($title['text']);
			$this->_set_font($wt, $title);
			$posX[$index] = $wt->GetWidth($aImg); // mémorise la largeur du texte
			$posY[$index] = $wt->GetTextHeight($aImg); // mémorise la hauteur du texte
			// 02/07/2007 GHX : Prise en compte d'un onglet sur plusieurs ligne
			if ( ( $tmpSum_posX + $posX[$index] ) >= $widthGaph * 0.95 )
            {
        		$tmpSum_posX = $posX[$index];
				$nbLine++;
			}
			else
            {
                $tmpSum_posX += $posX[$index];
            }
            $maxWidthTab = max( $maxWidthTab, $tmpSum_posX );
		}
		unset( $tmpSum_posX );
		$this->align = 'left';//fixe l'alignement
		$this->boxed = false;
		// modif 02/07/2007 Gwénaël
				// prise en compte d'un onglet sur plusieurs ligne
		if ( $maxWidthTab == -1 )
        {
			$w = array_sum($posX) + 2 * $this->posx; // calcule la longueur de l'onglet
        }
		else
        {
			$w = $maxWidthTab + 2 * $this->posx; // calcule la longueur de l'onglet
        }

		$h = ( max( $posY ) + 2 ) * $nbLine + 2 * $this->posy; // calcule la hauteur de l'onglet
		$x = $aImg->left_margin;
		// 2010/08/23 - MGD - BZ 15467 : Le décalage par rapport au  nombre de ligne ne sert à rien ici
		//$y = $aImg->top_margin + ( ( $nbLine - 1 ) * 3 );
		$y = $aImg->top_margin;
		$p = array($x, $y,
			   $x, $y-$h+$this->corner,
			   $x + $this->corner,$y-$h,
			   $x + $w - $this->corner, $y-$h,
			   $x + $w, $y-$h+$this->corner,
			   $x + $w, $y);
		$aImg->SetTextAlign('left','top');
		$x += $this->posx;
        $y = $y - $h + $h / ( $nbLine + 1 );

		$aImg->SetColor($this->fillcolor);
		$aImg->FilledPolygon($p);

		$aImg->SetColor($this->bordercolor);
		$aImg->Polygon($p, true);

		$indexPrevious = null;
		$_x = $x;
		$numLine = 1;

		$offset_Y = max( $posY ) + 2;
		foreach ( $this->tabTible as $index => $title ) {
			// Change les coordonnées
			if ( $indexPrevious !== null )
				$_x += $posX[$indexPrevious];

			// modif 02/07/2007 Gwénaël
				// prise en compte d'un onglet sur plusieurs ligne
			if ( $_x + $posX[$index] >= $widthGaph && $numLine < $nbLine )
            {
				$_x = $x;
				$y += $offset_Y;
                $numLine++;
			}

			$_y = $y - round($posY[$index]/2);
			// si c'est un .  ,  ; tout seul avec ou sans espace avant/après on change sa position pour le placer plus bas
			if ( preg_match('/^( )*[,;\.]( )*$/', $title['text']) )
				$_y += round(max($posY)/2)-1;
			// si le texte contient un des caractères suivants _ ( ) { } [ ] , ; | on baisse d'un 'cran' le text
			// ou si c'est simplement le caractère  - sans rien d'autre
			elseif ( preg_match('/[gjpqy_\(\)\[\]\{\}\|,;]/', $title['text']) || preg_match('/^( )*[-=]( )*$/', $title['text']) )
				$_y++;

			//On ajoute la partie du titre
			$this->_set_font($aImg, $title);
			$aImg->StrokeText($_x, $_y, $title['text'], 0, 'bottom');

			$indexPrevious = $index;
		}
	} // End function Stroke

	/**
	 * function private
	 *
	 * Applique le style du titre sur l'élément passé en paramètre
	 *
	 * @param mixed @aImg
	 * @param array $title
	 */
	function _set_font ( &$aImg, $title ) {
		// style par défault
		$color       = $this->color;
		$font_family = $this->font_family;
		$font_style  = $this->font_style;
		$font_size   = $this->font_size;

		// si le style a été définit, on le change
		if ( isset($title['color']) )
			$color = $this->_check_color($title['color']); // modif  18/07/2007 Gwénaël : Vérification de la couleur
		if ( isset($title['font_family']) && !empty($title['font_family']) )
			$font_family = $title['font_family'];
		if ( isset($title['font_style']) && !empty($title['font_style']) )
			$font_style = $title['font_style'];
		if ( isset($title['font_size']) && !empty($title['font_size']) )
			$font_size = $title['font_size'];

		// applique le style
		$aImg->SetColor($color);
		$aImg->SetFont($font_family, $font_style, $font_size);
	} // End function _set_font

	/**
	 * Teste si la couleur existe pour dans la liste des nom sinon prend la couleur par défaut et renvoye la couleur
	 *
	 * @return mixed
	 */
	function _check_color ( $c ) {
		if ( isset($this->colors[$c]) ) // la couleur existe
			return $c;
		elseif ( substr($c, 0, 1) == "#" ) // couleur au format hexadécimal
			return $c;

		return $this->default_color;
	} // End function _check_color
} // End class GraphTabTitleWithColor
?>