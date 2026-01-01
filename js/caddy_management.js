/*
	25/08/2009 GHX
		- Correction du BZ 11188
*/
/*
* Gestion du caddy avec http.send
* @author christphe chaput
* @version V1.0 2005-05-16
* - maj DELTA christophe 25 04 2006. cf MODIF DELTA NOUVEAU(ajout)   MODIF DELTA(mise en commentaires des modifications)
	- maj 04 10 2006 christophe : caddy devient cart.
*/
function getHTTPObject() {
  var xmlhttp;
  /*@cc_on
  @if (@_jscript_version >= 5)
    try {
      xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
      } catch (e) {
      try {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        } catch (E) {
        xmlhttp = false;
        }
      }
  @else
  xmlhttp = false;
  @end @*/
  if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
    try {
      xmlhttp = new XMLHttpRequest();
      } catch (e) {
      xmlhttp = false;
      }
    }
  return xmlhttp;
  }

var http = getHTTPObject();

  function handleHttpResponse()
   {
    //   if (http.readyState == 4) {
    //   alert("back");
    //     }
   }
  
  // Ajoute un élément dans le caddy.

  // 17/02/2009 - Modif. benoit : reprise de la fonction 'caddy_update()' en utilisant prototype.js

	function caddy_update(niveau0, id_user, object_page_from, object_type, object_title, object_id, object_summary, last_comment)
	{
		// 24/07/2009 BBX : suppression du message de confirmation. BZ 6645
		//if(confirm("Do you want to add this to your cart ?"))
		//{

			var action="ajouter";
			var url= "multi_object_caddy_management.php?last_comment="+last_comment+"&object_title="+object_title+"&object_id="+object_id+"&id_user="+id_user+"&object_page_from="+object_page_from+"&object_type="+object_type+"&object_summary="+object_summary+"&action="+action; //requette pour recuperer les elements de la liste a modifier

			new Ajax.Request(niveau0 + 'reporting/intranet/php/affichage/multi_object_caddy_management.php', 
			{
				method: 'get',
				parameters: "action=ajouter&last_comment=" + last_comment + "&object_title=" + object_title + "&object_id=" + object_id + "&id_user=" + id_user + "&object_page_from=" + object_page_from + "&object_type=" + object_type + "&object_summary=" + object_summary,
				onSuccess: function(transport){
					// 17:55 25/08/2009 GHX
					// Correction du BZ 11188
					$('updateCaddy_'+object_summary).setStyle({display:'block'});
					setTimeout("Effect.toggle('updateCaddy_"+object_summary+"','appear')",1000);
				}
			});
		//}
	}

  // MODIF DELTA AJOUT D UNE FONCTION
  // Ajoute un élément venant d'un gtm dans le caddy.
  function caddy_update_gtm(id_user,object_page_from,object_type,object_title,object_id,object_summary,last_comment) {
    if(confirm("Do you want to add this to your cart ?")){
      var action="ajouter";
      var url= "multi_object_caddy_management.php?last_comment="+last_comment+"&object_title="+object_title+"&object_id="+object_id+"&id_user="+id_user+"&object_page_from="+object_page_from+"&object_type="+object_type+"&object_summary="+object_summary+"&action="+action; //requette pour recuperer les elements de la liste a modifier
      http.open("GET", url, true);
      http.send(null);
    }
  }


	// permet d'ajouter un fichier pdf alarmes ou top / WCL dans le caddy.
	function add_alarm_pdf_caddy(id_user,object_type){
		if(confirm("Do you want to add this PDF to your cart ?")){
			var action="ajouter_pdf";
			var pdf = document.getElementById('nomPDF').value;
	   		var url= "multi_object_caddy_management.php?id_user="+id_user+"&object_page_from=alarm&object_type="+object_type+"&action="+action+"&pdf="+pdf; //requette pour recuperer les elements de la liste a modifier
			//alert(url);
			http.open("GET", url, true);
			// http.onreadystatechange = handleHttpResponse;
			http.send(null);
		}
	}

        // Supprime tous les éléments contenus dans le caddy.
        function caddy_reset(id_user) {
                if(confirm("Do you want to empty your cart ?")){
                        var action="vider";
                       var url= "multi_object_caddy_management.php?id_user="+id_user+"&action="+action; //requette pour recuperer les elements de la liste a modifier
                      // 12/12/2013 GFS - Bug 29275 - [SUP][REC][ROAMING RAN 5.2.0.01][ZAIN IRAK][AVP 41033][TC #TA-57382][Firefox][Reset all items] All items are not removed when clicking on "Reset all items" button into The Cart page.
                      http.open("POST", url, true);
                      // 29/08/2014 FGD - Bug 43561 - [REC][CB 5.3.3.01][TC#TA-56747][FF 31 compatibility][Reset all items] All items are not removed after clicking 'Reset all items' button
                      http.onreadystatechange = reload_window;
                      http.send(null);
                      //window.location.reload();
              }
        }

        // permet de supprimer un élément du caddy.
        function caddy_remove_one_element(id_user,object_id) {
                if(confirm("Do you want to remove this element from your cart ?")){
                        var action="supprimer";
                       var url= "multi_object_caddy_management.php?id_user="+id_user+"&object_id="+object_id+"&action="+action; //requette qui supprime l'élément choisit par l'utilisateur.
                       //alert(url);
                      http.open("GET", url, true);
                      http.onreadystatechange = reload_window;
                      http.send(null);
                      //window.location.reload();
              }
        }
        
        //recharge la fenêtre apres que la requête soit terminée
        function reload_window(){
        	if (http.readyState == 4) {
        		 window.location.reload();
        	}
        }

        // Permet d'afficher un graphique en grand format lorsque l'utilisateur
        // visualise son caddy. (ouvre une popup)
        // object_type est le type de l'objet à afficher.
        function increase_caddy_picture(object_id,object_type,width,height){
                if(object_type=="table"){
                        largeur = 950;
                        hauteur = 250;
                        options = "menubar=no,scrollbars=yes,statusbar=no, resizable=yes";
                        var top=(screen.height-hauteur)/2;
                        var left=(screen.width-largeur)/2;
                        window.open("view_table_from_caddy.php?object_id="+object_id,"","top="+top+",left="+left+",width="+largeur+",height="+hauteur+","+options);
                } else if(object_type=="builder_report"){
                        largeur = 950;
                        hauteur = 250;
                        options = "menubar=no,scrollbars=yes,statusbar=no, resizable=yes";
                        var top=(screen.height-hauteur)/2;
                        var left=(screen.width-largeur)/2;
                        window.open("view_table_from_caddy.php?type=builder_report&object_id="+object_id,"","top="+top+",left="+left+",width="+largeur+",height="+hauteur+","+options);
                } else {
                        options = "menubar=no,scrollbars=yes,statusbar=no, resizable=yes";
                        var top=(screen.height-height)/2;
                        var left=(screen.width-width)/2;
                        window.open(object_id,"","top="+top+",left="+left+",width="+width+",height="+height+","+options);

                }
        }

        // Permet d'afficher les images des graph en grand format.
        function caddy_zoom_all(id_user,zoom_all){
                if(zoom_all == 1){
                        window.location = "multi_object_caddy.php?id_user="+id_user;
                } else {
                        window.location = "multi_object_caddy.php?id_user="+id_user+"&zoom_all=true";
                }
        }

        // Redimensionne la fenêtre.
        function resize(x,y) {
                parent.window.resizeTo(x,y);
        }


        
