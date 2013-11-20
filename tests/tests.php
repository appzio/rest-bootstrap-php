<?php

/* phpunit tests for Activation Engine REST API 
   api version 1.0
*/


class PHPSDKTestCase extends PHPUnit_Framework_TestCase {
  const API_KEY = 'b8d580cef3967549';
  const API_SECRET_KEY = '24d4d308568cdd4e';
  const BASEURL = 'http://aengine.net';
  const APIURL = 'http://aengine.net/api';

  const TEST_USER   = 6;
  const TEST_USER_2 = 78;
  
  private $accesstoken;
  private $params = array(
      'api_key'  => self::API_KEY,
      'api_secret_key' => self::API_SECRET_KEY,
      'api_url' => self::APIURL
    );
  

  private static $kExpiredAccessToken = 'AAABrFmeaJjgBAIshbq5ZBqZBICsmveZCZBi6O4w9HSTkFI73VMtmkL9jLuWsZBZC9QMHvJFtSulZAqonZBRIByzGooCZC8DWr0t1M4BL9FARdQwPWPnIqCiFQ';

  public static function testConnection(){
  	if(self::doCall(self::BASEURL .'/en/site/index')){
  		return true;
  	}
  }  

  /* test connection */
  public function testApi(){
  	$activationengine = new ActivationEngine($this->params);
    
    $callresult = $activationengine->testKey();
    $this->assertEquals($callresult->msg,'Hello World!', 'Library broken?');
  }
  
  /* tests that we can create user, check its access token and drop the same user */
  public function testCreateUser(){
  	$activationengine = new ActivationEngine($this->params);
    
    $userparams = array(
    	'temp' => true);
    
    /* create user */
    $userinfo = $activationengine->createUser($userparams);
        
	$this->assertEquals(strlen($userinfo->token),16,"doesn't look like a valid token");
	
	/* test whether token is valid */	
	$test = $activationengine->testAccessToken($userinfo->token);
	$this->assertEquals($test, true, "Token does not work :-(");
	
	/* drop user */
	$test = $activationengine->dropUser($userinfo->username);
	$this->assertEquals($test, true, "Couldn't delete the user :-/");

  }

  private static function doCall($call,$post=false){
    $opts = array('http'=>array('header' => "User-Agent:MyAgent/1.0\r\n"));
	$context = stream_context_create($opts);
  	$content = file_get_contents($call,false,$context);
  	
  	if($content){
  	  	return $content;
  	} else {
  		return false;
  	}

  }
	
}




