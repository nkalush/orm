<?php

namespace orm\Database;

class Model
{
	public $db = NULL;
	public $driver = NULL;
	protected $table_name = NULL;
	protected $hooks = array(
		'before_save'=>array(),
	);

	public function __construct(DB $db = null)
	{
		if ( $db == null ) {
			$this->db = DB::Instance();
		} else {
			$this->db = $db;
		}

		$config = $this->db->getConfig();
		$this->config = $config[$config['driver']];

		if ($this->table_name == null) {
			$this->table_name = strtolower(get_called_class());
			if(isset($this->config['prefix']) && !empty($this->config['prefix'])){
				$this->table_name = $this->config['prefix'].$this->table_name;
			}
		}

		if ($config['driver'] == 'mysql') {
			$this->driver = new Drivers\MySqlDriver;
		} elseif($config['driver'] == 'sqlite') {
			$this->driver = new Drivers\SQLiteDriver;
		} else {
			$this->driver = new Drivers\DbDriver;
		}

		if(method_exists($this, "init")){
			$this->init();
		}
	}

	public function table_name()
	{
		return $this->table_name;
	}

	public function primary_key()
	{
		return isset($this->primary_key)?$this->primary_key:$this->table_name."_id";
	}

	public function has_many($name=null, $options = null)
	{
		$this->associations[$name] = array(
			'name' 		=> $name,
			'type' 		=> 'has_many',
			'options' 	=> $options
		);
	}

	public function belongs_to($name, $options = null)
	{
		$this->associations[$name] = array(
			'name' 		=> $name,
			'type' 		=> 'belongs_to',
			'options' 	=> $options
		);
	}

	public function before_save($name=null)
	{
		$this->hooks['before_save'][] = $name;
	}

	public function run_hook($hook, $row = null, $data = null)
	{
		if (!empty($this->hooks[$hook])) {
			foreach ($this->hooks[$hook] as $h) {
				$data = $this->$h($row, $data);
			}
		}
		return in_array($hook, array('after_save', 'after_create', 'after_update')) ? $row : $data;
	}

	public function update($id=null, $data=null)
	{
		$this->driver->from($this->table_name());

		$write_data = array();
		$old_data=$original_driver=false;

		if(is_numeric($id) && is_array($data)) {
			$this->driver->where($this->primary_key(),"=",$id);
			$write_data = $data;
		} elseif (is_array($id) && $data === null) {
			$write_data = $id;
		}

		if (count($this->hooks['before_save']) > 0) {
			$original_driver = clone $this->driver;
			$old_data = $this->first();
		}

		$write_data = $this->run_hook('before_save', $old_data, $write_data);

		if ($original_driver) {
			$this->driver = $original_driver;
		}
		//echo "<pre>".print_R($this->driver,true)."</pre>";exit;
		$this->driver->update($write_data);

		$sql = $this->driver->toSql();

		$bindings = $this->driver->getBindings();

		$data  = $this->db->query($sql, $bindings, false);

		if (is_numeric($id)) {
			$this->driver->reset();
			return $this->find($id);
		} else {
			$where = $this->driver->where;
			$this->driver->reset();
			$this->driver->where = $where;
			return $this->find();
		}

		return null;
	}

	public function create($data=null)
	{
		if(is_array($data))
		{
			$data = $this->run_hook('before_save', null, $data);

			$this->driver->from($this->table_name())
				->insert($data);

			$sql = $this->driver->toSql();
			$bindings = $this->driver->getBindings();
			$insert = $this->db->query($sql, $bindings, false);
			if ($insert) {
				$insert_id = $this->db->lastInsertId($this->primary_key());
				$this->driver->reset();
				$new_row = $this->find($insert_id);
				return $new_row;
			}
			$this->driver->reset();
		}
		return null;
	}

	public function destroy($id=null)
	{
		$this->driver->delete()->from($this->table_name());

		if (is_numeric($id)) {
			$this->driver->where($this->primary_key(),"=",$id);
		}

		if (!empty($this->driver->where)) {
			$sql = $this->driver->toSql();
			$bindings = $this->driver->getBindings();
			$delete = $this->db->query($sql, $bindings, false);
			$this->driver->reset();
			return $delete?true:false;
		} else {
			return null;
		}
	}

	public function find($options=null, $reset=true)
	{
		if ($options == null) {
			$this->driver->select()->from($this->table_name());
		} elseif(is_numeric($options)) {
			if (!empty($this->driver->join)) {
				$this->driver->select()->from($this->table_name())->where($this->table_name().'.'.$this->primary_key(),"=",$options);
			} else {
				$this->driver->select()->from($this->table_name())->where($this->primary_key(),"=",$options);
			}
		} else {
			$this->driver->from($this->table_name());

			if (isset($options['select']) && !empty($options['select'])) {
				$this->driver->select($options['select']);
			} else {
				$this->driver->select();
			}
			if (isset($options['where']) && !empty($options['where'])) {
				$this->driver->where($options['where']);
			}
			if (isset($options['where_in']) && !empty($options['where_in'])) {
				$this->driver->where($options['where_in']);
			}
			if (isset($options['having']) && !empty($options['having'])) {
				$this->driver->having($options['having']);
			}
			if (isset($options['order']) && !empty($options['order'])) {
				$this->driver->orderBy($options['order']);
			}
			if (isset($options['limit']) && !empty($options['limit'])) {
				$this->driver->limit($options['limit']);
			}
			if (isset($options['offset']) && !empty($options['offset'])) {
				$this->driver->offset($options['offset']);
			}
		}
		$sql = $this->driver->toSql();
		$bindings = $this->driver->getBindings();

		$data  = $this->db->query($sql, $bindings);
		if ($reset) {
			$this->driver->reset();
		}

		if (!empty($data)) {
			if (is_numeric($options)) {
				$response = new ModelRow($this, $data[0]);
			} else {
				$response = array();
				foreach ($data as $d) {
					$response[] = new ModelRow($this, $d);
				}
			}
			return $response;
		} else {
			return NULL;
		}
	}

	public function first($options=null, $reset=true)
	{
		$this->driver->limit(1);
		$first = $this->find($options, $reset);
		if ($first) {
			return $first[0];
		} else {
			return NULL;
		}
	}

	public function count($options=null, $reset=true)
	{
		if(!$reset){
			//clone the select and use that to build the count query to maintain selects
			$old_select = $this->driver->select;
			$this->driver->select = array();
		}

		$this->driver->select('COUNT(*) as num_rows')->from($this->table_name());

		$sql = $this->driver->toSql();
		$bindings = $this->driver->getBindings();

		$data = $this->db->query($sql, $bindings);

		if ($reset) {
			$this->driver->reset();
		} else {
			$this->driver->select = $old_select;
			unset($old_select);
		}
		if (!empty($data) && isset($data[0]['num_rows'])) {
			return $data[0]['num_rows'];
		} else {
			return 0;
		}
	}

	public function paginate($per_page=0, $current_page=0, $base_url="", $options=array())
	{
		if(empty($base_url)  && function_exists('url'))
		{
			$base_url = url('/');
		}

		$total_results = $this->count($options, false);

		if ($total_results > 0) {
			$offset = $current_page == 0?$current_page:($current_page-1) * $per_page;
			$this->driver->limit($offset, $per_page);

			$return["data"] = $this->find($options);

			$total_pages = ceil($total_results/$per_page);

			$return["links"] = array();

			$has_get = substr_count($base_url,"?");

			for($i=1;$i<=$total_pages;$i++)
			{
				$return['links'][] = array(
					"href"	=> $i==1?$base_url:$base_url.($has_get?"&":"?")."page=".$i,
					"name"	=> $i,
				);
			}
		} else {
			$this->driver->reset();
			$return["links"] = array();
			$return["data"] = array();
		}

		return $return;
	}

	public function is_connected($attached_id=0)
	{
		$this->driver->where($this->table_name().'.'.$this->primary_key(),"=",$attached_id);
		$response = $this->find(null);

		return $response?true:false;
	}

	public function attach($attached_id=0)
	{
		if(!empty($attached_id)){
			$driver_name = get_class($this->driver);
			$driver = new $driver_name();
			$driver->from($this->pivot_table['table_name']);
			$driver->insert(array(
				$this->pivot_table['attach_key'] => $this->pivot_table['attach_id'],
				$this->primary_key() => $attached_id,
			));

			$sql = $driver->toSql();
			$bindings = $driver->getBindings();
			$insert = $this->db->query($sql, $bindings, false);
			return $insert?true:false;
		}else{
			return false;
		}
	}

	public function detach($attached_id=0)
	{
		if(!empty($attached_id)){
			$driver_name = get_class($this->driver);
			$driver = new $driver_name();
			$driver->from($this->pivot_table['table_name']);
			$driver->where($this->pivot_table['attach_key'],'=', $this->pivot_table['attach_id']);
			$driver->where($this->primary_key(), '=', $attached_id);
			$driver->delete();

			$sql = $driver->toSql();
			$bindings = $driver->getBindings();
			$insert = $this->db->query($sql, $bindings, false);
			return $insert?true:false;
		}else{
			return false;
		}
	}

	public function detach_all()
	{
		$driver_name = get_class($this->driver);
		$driver = new $driver_name();
		$driver->from($this->pivot_table['table_name']);
		$driver->where($this->pivot_table['attach_key'],'=', $this->pivot_table['attach_id']);
		$driver->delete();

		$sql = $driver->toSql();
		$bindings = $driver->getBindings();
		$insert = $this->db->query($sql, $bindings, false);
		return $insert?true:false;
	}

	public function __call($method_name='', $parameters='')
	{
		if (method_exists($this, 'get_'.$method_name)) {
			call_user_func_array(array($this,'get_'.$method_name), $parameters);
			return $this;
		} elseif (method_exists($this->driver, $method_name)) {
			call_user_func_array(array($this->driver,$method_name), $parameters);
			return $this;
		}
	}

	static public function __callStatic($method_name='',$parameters='')
	{
		$model = get_called_class();
		$model = new $model;
		call_user_func_array(array($model,$method_name), $parameters);
		return $model;
	}
}