<footer class='p-3'>
    <?php $user = auth()->user(); ?>
    <x-filament-panels::theme-switcher />

    <div class='flex items-center gap-3 mt-2'>
        <x-filament::avatar
            :src="$user->getFilamentAvatarUrl()"
            :alt="$user->name"
            size="lg"
        />
        <div class='grow'>
            <h3>{{ $user->name }}</h3>
            <p class='text-sm text-gray-400'>{{ $user->email }}</p>
        </div>
        <x-filament::icon-button
            icon="heroicon-o-adjustments-horizontal"
            size="xl"
            label="Edit Profile"
            tooltip="Edit Profile"
            tag="a"
            href="{{ route('filament.member.auth.profile') }}"
        >
        Profile
        </x-filament::icon-button>
    </div>

    <div class='flex gap-2 mt-3'>
    <form class='grow' action="{{ route('filament.member.auth.logout') }}" method="POST">
        @csrf
    <x-filament::button
        type='submit'
        class=' w-full'
        icon="heroicon-o-arrow-left-end-on-rectangle"
        outlined>

    Logout</x-filament::button>
    </form>
    @if($user->isAdmin() && filament()->getCurrentPanel()->getId() !== 'admin')
    <x-filament::button outlined tag='a' href="{{ route('filament.admin.pages.dashboard') }}" icon="heroicon-o-key" class='grow'>Admin</x-filament::button>
    @endif
    @if($user->isAdmin() && filament()->getCurrentPanel()->getId() !== 'member')
    <x-filament::button outlined tag='a' href="{{ route('filament.member.pages.dashboard') }}" icon="heroicon-o-user" class='grow'>Member</x-filament::button>
    @endif
</div>
</footer>
