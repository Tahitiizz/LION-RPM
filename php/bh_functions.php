<?
/*
*	@cb50000@
*
*	27/07/2009 - Copyright Astellia
*
*	27/07/2009 BBX : adaptation CB 5.0 : 
*		=> global $database_connection => global $database
*/
?>
<?
/*
*	@cb21201@
*
*	14/03/2007 - Copyright Acurio
*
*	Composant de base version cb_2.1.2.01
*/
?>
<?
/*
*	@cb2000b_iu2000b@
*
*	20/07/2006 - Copyright Acurio
*
*	Composant de base version cb_2.0.0.0
*
*	Parser version iu_2.0.0.0
*/
?>
<?

/* - 17 08 2006 : creation du fichier (MD)
  *    >> Ce fichier contient l'ensemble des fonctions de base liees a la Busy Hour
  *
  * - 31 08 2006 : MD - BH activation
  *    >> ajout de la fonction bhIsDeployed() : retourne vrai si la busy hour est deployee en base
  *    >> ajout de la fonction getBHFormula() : retourne la formule de la bh a utiliser pour une famille donnee
  *    >> ajout de la fonction addBHDefinition() : ajoute une definition de BH pour une famille donnee
  *    >> ajout de la fonction deleteBHDefinition() : supprime la definition d'une BH pour une famille donnee
  *    >> ajout de la fonction getBHInfos() : retourne les infos concernant une definition de BH
  *    >> ajout de la fonction updateBHDefinition() : met a jour la def. d'une BH
  *    >> ajout de la fonction bhIsDefined() : retourne vrai si une BH est deja definie pour une famille donnee
  *    >> ajout de la fonction bhIsDeployedFor() : retourne vrai si la busy hour est deployee en base pour une famille donnee
  */
  
// Si l'instance de connexion à la base n'existe pas, on se conencte à la base locale
if(!isset($database)) {
    // 10/11/2011 BBX BZ 24534 : remplacement de new DataBaseConnection() par Database::get Connection()
    $database = Database::getConnection();
}

/* Ajoute dans la table sys_definition_time_bh_formula la definition d'une bh pour la famille $family
  * @param $family - la famille ou l'on defini une BH
  * @param $indicator_type - RAW ou KPI (en majuscule)
  * @param $indicator_name - le nom du raw ou kpi a partir duquel la BH est calculee
  * @param $bh_parameter - 1 pour un calcul de bh classique et 3 pour une 3DBH
  * @param $network_aggreg - standard ou aggregated
  * @param $comment - commentaire
  */
function addBHDefinition($family,$indicator_type,$indicator_name,$bh_parameter,$network_aggreg,$comment)
{
	global $database;
	$res=false;
	$query="INSERT INTO sys_definition_time_bh_formula
			(family,bh_indicator_type,bh_indicator_name,bh_parameter,bh_network_aggregation,comment)
			VALUES
			('$family','$indicator_type','$indicator_name',$bh_parameter,'$network_aggreg','$comment')";
	if($database->execute($query))
		$res=true;
	return $res;
}

/* Met a jour la definition de la BH pour une famille donnee
  * @param $family - la famille dont on veut mettre a jour la definition de la BH
  * @param $infos - les infos a mettre a jour sous la forme d'un tableau associatif (nom_colonne => valeur)
  */
function updateBHDefinition($family,$infos)
{
	global $database;
	$res=false;

	$set="";
	foreach($infos as $field=>$value)
		$set[]="$field='$value'";

	$query="UPDATE sys_definition_time_bh_formula
			SET ".implode(',',$set)."
			WHERE family='$family'";

	if($database->execute($query))
		$res=true;
	return $res;
}

/*
  * Supprime la definition de la BH pour la famille $family
  */
function deleteBHDefinition($family)
{
	global $database;
	$res=false;
	$query="DELETE FROM sys_definition_time_bh_formula WHERE family='$family'";
	if($database->execute($query))
		$res=true;
	return $res;
}

/*
  * Retourne vrai si une Bh est definie pour la famille $family
  */
function bhIsDefined($family)
{
	global $database;
	$res=false;
	$query="SELECT oid FROM sys_definition_time_bh_formula WHERE family='$family'";
	$result=$database->execute($query);
	if($database->getNumRows() > 0)
		$res=true;
	return $res;
}

/*
  * Retourne les infos de la BH pour la famille $family
  * @return array - un tableau vide si aucune BH n'est definie pour $family
  *                            un tableau associatif dont les clefs sont les noms des colonnes de la table sys_definition_time_bh_formula
  */
function getBHInfos($family)
{
	global $database;
	$infos=array();
	$query="SELECT * FROM sys_definition_time_bh_formula WHERE family='$family'";
	$result=$database->execute($query);
	if($database->getNumRows() > 0) {
		$row=$database->getQueryResults($result,1);
		$infos=$row;
	}
	return $infos;
}


/* Retourne la formule de la BH associee a la famille passee en parametre
 * @param $family - la famille dont on cherche la formule de BH
 */
function getBHFormula($family)
{
    global $database;
    // 12:06 15/10/2010 SCT : BZ 18427 => Désactivation de compteur utilisé pour la BH possible
    //  + retour de la méthode "getBHFormula" sous forme de tableau (la gestion des messages d'erreur)
    //  + recherche si le Raw/Kpi est activé/désactivé ou supprimé
    // DEBUT BUG 18427
    $retour = array();
    $messageTraceLog = null;
    $queryAllInfos = "SELECT * FROM sys_definition_time_bh_formula WHERE family = '".$family."'";
    $resultAllInfos = $database->execute($queryAllInfos);
    if($database->getNumRows() > 0)
    {
        // recherche des infos de la famille
        $infoFamily = get_family_information_from_family($family);

        $row = $database->getQueryResults($resultAllInfos,1);
        $bhIndicatorName = $row['bh_indicator_name'];
        $bhIndicatorType = $row['bh_indicator_type'];

        $query = "
            SELECT
                CASE WHEN t0.bh_indicator_type='KPI' THEN  t2.kpi_formula
                     ELSE t1.edw_field_name
                END as bh_formula,
                CASE WHEN t0.bh_indicator_type='KPI' THEN  t2.on_off
                     ELSE t1.on_off
                END as on_off
            FROM
                sys_definition_time_bh_formula t0,
                sys_field_reference t1,
                sys_definition_kpi t2
            WHERE
                t1.edw_group_table IN (SELECT edw_group_table FROM sys_definition_group_table WHERE family='$family') AND
                t2.edw_group_table = t1.edw_group_table AND
                t0.family='$family' AND
                (CASE WHEN t0.bh_indicator_type='KPI' THEN t2.kpi_name=t0.bh_indicator_name
                    ELSE t1.edw_field_name=t0.bh_indicator_name
                END)
            LIMIT 1";
        $result = $database->execute($query);
        if($database->getNumRows() > 0)
        {
            $row=$database->getQueryResults($result,1);
            $retour['formula'] = $row['bh_formula'];
            $retour['error'] = '';
            if($row['on_off'] == 0)
            {
                $retour['formula'] = '';
                // préparation d'un message pour le tracelog
                $retour['error'] = __T('A_COMPUTE_MSG_ERROR_BH_FORMULA_RAW_KPI_OFF', $infoFamily['family_label'], ($bhIndicatorType == 'KPI' ? 'Kpi' : 'Counter'), $bhIndicatorName);
            }
        }
        else // cas de non réponse à la requête précédente car Raw/Kpi supprimé des tables sys_field_reference/sys_definition_kpi
        {
            $retour['formula'] = '';
            $retour['error'] = __T('A_COMPUTE_MSG_ERROR_BH_FORMULA_RAW_KPI_DEL', $infoFamily['family_label'], ($bhIndicatorType == 'KPI' ? 'Kpi' : 'Counter'), $bhIndicatorName);
        }
    }
    return $retour;
    // FIN BUG 18427
}

/* Retourne vrai si la bh est deployee en base
  * Si au moins un TA de type BH est trouve dans la table sys_definition_group_tables_time
  * alors cel signifie que la BH est deployee pour tous les groupes de table
  */
function bhIsDeployed()
{
	global $database;
	$res=false;
	$query="SELECT oid FROM sys_definition_group_table_time
			WHERE time_agregation IN (SELECT agregation FROM sys_definition_time_agregation WHERE bh_type='bh')";
	$result=$database->execute($query);
	if($database->getNumRows() > 0)
		$res=true;
	return $res;
}

/* Retourne vrai si la bh est deployee en base pour $family
  * Voir fonction bhIsDeployed()
  * @param $family - la famille dont on veut savoir si la BH est deployee
  */
function bhIsDeployedFor($family)
{
	global $database;
	$res=false;
	$query="SELECT oid FROM sys_definition_group_table_time
			WHERE time_agregation IN (SELECT agregation FROM sys_definition_time_agregation WHERE bh_type='bh') AND
				  id_group_table IN (SELECT id_ligne FROM sys_definition_group_table WHERE family='$family')";
	$result=$database->execute($query);
	if($database->getNumRows() > 0)
		$res=true;
	return $res;
}

/* Retourne un element de parametrage de la busy hour pour la famille de id_group_table
  *
  * Liste des parametres disponibles :
  * - bh_formula : la formule a partir de laquelle on recherche l'heure la plus chargee
  * - bh_network_aggregation (standard ou aggregated) :  le mode de calcul de la BH pour l'agregation reseau
  *   ex : standard     >> *_bsc_day_bh calcule a partir de *_bsc_hour | *_bsc_week_bh calcule a partir de *_bsc_day_bh ...
  *          aggregated >> *_bsc_day_bh calcule a partir de *_cell_day_bh | *_bsc_week_bh calcule a partir de *_bsc_day_bh ...
  * - bh_time_aggregation (normal ou 3DBH) :  le mode de calcul de la BH pour l'agregation temporelle
  *   ex : normal  >> heure la plus charge du jour, de la semaine ou du mois
  *          3DBH    >> la moyenne des 3 jours les plus charges de la semaine ou du mois
  *
  * @param id_group_table - un identifiant de groupe de table
  * @param param le nom d'une colonne de la table sys_definition_time_bh_formula
  * @return un parametre de configuration de la busy hour courante
  */
function getBHParam($id_group_table,$param)
{
	global $database;
	$value=null;
	$query="SELECT t0.$param
			FROM sys_definition_time_bh_formula t0,sys_definition_group_table t1
			WHERE t1.id_ligne=$id_group_table AND t0.family=t1.family";
	$result=$database->execute($query);
	if($database->getNumRows() > 0) {
		$row=$database->getQueryResults($result,1);
		$value=$row[$param];
	}
	return $value;
}

/* Retourne le niveau d'agregation temporelle de type BH minimum
  * @param bh_type le type de busy hour ('bh' par defaut)
  * @return le niveau TA minimum
  */
function getMinTimeBHLevel($bh_type='bh')
{
	global $database;
	$time=null;
	$query="SELECT agregation
			FROM sys_definition_time_agregation
			WHERE bh_type='$bh_type'
			ORDER BY agregation_rank
			LIMIT 1";
	$result=$database->execute($query);
	if($database->getNumRows() > 0) {
		$row=$database->getQueryResults($result,1);
		$time=$row['agregation'];
	}
	return $time;
}

/* Retourne la valeur du champ passe en parametre dans la table sys_definition_time_agregation
  * @param time - le nom d'un niveau d'agregation de temps (hour, day, week, day_bh...)
  * @param field - le nom d'une colonne de la table sys_definition_time_agregation
  * @return la valeur du champ
  */
function getTimeFieldValue($time,$field)
{
	global $database;
	$field_value=null;
	$query="SELECT $field FROM sys_definition_time_agregation WHERE agregation='$time'";
	$result=$database->execute($query);
	if($database->getNumRows() > 0) {
		$row=$database->getQueryResults($result,1);
		$field_value=$row[$field];
	}
	return $field_value;
}

/* Retourne vrai si $time un niveau de temps de type Busy Hour
  * @param time : le niveau de temps dont on veut savoir s'il est de type BH (ex : day ou week_bh...)
  * @return vrai si le niveau est de type BH et faux sinon
  */
function isATimeBH($time)
{
	global $database;
	$bh=false;
	$query="SELECT oid FROM sys_definition_time_agregation
			WHERE agregation='$time' AND bh_type='bh'";
	//print $query.'<br/>';
	$result=$database->execute($query);
	if($database->getNumRows() > 0)
		$bh=true;
	return $bh;
}

/* Retourne les niveaux de temps de type BH definis dans sys_definition_time_agregation
  * @param bh_type le type de niveau de temps (bh par defaut)
  * @return un tableau contenant les niveaux de temps de type bh_type
  */
function getTimesBH($bh_type="bh")
{
	global $database;
	$times=array();
	$query="SELECT agregation FROM sys_definition_time_agregation WHERE bh_type='$bh_type'";
	//print $query;
	$result=$database->execute($query);
	while($row = $database->getQueryResults($result,1))
		$times[]=$row["agregation"];
	return $times;
}
?>
