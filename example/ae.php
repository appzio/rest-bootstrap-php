<?php

session_start();

$base = realpath(dirname(__FILE__) . '/..');
require "$base/src/activationengine.php";
define('AEURL', 'http://ae.com/api');

$params = array(
    'api_key'  => '0c48ec8e9120b2d7',
    'api_secret_key' => '8b99bacd9e2af8af',
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
        $msg = file_get_contents(AEURL .'/' .$_SESSION['aetoken'] .'/publictoken/getpage?page=' .$_SERVER['PHP_SELF']);
        $return = json_decode($msg);
        echo($return->dashboard);

        if(isset($return->message)){
            echo($return->message);
        }
    } else {
        return false;
    }
}

?>