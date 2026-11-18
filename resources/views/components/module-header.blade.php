@props([
    'title',
    'icon',
    'iconBg' => '#059669',
    'iconShadow' => 'rgba(5, 150, 105, 0.25)',
    'titleColor' => '#0f172a',
    'breadcrumbs' => [],
    'bordered' => false,
    'headerClass' => '',
])

<div {{ $attributes->merge(['class' => trim('hsse-page-header ' . ($bordered ? 'hsse-page-header--bordered ' : '') . $headerClass)]) }}>
    <div class="hsse-header-brand">
        <div class="hsse-header-icon" style="background: {{ $iconBg }}; box-shadow: 0 4px 10px {{ $iconShadow }};" aria-hidden="true">
            <i class="{{ $icon }}"></i>
        </div>
        <div class="hsse-header-text">
            <h1 class="hsse-header-title" style="color: {{ $titleColor }};">{{ $title }}</h1>
            @if(!empty($breadcrumbs))
                <nav class="hsse-breadcrumb-nav" aria-label="Breadcrumb">
                    @foreach($breadcrumbs as $crumb)
                        @if(!$loop->first)
                            <span class="hsse-breadcrumb-sep" aria-hidden="true">&rsaquo;</span>
                        @endif
                        @if(!empty($crumb['url']) && !$loop->last)
                            <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                        @elseif($loop->last)
                            <span class="hsse-breadcrumb-current">{{ $crumb['label'] }}</span>
                        @else
                            <span>{{ $crumb['label'] }}</span>
                        @endif
                    @endforeach
                </nav>
            @endif
        </div>
    </div>

    @if(isset($actions) && trim($actions) !== '')
        <div class="hsse-header-actions">
            {{ $actions }}
        </div>
    @elseif(trim($slot) !== '')
        <div class="hsse-header-actions">
            {{ $slot }}
        </div>
    @endif
</div>
