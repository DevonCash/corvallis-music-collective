<script>
    import Icon from '@iconify/svelte';
    import {inertia} from '@inertiajs/svelte';

    export let listPage = '/posts';
    export let tags = []
</script>
<div class="tags">
    <Icon icon="mdi:hashtag" />
    {#each tags as tag}
        <a use:inertia class='outline' role='button' href="{listPage}?tag={tag}">
            {tag}
        </a>
    {/each}
</div>

<style>
    .tags {
        display: flex;
        align-items: center;
        gap: 0.5em;
        margin-top: .5em;
    }
    .tags a[role='button'] {
        font-size: smaller;
        padding: 0.25rem 0.5rem;
        margin: 0;
    }

    .tags :global(.iconify) {
        color: var(--cmc-blue);
    }
</style>
