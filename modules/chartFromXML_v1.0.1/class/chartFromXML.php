<?php

/*
 * Since CB 5.1.6.20
 * 
 * 22/05/2012 NSE bz 27162 : correction de la largeur du tableau pour prendre en compte les largeurs des diff?rentes colonnes
 * 18/06/2012 MMT bz 19866 REOPEN calcul pas assez precis, Height = 1 ligne legende = 15 px + 60px marge * 
 */
/*
  10/06/2009 GHX
  Correction du bug BZ9838
  Correction du bug BZ9797
  16/07/2009 GHX
  - Evolution prise en compte du autoScale modif dans la fonction manageNegValues()
  21/07/2009 GHX
  - Correction du BZ 10358 [REC][T&A Cb 5.0][DASH]: affichage des valeurs null dans le dashboard
  -> Si on a des valeurs null on ne met plus zéro à la place
  23/07/2009 GHX
  Correction du BZ 9552 [REC][T&A CB 5.0][AFFICHAGE LEGENDE]: les légendes longues sont coupées
  -> Modif du style de la légende
  03/08/2009 GHX
  - Décodage des éléments html des labels
  - Correction du BZ 7616 [QAL][T&A HPG 4.0]: Mauvais ordre des séries dans le graph HO Causes
  05/08/2009 GHX
  - Correction du BZ 8691 [REC][T&A Core 4.0][GTM BUILDER]: ligne non présente dans légende quand transparence < 100
  06/08/2009 GHX
  - Modification d'une condition dans la fonction makeXaxis() sinon erreur avec array_map si on n'a qu'une seule valeur
  07/08/2009 GHX
  - Correction du BZ 6628 [REC][CB 4.0][RI]: affichage incorrecte quand 1 seule na
  17/08/2009 GHX
  - Ajout de la fonction createImageNoData() pour l'export des rapports
  20/08/2009 GHX
  - Correction du BZ 11167 [REC][T&A Cb 5.0][TP#1][TS#TT1-CB50][TC#37233][Dashboard] : en BH, mauvais affichage du tooltip pour les pie
  21/08/2009 GHX
  - Correction du BZ 11165 [REC][T&A Cb 5.0][TP#1][TS#TT1-CB50][TC#37233][Dash] : pour les graphs de type Pie, la légende est tronqu?e quand la p?riode est importante
  25/08/2009 GHX
  - Correction du BZ 11188 [REC][T&A CB 5.0][DASHBOARD]: pas de message d'ajout dans le caddy
  30/10/2009 GHX
  - Correction du BZ 12359 [REC][T&A Core 5.0][Dashboard] Erreur JpGraph
  06/11/2009 GHX
  - Correction du BZ 12567[REC][T&A CORE 5.0][REPORT]: erreur jpgraph
  04/12/2009 GHX
  - Correction d'un problème avec les accents dans la légende des PIE
  18/12/2009 GHX
  - Prise en compte de valeur par défaut si la couleur d'une série (bordure et/ou fond) n'est pas défini en base
  - Prise en compte que si un type de représentation n'est pas défini ou inconu (bar, line..) on définit automatique une bar
  - Prise en compte d'une valeur par défaut si pour une ligne le type (square / line) est inconnu ou incorrect
  22/12/2009 NSE/GHX
  - Correction BZ 11207 ordre des séries dans la légende
  04/03/2010 NSE bz 14325 :
  - construction du tableau des valeurs dans le cas d'un seul élément observé dans le graphe
  (on ajoute une valeur fictive de chaque côté pour avoir un rendu plus lisible) :
  on ajoute "" au lieu de "-" dans le tableau de valeurs
  09/06/10 YNE/FJT : SINGLE KPI
  30/04/2013 GFS BZ#18500 - [QAL][CB 5.0 / T&A HUAWEI NSS 5.0] [GTM]:For 3 GTMs, the color of all lines and legends become white in some cases.
  27/03/2017 : [AO-TA] formatage des nombres dans les graphs t&a Requirement [RQ:4893]
 */
?>
<?php

/** chartFromXML : classe de génération de graphe (barchart ou piechart) à partir d'un fichier XML
 *
 * 	Cette classe à pour objectif de remplacer les fichiers gtm_stroke_graph.class.php et gtm_stroke_pie.class.php
 * 	Contrairement à ces deux fichiers, elle ne s'occupe que de la génération des graphs, et aucunement de la collecte des informations dans les bases.
 * 	Toutes les informations sont présentes dans le fichier XML.
 *
 * 	@author	SLC - aout 2008
 * 	@version	CB 4.1.0.0
 * 	@since	CB 4.1.0.0
 *
 * 	Les différentes propriétés de la classe sont :
 *
 * 	$this->grinfo			objet SXE		toutes les infos issues du fichier XML (c'est un objet SimpleXMLElement)
 * 	$this->graph			objet JpGraph	l'objet graphique
 * 	$this->has_value			bool			true si possède au moins une valeur non vide --> sinon, on va lancer une erreur "data not found"
 * 	$this->has_bar				bool			true si possède des plots en 'bar' ou 'cumulatedbar'
 * 	$this->has_axeY2			bool			true si possède un axeY2
 * 	$this->has_cumulatedbar_left	bool			true si possède un cumulatedbar à gauche
 * 	$this->has_cumulatedbar_right	bool			true si possède un cumulatedbar à droite
 * 	$this->has_negvalue_left		bool			true si possède une valeur négative à gauche
 * 	$this->has_negvalue_right	bool			true si possède une valeur négative à droite
 * 	$this->legend_height		int			hauteur de la légende	- on ne fixera sa valeur que si legend_position == top ou bottom
 * 	$this->legend_width			int			largeur de la légende 	- on ne fixera sa valeur que si legend_position == left ou right
 * 	$this->legend_nb_col		int			nombre de colonnes de la légende
 * 	$this->plot				array			tableau de tous les plots du graph
 * 	$this->nb_plots				int			nombre de plots dans le graph
 * 	$this->filename				string			nom du fichier image a sauvegarder
 * 	$this->error_is_image		bool			true si les messages d'erreur doivent être renvoyés sous forme d'image
 * 	$this->tabtitle_width		int			largeur totale de tous les textes de tabtitle
 * 	$this->debug				bool			true si est en mode debug
 * 	$this->margin_left			int			marge gauche en pixels
 * 	$this->margin_right			int			marge de droite en pixels
 * 	$this->margin_top			int			marge haute en pixels
 * 	$this->margin_bottom		int			marge basse en pixels
 *
 * 	Le principe de la classe :
 * 		-1- on alimente et manipule $this->grinfo
 * 		-2- on génère le graph avec $this->graph
 * 		
 * 	On se serait bien passé de $this->grinfo, mais le problème est qu'une fois qu'on a créé
 * 	$this->graph avec new Graph(width,height,'auto'), on ne peut plus modifier la taille du graph.
 * 	On a donc ces deux objets principaux :
 * 		$this->grinfo qui contient toutes les données issues du XML, et que l'on peut
 * 		manipuler facilement pour modifier nos graphes.
 * 		$this->graph, l'objet JpGraph, qui n'est créé et utilisé que lors de l'appel à $this->stroke()
 *
 *
 * 	La classe génère par défaut des graphes X/Y, mais peut aussi générer des pie charts (camemberts), et des pie3D
 *
 * 	Les plots des graphs X/Y se répartissent dans 4 catégories :
 * 		- bar (groupbar) (les bars sont automatiquement mis dans un groupbar, parce que sinon ils se chevauchent)
 * 		- line
 * 		- cumulatedbar
 * 		- cumulatedline
 * 	Chacune de ces catégories possède une sous-catégorie : left | right (suivant qu'ils sont associés aux axes Y de droite ou de gauche)
 * 	
 * 	Remarque concernant le positionnement relatif des plots : tous les plots associés à l'axeY2 sont systématiquement derrière les plots
 * 	associés à l'axeY.
 * 	Par contre, les line et cumulatedline sont systématiquement placées devant les bar et cumulatedbar du même axeY.
 * 	
 * 	Pie charts : le principe est de garder exactement la même structure de fichier XML, et qu'en fait le même fichier XML puisse être utilisé pour faire des graphs classiques et des pies.
 * 	Comme un graph classique possède plusieurs plots, et qu'un pie n'accepte qu'une seule liste de valeurs, on spécifiera dans le tag <type>pie</type> quelle série de valeurs doit être utilisée pour générer le pie
 * 	via l'attribut data='' (ex: <type data='2'>pie</type>). Dans l'exemple, on veut utiliser les valeurs de la 3eme série de valeurs. Par défaut, data='0' pour désigner la premi?re série de valeurs.
 *
 * 	SXE : attention, cette classe utilise une surclasse de SimpleXMLElement, nommée SXE disponible sur http://code.google.com/p/sxe/
 * 	SXE is a class extending SimpleXML's SimpleXMLElement.
 * 	It provides the most commonly used DOM functions while preserving SimpleXML's ease of use.
 * 	
 * 	20/03/2009 - modif SPS : gestion des commentaires pour le dashboard et pour le graph
 * 	09/04/2009 - SPS : ajout de propriete pour l'affichage
 * 	07/05/2009 - SPS : ajout de la methode getMap
  05/03/2010 BBX
  - Ajout de la méthode "manageConnections"
  - Utilisation de la méthode "manageConnections" au lieu de DatabaseConnection afin d'éviter les instances redondantes
  08/03/2010 BBX
  - Suppression de la méthode "manageConnections"
  - Utilisation de la méthode "Database::getConnection" à la place.
 * 
 */
// tableau des couleurs des diagrammes camemberts
$pie_colors = array('black', 'red', 'orange', 'green', '#ffeeff', '#00ee00', '#8055F0', '#00FFFF', '#AA00BB', '#cceeBB', '#555555', '#339966', '#ddaaee', '#123456', '#334598', '#AAC4E6', '#FF56B9', '#AA45BB', '#FF32DD', '#1034FF', '#CC2187', '#23EA3F', '#559911', '#FF2376', '#FDFC23', '#F8D112');

class chartFromXML {

    // Mémorise les instances de connexions ouvertes
    private static $connections = Array();

    /** @var integer LEGEND_MAX_LINES Nombres maximum de lignes dans la légende pour un graph (pas PIE) */
    const LEGEND_MAX_LINES = 27;

    /** @var integer PIE_LEGEND_MAX_LENGTH Nombre maximum de caractères de la légende */
    const PIE_LEGEND_MAX_LENGTH = 50;

    /** @var integer PIE_LENGTH_PREFIX_LENGTH Longueur du préfix de la légende (PIE uniquement) */
    const PIE_LEGEND_PREFIX_LENGTH = 30;

    /** @var integer PIE_LEGEND_SUFIX_LENGTH Longueur du suffixe de la légende (PIE uniquement) */
    const PIE_LEGEND_SUFIX_LENGTH = 15;

    /** @var string PIE_LEGEND_DELIMITER Séparateur préfix suffixe da la légende des PIEs */
    const PIE_LEGEND_DELIMITER = '...';

    // 21/11/2011 BBX
    // Correction de messages "Notices" vu pendant les corrections
    public $debug = false;

    /** Constructeur de la classe : on lit le fichier XML de données et on en fait un objet SimpleXMLElement $this->grinfo
     * 	Le constructeur initialise aussi quelques variables.
     * 	@author	SLC - aout 2008
     * 	@version	CB 4.1.0.0
     * 	@param	string		$xmlFile chemin vers le fichier XML contenant les données
     * 	@return	void
     */
    function __construct($xmlFile) {
        // check if file exists
        if (!file_exists($xmlFile)) {
            $this->error("Fatal Error: XML file $xmlFile does not exist.");
            exit;
        }

        $this->xmlFile = $xmlFile;

        // we read the XML file
        $this->grinfo = simplexml_load_file($xmlFile, 'SXE'); // SXE = surclasse de SimpleXML d?finie dans /class/SimpleXMLElement_Extented.php
        if ($this->debug)
            echo "<br />XML file $xmlFile loaded.";

        $this->legend_height = 0; // height of the Legend box	- on ne fixera sa valeur que si legend_position == top ou bottom
        $this->legend_width = 0; // width of the Legend box	- on ne fixera sa valeur que si legend_position == left ou right
        $this->has_cumulatedbar_left = false;
        $this->has_cumulatedbar_right = false;
        $this->has_negvalue_left = false;
        $this->has_negvalue_right = false;
        $this->has_value = false;
        $this->error_is_image = true;
        $this->legend_nb_col = 1;
        $this->debug = false;
        $this->margin_left = 0;
        $this->margin_right = 0;
        $this->margin_top = 0;
        $this->margin_bottom = 0;
    }

    // setters : fonctions qui permettent d'écraser les valeurs passées dans le fichier XML
    function setTitle($val) {
        $this->grinfo->properties->title = $val;
    }

    function setWidth($val) {
        $this->grinfo->properties->width = $val;
    }

    function setHeight($val) {
        $this->grinfo->properties->height = $val;
    }

    function setImgFormat($val) {
        $this->grinfo->properties->img_format = $val;
    }

    function setLegendPosition($val) {
        $this->grinfo->properties->legend_position = $val;
    }

    function setInteractivity($val) {
        $this->grinfo->properties->interactivity = $val;
    }

    function setBaseDir($val) {
        $this->grinfo->properties->base_dir = $val;
    }

    function setBaseUrl($val) {
        $this->grinfo->properties->base_url = $val;
    }

    function setErrorIsImage($val) {
        $this->error_is_image = $val;
    }

    function setAbscisseInterval($val) {
        $this->grinfo->xaxis_labels['interval'] = $val;
    }

    /**
     * 	D?finit le type de graphique ( 'graph' || 'pie' || 'pie3D' ).	$this->setType('pie3D',2) demande de créer un pie3D en utilisant la troisième série de valeurs du fichier XML
     * 	@author	SLC - aout 2008
     * 	@version	CB 4.1.0.0
     * 	@param	string		$val valeur du type de graphique ( 'graph' || 'pie' || 'pie3D' )
     * 	@param	int		$data_index index de la série de valeurs à considérer pour le cas de la génération d'un pie ou d'un pie3D.	$this->grinfo->datas->data[$data_index]
     * 	@return	void
     */
    function setType($val, $data_index = '') {
        $this->grinfo->properties->type = $val;
        if ($data_index != '') {
            $data_index = intval($data_index);
            $this->grinfo->properties->type['data'] = $data_index;
        }
    }

    /** 	Cette fonction va charger un fichier XML ayant des valeurs par défaut.
     * 	Toutes les valeurs de ce fichier qui n'existent PAS dans les données initiales seront ajoutées à$this->grinfo
     * 	@author	SLC - aout 2008
     * 	@version	CB 4.1.0.0
     * 	@param	string		$xmlFile chemin vers le fichier XML contenant les données servant de valeurs par défaut
     * 	@return	void
     */
    function loadDefaultXML($default_xmlFile) {
        // we read the XML of the default file
        $default = simplexml_load_file($default_xmlFile, 'SXE');  // SXE = surclasse de SimpleXML définie dans /class/SimpleXMLElement_Extented.php
        $this->recursiveMergeXMLObjects(&$this->grinfo, $default);
        if ($this->debug)
            echo "<br />XML default file $default_xmlFile loaded.";
        // echo "<hr /><pre>".htmlentities($this->grinfo->asXML())."</pre><hr />";
    }

    /** 	Cette fonction fait une fusion recursive de deux objets XML $a et $b
     * 	$a est passé par référence, et sera donc modifié
     * 	Toutes les valeurs de $b non présentes dans $a seront copiées dans $a
     * 	@author	SLC - aout 2008
     * 	@version	CB 4.1.0.0
     * 	@param	object SXE		&$a est l'objet XML initial dans lequel nous allons insérer toutes les valeurs trouvées dans $b
     * 	@param	object SXE		$b est l'objet XML dans lequel nous allons lire les données (nodes et attributs). Si un node / attribut de $b existe déjà dans $a, on ne fait rien. Si un node / attribut de $b n'existe pas dans $a, on le copie.
     * 	@return	void
     */
    function recursiveMergeXMLObjects(&$a, $b) {
        foreach ($b->children() as $child_name => $child) {
            // si le child n'existe pas, on l'ajoute
            if (!isset($a->$child_name)) {
                $a->appendChild($child);
            } else {
                // on copie le texte du node
                if ((string) $child != '')
                    if ((string) $a->$child_name == '')
                        $a->$child_name->appendText((string) $child);
                // on copie les attributs
                foreach ($child->attributes() as $att_name => $att_value)
                    if ($a->$child_name->attributes()->$att_name == '')
                        $a->$child_name->addAttribute($att_name, $att_value);
                // on recurse sur les enfants de l'enfant
                if (count($child->children()))
                    $this->recursiveMergeXMLObjects(&$a->$child_name, $child);
            }
        }
    }

    /** 	Cette fonction renvoie les erreurs de la classe sous la forme d'une image
     * 	On pourrait décider de loguer les erreurs, ou les envoyer par email ...
     * 	@author	SLC - aout 2008
     * 	@version	CB 4.1.0.0
     * 	@param	string		$str_error message d'erreur
     * 	@return	void
     */
    function error($str_error) {
        if ($this->debug)
            echo "<br />call to: error($str_error)";

        if (!$this->error_is_image) {
            echo "<div class='error'><div class='chartFromXML'>chartFromXML error: $str_error</div></div>";
            exit;
        }

        // $str_error=ucfirst($this->mode)." mode. No data found\n".$this->tab_title;
        $str_error_len = strlen($str_error);

        $this->largeur_graphe = $str_error_len * 8;
        $this->largeur_graphe = min($str_error_len * 12, 950); // issu de gtm_stroke_pie.class.php
        // MODIF DELTA lorsqu'il y a no data found on force une taille par défaut pour le display des graphs
        // Attention il y a un width='100%' rajouté au niveau des balises html du gabarits des graphs, NE PAS L'OUBLIER
        $this->largeur_graphe = 900;
        $this->hauteur_graphe = 100;
        $this->graph = new Graph($this->largeur_graphe, $this->hauteur_graphe, "auto");

        $graph_value[0] = 0;
        $graph_value[1] = 0;
        $this->graph->SetScale('textlin');
        $this->graph->yaxis->Hide();
        $this->graph->xaxis->Hide();
        $line1plot = new LinePlot($graph_value);
        $line1plot->SetColor('#FFFFFF');
        $this->graph->Add($line1plot);

        $str_error = wordwrap($str_error, 100);
        $txt1 = new Text($str_error);
        $txt1->SetPos(0.2, 0.45, "left");
        $this->graph->AddText($txt1);

        $this->graph->Stroke($absolute_filename);
        exit;
    }

    /** 	Cette fonction evalue toutes les chaines d'un tableau en ayant créé toutes les variables issues du contenu et des attributs de 3 objets XML
     * 	C'est grace à cette fonction que l'on ?value les cha?nes href="" onmouseover="" onmouseout="" des tags <data>.
     * 	@author	SLC - aout 2008
     * 	@version	CB 4.1.0.0
     * 	@param	array		$targets	array of strings: tableau de chaines à évaluer			ex: test.php?arg0=$label&amp;arg1=$x&amp;arg2=$y&amp;arg3=$toto&amp;arg4=$tutu
     * 	@param	array		$data tableau de tous les arguments du tag data en cours		ex: <data label="Kpi 1" type="cumulatedbar" line_design="square" fill_color="#FF6600" fill_transparency='0.3' stroke_color="#FF6600" yaxis="right" ...
     * 	@param	object SXE	$label objet SXE correspondant au tag label du point en cours		ex: <label toto="2015">2005</label>
     * 	@param	object SXE	$value objet SXE correspondant au tag value du point actuel		ex: <value tutu="youpi">33</value>
     * 	@return 	array		array des chaines évaluées								ex: test.php?arg0=Kpi 1&amp;arg1=2005&amp;arg2=33&amp;arg3=2015&amp;arg4=youpi
     */
    function evalStringArray($targets, $data, $label, $value) {
        if ($this->debug)
            echo "<br />call to: evalStringArray($targets,$data,$label,$value)";

        // 1 - on crée toutes les variables qui sont suceptibles d'être à substituer dans les chaines à évaluer
        // analyse l'abscisse	(cad le tag <label> correspondant au point actuel)
        $x = (string) $label;
        foreach ($label->attributes() as $att_name => $att_value)
            ${$att_name} = $att_value;
        // analyse de data
        foreach ($data as $att_name => $att_value)
            ${$att_name} = $att_value;
        // analyse l'ordonnées	(cad le tag <value> correspondant au point actuel)
        $y = (string) $value;
        // 17:16 20/08/2009 GHX
        // Correction du BZ 11167
        // On supprime le str_replace
        $y = trim($y);
        // if ($y == '') $y = 0;
        foreach ($value->attributes() as $att_name => $att_value)
            ${$att_name} = $att_value;

        // 2 - on évalue toutes les chaines
        foreach ($targets as $key => $string) {
            // 21/11/2011 BBX
            // Correction de messages "Notices" vu pendant les corrections
            @eval("\$targets[$key] = \"$string\";");
        }
        return $targets;
    }

    /** 	Cette fonction génère et affiche l'image. C'est la fonction la plus lourde de la classe, car c'est elle qui effectue quasiment tout le travail de lecture de $this->grinfo
     * 	@author	SLC - aout 2008
     * 	@version	CB 4.1.0.0
     * 	@param	string		$absolute_filename	adresse du fichier dans lequel l'image doit être sauvegardée. Si vide, stroke() renvoie directement l'image
     * 	@return 	void		
     */
    function stroke($absolute_filename = '') {
        if ($this->debug)
            echo "<br />call to: stroke($absolute_filename)";

        // on reset à zero les marges. C'est utile uniquement quand un même fichier est utilisé pour générer plusieurs images
        $this->margin_left = 0;
        $this->margin_right = 0;
        $this->margin_top = 0;
        $this->margin_bottom = 0;

        // Single Kpi
        $fjt_type = (string) $this->grinfo->properties->type;

        // -1- on génère l'image
        switch ((string) $this->grinfo->properties->type) {
            case 'graph':
            case 'singleKPI':
                // 30/03/10 YNE : Add single KPI mode 
                if ($this->debug)
                    echo "<br />call to new JpGraph::Graph({$this->grinfo->properties->width},{$this->grinfo->properties->height},'auto')";
                $this->graph = new Graph((int) $this->grinfo->properties->width, (int) $this->grinfo->properties->height, "auto");
                break;
            case 'pie':
            case 'pie3D':
                // Adaptation de la hauteur de graph si la légende dépasse LEGEND_MAX_LINES lignes (bz19866)
                if (count($this->grinfo->xaxis_labels->label) >= self::LEGEND_MAX_LINES) {
                    // 17/03/2011 OJT : bz19866, Cast de la propriété height en entier avant l'addition
                    //$newHeight = intval( $this->grinfo->properties->height ) + 14 * ( count( $this->grinfo->xaxis_labels->label ) - self::LEGEND_MAX_LINES );
                    // 18/06/2012 MMT bz19866 REOPEN calcul pas assez precis, Height = 1 ligne legende = 15 px + 60px marge
                    $newHeight = intval(15 * count($this->grinfo->xaxis_labels->label) + 60);
                    $this->grinfo->properties->height = (string) $newHeight;
                }
                $this->graph = new PieGraph((int) $this->grinfo->properties->width, (int) $this->grinfo->properties->height, "auto");
                break;
            default:
                $this->error('This type of graph (' . (string) $this->grinfo->properties->type . ') is not valid.');
                break;
        }

        // -1.1- properties
        $this->graph->title->Set($this->grinfo->properties->title);
        $this->graph->title->SetFont(constant((string) $this->grinfo->properties->title['font_family']), constant((string) $this->grinfo->properties->title['font_style']), (string) $this->grinfo->properties->title['font_size']);
        $this->graph->title->SetColor((string) $this->grinfo->properties->title['color']);
        $this->graph->img->SetImgFormat($this->grinfo->properties->img_format);
        $this->graph->img->SetAntiAliasing(true);
        $this->graph->SetMarginColor((string) $this->grinfo->properties->margin_color);
        $this->graph->SetColor((string) $this->grinfo->properties->background_color);


        // 14:03 23/07/2009 GHX
        // Correction du BZ 9552 [REC][T&A CB 5.0][AFFICHAGE LEGENDE]: les légendes longues sont coupées
        // Modification du style de la légende
        $this->graph->legend->SetFont(FF_VERDANA, FS_NORMAL, 7);
        $this->graph->legend->SetVColMargin(5);

        // affichage de la marge (bizarrement, la marge ne s'affiche pas si le cadre est à false)
        if ((string) $this->grinfo->properties->margin_color != 'white')
            $this->graph->SetFrame(true, (string) $this->grinfo->properties->margin_color, 1);
        else
            $this->graph->SetFrame(false);

        // on crée le tabTitle
        $this->setTabTitle();

        // on construit le graph ou le pie
        switch ((string) $this->grinfo->properties->type) {
            case 'graph':
                $this->buildGraph();
                break;
            case 'singleKPI':
                $this->buildGraph();
                break;
            case 'pie':
            case 'pie3D':
                $this->buildPie();
                break;
        }

        // -2- on envoie l'image (via navigateur ou dans un fichier suivant la valeur de $absolute_filename)
        // echo '<pre>';print_r($this->graph);
        $this->graph->Stroke($absolute_filename);
    }

    /** 	Cette fonction vérifie l'existence de base_dir et base_url.
     * 	@author	SLC - 25/09/2008
     * 	@version	CB 4.1.0.0
     * 	@return 	void		
     */
    function checkDirs() {
        if ((string) $this->grinfo->properties->base_dir == '') {
            $dir = $_SERVER["SCRIPT_FILENAME"];
            $dir = substr($dir, 0, strrpos($dir, '/')) . '/image_files/';
            if (!file_exists($dir)) {
                mkdir($dir);
                @chmod($dir, 0777);
            }
            $this->grinfo->properties->base_dir = $dir;

            $self = $_SERVER["PHP_SELF"];
            $self = substr($self, 0, strrpos($self, '/')) . '/image_files/';
            $this->grinfo->properties->base_url = $self;
        }
    }

    /** 	Cette fonction génère et sauvegarde l'image en appelant $this->stroke()
     * 	@author	SLC - aout 2008
     * 	@version	CB 4.1.0.0
     * 	@param	string		$absolute_filename	adresse du fichier dans lequel l'image doit être sauvegardée. Si vide, stroke() renvoie directement l'image
     * 	@return 	void		
     */
    function saveImage($filename = '') {
        if ($this->debug)
            echo "<br />call to: saveImage($filename)";

        $this->setErrorIsImage(false);

        // -0- on verifie qu'on a base_dir et base_url
        $this->checkDirs();

        // -1- on verifie l'existance du repertoire
        if (!is_dir((string) $this->grinfo->properties->base_dir))
            $this->error("Le repertoire de destination (" . (string) $this->grinfo->properties->base_dir . ") n'existe pas.");

        // -2- on regarde le nom du fichier à écrire
        // on invente éventuellement le nom du fichier
        if (!$filename) {
            // s'il n'est pas spécifié, on crée le nom du fichier
            $filename = md5(uniqid(rand(), true));
            if ($this->grinfo->properties->img_format == 'jpeg') {
                $filename .= '.jpg';
            } else {
                $filename .= '.' . $this->grinfo->properties->img_format;
            }
        } else {
            // on regarde l'extention du nom de fichier (s'il y en a une)
            $has_extention = false;
            if (strpos($filename, '.')) {
                $extention = substr($filename, strrpos($filename, '.'));
                $extention = trim($extention, '.');
                if (in_array($extention, array('png', 'jpg', 'gif'))) {
                    if ($extention == 'jpg') {
                        $this->grinfo->properties->img_format = 'jpeg';
                    } else {
                        $this->grinfo->properties->img_format = $extention;
                    }
                    $has_extention = true;
                }
            }
            if (!$has_extention) { // on va rajouter l'extention
                if ($this->grinfo->properties->img_format == 'jpeg') {
                    $filename .= '.jpg';
                } else {
                    $filename .= '.' . $this->grinfo->properties->img_format;
                }
            }
        }
        // $filename est maintenant un nom de fichier valide, avec la bonne extention
        $this->filename = $filename;

        // -3- on génère l'image et on l'ecrit dans un repertoire
        // 11:19 30/10/2009 GHX
        // BZ 12359
        $this->preStroke();
        $this->stroke((string) $this->grinfo->properties->base_dir . $filename);
    }

    /**
     * Avant de constructuire le graphe, on fait certaines actions
     *
     * 	30/10/2009 GHX
     * 		- Ajout de la fonction pour corriger le BZ 12359
     *
     * @author GHX
     * @version CB 5.0.1.0.3
     * @since CB 5.0.1.0.3
     */
    function preStroke() {
        switch ((string) $this->grinfo->properties->type) {
            case 'graph':
                /*
                  On recalcule la hauteur de l'image en fonction de la plus grande légende de l'abscisse (sinon erreur JPGRAHP si la valeur est trop grande)
                 */
                //Création d'un object Text qui permettra de calculer la longueur de la chaine en pixels dans le graph
                $textTMP = new Text('text');
                $textTMP->SetFont(FF_ARIAL, FS_NORMAL, 7);
                $textTMP->SetAngle(60);
                $graph = new Graph((int) $this->grinfo->properties->width, (int) $this->grinfo->properties->height);

                // Récupère la hauteur de la plus grande légende de l'abscisse
                $max_label_length = 0;
                foreach ($this->grinfo->xaxis_labels->label as $label) {
                    $textTMP->Set((string) $label); //Spécifie le texte de la légende pour pouvoir récupérer sa longueur
                    $max_label_length = max($max_label_length, $textTMP->GetWidth($graph->img));
                }
                $ratio = $max_label_length / $this->grinfo->properties->height;

                // Modifie la hauteur de l'image en fonction du ratio
                if ($ratio < 0.5) {
                    if ($ratio >= 0.4) {
                        $this->grinfo->properties->height = $this->grinfo->properties->height * 1.6;
                    } elseif ($ratio >= 0.3) {
                        $this->grinfo->properties->height = $this->grinfo->properties->height * 1.4;
                    } elseif ($ratio >= 0.2) {
                        $this->grinfo->properties->height = $this->grinfo->properties->height * 1.2;
                    }
                } elseif ($ratio >= 0.5) {
                    if ($ratio >= 0.70) {
                        $this->grinfo->properties->height = $this->grinfo->properties->height * 2.5;
                    } else {
                        $this->grinfo->properties->height = $this->grinfo->properties->height * 2;
                    }
                }

                if ($this->grinfo->properties->width <= 900 && $ratio >= 0.4) {
                    $this->grinfo->properties->width = 1100;
                }
                break;
        }
    }

// End function preStroke

    /** 	Cette fonction construit le graph, si celui-ci est de type graph.
     * 	C'est cette fonction qui fait tout le travail de construction.
     * 	@author	SLC - aout 2008
     * 	@version	CB 4.1.0.0
     * 	@param	void
     * 	@return 	void		
     */
    function buildGraph() {
        if ($this->debug)
            echo "<br />call to: buildGraph()";

        // on va boucler sur les datas
        $this->plot = array();
        $this->nb_plots = count($this->grinfo->datas->data);

        // on a besoin de définir has_bar, has_axeY2, has_cumulatedbar_left, etc ...
        $this->has_bar = false;
        $this->has_axeY2 = false;
        for ($i = 0; $i < $this->nb_plots; $i++) {
            $data = $this->grinfo->datas[0]->data[$i];
            $type = (string) $data['type'];
            $side = (string) $data['yaxis'];
            // pour corriger un bug JpGraph pour les courbes "line" n'ayant qu'une seule valeur, on a besoin de savoir si on a des barplots
            if (($type == 'bar') || ($type == 'cumulatedbar'))
                $this->has_bar = true;
            // on regarde si on aura un axeY2
            if ($side == 'right')
                $this->has_axeY2 = true;
            // on regarde si on a un cumulatedbar à gauche
            if (($type == 'cumulatedbar') and ( $side == 'left'))
                $this->has_cumulatedbar_left = true;
            // on regarde si on a un cumulatedbar à droite
            if (($type == 'cumulatedbar') and ( $side == 'right'))
                $this->has_cumulatedbar_right = true;

            // on cherche les VALEURS négatives
            $nb_values = count($data->value);
            if ($side == 'left') {
                for ($j = 0; $j < $nb_values; $j++) {
                    if ($data->value[$j] < 0) {
                        $this->has_negvalue_left = true;
                        $j = $nb_values; // on sort de la boucle for
                    }
                }
            } else {
                for ($j = 0; $j < $nb_values; $j++) {
                    if ($data->value[$j] < 0) {
                        $this->has_negvalue_right = true;
                        $j = $nb_values; // on sort de la boucle for
                    }
                }
            }

            // on cherche si les données possèdent au moins une valeur
            if (!$this->has_value) {
                for ($j = 0; $j < $nb_values; $j++) {
                    if (trim((string) $data->value[$j]) != '') {
                        $this->has_value = true;
                        $j = $nb_values; // on sort de la boucle for
                    }
                }
            }
        }

        // 16:48 11/06/2009 GHX
        // Correction du BZ9797
        // if (!$this->has_value) $this->error("No value found inside data file");
        // on boucle sur tous les $this->grinfo->datas->data pour créer les $this->plot		
        for ($i = 0; $i < $this->nb_plots; $i++) {
            $data = $this->grinfo->datas[0]->data[$i];

            // 05/08/2009 GHX
            // Correction du BZ 8691 
            $_ = explode('@', $data["fill_color"]);
            $data["fill_color"] = $_[0];
            $data["fill_transparency"] = $_[1];

            // pour tous les appels nécessaires à evalStringArray() dans la prochaine boucle for() on crée une version de $data allégée
            $data_light = array();
            foreach ($data->attributes() as $att_name => $att_value)
                $data_light[$att_name] = $att_value;

            // get the data array
            $data_array = array();
            $target_array = array(); // premier argument de SetCSIMTargets()
            $content_array = array(); // second argument de SetCSIMTargets()
            $nb_value = count($data->value);

            // on boucle sur tous les tags <value> pour calculer les target_url, alt_title, alt_content, target_AA de chaque point
            for ($j = 0; $j < $nb_value; $j++) {
                $value = $data->value[$j];
                // la valeur
                $val = (string) $value;
                $val = str_replace(' ', '', trim($val));

                // 09:29 21/07/2009 GHX
                // Correction du BZ 10358 [REC][T&A Cb 5.0][DASH]: affichage des valeurs null dans le dashboard
                // if ($val == '') $val = 0;

                $data_array[] = $val;
                // on compose le tableau de toutes les chaines à évaluer
                $strings_to_eval = array(
                    'href' => (string) $data["href"],
                    'onmouseover' => (string) $data["onmouseover"],
                    'onmouseout' => (string) $data["onmouseout"],
                    'onclick' => (string) $data["onclick"],
                );
                // on evalue toutes les chaines du tableau
                $strings = $this->evalStringArray($strings_to_eval, $data_light, $this->grinfo->xaxis_labels->label[$j], $value);
                // echo '<pre style="color:red;">';print_r($strings);echo '</pre>';
                // on récupère les chaines évaluées en les mettant dans les bons containeurs (en vue de donner tout ça à SetCSIMTargets())
                $target_array[] = $strings['href'];
                $content_array[] = $strings;
            }
            // echo "<pre>";print_r($target_array);exit;
            // 10:23 18/12/2009 GHX
            // Ajout de valeur par défaut si elles ne sont pas défini
            if (trim((string) $data["stroke_color"]) == '')
                $data["stroke_color"] = '#1414E4';
            if (trim((string) $data["fill_color"]) == '')
                $data["fill_color"] = '#FFFFFF';
            if (trim((string) $data["fill_transparency"]) == '')
                $data["fill_transparency"] = 1;

            // on crée les objets plots isolés
            switch ((string) $data['type']) {

                // bar
                case 'bar':
                case 'cumulatedbar':
                    $this->plot[$i] = new Barplot($data_array);
                    $this->plot[$i]->SetColor((string) $data["stroke_color"]);
                    if ((string) $data["fill_transparency"] == '')
                        $data["fill_transparency"] = '0';
                    // 15:52 06/11/2009 GHX
                    // Correction du BZ 12567
                    if (((float) str_replace(",", ".", $data["fill_transparency"])) > 1)
                        $data["fill_transparency"] = 1;
                    if ((string) $data["fill_color"] != '')
                        $this->plot[$i]->SetFillColor((string) $data["fill_color"] . '@' . (string) $data["fill_transparency"]);
                    // echo '<pre style="color:red;">';print_r($content_array);echo '</pre>';
                    $this->plot[$i]->SetCSIMTargets($target_array, $content_array);
                    break;


                // line
                case 'line':
                case 'cumulatedline':
                    if (count($data_array) <= 1 and ! $this->has_bar) {
                        // on contourne le problème de JpGraph qui ne supporte pas le text auto-scaling
                        // pour le cas des courbes 'line' n'ayant qu'un seul point
                        // on augmente le tableau de valeurs
                        $valeur = $data_array[0];
                        unset($data_array);
                        // 10:03 07/08/2009 GHX
                        // On passe la valeur "-" au lieu de zéro
                        // 04/03/2010 NSE bz 14325 : on passe rien au lieu de -
                        $data_array = array('', $valeur, '');

                        // on augmente les valeurs de l'axe des abscisses
                        $nb_labels = 0;
                        foreach ($this->grinfo->xaxis_labels->label as $label)
                            $nb_labels++;
                        if ($nb_labels == 1) {
                            // on est bien dans le cas où on a qu'une seule valeur en abscisse et on va donc en ajouter deux vides (avant et après la valeur actuelle)
                            $label = (string) $this->grinfo->xaxis_labels->label;
                            unset($this->grinfo->xaxis_labels->label);
                            $this->grinfo->xaxis_labels->addChild('label');
                            $this->grinfo->xaxis_labels->addChild('label', $label);
                            $this->grinfo->xaxis_labels->addChild('label');
                        }

                        // on crée le plot
                        $this->plot[$i] = new ScatterPlot($data_array);
                        $this->plot[$i]->SetImpuls();
                        // gestion des marqueurs
                        // on calcule la fill color
                        $fill_color = '';
                        $has_marker = false;
                        if ((string) $data["fill_color"] != '') {
                            $fill_color = (string) $data["fill_color"];
                        } else {
                            $fill_color = (string) $data["stroke_color"];
                        }

                        // 21/09/2010 BBX
                        // Correction de diamond et ajout de triangle
                        // BZ 18010
                        // 30/04/2013 GFS - BZ#18500 - [QAL][CB 5.0 / T&A HUAWEI NSS 5.0] [GTM]:For 3 GTMs, the color of all lines and legends become white in some cases.
                        switch ((string) $data["line_design"]) {
                            case "circle" :
                                // 05/08/2009 GHX
                                // MARK_CIRCLE devient MARK_FILLEDCIRCLE
                                $this->plot[$i]->mark->SetType(MARK_FILLEDCIRCLE);
                                $this->plot[$i]->mark->SetWidth(4);
                                $has_marker = true;
                                break;
                            case "square" :
                                $this->plot[$i]->mark->SetType(MARK_SQUARE);
                                $this->plot[$i]->mark->SetWidth(5);
                                $has_marker = true;
                                break;
                            case "diamond" :
                                $this->plot[$i]->mark->SetType(MARK_DIAMOND);
                                $this->plot[$i]->mark->SetWidth(5);
                                $has_marker = true;
                                break;
                            case "triangle" :
                                $this->plot[$i]->mark->SetType(MARK_UTRIANGLE);
                                $this->plot[$i]->mark->SetWidth(5);
                                $has_marker = true;
                                break;
                            // 10:32 18/12/2009 GHX
                            // Ajout du default
                            default:
                                $this->plot[$i]->mark->SetType(MARK_FILLEDCIRCLE);
                                $this->plot[$i]->mark->SetWidth(4);
                                $has_marker = true;
                        }
                        if ($has_marker) {
                            $this->plot[$i]->mark->SetColor((string) $data["stroke_color"]);
                            $this->plot[$i]->mark->SetFillColor($fill_color);
                            $this->plot[$i]->mark->Show();
                        }
                        // FIN 30/04/2013 GFS - BZ#18500 - [QAL][CB 5.0 / T&A HUAWEI NSS 5.0] [GTM]:For 3 GTMs, the color of all lines and legends become white in some cases.
                        // 10:03 07/08/2009 GHX
                        // Ajout de 2 valeurs nulles dans le tableau sinon pas de tooltip
                        array_unshift($content_array, null);
                        array_push($content_array, null);
                        $this->plot[$i]->SetCSIMTargets($content_array, $content_array);
                    } else {
                        // cas normal d'une 'line' ou 'cumulatedline'
                        $this->plot[$i] = new Lineplot($data_array);
                        // on centre les lignes s'il y a des barres (sinon ça entraine un decallage moche)
                        if ($this->has_bar)
                            $this->plot[$i]->setBarCenter();

                        $this->plot[$i]->setColor((string) $data["stroke_color"]);

                        if ((string) $data["fill_transparency"] == '')
                            $data["fill_transparency"] = '0';
                        // 05/08/2009 GHX
                        // Modification de la condition
                        if ((string) $data["fill_color"] != '' && (string) $data["fill_transparency"] != '1') {
                            // 15:52 06/11/2009 GHX
                            // Correction du BZ 12567
                            if (((float) str_replace(",", ".", $data["fill_transparency"])) > 1)
                                $data["fill_transparency"] = 1;

                            $this->plot[$i]->setFillColor((string) $data["fill_color"] . '@' . (string) $data["fill_transparency"]);
                        }

                        // 18/11/2011 BBX
                        // BZ 22656 : correction de la couleur du premier plot
                        // Pour un élément de type "line" il faut appeler "SetLineWeight" et non "SetWeight"
                        $this->plot[$i]->SetLineWeight(2);

                        // gestion des marqueurs
                        // on calcule la fill color
                        $fill_color = '';
                        $has_marker = false;
                        // 11:33 05/08/2009 GHX
                        if ((string) $data["fill_color"] != '') {
                            $fill_color = (string) $data["fill_color"];
                        } else {
                            $fill_color = (string) $data["stroke_color"];
                        }

                        // on définit le style du marqueur
                        // 21/09/2010 BBX
                        // Correction de diamond et ajout de triangle
                        // BZ 18010
                        switch ((string) $data["line_design"]) {
                            case "circle" :
                                // 05/08/2009 GHX
                                // MARK_CIRCLE devient MARK_FILLEDCIRCLE
                                $this->plot[$i]->mark->SetType(MARK_FILLEDCIRCLE);
                                $this->plot[$i]->mark->SetWidth(4);
                                $has_marker = true;
                                break;
                            case "square" :
                                $this->plot[$i]->mark->SetType(MARK_SQUARE);
                                $this->plot[$i]->mark->SetWidth(5);
                                $has_marker = true;
                                break;
                            case "diamond" :
                                $this->plot[$i]->mark->SetType(MARK_DIAMOND);
                                $this->plot[$i]->mark->SetWidth(5);
                                $has_marker = true;
                                break;
                            case "triangle" :
                                $this->plot[$i]->mark->SetType(MARK_UTRIANGLE);
                                $this->plot[$i]->mark->SetWidth(5);
                                $has_marker = true;
                                break;
                            // 10:32 18/12/2009 GHX
                            // Ajout du default
                            default:
                                $this->plot[$i]->mark->SetType(MARK_FILLEDCIRCLE);
                                $this->plot[$i]->mark->SetWidth(4);
                                $has_marker = true;
                        }
                        if ($has_marker) {
                            $this->plot[$i]->mark->SetColor((string) $data["stroke_color"]);
                            $this->plot[$i]->mark->SetFillColor($fill_color);
                            $this->plot[$i]->mark->Show();
                        }

                        // Interactivité
                        // on change la structure de $content_array  (pas trop bien compris pourquoi les données associées aux lines et cumulatedlines étaient strucurées différemment
                        // peut-être est-ce du au fait que ce ne sont pas les lines qui on des <area>, mais les marqueurs de ces lines.
                        foreach ($content_array as &$content_element) {
                            $content_copie = $content_element;
                            $content_element[1] = $content_copie;
                        }
                        $this->plot[$i]->SetCSIMTargets($target_array, $content_array);
                    }
                    break;

                // 09:45 18/12/2009 GHX
                // Ajout du default
                default:
                    $this->grinfo->datas[0]->data[$i]['type'] = 'bar';
                    $this->plot[$i] = new Barplot($data_array);
                    $this->plot[$i]->SetColor((string) $data["stroke_color"]);
                    if ((string) $data["fill_transparency"] == '')
                        $data["fill_transparency"] = '0';
                    // 15:52 06/11/2009 GHX
                    // Correction du BZ 12567
                    if (((float) str_replace(",", ".", $data["fill_transparency"])) > 1)
                        $data["fill_transparency"] = 1;
                    if ((string) $data["fill_color"] != '')
                        $this->plot[$i]->SetFillColor((string) $data["fill_color"] . '@' . (string) $data["fill_transparency"]);
                    // echo '<pre style="color:red;">';print_r($content_array);echo '</pre>';
                    $this->plot[$i]->SetCSIMTargets($target_array, $content_array);
            }

            // légende du plot
            $legend = (string) $data["label"];
            $legend .= '(' . ucfirst((string) $data['yaxis']) . ')';
            $this->plot[$i]->setLegend($legend);
        }

        // 13/09/2011 BBX
        // BZ 20799 : remaniement du code afin de respecter l'ordre
        $this->nb_plots = count($this->plot);

        // Déclaration des structure de données utilisées
        // Va stocker les éléments à grouper (barres cumulées, etc...)
        $groups = array();
        // Va stocker les éléments simples
        $elements = array();
        // Va stocker tous les éléments dans leur forme finale
        $finalElements = array();

        // Pout tous les plots
        for ($i = 0; $i < $this->nb_plots; $i++) {
            // On récupère les attributs du plot courant
            $data = get_object_vars($this->grinfo->datas[0]->data[$i]);
            $data = $data['@attributes'];

            // Insertion dans la bonne structure de données selon l'élément courant
            if ($data['type'] == 'line') {
                // LINE
                $elements[$data['yaxis']][$i] = $this->plot[$i];
            } else {
                // ELEMENTS GROUPES
                $groups[$data['type']][$data['yaxis']][$i] = $this->plot[$i];
            }
        }

        // Ajout des éléments simples au tableau final
        $this->attachLines($finalElements, $elements);
        // Ajout des éléments composés au tableau final
        $this->attachPlots($finalElements, $groups);
        // Ajout des éléments au graphe
        $this->attachElements($finalElements);
        // FIN BZ 20799
        // Single Kpi
        $toto = (string) $this->grinfo->properties->scale;
        // gestion des SCALEs
        switch ((string) $this->grinfo->properties->scale) {
            case 'textlin':
                $this->graph->SetScale("textlin");
                // Application du formatage     
                $this->graph->yaxis->SetLabelFormatCallback('numberFormatLeftAxis');
                // on gère le scale de Y2
                if ($this->has_axeY2) {
                    $this->graph->SetY2Scale('int');
                    // Application du formatage     
                    $this->graph->y2axis->SetLabelFormatCallback('numberFormatRightAxis');
                }


                break;
            case 'textlog':

                $this->graph->SetScale("textlog");
                // on gère le scale de Y2
                if ($this->has_axeY2)
                    $this->graph->SetY2Scale('log');
                break;
            default:
                echo "You need to specify a scale for your graph. Accepted values are 'textlin' and 'textlog'.";
                exit;
                break;
        }

        // on ajoute les lignes horizontales
        $this->addHorizontalLines();

        // on positionne la légende
        $this->makeLegend();


        // on calcule les marges minimum
        $this->margin_left = $this->getMinMargin('left') + 70; // +70 pour le nom de axeY
        if ($this->has_axeY2)
            $this->margin_right = $this->getMinMargin('right') + 70; // +70 pour le nom de axeY2
        $this->margin_top = $this->getMinMargin('top');
        $this->margin_bottom = $this->getMinMargin('bottom');

        // on ajoute la largeur de la légende au côté sur lequel la légende est positionnée
        switch ((string) $this->grinfo->properties->legend_position) {
            case 'left':
                $this->margin_left += $this->legend_width + 5;
                break;
            case 'right':
                $this->margin_right += $this->legend_width;
                if (!$this->has_axeY2)
                    $this->margin_right += 5; // +5 petit espace supplémentaire
                break;
            case 'top':
                $this->margin_top += $this->legend_height;
                break;
            case 'bottom':
                $this->margin_bottom += $this->legend_height;
                break;
        }

        // on modifie les marges si elles ne sont pas assez grandes
        if ($this->margin_left < $this->grinfo->properties->margin_left)
            $this->margin_left = $this->grinfo->properties->margin_left;
        if ($this->margin_right < $this->grinfo->properties->margin_right)
            $this->margin_right = $this->grinfo->properties->margin_right;
        if ($this->margin_top < $this->grinfo->properties->margin_top)
            $this->margin_top = $this->grinfo->properties->margin_top;
        if ($this->margin_bottom < $this->grinfo->properties->margin_bottom)
            $this->margin_bottom = $this->grinfo->properties->margin_bottom;

        // on définit les marges du graphe
        $this->graph->SetMargin($this->margin_left, $this->margin_right, $this->margin_top, $this->margin_bottom);

        // on compose l'axe des X
        $this->makeXaxis();

        // on ajoute les noms des axeY
        $this->addAxeYNames();

        // 07/10/2011 BBX
        // BZ 23719 : ne sert plus ici
        // on gère les valeurs négatives
        //$this->manageNegValues();
        // on affiche une grille grise
        // $this->graph->ygrid->SetFill(true,'#EFEFEF@0.7','#FFFFFF@0.9');		// #EFEFEF c'est trop clair non à
        $this->graph->ygrid->SetFill(true, '#DDDDDD@0.7', '#FFFFFF@0.9');
        $this->graph->ygrid->SetColor('gray');
        if ($this->debug)
            echo "<br />buildGraph() -> ygrid colors set";
        $this->graph->ygrid->Show();
        $this->graph->xgrid->Show();
        if ($this->debug)
            echo "<br />buildGraph() -> reached end of function";
    }

    /** 	
     * Cette fonction construit le graph, si celui-ci est de type pie ou pie3D.
     * C'est cette fonction qui fait tout le travail de construction.
     *
     * 02/02/2011 OJT : Réindentation et réécriture de la méthode (correction bz19866)
     *
     * @author	SLC - aout 2008
     * @version	CB 4.1.0.0
     * @param	void
     * @return 	void		
     */
    function buildPie() {
        if ($this->debug)
            echo "<br />call to: buildPie()";

        $data = $this->grinfo->datas->data[(int) $this->grinfo->properties->type['data']];
        $nb_values = count($data->value);
        $data_light = array();
        $data_array = array();
        $data_legend = array();
        $target_array = array(); // premier argument de SetCSIMTargets()
        $content_array = array(); // second argument de SetCSIMTargets()
        // On cherche s'il y  a une VALEUR négative
        for ($j = 0; $j < $nb_values; $j++) {
            if ($data->value[$j] < 0) {
                $this->error('It is not possible to draw a pie graph that has negative values.');
            }
        }

        // Pour tous les appels nécessaires à evalStringArray() dans la prochaine boucle for() on crée une version de $data allégée
        foreach ($data->attributes() as $att_name => $att_value) {
            $data_light[$att_name] = $att_value;
        }

        // On boucle sur tous les tags <value> pour calculer les target_url, alt_title, alt_content, target_AA de chaque point
        for ($j = 0; $j < $nb_values; $j++) {
            $data_array[] = str_replace(' ', '', trim((string) $data->value[$j]));

            // Lecture de la légende
            // 04/12/2009 GHX : Ajout du html_entity_decode sinon les &eacute; sont affichés tel quel au lieu de à par exemple
            // 02/02/2011 OJT : Gestion de la longueur max de la légende (bz19866)
            $tmpLegend = (string) html_entity_decode($this->grinfo->xaxis_labels->label[$j]);
            if (strlen($tmpLegend) > self::PIE_LEGEND_MAX_LENGTH) {
                $tmpLegend = substr($tmpLegend, 0, self::PIE_LEGEND_PREFIX_LENGTH) . self::PIE_LEGEND_DELIMITER . substr($tmpLegend, self::PIE_LEGEND_SUFIX_LENGTH * -1);
            }
            $data_legend[] = $tmpLegend;

            // On compose le tableau de toutes les chaines à évaluer
            $strings_to_eval = array(
                'href' => (string) $data["href"],
                'onmouseover' => (string) $data["onmouseover"],
                'onmouseout' => (string) $data["onmouseout"],
                'onclick' => (string) $data["onclick"],
            );
            // On evalue toutes les chaines du tableau
            $strings = $this->evalStringArray($strings_to_eval, $data_light, $this->grinfo->xaxis_labels->label[$j], $data->value[$j]);
            // On récupère les chaines évaluées en les mettant dans les bons containeurs (en vue de donner tout ça à SetCSIMTargets())
            $target_array[] = $strings['href'];
            $content_array[] = $strings;
        }

        // On crée le pie
        if ((string) $this->grinfo->properties->type == 'pie3D') {
            $pieplot = new PiePlot3D($data_array);
            $pieplot->SetAngle(40);
            $pieplot->SetSize(0.40); // Rayon du camembert
            $pieplot->SetCenter(0.28, 0.45); // Positionnement (X,Y) du camembert
        } else {
            $pieplot = new PiePlot($data_array);
            $pieplot->SetSize(0.30); // Rayon du camembert
            $pieplot->SetCenter(0.25, 0.50); // Positionnement (X,Y) du camembert
            // Enable and set policy for guide-lines
            $pieplot->SetGuideLines();
            $pieplot->SetGuideLinesAdjust(1.2);
        }

        // Interactivité
        $pieplot->SetCSIMTargets($target_array, $content_array);

        // Pie properties
        if ($data_array[0] > 0)
            $pieplot->ExplodeSlice(0);
        $pieplot->value->Show();
        $pieplot->SetStartAngle(45);

        // Gestion des labels
        $pieplot->SetLabelType(PIE_VALUE_PER);
        $labels_array = array();
        // labels par defaut
        if ((string) $data['pie_label'] != '') {
            $default = (string) $data['pie_label'];
        } else {
            $default = "%.0f%%";
        }
        foreach ($data_array as $one_data) {
            $labels_array[] = $default;
        }

        // Override les labels avec <label pie_label="XX">
        for ($j = 0; $j < $nb_values; $j++) {
            $value = $this->grinfo->xaxis_labels->label[$j];
            if ((string) $value['pie_label'] != '') {
                $labels_array[$j] = (string) $value['pie_label'];
            }
        }
        // Override les labels avec <value pie_label="XX">
        for ($j = 0; $j < $nb_values; $j++) {
            $value = $data->value[$j];
            if ((string) $value['pie_label'] != '') {
                $labels_array[$j] = (string) $value['pie_label'];
            }
        }
        $pieplot->SetLabels($labels_array, 1);
        $pieplot->labeloffset = 0;
        //	$pieplot->SetGuideLines();	-- fonction non présente dans notre version actuelle de JpGraph		
        // Gestion des couleurs
        global $pie_colors;
        // on supplante les couleurs par les couleurs désignées dans le tag <label color="??"> 
        for ($j = 0; $j < $nb_values; $j++) {
            $value = $this->grinfo->xaxis_labels->label[$j];
            if ((string) $value['color'] != '')
                $pie_colors[$j] = (string) $value['color'];
        }
        // on supplante les couleurs par les couleurs désignées dans le tag <value color="??"> 
        for ($j = 0; $j < $nb_values; $j++) {
            $value = $data->value[$j];
            if ((string) $value['color'] != '')
                $pie_colors[$j] = (string) $value['color'];
        }
        $pieplot->SetSliceColors($pie_colors);

        $width_total_legende = $this->getLegendWidth($data_legend);

        // on en déduit la posX du bord droit de la légende, pour que ça soit bien positionné
        $width_legende_normalisee = $width_total_legende / ((int) $this->grinfo->properties->width);

        if ($width_legende_normalisee > 0.4)
            $posX = 0.01;
        elseif ($width_legende_normalisee > 0.3)
            $posX = 0.1;
        elseif ($width_legende_normalisee > 0.2)
            $posX = 0.2;
        else
            $posX = 0.3;

        $posY = 50 / ((int) $this->grinfo->properties->height);

        // on charge la liste des légendes à afficher.
        $pieplot->SetLegends($data_legend);

        // on positionne la légende
        // $this->makeLegend();
        $this->graph->legend->Setshadow(false);
        $this->graph->legend->SetFillColor('#fefefe@0.5');
        $this->graph->legend->SetColumns(1); // Toujours une colonne pour les PIE
        $this->graph->legend->SetPos($posX, $posY, "right", "top");

        // on ajoute le plot au graph :
        $this->graph->Add($pieplot);

        // on définit les marges : les marges ne sont PAS paramétrables avec un pie
        $this->graph->SetMargin(10, 10, 40, 10); // - 23/04/2007 christophe
        // $this->graph->SetFrame(true);
        // Affichage du titre de l'axe des abscisses.
        $this->displayXAxisTitle();
    }

    /** 	
     * Cette fonction calcule la longueur de la légende en mesurant la longueur de l'élément texte engendré par chaque légende
     * et en calculant le nombre de colonnes de la légende DANS LE CAS 'left' ou 'right' UNIQUEMENT
     *
     * @author SLC - aout 2008
     * @version	CB 4.1.0.0
     * @param array $legend_names est le tableau des légendes (strings)
     * @return int retourne la longueur de la légende en pixels		
     */
    function getLegendWidth($legend_names) {
        if ($this->debug)
            echo "<br />call to: getLegendWidth($legend_names)";

        // ==== gestion de la légende ====
        // positionnement de la légende : avant on faisait un bête
        // $this->graph->legend->SetPos(0.01,0.5,"right","center");		<- légende ferrée à droite
        // mais comme ça laissait souvent un espace énorme entre la légende et le camembert,
        // on fait maintenant pleins de calculs alambiqués pour évaluer la largeur de la légende et
        // l'approcher du camembert quand elle est assez étroite
        // nombre d'éléments à afficher dans la légende.
        $nb_element = count($legend_names);

        // objet texte qui va nous permettre de mesure les tailles des éléments de la légende
        $textTMP = new Text('text');
        $textTMP->SetFont($this->graph->legend->font_family, $this->graph->legend->font_style, $this->graph->legend->font_size);

        // on calcule le nombre d'éléments à afficher par colonne
        // pour cela on calcule la hauteur du premier élément
        $textTMP->Set($legend_names[0]);
        $hauteur_une_legende = $textTMP->GetFontHeight($this->graph->img);
        // on ajoute 3px pour la sécurité
        $hauteur_une_legende += 3;
        // on trouve la hauteur max de la légende
        $legend_max_height = (int) $this->grinfo->properties->height - 50 - 10 - 10; // - marge haute - marge basse - sécurité
        // 02/02/2011 OJT : bz19866 On utilise la constante prédéfinie
        // nombre de colonnes de la légende
        // on calcule la largeur totale de la légende
        // 18/06/2012 MMT bz19866 REOPEN supprime la gestion de plusieur colonnes
        $k = 0;
        $width_total_legende = 0;
        $width_col = 0;
        // on boucle sur les légendes dans la colonne
        for ($j = 0; $j < self::LEGEND_MAX_LINES; $j++) {
            $textTMP->Set($legend_names[$k]);
            $width_elem = $textTMP->GetWidth($this->graph->img);
            $width_col = max($width_col, $width_elem);
            $k++;
            if ($k >= $nb_element)
                $j = self::LEGEND_MAX_LINES;
        }
        $width_total_legende += $width_col + 40; // +40 pour le carré et les marges entre colonnes
        // 18/06/2012 MMT bz19866 fixe nb colonnes à 1
        $this->legend_nb_col = 1;
        $width_total_legende += 10; // marge supplémentaire depuis JpGraph 2.3.3
        if ($this->debug)
            echo " = $width_total_legende px sur $nb_col colonne(s)";
        return $width_total_legende;
    }

    /** 	Cette fonction compose le tabTitle à partir du node <tabtitle> du fichier XML.
     * 	Son principal travail est de récupérer les infos du node <tabtitle> pour les mettre dans un tableau et d'appeler la classe  GraphTabTitleWithColor pour créer le tabTitle.
     * 	Cette fonction est appelée par buildGraph() et buildPie()
     * 	@author	SLC - aout 2008
     * 	@version	CB 4.1.0.0
     * 	@param	void
     * 	@return 	void		
     */
    function setTabTitle() {
        if ($this->debug)
            echo "<br />call to: setTabTitle()";

        // on verifie qu'on a au moins un tabTitle->text
        if (isset($this->grinfo->properties->tabtitle->text[0])) {

            // on va avoir besoin de cet objet texte pour mesurer la longueur de nos chaînes une fois affichées
            $textTMP = new Text('text');

            // 21/11/2011 BBX
            // Correction de messages "Notices" vu pendant les corrections
            $nb_letters = 0;

            // 1 - on compose le tableau $text_array
            $tabtitle_width = 0;
            $text_array = array();
            foreach ($this->grinfo->properties->tabtitle->text as $text) {
                // on prend le contenu du tag <text>
                $text_a = array('text' => (string) $text);
                // 21/11/2011 BBX
                // Correction de messages "Notices" vu pendant les corrections
                $nb_letters += strlen($text_a['text']);
                // on scanne les attributs du tag <text color="" font_size="" font_style="" font_family="">
                foreach ($text->attributes() as $att_name => $att_value) {
                    $value = (string) $att_value;
                    if (defined($value))
                        $text_a[$att_name] = constant($value);
                    else
                        $text_a[$att_name] = $value;
                }
                // on ajoute le tag au tableau
                $text_array[] = $text_a;
                // on mesure la longueur de cet élément texte
                // mais pour cela il ne faut spécifier le style de l'élément
                // en partant des styles par défaut du tabtitle
                $font_family = constant((string) $this->grinfo->properties->tabtitle['font_family']);
                $font_style = constant((string) $this->grinfo->properties->tabtitle['font_style']);
                $font_size = (string) $this->grinfo->properties->tabtitle['font_size'];
                // et en écrasant par les styles locaux du text
                // 21/11/2011 BBX
                // Correction de messages "Notices" vu pendant les corrections
                if (isset($text_a['font_family']))
                    $font_family = $text_a['font_family'];
                if (isset($text_a['font_style']))
                    $font_style = $text_a['font_style'];
                if (isset($text_a['font_size']))
                    $font_size = $text_a['font_size'];
                $textTMP->SetFont($font_family, $font_style, $font_size);
                $textTMP->Set($text_a['text']);
                $text_width = $textTMP->GetWidth($this->graph->img);

                $tabtitle_width += $text_width;
                // echo "{$text_a['text']} | $text_width | $tabtitle_width<br >";

                unset($text_a);
            }

            $this->tabtitle_width = $tabtitle_width;

            // on cache le texte et on le supprime
            $textTMP->Hide(); // ne sert à rien mais par précaution
            unset($textTMP);

            // 2 - on crée l'objet GraphTabTitleWithColor et on y ajoute $text_array
            $this->graph->tabtitle = new GraphTabTitleWithColor();
            $this->graph->tabtitle->Set($text_array);

            // 3 - on spécifie les propriétés générales du tabtitle
            $this->graph->tabtitle->SetCorner(2);
            $this->graph->tabtitle->SetTabAlign((string) $this->grinfo->properties->tabtitle['align']);
            $this->graph->tabtitle->SetColor((string) $this->grinfo->properties->tabtitle['color'], 'whitesmoke@0.5', 'snow3');
            $this->graph->tabtitle->SetFont(constant((string) $this->grinfo->properties->tabtitle['font_family']), constant((string) $this->grinfo->properties->tabtitle['font_style']), (string) $this->grinfo->properties->tabtitle['font_size']);
        }
    }

//  ========== calcul des marges ==========

    /** 	Cette fonction retourne la margin minimum du côté $side en fonction des values des data
     * 	@author	SLC - aout 2008
     * 	@version	CB 4.1.0.0
     * 	@param	string		$side = 'left' || 'right' || 'top' || 'bottom'	désigne le côté que l'on étudie
     * 	@return 	int		renvoi la marge minimum nécessaire pour que les labels des axes et / ou les nom des axes s'affichent enti?rement (ne dépassent pas de l'image)
     */
    function getMinMargin($side) {
        if ($this->debug)
            echo "<br />call to: getMinMargin($side)";

        switch ($side) {
            case 'left':
            case 'right':
                // on boucle sur toutes les valeurs de l'axe correspondant
                $max_strlen = 0;
                foreach ($this->grinfo->datas->data as $data)
                    if ($data['yaxis'] == $side)
                        foreach ($data->value as $value)
                            $max_strlen = max($max_strlen, strlen((string) $value));
                $min_margin = 6 * $max_strlen + 20; // on compte 6px par chiffre +20px de marge
                // on calcule la longueur à d?portation à gauche du premier label de l'axe des abscisses
                // on calcule 3.2px / lettre - 15px pour le label de l'axe Y1
                $deportation = strlen((string) $this->grinfo->xaxis_labels[0]->label) * 3.2 - 15;
                $min_margin = max($min_margin, $deportation);
                break;

            case 'top':
                // on calcule la hauteur du tabTitle	20px si tabTitle sur une ligne, 40px si tabTitle sur deux lignes
                // on commence par calculer la largeur du graphe
                switch ((string) $this->grinfo->properties->legend_position) {
                    case 'left':
                        $largeur_du_graphe = $this->grinfo->properties->width - $this->grinfo->properties->margin_right - max($this->grinfo->properties->margin_left, ($this->margin_left + $this->legend_width));
                        break;
                    case 'right':
                        $largeur_du_graphe = $this->grinfo->properties->width - $this->grinfo->properties->margin_left - max($this->grinfo->properties->margin_right, ($this->margin_right + $this->legend_width));
                        break;
                    default:
                        $largeur_du_graphe = $this->grinfo->properties->width - $this->grinfo->properties->margin_left - $this->grinfo->properties->margin_right;
                        break;
                }
                $min_margin = 20;
                if ($largeur_du_graphe < $this->tabtitle_width)
                    $min_margin = 40;
                // on calcule la hauteur du titre	(forfait de 25px si titre existant)
                // 2010/08/23 - MGD - BZ 15467 : 'title' est un objet pas une chaine de cacatères
                //if ((string) $this->grinfo->properties->title != '')	$min_margin += 25;
                if (isset($this->grinfo->properties->title))
                    $min_margin += 25;
                break;

            case 'bottom':
                // on boucle pour chercher le label de axeX le plus long
                $max_label_length = 0;
                foreach ($this->grinfo->xaxis_labels->label as $label) {
                    // 2010/08/23 - MGD - BZ 15467 : on demande la taille en pixel de chaque label
                    //$max_label_length = max($max_label_length, strlen((string) $label));
                    $max_label_length = max($max_label_length, $this->getTextHeight("$label"));
                }

                // on calcule à la louche la longueur de ce label
                // $min_margin = 30 + ($max_label_length)*3.2;  // 3.2 c'est trop petit pour certains labels
                //$min_margin = 30 + ($max_label_length)*4.6;
                // 2010/08/23 - MGD - BZ 15467 : on arrete le calcul à la louche et on prend la vrai hauteur
                $min_margin = 30 + $max_label_length;
                break;
        }

        return $min_margin;
    }

    /*
     * 2010/08/23 - MGD - BZ 15467 Création de la fonction.
     * Calcul la hauteur d'un texte.
     * L'objectif est de connaitre la taille à reserver pour les labels
     * de l'axe X.
     */

    private function getTextHeight($str) {
        $t = new Text($str);
        $t->SetFont(FF_ARIAL, FS_NORMAL, 7);
        $t->SetOrientation(60);
        $r = $t->GetHeight(new Image(0, 0));
        unset($t);
        return $r;
    }

    /**
     * Cette fonction calcule l'intervale entre les labels sur l'axe des abscisses
     *
     * @version 4.1.0.0
     * @param   void
     * @return  int  Interval de l'axe des abscisses (4, signifie que 1 valeur sur 4 sera affichée)
     */
    public function getAbscisseInterval() {
        if ($this->debug)
            echo "<br />call to: getAbscisseInterval()";

        if ((int) $this->grinfo->xaxis_labels['interval'] > 0)
            return $this->grinfo->xaxis_labels['interval'];

        /*
         * Calcule l'intervalle en fonction du nombre de données d'abscisse à afficher.
         * On ajouter 1 intervale par tranche de 30 labels
         *
         * 10/02/2011 OJT : Nouvelle gestion des intervales (DE Selecteur/Historique)
         */
        return ceil(count($this->grinfo->xaxis_labels->label) / 30);
    }

    /** 	Cette fonction construit l'axe des abscisses. Elle est appelée par buildGraph()
     * 	@author	SLC - aout 2008
     * 	@version	CB 4.1.0.0
     * 	@param	void
     * 	@return 	void
     */
    function makeXaxis() {
        if ($this->debug)
            echo "<br />call to: makeXaxis()";

        $this->graph->xgrid->Show();
        $this->graph->xgrid->SetColor('gray');

        // labels de l'axe des X

        $xaxis_labels = (array) $this->grinfo->xaxis_labels;
        // on verifie qu'on va bien donner un tableau à SetTickLabels() et non pas une chaine
        $temp_xaxis_labels = $xaxis_labels['label'];
        // 11:13 06/08/2009 GHX
        // Modification de la condition sinon erreur sur la fonction array_map si on n'a qu'une seule valeur
        if (!is_array($temp_xaxis_labels))
            $temp_xaxis_labels = array($temp_xaxis_labels);

        // 13:55 03/08/2009 GHX
        // Décodage des éléments html des labels

        $temp_xaxis_labels = array_map('html_entity_decode', $temp_xaxis_labels);

        $this->graph->xaxis->SetTickLabels($temp_xaxis_labels);
        $this->graph->xaxis->SetTextLabelInterval($this->getAbscisseInterval());
        $this->graph->xaxis->setPos('min');
        $this->graph->xaxis->SetFont(FF_ARIAL, FS_NORMAL, 7);
        $this->graph->xaxis->SetLabelAngle(60);

        // Affichage du titre de l'axe des abscisses.
        $this->displayXAxisTitle();
    }

    /**
     * Affiche le titre de l'axe des abscisse sous le graph
     *
     * 27/04/2011 OJT : Externalisation de la fonction pour une utilisation
     * sous tous les types de graphs (Graph, Pie, SingleKpi)
     */
    public function displayXAxisTitle() {
        // Titre de l'axe des X
        $xaxis_title = (string) $this->grinfo->xaxis_labels->attributes()->title;
        $posY = 0.96; // Mise de la valeur en dur à 96% de la hauteur.
        // Création du titre. la position en X est calculé en fonction de la
        // largeur réelle du texte
        $txtX = new Text($xaxis_title);
        $txtX->SetPos(1 - ( ( $txtX->GetWidth($this->graph->img) + 5 ) / $this->grinfo->properties->width ), $posY, "left", "top");
        $txtX->SetOrientation("0");
        $txtX->SetColor("black");
        $this->graph->AddText($txtX);
    }

    /** 	Cette fonction construit et positionne la légende
     * 	@author	SLC - aout 2008
     * 	@version	CB 4.1.0.0
     * 	@param	void
     * 	@return 	void
     */
    function makeLegend() {
        if ($this->debug)
            echo "<br />call to: makeLegend()";



        // gestion de la légende
        $this->graph->legend->SetFillColor('#fafafa@0.7');
        $this->graph->legend->Setshadow(false);

        // on calcule la place que prend la légende
        // création d'un object Text qui permettra de calculer la longueur de la chaine en pixels dans le graph
        $textTMP = new Text('text');
        // on lui spécifie le même style que la légende, afin d'avoir les mêmes longueurs
        // ATTENTION les attributs 'font_XXX' sont normalement des attributs privés donc logiquement inaccessibles directement
        $textTMP->SetFont($this->graph->legend->font_family, $this->graph->legend->font_style, $this->graph->legend->font_size);

        $legend_position = (string) $this->grinfo->properties->legend_position;

        if (($legend_position == 'right') || ($legend_position == 'left')) {

            // recherche la longueur maximale de la légende
            $all_legends = array();
            for ($i = 0; $i < $this->nb_plots; $i++)
                $all_legends[] = $this->plot[$i]->legend;
            $this->legend_width = $this->getLegendWidth($all_legends);
            $this->graph->legend->SetColumns($this->legend_nb_col);
        }

        if (($legend_position == 'top') || ($legend_position == 'bottom')) {
            /* 	modif 19/03/2007 Gw?na?l
              - on calcule le nombre de colonne
              - on calcule ensuite le nombre de ligne
              - si le nombre ligne est supérieure à 1
              - on récupère pour chaque colonne la longueur la plus grande
              - on fait la somme des valeurs trouvées et on la compare avec la largeur du graphe
              - si la somme est supérieure
              - on diminue le nombre de colonne et on refait un test
              - on spécifie le nombre de colonne dans la légende
              - on modifie la valeur de la marge du haut en fonction du nombre de lignes
             */
            $longueur_max_legend = (int) $this->grinfo->properties->width - 25; //La légende sera au maximum la longueur du graph moins une marge de 25 pixels
            if ($this->debug)
                echo "<br />longueur max legend : $longueur_max_legend";
            $longueur_totale = 0;
            $nombre_colonne = 0;
            $nombre_colonne_final = 50; // on fixe arbitrairement un nombre de colonnes très élevé
            // on bloucle sur toutes les légendes
            if ($this->debug)
                echo "<br />nb plots : $this->nb_plots";
            if ($this->debug)
                echo "<table><tr><th>plot[\$i]</th><th>légende</th><th>longueur</th><th>total</th><th>nb_col</th><th>nb_col final</th></tr>";
            // 22/05/2012 NSE bz 27162 : correction de la largeur du tableau pour prendre en compte les largeurs des différentes colonnes
            // on met en premier dans le tableau les légendes les plus longues
            // on prévoit ainsi le cas le plus défavorable de largeur de tableau 
            // (cas dans lequel les légendes les plus longues sont toutes dans des colonnes différentes)
            for ($i = 0; $i < $this->nb_plots; $i++) {
                $textTMP->Set($this->plot[$i]->legend);
                $longueur[$i] = $textTMP->GetWidth($this->graph->img);
            }
            array_multisort($longueur, SORT_DESC, $this->plot);

            for ($i = 0; $i < $this->nb_plots; $i++) {    // print_r($this->plot[$i]);echo '<hr />';
                $textTMP->Set($this->plot[$i]->legend); // spécifie le texte de la légende pour pouvoir récupérer sa longueur
                $longueur_element = $textTMP->GetWidth($this->graph->img) + 38; // on prend 38px de marge par élément
                $longueur_totale += $longueur_element; // récupère la longueur du texte
                if ($longueur_totale < $longueur_max_legend) {
                    $nombre_colonne++;
                } else {
                    $nombre_colonne_final = min($nombre_colonne_final, $nombre_colonne);
                    $nombre_colonne = 1;
                    $longueur_totale = $longueur_element;
                }
                if ($this->debug)
                    echo "<tr><td>plot[$i]->legend</td><td>{$this->plot[$i]->legend}</td><td align='right'>$longueur_element</td><td align='right'>$longueur_totale</td><td align='right'>$nombre_colonne</td><td align='right'>$nombre_colonne_final</td>";
            }
            if ($this->debug)
                echo "</table>";
            // on vérifie si la dernière longueur ajoutée ne dépasse pas la longueur maximum
            if ($longueur_totale > $longueur_max_legend)
                $nombre_colonne_final = $nombre_colonne - 1;
            // on calcule le nombre de ligne qu'il y aura dans la légende
            $nombre_ligne = ceil($this->nb_plots / $nombre_colonne_final);
            if ($nombre_ligne > 1) {
                $longueur_max_legend = (int) $this->grinfo->properties->width;
                do {
                    $continuer = true;
                    $longueurs_max = array();
                    // on récupère pour chaque colonne la longueur de texte la plus grande
                    for ($i = 0; $i < $this->nb_plots; $i++) {
                        $textTMP->Set($this->plot[$i]->legend);
                        $longueur = $textTMP->GetWidth($this->graph->img);
                        if ($longueur > $longueurs_max[$i % $nombre_colonne_final])
                            $longueurs_max[$i % $nombre_colonne_final] = $longueur;
                    }
                    if (array_sum($longueurs_max) > $longueur_max_legend) {
                        // on décrémente le nombre de colonnes
                        $nombre_colonne_final--;
                        // et on refait un test
                        $continuer = false;
                    }
                } while (!$continuer);
            }
            // on recalcule le nombre de lignes au cas où le nombre de colonnes ait changé
            $nombre_ligne = ceil($this->nb_plots / $nombre_colonne_final);
            $this->legend_height = ($nombre_ligne * 14) + 20;

            $this->legend_nb_col = $nombre_colonne_final;
        }

        // on cache le texte et on le supprime
        $textTMP->Hide(); // ne sert à rien mais par précaution
        unset($textTMP);

        // 21/11/2011 BBX
        // Correction de messages "Notices" vu pendant les corrections
        switch ($legend_position) {
            case 'left':
                // on place la légende
                $this->graph->legend->setLayout(LEGEND_VER);
                $this->graph->legend->SetPos(0.01, 0.5, "left", "center");
                // on a $this->legend_width
                break;
            case 'right':
                // on place la légende
                $this->graph->legend->setLayout(LEGEND_VER);
                $this->graph->legend->SetPos(0.01, 0.5, "right", "center");
                // on a $this->legend_width
                break;
            case 'top':
                // on place la légende
                $this->graph->legend->setLayout(LEGEND_HOR);
                // on positionne la légende en fonction du nombre de ligne
                $this->graph->legend->SetPos(0.5, (0.01), "center", "top");
                // on a $this->legend_height;
                break;
            case 'bottom':
                // on place la légende
                $this->graph->legend->setLayout(LEGEND_HOR);
                // on positionne la légende en fonction du nombre de ligne
                $this->graph->legend->SetPos(0.5, 0.99, "center", "bottom");
                // on a $this->legend_height;
                break;
        }

        // on spécifie le nombre de colonnes dans la légende
        $this->graph->legend->SetColumns($this->legend_nb_col);
    }

    /** 	Cette fonction donne la largeur des barres des plots bar et cumulatedbar
     * 	@author	SLC - aout 2008
     * 	@version	CB 4.1.0.0
     * 	@param	void
     * 	@return 	double	retourne la largeur des barres
     */
    function getBarWidth() {
        if ($this->debug)
            echo "<br />call to: getBarWidth()";

        $nb_elements = count($this->grinfo->datas->data[0]->value);

        // 21/11/2011 BBX
        // Correction de messages "Notices" vu pendant les corrections
        // il manquait le "s" à nb_elements
        if ($nb_elements < 9)
            return 0.3;
        if ($nb_elements < 20)
            return 0.4;
        if ($nb_elements < 40)
            return 0.5;
        if ($nb_elements < 60)
            return 0.6;
        return 0.5;
    }

    /**
     * Cette fonction prépare les éléments de type Plot
     * 14/09/2011 BBX : modifié pour le BZ 20799
     * @param type $type
     * @param type $side 
     */
    function attachPlots(&$finalElements, $groups) {
        foreach ($groups as $type => $groupsByAxis) {
            foreach ($groupsByAxis as $axis => $data) {
                $order = '';
                $group_array = array();
                foreach ($data as $id => $plot) {
                    $order = $id;
                    $group_array[] = $plot;
                }

                switch ($type) {
                    case 'bar':
                        $group = new GroupBarPlot($group_array);
                        $group->SetWidth($this->getBarWidth());
                        break;
                    case 'cumulatedbar':
                        $group_array = array_reverse($group_array);
                        $group = new AccBarPlot($group_array);
                        $group->SetWidth($this->getBarWidth());
                        break;
                    case 'cumulatedline':
                        $group = new AcclinePlot($group_array);
                        // Gestion des valeurs négatives
                        $this->manageNegValues($plot, $axis);
                        break;
                }

                // Ajout des éléments groupés au tableau final
                // l'ordre conservé est celui du dernier élément du groupe
                $finalElements[$order] = array($group, $axis);
            }
        }
    }

    /**
     * Cette fonction prépare les éléments de type line
     * 14/09/2011 BBX : modifié pour le BZ 20799
     * @param type $finalElements
     * @param type $elements 
     */
    function attachLines(&$finalElements, $elements) {
        foreach ($elements as $axis => $data) {
            foreach ($data as $id => $plot) {

                // Gestion des valeurs négatives
                $this->manageNegValues($plot, $axis);

                // Ajout de éléments simples au tableau final
                $finalElements[$id] = array($plot, $axis);
            }
        }
    }

    /**
     * Cette fonction ajoute les éléments au graphe
     * 14/09/2011 BBX : ajouté pour le BZ 20799
     * @param type $finalElements 
     */
    function attachElements(&$finalElements) {
        ksort($finalElements);
        foreach ($finalElements as $ordre => $data) {
            $toAdd = $data[0];
            $axis = $data[1];
            if ((string) $axis == 'left')
                $this->graph->Add($toAdd);
            else
                $this->graph->AddY2($toAdd);
        }
    }

    /** 	Cette fonction trouve la plus grande valeur des courbes cumulatedbar associées à l'un ou l'autre graph
     * 	@author	SLC - aout 2008
     * 	@version	CB 4.1.0.0
     * 	@param	string		$yaxis = 'left' || 'right'	désigne l'axe des Y à étudier
     * 	@return 	void
     */
    function getMaxValueCumulatedBar($yaxis) {
        if ($this->debug)
            echo "<br />call to: getMaxValueCumulatedBar($yaxis)";

        for ($i = 0; $i < $this->nb_plots; $i++) {
            $data = $this->grinfo->datas[0]->data[$i];
            $type = (string) $data['type'];
            $side = (string) $data['yaxis'];
            // pour corriger un bug JpGraph pour les courbes "line" n'ayant qu'une seule valeur, on a besoin de savoir si on a des barplots
            if ($type == 'cumulatedbar') {
                if ($side == $yaxis) {
                    // on initialise $max
                    if (!isset($max))
                        $max = $data->value[0];
                    // on boucle sur les valeurs
                    $nb_values = count($data->value);
                    for ($j = 0; $j < $nb_values; $j++)
                        $max = max($max, $data->value[$j]);
                }
            }
        }
        return $max;
    }

    /** 	Cette fonction gère les valeurs négatives
     * 	@author	SLC - aout 2008
     * 	@version	CB 4.1.0.0
     * 	@param	void
     * 	@return 	void
     */
    function manageNegValues(&$plot, $axis) {
        // 07/10/2011 BBX
        // BZ 23719 : gestion valeurs négatives et couleur de remplissage
        if (method_exists($plot, 'SetFillFromYMax')) {
            if ($axis == 'left' && $this->has_negvalue_left)
                $plot->SetFillFromYMax(true);
            if ($axis == 'right' && $this->has_negvalue_right)
                $plot->SetFillFromYMax(true);
        }
        return;
    }

    /** 	Cette fonction ajoute les noms des axes des Y
     * 	@author	SLC - aout 2008
     * 	@version	CB 4.1.0.0
     * 	@param	void
     * 	@return 	void
     */
    function addAxeYNames() {
        if ($this->debug)
            echo "<br />call to: addAxeYNames()";

        // on ajout le nom de axeY
        $txtY1 = new Text((string) $this->grinfo->properties->left_axis_label);
        $posX = 0.005;
        // on ajoute la largeur de la légende si elle est à gauche
        if ((string) $this->grinfo->properties->legend_position == 'left')
            $posX = 0.005 + ($this->legend_width / $this->grinfo->properties->width);
        $txtY1->SetPos($posX, 0.45, "left", "center");
        $txtY1->SetOrientation("90");
        $txtY1->SetColor((string) $this->grinfo->properties->left_axis_label['color']);
        $this->graph->AddText($txtY1);

        // on ajoute le nom de axeY2
        if ($this->has_axeY2) {
            $txtY2 = new Text((string) $this->grinfo->properties->right_axis_label);
            $posX = 0.992;
            // on retranche la largeur de la légende si elle est à droite
            if ((string) $this->grinfo->properties->legend_position == 'right')
                $posX = 0.992 - ($this->legend_width / $this->grinfo->properties->width);
            $txtY2->SetPos($posX, 0.45, "right", "center");
            $txtY2->SetOrientation("90");
            $txtY2->SetColor((string) $this->grinfo->properties->right_axis_label['color']);
            $this->graph->AddText($txtY2);
        }
    }

    /** 	Cette fonction ajoute les lignes horizontales spécifiées par les tags <horizontal_line legend="66 000" color="firebrick3" yaxis="left">66000</horizontal_line>
     * 	@author	SLC - aout 2008
     * 	@version	CB 4.1.0.0
     * 	@param	void
     * 	@return 	void
     */
    function addHorizontalLines() {
        if ($this->debug)
            echo "<br />call to: addHorizontalLines()";

        foreach ($this->grinfo->properties->horizontal_line as $horizontal_line) {
            // on ajout un trait au niveau de l'abscisse 0
            $line = new PlotLine(HORIZONTAL, (string) $horizontal_line, (string) $horizontal_line['color'], 1);
            // légende de la barre horizontale
            $legend = (string) $horizontal_line["legend"];
            $legend .= '(' . ucfirst((string) $horizontal_line['yaxis']) . ')';
            $line->setLegend($legend);
            // on ajoute la barre à la liste des plots
            $this->plot[] = $line;
            $this->nb_plots++;
            if ((string) $horizontal_line['yaxis'] == 'left') {
                $this->graph->AddLine($line);
            } else {
                $this->graph->AddLine($line, true);
            }
            unset($line);
        }
    }

    /** 	Cette fonction génère le tag <IMG> et l'image map pour le graphique
     * 	Elle génère aussi le fichier image qu'elle écrit sur le disque
     * 	@author	SLC - aout 2008
     * 	@version	CB 4.1.0.0
     * 	@param	string		$filename nom du fichier image à écrire sur le disque
     * 	@return 	string		renvoie le tag <IMG> et l'image map correspondant au graph généré
     */
    function getHTML($filename = '') {
        if ($this->debug)
            echo "<br />call to: getHTML($filename)";

        $this->setErrorIsImage(false);

        // on génère et sauvegarde l'image
        $this->saveImage($filename);

        // on génère le tag <img> et le code de l'imagemap
        if ((int) $this->grinfo->properties->interactivity) {
            $imagemap = $this->graph->GetHTMLImageMap(substr($this->filename, 0, -4));
            $tag = "$imagemap<img id=\"img_" . $this->filename . "\" src=\"" . $this->grinfo->properties->base_url . $this->filename . "\" ismap='ismap' usemap=\"#" . substr($this->filename, 0, -4) . "\" border='0' />";
        } else {
            $tag = "<img id=\"img_" . $this->filename . "\" src=\"" . $this->grinfo->properties->base_url . $this->filename . "\" border='0' />";
        }
        return $tag;
    }

    public function setHTMLUrl($html_url) {
        $this->htmlUrl = $html_url;
    }

    /**
     * Retourne les dimensions de l'image du GTM généré
     *
     * @return array liste d'informations sur l'image (dont la largeur et la hauteur)
     */
    private function getGTMImageSize() {
        if (is_file($this->grinfo->properties->base_dir . $this->filename)) {
            return getimagesize($this->grinfo->properties->base_dir . $this->filename);
        } else {
            return false;
        }
    }

    private function displayError($message) {
        return '<div class="graph-no-data" style="background: no-repeat 10px url(' . NIVEAU_0 . 'images/graph/gtm_error.png) #fde6e6;">' . $message . '</div>';
    }

    /**
     * Génére le code HTML du GTM à afficher (cadre, image, map)
     * 
     * 20/03/2009 - modif SPS : gestion des commentaires
     * 16/08/2010 OJT : Correction bz16915  modification de l'identifiant de la balise de titre de graph
     * 
     * @param string $gtm_name nom du GTM
     * @param string $filename nom de l'image du GTM à afficher
     * @param array $bar_properties liste des propriétés de la barre de titres du GTM
     * @param boolean $gtm_bottom etat de l'affichage de la barre des commentaires + retour en haut de page sous le GTM
     * @param boolean $data_exist des données existent pour le GTM
     * @param string $gtm_message message à afficher lorsque le GTM ne contient pas de données
     * @return string code HTML du GTM
     */
    function getHTMLFrame($gtm_name, $filename = '', $bar_properties, $gtm_bottom = true, $data_exist = true, $gtm_message = '') {
        if ($this->debug)
            echo "<br/>call to getHTMLFrame()";

        /* 20/03/2009 - modif SPS : gestion des commentaires */
        //on recupere les identfiants que l'on veut passer pour la page des commentaires
        $id_gtm = $bar_properties['index']['gtm_id'];
        $id_dash = $_GET['id_dash'];
        $ta = $_SESSION['TA']['selecteur']['ta'];
        $ta_value = $_SESSION['TA']['selecteur']['ta_value'];
        $period = $_SESSION['TA']['selecteur']['period'];
        $na_axe1 = $_SESSION['TA']['selecteur']['na_axe1'];
        $ne_label = addslashes($bar_properties['index']['ne_label']);
        $gtm_infos = $bar_properties['index'];

        // 1 - Définition de la barre de titre du GTM
        // 16/08/2010 OJT : Correction bz16915, modification de l'identifiant de la balise
        $gtm_title = '<div id=\'' . $gtm_infos['gtm_id'] . '_' . $gtm_infos['ne'] . '_title\' >';

        // 1.1 - Nom du dashboard et du GTM
        $gtm_title .= '	<div id="gtm_infos" class="gtmTitleBack">';
        $gtm_title .= '		<img class="imgTitle" src="' . $this->htmlUrl . 'images/graph/puce_graph.gif" alt="arrow"/>';
        $gtm_title .= '		<span class="dashTitle">' . $bar_properties['title']['dash'] . '</span>';
        $gtm_title .= '		<img class="imgTitle" src="' . $this->htmlUrl . 'images/graph/btn_comment.gif" alt="" onMouseOver="popalt(\'' . __T('U_TOOLTIP_SHOW_COMMENTS') . '\'); style.cursor=\'pointer\';" onMouseOut=\'kill()\' onClick="ouvrir_fenetre(\'php/comment_list.php?dashboard=' . $bar_properties['title']['dash'] . '&params_list=dashboard@' . $id_dash . '@' . $ta . '@' . $na_axe1 . '@' . $ne_label . '@' . $ta_value . '\',\'comment\',\'yes\',\'yes\',750,550);" />';
        $gtm_title .= '		<span class="gtmTitle">- ' . $bar_properties['title']['gtm'] . '</span>';
        $gtm_title .= '	</div>';

        // 1.2 - Barre d'icones
        $gtm_title .= '	<div id="gtm_icons" class="gtmIcons">';
        $gtm_title .= ' <ul>';

        // 1.2.1 - Icone Excel

        $gtm_buttons = $bar_properties['buttons'];

        $excel = $gtm_buttons['excel'];

        if (count($excel) > 0) {
            // 22/11/2011 BBX
            // BZ 24764 : correction des notices php
            $gtm_title .= '<li><a target="' . (isset($excel['target']) ? $excel['target'] : '') . '" href="' . $excel['link'] . '"><img src="' . $excel['img'] . '" onmouseout="kill()" onmouseover="popalt(\'' . $excel['msg'] . '\'); style.cursor=\'pointer\';"/></a></li>';
        }

        // 1.2.X - Autres icones (même appel)

        foreach ($gtm_buttons as $key => $value) {
            if ($key != 'excel' && count($value) > 0) {
                // 20/09/2010 BBX
                // On ajoute un id sur le bouton pour pouvoir le modifier
                // BZ 11945
                $buttonId = !empty($value['id']) ? ' id="' . $value['id'] . '"' : '';
                $gtm_title .= '<li><img onclick="' . $value['link'] . '" onmouseout="kill()" onmouseover="popalt(\'' . $value['msg'] . '\'); style.cursor=\'pointer\';" src="' . $value['img'] . '"' . $buttonId . ' /></li>';
            }
        }

        $gtm_title .= '	</ul>';
        $gtm_title .= '	</div>';
        $gtm_title .= '</div>';

        // 17:30 25/08/2009 GHX
        // Correctoin du BZ 11188
        $gtm_title .= '	<div class="updateCaddy" id="updateCaddy_' . substr(basename($filename), 0, -4) . '" style="display:none">' . __T('U_CART_UPDATED') . '</div>';

        // 2 - Définition de l'image du GTM

        if ($data_exist === TRUE) {
            //$gtm_img  = '<div id="gtm_picture" class="imgGraph contourImage fondGraph">';
            /* 07/05/2009 - SPS : ajout du nom du fichier a l'id */
            //on recupere le nom du fichier genere et on vire l'extension
            $file = substr(basename($filename), 0, -4);
            $gtm_img = '<div id="gtm_picture_' . $file . '" class="imgGraph contourImage fondGraph">';
            $gtm_img .= $this->getHTML($filename);
            $gtm_img .= '<div id=\'gtm_dt_' . $file . '\' class=\'dataTableGraph\' style=\'display:none;width:' . $this->grinfo->properties->width . 'px;\'></div>';
            $gtm_img .= '</div>';

            // Une fois l'image définie, on récupère sa taille
            $gtm_size = $this->getGTMImageSize();
            $gtm_width = $gtm_size[0];
        } else {
            $gtm_width = 700;

            $gtm_img = '<div id="gtm_picture" class="imgGraph contourImage" style="width:' . $gtm_width . 'px">';
            $gtm_img .= $this->displayError($gtm_message);
            $gtm_img .= '</div>';
        }

        $gtm_com = '';
        $gtmDataTableIcones = '';
        $gtm_top = '';

        if ($gtm_bottom) {
            // 3 - Définition de la barre de commentaires
            $type_elem = $this->grinfo->properties->type;
            $param_liste = $type_elem . '@' . $id_gtm . '@' . $ta . '@' . $na_axe1 . '@' . $ne_label . '@' . $ta_value;

            /* 20/03/2009 - modif SPS : gestion des commentaires */
            //requete de selection du dernier commentaire sur le graph	
            $query = "
			SELECT libelle_comment
			FROM edw_comment
			WHERE id_elem = '$id_gtm'
			AND type_elem = '$type_elem'
			";

            if ($type_elem == "graph" || $type_elem == "pie") {
                $query .= "
						AND na = '$na_axe1'
						AND na_value = '$ne_label'
						AND ta = '$ta'
						AND date_selecteur = '$ta_value'
					";
            }
            $query .= "
				ORDER BY date_ajout DESC
			";
            //connexion a la base
            $db = Database::getConnection(0);
            $c = $db->getRow($query);
            //s'il y a un commentaire, on l'affiche sinon on affiche un message pour dire qu'il n'y a pas de commentaire
            if ($c['libelle_comment'] != "") {
                $comment = $c['libelle_comment'];
            } else {
                // 22/11/2011 BBX
                // BZ 24764 : correction des notices php
                $comment = __T('U_NO_COMMENT');
            }

            // Gestion de l'affichage de la table de données
            if ($data_exist === TRUE) {
                $gtmDataTableIcones = '<td>
                    <img
                        id=\'gtm_dt_' . $file . '_open\'
                        style=\'float:right;\'
                        src="' . $this->htmlUrl . 'images/graph/open_data_table.gif"
                        onmouseover="popalt(\'' . __T('U_TOOLTIP_OPEN_DATA_TABLE') . '\');style.cursor=\'pointer\';"
                        onmouseout="kill();"
                        onclick="showDataTable( \'' . $this->xmlFile . '\', \'' . $file . '\' );"
                    />
                    <img
                        id=\'gtm_dt_' . $file . '_close\'
                        style=\'float:right;display:none;\'
                        src="' . $this->htmlUrl . 'images/graph/close_data_table.gif"
                        onmouseover="popalt(\'' . __T('U_TOOLTIP_CLOSE_DATA_TABLE') . '\');style.cursor=\'pointer\';"
                        onmouseout="kill()"
                        onclick="showDataTable( \'' . $this->xmlFile . '\', \'' . $file . '\' );"
                    />
                    <img
                        id=\'gtm_dt_' . $file . '_wait\'
                        style=\'float:right;display:none;\'
                        src="' . $this->htmlUrl . 'images/graph/wait_data_table.gif"
                    />
                </td>';
            } else {
                // Aucune donnée, on n'affiche pas les icones de gestion de la table de données
            }

            /* 09/04/2009 : SPS : ajout de propriete pour l'affichage */
            //on ajoute la liste des param a l'id du bloc pour identifier ce bloc
            $gtm_com = '<div id="comment_block" style="clear:both;width:' . ($gtm_width + 10) . 'px">						
							<table width="100%" cellPadding="0" cellSpacing="0">
								<tr>
									<td width="100%">
										<fieldset>
											<div 
                                                id="last_comment_' . $param_liste . '"
                                                class="gtmComment"
                                                onclick="ouvrir_fenetre(\'php/comment_list.php?id_dernier_commentaire=last_comment_' . $param_liste . '&amp;params_list=' . $param_liste . '\',\'comment\',\'yes\',\'yes\',750,550);"
                                                onmouseover="popalt(\'' . __T('U_TOOLTIP_SHOW_COMMENTS') . '\'); style.cursor=\'pointer\';"
                                                onmouseout="kill()"
                                                style="float:left;cursor:pointer;width:95%;text-decoration:underline;"
                                            >' .
                    $comment .
                    '</div>
                                            <img
                                                style=\'float:right;\'
                                                src="' . $this->htmlUrl . 'images/graph/add_comment.gif"
                                                alt=""
                                                onmouseover="popalt(\'' . __T('U_TOOLTIP_ADD_COMMENT') . '\');style.cursor=\'pointer\';"
                                                onmouseout=\'kill()\'
                                                onclick="toggle_commentaire(\'ajouter_commentaire\',\'&params_liste=' . $param_liste . '\',\'last_comment_' . $param_liste . '\',\'last_comment_' . $param_liste . '\'); initComment();"
                                            />
										</fieldset>
									</td>' .
                    $gtmDataTableIcones .
                    '</tr>
							</table>
						</div>';

            // 4 - Définition de la barre permettant de revenir en haut de page
            // 13/08/2010 OJT : Correction bz16916 pour DE Firefox, modification du href
            $gtm_top = '<div id="back_to_top" class="backToTop" style="width:' . ($gtm_width + 10) . 'px"><a href="#haut_appli"><img src="' . $this->htmlUrl . '/images/graph/back_to_top.gif" border="0"></a></div>';
        }

        // 5 - Mise en commun des différentes parties			
        return
                '<input type="hidden" id="' . $gtm_infos['gtm_id'] . '_' . $gtm_infos['ne'] . '"/>' .
                '<div
                id="' . $gtm_infos['gtm_id'] . '"
                gtm_name="' . $gtm_infos['gtm_name'] . '"
                ne="' . $gtm_infos['ne'] . '"
                ne_label="' . $gtm_infos['ne_label'] . '"
                class="gtm"
                style="width:' . $gtm_width . 'px"
            >' .
                $gtm_title .
                $gtm_img .
                $gtm_com .
                $gtm_top .
                '</div>';
    }

    /**
     * fonction qui genere les balises maps et l'image (utilisee par la fonction d'ajout de ligne)
     * 
     * @date 07/05/2009
     * @author SPS
     * @param string $filename nom du fichier
     * @param string $filepath chemin du fichier
     * @param string $http_dir chemin pour l'affichage de l'image
     * @return string retourne les balises map ainsi que l'image (apres ajout d'une ligne)
     * */
    function getMap($filename, $filepath, $http_dir) {
        //on recupere le nom du fichier sans l'extension
        $file = substr($filename, 0, -4);
        //on genere l'image
        $this->stroke($filepath);
        //on genere les balises map
        $text = $this->graph->GetHTMLImageMap($file);
        //on ajoute la balise img avec la nouvelle image
        $text .= "<img id=\"img_" . $filename . "\" src=\"" . $http_dir . $filename . "\" ismap='ismap' usemap=\"#" . $file . "\" />";
        return $text;
    }

    /**
     * Fonction qui permet de créer une image avec le message d'erreur
     * 
     * 	17/08/2009 GXH
     * 		- Ajout de la fonction pour contourner un probleme dans l'export des raports si un des dashboard d'un des rapports n'a pas de données Erreur JPGRAPH
     *
     * @param string $filename nom du fichier image
     * @param string $msg
     */
    public function createImageNoData($filename, $msg) {
        $graph = new CanvasGraph(700, 60);

        $msg = str_replace(array('<br>', '<br />', '<br/>'), "\n", $msg);
        $msg = str_replace('Information :', "", $msg);

        $txt = new Text(strip_tags($msg), 20, 20);
        $txt->SetFont(FF_FONT1);
        $txt->SetBox("white", "white", false, 0, 0);
        $txt->SetColor("black");
        $txt->SetShadow("white", 0);
        $graph->AddText($txt);

        $graph->stroke((string) $this->grinfo->properties->base_dir . $filename);
    }

}
