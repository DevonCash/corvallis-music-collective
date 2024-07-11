<script>
    import { inertia } from "@inertiajs/svelte";
    import Icon from "@iconify/svelte";
    import Socials from "./Socials.svelte";
</script>

<footer>
    <div style="font-size: smaller;">
        <h2>Corvallis <br />Music <br />Collective</h2>
        <nav style="margin: 0;">
            <ul style="flex-direction: row;"><Socials /></ul>
        </nav>
        <div>
            <a style="white-space: nowrap;" href="mailto:contact@corvmc.org">
                <Icon icon="mdi:email" />
                contact@corvmc.org</a
            >
        </div>
    </div>
    <nav>
        <ul>
            <li><a use:inertia href="/">Home</a></li>
        </ul>

        <ul>
            <li><a use:inertia href="/events">Events</a></li>
            <li>
                <a use:inertia href="/events/submit">Submit Event</a>
            </li>
            <li>
                <a use:inertia href="/events/community-events"
                    >Submission Guidelines</a
                >
            </li>
        </ul>
        <ul>
            <li><a use:inertia href="/posts">Articles</a></li>
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

<style>
    footer {
        display: grid;
        grid-template-columns: min-content 1fr auto;
        align-items: flex-start;
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

    footer nav {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        margin: 0 2rem;
    }
    footer nav ul {
        flex-direction: column;
        align-items: flex-start;
    }
    footer nav :global(li) {
        padding: 0.5rem 1rem;
    }

    @media (max-width: 1024px) {
        footer {
            flex-direction: column;
            gap: 1rem;
            align-items: center;
        }

        footer h2 br {
            display: none;
        }
        footer img {
            transform: scaleX(1);
            left: 2rem;
        }
    }
    @media (max-width: 768px) {
        footer nav {
            grid-template-columns: 1fr;
            width: 100%;
        }
        footer nav ul {
            display: grid;
            grid-template-columns: repeat(auto-fit, 8rem);
            align-items: center;
            justify-content: center;
            text-align: center;
        }
    }
</style>
