CB 5.2.0.33 setup
- Refer to specific product instruction.

This version results from the merge of 5.1.6.41 CB on 5.2.0 branch.
Please refer to 5.1.6.41 readme file or TVS.

-------------------------------------------------------------------------------
 HISTORY
-------------------------------------------------------------------------------
CB 5.2.0.32 setup
- Refer to specific product instruction.

This version fixes following bugs:
P1:
	* 30464 : [SETUP PRODUCT]: incomplete slave cleaning
	* 30310 : [REC] T&A installation on portal: cannot do data integration sometimes due to right permissions on file_demon 
	* 30834 : [REC]: HTML log demon_yyyymmdd.html is not writeable
	* 30845 : [REC]T&A Gateway does not include user manuals
P2:   
	* 17912 : [SA]: show errors only filter does not work
	* 29463 : [IS][TA] quitter une instance de TA provoque des erreurs dans la 2ième instance

-------------------------------------------------------------------------------   
CB 5.2.0.31 setup
- Refer to specific product instruction.

An at least 2.0.0.05 Portal is needed to use this CB version.
This version includes from the merge of 5.1.6.38 CB on 5.2.0 branch.

This version fixes following bugs:
P1:
	* 30851 : [Query Builder] Updating Gateway does not give all rights to slaves on data table
	* 29927 : Update on Portal with bad IP 
	* 30195 : [INT] Using portal with CAS slows down the GUI 
	* 30718 : [REC][Mixed KPI]: Static alarm creation form uncompleted 
	* 30852 : T&A Gateway product label should not contain a '&' character which is not allowed in setup product 
P2:
	* 30452 : [REC]Migration Gateway duplicate switch to another profile Menu

-------------------------------------------------------------------------------
CB 5.2.0.29 setup
- Refer to specific product instruction.

An at least 2.0.0.05 Portal is needed to use this CB version.

This version fixes following bugs:
P1:
	* 30069 : [REC][v2.1][API PHP]: Insufficient files rights after deployment

-------------------------------------------------------------------------------
CB 5.2.0.28

This version results from the merge of 5.1.6.34 CB on 5.2.0 branch.
Please refer to 5.1.6.34 readme file or TVS.

This version fixes following bugs:
P1:
	* BZ 29912: [PAA 2.1][AVP NA][POC Bouygues] Impossible to log on T&A through portal after several failures (CAS authentication issues)
	* BZ 29675: [REC][5.2.025] Migration master to gateway failed when the sys_definition_selecteur table is empty
	* BZ 29685: [REC][Global parameter][BP] Blank content after saving last tab
P2:
	* BZ 29516: [REC][T&A Core PS 5.1][TA-57609][MIGRATION 5.0.0 - 5.1][CONTEXT]: list of activated menus for client profile erased by context UserProfile list

-------------------------------------------------------------------------------
CB 5.2.0.26

This version results from the merge of 5.1.6.32 CB on 5.2.0 branch.
Please refer to 5.1.6.32 readme file or TVS.

-------------------------------------------------------------------------------
CB 5.2.0.25 setup

This version results from the merge of 5.1.6.29 CB on 5.2.0 branch.
Please refer to 5.1.6.29 readme file or TVS.

-------------------------------------------------------------------------------

CB 5.2.0.24 
This version results from the merge of 5.1.6.27 CB on 5.2.0 branch.
Please refer to 5.1.6.27 readme file or TVS.

This version fixes following bugs:
P1:
	* BZ 27167: [REC][T&A Core-CS - 5.1][MIG 5.0 - 5.1.1]: Missing Procedure dashboard
	* BZ 27347: [REC][T&A Core-CS - 5.2][#TA-62147][MIG][PAA][Multiproduct]: Missing menu bar after patching the master product with the product that contains the Portal Lot2
	* BZ 27379: [REC][T&A CB 52018][TC#TA-57702][Corporate Multi-Product]: Corporate does not collect exported files (from affiliates) after adding as Slave in multi-product
	* BZ 27855: [REC][T&A CB 5.1.6.24][#TA-61509][Corporate][MIG PG8.2 - 9.1]: No data inserted for Corporate during Retrieve process
	* BZ 27149: [REC][T&A CoreCS 52001][TC#TA-56974]: Homepage is not displayed

-------------------------------------------------------------------------------
CB 5.2.0.23 

This version fixes following bugs:
P1:
	* BZ 27925: [REC][T&A GSM 51103][Retrieve process]: All of KPIs have been disabled in 1st retrieve 

-------------------------------------------------------------------------------
CB 5.2.0.22
This version fixes following bugs:
P1:
	* BZ 27854: [REC][T&A LTE 5.2.0.02][#TA-61972][API][Health Indicator]: Cannot login T&A API with astellia_admin account (with connection method)

-------------------------------------------------------------------------------
CB 5.2.0.21
This version results from the merge of 5.1.6.25 on 5.2.0 branch.
Please refer to 5.1.6.25 readme file or TVS.

-------------------------------------------------------------------------------
CB 5.2.0.20
This version fixes following bugs:
CB 5.2.0.19 is limited to 14 days after its packaging date

-------------------------------------------------------------------------------
CB 5.2.0.19 (LIMITED)
- Bugs P1
	* BZ 27152: [REC][T&A CoreCS 52001][TC#TA-56973]: Error on Corporate configuration after migration from CoreCS 5.0/5.1 to 52001
	* BZ 27353: [REC][T&A Core-CS - 5.2][#TA-61966][Master 5.2 and Slave 5.1]: SQL query result are not displayed on Slave 5.1
	* BZ 27378: [REC][T&A Core-CS - 5.2][Corporate]:The website cannot display the page when clicking on Save button
	* BZ 27128: [REC][Core CS 52001][TC#TA-57336]: Can't create a alarm with description contains character '

-------------------------------------------------------------------------------
CB 5.2.0.18
This version results from merge of CB 5.1.6.17 on CB 5.2.0.
So it fixes bugs corrected in CB 5.1.6.18 to 5.1.6.20:

-------------------------------------------------------------------------------
CB 5.2.0.16
This version fixes following bugs:
- Bug P1
	* BZ 27138: [REC][Core CS 52001][TC#TA-57389]: Nothing is showed in Queries List in Query Builder
- Bug P2
	* BZ 27033: [SUP][TA Core CS][AVP 23614]: The logo is not sent to affiliate T&A

-------------------------------------------------------------------------------
CB 5.2.0.15

Un Portail en version 2.0.0.05 minimum est nécessaire pour utiliser cette version du CB.

Cette version corrige les bugs suivants :
- Bugs P1 :
	* BZ 27026 : [REC][Gateway] No menu available after gateway installation

-------------------------------------------------------------------------------
CB 5.2.0.14

Cette version corrige les bugs suivants :
- Bugs P1 :
	* BZ 26542 : [DASHBOARD] : menu contextuel mal géré sous IE
	* BZ 26944 : [TA_GATEWAY] : On perd le paramétrage des users lors de l'ajout d'un produit
	* BZ 26955 : [PROFILE] : l'ajout de profile sur T&A donne lieu à un fichier xml incorrect
	* BZ 26959 : [GATEWAY] : pas de homepage par défaut sur TA Gateway
	* BZ 26949 : [QBV2] : Problème dans l'affichage des NE sélectionnés pour filtre

-------------------------------------------------------------------------------
CB 5.2.0.13

This version includes the 2 new features:
	* Query Builder V2 (since CB 5.2.0.04)
	* PAA Lot 2 Integration (since CB 5.2.0.08)

This version results from merge from CB 5.1.6.17 with CB 5.2.0.12.
So it fixes bugs corrected in CB 5.1.6.16 and 5.1.6.17:

	* BZ 26043	[SUP][T&A OMC Corporate BSS][Aircel india]: Long counter name are cut during automatic mapping.
	* BZ 26316	[EVO][T&A CorePS 5.1][PERF][RETRIEVE] Index creation on sys_aa_base table
	* BZ 26374	[SUP][T&A OMC NSN BSS 5.0][Aircel India]: SELECT can be added at the begining of a KPI formula in the KPI builder
	* BZ 26044	[SUP][T&A OMC Corporate BSS][Aircel India]: Counter used in GTM can be deactivated
	* BZ 24501	[REC][T&C Core CS 5.1][TC#TA-57156][User Manual] wrong diagram at page 26
	* BZ 25900	[SiemensBSS] Bad Compute KPI on several families
	* BZ 26014	[REC][V5.1.1.01] Wrong health indicator name
	* BZ 24368	[REC][T&A Core CS 5.1][GUI layout] Bad layout after uploading topology files
	* BZ 24382	[REC][T&A Core CS 5.1][GUI] Bad layout of data table
	* BZ 25636	[QAL][V1.3]: Size of logo is not taken into account
	* BZ 23708	[DEV][INSTALL]: error messages while running 2_cb_v5.1.p.sql file

-------------------------------------------------------------------------------

- CB 5.2.0.01 to 5.2.0.12

These versions fixe following bugs:

	* BZ 25402 [REC][Multi-product][Query Builder]: unable to export result from  remote slave 
	* BZ 23256	[REC][CB 50604][Browser compatibility][TC#TA-56770][query builder][new aggregation]: List of selected NEs is not updated accordingly
	* BZ 25823	[REC][CB 5.2.0.04][Integrate data]: Can't integrate data after we upgrade CB to 5.2.0.04
	* BZ 25834	[REC][CB 5.2.0.04][TC#TA-61934]: Wrong format of hour in filter of query
	* BZ 25874	[REC][CB 5.2.0.04][TC#TA-61966][Multi with Master(CB 5.2) and Slave(CB >=5.1.6)]: Can not active multi with message: Base component version is invalid
	* BZ 25927	[REC][CB 5.2.0.04] Unable to use the dblink feature
	* BZ 26004	[REC][CB 5.2.0.04][TC#TA-61967][Mixed KPI product]: Can not save & configure NA for creating family mixed kpi with blank page
	* BZ 26197	[REC][CB5.2.0][Gateway] Missing new menus in T&A Gateway
	* BZ 26293	[REC][CB 5.2.0.08][TC#TA-61969][Corporate]: Can't see menu after setup Corporate
	* BZ 26214	[REC][users synchronization]: users existing on PAA are not deleted from T&A even if they have no right for the T&A
	* BZ 26259	[REC][CB 5.2.0.08][TC#TA-61905][Right Panel][Exports panel]: No NEs in exported files at most of the family (except Cell, HPLMN )
	* BZ 26261	[REC][CB 5.2.0.08][TC#TA-62150]: Can install CB 5.2.0.08 on server has Redhad 4.8
	* BZ 26279	[REC][CB 5.2.0.08][TC# TA-62082][Export Panel][Auto export deletion]:CSV export older than x days is not removed automatically
	* BZ 26288	[REC][CB 5.2.0.08][TC#TA-62080][API]: Functions do not exist
	* BZ 26292	[REC][CB 5.2.0.08][TC#TA-62042][T&A Gateway]: Error in installation process - Both on PG 8.2 and PG 9.1
	* BZ 26226	[REC][CB 5.2.0.08][Center panel][Export button]: Display a pop up with the incorrect message
	* BZ 26406	[REC][CB 5.2.0.09][TC#TA-62162]: Have no menu bar of Gateway product
	* BZ 26260	[REC][CB 5.2.0.08][TC#TA-62164]: Can login to T&A app by deleted user
	* BZ 26496	[DEV][PAA]: CB uses bad appli & profile GUID
	* BZ 26498	[REC][PROTOCOL][PAA]: protocol test always fail with PAA authentication
	* BZ 26419	[REC][CB 5.2.0.09][TC#TA-62151]: Can't mount a context contains profile.csv
	* BZ 26417	[REC][CB 5.2.0.09][TC#TA-62153]: Can't open login page when PAA unavailable
	* BZ 26509	[REC][CB 5.2.0.11][TC#TA-62082][Query Builder][clean_files.php script]: CSV export older than x days is not removed when we run this script automatically
	* BZ 26516	[REC][CB 5.2.0.11][TC# TA-62162][Multi-product]:Users’ table is not updated after the report sender process is launched.
	* BZ 20772	[DEV][ROAMING 5.0.4] Virtual cells are visible in query builder result
	* BZ 21709	[Query Builder] condition on multiple NAs might not be taken into account
	* BZ 21838	[Query Builder][Firefox]: Clear saved Query does not clear Order By parameter
	* BZ 22481	[DEV] Pas de résultats lors de l'utilisation d'une formule + NA personnalisé
	* BZ 21514	[REC][T&A Core PS 5.1][TC#TA-57389][Query Builder] the next Condition textboxes
	* BZ 21817	[REC][T&A CB 5.1][query builder]: save query popup not well managed
	* BZ 22258	[REC][T&A CorePS 5.1][TC#TA-57389][Query Builder][Chrome]: 'Query Builder' function does not active on Chrome browser.
	* BZ 23486	[REC][T&A CB 5.1.4][Chrome 13][Query Builder] Wrong names for selected items
	* BZ 25822	[REC][CB 5.2.0.04][TC#TA-61939]: Can't press Del. key to delete element
	* BZ 25838	[REC][CB 5.2.0.04][TC#TA-61938]: Function and Group is not disable (on GUI) whe we choose "Disable function" button
	* BZ 25841	[REC][CB 5.2.0.04][TC#TA-61940]: Can't execute query have format of date is "today"
	* BZ 25844	[REC][CB 5.2.0.04][TC#TA-61907-62082][clean_files.php]: bug in the Linux command used to remove file
	* BZ 25876	[REC][CB 5.2.0.04][Filter panel][Search feature]: Display all raw counters and KPIs when search condition contains '%'
	* BZ 25879	[REC][CB 5.2.0.04][Move Popup]: Can't move popup back if we move it to out of window
	* BZ 26005	[REC][CB 5.2.0.04][TC#TA-61969][corporate product]: Setup Corporate screen should be displayed after saving
	* BZ 26175	[REC][DOC] Missing recommended browser in User Manual
	* BZ 25835	[REC][CB 5.2.0.04][Scrollbar & Tooltip]: Can not drag the vertical bar
	* BZ 26286	[REC][CB 5.2.0.08][TC#TA-61664][Test Case]: Test case TA-61664 must be updated
	* BZ 26296	[REC][CB 5.2.0.08][TC#TA-61971][Migration]: Query Builder V1 does not keep available after migration to CB 52008
	* BZ 26213	[REC][CB 5.2.0.08][Left panel][RAW list/KPI list][Scrollbar][FF/IE]: Bad GUI
	* BZ 26278	[REC][CB 5.2.0.08][TC#TA-61958]: Warning message is incorrect for invalid query on 3rd families
	* BZ 26282	[REC][CB 5.2.0.08][TC#TA-62084 ][Export Panel][FF/IE]: 'Delete' icon is not displayed when user log in application
	* BZ 26386	[REC][CB 5.2.0.09][Query Builder 2][Center Pannel]:A red triangle is still displayed when unchecked GROUP column
	* BZ 26404	[REC][CB 5.2.0.09][TC#TA-62149]: User "astellia_admin" still exist in user list after synchronize
	* BZ 26405	[REC][CB 5.2.0.09][Query Builder 2][Right Pannel]|[Firefox on Win7]:Missing icon when creating many queries
	* BZ 26423	[REC][CB 5.2.0.09][TC#TA-62167]: "Switch to another profile" icon still exist at top banner when we login to app by user has only a profile
	* BZ 24669	[REC][T&A ROAMING GSM 5.0.7][GUID][Alarm]:Got problem on new static/dynamic/TWL alarms screen.
	* BZ 25883	[REC][CB 5.2.0.04][TC#TA-61949][IE 7.0]: Can't copy SQL query on IE 7.0
	* BZ 26387	[REC][CB 5.2.0.09][TC#TA-62146]: Product has no error message when we enter invalid IP of CAS mode
	* BZ 26487	Admin tool menu is not displayed
	* BZ 26409	[REC][CB 5.2.0.09][TC#TA-62082][Query Builder][clean_files.php script]: CSV export older than x days is not removed by this script
	* BZ 26490	[REC][Query Builder v2][IE9]: item navigation under menu line
	* BZ 25986	[SUP][TA Cigale GSM][AVP 23043][EMTEL]: The way to make non iterative alarm is not specified.
	* BZ 26506	[REC][CB 5.2.0.11][TC#TA-61949][IE 7.0]: Can edit SQL query in mode "View SQL" on IE 7
	* BZ 17753	[REC][DE Firefox][Query Builder] : Drag&Drop non fonctionnel sous Chrome
	* BZ 10563	[REC][T&A Cb 5.0][Query Builder]: impossible de supprimer le raw/kpi de tri
	* BZ 6246	[REC][CB 4.0][B6246][QUERYBUILDER]: Query builder : si le résultat contient un grand nombre de ligne, le graph n'est pas généré
	* BZ 10792	[REC][T&A Cb 5.0][Query Builder] : pas de tooltip sur les élements créés dans 'My Network Aggregation'
	* BZ 11030	[REC][T&A Cb 5.0][Query Builder] : en mono produit, affichage d'un " - " avant le nom de la famille
	* BZ 11289	[REC][T&A CB 5.0][TC#15020][TS#UC23-CB40][TP#1][QUERY BUILDER]: manque colonne date
	* BZ 13907	[REC][T&A Gb 5.0][My Profile] IHM pas ergonomique
	* BZ 25889	[REC][CB 5.2.0.04][TC#TA-61949]: Have many errors in specification
	* BZ 25836	[EVO][REC][CB 5.2.0.04]: Should allow to resize Raw/KPI information popup
	* BZ 25839	[EVO][REC][CB 5.2.0.04][Left panel][Filter panel]: Tooltip should be displayed when the mouse is moved to product name
	* BZ 25870	[REC][CB 5.2.0.04][TC#TA-61945]: Value column is not reset automatically when we change the Operator
	* BZ 25877	[REC][CB 5.2.0.04][Filter panel]:Uncheck all families of a product => Product should be unchecked.
