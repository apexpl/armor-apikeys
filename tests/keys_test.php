<?php
declare(strict_types = 1);

use Apex\Armor\Armor;
use Apex\Armor\Policy\ArmorPolicy;
use Apex\Armor\ApiKeys\{KeyManager, ApiRequest, RefreshToken};
use Apex\Armor\User\ArmorUser;
use Apex\Container\Di;
use Apex\Db\Interfaces\DbInterface;
use PHPUnit\Framework\TestCase;

/**
 * PGP Keys
 */
class keys_test extends TestCase
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
        $this->assertEquals(24, strlen($api_key));

        // Create second API key
        list($api_key, $secret) = $manager->generate('u:test');
        $this->assertNotNull($api_key);
        $this->assertIsString($api_key);
        $this->assertEquals(24, strlen($api_key));

        // List API keys
        $keys = $manager->getUuid('u:test');
        $this->assertIsArray($keys);
        $this->assertCount(2, $keys);
        $this->assertContains($api_key, $keys);

        // Delete key
        $manager->delete($api_key);
        $keys = $manager->getUuid('u:test');
        $this->assertIsArray($keys);
        $this->assertCount(1, $keys);
        $this->assertNotContains($api_key, $keys);

        // Delete uuid
        $manager->deleteUuid('u:test');
        $keys = $manager->getUuid('u:test');
        $this->assertIsArray($keys);
        $this->assertCount(0, $keys);

        // Purge
        $num = $manager->purge();
        $this->assertEquals(0, $num);
    }
}


