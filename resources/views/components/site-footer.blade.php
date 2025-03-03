
<footer class="footer flex justify-around py-8 bg-base-300 mt-8 gap-8 px-8">
  <aside class="flex flex-col gap-2 items-center">
    <h2>Corvallis<br />Music<br />Collective</h2>
    <x-theme-selector />
  </aside>
  <div class="flex flex-col gap-1">
    <h6 class="footer-title">Contact</h6>
    <a class="link flex items-center gap-2">
      <x-filament::icon icon="heroicon-o-map-pin" class="size-5"></x-filament::icon>
      <span
        >6775 A Philomath Blvd, <div class="text-xs">
          Corvallis, OR 97333
        </div></span
      >
    </a>
    <a class="link flex items-center gap-2" href="mailto:contact@corvmc.org">
      <x-filament::icon icon="heroicon-o-envelope" class="size-5"></x-filament::icon>
      contact@corvmc.org
    </a>
    <a
      class="link flex items-center gap-2"
      href="https://www.facebook.com/profile.php?id=61557301093883"
    >
      <x-filament::icon icon="heroicon-o-globe-alt" class="size-5"></x-filament::icon>
      Corvallis Music Collective
    </a>
    <a class="link flex items-center gap-2" href="https://www.instagram.com/corvmc/">
      <x-filament::icon icon="heroicon-o-camera" class="size-5"></x-filament::icon>
      @corvmc
    </a>
  </div>
  <nav>
    <h6 class="footer-title">About</h6>
    <ul>
      <li><a class="link" href="/about/programs">Programs</a></li>
      <li><a class="link" href="/about/contribute">Contribute</a></li>
      <li><a class="link" href="/about/donate">Donate</a></li>
      <li><a class="link" href="/about/volunteer">Volunteer</a></li>
    </ul>
  </nav>
  <div>
    <x-logo class="size-48 opacity-50 -scale-x-100 -mt-6" />
  </div>
</footer>
