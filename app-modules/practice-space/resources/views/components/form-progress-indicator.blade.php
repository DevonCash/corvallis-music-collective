@php
    // Calculate progress based on the component's method
    $progress = $getProgress();
    
    // Determine color based on progress
    $progressColor = match(true) {
        $progress >= 100 => 'success',
        $progress >= 75 => 'success',
        $progress >= 50 => 'warning',
        default => 'danger',
    };
@endphp

<div {{ $attributes->class(['fi-progress-indicator space-y-2 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10']) }}>
    @if ($shouldShowLabel())
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $getProgressLabel() }}
            </span>
            
            @if ($shouldShowPercentage())
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $progress }}%
                </span>
            @endif
        </div>
    @endif
    
    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
        <div 
            class="h-2 rounded-full transition-all duration-300 ease-in-out"
            style="width: {{ $progress }}%; background-color: var(--primary-{{ $progressColor }});"
        ></div>
    </div>
    
    @if (count($getRequiredFields()) > 0 && $progress < 100)
        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            <p>Required fields: {{ implode(', ', array_map(fn($field) => str_replace('data.', '', $field), $getRequiredFields())) }}</p>
        </div>
    @endif
</div> 