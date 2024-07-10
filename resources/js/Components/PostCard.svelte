<script>
    import { inertia } from "@inertiajs/svelte";
    export let post;
</script>

<article>
    <header>
        <h3>{post.title}</h3>
    </header>
    <div class="content">
        {@html post.excerpt}
    </div>
    <footer>
        <a use:inertia href={post.url}>Read More</a>
    </footer>
</article>
