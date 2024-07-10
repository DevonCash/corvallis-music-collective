<script>
    import { inertia } from "@inertiajs/svelte";
    import Icon from "@iconify/svelte";
    export let post;
    export let authors;
</script>

{@debug post}
<main>
    <div class="grid">
        <nav aria-label="breadcrumb">
            <ul>
                <li>
                    <a use:inertia href="/">Home</a>
                </li>
                <li>
                    <a use:inertia href="/posts">Posts</a>
                </li>
                <li
                    style="max-width: 10em; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;"
                >
                    {post.title}
                </li>
            </ul>
        </nav>
        <menu>
            <li>
                <button class="outline">
                    <Icon icon="mdi:share" />
                    <span>Share</span>
                </button>
            </li>
        </menu>
    </div>
    <article>
        <header>
            <hgroup>
                <h2>{post.title}</h2>
                <div class="grid">
                    <div class="tags">
                        {#each post.tags as tag}
                            <a use:inertia href="/posts?tag={tag}" class="tag"
                                >{tag}</a
                            >
                        {/each}
                    </div>
                    <div style="text-align: right;">
                        <Icon icon="material-symbols:calendar-month" />
                        <time
                            >{new Date(
                                post.published_at,
                            ).toLocaleDateString()}</time
                        >
                    </div>
                </div>
                {#if authors.length}
                    <div class="authors">
                        by
                        {#each authors as author, idx}
                            <span>{idx !== 0 && ", "}{author.name}</span>
                        {/each}
                    </div>
                {/if}
            </hgroup>
        </header>
        {@html post.content}
        {#if new Date(post.updated_at) > new Date(post.published_at)}
            <footer>
                <Icon icon="mdi:clock-time-four-outline" />
                <time>
                    Updated
                    {new Date(post.updated_at).toLocaleDateString()}</time
                >
            </footer>
        {/if}
    </article>
</main>
