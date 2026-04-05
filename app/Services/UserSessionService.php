<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSession;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use UAParser\Parser;

class UserSessionService
{
    /**
     * Create a new session for user login.
     */
    public function createSession(User $user, string $token, Request $request): UserSession
    {
        // Mark all existing sessions as not current
        UserSession::where('user_id', $user->id)
            ->update(['is_current' => false]);

        // Parse user agent
        $uaParser = Parser::create();
        $result = $uaParser->parse($request->userAgent());

        // Detect device type
        $deviceType = $this->detectDeviceType($request->userAgent());

        // Get location data from IP
        $locationData = $this->getLocationFromIp($request->ip());

        // Generate device name
        $deviceName = $this->generateDeviceName($result->ua->family, $deviceType);

        return UserSession::create([
            'user_id' => $user->id,
            'token_id' => $this->getTokenId($token),
            'device_name' => $deviceName,
            'device_type' => $deviceType,
            'os_family' => $result->os->family,
            'browser' => $result->ua->family,
            'ip_address' => $request->ip(),
            'city' => $locationData['city'] ?? null,
            'region' => $locationData['region'] ?? null,
            'country' => $locationData['country'] ?? null,
            'user_agent' => $request->userAgent(),
            'is_current' => true,
            'last_active_at' => now(),
        ]);
    }

    /**
     * Update session last active time.
     */
    public function updateSessionActivity(string $token): ?UserSession
    {
        $tokenId = $this->getTokenId($token);
        $session = UserSession::where('token_id', $tokenId)->first();

        if ($session) {
            $session->update([
                'last_active_at' => now(),
            ]);
        }

        return $session;
    }

    /**
     * Get all sessions for a user.
     */
    public function getUserSessions(User $user): Collection
    {
        return UserSession::where('user_id', $user->id)
            ->orderBy('is_current', 'desc')
            ->orderBy('last_active_at', 'desc')
            ->get();
    }

    /**
     * Get current session.
     */
    public function getCurrentSession(User $user): ?UserSession
    {
        return UserSession::where('user_id', $user->id)
            ->where('is_current', true)
            ->first();
    }

    /**
     * Delete a specific session.
     */
    public function deleteSession(User $user, int $sessionId): bool
    {
        $session = UserSession::where('user_id', $user->id)
            ->where('id', $sessionId)
            ->first();

        if (!$session) {
            return false;
        }

        activity()
            ->causedBy($user)
            ->useLog('session')
            ->withProperties([
                'session_id' => $session->id,
                'device_name' => $session->device_name,
            ])
            ->log('session_revoked');

        // Revoke the Sanctum token
        PersonalAccessToken::where('id', $session->token_id)->delete();

        // Delete the session
        $session->delete();

        return true;
    }

    /**
     * Delete all sessions except current one.
     */
    public function deleteAllOtherSessions(User $user): int
    {
        $currentSession = $this->getCurrentSession($user);

        $query = UserSession::where('user_id', $user->id);

        if ($currentSession) {
            $query->where('id', '!=', $currentSession->id);
        }

        // Revoke all other Sanctum tokens
        $otherSessions = $query->get();
        foreach ($otherSessions as $session) {
            PersonalAccessToken::where('id', $session->token_id)->delete();
        }

        $deleted = $query->delete();

        if ($deleted > 0) {
            activity()
                ->causedBy($user)
                ->useLog('session')
                ->withProperties(['revoked_count' => $deleted])
                ->log('other_sessions_revoked');
        }

        // Mark current session as current again
        if ($currentSession) {
            $currentSession->update(['is_current' => true]);
        }

        return $deleted;
    }

    /**
     * Delete all sessions (logout from all devices).
     */
    public function deleteAllSessions(User $user): int
    {
        $count = UserSession::where('user_id', $user->id)->count();

        activity()
            ->causedBy($user)
            ->useLog('session')
            ->withProperties(['revoked_count' => $count])
            ->log('all_sessions_revoked');

        // Revoke all Sanctum tokens
        PersonalAccessToken::where('tokenable_id', $user->id)->delete();

        return UserSession::where('user_id', $user->id)->delete();
    }

    /**
     * Extract token ID from Sanctum token.
     */
    private function getTokenId(string $token): string
    {
        // Sanctum tokens are in format: id|hash
        // We need the ID part
        if (Str::contains($token, '|')) {
            return Str::before($token, '|');
        }

        // If it's a plain token, try to find the token
        $accessToken = PersonalAccessToken::findToken($token);
        return $accessToken ? (string) $accessToken->id : Str::random(16);
    }

    /**
     * Detect device type from user agent.
     */
    private function detectDeviceType(string $userAgent): string
    {
        $userAgent = strtolower($userAgent);

        if (Str::contains($userAgent, ['mobile', 'android', 'iphone', 'ipod', 'blackberry', 'windows phone'])) {
            return 'mobile';
        }

        if (Str::contains($userAgent, ['tablet', 'ipad', 'playbook', 'kindle', 'silk'])) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * Generate human-readable device name.
     */
    private function generateDeviceName(?string $browser, string $deviceType): string
    {
        $browser = $browser ?? 'Unknown Browser';

        $deviceNames = [
            'mobile' => 'Mobile Device',
            'tablet' => 'Tablet',
            'desktop' => 'Desktop',
        ];

        return $browser . ' on ' . ($deviceNames[$deviceType] ?? 'Device');
    }

    /**
     * Get location data from IP address.
     * Note: For production, use a service like ipapi.co, maxmind, or ipinfo.io
     */
    private function getLocationFromIp(?string $ip): array
    {
        if (!$ip || in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return [];
        }

        // Free IP geolocation using ipapi.co (rate limited)
        try {
            $response = file_get_contents("https://ipapi.co/{$ip}/json/");
            if ($response) {
                $data = json_decode($response, true);
                return [
                    'city' => $data['city'] ?? null,
                    'region' => $data['region'] ?? null,
                    'country' => $data['country_name'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            // Geolocation failed, return empty
        }

        return [];
    }
}
