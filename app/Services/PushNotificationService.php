<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private const EXPO_URL = 'https://exp.host/--/api/v2/push/send';

    /**
     * Envía push a todos los dispositivos registrados de un usuario.
     */
    public static function sendToUser(int $userId, string $title, string $body, array $data = []): void
    {
        $tokens = DeviceToken::tokensDeUsuario($userId);
        if (empty($tokens)) return;
        static::dispatch($tokens, $title, $body, $data);
    }

    /**
     * Envía push a múltiples usuarios.
     */
    public static function sendToUsers(array $userIds, string $title, string $body, array $data = []): void
    {
        if (empty($userIds)) return;

        $tokens = DeviceToken::whereIn('user_id', $userIds)->pluck('token')->all();
        if (empty($tokens)) return;
        static::dispatch($tokens, $title, $body, $data);
    }

    /**
     * Envía mensajes push a los tokens dados (en lotes de 100).
     */
    private static function dispatch(array $tokens, string $title, string $body, array $data = []): void
    {
        // Solo tokens Expo válidos
        $tokens = array_values(array_filter($tokens, fn($t) => str_starts_with($t, 'ExponentPushToken')));
        if (empty($tokens)) return;

        foreach (array_chunk($tokens, 100) as $chunk) {
            $messages = array_map(fn($token) => [
                'to'    => $token,
                'title' => $title,
                'body'  => $body,
                'data'  => $data,
                'sound' => 'default',
            ], $chunk);

            try {
                Http::withHeaders([
                    'Accept'          => 'application/json',
                    'Accept-Encoding' => 'gzip, deflate',
                    'Content-Type'    => 'application/json',
                ])->timeout(8)->post(self::EXPO_URL, $messages);
            } catch (\Throwable $e) {
                Log::warning('PushNotification failed', ['error' => $e->getMessage()]);
            }
        }
    }
}
