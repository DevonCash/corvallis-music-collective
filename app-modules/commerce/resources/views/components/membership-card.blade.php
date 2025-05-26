<x-filament::section compact>
     @php($user = Auth::user())
     <x-slot name="headerEnd">
         {{-- <x-filament::button color="primary" tag="a" :href="$user->billingPortalUrl(route('filament.member.pages.membership'))">
                Manage
            </x-filament::button> --}}
     </x-slot>
     @if ($user->subscribed())
         <x-slot name="heading">
             <h2>Current Membership: {{ $user->subscription()->name }}</h2>
         </x-slot>
         <p>Thank you for being a member!</p>
     @else
         <x-slot name="heading">
             <h2>Current Membership: Free Member</h2>
         </x-slot>
         <p>Join our supporting members to unlock additional benefits!</p>
     @endif
 </x-filament::section>
