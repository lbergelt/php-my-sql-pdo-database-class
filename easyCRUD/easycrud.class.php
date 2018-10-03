<?php
/**
* Easy Crud  -  This class kinda works like ORM. Just created for fun :)
*
* @author		Author: Vivek Wicky Aswal. (https://twitter.com/#!/VivekWickyAswal)
* @version      0.6
*/
require_once(__DIR__ . '/../db.class.php');

abstract class Crud
{
	protected $db;
	public $variables = array();

	// Declare on CRUD child
	protected $pk;
	protected $table;

	// Check fields before init object (optional)
	public $list_fields_table = array();
	protected $required_fields = array();

	public function __construct(&$db, $data = array()) {
		$this->db =  $db;
		$this->variables  = $data;
	}

	public function __set($name,$value){
		if($name === $this->pk) {
			$this->variables[$this->pk] = $value;
		}
		else {
			$this->variables[$name] = $value;
		}
	}

	public function __get($name)
	{
		if(isset($this->$name)) // use magic method: __isset
			return $this->variables[$name];

		return null;
	}

	public function __isset($name)
	{
		if(is_array($this->variables) && array_key_exists($name, $this->variables))
			return true;

		return false;
	}

	public function __unset($name)
	{
		if(isset($this->$name)) // use magic method: __isset
			unset($this->variables[$name]);
	}

	public function get_table()
	{
		return $this->table;
	}

	public function save($id = null)
	{
		if(empty($this->variables[$this->pk]) && !empty($id))
			$this->variables[$this->pk] = $id;

		$fieldsvals = '';
		$columns = array_keys($this->variables);

		foreach($columns as $column)
		{
			if($column === $this->pk)
				continue;

			$fieldsvals .= "{$column} = :{$column},";
		}

		$fieldsvals = substr_replace($fieldsvals , '', -1);

		if(count($columns) > 1 )
		{
			if(empty($this->variables[$this->pk]))
			{
				unset($this->variables[$this->pk]);
				$sql = "UPDATE {$this->table} SET {$fieldsvals}";
			}
			else $sql = "UPDATE {$this->table} SET {$fieldsvals} WHERE {$this->pk} = :{$this->pk}";

			return $this->exec($sql);
		}

		return null;
	}

	public function create()
	{
		$bindings   	= $this->variables;

		if(!empty($bindings)) {
			$fields     =  array_keys($bindings);
			$fieldsvals =  array(implode(",",$fields),":" . implode(",:",$fields));
			$sql 		= "INSERT INTO ".$this->table." (".$fieldsvals[0].") VALUES (".$fieldsvals[1].")";
		}
		else $sql 		= "INSERT INTO ".$this->table." () VALUES ()";

		return $this->exec($sql);
	}

	public function delete($id = null)
	{
		$id = ((!empty($id)) ? $id : $this->variables[$this->pk]);

		if(empty($id))
			return false;

		$sql = "DELETE FROM {$this->table} WHERE {$this->pk} = :{$this->pk} LIMIT 1" ;

		$result = $this->exec($sql, array($this->pk=>$id));
		$this->variables = array(); // Empty bindies

		return $result;
	}

	public function find($id = null)
	{
		$id = ((!empty($id)) ? $id : $this->variables[$this->pk]);

		if(!empty($id))
		{
			$sql = "SELECT * FROM {$this->table} WHERE {$this->pk} = :{$this->pk} LIMIT 1";

			$result = $this->db->row($sql, array($this->pk=>$id));
			$this->variables = ($result != false) ? array_change_key_case($result, CASE_LOWER) : array();
		}
		else $this->variables = null;
	}

	/**
	* @param array $fields.
	* @param array $sort.
	* @return array of Collection.
	* Example: $user = new User;
	* $found_user_array = $user->search(array('sex' => 'Male', 'age' => '18'), array('dob' => 'DESC'));
	* // Will produce: SELECT * FROM {$this->table_name} WHERE sex = :sex AND age = :age ORDER BY dob DESC;
	* // And rest is binding those params with the Query. Which will return an array.
	* // Now we can use for each on $found_user_array.
	* Other functionalities ex: Support for LIKE, >, <, >=, <= ... Are not yet supported.
	*/
	public function search($fields = array(), $sort = array(), $limit = 0)
	{
		$bindings = empty($fields) ? $this->variables : $fields;

		$sql = "SELECT * FROM " . $this->table;

		if(!empty($bindings))
		{
			$fieldsvals = array();
			$columns = array_keys($bindings);
			foreach($columns as $column) {
				$fieldsvals [] = $column . " = :". $column;
			}
			$sql .= " WHERE " . implode(" AND ", $fieldsvals);
		}

		if(!empty($sort))
		{
			$sortvals = array();
			foreach ($sort as $key => $value) {
				$sortvals[] = $key . " " . $value;
			}
			$sql .= " ORDER BY " . implode(", ", $sortvals);
		}

		if(!empty($limit))
			$sql .= " LIMIT {$limit}";

		return $this->exec($sql, $bindings);
	}

	public function all(){
		return $this->db->query("SELECT * FROM " . $this->table);
	}

	public function min($field)  {
		if($field)
		return $this->db->single("SELECT min(" . $field . ")" . " FROM " . $this->table);
	}

	public function max($field)  {
		if($field)
		return $this->db->single("SELECT max(" . $field . ")" . " FROM " . $this->table);
	}

	public function avg($field)  {
		if($field)
		return $this->db->single("SELECT avg(" . $field . ")" . " FROM " . $this->table);
	}

	public function sum($field)  {
		if($field)
		return $this->db->single("SELECT sum(" . $field . ")" . " FROM " . $this->table);
	}

	public function count($field)  {
		if($field)
		return $this->db->single("SELECT count(" . $field . ")" . " FROM " . $this->table);
	}


	private function exec($sql, $array = null) {

		if($array !== null) {
			// Get result with the DB object
			$result =  $this->db->query($sql, $array);
		}
		else {
			// Get result with the DB object
			$result =  $this->db->query($sql, $this->variables);
		}

		// Empty bindings (why?)
		// $this->variables = array();

		return $result;
	}

	// Check fields before init object (optional)
	public function check_fields()
	{
		// Prerequisites
		if(empty($this->variables))
			return !user_error('No fields filled');
		elseif(empty($this->list_fields_table))
			return !user_error(sprintf('Class %s: please add list_fields_table in __construct', __CLASS__));

		// Ne pas gérer les champs non supportés
		$common_fields = array_intersect_key($this->variables, array_flip($this->list_fields_table));
		$diff_fields = array_diff(array_keys($this->variables), $this->list_fields_table);

		if(!empty($diff_fields))
			return !user_error("Unsupported fields: ". implode(', ', $diff_fields));

		$this->variables = $common_fields;


		// Check missing fields
		if(!empty($this->required_fields))
		{
			$field_missing = array();

			foreach($this->required_fields as $require)
				if(empty($this->variables[ $require ]))
					$field_missing[] = $require;

			if(!empty($field_missing))
				return !user_error("Required fields not filleds: ". implode(', ', $field_missing));
		}

		return true;
	}

	// Vérifier qu'un dossier avec le même code n'existe pas déjà
	public function exists()
	{
		if(empty($this->variables[$this->pk]))
			return false;

		$id = $this->variables[$this->pk];
		$sql = "SELECT {$this->pk} FROM {$this->table} WHERE {$this->pk}= :{$this->pk} LIMIT 1";

		$already_exist = $this->db->row($sql, array($this->pk=>$id));

		return !empty($already_exist);
	}
}

?>