/**
* Fonctions nécessaires pour ErlangB
* 
*/
$(document).ready(function(){
			$("#mainbody").addClass('erlang');
			$("#rki").css("display","inline");
			//Hide div effect
			$("#effect").css("display","none");
			$("#openR").css("display","none");
			$("#closeR").css("display","none");

			// Ajouter un handler à checkbox fromR
			$("#fromR").click(function(){
				// si checked
				if ($("#fromR").is(":checked"))
				{
					$("#closeR").show("fast");
					$("#openR").hide("fast");

					/// si Tisx checked
					if ($("#Tisx").is(":checked"))
					{
					// Deselectionner Tisx 
	 				$("#Tisx").attr('checked', false);
					}

					// show la liste des compteurs
					$("#effect").show("fast");
					/// effacer le contenu de traffic
					$("#traffic").val('');
					
			
				}
				else
				{	
					$("#closeR").hide("fast");
					$("#openR").hide("fast");   
					// hide la liste des compteurs  
					$("#effect").hide("fast");
					/// deselectionner tout
					$('input[name^="counter"]').attr('checked',false);
				}
			});

			$("#closeR").click(function(){
				$("#closeR").hide("fast");
				$("#openR").show("fast");
				$("#effect").hide("fast");

			});
			$("#openR").click(function(){
				$("#openR").hide("fast");
				$("#closeR").show("fast");
				$("#effect").show("fast");

			});

			$("#keffect").css("display","none");
			$("#kopenR").css("display","none");
			$("#kcloseR").css("display","none");

			// Ajouter un handler à checkbox fromR
			$("#kfromR").click(function(){
				// si checked
				if ($("#kfromR").is(":checked"))
				{
					$("#kcloseR").show("fast");
					$("#kopenR").hide("fast");

					/// si Tisx checked
					if ($("#Nisx").is(":checked"))
					{
					// Deselectionner Tisx 
	 				$("#Nisx").attr('checked', false);
					}

					// show la liste des compteurs
					$("#keffect").show("fast");
					/// effacer le contenu de traffic
					$("#nbchannel").val('');
					
			
				}
				else
				{	
					$("#kcloseR").hide("fast");
					$("#kopenR").hide("fast");   
					// hide la liste des compteurs  
					$("#keffect").hide("fast");
					/// deselectionner tout
					$('input[name^="kcounter"]').attr('checked',false);
				}
			});

			$("#kcloseR").click(function(){
				$("#kcloseR").hide("fast");
				$("#kopenR").show("fast");
				$("#keffect").hide("fast");

			});
			$("#kopenR").click(function(){
				$("#kopenR").hide("fast");
				$("#kcloseR").show("fast");
				$("#keffect").show("fast");

			});

			// Ajouter un handler à checkbox Tisx
			$("#Tisx").click(function(){
				$("#Gisx").attr('checked', false);
				$("#Nisx").attr('checked', false);
				$("#traffic").val('');
				$("#closeR").hide("fast");
				$("#openR").hide("fast");


				// si checked
				if ($("#fromR").is(":checked"))
				{
					// hide La liste des compteurs et decocher le checkbox 
					$("#effect").hide("fast");
	 				$("#fromR").attr('checked', false);
					$('input[name^="counter"]').attr('checked',false);
				}
			});

			// Ajouter le handler à checkbox Gisx
			$("#Gisx").click(function(){
				$("#Tisx").attr('checked', false);
				$("#Nisx").attr('checked', false);
				$("#gos").val('');
			});

			// Ajouter le handler à checkbox Nisx
			$("#Nisx").click(function(){
				$("#Tisx").attr('checked', false);
				$("#Gisx").attr('checked', false);
				$("#fromC").attr('checked', false);
				$("#nbchannel").val('');
				if ($("#kfromR").is(":checked"))
				{
					// hide La liste des compteurs et decocher le checkbox 
					$("#keffect").hide("fast");
					$("#kopenR").hide("fast");
	 				$("#kfromR").attr('checked', false);
					$('input[name^="kcounter"]').attr('checked',false);

				}
			});

			// Ajouter le handler à checkbox fromC
			$("#fromC").click(function(){
				$("#Nisx").attr('checked', false);
				$("#nbchannel").val('');
				if ($("#kfromR").is(":checked"))
				{
					// hide La liste des compteurs et decocher le checkbox 
					$("#keffect").hide("fast");
					$("#kopenR").hide("fast");
	 				$("#kfromR").attr('checked', false);
					$('input[name^="kcounter"]').attr('checked',false);

				}
			});

			// Ajouter le handler à input traffic
			$("#traffic").click(function(){
				$("#Tisx").attr('checked', false);
				if ($("#fromR").is(":checked"))
				{
					// hide La liste des compteurs et decocher le checkbox 
					$("#effect").hide("fast");
	 				$("#fromR").attr('checked', false);
					$("#openR").hide("fast");
					$('input[name^="counter"]').attr('checked',false);

				}
			});

			// Ajouter le handler à input gos
			$("#gos").click(function(){
				$("#Gisx").attr('checked', false);
			});

			// Ajouter le handler à input nbchannel
			$("#nbchannel").click(function(){
				$("#Nisx").attr('checked', false);
				$("#fromC").attr('checked', false);
				if ($("#kfromR").is(":checked"))
				{
					// hide La liste des compteurs et decocher le checkbox 
					$("#keffect").hide("fast");
	 				$("#kfromR").attr('checked', false);
					$("#kopenR").hide("fast");
					$('input[name^="kcounter"]').attr('checked',false);

				}
			});
		});




		$(function(){
			var traffic = $("#traffic"),
			hello ='',
			form1 = $("#frm1"),
			gos = $("#gos"),
			fromR =  $("#fromR"),
			kfromR =  $("#kfromR"),
			fromC =  $("#fromC"),
			nbchannel = $("#nbchannel"),
			Gisx = $("#GisX"),
			Tisx = $("#TisX"),
			Nisx = $("#NisX"),
			allFields = $([]).add(traffic).add(gos).add(nbchannel).add(Nisx).add(Gisx).add(Tisx).add(fromR).add(kfromR).add(fromC),
			tips = $("#validateTips");

			function updateTips(t) {
				tips.text(t);
			}

			function display_error(o,message) {
				o.addClass('ui-state-error');
				updateTips(message);
				return true;

			}

			function isDigit(o,n) {
				if (isNaN(o.val())) {
						display_error(o,n + " must be a number.");
						return false;
				} else {
					if (o.val() < 0) {
						display_error(o,n + " must be a positive number.");
						return false;
					} else {

						return true;
					}
					
				}
			}

			function isEmpty(o,n)
			{
				if ( (o.val() == null) || (o.val() == 0)) {
					display_error(o,"Value of " + n + " can't be empty.");
					return true;
				} else {
					return false;
				}
			}



			function checkVal(o,n,min,max) {
				if (!isDigit(o,n) || isEmpty(o,n)) return false;
				if ( (min != 0) && (max != 0) ) {
					
					if ( o.val() > max || o.val() < min ) {
					display_error(o,"Value of " + n + " must be between "+min+" and "+max+".");
					return false;
					} else {
						return true;
					}
				} 

				return true;

			}



			function checkUnknown() {
				var count =0;
				if ( ($("#Gisx").is(":checked") ) || ($("#Tisx").is(":checked") ) || ($("#Nisx").is(":checked") ) )
				count++;
				if (count !=1) {

						display_error(traffic,"Please select one Unknown field.");
						display_error(nbchannel,"");
						display_error(gos,"");
						return false;


				}
				else return true;

			}
			function checkraws() {
				var kpis = [];
				var l;
				if ( $("input[name^='counter']") ) {
					$("input[name^='counter']").each(function() {
							if (this.checked) { kpis.push(this.value); }
							});
					l = kpis.length;

				} else {

				l = 0;
				}

				if ( fromR.is(":checked") && (l == 0) )  {
					display_error(traffic,"Please select at least one Raw counter.");
					return false;
				} else {

				return true;

				}

			}
			function checkkraws() {
				var kkpis = [];
				var l;
				if ( $("input[name^='kcounter']") ) {
					$("input[name^='kcounter']").each(function() {
							if (this.checked) { kkpis.push(this.value); }
							});
					l = kkpis.length;

				} else {

				l = 0;
				}

				if ( kfromR.is(":checked") && (l == 0) )  {
					display_error(nbchannel,"Please select at least one Raw counter.");
					return false;
				} else {

				return true;

				}

			}

			function from_php(list){ 
				$.get("erlangb/erlang.php", { list: list},
				function(data){
					
					// maj 24/11/2009 - MPR : On ajoute le résultat du calcul à la place de tout réécraser
					// On vérifie que la formule n'est pas la même sinon on l'a en double
					if( data != formulaire.zone_formule_numerateur.value && data ){
						formulaire.zone_formule_numerateur.value+= data;		
					}							
					// $("#formula_kpi").val('');
					// $("#formula_kpi").append(data);
				});
			}


			function misenforme(tr,go,nb,gx, tx,nx,cell,raw,kraw) {
				var result='';
				var list='';
				var send = false;
				var mode='CH';
				if ( ( nb == null) ||( nb == '') ||( nb < 0)) nb = 0;
				if ( ( tr == null) ||( tr == '') ||( tr < 0)) tr = 0;
				if ( ( go == null) ||( go == '') ||( go < 0)) go = 0;
				if (go) go/=100;
				if (gx) {
					mode ='GOS';
					if ( (cell)||(raw) ||(kraw)) send = true;
					list = 'A='+tr+'&N='+nb+'&mode=GOS';
				} else if (tx) {
					mode ='TRAFFIC';
					if ((cell)||(kraw)) send = true;
					list = 'P='+go+'&N='+nb+'&mode=TR';
				} else if (nx) {
					mode ='CHANNELS';
					if (raw) send = true;
					list = 'A='+tr+'&P='+go+'&mode=CH';
				}
				if (send) {


				var prefix = "CASE WHEN ('$network' = '<?php echo $aggregation; ?>') THEN erlangb____('<?php echo $aggregation; ?>',";
				var postfix = "'$table_source', '$where_clause') ELSE 0 END";
				///var prefix = "erlangb(";
				///var postfix = ")";

				result += prefix;
				var kpis = [];
				$("input[name^='counter']").each(function() {
					if (this.checked) { kpis.push(this.value); }
					});

				var kkpis = [];
				$("input[name^='kcounter']").each(function() {
					if (this.checked) { kkpis.push(this.value); }
					});
				result += "'"+kpis.join('+')+"',";
				result += "'"+kkpis.join('+')+"',";
				result += "'"+go+"',";
				result += "'"+nb+"',";
				result += "'"+tr+"',";
				result += "'"+mode+"'";
				result +=",";
				result += postfix;
				
				return result;
				} else {
				return from_php(list);
				}
			}
			// Accordion
			$("#accordion").accordion({ 
				header: "h3",
				autoHeight: true
			 });

			// Dialog			
			$('#dialog').dialog({
				autoOpen: false,
				width: 600,
				modal: true,
				close: function() {
					tips.text(hello);
					$('#effect').hide('fast');
				},
				open: function() {
					tips.text(hello);
				},
				buttons: {
					"Ok": function() { 
						var formule ='';
						var bValid = true;
						var form1isactive = false;
						if ($("#frm1 #Tisx").is(":checked") || $("#frm1 #fromR").is(":checked") || $("#frm1 #Tisx").is(":checked") || $("#frm1 #Nisx").is(":checked") || $("#frm1 #Gisx").is(":checked") || $("#frm1 #fromR").is(":checked") || $("#frm1 #fromC").is(":checked")) form1isactive =true;

						if ( ($("#frm1 #traffic").val() != 0) || ($("#frm1 #nbchannel").val() != 0) || ($("#frm1 #gos").val() != 0) ) 
							form1isactive =true;



						if (form1isactive) {

							allFields.removeClass('ui-state-error');
							if (!$("#frm1 #Gisx").is(":checked") ) 
							bValid = bValid && checkVal($("#frm1 #gos"),'Gos%',1,100);							
							if (!$("#frm1 #Tisx").is(":checked") && !$("#frm1 #fromR").is(":checked")) 
							bValid = bValid && checkVal($("#frm1 #traffic"),'Traffic (Erl.)',0,0);
							if (!$("#frm1 #Nisx").is(":checked") && !$("#frm1 #fromC").is(":checked")&& !$("#frm1 #kfromR").is(":checked")) 
							bValid = bValid && checkVal($("#frm1 #nbchannel"),'TCH (Number of Traffic Channel)',0,0, $("#frm1 #Nisx"));
							bValid = bValid && checkUnknown();
							bValid = bValid && checkraws();
							bValid = bValid && checkkraws();
							if (bValid) {
							formule = misenforme(traffic.val(),gos.val(),nbchannel.val(), $("#frm1 #Gisx").is(":checked"),$("#frm1 #Tisx").is(":checked"),$("#frm1 #Nisx").is(":checked"),$("#frm1 #fromC").is(":checked"),$("#frm1 #fromR").is(":checked"),$("#frm1 #kfromR").is(":checked"));
							
							// maj 24/11/2009 - MPR : On ajoute la formule à la place de tout réécraser
							// On vérifie que la formule n'est pas la même sinon on l'a en double
							if( formule != formulaire.zone_formule_numerateur.value && formule){
								formulaire.zone_formule_numerateur.value+= formule;		
							}
							// $("#formula_kpi").val('');
							// $('#formula_kpi').append(formule); 

							$(this).dialog('close');
							}
						}
					}, 

					"Cancel": function() { 
						allFields.val('').removeClass('ui-state-error');
						$(this).dialog("close"); 
					},

					"Reset": function() { 
						tips.text('');
						$("#closeR").hide("fast");
						$("#openR").hide("fast");
						allFields.val('').removeClass('ui-state-error');
						$('input:text').each( function() {     $(this).val('');});
						$('input:checkbox').each( function() {  
								$(this).attr('checked',false);
						});
						$('#formula_kpi').val(''); 
					} 
				}
			});
			
			// Dialog Link
			$('#dialog_link').click(function(){
				$('#dialog').dialog('open');
				return false;
			});
			
			//hover states on the static widgets
			$('#dialog_link, ul#icons li').hover(
				function() { $(this).addClass('ui-state-hover'); }, 
				function() { $(this).removeClass('ui-state-hover'); }

			);		

		});
