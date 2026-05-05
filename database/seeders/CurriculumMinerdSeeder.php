<?php

namespace Database\Seeders;

use App\Models\{Asignatura, CompetenciaEspecifica, IndicadorLogro};
use Illuminate\Database\Seeder;

/**
 * Currículo oficial MINERD — Nivel Secundario
 * Fuente: Diseño Curricular Nivel Secundario (2016-revisado)
 */
class CurriculumMinerdSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Sembrando currículo MINERD...');

        // ──────────────────────────────────────────────────────────────────────
        // PRIMER CICLO (1ro–3ro) — Escala cualitativa 1-4
        // ──────────────────────────────────────────────────────────────────────
        $primerCiclo = [

            'Lengua Española' => [
                ['codigo'=>'CE1','nombre'=>'Comprensión lectora',
                 'ils'=>[
                    'Identifica el tema central y la idea principal de textos leídos.',
                    'Infiere información implícita a partir del contexto del texto.',
                    'Distingue hechos de opiniones en textos informativos y literarios.',
                 ]],
                ['codigo'=>'CE2','nombre'=>'Producción escrita',
                 'ils'=>[
                    'Organiza sus ideas con coherencia y cohesión al redactar textos.',
                    'Utiliza correctamente la ortografía y los signos de puntuación.',
                    'Produce textos creativos y funcionales con propósito comunicativo definido.',
                 ]],
                ['codigo'=>'CE3','nombre'=>'Comunicación oral',
                 'ils'=>[
                    'Expresa sus ideas con claridad, fluidez y respeto al turno conversacional.',
                    'Escucha activamente y responde con pertinencia a planteamientos orales.',
                 ]],
            ],

            'Matemáticas' => [
                ['codigo'=>'CE1','nombre'=>'Números y operaciones',
                 'ils'=>[
                    'Resuelve operaciones con números enteros, fracciones y decimales.',
                    'Aplica propiedades de las operaciones para simplificar cálculos.',
                    'Interpreta y utiliza la notación científica y porcentajes.',
                 ]],
                ['codigo'=>'CE2','nombre'=>'Álgebra y funciones',
                 'ils'=>[
                    'Plantea y resuelve ecuaciones e inecuaciones de primer grado.',
                    'Representa relaciones y funciones mediante tablas y gráficas.',
                 ]],
                ['codigo'=>'CE3','nombre'=>'Geometría y medida',
                 'ils'=>[
                    'Calcula perímetro, área y volumen de figuras y sólidos geométricos.',
                    'Aplica el teorema de Pitágoras en situaciones del entorno.',
                 ]],
                ['codigo'=>'CE4','nombre'=>'Estadística y probabilidad',
                 'ils'=>[
                    'Recopila, organiza e interpreta datos en tablas y gráficos estadísticos.',
                    'Calcula medidas de tendencia central (media, moda, mediana).',
                 ]],
            ],

            'Ciencias Naturales' => [
                ['codigo'=>'CE1','nombre'=>'Ser humano y salud',
                 'ils'=>[
                    'Describe la estructura y función de los sistemas del cuerpo humano.',
                    'Valora prácticas saludables relacionadas con alimentación e higiene.',
                 ]],
                ['codigo'=>'CE2','nombre'=>'Medio ambiente y ecosistemas',
                 'ils'=>[
                    'Identifica los componentes bióticos y abióticos de un ecosistema.',
                    'Analiza el impacto de la actividad humana sobre el equilibrio ecológico.',
                 ]],
                ['codigo'=>'CE3','nombre'=>'Materia, energía y fenómenos',
                 'ils'=>[
                    'Clasifica la materia según sus propiedades físicas y químicas.',
                    'Explica transformaciones de energía en situaciones cotidianas.',
                 ]],
            ],

            'Ciencias Sociales' => [
                ['codigo'=>'CE1','nombre'=>'Identidad y ciudadanía',
                 'ils'=>[
                    'Valora la identidad cultural dominicana y el patrimonio histórico.',
                    'Reconoce derechos y deberes ciudadanos consagrados en la Constitución.',
                 ]],
                ['codigo'=>'CE2','nombre'=>'Historia y geografía',
                 'ils'=>[
                    'Ubica y secuencia hechos históricos dominicanos y latinoamericanos.',
                    'Interpreta mapas, gráficos y fuentes para analizar el espacio geográfico.',
                 ]],
                ['codigo'=>'CE3','nombre'=>'Economía y sociedad',
                 'ils'=>[
                    'Explica los factores de producción y su relación con el desarrollo.',
                    'Analiza problemas sociales contemporáneos y propone soluciones.',
                 ]],
            ],

            'Inglés' => [
                ['codigo'=>'CE1','nombre'=>'Comprensión auditiva y lectora',
                 'ils'=>[
                    'Comprende textos orales y escritos en inglés sobre temas cotidianos.',
                    'Infiere el significado de vocabulario desconocido por contexto.',
                 ]],
                ['codigo'=>'CE2','nombre'=>'Producción oral y escrita',
                 'ils'=>[
                    'Se expresa oralmente en inglés en situaciones comunicativas básicas.',
                    'Redacta textos sencillos con estructura y vocabulario apropiados.',
                 ]],
            ],

            'Educación Física' => [
                ['codigo'=>'CE1','nombre'=>'Capacidades motrices',
                 'ils'=>[
                    'Demuestra habilidades motrices básicas y coordinación corporal.',
                    'Ejecuta movimientos rítmicos con control y expresión corporal.',
                 ]],
                ['codigo'=>'CE2','nombre'=>'Salud y actividad física',
                 'ils'=>[
                    'Participa activamente en actividades físicas y deportivas.',
                    'Valora el ejercicio físico como elemento de salud integral.',
                 ]],
            ],

            'Educación Artística' => [
                ['codigo'=>'CE1','nombre'=>'Expresión visual y plástica',
                 'ils'=>[
                    'Produce obras visuales aplicando elementos del lenguaje plástico.',
                    'Utiliza diversas técnicas artísticas con creatividad y sentido estético.',
                 ]],
                ['codigo'=>'CE2','nombre'=>'Expresión musical y dramática',
                 'ils'=>[
                    'Interpreta ritmos y melodías del folclore dominicano e iberoamericano.',
                    'Participa en expresiones dramáticas y teatrales del entorno cultural.',
                 ]],
            ],

            'Formación Integral' => [
                ['codigo'=>'CE1','nombre'=>'Valores y convivencia',
                 'ils'=>[
                    'Practica valores de respeto, solidaridad y responsabilidad en la escuela.',
                    'Resuelve conflictos de manera pacífica y constructiva.',
                 ]],
                ['codigo'=>'CE2','nombre'=>'Dimensión espiritual y ética',
                 'ils'=>[
                    'Reflexiona sobre principios éticos universales desde diversas tradiciones.',
                    'Valora la vida, la dignidad humana y el bien común.',
                 ]],
            ],
        ];

        $this->sembrarCiclo($primerCiclo, 'primer_ciclo');

        // ──────────────────────────────────────────────────────────────────────
        // SEGUNDO CICLO (4to–6to) — Calificación numérica 0-100
        // ──────────────────────────────────────────────────────────────────────
        $segundoCiclo = [

            'Lengua Española' => [
                ['codigo'=>'CE1','nombre'=>'Comprensión e interpretación textual',
                 'ils'=>[
                    'Analiza críticamente textos literarios, periodísticos y académicos.',
                    'Interpreta recursos literarios (metáfora, ironía, intertextualidad).',
                    'Valora la literatura dominicana e hispanoamericana como expresión cultural.',
                 ]],
                ['codigo'=>'CE2','nombre'=>'Producción discursiva',
                 'ils'=>[
                    'Redacta ensayos, informes y textos argumentativos con rigor académico.',
                    'Aplica normas de citación y referencia bibliográfica (APA básico).',
                 ]],
                ['codigo'=>'CE3','nombre'=>'Investigación lingüística',
                 'ils'=>[
                    'Describe fenómenos del español dominicano desde una perspectiva sociolingüística.',
                    'Utiliza herramientas digitales para la búsqueda y análisis de información.',
                 ]],
            ],

            'Matemáticas' => [
                ['codigo'=>'CE1','nombre'=>'Álgebra y trigonometría',
                 'ils'=>[
                    'Resuelve sistemas de ecuaciones lineales y cuadráticas.',
                    'Aplica razones trigonométricas en la resolución de problemas.',
                 ]],
                ['codigo'=>'CE2','nombre'=>'Cálculo e infinitésimos',
                 'ils'=>[
                    'Comprende el concepto de límite y continuidad de funciones.',
                    'Calcula derivadas e integrales básicas con sus aplicaciones.',
                 ]],
                ['codigo'=>'CE3','nombre'=>'Probabilidad y estadística inferencial',
                 'ils'=>[
                    'Aplica distribuciones de probabilidad en situaciones reales.',
                    'Interpreta resultados de muestras y realiza inferencias básicas.',
                 ]],
            ],

            'Ciencias Naturales' => [
                ['codigo'=>'CE1','nombre'=>'Química',
                 'ils'=>[
                    'Comprende la estructura atómica y el enlace químico.',
                    'Balancea ecuaciones y calcula estequiometría de reacciones.',
                 ]],
                ['codigo'=>'CE2','nombre'=>'Física',
                 'ils'=>[
                    'Aplica leyes del movimiento y principios de termodinámica.',
                    'Analiza fenómenos de electromagnetismo y óptica.',
                 ]],
                ['codigo'=>'CE3','nombre'=>'Biología',
                 'ils'=>[
                    'Explica procesos celulares: fotosíntesis, respiración y división celular.',
                    'Relaciona genética mendeliana con la variabilidad y evolución.',
                 ]],
            ],

            'Ciencias Sociales' => [
                ['codigo'=>'CE1','nombre'=>'Historia universal y dominicana',
                 'ils'=>[
                    'Analiza procesos históricos desde una perspectiva crítica y comparada.',
                    'Relaciona hechos históricos con el contexto económico y social.',
                 ]],
                ['codigo'=>'CE2','nombre'=>'Geografía política y económica',
                 'ils'=>[
                    'Interpreta la dinámica geopolítica del Caribe y América Latina.',
                    'Evalúa el modelo de desarrollo dominicano y sus retos.',
                 ]],
                ['codigo'=>'CE3','nombre'=>'Filosofía y ética',
                 'ils'=>[
                    'Analiza corrientes filosóficas y su influencia en el pensamiento contemporáneo.',
                    'Argumenta posiciones éticas sobre dilemas actuales.',
                 ]],
            ],

            'Inglés' => [
                ['codigo'=>'CE1','nombre'=>'Comprensión avanzada',
                 'ils'=>[
                    'Comprende textos académicos y técnicos en inglés (nivel B1–B2).',
                    'Analiza textos literarios breves en inglés con guía docente.',
                 ]],
                ['codigo'=>'CE2','nombre'=>'Comunicación oral y escrita avanzada',
                 'ils'=>[
                    'Participa en debates y presentaciones en inglés sobre temas de actualidad.',
                    'Redacta ensayos y correos formales en inglés con corrección lingüística.',
                 ]],
            ],
        ];

        $this->sembrarCiclo($segundoCiclo, 'segundo_ciclo');

        $this->command->info('✔ Currículo MINERD sembrado correctamente.');
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function sembrarCiclo(array $data, string $ciclo): void
    {
        foreach ($data as $nombreAsig => $competencias) {
            $asig = Asignatura::where('nombre', $nombreAsig)->first();
            if (!$asig) {
                $this->command->warn("  ⚠ Asignatura no encontrada: {$nombreAsig}");
                continue;
            }

            foreach ($competencias as $idx => $ceData) {
                $ce = CompetenciaEspecifica::firstOrCreate(
                    ['asignatura_id' => $asig->id, 'ciclo' => $ciclo, 'codigo' => $ceData['codigo']],
                    ['nombre' => $ceData['nombre'], 'orden' => $idx + 1, 'activo' => true]
                );

                foreach ($ceData['ils'] as $ilIdx => $ilDesc) {
                    IndicadorLogro::firstOrCreate(
                        ['competencia_id' => $ce->id, 'codigo' => 'IL' . ($ilIdx + 1)],
                        ['descripcion' => $ilDesc, 'orden' => $ilIdx + 1, 'activo' => true]
                    );
                }
            }

            $this->command->line("  → {$ciclo} | {$nombreAsig}: " . count($competencias) . " CE");
        }
    }
}
