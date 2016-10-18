<?php

/* phpunit tests for Appzio REST API 
*/

const TEST_API_KEY = '9ba431ca8fec4330';
const TEST_API_SECRET_KEY = 'b7a7ef7cb349b71d';
const TEST_BASEURL = 'https://app.appzio.com/';
const TEST_APIURL = 'https://app.appzio.com/api';
const TEST_DEBUG = true;

/*const TEST_API_KEY = 'd59f75487c0fe09d';
const TEST_API_SECRET_KEY = 'f91a359cc1dbeabd';
const TEST_BASEURL = 'http://ae.com/';
const TEST_APIURL = 'http://ae.com/api';
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


    public function testRegistration()
    {

        $appzio = new Appzio($this->params);
        $userinfo = $this->createUser($appzio);

        $call = 'https://app.appzio.com/api/9ba431ca8fec4330/variable/updateuservariables?params=eyJmYl9sb2dpbiI6ImZhbHNlIiwiZGV2aWNlIjoieDg2XzY0Iiwic2NyZWVuX3dpZHRoIjoiMzIwIiwiZm9ybWF0IjoianNvbiIsInNjcmVlbl9oZWlnaHQiOiI1NjgiLCJhcGlfdmVyc2lvbiI6IjEuNzIiLCJxdWVyeSI6eyJ1c2VybmFtZSI6IjA1YjA1MjFjYjM4NDc4MDEiLCJhY3Rpb25pZCI6IjU4OTcyOCIsIm1lbnVpZCI6InJlc2V0LXBhc3N3b3JkLWZvcm0iLCJyZXR1cm5fYWN0aW9uIjoiMSIsInZhcmlhYmxlcyI6W3siNDA2MCI6IiJ9LHsiNDA3NCI6IiJ9XX0sImFwaV9rZXkiOiI5YmE0MzFjYThmZWM0MzMwIn0=&cryptversion=3';
        $result = $this->doCall($call);
        $this->assertContains('text":"Reset password',$result);

/*      $assetlist = $userinfo->userinfo->assets->assetlist;
        $this->assertEquals(is_array($assetlist), true, 'No assetlist');
        $branches = (array)$appzio->listBranches($userinfo->token);

        foreach($branches as $branch){
            $firstaction = $branch->actions->{1}->actionid;
            break;
        }


        // {\"username\":\"05b0521cb3847801\",\"actionid\":\"589728\",\"menuid\":\"reset-password-form\",\"return_action\":\"1\",\"variables\":[{\"4060\":\"\"},{\"4074\":\"\"}]},\"api_key\":\"9ba431ca8fec4330\"}"
        $vars['actionid'] = $firstaction;
        $appzio->updateVariables($userinfo->username,$vars);

        print_r($firstaction);die();
        //$test = $appzio->dropUser($userinfo->username);

        print_r($branches);die();*/

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




