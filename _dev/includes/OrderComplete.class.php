<?php
/**
 * Order Complete - process a completed order
 * @version 0.1
 * @package condiment
 * @subpackage Core
 * @author David Harris <theinternetlab@gmail.com>
 */

//! convert this to a singleton so info can be called from anywhere


//! add isOwner - so we can check they're owner
class OrderComplete{
	
	private $paymentProvider = 'worldpay';
	private $data;
	private $userId;
	private $page_id;
	private $listing_id;
	private $orderRef;
	private $orderId;
	private $orderItems;
	public $status;
	
	// url - full URL of page - including domain & GET variables
	function __construct($order_ref){
		$this->orderRef = $order_ref;
	}
	
	// when script finishes update cache if needed
	function __destruct(){
		
	}
	
	public function setData($data){
		$this->data = $data;
	}
	
		
	public function process(){
		//mail('abanham@psychologydirect.co.uk', 'world pay payment',  var_export($this->data, true) . var_export($_SERVER, true) );
		$this->orderCheck();
		// save the transaction response
		$serial = serialize($this->data);
		$this->updateOrder(" transaction_response = '" . mysql_real_escape_string($serial) . "'");

		
		// was it successful?
		// "Y" for a successful payment authorisation, "C" for a cancelled payment
		if($this->data['transStatus'] == 'Y'){
			//all okay can process the order
			// save specific info for back later administration
			// set the order item as paid
			$this->status = 'authorised';
			$this->updateOrder(" payment_received = 1 ,transaction_id = '" . mysql_real_escape_string( $this->data['transId'] ). "' ");
			//update relevent tables
			$this->processOrder();
			$this->updateOrder(" processed = 1 ");
			
		}elseif($this->data['transStatus'] == 'C'){
			$this->updateOrder(" processed = 1 ");
			$this->status = 'cancelled';
			//die('transaction was cancelled');
		}else{
			$this->status = 'problem';
			//die('unknown transaction response ' . $this->data['transStatus']);
		}
		
	}
		
	public function orderCheck(){
		$q = "SELECT order_id FROM orders WHERE associated_ref = '" . mysql_real_escape_string($this->orderRef). "'";
		list($check) = Common::getRows($q);
		if(!is_numeric($check['order_id']) ){
			$this->fatalError('no record of that ' . $this->orderRef . ' transaction');
		}else{
			$this->orderId = (int)$check['order_id'];
		}		
	}
	
	public function updateOrder($setSql){
		$q = "UPDATE orders SET " . $setSql . " WHERE order_id = '" . $this->orderId . "' LIMIT 1";
		//echo $q;
		Common::sendQuery($q);
	}
	
	private function processOrder(){
		//get order items and take appropriate messures, setting things as active / changing subscriptions
		$this->getOrderItems();
		if( count($this->orderItems) ){
			//  process order items
			foreach($this->orderItems as $row){
				switch($row['type']){
					case 'listing':
						// upgrade the listing
						$this->_processListing($row);
						// change user type to paid
						$this->_userLevel('subscriber');
						
					break;
					default:
						$this->fatalError('user paid for an unknown order type');
					break;
				}

			}
		}
		
		
	}
	
	public function setUserId($user_id){

		if($user_id == false){
		// site admin buying over the phone
			$q = "SELECT p.owner_id 
					FROM pages p, listing l , orders o, order_items i
					WHERE 
					o.associated_ref = '" . mysql_real_escape_string($this->orderRef). "'
					AND
					o.order_id = i.order_id
					AND
					i.purchase_type_id = 8
					AND
					i.purchase_item_id = l.listing_id
					AND
					l.page_id = p.page_id 
					LIMIT 1";
			list($row,) = Common::getRows($q);
			$this->userId = $row['owner_id'];
		}else{
			$this->userId = $user_id;
		}
	}
	
	
	private function _userLevel($type){

		if( in_array($type, array('free','subscriber') )){
			$q = "UPDATE users u, user_levels l 
					SET u.level_id = l.level_id
					WHERE 
						u.user_id = " . (int)$this->userId. "
						AND
						l.level = '" . $type . "'";
			
			Common::sendQuery($q);
		}
	}
	
	private function _processListing($row){
		if( isset($this->data['expires']) ){
			$expiry = "'" . $this->data['expires'] . "'";
		}else{
			$expiry = 'DATE_ADD(NOW(), INTERVAL 1 YEAR)';
		}
		//echo 'PROC LISTIGN';
		$q = "UPDATE listing 
				SET publish = 1, 
					expires = " . $expiry . ", 
					level_id = (SELECT level_id FROM listing_level WHERE level = '" . $row['stockcode'] . "' LIMIT 1)
				WHERE listing_id = " . (int)$row['purchase_item_id'] . "
				LIMIT 1";
		//echo '<br />' . $q . '<br />' ;
		Common::sendQuery($q);
		
	}
	
	private function getOrderItems(){
	// get order items from $this->orderId
		$q = "SELECT o.* , y.type
				FROM order_items o 
					LEFT JOIN types y ON (o.purchase_type_id = y.type_id)
				WHERE 
					o.order_id = " . (int)$this->orderId ;
		$this->orderItems = Common::getRows($q);
	}
	
	private function fatalError($message){
		mail('spakment@yahoo.co.uk', 'TW fatal payment error', $message . "\r\n" . var_export($_SERVER, true) );
		die($message);
	} 
	
}

?>