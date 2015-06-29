<?php
/*
	The purpose of this class is to hold a PDO object and
*/


namespace orm\Database;

use PDO;

class DbConnection
{
	protected $db = null;

	public function __construct($config = array())
	{
		//Set $this->db as the correct driver object
		if ( $config['driver'] == 'mysql') {
			$conn = new Connectors\MySqlConnector;
			$this->db = $conn->connect($config['mysql']);
		} elseif( $config['driver'] == 'sqlite') {
			$conn = new Connectors\SQLiteConnector;
			$this->db = $conn->connect($config['sqlite']);
		}
	}

	public function query($sql = '', $bindings = array(), $fetch_results = true)
	{
		try {
			$preparedQuery = $this->db->prepare($sql);
			if($preparedQuery->execute($bindings)){
				if($fetch_results){
					return $preparedQuery->fetchAll(PDO::FETCH_ASSOC);
				} else {
					return true;
				}
			}else{
				return false;
			}
		} catch(PDOException $e) {
			die($e->getMessage());
		}
	}

	public function lastInsertId($column = null)
	{
		$insert_id = $this->db->lastInsertId($column);
		return $insert_id;
	}
}