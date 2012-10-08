<?php
$_debug['file'][] = 'modules/shop/classes/worldpay.inc.php';


class Worldpay{

	private $contents;
	private $currency = 'GBP';
	private $isLive = true;
	public $form = array(
					'name'=>'checkout',
					'action'=>'https://live.worldpay.com'
					);


	private $setup = array(
	
					'testing' => array(
									'action' => 'https://select-test.wp3.rbsworldpay.com/wcc/purchase',
									'GBP' => array(
													'instId' => '266817' ,
				    								'hash_code' => 'therapies'
				    								),
				    				'EUR' => array(
													'instId' => '266817' ,
				    								'hash_code' => 'therapies'
				    								),
				    				'USD' => array(
													'instId' => '266817' ,
				    								'hash_code' => 'therapies'
				    								)
					
									),
			
					'live' => array(
									'action' => 'https://secure.worldpay.com/wcc/purchase',
									'GBP' => array(
													'instId' => '266817' ,
				    								'hash_code' => 'therapies'
				    								),
				    				'EUR' => array(
													'instId' => '266817' ,
				    								'hash_code' => 'therapies'
				    								),
				    				'USD' => array(
													'instId' => '266817' ,
				    								'hash_code' => 'therapies'
				    								)
					
									)
	);
	
	//add any default inputs in here
	public $inputs = array(
						'default_input' => '25001400' 	 
						);
					
	public $products;
	public $hashCode = false;
	private $instId = false;
	public function __construct(){
		
	}
	
	
	public function setCurrency($cur){
		if( in_array($cur, array('GBP','EUR','USD'))){
			$this->currency = $cur;
		
			if($this->isLive){
				$details = $this->setup['live'];
			}else{
				$details = $this->setup['testing'];
				$this->setFormVars('testMode', 100 );
				
			}
			$this->instId = $details[$this->currency]['instId'];
			$this->hashCode = $details[$this->currency]['hash_code'];
			
			$this->setFormAttributes('action',$details['action']);
			$this->setFormVars('instId', $this->instId );
			
		}
		
	}
	
	public function setTesting(){
		$this->isLive = false;
	}
	
	public function setLive(){
		$this->isLive = true;
	}
	
	// instId:amount:currency:cartId
	public function makeHash($amount, $cartId){
		if($this->hashCode != ''){
			$md5 = md5( $this->hashCode . ':' . $this->instId . ':' . $amount . ':' . $cartId . ':' . $this->currency);
			$this->setFormVars('signature', $md5 );
		}else{
			return false;
		}
	}
	
	// set the method & name of the form - defaults should be fine
	public function setFormAttributes($key,$value){
		$this->form[$key] = $value;
	}
	
	// add variables to the hidden inputs that get forwarded to Paypal
	public function setFormVars($key,$value){
		$this->inputs[$key] = $value;
	}
	
	public function makePostVars(){
	// this returns the form variables & setup that need to be sent to Paypal	

		// set up the form
		return array(
			'form' => $this->form ,
		
		//the hidden fields that will be submitted
			'inputs' => $this->inputs
		    );
	}
}	
?>