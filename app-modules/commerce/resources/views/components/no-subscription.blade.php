@php
    $component = $component ?? null;
@endphp

<div {!! $component ? $component->getExtraAttributeBag()->toHtml() : '' !!} class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">{{ $heading }}</h3>
    </div>

    <div class="p-4">
        <div class="flex items-start">
            <x-filament::icon
                name="heroicon-o-information-circle"
                class="h-5 w-5 text-gray-400 mr-2 flex-shrink-0 mt-0.5"
            />
            <p class="text-gray-600">
                You don't have an active subscription yet. Choose a plan below to get started.
            </p>
        </div>
    </div>
</div> 