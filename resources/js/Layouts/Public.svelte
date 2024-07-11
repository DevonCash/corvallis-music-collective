<script>
    import Icon from "@iconify/svelte";
    import Socials from "../Components/Socials.svelte";
    import { inertia } from "@inertiajs/svelte";
    let scrollY;
</script>

<svelte:window bind:scrollY />
<div id="top"></div>

<header class="cmc">
    <img src="/logo.svg" alt="Corvallis Music Collective Logo" />
    <hgroup>
        <h1>Corvallis Music Collective</h1>

        <nav>
            <ul>
                <li><a use:inertia href="/">Home</a></li>
                <li><a use:inertia href="/events">Events</a></li>
                <li><a use:inertia href="/posts">Articles</a></li>
                <li><a use:inertia href="/contribute">Contribute</a></li>
            </ul>

            <ul>
                <Socials />
                <li>
                    <a href="/contribute/donate">
                        <Icon icon="mdi:heart" />
                        Donate
                    </a>
                </li>
            </ul>
        </nav>
    </hgroup>
</header>
<slot {...$$props} />
<footer>
    <h2>Corvallis<br />Music<br />Collective</h2>
    <nav>
        <ul><Socials /></ul>

        <ul>
            <li><a use:inertia href="/">Home</a></li>
        </ul>
        <ul>
            <li><a use:inertia href="/posts">News</a></li>
        </ul>
        <ul>
            <li><a use:inertia href="/events">Events</a></li>
            <li>
                <a use:inertia href="/events/submit">Submit Event</a>
            </li>
            <li>
                <a use:inertia href="/events/community-events">Events</a>
            </li>
        </ul>
        <ul>
            <li><a use:inertia href="/contribute">Contribute</a></li>
            <li><a use:inertia href="/contribute/donate">Donate</a></li>
            <li>
                <a use:inertia href="/contribute/volunteer">Volunteer</a>
            </li>
        </ul>
    </nav>
    <img src="/logo.svg" alt="Corvallis Music Collective Logo" />
</footer>
<a class:show={scrollY > 0} href="#top" role="button"
    ><Icon icon="mdi:arrow-up" /> Back to top</a
>

<style>
    header {
        position: relative;
    }
    header::before {
        position: absolute;
        content: "";
        background: inherit;
        top: 0;
        left: calc((100vw - 100%) / -2);
        width: 100vw;
        height: 100%;
        z-index: -1;
    }

    header img {
        position: relative;
        top: -1rem;
        height: 7rem;
    }
    header {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    header hgroup {
        flex: auto;
    }

    header::after {
        content: "";
        position: absolute;
        left: calc((100vw - 100%) / -2);
        bottom: 0;
        width: 100vw;
        height: 1.5rem;
        background: linear-gradient(
            to bottom,
            var(--cmc-blue) 0%,
            var(--cmc-blue) 33%,
            var(--cmc-yellow) 33%,
            var(--cmc-yellow) 66%,
            var(--cmc-red) 66%,
            var(--cmc-red) 100%
        );
    }

    @media (prefers-color-scheme: light) {
        header h1 {
            color: var(--cmc-blue);
        }
    }

    @media (max-width: 768px) {
        header {
            flex-direction: column;
            gap: 1rem;
            align-items: center;
            justify-content: center;
        }
        header img {
            position: relative;
            right: -40px;
            top: 0;
        }

        header hgroup {
            text-align: center;
        }

        header nav {
            justify-content: center;
            flex-direction: column;
            align-items: center;
        }
    }

    a[href="#top"] {
        position: fixed;
        bottom: 1rem;
        right: 1rem;
        transform: translateX(calc(100% + 2rem));
        transition: transform 0.1s;
        z-index: 20;
    }

    a[href="#top"].show {
        transform: translateX(0);
    }

    footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    footer img {
        position: relative;
        height: 7rem;
        opacity: 0.5;
        filter: saturate(0.5);
        transform: scaleX(-1);
    }

    footer h2 {
        opacity: 0.5;
        margin: 0;
    }
    footer nav ul {
        flex-direction: column;
        align-items: flex-start;
    }
    footer nav :global(li) {
        padding: 0.5rem 1rem;
        white-space: nowrap;
    }
</style>
