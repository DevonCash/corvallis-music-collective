@php
    $isActive = request()->is('member*');
@endphp

<div class="-ml-6 -mt-8 -mb-4 px-2 pt-1 border-b border-gray-200 dark:border-gray-700">
    <a
        href="/member"
        @class([
            'flex items-center gap-2 rounded-lg px-2 py-2 text-sm transition',
            'hover:bg-gray-50 dark:hover:bg-white/5',
            'text-gray-700 dark:text-gray-200',
        ])
    >
        <x-filament::icon
            icon="heroicon-m-arrow-left"
            @class([
                'fi-tenant-menu-item-icon h-5 w-5 shrink-0',
                'text-gray-400 dark:text-gray-500',
            ])
        />
        <span class="fi-tenant-menu-item-label flex-1 truncate">
            Back to Member Panel
        </span>
    </a>
</div> 