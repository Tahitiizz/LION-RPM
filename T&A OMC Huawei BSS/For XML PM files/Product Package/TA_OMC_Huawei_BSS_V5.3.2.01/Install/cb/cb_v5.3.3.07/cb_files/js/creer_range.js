/*
 * - maj 16/04/2007 Gwénaël
 * 			>> ajout du pourcentage 0 dans la transparence du data range
 */

// Nombre de range ajoutée par le script javascript.
j = 0;
nb_range_max = 5;

function ajouterRange(nbRangeDefaut)
{
	j++;
	//if(j == nb_range_max){
		//alert("Adding range aborted (maximum 5 ranges)");
	//} else {
		i = nbRangeDefaut + j;	// Index de la partie à ajouter.
		
		texte = "";
		texte += "<div id=\"range"+i+"\">";
		texte += "<fieldset>";
		texte += "<legend class=\"texteGris\">&nbsp;<img src=\"../../../../images/icones/small_puce_fieldset.gif\"/>&nbsp;&nbsp;Range "+i+"&nbsp;</legend>";
		texte += "<table>";
		texte += " <tr>";
			texte += "<td colspan=\"2\">";
				texte += "<table cellpadding=\"0\" cellspacing=\"2\" align=\"left\">";
					texte += "<tr>";
						texte += "<td>";
							texte += "<table cellpadding=\"0\" cellspacing=\"2\" align=\"left\">";
								texte += "<tr>";
									texte += "<td class=\"texteGris\">Stroke color :</td>";
									texte += "<td>";
										texte += "<input type=\"button\" name=\"stroke_color_btn"+i+"\"  size=\"16\" style=\"background-color:#000000;\" ";
											texte += "class=\"hexfield\" onMouseOver=\"style.cursor='hand';\" ";
											texte += "onclick=\"javascript:ouvrir_fenetre('palette_couleurs_2.php?form_name=myForm&field_name=stroke_color_btn"+i+"&hidden_field_name=stroke_color"+i+"','Palette','no','no',304,100);\" />";
										texte += "<input type=\"hidden\" name=\"stroke_color"+i+"\" value=\"#000000\">";
									texte += "</td>";
								texte += "</tr>";
							texte += "</table>";
						texte += "</td>";
						texte += "<td>";
							texte += "<table cellpadding=\"0\" cellspacing=\"2\" align=\"left\">";
								texte += "<tr>";
									texte += "<td class=\"texteGris\">Fill color :</td>";
									texte += "<td>";
										texte += "<input type=\"button\" name=\"fill_color_btn"+i+"\"  size=\"16\" style=\"background-color:#FFFFFF;\" ";
											texte += "class=\"hexfield\" onMouseOver=\"style.cursor='hand';\" ";
											texte += "onclick=\"javascript:ouvrir_fenetre('palette_couleurs_2.php?form_name=myForm&field_name=fill_color_btn"+i+"&hidden_field_name=fill_color"+i+"','Palette','no','no',304,100);\" />";
										texte += "<input type=\"hidden\" name=\"fill_color"+i+"\" value=\"#FFFFFF\">";
									texte += "</td>";
									texte += "<td class=\"texteGrisPetit\">";
										texte += "transparency :";
									texte += "	<select style=\"width=50px;\" name=\"filled_transparence"+i+"\">";
										// modif 16/04/2007 Gwénaël
											// ajout du pourcentage 0% 
										texte += "<option value=\"0.0\">0%</option>";
										texte += "<option value=\"0.1\">10%</option>";
										texte += "<option value=\"0.2\">20%</option>";
										texte += "<option value=\"0.3\" selected=selected>30%</option>";
										texte += "<option value=\"0.4\">40%</option>";
										texte += "<option value=\"0.5\">50%</option>";
										texte += "<option value=\"0.6\">60%</option>";
										texte += "<option value=\"0.7\">70%</option>";
										texte += "<option value=\"0.8\">80%</option>";
										texte += "<option value=\"0.9\">90%</option>";
										texte += "<option value=\"1\">100%</option>";
									texte += "	</select>";
									texte += "</td>";
							texte +=" 	</tr>";
							texte +="</table>";
						texte += "</td>";
					texte += "</tr>";
				texte += "</table>";
			texte +="</td>";
		texte += "</tr>";
			texte += "<tr>";
				texte += "<td>";
					texte += "<table cellpadding=\"0\" cellspacing=\"2\" align=\"left\">";
						texte += "<tr>";
							texte += "<td class=\"texteGris\">Min range :</td>";
							texte += "<td>";
								texte += "<input type=\"text\" name=\"min_range"+i+"\"   style=\"width=50px\">";
							texte += "</td>";
						texte += "</tr>";
					texte += "</table>";
				texte += "</td>";
				texte += "<td>";
					texte += "<table cellpadding=\"0\" cellspacing=\"2\" align=\"left\">";
						texte += "<tr>";
							texte += "<td class=\"texteGris\">Max range :</td>";
							texte += "<td>";
								texte += "<input type=\"text\" name=\"max_range"+i+"\"  style=\"width=50px\">";
							texte += "</td>";
						texte += "</tr>";
					texte += "</table>";
				texte += "</td>";
				texte += "<td>";
				texte += "</td>";
			texte += "</tr>";
		texte += "</table>";
		texte += "</fieldset>";
		texte += "</div>";

		document.getElementById('range'+ (i - 1)).innerHTML += texte;
	//}
}