<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/svg+xml" href="{{asset('images/favicon.svg')}}">
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @vite(['resources/js/app.js','resources/css/app.css'])
  <title>{{ $title }}</title>
</head>
<body data-theme="cmc_light">
  @yield('body')
  <svg
    id='noise'
    xmlns='http://www.w3.org/2000/svg'
    xmlns:xlink='http://www.w3.org/1999/xlink'
    width='300' height='300'>

      <filter id='n' x='0' y='0'>
              <feTurbulence
                type='fractalNoise'
                baseFrequency='0.75'
                stitchTiles='stitch'/>
      </filter>

      <rect width='300' height='300' fill='#fff'/>
      <rect width='300' height='300' filter="url(#n)" opacity='0.50'/>
  </svg>
</body>
</html>
