<div class="space-y-2">
    <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $progress }}% Complete
        </span>
    </div>
    
    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
        <div 
            class="h-2 rounded-full transition-all duration-300 ease-in-out"
            style="width: {{ $progress }}%; background-color: var(--primary-{{ $color }});"
        ></div>
    </div>
    
    @if (!empty($incompleteFields))
        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            <p class="font-medium">Missing required fields:</p>
            <ul class="mt-1 list-disc list-inside">
                @foreach ($incompleteFields as $field)
                    <li>{{ $field }}</li>
                @endforeach
            </ul>
        </div>
    @else
        <div class="mt-1 text-xs text-success-500 dark:text-success-400">
            <p>All required fields are completed!</p>
        </div>
    @endif
</div> 