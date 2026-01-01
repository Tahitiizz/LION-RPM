/*
	07/02/2008 christophe
	> fichier trouver sur le net puis modifié :
		- affichage du curseur lors du survol des couleurs.
		- Paramètres : 
			element : id du champ hidden (ou autre) dans lequel on stocke la valeur de la couleur
			trigger : id de l'élément qui fera apparaître le color picker
			> modif : la couleur de fond de cet élément change en fonction de la couleur choisie.
	Permet d'afficher un color picker
	nécessite le fichier prototype.js pour fonctionner (v1.5+).
	
	06/05/2009 - SPS 
		- ajout de la balise fermante </td>
		- modification de l'evenement onClick pour fermer la fenetre apres la selection de la couleur
		- mise a jour de la fct toggleTable en fonction de la librairie presente dans builder/js
*/
var ColourPicker = Class.create();
ColourPicker.prototype = {
    colourArray: new Array(),
    element: null,
    trigger: null,
    tableShown: false,
    
    initialize: function(element, trigger) {
        this.colourArray = new Array();
        this.element = $(element);
        this.trigger = $(trigger);
       
	    this.trigger.onclick = this.toggleTable.bindAsEventListener(this);
        // Initialise the color array
        this.initColourArray();
        this.buildTable();
		
    },
    initColourArray: function() {
        var colourMap = new Array('00', '33', '66', '99', 'AA', 'CC', 'EE', 'FF');
        for(i = 0; i < colourMap.length; i++) {
            this.colourArray.push(colourMap[i] + colourMap[i] + colourMap[i]);
        }
        
        // Blue
        for(i = 1; i < colourMap.length; i++) {
            if(i != 0 && i != 4 && i != 6) {
                this.colourArray.push(colourMap[0] + colourMap[0] + colourMap[i]);
            }
        }
        for(i = 1; i < colourMap.length; i++) {
            if(i != 2 && i != 4 && i != 6 && i != 7) {
                this.colourArray.push(colourMap[i] + colourMap[i] + colourMap[7]);
            }
        }
        
        // Green
        for(i = 1; i < colourMap.length; i++) {
            if(i != 0 && i != 4 && i != 6) {
                this.colourArray.push(colourMap[0] + colourMap[i] + colourMap[0]);
            }
        }
        for(i = 1; i < colourMap.length; i++) {
            if(i != 2 && i != 4 && i != 6 && i != 7) {
                this.colourArray.push(colourMap[i] + colourMap[7] + colourMap[i]);
            }
        }
        
        // Red
        for(i = 1; i < colourMap.length; i++) {
            if(i != 0 && i != 4 && i != 6) {
                this.colourArray.push(colourMap[i] + colourMap[0] + colourMap[0]);
            }
        }
        for(i = 1; i < colourMap.length; i++) {
            if(i != 2 && i != 4 && i != 6 && i != 7) {
                this.colourArray.push(colourMap[7] + colourMap[i] + colourMap[i]);
            }
        }
        
        // Yellow
        for(i = 1; i < colourMap.length; i++) {
            if(i != 0 && i != 4 && i != 6) {
                this.colourArray.push(colourMap[i] + colourMap[i] + colourMap[0]);
            }
        }
        for(i = 1; i < colourMap.length; i++) {
            if(i != 2 && i != 4 && i != 6 && i != 7) {
                this.colourArray.push(colourMap[7] + colourMap[7] + colourMap[i]);
            }
        }
        
        // Cyan
        for(i = 1; i < colourMap.length; i++) {
            if(i != 0 && i != 4 && i != 6) {
                this.colourArray.push(colourMap[0] + colourMap[i] + colourMap[i]);
            }
        }
        for(i = 1; i < colourMap.length; i++) {
            if(i != 2 && i != 4 && i != 6 && i != 7) {
                this.colourArray.push(colourMap[i] + colourMap[7] + colourMap[7]);
            }
        }
        
        // Magenta
        for(i = 1; i < colourMap.length; i++) {
            if(i != 0 && i != 4 && i != 6) {
                this.colourArray.push(colourMap[i] + colourMap[0] + colourMap[i]);
            }
        }
        for(i = 1; i < colourMap.length; i++) {
            if(i != 2 && i != 4 && i != 6 && i != 7) {
                this.colourArray.push(colourMap[7] + colourMap[i] + colourMap[i]);
            }
        }
    },
    buildTable: function() {
        if(!this.tableShown) {
            html = "<div id=\"" + this.trigger.id + "ColourPicker\" style=\"display: none;position:absolute;top:0px;\" ><table class=\"colorPicker\">"
            for(i = 0; i < this.colourArray.length; i++) {
                if(i % 8 == 0) {
                    html += "<tr>";
                }
				/* 06/05/2009 - SPS 
					- ajout de la balise fermante </td> 
					- modification de l'evenement onClick pour fermer la fenetre apres la selection de la couleur
				*/
                //html += "<td style=\"cursor:pointer;background-color: #" + this.colourArray[i] + ";\" title=\"#" + this.colourArray[i] +  "\" onClick=\"$('" + this.element.id + "').value = '#" + this.colourArray[i] + "'; $('" + this.trigger.id + "').style.backgroundColor ='#" + this.colourArray[i] + "'; $('" + this.trigger.id + "ColourPicker').style.display = 'none';\"></td>";
				html += "<td style=\"cursor:pointer;background-color: #" + this.colourArray[i] + ";\" title=\"#" + this.colourArray[i] +  "\" onClick=\"$('" + this.element.id + "').value = '#" + this.colourArray[i] + "'; $('" + this.trigger.id + "').style.backgroundColor ='#" + this.colourArray[i] + "'; $('color_picker_container').style.display = 'none';\"></td>";
                if(i % 8 == 7) {
                    html += "</tr>";
                }
            }
            html += "</table></div>";
            new Insertion.After(this.trigger, html);
        }
    },
    toggleTable: function(sender) {
	    /* 06/05/2009 - SPS : mise a jour de la fct en fonction de la librairie presente dans builder/js/ */
		/*var obj = $(Event.element(sender).id + 'ColourPicker');
		obj.style.top = (event.clientY-122)+'px';
        obj.style.display = (obj.style.display == 'block' ? 'none' : 'block');
		*/
		var obj = $(Event.element(sender).id + 'ColourPicker');
		var cpc = $('color_picker_container');
		if (cpc.innerHTML == obj.innerHTML) {
			// on reste dans le même color picker
			cpc.innerHTML = obj.innerHTML
			if (cpc.style.display == 'block') {
				cpc.style.display = 'none';
			} else {
				cpc.style.display = 'block';
				cpc.style.top = ((sender.pageY)-10)+'px';
				cpc.style.left = ((sender.pageX)+20)+'px';
			}
		} else {
			// on change de color picker
			cpc.innerHTML = obj.innerHTML
			cpc.style.display = 'block';
			cpc.style.top = ((sender.pageY)-10)+'px';
			cpc.style.left = ((sender.pageX)+20)+'px';
		}
    }
}
