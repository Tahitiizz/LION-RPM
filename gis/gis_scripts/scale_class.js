var ScaleControl = Class.create();

ScaleControl.prototype = {

	/**
	 * Constructeur de la classe
	 * 
	 * @access public
	 * @param string le nom du div contenant la miniature 
	 * @param object l'instance du controleur du GIS
	 * @return void
	 */	
	
	 initialize: function(target, control){

		this.target		= $(target);
		this.control	= control;		// reference à l'instance du controleur

		// On commence par vider le contenu de la target au cas où il serait non vide au départ

		this.target.innerHTML = '';
		
		// Creation du div contenant les valeurs d'echelle et ses composants (valeurs min et max)

		this.scale_value = Builder.node('div', {'id':'scale_value','style':'font-family: Arial, Helvetica, sans-serif;font-size:8pt;color: #585858'}, "");
		this.target.appendChild(this.scale_value);

		this.start_km = Builder.node('div', {'id':'start_km','style':'padding-left:8px;width:5px;float:left'}, "0");
		this.scale_value.appendChild(this.start_km);

		this.nb_kms = Builder.node('div', {'id':'nb_kms','style':'float:right'}, " kms");
		this.scale_value.appendChild(this.nb_kms);
		
		// Creation du div contenant l'image de l'echelle

		this.scale_line = Builder.node('div', {'id':'scale_line','align':'center'}, "");
		this.target.appendChild(this.scale_line);

		this.scale_img = Builder.node('img', {'src':'gis_icons/scaleline.gif', 'style':'width:99px;height:7px'}, "");
		this.scale_line.appendChild(this.scale_img);

		this.update();

		// Une fois l'ensemble des étapes d'initialisation effectuées, on rend le calque de l'echelle visible

		this.target.style.visibility = 'visible';
	},

	/**
	 * Permet la mise à jour de l'echelle
	 * 
	 * @access public
	 * @return void
	 */	

	update: function(){

		// Calcul du rapport largeur de la viewbox sur la largeur du calque de visualisation
		
		var ratio = this.control.viewbox[2]/this.control.viewArea.offsetWidth;

		// Calculs nécessaires pour arrondir le nombre à 2 chiffres après la virgule

		var x1_line = Number(this.control.viewbox[0])+10*ratio;
		var x2_line = x1_line+100*ratio;

		var nb = (Math.ceil((x2_line-x1_line)/10))/100;

		// Suivant la valeur de nb, on l'exprime en mètres ou en kilomètres

		(nb < 1) ? nb = (nb*1000)+" m" : nb += " kms";

		// maj du contenu du div
		
		Element.update(this.nb_kms, nb);
	},

	/**
	 * Détruit l'ensemble des composants html crée
	 * 
	 * @access public
	 * @return void
	 */	

	destroy: function(){

		// Avant d'entamer le processus de destruction des composants de l'echelle, on masque celle-ci

		this.target.style.visibility = 'hidden';

		// On détruit les calques composants de l'echelle

		this.target.removeChild(this.scale_value);
		this.target.removeChild(this.scale_line);
	}
}