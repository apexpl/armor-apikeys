
# Key Management

All API keys can be managed through the `Apex\Armor\ApiKeys\KeyManager` class.

## Generate API Key

Every key consists of both, an API key and API secret.  You may generate a new  API key by passing the UUID to the `Apex\Armor\ApiKeys\KeyManager::generate()` method, for example:

~~~php
use Apex\Armor\Armor;
use Apex\Armor\ApiKeys\KeyManager;

// Init Armor
$armor = new Armor();

// Generate API key
$manager = new KeyManager($armor);
list($api_key, $api_secret) = $manager->generate('u:442');
~~~

This will return a two element array with the API key and secret.  Please note, the API secret is hashed via Bcrypt and this is the only time you will have it in plain text.  There is no way to retrieve an API secret after generation.


## Available Methods

Aside from key generation, the `Apex\Armor\ApiKeys\KeyManager` class also contains the following methods:

* `array getUuid(string $uuid)` - Get one-dimensional array of all API keys registered to user.
* `bool delete(string $api_key)` - Delete individual API key.
* `int deleteUuid(string $uuid)` - Delete all API keys on user's account.
* `int purge()` - Delete all API keys in database.


