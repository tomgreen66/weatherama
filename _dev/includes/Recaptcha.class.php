<?php
/*
INSERT INTO `settings` ( `type`, `key`, `value`, `editable`) VALUES
('const', 'USE_CAPTCHA', '1', 0),
('captcha', 'CAPTCHA_PUBLIC', '6LcKndASAAAAAMAvTuWeysvVw-YZKuQLFgF14ddT', 0),
('captcha', 'CAPTCHA_PRIVATE', '6LcKndASAAAAAIBI9xivHyGPw-KjA6FoYq79Bu5a', 0);
*/
class Recaptcha{
	

	private $api_server = "http://www.google.com/recaptcha/api";
	private $api_ssl_server = "https://www.google.com/recaptcha/api";
	private $verify_server = "www.google.com";
	private $settings;
	public $recaptcha_response = array();
	
	// url - full URL of page - including domain & GET variables
	function __construct(){
		//get settings
		$q = "SELECT `key`,`value` FROM settings WHERE `type` = 'captcha'";
		$rows = Common::getRows($q);
		foreach($rows as $row){
			$this->settings[ $row['key'] ] = $row['value'];			
		}
	}

	// when script finishes update cache if needed
	function __destruct(){
		
	}
	
	public function setPubKey($str){
		$this->public_key = $str;
	}
	
	public function setPrivKey($str){
		$this->private_key = $str;
	}
	
	

/**
 * Gets the challenge HTML (javascript and non-javascript version).
 * This is called from the browser, and the resulting reCAPTCHA HTML widget
 * is embedded within the HTML form it was called from.
 * @param string $pubkey A public key for reCAPTCHA
 * @param string $error The error given by reCAPTCHA (optional, default is null)
 * @param boolean $use_ssl Should the request be made over ssl? (optional, default is false)

 * @return string - The HTML to be embedded in the user's form.
 */
	public function recaptcha_get_html($error = null, $use_ssl = true){
		if($this->settings['CAPTCHA_PUBLIC'] == null || $this->settings['CAPTCHA_PUBLIC'] == '') {
			return ("To use reCAPTCHA you must get an API key from <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a>");
		}else{
		
			if($use_ssl){
				$server = $this->api_ssl_server;
			}else{
				$server = $this->api_server;
			}
		
			$errorpart = "";
			if ($error) {
				$errorpart = "&amp;error=" . $error;
			}
			return '<script type="text/javascript" src="'. $server . '/challenge?k=' . $this->settings['CAPTCHA_PUBLIC'] . $errorpart . '"></script>
			<noscript>
				<iframe src="'. $server . '/noscript?k=' . $this->settings['CAPTCHA_PUBLIC'] . $errorpart . '" height="300" width="500" frameborder="0"></iframe><br/>
				<textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
				<input type="hidden" name="recaptcha_response_field" value="manual_challenge"/>
			</noscript>';
		}
	}
	
	
/**
  * Calls an HTTP POST function to verify if the user's guess was correct
  * @param string $privkey
  * @param string $remoteip
  * @param string $challenge
  * @param string $response
  * @param array $extra_params an array of extra variables to post to the server
  * @return ReCaptchaResponse
  */
	public function recaptcha_check_answer ($remoteip, $challenge, $response, $extra_params = array()){
		if ($this->settings['CAPTCHA_PRIVATE'] == null || $this->settings['CAPTCHA_PRIVATE'] == '') {
			return ("To use reCAPTCHA you must get an API key from <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a>");
		}
	
		if ($remoteip == null || $remoteip == '') {
			return ("For security reasons, you must pass the remote ip to reCAPTCHA");
		}

		//discard spam submissions
		if ($challenge == null || strlen($challenge) == 0 || $response == null || strlen($response) == 0) {
				//$this->recaptcha_response = new ReCaptchaResponse();
				$this->recaptcha_response['is_valid'] = false;
				$this->recaptcha_response['error'] = 'incorrect-captcha-sol';
				return $this->recaptcha_response;
		}

		$response = $this->_recaptcha_http_post ($this->verify_server, "/recaptcha/api/verify",
										  array (
												 'privatekey' => $this->settings['CAPTCHA_PRIVATE'],
												 'remoteip' => $remoteip,
												 'challenge' => $challenge,
												 'response' => $response
												 ) + $extra_params
										  );

		$answers = explode ("\n", $response [1]);
		//$recaptcha_response = new ReCaptchaResponse();

		if (trim ($answers [0]) == 'true') {
				$this->recaptcha_response['is_valid'] = true;
		} else {
				$this->recaptcha_response['is_valid'] = false;
				$this->recaptcha_response['error'] = $answers[1];
		}
		return $this->recaptcha_response;

	}
	

	/**
	 * Encodes the given data into a query string format
	 * @param $data - array of string elements to be encoded
	 * @return string - encoded request
	 */
	private function _recaptcha_qsencode ($data) {
		$req = "";
		foreach ( $data as $key => $value )
				$req .= $key . '=' . urlencode( stripslashes($value) ) . '&';
		
		// Cut the last '&'
		$req=substr($req,0,strlen($req)-1);
		return $req;
	}
	
	
	
	/**
	 * Submits an HTTP POST to a reCAPTCHA server
	 * @param string $host
	 * @param string $path
	 * @param array $data
	 * @param int port
	 * @return array response
	 */
	private function _recaptcha_http_post($host, $path, $data, $port = 80) {
	
		$req = $this->_recaptcha_qsencode ($data);
		
		$http_request  = "POST $path HTTP/1.0\r\n";
		$http_request .= "Host: $host\r\n";
		$http_request .= "Content-Type: application/x-www-form-urlencoded;\r\n";
		$http_request .= "Content-Length: " . strlen($req) . "\r\n";
		$http_request .= "User-Agent: reCAPTCHA/PHP\r\n";
		$http_request .= "\r\n";
		$http_request .= $req;
		
		$response = '';
		if( false == ( $fs = @fsockopen($host, $port, $errno, $errstr, 10) ) ) {
			return ('Could not open recapture socket');
		}
		
		fwrite($fs, $http_request);
		
		while ( !feof($fs) )
			$response .= fgets($fs, 1160); // One TCP-IP packet
		fclose($fs);
		$response = explode("\r\n\r\n", $response, 2);
		
		return $response;
	}

}


?>