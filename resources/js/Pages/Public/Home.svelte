<script>
    import EventCard from "../../Components/EventCard.svelte";
    import PostCard from "../../Components/PostCard.svelte";

    export let posts;
    export let events;
</script>

<main>
    <section>
        <p class="mission">
            Building and connecting music communities around Corvallis
        </p>
        <section class="grid"></section>
    </section>
    <section class="events">
        <header class="grid">
            <h2>Upcoming Events</h2>
            <a href="/events">View All</a>
        </header>
        <div class="grid">
            {#each events as event}
                <EventCard {event} />
            {:else}
                <p>No events to display</p>
            {/each}
        </div>
    </section>
    <section class="news">
        <header>
            <h2>News</h2>
        </header>
        <div class="grid">
            {#each posts as post}
                <PostCard {post} />
            {:else}
                <p>No posts to display</p>
            {/each}
        </div>
    </section>
</main>

<style>
    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .events div.grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }

    .mission {
        font-size: xx-large;
        font-weight: bold;
        color: var(--cmc-blue);
    }
</style>
