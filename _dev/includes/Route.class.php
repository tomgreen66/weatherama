<?php
/**
 * Route discovery - translates URLs into database items
 * @version 0.1
 * @package condiment
 * @subpackage Core
 * @author David Harris <theinternetlab@gmail.com>
 */

//! convert this to a singleton so info can be called from anywhere


//! add isOwner - so we can check they're owner
class Route{
	
	private $url;
	private $parts = array();
	private $cache = array();
	private $isNewSite = false;
	public $isSiteAdmin = false;
	private $dbRows;
	private $page = array();
	private $editable = false;
	private $isPublished = true;
	private $isOwner = false;
	public $isRestricted = false;
	private $type = 'pages';
	
	// url - full URL of page - including domain & GET variables
	function __construct($url){
		$this->url = trim($url," \t\n\r\0\x0B/");
		if( $this->url === ""){
		//homepage is a special case
			$this->parts = array('home');
		}else{
			$this->parts = explode('/', $this->url);
		}
		$this->checkRoute();
	}

	// when script finishes update cache if needed
	function __destruct(){
		
	}
	
	public function getRouteInfo(){
		return $this->page;
	}
	
	public function getType(){
		return $this->type;
	}
	
	public function getController(){
	//	if($this->type == 'admin'){
	//		return 'controllers/' . $this->type . '/' . $this->page['template_file'] . '.class.php';
	//	}else{
			return 'controllers/' . $this->type . '/' . $this->page['template_file'] . '.class.php';
	//	}
	}
	
	// get the routes for this URL - self ordering based on the template priority set in the template order
	private function checkRoute(){
		$csv = implode("','", $this->parts);
		//! change the query to only return what we need after dev
		//var_dump( $this->url );
		// the query needs to do parent checks incase of multiple different level content
		// this query is over simplified - for start dev
		/*
		$q = "SELECT p.page_id, p.urlname, p.title, p.template_id ,p.uri ,p.owner_id, p.publish, t.template_file, t.page_type, t.edit_form, t.settings
				FROM pages p, templates t
				WHERE 
				p.template_id = t.template_id 
				AND
				p.urlname IN('" . $csv . "')
				ORDER BY 
				t.order DESC, FIELD(p.urlname, '" . $csv . "') DESC";
		*/	
		//using the uri
		$q = "SELECT p.page_id, p.urlname, p.title, p.template_id ,p.uri ,p.owner_id, p.restricted , p.publish, p.page_settings, t.template_file, t.page_type, t.edit_form, t.settings
				FROM pages p, templates t
				WHERE 
				p.template_id = t.template_id 
				AND
				p.uri = '/" . $this->url . "'
				ORDER BY 
				t.order DESC, FIELD(p.urlname, '" . $csv . "') DESC";

		$this->dbRows = Common::getRows($q);
		
		if( isset( $this->dbRows[0]['page_type'] ) ){
			$this->type = $this->dbRows[0]['page_type'];
		}elseif( $this->parts[0] == 'admin'){
			$this->type = 'admin';
		}
		
		if( '' != $this->dbRows[0]['page_settings']  ){
			$this->dbRows[0]['page_settings'] = unserialize($this->dbRows[0]['page_settings']);
		}
		
		
		
		if($_GET['dev']){
			echo '<h1>Route</h1>';
			echo $q . '<br /><pre>';
			var_dump($this->dbRows);
			echo '</pre>';
		}
		
		//check is published or previewing?
		if( $this->dbRows[0]['publish'] != '1'){
			$this->isPublished = false;
		}
		
		//is this page restricted?
		if( $this->dbRows[0]['restricted'] != '0' ){
			if( !isset($_SESSION['level_id']) ){
			//not logged in
				$this->isRestricted = true;
			}
			
			//or logged in and level is higher than restricted (site admin = 1 free =3)
			if( (int)$_SESSION['level_id'] > (int)$this->dbRows[0]['restricted'] ){
				$this->isRestricted = true;
			}
			
		}
		
		// is this user the owner? or the site admin
		if( isset($_SESSION['user_id']) && ($this->dbRows[0]['owner_id'] == $_SESSION['user_id'] || $_SESSION['level'] == 'siteadmin' )){
			$this->editable = true;
		}
		
		if( isset($_SESSION['user_id']) && $this->dbRows[0]['owner_id'] == $_SESSION['user_id']){
			$this->isOwner = true;
		}
		
		//is this the global admin user?
		if( isset($_SESSION['name']) && $_SESSION['name'] == 'admin' && $_SESSION['level'] == 'siteadmin' ){
			$this->isSiteAdmin = true;
		}
		
		$this->makePageInfo($this->dbRows[0]);
		
		
	}
	
	// this is for the breadcrumb builder - so it can access the full URL parts
	public function getAllParts(){
	
	}
	
	// check to see if this user can edit theis page / route
	public function canEdit(){
		return $this->editable;
	}
	
	
	public function canView(){
		if( !$this->isRestricted && ($this->isPublished || $this->editable)){
			return true;
		}else{
			if($_GET['dev']){
				echo ' NOT PUBLISHED ';
			}
			return false;
		}

	}
	
	// make the details to describe the page to pass onto other classes
	private function makePageInfo($array){
		$this->page = $array;
		$this->page['isOwner'] = $this->isOwner;
		$this->page['editable'] = $this->editable;
		$this->page['parts'] = $this->parts;
		$this->page['isPublished'] = $this->isPublished;
		$this->page['isSiteAdmin'] = $this->isSiteAdmin;
	}
	
	public function getInfo(){
		return var_export($this->parts);
	}
	
	public function isLoggedIn(){
		if( isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] == true ){
			return true;
		}else{
			return false;
		}
	}
	
	//is this a new sub site?
	public function isNewSite(){
		return $this->isNewSite;
	}

}

?>