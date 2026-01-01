/*
 *  @cb50400
 *
 *  20/08/2010 NSE DE Firefox bz17383 : drag&drop non fonctionnel : ajout de event en paramètre de fonctions
 *  07/09/2010 NSE DE Firefox bz 17383 : modification d'une condition / d'un paramètre non fonctionnelle
 *  17/01/2011 NSE bz 20136 : perte du produit sur lequel les Query sont sauvegardées
 *  11/02/2011 NSE DE Query Builder :
 *      - modification des messages d'erreur sur le format des dates,
 *      - modification des messages sur l'absence de paramètres dans le select
 *      - ajout de la fonction d'affichage du message sur l'affichage des réusltats limité à 1000
 *  17/02/2011 MMT Bz 20310: NA condition pour Firefox
 *  06/04/2011 MMT bz 20776 centralize la gestion des valeures de l'operateur de condition
 *			on supprime la liste et on la rempli avec les operateurs specifiques au type de paramètre
 *	 05/07/2011 MMT Bz 22724 operateurs < et <= ignorés
 *
 **/
//                 CONTENU DU FICHIER :
//				fichier composé uniquement de fonction javascript permettant 
//				la modification (ou la configuration) de :builder_report_onglet.php
//					LISTING:
//
//	function saisie_disabled()
//		fonction qui empeche la saisie au clavier


//	function HandleDrop(num_case,num_select,destination,data)
//		fonction qui gere le drag and drop et le transfer des infos


//	function DelRow_condition()
//		supprime une condition 


//	function AddRow_condition()
//		fonction qui ajoute une condition


//	function Erase(num_case,num_select)
//		fonction qui efface une case du champ select et décale toutes les autres cases


//	function Ajouter_condition(num_case,num_select,code_condition,condition,operateur,condition2)
//		fonction servant au réaffichage d'une condition ( opérateur et valeur saisi par l'utilisateur )


//	function Modif_sort(new_sort)
//		fonction servant au reaffichage du menu deroulant "sort"


//	function Modif_limit(new_limit)
//		fonction servant au reaffichage du menu deroulant "limit"


//	function Validation(num_select)
//		fonction controlant la validité des informations saisies dans le formulaire



//	function val()
//		fonction controlant la validité des informations saisies dans le formulaire

//	function recharge_page() 
//	recharge la page sans la modifier, toutes les variables (hidden et autres ) sont transmises par formulaire POST 
//	cette fonction est utilisée pour sauvegarder une reque , en effet ,toutes les info etant dans $_POST apres cette fonction, il suffit juste de sauvegarder cette variable


//		fonction qui empeche la saisie au clavier
function saisie_disabled() 
{
    alert('You can only Drag&Drop data');
    // Mise en commentaire de blur() car le fait de faire un blur faisiat perdre la main sur l'application si une autre était ouverte en même temps.
    /*blur();*/
return true;
}

/*
 * Fonction qui diffère l'acriture d'une valeur dans un champ.
 * Typiquement, on retarde l'écriture dans les input condition_0 et param_order de façon à éviter un bug sous Firefox.
 * (sans le différé, l'image dropée est chargée dans la page du navigateur)
 */
function setDroppedFieldValue(id,value){

    setTimeout('document.getElementById("'+id+'").value="'+value+'"',80);
    //remplace document.getElementById(id).value=value;
}

//		fonction qui gere le drag and drop et le transfer des infos ( si la variable passedData est vide  alors les données sont transmisent par drag and drop: event.dataTransfer.getData("Text") , sinonc est une fonction de reaffichage de la page a partir de données deja connues ( comme apres l'eecution d'une requete)
// 20/08/2010 NSE DE Firefox bz17383 : ajout de event
// 07/09/2010 NSE DE Firefox bz 17383 : différé de modification des input condition et paramètre
function HandleDrop(event,num_case,destination,passedData)
{
// teste si les données proviennent d'un draganddrop ou non
    if(passedData)		//les données sont passées en paramétre , pas de drag and drop
    {
		drag=false;	// la fonction n'a pas était appelée par un drag and drop , donc drag = false
	}
	else 
	{
		var passedData = event.dataTransfer.getData("Text");		//récupération des donées drag&dropées
		drag=true;	// la fonction a était appelée par un drag and drop , donc drag = true
	}
	if (passedData);
	{
		passedData = passedData.split(":");	

    	// 17/01/2011 NSE bz 20136 : récupération du paramètre id_product passé dans le cas d'une query sauvegardée
    	var product='';
    	if(passedData[0]==5)
        	product = passedData[5];
        
		// 24/04/2007 - Modif. benoit : si l'axe3 est defini, on ajoute le nom de la na d'axe3 à la chaine 'passedData'

		var axe3_name = "";
		if(passedData[0] == 8 && passedData[5].indexOf('undefined') == -1) axe3_name = ":"+passedData[5];

		passedData = passedData[0]+":"+passedData[1]+":"+passedData[2]+":"+passedData[3]+":"+passedData[4]+axe3_name;
		passedData2=passedData;
		passedData = passedData.split(":");
		// passedData contient la donnée à traiter sous la forme d'un tableua
	 	// passedData2 sous la forme d'un string
		elem_nbr_donnees=document.getElementById("nbr_donnees");			//determine le nombre délément deja séléctionnés, et donc  quel emplacement ajouter l'élément.
		case_cible=document.getElementById("donnees_hidden_"+num_case);	 //donnee conenu dans la case ciblé avant le drag&drop

		if(!elem_nbr_donnees.value)			// si le nombre d' élément n'a pas encore était initialisé on l'initialise a 0
		{
			nbr_donnees=0;
		}
		else {
			nbr_donnees=eval(elem_nbr_donnees.value);			// sinon on concertit le string en int
		}
			
		//teste si la première donnée est un chiffre auquel cas il ne faut pas dropper les données dans le champ
		if ( !isNaN(passedData[0]))
		{
		
			if(passedData[0]==5)		// est ce une query sauvegradée ?
			{
				recharge_page("id_query="+passedData[2]+"&family="+passedData[4]+"&product="+product);	// dans ce cas on recharge la page en passant en paramétre l'id de la query et la famille
			
				event.returnValue = false;
			}
			 if ((destination=="donnees")&&((passedData[0]==4)||(passedData[0]==3)||(passedData[0]==2)||(passedData[0]==6))) // donnée ajoutée a une case du select 
			{
				x=document.getElementById("nbr_case").value;
				if(nbr_donnees<=eval(x))			// on vérifie que le nombre max de données ne soit pas atteind
				{
					document.getElementById("donnees_hidden_"+nbr_donnees).value=passedData2;		// transfert des infos utiles
					document.getElementById("donnees_"+nbr_donnees).value=passedData[1];			// affichage du texte
					nbr_donnees++;							// on augmente le nombre de données
					elem_nbr_donnees.value=nbr_donnees;			// et on met a jour l information sur la page
				}
				else							// si le nombre de données max est atteind
				{
					alert("You can not select more data");	// on affiche un message pour l'utilisateur
					event.returnValue = false;
				}
			}
			if ((destination=="donnees")&&(passedData[0]==1))   //fonction ajoutée à une case du select
			{
			
				if(case_cible.value) // vérifie que la case ou l'on  met la fonction contient bien une donnée
				{
					donnee_case_cible=case_cible.value;
					donnee_tmp=(donnee_case_cible.split(":")[0]); // données_tmp contient le type de la données 4 : data type.
					if(donnee_tmp==4)		// vérifie que la donnée cible est bien un data type
					{
						//  on ajoute la fonction
						donnee_tmp=(donnee_case_cible.split(":")[1]);
						document.getElementById("fonction_"+num_case).value=passedData2;
						document.getElementById("donnees_"+num_case).value=passedData[1]+"("+donnee_tmp+")";
					}
					else 					// si la donnée cible n est pas une data , on en informe l'utilisateur
					{
						alert("Function can only be dropped on data.");
						event.returnValue = false;
					}
				}
				else						// si case ciblée est vide, on en informe l'utilisateur
				{
					alert("Function can only be dropped on a filled cell.");
					event.returnValue = false;
				}
			}
			if ((destination=="donnees")&&(passedData[0]==7))
			{
				alert("My_network_agregation can only be dropped on a condition.");
				event.returnValue = false;
			}
			if ((destination=="donnees")&&(passedData[0]==8)) // 3eme axe
			{
				alert("This item can only be dropped on a condition.");
				event.returnValue = false;
			}
			
			if (destination=="order_by")//donnée envoyé sur le order_by
			{
				if(passedData[0]==1)	// on verifie que la donnée envoyée sur l'order by n est pas une fonction
				{
					alert("Function can not  be dropped on \"Order_by\".");
					event.returnValue = false;
				}
				else if(passedData[0]==7)	// on verifie que la donnée envoyée sur l'order by n'est pas un "my network agregation"
				{
					alert("My network agregation can only be dropped on a condition.");
					event.returnValue = false;
				}
				else if (passedData[0]==8) //on verifie que la donne envoyée sur l'order by n'est pas un troisième axe (ex: Home Network)
				{
					alert("This item can only be dropped on a condition.");
					event.returnValue = false;				
				
				}
				else 
				{
					document.getElementById("param_order_hidden").value=passedData2;
					// 07/09/2010 NSE DE Firefox : on diffère l'écriture
                    setDroppedFieldValue('param_order',passedData[1]);
				}
			}
			if (destination=="condition")		// donnée envoyée sur une condition
			{
				// 06/04/2011 MMT bz 20776 centralize la gestion des valeures de l'operateur de condition
				// on supprime la liste et on la rempli avec les operateurs specifiques au type de paramètre
				var condOpSel = $('op_condition_'+num_case);
				// store la valeure existante
				prevValue = condOpSel.value;
				//vide la liste actuelle
				condOpSel.length = 0;
				
				// liste les operateur suivant le type
				if(passedData[0]==2)	{// NA
					availOps = ["=","<>"];
				} else if(passedData[0]==7){ // my_network_agregation
					availOps = ["in"];
				} else {
					// 5/7/2011 MMT Bz 22724 operateurs "<" et "<=" ignorés
					availOps = ["=",">",">=","<","<=","<>"];
				}
				// rempli le select avec les valeures
				for (i=0;i<availOps.length;i++) {
					opValue = availOps[i];
					option= new Element('option', {value:opValue }); // on creer une balise de type <option></option> avec valeur 'in'
					// 5/7/2011 MMT Bz 22724 remplace < et > par equivalent html pour le label car < n'est pas affiché sous IE'
					label = opValue.replace("<","&lt;").replace(">","&gt;");
					option.update(opValue.replace("<","&lt;"));
					condOpSel.insert(option);
					// attribut l'element selectionné
					if(prevValue == opValue){
						condOpSel.value=prevValue;
					}
				}
				
				// fin 06/04/2011 MMT bz 20776
				
				if(passedData[0]==7)				// la donnée est un "my_network_agregation", la condition est donc forcement : Cellname in "my network agregation"
				{
					// 17/02/2011 MMT Bz 20310: NA condition pour Firefox
					setDroppedFieldValue("condition_"+num_case,"Element");
					document.getElementById("value_condition_"+num_case).value=passedData[1];		// on met "my network agregation" comme valeur de la condition
					document.getElementById("value_condition_"+num_case).disabled=true;			// on empeche de modifier cette valeur
					// 06/04/2011 MMT bz 20776 factorise correction 20310 avec 20776
					document.getElementById("op_condition_"+num_case).disabled=true;
					document.getElementById("condition_hidden_"+num_case).value=passedData2;
				}
				else
				{
					if(passedData[0]!=1 && passedData[0]!=8)		// vérifie que l'on ne transfert pas une fonction
					{
						document.getElementById("value_condition_"+num_case).disabled=false;
						document.getElementById("op_condition_"+num_case).disabled=false;			
						// dans le cas ou un "my network agregation" est présent a cette emplacement il faut réautoriser la saisie de valeur et le menu déroulant
		 				condition_hidden_ancienne=document.getElementById("condition_hidden_"+num_case).value;	// on récupere donc la valeur présente 
		 				condition_hidden_ancienne=condition_hidden_ancienne.split(":");							// les information sont séparé par des ":" , on les place dans un tableau pour y acceder plus simplement 
		 				if(condition_hidden_ancienne[0]==7)						// si c est un "my network agregation"
		 				{
		 					document.getElementById("value_condition_"+num_case).disabled=false; // on autorise la saisie au clavier de la"valeur"
		 					document.getElementById("value_condition_"+num_case).value="";		// on efface l'ancienne valeur présente (le my network agregation)
		 					document.getElementById("op_condition_"+num_case).disabled=false;	// on autorise la modification du menu déroulant
							// 06/04/2011 MMT bz 20776 supprime gestion des operateurs précedents
		 					// on pas alors transferer les informations normalement...
		 				}
	 				
		 				document.getElementById("condition_hidden_"+num_case).value=passedData2;			// on passe les informations
                        // 07/09/2010 NSE DE Firefox : on diffère l'écriture
                        setDroppedFieldValue('condition_'+num_case,passedData[1]);                                        				// on gére l'affichage
					} // 07/06/2007 - Modif. benoit : rajout de la condition sur la fonction
					else if ((passedData2 != "") && (passedData[0] != 1))  
					{
						// 24/04/2007 - Modif. benoit : on verifie d'abord qu'il n'existe pas déja une condition sur le 3eme axe

						var condition_3eme_axe	= false;
						var condition_hidden	= "";

						for (var i=0;i<num_case;i++)
						{
							condition_hidden = document.getElementById("condition_hidden_"+i).value;
							condition_hidden = condition_hidden.split(':');

							if (condition_hidden[0] == 8) condition_3eme_axe = true;
						}

						if (condition_3eme_axe)
						{
							alert('Only one third axis condition available');
						}
						else
						{					
							// 24/04/2007 - Modif. benoit : reformatage de la condition sur le 3eme axe lors du drag&drop

							var condition_value = passedData[3];
							if(passedData[5].indexOf('undefined') == -1) condition_value = passedData[5];

							// 07/09/2010 NSE DE Firefox : on diffère l'écriture
							setDroppedFieldValue('condition_'+num_case,condition_value);
							// 23/05/2007 - Modif. benoit : on decode la valeur de la na 3eme axe (la valeur est encodée pour éviter les erreurs sur les separateurs de champs)
						
							document.getElementById("value_condition_"+num_case).value = decodeURIComponent(passedData[2]);
						
							document.getElementById("condition_hidden_"+num_case).value=passedData2;			// on passe les informations					
							document.getElementById("value_condition_"+num_case).disabled=true;
							document.getElementById("op_condition_"+num_case).value="=";
							document.getElementById("op_condition_"+num_case).disabled=true;	// on autorise la modification du menu déroulant
						}
					} 
					else
					{
						alert("Function can not  be part of a condition.");
						event.returnValue = false;				
					}
				}
			}	
		}
	}
	if(drag)
		event.returnValue = false;
}
  
/**
 * Supprime une condition
 * 13/10/2010 OJT : Réorganisation de la fonction et correction du bz18410
 */
function DelRow_condition()
{
    var num_ligne = parseInt( document.form_requete.nb_row_condition.value );
    if( num_ligne > 1 ) // On verifie que la ligne n' est pas la derniere
	{
        $( "table_condition").deleteRow( num_ligne - 1 );
        document.form_requete.nb_row_condition.value = num_ligne - 1;
	}
    else
	{
        // Si la ligne est la derniere on ne la suprrime pas mais on reinitialise tous les elements.
        $( "value_condition_0" ).value = "";
        $( "op_condition_0" ).value = "=";
        $( "condition_hidden_0" ).value = "";
        $( "condition_0" ).value = "";
	} 
}  



/**
 * Fonction qui ajoute une ligne condition
 * 13/10/2010 OJT : Réorganisation de la fonction et correction du bz18410
 */
function AddRow_condition()
{
    var row_value = document.form_requete.nb_row_condition.value;
    var ligne_encours =  parseInt( row_value ) - 1; // Calcul de la ligne encours

    // On vérifie que la dérniere condition est entrierement remplie
    if( ( $( 'condition_hidden_'+ ligne_encours ).value != "" ) && ( $( 'value_condition_'+ ligne_encours ).value != "" ) )
	{	
        if ( row_value >= 5 ){
            // Limitation à 5 conditions
            alert( 'No more selection available' );
            return false;
        }

        // Creation des trois champs composant la nouvelle condition ( la zone dudrag and drop,de l'operateur et de la valeur saisie par l'utilisteur)
        // 20/08/2010 NSE DE Firefox bz17383 : ajout de event
        c_condition = '<div align="center"><input name="condition[]" id="condition_'+row_value+'" class="br_caption" type="text" onFocus="saisie_disabled();" OnDrop="HandleDrop(event,\''+row_value+'\',\'condition\');"  size="12"><input id="condition_hidden_'+row_value+'" type="hidden" name="condition_hidden[]" ></div>';
        c_op = '<div align="center"><select name="op_condition[]" class="br_caption" id="op_condition_'+row_value+'"><option value="=">=</option><option value="&gt;">&gt;</option><option value="&gt;=">&gt;=</option><option value="&lt;">&lt;</option><option value="&lt;=">&lt;=</option><option value="&lt;&gt;">&lt;&gt;</option></optio/selectect></div>';
        c_value = '<div align="center"><input id="value_condition_'+row_value+'" class="br_caption" type="text" OnDrop="alert(\'Only figures can be captured\');return false;" name="value_condition[]" size="12"></div>';

        // On ajoute les 3 champs a la page (19/10/2010 OJT : Reopen 18410 utilisation de innerHTML)
        $( "table_condition" ).insertRow( row_value ).insertCell( 0 ).innerHTML = c_condition;
        $( "table_condition" ).rows[row_value].insertCell( 1 ).innerHTML = c_op;
        $( "table_condition" ).rows[row_value].insertCell( 2 ).innerHTML = c_value;
        document.form_requete.nb_row_condition.value = ++row_value; // On  augmente le nombre de condition
        return true;
	}
    alert( 'Complete all field to add a new condition' );
    return false;
}	

//		fonction qui efface une case du champ select et décale toutes les autres cases
function Erase(num_case,nbr_case)
{
	nbr_donnee=document.getElementById("nbr_donnees").value;
	if(document.getElementById("donnees_hidden_"+num_case).value)
	{
        //on décale toutes les cases à partir de la case à supprimer
		for(i=eval(num_case),j=eval(num_case+1);i<eval(nbr_case);i++,j++)		
		{
			document.getElementById("donnees_hidden_"+i).value=document.getElementById("donnees_hidden_"+j).value;
			document.getElementById("donnees_"+i).value=document.getElementById("donnees_"+j).value
			document.getElementById("fonction_"+i).value=document.getElementById("fonction_"+j).value	
		}
		// on efface apres la derniere case 
		document.getElementById("donnees_hidden_"+nbr_case).value="";
		document.getElementById("donnees_"+nbr_case).value="";
		document.getElementById("fonction_"+nbr_case).value="";
	
		// on met a jour le nouveau nombre de données
		c=eval(nbr_donnee)-1;
		document.getElementById("nbr_donnees").value=c;
	}
}

//		fonction servant au réaffichage d'une condition ( opérateur et valeur saisi par l'utilisateur )
// 20/08/2010 NSE DE Firefox bz17383 : ajout de event
function Ajouter_condition(event,num_case,code_condition,condition,operateur,condition2)
{
	document.getElementById("value_condition_"+num_case).value=condition2;
	document.getElementById("op_condition_"+num_case).value=operateur;
	HandleDrop(event,num_case,code_condition,condition);
	if (eval(num_case)!=4)
		AddRow_condition(0);
}

//		fonction servant au reaffichage du menu deroulant "sort"
function Modif_sort(new_sort)
{
	document.getElementById("param_sort").value=new_sort;
}

//	fonction servant au reaffichage du menu deroulant "limit"
function Modif_limit(new_limit)
{
	document.getElementById("param_limit").value=new_limit;
}

//	fonction servant a modifier la page cible du formulaire lors de ca validation . Si on le valid en cliquand sur  "save" la page cible reste la page de creation de requete ( page cible par default du formulaire.Si le formulaire est validé par "display result" , cette fonction est appelée et la pga cible du formulaire est positionnée sur la page d'affichage du tableau de résultat
function val()
{
	document.getElementById("onglet").value="1";
}

function drop_query()
{
	document.getElementById("onglet").value="0";
}

function verif_year(year)
{
date = new Date()
annee=date.getFullYear();
if(2003<=eval(year))
	if(annee>=eval(year))
		return true;
return false;
}

function verif_month(month)
{
if(1<=month)
	if(12>=month)
		return true;
return false;
}

function verif_week(week)
{
if(0<eval(week))
	if(52>=eval(week))
		return true;
return false;
}

function verif_day(day)
{
if(0<eval(day))
	if(31>=eval(day))
		return true;
return false;
}

function verif_hour(hour)
{
temp_hour=hour.substring(hour.length-2, hour.length); 
if(0<=eval(temp_hour))
	if(25>eval(temp_hour))
		return true;
return false;
}

// 2011/02/11 NSE DE Query Builder : gestion de la BH (ajout de la vérification pour les xxx_bh)
function verif_time_value(tmp,i,current_day,current_week)
{
	date = new Date()
	annee=date.getFullYear();
	mois=date.getMonth();
	current_month=current_day.slice(0,6)

	time=document.getElementById("value_condition_"+i).value;
    if(tmp[3]!="hour" && tmp[3]!="hour_bh")
	{
		year=time.slice(0,4);
		if(!verif_year(year))
			return false;
	}
    if(tmp[3]=="month" || tmp[3]=="month_bh")
	{
		if(time.length!=6)
			return false;
		month=time.slice(4,6);
		if(!verif_month(month))
			return false;
		if(time>current_month)
		{
			return false;
		}
	}
    if(tmp[3]=="week" || tmp[3]=="week_bh")
	{
		if(time.length!=6)
		{
			return false;
		}
		week=time.slice(4,6);
		if(!verif_week(week))
			return false;
		if(time>current_week)
			return false
	}
    if(tmp[3]=="day" || tmp[3]=="day_bh")
	{
		if(time.length!=8)
			return false;
		month=time.slice(4,6);
		if(!verif_month(month))
			return false;
		day=time.slice(6,8);
		if(!verif_day(day))
			return false;
		if(time>current_day)
		{
			return false;
		}
	}
	if(tmp[3]=="hour")
	{
		if(time.length!=10)
			return false;
		hour=time;
		if(!verif_hour(hour))
			return false;
	}

return true;
}

//	fonction controlant la validité des inormations saisies dans le formulaire
function Validation(day,week)
{
	if(document.getElementById("onglet").value=="0")
		return confirm("Delete the current query?");

	if (document.getElementById("nbr_donnees").value=="0")		//verifie qu'au moin une donnée soit séléctionnée
	{
		// 11/02/2011 NSE DE Query Builder : modification du message
    	alert("At least one Network Aggregation, one Time Aggregation and one raw counter/KPI must be dragged and dropped from the left hand-side lists.");
		return false;
	}
	datatype_present=0;
	time_present=0;
	network_present=0;
	for(i=0;i<document.getElementById("nbr_donnees").value;i++)	//compte le nombre de data_type,time et network agregation  present dans les données séléctionnées
	{
		tmp=document.getElementById("donnees_hidden_"+i).value;
		//alert(tmp);
		tmp=tmp.split(":");
		tmp=tmp[0];
		if((tmp==4)||(tmp==6))
			datatype_present=1;
		if(tmp==3)
			time_present=1;
		if(tmp==2)
			network_present=1;
	}
	for(i=0;i<document.form_requete.nb_row_condition.value;i++)	//compte le nombre de data_type,time et network agregation  present dans les condition séléctionnées
	{
		document.getElementById("value_condition_"+i).disabled=false;
		document.getElementById("op_condition_"+i).disabled=false;
		tmp=document.getElementById("condition_hidden_"+i).value;
		tmp1=tmp.split(":");
		tmp=tmp1[0];
		if(tmp==3){
            // 11/02/2011 NSE DE Query Builder : message sur la date précisant le bon format à utiliser
			if(!verif_time_value(tmp1,i,day,week)){
				var messdate = '';
                if(tmp1[3]=='hour')
                    messdate = 'Right format is YYYYMMDDHH.';
                else if(tmp1[3]=='day' || tmp1[3]=='day_bh')
                    messdate = 'Right format is YYYYMMDD.';
                else if(tmp1[3]=='week' || tmp1[3]=='week_bh')
                    messdate = 'Right format is YYYYWN with WN = Week number.';
                else if(tmp1[3]=='month' || tmp1[3]=='month_bh')
                    messdate = 'Right format is YYYYMM.';
                alert(document.getElementById("value_condition_"+i).value+" is not a valid "+tmp1[3]+". "+messdate);
				return false;
			}
			time_present=1;
		}
		if((tmp==2)||(tmp==7))
			network_present=1;
	}

    if(datatype_present==0)	//vérifie qu'au moins un datatype est séléctionné
	{
		// 11/02/2011 NSE DE Query Builder : modification du message
        alert("At least one raw counter or KPI must be dragged and dropped from the Data Type list.");
		return false;
	}
    if(time_present==0)	//vérifie qu'au moins un NA est séléctionné
	{
		// 11/02/2011 NSE DE Query Builder : modification du message
        alert("At least one Time Aggregation must be dragged and dropped from the Time Aggregation list.");
		return false;
	}
    if(network_present==0)	//vérifie qu'au moins un TA est séléctionné
	{
	// 11/02/2011 NSE DE Query Builder : modification du message
        alert("At least one Network Aggregation must be dragged and dropped from the Network Aggregation list.");
		return false;
	}
	//on verifie ensuite que la derniere condition soit bien remplie ( toutes les autres conditions le sont car il y a vérification avant l' ajout d'une condition
	var dernier_condition=document.getElementById("nb_row_condition").value;
	dernier_condition=eval(dernier_condition)-1;
	if( ( (document.getElementById("value_condition_"+dernier_condition).value)
        && (!(document.getElementById("condition_"+dernier_condition).value)))
        || ((document.getElementById("condition_"+dernier_condition).value)
        	&& (!(document.getElementById("value_condition_"+dernier_condition).value))) )
	{
		alert ("The last condition have to be completed");
		return  false;
	}
	if( ( !(document.getElementById("value_condition_"+dernier_condition).value))
        && (!(document.getElementById("condition_"+dernier_condition).value)))
	{
		DelRow_condition();
	}
	
	document.getElementById("show_onglet").value=1;
	return true;
}

function disable_drop()
{
document.getElementById("drop").disabled=true;
}
 //	efface tout le contenu de la page ( toutes les variables hidden sont aussi effacé)
function ClearAll() 
{
page.reload();
}

// réaffiche le message concernant les erreurs rencontrées  
 function ontop(error,hauteur)
 {
var url='builder_report_error.php?ERROR='+error;
win=ouvrir_fenetre(url,'Error','no','no',520,hauteur);
 }
 

//		Ce script recharge_page() vous permet de recharger la page en cours
//		 avec des paramètres "POST" du style : mapage.ext?param=valeur 
//			http://www.aidejavascript.com/article46.html

function recharge_page() {
  var query = location.search.substring(1);
  if (arguments.length == 1) query = change_query(query, arguments[0]);
  else {
    for (var i=0;i<arguments.length;i++) query = change_query(query, arguments[i]);
  }
  location.href = location.pathname + (query ? "?" + query : "");
 }

 function change_query(query, param) {
  // découpe param "variable=valeur" en variable et valeur
  var pos = param.indexOf("=");
  if (pos == -1) {
    var variable = param;
    var valeur = "";
  }
  else {
    var variable = param.substring(0, pos+1); // "variable="
    if (pos == param.length-1) var valeur = "";
    else var valeur = param.substring(pos+1); // "valeur"
  }
  if (variable == "*") query = "";
  // si on a déjà des paramètres
  else if (query) {
    // la variable n'est pas trouvée dans la chaîne query : on rajoute param au query
    if (query.indexOf(variable) == -1) query += valeur ? "&" + param : "";
    // sinon, il se peut qu'elle y ait, mais on peut avoir aussi "id_page=" alors qu'on cherche "page="
    else {
      var params = query.split("&");
      var num_param = ordre_param(params, variable.substring(0, variable.length-1));
      // si le paramètre n'existe pas déjà dans le query, on le rajoute à la fin
      if (num_param == -1) query += valeur ? "&" + param : "";
      // sinon on le change ou on le supprime (si valeur est vide)
      else {
        if (valeur) params[num_param] = param;
        else params.splice(num_param, 1);
        query = params.length ? params.join("&") : "";
      }
    }
  }
  // on n'a pas de paramètre actuellement, le query = le param
  else if (valeur) query = param;
  return query;
 }

function ordre_param(params, variable) {
  var i = 0;
  while (i<params.length) {
    var elts_param = params[i].split("=");
    if (elts_param[0] == variable) break;
    else i++;
  }
  if (i == params.length) return -1;
  else return i;
}
 
//		fin	http://www.aidejavascript.com/article46.html 
 
// 11/02/2011 NSE DE Query Builder : affichage/masquage du message sur l'affichage des résultats
// en paramètre l'objet select et le nombre limite de résultats pour l'affichage du message
function change_limit(select,limite){
    if(select.options[select.selectedIndex].value>limite)
        document.getElementById('alert_limit').style.display='block';
    else
        document.getElementById('alert_limit').style.display='none';
}
