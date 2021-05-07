<?php
declare(strict_types = 1);

use Apex\Armor\Armor;
use Apex\Armor\Policy\ArmorPolicy;
use Apex\Armor\ApiKeys\{KeyManager, ApiRequest, RefreshToken};
use Apex\Armor\User\ArmorUser;
use Apex\Armor\Auth\AuthSession;
use Apex\Container\Di;
use Apex\Db\Interfaces\DbInterface;
use PHPUnit\Framework\TestCase;

/**
 * PGP Keys
 */
class auth_test extends TestCase
{

    /**
     * Test create
     */
    public function test_create()
    {

        // Init
        $armor = new Armor(
            container_file: $_SERVER['test_container_file']
        );
        $armor->purge();
        $user = $armor->createUser('u:test', 'password12345', 'test', 'test@apexpl.io', '14165551234');
        $this->assertEquals(ArmorUser::class, $user::class);

        // Create API key
        $manager = Di::make(KeyManager::class);
        list($api_key, $secret) = $manager->generate('u:test');
        $this->assertNotNull($api_key);
        $this->assertIsString($api_key);

        // Authenticate
        $req = new ApiRequest($armor);
        $user = $req->authenticate($api_key, $secret);
        $this->assertNotNull($user);
        $this->assertEquals(ArmorUser::class, $user::class);
        $this->assertEquals('u:test', $user->getUuid());

        // Login
        $res = $req->login($api_key, $secret);
        $this->assertIsArray($res);
        $this->assertArrayHasKey('refresh_token', $res);
        $this->assertEquals('u:test', $res['uuid']);

        // Lookup
        $session = $req->lookup($res['access_token']);
        $this->assertNotNull($session);
        $this->assertEquals(AuthSession::class, $session::class);

        // Refresh token
        $refresh = new RefreshToken($armor);
        $ref = $refresh->refresh($res['access_token'], $res['refresh_token']);
        $this->assertIsArray($ref);
        $this->assertArrayHasKey('access_token', $ref);

        // Lookup
        $session = $req->lookup($ref['access_token']);
        $this->assertNotNull($session);
        $this->assertEquals(AuthSession::class, $session::class);

    }

}


