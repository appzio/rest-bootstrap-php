<?php

/* phpunit tests for Activation Engine REST API 
   boostrap library version 1.1.3, 2.9.2014 to work with Activation Engine api 1.1
*/

class PHPSDKTestCase extends PHPUnit_Framework_TestCase {

  const API_KEY = '535a3102e27cb2a3';
  const API_SECRET_KEY = 'a642095b46cf5542';
  const BASEURL = 'http://staging.aengine.net/';
  const APIURL = 'http://staging.aengine.net/api';

/*    const API_KEY = '33bcb5b8a0467dde';
    const API_SECRET_KEY = 'fda85945e09d32a1';
    const BASEURL = 'http://aengine.net/';
    const APIURL = 'http://aengine.net/api';*/

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

  const FBUSERID = '1097139142';

  private $accesstoken;
  private $params = array(
      'api_key'  => self::API_KEY,
      'api_secret_key' => self::API_SECRET_KEY,
      'api_url' => self::APIURL
    );

  private static $kExpiredAccessToken = 'AAABrFmeaJjgBAIshbq5ZBqZBICsmveZCZBi6O4w9HSTkFI73VMtmkL9jLuWsZBZC9QMHvJFtSulZAqonZBRIByzGooCZC8DWr0t1M4BL9FARdQwPWPnIqCiFQ';

  /* will test that the site is accessible */
  public static function testConnection(){
  	if(self::doCall(self::BASEURL .'/en/site/index')){
  		return true;
  	}
  }

  /* test that api is accessible. If not, make sure that API is enabled on your game and that keys are set correctly */
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
      $this->assertEquals(strlen($userinfo->token), 32, "Token does not work :-(");

      return $userinfo;
  }


    /* tests that we can create user, check its access token and drop the same user */
  public function testCreateUser(){
    $activationengine = new ActivationEngine($this->params);

    /* create user */
    $userinfo = $this->createUser($activationengine);
	$this->assertEquals(strlen($userinfo->token),32,"doesn't look like a valid token");
	
	/* test whether token is valid */	
	$test = $activationengine->testAccessToken($userinfo->token);
	$this->assertEquals($test, true, "Token does not work :-(");

    /* drop user */
    $test = $activationengine->dropUser($userinfo->username);
    $this->assertEquals($test, true, "Couldn't delete the user :-/");

  }

    public function testGetActions(){
        $activationengine = new ActivationEngine($this->params);
        $userinfo = $this->createUser($activationengine);
        $return = $activationengine->getActions($userinfo->token);

        $ret = each($return);
        $action = $ret[1];

        /* check we get an action list with at least one action */
        $this->assertEquals(is_object($action), true, "Didn't get a proper action list. Make sure your test game has an active action which includes Hello world! text.");
        $this->assertContains('Hello world', $action->msg, "Didn't get an action. Make sure your test game has an active action which includes Hello world! text.");

        /* check that the webview displays ok */
        $opts = array('http'=>array('header' => "User-Agent:MyAgent/1.0\r\n"));
        $context = stream_context_create($opts);
        $web = file_get_contents($action->actionurl_mobile_en,false,$context);
        $this->assertContains('Hello world', $web, "Didn't get action's webview. Make sure your test game has an active action which includes Hello world! text.");

        /* complete action */
        $return = $activationengine->completeAction($userinfo->token, $action->actionid, $action->token);
        $this->assertEquals(true,$return, "Couldn't complete the action :-/");

        /* drop user */
        $test = $activationengine->dropUser($userinfo->username);
        $this->assertEquals(true,$test, "Couldn't delete the user :-/");

    }



    /* this will test the simple base64 encryption scheme, which exists
        in favor of android to provide better performance at the cost of
        security */

    public function testSimpleEncrypt(){
        $activationengine = new ActivationEngine($this->params);
        $activationengine->encryptScheme = 3;

        /* create user */
        $userinfo = $this->createUser($activationengine);
        $this->assertEquals(strlen($userinfo->token),32,"doesn't look like a valid token, using simple encryption scheme");

        /* test whether token is valid */
        $test = $activationengine->testAccessToken($userinfo->token);
        $this->assertEquals($test, true, "Token does not work / connecting using simple encryption scheme");

        /* drop user */
        $test = $activationengine->dropUser($userinfo->username);
        $this->assertEquals($test, true, "Couldn't delete the user using simple encryption scheme");

    }



    public function testPushId(){
        /* create user, add push id */
        $activationengine = new ActivationEngine($this->params);
        $userinfo = $this->createUser($activationengine);
        $activationengine->addPushId($userinfo->username,'tester','ios');

        /* check that it got saved ok */
        $return = $activationengine->retrievePushId($userinfo->username);
        $this->assertEquals('tester', $return->device_id, 'Looks like there is a problem with setting the push id');
    }

    public function testLogin(){
        /* login user, returns token */
        $activationengine = new ActivationEngine($this->params);
        $userinfo = $this->createUser($activationengine);
        $token = $activationengine->loginUser($userinfo->username);
        $this->assertEquals(32, strlen($token), 'Looks like we did not get a valid token');

        /* drop user */
        $activationengine->dropUser($userinfo->username);
    }

    /* fetch users points */
    public function testGetUserPoints(){
        $activationengine = new ActivationEngine($this->params);
        $userinfo = $this->createUser($activationengine);
        $callresult = $activationengine->fetchUserPoints($userinfo->token);
        $this->assertEquals('2', $callresult->primary, 'Looks like we did not get point information correctly. Note that if you are testing this against
        your own game, the game should be setup so, that there is an invisible action which assigns player 2 points when game is createad. Ie. test expects
        to get two points for any new user');

        $callresult = $activationengine->fetchUserPoints($userinfo->token);

        $activationengine->manipulatePoints($userinfo->username,'primary','-1');
        $activationengine->manipulatePoints($userinfo->username,'secondary','1');
        $activationengine->manipulatePoints($userinfo->username,'tertiary','3');

        $callresult = $activationengine->fetchUserPoints($userinfo->token);

        $this->assertEquals('1',$callresult->primary,'something fishy with primary points');
        $this->assertEquals('3',$callresult->secondary,'something fishy with primary points');
        $this->assertEquals('5',$callresult->tertiary,'something fishy with primary points');

        /* drop user */
        $activationengine->dropUser($userinfo->username);
    }




    public function testUserinfo(){
        $activationengine = new ActivationEngine($this->params);
        $userinfo = $this->createUser($activationengine);

        /* set users phone number */
        $activationengine->setUserInfo($userinfo->username,array('phone' => '123'));
        $var_return = $activationengine->getUserInfo($userinfo->username);
        $this->assertEquals('123', $var_return->phone, 'Looks like user info does not work properly, problem getting the phone');

        /* test fetching user information (first sets variable) */
        $value = 'nuolijoki';
        $var = $activationengine->updateVariable($userinfo->username,'city',$value);
        $var_return = $activationengine->getUserInfo($userinfo->username);
        $this->assertEquals($value, $var_return->variables->city->value, 'Looks like user info does not work properly, problem getting variable');

        /* drop user */
        $activationengine->dropUser($userinfo->username);
    }


    public function testVariables(){
        $activationengine = new ActivationEngine($this->params);
        $userinfo = $this->createUser($activationengine);

        /* update variable value for user (note: test game must have variable called city for this to work */
        $value = 'muonio';
        $var = $activationengine->updateVariable($userinfo->username,'city',$value);
        $var_return = $activationengine->fetchVariable($userinfo->username,'city');
        $this->assertEquals($value, $var_return->variable, 'Looks like variables do not work properly');

        /* test updating several variables at once */
        $testarray = array('city' => 'espoo','name' => 'Juha');
        $activationengine->updateVariables($userinfo->username,$testarray);
        $var_return = $activationengine->fetchVariable($userinfo->username,'name');
        $this->assertEquals('Juha', $var_return->variable, 'Looks like variables do not work properly');

        /* drop user */
        $activationengine->dropUser($userinfo->username);

    }

    public function testFbId(){
        $activationengine = new ActivationEngine($this->params);
        $userinfo = $this->createUser($activationengine);

        /* add facebook id to user information NOTE: you can't use setUserInfo method for this */
        $return = $activationengine->addFacebookId($userinfo->username,self::FBUSERID);
        $this->assertEquals('ok', $return->msg, 'Looks like the facebook user was not valid');

        /* drop user */
        $activationengine->dropUser($userinfo->username);
    }


    public function testFbToken(){
        $activationengine = new ActivationEngine($this->params);
        $userinfo = $this->createUser($activationengine);

        /* tests the provided facebook token and adds it to user if its valid (also sets fbid based on token) */
        $return = $activationengine->addFacebookToken($userinfo->username,self::FBACCESSTOKEN);
        $this->assertEquals('ok', $return->msg, 'Looks like the facebook user was not valid');

        /* drop user */
        $activationengine->dropUser($userinfo->username);
    }


    /* this is a special method for mobile client, which returns configuration parameters for it
        now depreceated. This information comes with userinfo.
    */

    /*    public function testFetchClientConfig(){
        $activationengine = new ActivationEngine($this->params);
        $userinfo = $this->createUser($activationengine);
        $callresult = $activationengine->fetchClientConfig($userinfo->token);
        $this->assertContains('main_color',$callresult->clientconfig, 'looks like client config was not returned properly');
        $this->assertContains('main_page',$callresult->strings, 'looks like localization was not returned properly');

        // drop user
        $activationengine->dropUser($userinfo->username);
    }*/


    /* fetches top list. see documentation for more info on parameters */
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




