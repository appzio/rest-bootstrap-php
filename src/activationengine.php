<?php


if (!function_exists('curl_init')) {
  throw new Exception('ActivationEngine needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
  throw new Exception('ActivationEngine needs the JSON PHP extension.');
}

if (!function_exists('mcrypt_decrypt')) {
  throw new Exception('ActivationEngine needs the mcrypt PHP extension.');
}




class ActivationEngine 
{
  const AESS_COOKIE_NAME = 'aess';

  // We can set this to a high number because the main session
  // expiration will trump this.
  const AESS_COOKIE_EXPIRE = 31556926; 	// 1 year
  const API_VERSION = '1.0';
  const API_FORMAT = 'json';			// either json or html
  
  protected $api_key;
  protected $api_secret_key;
  protected $api_url;


  // Stores the shared session ID if one is set.
  protected $sharedSessionID;
  
  // defines whether the response is expected to be encrypted
  protected $encrypted_response = false;
  
  /**
   * Default options for curl.
   */
  public static $CURL_OPTS = array(
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_USERAGENT      => 'activationengine-php-1.0.2',
  );

  
  public function __construct($config) {
  	$this->api_key = $config['api_key'];
  	$this->api_secret_key = $config['api_secret_key'];
  	$this->api_url = $config['api_url'];
    	
  }
  
  
  public function testKey(){
  	$callurl = $this->api_url .'/' .$this->api_key .'/test/testapi';  	
  	$return = $this->makeRequest($callurl);
  	return $return;
  }
  
  /* create user and returns valid access token */
  public function createUser($params){
  	  	$callurl = $this->api_url .'/' .$this->api_key .'/users/createuser';
  	  	$query['userinfo'] = $params;
		$ret = $this->makeRequest($callurl,$query);
		
		if(strlen($ret->token) == 16){
			return $ret;
		} else {
			return false;
		}
  }
  
  /* drop user */
  public function dropUser($username){
  	  	$callurl = $this->api_url .'/' .$this->api_key .'/users/dropuser';
  	  	$query['username'] = $username;
		$return = $this->makeRequest($callurl,$query);
		
  		if($return->msg == 'ok'){
 	 		return true;
 	 	} else {
	  		return false;
 	 	}
  }

  
  /* tests whether access token is valid */
  public function testAccessToken($token){
	$callurl = $this->api_url .'/' .$this->api_key .'/users/checktoken';  	
  	$return = $this->makeRequest($callurl,array('token' => $token));
  	  	  	
  	if($return->msg == 'ok'){
  		return true;
  	} else {
  		return false;
  	}
  }
  
  protected function makeRequest($url, $query=array(), $ch=null) {
    if (!$ch) {
      $ch = curl_init();
    }

    $opts = self::$CURL_OPTS;
    
  //  if ($this->getFileUploadSupport()) {
  //    $opts[CURLOPT_POSTFIELDS] = $params;
  //  } else {
  //    $opts[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&');
  //  }
    
    
    /* we send the api_key as param also, this is required
    to authorize the call */
    
    $params['api_key'] = $this->api_key;
    $params['api_version'] = self::API_VERSION;
    $params['format'] = self::API_FORMAT;
    
    if($this->encrypted_response == true){
    	$params['encrypt_response'] = true;
    }
        
	if($query){
		$params['query'] = $query;
	}

/*	echo(chr(10) .'------------START-----------' .chr(10));*/

    $params = json_encode($params);
    $params = $this->aeEncode($params);
    $opts[CURLOPT_POSTFIELDS] = array('params' => $params);
    
    $opts[CURLOPT_URL] = $url;
    
/*    echo($url);
	echo(chr(10));
    echo($params);
    echo(chr(10) .'again decoded:');
    echo($this->aeDecode($params));
    echo(chr(10));*/

    // disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
    // for 2 seconds if the server does not support this header.
    if (isset($opts[CURLOPT_HTTPHEADER])) {
      $existing_headers = $opts[CURLOPT_HTTPHEADER];
      $existing_headers[] = 'Expect:';
      $opts[CURLOPT_HTTPHEADER] = $existing_headers;
    } else {
      $opts[CURLOPT_HTTPHEADER] = array('Expect:');
    }
    
    curl_setopt_array($ch, $opts);
    $result = curl_exec($ch);

    $errno = curl_errno($ch);
    
    
    // CURLE_SSL_CACERT || CURLE_SSL_CACERT_BADFILE
    if ($errno == 60 || $errno == 77) {
      self::errorLog('Invalid or no certificate authority found, '.
                     'using bundled information');
      curl_setopt($ch, CURLOPT_CAINFO,
                  dirname(__FILE__) . DIRECTORY_SEPARATOR . 'fb_ca_chain_bundle.crt');
      $result = curl_exec($ch);
    }

    // With dual stacked DNS responses, it's possible for a server to
    // have IPv6 enabled but not have IPv6 connectivity.  If this is
    // the case, curl will try IPv4 first and if that fails, then it will
    // fall back to IPv6 and the error EHOSTUNREACH is returned by the
    // operating system.
    if ($result === false && empty($opts[CURLOPT_IPRESOLVE])) {
        $matches = array();
        $regex = '/Failed to connect to ([^:].*): Network is unreachable/';
        if (preg_match($regex, curl_error($ch), $matches)) {
          if (strlen(@inet_pton($matches[1])) === 16) {
            self::errorLog('Invalid IPv6 configuration on server, '.
                           'Please disable or get native IPv6 on your server.');
            self::$CURL_OPTS[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            $result = curl_exec($ch);
          }
        }
    }

    if ($result === false) {
    	echo(curl_error($ch));
	    curl_close($ch);
    }
    
    curl_close($ch);
    
    if($this->encrypted_response == true){
		$ret = aeDecode($result);
		return json_decode($ret);
	} else {
/*		echo(chr(10) .'------------END-----------' .chr(10));*/
		return json_decode($result);
	}
	
  }
  
  
        public  function aeEncode($content){
        	$cipher = $this->api_secret_key;
            $content = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $cipher, $content,MCRYPT_MODE_CBC,'f9sd92Adj22Aj9mB');
            $content = base64_encode($content);
            $content = urlencode($content);
            file_put_contents(rand(1,9),$content);
            return $content;
        }

        public  function aeDecode($content){
        	$cipher = $this->api_secret_key;
            $content = urldecode($content);
            $content = base64_decode($content);
            $content = mcrypt_decrypt(MCRYPT_RIJNDAEL_128,$cipher,$content,MCRYPT_MODE_CBC,'f9sd92Adj22Aj9mB');
            $content = rtrim($content,"\0\4");
            return $content;
        }



}
