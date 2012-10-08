<?php
/**
 * Emailer - general email wrapper - to allow us to easily change all mail to different formats, plain text / HTML
 * @version 0.1
 * @package condiment
 * @subpackage emailer
 * @author David Harris <theinternetlab@gmail.com>
 */

//! TODO - This will likely wrap another HTML style email class

class SearchBox{
	
	public $isFilterSearch = false;
	public $data = array();
	public $numResults;
	public $els;
	public $isSubmitted = false; //weather a search has been submitted or is fresh - not filled in search
	
	// url - full URL of page - including domain & GET variables
	function __construct(){
		if( count($_GET) ){
					
			foreach(array('location','term') as $key){
				if(isset($_GET[$key]) && (is_array($_GET[$key]) && count($_GET[$key])) || strlen($_GET[$key]) > 0){
					$this->data[$key] = trim($_GET[$key]);
				}
			}
			
			
			foreach(array('profession','problem','therapy','format','maxprice','gender','speciality') as $key){
				if(isset($_GET[$key]) && (is_array($_GET[$key]) && count($_GET[$key])) || strlen($_GET[$key]) > 0){
					$this->data[$key] = trim($_GET[$key]);
					$this->isFilterSearch = true;
				}
			}
		}

		if(!$this->isSubmitted && isset($_SESSION['location']) && !isset($this->data['location']) ){
			$this->data['location'] = $_SESSION['location'];
		}
		$this->buildSearchElements();
	}

	// when script finishes update cache if needed
	function __destruct(){
		
	}
		
	public function setNumResults($numResults){
		$this->numResults = (int)$numResults;
	}
	
	public function insert(){
		include('views/partials/SearchBox.tpl.php');
	}
	


	private function buildSearchElements(){
		//! Types of therapist
		//foreach($this->_getProfessions() as $name => $id){
			$form = new FormElement('select');
			$form->setLabel($name);
			$form->setName('profession');//[' . $id . ']');
			
			$selOpts = $this->_getProfessions(); 
			$form->setSelectOptions($selOpts);
			
			if( isset($_GET['profession'])){ //[$id]) ){
				$form->setCurrentValue( $_GET['profession'] ); //[$id] );
			}
			$form->setElementValue($id);
			//$form->setHelpText('Pages are put live once you tick the published box');
			$this->els->profession = $form->build();
		//}
		/*
		foreach($this->_getProblems() as $name => $id){
			$form = new FormElement('checkbox');
			$form->setLabel($name);
			$form->setName('problem[' . $id . ']');
			
			if( isset($_GET['problem'][$id]) ){
				$form->setCurrentValue( $_GET['problem'][$id] );
			}
			$form->setElementValue($id);
			//$form->setHelpText('Pages are put live once you tick the published box');
			$this->els->problems[] = $form->build();
		}
		
		foreach($this->_getTherapies() as $name => $id){
			$form = new FormElement('checkbox');
			$form->setLabel($name);
			$form->setName('therapy[' . $id . ']');
			
			if( isset($_GET['therapy'][$id]) ){
				$form->setCurrentValue( $_GET['therapy'][$id] );
			}
			$form->setElementValue($id);
			//$form->setHelpText('Pages are put live once you tick the published box');
			$this->els->therapies[] = $form->build();
		}
		
		foreach($this->_getFormats() as $name => $id){
			$form = new FormElement('checkbox');
			$form->setLabel($name);
			$form->setName('format[' . $id . ']');
			
			if( isset($_GET['format'][$id]) ){
				$form->setCurrentValue( $_GET['format'][$id] );
			}
			$form->setElementValue($id);
			//$form->setHelpText('Pages are put live once you tick the published box');
			$this->els->formats[] = $form->build();
		}
		*/
		
	
	}
	
	
	
	private function _getFormats(){
		$q = "SELECT format_id, format FROM listing_formats";
		$rows = Common::getRows($q);
		$selOpts = array('Any'=> '');
		foreach($rows as $row){
			$selOpts[ $row['format'] ] = $row['format_id'];
		}
		return $selOpts;
	}
	
	private function _getProblems(){
		$q = "SELECT section_id, section_name FROM listing_problem";
		$rows = Common::getRows($q);
		$selOpts = array();
		foreach($rows as $row){
			$selOpts[ $row['section_name'] ] = $row['section_id'];
		}
		return $selOpts;
	}
	
	private function _getTherapies(){
		$q = "SELECT section_id, section_name FROM listing_approach";
		$rows = Common::getRows($q);
		$selOpts = array();
		foreach($rows as $row){
			$selOpts[ $row['section_name'] ] = $row['section_id'];
		}
		return $selOpts;
	}
	
	private function _getProfessions(){
		$q = "SELECT * FROM listing_type ORDER BY listing_type ASC";
		$rows = Common::getRows($q);
		$selOpts = array('Any' => '');
		foreach($rows as $row){
			$selOpts[ $row['listing_type'] ] = $row['listing_type_id'];
		}
		return $selOpts;
	}
}


?>