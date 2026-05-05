<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            // Institución
            'institution_type'        => 'publico',   // publico | privado
            // Landing page
            'landing_hero_title'      => 'Gestión Educativa Inteligente',
            'landing_hero_subtitle'   => 'Administra notas, asistencia, horarios y comunicación con padres en un solo lugar.',
            'landing_hero_badge'      => 'Plataforma Educativa',
            'landing_cta_primary'     => 'Iniciar Sesión',
            'landing_cta_demo'        => 'Ver Demo',
            'landing_stats_estudiantes' => '500+',
            'landing_stats_docentes'    => '30+',
            'landing_stats_asignaturas' => '20+',
            'landing_stats_asistencia'  => '98%',
            'landing_hero_image'      => null,
            'landing_enabled'         => '1',
            // Login
            'login_title'             => 'Iniciar Sesión',
            'login_subtitle'          => 'Accede a tu panel según tu rol',
            'login_allow_register'    => '1',
            'login_show_demo'         => '1',
            'login_primary_color'     => '#1e40af',
            'login_accent_color'      => '#10b981',
            // Módulos
            'module_payments'         => '0',
            'module_whatsapp'         => '0',
            'module_calendar'         => '1',
            'module_horarios'         => '1',
            'module_boletines'        => '1',
            'module_reportes'         => '1',
            // WhatsApp
            'whatsapp_provider'       => 'twilio',    // twilio | meta
            'whatsapp_account_sid'    => null,
            'whatsapp_auth_token'     => null,
            'whatsapp_from_number'    => null,
            'whatsapp_notify_absence' => '1',
            'whatsapp_notify_grades'  => '1',
            'whatsapp_notify_alerts'  => '1',
            // Pagos (solo privado)
            'payments_gateway'        => 'stripe',    // stripe | cardnet
            'payments_stripe_pk'      => null,
            'payments_stripe_sk'      => null,
            'payments_currency'       => 'DOP',
            'payments_concept'        => 'Cuota escolar mensual',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        $keys = [
            'institution_type', 'landing_hero_title', 'landing_hero_subtitle',
            'landing_hero_badge', 'landing_cta_primary', 'landing_cta_demo',
            'landing_stats_estudiantes', 'landing_stats_docentes',
            'landing_stats_asignaturas', 'landing_stats_asistencia',
            'landing_hero_image', 'landing_enabled',
            'login_title', 'login_subtitle', 'login_allow_register',
            'login_show_demo', 'login_primary_color', 'login_accent_color',
            'module_payments', 'module_whatsapp', 'module_calendar',
            'module_horarios', 'module_boletines', 'module_reportes',
            'whatsapp_provider', 'whatsapp_account_sid', 'whatsapp_auth_token',
            'whatsapp_from_number', 'whatsapp_notify_absence',
            'whatsapp_notify_grades', 'whatsapp_notify_alerts',
            'payments_gateway', 'payments_stripe_pk', 'payments_stripe_sk',
            'payments_currency', 'payments_concept',
        ];
        DB::table('system_settings')->whereIn('key', $keys)->delete();
    }
};
