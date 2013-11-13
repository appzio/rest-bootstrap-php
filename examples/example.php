<?php



$base = realpath(dirname(__FILE__) . '/..');
require "$base/src/activationengine.php";

$url = 'http://ae.com/api';

$params = array(
    'api_key'  => 'ea47ee7cac54316e',
    'api_secret_key' => '3e2233f090e99fce',
    'api_url' => 'http://ae.com/api'
);

/* creates user if token doesn't exist in the session */
if(!isset($_SESSION['aetoken'])){
    createToken($params);
} else {
    $activationengine = new ActivationEngine($params);
    $test = $activationengine->testAccessToken($_SESSION['aetoken']);
    if($test->msg != 'ok'){
        createToken($params);
    }
}

$dashboard = file_get_contents($url .'/' .$_SESSION['aetoken'] .'/publicapi/getdashboard');
print_r($dashboard);


function createToken($params){
    $activationengine = new ActivationEngine($params);
    $userparams = array(
        'temp' => true);

    /* create user */
    $userinfo = $activationengine->createUser($userparams);
    $_SESSION['aetoken'] = $userinfo->token;

}

?>