<?php
/**
 * Widget - skeleton for the various extra bits of logic for building widgets
 * @version 0.1
 * @package condiment
 * @subpackage Widget
 * @author David Harris <theinternetlab@gmail.com>
 */

class BaseWidget{
	
	public $update = false;
	public $data = array(); //store all data needed to build the template in here - it auto caches itself
	public $dbInfo;
	public $pageInfo;
	public $settings;
	
	function __construct(){
		
	}
	
	public function setWidgetInfo($dbInfo){
		$this->dbInfo = $dbInfo;
		if( isset( $this->dbInfo['settings'] ) ){
			$this->settings = json_decode( $this->dbInfo['settings'] );
		}
	}
	
	public function setPageInfo($pgInfo){
		$this->pageInfo = $pgInfo;
	}
	
	public function setCachedData($data){
		$this->data = $data;
	}
	
	public function getData(){
		return $this->data;
	}
	
	public function update(){
		$this->update = true;
	}
	
}

?>