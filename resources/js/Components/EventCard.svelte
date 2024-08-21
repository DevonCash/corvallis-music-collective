<script>
    import Icon from "@iconify/svelte";
    import { inertia } from "@inertiajs/svelte";
    export let event;

    let loadingImage = true;
    function format(isoDate) {
        return new Date(isoDate).toLocaleString();
    }
</script>

{@debug event}
<a href="/events/{event.id}" aria-label={event.name}>
    <article>
        <figure class:loading={loadingImage} style="--img: url({event.poster})">
            <img
                on:loadeddata={() => (loadingImage = false)}
                src={event.poster?.thumbnail_url}
                alt="Poster for {event.name}"
            />
            <figcaption style="display: none;">
                Poster for {event.name}
            </figcaption>
        </figure>
        <footer>
            <h4>{event.name}</h4>
        </footer>
    </article>
</a>

<style>
    a:has(article) {
        text-decoration: none;
        color: inherit;
    }

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
        margin: 0;
    }
    article:hover {
        transform: scale(1.05);
        z-index: 10;
    }

    footer {
        text-align: center;
        position: relative;
        margin: 0;
    }

    footer::before {
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

    h4 {
        color: var(--cmc-blue);
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>
