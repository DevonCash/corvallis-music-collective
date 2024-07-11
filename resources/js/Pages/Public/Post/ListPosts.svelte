<script>
    import { inertia } from "@inertiajs/svelte";
    import Paginator from "../../../Components/Paginator.svelte";
    import PostLi from "../../../Components/PostLi.svelte";
    export let posts;
</script>

<main>
    <hgroup>
        <h1>Articles</h1>
    </hgroup>
    <section>
        {#each posts.data as post}
            <PostLi {post} />
        {/each}
        <Paginator links={posts.links} />
    </section>
</main>
