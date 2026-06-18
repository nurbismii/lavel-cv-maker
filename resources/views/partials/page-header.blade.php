<div class="app-page-header">
    <div>
        @isset($eyebrow)
            <span class="app-page-eyebrow">{{ $eyebrow }}</span>
        @endisset

        <h1 class="app-page-title">{{ $title }}</h1>

        @isset($subtitle)
            <p class="app-page-subtitle">{{ $subtitle }}</p>
        @endisset
    </div>

    @isset($actions)
        <div class="app-page-actions">
            {{ $actions }}
        </div>
    @endisset
</div>
