/*********************************************************************************************************************************************
 Fonctions qui permettent de g�rer l'affichage d'une fen�tre volante
 **********************************************************************************************************************************************/
/**
 * 22/05/2013 : Link to Nova Explorer
 *
 * 12/01/2011 MMT DE Xpert  - add links to expert and Xpert contextual menus on graph data
 *
 * 11/08/2010 MMT, bz 16749  copie des changements tooltips pour correction firefox de la 5.1 par BBX (05/07/2010):
 *       BBX : Nouvelle gestion du tooltip BZ 13754
 *       - Recodage fonction popalt
 *       - Modification classe Tooltip
 *       - Compatibilit� tous navigateurs
 *       - Destruction tooltip au clique
 *
 *
 *  31/08/2010 MMT - DE firefox bz 17306
 *      - Ajout du parametre 'useActiveX' true/false pour liens vers AA
 *      - erreurs js lors de la cr�ation du menu contextuel boutton droit sur dashboard
 *      - correction erreur tooltip sur graph sous firefox
 *
 */


/**
 *
 - maj 18/02/2009, benoit : cr�ation d'une nouvelle fonction 'getDocumentElement()' et utilisation de celle-ci pour r�cup�rer des valeurs
 du document. En effet, les valeurs des �lements de document.body retourn�es pr�cedemment �taient incorrectes car l'application utilise
 maintenant une DTD qui inhibe ces valeurs. Il faut dans ce cas utiliser document.documentElement. La fonction JS cr�e permet de d�tecter
 ce cas et de basculer entre document.body et document.documentElement
 
 28/05/2009  GHX
 - Modification pour AA pour prendre en compte le nouveau model du menucontextuel
 14/08/2009 GHX
 - Correction du BZ 10289
 *
 */

/**
 *	- maj 28/08/2008 SLC : changement de class mouseover->tooltip, pour harmoniser l'apparence des tooltips
 *	- maj 13/03/2007 Gw�na�l    Cr�ation du message sur la fenetre volante au moment de l'affichage du texte et suppression lorsque l'on masque le message
 *	- maj 11/05/2007 Gw�na�l : modification du nom de la variable iframe en iframe_tooltip, conflit avec une autre variable qui � le m�me nom
 *	- maj 04/07/2007 Gw�na�l : changement de nom pour le menu contextuel vers AA
 *	- maj 04/07/07 Gw�na�l : optimisation mouseover sur les graphes
 *	- maj 20/07/07 Gw�na�l : nouveau tooltip -> se kill tout seul comme un grand
 -> prototype.js doit �tre charger avant + global_interface.css
 -> l'ancienne version est toujours op�rationnelle
 *	- maj 04/12/07 Maxime : On r�cup�re les dimensions exactes du div contenant le tooltip pour ie6
 *	- maj 18/03/2008 Maxime : On r�cup�re le nom du lien vers AA dans les param�tres
 *      - 27/03/2017 : [AO-TA] formatage des nombres dans les graphs t&a Requirement [RQ:4893]
 */

// modif 11:54 25/07/2007 Gw�na�l
// pararm�tre pour savoir si on affiche un tooltip d'une donn�e
var __pop = false;
//modif 26/06/2007 Gw�na�l
var linkAA = false;
//CB 5.3.1 : Link to Nova Explorer
var linkNE = false;
// 12/01/2011 MMT DE Xpert  - add links to expert and Xpert contextual menus on graph data
var linkXpert = false;
var valueLinkAA = null;
//CB 5.3.1 : Link to Nova Explorer
var valueLinkNE = null;
var useOldTooltip = false;
//affiche un layer
window.document.write("<DIV id='topdeck' style='POSITION: absolute; VISIBILITY: visible; Z-INDEX: 100; '></DIV>");
var iframe_tooltip = null;


/**
 * 12/01/2011 MMT DE Xpert
 * display a pop alt message and manage AA or/and Xpert graph links
 * calls pop(titre, msg, AA)
 * and also add Xpert Menu link with URL xpertLaunchURL and Label xpertMenuLabel
 * if xpertLaunchURL is given
 * CB 5.3.1 : Link to Nova Explorer (add NE parameter)
 */
function popGTMwithLinks(titre, msg, AA, NE, xpertLaunchURL, xpertMenuLabel) {

    // Gestion du s�parateur des milliers
    var split = msg.split('=');
    var separator = document.getElementById("graph_separator").value;
    var numberFormat = '';
    switch (separator) {
        case 'FR':
            numberFormat = new Intl.NumberFormat("fr-FR").format(split[1]);
            break;
        case 'EN':
            numberFormat = new Intl.NumberFormat("en-IN").format(split[1]);
            break;
        case 'NONE':
            numberFormat = split[1];
            break;
        default:
            numberFormat = new Intl.NumberFormat("en-IN").format(split[1]);
            break;
    }

    msg = split[0] + '=' + numberFormat;



    pop(titre, msg, AA, NE);

    if (xpertLaunchURL != '' && xpertLaunchURL != undefined) {
        // need to add NE and Product info to the url

        _myMenuContextuel.addMenuTemp({separator: true, id: 'separateurLinks'});
        eval("var myMenuItemXpert = {id : 'linkXpert', name: '" + xpertMenuLabel + "',	callback: function(){javascript:open_window('" + xpertLaunchURL + "','Launch_Xpert','yes','yes',1024,768);}};");
        _myMenuContextuel.addMenuTemp(myMenuItemXpert);
        linkXpert = true;

    }
}


// 31/08/2010 MMT - DE firefox bz 17306
// doit ajouter evenement prototype lors de l'appel de la fonction
// conserve la signature de la fonction existante pop(titre, msg, AA)
// en encapsullant par la nouvelle fonction popWithEvent(evt,titre, msg, AA)
// CB 5.3.1 : Link to Nova Explorer (add NE parameter)
function pop(titre, msg, AA, NE) {

    // recupere evenement courrant
    Event.observe(document.body, 'mouseover', function (event) {

        //popWithEvent(event, titre, msg, AA);
        //CB 5.3.1 : Link to Nova Explorer
        popWithEvent(event, titre, msg, AA, NE);

        document.body.stopObserving('mouseover');
    });

}

//CB 5.3.1 : Link to Nova Explorer (add NE parameter)
function popWithEvent(evt, titre, msg, AA, NE) {
    var tab;

    __pop = true;
    //modif 10:52 04/07/2007 Gw�na�l
    // suppression des balises tables => div et utilisation du css (cf .global_interface.css)
    // ancien code avant modif du 28/08/2008 de SLC
    var content = "<div class=\"mouseover\"><div class=\"titre\">" + titre + "</div><div class=\"msg\">" + msg + "</div></div>";
    // modif du 28/08/2008 de SLC : changement de class mouseover->tooltip, pour harmoniser l'apparence des tooltips
    var content = "<div class='tooltip'><div class='content'><div class='title'>" + titre + "</div><div class='text'>" + msg + "</div></div></div>";

    var nomlayer = document.getElementById("topdeck");
    nomlayer.innerHTML = content;
    nomlayer.style.visibility = "visible";
    // 31/08/2010 MMT - DE firefox bz 17306 passage parametre event
    positionTip(evt);

    // modif 28/06/2007 Gw�na�l
    // if ( AA != '' && AA != undefined ) {
    //CB 5.3.1 : Link to Nova Explorer
    if ((AA != '' && AA != undefined) || (NE != '' && NE != undefined)) {
        // maj 18/03/2008 Maxime : On r�cup�re le nom du lien vers AA dans les param�tres

        //12/01/2011 MMT DE Xpert pas de separateur si deja cr�e par Le menu Xpert
        if (!linkXpert) {
            _myMenuContextuel.addMenuTemp({separator: true, id: 'separateurLinks'});
        }

        //CB 5.3.1 : Link to Nova Explorer
        if (AA != '' && AA != undefined) {
            tab = AA.split('|t|');
            var valueLinkAA = tab[0];
            var labelLinkAA = tab[1];

            // 31/08/2010 MMT - DE firefox bz 17306 ajout du param�tre 'utilisation activeX oui/non'
            // pour liens AA. utilisation d'un nouveau delimiteur de valeur |j| du a la grande complexit� de r�cuperer
            // le separateur de valeure |s| dans sys_global_param dans ce fichier js
            valueLinkAA = valueLinkAA + '|j|' + doesBrowserSupportActiveX();
            // 31/08/2010 MMT - DE firefox bz 17306 besoin d'augmenter la taille de la fenetre pour satisfaire firefox de 600 a 700
            eval("var myMenuItemAA = {id : 'linkAA', name: '" + labelLinkAA + "',	callback: function(){javascript:open_window('php/launchAA.php?value=" + valueLinkAA + "','Launch_Activity_Analysis','yes','yes',700,350);}};");
            _myMenuContextuel.addMenuTemp(myMenuItemAA);
            linkAA = true;
        }

        //CB 5.3.1 : Link to Nova Explorer
        if (NE != '' && NE != undefined) {
            tab = NE.split('|t|');
            var valueLinkNE = tab[0];
            var labelLinkNE = tab[1];

            eval("var myMenuItemNE = {id : 'linkNE', name: '" + labelLinkNE + "',	callback: function(){javascript:open_window('php/launchNE.php?value=" + valueLinkNE + "','Launch_Nova_Explorer','yes','yes',700,350);}};");
            _myMenuContextuel.addMenuTemp(myMenuItemNE);
            linkNE = true;
        }
    }
}

// 31/08/2010 MMT - DE firefox bz 17306
// fonction destin�e a savoir si le navigateur client peut supporter ActiveX (IE) et donc lancer AA directement
// voir LinkToAA.class.php pour plus d'info'
function doesBrowserSupportActiveX() {
    return (typeof (ActiveXObject) != 'undefined');
}

// Modif christophe.
// Permet d'afficher un div contenant un gif "loading data".
function preload() {
    var content = "<table><tr><td><img src='wait.gif'/></td></tr></table>";
    var nomlayer = document.getElementById("topdeck");
    nomlayer.innerHTML = content;
    nomlayer.style.visibility = "visible";
    positionTipScreen();
}

///////////////////////  CUSTOMIZE HERE   ////////////////////
// settings for nomlayer
// Do you want tip to move when mouse moves over link?
var offX = 35;        // how far from mouse to show tip
var offY = -15;

/////////////////////////////////////////////////////////////
//  donomlayer function
//                        Assembles content for nomlayer and writes it to tipDiv.
//                        Call positionTip function from here if tipFollowMouse
//                        is set to false.
//////////////////////////////////////////////////////////////
var mouseX, mouseY;
function trackMouse(evt) {
    // 31/08/2010 MMT - DE firefox bz 17306 gestion de l'evenement Prototype pass� en param�tre
    var event = getBrowserEvent(evt);
    mouseX = event.clientX + getDocumentElement('scrollLeft');
    mouseY = event.clientY + getDocumentElement('scrollTop');
    positionTip(evt);

}

// 31/08/2010 MMT bz 17306:compatibilit� Firefox utilisation parametre evt
// return the appropriate event object
function getBrowserEvent(evt) {
    return evt || window.event;
}
/////////////////////////////////////////////////////////////
//  positionTip function
//                If tipFollowMouse set false, so trackMouse function
//                not being used, get position of mouseover event.
//                Calculations use mouseover event position,
//                offset amounts and nomlayer width to position
//                nomlayer within window space available.
/////////////////////////////////////////////////////////////
function positionTip(evt) {

    // 31/08/2010 MMT - DE firefox bz 17306 gestion de l'evenement Prototype pass� en param�tre
    var event = getBrowserEvent(evt);
    mouseX = event.clientX + getDocumentElement('scrollLeft');
    mouseY = event.clientY + getDocumentElement('scrollTop');
    var nomlayer = document.getElementById("topdeck");

    //  modif 13/03/2007 Gw�na�l
    // On r�cup�re la taille de la table au lieu du layer topdeck car il prend en compte la taille de l'iframe qui est par d�faut 300px sur 150px
    // nomlayertable width and height
    // try {
    // var nomlayertable = nomlayer.getElementsByTagName('div')[0]
    // var tpWd = nomlayertable.clientWidth;
    // }
    // catch ( e ) {
    var nomlayertable = nomlayer.getElementsByTagName('div')[0]
    var tpWd = nomlayertable.clientWidth;
    // }
    var tpHt = nomlayertable.clientHeight;
    // document area in view (subtract scrollbar width for ns)
    var winWd = getDocumentElement('clientWidth') + getDocumentElement('scrollLeft');
    var winHt = getDocumentElement('clientHeight') + getDocumentElement('scrollTop');

    // check mouse position, tip and window dimensions
    // and position the nomlayer
    if ((mouseX + offX + tpWd) > winWd)
        nomlayer.style.left = mouseX - (tpWd + offX) + "px";

    else
        nomlayer.style.left = mouseX + offX + "px";

    if ((mouseY + offY + tpHt) > winHt)
        nomlayer.style.top = mouseY - (tpHt + offY) + "px";

    else
        nomlayer.style.top = mouseY + offY + "px";

    //modif 13/03/2007 Gw�na�l
    //Sp�cifie la taille de l'iframe

    if (iframe_tooltip != null) {
        // modif 04/12/07 - maxime : On r�cup�re les dimensions exactes du div contenant le tooltip
        iframe_tooltip.style.width = tpWd; //= Largeur du tooltip
        iframe_tooltip.style.height = tpHt;
        ; //= Hauteur du tooltip
    }
}

// Rajout christophe
// Permet d'afficher l'objet au mileiu de la page.
function positionTipScreen(evt) {
    var nomlayer = document.getElementById("topdeck");
    var tpWd = nomlayer.clientWidth / 2;
    var tpHt = nomlayer.clientHeight * 2;
    nomlayer.style.left = tpWd + "px";
    nomlayer.style.top = tpHt + "px";
}

///////////// end nomlayer code ///////////////
function kill() {
    // modif 11:54 25/07/2007 Gw�na�l
    // ajout d'un condition dans le cas o� on doit killer le tooltip d'une donn�e
    if (_myTooltip && __pop == false)
        return;

    __pop = false;

    var nomlayer = document.getElementById("topdeck");
    nomlayer.style.visibility = "hidden";

    //modif 26/06/2007 Gw�na�l
    // suppression du lien vers AA si celui-ci lors que la souris quitte une donn�e d'un graphe
    //12/01/2011 MMT DE Xpert ajoute gestion menu Xpert
    //if ( linkAA || linkXpert) {
    //CB 5.3.1 : Link to Nova Explorer
    if (linkAA || linkNE || linkXpert) {
        _myMenuContextuel.removeMenuTemp('separateurLinks');
    }

    if (linkAA == true) {
        valueLinkAA = null;
        // 10:01 28/05/2009 GHX
        // Modification de la variable et du nom de la fonction
        _myMenuContextuel.removeMenuTemp('linkAA');
    }
    linkAA = false;

    //CB 5.3.1 : Link to Nova Explorer
    if (linkNE == true) {
        _myMenuContextuel.removeMenuTemp('linkNE');
    }
    linkNE = false;

    //12/01/2011 MMT DE Xpert ajoute gestion menu Xpert
    if (linkXpert == true) {
        // 10:01 28/05/2009 GHX
        // Modification de la variable et du nom de la fonction
        _myMenuContextuel.removeMenuTemp('linkXpert');
    }
    linkXpert = false;

    // modif 13/03/2007 Gw�na�l
    // Suppression de l'iframe
    if (iframe_tooltip != null) {
        nomlayer.removeChild(iframe_tooltip); //Suppression de l'iframe du tooltip affich�
        iframe_tooltip = null;
    }
}

/**
 * 05/07/2010 BBX
 * R��criture de la classe pour correction de bug + compatibilit�s tous navigateurs
 * BZ 13754
 */
_useOldTooltip = false;
try {
    Tooltip = Class.create();
    Tooltip.prototype =
            {
                initialize: function (txt, x, y)
                {
                    // Mouse Position
                    this.posx = x;
                    this.posy = y;

                    // Content and title
                    this.content = txt;
                    this.title = arguments[3] || false;

                    // Tooltip Creation
                    this.createTip();
                    this.showTip();
                },
                createTip: function ()
                {
                    // Container layer creation
                    this.topdeck = document.createElement('div');
                    this.topdeck.className = 'tooltip';
                    Element.setStyle(this.topdeck, {position: 'absolute'});

                    // Tooltip layer creation
                    this.tooltip = document.createElement('div');
                    this.tooltip.className = 'content';

                    // Title displayed if available
                    if (this.title) {
                        var title = document.createElement('div');
                        title.className = 'title';
                        Element.update(title, this.title);
                        this.tooltip.appendChild(title);
                    }

                    // Content layer creation
                    var content = document.createElement('div');
                    content.className = 'text';
                    Element.update(content, this.content);
                    this.tooltip.appendChild(content);

                    // Arrow displaying
                    this.divimg = document.createElement('div');
                    this.divimg.className = 'fleche';
                    this.topdeck.appendChild(this.divimg);

                    // Adding to doc
                    this.topdeck.appendChild(this.tooltip);
                    document.body.appendChild(this.topdeck);

                    // IE6 hack
                    if (Prototype.Browser.IE)
                    {
                        if (navigator.appVersion.indexOf('MSIE 6.0') != -1)
                        {
                            iframeTooltip = document.createElement('iframe');
                            iframeTooltip.setAttribute('frameborder', '1');
                            iframeTooltip.setAttribute('scrolling', 'no');
                            iframeTooltip.setAttribute('src', 'about:blank');
                            iframeTooltip.style.zIndex = 11100;
                            iframeTooltip.style.position = 'absolute';
                            iframeTooltip.style.top = '0px';
                            iframeTooltip.style.left = '0px';
                            iframeTooltip.style.filter = 'alpha(opacity=0)';
                            iframeTooltip.style.width = this.topdeck.offsetWidth + 'px';
                            iframeTooltip.style.height = this.topdeck.offsetHeight + 'px';
                            this.topdeck.appendChild(iframeTooltip);
                        }
                    }
                },
                showTip: function () {
                    this.positionTip();
                    this.topdeck.show();
                },
                hideTip: function () {
                    if (this.topdeck)
                        this.topdeck.remove();
                },
                positionTip: function () {
                    this.topdeck.setStyle({left: this.posx + 'px', top: this.posy + 'px'});
                }
            };
} catch (e) {
    _useOldTooltip = true;
}

// 18/02/2009 - Modif. benoit : ajout de la fonction JS ci-dessous qui permet de retourner la valeur d'un element du corps du fichier HTML
// (documentElement ou body suivant la pr�sence / abscence d'une DTD)

function getDocumentElement(elt) {
    return (document.body && document.body[elt]) ? document.body[elt] : document.documentElement[elt];
}



/**
 * 05/07/2010 BBX
 * Nouvelle gestion tooltip
 * BZ 13754
 */
var _myTooltip = null;
var _toolTipCallingElement = null;
var _mousePosX = 0;
var _mousePosY = 0;

document.observe("dom:loaded", function () {
    // Chaque �l�ment survol� est m�moris�
    Event.observe(document, 'mouseover', function (event) {
        if (Event.element(event) != _toolTipCallingElement) {
            _toolTipCallingElement = Event.element(event);
            _mousePosX = Event.pointerX(event);
            _mousePosY = Event.pointerY(event);
        }
    });
    // Le clique sur le document permet de faire disparaitre un tooltip rebel
    Event.observe(document, 'click', function (event) {
        if (_myTooltip != null)
            _myTooltip.hideTip();
        _myTooltip = null;
    });
});


/**
 * 05/07/2010 BBX
 * R��criture de la fonction popalt. BZ 13754
 * Nouveaut�s :
 *  - Compatible tous navigateurs
 *  - Permet de fermer un tooltip au clique
 */
function popalt(msg)
{
    // Si un �l�ment est survol�
    if (_toolTipCallingElement != null)
    {
        // Si classe Tooltip �chou�e, utilisation d'un title
        if (_useOldTooltip) {
            _toolTipCallingElement.writeAttribute("title", msg);
            return false;
        }

        // Si Tooltip existe, on le d�truit
        if (_myTooltip != null) {
            _myTooltip.hideTip();
            _myTooltip = null;
        } else {
            // Cr�ation du tooltip
            // 22/11/2011 BBX : fixing tooltip blinking
            _myTooltip = new Tooltip(msg, _mousePosX + 10, _mousePosY + 10, arguments[1] || false);
            // Tooltip d�truit lorsque l'�l�ment n'est plus survol�
            Event.observe(_toolTipCallingElement, 'mouseout', function (event) {
                if (_myTooltip != null)
                    _myTooltip.hideTip();
                _toolTipCallingElement.stopObserving('mouseout');
                _myTooltip = null;
            });
            // Tooltip d�truit lorsque l'�l�ment est cliqu�
            Event.observe(_toolTipCallingElement, 'click', function (event) {
                if (_myTooltip != null)
                    _myTooltip.hideTip();
                _toolTipCallingElement.stopObserving('click');
                _myTooltip = null;
            });
        }
    }
    return true;
}

