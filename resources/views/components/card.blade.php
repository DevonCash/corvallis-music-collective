<div class='card model-card  @container ' style='grid-template-columns: min-content auto; grid-template-rows: min-content auto;'>
    <time class='@md:min-w-36 text-xl whitespace-nowrap display bg-primary  text-primary-content flex items-center justify-center p-2' >
        @if(!empty($date))
            {{ $date }}
        @endif
    </time>
    <time class='text-sm @sm:text-xl display bg-base-100  flex items-center justify-start px-3 flex-wrap p-1 gap-x-3'>
         @if(!empty($time))
             {{ $time }}
         @endif
    </time>
    <picture class='skeleton col-span-full @md:col-span-1 shadow'>
        @if(!empty($image))
        <img src="{{$image}}" name="image" class='h-48 w-full @md:w-48 @md:h-full' style='object-fit: cover'>
            @endif
    </picture>
    <div class='col-span-full @md:col-span-1 shadow flex flex-col'>
        <div class='card-body overflow-hidden'>
            {{ $slot }}
        </div>
    </div>
</div>
    @if(auth()->check())
    <!--TODO: check if user is admin and logged in --!>
    <div class='text-xs join absolute'>
        <div class='join-item bg-neutral text-gray-100 display lowercase flex items-center px-2'>
            Admin
        </div>
        @if(!empty($admin))
            {{$admin}}
        @endif
    </div>
    @endif
