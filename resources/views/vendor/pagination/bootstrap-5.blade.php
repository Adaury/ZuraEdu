@if ($paginator->hasPages())
<nav aria-label="Navegación de páginas">
    <ul class="pagination pagination-sm mb-0 d-flex align-items-center gap-1">

        {{-- Anterior --}}
        @if ($paginator->onFirstPage())
            <li class="page-item disabled">
                <span class="page-link sge-page-nav">
                    <i class="bi bi-chevron-left"></i> Anterior
                </span>
            </li>
        @else
            <li class="page-item">
                <a class="page-link sge-page-nav" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                    <i class="bi bi-chevron-left"></i> Anterior
                </a>
            </li>
        @endif

        {{-- Números --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="page-item disabled">
                    <span class="page-link sge-page-num">{{ $element }}</span>
                </li>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="page-item active" aria-current="page">
                            <span class="page-link sge-page-num">{{ $page }}</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link sge-page-num" href="{{ $url }}">{{ $page }}</a>
                        </li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Siguiente --}}
        @if ($paginator->hasMorePages())
            <li class="page-item">
                <a class="page-link sge-page-nav" href="{{ $paginator->nextPageUrl() }}" rel="next">
                    Siguiente <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        @else
            <li class="page-item disabled">
                <span class="page-link sge-page-nav">
                    Siguiente <i class="bi bi-chevron-right"></i>
                </span>
            </li>
        @endif

    </ul>
</nav>

<style>
.sge-page-nav {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    font-size: .78rem;
    font-weight: 600;
    padding: .32rem .75rem;
    border-radius: 8px !important;
    border: 1px solid #e5e7eb !important;
    color: #374151;
    background: #f9fafb;
    line-height: 1.4;
    transition: background .15s, border-color .15s, color .15s;
}
.sge-page-nav i { font-size: .6rem; }
a.sge-page-nav:hover {
    background: #eff6ff;
    border-color: var(--primary, #1e3a6e) !important;
    color: var(--primary, #1e3a6e);
    text-decoration: none;
}
.page-item.disabled .sge-page-nav {
    opacity: .4;
    pointer-events: none;
}
.sge-page-num {
    font-size: .78rem;
    font-weight: 600;
    min-width: 32px;
    text-align: center;
    padding: .32rem .5rem;
    border-radius: 8px !important;
    border: 1px solid #e5e7eb !important;
    color: #374151;
    background: #f9fafb;
    line-height: 1.4;
    transition: background .15s, border-color .15s, color .15s;
}
a.sge-page-num:hover {
    background: #eff6ff;
    border-color: var(--primary, #1e3a6e) !important;
    color: var(--primary, #1e3a6e);
    text-decoration: none;
}
.page-item.active .sge-page-num {
    background: var(--primary, #1e3a6e) !important;
    border-color: var(--primary, #1e3a6e) !important;
    color: #fff;
}
.page-item.disabled .sge-page-num {
    opacity: .4;
    pointer-events: none;
}
</style>
@endif
