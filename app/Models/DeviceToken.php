<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $table = 'device_tokens';

    protected $fillable = ['user_id', 'token', 'platform', 'last_used_at'];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Upsert: actualiza last_used_at si ya existe, crea si no. */
    public static function register(int $userId, string $token, string $platform = 'unknown'): void
    {
        static::updateOrCreate(
            ['user_id' => $userId, 'token' => $token],
            ['platform' => $platform, 'last_used_at' => now()],
        );
    }

    /** Elimina el token del usuario (al hacer logout). */
    public static function remove(int $userId, string $token): void
    {
        static::where('user_id', $userId)->where('token', $token)->delete();
    }

    /** Devuelve los tokens vigentes de un usuario. */
    public static function tokensDeUsuario(int $userId): array
    {
        return static::where('user_id', $userId)->pluck('token')->all();
    }
}
