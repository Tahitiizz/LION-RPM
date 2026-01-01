/*
*	11/10/2011 - Copyright Astellia
*
*	11/10/2011 ACS
*		- Mantis 615: DE Data reprocessing GUI
*/

function switchAreaDisplay(idProduct) {
	if (areaDisplay[idProduct] == "none") {
		areaDisplay[idProduct] = "block";
		document.getElementById("dataReprocessImg_" + idProduct).src = niveau0 + 'images/icones/moins_alarme.gif';
	}
	else {
		areaDisplay[idProduct] = "none";
		document.getElementById("dataReprocessImg_" + idProduct).src = niveau0 + 'images/icones/plus_alarme.gif';
	}
	
	document.getElementById("dataReprocessArea_" + idProduct).style.display = areaDisplay[idProduct];
}

function getModeTxt(idProduct) {
	if (document.forms["dataReprocessForm_" + idProduct].mode[1].checked) {
		return "Delete files";
	}
	else {
		return "Reprocess files";
	}
}

function changeMode(idProduct, mode) {
	if (mode == 1) {
		document.getElementById("dataReprocessDates_" + idProduct).style.display = "block";
		document.getElementById("dataReprocessConnections_" + idProduct).style.display = "block";
		document.getElementById("execute_" + idProduct).value = getModeTxt(idProduct);
	}
	else {
		document.getElementById("dataReprocessDates_" + idProduct).style.display = "block";
		document.getElementById("dataReprocessConnections_" + idProduct).style.display = "none";
		document.getElementById("execute_" + idProduct).value = getModeTxt(idProduct);
	}
	
	document.getElementById("dataReprocessExecute_" + idProduct).style.display = "block";
}

function selectAllConnections(idProduct) {
	var list = document.getElementById("connections_" + idProduct);
	if (list != null) {
		for (var i = 0 ; i < list.length ; i++) {
			list.options[i].selected = true;
		}
	}
}

function executeDataReprocess(idProduct) {
	// hide error and info messages
	$('info_' + idProduct).style.display = 'none';
	$('error_' + idProduct).style.display = 'none';
	
	if (!checkDataForReprocess(idProduct)) {
		return;	
	}
	
	$('execute_' + idProduct).disable();
	
	ajaxDataReprocess(idProduct, false);
}

function ajaxDataReprocess(idProduct, isConfirmed) {
	var params = Form.serialize('dataReprocessForm_' + idProduct);
	if (isConfirmed) {
		params += "&confirm=confirm";
	}
	new Ajax.Request('setup_data_reprocessing_execute.php',{
		method: 'post',
		postBody: params,
		onComplete: function(data) {
			try {
				var response = eval('(' + data.responseText + ')');
			}
			catch (exception) {
				displayMessage(idProduct, true, "Service error");
				$('execute_' + idProduct).enable();
				return;
			}
			
			if (response.message_type == "warning") {
				if (response.message_alert == "code0") { // delete files
					warningMessage = warningMessageForDelete;
				}
				else if (response.message_alert == "code1") { // reprocess files
					warningMessage = warningMessageForReprocess;
				}
				else if (response.message_alert == "code2") { // reprocess files with "off" processes
					warningMessage = warningMessageForReprocess + "<div class=\"popupWarning\">" + warningMessageProcessOff + "</div>";
				}
				else {
					displayMessage(idProduct, true, "Service error");
					$('execute_' + idProduct).enable();
					return;
				}
				
				dates = getStringOfList(document.getElementById("dates_" + idProduct));
				var reg = new RegExp("(__dates__)", "g");
				warningMessage = warningMessage.replace(reg, dates, warningMessage);

				connections = getStringOfList(document.getElementById("connections_" + idProduct));
				reg = new RegExp("(__connections__)", "g");
				warningMessage = warningMessage.replace(reg, connections, warningMessage);
				
				displayConfirmBox(warningMessage, idProduct);
			}
			else {
				displayMessage(idProduct, response.message_type == "error", response.message_alert);
				
				// 14/10/2011 ACS BZ 24190 List of available dates not updated after "Delete files"
				// 24/10/2011 ACS BZ 24353 Strange items in date list when last date deleted
				updateDateList(document.getElementById("dates_" + idProduct), response.nbAvailableDates, response.availableDates);
				
				$('execute_' + idProduct).enable();
			}
		}
	});
}

function displayConfirmBox(msg, idProduct) {
    Dialog.confirm(
        msg,
        {
            className:"alphacube",
            title:getModeTxt(idProduct),
            width:500,
            draggable:true,
            wiredDrag: true,
            resizable: true,
            zindex:20000,
            destroyOnClose: true,
            top:200,
            buttonClass:"bouton",
            okLabel:getModeTxt(idProduct), cancelLabel:"Cancel",
            onOk:function(win){
				ajaxDataReprocess(idProduct, true);
				
				return true;
            },
            onCancel:function(win){
				$('execute_' + idProduct).enable();
            }
        }
    );
}

function updateDateList(select, nbAvailableDates, list) {
	select.options.length=0;
	if (nbAvailableDates > 0 && list) {
		for (var i in list) {
			var optn = document.createElement("OPTION");
			optn.text = i;
			optn.value = list[i];
			select.options.add(optn);
		}
	}
}

function getStringOfList(list) {
	var result = "";
	if (list) { 
		for (var i = 0; i < list.length ; i++) {
			if (list.options[i].selected) {
				if (result != "") {
					result += " & ";
				}
				result += list.options[i].text;
			}
		}
	}
	return result;
}


function checkDataForReprocess(idProduct) {
	// check mode
	var modeValue = null;
	var modeRadios = document.forms["dataReprocessForm_" + idProduct].mode;
	for (var i = 0; i < modeRadios.length ; i++) {
		if (modeRadios[i].checked) {
			modeValue = modeRadios[i].value;
		}
	}
	
	if (modeValue == null) {
		displayMessage(idProduct, true, msgMode);
		return;
	}
	
	// check dates
	var lastDate = null;
        var nbDates = 0;
	var datesSelect = document.getElementById("dates_" + idProduct);
	for (var i = 0; i < datesSelect.length ; i++) {
		if (datesSelect.options[i].selected) {
			lastDate = datesSelect.options[i].value;
                        nbDates++;
		}
	}
	
	if (lastDate == null) {
		displayMessage(idProduct, true, msgDate);
		return;
	}
        else{
            // 30/06/2014 NSE Mantis 5450: Reprocess des données autorisé pour Client admin
            // Ajout de messages d'alerte sur le nombre de dates traitées
            var reg = new RegExp("(__nbDays__)", "g");
            msgDateWarningDelete = msgDateWarningDelete.replace(reg, nbDates, msgDateWarningDelete);
        
            // plus de 5 dates sélectionnées
            if (nbDates > 5) {
                if(modeValue == 1){
                    // en mode delete, on demande confirmation sans bloquer
                    if(!confirm(msgDateWarningDelete)){
        		displayMessage(idProduct, true, msgUserCancel);
                        return;
                    }
                }
                else{
                    // en mode reprocess, on bloque
                    alert(msgDateLimitReprocess);
                    displayMessage(idProduct, true, msgDateLimit5);
                    return;
                }
            }
            else{
                // entre 2 et 5 dates sélectionnées
                if (nbDates > 2) {
                    if(modeValue == 1){
                        // en mode delete, on demande confirmation sans bloquer
                        if(!confirm(msgDateWarningDelete)){
                            displayMessage(idProduct, true, msgUserCancel);
                            return;
                        }
                    }
                    else{
                        // en mode reprocess, on demande confirmation sans bloquer
                        if(!confirm(msgDateWarningReprocess)){
                            displayMessage(idProduct, true, msgUserCancel);
                            return;
                        }
                    }
                }
            }
        }

	if (modeValue == 1) {
		// check connections
		var hasConnection = false;
		var connectionsSelect = document.getElementById("connections_" + idProduct);
		for (var i = 0; i < connectionsSelect.length ; i++) {
			if (connectionsSelect.options[i].selected) {
				hasConnection = true;
			}
		}
		
		if (!hasConnection) {
			displayMessage(idProduct, true, msgConnection);
			return;
		}
	}
	
	return true;
}

function displayMessage(idProduct, isError, message) {
	if (isError) {
		$('info_' + idProduct).style.display = 'none';
		$('error_' + idProduct).style.display = 'block';
		$('error_' + idProduct).update(message);
	}
	else {
		$('error_' + idProduct).style.display = 'none';
		$('info_' + idProduct).style.display = 'block';
		$('info_' + idProduct).update(message);
	}
}
