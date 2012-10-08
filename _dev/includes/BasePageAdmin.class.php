<?php
/**
 * Page Builder - this takes the routing information and gets all the neccessary content
 * @version 0.1
 * @package condiment
 * @subpackage Page
 * @author David Harris <theinternetlab@gmail.com>
 */

class BasePageAdmin{
	
	private $state;
	private $theme = 'admin';

	public $pageInfo;
	public $title;
	public $baseContent;
	public $cacheName;
	public $widgets;

	public $headerTemplate;
	public $footerTemplate;
	public $main = ''; // this is the store for the view buffer
	public $user_id;
	public $body_class;
	public $settings;
	
	public $data = array(); // for storing post data
	public $original = array(); // for checking new data vs the original data in the database
	public $editFile; // this is the template file loaded in for editting 
	public $validate;
	public $els; // form elements
	public $posted = false;
	public $hasErrors = false;
	public $action;
	public $checkboxList = array();
	public $meta = array('charset' => 'UTF-8');
	public $css = array();
	public $js = array('mootools','mootools-more','tinymce','tinymce_init_page');


	/* 
	 * array - all the info about the page 
	 * we need to get all the page and template 
	 * data from the databse and build the page
	 */ 
	function __construct(){
		
		//$this->setRoute($array);
		
		//include '';
		if( in_array($_GET['action'], array('edit','new','add','delete','list','move')) ){
			$this->action = $_GET['action'];
		}
		$this->validate = new Validator();
		
		$this->getBasePageContent();
		
		$this->setTheme();
		$this->setOwnerId();
		
		$this->processImages();
		
		if( isset($_POST) && count($_POST) ){
		
			$this->posted = true;
			
			//if images are sent deal with them
			// need to setup the folder name in the extending class
			
			foreach($_POST as $k => $v){
				$this->data[$k] = $v; 
			}
			//var_dump( $this->data );
		}
				
	}
	
	// when script finishes update cache if needed
	function __destruct(){
		
	}
	
	
	public function processImages(){
	
		// deal with the delete checkbox fieldname_delete
		if( false ){
		
		}

		//check for uploads & deal with files
		if(count($_FILES) > 0){
		
			$upload_path = SERVER_PATH . 'files/' . $this->imageFolder . '/';
			//var_dump( $upload_path );
			// the upload file will be fieldname_upload
			foreach($_FILES as $key => $file){
				//var_dump( $key , $file );
				list($datakey,) = explode('_upload', $key);
				//var_dump( $datakey );
				// some browsers send the file info even if theres no file
				// so check there is something there not just an empty field 
				if($file['size'] > 1){
					
					$upload = new Upload($_FILES[$key]);
					$upload->setDir($upload_path);
					
					// always store as original
					$upload->setOptions( array('originals' => 'none'));
					$upload->setRestrictions(array('image'));
					$upload->upload();
				
					// add saved filename to the data array to add into db
					if(!$upload->getErrors()) {
						$this->data[$datakey] = $upload->getFileName();
					}else{
						$this->validate->log_error($upload->getErrors(), 'file');
					}
				}
				//var_dump( $this->data[$datakey] );
			}		

		}
		
	}
	
	public function checkLoggedIn(){
		if( !isset($_SESSION['isLoggedIn'] ) || !$_SESSION['isLoggedIn'] || !is_numeric($_SESSION['user_id']) ){
			
			die('you need to be <a href="/login">logged in</a>');
		
		}else{
			$this->user_id = $_SESSION['user_id'];
		}
	}
	
	public function setTheme(){
		if( defined('ADMIN_THEME') ){ // defined('THEME') && file_exists( 'theme/' . THEME . '/header.tpl.php' ) ){
			$this->theme = ADMIN_THEME;
		}
		$this->headerTemplate = 'theme/' . $this->theme . '/header.tpl.php';
		$this->footerTemplate = 'theme/' . $this->theme . '/footer.tpl.php';
	}
	
	public function get($name){
		if( isset( $this->{$name}) ){
			return $this->{$name};
		}else{
			return false;
		}
	}
	
	public function bodyClass(){
		$cl = false;
		if( !$this->templateShow('sidebars') ){
			$cl = 'onecolumn';
		}elseif( !$this->templateShow('firstSidebar') || !$this->templateShow('secondSidebar') ){
			$cl = 'twocolumn';
		}else{
			$cl = 'threecolumn';
		}
		
		if($cl){
			return ' class="' . $cl . '"';
		}else{
			return false;
		}
		
	}
	
	// this is called on templates to see if the element should be should or not
	// its on an opt out basis - set it in the controller to false, otherwise it will be shown
	public function templateShow($check){
		
		if( isset($this->templateSetting[$check]) && $this->templateSetting[$check] === false){
			return false;
		}else{
			return true;
		}
	
	}
	
	public function setOwnerId(){
		$this->data['owner_id'] = $_SESSION['user_id'];
	}
	
	public function buildViews(){
	//loads in the HTML template for this page
		
		if( is_array($this->views) ){
			ob_start();
			foreach($this->views as $view){
				include($view);
				$this->main .= ob_get_contents();
			}
			ob_end_clean();
		}
		
		$this->data['title'] = $this->getTitle();
	}
	
	public function setDefaults( $defaults ){

		foreach($defaults as $k => $v){
			if( !isset($this->data[$k]) ){
				$this->data[$k] = $v;
			}
		}
	}
	
	public function initWidgets(){
		$this->widgets = new Widgets( $this->pageInfo ) ; //['page_id'] , $this->pageInfo['template_id'] );
	}
	
	public function getWidgets($position){
		return $this->widgets->get($position);
	}
	
	public function getBasePageContent(){
	
		$q = "SELECT c.content, c.keywords, c.description, c.section_id 
				FROM content c, page_to_content pc 
				WHERE 
					pc.page_id = '" . $this->pageInfo['page_id'] . "'
					AND
					pc.content_id = c.content_id
				ORDER BY c.section_id ASC";
				
		$this->baseContent = Common::getRows($q);
		//var_dump($q,$this->baseContent);		
		
	}
	
	public function setRoute($array){
		//var_dump($array);
		$this->pageInfo = $array;
		if( isset( $this->pageInfo['settings'] ) ){
			$this->settings = json_decode( $this->pageInfo['settings'] );
		}
		$this->title = $array['title'];
		$this->cacheName = 'admin' . $this->pageInfo['template_file'] . $this->pageInfo['page_id'];
		
		
	}
	
	public function addJS($js,$dependancies = array()){
		$this->js = array_unique($this->js);
		foreach( $dependancies as $val){
			if( !in_array($val, $this->js)) {
				$this->js[] = $val;
			}
		}
		if( !in_array($js, $this->js)) {
			$this->js[] = $js;
		}
	}
	
}

?>