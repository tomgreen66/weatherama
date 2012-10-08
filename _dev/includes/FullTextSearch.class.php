<?php
######################## PERFORM A FULL TEXT SEARCH ON A TABLE #################################
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

class FullTextSearch{
	
	public $str;
	
	
	public function __construct() {
	
	}
	/**
	  * find an item from a table setup to have a full text search
	  *
	  * @searchTerms: string; the user requested search 
	  * @return: array; the database fields to return
	  * @table: string; table to search
	  * @bool: boolean; do we search for each word in the search individualy or treat it as one string
	  * @keyName: string; the name of the full text index key on the table
	  *
	  * @returns the result set see get()
	**/
	public function find($searchTerms, $return, $table, $bool = true, $keyName = 'ftindex') {
		$cols        = $this->getSearchColumns($table, $keyName);
		$searchTerms = ($bool === true) ? $this->booleanAndTerm($searchTerms) : $searchTerms;
		
		return $this->get($table, $cols, $return, $searchTerms);
	}
	
	
	/**
	  * returns the searchable columns set up for the index
	  *
	  * @table: integer; the table to search
	  * @keyName: string; the name of the full text index key on the table
	  * 
	  * @returns: string; the columns
	**/
	public function getSearchColumns($table, $keyName = 'ftindex') {
		$q = "SHOW INDEX 
					  FROM " . $table . " 
					  WHERE Index_type = 'FULLTEXT'
					    AND Key_name = '" . $keyName . "'";
		$rows = Common::getRows($q);
					    
		if(is_array($rows)) {
			$ftcArray = array();
			foreach($rows as $value) {
				$ftcArray[] = $value['Column_name'];
			}
			
			$fullTextColumns = implode(',', $ftcArray);
		}
		
		return $fullTextColumns;
	}
	
	
	/**
	  * 
	  * @table: string; table to search
	  * @cols: string; the searchable columns set up for the index see search::getSearchColumns()
	  * @findCols: array; the database fields to return
	  * @term: string; the user requested search 
	  *
	  * @returns: array; ['total'] : number of TOTAL results; ['query'] : the query to get the results
	**/
	public function get($table, $cols, $findCols, $term) {
		
		$find = implode(', ', $findCols);
		
		$ftListQuery = "SELECT " . $find . ", MATCH
						(" . $cols . ")
						AGAINST ('" . $term . "') AS score FROM " . $table . " WHERE MATCH
						(" . $cols . ")
						AGAINST ('" . $term . "' IN BOOLEAN MODE)  ORDER BY score DESC";
						
		$ftNumRowsQuery = "SELECT COUNT(*) as num_rows FROM " . $table . " WHERE MATCH
						(" . $cols . ")
						AGAINST ('" . $term . "' IN BOOLEAN MODE)";
		
//		echo '<br /> list: ' .$ftListQuery . '<br /> <br />' . $ftNumRowsQuery .'<br />';
		$rows = Common::getRows($ftNumRowsQuery);

		if( is_numeric($rows[0]['num_rows']) ){
			$return['total'] = $rows[0]['num_rows'];
		}else{
			$return['total'] = 0;
		}

		$return['query'] = $ftListQuery;
		
		return $return;
	}
	
	/**
	  * add + to words for full text boolean searching
	  *
	  * @term: string; the user requested search 
	  *
	  * @returns the new string
	**/
	public function booleanAndTerm($term){
		$term = '+'.trim($term);
		$term = str_replace(" ", " +", $term);
		return $term;
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
		$str = strip_tags($body);
		$chunk = '';
		foreach($searches as $search){
			if (preg_match("/$search/i",$str)){
				$pos = stripos($str,$search);
				if (($pos - ($maxChar/2)) < 0){
					$startPos = 0;
				}else{
					$startPos = ($pos - ($maxChar/2));
					//want to find the first space before $pos
					//make negative offset for strrpos
					$negpos =  (strlen($str) - $startPos) * -1;
					$startPos = strrpos($str, ' ', $negpos);
				}
				
				if($startPos !== 0){
				
					$chunk .= '...'.$chunk;
					$chunk .= trim(substr($str,$startPos,$maxChar));
					
					if (($pos + ($maxChar/2)) < strlen($str)){
						$chunk .= '...';
					}
					break;
				}
			}
		}
		
		if ($chunk == ''){
			$chunk .= substr($str,0,$maxChar).'...';
		}
		
		
		//$chunk = strip_tags($chunk);
		foreach ($searches as $term){
			$chunk = str_ireplace ($term,"<em>$term</em>",$chunk);
		}
		return $chunk;
	}
	
}

?>