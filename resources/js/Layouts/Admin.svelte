<script>
    import { inertia } from "@inertiajs/svelte";
</script>

<main class="grid" style="grid-template-columns: minmax(auto, 10rem) 1fr;">
    <aside>
        <nav>
            <ul>
                <li><a use:inertia href="/admin">Dashboard</a></li>
                <li><a use:inertia href="/admin/events">Events</a></li>
                <li><a use:inertia href="/admin/venues">Venues</a></li>
                <li><a use:inertia href="/admin/bands">Bands</a></li>
                <li><a use:inertia href="/admin/posts">Posts</a></li>
                <li><a use:inertia href="/admin/users">Users</a></li>
            </ul>
        </nav>
    </aside>
    <div>
        <slot />
    </div>
</main>
