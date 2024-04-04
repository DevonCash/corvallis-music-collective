<x-card image={{$event->image}}>
    <x-slot:date>
        {{$event->start_at->format('F j')}}
    </x-slot:date>
    <x-slot:time>
        <time class='whitespace-nowrap'> {{$event->start_at->format('g:i A')}}</time>
        @if($event->end_at)
        -
        @if($event->end_at->isSameDay($event->start_at))
            <time class='whitespace-nowrap'  datetime={{$event->end_at->toISOString()}}>{{$event->end_at->format('g:i A')}}</time>
        @else
            <time class='whitespace-nowrap'  datetime={{$event->end_at->toISOString()}}> {{$event->end_at->format('j F, g:i A')}}</time>
        @endif
        @endif
    </x-slot:time>
    <h3 class='card-title'>{{$event->name}}</h3>
    <x-markdown class='prose-sm '>{{$event->description}}</x-markdown>
    @if($event->location)
        <div class='at'><a class='link'>{{$event->location}}</a></div>
    @endif
    @if($event->cost)
        <div class='cost'>{{$event->cost}}</div>
    @endif
    <div class='card-actions gap-0 justify-end  items-end' >
        <a class='btn grow @md:flex-initial btn-primary' href="{{$event->url}}">Get Tickets</a>
    </div>
    <x-slot:admin>
        <a class='join-item btn btn-secondary btn-outline btn-xs border-t-0' href='/admin/events/{{$event->id}}/edit'>Edit</a>
    </x-slot:admin>

</x-card>
