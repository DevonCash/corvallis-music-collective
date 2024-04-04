@component('mail::message')
  To finish logging in, please click the link below
    @component('mail::button', ['url' => $url])
        Click to Login
    @endcomponent
@endcomponent
