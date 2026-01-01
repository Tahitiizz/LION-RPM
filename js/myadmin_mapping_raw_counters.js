//fonction qui vérifie les information dans la table de correspondance
//règle 1 : si aucun  champ côté provider, on ne sauvegarde rien
//règle 2 : si un champ easyoptima est vide alors qu'un champ provider contient un nom, on affiche un message et on ne sauvegarde pas
function verification_table_correspondance_data()
         {
              compteur=formulaire.raw_data_counter.value;
              //parcoure la table de correspondance
              for (i=0;i<=compteur;i++)
                  {
                   nom_champ_omc=eval("formulaire.omc_data_"+i+".value");
                   nom_champ_easyoptima=eval("formulaire.easyoptima_data_"+i+".value");
                   //si le contenu du champ est non vide
                   if ((nom_champ_omc!="" && nom_champ_easyoptima=="") || (nom_champ_omc=="" && nom_champ_easyoptima!=""))
                      {
                       nom_champ_easyoptima=eval("formulaire.easyoptima_data_"+i+".value");
                       if (nom_champ_easyoptima=="")
                          {
                           alert("Some fields should be filled in");
                           return false;
                          }
                      }
                  }
        }



//fonction qui copie la valeur dans l'OMC vers le champ Easyoptima
function copy_value(numero)
         {
          valeur_champ_omc=eval("top.table_correspondance.formulaire.omc_data_"+numero+".value");
          if (valeur_champ_omc!="")
             {
              eval("top.table_correspondance.formulaire.easyoptima_data_"+numero+".value="+'valeur_champ_omc');
              eval("top.table_correspondance.formulaire.aggregated_"+numero+".checked=true");			  
             }
             else
             {
              alert('The field is empty - You can not copy it');
             }
         }

//fonction qui selectionne un champ de l'OMC pour l'afficher dans la table de correspondance
function select_omc_field(field_number)
         {
          //récupère le nombre de champ qui sont affichés dans la table de correspoondance
          compteur=top.table_correspondance.formulaire.raw_data_counter.value;
          //récupère les valeurs des champs cachés qui correspondant à la valeur sur laquelle a cliqué l'utilisateur
          nom_champ_omc=eval("field_name_omc_"+field_number+".value");
          nom_table_omc=eval("table_name_omc_"+field_number+".value");
          nom_champ_easyoptima=eval("field_name_easyoptima_"+field_number+".value");
          code_type_sql_formule=eval("code_type_sql_formule_"+field_number+".value");
		  code_aggregated_flag=eval("code_aggregated_flag_"+field_number+".value");
          //parcoure la table de correspondance pour identifier les champs vides
          flag_rempli=0;
          for (i=0;i<=compteur;i++)
              {
               valeur_champ=eval("top.table_correspondance.formulaire.omc_data_"+i+".value");
               //si on trouve un champ vide alors on le complète
               if (valeur_champ=="" && flag_rempli!=1)
                  {
                   eval("top.table_correspondance.formulaire.omc_table_"+i+".value="+'nom_table_omc');
                   eval("top.table_correspondance.formulaire.omc_data_"+i+".value="+'nom_champ_omc');
                   eval("top.table_correspondance.formulaire.easyoptima_data_"+i+".value="+'nom_champ_easyoptima');
                   eval("top.table_correspondance.formulaire.type_formule_"+i+".selectedIndex="+'code_type_sql_formule');
                   eval("top.table_correspondance.formulaire.aggregated_"+i+".checked="+'code_aggregated_flag');
                   flag_rempli=1;
                  }
              }
         }

//fonction qui ajoute une ligne dans le tableau de correspondance entre les row_data et la donnée dans Easyoptima
function add_row_data(niveau4_vers_images, nombre_lignes_max)
         {
          var contenu=new Array(3);
          var table=document.all.raw_data_selection;
          var raw_value=formulaire.raw_data_counter.value;
          var type_sql_formule= new Array(5);
          type_sql_formule[0]="SUM";
          type_sql_formule[1]="AVG";
          type_sql_formule[2]="MAX";
          type_sql_formule[3]="MIN";
          type_sql_formule[4]="NONE";

          row_value++;

          //on teste s'il y a plus de 8 éléments ajoutés
          if (raw_value>nombre_lignes_max)
             {
              alert('No more Raw Data - Please save the current ones');
              return false;
             }

          contenu[0]='<center><a href="javascript:counter_reset('+row_value+')"><img align="absmiddle" src="'+niveau4_vers_images+'drop.gif" border="0"></a>&nbsp;&nbsp;';
          contenu[0]=contenu[0]+'<input class="iform" type="text" name="omc_data_'+row_value+'" size="22" onfocus="alert(\'this data can not be modified\');formulaire.submit.focus();">&nbsp;';
          contenu[0]=contenu[0]+'<select class="iform" name="type_formule_'+row_value+'"><option value="'+type_sql_formule[0]+'">'+type_sql_formule[0]+'</option><option value="'+type_sql_formule[1]+'">'+type_sql_formule[1]+'</option><option value="'+type_sql_formule[2]+'">'+type_sql_formule[2]+'</option><option value="'+type_sql_formule[3]+'">'+type_sql_formule[3]+'</option><option value="'+type_sql_formule[4]+'">'+type_sql_formule[4]+'</option></select>';
          contenu[0]=contenu[0]+'<input type="hidden" name="omc_table_'+row_value+'"></center>';
          contenu[1]='<center><input onclick="copy_value('+row_value+')" type="button" name="copy_'+row_value+'" size="2" value=">>"></center>';
          contenu[2]='<center><input class="iform" type="text" name="easyoptima_data_'+row_value+'" size="21">&nbsp';
          contenu[2]=contenu[2]+'<input type="checkbox" name="aggregated_'+row_value+'"></center>';

          formulaire.row_data_counter.value=row_value;
          //insère une nouvelle ligne contenant 13 éléments
          var new_row=table.insertRow(2);
          for (var i = 0; i < 3; i++) {
               var new_cell = new_row.insertCell(i)
               new_cell.innerHTML=contenu[i];
              }
         }

//change la couleur  de la font
function change_color(numero_onglet,id_group_table)
         {
          var table = document.getElementById("group_table_list");
          nombre_colonnes=table.rows[0].cells.length;

          for (i=0;i<nombre_colonnes;i=i+2) //fait des pas de 2 car entre les onglets, il y a des colonnes
              {
               ongleti=document.getElementById("onglet"+i);
               if (i==numero_onglet)
                  {
                   ongleti.style.color='red'; //change la couleur de fond
                   ongleti.style.fontWeight='bold'; //change la couleur de fond
                  }
                  else
                  {
                   ongleti.style.color='#333333'; //change la couleur de fond
                   ongleti.style.fontWeight='bold'; //change la couleur de fond
                   ongleti.style.fontSize='12px';
                  }
              }
          //charge les pages dans les 2 iframe de gauche et de droite
          window.provider.location="mapping_raw_counters_external.php?id_group_table="+id_group_table;
          window.easyoptima_counter.location="mapping_raw_counters_internal.php?id_group_table="+id_group_table;
          window.table_correspondance.location="mapping_raw_counters_correspondance_table.php?id_group_table="+id_group_table;
         }

//function qui recupère toute les éléments de la liste déroulante "Choix des tables"
// En effet, PHP ne permet pas d'avoir tous ces éléments
//on concatène les données dans un champ "input" pour les récupérer en PHP
function collect_table_list()
         {
          //teste si l'utilisateur a choisi une connection dans la liste déroulante
          if (formulaire.choix_connection.value=='#')
             {
              alert("you must select a connection to a server");
              return false;
             }
          nombre_valeur= formulaire.choix_table.length;
          if (nombre_valeur>0) //teste si la table contenant la liste des tables sélectionnées est vide
             {
              liste_element='';
              for (i=0;i<nombre_valeur;i++)
                  {
                   valeur_element=formulaire.choix_table.options[i].text;
                   liste_element=liste_element+valeur_element+",";
                  }
              formulaire.transfert_liste_choix.value=liste_element;
             }
             else
             {
              alert("You must at least select 1 table");
              return false;
             }
          return true;
         }

//affiche la description de la table sur laquelle l'utilisateur a double cliqué
function show_table_description(nom_connection_odbc)
         {
          nom_table=formulaire.liste_table.options[formulaire.liste_table.selectedIndex].text;
          window.open('intra_myadmin_nms_counter_table_describe.php?nom_connection_odbc='+nom_connection_odbc+'&nom_table='+nom_table,'describe',"height=500,width=500,scrollbars=1");
         }

//fonction qui supprime dans la liste un élément et l'ajoute dans la liste de choix
function add_list_table()
         {
          if (formulaire.liste_table.selectedIndex!=-1) //vaut -1 si aucun choix dans le menu n'a été effectué
             {
              valeur_choisie=formulaire.liste_table.options[formulaire.liste_table.selectedIndex].text;
              nombre_valeur= formulaire.choix_table.length;
              //boucle sur toutes les valeurs de la liste pour vérifier si la valeur qui doit être passée dans l'autre table n'est pas déjà présente
              for (i=0;i<nombre_valeur;i++)
                  {
                   valeur_element=formulaire.choix_table.options[i].text;
                   if (valeur_choisie==valeur_element)
                      {
                       alert('Table already in the List');
                       return false;
                       break;
                      }
                  }
              formulaire.choix_table.options[formulaire.choix_table.length]=new Option(valeur_choisie,valeur_choisie);
              formulaire.liste_table.options[formulaire.liste_table.selectedIndex]=null;
             }
             else
             {
              alert('You must select an element in the List of Tables');
             }
         }

//fonction qui supprime un élément dans la liste des choix et le remet dans la liste initiale
function remove_list_choice()
         {
          if (formulaire.choix_table.selectedIndex!=-1) //vaut -1 si aucun choix dans le menu n'a été effectué
             {
              valeur_choisie=formulaire.choix_table.options[formulaire.choix_table.selectedIndex].text;
              nombre_valeur= formulaire.liste_table.length;
              //boucle sur toutes les valeurs de la liste pour vérifier si la valeur qui doit être passée dans l'autre table n'est pas déjà présente
              flag_table_presente=0;
              for (i=0;i<nombre_valeur;i++)
                  {
                   valeur_element=formulaire.liste_table.options[i].text;
                   if (valeur_choisie==valeur_element)
                      {
                       flag_table_presente=1;
                       break;
                      }
                  }

              //si la table n'est pas présente dans la table destination, alors on la crée dans la table destination
              if (flag_table_presente==0)
                 {
                  formulaire.liste_table.options[formulaire.liste_table.length]=new Option(valeur_choisie,valeur_choisie);
                 }
              formulaire.choix_table.options[formulaire.choix_table.selectedIndex]=null;
             }
             else
             {
              alert('You must select an element in the List of Selected Tables');
             }
         }

//fonction qui ajoute une ligne dans un tableau pour saisir les éléments de connection à une BDD
function add_connection_parameter()
         {
          var contenu=new Array(12);
          var table=document.all.connection_parameter;
          var row_value=formulaire.row_table.value;

          row_value++;

          //on teste s'il y a plus de 8 éléments ajoutés
          if (row_value==8)
             {
              alert('No more Parameters to be added');
              return false;
             }

          contenu[0]='<input type="hidden" name="id_connection'+row_value+'"><font class="font_12">Name</font>';
          contenu[1]='<input class="iform" type="text" name="connection_name'+row_value+'">';
          contenu[2]='<font class="font_12" face="arial" size="2">IP</font>';
          contenu[3]='<input class="iform" type="text" name="ip'+row_value+'" size="15">';
          contenu[4]='<font class="font_12" face="arial" size="2">Database/Path</font>';
          contenu[5]='<input class="iform" type="text" name="path'+row_value+'" size="30">';
          contenu[6]='<font class="font_12">Flat_file</font>';
          contenu[7]='<input class="iform" type="checkbox" name="flat_file'+row_value+'">';
          contenu[8]='<font class="font_12">Login</font>';
          contenu[9]='<input class="iform" type="text" name="login'+row_value+'" size="10">';
          contenu[10]='<font class="font_12">Password</font>';
          contenu[11]='<input type="password" class="iform" type="text" name="password'+row_value+'" size="10">';
//          contenu[10]='<font class="font_12">Group Table</font>';
//          contenu[11]='<input class="iform" type="text" name="daily_table'+row_value+'" size="15">';

          formulaire.row_table.value=row_value;
          //insère une nouvelle ligne contenant 13 éléments
          var new_row=table.insertRow(1);
          for (var i = 0; i < 12; i++) {
               var new_cell = new_row.insertCell(i)
               new_cell.innerHTML=contenu[i];
              }
         }

//fonction qui demande confirmation de la suppression d'une connection
function connection_delete(id_connection)
         {
          reponse=confirm('Delete this Connection ?\nAll Selected tables and counters will be deleted !');
          if (reponse)
             {
              window.location="../traitement/intra_myadmin_nms_counter_connection_parameters_management.php?id_connection="+id_connection+"&action=delete";
             }
             else
             {
              alert('No connection deleted');
             }
         }

//fonction qui supprime les données présente dans une ligne de la table de correspondance
function counter_reset(numero_ligne)
         {
          eval("formulaire.omc_data_"+numero_ligne+".value=\"\"");
          eval("formulaire.easyoptima_data_"+numero_ligne+".value=\"\"");
          eval("formulaire.type_formule_"+numero_ligne+".selectedIndex=0");
//          eval("formulaire.type_field_"+numero_ligne+".selectedIndex=0");
         }
		 
//fonction qui gère la suppression des compteurs
function counter_delete(id_field,id_group_table)
         {
          reponse=confirm('Delete this Counter ?\nThis counter will not be anymore retrieved\nIt may have an impact on the reports');
          if (reponse)
             {
              window.location="../traitement/mapping_raw_counters_counter_delete.php?id_field="+id_field+"&id_group_table="+id_group_table;
             }
             else
             {
              alert('No counter deleted');
             }
         }		 