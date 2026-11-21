@props([
    'title' => '',
    'value' => '0',
    'icon' => null,
    'description' => null,
    'trend' => null,
    'trendDirection' => 'up',
    'tone' => 'brand',
])

{{-- Deprecated alias: renders x-stat-tile so every KPI shares one visual language. Use x-stat-tile in new code. --}}
<x-stat-tile
    :label="$title"
    :value="$value"
    :hint="$description"
    :icon="$icon"
    :trend="$trend"
    :trend-direction="$trendDirection"
    :tone="$tone"
    {{ $attributes }}
/>
