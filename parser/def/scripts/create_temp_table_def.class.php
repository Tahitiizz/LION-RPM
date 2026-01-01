<?
/*
 *	@gsm3.0.0.00@
 *
 *	10:06 04/08/2009 SCT
 *		- mise à niveau sur CB 5.0
 *		- la nouvelle classe d'appel aux données n'est pas utilisée : le fichier fait appel à une classe CB pas encore modifiée
 *	10:06 04/08/2009 SCT : amélioration de l'affichage du démon (par la fonction DisplayInDemon et la variable $this->debug)
 *	10:06 04/08/2009 SCT : modification des NA de la famille Roaming "cluster1" devient "plmngp" et "clusterall" devient "plmnall"
 */
?>
<?php
/**
 * Gere la creation des tables pour famille à partir des tables contenant les données issues des fichiers source<br>
 * 11-12-2006 GH : mise à jour automatique des 2 nouveaux niveaux d'agregation 'network' pour les familles Phone (group table 6) et SMS Center (group table 4)
 * 
 * 09-11-2007 SCT : transformation de l'élément "sms_center" en "smscenter" et "phone_number" en "phonenumber"
 *   
 * @package Create_Tables_GSM
 * @author Guillaume Houssay 
 * @version 2.0.1.01
 */

class create_temp_table_def extends create_temp_table
{
	/**
	 * constructeur qui fait appel aux fonctions génériques du Composant de Base
	 */
	function create_temp_table_def()
	{
		parent::create_temp_table();
		// 10:07 04/08/2009 SCT : gestion de l'affichage du debug
		$this->debug = get_sys_debug('retrieve_collect_data');
	} 

	/**
	 * Fonction qui défini sous forme de tableau pour chaque group de table les jointures à affectuer entre les tables contenant les données des fichiers sources
	 * 
	 * @param int $group_table_param identifiant du group table
	 */
	function get_join($group_table_param)
	{
		foreach ($this->net_fields as $net)
		{
			$this->jointure[$net] = "";
			foreach ($this->time_fields as $time)
				$this->specific_fields[$net].=$time.", ";
			$this->specific_fields[$net].=$net;
		}
	}
	
	function get_nas_from_id_gt($family)
    {
		$query = "SELECT agregation
		    FROM sys_definition_network_agregation
		    WHERE family='$family' order by agregation_rank;";
		$resultm = $this->database->execute($query);
		while ($row=$this->database->getQueryResults($resultm,1))
			$results[]=$row["agregation"];
		return $results;
    }
	
	/**
	 * Fonction qui va mettre à jour les tables de topologie de référence en executant des requetes SQL
	 * 
	 * @param int $group_table_param identifiant du group table
	 * @param text $table_object_ref nom de la table topologie de reference pour l'identifiant du group table
	 * @param text $table_object nom de la table TEMPORAIRE topologie de reference pour l'identifiant du group table
	 * @param int $day jour traité
	 * @global ressource identifiant de connection à la BDD
	 */
	function MAJ_objectref_specific($day)
	{
		global $database_connection;

		switch ($group_table_param)
		{

		} 
		if ($sql != "") {
			$res_t = pg_query($database_connection, $sql);
			if (pg_last_error() != '')
				echo pg_last_error() . " " . $sql . ";\n";
			else
				echo pg_affected_rows($res_t) . "=" . $sql . ";\n";
		} 
	} 
} 

?>
