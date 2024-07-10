<script>
    import { inertia } from "@inertiajs/svelte";
    export let event;

    let loadingImage = true;
    function format(isoDate) {
        return new Date(isoDate).toLocaleString();
    }
</script>

<article>
    <figure class:loading={loadingImage} style="--img: url({event.poster})">
        <img
            on:loadeddata={() => (loadingImage = false)}
            src={event.poster}
            alt="Poster for {event.name}"
        />
    </figure>
    <footer>
        <hgroup>
            <h4>{event.name}</h4>
            <time datetime={event.startTime}>
                <span
                    >{new Date(event.start_time).toLocaleDateString(undefined, {
                        month: "long",
                        day: "numeric",
                    })}</span
                >
                <span style="text-align: right;"
                    >{new Date(event.start_time).toLocaleTimeString(undefined, {
                        hour: "numeric",
                        minute: "numeric",
                        ampm: "short",
                    })}</span
                >
            </time>
        </hgroup>

        <nav style="justify-content: center">
            <ul>
                <li>
                    <a href="/events/{event.id}" class="outline" use:inertia
                        >View Details</a
                    >
                </li>
            </ul>
        </nav>
    </footer>
</article>

<style>
    img {
        height: 100%;
        margin: auto;
        transform-origin: center center;
        transform: scale(1);
    }
    figure {
        max-height: 50vh;
        aspect-ratio: 3/4;
        flex: auto;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
    }

    figure::before {
        content: "";
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        background-image: var(--img);
        background-size: cover;
        background-position: center;
        filter: blur(0.5em) brightness(0.5) saturate(0.5);
    }

    article {
        overflow: hidden;
        transition: transform 0.2s;
        padding: 0;
        display: flex;
        flex-direction: column;
    }
    article:hover {
        transform: scale(1.05);
        z-index: 10;
    }

    footer {
        position: relative;
        margin: 0;
    }

    footer::before {
        content: "";
        position: absolute;
        bottom: 100%;
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

    h4 {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    menu {
        margin: 0;
    }

    menu li {
        flex: auto;
    }

    menu button,
    menu [role="button"] {
        width: 100%;
        margin-bottom: 0;
    }

    footer time {
        display: flex;
        justify-content: space-between;
    }
</style>
