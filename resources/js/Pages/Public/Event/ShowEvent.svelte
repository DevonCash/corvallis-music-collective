<script>
    import EventLi from "../../../Components/EventLi.svelte";
    export let event;
</script>

<main>
    <nav aria-label="breadcrumb">
        <ul>
            <li><a href="/">Home</a></li>
            <li><a href="/events">Events</a></li>
            <li>{event.name}</li>
        </ul>
    </nav>
    <EventLi {event} />
</main>
