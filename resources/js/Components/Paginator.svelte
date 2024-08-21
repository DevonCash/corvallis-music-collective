<script>
    import { inertia } from "@inertiajs/svelte";
    export let links;
</script>

{#if links}
    {@const prev = links[0]}
    {@const next = links[links.length - 1]}
    {@const rest = links.slice(1, links.length - 1)}
    <div class="paginator">
        <a
            use:inertia
            role="button"
            class="outline"
            disabled={!prev.url || null}
            href={prev.url}>{@html prev.label}</a
        >
        <div>
            <div role="group">
                {#each rest as link}
                    <a
                        use:inertia
                        role="button"
                        href={link.url}
                        class:outline={!link.active}>{link.label}</a
                    >
                {/each}
            </div>
        </div>
        <a
            use:inertia
            role="button"
            class="outline"
            disabled={!next.url || null}
            href={next.url}>{@html next.label}</a
        >
    </div>
{/if}

<style>

    .paginator {
        margin: auto;
        display: grid;
        grid-template-columns: min-content 1fr min-content;
        justify-content: center;
        align-items: center;
        gap: 1rem;
    }

    .paginator a {
        white-space: nowrap;
        margin-bottom: 0;
    }

    .paginator div {
        flex: 0;
        margin-bottom: 0;
        margin: auto;
    }
</style>
