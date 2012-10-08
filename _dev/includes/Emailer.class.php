<?php
/**
 * Emailer - general email wrapper - to allow us to easily change all mail to different formats, plain text / HTML
 * @version 0.1
 * @package condiment
 * @subpackage emailer
 * @author David Harris <theinternetlab@gmail.com>
 */

//! TODO - This will likely wrap another HTML style email class

class Emailer{
	
	private $to; //32 character md5 type identifier
	private $cc;
	private $bcc;
	private $subject;
	private $message;
	private $from = 'info@therapyweb.co.uk';
	
	// url - full URL of page - including domain & GET variables
	function __construct(){
		if(defined('EMAIL_FROM')){
			$this->from = EMAIL_FROM;
		}
	}

	// when script finishes update cache if needed
	function __destruct(){
		
	}
	
	public function setFrom($from){
		$this->from = $from;
	}
	
	public function setTo($to){
		
		if( defined('DEV') && DEV === '1' && defined('ADMIN_EMAIL')){
			$this->to = ADMIN_EMAIL;//'abanham@psychologydirect.co.uk'; //'spakment@yahoo.co.uk';//$to;
		}else{
			$this->to = $to;
		}
	}
	
	public function setSubject($subject){
		$this->subject = $subject;
	}
	
	public function setMessage($message){
		$this->message = $message;
	}

	public function send(){
		$headers = 'From: ' . $this->from . "\r\n" .
    	'Reply-To: ' . $this->from . "\r\n" .
    	'Return-Path: <' . $this->from . '>' . "\r\n";
    //	'X-Mailer: PHP/' . phpversion();
    	$mailParams = '-f' . $this->from ; // user@domain.com'; // '-f'.$defaults['Return-Path']
	
//		if( $_SESSION['level'] == 'siteadmin' ){
//			var_dump($this->to, $this->subject, $this->message, $headers);
//			die('<br />done');
//		}else{
			mail($this->to, $this->subject, $this->message, $headers, $mailParams);
//		}
	}

	
}

?>