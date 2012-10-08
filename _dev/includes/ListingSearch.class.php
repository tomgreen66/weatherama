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

class ListingSearch{
	
	public $str;
	public $numRows = false;
	public $pagination;
	public $Geocode;
	//1 = free 4 = gold
	private $searchLevels = array( 1=>200000, 2=>35000, 3=>35000, 4=>35000 );
	private $levelMultiplier = 13000; // 16000m = 10 miles
	private $searchDistance = 200000; // in meters
	private $ftfields = 'l.name,l.summary,l.profile,l.history,l.testimonials,l.sum_ages,l.sum_approach,l.sum_format,l.sum_memberships,l.sum_problems,l.sum_type';
	private $searchQ = array();
	private $doSearch = false; // dont do search unless there is at least 1 paramaeter
	private $vars = array();
	public $limit = 20;
	private $page = 1;
	private $totalRows = 0;
	
	
	public function __construct($vars) {
		$this->vars = $vars;
		
		if(isset($_GET['page']) && is_numeric($_GET['page']) ){
			$this->setPage($_GET['page']);
		}
	}
	
	public function setPage($num){
		$num = (int)$num;
		if($num === 0){
			$this->page = 1;
		}else{
			$this->page = $num;
		}
	}
	
	public function setDistance($dist){
		$this->searchDistance = (int)$dist;
	}
		
	public function withNumRows($bool){
		$this->numRows = $bool;
	}
	
	
	public function setNumRows($num){
		$this->totalRows = (int)$num;
	}
	
	public function makePagination(){
		$this->pagination = new Pagination('page', $this->totalRows , URL, $this->limit );
	}
	
	
	public function search(){
		// geolocate the search
		

		// $q = $this->makeQueryNoSplit();
		$q = $this->makeQuery();
		// echo ' <!-- ' . $q . ' --> ';	
		if($_GET['dev'] == '1'){
			echo '<br /><pre><b>' . $q . '</b></pre><br />';
		}
		
		if($this->doSearch){
			
			return Common::getRows($q,false,true);
			
		}else{
			return false;
		}
		

	}

	// this creates the SQL query - 2 types , one gives results, one gives number of results
	private function makeQuery(){
		
		$this->searchQ['select'] = array('l.name','l.summary','l.sum_approach','l.sum_problems','l.address_line_1','l.address_line_2','l.town','l.county','l.postcode','l.country','l.phone','l.latitude','l.longitude','l.publish_address','l.publish_phone','l.approved','p.uri','p.urlname','e.level','y.listing_type');
		$this->searchQ['select'][] = '(SELECT i.image FROM listing_images i WHERE l.listing_id=i.listing_id ORDER BY i.sort_order ASC LIMIT 1) AS image';
		$this->searchQ['from'] = 'listing_level e, listing_type y, pages p, listing l '; //should always be the same...
		$this->searchQ['where'] = array('e.level_id = l.level_id','y.listing_type_id = l.listing_type_id','l.page_id = p.page_id','l.publish = 1');//,'p.publish = 1');
		
		// set up the initial search variables		
		//! location
		if( strlen($this->vars['location']) ){
		
			$this->Geocode = new Geolocation( $this->vars['location']. ', UK' );
			$this->Geocode->lookup();
			// using the OS grid as makes calculation simpler than using lng/lat - can use simple pythag rather than calculus functions
			// we shorten the list of rows needing the calculation by the greater / less than in the where
			
			$calculation = ' SQRT(POW(( l.easting - ' . round($this->Geocode->getOSEast()) . '),2) + POW((l.northing - ' . round($this->Geocode->getOSNorth()) . '),2)) ';
			$this->searchQ['select'][] = $calculation . " AS distance";
			$this->searchQ['where'][] = "northing < " . round( $this->Geocode->getOSNorth() + $this->searchDistance ) ; 
			$this->searchQ['where'][] = "northing > " . round( $this->Geocode->getOSNorth() - $this->searchDistance ) ;
			$this->searchQ['where'][] = "easting < " . round( $this->Geocode->getOSEast() + $this->searchDistance ) ;
			$this->searchQ['where'][] = "easting > " . round( $this->Geocode->getOSEast() - $this->searchDistance ) ;
		//	$this->searchQ['where'][] = "(" . $calculation . ") <= " . $this->searchDistance ;
		// use having rather than where
			$this->searchQ['having'][] = "distance <= " . $this->searchDistance;
			//$this->searchQ['orderby'][] = '(distance - ((l.level_id-1)*16000)) ASC'; // (x^3 -400^3)^(1/3)
			
			// using a exponential function to order distances based on the listing level and multiplying by 16000 m = 16km = 10 miles
			// then raising to power 3, deleteing the distance cubed and then 1/3 (cube root), so only makes a diffenrce within that area
			
			$this->searchQ['orderby'][] = ' POW( (POW( distance ,3) - POW(((l.level_id-1)*' . $this->levelMultiplier. '),3)),(1/3)) ASC ';
			$this->searchQ['orderby'][] = 'l.level_id DESC';
			$this->doSearch = true;
		}else{
			$this->searchQ['orderby'][] = 'l.level_id DESC';
		}
		
		//! freetext
		if( strlen($this->vars['term']) ){
			$this->searchQ['select'][] = "MATCH(" . $this->ftfields . ") AGAINST ('" . $this->booleanAndTerm($this->vars['term']) . "' IN BOOLEAN MODE) AS score";
			//$this->searchQ['where'][] = "MATCH(" . $ftfields . ") AGAINST ('" . $this->booleanAndTerm($this->vars['term']) . "' IN BOOLEAN MODE)";
			// use having rather than where
			$this->searchQ['having'][] = "score > 0";
			$this->searchQ['orderby'][] = 'score DESC';
			$this->doSearch = true;
		}
		
		
		//! **** FILTERS ****
		//! profession
		if( isset($this->vars['profession']) && strlen($this->vars['profession']) ){
		
			//$this->searchQ['where'][] = " l.listing_type_id IN (" . implode(',', $this->vars['profession']) . ") ";
			$this->searchQ['where'][] = " l.listing_type_id = " . (int)$this->vars['profession'] . " ";
			$this->doSearch = true;
		}
		//! max price
		if( isset($this->vars['maxprice']) && is_numeric($this->vars['maxprice']) ){
		
			$this->searchQ['where'][] = " l.hourly_rate <= " . (int)$this->vars['maxprice'] . " ";
			$this->doSearch = true;
		}
		
		//! gender
		if( isset($this->vars['gender']) && in_array($this->vars['gender'] , array('M','F')) ){
		
			$this->searchQ['from'] .= ', listing_to_gender ltg';
			$this->searchQ['where'][] = " ltg.listing_id = l.listing_id ";
			$this->searchQ['where'][] = " ltg.gender = '" . $this->vars['gender'] . "' ";
			$this->doSearch = true;
		}
	
		//! speciality
		if( isset($this->vars['speciality']) && is_numeric($this->vars['speciality']) ){
		
			$this->searchQ['from'] .= ', listing_to_age ltag';
			$this->searchQ['where'][] = " ltag.listing_id = l.listing_id ";
			$this->searchQ['where'][] = " ltag.age_id = " . (int)$this->vars['speciality'] . " ";
			$this->doSearch = true;
		}
		
		//! ** build the query **
		$q = "SELECT " . (($this->numRows)?' SQL_CALC_FOUND_ROWS ':'') . implode(',', $this->searchQ['select']). "
				FROM " . $this->searchQ['from'] . "
				WHERE
					" . implode(' AND ', $this->searchQ['where']) ;

		if( isset($this->searchQ['having']) && count($this->searchQ['having']) ){
			$q .= " HAVING " . implode(' AND ' , $this->searchQ['having']);
		}
		
		$q .= "	ORDER BY " . implode(',', $this->searchQ['orderby']) . " ";
		
		$q .= "	LIMIT " . ( ($this->page-1)* $this->limit) . "," . $this->limit . " "; 
	
		return $q;
		
	}

	
	// this creates the SQL query - 2 types , one gives results, one gives number of results
	private function makeQueryNoSplit(){
		
		$this->searchQ['select'] = array('l.name','l.summary','l.sum_approach','l.sum_problems','l.address_line_1','l.address_line_2','l.town','l.county','l.postcode','l.country','l.phone','l.image','l.latitude','l.longitude','l.publish_address','l.publish_phone','l.approved','p.uri','p.urlname','e.level','y.listing_type');
		$this->searchQ['orderby'][] = 'l.level_id DESC';
		$this->searchQ['from'] = 'listing l, listing_level e, listing_type y, pages p'; //should always be the same...
		$this->searchQ['where'] = array('e.level_id = l.level_id','y.listing_type_id = l.listing_type_id','l.page_id = p.page_id','l.publish = 1');//,'p.publish = 1');
		
		// set up the initial search variables		
		//! location
		if( strlen($this->vars['location']) ){
		
			$this->Geocode = new Geolocation( $this->vars['location']. ', UK' );
			$this->Geocode->lookup();
			// using the OS grid as makes calculation simpler than using lng/lat - can use simple pythag rather than calculus functions
			// we shorten the list of rows needing the calculation by the greater / less than in the where
			
			$calculation = ' SQRT(POW(( l.easting - ' . round($this->Geocode->getOSEast()) . '),2) + POW((l.northing - ' . round($this->Geocode->getOSNorth()) . '),2)) ';
			$this->searchQ['select'][] = $calculation . " AS distance";
			$this->searchQ['where'][] = "northing < " . round( $this->Geocode->getOSNorth() + $this->searchDistance ) ; 
			$this->searchQ['where'][] = "northing > " . round( $this->Geocode->getOSNorth() - $this->searchDistance ) ;
			$this->searchQ['where'][] = "easting < " . round( $this->Geocode->getOSEast() + $this->searchDistance ) ;
			$this->searchQ['where'][] = "easting > " . round( $this->Geocode->getOSEast() - $this->searchDistance ) ;
		//	$this->searchQ['where'][] = "(" . $calculation . ") <= " . $this->searchDistance ;
		// use having rather than where
			$this->searchQ['having'][] = "distance <= " . $this->searchDistance;
			$this->searchQ['orderby'][] = 'distance ASC';
			$this->doSearch = true;
		}
		//! freetext
		if( strlen($this->vars['term']) ){
			$this->searchQ['select'][] = "MATCH(" . $this->ftfields . ") AGAINST ('" . $this->booleanAndTerm($this->vars['term']) . "' IN BOOLEAN MODE) AS score";
			//$this->searchQ['where'][] = "MATCH(" . $ftfields . ") AGAINST ('" . $this->booleanAndTerm($this->vars['term']) . "' IN BOOLEAN MODE)";
			// use having rather than where
			$this->searchQ['having'][] = "score > 0";
			$this->searchQ['orderby'][] = 'score DESC';
			$this->doSearch = true;
		}
		
		
		//! **** FILTERS ****
		//! profession
		if( isset($this->vars['profession']) && strlen($this->vars['profession']) ){
		
			//$this->searchQ['where'][] = " l.listing_type_id IN (" . implode(',', $this->vars['profession']) . ") ";
			$this->searchQ['where'][] = " l.listing_type_id = " . (int)$this->vars['profession'] . " ";
			$this->doSearch = true;
		}
		//! max price
		if( isset($this->vars['maxprice']) && is_numeric($this->vars['maxprice']) ){
		
			$this->searchQ['where'][] = " l.hourly_rate <= " . (int)$this->vars['maxprice'] . " ";
			$this->doSearch = true;
		}
		
		//! gender
		if( isset($this->vars['gender']) && in_array($this->vars['gender'] , array('M','F')) ){
		
			$this->searchQ['from'] .= ', listing_to_gender ltg';
			$this->searchQ['where'][] = " ltg.listing_id = l.listing_id ";
			$this->searchQ['where'][] = " ltg.gender = '" . $this->vars['gender'] . "' ";
			$this->doSearch = true;
		}
	
		//! speciality
		if( isset($this->vars['speciality']) && is_numeric($this->vars['speciality']) ){
		
			$this->searchQ['from'] .= ', listing_to_age ltag';
			$this->searchQ['where'][] = " ltag.listing_id = l.listing_id ";
			$this->searchQ['where'][] = " ltag.age_id = " . (int)$this->vars['speciality'] . " ";
			$this->doSearch = true;
		}
		
		//! ** build the query **
		$q = "SELECT " . (($this->numRows)?' SQL_CALC_FOUND_ROWS ':'') . implode(',', $this->searchQ['select']). "
				FROM " . $this->searchQ['from'] . "
				WHERE
					" . implode(' AND ', $this->searchQ['where']) ;
					
		if( isset($this->searchQ['having']) && count($this->searchQ['having']) ){
			$q .= " HAVING " . implode(' AND ' , $this->searchQ['having']);
		}
		
		$q .= "	ORDER BY " . implode(',', $this->searchQ['orderby']) . " ";

		$q .= "	LIMIT " . ( ($this->page-1)* $this->limit) . "," . $this->limit . " "; 
			
		return $q;
		
	}

	// this creates the SQL query - multiple distances
	private function makeQuerySplit(){
		
		$this->searchQ['select'] = array('l.name','l.level_id AS levelId','l.summary','l.sum_approach','l.sum_problems','l.address_line_1','l.address_line_2','l.town','l.county','l.postcode','l.country','l.phone','l.image','l.latitude','l.longitude','l.publish_address','l.publish_phone','l.approved','p.uri','p.urlname','e.level','y.listing_type');
		$this->searchQ['orderby'][] = 'levelId DESC';
		$this->searchQ['from'] = 'listing l, listing_level e, listing_type y, pages p'; //should always be the same...
		$this->searchQ['where'] = array('e.level_id = l.level_id','y.listing_type_id = l.listing_type_id','l.page_id = p.page_id','l.publish = 1');//,'p.publish = 1');
		
		
		//! freetext
		if( strlen($this->vars['term']) ){
			$this->searchQ['select'][] = "MATCH(" . $this->ftfields . ") AGAINST ('" . $this->booleanAndTerm($this->vars['term']) . "' IN BOOLEAN MODE) AS score";
			//$this->searchQ['where'][] = "MATCH(" . $ftfields . ") AGAINST ('" . $this->booleanAndTerm($this->vars['term']) . "' IN BOOLEAN MODE)";
			// use having rather than where
			$this->searchQ['having'][] = "score > 0";
			$this->searchQ['orderby'][] = 'score DESC';
			$this->doSearch = true;
		}
		
		
		//! **** FILTERS ****
		//! profession
		if( isset($this->vars['profession']) && strlen($this->vars['profession']) ){
		
			//$this->searchQ['where'][] = " l.listing_type_id IN (" . implode(',', $this->vars['profession']) . ") ";
			$this->searchQ['where'][] = " l.listing_type_id = " . (int)$this->vars['profession'] . " ";
			$this->doSearch = true;
		}
		//! max price
		if( isset($this->vars['maxprice']) && is_numeric($this->vars['maxprice']) ){
		
			$this->searchQ['where'][] = " l.hourly_rate <= " . (int)$this->vars['maxprice'] . " ";
			$this->doSearch = true;
		}
		
		//! gender
		if( isset($this->vars['gender']) && in_array($this->vars['gender'] , array('M','F')) ){
		
			$this->searchQ['from'] .= ', listing_to_gender ltg';
			$this->searchQ['where'][] = " ltg.listing_id = l.listing_id ";
			$this->searchQ['where'][] = " ltg.gender = '" . $this->vars['gender'] . "' ";
			$this->doSearch = true;
		}
	
		//! speciality
		if( isset($this->vars['speciality']) && is_numeric($this->vars['speciality']) ){
		
			$this->searchQ['from'] .= ', listing_to_age ltag';
			$this->searchQ['where'][] = " ltag.listing_id = l.listing_id ";
			$this->searchQ['where'][] = " ltag.age_id = " . (int)$this->vars['speciality'] . " ";
			$this->doSearch = true;
		}
		
		if( strlen($this->vars['location']) ){
			//! ** build the location query **
			
			$this->Geocode = new Geolocation( $this->vars['location']. ', UK' );
			$this->Geocode->lookup();
			$union = array();
			
			$this->doSearch = true;
			$this->searchQ['orderby'][] = 'distance ASC';
			$c = 1;	
			foreach($this->searchLevels as $level_id => $distance){
			// set up the initial search variables		
			//! location
				
				//load in current other query info for this level
				$l_select = $this->searchQ['select'];
				$l_where = $this->searchQ['where'];
				$l_having = $this->searchQ['having'];
				
				// using the OS grid as makes calculation simpler than using lng/lat - can use simple pythag rather than calculus functions
				// we shorten the list of rows needing the calculation by the greater / less than in the where
				
				$calculation = ' SQRT(POW(( l.easting - ' . round($this->Geocode->getOSEast()) . '),2) + POW((l.northing - ' . round($this->Geocode->getOSNorth()) . '),2)) ';
				$l_select[] = $calculation . " AS distance";
				$l_where[] = "l.level_id = " . $level_id;
				$l_where[] = "northing < " . round( $this->Geocode->getOSNorth() + $distance ) ; 
				$l_where[] = "northing > " . round( $this->Geocode->getOSNorth() - $distance ) ;
				$l_where[] = "easting < " . round( $this->Geocode->getOSEast() + $distance ) ;
				$l_where[] = "easting > " . round( $this->Geocode->getOSEast() - $distance ) ;
			//	$this->searchQ['where'][] = "(" . $calculation . ") <= " . $this->searchDistance ;
			// use having rather than where
				$l_having[] = "distance <= " . $distance;
				//$this->searchQ['orderby'][] = 'distance ASC';
				
				$l_q = "SELECT " . (($this->numRows) && 1 == $c ?' SQL_CALC_FOUND_ROWS ':'') . implode(',', $l_select). "
					FROM " . $this->searchQ['from'] . "
					WHERE
						" . implode(' AND ', $l_where) ;
	
				if( count($l_having) ){
					$l_q .= " HAVING " . implode(' AND ' , $l_having);
				}
				
				$union[] = $l_q;
				$c++;
				
			}
			
			$q = '(' . implode(' ) UNION ALL (', $union) . ') ';
			
			$q .= "	ORDER BY " . implode(',', $this->searchQ['orderby']) . " ";
			$q .= "	LIMIT " . ( ($this->page-1)* $this->limit) . "," . $this->limit . " ";
		
		
		}else{
		
		
			//! ** build the non-location query **
			$q = "SELECT " . (($this->numRows)?' SQL_CALC_FOUND_ROWS ':'') . implode(',', $this->searchQ['select']). "
					FROM " . $this->searchQ['from'] . "
					WHERE
						" . implode(' AND ', $this->searchQ['where']) ;
						
			if( isset($this->searchQ['having']) && count($this->searchQ['having']) ){
				$q .= " HAVING " . implode(' AND ' , $this->searchQ['having']);
			}
			
			$q .= "	ORDER BY " . implode(',', $this->searchQ['orderby']) . " ";
	
			$q .= "	LIMIT " . ( ($this->page-1)* $this->limit) . "," . $this->limit . " "; 
		}
		//echo $q;		
		return $q;
		
	}

	
	public function getData(){
		return $this->data;
	}
	
	public function booleanAndTerm($term){
		//echo '<!-- ' . $term . ' ';
		$term = preg_replace("/[^ a-zA-Z0-9]/", '', $term);
		
		$term = mysql_real_escape_string(trim($term));
		$terms = explode(' ', $term);
		$return = '';
		foreach($terms as $word){
			if(strlen($word)>2){
				// if has a plural then try without
				if(substr($word,-1) == 's'){
					$return .= '+(>' . $word. ' <' . substr($word,0,-1) . '*) ';
				}else{
				// if singular then try with an s
					$return .= '+(>' . $word . ' <' . $word . 's) ';
				}
			}
		}
		//echo $return;
		//echo ' --> ';
		return $return;
	}
		
	
	
	/**
	  * 
	  * @body: string; the body of the page returned
	  * @searchTerms: string; the searched terms
	  *
	  * @returns: string - short snippet of 240(default) characters near the search term
	**/

	public function searchSnippet($body,$searchTerms,$maxChar = 240){
		$searches = explode(' ', $searchTerms);
		$len = count($searches);
		$str = strip_tags($body);
		$chunk = '';
		for ($i = 0; $i < $len; $i++){
		
			if (preg_match("/$searches[$i]/i",$str)){
				$pos = stripos ($str,$searches[$i]);
				if (($pos - ($maxChar/2)) < 0){
					$startPos = 0;
				}else{
					$startPos = ($pos - ($maxChar/2));
					$chunk .= '...';
				}
				
				$chunk .= substr($str,$startPos,$maxChar);
				
				if (($pos + ($maxChar/2)) < strlen($str)){
					$chunk .= '...';
				}
				break;
			}
		}
		if ($chunk == ''){
			$chunk = substr($str,0,$maxChar).'...';
		}
		$chunk = strip_tags ($chunk);
		foreach ($searches as $term){
			$chunk = str_ireplace ($term,"<em>$term</em>",$chunk);
		}
		return $chunk;
	}
	
}

?>