<script>
    import Icon from "@iconify/svelte";
    import Header from "../Components/PublicHeader.svelte";
    import Footer from "../Components/PublicFooter.svelte";
    let scrollY;
</script>

<svelte:window bind:scrollY />
<div id="top"></div>

<Header />
<slot {...$$props} />
<Footer />

<a class:show={scrollY > 0} href="#top" role="button"
    ><Icon icon="mdi:arrow-up" /> Back to top</a
>

<style>
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
