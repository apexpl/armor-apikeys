<?php
declare(strict_types = 1);

namespace APex\Armor\ApiKeys;

use Apex\Armor\Armor;
use Apex\Armor\Auth\Operations\{RandomString, Password};
use Apex\Container\Di;
use Apex\Db\Interfaces\DbInterface;

/**
 * API key manager
 */
class KeyManager
{

    /**
     * Constructor
     */
    public function __construct(
        private Armor $armor
    ) { 
        $this->db = Di::get(DbInterface::class);
    }

    /**
     * Generate API key
     */
    public function generate(string $uuid):array
    {

        // Create API key
        do { 
            $api_key = RandomString::get(24);
            $exists = $this->db->getField("SELECT count(*) FROM armor_keys WHERE public_key = %s AND algo = 'api'", $api_key);
        } while ($exists > 0);

        // Generate password
        $password = RandomString::get(36);

        // Add to database
        $this->db->insert('armor_keys', [
            'uuid' => $uuid, 
            'algo' => 'api', 
            'public_key' => $api_key, 
            'private_key' => Password::hash($password)
        ]);

        // Return
        return [$api_key, $password];
    }

    /**
     * Get Uuid
     */
    public function getUuid(string $uuid):array
    {
        $api_keys = $this->db->getColumn("SELECT public_key FROM armor_keys WHERE uuid = %s AND algo = 'api'", $uuid);
        return $api_keys;
    }

    /**
     * Delete
     */
    public function delete(string $api_key):bool
    {
        $stmt = $this->db->query("DELETE FROM armor_keys WHERE public_key = %s AND algo = 'api'", $api_key);
        $num = $this->db->numRows($stmt);
        return $num > 0 ? true : false;
    }

    /**
     * Delete uuid
     */
    public function deleteUuid(string $uuid):int
    {
        $stmt = $this->db->query("DELETE FROM armor_keys WHERE uuid = %s AND algo = 'api'", $uuid);
        return $this->db->numRows($stmt);
    }

    /**
     * Purge
     */
    public function purge():int
    {
        $stmt = $this->db->query("DELETE FROM armor_keys WHERE algo = 'api'");
        return $this->db->numRows($stmt);
    }

}

