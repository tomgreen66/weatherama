<?php
######################## PERFORM A LISTING SEARCH  #################################
#
# .eg:
# $result = search->find('spakment', array('email'), 'users');
# 
# Add indexes:
# ALTER TABLE listing ADD FULLTEXT ftindex 
# (name,address_line_1,address_line_2,town,county,postcode) 
# ALTER TABLE users ADD FULLTEXT ftindex 
# (user,nicename,email,display_name)
################################################################################################

class ListingData{
	
	public $listing_id;
	public $page_id;
	public $data;
	public $isPreview = false;
	
	public function __construct() {

	}
	
	public function setListingId($id){
		$this->listing_id = (int)$id;
		$this->getCoreListing();
	}
	
	public function setPageId($id){
		$this->page_id = (int)$id;
		$this->getCoreListing();
	}
	
	public function isPreview($bool){
		$this->isPreview = $bool;
	}
	
	public function getData(){
		return $this->data;
	}
	
	private function getCoreListing(){
		if($this->isPreview){
			$publishCheck = ' ';
		}else{
			$publishCheck = ' AND l.publish = 1 ';// AND p.publish = 1 ';
		}
	
		if( is_numeric($this->listing_id) && $this->listing_id > 0 ){
		
			$q = "SELECT l.* , l.level_id, e.level, p.urlname, p.owner_id, y.listing_type as profession, c.country_name
				FROM listing_level e, listing_type y, pages p, listing l
					LEFT JOIN countries c ON l.country = c.country_code
				WHERE 
					e.level_id = l.level_id
					AND
					y.listing_type_id = l.listing_type_id
					AND
					p.page_id = l.page_id
					AND				
					l.listing_id = " . $this->listing_id . $publishCheck . "
					LIMIT 1";
		
		}elseif( is_numeric($this->page_id) && $this->page_id > 0){
		
			$q = "SELECT l.* , l.level_id, e.level, p.urlname, p.owner_id, y.listing_type as profession, c.country_name
				FROM listing_level e, listing_type y, pages p, listing l
					LEFT JOIN countries c ON l.country = c.country_code	
				WHERE 
					e.level_id = l.level_id
					AND
					y.listing_type_id = l.listing_type_id
					AND
					p.page_id = l.page_id
					AND
					l.page_id = " . $this->page_id . $publishCheck . "
					LIMIT 1";
		}
		
		list($this->data['listing']) = Common::getRows($q);
		
		if( isset($this->data['listing']['listing_id']) ){
			
			
			
			$this->data['listing']['level_id'] = (int)$this->data['listing']['level_id'];
			
			$images = $this->getImages();
			
			if( is_array($images)){
				$this->data['listing']['image'] = array_shift($images);
				if($this->data['listing']['level_id'] > 3 && count($images) > 0 ){
					$this->data['listing']['extra_images'] = $images;
				}
				
			}
			/*
			if($this->data['listing']['level_id'] > 3 && is_array($images) && count($images) > 1 ){
				$this->data['listing']['extra_images'] = unserialize($this->data['listing']['extra_images']);
			}else{
				$this->data['listing']['image'] = array_shift($images);
			}
			*/
			$this->data['listing']['testimonials'] = unserialize($this->data['listing']['testimonials']);
			
		}
		
		$this->data['listing']['can_contact'] = false;
		
		if( Validator::_emailtest($this->data['listing']['email']) ){
			$this->data['listing']['can_contact'] = true;
		}
			
	
	}
	
	public function getImages(){
		$q = "SELECT image  
				FROM listing_images 
				WHERE 
					listing_id = " . $this->data['listing']['listing_id'] ."
				ORDER BY sort_order ASC";
		$rows = Common::getRows($q);
		if(count($rows)){
			$return = array();
			foreach($rows as $row){
				$return[] = $row['image'];
			}
		}else{
			$return = false;
		}
		return $return;
	}

	
	
	public function getFullDetails(){
		if( isset($this->data['listing']['listing_id']) ){
			$this->getTherapies();
			$this->getProblems();
			$this->getFormats();
			$this->getLanguages();
			$this->getGenders();
			$this->getAges();
			$this->getMemberships();
			$this->getQualifications();	
		}
	}
	
	public function getAges(){
		$q = "SELECT a.age 
				FROM listing_ages a, listing_to_age lta 
				WHERE 
					lta.listing_id = " . $this->data['listing']['listing_id'] ."
					AND
					a.age_id = lta.age_id";
		$rows = Common::getRows($q);
		if(count($rows)){
			$this->data['ages'] = array();
			foreach($rows as $row){
				$this->data['ages'][] = $row['age'];
			}
		}else{
			$this->data['ages'] = false;
		}
	}
	
	public function getLanguages(){
		$q = "SELECT a.language 
				FROM languages a, listing_to_language lta 
				WHERE 
					lta.listing_id = " . $this->data['listing']['listing_id'] ."
					AND
					a.language_code = lta.language_code";
		$rows = Common::getRows($q);
		if(count($rows)){
			$this->data['languages'] = array();
			foreach($rows as $row){
				$this->data['languages'][] = $row['language'];
			}
		}else{
			$this->data['languages'] = false;
		}
	}
	
	
	public function getMemberships(){

		$q = "SELECT a.membership, a.web, lta.profile  
				FROM listing_memberships a, listing_to_membership lta 
				WHERE 
					a.membership_id = lta.membership_id 
					AND
					lta.listing_id = " . $this->data['listing']['listing_id'] ;
		$this->data['memberships'] = Common::getRows($q);
		
	}
	
	public function getQualifications(){

		$q = "SELECT qualification  
				FROM listing_qualifications 
				WHERE 
					listing_id = " . $this->data['listing']['listing_id'] ;
					
		$this->data['qualifications'] = Common::getRows($q);
		
	}
	
	public function getGenders(){

		$q = "SELECT lta.gender 
				FROM listing_to_gender lta 
				WHERE 
					lta.listing_id = " . $this->data['listing']['listing_id'] ;
		$rows = Common::getRows($q);
		if(count($rows)){
			$this->data['genders'] = array();
			foreach($rows as $row){
				switch($row['gender']){
					case 'M':
						$this->data['genders'][] = 'Male';
					break;
					default:
						$this->data['genders'][] = 'Female';
					break;
				}
				
			}
		}else{
			$this->data['genders'] = false;
		}
	}
	
	public function getFormats(){
		$q = "SELECT a.format 
				FROM listing_formats a, listing_to_format lta 
				WHERE 
					lta.listing_id = " . $this->data['listing']['listing_id'] ."
					AND
					a.format_id = lta.format_id";
		$rows = Common::getRows($q);
		if(count($rows)){
			$this->data['formats'] = array();
			foreach($rows as $row){
				$this->data['formats'][] = $row['format'];
			}
		}else{
			$this->data['formats'] = false;
		}
	}
	
	public function getTherapies(){
		$q = "SELECT a.section_name 
				FROM listing_approach a, listing_to_approach lta 
				WHERE 
					lta.listing_id = " . $this->data['listing']['listing_id'] ."
					AND
					a.section_id = lta.cat_id
				ORDER BY a.section_name ";
		$rows = Common::getRows($q);
		if(count($rows)){
			$this->data['therapies'] = array();
			foreach($rows as $row){
				$this->data['therapies'][] = $row['section_name'];
			}
		}else{
			$this->data['therapies'] = false;
		}
	}
	
	public function getProblems(){
		$q = "SELECT a.section_name 
				FROM listing_problem a, listing_to_problem lta 
				WHERE 
					lta.listing_id = " . $this->data['listing']['listing_id'] ."
					AND
					a.section_id = lta.cat_id
				ORDER BY a.section_name ";
		$rows = Common::getRows($q);
		if(count($rows)){
			$this->data['problems'] = array();
			foreach($rows as $row){
				$this->data['problems'][] = $row['section_name'];
			}
		}else{
			$this->data['problems'] = false;
		}
	}	
}

?>