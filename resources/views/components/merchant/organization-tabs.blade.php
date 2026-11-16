@props([
    'active',
    'branchesCount',
])

{{-- Contextual brand & branch tabs with 44px touch targets. --}}
@php
    $organizationTabs = [
        'branches' => ['label' => 'Daftar Cabang ('.$branchesCount.')', 'href' => route('merchant.branches.index')],
        'profile' => ['label' => 'Identitas Brand', 'href' => route('merchant.organization.edit')],
    ];
@endphp

<nav class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0" aria-label="Navigasi cabang dan brand">
    <div class="flex w-max items-center gap-2 border-b border-neutral-200 pb-3 sm:w-auto">
        @foreach ($organizationTabs as $tabKey => $tab)
            <a
                href="{{ $tab['href'] }}"
                @if ($active === $tabKey) aria-current="page" @endif
                class="inline-flex min-h-11 shrink-0 items-center whitespace-nowrap rounded-xl px-4 text-xs transition {{ $active === $tabKey ? 'border border-brand-200 bg-brand-50 font-bold text-brand-700' : 'font-semibold text-neutral-600 hover:bg-neutral-100 hover:text-neutral-950' }}"
            >
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</nav>
