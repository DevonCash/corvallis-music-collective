<script>
    import Icon from "@iconify/svelte";
    import { inertia } from "@inertiajs/svelte";
    let scrollY;
</script>

<svelte:window bind:scrollY />
<div id="top"></div>
<a class:show={scrollY > 0} href="#top" role="button"
    ><Icon icon="mdi:arrow-up" /> Back to top</a
>
<header class="cmc">
    <img src="/logo.svg" alt="Corvallis Music Collective Logo" />
    <hgroup>
        <h1>Corvallis Music Collective</h1>

        <nav>
            <ul>
                <li><a use:inertia href="/">Home</a></li>
                <li><a use:inertia href="/posts">News</a></li>
                <li><a use:inertia href="/events">Events</a></li>
                <li><a use:inertia href="/donate">Donate</a></li>
                <li><a use:inertia href="/volunteer">Volunteer</a></li>
            </ul>

            <ul>
                <li>
                    <a
                        href="https://www.facebook.com/profile.php?id=61557301093883"
                        ><Icon icon="mdi:facebook" /></a
                    >
                </li>
                <li>
                    <a href="https://www.instagram.com/corvmc/">
                        <Icon icon="mdi:instagram" /></a
                    >
                </li>
                <li>
                    <a href="https://x.com/corvmc">
                        <Icon icon="mdi:twitter" />
                    </a>
                </li>
            </ul>
        </nav>
    </hgroup>
</header>
<slot {...$$props} />

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
</style>
