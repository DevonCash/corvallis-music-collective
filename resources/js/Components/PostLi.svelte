<script>
    export let post;
    import { inertia } from "@inertiajs/svelte";

    const format = (isoDate) =>
        new Date(isoDate).toLocaleString(undefined, {
            month: "long",
            day: "numeric",
            year: "numeric",
        });
</script>

<li>
    <article>
        <hgroup>
            <h3>{post.title}</h3>
        </hgroup>
        <div style="margin-bottom: 1em;">
            <div>
                {#if post.authors.length}
                    By
                    {#each post.authors as author}
                        <a use:inertia href={author.url}>{author.name}</a>
                    {/each} on
                {/if}{format(post.published_at)}
            </div>
            <div class="tags">
                {#each post.tags as tag}
                    <a use:inertia href="/posts?tag={tag}" class="tag">{tag}</a>
                {/each}
            </div>
        </div>

        <div class="content">
            {@html post.excerpt}
        </div>
        <nav>
            <ul>
                <li><a use:inertia href="/posts/{post.id}">Full Article</a></li>
            </ul>
        </nav>
    </article>
</li>

<style>
    li {
        list-style: none;
    }
    article {
        border-radius: 0;
        border-left: 4px solid var(--cmc-yellow);
        box-shadow: none;
    }
</style>
