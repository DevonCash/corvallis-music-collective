<script>
    import { inertia } from "@inertiajs/svelte";
    import Icon from "@iconify/svelte";
</script>

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
                <li>
                    <a style="white-space: nowrap" href="/contribute/donate">
                        <Icon icon="mdi:heart" />
                        Donate
                    </a>
                </li>
                <li>
                    <a href="/member/login">Join / Sign in</a>
                </li>
            </ul>
        </nav>
    </hgroup>
</header>

<style>
    header {
        position: relative;
        display: flex;
        align-items: center;
        gap: 1rem;
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
        background: var(--cmc-stripe);
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
</style>
