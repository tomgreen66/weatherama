<?php 
	
###############################################################################################################################################
# -------------------------------------------------------- VAILDATE FORM DATA -----------------------------------------------------------------
#
# Send an array of whats required and an array of data then o the check
#
#
# EXAMPLE
# $formRequired = array('email' => 'email',
#						'phone' => 'number',
#						'address_line_1' => 'noempty',
#						'address_line_2' => 'noempty',
#						'town' => 'noempty',
#						'postcode' => 'noempty');
# 
# $validate = new validator();
# $validate->start($formRequired, $_POST);
# if($validate->check()) {
# 		do some stuff
# }
# 
# ----- check a field for an error - ie on the form itself
# if($validate->error('email')) {
# 		echo $validate->error('email');
# 		// or custom error etc
# }
#
# // get the data back to the form in an array
# $formData = $validate->form_data();
#
# // be sure to destroy the data for this form when youve finished with it
# $validate->destroy();
# 
# Super easy to add more validation methods
###############################################################################################################################################


class Validator {
	
	/**
	  * holds the number of errors
	**/
	var $errors = 0;
	
	/**
	  * an array of required fields and the type of vaildation to be done on them
	  * ie $required = array('email_field' => 'email', 'form_item' => 'no empty')
	  * values for validation type can be found in the method check()
	**/
	var $required = array();
	var $error_fields = array();
	/**
	  * the data sent to the form ie $_POST or $_GET or some other array
	**/
	var $data = array();
	
	/**
	  * holds all the empty fields
	**/
	var $empty = array();
	
	/**
	  * if not using sessions then use this array to store form data
	**/
	var $form_data = array();
	
	
	
	/**
	  * constructor doesnt do anything
	**/
	function validator() {
	}
	
	
	/**
	  * setup the validation
	  * @required: array; required fields
	  * @data: array; the data to be validated 
	**/
	function start($required, $data) {
		
		if(is_array($required) ) {
			$this->required = $required;
		}else{
			//$this->log_error('No fields specified', false);
			die('No fields specified');
		}
		
		if(is_array($data) && count($data) > 0) {
			$this->data = $data;
			$this->gather_data();
			
		}else{
			//$this->log_error('No data sent', false);
			die('No Data Sent');
		}
	
	}

	
	/**
	  * gathers the data - creates the empty[] array and saves the sent information
	**/
	function gather_data() {
		foreach($this->data as $field => $value) {
		
			// date entries are sent as an array - dates will be arrays with [day] [month] and [year] keys	- need glueing together before being processed	
			if(is_array($value) && isset($value['year'])){
				$this->data[$field] = $value['year'].'-'.$value['month'].'-'.$value['day'];
			}
			
			if (empty($this->data[$field])) {
				$this->empty[] = $field;
			}
			
			//$this->save_field($field);
		}
	}
	
	
	/**
	  * does the check
	  * more checks can easily be added here - just add to the switch statment
	  * @returns: boolean; if the data is valid or not
	**/ 
	function check() {
		
		foreach($this->required as $field => $value) {
			
			// if the second part of the array is an array then the first part of that array will always be the type
			// otherwise the second part of the array IS the type
			if(is_array($value)) {
				$type = $value[0];
			}else{
				if( strpos($value, 'minchars_') === 0){
					$value = explode('_', $value);
				}else{
					$type = $value;
					
				}
			}
			
			
			
			switch($type) {
				case 'email' :
					$er = $this->_email($field);
					if($er != 'ok') {
						$this->log_error($field);
					}else{
						$this->ok($field);
					}
					break;
				case 'emailUnique' :
					$val = 
					$er = $this->emailUnique($this->data[$field]);
					if($er != 'ok') {
						$this->log_error($field);
					}else{
						$this->ok($field);
					}
					break;
				case 'noempty' :
					if(!$this->_empty($field)) {
						$this->log_error($field);
					}else{
						$this->ok($field);
					}
					break;
				case 'urlname' :
					if(!$this->_urlname($field)) {
						$this->log_error($field);
					}else{
						$this->ok($field);
					}
					break;
				case 'ticked' :
					if(!$this->_ticked($field)) {
						$this->log_error($field);
					}else{
						$this->ok($field);
					}
					break;
					
				case 'number' :
					if(!$this->_number($field)) {
						$this->log_error($field);
					}else{
						$this->ok($field);
					}
					break;
					
				case 'float' :
					if(!$this->_float($field)) {
						$this->log_error($field);
					}else{
						$this->ok($field);
					}
					break;

				case 'mysqldate' :
					if(!$this->_mysqldate($field)) {
						$this->log_error($field);
					}else{
						$this->ok($field);
					}
					break;
				case 'phonenumber' :
					if(!$this->_phoneNumber($field)) {
						$this->log_error($field);
					}else{
						$this->ok($field);
					}
					break;
					
				case 'minchars' :
					if(!$this->_more_than_chars($field, $value[1])) {
						$this->log_error($field);
					}else{
						$this->ok($field);
					}
					break;
				case 'wwwaddress' :
					if(!$this->_wwwaddress($field)) {
						$this->log_error($field);
					}else{
						$this->ok($field);
					}
					break;
				case 'postcode':
					
					if(!$this->checkPostcode($this->data[$field])){
						$this->log_error($field);
					}else{
						$this->ok($field);
					}
					break;	
				
				case 'array' :
					if($this->_array_check($field, $value[1], $value[2])) {
						$this->ok($field); 
					}
					break;
			}
			
		}
		
		// return if the data is valid
		if($this->errors > 0) {
			return false;
		}else{
			return true;
		}
		
	}
	
	
	function numErrors() {
		return $this->errors;
	}
	
	
	
	function isValid($field){
		if( isset($this->form_data[$field]['error']) ) {
			return false;
		}else{
			return true;
		}
	}
	
	
	/**
	  * debug function
	**/
	function debug() {
		print_r($this->form_data);	
	}
	
	
	/**
	  * if this field had a previous error then remove it
	  * @field: string; the field to check
	**/
	function ok($field) {
		unset($this->form_data[$field]['error']);
	}
	
	
	/**
	  * save the error and log it
	  * @msg: string; the error message
	**/
	function log_error($field) {
		$this->error_fields[] = $field;
		$this->form_data[$field]['error'] = true;
		$this->errors ++;
	}
	
	
	/**
	  * be sure to destroy this forms data when finished with the form
	**/
	function destroy() {
		unset($this->form_data);
	}
	
	
	
	###############################################################################################################################################
	# ---------------------------------- CHECK DATA METHODS ---------------------------------------------------------------------------------------
	###############################################################################################################################################
	
	public function checkPostcode($toCheck) {
	
	/*
		// Permitted letters depend upon their position in the postcode.
		$alpha1 = "[abcdefghijklmnoprstuwyz]";                          // Character 1
		$alpha2 = "[abcdefghklmnopqrstuvwxy]";                          // Character 2
		$alpha3 = "[abcdefghjkstuw]";                                   // Character 3
		$alpha4 = "[abehmnprvwxy]";                                     // Character 4
		$alpha5 = "[abdefghjlnpqrstuwxyz]";                             // Character 5

		// Expression for postcodes: AN NAA, ANN NAA, AAN NAA, and AANN NAA with a space
		$pcexp[0] = '^('.$alpha1.'{1}'.$alpha2.'{0,1}[0-9]{1,2})([[:space:]]{0,})([0-9]{1}'.$alpha5.'{2})$';

		// Expression for postcodes: ANA NAA
		$pcexp[1] =  '^('.$alpha1.'{1}[0-9]{1}'.$alpha3.'{1})([[:space:]]{0,})([0-9]{1}'.$alpha5.'{2})$';
		
		// Expression for postcodes: AANA NAA
		$pcexp[2] =  '^('.$alpha1.'{1}'.$alpha2.'[0-9]{1}'.$alpha4.')([[:space:]]{0,})([0-9]{1}'.$alpha5.'{2})$';
		
		// Exception for the special postcode GIR 0AA
		$pcexp[3] =  '^(gir)(0aa)$';
		
		// Standard BFPO numbers
		$pcexp[4] = '^(bfpo)([0-9]{1,4})$';
		
		// c/o BFPO numbers
		$pcexp[5] = '^(bfpo)(c\/o[0-9]{1,3})$';
		
		// Overseas Territories
		$pcexp[6] = '^([a-z]{4})(1zz)$/i';
	*/
		//check postcode is valid
		$pcexp = array();
		$pcexp[0] = '/^[abcdefghijklmnoprstuwyz]{1}[abcdefghklmnopqrstuvwxy]{0,1}[0-9]{1,2}\s{0,}[0-9]{1}[abdefghjlnpqrstuwxyz]{2}\s{0,}$/';
		$pcexp[1] = '/^[abcdefghijklmnoprstuwyz]{1}[0-9]{1}[abcdefghjkstuw]{1}\s{0,}[0-9]{1}[abdefghjlnpqrstuwxyz]{2}$/';
		$pcexp[2] = '/^[abcdefghijklmnoprstuwyz]{1}[abcdefghklmnopqrstuvwxy][0-9]{1}[abehmnprvwxy]\s{0,}[0-9]{1}[abdefghjlnpqrstuwxyz]{2}$/';
		//if valid then ajax call for list of pubs

		// Load up the string to check, converting into lowercase
		$postcode = strtolower($toCheck);
		
		// Assume we are not going to find a valid postcode
		$valid = false;
		//$subject = "abcdef";
		//$pattern = '/^def/';
		//preg_match($pattern, substr($subject,3),
		// Check the string against the six types of postcodes
		foreach ($pcexp as $regexp) {

			//if (ereg($regexp,$postcode)) {
			if (preg_match($regexp,$postcode)) {
				// Load new postcode back into the form element  
				//$postcode = strtoupper ($matches[1] . ' ' . $matches [3]);
					
				// Take account of the special BFPO c/o format
				//$postcode = ereg_replace ('C\/O', 'c/o ', $postcode);
				
				// Remember that we have found that the code is valid and break from loop
				$valid = true;
				break;
			}
		}

		// Return with the reformatted valid postcode in uppercase if the postcode was 
		// valid
		if ($valid){
			return true; //$postcode;
		}else{
			return false;
		}
	}

	
	
	public function emailUnique($email,$allowed_user_ids = array()){
		$q = "SELECT email FROM users WHERE email = '" . mysql_real_escape_string( $email ) . "' ";
		if( count($allowed_user_ids) ){
			$q .= " AND user_id NOT IN(".implode(',', $allowed_user_ids) .") ";
		}
		$q .= " LIMIT 1 ";

		$rows = Common::getRows($q);
		if( isset($rows[0]['email'])) {
			return false;
		}else{
			return true;
		}
	}
	
	/**
	  * is the field empty?
	  * @field: string; the field to check
	**/
	function _empty($field) {
		
		if( empty($this->data[$field]) || trim($this->data[$field]) == ''){
			return false;
		}else{
			return true;
		}
		/*
		if(in_array($field, $this->empty)) {
			return false;
		}else{
			return true;
		}
		*/	
	}
	
	/**
	  * check urlname is unique
	  * @field: string; the field to check
	**/
	function _urlname($field) {
		// need parent id
		if( !isset($this->data['parent_id']) || !is_numeric($this->data['parent_id']) ){
			die('no parent id to check urlname against');
		}
		if( !strlen($this->data[$field]) ){
			return false;
		}
		
		return $this->checkUniqueUrlName( $this->data[$field],$this->data['parent_id'] );
	
	}
	
	public static function checkUniqueUrlName($urlname, $parent_id){
		$q = "SELECT urlname FROM pages WHERE urlname = '" . mysql_real_escape_string( $urlname ) . "' AND parent_id = '" . mysql_real_escape_string( $parent_id) . "' LIMIT 1";
		$rows = Common::getRows($q);
		if( isset($rows[0]['urlname'])) {
			return false;
		}else{
			return true;
		}
	} 
	
	/**
	  * is the field a number?
	  * @field: string; the field to check
	**/
	function _number($field) {
	
		$checkValue = $this->data[$field];
		
		if(ereg('^[0-9]+$', $checkValue)) {
			return true;
		}else{
			return false;
		}
	}
	
	
	/**
	  * is the field a number?
	  * @field: string; the field to check
	**/
	function _float($field) {
	
		$checkValue = $this->data[$field];
		//echo $checkValue;
		//if(ereg('^[0-9](\.\d+)?$', $checkValue)) {
		if(ereg('^[0-9]*\.[0-9]+|[0-9]+)$', $checkValue)) {
			return true;
		}else{
			return false;
		}
	}
	
	
	/**
	  * is the field has been ticked
	  * @field: string; the field to check
	**/
	function _ticked($field) {
		
		$checkValue = $this->data[$field];
		if($checkValue == '1') {
			return true;
		}else{
			return false;
		}	
		
	}  

	/**
	  * is the field a mysql date YYYY-MM-DD (2008-10-20)
	  * @field: string; the field to check
	**/
	function _mysqldate($field) {
		
		$checkValue = $this->data[$field];
		if(ereg('^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$', $checkValue)) {
			return true;
		}else{
			return false;
		}	
		
	}
	
	
	/**
	  * is the field a valid UK phone number
	  * @field: string; the field to check
	**/
	function _phoneNumber($field) {
		
		$checkValue = $this->data[$field];
		
		if(ereg('^0[ 0-9]+$',$checkValue)) {
			return true;
		}else{
			return false;
		}	
		
	}  
	
	
	/**
	  * is the field an email?
	  * @field: string; the field to check
	**/
	public function _email($field) {
	
		$checkValue = $this->data[$field];
		$return = $this->_emailtest($checkValue);
		return $return;
	}

	public static function _emailtest($email) {

		//if(eregi("^[_a-z0-9-]+(\.\+[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $checkValue)) {
		$emailpattern = '/^([*+!.&#$?\'\\%\/0-9a-z^_`{}=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i';
		if(preg_match($emailpattern, $email)) {
			return true;		
		}else{
			return false;
		}
	}	
	
	public function _wwwaddress($field){
	
		$checkValue = $this->data[$field];
		if($checkValue == 'www.your-website.co.uk'){
			return false;
		}else{
			if(eregi('^(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(:(\d+))?\/?$', $checkValue)){
			
				return true;
			}else{
				return false;
			}
		}
	}	
	
	/**
	  * does the field contain at least a certain number of characters?
	  * @field: string; the field to check
	  * @chars: integer; the minimum number of characters
	**/
	public function _more_than_chars($field, $chars) {
		$checkValue = $this->data[$field];
		
		if(strlen($checkValue) >= $chars) {
			return true;
		}else{
			return false;
		}
	}
	
	
	/**
	  * check checkboxes ie arrays - fieldname[]
	  * minimum check is to check if at least one item has been checked
	  * can check for a minimum and maximum number of items checked
	**/
	public function _array_check($field, $min = 1, $max = false) {
		$checkValue = $this->data[$field];
		
		if(is_array($checkValue)) {
			
			$numChecked = count($checkValue);
			
			if($max && $max != '') {
				if($numChecked >= $min && $numChecked <= $max) {
					return true;
				}else{
					//$this->log_error('Must select between ' . $min . ' and ' . $max . ' items', $field);
					return false;
				}
			}else{
				if($numChecked >= $min) {
					return true;
				}else{
					//$this->log_error('Must select as least ' . $nim . ' item(s)', $field);
					return false;
				}
			}
		
		}else{
			//$this->log_error('Must select at least one item', $field);
			return false;
		}
		
	}
	
	
	public function buildUri($data){
		// build them there own directory URL
		$q = "SELECT listing_type FROM listing_type WHERE listing_type_id = " . (int)$data['profession'] . " LIMIT 1";
		list($row) = Common::getRows($q); 
		$profession = $row['listing_type'];
		//try 1 
		$url1 = Common::toUrl($data['practice_name']);
		$url2 = Common::toUrl($data['practice_name'] . ' ' . $profession);
		$url3 = Common::toUrl($data['practice_name'] . ' ' . $profession . ' ' . $data['town']);
		
		if( self::isUniqueUrl($url1) ){
			$newUri = $url1;
		}elseif( self::isUniqueUrl($url2)) {
			$newUri = $url2;
		}elseif( self::isUniqueUrl($url3) ){
			$newUri = $url3;
		}else{
			// add number to end of $url1
			$i = 1;
			while( self::isUniqueUrl($url1 . '-' .$i) == false ){
				$i++;
			}
			$newUri = $url1 . '-' . $i;
			
		}	
		return $newUri;
		
	}
	
	public function isUniqueUrl($url){
		$q = "SELECT page_id FROM pages WHERE uri = '/" . mysql_real_escape_string($url) . "' LIMIT 1";
		if( count( Common::getRows($q) ) ){
			return false;
		}else{
			return true;
		}
	}
	
	
	
}
	
?>