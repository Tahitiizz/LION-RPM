<?php

include_once(dirname(__FILE__)."/../../../../php/environnement_liens.php");

// recherche du nom du parser
$module = strtolower(get_sys_global_parameters("module"));

include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/LoadData.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/DatabaseServices.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/FlatFile.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/Parser.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/LoadTopology.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/Tools.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/Counter.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/Parameters.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/CreateTempTableCB.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/CreateTempTable.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/Collect.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/TopoParser.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/FileType.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/ProcessManager.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/ConditionProvider.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/TempTable.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/ExecCopyQuery.class.php");
include_once(REP_PHYSIQUE_NIVEAU_0 . "parser/$module/scripts/lib/TempFiles.class.php");

?>