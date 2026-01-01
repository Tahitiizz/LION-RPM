<?php
/*
*	CLASSE PHPOdf
*	Cette classe va permettre de créer un fichier odt et de l'exporter en odt, doc, xls ou pdf.
*
*	@version : 4.1.0.00
*	@author : BBX
*	@date : 07/01/2009
*
*	31/07/2009 BBX : gestion des commentaires. BZ 10633
*	30/10/2009 BBX : meilleure gestion de l'auto resize des images des graphes. BZ 12359
*   22/12/2010 SCT : correction de la hauteur des images (si hauteur trop importante). BZ 18725
*   26/04/2011 NSE évolution urgente aircel : augmentation de la taille du titre des rapports
*   20/10/2015 JLG : Graphs comments are truncated when exported. BZ 50342
*
*/
class PHPOdf 
{
	/**
	*	30/10/2009 BBX : BZ 12359. Ajout des 2 constantes suivantes : 
	*	CONSTANTES
	**/
	
	// hauteur max d'un graphe landscape
	const MAX_HEIGHT_LANDSCAPE = '13';
	// hauteur max d'un graphe portrait
	const MAX_HEIGHT_PORTRAIT = '9';
        // 26/04/2011 NSE évolution urgente aircel
        // longueur max du titre d'un graph en landscape
	const MAX_TITLE_LENGTH_LANDSCAPE = '120';
        // longueur max du titre d'un graph en portrait
	const MAX_TITLE_LENGTH_PORTRAIT = '110';
        // longueur max du titre d'un graph en landscape 4 par page
	const MAX_TITLE_LENGTH_LANDSCAPE4 = '132';
        // Hauteur max logo client
        const CLIENT_LOGO_MAX_HEIGHT = 3;
	
	/**
	*	VARIABLES
	**/
	
	// Répertoire temporaire
	private $tmpDir = '/tmp/';
	// Répertoire de travail
	private $workDir = '';
	// Arboresence par défaut
	private $tree = Array(
		'/Configurations2',
		'/META-INF',
		'/Thumbnails',	
		'/Pictures',
		'/Configurations2/accelerator',
		'/Configurations2/floater',
		'/Configurations2/images',
		'/Configurations2/menubar',
		'/Configurations2/popupmenu',
		'/Configurations2/statusbar',
		'/Configurations2/toolbar',		
		'/Configurations2/images/Bitmaps');
	// Fichiers par défaut
	private $files = Array (
		'/content.xml',
		'/meta.xml',
		'/mimetype',
		'/settings.xml',
		'/styles.xml',
		'/Configurations2/accelerator/current.xml',
		'/META-INF/manifest.xml');
	// Auteur par défaut
	private $author = 'Astellia';
	// Nom du fichier
	private $filename = '';
	// Styles Office (fichier styles.xml)
	private $officeStyles = Array();
	// Styles Office (fichier content.xml)
	private $contentOfficeStyles = Array();
	// Style master page (fichier styles.xml)
	private $masterStyles = Array();
	// Style footer par défaut
	private $footerStyle = '<style:footer-style/>';
	// Style header par défaut
	private $headerStyle = '<style:header-style/>';
	// Styles type "automatic" (fichier content.xml)
	private $automaticStyles = Array();
	// Styles type "automatic" (fichier styles.xml)
	private $stylesAutomaticStyles = Array();
	// Types mime des images
	private $imageMimeTypes = Array(
		'image/png'=>'png',
		'image/gif'=>'gif',
		'image/jpg'=>'jpg',
		'image/jpeg'=>'jpg',
		'image/tiff'=>'tif');
	// Orientation par défaut
	private $orientation = 'portrait';
	// Largeur par défaut (A4 portrait)
	private $pageWidth = '20.999cm';
	// Hauteur par défaut (A4 portrait)
	private $pageHeight = '29.699cm';
	// Marges
	private $pageMarginTop = '1cm';
	private $pageMarginLeft = '1cm';
	private $pageMarginRight = '1cm';
	private $pageMarginBottom = '1cm';		
	// Ce tableau va gérer le contenu du fichier content.xml
	private $contentXML = Array(
		// Déclaration des polices
		'font-face-decls' => Array(),
		// Styles automatiques
		'automatic-styles' => Array(),
		// Contenu du document
		'content' => ''
	);	
	// Ce tableau va gérer le contenu du fichier styles.xml
	private $stylesXML = Array(
		// Déclaration des polices
		'font-face-decls' => Array(),
		// Styles Office
		'styles' => Array(),
		// Styles automatiques
		'automatic-styles' => Array(),
		// Styles de la page master
		'master-styles' => Array(),
	);	
	// Contiendra les images du document
	private $images = Array();
	// Commentaire GTM
	private $lastComment = '';
	
	/****
	* Constructeur
	* @param string filename
	* @param string orientation (portrait / landscape)
	*****/
	public function __construct($filename,$orientation='portrait') {
		$this->filename = $filename;
		$this->orientation = $orientation;
		if($this->orientation == 'landscape') {
			$this->pageWidth = '29.699cm';
			$this->pageHeight = '20.999cm';
		}
		$this->setMargins('1','1','1','1');
	}

	/****
	* setMargins
	* Redéfinit les marges
	* @param String : margin-top in cm
	* @param String : margin-left in cm
	* @param String : margin-right in cm
	* @param String : margin-bottom in cm
	*****/	
	public function setMargins($top,$left,$right,$bottom) {
		$this->pageMarginTop = $top.'cm';
		$this->pageMarginLeft = $left.'cm';
		$this->pageMarginRight = $right.'cm';
		$this->pageMarginBottom = $bottom.'cm';	
	}

	/****
	* setAuthor
	* Redéfinit l'auteur du document
	* @param String : auteur
	*****/		
	public function setAuthor($author) {
		$this->author = $author;
	}
	
	/****
	* getPageLayout
	* Définit la structure des pages
	* @return String : code xml définissant la structure de la page
	*****/
	public function getPageLayout() {
		return '<style:page-layout style:name="Mpm1"><style:page-layout-properties fo:page-width="'.$this->pageWidth.'" fo:page-height="'.$this->pageHeight.'" style:num-format="1" style:print-orientation="'.$this->orientation.'" fo:margin-top="'.$this->pageMarginTop.'" fo:margin-bottom="'.$this->pageMarginBottom.'" fo:margin-left="'.$this->pageMarginLeft.'" fo:margin-right="'.$this->pageMarginRight.'" style:writing-mode="lr-tb" style:footnote-max-height="0cm"><style:footnote-sep style:width="0.018cm" style:distance-before-sep="0.101cm" style:distance-after-sep="0.101cm" style:adjustment="left" style:rel-width="25%" style:color="#000000"/></style:page-layout-properties>'.$this->headerStyle.''.$this->footerStyle.'</style:page-layout>';	
	}
	
	/****
	* getStylesXml
	* Retourne le contenu du fichier styles.xml
	* @return String : contenu du fichier styles.xml
	*****/
	public function getStylesXml() {
		// Header du document
		$retour = '<?xml version="1.0" encoding="UTF-8"?>
<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0" xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0" xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0" xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0" xmlns:math="http://www.w3.org/1998/Math/MathML" xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0" xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0" xmlns:ooo="http://openoffice.org/2004/office" xmlns:ooow="http://openoffice.org/2004/writer" xmlns:oooc="http://openoffice.org/2004/calc" xmlns:dom="http://www.w3.org/2001/xml-events" xmlns:rpt="http://openoffice.org/2005/report" xmlns:of="urn:oasis:names:tc:opendocument:xmlns:of:1.2" xmlns:rdfa="http://docs.oasis-open.org/opendocument/meta/rdfa#" office:version="1.2">';
		// Génération des polices du document
		$fontFaceDecls = '<office:font-face-decls><style:font-face style:name="Tahoma1" svg:font-family="Tahoma"/><style:font-face style:name="Times New Roman" svg:font-family="&apos;Times New Roman&apos;" style:font-family-generic="roman" style:font-pitch="variable"/><style:font-face style:name="Arial" svg:font-family="Arial" style:font-family-generic="swiss" style:font-pitch="variable"/><style:font-face style:name="Arial Unicode MS" svg:font-family="&apos;Arial Unicode MS&apos;" style:font-family-generic="system" style:font-pitch="variable"/><style:font-face style:name="Tahoma" svg:font-family="Tahoma" style:font-family-generic="system" style:font-pitch="variable"/>';
		// Ajout des polices personnalisées
		foreach($this->stylesXML['font-face-decls'] as $style) $fontFaceDecls .= $style;
		$fontFaceDecls .= '</office:font-face-decls>';
		$retour .= $fontFaceDecls;
		// Génération des styles office
		$officeStyles = '<office:styles><style:default-style style:family="graphic"><style:graphic-properties draw:shadow-offset-x="0.3cm" draw:shadow-offset-y="0.3cm" draw:start-line-spacing-horizontal="0.283cm" draw:start-line-spacing-vertical="0.283cm" draw:end-line-spacing-horizontal="0.283cm" draw:end-line-spacing-vertical="0.283cm" style:flow-with-text="false"/><style:paragraph-properties style:text-autospace="ideograph-alpha" style:line-break="strict" style:writing-mode="lr-tb" style:font-independent-line-spacing="false"><style:tab-stops/></style:paragraph-properties><style:text-properties style:use-window-font-color="true" fo:font-size="12pt" fo:language="fr" fo:country="FR" style:letter-kerning="true" style:font-size-asian="12pt" style:language-asian="zxx" style:country-asian="none" style:font-size-complex="12pt" style:language-complex="zxx" style:country-complex="none"/></style:default-style><style:default-style style:family="paragraph"><style:paragraph-properties fo:hyphenation-ladder-count="no-limit" style:text-autospace="ideograph-alpha" style:punctuation-wrap="hanging" style:line-break="strict" style:tab-stop-distance="1.251cm" style:writing-mode="page"/><style:text-properties style:use-window-font-color="true" style:font-name="Times New Roman" fo:font-size="12pt" fo:language="fr" fo:country="FR" style:letter-kerning="true" style:font-name-asian="Arial Unicode MS" style:font-size-asian="12pt" style:language-asian="zxx" style:country-asian="none" style:font-name-complex="Tahoma" style:font-size-complex="12pt" style:language-complex="zxx" style:country-complex="none" fo:hyphenate="false" fo:hyphenation-remain-char-count="2" fo:hyphenation-push-char-count="2"/></style:default-style><style:default-style style:family="table"><style:table-properties table:border-model="collapsing"/></style:default-style><style:default-style style:family="table-row"><style:table-row-properties fo:keep-together="auto"/></style:default-style><style:style style:name="Standard" style:family="paragraph" style:class="text"/><style:style style:name="Heading" style:family="paragraph" style:parent-style-name="Standard" style:next-style-name="Text_20_body" style:class="text"><style:paragraph-properties fo:margin-top="0.423cm" fo:margin-bottom="0.212cm" fo:keep-with-next="always"/><style:text-properties style:font-name="Arial" fo:font-size="14pt" style:font-name-asian="Arial Unicode MS" style:font-size-asian="14pt" style:font-name-complex="Tahoma" style:font-size-complex="14pt"/></style:style><style:style style:name="Text_20_body" style:display-name="Text body" style:family="paragraph" style:parent-style-name="Standard" style:class="text"><style:paragraph-properties fo:margin-top="0cm" fo:margin-bottom="0.212cm"/></style:style><style:style style:name="List" style:family="paragraph" style:parent-style-name="Text_20_body" style:class="list"><style:text-properties style:font-name-complex="Tahoma1"/></style:style><style:style style:name="Caption" style:family="paragraph" style:parent-style-name="Standard" style:class="extra"><style:paragraph-properties fo:margin-top="0.212cm" fo:margin-bottom="0.212cm" text:number-lines="false" text:line-number="0"/><style:text-properties fo:font-size="12pt" fo:font-style="italic" style:font-size-asian="12pt" style:font-style-asian="italic" style:font-name-complex="Tahoma1" style:font-size-complex="12pt" style:font-style-complex="italic"/></style:style><style:style style:name="Index" style:family="paragraph" style:parent-style-name="Standard" style:class="index"><style:paragraph-properties text:number-lines="false" text:line-number="0"/><style:text-properties style:font-name-complex="Tahoma1"/></style:style><text:outline-style style:name="Outline"><text:outline-level-style text:level="1" style:num-format=""><style:list-level-properties text:list-level-position-and-space-mode="label-alignment"><style:list-level-label-alignment text:label-followed-by="listtab" text:list-tab-stop-position="0.762cm" fo:text-indent="-0.762cm" fo:margin-left="0.762cm"/></style:list-level-properties></text:outline-level-style><text:outline-level-style text:level="2" style:num-format=""><style:list-level-properties text:list-level-position-and-space-mode="label-alignment"><style:list-level-label-alignment text:label-followed-by="listtab" text:list-tab-stop-position="1.016cm" fo:text-indent="-1.016cm" fo:margin-left="1.016cm"/></style:list-level-properties></text:outline-level-style><text:outline-level-style text:level="3" style:num-format=""><style:list-level-properties text:list-level-position-and-space-mode="label-alignment"><style:list-level-label-alignment text:label-followed-by="listtab" text:list-tab-stop-position="1.27cm" fo:text-indent="-1.27cm" fo:margin-left="1.27cm"/></style:list-level-properties></text:outline-level-style><text:outline-level-style text:level="4" style:num-format=""><style:list-level-properties text:list-level-position-and-space-mode="label-alignment"><style:list-level-label-alignment text:label-followed-by="listtab" text:list-tab-stop-position="1.524cm" fo:text-indent="-1.524cm" fo:margin-left="1.524cm"/></style:list-level-properties></text:outline-level-style><text:outline-level-style text:level="5" style:num-format=""><style:list-level-properties text:list-level-position-and-space-mode="label-alignment"><style:list-level-label-alignment text:label-followed-by="listtab" text:list-tab-stop-position="1.778cm" fo:text-indent="-1.778cm" fo:margin-left="1.778cm"/></style:list-level-properties></text:outline-level-style><text:outline-level-style text:level="6" style:num-format=""><style:list-level-properties text:list-level-position-and-space-mode="label-alignment"><style:list-level-label-alignment text:label-followed-by="listtab" text:list-tab-stop-position="2.032cm" fo:text-indent="-2.032cm" fo:margin-left="2.032cm"/></style:list-level-properties></text:outline-level-style><text:outline-level-style text:level="7" style:num-format=""><style:list-level-properties text:list-level-position-and-space-mode="label-alignment"><style:list-level-label-alignment text:label-followed-by="listtab" text:list-tab-stop-position="2.286cm" fo:text-indent="-2.286cm" fo:margin-left="2.286cm"/></style:list-level-properties></text:outline-level-style><text:outline-level-style text:level="8" style:num-format=""><style:list-level-properties text:list-level-position-and-space-mode="label-alignment"><style:list-level-label-alignment text:label-followed-by="listtab" text:list-tab-stop-position="2.54cm" fo:text-indent="-2.54cm" fo:margin-left="2.54cm"/></style:list-level-properties></text:outline-level-style><text:outline-level-style text:level="9" style:num-format=""><style:list-level-properties text:list-level-position-and-space-mode="label-alignment"><style:list-level-label-alignment text:label-followed-by="listtab" text:list-tab-stop-position="2.794cm" fo:text-indent="-2.794cm" fo:margin-left="2.794cm"/></style:list-level-properties></text:outline-level-style><text:outline-level-style text:level="10" style:num-format=""><style:list-level-properties text:list-level-position-and-space-mode="label-alignment"><style:list-level-label-alignment text:label-followed-by="listtab" text:list-tab-stop-position="3.048cm" fo:text-indent="-3.048cm" fo:margin-left="3.048cm"/></style:list-level-properties></text:outline-level-style></text:outline-style><text:notes-configuration text:note-class="footnote" style:num-format="1" text:start-value="0" text:footnotes-position="page" text:start-numbering-at="document"/><text:notes-configuration text:note-class="endnote" style:num-format="i" text:start-value="0"/><text:linenumbering-configuration text:number-lines="false" text:offset="0.499cm" style:num-format="1" text:number-position="left" text:increment="5"/>';
		// Ajout des styles personnalisés
		foreach($this->stylesXML['styles'] as $style) $officeStyles .= $style;
		$officeStyles .= '</office:styles>';
		$retour .= $officeStyles;
		// Génération des styles automatiques
		$automaticStyles = '<office:automatic-styles>';
		// Ajout du page layout
		$automaticStyles .= $this->getPageLayout();
		// Ajout des styles personnalisés
		foreach($this->stylesXML['automatic-styles'] as $style) $automaticStyles .= $style;
		$automaticStyles .= '</office:automatic-styles>';
		$retour .= $automaticStyles;
		// Génération des styles master
		$masterStyles = '<office:master-styles>';
		$masterStyles .= $this->getMasterStyle();
		// Ajout des styles personnalisés
		foreach($this->stylesXML['master-styles'] as $style) $masterStyles .= $style;
		$masterStyles .= '</office:master-styles>';
		$retour .= $masterStyles;
		// Fin
		$retour .= '</office:document-styles>';
		return $retour;
	}

	/****
	* getContentXml
	* Retourne le contenu du fichier content.xml
	* @return String : contenu du fichier styles.xml
	*****/	
	public function getContentXml() {
		// Header du document
		$retour = '<?xml version="1.0" encoding="UTF-8"?>
<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0" xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0" xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0" xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0" xmlns:math="http://www.w3.org/1998/Math/MathML" xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0" xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0" xmlns:ooo="http://openoffice.org/2004/office" xmlns:ooow="http://openoffice.org/2004/writer" xmlns:oooc="http://openoffice.org/2004/calc" xmlns:dom="http://www.w3.org/2001/xml-events" xmlns:xforms="http://www.w3.org/2002/xforms" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:rpt="http://openoffice.org/2005/report" xmlns:of="urn:oasis:names:tc:opendocument:xmlns:of:1.2" xmlns:rdfa="http://docs.oasis-open.org/opendocument/meta/rdfa#" office:version="1.2"><office:scripts/>';
		// Génération des polices du document
		$fontFaceDecls = '<office:font-face-decls><style:font-face style:name="Tahoma1" svg:font-family="Tahoma"/><style:font-face style:name="Times New Roman" svg:font-family="&apos;Times New Roman&apos;" style:font-family-generic="roman" style:font-pitch="variable"/><style:font-face style:name="Arial" svg:font-family="Arial" style:font-family-generic="swiss" style:font-pitch="variable"/><style:font-face style:name="Arial Unicode MS" svg:font-family="&apos;Arial Unicode MS&apos;" style:font-family-generic="system" style:font-pitch="variable"/><style:font-face style:name="Tahoma" svg:font-family="Tahoma" style:font-family-generic="system" style:font-pitch="variable"/>';
		// Ajout des polices personnalisées
		foreach($this->contentXML['font-face-decls'] as $style) $fontFaceDecls .= $style;
		$fontFaceDecls .= '</office:font-face-decls>';
		$retour .= $fontFaceDecls;
		// Génération des styles automatiques
		if(count($this->contentXML['automatic-styles']) == 0) {
			$automaticStyles = '<office:automatic-styles/>';
		}
		else {
			$automaticStyles = '<office:automatic-styles>';
			// Ajout des styles personnalisés
			foreach($this->contentXML['automatic-styles'] as $style) $automaticStyles .= $style;
			$automaticStyles .= '</office:automatic-styles>';
		}
		$retour .= $automaticStyles;
		// Génération du contenu
		$content = '<office:body><office:text><text:sequence-decls><text:sequence-decl text:display-outline-level="0" text:name="Illustration"/><text:sequence-decl text:display-outline-level="0" text:name="Table"/><text:sequence-decl text:display-outline-level="0" text:name="Text"/><text:sequence-decl text:display-outline-level="0" text:name="Drawing"/></text:sequence-decls><text:p text:style-name="Standard"/>'.$this->contentXML['content'].'</office:text></office:body></office:document-content>';
		$retour .= $content;
		return $retour;
	}
	
	/****
	* createFile
	* Créé physiquement le fichier odt à l'état de nouveau fichier (non zippé)
	*****/	
	public function createFile() {
		// Définition du chemin de travail
		$this->workDir = $this->tmpDir.md5($this->filename);		
		// Etape 1 : construction de l'arborescence
		exec('mkdir '.$this->workDir);
		foreach($this->tree as $dir) exec('mkdir '.$this->workDir.$dir);
		foreach($this->files as $file) exec('touch '.$this->workDir.$file);	
		// Etape 2 : construction des fichiers
		file_put_contents($this->workDir.'/meta.xml',$this->getDefaultMeta());
		file_put_contents($this->workDir.'/mimetype.xml',$this->getDefaultMimeType());
		file_put_contents($this->workDir.'/settings.xml',$this->getDefaultSettings());
		file_put_contents($this->workDir.'/styles.xml',$this->getStylesXml());
		file_put_contents($this->workDir.'/content.xml',$this->getContentXml());
		file_put_contents($this->workDir.'/META-INF/manifest.xml',$this->getDefaultManifest());	
		// Copie des images
		foreach($this->images as $name => $infos) exec('cp '.$infos['path'].' '.$this->workDir.'/Pictures/'.$name);
		// Nous avons maintenant un fichier OpenDocument prêt
	}
	
	/****
	* save
	* Génère le fichier odt
	*****/
	public function save() {
		// Création de l'arborescence temporaire
		$this->createFile();
		// Zip + suppression des fichiers temporaires
		exec('cd '.$this->workDir.';zip -rn : '.$this->filename.' *');
		exec('rm -Rf '.$this->workDir);
	}
	
	/****
	* getDefaultMeta
	* Retourne le contenu par défaut du fichier meta.xml
	* @return String
	*****/	
	public function getDefaultMeta()
	{
		return '<?xml version="1.0" encoding="UTF-8"?>
<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" xmlns:ooo="http://openoffice.org/2004/office" office:version="1.2"><office:meta><meta:initial-creator>'.$this->author.'</meta:initial-creator><meta:creation-date>'.date('Y-m-d').'T'.date('H:i:s').'.'.date('W').'</meta:creation-date><meta:document-statistic meta:table-count="0" meta:image-count="0" meta:object-count="0" meta:page-count="1" meta:paragraph-count="0" meta:word-count="0" meta:character-count="0"/><meta:generator>OpenOffice.org/3.0$Win32 OpenOffice.org_project/300m9$Build-9358</meta:generator></office:meta></office:document-meta>';
	}
	
	/****
	* getDefaultMeta
	* Retourne le contenu par défaut du fichier mimetype.xml
	* @return String
	*****/	
	public function getDefaultMimeType()
	{
		return 'application/vnd.oasis.opendocument.text';
	}

	/****
	* getDefaultMeta
	* Retourne le contenu par défaut du fichier settings.xml
	* @return String
	*****/		
	public function getDefaultSettings()
	{
		return '<?xml version="1.0" encoding="UTF-8"?>
<office:document-settings xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:config="urn:oasis:names:tc:opendocument:xmlns:config:1.0" xmlns:ooo="http://openoffice.org/2004/office" office:version="1.2"><office:settings><config:config-item-set config:name="ooo:view-settings"><config:config-item config:name="ViewAreaTop" config:type="int">0</config:config-item><config:config-item config:name="ViewAreaLeft" config:type="int">0</config:config-item><config:config-item config:name="ViewAreaWidth" config:type="int">32625</config:config-item><config:config-item config:name="ViewAreaHeight" config:type="int">21512</config:config-item><config:config-item config:name="ShowRedlineChanges" config:type="boolean">true</config:config-item><config:config-item config:name="InBrowseMode" config:type="boolean">false</config:config-item><config:config-item-map-indexed config:name="Views"><config:config-item-map-entry><config:config-item config:name="ViewId" config:type="string">view2</config:config-item><config:config-item config:name="ViewLeft" config:type="int">7812</config:config-item><config:config-item config:name="ViewTop" config:type="int">3002</config:config-item><config:config-item config:name="VisibleLeft" config:type="int">0</config:config-item><config:config-item config:name="VisibleTop" config:type="int">0</config:config-item><config:config-item config:name="VisibleRight" config:type="int">32623</config:config-item><config:config-item config:name="VisibleBottom" config:type="int">21511</config:config-item><config:config-item config:name="ZoomType" config:type="short">0</config:config-item><config:config-item config:name="ViewLayoutColumns" config:type="short">0</config:config-item><config:config-item config:name="ViewLayoutBookMode" config:type="boolean">false</config:config-item><config:config-item config:name="ZoomFactor" config:type="short">100</config:config-item><config:config-item config:name="IsSelectedFrame" config:type="boolean">false</config:config-item></config:config-item-map-entry></config:config-item-map-indexed></config:config-item-set><config:config-item-set config:name="ooo:configuration-settings"><config:config-item config:name="AddParaTableSpacing" config:type="boolean">true</config:config-item><config:config-item config:name="PrintReversed" config:type="boolean">false</config:config-item><config:config-item config:name="OutlineLevelYieldsNumbering" config:type="boolean">false</config:config-item><config:config-item config:name="LinkUpdateMode" config:type="short">1</config:config-item><config:config-item config:name="PrintEmptyPages" config:type="boolean">true</config:config-item><config:config-item config:name="IgnoreFirstLineIndentInNumbering" config:type="boolean">false</config:config-item><config:config-item config:name="CharacterCompressionType" config:type="short">0</config:config-item><config:config-item config:name="PrintSingleJobs" config:type="boolean">false</config:config-item><config:config-item config:name="UpdateFromTemplate" config:type="boolean">true</config:config-item><config:config-item config:name="PrintPaperFromSetup" config:type="boolean">false</config:config-item><config:config-item config:name="AddFrameOffsets" config:type="boolean">false</config:config-item><config:config-item config:name="PrintLeftPages" config:type="boolean">true</config:config-item><config:config-item config:name="RedlineProtectionKey" config:type="base64Binary"/><config:config-item config:name="PrintTables" config:type="boolean">true</config:config-item><config:config-item config:name="ChartAutoUpdate" config:type="boolean">true</config:config-item><config:config-item config:name="PrintControls" config:type="boolean">true</config:config-item><config:config-item config:name="PrinterSetup" config:type="base64Binary"/><config:config-item config:name="IgnoreTabsAndBlanksForLineCalculation" config:type="boolean">false</config:config-item><config:config-item config:name="PrintAnnotationMode" config:type="short">0</config:config-item><config:config-item config:name="LoadReadonly" config:type="boolean">false</config:config-item><config:config-item config:name="AddParaSpacingToTableCells" config:type="boolean">true</config:config-item><config:config-item config:name="AddExternalLeading" config:type="boolean">true</config:config-item><config:config-item config:name="ApplyUserData" config:type="boolean">true</config:config-item><config:config-item config:name="FieldAutoUpdate" config:type="boolean">true</config:config-item><config:config-item config:name="SaveVersionOnClose" config:type="boolean">false</config:config-item><config:config-item config:name="SaveGlobalDocumentLinks" config:type="boolean">false</config:config-item><config:config-item config:name="IsKernAsianPunctuation" config:type="boolean">false</config:config-item><config:config-item config:name="AlignTabStopPosition" config:type="boolean">true</config:config-item><config:config-item config:name="ClipAsCharacterAnchoredWriterFlyFrames" config:type="boolean">false</config:config-item><config:config-item config:name="CurrentDatabaseDataSource" config:type="string"/><config:config-item config:name="TabAtLeftIndentForParagraphsInList" config:type="boolean">false</config:config-item><config:config-item config:name="DoNotCaptureDrawObjsOnPage" config:type="boolean">false</config:config-item><config:config-item config:name="TableRowKeep" config:type="boolean">false</config:config-item><config:config-item config:name="PrinterName" config:type="string"/><config:config-item config:name="PrintFaxName" config:type="string"/><config:config-item config:name="ConsiderTextWrapOnObjPos" config:type="boolean">false</config:config-item><config:config-item config:name="UseOldPrinterMetrics" config:type="boolean">false</config:config-item><config:config-item config:name="PrintRightPages" config:type="boolean">true</config:config-item><config:config-item config:name="IsLabelDocument" config:type="boolean">false</config:config-item><config:config-item config:name="UseFormerLineSpacing" config:type="boolean">false</config:config-item><config:config-item config:name="AddParaTableSpacingAtStart" config:type="boolean">true</config:config-item><config:config-item config:name="UseFormerTextWrapping" config:type="boolean">false</config:config-item><config:config-item config:name="DoNotResetParaAttrsForNumFont" config:type="boolean">false</config:config-item><config:config-item config:name="PrintProspect" config:type="boolean">false</config:config-item><config:config-item config:name="PrintGraphics" config:type="boolean">true</config:config-item><config:config-item config:name="AllowPrintJobCancel" config:type="boolean">true</config:config-item><config:config-item config:name="CurrentDatabaseCommandType" config:type="int">0</config:config-item><config:config-item config:name="DoNotJustifyLinesWithManualBreak" config:type="boolean">false</config:config-item><config:config-item config:name="TabsRelativeToIndent" config:type="boolean">true</config:config-item><config:config-item config:name="UseFormerObjectPositioning" config:type="boolean">false</config:config-item><config:config-item config:name="PrinterIndependentLayout" config:type="string">high-resolution</config:config-item><config:config-item config:name="UseOldNumbering" config:type="boolean">false</config:config-item><config:config-item config:name="PrintPageBackground" config:type="boolean">true</config:config-item><config:config-item config:name="CurrentDatabaseCommand" config:type="string"/><config:config-item config:name="PrintDrawings" config:type="boolean">true</config:config-item><config:config-item config:name="PrintBlackFonts" config:type="boolean">false</config:config-item><config:config-item config:name="UnxForceZeroExtLeading" config:type="boolean">false</config:config-item></config:config-item-set></office:settings></office:document-settings>';
	}
	
	/****
	* getMasterStyle
	* Retourne le style de la master page
	* @return String
	*****/		
	public function getMasterStyle() {
		if(count($this->masterStyles) > 0) {
			$retour = '<style:master-page style:name="Standard" style:page-layout-name="Mpm1">';
			foreach($this->masterStyles as $masterStyle) {
				$retour .= $masterStyle;
			}
			$retour .= '</style:master-page>';
			return $retour;
		}
		else {
			return '<style:master-page style:name="Standard" style:page-layout-name="Mpm1"/>';
		}		
	}

	/****
	* getDefaultMeta
	* Retourne le contenu par défaut du fichier manifest.xml
	* @return String
	*****/		
	public function getDefaultManifest()
	{
		return'<?xml version="1.0" encoding="UTF-8"?>
<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">
 <manifest:file-entry manifest:media-type="application/vnd.oasis.opendocument.text" manifest:version="1.2" manifest:full-path="/"/>
 <manifest:file-entry manifest:media-type="" manifest:full-path="Configurations2/statusbar/"/>
 <manifest:file-entry manifest:media-type="" manifest:full-path="Configurations2/accelerator/current.xml"/>
 <manifest:file-entry manifest:media-type="" manifest:full-path="Configurations2/accelerator/"/>
 <manifest:file-entry manifest:media-type="" manifest:full-path="Configurations2/floater/"/>
 <manifest:file-entry manifest:media-type="" manifest:full-path="Configurations2/popupmenu/"/>
 <manifest:file-entry manifest:media-type="" manifest:full-path="Configurations2/progressbar/"/>
 <manifest:file-entry manifest:media-type="" manifest:full-path="Configurations2/menubar/"/>
 <manifest:file-entry manifest:media-type="" manifest:full-path="Configurations2/toolbar/"/>
 <manifest:file-entry manifest:media-type="" manifest:full-path="Configurations2/images/Bitmaps/"/>
 <manifest:file-entry manifest:media-type="" manifest:full-path="Configurations2/images/"/>
 <manifest:file-entry manifest:media-type="application/vnd.sun.xml.ui.configuration" manifest:full-path="Configurations2/"/>
 <manifest:file-entry manifest:media-type="text/xml" manifest:full-path="content.xml"/>
 <manifest:file-entry manifest:media-type="text/xml" manifest:full-path="styles.xml"/>
 <manifest:file-entry manifest:media-type="text/xml" manifest:full-path="meta.xml"/>
 <manifest:file-entry manifest:media-type="" manifest:full-path="Thumbnails/thumbnail.png"/>
 <manifest:file-entry manifest:media-type="" manifest:full-path="Thumbnails/"/>
 <manifest:file-entry manifest:media-type="text/xml" manifest:full-path="settings.xml"/>
</manifest:manifest>';
	}
	
	/****
	* addContent
	* Ajoute du contenu au document
	* @param String : contenu XML
	*****/
	public function addContent($content) {
		$this->contentXML['content'] .= $content;
	}
	
	/****
	* addAutomaticStyleToContent
	* Permet de déclarer un nouveau style automatique dans le fichier content.xml
	* @param String : nom du style
	* @param String : famille du style (paragraph, graphic, ...)
	* @param String : style parent (Standard, Graphics, ...)
	* @param String : style XML
	*****/
	public function addAutomaticStyleToContent($styleName,$family='paragraph',$parent='Standard',$style) {
		$style = '<style:style style:name="'.$styleName.'" style:family="'.$family.'" style:parent-style-name="'.$parent.'">'.$style.'</style:style>';
		$this->contentXML['automatic-styles'][$styleName] = $style;
	}
	
	/****
	* addAutomaticStyleToStyles
	* Permet de déclarer un nouveau style automatique dans le fichier styles.xml
	* @param String : nom du style
	* @param String : famille du style (paragraph, graphic, ...)
	* @param String : style parent (Standard, Graphics, ...)
	* @param String : style XML
	*****/
	public function addAutomaticStyleToStyles($styleName,$family='paragraph',$parent='Standard',$style) {
		$style = '<style:style style:name="'.$styleName.'" style:family="'.$family.'" style:parent-style-name="'.$parent.'">'.$style.'</style:style>';
		$this->stylesXML['automatic-styles'][$styleName] = $style;
	}
	
	/****
	* addOfficeStyleToStyles
	* Permet de déclarer un nouveau style office dans le fichier styles.xml
	* @param String : nom du style
	* @param String : famille du style (paragraph, graphic, ...)
	* @param String : style parent (Standard, Graphics, ...)
	* @param String : classe de style
	* @param String : style XML
	*****/
	public function addOfficeStyleToStyles($styleName,$family='paragraph',$parent='Standard',$class='extra',$style) {
		$style = '<style:style style:name="'.$styleName.'" style:family="'.$family.'" style:parent-style-name="'.$parent.'" style:class="'.$class.'">'.$style.'</style:style>';
		$this->stylesXML['styles'][$styleName] = $style;
	}
	
	/****
	* makeAsParagraph
	* Transforme la chaine passée en paramètre en paragraphe xml
	* @param String : texte du paragraphe
	* @param String : style du paragraphe
	* @return String : paragraphe xml
	*****/	
	public function makeAsParagraph($text, $styleName='Standard') {
		$xmlString = '<text:p text:style-name="'.$styleName.'">'.$text.'</text:p>';
		return $xmlString;
	}
	
	/****
	* makeAsTitle
	* Transforme la chaine passée en paramètre en titre xml
	* @param String : texte du titre
	* @param String : style du titre
	* @param String : niveau du titre
	* @return String : titre xml
	*****/	
	public function makeAsTitle($text,$styleName='Heading',$outlineLevel='1') {
		$xmlString = '<text:h text:style-name="'.$styleName.'" text:outline-level="'.$outlineLevel.'">'.$text.'</text:h>';
		return $xmlString;
	}
	
	/****
	* pageBreak
	* Force le changement de page
	*****/		
	public function pageBreak() {
		$this->addAutomaticStyleToContent('PageBreak','paragraph','Standard','<style:paragraph-properties fo:break-before="page"/>');
		$pageBreak = $this->makeAsParagraph('', 'PageBreak');
		$this->contentXML['content'] .= $pageBreak;
	}
	
	/****
	* createTable
	* Retourne un tableau
	* @param String : name
	* @param Array : content table
	* @return String : tableau XML
	*****/
	public function createTable($name, $data) {
		$table .= '<table:table table:name="'.$name.'">';
		$table .= '<table:table-column table:number-columns-repeated="'.count($data[0]).'"/>';
		foreach($data as $row) {
			$table .= '<table:table-row>';
			foreach($row as $cell) {
				$table .= '<table:table-cell office:value-type="string">';
				$table .= $cell;
				$table .= '</table:table-cell>';
			}
			$table .= '</table:table-row>';
		}
		$table .= '</table:table>';
		return $table;
	}
	
	/****
	* drawLine
	* Retourne le code xml d'une ligne
	* @param Float : coordonnée x1
	* @param Float : coordonnée y1
	* @param Float : coordonnée x2
	* @param Float : coordonnée y2
	* @param String : nom du style de la ligne
	* @return String : ligne XML
	*****/		
	public function drawLine($x1,$y1,$x2,$y2,$styleName) {	
		$lineXML = '<draw:line text:anchor-type="paragraph" draw:z-index="0" draw:style-name="'.$styleName.'" draw:text-style-name="MP1" svg:x1="'.$x1.'cm" svg:y1="'.$y1.'cm" svg:x2="'.$x2.'cm" svg:y2="'.$y2.'cm"><text:p/></draw:line>';
		return $lineXML;
	}
	
	/****
	* newImage
	* Ajoute une image au document et retourne le code xml concernant l'image.
	* @param string chemin de l'image
	* @return string chaine xml de l'image à placer dans content.xml
	*****/	
	public function newImage($imagePath) {
		if(file_exists($imagePath)) {
			$this->getMimeType($imagePath);
			$ext = $this->imageMimeTypes[$this->getMimeType($imagePath)];
			if($ext != '') {
				$newName = strtoupper(md5($imagePath)).'.'.$ext;
				$imageXML = '<draw:image xlink:href="Pictures/'.$newName.'" xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad"/>';
				$this->images[$newName] = Array('path' => $imagePath, 'xml' => $imageXML);			
				return $imageXML;
			}
		}
	}
	
	/****
	* createFrame
	* génère un objet frame
	* @param String : Nom du frame
	* @param String : Nom du style du frame
	* @param String : Contenu du frame
	* @param String : Nom du style du paragraph contenant le frame
	* @param Float : largeur du frame
	* @param Float : hauteur du frame
	* @param Float : position abscisses du frame
	* @param Float : position ordonnées du frame
	* @param Int : profondeur du Frame
	* @param Float : largeur minimale du frame
	* @param Float : hauteur minimale du frame
	* @return String : frame XML
	*****/		
	public function createFrame($FrameName,$StyleName,$content,$paragraphStyleName='Standard',$width='10',$height='2',$x='',$y='',$zindex='0',$minWidth='',$minHeight='',$anchor='paragraph') {		
		$frame = '<draw:frame draw:style-name="'.$StyleName.'" draw:name="'.$FrameName.'" text:anchor-type="'.$anchor.'" '.($x != '' ? 'svg:x="'.$x.'cm" ' : '').''.($y != '' ? 'svg:y="'.$y.'cm" ' : '').'svg:width="'.$width.'cm" svg:height="'.$height.'cm"'.($minWidth != '' ? ' fo:min-width="'.$minWidth.'cm"' : '').''.($minHeight != '' ? ' fo:min-height="'.$minHeight.'cm"' : '').' draw:z-index="'.$zindex.'">'.$content.'</draw:frame>';	
		return $frame;
	}
	
	/****
	* createTextBox
	* Retourne un text box
	* @param String : contenu du textbox
	* @param Float : largeur minimum
	* @param Float : hauteur minimum
	* @return String : textbox XML
     *
     * BBX Evolution Aircell
     *
	*****/	
	public function createTextBox($content,$minWidth='',$minHeigt='') {
		$textBox = '<draw:text-box'.(($minWidth != '') ? ' fo:min-width="'.$minWidth.'cm"' : '').''.(($minHeigt != '') ? ' fo:min-height="'.$minHeigt.'cm"' : '').'>'.$content.'</draw:text-box>';
		return $textBox;
	}

	// ***************************************** /
	//		ASTELLIA METHODS
	// ***************************************** /
	
	/****
	* astelliaHeader
	* génère le header Astellia
	* @param String : titre du document
	* @param String : chemin de l'image du logo Astellia
	* @param String : chemin du logo du client
	*****/
	public function astelliaHeader($titre,$astelliaLogo,$clientLogo) {	
		/* 
		* 1) 
		* On trace les lignes
		*/
		// Style de la première ligne
		$style = '<style:graphic-properties draw:textarea-horizontal-align="center" draw:textarea-vertical-align="middle" style:horizontal-rel="paragraph" style:vertical-rel="paragraph"/>';
		$this->addAutomaticStyleToStyles('astelliaHeaderLine1','graphic','Standard',$style);
		// Génération de la première ligne
		$content = $this->drawLine(0.1,0.6,22.3,0.6,'astelliaHeaderLine1','Header');
		if($this->orientation == 'portrait')
			$content = $this->drawLine(0.1,0.6,14.8,0.6,'astelliaHeaderLine1','Header');

		/*
         *
         * OJT : inversion des logo Client et Astellia dans les pdf
		* 2) 
		* Logo Client
		*/
		// Ajout de l'image
		$image = $this->newImage($clientLogo);
		// Style du Frame
		$style = '<style:graphic-properties style:vertical-pos="from-top" style:vertical-rel="paragraph" style:horizontal-pos="from-left" style:horizontal-rel="paragraph" style:mirror="none"/>';
		$this->addAutomaticStyleToStyles('styleFrameLogoAstellia','graphic','Graphics',$style);
		// Calcul de la taille de l'image
		$imageSize = getimagesize($clientLogo);
		// Largeur
		$largeur = ($this->orientation == 'landscape') ? 4.8 : 4;
		$xPos = ($this->orientation == 'landscape') ? '22.8' : '15.2';
		// Hauteur
		$hauteur = ($imageSize[1] * $largeur) / $imageSize[0];
                // 30/08/2011 BBX
                // BZ 23540 : Ajout test hauteur
                if($hauteur > self::CLIENT_LOGO_MAX_HEIGHT) {
                    $largeur = (self::CLIENT_LOGO_MAX_HEIGHT * $largeur) / $hauteur;
                    $hauteur = self::CLIENT_LOGO_MAX_HEIGHT;
                }
		// Génération du frame
		$content .= $this->createFrame('FrameLogoAstellia','styleFrameLogoAstellia',$image,'Header',$largeur,$hauteur,$xPos,'0','1');
		
		/* 
		* 3) 
		* Logo Astellia
		*/
		// Ajout de l'image
		$image = $this->newImage($astelliaLogo);
		// Style du Frame
		$style = '<style:graphic-properties style:vertical-pos="from-top" style:vertical-rel="page" style:horizontal-pos="from-left" style:horizontal-rel="page" style:mirror="none"/>';
		$this->addAutomaticStyleToStyles('styleFrameLogoClient','graphic','Graphics',$style);
		// Calcul de la taille de l'image
		$imageSize = getimagesize($astelliaLogo);
		// Largeur
		$largeur = 1.5;
		// Hauteur
		$hauteur = ($imageSize[1] * $largeur) / $imageSize[0];
		// Génération du frame
		$content .= $this->createFrame('FrameLogoClient','styleFrameLogoClient',$image,'Header',$largeur,$hauteur,'2','1','1');	

		/* 
		* 4) 
		* Titre du header
		*/	
		// Style du titre
		$style = '<style:paragraph-properties fo:text-align="start" style:justify-single-word="false" style:text-autospace="none"/><style:text-properties style:font-name="Arial" fo:font-size="13pt" fo:font-weight="bold"/>';
		$this->addAutomaticStyleToStyles('TitrePage','paragraph','Standard',$style);
		// Création du textbox
		$title = $this->makeAsParagraph('<text:s/><text:s/><text:s/>'.$titre.'<text:s/><text:s/><text:s/>','TitrePage');
		$textBox = $this->createTextBox($title);
		// Style du Frame du Textbox
		$style = '<style:graphic-properties style:vertical-pos="from-top" style:vertical-rel="page" style:horizontal-pos="center" style:horizontal-rel="page" style:mirror="none"/>';
		$this->addAutomaticStyleToStyles('styleFrameTitrePage','graphic','Graphics',$style);		
		// Frame du textbox
		$content .= $this->createFrame('FrameTitrePage','styleFrameTitrePage',$textBox,'Header','0.2','0.2','0','1.3','1','0.2','0.2');	

		/* 
		* 5) 
		* Date du header
		*/
		$dateString = date('F d Y, h:i a');
		// Style de la date
		$style = '<style:paragraph-properties fo:text-align="start" style:justify-single-word="false" style:text-autospace="none"/><style:text-properties style:font-name="Arial" fo:font-size="8pt" fo:font-style="italic"/>';
		$this->addAutomaticStyleToStyles('DateHeader','paragraph','Standard',$style);
		// Création du textbox
		$date = $this->makeAsParagraph($dateString,'DateHeader');
		$textBox = $this->createTextBox($date);		
		// Style du Frame du Textbox
		$style = '<style:graphic-properties style:vertical-pos="from-top" style:vertical-rel="page" style:horizontal-pos="from-left" style:horizontal-rel="page" style:mirror="none"/>';
		$this->addAutomaticStyleToStyles('styleFrameDateHeader','graphic','Graphics',$style);		
		// Frame du textbox
		$content .= $this->createFrame('FrameDateHeader','styleFrameDateHeader',$textBox,'Header','0.2','0.5','6','1','1','0.2','0.2');	
				
		/* 
		* 6) 
		* Génération du Header
		*/
		$this->masterStyles[] = '<style:header>'.$this->makeAsParagraph($content).'</style:header>';
		$this->headerStyle = '<style:header-style><style:header-footer-properties fo:min-height="1.7cm" fo:margin-bottom="0cm"/></style:header-style>';
	}
	
	/****
	* astelliaFooter
	* Génère le footer Astellia
	*****/	
	public function astelliaFooter() {
		/*
		* 1)
		* Style du Footer
		*/
		$style = '
		<style:paragraph-properties fo:background-color="#e6e6e6" text:number-lines="false" text:line-number="0">
			<style:tab-stops>
				<style:tab-stop style:position="8.498cm" style:type="center"/>
				<style:tab-stop style:position="16.999cm" style:type="right"/>
			</style:tab-stops>
			<style:background-image/>
		</style:paragraph-properties>
		<style:text-properties style:font-name="Arial2"/>';
		$this->addOfficeStyleToStyles('Footer','paragraph','Standard','extra',$style);	
		
		/*
		* 2)
		* Style des paragraphes du Footer
		*/
		$style = '<style:paragraph-properties fo:text-align="start" style:justify-single-word="false" style:text-autospace="none"/>
		<style:text-properties style:font-name="Arial" fo:font-size="8pt" fo:font-style="italic" style:font-name-asian="Arial1" style:font-size-asian="8pt" style:font-style-asian="italic" style:font-name-complex="Arial1" style:font-size-complex="8pt" style:font-style-complex="italic"/>';
		$this->addAutomaticStyleToStyles('FooterParapgraph','paragraph','Footer',$style);
		
		/*
		* 3)
		* T&A
		*/
		$content = 'Powered by Trending &amp; Aggregation';
		
		/*
		* 4)
		* Numéro des pages
		*/
		$xPos = ($this->orientation == 'landscape') ? '26' : '18';
		$xmlString = 'Page <text:page-number text:select-page="current">1</text:page-number>/<text:page-count>1</text:page-count>';
		$pageNumber = $this->makeAsParagraph($xmlString,'FooterParapgraph');
		$textBox = $this->createTextBox($pageNumber);
		// Style du Frame du Textbox
		$style = '<style:graphic-properties style:vertical-pos="from-top" style:vertical-rel="paragraph" style:horizontal-pos="from-left" style:horizontal-rel="paragraph" style:mirror="none"/>';
		$this->addAutomaticStyleToStyles('styleFramePageNumber','graphic','Graphics',$style);		
		// Frame du textbox	
		$content .= $this->createFrame('FramePageNumber','styleFramePageNumber',$textBox,'Footer','0.2','0.2',$xPos,'0','2','0.2','0.2');	
		
		/*
		* 5)
		* Génération du footer
		*/
		$this->masterStyles[] = '<style:footer>'.$this->makeAsParagraph($content,'FooterParapgraph').'</style:footer>';
		$this->footerStyle = '<style:footer-style><style:header-footer-properties fo:min-height="0cm" fo:margin-top="0cm"/></style:footer-style>';		
	}

	/****
	* dashComment
	* Génère l'affichage du commentaire de dashboard
	* @param String : image de la puce
	* @param String : commentaire
	*****/		
	public function dashComment($comment,$puce)
	{
		/*
		* 1)
		* Définition des styles de textes utilisables
		*/	
		// Style du Frame du Textbox
		$style = '<style:graphic-properties style:vertical-pos="from-top" style:vertical-rel="page" style:horizontal-pos="from-left" style:horizontal-rel="page" style:mirror="none"/>';
		$this->addAutomaticStyleToContent('styleFrameTitreGTM','graphic','Graphics',$style);
		// Style du Frame de la puce
		$style = '<style:graphic-properties style:vertical-pos="from-top" style:vertical-rel="page" style:horizontal-pos="from-left" style:horizontal-rel="page" style:mirror="none"/>';
		$this->addAutomaticStyleToContent('styleFrameImagePuce','graphic','Graphics',$style);			
		// Style du texte du commentaire
		$style = '<style:text-properties fo:font-size="60%" fo:font-weight="normal" style:font-size-asian="60%" style:font-weight-asian="normal" style:font-size-complex="60%" style:font-weight-complex="normal" style:font-name="Arial"/>';
		$this->addOfficeStyleToStyles('CommentDash','paragraph','Standard','text',$style);
		// Style du Frame du commentaire
		$style = '<style:graphic-properties style:vertical-pos="from-top" style:vertical-rel="page" style:horizontal-pos="from-left" style:horizontal-rel="page" style:mirror="none" style:border-line-width="0.10mm" fo:border="0.10mm solid #898989" fo:padding="0.2mm"/>';
		$this->addAutomaticStyleToContent('styleFrameCommentDash','graphic','Graphics',$style);
		
		/*
		* 2) Quelques variables
		*/
		$posLegendX = ($this->orientation == 'landscape') ? '3' : '3';
		$posLegendY = ($this->orientation == 'landscape') ? '3' : '3';
		
		/*
		* 3) 
		* Titre
		*/
		// Titre
		$titre = $this->makeAsTitle('Dashboard Comment',$styleLegend,'5');
		$textBox = $this->createTextBox($titre);		
		// Frame du textbox	
		$frameWidth = ($this->orientation == 'landscape') ? '23' : '17';
		$frame = $this->createFrame('FrameTitreGtm'.uniqid(),'styleFrameTitreGTM',$textBox,'Standard','0.2','0.2',($posLegendX+0.5),($posLegendY-0.4),'1',$frameWidth,'0.2','page');
		$content = $this->makeAsParagraph($frame);
		
		/*
		* 5)
		* Image de la puce
		*/
		// Image de la puce
		$image = $this->newImage($puce);	
		// Frame du textbox	
		$frame = $this->createFrame('FrameImagePuce'.uniqid(),'styleFrameImagePuce',$image,'Standard','0.4','0.4',$posLegendX,$posLegendY,'9','0.4','0.4','page');
		$content .= $this->makeAsParagraph($frame);
		
		/*
		* 6)
		* Traitement du com
		*/
		$interdits = array("&");
		$autorises = array("&amp;");
		$comment = utf8_encode(str_replace($interdits,$autorises,$comment));
		$comment = $this->makeAsParagraph($comment,'CommentDash');
		$textBox = $this->createTextBox($comment);		
		// Frame du textbox	
		$framePosX = ($this->orientation == 'landscape') ? $posLegendX+0.35 : $posLegendX-1.1;
		$framePosY = $posLegendY+1;
		$frame = $this->createFrame('FrameCommentGtm'.uniqid(),'styleFrameCommentDash',$textBox,'Standard','0.2','0.2',$framePosX,$framePosY,'1',$frameWidth,'0.2','page');
		$content .= $this->makeAsParagraph($frame);	
		
		/*
		* 7)
		* Ajout du contenu
		*/
		$this->addContent($content);
	}
	
	/****
	* astelliaGTM
	* Génère l'affichage d'un GTM
	* @param String : titre du GTM
	* @param String : image du GTM
	* @param String : image de la puce
	* @param float : position du graphe en abscisses
	* @param float : position du graphe en ordonnées
	* @param float : largeur du graphe
	* @param float : position de la légende en abscisses
	* @param float : position de la légende en ordonnées
	* @param  string : style du graphe
	* @param  string : style de la légende
     *
     * 26/04/2011 NSE évolution urgente aircel : modification de la valeur par défaut du style de la légende, ajout d'un nouveau style et traitement de la largeur du titre
     *
	*****/	
	public function astelliaGTM($titreGTM,$imagePath,$puce,$posGraphX,$posGraphY,$graphWidth,$posLegendX,$posLegendY,$styleGraph='styleFrameImageGTMcenter',$styleLegend='TitreGTMBig') {
	
		/*
		* 1)
		* Définition des styles de graphes utilisables
		*/
		$style = '<style:graphic-properties style:vertical-pos="from-top" style:vertical-rel="page" style:horizontal-pos="center" style:horizontal-rel="page" style:mirror="none" fo:border="0.5mm solid #898989"/>';
		$this->addAutomaticStyleToContent('styleFrameImageGTMcenter','graphic','Graphics',$style);
		$style = '<style:graphic-properties style:vertical-pos="from-top" style:vertical-rel="page" style:horizontal-pos="left" style:horizontal-rel="page" style:mirror="none" fo:border="0.5mm solid #898989"/>';
		$this->addAutomaticStyleToContent('styleFrameImageGTMleft','graphic','Graphics',$style);
		$style = '<style:graphic-properties style:vertical-pos="from-top" style:vertical-rel="page" style:horizontal-pos="right" style:horizontal-rel="page" style:mirror="none" fo:border="0.5mm solid #898989"/>';
		$this->addAutomaticStyleToContent('styleFrameImageGTMright','graphic','Graphics',$style);		
		$style = '<style:graphic-properties style:vertical-pos="from-top" style:vertical-rel="page" style:horizontal-pos="from-left" style:horizontal-rel="page" style:mirror="none" fo:border="0.5mm solid #898989"/>';
		$this->addAutomaticStyleToContent('styleFrameImageGTMfromLeft','graphic','Graphics',$style);

		/*
		* 2)
		* Définition des styles de textes utilisables
		*/		
        // 26/04/2011 NSE évolution urgente aircel : style plus gros pour le titre
		$style = '<style:text-properties fo:font-size="'.($this->orientation == 'landscape' ? '110' : '92').'%" fo:font-weight="bold" style:font-size-asian="'.($this->orientation == 'landscape' ? '110' : '92').'%" style:font-weight-asian="bold" style:font-size-complex="'.($this->orientation == 'landscape' ? '110' : '92').'%" style:font-weight-complex="bold"/>';
		$this->addOfficeStyleToStyles('TitreGTMBig','paragraph','Heading','text',$style);
		$style = '<style:text-properties fo:font-size="85%" fo:font-weight="bold" style:font-size-asian="85%" style:font-weight-asian="bold" style:font-size-complex="85%" style:font-weight-complex="bold"/>';
		$this->addOfficeStyleToStyles('TitreGTM','paragraph','Heading','text',$style);
		$style = '<style:text-properties fo:font-size="50%" fo:font-weight="bold" style:font-size-asian="85%" style:font-weight-asian="bold" style:font-size-complex="85%" style:font-weight-complex="bold"/>';
		$this->addOfficeStyleToStyles('TitreGTMsmall','paragraph','Heading','text',$style);
		// Style du Frame du Textbox
		$style = '<style:graphic-properties style:vertical-pos="from-top" style:vertical-rel="page" style:horizontal-pos="from-left" style:horizontal-rel="page" style:mirror="none" style:wrap="parallel" style:number-wrapped-paragraphs="no-limit" style:wrap-contour="false" style:background-transparency="100%" />';
		$this->addAutomaticStyleToContent('styleFrameTitreGTM','graphic','Graphics',$style);
		// Style du Frame de la puce
		$style = '<style:graphic-properties style:vertical-pos="from-top" style:vertical-rel="page" style:horizontal-pos="from-left" style:horizontal-rel="page" style:mirror="none"/>';
		$this->addAutomaticStyleToContent('styleFrameImagePuce','graphic','Graphics',$style);	
		// Style du texte du commentaire
		$style = '<style:text-properties fo:font-size="60%" fo:font-weight="normal" style:font-size-asian="60%" style:font-weight-asian="normal" style:font-size-complex="60%" style:font-weight-complex="normal" style:font-name="Arial"/>';
		$this->addOfficeStyleToStyles('CommentGTM','paragraph','Standard','text',$style);
		// Style du Frame du commentaire
		$style = '<style:graphic-properties style:vertical-pos="from-top" style:vertical-rel="page" style:horizontal-pos="from-left" style:horizontal-rel="page" style:mirror="none" style:border-line-width="0.10mm" fo:border="0.10mm solid #898989" fo:padding="0.2mm"/>';
		$this->addAutomaticStyleToContent('styleFrameCommentGTM','graphic','Graphics',$style);
		
		/*
		* 3) 
		* Titre
		*/
		// Titre
        // 26/04/2011 NSE évolution urgente aircel : on limite la largeur du titre
        if($styleLegend=='TitreGTMBig' && strlen($titreGTM)>=($this->orientation == 'landscape' ? self::MAX_TITLE_LENGTH_LANDSCAPE : self::MAX_TITLE_LENGTH_PORTRAIT))
            $titreGTM = substr($titreGTM,0,($this->orientation == 'landscape' ? self::MAX_TITLE_LENGTH_LANDSCAPE : self::MAX_TITLE_LENGTH_PORTRAIT)).'...';
		$titre = $this->makeAsTitle($titreGTM,$styleLegend,'5');
		$textBox = $this->createTextBox($titre);		
		// Frame du textbox	
		$frameWidth = ($this->orientation == 'landscape') ? '25' : '18';
		$frame = $this->createFrame('FrameTitreGtm'.uniqid(),'styleFrameTitreGTM',$textBox,'Standard',$frameWidth,'0.2',($posLegendX+0.5),($posLegendY-0.4),'1','','0.2','page');
		$content = $this->makeAsParagraph($frame);
		
		/*
		* 4)
		* Image de la puce
		*/
		// Image de la puce
		$image = $this->newImage($puce);	
		// Frame du textbox	
		$frame = $this->createFrame('FrameImagePuce'.uniqid(),'styleFrameImagePuce',$image,'Standard','0.4','0.4',$posLegendX,$posLegendY,'9','0.4','0.4','page');
		$content .= $this->makeAsParagraph($frame);
		
		/*
		* 5)
		* Image du GTM
		*/
		// Image du GTM
		$image = $this->newImage($imagePath);
		// Calcul de la taille de l'image
		$imageSize = getimagesize($imagePath);
		
		/**
		*	30/10/2009 BBX : modification du calcul de la proportion des images. BZ 12359
		**/
		// Clacul de la proportion de l'image
		$imgProportion = $imageSize[0]/$imageSize[1];
		
		// On se base sur la largeur
		if($imgProportion >= 1)
		{
			// Hauteur
			$graphHeight = ($imageSize[1] * $graphWidth) / $imageSize[0];
            /**
             *	22/12/2010 SCT : correction de la hauteur des images (si hauteur trop importante). BZ 18725
             **/
            // On vérifie qu'on ne dépasse pas les dimensions maximum dans le sens PAYSAGE (hauteur max dépassée)
            if($this->orientation == 'landscape' && $graphHeight > self::MAX_HEIGHT_LANDSCAPE)
            {
                $graphWidth = self::MAX_HEIGHT_LANDSCAPE * $graphWidth / $graphHeight;
                $graphHeight = self::MAX_HEIGHT_LANDSCAPE;
		}
            // ce cas ne doit normalement pas arriver car la largeur est plus importante que la hauteur
            elseif($this->orientation != 'landscape' && $graphHeight > self::MAX_HEIGHT_PORTRAIT)
            {
                $graphWidth = self::MAX_HEIGHT_PORTRAIT * $graphWidth / $graphHeight;
                $graphHeight = self::MAX_HEIGHT_PORTRAIT;
            }
            /**
             *	FIN 22/12/2010 SCT : BZ 18725.
             **/

		}
		// On se base sur la hauteur
		else
		{
			// Hauteur
			$graphHeight = ($this->orientation == 'landscape') ? self::MAX_HEIGHT_LANDSCAPE : self::MAX_HEIGHT_PORTRAIT;
			// Largeur
			$graphWidth = ($imageSize[0] * $graphHeight) / $imageSize[1];
		}
		
		/**
		*	FIN 30/10/2009 BBX.
		**/

		// Frame du textbox	
		$frame = $this->createFrame('FrameImageGtm'.uniqid(),$styleGraph,$image,'Standard',$graphWidth,$graphHeight,$posGraphX,$posGraphY,'2',$graphWidth,$graphHeight,'page');
		$content .= $this->makeAsParagraph($frame);
		
		/*
		* 6)
		* Commentaire du GTM
		*/		
		// Titre
		if($this->lastComment != '') {
			$interdits = array("&");
			$autorises = array("&amp;");
			/**
			 * BZ 50342 : remove limit of 50 characters
			 */
			$comment = utf8_encode(str_replace($interdits,$autorises,$this->lastComment));
			$comment = $this->makeAsParagraph($comment,'CommentGTM');
			$textBox = $this->createTextBox($comment);		
			// Frame du textbox	
			$framePosX = ($this->orientation == 'landscape') ? $posLegendX+0.35 : $posLegendX-1.1;
			$frame = $this->createFrame('FrameCommentGtm'.uniqid(),'styleFrameCommentGTM',$textBox,'Standard','0.2','0.2',$framePosX,$graphHeight+$posGraphY+0.1,'1',$graphWidth,'0.2','page');
			$content .= $this->makeAsParagraph($frame);
		}
		
		/*
		* 7)
		* Retour de la page
		*/
		return $content;	
	}
	
	public function astelliaGTMArray($titreGTM1,$imagePath1,$titreGTM2,$imagePath2,$titreGTM3,$imagePath3,$titreGTM4,$imagePath4,$comment1='',$comment2='',$comment3='',$comment4='') 
	{		
		/*
		* 1)
		* Définition des styles
		*/	
		// Style des graphes
		$style = '<style:graphic-properties style:vertical-pos="from-top" style:vertical-rel="paragraph" style:horizontal-pos="center" style:horizontal-rel="paragraph" style:mirror="none" fo:border="0.5mm solid #898989"/>';
		$this->addAutomaticStyleToContent('styleFrameImageGTMcenterP','graphic','Graphics',$style);
		// Style des puces
		$style = '<style:graphic-properties style:vertical-pos="from-top" style:vertical-rel="paragraph" style:horizontal-pos="from-left" style:horizontal-rel="paragraph" style:mirror="none"/>';
		$this->addAutomaticStyleToContent('styleFrameImagePuce','graphic','Graphics',$style);
		// Style titres
        // 26/04/2011 NSE évolution urgente aircel : style plus gros pour le titre
		$style = '<style:text-properties fo:font-size="62%" fo:font-weight="bold" style:font-size-asian="62%" style:font-weight-asian="bold" style:font-size-complex="62%" style:font-weight-complex="bold"/>';
		$this->addOfficeStyleToStyles('TitreGTMBigSmall','paragraph','Heading','text',$style);
		$style = '<style:text-properties fo:font-size="50%" fo:font-weight="bold" style:font-size-asian="85%" style:font-weight-asian="bold" style:font-size-complex="85%" style:font-weight-complex="bold"/>';
		$this->addOfficeStyleToStyles('TitreGTMsmall','paragraph','Heading','text',$style);	
		// Style du Frame du Textbox
		$style = '<style:graphic-properties style:vertical-pos="from-top" style:vertical-rel="paragraph" style:horizontal-pos="from-left" style:horizontal-rel="paragraph" style:mirror="none"/>';
		$this->addAutomaticStyleToContent('styleFrameTitreGTM','graphic','Graphics',$style);
		// Style du texte du commentaire
		$style = '<style:text-properties fo:font-size="40%" fo:font-weight="normal" style:font-size-asian="40%" style:font-weight-asian="normal" style:font-size-complex="40%" style:font-weight-complex="normal" style:font-name="Arial"/>';
		$this->addOfficeStyleToStyles('CommentGTM','paragraph','Standard','text',$style);
		// Style du Frame du commentaire
		$style = '<style:graphic-properties style:vertical-pos="from-top" style:vertical-rel="paragraph-content" style:horizontal-pos="center" style:horizontal-rel="paragraph" style:mirror="none" style:border-line-width="0.10mm" fo:border="0.10mm solid #898989" fo:padding="0.2mm"/>';
		$this->addAutomaticStyleToContent('styleFrameCommentGTM','graphic','Graphics',$style);
		
		// Test des images et détermination du nombre d'itérations
		if(file_exists($imagePath4)) {
			$max = 4;
		}elseif(file_exists($imagePath3)) {
			$max = 3;
		}elseif(file_exists($imagePath2)) {
			$max = 2;
		}elseif(file_exists($imagePath1)) {
			$max = 1;
		}else {
			return false;
		}

		/*
		* 2)
		* Création des graphes
		*/	
		// Largeur des graphes
		$graphWidth = '12';		
		// Données tableau
		$data = Array();
		for($i = 1; $i <= $max; $i++) {		
			// Titre
			$titre = "titreGTM$i";
            // 26/04/2011 NSE évolution urgente aircel : on limite la largeur du titre
            if(strlen($$titre)>=($this->orientation == 'landscape' ? self::MAX_TITLE_LENGTH_LANDSCAPE4 : self::MAX_TITLE_LENGTH_PORTRAIT))
                $$titre = substr($$titre,0,($this->orientation == 'landscape' ? self::MAX_TITLE_LENGTH_LANDSCAPE4 : self::MAX_TITLE_LENGTH_PORTRAIT)).'...';
			$title = $this->makeAsTitle('<text:s text:c="12"/>'.$$titre,'TitreGTMBigSmall','5');
			$textBox = $this->createTextBox($title);	
			$frameTitre = $this->createFrame('FrameTitreGtm'.uniqid(),'styleFrameTitreGTM',$textBox,'Standard','0.2','0.2','0','0','1','10','0.2');
			$frameTitre = $this->makeAsParagraph($frameTitre);		
			
			// Tableau du titre du graphe
			$dataTitre = Array(Array($title));
			$tableTitre = $this->createTable('table_'.uniqid(), $dataTitre);
		
			// Commentaire
			$comment = "comment$i";
		
			// Image du GTM
			$graph = "imagePath$i";
			$image = $this->newImage($$graph);
			// Calcul de la taille de l'image
			$imageSize = getimagesize($$graph);
			// Hauteur
			$graphHeight = ($imageSize[1] * $graphWidth) / $imageSize[0];
			// S'il y a un commentaire, on réduit légèrement la hauteur de l'image pour que le com passe
			if($$comment != '') $graphHeight -= 0.5;
			// Frame du GTM
			$framegtm = $this->createFrame('FrameImageGtm'.uniqid(),'styleFrameImageGTMcenterP',$image,'Standard',$graphWidth,$graphHeight,'0','0','2');		
			$framegtm = $this->makeAsParagraph($framegtm);
			
			// Commentaire du GTM
			$comment = "comment$i";
			if($$comment != '') {
				$interdits = array("&");
				$autorises = array("&amp;");
				$comment = (strlen($$comment) >= 50) ? substr(utf8_encode(str_replace($interdits,$autorises,$$comment)),0,48).'...' : utf8_encode(str_replace($interdits,$autorises,$$comment));
				$comment = $this->makeAsParagraph($comment,'CommentGTM');
				$textBox = $this->createTextBox($comment);		
				// Frame du textbox
				$frame = $this->createFrame('FrameCommentGtm'.uniqid(),'styleFrameCommentGTM',$textBox,'Standard',$graphWidth,'0.2','0','0','1');
				$framegtm .= $this->makeAsParagraph($frame);
			}			
			
			// Mémorisation du graphe dans le tableau de données
			if($i <= 2) {
				$data[0][] = $tableTitre;
				$data[1][] = $framegtm;
			}
			else {
				$data[2][] = $tableTitre;
				$data[3][] = $framegtm;
			}
			// Cas spécifique : s'il n'y a qu'un graphe à afficher, on met chaine vide dans la deuxième colonne du tableau afin de conserver la présentation.
			if($max == 1) {
				$data[0][] = '';
				$data[1][] = '';			
			}
		}
		
		/*
		* 3)
		* Création du tableau
		*/
		$table = $this->createTable('table_'.uniqid(), $data);
		$this->addContent($table);		
	}

	/****
	* astelliaGTMLandscape
	* Génère l'affichage d'un GTM astellia en mode paysage simple
	* @param String : titre du GTM
	* @param String : image du GTM
	* @param String : image de la puce
	* @param String : commentaire
	*****/		
	public function astelliaGTMLandscape($titreGTM,$imagePath,$puce,$comment='') {	
		$this->lastComment = $comment;
        $content = $this->astelliaGTM($titreGTM,$imagePath,$puce,'0','4.5','23.81','2.2','3');
		$this->addContent($content);
	}
	
	/****
	* astelliaGTMPortraitTop
	* Génère l'affichage d'un GTM astellia mode portrait (GTM haut de page)
	* @param String : titre du GTM
	* @param String : image du GTM
	* @param String : image de la puce
	* @param String : commentaire
	*****/		
	public function astelliaGTMPortraitTop($titreGTM,$imagePath,$puce,$comment='') {	
		$this->lastComment = $comment;
		$content = $this->astelliaGTM($titreGTM,$imagePath,$puce,'0','4','18','1.0','2.8');
		$this->addContent($content);
	}
	
	/****
	* astelliaGTMPortraitBottom
	* Génère l'affichage d'un GTM astellia mode portrait (GTM bas de page)
	* @param String : titre du GTM
	* @param String : image du GTM
	* @param String : image de la puce
	* @param String : commentaire
	*****/		
	public function astelliaGTMPortraitBottom($titreGTM,$imagePath,$puce,$comment='') {	
		$this->lastComment = $comment;
		$content = $this->astelliaGTM($titreGTM,$imagePath,$puce,'0','17','18','1.0','15.8');
		$this->addContent($content);
	}

	/****
	* getMimeType
	* Retourne le type mime d'un fichier
	* @param string fichier
	* @return String
	*****/
	public function getMimeType($file) {
                // 21/08/2012 BBX
                // BZ 26476 : compatibility with RedHat 6.2
		exec("file -i {$file} | awk '{print $2}' | sed 's/;//g'" , $result);
		return $result[0];
	}

	/****
	* exportPdf
	* exporte le fichier en PDF
	*****/
	public function exportPdf() {
		$cmd = 'export HOME=/tmp;/usr/bin/soffice -headless -norestore -nofirststartwizard "macro:///astellia.exports.SaveAsPdf('.$this->filename.')"';
		exec($cmd,$results);
	}
	
	/****
	* exportWord
	* exporte le fichier en DOC
	*****/
	public function exportWord() {
		$cmd = 'export HOME=/tmp;/usr/bin/soffice -headless -norestore -nofirststartwizard "macro:///astellia.exports.SaveAsDoc('.$this->filename.')"';
		exec($cmd,$results);
	}		
	
	/*
	*
	*/
	/*** STATIC FUNCTIONS ***/
	/*
	*
	*/
	
	/****
	* AnyToPdf
	* converti n'importe quel fichier reconnu par OOo en PDF
	* @param string fichier
	*****/
	public static function AnyToPdf($file) {
		$cmd = 'export HOME=/tmp;/usr/bin/soffice -headless -norestore -nofirststartwizard "macro:///astellia.exports.SaveAsPdf('.$file.')"';
		exec($cmd,$results);	
	}

	/****
	* AnyToDoc
	* converti n'importe quel fichier reconnu par OOo en DOC
	* @param string fichier
	*****/	
	public static function AnyToDoc($file) {
		$cmd = 'export HOME=/tmp;/usr/bin/soffice -headless -norestore -nofirststartwizard "macro:///astellia.exports.SaveAsDoc('.$file.')"';
		exec($cmd,$results);	
	}
	
	/****
	* AnyToXls
	* converti n'importe quel fichier reconnu par OOo en XLS
	* @param string fichier
	*****/	
	public static function AnyToXls($file) {
		$cmd = 'export HOME=/tmp;/usr/bin/soffice -headless -norestore -nofirststartwizard "macro:///astellia.exports.SaveAsXls('.$file.')"';
		exec($cmd,$results);	
	}

	/****
	* CsvToXls
	* converti un fichier CVS en fichier XLS
	* @param string fichier
	*****/		
	public static function CsvToXls($file) {
		$cmd = 'export HOME=/tmp;/usr/bin/soffice -headless -norestore -nofirststartwizard "macro:///astellia.exports.CsvToXls('.$file.')"';
		exec($cmd,$results);	
	}
}
?>
