<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\ApiKey;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingsService
{
    public function getSetting(string $key, $default = null)
    {
        return SystemSetting::get($key, $default);
    }

    public function setSetting(string $key, $value, string $type = 'string', ?string $description = null): SystemSetting
    {
        return SystemSetting::set($key, $value, $type, $description);
    }

    public function getAllSettings(): \Illuminate\Database\Eloquent\Collection
    {
        return SystemSetting::getAll();
    }

    public function deleteSetting(string $key): bool
    {
        return SystemSetting::where('key', $key)->delete() > 0;
    }

    public function initializeDefaults(): void
    {
        $defaults = [
            ['key' => 'app_name', 'value' => 'XenonOS', 'type' => 'string', 'description' => 'Application name'],
            ['key' => 'app_url', 'value' => config('app.url'), 'type' => 'string', 'description' => 'Application URL'],
            ['key' => 'default_language', 'value' => 'en', 'type' => 'string', 'description' => 'Default language'],
            ['key' => 'timezone', 'value' => 'UTC', 'type' => 'string', 'description' => 'Default timezone'],
            ['key' => 'date_format', 'value' => 'Y-m-d', 'type' => 'string', 'description' => 'Date format'],
            ['key' => 'time_format', 'value' => 'H:i:s', 'type' => 'string', 'description' => 'Time format'],
            ['key' => 'currency', 'value' => 'USD', 'type' => 'string', 'description' => 'Default currency'],
            ['key' => 'invoice_prefix', 'value' => 'INV-', 'type' => 'string', 'description' => 'Invoice number prefix'],
            ['key' => 'invoice_due_days', 'value' => '30', 'type' => 'integer', 'description' => 'Invoice due days'],
            ['key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'description' => 'Maintenance mode'],
        ];

        foreach ($defaults as $default) {
            SystemSetting::set(
                $default['key'],
                $default['value'],
                $default['type'],
                $default['description']
            );
        }
    }

    public function getApiKeys()
    {
        return ApiKey::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function createApiKey(string $name, ?array $permissions = null, ?\Carbon\Carbon $expiresAt = null): array
    {
        return ApiKey::createKey($name, $permissions, $expiresAt);
    }

    public function revokeApiKey(int $id): bool
    {
        $apiKey = ApiKey::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$apiKey) {
            return false;
        }

        $apiKey->revoke();
        return true;
    }

    public function validateApiKey(string $key): ?ApiKey
    {
        return ApiKey::validateKey($key);
    }

    public function getAccountSettings(): array
    {
        return [
            'app_name' => $this->getSetting('app_name', 'XenonOS'),
            'timezone' => $this->getSetting('timezone', 'UTC'),
            'date_format' => $this->getSetting('date_format', 'Y-m-d'),
            'time_format' => $this->getSetting('time_format', 'H:i:s'),
            'currency' => $this->getSetting('currency', 'USD'),
            'default_language' => $this->getSetting('default_language', 'en'),
        ];
    }

    public function getSecuritySettings(): array
    {
        return [
            'password_min_length' => $this->getSetting('password_min_length', 8),
            'session_timeout' => $this->getSetting('session_timeout', 120),
            'two_factor_enabled' => $this->getSetting('two_factor_enabled', false),
            'api_key_enabled' => $this->getSetting('api_key_enabled', true),
        ];
    }
}
