/*
 * @cb50400
 *
 * 16/08/2010 NSE DE Firefox bz 16921 : remplacement formulaire par document.formulaire
 *
 */

function collect_table_list(traitement,family,product)
	{
	nombre_valeur= document.formulaire.choix_data.length;
	if (nombre_valeur>0) //teste si la table contenant la liste des tables sélectionnées est vide
		{
		liste_element='';
		for (i=0;i<nombre_valeur;i++)
			{
			valeur_element=document.formulaire.choix_data.options[i].value;
			if(liste_element!='')
				{
				liste_element=liste_element+","+valeur_element;
				}
			else
				{
				liste_element=valeur_element;
				}
			}
		document.getElementById("transfert_liste_choix").value=encodeURIComponent(liste_element);
		}
	else
		{
		alert("You must at least select 1 Element");
		return false;
		}
	return validation(traitement,family,product);
	}

// 16/08/2010 NSE DE Firefox bz 16921 : remplacement formulaire par document.formulaire
//fonction qui supprime dans la liste un élément et l'ajoute dans la liste de choix
function add_list_table()
         {
          if (document.formulaire.liste_data.selectedIndex!=-1) //vaut -1 si aucun choix dans le menu n'a été effectué
             {
              valeur_choisie=document.formulaire.liste_data.options[document.formulaire.liste_data.selectedIndex].text;

              id_valeur_choisie=document.formulaire.liste_data.options[document.formulaire.liste_data.selectedIndex].value;
              nombre_valeur= document.formulaire.choix_data.length;
              //boucle sur toutes les valeurs de la liste pour vérifier si la valeur qui doit être passée dans l'autre table n'est pas déjà présente
              for (i=0;i<nombre_valeur;i++)
                  {
                   valeur_element=document.formulaire.choix_data.options[i].value;
                   if (id_valeur_choisie==valeur_element)
                      {
                       alert('Data already in the List');
                       return false;
                       break;
                      }
                  }

              document.formulaire.choix_data.options[document.formulaire.choix_data.length]=new Option(valeur_choisie,id_valeur_choisie);
              document.formulaire.liste_data.options[document.formulaire.liste_data.selectedIndex]=null;
             }
             else
             {
              alert('You must select an element in the List of Data');
             }
         }

//fonction qui supprime un élément dans la liste des choix et le remet dans la liste initiale
function remove_list_choice()
{
          if (document.formulaire.choix_data.selectedIndex!=-1) //vaut -1 si aucun choix dans le menu n'a été effectué
             {
              valeur_choisie=document.formulaire.choix_data.options[document.formulaire.choix_data.selectedIndex].text;
              nombre_valeur= document.formulaire.liste_data.length;
              //boucle sur toutes les valeurs de la liste pour vérifier si la valeur qui doit être passée dans l'autre table n'est pas déjà présente
              flag_data_presente=0;
              for (i=0;i<nombre_valeur;i++)
                  {
                   valeur_element=document.formulaire.liste_data.options[i].text;
                   if (valeur_choisie==valeur_element)
                      {
                       flag_data_presente=1;
                       break;
                      }
                  }

              //si la table n'est pas présente dans la table destination, alors on la crée dans la table destination
              if (flag_data_presente==0)
                 {
                  document.formulaire.liste_data.options[document.formulaire.liste_data.length]=new Option(valeur_choisie,valeur_choisie);
                 }
              document.formulaire.choix_data.options[document.formulaire.choix_data.selectedIndex]=null;
             }
             else
             {
              alert('You must select an element in the List of Selected Data');
             }
}

function validation(traitement,family,product)
{

if(traitement=="drop")
	{
	if(document.getElementById("id_network_agregation").value)
		{
		url="my_agregation_list.php?family="+family+"&product="+product+"&id_agregation_to_drop="+document.getElementById("id_network_agregation").value;
		parent.frames['network_agregation_liste'].location=url;
		self.location="my_aggregation_cell_selection.php?family="+family+"&product="+product;
		}
	else
		{
		self.location="my_aggregation_cell_selection.php?family="+family+"&product="+product;
		}
	}
if(traitement=="save")
	{
	
	url="my_agregation_list.php?family="+family+"&product="+product+"&name_agregation_to_add="+document.getElementById("agregation_name").value+" & cell_list="+document.getElementById("transfert_liste_choix").value ;
	
	parent.frames['network_agregation_liste'].location=url;
	}
if(traitement=="modify")
	{
	url="my_agregation_list.php?family="+family+"&product="+product+"&id_quey_to_modify="+document.getElementById("id_network_agregation").value+"&name="+document.getElementById("agregation_name").value+" & cell_list="+document.getElementById("transfert_liste_choix").value ;
	parent.frames['network_agregation_liste'].location=url;
	}
return false;

}