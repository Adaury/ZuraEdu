@props(['items' => []])
@if(count($items) > 1)
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:.82rem;background:transparent;padding:0;">
        @foreach($items as $item)
            @if($loop->last)
                <li class="breadcrumb-item active" aria-current="page">
                    {{ $item['label'] }}
                </li>
            @else
                <li class="breadcrumb-item">
                    <a href="{{ $item['url'] }}" class="text-decoration-none" style="color:var(--primary);">
                        {{ $item['label'] }}
                    </a>
                </li>
            @endif
        @endforeach
    </ol>
</nav>
@endif
