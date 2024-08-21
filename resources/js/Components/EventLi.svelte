<script>
    import Icon from "@iconify/svelte";
    import { inertia } from "@inertiajs/svelte";
    import Tags from "./Tags.svelte";
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

<li class="event" id={event.id}>

        {#if event.poster}
            <figure style='display: inline-block; float: right;'>
                <img src={event.poster?.thumbnail_url} alt="Poster for {event.name}" />
                <figcaption style="display: none;">Poster for {event.name}</figcaption>
            </figure>
        {/if}
        <hgroup>
            <div class="time" style="color:#e5771e;">
                <time datetime={event.start_time}>
                    <strong class="day">{format(event.start_time)}</strong>
                    <span class="start">▪ {formatTime(event.start_time)}</span>
                </time>
                {#if event.end_time}
                    - <time datetime={event.end_time}>
                        <span class="end">{formatTime(event.end_time)}</span>
                    </time>
                {/if}
            </div>
            <h2 >
                <a use:inertia href="/events/{event.id}" style="color:inherit;"
                    >{event.name}</a
                >
            </h2>
            {#if event.venue}
                <div class="venue">
                    <Icon icon="mdi:alternate-email" />
                    {#if event.venue.link}
                        <a target="_blank" href={event.venue.link}
                            >{event.venue?.name}
                            <Icon icon="mdi:external-link" /></a
                        >
                    {:else}
                        {event.venue?.name}
                    {/if}
                </div>
            {/if}
        </hgroup>
        <section class="desc">
            {@html event.description}
        </section>

        {#if event.bands.length > 0}
            <div class="bands">
                <Icon icon="mdi:account-music" />
                <ul>
                    {#each event.bands as band}
                        <li>
                            <a use:inertia href="/bands/{band.id}"
                                >{band.name}</a
                            >
                        </li>
                    {/each}
                </ul>
            </div>
        {/if}

        {#if event.price?.length > 0}
            <ul class="price">
                {#each event.price as { label, price }}
                    <li>
                        <Icon icon="mdi:ticket" />
                        <strong>{label}</strong>: {price}
                    </li>
                {/each}
            </ul>
        {/if}
        {#if event.links?.length > 0}
            <nav>
                <ul>
                    {#each event.links as link}
                        <li><a href={link.url}>{link.name}</a></li>
                    {/each}
                </ul>
            </nav>
        {/if}
        <Tags tags={event.tags} listPage='/events'/>
</li>

<style>
    figure {
        float: right;
        margin: 0 0 .5em .5em;
        border: 2px solid var(--cmc-yellow);
        height: 16rem;
        border-radius: calc(var(--pico-border-radius) + .2em);
        overflow: hidden;
        padding: .2em;
        max-width: 40%;
    }
    img {
        border-radius: var(--pico-border-radius);
        height: 100%;
    }

    .desc {
        flex: 1 1 auto;
    }
    li.event {
        margin-bottom: 1rem;
        padding: 0.8rem 0;
        border-top: 4px solid var(--cmc-yellow);
        list-style: none;
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

    hgroup a h2 {
        margin-bottom: 0;
    }

    @media (max-width: 768px) {
        li {
            flex-direction: column;
        }
    }

    ul.price {
        padding: 0;
        margin: 0.5rem 0;
    }
    .price li {
        list-style: none;
    }

    .price li:not(:first-child) :global(.iconify) {
        visibility: hidden;
    }
</style>
