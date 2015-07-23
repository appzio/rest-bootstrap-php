<?php


/* This is the Appzio bootstrap class which will handle encryption and methods
   look at the tests.php on clues how to implement its usage.

   As a rule of thumb, api will always return json for both success and on errors. For
   public methods, the api is called with a valid session token, unencrypted and also the
   response is unencrypted. Private methods require authentication.

   If you need to debug a particular call, you can simply add ,'debug' => true) on the
   MakeRequest array. This will save files with both the request and response content
   (files ending in .txt). So for example, you would test it like this:

    public function addFacebookId($userid,$fbid){
        $callurl = $this->api_url .'/' .$this->api_key .'/users/addfacebookid';
        $return = $this->makeRequest($callurl,array('username' => $userid, 'fbid' => $fbid, 'debug' => true));


        if(is_object($return)){
            return $return;
        } else {
            return false;
        }
    }

    Before going any further with debugging something, make sure that the phpunit tests run
    without problems.

    Bootstrap library version 1.1.4, 23.7.2015 to work with Appzio api 1.7

*/

if (!function_exists('curl_init')) {
  throw new Exception('Appzio needs the CURL PHP extension.');
} 

if (!function_exists('json_decode')) {
  throw new Exception('Appzio needs the JSON PHP extension.');
}

if (!function_exists('mcrypt_decrypt')) {
  throw new Exception('Appzio needs the mcrypt PHP extension.');
}

require('Cryptor.php');
require('Decryptor.php');
require('Encryptor.php');

class Appzio 
{
  const AESS_COOKIE_NAME = 'aess';

  // We can set this to a high number because the main session
  // expiration will trump this.
  const AESS_COOKIE_EXPIRE = 31556926; 	// 1 year
  const API_VERSION = '1.1';
  const API_FORMAT = 'json';			// either json or html

  protected $api_key;
  protected $api_secret_key;
  protected $api_url;

  // Stores the shared session ID if one is set.
  protected $sharedSessionID;
  
  // defines whether the response is expected to be encrypted
  protected $encrypted_response = false;


  /*
        Encrypt options

        1 = depreceated, EOL 1/2015
        2 = secure PBKDF2 (default)
        3 = simple base 64, no real security

  */

  public $encryptScheme = 3;

  /**
   * Default options for curl.
   */
  public static $CURL_OPTS = array(
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_USERAGENT      => 'appzio-php-1.0.2',
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
        $query['debug'] = false;
        $query['return_userinfo'] = true;

        $this->encrypted_response = false;

		$ret = $this->makeRequest($callurl,$query);

		if(isset($ret->token) AND strlen($ret->token) == 32){
			return $ret;
		} else {
			return false;
		}
  }


    /* list branches */
    public function listBranches($token){
        $callurl = $this->api_url .'/' .$this->api_key .'/branches/listbranches';
        $return = $this->makeRequest($callurl,array('token' => $token));
        return $return;

    }

    public function manipulatePoints($username,$pointsystem,$points){
        $callurl = $this->api_url .'/' .$this->api_key .'/users/manipulatepoints';
        $query['pointsystem'] = $pointsystem;
        $query['points'] = $points;
        $query['username'] = $username;

        $ret = $this->makeRequest($callurl,$query);

        if(isset($ret->msg)){
            return $ret->msg;
        } else {
            return false;
        }

    }

    public function getUserInfo($username){
        $callurl = $this->api_url .'/' .$this->api_key .'/users/getuserinfo';
        $query['username'] = $username;
        //$query['stop'] = true;
        $return = $this->makeRequest($callurl,$query);

        if(is_object($return)){
            return $return;
        } else {
            return false;
        }
    }

    public function getActions($token){
        $callurl = $this->api_url .'/' .$token .'/actions/getactions';
        $query['debug'] = false;
        //$query['stop'] = true;
        $return = $this->makeRequest($callurl,$query);

        if(is_object($return)){
            return $return;
        } else {
            return false;
        }
    }

    /* marks action completed
        important to note: this action will resolve action and give points automatically

    */


    public function completeAction($token,$actionid,$actiontoken,$answer=false){
        $callurl = $this->api_url .'/' .$token .'/actions/completeaction';

        $query['debug'] = false;
        $query['token'] = $actiontoken;
        $query['actionid'] = $actionid;

        if($answer){
            $query['answer'] = $answer;
        }

        $return = $this->makeRequest($callurl,$query);

        if($return->msg == 'ok'){
            return true;
        } else {
            return false;
        }

    }

    /* following fields are supported:
        new_username,email,phone,firstname,lastname,timezone,temp_user */

    public function setUserInfo($username,$params){
        $callurl = $this->api_url .'/' .$this->api_key .'/users/setuserinfo';
        $query['username'] = $username;

        $return = $this->makeRequest($callurl,$query+$params);

        if($return->msg == 'ok'){
            return true;
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

    /* login user */
    public function loginUser($userid){
        $callurl = $this->api_url .'/' .$this->api_key .'/users/loginuser';
        $return = $this->makeRequest($callurl,array('username' => $userid));

        if(is_object($return) AND isset($return->token)){
            return $return->token;
        } else {
            return false;
        }
    }

    /* login user */
    public function logoutUser($userid){
        $callurl = $this->api_url .'/' .$this->api_key .'/users/logoutuser';
        $return = $this->makeRequest($callurl,array('username' => $userid));

        if(is_object($return) AND isset($return->token)){
            return $return->token;
        } else {
            return false;
        }
    }


    /* login user */
    public function addFacebookId($userid,$fbid){
        $callurl = $this->api_url .'/' .$this->api_key .'/users/addfacebookid';
        $return = $this->makeRequest($callurl,array('username' => $userid, 'fbid' => $fbid));


        if(is_object($return)){
            return $return;
        } else {
            return false;
        }
    }


    public function addFacebookToken($userid,$token){
        $callurl = $this->api_url .'/' .$this->api_key .'/users/addfacebooktoken';
        $return = $this->makeRequest($callurl,array('username' => $userid, 'fbtoken' => $token));

        if(is_object($return)){
            return $return;
        } else {
            return false;
        }
    }


    /* updates a single variable */
    public function updateVariable($userid,$variable_name,$variable_value){
        $callurl = $this->api_url .'/' .$this->api_key .'/variable/updateuservariable';
        $return = $this->makeRequest($callurl,array('username' => $userid, 'variablename' => $variable_name,'variablevalue' => $variable_value));

        if(is_object($return)){
            return $return;
        } else {
            return false;
        }
    }


    /* updates several variables at once */
    public function updateVariables($userid,$variables=array()){
        $callurl = $this->api_url .'/' .$this->api_key .'/variable/updateuservariables';
        $return = $this->makeRequest($callurl,array('username' => $userid, 'variables' => $variables,'debug' => true));

        if(is_object($return)){
            return $return;
        } else {
            return false;
        }
    }


    public function fetchVariable($userid,$variable_name){
        $callurl = $this->api_url .'/' .$this->api_key .'/variable/fetchuservariable';
        $return = $this->makeRequest($callurl,array('username' => $userid, 'variablename' => $variable_name));

        if(is_object($return)){
            return $return;
        } else {
            return false;
        }
    }



    /* fetches config for client (mobile client) */
   public function fetchClientConfig($token){
       $callurl = $this->api_url .'/' .$this->api_key .'/clientconfig/getclientconfig';
       $return = $this->makeRequest($callurl,array('token' => $token,'debug' => true));
       return $return;
   }


    /* fetch user points , returns json containing primary, secondary and tertiary points */
    public function fetchUserPoints($token){
        $callurl = $this->api_url .'/' .$token .'/points/getuserpoints';
        $return = $this->curlCall($callurl);
        $return = @json_decode($return);

        if(is_object(($return))){
            return $return;
        } else {
            return false;
        }
    }

    /* This will return toplist & additional array which shows current users relevant position (ie. two records before and after). IMPORTANT: you should cache these results, especially if you are using extravars option. If you want to extract player photo, you will need to request the specific variable for photo. That variable will return an image link.
        Parameters you can use with this method are:

        limit (default 10) -- how many results to return
        extravars (default: none) -- comma separated list of game variables that should be returned along with the list. Each added variable will affect performance, so make sure to cache these results.
        point (default primary) -- primary | secondary | tertiary to which points to return the list
        timelimit (default no limit) -- hour | day | month | year
    */

    public function fetchToplist($token){
        $callurl = $this->api_url .'/' .$token .'/points/toplist';
        $return = $this->curlCall($callurl);
        $return = @json_decode($return);

        if(is_object(($return))){
            return $return;
        } else {
            return false;
        }
    }


    /* adds a push id
        device should be either ios, android or web at this point
    */
    public function addPushId($userid,$pushid,$device){
        $callurl = $this->api_url .'/' .$this->api_key .'/users/activatepush';
        $return = $this->makeRequest($callurl,array('username' => $userid, 'pushid' => $pushid,'device' => $device));

        if(is_object($return)){
            return $return;
        } else {
            return false;
        }
    }

    /* adds a push id
        device should be either ios, android or web at this point
    */
    public function retrievePushId($userid){
        $callurl = $this->api_url .'/' .$this->api_key .'/users/retrievepushid';
        $return = $this->makeRequest($callurl,array('username' => $userid));

        if(is_object($return)){
            return $return;
        } else {
            return false;
        }
    }



    protected function curlCall($url,$params=array()){
        $ch = curl_init();
        $opts = self::$CURL_OPTS;
        $opts[CURLOPT_POSTFIELDS] = $params;
        $opts[CURLOPT_URL] = $url;
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

        return $result;
    }


    protected function makeRequest($url, $query=array(), $ch=null) {

        if(isset($query['debug']) AND $query['debug'] == true){
            $debug = true;
            unset($query['debug']);
        } else {
            $debug = false;
        }

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

        $params = json_encode($params);

        if($this->encryptScheme == 2){
            $params = $this->aeEncode($params);
        } elseif($this->encryptScheme == 3){
            $params = base64_encode($params);
        } else {
            throw new Exception('Encryption not supported by this library. Only 2 & 3 are valid values.');
        }

        $postfields = array('params' => $params, 'cryptversion' => $this->encryptScheme);

        if($debug == true){
            file_put_contents('request.txt',$url .'?cryptversion=' .$this->encryptScheme .'&params=' .$params);
        }

        if(isset($query['stop']) AND $query['stop'] == true){
            print_r($url .'----' );
            print_r($postfields);
            die();
        }


        $result = $this->curlCall($url,$postfields);

        if($debug == true){
            file_put_contents('raw-result.txt',$result);
        }

        if($this->encrypted_response == true){

            if($this->encryptScheme == 2){
                $ret = aeDecode($result);
            } elseif($this->encryptScheme == 3){
                $ret = base64_decode($result);
            }

            if($debug == true){
                file_put_contents('result-unencrypted.txt',$result);
            }

            return json_decode($ret);
        } else {
    /*		echo(chr(10) .'------------END-----------' .chr(10));*/
            if($debug == true){
                file_put_contents('result-plain.txt',$result);
                print_r(json_decode($result));
            }
            return json_decode($result);
        }

      }

        public function aeEncode($content){
            $cryptor = new \RNCryptor\Encryptor();
            $content = $cryptor->encrypt($content, $this->api_secret_key);
            return $content;
        }

        public function aeDecode($content){
            $cryptor = new \RNCryptor\Decryptor();
            $content = $cryptor->decrypt($content, $this->api_secret_key);
            return $content;
        }

}



if (!function_exists('hash_pbkdf2')) {

    /**
     * Based on pbkdf2() from https://defuse.ca/php-pbkdf2.htm. Made signature-compatible with hash_pbkdf2() in PHP5.5
     *
     * PBKDF2 key derivation function as defined by RSA's PKCS #5: https://www.ietf.org/rfc/rfc2898.txt
     * $algorithm - The hash algorithm to use. Recommended: SHA256
     * $password - The password.
     * $salt - A salt that is unique to the password.
     * $count - Iteration count. Higher is better, but slower. Recommended: At least 1000.
     * $key_length - The length of the derived key in bytes.
     * $raw_output - If true, the key is returned in raw binary format. Hex encoded otherwise.
     * Returns: A $key_length-byte key derived from the password and salt.
     *
     * Test vectors can be found here: https://www.ietf.org/rfc/rfc6070.txt
     *
     * This implementation of PBKDF2 was originally created by https://defuse.ca
     * With improvements by http://www.variations-of-shadow.com
     */
    function hash_pbkdf2($algorithm, $password, $salt, $count, $key_length = 0, $raw_output = false)
    {
        $algorithm = strtolower($algorithm);
        if(!in_array($algorithm, hash_algos(), true))
            die('PBKDF2 ERROR: Invalid hash algorithm.');
        if($count <= 0 || $key_length <= 0)
            die('PBKDF2 ERROR: Invalid parameters.');

        $hash_length = strlen(hash($algorithm, "", true));
        $block_count = ceil($key_length / $hash_length);

        $output = "";
        for($i = 1; $i <= $block_count; $i++) {
            // $i encoded as 4 bytes, big endian.
            $last = $salt . pack("N", $i);
            // first iteration
            $last = $xorsum = hash_hmac($algorithm, $last, $password, true);
            // perform the other $count - 1 iterations
            for ($j = 1; $j < $count; $j++) {
                $xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
            }
            $output .= $xorsum;
        }

        if($raw_output)
            return substr($output, 0, $key_length);
        else
            return bin2hex(substr($output, 0, $key_length));
    }
}
