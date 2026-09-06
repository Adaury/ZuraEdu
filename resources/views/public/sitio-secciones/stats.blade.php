<section class="stats">
    <div class="section-inner">
        <div class="stats-grid">
            @foreach($config['stats'] as $stat)
            <div>
                <div class="num">{{ $stat['numero'] }}</div>
                <div class="lbl">{{ $stat['label'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>
