<p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Números destacados (ej. "500+ Estudiantes"). Puedes agregar los que quieras.</p>

@include('admin.secciones.tipos._items', ['campos' => [
    ['key' => 'numero', 'label' => 'Número', 'placeholder' => 'Ej: 500+'],
    ['key' => 'label',  'label' => 'Descripción', 'placeholder' => 'Ej: Estudiantes'],
]])
