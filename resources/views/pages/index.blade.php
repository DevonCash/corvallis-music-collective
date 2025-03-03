@extends('layouts.app')
@section('content')
<main class="grow">
    <section class="hero container mx-auto" style="min-height: 50vh;">
      <div class="hero-content py-16">
        <div class="md:grid grid-cols-2 items-end gap-6">
          <h2 class="grow md:text-right md:text-5xl text-secondary font-bold">
            Building and connecting music communities in Corvallis
          </h2>
          <div class="grow prose">
            <p>
              CorvMC is a community-driven organization that supports local
              music and musicians. We believe that music is a vital part of a
              healthy community.
            </p>
            <p>
              <a class="link" href="/about/contribute">Join Our Community!</a>
            </p>
          </div>
        </div>
      </div>
    </section>
    <section>
      <div class="space-y-8 max-w-3xl mx-auto">
        <div class="prose max-w-none">
          <p class="font-bold">
            We're excited to announce our new community music space is now open
            in West Corvallis! After months of preparation, we've found a home
            for Corvallis musicians to rehearse, connect, and grow together.
          </p>

          <p>
            Our main room features a 12'x12' stage and serves as both a
            rehearsal space and concert venue, bringing musicians and audiences
            together. For smaller groups or individual practice, our intimate
            second room provides the perfect setting to develop your sound. Both
            spaces are available to members for hourly booking.
          </p>
        </div>

        <div class="card bg-base-200">
          <div class="card-body">
            <div class="card-title text-primary">
              Looking for a place to practice?
            </div>
            <p class="mb-3">
              Space starts at $30/hr, with discounts for contributing members.
              Join for free to get started!
            </p>
            <div class="card-actions">
              <a class="btn btn-primary" href="/signup"
                >Become a Member</a
              >
            </div>
          </div>
        </div>
        <div class="prose">
          <p>Our shared gear library includes:</p>
          <ul>
            <li>PA systems and mixing boards</li>
            <li>Microphones and stands</li>
            <li>XLR and ¼" cables</li>
            <li>Power cables</li>
            <li>Guitar amps</li>
            <li>Bass cabinet and head</li>
            <li>4-piece drum kit with hi-hat, ride, and crash cymbals</li>
            <li>Electric guitar</li>
            <li>Bass guitar</li>
          </ul>

          <p>
            Need something specific? Just ask – we're here to help our community
            make music happen.
          </p>
        </div>

        <div class="card bg-base-200">
          <div class="card-body">
            <h2 class="card-title">Share Your Gear, Share the Music</h2>
            <p class="mb-3">
              Resources work better when they're shared. If you have equipment
              gathering dust, consider contributing it to our community! Your
              donation helps make music more accessible for everyone in
              Corvallis.
            </p>
            <div class="card-actions">
              <a href="/about/contribute" class="btn btn-primary"
                >Learn about contributing →</a
              >
            </div>
          </div>
        </div>

        <div class="prose max-w-none">
          <p>
            Join us in our spaces and be part of making music more accessible in
            Corvallis. Everyone is welcome here.
          </p>
        </div>
      </div>
    </section>
  </main>
@endsection