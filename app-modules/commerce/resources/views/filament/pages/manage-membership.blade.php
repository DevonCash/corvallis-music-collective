<x-filament::page x-data="{tab: 'monthly'}">
    <x-filament::tabs label="Membership Tiers">
        <x-filament::tabs.item label="Monthly"
            alpine-active="tab === 'monthly'"
            x-on:click="tab = 'monthly'"
        >
            Monthly
        </x-filament::tabs.item>
        <x-filament::tabs.item label="Yearly"
            alpine-active="tab === 'yearly'"
            x-on:click="tab = 'yearly'"
        >
            Yearly
        </x-filament::tabs.item>
    </x-filament::tabs>
    <div class='flex gap-4' x-show="tab === 'monthly'">
        @foreach ($this->monthlyTiers as $tier)
        <x-commerce::membership-plan-card :tier="$tier">
            {{ $this->subscribeAction }}
        </x-commerce::membership-plan-card>
        @endforeach
    </div>

    <div class='flex gap-4' x-show="tab === 'yearly'">
        {{-- @foreach ($this->yearlyTiers as $tier)
        <x-commerce::membership-plan-card :tier="$tier">
            {{ $this->subscribeAction($tier) }}
        </x-commerce::membership-plan-card>
        @endforeach --}}
    </div>
</x-filament::page> 