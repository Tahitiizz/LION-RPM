===================================================================

  T&A Gateway

    Version 5.3.3.07
    Delivered on: 27/01/2015

===================================================================

Content:
  T&A Gateway             : V 5.3.3.07
  Base Component          : V 5.3.3.07
  Parser                  : Blank product V 5.3.3.07
  Context                 : TA Gateway V 5.3.3.07

Compatible environments:
  Red Hat 5.5 / Postgres 8.2, 9.1
  Red Hat 6.2 / Postgres 9.1
  Red Hat 6.5 / Postgres 9.1

Please refer to specific product instruction for setup.
At least 2.2.0.15 Portal is needed to use this CB version.

Bugs fixed in this version:
 - Packaging
 
-------------------------------------
HISTORY
-------------------------------------
v 5.3.3.06
P2
 - 46081: [SUP][T&A CB][#48966][MeditelMaroc]: Link to NE, incorrect filtering on ServiceKey, based on label instead of ID
 - 45086: [SUP][NA][Webservice] : WSDL access return unconditionally "Couldn't bind to service"

v 5.3.3.05
P1
 - 44707: [REC][CB 5.3.3.04][Documentation][User Manual]Some error lines in User Manual file  
P2
 - 44665: [VAL][CB 5.3.3][Gateway][Setup] : Setup does not check hostname resolution before carrying on with installation process 

v 5.3.3.04
P1
 - 43759: [REC][CB 5.3.3.02][TC #TA-56768][IE 11 Compatibility] Missing family's name in Query Builder GUI 
P2
 - 42876: [REC][CB 5.3.3.00][taSolrh65][TC#TA-62670] Login successfully to system after 3 incorrect password attempts 
 - 43757: [REC][CB 5.3.3.02][TC #TA-56775][IE 11 Compatibility] Missing family's combox in Graph builder GUI 
 - 44327: [QAL][CB 5.3.3][Gateway][Doc] : No Installation/Uninstallation/Upgrade Manual available for T&A Gateway 
 - 44552: [VAL][CB 5.3.3][Portal][configure] : Script configurePortal.sh omit "http://' header when registering to portal in silence mode 
 P3
 - 44339: [QAL][CB 5.3.3][ta_gsm][log] : directory ./log contain an old file Trending\&Aggregation\ Cigale\ GSM_1288775080.log 
 - 44547: [VAL][CB 5.3.3][Gateway][Doc] : User and Admin manual have some typos 
 
 - This version includes the merge of 5.3.2.07 CB.
 
v 5.3.3.03
New functionalities included in this version:
 - Mantis 2547 [DE MKT] Inscription automatique de la documentation dans le portail
 - Mantis 2561 [DE MKT] Reverse Proxy - Prise en compte hostname dans les setups TA (quick_install+configurePortal)
 - Mantis 2562 [DE MKT] Reverse Proxy - Lors de l’enregistrement dans le portail, proposer un lancement en HTTP ou HTTPS
 - Mantis 5428 [Transverse] Support Firewall
 - Mantis 4700 [Transverse] [Hardware-HP] - Support HP Gen8 V2
 - Mantis 5888 [Transverse] Viewers compatibility - Firefox (>16)
 - Mantis 3015 [Transverse] Viewers compatibility - IE10
 - Mantis 3014 [Transverse] Viewers compatibility - IE11
 - Mantis 5449 Amélioration de l'historique des données
 - Mantis 5450 Reprocess des données
 - Mantis 5451 Lancement manuel des process 

P1
 - 42894: [REC][CB 5.3.3.00][TC #TA-61558][Alarm SMS] SMS aren't sent to recipients for dynamic alarm 
P2
 - 42640: [REC][CB 5.3.3.00][TC #TA-61360][Admin Manual] New logo isn't updated from page 58 to the end. 
 - 42921: [REC][CB 5.3.3.00][TC #TA-61558][Alarm SMS] Warning message which appears in tracelog is not correctly 
 
 - This version includes the merge of 5.3.2.04 CB.
