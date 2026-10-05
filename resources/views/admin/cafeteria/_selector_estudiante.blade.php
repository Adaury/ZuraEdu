{{--
    Buscador de estudiante con autocompletado. Antes cada formulario cargaba TODOS los estudiantes activos en un <select> (tres veces por
    pantalla: con ~5.000 estudiantes eran megas de HTML). Ahora se escribe 2 o más letras y se consulta al servidor (máx. 15 resultados).
    Envía `estudiante_id`; el servidor lo vuelve a comprobar contra el colegio (nunca se fía del navegador).
--}}
<div x-data="{
        q: '', res: [], sel: null, abierto: false, buscando: false, t: null,
        buscar() {
            clearTimeout(this.t); this.sel = null;
            if (this.q.trim().length < 2) { this.res = []; this.abierto = false; return; }
            this.t = setTimeout(async () => {
                this.buscando = true;
                try {
                    const r = await fetch('{{ route('admin.cafeteria.estudiantes.buscar') }}?q=' + encodeURIComponent(this.q.trim()), { headers: { Accept: 'application/json' } });
                    this.res = r.ok ? await r.json() : [];
                } catch (e) { this.res = []; }
                this.buscando = false; this.abierto = true;
            }, 250);
        },
        elegir(e) { this.sel = e; this.q = e.nombre; this.abierto = false; }
     }"
     x-effect="$refs.texto && $refs.texto.setCustomValidity(sel ? '' : 'Busca y elige un estudiante de la lista')"
     @click.outside="abierto = false"
     class="relative">
    <input type="hidden" name="estudiante_id" :value="sel ? sel.id : ''">
    <input type="text" x-ref="texto" x-model="q" @input="buscar()" @focus="if (res.length) abierto = true" required
           autocomplete="off" placeholder="Escribe nombre, apellido o matrícula…" aria-label="Estudiante"
           class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
    <ul x-show="abierto && res.length" x-cloak role="listbox"
        class="absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto text-sm">
        <template x-for="e in res" :key="e.id">
            <li role="option" @click="elegir(e)" class="px-3 py-2 cursor-pointer hover:bg-blue-50 flex justify-between gap-2">
                <span x-text="e.nombre"></span>
                <span class="text-slate-400 text-xs shrink-0" x-text="e.matricula || ''"></span>
            </li>
        </template>
    </ul>
    <p x-show="abierto && !res.length && !buscando" x-cloak class="mt-1 text-xs text-slate-500">Sin resultados. Prueba con otra parte del nombre o la matrícula.</p>
    <p x-show="sel" x-cloak class="mt-1 text-xs text-emerald-700"><i class="bi bi-check-circle-fill"></i> Estudiante elegido</p>
</div>
