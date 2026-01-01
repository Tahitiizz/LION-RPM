/*
 * 07/08/2012 MMT DE mantis 1438 Display days of previous/next month in T&A calendar 
 * 30/9/10 MMT bz 18188 calendrier s'affiche sous GIS
 *
 *
 * 17/08/2010 création du fichier dans le cadre du bug bz 16753
 *
 */

/**
 * Permet la configuration et l'Utilisation du datepicker JQueryui
 * avec les options standard utilisées dans T&A
 *
 * documentation est dans http://jqueryui.com/demos/datepicker
 *
 * @author m.monfort
 */

// evite le conflit sur l'operateur $ avec prototype, utilise J pour JQuery
var J = jQuery.noConflict();

// MMT 14/09/10 netoyage
/**
 * ajoute le datePicker a l'input et a l'image passés (tous deux requis) en parametre
 */
function addDatePicker(inputId, imgId){

      J(function(){
         J("#" + inputId).datepicker({
            showOn: 'none',
            showWeek: true,
            changeMonth: true,
            changeYear: true,
            //07/08/2012 MMT DE mantis 1438 Display days of previous/next month in T&A calendar 
            showOtherMonths: true,
            selectOtherMonths: true,
            autoSize: true,
            firstDay: 0,
            dateFormat: 'dd/mm/yy',
            maxDate: '+0D',
            showButtonPanel: true,
            buttonText: 'Calendar',
            showAnim: 'fadeIn',
            currentText: 'Current Month',
            closeText: 'Close',
            // 30/9/10 MMT bz 18188 calendrier s'affiche sous GIS
            // JQuery affecte z-index = 1 a la division datepicker juste apres le beforeShow
            // utilisation d'un timer pour affecter la valeure par dessus'
            beforeShow: function() {setTimeout("J('#ui-datepicker-div').css('z-index',99999)",10); },
            monthNamesShort:['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
         });
         //trigger pour ouvrir le datepicker sur le click sur l'image'
         J("#" + imgId).click(function() { J("#" + inputId).datepicker("show") });
         

      });
      
}

/**
 * affecte la date passée en parametre au datePicker identifié par l'id de son input
 */
function setDatePickerValue(inputId,dateValue){

   J(function(){
      J("#" + inputId).datepicker('setDate',dateValue);
   });
}

/**
 * affecte les options (ecrase valeures par defaut si existantes) passée en parametre
 * au datePicker identifié par l'id de son input
 */
function setDatePickerOptions(inputId,options){
   
   J(function(){
      J("#" + inputId).datepicker('option',options);
   });
}





