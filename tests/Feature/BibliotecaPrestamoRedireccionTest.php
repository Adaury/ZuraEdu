<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Libro;
use App\Models\PrestamoBiblioteca;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Registrar un préstamo guardaba el préstamo y descontaba el ejemplar, pero la redirección apuntaba a una ruta
 * que no existe (`admin.biblioteca.prestamos`): el usuario veía un error 500 aunque la operación se había hecho.
 */
class BibliotecaPrestamoRedireccionTest extends TestCase
{
    use RefreshDatabase;

    public function test_prestar_un_libro_redirige_al_listado_de_prestamos(): void
    {
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $user = User::factory()->create(['activo' => true])->assignRole('Biblioteca');
        $libro = Libro::create(['titulo' => 'Don Quijote', 'autor' => 'Cervantes', 'categoria' => 'Literatura', 'cantidad_total' => 2, 'cantidad_disponible' => 2]);
        $est = Estudiante::factory()->create();

        $r = $this->actingAs($user)->post(route('admin.biblioteca.prestamos.store'), [
            'libro_id' => $libro->id, 'estudiante_id' => $est->id,
            'fecha_prestamo' => today()->toDateString(), 'fecha_vencimiento' => today()->addDays(7)->toDateString(),
        ]);

        $r->assertRedirect(route('admin.biblioteca.prestamos.index'));
        $this->assertSame(1, PrestamoBiblioteca::where('libro_id', $libro->id)->count());
        $this->assertSame(1, $libro->fresh()->cantidad_disponible);
    }
}
