/*
 * 28/07/2011 SPD1: Querybuilder V2 - Strings used by the application  
 */


Ext.define('Ext.ux.querybuilder.locale', {
	
	singleton: true,
	
	/* General configuration */
	"config": {
		"displayDateformat": 	"d/m/Y"
	},
	
	/* App */
	"app": {
		"serverError": 			"Server error",
		"seeConsole": 			"(See browser console for more details)",
		"pleaseWait": 			"Please wait..."
	},
	
	/* Left panel */
	"leftPanel": {
		"title":				"Filter",							
		"rawList":				"RAW list",							
		"kpiList":				"KPI list",
		"qtAdvOptionsTitle": 	"Show/Hide",							
		"qtAdvOptions":			"advanced search options",
		"addToSelected":		"Add to: selected elements",
		"addToFilters":			"Add to: filters",
		"addTo":				"Add element",
		"addFormula":			"Add element formula",
		"filtered":				"(filtered)",
		"infos":				"Get information (ctrl+click)"
	},

	/* Right panel */
	"rightPanel": {
		"title":				"Queries",
		"batchCsvExportTitle":	"Batch (CSV) export",
		"batchCsvExportDesc":	"Export selected queries",
		"importQueriesTitle":	"Import queries",
		"importQueriesDesc":	"Import queries into query builder",
		"exportQueriesTitle":	"Export queries",
		"exportQueriesDesc":	"Export selected queries into an archive"
	},
	
	/* SQL panel */
	"sqlPanel": {		
		"executeOn": 			"Execute on",
		"query": 				"Query"
	},

	/* Edit SQL window */
	"editSqlWindow": {
		"title": 				"Edit SQL",
		"message":				"If you edit SQL, you will not be able to return to the wizard mode.<br><br>Are you sure it is what you want?",
		"editButton": 			"Edit",
		"cancelButton":			"Cancel"		
	},

	/* Queries panel */
	"queriesPanel": {
		"userQueries":			"My queries",
		"publicQueries":		"Public queries",
		"shareTip":				"Click to share",
		"unshareTip":			"Click to unshare",
		"deleteTip":			"Delete this query",
		"deletePopupMessage":	"Are you sure you want to delete this query ?",
		"okButton": 			"Delete",
		"cancelButton": 		"No",
		"deletePopupTitle":		"Delete query",
		"deleteNotifTitle":		"Delete successful",
		"deleteNotif":			"The query has been deleted",
		"overwriteTitle":		"Overwrite",
		"overwriteMessage":		"A query already exists with this name. Do you want to overwrite it ?",
		"overwriteButton": 		"Overwrite",
		"saveTitle":			"Save",
		"saveButton": 			"Save",		
		"askForSave":			"Your query has not been saved. Do you want to save it now ?",
		"exportTitle":  		"Queries export",
		"batchExportTitle": 	"Batch export",
		"exportCompleted": 		"Export completed.",
		"checkFirst": 			"Check at least one query first.",
		"sharedBy": 			"Shared by: ",
		// 20/03/2013 GFS - Bug 32731 - [SUP][5.2][AVP NA][Truphone] It should not be possible to run a query in SQL mode on T&A Gateway if there is no mixed KPI
		"previewPopupTitle":	"Error",
		"previewPopupMessage":	"You must select a product to run your SQL query"
	},
		
	/* Download panel */
	"downloadPanel": {
		"title": "Exports",
		"deleteExport": "Delete this export",
		"deleteAll": "Delete all",
		"deletePopupMessage":	"Are you sure you want to delete this export ?",
		"deletePopupTitle":		"Delete export",
		"deleteAllPopupMessage":"Are you sure you want to delete all exports ?",
		"deleteAllPopupTitle":	"Delete exports",		
		"okButton": 			"Delete",
		"cancelButton": 		"No",
		"notificationTitle": 	"Query CSV export",
		"processStarted": 		"Process created.",
		"cancelSaveButton": 	"Cancel",
		"openQuery": 			"Open query used to generate this export"		
	},
			
	/* Table panel */
	"tablePanel": {
		"title": 			"Display table",
		"back":				"Back to query"
	},
	
	/* Graph panel */
	"graphPanel": {
		"title": 			"Display graph",			
		"back":				"Back to query",
		"graphParameters": 	"Graph parameters",
		"graphDisplay": 	"Graph display",
		"basketButton":		"Add to cart",
		"autoReloadButton": "Auto reload",
		"reload": 			"Reload",
		"setGraphName": 	"Set graph properties",
		"fullscreen": 		"Fullscreen view",
		"graphNameTitle": 	"Graph name",
		"graphNameMessage": "Please, enter the name of your graph:",
		"from": 			" (from query builder)",
		"caddyTitle": 		"Query builder graph",
		"graphAdded": 		"The graph has been added to your cart.",
		"graphNotifTitle": 	"Cart updated"		
	},	
	
	/* RAW/KPI Info window */
	"infoWindow": {
		"title":			"Information",
		"close":			"close",
		"name":				"name",
		"label":			"label",
		"product":			"product",
		"family":			"family",
		"description":		"description",
		"formula":			"formula"	
	},

	/* Graph param window */
	"graphParamWindow": {
		"title":			"Graph parameters",		
		"name":				"Name",
		"leftAxisLabel":	"Left X axis label",
		"rightAxisLabel":	"Right X axis label",
		"okButton": 		"Ok",
		"cancelButton": 	"Cancel"
	},
		
	/* Queries import window */
	"queriesImportWindow": {
		"title": 			"Query import",
		"importButton":		"Import queries",
		"closeButton": 		"Close",
		"label": 			"Select the file to import (*.tar.gz)",
		"reportTitle": 		"Queries import report",
		"importError": 		"Error during import process."		
	},
	
	/* Filter panel */
	"filterPanel": {
		"elements": 		"Apply filter on",
		"products": 		"Products",
		"raw": 				"Raw counters",
		"kpi": 				"KPI"	
	},
	
	/* Query tab*/
	"queryTab": {
		"title":			"Query wizard",
		"sqlTitle":			"Query SQL",
		"saveMessage":		"The query has been saved",
		"saveTitle":		"Save successful",
		"loadTitleError": 	"Loading error",
		"queryDeleted": 	"This query has been deleted"
	},
	
	/* Save query window */
	"querySaveWindow": {
		"title": 			"Save query",
		"message":			"Please, enter the name of your query:",
		"btSave":			"Save",
		"btCancel":			"Cancel"	
	},
	
	/* Network agg. panel */
	"netTimeAgg": {
		"title": 			"Network and time",
		"netTipTitle": 		"Network agg. in common",
		"netTipMessage": 	"Click or drag to add an aggregation.",
		"timeTipTitle": 	"Time agg. in common",
		"timeTipMessage":	"Click or drag to add an aggregation.",
		"noNa": 			"No network aggregation",
		"noTa": 			"No time aggregation"		
	},
	
	/* Preview tab */
	"previewTab": {
		"title": 			"Preview&nbsp;&nbsp;&nbsp"
	},
		
	/* Query tab toolbar */
	"queryToolbar": {
		"hideSQL": 			"&nbsp;&nbsp;Hide SQL&nbsp;&nbsp;",
		"showSQL":			"&nbsp;&nbsp;Show SQL&nbsp;&nbsp;",
		"editSQL":			"&nbsp;&nbsp;Edit SQL&nbsp;&nbsp;",
		"save":				"&nbsp;&nbsp;Save&nbsp;&nbsp;",
		"saveas":			"&nbsp;&nbsp;Save as",
		"preview":			"&nbsp;&nbsp;Preview&nbsp;&nbsp;",
		"btNew":			"&nbsp;&nbsp;New&nbsp;&nbsp;",
		"btExport":			"&nbsp;&nbsp;Export&nbsp;",
		"exportOptions": 	"&nbsp;&nbsp;Options",
		"sqlExportOptions": "&nbsp;&nbsp;Options (disabled in SQL)"
	},
	
	/* Graph parameters grid panel*/	
	"parametersGrid": {
		"name": 			"Data name",
		"type": 			"Display type",
		"color": 			"Color",
		"position": 		"Position",
		"typeList": [
			["line", "line"],
			["bar", "bar"],
			["cumulated", "cumulated"]		
		],
		"positionList": [
			["left", "left"],
			["right", "right"]
		],
		"visible": 			"Visible",
		"graphName": 		"Graph name",
		"alternativeText": 	"Alt. name"		
	},
		
	/* Data grid panel */
	"dataGridPanel": {
		"title":			"Selected elements",
		"deleteAll":		"Delete all",
		"deleteAllTip":		"Remove all rows from the grid",
		"distinctButton": 	"Apply distinct clause",
		"distinctButtonTip":"Apply distinct SQL clause",
		"disableFunctions": 	"Disable function",		
		"disableFunctionsTip":	"Disable <b>Function</b> and <b>Group</b> columns",
		"labelColumn":		"Label",
		"nameColumn":		"Name",
		"productColumn":	"Product",
		"functionColumn":	"Function",
		"orderColumn":		"Order",
		"groupColumn":		"Group",
		"visibleColumn":	"Visible",
		"filterColumn":		"Filter",
		"infoColumn": 		"Info",
		"orderByWarning": 	"Using order by may cause a signifiant increase in execution time of the request. In this case you should reduce the number of result (e.g. by adding a filter on the date).", 
		"functions": [                                         
			["",			"None"],
	        ["Sum", 		"Sum"],
	        ["Average", 	"Average"],
	        ["Maximum", 	"Maximum"],
	        ["Minimum", 	"Minimum"],
	        ["Count", 		"Count"]
		],
		"order": [                                         
			["", 		"None"],
			["Ascending", 	"Ascending"],
			["Descending", 	"Descending"]
		],
		"tipDeleteRow": 	"Delete row",
		"tipAddToFilter":	"Add to filter",
		"tipGetInfo":	"Get information"
	},
	
	/* Data grid validation zone */
	"validationZone": {
		"defaultTitle": 	"Validation control",
		"defaultError": 	"You must add, at least:<br>",
		"rawKpiMessage":	"- one RAW/KPI",
		"naMessage": 		"- one network aggregation",
		"taMessage": 		"- one time aggregation",
		"noNaInCommon": 	"There is no network aggregation in common between the current selected elements.",
		"noTaInCommon": 	"There is no time aggregation in common between the current selected elements."
	},
			
	/* Filter grid panel */
	"filterGridPanel": {
		"title":			"Filters",
		"deleteAll":		"Delete all",
		"deleteAllTip":		"Remove all user rows from the grid",
		"na": 				"N/A",
		"enableColumn": 	"Enable",
		"labelColumn":		"Label",
		"nameColumn":		"Name",
		"productColumn": 	"Product",
		"operatorColumn": 	"Operator",
		"valueColumn":	 	"Value",
		"connectorColumn": 	"Connector",						
		"connectorList": [
			["AND", "AND"],                              
			["OR", "OR"]
		],
		"operator": [                                         
			["Equals to", "Equals to"],
			["Not equals to", "Not equals to"],
			["Less than", "Less than"],
			["Less than or equal", "Less than or equal"],
			["Greater than", "Greater than"],
			["Greater than or equal", "Greater than or equal"],
			["Between", "Between"],
			["Not between", "Not between"],
			["Is null", "Is null"],
			["Is not null", "Is not null"],
			["Is true", "Is true"],
			["Is false", "Is false"]
		],
		"timeOperator": [                                         
			["Equals to", "Equals to"],
			["Not equals to", "Not equals to"],
			["Less than", "Less than"],
			["Less than or equal", "Less than or equal"],
			["Greater than", "Greater than"],
			["Greater than or equal", "Greater than or equal"],
			["Is null", "Is null"],
			["Is not null", "Is not null"]
		],	
		"naOperator": [
			["In", "In"],
			["Not in", "Not in"],
			["Is null", "Is null"],
			["Is not null", "Is not null"],
			["Starts with", "Starts with"],
			["Not starts with", "Not starts with"],
			["Ends with", "Ends with"],
			["Not ends with", "Not ends with"],
			["Contains", "Contains"],
			["Not contains", "Not contains"]
		],
		"hour": ["00:00","01:00","02:00","03:00","04:00","05:00","06:00","07:00","08:00","09:00","10:00","11:00","12:00","13:00","14:00","15:00","16:00","17:00","18:00","19:00","20:00","21:00","22:00","23:00"],	
		"tipDeleteRow": 			"Delete row",
		"defaultDateColumnValue": 	"dd/mm/yyyy",
		"defaultHourColumnValue": 	"hh:mm",
		// 17/01/2014 GFS - Bug 35176 - [REC][T&A CB 5.3.0][TC#TA-57177][Query Builder]: Format of value for Month/Week in Filter panel should be "yyyymm"/"yyyyww", not "mm"/"ww" 
		"defaultWeekColumnValue": 	"yyyyww",
		"defaultMonthColumnValue": 	"yyyymm",
		"defaultBetweenOperator": 	"value1,value2"		
	},
	/* Network selection window */
	"netSelWindow": {		
		"title": "Network element selection",
		"elementList": "List of elements",
		"btSelect": "Select",
		"btCancel": "Cancel",
		"selectedElements": "Selected elements",
		"myFavorites": "My favourites lists",
		"deleteAll": "Delete all",
		"saveToFav": "Save to favourites",
		"deleteElement": "Delete element",
		"deleteFavorite": "Delete favourite",
		"btSave": "Save",
		"btCancel": "Cancel",
		"saveFavTitle": "Save favourites list",
		"saveFavMessage": "Please, enter the name of this favourites list"
	},
	
	/* Errors */
	"errors": {
		"queryAlreadyExist": "1"	
	},
	
	/* Export options window */
	"exportOptionsWindow": {
		"title": "Export options",
		"okButton": "Ok",
		"cancelButton": "Cancel",
		"kpiAndCounters": "Kpi & counters",
		"networkElements": "Network elements"
	}	
});