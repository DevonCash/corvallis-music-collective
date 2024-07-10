<script>
    import { inertia } from "@inertiajs/svelte";
    import Paginator from "../../../Components/Paginator.svelte";
    export let bands;
</script>

<header>
    <h1>Bands</h1>
</header>
{@debug bands}
<section class="section">
    <search>
        <form>
            <fieldset role="group">
                <input class="input" type="text" placeholder="Search bands" />
                <button class="button">Search</button>
            </fieldset>
        </form>
    </search>
    <div class="grid">
        {#each bands.data as band}
            <article>
                <header>{band.name}</header>
                <div class="content">
                    {band.description}
                </div>
                <footer>
                    <div class="inset" role="group">
                        <a role="button" use:inertia href={`/bands/${band.id}`}
                            >View</a
                        >
                        <a
                            role="button"
                            use:inertia
                            href={`/admin/bands/${band.id}/edit`}>Edit</a
                        >
                    </div>
                </footer>
            </article>
        {/each}
    </div>
    <Paginator links={bands.links} />
</section>

<style>
    .grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }

    article {
        display: flex;
        flex-direction: column;
    }
    article .content {
        flex: auto;
    }
</style>
