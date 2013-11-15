<?php

session_start();

$base = realpath(dirname(__FILE__) . '/..');
require "$base/src/activationengine.php";
define('AEURL', 'http://ae.com/api');

$params = array(
    'api_key'  => 'ea47ee7cac54316e',
    'api_secret_key' => '3e2233f090e99fce',
    'api_url' => 'http://ae.com/api'
);

/* creates user if token doesn't exist in the session */
if(!isset($_SESSION['aetoken'])){
    $token = createToken($params);
} else {
    $activationengine = new ActivationEngine($params);

    $test = $activationengine->testAccessToken($_SESSION['aetoken']);

    if($test != true){
        $token = createToken($params);
    } else {
        $token = $_SESSION['aetoken'];
    }
}

$cssurl = AEURL .'/' .$token .'/publictoken/getcss?css=dashboard&format=html';
$dashboard = file_get_contents(AEURL .'/' .$_SESSION['aetoken'] .'/publictoken/getdashboard');

function createToken($params){
    $activationengine = new ActivationEngine($params);

    $userparams = array(
        'temp' => true);

    /* create user */
    $userinfo = $activationengine->createUser($userparams);
    if(isset($userinfo->token)){
        $_SESSION['aetoken'] = $userinfo->token;
        return $userinfo->token;
    } else {
        echo("Couldn't get a token");
        die();
    }
}

/* this will return either true or msgtoken */
function ae_callurl(){
    if(isset($_SESSION['aetoken'])){
        $msg = file_get_contents(AEURL .'/' .$_SESSION['aetoken'] .'/publictoken/visitpage?page=' .$_SERVER['PHP_SELF']);
        $return = json_decode($msg);
        if(isset($return->msg)){
            if($return->msg == 'ok'){
                return true;
            } else {
                if(isset($return->msgtoken)){
                    return $return->msgtoken;
                }
            }
        }
    } else {
        return true;
    }
}

?>