@php
    $component = $component ?? null;
    $error_message = $error_message ?? 'Unable to retrieve your current subscription details. Please try again later or contact support.';
    $error_details = $error_details ?? null;
    $heading = $heading ?? 'Subscription Information';
@endphp

<div {!! $component ? $component->getExtraAttributeBag()->toHtml() : '' !!} class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 dark:bg-gray-700 dark:border-gray-600">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $heading }}</h3>
    </div>

    <div class="p-4">
        <div class="flex items-start">
            <x-filament::icon
                name="heroicon-o-exclamation-circle"
                class="h-5 w-5 text-red-500 mr-2 flex-shrink-0 mt-0.5"
            />
            <div>
                <p class="text-red-600 dark:text-red-400">
                    {{ $error_message }}
                </p>
                
                @if($error_details)
                    <details class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        <summary class="cursor-pointer">Technical details</summary>
                        <pre class="mt-1 p-2 bg-gray-100 dark:bg-gray-700 rounded text-xs overflow-auto">{{ $error_details }}</pre>
                    </details>
                @endif
                
                <div class="mt-4">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        You can try the following:
                    </p>
                    <ul class="mt-2 text-sm text-gray-600 dark:text-gray-300 list-disc list-inside">
                        <li>Refresh the page</li>
                        <li>Clear your browser cache</li>
                        <li>Try again later</li>
                        <li>Contact support if the issue persists</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div> 