<?php
$_debug['file'][] = 'includes/TableSorter.class.php';

######################################################################################################
# Easily manage table sort order
######################################################################################################

class TableSorter {
	
	/**
	  * configure these in the constructor to allow this class to order different tables
	**/
	private $table;
	private $tableKey;
	private $tableParentKey;
	
	/**
	  * _constructor - set table and the id key
	  * 
	  * $tableParentKey examples
	  * one field:  $tableParentKey = array('parent_id')
	  * two fields: $tableParentKey = array('item_id', array('module', 'pages'))
	  * in the case of two field th first item is always item that will dictate change when sorting, the second is the second refernce
	**/
	public function __construct($table, $tableParentKey = array('parent_id'), $tableKey = 'id') {
		$this->table = $table;
		$this->tableKey = $tableKey;
		$this->tableParentKey = $tableParentKey;
	}
	

	public function cleanUp() {
		
		foreach($this->tableParentKey as $key => $val) {
			if($key == '0') {
				$g[] = $val;
			}else{
				$g[] = $val[0];
			}
		}
		$groupBy = implode(', ', $g);
		
		$q = getRows("SELECT id, " . $groupBy . "
					  FROM " . $this->table . "
					  ORDER BY " . $groupBy . " ASC, alive DESC, sort_order ASC");
		
		foreach($q as $r) {
		
			if(count($this->tableParentKey) > 1) {
				
				if($parent2 == $r[$this->tableParentKey[1][1]]) {
					if($parent != $r[$this->tableParentKey[0]]) {
						$parent = $r[$this->tableParentKey[0]];
						$c = 1;
					}
				}else{
					$parent2 = $r[$this->tableParentKey[1][1]];
					$parent = $r[$this->tableParentKey[0]];
					$c = 1;
					/*
					if($parent != $r[$this->tableParentKey[0]]) {
						$parent = $r[$this->tableParentKey[0]];
						$c = 1;
					}
					*/
				}
				
				
			}else{
				if($parent != $r[$this->tableParentKey[0]]) {
					$parent = $r[$this->tableParentKey[0]];
					$c = 1;
				}
			}
			
			sendQuery("UPDATE " . $this->table . " SET sort_order = '" . $c . "' WHERE " . $this->tableKey . " = '" . $r[$this->tableKey] . "'");
			$c ++;
		}
		
	}
	
	
	/**
	  * move an item up or down
	  *
	  * @direction: string; up | down
	  * @id: integer; the id of the item in the table to move
	**/
	public function moveItem($direction, $id) {
		//$this->cleanUp();
		$amount = 1; // do not change - currently can only handle 1
		
		$curOrder = getField('sort_order', $this->table, $this->tableKey, $id);
		$pos = ($direction == 'up') ? $curOrder - $amount : $curOrder + $amount;
		
		
		$parent = getField($this->tableParentKey[0], $this->table, $this->tableKey, $id);
		
		$c = 0;
		if(count($this->tableParentKey) > 1) {
			
			foreach($this->tableParentKey as $key => $val) {
				if($c > 0) {
				
					$wheres[] = $val[0] . " = '" . $val[1] . "'";
				}else{
					$wheres[] = $val . " = '" . $parent . "'";
				}
				
				$c ++;
			}
			
			$where = implode(' AND ', $wheres);
			
		}else{
			$where = $this->tableParentKey[0] .  " = '" . $parent . "'";
		}
		
		$replaceItem = getRows("SELECT " . $this->tableKey . ", sort_order FROM " . $this->table . " WHERE sort_order = '" . $pos . "' AND (" . $where . ")");
		
		if($direction == 'up') {
			sendQuery("UPDATE " . $this->table . " SET sort_order = sort_order -" . $amount . " WHERE " . $this->tableKey . " = '" . $id . "'");
			sendQuery("UPDATE " . $this->table . " SET sort_order = sort_order +" . $amount . " WHERE " . $this->tableKey . " = '" . $replaceItem[0][$this->tableKey] . "'");
		}else{
			sendQuery("UPDATE " . $this->table . " SET sort_order = sort_order +" . $amount . " WHERE " . $this->tableKey . " = '" . $id . "'");
			sendQuery("UPDATE " . $this->table . " SET sort_order = sort_order -" . $amount . " WHERE " . $this->tableKey . " = '" . $replaceItem[0][$this->tableKey] . "'");
		}
		
		$this->cleanUp();
		
	}
	
	
	/**
	  * get the next child sort_order for inserting / updating a record
	**/
	public function nextChild($parent_id = '0') {
		$q = "SELECT sort_order
			  FROM " . $this->table . " 
			  WHERE " . $this->tableParentKey[0] . " = '" . $parent_id . "'
			  ORDER BY sort_order DESC
			  LIMIT 1";
			  
		$row = getRows($q);
		
		return $row[0]['sort_order']+1;
	}
	
	
}


?>