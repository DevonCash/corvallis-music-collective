<div class='flex flex-col items-center p-4 gap-2'>
    {{auth()->user()->email}}

    <form action='/admin/logout' method="post">
        @csrf
        <button class='fi-btn ring-1 ring-gray-950/10 dark:ring-white/20 py-2 px-4'>
            <span class='fi-btn-label'>Sign Out</span></button>
    </form>
</div>
