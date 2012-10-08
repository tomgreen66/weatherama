<?php
/**
 * Form - handles caching data in and out of the database
 * @version 0.1
 * @package condiment
 * @subpackage Cache
 * @author David Harris <theinternetlab@gmail.com>
 */

class FormElement{

	private $type;
	private $validTypes = array('input','textarea','password','checkbox','hidden','select','radio','date','file','image');
	private $value;
	private $header;
	private $footer;
	private $label;
	private $name;
	private $required;
	private $validation;
	private $isValid;
	private $setSpanValidation;
	private $validationText;
	private $helpText;
	private $elementValue;
	private $alt;
	private $title;
	private $isImg = false;
	private $isPassword = false;
	private $filePath = 'pages';
	private $imPreviewFolder = '150x150_crop';
	private $labelFirst = true;
	private $selectOptions = array();
	private $classes = array();
	private $selectlist = array();
	
	public $html;
	
	function __construct($type = false){
		if($type){
			$this->setType($type);
		}
	}
	
	// when script finishes update cache if needed
	function __destruct(){
		
	}
	
	public function setType($type){
			
		if( in_array($type, $this->validTypes) ){
			$this->type  = $type;
		}else{
			//throw an exception
			die('cant make a form element of type: ' . $type);
		}
		
		switch($this->type){
			case 'checkbox':
				$this->labelFirst = false;
			break;
		}
	}
	
	public function setCurrentValue($val){
		$this->value = $val;
	}
	
	public function setClass($val){
		$this->classes[] = $val;
	}
	// used for check boxes, where the value is preset
	public function setElementValue($val){
		$this->elementValue = $val;
	}
	
	public function bulkSet($array){
		foreach($array as $key => $val){
			if( method_exists($this, $key) ){
				$this->{$key}($val);
			} 
		}	
	}
	
	public function setIsImage($bool){
		$this->isImg = $bool;
	}
	
	public function setImPreviewFolder($folder){
		$this->imPreviewFolder = $folder;
	}
	
	public function setIsPassword($bool){
		$this->isPassword = $bool;
	}
	
	public function setHeader($val){
		$this->header = $val;
	}
	
	public function setFilePath($val){
		$this->filePath = $val;
	}
	
	
	public function setRequired($bool){
		if($bool){
			$this->required = true;
			$this->classes[] = 'required';
		}else{
			$this->required = false;
		}
		
		if(!$this->validation){
			$this->setValidation();
		}
	}
	
	public function setValidation($validation = 'noempty'){
		if( strpos($validation, 'minchars_') === 0){
			$value = explode('_', $validation);
			$validation = 'minLength:' . $value[1];
		}
		$this->validation = $validation;
	}
	public function setFooter($val){
		$this->footer = $val;
	}
	
	public function setLabel($val){
		$this->label = $val;
	}
	
	public function setAlt($val){
		$this->alt = $val;
	}
	
	public function setTitle($val){
		$this->title = $val;
	}
	
	public function setName($val){
		$this->name = $val;
	}
	
	public function setSpanValidation($bool){
		if($bool){
			$this->setSpanValidation = true;
		}else{
			$this->setSpanValidation = false;
		}
	}
	
	public function setValidationText($val){
		$this->validationText = $val;
		$this->title = $val;
	}
	
	public function setValid($bool){
		if($bool){
			$this->isValid = true;
			$this->classes[] = 'valid';
		}else{
			$this->isValid = false;
			$this->classes[] = 'invalid';
		}
		
	}
	
	// this is for select lists : $label => $value
	public function setSelectOptions($array){
		$this->selectOptions = $array;
	}
	
	public function setHelpText($val){
		$this->helpText = $val;
	}
	
	public function build(){
		$this->html .= $this->header;
		
		if( strlen($this->label) && $this->labelFirst ){
			$this->html .= '<label for="' . $this->name . '">' . $this->label . '</label>';
		}
		
		switch( $this->type ){
			case 'input':
				$this->_input();
			break;
			case 'password':
				$this->setIsPassword(true);
				$this->_input();
			break;
			case 'textarea':
				$this->_textarea();
			break;
			case 'checkbox':
				$this->_checkbox();
			break;
			case 'hidden':
				$this->_hidden();
			break;
			case 'select':
				$this->_select();
			break;
			case 'radio':
				$this->_radio();
			break;
			case 'date':
				$this->_date();
			break;
			case 'file':
				$this->_file();
			break;
			case 'image':
				$this->_file();
			break;
		}
		
		if( strlen($this->label) && !$this->labelFirst ){
			$this->html .= '<label for="' . $this->name . '">' . $this->label . '</label>';
		}
		
		if($this->setSpanValidation){
			$this->html .= '<span class="form-validation';
			if( $this->isValid === true ){
				$this->html .= ' valid';
			}elseif( $this->isValid === false ){
				$this->html .= ' invalid';
			}

			$this->html .= '"></span>';
		}
		
		if( $this->isValid === false ){
			$this->html .= '<p class="validation-text">' . $this->validationText . '</p>';
		}

		$this->html .= $this->footer;

		return $this->html;
	}

	private function _textarea(){
		$this->classes[] = 'textarea';
		$this->html .= '<textarea class="' . implode(' ', $this->classes) . '" name="' . $this->name . '" id="' . $this->name . '" alt="' . $this->alt . '" title="' . $this->title . '">' .  $this->value . '</textarea>';
		
	}
	
	private function _input(){
		$this->classes[] = 'textinput';
		$type = 'text';
		if($this->isPassword){
			$type = 'password';
		}
		$this->html .= '<input type="' . $type . '" name="' . $this->name . '" value="' .  $this->value . '" id="' . $this->name . '" class="' . implode(' ', $this->classes) . '" alt="' . $this->alt . '" title="' . $this->title . '" ';

		if( $this->required){
			$this->html .= 'data-validators="' . $this->validation . '" ';
		}
		$this->html .= '/>';
		
	}
	
	private function _file(){
		$this->classes[] = 'file';
		$this->html .= '<input type="file" name="' . $this->name . '_upload" value="' .  $this->value . '" id="' . $this->name . '_upload" class="' . implode(' ', $this->classes) . '" alt="' . $this->alt . '" title="' . $this->title . '" />';

			if($this->value){
				$this->html .= '<input type="hidden" value="'.$this->value.'" name="' . $this->name . '" id="' . $this->name . '" />';
				$this->html .= '<div class="uploaded-image">';
				if($this->isImg) {
					$this->html .= '<img src="/files/'. $this->filePath . '/' . $this->imPreviewFolder. '/' . $this->value . '" /><br />';
				}
				$this->html .= '<span class="filename">' . $this->value . '</span><br />';
				$this->html .= '<input type="checkbox" class="input-checkbox" value="' . $this->value . '" id="image_remove[]" name="image_remove[]" /> 
				<label for="image_remove[]">Remove the current file?</label></div>';
			}
		
	}
	
	private function _hidden(){

		$this->html .= '<input type="hidden" name="' . $this->name . '" value="' .  $this->value . '" id="' . $this->name . '" />';
		
	}
	
	private function _checkbox(){
	
		$this->classes[] = 'checkbox';
		$this->html .= '<input type="checkbox" name="' . $this->name . '" value="' .  $this->elementValue . '" id="' . $this->name . '" class="' . implode(' ', $this->classes) . '" alt="' . $this->alt . '" title="' . $this->title . '"';
		if( $this->value == $this->elementValue ){
			$this->html .= ' checked="checked"';
		}
		$this->html .= ' />';
		
	}
	
	private function _select(){

		$this->classes[] = 'select';
		$this->html .= '<select name="' . $this->name . '" id="' . $this->name . '" class="' . implode(' ', $this->classes) . '" alt="' . $this->alt . '" title="' . $this->title . '">';
		if( !in_array('', $this->selectOptions ) ){
			$this->html .= '<option value="">---</option>';
		}
		if( count($this->selectOptions) ){
			foreach($this->selectOptions as $k => $v){
				$this->html .= '<option value="' . $v . '"';
				if($this->value == $v){
					$this->html .= ' selected="selected"';
				}
				$this->html .= '>' . $k . '</option>';
			}
		}
		$this->html .= '</select>';
		
	}
	
	
	private function _radio(){

		$this->classes[] = 'radio';
		foreach( $this->selectOptions  as $k => $v ){
			$this->html .= '<div class="' . implode(' ', $this->classes) . '"><input type="radio" name="' . $this->name . '" value="' . $v . '" id="radio_' . $v . '"'; 
			if($this->value == $v){
				$this->html .= ' CHECKED';
			}
			$this->html .= ' />'; 
			$this->html .= '<label for="radio_' . $v . '">' . $k . '</label> </div>';
		}

	}
	
	
	private function _date(){
			
			$this->classes[] = 'date';
			// divide up the date into yyyy[0] mm[1] dd[2]
			if( !$this->value ){
				$this->value = date('Y-m-d');
			}
			
			if( is_array($this->value)){
				$parts = array(
								0 => $this->value['year'],//Y
								1 => $this->value['month'],//Y
								2 => $this->value['day'],//Y
								);
			}else{
			
				$parts = explode('-', $this->value);

			}

			// days select
			$this->html .= '<select name="' . $this->name . '[day]" id="' . $this->name . '[day]">'."\r\n";

			for($i = 1; $i < 32; $i++){ 
				$this->html .= '<option value="'.str_pad($i, 2, "0", STR_PAD_LEFT).'"';
				$this->html .= ($parts[2]==$i ? ' selected="selected" ' : '');
				$this->html .= '>'.$i.'</option>'."\r\n";
			} 
			$this->html .= '</select>'."\r\n";
			$this->html .= '<select name="' . $this->name . '[month]" id="' . $this->name . '[month]">'."\r\n";

			// month select
			$months = array ('01'=>"Jan", '02'=>"Feb", '03'=>"Mar", '04'=>"Apr", '05'=>"May", '06'=>"Jun", '07'=>"Jul", '08'=>"Aug", '09'=>"Sep", '10'=>"Oct", '11'=>"Nov", '12'=>"Dec"); 
			//for($i = 1; $i < 13; $i++){
			foreach($months as $num => $month){
				$this->html .= '<option value="'. $num .'"';
				$this->html .= ($parts[1]==$num ? ' selected="selected" ' : ''); 
				$this->html .= '>'.$month .'</option>'."\r\n";
			}  
			$this->html .= '</select>'."\r\n";
			$this->html .= '<select name="' . $this->name . '[year]" id="' . $this->name . '[year]">'."\r\n";

			//year select
			$year = date('Y') - 4;
			// show last year and forward 3 years 
			for($i = 0; $i < 9; $i++){
				$y = $year+$i;
				$this->html .= '<option value="'.$y.'"';
				$this->html .= ($parts[0]==$y ? ' selected="selected" ' : ''); 
				$this->html .= '>'.$y.'</option>'."\r\n";
			 } 
			$this->html .= '</select>'."\r\n";

	}
	
}

?>