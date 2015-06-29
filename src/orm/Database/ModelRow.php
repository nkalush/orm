<?php

namespace orm\Database;

class ModelRow
{
	private $data;
	private $model = null;
	private $_attributes = array();
	private $_associations = array();

	public function __construct($model='', $data='')
	{
		$this->model = $model;
		$this->_attributes = $data;
		$this->data = new \stdClass;

		foreach ($this->_attributes as $key => $value)
		{
			$this->data->$key = $value;
		}
	}

	public function toArray()
	{
		return (array) $this->data;
	}

	public function add_data($key, $value)
	{
		$this->data->$key = $value;
		return $this;
	}

	public function __get($name=null)
	{
		if (property_exists($this->data, $name)) {
			return $this->data->$name;
		} elseif (method_exists($this->model, 'get_'.$name)) {
			$name = 'get_'.$name;
			return $this->model->$name($this);
		} elseif(isset($this->_associations[$name])){
				return $this->_associations[$name];
		} elseif(isset($this->model->associations[$name])) {
			$association = $this->model->associations[$name];

			if ($association['type'] == 'has_many') {
				$class_name = !empty($association['options']['class_name']) ? ucfirst($association['options']['class_name']) : ucfirst(\orm\Inflector::singularize($association['name']));
				$class = new $class_name;

				if ($association['options'] !== null && $association['options']['through']) {
					$primary_key = $this->model->primary_key();
					$foreign_key1 = !empty($association['options']['foreign_key']) ? $association['options']['foreign_key'] : $class->primary_key();
					$foreign_key2 = $class->primary_key();
					$table = $association['options']['through'];
					$class->base_join($table, $table . '.' . $foreign_key1,"=",$class->table_name() . '.' . $foreign_key2);

					$id = $this->$primary_key;
					$class->base_where($table . '.' . $primary_key,"=",$id);
					$class->pivot_table = array(
						"table_name"=> $table,
						"attach_key" => $primary_key,
						"attach_id" => $id,
					);
				} else {
					$primary_key = $this->model->primary_key();
					$id = $this->$primary_key;
					$class->base_where($primary_key,'=',$id);
				}
				$this->_associations[$name] = $class;
				return $class;
			} elseif ($association['type'] == 'belongs_to') {
				$class_name = !empty($association['options']['class_name']) ? ucfirst($association['options']['class_name']) : ucfirst($association['name']);
				$class = new $class_name;

				if (!empty($association['options']['foreign_key'])) {
					$primary_key = $association['options']['foreign_key'];
				} else {
					$primary_key = $class->primary_key();
				}

				$id = $this->$primary_key;
				if($id){
					$return = $class->find($id);
					$this->_associations[$name] = $return;
					return $return;
				} else {
					return null;
				}
			}
		}
	}

	public function __call($method='',$params=null)
	{
		if (method_exists($this->model,"get_".$method)) {
			return call_user_func_array(array($this->model, "get_".$method), array_merge(array($this->data),$params));
		} elseif(in_array($method, array('update','destroy'))){
			$primary_key = $this->model->primary_key();
			$this->model->where($primary_key,'=',$this->data->{$primary_key});
			$response = call_user_func_array(array($this->model, $method), $params);

			if($method=='update'){
				return $response[0];
			}
		}
	}
}