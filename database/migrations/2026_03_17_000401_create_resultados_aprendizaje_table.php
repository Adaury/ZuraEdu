<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resultados_aprendizaje', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignatura_id')->constrained('asignaturas')->cascadeOnDelete();
            $table->unsignedTinyInteger('numero');   // RA1, RA2...
            $table->string('descripcion', 500);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['asignatura_id','numero']);
        });

        // Add ra1-ra10 columns to calificaciones for RA grades
        Schema::table('calificaciones', function (Blueprint $table) {
            for ($i = 1; $i <= 10; $i++) {
                $table->decimal("ra{$i}", 5, 2)->nullable()->after('examen');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_aprendizaje');
        Schema::table('calificaciones', function (Blueprint $table) {
            for ($i = 1; $i <= 10; $i++) {
                $table->dropColumn("ra{$i}");
            }
        });
    }
};
