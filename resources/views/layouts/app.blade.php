@props(['header' => true, 'footer' => true])

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="description" content="Astro description" />
    <meta name="viewport" content="width=device-width" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link
      href="https://fonts.bunny.net/css?family=lexend:200,400,700,800"
      rel="stylesheet"
    />
    <script
      async
      src="https://widgets.givebutter.com/latest.umd.cjs?acct=jSnvpGVlVaDNar62&p=other"
    ></script>

    @vite('resources/css/app.css', 'resources/js/app.js')

    <title>{{$title ?? "Corvallis Music Collective"}}</title>
  </head>
  <body class="flex flex-col" style="min-height: 100vh;">
    @if($header !== false)
      <x-site-header />
    @endif
    <div class="grow" x-data>
      @section('content')
      {{ $slot?? '' }}
      @show
    </div>
    @if($footer !== false)
      <x-site-footer />
    @endif
  </body>
</html>
