<script>
    import Icon from "@iconify/svelte";
    import { inertia } from "@inertiajs/svelte";
    export let event;

    function format(isoDate) {
        return new Date(isoDate).toLocaleString(undefined, {
            month: "long",
            day: "numeric",
        });
    }

    function formatTime(isoDate) {
        return new Date(isoDate).toLocaleTimeString(undefined, {
            hour: "numeric",
            minute: "numeric",
            ampm: "short",
        });
    }
</script>

<li class="event">
    <figure>
        <img src={event.poster} alt="Poster for {event.name}" />
    </figure>
    <div class="content">
        <h2 id={event.id}>{event.name}</h2>
        <div class="time">
            <Icon icon="mdi:calendar" />
            <time datetime={event.start_time}>
                <strong class="day">{format(event.start_time)}</strong>
                <span class="start">{formatTime(event.start_time)}</span>
            </time>
            {#if event.end_time}
                - <time datetime={event.end_time}>
                    <span class="end">{formatTime(event.end_time)}</span>
                </time>
            {/if}
        </div>
        <div class="venue">
            <Icon icon="mdi:map-marker" />
            <span>{event.venue.name}</span>
        </div>
        <div class="bands">
            <Icon icon="mdi:account-music" />
            <ul>
                {#each event.bands as band}
                    <li>
                        <a use:inertia href="/bands/{band.id}">{band.name}</a>
                    </li>
                {/each}
            </ul>
        </div>
        <nav>
            <ul>
                <li><a href="/events/{event.id}">Full Details</a></li>
                <li><a href="#">Get Tickets</a></li>
            </ul>
        </nav>
    </div>
</li>

<style>
    li.event {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
        align-items: center;
        padding: 0.5rem 0;
    }

    .content {
        flex: auto;
        padding: 1rem 0;
        border-bottom: 1px solid currentColor;
    }

    figure {
        aspect-ratio: 3/4;
        height: 16rem;
        border-radius: var(--pico-border-radius);
        overflow: hidden;
    }

    .bands ul {
        display: inline;
        padding: 0;
        list-style: none;
        gap: 0.5rem;
    }
    .bands li {
        display: inline;
        list-style: none;
    }
    .bands li:not(:last-child) a::after {
        content: ",";
    }

    @media (max-width: 768px) {
        li {
            flex-direction: column;
        }
    }
</style>
