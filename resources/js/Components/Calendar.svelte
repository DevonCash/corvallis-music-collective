<script>
    import { groupBy } from "lodash";
    import {
        startOfWeek,
        getWeeksInMonth,
        today as getToday,
        getLocalTimeZone,
    } from "@internationalized/date";

    export let month;
    export let events = [];
    console.log(month);

    let today = getToday(getLocalTimeZone());
    let byDay, weeks;
    $: {
        byDay =
            events.length &&
            groupBy(events, (event) => {
                const date = new Date(event.start_time);
                return date?.getDate();
            });

        const firstCalDay = startOfWeek(month, "en-US");
        const numWeeks = getWeeksInMonth(month, "en-US");
        weeks = Array(numWeeks).fill([]);
        for (let w = 0; w < weeks.length; w++) {
            weeks[w] = Array(7)
                .fill(0)
                .map((_, i) => firstCalDay.add({ weeks: w, days: i }));
        }
    }
</script>

{@debug today}
<table>
    <caption>
        <h3>
            {month.toDate(getLocalTimeZone()).toLocaleDateString("en-US", {
                month: "long",
                year: "numeric",
            })}
        </h3>
    </caption>
    <thead>
        <tr>
            {#each weeks[0] as day}
                <th style="text-align: center;"
                    >{day
                        .toDate(getLocalTimeZone())
                        .toLocaleDateString("en-US", {
                            weekday: "short",
                        })}</th
                >
            {/each}
        </tr>
    </thead>
    {#each weeks as week}
        <tr>
            {#each week as day}
                {@const inMonth = day.month === month.month}
                <td>
                    {#if inMonth}
                        {#if byDay && byDay[day.day]}
                            {@const first = byDay[day.day][0]}
                            <a
                                class="day"
                                class:secondary={today.compare(day) === 0}
                                class:outline={today.compare(day) > 0}
                                role="button"
                                href="#{first.id}"
                            >
                                {day.day}
                            </a>
                        {:else}
                            <p
                                class="day"
                                class:today={today.compare(day) === 0}
                                class:outOfMonth={!inMonth}
                            >
                                {day.day}
                            </p>
                        {/if}
                    {/if}
                </td>
            {/each}
        </tr>
    {/each}
</table>

<style>
    .day {
        font-size: inherit;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0em;

        margin: 0;
        border-radius: 0.5em;
        text-align: center;
        aspect-ratio: 1/1;
        height: 3em;
    }

    p.day.today {
        border: 1px solid currentColor;
    }

    th {
        padding: 0.5em 0;
    }

    td {
        padding: 0.2em;
    }
    table {
        width: unset;
    }
    :global(:root) {
        scroll-behavior: smooth;
    }
</style>
