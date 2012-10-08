<?php

/*
- paginate either mysql or array values (any data set)
- can customise css and various text and characters for displaying results
- choose whether to display as <ul> list or as just as links
- seperate display of links and details
- creates links to browse through page
- can set the number of links per page and set next and previous buttons etc


// EXAMPLE
connect to db etc
include this class

//select total results
$total_results_query = mysql_query("SELECT * FROM table");
$total_results = mysql_num_rows($total_results_query);

// can set the number of links per page as the last parameter its defaulted to 9 so not included here
$pagination = new pagination('page_num', $total_results, $_SERVER['PHP_SELF'], 3); // 3 is the number of results per page

$from = $pagination->from();
$limit = $pagination->max_results;
$page_results = mysql_query("SELECT * FROM table WHERE LIMIT " . $from . ", " . $limit);

echo $pagination->paginate();
echo "<br>" . $pagination->listing_details(); // can set an optional value for text ie : 1 - 5 of 378 results - default 

while($row = mysql_fetch_array($page_results)) {
	// do it etc
} 


// TEST //
$paginate = new pagination('page', 333, $_SERVER['PHP_SELF']);

echo $paginate->paginate();
echo "<br>" . $paginate->listing_details();
*/


class Pagination {
	
	var $page_num;              // int : number of current page
	var $page_num_name;         // string : the name of the HTTP_GET_VARS that holds the page number
	var $page;                  // the page of the paginated results (usualy $_SERVER['PHP_SELF'])
	var $page_vars;             // variables returned from query string
	var $total_pages;           // total number of pages that need to be created
	
	var $total_results;         // the raw number of results
	var $max_results;           // max results per page
	var $max_pages;             // this value will be minus the offset either side of the actual display pages
	var $offset;                // the offset either side of the paged results
	
	var $cur_group;             // current group of paged results
	
	var $first_text = "First";  // text for button to first set of results
	var $last_text = "Last";    // text for button to last set of results
	var $s_char1 = "";         // current page character
	var $s_char2 = "";         // current page character
	var $next_text = ">>";
	var $prev_text = "<<";
	
	var $spaced_text = "";      // text for area between page buttons and first / last buttons - optional, default : none
	
	// style stuff
	var $display_as_li = true; // display as html list (<ul><li>link</li></ul>) - css customisable see function : paginate
	
	
	/**
	function : constructor - sets all values performs calculations etc
	**/
	function __construct($page_num_name, $total_results, $page, $max_results = 15, $max_pages_num = 9, $offset = 1) {
		//echo $total_results;
		
		//$page = URL; // overwrite anything that gets sent to the class
		$this->set_offset($max_pages_num, $offset);
	
		$this->page = $page;
		$this->page_num_name = $page_num_name;
		
		$this->max_results = $max_results;
		
		$this->page_num = $this->get_cur_page_num($page_num_name);
		
		$this->total_results = $total_results;
		$this->number_of_pages($total_results);
		$this->cur_group = $this->get_group($this->page_num);
	}
	
	
	/**
	function : sets the offset of the displayed links (default = 1)
	**/
	function set_offset($max, $offset) {
		if($max - ($offset * 2) < 1) {
			die('the offset must be less than half the maximum');
		}else{
			$this->max_pages = $max - ($offset * 2);
			$this->offset = $offset;
		}
	}
	
	
	/**
	function : create the links
	**/
	function paginate($url_var_ignore = array(), $css = '') {
		
		// return any query string variables
		$this->page_vars = $this->return_query_string($url_var_ignore);
		
		if($this->display_as_li) {
			$links = "<ul class='" . $css . "'>";
		}
		
		if($this->cur_group == 1) {
		
			// create links for first group
			for($i = 1; $i <= $this->max_pages + ($this->offset * 2); $i++) {
				if($i <= $this->total_pages) {
					$links .= $this->build_link($i, $i);
				}
			}
			if($i <= $this->total_pages) {
				$links .= $this->build_link($i, $this->next_text);
				$links .= $this->build_link($this->total_pages, $this->last_text);
			}
			
		}elseif($this->cur_group == $this->get_total_groups()) {
		
			$start = $this->group_first_page($this->cur_group);
			
			// create links for last group
			$links .= $this->build_link('1', $this->first_text); 
			$links .= $this->build_link($start-1, $this->prev_text);
        	
			for($i = $start - ($this->offset * 2); $i <= ($this->total_pages); $i++) {
				$links .= $this->build_link($i, $i);
			}
			
		}else{
		
			// create all other links
			$start = $this->group_first_page($this->cur_group);
			
			$links .= $this->build_link('1', $this->first_text);
			$links .= $this->spaced_text;
			for($i = $start - $this->offset; $i < ($start + $this->max_pages) + $this->offset; $i++){
				$links .= $this->build_link($i, $i);
			}
			$links .= $this->spaced_text;
			$links .= $this->build_link($this->total_pages, $this->last_text);
			
		}
		
		if($this->display_as_li) {
			$links .= "</ul>";
		}
		
		return $links;
		
	}
	
	
	/**
	function : returns the link to the page
	**/
	function build_link($i, $disp) {
		
		if($this->display_as_li) {
			$link = "<li>";
		}
		
		if(($this->page_num) == $i){
	//	<a title="/" href="" class="selected">1</a>
		
			$link .=  '<a title="/" class="selected">' . $this->s_char1 . $i . $this->s_char2 . '</a>';
		} else {
        	$link .= "<a href='". $this->page ."?" . $this->page_num_name . "=" . $i . "&amp;" . $this->page_vars . "'>" . $disp . "</a> ";
    	}
		
		if($this->display_as_li) {
			$link .= "</li>";
		}
		
		return $link;
	}
	/* ------- modified for uyl system -------- */
	/*
	function build_link($i, $disp) {
		
		if($this->display_as_li) {
			$link = "<li>";
		}
		
		
		if(($this->page_num) == $i){
			$link .= $this->s_char1 . $i . $this->s_char2 . " ";
		} else {
        	$link .= "<a href='". $this->page ."q/" . $this->page_num_name . "-" . $i . "/" . $this->page_vars . "'>" . $disp . "</a> ";
    	}
		
		if($this->display_as_li) {
			$link .= "</li>";
		}
		
		return $link;
	}
	*/
	
	
	/**
	function : returns the current page number from $_GET
	**/
	function get_cur_page_num($page_num_name) {
		global $_GET;
		if(isset($_GET[$page_num_name]) && $_GET[$page_num_name] != "") {
			return $_GET[$page_num_name];
		}else{
			return 1;
		}
	}
	/* -------- modified for uyl system --------*/
	/*
	function get_cur_page_num($page_num_name) {
		global $urlArray;
		if(isset($urlArray['args'][$page_num_name]) && $urlArray['args'][$page_num_name] != '') {
			return $urlArray['args'][$page_num_name];
		}else{
			return 1;
		}
	}
	*/
	
	
	/**
	function : returns listing details | displaying 1-2 of 768938544 Results etc
	**/
	function listing_details($text = "Results") {
		$from = $this->from() + 1;
		
		if($this->page_num == $this->total_pages) {
			$to = $this->total_results;
		}else{
			$to = $from + $this->max_results - 1;
		}
		
		if(isset($text) && $text != "") {
			$text = " " . $text;
		}
		
		return $from . " - " . $to . " of " . $this->total_results . $text;
	}
	
	
	/**
	function : returns total number of pages
	**/
	function number_of_pages($total_entries) {
		$this->total_pages = ceil($total_entries / $this->max_results);
	}
	
	
	/**
	function : returns the 'from' position for sql queries etc
	ie : "SELECT * FROM table LIMIT " . $paginate->from() . ", (limit is $this->max_results)
	**/
	function from() {
		$from = ($this->page_num * $this->max_results) - $this->max_results;
		return $from;
	}
	
	
	/**
	function : returns page group number
	**/
	function get_group($page) {
		$g = 0;
		for($i = 1; $i <= $this->total_pages; $i += $this->max_pages) {
			$g ++;
			if($page >= $i && $page < ($i + $this->max_pages)) {
				return $g;
			}
		}
	}
	
	
	/**
	function : returns first page in group
	**/
	function group_first_page($group) {
		$g = 0;
		for($i = 1; $i <= $this->total_pages; $i += $this->max_pages) {
			$g ++;
			if($g == $group) {
				return $i;
			}
		}
	}
	
	
	/**
	function : returns number of groups
	**/
	function get_total_groups() {
		return ceil($this->total_pages / $this->max_pages);		
	}


	/**
	function : returns url query string
	**/
	
	function return_query_string($ignore) {
		global $_GET;
		foreach($_GET as $var=>$value) {
			if(!in_array($var, $ignore) && $var != $this->page_num_name) {
				//$q_str .= $var . '=' . $value . '&amp;';
				$q_str .= $var . '=' . urlencode($value) . '&amp;';
			}
		}
		return $q_str;
	}
	/* -------- modified for uyl system ------- */
	/*
	function return_query_string($ignore) {
		global $urlArray;
		if(count($urlArray['args'])) {
			foreach($urlArray['args'] as $var => $value) {
				if(!in_array($var, $ignore) && $var != $this->page_num_name) {
					$q_str .= $var . '-' . $value . '/';
				}
			}
		}
		return $q_str;
	}
	*/
	
}
?>