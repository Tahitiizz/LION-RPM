<?php

/**
 * Alarm Db provider
 * @package Data\Db
 */
class AlarmDbProvider
{
	/**
	 * @var \DataBaseConnection
	 */
	private $cnx;

	/**
	 * AlarmDbProvider constructor.
	 * @param \DataBaseConnection $cnx
	 */
	public function __construct($cnx)
	{
		$this->cnx = $cnx;
	}

	/**
	 * Update alarm acknowledges by oids
	 * @param string[] $oids
	 */
	public function updateAckByOids($oids)
	{
		$oids_with_quotes = array();
		foreach ($oids as $oid) {
			$oids_with_quotes[] = "'$oid'";
		}
		$oids_string = implode(",", $oids_with_quotes);
		$query = "
				UPDATE edw_alarm
				SET acknowledgement = 1
				WHERE oid in ($oids_string)
			";
		return $this->cnx->execute($query);
	}
}