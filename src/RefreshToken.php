<?php
declare(strict_types = 1);

namespace Apex\Armor\ApiKeys;

use Apex\Armor\Armor;
use Apex\Armor\Auth\{SessionManager, AuthSession};
use Apex\Armor\Auth\Codes\StringCode;
use Apex\Container\Di;
use Apex\Db\Interfaces\DbInterface;
use redis;

/**
 * Refresh token
 */
class RefreshToken
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
     * Create
     */
    public function create(AuthSession $session, int $expire_secs = 5400):string
    {

        // Generate string
        list($token, $redis_key) = stringCode::get('refresh', 36);

        // Set request
        $request = [
            'uuid' => $session->getUuid(), 
            'expire_secs' => $expire_secs, 
            'expires_at' => (time() + $expire_secs)
        ];

        // Save to redis
        $redis = Di::get(redis::class);
        $redis->hmset($redis_key, $request);
        $redis->expire($redis_key, $expire_secs);

        // Return
        return $token;
    }

    /**
     * Refresh
     */
    public function refresh(string $access_token, string $refresh_token):?array
    {

        // Lookup access token
        $api = Di::make(ApiRequest::class);
        if (!$session = $api->lookup($access_token)) { 
            return null;
        }

        // Initialize
        $redis = Di::get(redis::class);
        $redis_key = 'armor:refresh:' . hash('sha512', $refresh_token);

        // Check redis
        if (!$vars = $redis->hgetall($redis_key)) { 
            return null;
        } elseif ($vars['uuid'] != $session->getUuid()) { 
            return null;
        }
        $user = $this->armor->getUuid($vars['uuid']);

        // Create session
        $manager = Di::make(SessionManager::class);
        $session = $manager->create($user, 'ok', '', false);
        $session->setAttribute('is_api', 'true');

        // Update expiration
        $redis->hset($redis_key, 'expires_at', (time() + (int) $vars['expire_secs']));
        $redis->expire($redis_key, (int) $vars['expire_secs']);

        // Set response
        $res = [
            'uuid' => $user->getUuid(), 
            'access_token' => $session->getId(), 
            'expires_at' => $session->getExpiresAt(), 
            'refresh_token' => $refresh_token, 
            'refresh_expires_at' => (time() + (int) $vars['expire_secs'])
        ];

        // Return
        return $res;
    }

}


