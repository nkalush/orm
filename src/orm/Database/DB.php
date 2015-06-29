<?php

namespace orm\Database;

class DB
{
	protected $db;
	protected $data;
	protected $config;
	protected $queries;

	public function __construct($config, $connection = NULL)
	{
		$this->config = $config;

		if (is_object($connection) /*&& get_class($config) == "DbConnection"*/) {
			$this->db = $connection;
		} elseif (!empty($config)) {
			$this->db = new DbConnection($config);
		}
	}

	static public function raw($expression='')
	{
		return new Expression($expression);
	}

	static public function Instance($config = null, $connection = null, $reset = false )
	{
		static $instance = null;

		if ($instance === null || $reset === true) {
			$instance = new DB($config, $connection);
		}

		return $instance;
	}

	public function getConfig()
	{
		return $this->config;
	}

	public function query($sql = '', $bindings = array(), $fetch_results = true)
	{
		$this->queries[] = array(
			'sql'=>$sql,
			'bindings'=>$bindings
		);
		if($fetch_results) {
			return $this->db->query($sql, $bindings);
		} else {
			return $this->db->query($sql, $bindings, $fetch_results);
		}
	}

	public function lastInsertId($column = null)
	{
		$insert_id = $this->db->lastInsertId($column);
		return $insert_id;
	}

	public function runtime_info()
	{
		return $this->queries;
	}

	public function __call($method=null, $params=null)
	{
		$response = call_user_func_array(array($this->db, $method), $params);
	}
}