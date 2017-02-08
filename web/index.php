<?php
/**
 * Example of retrieving the products list using Admin account via Magento REST API. OAuth authorization is used
 * Preconditions:
 * 1. Install php oauth extension
 * 2. If you were authorized as a Customer before this step, clear browser cookies for 'yourhost'
 * 3. Create at least one product in Magento
 * 4. Configure resource permissions for Admin REST user for retrieving all product data for Admin
 * 5. Create a Consumer
 */
// $callbackUrl is a path to your file with OAuth authentication example for the Admin user

// config vars set on the heroku app 
// heroku config:add BASE_URL=...
$baseURL = getenv("BASE_URL");
$consumerKey = getenv("CONSUMER_KEY");
$consumerSecret = getenv("CONSUMER_SECRET");
$callbackUrl = getenv("CALLBACK_URL");
$authorizeURL = getenv("AUTHORIZE_URL");

$requestTokenURL = $baseURL."/oauth/initiate";

if (!isset($authorizeURL)) {
    // admin authorize url
    $authorizeURL = $baseURL."/admin/oauth_authorize";
}

$accessTokenURL = $baseURL."/oauth/token";
$apiUrl = $baseURL."/api/rest";

session_start();

if (isset($_GET["reset_session"])) {
    $_SESSION['state'] = 0;
}



// helper to rerun the oauth flow with updated Magento settings
if (!isset($_GET['oauth_token']) && isset($_SESSION['state']) && $_SESSION['state'] == 1) {
    $_SESSION['state'] = 0;
}

try {
    $authType = ($_SESSION['state'] == 2) ? OAUTH_AUTH_TYPE_AUTHORIZATION : OAUTH_AUTH_TYPE_URI;
    $oauthClient = new OAuth($consumerKey, $consumerSecret, OAUTH_SIG_METHOD_HMACSHA1, $authType);
    $oauthClient->enableDebug();

    if (!isset($_GET['oauth_token']) && !$_SESSION['state']) {
        error_log("Fetching request token");
        $requestToken = $oauthClient->getRequestToken($requestTokenURL, $callbackUrl);
        $url = $authorizeURL . '?oauth_token=' . $requestToken['oauth_token'];
        $_SESSION['secret'] = $requestToken['oauth_token_secret'];
        $_SESSION['state'] = 1;
        error_log("Redirect");
        header('Location: ' . $url);
        exit;
    } else if ($_SESSION['state'] == 1) {
        error_log("Fetching access token");
        $oauthClient->setToken($_GET['oauth_token'], $_SESSION['secret']);
        $accessToken = $oauthClient->getAccessToken($accessTokenURL);
        $_SESSION['state'] = 2;
        $_SESSION['token'] = $accessToken['oauth_token'];
        $_SESSION['secret'] = $accessToken['oauth_token_secret'];
        header('Location: ' . $callbackUrl);
        exit;
    } else {
        error_log("Sending request to magento api");
        $oauthClient->setToken($_SESSION['token'], $_SESSION['secret']);
        $resourceUrl = "$apiUrl/orders";
        $oauthClient->fetch($resourceUrl, array(), 'GET', array('Content-Type' => 'application/json'));
        $ordersList = json_decode($oauthClient->getLastResponse());
        print_r($ordersList);
        echo "<p><a href=\"?reset_session=1\"> Reset session </a></p>";
    }
} catch (OAuthException $e) {
    error_log("Oauth error: ".$e->getMessage());
    print_r($e->getMessage());
    echo "<br/>";
    print_r($e->lastResponse);
    
    echo "<p> Oauth Token: ".$_SESSION['token']."</p>";
    echo "<p> Oauth Token Secret: ".$_SESSION['secret']."</p>";
    
    echo "<p><a href=\"?reset_session=1\"> Reset session </a></p>";
}