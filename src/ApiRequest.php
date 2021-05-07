<?php
declare(strict_types = 1);

namespace Apex\Armor\ApiKeys;

use Apex\Armor\Armor;
use Apex\Armor\Auth\Operations\Password;
use Apex\Armor\Auth\{AuthSession, SessionManager};
use Apex\Armor\Enums\SessionStatus;
use Apex\Armor\User\Extra\LoginHistory;
use Apex\Armor\Interfaces\{ArmorUserInterface, AdapterInterface};
use Apex\Container\Di;
use Apex\Db\Interfaces\DbInterface;

/**
 * API request
 */
class ApiRequest
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
     * Authenticate
     */
    public function authenticate(string $api_key, string $api_secret):?ArmorUserInterface
    {

        // Get key
        if (!$row = $this->db->getRow("SELECT * FROM armor_keys WHERE public_key = %s AND algo = 'api'", $api_key)) { 
            return null;
        } elseif (!Password::verify($api_secret, $row['private_key'])) { 
            return null;
        }

        // Load user
        $user = $this->armor->getUuid($row['uuid']);
        if ($user->isActive() === false) { 
            return null;
        } elseif ($user->isDeleted() === true) { 
            return null;
        } elseif ($user->isFrozen() === true) { 
            return null;
        }

        // Return
        return $user;
    }

    /**
     * Login
     */
    public function login(string $api_key, string $api_secret, int $refresh_expire_secs = 5400):?array
    {

        // Authenticate
        if (!$user = $this->authenticate($api_key, $api_secret)) { 
            return null;
        }

        // Create session
        $manager = Di::make(SessionManager::class);
        $session = $manager->create($user, 'ok', $api_secret, false);
        $session->setAttribute('is_api', 'true');

        // Create refresh token
        $refresh = Di::make(RefreshToken::class);
        $refresh_token = $refresh->create($session, $refresh_expire_secs);

        // Set response
        $res = [
            'uuid' => $user->getUuid(), 
            'access_token' => $session->getId(), 
            'expires_at' => $session->getExpiresAt(), 
            'refresh_token' => $refresh_token, 
            'refresh_expires_at' => (time() + $refresh_expire_secs)
        ];

        // Return
        return $res;
    }

    /**
     * Lookup
     */
    public function lookup(string $access_token):?AuthSession
    {

        // Lookup session
        $manager = Di::make(SessionManager::class);
        if (!$session = $manager->get($access_token)) { 
            return null;
        }

        // Check is_api attribute
        if (!$is_api = $session->getAttribute('is_api')) { 
            return null;
        } elseif ($is_api != 'true') { 
            return null;
        }

        // Check for expired
        if (time() > $session->getExpiresAt()) { 
            $session->setStatus(SessionStatus::EXPIRED);
            $adapter = Di::get(AdapterInterface::class);
            $adapter->handleSessionStatus($session, SessionStatus::EXPIRED);
            return null;
        }

        // Add page request, if needed
        if ($session->getHistoryId() > 0) { 
            $history = Di::make(LoginHistory::class);
            $history->addPageRequest($session->getHistoryId());
        }
    Di::set(AuthSession::class, $session);

        // Return
        return $session;
    }

}


