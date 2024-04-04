@extends('layouts.bare', ['title' => config('app.name')])

@section('body')

    <main class=' page'>
        <section class='hero' >
            <!-- <div class='hero-overlay col-span-full' style='background-image: url("https://picsum.photos/id/942/1500/1000")'></div> -->
            <div class='hero-content text-6xl sm:text-8xl  m-6 text-primary flex-col text-center md:text-start md:flex-row' >
                <figure class='w-48 md:w-64 shrink-0 grow-0 relative -right-10 md:right-0'>
                <img src="images/logo.svg" >
                </figure>
                <h1>Corvallis Music Collective</h1>
            </div>
        </section>
        <header class='border-t-2 border-black sticky -top-1 z-50 text-3xl bg-base-100'>
            <nav class='grid grid-cols-3 grow '>
                <a class='brand' href='/' class='grow'>
                    <div class='flex text-5xl  items-center justify-start display text-white' style='-webkit-text-stroke: 1px black;'>
                        <div class='w-12 p-2' >
                            <img src='images/favicon.svg'>
                        </div>
                        <span>CMC</span>
                    </div>
                </a>
                <ul class=' grow flex items-center justify-center'>
                    <li>
                        <a class='btn btn-ghost' href='#events'>Events</a>
                        <a class='btn btn-ghost' href='#about'>About</a>
                        <!-- <a class='btn btn-ghost'>Contact</a> -->
                    </li>
                </ul>
                <ul class='grow justify-end flex items-center'>
                </ul>
            </nav>
            <div class='bg-primary h-3'></div>
            <div class='bg-secondary h-2'></div>
            <div class='bg-accent h-1'></div>
        </header>
        <section  >
            <header class=''>
                <h2 id='events'> Events</h2>
                <a href='/events'>More</a>
            </header>
            <ul class='xl:grid grid-cols-3 gap-4'>
                    @foreach( $events as $event)
                    <li >
                        <x-event-card :event="$event" />
                    </li>
                    @endforeach
            </ul>
        </section>
        <!-- <section class='bg-white'>
            <header class='flex justify-between items-baseline'>
            <h2 class='text-3xl sm:text-xl md:text-7xl display'
                id='news'>News</h2>
            <a class='link text-xl' href='/news'>More</a>
            </header>
            <ul class='2xl:grid grid-cols-3 gap-4'>
                @foreach($posts as $post)
                <li class='mb-6 h-full'>
                    <x-post-card :post="$post" />
                </li>
                @endforeach
            </ul>
        </section> -->
        <section>
            <header class='flex justify-between items-baseline'>
                <h2 id='about' class='text-black text-3xl sm:text-5xl md:text-7xl display'>About</h2>
            </header>
            <div class='flex  flex-wrap md:flex-nowrap items-start gap-10 px-8 '>
                <div class="prose-lg min-w-96">
                    <p>
                        Corvallis Music Collective is a member-driven non-profit with a mission to make playing and
                        listening to local live music more accessible. We’re starting by establishing a location
                        which can be used as practice and teaching space for local musicians and operating a much-needed
                        all-ages venue. From there we will expand into community-building services including hosting events,
                        after-school programs and group lessons. Currently, we’re building our member base and seeking both
                        funding and a location that meets our needs.
                    </p>
                    <p>
                        If you love music and want to get involved, we would love to hear from you! Contact us to
                        learn more about our upcoming events and how you can get involved.
                    </p>
                </div>
                <div style='flex: 1 0 24rem'>
                @livewire('contact-form')
                </div>
            </div>
        </section>
    </main>

    <script>
        window.addEventListener('scroll', () => {
            const header = document.querySelector('header');
            if (window.scrollY > header.offsetTop) {
                header.classList.add('stuck');
            } else {
                header.classList.remove('stuck');
            }
        })
    </script>
@endsection
