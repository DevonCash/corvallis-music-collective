<script>
    import { inertia } from "@inertiajs/svelte";
    import Icon from "@iconify/svelte";
    import Tags from '../../../Components/Tags.svelte';
    export let post;
    export let authors;

    const format = (isoDate) =>
        new Date(isoDate).toLocaleString(undefined, {
            month: "long",
            day: "numeric",
            year: "numeric",
        });
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
                <time class='date' datetime={post.published_at}
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
               <Tags tags={post.tags}/>
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
