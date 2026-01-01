//reset le formulaire mais conserve certains éléments
function reset_query()
         {
          //sauvegarde 3 champs cachés
          var row_table_selection=formulaire_sql.row_table_selection.value;
          var col_table_selection=formulaire_sql.col_table_selection.value;
          var row_table_condition=formulaire_sql.row_table_condition.value;
          //reset tout le formulaire
          formulaire_sql.reset();
          //recharge les champs cachés importants
          formulaire_sql.row_table_selection.value=row_table_selection;
          formulaire_sql.col_table_selection.value=col_table_selection;
          formulaire_sql.row_table_condition.value=row_table_condition;
         }

//verifie si la donnée qui va servir de condition est bien une valeur numérique
function verify_condition()
         {
          var elem = event.srcElement;
          if (isNaN(elem.value))
             {
              alert('You must enter a number');
              elem.value="";
             }
         }

//fonction qui empeche la saisie au clavier
function saisie_disabled() {
         alert('You can only Drag&Drop data');
         formulaire_sql.soumettre.focus();
         return false;
         }

//fonction qui drop une query existante
//afin de passer le paramètre id_query, il est néceesaire de passer par le javascript
function drop_query()
         {
         valeur_id_query=formulaire_sql.id_query.value;
		 if (valeur_id_query!="")
		 	{
		 	 if (confirm('Drop this query ?'))
		 		{
      		 	 top.contenu_database.location='intra_forum_builder_report_queries_database.php?numero_label=0';
				 window.location='../traitement/intra_forum_query_drop.php?id_query='+valeur_id_query;
				}
			}
			else
			{
			 alert('You must select a query !');
			}
         return false;
         }

//fonction qui drop une query existante
//afin de passer le paramètre id_query, il est néceesaire de passer par le javascript
function drop_formula(id_formula)
         {
		 if (id_formula!="")
		 	{
		 	 if (confirm('Drop this formula ?'))
		 		{
      		 	 window.opener.top.contenu_database.location='intra_forum_builder_report_queries_database.php?numero_label=2';
				 window.location='../traitement/intra_forum_builder_report_formula_management.php?action=drop&id_formula='+id_formula;
				}
			}
			else
			{
			 alert('You must select a formula !');
			}
         return false;
         }
		 
//fonction qui ouvre la fenêtre pour sauvegarder la query
//afin de passer le paramètre id_query, il est néceesaire de passer par le javascript
function save_query()
         {

         valeur_id_query=formulaire_sql.id_query.value;
         eval("ouvrir_fenetre('intra_forum_builder_report_query_save.php?id_query="+valeur_id_query+"','save_query','no','no',370,90);");
         return false;

         }

//fonction qui récupère les données du Drag et les positionne
// 06/09/2010 NSE DE Firefox bz 17383 : passage de event en paramètre
function HandleDrop(event,nom_element_table) {

        var elem = event.srcElement;
        var passedData = event.dataTransfer.getData("Text");
        if (passedData);
            {
             passedData = passedData.split(":");
             //teste si la première donnée est un chiffre auquel cas il ne faut pas dropper les données dans le champ
             if (isNaN(passedData[0]))
                {
                 //teste si les données sont issues de la base de données à gauche ou des fonctions SQl
                 if (passedData[1]!='fonction')
                    {
                     //contient uniquememnt le nom du champ
                     elem.innerText=passedData[0];
                     event.returnValue = false;
                     //contient l'élément nom_table.nom_champ pour etre exploité dans la requete sql
                     nom_element_table.value=passedData[1];
                    }
                    else
                    {
                     if (elem.value!="")
                        {
                         valeur_champ=passedData[0]+'('+elem.value+')';
                         elem.innerText=valeur_champ;
                         valeur_table=passedData[0]+'('+nom_element_table.value+')';
                         nom_element_table.value=valeur_table;
                         event.returnValue = false;
                        }
                        else
                        {
                         alert('A data must be in the current field');
                         event.returnValue = false;
                        }
                    }
                }
             event.returnValue = false;
            }
         }

 //fonction qui gère le Drag&Drop d'une équation depuis la frame de gauche de la page
 // 06/09/2010 NSE DE Firefox bz 17383 : passage de event en paramètre
 function HandleDrop_query(evt) {

        var elem = evt.srcElement;
          var passedData = evt.dataTransfer.getData("Text");
          var drag=formulaire_sql.drag.value;
          //l'ordre des mots à une importance puisque c'est l'ordre d'apparaition dans la requete SQL
          var liste_mot_cle=new Array(7);
          liste_mot_cle[0]="FROM";
          liste_mot_cle[1]="WHERE";
          liste_mot_cle[2]="GROUP BY";
          liste_mot_cle[3]="ORDER BY";
          liste_mot_cle[4]="ASC";
          liste_mot_cle[5]="DESC";
          liste_mot_cle[6]="LIMIT";

          var liste_operande=new Array(5);
          //l'odre à une importance donc ne pas la modifier
          liste_operande[0]=">=";
          liste_operande[1]="<=";
          liste_operande[2]=">";
          liste_operande[3]="<";
          liste_operande[4]="=";

          if (passedData);
             {
              passedData = passedData.split(":");
              //teste si les données sont issues de la base de données à gauche ou des fonctions SQl
             }
          //teste si la valeur 0 est bien numérique car cela correspond à l'id de la query
          if ((!isNaN(passedData[0])) && (drag==""))
             {
              //charge id_query dans un champ caché
              formulaire_sql.id_query.value=passedData[0];

              //Recherche les éléments de SELECT
              numero_index_suivant=SQL_explode(passedData[1],0,liste_mot_cle);
              champ_select=passedData[1].slice(7,numero_index_suivant-1);//SELECT finit au 6ème caractère en incluant l'espace, -1 pour supprimer l'espace
              //split les nom des champs en utilisant le séparateur ,
              liste_champ_select=champ_select.split(", ");
              for (i=0;i<liste_champ_select.length;i++)
                  {
                   eval("name_champ='select"+i+"'");
                   eval("name_table='table_select"+i+"'");
                   nom_champ_selection=liste_champ_select[i].split(".");
                   eval("formulaire_sql."+name_champ+".value=nom_champ_selection[1];");
                   eval("formulaire_sql."+name_table+".value=liste_champ_select[i];");
                   add_selection(event,'0');
                  }
              //comme le champ 0 existe déjà, il faut supprimer le dernier champ qui a été ajouté
     	  	  delete_selection();

              //ce champ mis à 1 permet d'arrêter l'exécution intempestive de cette fonction
              formulaire_sql.drag.value=1;

              //Recherche les éléments de FROM - En fait ne sert que pour connaitre l'index de FROM
              //le nom des tables sont déduits des éléments de SELECT
              //Il faut laisser ces 2 lignes car les éléments suivants dépendent du résultat ci-dessous
              numero_index_precedent=numero_index_suivant;
              numero_index_suivant=SQL_explode(passedData[1],1,liste_mot_cle);

              //Recherche les éléments de WHERE
              numero_index_precedent=numero_index_suivant;
              numero_index_suivant=SQL_explode(passedData[1],2,liste_mot_cle);
              champ_where=passedData[1].slice(numero_index_precedent+6,numero_index_suivant);//WHERE fait 5 caractères
              if (champ_where.charAt(champ_where.length-1)==" ") //teste si le dernier caractere est un blanc
			  	 {
				  champ_where=champ_where.substr(0,champ_where.length-1); //enleve le dernier caractere
				 }
			  liste_champ_where=champ_where.split(" AND "); //sépare les champs les uns des autres
              //parcoure la liste des champs where séparés par AND
			  t=0; //t sert pour afficher les conditions. T n'est incrémentée que si la condition soit être affichée
              for (i=0;i<liste_champ_where.length;i++)
                  {                
					//parcoure la liste de tous les opérandes
                    for (j=0;j<liste_operande.length;j++)
                        {
                         numero_index=liste_champ_where[i].indexOf(liste_operande[j]);
                         if (numero_index!=-1)
                            {
							 champ_condition=liste_champ_where[i].split(liste_operande[j]);
                             nom_du_champ=champ_condition[0].split(".");
							 							 
							 if (nom_du_champ[1]!="omc_index")
							    {						
								 eval("name_champ='condition"+t+"_0'");
                             	 eval("name_table='table_condition"+t+"_0'");
                             	 eval("comparateur='condition"+t+"_1'");
                             	 eval("valeur_comparaison='condition"+t+"_2'");
                             	 eval("formulaire_sql."+name_champ+".value=nom_du_champ[1];");
                             	 eval("formulaire_sql."+name_table+".value=champ_condition[0];");
                             	 eval("formulaire_sql."+comparateur+".value=liste_operande[j];");
                             	 eval("formulaire_sql."+valeur_comparaison+".value=champ_condition[1];");
                             	 add_condition(event,'0');
								 t++;
								}
                             break;
                            }
                        }
                 }
              //comme la condition 0 existe déjà, il faut supprimer le dernier champ qui a été ajouté dans le cas où une condition existe
              if (liste_champ_where.length>=1 && formulaire_sql.row_table_condition.value>=1)
                 {
				  delete_condition();
                 }

              //Recherche les éléments de GROUP BY
              numero_index_precedent=numero_index_suivant;
              numero_index_suivant=SQL_explode(passedData[1],3,liste_mot_cle);
              champ_group_by=passedData[1].slice(numero_index_precedent+9,numero_index_suivant-1);//GROUP BY fait 8 caractères, -1 pour supprimer l'espace
              if (champ_group_by!="")
                 {
                  nom_champ_uniquement=champ_group_by.split("."); //split le champ et la table
                  formulaire_sql.group_by.value=nom_champ_uniquement[1] //nom du champ
                  formulaire_sql.group_by_table.value=champ_group_by;
                 }
              //Recherche les éléments de ORDER BY
              numero_index_precedent=numero_index_suivant;
              numero_index_suivant=SQL_explode(passedData[1],4,liste_mot_cle);
              champ_order_by=passedData[1].slice(numero_index_precedent+9,numero_index_suivant-1);//ORDER BY fait 8 caractères et -1 pour supprimer l'espace
              if (champ_order_by!="")
                 {
                  nom_champ_uniquement=champ_order_by.split("."); //split le champ et la table
                  formulaire_sql.order.value=nom_champ_uniquement[1]; //nom du champ
                  formulaire_sql.order_table.value=champ_order_by;    //nom complet dans champ cache : nom_table.nom_champ
                 }
              //Recherche les éléments ASC ou DESC
              numero_index=passedData[1].indexOf("ASC");
              if (numero_index!=-1)
                 {
                  formulaire_sql.classement.value="ASC";
                 }
                 else
                 {
                  formulaire_sql.classement.value="DESC";
                 }

              //Recherche les éléments LIMIT
              numero_index=passedData[1].indexOf("LIMIT");
              if (numero_index!=-1)
                 {
                  champ_limit=passedData[1].slice(numero_index+6,passedData[1].length); //LIMIT + un esapce fait 6 caractères
                  formulaire_sql.limit.value=champ_limit;
                 }
             }
          }
  //fonction qui permet de recherche les champs entre 2 mots_clés SQL

function SQL_explode(query_sql,numero_mot_cle,liste_mot_cle) {

         longueur_query=query_sql.length;
         for (i=numero_mot_cle;i<=6;i++)
             {
              numero_index=query_sql.indexOf(liste_mot_cle[i]);
              if (numero_index!=-1)
                {
                 break;
                }
                else
                {
                 numero_index=longueur_query;
                }
              }
         return numero_index;
         }



//fonction de drag&drop des champs dans les zones texte du formulaire
// 06/09/2010 NSE DE Firefox bz 17383 : passage de event en paramètre
function SetupDrag(event,nom_champ,nom_table) {
        var passedData = [nom_champ, nom_table+'.'+nom_champ];

        // store it as a string

        event.dataTransfer.setData("Text", passedData.join(":"));

        event.dataTransfer.effectAllowed = "copy";

        }



//fonction de drag&drop de la formule

function SetupDrag_formula(nom_formula, formula_equation) {

        var passedData = [nom_formula, formula_equation];

        // store it as a string

        event.dataTransfer.setData("Text", passedData.join(":"));

        event.dataTransfer.effectAllowed = "copy";

        }



//drag &drop de la query depuis la fenêtre de gauche

function SetupDrag_query(id_query,query) {

        var passedData = [id_query,query];

        var valeur_colonne_select=parseInt(top.contenu_equation_sql.formulaire_sql.col_table_selection.value);

        var valeur_ligne_select=parseInt(top.contenu_equation_sql.formulaire_sql.row_table_selection.value);

        var valeur_ligne_condition=parseInt(top.contenu_equation_sql.formulaire_sql.row_table_condition.value);

        nbre_champ_select=valeur_ligne_select*2+valeur_colonne_select;

        nbre_champ_condition=valeur_ligne_condition;

        for (i=0;i<nbre_champ_select;i++)

            {

             delete_selection();

            }

        for (i=0;i<nbre_champ_condition;i++)

            {

             delete_condition();

            }



        top.contenu_equation_sql.formulaire_sql.reset(); //re-initialise toutes les données du formulaire

        // store it as a string

        event.dataTransfer.setData("Text", passedData.join(":"));

        event.dataTransfer.effectAllowed = "copy";

        }





//drag &drop les fonctions SQL

function SetupDrag_fonction(nom_fonction) {

        var passedData = [nom_fonction,'fonction'];

        // store it as a string

        event.dataTransfer.setData("Text", passedData.join(":"));

        event.dataTransfer.effectAllowed = "copy";

        }


// 06/09/2010 NSE DE Firefox bz 17383 : passage de event en paramètre
function add_selection(event,flag_message)
         {
          var table=document.all.tableselect;
          //récupère les valeurs des champs cachés
          var row_value=formulaire_sql.row_table_selection.value;
          var col_value=formulaire_sql.col_table_selection.value;
          //on teste s'il y a plus de 6 éléments ajoutés
          if (row_value==2 && col_value==1 && flag_message==1)
             {
              alert('No more selection available');
              return false;
             }
          //insère un élément et éventuellement une nouvelle ligne
          if (col_value==0)
             {
             //calcule le numéro du champ en partant de 0 puis crée le nom du champ
              numero_champ=2*formulaire_sql.row_table_selection.value+1;
              eval("name_champ='select"+numero_champ+"'");
              eval("name_table='table_select"+numero_champ+"'");
              eval("contenu='<input class=iform type=text onFocus=saisie_disabled() OnDrop=HandleDrop(event,"+name_table+") name="+name_champ+" size=20><input type=hidden name="+name_table+" size=20>'");
              table.rows[row_value].insertCell(1).innerHTML=contenu;
              formulaire_sql.col_table_selection.value=1;
             }
             else
             {
             row_value++;
             formulaire_sql.row_table_selection.value=row_value;
             //calcule le numéro du champ en partant de 0 puis crée le nom du champ
             numero_champ=2*formulaire_sql.row_table_selection.value;
             eval("name_champ='select"+numero_champ+"'");
             eval("name_table='table_select"+numero_champ+"'");
             eval("contenu='<input class=iform type=text onFocus=saisie_disabled() OnDrop=HandleDrop("+name_table+") name="+name_champ+" size=20><input type=hidden name="+name_table+" size=20>'");
             formulaire_sql.col_table_selection.value=0;
             table.insertRow(row_value).insertCell(0).innerHTML =contenu;
             }
         }

//delete_selection est appelé notamment lorsqu'une equation est Dragé depuis la frame de gauche
//c'est pourquoi on fait appel à top.contenu_equation_sql.
function delete_selection()
         {

          table=top.contenu_equation_sql.document.all.tableselect;

          //récupère les valeurs des champs cachés

          row_value=top.contenu_equation_sql.formulaire_sql.row_table_selection.value;

          col_value=top.contenu_equation_sql.formulaire_sql.col_table_selection.value;

          //teste s'il y a quelquechose à effacer

          if (row_value==0 && col_value==0)

             {

              alert('No more selection to Delete');

              return false;

             }

          //efface le dernier élément ajouté

          table.rows[row_value].deleteCell(col_value);

          if (col_value==0)

             {

              row_value--;

              col_value=1;

             }

             else

             {

              col_value=0

             }

          //modifie les valeurs des champs cachés

          top.contenu_equation_sql.formulaire_sql.row_table_selection.value=row_value;

          top.contenu_equation_sql.formulaire_sql.col_table_selection.value=col_value;

         }


// 06/09/2010 NSE DE Firefox bz 17383 : passage de event en paramètre
function add_condition(event,flag_message) //$flag_message permet d'afficher ou non un message d'alerte

         {
          var contenu=new Array(3);
          var table=document.all.tablecondition;
          var row_value=formulaire_sql.row_table_condition.value;

          row_value++;
          //on teste s'il y a plus de 3 éléments ajoutés
          if (row_value==3 && flag_message==1)
             {
              alert('No more Condition available');
              return false;

             }

          eval("name_champ='condition"+row_value+"_0'");
          eval("name_table='table_condition"+row_value+"_0'");
          eval("comparateur='condition"+row_value+"_1'");
          eval("valeur_comparaison='condition"+row_value+"_2'");
          contenu[0]='<input class="iform" type="text" name="'+name_champ+'" size="20"  onFocus=saisie_disabled() OnDrop="HandleDrop(event,'+name_table+');"><input class="iform" type="hidden" name="'+name_table+'" size="20">';
          contenu[1]='<select name="'+comparateur+'" class="iform"><option value="=">=</option><option value="&gt;">&gt;</option><option value="&gt;=">&gt;=</option><option value="&lt;">&lt;</option><option value="&lt;=">&lt;=</option></select>';
          contenu[2]='<input class="iform" type="text" name="'+valeur_comparaison+'" size="8">';

          formulaire_sql.row_table_condition.value=row_value;
          //insère une nouvelle ligne contenant 3 éléments
          var new_row=table.insertRow(row_value);
          for (var i = 0; i < 3; i++) {
               var new_cell = new_row.insertCell(i)
               new_cell.innerHTML=contenu[i];
              }
         }

//delete_condition est appelé notamment lorsqu'une equation est Dragé depuis la frame de gauche
//c'est pourquoi on fait appel à top.contenu_equation_sql.
function delete_condition()

         {

          var table=top.contenu_equation_sql.document.all.tablecondition;

          var row_value=top.contenu_equation_sql.formulaire_sql.row_table_condition.value;

          if (row_value==0)

             {

              alert('No more Condition to delete');

              return false;

             }

          //efface la ligne

          table.deleteRow(row_value);

          row_value--;

          top.contenu_equation_sql.formulaire_sql.row_table_condition.value=row_value;

         }