<?php

/* phpunit tests for Appzio REST API 
*/

/*const TEST_API_KEY = '67fa7be1534eeb2c';
const TEST_API_SECRET_KEY = '8f6e9f242e718b67';
const TEST_BASEURL = 'http://staging.appzio.com/';
const TEST_APIURL = 'http://staging.appzio.com/api';
const TEST_DEBUG = true;*/


class PHPSDKTestCase extends PHPUnit_Framework_TestCase
{

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
        'api_key' => TEST_API_KEY,
        'api_secret_key' => TEST_API_SECRET_KEY,
        'api_url' => TEST_APIURL,
        'debug' => TEST_DEBUG // when this is set to true, all calls & responses will be logged under /log
    );

    private static $kExpiredAccessToken = 'AAABrFmeaJjgBAIshbq5ZBqZBICsmveZCZBi6O4w9HSTkFI73VMtmkL9jLuWsZBZC9QMHvJFtSulZAqonZBRIByzGooCZC8DWr0t1M4BL9FARdQwPWPnIqCiFQ';

    /* will test that the site is accessible */
    public static function testConnection()
    {
        if (self::doCall(TEST_BASEURL . '/en/site/index')) {
            return true;
        }
    }


    /* test that api is accessible. If not, make sure that API is enabled on your game and that keys are set correctly */
    public function testApi()
    {
        $appzio = new Appzio($this->params);
        $callresult = $appzio->testKey();
        $this->assertEquals($callresult->msg, 'Hello World!', 'Library broken?');
    }


    public function testAssets()
    {

        $appzio = new Appzio($this->params);
        $userinfo = $this->createUser($appzio);
        $assetlist = $userinfo->userinfo->assets->assetlist;

        $this->assertEquals(is_array($assetlist), true, 'No assetlist');

        $imagepath = $userinfo->userinfo->assets->path_image;
        $zippath = $userinfo->userinfo->assets->path_zip;

        foreach ($assetlist as $asset) {
            if (strstr($asset->filename, '.zip')) {
                $file = file_get_contents($zippath . $asset->filename);
            } else {
                $file = file_get_contents($imagepath . $asset->filename);
            }
            $this->assertEquals(empty($file), false, 'Missing file' . $asset->filename);
        }

    }

    public function testVarious()
    {

        $appzio = new Appzio($this->params);
        $userinfo = $this->createUser($appzio);
        $rawuserinfo = json_encode($userinfo);

        $this->assertNotContains('rgba(', $rawuserinfo, "Looks like we are trying to send RGBA colors to client, should be converted to hex.");
        $branches = $appzio->listBranches($userinfo->token);

        /* write to error log */
        $test = $appzio->deliverErrorLog();
        $this->assertEquals($test->msg, 'ok', "Couldn't write to error log");


        /* drop user */
        $test = $appzio->dropUser($userinfo->username);
        $this->assertEquals($test, true, "Couldn't delete the user :-/");

    }


    /* tests that we can create user, check its access token and drop the same user */
    public function testCreateUser()
    {
        $appzio = new Appzio($this->params);

        /* create user */
        $userinfo = $this->createUser($appzio);
        $this->assertEquals(strlen($userinfo->token), 32, "doesn't look like a valid token");

        /* test whether token is valid */
        $test = $appzio->testAccessToken($userinfo->token);
        $this->assertEquals($test, true, "Token does not work :-(");

        /* drop user */
        $test = $appzio->dropUser($userinfo->username);
        $this->assertEquals($test, true, "Couldn't delete the user :-/");
    }


    public function testUploadFile()
    {
        $appzio = new Appzio($this->params);
        $userinfo = $this->createUser($appzio);
        $rawuserinfo = json_encode($userinfo);

        $upload = $appzio->uploadFile($userinfo->username, 'profilepic', 'testupload.png');
        $var_return = $appzio->fetchVariable($userinfo->username, 'profilepic');

        if(isset($var_return->profilepic)){
            $this->assertContains('.png',$var_return->profilepic, 'Could not upload image');
        } else {
            $this->assertTrue(false,'Could not upload image');
        }

        /* drop user */
        $test = $appzio->dropUser($userinfo->username);
        $this->assertEquals($test, true, "Couldn't delete the user :-/");
    }



    public function testGetActions()
    {
        $appzio = new Appzio($this->params);
        $userinfo = $this->createUser($appzio);
        $return = $appzio->listBranches($userinfo->token);

        $ret = each($return);
        $action = $ret[1];

        if (isset($return->{0}->actions->{1})) {
            $firstaction = $return->{0}->actions->{1};
        } else {
            $firstaction = false;
        }

        /* check we get an action list with at least one action */
        $this->assertEquals(is_object($firstaction), true, "Didn't get a proper action list. Make sure your test game has an active action which includes Hello world! text.");
        $this->assertContains('Welcome', $firstaction->msg, "Didn't get an action. Make sure your test game has an active action which includes Welcome! text.");

        /* check that the webview displays ok */
        $opts = array('http' => array('header' => "User-Agent:MyAgent/1.0\r\n"));
        $context = stream_context_create($opts);
        $web = file_get_contents($firstaction->actionurl_mobile_en, false, $context);
        $this->assertContains('Welcome', $web, "Didn't get action's webview. Make sure your test game has an active action which includes Hello world! text.");

        /* complete action */
        $return = $appzio->completeAction($userinfo->token, $firstaction->actionid, $firstaction->token);
        $this->assertEquals(true, $return, "Couldn't complete the action :-/");

        /* drop user */
        $test = $appzio->dropUser($userinfo->username);
        $this->assertEquals(true, $test, "Couldn't delete the user :-/");
    }


    public function testLogin()
    {
        /* login user, returns token */
        $appzio = new Appzio($this->params);
        $userinfo = $this->createUser($appzio);
        $token = $appzio->loginUser($userinfo->username);
        $this->assertEquals(32, strlen($token), 'Looks like we did not get a valid token');
        $appzio->logoutUser($userinfo->username);

        $token2 = $appzio->loginUser($userinfo->username);
        $this->assertEquals(32, strlen($token2), 'Logout / login combo not working');
        //$this->assertNotEquals($token, strlen($token2), 'Logout / login combo not working');

        /* drop user */
        $appzio->dropUser($userinfo->username);
    }

    /* fetch users points */
    public function testGetUserPoints()
    {
        $appzio = new Appzio($this->params);
        $userinfo = $this->createUser($appzio);

        $callresult = $appzio->fetchUserPoints($userinfo->token);

        $this->assertEquals('2', $callresult->primary, 'Looks like we did not get point information correctly. Note that if you are testing this against
        your own game, the game should be setup so, that there is an invisible action which assigns player 2 points when game is createad. Ie. test expects
        to get two points for any new user');

        $callresult = $appzio->fetchUserPoints($userinfo->token);

        $appzio->manipulatePoints($userinfo->username, 'primary', '-1');
        $appzio->manipulatePoints($userinfo->username, 'secondary', '1');
        $appzio->manipulatePoints($userinfo->username, 'tertiary', '3');

        $callresult = $appzio->fetchUserPoints($userinfo->token);

        $this->assertEquals('1', $callresult->primary, 'something fishy with primary points');
        $this->assertEquals('3', $callresult->secondary, 'something fishy with secondary points');
        $this->assertEquals('5', $callresult->tertiary, 'something fishy with tertiary points');

        /* drop user */
        $appzio->dropUser($userinfo->username);
    }


    public function testUserinfo()
    {
        $appzio = new Appzio($this->params);
        $userinfo = $this->createUser($appzio);

        /* set users phone number */
        $appzio->setUserInfo($userinfo->username, array('phone' => '123'));
        $var_return = $appzio->getUserInfo($userinfo->username);
        $this->assertEquals('123', $var_return->phone, 'Looks like user info does not work properly, problem getting the phone');

        /* test fetching user information (first sets variable) */
        $value = 'nuolijoki';
        $var = $appzio->updateVariable($userinfo->username, 'city', $value);
        $var_return = $appzio->getUserInfo($userinfo->username);
        $this->assertEquals($value, $var_return->variables->city->value, 'Looks like user info does not work properly, problem getting variable');

        /* drop user */
        $appzio->dropUser($userinfo->username);
    }


    public function testVariables()
    {
        $appzio = new Appzio($this->params);
        $userinfo = $this->createUser($appzio);

        /* update variable value for user (note: test game must have variable called city for this to work */
        $value = 'muonio';
        $var = $appzio->updateVariable($userinfo->username, 'city', $value);
        $var_return = $appzio->fetchVariable($userinfo->username, 'city');
        $this->assertEquals($value, $var_return->variable, 'Looks like variables do not work properly');

        /* test updating several variables at once */
        $testarray = array('city' => 'espoo', 'name' => 'Juha');
        $appzio->updateVariables($userinfo->username, $testarray);
        $var_return = $appzio->fetchVariable($userinfo->username, 'name');
        $this->assertEquals('Juha', $var_return->variable, 'Looks like variables do not work properly');

        /* drop user */
        $appzio->dropUser($userinfo->username);
    }


    /* fetches top list. see documentation for more info on parameters */
    public function testListBranches()
    {
        $appzio = new Appzio($this->params);
        $userinfo = $this->createUser($appzio);
        $callresult = $appzio->listBranches($userinfo->token);

        if (isset($callresult->{0}->actions->{1}->subject)) {
            $subject = $callresult->{0}->actions->{1}->subject;
        } else {
            $subject = false;
        }

        $this->assertContains('Welcome', $subject, 'Looks like we did not get a top list');

        /* drop user */
        $appzio->dropUser($userinfo->username);
    }

    private static function doCall($call, $post = false)
    {
        $opts = array('http' => array('header' => "User-Agent:MyAgent/1.0\r\n"));
        $context = stream_context_create($opts);
        $content = file_get_contents($call, false, $context);

        if ($content) {
            return $content;
        } else {
            return false;
        }

    }

    private function createUser($appzio)
    {

        $userparams = array(
            'temp' => true,
            'debug' => false
        );

        /* create user */
        $userinfo = $appzio->createUser($userparams);
        $this->assertEquals(strlen($userinfo->token), 32, "Token does not work :-(");
        return $userinfo;
    }


}




