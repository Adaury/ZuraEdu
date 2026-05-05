<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUsuarioRequest;
use App\Http\Requests\Admin\UpdateUsuarioRequest;
use App\Mail\UsuarioAprobado;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::orderBy('name')->get();
        $query = User::with('roles')->orderBy('name');

        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(fn($q) => $q->where('name', 'like', "%$b%")
                ->orWhere('apellidos', 'like', "%$b%")
                ->orWhere('email', 'like', "%$b%"));
        }
        if ($request->filled('rol')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $request->rol));
        }

        $usuarios = $query->paginate(20)->withQueryString();
        $buscar   = $request->buscar ?? '';
        $rolFiltro = $request->rol ?? '';

        return view('admin.usuarios.index', compact('usuarios', 'roles', 'buscar', 'rolFiltro'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();
        return view('admin.usuarios.create', compact('roles'));
    }

    public function store(StoreUsuarioRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name'      => $data['name'],
            'apellidos' => $data['apellidos'] ?? null,
            'email'     => $data['email'],
            'telefono'  => $data['telefono'] ?? null,
            'password'  => Hash::make($data['password']),
            'activo'    => true,
        ]);

        $user->assignRole($data['role']);

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario)
    {
        $roles = Role::orderBy('name')->get();
        return view('admin.usuarios.edit', compact('usuario', 'roles'));
    }

    public function update(UpdateUsuarioRequest $request, User $usuario)
    {
        $data = $request->validated();

        $update = [
            'name'      => $data['name'],
            'apellidos' => $data['apellidos'] ?? null,
            'email'     => $data['email'],
            'telefono'  => $data['telefono'] ?? null,
            'activo'    => $request->boolean('activo'),
        ];

        if (! empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $usuario->update($update);
        $usuario->syncRoles([$data['role']]);

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $usuario)
    {
        if ($usuario->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }
        $usuario->delete();
        return back()->with('success', 'Usuario eliminado.');
    }

    // ── Excel de usuarios ─────────────────────────────────────────────────
    public function listaExcel(Request $request)
    {
        $query = User::with('roles')->orderBy('name');

        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(fn($q) => $q->where('name', 'like', "%$b%")
                ->orWhere('apellidos', 'like', "%$b%")
                ->orWhere('email', 'like', "%$b%"));
        }
        if ($request->filled('rol')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $request->rol));
        }

        $usuarios = $query->get();

        $ss    = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Usuarios');

        $hdrStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'DIRECTORIO DE USUARIOS — ' . now()->format('d/m/Y'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1')->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['#', 'Nombre', 'Apellidos', 'Email', 'Rol(es)', 'Estado'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '2', $h);
        }
        $sheet->getStyle('A2:F2')->applyFromArray($hdrStyle);

        foreach ($usuarios as $i => $usr) {
            $row   = $i + 3;
            $roles = $usr->getRoleNames()->implode(', ');

            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $usr->name ?? '');
            $sheet->setCellValue("C{$row}", $usr->apellidos ?? '');
            $sheet->setCellValue("D{$row}", $usr->email ?? '');
            $sheet->setCellValue("E{$row}", $roles);
            $sheet->setCellValue("F{$row}", $usr->activo ? 'Activo' : 'Inactivo');

            if (!$usr->activo) {
                $sheet->getStyle("A{$row}:F{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('fee2e2');
            } elseif ($i % 2 === 1) {
                $sheet->getStyle("A{$row}:F{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', 'F') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        $sheet->freezePane('A3');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'usr_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'usuarios_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function listaPdf(Request $request)
    {
        $query = User::with('roles')->orderBy('name');

        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(fn($q) => $q->where('name', 'like', "%$b%")
                ->orWhere('apellidos', 'like', "%$b%")
                ->orWhere('email', 'like', "%$b%"));
        }
        if ($request->filled('rol')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $request->rol));
        }

        $usuarios = $query->get();
        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.usuarios.lista_pdf', compact('usuarios', 'inst'))
            ->setPaper('letter', 'portrait');

        return $pdf->download('usuarios_' . now()->format('Ymd') . '.pdf');
    }

    public function toggleActivo(User $usuario)
    {
        if ($usuario->id === auth()->id()) {
            return response()->json(['error' => 'No puedes desactivar tu propio usuario.'], 422);
        }
        $usuario->update(['activo' => ! $usuario->activo]);
        return response()->json(['activo' => $usuario->activo]);
    }

    // ── Registro / Aprobación ─────────────────────────────────────────────

    public function pendientes(Request $request)
    {
        $usuarios = User::with('roles')
            ->where('pendiente_aprobacion', true)
            ->orderBy('created_at', 'asc')
            ->get();

        return view('admin.usuarios.pendientes', compact('usuarios'));
    }

    public function aprobar(User $usuario)
    {
        $usuario->update([
            'activo'               => true,
            'pendiente_aprobacion' => false,
            'motivo_rechazo'       => null,
        ]);

        Cache::forget('usuarios_pendientes_count');

        // Notificación interna al usuario aprobado
        try {
            \App\Models\Notificacion::enviar(
                $usuario->id,
                'general',
                '¡Acceso aprobado!',
                'Tu solicitud de acceso al sistema ha sido aprobada. Ya puedes iniciar sesión.',
            );
        } catch (\Throwable) {}

        // Notificar al usuario por email
        try {
            Mail::to($usuario->email)->queue(new UsuarioAprobado($usuario));
        } catch (\Exception $e) {
            // No interrumpir el flujo si el email falla
        }

        return back()->with('success', "Usuario {$usuario->name} aprobado correctamente. Se envió notificación por correo.");
    }

    public function rechazar(Request $request, User $usuario)
    {
        $request->validate([
            'motivo' => 'nullable|string|max:500',
        ]);

        // We delete the user; optionally you could keep them with a rejected flag
        $nombre = $usuario->nombre_completo;
        $usuario->delete();

        Cache::forget('usuarios_pendientes_count');
        return back()->with('success', "Solicitud de {$nombre} rechazada y eliminada.");
    }

    // ── Reset de contraseña (admin) ───────────────────────────────────────
    public function resetPassword(Request $request, User $usuario)
    {
        $data = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $usuario->update([
            'password'             => Hash::make($data['password']),
            'must_change_password' => true,   // Forza al usuario a cambiarla en el próximo login
        ]);

        return back()->with('success', "Contraseña de {$usuario->nombre_completo} restablecida. El usuario deberá cambiarla al iniciar sesión.");
    }
}
