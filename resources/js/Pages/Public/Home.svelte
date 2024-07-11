<script>
    import EventCard from "../../Components/EventCard.svelte";
    import PostCard from "../../Components/PostCard.svelte";
    import { inertia } from "@inertiajs/svelte";
    export let posts;
    export let events;
</script>

<main>
    <section class="grid" style="align-items: center;">
        <h2 class="mission">
            Building and connecting music communities around Corvallis
        </h2>
        <p>
            The Corvallis Music Collective is a community-driven organization
            that supports local music and musicians. We believe that music is a
            vital part of a healthy community, and we work to create
            opportunities for musicians and music appreciators of all ages and
            skills.
        </p>
    </section>
    <section class="events">
        <header class="grid">
            <hgroup>
                <h2>Upcoming Events</h2>
            </hgroup>
            <a href="/events">View All</a>
        </header>
        <div class="grid">
            {#each events as event}
                <EventCard {event} />
            {:else}
                <p>No events to display</p>
            {/each}
            {#if events.length < 3}
                <a href="/events" aria-label="Community Events" use:inertia>
                    <article style="text-align: center;">
                        <img
                            class="speaker-logo"
                            src="/logo.svg"
                            alt="Corvallis Music Collective Speaker Logo"
                        />
                        <p>
                            Looking for more local music? Check out our
                            <a href="/events" use:inertia>events page</a>
                            for our full community calendar!
                        </p>
                        <footer>
                            <h4>Community Events</h4>
                        </footer>
                    </article>
                </a>
            {/if}
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

    section header {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        border-bottom: 4px solid var(--cmc-yellow);
        margin-bottom: 1rem;
    }

    .events div.grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }

    .mission {
        font-size: xx-large;
        font-weight: bold;
        color: var(--cmc-blue);
    }

    a article {
        padding: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: space-between;
        transform: scale(1);
        transition: transform 0.3s;
    }

    a article p {
        margin: 0 1rem;
    }
    a article footer {
        margin: 0;
        position: relative;
        padding-left: 0;
        padding-right: 0;
        padding-bottom: 0;
        width: 100%;
    }
    a:hover article {
        transform: scale(1.05);
    }

    a:has(article) {
        text-decoration: none;
        color: inherit;
    }

    .speaker-logo {
        position: relative;
        width: 70%;
        left: 8%;
        margin: 2rem auto;
    }

    a article footer::before {
        content: "";
        position: absolute;
        top: -1px;
        left: 0;
        background: linear-gradient(
            to bottom,
            var(--cmc-blue) 0%,
            var(--cmc-blue) 33%,
            var(--cmc-yellow) 33%,
            var(--cmc-yellow) 66%,
            var(--cmc-red) 66%,
            var(--cmc-red) 100%
        );
        height: 0.5em;
        width: 100%;
    }
</style>
