<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    public function __construct(private SettingsService $settingsService)
    {
    }

    public function index()
    {
        $settings = $this->settingsService->getAllSettings();

        return response()->json(['settings' => $settings]);
    }

    public function show(string $key)
    {
        $value = $this->settingsService->getSetting($key);

        return response()->json(['key' => $key, 'value' => $value]);
    }

    public function update(Request $request, string $key)
    {
        $validated = $request->validate([
            'value' => 'required',
            'type' => 'nullable|string|in:string,integer,boolean,json',
            'description' => 'nullable|string',
        ]);

        $setting = $this->settingsService->setSetting(
            $key,
            $validated['value'],
            $validated['type'] ?? 'string',
            $validated['description'] ?? null
        );

        return response()->json(['message' => 'Setting updated', 'setting' => $setting]);
    }

    public function delete(string $key)
    {
        $deleted = $this->settingsService->deleteSetting($key);

        if (!$deleted) {
            throw ValidationException::withMessages(['message' => 'Setting not found']);
        }

        return response()->json(['message' => 'Setting deleted']);
    }

    public function accountSettings()
    {
        $settings = $this->settingsService->getAccountSettings();

        return response()->json(['settings' => $settings]);
    }

    public function updateAccountSettings(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'nullable|string|max:255',
            'timezone' => 'nullable|string',
            'date_format' => 'nullable|string',
            'time_format' => 'nullable|string',
            'currency' => 'nullable|string|size:3',
            'default_language' => 'nullable|string|size:2',
        ]);

        foreach ($validated as $key => $value) {
            $this->settingsService->setSetting($key, $value, 'string');
        }

        return response()->json(['message' => 'Account settings updated']);
    }

    public function securitySettings()
    {
        $settings = $this->settingsService->getSecuritySettings();

        return response()->json(['settings' => $settings]);
    }

    public function apiKeys()
    {
        $keys = $this->settingsService->getApiKeys();

        return response()->json(['api_keys' => $keys]);
    }

    public function createApiKey(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'expires_at' => 'nullable|date',
        ]);

        $keyData = $this->settingsService->createApiKey(
            $validated['name'],
            $validated['permissions'] ?? null,
            $validated['expires_at'] ? \Carbon\Carbon::parse($validated['expires_at']) : null
        );

        return response()->json([
            'message' => 'API key created',
            'api_key' => $keyData,
        ]);
    }

    public function revokeApiKey(int $id)
    {
        $revoked = $this->settingsService->revokeApiKey($id);

        if (!$revoked) {
            throw ValidationException::withMessages(['message' => 'API key not found']);
        }

        return response()->json(['message' => 'API key revoked']);
    }
}
