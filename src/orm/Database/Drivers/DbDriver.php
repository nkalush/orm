<?php

namespace orm\Database\Drivers;

use PDO;

class DbDriver
{
	protected $connection = null;

	protected $identifier_wrap = '"';
	protected $supported_functions = array('*', 'NULL', 'COUNT');

	protected $base_where = array();
	protected $base_join = array();

	public function __construct()
	{
		$this->reset();
	}

	protected function escape_table_name($name)
	{
		return $this->escape_key($this->prefix.$name);
	}

	protected function escape_key($key='')
	{
		$iw = $this->identifier_wrap;
		if ( !empty($key) ) {
			if (gettype($key) == 'object' && get_class($key) == 'orm\Database\Expression') {
				return $key;
			} elseif ( substr_count($key,", ") > 0 || substr_count($key,",") > 0 ) {
				if (substr_count($key,", ") > 0) {
					$keys = explode(", ",$key);
				} else if (substr_count($key,",") > 0) {
					$keys = explode(",",$key);
				}
				$key_array = array();
				foreach($keys as $k){
					$key_array[] = $this->escape_key($k);
				}
				return implode(", ",$key_array);
			} else {
				//Handle Aliases of functions
				if ( preg_match("/(.*\(.*\)) as (.*)/i", $key, $matches) ) {
					return $matches[1]." AS ".$matches[2];
				//handle strait row aliases
				} elseif ( preg_match("/(.*) as (.*)/i", $key, $matches) ) {
					return $this->escape_key($matches[1])." AS ".$matches[2];
				//handle things that are not aliases (column names)
				} else {
					//protect identifier
					$key = str_replace($iw, $iw.$iw, $key);

					//add identifier to keys with multiple parts
					if (substr_count($key,".") > 0) {
						$key = explode(".",$key);

						$key_parts = array();
						foreach($key as $k){
							$key_parts[]=$this->escape_key($k);
						}

						$key = implode(".",$key_parts);
					} else {
						//prevent adding ticks if the key is a reserved word
						$matches = preg_match("/(.+)\(.+\)/", $key, $func_check);
						if( in_array($key, $this->supported_functions) || ($matches && in_array($func_check[1], $this->supported_functions))) {
							$key = $key;
						} else {
							$key = $iw . $key . $iw;
						}
					}
					return $key;
				}
			}
		} else {
			return false;
		}
	}

	protected function escape_value($value)
	{
		return $value;
	}

	public function reset()
	{
		$this->distinct = '';
		$this->command = null;
		$this->select = array();
		$this->prefix = '';
		$this->set = array();
		$this->where = $this->base_where;
		$this->having = array();
		$this->group_by = array();
		$this->order_by = array();
		$this->offset = 0;
		$this->limit = null;
		$this->join = $this->base_join;
		$this->from = null;
		$this->bindings = array();
	}

	public function distinct()
	{
		$this->distinct = "DISTINCT ";

		return $this;
	}

	public function select($select=null)
	{
		$this->command = "SELECT";
		if (!empty($select)) {
			if ( is_array($select) ) {
				foreach ( $select as $k=>$s ) {
					$this->select[] = $this->escape_key($s);
				}
			} else {
				$this->select[] = $this->escape_key($select);
			}
		}

		return $this;
	}

	public function build_select()
	{
		if (!empty($this->select)) {
			return implode(', ',$this->select);
		} else {
			return '*';
		}
	}

	public function update($data = null)
	{
		$this->command = "UPDATE";
		if (is_array($data)) {
			$this->set = array_merge($this->set, $data);
		}

		return $this;
	}

	public function insert($data = null)
	{
		$this->command = "INSERT";
		if (is_array($data)) {
			$this->set = array_merge($this->set, $data);
		}

		return $this;
	}

	public function delete()
	{
		$this->command = "DELETE";

		return $this;
	}

	public function setTablePrefix($prefix="")
	{
		$this->prefix = $prefix;

		return $this;
	}

	public function from($table_name)
	{
		$this->from = $table_name;

		return $this;
	}

	public function set($key=null, $value=null)
	{
		if ($key !== null && $value !== null) {
			$this->set[$key] = $value;
		}

		return $this;
	}

	public function build_set()
	{
		$return = '';
		if (!empty($this->set)) {
			$sets = array();
			foreach ($this->set as $k=>$v) {
				$matches = preg_match("/(.+)\(.*\)/", $v, $func_check);
				if( in_array($v, $this->supported_functions) || ($matches && in_array($func_check[1], $this->supported_functions))) {
					$sets[] = $this->escape_key($k)." = ".$v;
				}else{
					$sets[] = $this->escape_key($k)." = ?";
					$this->bindings[] = $v;
				}
			}
			$return = " SET ".implode(", ",$sets);
		}
		return $return;
	}

	public function build_values()
	{
		$return = '';
		if (!empty($this->set)) {
			$keys = $values = array();
			foreach ($this->set as $k=>$v) {
				$keys[] = $this->escape_key($k);

				$matches = preg_match("/(.+)\(.*\)/", $v, $func_check);
				if( in_array($v, $this->supported_functions) || ($matches && in_array($func_check[1], $this->supported_functions))) {
					$values[] = $v;
				}else{
					$values[] = "?";
					$this->bindings[] = $v;
				}
			}
			$return = ' ('.implode(', ',$keys).') VALUES ('.implode(', ',$values).')';
		}
		return $return;
	}

	public function base_where($query = null, $operator = null, $value = null)
	{
		$this->add_where($query, $operator, $value, "AND", "WHERE", true);
		return $this;
	}

	public function where($query = null, $operator = null, $value = null)
	{
		$this->add_where($query, $operator, $value, "AND", "WHERE");
		return $this;
	}

	public function or_where($query = null, $operator = null, $value = null)
	{
		$this->add_where($query, $operator, $value, "OR", "WHERE");
		return $this;
	}

	public function having($query = null, $operator = null, $value = null)
	{
		$this->add_where($query, $operator, $value, "AND", "HAVING");
		return $this;
	}

	private function add_where($query = null, $operator = null, $value = null, $andor = "AND", $function = "WHERE",  $add_to_base = false)
	{
		$add = array();

		if (!empty($query) && !is_array($query) && !empty($operator) && $value !== null) {
			$add[]=array(
				"key"=>$this->escape_key($query),
				"operator"=>$operator,
				"value"=>$this->escape_value($value),
				"connector"=>$andor
			);
		} elseif (!empty($query) && !is_array($query) && !empty($operator) && $value === null) {
			$add[]=array(
				"key"=>$this->escape_key($query),
				"operator"=>is_array($operator)?"IN":"=",
				"value"=>$this->escape_value($operator),
				"connector"=>$andor
			);
		} elseif (!is_array($query) && is_callable($query)) {
			//to be used for nested queries like this: http://laravel.com/docs/queries#advanced-wheres
			$this_class = get_called_class();
			$tempdb = new $this_class();
			$query($tempdb);

			$add[]=array(
				"string"=>"(".$tempdb->build_where(false).")",
				"bindings" => $tempdb->getBindings(),
				"connector"=>$andor
			);
		} elseif (!is_array($query)) {
			if(preg_match("/([A-Za-z0-9_.`\"]+) ([<>=A-Za-z]+) ([A-Za-z0-9.()\"'`]+)/i", $query, $matches)) {
				$add[]=array(
					"key"=>$this->escape_key($matches[1]),
					"operator"=>$matches[2],
					"value"=>$this->escape_value($matches[3]),
					"connector"=>$andor
				);
			} else {
				$add[]=array(
					"string"=>$query,
					"connector"=>$andor
				);
			}
		} elseif (is_array($query)) {
			$is_assoc = (bool)count(array_filter(array_keys($query), 'is_string'));
			foreach($query as $k=>$v)
			{
				if ($function=="HAVING") {
					$this->having(($is_assoc?$k:$v), ($is_assoc?$v:null), null);
				} else {
					$this->where(($is_assoc?$k:$v), ($is_assoc?$v:null), null);
				}
			}
		}

		if($add_to_base){
			$this->base_where = array_merge($this->base_where,$add);
		}

		if ($function == "WHERE") {
			$this->where = array_merge($this->where,$add);
		} elseif ($function == "HAVING") {
			$this->having = array_merge($this->having, $add);
		}


		return $this;
	}

	private function build_where($complete = true, $function = "WHERE")
	{
		if ($function == "HAVING") {
			$pieces = $this->having;
		} else {
			$pieces = $this->where;
		}
		if (count($pieces) > 0) {
			if($complete) {
				$query = ' '.$function.' ';
			} else {
				$query = '';
			}
			foreach ($pieces as $where) {
				if (isset($where['key'])) {
					if (is_array($where['value'])) {
						if(!empty($where['value'])) {
							$parsedArray = '('.implode(', ', array_fill(0, count($where['value']), '?')).')';
							$set = $where['key'] . ' '.$where['operator'].' '.$parsedArray;
							$this->bindings = array_merge($this->bindings, $where['value']);
						}
					} else {
						$matches = preg_match("/(.+)\(.*\)/", $where['value'], $func_check);
						if( in_array($where['value'], $this->supported_functions) || ($matches && in_array($func_check[1], $this->supported_functions))) {
							$set = $where['key'] . ' '.$where['operator'].' '.$where['value'];
						} else {
							$set = $where['key'] . ' '.$where['operator'].' ?';
							$this->bindings[] = $where['value'];
						}
					}
				} elseif (isset($where['string'])) {
					$set = $where['string'];
					if ( isset($where['bindings']) ) {
						$this->bindings = array_merge($this->bindings, $where['bindings']);
					}
				}
				if (empty($query) || $query == ' '.$function.' ') {
					$query.=$set;
				} else {
					$query.=" ".$where["connector"]." ".$set;
				}
			}
			return $query;
		}
		return '';
	}

	public function base_join($table_name='',$on_query='',$on_operator='',$on_value='',$join_type='')
	{
		$this->join($table_name,$on_query,$on_operator,$on_value,$join_type, true);
		return $this;
	}

	public function join($table_name='',$on_query='',$on_operator='',$on_value='',$join_type='', $add_to_base = false)
	{
		if (!empty($table_name) && !empty($on_query) && !empty($on_operator) && !empty($on_value)) {
			$join = array(
				'table_name' => $this->escape_key($table_name),
				'key'        => $this->escape_key($on_query),
				'operator'   => $on_operator,
				'value'      => $this->escape_key($on_value),
				'type'       => $join_type
			);
		} elseif (!empty($table_name) && !empty($on_query) && !empty($on_operator) && empty($on_value)) {
			$join = array(
				'table_name' => $this->escape_key($table_name),
				'string'     => $on_query,
				'type'       => $on_operator
			);
		} elseif (!empty($table_name) && !empty($on_query) && empty($on_operator) && empty($on_value)) {
			$join = array(
				'table_name' => $this->escape_key($table_name),
				'string'     => $on_query,
				'type'       => $join_type
			);
		}

		$this->join[] = $join;

		if ($add_to_base) {
			$this->base_join[] = $join;
		}

		return $this;
	}

	public function build_join()
	{
		if (count($this->join) > 0) {
			$query='';
			foreach($this->join as $j){
				$query .= ' ';
				$query .= !empty($j['type'])?$j['type'].' ':'';
				$query .= 'JOIN '.$j['table_name'].' ON ';
				if (isset($j['string'])) {
					$query .= $j['string'];
				} elseif (isset($j['key']) && !empty($j['key']) && !empty($j['operator']) && !empty($j['value'])) {
					$query .= $j['key'].' '.$j['operator'].' '.$j['value'];
				}
			}
			return $query;
		}
		return '';
	}

	public function groupBy($group)
	{
		if (is_array($group)) {
			foreach($group as $g){
				$this->groupBy($g);
			}
		} else {
			if (substr_count(",", $group) > 0) {
				$group = explode(substr_count(", ",$group)>0?", ":",",$group);
				foreach($group as $g){
					$this->groupBy($g);
				}
			} else {
				$this->group_by[] = $this->escape_key($group);
			}
		}
		return $this;
	}

	public function build_groupBy()
	{
		$group_by = '';

		if (!empty($this->group_by)) {
			$group_by = ' GROUP BY '.implode(', ',$this->group_by);
		}

		return $group_by;
	}

	public function order($col="", $direction=null)
	{
		return $this->orderBy($col, $direction);
	}

	public function orderBy($col="", $direction=null)
	{
		if (is_array($col)) {
			foreach($col as $ob){
				$this->orderBy($ob);
			}
		} else {
			if ($direction == null) {
				if (substr_count($col,", ") > 0) {
					$order = explode(substr_count($col,", ")>0?", ":",",$col);
					print_r($order,true);
					foreach($order as $o){
						$this->orderBy($o);
					}
				} else {
					$order = explode(" ",$col);
					$this->order_by[] = array(
						"col" => $this->escape_key($order[0]),
						"dir" => strtolower($order[1])=="asc"?"ASC":"DESC"
					);
				}
			} else {
				$this->order_by[] = array(
					"col" => $this->escape_key($col),
					"dir" => strtolower($direction)=="asc"?"ASC":"DESC"
				);
			}
		}
		return $this;
	}

	public function build_orderBy()
	{
		$return = '';
		if (!empty($this->order_by)) {
			$order_by = array();
			foreach ($this->order_by as $o) {
				$order_by[] = $o['col'].' '.$o['dir'];
			}
			$return = ' ORDER BY '.implode(', ',$order_by);
		}
		return $return;
	}

	public function limit($offset=null,$limit=null)
	{
		if ($offset != null && $limit == null && is_numeric($offset)) {
			$this->limit = $offset;
		} elseif($offset !== null && $limit !== null && is_numeric($offset) && is_numeric($limit)) {
			$this->offset = $offset;
			$this->limit = $limit;
		}
		return $this;
	}

	public function offset($offset=null)
	{
		if($offset != null && is_numeric($offset)) {
			$this->offset = $offset;
		}
		return $this;
	}

	public function build_limit()
	{
		$return = '';
		if($this->limit !== null){
			$return = ' LIMIT '.$this->offset.", ".$this->limit;
		}
		return $return;
	}

	public function toSql()
	{
		return $this->build_query();
	}

	public function getBindings()
	{
		return $this->bindings;
	}

	public function build_query()
	{
		$this->bindings = array();
		if ($this->command == "SELECT") {
			return $this->command_select();
		} else if ($this->command == "UPDATE") {
			return $this->command_update();
		} else if ($this->command == "INSERT") {
			return $this->command_insert();
		} else if ($this->command == "DELETE") {
			return $this->command_delete();
		}
	}

	public function command_select()
	{
		return 'SELECT '.
			$this->distinct.
			$this->build_select().
			' FROM '.$this->escape_table_name($this->from).
			$this->build_join().
			$this->build_where().
			$this->build_groupBy().
			$this->build_where(true, "HAVING").
			$this->build_orderBy().
			$this->build_limit();
	}

	public function command_update()
	{
		return 'UPDATE '.
			$this->escape_table_name($this->from).
			$this->build_set().
			$this->build_where().
			$this->build_orderBy().
			$this->build_limit();
	}

	public function command_insert()
	{
		return 'INSERT INTO '.
			$this->escape_table_name($this->from).
			$this->build_values();
	}

	public function command_delete()
	{
		return 'DELETE FROM '.
			$this->escape_table_name($this->from).
			$this->build_where().
			$this->build_orderBy().
			$this->build_limit();
	}
}