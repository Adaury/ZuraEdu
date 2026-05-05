<?php

namespace Database\Seeders;

use App\Models\Comunicado;
use App\Models\Grupo;
use App\Models\SchoolYear;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class ComunicadosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        if (! $tenant) { $this->command->error('No hay tenant.'); return; }
        app()->instance('tenant', $tenant);

        $admin = User::where('email', 'admin@sge.test')->first()
            ?? User::first();

        if (! $admin) { $this->command->error('No hay usuario admin.'); return; }

        $this->command->info('📢 Creando comunicados de prueba...');

        $primerGrupo = Grupo::withoutGlobalScope('tenant')->first();

        $comunicados = [
            [
                'titulo'             => 'Bienvenida al Año Escolar 2024-2025',
                'cuerpo'             => '<p>Estimada comunidad educativa:</p><p>Con mucho orgullo les damos la bienvenida al nuevo año escolar. Este año trabajaremos juntos para lograr la excelencia académica y el desarrollo integral de nuestros estudiantes.</p><p>El inicio de clases será el lunes 7 de octubre. Recuerden traer todos los materiales escolares indicados en la lista entregada durante la matrícula.</p><p><strong>¡Bienvenidos!</strong></p>',
                'tipo_destinatarios' => 'todos',
                'published_at'       => now()->subDays(60),
                'activo'             => true,
            ],
            [
                'titulo'             => 'Reunión de Padres y Madres — Primer Período',
                'cuerpo'             => '<p>Se convoca a todos los padres, madres y tutores a la reunión del primer período académico.</p><ul><li><strong>Fecha:</strong> Viernes 15 de noviembre</li><li><strong>Hora:</strong> 5:00 PM</li><li><strong>Lugar:</strong> Auditorio del centro educativo</li></ul><p>Se presentarán los resultados del primer período y se abordarán temas de convivencia escolar.</p><p>Su asistencia es obligatoria.</p>',
                'tipo_destinatarios' => 'todos',
                'published_at'       => now()->subDays(45),
                'activo'             => true,
            ],
            [
                'titulo'             => 'Circular Docente: Entrega de Calificaciones P1',
                'cuerpo'             => '<p>Estimados docentes:</p><p>Se les recuerda que la <strong>fecha límite de entrega de calificaciones del Primer Período</strong> es el <strong>viernes 22 de noviembre</strong> a las 3:00 PM.</p><p>Deben ingresar todas las notas al sistema y verificar que los promedios sean correctos antes de esa fecha.</p><p>Cualquier corrección posterior requiere autorización de la coordinación.</p>',
                'tipo_destinatarios' => 'docentes',
                'published_at'       => now()->subDays(35),
                'activo'             => true,
            ],
            [
                'titulo'             => 'Suspensión de Clases — Día Feriado',
                'cuerpo'             => '<p>Les informamos que el próximo <strong>lunes 25 de noviembre</strong> no habrá clases por motivo de día festivo nacional.</p><p>Las actividades académicas se reanudarán el martes 26 de noviembre con normalidad.</p>',
                'tipo_destinatarios' => 'todos',
                'published_at'       => now()->subDays(28),
                'activo'             => true,
            ],
            [
                'titulo'             => 'Acto de Clausura del Primer Período',
                'cuerpo'             => '<p>Con gran entusiasmo les invitamos al <strong>Acto de Clausura del Primer Período Académico</strong>.</p><ul><li><strong>Fecha:</strong> Jueves 5 de diciembre</li><li><strong>Hora:</strong> 4:00 PM</li><li><strong>Dresscode:</strong> Uniforme de gala</li></ul><p>Se reconocerá a los estudiantes del cuadro de honor y se presentarán actuaciones artísticas de cada grado.</p>',
                'tipo_destinatarios' => 'todos',
                'published_at'       => now()->subDays(20),
                'activo'             => true,
            ],
            [
                'titulo'             => 'Inicio del Segundo Período',
                'cuerpo'             => '<p>Les comunicamos que el <strong>Segundo Período Académico</strong> inicia el lunes 9 de diciembre.</p><p>Se esperan los estudiantes en el plantel a las 7:30 AM.</p><p>Recuerden que este período es determinante para los promedios finales del año escolar.</p>',
                'tipo_destinatarios' => 'todos',
                'published_at'       => now()->subDays(14),
                'activo'             => true,
            ],
            [
                'titulo'             => 'Taller de Formación Docente',
                'cuerpo'             => '<p>Estimado personal docente:</p><p>El próximo sábado se llevará a cabo el <strong>Taller de Estrategias de Enseñanza con Tecnología</strong> en colaboración con el INAFOCAM.</p><ul><li><strong>Fecha:</strong> Sábado 14 de diciembre</li><li><strong>Hora:</strong> 9:00 AM – 12:00 PM</li><li><strong>Modalidad:</strong> Presencial</li></ul><p>La asistencia es obligatoria y generará horas de capacitación certificadas.</p>',
                'tipo_destinatarios' => 'docentes',
                'published_at'       => now()->subDays(7),
                'activo'             => true,
            ],
            [
                'titulo'             => 'Recordatorio: Pago de Cuotas Escolares',
                'cuerpo'             => '<p>Estimados padres y tutores:</p><p>Se les recuerda que el <strong>pago de la cuota mensual vence el día 5 de cada mes</strong>.</p><p>Pueden realizar el pago:</p><ul><li>En la secretaría del centro, de 8:00 AM a 4:00 PM</li><li>Transferencia bancaria a la cuenta indicada en el contrato</li></ul><p>Estudiantes con más de 2 cuotas pendientes no podrán recibir su boletín.</p>',
                'tipo_destinatarios' => 'todos',
                'published_at'       => now()->subDays(3),
                'activo'             => true,
            ],
        ];

        $creados = 0;
        foreach ($comunicados as $data) {
            $existe = Comunicado::withoutGlobalScope('tenant')
                ->where('titulo', $data['titulo'])
                ->exists();

            if ($existe) continue;

            Comunicado::create(array_merge($data, [
                'autor_id'  => $admin->id,
                'tenant_id' => $tenant->id,
            ]));
            $creados++;
        }

        $this->command->line("   ✓ {$creados} comunicados creados.");
    }
}
