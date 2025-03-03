<header
  class="flex w-full items-center gap-8 p-4 px-12 pb-10 flex-col md:flex-row text-center md:text-left mb-8"
>
  <x-logo class="size-48 shrink-0 relative left-10 md:left-0 -my-6" />
  <div class="flex flex-col items-stretch grow h-full md:items-start">
    <h1 class="text-4xl font-bold text-primary mb-6">
      Corvallis Music Collective
    </h1>
    <nav>
      <ul class="flex flex-row gap-4 justify-center md:justify-start">
        <li><a class="link" href="/">Home</a></li>
        <li>
          <a class="link" href="/about">About Us</a>
        </li>
        <li><a class="link" href="/about/contribute">Contribute</a></li>
        <li><a class="link" href="/about/volunteer">Volunteer</a></li>
      </ul>
    </nav>
  </div>
  <div>
    @auth
    <div class="dropdown">
          <a
            tabindex="0"
            role="button"
            style="height:unset;"
            class="btn p-2 btn-ghost flex gap-2 items-center flex-nowrap text-nowrap"
          >
            <div class="text-right">
              <div class="text-xs opacity-70 mt-1">{{auth()->user()->email}}</div>
            </div>
            <x-filament::icon icon="heroicon-m-chevron-down" />
          </a>
          <ul
            tabindex="0"
            class="menu rounded-box dropdown-content w-full bg-primary text-primary-content z-50 mt-1 shadow-xl"
          >
            <li>
              <a href="/member/schedule">
                <x-filament::icon icon="heroicon-m-calendar" class="size-5" />
                Schedule
              </a>
            </li>
            <li>
              <a href="/member/profile">
                <x-filament::icon icon="heroicon-m-user" class="size-5" />
                Profile
              </a>
            </li>
            <li>
              <form method="post" action="/member/logout">
                <button class="flex items-center gap-2">
                  <x-filament::icon icon="heroicon-m-arrow-right-on-rectangle" class="size-5" />
                  Logout
                </button>
              </form>
            </li>
          </ul>
        </div>
    @endauth

    @guest
        <div>
          <a class="btn btn-secondary" href="{{ route('filament.member.auth.login') }}">
            Sign Up / Login
          </a>
        </div>
    @endguest
  </div>
</header>

