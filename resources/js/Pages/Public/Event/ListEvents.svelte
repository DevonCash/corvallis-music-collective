<script>
    import EventLi from "../../../Components/EventLi.svelte";
    import { CalendarDate } from "@internationalized/date";
    import { inertia } from "@inertiajs/svelte";
    import { groupBy } from "lodash";
    import Calendar from "../../../Components/Calendar.svelte";

    export let month;
    export let events = [];

    let thisMonth, lastMonth, nextMonth, upcoming, past;

    $: {
        const groups =
            events.length &&
            groupBy(events, (event) =>
                new Date(event.start_time) > new Date() ? "upcoming" : "past",
            );
        upcoming = groups.upcoming || [];
        past = groups.past || [];

        const [y, m] = month.split("-");
        thisMonth = new Date(y, m - 1);
        lastMonth = new Date(thisMonth.getFullYear(), thisMonth.getMonth() - 1);
        nextMonth = new Date(thisMonth.getFullYear(), thisMonth.getMonth() + 1);
    }

    function getLink(date) {
        if (!date) return;
        const year = date.getFullYear();
        const month = date.getMonth() + 1;
        return `/events?month=${year}-${month}`;
    }

    function format(date) {
        return date?.toLocaleDateString("en-US", {
            month: "long",
            year: "numeric",
        });
    }
</script>

{@debug events}

<main>
    <header>
        <hgroup>
            <h1>
                Events <time datetime={thisMonth.toISOString()}
                    >{format(thisMonth)}</time
                >
            </h1>
            <nav>
                <ul>
                    <li>
                        <a use:inertia href={getLink(lastMonth)}>
                            « {format(lastMonth)}
                        </a>
                    </li>
                    <li>
                        <a use:inertia href={getLink(nextMonth)}>
                            {format(nextMonth)} »
                        </a>
                    </li>
                </ul>
            </nav>
        </hgroup>
    </header>
    <aside style="font-size: small; ">
        <Calendar
            {events}
            month={new CalendarDate(
                thisMonth.getFullYear(),
                thisMonth.getMonth() + 1,
                1,
            )}
        />
    </aside>

    <div class="content">
        {#if upcoming.length > 0}
            <section>
                <h3>Upcoming</h3>
                {#each upcoming as event}
                    <EventLi {event} />
                {/each}
            </section>
        {/if}
        {#if past.length > 0}
            <section>
                <h3>Past</h3>
                {#each past as event}
                    <EventLi {event} />
                {/each}
            </section>
        {/if}
        <!-- {#if events?.length === 0}
            <p>No events found for {format(thisMonth)}</p>
        {/if} -->
    </div>
</main>

<style>
    h1 time {
        color: var(--pico-primary);
        white-space: nowrap;
    }

    main {
        max-width: unset !important;
        display: grid;
        gap: 1rem;
        padding: 1.5rem 2.5rem !important;
        grid-template-columns: 1fr min-content;
        grid-template-areas: "header aside" "section aside" "section aside";
    }

    header {
        grid-area: header;
    }
    .content {
        grid-area: section;
    }
    aside {
        grid-area: aside;
        margin-bottom: 1rem;
    }

    aside :global(table) {
        position: sticky;
        top: 1rem;
    }

    @media (max-width: 1024px) {
        main {
            grid-template-columns: 1fr;
            grid-template-areas: "header" "aside" "section";
            align-items: center;
            padding: 1rem !important;
        }
        aside {
            grid-row: unset;
        }
        aside :global(table) {
            position: static;
            margin: auto;
            font-size: larger;
        }
    }
</style>
