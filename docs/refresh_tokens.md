
$ Refresh Tokens

For greater security, you may utilize refresh tokens within your applications meaning access tokens will expire after a short period of time, but a new access token can be automatically generated by using the refresh token.  This helps prevent things such as attackers sniffing wifi networks and obtaining access tokens, as they will only be valid for a fairly short period of time.

To properly utilize refresh tokens, please take note of two settings within the ArmorPolicy configuration:

* `expire_redis_session_secs` - Set this a little higher than the `expire_session_inactivty_secs` so the API throws an expired error status instead of abruptly throws an invalid session error.
* `lock_redis_expiration` _ Set to true, to force the user of refresh tokens as the access token will expire after initially created instead of the expiration date being updated every request.


## Refresh Session

You may generate a new access token by passing the refresh token to the `Apex\Armor\ApiKeys\RefreshToken::refresh()` method, for example:

~~~php
use Apex\Armor\Armor;
use Apex\Armor\ApiKeys\RefreshToken;

// Init Armor
$armor = new Armor();

// Obtain access and refresh token from user via $_POST, HTTP header, or whatever.
$access_token = $_POST['access_token'];
$refresh_token = $_POST['refresh_token'];

// Generate new access token
$ref = new RefreshToken($armor);
if (!$res = $ref->refresh($access_token, $access_token, $refresh_token)) { 
    die("Invalid refresh token");
}

// New access token is at $res['access_token']
~~~

This will return null on failure, or upon success will return the same array as when creating a login session which contains the following elements:

Element | Description
------------- |------------- 
uuid | The UUID of the authenticated user.
access_token | The access token which needs to be sent to authenticate all future API calls.
expires_at | Time in seconds from the epoch when the access token will expire.
refresh_token | The refresh token used to generate another access token.  Please see the [Refresh Tokens](refresh_tokens.md) page for details.
refresh_expires_at | The time in seconds since the epoch when the refresh token will expire.


