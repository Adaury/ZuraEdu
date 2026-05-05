<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $defaults = [
        // Hero section
        ['clave' => 'hp_hero_titulo',       'valor' => 'Sistema de Gestión Escolar', 'tipo' => 'string'],
        ['clave' => 'hp_hero_subtitulo',     'valor' => 'Plataforma integral para la gestión académica, técnica y administrativa de tu institución educativa.', 'tipo' => 'string'],
        ['clave' => 'hp_hero_btn_texto',     'valor' => 'Iniciar Sesión', 'tipo' => 'string'],
        ['clave' => 'hp_hero_btn2_texto',    'valor' => 'Ver Demo', 'tipo' => 'string'],
        ['clave' => 'hp_hero_visible',       'valor' => '1', 'tipo' => 'boolean'],
        // About / Institution
        ['clave' => 'hp_about_titulo',       'valor' => 'Sobre Nuestra Institución', 'tipo' => 'string'],
        ['clave' => 'hp_about_texto',        'valor' => 'Somos un politécnico comprometido con la formación integral de nuestros estudiantes, ofreciendo educación de calidad en las áreas académica y técnica.', 'tipo' => 'string'],
        ['clave' => 'hp_about_visible',      'valor' => '1', 'tipo' => 'boolean'],
        // Stats
        ['clave' => 'hp_stat1_numero',       'valor' => '500+', 'tipo' => 'string'],
        ['clave' => 'hp_stat1_label',        'valor' => 'Estudiantes', 'tipo' => 'string'],
        ['clave' => 'hp_stat2_numero',       'valor' => '40+', 'tipo' => 'string'],
        ['clave' => 'hp_stat2_label',        'valor' => 'Docentes', 'tipo' => 'string'],
        ['clave' => 'hp_stat3_numero',       'valor' => '5', 'tipo' => 'string'],
        ['clave' => 'hp_stat3_label',        'valor' => 'Especialidades', 'tipo' => 'string'],
        ['clave' => 'hp_stat4_numero',       'valor' => '15+', 'tipo' => 'string'],
        ['clave' => 'hp_stat4_label',        'valor' => 'Años de Trayectoria', 'tipo' => 'string'],
        ['clave' => 'hp_stats_visible',      'valor' => '1', 'tipo' => 'boolean'],
        // Features section
        ['clave' => 'hp_features_titulo',    'valor' => '¿Por qué elegirnos?', 'tipo' => 'string'],
        ['clave' => 'hp_features_visible',   'valor' => '1', 'tipo' => 'boolean'],
        // Contact / Footer
        ['clave' => 'hp_contacto_direccion', 'valor' => '', 'tipo' => 'string'],
        ['clave' => 'hp_contacto_telefono',  'valor' => '', 'tipo' => 'string'],
        ['clave' => 'hp_contacto_email',     'valor' => '', 'tipo' => 'string'],
        ['clave' => 'hp_contacto_visible',   'valor' => '1', 'tipo' => 'boolean'],
        // Social media
        ['clave' => 'hp_social_facebook',    'valor' => '', 'tipo' => 'string'],
        ['clave' => 'hp_social_instagram',   'valor' => '', 'tipo' => 'string'],
        ['clave' => 'hp_social_twitter',     'valor' => '', 'tipo' => 'string'],
        // Logo / Branding
        ['clave' => 'hp_logo_path',          'valor' => '', 'tipo' => 'string'],
        ['clave' => 'hp_color_primario',     'valor' => '#1e3a6e', 'tipo' => 'string'],
        ['clave' => 'hp_color_secundario',   'valor' => '#2563eb', 'tipo' => 'string'],
    ];

    public function up(): void
    {
        foreach ($this->defaults as $row) {
            DB::table('config_institucional')->insertOrIgnore([
                'clave'      => $row['clave'],
                'valor'      => $row['valor'],
                'tipo'       => $row['tipo'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $claves = array_column($this->defaults, 'clave');
        DB::table('config_institucional')->whereIn('clave', $claves)->delete();
    }
};
