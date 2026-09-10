<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show()
    {
        return view('perfil.show', [
            'user'       => auth()->user(),
            'categorias' => \App\Models\Notificacion::CATEGORIAS,
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'apellidos' => 'nullable|string|max:100',
            'telefono'  => 'nullable|string|max:20',
            'email'     => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update($data);

        return redirect()->back()->with('success', 'Perfil actualizado correctamente.');
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = auth()->user();

        // Borrar foto anterior
        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $path = $request->file('photo')->store('profile-photos', 'public');
        $user->update(['profile_photo' => $path]);

        return redirect()->back()->with('success', 'Foto de perfil actualizada.');
    }

    public function deletePhoto()
    {
        $user = auth()->user();

        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
            $user->update(['profile_photo' => null]);
        }

        return redirect()->back()->with('success', 'Foto eliminada.');
    }

    public function notificacionesUpdate(Request $request)
    {
        $user  = auth()->user();
        $prefs = $user->notif_push_prefs ?? [];

        foreach (\App\Models\Notificacion::CATEGORIAS as $clave => $meta) {
            // Las categorías que la institución apagó se renderizan como
            // checkbox deshabilitado -- el navegador NO envía inputs
            // disabled. Si se escribieran igual, un guardado pondría en
            // false una preferencia que el usuario tenía en true, y que
            // volvería a aplicarse el día que el centro reactive la
            // categoría sin que el usuario lo haya pedido de nuevo.
            if (! \App\Services\NotificacionPreferenciaService::pushInstitucionActivoCategoria($clave)) {
                continue;
            }

            $prefs[$clave] = $request->boolean("push_{$clave}");
        }

        $user->notif_push_prefs = $prefs; // no está en $fillable: asignación directa
        $user->save();

        return redirect()->route('perfil.show')
            ->withFragment('notificaciones')
            ->with('success', 'Preferencias de notificaciones actualizadas.');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = auth()->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }

        $user->update([
            'password'            => Hash::make($request->password),
            'must_change_password'=> false,
        ]);

        return redirect()->back()->with('success', 'Contraseña actualizada correctamente.');
    }
}
