-------------------------------------------------------------------------------

CB 5.3.1.11 setup
- This version includes from the merge of 5.3.0.21 CB on 5.3.1 branch.

An at least 2.2.0.15 Portal is needed to use this CB version.

This version fixes following bugs:
P1:
	* BZ 38199: [REC][CorePS 5.3.1.01][TC#TA-62406][Topology]: The backup file is NOT created after topology delete process is performed 
	* BZ 37709: [REC][Core PS 5.3.1.01][TC #TA-62564][Link to NE] The number of results in Nova Explorer aren't identical with the number in Activity Analysis
	* BZ 23077: [SUP][T&A OMC Ericsson BSS][Airtel Zambia][Mixed KPI] Compute Raw blocked after counter SYNCHRONIZE       
P2:
	* BZ 37891: [SUP][T&A Gateway][Econet Zimbabwe][AVP 40118][Context] : Backup before mount context is corrupted 
	
-------------------------------------------------------------------------------

===================================================================

	T&A Composant de Base
	Version 5.3.1.09
	(c) Astellia 21/10/2013

===================================================================

   - Refer to specific product instruction.
   - An at least 2.2.0.15 Portal is needed to use this CB version.
   - This version includes the merge of 5.3.0.20 CB on 5.3.1 branch.

New functionnalities:
	* Improvement on NE filtering in Gis export
	* Improvement on NE filtering in selector : NE parent filter on children
	* Link from T&A to Nova Explorer
	* Performance optimization
	* New health indicator for supervision (license key)
	* Phone number management done by portal
	* Change SNMP community through admin user interface
	* Configuration of the subject of emails alerts
	* IE/FF compatibility (exigence transverse)

This version fixes following bugs:
(As first 5.3.1 Delivery, this list only includes Bugs with version prior to 5.3.1)

P1 : 
	* 35923 - [SUP][5.3.1][#NA] : Data mixed KPI are not calculated for Network and Vendor level
	* 37046 - [REC][IU 5.3.1.01][TC #TA-62466][Corporate] Data tables for Handover 4G family aren't created in database
	* 37173 - [SUP][5.3.0.12][Mixed-KPI] Raw and KPI values are multiplied in mixed-KPI application
	* 37322 - [QAL][5.3.1.08] Link to Nova Explorer is incorrect when containing several interfaces

P2:
	* 35927 - [SUP][5.3.1][#NA]: Warning : Division by zero in Source Availability
	* 36748 - [SUP][T&A CB][#38959]: inadequate Virtual_Cell label
	* 36876 - [SUP][CB5.3.1][#NA]: Download Log does not work


