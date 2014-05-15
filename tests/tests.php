<?php

/* phpunit tests for Activation Engine REST API 
   api version 1.0
*/


class PHPSDKTestCase extends PHPUnit_Framework_TestCase {

/*  const API_KEY = '500b0d8ce48386d0';
  const API_SECRET_KEY = '90848de9d6e68020';
  const BASEURL = 'http://ae.com/';
  const APIURL = 'http://ae.com/api';*/

    const API_KEY = '33bcb5b8a0467dde';
    const API_SECRET_KEY = 'fda85945e09d32a1';
    const BASEURL = 'http://aengine.net/';
    const APIURL = 'http://aengine.net/api';

  const TEST_USER   = 6;
  const TEST_USER_2 = 78;

  /* get this from here: https://developers.facebook.com/tools/explorer/
     token is checked against https://graph.facebook.com/me?access_token=YOURTOKEN
     before its saved to user information
  */

  const FBACCESSTOKEN = 'AAABrFmeaJjgBAIshbq5ZBqZBICsmveZCZBi6O4w9HSTkFI73VMtmkL9jLuWsZBZC9QMHvJFtSulZAqonZBRIByzGooCZC8DWr0t1M4BL9FARdQwPWPnIqCiFQ';

    /* any valid Facebook user id, token & id are not cross-checked by the api, but saving
       the token is enough, it will also save fbuserid if its not set for the user
     */

  const FBUSERID = '1097139142"';

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

  private function createUser($activationengine){

      $userparams = array(
          'temp' => true
      );

      /* create user */
      $userinfo = $activationengine->createUser($userparams);

      return $userinfo;
  }
  
  /* tests that we can create user, check its access token and drop the same user */
  public function testCreateUser(){
    $activationengine = new ActivationEngine($this->params);

    /* create user */
      $userinfo = $this->createUser($activationengine);
    $count = 0;


/*    while($count < 100000){
        $userinfo = $this->createUser($activationengine);
        $count++;
    }*/

	$this->assertEquals(strlen($userinfo->token),32,"doesn't look like a valid token");
	
	/* test whether token is valid */	
	$test = $activationengine->testAccessToken($userinfo->token);
	$this->assertEquals($test, true, "Token does not work :-(");

    /* drop user */
    $test = $activationengine->dropUser($userinfo->username);
    $this->assertEquals($test, true, "Couldn't delete the user :-/");

  }


    public function testLogin(){
        $activationengine = new ActivationEngine($this->params);
        $userinfo = $this->createUser($activationengine);
        $token = $activationengine->loginUser($userinfo->username);
        $this->assertEquals(32, strlen($token), 'Looks like we did not get a valid token');

        /* drop user */
        $activationengine->dropUser($userinfo->username);
    }


    public function testVariables(){
        $activationengine = new ActivationEngine($this->params);
        $userinfo = $this->createUser($activationengine);
        $value = 'suolijoki';

        $var = $activationengine->updateVariable($userinfo->username,'kaupunki',$value);
        $var_return = $activationengine->fetchVariable($userinfo->username,'kaupunki');
        $this->assertEquals($value, $var_return->variable, 'Looks like variables do not work properly');


/*        $testarray = array('kaupunki' => 'espoo','name' => 'Juha');
        $activationengine->updateVariables($userinfo->username,$testarray);
        $var_return = $activationengine->fetchVariable($userinfo->username,'name');
        $this->assertEquals('Juha', $var_return->variable, 'Looks like variables do not work properly');*/

        /* drop user */
        $activationengine->dropUser($userinfo->username);

    }

    public function testFbId(){
        $activationengine = new ActivationEngine($this->params);
        $userinfo = $this->createUser($activationengine);
        $return = $activationengine->addFacebookId($userinfo->username,self::FBUSERID);
        $this->assertEquals('ok', $return->msg, 'Looks like the facebook user was not valid');

        /* drop user */
        $activationengine->dropUser($userinfo->username);
    }


    public function testFbToken(){
        $activationengine = new ActivationEngine($this->params);
        $userinfo = $this->createUser($activationengine);
        $return = $activationengine->addFacebookToken($userinfo->username,self::FBACCESSTOKEN);
        $this->assertEquals('ok', $return->msg, 'Looks like the facebook user was not valid');

        /* drop user */
        $activationengine->dropUser($userinfo->username);
    }

    public function testFetchClientConfig(){
        $activationengine = new ActivationEngine($this->params);
        $userinfo = $this->createUser($activationengine);
        $callresult = $activationengine->fetchClientConfig($userinfo->token);
        $this->assertContains('main_color',$callresult->clientconfig, 'looks like client config was not returned properly');
        $this->assertContains('main_page',$callresult->strings, 'looks like localization was not returned properly');

        /* drop user */
        $activationengine->dropUser($userinfo->username);
    }

    public function testGetUserPoints(){
        $activationengine = new ActivationEngine($this->params);
        $userinfo = $this->createUser($activationengine);
        $callresult = $activationengine->fetchUserPoints($userinfo->token);
        $this->assertEquals('2', $callresult->primary, 'Looks like we did not get point information correctly. Note that if you are testing this against
        your own game, the game should be setup so, that there is an invisible action which assigns player 2 points when game is createad. Ie. test expects
        to get two points for any new user');

        /* drop user */
        $activationengine->dropUser($userinfo->username);
    }

    public function testGetToplist(){
        $activationengine = new ActivationEngine($this->params);
        $userinfo = $this->createUser($activationengine);
        $callresult = $activationengine->fetchToplist($userinfo->token);
        $this->assertEquals('1', $callresult->toplist->{1}->rank, 'Looks like we did not get a top list');

        /* drop user */
        $activationengine->dropUser($userinfo->username);
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




