<x-filament-panels::page>
    @include('commerce::components.membership-card')
    <x-filament::section compact>
        <x-slot name="heading">
            <h2>Log out</h2>
        </x-slot>
        <div class='flex justify-between align-center'>
            <p>Log out of your account</p>
            {{ $this->logoutAction() }}
        </div>
    </x-filament::section>
    <x-filament::section color="danger" compact>
        <x-slot name="heading">
            <h2>Delete Account</h2>
        </x-slot>
        <div class='flex justify-between align-center'>
            <p>Permanently delete your account</p>
            {{ $this->deleteAccountAction() }}
        </div>
    </x-filament::section>
</x-filament-panels::page>
