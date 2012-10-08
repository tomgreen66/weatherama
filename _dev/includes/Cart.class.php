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
class Cart{
	
	private $url;
	public $currency = array();
	private $paysVat = true;
	public $products;
	private $prodList;
	private $isSiteAdmin = false;
	private $validCoupons = array('50OFF','QUARTEROFF');
	public $messages;
	public $fullTotal;
	public $productTotal;
	public $discount = 0;
	public $discountDetails = array();
	public $coupon;
	public $orderRef;
	private $paymentProvider = 'worldpay';
	public $canCheckout = true;
	private $orderNote;
	public $userId;
	
	// url - full URL of page - including domain & GET variables
	function __construct(){
		$this->userId = $_SESSION['user_id'];
	}

	// when script finishes update cache if needed
	function __destruct(){
		
	}
	
	
	private function isAllowed($product){
		
		//check this level is greater than current level
		$q = "SELECT l.level_id FROM listing l, listing_level ll 
			WHERE 
			l.listing_id = " . (int)$product['item_id'] . "
			AND
			l.level_id < ll.level_id 
			AND
			ll.level = '" . $product['sku'] . "' LIMIT 1";
		list($row,) = Common::getRows($q);

		if( isset($row['level_id']) && is_numeric($row['level_id'])){
			return true;
		}else{
			echo ' lower level than current ';
			return false;
			
		}
	}
	
	private function getLevel($listing_id){
		$q = "SELECT level_id FROM listing WHERE listing_id = " . (int)$listing_id . "  LIMIT 1";
		list($row,) = Common::getRows($q);
		if( isset($row['level_id']) ){
			return (int)$row['level_id'];
		}else{
			return false;
		}
	}
	
	// check if we owe user credit for a current subscription
	private function checkCredit($row){
		if( $row['level'] > 1){
			// get credit back - amount paid - minus time / percentage used
			$q = "SELECT i.purchase_item_id, i.stock_desc, i.purchase_type_id, i.stockcode, i.price_grs, o.order_currency,  l.expires, o.order_date,
			DATEDIFF(l.expires,o.order_date) as days_paid, DATEDIFF(NOW(),o.order_date) as days_used
			FROM order_items i, orders o, listing l
			WHERE 
				i.order_id = o.order_id
				AND
				i.purchase_item_id = l.listing_id
				AND
				i.purchase_item_id = " . (int)$row['item_id'] . "
				AND
				i.purchase_type_id = 8
				AND
				o.payment_received = 1
			LIMIT 1";
			//echo $q;
			list($paid,) = Common::getRows($q);
			// calculate credit due
			
			$this->discount += ($paid['price_grs'] / $paid['days_paid']) * ($paid['days_paid'] - $paid['days_used']);
			$this->discountDetails[] = 'Credit for ' . $paid['stock_desc'] . ' ' . ($paid['days_paid'] - $paid['days_used']) . ' unused days';
			//var_dump($paid, $credit);
		}
	}
	
	public function addProducts($productsArray){
	//products array is from the session is "code" => "qty"
		//var_dump($productsArray);
		//die();
		$productsArray = array_unique($productsArray);
		$prodcodes = array();
		foreach( $productsArray as $key => $row){
			$prodcodes[] = $row['sku'];
		}
		
		$this->prodList = implode("','", $prodcodes);
		
		//get product details
		$q = "SELECT p.stockcode, p.title, p.net_price 
				FROM products p 
					WHERE 
					p.stockcode IN ('" . $this->prodList . "')
					AND
					p.active = 1
					AND
					p.discontinued = 0
					"; //index array by stockcode
		$rows = Common::getRows($q, 'stockcode');

		// create $this->products 
		foreach($productsArray as $key => $row){
			// check its valid an is eligable to be purchased - eg, if the level is greater than curent subscription
			if( isset( $rows[ $row['sku'] ] ) && $this->isAllowed( $row ) ){
				
				//get products current level - incase need to discount or is not allowed
				$row['level'] = $this->getLevel($row['item_id']);
				
				if($this->paysVat){
					$t_price = $this->addVat($rows[ $row['sku'] ]['net_price']);
				}else{
					$t_price = $rows[ $row['sku'] ]['net_price'];
				}
				//var_dump($row['level']);
				if( $row['level'] > 1){
					$this->checkCredit($row);
				}
				$this->products[$key] = array(
						'purchase_item_id' => $row['item_id'],
						'qty' => $row['qty'],
						'stockcode' => $row['sku'],
						'type' => $row['type'],
						'title' => $rows[ $row['sku'] ]['title'],
						'net_price' => $rows[ $row['sku'] ]['net_price'],
						'price' => $t_price					
				);
				
			}else{
				//not allowed - remove from session
				//unset( $_SESSION['products'] );
				
				foreach( $_SESSION['products'] as $key => $val){
					if($val['sku'] == $row['sku'] && $val['item_id'] == $row['item_id']){
						echo $row['sku'] . ' not allowed';
						unset( $_SESSION['products'][$key] );
					}
				}
				//var_dump($_SESSION['products']);
			
			}
		}		
		
	}
	
	public function calcTotals(){
	//calc producttotal, vouchers, delivery, total
		//var_dump($value);
		foreach($this->products as $value){
			$this->productTotal += $value['price'] * $value['qty'];
		}

		$this->calcDiscount(); 
		$this->calcFullTotal();
		
	}
	
	

	
	private function calcFullTotal(){
	// this calculates what they will actually pay - so take into account VAT
		$this->fullTotal = $this->productTotal - $this->discount;

	}
	
	
	
	public function addCoupon($coupon){
		if( in_array($coupon, $this->validCoupons) ){
			$this->coupon = $coupon;
		}
	}	
	
	private function calcDiscount(){
		//check vouchers array and calculate the discount
		$this->calcCoupon();
	}
	
	private function calcCoupon(){
		
		switch($this->coupon){
			case 'QUARTEROFF':
				$expires = new DateTime('2012-04-03 00:10:00');
				$now = new DateTime();
				if( $now < $expires ){
					$this->discount += $this->productTotal * 0.25;
					$this->discountDetails[] = 'Quarter Off Coupon';
				}
			break;	
			case '50OFF':
				$this->discount += $this->productTotal / 2;
				$this->discountDetails[] = 'Half Price Coupon';
			break;
		}

	}
	
	public function setSiteAdmin($bool){
		$this->isSiteAdmin = $bool;
	}
	
	public function setCurrency($cur){
	//  check that currency is one of those supported
		$supported = array(
						'GBP' => array(
									'code' => 'GBP',
									
									'html' => '&#163;',
									//'html' => '&pound;',
									'symbol' => '£'
									),
						'EUR' => array(
									'code' => 'EUR',
									'html' => '&#8364;', //&#8364;
									'symbol' => 'Û'
									),
						'USD' => array(
									'code' => 'USD',
									'html' => '&#36;',
									'symbol' => '$'
									)
					);
		$this->currency = $supported[$cur];
	}
	
	private function removeVAT($amount){
		//return round(($amount / 1.175),2); //(1 - 0.175);
		return round(($amount / (1 + (VAT/100) ) ),2); //(1 - 0.175);
	}
	
	//add vat to a net price and return gross
	private function addVat($amt){
		return $amt * (1 + (VAT/100));
	}
	
	public function calcVat($amount){
		return $amount - $this->removeVAT($amount);
	}
	
	public function displayAmount($amount,$stockcode = false, $showActual = false, $showExVat = true){
		//the amount to display and the currency code
		if($amount === 0){
			return 'FREE';
		}

		switch($this->currency['code']){
			case 'GBP':
				$locale = 'en_GB';
				break;
			case 'EUR':
				$locale = 'de_DE';
				break;
			case 'USD':
				$locale = 'en_US';
				break;
			default:
				$locale = 'en_GB';
				break;
		}
		
		setlocale(LC_MONETARY, $locale);
		
		$return = $this->currency['html'] . money_format('%!i', $amount); 
		if( !$this->paysVat ){
			$return .= ' <span class="exVat">(ex VAT)</span>';
		}

		return $return;
	}
	
	
	private function makeOrderRef(){
	// make a unique ref - dont use database incremental order id number incase we clean out the databse in the future and reset the Ids...
		 if( $this->isSiteAdmin ){
			$uuid = ADMIN_ORDER_PREFIX . uniqid();
		 }else{
		 	$uuid = ORDER_PREFIX . uniqid();
		 }
		 //check its unique
		 $check = Common::getRows("SELECT order_id FROM orders WHERE associated_ref = '" . $uuid . "'");
		 if( isset($check[0]['order_id'])){
		 	return makeOrderRef();
		 }else{
		 	return $uuid;
		 }
	}
	
	public function setTotal($newTotal){
		$this->setOrderNote('Manually changed order total from ' . $this->fullTotal . ' to ' . $newTotal);
		$this->fullTotal = $newTotal;
	}
	
	public function setOrderNote($str){
		$this->orderNote .=  $str . "/r/n";
	}
	
	public function setPaymentProvider($str){
		$this->paymentProvider = $str;
	}
	
	public function saveOrder(){
	// save address(es)
	
	//! where do we get this address from? From response, their free listing or save in user table?
	//! should pass in the listting ID as a variable from the basket page
	//! get their free listing address
	//! UPDATE FOR LIVE SITE
		
		if( $this->isSiteAdmin ){
			
			foreach( $this->products as $prod){
				if( is_numeric($prod['purchase_item_id']) && $prod['type'] == 'listing'){
					$listing_id = (int)$prod['purchase_item_id'];
					//get user id from product table
					$q = "SELECT p.owner_id 
							FROM pages p, listing l
							WHERE 
							p.page_id = l.page_id
							AND
							l.listing_id = " . $listing_id . "
							LIMIT 1";
				
					list($row) = Common::getRows($q);
					$this->userId = $row['owner_id'];
					break;
				}
			}
			
			$q = "SELECT l.* 
				FROM listing l
				WHERE 
					l.listing_id = " . $listing_id . "
				LIMIT 1";
				
		}else{
			$q = "SELECT l.* 
				FROM listing l, pages p
				WHERE 
					l.page_id = p.page_id
					AND
					p.owner_id = " . (int)$this->userId . "
				LIMIT 1";
		}
		
		
		
		list($listing) = Common::getRows($q);
		
		

		if( !isset($listing['listing_id']) || !is_numeric($listing['listing_id'])){
			die('No listing to purchase');
		}
		
		$invAddress = array(
				'title' => '',
				'forename' => $listing['name'],
				'surname' => $listing['name'],
				'email' => $_SESSION['email'],
				'address1' => $listing['address_line_1'],
				'address2' => $listing['address_line_2'],
				'address3' => '',
				'town' => $listing['town'],
				'county' => $listing['county'],
				'postcode' => $listing['postcode'],
				'country_code' => $listing['country'],
				'telephone' => $listing['phone'],
				'mobilephone' => 'NA',
				'organisation' => ''
		);
		
		
		
		//strip any non alpha numeric stuff
		foreach($invAddress as $key => $value){
			$invAddress[$key] = mysql_real_escape_string( strip_tags($value) );
		}

		//delivery & billing address are the same
		$q = Common::makeUpsertQuery($invAddress , 'order_addresses');

		$addressId = Common::sendQuery($q);
		
		
		//make order ID
		$this->orderRef = $this->makeOrderRef();
		
		// save order 
		if( count($this->discountDetails) > 0 ){
			
			foreach($this->discountDetails as $info){
				$this->setOrderNote($info) . "\r\n";
			}
		}
		
			
		
		if( strlen($_SESSION['extra']['notes']) > 0 ){
			$this->setOrderNote($_SESSION['extra']['notes']);
		}
		
		// $amount = $amount * $this->rateMult;		
		$order = array(
			'associated_ref' => $this->orderRef,
			'order_currency' => $this->currency['code'],
			'order_amount' => $this->fullTotal ,
			'coupon' => $this->coupon,
			'order_note' => $this->orderNote,
			//'tax_reference' => $_SESSION['tax_reference'],
			'website_name' => SITE_NAME,
			'payment_provider' => $this->paymentProvider,
			'inv_address' => $addressId,
			'user_id' => (int)$this->userId
			

		);
		
	
		if( isset($_SESSION['addpassword']) ){
			$order['newpassword'] = $_SESSION['addpassword'];
		}
		
		$orderId = Common::sendQuery(Common::makeUpsertQuery($order , 'orders'));

		$orderSql = array();
		// save order items
		foreach($this->products as $value){
		//stockcode,title,price,vat,weight,qty	
		// should really do this in one insert...
			
			$checkVat = true;
			$price_net = $value['net_price'];
			$price_grs = $this->addVat($value['net_price']);
			$type_id = '';
			if($value['type']){
				list($row) = Common::getRows("SELECT type_id FROM types WHERE type = '" . mysql_real_escape_string($value['type']). "' LIMIT 1");
				$type_id = $row['type_id'];
			}
			$orderSql[] = array(
						'order_id' =>$orderId,
						'purchase_item_id' => $value['purchase_item_id'],
						'purchase_type_id' => $type_id,
						'stockcode' =>$value['stockcode'],
						'stock_desc' => mysql_real_escape_string($value['title']) ,
						'order_qty' =>$value['qty'],
						'price_net' =>$price_net,
						'price_grs' =>$price_grs
			);
					
		
		}		
		
				
		
		// could do this in one string loop above rather than sorting into arrays 
		// but this makes it easier to managea as we add new fields in
		$values = '';
		foreach($orderSql as $key => $value){

			$values .= "('" . implode("','",$value) . "') ";
			if( $key + 1 < count($orderSql) ){
				$values .= ' , ';
			}
		}
		
		$fields = implode(',', array_keys($orderSql[0]) );
		
		$orderInsSQL = "INSERT INTO order_items (" . $fields . ") VALUES " . $values ;
		$orderId = Common::sendQuery($orderInsSQL);
		
	}
	
}

?>