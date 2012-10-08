<?php
/**
 * Page Builder - this takes the routing information and gets all the neccessary content
 * @version 0.1
 * @package condiment
 * @subpackage Page
 * @author David Harris <theinternetlab@gmail.com>
 */

class BasePage{
	
	private $sidebarInfo;
	public $pageInfo;
	private $page = array();
	public $baseContent;
	public $body_class;
	public $user_id = false;
	private $theme = 'vanilla';
	public $main = '<!-- Base HTML -->';
	public $headerTemplate;
	public $footerTemplate;
	public $meta = array('charset' => 'UTF-8');
	public $css = array();
	public $js = array();
	private $title = 'Default';
	public $cacheName; //think this is best palce for the cache name	
	public $widgets;
	public $pageMeta;
	public $data = array();

	/* 
	 * array - all the info about the page 
	 * we need to get all the page and template 
	 * data from the databse and build the page
	 */ 
	function __construct(){
		
		//$this->setRoute($array);
		$this->setTheme();
		
		
	}
	
	// when script finishes update cache if needed
	function __destruct(){
		
	}
	
	public function setTheme(){
		if( defined('THEME') && file_exists( 'theme/' . THEME . '/header.tpl.php' ) ){
			$this->theme = THEME;
			$this->headerTemplate = 'theme/' . THEME . '/header.tpl.php';
			$this->footerTemplate = 'theme/' . THEME . '/footer.tpl.php';
			
			
		}
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
		
		if( $this->templateSetting[$check] === false){
			return false;
		}else{
			return true;
		}
	
	}
	
	public function setRoute($array){
		//var_dump($array);
		$this->pageInfo = $array;
		$this->title = $array['title'];
		$this->cacheName = $this->pageInfo['template_file'] . $this->pageInfo['page_id'];
		//var_dump( $this->pageInfo );
	}
	
	public function initWidgets(){
		$this->widgets = new Widgets( $this->pageInfo ) ; //['page_id'] , $this->pageInfo['template_id'] );
	}
	
	public function getBasePageContent(){
	
		$q = "SELECT pc.page_id, c.content, c.keywords, c.description, c.section_id 
				FROM content c, page_to_content pc 
				WHERE 
					pc.page_id = '" . $this->pageInfo['page_id'] . "'
					AND
					pc.content_id = c.content_id
				ORDER BY c.section_id ASC";
				
		$this->baseContent = Common::getRows($q);
		//var_dump($q,$this->baseContent);		
		
	}
	
	public function getAllPageMeta(){
		$q = "SELECT m.meta_key, m.meta_name, m.meta  , mt.type
				FROM meta m, item_to_meta i , types it, types mt
				WHERE 
				m.meta_id = i.meta_id
				AND
				i.item_id = '" . $this->pageInfo['page_id'] . "'
				AND
				i.type_id = it.type_id
				AND
				it.type = 'page'
				AND
				m.type_id = mt.type_id ";
		$rows = Common::getRows($q);
		foreach($rows as $row){
			$this->pageMeta[ $row['meta_key'] ] = $row;
		}

	}
	
	// return a specific meta (by meta key)
	public function getPageMeta($key){
		if( isset( $this->pageMeta[$key] ) ){
			return $this->pageMeta[$key];
		}else{
			return false;
		}
	}
	
	public function checkLoggedIn(){
		if( !isset($_SESSION['isLoggedIn'] ) || !$_SESSION['isLoggedIn'] || !is_numeric($_SESSION['user_id']) ){
			
			die('you need to be <a href="/login">logged in</a>');
		
		}else{
			$this->user_id = (int)$_SESSION['user_id'];
		}
	}
	
	// find out which side bars are needed for this template_id and this page_id
	public function getWidgets($position){
		return $this->widgets->get($position);
	}
	
	public function buildViews(){
	//loads in the HTML template for this page
		//$this->data['title'] = $this->getTitle();
		if( is_array($this->views) ){
			ob_start();
			foreach($this->views as $view){
				include($view);
				$this->main .= ob_get_contents();
			}
			ob_end_clean();
		}
			
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