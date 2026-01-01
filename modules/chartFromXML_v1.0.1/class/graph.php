<?php
/** 	Ce fichier est sencé remplacer tous les includes de la librairie JpGraph.
*
*	@author	SLC - 03/09/2008
*	@version	CB 4.1.0.0
*	@since	CB 4.1.0.0
*	
*	Tuning de JpGraph :
*	Ce fichier contient aussi la liste des modifications apportées à la version originale de JpGraph.
*	L'objectif que nous avions était de surclasser JpGraph et de créer notre propre classe étendue ne renfermant que nos modifications.
*	Celà aurait rendu les évolutions futures de JpGraph plus faciles.
*	Hélas, cela n'a pas été possible, tout d'abord parce que nos principaux problèmes venaient de variables privées innaccessibles depuis l'extérieur de la bibliothèque.
*	Or il n'est pas possible de modifier la visibilité d'une variable private en étendant la classe originale.
*	De plus, JpGraph n'est pas une classe, mais plusieurs dizaines de classes interdépendantes les unes des autres.
*	Enfin, les modifications que nous avions à apporter sont en général des modifications de quelques lignes dans des fonctions originales de plusieurs dizaines de lignes.
*	Pour toutes ces raisons, il a été choisi de modifier directement la classe JpGraph, en modifiant les fichiers originaux.
*	Les 4 modifications sont expliquées en bas de ce fichier.
*/

// on définit où se trouvent les polices True Type Fonts
DEFINE("TTF_DIR",BASE_JPGRAPH."ttf/");

include_once(BASE_JPGRAPH."jpgraph.php");
include_once(BASE_JPGRAPH."jpgraph_line.php");
include_once(BASE_JPGRAPH."jpgraph_scatter.php");
include_once(BASE_JPGRAPH."jpgraph_bar.php");
include_once(BASE_JPGRAPH."jpgraph_canvas.php");
include_once(BASE_JPGRAPH."jpgraph_pie.php");
include_once(BASE_JPGRAPH."jpgraph_pie3d.php");
include_once(BASE_JPGRAPH."jpgraph_log.php");
include_once(MOD_CHARTFROMXML."class/GraphTabTitleWithColor.class.php");


/** changement à effectuer dans la classe JpGraph lors d'un upgrade de la classe :

	jpgraph_legend.inc.php
		- Modification de la propriété des attributes de classes pour en passés certains en public
			AVANT : private $font_family=FF_FONT1,$font_style=FS_NORMAL,$font_size=12;
			APRES MODIF : public $font_family=FF_FONT1,$font_style=FS_NORMAL,$font_size=12;
	jpgraph.php
		- Remplacement de la propriétés private par protected dans la classe GraphTabTitle
	jpgraph_bar.php
		- Modification de la fonction BarPlot::Stroke() & AccBarPlot::Stroke() pour modifier la génération du code <area> pour répondre au besoin de T&A
	jpgraph_pie.php
		- Modification de la fonction PiePlot::AddSliceToCSIM() pour modifier la génération du code <area> pour répondre au besoin de T&A
	jpgraph_pie3d.php
		- Modification de la fonction PiePlot3D::Add3DSliceToCSIM() pour modifier la génération du code <area> pour répondre au besoin de T&A
	jpgraph_plotmark.inc.php
		- Modification des fonctions  PlotMark::AddCSIMPoly() & PlotMark::AddCSIMCircle() & PlotMark::Stroke() pour modifier la génération du <area> pour répondre au besoin de T&A
	jpgraph_stock.php
		- Modification de la fonction StockPlot::Stroke() pour modifier la génération du <area>
	
	**** OLD VERSION ****
	
	-1- rendre public 3 variables dans la classe Legend (définie à la fin de jpgraph.php)
	private $font_family=FF_FONT1,$font_style=FS_NORMAL,$font_size=12;
	devient
	public $font_family=FF_FONT1,$font_style=FS_NORMAL,$font_size=12;
	
	
	-2- dans GraphTabTitle::GraphTabTitle() définie dans jpgraph.php, il faut passer les variables private en protected, pour que la surclasse GraphTabTitleWithColor puisse accéder à ces variables
	private $corner = 6 , $posx = 7, $posy = 4;
	private $fillcolor='lightyellow',$bordercolor='black';
	private $align = 'left', $width=TABTITLE_WIDTHFIT;
	devient
	protected $corner = 6 , $posx = 7, $posy = 4;
	protected $fillcolor='lightyellow',$bordercolor='black';
	protected $align = 'left', $width=TABTITLE_WIDTHFIT;
	
	
	-3- modification du code pour l'intéractivité : les <area> dans les imagemaps
	Il faut, pour cela, insérer le code de gestion des <area> d'Astellia dans les fichiers suivants :
		jpgraph_bar.php
		jpgraph_pie.php
		jpgraph_pie3D.php
		jpgraph_plotmark.inc.php
	Recherche "Astellia" pour trouver les endroits comportant les modifs
	
	
	-4- dans la définition de la classe Plotline, définie dans jpgraph.php, il faut que la variable $legend soit publique, et non pas private.
	private $legend='',$hidelegend=false, $legendcsimtarget='', $legendcsimalt='',$legendcsimwintarget='';
	devient
	public $legend='',$hidelegend=false, $legendcsimtarget='', $legendcsimalt='',$legendcsimwintarget='';
	(c'est pour que la fonction de calcul de largeur de la légende puisse accéder au contenu des légendes des <horizontal_line>)

*/
?>
