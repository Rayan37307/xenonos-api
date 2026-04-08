<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'key_prefix',
        'key_hash',
        'permissions',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function createKey(string $name, ?array $permissions = null, ?\Carbon\Carbon $expiresAt = null): array
    {
        $key = 'xen_' . bin2hex(random_bytes(32));
        $keyPrefix = substr($key, 0, 12);
        
        $apiKey = static::create([
            'user_id' => auth()->id(),
            'name' => $name,
            'key_prefix' => $keyPrefix,
            'key_hash' => Hash::make($key),
            'permissions' => $permissions,
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);

        return [
            'id' => $apiKey->id,
            'key' => $key,
            'name' => $name,
            'expires_at' => $expiresAt,
        ];
    }

    public static function validateKey(string $key): ?ApiKey
    {
        $keyPrefix = substr($key, 0, 12);
        $apiKey = static::where('key_prefix', $keyPrefix)
            ->where('is_active', true)
            ->first();

        if (!$apiKey) {
            return null;
        }

        if ($apiKey->expires_at && $apiKey->expires_at->isPast()) {
            return null;
        }

        if (!Hash::check($key, $apiKey->key_hash)) {
            return null;
        }

        $apiKey->last_used_at = now();
        $apiKey->save();

        return $apiKey;
    }

    public function revoke(): void
    {
        $this->is_active = false;
        $this->save();
    }
}
