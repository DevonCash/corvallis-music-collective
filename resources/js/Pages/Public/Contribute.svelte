<script>
    import { inertia } from "@inertiajs/svelte";
    import Socials from "../../Components/Socials.svelte";
</script>

<main>
    <nav aria-label="breadcrumb">
        <ul>
            <li><a href="/">Home</a></li>
            <li>Contribute</li>
        </ul>
    </nav>
    <article>
        <header>
            <hgroup>
                <h1>Contribute to the Collective!</h1>
                <p>
                    The Corvallis Music Collective is a community-driven
                    organization, and there are many ways to get involved!
                </p>
            </hgroup>
        </header>
        <section>
            <h2>Spread the Word</h2>
            <p>
                The more people who know about the Corvallis Music Collective,
                the more we can do to support the local music community. If you
                believe in what we're doing, please help us spread the word!
            </p>
            <p>
                Share our website, our events, and our social media posts with
                your friends and family. The more people who know about us, the
                more we can do to support local music!
            </p>
            <nav
                style="justify-content:center; flex-direction: column; align-items: center; display: flex;"
            >
                <ul>
                    <Socials />
                </ul>
                <span>Follow us on social media!</span>
            </nav>
        </section>
        <section>
            <h2>Attend <a href="/events" use:inertia>Events</a></h2>
            <p>
                The best way to support us and the music community as a whole is
                to attend local music events! Music is a performance art, and
                that means the audience is the most important part of the show.
            </p>
            <p>
                Whatever your taste in music, there is something around here for
                everyone! Check out our <a href="/events" use:inertia
                    >events page</a
                > to see what's happening in the community.
            </p>
            <p>
                If you're playing or attending a local show, please consider
                <a use:inertia href="/events/submit">submitting it</a> to the community
                calendar!
            </p>
        </section>
        <section>
            <h2><a href="/contribute/volunteer" use:inertia>Volunteer</a></h2>
            <p>
                We are always looking for volunteers to help us with our events
                and projects. Whether you're a seasoned event planner or just
                looking to get involved, we have a place for you!
            </p>
            <p>
                If you're interested in volunteering, fill out our
                <a href="contribute/volunteer" use:inertia>volunteer form</a> and
                we'll get in touch with you soon!
            </p>
        </section>
        <section>
            <h2><a href="/contribute/donate" use:inertia>Donate</a></h2>
            <p>
                The Corvallis Music Collective is a non-profit organization, and
                we rely on donations to keep the lights on. If you believe in
                what we're doing and want to help us continue to support the
                local music community, please consider making a donation!
            </p>
            <p>
                If you own a local business, you can also sponsor one of our
                events or arrange an ongoing partnership with us. We are always
                looking for new ways to support the local music community!
            </p>
            <p>
                You can donate online through our
                <a href="contribute/donate" use:inertia>donation page</a>, or
                <a href="mailto:contact@corvmc.org">contact us directly</a> to make
                other arrangements.
            </p>
        </section>
    </article>
</main>
