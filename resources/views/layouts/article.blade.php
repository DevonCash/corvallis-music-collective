@extends('layouts.app')
@section('body')
<main class="grow">
    @section('content')
    <div class='prose mx-auto'>
      {!! $slot !!}
</div>
    @show
</main>
@endsection
