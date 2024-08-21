<script>
    export let post;
    import { inertia } from "@inertiajs/svelte";
    import Icon from "@iconify/svelte";
    import Tags from "./Tags.svelte";
    const format = (isoDate) =>
        new Date(isoDate).toLocaleString(undefined, {
            month: "long",
            day: "numeric",
            year: "numeric",
        });
</script>

<li>
    <hgroup>
        <time class="date" datetime={post.published_at}
            >{format(post.published_at)}</time
        >
        <h2>{post.title}</h2>
        <div>
            {#if post.authors.length}
                by {#each post.authors as author}
                    <a use:inertia href={author.url}>{author.name}</a>
                {/each}
            {/if}
        </div>
        <Tags tags={post.tags} listPage="/posts" />
    </hgroup>

    <div class="content">
        {@html post.excerpt}
    </div>

    <footer style="display: flex; justify-content: flex-end;">
        <a use:inertia href="/posts/{post.id}">Read More</a>
    </footer>
</li>

<style>
    li {
        list-style: none;
        border-top: 3px solid var(--cmc-yellow);
        padding: 0.5em 0;
        margin-bottom: 1em;
    }

    .tags {
        display: flex;
        align-items: center;
        gap: 0.5em;
    }
    .tags a[role="button"] {
        font-size: smaller;
        padding: 0.25rem 0.5rem;
        margin: 0;
    }

    time {
        color: #e5771e;
        font-weight: bolder;
    }
</style>
