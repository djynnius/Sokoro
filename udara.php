<?php
#Simple PHP ORM

class Udara{
	
	public $dbo;

	function __construct($dbo=false){
		if($dbo == false) self::__destruct();
		$this->dbo = $dbo;
	}

	function __destruct(){
		return "There was a problem";
	}

	public function getColumnNames(){
		$q = "SELECT * FROM {$this->table} LIMIT 1";
		$q = $this->dbo->query($q);
		$puts = array();
		while($r = $q->fetch(PDO::FETCH_ASSOC)){
			$puts = array_keys($r);
		}
		return $puts;
	}

	public function getColumnCount(){
		return count($this->getColumnNames());
	}

	public function table($table=false, $attrs=false){
		return $table == false || gettype($table) == 'array' ? 
			false : new UTable($table, $attrs, $this->dbo);
	}

	public function view($table=false, $q=false){
		return $table == false || gettype($table) == 'array' ? 
			false : new UView($table, $q, $this->dbo);
	}

	public function fetch($q){		
		try{
			if($q = $this->dbo->query($q)){
				$a = array();
				while($r = $q->fetch(PDO::FETCH_ASSOC)){
					$a[] = $r;
				}
				return $a;
			} else {
				throw new PDOException("Invalid query.");
				return false;
			}
		} catch(PDOException $e){
			//print $e->getMessage();
		}
	}

	public function paginate($q, $page=1, $count=10){
		$q = $q." LIMIT='".((int)$count)."' OFFSET='".(((int)$page)-1)*((int)$count)."'";
		try{
			return $this->fetch($q);
		} catch(Exception $e){
			return false;
		}
	}	

	public function write($q){
		$this->dbo->query($q);
	}

	public function execute($q){
		$this->dbo->query($q);
	}

	public function delete($q, $table=false){
		$this->dbo->query($q);
	}
}

#Table mapper
class UTable extends Udara{
	public $dbo;
	public $table;

	function __construct($table=false, $attrs=false, $dbo){
		$this->dbo = $dbo;
		$this->table = $table;

		if(!is_array($attrs)){
			$this->fetch();
		} else {
			$this->create($table, $attrs);
		}
	}

	public function create($table, $attrs){
		$q = "CREATE TABLE IF NOT EXISTS {$table} (";
		$count = count($attrs);
		$i = 1;
		foreach($attrs as $k=>$v){
			if($k == 'primary key'){
				$q .= $i == $count ? "{$k} ({$v}) " : "{$k} ({$v}), ";
			} else {
				$q .= $i == $count ? "{$k} {$v} " : "{$k} {$v}, ";	
			}
				
			$i++;
		}
		$q .= ")";		
		$this->dbo->query($q);
	}

	public function fetch(){
		$q = "SELECT * FROM `{$this->table}`";
		try{
			if($q = $this->dbo->query($q)){
				$a = array();
				while($r = $q->fetch(PDO::FETCH_ASSOC)){
					$a[] = $r;
				}
				return $a;				
			} else {
				throw new Exception("The table ({$this->table}) does not exist. \r\n");
			}
		} catch(Exception $e){
			print $e->getMessage();
			return false;
		}
	}

	private function removeEmptyArrayInput($a){
		foreach($a as $k=>$v){
			if(empty($v)){
				unset($a[$k]);
			} else {
				$a[$k] = trim($v);
				continue;
			}
		}		
		return $a;
	}

	public function add($a){
		$a = $this->removeEmptyArrayInput($a);
		$count = count($a);
		if($count == 0){
			return false;
		} else {
			$keys = array_keys($a);
			$values = array_values($a);
			$q = "INSERT INTO {$this->table} (";
			for($i=1; $i<=$count; $i++){
				$q .= $i == $count ? "{$keys[($i-1)]} " : "{$keys[($i-1)]}, ";
			}
			$q .= ") VALUES (";
			for($i=1; $i<=$count; $i++){
				$q .= $i == $count ? "? " : "?, ";
			}
			$q .= ")";
			$insert = $this->dbo->prepare($q);
			$insert->execute($values);
		}
	}

	public function remove($val, $col="id"){
		$q = "DELETE FROM {$this->table} WHERE {$col}=?";
		$stmt = $this->dbo->prepare($q);
		$stmt->execute(array($val,));
	}

	public function update($val='*', $a=array(), $col="id"){
		$a = $this->removeEmptyArrayInput($a);
		$count = count($a);
		$q = "UPDATE {$this->table} SET ";
		$i = 1;
		foreach($a as $k=>$v){
			$q .= ($i == $count) ? "{$k}=? " : "{$k}=?, ";	
			$i++;
		}
		$q .= $val == "*" ? "" : " WHERE {$col}=?";
		$a = array_values($a);
		if($val == "*"){null;} else {array_push($a, $val);}
		$stmt = $this->dbo->prepare($q);
		$stmt->execute($a);
	}

	public function row($val, $col='id'){
		try{
			$row = new URow($val, $col, $this->table, $this->dbo);
		} catch(PDOException $e){
			$row = false;
		}
		return $row;
	}

	public function drop(){
		$q = "DROP TABLE {$this->table}";
		$this->dbo->query($q);
	}

	public function size(){
		$q = "SELECT COUNT(`id`) AS `count` FROM {$this->table}";
		return $this
				->dbo
				->query($q)
				->fetchObject()
				->count;
	}

	/**
		many to many relatipnship check ensures 
		no duplicates are entered in many to many tables
	*/
	public function m2mExists($col1, $arg1, $col2, $arg2){
		$q = "SELECT COUNT(*) AS `count` 
				FROM {$this->table} 
			WHERE `{$col1}`='{$arg1}' 
				AND `{$col2}`='{$arg2}'"; 
		return $this->dbo->query($q)->fetchObject()->count > 0 ? true : false;
	}

	/**
		prevents duplicating of records
	*/
	public function exists($col, $val){
		$q = "SELECT COUNT({$col}) AS `count` 
				FROM {$this->table} 
			WHERE {$col}='{$val}'";
		return $this->dbo->query($q)->fetchObject()->count > 0 ? true : false;
	}
}

/**
	purely for the purpose of replicating the table actions 
	except for the creation of a view
*/
class UView extends UTable{
	function __construct($table=false, $q=false, $dbo){
		$this->dbo = $dbo;
		$this->table = $table;

		if(is_bool($q)){
			$this->fetch();
		} else {
			$this->create($table, $q);
		}
	}

	public function create($table, $q){
		$q = "CREATE VIEW `{$table}` AS {$q}";		
		$this->dbo->query($q);
	}

	public function drop(){
		$q = "DROP VIEW {$this->table}";
		$this->dbo->query($q);
	}	
	public function add(){return false;}

	public function remove(){return false;}

	public function update(){return false;}	
} 

/**
	row maper	
*/
class URow extends Udara{
	public $dbo;
	public $table;
	public $column;
	public $value;
	public $_row;

	function __construct($value, $column, $table, $dbo){
		$this->column = $column;
		$this->value = $value;
		$this->table = $table;
		$this->dbo = $dbo;
		$q = "SELECT * FROM `{$table}` WHERE `{$column}`='{$value}' LIMIT 1";
		$this->_row = $this->dbo->query($q)->fetchObject();
	}

	function currentRow(){
		return $this->_row;
	}


	function commit(){		
		foreach($this->currentRow() as $k=>$v){
			if($v == $this->$k){
				//continue;
			} else {
				$q = "UPDATE `{$this->table}` SET `{$k}`='{$this->$k}' WHERE id='{$this->currentRow()->id}'";
				$this->dbo->query($q);
			}
		}
	}

	function __get($attr){
		if(!property_exists($this, $attr) && in_array($attr, $this->getColumnNames())){
			return @$this->currentRow()->$attr;
		}
	}

	function size(){
		$i = 0;
		try{
			if($this->currentRow()){
				foreach((array)$this->currentRow() as $v){
					if(!empty($v)) $i++;
				}
				return $i;				
			} else {
				throw new PDOException;
			}
			
		} catch(PDOException $e){
			return $e->getMessage();
		}
	
	}

	function remove(){
		$q = "DELETE FROM `{$this->table}` WHERE `id`='{$this->currentRow()->id}'";
		$this->dbo->query($q);
	}
}