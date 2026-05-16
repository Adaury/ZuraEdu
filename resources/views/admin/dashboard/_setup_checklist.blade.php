{{--
    Checklist de configuración post-onboarding.
    Se oculta cuando el admin lo descarta (POST dismiss-checklist) o cuando todo está completo.
    $checklist = ['pasos'=>[...], 'completados'=>N, 'total'=>5, 'porcentaje'=>%, 'todo_listo'=>bool]
--}}
@php
    $pct   = $checklist['porcentaje'];
    $comp  = $checklist['completados'];
    $total = $checklist['total'];
@endphp

<div id="setupChecklist" class="mb-4" style="border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(15,23,42,.08);border:1px solid #e2e8f0;">

    {{-- Header --}}
    <div style="background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 100%);padding:1.1rem 1.4rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
        <div style="flex:1;display:flex;align-items:center;gap:.85rem;">
            <div style="width:42px;height:42px;border-radius:12px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi bi-rocket-takeoff-fill" style="color:#fff;font-size:1.2rem;"></i>
            </div>
            <div>
                <div style="font-size:.95rem;font-weight:800;color:#fff;line-height:1.2;">Configura tu institución</div>
                <div style="font-size:.75rem;color:rgba(255,255,255,.7);margin-top:.1rem;">
                    {{ $comp }} de {{ $total }} pasos completados
                </div>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:.75rem;flex-shrink:0;">
            <div style="text-align:right;">
                <div style="font-size:1.4rem;font-weight:900;color:#fff;line-height:1;">{{ $pct }}%</div>
                <div style="font-size:.65rem;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.06em;">listo</div>
            </div>
            <button onclick="dismissChecklist()" title="Descartar"
                style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);color:rgba(255,255,255,.8);border-radius:8px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.85rem;padding:0;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    {{-- Barra de progreso --}}
    <div style="height:5px;background:#e2e8f0;">
        <div style="height:100%;width:{{ $pct }}%;background:linear-gradient(90deg,#10b981,#34d399);transition:width .4s ease;"></div>
    </div>

    {{-- Pasos --}}
    <div style="background:#fff;padding:.75rem 1rem;display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:.6rem;">
        @foreach($checklist['pasos'] as $paso)
        @php $done = $paso['done']; @endphp
        <div style="border-radius:12px;border:1.5px solid {{ $done ? '#d1fae5' : '#f1f5f9' }};background:{{ $done ? '#f0fdf4' : '#fafafa' }};padding:.85rem 1rem;display:flex;align-items:flex-start;gap:.75rem;position:relative;">

            {{-- Ícono --}}
            <div style="width:36px;height:36px;border-radius:10px;background:{{ $done ? '#d1fae5' : '#f1f5f9' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:.05rem;">
                @if($done)
                    <i class="bi bi-check-circle-fill" style="color:#059669;font-size:1.1rem;"></i>
                @else
                    <i class="bi {{ $paso['icon'] }}" style="color:{{ $paso['color'] }};font-size:1rem;"></i>
                @endif
            </div>

            {{-- Texto --}}
            <div style="flex:1;min-width:0;">
                <div style="font-size:.82rem;font-weight:700;color:{{ $done ? '#065f46' : '#374151' }};display:flex;align-items:center;gap:.35rem;">
                    {{ $paso['titulo'] }}
                    @if($done)
                    <span style="font-size:.65rem;background:#10b981;color:#fff;border-radius:99px;padding:.1rem .45rem;font-weight:700;">Listo</span>
                    @endif
                </div>
                <div style="font-size:.72rem;color:#64748b;margin-top:.15rem;line-height:1.4;">{{ $paso['desc'] }}</div>
                @if(! $done)
                <a href="{{ $paso['route'] }}"
                   style="display:inline-flex;align-items:center;gap:.3rem;margin-top:.5rem;font-size:.72rem;font-weight:700;color:{{ $paso['color'] }};text-decoration:none;">
                    {{ $paso['label'] }} <i class="bi bi-arrow-right"></i>
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Pie --}}
    <div style="background:#f8fafc;border-top:1px solid #f1f5f9;padding:.6rem 1.2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;">
        <span style="font-size:.73rem;color:#94a3b8;">
            <i class="bi bi-info-circle me-1"></i>
            Puedes descartar este panel y retomarlo cuando quieras desde Configuración.
        </span>
        <button onclick="dismissChecklist()"
            style="background:none;border:1px solid #e2e8f0;border-radius:8px;font-size:.73rem;color:#64748b;padding:.25rem .75rem;cursor:pointer;">
            Descartar
        </button>
    </div>
</div>

@push('scripts')
<script>
function dismissChecklist() {
    fetch('{{ route('admin.dashboard.dismiss-checklist') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            'Accept': 'application/json',
        },
    }).finally(function () {
        var el = document.getElementById('setupChecklist');
        if (el) {
            el.style.transition = 'opacity .3s, max-height .4s';
            el.style.opacity = '0';
            el.style.overflow = 'hidden';
            el.style.maxHeight = el.offsetHeight + 'px';
            setTimeout(function () { el.style.maxHeight = '0'; el.style.marginBottom = '0'; }, 10);
            setTimeout(function () { el.remove(); }, 450);
        }
    });
}
</script>
@endpush
