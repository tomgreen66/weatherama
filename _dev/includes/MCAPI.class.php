<?php

class MCAPI {
    var $version = "1.3";
    var $errorMessage;
    var $errorCode;
    
    /**
     * Cache the information on the API location on the server
     */
    var $apiUrl;
    
    /**
     * Default to a 300 second timeout on server calls
     */
    var $timeout = 300; 
    
    /**
     * Default to a 8K chunk size
     */
    var $chunkSize = 8192;
    
    /**
     * Cache the user api_key so we only have to log in once per client instantiation
     */
    var $api_key;

    /**
     * Cache the user api_key so we only have to log in once per client instantiation
     */
    var $secure = false;
    
    /**
     * Connect to the MailChimp API for a given list.
     * 
     * @param string $apikey Your MailChimp apikey
     * @param string $secure Whether or not this should use a secure connection
     */
    function MCAPI($apikey, $secure=false) {
        $this->secure = $secure;
        $this->apiUrl = parse_url("http://api.mailchimp.com/" . $this->version . "/?output=php");
        $this->api_key = $apikey;
    }
    function setTimeout($seconds){
        if (is_int($seconds)){
            $this->timeout = $seconds;
            return true;
        }
    }
    function getTimeout(){
        return $this->timeout;
    }
    function useSecure($val){
        if ($val===true){
            $this->secure = true;
        } else {
            $this->secure = false;
        }
    }
    
    
     /**
     * Subscribe the provided email to a list. By default this sends a confirmation email - you will not see new members until the link contained in it is clicked!
     *
     * @section List Related
     *
     * @example mcapi_listSubscribe.php
     * @example json_listSubscribe.php        
     * @example xml-rpc_listSubscribe.php
     *
     * @param string $id the list id to connect to. Get by calling lists()
     * @param string $email_address the email address to subscribe
     * @param array $merge_vars optional merges for the email (FNAME, LNAME, etc.) (see examples below for handling "blank" arrays). Note that a merge field can only hold up to 255 bytes. Also, there are a few "special" keys:
                        string EMAIL set this to change the email address. This is only respected on calls using update_existing or when passed to listUpdateMember()
                        array GROUPINGS Set Interest Groups by Grouping. Each element in this array should be an array containing the "groups" parameter which contains a comma delimited list of Interest Groups to add. Commas in Interest Group names should be escaped with a backslash. ie, "," =&gt; "\," and either an "id" or "name" parameter to specify the Grouping - get from listInterestGroupings()
                        string OPTINIP Set the Opt-in IP fields. <em>Abusing this may cause your account to be suspended.</em> We do validate this and it must not be a private IP address.
                        array MC_LOCATION Set the members geographic location. By default if this merge field exists, we'll update using the optin_ip if it exists. If the array contains LATITUDE and LONGITUDE keys, they will be used. NOTE - this will slow down each subscribe call a bit, especially for lat/lng pairs in sparsely populated areas. Currently our automated background processes can and will overwrite this based on opens and clicks.
                        
                        <strong>Handling Field Data Types</strong> - most fields you can just pass a string and all is well. For some, though, that is not the case...
                        Field values should be formatted as follows:
                        string address For the string version of an Address, the fields should be delimited by <strong>2</strong> spaces. Address 2 can be skipped. The Country should be a 2 character ISO-3166-1 code and will default to your default country if not set
                        array address For the array version of an Address, the requirements for Address 2 and Country are the same as with the string version. Then simply pass us an array with the keys <strong>addr1</strong>, <strong>addr2</strong>, <strong>city</strong>, <strong>state</strong>, <strong>zip</strong>, <strong>country</strong> and appropriate values for each
    
                        string date use YYYY-MM-DD to be safe. Generally, though, anything strtotime() understands we'll understand - <a href="http://us2.php.net/strtotime" target="_blank">http://us2.php.net/strtotime</a>
                        string dropdown can be a normal string - we <em>will</em> validate that the value is a valid option
                        string image must be a valid, existing url. we <em>will</em> check its existence
                        string multi_choice can be a normal string - we <em>will</em> validate that the value is a valid option
                        double number pass in a valid number - anything else will turn in to zero (0). Note, this will be rounded to 2 decimal places
                        string phone If your account has the US Phone numbers option set, this <em>must</em> be in the form of NPA-NXX-LINE (404-555-1212). If not, we assume an International number and will simply set the field with what ever number is passed in.
                        string website This is a standard string, but we <em>will</em> verify that it looks like a valid URL
    
     * @param string $email_type optional email type preference for the email (html, text, or mobile defaults to html)
     * @param bool $double_optin optional flag to control whether a double opt-in confirmation message is sent, defaults to true. <em>Abusing this may cause your account to be suspended.</em>
     * @param bool $update_existing optional flag to control whether a existing subscribers should be updated instead of throwing and error, defaults to false
     * @param bool $replace_interests optional flag to determine whether we replace the interest groups with the groups provided, or we add the provided groups to the member's interest groups (optional, defaults to true)
     * @param bool $send_welcome optional if your double_optin is false and this is true, we will send your lists Welcome Email if this subscribe succeeds - this will *not* fire if we end up updating an existing subscriber. If double_optin is true, this has no effect. defaults to false.
     * @return boolean true on success, false on failure. When using MCAPI.class.php, the value can be tested and error messages pulled from the MCAPI object (see below)
     */
    function listSubscribe($id, $email_address, $merge_vars=NULL, $email_type='html', $double_optin=true, $update_existing=false, $replace_interests=true, $send_welcome=false) {
        $params = array();
        $params["id"] = $id;
        $params["email_address"] = $email_address;
        $params["merge_vars"] = $merge_vars;
        $params["email_type"] = $email_type;
        $params["double_optin"] = $double_optin;
        $params["update_existing"] = $update_existing;
        $params["replace_interests"] = $replace_interests;
        $params["send_welcome"] = $send_welcome;
        return $this->callServer("listSubscribe", $params);
    }
    
    
        /**
     * Actually connect to the server and call the requested methods, parsing the result
     * You should never have to call this function manually
     */
    function callServer($method, $params) {
	    $dc = "us1";
	    if (strstr($this->api_key,"-")){
        	list($key, $dc) = explode("-",$this->api_key,2);
            if (!$dc) $dc = "us1";
        }
        $host = $dc.".".$this->apiUrl["host"];
		$params["apikey"] = $this->api_key;

        $this->errorMessage = "";
        $this->errorCode = "";
        $sep_changed = false;
        //sigh, apparently some distribs change this to &amp; by default
        if (ini_get("arg_separator.output")!="&"){
            $sep_changed = true;
            $orig_sep = ini_get("arg_separator.output");
            ini_set("arg_separator.output", "&");
        }
        $post_vars = http_build_query($params);
        if ($sep_changed){
            ini_set("arg_separator.output", $orig_sep);
        }
        
        $payload = "POST " . $this->apiUrl["path"] . "?" . $this->apiUrl["query"] . "&method=" . $method . " HTTP/1.0\r\n";
        $payload .= "Host: " . $host . "\r\n";
        $payload .= "User-Agent: MCAPI/" . $this->version ."\r\n";
        $payload .= "Content-type: application/x-www-form-urlencoded\r\n";
        $payload .= "Content-length: " . strlen($post_vars) . "\r\n";
        $payload .= "Connection: close \r\n\r\n";
        $payload .= $post_vars;
        
        ob_start();
        if ($this->secure){
            $sock = fsockopen("ssl://".$host, 443, $errno, $errstr, 30);
        } else {
            $sock = fsockopen($host, 80, $errno, $errstr, 30);
        }
        if(!$sock) {
            $this->errorMessage = "Could not connect (ERR $errno: $errstr)";
            $this->errorCode = "-99";
            ob_end_clean();
            return false;
        }
        
        $response = "";
        fwrite($sock, $payload);
        stream_set_timeout($sock, $this->timeout);
        $info = stream_get_meta_data($sock);
        while ((!feof($sock)) && (!$info["timed_out"])) {
            $response .= fread($sock, $this->chunkSize);
            $info = stream_get_meta_data($sock);
        }
        fclose($sock);
        ob_end_clean();
        if ($info["timed_out"]) {
            $this->errorMessage = "Could not read response (timed out)";
            $this->errorCode = -98;
            return false;
        }

        list($headers, $response) = explode("\r\n\r\n", $response, 2);
        $headers = explode("\r\n", $headers);
        $errored = false;
        foreach($headers as $h){
            if (substr($h,0,26)==="X-MailChimp-API-Error-Code"){
                $errored = true;
                $error_code = trim(substr($h,27));
                break;
            }
        }
        
        if(ini_get("magic_quotes_runtime")) $response = stripslashes($response);
        
        $serial = unserialize($response);
        if($response && $serial === false) {
        	$response = array("error" => "Bad Response.  Got This: " . $response, "code" => "-99");
        } else {
        	$response = $serial;
        }
        if($errored && is_array($response) && isset($response["error"])) {
            $this->errorMessage = $response["error"];
            $this->errorCode = $response["code"];
            return false;
        } elseif($errored){
            $this->errorMessage = "No error message was found";
            $this->errorCode = $error_code;
            return false;
        }
        
        return $response;
    }
    
    /**
     * Actually connect to the server and call the requested methods, parsing the result
     * You should never have to call this function manually
     */
    function __call($method, $params) {
	    $dc = "us1";
	    if (strstr($this->api_key,"-")){
        	list($key, $dc) = explode("-",$this->api_key,2);
            if (!$dc) $dc = "us1";
        }
        $host = $dc.".".$this->apiUrl["host"];

        $this->errorMessage = "";
        $this->errorCode = "";
        $sep_changed = false;
        //sigh, apparently some distribs change this to &amp; by default
        if (ini_get("arg_separator.output")!="&"){
            $sep_changed = true;
            $orig_sep = ini_get("arg_separator.output");
            ini_set("arg_separator.output", "&");
        }
        //mutate params
        $mutate = array();
		$mutate["apikey"] = $this->api_key;
        foreach($params as $k=>$v){
            $mutate[$this->function_map[$method][$k]] = $v;
        }
        $post_vars = http_build_query($mutate);
        if ($sep_changed){
            ini_set("arg_separator.output", $orig_sep);
        }
        
        $payload = "POST " . $this->apiUrl["path"] . "?" . $this->apiUrl["query"] . "&method=" . $method . " HTTP/1.0\r\n";
        $payload .= "Host: " . $host . "\r\n";
        $payload .= "User-Agent: MCAPImini/" . $this->version ."\r\n";
        $payload .= "Content-type: application/x-www-form-urlencoded\r\n";
        $payload .= "Content-length: " . strlen($post_vars) . "\r\n";
        $payload .= "Connection: close \r\n\r\n";
        $payload .= $post_vars;
        
        ob_start();
        if ($this->secure){
            $sock = fsockopen("ssl://".$host, 443, $errno, $errstr, 30);
        } else {
            $sock = fsockopen($host, 80, $errno, $errstr, 30);
        }
        if(!$sock) {
            $this->errorMessage = "Could not connect (ERR $errno: $errstr)";
            $this->errorCode = "-99";
            ob_end_clean();
            return false;
        }
        
        $response = "";
        fwrite($sock, $payload);
        stream_set_timeout($sock, $this->timeout);
        $info = stream_get_meta_data($sock);
        while ((!feof($sock)) && (!$info["timed_out"])) {
            $response .= fread($sock, $this->chunkSize);
            $info = stream_get_meta_data($sock);
        }
        fclose($sock);
        ob_end_clean();
        if ($info["timed_out"]) {
            $this->errorMessage = "Could not read response (timed out)";
            $this->errorCode = -98;
            return false;
        }

        list($headers, $response) = explode("\r\n\r\n", $response, 2);
        $headers = explode("\r\n", $headers);
        $errored = false;
        foreach($headers as $h){
            if (substr($h,0,26)==="X-MailChimp-API-Error-Code"){
                $errored = true;
                $error_code = trim(substr($h,27));
                break;
            }
        }
        
        if(ini_get("magic_quotes_runtime")) $response = stripslashes($response);
        
        $serial = unserialize($response);
        if($response && $serial === false) {
        	$response = array("error" => "Bad Response.  Got This: " . $response, "code" => "-99");
        } else {
        	$response = $serial;
        }
        if($errored && is_array($response) && isset($response["error"])) {
            $this->errorMessage = $response["error"];
            $this->errorCode = $response["code"];
            return false;
        } elseif($errored){
            $this->errorMessage = "No error message was found";
            $this->errorCode = $error_code;
            return false;
        }
        
        return $response;
    }
    
    protected $function_map = array('campaignUnschedule'=>array("cid"),
'campaignSchedule'=>array("cid","schedule_time","schedule_time_b"),
'campaignResume'=>array("cid"),
'campaignPause'=>array("cid"),
'campaignSendNow'=>array("cid"),
'campaignSendTest'=>array("cid","test_emails","send_type"),
'campaignSegmentTest'=>array("list_id","options"),
'campaignCreate'=>array("type","options","content","segment_opts","type_opts"),
'campaignUpdate'=>array("cid","name","value"),
'campaignReplicate'=>array("cid"),
'campaignDelete'=>array("cid"),
'campaigns'=>array("filters","start","limit"),
'campaignStats'=>array("cid"),
'campaignClickStats'=>array("cid"),
'campaignEmailDomainPerformance'=>array("cid"),
'campaignMembers'=>array("cid","status","start","limit"),
'campaignHardBounces'=>array("cid","start","limit"),
'campaignSoftBounces'=>array("cid","start","limit"),
'campaignUnsubscribes'=>array("cid","start","limit"),
'campaignAbuseReports'=>array("cid","since","start","limit"),
'campaignAdvice'=>array("cid"),
'campaignAnalytics'=>array("cid"),
'campaignGeoOpens'=>array("cid"),
'campaignGeoOpensForCountry'=>array("cid","code"),
'campaignEepUrlStats'=>array("cid"),
'campaignBounceMessage'=>array("cid","email"),
'campaignBounceMessages'=>array("cid","start","limit","since"),
'campaignEcommOrders'=>array("cid","start","limit","since"),
'campaignShareReport'=>array("cid","opts"),
'campaignContent'=>array("cid","for_archive"),
'campaignTemplateContent'=>array("cid"),
'campaignOpenedAIM'=>array("cid","start","limit"),
'campaignNotOpenedAIM'=>array("cid","start","limit"),
'campaignClickDetailAIM'=>array("cid","url","start","limit"),
'campaignEmailStatsAIM'=>array("cid","email_address"),
'campaignEmailStatsAIMAll'=>array("cid","start","limit"),
'campaignEcommOrderAdd'=>array("order"),
'lists'=>array("filters","start","limit"),
'listMergeVars'=>array("id"),
'listMergeVarAdd'=>array("id","tag","name","options"),
'listMergeVarUpdate'=>array("id","tag","options"),
'listMergeVarDel'=>array("id","tag"),
'listInterestGroupings'=>array("id"),
'listInterestGroupAdd'=>array("id","group_name","grouping_id"),
'listInterestGroupDel'=>array("id","group_name","grouping_id"),
'listInterestGroupUpdate'=>array("id","old_name","new_name","grouping_id"),
'listInterestGroupingAdd'=>array("id","name","type","groups"),
'listInterestGroupingUpdate'=>array("grouping_id","name","value"),
'listInterestGroupingDel'=>array("grouping_id"),
'listWebhooks'=>array("id"),
'listWebhookAdd'=>array("id","url","actions","sources"),
'listWebhookDel'=>array("id","url"),
'listStaticSegments'=>array("id"),
'listStaticSegmentAdd'=>array("id","name"),
'listStaticSegmentReset'=>array("id","seg_id"),
'listStaticSegmentDel'=>array("id","seg_id"),
'listStaticSegmentMembersAdd'=>array("id","seg_id","batch"),
'listStaticSegmentMembersDel'=>array("id","seg_id","batch"),
'listSubscribe'=>array("id","email_address","merge_vars","email_type","double_optin","update_existing","replace_interests","send_welcome"),
'listUnsubscribe'=>array("id","email_address","delete_member","send_goodbye","send_notify"),
'listUpdateMember'=>array("id","email_address","merge_vars","email_type","replace_interests"),
'listBatchSubscribe'=>array("id","batch","double_optin","update_existing","replace_interests"),
'listBatchUnsubscribe'=>array("id","emails","delete_member","send_goodbye","send_notify"),
'listMembers'=>array("id","status","since","start","limit"),
'listMemberInfo'=>array("id","email_address"),
'listMemberActivity'=>array("id","email_address"),
'listAbuseReports'=>array("id","start","limit","since"),
'listGrowthHistory'=>array("id"),
'listActivity'=>array("id"),
'listLocations'=>array("id"),
'listClients'=>array("id"),
'templates'=>array("types","category","inactives"),
'templateInfo'=>array("tid","type"),
'templateAdd'=>array("name","html"),
'templateUpdate'=>array("id","values"),
'templateDel'=>array("id"),
'templateUndel'=>array("id"),
'getAccountDetails'=>array(),
'generateText'=>array("type","content"),
'inlineCss'=>array("html","strip_css"),
'folders'=>array("type"),
'folderAdd'=>array("name","type"),
'folderUpdate'=>array("fid","name","type"),
'folderDel'=>array("fid","type"),
'ecommOrders'=>array("start","limit","since"),
'ecommOrderAdd'=>array("order"),
'ecommOrderDel'=>array("store_id","order_id"),
'listsForEmail'=>array("email_address"),
'campaignsForEmail'=>array("email_address"),
'chimpChatter'=>array(),
'apikeys'=>array("username","password","expired"),
'apikeyAdd'=>array("username","password"),
'apikeyExpire'=>array("username","password"),
'ping'=>array());

}

?>