@extends('layouts.bare', ['title' => 'Login'])

@section('body')
<div class="page hero h-screen">
    <div class='hero-overlay'>
        <picture>
        <source srcset="https://picsum.photos/id/942/400/600" media="(max-width: 400)">
        <img src="https://picsum.photos/id/942/1500/1000" alt="background" class="w-full h-full object-cover" />
        </picture>
    </div>
    <div class='hero-content flex-col'>
    <h1 class='text-4xl m-10 leading-tight text-primary'>
        <div>Corvallis</div>
        <div>Music</div>
        <div>Collective</div>
    </h1>
   <div class="card card-compact md:card-normal bg-white">
       <div class='card-body'>
       @if(!session()->has('success'))
     <form action="{{ route('login') }}" method="post" class="space-y-4">
       @csrf
       <h2 class='display'>Login</h2>
       <div class="form-conrol">
         <label for="email" class="block">Email</label>
         <input type="email" name="email" id="email" class="input input-bordered w-full" />
         @error('email')
           <p class="text-sm text-red-600">{{ $message }}</p>
         @enderror
       </div>
       <button class="btn btn-primary float-end">Login</button>
     </form>
     @else
        <p>Please click the link sent to your email to login</p>
     @endif
       </div>
   </div>
    </div>
 </div>
 @endsection
