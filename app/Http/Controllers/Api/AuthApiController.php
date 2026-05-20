<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthApiController extends Controller
{
    /** POST /api/v1/auth/login */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
            'device'   => 'nullable|string|max:100',
        ]);

        // Sin scope de tenant — el login opera antes de que el tenant sea conocido
        $user = User::withoutGlobalScope('tenant')->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }
        if (! $user->activo)               return response()->json(['message' => 'Cuenta desactivada.'], 403);
        if ($user->pendiente_aprobacion)   return response()->json(['message' => 'Cuenta pendiente de aprobación.'], 403);

        $token = $user->createToken($request->device ?? 'app-mobile')->plainTextToken;
        $role  = $user->roles->first()?->name ?? 'Usuario';

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'        => $user->id,
                'name'      => $user->name,
                'apellidos' => $user->apellidos,
                'email'     => $user->email,
                'role'      => $role,
                'avatar'    => $this->avatar($user),
            ],
        ]);
    }

    /** POST /api/v1/auth/logout */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['ok' => true]);
    }

    /** GET /api/v1/auth/me */
    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'id'        => $user->id,
            'name'      => $user->name,
            'apellidos' => $user->apellidos,
            'email'     => $user->email,
            'role'      => $user->roles->first()?->name ?? 'Usuario',
            'avatar'    => $this->avatar($user),
            'tenant_id' => $user->tenant_id,
        ]);
    }

    /** POST /api/v1/auth/refresh-token
     * Revoca el token actual y emite uno nuevo (útil tras expiración próxima).
     */
    public function refreshToken(Request $request)
    {
        $user    = $request->user();
        $device  = $request->user()->currentAccessToken()->name;

        $request->user()->currentAccessToken()->delete();
        $newToken = $user->createToken($device)->plainTextToken;

        return response()->json(['token' => $newToken]);
    }

    /** PATCH /api/v1/auth/profile */
    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'apellidos' => 'nullable|string|max:100',
            'telefono'  => 'nullable|string|max:20',
        ]);

        $user = $request->user();
        $user->update(array_filter($data, fn($v) => $v !== null));

        return response()->json([
            'id'        => $user->id,
            'name'      => $user->name,
            'apellidos' => $user->apellidos,
            'email'     => $user->email,
            'telefono'  => $user->telefono,
            'role'      => $user->roles->first()?->name ?? 'Usuario',
        ]);
    }

    /** PATCH /api/v1/auth/change-password */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password'      => 'required|string',
            'new_password'          => 'required|string|min:8|confirmed',
        ]);

        if (! Hash::check($request->current_password, $request->user()->password)) {
            return response()->json(['message' => 'La contraseña actual es incorrecta.'], 422);
        }

        $request->user()->update(['password' => Hash::make($request->new_password)]);
        return response()->json(['ok' => true]);
    }

    private function avatar(User $user): ?string
    {
        if ($user->hasRole('Docente') && $user->docente?->foto)
            return asset('storage/' . $user->docente->foto);
        if ($user->hasRole('Estudiante') && $user->estudiante?->foto)
            return asset('storage/' . $user->estudiante->foto);
        return null;
    }
}
