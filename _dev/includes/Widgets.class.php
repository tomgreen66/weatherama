<?php
/**
 * Widgets - gets all the various widgets for the page
 * @version 0.1
 * @package condiment
 * @subpackage Widgets
 * @author David Harris <theinternetlab@gmail.com>
 */

class Widgets{
	
//	private $widgets;
	private $page_id;
	private $template_id;
	private $store;
	private $cache;
	private $cacheData;
	private $update = false;
	private $unique_id;
	private $editable;
	private $pageInfo;
	
	function __construct($pageInfo){ //_id,$template_id){
	/*
	 * Global elements & widgets that header / footer or theme may need
	 * global, theme and page widgets are defined in the database see `item_to_widget`
	 */
	 	$this->pageInfo = $pageInfo;
		$this->page_id = $pageInfo['page_id'];
		$this->template_id = $pageInfo['template_id'];
		$this->editable = $pageInfo['editable'];
		$this->unique_id = hash('md4', 'widgetClass' . $this->page_id . $this->template_id);
		//get Widgets cache
		$this->cache = new Cache($this->unique_id);
		$this->cache->setExpirey(2); //for testing - 1sec
		if($this->cacheData = $this->cache->getCache()){
			//echo ' <! -- using cached widgets -->';
		}else{
			$this->getWidgets();
		}
		
	}
	
	public function getPageInfo($item){
		if( isset($this->{$item})){
			return $this->{$item};
		}else{
			return false;
		}
		 
	}
	
	// when script finishes update cache if needed
	function __destruct(){
	//var_dump($this->cacheData['widgets']);
		if($this->update){
			// cache the widget
			//echo ' <!-- updated cached widgets -->';
			//var_dump($this->cacheData);
			$this->cache->updateCache( $this->cacheData );
		}
	}
	
	private function update(){
		$this->update = true;
	}
	
	// get the items from the database
	private function getWidgets(){
		$q = "SELECT w.content, w.title, w.settings, t.template_file, p.position, i.order, i.index_id
				FROM item_to_widget i, widgets w, widget_types t, positions p, types y
				WHERE 
				w.type_id = t.type_id
				AND
				i.widget_id = w.widget_id
				AND
				p.position_id = i.position_id
				AND
				i.type_id = y.type_id
				AND
				i.publish = 1
				AND
				(
					(y.type = 'global')
					OR 
					(y.type = 'page' AND i.item_id = '" . $this->page_id . "')
					OR 
					(y.type = 'template' AND i.item_id = '" . $this->template_id . "')
				
				";
		//if user can edit then show user widgets
		if( $this->editable ){
			$q .=	" OR (y.type = 'editable') ";
		}		
		$q .=	"
				)
				ORDER BY i.position_id ASC, i.order ASC 
				";
		//echo $q;
		$rows = Common::getRows($q);
		// re-key for fetching - so can request widgets per position
		foreach($rows as $key => $row){
			//$this->widgets[ $row['position'] ][] = $row;
			$this->cacheData['widgets'][ $row['position'] ][] = $row;
		}
		unset($rows);
	}
	
	
	public function insertWidget($widgetData,$position = 'firstsidebar',$start = true){
		if($start){
			array_unshift( $this->cacheData['widgets'][$position] , $widgetData); 
		}else{
			array_push($this->cacheData['widgets'][$position] , $widgetData );
		}
	}
	
	public function get($position){
		if( isset( $this->cacheData['widgets'][$position] ) && count($this->cacheData['widgets'][$position]) ){
			$content = '';
			foreach($this->cacheData['widgets'][$position] as $key => $item){
				//var_dump( $item );
				$className = $item['template_file']; // for better readability
				include_once 'controllers/widgets/' . $className . '.class.php';
				$widget = new $className();
				
				// set the widget infor from the database
				$widget->setWidgetInfo($item);
				
				//set information about this page (id etc..)
				$widget->setPageInfo($this->pageInfo);
				
				$unique_id = 'widg' . $className . $position . $item['index_id'];
				//$cache_id = md5($unique_id);
				$cache_id = hash('md4', $unique_id ); //bit faster than md5

				if( isset( $this->cacheData[$cache_id] ) ){
					$widget->setCachedData( $this->cacheData[$cache_id] );
				}else{
					$widget->build();
					$this->update();
					$this->cacheData[$cache_id] = $widget->getData();
				}
				
				ob_start();
				include 'views/widgets/' . $className . '.tpl.php';
				$content .= ob_get_contents();
				ob_end_clean();
				unset($widget);
				//! need to add filter here
				// $content = apply_filter('widget',$content);
			}
			
			return $content; 
		}else{
			return false;
		}
		
	}
	
}

?>