
# Login and Authentication

You may either authenticate each individual reuqest by their API key and secret, or create login sessions and provide access and refresh tokens for all future requests.  All authentication is handled by the `Apex\Armor\ApiKeys\ApiRequest` class.

## Authenticate Individual Request

It you wish to authenticate each individual request instead of creating login sessions, you may do so by passing the API key and secret to the `Apex\Armor\APiKeys\ApiRequest::authenticate()` method, for example:

~~~php
use Apex\Armor\Armor;
use Apex\Armor\ApiKeys\ApiRequest;

// Init Armor
$armor = new Armor();

// Authenticate request
$api = new ApiRequest($armor);
if (!$user = $api->authenticate($_POST['api_key'], $_POST['api_secret'])) { 
    die("Invalid API key / secret");
}

// Request authenticated, $user is now ArmorUserInterface object of API key
~~~


## Create Login Session

Alternatively, you may create login sessions and provide users with access and refresh tokens for all future API calls.  This is more secure as the API secret is only transmitted once during the initial login, and never again during the length of the session.  

You may wish to modify two settings within the ArmorPolicy configuration to properly utilize refresh tokens, which are:

Login sessions can be created by calling the `Apex\Armor\ApiKeys\ApiRequest::login()` method, which accepts the following parameters:

Variable | Required | Type | Description
------------- |------------- |------------- |------------- 
`$api_key` | Yes | string | The API key
`$api_secret` | Yes | string | The API secret.
`$expire_refresh_secs` | No | int | The number of seconds before the refresh token that will be generated will expire.  Defaults to 90 minutes.

This will return a null on failure, or an array on successful login with contains the following elements:

Element | Description
------------- |------------- 
uuid | The UUID of the authenticated user.
access_token | The access token which needs to be sent to authenticate all future API calls.
expires_at | Time in seconds from the epoch when the access token will expire.
refresh_token | The refresh token used to generate another access token.  Please see the [Refresh Tokens](refresh_tokens.md) page for details.
refresh_expires_at | The time in seconds since the epoch when the refresh token will expire.

For example:

~~~php
use Apex\Armor\Armor;
use Apex\Armor\ApiKeys\ApiRequest;

// Init Armor
$armor = new Armor();

// Login
$api = new ApiRequest($armor);
if (!$res = $api->login($_POST['api_key'], $_POST['api_secret'])) { 
    die("Invalid API key / secret");
}

/**
 * You must send $res['access_token'] and $res['refresh_token'] back to user.  
 * The $res['access_token'] needs to be sent to authenticate all future PAI calls (see below).
 */
~~~


## Authenticate API Calls

After a session has been created, all future API calls may be authenticated by passing the access token to the `Apex\Armor\ApiKeys\ApiRequest::lookup()` method, for example:

~~~php
use Apex\Armor\Armor;
use Apex\Armor\ApiKeys\ApiRequest;

// Init Armor
$armor = new Armor();

// Get access token from $_POST, HTTP headers, or where ever
$access_token = $_POST['token'];

// Authenticate API call
$api = new ApiRequest($armor);
if (!$session = $api->lookup($access_token)) { 
    die("Invalid request");
}

// Get authenticated user
$user = $session->getUser();
~~~

This will return an instance of the `AuthSession` class upon success, or null on failure.



